<?php



/**
 * Покупки - опаковка
 *
 *
 * @category  bgerp
 * @package   purchase
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class purchase_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
        
        
        $this->TAB('purchase_Offers', 'Оферти', 'ceo,purchase');
        $this->TAB('purchase_Requests', 'Покупки', 'ceo,purchase');
        $this->TAB('purchase_Debt', 'Задължения', 'ceo,purchase');
  
        
        $this->title = 'Покупки « Доставки';
        Mode::set('menuPage', 'Доставки:Покупки');
    }
}