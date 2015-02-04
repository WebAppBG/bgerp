<?php
/**
 * Клас 'store_ShipmentOrderDetails'
 *
 * Детайли на мениджър на експедиционни нареждания (@see store_ShipmentOrders)
 *
 * @category  bgerp
 * @package   store
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_ShipmentOrderDetails extends deals_DeliveryDocumentDetail
{
    /**
     * Заглавие
     * 
     * @var string
     */
    public $title = 'Детайли на ЕН';


    /**
     * Заглавие в единствено число
     *
     * @var string
     */
    public $singleTitle = 'Продукт';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'shipmentId';
    
    
    /**
     * Плъгини за зареждане
     * 
     * var string|array
     */
    public $loadList = 'plg_RowTools, plg_Created, store_Wrapper, plg_RowNumbering, plg_SaveAndNew, 
                        plg_AlignDecimals2 , doc_plg_TplManagerDetail, LastPricePolicy=sales_SalesLastPricePolicy, ReversePolicy=purchase_PurchaseLastPricePolicy';
    
    
    /**
     * Активен таб на менюто
     * 
     * @var string
     */
    public $menuPage = 'Логистика:Складове';
    
    
    /**
     * Кой има право да чете?
     * 
     * @var string|array
     */
    public $canRead = 'ceo, store';
    
    
    /**
     * Кой има право да променя?
     * 
     * @var string|array
     */
    public $canEdit = 'ceo, store';
    
    
    /**
     * Кой има право да добавя?
     * 
     * @var string|array
     */
    public $canAdd = 'ceo, store';
    
    
    /**
     * Кой може да го види?
     * 
     * @var string|array
     */
    public $canView = 'ceo, store';
    
    
    /**
     * Кой може да го изтрие?
     * 
     * @var string|array
     */
    public $canDelete = 'ceo, store';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'info, productId, packagingId, uomId, packQuantity, packPrice, discount, amount, weight, volume';
    
        
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
    	$this->FLD('shipmentId', 'key(mvc=store_ShipmentOrders)', 'column=none,notNull,silent,hidden,mandatory');
    	parent::setDocumentFields($this);
    	$this->FLD('packagingId', 'key(mvc=cat_Packagings, select=name, allowEmpty)', 'caption=Мярка,after=productId');
    	
        $this->FLD('weight', 'cat_type_Weight', 'input=hidden,caption=Тегло');
        $this->FLD('volume', 'cat_type_Volume', 'input=hidden,caption=Обем');
        $this->FLD('info', "varchar(50)", 'caption=Колети');
    }


    /**
     * Достъпните продукти
     */
    protected function getProducts($ProductManager, $masterRec)
    {
    	$property = ($masterRec->isReverse == 'yes') ? 'canBuy' : 'canSell';
    	
    	// Намираме всички продаваеми продукти, и оттях оставяме само складируемите за избор
    	$products = $ProductManager->getProducts($masterRec->contragentClassId, $masterRec->contragentId, $masterRec->date, $property);
    	$products2 = $ProductManager::getByProperty('canStore');
    	$products = array_intersect_key($products, $products2);
    	
    	return $products;
    }
    
    
	/**
     * След извличане на записите от базата данни
     */
    public static function on_AfterPrepareListRecs(core_Mvc $mvc, $data)
    {
        // Преброява броя на колетите, само ако се показва тази информация
        if(isset($data->listFields['info'])){
        	$data->masterData->row->colletsCount = cls::get('type_Int')->toVerbal($mvc->countCollets($data->recs));
        }
    }
    
    
    /**
     * Преброява общия брой на колетите
     * @param array $recs - записите от модела
     */
    private function countCollets($recs)
    {
    	$count = 0;
    	
    	if(!count($recs)) return 0;
    	
    	foreach ($recs as $rec){
    		
    		// За всяка информация за колети
    		if($rec->info){
    			
    			// Разбиване на записа
    			$info = explode(',', $rec->info);
	    		foreach ($info as &$seq){
	    			
	    			// Ако е посочен интервал от рода 1-5
	    			$seq = explode('-', $seq);
	    			if(count($seq) == 1){
	    				
	    				// Ако няма такова разбиване, се увеличава броя
	    				$count += 1;
	    			} else {
	    				
	    				// Ако е посочен интервал, броя се увеличава с разликата
	    				$count += $seq[1] - $seq[0] +1;
	    			}
	    		}
    		}
    	}
    	
    	// Връщане на броя на колетите
    	return $count;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form &$form)
    { 
    	$rec = &$form->rec;
    	$masterStore = $mvc->Master->fetch($rec->{$mvc->masterKey})->storeId;
    	 
    	if(isset($rec->productId)){
    		if(isset($masterStore)){
    			$storeInfo = deals_Helper::getProductQuantityInStoreInfo($rec->productId, $rec->classId, $masterStore);
    			$form->info = $storeInfo->formInfo;
    		}
    	}
    	
    	parent::inputDocForm($mvc, $form);
    	
    	if ($form->isSubmitted() && !$form->gotErrors()) {
            
            // Извличане на информация за продукта - количество в опаковка, единична цена
            $rec = $form->rec;
            
            if(isset($storeInfo)){
            	if($rec->quantity > $storeInfo->quantity){
            		$form->setWarning('packQuantity', 'Въведеното количество е по-голямо от наличното в склада');
            	}
            }
            
            if($rec->info){
            	if(!preg_match('/^[0-9]+[\ \,\-0-9]*$/', $rec->info, $matches)){
            		$form->setError('info', "Полето може да приема само числа,запетаи и тирета");
            	}
            	
            	$rec->info = preg_replace("/\s+/", "", $rec->info);
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterPrepareListRows($mvc, &$data)
    {
    	$rows = &$data->rows;
    	
    	if(!count($data->recs)) return;
    	
    	$storeId = $data->masterData->rec->storeId;
    	foreach ($rows as $id => $row){
    		$rec = $data->recs[$id];
    		$quantityInStore = store_Products::fetchField("#productId = {$rec->productId} AND #classId = {$rec->classId} AND #storeId = {$storeId}", 'quantity');
    		if(($quantityInStore - $rec->quantity) < 0){
    			$row->ROW_ATTR['class'] .= ' row-negative';
    		}
    	}
    }
}