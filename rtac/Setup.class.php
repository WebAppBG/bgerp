<?php


/**
 * Версия на YUKU textcomplete
 */
defIfNot('RTAC_YUKU_VERSION', '0.2.4');


/**
 * Класа, който да се използва за autocomplete
 */
defIfNot('RTAC_AUTOCOMPLETE_CLASS', 'rtac_yuku_Textcomplete');


/**
 * Максималният брой елементи, които ще се показват за autocomplete
 */
defIfNot('RTAC_MAX_SHOW_COUNT', 6);


/**
 * Роли, които трябва да има потребителя, за да се покаже в autocomplete
 */
defIfNot('RTAC_DEFAUL_SHARE_USER_ROLES', 'powerUser');


/**
 * Роли, от които трябва да има потребителя, за да може да ползва autocompletе-a за споделяне
 */
defIfNot('RTAC_DEFAUL_USER_ROLES_FOR_SHARE', 'powerUser');


/**
 * 
 * 
 * @category  vendors
 * @package   rtac
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class rtac_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
        
    
    /**
     * Описание на модула
     */
    var $info = "Autocomplete за ричтекст";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
        'rtac_yuku_Textcomplete',
    );
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
       'RTAC_AUTOCOMPLETE_CLASS' => array ('class(interface=rtac_AutocompleteIntf, select=title)', 'caption=Клас за autocomplete->Клас'),
       'RTAC_YUKU_VERSION' => array ('enum(0.2.4)', 'caption=Версия на YUKU->Версия'),
       'RTAC_MAX_SHOW_COUNT' => array ('int', 'caption=Максималният брой елементи|*&comma;| които ще се показват за autocomplete->Брой'),
       'RTAC_DEFAUL_SHARE_USER_ROLES' => array ('varchar', 'caption=Роли|*&comma;| които трябва да има потребителя|*&comma;| за да се покаже в autocomplete->Роли'),
       'RTAC_DEFAUL_USER_ROLES_FOR_SHARE' => array ('varchar', 'caption=Роли|*&comma;| от които трябва да има потребителя|*&comma;| за да може да ползва autocompletе-a за споделяне->Роли'),
     );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
    	
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина за конвертиране от highlight
        $html .= $Plugins->installPlugin('Richtext autocomplete', 'rtac_Plugin', 'type_Richtext', 'private');
        
        return $html;
    }
}
