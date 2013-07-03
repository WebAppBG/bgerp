<?php 


/**
 * Документ с който се сигнализара някакво несъответствие
 *
 * @category  bgerp
 * @package   support
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class support_Issues extends core_Master
{
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'issue_Document';
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Сигнали';
    
    
    /**
     * 
     */
    var $singleTitle = 'Сигнал';
    
    
    /**
     * 
     */
    var $abbr = 'Sig';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'user';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'user';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'user';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'user';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo, admin, support';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'admin, support';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Кой може да възлага задачата
     */
    var $canAssign = 'support, admin, ceo';
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Кой може да променя активирани записи
     */
    var $canChangerec = 'support, admin, ceo';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'support_Wrapper, doc_DocumentPlg, plg_RowTools, plg_Printing, doc_ActivatePlg, bgerp_plg_Blank, plg_Search, 
    				doc_SharablePlg, doc_AssignPlg, plg_Sorting, change_Plugin';

    
    /**
     * Дали може да бъде само в началото на нишка
     */
    // TODO може да се добави в папки на някои фирми, където да се добави по средата на нишката
    var $onlyFirstInThread = TRUE;
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'support/tpl/SingleLayoutIssue.shtml';
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    var $singleIcon = 'img/16/support.png';

    
    /**
     * Поле за търсене
     */
    var $searchFields = 'componentId, typeId, description';
    
    
    /**
     * 
     */
    var $listFields = 'id, title, systemIdShow, componentId, typeId';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'title';
    
    
    /**
     * 
     */
    var $cloneFields = 'componentId, typeId, title, description, priority';
	
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "10.1|Поддръжка";
	
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('typeId', 'key(mvc=support_IssueTypes, select=type)', 'caption=Тип, mandatory, width=100%');
        $this->FLD('title', 'varchar', "caption=Заглавие, mandatory, width=100%");
        $this->FLD('description', 'richtext(rows=10,bucket=Support)', "caption=Описание, width=100%, mandatory");
        $this->FLD('componentId', "key(mvc=support_Components,select=name,allowEmpty)", 'caption=Компонент, changable');
        $this->FLD('priority', 'enum(normal=Нормален, warning=Висок, alert=Критичен)', 'caption=Приоритет');
        
        // Възлагане на задача (за doc_AssignPlg)
        $this->FLD('assign', 'user(roles=powerUser, allowEmpty)', 'caption=Възложено на,input=none, changable');
        
        // Споделени потребители
        $this->FLD('sharedUsers', 'userList(roles=support)', 'caption=Споделяне->Потребители');

        $this->FNC('systemIdShow', 'key(mvc=support_Systems, select=name)', 'caption=Система, mandatory, input=none');
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param int $folderId - id на папката
     * @return boolean
     */
    public static function canAddToFolder($folderId)
    {
        // Името на класа
        $coverClassName = doc_Folders::fetchCoverClassName($folderId);
        
        // Ако не support_systems, не може да се добави
        if (strtolower($coverClassName) != 'support_systems') return FALSE;
    }
    
    
    /**
     * 
     */
    function on_CalcSystemIdShow($mvc, $rec)
    {
        // Ако има компонент
        if ($rec->componentId) {
            
            // systemId на съответния компонент
            $systemId = support_Components::fetchField($rec->componentId, 'systemId');   
            
            $rec->systemIdShow = $systemId;
        } elseif ($rec->folderId) {
            
            // Вземаме ковъра на папката
            $cover = doc_Folders::getCover($rec->folderId);
            
            // Ако ковъра е support_Systems
            if ($cover->className == 'support_Systems') {
                
                // Използваме id' то
                $rec->systemIdShow = $cover->that;
            }
        }
    }
    
    
    /**
     * 
     */
    function on_AfterPrepareListRows($mvc, $res, $data) {
        
        // Обхождаме записите
        foreach ((array)$data->recs as $key => $rec) {
            
            // Ако има id на система
            if ($rec->systemIdShow) {
                
                // Вземаме id' то на папката
                $folderId = support_Systems::forceCoverAndFolder($rec->systemIdShow);
                
                // Ако нямамем права за папката, прескачаме
                if (!doc_Folders::haveRightFor('single', $folderId)) continue;
                
                // Линк към папката
                $folderLink = ht::createLink($data->rows[$key]->systemIdShow, array('doc_Threads', 'list', 'folderId' => $folderId));
                
                // Заместваме името на системата с линк към папката
                $data->rows[$key]->systemIdShow = $folderLink;
            }
        }
    }
    
    
	/**
     * Реализация  на интерфейсния метод ::getThreadState()
     */
    static function getThreadState($id)
    {
        
        return 'opened';
    }
    
    
    /**
     * 
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        // Нормален приоритет по подразбиране
        $data->form->setDefault('priority', 'normal');
        
        // Вземаме systemId' то на документа от URL' то
        $systemId = Request::get('systemId', 'key(mvc=support_Systems, select=name)');
        
        // Опитваме се да вземеме return ult' то
        $retUrl = getRetUrl();

        // Ако има systemId
        if ($systemId) {
            
            // Вземаме записите
            $iRec = support_Systems::fetch($systemId);
            
            // Ако имаме права за single до папката
            if ($iRec->folderId && doc_Folders::haveRightFor('single', $iRec->folderId)) {    
                
                // Форсираме създаването на папката
                $folderId = support_Systems::forceCoverAndFolder($iRec);
                
                // Задаваме id' то на папката
                $data->form->rec->folderId = $folderId;    
            }
        } 
        
        // Ако все още не сме определили папката
        if (!$folderId) {
            
            // Ако няма подадено systemId, вземаме id' то на папката по подразбиране
            $folderId = $data->form->rec->folderId;
        }
        
        // Записите за класа, който се явява корица
        $coverClassRec = doc_Folders::fetch($folderId);
        
        // Задаваме systemId да е id' то на ковъра
        $systemId = $coverClassRec->coverId;
        
        // Всички системи, които наследяваме
        $allSystemsArr = support_Systems::getSystems($systemId);

        // Премахваме текущатата
        unset($allSystemsArr[$systemId]);
        
        // Извличаме всички компоненти, със съответното systemId или прототип
        $query = support_Components::getQuery();
        
        $query->where("#systemId = '{$systemId}'");
        
        // Обхождаме всики наследени системи
        foreach ($allSystemsArr as $allSystemId) {
            
            // Добавяме OR
            $query->orWhere("#systemId = '{$allSystemId}'");
        }

        // Обхождаме всички открити резултати
        while ($rec = $query->fetch()) {
            
            // Създаваме масив с компонентите
            $components[$rec->id] = support_Components::getVerbal($rec, 'name');
        }

        // Ако няма въведен компонент
        if (!$components) {
            
            // Добавяме съобщение за грешка
            core_Statuses::add(tr('Няма въведен компонент на системата.'));
            
            // Ако има права за добавяне на компонент
            if (support_Components::haveRightFor('add')) {
                
                // Линк за препращаме към станицата за добавяне на компонент
                $redirectArr = array('support_Components', 'add', 'systemId' => $systemId, 'ret_url' => $retUrl);    
            } else {
                
                // Ако нямаме права, препащаме където сочи return URL' то
                $redirectArr = $retUrl;
            }
            
            // Препащаме
            return redirect($redirectArr);
        }
        
        // Премахваме повтарящите се
        $components = array_unique($components);
        
        // Променяме съдържанието на полето компоненти с определения от нас масив
        $data->form->setOptions('componentId', $components);
        
        // Запитване за извличане на системите
        $sQuery = support_Systems::getQuery();
        
        $sQuery->where($systemId);
        
        // Обхождаме всики наследени системи
        foreach ($allSystemsArr as $allSystemId) {
            
            // Добавяме OR
            $sQuery->orWhere($allSystemId);
        }
        
        // Обхождаме всички открити записи
        while ($sRec = $sQuery->fetch()) {
            
            // Обединяваме всички позволени типове
            $allowedTypes = keylist::merge($sRec->allowedTypes, $allowedTypes);
        }

        // Разрешените типове за съответната система
        $allowedTypesArr = keylist::toArray($allowedTypes);

        // Обхождаме масива с всички разрешени типове
        foreach ($allowedTypesArr as $allowedType) {
            
            // Добавяме в масива вербалната стойност на рарешените типове
            $types[$allowedType] = support_IssueTypes::getVerbal($allowedType, 'type');
        }
        
        // Променяме съдържанието на полето тип с определения от нас масив, за да се показват само избраните
        $data->form->setOptions('typeId', $types);

        // Ако няма роля support
        if (!haveRole('support')) {
            
            // Скриваме полето за споделяне
            $data->form->setField('sharedUsers', 'input=none');
        }
    }
    
    
    /**
     * Затваря сигналите в даден тред
     * 
     * @param doc_Threads $threadId - id на нишката
     */
    static function closeIssue($threadId)
    {
        // Вземаме всички сингнали от нишката 
        // По сегашната логика трябва да е само един
        $query = static::getQuery();
        $query->where("#threadId = '{$threadId}'");
        
        // Обхождаме записите
        while ($rec = $query->fetch()) {
            
            // Сменяме състоянието на нишката на затворена
            $rec->state = 'closed';
            static::save($rec);
        }
    }
    
    
    /**
     * Подготовка на форма за филтър на списъчен изглед
     */
    static function on_AfterPrepareListFilter($mvc, &$data)
    {
        // Подреждаме по дата по - новите по - напред
        $data->query->orderBy('createdOn', 'DESC');
        
        // Подреждаме сиганлите активните отпред, затворените отзад а другите по между им
        $data->query->XPR('orderByState', 'int', "(CASE #state WHEN 'active' THEN 1 WHEN 'closed' THEN 3 ELSE 2 END)");
        $data->query->orderBy('orderByState');
        
        // Задаваме на полета да имат възможност за задаване на празна стойност
        $data->listFilter->getField('systemIdShow')->type->params['allowEmpty'] = TRUE;
        $data->listFilter->getField('componentId')->type->params['allowEmpty'] = TRUE;
         
        // Добавяме функционално поле за отговорници
        $data->listFilter->FNC('maintainers', 'type_Users(rolesForAll=support|ceo|admin)', 'caption=Отговорник,input,silent', array('attr' => array('onchange' => 'this.form.submit();')));
        
        // Кои полета да се показват
        $data->listFilter->showFields = 'systemIdShow, componentId, maintainers';
        
        // Добавяме бутон за филтриране
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Да се показват в хоризонтална подредба
        $data->listFilter->view = 'horizontal';
        
        // Вземаме стойността по подразбиране, която може да се покаже
        $default = $data->listFilter->getField('maintainers')->type->fitInDomain('all_users');
        
        // Задаваме стойността по подразбиране
        $data->listFilter->setDefault('maintainers', $default);

        // Полетата да не са задължителни и да се субмитва формата при промяната им
        $data->listFilter->setField('componentId', array('attr' => array('onchange' => 'this.form.submit();')));
        $data->listFilter->setField('componentId', array('mandatory' => FALSE));
        $data->listFilter->setField('systemIdShow', array('attr' => array('onchange' => 'this.form.submit();')));
        $data->listFilter->setField('systemIdShow', array('mandatory' => FALSE));
    }
    

    /**
     * 
     */
    static function on_BeforePrepareListRecs($mvc, &$res, &$data)
    {
        // id' то на системата
        $systemId = $data->listFilter->rec->systemIdShow;
        
        // Ако е избрана система
        if ($systemId) {
            
            // Добавяме външно поле за търсене
            $data->query->EXT("systemId", 'support_Components', "externalName=systemId");

            // Да се показват само сигнали от избраната система
            $data->query->where("#systemId = '{$systemId}'");
            $data->query->where("#componentId = `support_components`.`id`");
        }
        
        // Вземаме всички компоненти от избраната система
        $componentsArr = support_Components::getSystemsArr($systemId);
        
        // Ако има компоненти
        if (count($componentsArr)) {
            
            // Задаваме ги да се показват те
            $data->listFilter->setOptions('componentId', $componentsArr);    
        } else {
            
            // Добавяме празен стринг, за да не се покажат всичките записи 
            $data->listFilter->setOptions('componentId', array('' => ''));
        }
        
        // id' то на компонента
        $componentId = $data->listFilter->rec->componentId;
        
        // Ако е избран компонент
        if ($componentId) {
            
            // Масив с id' тата на еднаквите компоненти по име
            $sameComponentsArr = support_Components::getSame($componentId);
            
            // Обхождаме масива
            foreach ($sameComponentsArr as $sameVal) {
                
                // Ако го има в избраните
                if (isset($componentsArr[$sameVal])) {

                    // Добавяме във where
                    $data->query->orWhereArr('componentId', $sameComponentsArr);  
                    
                    // Прекъсваме по нататъшното изпълнение
                    break;
                }
            }
        }
        
        // Отговорници
        $maintainers = $data->listFilter->rec->maintainers;

        // Очакваме да има избран
        expect($maintainers, 'Няма избран отговорник.');  
            
        // Ако не е избран всички потребители
        if ($maintainers != 'all_users') {
            
            // Ако не са избрани всички потребители
            if (strpos($maintainers, '|-1|') === FALSE) {
        
                // Добавяме външно поле за търсене
                $data->query->EXT("componentMaintainers", 'support_Components', "externalName=maintainers");
        
                // Да се показват само сигнали за избран потребител
                $data->query->likeKeylist("componentMaintainers", $maintainers);
                $data->query->where("#componentId = `support_components`.`id`");
            }        
        }
    }
    
    
	/**
     * Интерфейсен метод на doc_DocumentInterface
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
     
        $row = new stdClass();
        
        // Типа
        $type = static::getVerbal($rec, 'typeId');

        // Компонента
        $component = static::getVerbal($rec, 'componentId');
        
        // Добавяме типа към заглавието
        $row->title    =  $this->getVerbal($rec, 'title');
        
        // Ако е възложено на някой
        if ($rec->assign) {
            
            // В заглавието добавяме потребителя
            $row->subTitle = $this->getVerbal($rec, 'assign') . ", ";   
        }
        
        $row->subTitle .= "{$type}, {$component}";

        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        $row->state = $rec->state;
        
        $row->recTitle = $rec->title;
        
        return $row;
    }
    
    
    /**
     * Потребителя, на когото е възложена задачата
     */
    function on_AfterGetShared($mvc, &$shared, $id)
    {
        // Ако има споделени потребители връщамес
        if ($shared) return ;
        
        // Вземаме записа
        $rec = $mvc->fetch($id);
        
        // Ако не е активен, връщаме
        if ($rec->state != 'active') return ;
        
        // Ако има компонент
        if ($rec->componentId) {
            
            // Отговорниците на компонента
            $maintainers = support_Components::fetchField($rec->componentId, 'maintainers');

            // Към отговорниците да не се показва създателя
            $maintainers = keylist::removeKey($maintainers, $rec->createdBy);
            
            // Добавяме към споделените
            $shared = keylist::merge($maintainers, $shared);
        }
    }
}
