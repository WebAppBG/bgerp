<?php


/**
 * Клас 'deals_DealDetail'
 *
 * Клас за наследяване от детайли на бизнес документи(@see deals_DealDetail)
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class deals_DealDetail extends doc_Detail
{
 	
 	
	/**
	 * Кои полета от листовия изглед да се скриват ако няма записи в тях
	 */
	protected $hideListFieldsIfEmpty = 'discount';
 	
 	
 	/**
     * Изчисляване на сумата на реда
     * 
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    public static function on_CalcAmount(core_Mvc $mvc, $rec)
    {
        if (empty($rec->price) || empty($rec->quantity)) {
            return;
        }
        
        $rec->amount = $rec->price * $rec->quantity;
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
     * Изчисляване на количеството на реда в брой опаковки
     * 
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    public static function on_CalcPackQuantity(core_Mvc $mvc, $rec)
    {
        if (empty($rec->quantity) || empty($rec->quantityInPack)) {
            return;
        }
        
        $rec->packQuantity = $rec->quantity / $rec->quantityInPack;
    }
    
    
    /**
     * След описанието на полетата
     */
    public static function getDealDetailFields(&$mvc)
    {
    	$mvc->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Продукт,notNull,mandatory', 'tdClass=large-field leftCol wrap,silent,removeAndRefreshForm=packPrice|discount|packagingId|tolerance');
    	$mvc->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Мярка', 'smartCenter,tdClass=small-field,silent,removeAndRefreshForm=packPrice|discount,mandatory');
    	$mvc->FLD('batch', 'varchar(128)', 'input=none,caption=Партида,after=productId,forceField');
    	
    	// Количество в основна мярка
    	$mvc->FLD('quantity', 'double', 'caption=Количество,input=none,smartCenter');
    	
    	// Количество (в осн. мярка) в опаковката, зададена от 'packagingId'; Ако 'packagingId'
    	// няма стойност, приема се за единица.
    	$mvc->FLD('quantityInPack', 'double', 'input=none,smartCenter');
    	
    	// Цена за единица продукт в основна мярка
    	$mvc->FLD('price', 'double', 'caption=Цена,input=none,smartCenter');
    	
    	// Брой опаковки (ако има packagingId) или к-во в основна мярка (ако няма packagingId)
    	$mvc->FNC('packQuantity', 'double(Min=0)', 'caption=К-во,input,smartCenter');
    	$mvc->FNC('amount', 'double(minDecimals=2,maxDecimals=2)', 'caption=Сума');
    	
    	// Цена за опаковка (ако има packagingId) или за единица в основна мярка (ако няма packagingId)
    	$mvc->FNC('packPrice', 'double(minDecimals=2)', 'caption=Цена,input,smartCenter');
    	$mvc->FLD('discount', 'percent(Min=0,max=1)', 'caption=Отстъпка');
    	$mvc->FLD('tolerance', 'percent(min=0,max=1,decimals=0)', 'caption=Толеранс,input=none');
        $mvc->FLD('showMode', 'enum(auto=По подразбиране,detailed=Разширен,short=Съкратен)', 'caption=Изглед,notNull,default=auto');
    	$mvc->FLD('notes', 'richtext(rows=3)', 'caption=Забележки');
    }
    
    
    /**
     * След описанието
     */
    public static function on_AfterDescription(&$mvc)
    {
    	// Скриване на полетата за създаване
    	$mvc->setField('createdOn', 'column=none');
    	$mvc->setField('createdBy', 'column=none');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if(($action == 'delete' || $action == 'add' || $action == 'edit') && isset($rec)){
        	$state = $mvc->Master->fetchField($rec->{$mvc->masterKey}, 'state');
        	if($state != 'draft'){
        		$requiredRoles = 'no_one';
        	}
        }
    }
    
    
    /**
     * След извличане на записите от базата данни
     */
    public static function on_AfterPrepareListRecs(core_Mvc $mvc, $data)
    {
        if (empty($data->recs)) return;
    	$recs = &$data->recs;
        
        deals_Helper::fillRecs($mvc->Master, $recs, $data->masterData->rec);
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $rec       = &$data->form->rec;
        $masterRec = $data->masterRec;
       	
       	$data->form->fields['packPrice']->unit = "|*" . $masterRec->currencyId . ", ";
        $data->form->fields['packPrice']->unit .= ($masterRec->chargeVat == 'yes') ? "|с ДДС|*" : "|без ДДС|*";
       
        $products = cat_Products::getProducts($masterRec->contragentClassId, $masterRec->contragentId, $masterRec->valior, $mvc->metaProducts);
        expect(count($products));
        
        $data->form->setSuggestions('discount', array('' => '') + arr::make('5 %,10 %,15 %,20 %,25 %,30 %', TRUE));
        
        if (empty($rec->id)) {
        	$data->form->setOptions('productId', array('' => ' ') + $products);
        	
        } else {
            // Нямаме зададена ценова политика. В този случай задъжително трябва да имаме
            // напълно определен продукт (клас и ид), който да не може да се променя във формата
            // и полето цена да стане задължително
            $data->form->setOptions('productId', array($rec->productId => $products[$rec->productId]));
        }
        
        if (!empty($rec->packPrice)) {
        	$vat = cat_Products::getVat($rec->productId, $masterRec->valior);
        	$rec->packPrice = deals_Helper::getDisplayPrice($rec->packPrice, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
        }
        
        if($rec->productId){
        	
        	$tolerance = cat_Products::getParams($rec->productId, 'tolerance');
        	if(!empty($tolerance)){
        		$percentVerbal = str_replace('&nbsp;', ' ', $mvc->getFieldType('tolerance')->toVerbal($tolerance));
        		$data->form->setField('tolerance', 'input');
        		if(empty($rec->id)){
        			$data->form->setDefault('tolerance', $tolerance);
        		}
        		$data->form->setSuggestions('tolerance', array('' => '', $percentVerbal => $percentVerbal));
        	}
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    protected static function inputDocForm(core_Mvc $mvc, core_Form $form)
    {
    	$rec = &$form->rec;
    	
    	$masterRec  = $mvc->Master->fetch($rec->{$mvc->masterKey});
    	$priceAtDate = ($masterRec->pricesAtDate) ? $masterRec->pricesAtDate : $masterRec->valior;
    	
    	if($rec->productId){
    		$productInfo = cat_Products::getProductInfo($rec->productId);
    		
    		$vat = cat_Products::getVat($rec->productId, $masterRec->valior);
    		$packs = cat_Products::getPacks($rec->productId);
    		$form->setOptions('packagingId', $packs);
    		$form->setDefault('packagingId', key($packs));
    		
    		if(isset($mvc->LastPricePolicy)){
    			$policyInfoLast = $mvc->LastPricePolicy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->packagingId, $rec->packQuantity, $priceAtDate, $masterRec->currencyRate, $masterRec->chargeVat);
    			if($policyInfoLast->price != 0){
    				$form->setSuggestions('packPrice', array('' => '', "{$policyInfoLast->price}" => $policyInfoLast->price));
    			}
    		}
    		
    		// Ако артикула не е складируем, скриваме полето за мярка
    		if(!isset($productInfo->meta['canStore'])){
    			$form->setField('packagingId', 'input=hidden');
    			$measureShort = cat_UoM::getShortName($form->rec->packagingId);
    			$form->setField('packQuantity', "unit={$measureShort}");
    		}
    	} else {
    		$form->setReadOnly('packagingId');
    	}
    	 
    	if ($form->isSubmitted() && !$form->gotErrors()) {
    	
    		// Извличане на информация за продукта - количество в опаковка, единична цена
    		if(!isset($rec->packQuantity)){
    			$rec->packQuantity = 1;
    		}
    		
    		// Закръгляме количеството спрямо допустимото от мярката
    		$roundQuantity = cat_UoM::round($rec->packQuantity, $rec->productId);
    		if($roundQuantity == 0){
    			$form->setError('packQuantity', 'Не може да бъде въведено количество, което след закръглянето указано в|* <b>|Артикули|* » |Каталог|* » |Мерки/Опаковки|*</b> |ще стане|* 0');
    			return;
    		}
    		
    		if($roundQuantity != $rec->packQuantity){
    			$form->setWarning('packQuantity', 'Количеството ще бъде закръглено до указаното в|* <b>|Артикули|* » |Каталог|* » |Мерки/Опаковки|*</b>');
    			
    			// Закръгляме количеството
    			$rec->packQuantity = $roundQuantity;
    		}
    		
    		// Ако артикула няма опаковка к-то в опаковка е 1, ако има и вече не е свързана към него е това каквото е било досега, ако още я има опаковката обновяваме к-то в опаковка
    		$rec->quantityInPack = ($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;
    		$rec->quantity = $rec->packQuantity * $rec->quantityInPack;
    		
    		if (!isset($rec->packPrice)) {
    			$Policy = (isset($mvc->Policy)) ? $mvc->Policy : cls::get('price_ListToCustomers');
    			$policyInfo = $Policy->getPriceInfo($masterRec->contragentClassId, $masterRec->contragentId, $rec->productId, $rec->packagingId, $rec->packQuantity, $priceAtDate, $masterRec->currencyRate, $masterRec->chargeVat);
    				
    			if (empty($policyInfo->price) && empty($pRec)) {
    				$form->setError('packPrice', 'Продукта няма цена в избраната ценова политика');
    			} else {
    				 
    				// Ако се обновява запис се взима цената от него, ако не от политиката
    				$price = $policyInfo->price;
    				if($policyInfo->discount && empty($rec->discount)){
    					$rec->discount = $policyInfo->discount;
    				}
    			}
    		} else {
    			$price = $rec->packPrice / $rec->quantityInPack;
    			$rec->packPrice =  deals_Helper::getPurePrice($rec->packPrice, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
    		}
    		 
    		$price = deals_Helper::getPurePrice($price, $vat, $masterRec->currencyRate, $masterRec->chargeVat);
    		$rec->price  = $price;
    		
    		// Ако има такъв запис, сетваме грешка
    		$exRec = deals_Helper::fetchExistingDetail($mvc, $rec->{$mvc->masterKey}, $rec->id, $rec->productId, $rec->packagingId, $rec->price, $rec->discount, $rec->tolerance, $rec->term);
    		if($exRec){
    			$form->setError('productId,packagingId,packPrice,discount,tolerance,term', 'Вече съществува запис със същите данни');
    			unset($rec->packPrice, $rec->price, $rec->quantity, $rec->quantityInPack);
    		}
    	
    		// При редакция, ако е променена опаковката слагаме преудпреждение
    		if($rec->id){
    			$oldRec = $mvc->fetch($rec->id);
    			if($oldRec && $rec->packagingId != $oldRec->packagingId && round($rec->packPrice, 4) == round($oldRec->packPrice, 4)){
    				$form->setWarning('packPrice,packagingId', "Опаковката е променена без да е променена цената.|*<br />| Сигурнили сте, че зададената цена отговаря на  новата опаковка!");
    			}
    		}
    	}
    }
    
    
    /**
     * Преди подготовка на полетата за показване в списъчния изглед
     */
    public static function on_AfterPrepareListRows($mvc, $data)
    {
    	if(!count($data->recs)) return;
    	
    	$recs = &$data->recs;
    	$rows = &$data->rows;
    	
    	core_Lg::push($data->masterData->rec->tplLang);
    	$date = ($data->masterData->rec->state == 'draft') ? NULL : $data->masterData->rec->modifiedOn;
    	
    	foreach ($rows as $id => &$row){
    		$rec = $recs[$id];
    		
    		$row->productId = cat_Products::getAutoProductDesc($rec->productId, $date, $rec->showMode);
    		if(!empty($rec->batch)){
    			$rec->notes .= ($rec->notes) ? "\n" : '';
    			$rec->notes .= "lot: {$rec->batch}";
    		}
    		
    		if($rec->notes){
    			deals_Helper::addNotesToProductRow($row->productId, $rec->notes);
    		}
    	}
    	
    	core_Lg::pop();
    }
    
    
    /**
     * След подготовка на лист тулбара
     */
    public static function on_AfterPrepareListToolbar($mvc, $data)
    {
    	if (!empty($data->toolbar->buttons['btnAdd'])) {
    		$masterRec = $data->masterData->rec;
    		
    		if(!count(cat_Products::getProducts($masterRec->contragentClassId, $masterRec->contragentId, $masterRec->valior, $mvc->metaProducts, NULL, 1))){
                $error = "error=Няма продаваеми артикули, ";
            }
            
            $data->toolbar->addBtn('Артикул', array($mvc, 'add', "{$mvc->masterKey}" => $masterRec->id, 'ret_url' => TRUE),
            "id=btnAdd-{$masterRec->id},{$error} order=10,title=Добавяне на артикул", 'ef_icon = img/16/shopping.png');
            
            unset($data->toolbar->buttons['btnAdd']);
        }
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    public static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
    	$recs = &$data->recs;
    	$rows = &$data->rows;
    	
    	// Скриване на полето "мярка"
    	$data->listFields = array_diff_key($data->listFields, arr::make('quantityInPack', TRUE));
    	
    	if(!count($recs)) return;
    	
        // Флаг дали има отстъпка
        $haveDiscount = FALSE;
        
        if(count($data->rows)) {
            foreach ($data->rows as $i => &$row) {
                $rec = $data->recs[$i];
                
              	if($rec->tolerance){
              		$tolerance = $mvc->getFieldType('tolerance')->toVerbal($rec->tolerance);
              		$row->packQuantity .= "<small style='font-size:0.8em;display:block;' class='quiet'>±{$tolerance}</small>";
              	}
                
              	// Показваме подробната информация за опаковката при нужда
              	deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
            }
        }
    }
    
    
    /**
	 * Инпортиране на артикул генериран от ред на csv файл 
	 * @param int $masterId - ид на мастъра на детайла
	 * @param array $row - Обект представляващ артикула за импортиране
	 * 					->code - код/баркод на артикула
	 * 					->quantity - К-во на опаковката или в основна мярка
	 * 					->price - цената във валутата на мастъра, ако няма се изчислява директно
	 * @return  mixed - резултата от експорта
	 */
    function import($masterId, $row)
    {
    	$Master = $this->Master;
    	
    	$pRec = cat_Products::getByCode($row->code);
    	
    	$price = NULL;
    	
    	// Ако има цена я обръщаме в основна валута без ддс, спрямо мастъра на детайла
    	if($row->price){
    		$masterRec = $Master->fetch($masterId);
    		$price = deals_Helper::getPurePrice($row->price, cat_Products::getVat($pRec->productId), $masterRec->currencyRate, $masterRec->chargeVat);
    	}
    	
    	return $Master::addRow($masterId, $pRec->productId, $row->quantity, $price, $pRec->packagingId);
    }
}
