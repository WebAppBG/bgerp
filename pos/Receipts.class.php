<?php



/**
 * Мениджър за "Бележки за продажби" 
 *
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class pos_Receipts extends core_Master {
    
    
	/**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'acc_TransactionSourceIntf, store_ShipmentIntf';
    
    
    /**
     * Заглавие
     */
    var $title = "Бележки за продажба";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_Rejected, plg_Printing, acc_plg_DocumentSummary,
    				 plg_State, bgerp_plg_Blank, pos_Wrapper, plg_Search, plg_Sorting,
                     acc_plg_Contable,plg_Modified';

    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Бележка за продажба";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, title=Заглавие, contragentName, total, paid, change, productCount, state , createdOn, createdBy';
    
    
    /**
	 * Коментари на статията
	 */
	var $details = 'pos_ReceiptDetails';
	
	
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'admin, pos';
    
    
    /**
     * Кой може да променя?
     */
    var $canAdd = 'pos, admin';
    
    
    /**
     * Кой може да променя?
     */
    var $canEdit = 'pos, admin';
    
	
	/**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'pos/tpl/SingleReceipt.shtml';
    
    
    /**
	 * Полета които да са достъпни след изтриване на дъска
	 */
	var $fetchFieldsBeforeDelete = 'id';
	
    
	/** 
	 *  Полета по които ще се търси
	 */
	var $searchFields = 'contragentName';
	
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,input=none');
    	$this->FLD('pointId', 'key(mvc=pos_Points, select=title)', 'caption=Точка на продажба');
    	$this->FLD('contragentName', 'varchar(255)', 'caption=Контрагент,input=none');
    	$this->FLD('contragentObjectId', 'int', 'input=none');
    	$this->FLD('contragentClass', 'key(mvc=core_Classes,select=name)', 'input=none');
    	$this->FLD('total', 'double(decimals=2)', 'caption=Общо, input=none, value=0, summary=amount');
    	$this->FLD('paid', 'double(decimals=2)', 'caption=Платено, input=none, value=0, summary=amount');
    	$this->FLD('change', 'double(decimals=2)', 'caption=Ресто, input=none, value=0, summary=amount');
    	$this->FLD('tax', 'double(decimals=2)', 'caption=Такса, input=none, value=0');
    	$this->FLD('state', 
            'enum(draft=Чернова, active=Контиран, rejected=Сторниран, closed=Затворен)', 
            'caption=Статус, input=none'
        );
        $this->FLD('productCount', 'int', 'caption=Продукти, input=none, value=0,summary=quantity');
    }
    
    
	/**
     * Извиква се преди изпълняването на екшън
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
    	$id = Request::get('id', 'int');
    	if($action == 'single' && !$id) {
    		
    			// Ако не е зададено Ид, намираме кой е последно добавената бележка
	    		$cu = core_Users::getCurrent();
    			$query = static::getQuery();
	    		$query->where("#createdBy = {$cu}");
	    		$query->where("#state = 'draft'");
	    		$query->orderBy("#createdOn", "DESC");
	    		if($rec = $query->fetch()) {
	    			
	    			return Redirect(array($mvc, 'single', $rec->id));
	    		}
    		
	    	// Ако няма последно добавена бележка създаваме нова
    		return Redirect(array($mvc, 'new'));
    	}
    }
    
    
    /**
     *  Екшън създаващ нова бележка, и редиректващ към Единичния и изглед
     *  Добавянето на нова бележка става само през този екшън 
     */
    function act_New()
    {
    	$rec = new stdClass();
    	$posId = pos_Points::getCurrent();
    	
    	$rec->contragentName = tr('Анонимен Клиент');
    	$rec->contragentClass = core_Classes::getId('crm_Persons');
    	$rec->contragentObjectId = pos_Points::defaultContragent($posId);
    	$rec->pointId = $posId;
    	$rec->valior = dt::now();
    	$this->requireRightFor('add', $rec);
    	$id = $this->save($rec);
    	
    	return Redirect(array($this, 'single', $id));
    }
    
    
	/**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->currency = acc_Periods::getBaseCurrencyCode($rec->createdOn);
    	
    	if($fields['-list']){
    		$row->title = "Бърза продажба №{$row->id}";
    		$row->title = ht::createLink($row->title, array($mvc, 'single', $rec->id), NULL, "ef_icon={$mvc->singleIcon}");
    	}
    	
    	if($rec->state != 'draft'){
    		
    		// показваме датата на последната модификация на документа, ако е активиран
    		$row->valior = dt::mysql2verbal($rec->modifiedOn, "d.m.Y H:i:s");
    	}
    	
    	$cu = core_Users::fetch($rec->createdBy);
    	$row->createdBy = core_Users::recToVerbal($cu)->names;
    }

    
	/**
     * След подготовка на тулбара на единичен изглед.
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        if($mvc->haveRightFor('list')) {
    		
    		// Добавяме бутон за достъп до 'List' изгледа
    		$data->toolbar->addBtn('Всички', array($mvc, 'list', 'ret_url' => TRUE),
    							   'ef_icon=img/16/application_view_list.png, order=18');    
    	}
    	
    	if($data->rec->state != 'draft' && $data->rec->state != 'closed' && sales_Invoices::haveRightFor('add')){
    		if($client = $mvc->pos_ReceiptDetails->hasClient($data->rec->id)){
    		$contragentClass = $client->class;
    		$contragentRec = $contragentClass::fetch($client->id);
    		$url = array('sales_Invoices',
    					 'add',
    					 'folderId' => $contragentRec->folderId, 
    					 'docType' => static::getClassId(), 
    					 'docId' => $data->rec->id);
    		$data->toolbar->addBtn('Фактура', $url, 'ef_icon=img/16/invoice.png, order=18');
    		}
    	}
    }
    
    
    /**
     * След подготовката на туулбара на списъчния изглед
     */
	static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if($mvc->haveRightFor('add')){
    		$addUrl = array($mvc, 'new');
    		$data->toolbar->buttons['btnAdd']->url = $addUrl;
    	}
    }
    
    
    /**
     * Пушваме css и js файловете
     */
    static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {	
    	if(!Request::get('ajax_mode')) {
	    	jquery_Jquery::enable($tpl);
	    	$tpl->push('pos/tpl/css/styles.css', 'CSS');
	    	$tpl->push('pos/js/scripts.js', 'JS');
	    	$tpl->push($data->theme . '/style.css', 'CSS');
	    	
	    	if($data->products->arr) {
	    		$tpl->replace(pos_Favourites::renderPosProducts($data->products), 'PRODUCTS');
	    	}
    	}
    }
    
    
    /**
     * Интерфейсен метод за вземане на продуктите (@see store_ShipmentIntf)
     * @param int $id - ид на бележка
     * @return array $products - Масив от продукти
     */
    public function getShipmentProducts($id)
    {
    	return static::getProducts($id);
    }
    
    
    /**
     * Извлича информацията за всички продукти които са продадени чрез
     * тази бележки, във вид подходящ за контирането
     * @param int id - ид на бележката
     * @param boolean $count - FALSE  връща масив от продуктите
     * 						   TRUE връща само броя на продуктите
     * @return array $products - Масив от продукти
     */
    public static function getProducts($id, $count = FALSE)
    {
    	expect($rec = static::fetch($id), 'Несъществуваща бележка');
    	$posRec = pos_Points::fetch($rec->pointId);
    	
    	$products = array();
    	$totalQuantity = 0;
    	$currencyId = acc_Periods::getBaseCurrencyId($rec->createdOn);
    	
    	$query = pos_ReceiptDetails::getQuery();
    	$query->where("#receiptId = {$id}");
    	$query->where("#quantity != 0");
    	$query->where("#action LIKE '%sale%'");
    	
	    while($rec = $query->fetch()) {
	    	$info = cat_Products::getProductInfo($rec->productId, $rec->value);
	    	
	    	if($info->packagingRec){
	    		$packagingId = $info->packagingRec->packagingId;
	    		$quantityInPack = $info->packagingRec->quantity;
	    	} else {
	    		$packagingId = NULL;
	    		$quantityInPack = 1;
	    	}
	    	
	    	$totalQuantity += $rec->quantity;
	    	$products[] = (object) array(
	    		'policyId' => $posRec->policyId,
	    		'productId' => $rec->productId,
		    	'price' => $rec->price,
	    		'vatPrice' => $rec->price * $rec->param,
	    	    'packagingId' => $packagingId,
	    	    'quantityInPack' => $quantityInPack,
	    	    'uomId' => $info->productRec->measureId,
		    	'quantity' => $rec->quantity);
	    }
	    
    	if($count){
    		return $totalQuantity;
    	}
	    
    	return $products;
    }
    
    
    /**
     * Ъпдейтва бележката след като и се създаде нов детайл
     * @param stdClass $detailRec - запис от pos_ReceiptDetails
     */
    function updateReceipt($detailRec)
    {
    	expect($rec = $this->fetch($detailRec->receiptId));
    	$action = explode("|", $detailRec->action);
    	switch($action[0]) {
    		case 'sale':
    			
    			// "Продажба" : преизчисляваме общата стойност на бележката
    			$rec->total = $this->countTotal($rec->id);
    			$rec->productCount = pos_Receipts::getProducts($rec->id, TRUE);
    			$change = $rec->paid - $rec->total;
    			if($change > 0) {
    				$rec->change = $change;
    			}
    			break;
    		case 'payment':
    			
    			// "Плащане" : преизчисляваме платеното до сега и рестото
    			$rec->paid = $this->countPaidAmount($rec->id);
    			$change = $rec->paid - $rec->total;
    			if($change > 0) {
    				$rec->change = $change;
    			}
    			break;
    		case 'client':
    			
    			// "Клиент" : записваме в бележката информацията за контрагента
    			$contragentRec = explode("|", $detailRec->param);
    			$rec->contragentId = $contragentRec[0];
    			$class = $contragentRec[1];
    			$rec->contragentClassId = $class::getClassId();
    			$rec->contragentName = $class::getTitleById($contragentRec[0]);
    			break;
    		case 'discount':
    			if($action[1] == 'sum'){
    				
    				// Ако отстъпката е сума намаляваме общата сума с отстъпката
    				$rec->total -= $detailRec->ean;
    			}
    			break;
    	}
    	
    	$this->save($rec);
    }
    
    
    /**
     * Изчислява всичко платено до момента
     * @param int $id - запис от модела
     * @return double $paid - платената сума до момента
     */
    function countPaidAmount($id)
    {
    	$paid = 0;
    	$query = pos_ReceiptDetails::getQuery();
    	$query->where("#receiptId = {$id}");
    	$query->where("#action LIKE '%payment%'");
    	while($dRec = $query->fetch()) {
    		$paid += $dRec->amount;
    	}
    	
    	return $paid;
    }
    
    
    /**
     * Изчислява дължимата сума
     * @param int $id
     * @return double $total;
     */
    function countTotal($id)
    {
    	$total = 0;
    	$query = pos_ReceiptDetails::getQuery();
    	$date = $this->fetchField($id, 'createdOn');
    	$query->where("#receiptId = {$id}");
    	$query->where("#action LIKE '%sale%'");
    	while($dRec = $query->fetch()) {
    		$vat = cat_Products::getVat($dRec->productId, $date);
    		$total += $dRec->amount + ($dRec->amount * $vat);
    	}
    	
    	return $total;
    }
    
    
    /**
     *  Филтрираме бележката
     */
	public static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
    	$data->query->orderBy('#createdOn', 'DESC');
    }
    
    
    /**
	 * Модификация на ролите, които могат да видят избраната тема
	 */
    static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{ 
		// Никой неможе да редактира бележка
		if($action == 'edit') {
			$res = 'no_one';
		}
		
		// Никой неможе да изтрива активирана бележка
		if($action == 'delete' && $rec->state != 'draft') {
			$res = 'no_one';
		}
		
		// Можем да контираме бележки само когато те са чернови и платената
		// сума е по-голяма или равна на общата или общата сума е <= 0
		if($action == 'conto' && isset($rec->id)) {
			if($rec->total == 0 || $rec->paid < $rec->total) {
				$res = 'no_one';
			}
		}
		
		// Немогат да се оттеглявт бележки в затворен сч. период
		if($action == 'reject'){
			$period = acc_Periods::fetchByDate($rec->valior);
			if($period->state == 'closed') {
				$res = 'no_one';
			}
		}
	}
	
	
	/**
   	 *  Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
   	 *  Създава транзакция която се записва в Журнала, при контирането
   	 */
    public static function getTransaction($id)
    {
    	expect($rec = static::fetch($id));
    	$products = static::getProducts($id);
    	$posRec = pos_Points::fetch($rec->pointId);
    	$totalVat = 0;
    	
    	$currencyId = acc_Periods::getBaseCurrencyId($rec->createdOn);
    	$currencyCode = currency_Currencies::getCodeById($currencyId);
    	
    	foreach ($products as $product) {
    		$totalQuantity = $product->quantity * $product->quantityInPack;
    		$totalAmount = $totalQuantity * $product->price;
    		$totalVat += $product->vatPrice;
    		$amount = currency_CurrencyRates::convertAmount($totalAmount, $rec->createdOn, $currencyCode);
	    	
    		// Първо Отчитаме прихода от продажбата
    		$entries[] = array(
	        'amount' => $amount, // Стойност на продукта за цялото количество, в основна валута
	        'debit' => array(
	            '501',  // Сметка "501. Каси"
	                array('cash_Cases', $posRec->caseId), // Перо 1 - Каса
	                array('currency_Currencies', $currencyId),     // Перо 3 - Валута
	            'quantity' => $amount), // "брой пари" във валутата на продажбата
	        
	        'credit' => array(
	            '7012', // Сметка "7012. Приходи от POS продажби"
	              	array('cat_Products', $product->productId), // Перо 1 - Продукт
	            'quantity' => $totalQuantity), // Количество продукт в основната му мярка
	    	);
	    	
	    	// После отчитаме експедиране от склада
    		$entries[] = array(
		        'debit' => array(
		            '7012', // Сметка "7012. Приходи от POS продажби"
		            	array('cat_Products', $product->productId), // Перо 1 - Продукт
	            	'quantity' => $totalQuantity), // Количество продукт в основната му мярка
		        
		        'credit' => array(
		            '321', // Сметка "321. Стандартни продукти"
		              	array('store_Stores', $posRec->storeId), // Перо 1 - Склад
		              	array('cat_Products', $product->productId), // Перо 1 - Продукт
	                'quantity' => $totalQuantity), // Количество продукт в основната му мярка
	    	);
    	}
    	
    	$entries[] = array(
                    'amount' => $totalVat,  // равностойноста на сумата в основната валута
                    
                    'debit' => array(
                        '501',  // Сметка "501. Каси"
	                		array('cash_Cases', $posRec->caseId), // Перо 1 - Каса
	                		array('currency_Currencies', $currencyId), 
	                	'quantity' => $totalVat, 
                    ),
                    
                    'credit' => array(
                        '4532', // кредитна сметка
                        'quantity' => $totalVat,
                    ));
    	
    	$transaction = (object)array(
                'reason'  => 'Касова бележка #' . $rec->id,
                'valior'  => $rec->createdOn,
                'entries' => $entries, 
            );
      
      return $transaction;
    }
    
    
	/**
     * Финализиране на транзакцията
     */
    public static function finalizeTransaction($id)
    {
        $rec = self::fetchRec($id);
        $rec->state = 'active';
        
        return self::save($rec);
    }
    
	
    /**
     * Предефиниране на наследения метод act_Single
     */
    function act_Single()
    {   
        $this->requireRightFor('single');
    	$id = Request::get('id', 'int');
        if(!$id) {
        	$id = Request::get('receiptId', 'int');
        }
        $data = new stdClass();
        expect($data->rec = $this->fetch($id));
        
        $conf = core_Packs::getConfig('pos');
        $data->theme = $conf->POS_PRODUCTS_DEFAULT_THEME;
        
        $this->requireRightFor('single', $data->rec);
        $this->prepareSingle($data);
    	if(!Mode::is('printing') && !Mode::is('screenMode', 'narrow') && $data->rec->state == 'draft') {
    		$data->products = pos_Favourites::prepareProducts();
    		$data->products->theme = $data->theme;
    	}
    	
        if($dForm = $data->pos_ReceiptDetails->form) {
            $rec = $dForm->input();
            $Details = cls::get('pos_ReceiptDetails');
			$Details->invoke('AfterInputEditForm', array($dForm));
			
        	// Ако формата е успешно изпратена - запис, лог, редирект
            $ean = Request::get('ean');
			if ($dForm->isSubmitted() && isset($ean)) {
            	
            	if($Details->haveRightFor('add', (object) array('receiptId' => $data->rec->id))) {
	            	
            		// Записваме данните
	            	$id = $Details->save($rec);
	                $Details->log('add', $id);
	                
	                return new Redirect(array($this, 'Single', $data->rec->id, "ajax_mode" => Request::get("ajax_mode")));
            	}
            }
        }
        
        Mode::set('wrapper', 'page_Empty');
        
        $tpl = $this->renderSingle($data);
        
        if(Request::get('ajax_mode')){
        	echo json_encode($tpl->getContent());
        	shutdown();
        }
        $this->log('Single: ' . ($data->log ? $data->log : tr($data->title)), $id);
        
        return $tpl;
    }
    
    
    /**
     * @param int $id
     * @return stdClass
     * @see acc_TransactionSourceIntf::rejectTransaction
     */
    public static function rejectTransaction($id)
    {
        $rec = self::fetch($id, 'id,state,valior');
       
        if ($rec) {
            static::reject($id);
        }
    }
    
    
    /**
     * Имплементиране на интерфейсен метод ( @see acc_TransactionSourceIntf )
     */
    static function on_AfterGetLink($mvc, &$res, $id)
    {
    	if(!$res) {
            $title = sprintf('%s&nbsp;№%d',
                empty($mvc->singleTitle) ? $mvc->title : $mvc->singleTitle, $id);
            $res = ht::createLink($title, array($mvc, 'single', $id));
        }
    }
    
     
     /**
     * Изтриваме детайлите ако се изтрие мастъра
     */
    function on_AfterDelete($mvc, &$res, $query)
    {
        foreach($query->getDeletedRecs() as $rec) {
        	$mvc->pos_ReceiptDetails->delete("#receiptId = {$rec->id}");
        }
    }
}