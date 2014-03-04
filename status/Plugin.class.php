<?php


/**
 * Плъгин за работа със статус съобщения
 *
 * @category  vendors
 * @package   status
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class status_Plugin extends core_Plugin
{
    
    
    /**
     * Добавя статус съобщение към избрания потребител
     * 
     * @param core_Mvc $mvc
     * @param integer $res
     * @param string $text - Съобщение, което ще добавим
     * @param unknown_type $type - Типа на съобщението - success, notice, warning, error
     * @param unknown_type $userId - Потребителя, към когото ще се добавя. Ако не е подаден потребител, тогава взема текущия потребител.
     * @param unknown_type $lifeTime - След колко време да е неактивно
     */
    function on_AfterNewStatus($mvc, &$res, $text, $type, $userId, $lifeTime)
    {
        // Добавяме съобщението
        $res = status_Messages::newStatus($text, $type, $userId, $lifeTime);
    }
    
    
    /**
     * Абонира за извличане на статус съобщения
     * 
     * @param core_Mvc $mvc
     * @param core_ET $tpl
     */
    function on_AfterSubscribe($mvc, &$tpl)
    {
        // Абонираме
        $tpl = status_Messages::subscribe();
    }
}
