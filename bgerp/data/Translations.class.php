<?php


/**
 * Клас 'bgerp_data_Translations'
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_data_Translations
{
    
    
    /**
     * Зареждане на стирнговете, които ще се превеждат
     */
    static function loadData()
    {
    	$file = "bgerp/data/csv/Translations.csv";

        $mvc = cls::get('core_Lg');

    	$fields = array( 
	    	0 => "lg", 
	    	1 => "kstring", 
	    	2 => "translated", 
	    	3 => "csv_createdBy",
	    	);
    	
    	$cntObj = csv_Lib::importOnce($mvc, $file, $fields);
        
        $res = static::addForAllLg();
        
        $res .= $cntObj->html;
        
        return $res;
    }
    
    
    public static function on_BeforeImportRec($mvc, $rec)
    {
    	if (isset($rec->csv_createdBy)) {
    		
    		$rec->createdBy = -1;
    	}
    }
    
    /**
     * Добавя съдържанието на преводите, които са зададени в EF_LANGUAGES
     * Добавя за всички езици без `en` и `bg`
     */
    static function addForAllLg()
    {
        // Масив в всички езици
        $langArr = arr::make(EF_LANGUAGES, TRUE);
        
        // Премахваме английския и българския
        unset($langArr['en']);
        unset($langArr['bg']);
        
        // Ако няма повече езици, не се изпълянва
        if (!count($langArr)) return ;

        // Вземаме всички преводи на английски
        $query = core_Lg::getQuery();
        $query->where("#lg = 'en'");
        while ($enLangRec = $query->fetch()) {
            
            // Добавяме ги в масив
            $enLangRecArr[$enLangRec->id] = $enLangRec;
        }
        
        // Обхождаме езиците
        foreach ($langArr as $lang => $dummy) {
            
            // Обхождаме всички преводи на английски
            foreach ((array)$enLangRecArr as $enLangRec) {
                
                // Създаваме запис
                $nRec = new stdClass();
                $nRec->lg = $lang;
                $nRec->kstring = $enLangRec->kstring;
                $nRec->translated = $enLangRec->translated;
                $nRec->createdBy = -1;
                
                // Опитваме се да запишем данните за съответния език
                core_Lg::save($nRec, NULL, 'IGNORE');
            // Ако запишем успешно
                if ($nRec->id) {
                    
                    // Увеличаваме брояча за съответния език
                    $nArr[$lang]++;
                }
            }
        }
        
        // Обхождаме всички записани резултати
        foreach ((array)$nArr as $lg => $times) {
            
            // Добавяме информационен стринг за всеки език
            $res .= "<li style='color:green'>Към {$langArr[$lg]} са добавени {$times} превода на английски.";
        }
        
        return $res;
    }

}