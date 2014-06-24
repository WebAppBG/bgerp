<?php
/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа purchase_Invoices
 *
 * @category  bgerp
 * @package   purchase
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class purchase_transaction_Invoice
{
    /**
     * 
     * @var purchase_Invoices
     */
    public $class;
    
    
    /**
     * @see acc_TransactionSourceIntf::getTransaction
     */
    public function finalizeTransaction($id)
    {
    	$rec = $this->class->fetchRec($id);
    	$rec->state = 'active';
    
    	if ($this->class->save($rec)) {
    		$this->class->invoke('AfterActivation', array($rec));
    		
    		// Нотификация към пораждащия документ, че нещо във веригата
    		// му от породени документи се е променило.
    		if ($origin = doc_Threads::getFirstDocument($rec->threadId)) {
    			$rec = new core_ObjectReference($this->class, $rec);
    			$origin->getInstance()->invoke('DescendantChanged', array($origin, $rec));
    		}
    	}
    }
    
    
    /**
     *  Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
     *  Създава транзакция която се записва в Журнала, при контирането
     *
     *  Dt: 4531 - Начислен ДДС за покупките
     *  Ct: 401  - Задължения към доставчици
     */
    public function getTransaction($id)
    {
    	// Извличаме записа
    	expect($rec = $this->class->fetchRec($id));
    	$cloneRec = clone $rec;
    
    	// Създаване / обновяване на перото за контрагента
    	$contragentClass = doc_Folders::fetchCoverClassName($cloneRec->folderId);
    	$contragentId    = doc_Folders::fetchCoverId($cloneRec->folderId);
    
    	$result = (object)array(
    			'reason'  => "Входяща фактура №{$rec->number}", // основанието за ордера
    			'valior'  => $rec->date,   // датата на ордера
    			'entries' => array(),
    	);
    
    	// Ако е ДИ или КИ се посочва към коя фактура е то
    	if($rec->type != 'invoice') {
    		$origin = $this->class->getOrigin($rec);
    		$type = $this->class->getVerbal($rec, 'type');
    		$result->reason = "{$type} към Фактура №" . str_pad($origin->fetchField('number'), '10', '0', STR_PAD_LEFT);
    	}
    	 
    	$entries = array();
    	$debitAccId  = '4531';
    	$creditAccId = '4530';
    
    	if(isset($cloneRec->vatAmount)){
    		$entries[] = array(
    				'amount' => currency_Currencies::round($cloneRec->vatAmount) * (($rec->type == 'credit_note') ? -1 : 1),  // равностойноста на сумата в основната валута
    
    				'credit' => array($creditAccId),
    
    				'debit' => array($debitAccId),
    		);
    	}
    
    	$result->entries = $entries;
    	 
    	return $result;
    }
}