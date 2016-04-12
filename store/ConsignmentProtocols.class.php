<?php



/**
 * Клас 'store_ConsignmentProtocols'
 *
 * Мениджър на протоколи за отговорно пазене
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov<ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_ConsignmentProtocols extends core_Master
{
	
	
	/**
     * Заглавие
     * 
     * @var string
     */
    public $title = 'Протоколи за отговорно пазене';


    /**
     * Флаг, който указва, че документа е партньорски
     */
    public $visibleForPartners = TRUE;
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Cpt';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf, store_iface_DocumentIntf, acc_TransactionSourceIntf=store_transaction_ConsignmentProtocol,batch_MovementSourceIntf=batch_movements_ConsignmentProtocol';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, store_Wrapper, doc_plg_BusinessDoc,plg_Sorting, acc_plg_Contable, cond_plg_DefaultValues,
                    doc_DocumentPlg, plg_Printing, acc_plg_DocumentSummary, trans_plg_LinesPlugin, doc_plg_TplManager, plg_Search, bgerp_plg_Blank, doc_plg_HidePrices';

    
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
    public $canEdit = 'ceo,store';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,store';
    
    
    /**
     * Кой има право да променя?
     */
    public $canChangeline = 'ceo,store';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canConto = 'ceo,store';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'valior, title=Документ, contragentId=Контрагент, folderId, createdOn, createdBy';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/shipment.png';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'store_ConsignmentProtocolDetailsSend,store_ConsignmentProtocolDetailsReceived' ;
    

    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Протокол за отговорно пазене';

   
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.7|Логистика";
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'valior,folderId,note';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';


    /**
     * На кой ред в тулбара да се показва бутона за принтиране
     */
    public $printBtnToolbarRow = 1;

    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('valior', 'date', 'caption=Дата, mandatory');
    	$this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
    	$this->FLD('contragentId', 'int', 'input=hidden,tdClass=leftCol');
    	
    	$this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code,allowEmpty)', 'mandatory,caption=Плащане->Валута');
    	$this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад, mandatory');
    
    	$this->FLD('lineId', 'key(mvc=trans_Lines,select=title, allowEmpty)', 'caption=Транспорт');
    	$this->FLD('note', 'richtext(bucket=Notes,rows=3)', 'caption=Допълнително->Бележки');
    	$this->FLD('state',
    			'enum(draft=Чернова, active=Контиран, rejected=Оттеглен)',
    			'caption=Статус, input=none'
    	);
    	$this->FLD('snapshot', 'blob(serialize, compress)', 'caption=Данни,input=none');
    	
    	$this->FLD('weight', 'cat_type_Weight', 'input=none,caption=Тегло');
    	$this->FLD('volume', 'cat_type_Volume', 'input=none,caption=Обем');
    }
    
    
    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     * @return int $id ид-то на обновения запис
     */
    public function updateMaster_($id)
    {
    	$rec = $this->fetch($id);
    	
    	$dRec1 = store_ConsignmentProtocolDetailsReceived::getQuery();
    	$dRec1->where("#protocolId = {$rec->id}");
    	$measuresSend = $this->getMeasures($dRec1->fetchAll());
    	 
    	$dRec2 = store_ConsignmentProtocolDetailsSend::getQuery();
    	$dRec2->where("#protocolId = {$rec->id}");
    	
    	$measuresReceived = $this->getMeasures($dRec2->fetchAll());
    	$weight =  $measuresSend->weight + $measuresReceived->weight;
    	$volume =  $measuresSend->volume + $measuresReceived->volume;
    	 
    	$rec->weight = $weight;
    	$rec->volume = $volume;
    	
    	return $this->save($rec);
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if(isset($fields['-list'])){
    		$row->contragentId = cls::get($rec->contragentClassId)->getHyperlink($rec->contragentId, TRUE);
    		$row->title = $mvc->getLink($rec->id, 0);
    	}
    	
    	store_DocumentMaster::prepareHeaderInfo($row, $rec);
    	if(isset($fields['-single'])){
    		$row->storeId = store_Stores::getHyperlink($rec->storeId);
    		if($rec->lineId){
    			$row->lineId = trans_Lines::getHyperLink($rec->lineId);
    		}
    		
    		$row->weight = ($row->weightInput) ? $row->weightInput : $row->weight;
    		$row->volume = ($row->volumeInput) ? $row->volumeInput : $row->volume;
    	}
    }
    
    
    /**
     * Функция, която се извиква след активирането на документа
     */
    public static function on_AfterActivation($mvc, &$rec)
    {
    	$rec = $mvc->fetchRec($rec);
    	
    	if(empty($rec->snapshot)){
    		$rec->snapshot = $mvc->prepareSnapshot($rec, dt::now());
    		$mvc->save($rec, 'snapshot');
    	}
    }
    
    
    /**
     * След подготовка на сингъла
     */
    public static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
    	// Ако няма 'снимка' на моментното състояние, генерираме го в момента
    	if(empty($data->rec->snapshot)){
    		$data->rec->snapshot = $mvc->prepareSnapshot($data->rec, dt::now());
    	}
    }
    
    
    /**
     * След рендиране на единичния изглед
     */
    public static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
    	// Ако потребителя няма достъп към визитката на лицето, или не може да види сч. справки то визитката, той не може да види справката
    	$Contragent = cls::get($data->rec->contragentClassId);
    	if(!$Contragent->haveRightFor('single', $rec->contragentId)) return;
    	if(!haveRole($Contragent->canReports)) return;
    	
    	$snapshot = $data->rec->snapshot;
    	
    	$mvcTable = new core_Mvc;
    	$mvcTable->FLD('blQuantity', 'int', 'tdClass=accCell');
    	 
    	$table = cls::get('core_TableView', array('mvc' => $mvcTable));
    	$details = $table->get($snapshot->rows, 'count=№,productId=Артикул,blQuantity=Количество');
    	
    	
    	$tpl->replace($details, 'SNAPSHOT');
    	$tpl->replace($snapshot->date, 'SNAPSHOT_DATE');
    }
    
    
    /**
     * Подготвя снапшот на моментното представяне на базата
     */
    private function prepareSnapshot($rec, $date)
    {
    	$rows = array();
    	
    	// Кое е перото на контрагента ?
    	$contragentItem = acc_Items::fetchItem($rec->contragentClassId, $rec->contragentId);
    	
    	// Ако контрагента не е перо, не показваме нищо
    	if($contragentItem){
    		
    		// За да покажем моментното състояние на сметката на контрагента, взимаме баланса до края на текущия ден
    		$to = dt::addDays(1, $date);
    		$Balance = new acc_ActiveShortBalance(array('from' => $to,
    				'to' => $to,
    				'accs' => '323',
    				'item1' => $contragentItem->id,
    				'strict' => TRUE,
    				'cacheBalance' => FALSE));
    		 
    		// Изчлисляваме в момента, какъв би бил крания баланс по сметката в края на деня
    		$Balance = $Balance->getBalanceBefore('323');
    		 
    		$Double = cls::get('type_Double');
    		$Double->params['smartRound'] = TRUE;
    		$Int = cls::get('type_Int');
    		 
    		$accId = acc_Accounts::getRecBySystemId('323')->id;
    		$count = 1;
    		 
    		// Подготвяме записите за показване
    		foreach ($Balance as $b){
    			if($b['accountId'] != $accId) continue;
    			if($b['blQuantity'] == 0) continue;
    			
    			$row = new stdClass;
    			$row->count = $Int->toVerbal($count);
    			$row->productId = acc_Items::getVerbal($b['ent2Id'], 'titleLink');
    			$row->blQuantity = $Double->toVerbal($b['blQuantity']);
    			if($b['baseQuantity'] < 0){
    				$row->blQuantity = "<span class='red'>{$row->blQuantity}</span>";
    			}
    		
    			$count++;
    			$rows[] = $row;
    		}
    	}
    	
    	// Връщаме подготвените записи, и датата към която са подготвени
        return (object)array('rows' => $rows, 'date' => cls::get('type_DateTime')->toVerbal($date));
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec  = &$form->rec;
    
    	$form->setDefault('valior', dt::now());
    	$form->setDefault('storeId', store_Stores::getCurrent('id', FALSE));
    	$rec->contragentClassId = doc_Folders::fetchCoverClassId($rec->folderId);
    	$rec->contragentId = doc_Folders::fetchCoverId($rec->folderId);
    	$form->setDefault('currencyId', acc_Periods::getBaseCurrencyCode());
    	
    	if(isset($rec->id)){
    		if(store_ConsignmentProtocolDetailsSend::fetchField("#protocolId = {$rec->id}")){
    			$form->setReadOnly('currencyId');
    		}
    	}
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	return tr("|Протокол за отговорно пазене|* №") . $rec->id;
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
     * В кои корици може да се вкарва документа
     *
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getAllowedFolders()
    {
    	return array('doc_ContragentDataIntf');
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
    	$folderClass = doc_Folders::fetchCoverClassName($folderId);
    
    	return cls::haveInterface('doc_ContragentDataIntf', $folderClass);
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
    public static function canAddToThread($threadId)
    {
    	$threadRec = doc_Threads::fetch($threadId);
    	$coverClass = doc_Folders::fetchCoverClassName($threadRec->folderId);
    	 
    	return cls::haveInterface('doc_ContragentDataIntf', $coverClass);
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    function loadSetupData()
    {
    	$tplArr = array();
    	$tplArr[] = array('name' => 'Протокол за отговорно пазене', 'content' => 'store/tpl/SingleLayoutConsignmentProtocol.shtml', 
    			 'narrowContent' => 'store/tpl/SingleLayoutConsignmentProtocolNarrow.shtml', 'lang' => 'bg');
    	
    	$res = '';
    	$res .= doc_TplManager::addOnce($this, $tplArr);
    
    	return $res;
    }
    
    
    /**
     * Подготовка на показване като детайл в транспортните линии
     */
    public function prepareProtocols($data)
    {
    	$data->protocols = array();
    	
    	$query = $this->getQuery();
    	$query->where("#lineId = {$data->masterId}");
    	
    	$count = 1;
    	while($rec = $query->fetch()){
    		
    		$rec->weight = ($rec->weightInput) ? $rec->weightInput : $rec->weight;
    		$rec->volume = ($rec->volumeInput) ? $rec->volumeInput : $rec->volume;
    		
    		$data->masterData->weight += $rec->weight;
    		$data->masterData->volume += $rec->volume;
    		$data->masterData->palletCount += $rec->palletCountInput;
    		
    		$row = $this->recToVerbal($rec, 'storeId,weight,volume,palletCountInput');
    		
    		$row->docId = $this->getLink($rec->id, 0);
    		$row->contragentAddress = str_replace('<br>', ',', $row->contragentAddress);
    		$row->contragentAddress = "<span style='font-size:0.8em'>{$row->contragentAddress}</span>";
    		
    		$row->rowNumb = cls::get('type_Int')->toVerbal($count);
    		$row->ROW_ATTR['class'] = "state-{$rec->state}";
    		$data->protocols[$rec->id] = $row;
    		$count++;
    	}
    }
    
    
    /**
     * Подготовка на показване като детайл в транспортните линии
     */
    public function renderProtocols($data)
    {
    	if(count($data->protocols)){
    		$table = cls::get('core_TableView');
    		$fields = "rowNumb=№,docId=Документ,storeId=Склад,weight=Тегло,volume=Обем,palletCountInput=Палети,contragentAddress=@Адрес";
    		 
    		return $table->get($data->protocols, $fields);
    	}
    }
}
