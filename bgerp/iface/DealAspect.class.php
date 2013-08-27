<?php

/**
 * Описание на един аспект от бизнес сделка, напр. запитване, офериране, договор, експедиция, 
 * плащане, фактуриране и (вероятно) други. 
 * 
 * @category  bgerp
 * @package   bgerp
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 *
 */
class bgerp_iface_DealAspect
{
    /**
     * Списък (масив) от характеристики на продукти
     *
     * @var array of bgerp_iface_DealProduct
     */
    public $products = array();


    /**
     * 3-буквен ISO код на валута
     *
     * @var string
     */
    public $currency;


    /**
     * Обща сума на съответната част от сделката - пари във валутата `$currency`
     *
     * @var double
     */
    public $amount = 0.0;


    /**
     * Информация за доставката
     *
     * @var bgerp_iface_DealDelivery
     */
    public $delivery;


    /**
     * Информация за плащането
     *
     * @var bgerp_iface_DealPayment
     */
    public $payment;
    
    
	public function __construct()
	{
		$this->delivery = new bgerp_iface_DealDelivery();
		$this->payment = new bgerp_iface_DealPayment();
	}


    /**
     * Добавя друг аспект (детайл) на сделката към текущия аспект
     * 
     * Използва се при изчисляване на обобщена информация по сделка
     * 
     * @param bgerp_iface_DealAspect $aspect
     */
    public function push(bgerp_iface_DealAspect $aspect)
    {
        foreach ($aspect->products as $p) {
            $this->pushProduct($p);
        }
        
        if (isset($aspect->delivery)) {
            $this->delivery = $aspect->delivery;
        }

        if (isset($aspect->payment)) {
            $this->payment = $aspect->payment;
        }

        if (isset($aspect->currency)) {
            $this->currency = $aspect->currency;
        }
        
        $this->amount += $aspect->amount;
    }
    
    
    public function pop(bgerp_iface_DealAspect $aspect)
    {
        foreach ($aspect->products as $p) {
            $this->popProduct($p);
        }
        
    }
    
    protected function pushProduct(bgerp_iface_DealProduct $p)
    {
        $found = $this->findProduct($p->productId, $p->getClassId(), $p->packagingId);
        
        if ($found) {
            $found->quantity += $p->quantity;
        } else {
            $this->products[] = clone $p;
        }
    }
    
    protected function popProduct(bgerp_iface_DealProduct $p)
    {
        $found = $this->findProduct($p->productId, $p->getClassId(), $p->packagingId);
        
        if ($found) {
            $found->quantity -= $p->quantity;
        } else {
            $q = $p->quantity;
            
            $p = clone $p;
            $p->quantity = -$q;
            
            $this->products[] = $p;
        }
    }
    
    public function findProduct($productId, $classId, $packagingId)
    {
        /* @var $p bgerp_iface_DealProduct */
        foreach ($this->products as $i=>$p) {
            if ($p->isIdentifiedBy($productId, $classId, $packagingId)) {
                return $p;
            }
        }
        
        return NULL;
    }
}
