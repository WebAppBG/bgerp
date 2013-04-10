<?php


/**
 * Драйвер за работа с архиви.
 * 
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_Archive extends fileman_webdrv_Generic
{
    
    
    /**
     * Кой таб да е избран по подразбиране
     * @Override
     * @see fileman_webdrv_Generic::$defaultTab
     */
    static $defaultTab = 'content';
    
    
    /**
     * Връща всички табове, които ги има за съответния файл
     * 
     * @param object $fRec - Записите за файла
     * 
     * @return array
     * 
     * @Override
     * @see fileman_webdrv_Generic::getTabs
     */
    static function getTabs($fRec)
    {
        // Вземаме табовете от родителя
        $tabsArr = parent::getTabs($fRec);
        
        // Директорията, в която се намираме вътре в архива
        $path = core_Type::escape(Request::get('path'));
        
        // Вземаме съдържанието
        $contentStr = static::getArchiveContent($fRec, $path);
        
        // Таб за съдържанието
		$tabsArr['content'] = (object) 
			array(
				'title'   => 'Съдържание',
				'html'    => "<div class='webdrvTabBody' style='white-space:pre-wrap;'><fieldset class='webdrvFieldset'><legend>Съдържание</legend>{$contentStr}</fieldset></div>",
				'order' => 7,
				'tpl' => $contentStr,
			);
        
        return $tabsArr;
    }
    
    
    /**
     * Връща инстанция на адаптера за работа с архиви
     * 
     * @param object $fRec - Записите за файла
     */
    static function getArchiveInst($fRec)
    {
        
        // Връщаме инстанцията
        return cls::get('archive_Adapter', $fRec->fileHnd);
    }
    
    
    /**
     * Връща съдържанието на архива в дървовидна структура
     * 
     * @param object $fRec - Записите за файла
     */
    static function getArchiveContent($fRec, $path = NULL) 
    {
        // Инстанция на класа
        $inst = static::getArchiveInst($fRec);
        
        // URL' то където да сочат файловете
        $url = array('fileman_webdrv_Archive', 'absorbFileInArchive', $fRec->fileHnd, 'index' => 1);
        
        // Създаваме дървото
        $tree = $inst->tree($url);
        
        // Изтриваме временните файлове
        $inst->deleteTempPath();
        
        // Връщаме дървото
        return $tree;
    }
    
    
    /**
     * Уплоадва файла от архива
     * 
     * @param object $fRec - Записите за файла
     * @param integer $index - Номера на файлам, който ще се екстрактва
     * 
     * @return fileHandler - Манипулатор на файл
     */
    static function uploadFileFromArchive($fRec, $index)
    {
        // Вземаме инстанция на архива
        $archive = static::getArchiveInst($fRec);
        
        // Качваме съответния файл
        $fh = $archive->getFile($index);
        
        // Изтриваме временните файлове
        $archive->deleteTempPath();
        
        // Връщаме манипулатора на файла
        return $fh;
    }
}
