<?php 


/**
 * Клас 'status_Messages'
 *
 * @category  vendors
 * @package   status
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class status_Messages extends core_Manager
{
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Статус съобщения';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'status_Wrapper, plg_Created';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('text', 'html', 'caption=Текст');
        $this->FLD('type', 'enum(success=Успех, notice=Известие, warning=Предупреждение, error=Грешка)', 'caption=Тип');
        $this->FLD('userId', 'user', 'caption=Потребител');
        $this->FLD('sid', 'varchar(32)', 'caption=Идентификатор');
        $this->FLD('lifeTime', 'time', 'caption=Живот');
    }
    
    
    /**
     * Добавя статус съобщение към избрания потребител
     * 
     * @param string $text - Съобщение, което ще добавим
     * @param enum $type - Типа на съобщението - success, notice, warning, error
     * @param integer $userId - Потребителя, към когото ще се добавя. Ако не е подаден потребител, тогава взема текущия потребител.
     * @param integer $lifeTime - След колко време да е неактивно
     * 
     * @return integer - При успешен запис връща id' то на записа
     */
    static function newStatus($text, $type='notice', $userId=NULL, $lifeTime=60)
    {
        // Ако не е бил сетнат преди
        if (!Mode::get('hitTime')) {
            
            // Задаваме текущото време
            Mode::set('hitTime', dt::mysql2timestamp());
        }
        
        // Ако не подаден потребител, тогава използваме текущия
        $userId = ($userId) ? ($userId) : (core_Users::getCurrent());
        
        // Стойности за записа
        $rec = new stdClass();
        if (!$userId) {
            $rec->sid = static::getSid();
        }
        $rec->text = $text;
        $rec->type = $type;
        $rec->userId = $userId;
        $rec->lifeTime = $lifeTime;
        
        $id = static::save($rec);
        
        return $id;
    }
    
    
    /**
     * Генерира sid на текущия потребител
     * 
     * @return string - md5 стойността на sid
     */
    static function getSid()
    {
        //Перманентния ключ на текущия потребител
        $permanentKey = Mode::getPermanentKey();
        
        // Стойността на солта на константата
        $conf = core_Packs::getConfig('status');
        $salt = $conf->STATUS_SALT;
        
        //Вземаме md5'а на sid
        $sid = md5($salt . $permanentKey);
        
        return $sid;
    }
    
    
    /**
     * Връща всички статуси на текущия потребител, на които не им е изтекъл lifeTime' а
     * 
     * @param integer $hitTime - timestamp на изискване на страницата
     * @param integer $idleTime - Време на бездействие на съответния таб
     * @param boolean $once - Еднакви (стринг и тип) статус съобщения да се показват само веднъж
     * 
     * @return array $resArr - Масив със съобщението и типа на статуса
     */
    static function getStatuses($hitTime, $idleTime, $once=TRUE)
    {
        $resArr = array();
        
        // id на текущия потребител
        $userId = core_Users::getCurrent();
        
        // Конфигурационния пакет
        $conf = core_Packs::getConfig('status');
        
        // Намяляме времето
        $hitTimeB = $hitTime - $conf->STATUS_TIME_BEFORE;
        
        // Време на извикване на страницата
        $hitTime = dt::timestamp2Mysql($hitTime);
        
        // Време на извикване на страницата с премахнат коригиращ офсет
        $hitTimeB = dt::timestamp2Mysql($hitTimeB);
        
        // Вземаме всички записи за текущия потребител
        // Създадени преди съответното време
        $query = static::getQuery();
        $query->where(array("#createdOn >= '[#1#]'", $hitTimeB));
        
        // Ако потребителя е логнат
        if ($userId > 0) {
            
            // Статусите за него
            $query->where(array("#userId = '[#1#]'", $userId));
        } else {
            // Статусите за съответния SID
            $sid = static::getSid();
            $query->where(array("#sid = '[#1#]'", $sid));
        }
        
        $query->orderBy('createdOn', 'ASC');
        
        $checkedArr = array();
        
        while ($rec = $query->fetch()) {
            
            // Проверяваме дали е изличан преди
            $isRetrived = status_Retrieving::isRetrived($rec->id, $hitTime, $idleTime, $sid, $userId);
            
            // Ако е извличан преди в съответния таб, да не се показва пак
            if ($isRetrived) continue;
            
            // Добавяме в извличанията
            status_Retrieving::addRetrieving($rec->id, $hitTime, $idleTime, $sid, $userId);
            
            // Ако ще се показват само веднъж
            if ($once) {
                
                $strHash = md5($rec->text . $rec->type);
                if ($checkedArr[$strHash]) continue;
                $checkedArr[$strHash] = $strHash;
            }
            
            // Двумерен масив с типа и текста
            $resArr[$rec->id]['text'] = tr("|*" . $rec->text);
            $resArr[$rec->id]['type'] = $rec->type;
        }
        
        return $resArr;
    }
    
    
    /**
     * Абонира за извличане на статус съобщения
     * 
     * @return core_ET
     */
    static function subscribe_()
    {
        $res = new ET();
        
        // Ако е регистриран потребител
        if (haveRole('user')) {
            
            // Абонираме статус съобщенията
            core_Ajax::subscribe($res, array('status_Messages', 'getStatuses'), 'status', 5000);
        }
        
        // Извлича статусите веднага след обновяване на страницата
        core_Ajax::subscribe($res, array('status_Messages', 'getStatuses'), 'statusOnce', 0);
        
        return $res;
    }
    
    
    /**
     * Връща статус съобщенията
     */
    static function act_getStatuses()
    {
        // Ако се вика по AJAX
        if (Request::get('ajax_mode')) {
            
            // Ако се принтира
            if (Request::get('Printing')) return array();
            
            // Времето на отваряне на таба
            $hitTime = Request::get('hitTime', 'int');
            
            // Време на бездействие
            $idleTime = Request::get('idleTime', 'int');
            
            // Вземаме непоказаните статус съобщения
            $statusesArr = static::getStatusesData($hitTime, $idleTime);
            
            // Ако няма нищо за показване
            if (!$statusesArr) return array();
            
            return $statusesArr;
        }
    }
    
    
    /**
     * Връща 'div' със статус съобщенията
     * 
     * @param integer $hitTime - Timestamp на показване на страницата
     * @param integer $idleTime - Време на бездействие на съответния таб
     * 
     * @return string - 'div' със статус съобщенията
     */
    static function getStatusesData_($hitTime, $idleTime)
    {
        // Всички статуси за текущия потребител преди времето на извикване на страницата
        $statusArr = static::getStatuses($hitTime, $idleTime);
        
        $resStatus = array();
        
        foreach ($statusArr as $value) {
            
            $res = '';
            
            // Записваме всеки статус в отделен div и класа се взема от типа на статуса
            $res = "<div class='statuses-{$value['type']}'> {$value['text']} </div>";
            
            // Добавяме резултата
            $resObj = new stdClass();
            $resObj->func = 'html';
            $resObj->arg = array('id'=>'statuses', 'html' => $res, 'replace' => FALSE);
            
            $resStatus[] = $resObj;
        }
        
        return $resStatus;
    }
    
    
    /**
     * Извиква се от крона. Премахва старите статус съобщения
     */
    function cron_removeOldStatuses()
    {
        // Текущото време
        $now = dt::verbal2mysql();
        
        // Вземаме всички статус съобщения, на които име е свършил lifeTime
        $query = static::getQuery();
        $query->where("ADDTIME(#createdOn, SEC_TO_TIME(#lifeTime)) < '{$now}'");
        
        while ($rec = $query->fetch()) {
            
            // Изтриваме информцията за изтегляния
            status_Retrieving::removeRetrieving($rec->id);
            
            // Изтриваме записа
            static::delete($rec->id);
        }
    }
    
    
	/**
     * Изпълнява се след създаването на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        $res .= "<p><i>Нагласяне на Cron</i></p>";
        
        //Данни за работата на cron
        $rec = new stdClass();
        $rec->systemId = 'removeOldStatuses';
        $rec->description = 'Премахва старите статус съобщения';
        $rec->controller = $mvc->className;
        $rec->action = 'removeOldStatuses';
        $rec->period = 5;
        $rec->offset = 0;
        $rec->delay = 0;
        $rec->timeLimit = 40;
        
        $Cron = cls::get('core_Cron');
        
        if ($Cron->addOnce($rec)) {
            $res .= "<li><font color='green'>Задаване на крон да премахва старите статус съобщения.</font></li>";
        } else {
            $res .= "<li>Отпреди Cron е бил нагласен да премахва старите статус съобщения.</li>";
        }
    }
}
