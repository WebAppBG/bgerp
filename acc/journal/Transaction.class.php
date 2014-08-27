<?php
use Behat\Mink\Exception\Exception;

class acc_journal_Transaction
{
    /**
     * 
     * @var array
     */
    protected $entries = array(); 
    
    
    /**
     * 
     * @var stdClass
     */
    public $rec;
    
    
    /**
     * @var acc_Journal
     */
    public $Journal;

    
    /**
     * @var acc_JournalDetails
     */
    public $JournalDetals;
    
    
    /**
     * 
     * @param float|array|object $amount ако е float се приема за обща стойност на транзакцията;
     *                                   в противен случай - за данни, резултат от извикването
     *                                   на @see acc_TransactionSourceIntf::getTransaction()
     *                                   
     * @see acc_TransactionSourceIntf::getTransaction()                                   
     */
    public function __construct($amount = NULL)
    {
        $rec = new stdClass();
        
        if (isset($amount)) {
            if (is_numeric($amount)) {
                $this->rec->totalAmount = floatval($amount);
            } else {
                $this->init($amount);
            }
        }
        
        $this->Journal = cls::get('acc_Journal');
        $this->JournalDetails = cls::get('acc_JournalDetails');
    }
    
    
    /**
     * Инициализира транзакция, с данни получени от acc_TransactionSourceIntf::getTransaction()
     * 
     * @param stdClass $data
     * @return void
     */
    public function init($data)
    {
        $data = (object)$data;
        
        $this->entries = array();
        
        acc_journal_Exception::expect(isset($data->entries) && is_array($data->entries), 'Няма ентрита');
        
        foreach ($data->entries as $entryData) {
            $this->add()->initFromTransactionSource($entryData);
        }
       
        unset($data->entries);
        $this->rec = clone $data;
    }
    
    
    /**
     * Добавя нов ред в транзакция
     * 
     * @param acc_journal_Entry $entry
     * @return acc_journal_Entry $entry
     */
    public function add($entry = NULL)
    {
        if (!isset($entry) || !($entry instanceof acc_journal_Entry)) {
            $entry = new acc_journal_Entry($entry);
        }
        
        $this->entries[] = $entry;
        
        return $entry;
    }

    
    /**
     * Проверка на валидността на счетоводна транзакция
     * 
     * @return boolean
     * @throws acc_journal_Exception
     */
    public function check()
    {
        /* @var $entry acc_journal_Entry */ 
    	if(count($this->entries)){
	    	foreach ($this->entries as $entry) {
	            try {
	                $entry->check();
	            } catch (acc_journal_Exception $ex) {
	                throw new acc_journal_Exception('Невалиден ред на транзакция: ' . $ex->getMessage());
	            }
	        }
    	}
        
        if (isset($this->rec->totalAmount)) {
            $sumItemsAmount = $this->amount();
            $roundTotal = core_Math::roundNumber($this->rec->totalAmount);
            
            acc_journal_Exception::expect(trim($roundTotal) == trim($sumItemsAmount),
                "Несъответствие между изчислената ({$sumItemsAmount}) и зададената ({$roundTotal}) суми на транзакция");
        }
        
        return TRUE;
    }
    
    
    /**
     * Изчислява общата сума на транзакцията като сбор от сумите на отделните й редове
     * 
     * @return float
     */
    protected function amount()
    {
        $totalAmount = 0;
        
        /* @var $entry acc_journal_Entry */
        foreach ($this->entries as $entry) {
            $totalAmount += $entry->amount();
        }
        
        
        return core_Math::roundNumber($totalAmount);
    }
    
    
    /**
     * Записва транзакция в БД
     * 
     * @return boolean
     */
    public function save()
    {
        $this->check();

        if (!$this->begin()) {
            return FALSE;
        }

        try {
        	if(count($this->entries)){
	        	foreach ($this->entries as $entry) {
	                if (!$entry->save($this->rec->id)) {
	                    // Проблем при записването на детайл-запис. Rollback!!!
	                    $this->rollback();
	                    return FALSE;
	                }
	            }
        	}
            
            $this->commit();
        } catch (Exception $ex) {
            $this->rollback();
            throw $ex;
        }
        
        return TRUE;
    }
    
    
    /**
     * Стартира процеса на записване на транзакция
     * 
     * @return boolean
     */
    protected function begin()
    {
    	// Ако транзакцията е празна не се записва в журнала
        if($this->isEmpty()) return TRUE;
        
        // Начало на транзакция: създаваме draft мастър запис, за да имаме ключ за детайлите
        $this->rec->state = 'draft';
        $this->rec->totalAmount = $this->amount();
        
        if (!$this->Journal->save($this->rec)) {
            // Не стана създаването на мастър запис, аборт!
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    /**
     * Финализира транзакция след успешно записване
     * 
     * @return boolean
     */
    protected function commit()
    {
        // Транзакцията е записана. Активираме
        $this->rec->state = 'active';
        
        // Ако транзакцията е празна не се записва в журнала
        if($this->isEmpty()) return TRUE;
        
        return $this->Journal->save($this->rec);
    }
    
    
    /**
     * Изтрива частично записана транзакция
     * 
     * @return boolean
     */
    public function rollback()
    {
        $this->JournalDetails->delete("#journalId = {$this->rec->id}");
        $this->Journal->delete($this->rec->id);
        
        // Логваме в журнала
        acc_Articles::log("Rollback на ред '{$this->rec->id}' от журнала");
        
        return TRUE;
    }
    
    
    public function isEmpty()
    {
        return empty($this->entries);
    }
    
    
    /**
     * Генерира обратна транзакция
     */
    public function invert()
    {
        // Обратната транзакция е множество от обратните записи на текущата транзакция
        foreach ($this->entries as &$entry) {
            $entry->invert();
        }
    }
    
    
    /**
     * Добавя към записите на текущата транзакция всички записи на друга транзакция
     * 
     * @param acc_journal_Transaction $transaction
     */
    public function join(acc_journal_Transaction $transaction)
    {
        foreach ($transaction->entries as $entry) {
            $this->add($entry);
        }
    }
    
    
    /**
     * Кои са затворените пера в транзакцията
     */
    public function getClosedItems()
    {
    	$closedEntries = array();
    	if(isset($this->entries)){
    		
    		foreach ($this->entries as $entry){
    			$closedEntries += $entry->debit->getClosedItems();
    			$closedEntries += $entry->credit->getClosedItems();
    		}
    	}
    	
    	return $closedEntries;
    }
}