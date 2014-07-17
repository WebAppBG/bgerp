<?php


/**
 * Клас 'doc_RichTextPlg' - Добавя функционалност за поставяне handle на документи в type_Richtext
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_RichTextPlg extends core_Plugin
{
    
    
    /**
     * Шаблон за намиране на линкове към документи
     * # (от 1 до 3 букви)(от 1 до 10 цифри). Без да се прави разлика за малки и големи букви.
     * Шаблона трябва да не започва и/или да не завършва с буква и/или цифра
     * 
     * @param begin    - Символа преди шаблона
     * @param dsName  - Името на шаблона, с # отпред
     * @param name     - Името на шаблона, без # отпред
     * @param abbr     - Абревиатурата на шаблона
     * @param id       - id' то на шаблона
     * @param end      - Символа след шаблона
     */
    static $pattern = "/(?'begin'[^a-z0-9а-я]|^){1}(?'dsName'\#(?'name'(?'abbr'[a-z]{1,3})(?'id'[0-9]{1,10})))(?'end'[^a-z0-9а-я]|$){1}/iu";
    
    
    /**
     * Обработваме елементите линковете, които сочат към докъментната система
     */
    function on_AfterCatchRichElements($mvc, &$html)
    {
        $this->mvc = $mvc;
        
        //Ако намери съвпадение на регулярния израз изпълнява функцията
        $html = preg_replace_callback(self::$pattern, array($this, '_catchFile'), $html);
        
        // Прихваща всички никове в ричтекста
        $html = preg_replace_callback(rtac_Plugin::$pattern, array($this, '_catchNick'), $html);
    }
    
    
    /**
     * Заменяме линковете от система с абсолютни URL' та
     *
     * @param array $match - Масив с откритите резултати
     *
     * @return string $res - Ресурса, който ще се замества
     */
    function _catchFile($match)
    {
        //Име на файла
        $docName = $match['dsName'];

        if (!$doc = doc_Containers::getDocumentByHandle($match)) {
            return $match[0];
        }
        
        $mvc    = $doc->instance;
        $docRec = $doc->rec();
        
        //Създаваме линк към документа
        $link = bgerp_L::getDocLink($docRec->containerId, doc_DocumentPlg::getMidPlace());
        
        //Уникален стринг
        $place = $this->mvc->getPlace();
        
        //Ако сме в текстов режим
        if(Mode::is('text', 'plain')) {
            //Добавяме линк към системата
            $this->mvc->_htmlBoard[$place] = "{$docName} ( $link )";
        } else {
            
            $title = substr($docName, 1);
            
            // Икона на линка
            $attr['ef_icon'] = $doc->getIcon($doc->that);
            
            // Атрибути на линка
            $attr['class'] = 'docLink';
            
            $attr['rel'] = 'nofollow';
            
            // Ако изпращаме или принтираме документа
            if (Mode::is('text', 'xhtml') || Mode::is('printing')) {
                
                // Линка да се отваря на нова страница
                $attr['target'] = '_blank';    
            } else {
                // Ако линка е в iframe да се отваря в родителския(главния) прозорец
                $attr['target'] = "_parent";
            }
            
            $href = ht::createLink($title, $link, NULL, $attr);
            
            //Добавяме href атрибута в уникалния стинг, който ще се замести по - късно
            $this->mvc->_htmlBoard[$place] = $href->getContent();
        }

        //Стойността, която ще заместим в регулярния израз
        //Добавяме символите отркити от регулярниярния израз, за да не се развали текста
        $res = $match['begin'] . "[#{$place}#]" . $match['end'];

        return  $res;
    }


    /**
     * Намира всички цитирания на хендъли на документи в текст
     *
     * @param string $rt - Стринг, в който ще търсим.
     * @return array $docs - Масив с ключове - разпознатите хендъли и стойности - масиви от вида
     *                         array(
     *                             'name' => хендъл, също като ключа
     *                             'mvc'  => мениджър на документа с този хендъл
     *                             'rec'  => запис за документа с този хендъл
     *                         ) 
     */
    static function getAttachedDocs($rt)
    {
        $docs = array();
        
        //Ако сме открили нещо
        if (preg_match_all(self::$pattern, $rt, $matches, PREG_SET_ORDER)) {
            
            //Обхождаме всички намерени думи
            foreach ($matches as $match) {
                if (!$doc = doc_Containers::getDocumentByHandle($match)) {
                    continue;
                }
                
                //Името на документа
                $name = $doc->getHandle();
                $mvc  = $doc->getInstance();
                $rec  = $doc->rec();
                
                $docs[$name] = compact('name', 'mvc', 'rec');
            }
            
            return $docs;
        }
    }
    
    
    /**
     * От името на файла намира класа и id' то на документа
     *
     * @param string $fileName - името на файла
     *
     * @return array $info - Информация за масива. $info['className'] - Името на класа. $info['id'] - id' то на документа
     */
    static function getFileInfo($fileName)
    {
        // Ако не е подадено нищо
        if (!trim($fileName)) return ;
        
        // Регулярен израз за определяне на всички думи, които могат да са линкове към наши документи
        preg_match("/(?'name'(?'abbr'[a-z]+)(?'id'[0-9]+))/i", $fileName, $matches);
        
        // Преобразуваме абревиатурата от намерения стринг в главни букви
        $abbr = strtoupper($matches['abbr']);
        
        // Вземаме всички класове и техните абревиатури от документната система
        $abbrArr = doc_Containers::getAbbr();
        
        // Името на класа
        $className = $abbrArr[$abbr];
        
        //id' то на класа
        $id = $matches['id'];
        
        // Вземаме записа от модела
        if ($id && $className) {
            
            // Името на класа
            $handleInfo['className'] = $className;
            
            // id' то на класа
            $handleInfo['id'] = $id;
            
            $rec = $className::fetchByHandle($handleInfo);
        }
        
        // Провяряваме дали имаме права и дали има такъв запис
        if (($rec) && ($className::haveRightFor('single', $rec))) {
            
            // Масив с id и класа
            $info = $handleInfo;
            
            return $info;
        }
    }

    
    /**
     * Прихваща извикването на getInfoFromDocHandle
     * Връща информация за документа, от манипулатора му
     */
    function on_GetInfoFromDocHandle($mvc, &$res, $fileName)
    {
        // Вземаме информация за файла
        $fileInfo = static::getFileInfo($fileName);
        
        // Ако няма, връщаме
        if (!$fileInfo) return ;
        
        // Вземаме инстанция на класа
        $class = cls::get($fileInfo['className']);
        
        $rec = $class->fetchByHandle($fileInfo);
        
        // Вземаме записа от контейнера на съответния документ
        $cRec = $class->getContainer($rec->id);
        
        // Добавяме датата
        $res['date'] = dt::mysql2verbal($cRec->createdOn);
        
        // Ако има създател
        if ($cRec->createdBy > 0) {
            
            // Добавяме имената на автора
            $res['author'] = core_Users::getVerbal($cRec->createdBy, 'names');
        } else {
            
            // Ако няма създател или е системата
            
            // Ако има клас и id на документ
            if ($class && $fileInfo['id']) {
                
                // Вземаме данните за документа
                $dRow = $class->getDocumentRow($fileInfo['id']);
                
                // Добавяме автора
                $res['author'] = $dRow->author;
                
                // Добавяме имейла, ако има такъв
                $res['authorEmail'] = $dRow->authorEmail;
            }
        }
    }
    
    
    /**
     * Връща всички документи които са цитирани във всички richtext полета
     * на даден мениджър
     * @param core_Mvc $mvc - мениджър
     * @param stdClass $rec - запис, за който проверяваме
     * @return array - Масив с ключове - разпознатите хендъли и стойности - масиви от вида
     *                       	array(
     *                             'name' => хендъл, също като ключа
     *                             'mvc'  => мениджър на документа с този хендъл
     *                             'rec'  => запис за документа с този хендъл
     *                          ) 
     */
    public static function getDocsInRichtextFields(core_Mvc $mvc, $rec)
    {
    	$all = '';
    	$rec = $mvc->fetch($rec->id);
    	$fields = $mvc->selectFields();
    	foreach ($fields as $name => $fld){
    		if($fld->type instanceof type_Richtext){
    			$all .= $rec->{$name};
    		}
    	}
    	
    	// Намират се всички цитирания на документи в поле richtext
    	return static::getAttachedDocs($all);
    }
    
    
    /**
     * Добавя бутон за качване на документ
     * 
     * @param core_Mvc $mvc
     * @param core_Toolbar $toolbarArr
     * @param array $attr
     */
    function on_AfterGetToolbar($mvc, &$toolbarArr, &$attr)
    {
        // Ако има права за добавяне
        if (doc_Containers::haveRightFor('adddoc')) {
            
            // id
            $id = $attr['id'];
            
            // Име на функцията и на прозореца
            $windowName = $callbackName = 'placeDoc_' . $id;
            
            // Ако е мобилен/тесем режим
            if(Mode::is('screenMode', 'narrow')) {
                
                // Парамтери към отварянето на прозореца
                $args = 'resizable=yes,scrollbars=yes,status=no,location=no,menubar=no,location=no';
            } else {
                $args = 'width=600,height=600,resizable=yes,scrollbars=yes,status=no,location=no,menubar=no,location=no';
            }
            
            // URL за добавяне на документи
            $url = doc_Containers::getUrLForAddDoc($callbackName);
            
            // JS фунцкията, която отваря прозореца
            $js = "openWindow('{$url}', '{$windowName}', '{$args}'); return false;";
            
            // Бутон за отвяряне на прозореца
            $documentUpload = new ET("<a class=rtbutton title='" . tr("Добавяне на документ/и от системата") . "' onclick=\"{$js}\">" . tr("Документ") . "</a>");
            
            
            // JS функцията
            $callback = "function {$callbackName}(docHnd) {
                var ta = get$('{$id}');
                rp(docHnd, ta, 1);
                return true;
            }";
            
            // Добавяме скрипта
            $documentUpload->appendOnce($callback, 'SCRIPTS');
            
            // Добавяне в групата за добавяне на документ
            $toolbarArr->add($documentUpload, 'filesAndDoc', 1000.055);
        }
    }
    
    
    /**
     * Прихваща никовете и създава линкове към сингъла на профилите
     * 
     * @param array $match
     */
    function _catchNick($match)
    {
        // Да не сработва в текстов режим
        if (Mode::is('text', 'plain')) return $match[0];
        
        // Вземаме id на записа от ника
        $nick = $match['nick'];
        $nick = strtolower($nick);
        $id = core_Users::fetchField(array("LOWER (#nick) = '[#1#]'", $nick));
        
        if (!$id) return $match[0];
        
        // Добавяме в борда
        $place = $this->mvc->getPlace();
        $this->mvc->_htmlBoard[$place] = crm_Profiles::createLink($id);
        
        return "[#{$place}#]";
    }
}
