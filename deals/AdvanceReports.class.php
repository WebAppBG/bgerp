<?php
/**
 * Клас 'deals_AdvanceReports'
 *
 * Мениджър за Авансови отчети
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov<ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class deals_AdvanceReports extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Авансови отчети';


    /**
     * Абревиатура
     */
    public $abbr = 'Ar';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=deals_transaction_AdvanceReport, bgerp_DealIntf, email_DocumentIntf, doc_ContragentDataIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, deals_Wrapper, plg_Sorting, plg_Printing, acc_plg_Contable, 
                    doc_DocumentPlg, acc_plg_DocumentSummary, plg_Search,
					doc_EmailCreatePlg, bgerp_plg_Blank, doc_plg_HidePrices';

    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,deals';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,dealsMaster';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,deals';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,deals';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,deals';


    /**
     * Кой може да го види?
     */
    public $canViewprices = 'ceo,deals';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canConto = 'ceo,deals';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'number,valior,currencyId, total,folderId,createdOn,createdBy';

    
   /**
    * Основна сч. сметка
    */
    public static $baseAccountSysId = '422';
    
    
    /**
     * Икона на единичния обект
     */
    var $singleIcon = 'img/16/legend.png';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'deals_AdvanceReportDetails' ;
    

    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Авансов отчет';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'deals/tpl/SingleAdvanceReportLayout.shtml';

   
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.7|Финанси";
   
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'number';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'number';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'total';
    
    
    /**
     * Опашка от записи за записване в on_Shutdown
     */
    protected $updated = array();
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'valior,number,folderId';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('operationSysId', 'varchar', 'caption=Операция,input=hidden');
    	$this->FLD("valior", 'date()', 'caption=Дата, mandatory,width=6em');
    	$this->FLD("number", 'int', 'caption=Номер,width=6em');
    	$this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута->Код,width=6em');
    	$this->FLD('rate', 'double(smartRound,decimals=2)', 'caption=Валута->Курс,width=6em');
    	$this->FLD('total', 'double(decimals=2)', 'input=none,caption=Общо,notNull');
    	$this->FLD('creditAccount', 'customKey(mvc=acc_Accounts,key=systemId,select=systemId)', 'input=none');
    	$this->FLD('state', 'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)', 'caption=Статус, input=none');
    
    	$this->setDbUnique('number');
    }
    
    
    /**
     *  Обработка на формата за редакция и добавяне
     */
    static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
    	$data->form->setDefault('valior', dt::now());
    	
    	expect($origin = $mvc->getOrigin($data->form->rec));
    	expect($origin->haveInterface('bgerp_DealAggregatorIntf'));
    	$dealInfo = $origin->getAggregateDealInfo();
    	$options = self::getOperations($dealInfo->get('allowedPaymentOperations'));
    	expect(count($options));
    	
    	$data->form->dealInfo = $dealInfo;
    	$data->form->setDefault('operationSysId', 'debitDeals');
    	
    	$data->form->setDefault('currencyId', currency_Currencies::getIdByCode($dealInfo->get('currency')));
    	$data->form->setDefault('rate', $dealInfo->get('rate'));
    	
    	$data->form->addAttr('currencyId', array('onchange' => "document.forms['{$data->form->formAttr['id']}'].elements['rate'].value ='';"));
    }
    
    
    /**
     * Проверка и валидиране на формата
     */
    function on_AfterInputEditForm($mvc, $form)
    {
    	$rec = &$form->rec;
    	 
    	if ($form->isSubmitted()){
    		$operations = $form->dealInfo->get('allowedPaymentOperations');
    		$operation = $form->dealInfo->allowedPaymentOperations[$rec->operationSysId];
    		$rec->creditAccount = $operation['credit'];
    		
    		$currencyCode = currency_Currencies::getCodeById($rec->currencyId);
    		if(!$rec->rate){
    			$rec->rate = round(currency_CurrencyRates::getRate($rec->valior, $currencyCode, NULL), 4);
    		} else {
    			if($msg = currency_CurrencyRates::hasDeviation($rec->rate, $rec->valior, $currencyCode, NULL)){
    				$form->setWarning('rate', $msg);
    			}
    		}
    	}
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	// Ако след запис, няма номер, тогава номера му става ид-то на документа
    	if(!$rec->number){
    		$rec->number = $rec->id;
    		$mvc->save($rec);
    	}
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if($fields['-list']){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    	}
    		
    	$rec->total /= $rec->rate;
    	$row->total = $mvc->fields['total']->type->toVerbal($rec->total);
    	
    	if($fields['-single']){
    
    		// Показваме заглавието само ако не сме в режим принтиране
    		if(!Mode::is('printing')){
    			$row->header = $mvc->singleTitle . "&nbsp;&nbsp;<b>#{$mvc->abbr}{$row->id}</b>" . " ({$row->state})" ;
    		}
    		
    		if($rec->currencyId == acc_Periods::getBaseCurrencyId($rec->valior)){
    			unset($row->rate);
    		}
    	}
    }
    
    
    /**
     * След промяна в детайлите на обект от този клас
     */
    public static function on_AfterUpdateDetail(core_Manager $mvc, $id, core_Manager $detailMvc)
    {
    	// Запомняне кои документи трябва да се обновят
    	$mvc->updated[$id] = $id;
    }
    
    
    /**
     * Обновява информацията на документа
     * @param int $id - ид на документа
     */
    public function updateMaster($id)
    {
    	$rec = $this->fetchRec($id);
    	$rec->total = 0;
    	
    	$query = $this->deals_AdvanceReportDetails->getQuery();
    	$query->where("#reportId = '{$id}'");
    	while($dRec = $query->fetch()){
    		$rec->total += $dRec->amount * (1 + $dRec->vat);
    	}
    
    	$this->save($rec);
    }
    
    
    /**
     * След изпълнение на скрипта, обновява записите, които са за ъпдейт
     */
    public static function on_Shutdown($mvc)
    {
    	if(count($mvc->updated)){
    		foreach ($mvc->updated as $id) {
    			$mvc->updateMaster($id);
    		}
    	}
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public function canAddToFolder($folderId)
    {
    	return FALSE;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
    	$self = cls::get(__CLASS__);
    	 
    	return "{$self->singleTitle} №{$rec->id}";
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
    	$threadRec = doc_Threads::fetch($threadId);
    
    	$firstDoc = doc_Threads::getFirstDocument($threadId);
    	$docState = $firstDoc->fetchField('state');
    
    	if(($firstDoc->haveInterface('bgerp_DealAggregatorIntf') && $docState == 'active')){
    
    		$dealInfo = $firstDoc->getAggregateDealInfo();
    		
    		if($dealInfo->dealType != bgerp_iface_DealResponse::TYPE_DEAL) return FALSE;
    		
    		$options = self::getOperations($dealInfo->allowedPaymentOperations);
    			
    		return count($options) ? TRUE : FALSE;
    	}
    
    	return FALSE;
    }
    
    
    /**
     * Връща платежните операции
     */
    private static function getOperations($operations)
    {
    	$options = array();
    	
    	// Оставяме само тези операции в коитос е дебитира основната сметка на документа
    	foreach ($operations as $sysId => $op){
    		if($op['credit'] == static::$baseAccountSysId){
    			$options[$sysId] = $op['title'];
    		}
    	}
    	 
    	return $options;
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
    	$row->recTitle = $row->title;
    
    	return $row;
    }
    
    
    /**
     * Документа не може да се активира ако има детайл с количество 0
     */
    public static function on_AfterCanActivate($mvc, &$res, $rec)
    {
    	if(!$rec->total) $res = FALSE;
    }
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    static function on_AfterPrepareSingle($mvc, &$res, &$data)
    {
    	$ownCompanyData = crm_Companies::fetchOwnCompany();
    	$Companies = cls::get('crm_Companies');
    	$data->row->MyCompany = cls::get('type_Varchar')->toVerbal($ownCompanyData->company);
    	$data->row->MyAddress = $Companies->getFullAdress($ownCompanyData->companyId);
    }
    
    
    /**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото на имейл по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
    	$handle = static::getHandle($id);
    	$tpl = new ET(tr("Моля запознайте се с нашия авансов отчет") . ': #[#handle#]');
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
    	 
    	// Документа не може да се контира/оттегля/възстановява, ако ориджина му е в състояние 'closed'
    	if(($action == 'conto' || $action == 'reject' || $action == 'restore') && isset($rec)){
    		$origin = $mvc->getOrigin($rec);
    		$originState = $origin->fetchField('state');
    		if($originState === 'closed'){
    			$res = 'no_one';
    		}
    	}
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
    	 
    }
}
