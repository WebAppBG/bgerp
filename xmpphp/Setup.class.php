<?php


/**
 * Адрес на xmpp чат сървър
 */
defIfNot('XMPPHP_SERVER', 'talk.google.com');

/**
 * Порт за връзка
 */
defIfNot('XMPPHP_PORT', '5222');

/**
 * Име на потребител
 */
defIfNot('XMPPHP_USER', '');

/**
 * Парола
 */
defIfNot('XMPPHP_PASSWORD', '');

/**
 * Домейн 
 */
defIfNot('XMPPHP_DOMAIN', 'gmail.com');


/**
 * class xmpphp_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани със чат съобщенията
 *
 *
 * @category  vendors
 * @package   xmpphp
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class xmpphp_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'xmpphp_Sender';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "XMPP известяване";


    /**
     * Необходими пакети
     */
    var $depends = '';
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
        
               
           'XMPPHP_SERVER'   => array ('varchar', 'caption=XMPP чат сървър->URL адрес'),
    
           'XMPPHP_PORT'   => array ('int', 'caption=XMPP чат сървър->Порт'),
     
           'XMPPHP_DOMAIN'   => array ('varchar', 'caption=XMPP чат сървър->Домейн'),
    
           'XMPPHP_USER'   => array ('identifier', 'mandatory, class=w25,caption=Сметка->Ник'),
    
           'XMPPHP_PASSWORD'   => array ('password', 'mandatory, caption=Сметка->Парола')
    
    
        );
    
        
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'xmpphp_Sender'
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