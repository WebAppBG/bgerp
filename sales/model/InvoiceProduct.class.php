<?php

/**
 * 
 * @author developer
 * @property core_Manager $productClass клас (мениджър) на продукта, описан с този ред
 */
class sales_model_InvoiceProduct extends core_Model
{
    /**
     * @var string|int|core_Mvc
     */
    public static $mvc = 'sales_InvoiceDetails';
    
    /**
     * @var int key(mvc=sales_Sales)
     */
    public $invoiceId;
    
    /**
     * Ценова политика
     * 
     * @var int class(interface=price_PolicyIntf)
     */
    public $policyId;
    
    /**
     * ИД на продукт
     * 
     * @var int
     */
    public $productId;
    
    /**
     * Мярка
     * 
     * @var int key(mvc=cat_UoM)
     */
    public $uomId;
    
    /**
     * Опаковка (ако има)
     * 
     * @var int key(mvc=cat_Packagings)
     */
    public $packagingId;
    
    /**
     * Количество (в осн. мярка) в опаковката, зададена от 'packagingId'; Ако 'packagingId'
     * няма стойност, приема се за единица.
     * 
     * @var double
     */
    public $quantityInPack;
        
    /**
     * Количество (в основна мярка)
     * 
     * @var double
     */
    public $quantity;
        
    /**
     * Цена за единица продукт в основна мярка
     * 
     * @var double
     */
    public $price;
        
    /**
     * Забележка
     * 
     * @var double
     */
    public $note;
        
    /**
     * Сума
     * 
     * @var double
     */
    public $amount;
    
    protected function calc_productClass()
    {
        return cls::get($this->policyId)->getProductMan();
    }
}
