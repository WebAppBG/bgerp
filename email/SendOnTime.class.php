<?php 


/**
 * Имейли за изпращане по време
 * 
 * @category  bgerp
 * @package   email
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_SendOnTime extends core_Manager
{
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Имейли за отложено изпращане';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, ceo';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin, ceo, debug';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Кой може да прекратява изпращането
     */
    public $canStop = 'user';
    
     
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'email_Wrapper, plg_Created, plg_State';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, object=Документ, sendOn=Изпращане->На, createdBy=Изпращане->От, boxFrom=Изпращане->Адрес, emailsTo, emailsCc, faxTo, faxService, sentOn';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('class', 'varchar(64, ci)', 'caption=Клас, oldFieldName=classId');
        $this->FLD('objectId', 'int', 'caption=Обект');
        $this->FLD('data', 'blob(serialize, compress)', 'caption=Данни');
        $this->FLD('delay', 'time', 'caption=Отлагане');
        $this->FLD('sentOn', 'datetime(format=smartTime)', 'caption=Изпратено на');
        $this->FLD('state', 'enum(pending=Чакащо,stopped=Спряно,closed=Приключено)', 'caption=Състояние, notNull');
        
        $this->FNC('sendOn', 'datetime(format=smartTime)', 'caption=Изпращане->На');
        $this->FNC('emailsTo', 'emails', 'caption=Изпращане->До');
        $this->FNC('emailsCc', 'emails', 'caption=Изпращане->Копие');
        $this->FNC('faxTo', 'drdata_PhoneType', 'caption=Изпращане->Факс');
        $this->FNC('boxFrom', 'key(mvc=email_Inboxes, select=email)', 'caption=Изпращане->От адрес,mandatory');
        $this->FNC('faxService', 'class(interface=email_SentFaxIntf, select=title)', 'input=none, caption=Изпращане->Факс услуга');
    }
    
    
    /**
     * Добавя запис в модела
     * 
     * @param integer $class
     * @param integer $objectId
     * @param array $data
     * @param integer $delay
     * 
     * @return integer
     */
    public static function add($class, $objectId, $data, $delay)
    {
        $rec = new stdClass();
        $rec->class = $class;
        $rec->objectId = $objectId;
        $rec->data = $data;
        $rec->delay = $delay;
        $rec->state = 'pending';
        
        return self::save($rec);
    }
    
    
    /**
     * Връща вербалните данни за чакащите за изпращане имейли
     * 
     * @param integer $objectId
     * 
     * @return array
     */
    public static function getPendingRows($objectId)
    {
        $query = self::getQuery();
        $query->where(array("#objectId = [#1#]", $objectId));
        
        $query->where("#state = 'pending'");
        $query->orderBy('delay', 'ASC');
        $resArr = array();
        
        while ($rec = $query->fetch()) {
            $resArr[$rec->id] = self::recToVerbal($rec);
            if (self::haveRightFor('stop', $rec)) {
                $resArr[$rec->id]->StopLink = ht::createLink('', array(get_called_class(), 'stop', $rec->id, 'ret_url'=>TRUE), tr('Сигурни ли сте, че искате да спрете изпращането') . '?',
                                                            array('ef_icon' => 'img/12/close.png', 'title' => 'Спиране на изпращането', 'class' => 'smallLinkWithWithIcon'));
            }
        }
        
        return $resArr;
    }
    
    
    /**
     * 
     * 
     * @param email_SendOnTime $mvc
     * @param stdObject $rec
     */
    static function on_CalcSendOn($mvc, $rec)
    {
        $rec->sendOn = dt::addSecs($rec->delay, $rec->createdOn);
    }
    
    
    /**
     * Добавя стойност на функционалното поле emailsTo
     * 
     * @param email_SendOnTime $mvc
     * @param stdObject $rec
     */
    static function on_CalcEmailsTo($mvc, $rec)
    {
        $rec->emailsTo = $rec->data['options']->emailsTo;
    }
    
    
    /**
     * Добавя стойност на функционалното поле emailsCc
     * 
     * @param email_SendOnTime $mvc
     * @param stdObject $rec
     */
    static function on_CalcEmailsCc($mvc, $rec)
    {
        $rec->emailsCc = $rec->data['options']->emailsCc;
    }
    
    
    /**
     * Добавя стойност на функционалното поле faxTo
     * 
     * @param email_SendOnTime $mvc
     * @param stdObject $rec
     */
    static function on_CalcFaxTo($mvc, $rec)
    {
        $rec->faxTo = $rec->data['options']->faxTo;
    }
    
    
    /**
     * Добавя стойност на функционалното поле faxService
     * 
     * @param email_SendOnTime $mvc
     * @param stdObject $rec
     */
    static function on_CalcFaxService($mvc, $rec)
    {
        $rec->faxService = $rec->data['options']->service;
    }
    
    
    /**
     * Добавя стойност на функционалното поле boxFrom
     * 
     * @param email_SendOnTime $mvc
     * @param stdObject $rec
     */
    static function on_CalcBoxFrom($mvc, $rec)
    {
        $rec->boxFrom = $rec->data['options']->boxFrom;
    }
    
    
    /**
     * Екшън за спиране на изпращането
     */
    function act_Stop()
    {
        self::requireRightFor('stop');
        
        $id = Request::get('id', 'int');
        
        self::requireRightFor('stop', $id);
        
        expect($rec = self::fetch($id));
        
        expect($rec->state == 'pending');
        
        $rec->state = 'stopped';
        
        $msg = 'Спряно изпращане';
        $type = 'notice';
        if (self::save($rec, 'state')) {
            email_Outgoings::logWrite($msg, $rec->objectId);
            email_Outgoings::touchRec($rec->objectId);
        } else {
            $msg = 'Грешка при спиране на изпращането';
            $type = 'error';
            email_Outgoings::logErr($msg, $rec->objectId);
        }
        
        return new Redirect(array('email_Outgoings', 'single', $rec->objectId), '|' . $msg, $type);
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Ако ще се затваря
        if ($action == 'stop' && $rec) {
            
            if (!haveRole('admin, ceo')) {
                
                // Ако няма роля admin или ceo
                // Ако не е изпратен от текущия потребител, да не може да се затваря
                if ($rec->createdBy != $userId) {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->object = email_Outgoings::getLinkForObject($rec->objectId);
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->XPR('orderByState', 'int', "(CASE #state WHEN 'pending' THEN 1 WHEN 'closed' THEN 3 ELSE 2 END)");
        $data->query->orderBy('orderByState', 'ASC');
        $data->query->orderBy('delay', 'ASC');
    }
    
    
    /**
     * Функция, която се изпълнява от крона и изпраща имейлите
     */
    function cron_SendEmails()
    {
        $query = self::getQuery();
        $now = dt::verbal2mysql();
        $query->where("DATE_ADD(#createdOn, INTERVAL #delay SECOND) <= '{$now}'");
        $query->where("#state != 'closed'");
        
        $cnt = 0;
        
        while ($rec = $query->fetch()) {
            
            // Трябва да спрем системния потребител
            $isSystemUser = core_Users::isSystemUser();
            if ($isSystemUser) {
                core_Users::cancelSystemUser();
            }
            
            core_Users::sudo($rec->createdBy);
            try {
                $inst = cls::get($rec->class);
                $inst->send($rec->data['rec'], $rec->data['options'], $rec->data['lg']);
                self::logErr('Грешка при изпращане', $rec->id);
            } catch (ErrorException $e) {
                reportException($e);
            }
            core_Users::exitSudo();
            
            if ($isSystemUser) {
                core_Users::forceSystemUser();
            }
            
            $rec->state = 'closed';
            $rec->sentOn = dt::now();
            
            $this->save($rec, 'state, sentOn');
            
            $cnt++;
        }
        
        $res = "Брой изпращания: " . $cnt;
        
        return $res;
    }
    
    
    /**
     * Изпълнява се след създаването на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        //Данни за работата на cron
        $rec = new stdClass();
        $rec->systemId = 'sendEmails';
        $rec->description = 'Изпраща имейлите, които са за отложено изпращане';
        $rec->controller = $mvc->className;
        $rec->action = 'sendEmails';
        $rec->period = 1;
        $rec->offset = 0;
        $rec->delay = 0;
        $rec->timeLimit = 100;
        $res .= core_Cron::addOnce($rec);
    }
}
