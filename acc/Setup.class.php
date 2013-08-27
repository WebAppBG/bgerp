<?php

/**
 * Задаване начало на първия активен счетоводен период
 */
defIfNot('ACC_FIRST_PERIOD_START', '');


/**
 * Стойност по подразбиране на актуалния ДДС (между 0 и 1)
 * Използва се по време на инициализацията на системата, при създаването на първия период
 */
defIfNot('ACC_DEFAULT_VAT_RATE', 0.20);


/**
 * Стойност по подразбиране на актуалния ДДС (между 0 и 1)
 * Използва се по време на инициализацията на системата, при създаването на първия период
 */
defIfNot('BASE_CURRENCY_CODE', 'BGN');


/**
 * class acc_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани със счетоводството
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    

    /**
     * Необходими пакети
     */
    var $depends = 'currency=0.1';
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'acc_Lists';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Двустранно счетоводство: Настройки, Журнали";
    

    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
        'ACC_FIRST_PERIOD_START' => array('date', 'caption=Начало на първия счетоводен период->Дата'), 
        'ACC_DEFAULT_VAT_RATE' => array('percent', 'caption=ДДС по подразбиране->Процент')
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'acc_Lists',
            'acc_Items',
            'acc_Periods',
            'acc_Accounts',
            'acc_Limits',
            'acc_Balances',
            'acc_BalanceDetails',
            'acc_Articles',
            'acc_ArticleDetails',
            'acc_Sales',
            'acc_SaleDetails',
            'acc_Journal',
            'acc_JournalDetails',
        	'acc_Operations',
        );
    

    /**
     * Роли за достъп до модула
     */
    var $roles = 'acc';

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(2.1, 'Счетоводство', 'Книги', 'acc_Balances', 'default', "acc, ceo"),
            array(2.1, 'Счетоводство', 'Настройки', 'acc_Periods', 'default', "acc, ceo"),
        );
 
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $html = parent::install();

        //Данни за работата на cron
        $rec = new stdClass();
        $rec->systemId = 'RecalcBalances';
        $rec->description = 'Преизчисляване на баланси';
        $rec->controller = 'acc_Balances';
        $rec->action = 'Recalc';
        $rec->period = 1;
        $rec->offset = 0;
        $rec->delay = 0;
        $rec->timeLimit = 55;
        
        $Cron = cls::get('core_Cron');
        
        if ($Cron->addOnce($rec)) {
            $html .= "<li><font color='green'>Задаване по крон да преизчислява баланси</font></li>";
        } else {
            $html .= "<li>Отпреди Cron е бил нагласен да преизчислява баланси</li>";
        }

        
        $html .= $this->loadSetupData();

        return $html;
    }


    /**
     * Инициализране на началните данни
     */
    function loadSetupData()
    {
    	$html = parent::loadSetupData();
    	
        $Periods = cls::get('acc_Periods');

        //$html .= $Periods->loadSetupData();

        //Зарежда данни за инициализация от CSV файл за acc_Lists
        $html .= acc_setup_Lists::loadData();
        
        //Зарежда данни за инициализация от CSV файл за acc_Accounts
        $html .= acc_setup_Accounts::loadData();
        
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