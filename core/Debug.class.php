<?php



/**
 * Клас 'core_Debug' ['Debug'] - Функции за дебъг и настройка на приложения
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Debug
{
	static $startMicroTime;

	static $lastMicroTime;

    static $debugTime = array();

    static $timers = array();
    
    /**
     * Дали дебъгера да записва
     * Това е един начин, да се изключат логовете на дебъгера
     */
    static $isLogging = TRUE;

    /**
     * Функция - флаг, че обектите от този клас са Singleton
     */
    function _Singleton() {}
    
 
    
    /**
     * Инициализираме таймерите
     */
    static function init()
    {
        if (!self::$startMicroTime) {
            self::$startMicroTime = dt::getMicrotime();
            self::$lastMicroTime = 0;
        	self::$debugTime[] = "0.00000: Begin";
        }
    }
    
    
    /**
     * Пускаме хронометъра за посоченото име
     */
    static function startTimer($name)
    {
        // Функцията работи само в режим DEBUG
        if(!isDebug()) return;
        
        self::init();
        
        self::$timers[$name] = new stdClass();
        self::$timers[$name]->start = dt::getMicrotime();
    }
    
    
    /**
     * Спираме хронометъра за посоченото име
     */
    static function stopTimer($name)
    {
        // Функцията работи само в режим DEBUG
        if(!isDebug()) return;
        
        self::init();
  
        if (self::$timers[$name]->start) {
            $workingTime = dt::getMicrotime() - self::$timers[$name]->start;
            self::$timers[$name]->workingTime += $workingTime;
            self::$timers[$name]->start = NULL;
        }
    }
    
    
    /**
     * Лог записи за текущия хит
     */
    static function log($name)
    {
        // Функцията работи само в режим DEBUG
        if(!isDebug() || !core_Debug::$isLogging) return;

        self::init();
        
        $rec = new stdClass();
        $rec->start = dt::getMicrotime() - self::$startMicroTime;
        $rec->name  = $name;

        self::$debugTime[] = $rec;
    }
    
    
    /**
     * Колко време е записано на това име?
     */
    static function getExecutionTime()
    {
        self::init();
        return number_format((dt::getMicrotime() - self::$startMicroTime), 5);
    }
    
    
    /**
     * Връща лога за текущия хит
     */
    static function getLog()
    {
        self::init();
        
        if (count(self::$debugTime)) {
            self::log('End');
            $html .= "\n<div style='padding:5px; margin:10px; border:solid 1px #777; background-color:#FFFF99; display:table;color:black;'>" .
            "\n<div style='background-color:#FFFF33; padding:5px; color:black;'>Debug log</div><ul>";
            
            foreach (self::$debugTime as $rec) {
                $html .= "\n<li style='padding:15px 0px 15px 0px;border-top:solid 1px #cc3;'>" .  number_format(($rec->start - $last), 5) . ": " . htmlentities($rec->name, ENT_QUOTES, 'UTF-8');
                $last = $rec->start;
            }
            
            $html .= "\n</ul></div>";
        }
        
        if (count(self::$timers)) {
            $html .= "\n<div style='padding:5px; margin:10px; border:solid 1px #777; background-color:#FFFF99; display:table;color:black;'>" .
            "\n<div style='background-color:#FFFF33; padding:5px;color:black;'>Timers info</div><ol>";
            
            foreach (self::$timers as $name => $t) {
                $html .= "\n<li> '{$name}' => " . number_format($t->workingTime, 5) . ' sec.';
            }
            
            $html .= "\n</ol></div>";
        }
        
        return $html;
    }
}