<?php



/**
 * Четене и записване на локални файлове
 *
 *
 * @category  bgerp
 * @package   backup
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Архивиране
 */
class backup_Start extends core_Manager
{
    
    /**
     * Заглавие
     */
    var $title = 'Стартира архивиране';
    
    /**
     * Име на семафора за стартиран процес на бекъп
     */
    private static $lockFileName;
    private static $conf;
    private static $backupFileName;
    private static $binLogFileName;
    private static $metaFileName;
    private static $storage;
    private static $confFileName;
    
    function init($params = array())
    {
        self::$lockFileName = EF_TEMP_PATH . '/backupLock.tmp';
        self::$conf = core_Packs::getConfig('backup');
        $now = date("Y_m_d_H_i");
        self::$backupFileName = self::$conf->BACKUP_PREFIX . "_" . EF_DB_NAME . "_" . $now . ".full.gz";
        self::$binLogFileName = self::$conf->BACKUP_PREFIX . "_" . EF_DB_NAME . "_" . $now . ".binlog.gz";
        self::$metaFileName = self::$conf->BACKUP_PREFIX . "_" . EF_DB_NAME . "_META";
        self::$confFileName = self::$conf->BACKUP_PREFIX . "_" . EF_DB_NAME . "_conf.tar.gz";
        self::$storage = core_Cls::get("backup_" . self::$conf->BACKUP_STORAGE_TYPE);
    }
    
    /**
     * Стартиране на пълното архивиране на MySQL-a
     * 
     * 
     */
    private static function full()
    {
        if (!self::lock()) {
            core_Logs::add("Backup", "", "Full Backup не може да вземе Lock!");
            
            exit(1);
        }
        
        // проверка дали всичко е наред с mysqldump-a
        $cmd = "mysqldump --no-data --no-create-info --no-create-db --skip-set-charset --skip-comments -h"
                 . self::$conf->BACKUP_MYSQL_HOST . " -u"
                 . self::$conf->BACKUP_MYSQL_USER_NAME. " -p"
                 . self::$conf->BACKUP_MYSQL_USER_PASS. " " . EF_DB_NAME ." 2>&1";
        exec($cmd, $output ,  $returnVar);
        if ($returnVar !== 0) {
            core_Logs::add("Backup", "", "FULL Backup mysqldump ERROR!" . $output[0]);
            self::unLock();
            
            exit(1);
        }
        
        // проверка дали gzip е наличен
        exec("gzip --help", $output,  $returnVar);
        if ($returnVar !== 0) {
            core_Logs::add("Backup", "", "gzip NOT found");
            self::unLock();
            
            exit(1);
        }
        
        exec("mysqldump --lock-tables --delete-master-logs -u"
              . self::$conf->BACKUP_MYSQL_USER_NAME . " -p" . self::$conf->BACKUP_MYSQL_USER_PASS . " " . EF_DB_NAME 
              . " | gzip -9 >" . EF_TEMP_PATH . "/" . self::$backupFileName 
            , $output, $returnVar);
        
        if ($returnVar !==0 ) {
            core_Logs::add("Backup", "", "ГРЕШКА full Backup: {$returnVar}");
            self::unLock();
            
            exit(1);
        }
        
        // Сваляме мета файла с описанията за бекъпите
        if (!self::$storage->getFile(self::$metaFileName)) {
            // Ако го няма - създаваме го
            touch(EF_TEMP_PATH . "/" . self::$metaFileName);
            $metaArr = array();
        } else {
            $metaArr = unserialize(file_get_contents(EF_TEMP_PATH . "/" . self::$metaFileName));
        }
        
        if (!is_array($metaArr)) {
            core_Logs::add("Backup", "", "Лоша МЕТА информация!");
            self::unLock();
            
            exit(1);
        }
        
        // Ако има дефинирана парола криптираме файловете с данните
        if (self::$conf->BACKUP_CRYPT == 'yes') {
            self::$backupFileName = self::crypt(self::$backupFileName);
        }
       
        // Добавяме нов запис за пълния бекъп
        $metaArr[][0] = self::$backupFileName;
        file_put_contents(EF_TEMP_PATH . "/" . self::$metaFileName, serialize($metaArr));
        
        // Качваме бекъп-а
        self::$storage->putFile(self::$backupFileName);
          
        // Качваме и мета файла
        self::$storage->putFile(self::$metaFileName);

        // Изтриваме бекъп-а от temp-a и metata
        unlink(EF_TEMP_PATH . "/" . self::$backupFileName);
        unlink(EF_TEMP_PATH . "/" . self::$metaFileName);
        self::saveConf();
        
        core_Logs::add("Backup", "", "FULL Backup OK!");
        self::unLock();
        
        return "FULL Backup OK!"; 
    }

    /**
     * Съхраняване на бинарния лог на MySQL-a
     * 
     * 
     */
    private static function binLog()
    {
        if (!self::lock()) {
            core_Logs::add("Backup","", "Warning: BinLog не може да вземе Lock.");
            
            exit(1);
        }
        
        // 1. сваля се метафайла
        if (!self::$storage->getFile(self::$metaFileName)) {
            // Ако го няма - пропускаме - не е минал пълен бекъп
            core_Logs::add("Backup", "", "ГРЕШКА при сваляне на МЕТА-а!");
            self::unLock();
            
            exit(1);
        } else {
            $metaArr = unserialize(file_get_contents(EF_TEMP_PATH . "/" . self::$metaFileName));
        }
        
        if (!is_array($metaArr)) {
            core_Logs::add("Backup", "", "Лоша МЕТА информация!");
            self::unLock();
            
            exit(1);
        }
        
        // Взима бинарния лог
        $db = cls::get("core_Db", array('dbUser'=>self::$conf->BACKUP_MYSQL_USER_NAME,
                'dbHost'=>self::$conf->BACKUP_MYSQL_HOST,
                'dbPass'=>self::$conf->BACKUP_MYSQL_USER_PASS,
                'dbName'=>'information_schema')
               );
        // 2. взимаме името на текущия лог
        $db->query("SHOW MASTER STATUS");
        $resArr = $db->fetchArray();
        // $resArr['file'] e името на текущия бинлог

        // 3. флъшваме лог-а
        $db->query("FLUSH LOGS");

        // 4. взимаме съдържанието на binlog-a в temp-a и го компресираме
        exec("mysqlbinlog --read-from-remote-server -u"
                . self::$conf->BACKUP_MYSQL_USER_NAME
                . " -p" . self::$conf->BACKUP_MYSQL_USER_PASS . " {$resArr['file']} -h"
                . self::$conf->BACKUP_MYSQL_HOST . "| gzip -9 > " . EF_TEMP_PATH . "/" . self::$binLogFileName, $output, $returnVar);
        if ($returnVar !== 0) {
            core_Logs::add("Backup", "", "ГРЕШКА при mysqlbinlog!");
            self::unLock();
            
            exit(1);
        }
        
        // 5. Ако има дефинирана парола криптираме файловете с данните
        if (self::$conf->BACKUP_CRYPT == 'yes') {
            self::$binLogFileName = self::crypt(self::$binLogFileName);
        }

        // 6. добавя се инфо за бинлога
        $maxKey = max(array_keys($metaArr)); 
        $metaArr[$maxKey][] = self::$binLogFileName;
        file_put_contents(EF_TEMP_PATH . "/" . self::$metaFileName, serialize($metaArr));
        
        // 7. Качва се binloga с подходящо име
        self::$storage->putFile(self::$binLogFileName);
         
        // 8. Качва се и мета файла
        self::$storage->putFile(self::$metaFileName);
        
        // 9. Изтриваме бекъп-а от temp-a и metata
        unlink(EF_TEMP_PATH . "/" . self::$binLogFileName);
        unlink(EF_TEMP_PATH . "/" . self::$metaFileName);
        
        core_Logs::add("Backup", "", "binLog Backup OK!");
        self::unLock();
            
        return "binLog Backup OK!";
    }    
    
    /**
     * Почистване на стария бекъп
     * 
     * 
     */
    private static function clean()
    {
        if (!self::lock()) {
            core_Logs::add("Backup","", "Warning: clean не може да вземе Lock.");
            
            exit(1);
        }
        
        // Взимаме мета файла
        if (!self::$storage->getFile(self::$metaFileName)) {
            core_Logs::add('Backup', '', "Warning: clean не може да вземе МЕТА файла.");
            self::unLock();
            
            exit(1);
        } else {
            $metaArr = unserialize(file_get_contents(EF_TEMP_PATH . "/" . self::$metaFileName));
        }
        
        if (count($metaArr) > self::$conf->BACKUP_CLEAN_KEEP) {
            // Има нужда от почистване
            $garbage = array_slice($metaArr, 0, count($metaArr) - self::$conf->BACKUP_CLEAN_KEEP);
            $keeped  = array_slice($metaArr, count($metaArr) - self::$conf->BACKUP_CLEAN_KEEP, count($metaArr));
            file_put_contents(EF_TEMP_PATH . "/" . self::$metaFileName, serialize($keeped));
            // Качваме МЕТАТ-а в сториджа
            self::$storage->putFile(self::$metaFileName);
            // Отключваме бекъп-а, защото изтриването на файлове може да е бавна операция
            self::unLock();
        } else {
            // Нямаме работа по изтриване
            self::unLock();
            core_Logs::add("Backup", '', 'info: clean - нищо за изтриване.');
            
            return;
        }
        // Изтриваме боклука
        $cnt = 0;
        foreach ($garbage as $backups)
            foreach ($backups as $fileName) {
                self::$storage->removeFile($fileName);
                $cnt++;
        }
        core_Logs::add("Backup", '', 'info: clean - успешно изтрити: ' . $cnt . " файла");
        
        return;
    }
    
    /**
     * Запазва конфигурация на bgERP
     * 
     *  @return boolean
     */
    private static function saveConf()
    {
        $traceArr = debug_backtrace();
        $maxKey = max(array_keys($traceArr));
        // Директорията от където се изпълнява скрипта
        $confFiles[] = " " . dirname($traceArr[$maxKey]['file']).'/index.cfg.php';
        $confFiles[] = " " . EF_CONF_PATH . '/' . EF_APP_NAME . '.cfg.php';
        $confFiles[] = " " . EF_CONF_PATH . '/' . '_common.cfg.php';
        
        $cmd = "tar cfvz " . EF_TEMP_PATH . "/" .self::$confFileName;
        foreach ($confFiles as $file) {
            $cmd .= $file;
        }

        exec($cmd, $output, $returnVar);
        if ($returnVar !== 0) {
            core_Logs::add("Backup", "", "error: tar gzip configuration!");
        
            exit(1);
        }
        
        // Ако има дефинирана парола криптираме файловете с данните
        if (self::$conf->BACKUP_CRYPT == 'yes') {
            self::$confFileName = self::crypt(self::$confFileName);
        }
        
        self::$storage->putFile(self::$confFileName);
        
        @unlink(EF_TEMP_PATH . "/" . self::$confFileName);
        
        return;
    }
    
    /**
     * Криптира зададен файл в темп директорията
     * със зададената парола и изтрива оригинала
     * @param string $fileName
     * 
     * @return string - името на новия файл
     */
    private static function crypt($fileName)
    {
        $command = "openssl enc -aes-256-cbc -in "
        . EF_TEMP_PATH . "/" . $fileName .
        " -out " . EF_TEMP_PATH . "/" . $fileName . ".enc" . " -k "
        . self::$conf->BACKUP_PASS . " 2>&1";
        
        exec($command, $output, $returnVar);
        if ($returnVar !== 0 ) {
            $err = implode(",", $output);
            core_Logs::add("Backup", "", "ГРЕШКА при криптиране!: {$err}");
            self::unLock();
        
            exit(1);
        } else {
            // Разкарваме некриптирания файл
            @unlink(EF_TEMP_PATH . "/" . $fileName);
        }
        
        return $fileName.".enc";
    }
    
    /**
     * Запазва файлове от fileMan-a
     * 
     * @return boolean
     */
    private static function saveFileMan()
    {
        $unArchived = fileman_Data::getUnArchived();
        
        foreach ($unArchived as $fileObj) {
            if (@copy($fileObj->path, EF_TEMP_PATH . "/" . fileman_Data::getFileName($fileObj))) {
                if (self::$storage->putFile(fileman_Data::getFileName($fileObj))) {
                    fileman_Data::setArchived($fileObj->id);
                    @unlink(EF_TEMP_PATH . "/" . fileman_Data::getFileName($fileObj));
                }
            }
            
        }
    }
    
    /**
     * Вдига семафор за стартиран бекъп
     * Връща false ако семафора е вече вдигнат
     * 
     *  @return boolean
     */
    private static function lock()
    {
        if (self::isLocked()) {
            
            return FALSE;
        }
        
        return touch(self::$lockFileName);
    }
    
    /**
     * Смъква семафора на бекъп-а
     * 
     *  @return boolean
     */
    public static function unLock()
    {
        self::init(array());
        
        return @unlink(self::$lockFileName);
    }
    
    /**
     * Показва състоянието на семафора за бекъп
     *
     *  @return boolean
     */
    public static function isLocked()
    {
        self::init(array());
        
        return file_exists(self::$lockFileName);
    }
    
    /**
     * Стартиране от крон-а
     *
     * 
     */
    static function cron_Full()
    {
        self::full();
    }
    
    static function cron_BinLog()
    {
        self::binLog();
    }
    
    static function cron_Clean()
    {
        self::clean();
    }
    
    public function cron_FileMan()
    {
        self::saveFileMan();
    }
    

    
    /**
     * Методи за извикване през WEB
     *
     */
    public function act_Full()
    {
        self::init(array());
        
        return self::full();
    }
    
    public function act_BinLog()
    {
        self::init(array());
        
        return self::binLog();
    }
    
    public function act_Clean()
    {
        self::init(array());
        
        return self::clean();
    }
    
    public function act_SaveConf()
    {
        self::init(array());
        
        return self::saveConf();
    }
    
}