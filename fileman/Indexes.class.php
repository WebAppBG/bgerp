<?php

/**
 * Информация за всички файлове във fileman_Files
 *
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_Indexes extends core_Manager
{
    
    
    /**
     * Заглавие на таблицата
     */
    var $title = "Информация за файловете";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin, debug';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'no_one';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'fileman_Wrapper, plg_RowTools, plg_Created';
    
    
    /**
     * 
     */
    public $interfaces = 'fileman_ProcessIntf';
    
    
    /**
     * Масив с разширенията и минималните размери, на които ще се пускат обработки за OCR, при генериране на ключови думи
     */
    public static $ocrIndexArr = array('jpg' => 10000, 'jpeg' => 10000, 'png' => 10000, 'bmp' => 50000, 'tif' => 20000, 'tiff' => 20000, 'pdf' => 20000);
    
    
    /**
     * Максимален размер на файлове, на които ще се пуска OCR
     */
    public static $ocrMax = 20000000;
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('dataId', 'key(mvc=fileman_Data)', 'caption=Данни на файл,notNull');
        $this->FLD('type', 'varchar(32)', 'caption=Тип');
        $this->FLD('content', 'blob(1000000)', 'caption=Съдържание');
        
        $this->setDbUnique('dataId,type');
    }
    
    
    /**
     * Подготвя данните за информацията за файла
     */
    static function prepare_(&$data, $fh)
    {
        // Записи за текущия файл
        $data->fRec = fileman_Files::fetchByFh($fh);

        // Разширението на файла
        $ext = fileman_Files::getExt($data->fRec->name);
        
        // Вземаме уеб-драйверите за това файлово разширение
        $webdrvArr = self::getDriver($ext);

        // Обикаляме всички открити драйвери
        foreach($webdrvArr as $drv) {
            
            // Стартираме процеса за извличане на данни
            $drv->startProcessing($data->fRec);
            
            // Комбиниране всички открити табове
            $data->tabs = arr::combine($data->tabs, $drv->getTabs($data->fRec));
        }
    }
    
    
    /**
     * Рендира информацията за файла
     */
    static function render_($data)
    {
        // Масив с всички табове
        $tabsArr = $data->tabs;

        if(! count($data->tabs)) return FALSE;

        // Подреждаме масивити според order
        $tabsArr = static::orderTabs($tabsArr);

        // Ако е избран някой таб
        if ($tabsArr[$data->currentTab]) {
            
            // Задаваме той да е текущия
            $currentTab = $data->currentTab;    
        } elseif ($tabsArr[$tabsArr['__defaultTab']]) {
            
            // Ако не е избран таб, избираме таба по подразбиране зададен от класа
            $currentTab = $tabsArr['__defaultTab'];
        } else {
            
            unset($tabsArr['__defaultTab']);
            
            // Ако нито едно от двете не сработи, вземаме първия таб
            $currentTab = key($tabsArr);    
        }
        
        // Създаваме рендер на табове
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));
        
        // Обикаляме всички табове
        foreach($tabsArr as $name => $rec) {
           
            // Ако не е таб
            if (strpos($name, '__') === 0) continue;

            // Ако е текущия таб таб
            if($name == $currentTab) {
                 $tabs->TAB($name, $rec->title,  array('currentTab' => $name, 'id' => $data->rec->fileHnd, '#' => 'fileDetail'));
                 
                 // Вземаме съдържанеито на тялот
                 $body = $rec->html;
            } else {

                // Създаваме таб
                $tabs->TAB($name, $rec->title, array('currentTab' => $name, 'id' => $data->rec->fileHnd, '#' => 'fileDetail'));
            }
        }
        
        // Рендираме съдържанието на таба
        $tpl = $tabs->renderHtml($body, $currentTab);
        
        // Ако има подаден шаблон
        if ($tabsArr[$currentTab]->tpl) {
            
            // Добавяме чакащите елементи от шаблона
            $tpl->processContent($tabsArr[$currentTab]->tpl); // TODO вероятно ще се промени    
        }
        
        return $tpl;
    }
    

    /**
     * Връща масив от инстанции на уеб-драйвери за съответното разширение
     * Първоначалните уеб-драйвери на файловете се намират в директорията 'fileman_webdrv'
     */
    static function getDriver_($ext, $pathArr = array('fileman_webdrv'))
    {   
        // Разширението на файла
        $ext = strtolower($ext);

        // Масив с инстанциите на всички драйвери, които отговарят за съответното разширение
        $res = array();

        // Обхождаме масива с пътищата
        foreach($pathArr as $path) {
            
            // Към пътя добавяме разширението за да получим драйвера
            $className = $path . '_' . $ext;
            
            // Ако има такъв клас
            if(cls::load($className, TRUE)) {
                
                // Записваме инстанцията му
                $res[] = cls::get($className);
            }
        }

        // Ако не може да се намери нито един драйвер
        if(count($res) == 0) {
            
            // Създаваме инстанция на прародителя на драйверите
            $res[] = cls::get('fileman_webdrv_Generic');
        }

        // Връщаме масива
        return $res;
    }
    

    /**
     * Връща десериализараната информация за съответния файл и съответния тип
     * 
     * @param fileHandler $fileHnd - Манипулатор на файла
     * @param string $type - Типа на файла
     * 
     * @return mixed $content - Десериализирания стринг
     */
    static function getInfoContentByFh($fileHnd, $type)
    {
        // Записите за файла
        $fRec = fileman_Files::fetchByFh($fileHnd);
        
        // Вземаме разширението на файла, от името му
        $ext = fileman_Files::getExt($fRec->name);
        
        // Масив с всички драйвери
        $drivers = static::getDriver($ext);
        
        // Обхождаме намерените драйверо
        foreach ($drivers as $driver) {
            
            // Проверяваме дали имат съответния метод
            if (method_exists($driver, 'getInfoContentByFh')) {
                
                // Вземамем съдържанието
                $content = $driver::getInfoContentByFh($fileHnd, $type);
                
                // Ако открием съдържание, връщаме него
                if ($content !== FALSE) return $content;
            }
        }
        
        // Вземаме текстовата част за съответното $dataId
        $rec = fileman_Indexes::fetch(array("#dataId = '[#1#]' AND #type = '[#2#]'", $fRec->dataId, $type), '*', FALSE);

        // Ако няма такъв запис
        if (!$rec) return FALSE;
        
        return static::decodeContent($rec->content);
    }
    
    
	/**
     * Декодираме подадения текст
     * 
     * @param string $content - Текста, който да декодираме
     * 
     * @return string $content - Променения текст
     */
    static function decodeContent($content)
    {
        // Вземаме конфигурацията
        $conf = core_Packs::getConfig('fileman');
        
        // Променяме мемори лимита
        ini_set("memory_limit", $conf->FILEMAN_DRIVER_MAX_ALLOWED_MEMORY_CONTENT);

        // Декодваме
        $content = base64_decode($content);
        
        // Декомпресираме
        $content = gzuncompress($content);
        
        // Десериализираме съдържанието
        $content = unserialize($content);
        
        return $content;
    }
    
    
    /**
     * Подреждане на табовете в зависимост от order
     */
    static function orderTabs($tabsArr)
    {
        // Подреждаме масива
        core_Array::orderA($tabsArr);

        return $tabsArr;
    }
    

    /**
     * Проверява дали файла е заключен или записан в БД
     * 
     * @param array $params - Масив с допълнителни променливи
     * @param boolean $trim
     * 
     * @return boolean - Връща TRUE ако файла е заключен или има запис в БД
     * 
     * @access protected
     */
    static function isProcessStarted($params, $trim=FALSE)
    {
        // Ако няма lockId
        if (!$params['lockId']) {
            $params['lockId'] = fileman_webdrv_Generic::getLockId($params['type'], $params['dataId']);
        }

        // Ако процеса е заключен
        if (core_Locks::isLocked($params['lockId'])) return TRUE;
        
        // Ако има такъв запис
        if ($params['dataId'] && $rec = fileman_Indexes::fetch("#dataId = '{$params['dataId']}' AND #type = '{$params['type']}'")) {
            
            $conf = core_Packs::getConfig('fileman');
            
            // Времето след което ще се изтрият
            $time = time() - $conf->FILEMAN_WEBDRV_ERROR_CLEAN;
            
            // Съдържанието
            $content = fileman_Indexes::decodeContent($rec->content);
            
            // Ако в индекса е записана грешка
            if (($content->errorProc) && (dt::mysql2timestamp($rec->createdOn) < $time)) {
                
                // Изтрива съответния запис
                fileman_Indexes::delete($rec->id); 
                
                // Връщаме FALSE, за да укажем, че няма запис
                return FALSE;   
            } else {
                
                // Ако е обект
                if (is_object($content)) {
                    
                    // Вземаме грешката
                    $content = $content->errorProc;
                }
                
                // Ако е задедено да се провери съдържанието
                if (($trim) && (!trim($content))) return FALSE;
                
                return TRUE;
            } 
        }

        return FALSE;
    }

    
    /**
     * Подготвяме content частта за по добър запис
     * 
     * @param string $text - Текста, който да променяме
     * 
     * @return string $text - Променения текст
     */
    static function prepareContent($text)
    {
        // Вземаме конфигурацията
        $conf = core_Packs::getConfig('fileman');
        
        // Променяме мемори лимита
        ini_set("memory_limit", $conf->FILEMAN_DRIVER_MAX_ALLOWED_MEMORY_CONTENT);

        // Сериализираме
        $text = serialize($text);
        
        // Компресираме
        $text = gzcompress($text);
        
        // Енкодваме
        $text = base64_encode($text);    
                
        return $text;
    }
    
    
    /**
     * Записваме подадени параметри в модела
     * 
     * @param array $params - Подадените параметри
     * $params['dataId'] - key fileman_Data
     * $params['type'] - Типа на файла
     * $params['createdBy'] - Създадено от
     * $params['content'] - Съдържанието
     * 
     */
    static function saveContent($params)
    {
        if (!$params['dataId'] && !is_numeric($params['dataId'])) return ;
        
        $rec = new stdClass();
        $rec->dataId = $params['dataId'];
        $rec->type = $params['type'];
        $rec->createdBy = $params['createdBy'];
        $rec->content = static::prepareContent($params['content']);
        
        $saveId = static::save($rec, NULL, 'IGNORE');
        
        if (!$saveId && !is_object($params['content'])) {
            $recOld = self::fetch(array("#dataId = '[#1#]' AND #type = '[#2#]'", $rec->dataId, $rec->type));
            
            if ($recOld) {
                $content = self::decodeContent($recOld->content);
                if (is_object($content)) {
                    $saveId = static::save($rec, NULL, 'REPLACE');
                }
            }
        }
        
        return $saveId;
    }
    
    
	/**
     * Проверява дали има грешка. Ако има грешка, записваме грешката в БД.
     * 
     * @param string $file - Пътя до файла, който ще се проверява
     * @param array $params - Други допълнителни параметри
     * 
     * @return boolean - Ако не открие грешка, връща FALSE
     */
    static function haveErrors($file, $params)
    {
        $haveErrFile = FALSE;
        
        // Ако е файл в директория
        if (strstr($file, '/')) {
            
            // Ако е валиден файл
            $isValid = is_file($file);
            
            // Ако няма валиден файл записваме грешката в лога
            if (!$isValid) {
                
                if (($errFilePath = $params['errFilePath']) && is_file($errFilePath)) {
                    
                    $haveErrFile = TRUE;
                    $errContent = file_get_contents($errFilePath);
                    
                    $errContent = trim($errContent);
                    
                    // Записваме грешката в дебъг лога
                    if ($errContent) {
                        fileman_Indexes::logErr($errContent);
                    }
                }
            }
        } else {
            
            // Ако е манупулатор на файл
            $isValid = fileman_Files::fetchField("#fileHnd='{$file}'");
        }
        
        // Ако има файл
        if ($isValid) return FALSE;
        
        // Създаваме запис за грешка
        static::createError($params);

        // Записваме грешката в лога
        static::createErrorLog($params['dataId'], $params['type']);
        
        return TRUE;
    }
    
    
    /**
     * Записваме грешка в модела
     */
    static function createError($params) 
    {
        // Ако няма файл, записваме грешката
        $error = new stdClass();
        $error->errorProc = "Възникна грешка при обработка.";
        
        // Текстовата част
        $params['content'] = $error;

        // Обновяваме данните за запис във fileman_Indexes
        $savedId = fileman_Indexes::saveContent($params);
    }
    
    
	/**
     * Записва в лога ако възникне греша при асинхронното обработване на даден файл
     * 
     * @param fileman_Data $dataId - id' то на данните на файла
     * @param string $type - Типа на файла
     */
    static function createErrorLog($dataId, $type)
    {
        fileman_Data::logWarning("Възникна грешка при обработката на файла към '{$type}'", $dataId);
    }
    
    
    /**
     * Изтрива индекса за съответните данни
     * 
     * @param fileman_Data $dataId - id' то на данните
     */
    static function deleteIndexesForData($dataId)
    {
        // Изтриваме всички записи със съответното dataId
        fileman_Indexes::delete(array("#dataId = [#1#]", $dataId));
        
        fileman_Data::resetProcess($dataId);
    }
    
    
    
    /**
     * Регенериране на ключови думи и индексирани записи
     */
    function act_Regenerate()
    {
        requireRole('admin');
        
        $retUrl = getRetUrl();
        
        // Вземаме празна форма
        $form = cls::get('core_Form');
        
        $form->FNC('rType', 'enum(all=Всички, indexes=Индекси, keywords=Ключови думи)', 'caption=На, input=input, mandatory');
        $form->FNC('rLimit', 'int(min=0)', 'caption=Ограничение, input=input');
        
        $form->input('rType, rLimit', TRUE);
        
        $form->setDefault('rLimit', 1000);
        
        if ($form->isSubmitted()) {
            
            core_App::setTimeLimit(300);
            
            $type = $form->rec->rType;
            $limit = $form->rec->rLimit;
            
            $res = '';
            
            // Изтриваме индексите
            if ($type == 'all' || $type == 'indexes') {
                $iQuery = self::getQuery();
                $iQuery->orderBy('createdOn', 'DESC');
                if ($limit) {
                    $iQuery->limit($limit);
                }
                
                while ($iRec = $iQuery->fetch()) {
                    
                    fileman_Data::resetProcess($iRec->dataId);
                    
                    self::delete($iRec->id);
                }
            }
            
            // Премахваме флага, че е обработен на ключовите думи
            if ($type == 'all' || $type == 'keywords') {
                $dQuery = fileman_Data::getQuery();
                $dQuery->where("#processed = 'yes'");
                
                $dQuery->orderBy('lastUse', 'DESC');
                $dQuery->orderBy('createdOn', 'DESC');
                
                if ($limit) {
                    $dQuery->limit($limit);
                }
                
                while ($dRec = $dQuery->fetch()) {
                    $dRec->processed = 'no';
                    fileman_Data::save($dRec, 'processed');
                }
            }
            
            return new Redirect($retUrl, 'Данните са добавени в списъка за регенерация по крон');
        }
        
        $form->title = 'Регенериране на ключови думи и индексирани записи';
        
        // Добавяме бутоните на формата
        $form->toolbar->addSbBtn('Регенериране', 'repair', 'ef_icon = img/16/hammer_screwdriver.png');
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close-red.png');
        
        return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Пуска обработка на текстовата част и пълним ключовите думи
     *
     * @param stdObject $dRec
     * @param datetime $endOn
     * 
     * @return boolean
     */
    function processFile($dRec, $endOn)
    {
        if (dt::now() >= $endOn) return FALSE;
        
        // Намираме всички файлове
        $fQuery = fileman_Files::getQuery();
        $fQuery->where(array("#dataId = '[#1#]'", $dRec->id));
        $fQuery->orderBy('createdOn', 'DESC');
        
        // Имената на файловете да са в ключовите полета
        $fArr = array();
        $fNameStr = '';
        while ($fRec = $fQuery->fetch()) {
            $fArr[$fRec->fileHnd] = $fRec;
            $fNameStr .= $fRec->name . ' ';
        }
        
        // Правим обработка, докато намерим някоя съдържание на файл
        $extArr = array();
        $content = FALSE;
        $break = FALSE;
        foreach ($fArr as $hnd => $fRec) {
            
            if (dt::now() >= $endOn) {
                $break = TRUE;
                break;
            }
            
            if (!$fRec) continue;
            
            $fName = $fRec->name;
            
            if (!$fRec) continue;
        	
            $ext = fileman_Files::getExt($fName);
            
            // Няма нужда за същото разширение да се прави обработка
            if ($extArr[$ext]) continue;
            $extArr[$ext] = $ext;
            
            // Ако от преди това е извличано текстовата част, използваме нея
            $content = self::getTextForIndex($hnd);
            if ($content === FALSE) {
                
                // Намираме драйвера
                $webdrvArr = self::getDriver($ext);
                if (empty($webdrvArr)) continue;
                $drvInst = FALSE;
                foreach ($webdrvArr as $drv) {
                    if (!$drv) continue;
        			
                    if (!method_exists($drv, 'extractText')) continue;
        			
                    $drvInst = $drv;
        			
                    break;
                }
                
                if ($drvInst) {
                    try {
                        // Извличаме текстовата част от драйвера
                        $drvInst->extractText($fRec);
                    } catch (ErrorException $e) {
                        reportException($e);
                    }
                    
                    $dId = fileman_webdrv_Generic::prepareLockId($fRec);
                    
                    // Заключваме процеса и изчакваме докато се отключи
                    $lockId = fileman_webdrv_Generic::getLockId('text', $dId);
                    while (core_Locks::isLocked($lockId)) {
                        if (dt::now() >= $endOn) {
                            $break = TRUE;
                            break;
                        }
                        usleep(500000);
                    }
                }
                
                // Ако не може да се определи текстова част
                // И ако отговора на условията, извличаме текстовата част с OCR
                $content = self::getTextForIndex($hnd);
                $minSize = self::$ocrIndexArr[$ext];
                if (($content === FALSE || !trim($content)) && isset($minSize) && ($dRec->fileLen > $minSize) && ($dRec->fileLen < self::$ocrMax)) {
                    
                    $filemanOcr = fileman_Setup::get('OCR');
                    
                    if (!$filemanOcr || !cls::load($filemanOcr, TRUE)) continue;
                    
                    $intf = cls::getInterface('fileman_OCRIntf', $filemanOcr);
                    
                    if (!$intf) continue;
                    if (!$intf->canExtract($fRec)) continue;
                    if (!$intf->haveTextForOcr($fRec)) continue;
                    
                    try {
                        $intf->getTextByOcr($fRec);
                    } catch (ErrorException $e) {
                        reportException($e);
                    }
                    
                    // Изчакваме докато завърши обработката
                    $lockId = fileman_webdrv_Generic::getLockId('textOcr', $dId);
                    while (core_Locks::isLocked($lockId)) {
                        if (dt::now() >= $endOn) {
                            $break = TRUE;
                            break;
                        }
                        usleep(500000);
                    }
                    
                    $content = self::getTextForIndex($hnd);
                    
                    fileman_Data::logDebug('OCR обработка на данни', $dRec->id);
                }
            }
            
            // Ако открием текстова част, спираме процеса
            if ($content !== FALSE) break;
            
            if ($break) break;
        }
        
        if ($break) return FALSE;
        
        if ($content === FALSE) {
            $content = '';
        }
        
        $content = $fNameStr . $content;
        
        $dRec->searchKeywords = plg_Search::normalizeText($content);
        
        fileman_Data::logDebug('Добавени ключови полета с дължина ' . strlen($dRec->searchKeywords) . ' символа', $dRec->id);
        
        fileman_Data::save($dRec, 'searchKeywords');
        
        return TRUE;
    }
    
    
    /**
     * 
     * @param string $fh
     * 
     * @return FALSE|string
     */
    protected static function getTextForIndex($fh)
    {
        $text = fileman_Indexes::getInfoContentByFh($fh, 'text');
        $textOcr = fileman_Indexes::getInfoContentByFh($fh, 'textOcr');
        
        $content = FALSE;
        
        if ($text !== FALSE && is_string($text)) {
            $content = $text;
        }
        
        if ($textOcr !== FALSE && is_string($textOcr)) {
            $content = $textOcr;
        }
        
        return $content;
    }
 }
 