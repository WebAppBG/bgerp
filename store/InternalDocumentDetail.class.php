<?php



/**
 * Абстрактен клас за наследяване от вътрешни скалдови документи
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
    	$mvc->FLD('packagingId', 'key(mvc=cat_Packagings, select=name, allowEmpty)', 'caption=Мярка,after=productId');
    	$mvc->FLD('quantityInPack', 'double(decimals=2)', 'input=none,column=none');
    	$mvc->FLD('packQuantity', 'double(Min=0)', 'caption=К-во,input=input,mandatory');
		$mvc->FLD('packPrice', 'double(minDecimals=2)', 'caption=Цена,input');
		$mvc->FNC('amount', 'double(minDecimals=2,maxDecimals=2)', 'caption=Сума,input=none');
		
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
    	
    	$products = $mvc->getProducts(cls::get('cat_Products'), $masterRec);
		expect(count($products));
			
		if (empty($rec->id)) {
			$data->form->setField('productId', "removeAndRefreshForm=packPrice|packagingId");
			$data->form->setOptions('productId', array('' => ' ') + $products);
		} else {
			$data->form->setOptions('productId', array($rec->productId => $products[$rec->productId]));
		}
		
		$data->form->setField('packPrice', "unit={$masterRec->currencyId}");
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
    	$ProductMan = cls::get('cat_Products');
    	
    	$masterRec  = $mvc->Master->fetch($rec->{$mvc->masterKey});
    	$currencyRate = $rec->currencyRate = currency_CurrencyRates::getRate($masterRec->valior, $masterRec->currencyId, acc_Periods::getBaseCurrencyCode($masterRec->valior));
    	
    	if($form->rec->productId){
    		$form->setOptions('packagingId', $ProductMan->getPacks($rec->productId));
    		unset($form->getFieldType('packagingId')->params['allowEmpty']);
    		
    		// Само при рефреш слагаме основната опаковка за дефолт
    		if($form->cmd == 'refresh'){
    			$baseInfo = $ProductMan->getBasePackInfo($rec->productId);
    			if($baseInfo->classId == 'cat_Packagings'){
    				$form->rec->packagingId = $baseInfo->id;
    			}
    		}
    			
    		// Слагаме цената от политиката за последна цена
    		if(isset($mvc->LastPricePolicy)){
    			$policyInfoLast = $mvc->LastPricePolicy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $ProductMan->getClassId(), $rec->packagingId, $rec->packQuantity, $masterRec->valior, $currencyRate, 'yes');
    			if($policyInfoLast->price != 0){
    				$form->setSuggestions('packPrice', array('' => '', "{$policyInfoLast->price}" => $policyInfoLast->price));
    			}
    		}
    	}
    	
    	if($form->isSubmitted()){
    		$productInfo = $ProductMan->getProductInfo($rec->productId);
    		$rec->quantityInPack = (empty($rec->packagingId)) ? 1 : $productInfo->packagings[$rec->packagingId]->quantity;
    		
    		if(!isset($rec->packPrice)){
    			$Policy = $ProductMan->getPolicy();
    			$rec->packPrice = $Policy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $ProductMan->getClassId(), $rec->packagingId, $rec->packQuantity, $masterRec->valior, $currencyRate, 'yes')->price;
    			$rec->packPrice = $rec->packPrice * $rec->quantityInPack;
    		}
    		
    		if(!isset($rec->packPrice)){
    			$form->setError('packPrice', 'Продукта няма цена в избраната ценова политика');
    		}
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
    	
    	$Double = cls::get('type_Double', array('params' => array('smartRound' => TRUE)));
    	
    	foreach ($data->rows as $i => &$row) {
    		$rec = &$data->recs[$i];
    		
    		if(cat_Products::haveRightFor('single', $rec->productId)){
    			$row->productId = ht::createLinkRef(cat_Products::getTitleById($rec->productId), array('cat_Products', 'single', $rec->productId));
    		}
    		
    		$pInfo = cat_Products::getProductInfo($rec->productId);
    		$uomId = $pInfo->productRec->measureId;
    		if (empty($rec->packagingId)) {
    				$row->packagingId = cat_UoM::getTitleById($uomId);
    		} else {
    			if(cat_Packagings::fetchField($rec->packagingId, 'showContents') == 'yes'){
    				$shortUomName = cat_UoM::getShortName($uomId);
    				$row->quantityInPack = $Double->toVerbal($rec->quantityInPack);
    				$row->packagingId .= ' <small class="quiet">' . $row->quantityInPack . ' ' . $shortUomName . '</small>';
    				$row->packagingId = "<span class='nowrap'>{$row->packagingId}</span>";
    			}
    		}
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
}