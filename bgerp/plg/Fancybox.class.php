<?php



/**
 * Създава линк към свалянето на картинката в plain режим
 *
 * @category  bgerp
 * @package   plg
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_plg_Fancybox extends core_Plugin
{
    
    
    /**
     * Създава линк към свалянето на картинката в plain режим
     *
     * @param core_Mvc $mvc
     * @param core_Et $resTpl
     * @param string $fh
     * @param integer $thumbSize
     * @param integer $maxSize
     * @param string $baseName
     * @param array $imgAttr
     * @param array $aAttr
     */
    function on_BeforeGetImage($mvc, &$resTpl, $fh, $thumbSize, $maxSize, $baseName = NULL, $imgAttr = array(), $aAttr = array())
    {
        // Да сработва само за plain режим
        if (!Mode::is('text', 'plain')) return ;
        
        // Създава линк към свалянето на картинката
        $resUrl = toUrl(array('F', 'T', doc_DocumentPlg::getMidPlace(), 'n' => $baseName), $imgAttr['isAbsolute'], TRUE, array('n'));
        $resTpl = new ET(tr('Картинка|*: ') . $resUrl);
        
        return FALSE;
    }
}
