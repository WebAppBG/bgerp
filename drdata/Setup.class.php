<?php

/**
 * Хост по подразбиране за изпращач
 */
defIfNot("SENDER_HOST", "localhost");


/**
 * Стандартен и-мейл на изпращача
 */
defIfNot("SENDER_EMAIL", '??????');


/**
 * Стандартен и-мейл на изпращача
 */
defIfNot("COUNTRY_PHONE_CODE", '359');


/**
 * class drdata_Setup
 *
 * Инсталиране/Деинсталиране на
 * доктор за адресни данни
 *
 *
 * @category  vendors
 * @package   drdata
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class drdata_Setup extends core_Manager {
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.15';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'drdata_Countries';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Готови данни и типове от различни области";
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
            'SENDER_HOST'   => array ('identifier', 'mandatory'),
            'SENDER_EMAIL'  => array ('email', 'mandatory'),
            'COUNTRY_PHONE_CODE'  => array ('int', 'mandatory'),
        );
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        
        $managers = array(
            'drdata_Countries',
            'drdata_IpToCountry',
            'drdata_DialCodes',
            'drdata_Vats',
            'drdata_Domains',
        	
        
        );
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        return "Пакета drdata е разкачен";
    }
}