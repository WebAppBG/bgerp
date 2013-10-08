<?php
 
/**
 * Генериране на PDF файлове от HTML файл чрез web kit
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_PdfCreator extends core_Manager
{
    
    const PDF_BUCKET = 'pdf';
    
    /**
     * Заглавие
     */
    var $title = "Генерирани PDF документи";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'doc_Wrapper, plg_Created, plg_RowTools';
    
    
    /**
     * Кой има право да го чете?
     */
    var $canRead = 'admin, ceo';
    
    
    /**
     * Кой има право да го променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin, ceo';
    
    
    /**
     * Кой има право да изтрива?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Кой има права за имейли-те?
     */
    var $canEmail = 'admin, ceo';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Име,mandatory');
        $this->FLD('fileHnd', 'fileman_FileType(bucket=' . self::PDF_BUCKET . ')', 'caption=Файл,mandatory');
        $this->FLD('md5', 'varchar(32)', 'caption=MD5');
        
        $this->setDbUnique('md5');
    }
    
    
    /**
     * Създава pdf файл и връща манипулатора му
     */
    static function convert($html, &$name)
    {
        // Шаблона
        $htmlET = $html;
        
        // Класа в зависимост от режима
        $class = Mode::is('screenMode', 'narrow') ? $class='narrow' : 'wide';
        
        // Добавяме класа
        $html = "<div class='{$class}'>" . $html . "</div>";
        
        $md5 = md5($html);
        
        //Проверяваме дали файла със същото име съществува в кофата
        $fileHnd = doc_PdfCreator::fetchField("#md5='{$md5}'", 'fileHnd');

        if($fileHnd && isDebug()) {
            doc_PdfCreator::delete("#fileHnd = '{$fileHnd}'");
            // TODO:: да се махне и от fileman
            unset($fileHnd);
        }
        
        //Ако не съществува
        if (!$fileHnd) {
            
            //Вземаме всичките css стилове
            $css = file_get_contents(sbf('css/common.css', "", TRUE)) .
                "\n" . file_get_contents(sbf('css/Application.css', "", TRUE)) . 
                "\n" . getFileContent('css/email.css') . 
                "\n" . getFileContent('css/pdf.css');
            
            // Ако е инстанция на core_ET
            if ($htmlET instanceof core_ET) {
                
                // Вземаме масива с всички чакащи CSS файлове
                $cssArr = $htmlET->getArray('CSS', FALSE);
                
                // Обхождаме масива
                foreach ((array)$cssArr as $cssPath) {
                    try {
                        
                        // Опитваме се да вземаме съдържанието на CSS
                        $css .= file_get_contents(sbf($cssPath, "", TRUE));
                    } catch (Exception $e) {
                        
                        // Ако възникне грешка, добавяме в лога
                        static::log('Не може да се взема CSS файла: ' . $cssPath);
                    }
                }
                
                // Вземаме всички стилове
                $styleArr = $htmlET->getArray('STYLES', FALSE);
                
                // Обхождаме масива със стиловете
                foreach ((array)$styleArr as $styles) {
                    
                    // Добавяме към CSS-а
                    $css .= "\n" . $styles;
                }
            }
            
            $html = self::removeFormAttr($html);
            
            //Добавяме всички стилове inline
            $html = '<div id="begin">' . $html . '<div id="end">';
            
            // Вземаме конфигурацията на пакета csstoinline
            $conf = core_Packs::getConfig('csstoinline');
            
            // Класа
            $CssToInline = $conf->CSSTOINLINE_CONVERTER_CLASS;
            
            // Инстанция на класа
            $CssToInlineInst = cls::get($CssToInline);
            
            // Стартираме процеса
            $html = $CssToInlineInst->convert($html, $css); 
            
            $html = str::cut($html, '<div id="begin">', '<div id="end">');
            
            $name = self::createPdfName($name);
            
            // Вземаме конфигурацията на пакета doc
            $confDoc = core_Packs::getConfig('doc');

            $PdfCreatorInst = cls::get($confDoc->BGERP_PDF_GENERATOR);
            
            // Емулираме xhtml режим
            Mode::push('text', 'xhtml');
            
            // Вземаме всички javascript файлове, които ще се добавят
            $jsArr['JS'] = $htmlET->getArray('JS', FALSE);
            
            // Вземаме всеки JQUERY код, който ще се добави
            $jsArr['JQUERY_CODE'] = $htmlET->getArray('JQUERY_CODE', FALSE);
            
            // Стартираме конвертирането
            $fileHnd = $PdfCreatorInst->convert($html, $name, self::PDF_BUCKET, $jsArr);
            
            // Връщаме предишната стойност
            Mode::pop('text');
            
            //Записваме данните за текущия файл
            $rec = new stdClass();
            $rec->name = $name;
            $rec->md5 = $md5;
            $rec->fileHnd = $fileHnd;
            
            doc_PdfCreator::save($rec);
        }
        
        return $fileHnd;
    }
    
    
    /**
     * Преобразува името на файла да е с разширение .pdf
     */
    static function createPdfName($name)
    {
        $name = mb_strtolower($name);
        
        //Проверява разширението дали е PDF
        if (($dotPos = mb_strrpos($name, '.')) !== FALSE) {
            //Вземаме разширението
            $ext = mb_strtolower(mb_substr($name, $dotPos + 1));
            
            //Ако разширението е pdf връщаме
            if ($ext == 'pdf') {
                
                return $name;
            }
        }
        
        $name = $name . '.pdf';
        
        return $name;
    }
    
    
    /**
     * След началното установяване на този мениджър, ако е зададено -
     * той сетъпва външния пакет, чрез който ще се генерират pdf-те
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        // Вземаме конфига
    	$conf = core_Packs::getConfig('doc');
    	
    	// Пакета, който ще се инсталира
    	$setupPack = 'dompdf';
    	
    	// Ако няма запис в модела
    	if (!$conf->_data['BGERP_PDF_GENERATOR']) {
    	    
    	    // Вземаме конфига
    	    $confWebkit = core_Packs::getConfig('webkittopdf');
    	    
    	    // Изпълянваме процеса
    	    exec(escapeshellarg($confWebkit->WEBKIT_TO_PDF_BIN), $dummy, $erroCode);
    	    
    	    // Ако я има инсталирана
            if ($erroCode == 1 || $erroCode == 0) {
                
                // Да използваме webkittopdf
    	        $data['BGERP_PDF_GENERATOR'] = core_Classes::getId('webkittopdf_Converter');
    	        
    	        // Добавяме в записите
                core_Packs::setConfig('doc', $data);
                
                // Пакета, който ще се инсталира да е webkittopdf
                $setupPack = 'webkittopdf';
            }
    	}
    	
    	// Инсталираме пакета
        $Packs = cls::get('core_Packs');
        $res .= $Packs->setupPack($setupPack);
    	
        //Създаваме, кофа, където ще държим всички генерирани PDF файлове
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket(self::PDF_BUCKET, 'PDF-и на документи', NULL, '104857600', 'user', 'user');
    }
    
    
	/**
     * Изчиства всикo което е между <form> ... </form>
     */
    static function removeFormAttr($html)
    {
        //Шаблон за намиране на <form ... </form>
        $pattern = '/\<form.*\<\/form\>/is';
        
        //Премахваме всикo което е между <form> ... </form>
        $html = preg_replace($pattern, '', $html);

        return $html;
    }
}