<?php



/**
 * Движения на партиди
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class batch_Movements extends core_Detail {
    
	
    /**
     * Заглавие
     */
    public $title = 'Движения на партида';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_AlignDecimals2,batch_Wrapper, plg_RowNumbering, plg_Sorting, plg_Created';
    
    
    /**
     * Кои полета да се показват в листовия изглед
     */
    public $listFields = 'quantity, operation, date, document=Документ,createdOn=Създаване';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Движение на партида";
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'powerUser';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Ключ към мастъра
     */
    public $masterKey = 'itemId';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('itemId', 'key(mvc=batch_Items)', 'input=hidden,mandatory,caption=Партида');
    	$this->FLD('operation', 'enum(in=Влиза, out=Излиза, stay=Стои)', 'mandatory,caption=Операция');
    	$this->FLD('quantity', 'double', 'input=hidden,mandatory,caption=Количество');
    	$this->FLD('docType', 'class(interface=doc_DocumentIntf)', 'caption=Документ вид');
    	$this->FLD('docId', 'int', 'caption=Документ номер');
    	$this->FLD('date', 'date', 'caption=Дата');
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
    	$row->document = cls::get($rec->docType)->getLink($rec->docId, 0);
    	
    	if(isset($rec->productId)){
    		$row->productId = cat_Products::getHyperlink($rec->productId, TRUE);
    	
    		$Definition = batch_Defs::getBatchDef($rec->productId);
    		$row->batch = $Definition->toVerbal($rec->batch);
    	}
    	
    	if(isset($rec->storeId)){
    		$row->storeId = store_Stores::getHyperlink($rec->storeId, TRUE);
    	}
    	
    	$row->operation = "<span style='float:center'>{$row->operation}</span>";
    	switch($rec->operation){
    		case 'in':
    			$row->ROW_ATTR['style'] = 'background-color:rgba(0, 255, 0, 0.1)';
    			break;
    		case 'out':
    			$row->ROW_ATTR['style'] = 'background-color:rgba(255, 0, 0, 0.1)';
    			break;
    		case 'stay':
    			$row->ROW_ATTR['style'] = 'background-color:rgba(0, 0, 255, 0.1)';
    			break;
    	}
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
    	if(isset($data->masterMvc) && $data->masterMvc instanceof batch_Items) return;
    	$data->listFilter->layout = new ET(tr('|*' . getFileContent('acc/plg/tpl/FilterForm.shtml')));
    	
    	$data->listFilter->FLD('batch', 'varchar(128)', 'caption=Партида,silent');
    	$data->listFilter->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад');
    	//$data->listFilter->FLD('Protected', 'varchar(128)', 'input=hidden,silent');
    	
    	$data->listFilter->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
    	$data->listFilter->setOptions('productId', array('' => '') + batch_Items::getProductsWithDefs());
    	$data->listFilter->FNC('action', 'enum(all=Всички,in=Влиза, out=Излиза, stay=Стои)', 'caption=Операция,input');
    	$data->listFilter->FLD('from', 'date', 'caption=От,silent');
    	$data->listFilter->FLD('to', 'date', 'caption=До,silent');
    	
    	$showFields = arr::make('batch,productId,storeId,action,from,to', TRUE);
    	
    	if(haveRole('batch,ceo')){
    		$data->listFilter->showFields = 'batch,productId,storeId,action,from,to';
    	} else {
    		if(Request::get('batch', 'varchar')){
    			$data->listFilter->setField('batch', 'input=hidden');
    		}
    		
    		if(Request::get('productId', 'varchar')){
    			$data->listFilter->setField('productId', 'input=hidden');
    		} else {
    			unset($showFields['productId']);
    		}
    		
    		$data->listFilter->showFields = implode(',', $showFields);
    	}
    	
    	Request::setProtected('batch');
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
    	$data->listFilter->input(NULL, 'silent');
    	$data->listFilter->input();
    	
    	$data->query->EXT('productId', 'batch_Items', 'externalName=productId,externalKey=itemId');
    	$data->query->EXT('storeId', 'batch_Items', 'externalName=storeId,externalKey=itemId');
    	$data->query->EXT('batch', 'batch_Items', 'externalName=batch,externalKey=itemId');
    	
    	$fields = array('RowNumb' => '№', 'batch' => 'Партида', 'productId' => 'Артикул', 'storeId' => 'Склад');
    	$data->listFields = $fields + $data->listFields;
    	
    	if($fRec = $data->listFilter->rec){
    		if(isset($fRec->productId)){
    			$data->query->where("#productId = {$fRec->productId}");
    			unset($data->listFields['productId']);
    		}
    		
    		if(isset($fRec->storeId)){
    			$data->query->where("#storeId = {$fRec->storeId}");
    			unset($data->listFields['storeId']);
    		}
    		
    		if(isset($fRec->batch)){
    			$data->query->like('batch', $fRec->batch);
    		}
    		
    		if(isset($fRec->action) && $fRec->action != 'all'){
    			$data->query->where("#operation = '{$fRec->action}'");
    		}
    		
    		if(isset($fRec->from)){
    			$data->query->where("#date >= '{$fRec->from}'");
    		}
    		
    		if(isset($fRec->to)){
    			$data->query->where("#date <= '{$fRec->to}'");
    		}
    	}
    }
    
    
    /**
     * Записва движение на партида от документ
     * 
     * @param mixed $class - ид на документ
     * @param mixed $rec   - ид или запис на документа
     * @return boolean     - успех или не
     */
    public static function saveMovement($class, $rec)
    {
    	$mvc = cls::get($class);
    	
    	// Взимаме класа имплементиращ интерфейса за партидни движения
    	expect($MovementImpl = cls::getInterface('batch_MovementSourceIntf', $mvc));
		expect($docRec = $mvc->fetchRec($rec));
		
		try{
			// Взимаме движенията от документа
			$entries = $MovementImpl->getMovements($docRec);
			
			expect(is_array($entries), 'Класа не върна движения');
			
			// Проверяваме записите
			foreach ($entries as $entry){
				expect(cat_Products::fetchField($entry->productId, 'id'), 'Няма артикул');
				expect($entry->batch, 'Няма номер на партида');
				expect(store_Stores::fetchField($entry->storeId, 'id'), 'Няма склад');
				expect(isset($entry->quantity), 'Няма количество');
				expect(in_array($entry->operation, array('in', 'out', 'stay')), 'Невалидна операция');
			}
		} catch(core_exception_Expect $e){
			
			// Ако има проблем, показваме грешката
			bp($e->getMessage(), $e->getDump(), $entries);
		}
		
		$result = TRUE;
		
		if(!count($entries)) return $result;
		
		// За всяко движение
		foreach ($entries as $entry2){
			try{
				// Форсираме партидата
				$itemId = batch_Items::forceItem($entry2->productId, $entry2->batch, $entry2->storeId);
				
				// Ако има проблем с форсирането сетваме грешка
				if(!$itemId) {
					$result = FALSE;
					break;
				}	
				
				// Движението, което ще запишем
				$mRec = (object)array('itemId'    => $itemId,
									  'quantity'  => $entry2->quantity,
									  'operation' => $entry2->operation,
									  'docType'   => $mvc->getClassId(),
									  'docId'     => $docRec->id,
									  'date'	  => $entry2->date,
				);
					
				// Запис на движението
				$id = self::save($mRec);
				
				// Ако има проблем със записа, сетваме грешка
				if(!$id){
					$result = FALSE;
					break;
				}
			} catch(core_exception_Expect $e){
				
				// Ако е изникнала грешка
				$result = FALSE;
			}
		}
		
		// При грешка изтриваме всички записи до сега
		if($result === FALSE){
			self::removeMovement($class, $rec);
		}
		
		// Връщаме резултата
		return $result;
    }
    
    
    /**
     * Изтрива записите породени от документа
     * 
     * @param mixed $class - ид на документ
     * @param mixed $rec   - ид или запис на документа
     * @return void        - изтрива движенията породени от документа
     */
    public static function removeMovement($class, $rec)
    {
    	$Class = cls::get($class);
    	$docClassId = $Class->getClassId();
    	$docId = $Class->fetchRec($rec)->id;
    	
    	// Изтриваме записите
    	static::delete("#docType = {$docClassId} AND #docId = {$docId}");
    }
    
    
    /**
     * Изпълнява се след подготовката на листовия изглед
     */
    protected static function on_AfterPrepareListTitle($mvc, &$res, $data)
    {
    	$data->title = 'Движения на партида|*';
    	$titles = array();
    	
    	if($fRec = $data->listFilter->rec){
    		if(isset($fRec->productId)){
    			$titles[] = "<b style='color:green'>" . cat_Products::getTitleById($fRec->productId) . "</b>";
    		}
    		
    		if($fRec->batch){
    			$titles[] = "<b style='color:green'>" . cls::get('type_Varchar')->toVerbal($fRec->batch) . "</b>";
    		}
    		
    		if(isset($fRec->storeId)){
    			$titles[] = "<b style='color:green'>" . store_Stores::getTitleById($fRec->storeId) . "</b>";
    		}
    	}
    	
    	if(count($titles)){
    		$data->title .= " " . implode(' <b>,</b> ', $titles);
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'list'){
    		
    		// Ако потребителя няма определените роли, позволяваме достъп само през защитено урл
    		if(!core_Users::haveRole('ceo,batch', $userId)){
    			
    			// Само през защитено урл имаме достъп
    			if(!Request::get('Protected')){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    }
}