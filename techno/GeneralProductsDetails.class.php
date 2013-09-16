<?php



/**
 * Детайли на универсалните продукти
 *
 * @category  bgerp
 * @package   techo
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class techno_GeneralProductsDetails extends core_Detail {
    
    
    /**
     * Заглавие
     */
    var $title = 'Компоненти';
    
    
    /**
     * Единично заглавие
     */
    var $singleTitle = 'Компонент';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'techno_Wrapper,plg_RowTools, plg_Sorting, plg_SaveAndNew, plg_AlignDecimals,plg_RowNumbering';
    
  
    /**
	 * Мастър ключ към универсалния продукти
	 */
	var $masterKey = 'generalProductId';
    
    
    /**
	 *  Брой елементи на страница 
	 */
	var $listItemsPerPage = "20";
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'RowNumb';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'techno, ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'no_one';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'techno, ceo';
    
    
    /**
     * Кой таб да бъде отворен
     */
    var $currentTab = 'Универсални продукти';
	
    
    /**
     * Полета за списъчния изглед
     */
    var $listFields = 'RowNumb=Пулт, componentId, cQuantity, price, amount, bTaxes';
    
    
    /**
     * Продуктите от кои групи могат да са компоненти
     */
    static $allowedGroups = 'prefabrications';
    
    
     /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('generalProductId', 'key(mvc=techno_GeneralProducts)', 'caption=Продукт,input=hidden');
    	$this->FLD('componentId', 'varchar(255)', 'caption=Продукт,mandatory');
    	$this->FLD('cQuantity', 'double', 'caption=К-во');
    	$this->FLD('price', 'double(decimals=2)', 'caption=Цена,');
    	$this->FLD('amount', 'double(decimals=2)', 'caption=Сума,input=hidden');
    	$this->FLD('cMeasureId', 'key(mvc=cat_UoM,select=shortName)', 'caption=Мярка,input=none');
    	$this->FLD('bTaxes', 'double(decimals=2)', 'caption=Такса');
    	$this->FLD('vat', 'percent', 'caption=ДДС,input=hidden');
    }
    
    
	/**
     * Преди показване на форма за добавяне/промяна.
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec = &$form->rec;
    	$products = array('-1' => tr('Основа')) + cat_Products::getByGroup(static::$allowedGroups);
    	
    	if(empty($form->rec->id)){
    		$products = static::getRemainingOptions($rec->generalProductId, $products);
    		expect(count($products));
    		$form->setOptions('componentId', $products);
    		$data->remainingProducts = count($products) - 1;
    	} else {
    		if($rec->componentId == -1){
    			$rec->price = $rec->amount;
    		}
    		$products = array($rec->componentId => $products[$rec->componentId]);
    	}
    	
    	$form->fields['componentId']->type = cls::get("type_Enum", array('options' => $products));
    }
    
    
     /**
     * Подготовка на бутоните на формата за добавяне/редактиране.
     */
    function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
    	if (empty($data->form->rec->id)) {
    		if(!$data->remainingProducts){
    			$data->form->toolbar->removeBtn('Запис и Нов');
    		}
    	}
    }
    
    
	/**
     * Извиква се след въвеждането на данните от Request във формата
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
    		$rec = &$form->rec;
    		
    		if($rec->componentId != -1){
		        if(!$rec->cQuantity){
			        $form->setError('cQuantity', 'Моля задайте количество');
			    }
		        $rec->cMeasureId = cat_Products::fetchField($rec->componentId, 'measureId');
	        	$rec->vat = cat_Products::getVat($rec->componentId);
	        	if(!$rec->bTaxes){
	        		$rec->bTaxes = cat_products_Params::fetchParamValue($rec->componentId, 'bTax');
	        	}
        	} else {
        		$rec->cQuantity = 1;
        	}
        	
        	if(!$rec->price){
        		$folderId = $mvc->Master->fetchField($rec->generalProductId, 'folderId');
        		$Policy = cls::get('price_ListToCustomers');
		        $contClass = doc_Folders::fetchCoverClassId($folderId);
			    $contId = doc_Folders::fetchCoverId($folderId);
			    $rec->price = $Policy->getPriceInfo($contClass, $contId, $rec->componentId, NULL, $rec->cQuantity, dt::now())->price;
			    if(!$rec->price){
			        $form->setError('price', 'Проблем при извличането на цената! Моля задайте ръчно');
			    }
	        }
	        
	        $rec->amount = $rec->cQuantity * $rec->price;
	        if($rec->componentId == -1){
	        	unset($rec->cQuantity, $rec->cPrice);
	        }
    	}
    }
    
    
    /**
     * Помощен метод за показване само на тези компоненти, които
     * не са добавени към спецификацията
     */
    public static function getRemainingOptions($generalProductId, $products = NULL)
    {
    	if(empty($products)){
    		$products = array('-1' => tr('Основа')) + cat_Products::getByGroup(static::$allowedGroups);
    	}
    	$query = static::getQuery();
    	$query->where("#generalProductId = {$generalProductId}");
    	$query->show('componentId');
    	while($rec = $query->fetch()){
    		if(isset($products[$rec->componentId])){
    			unset($products[$rec->componentId]);
    		}
    	}
    	
    	return $products;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$row->componentId = ($rec->componentId != -1) ? cat_Products::getTitleById($rec->componentId) : tr('Основа');
    	if(!Mode::is('text', 'xhtml') && !Mode::is('printing') && $rec->componentId != '-1'){
    		$row->componentId = ht::createLinkRef($row->componentId, array('cat_Products', 'single', $rec->componentId), NULL, 'title=Към компонента');
    	}
    }
    
    
    /**
     * Променяме рендирането на детайлите
     */
    function renderDetail_($data)
    {
    	$cTpl = getTplFromFile('techno/tpl/GeneralProductsDetails.shtml');
    	$tpl = $cTpl->getBlock('LONG');
    	if($data->rows){
    		foreach ($data->rows as $row){
    			$cloneTpl = clone $tpl->getBlock('COMPONENT');
    			$cloneTpl->placeObject($row);
    			$cloneTpl->removeBlocks();
    			$cloneTpl->append2master();
    		}
    		
    		if($data->total){
	    		$tpl->placeObject($data->total);
	    	}
    	}
    	
    	$tpl->replace($this->renderListToolbar($data), 'ListToolbar');
    	return $tpl;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    static function on_AfterPrepareListRows($mvc, &$data)
    {	
        if(count($data->recs)){
        	$total = $taxes = 0;
        	foreach ($data->recs as $rec){
        		$total += $rec->amount;
        		$taxes += $rec->bTaxes;
        	}
        	
        	$Double = cls::get('type_Double');
	    	$Double->params['decimals'] = 2;
	    	$data->total = (object)array('totalAmount' => $Double->toVerbal($total), 'totalTaxes' => ($taxes) ? $Double->toVerbal($taxes) : NULL);
    		$cCode = acc_Periods::getBaseCurrencyCode($data->masterData->rec->modifiedOn);
	    	$data->total->currencyId = $cCode;
    		if($taxes){
	    		$data->total->taxCurrencyId = $cCode;
	    	}
        }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(isset($data->toolbar->buttons['btnAdd']) && count($mvc->getRemainingOptions($data->masterData->rec->id))){
    		$data->toolbar->buttons['btnAdd']->title = tr("Нов компонент");
    	} else {
    		$data->toolbar->removeBtn('btnAdd');
    	}
    }
    
    
    /**
     * Връща краткото представяне на документа
     * @param int $generalProductId - ид на продукта
     */
    public function getShortLayout($generalProductId)
    {
    	$tpl = getTplFromFile('techno/tpl/GeneralProductsDetails.shtml')->getBlock('SHORT');
    	$query = $this->getQuery();
    	$query->where("#generalProductId = {$generalProductId}");
    	$query->where("#componentId != -1");
    	$recs = $query->fetchAll();
    	if(count($recs)){
    		foreach ($recs as $rec){
    			$row = $this->recToVerbal($rec, 'componentId,cQuantity,cMeasureId');
    			$block = clone $tpl->getBlock('COMPONENT');
    			$block->placeObject($row);
    			$block->removeBlocks();
    			$block->append2master();
    		}
    	}
    	
    	return $tpl;
    }
    
    
    /**
     * Изчислява точната цена на продукта
     * @param int $generalProductId - ид на продукта
     */
    public function getTotalPrice($generalProductId)
    {
    	$total = $taxes = 0;
    	$query = $this->getQuery();
    	$query->where("#generalProductId = {$generalProductId}");
    	while($rec = $query->fetch()){
    		$total += $rec->amount;
        	$taxes += $rec->bTaxes;
    	}
    	
    	return (object)array('price' => $total, 'tax' => $taxes);
    }
}