<?php



/**
 * Базов клас за наследяване на ф-ри
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class deals_InvoiceMaster extends core_Master
{
    
    
	/**
	 * Поле за единичния изглед
	 */
	public $rowToolsSingleField = 'number';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'dealValue,vatAmount,baseAmount,total,vatPercent,discountAmount';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'date';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $valiorFld = 'date';
    
    
    /**
     * Можели да се принтират оттеглените документи?
     */
    public $printRejected = TRUE;
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
    	'place'               => 'lastDocUser|lastDoc',
    	'responsible'         => 'lastDocUser|lastDoc',
    	'contragentCountryId' => 'lastDocUser|lastDoc|clientData',
    	'contragentVatNo'     => 'lastDocUser|lastDoc|clientData',
    	'uicNo'     		  => 'lastDocUser|lastDoc|clientData',
		'contragentPCode'     => 'lastDocUser|lastDoc|clientData',
    	'contragentPlace'     => 'lastDocUser|lastDoc|clientData',
        'contragentAddress'   => 'lastDocUser|lastDoc|clientData',
        'accountId'           => 'lastDocUser|lastDoc',
    	//'template' 			  => 'lastDocUser|lastDoc|LastDocSameCuntry',
    );
    
    
    /**
     * Опашка от записи за записване в on_Shutdown
     */
    protected $updated = array();
    
    
    /**
     * След описанието на полетата
     */
    protected static function setInvoiceFields(core_Master &$mvc)
    {
    	$mvc->FLD('date', 'date(format=d.m.Y)', 'caption=Дата,  notNull, mandatory, export=Csv');
    	$mvc->FLD('place', 'varchar(64)', 'caption=Място, class=contactData');
    	$mvc->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
    	$mvc->FLD('contragentId', 'int', 'input=hidden');
    	$mvc->FLD('contragentName', 'varchar', 'caption=Получател->Име, mandatory, class=contactData, export=Csv');
    	$mvc->FLD('responsible', 'varchar(255)', 'caption=Получател->Отговорник, class=contactData');
    	$mvc->FLD('contragentCountryId', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg)', 'caption=Получател->Държава,mandatory,contragentDataField=countryId');
    	$mvc->FLD('contragentVatNo', 'drdata_VatType', 'caption=Получател->VAT №,contragentDataField=vatNo, export=Csv');
    	$mvc->FLD('uicNo', 'type_Varchar', 'caption=Получател->Национален №,contragentDataField=uicId, export=Csv');
    	$mvc->FLD('contragentPCode', 'varchar(16)', 'caption=Получател->П. код,recently,class=pCode,contragentDataField=pCode');
    	$mvc->FLD('contragentPlace', 'varchar(64)', 'caption=Получател->Град,class=contactData,contragentDataField=place');
    	$mvc->FLD('contragentAddress', 'varchar(255)', 'caption=Получател->Адрес,class=contactData,contragentDataField=address');
    	$mvc->FLD('changeAmount', 'double(decimals=2)', 'input=none');
    	$mvc->FLD('reason', 'text(rows=2)', 'caption=Плащане->Основание, input=none');
    	$mvc->FLD('paymentMethodId', 'key(mvc=cond_PaymentMethods, select=description,allowEmpty)', 'caption=Плащане->Начин, export=Csv');
    	$mvc->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Валута->Код,input=hidden');
    	$mvc->FLD('rate', 'double(decimals=2)', 'caption=Валута->Курс,input=hidden');
    	$mvc->FLD('deliveryId', 'key(mvc=cond_DeliveryTerms, select=codeName, allowEmpty)', 'caption=Доставка->Условие,input=hidden');
    	$mvc->FLD('deliveryPlaceId', 'key(mvc=crm_Locations, select=title)', 'caption=Доставка->Място');
    	$mvc->FLD('vatDate', 'date(format=d.m.Y)', 'caption=Данъци->Дата на ДС');
    	$mvc->FLD('vatRate', 'enum(yes=Включено, separate=Отделно, exempt=Oсвободено, no=Без начисляване)', 'caption=Данъци->ДДС,input=hidden');
    	$mvc->FLD('vatReason', 'varchar(255)', 'caption=Данъци->Основание');
    	$mvc->FLD('additionalInfo', 'richtext(bucket=Notes, rows=6)', 'caption=Допълнително->Бележки');
    	$mvc->FLD('dealValue', 'double(decimals=2)', 'caption=Стойност, input=hidden,summary=amount, export=Csv');
    	$mvc->FLD('vatAmount', 'double(decimals=2)', 'caption=ДДС, input=none,summary=amount');
    	$mvc->FLD('discountAmount', 'double(decimals=2)', 'caption=Отстъпка->Обща, input=none,summary=amount');
    }
    
    
    /**
     *  Подготовка на филтър формата
     */
    public static function on_AfterPrepareListFilter($mvc, $data)
    {
    	$data->listFilter->FNC('invState', 'enum(all=Всички, draft=Чернова, active=Контиран)', 'caption=Състояние,input,silent');
    	
    	if($mvc->getField('type', FALSE)){
    		$data->listFilter->FNC('invType', 'enum(all=Всички, invoice=Фактура, credit_note=Кредитно известие, debit_note=Дебитно известие)', 'caption=Вид,input,silent');
    		$data->listFilter->showFields .= ',invType';
    	}
    	
    	$data->listFilter->showFields .= ',invState';
    	$data->listFilter->input();
    	$data->listFilter->setDefault('invState', 'all');
    	
    	if($rec = $data->listFilter->rec){
    		
    		// Филтър по тип на фактурата
    		if($rec->invType){
    			if($rec->invType != 'all'){
    				$data->query->where("#type = '{$rec->invType}'");
    			}
    		}
    		
    		// Филтър по състояние
    		if($rec->invState){
    			if($rec->invState != 'all'){
    				$data->query->where("#state = '{$rec->invState}'");
    			}
    		}
    	}
    	
    }
    
    
    /**
     * След като се поготви заявката за модела
     */
    public static function on_AfterGetQuery(core_Mvc $mvc, &$query)
    {
    	// Сортираме низходящо по номер
    	$query->orderBy('#number', 'DESC');
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
    public function updateMaster($id)
    {
    	$rec = $this->fetchRec($id);
    	$Detail = $this->mainDetail;
    	
    	$query = $this->$Detail->getQuery();
    	$query->where("#{$this->$Detail->masterKey} = '{$id}'");
    	$recs = $query->fetchAll();
    	
    	if(count($recs)){
    		foreach ($recs as &$dRec){
    			$dRec->price = $dRec->price * $dRec->quantityInPack;
    		}
    	}
    	
    	$this->$Detail->calculateAmount($recs, $rec);
    	
    	$rec->dealValue = $this->_total->amount * $rec->rate;
    	$rec->vatAmount = $this->_total->vat * $rec->rate;
    	$rec->discountAmount = $this->_total->discount * $rec->rate;
    	$this->save($rec);
    }
    
    
    /**
     * Преди подготвяне на едит формата
     */
    public static function on_BeforePrepareEditForm($mvc, &$res, $data)
    {
    	$type = Request::get('type');
    	if(!$type || $type == 'invoice') return;
    	 
    	$title = ($type == 'debit_note') ? 'Дебитно известие' : 'Кредитно известие';
    	$mvc->singleTitle = $title;
    }


    /**
     * Валидиране на полето 'date' - дата на фактурата
     * Предупреждение ако има фактура с по-нова дата (само при update!)
     */
    public static function on_ValidateDate(core_Mvc $mvc, $rec, core_Form $form)
    {
    	$newDate = $mvc->getNewestInvoiceDate();
    	if($newDate > $rec->date) {
    		
    		// Най-новата валидна ф-ра в БД е по-нова от настоящата.
    		$form->setError('date',
    				'Не може да се запише фактура с дата по-малка от последната активна фактура (' .
    				dt::mysql2verbal($newestInvoiceRec->date, 'd.m.y') .
    				')'
    		);
    	}
    }

	
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'conto' && isset($rec)){
    		
    		// Не може да се контира, ако има ф-ра с по нова дата
    		$lastDate = $mvc->getNewestInvoiceDate();
    		if($lastDate > $rec->date) {
    			$res = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Връща датата на последната ф-ра
     */
    protected function getNewestInvoiceDate()
    {
    	$query = $this->getQuery();
    	$query->where("#state = 'active'");
    	$query->orderBy('date', 'DESC');
    	$query->limit(1);
    	$lastRec = $query->fetch();
    	
    	return $lastRec->date;
    }
    
    
    /**
     * Валидиране на полето 'vatDate' - дата на данъчно събитие (ДС)
     *
     * Грешка ако ДС е след датата на фактурата или на повече от 5 дни преди тази дата.
     */
    public static function on_ValidateVatDate(core_Mvc $mvc, $rec, core_Form $form)
    {
    	if (empty($rec->vatDate)) {
    		return;
    	}
    
    	// Датата на ДС не може да бъде след датата на фактурата, нито на повече от 5 дни преди нея.
    	if ($rec->vatDate > $rec->date || dt::addDays(5, $rec->vatDate) < $rec->date) {
    		$form->setError('vatDate', '|Данъчното събитие трябва да е до 5 дни|* <b>|преди|*</b> |датата на фактурата|*');
    	}
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public function renderSingleLayout($data)
    {
    	$tpl = parent::renderSingleLayout($data);
    	
    	if(Mode::is('printing') || Mode::is('text', 'xhtml')){
    		$tpl->removeBlock('header');
    	}
    	 
    	if($data->paymentPlan){
    		$tpl->placeObject($data->paymentPlan);
    	}
    	
    	return $tpl;
    }


    /**
     * Подготвя вербалните данни на моята фирма
     */
    protected function prepareMyCompanyInfo(&$row)
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
    }


    /**
     * След подготовка на тулбара на единичен изглед.
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = &$data->rec;
    	
    	// Ако има бутон за принтиране, слагаме го да е първия бутон
    	if(!empty($data->toolbar->buttons['btnPrint'])){
    		$printUrl = array($mvc, 'single', $rec->id, 'Printing' => 'yes');
    		$data->toolbar->removeBtn('btnPrint');
    		$data->toolbar->addBtn('Печат', $printUrl, 'id=btnPrint,target=_blank,order=1', 'ef_icon = img/16/printer.png,title=Печат на страницата');
    	}
    	 
    	if($rec->type == 'invoice' && $rec->state == 'active' && $rec->dealValue){
    
    		if($mvc->haveRightFor('add', (object)array('type' => 'debit_note')) && $mvc->canAddToThread($rec->threadId)){
    			$data->toolbar->addBtn('ДИ', array($mvc, 'add', 'originId' => $rec->containerId, 'type' => 'debit_note', 'ret_url' => TRUE), 'ef_icon=img/16/layout_join_vertical.png,title=Дебитно известие');
    			$data->toolbar->addBtn('КИ', array($mvc, 'add','originId' => $rec->containerId, 'type' => 'credit_note', 'ret_url' => TRUE), 'ef_icon=img/16/layout_split_vertical.png,title=Кредитно известие');
    		}
    	}
    }


    /**
     * Попълва дефолтите на Дебитното / Кредитното известие
     */
    protected function populateNoteFromInvoice(core_Form &$form, core_ObjectReference $origin)
    {
    	$caption = ($form->rec->type == 'debit_note') ? 'Увеличение' : 'Намаление';
    
    	$invArr = (array)$origin->fetch();
    	$number = $origin->instance->recToVerbal((object)$invArr)->number;
    	 
    	$invDate = dt::mysql2verbal($invArr['date'], 'd.m.Y');
    	$invArr['reason'] = tr("|{$caption} към фактура|* №{$number} |издадена на|* {$invDate}");
    
    	foreach(array('id', 'number', 'date', 'containerId', 'additionalInfo', 'dealValue', 'vatAmount', 'state', 'discountAmount', 'createdOn', 'createdBy', 'modifiedOn', 'modifiedBy') as $key){
    		unset($invArr[$key]);
    	}
    
    	// Копиране на повечето от полетата на фактурата
    	foreach($invArr as $field => $value){
    		$form->setDefault($field, $value);
    	}
    	 
    	$form->setDefault('date', dt::today());
    	$form->setField('reason', 'input');
    	$form->setField('changeAmount', 'input');
    	$form->setField('changeAmount', "unit={$form->rec->currencyId} без ДДС");
    	$form->setField('vatRate', 'input=hidden');
    	$form->setField('reason', 'input,mandatory');
    	$form->setField('deliveryId', 'input=none');
    	$form->setField('deliveryPlaceId', 'input=none');
    
    	foreach(array('rate', 'currencyId', 'contragentName', 'contragentVatNo', 'uicNo', 'contragentCountryId') as $name){
    		if($form->rec->$name){
    			$form->setReadOnly($name);
    		}
    	}
    
    	$form->setField('changeAmount', "caption=Плащане->{$caption},mandatory");
    }
    

    /**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото на имейла по подразбиране
     */
    public static function getDefaultEmailBody($id)
    {
    	$handle = static::getHandle($id);
    	 
    	$type = static::fetchField($id, 'type');
    	switch($type){
    		case 'invoice':
    			$type = "приложената фактура";
    			break;
    		case 'debit_note':
    			$type = "приложеното дебитно известие";
    			break;
    		case 'credit_note':
    			$type = "приложеното кредитно известие";
    			break;
    	}
    
    	// Създаване на шаблона
    	$tpl = new ET(tr("Моля запознайте се с") . " [#type#]:\n#[#handle#]");
    	$tpl->append($handle, 'handle');
    	$tpl->append(tr($type), 'type');
    
    	return $tpl->getContent();
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
		$row = new stdClass();
        $row->title = static::getRecTitle($rec);
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->authorId = $rec->createdBy;
        $row->state = $rec->state;
        $row->recTitle = $row->title;
        
        return $row;
   }

   
   /**
    * Връща масив от използваните нестандартни артикули в фактурата
    * @param int $id - ид на фактура
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
    * Извиква се след подготовката на toolbar-а за табличния изглед
    */
   public static function on_AfterPrepareListToolbar($mvc, &$data)
   {
	   	if(!empty($data->toolbar->buttons['btnAdd'])){
	   		$data->toolbar->removeBtn('btnAdd');
	   	}
   }


   /**
    * Документа не може да се активира ако има детайл с количество 0
    */
   public static function on_AfterCanActivate($mvc, &$res, $rec)
   {
	   	// ДИ и КИ могат да се активират винаги
	   	if($rec->type != 'invoice' && isset($rec->changeAmount)){
	   		$res = ($rec->changeAmount >= 0) ? TRUE : FALSE;
	   		return;
	   	}
	   	 
	   	// Ако няма ид, не може да се активира документа
	   	if(empty($rec->id) && !isset($rec->dpAmount)) return $res = FALSE;
	   	 
	   	// Ако има Авансово плащане може да се активира
	   	if(isset($rec->dpAmount)){
	   		$res = (round($rec->dealValue, 2) < 0 || is_null($rec->dealValue)) ? FALSE : TRUE;
	   
	   		return;
	   	}
	   	 
	   	$Detail = $mvc->mainDetail;
	   	$dQuery = $mvc->$Detail->getQuery();
	   	$dQuery->where("#{$mvc->$Detail->masterKey} = {$rec->id}");
	   	$dQuery->where("#quantity = 0");
	   	 
	   	// Ако има поне едно 0-во к-во документа, не може да се активира
	   	if($dQuery->fetch()){
	   		$res = FALSE;
	   	}
   }


   /**
    * Генерира фактура от пораждащ документ: може да се породи от:
    * 
    * 1. Продажба / Покупка
    * 2. Фактура тоест се прави ДИ или КИ
    */
   public static function on_AfterCreate($mvc, $rec)
   {
	   	expect($origin = $mvc::getOrigin($rec));
	   	 
	   	if ($origin->haveInterface('bgerp_DealAggregatorIntf')) {
	   		$info = $origin->getAggregateDealInfo();
	   		$agreed = $info->get('products');
	   		$products = $info->get('shippedProducts');
	   		$invoiced = $info->get('invoicedProducts');
	   		$packs = $info->get('shippedPacks');
	   		
	   		$mvc::prepareProductFromOrigin($mvc, $rec, $agreed, $products, $invoiced, $packs);
	   	}
   }
   

   /**
    * Подготвя продуктите от ориджина за запис в детайла на модела
    */
   protected static function prepareProductFromOrigin($mvc, $rec, $agreed, $products, $invoiced, $packs)
   {
	   	if(count($products) != 0){
	   		
	   		// Записваме информацията за продуктите в детайла
	   		foreach ($products as $product){
	   			$continue = FALSE;
	   			$diff = $product->quantity;
	   			if(count($invoiced)){
	   				foreach ($invoiced as $inv){
	   					if($inv->classId == $product->classId && $inv->productId == $product->productId){
	   						$diff = $product->quantity - $inv->quantity;
	   						if($diff <= 0){
	   							$continue = TRUE;
	   						}
	   						break;
	   					}
	   				}
	   			}
	   	
	   			if($continue) continue;
	   	
	   			$mvc::saveProductFromOrigin($mvc, $rec, $product, $packs, $diff);
	   		}
	   	}
   }
   
   
   /**
    * Записва продукт от ориджина
    */
   protected static function saveProductFromOrigin($mvc, $rec, $product, $packs, $restAmount)
   {
	   	$dRec = clone $product;
	   	$index = $product->classId . "|" . $product->productId;
	   	if($packs[$index]){
	   		$packQuantity = $packs[$index]->inPack;
	   		$dRec->packagingId = $packs[$index]->packagingId;
	   	} else {
	   		$packQuantity = 1;
	   		$dRec->packagingId = NULL;
	   	}
	   	
	   	$Detail = $mvc->mainDetail;
	   	$dRec->{$mvc->$Detail->masterKey} = $rec->id;
	   	$dRec->classId        			  = $product->classId;
	   	$dRec->discount        			  = $product->discount;
	   	$dRec->price 		  			  = ($product->amount) ? ($product->amount / $product->quantity) : $product->price;
	   	$dRec->quantityInPack 			  = $packQuantity;
	   	$dRec->quantity       			  = $restAmount / $packQuantity;
	   	
	   	$mvc->$Detail->save($dRec);
   }
   
   
   /**
    * Подготвя данните (в обекта $data) необходими за единичния изглед
    */
   public function prepareSingle_($data)
   {
	   	parent::prepareSingle_($data);
	   	$rec = &$data->rec;
	   	 
	   	if(empty($data->noTotal)){
	   		if(isset($rec->type) && $rec->type != 'invoice'){
	   			$this->_total = new stdClass();
	   			$this->_total->amount = $rec->dealValue / $rec->rate;
	   			$this->_total->vat = $rec->vatAmount / $rec->rate;
	   		}
	   		
	   		$data->summary = deals_Helper::prepareSummary($this->_total, $rec->date, $rec->rate, $rec->currencyId, $rec->vatRate, TRUE, 'bg');
	   		$data->row = (object)((array)$data->row + (array)$data->summary);
	   		
	   		if($rec->paymentMethodId && $rec->type == 'invoice' && $rec->dpOperation != 'accrued') {
	   			$total = $this->_total->amount + $this->_total->vat - $this->_total->discount;
	   			cond_PaymentMethods::preparePaymentPlan($data, $rec->paymentMethodId, $total, $rec->date, $rec->currencyId);
	   		}
	   	}
   }
    
    
   /**
    * След подготовка на тулбара на единичен изглед.
    */
   public static function on_AfterPrepareSingle($mvc, &$res, &$data)
   {
    	$rec = &$data->rec;
    	
    	$myCompany = crm_Companies::fetchOwnCompany();
    	if($rec->contragentCountryId != $myCompany->countryId){
    		$data->row->place = str::utf2ascii($data->row->place);
    	}
    }
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене, това са името на
     * документа или папката
     */
    public static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
    	// Тук ще генерираме всички ключови думи
    	$detailsKeywords = '';
    
    	// заявка към детайлите
    	$Detail = cls::get($mvc->mainDetail);
    	$query = $Detail->getQuery();
    	
    	// точно на тази фактура детайлите търсим
    	$query->where("#{$Detail->masterKey} = '{$rec->id}'");
    
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
     * След подготовка на формата
     */
    protected static function prepareInvoiceForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$form->setDefault('date', dt::today());
    	
    	$coverClass = doc_Folders::fetchCoverClassName($form->rec->folderId);
    	$coverId = doc_Folders::fetchCoverId($form->rec->folderId);
    	$form->rec->contragentName = $coverClass::fetchField($coverId, 'name');
    
    	$className = doc_Folders::fetchCoverClassName($form->rec->folderId);
    	if($className == 'crm_Persons'){
    		$numType = 'bglocal_EgnType';
    		$form->setField('uicNo', 'caption=Получател->ЕГН');
    		$form->getField('uicNo')->type = cls::get($numType);
    	}
    
    	$type = Request::get('type');
    	if(empty($type)){
    		$type = 'invoice';
    	}
    	$form->setDefault('type', $type);
    	 
    	// При създаване на нова ф-ра зареждаме полетата на
    	// формата с разумни стойности по подразбиране.
    	expect($origin = $mvc::getOrigin($form->rec));
    	
    	if($origin->haveInterface('bgerp_DealAggregatorIntf')){
    		$aggregateInfo         = $origin->getAggregateDealInfo();
    		 
    		$form->rec->vatRate    = $aggregateInfo->get('vatType');
    		$form->rec->currencyId = $aggregateInfo->get('currency');
    		$form->rec->rate       = $aggregateInfo->get('rate');
    		 
    		if($aggregateInfo->get('paymentMethodId')){
    			$form->rec->paymentMethodId = $aggregateInfo->get('paymentMethodId');
    			$form->setField('paymentMethodId', 'input=hidden');
    		}
    		 
    		$form->rec->deliveryId = $aggregateInfo->get('deliveryTerm');
    		if($aggregateInfo->get('deliveryLocation')){
    			$form->rec->deliveryPlaceId = $aggregateInfo->get('deliveryLocation');
    			$form->setField('deliveryPlaceId', 'input=hidden');
    		}
    		
    		$data->aggregateInfo = $aggregateInfo;
    	}
    	 
    	if($origin->className  == $mvc->className){
    		$mvc->populateNoteFromInvoice($form, $origin);
    		$data->flag = TRUE;
    	}
    	 
    	if(empty($data->flag)){
    		$form->setDefault('currencyId', drdata_Countries::fetchField(($form->rec->contragentCountryId) ? $form->rec->contragentCountryId : $mvc->fetchField($form->rec->id, 'contragentCountryId'), 'currencyCode'));
    		$locations = crm_Locations::getContragentOptions($coverClass, $coverId);
    		$form->setOptions('deliveryPlaceId',  array('' => '') + $locations);
    	}
    	 
    	// Метод който да бъде прихванат от deals_plg_DpInvoice
    	$mvc->prepareDpInvoicePlg($data);
    }
    
    
    /**
     * След изпращане на формата
     */
    protected static function inputInvoiceForm(core_Mvc $mvc, core_Form $form)
    {
    	if ($form->isSubmitted()) {
    		$rec = &$form->rec;
    		 
    		if(!$rec->rate){
    			$rec->rate = round(currency_CurrencyRates::getRate($rec->date, $rec->currencyId, NULL), 4);
    		}
    
    		if($msg = currency_CurrencyRates::hasDeviation($rec->rate, $rec->date, $rec->currencyId, NULL)){
    			$form->setWarning('rate', $msg);
    		}
    		 
    		$Vats = cls::get('drdata_Vats');
    		$rec->contragentVatNo = $Vats->canonize($rec->contragentVatNo);
    		 
    		foreach ($mvc->fields as $fName => $field) {
    			$mvc->invoke('Validate' . ucfirst($fName), array($rec, $form));
    		}
    		 
    		if(strlen($rec->contragentVatNo) && !strlen($rec->uicNo)){
    			$rec->uicNo = drdata_Vats::getUicByVatNo($rec->contragentVatNo);
    		} elseif(!strlen($rec->contragentVatNo) && !strlen($rec->uicNo)){
    			$form->setError('contragentVatNo,uicNo', 'Трябва да е въведен поне един от номерата');
    		}
    		 
    		// Ако е ДИ или КИ
    		if($rec->type != 'invoice'){
    	   
    			// Изчисляване на стойността на ддс-то
    			$vat = acc_Periods::fetchByDate()->vatRate;
    			$rec->vatAmount = $rec->changeAmount * $vat;
    			$rec->vatAmount *= $rec->rate;
    
    			// Стойността е променената сума
    			$rec->dealValue = $rec->changeAmount;
    			$rec->dealValue *= $rec->rate;
    		}
    	}
    
    	acc_Periods::checkDocumentDate($form);
    
    	// Метод който да бъде прихванат от deals_plg_DpInvoice
    	$mvc->inputDpInvoice($form);
    }
    
    
    /**
     * Преди запис в модела
     */
    protected static function beforeInvoiceSave($rec)
    {
    	if (empty($rec->vatDate)) {
    		$rec->vatDate = $rec->date;
    	}
    
    	if (!empty($rec->folderId)) {
    		$rec->contragentClassId = doc_Folders::fetchCoverClassId($rec->folderId);
    		$rec->contragentId = doc_Folders::fetchCoverId($rec->folderId);
    	}
    
    	if($rec->state == 'active'){
    		 
    		if(empty($rec->place) && $rec->state == 'active'){
    			$inCharge = cls::get($rec->contragentClassId)->fetchField($rec->contragentId, 'inCharge');
    			$inChargeRec = crm_Profiles::getProfile($inCharge);
    			$myCompany = crm_Companies::fetchOwnCompany();
    			$place = empty($inChargeRec->place) ? $myCompany->place : $inChargeRec->place;
    			$countryId = empty($inChargeRec->country) ? $myCompany->countryId : $inChargeRec->country;
    
    			$rec->place = $place;
    			if($rec->contragentCountryId != $countryId){
    				$cCountry = drdata_Countries::fetchField($countryId, 'commonNameBg');
    				$rec->place .= (($place) ? ", " : "") . $cCountry;
    			}
    		}
    	}
    }
    
    
    /**
     * Вербално представяне на фактурата
     */
    protected static function getVerbalInvoice($mvc, $rec, $row, $fields)
    {
    	if($rec->number){
    		$row->number = str_pad($rec->number, '10', '0', STR_PAD_LEFT);
    	}
    	 
    	if($fields['-list']){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    		if($rec->number){
    			$row->number = ht::createLink($row->number, array($mvc, 'single', $rec->id),NULL, 'ef_icon=img/16/invoice.png');
    		}
    	
    		$total = $rec->dealValue + $rec->vatAmount - $rec->discountAmount;
    		@$row->dealValue = $mvc->getFieldType('dealValue')->toVerbal($total / $rec->rate);
    		$row->dealValue = "<span class='cCode' style='float:left'>{$rec->currencyId}</span>&nbsp;" . $row->dealValue;
    	
    		$baseCode = acc_Periods::getBaseCurrencyCode($rec->date);
    		$row->vatAmount = "<span class='cCode' style='float:left'>{$baseCode}</span>&nbsp;" . $row->vatAmount;
    	}
    	
    	if($fields['-single']){
    	
    		if($rec->originId && $rec->type != 'invoice'){
    			unset($row->deliveryPlaceId, $row->deliveryId);
    		}
    	
    		if(doc_Folders::fetchCoverClassName($rec->folderId) == 'crm_Persons'){
    			$row->cNum = tr('|ЕГН|* / <i>Personal №</i>');
    		} else {
    			$row->cNum = tr('|ЕИК|* / <i>UIC</i>');
    		}
    	
    		$row->header = "{$row->type} #<b>{$mvc->getHandle($rec->id)}</b> ({$row->state})" ;
    		$userRec = core_Users::fetch($rec->createdBy);
    		$row->username = core_Users::recToVerbal($userRec, 'names')->names;
    	
    		if($rec->type != 'invoice'){
    			$originRec = $mvc->getOrigin($rec)->fetch();
    			$originRow = $mvc->recToVerbal($originRec, 'number,date');
    			$row->originInv = $originRow->number;
    			$row->originInvDate = $originRow->date;
    		}
    			
    		if($rec->rate == 1){
    			unset($row->rate);
    		}
    			
    		if(!$row->vatAmount){
    			$row->vatAmount = "<span class='quiet'>0,00</span>";
    		}
    		$mvc->prepareMyCompanyInfo($row);
    	}
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$row = static::recToVerbal($rec, 'type,number,-list');
    	$row->number = strip_tags($row->number);
    	$num = ($row->number) ? $row->number : $rec->id;
    
    	return tr("|{$row->type}|* №{$num}");
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
    	$total = $rec->dealValue + $rec->vatAmount - $rec->discountAmount;
    	$total = ($rec->type == 'credit_note') ? -1 * $total : $total;
    
    	$aggregator->sum('invoicedAmount', $total);
    	$aggregator->setIfNot('invoicedValior', $rec->date);
    	$aggregator->setIfNot('paymentMethodId', $rec->paymentMethodId);
    
    	if(isset($rec->dpAmount)){
    		if($rec->dpOperation == 'accrued'){
    			$aggregator->sum('downpaymentInvoiced', $total);
    		} elseif($rec->dpOperation == 'deducted') {
    			$vat = acc_Periods::fetchByDate($rec->date)->vatRate;
    			
    			// Колко е приспаднатото плащане с ддс
    			$deducted = abs($rec->dpAmount);
    			$vatAmount = ($rec->vatRate == 'yes' || $rec->vatRate == 'separate') ? ($deducted) * $vat : 0;
    			$aggregator->sum('downpaymentDeducted', $deducted + $vatAmount);
    		}
    	}
    
    	$Detail = $this->mainDetail;
    	
    	$dQuery = $Detail::getQuery();
    	$dQuery->where("#invoiceId = {$rec->id}");
    
    	// Намираме всички фактурирани досега продукти
    	$invoiced = $aggregator->get('invoicedProducts');
    	while ($dRec = $dQuery->fetch()) {
    		$p = new stdClass();
    		$p->classId     = $dRec->classId;
    		$p->productId   = $dRec->productId;
    		$p->packagingId = $dRec->packagingId;
    		$p->quantity    = $dRec->quantity * $dRec->quantityInPack;
    
    		// Добавяме към фактурираните продукти
    		$update = FALSE;
    		if(count($invoiced)){
    			foreach ($invoiced as &$inv){
    				if($inv->classId == $p->classId && $inv->productId == $p->productId){
    					$inv->quantity += $p->quantity;
    					$update = TRUE;
    					break;
    				}
    			}
    		}
    		 
    		if(!$update){
    			$invoiced[] = $p;
    		}
    	}
    	
    	$aggregator->set('invoicedProducts', $invoiced);
    }
    
    
    /**
     * След подготовка на авансова ф-ра
     */
    public static function on_AfterPrepareDpInvoicePlg($mvc, &$res, &$data)
    {
    	
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputDpInvoice($mvc, &$res, &$form)
    {
    	
    }
}