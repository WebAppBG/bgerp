<?php


/**
 * Добавя необходимите атрибути за работа със select2
 * 
 * @category  bgerp
 * @package   selec2
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class select2_Adapter
{
    
    
    /**
     * Добавя необходимите файлове и стартира скирпта
     * 
     * @param core_Et $tpl
     * @param string $id
     * @param string|NULL $placeHolder
     * @param boolean $allowClear
     * @param string|NULL|FALSE $lg
     */
    public static function appendAndRun(&$tpl, $id, $placeHolder=NULL, $allowClear=FALSE, $lg=NULL)
    {
        if (is_null($lg)) {
            $lg = core_Lg::getCurrent();
        }
        
        self::appendFiles($tpl, $lg);
        self::run($tpl, $id, $placeHolder, $allowClear, $lg);
    }
    
    
    /**
     * Добавя необходимите файлове за работа на select2
     * 
     * @param core_Et $tpl
     * @param string|NULL|FALSE $lg
     */
    public static function appendFiles(&$tpl, $lg=NULL)
    {
        $conf = core_Packs::getConfig('select2');
        
        $tpl->push('select2/' . $conf->SELECT2_VERSION . "/select2.min.css", "CSS");
        $tpl->push('select2/' . $conf->SELECT2_VERSION . "/select2.min.js", "JS");
        
        if ($lg !== FALSE) {
            if (is_null($lg)) {
                $lg = core_Lg::getCurrent();
            }
            
            if (isset($lg)) {
                $lgPath = 'select2/' . $conf->SELECT2_VERSION . '/i18n/' . $lg . '.js';
                if (getFullPath($lgPath)) {
                    $tpl->push($lgPath, "JS");
                }
            }
        }
    }
    
    
    /**
     * Добавя скрипт, който да се стартира
     * 
     * @param core_Et $tpl
     * @param string $id
     * @param string|NULL $placeHolder
     * @param boolean $allowClear
     * @param string|NULL|FALSE $lg
     */
    public static function run(&$tpl, $id, $placeHolder=NULL, $allowClear=FALSE, $lg=NULL)
    {
        jquery_Jquery::run($tpl, "$('#" . $id . "').select2({placeholder: '{$placeHolder}', allowClear: '{$allowClear}', language: '{$lg}'});", TRUE);
    }
}
