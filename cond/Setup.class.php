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
    var $info = "Търговски условия по сделките";
        
        
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
			'cond_Texts',
        	'cond_PaymentMethods',
        	'cond_DeliveryTerms',
        	'cond_Parameters',
        	'cond_ConditionsToCustomers',
    		'cond_Payments',
    		'cond_Countries',
    		'migrate::oldPosPayments',
    		'migrate::removePayment',
    		'migrate::deleteOldPaymentTime1',
    		'migrate::deleteParams2',

        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'cond';

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(1.9, 'Система', 'Дефиниции', 'cond_DeliveryTerms', 'default', "cond, ceo"),
        );


    /**
     * Дефинирани класове, които имат интерфейси
     */
    var $defClasses = "cond_type_Double,cond_type_Text,cond_type_Varchar,cond_type_Time,cond_type_Date,cond_type_Component,cond_type_Enum,cond_type_Set,cond_type_Percent,cond_type_Int,cond_type_Delivery,cond_type_PaymentMethod";
    
    
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

		$Plugins = cls::get('core_Plugins');

		// Замества handle' ите на документите с линк към документа
		$html .= $Plugins->installPlugin('Плъгин за пасажи в RichEdit', 'cond_RichTextPlg', 'type_Richtext', 'private');

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
    
    
    /**
     * Изтриване на стар платежен метод
     */
    public function oldPosPayments()
    {
    	if($id = cond_Payments::fetchField("#title = 'Кеш'", 'id')){
    		cond_Payments::delete($id);
    	}
    }
    
    
    /**
     * Изтриване на стари начини за плащане
     */
    public function removePayment()
    {
    	cond_Payments::delete("#title = 'В брой'");
    }
    
    
    /**
     * Изтрива старите начини на плащания
     */
    function deleteOldPaymentTime1()
    {
    	$paymentClassId = cond_Payments::getClassId();
    	
    	foreach (array('В брой', 'Transcard', 'vaucherCBA', 'vaucherCheck', 'Стая') as $name){
    		cond_Payments::delete("#title = '{$name}'");
    		acc_Items::delete("#classId = '{$paymentClassId}' AND #title='{$name}'");
    	}
    }
    
    
    /**
     * Изтрива параметри
     */
    function deleteParams2()
    {
    	if($f1 = cond_Parameters::fetch("#name = 'Текст за фактура'")){
    		cond_ConditionsToCustomers::delete("#conditionId = {$f1->id}");
    		cond_Parameters::delete($f1->id);
    	}
    	
    	if($f2 = cond_Parameters::fetch("#name = 'Други условия към фактура (английски)'")){
    		cond_ConditionsToCustomers::delete("#conditionId = {$f2->id}");
    		cond_Parameters::delete($f2->id);
    	}
    }
}