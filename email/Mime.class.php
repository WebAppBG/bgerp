<?php 


/**
 * Помощен клас за парсиране на
 *
 *
 * @category  bgerp
 * @package   email
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @see       https://github.com/bgerp/bgerp/issues/115
 */
class email_Mime extends core_BaseClass
{
    
    
    /**
     * Текстоватана имейл-а
     */
    var $textPart;
    
    
    /**
     * Текстовата част на имйела, без да се взема в предвид HTML частта
     */
    var $justTextPart;
    
    
    /**
     * Рейтинг на текстовата част
     */
    var $bestTextRate = 0;
    
    
    /**
     * Индекса на най-подходящата текстова част
     */
    var $bestTextIndex;
    
    
    /**
     * Масив с данни за изпращача
     */
    var $from;
    
    
    /**
     * IP адреса на изпращача
     */
    var $ip;
    
    
    /**
     * Езика на имейл-а
     */
    var $lg;
    
    
    /**
     * Дали имейл-а е спам или не
     */
    var $spam;
    
    
    /**
     * Хеша за проверка на уникалността на имейл-а
     */
    var $hash;
    
    
    /**
     * Масив с id => [име нафайл] - прикачени файлове
     */
    var $attachedFiles = array();
    
    
    /**
     * Масив с cid(name) => id(fileman_Files) - вградени (embedded) файлове
     */
    var $linkedFiles = array();
    
    
    /**
     * Масив с id(fileman_Files) => id(fileman_Files) - cid файлове
     */
    var $cidFiles = array();
    
    
    /**
     * Масив с id(fileman_Files) => [име нафайл] - Файлове от допълнителните части на имейла
     */
    var $partFiles = array();
    
    
    /**
     * Масив със съобщения за грешки по време на парсирането
     */
    var $errors = array();
    
    
    /**
     * Връща хеша, който служи за проверка на уникалността на имейл-а
     * Ако хедъри-те на писмото не са зададени като входен параметър,
     * то те се вземат от вътрешното състояние
     */
    function getHash($headers = NULL)
    {   
        if($this->hash) {
            $hash = $this->hash;
        } elseif ($internalHeaders = $this->getHeadersStr()) {
            $hash = md5($internalHeaders);
            $this->hash = $hash;
        } else {
            expect($headers);
            $hash = md5($headers);
        }
 
        return $hash;
    }
    
    
    /**
     * Връща обект с данните в едно писмо.
     */
    function getEmail($rawEmail)
    {
        $this->parseAll($rawEmail);
        
        // Очакваме, че преди това с метода ->parseAll е парсиран текста на писмото
        expect($this->parts);
        
        $rec = new stdClass();
        
        // Запазваме Message-ID, като премахваме ограждащите скоби
        $rec->messageId = trim($this->getHeader('Message-ID'), '<>');
        
        // Декодираме и запазваме събджекта на писмото
        $rec->subject = $this->getHeader('Subject');
        $rec->subject = str_replace(array("\n\t", "\n"), array('', ''), $rec->subject);
        
        // Извличаме информация за изпращача
        list($rec->fromName, $rec->fromEml) = $this->getFromEmail();
        
        // Опитва се да намари IP адреса на изпращача
        $rec->fromIp = $this->getSenderIp();
        
        // Извличаме информация за получателя (към кого е насочено писмото)
        $rec->toEml = $this->getToEmail();
        
        // Намира вътрешната пощенска кутия, към която е насочено писмото
        $rec->toBox = $this->getToBox();
        
        // Пробваме да определим езика на който е написана текстовата част
        $rec->lg = $this->getLg();
        
        // Определяме датата на писмото
        $rec->date = $this->getDate();
        
        // Опитваме се да определим държавата на изпращача
        $rec->country = $this->getCountry($rec->fromEml, $rec->lg, $rec->fromIp);
        
        // Ако писмото е лошо - връщане на сигнал за грешка
        if($this->isBadMail($rec)) return NULL;
        
        // Обработваме съдържанието и прикачените файлове
        
        // Минаваме по всички текстови и HTML части да ги запишем като прикачени файлове
        // Пропускаме само тази PLAIN TEXT част, която е използване
        foreach($this->parts as $index => $p) {
            if($p->type == 'TEXT') {
                if(($index == $this->bestTextIndex) || (!$p->data)) continue;
                
                if($p->subType == 'HTML') {
                    $p->data = $this->replaceCid($p->data);
                }
                $fileName = $this->getFileName($index);
                $p->filemanId = $this->addFileToFileman($p->data, $fileName);
                
                if($index == $this->firstHtmlIndex) {
                    $this->htmlFile = $p->filemanId;
                } else {
                    $this->partFiles[$p->filemanId] = $fileName;
                }
            }
        }
        
        // Задаваме прикачените файлове като keylist
        $rec->files = type_Keylist::fromArray($this->getJustAttachedFiles());
        
        // Задаваме първата html част като .html файл
        $rec->htmlFile = $this->htmlFile;
        
        // Записваме текста на писмото, като [hash].eml файл
        $emlFileName = $this->getHash() . '.eml';
        $emlFileId = $this->addFileToFileman($this->data, $emlFileName);
        $rec->emlFile = $emlFileId;
        
        // Задаваме текстовата част
        $rec->textPart = $this->textPart;
        
        // Запазване на допълнителни MIME-хедъри за нуждите на рутирането
        $rec->inReplyTo      = $this->getHeader('In-Reply-To');
        $rec->bgerpSignature = $this->getHeader('X-Bgerp-Thread');
        
        // Добавя грешки, ако са възникнали при парсирането
        if(count($mimeParser->errors)) {
            foreach($mimeParser->errors as $err) {
                $rec->parserWarning = "\n<li style='color:red'>{$err}</li>";
            }
        }

        $rec->hash = $this->getHash();
        
        return $rec;
    }
    
    
    /**
     * Извлича адрес към когото е насочено писмото
     */
    function getToEmail()
    {
        $toHeader = $this->getHeader('To');
        $toParser = new email_Rfc822Addr();
        $parseTo = array();
        $toParser->ParseAddressList($toHeader, $parseTo);
        $toEmlArr = $this->extractEmailsFrom($parseTo[0]['address']);
        $toEml = $toEmlArr[0];
        
        return $toEml;
    }
    
    
    /**
     * Извлича масив с два елемента: Името на изпращача и имейла му
     */
    function getFromEmail()
    {
        $fromHeader = $this->getHeader('From');
        $fromParser = new email_Rfc822Addr();
        $parseFrom = array();
        $fromParser->ParseAddressList($fromHeader, $parseFrom);
        $fromEmlStr = $parseFrom[0]['address'] ? $parseFrom[0]['address'] : $parseFrom[1]['address'];
        $fromName = $parseFrom[0]['name'] . ' ' . $parseFrom[1]['name'];
        
        if(!$fromEmlStr) {
            $fromEmlArr = $this->extractEmailsFrom($this->getHeader('Return-Path'));
        } else {
            $fromEmlArr = $this->extractEmailsFrom($fromEmlStr);
        }
        
        $fromEml = $fromEmlArr[0];
        
        return array($fromName, $fromEml);
    }
    
    
    /**
     * Определяне на датата на писмото
     */
    function getDate()
    {
        // Определяме датата на писмото
        $d = date_parse($this->getHeader('Date'));
        
        if(count($d)) {
            $time = mktime($d['hour'], $d['minute'], $d['second'], $d['month'], $d['day'] , $d['year']);
            
            if($d['is_localtime']) {
                $time = $time + $d['zone'] * 60 + (date("O") / 100 * 60 * 60);
            }
            
            return dt::timestamp2Mysql($time);
        }
    }
    
    
    /**
     * Връща масив от всички под-стрингове, които
     * приличат на е-имейл адреси от дадения стринг
     */
    static function extractEmailsFrom($string)
    {
        preg_match_all("/[=\+\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i", $string, $matches);
        
        return $matches[0];
    }
    
    
    /**
     * Връща хедърната част на писмото като текст
     * Ако липсват, извлича ги чрез imap връзката
     */
    function getHeadersStr($partIndex = 1)
    {
        
        return $this->parts[$partIndex]->headersStr;
    }
    
    
    /**
     * Връща указания хедър.
     * Ако се очаква повече от един хедър с това име, то:
     *
     * - ако $id е положително -се записа с индекс $id
     *
     * - ако $id e отрицателно - връща се хедър-а с номер $id, като броенето започва отзад на пред.
     * при $id == -1 се връща последния срещнат хедър с указаното име
     *
     * - ако $id == 0 се връща първият срещнат хедър с това име. Тази стойност за $id се приема по
     * подразбиране и може да не се цитира, ако се очаква с посоченото име да има само един хедър
     *
     * - ако $id == '*'конкатенация между всички записи за дадения хедър
     * разделени с интервал
     */
    function getHeader($name, $part = 1, $headerIndex = 0)
    {
        if(is_object($part)) {
            $headersArr = $part->headersArr;
        } else {
            
            //Ако искаме всички части
            if ($part == '*') {
                foreach ($this->parts as $tPart) {
                    foreach ($tPart->headersArr as $key => $type) {
                        foreach ($type as $id => $val) {
                            
                            //Масив с всички хедъри
                            $headersArr[$key][$id] = $val;
                        }
                    }
                }
            } else {
                
                //Ако искаме точно определена част
                $headersArr = $this->parts[$part]->headersArr;    
            }
        }
        
        $name = strtolower($name);
        
        if ($headerIndex == "*") {
            if (is_array($headersArr[$name])) {
                $res = implode(' ', $headersArr[$name]);
            }
        } else {
            
            if($headerIndex < 0) {
                $headerIndex = count($headersArr[$name]) + $headerIndex;
            }
            
            expect(is_int($headerIndex));
            
            $res = $headersArr[$name][$headerIndex];
        }

        return $this->decodeHeader($res);
    }
    
    
    /**
     * Връща адреса, към когото е изпратен имейл-а. Проверява в email_Inboxes, за първия срещнат.
     * Ако няма връща първия имейл от масива, който би трябвало да е 'X-Origin-To'
     */
    function getToBox()
    {
        $recipients = $this->getHeader('X-Original-To', '*') . ' ' .
        $this->getHeader('Delivered-To', '*') . ' ' .
        $this->getHeader('To') . ' ' .
        $this->getHeader('Cc') . ' ' .
        $this->getHeader('Bcc');
        
        $to = email_Inboxes::findFirstInbox($recipients);
        
        return $to;
    }
    
    
    /**
     * Връща езика на който предполага, че е написано съобщението
     */
    function getLg()
    {
        $lgRates = lang_Encoding::getLgRates($this->textPart);
        
        return arr::getMaxValueKey($lgRates);
    }
    
    
    /**
     * Прави опит да намери IP адреса на изпращача
     */
    function getSenderIp()
    {
        $ip = trim($this->getHeader('X-Originating-IP', 1, -1), '[]');
        
        if(empty($ip) || (!type_Ip::isPublic($ip))) {
            
            $ip = trim($this->getHeader('X-Sender-IP', 1, -1), '[]');
        }
     
        if(empty($ip) || (!type_Ip::isPublic($ip))) {
            $regExp = '/Received:.*\[((?:\d+\.){3}\d+)\]/';
            preg_match_all($regExp, $this->getHeadersStr(), $matches);
            
            if($ipCnt = count($matches[1])) {
                for($i = $ipCnt - 1; $i >= 0; $i--) {
                    if(type_Ip::isPublic($matches[1][$i])) {
                        $ip = $matches[1][$i];
                        break;
                    }
                }
            }
        }
        
        if(empty($ip) || (!type_Ip::isPublic($ip))) {
            $regExp = '/Received:.*?((?:\d+\.){3}\d+)/';
            preg_match_all($regExp, $this->getHeadersStr(), $matches);
            
            if($ipCnt = count($matches[1])) {
                for($i = $ipCnt - 1; $i >= 0; $i--) {
                    if(type_Ip::isPublic($matches[1][$i])) {
                        $ip = $matches[1][$i];
                        break;
                    }
                }
            }
        }
         
        return $ip;
    }
    
    
    /**
     * Проверява дали имейл-а е спам или някакъв друг лош email
     * @todo да се реализира
     */
    function isBadMail($rec)
    {
        if(!$rec->fromEml) return TRUE;
        
        return FALSE;
    }
    
    
    /**
     * Изчислява коя е вероятната държава от където e изпратен имейл-а
     */
    function getCountry($from, $lg, $ip)
    {
        // Вземаме топ-левъл-домейна на имейл-а на изпращача
        $tld = substr($from, strrpos($from, '.'));
        
        // Двубуквен код на държава, според домейна, на изпращача на имейл-а
        if($tld) {
            if($ccByEmail = drdata_countries::fetchField("#domain = '{$tld}'", 'letterCode2')) {
                switch($ccByEmail) {
                    case 'us' :
                        $rate = 10;
                        break;
                    case 'gb' :
                    case 'de' :
                    case 'ru' :
                        $rate = 20;
                    default :
                    $rate = 40;
                }
                $countries[$ccByEmail] += $rate;
            }
        }
        
        // Двубуквен код на държава според $ip-то на изпращача
        if($ip) {
            if($ccByIp = drdata_ipToCountry::get($ip)) {
                switch($ccByIp) {
                    case 'us' :
                        $rate = 30;
                        break;
                    case 'gb' :
                    case 'de' :
                    case 'ru' :
                        $rate = 40;
                    default :
                    $rate = 60;
                }
                $countries[$ccByIp] += $rate;
            }
        }
        
        // Според държавата където е локиран сървъра на изпращача
        
        // Списък с държави в които се говори намерения език
        if($lg) {
            $countries[$lg] += 30;
        }
        
        // Намираме страната с най-много събрани точки
        if(count($countries)) {
            $firstCountry = arr::getMaxValueKey($countries);
            $countryId = drdata_Countries::fetchField("#letterCode2 = '{$firstCountry}'", 'id');
            
            return $countryId;
        }
    }
    
    
    /**
     * Вкарва прикрепените файлове във Fileman
     *
     * @return number - id' то на файла
     */
    function addFileToFileman($data, $name)
    {
        //Вкарваме файла във Fileman
        $Fileman = cls::get('fileman_Files');
        
        $fh = $Fileman->addNewFileFromString($data, 'Email', $name);
        
        $id = $Fileman->fetchByFh($fh, 'id');
        
        return $id;
    }
    
    
    /**
     * Замества cid' овете в html частта с линкове от системата
     */
    function replaceCid($html)
    {
        if (count($this->linkedFiles)) {

            foreach ($this->linkedFiles as $cid => $fileId) {

                $patterns = array("cid:{$cid}" => '', "\"cid:{$cid}\"" => '"', "'cid:{$cid}'" => "'");
                
                $Download = cls::get("fileman_Download");
                
                foreach($patterns as $ptr => $q) {
                    if(stripos($html, $ptr) !== FALSE) {
                        $fileUrl = static::getUrlForDownload($fileId);
                        $html = str_ireplace($ptr, "{$q}{$fileUrl}{$q}", $html);
                        
                        $this->cidFiles[$fileId] = $fileId;
                        
                    }
                }
            }
        }

        return $html;
    }
    
    
    /**
     * Връща рейтинга на текст
     * Колкото е по-голям рейтинга, толкова текста е по-съдържателен
     */
    function getTextRate($text)
    {
        $textRate = 0;
        $text = str_replace('&nbsp;', ' ', $text);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        
        if(trim($text, " \n\r\t" . chr(194) . chr(160))) {
            $textRate += 1;
            $words = preg_replace('/[^\pL\p{Zs}\d]+/u', ' ', $text);
            
            $textRate += mb_strlen($words);
        }
        
        return $textRate;
    }
    
    
    /**
     * Парсира хедъри-те в масив
     */
    static function parseHeaders($headersStr)
    {
        $headers = str_replace("\n\r", "\n", $headersStr);
        $headers = str_replace("\r\n", "\n", $headers);
        $headers = str_replace("\r", "\n", $headers);
        $headers = trim($headers);     //
        $headers = explode("\n", $headers);
        
        // парсира масив с хедъри на имейл
        foreach($headers as $h) {
            if(substr($h, 0, 1) != "\t" && substr($h, 0, 1) != " ") {
                $pos = strpos($h, ":");
                $index = strtolower(substr($h, 0, $pos));
                
                $headersArr[$index][] = trim(substr($h, $pos - strlen($h) + 1));
            } else {
                $current = count($headersArr[$index]) - 1;
                $headersArr[$index][$current] .= "\n" . $h;
            }
        }
        
        return $headersArr;
    }
    
    
    /**
     * Преобразува подадения стринг от имейл адреси в масив
     *
     * @param string $addrStr - Масив от
     *
     * @param string $defHost - Хоста по подразбиране. Ако не се намери хоста в имейл-а, тогава се използва.
     *
     * @return array
     * mailbox - пощенска кутия
     * host - хост
     * personal - име
     */
    function parseAddrList($addrStr, $defHost = '')
    {
        $arr = imap_rfc822_parse_adrlist($addrStr, $defHost);
        
        return $arr;
    }
    
    
    /**
     * Преобразува хедър-а в обект
     *
     * @param string $header - Хедър-а, който ще се преобразува
     *
     * @return obj
     */
    function rfcParseHeaders($header)
    {
        $obj = imap_rfc822_parse_headers($header);
        
        return $obj;
    }
    
    
    /**
     * Конвертира към UTF-8 текст
     * @todo $subtype не се използва никъде
     */
    function convertToUtf8($str, $charset=NULL, $subtype=NULL)
    {
        if ($this->is7Bit($str)) {
            // Тук трябва да има магическа функция, която да разпознае
            // дали евентуално няма някаква кодировка на текста (BASE64, QUOTED PRINTABLE ...
            // иначе в 99% от случаите това е просто текст на базова латиница
        } else {
            
            // Ако кодировката на текста е записана като ASCII, а имаме 8-битово кодиране, значи има грешка
            if($charset == 'US-ASCII') unset($charset);
            
            // Ако нямаме зададена кодировка на текста, опитваме се да я познаем
            if(!$charset) {
                // Махаме от текста всякакви HTML елементи
                $text = preg_replace('/\n/', ' ', $str);
                $text = preg_replace('/<script.*<\/script>/U', ' ', $text);
                $text = preg_replace('/<style.*<\/style>/U', ' ', $text);
                $text = strip_tags($text);
                $text = str_replace('&nbsp;', ' ', $text);
                $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
                
                // Анализираме текста и определяме предполагаемия енкодинг
                $res = lang_Encoding::analyzeCharsets($text);
                $charset = arr::getMaxValueKey($res->rates);
            }
            
            // Декодираме стринга към UTF-8, ако той не е в тази кодировка
            if($charset && ($charset != 'UTF-8')) {
                $str = iconv($charset, 'UTF-8//IGNORE', $str);
            }
        }
        
        return $str;
    }
    
    
    /**
     * Декодира хедърната част част
     */
    function decodeHeader($val)
    {
        if ($this->is7Bit($val)) {
            $imapDecodeArr = imap_mime_header_decode($val);
            
            $decoded = '';
            
            if (count($imapDecodeArr) > 0) {
                foreach ($imapDecodeArr as $value) {
                    
                    $charset = lang_Encoding::canonizeCharset($value->charset);
                    
                    if($charset && ($charset != 'UTF-8')) {
                        
                        $d = iconv($charset, "UTF-8", $value->text);

                        if($charset == 'ISO-8859-1' && ($len = mb_strlen($d)) > 2) {
                            // Дали не е грешка?
                            $badAlpha = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕ×ÖÜÚÞßàáâãäåæçèéêëìíîïðñòóôõ÷öüúþÿ';
                            $bad = 0;

                            for($i = 0; $i < $len; $i++) {
                                if(strpos($badAlpha, mb_substr($d, $i, 1)) !== FALSE) {
                                    $bad++;
                                }
                            }

                            if($bad > $len/2) {
                                $d  = iconv('CP1251', "UTF-8", $value->text);
                            }

                        }

                        $decoded .= $d;

                    } else {
                        $decoded .= $value->text;
                    }
                }
            } else {
                $decoded = $val;
            }
        } else {
            if(mb_detect_encoding($val, "UTF-8", TRUE) == "UTF-8") {
                $charset = 'UTF-8';
            } else {
                $charset = $this->parts[0]->charset;
            }
            
            $decoded = $this->convertToUtf8($val, $charset, 'PLAIN');
        }
        
        return $decoded;
    }
    
    
    /**
     * Проверява дали аргумента е 7 битов стринг
     *
     * @param string $str - Стринга, който ще се проверява
     *
     * @return boolean
     */
    function is7Bit($str)
    {
        $len = strlen($str);
        
        for ($i = 0; $i < $len; $i++) {
            if (ord($str{$i}) > 127) {
                
                return FALSE;
            }
        }
        
        return TRUE;
    }
    
    
    /**
     * Парсира цяло MIME съобщение
     */
    function parseAll($data, $index = 1)
    {
        // Ако не е записано, зачистваме цялото съдържание на писмото
        if(empty($this->data)) $this->data = $data;
        
        $bestPos = strlen($data);
        
        foreach(array("\r\n", "\n\r", "\n", "\r") as $c) {
            $pos = strpos($data, $c . $c);
            
            if($pos > 0 && $pos < $bestPos) {
                $bestPos = $pos;
                $nl = $c;
            }
        }
        
        if($bestPos < strlen($data)) {
            $data = explode($nl . $nl, $data, 2);
        }
        $p = &$this->parts[$index];
        
        if(!is_object($p)) {
            $p = new stdClass();
        }
        
        // Записваме хедъри-те на тази част като стринг
        $p->headersStr = $data[0];
        
        // Записваме хедъри-те на тази част като масив (за по-лесно търсене)
        // Масивът е двумерен, защото един хедър може (макар и рядко) 
        // да се среща няколко пъти
        $p->headersArr = $this->parseHeaders($data[0]);
        
        // Парсираме хедър-а 'Content-Type'
        $ctParts = $this->extractHeader($p, 'Content-Type', array('boundary', 'charset', 'name'));
        
        list($p->type, $p->subType) = explode('/', strtoupper($ctParts[0]), 2);
        
        $p->type = trim($p->type);
        $p->subType = trim($p->subType);
        
        $knownTypes = array('MULTIPART', 'TEXT', 'MESSAGE', 'APPLICATION', 'AUDIO', 'IMAGE', 'VIDEO', 'MODEL', 'X-UNKNOWN');
        
        // Ако типа не е от познатите типове, търсим ги като стринг в хедър-а 'Content-Type'
        // Ако някой познат тип се среща в хедър-а, то приемаме, че той е търсения тип
        if(!in_array($p->type, $knownTypes)) {
            $ct = $this->getHeader('Content-Type', $p);
            
            foreach($knownTypes as $t) {
                if(stripos($ct, $t)) {
                    $p->type = $t;
                    break;
                }
            }
        }
        
        // Ако по никакъв начин не сме успели да определим типа, приемаме че е 'TEXT'
        if(empty($p->type)) {
            $p->type = 'TEXT';
        }
        
        $knownSubTypes = array('PLAIN', 'HTML');
        
        // Ако под-типа не е от познатите под-типове, търсим ги като стринг в хедър-а 'Content-Type'
        // Ако някой познат под-тип се среща в хедър-а, то приемаме, че той е търсения под-тип
        if(!in_array($p->subType, $knownSubTypes)) {
            $ct = $this->getHeader('Content-Type', $p);
            
            foreach($knownSubTypes as $t) {
                if(stripos($ct, $t)) {
                    $p->subType = $t;
                    break;
                }
            }
        }
        
        $p->charset = lang_Encoding::canonizeCharset($p->charset);
        
        // Парсираме хедър-а 'Content-Transfer-Encoding'
        $cte = $this->extractHeader($p, 'Content-Transfer-Encoding');
        
        if($cte[0]) {
            $p->encoding = lang_Encoding::canonizeEncoding($cte[0]);
        }
        
        // Парсираме хедър-а 'Content-Disposition'
        $cd = $this->extractHeader($p, 'Content-Disposition', array('filename'));
        
        if($cd[0]) {
            $p->attachment = $cd[0];
        }
        
        // Ако частта е съставна, рекурсивно изваждаме частите и
        if(($p->type == 'MULTIPART') && $p->boundary) {
            $data[1] = explode("--" . $p->boundary, $data[1]);
            
            $cntParts = count($data[1]);
            
            if($cntParts == 2) {
                $this->errors[] = "Само едно  boundary в MULTIPART частта ($cntParts)";
                
                if(strlen($data[1][0]) > strlen($data[1][1])) {
                    unset($data[1][1]);
                } else {
                    unset($data[1][0]);
                }
            }
            
            if($cntParts == 1) {
                $this->errors[] = "Няма нито едно boundary в MULTIPART частта ($cntParts)";
            }
            
            if($cntParts >= 3) {
                if(strlen($data[1][0]) > 255) {
                    $this->errors[] = "Твърде много текст преди първата MULTIPART част";
                } else {
                    unset($data[1][0]);
                }
                
                if(strlen($data[1][$cntParts-1]) > 255) {
                    $this->errors[] = "Твърде много текст след последната MULTIPART част";
                } else {
                    unset($data[1][$cntParts-1]);
                }
            }
            
            for($i = 0; $i < $cntParts; $i++) {
                if($data[1][$i]) {
                    $this->parseAll($data[1][$i], $index . "." . $i);
                }
            }
            
            // Ако частта не е съставна, декодираме, конвертираме към UTF-8 и 
            // евентуално записваме прикачения файл
        } else {
            
            // Декодиране
            switch($p->encoding) {
                case 'BASE64' :
                    $data[1] = imap_base64($data[1]);
                    break;
                case 'QUOTED-PRINTABLE' :
                    $data[1] = imap_qprint($data[1]);
                    break;
                case '8BIT' :
                case '7BIT' :
                default :
            }
            
            // Ако часта e текстова и не е атачмънт, то по подразбиране, този текст е PLAIN
            if($p->attachment != 'attachment' && $p->type == 'TEXT' && !trim($p->subType)) {
                $p->subType = 'PLAIN';
            }

            // Конвертиране към UTF-8
            if($p->type == 'TEXT' && ($p->subType == 'PLAIN' || $p->subType == 'HTML') && ($p->attachment != 'attachment')) {
                
                $text = $this->convertToUtf8($data[1], $p->charset, $p->subType);
                
                // Текстовата част, без да се гледа HTML частта
                if ($p->subType == 'PLAIN') $this->justTextPart = $text;
                
                if($p->subType == 'HTML') {
                    $text = html2text_Converter::toRichText($text);
                }
                
                $textRate = $this->getTextRate($text);
                
                // Отдаваме предпочитания на плейн-частта, ако идва от bgERP
                if($p->subType == 'PLAIN') {
                    $textRate = $textRate * 1.5;
                    
                    if($this->getHeader('X-Bgerp-Thread')) {
                        $textRate = $textRate * 1.5;
                    }

                    // Ако обаче, текст часта съдържа значително количество HTML елементи,
                    // ние не я предпочитаме
                    $k = (mb_strlen(strip_tags($text)) + 1) / (mb_strlen($text) + 1);
                    $textRate = $textRate * $k * $k;
                }
                
                // Ако нямаме никакъв текст в тази текстова част, не записваме данните
                if(($textRate < 1) && (stripos($data[1], '<img ') === FALSE)) return;
                
                if($p->subType == 'HTML') {
                    $p->data = $data[1];
                } else {
                    $p->data = $text;
                }
                
                if($textRate > (1.05 * $this->bestTextRate)) {
                    if($p->subType == 'HTML') {
                        // Записваме данните
                        $this->textPart = $text;
                    } else {
                        $this->textPart = $p->data;
                        $this->bestTextIndex = $index;
                    }
                    $this->bestTextRate = $textRate;
                }
                
                if($p->subType == 'HTML' && (!$this->firstHtmlIndex) && ($textRate > 1 || (stripos($data[1], '<img ') === FALSE))) {
                    
                    $this->firstHtmlIndex = $index;
                }
            } else {
                
                // Ако частта представлява атачнат файл, определяме името му и разширението му
                $fileName = $this->getFileName($index);
                
                $p->filemanId = $this->addFileToFileman($data[1], $fileName);
                
                // Ако имаме 'Content-ID', запазваме го с връзката към файла, 
                // за да можем да свържем вградените граф. файлове в HTML частите
                if($cid = trim($this->getHeader('Content-ID', $p), '<>')) {
                    $this->linkedFiles[$cid] = $p->filemanId;
                }

                $this->attachedFiles[$p->filemanId] = $fileName;    
            }
        }
    }
    
    
    /**
     * Екстрактва информационните части на всеки хедър
     */
    function extractHeader(&$part, $headerName, $autoAttributes = array())
    {
        $header = $this->getHeader($headerName, $part);
        
        // $header = str_replace(array("\n", "\r", "\t"), array(';', ';', ';'), $header);
        $hParts = explode(';', $header);
        
        foreach($hParts as $p) {
            if(!trim($p)) continue;
            $p2 = explode('=', $p, 2);
            
            if(count($p2) == 1) {
                $res[] = $p;
            } else {
                $key = strtolower(trim($p2[0]));
                $value = trim($p2[1], "\"' ");
                $res[$key] = $value;
                
                if(in_array($key, $autoAttributes)) {
                    $part->{$key} = $value;
                }
            }
        }
        
        return $res;
    }
    
    
    /**
     * Връща най-доброто име за прикачен файл съответстващ на прикачената част
     */
    function getFileName($partIndex)
    {
        $p = $this->parts[$partIndex];
        
        setIfNot($fileName, $p->filename, $p->name);
        
        if(!$fileName || !strpos($fileName, '.')) {
            
            $fileName = $partIndex . '_' . substr($this->getHash(), 0, 6);
            
            // Опитваме се да определим разширението от 'Content-Type'
            
            $ctParts = $this->extractHeader($partIndex, 'Content-Type');
            
            $mimeT = strtolower($ctParts[0]);
            
            $fileName = fileman_mimes::addCorrectFileExt($fileName, $mimeT);
        }
        
        return $fileName;
    }
    
    
    /**
     * Връща релативен линк за сваляне
     * 
     * @param number $fileId - id' то на файла
     * 
     * @return $url - Релативно URL на файла
     */
    static function getUrlForDownload($fileId)
    {
        $fh = fileman_Files::fetchField($fileId, 'fileHnd');
        
        $url = toUrl(array('fileman_Download', 'Download', 'fh'=>$fh), 'relative');
        
        return $url;
    }
    
    
    /**
     * Взема хедърите от манипулатора на eml файл
     * 
     * @param fileman_Files $emlFileHnd - Манипулатора на eml файла
     * @param boolean $parseHeaders - Дали да се парсират в масив откритите хедъри
     * 
     * @return array $headersArr - Масив с хедърите
     * 		   string $headersArr['string'] - Стринг с хедърите
     * 		   array $headersArr['array'] - Масив с парсираните хедърите /Ако е зададено/
     */
    function getHeadersFromEmlFile($emlFileHnd, $parseHeaders=FALSE)
    {
        // Ако хедърите не са били извлечени
        if (!($headersStr = $this->getHeadersStr())) {
            
            // Вземаме съдържанието на eml файла
            $emlFileContent = fileman_Files::getContent($emlFileHnd);
            
            // Парсираме съдържанието
            $this->parseAll($emlFileContent);
    
            // Стринг с хедърите
            $headersStr = $this->getHeadersStr();    
        }
        
        // Добавяме в масива
        $headersArr['string'] = $headersStr;
        
        // Ако е зададено да се парсират хедърите
        if ($parseHeaders) {
            
            // Добавяме в масива парсираните хедъри
            $headersArr['array'] = $this->parseHeaders($headersStr);
        }
        
        return $headersArr;
    }
    
    
    /**
     * Връща линкнатите файлове (cid)
     * 
     * @return array $linkedFiles - Масив с всички линкнати файлове (cid)
     */
    function getLinkedFiles()
    {
        // Преборъщаме масива, id'тата да са ключ
        $linkedFiles = array_flip($this->linkedFiles);
        
        return $linkedFiles;
    }
    
    
    /**
     * Премахва CID файловете от прикачените и връща масива
     * 
     * @return array $attachedFiles - Масив с прикачените файлове, без CID файловете
     */
    function getJustAttachedFiles()
    {
        // Всички файлове, които са маркирани, като cid
        $cidFiles = $this->getCidFiles();
        
        // Всички прикачени файлове в документа
        $attachedFiles = $this->attachedFiles;
        
        // Конфигурационните константи
        $conf = core_Packs::getConfig('email');
        
        // Обхождаме всики cid файлове
        foreach ($cidFiles as $key => $cidFile) {
            
            // Вземаме записите за съответния файа
            $fRec = fileman_Files::fetch($cidFile);
            
            // Данните за съответния файл
            $dataRec = fileman_Data::fetch($fRec->dataId);
            
            // Дължината на файла
            $fLen = $dataRec->fileLen;
            
            // Ако дължината на файла е по малка от максимално допустимата
            if ($fLen <= $conf->EMAIL_MAXIMUM_CID_LEN) {
                
                // Ако има такъв прикачен файл, премахваме го от прикачените
                if ($attachedFiles[$key]) unset($attachedFiles[$key]);
            }
        }

        return $attachedFiles;
    }
    
    
    /**
     * Връща прикачените файлове
     * 
     * @return array - Масив с всички прикачени файлове
     */
    function getAttachedFiles()
    {
        
        return $this->attachedFiles;
    }
    
    
    /**
     * Връща файловете от частите на EML
     * 
     * @return array - Масив с всички файлове на частите
     */
    function getPartFiles()
    {
        
        return $this->partFiles;
    }
    
    
    /**
     * Връща cid файловете
     * 
     * @return array - Масив с всички cid файлове
     */
    function getCidFiles()
    {
        
        return $this->cidFiles;
    }
    
    
    /**
     * Връща текстовата част на EML файла /Без да взема в предвид HTML частта/
     * 
     * @return string - Текстова част на имейла
     */
    function getJustTextPart()
    {
        
        return $this->justTextPart;
    }
}