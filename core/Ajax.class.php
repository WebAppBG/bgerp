<?php


/**
 * Клас за работа с EFAE - Experta Framework Ajax Engine
 * 
 * @category  ef
 * @package   core
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class core_Ajax extends core_Mvc
{
    
    
    /**
     * Колко дни да остане в лога
     */
    static $logKeepDays = 3;
    
    
    /**
     * Екшън, който се вика по AJAX и извиква всички подадени URL-та
     */
    function act_Get()
    {
        // Ако не сме в DEBUG режим
        if (!isDebug()) {
            
            // Очаквае заявката да е по AJAX - да има такъв хедър
            if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
                core_Logs::log("Стартиране на core_Ajax::get() извън AJAX");
                expect(FALSE);
            }
        }
        
        // Масив с URL-та в JSON формат
        $subscribed = Request::get('subscribed');
        
        // Времето на извикване на страницата
        $hitTime = Request::get('hitTime', 'int');
        
        // Времето на извикване на страницата
        $idleTime = Request::get('idleTime', 'int');
        
        // Декодираме масива
        $subscribedArr = json_decode($subscribed);
        
        // URL от който се вика AJAX
        $parentUrl = Request::get('parentUrl');
        
        // Дали се вика по ajax
//        $ajaxMode = Request::get('ajax_mode');
        $ajaxMode = 1;
        
        // Ако няма нищо в масив, прекъсваме функцията
        if (!$subscribed) shutdown();
        
        // Резултатния масив
        $jResArr = array();
        
        // Стойности, които да се игнорират
        Request::ignoreParams(array('subscribed' => TRUE,
                					'parentUrl' => TRUE,
                					'idleTime' => TRUE,
                					'hitTime' => TRUE,
                					'ajax_mode' => TRUE,
                					'refreshUrl' => TRUE,
                					'divId' => TRUE));
        
        // Обхождаме всички подадедени локални URL-та
        foreach ((array)$subscribedArr as $name=>$url) {
            
            // Декодираме URL-то
            $url = urldecode($url);
            
            // Вземаме масива от локолното URL
            $urlArr = core_App::parseLocalUrl($url);
            
            // Добавяме параметър, който указва, че е стартиран по AJAX
            $urlArr['ajax_mode'] = $ajaxMode;
            
            // Ако е зададен hitTime
            if ($hitTime) {
                
                // Да се добави в URL-то
                $urlArr['hitTime'] = $hitTime;
            }
            
            // Да се добави в URL-то
            $urlArr['idleTime'] = $idleTime;
            
            // Добавяме URL-то в заявката
            $urlArr['parentUrl'] = $parentUrl;
            
            try {
                // Извикваме URL-то
                $resArr = Request::forward($urlArr);
                
            } catch (Exception $e) {
                
                // Записваме в лога
                core_Logs::add($this, NULL, "Грешка при вземане на данни за {$url} - {$e->getMessage()}", static::$logKeepDays);
                
                // Ако сме в дебъг режим и сме логнат
                if (isDebug() && haveRole('user')) {
                    
                    // Показваме статус съобщение
                    core_Statuses::newStatus("|Грешка при вземане на данни за|* {$url} - {$e->getMessage()}", 'warning');
                }
                
                continue;
            }
            
            // Ако няма масив или масива не е масива
            if (!is_array($resArr)) {
                
                // Записваме в лога резултата
                $resStr = core_Type::mixedToString($resArr);
                core_Logs::add($this, NULL, "Некоректен резултат за {$url} - {$resStr}", static::$logKeepDays);
                
                // Ако сме в дебъг режим и сме логнат
                if (isDebug() && haveRole('user')) {
                    
                    // Показваме статус съобщение
                    core_Statuses::newStatus("|Некоректен резултат за|* {$url}", 'warning');
                }
                 
                 continue;
            }
            
            // Обединяваме масивите
            $jResArr = array_merge($jResArr, $resArr);
        }
        
        // Нулираме масива за игнориране
        Request::resetIgnoreParams();
        
        // За да не се кешира
        header("Expires: Sun, 19 Nov 1978 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        
        // Указваме, че ще се връща JSON
        header('Content-Type: application/json');
        
        // Връщаме резултата в JSON формат
        echo json_encode($jResArr);
        
        // Прекратяваме процеса
        shutdown();
    }
    
    
    /**
     * Функция, която абонира дадено URL за извлича данни по AJAX
     * 
     * @param core_ET $tpl - Щаблон, към който ще се добавя
     * @param array $urlArr - Масив, от който ще се генерира локално URL
     * @param string $name - Уникално име
     * @param int $interval - Интервал на извикване в секунди
     */
    static function subscribe(&$tpl, $urlArr, $name, $interval=5000)
    {
        // Масив с всички използвани имена
        static $nameArr=array();
        
        // Ако няам име или не  е уникално
        if (!$name || $nameArr[$name]) {
            
            // Не би трябвало да се стига до тук
            
            // Добавяме грешката
            core_Logs::add('core_Ajax', NULL, "Повтарящо се име - '{$name}'", static::$logKeepDays);
            
//            // Докато генерираме уникално име
//            while ($nameArr[$name]) {
//                
//                // Добавяме след името
//                $name .= '_efae';
//            }
        }
        
        // Добавяме в масива
        $nameArr[$name] = TRUE;
        
        // Добавяме необходимите неща за стартиране на efae
        static::enable($tpl);
        
        // Локално URL
        $localUrl = toUrl($urlArr, 'local');
        
        // Ескейпваме
        $localUrl = urlencode($localUrl);
                
        // Добавяме стринга, който субскрайбва съответното URL
        $subscribeStr = "\n runOnLoad(function(){getEfae().subscribe('{$name}', '{$localUrl}', {$interval});});";
        
        // Добавяме само веднъж
        $tpl->appendOnce($subscribeStr, 'SCRIPTS');
    }
    
    
    /**
     * Добавя необходимите неща, за да може да се стартира efae
     * 
     * @param core_ET $tpl - Щаблон, към който ще се добавя
     */
    protected static function enable(&$tpl)
    {
        // Скрипт, за вземане на инстанция на efae
//        $tpl->appendOnce("\n runOnLoad(function(){ getEfae(); });", 'SCRIPTS');
        
        // Този пакет е във vendors
        if (method_exists('jquery_Jquery', 'enable')) {
            
            // Стартираме JQuery
            jquery_Jquery::enable($tpl);
        } else {
            
            // Добавяме грешката
            core_Logs::add('core_Ajax', NULL, 'Липсва метода `enable` в `jquery_Jquery`', static::$logKeepDays);
        }
        
        // Стартираме извикването на `run` фунцкцията на efae
        static::run($tpl);
    }
    
    
    /**
     * Добавя фунцкция, която стартира efae
     * 
     * @param core_ET $tpl - Щаблон, към който ще се добавя
     */
    protected static function run(&$tpl)
    {
        // URL, което сочи към екшъна за извличане на данни по AJAX
        $url = array('core_Ajax', 'Get');
        
        // Ако е в принт режим
        if ($printing = Request::get('Printing')) {
            // Добавяме в пареметрите
            $url['Printing'] = $printing;
        } else if (Mode::get('printing')) {
            $url['Printing'] = 1;
        }
        
        $url = toUrl($url);
        
        // Добавяме URL-то за сваляне на ajax
        $tpl->appendOnce("\n runOnLoad(function(){getEfae().setUrl('{$url}');});", 'SCRIPTS');
        
        // URL от който ще се вика айакса
//        $parentUrl = toUrl(getCurrentUrl(), 'local');
        
        // Задаваме УРЛ-то
//        $tpl->appendOnce("\n runOnLoad(function(){getEfae().setParentUrl('{$parentUrl}');});", 'SCRIPTS');
        
        // Този пакет е във vendors - ако липсва
        if (method_exists('jquery_Jquery', 'run')) {
            
            // Стартираме извикването на `run` фунцкцията на efae
            jquery_Jquery::run($tpl, "\n getEfae().run();", TRUE);
            jquery_Jquery::run($tpl, "\n getEO().runIdleTimer();", TRUE);
        } else {
            
            // Стартираме извикването на run
            $tpl->prependOnce("\n runOnLoad(function(){getEfae().run();});", 'SCRIPTS');
            $tpl->prependOnce("\n runOnLoad(function(){getEO().runIdleTimer();});", 'SCRIPTS');
            
            // Добавяме грешката
            core_Logs::add('core_Ajax', NULL, 'Липсва метода `run` в `jquery_Jquery`', static::$logKeepDays);
        }
    }
}
