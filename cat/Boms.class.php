<?php


/**
 * Мениджър за технологични карти (Рецепти)
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_Boms extends core_Master
{
   
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'techno2_Boms';
	
	
   /**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Технологични рецепти";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    var $loadList = 'plg_RowTools, cat_Wrapper, plg_Sorting, doc_DocumentPlg, plg_Printing, acc_plg_DocumentSummary, doc_ActivatePlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "tools=Пулт,originId=Артикул,createdOn,createdBy,modifiedOn,modifiedBy";
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'cat_BomDetails';
    
    
    /**
     * Заглавие на единичен документ
     */
    var $singleTitle = 'Технологична рецепта';
    
    
    /**
     * Икона на единичния изглед
     */
    var $singleIcon = 'img/16/legend.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Bom";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'cat,ceo';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'cat,ceo';
    
    
    /**
     * Кой може да го контира?
     */
    var $canConto = 'cat,ceo';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'cat,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo,cat';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    var $canSingle = 'ceo,cat';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'cat/tpl/SingleLayoutBom.shtml';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('notes', 'richtext(rows=4)', 'caption=Забележки');
    	$this->FLD('state','enum(draft=Чернова, active=Активиран, rejected=Оттеглен)', 'caption=Статус, input=none');
    	$this->FLD('quantity', 'double(smartRound,Min=0)', 'caption=За');
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
    	
    	$originInfo = doc_Containers::getDocument($form->rec->originId)->getProductInfo();
    	$shortUom = cat_UoM::getShortName($originInfo->productRec->measureId);
    	$form->setField('quantity', "unit={$shortUom}");
    	$form->setDefault('quantity', 1);
    }
    
    
    /**
     * Подготовка на бутоните на формата за добавяне/редактиране
     */
    public static function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
    	// Документа не може да се създава  в нова нишка, ако е възоснова на друг
    	if(!empty($data->form->toolbar->buttons['btnNewThread'])){
    		$data->form->toolbar->removeBtn('btnNewThread');
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'write' || $action == 'add') && isset($rec)){
    		
    		// Може да се добавя само ако има ориджин
    		if(empty($rec->originId)){
    			$res = 'no_one';
    		} else {
    			$origin = doc_Containers::getDocument($rec->originId);
    			if(!$origin->haveInterface('cat_ProductAccRegIntf')){
    				$res = 'no_one';
    			}
    			
    			// Трябва да е активиран
    			if($origin->fetchField('state') != 'active'){
    				$res = 'no_one';
    			}
    		}
    	}
    	
    	if(($action == 'activate' || $action == 'restore' || $action == 'conto' || $action == 'write') && isset($rec->originId) && $res != 'no_one'){
    		
    		// Ако има активна карта, да не може друга да се възстановява,контира,създава или активира
    		if($mvc->fetch("#originId = {$rec->originId} AND #state = 'active'")){
    			$res = 'no_one';
    		}
    	}
    	
    	// Ако няма ид, не може да се активира
    	if($action == 'activate' && empty($rec->id)){
    		$res = 'no_one';
    	}
    	
    	// Не може да се активира, ако няма избрани ресурси
    	if($action == 'activate' && isset($rec->id)){
    		if(!count(cat_BomDetails::fetchField("#bomId = {$rec->id}"))){
    			$res = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
    public static function canAddToThread($threadId)
    {
    	// Ако има ориджин в рекуеста
    	if($originId = Request::get('originId', 'int')){
    		
    		$origin = doc_Containers::getDocument($originId);
    		expect($origin->haveInterface('cat_ProductAccRegIntf'));
    		expect($origin->fetchField('state') == 'active');
    		
    		// Ако е спецификация, документа може да се добави към нишката
    		return TRUE;
    	}
    	
    	return FALSE;
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
    	
    	$row = new stdClass();
    	$row->title = $this->getRecTitle($rec);
    	$row->authorId = $rec->createdBy;
    	$row->author = $this->getVerbal($rec, 'createdBy');
    	$row->state = $rec->state;
    	$row->recTitle = $rec->title;
    	
    	return $row;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$self = cls::get(__CLASS__);
    
    	return "{$self->singleTitle} №{$rec->id}";
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$origin = doc_Containers::getDocument($rec->originId);
    	$row->originId = $origin->getHyperLink(TRUE);
    	
    	if($row->quantity){
    		$measureId = doc_Containers::getDocument($rec->originId)->getProductInfo()->productRec->measureId;
    		$row->quantity .= " " . cat_UoM::getShortName($measureId);
    	}
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    public static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(!empty($data->toolbar->buttons['btnAdd'])){
    		$data->toolbar->removeBtn('btnAdd');
    	}
    }
    
    
    /**
     * Връща сумата на спецификацията според подадения ориджин
     * 
     * @param int $containerId - ид на контейнера, който е генерирал картата
     * @return stdClass $total - обект съдържащ сумарната пропорционална и начална цена
     * 		 o $total->base - началната сума (в основната валута за периода)
     * 		 o $total->prop - пропорционалната сума (в основната валута за периода)
     */
    public static function getTotalByOrigin($containerId)
    {
    	// Намираме активната карта за обекта
    	$rec = self::fetch("#originId = {$containerId} AND #state = 'active'");
    	
    	// Ако няма, връщаме нулеви цени
    	if(empty($rec)) return FALSE;
    	
    	// Кои ресурси участват в спецификацията
    	$rInfo = static::getResourceInfo($rec);
    	
    	$amounts = (object)array('base' => 0, 'prop' => 0);
    	
    	// За всеки ресурс
    	if(count($rInfo)){
    		foreach ($rInfo as $dRec){
    			$selfValue = mp_Resources::fetchField($dRec->resourceId, 'selfValue');
    			
    			// Добавяме към началната сума и пропорционалната
    			$amounts->base += $dRec->baseQuantity * $selfValue;
    			$amounts->prop += $dRec->propQuantity * $selfValue;
    		}
    	}
    	
    	// Връщаме изчислените суми
    	return $amounts;
    }
    
    
    /**
     * Връща информация с ресурсите използвани в технологичната рецепта
     *
     * @param mixed $id - ид или запис
     * @return array $res - масив с записи на участващите ресурси
     * 			o $res->resourceId       - ид на ресурса
     * 			o $res->activityCenterId - ид на центъра на дейност от производствения етап
     * 			o $res->baseQuantity     - начално количество на ресурса
     * 			o $res->propQuantity     - пропорционално количество на ресурса
     */
    public static function getResourceInfo($id)
    {
    	$resources = array();
    	
    	//@TODO временно докато се изясни
    	return $resources;
    	
    	
    	
    	expect($rec = static::fetchRec($id));
    	
    	// Намираме всички етапи в рецептата
    	$dQuery = cat_BomStages::getQuery();
    	$dQuery->where("#bomId = {$rec->id}");
    	
    	// За всеки етап
    	while($dRec = $dQuery->fetch()){
    		
    		// Проверяваме имали вързани ресурси към него
    		$sQuery = cat_BomStageDetails::getQuery();
    		$sQuery->where("#bomstageId = {$dRec->id}");
    		while($sRec = $sQuery->fetch()){
    			$arr = array();
    			$arr['resourceId'] = $sRec->resourceId;
    			if(isset($dRec->stage)){
    				$arr['activityCenterId'] = mp_Stages::fetchField($dRec->stage, 'departmentId');
    			}
    			
    			$arr['baseQuantity'] = $sRec->baseQuantity;
    			$arr['propQuantity'] = $sRec->propQuantity;
    			
    			$resources[] = (object)$arr;
    		}
    	}
    	
    	// Връщаме намерените ресурси
    	return $resources;
    }
    
    
    /**
     * Рекурсивно обхождаме дървото на рецептата и търсим дали
     * тя съдържа някъде определен ресурс, ако да то добавяме
     * всички ресурси които са част от дървото към масив.
     * 
     * @param int $resourceId - ид на ресурса
     * @param array $notAllowed - масив където се добавят
     * забранените ресурси
     * @param int $needle - ресурс, който търсим
     * @param array $path - пътя до ресурса в дървото
     */
    private static function traverseTree($resourceId, $needle, &$notAllowed, $path = array())
    {
    	// Добавяме текущия продукт
    	$path[] = $resourceId;
    	
    	// Ако стигнем до началния, прекратяваме рекурсията
    	if($resourceId == $needle){
    		foreach($path as $p){
    			 
    			// За всеки продукт в пътя до намерения ние го
    			// добавяме в масива notAllowed, ако той, вече не е там
    			if(!array_key_exists($p, $path)){
    				$notAllowed[$p] = $p;
    			}
    		}
    		return;
    	}
    	
    	// Взимаме вложените ресурси в етапа
    	$query = cat_BomStageDetails::getQuery();
    	$stageRec = cat_BomStages::fetch("#resourceId = {$resourceId}");
    	
    	$query->where("#bomstageId = {$stageRec->id} AND #type = 'input'");
    	
    	// За всеки
    	while($rec = $query->fetch()){
    		
    		// Ако някой от вложимите е изходен за друг етап от рецептата
    		if($sRec = cat_BomStages::fetch("#bomId = {$stageRec->bomId} AND #resourceId = {$rec->resourceId}")){
    			
    			// Извикваме рекурсивно
    			self::traverseTree($sRec->resourceId, $needle, $notAllowed, $path);
    		}
    	}
    }
}