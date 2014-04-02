<?php



/**
 * Плъгин позволяващ на обикновена фактура да начислява или да приспада
 * ддс ако се очаква авансово плащане
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_plg_DpInvoice extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
     */
    function on_AfterDescription(core_Mvc $mvc)
    {
    	if($mvc instanceof sales_Invoices || $mvc instanceof purchase_Invoices){
    		
    		// Сума на авансовото плащане (ако има)
	    	$mvc->FLD('dpAmount', 'double', 'caption=Авансово плащане->Сума,input=none,before=contragentName');
	    	
	    	// Операция с авансовото плащане начисляване/намаляване
	    	$mvc->FLD('dpOperation', 'enum(accrued=Начисляване, deducted=Приспадане)', 'caption=Авансово плащане->Операция,input=none,before=contragentName');
    	}
    }
    
    
    /**
     * Извиква се след подготовка на формата във фактурата
     */
    public static function on_AfterPrepareDpInvoicePlg($mvc, &$res, &$data)
    {
    	$form = &$data->form;
    	$rec = &$form->rec;
    	
    	// Ако е детайла на фактурата не правим нищо
        if(!($mvc instanceof sales_Invoices || $mvc instanceof purchase_Invoices)) return;
    	
        // Ако е ДИ или КИ не правим нищо
        if($rec->type != 'invoice') return;
        
        // Намиране на пораждащия се документ
        $origin         = $mvc->getOrigin($rec);
        $originRec      = $origin->fetch();
        $dealInfo       = $origin->getAggregateDealInfo();
        $form->dealInfo = $dealInfo;
        
        // Ако няма очаквано авансово плащане не правим нищо
        if(empty($dealInfo->agreed->downpayment)) return;
        
        if(empty($form->rec->id)){
        	
        	// Поставяне на дефолт стойностти
        	self::getDefaultDpData($form);
        }
        
        // Ако има експедирано, не се показват полетата за начисляване
    	if($form->rec->dpOperation == 'accrued' && $form->dealInfo->shipped->amount){
    		
    		return;
    	}
    	
        // Показване на полетата за авансовите плащания
        $form->setField('dpAmount',"input,mandatory,unit=|*{$rec->currencyId} |без ДДС|*");
        $form->setField('dpOperation','input');
        
        // Показване на закръглената сума
        $form->rec->dpAmount = currency_Currencies::round($form->rec->dpAmount / $form->rec->rate);
    }
    
    
    /**
     * Подготвя дефолт стойностите за авансовите плащания
     * 
     * @param core_Form $form
     */
    private static function getDefaultDpData(core_Form &$form)
    {
    	// Договореното до момента
    	$agreed   = $form->dealInfo->agreed;
    	$paid     = $form->dealInfo->paid;
    	$invoiced = $form->dealInfo->invoiced;
    	
    	// Ако има платен аванс ръководим се по него, ако няма по договорения
    	$downpayment = (empty($paid->downpayment)) ? $agreed->downpayment : $paid->downpayment;
    	
    	// Ако няма фактуриран аванс
    	if(empty($invoiced->downpayment)){
    			
    		// Начисляване на аванса
    		$dpAmount = $downpayment;
    		$dpOperation = 'accrued';
    	} else {
    		
    		// Ако има вече начислен аванс, начисляваме останалото за начисляване
    		$dpAmount = ($downpayment - $invoiced->downpayment);
    		$dpOperation = 'accrued';
    	}
    	
    	// Ако всичко е начислено, приспадаме аванса
    	if(round($dpAmount) == 0){
    		$dpAmount = -1 * ($invoiced->downpayment - $invoiced->downpaymentDeducted);
    		$dpOperation = 'deducted';
    	}
    	
    	// Слагане на изчислените дефолти
    	if(isset($dpAmount)){
    		$dpAmount = self::getDpWithoutVat($dpAmount, $form->rec);
    		$form->setDefault('dpAmount', $dpAmount);
    	}
    	
    	if($dpOperation){
    		$form->setDefault('dpOperation', $dpOperation);
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
        // Ако сме в детайла пропускаме
    	if($mvc->Master) return;
    	
    	if(empty($form->dealInfo)) return;
    	
    	if ($form->isSubmitted()) {
        	$rec      = &$form->rec;
        	$agreed   = $form->dealInfo->agreed;
        	$paid     = $form->dealInfo->paid;
        	$invoiced = $form->dealInfo->invoiced;
        	$shipped  = $form->dealInfo->shipped;
        	
        	if($rec->dpOperation == 'accrued'){
        		
        		if($shipped->amount) {
        			
        			// Ако има експедирано, не се начислява аванс
        			unset($rec->dpOperation, $rec->dpAmount);
        			return;
        		}
        		
        		$downpayment = (empty($paid->downpayment)) ? $agreed->downpayment : $paid->downpayment;
        		$vat = acc_Periods::fetchByDate($rec->date)->vatRate;
        		if($rec->vatRate != 'yes' && $rec->vatRate != 'separate'){
    				$vat = 0;
    			}
        		
        		$downpayment = round(($downpayment - ($downpayment * $vat / (1 + $vat))) / $rec->rate, 2);
        		
	        	if($rec->dpAmount > $downpayment){
	            	$form->setError('dpAmount', "|Въведената сума е по-голяма от очаквания аванс от|* '{$downpayment}' |без ДДС|*");
	            }
	            
        		if($rec->dpAmount < 0){
        			$form->setError('dpAmount', 'При начисляване сумата трябва да е положителна');
        		}
        	} elseif($rec->dpOperation == 'deducted'){
        		
        		if($rec->dpAmount > 0){
        			$form->setError('dpAmount', 'При приспадане сумата трябва да е отрицателна');
        		}
        		
        		if(empty($invoiced->downpayment)){
        			$form->setWarning('dpOperation', 'Избрано е приспадане на аванс, без да има начислено ДДС за аванс');
        		} else {
        			if(abs($rec->dpAmount) > ($invoiced->downpayment - $invoiced->downpaymentDeducted)){
        				$form->setWarning('dpAmount', 'Приспаднатия аванс е по-голям от този който трябва да бъде приспаднат');
        			}
        		}
        	}
        	
        	$rec->dpAmount = $rec->dpAmount * $rec->rate;
        }
    }
    
    
    /**
     * Помощна ф-я връщаща сумата на аванса без ддс
     */
    private static function getDpWithoutVat($downpayment, $rec)
    {
    	$vat = acc_Periods::fetchByDate($rec->date)->vatRate;
    	
    	$vatAmount = ($rec->vatRate == 'yes' || $rec->vatRate == 'separate') ? ($downpayment) * $vat / (1 + $vat) : 0;
    	
    	return  $downpayment - $vatAmount;
    }
    
    
    /**
     * След подготовката на детайлите
     */
    public static function on_AfterPrepareDetail($mvc, &$res, &$data)
    {
    	$masterRec = $data->masterData->rec;
    	
    	// Ако е ДИ или КИ не правим нищо
    	if($masterRec->type != 'invoice') return;
    	
    	// Ако има сума на авансовото плащане и тя не е "0"
    	if($masterRec->dpAmount){
    		
    		// Сумата се обръща в валутата на фактурата
    		$dpAmount = currency_Currencies::round($masterRec->dpAmount / $masterRec->rate);
    		
    		// Обръщане на сумата във вербален вид
    		$Double = cls::get('type_Double');
    		$Double->params['decimals'] = 2;
    		$dpAmount = $Double->toVerbal($dpAmount);
    		
    		// Взависимост от операцията, показваме подходящо съобщение
    		$dpOperation = ($masterRec->dpOperation == 'accrued') ? tr("Авансово плащане") : tr("Приспадане на авансово плащане");
    		
    		// Записване в $data
    		$data->dpInfo = (object)array('dpAmount' => $dpAmount, 'dpOperation' => $dpOperation);
    	}
    }
    
    
    /**
     * След рендиране на лист таблицата
     */
    public static function on_AfterRenderListTable($mvc, &$tpl, &$data)
    {
    	// Ако сме в мастъра, пропускаме
    	if(empty($mvc->Master)) return;
    	
    	// Ако няма данни за показване на авансово плащане
    	if(empty($data->dpInfo)) return;
    	
    	// Добавяне на ред под детайла, показващ авансовото плащане
    	$masterRec = $data->masterData->rec;
    	
    	// Ако няма записи, да не се показва реда "няма записи"
    	if(empty($data->rows)){
    		$tpl->removeBlock('NO_ROWS');
    	}
    	
    	$colspan = count($data->listFields) - 2;
    	
    	$lastRow = new ET("<tr><td></td><td colspan='{$colspan}'>[#dpOperation#]<td style='text-align:right'>[#dpAmount#]</td></td></tr>");
    	$lastRow->placeObject($data->dpInfo);
    	
    	$tpl->append($lastRow, 'ROW_AFTER');
    }
    
    
    /**
     * Изпълнява се след създаване
     */
    public static function on_AfterCreate($mvc, $rec)
    {
    	if($mvc->Master) return;
    	
    	// Ако е ДИ или КИ не правим нищо
    	if($rec->type != 'invoice') return;
    	
    	// Ако има авансово плащане
    	if($rec->dpAmount && $rec->dpOperation == 'accrued'){
    		$mvc->updateMaster($rec->id);
    		
    		// Така спираме изпълнението на on_AfterCreate в фактурата
    		return FALSE;
    	}
    }
    
    
    /**
     * След калкулиране на общата сума
     */
    public static function on_AfterCalculateAmount($mvc, &$res, &$recs, &$masterRec)
    {
    	if(!isset($masterRec->dpAmount)) return;
    	$total = &$masterRec->_total;
    	
    	// Ако няма детайли, инстанцираме обекта
    	if(!$total){
    		$total = (object)array('amount' => 0, 'vat' => 0, 'discount' => 0);
    	}
    	
    	// Колко е ддс-то
    	$vat = acc_Periods::fetchByDate($masterRec->date)->vatRate;
    	if($masterRec->vatRate != 'yes' && $masterRec->vatRate != 'separate'){
    		$vat = 0;
    	}
    	
    	// Умно закръгляне на сумите
    	$dpVat = currency_Currencies::round($masterRec->dpAmount * $vat / $masterRec->rate);
    	$dpAmount = currency_Currencies::round($masterRec->dpAmount / $masterRec->rate);
    	
    	// Добавяне на авансовите данни в тотала
    	$total->vat    += $dpVat;
    	$total->amount += $dpAmount;
    }
}