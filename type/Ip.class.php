<?php



/**
 * Клас 'type_IP' - тип за съхранение и обработка на IP v4 адрес
 *
 *
 * @category  ef
 * @package   type
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class type_IP extends type_Varchar {
    
    
    /**
     * Дължина на полето в mySql таблица
     */
    var $dbFieldLen = 15;
    
    
    /**
     * Инициализиране на типа
     */
    function init($params = array())
    {
        $params['params']['size'] = 15;
        
        parent::init($params);
    }
    
    
    /**
     * Приема вербална стойност
     */
    function fromVerbal_($value)
    {
        $value = trim($value);
        
        if(empty($value)) return NULL;
        
        if(!filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $this->error = 'Некоректен IP адрес';
            
            return FALSE;
        }
        
        return $value;
    }
    
    
    /**
     * Преобразува имейл-а в човешки вид
     */
    function toVerbal_($value)
    {
        if(empty($value)) return NULL;
        
        $time = $this->params['time'];
        
        $value = self::decorateIp($value, $time);
        
        return $value;
    }
    
    
    /**
     * Декорира ip адреса
     * 
     * @param IP $ip
     * @param time $time
     * @param array $attr
     */
    static function decorateIp($ip, $time = NULL, $coloring = FALSE, $showNames = FALSE)
    {
        $res = $ip;
        
        $me = cls::get(get_called_class());
        
        $me->invoke('AfterDecorateIp', array(&$res, $ip, $time, $coloring, $showNames));
        
        return $res;
    }
    
    
    /**
     * Връща последното публично IP намерено в даден стринг
     */
    function getLastIp($str)
    {
        preg_match_all('/((?:\d{1,3}\.){3})\d{1,3}/', $str, $matches);
        
        for ($ipCount = count($matches[0])-1; $ipCount >= 0; $ipCount--) {
            
            $ip = $matches[0][$ipCount];
            
            if (type_Ip::isPublic($ip)) return $ip;
        }
    }
    
    
    /**
     * Дали посоченото IP е частно (запазено за частна употреба от организации)?
     * 
     * @param string $ip
     * 
     * @return boolean
     */
    public static function isPrivate($ip)
    {
        if(strpos($ip, '10.') === 0) return TRUE;
        
        if(strpos($ip, '127.0.0.') === 0) return TRUE;
 
        if($ip == '::1') return TRUE;

        if(strpos($ip, '192.168.') === 0) return TRUE;
        
        for($i = 16; $i < 32; $i++) {
            if(strpos($ip, "172.{$i}.") === 0) return TRUE;
        }
        
        return FALSE;
    }


    /**
     * Връща TRUE, ако IP-то на потребителя е от локалния компютър
     */
    public static function isLocal()
    {
        $localIpArr = array('::1', '127.0.0.1');
        $isLocal = in_array($_SERVER['REMOTE_ADDR'], $localIpArr);
        
        return $isLocal;
    }
    
    
    /**
     * Дали посоченото IP е публично
     */
    public static function isPublic($ip)
    {
        return !type_Ip::isPrivate($ip);
        
        if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE)) {
            
            expect(!type_Ip::isPrivate($ip), $ip);     // @todo: да се махне
            return TRUE;
        } else {
            
            expect(type_Ip::isPrivate($ip));     // @todo: да се махне
            return FALSE;
        }
    }
}