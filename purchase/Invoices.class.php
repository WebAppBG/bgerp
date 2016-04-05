<?php



/**
 * Входящи фактури към покупки
 *
 *
 * @category  bgerp
 * @package   purchase
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class purchase_Invoices extends deals_InvoiceMaster
{
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf, acc_TransactionSourceIntf=purchase_transaction_Invoice, bgerp_DealIntf, deals_InvoiceSourceIntf';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Ini';
    
    
    /**
     * Заглавие
     */
    public $title = 'Входящи фактури';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Входяща фактура';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, purchase_Wrapper, doc_plg_TplManager, plg_Sorting, acc_plg_Contable, doc_DocumentPlg,
					doc_EmailCreatePlg, bgerp_plg_Blank, plg_Printing, cond_plg_DefaultValues,deals_plg_DpInvoice,
                    doc_plg_HidePrices, acc_plg_DocumentSummary, plg_Search';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, number, date, place, folderId, dealValue, vatAmount, type';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'purchase_InvoiceDetails';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,invoicer';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,invoicer';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,purchase';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,invoicer';
	
	
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,invoicer';
    
    
    /**
     * Кой може да го контира?
     */
    public $canConto = 'ceo,invoicer';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'number, folderId, id, contragentName';
    
    
    /**
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'purchase/tpl/SingleLayoutInvoice.shtml';
    
    
    /**
     * Икона за фактура
     */
    public $singleIcon = 'img/16/invoice.png';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "3.3|Търговия";
    
    
    /**
     * Кой е основния детайл
     */
    public $mainDetail = 'purchase_InvoiceDetails';
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
    		'place'               => 'lastDocUser|lastDoc',
    		'responsible'         => 'lastDocUser|lastDoc',
    		'contragentCountryId' => 'clientData|lastDocUser|lastDoc',
    		'contragentVatNo'     => 'clientData|lastDocUser|lastDoc',
    		'uicNo'     		  => 'clientData|lastDocUser|lastDoc',
    		'contragentPCode'     => 'clientData|lastDocUser|lastDoc',
    		'contragentPlace'     => 'clientData|lastDocUser|lastDoc',
    		'contragentAddress'   => 'clientData|lastDocUser|lastDoc',
    		'accountId'           => 'lastDocUser|lastDoc',
    		'template' 		      => 'lastDocUser|lastDoc|LastDocSameCuntry',
    );
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	parent::setInvoiceFields($this);
    	
    	$this->FLD('number', 'varchar', 'caption=Номер, export=Csv,hint=Номера с който идва фактурата,after=place');
    	$this->FLD('fileHnd', 'fileman_FileType(bucket=Documents)', 'caption=Документ,after=number');
    	
    	$this->FLD('accountId', 'key(mvc=bank_Accounts,select=iban, allowEmpty)', 'caption=Плащане->Банкова с-ка, export=Csv');
    	$this->FLD('state', 'enum(draft=Чернова, active=Контирана, rejected=Оттеглен)', 'caption=Статус, input=none,export=Csv');
    	$this->FLD('type', 'enum(invoice=Входяща фактура, credit_note=Входящо кредитно известие, debit_note=Входящо дебитно известие, dc_note=Известие)', 'caption=Вид, input=hidden');
    }
    
    
    /**
     * След подготовка на формата
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
		$rec = $data->form->rec;
    	$origin = $mvc->getOrigin($data->form->rec);
    	if($origin->isInstanceOf('findeals_AdvanceReports')){
    		$data->form->setOptions('vatRate', arr::make('separate=Отделно, exempt=Oсвободено, no=Без начисляване'));
    		$data->form->setField('vatRate', 'input');
    		$data->form->setDefault('vatRate', 'separate');
    		
    		if(isset($data->form->rec->id)){
    			if(purchase_InvoiceDetails::fetch("#invoiceId = {$data->form->rec->id}")){
    				$data->form->setReadOnly('vatRate');
    			}
    		}
    	}
    	
    	parent::prepareInvoiceForm($mvc, $data);
    	
    	if($data->aggregateInfo){
    		if($data->aggregateInfo->get('bankAccountId')){
    			$data->form->rec->accountId = $data->aggregateInfo->get('bankAccountId');
    		}
    	}
    	
    	$coverClass = doc_Folders::fetchCoverClassName($data->form->rec->folderId);
    	$coverId = doc_Folders::fetchCoverId($data->form->rec->folderId);
    	$data->form->setOptions('accountId', bank_Accounts::getContragentIbans($coverId, $coverClass, TRUE));
    	
    	if($data->form->rec->vatRate != 'yes' && $data->form->rec->vatRate != 'separate'){
    		$data->form->setField('vatReason', 'mandatory');
    	}
    	
    	$bgId = drdata_Countries::fetchField("#commonName = 'Bulgaria'", 'id');
    	if($rec->contragentCountryId == $bgId){
    		$data->form->setFieldType('number', core_Type::getByName('bigint(size=10)'));
    	}
    }
    
    
    /**
     * След изпращане на формата
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
    	parent::inputInvoiceForm($mvc, $form);
    	
    	if($form->isSubmitted()){
    		$rec = &$form->rec;
    		if(empty($rec->number)){
    			$rec->number = NULL;
    		}
    		
    		// изискваме за контрагент с този номер да няма фактура със този номер
    		foreach (array('contragentVatNo', 'uicNo') as $fld){
    			if(isset($rec->{$fld}) && !empty($rec->number)){
    				if($mvc->fetchField("#{$fld}='{$rec->{$fld}}' AND #number='{$rec->number}' AND #id != '{$rec->id}'")){
    					$form->setError("{$fld},number", 'Има вече входяща фактура с този номер, за този контрагент');
    				}
    			}
    		}
    	}
    }
    
    
    /**
     * Преди запис в модела
     */
    public static function on_BeforeSave($mvc, $id, $rec)
    {
    	parent::beforeInvoiceSave($rec);
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public static function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
    	$tpl->push('purchase/tpl/invoiceStyles.css', 'CSS');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	parent::getVerbalInvoice($mvc, $rec, $row, $fields);
    	
    	if($fields['-single']){
    		if($rec->accountId){
    			$Varchar = cls::get('type_Varchar');
    			$ownAcc = bank_Accounts::fetch($rec->accountId);
    			$row->bank = $Varchar->toVerbal($ownAcc->bank);
    			$row->bic = $Varchar->toVerbal($ownAcc->bic);
    		}
    	}
    }


    /*
     * Реализация на интерфейса doc_DocumentIntf
     */
    
    
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
     * Намира ориджина на фактурата (ако има)
     */
    public static function getOrigin($rec)
    {
    	$origin = NULL;
    	$rec = static::fetchRec($rec);
    	
    	if($rec->originId) {
    		return doc_Containers::getDocument($rec->originId);
    	}
    	
    	if($rec->threadId){
    		return doc_Threads::getFirstDocument($rec->threadId);
	    }
    	
    	return $origin;
    }
    
    
	/**
     * Извиква се след изчисляването на необходимите роли за това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
        // Ако резултата е 'no_one' пропускане
    	if($res == 'no_one') return;
	        
    	if($action == 'add' && isset($rec->threadId)){
    		 $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
    		 $docState = $firstDoc->fetchField('state');
    		 
    		 if(!(($firstDoc->isInstanceOf('purchase_Purchases') || $firstDoc->isInstanceOf('findeals_AdvanceDeals')) && $docState == 'active')){
    			$res = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    function loadSetupData()
    {
    	$tplArr = array();
    	$tplArr[] = array('name' => 'Входяща фактура нормален изглед', 'content' => 'purchase/tpl/InvoiceHeaderNormal.shtml', 'lang' => 'bg');
    	$tplArr[] = array('name' => 'Входяща фактура изглед за писмо', 'content' => 'purchase/tpl/InvoiceHeaderLetter.shtml', 'lang' => 'bg');
        
    	$res = '';
        $res .= doc_TplManager::addOnce($this, $tplArr);
        
        return $res;
    }
    
    
    /**
     *  Подготовка на филтър формата
     */
    public static function on_AfterPrepareListFilter($mvc, $data)
    {
    	if(!$data->listFilter->getField('invType', FALSE)){
    		$data->listFilter->FNC('invType', 'enum(all=Всички, invoice=Фактура, credit_note=Кредитно известие, debit_note=Дебитно известие)', 'caption=Вид,input,silent');
    	}
    	 
    	$data->listFilter->showFields .= ',invType';
    	 
    	$data->listFilter->input(NULL, 'silent');
    	 
    	if($rec = $data->listFilter->rec){
    		if($rec->invType){
    			if($rec->invType != 'all'){
   					$data->query->where("#type = '{$rec->invType}'");
   					
   					$sign = ($rec->invType == 'credit_note') ? "<=" : ">";
   					$data->query->orWhere("#type = 'dc_note' AND #dealValue {$sign} 0");
   				}
    		}
    	}
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = $data->rec;
    	
    	if($rec->state == 'active'){
    		$amount = $rec->dealValue + $rec->vatAmount;
    		$amount /= ($rec->displayRate) ? $rec->displayRate : $rec->rate;
    		$amount = round($amount, 2);
    
    		if($amount < 0){
    			if(cash_Pko::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
    				$data->toolbar->addBtn("ПКО", array('cash_Pko', 'add', 'originId' => $rec->containerId, 'amountDeal' => abs($amount), 'fromContainerId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/money_delete.png,title=Създаване на нов приходен касов ордер към документа');
    			}
    			
    			if(bank_IncomeDocuments::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
    				$data->toolbar->addBtn("ПБД", array('bank_IncomeDocuments', 'add', 'originId' => $rec->containerId, 'amountDeal' => abs($amount), 'fromContainerId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/bank_rem.png,title=Създаване на нов приходен банков документ');
    			}
    		} else {
    			if(cash_Rko::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
    				$data->toolbar->addBtn("РКО", array('cash_Rko', 'add', 'originId' => $rec->containerId, 'amountDeal' => $amount, 'fromContainerId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/money_delete.png,title=Създаване на нов разходен касов ордер към документа');
    			}
    			
    			if(bank_SpendingDocuments::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
    				$data->toolbar->addBtn("РБД", array('bank_SpendingDocuments', 'add', 'originId' => $rec->containerId, 'amountDeal' => $amount, 'fromContainerId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/bank_rem.png,title=Създаване на нов разходен банков документ');
    			}
    		}
    	}
    }
}