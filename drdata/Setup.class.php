<?php

/**
 * Хост по подразбиране за изпращач
 */
defIfNot("SENDER_HOST", "localhost");


/**
 * Стандартен и-мейл на изпращача
 */
defIfNot("SENDER_EMAIL", 'team@example.com');


/**
 * Стандартен и-мейл на изпращача
 */
defIfNot("COUNTRY_PHONE_CODE", '359');


/**
 * Избягвани под-стрингове при парсиране на вход. писма
 */
defIfNot("DRDATA_AVOID_IN_EXT_ADDRESS", '');


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
class drdata_Setup extends core_ProtoSetup
{
    
    
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
            'SENDER_HOST'   => array ('identifier', 'mandatory, caption=Настойки на проверителя на имейл адреси->Хост'),
            'SENDER_EMAIL'  => array ('email', 'mandatory, caption=Настойки на проверителя на имейл адреси->`От` имейл'),
            'COUNTRY_PHONE_CODE'  => array ('int', 'mandatory, caption=Код на държава по подразбиране->Код'),
            'DRDATA_AVOID_IN_EXT_ADDRESS' => array('text', 'caption=Избягвани под-стрингове при парсиране на вход. писма->Стрингове'),
        );

        
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'drdata_Countries',
            'drdata_IpToCountry',
            'drdata_DialCodes',
            'drdata_Vats',
            'drdata_Domains',
    		'drdata_Languages',
        	
        
        );
    
    
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