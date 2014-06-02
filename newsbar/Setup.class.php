<?php



/**
 * class newsbar_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с пакета за новини
 *
 *
 * @category  bgerp
 * @package   neswbar
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class newsbar_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'newsbar_News';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Новини";

    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
   var $managers = array(
            'newsbar_News',
  
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'newsbar';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.99, 'Сайт', 'Нюзбар', 'newsbar_News', 'list', "cms, newsbar, admin, ceo"),
        );

        
    /**
     * Инсталиране на пакета
     */
    function install()
    {  
    	$html = parent::install(); 
    	 
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('newsBar', 'Прикачени файлове в новини', 'png,gif,ico,bmp,jpg,jpeg,image/*', '1MB', 'user', 'newsbar');
        
               
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме клавиатурата към password полета
        $html .= $Plugins->installPlugin('Лента с Новини', 'newsbar_Plugin', 'cms_Page', 'private');  
               
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }

}