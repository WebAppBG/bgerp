<?php



/**
 * Мениджър на Задания за производство
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Задания за производство
 */
class planning_Jobs extends core_Master
{
    
    
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'mp_Jobs';
	
	
	/**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Задания за производство';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Задание за производство';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Job';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, doc_DocumentPlg, planning_plg_StateManager, planning_Wrapper, plg_Sorting, acc_plg_DocumentSummary, plg_Search, doc_SharablePlg, change_Plugin';
    
    
    /**
     * Полетата, които могат да се променят с change_Plugin
     */
    public $changableFields = 'dueDate,quantity,notes';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, planning';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, planning';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, planning';
    
    
    /**
     * Кой може да променя състоянието?
     */
    public $canChangestate = 'ceo, planning';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, planning';
	
	
	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo, planning';
    
    
	/**
	 * Полета за търсене
	 */
	public $searchFields = 'folderId, productId, notes, saleId, deliveryPlace, storeId, deliveryTermId';
	
	
	/**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/clipboard_text.png';
    
    
	/**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт,dueDate, title=Документ, productId=За артикул, saleId, quantity, quantityProduced, folderId, state, createdOn, createdBy, modifiedOn,modifiedBy';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Шаблон за единичен изглед
     */
    public $singleLayoutFile = 'planning/tpl/SingleLayoutJob.shtml';
    
    
    /**
     * Поле за дата по което ще филтрираме
     */
    public $filterDateField = 'dueDate';
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'Tasks=tasks_Tasks';
    

    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    protected $hideListFieldsIfEmpty = 'saleId';
    
    
    /**
     * Вербални наименования на състоянията
     */
    private static $actionNames = array('created'  => 'Създаване', 
    								    'active'   => 'Активиране', 
    								    'stopped'  => 'Спиране', 
    								    'closed'   => 'Приключване', 
    									'rejected' => 'Оттегляне',
    									'restore'  => 'Възстановяване',
    								    'wakeup'   => 'Събуждане');
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'silent,mandatory,caption=Артикул');
    	$this->FLD('dueDate', 'date(smartTime)', 'caption=Падеж,mandatory');
    	$this->FLD('quantity', 'double(decimals=2)', 'caption=Планирано,mandatory,silent');
    	$this->FLD('quantityFromTasks', 'double(decimals=2)', 'input=none,caption=Произведено,notNull,value=0');
    	$this->FLD('quantityProduced', 'double(decimals=2)', 'input=none,caption=Заскладено,notNull,value=0');
    	$this->FLD('notes', 'richtext(rows=3)', 'caption=Забележки');
    	$this->FLD('tolerance', 'percent', 'caption=Толеранс,silent');
    	$this->FLD('deliveryTermId', 'key(mvc=cond_DeliveryTerms,select=codeName,allowEmpty)', 'caption=Данни от договора->Условие');
    	$this->FLD('deliveryDate', 'date(smartTime)', 'caption=Данни от договора->Срок');
    	$this->FLD('deliveryPlace', 'key(mvc=crm_Locations,select=title,allowEmpty)', 'caption=Данни от договора->Място');
    	$this->FLD('storeId', 'key(mvc=store_Stores,select=name)', 'caption=Данни от договора->Склад');
    	
    	$this->FLD('weight', 'cat_type_Weight', 'caption=Тегло,input=none');
    	$this->FLD('brutoWeight', 'cat_type_Weight', 'caption=Бруто,input=none');
    	$this->FLD('state',
    			'enum(draft=Чернова, active=Активирано, rejected=Отказано, closed=Приключено, stopped=Спряно, wakeup=Събудено)',
    			'caption=Състояние, input=none'
    	);
    	$this->FLD('saleId', 'key(mvc=sales_Sales)', 'input=hidden,silent,caption=Продажба');
    	
    	$this->FLD('sharedUsers', 'userList(roles=planning|ceo)', 'caption=Споделяне->Потребители,mandatory');
    	$this->FLD('history', 'blob(serialize, compress)', 'caption=Данни,input=none');
    	
    	$this->setDbIndex('productId');
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
    	
    	$form->setReadOnly('productId');
    	$pInfo = cat_Products::getProductInfo($rec->productId);
    	$uomName = cat_UoM::getShortName($pInfo->productRec->measureId);
    	
    	$form->setField('quantity', "unit={$uomName}");
    	$form->setSuggestions('tolerance', array('' => '') + arr::make('5 %,10 %,15 %,20 %,25 %,30 %', TRUE));
    	
		if($tolerance = cat_Products::getParams($rec->productId, 'tolerance')){
    		$form->setDefault('tolerance', $tolerance);
    	}
    	
    	if(isset($rec->saleId)){
    		$saleRec = sales_Sales::fetch($rec->saleId);
    		
    		// Ако има данни от продажба, попълваме ги
    		$form->setDefault('deliveryTermId', $saleRec->deliveryTermId);
    		$form->setDefault('deliveryDate', $saleRec->deliveryTime);
    		$form->setDefault('deliveryPlace', $saleRec->deliveryLocationId);
    		$form->setDefault('storeId', $saleRec->shipmentStoreId);
    		$caption = "|Данни от|* <b>" . sales_Sales::getRecTitle($rec->saleId) . "</b>";
    		
    		$form->setField('deliveryTermId', "caption={$caption}->Условие,changable");
    		$form->setField('deliveryDate', "caption={$caption}->Срок,changable");
    		$form->setField('deliveryPlace', "caption={$caption}->Място,changable");
    		$form->setField('storeId', "caption={$caption}->Склад");
    	} else {
    		
    		// Ако заданието не е към продажба, скриваме полетата от продажбата
    		$form->setField('deliveryTermId', 'input=none');
    		$form->setField('deliveryDate', 'input=none');
    		$form->setField('deliveryPlace', 'input=none');
    		$form->setField('storeId', 'input=none');
    	}
    	
    	// При ново задание, ако текущия потребител има права го добавяме като споделен
    	if(haveRole('planning,ceo') && empty($rec->id)){
    		$form->setDefault('sharedUsers', keylist::addKey($rec->sharedUsers, core_Users::getCurrent()));
    	}
    }
    
    
    /**
     *  Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
    	if(!Request::get('Rejected', 'int')){
    		$data->listFilter->setOptions('state', array('' => '') + arr::make('draft=Чернова, active=Активирано, closed=Приключено, stopped=Спряно, wakeup=Събудено', TRUE));
    		$data->listFilter->setField('state', 'placeholder=Всички');
    		$data->listFilter->showFields .= ',state';
    		$data->listFilter->input();
    		 
    		if($state = $data->listFilter->rec->state){
    			$data->query->where("#state = '{$state}'");
    		}
    	}
    }
    
    
    /**
     * След подготовка на сингъла
     */
    public static function on_AfterRenderSingle($mvc, &$tpl, &$data)
    {
    	$tpl->push('planning/tpl/styles.css', "CSS");
    	
    	// Рендираме историята на действията със заданието
    	if(count($data->row->history)){
    		foreach ($data->row->history as $hRow){
    			$clone = clone $tpl->getBlock('HISTORY_ROW');
    			$clone->placeObject($hRow);
    			$clone->removeBlocks();
    			$clone->append2master();
    		}
    	}
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = &$data->rec;
    	
    	if($rec->state != 'draft' && $rec->state != 'rejected'){
    		if(cat_Boms::haveRightFor('add', (object)array('productId' => $rec->productId, 'type' => 'production', 'originId' => $rec->containerId))){
    			$data->toolbar->addBtn("Рецепта", array('cat_Boms', 'add', 'productId' => $rec->productId, 'originId' => $rec->containerId, 'quantity' => $rec->quantity, 'ret_url' => TRUE, 'type' => 'production'), 'ef_icon = img/16/add.png,title=Създаване на нова работна рецепта');
    		}
    	}

    	// Бутон за добавяне на документ за бързо производство
    	if(planning_DirectProductionNote::haveRightFor('add', (object)array('originId' => $rec->containerId))){
    		 $pUrl = array('planning_DirectProductionNote', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE);
    		 $data->toolbar->addBtn("Производство", $pUrl, 'ef_icon = img/16/page_paste.png,title=Създаване на протокол за бързо производство от заданието');
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
    		
    		// Колко е транспортното тегло
    		if($weight = cls::get('cat_Products')->getWeight($rec->productId)){
    			$rec->brutoWeight = $weight * $rec->quantity;
    		} else {
    			$rec->brutoWeight = NULL;
    		}
    		
    		// Колко е еденичното тегло
    		if($weight = cat_Products::getParams($rec->productId, 'weight')){
    			$rec->weight = $weight * $rec->quantity;
    		} else {
    			$rec->weight = NULL;
    		}
    		
    		if($rec->dueDate < dt::today()){
    			$form->setWarning('dueDate', 'Падежът е в миналото');
    		}
    		
    		// Форсираме заданието в дефолт папката според драйвера
    		$Driver = cat_Products::getDriver($rec->productId);
    		$rec->folderId = doc_UnsortedFolders::forceCoverAndFolder((object)array('name' => $Driver->getJobFolderName()));
    	}
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    public static function on_AfterCreate($mvc, $rec)
    {
    	// Споделяме текущия потребител със нишката на заданието
    	$cu = core_Users::getCurrent();
    	doc_ThreadUsers::addShared($rec->threadId, $rec->containerId, $cu);
    	
    	// Записваме в историята на действията, че кога и от кого е създаден документа
    	self::addToHistory($rec->history, 'created', $rec->createdOn, $rec->createdBy);
    	$mvc->save($rec, 'history');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->title = $mvc->getLink($rec->id, 0);
    	$pInfo = cat_Products::getProductInfo($rec->productId);
    	$shortUom = cat_UoM::getShortName($pInfo->productRec->measureId);
    	
    	$rec->quantityFromTasks = planning_TaskActions::getQuantityForJob($rec->id, 'product');
    	$row->quantityFromTasks = $mvc->getFieldType('quantity')->toVerbal($rec->quantityFromTasks);
    	
    	$row->quantity .= " {$shortUom}";
    	$row->quantityProduced .=  " {$shortUom}";
    	$row->quantityFromTasks .=  " {$shortUom}";
    	$quantityToProduce = $rec->quantity - $rec->quantityProduced;
    	$quantityNotStored = $rec->quantityFromTasks - $rec->quantityProduced;
    	
    	$row->quantityNotStored = $mvc->getFieldType('quantity')->toVerbal($quantityNotStored);
    	$row->quantityNotStored .=  " {$shortUom}";
    	
    	$row->quantityToProduce = $mvc->getFieldType('quantity')->toVerbal($quantityToProduce);
    	$row->quantityToProduce .=  " {$shortUom}";
    	
    	if($fields['-list']){
    		$row->productId = cat_Products::getHyperlink($rec->productId, TRUE);
    	}
    	 
    	if($rec->saleId){
    		$row->saleId = sales_Sales::getlink($rec->saleId, 0);
    	}
    	
    	if(empty($rec->quantityProduced)){
    		$row->quantityProduced = "<b class='quiet'>{$row->quantityProduced}</b>";
    	}
    	
    	if($fields['-single']){
    		
    		if($sBomId = cat_Products::getLastActiveBom($rec->productId, 'sales')->id){
    			$row->sBomId = cat_Boms::getLink($sBomId, 0);
    		}
    		
    		if($pBomId = cat_Products::getLastActiveBom($rec->productId, 'production')->id){
    			$row->pBomId = cat_Boms::getLink($pBomId, 0);
    		}
    		
    		if($rec->storeId){
    			$row->storeId = store_Stores::getHyperLink($rec->storeId, TRUE);
    		}
    		
    		$date = ($rec->state == 'draft') ? NULL : $rec->modifiedOn;
    		$row->origin = cat_Products::getAutoProductDesc($rec->productId, $date, 'detailed', 'internal');
    		
    		if($rec->state == 'stopped' || $rec->state == 'closed') {
    			$tpl = new ET(tr(' от [#user#] на [#date#]'));
    			$row->state .= $tpl->placeArray(array('user' => $row->modifiedBy, 'date' => dt::mysql2Verbal($rec->modifiedOn)));
    		}
    	}
    	
    	if(!Mode::is('text', 'xhtml') && !Mode::is('printing')){
    		$row->dueDate = ht::createLink($row->dueDate, array('cal_Calendar', 'day', 'from' => $row->dueDate, 'Task' => 'true'), NULL, array('ef_icon' => 'img/16/calendar5.png', 'title' => 'Покажи в календара'));
    	}
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$self = cls::get(get_called_class());
    	 
    	return tr($self->singleTitle) . " №{$rec->id}";
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
    	$row->recTitle = $this->getRecTitle($rec);
    	$row->subTitle = $this->getVerbal($rec, 'productId');
    	
    	return $row;
    }


    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(!empty($data->toolbar->buttons['btnAdd'])){
    		$data->toolbar->removeBtn('btnAdd');
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'write' || $action == 'add' || $action == 'edit'){
    		
    		if(isset($rec)){
    			// Може да се добавя само ако има ориджин
    			if(empty($rec->productId)){
    				$res = 'no_one';
    			} else {
    				$productRec = cat_Products::fetch($rec->productId);
    				 
    				// Трябва да е активиран
    				if($productRec->state != 'active'){
    					$res = 'no_one';
    				}
    				 
    				// Трябва и да е производим
    				if($res != 'no_one'){
    					 
    					if($productRec->canManifacture == 'no'){
    						$res = 'no_one';
    					}
    				}
    			}
    			
    			// Ако се създава към продажба, тя трябва да е активна
    			if(!empty($rec->saleId)){
    				if(sales_Sales::fetchField($rec->saleId, "state") != 'active'){
    					$res = 'no_one';
    				}
    			}
    		}
    			
    		if($action == 'add' && empty($rec)){
	    		$res = 'no_one';
    		}
    	}
    	 
    	if(($action == 'activate' || $action == 'restore' || $action == 'conto' || $action == 'write' || $action == 'add') && isset($rec->productId) && $res != 'no_one'){
    
    		// Ако има активно задание, да не може друга да се възстановява,контира,създава или активира
    		if($mvc->fetch("#productId = {$rec->productId} AND (#state = 'active' || #state = 'stopped' || #state = 'wakeup')")){
    			$res = 'no_one';
    		}
    	}
    	 
    	// Ако няма ид, не може да се активира
    	if($action == 'activate' && empty($rec->id)){
    		$res = 'no_one';
    	}
    	
    	// Ако потрбителя няма достъп до сингъла на артикула, не може да модифицира заданията към артикула
    	if(($action == 'add' || $action == 'delete') && isset($rec) && $requiredRoles != 'no_one'){
    		if(!cat_Products::haveRightFor('single', $rec->productId)){
    			$res = 'no_one';
    		}
    	}
    	
    	// Само спрените могат да се променят
    	if($action == 'changerec' && isset($rec)){
    		if($rec->state != 'stopped'){
    			$res = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Връща масив от използваните документи в даден документ (като цитат или
     * са включени в детайлите му)
     * @param int $data - сериализираната дата от документа
     * @return param $res - масив с използваните документи
     * 					[class] - инстанция на документа
     * 					[id] - ид на документа
     */
    function getUsedDocs_($id)
    {
    	$rec = $this->fetchRec($id);
    	$cid = cat_Products::fetchField($rec->productId, 'containerId');
    	$res[$cid] = $cid;
    
    	return $res;
    }
    
    
    /**
     * Добавя действие към историята
     * 
     * @param array $history - масив с историята
     * @param enum(closed,active,rejected,restore,wakeup,stopped,create) $action - действие
     * @param datetime $date - кога
     * @param int $userId - кой
     * @return void
     */
    private static function addToHistory(&$history, $action, $date, $userId)
    {
    	if(!$history){
    		$history = array();
    	}
    	
    	$history[] = array('action' => self::$actionNames[$action], 'date' => $date, 'user' => $userId, 'engaction' => $action);
    }
    
    
    /**
     * Функция, която се извиква след активирането на документа
     */
    public static function on_AfterActivation($mvc, &$rec)
    {
    	// След активиране на заданието, добавяме артикула като перо
    	cat_Products::forceItem($rec->productId, 'catProducts');
    }
    
    
    /**
     * След подготовка на сингъла
     */
    public static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
    	// Подготвяме данните на историята за показване
    	$data->row->history = array();
    	if(count($data->rec->history)){
    		foreach($data->rec->history as $historyRec){
    			$historyRec['action'] = tr($historyRec['action']);
    			$data->row->history[] = (object)array('date'       => cls::get('type_DateTime')->toVerbal($historyRec['date']),
								    				  'user'       => crm_Profiles::createLink($historyRec['user']),
								    				  'action'     => "<span>{$historyRec['action']}</span>",
								    				  'stateclass' => "state-{$historyRec['engaction']}"
    			);
    		}
    	}
    	
    	$data->row->history = array_reverse($data->row->history, TRUE);
    }
    
    
    /**
     * Реакция в счетоводния журнал при оттегляне на счетоводен документ
     */
    public static function on_AfterReject(core_Mvc $mvc, &$res, $id)
    {
    	// Записваме действието във историята
    	$rec = $mvc->fetchRec($id);
    	self::addToHistory($rec->history, 'rejected', $rec->modifiedOn, $rec->modifiedBy);
    	$mvc->save($rec, 'history');
    }
    
    
    /**
     * След възстановяване
     */
    public static function on_AfterRestore(core_Mvc $mvc, &$res, $id)
    {
    	// Записваме действието във историята
    	$rec = $mvc->fetchRec($id);
    	self::addToHistory($rec->history, 'restore', $rec->modifiedOn, $rec->modifiedBy);
    	$mvc->save($rec, 'history');
    }
    
    
    /**
     * След промяна на състоянието
     */
    public static function on_AfterChangeState($mvc, &$rec)
    {
    	// Записваме в историята действието
    	self::addToHistory($rec->history, $rec->state, dt::now(), core_Users::getCurrent());
    	$mvc->save($rec, 'history');
    }
    
    
    /**
     * Подготовка на заданията за артикула
     * 
     * @param stdClass $data
     */
    public function prepareJobs($data)
    {
    	$data->rows = array();
    	$data->hideToolsCol = $data->hideSaleCol = TRUE;
    	
    	// Намираме неоттеглените задания
    	$query = $this->getQuery();
    	$query->where("#productId = {$data->masterId}");
    	$query->where("#state != 'rejected'");
    	$query->orderBy("id", 'DESC');
    	while($rec = $query->fetch()){
    		$data->rows[$rec->id] = $this->recToVerbal($rec);
    		if(isset($rec->saleId)){
    			$data->hideSaleCol = FALSE;
    		}
    		
    		if($this->haveRightFor('edit', $rec)){
    			$data->hideToolsCol = FALSE;
    		}
    	}
    	
    	$masterInfo = $data->masterMvc->getProductInfo($data->masterId);
    	if(!isset($masterInfo->meta['canManifacture'])){
    		$data->notManifacturable = TRUE;
    	}
    	
    	if(!haveRole('ceo,planning') || ($data->notManifacturable === TRUE && !count($data->rows))){
    		$data->hide = TRUE;
    		return;
    	}
    	
    	$data->TabCaption = 'Задания';
    	$data->Tab = 'top';
    	
    	// Проверяваме можем ли да добавяме нови задания
    	if($this->haveRightFor('add', (object)array('productId' => $data->masterId))){
    		$data->addUrl = array($this, 'add', 'productId' => $data->masterId, 'ret_url' => TRUE);
    	}
    }
    
    
    /**
     * Рендиране на заданията към артикул
     * 
     * @param stdClass $data
     * @return core_ET $tpl - шаблон на детайла
     */
    public function renderJobs($data)
    {
    	 if($data->hide === TRUE) return;
    	
    	 $tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
    	 $title = tr('Задания за производство');
    	 $tpl->append($title, 'title');
    	 
    	 if(isset($data->addUrl)){
    	 	$addBtn = ht::createLink('', $data->addUrl, FALSE, 'ef_icon=img/16/add.png,title=Добавяне на ново задание за производство');
    	 	$tpl->append($addBtn, 'title');
    	 }
    	 
    	 $listFields = arr::make('tools=Пулт,dueDate=Падеж,title=Документ,saleId=Към продажба,quantity=Количество,quantityProduced=Произведено,createdBy=Oт,createdOn=На');
    	 
    	 if($data->hideSaleCol){
    	 	unset($listFields['saleId']);
    	 }
    	 
    	 if($data->hideToolsCol){
    	 	unset($listFields['tools']);
    	 }
    	 
    	 $table = cls::get('core_TableView', array('mvc' => $this));
    	 $details = $table->get($data->rows, $listFields);
    	 
    	 // Ако артикула не е производим, показваме в детайла
    	 if($data->notManifacturable === TRUE){
    	 	$tpl->append(" <span class='red small'>(" . tr('Артикулът не е производим') . ")</span>", 'title');
    	 	$tpl->append("state-rejected", 'TAB_STATE');
    	 }
    	 
    	 $tpl->replace($details, 'content');
    	 
    	 return $tpl;
    }
    
    
    /**
     * Може ли документа да се добави в посочената папка?
     *
     * @param $folderId int ид на папката
     * @return boolean
     */
    public static function canAddToFolder($folderId)
    {
    	$coverClass = doc_Folders::fetchCoverClassName($folderId);
    	
    	return $coverClass == 'doc_UnsortedFolders';
    }
    
    
    /**
     * Преизчисляваме какво количество е произведено по заданието
     * 
     * @param int $id - ид на запис
     * @return void
     */
    public static function updateProducedQuantity($id)
    {
    	$rec = static::fetchRec($id);
    	$producedQuantity = 0;
    	
    	// Взимаме к-та на произведените артикули по заданието в протокола за производство
    	$prodQuery = planning_ProductionNoteDetails::getQuery();
    	$prodQuery->EXT('state', 'planning_ProductionNotes', 'externalName=state,externalKey=noteId');
    	$prodQuery->XPR('totalQuantity', 'double', 'SUM(#quantity)');
    	$prodQuery->where("#jobId = {$rec->id}");
    	$prodQuery->where("#state = 'active'");
    	$prodQuery->show('totalQuantity');
    	
    	$producedQuantity += $prodQuery->fetch()->totalQuantity;
    	
    	// Взимаме к-та на произведените артикули по заданието в протокола за бързо производство
    	$directProdQuery = planning_DirectProductionNote::getQuery();
    	$directProdQuery->where("#originId = {$rec->containerId}");
    	$directProdQuery->where("#state = 'active'");
    	$directProdQuery->XPR('totalQuantity', 'double', 'SUM(#quantity)');
    	$directProdQuery->show('totalQuantity');
    	
    	$producedQuantity += $directProdQuery->fetch()->totalQuantity;
    	
    	// Обновяваме произведеното к-то по заданието
    	$rec->quantityProduced = $producedQuantity;
    	self::save($rec, 'quantityProduced');
    }
}
