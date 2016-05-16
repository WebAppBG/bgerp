<?php


/**
 * Клас 'store_InventoryNotes'
 *
 * Мениджър за документ за инвентаризация на склад
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov<ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_InventoryNotes extends core_Master
{
    
    
	/**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=store_transaction_InventoryNote';
    
    
    /**
     * Заглавие
     */
    public $title = 'Протоколи за инвентаризация';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Ivn';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,store';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,store';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,store';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,storeMaster';
    
    
    /**
     * Кой може да създава продажба към отговорника на склада?
     */
    public $canMakesale = 'ceo,sale';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,storeMaster';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Протокол за инвентаризация';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/shipment.png';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.8|Логистика";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, store_Wrapper,acc_plg_Contable,doc_DocumentPlg, plg_Printing, acc_plg_DocumentSummary, plg_Search,bgerp_plg_Blank';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'store_InventoryNoteSummary,store_InventoryNoteDetails';
    
    
    /**
     * Главен детайл на модела
     */
    public $mainDetail = 'store_InventoryNoteSummary';
   
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'store/tpl/InventoryNote/SingleLayout.shtml';
    

    /**
     * Да се забрани ли кеширането на документа
     */
    public $preventCache = TRUE;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'valior,title=Документ,storeId,folderId,createdOn,createdBy,modifiedOn,modifiedBy';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('valior', 'date', 'caption=Вальор, mandatory');
    	$this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад, mandatory');
    	$this->FLD('groups', 'keylist(mvc=cat_Groups,select=name)', 'caption=Маркери');
    	$this->FLD('hideOthers', 'enum(yes=Да,no=Не)', 'caption=Показване само на избраните маркери->Избор, mandatory, notNULL,value=yes,maxRadio=2');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    protected static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'makesale' && isset($rec->id)){
    		if($rec->state != 'active'){
    			$requiredRoles = 'no_one';
    		} else {
    			$responsible = $mvc->getSelectedResponsiblePersons($rec);
    			if(!count($responsible)){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    }
    
    
    /**
     * Намира МОЛ-те на които ще начитаме липсите
     * 
     * @param stdClass $rec
     * @return array $options
     */
    private static function getSelectedResponsiblePersons($rec)
    {
    	$options = array();
    	
    	$dQuery = store_InventoryNoteSummary::getResponsibleRecsQuery($rec->id);
    	$dQuery->show('charge');
    	while($dRec = $dQuery->fetch()){
    		$options[$dRec->charge] = core_Users::getVerbal($dRec->charge, 'nick');
    	}
    	
    	return $options;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$form->setDefault('valior', dt::today());
    	
    	$form->setDefault('storeId', doc_Folders::fetchCoverId($form->rec->folderId));
    	$form->setDefault('hideOthers', 'yes');
    	
    	if(isset($form->rec->id)){
    		$form->setReadOnly('storeId');
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
    		$rec = &$form->rec;
    		if(isset($rec->groups)){
    			$error = FALSE;
    			
    			// Кои са недопустимите маркери
    			$notAllowed = array();
    			$groups = keylist::toArray($rec->groups);
    			
    			foreach ($groups as $grId){
    				
    				// Ако текущия маркер е в недопустимите сетваме грешка
    				if(array_key_exists($grId, $notAllowed)){
    					$error = TRUE;
    					break;
    				}
    				
    				// Иначе добавяме него и наследниците му към недопустимите маркери
    				$descendant = cat_Groups::getDescendantArray($grId);
    				$notAllowed += $descendant;
    			}
    			
    			if($error === TRUE){
    				
    				// Сетваме грешка ако са избрани маркери, които са вложени един в друг
    				$form->setError('groups', 'Избрани са вложени маркери');
    			}
    		}
    	}
    }
    
    
    /**
     * Можели документа да се добави в посочената папка
     * 
     * @param $folderId int ид на папката
     * @return boolean
     */
    public static function canAddToFolder($folderId)
    {
    	$folderClass = doc_Folders::fetchCoverClassName($folderId);
    	
    	return ($folderClass == 'store_Stores') ? TRUE : FALSE;
    }
    
    
    /**
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow($id)
    {
    	expect($rec = $this->fetch($id));
    	$title = $this->getRecTitle($rec);
    
    	$row = (object)array(
    			'title'    => $title,
    			'authorId' => $rec->createdBy,
    			'author'   => $this->getVerbal($rec, 'createdBy'),
    			'state'    => $rec->state,
    			'recTitle' => $title
    	);
    
    	return $row;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$self = cls::get(get_called_class());
    	 
    	return tr("|{$self->singleTitle}|* №") . $rec->id;
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = $data->rec;
    	
    	if($rec->state != 'rejected'){
    		if($mvc->haveRightFor('single', $rec->id)){
    			$url = array($mvc, 'single', $rec->id);
    			$url['Printing'] = 'yes';
    			$url['Blank'] = 'yes';
    			 
    			$data->toolbar->addBtn('Бланка', $url, 'ef_icon = img/16/print_go.png,title=Разпечатване на бланка,target=_blank');
    		}
    	}
    	
    	if($mvc->haveRightFor('makesale', $rec)){
    		$url = array($mvc, 'makeSale', $rec->id, 'ret_url' => TRUE);
    		$data->toolbar->addBtn('Начет', $url, 'ef_icon = img/16/cart_go.png,title=Начисляване на излишъците на МОЛ-а');
    	}
    }
    
    
    /**
     * Екшън създаващ продажба в папката на избран МОЛ
     */
    function act_makeSale()
    {
    	// Проверка за права
    	$this->requireRightFor('makesale');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	$this->requireRightFor('makesale', $rec);
    	
    	// Имали пторебители за начет
    	$options = $this->getSelectedResponsiblePersons($rec);
    	
    	// Подготвяме формата
    	$form = cls::get('core_Form');
    	$form->title = "Избор на МОЛ за начет";
    	$form->FLD('userId', 'key(mvc=core_Users,select=nick)', 'caption=МОЛ,mandatory');
    	
    	$form->setOptions('userId', array('' => '') + $options);
    	if(count($options) == 1){
    		$form->setDefault('userId', key($options));
    	}
    	$form->input();
    	
    	// Ако е събмитната
    	if($form->isSubmitted()){
    		
    		// Кой е избрания потребител?
    		$userId = $form->rec->userId;
    		$personId = crm_Profiles::fetchField($userId, 'personId');
    		
    		// Създаваме продажба в папката му
    		$fields = array('shipmentStoreId' => $rec->storeId, 'valior' => $rec->valior);
    		$saleId = sales_Sales::createNewDraft('crm_Persons', $personId, $fields);
    		
    		// Добавяме редовете, които са за неговото начисляване
    		$dQuery = store_InventoryNoteSummary::getResponsibleRecsQuery($rec->id);
    		$dQuery->where("#charge = {$userId}");
    		while($dRec = $dQuery->fetch()){
    			$quantity = abs($dRec->delta);
    			sales_Sales::addRow($saleId, $dRec->productId, $quantity);
    		}
    		
    		
    		// Редирект при успех
    		redirect(array('sales_Sales', 'single', $saleId));
    	}
    	
    	// Добавяме бутони
    	$form->toolbar->addSbBtn('Продажба', 'save', 'id=save, ef_icon = img/16/cart_go.png', 'title=Създаване на продажба');
    	$form->toolbar->addBtn('Отказ', array('store_InventoryNotes', 'single', $noteId),  'id=cancel, ef_icon = img/16/close16.png', 'title=Прекратяване на действията');
    	
    	// Рендираме формата
    	$tpl = $form->renderHtml();
    	$tpl = $this->renderWrapping($tpl);
    	
    	// Връщаме шаблона
    	return $tpl;
    }
    
    
    /**
     * Преди подготовка на сингъла
     */
    protected static function on_BeforePrepareSingle(core_Mvc $mvc, &$res, $data)
    {
    	if(Request::get('Blank', 'varchar')){
    		Mode::set('blank');
    	}
    }
    
    
    /**
     * След подготовка на сингъла
     */
    protected static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
    	$rec = &$data->rec;
    	$row = &$data->row;
    	
    	$ownCompanyData = crm_Companies::fetchOwnCompany();
    	$row->MyCompany = cls::get('type_Varchar')->toVerbal($ownCompanyData->company);
    	$row->MyCompany = transliterate(tr($row->MyCompany));
    	$row->MyAddress = cls::get('crm_Companies')->getFullAdress($ownCompanyData->companyId, TRUE)->getContent();
 		
    	$toDate = dt::addDays(-1, $rec->valior);
    	$toDate = dt::verbal2mysql($toDate, FALSE);
    	$row->toDate = $mvc->getFieldType('valior')->toVerbal($toDate);
    	
    	if($storeLocationId = store_Stores::fetchField($data->rec->storeId, 'locationId')){
    		$row->storeAddress = crm_Locations::getAddress($storeLocationId);
    	}
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    protected static function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
    	$tpl->push('store/tpl/css/styles.css', 'CSS');
    	if(!Mode::is('printing') && !Mode::is('text', 'xhtml') && !Mode::is('pdf')){
    		$tpl->push('store/js/InventoryNotes.js', 'JS');
    		jquery_Jquery::run($tpl, "noteActions();");
			jqueryui_Ui::enable($tpl);
		}
    }
    
    
    /**
     * Връща артикулите в протокола
     * 
     * @param stdClass $rec - ид или запис
     * @return array $res - масив с артикули
     */
    private function getCurrentProducts($rec)
    {
    	$res = array();
    	$rec = $this->fetchRec($rec);
    	
    	$query = store_InventoryNoteSummary::getQuery();
    	$query->where("#noteId = {$rec->id}");
    	$query->show('noteId,productId,blQuantity,groups,modifiedOn');
    	while($dRec = $query->fetch()){
    		$res[] = $dRec;
    	}
    	
    	return $res;
    }
    
    
    /**
     * Масив с артикулите срещани в счетоводството
     * 
     * @param stClass $rec
     * @return array
     * 		o productId      - ид на артикул
     * 	    o groups         - в кои маркери е
     *  	o blQuantity     - к-во
     *  	o searchKeywords - ключови думи
     *  	o modifiedOn     - текуща дата
     */
    private function getProductsFromBalance($rec)
    {
    	$res = array();
    	$rGroup = keylist::toArray($rec->groups);
    	$Summary = cls::get('store_InventoryNoteSummary');
    	
    	// Търсим артикулите от два месеца назад
    	$from = dt::addMonths(-2, $rec->valior);
    	$from = dt::verbal2mysql($from, FALSE);
    	$to = dt::addDays(-1, $rec->valior);
    	$to = dt::verbal2mysql($to, FALSE);
    	$now = dt::now();
    	
    	// Изчисляваме баланс за подадения период за склада
    	$storeItemId = acc_items::fetchItem('store_Stores', $rec->storeId)->id;
    	$Balance = new acc_ActiveShortBalance(array('from' => $from, 'to' => $to, 'accs' => '321', 'cacheBalance' => FALSE, 'item1' => $storeItemId));
    	$bRecs = $Balance->getBalance('321');
    	
    	$productPositionId = acc_Lists::getPosition('321', 'cat_ProductAccRegIntf');
    	
    	// Подготвяме записите в нормален вид
    	if(is_array($bRecs)){
    		foreach ($bRecs as $bRec){
    			
    			$productId = acc_Items::fetchField($bRec->{"ent{$productPositionId}Id"}, 'objectId');
    			$aRec = (object)array("noteId"     => $rec->id,
    								  "productId"  => $productId,
    								  "groups"     => NULL,
    								  "modifiedOn" => $now,
    								  "blQuantity" => $bRec->blQuantity,);
    			$aRec->searchKeywords = $Summary->getSearchKeywords($aRec);
    			
    			$groups = cat_Products::fetchField($productId, 'groups');
    			if(count($groups)){
    				$groups = cat_Groups::getDescendantArray($groups);
    				$groups = keylist::fromArray($groups);
    				$aRec->groups = $groups;
    			}
    			
    			$add = TRUE;
    			
    			// Ако е указано че искаме само артикулите с тези маркери
    			if($rec->hideOthers == 'yes'){
    				if(!keylist::isIn($rGroup, $aRec->groups)){
    					$add = FALSE;
    				}
    			}
    			
    			if($add === TRUE){
    				$res[] = $aRec;
    			}
    		}
    	}
    	
    	// Връщаме намерените артикули
    	return $res;
    }
    
    
    /**
     * Синхронизиране на множеството на артикулите идващи от баланса
     * и текущите записи.
     * 
     * @param stdClass $rec
     * @return void
     */
    public function sync($id)
    {
    	expect($rec = $this->fetchRec($id));
    	
    	// Дигаме тайм лимита
    	core_App::setTimeLimit(600);
    	
    	// Извличаме артикулите от баланса
    	$balanceArr = $this->getProductsFromBalance($rec);
    	
    	// Извличаме текущите записи
    	$currentArr = $this->getCurrentProducts($rec);
    	 
    	// Синхронизираме двата масива
    	$syncedArr = arr::syncArrays($balanceArr, $currentArr, 'noteId,productId', 'blQuantity,groups,modifiedOn');
    	 
    	$Summary = cls::get('store_InventoryNoteSummary');
    	
    	// Ако има нови артикули, добавяме ги
    	if(count($syncedArr['insert'])){
    		$Summary->saveArray($syncedArr['insert']);
    	}
    	 
    	// На останалите им обновяваме определени полета
    	if(count($syncedArr['update'])){
    		$Summary->saveArray($syncedArr['update'], 'id,noteId,productId,blQuantity,groups,modifiedOn,searchKeywords');
    	}
    	 
    	$deleted = 0;
    	
    	// Ако трябва да се трият артикули
    	if(count($syncedArr['delete'])){
    		foreach ($syncedArr['delete'] as $deleteId){
    			
    			// Трием само тези, които нямат въведено количество
    			$quantity = store_InventoryNoteSummary::fetchField($deleteId, 'quantity');
    			if(!isset($quantity)){
    				$deleted++;
    				store_InventoryNoteSummary::delete($deleteId);
    			}
    		}
    	}
    	 
    	// Дебъг информация
    	if(haveRole('debug')){
    		core_Statuses::newStatus("Данните са синхронизирани");
    		if($deleted){
    			core_Statuses::newStatus("Изтрити са {$deleted} реда");
    		}
    	
    		if($added = count($syncedArr['insert'])){
    			core_Statuses::newStatus("Добавени са {$added} реда");
    		}
    	}
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int $id първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	// Синхронизираме данните само в чернова
    	if($rec->state == 'draft'){
    		$mvc->sync($rec);
    	}
    	
    	static::invalidateCache($rec);
    }
    
    
    /**
     * Инвалидиране на кеша на документа
     * 
     * @param mixed $rec – ид или запис
     * @return void 
     */
    public static function invalidateCache($rec)
    {
    	$rec = static::fetchRec($rec);
    	$key = self::getCacheKey($rec);
    	
    	core_Cache::remove('store_InventoryNotes', $key);
    }
    
    
    /**
     * Връща ключа за кеширане на данните
     * 
     * @param stdClass $rec - запис
     * @return string $key  - уникален ключ
     */
    public static function getCacheKey($rec)
    {
    	// Подготвяме ключа за кеширане
    	$cu = core_Users::getCurrent();
    	$lg = core_Lg::getCurrent();
    	$isNarrow = (Mode::is('screenMode', 'narrow')) ? TRUE : FALSE;
    	$key = "ip{$cu}|{$lg}|{$rec->id}|{$isNarrow}|";
    	
    	// Връщаме готовия ключ
    	return $key;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     * @param array $fields
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if(isset($fields['-list'])){
    		$row->storeId = store_Stores::getHyperlink($rec->storeId, TRUE);
    		$row->title = $mvc->getLink($rec->id, 0);
    	}
    }
    
    
    /**
     * Документа не може да се активира ако има детайл с количество 0
     */
    public static function on_AfterCanActivate($mvc, &$res, $rec)
    {
    	$res = TRUE;
    }
}
