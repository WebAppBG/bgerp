<?php



/**
 * Видове палети
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_PalletTypes extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Видове палети';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_LastUsedKeys, store_Wrapper, plg_RowTools, store_PalletteWrapper';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,store';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,store';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,store';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,store';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,store';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,store';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,store';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,title,width=Широчина,depth=Дълбочина,height=Височина,maxWeight=Макс. тегло,tools=Пулт';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('title', 'varchar(16)', 'caption=Заглавие,mandatory');
        $this->FLD('width', 'double(decimals=2)', 'caption=Палет->Широчина [м]');
        $this->FLD('depth', 'double(decimals=2)', 'caption=Палет->Дълбочина [м]');
        $this->FLD('height', 'double(decimals=2)', 'caption=Палет->Височина [м]');
        $this->FLD('maxWeight', 'double(decimals=2)', 'caption=Палет->Тегло [kg]');
    }
}