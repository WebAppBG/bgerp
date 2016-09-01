<?php


/**
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с продуктите
 * 
 * 
 * @category  bgerp
 * @package   spcheck
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class spcheck_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версията на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'spcheck_Dictionary';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Проверка на правопис";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
        'spcheck_Dictionary',
    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.87, 'Производство', 'Речник', 'spcheck_Dictionary', 'default', "powerUser")
        );
    
    
    /**
     * Инсталиране на пакета
     */
    function checkConfig()
    {
        $modulName = 'pspell';
        
        $activePhpModules = get_loaded_extensions();
        
        if (!in_array($modulName, $activePhpModules)) {
            
            return "Не е инсталиран PHP модулът '{$modulName}'";
        }
    }
    
    
    /**
     * Скрипт за инсталиране
     */
    function install()
    {
        $html = parent::install();
        
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме на плъгина за проверка на правописа
        $html .= $Plugins->forcePlugin('Spell Check', 'spcheck_Plugin', 'core_Master', 'family');
        
        return $html;
    }
}
