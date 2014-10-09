<?php



/**
 * Абстрактен клас за наследяване на складови документи
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class store_DocumentMaster extends core_Master
{
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'amountDelivered';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'storeId, locationId, deliveryTime, lineId, contragentClassId, contragentId, weight, volume, folderId, id';
    
    
    /**
     * Опашка от записи за записване в on_Shutdown
     */
    protected $updated = array();
    
    
    /**
     * След описанието на полетата
     */
    protected static function setDocFields(core_Master &$mvc)
    {
    	$mvc->FLD('valior', 'date', 'caption=Дата, mandatory,oldFieldName=date');
    	$mvc->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code,allowEmpty)', 'input=none,caption=Плащане->Валута');
    	$mvc->FLD('currencyRate', 'double(decimals=2)', 'caption=Валута->Курс,input=hidden');
    	$mvc->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=От склад, mandatory');
    	$mvc->FLD('chargeVat', 'enum(yes=Включено, separate=Отделно, exempt=Oсвободено, no=Без начисляване)', 'caption=ДДС,input=hidden');
    	
    	$mvc->FLD('amountDelivered', 'double(decimals=2)', 'caption=Доставено->Сума,input=none,summary=amount'); // Сумата на доставената стока
    	$mvc->FLD('amountDeliveredVat', 'double(decimals=2)', 'caption=Доставено->ДДС,input=none,summary=amount');
    	$mvc->FLD('amountDiscount', 'double(decimals=2)', 'input=none');
    	
    	// Контрагент
    	$mvc->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
    	$mvc->FLD('contragentId', 'int', 'input=hidden');
    	
    	// Доставка
    	$mvc->FLD('locationId', 'key(mvc=crm_Locations, select=title,allowEmpty)', 'caption=Обект до,silent');
    	$mvc->FLD('deliveryTime', 'datetime', 'caption=Срок до');
    	$mvc->FLD('lineId', 'key(mvc=trans_Lines,select=title,allowEmpty)', 'caption=Транспорт');
    	
    	// Допълнително
    	$mvc->FLD('weight', 'cat_type_Weight', 'input=none,caption=Тегло');
    	$mvc->FLD('volume', 'cat_type_Volume', 'input=none,caption=Обем');
    	
    	$mvc->FLD('note', 'richtext(bucket=Notes,rows=3)', 'caption=Допълнително->Бележки');
    	$mvc->FLD('state',
    			'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)',
    			'caption=Статус, input=none'
    	);
    	$mvc->FLD('isReverse', 'enum(no,yes)', 'input=none,notNull,value=no');
    	$mvc->FLD('accountId', 'customKey(mvc=acc_Accounts,key=systemId,select=id)','input=none,notNull,value=411');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec  = &$form->rec;
    
    	$form->setDefault('valior', dt::now());
    	$form->setDefault('storeId', store_Stores::getCurrent('id', FALSE));
    	$rec->contragentClassId = doc_Folders::fetchCoverClassId($rec->folderId);
    	$rec->contragentId = doc_Folders::fetchCoverId($rec->folderId);
    	if(!trans_Lines::count("#state = 'active'")){
    		$form->setField('lineId', 'input=none');
    	}
    
    	// Поле за избор на локация - само локациите на контрагента по продажбата
    	$form->getField('locationId')->type->options =
    	array('' => '') + crm_Locations::getContragentOptions($rec->contragentClassId, $rec->contragentId);
    
    	expect($origin = ($form->rec->originId) ? doc_Containers::getDocument($form->rec->originId) : doc_Threads::getFirstDocument($form->rec->threadId));
    	expect($origin->haveInterface('bgerp_DealAggregatorIntf'));
    	$dealInfo = $origin->getAggregateDealInfo();
    	$form->dealInfo = $dealInfo;
    	
    	$form->setDefault('currencyId', $dealInfo->get('currency'));
    	$form->setDefault('currencyRate', $dealInfo->get('rate'));
    	$form->setDefault('locationId', $dealInfo->get('deliveryLocation'));
    	$form->setDefault('deliveryTime', $dealInfo->get('deliveryTime'));
    	$form->setDefault('chargeVat', $dealInfo->get('vatType'));
    	$form->setDefault('storeId', $dealInfo->get('storeId'));
    }
    
    
    /**
     * След изпращане на формата
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
    	if ($form->isSubmitted()) {
    		$rec = &$form->rec;
			if($rec->lineId){
				
				// Ако има локация и тя е различна от договорената, слагаме предупреждение
    			if($rec->locationId && $rec->locationId != $form->dealInfo->get('deliveryLocation')){
    				$agreedLocation = crm_Locations::getTitleById($form->dealInfo->get('deliveryLocation'));
    				$form->setWarning('locationId', "Избраната локация е различна от договорената \"{$agreedLocation}\"");
    			}
				
    			// Ако има избрана линия и метод на плащане, линията трябва да има подочетно лице
    			if($pMethods = $form->dealInfo->get('paymentMethodId')){
    				if(cond_PaymentMethods::isCOD($pMethods) && !trans_Lines::hasForwarderPersonId($rec->lineId)){
    					$form->setError('lineId', 'При наложен платеж, избраната линия трябва да има материално отговорно лице!');
    				}
    			}
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
     * Обновява информацията на документа
     * @param int $id - ид на документа
     */
    protected function updateMaster($id)
    {
    	$rec = $this->fetchRec($id);
    	 
    	$Detail = $this->mainDetail;
    	$query = $this->$Detail->getQuery();
    	$query->where("#{$this->$Detail->masterKey} = '{$id}'");
    
    	$recs = $query->fetchAll();
    
    	deals_Helper::fillRecs($this, $recs, $rec);
    	$measures = $this->getMeasures($recs);
    	 
    	$rec->weight = $measures->weight;
    	$rec->volume = $measures->volume;
    
    	// ДДС-т е отделно amountDeal  е сумата без ддс + ддс-то, иначе самата сума си е с включено ддс
    	$amount = ($rec->chargeVat == 'separate') ? $this->_total->amount + $this->_total->vat : $this->_total->amount;
    	$amount -= $this->_total->discount;
    	$rec->amountDelivered = $amount * $rec->currencyRate;
    	$rec->amountDeliveredVat = $this->_total->vat * $rec->currencyRate;
    	$rec->amountDiscount = $this->_total->discount * $rec->currencyRate;
    
    	$this->save($rec);
    }
    

    /**
     * След създаване на запис в модела
     */
    public static function on_AfterCreate($mvc, $rec)
    {
    	$origin = $mvc::getOrigin($rec);
    
    	// Ако новосъздадения документ има origin, който поддържа bgerp_AggregateDealIntf,
    	// използваме го за автоматично попълване на детайлите на документа
    	if ($origin->haveInterface('bgerp_DealAggregatorIntf')) {
    
    		// Ако документа е обратен не слагаме продукти по дефолт
    		if($rec->isReverse == 'yes') return;
    
    		$aggregatedDealInfo = $origin->getAggregateDealInfo();
    		$agreedProducts = $aggregatedDealInfo->get('products');
    		$Detail = $mvc->mainDetail;
    		
    		if(count($agreedProducts)){
    			foreach ($agreedProducts as $product) {
    				$info = cls::get($product->classId)->getProductInfo($product->productId, $product->packagingId);
    				 
    				// Колко остава за експедиране от продукта
    				$toShip = $product->quantity - $product->quantityDelivered;
    				 
    				// Пропускат се експедираните и нескладируемите продукти
    				if (!isset($info->meta['canStore']) || ($toShip <= 0)) continue;
    				 
    				$shipProduct = new stdClass();
    				$shipProduct->{$mvc->$Detail->masterKey}  = $rec->id;
    				$shipProduct->classId     = $product->classId;
    				$shipProduct->productId   = $product->productId;
    				$shipProduct->packagingId = $product->packagingId;
    				$shipProduct->quantity    = $toShip;
    				$shipProduct->price       = $product->price;
    				$shipProduct->uomId       = $product->uomId;
    				$shipProduct->discount    = $product->discount;
    				$shipProduct->weight      = $product->weight;
    				$shipProduct->volume      = $product->volume;
    				$shipProduct->quantityInPack = ($product->packagingId) ? $info->packagingRec->quantity : 1;
    				 
    				$mvc->$Detail->save($shipProduct);
    			}
    		}
    	}
    }
    
    
    /**
     * Подготвя данните на хедъра на документа
     */
    private function prepareHeaderInfo(&$row, $rec)
    {
    	$ownCompanyData = crm_Companies::fetchOwnCompany();
    	$Companies = cls::get('crm_Companies');
    	$row->MyCompany = cls::get('type_Varchar')->toVerbal($ownCompanyData->company);
    	$row->MyAddress = $Companies->getFullAdress($ownCompanyData->companyId);
    
    	$uic = drdata_Vats::getUicByVatNo($ownCompanyData->vatNo);
    	if($uic != $ownCompanyData->vatNo){
    		$row->MyCompanyVatNo = $ownCompanyData->vatNo;
    	}
    	$row->uicId = $uic;
    	 
    	// Данните на клиента
    	$ContragentClass = cls::get($rec->contragentClassId);
    	$cData = $ContragentClass->getContragentData($rec->contragentId);
    	$row->contragentName = cls::get('type_Varchar')->toVerbal(($cData->person) ? $cData->person : $cData->company);
    	$row->contragentAddress = $ContragentClass->getFullAdress($rec->contragentId);
    	$row->vatNo = $cData->vatNo;
    }
    
    
    /**
     * След рендиране на сингъла
     */
   public static function on_AfterRenderSingle($mvc, $tpl, $data)
   {
    	if(Mode::is('printing') || Mode::is('text', 'xhtml')){
    		$tpl->removeBlock('header');
    	}
   }


   /**
    * Подготвя данните (в обекта $data) необходими за единичния изглед
    */
   public function prepareSingle_($data)
   {
	   	parent::prepareSingle_($data);
	   	 
	   	$rec = &$data->rec;
	   	if(empty($data->noTotal)){
	   		$data->summary = deals_Helper::prepareSummary($this->_total, $rec->valior, $rec->currencyRate, $rec->currencyId, $rec->chargeVat);
	   		$data->row = (object)((array)$data->row + (array)$data->summary);
	   	}
   }


   /**
    * След подготовка на единичния изглед
    */
   public static function on_AfterPrepareSingle($mvc, &$res, &$data)
   {
   		$data->row->header = $mvc->singleTitle . " #<b>{$mvc->abbr}{$data->row->id}</b> ({$data->row->state})";
   }


   /**
    * След преобразуване на записа в четим за хора вид
    */
   public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
   {
	   	@$amountDelivered = $rec->amountDelivered / $rec->currencyRate;
	   	$row->amountDelivered = $mvc->getFieldType('amountDelivered')->toVerbal($amountDelivered);
	   
	   	if(!$rec->weight) {
	   		$row->weight = "<span class='quiet'>0</span>";
	   	}
	   
	   	if(!$rec->volume) {
	   		$row->volume = "<span class='quiet'>0</span>";
	   	}
	   	 
	   	if(isset($fields['-list'])){
	   		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
	   		if($rec->amountDelivered){
    			$row->amountDelivered = "<span class='cCode' style='float:left'>{$rec->currencyId}</span> &nbsp;{$row->amountDelivered}";
    		} else {
    			$row->amountDelivered = "<span class='quiet'>0.00</span>";
    		}
	   	}
	   	 
	   	if(isset($fields['-single'])){
	   		$mvc->prepareHeaderInfo($row, $rec);
	   	}
   }

   
   /**
    * Документа не може да бъде начало на нишка; може да се създава само в съществуващи нишки
    */
    public static function canAddToFolder($folderId)
    {
   		return FALSE;
    }
    
    
    /**
     * Може ли документа да се добави в посочената нишка?
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
    public static function canAddToThread($threadId)
    {
        $firstDoc = doc_Threads::getFirstDocument($threadId);
    	$docState = $firstDoc->fetchField('state');
    
    	// Може да се добавя само към активиран документ
    	if($docState == 'active'){
    		
    		if($firstDoc->haveInterface('bgerp_DealAggregatorIntf')){
    			$operations = $firstDoc->getShipmentOperations();
    			
    			return (isset($operations[static::$defOperationSysId])) ? TRUE : FALSE;
    		}
    	}
    	
    	return FALSE;
    }


    /**
     * Връща масив от използваните нестандартни артикули в документа
     * 
     * @param int $id - ид на dokumenta
     * @return param $res - масив с използваните документи
     * 					['class'] - инстанция на документа
     * 					['id'] - ид на документа
     */
    public function getUsedDocs_($id)
    {
    	$res = array();
    	
    	$Detail = $this->mainDetail;
    	$dQuery = $this->$Detail->getQuery();
    	$dQuery->EXT('state', $this->className, "externalKey={$this->$Detail->masterKey}");
    	$dQuery->where("#{$this->$Detail->masterKey} = '{$id}'");
    	$dQuery->groupBy('productId,classId');
    	while($dRec = $dQuery->fetch()){
    		$productMan = cls::get($dRec->classId);
    		if(cls::haveInterface('doc_DocumentIntf', $productMan)){
    			$res[] = (object)array('class' => $productMan, 'id' => $dRec->productId);
    		}
    	}
    	
    	return $res;
    }
    
    
    /**
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow($id)
    {
    	expect($rec = $this->fetch($id));
    	$title = $this->getRecTitle($rec);
    
    	$row = (object)array(
    			'title'    => $title,
    			'authorId' => $rec->createdBy,
    			'author'   => $this->getVerbal($rec, 'createdBy'),
    			'state'    => $rec->state,
    			'recTitle' => $title
    	);
    
    	return $row;
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
     * Помощен метод за показване на документа в транспортните линии
     * @param stdClass $rec - запис на документа
     * @param stdClass $row - вербалния запис
     */
    private function prepareLineRows($rec)
    {
    	$row = new stdClass();
    	$fields = $this->selectFields();
    	$fields['-single'] = TRUE;
    	$oldRow = $this->recToVerbal($rec, $fields);
    	 
    	$amount = currency_Currencies::round($rec->amountDelivered / $rec->currencyRate, $rec->currencyId);
    	 
    	$row->weight = $oldRow->weight;
    	$row->volume = $oldRow->volume;
    	$row->collection = "<span class='cCode'>{$rec->currencyId}</span> " . $this->getFieldType('amountDelivered')->toVerbal($amount);
    	$row->rowNumb = $rec->rowNumb;
    	 
    	$row->address = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    	$row->address .= ", " . (($rec->locationId) ? crm_Locations::getAddress($rec->locationId) : $oldRow->contragentAddress);
    	trim($row->address, ', ');
    	 
    	$row->TR_CLASS = ($rec->rowNumb % 2 == 0) ? 'zebra0' : 'zebra1';
    	$row->docId = $this->getDocLink($rec->id);
    	 
    	return $row;
    }
    
    
    /**
     * Помощен метод за показване на документа в транспортните линии
     */
    protected function prepareLineDetail($masterRec)
    {
    	$arr = array();
    	$query = $this->getQuery();
    	$query->where("#lineId = {$masterRec->id}");
    	$query->where("#state = 'active'");
    	$query->orderBy("#createdOn", 'DESC');
    	 
    	$i = 1;
    	while($dRec = $query->fetch()){
    		$dRec->rowNumb = $i;
    		$arr[$dRec->id] = $this->prepareLineRows($dRec);
    		$i++;
    	}
    	
    	return $arr;
    }


    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    function loadSetupData()
    {
    	$res = '';
    	$this->setTemplates($res);
    	
    	return $res;
    }


    /**
     * Добавя ключови думи за пълнотекстово търсене, това са името на
     * документа или папката
     */
    public static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
    	// Тук ще генерираме всички ключови думи
    	$detailsKeywords = '';
    	$Detail = $mvc->mainDetail;
    	
    	// заявка към детайлите
    	$query = $mvc->$Detail->getQuery();
    	
    	// точно на тази фактура детайлите търсим
    	$query->where("#{$mvc->$Detail->masterKey} = '{$rec->id}'");
    
    	while ($recDetails = $query->fetch()){
    		// взимаме заглавията на продуктите
    		$productTitle = cls::get($recDetails->classId)->getTitleById($recDetails->productId);
    		
    		// и ги нормализираме
    		$detailsKeywords .= " " . plg_Search::normalizeText($productTitle);
    	}
    	 
    	// добавяме новите ключови думи към основните
    	$res = " " . $res . " " . $detailsKeywords;
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
    	$rec = $this->fetchRec($id);
    
    	// Конвертираме данъчната основа към валутата идваща от продажбата
    	$aggregator->setIfNot('deliveryLocation', $rec->locationId);
    	$aggregator->setIfNot('deliveryTime', $rec->deliveryTime);
    	$aggregator->setIfNot('storeId', $rec->storeId);
    	$aggregator->setIfNot('shippedValior', $rec->valior);
    
    	$Detail = $this->mainDetail;
    	$dQuery = $this->$Detail->getQuery();
    	$dQuery->where("#{$this->$Detail->masterKey} = {$rec->id}");
    
    	// Подаваме на интерфейса най-малката опаковка с която е експедиран продукта
    	while ($dRec = $dQuery->fetch()) {
    		if(empty($dRec->packagingId)) continue;
    		 
    		// Подаваме най-малката опаковка в която е експедиран продукта
    		$push = TRUE;
    		$index = $dRec->classId . "|" . $dRec->productId;
    		$shipped = $aggregator->get('shippedPacks');
    		if($shipped && isset($shipped[$index])){
    			if($shipped[$index]->inPack < $dRec->quantityInPack){
    				$push = FALSE;
    			}
    		}
    
    		// Ако ще обновяваме информацията за опаковката
    		if($push){
    			$arr = (object)array('packagingId' => $dRec->packagingId, 'inPack' => $dRec->quantityInPack);
    			$aggregator->push('shippedPacks', $arr, $index);
    		}
    		
    		$vat = cls::get($dRec->classId)->getVat($dRec->productId);
    		if($rec->chargeVat == 'yes' || $rec->chargeVat == 'separate'){
    			$dRec->packPrice += $dRec->packPrice * $vat;
    		}
    		
    		$aggregator->pushToArray('productVatPrices', $dRec->packPrice, $index);
    	}
    }
}