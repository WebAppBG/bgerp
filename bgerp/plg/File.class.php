<?php


/**
 * Плъгин за работа с файлове
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_plg_File extends core_Plugin
{
    
    
    /**
     * Прихващаме генерирането на имейл.
     * Ако мода е xhtml, тогава сработва и прекъсва по нататъшното изпълнение на функцията.
     */
    static function on_BeforeGenerateUrl($mvc, &$res, $fh, $isAbsolute)
    {
        $mode = Mode::get('text');
        
        // Ако не се изпраща имейла, да не сработва
        if ($mode != 'xhtml' && $mode != 'plain') return ;
        
        // Действието
        $action = log_Documents::getAction();

        // Ако не изпращаме имейла, да не сработва
//        if ((!$action) || in_array($action->action, array(log_Documents::ACTION_DISPLAY, log_Documents::ACTION_RECEIVE, log_Documents::ACTION_RETURN))) return ;
        if (!$action) return ;
        
        // Името на файла
        $name = fileman_Files::fetchByFh($fh, 'name');

        //Генерираме връзката 
        $res = toUrl(array('F', 'S', doc_DocumentPlg::getMidPlace(), 'n' => $name), $isAbsolute);

        return FALSE;
    }
}