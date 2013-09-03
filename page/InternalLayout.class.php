<?php



/**
 * Клас 'page_Layout' - Лейаута на страница от приложението
 *
 * Файлът може да се подмени с друг
 *
 *
 * @category  ef
 * @package   page
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class page_InternalLayout extends core_ET
{
    
    
    /**
     * Конструктор на шаблона
     */
    function page_InternalLayout()
    {
         // Задаваме лейаута на страницата
        $this->core_ET("<div id='main-container' class='clearfix21 main-container [#HAS_SCROLL_SUPPORT#]'><div id=\"framecontentTop\"  class=\"container\">" .
            "[#bgerp_Menu::renderMenu#]" .
            "</div>" .
            "<div id=\"maincontent\"><div>" .
            "<!--ET_BEGIN NAV_BAR--><div id=\"navBar\">[#NAV_BAR#]</div>\n<!--ET_END NAV_BAR--><div class='clearfix' style='min-height:10px;'></div>" .
            " <!--ET_BEGIN STATUSES-->[#STATUSES#]<!--ET_END STATUSES-->" .
            "[#PAGE_CONTENT#]" .
            "</div></div>" .
            "<div id=\"framecontentBottom\" class=\"container\">" .
            "[#PAGE_FOOTER#]" .
            "</div></div>");

        if(Mode::get("checkNativeSupport")){
        	$this->replace('narrow-scroll', "HAS_SCROLL_SUPPORT");
        }
        
        // Опаковките и главното съдържание заемат екрана до долу
        $this->append("runOnLoad(setMinHeight);", "JQRUN");
    }
            

}