<?php

/**
 * Интерфейс за бизнес информация по сделка, носена от документ
 * 
 * Чрез този интерфейс документите "публикуват" информация за различни аспекти от сделката, 
 * в която участват - запитване, офериране, договор, експедиция, плащане, фактуриране и
 * (вероятно) други. 
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */

class bgerp_DealIntf
{
    /**
     * Информацията, която този документ допринася към сделка
     *
     * @param int $id ид на документ
     * @return bgerp_iface_DealResponse
     */
    public function getInfo($id)
    {
        return $this->class->getInfo($id);
    }
    
}