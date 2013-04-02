<?php



/**
 * Мениджър на баланси
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_Balances extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Оборотни ведомости";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, acc_Wrapper,Accounts=acc_Accounts,plg_Sorting, plg_Printing';
                    
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'acc_BalanceDetails';
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = 'Оборотна ведомост';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin,acc';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,acc';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'no_one';
    

    /**
     * Кой може да добавя?
     */
    var $canAdd = 'no_one';
    
    /**
     * @var acc_Accounts
     */
    var $Accounts;
    

    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'acc/tpl/SingleLayoutBalance.shtml';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('periodId', 'key(mvc=acc_Periods,select=title)', 'caption=Период,column=none,mandatory');
        $this->FLD('fromDate', 'date', 'input=none,caption=Период->от');
        $this->FLD('toDate', 'date', 'input=none,caption=Период->до');
        $this->FLD('state', 'enum(draft=Горещ,active=Активен,rejected=Изтрит)', 'caption=Тип,input=none');
        $this->FLD('lastCalculate', 'datetime', 'input=none,caption=Последно изчисляване');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function act_Single()
    {
        if ($accountId = Request::get('accId', 'int')) {
            $this->accountRec = $this->Accounts->fetch($accountId);
        }
        
        return parent::act_Single();
    }
    
    
    /**
     * Извиква се след изчисляването на необходимите роли за това действие
     */
    static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action)
    {
        if ($mvc->accountRec) {
            if ($action == 'edit' || $action == 'delete') {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на титлата в единичния изглед
     */
    static function on_AfterPrepareSingleTitle($mvc, $data)
    {
        if ($mvc->accountRec) {
            $data->row->accountId = acc_Accounts::getRecTitle($mvc->accountRec);
        } else {
            $data->row->accountId = 'Обобщен';
        }

        $data->title = new ET('<span class="quiet">Баланс</span> ' . $data->row->periodId);
    }
    
    
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        if (!empty($mvc->accountRec)) {
            $data->toolbar->addBtn('Обобщен ' . $data->row->periodId, array($mvc, 'single', $data->rec->id));
        }
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    static function on_BeforeSave($mvc, &$id, $rec)
    {
        $Periods = &cls::get('acc_Periods');
        $periodRec = $Periods->fetch($rec->periodId);
        
        $rec->baseBalanceId = $mvc->getBaseBalanceId($periodRec);
        $rec->fromDate = $periodRec->start;
        $rec->toDate = $periodRec->end;
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    static function on_AfterSave($mvc, &$id, $rec)
    {
        $mvc->acc_BalanceDetails->calculateBalance($rec);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    private function getBaseBalanceId($periodRec)
    {
        $balanceId = NULL;
        
        $Periods = &cls::get('acc_Periods');
        
        if ($prevPeriodRec = $Periods->fetchPreviousPeriod($periodRec)) {
            $balanceId = $this->fetchField("#periodId = {$prevPeriodRec->id}", 'id');
        }
        
        return $balanceId;
    }
  

    /**
     * Презичислява балансите за периодите, в които има промяна ежеминутно
     */
    function cron_Recalc()
    {
        // Взема всички периоди (без closed) от най-стария, към най-новия
        // За всеки период, ако има стойност в lastEntry:
        //  - взема съответстващия му баланс (мастера)
        //  - ако няма такъв баланс, то той се изчислява
        //  - ако има такъв баланс, то той се изчислява, само ако неговото поле lastCalc <= lastEntry
        // след преизчисляване на баланс, полето lastCalc се попълва с времето, когато е започнало неговото изчисляване
        // продължава се със слеващия баланс
        
        $pQuery = acc_Periods::getQuery();
        $pQuery->orderBy('#end', 'ASC');
        $pQuery->where("#state != 'closed'");
        while($pRec = $pQuery->fetch()) {
            
            if($pRec->lastEntry) {
                $rec = self::fetch("#periodId = $pRec->id");
                if(!$rec || ($rec->lastCalculate <= $pRec->lastEntry)) {

                    if(!$rec) {
                        $rec = new stdClass();
                    }
                    $lastCalculate = dt::verbal2mysql();

                    $rec->periodId = $pRec->id;

                    self::save($rec);

                    $bDetail = cls::get('acc_BalanceDetails');

                    $bDetail->calculateBalance($rec);

                    $rec->lastCalculate = $lastCalculate;

                    self::save($rec);
                }
            }
        }

    }
}
