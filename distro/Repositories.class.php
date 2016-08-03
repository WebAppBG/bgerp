<?php


/**
 * Модел, който съдържа пътищата до хранилищата
 *
 * @category  vendors
 * @package   distro
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class distro_Repositories extends core_Master
{
    
    
    /**
     * Папка за системните файлове
     */
    protected static $systemPath = '.system';
    
    
    /**
     * Файл в който ще записва inotifywait
     */
    protected static $systemFile = '.system';
    
    
    /**
     * Файл, който ще се пуска по крон
     */
    protected static $autorunFile = 'autorun.sh';
    
    
    /**
     * Директория за грешките
     */
    protected static $errPath = 'err';
    
    
    /**
     * Заглавие на таблицата
     */
    public $title = "Път до хранилище";
    
    
    /**
     * 
     */
    public $singleTitle = "Хранилище";
    
    
    /**
     * Път към картинка 16x16
     */
    public $singleIcon = 'img/16/repository.png';
    
    
    /**
     * 
     */
    public $canSingle = 'admin';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'admin';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'admin';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'admin';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    public $canReject = 'admin';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'admin';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'distro_Wrapper, plg_RowTools2, plg_Created, plg_State, plg_Rejected';
    
    
    /**
     * 
     */
    public $listFields = 'id, name, hostId, path, info, createdOn, createdBy';
    
    
    /**
     * Полетата, които ще се показват в единичния изглед
     */
    public $singleFields = 'id, hostId, name, path, info, createdOn, createdBy';


    /**
     * Кои полета да се извличат при изтриване
     */
    var $fetchFieldsBeforeDelete = 'id, hostId, path';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('hostId', 'key(mvc=ssh_Hosts, select=name)', 'caption=Хост,input,mandatory');
        $this->FLD('name', 'varchar', 'caption=Име, mandatory');
        $this->FLD('path', 'varchar', 'caption=Път на хранилището, mandatory');
        $this->FLD('info', 'richtext', 'caption=Информация');
        $this->FLD('lineHash', 'varchar(32)', 'caption=Хеш, input=none');
        $this->FLD('url', 'url', 'caption=Линк за сваляне');
        
        $this->setDbUnique('name');
        $this->setDbUnique('hostId, path');
    }
    
    
    /**
     * Парсира и връща линиите от системния файл в отдалечената директория
     * 
     * @param integer $repoId
     * @param number $linesCnt
     * @param boolean $removeDuplicated
     * 
     * @return array
     */
    public static function parseLines($repoId, $linesCnt = 1000, $removeDuplicated = TRUE)
    {
        $linesArr = distro_Repositories::getLines($repoId, $linesCnt, $removeDuplicated);
        
        $resArr = array();
        
        foreach ($linesArr as $line) {
            $resArr[] = distro_Repositories::parseLine($repoId, $line);
        }
        
        return $resArr;
    }
    
    
    /**
     * Връща масив с всички хранилища
     * 
     * @return array $reposArr - Масив с id-та на всички хранилища
     */
    public static function getReposArr()
    {
        // Масив с всички хранилища
        static $reposArr = array();
        
        // Ако не е генериран преди
        if (empty($reposArr)) {
            
            // Вземаме всички записи
            $query = static::getQuery();
            $query->where("#state != 'rejected'");
            
            // Обхождаме записите
            while ($rec = $query->fetch()) {
                
                // Добавяме в масива
                $reposArr[$rec->id] = $rec->id;
            }
        }
        
        return $reposArr;
    }
    
    
    /**
     * Създава директория в хранилището
     * 
     * @param integer $repoId
     * @param string|NULL $name
     * 
     * @return FALSE|string
     */
    public static function createDir($repoId, $name)
    {
        $rec = self::fetch((int) $repoId);
        
        $sshObj = self::connectToRepo($rec);
        
        if ($sshObj === FALSE) return FALSE;
        
        $oPath = rtrim($rec->path, '/');
        $oPath .= '/' . $name;
        $path = escapeshellarg($oPath);
        
        $sshObj->exec('mkdir -p ' . $path);
        
        return $oPath;
    }
    
    
    /**
     * Връща md5 стойността на файла
     * 
     * @param integer $repoId
     * @param string $dir
     * @param string $name
     * 
     * @return FALSE|string
     */
    public static function getFileMd5($repoId, $dir, $name)
    {
        $rec = self::fetch((int) $repoId);
        
        $sshObj = self::connectToRepo($rec);
        
        if ($sshObj === FALSE) return FALSE;
        
        $path = rtrim($rec->path, '/');
        $path .= '/' . $dir . '/' . $name;
        $path = escapeshellarg($path);
        
        $sshObj->exec('md5sum ' . $path, $output);
        
        if ($output) {
            list($md5) = explode(' ', $output, 2);
            
            $md5 = trim($md5);
            
            return $md5;
        }
        
        return FALSE;
    }
	
    
    /**
     * Активира състоянието на хранилището
     * 
     * @param integer $id - id на хранилище
     * 
     * @return integer|NULL - id на записа, ако се е активирал
     */
    public static function activateRepo($id)
    {
        // Вземаем записа
        $rec = static::fetch($id);
        
        // Ако не е бил активиран
        if ($rec->state != 'active') {
            
            // Активираме
            $rec->state = 'active';
            
            return static::save($rec);
        }
    }
    
    
    /**
     * Задава стойност за хеша за реда
     * 
     * @param integer $repoId
     */
    public static function setLineHash($repoId, $lineHash)
    {
        $nRec = new stdClass();
        $nRec->id = $repoId;
        $nRec->lineHash = $lineHash;
        
        self::save($nRec, 'lineHash');
    }
    
    
    /**
     * Връща масив с хранилищата и хеша на последния обработен ред
     * 
     * @return array
     */
    public static function getLinesHash()
    {
        $resArr = array();
        
        $query = self::getQuery();
        $query->where("#state != 'rejected'");

        while ($rec = $query->fetch()) {
            $resArr[$rec->id] = $rec->lineHash;
        }
        
        return $resArr;
    }
    
    
    /**
     * Ако в хранилището е зададено URL, генерираме линк към самия файл в него
     * 
     * @param integer $id
     * @param string $subDir
     * @param string $file
     * @return string
     */
    public static function getUrlForFile($id, $subDir, $file)
    {
        $rec = self::fetch((int) $id);
        
        if (!($url = trim($rec->url))) return $file;
        
        $url = rtrim($url, '/');
        
        $url .= '/' . $subDir . '/' . $file;

        $ext = fileman_Files::getExt($file);
        //Иконата на файла, в зависимост от разширението на файла
        $icon = "fileman/icons/{$ext}.png";
        
        //Ако не можем да намерим икона за съответното разширение, използваме иконата по подразбиране
        if (!is_file(getFullPath($icon))) {
            $icon = "fileman/icons/default.png";
        }
        
        $sbfIcon = sbf($icon,"");
        $link = ht::createLink($file, $url, NULL, array('target'=>'_blank'));
        $fileStr = "<span class='linkWithIcon' style='background-image:url($sbfIcon);'>{$link}</span>";
        
        return $fileStr;
    }
    
    
    /**
     * Връща настройките на хост за съответното хранилище
     * 
     * @param integer|stdObjec $id
     * 
     * @return FALSE|array
     */
    public static function getHostParams($id)
    {
        $rec = self::fetchRec($id);
        
        try {
            $hostConfig = ssh_Hosts::fetchConfig($rec->hostId);
        } catch (core_exception_Expect $e) {
            $this->logErr($e->getMessage(), $id);
			
            return FALSE;
        }
        
        return $hostConfig;
    }
    
    
    /**
     * Връща пътя до директрията с грешките файл, където ще се записват данните от inotifywait
     * 
     * @param integer $id
     * 
     * @return string
     */
    public static function getErrPath($id)
    {
        $rec = self::fetch($id);
        $errPath = rtrim($rec->path, '/');
        $errPath .= '/' . self::$systemPath . '/' . self::$errPath . '/';
        
        return $errPath;
    }
    
    /**
     * Парсира подадения ред от файла
     * 
     * @param integer $repoId
     * @param string $line
     * 
     * @return array - [lineHash, rPath, date, name, isDir, act]
     */
    protected static function parseLine($repoId, $line)
    {
        $rec = self::fetch((int) $repoId);
        
        $line = trim($line, '"');
        
        if (!trim($line)) return array();
        
        list($path, $file, $act, $date) = explode('" "', $line);
        
        $path = str_replace($rec->path, '', $path);
        $path = trim($path, '/');
        
        $resArr = array();
        $resArr['lineHash'] = self::getLineHash($line);
        $resArr['rPath'] = $path;
        $resArr['date'] = $date;
        $resArr['name'] = $file;
        
        list($actName, $isDir) = explode(',', $act);
        
        $resArr['isDir'] = ($isDir == 'ISDIR') ? TRUE : FALSE;
        
        if ($actName == 'CREATE' || $actName == 'MOVED_TO') {
            $resArr['act'] = 'create';
        } elseif ($actName == 'DELETE' || $actName == 'MOVED_FROM') {
            $resArr['act'] = 'delete';
        } elseif ($actName == 'MODIFY') {
            $resArr['act'] = 'edit';
        } else {
            $resArr['act'] = 'unknown';
        }
        
        return $resArr;
    }
    
    
    /**
     * Връща линиите от системния файл в отдалечената директория
     * 
     * @param integer $repoId
     * @param number $linesCnt
     * @param boolean $removeDuplicated
     * 
     * @return array
     */
    protected static function getLines($repoId, $linesCnt = 1000, $removeDuplicated = TRUE)
    {
        $rec = self::fetch((int) $repoId);
        
        $sshObj = self::connectToRepo($rec);
        
        if ($sshObj === FALSE) return array();
        
        $linesCnt = escapeshellarg($linesCnt);
        $path = self::getSystemFile($rec->path);
        $path = escapeshellarg($path);
        
        $cmd = "tail -n {$linesCnt} $path";
        
        $sshObj->exec($cmd, $resLines);
        
        $resLines = trim($resLines);
        
        $linesArr = explode("\n", $resLines);
        
        if ($removeDuplicated) {
            $linesArr = array_unique($linesArr);
        }
        
        $linesArr = array_reverse($linesArr);
        
        return $linesArr;
    }
    
    
    /**
     * Прави връзка към сървъра по SSH
     * 
     * @param stdObject|integer $rec
     * 
     * @return FALSE|ssh_Actions
     */
    public static function connectToRepo($rec)
    {
        $rec = self::fetchRec($rec);
        
        $repoConnectArr = array();
        
        if (!isset($repoConnectArr[$rec->id])) {
            try {
                $repoConnectArr[$rec->id] = new ssh_Actions($rec->hostId);
            } catch (core_exception_Expect $e) {
                self::logWarning('Грешка при свързване към хост: ' . $e->getMessage(), $rec->id);
                reportException($e);
                
                $repoConnectArr[$rec->id] = FALSE;
            }
        }
        
        return $repoConnectArr[$rec->id];
    }
    
    
    /**
     * Връща хеша за стринга
     * 
     * @param string $line
     * 
     * @return string
     */
    protected static function getLineHash($line)
    {
        
        return md5($line);
    }
    
    
    /**
     * Връща стринг, който периодично ще спира/стартира inotifywait програмата в хранилището
     * 
     * @param string $path
     * 
     * @return string
     */
    protected static function getAutorunSh($path)
    {
        $tpl = getTplFromFile('/distro/tpl/InotifyAutorun.txt');
        
        $systemPath = self::getSystemFile($path);
        
        $nObj = new stdClass();
        $nObj->regExPath = preg_quote($path, '/');
        $nObj->path = escapeshellarg($path);
        $nObj->sysPath = escapeshellarg($systemPath);
        $nObj->hour = '03';
        $nObj->min = rand(10, 59);
        $nObj->sleep = rand(30, 40);
        $nObj->pipe = '|'; // Това е заради превеждането на шаблона
        
        $tpl->placeObject($nObj);
        
        return $tpl->getContent();
    }
    
    
    /**
     * Връща пътя до системния файл, където ще се записват данните от inotifywait
     * 
     * @param string $path
     * 
     * @return string
     */
    protected static function getSystemFile($path)
    {
        $systemPath = rtrim($path, '/');
        $systemPath .= '/' . self::$systemPath . '/' . self::$systemFile;
        
        return $systemPath;
    }
    
    
    /**
     * Връща стринг, който при стартиране добавя изпълнянието на файла в кронтаба
     * 
     * @param string $path
     * 
     * @return string
     */
    protected function getStringToAddCrontab($path)
    {
        $path = escapeshellarg($path);
        
        $res = 'crontab -l > cron.res' . "\n";
        $res .= 'echo "* * * * * ' . $path . '" >> cron.res' . "\n";
        $res .= 'crontab cron.res' . "\n";
        $res .= 'rm cron.res';
        
        return $res;
    }
    
    
    /**
     * Премахва процеса от кронтаба и го спира
     * 
     * @param stdObject $rec
     */
    protected static function stopProcess($rec)
    {
        $sshObj = self::connectToRepo($rec);
        
        // Премахваме процеса от кронтаба
        $autorunPath = rtrim($rec->path, '/');
        $autorunPath .= '/' . self::$systemPath . '/' . self::$autorunFile;
        $autorunPath = escapeshellarg($autorunPath);
        $sshObj->exec("crontab -l | grep -v " . $autorunPath . " | crontab -");
        
        // Спираме процеса
        $path = preg_quote($rec->path, '/');
        $sshObj->exec('pid=$(ps aux | egrep "(inotifywait)(.*?)(' . $path . ')+$" | awk {\'print $2\'}); if  [ -n "$pid" ];  then kill -9 $pid; fi;');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param distro_Repositories $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        if ($data->form->rec->id) {
            $data->form->setReadOnly('hostId');
            $data->form->setReadOnly('path');
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param distro_Repositories $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $form->rec->path = rtrim($form->rec->path, '/');
            $form->rec->path .= '/';
        }
        
        if ($form->isSubmitted() && !$mvc->isUnique($form->rec, $fields)) {
            $form->setError($fields, "Вече съществува запис със същите данни");
        }
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     * 
     * @param distro_Repositories $mvc
     * @param stdClass $rec
     * @param array $fields
     * @param NULL|string $mode
     */
    public static function on_AfterCreate($mvc, $rec, $fields, $mode)
    {
        $sysDir = $mvc->createDir($rec->id, self::$systemPath . '/');
        $errDir = $mvc->createDir($rec->id, self::$systemPath . '/' . self::$errPath . '/');
        
        if ($sysDir === FALSE) return ;
        
        $sshObj = self::connectToRepo($rec);
        
        // Добавяме скрипта за стартирана на inotifywait
        $autorunSh = $mvc->getAutorunSh($rec->path);
        $autorunSh = escapeshellarg($autorunSh);
        $path = rtrim($sysDir, '/');
        $path .= '/' . self::$autorunFile;
        $ePath = escapeshellarg($path);
        $sshObj->exec("echo {$autorunSh} >> $ePath");
        $sshObj->exec("chmod +x {$ePath}");
        
        // Добавяме стартирането на файла в кронтаба
        $addCrontabStr = $mvc->getStringToAddCrontab($path);
        $sshObj->exec($addCrontabStr);
        
        // Добавяме .htaccess в хранилището
        $htaccesPath = rtrim($rec->path, '/');
        $htaccesPath .= '/.htaccess';
        $htaccesPath = escapeshellarg($htaccesPath);
        $fPath = getFullPath('/distro/tpl/htaccess.txt');
        $content = file_get_contents($fPath);
        $content = escapeshellarg($content);
        
        $sshObj->exec("echo {$content} >> $htaccesPath");
    }
    
    
    /**
     * След оттегляне на документа
     *
     * @param distro_Repositories $mvc
     * @param mixed $res
     * @param int|object $id първичен ключ или запис на $mvc
     */
    public static function on_AfterReject($mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        
        $mvc->stopProcess($rec);
    }
    

    /**
     * След изтриване на запис
     */
    protected static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
    {
        foreach ($query->getDeletedRecs() as $id => $rec) {
            $mvc->stopProcess($rec);
        }
    }
    
    
    /**
     * След възстановяване на документа
     *
     * @param distro_Repositories $mvc
     * @param mixed $res
     * @param int|object $id първичен ключ или запис на $mvc
     */
    public static function on_AfterRestore($mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        
        $sshObj = self::connectToRepo($rec);
        
        // Добавяме процеса в кронтаба
        $path = rtrim($rec->path, '/');
        $path .= '/' . self::$systemPath . '/' . self::$autorunFile;
        $addCrontabStr = $mvc->getStringToAddCrontab($path);
        $sshObj->exec($addCrontabStr);
    }
    
    
	/**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Ако има запис и се опитваме да изтрием
        if ($rec && ($action == 'delete')) {
            
            // Ако състоянието е активно
            if ($rec->state == 'active' || $rec->state == 'rejected') {
            
				// Да не може да се изтрие
                $requiredRoles = 'no_one';
            }
        }
    }
}
