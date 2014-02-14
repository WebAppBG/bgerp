<?php
/**
 * Клас 'purchase_ServicesDetails'
 *
 * Детайли на мениджър на протокол за доставка на услуги (@see purchase_ServicesDetails)
 *
 * @category  bgerp
 * @package   purchase
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class purchase_ServicesDetails extends core_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Детайли на протокола за покупка на услуги';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Услуга';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'shipmentId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_Created, purchase_Wrapper, plg_RowNumbering, 
                        plg_AlignDecimals, doc_plg_HidePrices';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, purchase';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, purchase';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, purchase';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, purchase';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId, uomId, packQuantity, packPrice, discount, amount';
    
        
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
	/**
     * Полета свързани с цени
     */
    public $priceFields = 'price,amount,discount,packPrice';
    
    
    /**
     * Полета за скриване/показване от шаблоните
     */
    public $toggleFields = 'packagingId=Опаковка,packQuantity=Количество,packPrice=Цена,discount=Отстъпка,amount=Сума,weight=Обем,volume=Тегло,info=Инфо';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('shipmentId', 'key(mvc=purchase_Services)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('classId', 'class(select=title)', 'caption=Мениджър,silent,input=hidden');
        $this->FLD('productId', 'int(cellAttr=left)', 'caption=Продукт,notNull,mandatory', 'tdClass=large-field');
        $this->FLD('uomId', 'key(mvc=cat_UoM, select=name)', 'caption=Мярка,input=none');
        $this->FLD('packagingId', 'key(mvc=cat_Packagings, select=name, allowEmpty)', 'caption=Мярка/Опак.,input=none');
        $this->FLD('quantity', 'double', 'caption=К-во,input=none');
        $this->FLD('quantityInPack', 'double(decimals=2)', 'input=none,column=none');
        $this->FLD('price', 'double(decimals=2)', 'caption=Цена,input=none');
        $this->FNC('amount', 'double(decimals=2)', 'caption=Сума,input=none');
        $this->FNC('packQuantity', 'double(Min=0,decimals=2)', 'caption=К-во,input=input,mandatory');
        $this->FNC('packPrice', 'double(minDecimals=2)', 'caption=Цена,input=none');
        $this->FLD('discount', 'percent', 'caption=Отстъпка,input=none');
    }


    /**
     * Изчисляване на цена за опаковка на реда
     */
    public function on_CalcPackPrice(core_Mvc $mvc, $rec)
    {
        if (!isset($rec->price) || empty($rec->quantity) || empty($rec->quantityInPack)) {
            return;
        }
    
        $rec->packPrice = $rec->price * $rec->quantityInPack;
    }
    
    
    /**
     * Изчисляване на количеството на реда в брой опаковки
     */
    public function on_CalcPackQuantity(core_Mvc $mvc, $rec)
    {
        if (!isset($rec->price) || empty($rec->quantity) || empty($rec->quantityInPack)) {
            return;
        }
    
        $rec->packQuantity = $rec->quantity / $rec->quantityInPack;
    }
    
    
    /**
     * Изчисляване на сумата на реда
     */
    public function on_CalcAmount(core_Mvc $mvc, $rec)
    {
        if (empty($rec->price) || empty($rec->quantity)) {
            return;
        }
    
        $rec->amount = $rec->price * $rec->quantity;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if(($action == 'edit' || $action == 'delete') && isset($rec)){
        	if($mvc->Master->fetchField($rec->shipmentId, 'state') != 'draft'){
        		$requiredRoles = 'no_one';
        	}
        }
    	
    	if($action == 'add' && isset($rec->shipmentId)){
      		$masterRec = $mvc->Master->fetch($rec->shipmentId);
		    $origin = $mvc->Master->getOrigin($masterRec);
		    $dealAspect = $origin->getAggregateDealInfo()->agreed;
		    $invProducts = $mvc->Master->getDealInfo($rec->shipmentId)->shipped;
    		if($masterRec->state != 'draft' || $masterRec->isFull == 'yes'){
    			$requiredRoles = 'no_one';
    		}
    	}
    }


	/**
     * След извличане на записите от базата данни
     */
    public static function on_AfterPrepareListRecs(core_Mvc $mvc, $data)
    {
        $recs = &$data->recs;
        $orderRec = $data->masterData->rec;
        
        if (empty($recs)) return;
        
        price_Helper::fillRecs($recs, $orderRec);
    }
    
    
    /**
     * След обработка на записите от базата данни
     */
    public function on_AfterPrepareListRows(core_Mvc $mvc, $data)
    {
        $rows = $data->rows;
    	
        // Скриваме полето "мярка"
        $data->listFields = array_diff_key($data->listFields, arr::make('uomId', TRUE));
        
        // Флаг дали има отстъпка
        $haveDiscount = FALSE;
    
        if(count($data->rows)) {
            foreach ($data->rows as $i => &$row) {
            	$rec = &$data->recs[$i];
            	$ProductManager = cls::get($rec->classId);
        		$row->productId = $ProductManager->getTitleById($rec->productId);
        		$haveDiscount = $haveDiscount || !empty($rec->discount);
    			
                if (empty($rec->packagingId)) {
                    $row->packagingId = ($rec->uomId) ? $row->uomId : '???';
                } else {
                    $shortUomName = cat_UoM::getShortName($rec->uomId);
                    $row->quantityInPack = $mvc->fields['quantityInPack']->type->toVerbal($rec->quantityInPack);
                    $row->packagingId .= ' <small class="quiet">' . $row->quantityInPack . '  ' . $shortUomName . '</small>';
                }
            }
        }
    
        if(!$haveDiscount) {
            unset($data->listFields['discount']);
        }
    }
        
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = &$data->form;
    	$origin = purchase_Services::getOrigin($data->masterRec, 'bgerp_DealIntf');
        
        $masterRec = $mvc->Master->fetch($form->rec->shipmentId);
      	expect($origin = $mvc->Master->getOrigin($masterRec));
      	$dealAspect = $origin->getAggregateDealInfo()->agreed;
      	$invProducts = $mvc->Master->getDealInfo($form->rec->shipmentId)->shipped;
      	$form->setOptions('productId', bgerp_iface_DealAspect::buildProductOptions($dealAspect, $invProducts, 'services', $form->rec->productId, $form->rec->classId, $form->rec->packagingId));
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    { 
        if ($form->isSubmitted() && !$form->gotErrors()) {
            
            // Извличане на информация за продукта - количество в опаковка, единична цена
            $rec = $form->rec;
            
            // Извличаме ид на политиката, кодирано в ид-то на продукта 
            list($rec->classId, $rec->productId, $rec->packagingId) = explode('|', $rec->productId);
			$rec->packagingId = ($rec->packagingId) ? $rec->packagingId : NULL;
            
            /* @var $origin bgerp_DealAggregatorIntf */
            $origin = purchase_Services::getOrigin($rec->shipmentId, 'bgerp_DealIntf');
            
            /* @var $dealInfo bgerp_iface_DealResponse */
            $dealInfo = $origin->getAggregateDealInfo();
            
            $aggreedProduct = $dealInfo->agreed->findProduct($rec->productId, $rec->classId, $rec->packagingId);
            
            if (!$aggreedProduct) {
                $form->setError('productId', 'Продуктът не е наличен за експедиция');
                return;
            }
            
            $rec->price = $aggreedProduct->price;
            $rec->uomId = $aggreedProduct->uomId;
            
            if (empty($rec->packagingId)) {
                $rec->quantityInPack = 1;
            } else {
                // Извлича $productInfo, за да определи количеството единици продукт (в осн. мярка) в една опаковка
                $productInfo = cls::get($rec->classId)->getProductInfo($rec->productId, $rec->packagingId);
                $rec->quantityInPack = $productInfo->packagingRec->quantity;
            }
            
            $rec->quantity = $rec->packQuantity * $rec->quantityInPack;
           
            if (empty($rec->discount)) {
                $rec->discount = $aggreedProduct->discount;
            }
        }
    }
    
    
	/**
     * След подготовката на списъчните полета
     */
    function on_AfterPrepareListFields($mvc, $data)
    {
        $showPrices = Request::get('showPrices', 'int');
    	if(Mode::is('printing') && empty($showPrices)) {
            unset($data->listFields['packPrice'], 
            	  $data->listFields['amount'], 
            	  $data->listFields['discount']);
        }
    }
}