<?php



/**
 * FileHandler на логото на фирмата на английски
 */
defIfNot(BGERP_COMPANY_LOGO_EN, '');


/**
 * FileHandler на логото на фирмата на български
 */
defIfNot(BGERP_COMPANY_LOGO, '');



/**
 * След колко време, ако не работи крона да бие нотификация
 */
defIfNot(BGERP_NON_WORKING_CRON_TIME, 3600);


/**
 * class 'bgerp_Setup' - Начално установяване на 'bgerp'
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_Setup extends core_ProtoSetup {
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'bgerp_Menu';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct;
    
    
    /**
     * Описание на модула
     */
    var $info = "Основно меню и портал на bgERP";
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
        'BGERP_COMPANY_LOGO' => array ('fileman_FileType(bucket=pictures)', 'caption=Фирмена бланка->На български, customizeBy=powerUser'),
        
        'BGERP_COMPANY_LOGO_EN' => array ('fileman_FileType(bucket=pictures)', 'caption=Фирмена бланка->На английски, customizeBy=powerUser'),
        
        'BGERP_NON_WORKING_CRON_TIME' => array ('time(suggestions=30 мин.|1 час| 3 часа)', 'caption=След колко време да дава нотификация за неработещ cron->Време'),
    );
    
    
    /**
     * Описание на системните действия
     */
    var $systemActions = array(
        
        'Поправка' => array ('doc_Containers', 'repair', 'ret_url' => TRUE)
    
    );
    
    /**
     * Път до js файла
     */
    //    var $commonJS = 'js/PortalSearch.js';
    
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        // Предотвратяваме логването в Debug режим
        Debug::$isLogging = FALSE;
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        $html .= $Plugins->repair();
        
        $managers = array(
            'bgerp_Menu',
            'bgerp_Portal',
            'bgerp_Notifications',
            'bgerp_Recently',
        );
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        // Инстанция на мениджъра на пакетите
        $Packs = cls::get('core_Packs');
        
        // Това първо инсталиране ли е?
        $isFirstSetup = ($Packs->count() == 0);
        
        // Списък на основните модули на bgERP
        $packs = "core,fileman,drdata,bglocal,editwatch,recently,thumb,doc,acc,currency,cms,
                  email,crm, cat, trans, price, blast,rfid,hr,trz,lab,sales,mp,marketing,store,cond,cash,bank,
                  budget,purchase,accda,sens,cams,frame,cal,fconv,log,fconv,cms,blogm,forum,deals,findeals,
                  vislog,docoffice,incoming,support,survey,pos,change,sass,techno2,
                  callcenter,social,hyphen,distro,dec,status,phpmailer,label";
        
        // Ако има private проект, добавяме и инсталатора на едноименния му модул
        if (defined('EF_PRIVATE_PATH')) {
            $packs .= ',' . strtolower(basename(EF_PRIVATE_PATH));
        }
        
        // Добавяме допълнителните пакети, само при първоначален Setup
        $Folders = cls::get('doc_Folders');
        
        if (!$Folders->db->tableExists($Folders->dbTableName) || ($isFirstSetup)) {
            $packs .= ",avatar,keyboard,statuses,google,catering,gdocs,jqdatepick,imagics,fastscroll,context,autosize,oembed,hclean,chosen,help,toast,compactor,rtac";
        } else {
            $packs = arr::make($packs, TRUE);
            $pQuery = $Packs->getQuery();
            
            while ($pRec = $pQuery->fetch()) {
                if(!$packs[$pRec->name]) {
                    $packs[$pRec->name] = $pRec->name;
                }
            }
        }
        
        if (Request::get('SHUFFLE')) {
            
            // Ако е зададен параметър shuffle  в урл-то разбъркваме пакетите
            if (!is_array($packs)) {
                $packs = arr::make($packs);
            }
            shuffle($packs);
            $packs = implode(',', $packs);
        }
        
        $haveError = array();
        
        do {
            $loop++;
            
            // Извършваме инициализирането на всички включени в списъка пакети
            foreach (arr::make($packs) as $p) {
                if (cls::load($p . '_Setup', TRUE) && !$isSetup[$p]) {
                    try {
                        $html .= $Packs->setupPack($p);
                        $isSetup[$p] = TRUE;
                        
                        // Махаме грешките, които са възникнали, но все пак
                        // са се поправили в не дебъг режим
                        if (!isDebug()) {
                            unset($haveError[$p]);
                        }
                    } catch (core_exception_Expect $exp) {
                        $force = TRUE;
                        $Packs->alreadySetup[$p . $force] = FALSE;
                        
                        //$haveError = TRUE;
                        file_put_contents(EF_TEMP_PATH . '/' . date('H-i-s') . '.log.html', ht::mixedToHtml($exp->getTrace()) . "\n\n",  FILE_APPEND);
                        $haveError[$p] .= "<h3 style='color:red'>Грешка при инсталиране на пакета {$p}<br>" . $exp->getMessage() . " " . date('H:i:s') . "</h3>";
                    }
                }
            }
            
            // Извършваме инициализирането на всички включени в списъка пакети
            foreach (arr::make($packs) as $p) {
                if (cls::load($p . '_Setup', TRUE) && !$isLoad[$p]) {
                    $packsInst[$p] = cls::get($p . '_Setup');
                    
                    if (method_exists($packsInst[$p], 'loadSetupData')) {
                        try {
                            $html .= "<h2>Инициализиране на $p</h2>";
                            $html .= "<ul>";
                            $html .= $packsInst[$p]->loadSetupData();
                            $html .= "</ul>";
                            $isLoad[$p] = TRUE;
                            
                            // Махаме грешките, които са възникнали, но все пак са се поправили
                            // в не дебъг режим
                            if (!isDebug()) {
                                unset($haveError[$p]);
                            }
                        } catch(core_exception_Expect $exp) {
                            //$haveError = TRUE;
                            file_put_contents(EF_TEMP_PATH . '/' . date('H-i-s') . '.log.html', ht::mixedToHtml($exp->getTrace()) . "\n\n",  FILE_APPEND);
                            $haveError[$p] .= "<h3 style='color:red'>Грешка при зареждане данните на пакета {$p} <br>" . $exp->getMessage() . " " . date('H:i:s') . "</h3>";
                        }
                    }
                }
            }
        } while (!empty($haveError) && ($loop<5));
        
        $html .= implode("\n", $haveError);
        
        //Създаваме, кофа, където ще държим всички прикачени файлове на бележките
        $Bucket = cls::get('fileman_Buckets');
        $Bucket->createBucket('Notes', 'Прикачени файлове в бележки', NULL, '1GB', 'user', 'user');
        $Bucket->createBucket('bnav_importCsv', 'CSV за импорт', 'csv', '20MB', 'user', 'every_one');
        
        // Добавяме Импортиращия драйвър в core_Classes
        $html .= core_Classes::add('bgerp_BaseImporter');
        
        //TODO в момента се записват само при инсталация на целия пакет
        
        
        //Зарежда данни за инициализация от CSV файл за core_Lg
        $html .= bgerp_data_Translations::loadData();
        
        // Инсталираме плъгина за прихващане на първото логване на потребител в системата
        $html .= $Plugins->installPlugin('First Login', 'bgerp_plg_FirstLogin', 'core_Users', 'private');
        
        // Инсталираме плъгина за проверка дали работи cron
        $html .= $Plugins->installPlugin('Check cron', 'bgerp_plg_CheckCronOnLogin', 'core_Users', 'private');
        
        $Menu = cls::get('bgerp_Menu');
        
        // Да се изтрият необновените менюта
        $Menu->deleteNotInstalledMenu = TRUE;
        
        $html .= bgerp_Menu::addOnce(1.62, 'Система', 'Админ', 'core_Packs', 'default', 'admin');
        
        $html .= bgerp_Menu::addOnce(1.66, 'Система', 'Файлове', 'fileman_Log', 'default', 'powerUser');
        
        $html .= $Menu->repair();
        
        // Принудително обновяване на ролите
        $html .= core_Roles::rebuildRoles();
        $html .= core_Users::rebuildRoles();
        
        return $html;
    }
    
    
    /**
     * Временно, преди този клас да стане наследник на core_ProtoSetup
     */
    function loadSetupData()
    {
    }
}
