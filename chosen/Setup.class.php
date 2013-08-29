<?php

/**
 * Минималния брой елементи, за които няма да сработи Chosen
 */
defIfNot('CHOSEN_MIN_ITEMS', 30);


/**
 * Клас 'chosen_Setup' - Предава по добър изглед на keylist полетата
 *
 *
 * @category  vendors
 * @package   chosen
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link      http://harvesthq.github.com/chosen/
 */
class chosen_Setup extends core_ProtoSetup {
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = '';
    
    
    /**
     * Описание на модула
     */
    var $info = "Удобно избиране от множества";
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
    
            // Минималния брой елементи, за които няма да сработи Chosen
            'CHOSEN_MIN_ITEMS' => array ('int', 'caption=Минимален брой опции за да сработи Chosen->Опции, suggestions=10|20|30|40|50'),
    
        );
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
    	
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме
        $html .= $Plugins->forcePlugin('Chosen', 'chosen_Plugin', 'type_Keylist', 'private');
        $html .= $Plugins->forcePlugin('ChosenSelect', 'chosen_PluginSelect', 'type_Key', 'private');
        $html .= $Plugins->forcePlugin('ChosenSelectUser', 'chosen_PluginSelect', 'type_User', 'private');
        $html .= $Plugins->forcePlugin('ChosenSelectUsers', 'chosen_PluginSelect', 'type_Users', 'private');

        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
    	$html = parent::deinstall();
    	
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Премахваме от type_Keylist полета
        $Plugins->deinstallPlugin('chosen_Plugin');
        $html .= "<li>Премахнати са всички инсталации на 'chosen_Plugin'";
        
        // Премахваме от type_Key полета
        $Plugins->deinstallPlugin('chosen_PluginSelect');
        $html .= "<li>Премахнати са всички инсталации на 'chosen_PluginSelect'";
        
        return $html;
    }
}