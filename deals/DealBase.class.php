<?php



/**
 * Абстрактен клас за наследяване от класове сделки
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class deals_DealBase extends core_Master
{

	
	/**
	 * Работен кеш
	 */
	protected $historyCache = array();
	
	
	/**
	 * Колко записи от журнала да се показват от историята
	 */
	protected $historyItemsPerPage = 6;
	
	
	/**
	 * Колко записи от репорта да се показват от отчета
	 */
	protected $reportItemsPerPage = 10;
	
	
	/**
	 * Колко записи от репорта да се показват от отчета
	 * в csv-то
	 */
	protected $csvReportItemsPerPage = 1000;
	
	
	/**
	 * Документа продажба може да бъде само начало на нишка
	 */
	public $onlyFirstInThread = TRUE;
	
	
	/**
	 * В коя номенклатура да се вкара след активиране
	 */
	public $addToListOnActivation = 'deals';
	
	
	/**
	 * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
	 */
	public $rowToolsField = 'tools';
	
	
	/**
	 * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
	 */
	public $rowToolsSingleField = 'title';
	
	
	/**
	 * Извиква се след описанието на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Master &$mvc)
	{
		if(empty($mvc->fields['closedDocuments'])){
			$mvc->FLD('closedDocuments', "keylist(mvc={$mvc->className})", 'input=none,notNull');
		}
		$mvc->FLD('closedOn', 'datetime', 'input=none');
	}
	
	
	/**
	 * Функция, която се извиква след активирането на документа
	 */
	public static function on_AfterActivation($mvc, &$rec)
	{
		$rec = $mvc->fetchRec($rec);
		 
		if($rec->state == 'active'){
			$Cover = doc_Folders::getCover($rec->folderId);
			
			if($Cover->haveInterface('crm_ContragentAccRegIntf')){
				
				// Добавяме контрагента като перо, ако не е
				$Cover->forceItem('contractors');
			}
		}
	}


	/**
	 * Имплементация на @link bgerp_DealAggregatorIntf::getAggregateDealInfo()
	 * Генерира агрегираната бизнес информация за тази сделка
	 *
	 * Обикаля всички документи, имащи отношение към бизнес информацията и извлича от всеки един
	 * неговата "порция" бизнес информация. Всяка порция се натрупва към общия резултат до
	 * момента.
	 *
	 * Списъка с въпросните документи, имащи отношение към бизнес информацията за продажбата е
	 * сечението на следните множества:
	 *
	 *  * Документите, върнати от @link doc_DocumentIntf::getDescendants()
	 *  * Документите, реализиращи интерфейса @link bgerp_DealIntf
	 *  * Документите, в състояние различно от `draft` и `rejected`
	 *
	 * @return bgerp_iface_DealAggregator
	 */
	public function getAggregateDealInfo($id)
	{
		$dealRec = $this->fetchRec($id);
	
		$dealDocuments = $this->getDescendants($dealRec->id);
	
		$aggregateInfo = new bgerp_iface_DealAggregator;
	
		// Извличаме dealInfo от самата сделка
		$this->pushDealInfo($dealRec->id, $aggregateInfo);
	
		foreach ($dealDocuments as $d) {
			$dState = $d->rec('state');
			if ($dState == 'draft' || $dState == 'rejected') {
				// Игнорираме черновите и оттеглените документи
				continue;
			}
			
			if ($d->haveInterface('bgerp_DealIntf')) {
				try{
					$d->getInstance()->pushDealInfo($d->that, $aggregateInfo);
					
				} catch(core_exception_Expect $e){
					$this->logErr('Проблем с пушването на данните на бизнес документ - ' . $e->getMessage(), $dealRec->id);
					reportException($e);
				}
			}
		}
	
		return $aggregateInfo;
	}
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'closewith' && isset($rec)){
    		$options = $mvc->getDealsToCloseWith($rec);
    		if(!count($options) || $rec->state != 'draft'){
    			$res = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Преди рендиране на тулбара
     */
    public static function on_BeforeRenderSingleToolbar($mvc, &$res, &$data)
    {
    	$rec = &$data->rec;
    	
    	if($mvc->haveRightFor('closeWith', $rec)) {
    		 
    		// Ако тази сделка може да се приключи с друга сделка, и има налични сделки подменяме бутона да сочи
    		// към екшън за избиране на кои сделки да се приключат с тази
    		$data->toolbar->removeBtn('btnConto');
    		$data->toolbar->removeBtn('btnActivate');
    		$data->toolbar->addBtn('Активиране', array($mvc, 'closeWith', $rec->id), "id=btnConto{$error}", 'ef_icon = img/16/tick-circle-frame.png,title=Активиране на документа');
    	}
    }
    
    
    /**
     * Кои сделки ще могатд а се приключат с документа
     *
     * @param object $rec
     * @return array $options - опции
     */
    public function getDealsToCloseWith($rec)
    {
    	// Избираме всички други активни сделки от същия тип и валута, като началния документ в същата папка
    	$docs = array();
    	$dealQuery = $this->getQuery();
    	$dealQuery->where("#id != {$rec->id}");
    	$dealQuery->where("#folderId = {$rec->folderId}");
    	$dealQuery->where("#currencyId = '{$rec->currencyId}'");
    	$dealQuery->where("#state = 'active'");
    	$dealQuery->where("#closedDocuments = ''");
    	
    	$valiorFld = ($this->valior) ? $this->valior : 'createdOn';
    	while($dealRec = $dealQuery->fetch()){
    		$title = $this->getRecTitle($dealRec) . " / " . (($this->valiorFld) ? $this->getVerbal($dealRec, $this->valiorFld) : '');
    		$docs[$dealRec->id] = $title;
    	}
    	 
    	return $docs;
    }


    /**
     * Преди да се проверят имали приключени пера в транзакцията
     *
     * Обхождат се всички документи в треда и ако един има приключено перо, документа начало на нишка
     * не може да се оттегля/възстановява/контира
     */
    public static function on_BeforeGetClosedItemsInTransaction($mvc, &$res, $id)
    {
    	$closedItems = array();
    	$rec = $mvc->fetchRec($id);
    	$dealItem = acc_Items::fetchItem($mvc->getClassId(), $rec->id);
    	 
    	// Записите от журнала засягащи това перо
    	$entries = acc_Journal::getEntries(array($mvc, $rec->id));
    	
    	// Към тях добавяме и самия документ
    	$entries[] = (object)array('docType' => $mvc->getClassId(), 'docId' => $rec->id);
    	
    	$entries1 = array();
    	foreach ($entries as $ent){
    		$index = $ent->docType . "|" . $ent->docId;
    		if(!isset($entries1[$index])){
    			$entries1[$index] = $ent;
    		}
    	}
    	
    	// За всеки запис
    	foreach ($entries1 as $ent){
    		
    		// Ако има метод 'getValidatedTransaction'
    		$Doc = cls::get($ent->docType);
    		
    		// Ако транзакцията е направена от друг тред запомняме от кой документ е направена
    		$threadId = $Doc->fetchField($ent->docId, 'threadId');
    		if($threadId != $rec->threadId){
    			$mvc->usedIn[$dealItem->id][] = $Doc->getHandle($ent->docId);
    		}
    		
    		if(cls::existsMethod($Doc, 'getValidatedTransaction')){
    			
    			// Ако има валидна транзакция, проверяваме дали има затворени пера
    			$transaction = $Doc->getValidatedTransaction($ent->docId);
    			
    			if($transaction){
    				// Добавяме всички приключени пера
    				$closedItems += $transaction->getClosedItems();
    			}
    		}
    	}
    	
    	if($rec->state != 'closed'){
    		unset($closedItems[$dealItem->id]);
    	}
    	 
    	// Връщаме намерените пера
    	$res = $closedItems;
    }
    
    
    /**
     * Екшън за приключване на сделка с друга сделка
     */
    public function act_Closewith()
    {
    	$id = Request::get('id', 'int');
    	expect($rec = $this->fetch($id));
    	expect($rec->state == 'draft');
    
    	// Трябва потребителя да може да контира
    	$this->requireRightFor('conto', $rec);
    
    	$options = $this->getDealsToCloseWith($rec);
    	expect(count($options));
    
    	// Подготовка на формата за избор на опция
    	$form = cls::get('core_Form');
    	$form->title = "|Активиране на|* <b>" . $this->getTitleById($id). "</b>" . " ?";
    	$form->info = 'Активирането на тази сделка може да приключи други сделки';
    	$form->FLD('closeWith', "keylist(mvc={$this->className})", 'caption=Приключи и,column=1');
    	$form->setSuggestions('closeWith', $options);
    	$form->input();
    
    	// След като формата се изпрати
    	if($form->isSubmitted()){
    		$rec->contoActions = 'activate';
    		$rec->state = 'active';
    		if(!empty($form->rec->closeWith)){
    			$rec->closedDocuments = $form->rec->closeWith;
    		}
    		$this->save($rec);
    		$this->invoke('AfterActivation', array($rec));
    	   
    		if(!empty($form->rec->closeWith)){
    			$CloseDoc = cls::get($this->closeDealDoc);
    			$deals = keylist::toArray($form->rec->closeWith);
    			foreach ($deals as $dealId){
    					 
    				// Създаване на приключващ документ-чернова
    				$dRec = $this->fetch($dealId);
    				$clId = $CloseDoc->create($this->className, $dRec, $id);
    				$CloseDoc->conto($clId);
    			}
    		}
    	   
    		// Записваме, че потребителя е разглеждал този списък
    		$this->logInfo("Приключване на сделка с друга сделка", $id);
    		
    		return redirect(array($this, 'single', $id));
    	}
    
    	$form->toolbar->addSbBtn('Активиране', 'save', 'ef_icon = img/16/tick-circle-frame.png');
    	$form->toolbar->addBtn('Отказ', array($this, 'single', $id),  'ef_icon = img/16/close16.png');
    	
    	// Рендиране на формата
    	return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if($rec->closedDocuments){
    		$docs = keylist::toArray($rec->closedDocuments);
    		$row->closedDocuments = '';
    		
    		foreach ($docs as $docId){
    			$row->closedDocuments .= ht::createLink($mvc->getHandle($docId), array($mvc, 'single', $docId)) . ", ";
    		}
    		$row->closedDocuments = trim($row->closedDocuments, ", ");
    	}
    	
    	if($fields['-list']){
    		$row->title = $mvc->getLink($rec->id, 0);
    	}
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public static function on_AfterRenderSingleLayout($mvc, &$tpl, &$data)
    {
    	if(isset($data->tabs)){
    		if (isset($data->dealHistory)) {
    			$tab = 'dealHistory';
    		} elseif (isset($data->dealReport)) {
    			$tab =  'dealReport';
    		} else {
    			$tab = 'statistic';
    		}
   
    		$tabHtml = $data->tabs->renderHtml("", $tab);
    		$tpl->replace($tabHtml, 'TABS');
    		
    		// Ако има история на сделката показваме я
    		if(isset($data->dealHistory)){
    			$tableMvc = new core_Mvc;
    			$tableMvc->FLD('debitAcc', 'varchar', 'tdClass=articleCell');
    			$tableMvc->FLD('creditAcc', 'varchar', 'tdClass=articleCell');
    		
    			$table = cls::get('core_TableView', array('mvc' => $tableMvc));
    			$fields = "valior=Вальор,debitAcc=Дебит->Сметка,debitQuantity=Дебит->К-во,debitPrice=Дебит->Цена,creditAcc=Кредит->Сметка,creditQuantity=Кредит->К-во,creditPrice=Кредит->Цена,amount=Сума";
    		
    			$tpl->append($table->get($data->dealHistory, $fields), 'DEAL_HISTORY');
    			$tpl->append($data->historyPager->getHtml(), 'DEAL_HISTORY');
    		}
    		
    		// Ако има отчет на сделката показваме я
    		if(isset($data->dealReport)){
    			$tableMvc = new core_Mvc;
    			$tableMvc->FLD('code', 'varchar');
    			$tableMvc->FLD('productId', 'varchar');
    			$tableMvc->FLD('measure', 'varchar', 'tdClass=accToolsCell,smartCenter');
    			$tableMvc->FLD('quantity', 'varchar', 'tdClass=aright');
    			$tableMvc->FLD('shipQuantity', 'varchar', 'tdClass=aright');
    			$tableMvc->FLD('bQuantity', 'varchar', 'tdClass=aright');
    		
    			$table = cls::get('core_TableView', array('mvc' => $tableMvc));
    			$fields = "code=Код,
    					   productId=Продукт,
    					   measure=Мярка,
    					   quantity=Количество->Поръчано,
    					   shipQuantity=Количество->Доставено,
    					   bQuantity=Количество->Остатък";
    		
    			$tpl->append($table->get($data->dealReport, $fields), 'DEAL_REPORT');
    			$tpl->append($data->reportPager->getHtml(), 'DEAL_REPORT');
    			
    			if($mvc->haveRightFor('export', $data->rec)){
	    			$expUrl = getCurrentUrl();;
	    			$expUrl['export'] = TRUE;
	
	    			$btn = cls::get('core_Toolbar');
	    			$btn->addBtn('Експорт в CSV', $expUrl, NULL, 'ef_icon=img/16/file_extension_xls.png, title=Сваляне на записите в CSV формат');
	    			$btnCSV = 'export';
	    			$btnCSVHtml = $btn->renderHtml("", $btnCSV);
	    			$tpl->replace($btnCSVHtml, 'TABEXP');
    			}
    		}
    	}
    	
    	if(Mode::is('printing') || Mode::is('text', 'xhtml')){
    		$tpl->removeBlock('header');
    		$tpl->removeBlock('STATISTIC_BAR');
    	} elseif(Request::get('dealHistory', 'int')) {
    		$tpl->removeBlock('STATISTIC_BAR');
    	}  elseif(Request::get('dealReport', 'int')) {
    		$tpl->removeBlock('STATISTIC_BAR');
    	}
    }
    
    
    /**
     * Генерираме ключа за кеша
     * Интерфейсен метод
     * 
     * @param core_Mvc $mvc
     * @param NULL|FALSE|string $res
     * @param NULL|integer $id
     * @param object $cRec
     * 
     * @see doc_DocumentIntf
     */
    public static function on_AfterGenerateCacheKey($mvc, &$res, $id, $cRec)
    {
        if ($res === FALSE) return ;
        
        $dealHistory = Request::get('dealHistory');
        $dealReport = Request::get('dealReport');
        
        $res = md5($res . '|' . $dealHistory . '|' . $dealReport);
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     */
    public static function on_AfterPrepareSingle($mvc, &$res, &$data)
    {
    	$tabs = cls::get('core_Tabs', array('htmlClass' => 'deal-history-tab'));
    	$url = getCurrentUrl();
    	unset($url['dealHistory']);
    	unset($url['dealReport']);
    	
    	$histUrl = array();
    	if($data->rec->state != 'draft' && $data->rec->state != 'rejected'){
    		
    		$histUrl = $url;
    		$histUrl['dealHistory'] = TRUE;
    		
    		$reportUrl = $url;
    		$reportUrl['dealReport'] = TRUE;
    		
    		// Ако сме в нормален режим
    		if(!Mode::is('printing') && !Mode::is('text', 'xhtml')){
    			$tabs->TAB('statistic', 'Статистика' , $url);
    			$tabs->TAB('dealHistory', 'Обороти' , $histUrl);
    			$tabs->TAB('dealReport', 'Поръчано/Доставено' , $reportUrl);
    		
    			// Ако е зареден флаг в урл-то и имаме право за журнала подготвяме историята
    			if(Request::get('dealHistory', 'int') && haveRole('acc, ceo')){
    				$mvc->prepareDealHistory($data);
    			}
    			
    			// Ако е зареден флаг в урл-то и имаме право за журнала подготвяме историята
    			if(Request::get('export', 'int') && haveRole('acc, ceo')){
    				$mvc->prepareDealReport($data);
    				$mvc->еxportReport($data);
    			}
    			
    			// Ако е зареден флаг в урл-то и имаме право за отчет подготвяме репорта
    			if(Request::get('dealReport', 'int') && haveRole('acc, ceo')){
    				$mvc->prepareDealReport($data);
    			}
    		
    			// Ако имаме сч. права показваме табовете
    			if(haveRole('acc, ceo')){
    				$data->tabs = $tabs;
    			}
    		}
    	} 
    }
    
    
    /**
     * Екшън който експортира данните
     */
    protected function еxportReport(&$data)
    {
        expect(Request::get('export', 'int'));
        
    	expect($rec = $data->dealReportCSV);

    	// Проверка за права
    	$this->requireRightFor('export', $rec);
        
    	$title = $this->title . " Поръчано/Доставено";
    
    	$csv = $this->prepareCsvExport($rec);
    
    	$fileName = str_replace(' ', '_', Str::utf2ascii($title));
    	 
    	header("Content-type: application/csv");
    	header("Content-Disposition: attachment; filename={$fileName}.csv");
    	header("Pragma: no-cache");
    	header("Expires: 0");
    	 
    	echo $csv;
    
    	shutdown();
    }
    
    
    /**
     * Екшън подготвя данните за експортира 
     */
    protected function prepareCsvExport(&$data)
    {
    	$exportFields = $this->getExportFields();
 
    	$conf = core_Packs::getConfig('core');
    	
    	if (count($this->innerState->recs) > $conf->EF_MAX_EXPORT_CNT) {
    		redirect(array($this), FALSE, "Броят на заявените записи за експорт надвишава максимално разрешения|* - " . $conf->EF_MAX_EXPORT_CNT, 'error');
    	}
    	
    	$csv = "";
    	
    	foreach ($exportFields as $caption) {
    		$header .= $caption. ",";
    	}
    	
    	if(count($data)) {
    		foreach ($data as $id => $rec) {
    			// от вербална стойност ги правим в числа
    			$rec->quantity = (double) $rec->quantity;
    			$rec->shipQuantity = (double) $rec->shipQuantity;
    			$rec->bQuantity = (double) $rec->bQuantity;
    
    			$rCsv = '';
    			
    			foreach ($exportFields as $field => $caption) {
    				$value = '';
    			
	    			if (isset($rec->{$field})) {
	    			
	    				$value = $rec->{$field};
	    				if ($field == 'productId') {
	    					$value = html2text_Converter::toRichText($value);
	    				}
	    				// escape
	    				if (preg_match('/\\r|\\n|,|"/', $value)) {
	    					$value = '"' . str_replace('"', '""', $value) . '"';
	    				}
	    				
	    				$rCsv .= $value. ",";

	    			} else {
	    				$rCsv .= '-' . ",";
	    			}
    			}
    			$csv .= $rCsv;
    			$csv .=  "\n";
    		}
    		$csv = $header . "\n" . $csv;
    	}
    	
    	return $csv; 
    }
    
    
    /**
     * Ще се експортирват полетата, които се
     * показват в табличния изглед
     *
     * @return array
     * @todo да се замести в кода по-горе
     */
    protected function getExportFields_()
    {
    	// Кои полета ще се показват
		$fields = arr::make("code=Код,
    					     productId=Продукт,
    					     measure=Мярка,
    					     quantity=Количество - Поръчано,
    					     shipQuantity=Количество - Доставено,
    					     bQuantity=Количество - Остатък", TRUE);
    
    	return $fields;
    }
    
    
    /**
     * Подготвя обединено представяне на всички записи от журнала където участва сделката
     */
    protected function prepareDealReport(&$data)
    {
    	$rec = $data->rec;

    	// обобщената информация за цялата нищка
    	$dealInfo = self::getAggregateDealInfo($rec->id);
   
    	$report = array();
    	$Int = cls::get('type_Int');

    	// Ако има записи където участва артикула подготвяме ги за показване
    	if(count($dealInfo->products)){
    		foreach ($dealInfo->products as $id => $product) { 
		    	// ако можем да го покажем на страницата	
		    	//if($count >= $start && $count <= $end){
			    	$obj = new stdClass();
			    	// информацията за продукта
			    	$productInfo = cat_Products::getProductInfo($product->productId);
				    // кода на продукта	
			    	$obj->code = $productInfo->productRec->code;
			    	// името на продукта с линк
				    $obj->productId = cat_Products::getShortHyperLink($product->productId);
				    // мярката му
				    $measureId = $productInfo->productRec->measureId;
				    $obj->measure = cat_UoM::fetchField($measureId,'shortName');
				    
				    // поръчаното количество
				    $obj->quantity = $Int->toVerbal($product->quantity);
	
				    $report[$id] = $obj;			    	
		    }
		    
		    // за всеки един масив от доставени продукти
		    foreach ($dealInfo->shippedProducts as $idShip => $shipProduct) {

		    	// ако ид-то на продукта не е добавен в резултатния масив до сега
			    if (!array_key_exists($idShip, $report)) {

			    	// извличаме информацията за продукта
			    	$shipProductInfo = cat_Products::getProductInfo($shipProduct->productId);
			    	// намираме му мярката
			    	$shipMeasureId = $shipProductInfo->productRec->measureId;
			    	// и правим обект с новия продукт		
			    	$report[$idShip] = (object) array ( "code" => $shipProductInfo->productRec->code,
			    					"productId" => cat_Products::getShortHyperLink($shipProduct->productId),
			    					"measure" => cat_UoM::fetchField($shipMeasureId,'shortName'),
			    					"quantity" => 0,
			    					"shipQuantity" => $Int->toVerbal($shipProduct->quantity),
			    					"bQuantity" => NULL
			    					
			    	);
			    // ако вече е добавен		
			    } else {
			    	// ще го ъпдейтнем		
			    	$shipObj = &$report[$idShip];
			    	
			    	if($shipStoreId = $dealInfo->storeId){
			    		$shipQuantityInStore = (double) store_Products::fetchField("#productId = {$shipProduct->productId} AND #storeId = {$shipStoreId}", 'quantity');
			    			
			    		// като добавим доставеното количесто
			    		$shipObj->shipQuantity = $Int->toVerbal($shipProduct->quantity);
			    		// и намерим остатъка за доставяне
			    		$shipObj->bQuantity = $Int->toVerbal(abs(strip_tags($shipObj->quantity) - strip_tags($shipObj->shipQuantity)));
			    			
						$shipDiff = ($rec->state == 'active') ? $shipQuantityInStore : ($shipQuantityInStore - abs(strip_tags($shipObj->quantity) - strip_tags($shipObj->shipQuantity)));
			    			
			    		if($shipDiff < 0){
			    			$shipObj->quantity = "<span class='row-negative' title = '" . tr('Количеството в склада е отрицателно') . "'>{$Int->toVerbal($shipObj->quantity)}</span>";
			    		}
			    	}
			    }
		    }
    	}

    	$data->dealReportCSV = $report;
    	
    	// правим странициране
    	$pager = cls::get('core_Pager',  array('pageVar' => 'P_' .  $this->className,'itemsPerPage' => $this->reportItemsPerPage)); 
    	
    	$cnt = count($report);
    	$pager->itemsCount = $cnt;
    	$data->reportPager = $pager;
    	 
    	$pager->calc();
    	
    	$start = $data->reportPager->rangeStart;
    	$end = $data->reportPager->rangeEnd - 1;
    	
    	// проверяваме дали може да се сложи на страницата
    	$data->dealReport = array_slice ($report, $start, $end - $start + 1);
    }
    
    
    /**
     * Подготвя обединено представяне на всички записи от журнала където участва сделката
     */
    protected function prepareDealHistory(&$data)
    {
    	$rec = $data->rec;
    	
    	// Извличаме всички записи от журнала където сделката е в дебита или в кредита
    	$entries = acc_Journal::getEntries(array($this->className, $rec->id));
    	
    	$history = array();
    	$Date = cls::get('type_Date');
    	$Double = cls::get('type_Double', array('params' => array('decimals' => '2')));
    	
    	$Pager = cls::get('core_Pager', array('itemsPerPage' => $this->historyItemsPerPage));
    	$Pager->setPageVar($this->className, $rec->id);
    	$Pager->itemsCount = count($entries);
    	$Pager->calc();
    	$data->historyPager = $Pager;
    	
    	$start = $data->historyPager->rangeStart;
    	$end = $data->historyPager->rangeEnd - 1;
    	
    	// Ако има записи където участва перото подготвяме ги за показване
    	if(count($entries)){
    		$count = 0;
    		
    		foreach ($entries as $ent){
    			
    			if($count >= $start && $count <= $end){
    				$obj = new stdClass();
    				$obj->valior = $Date->toVerbal($ent->valior);
    				
    				$Doc = cls::get($ent->docType);
    				$docHandle = $Doc->getHandle($ent->docId);
    				if($Doc->haveRightFor('single', $ent->docId)){
    					$docHandle = ht::createLink("#" . $docHandle, array($Doc, 'single', $ent->docId));
    				}
    				
    				$obj->valior .= "<br>{$docHandle}";
    				$obj->valior = "<span style='font-size:0.8em;'>{$obj->valior}</span>";
    				if(empty($this->historyCache[$ent->debitAccId])){
    					$this->historyCache[$ent->debitAccId] = acc_Balances::getAccountLink($ent->debitAccId);
    				}
    				 
    				if(empty($this->historyCache[$ent->creditAccId])){
    					$this->historyCache[$ent->creditAccId] = acc_Balances::getAccountLink($ent->creditAccId);
    				}
    				$obj->debitAcc = $this->historyCache[$ent->debitAccId];
    				$obj->creditAcc = $this->historyCache[$ent->creditAccId];
    				 
    				foreach (range(1, 3) as $i){
    					if(!empty($ent->{"debitItem{$i}"})){
    						$obj->debitAcc .= "<div style='font-size:0.8em;margin-top:1px'>{$i}. " . acc_Items::getVerbal($ent->{"debitItem{$i}"}, 'titleLink') . "</div>";
    					}
    				
    					if(!empty($ent->{"creditItem{$i}"})){
    						$obj->creditAcc .= "<div style='font-size:0.8em;margin-top:1px'>{$i}. " . acc_Items::getVerbal($ent->{"creditItem{$i}"}, 'titleLink') . "</div>";
    					}
    				}
    				 
    				foreach (array('debitQuantity', 'debitPrice', 'creditQuantity', 'creditPrice', 'amount') as $fld){
    					$obj->$fld = "<span style='float:right'>" . $Double->toVerbal($ent->$fld) . "</span>";
    				}
    				 
    				$history[] = $obj;
    			}
    			
    			$count++;
    		}
    	}
    	
    	$data->dealHistory = $history;
    }
}
