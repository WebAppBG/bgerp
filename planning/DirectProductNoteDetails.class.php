<?php


/**
 * Клас 'planning_DirectProductNoteDetails'
 *
 * Детайли на мениджър на детайлите на протокола за бързо производство
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_DirectProductNoteDetails extends deals_ManifactureDetail
{
    
	
	/**
     * Заглавие
     */
    public $title = 'Детайли на протокола за бързо производство';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Ресурс';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'noteId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_SaveAndNew, plg_Created, planning_Wrapper, plg_AlignDecimals2, plg_Sorting';
    
    
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
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, planning';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=№,productId=Материал, packagingId, packQuantity';
    
        
    /**
     * Активен таб
     */
    public $currentTab = 'Протоколи->Бързо производство';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('noteId', 'key(mvc=planning_DirectProductionNote)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('resourceId', 'int', 'silent,caption=Ресурс,input=none,removeAndRefreshForm=productId|packagingId|quantityInPack|quantity|packQuantity|measureId');
        $this->FLD('type', 'enum(input=Влагане,pop=Отпадък)', 'caption=Действие,silent,input=hidden');
        
        parent::setDetailFields($this);
        $this->FLD('conversionRate', 'double', 'input=none');
        
        // Само вложими продукти
        $this->setDbUnique('noteId,productId,type');
    }
    
    
    /**
     * Преди подготвяне на едит формата
     */
    public static function on_BeforePrepareEditForm($mvc, &$res, $data)
    {
    	$type = Request::get('type', 'enum(input,pop)');
    	 
    	$title = ($type == 'pop') ? 'отпадък' : 'материал';
    	$mvc->singleTitle = $title;
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
    	
    	if(isset($rec->id)){
    		$products = array($rec->productId => cat_Products::getTitlebyId($rec->productId, FALSE));
    	} else {
    		$metas = ($rec->type == 'input') ? 'canConvert' : 'canConvert,canStore';
    		$products = array('' => '') + cat_Products::getByProperty($metas);
    		unset($products[$data->masterRec->productId]);
    	}
    	$form->setOptions('productId', $products);
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
    	$rec = &$form->rec;
    	
		if($rec->type == 'pop'){
    		$form->setField('batch', 'input=none');
    	}
    	
    	if($rec->productId){
    		$pInfo = cat_Products::getProductInfo($rec->productId);
    		if(isset($pInfo->meta['canStore'])){
    			$storeId = $mvc->Master->fetchField($rec->noteId, 'inputStoreId');
    			$storeInfo = deals_Helper::checkProductQuantityInStore($rec->productId, $rec->packagingId, $rec->packQuantity, $storeId);
    			$form->info = $storeInfo->formInfo;
    		}
    	
    		if($form->isSubmitted()){
    			
    			if(isset($storeInfo->warning)){
    				$form->setWarning('packQuantity', $storeInfo->warning);
    			}
    			
    			// Ако добавяме отпадък, искаме да има себестойност
    			if($rec->type == 'pop'){
    				$selfValue = price_ListRules::getPrice(price_ListRules::PRICE_LIST_COST, $rec->productId);
    		
    				if(!isset($selfValue)){
    					$form->setError('productId', 'Отпадакът няма себестойност');
    				}
    			}
    		}
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterPrepareListRows($mvc, &$data)
    {
    	if(!count($data->recs)) return;
    	
    	foreach ($data->rows as $id => &$row)
    	{
    		$rec = $data->recs[$id];
    		
    		if($rec->type == 'pop'){
    			$row->packQuantity .= " {$row->packagingId}";
    		}
    	}
    }
    
    
    /**
     * След подготовка на детайлите, изчислява се общата цена
     * и данните се групират
     */
    public static function on_AfterPrepareDetail($mvc, $res, $data)
    {
    	$data->inputArr = $data->popArr = array();
    	$countInputed = $countPoped = 1;
    	$Int = cls::get('type_Int');
    	
    	// За всеки детайл (ако има)
    	if(count($data->rows)){
    		foreach ($data->rows as $id => $row){
    			$rec = $data->recs[$id];
    			if(!is_object($row->tools)){
    				$row->tools = new ET("[#TOOLS#]");
    			}
    			
    			// Разделяме записите според това дали са вложими или не
    			if($rec->type == 'input'){
    				$row->tools->append($Int->toVerbal($countInputed), 'TOOLS');
    				$data->inputArr[$id] = $row;
    				$countInputed++;
    			} else {
    				$row->tools->append($Int->toVerbal($countPoped), 'TOOLS');
    				$data->popArr[$id] = $row;
    				$countPoped++;
    			}
    		}
    	}
    }
    
    
    /**
     * Променяме рендирането на детайлите
     * 
     * @param stdClass $data
     * @return core_ET $tpl
     */
    function renderDetail_($data)
    {
    	$tpl = new ET("");
    	
    	$this->invoke('BeforeRenderListTable', array(&$tpl, &$data));
    	if(Mode::is('printing')){
    		unset($data->listFields['tools']);
    	}
    	
    	// Рендираме таблицата с вложените материали
    	$data->listFields['productId'] = '|Суровини и материали|* ' . "<small style='font-weight:normal'>( |вложени от склад|*: {$data->masterData->row->inputStoreId} )</small>";
    	$table = cls::get('core_TableView', array('mvc' => $this));
    	$table->setFieldsToHideIfEmptyColumn($this->hideListFieldsIfEmpty);
    	
    	$detailsInput = $table->get($data->inputArr, $data->listFields);
    	$tpl->append($detailsInput, 'planning_DirectProductNoteDetails');
    	
    	// Добавяне на бутон за нов материал
    	if($this->haveRightFor('add', (object)array('noteId' => $data->masterId, 'type' => 'input'))){
    		$tpl->append(ht::createBtn('Материал', array($this, 'add', 'noteId' => $data->masterId, 'type' => 'input', 'ret_url' => TRUE),  NULL, NULL, array('style' => 'margin-top:5px;margin-bottom:15px;', 'ef_icon' => 'img/16/wooden-box.png', 'title' => 'Добавяне на нов материал')), 'planning_DirectProductNoteDetails');
    	}
    	
    	// Рендираме таблицата с отпадъците
    	if(count($data->popArr) || $data->masterData->rec->state == 'draft'){
    		$data->listFields['productId'] = "Отпадъци|* <small style='font-weight:normal'>( |остават в незавършеното производство|* )</small>";
    		$detailsPop = $table->get($data->popArr, $data->listFields);
    		$detailsPop = ht::createElement("div", array('style' => 'margin-top:5px;margin-bottom:5px'), $detailsPop);
    		$tpl->append($detailsPop, 'planning_DirectProductNoteDetails');
    	}
    	
    	// Добавяне на бутон за нов отпадък
    	if($this->haveRightFor('add', (object)array('noteId' => $data->masterId, 'type' => 'pop'))){
    		$tpl->append(ht::createBtn('Отпадък', array($this, 'add', 'noteId' => $data->masterId, 'type' => 'pop', 'ret_url' => TRUE),  NULL, NULL, array('style' => 'margin-top:5px;;margin-bottom:10px;', 'ef_icon' => 'img/16/wooden-box.png', 'title' => 'Добавяне на нов отпадък')), 'planning_DirectProductNoteDetails');
    	}
    	
    	// Връщаме шаблона
    	return $tpl;
    }
}