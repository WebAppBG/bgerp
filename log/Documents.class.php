<?php 


/**
 * История от събития, свързани с документите
 *
 * Събитията са изпращане по имейл, получаване, връщане, печат, разглеждане
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class log_Documents extends core_Manager
{
    
    
    /**
     * Заглавие на таблицата
     */
    var $title = "Лог на документи";
    
    
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
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'log_Wrapper,  plg_Created';
    
    
    /**
     * @todo Чака за документация...
     */
    var $listFields = 'createdOn, createdBy, action=Какво, containerId=Кое, dataBlob';
    
    var $listFieldsSet = array(
        self::ACTION_SEND  => 'createdOn=Дата, createdBy=Потребител, containerId=Кое, toEmail=До, cc=Кп, receivedOn=Получено, returnedOn=Върнато',
        self::ACTION_PRINT => 'createdOn=Дата, createdBy=Потребител, containerId=Кое, action=Действие, seenOnTime=Видяно',
        self::ACTION_OPEN => 'seenOnTime=Дата, seenFromIp=IP, reason=Основание',
        self::ACTION_DOWNLOAD => 'fileHnd=Файл, seenOnTime=Свалено->На, seenFromIp=Свалено->От',
    );
    
    /**
     * Масов-кеш за историите на контейнерите по нишки
     *
     * @var array
     */
    protected static $histories = array();
    
    
    /**
     * Домейн на записите в кеша
     *
     * @see core_Cache
     */
    const CACHE_TYPE = 'thread_history';
    
    const ACTION_SEND    = 'send';
    const ACTION_RETURN  = '_returned';
    const ACTION_RECEIVE = '_received';
    const ACTION_OPEN    = 'open';
    const ACTION_PRINT   = 'print';
    const ACTION_DISPLAY = 'display';
    const ACTION_FAX     = 'fax';
    const ACTION_PDF     = 'pdf';
    const ACTION_DOWNLOAD = 'download';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $actionsEnum = array(
            self::ACTION_SEND    . '=имейл',
            self::ACTION_RETURN  . '=връщане',
            self::ACTION_RECEIVE . '=получаване',
            self::ACTION_OPEN    . '=показване',
            self::ACTION_PRINT   . '=отпечатване',
            self::ACTION_DISPLAY . '=разглеждане',
            self::ACTION_FAX     . '=факс',
            self::ACTION_PDF     . '=PDF',
            self::ACTION_DOWNLOAD . '=сваляне',
        );
        
        // Тип на събитието
        $this->FLD("action", 'enum(' . implode(',', $actionsEnum) . ')', "caption=Действие");
        
        // Нишка на документа, за който се отнася събитието
        $this->FLD('threadId', 'key(mvc=doc_Threads)', 'caption=Нишка');
        
        // Документ, за който се отнася събитието
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'caption=Контейнер');
        
        // MID на документа
        $this->FLD('mid', 'varchar', 'input=none,caption=Ключ,column=none');
        
        $this->FLD('parentId', 'key(mvc=log_Documents, select=action)', 'input=none,caption=Основание');
        
//         $this->FLD('baseParentId', 'key(mvc=log_Documents, select=action)', 'input=none,caption=Основание');
        
        // Допълнителни обстоятелства, в зависимост от събитието (в PHP serialize() формат)
        $this->FLD("dataBlob", "blob", 'caption=Обстоятелства,column=none');
        
        $this->FNC('data', 'text', 'input=none,column=none');
        $this->FNC('seenOnTime', 'datetime(format=smartTime)', 'input=none');
        $this->FNC('seenFrom', 'key(mvc=core_Users)', 'input=none');
        $this->FNC('receivedOn', 'datetime(format=smartTime)', 'input=none');
        $this->FNC('returnedOn', 'datetime(format=smartTime)', 'input=none');
        $this->FNC('seenFromIp', 'ip', 'input=none');
        $this->FNC('reason', 'html', 'input=none');
        
        $this->setDbIndex('containerId');
        $this->setDbUnique('mid');
    }
    
    
    function on_CalcData($mvc, $rec)
    {
        $rec->data = @unserialize($rec->dataBlob);
        if (empty($rec->data)) {
            $rec->data = new StdClass();
        }
    }
    

    function on_CalcReceivedOn($mvc, $rec)
    {
		if ($rec->action == static::ACTION_SEND && !empty($rec->data->receivedOn)) {
			$rec->receivedOn = $rec->data->receivedOn;
		}
    }
    

    function on_CalcReturnedOn($mvc, $rec)
    {
		if ($rec->action == static::ACTION_SEND && !empty($rec->data->returnedOn)) {
			$rec->returnedOn = $rec->data->returnedOn;
		}
    }

    
    public static function saveAction($actionData)
    {
        $rec = (object)array_merge((array)static::getAction(), (array)$actionData);
        
        if (empty($rec->parentId)) {
            if (($parentAction = static::getAction(-1)) && !empty($parentAction->id) ) {
                $rec->parentId = $parentAction->id;
            }
        }
        
        expect($rec->containerId && $rec->action);
        
        if (empty($rec->threadId)) {
            expect($rec->threadId = doc_Containers::fetchField($rec->containerId, 'threadId'));
        }

        if (!$rec->mid && !in_array($rec->action, array(self::ACTION_DISPLAY, self::ACTION_RECEIVE, self::ACTION_RETURN, self::ACTION_DOWNLOAD))) {
            $rec->mid = static::generateMid();
        }
        
        /*
         * Забележка: plg_Created ще попълни полетата createdBy (кой е отпечатал документа) и
         *             createdOn (кога е станало това)
         */
        
        if (static::save($rec)) {
			// Milen: Това какво прави? Супер неясно глобално предаване на параметри!!!
			if(static::getAction()) {
				static::getAction()->id = $rec->id;
			}
            
            return $rec->mid;
        }
        
        return FALSE;
    }
    
    
    public static function pushAction($actionData)
    {
        Mode::push('action', (object)$actionData);
    }
    
    
    public static function popAction()
    {
        if ($action = static::getAction()) {
            Mode::pop('action');
        }
        
        return $action;
    }

    
    public static function getAction($offset = 0)
    {
        return Mode::get('action', $offset);
    }

    
    public static function hasAction()
    {
        return Mode::get('action');
    }
    
    
    /**
     * Достъпност на документ от не-идентифицирани посетители
     * 
     * @param int $cid key(mvc=doc_Containers)
     * @param string $mid
     * @return object|boolean запис на модела или FALSE
     * 
     */
    public static function fetchHistoryFor($cid, $mid)
    {
        return static::fetch(array("#mid = '[#1#]' AND #containerId = [#2#]", $mid, $cid));
    }
    
    
    protected static function fetchByMid($mid)
    {
        return static::fetch(array("#mid = '[#1#]'", $mid));
    }


    protected static function fetchByCid($cid)
    {
        return static::fetch(array("#containerId = [#1#]", $cid));
    }


    public static function returned($mid, $date = NULL)
    {
        if (!($sendRec = static::getActionRecForMid($mid, static::ACTION_SEND))) {
            // Няма изпращане с такъв MID
            return FALSE;
        }
    
        if (!empty($sendRec->data->returnedOn)) {
            // Връщането на писмото вече е било отразено в историята; не правим нищо
            return TRUE;
        }

        if (!isset($date)) {
            $date = dt::now();
        }
        
        expect(is_object($sendRec->data), $sendRec);
    
        $sendRec->data->returnedOn = $date;
    
        static::save($sendRec);
    
        $retRec = (object)array(
            'action' => static::ACTION_RETURN,
            'containerId' => $sendRec->containerId,
            'threadId'    => $sendRec->threadId,
            'parentId'    => $sendRec->id
        );
    
        static::save($retRec);

        $msg = "Върнато писмо: " . doc_Containers::getDocTitle($sendRec->containerId);
    
        // Нотификация за връщането на писмото до изпращача му
        bgerp_Notifications::add(
            $msg, // съобщение
            array('log_Documents', 'list', 'containerId' => $sendRec->containerId), // URL
            $sendRec->createdBy, // получател на нотификацията
            'alert' // Важност (приоритет)
        );
    
        core_Logs::add(get_called_class(), $sendRec->id, $msg);
        
        return TRUE;
    }


    public static function received($mid, $date = NULL, $IP = NULL)
    {
        if (!($sendRec = static::getActionRecForMid($mid, static::ACTION_SEND))) {
            // Няма изпращане с такъв MID
            return FALSE;
        }
    
        if (!empty($sendRec->data->receivedOn)) {
            // Връщането на писмото вече е било отразено в историята; не правим нищо
            return TRUE;
        }
    
        if (!isset($date)) {
            $date = dt::now();
        }

        expect(is_object($sendRec->data), $sendRec);
        
        $sendRec->data->receivedOn = $date;
        $sendRec->data->seenFromIp = $IP;
    
        static::save($sendRec);
    
        $rcvRec = (object)array(
            'action' => static::ACTION_RECEIVE,
            'containerId' => $sendRec->containerId,
            'threadId'    => $sendRec->threadId,
            'parentId'    => $sendRec->id
        );
    
        static::save($rcvRec);
        
        $msg = "Потвърдено получаване: " . doc_Containers::getDocTitle($sendRec->containerId);
        
        // Нотификация за получаване на писмото до адресата.
        /*
         * За сега отпада: @link https://github.com/bgerp/bgerp/issues/353#issuecomment-8531333
         *  
        bgerp_Notifications::add(
            $msg, // съобщение
            array('log_Documents', 'list', 'containerId' => $sendRec->containerId), // URL
            $sendRec->createdBy, // получател на нотификацията
            'alert' // Важност (приоритет)
        );
        */
        
        core_Logs::add(get_called_class(), $sendRec->id, $msg);
    
        return TRUE;
    }
    
    
    /**
     * Преди показването на документ по MID
     * 
     * @param int $cid key(mvc=doc_Containers)
     * @param string $mid
     * @return stdClass
     */
    public static function opened($cid, $mid)
    {
        expect($parent = static::fetchByMid($mid));
        
        if ($parent->containerId != $cid) {
            // Заявен е документ, който не е собственик на зададения MID. В този случай заявения 
            // документ трябва да е свързан с (цитиран от) документа собственик на MID.
            $requestedDoc = doc_Containers::getDocument($cid);
            $midDoc       = doc_Containers::getDocument($parent->containerId);
            
            $linkedDocs = $midDoc->getLinkedDocuments();
            
            // свързан ли е?
            expect(isset($linkedDocs[$requestedDoc->getHandle()]));
            
            // До тук се стига само ако заявения е свързан.
            
            $action = static::fetch(array("#containerId = [#1#] AND #parentId = {$parent->id}", $cid));
            
            if (!$action || $action->action != self::ACTION_OPEN) {
                // Ако нямаме отбелязано виждане на заявения документ - създаваме нов запис
                $action = (object)array(
                    'action'      => self::ACTION_OPEN,
                    'containerId' => $cid,
                    'parentId'    => $parent->id,
                    'data'        => new stdClass(),
                );
            }
        } else {
            $action = $parent;
            $parent = NULL;
            
            if ($action->parentId) {
                // Ако текущото виждане има родител - подсигуряваме, че и родителя е маркиран 
                // като видян
                $parent = static::fetch($action->parentId);
            }
        }
        
        expect($action);
        
        return static::markAsOpened($action, $parent);
    }
    
    
    /**
     * Помощен метод - маркира запис като видян и го добавя в стека с действията.
     * 
     *  Ако има зададен запис за родителско действие ($parent) и той се маркира като видян.
     *  Стека с действията се пълни в паметта; записа му в БД става в края на заявката
     *  @see log_Documents::on_Shutdown()
     * 
     * @param stdClass $action запис на този модел
     */
    protected static function markAsOpened($action, $parent)
    {
        if ($parent) {
            // Ако е зададено действие-родител - маркираме го като видяно.
            static::markAsOpened($parent, NULL);
        }
        
        $openAction = self::ACTION_OPEN;
        
        $ip = core_Users::getRealIpAddr();
        
        if (!isset($action->data->{$openAction}[$ip])) {
            $action->data->{$openAction}[$ip] = array(
                'on' => dt::now(true),
                'ip' => $ip
            );
        }
        
        static::pushAction($action);
        
        $msg = "Видян документ: " . doc_Containers::getDocTitle($action->containerId);
        
        core_Logs::add('doc_Containers', $action->containerId, $msg);
        
        return $action;
    }
    
    
    /**
     * Маркира файла, че е свален
     * 
     * @param string $mid
     * @param fileHnd $fh - Манипулатор на файла, който се сваля
     * 
     * @return object $rec
     */
    public static function downloaded($mid, $fh)
    {
        $downloadAction = static::ACTION_DOWNLOAD;
        
        // IP' то на потребителя
        $ip = core_Users::getRealIpAddr();
        
        // Очакваме да има запис, в който е цитиран файла
        expect($sendRec = static::getActionRecForMid($mid, FALSE));
        expect(is_object($sendRec->data));
        
        // Вземаме записа, ако има такъв
        $rec = static::fetch("#containerId = '{$sendRec->containerId}' AND #action = '{$downloadAction}'");
        
        // Ако съответния потребител е свалял файла
        if (!empty($rec->data->{$downloadAction}[$fh][$ip])) {
            
            return TRUE;    
        }
        
        // Датата и часа
        $date = dt::now(true);
        
        // Ако няма запис
        if (!$rec) {
            
            // Създаваме обект с данни
            $rec = (object)array(
                'action' => $downloadAction,
                'containerId' => $sendRec->containerId,
                'threadId'    => $sendRec->threadId,
                'data' => new stdClass(),
            );    
        }
        
        // Добавяме данните
        $rec->data->{$downloadAction}[$fh][$ip] = array(
            'ip' => $ip,
            'seenOnTime' => $date,
        );
        
        // id' то на текущия потребител
        $currUser = core_Users::getCurrent('id');
        
        // Ако има логнат потребител
        if ($currUser) {
            
            // Добавяме id' то му
            $rec->data->{$downloadAction}[$fh][$ip]['seenFrom'] = $currUser; 
        }

        // Пушваме съответното действие
        static::pushAction($rec);
        
        // Добавяме запис в лога
        $msg = "Свален файл: " . fileman_Download::getDownloadLink($fh);
        
        core_Logs::add('doc_Containers', $rec->containerId, $msg);

        return $rec;
    }
    
    
    /**
     * Случаен уникален идентификатор на документ
     *
     * @return string
     */
    protected static function generateMid()
    {
        do {
            $mid = str::getRand('Aaaaaaaa');
        } while (static::fetch("#mid = '{$mid}'", 'id'));
    
        return $mid;
    }
    
    static function on_BeforeSave($mvc, &$id, $rec)
    {
        if (empty($rec->data)) {
            $rec->dataBlob = NULL;
        } else {
            if (is_array($rec->data)) {
                $rec->data = (object)$rec->data;
            }
        
            $rec->dataBlob = serialize($rec->data);
        }
    }
    
    
    /**
     * Изпълнява се след всеки запис в модела
     *
     * @param log_Documents $mvc
     * @param int $id key(mvc=log_Documents)
     * @param stdClass $rec запис на модела, който е бил записан в БД
     */
    static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
        if ((!$rec->threadId) && ($rec->containerId)) {
            $rec->threadId = doc_Containers::fetchField($rec->containerId, 'threadId');
        }
        expect($rec->threadId);
        
        // Изчистваме кешираната история на треда, понеже тя току-що е била променена.
        $mvc::removeHistoryFromCache($rec->threadId);
    }
    
    
    /**
     * Подготовка на историята на цяла нишка
     *
     * Данните с историята на треда се кешират, така че многократно извикване с един и същ
     * параметър няма негативен ефект върху производителността.
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return array ключ е contanerId, стойност - историята на този контейнер
     */
    protected static function prepareThreadHistory($threadId)
    {
        if (!isset(static::$histories[$threadId])) {
            $cacheKey = static::getHistoryCacheKey($threadId);
        
            if (($history = core_Cache::get(static::CACHE_TYPE, $cacheKey)) === FALSE) {
                // Историята на този тред я няма в кеша - подготвяме я и я записваме в кеша
                $history = static::buildThreadHistory($threadId);
                core_Cache::set(static::CACHE_TYPE, $cacheKey, $history, '2 дена');
            }       
            
            static::$histories[$threadId] = $history;
        }
        
        return static::$histories[$threadId];
    }
    
    
    /**
     * Изтрива от кеша записана преди история на нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     */
    static function removeHistoryFromCache($threadId)
    {
        $cacheKey = static::getHistoryCacheKey($threadId);
        
        core_Cache::remove(static::CACHE_TYPE, $cacheKey);
    }
    
    /**
     * Ключ, под който се записва историята на нишка в кеша
     *
     * @see core_Cache
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return string
     */
    protected static function getHistoryCacheKey($threadId)
    {
        return $threadId;
    }
    
    /**
     * Преизчислява историята на нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return array масив с ключ $containerId (на контейнерите от $threadId, за които има запис
     *                  в историята) и стойности - обекти (stdClass) със следната структура:
     *
     *  ->summary => array(
     *         [ACTION1] => брой,
     *         [ACTION2] => брой,
     *         ...
     *     )
     *         
     *  ->containerId - контейнера, чиято история се съдържа в обекта (за удобство)
     */
    protected static function buildThreadHistory($threadId)
    {
        static::log('Регенериране на историята на нишка', $threadId, 3);
        
        $query = static::getQuery();
        $query->where("#threadId = {$threadId}");
        $query->orderBy('#createdOn');
        
        $open = self::ACTION_OPEN;
        $download = self::ACTION_DOWNLOAD;
        
        $data = array();   // Масив с историите на контейнерите в нишката
        while ($rec = $query->fetch()) {
            if (($rec->action != $open) && ($rec->action != $download)) {
                $data[$rec->containerId]->summary[$rec->action] += 1;
            }
            
            $data[$rec->containerId]->summary[$open] += count($rec->data->{$open});
            $data[$rec->containerId]->summary[$download] += static::getCountOfDownloads($rec->data->{$download});
            $data[$rec->containerId]->containerId = $rec->containerId;
        }
        
        return $data;
    }
    
    
    /**
     * Връща броя на свалянията
     * 
     * @param array $data - Масив с данни, в които ще се търси
     * 
     * @return integer $downloadCount - Броя на свалянията на файловете
     */
    protected static function getCountOfDownloads($data)
    {
        // Ако е масив
        if (is_array($data)) {
            
            // Обхождаме масива
            foreach ($data as $downloadRec) {
                
                // Добавяме броя на свалянията към променливата
                $downloadCount += count($downloadRec);
            }  
        }

        return $downloadCount;
    }
    
    
    /**
     * Подготвя историята на един контейнер
     *
     * @param int $containerId key(mvc=doc_Containers)
     * @param int $threadId key(mvc=doc_Threads)
     */
    protected static function prepareContainerHistory($containerId, $threadId)
    {
        $threadHistory = static::prepareThreadHistory($threadId);
        
        return $threadHistory[$containerId];
    }

    
    /**
     * @todo Чака за документация...
     */
    public static function renderSummary($data)
    {
        static $wordings = NULL;
        
        static $actionToTab = NULL;
        
        if (empty($data->summary)) {
            return '';
        }
        
        if (!isset($wordings)) {
            $wordings = array(
                static::ACTION_SEND    => array('изпращане', 'изпращания'),
                static::ACTION_RECEIVE => array('получаване', 'получавания'),
                static::ACTION_RETURN  => array('връщане', 'връщания'),
                static::ACTION_PRINT   => array('отпечатване', 'отпечатвания'),
                static::ACTION_OPEN   => array('виждане', 'виждания'),
                static::ACTION_DOWNLOAD => array('сваляне', 'сваляния'),
            );
        }

        if (!isset($actionToTab)) {
            $actionToTab = array(
                static::ACTION_SEND    => static::ACTION_SEND,
                static::ACTION_FAX     => static::ACTION_SEND,
                static::ACTION_RECEIVE => static::ACTION_SEND,
                static::ACTION_RETURN  => static::ACTION_SEND,
                static::ACTION_PRINT   => static::ACTION_PRINT,
                static::ACTION_PDF     => static::ACTION_PRINT,
                static::ACTION_OPEN    => static::ACTION_OPEN,
                static::ACTION_DOWNLOAD    => static::ACTION_DOWNLOAD,
            );
        }
        
        $html = '';
        
        foreach ($data->summary as $action=>$count) {
            if ($count == 0) {
                continue;
            }
            $actionVerbal = $action;
            if (isset($wordings[$action])) {
                $actionVerbal = $wordings[$action][intval($count > 1)];
            }
            
            $link = ht::createLink(
                "<b>{$count}</b> <span>{$actionVerbal}</span>", 
                array(
                    get_called_class(), 
                    'list', 
                    'containerId'=>$data->containerId, 
                    'action' => $actionToTab[$action]
                )
            );
            $html .= "<li class=\"action {$action}\">{$link}</li>";
        }
        
        $html = "<ul class=\"history summary\">{$html}</ul>";
        
        return $html;
    }
    
    
    /**
     * Шаблон (ET) съдържащ историята на документа в този контейнер.
     *
     * @param int $container key(mvc=doc_Containers)
     * @param int $threadId key(mvc=doc_Thread) нишката,в която е контейнера
     * @return core_ET
     * @deprecated
     */
    public static function getHistory($containerId, $threadId)
    {
        $data = static::prepareContainerHistory($containerId, $threadId);
        
        return static::renderHistory($data);
    }
    
    
    /**
     * Шаблон (ET) съдържащ обобщената историята на документа в този контейнер.
     *
     * @param int $container key(mvc=doc_Containers)
     * @param int $threadId key(mvc=doc_Thread) нишката,в която е контейнера
     * @return core_ET
     */
    public static function getSummary($containerId, $threadId)
    {
        $data = static::prepareContainerHistory($containerId, $threadId);
        
        return static::renderSummary($data);
    }
    
    
    /**
     * 
     * @param log_Documents $mvc
     * @param core_Query $query
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $mvc->restrictListedActions($data->query);
    }
    
    
    /**
     * @param core_Query $query
     */
    function restrictListedActions($query)
    {
        switch (static::getCurrentSubset()) {
            case static::ACTION_SEND:
                $query->where(sprintf("#action = '%s' OR #action = '%s'", static::ACTION_SEND, static::ACTION_FAX));
                break;
            case static::ACTION_PRINT:
                $query->where(sprintf("#action = '%s' OR #action = '%s'", static::ACTION_PRINT, static::ACTION_PDF));
                break;
        }
    }
    
    
    static function getCurrentSubset()
    {
        if (!$action = Request::get('action')) {
            $action = static::ACTION_SEND;
        }
        
        expect(
               $action == static::ACTION_SEND 
            || $action == static::ACTION_PRINT 
            || $action == static::ACTION_OPEN
            || $action == static::ACTION_DOWNLOAD
        );
        
        return $action;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_AfterPrepareListRows(log_Documents $mvc, &$data)
    {
        switch ($subset = $mvc::getCurrentSubset()) {
            case $mvc::ACTION_SEND:
                $mvc->currentTab = 'Изпращания';
                $mvc::prepareSendSubset($data);
                break;
            case $mvc::ACTION_PRINT:
                $mvc->currentTab = 'Отпечатвания';
                $mvc::preparePrintSubset($data);
                break;
            case $mvc::ACTION_OPEN:
                $mvc->currentTab = 'Виждания';
                $mvc::prepareOpenSubset($data);
                break;
            case $mvc::ACTION_DOWNLOAD:
                $mvc->currentTab = 'Сваляния';
                $mvc::prepareDownloadSubset($data);
                break;
            default:
                expect(FALSE);
        }

        if (!empty($mvc->listFieldsSet[$subset])) {
            $data->listFields = arr::make($mvc->listFieldsSet[$subset], TRUE);
        }
        
        if (Request::get('containerId', 'int') && isset($data->listFields['containerId'])) {
            unset($data->listFields['containerId']);
        }
    }
    
    
    static function prepareSendSubset($data)
    {
        $rows = $data->rows;
        $recs = $data->recs;
        
        if (empty($data->recs)) {
            return;
        }

        foreach ($recs as $i=>$rec) {
            $row = $rows[$i];
        
            if (!$data->doc) {
                $row->containerId = ht::createLink($row->containerId, array(get_called_class(), 'list', 'containerId'=>$rec->containerId));
            }
            
            $row->toEmail    = $rec->data->to;
            $row->cc    = $rec->data->cc;
            $row->receivedOn = static::renderOpenActions($rec, $rec->receivedOn);
            $row->returnedOn = static::getVerbal($rec, 'returnedOn');
            
            $stateClass = 'state-active';
            
            switch (true) {
                case !empty($row->receivedOn):
                    $stateClass = 'state-closed';
                    break;
                case !empty($row->returnedOn):
                    $stateClass = 'state-rejected';
                    break;
            }
            
            $row->ROW_ATTR['class'] .= ' ' . $stateClass;
        }
    }
    
    
    static function preparePrintSubset($data)
    {
        $rows = $data->rows;
        $recs = $data->recs;
        
        if (empty($data->recs)) {
            return;
        }
        
        foreach ($recs as $i=>$rec) {
            $row = $rows[$i];
        
            if (!$data->doc) {
                $row->containerId = ht::createLink($row->containerId, array(get_called_class(), 'list', 'containerId'=>$rec->containerId));
            }
            
            $row->seenOnTime = static::renderOpenActions($rec);
            
            $row->ROW_ATTR['class'] .= ' ' . (empty($row->seenOnTime) ? 'state-closed' : 'state-active');
        }
    }
    
    
    /**
     * Подготвяме подмножеството на свалените файлове
     * 
     * @param object $data
     */
    static function prepareDownloadSubset(&$data)
    {
        // Всички записи
        $recs = $data->recs;
        
        // Ако няма записи не се изпълнява
        if (empty($data->recs)) {
            
            return;
        }
        
        $download = static::ACTION_DOWNLOAD;
        $rows = array();
        
        // Обхождаме записите
        foreach ($recs as $id=>$rec) {
            
            // Ако няма зададени действия пррскачаме
            if (count($rec->data->{$download}) == 0) {
                
                continue;
            }
            
            // Обхождаме всички сваляния
            foreach ($rec->data->{$download} as $fh => $downData) {
                foreach ($downData as $downData2) {
                    // СЪздаваме обект със запсиите
                    $nRec = (object)array(
                        'seenOnTime' => $downData2['seenOnTime'],
                        'seenFrom' => $downData2['seenFrom'],
                        'seenFromIp' => $downData2['ip'],
                    );
                    
                    // Вземаме вербалните стойности
                    $row = static::recToVerbal($nRec, array_keys(get_object_vars($nRec)));
                    
                    // Превръщаме манипулатора, в линк за сваляне
                    $row->fileHnd = fileman_Download::getDownloadLink($fh);
                    
                    // Ако потребител от системата е свалил файла, показваме името му, в противен случай IP' то
                    $row->seenFromIp = $row->seenFrom ? $row->seenFrom : $row->seenFromIp;
                    
                    // Записваме в масив данните, с ключ датата
                    $rows[$nRec->seenOnTime] = $row;    
                }
            }
        }
        
        // Подреждаме масива
        ksort($rows);
        
        // Променяме всички вербални данни, да показват откритите от нас
        $data->rows = $rows;
    }
    
    
    static function prepareOpenSubset($data)
    {
        $recs = $data->recs;
        
        if (empty($data->recs)) {
            return;
        }

        $open = static::ACTION_OPEN;
        $rows = array();
        
        foreach ($recs as $i=>$rec) {
            
            if (count($rec->data->{$open}) == 0) {
                continue;
            }
            
            foreach ($rec->data->{$open} as $o) {
            
                $row = (object)array(
                    'seenOnTime' => $o['on'],
                    'seenFromIp' => $o['ip'],
                    'reason' => static::formatViewReason($rec)
                );
                
                $row = static::recToVerbal($row, array_keys(get_object_vars($row)));
                
                $rows[$o['on']] = $row;
                
            }
        }
        
        ksort($rows);
        $data->rows = $rows; 
    }
    
    
    protected static function formatViewReason($rec, $deep = TRUE)
    {
        switch ($rec->action) {
            case self::ACTION_SEND:
                return 'Имейл до ' . $rec->data->to . ' / ' . static::getVerbal($rec, 'createdOn');
            case self::ACTION_PRINT:
                return 'Отпечатване / ' . static::getVerbal($rec, 'createdOn');
            case self::ACTION_OPEN:
                if ($deep && !empty($rec->parentId)) {
                    $parentRec = static::fetch($rec->parentId);
                    $res = static::formatViewReason($parentRec, FALSE);
                } else {
                    $doc = doc_Containers::getDocument($rec->containerId);
                    $docRow = $doc->getDocumentRow();
                    $res = 'Показване на ' . 
                        ht::createLink($docRow->title, 
                            array(
                                get_called_class(), 'containerId' => $rec->containerId,
                                'action' => 'open'
                            )
                        ) . ' / ' . static::getVerbal($rec, 'createdOn');
                }
                return $res;
            default:
                return strtoupper($rec->action) . ' / ' . static::getVerbal($rec, 'createdOn');
        }
    }
    
    
    
    /**
     * Помощен метод - рендира историята на разглежданията на документ
     * 
     * @param stdClass $rec
     * @param string $date
     * @return string HTML 
     */
    private static function renderOpenActions($rec, $date = NULL, $brief = TRUE)
    {
        $openActionName = static::ACTION_OPEN;
        $html = '';
        
        if (!empty($rec->data->{$openActionName})) {
            $firstOpen = reset($rec->data->{$openActionName});
        }
        
        $_r = $rec->receivedOn;
        
        if (!empty($firstOpen) && (empty($date) || $firstOpen['on'] < $date)) {
            $rec->receivedOn = $firstOpen['on'];
        } else {
            $rec->receivedOn = $date;
        }
        
        $html .= static::getVerbal($rec, 'receivedOn');
        
        if (!empty($firstOpen)) {
            $html .= ' (' . $firstOpen['ip'] . ') ';
            $html .= ht::createLink(
                count($rec->data->{$openActionName}),
                array(
                    get_called_class(),
                    'containerId' => $rec->containerId,
                    'action' => static::ACTION_OPEN
                ),
                FALSE,
                array(
                    'class' => 'badge',
                )
            );
        }
        
        $rec->receivedOn = $_r;
        
        return $html;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_AfterPrepareListFields($mvc, $data)
    {
        if ($data->containerId = Request::get('containerId', 'key(mvc=doc_Containers)')) {
            unset($data->listFields['containerId']);
            $data->query->where("#containerId = {$data->containerId}");
            $data->doc = doc_Containers::getDocument($data->containerId, 'doc_DocumentIntf');
        }
        
        $data->query->orderBy('#createdOn', 'DESC');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_AfterPrepareListTitle(log_Documents $mvc, $data)
    {
        if (!$data->containerId) {
            $data->title = "История";
        }
        
        $url = array('log_Documents', 'list', 'containerId' => $data->containerId);
        
        if (($subset = $mvc::getCurrentSubset()) != $mvc::ACTION_SEND) {
            $url += array('action' => $subset);
        }
        
        bgerp_Notifications::clear($url);
    }
    

    /**
     * @todo Чака за документация...
     */
    static function on_AfterRenderListTitle(log_Documents $mvc, &$tpl, $data)
    {
        /* @var $doc doc_DocumentIntf */
        $doc = $data->doc;
        
        if ($doc) {
            $row = $doc->getDocumentRow();
            $tpl = new ET('<div class="listTitle">' . $doc->getLink() . '</div>');
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_AfterRenderListTable($mvc, &$tpl, $data)
    {
        if ($data->doc) {
            $tpl->append($data->doc->getDocumentBody());
        }
    }
    
    
    /**
     * Връща cid' а на документа от URL.
     * 
     * Проверява URL' то дали е от нашата система.
     * Проверява дали cid' а и mid'а си съвпадат.
     * Ако открие записа на документа проверява дали има родител.
     * Ако има родител връща cid'а на родителя. 
     * 
     * @param URL $url - URL от системата, в който ще се търси
     * 
     * @return integer $cid - Container id на документа
     */
    static function getDocumentCidFromURL($url)
    {
        // Проверяваме дали URL' то е от нашата система
        if (!static::isOurURL($url)) {
            
            return ;
        }
        
        // Вземаме cid'a и mid' а от URL' то
        $cidAndMidArr = static::getCidAndMidFromUrl($url);
        
        // Ако няма cid или мид
        if (!count($cidAndMidArr)) {
            
            return ;
        }
        
        // Вземам записа за съответния документ в лога
        $rec = log_Documents::fetchHistoryFor($cidAndMidArr['cid'], $cidAndMidArr['mid']);
        
        // Ако няма запис - mid' а не е правилен
        if (!$rec) {
            
            return ;
        }
        
        // Ако записа има parentId
        if ($rec->parentId) {
            
            // Задаваме cid'a да е containerId' то на родителския документ
            $cid = log_Documents::fetchField($rec->parentId, 'containerId');
        } else {
            
            $cid = $rec->containerId;
        }
        
        return $cid;
    }
    
    
    /**
     * Проверява подаденото URL далу е от системата.
     * 
     * @param URL $url - Линка, който ще се проверява
     * 
     * @return boolean - Ако открие съвпадение връща TRUE
     */
    static function isOurURL($url)
    {
        // Изчистваме URL' то от празни символи
        $url = trim($url);
        
        // Ако открием търсенто URL в позиция 0
        if (stripos($url, core_App::getBoot(TRUE)) === 0) {
            return TRUE;
        }
        
        return FALSE;
    }
    
    
    /**
     * Връща cid' а и mid' а от подаденото URL
     * 
     * @param URL $URL - Линка, в който ще се търси
     * 
     * @return array $res - Масив с ['cid'] и ['mid']
     */
    static function getCidAndMidFromUrl($url)
    {
        $bootUrl = core_App::getBoot(TRUE);
        
        // Ескейпваме името на директорията. Също така, допълнително ескейпваме и '/'
        $bootUrlEsc = preg_quote($bootUrl, '/');
        
        // Шаблон за намиране на mid'a и cid'а в URL
        // Шаблона работи само ако:
        // Класа е L
        // Екшъна е B или S
        // Веднага след тях следва ?m= за мида
        $pattern = "/(?'boot'{$bootUrlEsc}{1})\/(?'ctr'[L]{1})\/(?'act'[B|S]{1})\/(?'cid'[^\/]+)\/\?m\=(?'mid'[^$]+)/i";

        // Проверявама дали има съвпадение
        preg_match($pattern, $url, $matches);
        
        $res = array();
        
        // Ако намери cid и mid
        if (($matches['cid']) && ($matches['mid'])) {
            
            $res['cid'] = $matches['cid'];
            $res['mid'] = $matches['mid'];
        }

        return $res;
    }
    
    
    /**
     * Връща записа за съответния екшън и мид
     * 
     * @param string $mid - Mid' а на действието
     * @param string $action - Действието, което искаме да търсим
     * 
     * @return log_Documents - Обект с данни
     */
    static function getActionRecForMid($mid, $action=NULL)
    {
        // Ако не сме задали да не се проверява 
        if ($action === FALSE) {
            
            // Вземаме записа, ако има такъв
            $rec = static::fetch(array("#mid = '[#1#]'", $mid));
            
            // Ако екшъна е един от посочените, връщаме FALSE
            if (in_array($rec->action, array(self::ACTION_DISPLAY, self::ACTION_RECEIVE, self::ACTION_RETURN, self::ACTION_DOWNLOAD))) {
                
                return FALSE;
            }
        } else {
            
            // Акшъна по подразбиране да е send
            setIfNot($action, static::ACTION_SEND);
    
            // Вземаме записа, ако има такъв
            $rec = static::fetch(array("#mid = '[#1#]' AND #action = '{$action}'", $mid));
        }
        
        return $rec;
    }
    
    
    /**
     * 
     */
    public static function on_Shutdown($mvc)
    {
        static::flushActions();
    }
    
    
    /**
     * Записва в БД всички действия от стека
     */
    protected static function flushActions()
    {
        $count = 0;
        
        while ($action = static::popAction()) {
            static::save($action);
            $count++;
        }

        if($count > 0) {
            core_Logs::add(get_called_class(), NULL, "Записани {$count} действия");
        }
    }
    
}