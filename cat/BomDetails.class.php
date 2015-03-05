<?php



/**
 * Мениджър на етапи детайл на технологична рецепта, всеки детайл също може да има детайл
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_BomDetails extends doc_Detail
{
	
	
    /**
     * Заглавие
     */
    var $title = "Етапи на технологичните рецепти";
    
    
    /**
     * Заглавие
     */
    var $singleTitle = "Етап";
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'bomId';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, cat_Wrapper, plg_GroupByField, plg_AlignDecimals2';
    
    
    /**
     * По кое поле да се групират записите
     */
    var $groupByField = 'stageId';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Активен таб
     */
    var $currentTab = 'Рецепти';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,cat';
    
    
    /**
     * Кой има право да чете?
     */
    var $canSingle = 'no_one';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,cat';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,cat';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,cat';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, stageId, resourceId, measureId=Мярка, baseQuantity=Начално,propQuantity=Пропорц.';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('bomId', 'key(mvc=cat_Boms)', 'column=none,input=hidden,silent');
    	$this->FLD('stageId', 'key(mvc=mp_Stages,allowEmpty,select=name)', 'caption=Етап');
    	$this->FLD("resourceId", 'key(mvc=mp_Resources,select=title,allowEmpty)', 'caption=Ресурс,mandatory,silent', array('attr' => array('onchange' => 'addCmdRefresh(this.form);this.form.submit();')));
    	$this->FLD("productId", 'key(mvc=cat_Products, select=name, allowEmpty)', 'caption=Отпадък,input=none');
    	
    	$this->FLD("baseQuantity", 'double', 'caption=Количество->Начално,hint=Начално количество');
    	$this->FLD("propQuantity", 'double', 'caption=Количество->Пропорционално,hint=Пропорционално количество');
    	$this->FLD('type', 'enum(input=Добавяне,pop=Изкарване)', 'column=none,input=hidden,silent');
    	 
    	$this->setDbUnique('bomId,resourceId');
    }
    
    
    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    protected static function on_AfterPrepareListFields($mvc, $data)
    {
    	$data->listFields['resourceId'] = ' ';
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
    	 
    	// Ако добавяме нов изходен ресурс
    	if ($form->rec->type == 'pop'){
    		$form->setField('resourceId', 'input=none');
    		$form->setField('productId', 'mandatory,input');
    		$form->setField('baseQuantity', 'mandatory,caption=К-во');
    		$form->setField('propQuantity', 'input=none');
    		
    		$products = cat_Products::getByProperty('waste');
    		if(count($products)){
    			$form->setOptions('productId', $products);
    		} else {
    			return Redirect(array('cat_Boms', 'single', $masterRec->bomId), NULL, 'Няма наличини отпадни артикули');
    		}
    	} else {
    		$quantity = $data->masterRec->quantity;
    		$originInfo = cat_Products::getProductInfo($data->masterRec->productId);
    		$shortUom = cat_UoM::getShortName($originInfo->productRec->measureId);
    		
    		$propCaption = "|За|* |{$quantity}|* {$shortUom}";
    		$form->setField('propQuantity', "caption={$propCaption}");
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
    	$rec = &$form->rec;
    	
    	// Ако има избран ресурс, добавяме му мярката до полетата за количества
    	if(isset($rec->resourceId)){
    		if($uomId = mp_Resources::fetchField($rec->resourceId, 'measureId')){
    			$uomName = cat_UoM::getShortName($uomId);
    	
    			$form->setField('baseQuantity', "unit={$uomName}");
    			$form->setField('propQuantity', "unit={$uomName}");
    		}
    	}
    	
    	// Проверяваме дали е въведено поне едно количество
    	if($form->isSubmitted()){
    	
    		// Не може и двете количества да са празни
    		if(empty($rec->baseQuantity) && empty($rec->propQuantity)){
    			$form->setError('baseQuantity,propQuantity', 'Трябва да е въведено поне едно количество');
    		}
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	if($rec->productId){
    		$row->resourceId = cat_Products::getHyperlink($rec->productId, TRUE);
    		$measureId = cat_Products::getProductInfo($rec->productId)->productRec->measureId;
    	} else {
    		$row->resourceId = mp_Resources::getHyperlink($rec->resourceId, TRUE);
    		$measureId = mp_Resources::fetchField($rec->resourceId, 'measureId');
    	}
    	$row->measureId = cat_UoM::getTitleById($measureId);
    	
    	$row->ROW_ATTR['class'] = ($rec->type != 'input') ? 'row-removed' : 'row-added';
    	$row->ROW_ATTR['title'] = ($rec->type != 'input') ? tr('Отпадъчен артикул') : NULL;
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	$data->toolbar->removeBtn('btnAdd');
    	if($mvc->haveRightFor('add', (object)array('bomId' => $data->masterId))){
    		$data->toolbar->addBtn('Ресурс', array($mvc, 'add', 'bomId' => $data->masterId, 'type' => 'input', 'ret_url' => TRUE), NULL, "title=Добавяне на нов входящ ресурс,ef_icon=img/16/page_white_text.png");
    		$data->toolbar->addBtn('Отпадък', array($mvc, 'add', 'bomId' => $data->masterId, 'type' => 'pop', 'ret_url' => TRUE), NULL, "title=Добавяне на нов отпаден артикул,ef_icon=img/16/wooden-box.png");
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'edit' || $action == 'delete' || $action == 'add') && isset($rec)){
    		if($mvc->Master->fetchField($rec->{$mvc->masterKey}, 'state') != 'draft'){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
    /**
     * След извличане на записите от базата данни
     */
    public static function on_AfterPrepareListRecs(core_Mvc $mvc, $data)
    {
    	if(!count($data->recs)) return;
    	 
    	$recs = &$data->recs;
    	 
    	foreach ($recs as &$rec){
    		if($rec->stageId){
    			$rec->order = mp_Stages::fetchField($rec->stageId, 'order');
    		} else {
    			$rec->order = 0;
    		}
    	}
    	 
    	// Сортираме по подредбата на производствения етап
    	usort($recs, function($a, $b) {
    		if($a->order == $b->order)  return 0;
    
    		return ($a->order > $b->order) ? 1 : -1;
    	});
    }
}