<?php


/**
 * След колко време (в секунди) след първото изпращане към един имейл да се взема в предвид, че е изпратено преди (Повторно изпращане) 
 * 
 * По подразбиране 12 часа
 */
defIfNot('LOG_EMAIL_RESENDING_TIME', '43200');


/**
 * Исторически данни за одит и обратна връзка
 *
 *
 * @category  bgerp
 * @package   log
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class log_Setup
{
    
    
    /**
     * Версията на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'log_Documents';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "История";
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
    	'LOG_EMAIL_RESENDING_TIME' => array ('int'),
        );

    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        
        // Инсталиране на мениджърите
        $managers = array(
            'log_Documents',
            'log_Files',
        );
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(1.25, 'Документи', 'История', 'log_Documents', 'default', "admin");
        
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