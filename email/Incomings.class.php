<?php 


/**
 * Входящи писма
 *
 *
 * @category  bgerp
 * @package   email
 * @author    Milen Georgiev <milen@download.bg> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_Incomings extends core_Master
{
    
    
    /**
     * Текста бутона за създаване на имейли
     */
    var $emailButtonText = 'Отговор';
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'email_Messages';
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Входящи имейли';
    
    
    /**
     * @todo Чака за документация...
     */
    var $singleTitle = 'Входящ имейл';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'user';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'email';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
     
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'email_Wrapper, doc_DocumentPlg, plg_RowTools, 
         plg_Printing, email_plg_Document, doc_EmailCreatePlg, plg_Sorting';
    
    
    /**
     * Сортиране по подразбиране по низходяща дата
     */
    var $defaultSorting = 'date=down';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'email/tpl/SingleLayoutMessages.shtml';
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    var $singleIcon = 'img/16/email.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Msg";
    
    
    /**
     * Първоначално състояние на документа
     */
    var $firstState = 'closed';
    
    
    /**
     * Полето "Относно" да е хипервръзка към единичния изглед
     */
    var $rowToolsSingleField = 'subject';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,subject,date,fromEml=От,toBox=До,accId,boxIndex,country';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'subject, fromEml, fromName, textPart, files';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('accId', 'key(mvc=email_Accounts,select=email)', 'caption=Акаунт');
        $this->FLD("messageId", "varchar", "caption=Съобщение ID");
        $this->FLD("subject", "varchar", "caption=Тема");
        $this->FLD("fromEml", "email", 'caption=От->Имейл');
        $this->FLD("fromName", "varchar", 'caption=От->Име');
        
        // Първия наш имейл от MIME-хедъра "To:"
        $this->FLD("toEml", "email(link=no)", 'caption=До->Имейл');
        
        // Наша пощенска кутия (email_Inboxes) до която е адресирано писмото.
        // Това поле се взема предвид при рутиране и създаване на правила за рутиране.
        $this->FLD("toBox", "email(link=no)", 'caption=До->Кутия');
        
        $this->FLD("headers", "text", 'caption=Хедъри');
        $this->FLD("textPart", "richtext", 'caption=Текстова част');
        $this->FLD("spam", "int", 'caption=Спам');
        $this->FLD("lg", "varchar", 'caption=Език');
        $this->FLD("date", "datetime(format=smartTime)", 'caption=Дата');
        $this->FLD('hash', 'varchar(32)', 'caption=Keш');
        $this->FLD('country', 'key(mvc=drdata_countries,select=letterCode2)', 'caption=Държава');
        $this->FLD('fromIp', 'ip', 'caption=IP');
        $this->FLD('files', 'keylist(mvc=fileman_Files)', 'caption=Файлове, input=none');
        $this->FLD('emlFile', 'key(mvc=fileman_Files)', 'caption=eml файл, input=none');
        $this->FLD('htmlFile', 'key(mvc=fileman_Files)', 'caption=html файл, input=none');
        $this->FLD('boxIndex', 'int', 'caption=Индекс');
        $this->FLD('uid', 'int', 'caption=Imap UID');

        $this->setDbUnique('hash');
    }


    /**
     * Взема записите от пощенската кутия и ги вкарва в модела
     *
     * @param string $htmlRes
     *
     * @return string $logMsg - Съобщение с броя на новите имейли
     */
    function getMailInfo(&$htmlRes = NULL)
    {
    	$conf = core_Packs::getConfig('email');
    	
        set_time_limit(300);

        ini_set('memory_limit', $conf->EMAIL_MAX_ALLOWED_MEMORY);

        // Максималната продължителност за теглене на писма
        $maxFetchingTime = $conf->EMAIL_MAX_FETCHING_TIME;
    
            
        // До коя секунда в бъдещето максимално да се теглят писма?
        $maxTime = time() + $maxFetchingTime;
            
        // даваме достатъчно време за изпълнението на PHP скрипта
        set_time_limit($maxFetchingTime + 60);
        
        $accQuery = email_Accounts::getQuery();
        $accQuery->XPR('order', 'double', 'RAND()');
        $accQuery->orderBy('#order');

        while (($accRec = $accQuery->fetch("#state = 'active'")) && ($maxTime > time())) {
            
            // Заключваме тегленето от тази пощенска кутия
            $lockKey = 'Inbox:' . $accRec->id;
            
            $logMsg .= ($logMsg ? "<br>" : "") . "{$accRec->email}: ";
            
            $htmlRes .= "\n<li> Връзка с пощенската кутия на: <b>\"{$accRec->user} ({$accRec->server})\"</b></li>";
            
            if(!core_Locks::get($lockKey, $maxFetchingTime, 1)) {
                $htmlRes .= "<i style='color:red;'>Кутията е заключена от друг процес</i>";
                $logMsg  .= "<i style='color:red;'>Кутията е заключена от друг процес</i>";
                continue;
            }

            // Нулираме броячите за различните получени писма
            $skipedEmails = $skipedServiceEmails = $errorEmails = $newEmails = 0;
            
            /* @var $imapConn email_Imap */
            $imapConn = cls::get('email_Imap', array('accRec' => $accRec));
            
            // Логването и генериране на съобщение при грешка е винаги в контролерната част
            if ($imapConn->connect() === FALSE) {
                           

                $this->log("Не може да се установи връзка с пощенската кутия на <b>\"{$accRec->user} ({$accRec->server})\"</b>. " .
                    "Грешка: " . $imapConn->getLastError());
                
                $htmlRes .= "\n<li style='color:red'> Възникна грешка при опит да се свържем с пощенската кутия: <b>{$arr['user']}</b>" .
                $imapConn->getLastError() .
                "</li>";
                $logMsg .= ' ERROR!';
                continue;
            }
            

            // Получаваме броя на писмата в INBOX папката
            $numMsg = $imapConn->getStatistic('messages');
            
            $firstUnreadMsg = $this->getFirstUnreadMsgNo($imapConn, $numMsg);
            
            $startTime = time();

            if($firstUnreadMsg > 0) {
            
                // Правим цикъл по всички съобщения в пощенската кутия
                // Цикълът може да прекъсне, ако надвишим максималното време за сваляне на писма
                // Прогресивно извличане: ($i = 504; ($i <= $numMsg) && ($maxTime > time()); $i++)
                for ($i = $firstUnreadMsg; $i <= $numMsg && ($maxTime > time()); $i++) {
                    
                    if(($i % 100) == 1 || ( ($i - $firstUnreadMsg) < 100)) {
                        $this->log("Fetching message {$i} from {$accRec->server}");
                        echo "<li> Fetching message {$i} from {$accRec->server}";
                    }
                    
                    $rec = $this->fetchSingleMessage($i, $imapConn);
                    
                    if ($rec->isDublicate) {
                        // Писмото вече е било извличано и е записано в БД. $rec съдържа данните му.
                        // Debug::log("Е-имейл MSG_NUM = $i е вече при нас, пропускаме го");
                        $htmlRes .= "\n<li> Skip: {$rec->hash}</li>";
                        $skipedEmails++;
                    } elseif($rec->isService) {
                        $htmlRes .= "\n<li> Skip service mail: {$rec->hash}</li>";
                        $skipedServiceEmails++;
                    } elseif($rec->error) {
                        // Възникнала е грешка при извличането на това писмо
                        // Debug::log("Е-имейл MSG_NUM = $i е вече при нас, пропускаме го");
                        $htmlRes .= "\n<li> Error: msg = {$i} ({$rec->error})</li>";
                        $errorEmails++;
                    } else {
                        // Ново писмо. 
                        $htmlRes .= "\n<li style='color:green'> Get: {$rec->hash}</li>";
                        $rec->accId = $accRec->id;
                        $newEmails++;
                        $rec->uid = $imapConn->getUid($i);

                        /**
                         * Служебните писма не подлежат на рутинно рутиране. Те се рутират по други
                         * правила.
                         *
                         * Забележка 1: Не вграждаме логиката за рутиране на служебни писма в процеса
                         *              на рутиране, защото той се задейства след запис на писмото
                         *              което означава, че писмото трябва все пак да бъде записано.
                         *
                         *              По този начин запазваме възможността да не записваме
                         *              служебните писма.
                         *
                         * Забележка 2: Въпреки "Забележка 1", все пак може да записваме и служебните
                         *              писма (при условие че подсигурим, че те няма да се рутират
                         *              стандартно). Докато не изтриваме писмата от сървъра след
                         *              сваляне е добра идея да ги записваме в БД, иначе няма как да
                         *              знаем дали вече не са извършени (еднократните) действия
                         *              свързани с обработката на служебно писмо. Т.е. бихме
                         *              добавяли в лога на писмата по един запис за върнато писмо
                         *              (например) всеки път след изтегляне на писмата от сървъра.
                         *
                         *
                         */
                        
                        // Когато правим начално фетчване, датата на документа е датата на писмото
                        if($flagFetchAll) {
                            $rec->createdOn = $rec->date;
                        }
                        
                        $saved = email_Incomings::save($rec);
                                               
                        // Ако парсера е издал предупреждения - добавяме ги и към двете статусни съобщения
                        if($rec->parserWarning) {
                            $logMsg  .= "<font color=red>Parser Error in msg {$i} {$rec->hash}</font><br>"  . $rec->parserWarning;
                            $htmlRes .= "<font color=red>Parser Error in msg {$i} {$rec->hash}</font><br>" . $rec->parserWarning;
                        }
                    }
                    
                    if ($accRec->deleteAfterRetrieval == 'yes') {
                        $imapConn->delete($i);
                    }
                }
            
                $imapConn->expunge();
            }
            
            $imapConn->close();
            
            // Махаме заключването от кутията
            core_Locks::release($lockKey);
            
            $duration = time() - $startTime;

            $msg = "($duration) s; Total: {$numMsg}, Skip: {$skipedEmails}, Skip service: {$skipedServiceEmails},  Errors: {$errorEmails}, New: {$newEmails}";
            $logMsg .= $msg;
            $htmlRes .= $msg;
        }
        
        return $logMsg;
    }
    

    /**
     * Връща поредния номер на първото не-четено писмо
     */
    function getFirstUnreadMsgNo($imapConn, $maxMsgNo)
    {
        // Няма никакви съобщения за сваляне?
        if(!($maxMsgNo > 0)) {
            return NULL;
        }
        
        if($imapConn->accRec->protocol == 'imap') {
            $query = self::getQuery();
            $query->XPR('maxUid', 'int', 'max(#uid)');
            $query->show('maxUid');
            $maxRec = $query->fetch("#accId = {$imapConn->accRec->id}");
        }
 
        if(!$maxRec->maxUid) {
            
            // Горен указател
            $t = $maxMsgNo; 
            
            $i = 1;
            
            // Долен указател
            $b = max(1, $maxMsgNo - $i);
            
            $isDownT = $this->isDownloaded($imapConn, $t);

            // Дали всички съобщения са прочетени?
            if($isDownT) {
                return NULL;
            }

            $isDownB = $this->isDownloaded($imapConn, $b);

            do {
                // Ако и двете не са свалени; Изпълнява се няколко пъти последователно в началото
                if(!$isDownB && !$isDownT) {
                    if($t == $b) {

                        return $t;
                    }
                    $t = $b;
                    $i = $i * 2;
                    $b = max(1, $maxMsgNo - $i);
                    $isDownB = $this->isDownloaded($imapConn, $b);
                } elseif($isDownB && !$isDownT) {
                    // Условие, при което $t е първото не-свалено писмо
                    if($t - $b == 1) {

                        return $t;
                    }
                    $m = round(($t + $b) / 2);
                    $isDownM = $this->isDownloaded($imapConn, $m);
                    if($isDownM) {
                        $b = $m;
                    } else {
                        $t = $m;
                    }
                }

                $change = ($t != $tLast || $b != $bLast);

                $tLast = $t;

                $bLast = $b;
                
            } while($change);

        } else {
            $maxReadMsgNo = $imapConn->getMsgNo($maxRec->maxUid);
            
            if(($maxReadMsgNo === FALSE) || ($maxReadMsgNo >= $maxMsgNo)) {
                $maxReadMsgNo = NULL;
            } else {
                $maxReadMsgNo++;
            }
            
            return $maxReadMsgNo;
        }
    }


    function isDownloaded($imapConn, $msgNum)
    {
        static $isDown = array();
        
        // Номерата почват от 1
        if($msgNum < 1) {
            $this->log('TRUE: $msgNum < 1');

            return TRUE;
        }
        
        echo "<li> ID {$imapConn->accRec->id} $msgNum </li> <br>";
        $this->log( "<li> ID {$imapConn->accRec->id} $msgNum </li> <br>");

        if(!isset($isDown[$imapConn->accRec->id][$msgNum])) {

            $headers = $imapConn->getHeaders($msgNum);
            
            // Ако няма хедъри, значи има грешка
            if(!$headers) {
                $this->log('TRUE: !$headers');

                return TRUE;
            }
            
            $mimeParser = new email_Mime();
            
            $hash = $mimeParser->getHash($headers);
            
            $res = $isDown[$imapConn->accRec->id][$msgNum] = $this->fetchField("#hash = '{$hash}'", 'id');
            $this->log(($res ? 'TRUE' : 'FALSE' ) . ":{$imapConn->accRec->id} $res $hash");

        }

        return $res;
    }


    
    /**
     * Проверява за служебно писмо (т.е. разписка, върнато) и ако е го обработва.
     *
     * Вдига флага $rec->isServiceMail в случай, че $rec съдържа служебно писмо.Обработката на
     * служебни писма включва запис в log_Documents.
     *
     * @param stdClass $rec запис на модел email_Incomings
     * @return boolean TRUE ако писмото е служебно
     */
    static function processServiceMail($toEml, $date, $fromIp)
    {
        $isServiceMail = FALSE;
        
        if ($mid = static::isReturnedMail($toEml)) {
            // Върнато писмо
            $isServiceMail = log_Documents::returned($mid, $date);
        } elseif ($mid = static::isReceipt($toEml)) {
            // Разписка
            $isServiceMail = log_Documents::received($mid, $date, $fromIp);
        } else {
            // Не служебна поща
        }
        
        return $isServiceMail;
    }
    
    
    /**
     * Проверява дали писмо е върнато.
     *
     * @param string имейл адрес
     * @return string MID на писмото, ако наистина е върнато; FALSE в противен случай.
     */
    static function isReturnedMail($toEml)
    {
        if (!preg_match('/^.+\+returned=([a-z]+)@/i', $toEml, $matches)) {
            return FALSE;
        }
        
        return $matches[1];
    }
    
    
    /**
     * Проверява дали съобщението е разписка за получено писмо
     *
     * @param string имейл адрес
     * @return string MID на писмото, ако наистина е разписка; FALSE в противен случай.
     */
    static function isReceipt($toEml)
    {
        if (!preg_match('/^.+\+received=([a-z]+)@/i', $toEml, $matches)) {
            return FALSE;
        }
        
        return $matches[1];
    }


    /**
     * Извлича едно писмо от пощенския сървър.
     *
     * Следи и пропуска (не извлича) вече извлечените писма.
     *
     * @param int $msgNum пореден номер на писмото за извличане
     * @param email_Imap $conn обект-връзка с пощенския сървър
     * @param email_Mime $mimeParser инстанция на парсер на MIME съобщения
     * @return stdClass запис на модел email_Incomings
     */
    function fetchSingleMessage($msgNum, $conn)
    {
        // Debug::log("Започва обработката на е-имейл MSG_NUM = $msgNum");
        
        $headers = $conn->getHeaders($msgNum);
        
        // Ако няма хедъри, значи има грешка
        if(!$headers) {
            $rec = new stdClass();
            $rec->error = 'Missed headers';
            $rec->hash  = 'none';
            
            return $rec;;
        }
        
        $mimeParser = new email_Mime();
        
        $hash = $mimeParser->getHash($headers);
        
        if ((!$rec = $this->fetch("#hash = '{$hash}'"))) {
            
            // Тук парсираме писмото и проверяваме дали не е системно
            $mime = new email_Mime();
            
            $mime->parts[1] = new stdClass();
            
            $mime->parts[1]->headersArr = $mime->parseHeaders($headers);
            $mime->parts[1]->headersStr = $headers;

            // Извличаме информация за вътрешния системен адрес, към когото е насочено писмото
            $toEml = $mime->getHeader('X-Original-To', '*');
            
            if(!preg_match('/^.+\+([a-z]+)=([a-z]+)@/i', $toEml)) {
                $toEml = $mime->getHeader('Delivered-To', '*');
            }
            
            if(!preg_match('/^.+\+([a-z]+)=([a-z]+)@/i', $toEml)) {
                $toEml = $mime->getToEmail();
            }
            
            // Намираме датата на писмото
            $date = $mime->getDate();
            
            // Опитваме се да намерим IP-то на изпращача
            $fromIp = $mime->getSenderIp();
            
            // Ако е-мейла е сервизен, връщаме празен запис;
            if(!static::processServiceMail($toEml, $date, $fromIp)) {
                // Писмото не е било извличано до сега. Извличаме го.
                // Debug::log("Сваляне на имейл MSG_NUM = $msgNum");
                $rawEmail = $conn->getEml($msgNum);
                
                // Debug::log("Парсираме и композираме записа за имейл MSG_NUM = $msgNum");
                try {
                    if(empty($rawEmail)) {
                        $rec = new stdClass();
                        $rec->error = 'Липсва сорса на имейла';
                        
                        return;
                    }

                    $rec = $mimeParser->getEmail($rawEmail);

                } catch (Exception $exc) {
                    // Не можем да парсираме е-мейла
                    
                    if(Request::get('forced')) {
                        $exc->getAsHtml();
                    }

                    email_Unparsable::add($rawEmail);
                    
                    $rec = new stdClass();
                    $rec->error = 'Не може да се парсира имейла';
                        
                    return;
                }
                
                // Ако не е получен запис, значи има грешка
                if(!$rec) {
                    $rec = new stdClass();
                    $rec->error = 'Error in parsing';
                } else {
                    // Само за дебъг. Todo - да се махне
                    $rec->boxIndex = $msgNum;

                    // Все пак да вземем хеша на хедърите от истинското писмо, вместо от $conn->getHeaders($msgNum)
                    $hash = $mimeParser->getHash();
                    
                    // Проверка дали междувременно друг процес не е свалил и записал писмото
                    $rec->isDublicate = $this->fetchField("#hash = '{$hash}'", 'id');
                }
            } else {
                $rec = new stdClass();
                $rec->isService =  TRUE;
            }
        } else {
            $rec->isDublicate = TRUE;
        }
        
        // Задаваме хеша на писмото
        $rec->hash = $hash;
        
        return $rec;
    }
    
    
    /**
     * Изпълнява се преди преобразуването към вербални стойности на полетата на записа
     */
    static function on_BeforeRecToVerbal($mvc, &$row, $rec, $fields)
    {
        $rec->textPart = trim($rec->textPart);
    }
    
    
    /**
     * Преобразува containerId в машинен вид
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields)
    {
        if(!$rec->subject) {
            $row->subject .= '[' . tr('Липсва заглавие') . ']';
        }
        
        // Показва до събджекта номера на писмото от пощенската кутия
        // $row->subject .= " ($rec->boxIndex)";
        
        if($fields['-single']) {
            if ($rec->files) {
                $vals = type_Keylist::toArray($rec->files);
                
                if (count($vals)) {
                    $row->files = '';
                    
                    foreach ($vals as $keyD) {
                        $row->files .= fileman_Download::getDownloadLinkById($keyD);
                    }
                }
            }
        }
        
        if(!$rec->toBox) {
            $row->toBox = $row->toEml;
        }
        
        if($rec->fromIp && $rec->country) {
            $row->fromIp .= " ($row->country)";
        }
        
        if(trim($row->fromName) && (strtolower(trim($rec->fromName)) != strtolower(trim($rec->fromEml)))) {
            $row->fromEml = $row->fromEml . ' (' . trim($row->fromName) . ')';
        }
                
        if($fields['-list']) {
           // $row->textPart = mb_Substr($row->textPart, 0, 100);
        }
    }
    
    
    /**
     * Да сваля имейлите
     */
    function act_DownloadEmails()
    {
        requireRole('admin');
        
        $mailInfo = $this->getMailInfo($htmlRes);
        
        return $htmlRes;
    }
    
    
    /**
     * Да сваля имейлите по - крон
     */
    function cron_DownloadEmails()
    {
        $mailInfo = $this->getMailInfo();
        
        return $mailInfo;
    }
    
    
    /**
     * Cron екшън за опресняване на публичните домейни
     */
    function cron_UpdatePublicDomains()
    {
        $domains = static::scanForPublicDomains();
        
        $out .= "<li>Сканирани " . count($domains) . " домейн(а) ... </li>";
        
        $stats   = drdata_Domains::resetPublicDomains($domains);
        
        $out .= "<li>Добавени {$stats['added']}, изтрити {$stats['removed']} домейн(а)</li>";
        
        if ($stats['addErrors']) {
            $out .= "<li class=\"error\">Проблем при добавянето на {$stats['addErrors']} домейн(а)!</li>";
        }
        
        if ($stats['removeErrors']) {
            $out .= "<li class=\"error\">Проблем при изтриването на {$stats['removeErrors']} домейн(а)!</li>";
        }
        
        $out = ""
        . "<h4>Опресняване на публичните домейни<h4>"
        . "<ul>"
        .    $out
        . "</ul>";
        
        return $out;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function act_UpdatePublicDomains()
    {
        return static::cron_UpdatePublicDomains();
    }
    
    
    /**
     * Изпълнява се след създаването на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        $res .= "<p><i>Нагласяне на Cron</i></p>";
        
        $rec = new stdClass();
        $rec->systemId = 'DownloadEmails';
        $rec->description = 'Сваля и-имейлите в модела';
        $rec->controller = $mvc->className;
        $rec->action = 'DownloadEmails';
        $rec->period = 2;
        $rec->offset = 0;
        $rec->delay = 0;
        $rec->timeLimit = 100;
        
        $Cron = cls::get('core_Cron');
        
        if ($Cron->addOnce($rec)) {
            $res .= "<li><font color='green'>Задаване на крон да сваля имейлите в модела.</font></li>";
        } else {
            $res .= "<li>Отпреди Cron е бил нагласен да сваля имейлите.</li>";
        }
        
        return $res;
    }
    
    /******************************************************************************************
     *
     * ИМПЛЕМЕНТАЦИЯ НА email_DocumentIntf
     * 
     ******************************************************************************************/
    
    
    /**
     * Текстов вид (plain text) на документ при изпращането му по имейл
     *
     * @param int $id ид на документ
     * @param string $emailTo
     * @param string $boxFrom
     * @return string plain text
     */
    public function getEmailText($id, $emailTo = NULL, $boxFrom = NULL)
    {
        return static::fetchField($id, 'textPart');
    }


    /**
     * Какъв да е събджекта на писмото по подразбиране
     *
     * @param int $id ид на документ
     * @param string $emailTo
     * @param string $boxFrom
     * @return string
     */
    public function getDefaultSubject($id, $emailTo = NULL, $boxFrom = NULL)
    {
        return 'FW: ' . static::fetchField($id, 'subject');
    }
    
    
    /****************************************************************************************
     *                                                                                      *
     *  ИМПЛЕМЕНТАЦИЯ НА @link doc_DocumentIntf                                             *
     *                                                                                      *
     ****************************************************************************************/
    
    
    /**
     * Интерфейсен метод на doc_DocumentIntf
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $subject = $this->getVerbal($rec, 'subject');
        
        if(!trim($subject)) {
            $subject = '[' . tr('Липсва заглавие') . ']';
        }
        
        $row = new stdClass();
        $row->title = $subject;
        
        if(trim($rec->fromName)) {
            $row->author = $this->getVerbal($rec, 'fromName');
        } else {
            $row->author = "<small>{$rec->fromEml}</small>";
        }
        
        $row->authorEmail = $rec->fromEml;
        
        $row->state = $rec->state;
        
        $row->recTitle = $rec->subject;
        
        return $row;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function isSpam($rec)
    {
        
        /**
         * @TODO
         */
        
        return FALSE;
    }
    
    
    /**
     * Рутиране на писмо още преди записването му.
     *
     * Тук писмата се рутират при възможност директно в нишката, за която са предназначени.
     * Ако това рутиране пропадне, задейства се метода @see doc_DocumentPlg::on_AfterRoute() и
     * той изпраща писмото в специална папка за несортирани писма. От там по-късно писмата биват
     * рутирани @link email_Router.
     *
     * @param stdClass $rec запис на модела email_Incomings
     */
    public function route_($rec)
    {
        // Правилата за рутиране, подредени по приоритет. Първото правило, след което съобщението
        // има нишка и/или папка прекъсва процеса - рутирането е успешно.
        $rules = array(
            'self::routeByThread',
            'email_Filters::preroute',
            'self::routeByFromTo',
            'self::routeByFrom',
            'self::routeSpam',
            'self::routeByDomain',
            'self::routeByPlace',
            'self::routeByTo',
        );
        
        foreach ($rules as $rule) {
            if (is_callable($rule)) {
                call_user_func($rule, $rec);
                
                if ($rec->folderId || $rec->threadId) {
                    break;
                }
            }
        }
    }
    
    
    /**
     * Извлича при възможност нишката от наличната информация в писмото
     *
     * Местата, където очакваме информация за манипулатор на тред са:
     *
     * o `In-Reply-To` (MIME хедър)
     * o `Subject`
     *
     * @param stdClass $rec
     */
    static function routeByThread($rec)
    {
        $rec->threadId = static::extractThreadFromReplyTo($rec);
        
        if (!$rec->threadId) {
            $rec->threadId = static::extractThreadFromSubject($rec);
        }
        
        if ($rec->threadId) {
            // Премахване на манипулатора на нишката от събджекта
            static::stripThreadHandle($rec);
            
            // Зануляваме папката - тя е еднозначно определена от вече намерената нишка.
            $rec->folderId = NULL;
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function routeByFromTo($rec)
    {
        if (!static::isGenericRecipient($rec)) {
            // Това правило не се прилага за "общи" имейли
            $rec->folderId = static::routeByRule($rec, email_Router::RuleFromTo);
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function routeByFrom($rec)
    {
        if (static::isGenericRecipient($rec)) {
            // Това правило се прилага само за "общи" имейли
            $rec->folderId = static::routeByRule($rec, email_Router::RuleFrom);
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function routeSpam($rec)
    {
        if (static::isSpam($rec)) {
            $rec->isSpam = TRUE;
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function routeByDomain($rec)
    {
        if (static::isGenericRecipient($rec) && !$rec->isSpam) {
            $rec->folderId = static::routeByRule($rec, email_Router::RuleDomain);
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function routeByPlace($rec)
    {
        if (empty($rec->toBox)) {
            $rec->toBox = email_Inboxes::fetchField($rec->accId, 'email');
        }
        
        if (static::isGenericRecipient($rec) && !$rec->isSpam && $rec->country) {
            //
            // Ако се наложи създаване на папка за несортирани писма от държава, отговорника
            // трябва да е отговорника на кутията, до която е изпратено писмото.
            //
            $inChargeUserId = email_Inboxes::getEmailInCharge($rec->toBox);
            
            $rec->folderId = static::forceCountryFolder(
                $rec->country /* key(mvc=drdata_Countries) */,
                $inChargeUserId
            );
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function routeByTo($rec)
    {
        $rec->folderId = email_Inboxes::forceFolder($rec->toBox);
        
        expect($rec->folderId);
    }
    
    
    /**
     * Потребителско рутиране
     */
    static function routeByRule($rec, $type)
    {
        return email_Router::route($rec->fromEml, $rec->toBox, $type);
    }
    
    /**
     * Извлича нишката от 'In-Reply-To' MIME хедър
     *
     * @param stdClass $rec
     * @return int първичен ключ на нишка или NULL
     */
    protected static function extractThreadFromReplyTo($rec)
    {
        if (!$rec->inReplyTo) {
            return NULL;
        }
        
        if (!($mid = email_util_ThreadHandle::extractMid($rec->inReplyTo))) {
            return NULL;
        }
        
        if (!($sentRec = email_Sent::fetchByMid($mid, 'containerId, threadId'))) {
            return NULL;
        }
        
        $rec->originId = $sentRec->containerId;
        
        return $sentRec->threadId;
    }
    
    /**
     * Извлича нишката от 'Subject'-а
     *
     * @param stdClass $rec
     * @return int първичен ключ на нишка или NULL
     */
    protected static function extractThreadFromSubject($rec)
    {
        $subject = $rec->subject;
        
        // Списък от манипулатори на нишки, за които е сигурно, че не са наши
        $blackList = array();
        
        if ($rec->bgerpSignature) {
            // Възможно е това писмо да идва от друга инстанция на BGERP.
            list($foreignThread, $foreignDomain) = preg_split('/\s*;\s*/', $rec->bgerpSignature, 2);
            
            if ($foreignDomain != BGERP_DEFAULT_EMAIL_DOMAIN) {
                // Да, друга инстанция;
                $blackList[] = $foreignThread;
            }
        }
        
        // Списък от манипулатори на нишка, които може и да са наши
        $whiteList = email_util_ThreadHandle::extract($subject);
        
        // Махаме 'чуждите' манипулатори
        $whiteList = array_diff($whiteList, $blackList);
        
        // Проверяваме останалите последователно 
        foreach ($whiteList as $handle) {
            if ($threadId = static::getThreadByHandle($handle)) {
                break;
            }
        }
        
        return $threadId;
    }
    
    /**
     * Намира тред по хендъл на тред.
     *
     * @param string $handle хендъл на тред
     * @return int key(mvc=doc_Threads) NULL ако няма съответен на хендъла тред
     */
    protected static function getThreadByHandle($handle)
    {
        return doc_Threads::getByHandle($handle);
    }
    
    
    /**
     * Създава при нужда и връща ИД на папката на държава
     *
     * @param int $countryId key(mvc=drdata_Countries)
     * @return int key(mvc=doc_Folders)
     */
    static function forceCountryFolder($countryId, $inCharge)
    {
        $folderId = NULL;
        
        $conf = core_Packs::getConfig('email');

        /**
         * @TODO: Идея: да направим клас email_Countries (или може би bgerp_Countries) наследник
         * на drdata_Countries и този клас да стане корица на папка. Тогава този метод би
         * изглеждал така:
         *
         * $folderId = email_Countries::forceCoverAndFolder(
         *         (object)array(
         *             'id' => $countryId
         *         )
         * );
         *
         * Това е по-ясно, а и зависимостта от константата EMAILІUNSORTABLE_COUNTRY отива на
         * 'правилното' място.
         */
        
        $countryName = static::getCountryName($countryId);
        

        if (!empty($countryName)) {
            $folderId = doc_UnsortedFolders::forceCoverAndFolder(
                (object)array(
                    'name'     => sprintf($conf->EMAIL_UNSORTABLE_COUNTRY, $countryName),
                    'inCharge' => $inCharge
                )
            );
        }
        
        return $folderId;
    }
    
    
    /**
     * Връща името на държавата от която е пратен имейл-а
     */
    protected static function getCountryName($countryId)
    {
        if ($countryId) {
            $countryName = drdata_Countries::fetchField($countryId, 'commonName');
        }
        
        return $countryName;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function isGenericRecipient($rec)
    {
        return empty($rec->toBox) || email_Inboxes::isGeneric($rec->toBox);
    }
    
    
    /**
     * Преди вкарване на запис в модела
     */
    static function on_BeforeSave($mvc, $id, &$rec) {
        //При сваляне на имейл-а, състоянието е затворено
        
        if (!$rec->id) {
            $rec->state = 'closed';
            $rec->_isNew = TRUE;
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function stripThreadHandle($rec)
    {
        expect($rec->threadId);
        
        $threadHandle = doc_Threads::getHandle($rec->threadId);
        
        $rec->subject = email_util_ThreadHandle::strip($rec->subject, $threadHandle);
    }
    
    
    /**
     * Извиква се след вкарване на запис в таблицата на модела
     */
    static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
        static::needFields($rec, 'fromEml, toBox, date, containerId,threadId');
        
        if ($rec->state == 'rejected') {
            $mvc->removeRouterRules($rec);
        } elseif (empty($rec->_skipRouterRules)) {
            $mvc->makeRouterRules($rec);
        }
    }
    
    
    /**
     * След изтриване на записи на модела
     *
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param core_Query $query
     */
    static function on_AfterDelete($mvc, &$res, $query)
    {
        foreach ($query->getDeletedRecs() as $rec) {
            $mvc->removeRouterRules($rec);
        }
    }
    
    
    /**
     * Зарежда при нужда полета на зададен запис от модела.
     *
     * @param stdClass $rec запис на модела; трябва да има зададен поне първ. ключ ($rec->id)
     * @param mixed $fields полетата, които са нужни; ако ги няма в записа - зарежда ги от БД
     *
     * @TODO това е метод от нивото на fetch, така че може да се изнесе в класа core_Mvc
     */
    static function needFields($rec, $fields)
    {
        expect($rec->id);
        
        $fields = arr::make($fields);
        $missing = array();
        
        foreach ($fields as $f) {
            if (!isset($rec->{$f})) {
                $missing[$f] = $f;
            }
        }
        
        if (count($missing) > 0) {
            $savedRec = static::fetch($rec->id, $missing);
            
            foreach ($missing as $f) {
                $rec->{$f} = $savedRec->{$f};
            }
        }
        
        return $rec;
    }
    
    
    /**
     * Създава правила за рутиране на базата на това писмо
     *
     * За обновяване на правилата след всеки запис на писмо се използва този метод.
     *
     * @param stdClass $rec
     */
    static function makeRouterRules($rec)
    {
        static::makeFromToRule($rec);
        static::makeFromRule($rec);
        static::makeDomainRule($rec);
    }
    
    
    /**
     * Премахва всички правила за рутиране, създадени поради това писмо.
     *
     * В добавка създава правила на базата на последните 3 писма от същия изпращач.
     *
     * @param stdClass $rec
     */
    static function removeRouterRules($rec)
    {
        // Премахване на правилата
        email_Router::removeRules('document', $rec->containerId);
        
        //
        // Създаване на правила на базата на последните 3 писма от същия изпращач
        //
        
        /* @var $query core_Query */
        $query = static::getQuery();
        $query->where("#fromEml = '{$rec->fromEml}' AND #state != 'rejected'");
        $query->orderBy('date', 'DESC');
        $query->limit(3);     // 3 писма
        while ($mrec = $query->fetch()) {
            static::makeRouterRules($mrec);
        }
    }
    
    
    /**
     * Създаване на правило от тип `FromTo` - само ако получателя не е общ.
     *
     * @param stdClass $rec
     * @param int $priority
     */
    static function makeFromToRule($rec)
    {
        if (!static::isGenericRecipient($rec)) {
            $key = email_Router::getRoutingKey($rec->fromEml, $rec->toBox, email_Router::RuleFromTo);
            
            // Най-висок приоритет, нарастващ с времето
            $priority = email_Router::dateToPriority($rec->date, 'high', 'asc');
            
            email_Router::saveRule(
                (object)array(
                    'type'       => email_Router::RuleFromTo,
                    'key'        => $key,
                    'priority'   => $priority,
                    'objectType' => 'document',
                    'objectId'   => $rec->containerId
                )
            );
        }
    }
    
    
    /**
     * Създаване на правило от тип `From` - винаги
     *
     * @param stdClass $rec
     * @param int $priority
     */
    static function makeFromRule($rec)
    {
        // Най-висок приоритет, нарастващ с времето
        $priority = email_Router::dateToPriority($rec->date, 'high', 'asc');
        
        email_Router::saveRule(
            (object)array(
                'type'       => email_Router::RuleFrom,
                'key'        => email_Router::getRoutingKey($rec->fromEml, NULL, email_Router::RuleFrom),
                'priority'   => $priority,
                'objectType' => 'document',
                'objectId'   => $rec->containerId
            )
        );
    }
    
    
    /**
     * Създаване на правило от тип `Domain` - ако изпращача не е от пуб. домейн и получателя е общ.
     *
     * @param stdClass $rec
     * @param int $priority
     */
    static function makeDomainRule($rec)
    {
        if (static::isGenericRecipient($rec) && ($key = email_Router::getRoutingKey($rec->fromEml, NULL, email_Router::RuleDomain))) {
            
            // До тук: получателя е общ и домейна не е публичен (иначе нямаше да има ключ).
            
            // Остава да проверим дали папката е на визитка. Иначе казано, дали корицата на
            // папката поддържа интерфейс `crm_ContragentAccRegIntf`
            
            if ($coverClassId = doc_Folders::fetchField($rec->folderId, 'coverClass')) {
                $isContragent = cls::haveInterface('crm_ContragentAccRegIntf', $coverClassId);
            }
            
            if ($isContragent) {
                // Всички условия за добавяне на `Domain` правилото са налични.
                
                // Най-висок приоритет, нарастващ с времето
                $priority = email_Router::dateToPriority($rec->date, 'high', 'asc');
                
                email_Router::saveRule(
                    (object)array(
                        'type'       => email_Router::RuleDomain,
                        'key'        => $key,
                        'priority'   => $priority,
                        'objectType' => 'document',
                        'objectId'   => $rec->containerId
                    )
                );
            }
        }
    }
    
    
    /**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща данните за адресанта
     */
    static function getContragentData($id)
    {
        //Данните за имейл-а
        $msg = email_Incomings::fetch($id);
        
        $addrParse = cls::get('drdata_Address');
        $ap = $addrParse->extractContact($msg->textPart);
        
        $contragentData = new stdClass();
        
        if(count($ap['company'])) {
            $contragentData->company = arr::getMaxValueKey($ap['company']);
            
            if(count($ap['company'] > 1)){
                foreach($ap['company'] as $cName => $prob) {
                    $contragentData->companyArr[$cName] =  $cName;
                }
            }
        }
        
        if(count($ap['tel'])) {
            $contragentData->tel = arr::getMaxValueKey($ap['tel']);
        }
        
        if(count($ap['fax'])) {
            $contragentData->fax = arr::getMaxValueKey($ap['fax']);
        }
        
        $contragentData->email = $msg->fromEml;
        $contragentData->countryId = $msg->country;
        
        return $contragentData;
    }
    
    
    /**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото наимей по подразбиране
     */
    static function getDefaultEmailBody($id, $forward)
    {
        //Вземаме датата от базата от данни
        $rec = email_Incomings::fetch($id, 'date');
        
        if ($forward) {
            
            // Инстанция към документа
            $incomingInst = cls::get('email_Incomings');
            
            // Манипулатора на документа
            $handle = $incomingInst->getHandle($id);
            
            // Текстова част
            $text = "Моля запознайте се с препратения имейл #{$handle}.";    
        } else {
            
            //Вербализираме датата
            $date = dt::mysql2verbal($rec->date, 'd-M H:i');
            
            //Създаваме шаблона
            $text = tr('Благодаря за имейла от') . " {$date}.\n" ;    
        }
        
        return $text;
    }
    
    
    /**
     * Намира всички домейни, от които има изпратени писма, намиращи се в различни фирмени папки
     *
     * @return array масив с ключове - домейни (и стойности TRUE)
     */
    static function scanForPublicDomains()
    {
        // Извличаме ид на корица на фирмените папки
        $crmCompaniesClassId = core_Classes::fetchIdByName('crm_Companies');
        
        // Построяваме заявка, извличаща всички писма, които са във фирмена папка.
        /* @var $query core_Query */
        $query = static::getQuery();
        $query->EXT('coverClass', 'doc_Folders', 'externalKey=folderId');
        $query->where("#coverClass = {$crmCompaniesClassId}");
        $query->show('fromEml, folderId');
        
        $domains = array();
        $result  = array();
        
        while ($rec = $query->fetch()) {
            $fromDomain = type_Email::domain($rec->fromEml);
            $domains[$fromDomain][$rec->folderId] = TRUE;
            
            if (count($domains[$fromDomain]) > 1) {
                // От $fromDomain има поне 2 писма, които са в различни фирмени папки
                $results[$fromDomain] = TRUE;
            }
        }
        
        return $result;
    }
    
    
    /**
     * Реализация  на интерфейсния метод ::getThreadState()
     */
    static function getThreadState($id)
    {
        return 'opened';
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function getExternalEmails($threadId)
    {
        /* @var $query core_Query */
        $query = static::getQuery();
        $query->where("#threadId = {$threadId}");
        $query->show('fromEml');
        
        $result = array();
        
        while ($rec = $query->fetch()) {
            if($eml = trim($rec->fromEml)) {
                $result[$eml] = $eml;
            }
        }
        
        return $result;
    }
    
    /**
     * @todo Чака за документация...
     */
    function act_Update()
    {
        set_time_limit(3600);
        $query = self::getQuery();
        
        while($rec = $query->fetch()) {
            $i++;
            
            if($i % 100 == 1) {
                $this->log("Update email $i");
            }
            self::save($rec);
        }
    }

    
    /**
     * Добавя бутони
     */
    function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        // Ако имаме права за single
        if ($mvc->haveRightFor('single', $data->rec)) {
            
            if ($data->rec->emlFile) {
                
                // Име на бутона
                if ($data->rec->htmlFile) {
                    $buttonName = 'Изглед';
                } else {
                    $buttonName = 'Детайли';
                }
                
                // Добавяме бутон за разглеждане не EML файла
                $data->toolbar->addBtn($buttonName, array(
                        'fileman_Files',
                        'single',
                        'id' => fileman_Files::fetchField($data->rec->emlFile, 'fileHnd'),
                    ),
                'class=btn-eml, order=21');    
                
                // Добавяме бутон за препращане на имейла
                $data->toolbar->addBtn('Препаращне', array(
                        'email_Outgoings',
                        'forward',
                        $data->rec->id,
                        'ret_url' => TRUE,
                    ), 'class=btn-forward, order=20'
                );
            }
        }
    }
    
    
    /**
     * Връща EML файл при генериране на възможности разширения за прикачване
     */
    function on_BeforeGetTypeConvertingsByClass($mvc, $res, $id)
    {
        //Превръщаме $res в масив
        $res = (array)$res;
        
        // Вземаме манипулатора на файла
        $name = $mvc->getHandle($id);
        
        //Името на файла е с големи букви, както са документите
        $name = strtoupper($name) . '.eml';
        
        //Задаваме полето за избор, да е избран по подразбиране
        $res[$name] = 'on';
    }
    
    
    /**
     * Добавяме манупулаторите на файловете с разширение .eml
     * 
     * @param core_Mvc $mvc
     * @param array $res масив с манипулатор на файл (@see fileman)
     * @param int $id първичен ключ на документа
     * @param string $type формат, в който да се генерира съдържанието на док.
     * @param string $fileName име на файл, в който да се запише резултата
     */
    static function on_BeforeConvertTo($mvc, &$res, $id, $type, $fileName = NULL)
    {
        // Преобразуваме в масив
        $res = (array)$res;
        
        switch (strtolower($type)) {
            case 'eml':
        
                // Вземаме id' то на EML файла
                $emlFileId = $mvc->fetchField($id, 'emlFile');
                
                // Манипулатора на файла
                $fh = fileman_Files::fetchField($emlFileId, 'fileHnd');
                
                // Добавяме в масива
                if ($fh) {
                    $res[$fh] = $fh;
                } 
                  
            break;
        }
    }
    
	
	/**
	 * Връща прикачените файлове
     * 
     * @param object $rec - Запис
     */
    function getLinkedFiles($rec)
    {
        // Ако не е обект
        if (!is_object($rec)) {
             
            // Вземаме записите за файла
            $rec = $this->fetch($rec);    
        }
         
        // Превръщаме в масив
        $filesArr = type_Keylist::toArray($rec->files);
         
         // Ако има HTML файл
         if ($rec->htmlFile) {
             
             // Добавяме го към файловете
             $filesArr[$rec->htmlFile] = $rec->htmlFile;
         }
         
         // Добавяме EML файла, към файловете
         $filesArr[$rec->emlFile] = $rec->emlFile;
         
         // Обхождаме всички файлове
         foreach ($filesArr as $fileId) {
             
            // Вземаме записите за файловете
            $fRec = fileman_Files::fetch($fileId);
             
            // Създаваме масив с прикачените файлове
            $fhArr[$fRec->fileHnd] = $fRec->name;
        }
         
        return $fhArr;
    }

     
    /**
     * Връща иконата на документа
     */
    function getIcon_($id)
    {
        $rec = self::fetch($id);
 
        if($rec->files) {
             
            return "img/16/email-attach.png";
        }
    }
}
