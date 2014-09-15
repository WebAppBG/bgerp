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
class sales_ServicesDetails extends acc_DeliveryDocumentDetail
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
                        plg_AlignDecimals2, doc_plg_HidePrices';
    
    
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
    public $listFields = 'productId, packagingId=Мярка, uomId, packQuantity, packPrice, discount, amount';
    
        
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
        parent::setDocumentFields($this);
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
    	 
    	$products = array_diff_key($products, $products2);
    	
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
}