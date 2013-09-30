<?php

/**
 * class cash_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъра Case
 *
 *
 * @category  bgerp
 * @package   cash
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cash_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'cash_Cases';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'drdata=0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Каси, кешови операции и справки";
    
	
	/**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'cash_Cases',
        	'cash_Pko',
        	'cash_Rko',
        	'cash_InternalMoneyTransfer',
        	'cash_ExchangeDocument',
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'cash';

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(2.2, 'Финанси', 'Каси', 'cash_Cases', 'default', "cash, ceo"),
        );

        
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $html = parent::install();
        
        // Добавяне на роля за старши касиер
        $html .= core_Roles::addRole('cashMaster', 'cash') ? "<li style='color:green'>Добавена е роля <b>cashMaster</b></li>" : '';
    	
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