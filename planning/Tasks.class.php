<?php


/**
 * Мениджър на задачи за производство
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Задачи за производство
 */
class planning_Tasks extends tasks_Tasks
{
    
    
	/**
	 * Интерфейси
	 */
    public $interfaces = 'label_SequenceIntf';
    
    
	/**
	 * Свойство, което указва интерфейса на вътрешните обекти
	 */
	public $driverInterface = 'planning_DriverIntf';
	
	
	/**
	 * Шаблон за единичен изглед
	 */
	public $singleLayoutFile = 'planning/tpl/SingleLayoutTask.shtml';
	
	
	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Master &$mvc)
	{
		expect(is_subclass_of($mvc->driverInterface, 'tasks_DriverIntf'), 'Невалиден интерфейс');
	}
	
	
	/**
	 * Плъгини за зареждане
	 */
	public $loadList = 'doc_plg_BusinessDoc,doc_DocumentPlg, planning_plg_StateManager, planning_Wrapper, acc_plg_DocumentSummary, plg_Search, change_Plugin, plg_Clone, plg_Printing,plg_RowTools2,bgerp_plg_Blank';
	
	
	/**
	 * Заглавие
	 */
	public $title = 'Задачи за производство';
	
	
	/**
	 * Еденично заглавие
	 */
	public $singleTitle = 'Задача за производство';
	
	
	/**
	 * Абревиатура
	 */
	public $abbr = 'Pts';
	
	
	/**
	 * Групиране на документите
	 */
	public $newBtnGroup = "3.8|Производство";
	
	
	/**
	 * Клас обграждащ горния таб
	 */
	public $tabTopClass = 'portal planning';
	
	
	/**
	 * Да не се кешира документа
	 */
	public $preventCache = TRUE;
	
	
	/**
	 * Дали винаги да се форсира папка, ако не е зададена
	 * 
	 * @see doc_plg_BusinessDoc
	 */
	public $alwaysForceFolderIfEmpty = TRUE;
	
	
	/**
	 * Подготовка на формата за добавяне/редактиране
	 */
	public static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$rec = &$data->form->rec;
		
		if(empty($rec->id)){
			if($folderId = Request::get('folderId', 'key(mvc=doc_Folders)')){
				unset($rec->threadId);
				$rec->folderId = $folderId;
			}
		}
	}
	
	
	/**
	 * След рендиране на задачи към задание
	 * 
	 * @param core_Manager $mvc
	 * @param stdClass $data
	 * @return void
	 */
	public static function on_AfterPrepareTasks($mvc, &$data)
	{
		if(Mode::isReadOnly()) return;
		$masterRec = $data->masterData->rec;
		$containerId = $data->masterData->rec->containerId;
		$defDriver = planning_drivers_ProductionTask::getClassId();
		
		// Може ли на артикула да се добавят задачи за производство
		$defaultTasks = cat_Products::getDefaultProductionTasks($data->masterData->rec->productId, $data->masterData->rec->quantity);
		
		$departments = keylist::toArray($masterRec->departments);
		if(!count($departments) && !count($defaultTasks)){
			$departments = array('' => NULL);
		}
		
		$sysId = (count($defaultTasks)) ? key($defaultTasks) : NULL;
		
		$draftRecs = array();
		foreach ($departments as $depId){
			$depFolderId = isset($depId) ? hr_Departments::forceCoverAndFolder($depId) : NULL;
			
			$r = new stdClass();
			$r->folderId    = $depFolderId;
			$r->title       = cat_Products::getTitleById($masterRec->productId);
			$r->systemId    = $sysId;
			$r->driverClass = $defDriver;
			
			if(!$sysId){
				$r->productId = $masterRec->productId;
			}
			
			$draftRecs[]    = $r;
		}
		
		if(count($defaultTasks)){
			foreach ($defaultTasks as $index => $taskInfo){
		
				// Имали от създадените задачи, такива с този индекс
				$foundObject = array_filter($data->recs, function ($a) use ($index) {
					return $a->systemId == $index;
				});
		
				// Ако има не показваме дефолтната задача
				if(is_array($foundObject) && count($foundObject)) continue;
			
				$r = new stdClass();
				$r->title       = $taskInfo->title;
				$r->systemId    = $index;
				$r->driverClass = $taskInfo->driver;
				$draftRecs[]    = $r;
			}
		}
		
		// Вербализираме дефолтните записи
		foreach ($draftRecs as $draft){
			if(!$mvc->haveRightFor('add', (object)array('originId' => $containerId, 'driverClass' => $draft->driverClass))) continue;
		
			$url = array('planning_Tasks', 'add', 'folderId' => $draft->folderId, 'originId' => $containerId, 'driverClass' => $draft->driverClass, 'title' => $draft->title, 'ret_url' => TRUE);
			if(isset($draft->systemId)){
				$url['systemId'] = $draft->systemId;
			} else {
				$url['productId'] = $draft->productId;
			}
			
			$row = new stdClass();
			core_RowToolbar::createIfNotExists($row->_rowTools);
			$row->_rowTools->addLink('', $url, array('ef_icon' => 'img/16/add.png', 'title' => "Добавяне на нова задача за производство"));
				
			$row->title = cls::get('type_Varchar')->toVerbal($draft->title);
			$row->ROW_ATTR['style'] .= 'background-color:#f8f8f8;color:#777';
			if(isset($draft->folderId)){
				$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($draft->folderId))->title;
			}
				
			$data->rows[] = $row;
		}
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if(isset($rec) && empty($rec->originId)){
			$requiredRoles = 'no_one';
		}
		
		if($action == 'add' && isset($rec->originId)){
			// Може да се добавя само към активно задание
			if($origin = doc_Containers::getDocument($rec->originId)){
				if(!$origin->isInstanceOf('planning_Jobs')){
					$requiredRoles = 'no_one';
				}
			}
		}
	}
	
	
	/**
	 * Преди запис на документ
	 */
	public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
	{
		$rec->classId = ($rec->classId) ? $rec->classId : $mvc->getClassId();
	}
	

	/**
	 * След подготовка на тулбара на единичен изглед.
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $data
	 */
	protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
	{
		if(core_Packs::isInstalled('label')){
			if (($data->rec->state != 'rejected' && $data->rec->state != 'draft') && label_Labels::haveRightFor('add')){
				
				$tQuery = label_Templates::getQuery();
				$tQuery->where("#classId = '{$mvc->getClassId()}' AND #state != 'rejected'");
				$tQuery->show('id');
				$tQuery->limit(1);
				$error = ($tQuery->fetch()) ? '' : ",error=Няма наличен шаблон за етикети от задачи за производство";
				
				core_Request::setProtected('class,objectId');
				$url = array('label_Labels', 'selectTemplate', 'class' => $mvc->className, 'objectId' => $data->rec->id, 'ret_url' => TRUE);
				$data->toolbar->addBtn('Етикетиране', toUrl($url), NULL, "target=_blank,ef_icon = img/16/price_tag_label.png,title=Разпечатване на етикети от задачата за производство{$error}");
				core_Request::removeProtected('class,objectId');
			}
		}
	}
	
	
	/**
	 * Генерира баркод изображение от даден сериен номер
	 * 
	 * @param string $serial - сериен номер
	 * @return core_ET $img - баркода
	 */
	public static function getBarcodeImg($serial)
	{
		$attr = array();
		
		$conf = core_Packs::getConfig('planning');
		$barcodeType = $conf->PLANNING_TASK_LABEL_COUNTER_BARCODE_TYPE;
		$size = array('width' => $conf->PLANNING_TASK_LABEL_WIDTH, 'height' => $conf->PLANNING_TASK_LABEL_HEIGHT);
		$attr['ratio'] = $conf->PLANNING_TASK_LABEL_RATIO;
		if ($conf->PLANNING_TASK_LABEL_ROTATION == 'yes') {
			$attr['angle'] = 90;
		}
		
		if ($conf->PLANNING_TASK_LABEL_COUNTER_SHOWING == 'barcodeAndStr') {
			$attr['addText'] = array();
		}
		
		// Генериране на баркод от серийния номер, според зададените параметри
		$img = barcode_Generator::getLink($barcodeType, $serial, $size, $attr);
		
		return $img;
	}
	
	
	public static function getTaskInfo($id)
	{
		$rec = static::fetchRec($id);
		$Driver = static::getDriver($rec);
		$info = $Driver->getProductDriverInfo($id);
		
		return $info;
	}
	
	
	/**
	 * Връща масив с плейсхолдърите, които ще се попълват от getLabelData
	 *
	 * @param mixed $id - ид или запис
	 * @return array $fields - полета за етикети
	 */
	public function getLabelPlaceholders($id)
	{
		expect($rec = planning_Tasks::fetchRec($id));
		$tInfo = planning_Tasks::getTaskInfo($rec);
		$fields = array('JOB', 'NAME', 'BARCODE', 'MEASURE_ID', 'QUANTITY', 'ИЗГЛЕД', 'PREVIEW');
		
		// Извличане на всички параметри на артикула
		$params = cat_Products::getParams($tInfo->productId, NULL, TRUE);
		$params = array_keys(cat_Params::getParamNameArr($params, TRUE));
		$fields = array_merge($fields, $params);
		
		return $fields;
	}
	
	
	/**
	 * Връща данни за етикети
	 * 
	 * @param int $id - ид на задача
	 * @param number $labelNo - номер на етикета
	 * 
	 * @return array $res - данни за етикетите
     * 
     * @see label_SequenceIntf
	 */
	public function getLabelData($id, $labelNo = 0)
	{
		$res = array();
		expect($rec = planning_Tasks::fetchRec($id));
		expect($origin = doc_Containers::getDocument($rec->originId));
		$jobRec = $origin->fetch();
		$tInfo = planning_Tasks::getTaskInfo($rec);
		
		// Информация за артикула и заданието
		$res['JOB'] = "#" . $origin->getHandle();
		$res['NAME'] = cat_Products::getTitleById($tInfo->productId);
		
		// Генериране на баркод
		$serial = planning_TaskSerials::force($id, $labelNo, $tInfo->productId);
		$res['BARCODE'] = self::getBarcodeImg($serial)->getContent();
		
		// Информация за артикула
		$measureId = cat_Products::fetchField($tInfo->productId, 'measureId');
		$res['MEASURE_ID'] = cat_UoM::getShortName($measureId);
		$res['QUANTITY'] = cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($tInfo->quantityInPack);
		if(isset($jobRec->saleId)){
			$res['ORDER'] = sales_Sales::getLink($jobRec->saleId, 0);
		}
		
		// Извличане на всички параметри на артикула
		Mode::push('text', 'plain');
		$params = cat_Products::getParams($tInfo->productId, NULL, TRUE);
		Mode::pop('text');
		
		$params = cat_Params::getParamNameArr($params, TRUE);
		$res = array_merge($res, $params);
		
		// Генериране на превю на артикула за етикети
		$previewWidth = planning_Setup::get('TASK_LABEL_PREVIEW_WIDTH');
		$previewHeight = planning_Setup::get('TASK_LABEL_PREVIEW_HEIGHT');
		$preview = cat_Products::getPreview($tInfo->productId, array($previewWidth, $previewHeight));
		if(!empty($preview)){
			$res['ИЗГЛЕД'] = $preview;
			$res['PREVIEW'] = $preview;
		}
		
		// Връщане на масива, нужен за отпечатването на един етикет
		return $res;
	}
    
    
    /**
     * Броя на етикетите, които могат да се отпечатат
     * 
     * @param integer $id
     * @param string $allowSkip
     * 
     * @return integer
     * 
     * @see label_SequenceIntf
     */
    public function getEstimateCnt($id, &$allowSkip)
    {
        $allowSkip = TRUE;
        
        return 100 + $id;
    }
}
