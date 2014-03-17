<?php

/**
 * Тема по подразбиране
 */
defIfNot('CMS_THEME', 'cms_DefaultTheme');


/**
 * Текущ език
 */
defIfNot('EF_DEFAULT_LANGUAGE', 'bg');

/**
 * Основен език на публичната част
 */
defIfNot('CMS_BASE_LANG', EF_DEFAULT_LANGUAGE);


/**
 * Допълнителни езици публичната част
 */
defIfNot('CMS_LANGS', '');


/**
 * Колко секунди да се кешира съдържанието за не PowerUsers
 */
defIfNot('CMS_BROWSER_CACHE_EXPIRES', 3600);


/**
 * допълнителен текст при копиране
 */
defIfNot('CMS_COPY_DEFAULT_TEXT', 'Виж още на');


/**
 * Добавка при копиране изключване за определени роли
 */
defIfNot('CMS_COPY_DISABLE_FOR', '');


/**
 * Изображение което ще се показва в Ографа
 */

defIfNot('CMS_OGRAPH_IMAGE', '');


/**
 * class cms_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с продуктите
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версията на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'cms_Content';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Управление на публичното съдържание";
    
    
    /**
	 * Описание на конфигурационните константи
	 */
	var $configDescription = array(
            'CMS_BASE_LANG' => array ('customKey(mvc=drdata_Languages,select=languageName, key=code)', 'caption=Езици за публичното съдържание->Основен'),

            'CMS_LANGS' => array ('keylist(mvc=drdata_Languages,select=languageName)', 'caption=Езици за публичното съдържание->Допълнителни'),

			'CMS_THEME' => array ('class(interface=cms_ThemeIntf,select=title)', 'caption=Тема по подразбиране->Тема'),

            'CMS_BROWSER_CACHE_EXPIRES' => array ('time', 'caption=Кеширане в браузъра->Време'),
			
            'CMS_COPY_DEFAULT_TEXT' => array ('text(rows=1)', 'caption=Добавка при копиране->Текст,width=100%'),
	
			'CMS_COPY_DISABLE_FOR' => array ('keylist(mvc=core_Roles,select=role)', 'caption=Добавка при копиране->Изключване за'),
			
			'CMS_OGRAPH_IMAGE' => array ('fileman_FileType(bucket=pictures)', 'caption=Изображение за Фейсбук->Изображение'),
	);

	
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'cms_Content',
            'cms_Objects',
            'cms_Articles',
        	'cms_Feeds',
            'cms_Includes',
            'cms_VerbalId',
         );

         
    /**
     * Роли за достъп до модула
     */
    var $roles = 'cms';

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.5, 'Сайт', 'CMS', 'cms_Content', 'default', "cms, ceo, admin"),
        );
 
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $html = parent::install();
        
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('gallery_Pictures', 'Снимки', 'jpg,jpeg,image/jpeg,gif,png', '6MB', 'user', 'every_one');
        
        $disableFor = keylist::addKey('', core_Roles::fetchByName('powerUser'));
        core_Packs::setConfig('cms', array('CMS_COPY_DISABLE_FOR' => $disableFor));
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
     
        // Инсталираме плъгина  
        $html .= $Plugins->forcePlugin('Публична страница', 'cms_PagePlg', 'page_Wrapper', 'private');
        $html .= $Plugins->forcePlugin('Показване на обекти', 'cms_ObjectsInRichtextPlg', 'type_Richtext', 'private');
        $html .= $Plugins->forcePlugin('Копиране с линк към страницата', 'cms_CopyTextPlg', 'cms_Page', 'private');
        
        // Добавяме класа връщащ темата в core_Classes
        core_Classes::add('cms_DefaultTheme');
        
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
