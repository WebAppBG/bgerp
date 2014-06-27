<?php
/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа cash_Rko
 *
 * @category  bgerp
 * @package   cash
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class cash_transaction_Rko
{
    /**
     * 
     * @var cash_Rko
     */
    public $class;
    
    
    /**
     * @param int $id
     * @return stdClass
     * @see acc_TransactionSourceIntf::getTransaction
     */
    public function finalizeTransaction($id)
    {
    	$rec = $this->class->fetchRec($id);
    	$rec->state = 'closed';
    
    	$this->class->save($rec);
    }
    
    
    /**
     *  Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
     *  Създава транзакция която се записва в Журнала, при контирането
     */
    public function getTransaction($id)
    {
    	// Извличаме записа
    	expect($rec = $this->class->fetchRec($id));
    
    	$origin = $this->class->getOrigin($rec);
    	
    	if($rec->isReverse == 'yes'){
    		
    		// Ако документа е обратен, правим контировката на ПКО-то но с отрицателен знак
    		$entry = cash_transaction_Pko::getReverseEntries($rec, $origin);
    	} else {
    		
    		// Ако документа не е обратен, правим нормална контировка на РКО
    		$entry = $this->getEntry($rec, $origin);
    	}
    	
    	// Подготвяме информацията която ще записваме в Журнала
    	$result = (object)array(
    			'reason'  => $rec->reason,   // основанието за ордера
    			'valior'  => $rec->valior,   // датата на ордера
    			'entries' => array($entry)
    	);
    
    	return $result;
    }
    
    
    /**
     * Връща записа на транзакцията
     */
    private function getEntry($rec, $origin, $reverse = FALSE)
    {
    	$dealInfo = $origin->getAggregateDealInfo();
    	$amount = round($rec->rate * $rec->amount, 2);
    	
    	// Ако е обратна транзакцията, сумите и к-та са с минус
    	$sign = ($reverse) ? -1 : 1;
    	
    	// Дебита е винаги във валутата на пораждащия документ,
    	$debitCurrency = currency_Currencies::getIdByCode($dealInfo->agreed->currency);
    	$debitQuantity = round($amount / $dealInfo->agreed->rate, 2);
    	
    	// Дебитираме разчетната сметка
    	$debitArr = array($rec->debitAccount,
    						array($rec->contragentClassId, $rec->contragentId),
    						array($origin->className, $origin->that),
    						array('currency_Currencies', $debitCurrency),
    						'quantity' => $sign * $debitQuantity);
    	
    	// Кредитираме касата
    	$creditArr = array($rec->creditAccount,
    							array('cash_Cases', $rec->peroCase),
    							array('currency_Currencies', $rec->currencyId),
    							'quantity' => $sign * $rec->amount);
    	
    	$entry = array('amount' => $sign * $amount, 'debit' => $debitArr, 'credit' => $creditArr,);
    	
    	return $entry;
    }
    
    
    /**
     * Връща обратна контировка на стандартната
     */
    public static function getReverseEntries($rec, $origin)
    {
    	$self = cls::get(get_called_class());
    	
    	return $self->getEntry($rec, $origin, TRUE);
    }
}