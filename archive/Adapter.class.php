<?php


/**
 * Директория, в която ще се държат екстрактнатите файлове
 */
defIfNot('ARCHIVE_TEMP_PATH', EF_TEMP_PATH . '/archive');



/**
 * Пътя до 7z пакета
 */
defIfNot('ARCHIVE_7Z_PATH', '7z');


/**
 * Адаптер за разглеждане и разархивиране на архиви
 *
 * @category  vendors
 * @package   archive
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class archive_Adapter
{
    
    
    /**
     * Инстанция на класа
     */
    protected $inst;
    
    
    /**
     * Пътя до временния файл на архива
     */
    protected $path;
    
    
    /**
     * Временната директория с временните файлове, екстрактнати от архива
     */
    protected $dir;
    
    
    /**
     * При създаване на инстанция на класа, инициализираме някои променливи
     * 
     * @param fileHandler $fh - Манипулатор на файл
     */
    function init($fh)
    {
        // Вкарваме пакета
        require_once getFullPath("archive/7z/7z.php");
        
        // Пътя до файла
        $this->path = fileman::extract($fh);

        // Инстанция на архива
        $this->inst = new Archive_7z($this->path);
    }
    
    
    /**
     * Връща в дървовидна структура съдържанието на архива, които сочат в съответното URL
     * 
     * @param array $url - Масив с URL
     */
    function tree($url)
    {
        // Вземаме съдържанието
        $entriesArr = $this->getEntries();
        
        // Инстанция на класа
        $tableInst = cls::get('core_Tree');

        // Обхождаме масива
        foreach ((array)$entriesArr as $key => $entry) {
            
            // Пътя в архива
            $path = $entry->getPath();
            
            // Заместваме разделителите за поддиректория с разделителя за дърво
            $path = str_replace(array('/', '\\'), "->", $path);
            
            // Ако има размер
            if ($entry->getSize()) {
                
                $urlPath = $url;
                
                // Индекса да е ключа
                $urlPath['index'] = $key;
            } else {
                
                // Ако няма размер
                $urlPath = FALSE;
            }
            
            // Добавяме пътя в дървото със съответното URL
            $tableInst->addNode($path, $urlPath, TRUE);
        }
        
        // Името
        $tableInst->name = 'archive';
        
        // Рендираме изгледа
        $res = $tableInst->renderHtml(NULL);
        
        // Връщаме шаблона
        return $res;
    }
    
    
    /**
     * Връща масив от обекти със информацията за съдържанието на файла
     * 
     * @param ingeger $entry - Индекса на файла от архива
     * 
     * @return mixed - Ако е подаден индекс, връща обект с информация за съответния файл/папка
     * Ако не е подаден индек, масив с всички файлове/папки, като обекти
     */
    public function getEntries($entry = FALSE)
    {
        // Вземаме информация за всички файлове/папки
        $entriesArr = $this->inst->getEntries();
        
        // Ако е подаден номер на файл
        if ($entry !== FALSE) {
            
            // Връщаме съответния запис
            return $entriesArr[$entry];
        }
        
        // Обхождаме масива
        foreach ($entriesArr as $e) {
            
            // Минаваме пътя през изчисване на името
            $e->path = i18n_Charset::convertToUtf8($e->path);
        }
        
        // Връщаме целия маси
        return $entriesArr;
    }
    
    
    /**
     * Качва в кофа файла в съответния индекс и връща манипулатора на качения файл
     * 
     * @param integer $index - Индекса на файла в архива
     */
    public function getFile($index)
    {
        try {
            
            // Вземаме обекта за съответния файл
            $entry = $this->getEntries($index);
        } catch (Exception $e) {
            
            // Ако възникне грешка
            expect(FALSE, 'Възникна грешка при свалянето на файла');
        }
        
        
        // Ако няма размер
        expect($entry->getSize(), 'Не е файл');
        
        try {
            
            // Вземаме пътя до файла в архива
            $path = $entry->getPath();
        } catch (Exception $e) {
            
            // Ако възникне грешка
            expect(FALSE, 'Не може да се определи пътя до файла.');
        }
        
        // Вземаме манипулатора на файла
        $fh = $this->absorbFile($path);
        
        // Очакваме да има манипулатор
        expect($fh, 'Не може да се вземе файла');
        
        return $fh;
    }


	/**
     * Изтрива временния файл
     */
    public function deleteTempPath()
    {   
        // Изтрива временния файл
        fileman::deleteTempPath($this->path);
    }
    
    
    /**
     * Абсорбираме файла от архива.
     * Качваме подадения файл от архива, в кофата 'archive'
     * 
     * @param string $path - Вътрешния път в архива
     * 
     * @param fileHandler $fh - Манипулатора на файла
     */
    protected function absorbFile($path)
    {
        try {
            
            // Екстрактваме файла от архива и връщаме пътя във файловата система
            $path = $this->extractEntry($path);
        } catch (Exception $e) {
            
            // Ако възникне грешка
            expect(FALSE, 'Не може да се екстрактен файла от архива');
        }
        
        // Ако е файл
        if (is_file($path)) {
            
            // Абсорбираме файла
            $fh = fileman::absorb($path, 'archive');    
        }
        
        // Изтриваме временнада директория със съдържанието му
        core_Os::deleteDir($this->dir);
        
        return $fh;
    }
    
    
    /**
     * Екстрактваме файла от архива и връщаме пътя във файловата система
     * 
     * @param string $path - Вътрешния път в архива
     */
    function extractEntry($path)
    {
        // Вземаме директорията
        $outputDir = $this->setOutputDirectory();
        
        // Екстрактваме файла
        $this->inst->extractEntry($path);
        
        // Връщаме пълния път до файла
        return $this->dir . '/' . $path;        
    }
    
    
	/**
     * Задаваме временна директроя, където ще се разархивират файловете
     */
    protected function setOutputDirectory()
    {
        // Вземаме директорията за временните файлове
        $dir = ARCHIVE_TEMP_PATH;
        
        // Сканираме директорията
        $dirs = @scandir($dir);
        
        // Опитваме се да генерираме име, което не се среща в директория
        do {
            $newName = str::getRand();
        }while(in_array($newName, (array)$dirs));
        
        // Пътя на директорията
        $this->dir = $dir . '/' . $newName;
        
        // Създаваме директорията
        expect(mkdir($this->dir, 0777, TRUE), 'Не може да се създаде директория.');
        
        // Инициализираме директорията
        $this->inst->setOutputDirectory($this->dir);
    }
}