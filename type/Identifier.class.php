<?php



/**
 * Клас  'type_Identifier' - Тип за идентификатор
 *
 *
 * @category  ef
 * @package   type
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class type_Identifier extends type_Varchar {
    
    
    /**
     * Конвертира от вербална стойност
     */
    function fromVerbal($value)
    {
        $value = parent::fromVerbal(trim($value));
        
        if($value === '') return NULL;

        // Проверяваме дали е валиден
        $res = self::isValid($value);
        
        // Ако има грешка, показваме нея
        if ($res['error']) {
            
            // Сетваме грешката
            $this->error = $res['error'];
            
            return FALSE;
        }
        
        return $value;
    }
    
    
    /**
     * Проверява дали е валиден
     */
    function isValid($value)
    {
        //Проверяваме за грешки
        $res = parent::isValid($value);
        
        //Ако има грешки връщаме резултатa
        if ($res['error']) return $res;
        
        $pattern = "/^[a-zA-Z_]{1}[a-zA-Z0-9_]*$/i";
        
        if(!preg_match($pattern, $value)) {
            
            $res['error'] = 'Некоректен идентификатор|* ' . parent::escape($value);
        }
        
        return $res;
    }
}
