<?php


/**
 * Какъв е шаблона за манипулатора на файла?
 */
defIfNot('FILEMAN_HANDLER_PTR', '$*****');


/**
 * Каква да е дължината на манипулатора на файла?
 */
defIfNot('FILEMAN_HANDLER_LEN', strlen(FILEMAN_HANDLER_PTR));


/**
 * Клас 'fileman_Files' -
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
class fileman_Files extends core_Master 
{
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'fileman_FileDetails';
    
    
    /**
     * 
     */
    var $canEdit = 'no_one';
    
    /**
     * Всички потребители могат да разглеждат файлове
     */
    var $canSingle = 'user';
    
    /**
     * 
     */
    var $canDelete = 'no_one';
    
    
    /**
     * 
     */
     var $singleLayoutFile = 'fileman/tpl/SingleLayoutFile.shtml';
    
     
    /**
     * Заглавие на модула
     */
    var $title = 'Файлове';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Файлов манипулатор - уникален 8 символно/цифров низ, започващ с буква.
        // Генериран случайно, поради което е труден за налучкване
        $this->FLD("fileHnd", "varchar(" . strlen(FILEMAN_HANDLER_PTR) . ")",
            array('notNull' => TRUE, 'caption' => 'Манипулатор'));
        
        // Име на файла
        $this->FLD("name", "varchar(255)",
            array('notNull' => TRUE, 'caption' => 'Файл'));
        
        // Данни (Съдържание) на файла
        $this->FLD("dataId", "key(mvc=fileman_Data)",
            array('caption' => 'Данни Id'));
        
        // Клас - притежател на файла
        $this->FLD("bucketId", "key(mvc=fileman_Buckets, select=name)",
            array('caption' => 'Кофа'));
        
        // Състояние на файла
        $this->FLD("state", "enum(draft=Чернова,active=Активен,rejected=Оттеглен)",
            array('caption' => 'Състояние'));
        
        // Плъгини за контрол на записа и модифицирането
        $this->load('plg_Created,plg_Modified,Data=fileman_Data,Buckets=fileman_Buckets,' .
            'Download=fileman_Download,Versions=fileman_Versions,fileman_Wrapper');
        
        // 
        $this->FLD('extractedOn', 'datetime(format=smartTime)', 'caption=Екстрактнато->На,input=none');
        
        // Индекси
        $this->setDbUnique('fileHnd');
        $this->setDbUnique('name,bucketId', 'uniqName');
        $this->setDbIndex('dataId,bucketId', 'indexDataId');
    }
    
    
    /**
     * Преди да запишем, генерираме случаен манипулатор
     */
    static function on_BeforeSave(&$mvc, &$id, &$rec)
    {
        // Ако липсва, създаваме нов уникален номер-държател
        if(!$rec->fileHnd) {
            do {
                
                if(16 < $i++) error('Unable to generate random file handler', $rec);
                
                $rec->fileHnd = str::getRand(FILEMAN_HANDLER_PTR);
            } while($mvc->fetch("#fileHnd = '{$rec->fileHnd}'"));
        } elseif(!$rec->id) {
            
            $existingRec = $mvc->fetch("#fileHnd = '{$rec->fileHnd}'");
            
            $rec->id = $existingRec->id;
        }
    }
    
    
    /**
     * Сингъла на файловете
     */
    function act_Single()
    {
        // Манипулатора на файла
        $fh = Request::get('id');
        
        // Очакваме да има подаден манипулатор на файла
        expect($fh, 'Липсва манупулатора на файла');
        
        // Ескейпваме манипулатора
        $fh = $this->db->escape($fh);

        // Записа за съответния файл
        $fRec = fileman_Files::fetchByFh($fh);
        
        // Очакваме да има такъв запис
        expect($fRec, 'Няма такъв запис.');
        
        // Задаваме id' то на файла да е самото id, а не манупулатора на файла
        Request::push(array('id' => $fRec->id));
        
        // Заглавието на таба
        $this->title = "|*" . static::getVerbal($fRec, 'name');
        
        return parent::act_Single();
    }
    
    
    /**
     * Задава файла с посоченото име в посочената кофа
     */
    function setFile($path, $bucket, $fname = NULL, $force = FALSE)
    {
        if($fname === NULL) $fname = basename($path);
        
        $Buckets = cls::get('fileman_Buckets');
        
        expect($bucketId = $Buckets->fetchByName($bucket));
        
        $fh = $this->fetchField(array("#name = '[#1#]' AND #bucketId = {$bucketId}",
                $fname,
            ), "fileHnd");
        
        if(!$fh) {
            $fh = $this->addNewFile($path, $bucket, $fname);
        } elseif($force) {
            $this->setContent($fh, $path);
        }
        
        return $fh;
    }
    
    
    /**
     * Добавя нов файл в посочената кофа
     */
    function addNewFile($path, $bucket, $fname = NULL)
    {
        if($fname === NULL) $fname = basename($path);
        
        $Buckets = cls::get('fileman_Buckets');
        
        $bucketId = $Buckets->fetchByName($bucket);
        
        if($dataId = $this->Data->absorbFile($path, FALSE)) {
            
            // Проверяваме името на файла
            $fh = $this->checkFileName($dataId, $bucketId, $fname);
            
            // Ако има съвпадения с друг файл в системата връщаме манипулатора му
            if ($fh) return $fh;
        }        
        
        $fh = $this->createDraftFile($fname, $bucketId);
        
        $this->setContent($fh, $path);
        
        return $fh;
    }
    
    
    /**
     * Добавя нов файл в посочената кофа от стринг
     */
    function addNewFileFromString($string, $bucket, $fname = NULL)
    {
        $me = cls::get('fileman_Files');
        
        if($fname === NULL) $fname = basename($path);
        
        $Buckets = cls::get('fileman_Buckets');
        
        $bucketId = $Buckets->fetchByName($bucket);
        
        if($dataId = $this->Data->absorbString($string, FALSE)) {

            // Проверяваме името на файла
            $fh = $this->checkFileName($dataId, $bucketId, $fname);
            
            // Ако има съвпадения с друг файл в системата връщаме манипулатора му
            if ($fh) return $fh;
        }        

        $fh = $me->createDraftFile($fname, $bucketId);
        
        $me->setContentFromString($fh, $string);
        
        return $fh;
    }
    
    
    /**
     * Създаваме нов файл в посочената кофа
     */
    function createDraftFile($fname, $bucketId)
    {
        expect($bucketId, 'Очаква се валидна кофа');
        
        $rec = new stdClass();
        $rec->name = $this->getPossibleName($fname, $bucketId);
        $rec->bucketId = $bucketId;
        $rec->state = 'draft';
        
        $this->save($rec);
        
        return $rec->fileHnd;
    }


    /**
     * Променя името на съществуващ файл
     * Връща новото име, което може да е различно от желаното ново име
     */
    static function rename($id, $newName) 
    {
        expect($rec = static::fetch($id));

        if($rec->name != $newName) { 
            $rec->name = static::getPossibleName($newName, $rec->bucketId); 
            static::save($rec);
        }

        return $rec->name;
    }
    
    
    /**
     * Връща първото възможно има, подобно на зададеното, така че в този
     * $bucketId да няма повторение на имената
     */
    static function getPossibleName($fname, $bucketId)
    {
        // Конвертираме името към такова само с латински букви, цифри и знаците '-' и '_'
        $fname = STR::utf2ascii($fname);
        $fname = preg_replace('/[^a-zA-Z0-9\-_\.]+/', '_', $fname);
        
        // Циклим докато генерираме име, което не се среща до сега
        $fn = $fname;
        
        if(($dotPos = strrpos($fname, '.')) !== FALSE) {
            $firstName = substr($fname, 0, $dotPos);
            $ext = substr($fname, $dotPos);
        } else {
            $firstName = $fname;
            $ext = '';
        }
        
        // Двоично търсене за свободно име на файл
        $i = 1;
        
        while(self::fetchField(array("#name = '[#1#]' AND #bucketId = '{$bucketId}'", $fn), 'id')) {
            $fn = $firstName . '_' . $i . $ext;
            $i = $i * 2;
        }
        
        // Търсим първото незаето положение за $i в интервала $i/2 и $i
        if($i > 4) {
            $min = $i / 4;
            $max = $i / 2;
            
            do {
                $i =  ($max + $min) / 2;
                $fn = $firstName . '_' . $i . $ext;
                
                if(self::fetchField(array("#name = '[#1#]' AND #bucketId = '{$bucketId}'", $fn), 'id')) {
                    $min = $i;
                } else {
                    $max = $i;
                }
            } while ($max - $min > 1);
            
            $i = $max;
            
            $fn = $firstName . '_' . $i . $ext;
        }

        return $fn;
    }
    
    
    /**
     * Ако имаме нови данни, които заменят стари
     * такива указваме, че старите са стара версия
     * на файла и ги разскачаме от файла
     */
    function setData($fileHnd, $newDataId)
    {
        $rec = $this->fetch("#fileHnd = '{$fileHnd}'");
        
        // Ако новите данни са същите, като старите 
        // нямаме смяна
        if($rec->dataId == $newDataId) return $rec->dataId;
        
        // Ако имаме стари данни, изпращаме ги в историята
        if($rec->dataId) {
            $verRec->fileHnd = $fileHnd;
            $verRec->dataId = $rec->dataId;
            $verRec->from = $rec->modifiedOn;
            $verRec->to = dt::verbal2mysql();
            $this->Versions->save($verRec);
            
            // Намаляваме с 1 броя на линковете към старите данни
            $this->Data->decreaseLinks($rec->dataId);
        }
        
        // Записваме новите данни
        $rec->dataId = $newDataId;
        $rec->state = 'active';
        
        $this->save($rec);
        
        // Увеличаваме с 1 броя на линковете към новите данни
        $this->Data->increaseLinks($newDataId);
        
        return $rec->dataId;
    }
    
    
    /**
     * Задава данните на даден файл от съществуващ файл в ОС
     */
    function setContent($fileHnd, $osFile)
    {
        $dataId = $this->Data->absorbFile($osFile);
        
        return $this->setData($fileHnd, $dataId);
    }
    
    
    /**
     * Задава данните на даден файл от стринг
     */
    function setContentFromString($fileHnd, $string)
    {
        $dataId = $this->Data->absorbString($string);
        
        return $this->setData($fileHnd, $dataId);
    }
    
    
    /**
     * Връща данните на един файл като стринг
     */
    static function getContent($hnd)
    {
        //expect($path = fileman_Download::getDownloadUrl($hnd));  
        expect($path = fileman_Files::fetchByFh($hnd, 'path'));
        
        return @file_get_contents($path);
    }
    
    
    /**
     * Копира данните от един файл на друг файл
     */
    function copyContent($sHnd, $dHnd)
    {
        $sRec = $this->fetch("#fileHnd = '{$sHnd}'");
        
        if($sRec->state != 'active') return FALSE;
        
        return $this->setData($fileHnd, $sRec->dataId);
    }
    
    
    /**
     * Връща записа за посочения файл или негово поле, ако е указано.
     * Ако посоченото поле съществува в записа за данните за файла,
     * връщаната стойност е от записа за данните на посочения файл
     */
    static function fetchByFh($fh, $field = NULL)
    {
        $Files = cls::get('fileman_Files');
        
        $rec = $Files->fetch(array("#fileHnd = '[#1#]'", $fh));
        
        if($field === NULL) return $rec;
        
        if(!isset($rec->{$field})) {
            $Data = cls::get('fileman_Data');
            
            $dataFields = $Data->selectFields("");
            
            if($dataFields[$field]) {
                $rec = $Data->fetch($rec->dataId);
            }
        }
        
        return $rec->{$field};
    }
    
    
    /**
     * Какви роли са необходими за качване или сваляне?
     */
    static function on_BeforeGetRequiredRoles($mvc, &$roles, $action, $rec = NULL, $userId = NULL)
    {
        if($action == 'download' && is_object($rec)) {
            $roles = $mvc->Buckets->fetchField($rec->bucketId, 'rolesForDownload');
        } elseif($action == 'add' && is_object($rec)) {
            $roles = $mvc->Buckets->fetchField($rec->bucketId, 'rolesForAdding');
        } else {
            
            return;
        }
        
        return FALSE;
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {   
        try {
		
			$row->name = $mvc->Download->getDownloadLink($rec->fileHnd);
		
        } catch(core_Exception_Expect $e) {
             
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function makeBtnToAddFile($title, $bucketId, $callback, $attr = array())
    {
        $function = $this->getJsFunctionForAddFile($bucketId, $callback);
        
        return ht::createFnBtn($title, $function, NULL, $attr);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function makeLinkToAddFile($title, $bucketId, $callback, $attr = array())
    {
        $attr['onclick'] = $this->getJsFunctionForAddFile($bucketId, $callback);
        $attr['href'] = $this->getUrLForAddFile($bucketId, $callback);
        $attr['target'] = 'addFileDialog';
        
        return ht::createElement('a', $attr, $title);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function getUrLForAddFile($bucketId, $callback)
    {
        Request::setProtected('bucketId,callback');
        $url = array('fileman_Upload', 'dialog', 'bucketId' => $bucketId, 'callback' => $callback);
        
        return toUrl($url);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getJsFunctionForAddFile($bucketId, $callback)
    {
        $url = $this->getUrLForAddFile($bucketId, $callback);
        
        $windowName = 'addFileDialog';
        
        if(Mode::is('screenMode', 'narrow')) {
            $args = 'resizable=yes,scrollbars=yes,status=no,location=no,menubar=no,location=no';
        } else {
            $args = 'width=400,height=320,resizable=yes,scrollbars=yes,status=no,location=no,menubar=no,location=no';
        }
        
        return "openWindow('{$url}', '{$windowName}', '{$args}'); return false;";
    }
    
    
    /**
     * Превръща масив с fileHandler' и в масив с id' тата на файловете
     * 
     * @param array $fh - Масив с манупулатори на файловете
     * 
     * @return array $newArr - Масив с id' тата на съответните файлове
     */
    static function getIdFromFh($fh)
    {
        //Преобразуваме към масив
        $fhArr = (array)$fh;
        
        //Създаваме променлива за id' тата
        $newArr = array();
        
        foreach ($fhArr as $val) {
            
            //Ако няма стойност, прескачаме
            if (!$val) continue;
            
            //Ако стойността не е число
            if (!is_numeric($val)) {
                
                //Вземема id'то на файла
                try {
                    $id = static::fetchByFh($val, 'id');
                } catch (Exception $e) {
                    //Ако няма такъв fh, тогава прескачаме
                    continue;
                }   
            } else {
                
                //Присвояваме променливата, като id
                $id = $val;
            }
            
            //Записваме в масива
            $newArr[$id] = $id;
        }
        
        return $newArr;
    }
    
    
    /**
     * Изпълнява се преди подготовката на single изглед
     */
    function on_BeforeRenderSingle($mvc, $tpl, &$data)
    {
        $row = &$data->row;
        $rec = $data->rec;

        expect($rec->dataId, 'Няма данни за файла');
        
        //Разширението на файла
        $ext = fileman_Files::getExt($rec->name);
        
        //Иконата на файла, в зависимост от разширението на файла
        $icon = "fileman/icons/{$ext}.png";
        
        //Ако не можем да намерим икона за съответното разширение, използваме иконата по подразбиране
        if (!is_file(getFullPath($icon))) {
            $icon = "fileman/icons/default.png";
        }
        
        //Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
        $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');

        // Вербалното име на файла
        $row->fileName = "<span class='linkWithIcon' style='margin-left:-15px; background-image:url(" . sbf($icon, '"', $isAbsolute) . ");'>" . $mvc->getVerbal($rec,'name') . "</span>";
        
        // Иконата за редактиране     
        $editImg = "<img src=" . sbf('img/16/edit-icon.png') . ">";
            
        // URL' то където ще препрати линка
        $editUrl = array(
            $mvc,
            'editFile',
            'id' => $rec->fileHnd,
            'ret_url' => TRUE
        );
            
        // Създаваме линка
        $editLink = ht::createLink($editImg, $editUrl);
        
        // Добавяме линка след името на файла
        $row->fileName .= "<span style='margin-left:3px;'>{$editLink}</span>";

        // Масив с линка към папката и документа на първата достъпна нишка, където се използва файла
        $pathArr = static::getFirstContainerLinks($rec);
        
        // Ако има такъв документ
        if (count($pathArr)) {
            
            // Пътя до файла и документа
            $path = ' « ' . $pathArr['firstContainer']['content'] . ' « ' . $pathArr['folder']['content'];
        
            // TODO името на самия документ, където се среща но става много дълго
            //$pathArr['container']
            
            // Пред името на файла добаваме папката и документа, къде е използван
            $row->fileName .= $path;    
        }

        // Версиите на файла
//        $row->versions = static::getFileVersionsString($rec->id);
    }
    
    
    /**
     * Екшън за редактиране на файл
     */
    function act_EditFile()
    {
        // Проверяваме дали екшъна е субмитнат
        if (Request::get('Cmd')) {
            
            // id' то на записа
            $id = Request::get('id', 'int');
            
            // Очакваме да има id
            expect($id);
            
            // Вземаме записите за файла
            $fRec = fileman_Files::fetch($id);
        } else {
            
            // Манипулатора на файла
            $fh = Request::get('id');
    
            // Очакваме да има подаден манипулатор на файла
            expect($fh, 'Липсва манупулатора на файла');
            
            // Ескейпваме манипулатора
            $fh = $this->db->escape($fh);
    
            // Записа за съответния файл
            $fRec = fileman_Files::fetchByFh($fh);
            
            // Задаваме id' то да сочи id' то на записа
            Request::push(array('id' => $fRec->id));
        }
        
        // Очакваме да има такъв запис
        expect($fRec, 'Няма такъв запис.');
        
        // Проверяваме за права
        $this->requireRightFor('single', $fRec);
        
        //URL' то където ще се редиректва при отказ
        $retUrl = getRetUrl();
        $retUrl = ($retUrl) ? ($retUrl) : (array('fileman_Files', 'single', $fRec->fileHnd));
        
        // Вземаме формата към този модел
        $form = $this->getForm();
        
        // Въвеждаме id-то (и евентуално други silent параметри, ако има)
        $form->input(NULL, 'silent');
        
        $form->input('name');
        
        // Размера да е максимален
        $form->setField('name', 'width=100%');
        
        // Ако формата е изпратена без грешки
        if($form->isSubmitted()) {

            // Предишното име на файла
            $oldFileName = $fRec->name;
            
            // Вземаме новото име на файла
            $newFileName = $form->rec->name;
            
            // Ако сме редактирали името
            if ($newFileName != $oldFileName) {
                
                // Изтриваме файла от sbf и от модела
                fileman_Download::deleteFileFromSbf($fRec->id);
                
                // Вземамем новото възможно име
                $newFileName = $this->getPossibleName($newFileName, $fRec->bucketId);
                
                // Записа, който ще запишем
                $nRec = new stdClass();
                $nRec->id = $fRec->id;
                $nRec->name = $newFileName;
                $nRec->fileHnd = $fRec->fileHnd;

                // Вземаме разширението на новия файл
                $newExt = fileman_Files::getExt($newFileName);
                
                // Вземаме разширението на стария файл
                $oldExt = fileman_Files::getExt($oldFileName);
                
                // Ако няма проблем при записването
                if (static::save($nRec) && ($newExt != $oldExt)) {
                    
                    // Изтриваме всички предишни индекси за файла
                    fileman_Indexes::deleteIndexesForData($fRec->dataId);
                    
                    // Ако има разширение
                    if ($newExt) {
                        
                        // Вземаме драйверите
                        $drivers = fileman_Indexes::getDriver($newExt);    
                        
                        // Обикаляме всички открити драйвери
                        foreach($drivers as $drv) {
                            
                            // Стартираме процеса за извличане на данни
                            $drv->startProcessing($fRec);
                        }
                    }    
                }
            }

            // Редиректваме
            Redirect($retUrl);
        }
        
        // Задаваме по подразбиране да е текущото име на файла
        $form->setDefault('name', $fRec->name);
        
        // Задаваме да се показват само полетата, които ни интересуват
        $form->showFields = 'name';
        
        // Добавяме бутоните на формата
        $form->toolbar->addSbBtn('Запис', 'save', array('class' => 'btn-save'));
        $form->toolbar->addBtn('Отказ', $retUrl, array('class' => 'btn-cancel'));

        // Вербалното име на файла
        $fileName = fileman_Files::getVerbal($fRec, 'name');
        
        // Добавяме титлата на формата
        $form->title = "Редактиране на файл|*:  {$fileName}";
        
        return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * 
     */
    function on_AfterPrepareSingle($mvc, &$tpl, $data)
    {
        // Манипулатора на файла
        $fh = $data->rec->fileHnd;
        
        // Подготвяме данните
        fileman_Indexes::prepare($data, $fh);
        
        // Задаваме екшъна
        if (!$data->action) $data->action = 'single';
    }
    
    
    /**
     * 
     */
    function on_AfterRenderSingle($mvc, &$tpl, &$data)
    {
        // Манипулатора на файла
        $fh = $data->rec->fileHnd;
        
        // Текущия таб
        $data->currentTab = Request::get('currentTab');
        
        // Рендираме табовете
        $fileInfo = fileman_Indexes::render($data);
        
        // Добавяме табовете в шаблона
        $tpl->append($fileInfo, 'fileDetail');
    }
    
    
    /**
     * Връща разширението на файла, от името му
     */
    static function getExt($name)
    {
        if(($dotPos = mb_strrpos($name, '.')) !== FALSE) {
            $ext =  mb_strtolower(mb_substr($name, $dotPos + 1));
            $pattern = "/^[a-zA-Z0-9]{1,10}$/i";
            if(!preg_match($pattern, $ext)) {
                $ext = '';
            }
        } else {
            $ext = '';
        }
        
        return $ext;
    }

    
    /**
     * Връща типа на файла
     * 
     * @param string $fileName - Името на файла
     * 
     * @return string - mime типа на файла
     */
    static function getType($fileName)
    {
        if (($dotPos = mb_strrpos($fileName, '.')) !== FALSE) {
            
            // Файл за mime типове
            include(dirname(__FILE__) . '/data/mimes.inc.php');
            
            // Разширение на файла
            $ext = mb_substr($fileName, $dotPos + 1);
        
            return $mimetypes["{$ext}"];
        }
    }
    
    
    /**
     * Връща стринг с всички версии на файла, който търсим
     */
    static function getFileVersionsString($id)
    {
        // Масив с всички версии на файла
        $fileVersionsArr = fileman_FileDetails::getFileVersionsArr($id);
        
        foreach ($fileVersionsArr as $fileHnd => $fileInfo) {
            
            // Линк към single' а на файла
            $link = ht::createLink($fileInfo['fileName'], array('fileman_Files', 'single', $fileHnd), FALSE, array('title' => $fileInfo['versionInfo']));
            
            // Всеки линк за файла да е на нов ред
            $text .= ($text) ? '<br />' . $link : $link;
        }

        return $text;
    }
    
    
    /**
     * Връща името на файла без разширението му
     * 
     * @param mixed $fh - Манипулатор на файла или пътя до файла
     * 
     * @retun string $name - Името на файла, без разширението
     */
    static function getFileNameWithoutExt($fh)
    {
        // Ако е подаден път до файла
        if (strstr($fh, '/')) {
            
            // Вземаме името на файла
            $fname = basename($fh);
        } else {
            
            // Ако е подаден манипулатор на файл
            // Вземаме името на файла
            $fRec = static::fetchByFh($fh);
            $fname = $fRec->name;
        }
        
        // Ако има разширение
        if(($dotPos = mb_strrpos($fname, '.')) !== FALSE) {
            $name = mb_substr($fname, 0, $dotPos);
        } else {
            $name = $fname;
        }
        
        return $name;
    }
    

	/**
     * 
     */
    function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        // Добавяме бутон за сваляне
        $downloadUrl = toUrl(array('fileman_Download', 'Download', 'fh' => $data->rec->fileHnd, 'forceDownload' => TRUE), FALSE);
        $data->toolbar->addBtn('Сваляне', $downloadUrl, 'id=btn-download,class=btn-download', array('order=8'));
    }
    
    
    /**
     * Проверява дали името на подадения файл не се съдържа в същата кофа със същите данни.
     * Ако същия файл е бил качен връща манипулатора на файла
     * 
     * @param fileman_Data $dataId - id' то на данните на файка
     * @param fileman_Buckets $bucketId - id' то на кофата
     * @param string $inputFileName - Името на файла, който искаме да качим
     * 
     * @return fileman_Files $fileHnd - Манипулатора на файла
     */
    static function checkFileName($dataId, $bucketId, $inputFileName)
    {
        // Вземаме всички файлове, които са в съответната кофа и със същите данни
        $query = static::getQuery();
        $query->where("#bucketId = '{$bucketId}' AND #dataId = '{$dataId}'");
        $query->show('fileHnd, name');
        
        // Масив с името на файла и разширението
        $inputFileNameArr = static::getNameAndExt($inputFileName);
        
        // Обикаляме всички открити съвпадения
        while ($rec = $query->fetch($where)) {

            // Ако имената са еднакви
            if ($rec->name == $inputFileName) return $rec->fileHnd;
            
            // Вземаме името на файла и разширението
            $recFileNameArr = static::getNameAndExt($rec->name);
            
            // Намираме името на файла до последния '_'
            if(($underscorePos = mb_strrpos($recFileNameArr['name'], '_')) !== FALSE) {
                $recFileNameArr['name'] = mb_substr($recFileNameArr['name'], 0, $underscorePos);
            }

            // Ако двата масива са еднакви
            if ($inputFileNameArr == $recFileNameArr) {
                
                // Връщаме манипулатора на файла
                return $rec->fileHnd;
            }
        }
        
        return FALSE;
    }
    
    
    /**
     * Създава масив с името на разширението на подадения файл
     * 
     * @param string $fname - Името на файла
     * 
     * @return array $nameArr - Масив с разширението и името на файла
     * 		   string $nameArr['name'] - Името на файла, без разширението
     * 		   string $nameArr['ext'] - Разширението на файла
     */
    static function getNameAndExt($fname)
    {
        // Ако има точка в името на файла, вземаме мястото на последната
        if(($dotPos = mb_strrpos($fname, '.')) !== FALSE) {
            
            // Името на файла
            $nameArr['name'] = mb_substr($fname, 0, $dotPos);
            
            // Разширението на файла
            $nameArr['ext'] = mb_substr($fname, $dotPos + 1);
        } else {
            
            // Ако няма разширение
            $nameArr['name'] = $fname;
            $nameArr['ext'] = '';
        }
        
        return $nameArr;
    }
    
    
    /**
     * Екшън за вземане на текстовата част на файл с OCR програма
     */
    function act_getTextByOcr()
    {
        // Манипулатора на файла
        $fh = $this->db->escape(Request::get('id'));
        
        // Типа на файла
        $type = Request::get('type');
        
        // Очакваме да има такъв запис
        expect($rec = $this->fetchByFh($fh), 'Няма такъв запис');
        
        // Очакваме да има права за single        
        $this->requireRightFor('single', $rec);
        
        // URL' то където ще се редиректне
        $retUrl = getRetUrl();
        
        // Ако няма такова URL
        if (!$retUrl) {
            
            // Създавме го
            $retUrl = toUrl(array('fileman_Files', 'single', $fh, 'currentTab' => 'text', '#' => 'fileDetail'));
        }

        // Проверяваме дали за файла има текстова част или процеса е стартиран
        $params['type'] = 'text';
        $params['dataId'] = $rec->dataId;
        $textProc = fileman_Indexes::isProcessStarted($params, TRUE);
        
        // Ако има текстова част или в момента се извлича
        if ($textProc) {
            
            // Добавяме съобщение в статуса
            core_Statuses::add('Има извлечена текстова част');
            
            return redirect($retUrl); 
        }
        
        // Проверяваме дали за файла има извлечена текстова част или в момента се извлича с OCR
        $paramsOcr['type'] = 'textOcr';
        $paramsOcr['dataId'] = $rec->dataId;
        $textOcrProc = fileman_Indexes::isProcessStarted($paramsOcr);
        
        // Ако има текстова OCR част или в момента се извлича
        if ($textOcrProc) {
            
            // Добавяме съобщение в статуса
            core_Statuses::add('Разпознаването на текст за текущия файл е бил стартиран');
            
            return redirect($retUrl);
        }

        // В зависимост от подадения тип, стартираме съответния процес
        switch ($type) {
            
            case 'abbyy':
                $this->getTextByAbbyyOcr($fh);
            break;
            
            default:
                
                // Очакваме да има такъв тип
                expect(FALSE, "Типа не е верен {$type}");
            break;
        }
        
        return redirect($retUrl);
    }    
    
    
    /**
     * Преобразува линка към single' на файла richtext линк
     * 
     * @param string $fileHnd - Манипулатора на файла
     * 
     * @return string $res - Линка в richText формат
     */
    function getVerbalLinkFromClass($fileHnd)
    {
        // Записите за файла
        $fRec = static::fetchByFh($fileHnd);
        
        // Ако няма такъв запис или нямаме права за single
        if ((!$fRec) || (!static::haveRightFor('single', $fRec))) return FALSE;
        
        // Името на файла
        $name = static::getVerbal($fRec, 'name');
        
        // Преобразуваме линка към richText линк
        $res = "[file={$fileHnd}]{$name}[/file]";
        
        return $res;
    }
}
