<?php



/**
 * Клас 'doc_DocumentIntf' - Интерфейс за мениджърите на документи
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_DocumentIntf
{
    
    
    /**
     * Намира най-подходящите $rec->folderId (папка)
     * и $rec->threadId за дадения документ
     */
    function route($rec)
    {
        $this->class->route($rec);
    }
    
    
    /**
     * Връща манипулатор на документа
     */
    function getHandle($id)
    {
        return $this->class->getHandle($id);
    }
    
    
    /**
     * Връща обект,следните вербални стойности
     * - $docRow->title - Заглавие на документа
     * - $docRow->authorId - id на автора на документа, ако той е потребител на системата
     * - $docRow->author - името на автора на документа
     * - $docRow->authorEmail - името на автора на документа
     * - $docRow->state - състояние на документа
     */
    function getDocumentRow($id)
    {
        return $this->class->getDocumentRow($id);
    }
    
    
    /**
     * Връща заглавието на документа, като хипервръзка, сочеща към самия документ
     */
    function getLink($id)
    {
        return $this->class->getLink($id);
    }
    
    
    /**
     * Връща визуалното представяне на документа
     */
    function getDocumentBody($id, $mode = 'html', $options = NULL)
    {
        return $this->class->getDocumentBody($id, $mode, $options);
    }
    
    
    /**
     * Определя състоянието на нишката от документи
     * Външните документи правят нишката в състояние "отворено",
     * а всички останали - в "затворено"
     */
    function getThreadState($id)
    {
        return $this->class->getThreadState($id);
    }
    
    
    /**
     * Потребителите, с които е споделен този документ
     *
     * @return string keylist(mvc=core_Users)
     */
    function getShared($id)
    {
        return $this->class->getShared($id);
    }
    
    
    function getContainer($id)
    {
        return $this->class->getContainer($id);
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    static function canAddToFolder($folderId)
    {
        return $this->class->canAddToFolder($folderId);
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
    function canAddToThread($threadId)
    {
        return $this->class->canAddToThread($threadId);
    }
    
    
    /**
     * Връща възможните типове за файлови формати, към които може да се конвертира дадения документ
     *
     * @return array $res - Масив с типа (разширението) на файла и стойност указваща дали е избрана 
     *                      по подразбиране
     */
    function getPossibleTypeConvertings()
    {
        return $this->class->getPossibleTypeConvertings();
    }
    
    
	/**
     * Връща възможните типове за файлови формати, в зависимост от класа
     *
     * @return array $res - Масив с типа (разширението) на файла и стойност указваща дали е избрана 
     *                      по подразбиране
     */
    function getTypeConvertingsByClass()
    {
        return $this->class->getTypeConvertingsByClass();
    }
    
    
    /**
     * Конвертира документа към файл от указания тип и връща манипулатора му
     *
     * @param string $fileName - Името на файла, без разширението
     * @param string $type     - Разширението на файла
     *
     * return array $res - Масив с fileHandler' и на документите
     * @deprecated
     * @see doc_DocumentIntf::convertTo()
     */
    function convertDocumentAsFile($fileName, $type)
    {
        return $this->class->convertDocumentAsFile($fileName, $type);
    }
    
    
    /**
     * Конвертира документа към файл от указания тип и връща манипулатора му
     *
     * @param string $type     - Разширението на файла
     * @param string $fileName - Името на файла; ако не е зададено се определя автоматично -
     *                             {ABBR}{id}.{type}
     *
     * return array $res - масив с манипулатора на генерирания файл
     */
    function convertTo($type, $fileName = NULL)
    {
        return $this->class->convertTo($type, $fileName);
    }
    
    /**
     * Връша прикачените файлове в документите
     * 
     * @param mixed $rec - id' то на документа или записа на документа
     * 
     * @return array $files - Масив с ключ манипулатора на файла и стойност	името на файла
     */
    function getAttachments($rec)
    {
        
        return $this->class->getAttachments($rec);
    }
    
    
    /**
     * Връща масив от използваните документи в даден документ (като цитат или
     * са включени в детайлите му)
     * @param int $id - ид на документ
     * @return param $res - масив с използваните документи
     * 					['class'] - инстанция на документа
     * 					['id'] - ид на документа
     */
    function getUsedDocs($id)
    {
    	return $this->class->getUsedDocs($id);
    }
}
