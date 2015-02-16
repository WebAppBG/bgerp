<?php

/**
 * Клас 'cat_products_Vat' 
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class cat_products_VatGroups extends cat_products_Detail
{
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'productId';
    
    
    /**
     * Заглавие
     */
    public $title = 'ДДС групи';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'vatGroup,vatPercent=ДДС (%),validFrom,tools=Пулт';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'cat_Wrapper, plg_Created, plg_Modified, plg_RowTools';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Кой може да качва файлове
     */
    public $canAdd = 'ceo,cat';
    
    
    /**
     * Кой може да качва файлове
     */
    public $canDelete = 'ceo,cat';
    
    
    /**
     * Заглавие в единствено число
     *
     * @var string
     */
    public $singleTitle = 'ДДС група';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'input=hidden,silent');
        $this->FLD('vatGroup', 'key(mvc=acc_VatGroups,select=title)', 'caption=Група');
        $this->FLD('validFrom', 'datetime', 'caption=В сила oт');
    }

    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()) {
    		$now = dt::verbal2mysql();
    
    		if(!$form->rec->validFrom) {
    			$form->rec->validFrom = $now;
    		}
    
    		if($form->rec->validFrom < $now) {
    			$form->setError('validFrom', 'Групата не може да се сменя с минала дата');
    		}
    	}
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
    	$row->vatGroup = acc_VatGroups::getTitleById($rec->vatGroup);
    	$row->vatPercent = acc_VatGroups::getVerbal($rec->vatGroup, 'vat');
    }
    
    
    /**
     * Извиква се след подготовка на заявката за детайла
     */
    public static function on_AfterPrepareDetailQuery(core_Detail $mvc, $data)
    {
    	// Историята на ценовите групи на продукта - в обратно хронологичен ред.
    	$data->query->orderBy("validFrom,id", 'DESC');
    }
    
    
    /**
     * След подготовка на записите във вербален вид
     */
    public static function on_AfterPrepareListRows(core_Detail $mvc, $data)
    {
    	if (!$data->rows) return;
    	
    	$now  = dt::now(TRUE);
    	$currentGroup = NULL;
    	
    	foreach ($data->rows as $id => &$row) {
    		$rec = $data->recs[$id];
    		if($rec->validFrom > $now){
    			$data->rows[$id]->ROW_ATTR['class'] = 'state-draft';
    		}elseif($rec->validFrom <= $now && is_null($currentGroup)){
    			$currentGroup = $rec->validFrom;
    			$data->rows[$id]->ROW_ATTR['class'] = 'state-active';
    		} else {
    			$data->rows[$id]->ROW_ATTR['class'] = 'state-closed';
    		}
    	}
    }
    
    
    /**
     * Подготовка на файловете
     */
    public static function prepareVatGroups($data)
    {   
        $data->TabCaption = 'ДДС';
        $data->TabPlaceholder = 'MAIN_DETAILS';
        
        $data->Order = 60;

        static::prepareDetail($data);
    }
    
    
    /**
     * Рендиране на файловете
     */
    public static function renderVatGroups($data)
    {
        return static::renderDetail($data);
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles(core_Mvc $mvc, &$requiredRoles, $action, $rec)
    {
    	if(($action == 'edit' || $action == 'delete') && isset($rec)){
    		if($rec->validFrom <= dt::now()){
    			$requiredRoles = 'no_one';
    		}
    	}
    	
    	if($action == 'add' && isset($rec->productId)){
    		if($mvc->Master->fetchField($rec->productId, 'state') != 'active'){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Коя е активната данъчна група
     */
    public static function getCurrentGroup($productId)
    {
    	$query = cat_products_VatGroups::getQuery();
    	$query->where("#productId = {$productId}");
    	$query->where("#validFrom <= NOW()");
    	$query->orderBy("#validFrom", "DESC");
    	
    	if($rec = $query->fetch()){
    		
    		return acc_VatGroups::fetch($rec->vatGroup);
    	}
    	
    	return FALSE;
    }
}