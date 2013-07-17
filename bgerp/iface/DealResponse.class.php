<?php

/**
 * Информацията, която документ допринася към сделка
 * 
 * @category  bgerp
 * @package   bgerp
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_iface_DealResponse
{
    const TYPE_PURCHASE = 'purchase';
    const TYPE_SALE     = 'sale';
    
    /**
     * Тип на сделката
     * 
     * @var enum(bgerp_iface_DealResponse::TYPE_PURCHASE, bgerp_iface_DealResponse::TYPE_SALE)
     */
    public $dealType;

    
    /**
     * Информация за запитване
     * 
     * @var bgerp_iface_DealAspect
     */
    public $inquired;
    
    
    /**
     * Информация за оферта
     * 
     * @var bgerp_iface_DealAspect
     */
    public $quoted;
    
    
    /**
     * Информация за договорената (одобрена от поръчителя и изпълнителя) сделка
     * 
     * @var bgerp_iface_DealAspect
     */
    public $agreed;
    
    
    /**
     * Информация за експедирана стока по сделката
     * 
     * @var bgerp_iface_DealAspect
     */
    public $shipped;
    
    
    /**
     * Информация за плащане по сделката
     * 
     * @var bgerp_iface_DealAspect
     */
    public $paid;
    
    
    /**
     * Информация за фактуриране
     * 
     * @var bgerp_iface_DealAspect
     */
    public $invoiced;
}
