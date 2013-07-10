<?php



/**
 * Клас 'csv_Lib' - Пакет за работа с CSV файлове
 *
 *
 * @category  vendors
 * @package   csv
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class csv_Lib
{
    /**
     * Импортира CSV файл в указания модел
     */
    static function import($mvc, $file, $fields = array(), $defaults = array(), $format = array())
    {   
        // Дефолт стойностите за форматирането по подразбиране
        setIfNot($format['length'], 0);
        setIfNot($format['delimiter'], ',');
        setIfNot($format['enclosure'], '"');
        setIfNot($format['escape'], '\\');
        setIfNot($format['skip'], '#');
        
        $firstRow = TRUE; 
        $res    = (object) array('created' => 0, 'updated' => 0, 'skipped' =>0);
        $fields = arr::make($fields);

        $fromZero = !$mvc->fetch("1=1");
        
        $path = getFullPath($file);

        expect(($handle = fopen($path, "r")) !== FALSE);

        while (($data = fgetcsv($handle, $format['length'], $format['delimiter'], $format['enclosure'], $format['escape'])) !== FALSE) {

            // Пропускаме празните линии
            if(!count($data) || (count($data) == 1 && trim($data[0]) == '')) continue;

            // Пропускаме редовете със знака указан в $skip
            if($data[0]{0} == $format['skip']) continue;

            // Ако не са указани полетата, вземаме ги от първия ред
            if($firstRow && !count($fields)) {
                foreach($data as $f) {
                    $fields[] = $f;
                }
                
                $firstRow = FALSE;
            } else {
                // Вкарваме данните
                if($defaults) {
                    $rec = (object)$defaults;
                } else {
                    $rec = new stdClass();
                }
                
                foreach($fields as $i => $f) {
                    $rec->{$f} = $data[$i];
                }
                
                // Ако таблицата се попълва от нулата, само се добавят редове
                if($fromZero) {
                    $mvc->save($rec);
                    $res->created++;
                    continue;
                }

                $conflictFields = array();

                if(!$mvc->isUnique($rec, $conflictFields, $exRec)) {
                    $rec->id = $exRec->id;
                    $flagUpdate = TRUE;
                } else {
                    $res->created++;
                    $flagUpdate = FALSE;
                }
                
                // По подразбиране записът е добавен от системния потребител
                setIfNot($rec->createdBy, -1);

                // Ако нямаме запис с посочените уникални стойности, вкарваме новия
                $mvc->save($rec);
                
                if($flagUpdate) {
                    $res->skipped++;
                    $rec = $mvc->fetch($rec->id);
                    foreach($fields as $i => $f) {
                        if($rec->{$f} != $exRec->{$f}) {
                            $res->updated++;
                            $res->skipped--;
                            break;
                        }
                    }
                }
            }
        }
            
        fclose($handle);

        $res->html = self::cntToVerbal($res);
        
        return $res;
    }


    /**
     * Функция, която импортира еднократно даден csv файл в даден модел
     */
    static function importOnce($mvc, $file, $fields = array(), $defaults = array(), $format = array(), $delete = FALSE)
    {
        // Пътя до файла с данните
        $filePath = getFullPath($file);
        
        // Името на променливата, в която се записва хеша на CSV файла
        $param = 'csvFile' . preg_replace('/[^a-z0-9]+/', '_', $file);
        
        // Хеша на CSV данните
        $hash = md5_file($filePath);

        list($pack,) = explode('_', $mvc->className);
        
        // Конфигурация на пакета 'lab'
        $conf = core_Packs::getConfig($pack);

        $cntObj = new stdClass();

        if($conf->{$param} != $hash) {
            
            // Изтриваме предишното съдържание на модела, ако е сетнат $delete
            if($delete) {
                $mvc->db->query("TRUNCATE TABLE `{$mvc->dbTableName}`");
            }
            
            $cntObj = self::import($mvc, $file, $fields, $defaults, $format);
            
            // Записваме в конфигурацията хеша на последния приложен csv файл
            core_Packs::setConfig($pack, array($param => $hash));
        }

        return $cntObj;
    }


    /**
     * Импортира съдържанието на посочения CSV файл, когато той е променян
     * Преди импортирането изпразва таблицата, 
     */
    static function importOnceFromZero($mvc, $file, $fields = array(), $defaults = array(), $format = array())
    {
        return self::importOnce($mvc, $file, $fields, $defaults, $format, TRUE);
    }


    /**
     * Връща html вербално представяне на резултата от ::import(...)
     */
    static function cntToVerbal($cntObj)
    {
        $res = '';

        if($cntObj->created) {
            $res .= "\n<li style='color:green;'>Създадени са {$cntObj->created} записа</li>";
        }
            
        if($cntObj->updated) {
            $res .= "\n<li style='color:#600;'>Обновени са {$cntObj->updated} записа</li>";
        }
            
        if($cntObj->skipped) {
            $res .= "\n<li>Пропуснати са {$cntObj->skipped} записа</li>";
        }

        return $res;
    }
}