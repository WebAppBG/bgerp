<?php
/**
 * Документ "Оферта"
 *
 * Мениджър на документи за Оферта за продажба
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_Quotations extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Оферти';


    /**
     * Абревиатура
     */
    public $abbr = 'Q';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'sales_Quotes';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, doc_ContragentDataIntf, email_DocumentIntf,  bgerp_DealIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, sales_Wrapper, plg_Sorting, plg_Printing, doc_EmailCreatePlg, acc_plg_DocumentSummary, plg_Search, doc_plg_HidePrices, doc_plg_TplManager,
                    doc_DocumentPlg, doc_ActivatePlg, bgerp_plg_Blank, doc_plg_BusinessDoc, cond_plg_DefaultValues';
       
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,sales';
    
    
    /**
     * Поле за търсене по дата
     */
    public $filterDateField = 'date';
    
    
    /**
     * В кой плейсхолдър ще се слага шаблона от doc_plg_TplManager
     */
    public $templateFld = 'QUOTE_HEADER';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/document_quote.png';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,sales';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,sales';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, date, folderId, state, createdOn,createdBy';
    

    /**
     * Детайла, на модела
     */
    public $details = 'sales_QuotationsDetails';
    

    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Оферта';
    
    
    /**
     * Шаблон за еденичен изглед
     */
    public $singleLayoutFile = 'sales/tpl/SingleLayoutQuote.shtml';
   
   
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'paymentMethodId, reff, company, person, email, folderId';
    
   
    /**
     * Брой оферти на страница
     */
    public $listItemsPerPage = '20';
    
    
    /**
      * Групиране на документите
      */ 
    public $newBtnGroup = "3.7|Търговия";
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
    
    	'validFor'        => 'lastDocUser|lastDoc|',
    	'paymentMethodId' => 'clientCondition|lastDocUser|lastDoc',
        'currencyId'      => 'lastDocUser|lastDoc',
        'chargeVat'       => 'lastDocUser|lastDoc|defMethod',
    	'others'          => 'lastDocUser|lastDoc',
        'deliveryTermId'  => 'clientCondition|lastDocUser|lastDoc',
        'deliveryPlaceId' => 'lastDocUser|lastDoc|',
        'company'         => 'lastDocUser|lastDoc|clientData',
        'person' 		  => 'lastDocUser|lastDoc|clientData',
        'email' 		  => 'lastDocUser|lastDoc|clientData',
    	'tel' 			  => 'lastDocUser|lastDoc|clientData',
        'fax' 			  => 'lastDocUser|lastDoc|clientData',
        'country'		  => 'lastDocUser|lastDoc|clientData',
        'pCode' 		  => 'lastDocUser|lastDoc|clientData',
    	'place' 		  => 'lastDocUser|lastDoc|clientData',
    	'address' 		  => 'lastDocUser|lastDoc|clientData',
    	'template' 		  => 'lastDocUser|lastDoc|LastDocSameCuntry',
    );
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('date', 'date', 'caption=Дата, mandatory'); 
        $this->FLD('reff', 'varchar(255)', 'caption=Ваш реф.,class=contactData');
        
        $this->FNC('row1', 'complexType(left=К-во,right=Цена)', 'caption=Детайли->К-во / Цена');
    	$this->FNC('row2', 'complexType(left=К-во,right=Цена)', 'caption=Детайли->К-во / Цена');
    	$this->FNC('row3', 'complexType(left=К-во,right=Цена)', 'caption=Детайли->К-во / Цена');
    	
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
        $this->FLD('contragentId', 'int', 'input=hidden');
        $this->FLD('paymentMethodId', 'key(mvc=cond_PaymentMethods,select=description)','caption=Плащане->Метод,width=15em,salecondSysId=paymentMethodSale');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)','caption=Плащане->Валута,width=8em,oldFieldName=paymentCurrencyId');
        $this->FLD('currencyRate', 'double(decimals=2)', 'caption=Плащане->Курс,width=8em,oldFieldName=rate');
        $this->FLD('chargeVat', 'enum(yes=Включено, separate=Отделно, exempt=Oсвободено, no=Без начисляване)','caption=Плащане->ДДС,oldFieldName=vat');
        $this->FLD('deliveryTermId', 'key(mvc=cond_DeliveryTerms,select=codeName)', 'caption=Доставка->Условие,width=8em,salecondSysId=deliveryTermSale');
        $this->FLD('deliveryPlaceId', 'varchar(126)', 'caption=Доставка->Място,width=10em,hint=Изберете локация или въведете нова');
        
		$this->FLD('company', 'varchar', 'caption=Получател->Фирма, changable, class=contactData');
        $this->FLD('person', 'varchar', 'caption=Получател->Лице, changable, class=contactData');
        $this->FLD('email', 'varchar', 'caption=Получател->Имейл, changable, class=contactData');
        $this->FLD('tel', 'varchar', 'caption=Получател->Тел., changable, class=contactData');
        $this->FLD('fax', 'varchar', 'caption=Получател->Факс, changable, class=contactData');
        $this->FLD('country', 'varchar', 'caption=Получател->Държава, changable, class=contactData');
        $this->FLD('pCode', 'varchar', 'caption=Получател->П. код, changable, class=contactData');
        $this->FLD('place', 'varchar', 'caption=Получател->Град/с, changable, class=contactData');
        $this->FLD('address', 'varchar', 'caption=Получател->Адрес, changable, class=contactData');
    	
    	$this->FLD('validFor', 'time(uom=days,suggestions=10 дни|15 дни|30 дни|45 дни|60 дни|90 дни)', 'caption=Допълнително->Валидност,width=8em');
    	$this->FLD('others', 'text(rows=4)', 'caption=Допълнително->Условия,width=100%', array('attr' => array('style' => 'max-width:500px;')));
    }
    
    
	/**
     * Дали да се начислява ДДС
     */
    public function getDefaultVat($rec)
    {
        $coverId = doc_Folders::fetchCoverId($rec->folderId);
    	$Class = cls::get(doc_Folders::fetchCoverClassName($rec->folderId));
    	
    	return ($Class->shouldChargeVat($coverId)) ? 'yes' : 'export';
    }
    
    
	/**
     * Малко манипулации след подготвянето на формата за филтриране
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
    	 $data->listFilter->showFields = 'search,' . $data->listFilter->showFields;
    	 $data->listFilter->input();
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
       $rec = &$data->form->rec;
       if(empty($rec->id)){
       	  $mvc->populateDefaultData($data->form);
       } else {
       		if($mvc->sales_QuotationsDetails->fetch("#quotationId = {$data->form->rec->id}")){
	       		foreach (array('chargeVat', 'currencyRate', 'currencyId', 'deliveryTermId') as $fld){
	        		$data->form->setReadOnly($fld);
	        	}
	       	}
       }
      
       $locations = crm_Locations::getContragentOptions($rec->contragentClassId, $rec->contragentId, FALSE);
       $data->form->setSuggestions('deliveryPlaceId',  array('' => '') + $locations);
      
       if($rec->originId){
       	
       		// Ако офертата има ориджин
       		$data->form->setField('row1,row2,row3', 'input');
       		$origin = doc_Containers::getDocument($rec->originId);
       		
       		if($origin->haveInterface('techno_ProductsIntf')){
       			$price = $origin->getPriceInfo()->price;
	       		
       			// Ако няма цена офертата потребителя е длъжен да я въведе от формата
	       		if(!$price){
	       			$data->form->fields['row1']->type->params['require'] = 'both';
	       			$data->form->fields['row2']->type->params['require'] = 'both';
	       			$data->form->fields['row3']->type->params['require'] = 'both';
	       		}
       		}
       }
       
       if(!$rec->person){
       	  $data->form->setSuggestions('person', crm_Companies::getPersonOptions($rec->contragentId, FALSE));
       }
       
       $data->form->addAttr('currencyId', array('onchange' => "document.forms['{$data->form->formAttr['id']}'].elements['currencyRate'].value ='';"));
    }
    
    
	/** 
	 * След подготовка на тулбара на единичен изглед
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
	    if($data->rec->state == 'active'){
	    	$items = $mvc->getItems($data->rec->id);
	    	if((sales_QuotationsDetails::fetch("#quotationId = {$data->rec->id} AND #optional = 'yes'") || !$items) AND sales_SaleRequests::haveRightFor('add')){
	    		
	    		// Ако има поне един опционален продукт, може да се генерира заявка
	    		$data->toolbar->addBtn('Заявка', array('sales_SaleRequests', 'CreateFromOffer', 'originId' => $data->rec->containerId, 'ret_url' => TRUE), NULL, 'ef_icon=img/16/star_2.png,title=Създаване на нова заявка за продажба');
	    	} elseif($items && sales_Sales::haveRightFor('add')){
	    		
	    		// Ако има уникални продукти и потребителя има може да създава продажба, се поставя бутон за продажба
	    		$data->toolbar->addBtn('Продажба', array('sales_Sales', 'add', 'originId' => $data->rec->containerId, 'ret_url' => TRUE), NULL, 'ef_icon=img/16/star_2.png,title=Създаване на продажба по офертата');
	    	}
	    }
    }
    
    
    /** 
	 * След подготовка на тулбара на единичен изглед
     */
    static function on_AfterPrepareSingle($mvc, &$res, &$data)
    {
    	if($data->sales_QuotationsDetails->summary){
    		$data->row = (object)((array)$data->row + (array)$data->sales_QuotationsDetails->summary);
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
	    	$rec = &$form->rec;
	    	
		    if(!$rec->currencyRate){
			    $rec->currencyRate = round(currency_CurrencyRates::getRate($rec->date, $rec->currencyId, NULL), 4);
			}
		
	    	if($msg = currency_CurrencyRates::hasDeviation($rec->currencyRate, $rec->date, $rec->currencyId, NULL)){
			    $form->setWarning('rate', $msg);
			}
		}
    }
    
    
	/**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave($mvc, &$id, $rec)
    {
    	if($rec->originId){
    		$origin = doc_Containers::getDocument($rec->originId);
    		
    		// Ориджина трябва да е спецификация
    		expect(cls::haveInterface('techno_ProductsIntf', $origin->className));
    		$originRec = $origin->fetch();
    		
    		// В папка на контрагент
    		$coverClass = doc_Folders::fetchCoverClassName($originRec->folderId);
    		expect(cls::haveInterface('doc_ContragentDataIntf', $coverClass));
    		
    		$dRows = array($rec->row1, $rec->row2, $rec->row3);
    		if(($dRows[0] || $dRows[1] || $dRows[2])){
    			$mvc->sales_QuotationsDetails->insertFromSpecification($rec, $origin, $dRows);
			}
    	}
    }
    
    
    /**
     * Попълване на дефолт данни
     */
    public function populateDefaultData(core_Form &$form)
    {
    	$form->setDefault('date', dt::now());
    	expect($data = doc_Folders::getContragentData($form->rec->folderId), "Проблем с данните за контрагент по подразбиране");
    	$contragentClassId = doc_Folders::fetchCoverClassId($form->rec->folderId);
    	$contragentId = doc_Folders::fetchCoverId($form->rec->folderId);
    	$form->setDefault('contragentClassId', $contragentClassId);
    	$form->setDefault('contragentId', $contragentId);
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
		if($fields['-single']){
			$quotDate = dt::mysql2timestamp($rec->date);
			$timeStamp = dt::mysql2timestamp(dt::verbal2mysql());
			if(isset($rec->validFor) && (($quotDate + $rec->validFor) < $timeStamp)){
				$row->expired = tr("офертата е изтекла");
			}
			
			$row->header = $mvc->singleTitle . " №<b>{$row->id}</b> ({$row->state})";
	    	$row->number = $mvc->getHandle($rec->id);
			$row->username = core_Users::recToVerbal(core_Users::fetch($rec->createdBy), 'names')->names;
			
			$contragent = new core_ObjectReference($rec->contragentClassId, $rec->contragentId);
			$row->contragentAddress = $contragent->getFullAdress();
			
			if($rec->currencyRate == 1){
				unset($row->currencyRate);
			}
			
			if($rec->others){
				$others = explode('<br>', $row->others);
				$row->others = '';
				foreach ($others as $other){
					$row->others .= "<li>{$other}</li>";
				}
			}
			
			if(!Mode::is('text', 'xhtml') && !Mode::is('printing')){
				if($rec->deliveryPlaceId){
					if($placeId = crm_Locations::fetchField("#title = '{$rec->deliveryPlaceId}'", 'id')){
		    			$row->deliveryPlaceId = ht::createLinkRef($row->deliveryPlaceId, array('crm_Locations', 'single', $placeId), NULL, 'title=Към локацията');
					}
				}
				
				if(cond_DeliveryTerms::haveRightFor('single', $rec->deliveryTermId)){
					$row->deliveryTermId = ht::createLinkRef($row->deliveryTermId, array('cond_DeliveryTerms', 'single', $rec->deliveryTermId));
				}
			}
			
			$ownCompanyData = crm_Companies::fetchOwnCompany();
	        $Companies = cls::get('crm_Companies');
	        $row->MyCompany = cls::get('type_Varchar')->toVerbal($ownCompanyData->company);
	        $row->MyAddress = $Companies->getFullAdress($ownCompanyData->companyId);
		}
		
    	if($fields['-list']){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
	    }
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    static function getHandle($id)
    {
    	$rec = static::fetch($id);
    	$self = cls::get(get_called_class());
    	
    	return $self->abbr . $rec->id;
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = "Оферта №" .$this->abbr . $rec->id;
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
        $row->recTitle = $row->title;

        return $row;
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
	  	if(Mode::is('printing') || Mode::is('text', 'xhtml')){
    		$tpl->removeBlock('header');
    	}
    	
    	$tpl->push('sales/tpl/styles.css', 'CSS');
    }
    
    
    /**
     * След проверка на ролите
     */
    function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec, $userId)
    {
    	if($res == 'no_one') return;
    	if($action == 'activate'){
    		if(!$rec->id) {
    			
    			// Ако документа се създава, то не може да се активира
    			$res = 'no_one';
    		} else {
    			
    			// Ако няма задължителни продукти/услуги не може да се активира
    			$detailQuery = $mvc->sales_QuotationsDetails->getQuery();
    			$detailQuery->where("#quotationId = {$rec->id}");
    			$detailQuery->where("#optional = 'no'");
    			if(!$detailQuery->count()){
    				$res = 'no_one';
    			}
    		}
    	}
    	
    	if($action == 'edit'){
    		$res = 'ceo,sales';
    	}
    }
    
    
	/**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото на имейл по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
        $handle = static::getHandle($id);
        $tpl = new ET(tr("Моля запознайте се с нашата оферта") . ': #[#handle#]');
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
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
    	$coverClass = doc_Folders::fetchCoverClassName($threadRec->folderId);
    	
    	return cls::haveInterface('doc_ContragentDataIntf', $coverClass);
    }
    
    
	/**
     * Документи-оферти могат да се добавят само в папки с корица контрагент.
     */
    public static function canAddToFolder($folderId)
    {
        $coverClass = doc_Folders::fetchCoverClassName($folderId);
    
        return cls::haveInterface('doc_ContragentDataIntf', $coverClass);
    }
    
    
    /**
     * Функция, която прихваща след активирането на документа
     * Ако офертата е базирана на чернова спецификация, активираме и нея
     */
    public static function on_AfterActivation($mvc, &$rec)
    {
    	if($rec->originId){
    		$origin = doc_Containers::getDocument($rec->originId);
	    	if($origin->haveInterface('techno_ProductsIntf')){
	    		$originRec = $origin->fetch();
	    		if($originRec->state == 'draft'){
	    			$originRec->state = 'active';
	    			$origin->getInstance()->save($originRec);
	    		}		
	    	}
    	}
    	
    	if($rec->deliveryPlaceId){
		    if(!crm_Locations::fetchField(array("#title = '[#1#]'", $rec->deliveryPlaceId), 'id')){
		    	$newLocation = (object)array(
		    						'title' => $rec->deliveryPlaceId,
		    						'countryId' => drdata_Countries::fetchField("#commonNameBg = '{$rec->country}' || #commonName = '{$rec->country}'", 'id'),
		    						'pCode' => $rec->pcode,
		    						'place' => $rec->place,
		    						'contragentCls' => $rec->contragentClassId,
		    						'contragentId' => $rec->contragentId,
		    						'type' => 'correspondence');
		    		
		    	// Ако локацията я няма в системата я записваме
		    	crm_Locations::save($newLocation);
		    }
		}
    }
    
    
    /**
     * Връща масив от използваните документи в офертата
     * @param int $id - ид на оферта
     * @return param $res - масив с използваните документи
     * 					['class'] - Инстанция на документа
     * 					['id'] - ид на документа
     */
    public function getUsedDocs_($id)
    {
    	$res = array();
    	$dQuery = $this->sales_QuotationsDetails->getQuery();
    	$dQuery->EXT('state', 'sales_Quotations', 'externalKey=quotationId');
    	$dQuery->where("#quotationId = '{$id}'");
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
     * Имплементация на @link bgerp_DealIntf::getDealInfo()
     * 
     * @param int|object $id
     * @return bgerp_iface_DealResponse
     * @see bgerp_DealIntf::getDealInfo()
     */
    public function getDealInfo($id)
    {
    	$rec = $this->fetchRec($id);
    	$products = $this->getItems($id, $total);
    	
    	if(!count($products)) return FALSE;
    	
    	/* @var $result bgerp_iface_DealResponse */
        $result = new bgerp_iface_DealResponse();
    	$result->dealType = bgerp_iface_DealResponse::TYPE_SALE;
        
        $result->quoted->amount                  = $total;
        $result->quoted->currency                = $rec->currencyId;
        $result->quoted->rate 					 = $rec->currencyRate;
        $result->quoted->vatType 				 = $rec->chargeVat;  
        if($rec->deliveryPlaceId){
        	$result->quoted->delivery->location  = crm_Locations::fetchField("#title = '{$rec->deliveryPlaceId}'", 'id');
        }
        $result->quoted->delivery->term          = $rec->deliveryTermId;
    	$result->quoted->payment->method         = $rec->paymentMethodId;
    	
    	$result->quoted->products = $products;
        
        return $result;
    }
    
    
    /**
     * Помощна ф-я за връщане на всички продукти от офертата.
     * Ако има вариации на даден продукт и не може да се
     * изчисли общата сума ф-ята връща NULL
     * @param int $id - ид на оферта
     * @param double $total - обща сума на продуктите
     */
    private function getItems($id, &$total = 0)
    {
    	$query = $this->sales_QuotationsDetails->getQuery();
    	$query->where("#quotationId = {$id} AND #optional = 'no'");
    	$total = 0;
    	$products = array();
    	while($detail = $query->fetch()){
    		$uIndex =  "{$detail->productId}|{$detail->policyId}";
    		if(array_key_exists($uIndex, $products) || !$detail->quantity) return NULL;
    		$total += $detail->quantity * ($detail->price * (1 + $detail->discount));
    		$products[$uIndex] = $detail;
    	}
    	
    	return array_values($products);
    }
    
    
    /**
     * Интерфейсен метод (@see doc_ContragentDataIntf::getContragentData)
     */
	static function getContragentData($id)
    {
        //Вземаме данните от визитката
        $rec = static::fetch($id);
        if(!$rec) return;
        
        $contrData = new stdClass();
        $contrData->company = $rec->company;
         
        //Заместваме и връщаме данните
        if (!$rec->person) {
        	$contrData->companyId = $rec->contragentId;
            $contrData->tel = $rec->tel;
            $contrData->fax = $rec->fax;
            $contrData->pCode = $rec->pCode;
            $contrData->place = $rec->place;
            $contrData->address = $rec->address;
            $contrData->email = $rec->email;
        } else {
        	$contrData->person = $rec->person;
            $contrData->pTel = $rec->tel;
            $contrData->pFax = $rec->fax;
            $contrData->pCode = $rec->pCode;
            $contrData->place = $rec->place;
            $contrData->pAddress = $rec->address;
            $contrData->pEmail = $rec->email;
        }
        
        return $contrData;
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
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
    	$tplArr[] = array('name' => 'Оферта нормален изглед', 'content' => 'sales/tpl/QuotationHeaderNormal.shtml', 'lang' => 'bg');
    	$tplArr[] = array('name' => 'Оферта изглед за писмо', 'content' => 'sales/tpl/QuotationHeaderLetter.shtml', 'lang' => 'bg');
    	
    	$skipped = $added = $updated = 0;
    	foreach ($tplArr as $arr){
    		$arr['docClassId'] = $mvc->getClassId();
    		doc_TplManager::addOnce($arr, $added, $updated, $skipped);
    	}
    	
    	$res .= "<li><font color='green'>Добавени са {$added} шаблона за оферти, обновени са {$updated}, пропуснати са {$skipped}</font></li>";
    }
    
    
     /**
      * Добавя ключови думи за пълнотекстово търсене, това са името на
      * документа или папката
      */
     function on_AfterGetSearchKeywords($mvc, &$res, $rec)
     {
     	// Тук ще генерираме всички ключови думи
     	$detailsKeywords = '';

     	// заявка към детайлите
     	$query = sales_QuotationsDetails::getQuery();
     	// точно на тази фактура детайлите търсим
     	$query->where("#quotationId  = '{$rec->id}'");
     	
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
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
        $rec = (is_object($rec)) ? $rec : static::fetch($rec);
    	
    	return tr("|Оферта|* №{$rec->id}");
    }
}
