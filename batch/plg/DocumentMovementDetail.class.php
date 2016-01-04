<?php



/**
 * Клас 'batch_plg_DocumentMovementDetail' - За генериране на партидни движения от документите
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo да се разработи
 */
class batch_plg_DocumentMovementDetail extends core_Plugin
{
	
	
	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Mvc $mvc)
	{
		$mvc->FLD('batch', 'text', 'input=hidden,caption=Партиден №,after=productId,forceField');
		setIfNot($mvc->productFieldName, 'productId');
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$data->form->setField('batch', 'input=hidden');
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
		$storeId = $mvc->Master->fetchField($rec->{$mvc->masterKey}, $mvc->Master->storeFieldName);
		
		if(isset($rec->{$mvc->productFieldName})){
			$BatchClass = batch_Defs::getBatchDef($rec->{$mvc->productFieldName});
			if($BatchClass){
				$form->setField('batch', 'input');
				$suggestions = batch_Items::getBatches($rec->{$mvc->productFieldName}, $storeId);
				
				$form->setFieldType('batch', $BatchClass->getBatchClassType());
				$form->setDefault('batch', $BatchClass->getAutoValue($mvc->Master, $rec->{$mvc->masterKey}));
				if(!empty($rec->batch)){
					$rec->batch = $BatchClass->denormalize($rec->batch);
				}
				
				if($mvc->Master->batchMovementDocument == 'out'){
					if(count($suggestions)){
						$form->setSuggestions('batch', array('' => '') + $suggestions);
					}
				}
			} else {
				$form->setField('batch', 'input=none');
				unset($rec->batch);
			}
			
			if($form->isSubmitted()){
				if(is_object($BatchClass)){
					$productInfo = cat_Products::getProductInfo($rec->{$mvc->productFieldName});
					$quantityInPack = ($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;
					$quantity = $rec->packQuantity * $quantityInPack;
					
					if(!$BatchClass->isValid($rec->batch, $quantity, $msg)){
						$form->setError('batch', $msg);
					}
				}
			}
		}
	}
	
	
	/**
	 * Преди запис на документ
	 */
	public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
	{
		// Нормализираме полето за партидата
		if(!empty($rec->batch)){
			$BatchClass = batch_Defs::getBatchDef($rec->{$mvc->productFieldName});
			if(is_object($BatchClass)){
				$rec->batch = $BatchClass->normalize($rec->batch);
			}
		} else {
			$rec->batch = NULL;
		}
	}
	
	
	/**
	 * Проверка дали всичко с реда на детайла е ок откъм партидите
	 * 
	 * @param core_Detail $mvc
	 * @param mixed $id - ид или запис
	 * @return FALSE|string - грешката или FALSE ако няма
	 */
	public static function getBatchRecInvalidMessage(core_Detail $mvc, $id)
	{
		$rec = $mvc->fetchRec($id);
		$msg = FALSE;
		
		// Кой е избрания склад в мастъра
		$storeName = $mvc->Master->storeFieldName;
		
		$masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey}, "state,{$storeName}");

		// Ако реда има партидност
		$BatchClass = batch_Defs::getBatchDef($rec->{$mvc->productFieldName});
		if(is_object($BatchClass)){
			
			// Ако не е въведена партида, сетваме грешка
			if(empty($rec->batch)){
				$msg = 'Не е въведен партиден номер';
			} elseif($BatchClass instanceof batch_definitions_Serial){
				
				// Ако има сериен номер, проверяваме другите детайли
				$query = $mvc->getQuery();
				$query->where("#{$mvc->masterKey} = {$rec->{$mvc->masterKey}}");
				$query->where("#id != {$rec->id}");
				$query->where("#batch IS NOT NULL AND #batch != ''");
				
				// За всеки
				while($oRec = $query->fetch()){
					$DbatchClass = batch_Defs::getBatchDef($oRec->{$mvc->productFieldName});
					
					// Чийто клас на партидата също е сериен номер
					if(!($DbatchClass instanceof batch_definitions_Serial)) continue;
					
					// Засичаме серийните номера
					$oSerials = $BatchClass->makeArray($rec->batch);
					$serials = batch_Defs::getBatchArray($oRec->{$mvc->productFieldName}, $oRec->batch);
					
					// Проверяваме имали дублирани
					$intersectArr = array_intersect($oSerials, $serials);
					$intersect = count($intersectArr);
					
					// Ако има казваме, кои се повтарят
					// един сериен номер не може да е на повече от един ред
					if($intersect){
						$imploded = implode(',', $intersectArr);
						if($intersect == 1){
							$msg = "|Серийният номер|*: {$imploded}| се повтаря в документа|*";
						} else {
							$msg = "|Серийните номера|*: {$imploded}| се повтарят в документа|*";
						}
					}
				}
			}
			
			// Ако има склад и партида
			if($masterRec->state == 'draft' && isset($masterRec->{$storeName}) && !empty($rec->batch) && $mvc->Master->batchMovementDocument == 'out'){
				$batchArr = $BatchClass->makeArray($rec->batch);
				
				foreach ($batchArr as $b){
					$batchQuantity = batch_Items::getQuantity($rec->{$mvc->productFieldName}, $b, $masterRec->{$storeName});
					$quantity = $rec->quantity / count($batchQuantity);
					
					// Ако текущото количество е по-голямо от експедираното сетваме грешка
					if($quantity > $batchQuantity){
						$msg2 = 'Няма достатъчно количество от избраната партида в склада';
						$msg = ($msg === FALSE) ? $msg2 : $msg . "<br>" . $msg2;
						break;
					}
				}
			}
		}
		
		// Връщаме съобщението за грешка ако има
		return $msg;
	}
	
	
	/**
	 * Преди рендиране на таблицата
	 */
	public static function on_BeforeRenderListTable($mvc, &$res, $data)
	{
		if(!count($data->recs)) return;
		
		$rows = &$data->rows;
		$recs = &$data->recs;
		
		foreach ($recs as $id => $rec){
			
			// Ако има проблем с партидите, показваме грешката и маркираме реда
			if($msg = self::getBatchRecInvalidMessage($mvc, $rec)){
				$msg = tr($msg);
				$rows[$id]->{$mvc->productFieldName} .= "<div style='font-size:0.75em;color:red;'>{$msg}</div>";
				$rows[$id]->ROW_ATTR['style'] = 'background-color:#ffb3b3';
			}
		}
	}
}