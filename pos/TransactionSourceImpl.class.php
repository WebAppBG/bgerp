<?php
/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа pos_eports
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class pos_TransactionSourceImpl
{
    /**
     * @var pos_Reports
     */
    public $class;
    
    
    /**
     * Връща транзакцията на бележката
     */
    public function getTransaction($id)
    {
        $rec = $this->class->fetchRec($id);
        $posRec = pos_Points::fetch($rec->pointId);
    	$entries = array();
        $totalVat = array();
        
        $paymentsArr = $productsArr = array();
    	if(!$rec->details){
    		$this->class->extractData($rec);
    	}
    	
    	if(count($rec->details['receiptDetails'])){
    		foreach ($rec->details['receiptDetails'] as $dRec){
    			if($dRec->action == 'sale'){
    				$productsArr[] = $dRec;
    			} elseif($dRec->action == 'payment'){
    				$paymentsArr[] = $dRec;
    			}
    		}
    	}
    	
    	// Генериране на записите
        $entries = array_merge($entries, $this->getTakingPart($rec, $productsArr, $totalVat, $posRec));
        
        $entries = array_merge($entries, $this->getPaymentPart($rec, $paymentsArr, $posRec));
        
        // Начисляване на ддс ако има
        if(count($totalVat)){
        	$entries = array_merge($entries, $this->getVatPart($rec, $totalVat, $posRec));
        }
        
        $transaction = (object)array(
            'reason'  => 'Отчет за POS продажба №' . $rec->id,
            'valior'  => $rec->createdOn,
            'entries' => $entries, 
        );
        
        if(empty($rec->id)){
        	unset($rec->details);
        }
        
        return $transaction;
    }
    
    
    /**
     * Генериране на записите от тип 1 (вземане от клиент)
     * 
     *    Dt: 411  - Вземания от клиенти               (Клиент, Валута)
     *    
     *    Ct: 701  - Приходи от продажби на Стоки и Продукти  (Клиенти, Стоки и Продукти)
     *    	  703  - Приходи от продажба на услуги 			  (Клиенти, Услуги)
     *        706  - Приходи от продажба на Суровини и Материали (Клиенти, Суровини и материали)
     * 
     * @param stdClass $rec    - записа
     * @param array $products  - продуктите
     * @param doube $totalVat  - общото ддс
     * @param stdClass $posRec - точката на продажба
     */
    protected function getTakingPart($rec, $products, &$totalVat, $posRec)
    {
    	$entries = $tmpVat = array();
    	
    	foreach ($products as $product) {
    		
    		$product->totalQuantity = round($product->quantity * $product->quantityInPack, 2);
    		$totalAmount   = currency_Currencies::round($product->amount);
    		if($product->param){
    			$totalVat[$product->contragentClassId ."|". $product->contragentId] += $product->param * $product->amount;
    		}
    		
	    	$currencyId = acc_Periods::getBaseCurrencyId($rec->createdOn);
    		$pInfo = cat_Products::getProductInfo($product->value);
    		$storable = isset($pInfo->meta['canStore']);
    		$convertable = isset($pInfo->meta['canConvert']);
    		
    		// Нескладируемите продукти дебит 703. Складируемите и вложими 706 останалите 701
    		$creditAccId = ($storable) ? (($convertable) ? '706' : '701') : '703';
    		$credit = array(
	              $creditAccId, 
	                    array($product->contragentClassId, $product->contragentId), // Перо 1 - Клиент
	                    array('cat_Products', $product->value), // Перо 2 - Артикул
	              'quantity' => $product->totalQuantity, // Количество продукт в основната му мярка
	        );
	        
    		$entries[] = array(
	        'amount' => $totalAmount, // Стойност на продукта за цялото количество, в основна валута
	        'debit' => array(
	            '411',  
	                array($product->contragentClassId, $product->contragentId), // Перо 1 - Каса
	                array('currency_Currencies', $currencyId), // Перо 3 - Валута
	            'quantity' => $totalAmount), // "брой пари" във валутата на продажбата
	        
	        'credit' => $credit,
	    	);
	    	
	    	if($storable){
	    		$entries = array_merge($entries, $this->getDeliveryPart($rec, $product, $posRec, $convertable));
	    	}
    	}
        
    	return $entries;
    }
    
    
    /**
     * Помощен метод - генерира доставната част от транзакцията за продажба (ако има)
     * Експедиране на стоката от склада (в някой случаи)
     *
     *    Dt: 701. Приходи от продажби на стоки и продукти     (Клиент, Стоки и Продукти)
     *    	  706. Приходи от продажба на суровини/материали   (Клиент, Суровини и материали)
     *    
     *    Ct: 321. Стоки и Продукти 	   (Склад, Стоки и Продукти)
     *    	  302. Суровини и материали    (Складове, Суровини и материали)
     *    
     * @param stdClass $rec        - записа
     * @param array $product       - артикула
     * @param stdClass $posRec     - точката на продажба
     * @param boolean $convertable - вложим ли е продукта
     * @return array 
     */
    protected function getDeliveryPart($rec, $product, $posRec, $convertable)
    {
        $entries = array();
        $creditAccId = ($convertable) ? '302' : '321';
        $debitAccId = ($convertable) ? '706' : '701';
        
        // После се отчита експедиране от склада
	    $entries[] = array(
			 'debit' => array(
			       $debitAccId,
			       		array($product->contragentClassId, $product->contragentId), // Перо 1 - Клиент
			            array('cat_Products', $product->value), // Перо 1 - Продукт
		           'quantity' => $product->totalQuantity),
			        
			 'credit' => array(
			        $creditAccId,
			            array('store_Stores', $posRec->storeId), // Перо 1 - Склад
			            array('cat_Products', $product->value), // Перо 1 - Продукт
		            'quantity' => $product->totalQuantity),
		);
		
		return $entries;
    }
    
    
    /**
     * Връща часта контираща ддс-то
     * 
     * 		Dt: 411.  Взимания от клиенти           (Клиенти, Валути)
     * 
     * 		Ct: 4532. Начислен ДДС за продажбите
     * 
     * @param stdClass $rec    - записа
     * @param double $totalVat - начисленото ддс
     * @param stdClass $posRec - точката на продажба
     */
    protected function getVatPart($rec, $totalVat, $posRec)
    {
    	$entries = array();
    	foreach ($totalVat as $index => $value){
    		$contragentArr = explode("|", $index);
    		
    		$entries[] = array(
	         'amount' => currency_Currencies::round($value), // равностойноста на сумата в основната валута
	            
	         'debit' => array(
	              '411',  
	            	 $contragentArr, // Перо 1 - Клиент
	            	 array('currency_Currencies', acc_Periods::getBaseCurrencyId($rec->createdOn)), // Валута в основна мярка
	              'quantity' => currency_Currencies::round($value), 
	            ),
	            
	        'credit' => array(
	              '4532',  
	              'quantity' => currency_Currencies::round($value),
	            )
	    	);
    	}
	    	
	    return $entries;
    }
    
    
	/**
     * Помощен метод - генерира платежната част от транзакцията за продажба (ако има)
     * 
     *    Dt: 501. Каси                  (Каса, Валута)
     *        
     *    Ct: 411. Вземания от клиенти   (Клиент, Валута)
     *    
     * @param stdClass $rec
     * @return array
     */
    protected function getPaymentPart($rec, $paymentsArr, $posRec)
    {
        $entries = array();
        
        // Продажбата съхранява валутата като ISO код; преобразуваме в ПК.
        $currencyId = acc_Periods::getBaseCurrencyId($rec->createdOn);
        
        foreach ($paymentsArr as $payment) {
        	$entries[] = array(
                'amount' => currency_Currencies::round($payment->amount), // В основна валута
                
                'debit' => array(
                    '501', // Сметка "501. Каси"
                        array('cash_Cases', $posRec->caseId),         // Перо 1 - Каса
                        array('currency_Currencies', $currencyId), // Перо 2 - Валута
                    'quantity' => currency_Currencies::round($payment->amount), // "брой пари" във валутата на продажбата
                ),
                
                'credit' => array(
                    '411', // Сметка "411. Вземания от клиенти"
                        array($payment->contragentClassId, $payment->contragentId), // Перо 1 - Клиент
                        array('currency_Currencies', $currencyId),          // Перо 2 - Валута
                    'quantity' => currency_Currencies::round($payment->amount), // "брой пари" във валутата на продажбата
                ),
            );
        }
            
        return $entries;
    }
    
    
	/**
     * Финализиране на транзакцията
     */
    public function finalizeTransaction($id)
    {
        $rec = $this->class->fetchRec($id);
        $rec->state = 'active';
        
        $id = $this->class->save($rec);
        $this->class->invoke('AfterActivation', array($rec));
        
        return $id;
    }
}