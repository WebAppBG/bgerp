<?php


/**
 * JS файловете
 */
defIfNot('COMPACTOR_JS_FILES', 'jquery/1.8.3/jquery.min.js, js/efCommon.js, js/overthrow-detect.js, toast/0.3.0f/javascript/jquery.toastmessage.js');


/**
 * CSS файловете
 */
defIfNot('COMPACTOR_CSS_FILES', 'css/common.css, css/Application.css, toast/0.3.0f/resources/css/jquery.toastmessage.css, css/default-theme.css');


/**
 * 
 *
 * @category  compactor
 * @package   toast
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class compactor_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "";
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html .= parent::install();
    	
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина за показване на статусите като toast съобщения
        $html .= $Plugins->installPlugin('Компактиране на файлове', 'compactor_Plugin', 'page_Html', 'private');

        return $html;
    }
    
    
    /**
     * 
     */
    public function loadSetupData()
    {
        $res .= parent::loadSetupData();
        
        // JS и CSS файловете от конфигурацията
//        $conf = core_Packs::getConfig('compactor');
//        $jsFilesArr = arr::make($conf->COMPACTOR_JS_FILES, TRUE);
//        $cssFilesArr = arr::make($conf->COMPACTOR_CSS_FILES, TRUE);
        
        // JS и CSS файловете от конфигурацията от константите
        $jsFilesArr = arr::make(COMPACTOR_JS_FILES, TRUE);
        $cssFilesArr = arr::make(COMPACTOR_CSS_FILES, TRUE);
        
        // Всички записани пакети
        $query = core_Packs::getQuery();
        while ($rec = $query->fetch()) {
            
            // Ако няма име
            if (!$rec->name) continue;
            
            // Сетъп пакета
            $pack = $rec->name  . '_Setup';
            
            // Ако файлът съществува
            if (cls::load($pack, TRUE)) {
                
                // Инстанция на пакета
                $inst = cls::get($pack);
                
                // Вземаме CSS файловете и заместваме плейсхолдерите от конфига
                if (method_exists($inst, 'getCommonCss')) {
                    $commonCss = $inst->getCommonCss();
                }
                
                // Вземаме JS файловете и заместваме плейсхолдерите от конфига
                if (method_exists($inst, 'getCommonJs')) {
                    $commonJs = $inst->getCommonJs();
                }
                
                // Ако няма файлове за добавяне
                if (!$commonCss && !$commonJs) continue;
                
                // Добавяме зададените CSS файлове към главния
                if ($commonCss) {
                    $commonCssArr = arr::make($commonCss, TRUE);
                    $cssFilesArr = array_merge((array)$cssFilesArr, (array)$commonCssArr);
                    $haveCss = TRUE;
                }
                
                // Добавяме зададените JS файлове към главния
                if ($commonJs) {
                    $commonJsArr = arr::make($commonJs, TRUE);
                    $jsFilesArr = array_merge((array)$jsFilesArr, (array)$commonJsArr);
                    $haveJs = TRUE;
                }
            }
        }
        
        // Ако има добавен CSS файл, добавяме ги към конфигурацията
        if ($haveCss) {
            $cssFilesStr = implode(', ', $cssFilesArr);
            $data['COMPACTOR_CSS_FILES'] = $cssFilesStr;
            $res .= '<li>CSS файловете за компактиране: ' . $cssFilesStr;
        }
        
        // Ако има добавен JS файл, добавяме ги към конфигурацията
        if ($haveJs) {
            $jsFilesStr = implode(', ', $jsFilesArr);
            $data['COMPACTOR_JS_FILES'] = $jsFilesStr;
            $res .= '<li>JS файловете за компактиране: ' . $jsFilesStr;
        }
        
        // Ако има данни за добавяме, обновяваме данние от компактора
        if ($data) {
            core_Packs::setConfig('compactor', $data);
        }
        
        return $res;
    }
}
