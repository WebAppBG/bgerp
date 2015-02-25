<?php



/**
 * Мениджър на Задания за производство
 *
 *
 * @category  bgerp
 * @package   mp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Задания за производство
 */
class mp_Jobs extends core_Master
{
    
    
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
    public $loadList = 'plg_RowTools, doc_DocumentPlg, mp_Wrapper, doc_ActivatePlg, plg_Search, doc_SharablePlg';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, mp';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, mp';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, mp';
    
    
    /**
     * Кой може да добавя?
     */
    public $canClose = 'ceo, mp';
    
    
    /**
     * Кой има право да пише?
     */
    public $canWrite = 'ceo, mp';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, mp';

	
	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo, mp';
    
    
	/**
	 * Полета за търсене
	 */
	public $searchFields = 'folderId';
	
	
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
    public $listFields = 'tools=Пулт, originId=Спецификация, dueDate, quantity, state, createdOn, createdBy';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Шаблон за единичен изглед
     */
    public $singleLayoutFile = 'mp/tpl/SingleLayoutJob.shtml';
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('dueDate', 'date(smartTime)', 'caption=Падеж,mandatory');
    	$this->FLD('quantity', 'double(decimals=2)', 'caption=Количество,mandatory,silent');
    	$this->FLD('notes', 'richtext(rows=3)', 'caption=Забележки');
    	$this->FLD('deliveryTermId', 'key(mvc=cond_DeliveryTerms,select=codeName,allowEmpty)', 'caption=Доставка->Условие');
    	$this->FLD('deliveryDate', 'date(smartTime)', 'caption=Доставка->Срок');
    	$this->FLD('deliveryPlace', 'key(mvc=crm_Locations,select=title,allowEmpty)', 'caption=Доставка->Място');
    	$this->FLD('weight', 'cat_type_Weight', 'caption=Тегло,input=none');
    	$this->FLD('brutoWeight', 'cat_type_Weight', 'caption=Бруто,input=none');
    	$this->FLD('data', 'blob(serialize,compress)', 'input=none');
    	$this->FLD('state',
    			'enum(draft=Чернова, active=Активирано, rejected=Отказано, closed=Затворено)',
    			'caption=Статус, input=none'
    	);
    }
    
    
    /**
     *  Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
    	$data->listFilter->view = 'horizontal';
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    	$data->listFilter->showFields = 'search';
    
    	// Активиране на филтъра
    	$data->listFilter->input();
    }
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    public static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
    	if($rec->originId){
    		// Извличане на ключовите думи от документа
    		$origin = doc_Containers::getDocument($rec->originId);
    		$title = $origin->getTitleById();
    		 
    		$res = plg_Search::normalizeText($title);
    		$res = " " . $res;
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$origin = doc_Containers::getDocument($rec->originId);
    	if($fields['-list']){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    		$row->originId = $origin->getHyperlink(TRUE);
    	}
    	 
    	if($fields['-single']){
    
    		$pInfo = $origin->getProductInfo();
    		$row->quantity .= " " . cat_UoM::getShortName($pInfo->productRec->measureId);
    		$row->origin = $origin->renderJobView($rec->modifiedOn);
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
    
    		// Ако има активно задание, да не може друга да се възстановява,контира,създава или активира
    		if($mvc->fetch("#originId = {$rec->originId} AND #state = 'active'")){
    			$res = 'no_one';
    		}
    	}
    	 
    	// Ако няма ид, не може да се активира
    	if($action == 'activate' && empty($rec->id)){
    		$res = 'no_one';
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
    	$origin = doc_Containers::getDocument($this->fetchRec($id)->originId);
    	$res[] = (object)array('class' => $origin->getInstance(), 'id' => $origin->that);
    
    	return $res;
    }
    
    
    /**
     * Функция, която се извиква след активирането на документа
     */
    public static function on_AfterActivation($mvc, &$rec)
    {
    	// След активиране на заданието, добавяме артикула като перо
    	$origin = doc_Containers::getDocument($mvc->fetchRec($rec)->originId);
    	$origin->forceItem('catProducts');
    }
    
    
    /**
     * При нова сделка, се ънсетва threadId-то, ако има
     */
    public static function on_AfterPrepareDocumentLocation($mvc, $form)
    {
    	if($form->rec->threadId && !$form->rec->id){
    		unset($form->rec->threadId);
    	}
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	if($mvc->haveRightFor('close', $data->rec)){
    		if($data->rec->state == 'closed'){
    			$data->toolbar->addBtn("Активиране", array($mvc, 'changeState', $data->rec->id, 'ret_url' => TRUE), 'ef_icon = img/16/lightbulb.png,title=Активиранe на артикула,warning=Сигурнили сте че искате да активирате артикула, това ще му активира перото');
    		} elseif($data->rec->state == 'active'){
    			$data->toolbar->addBtn("Приключване", array($mvc, 'changeState', $data->rec->id, 'ret_url' => TRUE), 'ef_icon = img/16/lightbulb_off.png,title=Затваряне артикула и перото му,warning=Сигурнили сте че искате да приключите артикула, това ще му затвори перото');
    		}
    	}
    }
    
    
    /**
     * Затваря/отваря артикула и перото му
     */
    public function act_changeState()
    {
    	$this->requireRightFor('close');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	$this->requireRightFor('close', $rec);
    	 
    	$state = ($rec->state == 'closed') ? 'active' : 'closed';
    	$rec->exState = $rec->state;
    	$rec->state = $state;
    	 
    	$this->save($rec, 'state');
    	 
    	return followRetUrl();
    }
}