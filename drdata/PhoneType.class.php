<?php


/**
 * Клас 'drdata_PhoneType' - тип за телефонен(ни) номера
 *
 *
 * @category  vendors
 * @package   drdata
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class drdata_PhoneType extends type_Varchar
{
    
    
    /**
     * Връща подадения номер като стринг като пълен номер
     * 
     * @param string $number - Номера
     * 
     * @return string $numStr - Номера в пълен формат
     * @return mixed $arrayKey - Ако трябва дас е връща само един от номерата
     */
    public static function getNumberStr($number, $arrayKey=FALSE)
    {
        // Вземаме номера
        $numArr = drdata_PhoneType::toArray($number);
        
        // Ако не е валиден номер
        if (!$numArr || !count($numArr)) {
            
            return $number;
        }
        
        // Ако ще се връщат всички номера
        if ($arrayKey === FALSE) {
            foreach ($numArr as $num) {
                
                // Вземаме пълния стринг за номера
                $numStr = static::getNumStrFromObj($num);
                
                $resNumStr .= ($resNumStr) ? ', ' . $numStr : $numStr;
            }
        } else {
            $resNumStr = static::getNumStrFromObj($numArr[$arrayKey]);
        }
        
        return $resNumStr;
    }
    
    
    /**
     * Връща пълния номер от подадени обект
     * 
     * @param object $numObj - Обект, генериран от drdata_PhoneType
     * 
     * @return string $callerNumStr - Стринг с пълния номер
     */
    public static function getNumStrFromObj($numObj, $phoneCodeBefore='+')
    {
        // Ако не е обект, връщаме
        if (!is_object($numObj)) return $numObj;
        
        // Генерираме пълния номер
        $callerNumStr = $phoneCodeBefore . $numObj->countryCode . $numObj->areaCode . $numObj->number;
        
        return $callerNumStr;
    }
    
    
    /**
     * Оправя телефонните номера
     */
    function toVerbal_($telNumber)
    {
        if(!$telNumber) return NULL;
        
        if (Mode::is('text', 'plain') || Mode::is('text', 'pdf') || Mode::is('text', 'xhtml')) {
            
            return $telNumber;
        }
        
        $parsedTel = static::toArray($telNumber, $this->params);

        $telNumber = parent::toVerbal_($telNumber);

        if ($parsedTel == FALSE) {
            return "<font color='red'>{$telNumber}</font>";
        } else {
            $res = new ET();
            $value = '';

            foreach($parsedTel as $t) {

                $res->append($add);

                $value = '';

                if($t->countryCode) {
                    $value .= '' . $t->countryCode;
                }

                if($t->areaCode) {
                    $value .= '' . $t->areaCode;
                }

                if($t->number) {
                    $value .= '' . $t->number;
                }

               /* $attr = array();

                if(($t->country != 'Unknown') && ($t->area != 'Unknown') && $t->area && $t->country) {
                    $attr['title'] = "{$t->country}, {$t->area}";
                } elseif(($t->country != 'Unknown') && $t->country) {
                    $attr['title'] = "{$t->country}";
                }
                
                $title = $t->original;*/
                
                //$res->append(ht::createLink($title, 'tel:00'. $value, NULL, $attr));
                $res->append(self::getLink_($telNumber, $value, FALSE));

               /* if($t->internal) {
                    $res->append(tr('вътр.') . $t->internal) ;
                }

                $add = ", ";*/
            }
        }

        return $res;
    }


    /**
     * Конвертира списък от телефонни номера до масив
     *
     * @param string $str
     * @param array $params
     * @return array резултата е същия като на @see drdata_Phones::parseTel()
     */
    public static function toArray($str, $params = array())
    {
        $Phones = cls::get('drdata_Phones');
        
        // Ако не е подаден телефонния код на държавата, ще се използва от конфигурационната константа
        if (!($code = $params['countryPhoneCode'])) {
            
            $conf = core_Packs::getConfig('drdata');
        
            $code = $conf->COUNTRY_PHONE_CODE;
        }
        
        $result = $Phones->parseTel($str, $code);

        return $result;
    }
    
    
    /**
     * Превръщане на телефонните номера и факсове в линкове
     * 
     * @param varchar $verbal
     * @param drdata_PhoneType $canonical
     * @param boolean $isFax
     */
    public  function getLink_($verbal, $canonical, $isFax = FALSE)
    {
    	$res = new ET();
        
    	$parsedTel = static::toArray($verbal, $this->params);
    	
    	foreach($parsedTel as $t) {
    		$attr = array();

            if(($t->country != 'Unknown') && ($t->area != 'Unknown') && $t->area && $t->country) {
            	$attr['title'] = "{$t->country}, {$t->area}";
            } elseif(($t->country != 'Unknown') && $t->country) {
            	$attr['title'] = "{$t->country}";
            }
                
            $title = $t->original;
            
	    	if($isFax) {
	        	$res->append(ht::createLink($title, NULL, NULL, $attr)); 
	        } else {
	           	$res->append(ht::createLink($title, "tel:00" . $canonical, NULL, $attr));     			
	        }

            if ($t->internal) {
            	$res->append(tr('вътр.') . $t->internal) ;
            }

            $add = ", ";
    	}

        return $res;
    }
}
