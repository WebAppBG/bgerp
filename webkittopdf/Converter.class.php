<?php 


/**
 * Дефинира име на папка в която ще се съхраняват временните данни данните
 */
defIfNot('WEBKIT_TO_PDF_TEMP_DIR', EF_TEMP_PATH . "/webkittopdf");


/**
 * Генериране на PDF файлове от HTML файл чрез web kit
 *
 *
 * @category  vendors
 * @package   webkittopdf
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class webkittopdf_Converter extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'webkittopdf';
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'doc_ConvertToPdfIntf';
    
    
    /**
     * Конвертира html към pdf файл
     * 
     * @param string $html - HTML стинга, който ще се конвертира
     * @param string $fileName - Името на изходния pdf файл
     * @param string $bucketName - Името на кофата, където ще се записват данните
     * @param array $jsArr - Масив с JS и JQUERY_CODE
     *
     * @return string $fh - Файлов манипулатор на новосъздадения pdf файл
     */
    static function convert($html, $fileName, $bucketName, $jsArr=array())
    {   
        // Вземаме конфигурационните данни
    	$conf = core_Packs::getConfig('webkittopdf');
    	
        //Генерираме унукално име на папка
        do {
            $randId = str::getRand();
            $tempPath = WEBKIT_TO_PDF_TEMP_DIR . '/' . $randId;
        } while (is_dir($tempPath));
        
        //Създаваме рекурсивно папката
        expect(mkdir($tempPath, 0777, TRUE));
        
        //Пътя до html файла
        $htmlPath = $tempPath . '/' . $randId . '.html';
        
        // Зареждаме опаковката 
        $wrapperTpl = cls::get('page_Print');
        
        // Ако е зададено да се използва JS
        if ($conf->WEBKIT_TO_PDF_USE_JS == 'yes') {
            
            // Обхождаме масива с JS файловете
            foreach ((array)$jsArr['JS'] as $js) {
                
                // Добавяме в шаблона
                $wrapperTpl->push($js, 'JS');
            }
            
            // Обхождаме масива с JQUERY кодовете
            if ($jsArr['JQUERY_CODE'] && count((array)$jsArr['JQUERY_CODE'])) {
                
                // Активираме JQUERY
                jquery_Jquery::enable($wrapperTpl);
                
                // Обхождаме JQuery кодовете
                foreach ((array)$jsArr['JQUERY_CODE'] as $jquery) {
                    
                    // Добавяме кодовете
                    jquery_Jquery::run($wrapperTpl, $jquery);
                }
            }
            
            // Променлива за стартиране на JS
            $jsScript = '--enable-javascript';
            
            // Добавяме забавянето
            $jsScript .= " --javascript-delay " . escapeshellarg($conf->WEBKIT_TO_PDF_JS_DELAY);
            
            // Ако е No
            if ($conf->WEBKIT_TO_PDF_JS_STOP_SLOW_SCRIPT == 'no') {
                
                // Добавяме към променливите за JS
                $jsScript .= " --no-stop-slow-scripts";
            }
        } elseif ($conf->WEBKIT_TO_PDF_USE_JS == 'no') {
            
            // Ако е зададено да не се изпълнява
            $jsScript = "--disable-javascript";
        }
        
        // Изпращаме на изхода опаковано съдържанието
        $wrapperTpl->replace($html, 'PAGE_CONTENT');
        
        // Вземаме съдържанието
        // Трети параметър трябва да е TRUE, за да се вземе и CSS
        $html = $wrapperTpl->getContent(NULL, "CONTENT", TRUE);
        $html = "\xEF\xBB\xBF" . $html;
        
        //Записваме данните в променливата $html в html файла
        $fileHnd = fopen($htmlPath, 'w');
        fwrite($fileHnd, $html);
        fclose($fileHnd);
        
        //Пътя до pdf файла
        $pdfPath = $tempPath . '/' . $fileName;
        
        //Ако ще използва xvfb-run
        if ($conf->WEBKIT_TO_PDF_XVFB_RUN == 'yes') {
            
            //Променливата screen
            $screen = '-screen 0 ' . $conf->WEBKIT_TO_PDF_SCREEN_WIDTH . 'x' . $conf->WEBKIT_TO_PDF_SCREEN_HEIGHT . 'x' . $conf->WEBKIT_TO_PDF_SCREEN_BIT;
            
            //Ескейпваме променливата
            $screen = escapeshellarg($screen);
            
            //Изпълнение на програмата xvfb-run
            $xvfb = "xvfb-run -a -s {$screen}";
        } else {
            
            // Флаг указващ да се използва XServer в пакета
            $useXServer = TRUE;
        }
        
        //Ескейпваме всички променливи, които ще използваме
        $htmlPathEsc = escapeshellarg($htmlPath);
        $pdfPathEsc = escapeshellarg($pdfPath);
        $binEsc = escapeshellarg($conf->WEBKIT_TO_PDF_BIN);
        
        // Скрипта за wkhtmltopdf
        $wk = $binEsc;
        
        // Ако е вдигнат флага
        if ($useXServer) {
            
            // Добавяме в настройките
            $wk .= " --use-xserver";
        }
        
        // Ако е зададено да се използва медиа тип за принтиране
        if ($conf->WEBKIT_TO_PDF_USE_PRINT_MEDIA_TYPE == 'yes') {
            
            // Добавяме в настройките
            $wk .= " --print-media-type";
        }
    
        // Ако е зададено да се използва grayscale
        if ($conf->WEBKIT_TO_PDF_USE_GRAYSCALE == 'yes') {
            
            // Добавяме в настройките
            $wk .= " --grayscale";
        }
        
    
        // Ако е зададен енкодинг за текущия фай;
        if ($conf->WEBKIT_TO_PDF_INPUT_ENCODING) {
            
            // Добавяме в настройките
            $wk .= " --encoding " . escapeshellarg($conf->WEBKIT_TO_PDF_INPUT_ENCODING);
        }
        
        // Ако има променливи за JS
        if ($jsScript) {
            
            // Добавяме към скрипта
            $wk .= " " . $jsScript;
        }
        
        // Добавяме изходните файлове
        $wk .= " {$htmlPathEsc} {$pdfPathEsc}";
        
        //Скрипта, който ще се изпълнява
        $exec = ($xvfb) ? "{$xvfb} {$wk}" : $wk;
        
        //Стартираме скрипта за генериране на pdf файл от html файл
        shell_exec($exec);
        
        //Качвания новосъздадения PDF файл
        $Fileman = cls::get('fileman_Files');
        
        // Ако възникне грешка при качването на файла (липса на права)
        try {
            
            // Качваме файла в кофата и му вземаме манипулатора
            $fh = $Fileman->addNewFile($pdfPath, $bucketName, $fileName); 
        } catch (Exception $e) {}
        
        //Изтриваме временната директория заедно с всички създадени папки
        core_Os::deleteDir($tempPath);
        
        //Връщаме манипулатора на файла
        return $fh;
    }
    
    
    /**
     * След началното установяване на този мениджър, ако е зададено -
     * той сетъпва външния пакет, чрез който ще се генерират pdf-те
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        // Вземаме конфига
        $confWebkit = core_Packs::getConfig('webkittopdf');
        
        // Опитваме се да вземем версията на webkit
        exec(escapeshellarg($confWebkit->WEBKIT_TO_PDF_BIN) . " -V", $resArr, $erroCode);
        
        // Вземаме масива с версията
        $versionArr = explode(" ", trim($resArr[1]));
        
        // Вземаме версията и подверсията
        list($version, $subVersion) = explode(".", trim($versionArr[1]));
        
        // Ако версията е над нулеват и подверсията е над 11-та
        if (($version > 0) || ($subVersion >= 11)) {
            
            // Ако не е избрана нищо
            if (!core_Packs::getConfigKey($confWebkit, 'WEBKIT_TO_PDF_USE_JS')) {
                
                // Избиране по подразбиране
                $data['WEBKIT_TO_PDF_USE_JS'] = 'yes';
                
                // Добавяме в конфигурацията
                core_Packs::setConfig('webkittopdf', $data);
                
                // Добавяме съобщение
                $res .= "<li style='color: green;'>" . 'Активирано е използване на JS при генериране на PDF' . "</li>";
            }
        }
    }
}
