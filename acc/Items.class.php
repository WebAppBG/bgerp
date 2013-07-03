<?php



/**
 * Мениджър на пера.
 *
 * Перата са детайли (master-detail) на модела Номенклатури (@see acc_Lists)
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_Items extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_State2, plg_RowTools, editwatch_Plugin, 
                     plg_SaveAndNew, acc_WrapperSettings, Lists=acc_Lists, plg_State2,plg_Sorting';
    
    
    /**
     * Заглавие
     */
    var $title = 'Пера';
    
    
    /**
     * Активен таб на менюто
     */
    var $menuPage = 'Счетоводство:Настройки';
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,acc';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,acc';
    
    
    /**
     * var $canList = 'admin,acc';
     */
    var $canAdmin = 'ceo,acc';
    
    
    /**
     * @var acc_Lists
     */
    var $Lists;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'num,titleLink=Наименование,uomId,lastUseOn,state,tools=Пулт';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Опашка от записи за записване в on_Shutdown
     * 
     * @var array Масив от записи на acc_Items (с ключове - ид-та на записи)
     * @see acc_Items::touch()
     */
    protected $touched = array();
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Разпознаваем от човек код на перото.
        $this->FLD('num', 'varchar(64)', "caption=Код,mandatory,remember=info,notNull,input=none");
        
        // Заглавие
        $this->FLD('title', 'varchar(64)', 'caption=Наименование,mandatory,remember=info,input=none');
        
        // Външен ключ към номенклатурата на това перо.
        $this->FLD('lists', 'keylist(mvc=acc_Lists,select=name)', 'caption=Номенклатури,input');
        
        // Външен ключ към модела (класа), генерирал това перо. Този клас трябва да реализира
        // интерфейса, посочен в полето `interfaceId` на мастъра @link acc_Lists 
        $this->FLD('classId', 'class(interface=acc_RegisterIntf,select=title,allowEmpty)',
            'caption=Регистър,input=hidden,silent');
        
        // Външен ключ към обекта, чиято сянка е това перо. Този обект е от класа, посочен в
        // полето `classId` 
        $this->FLD('objectId', 'int', "input=hidden,silent,column=none,caption=Обект");
        
        // Мярка на перото. Има смисъл само ако мастър номенклатурата е отбелязана като 
        // "оразмерима" (acc_Lists::dimensional == true). Мярката се показва и въвежда само 
        // ако има смисъл.
        $this->FLD('uomId', 'key(mvc=cat_UoM,select=name,allowEmpty)', 'caption=Мярка,remember');
        
        // Състояние на перото
        $this->FLD('state', 'enum(active=Активно,closed=Затворено)', 'caption=Състояние,input=none');
        
        // Кога за последно е използвано
        $this->FLD('lastUseOn', 'datetime', 'caption=Последно,input=none');
        
        // Титла - хипервръзка
        $this->FNC('titleLink', 'html', 'column=none');
        
        // Номер и титла - хипервръзка
        $this->FNC('numTitleLink', 'html', 'column=none');
        
        // Наименование 
        $this->FNC('caption', 'html', 'column=none');
        
        $this->setDbUnique('objectId,classId');
    }
    
    
    /**
     * За полето titleLink създава линк към обекта от регистъра
     *
     * @todo: Това не е добро решение, защото това функционално поле ще се изчислява в много случаи без нужда.
     */
    static function on_CalcTitleLink($mvc, $rec)
    {
        $title = $mvc->getVerbal($rec, 'title');
        $rec->titleLink = $title;
        
        if ($rec->classId && cls::load($rec->classId, TRUE)) {
            $AccRegister = cls::get($rec->classId);
            
            if(method_exists($AccRegister, 'getLinkToObj')) {
                $rec->titleLink = $AccRegister->getLinkToObj($rec->objectId);
            } elseif(method_exists($AccRegister, 'act_Single')) {
                if($AccRegister->haveRightFor('single', $rec->objectId)) {
                    $rec->titleLink = ht::createLink($title, array($AccRegister, 'Single', $rec->objectId));
                }
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_CalcNumTitleLink($mvc, $rec)
    {
        if (!isset($rec->titleLink)) {
            $mvc->on_CalcTitleLink($mvc, $rec);
        }
        $rec->numTitleLink = $rec->num . '. ' . $rec->titleLink;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_CalcCaption($mvc, $rec)
    {
        $rec->caption = $mvc->getVerbal($rec, 'num') . '&nbsp;' . $mvc->getVerbal($rec, 'title');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_AfterGetVerbal($mvc, &$num, $rec, $part)
    {
        if($part == 'num') {
            $listRec = $mvc->Lists->fetch($mvc->getCurrentListId());
            $maxNumLen = strlen($listRec->itemMaxNum);
            $num = str_pad($num, $maxNumLen, '0', STR_PAD_LEFT);
            $num = str_replace('&nbsp;', '', $num);
        }
    }
    
    
    /**
     * Изпълнява се преди подготовката на редовете в таблицата
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy('#num');
    }
    
    
    /**
     * Изпълнява се след запис на перо
     * Предизвиква обновяване на обобщената информация за перата
     */
    static function on_AfterSave($mvc, $id, $rec)
    {
        $affectedLists = keylist::toArray($rec->lists);
        
        foreach ($affectedLists as $listId) {
            $mvc->Lists->updateSummary($listId);
        }
    }
    
    
    /**
     * Изпълнява се преди изтриване на пера
     * Събира информация, на кои номенклатури трябва да си обновят информацията
     */
    static function on_BeforeDelete($mvc, &$numRows, $query, $cond)
    {
        $tmpQuery = clone($query);
        $query->_listsForUpdate = array();
        
        while($rec = $tmpQuery->fetch($cond)) {
            $query->_listsForUpdate += keylist::toArray($rec->lists);
        }
    }
    
    
    /**
     * Изпълнява се след изтриване на пера
     * Предизвиква обновяване на информацията на подбрани преди изтриване номенклатури
     */
    static function on_AfterDelete($mvc, &$numRows, $query, $cond)
    {
        if(count($query->_listsForUpdate)) {
            foreach($query->_listsForUpdate as $listId) {
                $mvc->Lists->updateSummary($listId);
            }
        }
    }
    
    
    /**
     * Извиква се преди подготовката на титлата в списъчния изглед
     */
    static function on_AfterPrepareListTitle($mvc, $data, $data)
    {
        $listId = $mvc->getCurrentListId();
        $listRec = $mvc->Lists->fetch($listId);
        
        $data->title = "Пера в номенклатурата|* <font color=green> {$listRec->caption} </font>";
        
        return FALSE;
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    static function on_AfterPrepareEditForm($mvc, $data)
    {
        /* @var $form core_Form */
        $form = $data->form;
        $rec  = &$form->rec;
        
        if (!$rec->id && $rec->classId && $rec->objectId) {
            if ($_rec = $mvc::fetchItem($rec->classId, $rec->objectId)) {
                $rec = $_rec;
            }
        }
        
        if ($rec->classId && $rec->objectId) {
            /* @var $register acc_RegisterIntf */
            expect($register = core_Cls::getInterface('acc_RegisterIntf', $rec->classId));
            
            $form->setField('num', 'input=none');
            $form->setField('title', 'input=none');
            
            if (!$register->isDimensional()) {
                $form->setHidden('uomId');
            }
            
            if (!$rec->id) {
                // Попълва полетата на $rec с данни извлечени от съотв. регистър
                static::syncItemRec($rec, $register, $rec->objectId);
                
                $mvc::on_CalcNumTitleLink($mvc, $rec);
            }
            
            expect(isset($rec->numTitleLink));
            
            $form->info = $rec->numTitleLink;
        }
        
        $form->fields['lists']->type->suggestions = acc_Lists::getPossibleLists($rec->classId);
        $form->setDefault('ret_url', Request::get('ret_url'));
        
        if ($rec->id) {
            $form->title = 'Редактиране на перо';
        }
    }
    
    
    /**
     * Изпълнява се след въвеждане на данните от заявката във формата
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    static function on_AfterInputEditForm($mvc, $form)
    {
        if(!$form->rec->id) {
            $listId = $mvc->getCurrentListId();
            Mode::setPermanent('lastEnterItemNumIn' . $listId, $rec->num);
        }
        
//         bp($form->rec);
    }
    
    
    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    static function on_AfterPrepareListFields($mvc, $data)
    {
        $listId = $mvc->getCurrentListId();
        $listRec = $mvc->Lists->fetch($listId);
        
        if($listRec->dimensional == 'no') {
            unset($data->listFields['uomId']);
        }
        
        if($listRec->regInterfaceId) {
            unset($data->listFields['tools']);
        }
    }
    
    
    /**
     * Добавя филтър към перата
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('listId', 'key(mvc=acc_Lists,select=name)', 'input,caption=xxx');
        $data->listFilter->FNC('search', 'varchar', 'caption=Търсене,input,silent');
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'listId, search';
        
        $data->listFilter->setDefault('listId', $listId = $mvc->getCurrentListId());
        
        $filter = $data->listFilter->input();
        
        expect($filter->listId);
        
        $data->query->where("#lists LIKE '%|{$filter->listId}|%'");
        
        if($filter->search) {
            $data->query->where(array("#title LIKE '[#1#]'", "%{$filter->search}%"));
        }
    }
    
    
    /**
     * Какви роли са необходими
     */
    static function on_BeforeGetRequiredRoles($mvc, &$roles, $cmd)
    {
        return;
        if($cmd == 'write') {
            $listId = $mvc->getCurrentListId();
            
            $listRec = $mvc->Lists->fetch($listId);
            
            if(!$listRec || $listRec->regInterfaceId) {
                $roles = 'no_one';
                
                return FALSE;
            }
        }
    }
    
    
    /**
     * Тази функция връща текущата номенклатура, като я открива по първия възможен начин:
     *
     * 1. От Заявката (Request)
     * 2. От Сесията (Mode)
     * 3. Първата активна номенклатура от таблицата
     */
    function getCurrentListId()
    {
        $listId = Request::get('listId', 'key(mvc=acc_Lists,select=name)');
        
        if(!$listId) {
            $listId = Mode::get('currentListId');
        }
        
        if(!$listId) {
            $listQuery = $this->Lists->getQuery();
            $listQuery->orderBy('num');
            $listRec = $listQuery->fetch('1=1');
            $listId = $listRec->id;
        }
        
        if($listId) {
            Mode::setPermanent('currentListId', $listId);
        } else {
            redirect(array('acc_Lists'));
        }
        
        return $listId;
    }
    
    
    /**
     * Извлича опциите според id-то на номенклатурата
     */
    function fetchOptions($listId)
    {
        $query = $this->getQuery();
        
        $query->orderBy("#num");
        
        while($rec = $query->fetch("#lists LIKE '%|{$listId}|%' AND #state = 'active'")) {
            $options[$rec->id] = $this->getVerbal($rec, 'caption');
        }
        
        return $options;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getItemsKeys($objectKeys, $listId) {
        $query = $this->getQuery();
        $query->where("#lists LIKE '%|{$listId}|%'");
        $query->where("#objectId IN (" . implode(',', $objectKeys) . ')');
        
        $result = array();
        
        while ($rec = $query->fetch()) {
            $result[$rec->objectId] = $rec->id;
        }
        
        return $result;
    }
    
    
    public static function prepareObjectLists($data)
    {   
        $data->TabCaption = 'Номенклатури';

        /* @var $masterMvc core_Mvc */
        $masterMvc = $data->masterMvc; 
        
        $classId  = $masterMvc::getClassId();
        $objectId = $data->masterId;
        
        $data->itemRec = static::fetchItem($classId, $objectId);
        $data->canChange = static::haveRightFor('edit', $data->itemRec);
    }
    
    
    public static function renderObjectLists($data)
    {
        $masterMvc = $data->masterMvc;
        
        try {
            $tpl = $masterMvc::getDetailWrapper();
        } catch (core_exception_Expect $e) {
            $tpl = new ET(getFileContent('crm/tpl/ContragentDetail.shtml'));
        }
        
        $tpl->append(tr('Номенклатури'), 'title');
        
        if($data->canChange && !Mode::is('printing')) {
            $url = array(get_called_class(), 'edit', 'classId'=>$masterMvc::getClassId(), 'objectId'=>$data->masterId, 'ret_url' => TRUE);
            $img = "<img src=" . sbf('img/16/edit.png') . " width='16' height='16' />";
            $tpl->append(
                ht::createLink(
                    $img, $url, FALSE,
                    'title=' . tr('Промяна')
                ),
                'title'
            );
        }
        
        if ($data->itemRec->lists) {
            $content = static::getVerbal($data->itemRec, 'lists');
            $tpl->append($content, 'content');
        } else {
           $tpl->append(tr("Не е включен в номенклатура"), 'content');
        }
        
        return $tpl;
    }
    
    
    /**
     * Помощен метод за извличане на перо със зададени регистър и ключ в регистъра
     * 
     * @param int $classId
     * @param int $objectId
     * @param mixed $fields списък от полета на acc_Items, които да бъдат извлечени
     */
    protected static function fetchItem($classId, $objectId, $fields = NULL)
    {
        return static::fetch("#classId = {$classId} AND #objectId = {$objectId}", $fields);
    }
    
    
    /**
     * След промяна на запис на мениджър, на който acc_Items е екстендер
     * 
     * Това събитие се генерира от @see groups_Extendable
     * 
     * @param acc_Items $mvc
     * @param stdClass $regRec
     * @param core_Mvc $master
     */
    public static function on_AfterMasterSave(acc_Items $mvc, stdClass $regRec, core_Mvc $master)
    {
        $mvc::syncItemWith($master, $regRec->id);
    }
    
    
    /**
     * Синхронизира запис от регистър на пера със съответното му номенклатурно перо.
     * 
     * @param core_Mvc $master
     * @param int $objectId;
     */
    public static function syncItemWith(core_Mvc $master, $objectId)
    {
        // Синхронизирането е възможно само с мениджъри поддържащи acc_RegisterIntf
        if (!core_Cls::haveInterface('acc_RegisterIntf', $master)) {
            return;
        }
        
        $classId  = $master::getClassId();
        
        if ($itemRec = static::fetchItem($classId, $objectId)) {
            static::syncItemRec($itemRec, $master, $itemRec->objectId);
            static::save($itemRec);
        }
    }
    
    
    /**
     * Синхронизира запис-перо с автентични данни извлечени от регистъра.
     * 
     * @param acc_RegisterIntf $register
     * @param int $objectId
     * @param stdClass $itemRec
     */
    public static function syncItemRec(&$itemRec, $register, $objectId)
    {
        if (is_scalar($register)) {
            $register = cls::get($register);
        }

        if (!$regRec = $register->getItemRec($objectId)) {
            return FALSE;
        }
        
        if ($regRec) {
            $itemRec->num      = $regRec->num; 
            $itemRec->title    = $regRec->title;
            $itemRec->uomId    = $regRec->uomId;
            $itemRec->features = $regRec->features;
                    
            if (!empty($register->autoList)) {
                // Автоматично добавяне към номенклатурата $autoList
                expect($autoListId = acc_Lists::fetchField(array("#systemId = '[#1#]'", $register->autoList), 'id'));
                $itemRec->lists = keylist::addKey($itemRec->lists, $autoListId);
            }
        }
            
        return TRUE;
    }
    
    
    /**
     * Създава (ако липсва) перо, добавя го в номенклатира (ако не е) и го маркира като използвано
     * 
     * @param int $classId
     * @param int $objectId
     * @param int $listId
     * @return int ИД на перото
     */
    public static function force($classId, $objectId, $listId)
    {
        $rec = self::fetchItem($classId, $objectId);
        
        if (empty($rec)) {
            // Няма такова перо - създаваме ново и го добавяме в номенклатурата $listId
            $rec = new stdClass();
            expect($register = core_Cls::getInterface('acc_RegisterIntf', $classId));
            self::syncItemRec($rec, $register, $objectId);
        }
        
        $rec->classId  = $classId;
        $rec->objectId = $objectId;
        
        if (!empty($rec->id) && keylist::isIn($listId, $rec->lists)) {
            // Идеята е да се буферира многократното обновяване на едно и също перо само за
            // да му се смени състоянието и датата на последно използване
            self::touch($rec);
        } else {
            
            // Ако перото не е в номенкл. $listId (независимо дали се създава за пръв път или
            // вече го има), добавяме го и записваме на момента.
            $rec->lists = keylist::addKey($itemRec->lists, $listId);
            $rec->state      = 'active';
            $rec->lastUseOn = dt::now();
        
            self::save($rec);
        }
        
        return $rec->id;
    }
    
    
    /**
     * Запомня запис на перо за по късно обновление.
     * 
     * @param stdClass $rec
     */
    public static function touch($rec)
    {
        /*
         * Вземаме инстация на acc_Items за да подсигурим извикването на acc_Items::on_Shutdown()
         * 
         * @var $Items acc_Items
         */
        $Items = cls::get(__CLASS__);
        
        $rec->state      = 'active';
        $rec->lastUseOn = dt::now();
        
        expect($rec->id);
        
        // Тук само запомняме какво е "пипнато" (използвано). Същинското обновяване се прави
        // в on_Shutdown()
        $Items->touched[$rec->id] = $rec;
    }

    
    /**
     * Изчиства записите, заопашени за запис 
     * 
     * @param acc_Items $mvc
     */
    public static function on_Shutdown($mvc)
    {
        foreach ($mvc->touched as $rec) {
            $mvc->save($rec);
        }
    }
}
