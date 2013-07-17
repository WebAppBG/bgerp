<?php

/**
 * Информация за плащане по сделка
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 */
class bgerp_iface_DealPayment
{
    /**
     * Начин на плащане
     *
     * @var int key(mvc=salecond_PaymentMethods)
     * @see salecond_PaymentMethods
     */
    public $method;


    /**
     * 3-буквен ISO код на валута
     *
     * @var string
     */
    public $currency;


    /**
     * Банкова сметка (ако $method указва плащане по банков път)
     *
     * @var int key(mvc=bank_Accounts)
     * @see bank_Accounts
     */
    public $bankAccountId;


    /**
     * Каса (ако $method указва плащане в брой)
     *
     * @var int key(mvc=cash_Cases)
     * @see cash_Cases
     */
    public $caseId;
}
