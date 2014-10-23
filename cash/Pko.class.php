<?php



/**
 * Документ за Приходни касови ордери
 *
 *
 * @category  bgerp
 * @package   cash
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cash_Pko extends core_Master
{
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=cash_transaction_Pko, sales_PaymentIntf, bgerp_DealIntf, email_DocumentIntf, doc_ContragentDataIntf';
   
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Приходни касови ордери";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    var $loadList = 'plg_RowTools, cash_Wrapper, plg_Sorting, acc_plg_Contable,
                     doc_DocumentPlg, plg_Printing, doc_SequencerPlg,acc_plg_DocumentSummary,
                     plg_Search,doc_plg_MultiPrint, bgerp_plg_Blank,
                     bgerp_DealIntf, doc_EmailCreatePlg, cond_plg_DefaultValues';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "tools=Пулт, number, valior, reason, folderId, currencyId=Валута, amount, state, createdOn, createdBy";
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo, cash';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo, cash';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'reason';
    
    
    /**
     * Заглавие на единичен документ
     */
    var $singleTitle = 'Приходен касов ордер';
    
    
    /**
     * Икона на единичния изглед
     */
    var $singleIcon = 'img/16/money_add.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Pko";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'cash, ceo';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'cash, ceo';
    
    
    /**
     * Кой може да го контира?
     */
    var $canConto = 'cash, ceo';
    
    
    /**
     * Кой може да го оттегля
     */
    var $canRevert = 'cash, ceo';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'cash/tpl/Pko.shtml';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'number, valior, contragentName, reason, id';
    
    
    /**
     * Параметри за принтиране
     */
    var $printParams = array( array('Оригинал'), array('Копие')); 

    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "4.1|Финанси";
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
    	'depositor'      => 'lastDocUser|lastDoc',
    );
    
    
    /**
     * Основна сч. сметка
     */
    public static $baseAccountSysId = '501';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('operationSysId', 'varchar', 'caption=Операция,mandatory');
    	
    	// Платена сума във валута, определена от полето `currencyId`
    	$this->FLD('amount', 'double(decimals=2,max=2000000000,min=0)', 'caption=Сума,mandatory,summary=amount');
    	
    	$this->FLD('reason', 'richtext(rows=2)', 'caption=Основание,mandatory');
    	$this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,mandatory');
    	$this->FLD('number', 'int', 'caption=Номер');
    	$this->FLD('peroCase', 'key(mvc=cash_Cases, select=name)', 'caption=Каса');
    	$this->FLD('contragentName', 'varchar(255)', 'caption=Контрагент->Вносител,mandatory');
    	$this->FLD('contragentId', 'int', 'input=hidden,notNull');
    	$this->FLD('contragentClassId', 'key(mvc=core_Classes,select=name)', 'input=hidden,notNull');
    	$this->FLD('contragentAdress', 'varchar(255)', 'input=hidden');
        $this->FLD('contragentPlace', 'varchar(255)', 'input=hidden');
        $this->FLD('contragentPcode', 'varchar(255)', 'input=hidden');
        $this->FLD('contragentCountry', 'varchar(255)', 'input=hidden');
    	$this->FLD('depositor', 'varchar(255)', 'caption=Контрагент->Броил,mandatory');
    	$this->FLD('creditAccount', 'customKey(mvc=acc_Accounts,key=systemId,select=systemId)', 'input=none');
    	$this->FLD('debitAccount', 'customKey(mvc=acc_Accounts,key=systemId,select=systemId)', 'input=none');
    	$this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута->Код');
    	$this->FLD('rate', 'double(smartRound,decimals=2)', 'caption=Валута->Курс');
    	$this->FLD('notes', 'richtext(bucket=Notes,rows=6)', 'caption=Допълнително->Бележки');
    	$this->FLD('state', 
            'enum(draft=Чернова, active=Контиран, rejected=Сторнирана, closed=Контиран)', 
            'caption=Статус, input=none'
        );
    	$this->FLD('isReverse', 'enum(no,yes)', 'input=none,notNull,value=no');
    	
        // Поставяне на уникален индекс
    	$this->setDbUnique('number');
    }
	
	
	/**
	 *  Подготовка на филтър формата
	 */
	static function on_AfterPrepareListFilter($mvc, $data)
	{
		// Добавяме към формата за търсене търсене по Каса
		cash_Cases::prepareCaseFilter($data, array('peroCase'));
	}
	
	
    /**
     *  Обработка на формата за редакция и добавяне
     */
    public static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
    	$folderId = $data->form->rec->folderId;
    	$form = &$data->form;
    	
    	$contragentId = doc_Folders::fetchCoverId($folderId);
        $contragentClassId = doc_Folders::fetchField($folderId, 'coverClass');
    	$form->setDefault('contragentId', $contragentId);
        $form->setDefault('contragentClassId', $contragentClassId);
    	
        expect($origin = $mvc->getOrigin($form->rec));
        expect($origin->haveInterface('bgerp_DealAggregatorIntf'));
        $dealInfo = $origin->getAggregateDealInfo();
        $pOperations = $dealInfo->get('allowedPaymentOperations');
        
        $options = self::getOperations($pOperations);
        expect(count($options));
        
        // Използваме помощната функция за намиране името на контрагента
    	
    	$form->setDefault('reason', "Към документ #{$origin->getHandle()}");
    	if($dealInfo->get('dealType') != findeals_Deals::AGGREGATOR_TYPE){
    		 		
    		$amount = ($dealInfo->get('amount') - $dealInfo->get('amountPaid')) / $dealInfo->get('rate');
    		if($amount <= 0) {
    		 	$amount = 0;
    	}
    		 		 
    	$defaultOperation = $dealInfo->get('defaultCaseOperation');
    	if($defaultOperation == 'customer2caseAdvance'){
    		 	$amount = ($dealInfo->get('agreedDownpayment') - $dealInfo->get('downpayment')) / $dealInfo->get('rate');
    		 }
    	}
    		 	
	    if($caseId = $dealInfo->get('caseId')){
	    		 	
	    	// Ако потребителя има права, логва се тихо
	    	cash_Cases::selectSilent($caseId);
	    }
    		 	
	    $cId = $dealInfo->get('currency');
	    $form->setDefault('currencyId', currency_Currencies::getIdByCode($cId));
	    $form->setDefault('rate', $dealInfo->get('rate'));
    		 		
    	if($dealInfo->get('dealType') == sales_Sales::AGGREGATOR_TYPE){
    		 $form->setDefault('amount', currency_Currencies::round($amount, $dealInfo->get('currency')));
    	}
    	
    	// Поставяме стойности по подразбиране
    	$form->setDefault('valior', dt::today());
        
        if($contragentClassId == crm_Companies::getClassId()){
    		$form->setSuggestions('depositor', crm_Companies::getPersonOptions($contragentId, FALSE));
    	}
        
    	$form->setOptions('operationSysId', $options);
    	if(isset($defaultOperation) && array_key_exists($defaultOperation, $options)){
    		$form->setDefault('operationSysId', $defaultOperation);	
        }
        
    	$form->setDefault('peroCase', cash_Cases::getCurrent());
    	$cData = cls::get($contragentClassId)->getContragentData($contragentId);
    	$form->setReadOnly('contragentName', ($cData->person) ? $cData->person : $cData->company);
    	
    	$form->addAttr('currencyId', array('onchange' => "document.forms['{$data->form->formAttr['id']}'].elements['rate'].value ='';"));
    }

    
    /**
     * Връща платежните операции
     */
    private static function getOperations($operations)
    {
    	$options = array();
    	
    	// Оставяме само тези операции в коитос е дебитира основната сметка на документа
    	foreach ($operations as $sysId => $op){
    		if($op['debit'] == static::$baseAccountSysId){
    			$options[$sysId] = $op['title'];
    		}
    	}
    	
    	return $options;
    }
    
    
    /**
     * Проверка и валидиране на формата
     */
    function on_AfterInputEditForm($mvc, $form)
    {
    	if ($form->isSubmitted()){
    		
    		$rec = &$form->rec;
	    	
    		$origin = $mvc->getOrigin($form->rec);
    		$dealInfo = $origin->getAggregateDealInfo();
    		
    		$operation = $dealInfo->allowedPaymentOperations[$rec->operationSysId];
    		$debitAcc = empty($operation['reverse']) ? $operation['debit'] : $operation['credit'];
    		$creditAcc = empty($operation['reverse']) ? $operation['credit'] : $operation['debit'];
    		$rec->debitAccount = $debitAcc;
    		$rec->creditAccount = $creditAcc;
    		$rec->isReverse = empty($operation['reverse']) ? 'no' : 'yes';
    		
    		$contragentData = doc_Folders::getContragentData($rec->folderId);
	    	$rec->contragentCountry = $contragentData->country;
	    	$rec->contragentPcode = $contragentData->pCode;
	    	$rec->contragentPlace = $contragentData->place;
	    	$rec->contragentAdress = $contragentData->address;
	    	$currencyCode = currency_Currencies::getCodeById($rec->currencyId);
	    	
		    if(!$rec->rate){
		    	// Изчисляваме курса към основната валута ако не е дефиниран
		    	$rec->rate = round(currency_CurrencyRates::getRate($rec->valior, $currencyCode, NULL), 4);
		    } else {
		    	if($msg = currency_CurrencyRates::hasDeviation($rec->rate, $rec->valior, $currencyCode, NULL)){
		    		$form->setWarning('rate', $msg);
		    	}
		    }
	    }
    	
	    acc_Periods::checkDocumentDate($form, 'valior');
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->number = static::getHandle($rec->id);
    	if($fields['-list']){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    	}	
    	
    	if($fields['-single']){
    		
    		$contragent = new core_ObjectReference($rec->contragentClassId, $rec->contragentId);
    		$row->contragentAddress = $contragent->getFullAdress();
    		
            if($rec->rate != 1) {
		   		$rec->equals = round($rec->amount * $rec->rate, 2);
		   		$row->equals = $mvc->getFieldType('amount')->toVerbal($rec->equals);
		   		$row->baseCurrency = acc_Periods::getBaseCurrencyCode($rec->valior);
		    } 
		    
            if(!$rec->equals) {
	    		
	    		// Ако валутата на документа съвпада с тази на периода не се показва курса
	    		unset($row->rate);
	    		unset($row->baseCurrency);
	    	} 
           
	    	$spellNumber = cls::get('core_SpellNumber');
		    $amountVerbal = $spellNumber->asCurrency($rec->amount, 'bg', FALSE);
		    $row->amountVerbal = $amountVerbal;
		    	
    		// Вземаме данните за нашата фирма
        	$ownCompanyData = crm_Companies::fetchOwnCompany();
        	$Companies = cls::get('crm_Companies');
        	$row->organisation = cls::get('type_Varchar')->toVerbal($ownCompanyData->company);
        	$row->organisationAddress = $Companies->getFullAdress($ownCompanyData->companyId);
            
    		// Извличаме имената на създателя на документа (касиера)
    		$cashierRec = core_Users::fetch($rec->createdBy);
    		$cashierRow = core_Users::recToVerbal($cashierRec);
	    	$row->cashier = $cashierRow->names;
	    }
       
        // Показваме заглавието само ако не сме в режим принтиране
    	if(!Mode::is('printing')){
    		$row->header = $mvc->singleTitle . "&nbsp;&nbsp;<b>{$row->ident}</b>" . " ({$row->state})" ;
    	}
    }
    
    
    /**
     * Вкарваме css файл за единичния изглед
     */
	static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
    	$tpl->push('cash/tpl/styles.css', 'CSS');
    }
    
    
   	/*
     * Реализация на интерфейса doc_DocumentIntf
     */
    
    
 	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = $this->singleTitle . " №{$id}";
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
		$row->recTitle = $rec->reason;
		
        return $row;
    }
    
    
	/**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        return FALSE;
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената нишка
     * 
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
	public static function canAddToThread($threadId)
    {
    	$firstDoc = doc_Threads::getFirstDocument($threadId);
    	$docState = $firstDoc->fetchField('state');
    	
    	if(($firstDoc->haveInterface('bgerp_DealAggregatorIntf') && $docState == 'active')){
			
    		// Ако няма позволени операции за документа не може да се създава
    		$operations = $firstDoc->getPaymentOperations();
    		$options = self::getOperations($operations);
    		
    		return count($options) ? TRUE : FALSE;
    	}
		
    	return FALSE;
    }
    
    
   	/*
     * Реализация на интерфейса sales_PaymentIntf
     */
    
    
    /**
     * Информация за платежен документ
     * 
     * @param int|stdClass $id ключ (int) или запис (stdClass) на модел 
     * @return stdClass Обект със следните полета:
     *
     *   o amount       - обща сума на платежния документ във валутата, зададена от `currencyCode`
     *   o currencyCode - key(mvc=currency_Currencies, key=code): ISO код на валутата
     *   o currencyRate - double - валутен курс към основната (към датата на док.) валута 
     *   o valior       - date - вальор на документа
     */
    public static function getPaymentInfo($id)
    {
        $rec = self::fetchRec($id);
        
        return (object)array(
            'amount' 	   => $rec->amount,
            'currencyCode' => currency_Currencies::getCodeById($rec->currencyId),
        	'currencyRate' => $rec->rate,
            'valior'       => $rec->valior,
        );
    }


    /**
     * Имплементация на @link bgerp_DealIntf::getDealInfo()
     *
     * @param int|object $id
     * @return bgerp_iface_DealAggregator
     * @see bgerp_DealIntf::getDealInfo()
     */
    public function pushDealInfo($id, &$aggregator)
    {
        $rec = self::fetchRec($id);
    	$aggregator->setIfNot('caseId', $rec->peroCase);
    }
    
    
	/**
     * В кои корици може да се вкарва документа
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getAllowedFolders()
    {
    	return array('doc_ContragentDataIntf');
    }
    
    
	/**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото на имейл по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
        $handle = static::getHandle($id);
        $tpl = new ET(tr("Моля запознайте се с нашия приходен касов ордер") . ': #[#handle#]');
        $tpl->append($handle, 'handle');
        return $tpl->getContent();
    }
    
    
	/**
     * Извиква се след изчисляването на необходимите роли за това действие
     */
    function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
        // Ако резултата е 'no_one' пропускане
    	if($res == 'no_one') return;
    }
    
    
    /**
     * Подготовка на бутоните на формата за добавяне/редактиране
     */
    function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
    	// Документа не може да се създава  в нова нишка, ако е възоснова на друг
    	if(!empty($data->form->toolbar->buttons['btnNewThread'])){
    		$data->form->toolbar->removeBtn('btnNewThread');
    	}
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(!empty($data->toolbar->buttons['btnAdd'])){
    		$data->toolbar->removeBtn('btnAdd');
    	}
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
    	$self = cls::get(__CLASS__);
    	
    	return $self->singleTitle . " №$rec->id";
    }
}
