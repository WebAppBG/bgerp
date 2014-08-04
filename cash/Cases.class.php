<?php



/**
 * Каса сметки
 *
 *
 * @category  bgerp
 * @package   cash
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cash_Cases extends core_Master {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'acc_RegisterIntf, cash_CaseAccRegIntf';
    
    
    /**
     * Заглавие
     */
    var $title = 'Фирмени каси';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Каса";
    
    
    /**
     * Икона за единичен изглед
     */
    var $singleIcon = 'img/16/safe-icon.png';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт, name, cashiers, blAmount=Сума';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'name';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, acc_plg_Registry, cash_Wrapper, plg_Current, doc_FolderPlg, plg_Created, plg_Rejected';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от 
     * таблицата.
     * 
     * @see plg_RowTools
     * @var $string име на поле от този модел
     */
    var $rowToolsField = 'tools';
    
    
    /**
     *  Кой може да чете
     */
    var $canRead = 'ceo, cash';
    
    
    /**
     * Кой може да пише
     */
    var $canWrite = 'ceo, cashMaster';
    
    
    /**
     * Кой  може да вижда счетоводните справки?
     */
    var $canReports = 'ceo,cash,acc';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo, cash';


   /**
	* Кой може да селектира?
	*/
	var $canSelect = 'ceo,cash';
	
	
	/**
	 * Кой може да селектира всички записи
	 */
	var $canSelectAll = 'ceo,cashMaster';
	
	
	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,cash';
    
    
	/**
	 * Кое поле отговаря на кой работи с дадена каса
	 */
	var $inChargeField = 'cashiers';
	
	
	/**
     * Детайли на този мастър обект
     * 
     * @var string|array
     */
    public $details = 'AccReports=acc_ReportDetails';
    
    
    /**
     * По кои сметки ще се правят справки
     */
    public $balanceRefAccounts = '501';
    
    
    /**
     * По кой итнерфейс ще се групират сметките 
     */
    public $balanceRefGroupBy = 'cash_CaseAccRegIntf';
    
    
    /**
     * Всички записи на този мениджър автоматично стават пера в номенклатурата със системно име
     * $autoList.
     * 
     * @see acc_plg_Registry
     * @var string
     */
    var $autoList = 'case';
 
    
    /**
     * Файл с шаблон за единичен изглед
     */
    var $singleLayoutFile = 'cash/tpl/SingleLayoutCases.shtml';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name', 'varchar(255)', 'caption=Наименование,oldFiled=Title,mandatory');
        $this->FLD('cashiers', 'userList(roles=cash|ceo)', 'caption=Касиери,mandatory');
    }
    
    
	/**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    function on_AfterRecToVerbal(&$mvc, &$row, &$rec)
    {
        $row->STATE_CLASS .= ($rec->state == 'rejected') ? " state-rejected" : " state-active";
        
        $caseItem = acc_Items::fetchItem($mvc->getClassId(), $rec->id);
        $Balance = new acc_ActiveShortBalance($caseItem->id);
        $rec->blAmount = $Balance->getAmount($mvc->balanceRefAccounts, $caseItem->id);
        
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        $row->blAmount = "<span style='float:right'>" . $Double->toVerbal($rec->blAmount) . "<span>";
        if($rec->blAmount < 0){
        	$row->blAmount = "<span style='color:red'>{$row->blAmount}</span>";
        }
    }
    
    
    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    static function on_AfterPrepareListFields($mvc, $data)
    {
    	$data->listFields['blAmount'] .= ", " . acc_Periods::getBaseCurrencyCode();
    }
    
    
    /**
     * Подготвя и осъществява търсене по каса, изпозлва се
     * в касовите документи
     * @param stdClass $data 
     * @param array $fields - масив от полета в полета в които ще се
     * търси по caseId
     */
    public static function prepareCaseFilter(&$data, $fields = array())
    {
    	$data->listFilter->FNC('case', 'key(mvc=cash_Cases,select=name,allowEmpty)', 'caption=Каса,width=10em,silent');
		$data->listFilter->showFields .= ',case';
		$data->listFilter->setDefault('case', static::getCurrent('id', FALSE));
		$data->listFilter->input();
		if($filter = $data->listFilter->rec) {
			if($filter->case) {
				foreach($fields as $fld){
					$data->query->where("#{$fld} = {$filter->case}");
				}
			}
		}
    }
    
    
    /**
     * След рендиране на лист таблицата
     */
    public static function on_AfterRenderListTable($mvc, &$tpl, &$data)
    {
    	if(!count($data->rows)) return;
    	foreach ($data->recs as $rec){
    		$total += $rec->blAmount;
    	}
    	
    	$Double = cls::get('type_Double');
    	$Double->params['decimals'] = 2;
    	$total = $Double->toVerbal($total);
    	if($rec->blAmount < 0){
    		$total = "<span style='color:red'>{$total}</span>";
    	}
    	
    	$lastRow = new ET("<tr style='text-align:right' class='state-closed'><td colspan='3'>[#caption#]: &nbsp;</td><td colspan='2' ><b>[#total#]</b></td></tr>");
    	$lastRow->replace(tr("Общо"), 'caption');
    	$lastRow->replace($total, 'total');
    	
    	$tpl->append($lastRow, 'ROW_AFTER');
    }
    
    
    /*******************************************************************************************
     * 
     * ИМПЛЕМЕНТАЦИЯ на интерфейса @see crm_ContragentAccRegIntf
     * 
     ******************************************************************************************/
    
    
    /**
     * @see crm_ContragentAccRegIntf::getItemRec
     * @param int $objectId
     */
    static function getItemRec($objectId)
    {
        $self = cls::get(__CLASS__);
        $result = NULL;
        
        if ($rec = $self->fetch($objectId)) {
            $result = (object)array(
                'num' => $rec->id,
                'title' => $rec->name,
                'features' => 'foobar' // @todo!
            );
        }
        
        return $result;
    }
    
    
	/**
	 * Преди подготовка на резултатите
	 */
	function on_AfterPrepareListFilter($mvc, &$data)
	{
		if(!haveRole($mvc->canSelectAll)){
			
			// Показват се само записите за които отговаря потребителя
			$cu = core_Users::getCurrent();
			$data->query->where("#cashiers LIKE '%|{$cu}|%'");
		}
	}
    
    
    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     * @param int $objectId
     */
    static function itemInUse($objectId)
    {
        // @todo!
    }
    
    /**
     * КРАЙ НА интерфейса @see acc_RegisterIntf
     */
}
