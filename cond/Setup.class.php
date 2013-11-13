<?php


/**
 * class cond_Setup
 *
 * Инсталиране/Деинсталиране на
 * админ. мениджъри с общо предназначение
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cond_Setup  extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'cond_DeliveryTerms';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'crm=0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Условия на продажба";
        
        
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
        	'cond_PaymentMethods',
        	'cond_DeliveryTerms',
        	'cond_Parameters',
        	'cond_ConditionsToCustomers',
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'cond';

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.9, 'Търговия', 'Терминология', 'cond_DeliveryTerms', 'default', "cond, ceo"),
        );


	/**
     * Инсталиране на пакета
     * @TODO Да се премахне след като кода се разнесе до всички бранчове
     * и старата роля 'salecond' бъде изтрита
     */
    function install()
    {
    	$html = parent::install();
    	
    	// Ако има роля 'salecond'  тя се изтрива (остаряла е)
    	if($roleRec = core_Roles::fetch("#role = 'salecond'")){
    		core_Roles::delete("#role = 'salecond'");
    	}
    	
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