<?php



/**
 * Мениджър на ресурсите свързани с обекти
 *
 *
 * @category  bgerp
 * @package   mp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class mp_ObjectResources extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Ресурси на обекти';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_Created, mp_Wrapper';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,mp';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,mp';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,mp';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,mp';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,debug';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт,resourceId,objectId, createdOn,createdBy';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Ресурс на обект';
    
    
    /**
     * Активен таб
     */
    public $currentTab = 'Ресурси->Отношения';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('classId', 'class(interface=mp_ResourceSourceIntf)', 'input=hidden,silent');
    	$this->FLD('objectId', 'int', 'input=hidden,caption=Обект,silent');
    	$this->FLD('resourceId', 'key(mvc=mp_Resources,select=title,allowEmpty,makeLink)', 'caption=Ресурс,mandatory');
    	
    	// Поставяне на уникални индекси
    	$this->setDbUnique('classId,objectId,resourceId');
    }

    
    /**
     * Екшън създаващ нов ресурс и свързващ го с обекта
     */
    public function act_NewResource()
    {
    	mp_Resources::requireRightFor('add');
    	
    	expect($classId = Request::get('classId', 'int'));
    	expect($objectId = Request::get('objectId', 'int'));
    	
    	$this->requireRightFor('add', (object)array('classId' => $classId, 'objectId' => $objectId));
    	
    	$form = cls::get('core_Form');
    	$form->title = tr("Създаване на ресурс към") . " |*<b>" . cls::get($classId)->getTitleById($objectId) . "</b>";
    	$form->FNC('newResource', 'varchar', 'mandatory,caption=Нов ресурс,input');
    	$form->FNC('classId', 'class(interface=mp_ResourceSourceIntf)', 'input=hidden');
    	$form->FNC('objectId', 'int', 'input=hidden,caption=Обект');
    	
    	$form->setDefault('classId', $classId);
    	$form->setDefault('objectId', $objectId);
    	$form->input();
    	
    	// Ако формата е събмитната
    	if($form->isSubmitted()){
    		
    		// Трябва ресурса да е уникален
    		if(mp_Resources::fetch(array("#title = '[#1#]'", $form->rec->newResource))){
    			$form->setError("newResource", "Има вече запис със същите данни");
    		} else {
    			
    			// Създава нов запис и го свързва с обекта 
    			$type = cls::get($form->rec->classId)->getResourceType($form->rec->objectId);
    			$resourceId = mp_Resources::save((object)array('title' => $form->rec->newResource, 'type' => $type));
    			
    			$nRec = (object)array('classId' => $classId, 'objectId' => $objectId, 'resourceId' => $resourceId);
    			$this->save($nRec);
    		}
    		
    		if(!$form->gotErrors()){
    			return followRetUrl(NULL, tr('Успешно е добавен ресурса'));
    		}
    	}
    	
    	$form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close16.png');
        
        return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec = &$form->rec;
    	
    	$Class = cls::get($rec->classId);
    	expect(cls::haveInterface('mp_ResourceSourceIntf', $Class));
    	
    	$resourceType = $Class->getResourceType($rec->objectId);
    	
    	$options = mp_Resources::makeArray4Select('title', array("#type = '{$resourceType}'"));
    	
    	if(count($options)){
    		$form->setOptions('resourceId', $options);
    	} else {
    		$form->setReadOnly('resourceId');
    		$resourceType = cls::get('mp_Resources')->getFieldType('type')->toVerbal($resourceType);
    		$form->info = tr("|Няма ресурси от тип|* <b>'{$resourceType}'</b>");
    	}
    }
    
    
    /**
     * Подготвя показването на ресурси
     */
    public function prepareResources(&$data)
    {
    	$data->TabCaption = 'Ресурси';
    	$data->rows = array();
    	 
    	$classId = $data->masterMvc->getClassId();
    	 
    	$query = $this->getQuery();
    	$query->where("#classId = {$classId} AND #objectId = {$data->masterId}");
    	 
    	while($rec = $query->fetch()){
    		$data->rows[$rec->id] = $this->recToVerbal($rec);
    	}
    	 
    	if(!Mode::is('printing')) {
    		if(self::haveRightFor('add', (object)array('classId' => $classId, 'objectId' => $data->masterId))){
    			$type = $data->masterMvc->getResourceType($data->masterId);
    			if(mp_Resources::fetch("#type = '{$type}'")){
    				$data->addUrl = array($this, 'add', 'classId' => $classId, 'objectId' => $data->masterId, 'ret_url' => TRUE);
    			}
    			
    			$data->addUrlNew = array($this, 'NewResource', 'classId' => $classId, 'objectId' => $data->masterId, 'ret_url' => TRUE);
    		}
    	}
    }
    
    
    /**
     * Рендира показването на ресурси
     */
    public function renderResources(&$data)
    {
    	$tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
    	$classId = $data->masterMvc->getClassId();
    
    	$tpl->append(tr('Ресурси'), 'title');
    	$table = cls::get('core_TableView', array('mvc' => $this));
    
    	$tpl->append($table->get($data->rows, 'resourceId=Ресурс,createdOn=Създадено->На,createdBy=Създадено->На,tools=Пулт'), 'content');
    	
    	if(isset($data->addUrl)){
    		$tpl->append(ht::createBtn('Избор', $data->addUrl, NULL, NULL, 'ef_icon=img/16/star_2.png'), 'content');
    	}
    	
    	if(isset($data->addUrlNew)){
    		$tpl->append(ht::createBtn('Нов', $data->addUrlNew, NULL, NULL, 'ef_icon=img/16/star_2.png'), 'content');
    	}
    	
    	return $tpl;
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'add' || $action == 'delete' || $action == 'edit') && isset($rec)){
    		
    		$Class = cls::get($rec->classId);
    		$masterRec = $Class->fetchRec($rec->objectId);
    		
    		// Не може да добавяме запис ако не може към обекта, ако той е оттеглен или ако нямаме достъп до сингъла му
    		if($masterRec->state != 'active' || !$Class->haveRightFor('single', $rec->objectId)){
    			$res = 'no_one';
    		}
    	}
    	 
    	if($action == 'add' && isset($rec)){
    		
    		if(!$Class->canHaveResource($rec->objectId)){
    			$res = 'no_one';
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
    	$row->objectId = cls::get($rec->classId)->getHyperlink($rec->objectId, TRUE);
    	$row->objectId = "<span style='float:left'>{$row->objectId}</span>";
    	
    	$row->resourceId = mp_Resources::getHyperlink($rec->resourceId);
    }
    
    
    /**
     * След подготовка на лист тулбара
     */
    public static function on_AfterPrepareListToolbar($mvc, $data)
    {
    	$data->toolbar->removeBtn('btnAdd');
    }
    
    
    /**
     * Дали обекта е добавен като ресурс
     * 
     * @param mixed $class - клас
     * @param int $objectId - ид
     * @return boolean - Дали е добавен като ресурс или не
     */
    public static function isResource($class, $objectId)
    {
    	$Class = cls::get($class);
    	
    	// Проверяваме имали такъв запис
    	if(self::fetchField("#classId = {$Class->getClassId()} AND #objectId = {$objectId}")){
    		
    		return TRUE;
    	}
    	
    	return FALSE;
    }
}