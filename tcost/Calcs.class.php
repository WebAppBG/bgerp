<?php



/**
 * Модел за кеширани изчислени транспортни цени
 *
 *
 * @category  bgerp
 * @package   tcost
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class tcost_Calcs extends core_Manager
{


	/**
	 * Масив за мапване на стойностите от мениджърите
	 */
	private static $map = array(
			'masterMvc' 	     => 'sales_Sales',
			'deliveryTermId'     => 'deliveryTermId',
			'contragentClassId'  => 'contragentClassId',
			'contragentId'       => 'contragentId',
			'productId' 	     => 'productId',
			'packagingId' 	     => 'packagingId',
			'deliveryLocationId' => 'deliveryLocationId',
			'valior'             => 'valior',
			'quantity'           => 'quantity',
			'price' 	         => 'price',
			'packPrice'          => 'packPrice',
			'chargeVat'	         => 'chargeVat',
			'currencyRate'	     => 'currencyRate',
			'currencyId'         => 'currencyId',
	);
	
	
	/**
     * Заглавие
     */
    public $title = "Изчислен транспорт";


    /**
     * Плъгини за зареждане
     */
    public $loadList = "tcost_Wrapper";


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';


    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';


    /**
     * Полета, които се виждат
     */
    public $listFields  = "docId,recId,fee";
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('docClassId', 'class(interface=doc_DocumentIntf)', 'mandatory,caption=Вид на документа');
    	$this->FLD('docId', 'int', 'mandatory,caption=Ид на документа');
    	$this->FLD('recId', 'int', 'mandatory,caption=Ид на реда');
    	$this->FLD('fee', 'double', 'mandatory,caption=Сума на транспорта');
    	
    	$this->setDbUnique('docClassId,docId,recId');
    	$this->setDbIndex('docClassId,docId');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$row->docId = cls::get($rec->docClassId)->getLink($rec->docId, 0);
    }
    
    
    /**
     * Връща информация за цената на транспорта, към клиент
     * 
     * @param int $deliveryTermId  - условие на доставка
     * @param int $productId       - ид на артикул
     * @param int $packagingId     - ид на опаковка
     * @param double $quantity     - к-во
     * @param double $totalWeight  - общо тегло   
     * @param int $toCountryId     - ид на държава
     * @param varchar $toPcodeId   - пощенски код
     * @return FALSE|array $res         - информация за цената на транспорта или NULL, ако няма
     * 					['totalFee']  - обща сума на целия транспорт, в основна валута без ДДС
     * 					['singleFee'] - цената от транспорта за 1-ца от артикула, в основна валута без ДДС
     */
    public static function getTransportCost($deliveryTermId, $productId, $packagingId, $quantity, $totalWeight, $toCountryId, $toPcodeId)
    {
    	// Имали в условието на доставка, драйвер за изчисляване на цени?
    	$TransportCostDriver = cond_DeliveryTerms::getCostDriver($deliveryTermId);
    	if(!is_object($TransportCostDriver)) return FALSE;
    	
    	$ourCompany = crm_Companies::fetchOurCompany();	 
    	$totalFee = $TransportCostDriver->getTransportFee($deliveryTermId, $productId, $packagingId, $quantity, $totalWeight, $toCountryId, $toPcodeId, $ourCompany->country, $ourCompany->pCode);
    	
    	$res = array('totalFee' => $totalFee);
    	
    	if($totalFee != tcost_CostCalcIntf::CALC_ERROR){
    		$res['singleFee'] = round($totalFee / $quantity, 2);
    	}
    	
    	return $res;
    }
    
    
    /**
     * Връща начисления транспорт към даден документ
     * 
     * @param mixed $docClassId - ид на клас на документ
     * @param int $docId        - ид на документ
     * @param int $recId        - ид на ред на документ
     * @return stdClass|NULL    - записа, или NULL ако няма
     */
    public static function get($docClassId, $docId, $recId)
    {
    	$docClassId = cls::get($docClassId)->getClassId();
    	$rec = self::fetch("#docClassId = {$docClassId} AND #docId = {$docId} AND #recId = '{$recId}'");
    	
    	return (is_object($rec)) ? $rec : NULL;
    }
    
    
    /**
     * Синхронизира сумата на скрития транспорт на един ред на документ
     * 
     * @param mixed $docClassId - ид на клас на документ
     * @param int $docId        - ид на документ
     * @param int $recId        - ид на ред на документ
     * @param double $fee       - начисления скрит транспорт
     * @return void
     */
    public static function sync($docClass, $docId, $recId, $fee)
    {
    	// Клас ид
    	$classId = cls::get($docClass)->getClassId();
    	
    	// Проверка имали запис за ъпдейт
    	$exRec = self::get($classId, $docId, $recId);
    	
    	// Ако подадената сума е NULL, и има съществуващ запис - трие се
    	if(is_null($fee) && is_object($exRec)){
    		self::delete($exRec->id);
    	}
    	
    	// Ако има сума
    	if(isset($fee)){
    		$fields = NULL;
    		
    		// И няма съществуващ запис, ще се добавя нов
    		if(!$exRec){
    			$exRec = (object)array('docClassId' => $classId, 'docId' => $docId, 'recId' => $recId);
    		} else {
    			$fields = 'fee';
    		}
    		 
    		// Ъпдейт / Добавяне на записа
    		$exRec->fee = $fee;
    		self::save($exRec);
    	}
    }
    
    
    /**
     * След подготовка на туклбара на списъчния изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(haveRole('debug')){
    		$data->toolbar->addBtn('Изчистване', array($mvc, 'truncate'), 'warning=Искатели да изчистите таблицата,ef_icon=img/16/sport_shuttlecock.png');
    	}
    }
    
    
    /**
     * Изчиства записите в балансите
     */
    public function act_Truncate()
    {
    	requireRole('debug');
    		
    	// Изчистваne записите от моделите
    	self::truncate();
    		
    	$this->logWrite("Изтриване на кеша на транспортните суми");
    
    	return new Redirect(array($this, 'list'), '|Записите са изчистени успешно');
    }
    
    
    /**
     * Помощна ф-я връщаща п. кода и държава от подадени данни
     * 
     * @param mixed $contragentClassId - клас на контрагента
     * @param int $contragentId        - ид на контрагента
     * @param string|NULL $pCode       - пощенски код
     * @param int|NULL $countryId      - ид на държава
     * @param int|NULL $locationId     - ид на локация
     * @return array $res
     * 				['pCode']     - пощенски код
     * 				['countryId'] - ид на държава
     */
    public static function getCodeAndCountryId($contragentClassId, $contragentId, $pCode = NULL, $countryId = NULL, $locationId = NULL)
    {
    	$cData = cls::get($contragentClassId)->getContragentData($contragentId);
    	
    	// Ако има локация, адресните данни са приоритетни от там
    	if(isset($locationId)){
    		$locationRec = crm_Locations::fetch($locationId);
    		$locationCountryId = (isset($locationRec->countryId)) ? $locationRec->countryId : $cData->countryId;
    		if(isset($locationCountryId) && !empty($locationRec->pCode)){
    			return array('pCode' => $locationRec->pCode, 'countryId' => $locationCountryId);
    		}
    		
    		if(isset($locationRec->countryId)) {
    			return array('pCode' => NULL, 'countryId' => $locationRec->countryId);
    		}
    	}
    	
    	// Ако има от документа данни, взимат се тях
    	$cId = isset($countryId) ? $countryId : $cData->countryId;
    	if(isset($cId) && !empty($pCode)){
    		return array('pCode' => $pCode, 'countryId' => $cId);
    	}
    	
    	if(isset($countryId)){
    		return array('pCode' => NULL, 'countryId' => $countryId);
    	}
    	
    	// В краен случай се връщат адресните данни на визитката
    	return array('pCode' => $cData->pCode, 'countryId' => $cData->countryId);
    }
    
    
    /**
     * Колко е начисления скрит танспорт за документа
     * 
     * @param mixed $docClass - клас на документа
     * @param int $docId      - ид на документа
     * @return double $count  - общо начислени разходи
     */
    public static function calcInDocument($docClass, $docId)
    {
    	$count = 0;
    	$classId = cls::get($docClass)->getClassId();
    	$feeErr = tcost_CostCalcIntf::CALC_ERROR;
    	
    	$query = self::getQuery();
    	$query->where("#docClassId = {$classId} AND #docId = {$docId}");
    	$query->XPR('sum', 'double', 'sum(#fee)');
    	$query->where("#fee != {$feeErr}");
    	
    	if($rec = $query->fetch()){
    		$count = $rec->sum;
    	}
    	
    	return $count;
    }
    
    
    /**
     * Показване на хинт при изчисление на цена
     * 
     * @param varchar $amountRow           - вербалната сума на реда
     * @param double $amountFee            - вербалната транспортна такса
     * @param double $vat                  - процент ДДС
     * @param double $currencyRate         - валутен курс
     * @param varchar $chargeVat           - режим на ДДС
     * @return core_ET|varchar $amountRow  - сумата на реда с хинт
     */
    public static function getAmountHint($amountRow, $amountFee, $vat, $currencyRate, $chargeVat)
    {
    	if($amountFee == tcost_CostCalcIntf::CALC_ERROR){
    		
    		return ht::createHint($amountRow, 'Скритият транспорт, не може да бъде изчислен', 'warning', FALSE);
    	} elseif(isset($amountFee)){
    		$amountFee = deals_Helper::getDisplayPrice($amountFee, $vat, $currencyRate, $chargeVat);
    		$amountFee = cls::get('type_Double', array('params' => array('decimals' => 2)))->toVerbal($amountFee);
    		$hint = tr("Транспорт|*: {$amountFee}");
    		
    		return ht::createHint($amountRow, $hint, 'notice', FALSE, 'width=14px,height=14px');
    	}
    	
    	return $amountRow;
    }
    
    
    /**
     * Сумата на видимия транспорт в документа
     * 
     * @param core_Query $query       - заявка
     * @param string $productFld      - име на полето на артикула
     * @param string $amountFld       - име на полето на сумата
     * @param string $packPriceFld    - име на полето на цената на опаковката
     * @param string $packQuantityFld - име на полето на количеството на опаковката
     * @param string $discountFld     - име на полето за отстъпката
     * @return double $amount         - сума на видимия транспорт в основна валута без ДДС
     */
    public static function getVisibleTransportCost(core_Query $query, $productFld = 'productId', $amountFld = 'amount', $packPriceFld = 'packPrice', $packQuantityFld = 'packQuantity', $discountFld = 'discount')
    {
    	$amount = 0;
    	
    	// Ще се гледат само отбелязаните артикули, като транспорт
    	$transportArr = keylist::toArray(tcost_Setup::get('TRANSPORT_PRODUCTS_ID'));
    	$query->in($productFld, $transportArr);
    	
    	// За всеки намерен, сумата му се събира
    	while($dRec = $query->fetch()){
    		
    		// Ако няма поле за сума, тя се взима от к-то по цената
    		$amountRec = isset($dRec->{$amountFld}) ? $dRec->{$amountFld} : $dRec->{$packPriceFld} * $dRec->{$packQuantityFld};
    		if(isset($dRec->{$discountFld})){
    			$amountRec = $amountRec * (1 - $dRec->{$discountFld});
    		}
    		
    		$amount += $amountRec;
    	}
    	
    	// Връщане на намерената сума
    	return $amount;
    }
    
    
    /**
     * Връща общото тегло на масив с артикули
     * 
     * @param array $products - масив с артикули
     * @param tcost_CostCalcIntf $TransportCalc - интерфейс за изчисляване на транспортна цена
     * @param string $productFld - поле съдържащо ид-то на артикул
     * @param string $quantityFld - поле съдържащо количеството на артикул
     * @return double $totalWeight - общото тегло
     */
    public static function getTotalWeight($products,tcost_CostCalcIntf $TransportCalc, $productFld = 'productId', $quantityFld = 'quantity')
    {
    	$totalWeight = 0;
    	if(!is_array($products)) return $totalWeight;
    	
    	// За всеки артикул в масива
    	foreach ($products as $p1){
    		
    		// Намира се обемното му тегло и се съжура
    		$singleWeight = cat_Products::getWeight($p1->productId, $p1->packagingId);
    		$singleVolume = cat_Products::getVolume($p1->productId, $p1->packagingId);
    		$singleWeight = $TransportCalc->getVolumicWeight($singleWeight, $singleVolume);
    		$totalWeight += $singleWeight * $p1->{$quantityFld};
    	}
    	
    	// Връщане на общото тегло
    	return $totalWeight;
    }
    
    
    /**
     * Помощна функция връщаща сумата на реда
     * Използва се след инпута на формата на документите
     * 
     * @param int $deliveryTermId          - ид на условие на доставка
     * @param mixed $contragentClassId     - клас на контрагента
     * @param int $contragentId            - ид на контрагента
     * @param int $productId               - ид на артикула
     * @param int $packagingId             - ид на опаковка
     * @param double $quantity             - количество
     * @param int|NULL $deliveryLocationId - ид на локация
     * @return NULL|array $feeArray        - сумата на транспорта
     */
    public static function getCostArray($deliveryTermId, $contragentClassId, $contragentId, $productId, $packagingId, $quantity, $deliveryLocationId)
    {
    	// Ако може да се изчислява скрит транспорт
    	if(!cond_DeliveryTerms::canCalcHiddenCost($deliveryTermId, $productId)) return NULL;
    	
    	// Пощенския код и ид-то на държавата
    	$codeAndCountryArr = tcost_Calcs::getCodeAndCountryId($contragentClassId, $contragentId, NULL, NULL, $deliveryLocationId);
    	 
    	// Опит за изчисляване на транспорт
    	$totalWeight = cond_Parameters::getParameter($contragentClassId, $contragentId, 'calcShippingWeight');
    	$feeArr = tcost_Calcs::getTransportCost($deliveryTermId, $productId, $packagingId, $quantity, $totalWeight, $codeAndCountryArr['countryId'], $codeAndCountryArr['pCode']);
    
    	return $feeArr;
    }
    
    
    /**
     * Връща вербалното показване на транспортните цени
     * 
     * @param stdClass $row                 - вербалното представяне на реда
     * @param double $leftTransportCost     - остатъка за транспорт
     * @param double $hiddenTransportCost   - скрития транспорт
     * @param double $expectedTransportCost - очаквания транспорт
     * @param double $visibleTransportCost  - явния транспорт
     */
    public static function getVerbalTransportCost(&$row, &$leftTransportCost, $hiddenTransportCost, $expectedTransportCost, $visibleTransportCost)
    {
    	$Double = cls::get('type_Double', array('params' => array('decimals' => 2)));
    	foreach (array('hiddenTransportCost', 'expectedTransportCost', 'visibleTransportCost')  as $fld){
    		$row->{$fld} = $Double->toVerbal(${$fld});
    		if(${$fld} == 0){
    			$row->{$fld} = "<span class='quiet'>{$row->{$fld}}</span>";
    		}
    	}
    	
    	$leftTransportCost = $expectedTransportCost - $hiddenTransportCost - $visibleTransportCost;
    	$row->leftTransportCost = $Double->toVerbal($leftTransportCost);
    	$leftTransportCost = round($leftTransportCost, 2);
    	$class = ($leftTransportCost > 0) ? 'green' : (($leftTransportCost < 0) ? 'red' : 'quiet');
    	
    	$row->leftTransportCost = "<span class='{$class}'>{$row->leftTransportCost}</span>";
    }
    
    
    /**
     * Помощна функция за подотовка на цената на транспорта
     * 
     * @param stdClass $rec       - запис
     * @param core_Form $form     - форма
     * @param stdClass $masterRec - мастър запис
     * @param array $map          - масив за мапване на полетата
     * @return void
     */
    public static function prepareFee(&$rec, &$form, $masterRec, $map = array())
    {
    	$map = array_merge(self::$map, $map);
    	
    	// Имали вече начислен транспорт
    	$rec->fee = tcost_Calcs::get($map['masterMvc'], $masterRec->id, $rec->id)->fee;
    	
    	// Колко е очаквания транспорт
    	$feeArr = tcost_Calcs::getCostArray($masterRec->{$map['deliveryTermId']}, $masterRec->{$map['contragentClassId']}, $masterRec->{$map['contragentId']}, $rec->{$map['productId']}, $rec->{$map['packagingId']}, $rec->{$map['quantity']}, $masterRec->{$map['deliveryLocationId']});
    	
    	// Ако има такъв към цената се добавя
    	if(is_array($feeArr)){
    		if($rec->autoPrice === TRUE){
    			if(isset($feeArr['singleFee'])){
    				$rec->{$map['price']} += $feeArr['singleFee'];
    			}
    		}
    		$rec->fee = $feeArr['totalFee'];
    	}
    	
    	if($rec->autoPrice !== TRUE){
    		// Проверка дали цената е допустима спрямо сумата на транспорта
    		$amount = round($rec->{$map['price']} * $rec->{$map['quantity']}, 2);
    		 
    		if($amount <= round($rec->fee, 2)){
    			$fee = cls::get('type_Double', array('params' => array('decimals' => 2)))->toVerbal($rec->fee / $masterRec->{$map['currencyRate']});
    			$form->setError('packPrice', "Сумата на артикула без ДДС е по-малка от сумата на скрития транспорт|* <b>{$fee}</b> {$masterRec->{$map['currencyId']}}, |без ДДС|*");
    			$vat = cat_Products::getVat($rec->{$map['productId']}, $masterRec->{$map['valior']});
    			$rec->{$map['packPrice']} = deals_Helper::getDisplayPrice($rec->{$map['packPrice']}, $vat, $masterRec->{$map['currencyRate']}, $masterRec->{$map['chargeVat']});
    		}
    	}
    	
    	// Ако има сума ще се синхронизира
    	if(isset($rec->fee)){
    		$rec->syncFee = TRUE;
    	}
    }
}