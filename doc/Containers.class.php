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
    var $canList = 'user';
    
    
    /**
     * @todo Чака за документация...
     */
    var $canAdd  = 'no_one';
    
    
    /**
     * Масив с всички абревиатури и съответните им класове
     */
    static $abbrArr = NULL;
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Мастери - нишка и папка
        $this->FLD('folderId' , 'key(mvc=doc_Folders)', 'caption=Папки');
        $this->FLD('threadId' , 'key(mvc=doc_Threads)', 'caption=Нишка');
        
        // Документ
        $this->FLD('docClass' , 'class(interface=doc_DocumentIntf,select=title,allowEmpty)', 'caption=Документ->Клас');
        $this->FLD('docId' , 'int', 'caption=Документ->Обект');
        $this->FLD('handle' , 'varchar', 'caption=Документ->Манипулатор');
        $this->FLD('searchKeywords', 'text', 'notNull,column=none, input=none');
        
        $this->FLD('activatedBy', 'key(mvc=core_Users)', 'caption=Активирано от, input=none');
        
        // Индекси за бързодействие
        $this->setDbIndex('folderId');
        $this->setDbIndex('threadId');
        $this->setDbUnique('docClass, docId');
    }
    
    
    /**
     * Филтрира по id на нишка (threadId)
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $threadId = Request::get('threadId', 'int');
        
        if($threadId) {
            $data->query->where("#threadId = {$threadId}");
        }
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
        
        bgerp_Recently::add('document', $data->threadRec->firstContainerId);
        
        $data->query->orderBy('#createdOn');
    }
    
    
    /**
     * Подготвя титлата за единичния изглед на една нишка от документи
     */
    static function on_AfterPrepareListTitle($mvc, &$res, $data)
    {
        $title = new ET("<div style='font-size:18px'>[#user#] » [#folder#] ([#folderCover#]) » [#threadTitle#]</div>");
        
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
        
        $tpl->appendOnce("flashHashDoc(flashDocInterpolation);", 'ON_LOAD');
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
            $row->document = $document->renderDocument($data);
            
            if($q = Request::get('Q')) {
                $row->document = plg_Search::highlight($row->document, $q);
            }

            $row->created = str::limitLen($docRow->author, 32);
        } else {
            if(isDebug()) {
                if(!$rec->docClass) {
                    $debug = 'Липсващ $docClass';
                }
                if(!$rec->docId) {
                    $debug .= 'Липсващ $docId';
                }
                if(!$document) {
                    $debug .= 'Липсващ $document';
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
            $row->created = new ET("<table style='margin-left:2px;margin-top:3px;margin-bottom:0px;' ><tr><td rowspan='2' valign='top' style='white-space:nowrap; padding-right:5px'>[#2#]</td><td nowrap style='padding-top:2px;'>[#3#]</td><td rowspan='2' style='width:50%'>[#HISTORY#]</td></tr><tr><td nowrap>[#1#]</td></tr></table>",
                $mvc->getVerbal($rec, 'createdOn'),
                $avatar,
                $row->created);
                
            // визуализиране на обобщена информация от лога
        } else {
            $row->created = new ET("<div style='text-align:center;'><div style='text-align:left;display:inline-block;'><div style='font-size:0.8em;margin-top:7px;margin-left:5px;margin-right:5px;'>[#3#]</div>
                                                <div style='font-size:0.8em;margin-left:7px;margin-right:7px; margin-bottom:10px;margin-top:5px;'>[#1#]</div>
                                                <div class='gravatar-box' style='margin:10px;'>[#2#]</div>[#HISTORY#]</div></div>",
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
            $data->toolbar->addBtn('Нов...', array($mvc, 'ShowDocMenu', 'threadId'=>$data->threadId), 'id=btnAdd,class=btn-add');
            
            if($data->threadRec->state == 'opened') {
                // TODO може да се направи бутона да не е активен
                $data->toolbar->addBtn('Затваряне', array('doc_Threads', 'close', 'threadId'=>$data->threadId), 'class=btn-close');
            } elseif($data->threadRec->state == 'closed' || empty($data->threadRec->state)) {
                $data->toolbar->addBtn('Отваряне', array('doc_Threads', 'open', 'threadId'=>$data->threadId), 'class=btn-open');
            }
            $data->toolbar->addBtn('Преместване', array('doc_Threads', 'move', 'threadId'=>$data->threadId, 'ret_url' => TRUE), 'class=btn-move');
        }
    }
    
    
	function on_AfterRenderWrapping($mvc, &$tpl)
    {
    	jquery_Jquery::enable($tpl);
    	
    	$tpl->push('doc/tpl/style.css', 'CSS');
    	$tpl->push('doc/js/accordion.js', 'JS');
    	
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
    static function update_($id)
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
        
        $fields = 'state,folderId,threadId,containerId';
        
        $docRec = $docMvc->fetch($rec->docId, $fields);
        
        if ($docRec->searchKeywords = $docMvc->getSearchKeywords($docRec->id)) {
            $fields .= ',searchKeywords';
        }
                
        foreach(arr::make($fields) as $field) {
            if($rec->{$field} != $docRec->{$field}) {
                $rec->{$field} = $docRec->{$field};
                $mustSave = TRUE;
            }
        }

        // Дали документа се активира в момента, и кой го активира
        if(empty($rec->activatedBy) && $rec->state != 'draft' && $rec->state != 'rejected') {
            
            $rec->activatedBy = core_Users::getCurrent();
            
            $flagJustActived = TRUE;
            $mustSave = TRUE;
        }

        if($mustSave) {
            
            $bSaved = doc_Containers::save($rec);

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
                // Подготвяме няколко стринга, за употреба по-после
                $docSingleTitle = mb_strtolower($docMvc->singleTitle);  
                $docHnd = $docMvc->getHandle($rec->docId);
                $threadTitle = str::limitLen(doc_Threads::getThreadTitle($rec->threadId, FALSE), 90);
                $nick = core_Users::getCurrent('nick');
                $nick = str_replace(array('_', '.'), array(' ', ' '), $nick);
                $nick = mb_convert_case($nick, MB_CASE_TITLE, 'UTF-8');
                 
                // Нотифицираме всички споделени потребители на този контейнер
                $sharedArr = type_Keylist::toArray($shared);
                if(count($sharedArr)) {
                    $message = "{$nick} сподели {$docSingleTitle} в \"{$threadTitle}\"";
                    $url = array('doc_Containers', 'list', 'threadId' => $rec->threadId);
                    $customUrl = array('doc_Containers', 'list', 'threadId' => $rec->threadId, 'docId' => $docHnd, '#' => $docHnd);
                    $priority = 'normal';
                    foreach($sharedArr as $userId) {
                        bgerp_Notifications::add($message, $url, $userId, $priority, $customUrl);
                        $notifiedUsers[$userId] = $userId;
                    }
                }

                // Нотифицираме всички абонати на дадената нишка
                $subscribed = doc_ThreadUsers::getSubscribed($rec->threadId);
                $subscribedArr = type_Keylist::toArray($subscribed);
                if(count($subscribedArr)) { 
                    $message = "{$nick} добави  {$docSingleTitle} в \"{$threadTitle}\"";
                    $url = array('doc_Containers', 'list', 'threadId' => $rec->threadId);
                    $customUrl = array('doc_Containers', 'list', 'threadId' => $rec->threadId, 'docId' => $docHnd, '#' => $docHnd);
                    $priority = 'normal';
                    foreach($subscribedArr as $userId) {  
                        if($userId > 0  && (!$notifiedUsers[$userId]) && 
                            doc_Threads::haveRightFor('single', $rec->threadId, $userId)) {
                            bgerp_Notifications::add($message, $url, $userId, $priority, $customUrl);
                        }
                    }
                }
            }
        }
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
            if(!$rec->docId) sleep(1);
            $rec = doc_Containers::fetch($id, 'docId, docClass');
            
            if(!$rec->docId) sleep(1);
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
        if (!$doc = doc_RichTextPlg::getAttachedDocs("#{$handle}")) {
            return FALSE;
        }
        
        // извежда в променливи $mvc и $rec - мениджъра и записа на документа със зададения хендъл
        extract(reset($doc));  
        
        return static::getDocument((object)array('docClass'=>$mvc, 'docId'=>$rec->id), $intf);
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
     * Генерира и връща манипулатор на документ.
     *
     * @param int $id key(mvc=doc_Container)
     * @return string манипулатора на документа
     */
    public static function getHandle($id)
    {
        $rec = static::fetch($id, 'id, handle, docId, docClass');
        
        expect($rec);
        
        if (!$rec->handle) {
            $doc = static::getDocument($rec, 'doc_DocumentIntf');
            $rec->handle = $doc->getHandle();
            
            do {
                $rec->handle = email_util_ThreadHandle::protect($rec->handle);
            } while (!is_null(static::getByHandle($rec->handle)));
            
            expect($rec->handle);
            
            // Записваме току-що генерирания манипулатор в контейнера. Всеки следващ 
            // опит за вземане на манипулатор ще връща тази записана стойност.
            static::save($rec);
        }
        
        return $rec->handle;
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
        }  catch (core_Exception_Expect $expect) {
            $title = '?????????????????????????????????????';
        }

        $title = $docRow->title;

        return $title;
    }
    
    
    /**
     * Екшън за активиране на постинги
     */
    function act_Activate()
    {
        $containerId = Request::get('containerId');
        
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
        $clsInst->invoke('Activation', array(&$recAct));
        
        //Записваме данните в БД
        $clsInst->save($recAct);
        
        //Редиректваме към сингъла на съответния клас, от къде се прехвърляме към треда
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
        // Извличане на потенциалните класове на нови документи
        $docArr = core_Classes::getOptionsByInterface('doc_DocumentIntf');
        
        foreach($docArr as $id => $class) {
	            
            $mvc = cls::get($class);
            
            list($order, $group) = explode('|', $mvc->newBtnGroup);

            if($mvc->haveRightFor('add', $rec)) {
                $docArrSort[$order*1000] = array($group, $mvc->singleTitle, $class);
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
        $tpl->append("\n<h3>" . tr('Добавяне на нов документ в нишката') . ":</h3>");
        $tpl->append("<div class='accordian'><ul>");
        
        $active = ' class="active"';
        
        foreach($btns as $group => $bArr) {
       	
        	$tpl->append("<li{$active}><img class='btns-icon plus' src=". sbf('img/16/toggle1.png') ."><img class='btns-icon minus' src=". sbf('img/16/toggle2.png') .">&nbsp;{$group}</li>");
        	$tpl->append("<li>");
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
        $abbr = core_Cache::get('abbr', 'allClass', 1440, array('core_Classes', 'core_Interfaces'));
        
        //Ако няма
        if (!$abbr) {
            
            $docClasses = core_Classes::getOptionsByInterface('doc_DocumentIntf');

            //Обикаляме всички записи, които имплементират doc_DocumentInrf
            foreach ($docClasses as $id => $className) {
                
                //Създаваме инстанция на класа в масив
                $instanceArr[$id] = cls::get($className);
                
                //Създаваме масив с абревиатурата и името на класа                
                $abbr[strtoupper($instanceArr[$id]->abbr)] = $className;
            }
            
            //Записваме масива в кеша
            core_Cache::set('abbr', 'allClass', $abbr, 1440, array('core_Classes', 'core_Interfaces'));
        }
        
        self::$abbrArr = $abbr;
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
            
            //Кое поле е избрано да се показва, като текст
            expect($field = $ctrInst->rowToolsSingleField);

            // Очакваме да имаме права за съответния екшън
            expect($rec && $ctrInst->haveRightFor('single', $rec));
        } catch (core_exception_Expect $e) {
            
            // Ако възникне някаква греша
            return FALSE;
        }

        //Стойността на полето на текстовата част
        $title = $ctrInst->getVerbal($params['id'], $field);
        
        // Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
        $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
        
        // Иконата на класа
        $sbfIcon = sbf($ctrInst->singleIcon, '"', $isAbsolute);

        //Ако мода е xhtml
        if (Mode::is('text', 'xhtml')) {
            
            // Ескейпваме плейсхолдърите
            $title = core_ET::escape($title);
            
            // TODO може да се използва този начин вместо ескейпването
            //$res = new ET("<span class='linkWithIcon' style='background-image:url({$sbfIcon});'> [#1#] </span>", $title);
            
            //Добаваме span с иконата и заглавиетео - не е линк
            //TODO класа да не е linkWithIcon
            $res = "<span class='linkWithIcon' style='background-image:url({$sbfIcon});'> {$title} </span>";    
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

            if(!doc_Folders::fetch($rec->folderId, 'id')) {
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
                if(!$rec->docId) {
                    $err[$rec->id] .= 'Not exists docId; ';
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
        return (boolean)static::fetch("#threadId = '{$threadId}' AND #docClass = '{$documentClassId}'");
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
     */
    public static function rejectByThread($threadId)
    {
        /* @var $query core_Query */
        $query = static::getQuery();
        
        $query->where("#threadId = {$threadId}");
        $query->where("#state <> 'rejected'");
        
        while ($rec = $query->fetch()) {
            $doc = static::getDocument($rec);
            $doc->reject();
        }
    }
    
    
    /**
     * Възстановява всички контейнери в зададена нишка. Това включва възстановяването и на 
     * реалните документи, съдържащи се в контейнерите.
     * 
     * @param int $threadId
     */
    public static function restoreByThread($threadId)
    {
        /* @var $query core_Query */
        $query = static::getQuery();
        
        $query->where("#threadId = {$threadId}");
        $query->where("#state = 'rejected'");
        
        while ($rec = $query->fetch()) {
            $doc = static::getDocument($rec);
            $doc->restore();
        }
    }
    
    
    /**
     * 
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
}
