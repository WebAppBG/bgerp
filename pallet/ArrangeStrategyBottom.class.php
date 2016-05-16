<?php



/**
 * Стратегия за подреждане на склада 'pallet_ArrangeStrategyBottom'
 *
 *
 * @category  bgerp
 * @package   pallet
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class pallet_ArrangeStrategyBottom
{
    
    
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'store_ArrangeStrategyBottom';
	
	
    /**
     * Какви интерфeйси поддържа този мениджър
     */
    var $interfaces = 'store_iface_ArrangeStrategyIntf';
    
    
    /**
     * Връща автоматично изчислено палет място
     *
     * @param int $palletId
     */
    function getAutoPalletPlace($productId) {
        // Взема селектирания склад
        $selectedStoreId = store_Stores::getCurrent();
        
        $palletPlaceAuto = "1-A-1";
        
        return $palletPlaceAuto;
    }
}