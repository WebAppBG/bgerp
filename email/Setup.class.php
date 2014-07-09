<?php

/**
 * Максимално време за еднократно фетчване на писма
 */
defIfNot('EMAIL_MAX_FETCHING_TIME', 30);


/**
 * Максималната разрешена памет за използване
 */
defIfNot('EMAIL_MAX_ALLOWED_MEMORY', '800M');


/**
 * Шаблон за име на папките, където отиват писмата от дадена държава и неподлежащи на
 * по-адекватно сортиране
 */
defIfNot('EMAIL_UNSORTABLE_COUNTRY', 'Несортирани - %s');


/**
 * Максималното време за изчакване на буфера
 */
defIfNot('EMAIL_POP3_TIMEOUT', 2);


/**
 * Максималната големина на файловете, които ще се приемат за CID
 * 10kB
 */
defIfNot('EMAIL_MAXIMUM_CID_LEN', 10240);


/**
 * Ниво за score на SpamAssassin, над което писмото се обявява за твърд СПАМ
 */
defIfNot('SPAM_SA_SCORE_LIMIT', 7);


/**
 * След колко време (в секунди) след първото изпращане към един имейл да се взема в предвид, че е изпратено преди (Повторно изпращане) 
 * 
 * По подразбиране 12 часа
 */
defIfNot('EMAIL_RESENDING_TIME', '43200');


/**
 * Максимална дължина на текстовата част на входящите имейли
 */
defIfNot('EMAIL_MAX_TEXT_LEN', '1000000');


/**
 * Дали манипулатора на нишката да е в началото на събджекта на писмото
 */
defIfNot('EMAIL_THREAD_HANDLE_POS', 'BEFORE_SUBJECT');


/**
 * Какъв тип да е генерирания манипулатор за събджект на имейл
 * t0 - <123456>
 * t1 - EML234SGR
 * t2 - #123496
 */
defIfNot('EMAIL_THREAD_HANDLE_TYPE', 'type1');


/**
 * Какъв какви типове манипулатори за събджект на имейл се 
 * с минали периоди 
 * t0 - <123456> (номер на нишка)
 * t1 - EML234SGR (манипулатор на документ + защита)
 * t2 - #123496 (номер на нишка + защита)
 */
defIfNot('EMAIL_THREAD_HANDLE_LEGACY_TYPES', 'type0');


/**
 * Максимален размер на примкачените файлове при изпращане на имейл
 * 20MB
 */
defIfNot('EMAIL_MAX_ATTACHED_FILE_LIMIT', 20971520);


/**
 * class email_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с 'email'
 *
 *
 * @category  bgerp
 * @package   email
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'email_Incomings';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Електронна поща";
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'fileman=0.1,doc=0.1';
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
    
            // Максимално време за еднократно фетчване на писма
            'EMAIL_MAX_FETCHING_TIME' => array ('time(suggestions=1 мин.|2 мин.|3 мин.)', 'mandatory, caption=Максимално време за получаване на имейли в една сесия->Време'),
    
            // Максималното време за изчакване на буфера
            'EMAIL_POP3_TIMEOUT'  => array ('time(suggestions=1 сек.|2 сек.|3 сек.)', 'mandatory, caption=Таймаут на POP3 сокета->Време'),
            
            // Максималната разрешена памет за използване
            'EMAIL_MAX_ALLOWED_MEMORY' => array ('fileman_FileSize', 'mandatory, caption=Максималната разрешена памет за използване при парсиране на имейли->Размер, suggestions=10 kB|20 kB|30 kB|40 kB'),

            // Шаблон за име на папки
            'EMAIL_UNSORTABLE_COUNTRY' => array ('varchar', 'mandatory, caption=Шаблон за име на папки с несортирани имейли->Шаблон'),

            // Максималната големина на файловете, които ще се приемат за CID
            'EMAIL_MAXIMUM_CID_LEN' => array ('int', 'caption=Максималната големина на файловете|*&comma;| които ще се приемат за вградени изображения->Размер'),
            
            // Ниво за score на SpamAssassin, над което писмото се обявява за твърд СПАМ
            'SPAM_SA_SCORE_LIMIT' => array ('int', 'caption=Ниво за score на SpamAssassin|*&comma;| над което писмото се обявява за твърд СПАМ->Ниво'),
            
            // След колко време (в секунди) след първото изпращане към един имейл да се взема в предвид, че е изпратено преди (Повторно изпращане) 
            'EMAIL_RESENDING_TIME' => array ('time(suggestions=1 часа|2 часа|3 часа|5 часа|7 часа|10 часа|12 часа)', 'caption=Време от първото изпращане на имейл|*&comma;| след което се маркира "Преизпращане"->Време'),
            
            // Максимален брой символи в текстовата част на входящите имейли
            'EMAIL_MAX_TEXT_LEN' => array ('int', 'caption=Максимален брой символи в текстовата част на входящите имейли->Символи'),
            
            // Тип на манипулатора в събджекта
            'EMAIL_THREAD_HANDLE_POS' => array ('enum(BEFORE_SUBJECT=Преди събдекта,AFTER_SUBJECT=След събджекта)', 'caption=Манипулатор на нишка в събджект на имейл->Позиция'),
            
            // Позиция на манипулатора в събджекта
            'EMAIL_THREAD_HANDLE_TYPE' => array ('enum(type0=Тип 0 <1234>,type1=Тип 1 #EML123DEW,type2=Тип 2 #123498,type3=Тип 3 <aftepod>)', 'caption=Манипулатор на нишка в събджект на имейл->Тип'),
            
            // Позиция на манипулатора в събджекта
            'EMAIL_THREAD_HANDLE_LEGACY_TYPES' => array ('set(type0=Тип 0 <1234>,type1=Тип 1 #EML123DEW,type2=Тип 2 #123498,type3=Тип 3 <aftepod>)', 'caption=Манипулатор на нишка в събджект на имейл->Наследени,columns=1'),
            
            // Максимален размер на прикачените файлове и документи
            'EMAIL_MAX_ATTACHED_FILE_LIMIT' => array ('fileman_FileSize', 'caption=Максимален размер на прикачените файлове/документи в имейла->Размер, suggestions=10 MB|20 MB|30 MB'),
        );
        
        
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'email_Incomings',
            'email_Outgoings',
            'email_Inboxes',
            'email_Accounts',
            'email_Router',
            'email_Addresses',
            'email_FaxSent',
            'email_Filters',
            'email_Returned',
            'email_Receipts',
            'email_Spam',
            'email_Fingerprints',
            'email_Unparsable',
            'email_Salutations',
            'email_ThreadHandles',
            'migrate::transferThreadHandles',
        );
    

    /**
     * Роли за достъп до модула
     */
    var $roles = 'email, fax';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(1.23, 'Документи', 'Имейли', 'email_Outgoings', 'default', "admin, email, fax, user"),
        );
        
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
       
        $html = parent::install();
            
        //инсталиране на кофата
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('Email', 'Прикачени файлове в имейлите', NULL, '104857600', 'user', 'user');
             
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме
        $html .= $Plugins->installPlugin('UserInbox', 'email_UserInboxPlg', 'core_Users', 'private');
        
        // Инсталираме плъгина за преобразуване на имейлите в линкове
        $html .= $Plugins->installPlugin('EmailToLink', 'email_ToLinkPlg', 'type_Email', 'private');
        
        //
        // Инсталиране на плъгин за автоматичен превод на входящата поща
        //
        $html .= $Plugins->installPlugin('Email Translate', 'email_plg_IncomingsTranslate', 'email_Incomings', 'private');
        
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
     * Миграция, която прехвърля манипулаторите на нишки от модел doc_Threads 
     * в email_ThreadHandles
     */
    function transferThreadHandles()
    {
        $docThreads = cls::get('doc_Threads');

        // Манипулатор на нишката (thread handle)
        $docThreads->FLD('handle', 'varchar(32)', 'caption=Манипулатор');

        $tQuery = $docThreads->getQuery();

        while($rec = $tQuery->fetch("#handle IS NOT NULL")) {
            $rec->handle = strtoupper($rec->handle);
            if($rec->handle{0} >= 'A' && $rec->handle{0} <= 'Z') {
                email_ThreadHandles::save( (object) array('threadId' => $rec->id, 'handle' => '#' . $rec->handle));
            }
        }

    }
}
