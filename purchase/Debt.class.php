<?php



/**
 * Мениджър на задължения по покупки
 *
 *
 * @category  bgerp
 * @package   purchase
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Задължения по покупки
 */
class purchase_Debt extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Задължения по покупки';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Created, plg_Rejected, plg_State2, plg_SaveAndNew, 
                    purchase_Wrapper';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,purchase';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin,purchase';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin,purchase';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'admin,purchase';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin,purchase';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    }
    
    
    /**
     * Екшън по подразбиране.
     * Извежда картинка, че страницата е в процес на разработка
     */
    function act_Default()
    {
    	$text = tr('В процес на разработка');
    	$underConstructionImg = "<h2>$text</h2><img src=". sbf('img/under_construction.png') .">";

        return $this->renderWrapping($underConstructionImg);
    }
}