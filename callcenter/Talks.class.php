<?php 


/**
 * Защитен ключ за регистриране на обаждания
 */
defIfNot('CALLCENTER_PROTECT_KEY', md5(EF_SALT . 'callCenter'));


/**
 * Мениджър за записване на обажданията
 *
 * @category  bgerp
 * @package   callcenter
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class callcenter_Talks extends core_Master
{
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Разговори';
    
    
    /**
     * 
     */
    var $singleTitle = 'Разговор';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'user';
    
    
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
    var $canView = 'user';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'user';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'admin';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'callcenter_Wrapper, plg_RowTools, plg_Printing, plg_Search, plg_Sorting, plg_RefreshRows';
    
    
    /**
     * 
     */
    var $refreshRowsTime = 3000;
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'callcenter/tpl/SingleLayoutTalks.shtml';
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    var $singleIcon = 'img/16/incoming.png';

    
    /**
     * Поле за търсене
     */
    var $searchFields = 'externalNum, internalNum, dialStatus, startTime';
    
    
    /**
     * 
     */
    var $listFields = 'singleLink=-, externalData, externalNum, internalData, internalNum, startTime, duration';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsField = 'singleLink';
    
    
    /**
     * Полетата, които ще се показват в единичния изглед
     */
//    var $singleFields = 'externalNum, contragent, internalNum, users, dialStatus, uniqId, startTime, answerTime, endTime, duration';
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('externalNum', 'drdata_PhoneType', 'caption=Външен->Номер, width=100%, oldFieldName=callerNum');
        $this->FLD('externalData', 'key(mvc=callcenter_Numbers)', 'caption=Външен->Контакт, width=100%, oldFieldName=callerData');
        
        $this->FLD('internalNum', 'varchar', 'caption=Вътрешен->Номер, width=100%, oldFieldName=calledNum');
        $this->FLD('internalData', 'key(mvc=callcenter_Numbers)', 'caption=Вътрешен->Потребител, width=100%, oldFieldName=calledData');
        
//        $this->FLD('mp3', 'varchar', 'caption=Аудио');
        $this->FLD('dialStatus', 'enum(NO ANSWER=Без отговор, FAILED=Прекъснато, BUSY=Заето, ANSWERED=Отговорено, UNKNOWN=Няма информация)', 'allowEmpty, caption=Състояние, hint=Състояние на обаждането');
        $this->FLD('uniqId', 'varchar', 'caption=Номер');
        $this->FLD('startTime', 'datetime(format=smartTime)', 'caption=Време->Начало');
        $this->FLD('answerTime', 'datetime(format=smartTime)', 'allowEmpty, caption=Време->Отговор');
        $this->FLD('endTime', 'datetime(format=smartTime)', 'allowEmpty, caption=Време->Край');
        $this->FLD('callType', 'type_Enum(incoming=Входящ, outgoing=Изходящ)', 'allowEmpty, caption=Тип на разговора, hint=Тип на обаждането');
        
        $this->FNC('duration', 'time', 'caption=Време->Продължителност');
        
        $this->setDbUnique('uniqId');
    }
    
    
    /**
     * 
     */
    function on_CalcDuration($mvc, &$rec) 
    {
        // Ако е отговорено и затворено
        if ($rec->answerTime && $rec->endTime) {
            
            // Продължителност на разговора
            $duration = dt::secBetwen($rec->endTime, $rec->answerTime);
            
            // Ако има
            if ($duration) {
                
                // Добавяме към записа
                $rec->duration = $duration;
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
    static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {   
        // Информация за външния номер
        $externalNumArr = drdata_PhoneType::toArray($rec->externalNum);
        
        // Ако е валиден номер
        if ($externalNumArr) {
            
            // Ако е мобилен, класа също да е мобилен
            $externalClass = ($externalNumArr[0]->mobile) ? 'mobile' : 'telephone';
            
            // Добавяме стил за телефони        
            $row->externalNum = "<div class='{$externalClass}'>" . $row->externalNum . "</div>";
        } else {
            
            // Вероятно е обаждане от вътрешен номер. Да няма оцветяване.
            $row->externalNum = core_Type::escape($rec->externalNum);
        }
        
        // Ако има данни за търсещия
        if ($rec->externalData) {
         
            // Вземаме записа
            $numRec = callcenter_Numbers::fetch($rec->externalData);
            
            // Вербалния запис
            $externalNumRow = callcenter_Numbers::recToVerbal($numRec);
            
            // Ако има открити данни
            if ($externalNumRow->contragent) {
                
                // Флаг, за да отбележим, че има данни
                $haveExternalData = TRUE;
                
                // Добавяме данните
                $row->externalData = $externalNumRow->contragent;
            }
        } 
        
        // Ако флага не е дигнат
        if (!$haveExternalData) {
            
            // Ако има номер
            if ($rec->externalNum) {
                // Уникално id
                $uniqId = $rec->id . 'caller';
                
                // Добавяме линка
                $row->externalData = static::getTemplateForAddNum($rec->externalNum, $uniqId);
            }
        }
        
        // Ако има данни за търсения
        if ($rec->internalData) {
         
            // Вземаме записа
            $numRec = callcenter_Numbers::fetch($rec->internalData);
            
            // Вербалния запис
            $internalNumRow = callcenter_Numbers::recToVerbal($numRec);
            
            // Ако има открити данни
            if ($internalNumRow->contragent) {
                 
                // Флаг, за да отбележим, че има данни
                $haveInternalData = TRUE;
                
                // Добавяме данните
                $row->internalData = $internalNumRow->contragent;
            }
        }
        
        // Ако флага не е дигнат 
        if (!$haveInternalData) {
            
            // Ако има номер
            if ($rec->internalNum) {
                // Уникално id
                $uniqId = $rec->id . 'called';
                
                // Добавяме линка
                $row->internalData = static::getTemplateForAddNum($rec->internalNum, $uniqId);
            }
        }
        
        // Ако сме в тесен режим
        if (mode::is('screenMode', 'narrow')) {
            
            // Ако не сме в сингъла
            // Добавяме данните към номера
            if(!$fields['-single']) {
                
                // Дива за разстояние
                $div = "<div style='margin-top:5px;'>";
                
                // Добавяме данните към номерата
                $row->externalNum .=  $div. $row->externalData . "</div>";
                $row->internalNum .= $div . $row->internalData . "</div>";
            }
            
            // Ако има продължителност
            if ($rec->duration) {
             
                // Ако няма вербална стойност
                if (!$duration = $row->duration) {
                 
                    // Вземаме вербалната стойност
                    $duration = static::getVerbal($rec, 'duration');
                }
                
                // Добавяме след времето на позвъняване
                $row->startTime .= $div . $duration;
            }
        }
        
        // В зависмост от състоянието на разгоравя, опделяме клас за реда в таблицата
        if (!$rec->dialStatus) {
            $row->DialStatusClass .= ' dialStatus-opened';
        } elseif ($rec->dialStatus == 'ANSWERED') {
            $row->DialStatusClass .= ' dialStatus-answered';
        } else {
            $row->DialStatusClass .= ' dialStatus-failed';
        }
        
        // Добавяме класа
        $row->ROW_ATTR['class'] = $row->DialStatusClass;
        
        // Ако не може да се определи номера
        if (!$rec->externalNum) {
            
            // Добавяме, че е скрит номер
            $row->externalNum = tr('Скрит номер');
        }
    }
    
    
    /**
     * 
     */
    public static function on_AfterPrepareListRows($mvc, $data)
    {
        // Изчистваме нотификацията
        $url = array('callcenter_Talks', 'list');
        bgerp_Notifications::clear($url);  
    }
    
    
    /**
     * Обновява записите за съответния номер
     * 
     * @param string $numStr - Номера
     * @param integer $numId - id на номера
     */
    static function updateRecsForNum($numStr, $numId=NULL)
    {
        // Вземаме всички записи за съответния номер
        $query = static::getQuery();
        $query->where(array("#externalNum = '[#1#]' || #internalNum = '[#1#]'", $numStr));
        
        // Ако id на номера
        if (!$numId) {
            
            // Вземаме последното id
            $nRec = callcenter_Numbers::getRecForNum($numStr);
            $numId = $nRec->id;
        }
        
        // Обхождаме резултатите
        while ($rec = $query->fetch()) {
            
            // Ако номера на позвъняващия отговара
            if ($rec->externalNum == $numStr) {
                
                // Променяме данните
                $rec->externalData = $numId;
            }
            
            // Ако номера на търсения отговара
            if ($rec->internalNum == $numStr) {
                
                // Променяме данните
                $rec->internalData = $numId;
            }
            
            // Записваме
            static::save($rec);
        }
    }
    
    
    /**
     * Екшън за регистриран на обаждане
     */
    function act_RegisterCall()
    {
        // Ключа за защита
        $protectKey = Request::get('p');
        
        // Ако не отговаря на посочения от нас
        if ($protectKey != CALLCENTER_PROTECT_KEY) {
            
            // Записваме в лога
            static::log('Невалиден публичен ключ за обаждането');
            
            // Връщаме
            return FALSE;
        }
        
        // Вземаме променливите
        $startTime = Request::get('starttime');
        $internalNum = Request::get('extension');
        $externalNum = Request::get('callerId');
        $dialStatus = Request::get('dialstatus');
        $uniqId = Request::get('uniqueId');
        $outgoing = Request::get('outgoing');
        
        // Създаваме обекта, който ще използваме
        $nRec = new stdClass();
        
        // Вземаме записите за позвъняващия номера
        $cRec = callcenter_Numbers::getRecForNum($externalNum);
        
        // Ако има такъв запис
        if ($cRec) {
            
            // Вземаме данните за контрагента
            $nRec->externalData = $cRec->id;
        }
        
        // Вземаме записите за търсения номера
        $dRec = callcenter_Numbers::getRecForNum($internalNum);
        
        // Ако има такъв запис
        if ($dRec) {
            
            // Вземаме данните за контрагента
            $nRec->internalData = $dRec->id;
        }
        
        // Добавяме останалите променливи
        $nRec->externalNum = callcenter_Numbers::getNumberStr($externalNum);
        $nRec->internalNum = callcenter_Numbers::getNumberStr($internalNum);
        $nRec->uniqId = $uniqId;
        $nRec->startTime = $startTime;
        
        // Ако е изходящо обаждане
        if ($outgoing) {
            
            // Отбелязваме типа
            $nRec->callType = 'outgoing';
        } else {
            $nRec->callType = 'incoming';
        }
        
        // Записваме
        static::save($nRec);

        return TRUE;
    }
    
    
    /**
     * Екшън за отбелязване на край на разговора
     */
    function act_RegisterEndCall()
    {
        // Ключа за защита
        $protectKey = Request::get('p');
        
        // Ако не отговаря на посочения от нас
        if ($protectKey != CALLCENTER_PROTECT_KEY) {
            
            // Записваме в лога
            static::log('Невалиден публичен ключ за обаждането');
            
            // Връщаме
            return FALSE;
        }
        
        // Вземаме уникалното id на разговора
        $uniqId = Request::get('uniqueId');
        
        // Вземаме записа
        $rec = static::fetch(array("#uniqId = '[#1#]'", $uniqId));
        
        // Ако има такъв запис
        if ($rec->id) {
            
            // Типа на обаждането
            $outgoing = Request::get('outgoing');
            
            // Ако е изходящо
            if ($outgoing) {
                
                // Отбелязваме
                $rec->callType = 'outgoing';
            }
            // Вземаме другите променливи
            $rec->answerTime = Request::get('answertime');
            $rec->endTime = Request::get('endtime');
            $rec->dialStatus = Request::get('dialstatus');
            
            // Обновяваме записа
            static::save($rec, NULL, 'UPDATE');
            
            // Добавяме нотификация
            static::addNotification($rec);
            
            // Връщаме
            return TRUE;
        }
    }
    
    
    /**
     * Добавяме нотификация, за пропуснато повикване
     */
    static function addNotification($rec)
    {
        // Ако няма потребители на този номер или е отговорено
        if ($rec->dialStatus == 'ANSWERED' || $rec->callType == 'outgoing') return;
        
        // Параметри на нотификацията
        $message = "|Имате пропуснато повикване";
        $priority = 'normal';
        $url = array('callcenter_Talks', 'list');
        $customUrl = $url;
        
        $internalNum = $rec->internalNum;
        
        // Вземаме потребителите, които отговарят за съответния номер
        $usersArr = callcenter_Numbers::getUserForNum($internalNum);
        
        // Обхождаме всички потребители
        foreach ((array)$usersArr as $user) {
            
            // Добавяме им нотификация
            bgerp_Notifications::add($message, $url, $user, $priority, $customUrl);
        }
    }
    
    
    /**
     * 
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('usersSearch', 'users(rolesForAll=ceo, rolesForTeams=ceo|manager)', 'caption=Потребител,input,silent', array('attr' => array('onchange' => 'this.form.submit();')));
        
        // Функционално поле за търсене по статус и тип на разговора
        $data->listFilter->FNC('dialStatusType', 'enum()', 'caption=Състояние,input');
        
        // Опции за търсене
        $statusOptions[''] = '';
        
        // Опциите за входящи разговори
        $incomingsOptions = new stdClass();
        $incomingsOptions->title = tr('Входящи');
        $incomingsOptions->attr = array('class' => 'team');
        $incomingsOptions->keylist = 'incomings';
        
        $statusOptions['incoming'] = $incomingsOptions;
        $statusOptions['incoming_ANSWERED'] = tr('Отговорено');
        $statusOptions['incoming_NO ANSWER'] = tr('Без отговор');
        $statusOptions['incoming_BUSY'] = tr('Заето');
        $statusOptions['incoming_FAILED'] = tr('Прекъснато');
        
        // Опциите за изходящи разговоири
        $outgoingsOptions = new stdClass();
        $outgoingsOptions->title = tr('Изходящи');
        $outgoingsOptions->attr = array('class' => 'team');
        $incomingsOptions->keylist = 'outgoings';
        
        $statusOptions['outgoing'] = $outgoingsOptions;
        $statusOptions['outgoing_ANSWERED'] = tr('Отговорено');
        $statusOptions['outgoing_NO ANSWER'] = tr('Без отговор');
        $statusOptions['outgoing_BUSY'] = tr('Заето');
        $statusOptions['outgoing_FAILED'] = tr('Прекъснато');
        
        // Задаваме опциите
        $data->listFilter->setOptions('dialStatusType', $statusOptions);
        
        // Ако имаме тип на обаждането
        if ($typeOptions = &$data->listFilter->getField('callType')->type->options) {
            
            // Добавяме в началото празен стринг за всички
            $typeOptions = array('all' => '') + $typeOptions;
            
            // Избираме го по подразбиране
            $data->listFilter->setDefault('callType', 'all');
        }
        
        // Ако имаме статуси
        if ($typeOptions = &$data->listFilter->getField('dialStatus')->type->options) {
            
            // Добавяме в началото празен стринг за всички
            $typeOptions = array('all' => '') + $typeOptions;
            
            // Избираме го по подразбиране
            $data->listFilter->setDefault('dialStatus', 'all');
        }
        
        // В хоризонтален вид
        $data->listFilter->view = 'horizontal';
        
        // Добавяме бутон
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'search, usersSearch, dialStatusType';
        
        $data->listFilter->input('search, usersSearch, dialStatusType', 'silent');
    }

    
    /**
     * 
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        // Ако не е избран потребител по подразбиране
        if(!$data->listFilter->rec->usersSearch) {
            
            // Да е текущия
            $data->listFilter->rec->usersSearch = '|' . core_Users::getCurrent() . '|';
        }
        
        // Сортиране на записите по num
        $data->query->orderBy('startTime', 'DESC');
        
        // Ако има филтър
        if($filter = $data->listFilter->rec) {
            
            // Ако филтъра е по потребители
            if($filter->usersSearch) {
                
    			// Ако се търси по всички и има права admin или ceo
    			if ((strpos($filter->usersSearch, '|-1|') !== FALSE) && (haveRole('ceo, admin'))) {
    			    // Търсим всичко
                } else {
                    
                    // Масив с потребителите
                    $usersArr = type_Keylist::toArray($filter->usersSearch);
                    
                    // Масив с номерата на съответните потребители
                    $numbersArr = callcenter_Numbers::getInternalNumbersForUsers($usersArr);
                    
                    // Ако има такива номера
                    if (count((array)$numbersArr)) {
                        
                        // Показваме обажданията към и от тях
                        $data->query->orWhereArr('externalNum', $numbersArr);
        			    $data->query->orWhereArr('internalNum', $numbersArr, TRUE);
                    } else {
                        
                        // Не показваме нищо
                        $data->query->where("1=2");
                    }
                }
    		}
    		
            // Ако се търси по статус или вид
            if ($filter->dialStatusType) {
                
                $dialStatusType = $filter->dialStatusType;
                
                // Разделяме статуса от типа
                list($callType, $dialStatus) = explode('_', $dialStatusType);
                
                // Търсим по типа
                $data->query->where(array("#callType = '[#1#]'", $callType));
                
                // Ако търсим по входящи
                if ($callType == 'incoming') {
                    
                    // Търсим по статус
                    $data->query->orWhere("#callType IS NULL");
                }
                
                // Ако е избран статуса на разговора
                if ($dialStatus) {
                    
                    // Търсим по статус на обаждане
                    $data->query->where(array("#dialStatus = '[#1#]'", $dialStatus));
                }
            }
        }
    }
    
    
    /**
     * 
     */
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Ако искаме да отворим сингъла на документа
        if ($rec->id && $action == 'single' && $userId) {
            
            // Ако нямаме роля CEO
            if (!haveRole('ceo')) {
                
                // Ако сме мениджър
                if (haveRole('manager')) {
                    
                    // Вземаме хората от нашия екип
                    $teemMates = core_Users::getTeammates($userId);
                    
                    // Съотборниците в масив
                    $teemMatesArr = type_Keylist::toArray($teemMates);
                    
                    // Връща номерата на всички съотборници
                    $numbersArr = callcenter_Numbers::getInternalNumbersForUsers($teemMatesArr);
                    
                } else {
                    
                    // Връща номерата на потребителя
                    $numbersArr = callcenter_Numbers::getInternalNumbersForUsers($userId);
                }
            
                // Ако има търсен номер и е в масива
                if ($rec->externalNum && in_array($rec->externalNum, $numbersArr)) {
                    
                    // Имаме права
                    $haveRole = TRUE;
                }
                
                // Ако има търсещ номер и е в масива
                if ($rec->internalNum && in_array($rec->internalNum, $numbersArr)) {
                    
                    // Имаме права
                    $haveRole = TRUE;
                }
                
                // Ако флага не е вдингнат
                if (!$haveRole) {
                    
                    // Нямаме права
                    $requiredRoles = 'no_one';
                }
            }
        } 
    }

    
    /**
     * 
     */
    function getIcon($id)
    {
        // Ако няма id връщаме
        if (!$id) return ;
        
        // Вземаме записа
        $rec = static::fetch($id);
        
        // Ако е отговорено
        if (!$rec->dialStatus || $rec->dialStatus == 'ANSWERED') {
            
            // Ако е изходящо обаждане
            if ($rec->callType == 'outgoing') {
                
                // Икона за изходящо обаждане
                $this->singleIcon = 'img/16/outgoing.png';
            } else {
                
                // Ако в входящо
                $this->singleIcon = 'img/16/incoming.png';
            }
        } else {
            
            // Ако е изходящо обаждане
            if ($rec->callType == 'outgoing') {
                
                // Икона за изходящо обаждане
                $this->singleIcon = 'img/16/outgoing-failed.png';
            } else {
                
                // Ако в входящо
                $this->singleIcon = 'img/16/incoming-failed.png';
            }
        }
    }
    
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $mvc
     * @param unknown_type $data
     */
    static function on_AfterPrepareListFields($mvc, $data)
    {
        // Ако сме в тесен режим
        if (mode::is('screenMode', 'narrow')) {
            
            // Променяме полетата, които ще се показват
            $data->listFields = arr::make('singleLink=-, externalNum=Външен, internalNum=Вътрешен, startTime=Време');
        }
    }
    
    
    /**
     * Връща стринг с линкове за добавяне на номера във фирма, лица или номера
     * 
     * @param string $num - Номера, за който се отнася
     * @param string $uniqId - Уникално id
     * 
     * @return string - Тага за заместване
     */
    static function getTemplateForAddNum($num, $uniqId)
    {
        // Ако не е валиден номер
        // Третираме го като вътрешен
        if (!$numArr = drdata_PhoneType::toArray($num)) {
            
            // Аттрибути за стилове 
            $numbersAttr['title'] = tr('Добави към потребител');
            
            // Икона на телефон
            $phonesImg = "<img src=" . sbf('img/16/telephone2-add.png') . " width='16' height='16'>";
            
            // Създаваме линк
            $text = ht::createLink($phonesImg, array('callcenter_Numbers', 'add', 'number' => $num, 'ret_url' => TRUE), FALSE, $numbersAttr);
            
            // Ако няма роля admin, да не се показва шаблона за нов
            if (!haveRole('admin')) return ;
        } else {
            
            // Аттрибути за стилове 
            $companiesAttr['title'] = tr('Нова фирма');
            
            // Икона на фирмите
            $companiesImg = "<img src=" . sbf('img/16/office-building-add.png') . " width='16' height='16'>";
            
            // Добавяме линк към създаване на фирми
            $text = ht::createLink($companiesImg, array('crm_Companies', 'add', 'tel' => $num, 'ret_url' => TRUE), FALSE, $companiesAttr);
            
            // Аттрибути за стилове 
            $personsAttr['title'] = tr('Ново лице');
            
            // Икона на изображенията
            $personsImg = "<img src=" . sbf('img/16/vcard-add.png') . " width='16' height='16'>";
            
            // Ако е мобилен номер, полето ще сочи към мобилен
            $personNumField = ($numArr[0]->mobile) ? 'mobile' : 'tel';
            
            // Добавяме линк към създаване на лица
            $text .= " | ". ht::createLink($personsImg, array('crm_Persons', 'add', $personNumField => $num, 'ret_url' => TRUE), FALSE, $personsAttr);
        }
        
        // Дали да се показва или не
        $visibility = (mode::is('screenMode', 'narrow')) ? 'visible' : 'hidden';
        
        // Ако сме в мобилен режим
        if (mode::is('screenMode', 'narrow')) {
            
            // Не се добавя JS
            $res = "<div id='{$uniqId}'>{$text}</div>";
        } else {
            
            // Ако не сме в мобилен режим
            
            // Скриваме полето и добавяме JS за показване
            $res = "<div onmouseover=\"changeVisibility('{$uniqId}', 'visible');\" onmouseout=\"changeVisibility('{$uniqId}', 'hidden');\">
        		<div style='visibility:hidden;' id='{$uniqId}'>{$text}</div></div>";
        }
        
        return $res;
    }
    
    
    /**
     * Екшън за тестване
     * Генерира обаждане
     */
    function act_Mockup()
    {
        // Текущото време - времето на позвъняване
        $startTime = dt::now();
        
        // Масив със статусите
        $staturArr = array('NO ANSWER', 'FAILED', 'BUSY', 'ANSWERED', 'UNKNOWN', 'ANSWERED', 'ANSWERED', 'ANSWERED', 'ANSWERED', 'ANSWERED', 'ANSWERED');
        
        // Избираме един случаен стату
        $status = $staturArr[rand(0, 10)];
        
        // Ако е отговорен
        if ($status == 'ANSWERED') {
            
            // Времето в UNIX
            $unixTime = dt::mysql2timestamp($startTime);
            
            // Времето за отговор
            $answerTime = $unixTime + rand(3, 7);
            
            // Времето на края на разговора
            $endTime = $unixTime + rand(22, 88);
            
            // Преобразуваме ги в mySQL формат
            $myAnswerTime = dt::timestamp2Mysql($answerTime);
            $myEndTime = dt::timestamp2Mysql($endTime);
        }
        
        // Генерираме рандом чило за уникалното id
        $uniqId = rand();
        
        // Масив за линка
        $urlArr = array(
            'Ctr' => 'callcenter_Talks',
            'Act' => 'RegisterCall',
            'p' => CALLCENTER_PROTECT_KEY,
            'starttime' => $startTime,
            'extension' => '540',
            'callerId' => '539',
            'uniqueId' => $uniqId,
//            'outgoing' => 'outgoing',
        );
        
        // Вземаме абсолютния линк
        $url = toUrl($urlArr, 'absolute');
        
        // Извикваме линка
        exec("wget -q --spider '{$url}'");
        
        // Масив за линка
        $urlArr = array(
            'Ctr' => 'callcenter_Talks',
            'Act' => 'RegisterEndCall',
            'p' => CALLCENTER_PROTECT_KEY,
            'answertime' => $myAnswerTime,
            'endtime' => $myEndTime,
            'dialstatus' => $status,
            'uniqueId' => $uniqId,
//            'outgoing' => 'outgoing'
        );
        
        // Вземаме абсолютния линк
        $url = toUrl($urlArr, 'absolute');
        
        // Извикваме линка
        exec("wget -q --spider '{$url}'");
    }
}
