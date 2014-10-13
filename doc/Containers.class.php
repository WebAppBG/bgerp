<?php



/**
 * Клас 'doc_Containers' - Контейнери за документи
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_Containers extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_Modified,plg_RowTools,doc_Wrapper,plg_State, doc_ThreadRefreshPlg';
    
    
    /**
     * 10 секунди време за опресняване на нишката
     */
    var $refreshRowsTime = 10000;


    /**
     * Заглавие
     */
    var $title = "Документи в нишките";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "created=Създаване,document=Документи";
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'doc_ThreadDocuments';
    
    
    /**
     * @todo Чака за документация...
     */
    var $listItemsPerPage = 100;
    
    
    /**
     * @todo Чака за документация...
     */
    var $canList = 'powerUser';
    
    
    /**
     * @todo Чака за документация...
     */
    var $canAdd  = 'no_one';
    
    
    /**
     * Масив с всички абревиатури и съответните им класове
     */
    static $abbrArr = NULL;
    
    
    /**
     * Кой може да добавя документ
     * @see doc_RichTextPlg
     */
    var $canAdddoc = 'user';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Мастери - нишка и папка
        $this->FLD('folderId' , 'key(mvc=doc_Folders)', 'caption=Папки');
        $this->FLD('threadId' , 'key(mvc=doc_Threads)', 'caption=Нишка');
        $this->FLD('originId' , 'key(mvc=doc_Containers)', 'caption=Основание');
        
        // Документ
        $this->FLD('docClass' , 'class(interface=doc_DocumentIntf,select=title,allowEmpty)', 'caption=Документ->Клас');
        $this->FLD('docId' , 'int', 'caption=Документ->Обект');
        $this->FLD('searchKeywords', 'text', 'notNull,column=none, input=none');
        
        $this->FLD('activatedBy', 'key(mvc=core_Users)', 'caption=Активирано от, input=none');
        
        // Индекси за бързодействие
        $this->setDbIndex('folderId');
        $this->setDbIndex('threadId');
        $this->setDbUnique('docClass, docId');
    }
    
    
    /**
     * Изпълнява се след подготовката на филтъра за листовия изглед
     * Обикновено тук се въвеждат филтриращите променливи от Request
     */
    static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        expect($data->threadId = Request::get('threadId', 'int'));
        expect($data->threadRec = doc_Threads::fetch($data->threadId));
        
        $data->folderId = $data->threadRec->folderId;
        
        doc_Threads::requireRightFor('single', $data->threadRec);
        
        expect($data->threadRec->firstContainerId, 'Проблемен запис на нишка', $data->threadRec);
       
        bgerp_Recently::add('document', $data->threadRec->firstContainerId, NULL, ($data->threadRec->state == 'rejected') ? 'yes' : 'no');
        
        $data->query->orderBy('#createdOn');
        
    	$threadId = Request::get('threadId', 'int');
        
        if($threadId) {
            $data->query->where("#threadId = {$threadId}");
        }
    }
    
    
    /**
     * Подготвя титлата за единичния изглед на една нишка от документи
     */
    static function on_AfterPrepareListTitle($mvc, &$res, $data)
    {
        $title = new ET("<div class='path-title'>[#user#] » [#folder#] ([#folderCover#]) » [#threadTitle#]</div>");
        
        // Папка и корица
        $folderRec = doc_Folders::fetch($data->folderId);
        $folderRow = doc_Folders::recToVerbal($folderRec);
        $title->replace($folderRow->title, 'folder');
        $title->replace($folderRow->type, 'folderCover');
        // Потребител
        if($folderRec->inCharge) {
            $user = crm_Profiles::createLink($folderRec->inCharge);
        } else {
            $user = '@system';
        }
        $title->replace($user, 'user');
        
        // Заглавие на треда
        $document = $mvc->getDocument($data->threadRec->firstContainerId);
        $docRow = $document->getDocumentRow();
        $docTitle = str::limitLen($docRow->title, 70);
        $title->replace($docTitle, 'threadTitle');
        
        $mvc->title = '|*' . str::limitLen($docRow->title, 20) . ' « ' . doc_Folders::getTitleById($folderRec->id) .'|';

        $data->title = $title;
    }
    
    
    /**
     * Добавя div със стил за състоянието на треда
     */
    static function on_AfterRenderListTable($mvc, &$tpl, $data)
    {
        $state = $data->threadRec->state;
        $tpl = new ET("<div class='thread-{$state} single-thread'>[#1#]</div>", $tpl);
        
        // Изчистване на нотификации за отворени теми в тази папка
        $url = array('doc_Containers', 'list', 'threadId' => $data->threadRec->id);
        bgerp_Notifications::clear($url);
        
        $tpl->appendOnce("\n runOnLoad(function(){flashHashDoc(flashDocInterpolation);});", 'JQRUN');
        
        if(Mode::is('screenMode', 'narrow')) {
        	$tpl->appendOnce("\n runOnLoad(function(){setThreadElemWidth()});", 'JQRUN');
        	$tpl->appendOnce('$(window).resize(function(){setThreadElemWidth();});', "JQRUN");
        }
    }
    
    
    /**
     * Подготвя някои вербални стойности за полетата на контейнера за документ
     * Използва методи на интерфейса doc_DocumentIntf, за да вземе тези стойности
     * директно от документа, който е в дадения контейнер
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec, $fields = NULL)
    {
        try {
            try {
                $document = $mvc->getDocument($rec->id);
                $docRow = $document->getDocumentRow();
            } catch ( core_Exception_Expect $expect) {
                // Ако имаме клас на документа, обаче липсва ключ към конкретен документ
                // Правим опит да го намерим по обратния начин - чрез $containerId в записа на документа
                if($rec->docClass && !$rec->docId && cls::load($rec->docClass, TRUE)) {
                    $docMvc = cls::get($rec->docClass);
                    if($rec->docId = $docMvc->fetchField("#containerId = {$rec->id}", 'id')) {
                        $mvc->save($rec);
                        $document = $mvc->getDocument($rec->id);
                        $docRow = $document->getDocumentRow();
                    }
                }
            }
        } catch (core_Exception_Expect $expect) {
            // Възникнала е друга грешка при прочитането на документа
            // Не се предвижда коригиращо действие
        }

        if($docRow) {
            $data = $document->prepareDocument();
            $row->ROW_ATTR['id'] = $document->getHandle();
            $row->ROW_ATTR['onMouseUp'] = "saveSelectedTextToSession('" . $document->getHandle() . "', 'onlyHandle');";
            $row->document = $document->renderDocument($data);
            
            if($q = Request::get('Q')) {
                $row->document = plg_Search::highlight($row->document, $q);
            }

            $row->created = str::limitLen($docRow->author, 32);
        } else {
            if(isDebug()) {
                if(!$rec->docClass) {
                    $debug = 'Липсващ $docClass ';
                }
                if(!$rec->docId) {
                    $debug .= 'Липсващ $docId ';
                }
                if(!$document) {
                    $debug .= 'Липсващ $document ';
                }
            }

            $row->document = new ET("<h2 style='color:red'>[#1#]</h2><p>[#2#]</p>", tr('Грешка при показването на документа'), $debug);
        }
        
        if($docRow->authorId || $docRow->authorEmail) {
            $avatar = avatar_Plugin::getImg($docRow->authorId, $docRow->authorEmail);
        } else {
            $avatar = avatar_Plugin::getImg($rec->createdBy, $docRow->authorEmail);
        }

        $row->created = ucfirst($row->created);
        
        if ($rec->createdBy > 0) {
            $row->created = crm_Profiles::createLink($rec->createdBy);
        }

        if(Mode::is('screenMode', 'narrow')) {
            $row->created = new ET("<div class='profile-summary'><div class='fleft'><div class='fleft'>[#2#]</div><div class='fleft'><span>[#3#]</span>[#1#]</div></div><div class='fleft'>[#HISTORY#]</div><div class='clearfix21'></div></div>",
                $mvc->getVerbal($rec, 'createdOn'),
                $avatar,
                $row->created);
                
            // визуализиране на обобщена информация от лога
        } else {
            $row->created = new ET("<table class='wide-profile-info'><tr><td><div class='name-box'>[#3#]</div>
                                                <div class='date-box'>[#1#]</div></td></tr>
                                                <tr><td class='gravatar-box'>[#2#]</td></tr><tr><td>[#HISTORY#]</td></tr></table>",
                $mvc->getVerbal($rec, 'createdOn'),
                $avatar,
                $row->created);
                
            // визуализиране на обобщена информация от лога
        }
        
        $row->created->append(log_Documents::getSummary($rec->id, $rec->threadId), 'HISTORY');

        if(Mode::is('screenMode', 'narrow')) {
            $row->document = new ET($row->document); 
            $row->document->prepend($row->created);
        }
    }
    

    /**
     * При мобилен изглед оставяме само колонката "документ"
     */
    function on_BeforeRenderListTable($mvc, $tpl, $data)
    {   
        if(Mode::is('screenMode', 'narrow')) {
            $data->listFields = array('document' => 'Документ');
        }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, $data)
    {
        if($data->threadRec->state != 'rejected') {
            $data->toolbar->addBtn('Нов...', array($mvc, 'ShowDocMenu', 'threadId'=>$data->threadId), 'id=btnAdd', 'ef_icon = img/16/star_2.png');
            
            if($data->threadRec->state == 'opened') {
                $data->toolbar->addBtn('Затваряне', array('doc_Threads', 'close', 'threadId'=>$data->threadId), 'ef_icon = img/16/close.png');
            } elseif($data->threadRec->state == 'closed' || empty($data->threadRec->state)) {
                $data->toolbar->addBtn('Отваряне', array('doc_Threads', 'open', 'threadId'=>$data->threadId), 'ef_icon = img/16/open.png');
            }
            $data->toolbar->addBtn('Преместване', array('doc_Threads', 'move', 'threadId'=>$data->threadId, 'ret_url' => TRUE), 'ef_icon = img/16/move.png');
        }
        
        // Ако има права за модифициране на настройките за персоналзиране
        if (doc_Threads::haveRightFor('modify', $data->threadId)) {
            
            // Добавяме бутон в тулбара
            $threadClassId = core_Classes::fetchIdByName('doc_Threads');
            custom_Settings::addBtn($data->toolbar, $threadClassId, $data->threadId, 'Настройки');
        }
    }
    
    
    /**
     * Създава нов контейнер за документ от посочения клас
     * Връща $id на новосъздадения контейнер
     */
    static function create($class, $threadId, $folderId, $createdOn)
    {
        $className = cls::getClassName($class);
        
        $rec = new stdClass();
        $rec->docClass  = core_Classes::fetchIdByName($className);
        $rec->threadId  = $threadId;
        $rec->folderId  = $folderId;
        $rec->createdOn = $createdOn;
        
        self::save($rec);
        
        return $rec->id;
    }
    
    
    /**
     * Обновява информацията в контейнера според информацията в документа
     * Ако в контейнера няма връзка към документ, а само мениджър на документи - създава я
     *
     * @param int $id key(mvc=doc_Containers)
     */
    static function update_($id, $updateAll=TRUE)
    {
        expect($rec = doc_Containers::fetch($id), $id);
        
        $docMvc = cls::get($rec->docClass);
        
        // В записа на контейнера попълваме ключа към документа
        if(!$rec->docId) {
            expect($rec->docId = $docMvc->fetchField("#containerId = {$id}", 'id'));
            $mustSave = TRUE;
        }


        // Обновяването е възможно при следните случаи
        // 1. Създаване на документа, след запис на документа
        // 2. Промяна на състоянието на документа (активиране, оттегляне, възстановяване)
        // 3. Промяна на папката на документа
        
        $fields = 'state,folderId,threadId,containerId,originId';
        
        $docRec = $docMvc->fetch($rec->docId, $fields);
        
        if ($docRec->searchKeywords = $docMvc->getSearchKeywords($docRec->id)) {
            $fields .= ',searchKeywords';
        }
        $updateField = NULL;
        $fieldsArr = arr::make($fields);
        foreach($fieldsArr as $field) {
            
            if (!$updateAll && ($field != 'containerId')) {
                $updateField[$field] = $field;
            }
            
            if($rec->{$field} != $docRec->{$field}) {
                $rec->{$field} = $docRec->{$field};
                $mustSave = TRUE;
            }
        }

        // Дали документа се активира в момента, и кой го активира
        if(empty($rec->activatedBy) && $rec->state != 'draft' && $rec->state != 'rejected') {
            
            $rec->activatedBy = core_Users::getCurrent();
            
            if (!$updateAll) {
                $updateField['activatedBy'] = 'activatedBy';
            }
            
            $flagJustActived = TRUE;
            $mustSave = TRUE;
        }

        if($mustSave) {
            doc_Containers::save($rec, $updateField);

            // Ако този документ носи споделяния на нишката, добавяме ги в списъка с отношения
            if($rec->state != 'draft' && $rec->state != 'rejected') {
                $shared = $docMvc->getShared($rec->docId);
                doc_ThreadUsers::addShared($rec->threadId, $rec->containerId, $shared);
                doc_ThreadUsers::addSubscribed($rec->threadId, $rec->containerId, $rec->createdBy);
            } elseif ($rec->state == 'rejected') {
                doc_ThreadUsers::removeContainer($rec->containerId);
            }
            
            if($rec->threadId && $rec->docId) {
                // Предизвиква обновяване на треда, след всяко обновяване на контейнера
                doc_Threads::updateThread($rec->threadId);
            }
            
            
            // Нотификации на абонираните и споделените потребители
            if($flagJustActived) {
                
                // Масис със споделените потребители
                $sharedArr = keylist::toArray($shared);
                
                // Нотифицираме споделените
                static::addNotifiactions($sharedArr, $docMvc, $rec, 'сподели', FALSE);
                
                // Всички абонирани потребилите
                $subscribed = doc_ThreadUsers::getSubscribed($rec->threadId);
                $subscribedArr = keylist::toArray($subscribed);
                
                // Нотифицираме абонираните потребители
                static::addNotifiactions($subscribedArr, $docMvc, $rec, 'добави');
            }
        }
    }
    
    
    /**
     * Добавя нотификация за съответното действие на потребителите
     * 
     * @param array $usersArr - Масив с потребителите, които да се нотифицират
     * @param core_Mvc $docMvc - Класа на документа
     * @param object $rec - Запис за контейнера
     * @param string $action - Действието
     * @param boolean $checkThreadRight - Дали да се провери за достъп до нишката
     * @param string $priority - Приоритет на нотификацията
     */
    static function addNotifiactions($usersArr, $docMvc, $rec, $action='добави', $checkThreadRight=TRUE, $priority='normal')
    {
        // Ако няма потребители за нотифирциране
        if (!$usersArr) return ;
        
        static $threadTitleArr = array();
        
        // Броя на потребителите, които ще се показват в съобщението на нотификацията
        $maxUsersToShow = 2;
        
        // Масив с нотифицираниете потребители
        // За предпазване от двойно нотифициране
        static $notifiedUsersArr = array();
        
        // Преобразуваме в масив, ако не е
        $usersArr = arr::make($usersArr);
        
        // Ник на текущия потребител
        $currUserNick = core_Users::getCurrent('nick');
        
        // Подготвяме ника
        $currUserNick = core_Users::prepareNick($currUserNick);
        
        // id на текущия потребител
        $currUserId = core_Users::getCurrent();
        
        // Ако заглавието на нишката не е определяна преди
        if (!$threadTitleArr[$rec->threadId]) {
            
            // Определяме заглавието и добавяме в масива
            $threadTitleArr[$rec->threadId] = str::limitLen(doc_Threads::getThreadTitle($rec->threadId, FALSE), doc_Threads::maxLenTitle);
        }
        
        // Кой линк да се използва за изичстване на нотификация
        $url = array('doc_Containers', 'list', 'threadId' => $rec->threadId);
        
        // Къде да сочи линка при натискане на нотификацията
        $customUrl = array($docMvc, 'single', $rec->docId);
        
        // Ако няма да се споделя, а ще се добавя
        if ($action != 'сподели') {
            
            // id на класа
            $threadClassId = doc_Threads::getClassId();
            
            // Вземаме данните
            $noNotificationsUsersArr = custom_Settings::fetchUsers($threadClassId, $rec->threadId, 'notify');
        }
        
        // Обхождаме масива с всички потребители, които ще имат съответната нотификация
        foreach((array)$usersArr as $userId) {
            
            // Ако текущия потребител, е някой от системните, няма да се нотифицира
            if ($userId < 1) continue; 
            
            // Ако потребителя, вече е бил нотифициран
            if ($notifiedUsersArr[$userId]) continue;
            
            // Ако има масив с потребители, които да не се нотифицират
            if ($noNotificationsUsersArr) {
                
                // Ако текущия потребител не трябва да се нотифицира
                if ($noNotificationsUsersArr[$userId] == 'no') continue;
                
                // Ако текущия потребител не трябва да се нотифицира, когато настройката е по-подразбиране
                if ($noNotificationsUsersArr[$userId] != 'yes') {
                    if ($noNotificationsUsersArr[-1] == 'no') continue;
                }
            }
            
            // Ако е зададено да се проверява и няма права до сингъла на нишката, да не се нотифицира
            if ($checkThreadRight && !doc_Threads::haveRightFor('single', $rec->threadId, $userId)) continue;
            
            // Вземаме всички, които са писали в нишката след последното виждане
            $authorArr = static::getLastAuthors($url, $userId, $rec->threadId);
            
            // Ника на текущия потребител
            $currUserNickMsg = $currUserNick;
            
            // Сингъл типа на документиа
            $docTitle = $docMvc->singleTitle;
            
            // Името да е в долния регистър
            $docTitle = mb_strtolower($docTitle);
            
            // Генерираме съобщението
            $message = "{$currUserNickMsg} |{$action}|* |{$docTitle}|*";
            
            // Други добавки от съответния потребител
            $currUserOther = '';
            
            // Ако текущия потребител е добавил повече от един документ
            if ($authorArr[$currUserId] > 1) {
                
                // В зависимост от текста определяме началния текст
                if ($action != 'добави') {
                    $currUserOther = 'и добави';
                } else {
                    $currUserOther = 'и';
                }
                
                // В зависимост от броя документи, определяме текста
                if ($authorArr[$currUserId] == 2) {
                    $currUserOther .= " друг документ";
                } elseif ($authorArr[$currUserId] > 2) {
                    $currUserOther .= " други документи";
                }
                
                // Добавяме текста към съобщението
                $message .= " |{$currUserOther}|*";
            }
            
            // Добавяме останалата част от съобщението
            $message .= " |в|* \"{$threadTitleArr[$rec->threadId]}\"";
            
            // Никове, на другите потребители, които са добавили нещо
            $otherNick = '';
            
            // Да няма допълнителна нотификация за добавени документи от
            // текущия потребител и потребителя, който ще се нотифицира
            unset($authorArr[$currUserId]);
            unset($authorArr[$userId]);
            
            // Флаг, който указва, че има добавени повече от един документ за някой потребител
            $haveMore = FALSE;
            
            // Нулираме брояча
            $usersCount = 0;
            
            // Обхождаме всички останали потребители в масива
            foreach ((array)$authorArr as $author => $count) {
                
                // Увеличаваме брояча
                $usersCount++;
                
                // Ако сме достигнали максималния лимит, прекъсваме
                if ($usersCount > $maxUsersToShow) break;
                
                // Вземаме ника на автора
                $uNick = static::getUserNick($author);
                
                // Ако е добавил повече от един документ, от последтово виждане
                if ($count > 1) {
                    
                    // Вдигаме флага
                    $haveMore = TRUE;
                }
                
                // Добавяме към другите никове
                $otherNick .= $uNick . ', ';
            }
            
            // Ако има други потребители, които са добавили нещо преди последното виждане
            if ($otherNick) {
                
                // Премахваме от края
                $otherNick = rtrim($otherNick, ', ');
                
                // Ограничаваме дължината
                $otherNick = str::limitLen($otherNick, 50);
                
                // Броя на авторите, които са добавили нещо
                $cntAuthorArr = count($authorArr);
                
                // Ако има други, които са добавили документи
                if ($cntAuthorArr > $maxUsersToShow) {
                    
                    // Добавяме съобщението
                    $otherNick .= ' |и други|*';
                }
                
                // В зависимост от броя на документите и авторите, определяме стринга
                if ($cntAuthorArr > 1) {
                    $msgText = 'също добавиха документи';
                } elseif ($haveMore) {
                    $msgText = 'също добави документи';
                } else {
                    $msgText = 'също добави документ';
                }
                
                // Събираме стринга
                $messageN = $message . '. ' . $otherNick . " |{$msgText}";
            } else {
                
                $messageN = $message;
            }

            // Нотифицираме потребителя
            bgerp_Notifications::add($messageN, $url, $userId, $priority, $customUrl);
            
            // Добавяме в масива, за да не се нотифицара повече
            $notifiedUsersArr[$userId] = $userId;
        }
    }
    
    
    /**
     * Връща ника за съответния потребител
     * 
     * @param integer $userId - id на потребител
     * 
     * @return string
     */
    static function getUserNick($userId)
    {
        // Вземаме ника на потребителя
        $nick = core_Users::getNick($userId);
        
        // Обработваме ника
        $nick = core_Users::prepareNick($nick);
        
        return $nick;
    }
    
    
    /**
     * Връща масив с всички потребители( и броя на документите),
     * които са писали след последното виждане
     * от съответния потребител
     * 
     * @param array $url
     * @param integer $userId
     * @param integer $threadId
     * 
     * @return array
     */
    static function getLastAuthors($url, $userId, $threadId)
    {
        // Време на последното виждане, за съответния потребител
        $lastClosedOn = bgerp_Notifications::getLastClosedTime($url, $userId);
        
        // Ако няма време на последно затваряне
        if (!$lastClosedOn) {
            
            // Вадим от текущото време, зададените секунди за търсене преди
            $lastClosedOn = dt::subtractSecs(bgerp_Notifications::NOTIFICATIONS_LAST_CLOSED_BEFORE);
        }
        
        // Вземаме всички записи
        // Които не са чернови или оттеглени
        // И са променени след последното разглеждане
        $query = static::getQuery();
        $query->where(array("#modifiedOn > '[#1#]'", $lastClosedOn));
        $query->where(array("#threadId = '[#1#]'", $threadId));
        $query->where("#state != 'draft'");
        $query->where("#state != 'rejected'");
        $query->orderBy('modifiedOn', 'DESC');
        
        // Масив с потребителите
        $authorArr = array();
        
        while($rec = $query->fetch()) {
            
            // Увеличаваме броя на документите за съответния потребител, който е активирал документа
            $authorArr[$rec->activatedBy]++;
        }
        
        return $authorArr;
    }
    
    
    /**
     * Проверява дали има документ в нишката след подадената дата от съответния клас
     * 
     * @param integer $threadId
     * @param date $date
     * @param integer $classId
     */
    static function haveDocsAfter($threadId, $date=NULL, $classId=NULL)
    {
        // Ако не е подадена дата, да се използва текущото време
        if (!$date) {
            $date = dt::now();
        }
        
        // Първия документ, в нишката, който не е оттеглен
        $query = static::getQuery();
        $query->where(array("#threadId = '[#1#]'", $threadId));
        $query->where("#state != 'rejected'");
        
        // Създадене след съответната дата
        $query->where(array("#createdOn > '[#1#]'", $date));
        
        // Ако е зададен от кой клас да е документа
        if ($classId) {
            $query->where(array("#docClass = '[#1#]'", $classId));
        }
        
        $rec = $query->fetch();
        
        return $rec;
    }
    
    
    /**
     * Връща обект-пълномощник приведен към зададен интерфейс
     *
     * @param mixed int key(mvc=doc_Containers) или обект с docId и docClass
     * @param string $intf
     * @return core_ObjectReference
     */
    static function getDocument($id, $intf = NULL)
    {
        if (!is_object($id)) {
            $rec = doc_Containers::fetch($id, 'docId, docClass');
            
            // Ако няма id на документ, изчакваме една-две секунди, 
            // защото може този документ да се създава точно в този момент
            if(!$rec->docId) sleep((int)'BGERP_DOCUMENT_SLEEP_TIME');
            $rec = doc_Containers::fetch($id, 'docId, docClass');
            
            if(!$rec->docId) sleep((int)'BGERP_DOCUMENT_SLEEP_TIME');
            $rec = doc_Containers::fetch($id, 'docId, docClass');
        } else {
            $rec = $id;
        }
        
        expect($rec->docClass);
        expect($rec->docId);
        
        return new core_ObjectReference($rec->docClass, $rec->docId, $intf);
    }
    
    
    /**
     * Документ по зададен хендъл
     * 
     * @param string $handle Inv478, Eml57 и т.н.
     * @param string $intf интерфейс
     * @return core_ObjectReference
     */
    static function getDocumentByHandle($handle, $intf = NULL)
    {
        if (!is_array($handle)) {
            $handle = self::parseHandle($handle);
        }
        
        if (!$handle) {
            // Невалиден хендъл
            return FALSE;
        }
        
        //Проверяваме дали сме открили клас. Ако не - връщаме FALSE
        if (!$mvc = self::getClassByAbbr($handle['abbr'])) {
            return FALSE;
        }
        
        //Ако нямаме запис за съответното $id връщаме FALSE
        if (!$docRec = $mvc::fetchByHandle($handle)) {
            return FALSE;
        }
        
        //Проверяваме дали имаме права за single. Ако не - FALSE
        if (!$mvc->haveRightFor('single', $docRec)) {
            return FALSE;
        }
        
        return static::getDocument((object)array('docClass'=>$mvc, 'docId'=>$docRec->id), $intf);
    }
    
    protected static function parseHandle($handle)
    {
        $handle = trim($handle);
        
        if (!preg_match("/(?'abbr'[a-z]{1,3})(?'id'[0-9]{1,10})/i", $handle, $matches)) {
            return FALSE;
        }
        
        return $matches;
    }
    
    
    /**
     * Намира контейнер на документ по негов манипулатор.
     *
     * @param string $handle манипулатор на документ
     * @return int key(mvc=doc_Containers) NULL ако няма съответен на манипулатора контейнер
     */
    public static function getByHandle($handle)
    {
        $id = static::fetchField(array("#handle = '[#1#]'", $handle), 'id');
        
        if (!$id) {
            $id = NULL;
        }
        
        return $id;
    }


    /**
     * Потребителите, с които е споделен документ
     *
     * @param int $id key(mvc=doc_Containers) първ. ключ на контейнера на документа
     * @return string keylist(mvc=core_Users)
     * @see doc_DocumentIntf::getShared()
     */
    public static function getShared($id)
    {
        $doc = static::getDocument($id, 'doc_DocumentIntf');
        
        return $doc->getShared();
    }
    
    
    /**
     * Състоянието на документ
     *
     * @param int $id key(mvc=doc_Containers) първ. ключ на контейнера на документа
     * @return string състоянието на документа
     */
    public static function getDocState($id)
    {
        $doc = static::getDocument($id, 'doc_DocumentIntf');
        
        $row = $doc->getDocumentRow();
        
        return $row->state;
    }



    /**
     * Връща заглавието на документ
     */
    static function getDocTitle($id) 
    {
        $doc = static::getDocument($id, 'doc_DocumentIntf');
        
        try {
            $docRow = $doc->getDocumentRow();
            $title = $docRow->title;
        }  catch (core_Exception_Expect $expect) {
            $title = '?????????????????????????????????????';
        }
        
        return $title;
    }
    
    
    /**
     * Екшън за активиране на постинги
     */
    function act_Activate()
    {
        $containerId = Request::get('containerId', 'int');
        
        //Очакваме да име
        expect($containerId);
        
        //Документна
        $document = doc_Containers::getDocument($containerId);
        $class = $document->className;
        
        // Инстанция на класа
        $clsInst = cls::get($class);
        
        // Очакваме да има такъв запис
        expect($rec = $class::fetch("#containerId='{$containerId}'"));
        
        // Очакваме потребителя да има права за активиране
        $clsInst->requireRightFor('activate', $rec);
        
        //Променяме състоянието
        $recAct = new stdClass();
        $recAct->id = $rec->id;
        $recAct->state = 'active';
        
        // Извикваме фунцкията
        $clsInst->invoke('BeforeActivation', array(&$recAct));
        
        //Записваме данните в БД
        $clsInst->save($recAct);
        
        $rec->state = 'active';
        $clsInst->invoke('AfterActivation', array(&$rec));
        
        //Редиректваме към сингъла на съответния клас, от къде се прехвърляме 		//към треда
        redirect(array($class, 'single', $rec->id));
    }
    
    
    /**
     * Показва меню от възможности за добавяне на нови документи,
     * достъпни за дадената нишка. Очаква threadId
     */
    function act_ShowDocMenu()
    {
        expect($threadId = Request::get('threadId', 'int'));
        
        doc_Threads::requireRightFor('newdoc', $threadId);
        
        $rec = (object) array('threadId' => $threadId);
        
        $tpl = doc_Containers::getNewDocMenu($rec);
       	
        return $this->renderWrapping($tpl);
    }



    /**
     * Връща акордеаон-меню за добавяне на нови документи
     * Очаква или $rec->threadId или $rec->folderId
     */
    static function getNewDocMenu($rec)
    {
        // Определяме заглавието на нишката или папката
        if ($rec->threadId) {
            $thRec = doc_Threads::fetch($rec->threadId);
            $title = doc_Threads::recToVerbal($thRec)->onlyTitle;
        } else {
            $title = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
        }

        // Извличане на потенциалните класове на нови документи
        $docArr = core_Classes::getOptionsByInterface('doc_DocumentIntf');
 
        if(is_array($docArr) && count($docArr)) {
            foreach($docArr as $id => $class) {
                    
                $mvc = cls::get($class);
                
                list($order, $group) = explode('|', $mvc->newBtnGroup);

                if($mvc->haveRightFor('add', $rec)) {
                    $ind = $order*10000 + $i++;
                    $docArrSort[$ind] = array($group, $mvc->singleTitle, $class);
                }
            }
            
            // Сортиране
            ksort($docArrSort);

            // Групиране
            foreach($docArrSort as $id => $arr) {
                $btns[$arr[0]][$arr[1]] = $arr[2];
            }
        
            // Генериране на изгледа
            $tpl = new ET();
            
            // Ако сме в нишка
            if ($rec->threadId) {
                $text = tr("Нов документ в") . " " . $title;
            } else {
                $text = tr("Нова тема в") . " " . $title;
            }
            
            $tpl->append("\n<div class='listTitle'>" . $text . "</div>");
            $tpl->append("<div class='accordian noSelect'><ul>");
            
            $active = 'active';
            
            foreach($btns as $group => $bArr) {
                
                // Превеждаме групата
                $group = tr($group);
                
                $tpl->append("<li class='btns-title {$active} '><img class='btns-icon plus' src=". sbf('img/16/toggle1.png') ."><img class='btns-icon minus' src=". sbf('img/16/toggle2.png') .">&nbsp;{$group}</li>");
                $tpl->append("<li class='dimension'>");
                foreach($bArr as $btn => $class) {
                    $mvc = cls::get($class);
                    
                    $tpl->append(new ET("<div class='btn-group'>[#1#]</div>", ht::createBtn($mvc->singleTitle, 
                        array($class, 'add', 
                            'threadId' => $rec->threadId, 'folderId' => $rec->folderId, 'ret_url' => TRUE), 
                            NULL, NULL, "class=linkWithIcon,style=background-image:url(" . sbf($mvc->singleIcon, '') . ");width:100%;text-align:left;")));
                }
                
                $tpl->append("</li>"); 
                $active = '';
            }

            $tpl->append("</ul></div>");
            
            $tpl->push('doc/tpl/style.css', 'CSS');
            $tpl->push('doc/js/accordion.js', 'JS');
            jquery_Jquery::run($tpl, "accordionRenderAndCollapse();");
        } else {

            $tpl = tr("Няма възможност за добавяне на документ в") . " " . $title;
        }

        return $tpl;
    }
    
    
    /**
     * Връща абревиатурата на всички класов, които имплементират doc_DocumentIntf
     */
    static function getAbbr()
    {
        if (!self::$abbrArr) {
            self::setAbrr();
        }
        
        return self::$abbrArr;
    }
    
    
    /**
     * Задава абревиатурата на всички класов, които имплементират doc_DocumentIntf
     */
    static function setAbrr()
    {
        //Проверяваме дали записа фигурира в кеша
        $abbrArr = core_Cache::get('abbr', 'allClass', 1440, array('core_Classes', 'core_Interfaces'));
        
        //Ако няма
        if (!$abbrArr) {
            
            $docClasses = core_Classes::getOptionsByInterface('doc_DocumentIntf');

            //Обикаляме всички записи, които имплементират doc_DocumentInrf
            foreach ($docClasses as $id => $className) {
                
                //Създаваме инстанция на класа в масив
                $instanceArr[$id] = cls::get($className);
                
                $abbr = strtoupper($instanceArr[$id]->abbr);
                
                // Ако сме в дебъг режим
                if (isDebug()) {
                    
//                    expect(trim($instanceArr[$id]->abbr), $instanceArr[$id]);
                    expect(!$abbrArr[$abbr], $abbr, $abbrArr[$abbr], $className);
                }
                
                // Ако няма абревиатура
                if (!trim($abbr)) continue;
                
                //Създаваме масив с абревиатурата и името на класа                
                $abbrArr[$abbr] = $className;
            }
            
            //Записваме масива в кеша
            core_Cache::set('abbr', 'allClass', $abbrArr, 1440, array('core_Classes', 'core_Interfaces'));
        }
        
        self::$abbrArr = $abbrArr;
    }
    
    
    /**
     * Връща езика на контейнера
     *
     * @param int $id - id' то на контейнера
     *
     * @return string $lg - Двубуквеното означение на предполагаемия език на имейла
     */
    static function getLanguage($id)
    {
        //Ако няма стойност, връщаме
        if (!$id) return ;
        
        //Записите на контейнера
        $doc = doc_Containers::getDocument($id);
        
        //Вземаме записите на класа
        $docRec = $doc->fetch();
        
        if($docRec->textPart) {

            $lg = i18n_Language::detect($docRec->textPart);
          
        } else {
            $lg = $docRec->lg;
        }

        //Връщаме езика
        return $lg;
    }
    
    
    /**
     * Връща линка на папката във вербален вид
     * 
     * @param array $params - Масив с частите на линка
     * @param $params['Ctr'] - Контролера
     * @param $params['Act'] - Действието
     * @param $params['id'] - id' то на сингъла
     * 
     * @return $res - Линк
     */
    static function getVerbalLink($params)
    {
        try {
            
            // Опитваме се да вземем инстанция на класа
            $ctrInst = cls::get($params['Ctr']);
            
            // Ако метода съществува в съответия клас
            if (method_exists($ctrInst, 'getVerbalLinkFromClass')) {
                
                // Вземаме линковете от класа
                $res = $ctrInst->getVerbalLinkFromClass($params['id']); 

                return $res;
            }
                
             // Проверяваме дали е число
            expect(is_numeric($params['id']));
            
            // Вземаме записите
            $rec = $ctrInst->fetch($params['id']);
            
            // Кое поле е избрано да се показва, като текст
            $field = $ctrInst->rowToolsSingleField;

            // Очакваме да имаме права за съответния екшън
            expect($rec && $ctrInst->haveRightFor('single', $rec));
        } catch (core_exception_Expect $e) {
            
            // Ако възникне някаква греша
            return FALSE;
        }
        
        // Ако не е зададено поле
        if ($field) {
            
            // Стойността на полето на текстовата част
            $title = $ctrInst->getVerbal($params['id'], $field);
        } else {
            
            // Използваме името на модула
            $title = ($ctrInst->singleTitle) ? $ctrInst->singleTitle : $ctrInst->title;
            
            // Добавяме id на фирмата
            $title .= ' #' . $rec->id;
        }
        
        // Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
        $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
        
        // Иконата на класа
        $sbfIcon = sbf($ctrInst->singleIcon, '"', $isAbsolute);

        // Ако мода е xhtml
        if (Mode::is('text', 'xhtml')) {
            
            $res = new ET("<span class='linkWithIcon' style='background-image:url({$sbfIcon});'> [#1#] </span>", $title);
        } elseif (Mode::is('text', 'plain')) {
            
            // Ескейпваме плейсхолдърите и връщаме титлата
            $res = core_ET::escape($title);
        } else {
            
            //Атрибути на линка
            $attr['class'] = 'linkWithIcon';
            $attr['style'] = "background-image:url({$sbfIcon});";    
            $attr['target'] = '_blank';    
            
            //Създаваме линк
            $res = ht::createLink($title, $params, NULL, $attr); 
        }
        
        return $res;
    }
    
    
    public static function getClassByAbbr($abbr)
    {
        $abbrArr = static::getAbbr();
        $abbr    = strtoupper($abbr);
        
        foreach ($abbrArr as $a=>$className) {
            if (strtoupper($a) == $abbr) {
                $docManager = cls::get($className);
                
                expect(cls::haveInterface('doc_DocumentIntf', $docManager));
                
                return $docManager;
            }
        }
        
        return NULL;
    }


    function repair()
    {
        $query = $this->getQuery();
        
        while($rec = $query->fetch()) {
            if(!$rec->threadId) {
                $err[$rec->id] .= 'Missing threadId; ';
            }
            if(!$rec->folderId) {
                $err[$rec->id] .= 'Missing folderId; ';
            }

            if($rec->folderId && !doc_Folders::fetch($rec->folderId, 'id')) {
                $err[$rec->id] .= 'Missing folder;';
                $tRec = doc_Threads::fetch($rec->threadId);

                $rec->folderId = 291;
                $this->save($rec);

                $tRec->folderId = 291;
                doc_Threads::save($tRec);
            }
            if(!$rec->docClass) {
                $err[$rec->id] .= 'Missing docClass; ';
            }
            if(!core_Classes::fetch("#id = {$rec->docClass} && #state = 'active'")) {
                $err[$rec->id] .= 'Not exists docClass; ';
            } else {
                
                // Ако няма docId
                if(!$rec->docId) {
                    
                    // Ако има клас
                    if ($rec->docClass) {
                        
                        // Инстанция на класа
                        $docClass = cls::get($rec->docClass);
                        
                        // Вземаме docId от документа със съответноя контейнер
                        $rec->docId = $docClass->fetchField("#containerId = '{$rec->id}'", 'id');
                        
                        // Ако има docId
                        if ($rec->docId) {
                            
                            // Опитваме се да го запишем
                            if (static::save($rec, 'docId')) {
                                
                                // Добавяме съобщение
                                $err[$rec->id] .= "Repaired docId = '{$rec->docId}' from class '{$docClass->className}'; ";    
                            } else {
                                
                                // Добавяме съобщение, ако евентуално не може да се запише
                                $err[$rec->id] .= "Can' t save docId = '{$rec->docId}' from class '{$docClass->className}'; ";
                            }
                        } else {
                            
                            // Ако не можем да определим docId
                            $err[$rec->id] .= "Can' t repair docClass = '{$rec->docClass}'; ";
                        }
                    } else {
                        
                        // Ако няма клас, няма как да се определи
                        $err[$rec->id] .= 'Not exists docId; ';    
                    }
                } else {

                    $cls = cls::get($rec->docClass);

                    if(!$cls->fetch($rec->docId)) {
                        $err[$rec->id] .= 'Not exists document; ';
                    }
                }
            }
        }
        
        if(count($err)) {
            foreach($err as $id => $msg) {
                $res .= "<li> $id => $msg </li>";
            }
        }

        return $res;
    }
    
    
    /**
     * Проверява дали даден тип документ, се съдържа в нишката
     * 
     * @param integer $threadId - id от doc_Threads
     * @param sting $documentName - Името на класа на документа
     */
    static function checkDocumentExistInThread($threadId, $documentName) 
    {
        // Името на документа с малки букви
        $documentName = strtolower($documentName);
        
        // Вземаме id' то на класа
        $documentClassId = core_Classes::fetch("LOWER(#name) = '{$documentName}'")->id;
        
        // Ако има такъв запис, връщаме TRUE
        return (boolean)static::fetch("#threadId = '{$threadId}' AND #docClass = '{$documentClassId}' AND #state != 'rejected'");
    }
    
    
    /**
     * 
     */
    public static function on_AfterSave($mvc, &$id, $rec)
    {
        // Обновяваме записите за файловете
        doc_Files::updateRec($rec);
        
        // Обновяваме записите за обръщенията
        email_Salutations::updateRec($rec);
    }
    
    
    /**
     * Оттегля всички контейнери в зададена нишка. Това включва оттеглянето и на реалните документи,
     * съдържащи се в контейнерите.
     * 
     * @param int $threadId
     * @return array $rejectedIds - ид-та на документите оттеглени, при оттеглянето на треда
     */
    public static function rejectByThread($threadId)
    {
        $query = static::getQuery();
        
        $query->where("#threadId = {$threadId}");
        $query->where("#state <> 'rejected'");
        
        // Подреждаме ги последно модифициране
        $query->orderBy("#modifiedOn" , 'DESC');
        
        $rejectedIds = array();
        while ($rec = $query->fetch()) {
            try{
            	$doc = static::getDocument($rec);
            	$doc->reject();
            } catch(Exception $e){
            	continue;
            }
            
            // Запомняме ид-та на контейнерите, които сме оттеглили
            $rejectedIds[] = $rec->id;
        }
       
        return $rejectedIds;
    }
    
    
    /**
     * Възстановява всички контейнери в зададена нишка. Това включва възстановяването и на 
     * реалните документи, съдържащи се в контейнерите.
     * 
     * @param int $threadId
     */
    public static function restoreByThread($threadId)
    {
        // При възстановяване на треда, гледаме кои контейнери са били оттеглени със него
    	$rejectedInThread = doc_Threads::fetchField($threadId, 'rejectedContainersInThread');
        
        /* @var $query core_Query */
        $query = static::getQuery();
        
        $query->where("#threadId = {$threadId}");
        $query->where("#state = 'rejected'");
        $query->orderBy("#id", ASC);
        
        // Ако има документи оттеглени със треда
        if(count($rejectedInThread)){
        	
        	// Възстановяваме само тези контейнери от тях
        	$query->in('id', $rejectedInThread);
        	$recs = $query->fetchAll();
			$recs = array_replace(array_flip($rejectedInThread), $recs);
		} else {
			$recs = $query->fetchAll();
		}
        
        if(count($recs)){
        	foreach ($recs as $rec){
        		try{
        			$doc = static::getDocument($rec);
        			$doc->restore();
        		} catch(Exception $e){
        			continue;
        		}
        	}
        }
    }
    
    
    /**
     * Връща контрагент данните на контейнера
     */
    static function getContragentData($id)
    {
        // Записа
        $rec = static::fetch($id);
        
        // Класа
        $class = cls::get($rec->docClass);
        
        // Контрагент данните
        $contragentData = $class::getContragentData($rec->docId);

        return $contragentData;
    }
    
    
    /**
     * Връща линк към сингъла на документа
     * 
     * @param int $id - id на документа
     * 
     * @return string - Линк към документа
     */
    static function getLinkForSingle($id)
    {
        // Ако не е чило, връщаме
        if (!is_numeric($id)) return ;

        // Документа
        $doc = doc_Containers::getDocument($id);
        
        // Полетата на документа във вербален вид
        $docRow = $doc->getDocumentRow();
        
        // Ако има права за сингъла на документа
        if ($doc->instance->haveRightFor('single', $doc->that)) {
            
            // Да е линк към сингъла
            $url = array($doc, 'single', $doc->that);
        } else {
            
            // Ако няма права, да не е линк
            $url = array();
        }
        
        // Атрибутеите на линка
        $attr['class'] = 'linkWithIcon';
        $attr['style'] = 'background-image:url(' . sbf($doc->getIcon($doc->that)) . ');';
        $attr['title'] = tr('Документ') . ': ' . $docRow->title;
        
        // Документа да е линк към single' а на документа
        $res = ht::createLink(str::limitLen($docRow->title, 35), $url, NULL, $attr);
        
        return $res;
    }
    
    
    /**
     * Връща масив с всички id' та на документите в нишката
     * 
     * @param mixed $thread - id на нишка или масив с id-та на нишка
     * @param string $state - Състоянието на документите
     * @param string $order - ASC или DESC подредба по дата на модифициране или да не се подреждат
     * 
     * @return array - Двумерен масив с нишките и документите в тях
     */
    static function getAllDocIdFromThread($thread, $state=NULL, $order=NULL)
    {
        $arr = array();
        
        // Вземаме всички документи от нишката
        $query = static::getQuery();
        
        // Ако е подаден масив
        if (is_array($thread)) {
            
            // За всички нишки
            $query->orWhereArr("threadId", $thread);
        } else {
            
            // За съответната нишка
            $query->where("#threadId = '{$thread}'");
        }
        
        // Ако е зададено състояние
        if ($state) {
            
            // Да се вземат документи от съответното състояние
            $query->where(array("#state = '[#1#]'", $state));
        }
        
        // Ако състоянието не е оттеглено
        if ($state != 'rejected') {
            
            // Оттеглените документи да не се вземат в предвид
            $query->where(array("#state != 'rejected'"));
        }
        
        // Ако е зададена подреба
        if ($order) {
            
            // Използваме я
            $query->orderBy('modifiedOn', $order);
        }
        
        // Обхождаме резултатите
        while ($rec = $query->fetch()) {
            
            // Ако няма клас или документ - прескачаме
            if (!$rec->docClass || !$rec->docId) continue;
            
            // Инстанция на класа
            $cls = core_Cls::get($rec->docClass);
            
            // Ако нямаме права за сингъла на документа, прескачаме
            if (!$cls->haveRightFor('single', $rec->docId)) continue;
            
            // Добавяме в масива
            $arr[$rec->threadId][$rec->id] = $rec;
        }
        
        return $arr;
    }
    
    
    /**
     * Връща URL за добавяне на документ
     * 
     * @param string $callback
     * 
     * @return URL
     */
    static function getUrLForAddDoc($callback)
    {
        // Защитаваме променливите
        Request::setProtected('callback');
        
        // Задаваме линка
        $url = array('doc_Containers', 'AddDoc', 'callback' => $callback);
        
        return toUrl($url);
    }
    
    
    /**
     * Екшън, който редиректва към качването на файл в съответния таб
     */
    function act_AddDoc()
    {
        $callback = Request::get('callback', 'identifier');
        
        // Защитаваме променливите
        Request::setProtected('callback');
        
        // Името на класа
        $class = 'doc_Log';
        
        // Вземаме екшъна
        $act = 'addDocDialog';
        
        // URL-то да сочи към съответния екшън и клас
        $url = array($class, $act, 'callback' => $callback);
        
        return new Redirect($url);
    }
    
    
    /**
     * Нотифицира за неизпратени имейли или чернови документи
     */
    function cron_notifyForIncompleteDoc()
    {
        $this->notifyForIncompleteDoc();
    }
    
    
    /**
     * Нотифицира за неизпратени имейли или чернови документи
     */
    function notifyForIncompleteDoc()
    {
        // Конфигураця
        $conf = core_Packs::getConfig('doc');
        
        // Текущото време
        $now = dt::now();
        
        // Масив с датите между които ще се извлича
        $dateRange = array();
        $dateRange[0] = dt::subtractSecs($conf->DOC_NOTIFY_FOR_INCOMPLETE_FROM, $now); 
        $dateRange[1] = dt::subtractSecs($conf->DOC_NOTIFY_FOR_INCOMPLETE_TO, $now); 
        
        // Подреждаме масива
        sort($dateRange);
        
        // Всички документи създадени от потребителите и редактирани между датите
        $query = static::getQuery();
        $query->where(array("#modifiedOn >= '[#1#]'", $dateRange[0]));
        $query->where(array("#modifiedOn <= '[#1#]'", $dateRange[1]));
        $query->where("#createdBy > 0");
        
        // Инстанция на класа
        $Outgoings = cls::get('email_Outgoings');
        
        // id на класа
        $outgoingsClassId = $Outgoings->getClassId();
        
        // Само черновите
        $query->where("#state = 'draft'");
        
        // Или, ако са имейли, активните
        $query->orWhere(array("#state = 'active' AND #docClass = '[#1#]'", $outgoingsClassId));
        
        // Групираме по създаване и състояние
        $query->groupBy('createdBy, state');
        
        while ($rec = $query->fetch()) {
            
            // Масив с екипите на съответния потребител
            $authorTemasArr[$rec->createdBy] = type_Users::getUserFromTeams($rec->createdBy);
            
            // Вземаме първия екип, в който участва
            $firstTeamAuthor = key($authorTemasArr[$rec->createdBy]);
            
            // URL-то което ще служи за премахване на нотификацията
            $urlArr = array('doc_Search', 'state' => $rec->state, 'author' => $rec->createdBy);
            
            // Ако е чернова
            if ($rec->state == 'draft') {
                
                // Съобщение
                $message = "|Имате създадени, но неактивирани документи";
                
                // Линк, където ще сочи нотификацията
                $customUrl = array('doc_Search', 'state' => 'draft', 'author' => $firstTeamAuthor);
            } else {
                
                // Съобщение
                $message = "|Активирани, но неизпратени имейли";
                
                // Линк, където ще сочи нотификацията
                $customUrl = array('doc_Search', 'state' => 'active', 'docClass' => $outgoingsClassId, 'author' => $firstTeamAuthor);
            }
            
            // Добавяме нотификация
            bgerp_Notifications::add($message, $urlArr, $rec->createdBy, 'normal', $customUrl);
        }
        
        return ;
    }
    
    
	/**
     * Изпълнява се след създаването на модела
	 * 
	 * @param unknown_type $mvc
	 * @param unknown_type $res
	 */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        //Данни за работата на cron
        $rec = new stdClass();
        $rec->systemId = 'notifyForIncompleteDoc';
        $rec->description = 'Нотифициране за незавършени действия с документите';
        $rec->controller = $mvc->className;
        $rec->action = 'notifyForIncompleteDoc';
        $rec->period = 60;
        $rec->offset = 0;
        $rec->delay = 0;
        $rec->timeLimit = 200;
        $res .= core_Cron::addOnce($rec);
    }
}
