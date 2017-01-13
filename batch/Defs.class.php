<?php



/**
 * Задавания на партиди
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class batch_Defs extends core_Manager {
    
	
	/**
     * Заглавие
     */
    public $title = 'Задавания на партиди';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, batch_Wrapper, plg_Modified, plg_Search';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'productId';
    
    
    /**
     * Кои полета да се показват в листовия изглед
     */
    public $listFields = 'productId,templateId=Дефиниция,batchCaption=Кепшън,modifiedOn,modifiedBy';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Задаване на партидa";
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'batch,ceo';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'batch,ceo';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'batch, ceo';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     *
     *  @var string
     */
    public $hideListFieldsIfEmpty = 'batchCaption';
    
    
    /**
     * Работен кеш
     */
    public static $cache = array();
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул,before=driverClass,silent,mandatory');
    	$this->FLD('templateId', 'key(mvc=batch_Templates,select=name, allowEmpty)', 'caption=Дефиниция,mandatory,removeAndRefreshForm=batchCaption,silent');
    	
    	$this->FLD('batchCaption', 'varchar(20)', 'caption=Заглавие,input=none,after=driverClass');
    	$this->setDbUnique('productId');
    }
    
    
    /**
     * Подредба на записите
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
    	$data->listFilter->FLD('type', "class(interface=batch_BatchTypeIntf,select=title,allowEmpty)", 'caption=Тип,silent');
    	$data->listFilter->view = 'horizontal';
    	$data->listFilter->showFields = 'search,type';
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
    	$data->listFilter->input();
    	
    	if($data->listFilter->isSubmitted()){
    		if($type = $data->listFilter->rec->type){
    			$data->query->EXT('driverClass', 'batch_Templates', 'externalName=driverClass,externalKey=templateId');
    			$data->query->where("#driverClass = {$type}");
    		}
    	}
    	
    	// Сортиране на записите по num
    	$data->query->orderBy('id');
    }
  
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	
    	// От всички складируеми артикули, се махат тези които вече имат партидност
    	$storable = cat_Products::getByProperty('canStore');
    	$query = self::getQuery();
    	$query->show('productId');
    	$alreadyWithDefs = arr::extractValuesFromArray($query->fetchAll(), 'productId');
    	$storable = array_diff_key($storable, $alreadyWithDefs);
    	if(isset($form->rec->id)){
    		if(!array_key_exists($form->rec->productId, $storable)){
    			$storable[$form->rec->productId] = cat_Products::getTitleById($form->rec->productId, FALSE);
    		}
    		$form->setReadOnly('templateId');
    	}
    	
    	$form->setOptions('productId', array('' => '') + $storable);
    	if(isset($form->rec->id)){
    		$form->setReadOnly('productId');
    	}
    	
    	if(isset($form->rec->productId)){
    		if(batch_Items::fetchField("#productId = {$form->rec->productId}")){
    			$form->setReadOnly('productId');
    		}
    	}
    	
    	// Ако е избрана дефиниция, полето за заглавие на дефиницията се показва
    	if(isset($form->rec->templateId)){
    		$form->setField('batchCaption', 'input');
    		
    		$Class = cls::get(batch_Templates::fetchField($form->rec->templateId, 'driverClass'));
    		if(isset($Class->fieldCaption)){
    			$form->setField('batchCaption', "placeholder={$Class->fieldCaption}");
    		}
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$row->productId = cat_Products::getHyperlink($rec->productId, TRUE);
    	$row->ROW_ATTR['class'] = 'state-active';
    	$row->templateId = batch_Templates::getHyperlink($rec->templateId, TRUE);
    }
    
    
    /**
     * Връща дефиницията на партидата за продукта, ако има
     * 
     * @param int $productId - ид на продукт
     * @return batch_drivers_Proto|FALSE $BatchClass - инстанцията на класа или FALSE ако няма
     */
    public static function getBatchDef($productId)
    {
    	// Имали кеширана стойност
    	if(array_key_exists($productId, self::$cache)) return self::$cache[$productId];
    	self::$cache[$productId] = FALSE;
    	
    	// Намираме записа за артикула
    	$rec = self::fetch("#productId = '{$productId}'");
    	
    	if(isset($rec->templateId)){
    		$template = batch_Templates::fetch($rec->templateId);
    		if(cls::load($template->driverClass, TRUE)){
    			$BatchClass = cls::get($template->driverClass);
    			$template->productId = $productId;
    			$BatchClass->setRec($template);
    		
    			self::$cache[$productId] = $BatchClass;
    		}
    	}
    	
    	return self::$cache[$productId];
    }
    
    
    /**
     * Разбира партидата на масив от партиди
     */
    public static function getBatchArray($productId, $batch)
    {
    	$array = array($batch => $batch);
    	
    	$DefClass = self::getBatchDef($productId);
    	if(is_object($DefClass)){
    		$array = $DefClass->makeArray($batch);
    	}
    	
    	return $array;
    }
    
    
    /**
     * Форсира партидна дефиниция на артикула ако може
     * Партидната дефиниция се намира по следния приоритет:
     * 
     * 1. Ако артикула е базиран на прототип неговата партидна дефиниция
     * 2. Ако артикула е в папка на категория и тя има избрана дефолтна дефиниция
     * 3. От драйвера на артикула, ако върне подходящ клас
     * 4. Ако има дефолтна партида форсира се тя
     * 
     * @param int $productId - ид на артикул
     * @return int|NULL $id - форсирания запис, или NULL ако няма такъв
     */
    public static function force($productId, $defaultDef = NULL)
    {
    	// Трябва да е подаден складируем артикул
    	expect($productRec = cat_Products::fetchRec($productId));
    	expect($productRec->canStore == 'yes');
    	
    	// Ако има съществуваща дефиниция, не създаваме нова
    	if($id = static::fetchField("#productId = {$productRec->id}", 'id')) return $id;
    	
    	// Ако артикула е базиран на прототип, който има партида копираме му я
    	if(isset($productRec->proto)){
    		if($exRec = static::fetch("#productId = {$productRec->proto}")){
    			unset($exRec->id,$exRec->modifiedOn,$exRec->modifiedBy);
    			$exRec->productId = $productRec->id;
    			
    			// Записваме точно копие на дефиницията от прототипа
    			return self::save($exRec);
    		}
    	}
    	
    	// Ако артикула е в папка на категория, с избрана партида връщаме нея
    	$folderClassName = doc_Folders::fetchCoverClassName($productRec->folderId);
    	if($folderClassName == 'cat_Categories'){
    		$folderObjectId = doc_Folders::fetchCoverId($productRec->folderId);
    		if($categoryDefRec = batch_CategoryDefinitions::fetch("#categoryId = {$folderObjectId}")){
    			unset($categoryDefRec->id, $categoryDefRec->categoryId);
    			$categoryDefRec->productId = $productRec->id;
    			
    			// Записваме точно копие на дефиницията от категорията
    			return self::save($categoryDefRec);
    		}
    	}
    	
    	// Ако горните условия не са изпълнени, питаме драйвера дали може да върне дефиниция
    	$Driver = cat_Products::getDriver($productRec);
    	$batchClass = $Driver->getDefaultBatchDef($productRec);
    	if(!empty($batchClass)){
    		$BatchType = cls::get($batchClass);
    		$rec = (object)array('driverClass' => $BatchType->getClassId(), 'productId' => $productRec->id);
    	
    		// Записваме дефолтната партида
    		return self::save($rec);
    	}
    	
    	// Ако има дефолтна партида форсира се
    	if(isset($defaultDef)){
    		expect($Class = cls::get($defaultDef), 'Невалиден клас');
    		expect($Class instanceof batch_definitions_Proto, "Не наследява 'batch_definitions_Proto'");
    		$rec = (object)array('productId' => $productRec->id, 'driverClass' => $Class->getClassId());
    		
    		return self::save($rec);
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'delete' && isset($rec->productId)){
    		if(batch_Items::fetchField("#productId = {$rec->productId}")){
    			$requiredRoles = 'no_one';
    		}
    	}
    	
    	if($action == 'add' && isset($rec->productId)){
    		if(self::fetchField("#productId = {$rec->productId}")){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
}