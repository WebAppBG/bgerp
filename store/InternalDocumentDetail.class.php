<?php



/**
 * Абстрактен клас за наследяване от вътрешни складови документи
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class store_InternalDocumentDetail extends doc_Detail
{
    
    
    /**
     * Описание на модела (таблицата)
     */
    protected function setFields($mvc)
    {
    	$mvc->FLD('productId', 'key(mvc=cat_Products,select=name)', 'silent,caption=Продукт,notNull,mandatory', 'tdClass=large-field leftCol wrap');
    	$mvc->FLD('packagingId', 'key(mvc=cat_UoM, select=name)', 'caption=Мярка,after=productId,mandatory');
    	$mvc->FLD('batch', 'text', 'input=none,caption=Партида,after=productId,forceField');
    	$mvc->FLD('quantityInPack', 'double(decimals=2)', 'input=none,column=none');
    	$mvc->FLD('packQuantity', 'double(Min=0)', 'caption=Количество,input=input,mandatory');
		$mvc->FLD('packPrice', 'double(minDecimals=2)', 'caption=Цена,input');
		$mvc->FNC('amount', 'double(minDecimals=2,maxDecimals=2)', 'caption=Сума,input=none');
		
		// Допълнително
		$mvc->FLD('weight', 'cat_type_Weight', 'input=none,caption=Тегло');
		$mvc->FLD('volume', 'cat_type_Volume', 'input=none,caption=Обем');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm(core_Mvc $mvc, &$data)
    {
    	$rec = &$data->form->rec;
    	$masterRec = $data->masterRec;
    	
    	$products = $mvc->getProducts($masterRec);
		expect(count($products));
			
		if (empty($rec->id)) {
			$data->form->setField('productId', "removeAndRefreshForm=packPrice|packagingId");
			$data->form->setOptions('productId', array('' => ' ') + $products);
		} else {
			$data->form->setOptions('productId', array($rec->productId => $products[$rec->productId]));
		}
		
		$rec->chargeVat = (cls::get($masterRec->contragentClassId)->shouldChargeVat($masterRec->contragentId)) ? 'yes' : 'no';
		$chargeVat = ($rec->chargeVat == 'yes') ? 'с ДДС' : 'без ДДС';
		
		$data->form->setField('packPrice', "unit={$masterRec->currencyId} {$chargeVat}");
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form &$form)
    {
    	$rec = &$form->rec;
    	
    	$masterRec  = $mvc->Master->fetch($rec->{$mvc->masterKey});
    	$currencyRate = $rec->currencyRate = currency_CurrencyRates::getRate($masterRec->valior, $masterRec->currencyId, acc_Periods::getBaseCurrencyCode($masterRec->valior));
    	if(!$currencyRate){
    		$form->setError('currencyRate', 'Не може да се изчисли курс');
    	}
    	
    	if($form->rec->productId){
    		$packs = cat_Products::getPacks($rec->productId);
    		$form->setOptions('packagingId', $packs);
    		
    		// Слагаме цената от политиката за последна цена
    		if(isset($mvc->LastPricePolicy)){
    			$policyInfoLast = $mvc->LastPricePolicy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, cat_Products::getClassId(), $rec->packagingId, $rec->packQuantity, $masterRec->valior, $currencyRate, $rec->chargeVat);
    			if($policyInfoLast->price != 0){
    				$form->setSuggestions('packPrice', array('' => '', "{$policyInfoLast->price}" => $policyInfoLast->price));
    			}
    		}
    	} else {
    		$form->setReadOnly('packagingId');
    	}
    	
    	if($form->isSubmitted()){
    		$productInfo = cat_Products::getProductInfo($rec->productId);
    		
    		// Ако артикула няма опаковка к-то в опаковка е 1, ако има и вече не е свързана към него е това каквото е било досега, ако още я има опаковката обновяваме к-то в опаковка
    		$rec->quantityInPack = ($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;
    	
    		if(!isset($rec->packPrice)){
    			$Policy = cls::get('price_ListToCustomers');
    			$rec->packPrice = $Policy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, cat_Products::getClassId(), $rec->packagingId, $rec->packQuantity, $masterRec->valior, $currencyRate, $rec->chargeVat)->price;
    			$rec->packPrice = $rec->packPrice * $rec->quantityInPack;
    		}
    		
    		if(!isset($rec->packPrice)){
    			$form->setError('packPrice', 'Продукта няма цена в избраната ценова политика');
    		}
    		
    		$rec->weight = cat_Products::getWeight($rec->productId, $rec->packagingId);
    		$rec->volume = cat_Products::getVolume($rec->productId, $rec->packagingId);
    	}
    }
    
    
    /**
     * Изчисляване на сумата на реда
     */
    public static function on_CalcAmount(core_Mvc $mvc, $rec)
    {
    	if (empty($rec->packPrice) || empty($rec->packQuantity)) {
    		return;
    	}
    
    	$rec->amount = $rec->packPrice * $rec->packQuantity;
    }
    
    
    /**
     * След обработка на записите от базата данни
     */
    public static function on_AfterPrepareListRows(core_Mvc $mvc, $data)
    {
    	if(!count($data->rows)) return;
    	
    	foreach ($data->rows as $i => &$row) {
    		$rec = &$data->recs[$i];
    		
    		$row->productId = cat_Products::getShortHyperlink($rec->productId);
    		
    		batch_Defs::appendBatch($rec->productId, $rec->batch, $rec->notes);
    		if($rec->notes){
    			deals_Helper::addNotesToProductRow($row->productId, $rec->notes, $rec->batch);
    		}
    		
    		// Показваме подробната информация за опаковката при нужда
    		deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
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
     * След рендиране на детайла
     */
    public static function on_AfterRenderDetail($mvc, &$tpl, $data)
    {
    	// Ако документа е активиран и няма записи съответния детайл не го рендираме
    	if($data->masterData->rec->state != 'draft' && !$data->rows){
    		$tpl = new ET('');
    	}
    }
}