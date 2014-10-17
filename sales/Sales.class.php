<?php
/**
 * Клас 'sales_Sales'
 *
 * Мениджър на документи за продажба на продукти от каталога
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Stefan Stefanov <stefan.bg@gmail.com> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_Sales extends deals_DealMaster
{
	const AGGREGATOR_TYPE = 'sale';
    
    /**
     * Заглавие
     */
    public $title = 'Продажби';


    /**
     * Абревиатура
     */
    public $abbr = 'Sal';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf,
                          acc_TransactionSourceIntf=sales_transaction_Sale,
                          bgerp_DealIntf, bgerp_DealAggregatorIntf, deals_DealsAccRegIntf, acc_RegisterIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, sales_Wrapper, plg_Sorting, doc_plg_MultiPrint, doc_plg_TplManager, doc_DocumentPlg, acc_plg_Contable, plg_Printing,
                    acc_plg_DocumentSummary, plg_Search, plg_ExportCsv, doc_plg_HidePrices, cond_plg_DefaultValues,
					doc_EmailCreatePlg, bgerp_plg_Blank, doc_plg_BusinessDoc, plg_Clone, doc_SharablePlg';
    
    
    /**
     * Активен таб на менюто
     */
    public $menuPage = 'Търговия:Продажби';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,sales';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,sales';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,sales';
    
    
    /**
     * Кой може да принтира фискална бележка
     */
    public $canPrintfiscreceipt = 'ceo,sales';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,sales';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,sales';
    

    /**
     * Кой може да го активира?
     */
    public $canConto = 'ceo,sales,acc';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, valior, folderId, currencyId=Валута, amountDeal, amountDelivered, amountPaid, amountInvoiced,
                             dealerId, initiatorId,paymentState,
                             createdOn, createdBy, modifiedOn, modifiedBy';


    /**
     * Детайла, на модела
     */
    public $details = 'sales_SalesDetails';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Продажба';
   
    
    /**
     * Групиране на документите
     */ 
    public $newBtnGroup = "3.1|Търговия";
   
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'amountDeal,amountDelivered,amountPaid,amountInvoiced,amountToPay,amountToDeliver,amountToInvoice';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    public $singleLayoutFile = 'sales/tpl/SingleLayoutSale.shtml';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/cart_go.png';

    
    /**
     * Поле в което се замества шаблона от doc_TplManager
     */
    public $templateFld = 'SINGLE_CONTENT';
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
    
    	'deliveryTermId'     => 'clientCondition|lastDocUser|lastDoc',
    	'paymentMethodId'    => 'clientCondition|lastDocUser|lastDoc',
    	'currencyId'         => 'lastDocUser|lastDoc|CoverMethod',
    	'bankAccountId'      => 'lastDocUser|lastDoc',
    	'makeInvoice'        => 'lastDocUser|lastDoc',
    	'deliveryLocationId' => 'lastDocUser|lastDoc',
    	'chargeVat'			 => 'lastDocUser|lastDoc',
    	'template' 			 => 'lastDocUser|lastDoc|LastDocSameCuntry',
    );
    
    
    /**
     * В коя група по дефолт да влизат контрагентите, към които е направен документа
     */
    public $crmDefGroup = 'customers';
    
    
    /**
     * Кое поле показва сумата на сделката
     */
    public $canClosewith = 'ceo,salesMaster';
    
    
    /**
     * Позволени операции на последващите платежни документи
     */
    public $allowedPaymentOperations = array(
    		'customer2caseAdvance' => array('title' => 'Авансово плащане от Клиент', 'debit' => '501', 'credit' => '412'),
    		'customer2bankAdvance' => array('title' => 'Авансово плащане от Клиент', 'debit' => '503', 'credit' => '412'),
    		'customer2case'        => array('title' => 'Плащане от Клиент', 'debit' => '501', 'credit' => '411'),
    		'customer2bank'        => array('title' => 'Плащане от Клиент', 'debit' => '503', 'credit' => '411'),
    		'case2customer'        => array('title' => 'Връщане към Клиент', 'debit' => '411', 'credit' => '501', 'reverse' => TRUE),
    		'bank2customer'        => array('title' => 'Връщане към Клиент', 'debit' => '411', 'credit' => '503', 'reverse' => TRUE),
    		'caseAdvance2customer' => array('title' => 'Върнат аванс на Клиент', 'debit' => '412', 'credit' => '501', 'reverse' => TRUE),
    		'bankAdvance2customer' => array('title' => 'Върнат аванс на Клиент', 'debit' => '412', 'credit' => '503', 'reverse' => TRUE),
    		'debitDeals'           => array('title' => 'Прихващане на вземания', 'debit' => '*', 'credit' => '411'),
    		'creditDeals'          => array('title' => 'Прихващане на задължение', 'debit' => '411', 'credit' => '*', 'reverse' => TRUE), 
    		);

    
    /**
     * Позволени операции за посследващите складови документи/протоколи
     */
    public $allowedShipmentOperations = array('delivery'        => array('title' => 'Експедиране на стока', 'debit' => '411', 'credit' => 'store'),
    										  'deliveryService' => array('title' => 'Доставка на услуги', 'debit' => '411', 'credit' => 'service'),
    						                  'buyServices'     => array('title' => 'Връщане на услуги', 'debit' => 'service', 'credit' => '411', 'reverse' => TRUE),
    										  'stowage'         => array('title' => 'Връщане на стока', 'debit' => 'store', 'credit' => '411', 'reverse' => TRUE),
    );
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'deliveryTermId, deliveryLocationId, shipmentStoreId, paymentMethodId, currencyId, bankAccountId, caseId, initiatorId, dealerId, folderId, id';
    
    
    /**
     * Как се казва приключващия документ
     */
    public $closeDealDoc = 'sales_ClosedDeals';
    
    
    /**
     * Главен детайл на модела
     */
    public $mainDetail = 'sales_SalesDetails';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        parent::setDealFields($this);
        $this->FLD('bankAccountId', 'key(mvc=bank_Accounts,select=iban,allowEmpty)', 'caption=Плащане->Банкова с-ка,after=currencyRate');
        $this->FLD('pricesAtDate', 'date', 'caption=Допълнително->Цени към,after=makeInvoice');
    }
    
    
    /**
     * Преди запис на документ
     */
    public static function on_BeforeSave($mvc, $res, $rec)
    {
		// Ако има б. сметка се нотифицират операторите и
    	if($rec->bankAccountId){
    		$operators = bank_OwnAccounts::fetchField("#bankAccountId = '{$rec->bankAccountId}'",'operators');
    		$rec->sharedUsers = keylist::merge($rec->sharedUsers, $operators);
    	}
    }
    
    
    /**
     * Определяне на документа-източник (пораждащия документ)
     */
    public function getOrigin_($rec)
    {
        $rec = static::fetchRec($rec);
        
        if (!empty($rec->originId)) {
            $origin = doc_Containers::getDocument($rec->originId);
        } else {
            $origin = FALSE;
        }
        
        return $origin;
    }


    /**
     * След създаване на запис в модела
     */
    public static function on_AfterCreate($mvc, $rec)
    {
        if (!$origin = $mvc->getOrigin($rec)) {
            return;
        }
    
        // Ако новосъздадения документ има origin, който поддържа bgerp_DealIntf,
        // използваме го за автоматично попълване на детайлите на продажбата
    
        if ($origin->haveInterface('bgerp_DealIntf')) {
            /* @var $dealInfo bgerp_iface_DealResponse */
            $dealInfo = $origin->getDealInfo();
            
            $quoted = $dealInfo->quoted;
            
            /* @var $product bgerp_iface_DealProduct */
            foreach ($quoted->products as $product) {
                $product = (object)$product;

                if ($product->quantity <= 0) {
                    continue;
                }
        
                $saleProduct = new stdClass();
        		$ProductMan = cls::get($product->classId);
                
                $saleProduct->saleId      = $rec->id;
                $saleProduct->classId     = $ProductMan->getClassId();
                $saleProduct->productId   = $product->productId;
                $saleProduct->packagingId = $product->packagingId;
                $saleProduct->quantity    = $product->quantity;
                $saleProduct->discount    = $product->discount;
                $saleProduct->price       = $product->price;
                $saleProduct->uomId       = $product->uomId;
        
                $productInfo = $ProductMan->getProductInfo($saleProduct->productId, $saleProduct->packagingId);
                $saleProduct->quantityInPack = ($saleProduct->packagingId) ? $productInfo->packagingRec->quantity : 1;
                
                sales_SalesDetails::save($saleProduct);
            }
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param sales_Sales $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        
    	// Задаване на стойности на полетата на формата по подразбиране
        self::setDefaultsFromOrigin($mvc, $form);
        
        $myCompany = crm_Companies::fetchOwnCompany();
        
        $form->setOptions('bankAccountId',  bank_Accounts::getContragentIbans($myCompany->companyId, 'crm_Companies', TRUE));
        $form->setDefault('bankAccountId', bank_OwnAccounts::getCurrent('bankAccountId', FALSE));
       
        $form->setDefault('contragentClassId', doc_Folders::fetchCoverClassId($form->rec->folderId));
        $form->setDefault('contragentId', doc_Folders::fetchCoverId($form->rec->folderId));
        
        $conf = core_Packs::getConfig('sales');
        $maxMonths =  $conf->SALE_MAX_FUTURE_PRICE / type_Time::SECONDS_IN_MONTH;
		$minMonths =  $conf->SALE_MAX_PAST_PRICE / type_Time::SECONDS_IN_MONTH;
        
        $priceAtDateFld = $form->getFieldType('pricesAtDate');
        $priceAtDateFld->params['max'] = dt::addMonths($maxMonths);
        $priceAtDateFld->params['min'] = dt::addMonths(-$minMonths);
    }
    
    
    /**
     * Зареждане на стойности по подразбиране от документа-основание 
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    protected static function setDefaultsFromOrigin(core_Mvc $mvc, core_Form $form)
    {
        if (!($origin = $mvc->getOrigin($form->rec)) || !$origin->haveInterface('bgerp_DealIntf')) {
            // Не може да се използва `bgerp_DealIntf`
            return false;
        }
        
        /* @var $dealInfo bgerp_iface_DealResponse */
        $dealInfo = $origin->getDealInfo();
        $originRec = $origin->fetch();
        $aspect   = $dealInfo->quoted;
        
        $form->rec->note			   = $originRec->others;
        $form->rec->deliveryTermId     = $aspect->delivery->term;
        $form->rec->deliveryLocationId = $aspect->delivery->location;
        $form->rec->paymentMethodId    = $aspect->payment->method;
        $form->rec->bankAccountId      = $aspect->payment->bankAccountId;
        $form->rec->currencyId         = $aspect->currency;
        $form->rec->currencyRate       = $aspect->rate;
        $form->rec->chargeVat          = $aspect->vatType;
        $form->setReadOnly('chargeVat');
    }
    
    
	/**
     * След подготовка на тулбара на единичен изглед
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = &$data->rec;
    	
    	if($rec->state == 'active'){
    		$closeArr = array('sales_ClosedDeals', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE);
    		
    		if(sales_ClosedDeals::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
	    		$data->toolbar->addBtn('Приключване', $closeArr, "row=2,ef_icon=img/16/closeDeal.png,title=Приключване на продажбата");
	    	} else {
	    		
	    		// Ако разликата е над допустимата но потребителя има права 'sales', той вижда бутона но не може да го използва
	    		if(!sales_ClosedDeals::isSaleDiffAllowed($rec) && haveRole('sales')){
	    			$data->toolbar->addBtn('Приключване', $closeArr, "ef_icon=img/16/closeDeal.png,title=Приключване на продажбата,error=Нямате право да приключите продажба с разлика над допустимото");
	    		}
	    	}
    		
    		// Ако протокол може да се добавя към треда и не се експедира на момента
    		if (sales_Services::haveRightFor('add', (object)array('threadId' => $rec->threadId))) {
    			$serviceUrl =  array('sales_Services', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE);
	            $data->toolbar->addBtn('Пр. услуги', $serviceUrl, 'ef_icon = img/16/shipment.png,title=Продажба на услуги,order=9.22');
	        }
	        
	        // Ако ЕН може да се добавя към треда и не се експедира на момента
	    	if (store_ShipmentOrders::haveRightFor('add', (object)array('threadId' => $rec->threadId))) {
	    		$shipUrl = array('store_ShipmentOrders', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE);
	            $data->toolbar->addBtn('Експедиране', $shipUrl, 'ef_icon = img/16/shipment.png,title=Експедиране на артикулите от склада,order=9.21');
	        }
	        
    		if(sales_Proformas::haveRightFor('add')){
	    		$data->toolbar->addBtn("Проформа", array('sales_Proformas', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE), 'row=2,ef_icon=img/16/invoice.png,title=Създаване на проформа,order=9.9992');
		    }
	    	
	        if(sales_Invoices::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
	    		$data->toolbar->addBtn("Фактура", array('sales_Invoices', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/invoice.png,title=Създаване на фактура,order=9.9993');
		    }
		    
		    if(cash_Pko::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
		    	$data->toolbar->addBtn("ПКО", array('cash_Pko', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/money_add.png,title=Създаване на нов приходен касов ордер');
		    }
		    
    		if(bank_IncomeDocuments::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
		    	$data->toolbar->addBtn("ПБД", array('bank_IncomeDocuments', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/bank_add.png,title=Създаване на нов приходен банков документ');
		    }
		    
		    if(!Mode::is('printing') && !Mode::is('text', 'xhtml') && $mvc->haveRightFor('printFiscReceipt', $rec)){
		    	$data->toolbar->addBtn('КБ', array($mvc, 'printReceipt', $rec->id), NULL, 'ef_icon=img/16/cash-receipt.png,warning=Искате ли да издадете нова касова бележка ?,title=Издаване на касова бележка', array('class' => "actionBtn", 'target' => 'iframe_a'));
		    }
    	}
    }
    
    
    /**
     * Принтиране на касова бележка
     */
    public function act_PrintReceipt()
    {
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetchRec($id));
    	$this->requireRightFor('printFiscReceipt', $rec);
    	
    	$conf = core_Packs::getConfig('sales');
    	$Driver = cls::get($conf->SALE_FISC_PRINTER_DRIVER);
    	$driverData = $this->prepareFiscPrinterData($rec);
    	
    	return $Driver->createFile($driverData);
    }
    
    
    /**
     * Подготвя данните за фискалния принтер
     */
    private function prepareFiscPrinterData($rec)
    {
    	$dQuery = $this->sales_SalesDetails->getQuery();
    	$dQuery->where("#saleId = {$rec->id}");
    	
    	$data = (object)array('products' => array(), 'payments' => array());
    	while($dRec = $dQuery->fetch()){
    		$nRec = new stdClass();
    		$nRec->id = $dRec->productId;
    		$nRec->managerId = $dRec->classId;
    		$nRec->quantity = $dRec->packQuantity;
    		if($dRec->discount){
    			$nRec->discount = $dRec->discount;
    		}
    		$pInfo = cls::get($dRec->classId)->getProductInfo($dRec->productId);
    		$nRec->measure = ($dRec->packagingId) ? cat_Packagings::getTitleById($dRec->packagingId) : cat_UoM::getShortName($pInfo->productRec->measureId);
    		$nRec->vat = cls::get($dRec->classId)->getVat($dRec->productId, $rec->valior);
    		if($rec->chargeVat != 'yes' && $rec->chargeVat != 'separate'){
    			$nRec->vat = 0;
    		}
    		
    		$nRec->price = $dRec->packPrice;
    		$nRec->vatGroup = cls::get($dRec->classId)->getParam($dRec->productId, 'vatGroup');
    		$nRec->name = $pInfo->productRec->name;
    		
    		$data->products[] = $nRec;
    	}
    	
    	$nRec = new stdClass();
    	$nRec->type = 0;
    	$nRec->amount = round($rec->amountPaid, 2);
    	
    	$data->short = TRUE;
    	$data->hasVat = ($rec->chargeVat == 'yes' || $rec->chargeVat == 'separate') ? TRUE : FALSE;
    	$data->payments[] = $nRec;
    	$data->totalPaid = $nRec->amount;
    	
    	return $data;
    }
    
	
    /**
     * Имплементация на @link bgerp_DealIntf::getDealInfo()
     * 
     * @param int|object $id
     * @return bgerp_iface_DealAggregator
     * @see bgerp_DealIntf::getDealInfo()
     */
    public function pushDealInfo($id, &$result)
    {
        $rec = $this->fetchRec($id);
        $actions = type_Set::toArray($rec->contoActions);
        
        $result->setIfNot('dealType', self::AGGREGATOR_TYPE);
        
        // Извличаме продуктите на продажбата
        $dQuery = sales_SalesDetails::getQuery();
        $dQuery->where("#saleId = {$rec->id}");
        $detailRecs = $dQuery->fetchAll();
       
        $downPayment = NULL;
        if(cond_PaymentMethods::hasDownpayment($rec->paymentMethodId)){
        	// Колко е очакваното авансово плащане
        	$downPayment = cond_PaymentMethods::getDownpayment($rec->paymentMethodId, $rec->amountDeal);
		}
        
        // Кои са позволените операции за последващите платежни документи
        $result->set('allowedPaymentOperations', $this->getPaymentOperations($rec));
        $result->set('allowedShipmentOperations', $this->getShipmentOperations($rec));
        $result->set('involvedContragents', array((object)array('classId' => $rec->contragentClassId, 'id' => $rec->contragentId)));
        
        $result->set('amount', $rec->amountDeal);
        $result->setIfNot('currency', $rec->currencyId);
        $result->setIfNot('rate', $rec->currencyRate);
        $result->setIfNot('vatType', $rec->chargeVat);
        $result->setIfNot('agreedValior', $rec->valior);
        $result->setIfNot('deliveryLocation', $rec->deliveryLocationId);
        $result->setIfNot('deliveryTime', $rec->deliveryTime);
        $result->setIfNot('deliveryTerm', $rec->deliveryTermId);
        $result->setIfNot('storeId', $rec->shipmentStoreId);
        $result->setIfNot('paymentMethodId', $rec->paymentMethodId);
        $result->setIfNot('caseId', $rec->caseId);
        $result->setIfNot('bankAccountId', $rec->bankAccountId);
        
        sales_transaction_Sale::clearCache();
        $result->set('agreedDownpayment', $downPayment);
        $result->set('downpayment', sales_transaction_Sale::getDownpayment($rec->id));
        $result->set('amountPaid', sales_transaction_Sale::getPaidAmount($rec->id));
        $result->set('deliveryAmount', sales_transaction_Sale::getDeliveryAmount($rec->id));
        $result->set('blAmount', sales_transaction_Sale::getBlAmount($rec->id));
        
        // Спрямо очакваното авансово плащане ако има, кои са дефолт платежните операции
        $agreedDp = $result->get('agreedDownpayment');
        $actualDp = $result->get('downpayment');
        if($agreedDp && ($actualDp < $agreedDp)){
        	$result->set('defaultCaseOperation', 'customer2caseAdvance');
        	$result->set('defaultBankOperation', 'customer2bankAdvance');
        } else {
        	$result->set('defaultCaseOperation', 'customer2case');
        	$result->set('defaultBankOperation', 'customer2bank');
        }
        
        if (isset($actions['ship'])) {
            $result->setIfNot('shippedValior', $rec->valior);
        }
        
        foreach ($detailRecs as $dRec) {
            $p = new bgerp_iface_DealProduct();
            
            $p->classId           = $dRec->classId;
            $p->productId         = $dRec->productId;
            $p->packagingId       = $dRec->packagingId;
            $p->discount          = $dRec->discount;
            $p->quantity          = $dRec->quantity;
            $p->quantityDelivered = $dRec->quantityDelivered;
            $p->price             = $dRec->price;
            $p->uomId             = $dRec->uomId;
            
            $ProductMan = cls::get($p->classId);
            $p->weight  = $ProductMan->getWeight($p->productId, $p->packagingId);
            $p->volume  = $ProductMan->getVolume($p->productId, $p->packagingId);
            
            $result->push('products', $p);
            
            if (isset($actions['ship']) && !empty($dRec->packagingId)) {
            	$push = TRUE;
            	$index = $dRec->classId . "|" . $dRec->productId;
            	$shipped = $result->get('shippedPacks');
            	if($shipped && isset($shipped[$index])){
            		if($shipped[$index]->inPack < $dRec->quantityInPack){
            			$push = FALSE;
            		}
            	}
            	
            	if($push){
            		$arr = (object)array('packagingId' => $dRec->packagingId, 'inPack' => $dRec->quantityInPack);
            		$result->push('shippedPacks', $arr, $index);
            	}
            }
         }
         
         $result->set('contoActions', $actions);
         $result->set('shippedProducts', sales_transaction_Sale::getShippedProducts($rec->id));
    }
    
    
    /**
     * Кои са позволените платежни операции за тази сделка
     */
    public function getPaymentOperations($id)
    {
    	$rec = $this->fetchRec($id);
    	 
    	$allowedPaymentOperations = $this->allowedPaymentOperations;
    	 
    	if($rec->paymentMethodId){
    
    		// Ако има метод за плащане и той няма авансова част, махаме авансовите операции
    		if(!cond_PaymentMethods::hasDownpayment($rec->paymentMethodId)){
    			unset($allowedPaymentOperations['customer2caseAdvance'], 
    					$allowedPaymentOperations['customer2bankAdvance'], 
    					$allowedPaymentOperations['caseAdvance2customer'],
    					$allowedPaymentOperations['bankAdvance2customer']);
    		}
    	}
    	 
    	return $allowedPaymentOperations;
    }
    
    
    /**
     * Приключва всички приключени продажби
     */
    function cron_CloseOldSales()
    {
    	$conf = core_Packs::getConfig('sales');
    	$olderThan = $conf->SALE_CLOSE_OLDER_THAN;
    	$limit = $conf->SALE_CLOSE_OLDER_NUM;
    	$ClosedDeals = cls::get('sales_ClosedDeals');
    	
    	$this->closeOldDeals($olderThan, $ClosedDeals, $limit);
    }
    
    
    /**
     * Нагласяне на крон да приключва продажби и да проверява дали са просрочени
     */
    protected function setCron(&$res)
    {
    	// Крон метод за затваряне на остарели продажби
    	$rec = new stdClass();
        $rec->systemId = "Close sales";
        $rec->description = "Затваряне на приключените продажби";
        $rec->controller = "sales_Sales";
        $rec->action = "CloseOldSales";
        $rec->period = 180;
        $rec->offset = 0;
        $rec->delay = 0;
        $rec->timeLimit = 100;
        $res .= core_Cron::addOnce($rec);

        // Проверка по крон дали продажбата е просрочена
        $rec2 = new stdClass();
        $rec2->systemId = "IsSaleOverdue";
        $rec2->description = "Проверяване за просрочени продажби";
        $rec2->controller = "sales_Sales";
        $rec2->action = "CheckSalesPayments";
        $rec2->period = 60;
        $rec2->offset = 0;
        $rec2->delay = 0;
        $rec2->timeLimit = 100;
        $res .= core_Cron::addOnce($rec2);
    }
    
    
    /**
     * Зарежда шаблоните на продажбата в doc_TplManager
     */
    protected function setTemplates(&$res)
    {
    	$tplArr = array();
    	$tplArr[] = array('name' => 'Договор за продажба',    'content' => 'sales/tpl/sales/Sale.shtml', 'lang' => 'bg');
    	$tplArr[] = array('name' => 'Договор за изработка',   'content' => 'sales/tpl/sales/Manufacturing.shtml', 'lang' => 'bg');
    	$tplArr[] = array('name' => 'Договор за услуга',      'content' => 'sales/tpl/sales/Service.shtml', 'lang' => 'bg');
    	$tplArr[] = array('name' => 'Sales contract',         'content' => 'sales/tpl/sales/SaleEN.shtml', 'lang' => 'en');
    	$tplArr[] = array('name' => 'Manufacturing contract', 'content' => 'sales/tpl/sales/ManufacturingEN.shtml', 'lang' => 'en');
    	$tplArr[] = array('name' => 'Service contract',       'content' => 'sales/tpl/sales/ServiceEN.shtml', 'lang' => 'en');
        
        $res .= doc_TplManager::addOnce($this, $tplArr);
    }
    
    
	/**
     * Проверява дали продажбата е просрочена или платени
     */
    function cron_CheckSalesPayments()
    {
    	$conf = core_Packs::getConfig('sales');
    	$overdueDelay = $conf->SALE_OVERDUE_CHECK_DELAY;
    	
    	$this->checkPayments($overdueDelay);
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'printfiscreceipt' && isset($rec)){
    		
    		$actions = type_Set::toArray($rec->contoActions);
    		
    		if ($actions['ship'] && $actions['pay']) {
    			$conf = core_Packs::getConfig('sales');
    			
    			// Ако няма избран драйвер за принтер или той е деинсталиран никой не може да издава касова бележка
    			if($conf->SALE_FISC_PRINTER_DRIVER == '' || core_Classes::fetchField($conf->SALE_FISC_PRINTER_DRIVER, 'state') == 'closed'){
    				$res = 'no_one';
    			}
    		} else {
    			$res = 'no_one';
    		}
    	}
    	
    	if($action == 'closewith' && isset($rec)){
    		if(sales_SalesDetails::fetch("#saleId = {$rec->id}")){
    			$res = 'no_one';
    		}
    	}
    }
}