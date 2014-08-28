<?php
/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа store_Receipts
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class store_transaction_Receipt
{
    /**
     * 
     * @var purchase_Purchases
     */
    public $class;
    
    
    /**
     * Генериране на счетоводните транзакции, породени от складова разписка
     * Заприхождаване на артикул: Dt:302 или Dt:321
     *	  
     *	  Dt: 302. Суровини и материали 	  (Склад, Суровини и Материали) - за вложимите продукти
     *	  	  321. Стоки и Продукти 		  (Склад, Стоки и Продукти) - за всички останали складируеми продукти
     *
     *    Ct: 401. Задължения към доставчици (Доставчик, Валути)
     *
     * @param int|object $id първичен ключ или запис на покупка
     * @return object NULL означава, че документа няма отношение към счетоводството, няма да генерира
     *                счетоводни транзакции
     * @throws core_exception_Expect когато възникне грешка при генерирането на транзакция               
     */
    public function getTransaction($id)
    {
        $entries = array();
        
        $rec = $this->fetchShipmentData($id);
        
        $origin = $this->class->getOrigin($rec);
        
        // Всяка СР трябва да има поне един детайл
        if (count($rec->details) > 0) {
        	
        	if($rec->isReverse == 'yes'){
        		
        		// Ако СР е обратна, тя прави контировка на ЕН но с отрицателни стойностти
        		$reverseSource = cls::getInterface('acc_TransactionSourceIntf', 'store_ShipmentOrders');
        		$entries = $reverseSource->getReverseEntries($rec, $origin);
        	} else {
        		
        		// Ако СР е права, тя си прави дефолт стойностите
        		$entries = $this->getDeliveryPart($rec, $origin);
        	} 
        }
        
        $transaction = (object)array(
            'reason'  => 'Складова разписка №' . $rec->id,
            'valior'  => $rec->valior,
            'entries' => $entries, 
        );
        
        return $transaction;
    }
    
    
    /**
     * Финализиране на транзакцията
     */
    public function finalizeTransaction($id)
    {
        $rec = $this->class->fetchRec($id);
        
        $rec->state = 'active';
        
        if ($this->class->save($rec)) {
            $this->class->invoke('AfterActivation', array($rec));
        }
    }
    
    
    /**
     * Помощен метод за извличане на данните на СР - мастър + детайли
     * 
     * Детайлите на СР (продуктите) са записани в полето-масив 'details' на резултата 
     * 
     * @param int|object $id първичен ключ или запис на СР
     * @param object запис на СР (@see store_Receipts)
     */
    protected function fetchShipmentData($id)
    {
        $rec = $this->class->fetchRec($id);
        
        $rec->details = array();
        
        if (!empty($rec->id)) {
            // Извличаме детайлите на покупката
            $detailQuery = store_ReceiptDetails::getQuery();
            $detailQuery->where("#receiptId = '{$rec->id}'");
            $rec->details  = array();
            
            while ($dRec = $detailQuery->fetch()) {
                $rec->details[] = $dRec;
            }
        }
        
        return $rec;
    }
    
    
    /**
     * Помощен метод - генерира доставната част от транзакцията за покупка
     * Вкарване на стоката в склада (в някои случаи)
     * 
     *	  Dt: 302. Суровини и материали 	  (Склад, Суровини и Материали) - за вложимите продукти
     *	  	  321. Стоки и Продукти 		  (Склад, Стоки и Продукти) - за всички останали складируеми продукти
     *
     *    Ct: 401. Задължения към доставчици (Доставчик, Сделки, Валути)
     *    
     * @param stdClass $rec
     * @return array
     */
    protected function getDeliveryPart($rec, $origin, $reverse = FALSE)
    {
        $entries = array();
        $sign = ($reverse) ? -1 : 1;
        
        expect($rec->storeId, 'Генериране на експедиционна част при липсващ склад!');
        $currencyRate = $this->getCurrencyRate($rec);
        $currencyCode = ($rec->currencyId) ? $rec->currencyId : $this->class->fetchField($rec->id, 'currencyId');
        $currencyId   = currency_Currencies::getIdByCode($currencyCode);
        deals_Helper::fillRecs($this->class, $rec->details, $rec);
        
        
        foreach ($rec->details as $detailRec) {
        	$pInfo = cls::get($detailRec->classId)->getProductInfo($detailRec->productId);
        	
        	if($rec->chargeVat == 'yes'){
        		$ProductManager = cls::get($detailRec->classId);
            	$vat = $ProductManager->getVat($detailRec->productId, $rec->valior);
            	$amount = $detailRec->amount - ($detailRec->amount * $vat / (1 + $vat));
        	} else {
        		$amount = $detailRec->amount;
        	}
        	
        	$amount = ($detailRec->discount) ?  $amount * (1 - $detailRec->discount) : $amount;
        	
        	// Ако е материал дебит 302 иначе 321
        	$debitAccId = (isset($pInfo->meta['materials'])) ? '302' : '321';
        		
        	$debit = array(
                  $debitAccId, 
                       array('store_Stores', $rec->storeId), // Перо 1 - Склад
                       array($detailRec->classId, $detailRec->productId),  // Перо 2 - Артикул
                  'quantity' => $sign * $detailRec->quantity, // Количество продукт в основната му мярка
            );
        	
        	$entries[] = array(
        		 'amount' => $sign * $amount * $rec->currencyRate,
        		 'debit'  => $debit,
	             'credit' => array(
	                   $rec->accountId, 
                       array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Доставчик
	             	   array($origin->className, $origin->that),		   // Перо 2 - Сделка
                       array('currency_Currencies', $currencyId),          // Перо 3 - Валута
                    'quantity' => $sign * $amount, // "брой пари" във валутата на покупката
	             ),
	        );
        }
        
    	if($this->class->_total->vat){
        	$vatAmount = $this->class->_total->vat * $currencyRate;
        	$entries[] = array(
                'amount' => $sign * $vatAmount, // В основна валута
                
                'credit' => array(
                    $rec->accountId,
                        array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
                		array($origin->className, $origin->that),			// Перо 2 - Сделка
                        array('currency_Currencies', acc_Periods::getBaseCurrencyId($rec->valior)), // Перо 3 - Валута
                    'quantity' => $sign * $vatAmount, // "брой пари" във валутата на продажбата
                ),
                
                'debit' => array(
                    '4530',
                		array($origin->className, $origin->that),
                ),
            );
        }
        
        return $entries;
    }
    
    
    /**
     * Курс на валутата на покупката към базовата валута за периода, в който попада продажбата
     * 
     * @param stdClass $rec запис на покупка
     * @return float
     */
    protected function getCurrencyRate($rec)
    {
        return currency_CurrencyRates::getRate($rec->valior, $rec->currencyId, NULL);
    }
    
    
    /**
     * Връща обратна контировка на стандартната
     */
    public function getReverseEntries($rec, $origin)
    {
    	$entries = $this->getDeliveryPart($rec, $origin, TRUE);
    	 
    	return $entries;
    }
}