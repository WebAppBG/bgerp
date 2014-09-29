<?php

cls::load('acc_strategy_Strategy');


/**
 * Клас 'acc_strategy_WAC' - за средно притеглена цена
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_strategy_WAC extends acc_strategy_Strategy
{
    protected $quantity = 0;
    protected $amount = 0;
    
    
    /**
     * Захранване на стратегията с данни
     *
     * @param double $quantity
     * @param double $amount
     */
    function feed($quantity, $amount)
    {
        // Ако сумата или к-то са отрицателни не захранваме стратегията
    	if($quantity < 0 || $amount < 0) return;
    	
        $this->quantity += $quantity;
        $this->amount += $amount;
    }
    
    
    /**
     * Връща сумата спрямо количеството
     */
    function consume($quantity)
    {
        if ($quantity == 0) {
            return 0;
        }
        
        if ($this->quantity == 0) {
            return FALSE;
        }
        
        return $quantity * ($this->amount / $this->quantity);
    }
}
