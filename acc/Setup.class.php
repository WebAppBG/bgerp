<?php


/**
 * Колко дена преди края на месеца да се направи следващия бъдещ период чакащ
 */
defIfNot('ACC_DAYS_BEFORE_MAKE_PERIOD_PENDING', '');



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
 * Толеранс за допустимо разминаване на суми
 */
defIfNot('ACC_MONEY_TOLERANCE', '0.05');


/**
 * Колко реда да се показват в детайлния баланс
 */
defIfNot('ACC_DETAILED_BALANCE_ROWS', 500);


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
 * @copyright 2006 - 2014 Experta OOD
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
        'acc_Journal',
        'acc_JournalDetails',
        'acc_Features',
    	'acc_VatGroups',
    	'acc_ClosePeriods',
    	'acc_Operations',
    	'acc_BalanceRepairs',
    	'acc_BalanceRepairDetails',
    	'acc_BalanceTransfers',
    	'acc_AllocatedExpenses',
        'migrate::removeYearInterfAndItem',
        'migrate::updateItemsNum1',
    	'migrate::updateClosedItems3',
    	'migrate::fixExpenses',
    	'migrate::updateItemsEarliestUsedOn',
    );
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
        'ACC_MONEY_TOLERANCE' => array("double(decimals=2)", 'caption=Толеранс за допустимо разминаване на суми в основна валута->Сума'),
        'ACC_DETAILED_BALANCE_ROWS' => array("int", 'caption=Редове в страница от детайлния баланс->Брой редове,unit=бр.'),
    	'ACC_DAYS_BEFORE_MAKE_PERIOD_PENDING' => array("time(suggestions= 1 ден|2 дена|7 Дена)", 'caption=Колко дни преди края на месеца да се направи следващия бъдещ период чакащ->Дни'),
    );
    
    
    /**
     * Роли за достъп до модула
     */
    var $roles = array(
    	array('accJournal'),
    	array('acc', 'accJournal'),
        array('accMaster', 'acc'),
    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
        array(2.1, 'Счетоводство', 'Книги', 'acc_Balances', 'default', "acc, ceo"),
        array(2.3, 'Счетоводство', 'Настройки', 'acc_Periods', 'default', "acc, ceo"),
    );
    
    
    /**
     * Описание на системните действия
     */
    var $systemActions = array(
        array('title' => 'Реконтиране', 'url' => array('acc_Journal', 'reconto', 'ret_url' => TRUE), 'params' => array('title' => 'Реконтиране на документите'))
    );
    
    
    /**
     * Настройки за Cron
     */
    var $cronSettings = array(
        array(
            'systemId' => "Delete Items",
            'description' => "Изтриване на неизползвани затворени пера",
            'controller' => "acc_Items",
            'action' => "DeleteUnusedItems",
            'period' => 1440,
        	'offset' => 60,
            'timeLimit' => 100
        ),
        array(
            'systemId' => "Create Periods",
            'description' => "Създаване на нови счетоводни периоди",
            'controller' => "acc_Periods",
            'action' => "createFuturePeriods",
            'period' => 1440,
            'offset' => 60,
        ),
        array(
            'systemId' => 'RecalcBalances',
            'description' => 'Преизчисляване на баланси',
            'controller' => 'acc_Balances',
            'action' => 'Recalc',
            'period' => 1,
            'timeLimit' => 55,
        ),
    	array(
    		'systemId' => "SyncAccFeatures",
    		'description' => "Синхронизиране на счетоводните свойства",
    		'controller' => "acc_Features",
    		'action' => "SyncFeatures",
    		'period' => 1440,
    		'offset' => 60,
    		'timeLimit' => 600,
    	),
    	array(
    		'systemId' => "CheckAccLimits",
    		'description' => "Проверка на счетоводните лимити",
    		'controller' => "acc_Limits",
    		'action' => "CheckAccLimits",
    		'period' => 480,
    		'offset' => 1,
    		'timeLimit' => 60,
    	),
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    var $defClasses = "acc_ReportDetails, acc_reports_BalanceImpl, acc_BalanceHistory, acc_reports_HistoryImpl, acc_reports_PeriodHistoryImpl,
    					acc_reports_CorespondingImpl,acc_reports_SaleArticles,acc_reports_SaleContractors,acc_reports_OweProviders,
    					acc_reports_ProfitArticles,acc_reports_ProfitContractors,acc_reports_MovementContractors,acc_reports_TakingCustomers,
    					acc_reports_ManufacturedProducts,acc_reports_PurchasedProducts,acc_reports_BalancePeriodImpl, acc_reports_ProfitSales";
    
    
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
     * Обновява номерата на перата
     */
    function updateItemsNum1()
    {
        $Items = cls::get('acc_Items');
        $itemsQuery = $Items->getQuery();
        
        do{
            try {
                $iRec = $itemsQuery->fetch();
                
                if($iRec === NULL) break;
            
            	if(cls::load($iRec->classId, TRUE)){
	                $Register = cls::get($iRec->classId);
	                
	                if($iRec->objectId) {
	                    $regRec = $Register->getItemRec($iRec->objectId);
	                    
	                    if($regRec->num != $iRec->num){
	                        $iRec->num = $regRec->num;
	                        $Items->save_($iRec, 'num');
	                    }
	                }
	            }
            } catch (core_exception_Expect $e) {
            	reportException($e);
            	continue;
            }
            
        } while(TRUE);
    }
    
    
    /**
     * Миграция, която премахва данните останали от мениджъра за годините
     */
    function removeYearInterfAndItem()
    {
        // Изтриваме интерфейса на годините от таблицата с итнерфейсите
        if($oldIntRec = core_Interfaces::fetch("#name = 'acc_YearsAccRegIntf'")){
            core_Interfaces::delete($oldIntRec->id);
        }
        
        if($oldIntRec = core_Interfaces::fetch("#name = 'acc_YearsRegIntf'")){
            core_Interfaces::delete($oldIntRec->id);
        }
        
        try {
            $oldYearManId = core_Classes::getId('acc_Years');
        } catch (core_exception_Expect $e) {
            // Възможно е да няма такъв запис
        }
        
        // Изтриваме и перата за години със стария меджър 'години'
        if($oldYearManId) {
            if(acc_Items::fetch("#classId = '{$oldYearManId}'")){
                acc_Items::delete("#classId = '{$oldYearManId}'");
            }
        }
    }
    
    
    /**
     * Ъпдейт на затворените пера
     */
    public function updateClosedItems3()
    {
    	core_App::setTimeLimit(400);
    	
    	$dealListSysId = acc_Lists::fetchBySystemId('deals')->id;
    	
    	if(!acc_Items::count()) return;
    	
    	$iQuery = acc_Items::getQuery();
    	$iQuery->where("#state = 'closed'");
    	$iQuery->likeKeylist('lists', $dealListSysId);
    	$iQuery->show('classId,objectId,id');
    	
    	while($iRec = $iQuery->fetch()){
    		$closedOn = NULL;
    		$Deal = cls::get($iRec->classId);
    		
    		if($Deal->fetchField($iRec->objectId, 'state') == 'closed'){
    			$CloseDoc = $Deal->closeDealDoc;
    			if($CloseDoc){
    				$CloseDoc = cls::get($CloseDoc);
    				if($clRec = $CloseDoc::fetch("#docClassId = {$iRec->classId} AND #docId = {$iRec->objectId} AND #state = 'active'")){
    					$valior = $CloseDoc->getValiorDate($clRec);
    					if(!$valior){
    						$closedOn = $clRec->createdOn;
    					} else {
    						$closedOn = $valior;
    					}
    				}
    			}
    		}
    		
    		if(!$closedOn){
    			$closedOn = $Deal->fetchField($iRec->objectId, 'modifiedOn');
    		}
    		
    		$iRec->closedOn = $closedOn;
    		$iRec->closedOn = dt::verbal2mysql($iRec->closedOn, FALSE);
    		cls::get('acc_Items')->save_($iRec, 'closedOn');
    	}
    }
    
    
    /**
     * Миграция на разпределението на разходите
     */
    function fixExpenses()
    {
    	$query = acc_AllocatedExpenses::getQuery();
    	$query->where('#currencyId IS NULL AND #rate IS NULL');
    	while($rec = $query->fetch()){
    		$rec->currencyId = 'BGN';
    		$rec->rate = 1;
    		acc_AllocatedExpenses::save($rec);
    	}
    }
    
    
    /**
     * Ъпдейтва полето за най-ранно използване
     */
    function updateItemsEarliestUsedOn()
    {
    	$Items = cls::get('acc_Items');
    	$Items->setupMvc();
    	
    	$query = $Items->getQuery();
    	while($rec = $query->fetch()){
    		if(empty($rec->earliestUsedOn)){
    			try{
    				$rec->earliestUsedOn = dt::verbal2mysql($rec->createdOn, FALSE);
    				$Items->save_($rec, 'earliestUsedOn');
    			} catch(core_exception_Expect $e){
    				reportException($e);
    			}
    		}
    	}
    }
}
