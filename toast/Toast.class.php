<?php 


/**
 * Плъгин за визуализиране на статус съобщенията, като toast съобщянията при Android
 *
 * @category  vendors
 * @package   toast
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class toast_Toast extends core_Plugin
{
    
    
    /**
     * Абонира за показване на статус съобщения
     * 
     * Изпълнява се преди Subscribe_ метода
     * Ако javascript' а не е активен, прескача изпълнението на метода.
     * Ако е активен тогава се изпълнява.
     * 
     * @param object $mvc
     * @param core_ET $tpl
     *  
     * @return FALSE - За да не изпълняват други функции (show_)
     */
    function on_BeforeSubscribe(&$mvc, &$tpl)
    {
        //Проверяваме дали е включн javascript'a.
        //Ако не е връщаме TRUE, за да може да се изпълнят другите функции
        if (!Mode::is('javascript', 'yes')) return TRUE;
        
        //Създаваме шаблона
        $tpl = new ET();
        
        //Вземаме текущата версия на външния пакет
        $conf = core_Packs::getConfig('toast');
        $version = $conf->TOAST_MESSAGE_VERSION;
        
        // Активираме JQuery
        jquery_Jquery::enable($tpl);
        
        //Добавяме JS и CSS необходими за работа на статусите
        $tpl->push("toast/{$version}/javascript/jquery.toastmessage.js", 'JS');
        $tpl->push("toast/{$version}/resources/css/jquery.toastmessage.css", 'CSS');
        
        // Ако е регистриран потребител
        if (haveRole('user')) {
            
            // Абонираме статус съобщенията
            core_Ajax::subscribe($tpl, array('toast_Toast', 'getStatuses'), 'status', 5000);
        }
        
        // Извлича статусите веднага след обновяване на страницата
        core_Ajax::subscribe($tpl, array('toast_Toast', 'getStatuses'), 'statusOnce', 0);
        
        // Връщаме FALSE за да не се изпълнява метода
        return FALSE;
    }
    
    
    /**
     * Екшън, който се вика по AJAX и показва статус съобщенията
     */
    function act_GetStatuses()
    {
        // Ако заявката е по AJAX
        if (Request::get('ajax_mode')) {
            
            // Времето на отваряне на таба
            $hitTime = Request::get('hitTime', 'int');
            
            // Време на бездействие
            $idleTime = Request::get('idleTime', 'int');
            
            // Всички активни статуси за текущия потребител, след съответното време
            $toastJs = static::getStatusesJS($hitTime, $idleTime);
            
            // Ако няма нищо за показване
            if (!$toastJs) return array();
            
            return $toastJs;
        }
    }
    
    
    /**
     * Връща javascript за показване на статус съобщения
     * 
     * @param integer $hitTime - Timestamp на показване на страницата
     * @param integer $idleTime - Време на бездействие на съответния таб
     * 
     * @return string - javascript за показване на статус съобщения
     */
    static function getStatusesJS($hitTime, $idleTime)
    {
        // Всички активни статуси за текущия потребител
        $notifArr = status_Messages::getStatuses($hitTime, $idleTime);
        
        // Броя на намерените статуси
        $countArr = count($notifArr);
        
        // JS, който ще се вика
        $resStatus = array();
        
        foreach ($notifArr as $val) {
            
            // Всеки следващ статус със закъсенине + 1 секунди
            $timeOut += (!$timeOut) ? 1 : 1000;
            
            // Ако статусите за показване са повече от 3 или текста е дълъг
            if (($countArr > 3) || (mb_strlen(strip_tags($val['text'])) > 150)) {
                
                // Статусите да са лепкави (да не се премахват след определено време от екрана)
                $sticky = TRUE;
                $stayTime = 10000;
            } else {
                
                // Лепкавостта на статусите да се определя от вида на статуса
                $sticky = static::isSticky($val['type']);
                
                // Времето за оставане на екрана да се определя от типа на статуса (само за тези, които не са лепкави)
                $stayTime = static::getStayTime($val['type']);
            }
            
            // Данни за показване на статус съобщение
            $statusData = array();
            $statusData['text'] = addslashes($val['text']);
            $statusData['type'] = $val['type'];
            $statusData['timeOut'] = $timeOut;
            $statusData['isSticky'] = (int)$sticky;
            $statusData['stayTime'] = $stayTime;
            
            $toastObj = new stdClass();
            $toastObj->func = 'showToast';
            $toastObj->arg = $statusData;
            
            $resStatus[] = $toastObj;
        }
        
        return $resStatus;
    }
        
    
    /**
     * В зависимост от типа определяме дали статуса е да лепкав или не (да не се маха от екрана)
     * 
     * @param string $type - Типа на статуса
     * 
     * @return boolean - Дали статус съобщението да е лепкаво или не
     */
    static function isSticky($type)
    {
        // В зависимост от типа определяме дали е да лепкав или не
        switch ($type) {
            case 'success':
                $sticky = FALSE;
            break;
            
            case 'notice':
                $sticky = FALSE;
            break;
            
            case 'warning':
                $sticky = TRUE;
            break;
            
            case 'error':
                $sticky = TRUE;
            break;
            
            default:
                $sticky = TRUE;
            break;
        }
        
        return $sticky;
    }
    
    
    /**
     * Определя дали е статуса да е лепкав в зависимост от подадения тип
     * 
     * @param string $type - Типа на статуса
     * 
     * @return integer - Колко дълго да се показва статуса на екрана
     */
    static function getStayTime($type)
    {
        // В зависимост от типа определяме времето на престой на екрана
        switch ($type) {
            case 'success':
                $time = 6000;
            break;
            
            case 'notice':
                $time = 8000;
            break;
            
            case 'warning':
                $time = 15000;
            break;
            
            case 'error':
                $time = 50000;
            break;
            
            default:
                 $time = 5000;
            break;
        }
        
        return $time;
    }
}
