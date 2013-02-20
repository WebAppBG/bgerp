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
     * Събджект на писмото
     */
    var $subject;
     
     
    /**
     * Имейлът от хедъра 'To'
     */
    var $toEmail;
   

    /**
     * Името на изпращача от хедъра 'From'
     */
    var $fromName;
    

    /**
     * Имейла на изпращача от хедъра 'From'
     */
    var $fromEmail;
    
    
    /**
     * Времето на изпращане на имейла
     */
    var $sendingTime;
    
    
    /**
     * Езика на имейл-а
     */
    var $lg;

   
    /**
     * IP адреса на изпращача
     */
    var $senderIp;
    
       
    /**
     * Масив с id => [данни за файл] - прикачени файлове
     * ->name
     * ->data
     * ->type
     * ->param
     */
    var $files = array();
    
    
    /**
     * Масив със съобщения за грешки по време на парсирането
     */
    var $errors = array();


    /**
     * Връща събджекта на писмото
     */
    function getSubject()
    {
        if(!isset($this->subject)) {
            $this->subject = $this->getHeader('Subject');
            $this->subject = str_replace(array("\n\t", "\n"), array('', ''),  $this->subject);
        }

        return $this->subject;
    }


    /**
     * Извлича адрес към когото е насочено писмото
     */
    function getToEmail()
    {
        if(!isset($this->toEmail)) {
            $toHeader = $this->getHeader('To');
            $toParser = new email_Rfc822Addr();
            $parseTo = array();
            $toParser->ParseAddressList($toHeader, $parseTo);
            $toEmlArr = type_Email::extractEmails($parseTo[0]['address']);
            $this->toEmail = $toEmlArr[0];
        }
        
        return $this->toEmail;
    }

    
    /**
     * Връща името на изпращача
     */
    function getFromName()
    {
        if(!isset($this->fromName)) {
            $this->parseFromEmail();
        }

        return $this->fromName;
    }


    /**
     * Връща името на изпращача
     */
    function getFromEmail()
    {
        if(!isset($this->fromEmail)) {
            $this->parseFromEmail();
        }

        return $this->fromEmail;
    }
    

    /**
     * Извлича масив с два елемента: Името на изпращача и имейла му
     */
    private function parseFromEmail()
    {
        $fromHeader = $this->getHeader('From');
        $fromParser = new email_Rfc822Addr();
        $parseFrom = array();
        $fromParser->ParseAddressList($fromHeader, $parseFrom);
        $fromEmlStr = $parseFrom[0]['address'] ? $parseFrom[0]['address'] : $parseFrom[1]['address'];
        $this->fromName = $parseFrom[0]['name'] . ' ' . $parseFrom[1]['name'];
        
        if(!$fromEmlStr) {
            $fromEmlArr = type_Email::extractEmails($this->getHeader('Return-Path'));
        } else {
            $fromEmlArr = type_Email::extractEmails($fromEmlStr);
        }
        
        $this->fromEmail = $fromEmlArr[0];
    }
    
    
    /**
     * Определяне на датата на писмото, когато е изпратено
     */
    function getSendingTime()
    {
        if(!isset($this->sendingTime)) {
            // Определяме датата на писмото
            $d = date_parse($this->getHeader('Date'));
            
            if(count($d)) {
                $time = mktime($d['hour'], $d['minute'], $d['second'], $d['month'], $d['day'] , $d['year']);
                
                if($d['is_localtime']) {
                    $time = $time + $d['zone'] * 60 + (date("O") / 100 * 60 * 60);
                }
                
                $this->sendingTime = dt::timestamp2Mysql($time);
            }
        }

        return $this->sendingTime;
    }


    /**
     * Връща езика на който предполага, че е написан имейла
     */
    function getLg()
    {
        if(!isset($this->lg)) {
            $lgRates = lang_Encoding::getLgRates($this->textPart);
            $this->lg = arr::getMaxValueKey($lgRates);
        }

        return $this->lg;
    }
    
    
    /**
     * Прави опит да намери IP адреса на изпращача
     */
    function getSenderIp()
    {
        if(!isset($this->senderIp)) {
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

            $this->senderIp = $ip;
        }
         
        return $this->senderIp;
    }


    /**
     * Изчислява коя е вероятната държава от където e изпратен имейл-а
     */
    function getCountry()
    {
        $from = $this->getFromEmail();
        $lg   = $this->getLg();
        $ip   = $this->getSenderIp();

        // Вземаме топ-левъл-домейна на имейл-а на изпращача
        $tld = substr($from, strrpos($from, '.'));
        
        // Двубуквен код на държава, според домейна, на изпращача на имейл-а
        if($tld) {
            if($ccByEmail = drdata_Countries::fetchField("#domain = '{$tld}'", 'letterCode2')) {
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
        
        // Според държавата където е локиран маил-сървъра на изпращача
        
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
     * Изходния код на писмото
     */
    function getData()
    {
        return $this->data;
    }


    /**
     * Връща манипулатора на eml файл, отговарящ на писмото
     */
    function getEmlFile()
    {
        // Записваме текста на писмото, като [hash].eml файл
        $emlFileName = md5($this->getHeadersStr()) . '.eml';
        
        $fmId = $this->addFileToFileman($this->data, $emlFileName);
        
        return $fmId;
    }


    /**
     * Връща прикачените файлове
     * 
     * @return array - Масив с всички прикачени файлове
     */
    function getFiles()
    {
        foreach($this->files as  $fRec) {
            
            $list .= ($list ? '' : '|') . $fRec->fmId . '|';
        }
      
        return $list;
    }


    /**
     * Връща id на файла, в който е записана html часта
     */
    function getHtmlFile()
    {
        return $this->htmlFile;
    }


    /**
     * Записва във fileman всички файлове, които са извлечени при парсирането
     */
    function saveFiles()
    {
        foreach($this->files as $id => &$fRec) {
            if(!$fRec->fmId) {
                $fRec->fmId = $this->addFileToFileman($fRec->data, $fRec->name);
            }
        }

        // Минаваме по всички текстови и HTML части да ги запишем като прикачени файлове
        // Пропускаме само тази PLAIN TEXT част, която е използване
        foreach($this->parts as $index => $p) {
            if($p->type == 'TEXT') {
                if(($index == $this->bestTextIndex) || (!$p->data)) continue;
                
                // В HTML часта заместваме cid:... с линкве към файловете
                if($p->subType == 'HTML') {
                    $p->data = $this->placeInlineFiles($p->data);
                }

                $fileName = $this->getFileName($index);
                
                $p->fileId = $this->addFile($p->data, $fileName, 'part', $p->subType);

                $FRecText = $this->files[$p->fileId];
 
                $FRecText->fmId = $this->addFileToFileman($FRecText->data, $FRecText->name);

                if($index == $this->firstHtmlIndex) {
                    $this->htmlFile = $FRecText->fmId;
                } 
            }
        }
    }


    /**
     * Добавя файл в списъка на прикачените файлове
     */
    function addFile($data, $name, $type = NULL, $param = NULL)
    {   
        $rec = (object) array(
            'name' => $name, 
            'data' => $data, 
            'type' => $type, 
            'param' => $param);
        $id  = count($this->files) + 1;
        $this->files[$id] = $rec;

        return $id;
    }

    
    /**
     * Вкарва прикрепените файлове във Fileman
     *
     * @return fh - манипулатора на файла
     */
    function addFileToFileman($data, $name)
    {
        $Fm = cls::get('fileman_Files');
        
        //Вкарваме файла във Fileman
        $fh = $Fm->addNewFileFromString($data, 'Email', $name);
        
        // Вземаме id-то на файла
        $id = $Fm->fetchField("#fileHnd = '{$fh}'", 'id');
            
        return $id;
    }
    
    
    /**
     * Замества cid' овете в html частта с линкове от системата
     */
    function placeInlineFiles($html)
    {
        if (count($this->files)) {

            foreach ($this->files as $fRec) {
                
                if($fRec->type != 'inline') continue;
                
                $cid = $fRec->param;

                $patterns = array("cid:{$cid}" => '', "\"cid:{$cid}\"" => '"', "'cid:{$cid}'" => "'");
                
                $Download = cls::get("fileman_Download");
                
                foreach($patterns as $ptr => $q) {
                    if(stripos($html, $ptr) !== FALSE) {
                        $fh = fileman_Files::fetchField($fRec->fmId, 'fileHnd');
                        $fileUrl = toUrl(array('fileman_Download', 'Download', 'fh' => $fh));
                        $html = str_ireplace($ptr, "{$q}{$fileUrl}{$q}", $html);
                    }
                }
            }
        }

        return $html;
    }



    /***********************************************************************************
     *                                                                                 *
     *  ФУНКЦИИ  ЗА РАБОТА С ХЕДЪРИ                                                    *
     *                                                                                 *
     ***********************************************************************************/

    /**
     * Връща масив с парсирани хедъри на миме-съобщение
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
     * Връща хедърната част на писмото като текст
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
    function getHeader($name, $part = 1, $headerIndex = 0, $decode=TRUE)
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
        
        return static::getHeadersFromArr($headersArr, $name, $headerIndex);
    }
    
    
    /**
     * Връща даден хедът от масив
     * 
     * @param array $headersArr - Масив с хедърите
     * @param string $name - Името на хедъра
     * @param mixed $headerIndex - Число или * - Указва, кои да се извлекат
     * @param boolean $decode - Дали да се декодира хедъра
     * 
     * @retun string $res - Съдържанието на хедъра
     */
    static function getHeadersFromArr($headersArr, $name, $headerIndex=0, $decode=TRUE)
    {
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

        if ($decode) {
            $res = static::decodeHeader($res);
        }
        
        return $res;
    }
    
    
    /**
     * Екстрактва информационните части на всеки хедър
     */
    function extractHeader(&$part, $headerName, $autoAttributes = array())
    {
        $header = $this->getHeader($headerName, $part);
        
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
     * Декодира хедърната част част
     */
    static function decodeHeader($val)
    {   
        // Ако стойността на хедъра е 7-битова, той може да е кодиран
        if(lang_Encoding::is7Bit($val)) {
            
            $imapDecodeArr = @imap_mime_header_decode($val);
            
            $decoded = '';
            
            if (count($imapDecodeArr) > 0) {
                foreach ($imapDecodeArr as $value) {
                    $decoded .= lang_Encoding::convertToUtf8($value->text, $value->charset);
                }
            } else {
                $decoded = $val;
            }
        } else {

            $decoded = lang_Encoding::convertToUtf8($val, $this->parts[1]->charset);
        }
        
        return $decoded;
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
                
                $text = lang_Encoding::convertToUtf8($data[1], $p->charset, $p->subType);
                
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
                $cid = trim($this->getHeader('Content-ID', $p), '<>');
                $p->filemanId = $this->addFile($data[1], $fileName, 'inline', $cid);
            }
        }
    }
    
    
    /**
     * Връща рейтинга на текст
     * Колкото е по-голям рейтинга, толкова текста е по-съдържателен
     */
    private function getTextRate($text)
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
     * Връща най-доброто име за прикачен файл съответстващ на прикачената част
     */
    private function getFileName($partIndex)
    {
        $p = $this->parts[$partIndex];
        
        setIfNot($fileName, $p->filename, $p->name);
        
        // Ако липсва файл, името му е производно на хеша на съдържанието му
        if(!$fileName) {
            $fileName = $partIndex . '_' . substr(md5($p->data), 0, 6);
        }

        // Ако липсва файлово разширение се опитваме да го определим от 'Content-Type'
        if(!fileman_Files::getExt($fileName)) {
            $ctParts = $this->extractHeader($partIndex, 'Content-Type');
            $mimeT = strtolower($ctParts[0]);
            $fileName = fileman_mimes::addCorrectFileExt($fileName, $mimeT);
        }

        return $fileName;
    }
    



    //---------------------------------------------------------------------------------------------------------------------------------------

    
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
    
    
    /**
     * Екстрактва имейлите и връща само имейл частта на масива
     * 
     * @param string $arr - Масив с имейли
     * 
     * @return string $res - Резултата
     */
    static function getAllEmailsFromStr($str)
    {
        // Инстанция на класа
        $toParser = new email_Rfc822Addr();
        
        // Масив в който ще парсираме
        $parseToArr = array();
        
        // Парсираме
        $toParser->ParseAddressList($str, $parseToArr);
        
        // Обхождаме масива
        foreach ((array)$parseToArr as $key => $dummy) {
           
            // Извличаме само имейлите
            $EmlArr = type_Email::extractEmails($parseToArr[$key]['address']); 
            
            // Преобразуваме в стринг
            $implode = implode(', ', $EmlArr);
            
            // Добавяме към полето
            $res .= ($res) ? ', '. $implode : $implode;
        }
        
        return $res;
    }
}