<?php

cls::load('type_Varchar');


/**
 * Клас 'gs1_TypeEan' - Тип за баркодовете на продуктите. Проверява
 * дали подаден стринг е Валиден ЕАН8, ЕАН13, ЕАН13+2 или ЕАН13+5 код.
 * При грешно подаден такъв изкарва и подсказка с правилно изчисления код
 *
 *
 * @category  vendors
 * @package   gs1
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class gs1_TypeEan extends type_Varchar
{
    
    
    /**
     * Колко символа е дълго полето в базата
     */
    var $dbFieldLen = 18;
    
    
    /**
     * Инициализиране на обекта
     */
    function init($params = array())
    {
        parent::init($params);
        $this->params['size'] = $this->params[0] = 18;
    }
    
    
    /**
     * Към 12-цифрен номер, добавя 13-та цифра за да го направи EAN13 код
     * @param string $digits - 12-те или 7-те цифри на кода
     * @param int $n - дали проверяваме за ЕАН8 или ЕАН13, ЕАН13 е по дефолт
     * @return string - правилния ЕАН8 или ЕАН13 код
     */
    function eanCheckDigit($digits, $n = 13)
    {
        $digits = (string)$digits;
        $oddSum = $evenSum = 0;
        
        foreach(array('even'=>'0', 'odd'=>'1') as $k=>$v) {
	        foreach (range($v, $n, 2) as ${"{$k}Num"}) {
	        	${"{$k}Sum"} += $digits[${"{$k}Num"}];
			}
        }
        
        // Ако е ЕАН13 умножаваме нечетната сума по три иначе- четната
        ($n == 13) ? $oddSum = $oddSum * 3 : $evenSum = $evenSum * 3;
		$totalSum = $evenSum + $oddSum;
        $nextTen = (ceil($totalSum / 10)) * 10;
        $checkDigit = $nextTen - $totalSum;
        return $digits . $checkDigit;
    }
    
    
    /**
     * Проверка за валидност на EAN13 или EAN8 код
     * @param string $value - подадената сума
     * @param int $n - дали е ЕАН13 или ЕАН8
     * @return boolean TRUE/FALSE
     */
    function isValidEan($value, $n = 13)
    {
        $digits12 = substr($value, 0, $n-1);
        $digits13 = $this->eanCheckDigit($digits12, $n);
        $res = ($digits13 == $value);
        
        return $res;
    }
    
    
    /**
     * Връща верен EAN 13 + 2/5, ако е подаден такъв
     * @param string $value - 15 или 18 цифрен баркод
     * @param int $n - колко цифри са допълнителните към EAN13
     * @return string $res - Подадения ЕАН13+2/5 код с правилна 13 цифра
     */
    function eanSCheckDigit($value, $n)
    {
    	$digits12 = substr($value, 0, 12);
    	$supDigits = substr($value, 13, $n);
    	$res = $this->eanCheckDigit($digits12);
    	$res .= $supDigits;
    	
    	return $res;
    }
    
    
    /**
     * Проверка за валидност на първите 13 цифри от 15 или 18 
     * цифрен баркод код, дали са валиден EAN13 код
     * @param string $value - EAN код с повече от 13 цифри
     * @return boolean TRUE/FALSE
     */
    function isValidEanS($value)
    {
    	$digits13 = substr($value, 0, 13);
    	if($this->isValidEan($digits13)) {
    		return TRUE;
    	} else {
    		return FALSE;
    	}
    }
    
    
    /**
     * Дефиниция на виртуалния метод на типа, който служи за проверка на данните
     * @return stdClass $res - обект съдържащ информация за валидноста на полето
     */
    function isValid($value)
    {
        if(!trim($value)) return array('value' => '');
        
        $res = new stdClass();
    	if (preg_match("/[^0-9]/", $value)) {
                $res->error .= "Полето приема само цифри.";  
        } else {
        	$code = strlen($value);
        	if($this->params['gln']) {
        		$code = 13;
        	}
        	
        	// Взависимост от дължината на стринга проверяваме кода
        	switch($code) {
        		case 13:
		        	if (!$this->isValidEan($value)){
		        		(!$this->params['gln']) ? $type = 'EAN13' : $type = 'GLN(13 цифри)';
		                $res->error = "Невалиден {$type} номер.";
		            }
        			break;
        		case 7:
        			$res->value = $this->eanCheckDigit($value, 8);
            		$res->warning = "Въвели сте само 7 цифри. Пълният EAN8 код {$res->value} ли е?";
        			break;
        		case 8:
		        	if (!$this->isValidEan($value, 8)){
		                $res->error = "Невалиден EAN8 номер.";
		            }
        			break;
        		case 15:
		        	if (!$this->isValidEanS($value)){
		        		$res->value = $this->eanSCheckDigit($value, 2);
		        		$res->error = "Невалиден EAN13+2 номер. Пълният EAN13+2 код {$res->value} ли е?";
		            }
        			break;
        		case 18:
		        	if (!$this->isValidEanS($value)){
		        		$res->value = $this->eanSCheckDigit($value, 5);
		                $res->error = "Невалиден EAN13+5 номер. Пълният EAN13+5 код {$res->value} ли е?";
		            }
        			break;
        		case 12:
        			$res->value = $this->eanCheckDigit($value);
            		$res->warning = "Въвели сте само 12 цифри. Пълният EAN13 код {$res->value} ли е?";
        			break;
        		default:
        			$res->error = "Невалиден EAN13 номер. ";
                	$res->error .= "Въведения номер има |*{$code}| цифри.";  
        			break;
        	}
        }
       
        return (array) $res;
    }
}