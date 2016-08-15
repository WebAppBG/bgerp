<?php
/**
 * Клас 'sales_ServicesDetails'
 *
 * Детайли на мениджър на предавателните протоколи
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_ServicesDetails extends deals_DeliveryDocumentDetail
{
    /**
     * Заглавие
     */
    public $title = 'Детайли на предавателния протокол';


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
    public $loadList = 'plg_RowTools, plg_Created, sales_Wrapper, plg_RowNumbering, plg_SaveAndNew, 
                        plg_AlignDecimals2, plg_Sorting, doc_plg_HidePrices, LastPricePolicy=sales_SalesLastPricePolicy, ReversePolicy=purchase_PurchaseLastPricePolicy';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, sales';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, sales';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, sales';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, sales';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId=Мярка, packQuantity, packPrice, discount, amount,quantityInPack';
    
        
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
        $this->FLD('shipmentId', 'key(mvc=sales_Services)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('expenseItemId', 'acc_type_Item(select=titleNum,allowEmpty,lists=600,allowEmpty)', 'input=none,after=productId,caption=Разход за');
        parent::setDocumentFields($this);
        $this->FLD('showMode', 'enum(auto=По подразбиране,detailed=Разширен,short=Съкратен)', 'caption=Изглед,notNull,default=short,value=short');
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
    		if($pRec->canConvert == 'no' && $pRec->fixedAsset == 'no' && $data->masterRec->isReverse == 'yes' && acc_Lists::getItemsCountInList('costObjects') > 1){
    			$form->setField('expenseItemId', 'input');
    		}
    	}
    }
    
    
    /**
     * Достъпните продукти
     */
    protected function getProducts($masterRec)
    {
    	$property = ($masterRec->isReverse == 'yes') ? 'canBuy' : 'canSell';
    	
    	// Намираме всички продаваеми продукти, и оттях оставяме само складируемите за избор
    	$products = cat_Products::getProducts($masterRec->contragentClassId, $masterRec->contragentId, $masterRec->date, $property, 'canStore');
    	
    	return $products;
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
    	core_Lg::push($data->masterData->rec->tplLang);
    	$date = ($data->masterData->rec->state == 'draft') ? NULL : $data->masterData->rec->modifiedOn;
    	
    	if(count($data->rows)) {
    		foreach ($data->rows as $i => &$row) {
    			$rec = &$data->recs[$i];

                $row->productId = cat_Products::getAutoProductDesc($rec->productId, $date, $rec->showMode, 'public', $data->masterData->rec->tplLang);
                if($rec->notes){
    				$row->productId .= "<div class='small'>{$mvc->getFieldType('notes')->toVerbal($rec->notes)}</div>";
    			}
    		}
    	}
    	
    	core_Lg::pop();
    }
}