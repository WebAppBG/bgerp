<?php
/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа deals_CreditDocuments
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class deals_transaction_CreditDocument
{
    /**
     * 
     * @var deals_CreditDocuments
     */
    public $class;
    
    
    /**
     *  Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
     *  Създава транзакция която се записва в Журнала, при контирането
     */
    public function getTransaction($id)
    {
    	// Извличаме записа
    	expect($rec = $this->class->fetchRec($id));
    	expect($origin = $this->class->getOrigin($rec));
    	
    	if($rec->isReverse == 'yes'){
    		// Ако документа е обратен, правим контировката на прехвърлянето на взимане но с отрицателен знак
    		$entry = deals_transaction_DebitDocument::getReverseEntries($rec, $origin);
    	} else {
    		
    		// Ако документа не е обратен, правим нормална контировка на прехвърляне на задължение
    		$entry = $this->getEntry($rec, $origin);
    	}
    	 
    	// Подготвяме информацията която ще записваме в Журнала
    	$result = (object)array(
    			'reason' => $rec->name, // основанието за ордера
    			'valior' => $rec->valior,   // датата на ордера
    			'entries' => array($entry)
    	);
    	 
    	return $result;
    }


    /**
     * @param int $id
     * @return stdClass
     * @see acc_TransactionSourceIntf::getTransaction
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
     * Връща записа на транзакцията
     */
    private function getEntry($rec, $origin, $reverse = FALSE)
    {
    	$amount = round($rec->rate * $rec->amount, 2);
    	$dealInfo = $origin->getAggregateDealInfo();
    	$dealRec = deals_Deals::fetch($rec->dealId);
    	
    	// Ако е обратна транзакцията, сумите и к-та са с минус
    	$sign = ($reverse) ? -1 : 1;
    	
    	// Дебитираме разчетната сметка на сделката, начало на нишка
    	$debitArr = array($rec->debitAccount,
    						array($rec->contragentClassId, $rec->contragentId),
    						array($origin->className, $origin->that),
    						array('currency_Currencies', currency_Currencies::getIdByCode($dealInfo->get('currency'))),
    						'quantity' => $sign * round($amount / $dealInfo->get('rate'), 2));
    	
    	// Кредитираме разчетната сметка на избраната финансова сделка
    	$creditArr = array($rec->creditAccount,
    							array($dealRec->contragentClassId, $dealRec->contragentId),
    							array($dealRec->dealManId, $rec->dealId),
    							array('currency_Currencies', currency_Currencies::getIdByCode($dealRec->currencyId)),
    							'quantity' => $sign * round($amount / $dealRec->currencyRate, 2));
    	
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