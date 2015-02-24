<?php



/**
 * Клас 'store_ConsignmentProtocolDetailsReceived'
 *
 * Детайли на мениджър на детайлите на протоколите за отговорни пазене-получени
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_ConsignmentProtocolDetailsReceived extends store_InternalDocumentDetail
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Детайли на протоколите за отговорни пазене-получени';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'върнат артикул';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'protocolId';
    
    
    /**
     * Плъгини за зареждане
     * 
     * var string|array
     */
    public $loadList = 'plg_RowTools, plg_Created, store_Wrapper, plg_RowNumbering, plg_SaveAndNew, 
                        plg_AlignDecimals2, LastPricePolicy=sales_SalesLastPricePolicy';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, store';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, store';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, store';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, store';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, packagingId, packQuantity, packPrice, amount';
    
        
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
	/**
     * Полета свързани с цени
     */
    public $priceFields = 'price, amount, discount, packPrice';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('protocolId', 'key(mvc=store_ConsignmentProtocols)', 'column=none,notNull,silent,hidden,mandatory');
    	parent::setFields($this);
    	$this->setDbUnique('protocolId,productId,packagingId');
    }
    
    
    /**
     * Достъпните продукти
     */
    protected function getProducts($ProductManager, $masterRec)
    {
    	// Намираме всички продаваеми продукти, и оттях оставяме само складируемите за избор
    	$products = $ProductManager->getProducts($masterRec->contragentClassId, $masterRec->contragentId, $masterRec->date, 'canSell,canStore');
    	 
    	return $products;
    }
}