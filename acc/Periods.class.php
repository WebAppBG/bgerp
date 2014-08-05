<?php



/**
 * Мениджира периодите в счетоводната система
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * Текущ период = период в който попада днешната дата
 * Активен период = период в състояние 'active'. Може да има само един активен период
 * Чакащ период - период в състояния 'pending' който е след активния период и преди текущия (ако двата не съвпадат)
 * Бъдещ период - период, който започва след изтичането на текущия
 * Приключен период - период в състояние "closed"
 */
class acc_Periods extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = "Счетоводни периоди";
    

    /**
     * Заглавие в единствено число
     */
    var $singleTitle = 'Период';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, acc_WrapperSettings, plg_State, plg_Sorting';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "id, title, start=Начало, end, vatRate, baseCurrencyId, state, lastEntry, close=Приключване";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,acc';
    
    
    /**
     * Кой може да пише?
     */
    var $canEdit = 'ceo,acc';
    
    
    /**
     * Кой може да пише?
     */
    var $canClose = 'ceo,accMaster';
    
    
    /**
     * Кой може да редактира системните данни
     */
    var $canEditsysdata = 'ceo,accMaster';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo,acc';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Кой може да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('end', 'date(format=d.m.Y)', 'caption=Край,mandatory');
        $this->FLD('state', 'enum(draft=Бъдещ,active=Активен,closed=Приключен,pending=Чакащ)', 'caption=Състояние,input=none');
        $this->FNC('start', 'date(format=d.m.Y)', 'caption=Начало', 'dependFromFields=end');
        $this->FNC('title', 'varchar', 'caption=Заглавие,dependFromFields=start|end');
        $this->FLD('lastEntry', 'datetime', 'caption=Последен запис,input=none');
        $this->FLD('vatRate', 'percent', 'caption=Параметри->ДДС,oldFieldName=vatPercent');
        $this->FLD('baseCurrencyId', 'key(mvc=currency_Currencies, select=code, allowEmpty)', 'caption=Параметри->Валута,width=5em');
    }


    /**
     * Изчислява полето 'start' - начало на периода
     */
    static function on_CalcStart($mvc, $rec)
    {
        $rec->start = dt::mysql2verbal($rec->end, 'Y-m-01');
    }
    
    
    /**
     * Изчислява полето 'title' - заглавие на периода
     */
    static function on_CalcTitle($mvc, $rec)
    {
        $rec->title = dt::mysql2verbal($rec->end, "F Y");
    }
    
    
    /**
     * Сортира записите по поле end
     */
    static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('end', 'DESC');
    }


    /**
     * Добавя за записите поле start и бутони 'Справки' и 'Приключи'
     * Поле 'start' - това поле не съществува в модела. Неговата стойност е end за предходния период + 1 ден.
     * Поле 'reports' - в това поле ще има бутон за справки за периода.
     *
     * @param stdCLass $row
     * @param stdCLass $rec
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        if($mvc->haveRightFor('close', $rec)) {
           $row->close = ht::createBtn('Приключване', array($this, 'Close', $rec->id), 'Наистина ли желаете да приключите периода?', NULL, 'ef_icon=img/16/lock.png,title=Приключване на периода');
        }
        
        if($repId = acc_Balances::fetchField("#periodId = {$rec->id}", 'id')){
        	$row->title = ht::createLink($row->title, array('acc_Balances', 'Single', $repId), NULL, 'ef_icon=img/16/table_sum.png');
        }
       
        $curPerEnd = static::getPeriodEnd();
        if($rec->end == $curPerEnd){
        	$row->id = ht::createElement('img', array('src' => sbf('img/16/control_play.png', ''), 'style' => 'display:inline-block;margin-right:5px')) . $row->id;
        }
    }
    
    
    /**
     * Връща запис за периода, към който се отнася датата. 
     * Ако не е зададена $date, връща текущия период
     *
     * @return stdClass $rec
     */
    static function fetchByDate($date = NULL)
    {
        $lastDayOfMonth = dt::getLastdayOfMonth($date);
        $rec = self::fetch("#end = '{$lastDayOfMonth}'");

        return $rec;
    }
	
	
    /**
     * Изпълнява се след създаване на нов запис
     */
    public static function on_AfterCreate($mvc, $rec)
    {
    	// Форсираме перо за месеца и годината на периода
    	static::forceYearAndMonthItems($rec->end);
    }
    
    
	/**
	 * Форсира пера за месеца и годината на дадена дата
	 * 
	 * @param datetime $date - дата
	 * @return stdClass -> year - ид на перото на годината
	 * 					-> month - ид на перото на месеца
	 */
	public static function forceYearAndMonthItems($date)
	{
		// Взимаме пълното наименование на месеца
		$month = dt::mysql2verbal($date, 'F');
		
		// Коя е годината
		$year = dt::mysql2verbal($date, 'Y');
		
		// Кода на месеца
		$monthNum = dt::mysql2verbal($date, 'm');
		
		// Ако има перо за този месец го връщаме, ако няма създаваме ново
		$monthItem = acc_Items::forceSystemItem($month, $monthNum, 'month');
		
		// Ако има перо за тази година го връщаме, ако няма създаваме ново
		$yearItem = acc_Items::forceSystemItem($year, $year, 'year');
		
		// Връщаме ид-то на перата на годината и месеца
		return (object)array('year' => $yearItem->id, 'month' => $monthItem->id);
	}
    
    
    /**
     * Връща записа за периода предхождащ зададения.
     *
     * @param stdClass $rec запис за периода, чийто предшественик търсим.
     * @return stdClass запис за предходния период или NULL ако няма
     */
    static function fetchPreviousPeriod($rec)
    {
        $query = self::getQuery();
        $query->where("#end < '{$rec->end}'");
        $query->orderBy('end', 'DESC');
        $recPrev = $query->fetch();
        
        return $recPrev;
    }

    
    /**
     * Проверява датата в указаното поле на формата дали е в отворен период
     * и записва във формата съобщение за грешка или предупреждение
     * грешка или предупреждение няма, ако датата е от началото на активния, 
     * до края на насотящия период
     */
    static function checkDocumentDate($form, $field = 'date')
    {
		$date = $form->rec->{$field};
        
        if(!$date) {

            return;
        }

        $rec = self::forceActive();
 
        if($rec->start >= $date) {
            $form->setError($field, "Датата е преди активния счетоводен период| ($rec->title)");
            
            return;
        }
        
        $rec = self::fetchByDate($date);

        if(!$rec) {
            $form->setError($field, "Датата е в несъществуващ счетоводен период");
            
            return;
        }

        if($date > dt::getLastDayOfMonth()) {
            $form->setWarning($field, "Датата е в бъдещ счетоводен период");
            
            return;
        }

        return TRUE;
    }


    /**
     * Връща посочения период или го създава, като създава и периодите преди него
     */
    function forcePeriod($date)
    {
        $end = dt::getLastDayOfMonth($date);

        $rec = self::fetch("#end = '{$end}'");

        if($rec) return $rec;

        // Определяме, кога е последният ден на началния период
        $query = self::getQuery();
        $query->orderBy('#end', 'ASC');
        $query->limit(1);
        $firstRec = $query->fetch();
        if(!$firstRec) {
            $firstRec = new stdClass();
            if(defined('ACC_FIRST_PERIOD_START') && ACC_FIRST_PERIOD_START){
            	
            	// Проверяваме дали ACC_FIRST_PERIOD_START е във валиден формат за дата
            	$dateArr = date_parse(ACC_FIRST_PERIOD_START);
            	if(checkdate($dateArr["month"], $dateArr["day"], $dateArr["year"])){
            		
            		// Ако е валидна дата, за първи запис е посочения месец
            		$firstRec->end = dt::getLastDayOfMonth(dt::verbal2mysql(ACC_FIRST_PERIOD_START));
            	} else {
            		
            		// При грешна дата се създава предходния месец на текущия
            		$firstRec->end = dt::getLastDayOfMonth(NULL, -1);
            	}
            	
            } else {
            	$firstRec->end = dt::getLastDayOfMonth(NULL, -1);
            }
        }

        // Ако датата е преди началния период, връщаме началния
        if($end < $firstRec->end) {

            return self::forcePeriod($firstRec->end);
        }
        
        // Конфигурационни данни на пакета 'acc'
        $conf = core_Packs::getConfig('acc');
        
        // Връзка към сингълтон инстанса
        $me = cls::get('acc_Periods');

        // Ако датата е точно началния период, създаваме го, ако липсва и го връщаме
        if($end == $firstRec->end) {
            if(!$firstRec->id) {
                $firstRec->vatRate = $conf->ACC_DEFAULT_VAT_RATE;
                $firstRec->baseCurrencyId = currency_Currencies::getIdByCode($conf->BASE_CURRENCY_CODE);
                self::save($firstRec);
                $firstRec = self::fetch($firstRec->id); // За титлата
                $me->actLog .= "<li style='color:green;'>Създаден е начален период $firstRec->title</li>";
            }
            
            return $firstRec;
        }

        // Ако периода е след началния, то:
        
        // 1. вземаме предишния период
        $prevEnd = dt::getLastDayOfMonth($date, -1);
        $prevRec = self::forcePeriod($prevEnd);
        
        // 2. създаваме търсения период на база на началния
        $rec = new stdCLass();
        $rec->end = $end;

        // Периодите се създават в състояние драфт
        $curPerEnd = static::getPeriodEnd();
        if($rec->end > $curPerEnd){
        	$rec->state = 'draft';
        } else {
        	$rec->state = 'pending';
        }
        
        // Вземаме последните
        setIfnot($rec->vatRate, $prevRec->vatRate, ACC_DEFAULT_VAT_RATE);
		
        if($prevRec->baseCurrencyId) {
            $rec->baseCurrencyId = $prevRec->baseCurrencyId;
        } else {
            $rec->baseCurrencyId = currency_Currencies::getIdByCode($conf->BASE_CURRENCY_CODE);
        }
		
        self::save($rec);
        
        $rec = self::fetch($rec->id);

        $me->actLog .= "<li style='color:green;'>Създаден е период $rec->title</li>";

        return $rec;
    }


    /**
     * Връща активния период. Създава такъв, ако няма
     */
    static function forceActive()
    {
        if(!($rec = self::fetch("#state = 'active'"))) {

            $me = cls::get('acc_Periods');

            $query = self::getQuery();
            $query->where("#state != 'closed'");
            $query->orderBy('#end', 'ASC');
            $query->limit(1);
            
            $rec = $query->fetch();

            $rec->state = 'active';

            self::save($rec, 'state');
            
            $me->actLog .= "<li style='color:green;'>Зададен е активен период {$rec->end}</li>";
        }

        return $rec;
    }
    
    
    /**
     * Маркира периода, съответстващ на зададена дата, като променен.
     * 
     * Тази маркировка се използва при преизчисляването на баланса.
     * 
     * @param string $date дата, към която
     * @return boolean
     */
    public static function touch($date)
    {
        expect($periodRec = static::fetchByDate($date), "Липсва счетоводен период вкючващ {$date}");

        $periodRec->lastEntry = dt::now(TRUE); // дата и час
        
        return static::save($periodRec);
    }

    
    /**
     * 
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm(core_Mvc $mvc, $data)
    {
        if ($data->form->rec->id) {
            $data->form->setReadOnly('end');
        }
    }

    
    /**
     * Премахва възможността да се редактират периоди със state='closed'
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     * Ако state = 'closed' премахва възможността да се редактира записа.
     *
     * @param acc_Periods $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass|NULL $rec
     * @param int|NULL $userId
     */
    static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if(!$rec) {
            return;
        }

        // Последния ден на текущия период
        $curPerEnd = static::getPeriodEnd();
        
        // Забраняваме всички модификации за всички минали периоди
        if ($action == 'edit'){
            if($rec->end <= $curPerEnd) {
                $requiredRoles = "no_one";
            }
        }
        
        // Период може да се затваря само ако е изтекъл
        if($action == 'close' && $rec->id) {
            $rec = self::fetch($rec->id);
            if($rec->end >= $curPerEnd || $rec->state != 'active') {
                 $requiredRoles = "no_one";
            }
            
            $balRec = acc_Balances::fetch("#periodId = {$rec->id}");

            if(($balRec->lastCalculate || $rec->lastEntry) && ($balRec->lastCalculate < $rec->lastEntry)) {
                $requiredRoles = "no_one";
            }
        }
    }

  
    
    /**
     * Затваря активен период и задава на следващия период да е активен
     * Ако няма следващ го създава
     *
     * @return string $res
     */
    function act_Close()
    {
        $this->requireRightFor('close');

        // Затваряме период
        $id = Request::get('id', 'int');
        
        $rec = new stdClass();
        
        $rec = $this->fetch("#id = '{$id}'");
        
        // Очакваме, че затваряме активен период
        $this->requireRightFor('close', $rec);
        
        // Новото състояние е 'Затворен';
        $rec->state = "closed";
        
        $this->save($rec);
        
        $res = "Затворен е период |*<span style=\"color:red;\">{$rec->title}</span>";
        
        // Отваря следващия период. Създава го, ако не съществува
        $nextRec = $this->forcePeriod(dt::addDays(1, $rec->end));
        
        $activeRec = $this->forceActive();
        
        $res .= "<br>Активен е период |* <span style=\"color:red;\">{$activeRec->title}</span>";
        
        $res = new Redirect(array('acc_Periods'), tr($res));
        
        return $res;
    }


    /**
     * Инициализира начални счетоводни периоди при инсталиране
     * Ако няма дефинирани периоди дефинира период, чийто край е последния ден от предходния 
     * месец със state='closed' и период, който е за текущия месец и е със state='active'
     */
    function loadSetupData()
    {
		// Форсира създаването на периоди от текущия месец до ACC_FIRST_PERIOD_START
    	$this->forcePeriod(dt::verbal2mysql());

        $this->updateExistingPeriodsState();
        
        $Cron = cls::get('core_Cron');
        
        $rec = new stdClass();
        $rec->systemId = "Create Periods";
        $rec->description = "Създава нови счетоводни периоди";
        $rec->controller = "acc_Periods";
        $rec->action = "createFuturePeriods";
        $rec->period = 1440;
        $rec->offset = 60;
        
        $Cron->addOnce($rec);

        return $this->actLog;
    }

    
    /**
     * Обновява състоянията на съществуващите чернови периоди
     */
    function updateExistingPeriodsState()
    {
    	$curPerEnd = static::getPeriodEnd();
    	$activeRec = $this->forceActive();
    	
    	$query = $this->getQuery();
    	$query->where("#end > '{$activeRec->end}'");
    	$query->where("#end <= '{$curPerEnd}'");
    	
    	while($rec = $query->fetch()){
	        $rec->state = 'pending';
	        $this->save($rec);
    	}
    }
    
    
    // Създава бъдещи (3 месеца напред) счетоводни периоди
    function cron_CreateFuturePeriods()
    {
        $this->forcePeriod(dt::getLastDayOfMonth(NULL, 3));
        $this->updateExistingPeriodsState();
    }
    
 	
    /**
     * Връща първичния ключ (id) на базовата валута към определена дата
     * Ако не е зададе
     * @param string $date Ако е NULL - текущата дата
     * @return int key(mvc=currency_Currencies)
     */
    public static function getBaseCurrencyId($date = NULL)
    {
        $periodRec = static::fetchByDate($date);
        
        if(!($baseCurrencyId = $periodRec->baseCurrencyId)) {
        	$conf = core_Packs::getConfig('acc');
        	$baseCurrencyId = currency_Currencies::getIdByCode($conf->BASE_CURRENCY_CODE);
        }
        
        return $baseCurrencyId;
    }
   
 
    /**
     * Връща кода на базовата валута към определена дата
     * 
     * @param string $date Ако е NULL - текущата дата
     * @return string трибуквен ISO код на валута
     */
    public static function getBaseCurrencyCode($date = NULL)
    {
        return currency_Currencies::getCodeById(static::getBaseCurrencyId($date));
    }
    
    
    /**
     * Връща края на даден период
     * @param date $date - дата от период, NULL  ако е текущия
     * @return date - крайната дата на периода (ако съществува)
     */
    public static function getPeriodEnd($date = NULL)
    {
    	return acc_Periods::fetchByDate($date)->end;
    }
}
