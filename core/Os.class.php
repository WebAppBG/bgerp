<?php



/**
 * Клас 'core_Os' - Стартиране на процеси на OS
 *
 * PHP versions 4 and 5
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
class core_Os
{
    
    
    /**
     * Връща TRUE ако операционната система е Windows
     */
    static function isWindows()
    {
        return stristr(PHP_OS, 'WIN');
    }
    
    
    /**
     * Връща съобщенията за грешки, генерирани от съответния процес
     */
    function getErrors($pid)
    {
        $uniqId = substr($pid, strpos($pid, '_') + 1);
        $fName = $this->getErrorFile($uniqId);
        
        if (file_exists($fName)) {
            if (@filesize($fName)) {
                $errorMsg = file_get_contents($fName);
                
                // Премахва изходящия файл. Дали така трябва?
                unlink($this->getTempFile($uniqId));
            }
            unlink($fName);
            
            return $errorMsg;
        }
    }
    
    
    /**
     * Връща уникален глобален идентификатор
     */
    static function getUniqId($base = 'id')
    {
        static $i, $uniqId;
        
        if (!$uniqId) {
            $uniqId = uniqid($base);
        }
        $i++;
        
        return $uniqId . "_" . $i;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getTempFile($uniqId)
    {
        return EF_TEMP_PATH . "\\" . $uniqId . ".out";
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getErrorFile($uniqId)
    {
        return EF_TEMP_PATH . "\\" . $uniqId . ".err";
    }
    
    
    /**
     * Изтрива директория
     * Връща false при неуспех
     */
    static function deleteDir($dir)
    {
		foreach(glob($dir . '/*') as $file) {
		        if(is_dir($file))
		            self::deleteDir($file);
		        else
		            @unlink($file);
		}
		
	    return @rmdir($dir);
    }


    /**
     * Изтрива файловете в посочената директория и нейните под-директории,
     * които не са прочитани в последните скудни указани от $maxAge
     * 
     * @param string $dir
     * @param integer $maxAge
     * 
     * @return integer - Броя на изтритите файлове
     */
    static function deleteOldFiels($dir, $maxAge = 86400)
    {
        $allFiles = self::listFiles($dir);
        
        $delCnt = 0;
        if(is_array($allFiles['files'])) {
            foreach($allFiles['files'] as $fPath) {
                if(time() - fileatime($fPath) > $maxAge) {
                    
                    if (@unlink($fPath)) {
                        $delCnt++;
                    }
                }
            }
        }
        
        return $delCnt;
    }
    
    
    /**
     * Изтрива всички файлове от EF_TEMP_PATH по крон
     */
    static function cron_clearOldFiles()
    {
        // Конфигурацията на пакета core
        $conf = core_Packs::getConfig('core');
        
        // Резултат във вербален вид
        $resText = '';
        
        // Брояч за изтриванията
        $delCnt = 0;

        // Изтриваме всички, файлове, кото са по стари от дадено време в директорията за временни файлове
        if (defined('EF_TEMP_PATH')) { 
            $delCnt = self::deleteOldFiels(EF_TEMP_PATH,  $conf->CORE_TEMP_PATH_MAX_AGE);  
            if($delCnt > 0) {
                $resText .= ($resText ? "\n" : '') . ($delCnt>1 ? "Бяха изтрити" : "Беше изтрит") . " {$delCnt} " . ($delCnt>1 ? "файла" : "файл") . ' от ' . EF_TEMP_PATH;
            }
        }
        
        // Изтриваме всички стари файлове в поддиректории на sbf които не започват със символа '_'
        if (defined('EF_SBF_PATH')) {
            if ($handle = opendir(EF_SBF_PATH)) {
                while (FALSE !== ($entry = readdir($handle))) {
                    if ($entry != "." && $entry != ".." && false === strpos($entry, '_') && is_dir(EF_SBF_PATH . "/{$entry}")) {
                        $delCnt = self::deleteOldFiels(EF_SBF_PATH . "/{$entry}", $conf->CORE_TEMP_PATH_MAX_AGE);
                    }
                }
                closedir($handle);
            }
            if($delCnt > 0) {
                $resText .= ($resText ? "\n" : '') . ($delCnt>1 ? "Бяха изтрити" : "Беше изтрит") . " {$delCnt} " . ($delCnt>1 ? "файла" : "файл") . ' от ' . EF_SBF_PATH;
            }
        }

        return $resText;
    }
    

    /**
     * Връща масив със всички поддиректории и файлове от посочената начална директория
     *
     * array(
     * 'files' => [],
     * 'dirs'  => [],
     * )
     * @param string $root
     * @result array
     */
    static function listFiles($root)
    {
        $files = array('files'=>array(), 'dirs'=>array());
        $directories = array();
        $last_letter = $root[strlen($root)-1];
        $root = ($last_letter == '\\' || $last_letter == '/') ? $root : $root . DIRECTORY_SEPARATOR;        //?
        $directories[] = $root;
        
        while (sizeof($directories)) {
            
            $dir = array_pop($directories);
            
            if ($handle = opendir($dir)) {
                while (FALSE !== ($file = readdir($handle))) {
                    if ($file == '.' || $file == '..') {
                        continue;
                    }
                    $file = $dir . $file;
                    
                    if (is_dir($file)) {  
                        $directory_path = $file . DIRECTORY_SEPARATOR;
                        array_push($directories, $directory_path);
                        $files['dirs'][] = $directory_path;
                    } elseif (is_file($file)) {
                        $files['files'][] = $file; 
                    }
                }
                closedir($handle);
            }
        }
 
        return $files;
    }

    
    /**
     * Връща времето на последната промяна на файл в директорията
     * 
     * @param string $dir - Директорията
     * 
     * @return integer - Времето на последната промяна
     */
    static function getLastModified($dir)
    {
        // Всички файлове
        $files = scandir($dir);
        
        // Запазваме в променлива, за да не вземаме 2 пъти за една и съща директория
        static $lastModificationDir = array();
        
        // Ако вече сме гледали в директория
        if (!$lastModificationDir[$dir]) {
            
            // Обхождаме файловете
            foreach ($files as $file) {
                
                // Прескачаме ги
                if ($file == '.' || $file == '..') continue;
                
                // Вземаме времето на промяна на последния файл
                $time = filemtime($dir . DIRECTORY_SEPARATOR . $file);
                
                // Ако времето е по - голямо от записаното в директорията
                if ($time > $lastModificationDir[$dir]) {
                    
                    // Записваме времето на последната промяна
                    $lastModificationDir[$dir] = $time;
                }
            }
        }
        
        return $lastModificationDir[$dir];
    }
    
    
    /**
     * Функция, която връща резултата от изпълнението на посленидния preg
     * В preg_ фунцкиите, ако възникне грешка връщат NULL
     */
    static function pregLastError()
    {
        $pregLastError = preg_last_error();
        
        if ($pregLastError == PREG_NO_ERROR) {
            $res = 'There is no error.';
        } else if ($pregLastError == PREG_INTERNAL_ERROR) {
            $res = 'There is an internal error!';
        } else if ($pregLastError == PREG_BACKTRACK_LIMIT_ERROR) {
            $res = 'Backtrack limit was exhausted!';
        } else if ($pregLastError == PREG_RECURSION_LIMIT_ERROR) {
            $res = 'Recursion limit was exhausted!';
        } else if ($pregLastError == PREG_BAD_UTF8_ERROR) {
            $res = 'Bad UTF8 error!';
        } else if ($pregLastError == PREG_BAD_UTF8_ERROR) {
            $res = 'Bad UTF8 offset error!';
        } else {
            $res = 'Unrecognized error!';
        }
        
        return $res;
    }


    /**
     * Връща броя на стартираните процеси на Apache
     */
    function countApacheProc()
    {   
        $processes = 0;

        if($this->isWindows()) {
            $output = shell_exec("tasklist");
            $lines = explode("\n", $output);
            foreach($lines as $l) { 
                if(strpos($l, 'httpd.exe') !== FALSE) {
                    $processes++; 
                }
            }
        } else {
            exec('ps aux | grep apache', $output);
            $processes = count($output);
        }

        return $processes;
    }


    /**
     * Съдава пътищата посочени във входния аргумент
     *
     * return string
     */
    public static function createDirectories($directories, $mode = 0777, $recursive = TRUE)
    {
        // Създава, ако е необходимо зададените папки
        foreach(arr::make($directories) as $path => $caption) {
            
            if(is_numeric($path)) {
                $path = $caption;
                $caption = '';
            }

            if(!is_dir($path)) {
                if(!mkdir($path, $mode, $recursive)) {
                    $res .= "<li class='debug-error'>Не може да се създаде директорията <b>{$path}</b> {$caption}</li>";
                } else {
                    $res .= "<li class='debug-new'>Създадена е директорията <b>{$path}</b> {$caption}</li>";
                }
            } else {
                $res .= "<li class='debug-info'>Съществуваща директория <b>{$path}</b> {$caption}</li>";
            }
            
            if(!is_writable($path)) {
                $res .= "<li class='debug-error'>Не може да се записва в директорията <b>{$path}</b> {$caption}</li>";
            }
        } 
        
        return $res;
    }

}