<?php

/**
 * Интерфейс за бизнес информация, обобщена за всички документи по една сделка
 * 
 * Някои мениджъри (напр. "Продажба" - sales_Sales) могат да обобщят информацията от всички
 * документи по сделката, които реализират bgerp_DealIntf. Чрез този метод тази информация
 * става достъпна за другите.
 * 
 * @category  bgerp
 * @package   bgerp
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */

class bgerp_DealAggregatorIntf
{
    /**
     * Обобщена бизнес информация отнасяща се за една сделка
     * 
     * Структурата на резултата е идентична с тази на @link bgerp_DealIntf::getInfo(), но 
     * семантиката на стойностите в него е различна. bgerp_DealIntf предоставя информация за
     * един конкретен документ, докато тук информацията е обобщена (сумирана) за всички
     * документи по сделката.
     *
     * @param int $id ид на документ
     * @return bgerp_iface_DealResponse
     */
    public function getAggregateDealInfo($id)
    {
        return $this->class->getAggregateDealInfo($id);
    }
    
}