<?php



/**
 * Клас 'doc_Folders' - Папки с нишки от документи
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class doc_Threads extends core_Manager
{   


    /**
     * Максимална дължина на показваните заглавия 
     */
    const maxLenTitle = 70;
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created,plg_Modified,plg_State,doc_Wrapper, plg_Select, expert_Plugin,plg_Sorting';
    
    
    /**
     * Интерфейси
     */
    var $interfaces = 'core_SettingsIntf';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'powerUser';
    
    
    /**
     * 
     */
    var $canWrite = 'no_one';
    
    
    /**
     * Заглавие
     */
    var $title = "Нишки от документи";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Нишка от документи";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'title=Заглавие,author=Автор,last=Последно,hnd=Номер,allDocCnt=Документи,createdOn=Създаване';
    
    
    /**
     * 
     */
    var $canNewdoc = 'powerUser';
    
    
    /**
     * Какви действия са допустими с избраните редове?
     */
    var $doWithSelected = 'open=Отваряне,close=Затваряне,restore=Възстановяване,reject=Оттегляне,move=Преместване';
    

    /**
     * Кешираме достъпа до даден контейнер
     */
    var $haveRightForSingle = array();
    

    /**
     * Данните на адресата, с най - много попълнени полета
     */
    static $contragentData = NULL;
    
    
    /**
     * Опашка от id на нишки, които трябва да обновят статистиките си
     *  
     * @var array
     * @see doc_Threads::updateThread()
     */
    protected static $updateQueue = array();
    
    
    /**
     * Описание на модела на нишките от контейнери за документи
     */
    function description()
    {
        // Информация за нишката
        $this->FLD('folderId', 'key(mvc=doc_Folders,select=title,silent)', 'caption=Папки');
       // $this->FLD('title', 'varchar(255)', 'caption=Заглавие');
        $this->FLD('state', 'enum(opened,pending,closed,rejected)', 'caption=Състояние,notNull');
        $this->FLD('allDocCnt', 'int', 'caption=Брой документи->Всички');
        $this->FLD('partnerDocCnt', 'int', 'caption=Брой документи->Публични, oldFieldName=pubDocCnt');
        $this->FLD('last', 'datetime(format=smartTime)', 'caption=Последно');
        
        // Ключ към първия контейнер за документ от нишката
        $this->FLD('firstContainerId' , 'key(mvc=doc_Containers)', 'caption=Начало,input=none,column=none,oldFieldName=firstThreadDocId');
        
        // Достъп
        $this->FLD('shared' , 'keylist(mvc=core_Users, select=nick)', 'caption=Споделяне');
                
        // Състоянието на последния документ в нишката
        $this->FLD('lastState', 'enum(draft=Чернова,
                  pending=Чакащо,
                  active=Активирано,
                  opened=Отворено,
                  closed=Приключено,
                  hidden=Скрито,
                  rejected=Оттеглено,
                  stopped=Спряно,
                  wakeup=Събудено,
                  free=Освободено)','caption=Последно->състояние, input=none');
        
        // Създателя на последния документ в нишката
        $this->FLD('lastAuthor', 'key(mvc=core_Users)', 'caption=Последно->От, input=none');
        
        // Ид-та на контейнерите оттеглени при цялостното оттегляне на треда, при възстановяване на треда се занулява
        $this->FLD('rejectedContainersInThread', 'blob(serialize,compress)', 'caption=Заглавие, input=none');
        
        // Индекс за по-бързо избиране по папка
        $this->setDbIndex('folderId');
        
        $this->setDbIndex('firstContainerId');
    }
    
    
    /**
     * Връща линк към подадения обект
     * 
     * @param integer $objId
     * 
     * @return core_ET
     */
    public static function getLinkForObject($objId)
    {
        if (doc_Threads::haveRightFor('single', $objId)) {
            
            $fistContainerId = self::fetchField($objId, 'firstContainerId');
            
            return doc_Containers::getLinkForObject($fistContainerId);
        }
    }
    
    
    /**
     * 
     * 
     * @param integer $id
     * @param boolean $escape
     */
    public static function getTitleForId_($id, $escaped = TRUE)
    {
        $fistContainerId = self::fetchField($id, 'firstContainerId');
        
        return doc_Containers::getTitleForId_($fistContainerId);
    }
    
    
    /**
     * Поправка на структурата на нишките
     * 
     * @param datetime $from
     * @param datetime $to
     * @param integer $delay
     * 
     * @return array
     */
    public static function repair($from = NULL, $to = NULL, $delay = 10)
    {
        // Изкючваме логването
        $isLoging = core_Debug::$isLogging;
        core_Debug::$isLogging = FALSE;
        
        $resArr = array();
        
        // id на папката за несортирани
        $unsortedCoverClassId = core_Classes::getId('doc_UnsortedFolders');
        
        // id на папката за несортирани
        $currUser = core_Users::getCurrent();
        if ($currUser > 0) {
            $defaultFolderId = doc_Folders::fetchField("#coverClass = '{$unsortedCoverClassId}' AND #inCharge = '{$currUser}'", 'id', FALSE);
        }
        if (!isset($defaultFolderId)) {
            $defaultFolderId = doc_Folders::fetchField("#coverClass = '{$unsortedCoverClassId}'", 'id', FALSE);
        }
        
        $query = self::getQuery();
        
        // Подготвяме данните за търсене
        doc_Folders::prepareRepairDateQuery($query, $from, $to, $delay);
        
        $query->where("#firstContainerId IS NULL");
        $query->orWhere("#folderId IS NULL");
        
        // Не им се правят обработвки
        // За да предизвикат стартиране за съответния запис в on_Shutdown
        $query->orWhere("#allDocCnt IS NULL");
        $query->orWhere("#partnerDocCnt IS NULL");
        $query->orWhere("#lastAuthor IS NULL");
        $query->orWhere("#lastState IS NULL");
        
        while ($rec = $query->fetch()) {
            
            // Ако има нишка без firstContainerId
            if (!isset($rec->firstContainerId)) {
            
                // Първия документ от нишката
                $firstCid = doc_Containers::fetchField("#threadId = '{$rec->id}'", 'id', FALSE);
                
                // Ако не може да се определи първия документ в нишката, изтриваме нишката
                if (!$firstCid) {
                    if ($rec->id) {
                        self::delete($rec->id);
                        $resArr['del_cnt']++;
                        continue;
                    }
                }
                
                $rec->firstContainerId = $firstCid;
                
                if (self::save($rec)) {
                    $resArr['firstContainerId']++;
                }
            }
            
            // Ако няма папка използваме папката за несортирани
            if (!isset($rec->folderId) && isset($defaultFolderId)) {
                $rec->folderId = $defaultFolderId;
                
                if (self::save($rec)) {
                    $resArr['folderId']++;
                }
            }
            
            // Обновяваме нишката
            self::updateThread($rec->id);
        }
        
        // Връщаме старото състояние за ловговането в дебъг
        core_Debug::$isLogging = $isLoging;
        
        $conf = core_Packs::getConfig('doc');
        
        if ($conf->DOC_REPAIR_STATE == 'yes') {
            $resArr += self::repairStates($from, $to, $delay);
        }
        
        return $resArr;
    }
    
    
    
    /**
     * Поправка на развалените полета за състоянието на нишките
     * 
     * @param datetime $from
     * @param datetime $to
     * @param integer $delay
     * 
     * @return array
     */
    public static function repairStates($from = NULL, $to = NULL, $delay = 10)
    {
        $resArr = array();
        $query = self::getQuery();
        
        doc_Folders::prepareRepairDateQuery($query, $from, $to, $delay);
        
        while ($rec = $query->fetch()) {
            
            if (!$rec->firstContainerId) continue;
            
            try {
                $cRec = doc_Containers::fetch($rec->firstContainerId);
            } catch (Exception $e) {
                continue;
            }
            
            if (!$cRec || !$cRec->docClass || !$cRec->docId) continue;
            
            try {
                $clsInst = cls::get($cRec->docClass);
                $iRec = $clsInst->fetch($cRec->docId, 'state', FALSE);
                
                if (!isset($iRec->state)) continue;
                
                // Ако състоянието на документа е оттеглен и на нишката трябва да е оттеглен
                if ($iRec->state != 'rejected') continue;
                if ($iRec->state == $rec->state) continue;
                $rec->state = $iRec->state;
                
                if (self::save($rec, 'state')) {
                    $resArr['firstContainerIdState']++;
                }
            } catch (core_exception_Expect $e) {
                
                continue;
            }
        }
        
        return $resArr;
    }
    
    
    /**
     * Екшън за оттегляне на тредове
     */
    function act_Reject()
    {
        return $this->doRejectOrRestore('Reject');
    }
    

    /**
     * Екшън за възстановяване на тредове
     */
    function act_Restore()
    {
        return $this->doRejectOrRestore('Restore');
    }

    
    /**
     * Изпълнява процедура по оттегляне/възстановяване на нишка
     */
    function doRejectOrRestore($act)
    {
        if($selected = Request::get('Selected')) {
            Debug::log('Selected = ' . $selected);
            $selArr = arr::make($selected);
            
            foreach($selArr as $id) {
                if($this->haveRightFor('single', $id)) {
                    $this->haveRightForSingle[$id] = TRUE;
                    Request::push(array('id' => $id, 'Selected' => FALSE));
                    $res = Request::forward();
                    Request::pop();
                }
            } 
        } else {
            expect($id = Request::get('id', 'int'));
            expect($rec = $this->fetch($id));
            if(!$this->haveRightForSingle[$id]) {
                $this->requireRightFor('single', $rec);
            }
            $fDoc = doc_Containers::getDocument($rec->firstContainerId);
            
            Request::push(array('id' => $fDoc->that, 'Ctr' => $fDoc->className, 'Act' => $act));
            $res = Request::forward();
            Request::pop();
        }
        
        return $res;
    }
    
    
    /**
     * Подготвя титлата на папката с теми
     */
    static function on_AfterPrepareListTitle($mvc, &$res, $data)
    {
        expect($data->folderId = Request::get('folderId', 'int'));
        
        $title = new ET("<div class='path-title'>[#user#] » [#folder#] ([#folderCover#])</div>");
        
        // Папка и корица
        $folderRec = doc_Folders::fetch($data->folderId);
        $folderRow = doc_Folders::recToVerbal($folderRec);
        $title->append($folderRow->title, 'folder');
        $title->replace($folderRow->type, 'folderCover');
        
        // Потребител
        if($folderRec->inCharge > 0) {
            $user = crm_Profiles::createLink($folderRec->inCharge);
        } else {
            $user = '@system';
        }
        $title->replace($user, 'user');
      
        if(Request::get('Rejected')) {
            $title->append("&nbsp;<span class='state-rejected stateIndicator'>&nbsp;" . tr('оттеглени') . "&nbsp;</span>", 'folder');
        }
        
        $title->replace($user, 'user');
        
        $data->title = $title;

        $mvc->title = '|*' . doc_Folders::getTitleById($folderRec->id) . '|' ;
    }
    
    
    /**
     * 
     * 
     * @param doc_Threads $mvc
     * @param object $data
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('search', 'varchar', 'caption=Ключови думи,input,silent,recently');
        $data->listFilter->FNC('order', 'enum(open=Първо отворените, recent=По последно, create=По създаване, numdocs=По брой документи)', 
            'allowEmpty,caption=Подредба,input,silent,refreshForm');
        $data->listFilter->setField('folderId', 'input=hidden,silent');
        $data->listFilter->FNC('documentClassId', "class(interface=doc_DocumentIntf,select=title,allowEmpty)", 'caption=Вид документ,input,recently');
        
        if(!isset($data->listFilter->fields['Rejected'])) {
        	$data->listFilter->FNC('Rejected', 'varchar', 'input=hidden,silent');
        }
        
        // Ако е зададено
        if ($rejectedId = Request::get('Rejected', 'int')) {
        
        	// Задаваме стойността от заявката
        	$data->listFilter->setDefault('Rejected', $rejectedId);
        }
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Търсене', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        $data->listFilter->showFields = 'folderId,search,order,documentClassId';
        
        $data->listFilter->input(NULL, 'silent');
        
        // id на папката
        $folderId = $data->listFilter->rec->folderId;

        $rejected = Request::get('Rejected');
        
        $documentsInThreadOptions = self::getDocumentTypesOptionsByFolder($folderId, FALSE, $rejected);
        if(count($documentsInThreadOptions)) {
            $documentsInThreadOptions = array_map('tr', $documentsInThreadOptions);
            $data->listFilter->setOptions('documentClassId', $documentsInThreadOptions);
        } else {
        	$data->listFilter->setReadOnly('documentClassId');
        }
        
        // Вземаме данните
        $key = doc_Folders::getSettingsKey($folderId);
        $vals = core_Settings::fetchKey($key);
        
        // Ако е зададено подреждане в персонализацията
        if ($vals['ordering']) {
            
            // Подреждаме по зададената стойност
            $data->listFilter->setDefault('order', $vals['ordering']);
        }
        
        expect($folderId = $data->listFilter->rec->folderId);
        
        doc_Folders::requireRightFor('single');
        
        expect($folderRec = doc_Folders::fetch($folderId));
        
        doc_Folders::requireRightFor('single', $folderRec);
        
        $mvc::applyFilter($data->listFilter->rec, $data->query);

        // Изчистване на нотификации, свързани с промени в тази папка
        $url = array('doc_Threads', 'list', 'folderId' => $folderId);
        bgerp_Notifications::clear($url);
        bgerp_Recently::add('folder', $folderId, NULL, ($folderRec->state == 'rejected') ? 'yes' : 'no');
    }
    
    
    /**
     * Връща типовете документи в папката, за бързодействие кешира резултатите
     * 
     * @param int $folderId - ид на папка
     * @param boolean $onlyVisibleForPartners - дали да са само видимите за партнъори документи
     * @param boolean $rejected - оттеглените или не оттеглените документи
     * @return array $options - типовете документи
     */
    public static function getDocumentTypesOptionsByFolder($folderId, $onlyVisibleForPartners = FALSE, $rejected = FALSE)
    {
    	$cacheKey = ($onlyVisibleForPartners === TRUE) ? "visibleDocumentsInFolder{$folderId}" : "folder{$folderId}";
    	
    	// Проверяваме имали кеширани данни
    	$options = core_Cache::get("doc_Folders", $cacheKey);
    	
    	// Ако няма кеширани данни, извличаме ги наново
    	if($options === FALSE) {
    		
    		// Ще групираме типовете документи в нишката
    		$query = doc_Threads::getQuery();
    		$query->where("#folderId = {$folderId}");
    		$query->EXT('firstDocumentClassId', 'doc_Containers', 'externalName=docClass,externalKey=firstContainerId');
    		
    		// Ако ще проверяваме за партньори, оставяме само видимите за тях документи
    		if($onlyVisibleForPartners){
    			$query->EXT('visibleForPartners', 'doc_Containers', 'externalName=visibleForPartners,externalKey=firstContainerId');
    			$query->EXT('firstDocState', 'doc_Containers', 'externalName=state,externalKey=firstContainerId');
    			$query->where("#visibleForPartners = 'yes'");
    			$query->where("#firstDocState != 'draft' && #firstDocState != 'rejected'");
    		}
    		$query->show('firstDocumentClassId, state');
    		
    		// Групираме записите по classId
    		while($rec = $query->fetch()){
    			$index = ($rec->state == 'rejected') ? 'rejected' : 'notrejected';
    			
    			if(!isset($options[$index][$rec->firstDocumentClassId])){
    				$options[$index][$rec->firstDocumentClassId] = core_Classes::getTitleById($rec->firstDocumentClassId);
    			}
    		}
    		
    		// Кешираме резултатите
    		core_Cache::set("doc_Folders", $cacheKey, $options, 1440);
    	}
    
    	// Връщаме данните за оттеглените или за не оттеглените документи в папката
    	if(!$rejected){	
    		return $options['notrejected'];
    	} else {
    		return $options['rejected'];
    	}
    }
    
    
    /**
     * Налага данните на филтъра като WHERE /GROUP BY / ORDER BY клаузи на заявка
     *
     * @param stdClass $filter
     * @param core_Query $query
     */
    static function applyFilter($filter, $query)
    {
        if (!empty($filter->folderId)) {
            $query->where("#folderId = {$filter->folderId}");
        }
        
        // Налагане на условията за търсене
        if (!empty($filter->search)) {
            $query->EXT('containerSearchKeywords', 'doc_Containers', 'externalName=searchKeywords');
            $query->where(
            	  '`' . doc_Containers::getDbTableName() . '`.`thread_id`' . ' = ' 
                . '`' . static::getDbTableName() . '`.`id`');
            
            plg_Search::applySearch($filter->search, $query, 'containerSearchKeywords');
            
            $query->groupBy('`doc_threads`.`id`');
        }
        
        // Подредба - @TODO
        switch ($filter->order) {
        	default:
            case 'open':
                $query->XPR('isOpened', 'int', "IF(#state = 'opened', 0, 1)");
                $query->orderBy('#isOpened,#state=ASC,#last=DESC,#id=DESC');
                break;
            case 'recent':
                $query->orderBy('#last=DESC,#id=DESC');
                break;
            case 'create':
                $query->orderBy('#createdOn=DESC,#state=ASC,#last=DESC,#id=DESC');
                break;
            case 'numdocs':
                $query->orderBy('#allDocCnt=DESC,#state=ASC,#last=DESC,#id=DESC');
                break;
        }
       
        if($filter->documentClassId){
        	$query->EXT('firstDocumentClassId', 'doc_Containers', 'externalName=docClass,externalKey=firstContainerId');
        	$query->where("#firstDocumentClassId = {$filter->documentClassId}");
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Manager $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        if(empty($rec->firstContainerId)) return;

        try {
            $docProxy = doc_Containers::getDocument($rec->firstContainerId);
        } catch (core_Exception_Expect $expect) {

            return;
        }
        
        $docRow = $docProxy->getDocumentRow();
        
        $attr = array();
        $attr['class'] .= 'linkWithIcon';
        $attr['style'] = 'background-image:url(' . sbf($docProxy->getIcon($docProxy->that)) . ');';

        if(mb_strlen($docRow->title) > self::maxLenTitle) {
            $attr['title'] = $docRow->title;
        }
		
        $row->onlyTitle = $row->title = ht::createLink(str::limitLen($docRow->title, self::maxLenTitle),
            array('doc_Containers', 'list',
                'threadId' => $rec->id,
                'folderId' => $rec->folderId,
                'Q' => Request::get('search') ? Request::get('search') : NULL),
            NULL, $attr);

        if($docRow->subTitle) {
            $row->title .= "\n<div class='threadSubTitle'>{$docRow->subTitle}</div>";
        }

        if($docRow->authorId > 0) {
        	$row->author = crm_Profiles::createLink($docRow->authorId);
        } else {
            $row->author = $docRow->author;
        }
        
        $row->hnd = "<div class='rowtools'>";
        
        $row->hnd .= "<div style='padding-right:5px;' class='l'><div class=\"stateIndicator state-{$docRow->state}\"></div></div> <div class='r'>";
        
        $row->hnd .= $rec->handle ? substr($rec->handle, 0, strlen($rec->handle)-3) : $docProxy->getHandle();
        
        $row->hnd .= '</div>';
        
        $row->hnd .= '</div>';
    }
    
    
    /**
     * Създава нов тред
     */
    static function create($folderId, $createdOn, $createdBy)
    {
        $rec = new stdClass();
        $rec->folderId = $folderId;
        $rec->createdOn = $createdOn;
        $rec->createdBy = $createdBy;
        
        self::save($rec);
        
        return $rec->id;
    }
    
    
    /**
     * Екшън за преместване на тред
     */
    function exp_Move($exp)
    {
        if($selected = Request::get('Selected')) {
            $selArr = arr::make($selected);
            Request::push(array('threadId' => $selArr[0]));
        }
        
        $threadId = Request::get('threadId', 'int');
        
        if($threadId) {
            $this->requireRightFor('single', $threadId);

            $tRec = $this->fetch($threadId);
        }
        
        // TODO RequireRightFor
        $exp->DEF('#threadId=Нишка', 'key(mvc=doc_Threads)', 'fromRequest');
        $exp->DEF('#Selected=Избрани', 'varchar', 'fromRequest');
        
        $exp->functions['doc_threads_fetchfield'] = 'doc_Threads::fetchField';
        $exp->functions['getcompanyfolder'] = 'crm_Companies::getCompanyFolder';
        $exp->functions['getpersonfolder'] = 'crm_Persons::getPersonFolder';
        $exp->functions['getcontragentdata'] = 'doc_Threads::getContragentData';
        $exp->functions['getquestionformoverest'] = 'doc_Threads::getQuestionForMoveRest';
        $exp->functions['haveaccess'] = 'doc_Folders::haveRightToFolder';
        
        $exp->DEF('dest=Преместване към', 'enum(exFolder=Съществуваща папка, 
                                                newCompany=Нова папка на фирма,
                                                newPerson=Нова папка на лице)', 'maxRadio=4,columns=1', '');
        
        $exp->ASSUME('#dest', "'exFolder'");

        if(count($selArr) > 1) {
            $exp->question("#dest", tr("Моля, посочете къде да бъдат преместени нишките") . ":", TRUE, 'title=' . tr('Преместване на нишки от документи'));
        } else {
            if($tRec->allDocCnt > 1) {
                $exp->question("#dest", tr("Моля, посочете къде да бъде преместена нишката") . ":", TRUE, 'title=' . tr('Преместване на нишка от документи'));
            } else {
                $exp->question("#dest", tr("Моля, посочете къде да бъде преместен документа") . ":", TRUE, 'title=' . tr('Преместване на документ в нова папка'));
            }
        }
        
        $exp->DEF('#folderId=Папка', 'key(mvc=doc_Folders, select=title, where=#state !\\= \\\'rejected\\\')', 'width=500px');
        
        // Информация за фирма и представител
        $exp->DEF('#company', 'varchar(255)', 'caption=Фирма,width=100%,mandatory,remember=info');
        $exp->DEF('#salutation', 'enum(,mr=Г-н,mrs=Г-жа,miss=Г-ца)', 'caption=Обръщение');
        $exp->DEF('#name', 'varchar(255)', 'caption=Имена,width=100%,mandatory,remember=info');
        
        // Адресни данни
        $exp->DEF('#country', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Държава,remember,notNull');
        $exp->DEF('#pCode', 'varchar(16)', 'caption=П. код,recently');
        $exp->DEF('#place', 'varchar(64)', 'caption=Град,width=100%');
        $exp->DEF('#address', 'varchar(255)', 'caption=Адрес,width=100%');
        
        // Комуникации
        $exp->DEF('#email', 'emails', 'caption=Имейл,width=100%,notNull');
        $exp->DEF('#tel', 'drdata_PhoneType', 'caption=Телефони,width=100%,notNull');
        $exp->DEF('#fax', 'drdata_PhoneType', 'caption=Факс,width=100%,notNull');
        $exp->DEF('#website', 'url', 'caption=Web сайт,width=100%,notNull');
        
        // Стойности по подразбиране при нова папка на фирма или лице
        $exp->ASSUME('#email', "getContragentData(#threadId, 'email')", "#dest == 'newCompany' || #dest == 'newPerson'");
        $exp->ASSUME('#country', "getContragentData(#threadId, 'countryId')", "#dest == 'newCompany' || #dest == 'newPerson'");
        $exp->ASSUME('#company', "getContragentData(#threadId, 'company')", "#dest == 'newCompany' || #dest == 'newPerson'");
        $exp->ASSUME('#tel', "getContragentData(#threadId, 'tel')", "#dest == 'newCompany' || #dest == 'newPerson'");
        $exp->ASSUME('#fax', "getContragentData(#threadId, 'fax')", "#dest == 'newCompany' || #dest == 'newPerson'");
        $exp->ASSUME('#pCode', "getContragentData(#threadId, 'pCode')", "#dest == 'newCompany' || #dest == 'newPerson'");
        $exp->ASSUME('#place', "getContragentData(#threadId, 'place')", "#dest == 'newCompany' || #dest == 'newPerson'");
        $exp->ASSUME('#address', "getContragentData(#threadId, 'address')", "#dest == 'newCompany' || #dest == 'newPerson'");
        $exp->ASSUME('#website', "getContragentData(#threadId, 'web')", "#dest == 'newCompany' || #dest == 'newPerson'");
        
        $exp->SUGGESTIONS('#company', "getContragentData(#threadId, 'companyArr')", "#dest == 'newCompany' || #dest == 'newPerson'");
        
        // Данъчен номер на фирмата
        $exp->DEF('#vatId', 'drdata_VatType', 'caption=Данъчен №,remember=info,width=100%');
        
        // Допълнителна информация
        $exp->DEF('#info', 'richtext', 'caption=Бележки,height=150px');
        
        $exp->question("#company, #country, #pCode, #place, #address, #email, #tel, #fax, #website, #vatId", tr("Моля, въведете контактните данни на фирмата") . ":", "#dest == 'newCompany'", 'title=' . tr('Преместване в папка на нова фирма'));
        
        $exp->question("#salutation, #name, #country, #pCode, #place, #address, #email, #tel, #website", tr("Моля, въведете контактните данни на лицето") . ":", "#dest == 'newPerson'", 'title=' . tr('Преместване в папка на ново лице'));

        $exp->rule('#folderId', "getPersonFolder(#salutation, #name, #country, #pCode, #place, #address, #email, #tel, #website)", TRUE);

        $exp->rule('#folderId', "getCompanyFolder(#company, #country, #pCode, #place, #address, #email, #tel, #fax, #website, #vatId)", TRUE);
        
        $exp->ASSUME('#folderId', "doc_Threads_fetchField(#threadId, 'folderId')", TRUE);
        
        $exp->question("#folderId", tr("Моля, изберете папка") . ":", "#dest == 'exFolder'", 'title=' . tr('Избор на папка за нишката'));
        
        // От какъв клас е корицата на папката където е изходния тред?
        $exp->DEF('#moveRest=Преместване на всички', 'enum(yes=Да,no=Не)');
        $exp->rule('#askMoveRest', "getQuestionForMoveRest(#threadId)", TRUE);
        $exp->question("#moveRest", "=#askMoveRest", '#askMoveRest && #folderId', 'title=' . tr('Групово преместване'));
        $exp->rule("#moveRest", "'no'", '!(#askMoveRest)');
        $exp->rule("#moveRest", "'no'", '#Selected');
        $exp->rule("#haveAccess", "haveaccess(#folderId)");
        $exp->WARNING(tr("Нямате достъп до избраната папка! Сигурни ли сте че искате да преместите нишката?"), '#haveAccess === FALSE');
        
        $result = $exp->solve('#folderId,#moveRest,#haveAccess');
        
        if($result == 'SUCCESS') {
            $threadId = $exp->getValue('threadId');
            $this->requireRightFor('single', $threadId);
            $folderId = $exp->getValue('folderId');
            $haveAccess = $exp->getValue('haveAccess');
            $selected = $exp->getValue('Selected');
            $moveRest = $exp->getValue('moveRest');
            $threadRec = doc_Threads::fetch($threadId);
            
            if($moveRest == 'yes') {
                $doc = doc_Containers::getDocument($threadRec->firstContainerId);
                $msgRec = $doc->fetch();
                $msgQuery = email_Incomings::getSameFirstDocumentsQuery($threadRec->folderId, array('fromEml' => $msgRec->fromEml));
                
                while($mRec = $msgQuery->fetch()) {
                    $selArr[] = $mRec->threadId;
                }
            } else {
                $selArr = arr::make($selected);
            }
            
            if(!count($selArr)) {
                $selArr[] = $threadId;
            }
            
            // Брояч на успешните премествания
            $successCnt = 0;

            // Брояч на грешките при преместване
            $errCnt = 0;

            foreach($selArr as $threadId) {
                try {
                    $this->move($threadId, $folderId);
                    $successCnt++;
                } catch ( core_Exception_Expect $expect ) { $errCnt++; }
            }
            
            // Изходяща папка
            $folderFromRec = doc_Folders::fetch($threadRec->folderId);
            $folderFromRow = doc_Folders::recToVerbal($folderFromRec);
            
            // Входяща папка
            $folderToRec = doc_Folders::fetch($folderId);
            $folderToRow = doc_Folders::recToVerbal($folderToRec);
            
            if ($successCnt) {
                if ($successCnt == 1) {
                    $message = "|*{$successCnt} |нишка от|* {$folderFromRow->title} |е преместена в|* {$folderToRow->title}";
                } else {
                    $message = "|*{$successCnt} |нишки от|* {$folderFromRow->title} |са преместени в|* {$folderToRow->title}";
                }
            }
            
            if($errCnt) {
                $message .= "<br> |възникнаха|* {$errCnt} |грешки";
                $exp->redirectMsgType = 'error';
            }
            
            $exp->message = tr($message);
            
            // Ако преместваме само една нишка
            if (count($selArr) == 1) {
            
                // Ако имаме права за нишката, в преместената папка
                if ($this->haveRightFor('single', $threadId)) {
                    
                    // Вземаме първия документ в нишката
                    $firstContainerId = $threadRec->firstContainerId;
                    
                    // Ако има такъв
                    if ($firstContainerId) {
                        
                        // Вземаме документа
                        $doc = doc_Containers::getDocument($firstContainerId);
                        
                        // Редиректваме към сингъла на документа
                        $exp->setValue('ret_url', toUrl(array($doc, 'single', $doc->that)));
                        
                        // Сетваме флага
                        $haveRightForSingle = TRUE;
                    }
                }
            }
            
            // Ако не е вдигнат флага - когато преместваме повече от една нишка или нямаме достъп до преместената нишка (когато е една)
            if (!$haveRightForSingle) {
                
                // Ако имаме достъп 
                if ($haveAccess){
                    
                    // да отидем в изходящата папка
                	$exp->setValue('ret_url', toUrl(array('doc_Threads', 'list', 'folderId' => $folderToRec->id)));
                } else {
                    
                    // Ако няма ret_url, да редиректне в папката, от която се мести    
                    $exp->setValue('ret_url', toUrl(array('doc_Threads', 'list', 'folderId' => $folderFromRec->id)));
                }
            }
        }
        
        // Поставя  под формата, първия постинг в треда
        // TODO: да се замени с интерфейсен метод
        if($threadId = $exp->getValue('threadId')) {
            $threadRec = self::fetch($threadId);
            $originTpl = new ET("<div class='preview-holder'><div style='margin-top:20px; margin-bottom:-10px; padding:5px;'><b>" . tr("Първи документ в нишката") . "</b></div><div class='scrolling-holder'>[#DOCUMENT#]</div></div>");
            $document = doc_Containers::getDocument($threadRec->firstContainerId);
            $docHtml = $document->getInlineDocumentBody();
            $originTpl->append($docHtml, 'DOCUMENT');
            
            if(!$exp->midRes) {
                $exp->midRes = new stdClass();
            }
            $exp->midRes->afterForm = $originTpl;
        }
        
        return $result;
    }
    
    
    /**
     * Преместване на нишка от в друга папка.
     *
     * @param int $id key(mvc=doc_Threads)
     * @param int $destFolderId key(mvc=doc_Folders)
     * @return boolean
     */
    public static function move($id, $destFolderId)
    {
        // Подсигуряваме, че нишката, която ще преместваме, както и папката, където ще я 
        // преместваме съществуват.
        expect($currentFolderId = static::fetchField($id, 'folderId'));
        expect(doc_Folders::fetchField($destFolderId, 'id') == $destFolderId);
        
        // Извличаме doc_Cointaners на този тред
        /* @var $query core_Query */
        $query = doc_Containers::getQuery();
        $query->where("#threadId = {$id}");
        $query->show('id, docId, docClass');
        
        while ($rec = $query->fetch()) {

            $doc = doc_Containers::getDocument($rec->id);

            /*
             *  Преместваме оригиналния документ. Плъгина @link doc_DocumentPlg ще се погрижи да
             *  премести съответстващия му контейнер.
             */
            expect($rec->docId, $rec);
            $doc->getInstance()->save(
                (object)array(
                    'id' => $rec->docId,
                    'folderId' => $destFolderId,
                ),
                'id,folderId'
            );
        }
        
        // Преместваме самата нишка
        if (doc_Threads::save(
                (object)array(
                    'id' => $id,
                    'folderId' => $destFolderId
                )
            )) {
                
                // Изчистваме нотификацията до потребители, които нямат достъп до нишката
                $urlArr = array('doc_Containers', 'list', 'threadId' => $id);
                $usersArr = bgerp_Notifications::getNotifiedUserArr($urlArr);
                $nRec = doc_Threads::fetch($id, '*', FALSE);
                
                if ($usersArr) {
                    foreach ((array)$usersArr as $userId => $hidden) {
                        
                        // Ако има права до сингъла
                        if (doc_Threads::haveRightFor('single', $nRec, $userId)) {
                            
                            // Ако е скрит, го показваме
                            if ($hidden == 'yes') {
                                
                                // Показваме
                                bgerp_Notifications::setHidden($urlArr, 'no', $userId);
                            }
                        } else {
                            
                            // Ако нямаме права и се показва 
                            if ($hidden == 'no') {
                                bgerp_Notifications::setHidden($urlArr, 'yes', $userId);
                            }
                        }
                    }
                }
                
                // Нотифицираме новата и старата папка за настъпилото преместване
                
                // $currentFolderId сега има една нишка по-малко
                doc_Folders::updateFolderByContent($currentFolderId);
                
                // $destFolderId сега има една нишка повече
                doc_Folders::updateFolderByContent($destFolderId);
                
                //
                // Добавяме нови правила за рутиране на базата на току-що направеното преместване.
                //
                // expect($firstContainerId = static::fetchField($id, 'firstContainerId'));
                // email_Router::updateRoutingRules($firstContainerId, $destFolderId);
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function getQuestionForMoveRest($threadId)
    {
        $threadRec = doc_Threads::fetch($threadId);
        $folderRec = doc_Folders::fetch($threadRec->folderId);
        $folderFromRow = doc_Folders::recToVerbal($folderRec);
        
        $doc = doc_Containers::getDocument($threadRec->firstContainerId);
        
        if($doc->className == 'email_Incomings') {
            
            $msgRec = $doc->fetch();
            
            $msgQuery = email_Incomings::getSameFirstDocumentsQuery($folderRec->id, array('fromEml' => $msgRec->fromEml));
            
            $msgQuery->show('id');
            
            $sameEmailMsgCnt = $msgQuery->count() - 1;
            
            $msgRow = $doc->recToVerbal($msgRec);
            
            if($sameEmailMsgCnt > 0) {
                if ($sameEmailMsgCnt == 1) {
                    $res = tr("|Желаете ли и останалата|* {$sameEmailMsgCnt} |нишка, започваща с входящ имейл от|* {$msgRow->fromEml}, |намираща се в|* {$folderFromRow->title} |също да бъде преместена|*?");
                } else {
                    $res = tr("|Желаете ли и останалите|* {$sameEmailMsgCnt} |нишки, започващи с входящ имейл от|* {$msgRow->fromEml}, |намиращи се в|* {$folderFromRow->title} |също да бъдат преместени|*?");
                }
            }
        }
        
        return $res;
    }
    
    
    /**
     * Извлича първичния ключ на първия контейнер в нишка
     * 
     * @param int $id key(mvc=doc_Threads)
     * @return int key(mvc=doc_Containers)
     */
    public static function getFirstContainerId($id)
    {
        /* @var $query core_Query */
        $query = doc_Containers::getQuery();
        $query->where("#threadId = {$id}");
        $query->orderBy('createdOn', 'ASC');
        $query->limit(1);
        $query->show('id');
        $r = $query->fetch();
        
        return $r->id;
    }
    
    
    /**
     * Референция към първия документ в зададена нишка
     * 
     * @param int $id key(mvc=doc_Threads)
     * @return core_ObjectReference референция към документ
     */
    public static function getFirstDocument($id)
    {
        try{
        	$containerId = static::getFirstContainerId($id);
        	$firstDoc = doc_Containers::getDocument($containerId);
        } catch(core_exception_Expect $e){
        	
        	// Ако няма първи документ, връща NULL
        	return NULL;
        }
        
        return $firstDoc;
    }
    
    
    /**
     * Добавя нишка в опашката за опресняване на стат. информация.
     * 
     * Същинското опресняване ще случи при shutdown на текущото изпълнение, при това еднократно
     * за всяка нишка, независимо колко пъти е заявена за опресняване тя.
     *  
     * @param int $id key(mvc=doc_Threads)
     */
    public static function updateThread($id)
    {
        // Изкуствено създаваме инстанция на doc_Folders. Това гарантира, че ще бъде извикан
        // doc_Folders::on_Shutdown()
        cls::get('doc_Folders');
        
        self::$updateQueue[$id] = TRUE;
    }
    
    
    /**
     * Обновява информацията за дадена тема. Обикновено се извиква след промяна на doc_Containers
     * 
     * @param array|int $ids масив с ключ id на нишка или 
     */
    public static function doUpdateThread($ids = NULL)
    {
        if (!isset($ids)) {
            $ids = self::$updateQueue;
        }
        
        if (is_array($ids)) {
            foreach (array_keys($ids) as $id) {
                if (!isset($id)) { continue; }
                self::doUpdateThread($id);
            }
            return;
        }
        
        if (!$id = $ids) {
            return;
        }
        
        // Вземаме записа на треда
        $rec = self::fetch($id, NULL, FALSE);
        
        // Запазваме общия брой документи
        $exAllDocCnt = $rec->allDocCnt;
        
        $dcQuery = doc_Containers::getQuery();
        $dcQuery->orderBy('#createdOn');
        
        // Публични документи в треда
        $rec->partnerDocCnt = $rec->allDocCnt = 0;

        $firstDcRec = NULL;
        
        while($dcRec = $dcQuery->fetch("#threadId = {$id}")) {
            
            if(!$firstDcRec) {
                $firstDcRec = $dcRec;
            }
            
            // Не броим оттеглените документи
            if($dcRec->state != 'rejected') {
                $lastDcRec = $dcRec;
                
                if($dcRec->visibleForPartners == 'yes') {
                    $rec->partnerDocCnt++;
                }
                
                $rec->allDocCnt++;
            }
        }
        
        // Попълваме полето за споделените потребители
        $rec->shared = keylist::fromArray(doc_ThreadUsers::getShared($rec->id));

        if($firstDcRec) {
            // Първи документ в треда
            $rec->firstContainerId = $firstDcRec->id;
            
            // Последния документ в треда
            if($lastDcRec->state != 'draft') {
                $rec->last = max($lastDcRec->createdOn, $lastDcRec->modifiedOn);
            } else {
                $rec->last = $lastDcRec->createdOn;
            }
            
            // Ако имаме добавяне/махане на документ от треда или промяна на състоянието към активно
            // тогава състоянието му се определя от последния документ в него
            if(($rec->allDocCnt != $exAllDocCnt) || ($rec->lastState && ($lastDcRec->state != $rec->lastState))) {
                // Ако състоянието не е draft или не е rejected
                if($lastDcRec && $lastDcRec->state != 'draft') {
                    $doc = doc_Containers::getDocument($lastDcRec->id);
                    $newState = $doc->getThreadState();
                    
                    if($newState) {
                        $rec->state = $newState;
                    }
                }
            }
            
            if ($lastDcRec) {
                
                // Състоянието на последния документ
                $rec->lastState = $lastDcRec->state;
                
                if (isset($lastDcRec->createdBy)) {
                    
                    // Създателя на последния докуемент
                    $rec->lastAuthor = $lastDcRec->createdBy;    
                }
            }
            
            // Състоянието по подразбиране за последния документ е затворено
            if(!$rec->lastState) {
                $rec->lastState = 'closed';
            }
            
            // Състоянието по подразбиране за треда е затворено
            if(!$rec->state) {
                $rec->state = 'closed';
            }
            
            doc_Threads::save($rec, 'last, allDocCnt, partnerDocCnt, firstContainerId, state, shared, modifiedOn, modifiedBy, lastState, lastAuthor');
         } else {
            // Ако липсват каквито и да е документи в нишката - изтриваме я
            self::delete($id);
        }
        
        doc_Folders::updateFolderByContent($rec->folderId);
    }
    
    
    /**
     * Оттегля цяла нишка, заедно с всички документи в нея
     * 
     * @param int $id
     */
    public static function rejectThread($id)
    {
        // Оттегляме записа в doc_Threads
        expect($rec = static::fetch($id));
            
        if ($rec->state == 'rejected') {
            
            return;
        }
        
        $rec->state = 'rejected';
        static::save($rec);

        // Оттегляме всички контейнери в нишката
        $rejectedIds = doc_Containers::rejectByThread($rec->id);
        
        // Добавяме и контейнера на първия документ в треда
        $rejectedIds[] = $rec->firstContainerId;
        
        // Обръщаме последователността на обратно
        $rejectedIds = array_reverse($rejectedIds);
        	
        // Ако има оттеглени контейнери с треда, запомняме ги, за да може при възстановяване да възстановим само тях
        $rec->rejectedContainersInThread = $rejectedIds;
        	
        static::save($rec, 'rejectedContainersInThread');
        
        self::invalidateDocumentCache($rec->id);
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param doc_Threads $mvc
     * @param int $id - първичния ключ на направения запис
     * @param stdClass $rec - всички полета, които току-що са били записани
     */
    public static function on_AfterSave($mvc, &$id, $rec)
    {
        if ($rec->folderId) {
            $Folders = cls::get('doc_Folders');
            if (Mode::is('isMigrate')) {
                $Folders->preventNotification[$rec->folderId] = $rec->folderId;
            }
        }
    }
    
    
    /**
     * Възстановява цяла нишка, заедно с всички документи в нея 
     * 
     * @param int $id
     */
    public static function restoreThread($id)
    {
        // Възстановяваме записа в doc_Threads
        expect($rec = static::fetch($id));
        
        if ($rec->state != 'rejected') {
            
            return;
        }
        
        $rec->state = 'closed';
        static::save($rec);

        // Възстановяваме всички контейнери в нишката
        doc_Containers::restoreByThread($rec->id);
        
        if($rec->rejectedContainersInThread){
        	
        	// Зануляваме при нужда списъка с оттеглените ид-та
        	unset($rec->rejectedContainersInThread);
        	static::save($rec, 'rejectedContainersInThread');
        }
        
        self::invalidateDocumentCache($rec->id);
    }
    
    
    /**
     * Преди извличане на записите от БД
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        if($data->query) {
            if(Request::get('Rejected')) {
                $data->query->where("#state = 'rejected'");
            } else {
                $data->rejQuery = clone($data->query);
                $data->rejQuery->where("#state = 'rejected'");
                // Показваме или само оттеглените или всички останали нишки
         	    $data->query->where("#state != 'rejected' OR #state IS NULL");
            }
        }
    }

    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        
        // Бутони за разгледане на всички оттеглени тредове
        if(Request::get('Rejected')) {
            $data->toolbar->removeBtn('*', 'with_selected');
            $data->toolbar->addBtn('Всички', array($mvc, 'folderId' => $data->folderId), 'id=listBtn', 'ef_icon = img/16/application_view_list.png');
        } else {
        	$folderState = doc_Folders::fetchField($data->folderId, 'state');
        	if($folderState == 'closed'){
        		$data->toolbar->removeBtn('*');
        	} else {
        		// Може да се добавя нов документ, само ако папката не е затворена
        		if(doc_Folders::fetchField($data->folderId, 'state') != 'closed'){
        			$data->toolbar->addBtn('Нов...', array($mvc, 'ShowDocMenu', 'folderId' => $data->folderId), 'id=btnAdd', array('ef_icon'=>'img/16/star_2.png', 'title'=>'Създаване на нова тема в папката'));
        		}
        		
        		$data->rejectedCnt = $data->rejQuery->count("#folderId = {$data->folderId}");;
        		
        		if($data->rejectedCnt) {
        			$curUrl = getCurrentUrl();
        			$curUrl['Rejected'] = 1;
        			$data->toolbar->addBtn("Кош|* ({$data->rejectedCnt})",
        			$curUrl, 'id=binBtn,class=fright,order=50' . (Mode::is('screenMode', 'narrow') ? ',row=2' : ''), 'ef_icon = img/16/bin_closed.png');
            	}
        		
        		// Ако има мениджъри, на които да се слагат бързи бутони, добавяме ги
        	    if($managersIds = self::getFastButtons($data->folderId)){
        			foreach ($managersIds as $classId){
        				$Cls = cls::get($classId);
        				$data->toolbar->addBtn($Cls->singleTitle, array($Cls, 'add', 'folderId' => $data->folderId), "ef_icon = {$Cls->singleIcon},title=Създаване на " . mb_strtolower($Cls->singleTitle));
        			}
        		}
        	}
        }
        
        // Ако има права за настройка на папката, добавяме бутона
        $key = doc_Folders::getSettingsKey($data->folderId);
        $userOrRole = core_Users::getCurrent();
        if (doc_Folders::canModifySettings($key, $userOrRole)) {
            core_Settings::addBtn($data->toolbar, $key, 'doc_Folders', $userOrRole, 'Настройки', array('class' => 'fright', 'row' => 2, 'title'=>'Персонални настройки на папката'));
        }
    }
    
    
    /**
     * Връща масив с ид-та на мениджърите за които ще има бързи, ако няма връща NULL
     */
    private static function getFastButtons($folderId)
    {
    	$managersIds = array();
    	
    	// Ако няма кеширани, $managersIds намираме ги
    	if(!count($managersIds)){
    		
    		// Намираме имали класове с интерфейса за добавяне
    		$classesToAdd = core_Classes::getOptionsByInterface('doc_AddToFolderIntf');
    		
    		if(count($classesToAdd)){
    			$folderRec = doc_Folders::fetch($folderId);
    			$cu = core_Users::getCurrent();
    		
    			// За всеки мениджър
    			foreach ($classesToAdd as $classId => $className){
    						
    				// Проверяваме дали може да се добавя като бърз бутон
    				if(cls::load($className, TRUE)){
    					
    					$Cls = cls::get($className);
    					if($Cls->haveRightFor('add', (object)array('folderId' => $folderRec->id))){
    						
    						// Ако имплементира интерфейсния метод 'mustShowButton', и той върне TRUE
    						if(cls::existsMethod($Cls, 'mustShowButton') && $Cls->mustShowButton($folderRec, $cu)){
    							$managersIds[$classId] = $classId;
    						}
    					}
    				}
    			}
    		}
    	}
    	
    	// Връщаме ид-та на всички мениджъри, които да имат бързи бутони
    	return count($managersIds) ? $managersIds : NULL;
    }
    
    
    /**
     * Извиква се след изчисляване на ролите необходими за дадено действие
     */
    static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec, $userId = NULL)
    {
        if($action == 'open') {
            if($rec->state == 'closed') {
                $res = $mvc->getRequiredRoles('single', $rec, $userId);
            } else {
                $res = 'no_one';
            }
        }
        
        if($action == 'close') {
            if($rec->state == 'opened') {
                $res = $mvc->getRequiredRoles('single', $rec, $userId);
            } else {
                $res = 'no_one';
            }
        }
        
        if($action == 'reject') {
            if($rec->state == 'opened' || $rec->state == 'closed') {
                $res = $mvc->getRequiredRoles('single', $rec, $userId);
            } else {
                $res = 'no_one';
            }
        }
        

        if($action == 'restore') {
            if($rec->state == 'rejected') {
                $res = $mvc->getRequiredRoles('single', $rec, $userId);
            } else {
                $res = 'no_one';
            }
        }

        if($action == 'move') {
            $res = $mvc->getRequiredRoles('single', $rec, $userId);
        }

        if($action == 'single') {
            if(doc_Folders::haveRightToFolder($rec->folderId, $userId)) {
                $res = 'user';
            } elseif(keylist::isIn($userId, $rec->shared)) {
                $res = 'user';
            } else {
                $res = 'no_one';
            }
        }

        if($action == 'newdoc') {
            if($rec->state == 'opened' || $rec->state == 'closed') {
            	if(doc_Folders::fetchField($rec->folderId, 'state') != 'closed'){
            		$res = $mvc->getRequiredRoles('single', $rec, $userId);
            	} else {
            		$res = 'no_one';
            	}
            } else {
                $res = 'no_one';
            }
        }
    }
    
    
    
    /**
     * Отваря треда
     */
    function act_Open()
    {
        if($selected = Request::get('Selected')) {
            
            foreach(arr::make($selected) as $id) {
                $R = cls::get('core_Request');
                Request::push(array('threadId' => $id, 'Selected' => FALSE));
                Request::forward();
                Request::pop();
            }
            
            followRetUrl();
        }
        
        expect($id = Request::get('threadId', 'int'));
        
        expect($rec = $this->fetch($id));
        $this->requireRightFor('single', $rec);
        expect(doc_Folders::fetchField($rec->folderId, 'state') != 'closed');
        
        $rec->state = 'opened';
        
        $this->save($rec);
        
        $this->updateThread($rec->id);
        
        $this->logInfo('Отвори нишка', $id);
        
        return new Redirect(array('doc_Containers', 'list', 'threadId' => $id));
    }
    
    
    /**
     * Затваря треда
     */
    function act_Close()
    {
        if($selected = Request::get('Selected')) {
            
            foreach(arr::make($selected) as $id) {
                $R = cls::get('core_Request');
                Request::push(array('threadId' => $id, 'Selected' => FALSE));
                Request::forward();
                Request::pop();
            }
            
            followRetUrl();
        }
        
        expect($id = Request::get('threadId', 'int'));
        
        expect($rec = $this->fetch($id));
        
        $this->requireRightFor('single', $rec);
        expect(doc_Folders::fetchField($rec->folderId, 'state') != 'closed');
        
        $rec->state = 'closed';
        
        $this->save($rec);
        
        $this->updateThread($rec->id);
        
        $this->logInfo('Затвори нишка', $id);
        
        return new Redirect(array('doc_Containers', 'list', 'threadId' => $id));
    }
    
    
    /**
     * Намира контрагента с който се комуникира по тази нишка
     * Връща данните, които са най - нови и с най - много записи
     */
    static function getContragentData($threadId, $field = NULL)
    {
        static $cashe;
        
        if(!$bestContragentData = $cashe[$threadId]) {
            $query = doc_Containers::getQuery();
            $query->where("#state != 'rejected'");
            $query->where("#threadId = '{$threadId}'");
            $query->orderBy('createdOn', 'DESC');
            
            // Текущо най-добрата оценка за данни на контрагент
            $bestRate = 0;
            
            while ($rec = $query->fetch()) {
                
                $className = Cls::getClassName($rec->docClass);
                
                if (cls::haveInterface('doc_ContragentDataIntf', $className)) {
                    
                    $contragentData = $className::getContragentData($rec->docId);
                    
                    $rate = self::calcPoints($contragentData);
                    
                    // Даваме предпочитания на документите, създадени от потребители на системата
                    if($rec->createdBy > 0) {
                        $rate = $rate * 10;
                    }
                    
                    if($rate > $bestRate) {
                        $bestContragentData = clone($contragentData);
                        $bestRate = $rate;
                    }
                }
            }
            
            // Вземаме данните на потребителя от папката
            // След като приключим обхождането на треда
            $folderId = doc_Threads::fetchField($threadId, 'folderId');
            
            $contragentData = doc_Folders::getContragentData($folderId);
            
            if($contragentData) {
                $rate = self::calcPoints($contragentData) + 4;
            } else {
                $rate = 0;
            }
            
            if($rate > $bestRate) {
                if($bestContragentData->company == $contragentData->company) {
                    foreach(array('tel', 'fax', 'email', 'web', 'address', 'person') as $part) {
                        if($bestContragentData->{$part}) {
                            setIfNot($contragentData->{$part}, $bestContragentData->{$part});
                        }
                    }
                }
                
                $bestContragentData = $contragentData;
                $bestRate = $rate;
            }
            
            // Попълваме вербалното или индексното представяне на държавата, ако е налично другото
            if($bestContragentData->countryId && !$bestContragentData->country) {
                
                // Ако езика е на български
                if (core_Lg::getCurrent() == 'bg') {
                    $bestContragentData->country = drdata_Countries::fetchField($bestContragentData->countryId, 'commonNameBg');
                } else {
                    $bestContragentData->country = drdata_Countries::fetchField($bestContragentData->countryId, 'commonName');
                }
            }
            
            // Попълваме вербалното или индексното представяне на фирмата, ако е налично другото
            if($bestContragentData->companyId && !$bestContragentData->company) {
                $bestContragentData->company = crm_Companies::fetchField($bestContragentData->companyId, 'name');
            }
            
            // Попълваме вербалното или индексното представяне на държавата, ако е налично другото
            if(!$bestContragentData->countryId && $bestContragentData->country) {
                $bestContragentData->countryId = drdata_Countries::fetchField(array("LOWER(#commonName) LIKE '%[#1#]%'", mb_strtolower($bestContragentData->country)), 'id');
            }
            
            if(!$bestContragentData->countryId && $bestContragentData->country) {
                $bestContragentData->countryId = drdata_Countries::fetchField(array("LOWER(#formalName) LIKE '%[#1#]%'", mb_strtolower($bestContragentData->country)), 'id');
            }
            
            if(!$bestContragentData->countryId && $bestContragentData->country) {
                $bestContragentData->countryId = drdata_Countries::fetchField(array("LOWER(#commonNameBg) LIKE '%[#1#]%'", mb_strtolower($bestContragentData->country)), 'id');
            }
            
            $cashe[$threadId] = $bestContragentData;
        }
        
        if($field) {
            return $bestContragentData->{$field};
        } else {
            return $bestContragentData;
        }
    }
    
    
    /**
     * Изчислява точките (рейтинга) на подадения масив
     */
    static function calcPoints($data)
    {
        $dataArr = (array) $data;
        $points = 0;
        
        foreach($dataArr as $key => $value) {
            if(!is_scalar($value) || empty($value)) continue;
            $len = max(0.5, min(mb_strlen($value) / 20, 1));
            $points += $len;
        }
        
        if($dataArr['company']) $points += 3;
        
        return $points;
    }
    
    
    /**
     * Показва меню от възможности за добавяне на нови документи към посочената нишка
     * Очаква folderId
     */
    function act_ShowDocMenu()
    {
        expect($folderId = Request::get('folderId', 'int'));
        
        doc_Folders::requireRightFor('newdoc', $folderId);
        
        $rec = (object) array('folderId' => $folderId);
        
        $tpl = doc_Containers::getNewDocMenu($rec);
       	
        return $this->renderWrapping($tpl);
    }
    
    
    /**
     * Добавя към заявка необходимите условия, така че тя да връща само достъпните нишки.
     *
     * В резултат заявката ще селектира само достъпните за зададения потребител нишки които са
     * в достъпни за него папки (@see doc_Folders::restrictAccess())
     *
     * @param core_Query $query
     * @param int $userId key(mvc=core_Users) текущия по подразбиране
     */
    static function restrictAccess($query, $userId = NULL)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        doc_Folders::restrictAccess($query, $userId, FALSE);
        
        if ($query->mvc->className != 'doc_Threads') {
            // Добавя необходимите полета от модела doc_Threads
            $query->EXT('threadShared', 'doc_Threads', 'externalName=shared,externalKey=threadId');
        } else {
            $query->XPR('threadShared', 'varchar', '#shared');
        }
        
        $query->orWhere("#threadShared LIKE '%|{$userId}|%'");
    }
    
    
    /**
     * Връща езика на нишката
     * 
     * Първо проверява в обръщенията, после в контейнера
     *
     * @param int $id - id' то на нишката
     *
     * @return string $lg - Двубуквеното означение на предполагаемия език на имейла
     */
    static function getLanguage($id)
    {
        // Ако няма стойност, връщаме
        if (!$id) return ;
        
        // Записа на нишката
        $threadRec = doc_Threads::fetch($id);
        
        // id' то на контейнера на първия документ в треда
        $firstContId = $threadRec->firstContainerId;
        
        // Ако няма id на първия документ
        if (!$firstContId) return ;
        
        // Връщаме езика на контейнера
        return doc_Containers::getLanguage($firstContId);
    }

    
    /**
     * Връща титлата на нишката, която е заглавието на първия документ в нишката
     * 
     * @param integer $id
     * @param boolean $verbal - Дали да се върне вербалната стойност
     */
    static function getThreadTitle($id, $verbal=TRUE)
    {
        $rec = self::fetch($id);
        
        // Ако няма първи контейнер
        // При директно активиране на първия документ
        if (!($cid = $rec->firstContainerId)) {
            
            // Вземаме id' то на записа
            $cid = doc_Containers::fetchField("#threadId = '{$rec->id}'");
        }
        
        $document = doc_Containers::getDocument($cid);
        $docRow = $document->getDocumentRow();  
        
        if ($verbal) {
            $title = $docRow->title;
        } else {
            $title = $docRow->recTitle;
        }
        
        return $title;
    }
    
    /**
     * Връща линка на папката във вербален вид
     * 
     * @param array $params - Масив с частите на линка
     * @param $params['Ctr'] - Контролера
     * @param $params['Act'] - Действието
     * @param $params['threadId'] - id' то на нишката
     * 
     * @return core_ET|FALSE - Линк
     */
    static function getVerbalLink($params)
    {
        // Проверяваме дали е число
        if (!is_numeric($params['threadId'])) return FALSE;
        
        // Записите за нишката
        $rec = static::fetch($params['threadId']);

        // Проверяваме дали има права
        if (!$rec || !static::haveRightFor('single', $rec)) return FALSE;
        
        // Инстанция на първия документ
        $docProxy = doc_Containers::getDocument($rec->firstContainerId);
        
        // Вземаме колоните на документа
        $docRow = $docProxy->getDocumentRow();
        
        // Ескейпваме заглавието
        $title = $docRow->title;

        // Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
        $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
        
        // Иконата на нишката
        $sbfIcon = sbf($docProxy->getIcon($docProxy->that), '"', $isAbsolute);
        
        // Ако мода е xhtml
        if (Mode::is('text', 'xhtml')) {
            
            $res = new ET("<span class='linkWithIcon' style='background-image:url({$sbfIcon});'> [#1#] </span>", $title);
        } elseif (Mode::is('text', 'plain')) {
            
            // Ескейпваме плейсхолдърите и връщаме титлата
            $res = core_ET::escape($title);
        } else {
            
            // Атрибути на линка
            $attr = array();
            $attr['class'] = 'linkWithIcon';
            $attr['style'] = "background-image:url({$sbfIcon});";    
            $attr['target'] = '_blank'; 
            
            // Създаваме линк
            $res = ht::createLink($title, $params, NULL, $attr);  
        }
        
        return $res;
    }
    
    
    /**
     * Прави широчината на колонката със заглавието на треда да не се свива под 240px
     */
    function on_AfterPrepareListFields($mvc, $res, $data)
    {
        $data->listFields['title'] = "|*<div style='min-width:240px'>|" . $data->listFields['title'] . '|*</div>';
    }
    
    
    /**
     * Връща ключа за персонална настройка
     * 
     * @param integer $id
     * 
     * @return string
     */
    static function getSettingsKey($id)
    {
        $key = 'doc_Threads::' . $id;
        
        return $key;
    }
    
    
    /**
     * Може ли текущия потребител да пороменя сетингите на посочения потребител/роля?
     * 
     * @param string $key
     * @param integer $userOrRole
     * @see core_SettingsIntf
     */
    static function canModifySettings($key, $userOrRole=NULL)
    {
        // За да може да промени трябва да има достъп до сингъла на нишката
        // Да променя собствените си настройки или да е admin|ceo
        
        list(, $id) = explode('::', $key);
        
        $currUser = core_Users::getCurrent();
        
        if (!doc_Threads::haveRightFor('single', $id, $currUser)) return FALSE;
        
        if (!$userOrRole) return TRUE;
        
        if ($currUser == $userOrRole) {
            
            return TRUE;
        }
        
        if (haveRole('admin, ceo', $currUser)) {
            
            return TRUE;
        }
        
        return FALSE;
    }
    
    
    
    /**
     * Подготвя формата за настройки
     * 
     * @param core_Form $form
     * @see core_SettingsIntf
     */
    function prepareSettingsForm(&$form)
    {
        // Задаваме таба на менюто да сочи към документите
        Mode::set('pageMenu', 'Документи');
        Mode::set('pageSubMenu', 'Всички');
        $this->currentTab = 'Теми';
        
        // Вземаме id на папката от ключа
        list(, $threadId) = explode('::', $form->rec->_key);
        
        // Определяме заглавито
        $rec = $this->fetch($threadId);
        $row = $this->recToVerbal($rec, 'title');
        $form->title = 'Настройка на|* ' . $row->title;
        
        // Добавяме функционални полета
        $form->FNC('notify', 'enum(default=Автоматично, yes=Винаги, no=Никога)', 'caption=Известие при добавяне на документ->Известяване, input=input');
        
        $form->setDefault('notify', 'default');
        
        // Сетваме стринг за подразбиране
        $defaultStr = 'По подразбиране|*: ';
        
        // Ако сме в мобилен режим, да не е хинт
        $paramType = Mode::is('screenMode', 'narrow') ? 'unit' : 'hint';
        
        // Сетваме стойност по подразбиране
        $form->setParams('notify', array($paramType => $defaultStr . '|Винаги'));
    }
    
    
    /**
     * Проверява формата за настройки
     * 
     * @param core_Form $form
     * @see core_SettingsIntf
     */
    function checkSettingsForm(&$form)
    {
        
        return ;
    }
    
    
    /**
     * Преди подготвяне на пейджъра, ако има персонализация да се използва
     * 
     * @param doc_Threads $mvc
     * @param object $res
     * @param object $data
     */
    function on_BeforePrepareListPager($mvc, &$res, &$data)
    {
        // id на папката
        $folderId = Request::get('folderId');
        
        $key = doc_Folders::getSettingsKey($folderId);
        $vals = core_Settings::fetchKey($key);
        
        // Ако е зададено да се страницира
        if ($vals['perPage']) {
            
            // Променяме броя на страниците
            $mvc->listItemsPerPage = $vals['perPage'];
        }
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    public static function on_AfterCreate($mvc, $rec)
    {
    	self::invalidateDocumentCache($rec->id);
    }
    
    
    /**
     * Инвалидиране на кеша за видовете документи в папката
     */
    private static function invalidateDocumentCache($id)
    {
    	// Изтриваме от кеша видовете документи в папката и в коша и
    	$folderId = self::fetchField($id, 'folderId');
    	core_Cache::remove("doc_Folders", "folder{$folderId}");
    	core_Cache::remove("doc_Folders", "visibleDocumentsInFolder{$folderId}");
    }
}
