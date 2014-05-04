<?php


/**
 * Тип на записите в кеша
 */
defIfNot('RICHTEXT_CACHE_TYPE', 'RichText');


/**
 * Текстове, които ще се удебеляват автоматично
 * @type type_Set
 */
defIfNot('RICHTEXT_BOLD_TEXT', 'За,Отн,Относно,回复,转发,SV,VS,VS,VL,RE,FW,FRW,TR,AW,WG,ΑΠ,ΣΧΕΤ,ΠΡΘ,R,RIF,I,SV,FS,SV,VB,RE,RV,RES,ENC,Odp,PD,YNT,İLT');


/**
 * Клас  'type_Richtext' - Тип за форматиран (като BBCode) текст
 *
 *
 * @category  ef
 * @package   type
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class type_Richtext extends type_Blob 
{
    
    static $emoticons = array(
        'smile' => ' :) ',
        'bigsmile' => ' :D ',
        'cool' => ' ;) ',
        'beer' => ' [beer] ',
        'question' => ' [?] ',
        'heart' => ' [love] ',
        'ok' => ' [ok] ',
        'think' => ' :-? '
    );
    
    
    /**
     * Шаблон за болдване на текст
     */
    static $boldPattern = NULL;
    
    
    /**
     * Максимална дължина на едноредов коментар
     */
    const ONE_LINE_CODE_LENGTH = 120;
    
    
    /**
     * Шаблон за намиране на линкове в текст
     */
    // static $urlPattern = "#((www\.|http://|https://|ftp://|ftps://|nntp://)[^\s<>()]+)#i";
    
    
	/**
     * Инициализиране на типа
     * Задава, че да се компресира
     */
    function init($params = array())
    {
        // По подразбиране да се компресира
        setIfNot($params['params']['compress'], 'compress');
        
        // По подразбиране е средно голямо
        setIfNot($params['params']['size'], 1000000);

        // Ако е зададено да не се компресира
        if ($params['params']['compress'] == 'no') {
            
            // Премахваме от масива
            unset($params['params']['compress']);
        }
        
        parent::init($params);
    }
    
    
    /**
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        $tpl = new ET("<span class='richEdit' style='width:100%;'>[#TEXTAREA#]<div class='richedit-toolbar {$attr['errorClass']}'>[#TBL_GROUP1#][#TBL_GROUP2#][#TBL_GROUP3#]</div></span>");
        
        if(Mode::is('screenMode', 'narrow')) {
            $attr['style'] .= 'min-width:260px;width:100%;';
            setIfNot($attr['rows'], $this->params['rows'], 7);
        } else {
            $attr['style'] .= 'width:100%;';
            setIfNot($attr['rows'], $this->params['rows'], 10);
        }
        
        // Атрибута 'id' се сетва с уникален такъв, ако не е зададен
        ht::setUniqId($attr);
        
        $attr['onselect'] = 'sc(this);';
        $attr['onclick'] = 'sc(this);';
        $attr['onkeyup'] = 'sc(this);';
        $attr['onchange'] = 'sc(this);';
        $attr['onfocus'] = "getEO().textareaFocus('{$attr['id']}');";
        $attr['onblur'] = "getEO().textareaBlur('{$attr['id']}');";
        
        $tpl->append(ht::createTextArea($name, $value, $attr), 'TEXTAREA');
        
        $toolbarArr = type_Richtext::getToolbar($attr);
        
        $toolbarArr->order();
        
        foreach($toolbarArr as $link) {
            $tpl->append($link->html, $link->place);
        }
        
        // Ако е зададено да се аппендва маркирания текст, като цитата
        if ($this->params['appendQuote']) {
            
            // Добавяме функцията за апендване на цитата
            $tpl->append("\n runOnLoad(function(){appendQuote('{$attr['id']}');});", 'SCRIPTS');
        }
        
    	$tpl->append("\n runOnLoad(function(){hideRichtextEditGroups();});", 'SCRIPTS');
    	
    	$tpl->append("\n runOnLoad(function(){getEO().saveSelTextInTextarea('{$attr['id']}');});", 'SCRIPTS');
    	
        return $tpl;
    }
    
    
    /**
     * Преобразуване от вътрешно представяне към вербална стойност
     */
    function toVerbal($value)
    {
        if (!strlen($value)) return NULL;
        
        if (Mode::is('text', 'plain')) {
            $res = strip_tags($this->toHtml($value));
            $res = html_entity_decode($res, ENT_QUOTES, 'UTF-8');
        } else {
            $res = $this->toHtml($value);
        }
        

        return $res;
    }
    
    
    /**
     * Преобразува текст, форматиран с мета тагове (BB) в HTML
     *
     * Преобразованията са следните:
     * o Новите редове ("\n") се заменят с <br/>
     * o Интервалите в началото на реда се заменят с &nbsp;
     * o BB таговете се заменят според значението си
     *
     * Таговете, които се поддържат са:
     *
     * o [b]...[/b],
     * [i]...[/i],
     * [u]...[/u],
     * [h1-4]...[/h1-4]
     * [hr] - както съответните HTML тагове
     * o [strike]...[/strike] - задраскан текст
     * o [color=#XXX]...[/color] - цвят на текста
     * o [bg=#XXX]...[/bg] - цвят на фона
     * o [img{=caption}]url[/img] - изображение с опционално заглавие
     * o [code{=syntax}]...[/code] - преформатиран текст с опционално езиково оцветяване
     * o [em={code}] - емотикони
     *
     * @param string $richtext
     * @return string
     */
    function toHtml($html)
    {
        if (!strlen($html)) return "";
        
        $textMode = Mode::get('text');

        if(!$textMode) {
            $textMode = 'html';
        }
        
//        $md5 = md5($html) . $textMode;

        // if($ret = core_Cache::get(RICHTEXT_CACHE_TYPE, $md5, 1000)) {
        //     return $ret;
        // }
        
        // Място, където съхраняваме нещата за субституция
        $this->_htmlBoard = array();
        
        // Уникален маркер, който ще се използва за временните плейсхолдери
        $this->randMark = rand(1, 2000000000);
        
        // Задаваме достатъчно голям буфер за обработка на регулярните изрази
        ini_set('pcre.backtrack_limit', '2M');
        
        // Обработваме [html] ... [/html] елементите, които могат да съдържат чист HTML код
        $html = preg_replace_callback("/\[html](.*?)\[\/html\]([\r\n]{0,2})/is", array($this, '_catchHtml'), $html);
        
        // Премахваме всичкото останало HTML форматиране
        $html = str_replace(array("&", "<"), array("&amp;", "&lt;"), $html);
        
        $html = core_ET::escape($html);

		if(count($this->_htmlBoard)) {
			foreach($this->_htmlBoard as $place => $cnt) {
				$replaceFrom[] = core_ET::escape("[#$place#]");
				$replaceTo[] = "[#$place#]";
			}
			
			// Възстановяваме началното състояние
			$html = str_replace($replaceFrom, $replaceTo, $html);
		}

        // Даваме възможност други да правят обработки на текста
        $this->invoke('BeforeCatchRichElements', array(&$html));

        // Обработваме [code=????] ... [/code] елементите, които трябва да съдържат програмен код
        $html = preg_replace_callback("/\[code(=([a-z0-9]{1,32})|)\](.*?)\[\/code\]([\r\n]{0,2})/is", array($this, '_catchCode'), $html);
              
        // Обработваме [img=http://????] ... [/img] елементите, които представят картинки с надписи под тях
        $html = preg_replace_callback("/\[img(=([^#][^\]]*)|)\](.*?)\[\/img\]/is", array($this, '_catchImage'), $html);
        
        // Обработваме [gread=http://????] ... [/gread] елементите, които ифрейм на google read
        $html = preg_replace_callback("/\[gread(=([^\]]*)|)\](.*?)\[\/gread\]/is", array($this, '_catchGread'), $html);
        
        // Обработваме [link=http://????] ... [/link] елементите, които представляват описания на хипервръзки
        $html = preg_replace_callback("/\[link(=([^\]]*)|)\](.*?)\[\/link\]/is", array($this, '_catchLink'), $html);
        
        // Обработваме [hide=caption] ... [/hide] елементите, които скриват/откриват текст
        $html = preg_replace_callback("/\[hide(=([^\]]*)|)\](.*?)\[\/hide\]/is", array($this, '_catchHide'), $html);
        
        // Обработваме едноредовите кодове: стрингове
        $html = preg_replace_callback("/(?'ap'\`)(?'text'.{1," . static::ONE_LINE_CODE_LENGTH . "}?)(\k<ap>)/u", array($this, '_catchOneLineCode'), $html);
        
        // H!..6
        $html = preg_replace_callback("/\[h([1-6])\](.*?)\[\/h[1-6]\]([\r\n]{0,2})/is", array($this, '_catchHeaders'), $html);
        
        // Даваме възможност други да правят обработки на текста
        $this->invoke('AfterCatchRichElements', array(&$html));

        
        // Обработваме имейлите, зададени в явен вид
        $html = preg_replace_callback("/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i", array($this, '_catchEmails'), $html);

        
        // Вземаме шаблона за намиране на текста, който ще се болдва
        $patternBold = static::getRichTextPatternForBold();
        
        // Ако има шаблон
        if ($patternBold) {
            
            // Търсим в шаблона
            $html = preg_replace_callback($patternBold, array($this, '_catchBold'), $html);   
        }
        
        $html = $this->replaceTags($html);   

        // Обработваме елементите [color=????]  
        $html = preg_replace_callback("/\[color(=([^\]]*)|)\]\s*/si", array($this, '_catchColor'), $html);
        
        // Обработваме елементите [bg=????]  
        $html = preg_replace_callback("/\[bg(=([^\]]*)|)\]\s*/si", array($this, '_catchBg'), $html);
        
        // Поставяме емотиконите на местата с елемента [em=????]
        $html = preg_replace_callback("/\[em(=([^\]]+)|)\]/is", array($this, '_catchEmoticons'), $html);


        // Обработваме елемента [li]
        $html = preg_replace_callback("/\[li](.*?)((<br>)|(\n)|($))/is", array($this, '_catchLi'), $html);
        
        // Обработваме [bQuote=????] ... [/bQuote] елементите, които трябва да съдържат програмен код
        $html = preg_replace_callback("/\[bQuote(=([a-zA-Z0-9]+))?\](.*?)\[\/bQuote\]/s", array($this, '_catchBQuote'), $html);
        $from = array("[bQuote]", "[/bQuote]");
        if(!Mode::is('text', 'plain')) {
            $to = array("<div class='richtext-quote'>", "</div>");
        } else {
            $to = array("", "");
        }
        $html = str_replace($from, $to, $html);
        
        // Обработваме хипервръзките, зададени в явен вид
        $html = preg_replace_callback(static::getUrlPattern(), array($this, '_catchUrls'), $html);
       
        if(!Mode::is('text', 'plain')) {
            
            // Заменяме обикновените интервали в началото на всеки ред, с непрекъсваеми такива
            $newLine = TRUE;
            $sp = "";
          
            for($i = 0; $i<strlen($html); $i++) {
                
                $c = substr($html, $i, 1);
                
                if ($c == "\n") {
                    $newLine = TRUE;
                } else {
                    if ($c == " ") {
                        $c = $newLine ? ("&nbsp;") : (" ");
                    } else {
                        $newLine = FALSE;
                    }
                }
                $out .= $c;
            }
            
            $st1 = '';
            
            $out = str_replace(array(
                "\n&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br>\n",
                "\n&nbsp;&nbsp;&nbsp;&nbsp;<br>\n",
                "\n&nbsp;&nbsp;&nbsp;<br>\n", 
                "\n&nbsp;&nbsp;<br>\n", 
                "\n&nbsp;<br>\n"), 
                array("\n<br>\n", 
                      "\n<br>\n", 
                      "\n<br>\n",
                      "\n<br>\n",
                      "\n<br>\n"), $out);

            $lines = explode("<br>\n", $out);
            $empty = 0;
            
            foreach($lines as $l) {
                if(trim($l)) {
                    $empty = 0;
                } else {
                    $empty++;
                }
                
                if($empty <2) {
                    $st1 .= $l . "<br>\n";
                }
            }
            
            $html = $st1;
            
            $html = str_replace(array('<b></b>', '<i></i>', '<u></u>'), array('', '', ''), $html);
        }
        
        if(!Mode::is('text', 'plain')) {
            $html =  new ET("<div class=\"richtext\">{$html}</div>");
        } else {
            $html =  new ET($html);
        }

        // Подготовка и заместване на плейсхолдерите
        foreach($this->_htmlBoard as $place => $text) {
            $this->_htmlBoard[$place] = new ET($text);
        }
 
        if(count($this->_htmlBoard)) {
           $html->placeArray($this->_htmlBoard);
           $html->placeArray($this->_htmlBoard);
        }
        
        // Ако инстанция на core_ET
        if ($html instanceof core_ET) {
            
            // Вземаме съдържанието
            $cHtml = $html->getContent();
        }
        
        // Хифенира текста
        $this->invoke('AfterToHtml', array(&$cHtml));
        
        // Ако е инстанция на core_ET
        if ($html instanceof core_ET) {
            
            // Променяме съдържанието
            $html->setContent($cHtml);
        } else {
            $html = $cHtml;
        }
        
        // core_Cache::set(RICHTEXT_CACHE_TYPE, $md5, $html, 1000);
        
        return $html;
    }


    function replaceTags($html)
    {
        // Нормализираме знаците за край на ред и обработваме елементите без параметри
        $from = array("\r\n", "\n\r", "\r", "\n", "\t", '[/color]', '[/bg]', '[b]', '[/b]', '[u]', '[/u]', '[i]', '[/i]', '[hr]', '[ul]', '[/ul]', '[ol]', '[/ol]', '[bInfo]', '[/bInfo]', '[bTip]', '[/bTip]', '[bOk]', '[/bOk]', '[bWarn]', '[/bWarn]', '[bQuestion]', '[/bQuestion]', '[bError]', '[/bError]', '[bText]', '[/bText]',); 
        // '[table]', '[/table]', '[tr]', '[/tr]', '[td]', '[/td]', '[th]', '[/th]');
        
        $textMode = Mode::get('text');
        
        if($textMode != 'plain') { 
            $to = array("\n", "\n", "\n", "<br>\n", "<span style='padding-left:3em'></span>", '</span>', '</span>', '<b>', '</b>', '<u>', '</u>', '<i>', '</i>', '<hr>', '<ul>', '</ul>', '<ol>', '</ol>', '<div class="richtext-info">', '</div>' , '<div class="richtext-tip">', '</div>' , '<div class="richtext-success">', '</div>', '<div class="richtext-warning">', '</div>', '<div class="richtext-question">', '</div>', '<div class="richtext-error">', '</div>', '<div class="richtext-text">', '</div>',);
               // '[table>', '[/table>', '[tr>', '[/tr>', '[td>', '[/td>', '[th>', '[/th>');
        } elseif(Mode::is('ClearFormat')) {
           $to   = array("\n",   "\n",   "\n",  "\n", "    ", '',  '',  '',  '',  '',  '',  '',  '', "\n", '', '', '', '', "\n", "\n" , "\n", "\n", "\n", "\n" , "\n", "\n", "\n", "\n" , "\n", "\n", "\n", "\n",);
            // "", "", "\n", "\n", "\t", ' ', "\t", ' ');
        } else {
            $to   = array("\n",   "\n",   "\n",  "\n", "    ", '',  '',  '*',  '*',  '',  '',  '',  '', str_repeat('_', 84), '', '', '', '', "\n", "\n" , "\n", "\n", "\n", "\n" , "\n", "\n", "\n", "\n" , "\n", "\n", "\n", "\n",);
            // "", "", "\n", "\n", "\t", ' ', "\t", ' ');
        }

        $html = str_replace($from, $to, $html);

        return $html;
    }
    
    
    /**
     * Връща шаблона за намиране на URL
     * 
     * @return pattern $urlPattern;
     */
    static function getUrlPattern()
    {
//        $rexProtocol = '(https?://)?';
//        $rexDomain   = '((?:[-a-zA-Z0-9]{1,63}\.)+[-a-zA-Z0-9]{2,63}|(?:[0-9]{1,3}\.){3}[0-9]{1,3})';
//        $rexPort     = '(:[0-9]{1,5})?';
//        $rexPath     = '(/[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]*?)?';
//        $rexQuery    = '(\?[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]+?)?';
//        $rexFragment = '(#[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]+?)?';
//        $urlPattern = "&\\b({$rexProtocol}{$rexDomain}{$rexPort}{$rexPath}{$rexQuery}{$rexFragment}(?=[?.!,;:\"]?(\s|$)))&";
        
        $urlPattern = "/(((http(s?)|ftp(s?)):\/\/)|(www\.))([^\s<>]+)/";
        
        return $urlPattern;
    }
    
    
    /**
     * Връща уникален стринг, който се използва за име на плейсхолдер
     */
    function getPlace()
    {
        return 'richText' . $this->randMark++;
    }
    
    
    /**
     * Обработва [html] ... [/html]
     */
    function _catchHtml($match)
    {
        if(Mode::is('text', 'plain')) {
            $res = html2text_Converter::toRichText($match[1]);
        } else {
            $place = $this->getPlace();
            $this->_htmlBoard[$place] = $match[1];
            $res = "[#{$place}#]";
			$this->_htmlBoard['html1'] = TRUE;
        }

		return $res;
    }
    
    
    /**
     * Заменя [html] ... [/html]
     */
    function _catchLi($match)
    {
        $text = $match[1];
        
        if(!Mode::is('text', 'plain')) {
            $res = "<li>$text</li>\n";
        } else {
            $res = " o {$text}\n";
        }
        
        return $res;
    }
    
    
    /**
     * Шаблон за вкарване даден текст, в richText [b] [/b] тагове
     * нов ред или начало на текст и/или интервали един от текстовете RICHTEXT_BOLD_TEXT две точки интервал произволен текст нов ред или край на текст
     * 
     */
    static function getRichTextPatternForBold()
    {
        // Ако не е сетнат шаблона
        if (!isset(static::$boldPattern)) {
            
            // Разбиваме текстовете на масив
            $boldTextTypeArr = type_Set::toArray(RICHTEXT_BOLD_TEXT);
            
            // Обхождаме масива
            foreach ($boldTextTypeArr as $boldTextType) {
                
                // Ако е празен стринг прескачаме
                if (!($boldTextType = trim($boldTextType))) continue;
                
                // Ескейпваме текста
                $boldTextType = preg_quote($boldTextType, '/');
                
                // Добавяме към шаблона
                $boldTextPattern .= ($boldTextPattern) ? '|' . $boldTextType : $boldTextType;
            }
            
            // Ако има текст за шаблона
            if ($boldTextPattern) {
                
                // Добавяме текста в шаблона
                static::$boldPattern = "/(?'begin'([\r\n]|^){1}[\ \t]*){1}(?'text'(?'leftText'({$boldTextPattern}))(?'sign'\:\ )(?'rightText'[^\r|^\n|^$]+))/ui";    
            } else {
                
                // Добавяме FALSE, за да не се опитваме да го определим пак
                static::$boldPattern = FALSE;
            }
        }
        
        // Връщаме резултата
        return static::$boldPattern;
    }
    
    
    /**
     * Вкарва текста който е в следната последователност: 
     * \n и/или интервали \n или в началото[Главна буква][една или повече малки букви и или интервали и или големи букви]:[интервал][произволен текст]\n или край на текста
     * в болд таг на richText
     */
    function _catchBold($match)
    {
        $res = $match['begin'] . '[b]' . $match['text'] . '[/b]';
        
        return $res;
    }
    
    
    /**
     * Заменя [img=????] ... [/img]
     */
    function _catchImage($match)
    {
        $place = $this->getPlace();
        $url = core_Url::escape($match[2]);
        
        $title = htmlentities($match[3], ENT_COMPAT, 'UTF-8');
        
        $this->_htmlBoard[$place] = "<div><img src=\"{$url}\" alt=\"{$title}\"><br><small>";
        
        return "[#{$place}#]{$title} </small></div>";
    }
    
    
    /**
     * Заменя [gread=????] ... [/gread]
     */
    function _catchGread($match)
    {
        $place = $this->getPlace();
        $url = urlencode(core_Url::escape($match[2]));
        
        $title = htmlentities($match[3], ENT_COMPAT, 'UTF-8');
        
        $this->_htmlBoard[$place] = "<div><iframe src=\"//docs.google.com/gview?url={$url}&embedded=true\" style=\"width:600px; height:500px;\" frameborder=\"0\"></iframe><br><small>";
        
        return "[#{$place}#]{$title}</small></div>";
    }
    
    
    /**
     * Заменя елемента [code=???] .... [/code]
     */
    function _catchCode($match)
    {
        $place = $this->getPlace();
        $code = $match[3];
        
        $code = str_replace("\r\n", "\n", $code);

        if($code{0} == "\n") {
            $code = substr($code, 1);
        }

        if(substr($code, -1) == "\n") {
            $code = substr($code, 0, strlen($code) - 1);
        }

        if(!trim($code)) return "";
        $lg = $match[2];

        if($lg && $lg != 'text') {
            if ($lg != 'auto') {
                $classLg = " {$lg}";
            }
            $code1 = "<pre class='rich-text code{$classLg}'><code>" . rtrim($code) . "</code></pre>"; 
        } else {
            return "<pre class='rich-text'>" . rtrim($code) . "</pre>";
        }
        
        $this->_htmlBoard[$place] = $code1;
        
        // Инвокваме кода за highlight
        $this->invoke('AfterHighLightCode');
        
        return "[#{$place}#]";
    }
    

	/**
     * Заменя елемента [bQuote=???] .... [/bQuote]
     */
    function _catchBQuote($match)
    {
        // Мястото
        $place = $this->getPlace();
        
        // Цитата
        $quote = $match[3];
        
        // Тримваме цитата
        $quote = trim($quote);
        
        // Ако няма цитата, връщаме
        if(!strlen($quote)) return "";
        
        // Манипулатора на файла
        $docHnd = $match[2];
        
        // Ако сме в текстов режим
        if (Mode::is('text', 'plain')) {
            
            // Стринга за цитата
            $quoteStr = "  > ";
            
            // Добавяме в начлоато на всеки ред стринга за цитат
            $quote = str_ireplace(array( "\r\n", "\n\r", "\n"), array("\r\n{$quoteStr}", "\n\r{$quoteStr}", "\n{$quoteStr}"), $quote);
            $quote = "\n{$quoteStr}" . $quote; 
        } else {
            
            // Добавяме в цитата, ако не сме в текстов режим
            $quote = "<div class='richtext-quote'>" . $quote . "</div>";
        }
        
        // Ако има манипулатор на документа
        if ($docHnd) {
            
            // Извикваме функцията
            $this->invoke('getInfoFromDocHandle', array(&$dInfo, $docHnd));
            
            // Датата
            $date = $dInfo['date'];
            
            // Ако има имейл
            if ($dInfo['authorEmail']) {
                
                // Инстанция на имейка
                $emailInst = cls::get('type_Email');
                
                // Вземаме вербалния имейл
                $dInfo['authorEmail'] = $emailInst->toVerbal($dInfo['authorEmail']);
            }
            
            // Определяме автора
            $author = ($dInfo['authorEmail']) ? $dInfo['authorEmail'] : $dInfo['author'];
            
            // Ако има дата
            if ($date) {
                
                // Добавяме в стринга
                $authorInfo = $date . " ";
            }
            
            // Ако има автор
            if ($author) {
                
                // Добавяме автора в стринга
                $authorInfo .= "&lt;{$author}&gt;";
            }
            
            // Ако има информация за автора
            if ($authorInfo) {
                
                // Ако сме в текстов режим
                if (Mode::is('text', 'plain')) {
                    
                    // Добавяме към цитата автора и дата
                    $quote = $authorInfo . $quote; 
                } else {
                    
                    // Автора и датата
                    $authorInfo = "<div class='quote-title'>{$authorInfo}</div>";
                    
                    // Добавяме информация за автора
                    $quote = $authorInfo . $quote;
                }
            }
        }
        
        return $quote;
    }
    
    
	/**
     * За едноредови коментари между апострофите
     */
    function _catchOneLineCode($match)
    {
        // Ако има част от плейсхолдер
        // За да не се вкарва в инлайн блоковите елементи
        if (strpos($match['text'], '#')) {
            
            // Обхождаме масива с дъските
            foreach ((array)$this->_htmlBoard as $htmlBoard => $dummy) {
                
                // Вземаме плейсхолдера
                $placeBoard = core_ET::toPlace($htmlBoard);
                
                // Ако се съдржа в текста
                if (strpos($match['text'], $placeBoard) !== FALSE) {
                    
                    // Връщаме текст
                    return $match[0];
                }
            }
        }
        
        // Мястото
        $place = $this->getPlace();
        
        // Кода между апострофите
        $code = $match['text'];
        
        // Ако е празен стринг
        if(!($code = trim($code))) return $match[0];
        
        // Добавяме кода в блок
        $code1 = "<span class='oneLineCode'>{$code}</span>";
        
        // Доабавяме в масива
        $this->_htmlBoard[$place] = $code1;
        
        return "[#{$place}#]";
    }
    
    
    /**
     * Заменя елементите [link=?????]......[/link]
     */
    function _catchLink($match)
    {
        $place = $this->getPlace();
        $title = $match[3];
        
        // URL' то 
        $url = trim($match[2]);
        
        // Ако сме в текстов режим
        if (Mode::is('text', 'plain')) {
            
            // Изчистваме празните интервали в началото и края
            $title = trim($title);
            
            // В зависимост от това дали имаме заглавие на линка, определяме текста
            if(substr($title, 0, 1) == '[' && substr($title, -1) == ']') {
                $text = $title;
            } else {
                $text = ($title)? "({$title}) - {$url}" : $url;
            }
            
            return $text;
        }
        
        // Ако имаме само http:// значи линка е празен
        if($url == 'http://' || $url == 'https://') {
            $url = '';
        }
        
        // Ако нямаме схема на URL-то
        if(!preg_match("/^[a-z0-9]{0,12}\:\/\//i", $url) ) {
            if($url{0} == '/') {                
                $httpBoot = getBoot(TRUE);
                if (EF_APP_NAME_FIXED !== TRUE) {
                    $app = Request::get('App');
                    $httpBoot .= '/' . ($app ? $app : EF_APP_NAME);
                }

                $url = $httpBoot . $url;
            } else {
                $url = "http://{$url}";
            }
        }
         
        if(core_Url::isLocal($url, $rest)) {
            $link = $this->internalLink($url, $title, $place, $rest);
            list($url1, $url2) = explode('#', $url, 2);
            if($url2) {
                $url2 = str::canonize($url2);
                $url = $url1 . '#' . $url2;
            } else {
                $url = $url1; 
            }
        } else {
            $link = $this->externalLink($url, $title, $place);
        }
        
        $url = core_Url::escape($url);

        $this->_htmlBoard[$place] = $url;
        
        return $link;
    }
    
    
    /**
     * Конвертира към HTML елементите [link=...]...[/link], сочещи към вътрешни URL
     * 
     * @param string $url URL, къдетo трябва да сочи връзката
     * @param string $text текст под връзката
     * @param string $place
     * @return string HTML елемент <a href="...">...</a>
     */
    public function internalLink_($url, $title, $place, $rest)
    {
        $link = "<a href=\"[#{$place}#]\">{$title}</a>";

        return $link;
    }


    /**
     * Конвертира към HTML елементите [link=...]...[/link], сочещи към външни URL
     * 
     * Може да бъде прихванат в плъгин на `type_Richtext` с on_AfterExternalLink()
     * 
     * @param string $url URL, къдетo трябва да сочи връзката
     * @param string $text текст под връзката
     * @param string $place
     * @return string HTML елемент <a href="...">...</a>
     */
    public function externalLink_($url, $title, $place)
    {
        $titlePlace = $this->getPlace();
        
        // Парсираме URL' то 
        $urlArr = @parse_url($url);
        
        // Домейна
        $domain = $urlArr['host'];

        // Ако няма заглавие
        if (!trim($title)) {
            
            // Използваме домейна за заглавие
            $this->_htmlBoard[$titlePlace] = $domain;
            $title = $domain;
        } else {
            // Правим обработка на елементите, които може да са вътре в линка
            $title = $this->replaceTags($title);
            $title = str_replace(
                array('[h1]', '[h2]', '[h3]', '[h4]', '[h5]', '[h6]', '[/h1]', '[/h2]', '[/h3]', '[/h4]', '[/h5]', '[/h6]'), 
                array('<b>', '<b>', '<b>', '<b>', '<b>', '<b>', '</b>', '</b>', '</b>', '</b>', '</b>', '</b>'), 
                $title);
            // Обработваме [img=http://????] ... [/img] елементите, които представят картинки с надписи под тях
            $title = preg_replace_callback("/\[img(=([^#][^\]]*)|)\](.*?)\[\/img\]/is", array($this, '_catchImage'), $title);

            $this->_htmlBoard[$titlePlace] = $title;    
        }
            
        if($title{0} != ' ') {
            
            $bgPlace = $this->getPlace();
            $thumb = new img_Thumb("http://www.google.com/s2/u/0/favicons?domain={$domain}", 16, 16, 'url');
            $iconUrl = $thumb->getUrl();
            $this->_htmlBoard[$bgPlace] = "background-image:url('{$iconUrl}');";

            $link = "<a href=\"[#{$place}#]\" target=\"_blank\" class=\"out linkWithIcon\" style=\"[#{$bgPlace}#]\">[#{$titlePlace}#]</a>";  

        } else {
            $link = "<a href=\"[#{$place}#]\" target=\"_blank\" class=\"out\">[#{$titlePlace}#]</a>";
        }
        
        return $link;
    }


    /**
     * Заменя елементите [hide=?????]......[/hide]
     */
    function _catchHide($match)
    {
        $place = $this->getPlace();
        $text = trim($match[3]);
        $title = $match[2];

        if(Mode::is('text', 'plain')) {
            
            return "\n{$title}\n{$text}";
        }

        $id = 'hide' . rand(1, 1000000);
        
        $html = "<a href=\"javascript:toggleDisplay('{$id}')\"  style=\"font-weight:bold; background-image:url(" . sbf('img/16/plus.png', "'") . ");\" 
                   class=\"linkWithIcon\">{$title}</a><div class='clearfix21 richtextHide' id='{$id}'>";
        
        $this->_htmlBoard[$place] =  $html;
        
        return "[#{$place}#]{$text}</div>";
    }
    
    
    /**
     * Замества [color=????] елементите
     */
    function _catchColor($match)
    {
        $color = parent::escape($match[2]);
        
        if(!$color) $color = 'black';
        
        return "<span style=\"color:{$color}\">";
    }
    
    
    /**
     * Замества [bg=????] елементите
     */
    function _catchBg($match)
    {
        $color = parent::escape($match[2]);
        
        if(!$color) $color = 'black';
        
        return "<span style=\"background-color:{$color}\">";
    }
    
    
    /**
     * Замества [em=????] елементите
     */
    function _catchEmoticons($match)
    {
        $em = type_Varchar::escape($match[2]);
        
        if(Mode::is('text', 'xhtml')) {
            $iconFile = sbf("img/em15/em.icon.{$em}.gif", '"', TRUE);
            $res = "<img src={$iconFile} style='margin-left:1px; margin-right:1px;' height=15 width=15/>";
        } elseif(Mode::is('text', 'plain')) {
            $res = self::$emoticons[$em];
        } else {
            $iconFile = sbf("img/em15/em.icon.{$em}.gif");
            $res = "<img src={$iconFile} style='margin-left:1px; margin-right:1px;' height=15 width=15/>";
        }
        
        $place = $this->getPlace();
            
        $this->_htmlBoard[$place] = $res;
        
        return "[#{$place}#]";
    }
    
    
    /**
     * Обработва хедъри-те [h1..6] ... [/h..]
     */
    function _catchHeaders($matches)
    { 
        $text  = $matches[2];
        $level = $matches[1];
        
        if(!Mode::is('text', 'plain')) {
            $name = str::canonize($text);
            $res = "<a name=\"{$name}\" class='header'><h{$level}>{$text}</h{$level}></a>";
        } else {
            $res =   mb_strtoupper($text) . "\n" . str_repeat('=', mb_strlen($text)) . "\n";
        }
        
        return $res;
    }
    
    
    /**
     * Прави субституция на хипервръзките
     */
    function _catchUrls($html)
    {   
        $url = rtrim($html[0], ',.;');

        if($tLen = (strlen($html[0]) - strlen($url))) {
            $trim = substr($html[0], 0 - $tLen);
        }
        
        if(!stripos($url, '://') && (stripos($url, 'www.') === 0)) {
            $url = 'http://' . $url;
        }
        
        if(!stripos($url, '://')) return $url;

        if( core_Url::isLocal($url, $rest) ) {
            $result = $this->internalUrl($url, str::limitLen(decodeUrl($url), 120), $rest);
        } else {
            $result = $this->externalUrl($url, str::limitLen(decodeUrl($url), 120));
        }


        return $result . $trim;
    }
    
    
    /**
     * Конвертира вътрешен URL към подходящо HTML представяне.
     * 
     * @param string $url
     * @param string $title
     * @return string HTML елемент <a href="...">...</a>
     */
    public function internalUrl_($url, $title, $rest)
    {
        $link = $url;
        
        if(!Mode::is('text', 'plain')) {
            
            $title = type_Varchar::escape($title);
            
            $link = "<a href=\"{$url}\">{$title}</a>";    
        }
        
        $place = $this->getPlace();
            
        $this->_htmlBoard[$place] = $link;
        
        return "[#{$place}#]";
    }
    

    /**
     * Конвертира въшнен URL към подходящо HTML представяне
     * 
     * @param string $url
     * @param string $title
     * @param string HTML код
     */
    public function externalUrl_($url, $title)
    {   
        $link = $url;
        
        if(!Mode::is('text', 'plain')) {
            
            $title = type_Varchar::escape($title);
            
            $link = "<a href=\"{$url}\" target='_blank' class='out'>{$title}</a>";
        }
        
        $place = $this->getPlace();
            
        $this->_htmlBoard[$place] = $link;
        
        return "[#{$place}#]";
    }


    /**
     * Прави субституция на имейлите
     */
    function _catchEmails($match)
    {
        $email = $match[0];
        
        $emlType = cls::get('type_Email');

        if($emlType->isValidEmail($email)) {
            
            $place = $this->getPlace();
            
            $this->_htmlBoard[$place] = $emlType->toVerbal($email);
            
            return "[#{$place}#]";
        }

        return $email;
    }


    /**
     * Връща масив с html код, съответстващ на бутоните на Richedit компонента
     */
    function getToolbar(&$attr)
    {
        $formId = $attr['id'];
        
        $toolbarArr = new core_ObjectCollection('html,place,order');
        
        // Ако е логнат потребител
        if (core_Users::haveRole('user')) {
        
            $toolbarArr->add("<span class='richtext-relative-group'>", 'TBL_GROUP1');
            
           	$toolbarArr->add("<a class='rtbutton richtext-group-title' title='" . tr('Усмивки') .  "' onclick=\"toggleRichtextGroups('{$attr['id']}-group1', event)\"><img src=" . sbf('img/em15/em.icon.smile.gif') . " height='15' width='15'  alt='smile'></a>", 'TBL_GROUP1');
            
           	$emot1 = 'richtext-holder-group-after';
            
            $toolbarArr->add("<span id='{$attr['id']}-group1' class='richtext-emoticons richtext-holder-group {$emot1}'>", 'TBL_GROUP1');
            
    	        $toolbarArr->add("<a class='rtbutton' title='" . tr('Усмивка') .  "' onclick=\"rp('[em=smile]', document.getElementById('{$formId}'),0)\"><img src=" . sbf('img/em15/em.icon.smile.gif') . " height='15' width='15'  alt='smile'></a>", 'TBL_GROUP1');
    	        
    	        $toolbarArr->add("<a class='rtbutton' title='" . tr('Широка усмивка') .  "' onclick=\"rp('[em=bigsmile]', document.getElementById('{$formId}'),0)\"><img src=" . sbf('img/em15/em.icon.bigsmile.gif') . " height='15' width='15' alt='bigsmile'></a>", 'TBL_GROUP1');
    	        
    	        $toolbarArr->add("<a class='rtbutton' title='" . tr('Супер!') .  "' onclick=\"rp('[em=cool]', document.getElementById('{$formId}'),0)\"><img src=" . sbf('img/em15/em.icon.cool.gif') . " height='15' width='15' alt='cool'></a>", 'TBL_GROUP1');
    	      
    	        $toolbarArr->add("<a class='rtbutton' title='" . tr('Бира') .  "' onclick=\"rp('[em=beer]', document.getElementById('{$formId}'),0)\"><img alt='Бира' src=" . sbf('img/em15/em.icon.beer.gif') . " height='15' width='15'></a>", 'TBL_GROUP1');
    	            
    	        $toolbarArr->add("<a class='rtbutton' title='" . tr('Въпрос?') .  "' onclick=\"rp('[em=question]', document.getElementById('{$formId}'),0)\"><img alt='Въпрос?' src=" . sbf('img/em15/em.icon.question.gif') . " height='15' width='15' ></a>", 'TBL_GROUP1');
    	            
    	        $toolbarArr->add("<a class='rtbutton' title='" . tr('Сърце') .  "' onclick=\"rp('[em=heart]', document.getElementById('{$formId}'),0)\"><img alt='Сърце' src=" . sbf('img/em15/em.icon.heart.gif') . " height='15' width='15'></a>", 'TBL_GROUP1');
    	            
    	        $toolbarArr->add("<a class='rtbutton' title='" . tr('OK') .  "' onclick=\"rp('[em=ok]', document.getElementById('{$formId}'),0)\"><img alt='OK' src=" . sbf('img/em15/em.icon.ok.gif') . " height='15' width='15'></a>", 'TBL_GROUP1');
    	            
    	        $toolbarArr->add("<a class='rtbutton' title='" . tr('Мисля') .  "' onclick=\"rp('[em=think]', document.getElementById('{$formId}'),0)\"><img alt='Мисля' src=" . sbf('img/em15/em.icon.think.gif') . " height='15' width='15'></a>", 'TBL_GROUP1');
            
            $toolbarArr->add("</span>", 'TBL_GROUP1');
            
            $toolbarArr->add("</span>", 'TBL_GROUP1');
            
            
            $toolbarArr->add("<a class=rtbutton style='font-weight:bold;text-indent:1px' title='" . tr('Удебелен текст') .  "' onclick=\"s('[b]', '[/b]', document.getElementById('{$formId}'))\">b</a>", 'TBL_GROUP2');
             
            $toolbarArr->add("<a class=rtbutton style='font-style:italic;text-indent:2px;' title='" . tr('Наклонен текст') .  "' onclick=\"s('[i]', '[/i]', document.getElementById('{$formId}'))\">i</a>", 'TBL_GROUP2');
             
            $toolbarArr->add("<a class=rtbutton style='text-decoration:underline;text-indent:2px;' title='" . tr('Подчертан текст') .  "' onclick=\"s('[u]', '[/u]', document.getElementById('{$formId}'))\">u</a>", 'TBL_GROUP2');
            
            
            $toolbarArr->add("<span class='richtext-relative-group'>", 'TBL_GROUP2');
            
            $toolbarArr->add("<a class='rtbutton richtext-group-title' style='font-weight:bold; color:blue' title='" . tr('Цвят на буквите') .  "' onclick=\"toggleRichtextGroups('{$attr['id']}-group2', event)\">А</a>", 'TBL_GROUP2');
            
            $emot2 = 'richtext-holder-group-after';
            
            $toolbarArr->add("<span id='{$attr['id']}-group2' class='richtext-emoticons2 richtext-holder-group {$emot2}'>", 'TBL_GROUP2');
    	        
    		   	$toolbarArr->add("<a class=rtbutton style='font-weight:bold; color:blue' title='" . tr('Сини букви') .  "' onclick=\"s('[color=blue]', '[/color]', document.getElementById('{$formId}'))\">A</a>", 'TBL_GROUP2');
    		        
    		    $toolbarArr->add("<a class=rtbutton style='font-weight:bold; color:red' title='" . tr('Червени букви') .  "' onclick=\"s('[color=red]', '[/color]', document.getElementById('{$formId}'))\">A</a>", 'TBL_GROUP2');
    		    
    		    $toolbarArr->add("<a class=rtbutton style='font-weight:bold; color:green' title='" . tr('Зелени букви') .  "' onclick=\"s('[color=green]', '[/color]', document.getElementById('{$formId}'))\">A</a>", 'TBL_GROUP2');
    		     
    		    $toolbarArr->add("<a class=rtbutton style='font-weight:bold; color:#888' title='" . tr('Сиви букви') .  "' onclick=\"s('[color=#888]', '[/color]', document.getElementById('{$formId}'))\">A</a>", 'TBL_GROUP2');
    		        
            $toolbarArr->add("</span>", 'TBL_GROUP2');
            
            $toolbarArr->add("</span>", 'TBL_GROUP2');
            
            
            $toolbarArr->add("<span class='richtext-relative-group'>", 'TBL_GROUP2');
            
            $toolbarArr->add("<a class=rtbutton style='font-weight:bold; background: yellow;' title='" . tr('Цвят на фона') .  "' onclick=\"toggleRichtextGroups('{$attr['id']}-group3', event)\">A</a>", 'TBL_GROUP2');
            
            $emot3 = 'richtext-holder-group-after';
            
            $toolbarArr->add("<span id='{$attr['id']}-group3' class='richtext-emoticons3 richtext-holder-group {$emot3}'>", 'TBL_GROUP2');
            
    	        $toolbarArr->add("<a class=rtbutton style='font-weight:bold; background: yellow;' title='" . tr('Жълт фон') .  "' onclick=\"s('[bg=yellow]', '[/bg]', document.getElementById('{$formId}'))\">A</a>", 'TBL_GROUP2');
    	                
    	        $toolbarArr->add("<a class=rtbutton style='font-weight:bold; background: lightgreen;' title='" . tr('Зелен фон') .  "' onclick=\"s('[bg=lightgreen]', '[/bg]', document.getElementById('{$formId}'))\">A</a>", 'TBL_GROUP2');
    	        
    	        $toolbarArr->add("<a class=rtbutton style='font-weight:bold; background: red; color: white' title='" . tr('Червен фон') .  "' onclick=\"s('[bg=red][color=white]', '[/color][/bg]', document.getElementById('{$formId}'))\">A</a>", 'TBL_GROUP2');
    	        
    	        $toolbarArr->add("<a class=rtbutton style='font-weight:bold; background: black; color: white' title='" . tr('Черен фон') .  "' onclick=\"s('[bg=black][color=white]', '[/color][/bg]', document.getElementById('{$formId}'))\">A</a>", 'TBL_GROUP2');
            
            $toolbarArr->add("</span>", 'TBL_GROUP2');
            
            $toolbarArr->add("</span>", 'TBL_GROUP2');
            
            
            $toolbarArr->add("<span class='richtext-relative-group'>", 'TBL_GROUP2');
            
            $toolbarArr->add("<a class='rtbutton richtext-group-title' title='" . tr('Заглавия') . "' onclick=\"toggleRichtextGroups('{$attr['id']}-group4', event)\">H</a>", 'TBL_GROUP2');
            
            $emot4 = 'richtext-holder-group-after';
            
            $toolbarArr->add("<span id='{$attr['id']}-group4' class='richtext-emoticons4 richtext-holder-group {$emot4}'>", 'TBL_GROUP2');
    	        
    	        $toolbarArr->add("<a class=rtbutton title='" . tr('Заглавие') . " 1" .  "' onclick=\"s('[h1]', '[/h1]', document.getElementById('{$formId}'),1)\">H1</a>", 'TBL_GROUP2');
    	         
    	        $toolbarArr->add("<a class=rtbutton title='" . tr('Заглавие') . " 2" .  "' onclick=\"s('[h2]', '[/h2]', document.getElementById('{$formId}'),1)\">H2</a>", 'TBL_GROUP2');
    	         
    	        $toolbarArr->add("<a class=rtbutton title='" . tr('Заглавие') . " 3" .  "' onclick=\"s('[h3]', '[/h3]', document.getElementById('{$formId}'),1)\">H3</a>", 'TBL_GROUP2');
    	         
    	        $toolbarArr->add("<a class=rtbutton title='" . tr('Заглавие') . " 4" .  "' onclick=\"s('[h4]', '[/h4]', document.getElementById('{$formId}'),1)\">H4</a>", 'TBL_GROUP2');
    	        
    	        $toolbarArr->add("<a class=rtbutton title='" . tr('Заглавие') . " 5" .  "' onclick=\"s('[h5]', '[/h5]', document.getElementById('{$formId}'),1)\">H5</a>", 'TBL_GROUP2');
    	        
    	        $toolbarArr->add("<a class=rtbutton title='" . tr('Заглавие') . " 6" .  "' onclick=\"s('[h6]', '[/h6]', document.getElementById('{$formId}'),1)\">H6</a>", 'TBL_GROUP2');
    	         
    	        $toolbarArr->add("</span>", 'TBL_GROUP2');
            
            $toolbarArr->add("</span>", 'TBL_GROUP2');
            
            
            $toolbarArr->add("<a class=rtbutton  title='" . tr('Списък') .  "' onclick=\"rp('[li] ', document.getElementById('{$formId}'), 1)\">&#9679</a>", 'TBL_GROUP2');
             
            
            $toolbarArr->add("<span class='richtext-relative-group'>", 'TBL_GROUP2');
            
            $toolbarArr->add("<a class='rtbutton richtext-group-title' title='" . tr('Блок') .  "' onclick=\"toggleRichtextGroups('{$attr['id']}-group5', event)\">" . tr('Блок') . "</a>", 'TBL_GROUP2');
            
            $emot5 = 'richtext-holder-group-after';
            
            $toolbarArr->add("<span id='{$attr['id']}-group5' class='richtext-emoticons5 richtext-holder-group {$emot5}'>", 'TBL_GROUP2');
             
            	$toolbarArr->add("<a class=rtbutton title='" . tr("Код") . "' onclick=\"s('[code=auto]', '[/code]', document.getElementById('{$formId}'),1,1," . static::ONE_LINE_CODE_LENGTH . ")\"><img src=" . sbf('img/16/script_code_red.png') . " height='15' width='15'/></a>", 'TBL_GROUP2');
            	
            	$toolbarArr->add("<a class=rtbutton title='" . tr('Цитат') .  "' onclick=\"s('[bQuote]', '[/bQuote]', document.getElementById('{$formId}'),1)\"><img src=" . sbf('img/16/quote.png') . " height='15' width='15'/></a>", 'TBL_GROUP2');
            	
    	        $toolbarArr->add("<a class=rtbutton title='" . tr('Грешка') .  "' onclick=\"s('[bError]', '[/bError]', document.getElementById('{$formId}'),1)\"><img src=" . sbf('img/dialog_error-small.png') . " height='15' width='15'/></a>", 'TBL_GROUP2'); 
    	        
    	        $toolbarArr->add("<a class=rtbutton title='" . tr('Успех') .  "' onclick=\"s('[bOk]', '[/bOk]', document.getElementById('{$formId}'),1)\"><img src=" . sbf('img/ok-small.png') . " height='15' width='15'  align='top'/></a>", 'TBL_GROUP2');
    	
    	        $toolbarArr->add("<a class=rtbutton title='" . tr('Съвет') .  "' onclick=\"s('[bTip]', '[/bTip]', document.getElementById('{$formId}'),1)\"><img src=" . sbf('img/App-tip-icon3-small.png') . " height='15' width='15'  align='top'/></a>", 'TBL_GROUP2');
    	         
    	        $toolbarArr->add("<a class=rtbutton title='" . tr('Информация') .  "' onclick=\"s('[bInfo]', '[/bInfo]', document.getElementById('{$formId}'),1)\"><img src=" . sbf('img/info_blue-small.png') . " height='15' width='15'  align='top'/></a>", 'TBL_GROUP2');
    	       
    	        $toolbarArr->add("<a class=rtbutton title='" . tr('Предупреждение') .  "' onclick=\"s('[bWarn]', '[/bWarn]', document.getElementById('{$formId}'),1)\"><img src=" . sbf('img/dialog_warning-small.png') . " height='15' width='15'  align='top'/></a>", 'TBL_GROUP2');
    	
    	        $toolbarArr->add("<a class=rtbutton title='" . tr('Въпрос') .  "' onclick=\"s('[bQuestion]', '[/bQuestion]', document.getElementById('{$formId}'),1)\"><img src=" . sbf('img/Help-icon-small.png') . " height='15' width='15'  align='top'/></a>", 'TBL_GROUP2');
            	        
    	        $toolbarArr->add("<a class=rtbutton title='" . tr('Неномериран списък') .  "' onclick=\"s('[ul]', '[/ul]', document.getElementById('{$formId}'),1,1)\"><img src=" . sbf('img/16/ul.png') . " height='15' width='15'  align='top'/></a>", 'TBL_GROUP2');
    	        
    	        $toolbarArr->add("<a class=rtbutton title='" . tr('Номериран списък') .  "' onclick=\"s('[ol]', '[/ol]', document.getElementById('{$formId}'),1,1)\"><img src=" . sbf('img/16/ol.png') . " height='15' width='15'  align='top'/></a>", 'TBL_GROUP2');
    	         
    	    $toolbarArr->add("</span>", 'TBL_GROUP2');
            
            $toolbarArr->add("</span>", 'TBL_GROUP2');
            
            
            $toolbarArr->add("<span class='richtext-relative-group'>", 'TBL_GROUP3');
            
            $toolbarArr->add("<a class='rtbutton richtext-group-title'  style='margin-left:4px;' title='" . tr('Добавяне на файлове/документи') .  "' onclick=\"toggleRichtextGroups('{$attr['id']}-group6', event);\">" . tr('Сложи') . "</a>", 'TBL_GROUP3');
            
            $emot6 = 'richtext-holder-group-after';
            
            $toolbarArr->add("<span id='{$attr['id']}-group6' class='richtext-emoticons6 richtext-holder-group {$emot6}'>", 'TBL_GROUP3');
        	
    	        $toolbarArr->add(new ET("[#filesAndDoc#]"), 'TBL_GROUP3');
    	             
    	        $toolbarArr->add("<a class=rtbutton title='" . tr('Черта') .  "' onclick=\"rp('[hr]', document.getElementById('{$formId}'))\">" . tr("Черта") . "</a>", 'filesAndDoc', 1000.045);
    	        
    	        $toolbarArr->add("<a class=rtbutton title='" . tr("Линк") . "' onclick=\"var linkTo = prompt('" . tr("Добавете линк") . "','http://'); if(linkTo) { s('[link=' + linkTo + ']', '[/link]', document.getElementById('{$formId}'))}\">" . tr("Линк") . "</a>", 'filesAndDoc', 1000.075);
            
            $toolbarArr->add("</span>", 'TBL_GROUP3');
            
            $toolbarArr->add("</span><div class='clearfix21'></div>", 'TBL_GROUP3');
        
        } else {
            $toolbarArr->add("<span class='richtext-relative-group simple-toolbar'>", 'TBL_GROUP1');
                $toolbarArr->add(new ET("[#simpleToolbar#]"), 'TBL_GROUP1');
            $toolbarArr->add("</span><div class='clearfix21'></div>", 'TBL_GROUP1');
        }
        
        $this->invoke('AfterGetToolbar', array(&$toolbarArr, &$attr));
        
        return $toolbarArr;
    }
    
    
    /**
     * Парсира вътрешното URL
     * 
     * @param URL $res - Вътрешното URL, което ще парсираме
     * 
     * @return array $params - Масив с парсираното URL
     */
    static function parseInternalUrl($rest)
    {
        $rest = trim($rest, '/');
        
        $restArr = explode('/', $rest);

        $params = array();
        
        $lastPart = $restArr[count($restArr)-1];

        if($lastPart{0} == '?') {
           $lastPart = ltrim($lastPart, '?'); 
           $lastPart = str_replace('&amp;', '&', $lastPart);
           parse_str($lastPart, $params);
           unset($restArr[count($restArr)-1]);
        }

        setIfNot($params['Ctr'], $restArr[0]);
        
        // Ако екшъна е SBF
        if (strtolower($params['Ctr']) == 'sbf') return FALSE;
        
        setIfNot($params['Act'], $restArr[1], 'default');

        if(count($restArr) % 2) {
            setIfNot($params['id'], $restArr[2]);
            $pId = 3;
        } else {
            $pId = 2;
        }
        
        // Добавяме останалите параметри, които са в часта "път"
        while($restArr[$pId]) {
            $params[$restArr[$pId]] = $params[$restArr[$pId+1]];
            $pId++;
        }
        
        // Декодира защитеното id
        if(($id = $params['id']) && ($ctr = $params['Ctr'])) {
            $id = core_Request::unprotectId($id, $ctr);
            $params['id'] = $id;
        }
        
        return $params;
    }
    
    
    /**
     * Съобщението, което ще се показва ако нямаме достъп до обекта
     */
    static function getNotAccessMsg()
    {
        $text = tr('Липсващ обект');
        if (Mode::is('text', 'plain')) {
            
            // 
            $str = $text;
            
        } else {
            // Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
            $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
            
            // Иконата за линка
            $sbfIcon = sbf('img/16/link_break.png','"', $isAbsolute);
            
            // Съобщението
            $str = "<span class='linkWithIcon' style='background-image:url({$sbfIcon});'> {$text} </span>"; 
                
        }
        
        return $str;
    }
}
