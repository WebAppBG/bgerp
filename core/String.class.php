<?php



/**
 * Клас 'core_String' ['str'] - Функции за за работа със стрингове
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
class core_String
{
    
    
    /**
     * Конвертира всички европейски азбуки,
     * включително и кирилицата, но без гръцката към латиница
     *
     * @param  string $text текст за конвертиране
     * @return string резултат от конвертирането
     * @access public
     */
    static function utf2ascii($text)
    {
        static $trans = array();
        
        if (!count($trans)) {
            ob_start();
            require_once(dirname(__FILE__) . '/transliteration.inc.php');
            ob_end_clean();
            
            $trans = $code;
        }
        
        foreach ($trans as $alpha => $lat) {
            $text = str_replace($alpha, $lat, $text);
        }
        
        preg_match_all('/[A-Z]{2,3}[a-z]/', $text, $matches);
        
        foreach ($matches[0] as $upper) {
            $cap = ucfirst(strtolower($upper));
            $text = str_replace($upper, $cap, $text);
        }
        
        return $text;
    }

    
    /**
     * Прави първия символ на стринга главна буква (за многобайтови символи)
     * @param string $string - стринга който ще се рансформира
     */
	public static function mbUcfirst($string) 
	{
        $string = mb_strtoupper(mb_substr($string, 0, 1)) . mb_substr($string, 1);
        
        return $string;
    }

    
    /**
     * Превръща UTF-9 в каноничен стринг, съдържащ само латински букви и числа
     * Всички символи, които не могат да се конвертират, се заместват с втория аргумент
     */
    public static function canonize($str, $substitute = '-')
    {
        $cStr = str::utf2ascii($str);

        $cStr = trim(preg_replace('/[^a-zA-Z0-9]+/', $substitute, " {$cStr} "), $substitute);
        
        return $cStr;
    }

    

    /**
     * Функция за генериране на случаен низ. Приема като аргумент шаблон за низа,
     * като символите в шаблона имат следното значение:
     *
     * '*' - Произволна латинска буква или цифра
     * '#' - Произволна цифра
     * '$' - Произволна буква
     * 'a' - Произволна малка буква
     * 'А' - Произволна голяма буква
     * 'd' - Малка буква или цифра
     * 'D' - Голяма буква или цифра
     */
    static function getRand($pattern = 'addddddd')
    {
        static $chars, $len;
        
        if(empty($chars)) {
            $chars['*'] = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $chars['#'] = "0123456789";
            $chars['$'] = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $chars['a'] = "abcdefghijklmnopqrstuvwxyz";
            $chars['A'] = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $chars['d'] = "0123456789abcdefghijklmnopqrstuvwxyz";
            $chars['D'] = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            
            // Генерираме $seed
            $seed = microtime() . EF_SALT;
            
            foreach($chars as $k => $str) {
                
                $r2 = $len[$k] = strlen($str);
                
                while($r2 > 0) {
                    $r1 = (abs(crc32($seed . $r2--))) % $len[$k];
                    $c = $chars[$k]{$r1};
                    $chars[$k]{$r1} = $chars[$k]{$r2};
                    $chars[$k]{$r2} = $c;
                }
            }
        }
        
        $pLen = strlen($pattern);
        
        for($i = 0; $i < $pLen; $i++) {
            
            $p = $pattern{$i};
            
            $rand = rand(0, $len[$p]-1);
            
            $rand1 = ($rand + 7) % $len[$p];
            
            $c = $chars[$p]{$rand};
            $chars[$p]{$rand} = $chars[$p]{$rand1};
            $chars[$p]{$rand1} = $c;
            
            $res .= $c;
        }
        
        return $res;
    }
    
    
    /**
     *
     */
    static function cut($str, $beginMark, $endMark = '', $caseSensitive = FALSE)
    {
    
        return static::crop($str, $beginMark, $endMark, $caseSensitive);
    }
    
    
    /**
     * Отделя стринг, заключен между други два стринга
     */
    static function crop($str, $beginMark, $endMark = '', $caseSensitive = FALSE, &$offset = 0)
    {
        if (!$caseSensitive) {
            $sample = mb_strtolower($str);
            $beginMark = mb_strtolower($beginMark);
            $endMark = mb_strtolower($endMark);
        } else {
            $sample = $str;
        }
        
        $begin = mb_strpos($sample, $beginMark, $offset);
        
        if ($begin === FALSE) return FALSE;
        
        $begin = $begin + mb_strlen($beginMark);
        
        if ($endMark) {
            $end = mb_strpos($sample, $endMark, $begin);
            
            if ($end === FALSE) return FALSE;
            
            $result = mb_substr($str, $begin, $end - $begin);
            $offset = $end + mb_strlen($endMark);
        } else {
            $result = mb_substr($str, $begin);
            $offset = mb_strlen($str);
        }
        
        return $result;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function findOn($str, $match, $until = -1)
    {
        $str = mb_strtolower($str);
        $match = mb_strtolower($match);
        $find = mb_strpos($str, $match);
        
        if ($find === FALSE) {

            return FALSE;
        }
        
        if ($until < 0) {

            return TRUE;
        }
        
        if ($find <= $until) {
            return TRUE;
        } else {
        
            return FALSE;
        }
    }


    /**
     * Връща истина, само ако и двата стринга са не-нулеви и единият е по-стринг на другия
     */
    static function contained($str1, $str2)
    {
        if(strlen($str1) == 0 || strlen($str2) == 0) {

            return FALSE;
        }

        if(strpos($str1, $str2) !== FALSE || strpos($str2, $str1) !== FALSE) {

            return TRUE;
        }

        return FALSE;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function addHash($str, $length = 4)
    {
        
        return $str . "_" . substr(md5(EF_SALT . $str), 0, $length);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function checkHash($str, $length = 4)
    {
        if ($str == str::addHash(substr($str, 0, strlen($str) - $length - 1), $length) && substr($str, -1 - $length, 1) == "_") {
            return substr($str, 0, strlen($str) - $length - 1);
        }
        
        return FALSE;
    }
    
    
    /**
     * Конвертиране между PHP и MySQL нотацията
     */
    static function phpToMysqlName($name)
    {
        $name = trim($name);
        
        for ($i = 0; $i < strlen($name); $i++) {
            $c = $name{$i};
            
            if ((($lastC >= "a" && $lastC <= "z") || ($lastC >= "0" && $lastC <= "9")) && ($c >= "A" && $c <= "Z")) {
                $mysqlName .= "_";
            }
            $mysqlName .= $c;
            $lastC = $c;
        }
        
        return strtolower($mysqlName);
    }
    
    
    /**
     * Превръща mysql име (с подчертавки) към нормално име
     */
    static function mysqlToPhpName($name)
    {
        $cap = FALSE;
        
        for ($i = 0; $i < strlen($name); $i++) {
            $c = $name{$i};
            
            if ($c == "_") {
                $cap = TRUE;
                continue;
            }
            
            if ($cap) {
                $out .= strtoupper($c);
                $cap = FALSE;
            } else {
                $out .= strtolower($c);
            }
        }
        
        return $out;
    }
    
    
    /**
     * Конвертира стринг до уникален стринг с дължина, не по-голяма от указаната
     * Уникалността е много вероятна, но не 100% гарантирана ;)
     */
    static function convertToFixedKey($str, $length = 64, $md5Len = 32, $separator = "_")
    {
        if (strlen($str) <= $length) return $str;
        
        $strLen = $length - $md5Len - strlen($separator);
        
        if ($strlen < 0)
        error("Дължината на MD5 участъка и разделителя е по-голяма от зададената обща дължина", array(
                'length' => $length,
                'md5Len' => $md5Len
            ));
        
        if (ord(substr($str, $strLen - 1, 1)) >= 128 + 64) {
            $strLen--;
            $md5Len++;
        }
        
        $md5 = substr(md5(_SALT_ . $str), 0, $md5Len);
        
        return substr($str, 0, $strLen) . $separator . $md5;
    }
    
    
    /**
     * Парсира израз, където променливите започват с #
     */
    static function prepareExpression($expr, $nameCallback)
    {
        $len = strlen($expr);
        $esc = FALSE;
        $isName = FALSE;
        $lastChar = '';
        
        for ($i = 0; $i <= $len; $i++) {
            
            $c = $expr{$i};
            
            if($lastChar == "\\") {
                $bckSl++;
            } else {
                $bckSl = 0;
            }

            if ($c == "'" && (($bckSl % 2) == 0)) {
                $esc = (!$esc);
            }
            
            if ($esc) {
                $out .= $c;
                $lastChar = $c;
                continue;
            }
            
            if ($isName) {
                if (($c >= 'a' && $c <= 'z') || ($c >= 'A' && $c <= 'Z') || ($c >= '0' && $c <= '9') || $c == '_') {
                    $name .= $c;
                    continue;
                } else {
                    // Край на името
                    $isName = FALSE;
                    $out .= call_user_func($nameCallback, $name);
                    $out .= $c;
                    $lastChar = $c;
                    continue;
                }
            } else {
                if ($c == '#') {
                    $name = '';
                    $isName = TRUE;
                    continue;
                } else {
                    $out .= $c;
                    $lastChar = $c;
                }
            }
        }
        
        return $out;
    }
    
    
    /**
     * Проверка дали символът е латинска буква
     */
    static function isLetter($c)
    {
        
        return ($c >= 'a' && $c <= 'z') || ($c >= 'A' && $c <= 'Z') || $c == '_';
    }
    
    
    /**
     * Проверка дали символът е цифра
     */
    static function isDigit($c)
    {
        return $c >= '0' && $c <= '9';
    }


    /**
     * Оставя само първите $length символа от дадения стринг
     */
    static function truncate($str, $length, $breakWords = TRUE, $append = '…')
    {
      $strLength = mb_strlen($str);

      if ($strLength <= $length) {
         return $str;
      }

      if (!$breakWords) {
           while(preg_match('/^[\pL\pN]/', mb_substr($str, $length, 1))) {
               $length--;
           }
      }

      return mb_substr($str, 0, $length) . $append;
    }
    

    /**
     * На по-големите от дадена дължина стрингове, оставя началото и края, а по средата ...
     */
    static function limitLen($str, $maxLen)
    {
        if(mb_strlen($str) > $maxLen) {
            if($maxLen > 20) {
                $remain = (int) ($maxLen - 5) / 2;
                $str = mb_substr($str, 0, $remain) . ' ... ' . mb_substr($str, -$remain);
            } else {
                $remain = (int) ($maxLen - 3);
                $str = mb_substr($str, 0, $remain) . ' ... ';
            }
        }
        
        return $str;
    }
	
    
    /**
     *  Инкрементиране с еденица на стринг, чиято последна част е число
     *  Ако стринга не завършва на числова част връща се FALSE
     *  @param str $string - стринга който се подава
     *  @return mixed string/FALSE - инкрементирания стринг или FALSE
     */
    public static function increment($str)
    {
    	if(is_string($str)){
    		
	    	//Разделяне на текста от последното число
	    	preg_match("/.+?(\d+)$/", $str, $match);
	    	
	    	//Ако е открито число
	        if (isset($match['1'])) {
	        	$numLen = strlen($match['1']);
	        	$numIndex = strrpos($str, $match['1']);
	        	$other = substr($str,0, $numIndex);
	        	
	            // Съединяване на текста с инкрементирана с единица стойност на последното число
	            return $other . str_pad(++$match['1'], $numLen, "0", STR_PAD_LEFT);
	        }
    	}
    	
        return FALSE;
    }

    
    /** 
     * Циклене по UTF-8 низове
     */
    static function nextChar($string, &$pointer)
    {
        $c = mb_substr(substr($string, $pointer, 5), 0, 1);

        $pointer += strlen($c);

        return $c;
    }


    /**
     * Опитва се да премахне от даден стринг, масив от под-стрингове, считано то началото му
     */
    static function removeFromBegin($str, $sub)
    {
        if(!is_array($sub)) {
            expect(is_scalar($sub));
            $sub = array($sub);
        }

        foreach($sub as $s) {
            if(stripos($str, $s) === 0) {
                $str = mb_substr($str, mb_strlen($s));
            }
        }

        return $str;
    }
    
    
    /**
     * Връща масив с гласните букви на латиница и кирилица
     */
    static function getVowelArr()
    {
        
        return array("a"=>"a", "e"=>"e", "i"=>"i", "o"=>"o", "u"=>"u",
    					"а"=>"а", "ъ"=>"ъ", "о"=>"о", "у"=>"у", "е"=>"е", "и"=>"и");
    }
    
    
    /**
     * Проверява даден символ дали е гласна буква
     * 
     * @param char $char - Симвът, който ще проверяваме
     * 
     * @return boolena - Ако е гласна връщаме TRUE
     */
    static function isVowel($char)
    {
        // Масива със съгласните букви
        static $vowelArr;
        
        // Ако не е сетнат
	    if (!$vowelArr) {
	        
	        // Вземаме масива
	        $vowelArr = static::getVowelArr();
	    }
	    
	    // Буквата в долен регистър
	    $char = mb_strtolower($char);
	    
	    // Ако е съгласна
	    return (boolean)$vowelArr[$char];
    }
    
    
    /**
     * Връща масив с съгласните букви на латиница и кирилица
     */
    static function getConsonentArr()
    {
        
        return array("б"=>"б","в"=>"в", "г"=>"г", "д"=>"д", "ж"=>"ж", "з"=>"з", "к"=>"к",
        				"л"=>"л", "м"=>"м", "н"=>"н", "п"=>"п", "р"=>"р", "с"=>"с", "т"=>"т",
        				"ф"=>"ф", "х"=>"х", "ц"=>"ц", "ч"=>"ч", "ш"=>"ш",
        				"b"=>"b", "c"=>"c", "d"=>"d", "f"=>"f", "g"=>"g", "h"=>"h", "j"=>"j",
        				"k"=>"k", "l"=>"l", "m"=>"m", "n"=>"n", "p"=>"p", "q"=>"q", "r"=>"r", "s"=>"s",
        				"t"=>"t", "v"=>"v", "x"=>"x", "z"=>"z");
    }
    
    
    /**
     * Проверява даден символ дали е съгласна буква
     * 
     * @param char $char - Симвът, който ще проверяваме
     * 
     * @return boolena - Ако е съгласна връщаме TRUE
     */
	static function isConsonent($char)
	{
	    // Масива със съгласните букви
	    static $consonentArr;
	    
	    // Ако не е сетнат
	    if (!$consonentArr) {
	        
	        // Вземаме масива
	        $consonentArr = static::getConsonentArr();
	    }
	    
	    // Буквата в долен регистър
	    $char = mb_strtolower($char);
	    
	    // Ако е съгласна
	    return (boolean)$consonentArr[$char];
	}
	
	
	/**
	 * Всеки символ след празен да е в горния регистър
	 * 
	 * @param string $str
	 * 
	 * @return string
	 */
	static function stringToNameCase($str)
	{
	    $str = mb_convert_case($str, MB_CASE_TITLE, 'UTF-8');
	    
	    return $str;
	}
}
