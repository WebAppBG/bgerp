<?php



/**
 * Клас 'tcost_CostCalcIntf' - Интерфейс за класове, които определят цената за транспорт
 *
 *
 * @category  bgerp
 * @package   tcost
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class tcost_CostCalcIntf
{
	
	
    /**
     * Определяне на обемното тегло, на база на обема на товара
     * 
     * @param double $weight  - Тегло на товара
     * @param double $volume  - Обем  на товара
     *
     * @return double         - Обемно тегло на товара  
     */
    public function getVolumicWeight($weight, $volume)
    {
        return $this->class->getVolumicWeight($weight, $volume);
    }


    /**
     * Определяне цената за транспорт при посочените параметри
     *
     * @param int $productId         - ид на артикул
     * @param int $quantity          - количество
     * @param int $totalWeight       - Общо тегло на товара
     * @param int $toCountry         - id на страната на мястото за получаване
     * @param string $toPostalCode   - пощенски код на мястото за получаване
     * @param int $fromCountry       - id на страната на мястото за изпращане
     * @param string $fromPostalCode - пощенски код на мястото за изпращане
     *
     * @return double                - цена, която ще бъде платена за теглото на артикул
     */
    function getTransportFee($deliveryTermId, $productId, $quantity, $totalWeight, $toCountry, $toPostalCode, $fromCountry, $fromPostalCode)
    {
        return $this->class->getTransportFee($deliveryTermId, $productId, $quantity, $totalWeight, $toCountry, $toPostalCode, $fromCountry, $fromPostalCode);
    }
}