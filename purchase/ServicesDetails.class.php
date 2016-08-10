<?php



/**
 * Клас 'purchase_ServicesDetails'
 *
 * Детайли на мениджър на приемателния протокол
 *
 * @category  bgerp
 * @package   purchase
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class purchase_ServicesDetails extends deals_DeliveryDocumentDetail
{
	
	
    /**
     * Заглавие
     */
    public $title = 'Детайли на приемателния протокол';


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
    public $loadList = 'plg_RowTools, plg_Created, purchase_Wrapper, plg_RowNumbering, plg_SaveAndNew,
                        plg_AlignDecimals2, plg_Sorting, doc_plg_HidePrices,ReverseLastPricePolicy=sales_SalesLastPricePolicy, Policy=purchase_PurchaseLastPricePolicy';
    
    
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
    public $listFields = 'productId, packagingId=Мярка, packQuantity, packPrice, discount, amount, quantityInPack';
    
        
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
    	$this->FLD('expenseItemId', 'acc_type_Item(select=titleNum,allowEmpty,lists=600,allowEmpty)', 'input=none,after=productId,caption=Разход за');
    	parent::setDocumentFields($this);
    }
    
    
    /**
     * Достъпните продукти
     */
    protected function getProducts($masterRec)
    {
    	$property = ($masterRec->isReverse == 'yes') ? 'canSell' : 'canBuy';
    	 
    	// Намираме всички продаваеми продукти, и оттях оставяме само складируемите за избор
    	$products = cat_Products::getProducts($masterRec->contragentClassId, $masterRec->contragentId, $masterRec->date, $property, 'canStore');
    	
    	return $products;
    }


    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm(core_Mvc $mvc, &$data)
    {
    	$form = &$data->form;
    	$rec = &$form->rec;
    	
    	// Ако е избран артикул и той е невложим и имаме разходни пера,
    	// показваме полето за избор на разход
    	if(isset($rec->productId)){
    		$pRec = cat_Products::fetch($rec->productId, 'canConvert,fixedAsset');
    		if($pRec->canConvert == 'no' && $pRec->fixedAsset == 'no' && $data->masterRec->isReverse != 'yes' && acc_Lists::getItemsCountInList('costObjects') > 1){
    			$form->setField('expenseItemId', 'input');
    		}
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form &$form)
    {
    	parent::inputDocForm($mvc, $form);
    }
    
    
    /**
     * След обработка на записите от базата данни
     */
    public static function on_AfterPrepareListRows(core_Mvc $mvc, $data)
    {
    	$date = ($data->masterData->rec->state == 'draft') ? NULL : $data->masterData->rec->modifiedOn;
    	
    	if(count($data->rows)) {
    		foreach ($data->rows as $i => &$row) {
    			$rec = &$data->recs[$i];
    
    			$row->productId = cat_Products::getAutoProductDesc($rec->productId, $date, 'title', 'public', $data->masterData->rec->tplLang);
    			$row->productId .= acc_ExpenseAllocations::displayExpenseItemId($rec->expenseItemId, $rec->productId);
    			
    			if($rec->notes){
    				$row->productId .= "<div class='small'>{$mvc->getFieldType('notes')->toVerbal($rec->notes)}</div>";
    			}
    		}
    	}
    }
}