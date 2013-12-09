<?php



/**
 * Мениджър за "Детайли на офертите" 
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class sales_QuotationsDetails extends core_Detail {
    
    
    /**
     * Заглавие
     */
    public $title = 'Детайли на офертите';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'sales_QuotesDetails';
    
    
    /**
	 * Мастър ключ към дъските
	 */
	public $masterKey = 'quotationId';
    
    
    /**
     * Кой може да променя?
     */
    public $canAdd = 'ceo,sales';
    
    
    /**
     * Кой може да променя?
     */
    public $canDelete = 'ceo,sales';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, sales_Wrapper, plg_AlignDecimals, doc_plg_HidePrices, plg_SaveAndNew';
    
    
    /**
     * Кой може да променя?
     */
    public $canList = 'no_one';
    
	
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, quantity, price, discount, tolerance, term, optional, amount, discAmount';
    
    
    /**
     * Кой таб да бъде отворен
     */
    public $currentTab = 'Оферти';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'price,discount,amount,discAmount';
    
    
    /**
     * Помощен масив (@see price_Helper)
     */
    protected static $map = array('priceFld'      => 'price', 
    							  'quantityFld'   => 'quantity', 
    							  'valior'        => 'date', 
    							  'discAmountFld' => 'discAmountVat');
  	
  	
  	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('quotationId', 'key(mvc=sales_Quotations)', 'column=none,notNull,silent,hidden,mandatory');
    	$this->FLD('productId', 'int', 'caption=Продукт,notNull,mandatory');
        $this->FLD('classId', 'class(interface=cat_ProductAccRegIntf, select=title)', 'input=hidden,caption=Политика,silent,oldFieldName=productManId');
    	$this->FLD('quantity', 'double', 'caption=К-во,width=8em;');
    	$this->FLD('price', 'double(minDecimals=2)', 'caption=Ед. цена, input,width=8em');
        $this->FLD('discount', 'percent(maxDecimals=2)', 'caption=Отстъпка,width=8em');
        $this->FLD('tolerance', 'percent(min=0,max=1,decimals=0)', 'caption=Толеранс,width=8em;');
    	$this->FLD('term', 'time(uom=days,suggestions=1 ден|5 дни|7 дни|10 дни|15 дни|20 дни|30 дни)', 'caption=Срок,width=8em;');
    	$this->FLD('vatPercent', 'percent(min=0,max=1,decimals=2)', 'caption=ДДС,input=none');
        $this->FLD('optional', 'enum(no=Не,yes=Да)', 'caption=Опционален,maxRadio=2,columns=2,width=10em');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    static function on_AfterPrepareListRecs($mvc, $data)
    {
    	if(!count($data->recs)) return;
    	$recs = &$data->recs;
    	$rows = &$data->rows;
    	$masterRec = $data->masterData->rec;
    	$notOptional = $optional = array();
    	$total = new stdClass();
    	$total->discAmount = 0;
    	
    	foreach ($recs as $id => $rec){
    		if($rec->optional == 'no'){
    			$notOptional[$id] = $rec;
    		}  else {
    			$optional[$id] = $rec;
    		}
    	}
    	
    	// Подготовка за показване на задължителнтие продукти
    	price_Helper::fillRecs($notOptional, $masterRec, static::$map);
    	
    	if(empty($data->noTotal)){
    		
    		// Запомня се стойноста и ддс-то само на опционалните продукти
    		$data->summary = price_Helper::prepareSummary($masterRec->_total, $masterRec->date, $masterRec->currencyRate, $masterRec->currencyId, $masterRec->chargeVat);
    	}
    	
    	// Подготовка за показване на опционалните продукти
    	price_Helper::fillRecs($optional, $masterRec, static::$map);
    	$recs = $notOptional + $optional;
    	
    	// Изчисляване на цената с отстъпка
    	foreach($recs as $id => $rec){
            if($rec->optional == 'no'){
    			$other = $mvc->checkUnique($recs, $rec->productId, $rec->classId, $rec->id);
            	if($other) unset($data->summary);
    		}
    	}
    }
    
    
    /**
     * Проверява дали има вариация на продукт
     */
    private function checkUnique($recs, $productId, $classId, $id, $isOptional = 'no')
    {
    	$other = array_values(array_filter($recs, function ($val) use ($productId, $classId, $id, $isOptional) {
           				if($val->optional == $isOptional && $val->productId == $productId && $val->classId == $classId && $val->id != $id){
            				return $val;
            			}}));
            			
        return count($other);
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
        $rec = &$form->rec;
        $masterLink = sales_Quotations::getLink($form->rec->quotationId);
        $form->title = (($rec->id) ? "Редактиране" : "Добавяне") . " на артикул в|*" . $masterLink;
       
        $masterRec = $mvc->Master->fetch($form->rec->quotationId);
        $productMan = cls::get($rec->classId);
        $products = $productMan->getProducts($masterRec->contragentClassId, $masterRec->contragentId);
    	
        if($rec->productId){
        	// При редакция единствения възможен продукт е редактируемия
	   		$productName = $products[$rec->productId];
	   		$products = array();
	   		$products[$rec->productId] = $productName;
	   }
	   
       $form->setDefault('optional', 'no');
	   $form->setOptions('productId', $products);
       
	   $form->fields['price']->unit = ($masterRec->chargeVat == 'yes') ? 'с ДДС' : 'без ДДС';
	   
	   if($form->rec->price && $masterRec->currencyRate){
       	 	if($masterRec->chargeVat == 'yes'){
       	 		($rec->vatPercent) ? $vat = $rec->vatPercent : $vat = $productMan::getVat($rec->productId, $masterRec->date);
       	 		 $rec->price = $rec->price * (1 + $vat);
       	 	}
       	 	
       		$rec->price = $rec->price / $masterRec->currencyRate;
       }
       
       // Спецификациите немогат да са опционални
       if(!$productMan instanceof cat_Products){
       		$form->setField('optional', 'input=none');
       }
    }
    
    
	/**
     * Извиква се след въвеждането на данните от Request във формата
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
	    	$rec = &$form->rec;
	    	
	    	if($sameProduct = $mvc->fetch("#quotationId = {$rec->quotationId} AND #classId = {$rec->classId} AND #productId = {$rec->productId}")){
	    		if($rec->optional == 'yes' && $sameProduct->optional == 'no' && $rec->id != $sameProduct->id){
	    			$form->setError('optional', "Неможе да добавите продукта като опционален, защото фигурира вече като задължителен!");
	    			
	    			return;
	    		} elseif($rec->optional == 'no' && $sameProduct->optional == 'yes' && $rec->id != $sameProduct->id){
	    			$form->setError('optional', "Неможе да добавите продукта като задължителен, защото фигурира вече като опционален!");
	    			
	    			return;
	    		}
	    		
	    	}
	    	
	    	$ProductMan = cls::get($rec->classId);
	    	if(!$rec->vatPercent){ 
	    		$rec->vatPercent = $ProductMan::getVat($rec->productId, $masterRec->date);
	    	}
	    	
	    	$masterRec = $mvc->Master->fetch($rec->quotationId);
	    	
    		if(!$rec->discount){
    			$rec->discount = $price->discount;
	    	}
	    	
	    	if(!$rec->price){
	    		$price = $ProductMan->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->classId, NULL, $rec->quantity, $masterRec->date);
	    		
	    		if(!$price->price){
	    			$form->setError('price', 'Проблем с изчислението на цената ! Моля задайте ръчно');
	    		}
	    		$rec->price = $price->price;
	    	} else {
	    		
	    		if($masterRec->chargeVat == 'yes'){
	    			$rec->price = $rec->price / (1 + $rec->vatPercent);
	    		}
	    		$rec->price = $rec->price * $masterRec->currencyRate;
	    	}
	    	
	    	if($rec->optional == 'no' && !$rec->quantity){
	    		$form->setError('quantity', 'Задължителния продукт неможе да е без количество!');
	    	}
    	}
    }
    
    
	/**
     * Подготовка на бутоните за добавяне на нови редове на фактурата 
     */
    public static function on_AfterPrepareListToolbar($mvc, $data)
    {
    	if (!empty($data->toolbar->buttons['btnAdd'])) {
            $productManagers = core_Classes::getOptionsByInterface('cat_ProductAccRegIntf');
            $masterRec = $data->masterData->rec;
            $addUrl = $data->toolbar->buttons['btnAdd']->url;
            
            foreach ($productManagers as $manId => $manName) {
            	$productMan = cls::get($manId);
            	$products = $productMan->getProducts($masterRec->contragentClassId, $masterRec->contragentId, $masterRec->date);
                if(!count($products)){
                	$error = "error=Няма продаваеми {$productMan->title}";
                }
                
            	$data->toolbar->addBtn($productMan->singleTitle, $addUrl + array('classId' => $manId),
                    "id=btnAdd-{$manId},{$error},order=10", 'ef_icon = img/16/shopping.png');
            	unset($error);
            }
            
            unset($data->toolbar->buttons['btnAdd']);
        }
    }
    
    
    /**
     * След подготовка на детайлите, изчислява се общата цена
     * и данните се групират
     */
    static function on_AfterPrepareDetail($mvc, $res, $data)
    {
	    // Групираме резултатите по продукти и дали са опционални или не
    	$mvc->groupResultData($data);
    }
    
    
    /**
     * Групираме резултатите спрямо продукта
     * @var stdClass $data
     */
    private function groupResultData(&$data)
    {
    	$newRows = array();
    	$dZebra = $oZebra = 'zebra0';
    	if(!$data->rows) return;
    	foreach($data->rows as $i => $row){
    		$pId = $data->recs[$i]->productId;
    		$polId = $data->recs[$i]->classId;
    		$optional = $data->recs[$i]->optional;
    		($optional == 'no') ? $zebra = &$dZebra : $zebra = &$oZebra;
    		
    		// Сездава се специален индекс на записа productId|optional, така
    		// резултатите са разделени по продукти и дали са опционални или не
    		$pId = $pId . "|{$optional}|" . $polId;
    		if(array_key_exists($pId, $newRows)){
    			
    			// Ако има вече такъв продукт, го махаме от записа
    			unset($row->productId);
    			
    			// Слагаме клас на клетките около rospan-а за улеснение на JS
    			$row->rowspanId = $newRows[$pId][0]->rowspanId;
    			$row->TR_CLASS = $newRows[$pId][0]->TR_CLASS;
    		} else {
    			// Слагаме уникален индекс на клетката с продукта
    			$prot = md5($pId.$data->masterData->rec->id);
	    		$row->rowspanId = $row->rowspanpId = "product-row{$prot}";
	    		$zebra = $row->TR_CLASS = ($zebra == 'zebra0') ? 'zebra1' :'zebra0';
    		}
    		
    		$newRows[$pId][] = $row;
    		$newRows[$pId][0]->rowspan = count($newRows[$pId]);
    	}
    	
    	// Така имаме масив в който резултатите са групирани 
    	// по продукти, и това дали са опционални или не,
    	$data->rows = $newRows;
    }
    
    
    /**
     * Променяме рендирането на детайлите
     */
    function renderDetail_($data)
    {
    	$tpl = new ET("");
    	$masterRec = $data->masterData->rec;
    	
    	// Шаблон за задължителните продукти
    	$dTpl = getTplFromFile('sales/tpl/LayoutQuoteDetails.shtml');
    	
    	// Шаблон за опционалните продукти
    	$oTpl = clone $dTpl;
    	$oCount = $dCount = 1;
    	
    	// Променливи за определяне да се скриват ли някои колони
    	$hasQuantityColOpt = FALSE;
    	if($data->rows){
	    	foreach($data->rows as $index => $arr){
	    		list($pId, $optional, $polId) = explode("|", $index);
	    		foreach($arr as $key => $row){
	    			
	    			// Взависимост дали е опционален продукта го добавяме към определения шаблон
	    			if($optional == 'no'){
	    				$rowTpl = $dTpl->getBlock('ROW');
	    				$id = &$dCount;
	    			} else {
	    				$rowTpl = $oTpl->getBlock('ROW');
	    				
	    				// Слага се 'opt' в класа на колоната да се отличава
	    				$rowTpl->replace("-opt{$masterRec->id}", 'OPT');
	    				if($row->productId){
	    					$rowTpl->replace('-opt-product', 'OPTP');
	    				}
	    				$oTpl->replace("-opt{$masterRec->id}", 'OPT');
	    				$id = &$oCount;
		    			if($hasQuantityColOpt !== TRUE && ($row->quantity)){
		    				$hasQuantityColOpt = TRUE;
		    			}
	    			}
	    			
	    			$row->index = $id++;
	    			$rowTpl->placeObject($row);
	    			$rowTpl->removeBlocks();
	    			$rowTpl->append2master();
	    		}
	    	}
    	}

    	if($summary = $data->summary){
    		$SpellNumber = cls::get('core_SpellNumber');
    		$sayWords = $SpellNumber->asCurrency($data->summary->total);
    		
    		$dTpl->replace(price_Helper::renderSummary($summary), 'SUMMARY');
    		$dTpl->replace($sayWords, 'sayWords');
    	}
    	
    	$vatRow = ($masterRec->chargeVat == 'yes') ? tr(', |с ДДС|*') : tr(', |без ДДС|*');
    	$misc = $masterRec->currencyId . $vatRow;
    	
    	$tpl->append($this->renderListToolbar($data), 'ListToolbar');
    	$dTpl->append(tr('Оферирани'), 'TITLE');
    	$dTpl->append($misc, "MISC");
    	$dTpl->removeBlocks();
    	$tpl->append($dTpl, 'MANDATORY');
    	
    	// Ако няма опционални продукти не рендираме таблицата им
    	if($oCount > 1){
    		$oTpl->append(tr('Опционални'), 'TITLE');
    		$oTpl->append($misc, "MISC");
    		$tpl->append($oTpl, 'OPTIONAL');
    	}
    	
    	if(!$hasQuantityColOpt){
    		$tpl->append(".quote-col-opt{$masterRec->id} {display:none;} .product-id-opt-product {width:65%;}", 'STYLES');
    	}
    	
    	// Закачане на JS
        jquery_Jquery::enable($tpl);
        $tpl->push('sales/js/ResizeQuoteTable.js', 'JS');
        jquery_Jquery::run($tpl, "resizeQuoteTable();");
        
    	return $tpl;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$ProductMan = cls::get($rec->classId);
        $pInfo = $ProductMan->getProductInfo($rec->productId);
    	
        $double = cls::get('type_Double');
        $double->params['decimals'] = 2;
    	$row->productId = $ProductMan->getTitleById($rec->productId, TRUE, TRUE);
    	
    	if(!Mode::is('text', 'xhtml') && !Mode::is('printing') && is_string($row->productId) && $ProductMan->haveRightFor('read', $rec->productId)){
    		$row->productId = ht::createLinkRef($row->productId, array($ProductMan, 'single', $rec->productId), NULL, 'title=Към продукта');
    	}
    	
    	if($rec->quantity){
    		$uomId = $pInfo->productRec->measureId;
    		$row->uomShort = cat_UoM::getShortName($uomId);
    	}
    	
    	if($rec->amount){
    		$row->amount = $double->toVerbal($rec->amount);
    	}
    	
    	if($rec->discount){
    		$Percent = cls::get('type_Percent');
		    $parts = explode(".", $rec->discount * 100);
		    $percent->params['decimals'] = count($parts[1]);
		    $row->discount = $Percent->toVerbal($rec->discount);
    	}
    }
    
    
    /**
     * След проверка на ролите
     */
    function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec, $userId)
    {
    	if($action == 'add' || $action == 'delete' && isset($rec)){
    		$quoteState = $mvc->Master->fetchField($rec->quotationId, 'state');
    		if($quoteState != 'draft'){
    			$res = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Ако ориджина е спецификация, вкарват се записи отговарящи
     * на посочените примерни количества в нея
     * @param stdClass $rec - запис на оферта
     * @param core_ObjectReference $origin - ид на спецификацията
     * @param array $quantities - количества подадени от заявката
     */
    public function insertFromSpecification($rec, $origin, $quantities = array())
    {
    	$docClassId = $origin->instance->getClassId();
    	$docId = $origin->that;
    
    	if(!$specRec = techno_Specifications::fetchByDoc($docClassId, $docId)){
    		$specId  = techno_Specifications::forceRec($origin->instance, $origin->fetch());
    		$specRec = techno_Specifications::fetch($specId);
    	}
    	
    	$classId = techno_Specifications::getClassId();
    	$ProductMan = cls::get($classId);
    	
    	// Изтриват се предишни записи на спецификацията в офертата
    	$this->delete("#quotationId = {$rec->id} AND #productId = {$specRec->id} AND #classId = {$classId}");
    	
    	foreach ($quantities as $q) {
    		if(empty($q)) continue;
    		
    		// Записва се нов детайл за всяко зададено к-во
    		$dRec = new stdClass();
    		$dRec->quotationId = $rec->id;
    		$dRec->productId = $specRec->id;
    		$dRec->quantity = $q;
    		$dRec->productManId = $classId;
    		$price = $ProductMan->getPriceInfo($rec->contragentClassId, $rec->contragentId, $dRec->productId, $dRec->classId, NULL, $q, $rec->date);
    		
    		$dRec->price = $price->price;
    		$dRec->optional = 'no';
    		$dRec->discount = $price->discount;
    		$dRec->vatPercent = $ProductMan->getVat($dRec->productId, $rec->date);
    		
    		$this->save($dRec);
    	}
    }
    
    
	/**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave($mvc, &$id, $rec)
    {
    	// Нотифицираме продуктовия мениджър че продукта вече е използван
    	$ProductMan = cls::get($rec->classId);
    	$productRec = $ProductMan->fetch($rec->productId);
    	$productRec->lastUsedOn = dt::now();
    	$ProductMan->save_($productRec);
    }
}