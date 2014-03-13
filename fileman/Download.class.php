<?php



/**
 * @todo Чака за документация...
 */
defIfNot('EF_DOWNLOAD_ROOT', '_dl_');


/**
 * @todo Чака за документация...
 */
defIfNot('EF_DOWNLOAD_DIR', EF_INDEX_PATH . '/' . EF_SBF . '/' . EF_APP_NAME . '/' . EF_DOWNLOAD_ROOT);


/**
 * @todo Чака за документация...
 */
defIfNot('EF_DOWNLOAD_PREFIX_PTR', '$*****');




/**
 * Клас 'fileman_Download' -
 *
 *
 * @category  vendors
 * @package   fileman
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class fileman_Download extends core_Manager {
    
    
    /**
     * @todo Чака за документация...
     */
    var $pathLen = 6;
    
    
    /**
     * Заглавие на модула
     */
    var $title = 'Сваляния';
    
	
	/**
	 * Кой може да го разглежда?
	 */
	var $canList = 'admin, debug';
    
	
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Файлов манипулатор - уникален 8 символно/цифров низ, започващ с буква.
        // Генериран случайно, поради което е труден за налучкване
        $this->FLD("fileName", "varchar(255)", 'notNull,caption=Име');
        
        $this->FLD("prefix", "varchar(" . strlen(EF_DOWNLOAD_PREFIX_PTR) . ")",
            array('notNull' => TRUE, 'caption' => 'Префикс'));
        
        // Име на файла
        $this->FLD("fileId",
            "varchar(32)",
            array('notNull' => TRUE, 'caption' => 'Файл'));
        
        // Крайно време за сваляне
        $this->FLD("expireOn",
            "datetime",
            array('caption' => 'Активен до'));
        
        // Плъгини за контрол на записа и модифицирането
        $this->load('plg_Created,Files=fileman_Files,fileman_Wrapper,Buckets=fileman_Buckets');
        
        // Индекси
        $this->setDbUnique('prefix');
    }
    
    
    /**
     * Връща URL за сваляне на файла с валидност publicTime часа
     * 
     * @param string $src - Манипулатор на файл, път до файл или URL
     * @param integer $lifeTime - Колко време да се пази линка (в часове)
     * @param string $type -  - Типа на сорса - handler, url, path
     * 
     * @return URL - Линк към файла
     */
    static function getDownloadUrl($src, $lifeTime = 1, $type = 'handler')
    {
        // Очакваме типа да е един от дадените
        expect(in_array($type, array('url', 'path', 'handler')));
        
        // Ако е подаден празен стринг
        if (!trim($src)) return FALSE;

        // Ако типа е URL
        if ($type == 'url') {
            
            // Връщаме сорса
            return $src;
        } elseif ($type == 'handler') {
            // Ако е манипулато на файл
            
            // Намираме записа на файла
            $fRec = fileman_Files::fetchByFh($src);
            
            // Ако няма запис връщаме
            if(!$fRec) return FALSE;
            
            // Името на файла
            $name = $fRec->name;
            
            // id' то на файла
            $fileId = $fRec->id;
            
            // Пътя до файла
            $originalPath = fileman_Files::fetchByFh($fRec->fileHnd, 'path');
        } else {
            // Ако е път до файл

            // Ако не е подаден целия път до файла
            if (!is_file($src)) {
                
                // Пътя до файла
                $originalPath = getFullPath($src);
            } else {
                
                // Целия път до файла
                $originalPath = $src;
            }
            
            // Ако не е файл
            if (!is_file($originalPath)) return FALSE;
            
            // Времето на последна модификация на файла
            $fileTime = filemtime($originalPath);
            
            // id' то на файла - md5 на пътя и времето
            $fileId = md5($originalPath . $fileTime);
            
            // Името на файла
            $name = basename($originalPath);
        }
        
        // Генерираме времето на изтриване
        $time = dt::timestamp2Mysql(time() + $lifeTime * 3600);
        
        // Записите за файла
        $dRec = static::fetch("#fileId = '{$fileId}'");

        // Ако имаме линк към файла, тогава използваме същия линк
        if ($dRec) {
            
            // Ако времето, за което е активен линка е по малко от времето, което искаме да зададем
            if ($dRec->expireOn < $time) {
                
                // Променяме времето
                $dRec->expireOn = $time;
            }
            
            // Вземаме URL
            $link = static::getSbfDownloadUrl($dRec, TRUE);
            
            // Записваме
            static::save($dRec);
            
            // Връщаме URL' то
            return $link;
        }
        
        // Обект
        $rec = new stdClass();
        
        // Генерираме името на директорията - префикс
        // Докато не се генерира уникално име в модела
        do {
            $rec->prefix = str::getRand(EF_DOWNLOAD_PREFIX_PTR);
        } while (static::fetch("#prefix = '{$rec->prefix}'"));
        
        // Задаваме името на файла за сваляне - същото, каквото файла има в момента
        $rec->fileName = $name;
        
        // Ако няма директория
        if(!is_dir(EF_DOWNLOAD_DIR . '/' . $rec->prefix)) {
            
            // Създаваме я
            mkdir(EF_DOWNLOAD_DIR . '/' . $rec->prefix, 0777, TRUE);
        }
        
        // Генерираме пътя до файла (hard link) който ще се сваля
        $downloadPath = EF_DOWNLOAD_DIR . '/' . $rec->prefix . '/' . $rec->fileName;
        
        // Създаваме хард-линк или копираме
        if(!@copy($originalPath, $downloadPath)) {
            error("Не може да бъде копиран файла|* : '{$originalPath}' =>  '{$downloadPath}'");
        }
        
        // Задаваме id-то на файла
        $rec->fileId = $fileId;
        
        // Задаваме времето, в което изтича възможността за сваляне
        $rec->expireOn = $time;
        
        // Записваме информацията за свалянето, за да можем по-късно по Cron да
        // премахнем линка за сваляне
        static::save($rec);
        
        // Връщаме линка за сваляне
        return static::getSbfDownloadUrl($rec, TRUE);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function act_Download()
    {
        // Манипулатора на файла
        $fh = Request::get('fh');
        
        // Очакваме да има подаден манипулатор
        expect($fh, 'Липсва манупулатора на файла');
        
        // Ескейпваме манупулатора
        $fh = $this->db->escape($fh);
        
        // Вземаме записа на манипулатора
        $fRec = $this->Files->fetchByFh($fh);
        
        // Очакваме да има такъв запис
        expect($fRec, 'Няма такъв запис.');
        
        // TODO не е необходимо да има права за сваляне ?
        // Очакваме да има права за сваляне
//        $this->Files->requireRightFor('download', $fRec);
        
        // Генерираме линк за сваляне
        $link = $this->getDownloadUrl($fh, 1);
        
        // Ако искам да форсираме свалянето
//        if (Request::get('forceDownload')) {
//
//            // Големина на файла
//            $fileLen = fileman_Data::fetchField($fRec->dataId, 'fileLen');
//            
//            // 1024*1024
//            $chunksize = 1048576;
//            
//            // Големината на файловете, над която ще се игнорира forceDownload
//            $chunksizeOb = 30 * $chunksize;
//
//            // Ако файла е по - малък от $chunksizeOb
//            if ($fileLen < $chunksizeOb) {
//
//                // Задаваме хедърите
//                header('Content-Description: File Transfer');
//                header('Content-Type: application/octet-stream');
//                header('Content-Disposition: attachment; filename='.basename($link));
//                header('Content-Transfer-Encoding: binary');
//                header('Expires: 0');
//                header('Cache-Control: must-revalidate');
//                header('Content-Length: ' . $fileLen);
//                header("Connection: close");
//                
////                header('Pragma: public'); //TODO Нужен е когато се използва SSL връзка в браузъри на IE <= 8 версия
////                header("Pragma: "); // TODO ако има проблеми с някои версии на IE
////                header("Cache-Control: "); // TODO ако има проблеми с някои версии на IE
//
//                // Ако е файла по - малък от 1 MB
//                if ($fileLen < $chunksize) { 
//                    
//                    // Предизвикваме сваляне на файла
//                    readfile($link);  
//                } else {
//                    
//                    // Стартираме нов буфер
//                    ob_start();
//                    
//                    // Вземаме манипулатора на файла
//                    $handle = fopen($link, 'rb'); 
//                    $buffer = ''; 
//                    
//                    // Докато стигнем края на файла
//                    while (!feof($handle)) { 
//                        
//                        // Вземаме част от файла
//                        $buffer = fread($handle, $chunksize); 
//                        
//                        // Показваме го на екрана
//                        echo $buffer; 
//                        
//                        // Изчистваме буфера
//                        ob_flush(); 
//                        flush(); 
//                    } 
//                    
//                    // Затваряме файла
//                    fclose($handle);
//                    
//                    // Спираме буфера, който сме стартирали
//                    ob_end_clean();
//                }
//                
//                // Прекратяваме изпълнението на скрипта
//                shutdown();
//            }
//        }
        
        // Редиректваме към линка
        redirect($link);  
    }
    
    
    /**
     * Изтрива линковете, които не се използват и файловете им
     */
    function clearOldLinks()
    {
        $now = dt::timestamp2Mysql(time());
        $query = self::getQuery();
        $query->where("#expireOn < '{$now}'");
        
        $htmlRes .= "<hr />";
        
        $count = $query->count();
        
        if (!$count) {
            $htmlRes .= "\n<li style='color:green'> Няма записи за изтриване.</li>";
        } else {
            $htmlRes .= "\n<li'> {$count} записа за изтриване.</li>";
        }
        
        while ($rec = $query->fetch()) {
            
            $htmlRes .= "<hr />";
            
            $dir = static::getDownloadDir($rec);
            
            if (self::delete("#id = '{$rec->id}'")) {
                $htmlRes .= "\n<li> Deleted record #: $rec->id</li>";
                
                if (core_Os::deleteDir($dir)) {
                    $htmlRes .= "\n<li> Deleted dir: $rec->prefix</li>";
                } else {
                    $htmlRes .= "\n<li style='color:red'> Can' t delete dir: $rec->prefix</li>";
                }
            } else {
                $htmlRes .= "\n<li style='color:red'> Can' t delete record #: $rec->id</li>";
            }
        }
        
        return $htmlRes;
    }
    
    
    /**
     * Стартиране на процеса за изтриване на ненужните файлове
     */
    function act_ClearOldLinks()
    {
        $clear = $this->clearOldLinks();
        
        return $clear;
    }
    
    
    /**
     * Стартиране на процеса за изтриване на ненужните файлове по крон
     */
    function cron_ClearOldLinks()
    {
        $clear = $this->clearOldLinks();
        
        return $clear;
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        if(!is_dir(EF_DOWNLOAD_DIR)) {
            if(!mkdir(EF_DOWNLOAD_DIR, 0777, TRUE)) {
                $res .= '<li><font color=red>' . tr('Не може да се създаде директорията') .
                ' "' . EF_DOWNLOAD_DIR . '</font>';
            } else {
                $res .= '<li>' . tr('Създадена е директорията') . ' <font color=green>"' .
                EF_DOWNLOAD_DIR . '"</font>';
            }
        }
        
        if( CORE_OVERWRITE_HTAACCESS ) {
            $filesToCopy = array(
                core_App::getFullPath('fileman/tpl/htaccessDL.txt') => EF_DOWNLOAD_DIR . '/.htaccess',
            );
            
            foreach($filesToCopy as $src => $dest) {
                if(copy($src, $dest)) {
                        $res .= "<li style='color:green;'>Копиран е файла: <b>{$src}</b> => <b>{$dest}</b></li>";
                } else {
                        $res .= "<li style='color:red;'>Не може да бъде копиран файла: <b>{$src}</b> => <b>{$dest}</b></li>";
                }
            }
        }
        
        $res .= "<p><i>Нагласяне на Cron</i></p>";
        
        $rec = new stdClass();
        $rec->systemId = 'ClearOldLinks';
        $rec->description = 'Изчиства старите линкове за сваляне';
        $rec->controller = $mvc->className;
        $rec->action = 'ClearOldLinks';
        $rec->period = 100;
        $rec->offset = 0;
        $rec->delay = 0;
        
        // $rec->timeLimit = 200;
        
        $Cron = cls::get('core_Cron');
        
        if ($Cron->addOnce($rec)) {
            $res .= "<li><font color='green'>Задаване на крон да изчиства линкове и директории, с изтекъл срок.</font></li>";
        } else {
            $res .= "<li>Отпреди Cron е бил нагласен да изчиства линкове и директории, с изтекъл срок.</li>";
        }

        return $res;
    }
    
    
    /**
     * Ако имаме права за сваляне връща html <а> линк за сваляне на файла.
     */
    static function getDownloadLink($fh, $title=NULL)
    {
    	$conf = core_Packs::getConfig('fileman');
    	
        //Намираме записа на файла
        $fRec = fileman_Files::fetchByFh($fh);
        
        //Проверяваме дали сме отркили записа
        if(!$fRec) return FALSE;
        
		// Дали файла го има? Ако го няма, вместо линк, връщаме името му
		$path = fileman_Files::fetchByFh($fh, 'path');
        
		// Тримваме титлата
		$title = trim($title);

		// Ако сме подали
		if ($title) {
		    
		    // Използваме него за име
		    $name = $title;
		    
		    // Обезопасяваме името
		    $name = core_Type::escape($name);
		} else {
		    
		    // Ако не е подадено, използваме името на файла
		    
		    //Името на файла
            $name = fileman_Files::getVerbal($fRec, 'name');
		}
        
        //Разширението на файла
        $ext = fileman_Files::getExt($fRec->name);
        
        //Иконата на файла, в зависимост от разширението на файла
        $icon = "fileman/icons/{$ext}.png";
        
        //Ако не можем да намерим икона за съответното разширение, използваме иконата по подразбиране
        if (!is_file(getFullPath($icon))) {
            $icon = "fileman/icons/default.png";
        }
        
        // Икона на линка
        $attr['ef_icon'] = $icon;
        
        // Клас на връзката
        $attr['class'] = 'file';

        // Ограничаваме максиманата дължина на името на файла
        $nameFix = str::limitLen($name, 32);

        if($nameFix != $name) {
            $attr['title'] = $name;
        }

        //Инстанция на класа
        $FileSize = cls::get('fileman_FileSize');
        
        // Титлата пред файла в plain режим
        $linkFileTitlePlain = tr('Файл') . ": ";
        
        // Ако има данни за файла и съществува
        if (($fRec->dataId) && file_exists($path)) {
            
            // Ако сме в текстов режим
            if(Mode::is('text', 'plain')) {
                
                //Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml
                $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
                
                //Линк към файла
                $link = toUrl(array('fileman_Download', 'Download', 'fh' => $fh), $isAbsolute);
                
                //Добаваме линка към файла
                $link = "{$linkFileTitlePlain}$name ( $link )";
            } else {
                
                //Големината на файла в байтове
                $fileLen = fileman_Data::fetchField($fRec->dataId, 'fileLen');
                
                //Преобразуваме големината на файла във вербална стойност
                $size = $FileSize->toVerbal($fileLen);
    
                // Ако линка е в iframe да се отваря в родителския(главния) прозорец
                $attr['target'] = "_parent";
                
                //Ако сме в режим "Тесен"
                if (Mode::is('screenMode', 'narrow')) {
                    
                    //Ако големината на файла е по - голяма от константата
                    if ($fileLen >= $conf->LINK_NARROW_MIN_FILELEN_SHOW) {
                        
                        //След името на файла добавяме размера в скоби
                        $nameFix = $nameFix . "&nbsp;({$size})";     
                    }
                } else {
                    
                    //Заместваме &nbsp; с празен интервал
                    $size =  str_ireplace('&nbsp;', ' ', $size);
                    
                    //Добавяме към атрибута на линка информация за размера
                    $attr['title'] .= ($attr['title'] ? "\n" : '') . tr("|Размер:|* {$size}");
                }
                
                //Генерираме връзката 
                $url  = static::generateUrl($fh);
                $link = ht::createLink($nameFix, $url, NULL, $attr);
            }
        } else {
            
            // Ако няма файл
            
            // Ако сме в текстов режим
            if(Mode::is('text', 'plain')) {
                
                // Линка 
                $link = $linkFileTitlePlain . $name;
            } else {
                if(!file_exists($path)) {
    				$attr['style'] .= ' color:red;';
    			}
                //Генерираме името с иконата
                $link = "<span class='linkWithIcon' style=\"" . $attr['style'] . "\"> {$nameFix} </span>";
            }
        }
        
        return $link;
    }
    
    
    /**
     * Прекъсваема функция за генериране на URL от манипулатор на файл
     */
    static function generateUrl_($fh)
    {
        // Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
        $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
        
        //Генерираме връзката 
        $url = toUrl(array('fileman_Files', 'Single', $fh), $isAbsolute);
        
        return $url;
    }
    
    
    /**
     * Връща линк за сваляне, според ID-то
     */
    static function getDownloadLinkById($id)
    {
        $fh = fileman_Files::fetchField($id, 'fileHnd');
        
        return fileman_Download::getDownloadLink($fh);
    }


    /**
     * Екшън за генериране на линк за сваляне на файла
     */
    function act_GenerateLink()
    {
        //Права за работа с екшън-а
        requireRole('user');
        
        // Манипулатора на файла
        $fh = Request::get('fh');
        
        // Очакваме да има подаден манипулатор на файла
        expect($fh, 'Липсва манупулатора на файла');
        
        // Ескейпваме манипулатора
        $fh = $this->db->escape($fh);

        // Записа за съответния файл
        $fRec = $this->Files->fetchByFh($fh);
        
        // Очакваме да има такъв запис
        expect($fRec, 'Няма такъв запис.');
        
        // Проверяваме за права за сваляне на файла
        $this->Files->requireRightFor('download', $fRec);
        
        
        $this->FNC('activeMinutes', 'enum(
    										0.5 = Половин час, 
    										1=1 час,
    										3=3 часа,
    										5=5 часа,
    										12=12 часа,
    										24=1 ден,
    										168=1 седмица
    								 	  )', 'caption=Валидност, mandatory');
        
        
        //URL' то където ще се редиректва при отказ
        $retUrl = getRetUrl();
        $retUrl = ($retUrl) ? ($retUrl) : (array('fileman_Files', 'single', $fh));
        
        // Вземаме формата към този модел
        $form = $this->getForm();
        
        // Въвеждаме id-то (и евентуално други silent параметри, ако има)
        $form->input(NULL, 'silent');
        
        // Въвеждаме съдържанието на полетата
        $form->input('activeMinutes');
        
        // Ако формата е изпратена без грешки, показваме линка за сваляне
        if($form->isSubmitted()) {
            
            // Вземаме линка, за да може да се запише новото време до когато е активен линка
            $link = self::getDownloadUrl($fRec->fileHnd, $form->rec->activeMinutes);
            
            // Редиректваме на страницата за информация
            Redirect(array('fileman_Files', 'single', $fh, 'currentTab' => 'info', '#' => 'fileDetail'));
        }
        
        // По подразбиране 12 часа да е активен
        $form->setDefault('activeMinutes', 12);
        
        // Задаваме да се показват само полетата, които ни интересуват
        $form->showFields = 'activeMinutes';
        
        // Добавяме бутоните на формата
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close16.png');

        $fileName = fileman_Files::getVerbal($fRec, 'name');
        
        // Добавяме титлата на формата
        $form->title = "Генериране на линк за|* {$fileName}";
        
        return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Връща SBF линк за сваляне на файла
     * 
     * @param object $rec - Записа за файла
     * @param boolean $absolute - Дали линка да е абсолютен или не
     * 
     * @return string $link - Текстов линк за сваляне
     */
    static function getSbfDownloadUrl($rec, $absolute=FALSE)
    {
        // Линка на файла
        $link = sbf(EF_DOWNLOAD_ROOT . '/' . $rec->prefix . '/' . $rec->fileName, '', $absolute);
        
        return $link;
    }
    
    
    /**
     * Връща директорията, в който е записан файла
     * 
     * @param fileman_Download $rec - Записа, за който търсим директорията
     */
    static function getDownloadDir($rec)
    {
        // Очакваме да е обект
        expect(is_object($rec), 'Не сте подали запис');
        
        // Директорията на файла
        $dir = EF_DOWNLOAD_DIR . '/' . $rec->prefix;
        
        return $dir;
    }
    
    
    /**
     * Изтрива подадени файл от sbf директорията и от модела
     * 
     * @param fileman_Files $fileId - id' то на записа, който ще изтриваме
     */
    static function deleteFileFromSbf($fileId)
    {
        // Очакваме да има 
        expect($fileId);
        
        // Ако има такъм запис
        if ($rec = static::fetch("#fileId = '{$fileId}'")) {
            
            // Директорията, в която се намира
            $dir = static::getDownloadDir($rec);
        
            // Изтриваме директорията
            core_Os::deleteDir($dir);
            
            // Изтриваме записа от модела
            $deleted = static::delete("#fileId = '{$fileId}'");    
        }
    }
}
