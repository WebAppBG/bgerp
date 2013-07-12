<?php


/**
 * Инсталиране/Деинсталиране на плъгини свързани с hljs
 *
 * @category  vendors
 * @package   hljs
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hljs_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
        
    
    /**
     * Описание на модула
     */
    var $info = "Оцветяване на код";
    

    /**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
    	
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина за конвертиране от highlight
        $html .= $Plugins->installPlugin('Highlight', 'hljs_RichTextPlg', 'type_Richtext', 'private');
        
        return $html;
    }

	
	/**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
    	$html = parent::deonstall();
    	
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Деинсталираме highlight конвертора
        if($delCnt = $Plugins->deinstallPlugin('hljs_RichTextPlg')) {
            $html .= "<li>Премахнати са {$delCnt} закачания на 'type_Richtext'";
        } else {
            $html .= "<li>Не са премахнати закачания на плъгина";
        }
        
        return $html;
    }
}