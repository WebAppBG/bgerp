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
 * class 'bgerp_Setup' - Начално установяване на 'bgerp'
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class bgerp_Setup {
    
    
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
           
       'BGERP_COMPANY_LOGO_EN' => array ('fileman_FileType(bucket=pictures)', 'caption=Фирмена бланка на английски (750х100 px)->Изображение'),

       'BGERP_COMPANY_LOGO'   => array ('fileman_FileType(bucket=pictures)', 'caption=Фирмена бланка на български (750х100 px)->Изображение'),
    
     );
    
    
    /**
     * Описание на системните действия
     */
    var $systemActions = array(
           
       'Поправка' => array ('doc_Folders', 'repair')
    
    );
    
    
    /**
     * Инсталиране на пакета
     */
    function install($Plugins = NULL)
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
        $packs = "core,fileman,drdata,bglocal,editwatch,recently,thumbnail,doc,acc,currency,cms,
                  email,crm, cat, trans, price, blast,rfid,hr,trz,lab,sales,mp,store,cond,cash,bank,
                  budget,purchase,accda,sens,cams,cal,fconv,log,fconv,cms,blogm,forum,
                  vislog,docoffice,incoming,support,survey,pos,change,sass,techno,
                  callcenter,social,hyphen,distro,dec,help,status,toast";
        
        // Ако има private проект, добавяме и инсталатора на едноименния му модул
        if(defined('EF_PRIVATE_PATH')) {
            $packs .= ',' . strtolower(basename(EF_PRIVATE_PATH));
        }
        
        // Добавяме допълнителните пакети, само при първоначален Setup
        $Folders = cls::get('doc_Folders');
        if(!$Folders->db->tableExists($Folders->dbTableName) || ($isFirstSetup)) {
            $packs .= ",avatar,keyboard,statuses,google,catering,gdocs,jqdatepick,oembed,hclean,chosen";
        } else {
            $packs = arr::make($packs, TRUE);
            $pQuery = $Packs->getQuery();
            
            while($pRec = $pQuery->fetch()) {
                if(!$packs[$pRec->name]) {
                    $packs[$pRec->name] = $pRec->name;
                }
            }
        }

    	if(Request::get('SHUFFLE')){
        	
        	// Ако е зададен параметър shuffle  в урл-то разбуркваме пакетите
        	if(!is_array($packs)){
        		$packs = arr::make($packs);
	        }
	        shuffle($packs);
	        $packs = implode(',', $packs);
        }
        
        do {
            $haveError = FALSE;
            $loop++;
            // Извършваме инициализирането на всички включени в списъка пакети
            foreach(arr::make($packs) as $p) {
                if(cls::load($p . '_Setup', TRUE) && !$isSetup[$p]) {
                    try {
                        $html .= $Packs->setupPack($p);
                        $isSetup[$p] = TRUE;
                    } catch(core_exception_Expect $exp) {
                        $html = $html . "<h3 style='color:red'>Грешка при инсталиране на пакета {$p}</h3>";
                        //$html .= $exp->getAsHtml();
                        $force = TRUE; 
                        $Packs->alreadySetup[$p . $force] = FALSE;
                        $haveError = TRUE;
                    }
                 }
            }
        
            // Извършваме инициализирането на всички включени в списъка пакети
            foreach(arr::make($packs) as $p) {
                if(cls::load($p . '_Setup', TRUE) && !$isLoad[$p]) {
                    $packsInst[$p] = cls::get($p . '_Setup');
                    if(method_exists($packsInst[$p], 'loadSetupData')) {
                        try {
                            $packsInst[$p]->loadSetupData();
                            $isLoad[$p] = TRUE;
                        } catch(core_exception_Expect $exp) {
                            $html = $html . "<h3 style='color:red'>Грешка при зареждане данните на пакета {$p}</h3>";
                            $haveError = TRUE;
                            //$html .= $exp->getAsHtml();
                        }
                    }
                }
            }
        } while ($haveError && ($loop<5));
        
        //Създаваме, кофа, където ще държим всички прикачени файлове на бележките
        $Bucket = cls::get('fileman_Buckets');
        $Bucket->createBucket('Notes', 'Прикачени файлове в бележки', NULL, '1GB', 'user', 'user');
		$Bucket->createBucket('bnav_importCsv', 'CSV за импорт', 'csv', '20MB', 'user', 'every_one');
		
		// Добавяме Импортиращия драйвър в core_Classes
        core_Classes::add('bgerp_BaseImporter');
        
        //TODO в момента се записват само при инсталация на целия пакет
        
        
        //Зарежда данни за инициализация от CSV файл за core_Lg
        $html .= bgerp_data_Translations::loadData();
        

        // Инсталираме плъгина за прихващане на първото логване на потребител в системата
        $html .= $Plugins->installPlugin('First Login', 'bgerp_plg_FirstLogin', 'core_Users', 'private');

        $Menu = cls::get('bgerp_Menu');
        
        // Да се изтрият необновените менюта
        $Menu->deleteNotInstalledMenu = TRUE;
        
        $html .= $Menu->addItem(1.62, 'Система', 'Админ', 'core_Packs', 'default', 'admin');

        $html .= $Menu->addItem(1.66, 'Система', 'Файлове', 'fileman_Log', 'default', 'powerUser');
        
        $html .= $Menu->repair();
        
        // Принудително обновяване на ролите
        $html .= core_Roles::rebuildRoles();
        $html .= core_Users::rebuildRoles();


        return $html;
    }
    
    
}
