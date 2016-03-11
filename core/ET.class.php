<?php



/**
 * Клас  'core_ET' ['ET'] - Система от текстови шаблони
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
class core_ET extends core_BaseClass
{
    
    
    /**
     * Съдържание на шаблона
     */
    var $content;
    
    
    /**
     * Копие на шаблона
     */
    var $contentBackup;
    
    
    /**
     * Място за заместване по подразбиране
     */
    var $defaultPlace;
    
    
    /**
     * Масив с блокове
     */
    var $blocks = array();
    
    
    /**
     * Масив с плейсхолдери
     */
    var $places = array();
    
    
    /**
     * Масив с хешове на съдържание, което се замества еднократно
     */
    var $once = array();
    
    
    /**
     * Чакащи замествания
     */
    var $pending = array();
    
    
    /**
     * 'Изчезваеми' блокове
     */
    var $removableBlocks = array();
    
    
    /**
     * Указател към 'мастер' шаблона
     */
    var $master;
    
    
    /**
     * Името на детайла
     */
    var $detailName;
    
    
    /**
     * Конструктор на шаблона
     */
    function __construct($content = "")
    {
        if ($content instanceof core_ET) {
            $this->content = $content->content;
            $this->places = $content->places;
            $this->once = $content->once;
            $this->pending = $content->pending;
            $this->blocks = $content->blocks;
            $this->removableBlocks = $content->removableBlocks;
            $this->removablePlaces = $content->removablePlaces;
        } else {
            $this->content = $content;
            $this->content = $this->loadFilesRecursivelyFromString($this->content);
            $rmPlaces = $this->getPlaceHolders();
            $this->setRemovableBlocks($rmPlaces);
                
            // Взема началните плейсхолдери, за да могат непопълнените да бъдат изтрити
            if(count($rmPlaces)) {
                foreach($rmPlaces as $place) {
                    $this->removablePlaces[$place] = $place;
                }
            }
        }
        
        // Всички следващи аргументи, ако има такива се заместват на 
        // плейсхолдери с имена [#1#], [#2#] ...
        $args = func_get_args();
        
        if (($n = count($args)) > 1) {
            for ($i = 1; $i < $n; $i++) {
                $this->replace($args[$i], $i);
            }
        }
    }
    
    
    /**
     * Добава обграждащите символи към даден стринг,
     * за да се получи означение на плейсхолдър
     */
    static function toPlace($name)
    {
        
        return "[#{$name}#]";
    }
    
    
    /**
     * Превръща име към означение за начало на блок
     */
    function toBeginMark($blockName)
    {
        return "<!--ET_BEGIN $blockName-->";
    }
    
    
    /**
     * Превръща име към означение за край на блок
     */
    function toEndMark($blockName)
    {
        return "<!--ET_END $blockName-->";
    }
    
    
    /**
     * Намира позициите на маркерите за начало и край на блок
     */
    function getMarkerPos($blockName)
    {
        $beginMark = $this->toBeginMark($blockName);
        
        $markerPos = new stdClass();
        
        $markerPos->beginStart = strpos($this->content, $beginMark);
        
        if ($markerPos->beginStart === FALSE) return FALSE;
        
        $endMark = $this->toEndMark($blockName);
        $markerPos->beginStop = $markerPos->beginStart + strlen($beginMark);
        $markerPos->endStart = strpos($this->content, $endMark, $markerPos->beginStop);
        
        if ($markerPos->endStart === FALSE) return FALSE;
        
        $markerPos->endStop = $markerPos->endStart + strlen($endMark);
        
        return $markerPos;
    }
    
    
    /**
     * Връща даден блок
     */
    function getBlock($blockName)
    {
        if (is_object($this->blocks[$blockName])) {
            
            return $this->blocks[$blockName];
        }
        
        $placeHolder = $this->toPlace(strtoupper($blockName));
        
        $mp = $this->getMarkerPos($blockName);
        
        expect(is_object($mp), 'Не може да бъде открит блока ' . $blockName, $this->content);
        
        $newTemplate = new ET(substr($this->content, $mp->beginStop,
                $mp->endStart - $mp->beginStop));
        $newTemplate->master = & $this;
        $newTemplate->detailName = strtoupper($blockName);
        
        $this->content = substr($this->content, 0, $mp->beginStart) .
        $placeHolder .
        substr($this->content, $mp->endStop, strlen($this->content) - $mp->endStop);
        
        $this->places[strtoupper($blockName)] = 1;
        $this->blocks[$blockName] = $newTemplate;
        $newTemplate->backup();
        
        return $newTemplate;
    }
    
    
    /**
     * Премахва блок от шаболона
     * @param string $blockName - име на шаблона
     */
    public function removeBlock($blockName)
    {
    	$mp = $this->getMarkerPos($blockName);
    	if(!$mp) return;
    	
    	$contentBeforeBlock = substr($this->content, 0, $mp->beginStart);
        $contentAfterBlock = substr($this->content, $mp->endStop);
    	
    	$this->content = $contentBeforeBlock . $contentAfterBlock;
    }
    
    
    /**
     * ,
     * removeBlocks()
     * ,
     */
    function setRemovableBlocks($places)
    {
        if(count($places)) {
            foreach($places as $b) {
                
                $mp = $this->getMarkerPos($b);
                
                if(is_object($mp)) {
                    $content = substr($this->content, $mp->beginStop, $mp->endStart - $mp->beginStop);
                    
                    // Премахване всички плейсхолдери
                    $content = preg_replace('/\[#([a-zA-Z0-9_:]{1,})#\]/', '', $content);
                    
                    $this->removableBlocks[$b] = md5($content);
                }
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function removeBlocks()
    {
        if (count($this->removableBlocks)) {
            foreach ($this->removableBlocks as $blockName => $md5) {
                $mp = $this->getMarkerPos($blockName);
                
                if ($mp) {
                    $content = substr($this->content, $mp->beginStop,
                        $mp->endStart - $mp->beginStop);
                    
                    // Премахване всички плейсхолдери
                    $content = preg_replace('/\[#([a-zA-Z0-9_:]{1,})#\]/', '', $content);
                    
                    if ($md5 == md5($content)) {
                        
                        $content = '';
                    }
                    
                    $this->content = substr($this->content, 0, $mp->beginStart) .
                    $content .
                    substr($this->content, $mp->endStop,
                        strlen($this->content) - $mp->endStop);
                }
            }
        }
        
        if($this->removablePlaces) {
            
            foreach($this->removablePlaces as $p) {
                $place = $this->toPlace($p);
                $this->content = str_replace($place, '', $this->content);
            }
        }
        
        return $this;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function removePlaces()
    {
        $places = $this->getPlaceholders();
        
        foreach ($places as $p) {
            $this->replace('', $p);
        }
        
        return $this;
    }


    /**
     * Премахва чакащите субституции за мястото $place
     */
    function removePendings($place)
    {
        foreach($this->pending as $id => $pending) {
            if($pending->place == $place) {
                unset($this->pending[$id]);
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function backup()
    {
        $this->contentBackup = $this->content;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function restore()
    {
        $this->content = $this->contentBackup;
        $this->places = array();
        $this->once = array();
        $this->pending = array();
    }
    
    
    /**
     * master-,
     * master-
     */
    function append2Master()
    {
        if (is_object($this->master)) {
            $this->master->append($this, $this->detailName);
            $this->restore();
        }
    }
    
    
    /**
     * master-,       master-
     */
    function replace2Master()
    {
        if (is_object($this->master)) {
            $this->master->replace($this, $this->detailName);
            $this->restore();
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function prepend2Master()
    {
        if (is_object($this->master)) {
            $this->master->prepend($this, $this->detailName);
            $this->restore();
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function preparePlace($place)
    {
        if ($place === NULL) {
            return $this->toPlace($this->defaultPlace);
        } else {
            $this->places[$place] = 1;
            
            return $this->toPlace($place);
        }
    }
    
    
    /**
     * Замества контролните символи в текста (начало на плейсхолдер)
     * с други символи, които не могат да се разчетат като контролни
     */
    static function escape($str)
    {
        expect(!($str instanceof stdClass), $str);
        return str_replace('[#', '&#91;#', $str);
    }
    
    static function unEscape($str)
    {
    	expect(!($str instanceof stdClass), $str);
        return str_replace('&#91;#', '[#', $str);
    }
    
    /**
     * @todo Чака за документация...
     */
    function addSubstitution($str, $place, $once, $mode)
    {
        $this->pending[] = (object) array(
            'str' => $str,
            'place' => $place,
            'once' => $once,
            'mode' => $mode);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function push($value, $place, $once = FALSE)
    {
        if (is_array($value)) {
            foreach ($value as $v) {
                $this->addSubstitution($v, $place, $once, 'push');
            }
        } else {
            $this->addSubstitution($value, $place, $once, 'push');
        }
    }
    
    
    /**
     * Връща масив със стойността на чакащия плейсхолдер
     * 
     * @param string $place
     * @param string $mode
     * 
     * @return array
     */
    function getArray($place, $mode = 'push')
    {
        $res = array();

        if (count($this->pending)) {
            foreach ($this->pending as $sub) {
                if ($sub->place == $place && (!$mode || $sub->mode == $mode)) {
                    if ($sub->once) {
                        $md5 = md5($sub->str);
                        
                        if ($this->once[$md5])
                        continue;
                        $this->once[$md5] = TRUE;
                    }
                    $res[] = $sub->str;
                }
            }
        }
        
        return $res;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function processContent($content)
    {
        if (is_object($content) && (is_a($content, "et") || is_a($content, "core_Et"))) {
            //   
            foreach ($content->pending as $sub) {
                
                switch ($sub->mode) {
                    case "append" :
                        $this->append($sub->str, $sub->place, $sub->once);
                        break;
                    case "prepend" :
                        $this->prepend($sub->str, $sub->place, $sub->once);
                        break;
                    case "replace" :
                        $this->replace($sub->str, $sub->place, $sub->once);
                        break;
                    case "push" :
                        $this->push($sub->str, $sub->place, $sub->once);
                        break;
                }
            }
            
            // Прехвърля в Master шаблона всички appendOnce хешове
            if(count($content->once)) {
                foreach ($content->once as $md5 => $true) {
                    $this->once[$md5] = TRUE;
                }
            }
            
            // Прехвърля в мастер шаблона всички плейсхолдери, които трябва да се заличават
            if(count($content->removablePlaces)) {
                foreach ($content->removablePlaces as $place) {
                    $this->removablePlaces[$place] = $place;
                }
            }
            
            return $content->getContent(NULL, 'CONTENT', FALSE, FALSE);
        } else {
            return $this->escape($content);
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function importRemovableBlocks($content)
    {
        if (is_object($content) && (is_a($content, "et") || is_a($content, "core_Et"))) {
            if (count($content->removableBlocks)) {
                foreach ($content->removableBlocks as $name => $md5) {
                    if (!$this->removableBlocks[$name]) {
                        $this->removableBlocks[$name] = $md5;
                    }
                }
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function sub($content, $placeHolder, $once, $mode, $global = TRUE)
    {
        
        if ($content === NULL) return $this;
        
        if ($once) {
            if ($content instanceof core_Et) {
                $str = serialize($content);
            } else {
                $str = $content;
            }
            
            $md5 = md5($str);
            
            if ($this->once[$md5]) {
                
                return $this;
            }
        }
        
        if ($content instanceof core_ET) {
            // DEBUG::startTimer("SUB1");
            $this->importRemovableBlocks($content);
        }
        
        //DEBUG::stopTimer("SUB1");
        
        //DEBUG::startTimer("SUB2");
        $str = $this->processContent($content);
        
        //DEBUG::stopTimer("SUB2");
        
        // DEBUG::startTimer("SUB3");
        $place = $this->preparePlace($placeHolder);
        
        // DEBUG::stopTimer("SUB3");
        
        if (strpos($this->content, $place) !== FALSE) {
            
            if ($once) {
                $this->once[$md5] = TRUE;
            }
            
            switch ($mode) {
                case "append" :
                    $new = $str . $place;
                    break;
                case "prepend" :
                    $new = $place . $str;
                    break;
                case "replace" :
                    $new = $str;
                    break;
            }
            
            $this->content = str_replace($place, $new, $this->content);
        } else {
            if ($placeHolder == NULL) {
                switch ($mode) {
                    case "append" :
                        $this->content = $this->content . $str;
                        break;
                    case "prepend" :
                        $this->content = $str . $this->content;
                        break;
                    case "replace" :
                        $this->content = $str;
                        break;
                }
            } else {
                if($global) {
                    $this->addSubstitution($content, $placeHolder, $once, $mode);
                }
            }
        }

        return $this;
    }
    
    
    /**
     * Заместване след плейсхолдъра
     */
    function append($content, $placeHolder = NULL, $once = FALSE)
    {
        return $this->sub($content, $placeHolder, $once, "append");
    }
    
    
    /**
     * Заместване след пелйсхолдъра.
     * Всички опити за използване на същото съдържание ще бъдат игнорирани
     */
    function appendOnce($content, $placeHolder = NULL)
    {
        return $this->append($content, $placeHolder, TRUE);
    }
    
    
    /**
     * Замества преди плейсхолдъра
     * Всички опити за използване на същото съдържание ще бъдат игнорирани
     */
    function prependOnce($content, $placeHolder = NULL)
    {
        return $this->prepend($content, $placeHolder, TRUE);
    }
    
    
    /**
     * Заместване преди пелйсхолдъра
     */
    function prepend($content, $placeHolder = NULL, $once = FALSE)
    {
        return $this->sub($content, $placeHolder, $once, "prepend");
    }
    
    
    /**
     * Замества посочения плейсходер със съдържанието. Може да се зададе
     * еднократно вкарване на съдържанието при което всички последващи опити
     * за заместване на същото съдържание, ще бъдат пропуснати
     */
    function replace($content, $placeHolder = NULL, $once = FALSE, $global = TRUE)
    {
        return $this->sub($content, $placeHolder, $once, "replace", $global);
    }
    
    
    /**
     * Отпечатва текстовото съдържание на шаблона
     */
    function output($content = '', $place = "CONTENT")
    {   
        echo $this->getContent($content, $place, TRUE, TRUE);
    }
    
    
    /**
     * Връща текстовото представяне на шаблона, след всички възможни субституции
     */
    function getContent($content = NULL, $place = "CONTENT", $output = FALSE, $removeBlocks = TRUE)
    { 
        if ($content) {
            $this->replace($content, $place);
        }
        
        if ($output) {
            $this->invoke('output');
        }
        
        $redirectArr = $this->getArray('_REDIRECT_');
        
        if ($redirectArr[0]) {
            
            $msgArr = Mode::get('redirectMsg');
            
            redirect($redirectArr[0], FALSE, $msgArr['msg'], $msgArr['type']);
        }
        
        if (is_array($this->places)) {
            foreach ($this->places as $place => $dummy) {
                $this->content = str_replace($this->toPlace($place), '', $this->content);
            }
        }
        
        if ($removeBlocks) {
            $this->removeBlocks($removeBlocks);
        }
        
        return $this->content;
    }
    
    
    /**
     * Сетва съдържанието в шаблона
     * 
     * @param string $content
     */
    function setContent($content)
    {
        
        $this->content = $content;
    }
    
    
    /**
     * Прави субституция на данни, които могат да бъдат масив с обекти или масив с масиви
     * в указания блок-държач. Ако няма данни, блока държач изчезва, а се появява указания
     * празен блок
     */
    function placeMass($data, $holderBlock = NULL, $emptyBlock = NULL)
    {
        if ($holderBlock) {
            $tpl = $this->getBlock($holderBlock);
        } else {
            $tpl = & $this;
        }
        
        if ($emptyBlock) {
            $empty = $this->getBlock("$emptyBlock");
        }
        
        if (is_array($data)) {
            foreach ($data as $name => $object) {
                if (is_object($object)) {
                    $tpl->placeObject($object);
                    $tpl->append2master();
                } elseif (is_array($object)) {
                    $tpl->placeArray($object);
                    $tpl->append2master();
                }
            }
        } else {
            if ($emptyBlock) {
                $empty->replace2master();
            }
        }

        return $this;
    }
    
    
    /**
     * Прави субституция на елементите на масив в плейсхолдери започващи
     * с посочения префикс. Ако е посочен блок-държач, субституцията се
     * прави само в неговите рамки.
     */
    function placeArray($data, $holderBlock = NULL, $prefix = '')
    {
        // Ако данните са обект - конвертираме ги до масив
        if (is_object($data)) {
            $this->placeArray(get_object_vars($data), $holderBlock, $prefix);
        }
        
        if ($holderBlock) {
            $tpl = $this->getBlock($holderBlock);
        } else {
            $tpl = & $this;
        }
        
        if ($prefix) {
            $prefix .= "_";
        }
        
        if (count($data)) {
            foreach ($data as $name => $object) {
                if(is_array($object) || (is_object($object) && !($object instanceof core_ET))) {
                    $tpl->placeArray($object, NULL, $prefix . $name);
                } else {
                    $tpl->replace($object, $prefix . $name, FALSE, FALSE);
                }
            }
        }
        
        if ($holderBlock) {
            $tpl->replace2master();
        }

        return $this;
    }
    
    
    /**
     * Прави субституция на променливите на обект в плейсхолдери започващи
     * с посочения префикс
     */
    function placeObject($data, $holderBlock = NULL, $prefix = NULL)
    {
		$arr = (array)$data;
		
		// WORKAROUND
		$dataArr = array();
		foreach ($arr as $key => $var){
			if(is_scalar($var) || $var instanceof core_ET){
				$dataArr[$key] = $var;
			}
		}
		
    	$this->placeArray($dataArr, $holderBlock, $prefix);

        return $this;
    }
    
    
    /**
     * Превежда съдържанието на посочения език, или на текущия
     */
    function translate($lg = NULL)
    {
        $this->content = tr("|*" . $this->content);
    }
    
    
    /**
     * Връща плейсхолдерите на шаблона
     */
    function getPlaceholders()
    {
        preg_match_all('/\[#([a-zA-Z0-9_:]{1,})#\]/', $this->content, $matches);
        
        return $matches[1];
    }
    
    
    /**
     * Връща TUR, ако има плейсхолдър с посоченото име, и FALSE ако няма
     */
    function isPlaceholderExists($placeholder)
    {
        $place = $this->toPlace($placeholder);
        
        return strpos($this->content, $place) !== FALSE;
    }
    
    
    /**
     * Конвертира към стринг
     */
    function __toString()
    {
        return $this->getContent();
    }
    
    
    /**
     * Връща шаблона на подадения файл през превода
     * 
     * @param string $file - Пътя на файла от пакета нататък
     * 
     * @return core_Et - Обект
     */
    public static function getTplFromFile($file)
    {
        $content = self::loadFilesRecursively($file);
        
        return new ET(tr("|*" . $content));
    }
    
    
    /**
     * Зарежда всички файлове, които са зададени като пътища в плейсхолдер от файл
     * 
     * @param string $file
     * 
     * @return string
     */
    public static function loadFilesRecursively($file)
    {
        $content = getFileContent($file);
        
        $content = self::loadFilesRecursivelyFromString($content);
        
        return $content;
    }
    
    
    /**
     * Зарежда всички файлове, които са зададени като пътища в плейсхолдер от стринг
     * 
     * @param string $content
     * 
     * @return string
     */
    public static function loadFilesRecursivelyFromString($content)
    {
        $pathArr = self::getTeplatePlaceholders($content);
        
        if ($pathArr) {
            foreach ($pathArr as $path) {
                
                $resContent = self::loadFilesRecursively($path);
                
                $content = strtr($content, array("[#{$path}#]" => $resContent));
            }
        }
        
        return $content;
    }
    
    
    /**
     * Връща масив с всички пътища, зададени в плейсхолдерите
     * 
     * @param string
     * 
     * @return array
     */
    protected static function getTeplatePlaceholders($str)
    {
        preg_match_all('/\[#((\w*(\/|\.)+\w*)*)#\]/', $str, $matches);
        
        return $matches[1];
    }
}
