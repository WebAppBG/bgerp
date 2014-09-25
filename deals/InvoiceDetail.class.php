<?php



/**
 * Базов клас за наследяване на детайл на ф-ри
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class deals_InvoiceDetail extends core_Detail
{
	
	/**
	 * Помощен масив за мапиране на полета изпозлвани в deals_Helper
	 */
	public static $map = array( 'rateFld'     => 'rate',
								'chargeVat'   => 'vatRate',
								'quantityFld' => 'quantity',
								'valior'      => 'date',
								'alwaysHideVat' => TRUE);
	

	/**
	 * Полета свързани с цени
	 */
	public $priceFields = 'price,amount,discount';
	

	/**
	 * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
	 */
	public $rowToolsField = 'RowNumb';
	
	
	/**
	 * Полета, които ще се показват в листов изглед
	 */
	public $listFields = 'productId, packagingId, quantity, packPrice, discount, amount';


	/**
	 * Извиква се след описанието на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function setInvoiceDetailFields(&$mvc)
	{
		$mvc->FLD('productId', 'int', 'caption=Продукт','tdClass=large-field leftCol wrap');
		$mvc->FLD('classId', 'class(interface=cat_ProductAccRegIntf, select=title)', 'caption=Мениджър,silent,input=hidden');
		$mvc->FLD('packagingId', 'key(mvc=cat_Packagings, select=name, allowEmpty)', 'caption=Мярка','tdClass=small-field');
		$mvc->FLD('quantity', 'double(Min=0)', 'caption=К-во,mandatory','tdClass=small-field');
		$mvc->FLD('quantityInPack', 'double(smartRound)', 'input=none');
		$mvc->FLD('price', 'double', 'caption=Цена, input=none');
		$mvc->FLD('amount', 'double(minDecimals=2,maxDecimals=2)', 'caption=Сума,input=none');
		$mvc->FNC('packPrice', 'double(minDecimals=2)', 'caption=Цена,input');
		$mvc->FLD('discount', 'percent', 'caption=Отстъпка');
		$mvc->FLD('note', 'varchar(64)', 'caption=@Пояснение');
	}
	
	
	/**
	 * Извиква се след подготовката на формата
	 */
	public static function on_AfterPrepareEditForm($mvc, $data)
	{
		$rec = &$data->form->rec;
		$masterRec = $data->masterRec;
		$ProductManager = ($data->ProductManager) ? $data->ProductManager : cls::get($rec->classId);
	
		$data->form->fields['packPrice']->unit = "|*" . $masterRec->currencyId . ", ";
		$data->form->fields['packPrice']->unit .= ($masterRec->chargeVat == 'yes') ? "|с ДДС|*" : "|без ДДС|*";
	
		$products = $ProductManager->getProducts($masterRec->contragentClassId, $masterRec->contragentId, $masterRec->valior, $mvc->metaProducts);
		expect(count($products));
	
		$data->form->setSuggestions('discount', arr::make('5 %,10 %,15 %,20 %,25 %,30 %', TRUE));
	
		if (empty($rec->id)) {
			$data->form->addAttr('productId', array('onchange' => "addCmdRefresh(this.form);document.forms['{$data->form->formAttr['id']}'].elements['id'].value ='';document.forms['{$data->form->formAttr['id']}'].elements['packPrice'].value ='';document.forms['{$data->form->formAttr['id']}'].elements['discount'].value ='';this.form.submit();"));
			$data->form->setOptions('productId', array('' => ' ') + $products);
			 
		} else {
			// Нямаме зададена ценова политика. В този случай задъжително трябва да имаме
			// напълно определен продукт (клас и ид), който да не може да се променя във формата
			// и полето цена да стане задължително
			$data->form->setOptions('productId', array($rec->productId => $products[$rec->productId]));
		}
	
		if (!empty($rec->packPrice)) {
			$rec->packPrice = deals_Helper::getDisplayPrice($rec->packPrice, 0, $masterRec->rate, 'no');
		}
	}


	/**
	 * След подготовка на лист тулбара
	 */
	public static function on_AfterPrepareListToolbar($mvc, &$data)
	{
		if (!empty($data->toolbar->buttons['btnAdd'])) {
			$productManagers = core_Classes::getOptionsByInterface('cat_ProductAccRegIntf');
			$masterRec = $data->masterData->rec;
	
			foreach ($productManagers as $manId => $manName) {
				$productMan = cls::get($manId);
				$error = '';
				if(!count($productMan->getProducts($masterRec->contragentClassId, $masterRec->contragentId, $masterRec->valior, $mvc->metaProducts, 1))){
					$text = ($mvc->metaProducts == 'canSell') ? "продаваеми" : "купуваеми";
					$error = "error=Няма {$text} {$productMan->title}";
				}
	
				$data->toolbar->addBtn($productMan->singleTitle, array($mvc, 'add', "{$mvc->masterKey}" => $masterRec->id, 'classId' => $manId, 'ret_url' => TRUE),
						"id=btnAdd-{$manId},{$error},order=10", 'ef_icon = img/16/shopping.png');
				unset($error);
			}
	
			unset($data->toolbar->buttons['btnAdd']);
		}
	}

	
	/**
	 * Изчисляване на цена за опаковка на реда
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $rec
	 */
	public static function on_CalcPackPrice(core_Mvc $mvc, $rec)
	{
		if (!isset($rec->price) || empty($rec->quantity) || empty($rec->quantityInPack)) {
			return;
		}
	
		$rec->packPrice = $rec->price * $rec->quantityInPack;
	}
	
	
	/**
	 * След калкулиране на общата сума
	 */
	public function calculateAmount_(&$recs, &$rec)
	{	
		deals_Helper::fillRecs($this->Master, $recs, $rec, static::$map);
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид.
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $row Това ще се покаже
	 * @param stdClass $rec Това е записа в машинно представяне
	 */
	public static function on_AfterPrepareListRows($mvc, &$data)
	{
		$masterRec = $data->masterData->rec;
		
		if(isset($masterRec->type) && $masterRec->type != 'invoice'){
	
			// При дебитни и кредитни известия показваме основанието
			$data->listFields = array();
			$data->listFields['number'] = '№';
			$data->listFields['reason'] = 'Основание';
			$data->listFields['amount'] = 'Сума';
			$data->rows = array();
	
			// Показване на сумата за промяна на известието
			$amount = $mvc->getFieldType('amount')->toVerbal($masterRec->dealValue / $masterRec->rate);
	
			$data->rows[] = (object) array('number' => 1,
					'reason' => $masterRec->reason,
					'amount' => $amount);
		}
	}
	
	
	/**
	 * След извличане на записите от базата данни
	 */
	public static function on_AfterPrepareListRecs(core_Mvc $mvc, $data)
	{
		$recs = &$data->recs;
		$invRec = &$data->masterData->rec;
		$haveDiscount = FALSE;
		
		$mvc->calculateAmount($recs, $invRec);
	
		if (empty($recs)) return;
	
		foreach ($recs as &$rec){
			$haveDiscount = $haveDiscount || !empty($rec->discount);
		}
	
		if(!$haveDiscount) {
			unset($data->listFields['discount']);
		}
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид.
	 */
	public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		$ProductMan = cls::get($rec->classId);
		$row->productId = $ProductMan::getTitleById($rec->productId);
	
		if($rec->note){
			$varchar = cls::get('type_Varchar');
			$row->note = $varchar->toVerbal($rec->note);
			$row->productId .= "<br/><small style='color:#555;'>{$row->note}</small>";
		}
			
		$pInfo = $ProductMan->getProductInfo($rec->productId);
		$measureShort = cat_UoM::getShortName($pInfo->productRec->measureId);
	
		if($rec->packagingId){
			$row->quantityInPack = $mvc->getFieldType('quantityInPack')->toVerbal($rec->quantityInPack);
			$row->packagingId .= " <small style='color:gray'>{$row->quantityInPack} {$measureShort}</small>";
			$row->packagingId = "<span class='nowrap'>{$row->packagingId}</span>";
		} else {
			$row->packagingId = $measureShort;
		}
	}
	
	
	/**
	 * След проверка на ролите
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{
		if(($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec->{$mvc->masterKey})){
			$hasType = $mvc->Master->getField('type', FALSE);
	
			if(empty($hasType) || (isset($hasType)  && $mvc->Master->fetchField($rec->{$mvc->masterKey}, 'type') == 'invoice')){
				$masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey});
				if($masterRec->state != 'draft'){
					$res = 'no_one';
				} else {
					// При начисляване на авансово плащане не може да се добавят други продукти
					if($masterRec->dpOperation == 'accrued'){
						$res = 'no_one';
					}
				}
			} else {
				// Към ДИ и КИ немогат да се добавят детайли
				$res = 'no_one';
			}
		}
	}
	
	
	/**
	 * Преди подготвяне на едит формата
	 */
	public static function on_BeforePrepareEditForm($mvc, &$res, $data)
	{
		if($classId = Request::get('classId', 'class(interface=cat_ProductAccRegIntf)')){
			$data->ProductManager = cls::get($classId);
			$mvc->getField('productId')->type = cls::get('type_Key', array('params' => array('mvc' => $data->ProductManager->className, 'select' => 'name', 'maxSuggestions' => 1000000000)));
		}
	}
	
	
	/**
	 * Преди извличане на записите филтър по number
	 */
	public static function on_AfterPrepareListFilter($mvc, &$data)
	{
		$data->query->orderBy('#id', 'ASC');
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
		$masterRec  = $mvc->Master->fetch($rec->{$mvc->masterKey});
		$update = FALSE;
	
		/* @var $ProductMan core_Manager */
		expect($ProductMan = cls::get($rec->classId));
		if($form->rec->productId){
			$vat = cls::get($rec->classId)->getVat($rec->productId);
			$form->setOptions('packagingId', $ProductMan->getPacks($rec->productId));
	
			// Само при рефреш слагаме основната опаковка за дефолт
			if($form->cmd == 'refresh'){
				$baseInfo = $ProductMan->getBasePackInfo($rec->productId);
				if($baseInfo->classId == cat_Packagings::getClassId()){
					$form->rec->packagingId = $baseInfo->id;
				}
				
				if(isset($mvc->LastPricePolicy)){
					$policyInfo = $mvc->LastPricePolicy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->classId, $rec->packagingId, $masterRec->rate);
					
					if($policyInfo->price != 0){
						$form->setSuggestions('packPrice', array('' => '', "{$lastPrice}" => $lastPrice));
					}
				}
			}
		}
	
		if ($form->isSubmitted() && !$form->gotErrors()) {
	
			// Извличане на информация за продукта - количество в опаковка, единична цена
			$rec = &$form->rec;
	
			if($rec->quantity == 0){
				$form->setError('quantity', 'Количеството не може да е|* "0"');
			}
	
			if(empty($rec->id)){
				$where = "#{$mvc->masterKey} = {$rec->{$mvc->masterKey}} AND #classId = {$rec->classId} AND #productId = {$rec->productId} AND #packagingId";
				$where .= ($rec->packagingId) ? "={$rec->packagingId}" : " IS NULL";
				if($pRec = $mvc->fetch($where)){
					$form->setWarning("productId", "Има вече такъв продукт с тази опаковка. Искате ли да го обновите?");
					$rec->id = $pRec->id;
					$update = TRUE;
				}
			}
	
			$productRef = new core_ObjectReference($ProductMan, $rec->productId);
			expect($productInfo = $productRef->getProductInfo());
	
			$rec->quantityInPack = (empty($rec->packagingId)) ? 1 : $productInfo->packagings[$rec->packagingId]->quantity;
				
			// Ако няма въведена цена
			if (!isset($rec->packPrice)) {
						
				// Ако продукта има цена от пораждащия документ, взимаме нея, ако не я изчисляваме наново
				$origin = $mvc->Master->getOrigin($masterRec);
				$dealInfo = $origin->getAggregateDealInfo();
				$products = $dealInfo->get('products');
						
				if(count($products)){
					foreach ($products as $p){
						if($rec->classId == $p->classId && $rec->productId == $p->productId && $rec->packagingId == $p->packagingId){
							$policyInfo = new stdClass();
							$policyInfo->price = deals_Helper::getDisplayPrice($p->price, $vat, $masterRec->rate, 'no');
							break;
						}
					}
				}
						
				if(!$policyInfo){
					$Policy = cls::get($rec->classId)->getPolicy();
					$policyInfo = $Policy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->classId, $rec->packagingId, $rec->quantity, dt::now(), $masterRec->rate);
				}
					
				// Ако няма последна покупна цена и не се обновява запис в текущата покупка
				if (!isset($policyInfo->price) && empty($pRec)) {
					$form->setError('price', 'Продукта няма цена в избраната ценова политика');
				} else {
							
					// Ако се обновява вече съществуващ запис
					if($pRec){
						$pRec->packPrice = deals_Helper::getDisplayPrice($pRec->packPrice, $vat, $masterRec->rate, 'no');
					}
							
					// Ако се обновява запис се взима цената от него, ако не от политиката
					$rec->price = ($pRec->price) ? $pRec->price : $policyInfo->price;
					$rec->packPrice = ($pRec->packPrice) ? $pRec->packPrice : $policyInfo->price * $rec->quantityInPack;
					
					if($policyInfo->discount && empty($rec->discount)){
						$rec->discount = $policyInfo->discount;
					}
				}
	
			} else {
				// Изчисляване цената за единица продукт в осн. мярка
				$rec->price  = $rec->packPrice  / $rec->quantityInPack;
				
				// Обръщаме цената в основна валута, само ако не се ъпдейтва или се ъпдейтва и е чекнат игнора
				if(!$update || ($update && Request::get('Ignore'))){
					$rec->packPrice =  deals_Helper::getPurePrice($rec->packPrice, 0, $masterRec->rate, $masterRec->vatRate);
				}
			}
	
			$rec->price = deals_Helper::getPurePrice($rec->price, 0, $masterRec->rate, $masterRec->chargeVat);
			
			// Записваме основната мярка на продукта
			$rec->uomId = $productInfo->productRec->measureId;
			$rec->amount = $rec->packPrice * $rec->quantity;
				
			// При редакция, ако е променена опаковката слагаме преудпреждение
			if($rec->id){
				$oldRec = $mvc->fetch($rec->id);
				if($oldRec && $rec->packagingId != $oldRec->packagingId && trim($rec->packPrice) == trim($oldRec->packPrice)){
					$form->setWarning('packPrice,packagingId', 'Опаковката е променена без да е променена цената.|*<br />| Сигурнили сте че зададената цена отговаря на  новата опаковка!');
				}
			}
		}
	}
}