<?php

/**
 * Кеш на изгледа на частните артикули
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_ProductTplCache extends core_Master
{
	
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'techno2_SpecTplCache';
	
	
	/**
	 * Необходими плъгини
	 */
	public $loadList = 'plg_RowTools, cat_Wrapper';
	 
	
	/**
	 * Заглавие на мениджъра
	 */
	public $title = "Кеш на изгледа на артикулите";
	
	
	/**
	 * Права за писане
	 */
	public $canWrite = 'no_one';
	
	
	/**
	 * Права за запис
	 */
	public $canRead = 'ceo, cat';
	
	
	/**
	 * Права за запис
	 */
	public $canDelete = 'ceo, cat';
	
	
	/**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, cat';
	
	
	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo, cat';
	
	
	/**
	 * Полета, които ще се показват в листов изглед
	 */
	public $listFields = 'id, productId, time, type, documentType';
	
	
	/**
	 * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
	 */
	public $rowToolsSingleField = 'productId';
	
	
	/**
	 * Файл с шаблон за единичен изглед на статия
	 */
	public $singleLayoutFile = 'cat/tpl/SingleLayoutTplCache.shtml';
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD("productId", "key(mvc=cat_Products,select=name)", "input=none,caption=Артикул");
		$this->FLD("type", "enum(title=Заглавие,description=Описание)", "input=none,caption=Тип");
		$this->FLD("documentType", "enum(public=Външни документи,internal=Вътрешни документи)", "input=none,caption=Документ тип");
		
		$this->FLD("cache", "blob(1000000, serialize, compress)", "input=none,caption=Html,column=none");
		$this->FLD("time", "datetime", "input=none,caption=Дата");
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид.
	 */
	public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		if(isset($fields['-single'])){
			if($rec->type == 'description'){
				$Driver = cls::get('cat_Products')->getDriver($rec->productId);
				$row->cache = $Driver->renderProductDescription($rec->cache);
				
				$componentTpl = cat_Products::renderComponents($rec->cache->components);
				$row->cache->append($componentTpl, 'COMPONENTS');
				
			} else {
				$row->cache = cls::get('type_Varchar')->toVerbal($rec->cache);
			}
		}
	}


	/**
	 * Подготовка на филтър формата
	 */
	public static function on_AfterPrepareListFilter($mvc, &$data)
	{
		$data->listFilter->FLD("docId", "key(mvc=cat_Products,select=name,allowEmpty)", "input,caption=Артикул");
		$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
		$data->listFilter->view = 'horizontal';
		$data->listFilter->showFields = 'docId';
		
		$data->listFilter->input(NULL, 'silent');
		
		if(isset($data->listFilter->rec->docId)){
			$data->query->where("#productId = '{$data->listFilter->rec->docId}'");
		}
	}
	
	
	/**
	 * След подготовка на туклбара на списъчния изглед
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareListToolbar($mvc, &$data)
	{
		if(haveRole('admin,debug,ceo')){
			$data->toolbar->addBtn('Изчистване', array($mvc, 'truncate'), 'warning=Искатели да изчистите таблицата,ef_icon=img/16/sport_shuttlecock.png');
		}
	}
	
	
	/**
	 * Изчиства записите в балансите
	 */
	public function act_Truncate()
	{
		requireRole('admin,debug,ceo');
		 
		// Изчистваме записите от моделите
		self::truncate();
		 
		// Записваме, че потребителя е разглеждал този списък
		$this->logWrite("Изтриване на кеша на изгледите на артикула");
		
		Redirect(array($this, 'list'), FALSE, 'Записите са изчистени успешно');
	}
	
	
	/**
	 * Връща кешираните данни на артикула за дадено време ако има
	 * 
	 * @param int $productId - ид на артикул
	 * @param datetime $time - време
	 * @return mixed
	 */
	public static function getCache($productId, $time, $type, $documentType)
	{
		// Кога артикула е бил последно модифициран
		$productModifiedOn = cat_Products::fetchField($productId, 'modifiedOn');
		
		// Намираме кешираните данни
		$res = array($productModifiedOn => NULL);
		$query = self::getQuery();
		$query->where("#productId = {$productId} AND #type = '{$type}' AND #documentType = '{$documentType}' AND #time <= '{$time}'");
		$query->orderBy('time', 'DESC');
		while($rec = $query->fetch()){
			$res[$rec->time] = $rec->cache;
		}
		
		// За всяко от времената на модификация на артикула за които има кеш + последното модифициране
		// намираме това което е най-близо до датата за която проверяваме, връщаме намерения кеш, ако
		// върнатата дата е последната модификация на артикула за която няма кеш връща се NULL, което ще
		// доведе до кеширане на изгледа
		foreach ($res as $cTime => $cache){
			if($cTime <= $time) return $cache;
		}
	}
	
	
	/**
	 * Кешира заглавието на артикула
	 *
	 * @param int $productId
	 * @param datetime $time
	 * @param enum(internal,public) $documentType
	 * @return string - заглавието на артикула
	 */
	public static function cacheTitle($rec, $time, $documentType)
	{
		$rec = cat_Products::fetchRec($rec);
		
		$cacheRec = new stdClass();
		
		// Ако няма кеш досега записваме го с датата за която проверяваме за да се върне винаги
		if(!self::count(("#productId = {$rec->id} AND #type = 'title' AND #documentType = '{$documentType}' AND #time <= '{$time}'"))){
			$cacheRec->time = $time;
		} else {
		
			// Ако записваме нов кеш той е с датата на модифициране на артикула
			$cacheRec->time = $rec->modifiedOn;
		}
		
		$cacheRec->productId = $rec->id;
		$cacheRec->type = 'title';
		$cacheRec->documentType = $documentType;
		$cacheRec->cache = cat_Products::getTitleById($rec->id);
		
		if(isset($time)){
			self::save($cacheRec);
		}
		
		return $cacheRec->cache;
	}
	
	
	/**
	 * Кешира описанието на артикула
	 *
	 * @param int $productId
	 * @param datetime $time
	 * @param enum(public,internal) $documentType
	 * @return core_ET
	 */
	public static function cacheDescription($productId, $time, $documentType)
	{
		$pRec = cat_Products::fetchRec($productId);
		
		$data = cat_Products::prepareDescription($pRec->id, $documentType);
		
		$data->components = array();
		cat_Products::prepareComponents($pRec->id, $data->components, $documentType);
		
		$cacheRec = new stdClass();
		
		// Ако няма кеш досега записваме го с датата за която проверяваме за да се върне винаги
		if(!self::count(("#productId = {$pRec->id} AND #type = 'description' AND #documentType = '{$documentType}' AND #time <= '{$time}'"))){
			$cacheRec->time = $time;
		} else {
		
			// Ако записваме нов кеш той е с датата на модифициране на артикула
			$cacheRec->time = $pRec->modifiedOn;
		}
		
		$cacheRec->productId = $pRec->id;
		$cacheRec->type = 'description';
		$cacheRec->documentType = $documentType;
		$cacheRec->cache = $data;
		
		if(isset($time)){
			self::save($cacheRec);
		}
			
		return $cacheRec->cache;
	}
}