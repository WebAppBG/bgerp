<?php



/**
 * Клас 'page_Empty' - Шаблон за празна страница
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
class page_Empty extends page_Html
{
    
    
    /**
     * Конструктор
     */
    function page_Empty()
    {
        $this->page_Html();
        $this->replace("UTF-8", 'ENCODING');
        $this->push('css/common.css', 'CSS');
        $this->push('js/efCommon.js', 'JS');
    }
}
