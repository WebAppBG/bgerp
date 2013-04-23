<?php



/**
 * Мениджър на приходи и разходи
 *
 *
 * @category  bgerp
 * @package   budget
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Приходи и Разходи
 */
class budget_IncomeExpenses extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Приходи и Разходи';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Created, plg_SaveAndNew, 
                    budget_Wrapper';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,budget';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin,budget';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin,budget';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'admin,budget';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin,budget';
    
    
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