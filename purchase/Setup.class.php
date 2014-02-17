<?php

/**
 * Толеранс за автоматичното затваряне на покупките за доставеното - платеното
 */
defIfNot('PURCHASE_CLOSE_TOLERANCE', '0.01');


/**
 * Покупки до колко дни назад без да са модифицирани да се затварят автоматично
 */
defIfNot('PURCHASE_CLOSE_OLDER_THAN', 60 * 60 * 24 * 3);


/**
 * Колко време да се изчака след активиране на покупка, преди да се провери дали е пресрочена
 */
defIfNot('PURCHASE_OVERDUE_CHECK_DELAY', 60 * 60 * 6);


/**
 * Покупки - инсталиране / деинсталиране
 *
 *
 * @category  bgerp
 * @package   purchase
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class purchase_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'purchase_Purchases';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Покупки - доставки на стоки, материали и консумативи";
    
    
   /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'purchase_Offers',
            'purchase_Purchases',
            'purchase_PurchasesDetails',
    		'purchase_Services',
    		'purchase_ServicesDetails',
    		'purchase_ClosedDeals',
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'purchase';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.3, 'Логистика', 'Доставки', 'purchase_Purchases', 'default', "purchase, ceo"),
        );


    /**
	 * Описание на конфигурационните константи
	 */
	var $configDescription = array(
			'PURCHASE_CLOSE_TOLERANCE'     => array("double(decimals=2)", 'caption=Покупки->Толеранс за приключване'),
			'PURCHASE_OVERDUE_CHECK_DELAY' => array("time", "caption=Покупки->Толеранс за пресрочване"),
			'PURCHASE_CLOSE_OLDER_THAN'    => array("time(uom=days,suggestions=1 ден|2 дена|3 дена)", 'caption=Покупки->Затваряне на по-стари от'),
		);
		
		
	/**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
        
        // Добавяме политиката "По последна покупна цена"
        core_Classes::add('purchase_PurchaseLastPricePolicy');
        
        // Добавяне на роля за старши куповач
        $html .= core_Roles::addRole('purchaseMaster', 'purchase') ? "<li style='color:green'>Добавена е роля <b>purchaseMaster</b></li>" : '';
        
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
