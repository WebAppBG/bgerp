<?php



/**
 * Мениджър за "Продуктови Категории" 
 *
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class pos_FavouritesCategories extends core_Manager {
    
    /**
     * Заглавие
     */
    var $title = "Продуктови категории";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, plg_Printing,
    				 pos_Wrapper, pos_FavouritesWrapper';

    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт, name, createdOn, createdBy';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
	
	/**
     * Кой може да го прочете?
     */
    var $canRead = 'admin, pos';
    
    
    /**
     * Кой може да променя?
     */
    var $canAdd = 'admin, pos';
    
    
    /**
     * Кой може да променя?
     */
    var $canEdit = 'pos, admin';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'admin, pos';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('name', 'varchar(64)', 'caption=Име, mandatory,width=19em');
        
        $this->setDbUnique('name');
    }
    
    
    /**
     * Връща всички продуктови категории
     * @return array $categories - Масив от всички категории
     */
    public static function prepareAll()
    {
    	$categories = array();
    	$varchar = cls::get('type_Varchar');
    	$categories[0] = (object)array('id'=>'', 'name' => tr('Всички'));
    	$query = static::getQuery();
    	while($rec = $query->fetch()) {
    		$rec->name = $varchar->toVerbal($rec->name);
    		$categories[$rec->id] = (object)array('id' => $rec->id, 'name' => $rec->name);
    	}
    	
    	return $categories;
    }
}