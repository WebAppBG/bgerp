<?php


/**
 * Клас за Отворени сделки. След запис на активна продажба/покупка
 * се създава нов запис в модела. Така лесно могат да се създават пораждащи
 * документи възоснова на тях.
 * Модела се използва в модулите 'cash', 'bank', 'store'
 * В 'cash': се създават приходни и разходни касови ордер
 * В 'bank': се създават приходни и разходни банкови документи
 * В 'store': се създават експедиционни нареждания и складови разписки
 * 
 * Посочените документи се записват в треда на съответната продажба/покупка
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class deals_OpenDeals extends core_Manager {
    
    
    /**
     * Заглавие
     */
    public $title = 'Отворени сделки';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'acc_OpenDeals';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Отворена сделка";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'valior=Вальор, docId=Документ, client=Клиент, currencyId=Валута, amountDeal, amountPaid, state=Състояние, newDoc=Създаване';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'acc_plg_DocumentSummary, plg_Search, plg_Sorting, plg_Rejected';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, cash, bank, store';
	
	
	/**
	 * Кой може да създава
	 */
	public $canAdd = 'no_one';
	
	
	/**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('docClass', 'class(interface=bgerp_DealAggregatorIntf,select=title)', 'caption=Документ->Клас');
        $this->FLD('docId', 'int', 'caption=Документ->Обект,tdClass=leftCol');
    	$this->FLD('valior', 'date', 'caption=Дата');
    	$this->FLD('amountDeal', 'double(decimals=2)', 'caption=Сума->Поръчано, summary = amount');
    	$this->FLD('amountPaid', 'double(decimals=2)', 'caption=Сума->Платено, summary = amount');
    	$this->FLD('state', 'enum(active=Активно, closed=Приключено, rejected=Оттеглено)', 'caption=Състояние');
    	
    	$this->setDbUnique('docClass,docId');
    }
	
	
	/**
      * Добавя ключови думи за пълнотекстово търсене
      */
     function on_AfterGetSearchKeywords($mvc, &$res, $rec)
     {
    	// Извличане на ключовите думи от документа
     	$object = new core_ObjectReference($rec->docClass, $rec->docId);
    	$folderId = $object->fetchField('folderId');
    	
    	$keywords = $object->getHandle();
    	$keywords .= " " . doc_Folders::fetchField($folderId, 'title');
     	
    	$res = plg_Search::normalizeText($keywords);
    	$res = " " . $res;
     }
     
     
	/**
     * Малко манипулации след подготвянето на формата за филтриране
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
    	
    	$data->listFilter->FNC('show', 'varchar', 'input=hidden');
    	$data->listFilter->FNC('sState', 'enum(all=Всички, active=Активни, closed=Приключени)', 'caption=Състояние,input');
    	$data->listFilter->setDefault('show', Request::get('show'));
    	
    	$data->listFilter->showFields = 'search,from,to';
    	if(!Request::get('Rejected', 'int')){
    		$data->listFilter->showFields .= ', sState';
    	}
    	$data->listFilter->input(NULL, 'silent');
        
    	$data->query->orderBy('state', "ASC");
		$data->query->orderBy('id', "DESC");
		
		if(isset($data->listFilter->rec->sState) && $data->listFilter->rec->sState != 'all'){
			$data->query->where("#state = '{$data->listFilter->rec->sState}'");
		}
		
		$data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
    }
      	
	
	/**
	 * Преди подготовка на полетата за показване в списъчния изглед
	 */
	static function on_AfterPrepareListFields($mvc, $data)
    {
    	if(Mode::is('screenMode', 'narrow')){
    		
    		// В мобилен изглед, бутона за нови документи е първи
    		$tmp = array_pop($data->listFields);
    		$data->listFields = array('newDoc' => $tmp) + $data->listFields;
    	}
    }
	
	
	/**
	 * Записва/Обновява нова отворена сделка
	 * @param stdClass $rec - запис от sales_Sales или purchase_Requests
	 * @param mixed $docClass - инстанция или име на класа
	 */
    public static function saveRec($rec, $docClass)
    {
    	// Записа се записва само при активация на документа със сума на сделката
    	$info = $docClass->getAggregateDealInfo($rec->id);
    	
    	$classId = $docClass::getClassId();
    	$new = array(
    		'valior' => $info->get('agreedValior'),
    		'amountDeal' => $info->get('amount'),
    		'amountPaid' => $info->get('amountPaid'), 
    		'state' => $rec->state,
    		'docClass' => $classId,
    		'docId' => $rec->id,
    		'id' => static::fetchField("#docClass = {$classId} AND #docId = {$rec->id}", 'id'),
    	);
    		
	    static::save((object)$new);
    }
    
    
    /**
     * След подготовка на list туулбара се добавя флага за
     * обвивката на пакета
     */
    function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
    	if(Request::get('Rejected', 'int')){
    		$data->toolbar->buttons['listBtn']->url = array($mvc, 'list', 'show' => Request::get('show'));
    	}
    	
    	if(!empty($data->toolbar->buttons['binBtn'])){
    		$data->toolbar->buttons['binBtn']->url = array($mvc, 'list', 'show' => Request::get('show'), 'Rejected' => TRUE);
    	}
    }
    
    
    /**
	 * След обработка на вербалните данни
	 */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if($fields['-list']){
	    	$row->ROW_ATTR['class'] = "state-{$rec->state}";
    		
    		// Извличане на записа на документа и папката
    		$DocClass = cls::get($rec->docClass);
	    	$docRec = $DocClass->fetch($rec->docId, 'folderId,currencyId,containerId,currencyRate');
	    	$folderRec = doc_Folders::fetch($docRec->folderId);
	    	
	    	$row->currencyId = $docRec->currencyId;
	    	$inCharge = doc_Folders::recToVerbal($folderRec)->inCharge;
	    	$row->client = $inCharge. " » " . doc_Folders::recToVerbal($folderRec)->title;
	    	$row->docId = $DocClass->getHandle($rec->docId);
	    	
    		// Обръщане на сумите в валутата на документа
	    	foreach (array('Deal', 'Paid') as $name){
	    		$field = "amount{$name}";
		    	if(empty($rec->$field)){
		    		$row->$field = "<span class='quiet'>0,00</span>";
		    	} else {
		    		$row->$field = $mvc->getFieldType($field)->toVerbal($rec->$field / $docRec->currencyRate);
		    	}
	    	}
	    	
	    	$attr = array();
	    	$attr['class'] = 'linkWithIcon';
	    	if($DocClass->haveRightFor('single', $rec->docId)){
	    		
	    		// Ако потребителя има достъп до документа, той излиза като линк
	    		$icon = $DocClass->getIcon($rec->docId);
	    		$attr['style'] = 'background-image:url(' . sbf($icon) . ');';
	    		$row->docId = ht::createLink($row->docId, array($DocClass, 'single', $rec->docId), NULL, $attr);
	    	
	    		// Ако документа е активен и потребителя има достъп до него, може да генерира документи
		    	if($rec->state == 'active'){
		    		$row->newDoc = $mvc->getNewDocBtns($docRec->id, $docRec->containerId, $DocClass);
		    	}
	    	} else {
	    		
	    		// Ако няма достъп, докумнта излиза с катинарче
	    		$icon = ht::createElement('img', array('src' => sbf('img/16/lock.png', '')));
	    		$row->docId = $icon . " " . "<span style='color:#777'>" . $row->docId . "";
	    		unset($row->amountDeal, $row->amountPaid, $row->currencyId);
	    	}
    	}
    }
    
    
    /**
     * Подготовка бутоните за генериране на нови документи възоснова на продажбата/покупката
     * @param int $threadId - ид на нишката 
     * @param core_Master $docClass - инстанция на класа
     * @return html $btns
     */
    private function getNewDocBtns($id, $originId, core_Master $docClass)
    {
    	$btns = "";
    	switch(Request::get('show')){
	    	case 'cash':
	    		
	    		// Приходен и Разходен касов ордер
	    		$btns = ht::createBtn('ПКО', array('cash_Pko', 'add', 'originId' => $originId), NULL, NULL, 'ef_icon=img/16/money_add.png,title=Нов приходен касов ордер');
	    		$btns .= ht::createBtn('РКО', array('cash_Rko', 'add', 'originId' => $originId), NULL, NULL, 'ef_icon=img/16/money_delete.png,title=Нов разходен касов ордер');
	    		break;
	    	case 'bank':
	    		
	    		// Приходен и Разходен банков документ
	    		$btns = ht::createBtn('ПБД', array('bank_IncomeDocuments', 'add', 'originId' => $originId), NULL, NULL, 'ef_icon=img/16/bank_add.png,title=Нов приходен банков документ');
	    		$btns .= ht::createBtn('РБД', array('bank_SpendingDocuments', 'add', 'originId' => $originId), NULL, NULL, 'ef_icon=img/16/bank_rem.png,title=Нов разходен банков документ');
				break;
	    	case 'store':
	    		
	    		// Бутони за Складова разписка и Експедиционно нареждане
	    		$btns = ht::createBtn('СР', array('store_Receipts', 'add', 'originId' => $originId), NULL, NULL, 'ef_icon=img/16/shipment.png,title=Нова складова разписка');
	    		$btns .= ht::createBtn('ЕН', array('store_ShipmentOrders', 'add', 'originId' => $originId), NULL, NULL, 'ef_icon=img/16/shipment.png,title=Ново експедиционно нареждане');
	    		
	    		break;
	    }
	    
	    return "<span style='margin-left:0.4em; display: block;'>{$btns}</span>";
	}
    
    
	/**
     * Извиква се преди изпълняването на екшън
     */
    public static function on_BeforeAction(core_Mvc $mvc, &$res, $action)
    {
    	if($action != 'list' && $action != 'default') return;
    	$show = Request::get('show', 'enum(store,bank,cash)');
    	
    	requireRole('powerUser');
    	expect(haveRole("ceo,{$show}"));
    	
    	switch($show){
    		case 'cash':
    			$menu = "Финанси";
    			$subMenu = 'Каси';
                $mvc->load("{$show}_Wrapper");
    			break;
    		case 'bank':
    			$menu = "Финанси";
    			$subMenu = 'Банки';
                $mvc->load("{$show}_Wrapper");
    			break;
    		case 'store':
    			$menu = "Логистика";
    			$subMenu = 'Склад';
                $mvc->load("{$show}_Wrapper");
                $mvc->currentTab = 'Документи->Чакащи';
    			break;
    	}
    	
    	Mode::set('pageMenu', $menu);
		Mode::set('pageSubMenu', $subMenu);
    	
    }
}
