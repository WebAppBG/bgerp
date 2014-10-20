<?php 


/**
 * Шаблон за писма за масово разпращане
 * 
 * 
 * @category  bgerp
 * @package   blast
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 * 
 * @method array getEmailOtherPlaces(object $rec)
 * @method string getHandle(integer $id)
 * @method string getVerbalSizesFromArray(array $arr)
 * @method boolean checkMaxAttachedSize(array $attachSizeArr)
 * @method array getFilesSizes(array $sizeArr)
 * @method array getDocumentsSizes(array $docsArr)
 * @method array getAttachments(object $aRec)
 * @method array getPossibleTypeConvertings(object $cRec)
 */
class blast_Emails extends core_Master
{
    
    
    /**
     * Име на папката по подразбиране при създаване на нови документи от този тип.
     * Ако стойноста е 'FALSE', нови документи от този тип се създават в основната папка на потребителя
     */
    public $defaultFolder = 'Циркулярни имейли';
	
    
    /**
     * Полета, които ще се клонират
     */
    public $cloneFields = 'perSrcClassId, perSrcObjectId, from, subject, body, recipient, attn, email, tel, fax, country, pcode, place, address, attachments, encoding';
    
    
    /**
     * Заглавие на таблицата
     */
    public $title = "Циркулярни имейли";
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Циркулярен имейл";
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/emails.png';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Inf';
    
    
    /**
     * Полето "Относно" да е хипервръзка към единичния изглед
     */
    public $rowToolsSingleField = 'subject';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * Кой има право да чете?
     */
    protected $canRead = 'ceo, blast';
    
    
    /**
     * Кой има право да променя?
     */
    protected $canEdit = 'ceo, blast';
    
    
    /**
     * Кой има право да клонира?
     */
    protected $canClone = 'ceo, blast';
    
    
    /**
     * Кой има право да добавя?
     */
    protected $canAdd = 'ceo, blast';
    
    
    /**
     * Кой може да го види?
     */
    protected $canView = 'ceo, blast';
    
    
    /**
     * Кой може да го разглежда?
     */
    protected $canList = 'ceo, blast';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	protected $canSingle = 'ceo, blast';


	/**
	 * Кой може да оттелгя имейла
	 */
	protected $canReject = 'ceo, blast';
    
	
	/**
	 * Кой може да активира имейла
	 */
	protected $canActivate = 'ceo, blast';
    
	
	/**
	 * Кой може да обновява списъка с детайлите
	 */
	protected $canUpdate = 'ceo, blast';
	

	/**
	 * Кой може да спира имейла
	 */
	protected $canStop = 'ceo, blast';
	
	
    /**
     * Кой може да го изтрие?
     */
    protected $canDelete = 'no_one';
    
    
    /**
     * Кой може да праша информационните съобщения?
     */
    protected $canBlast = 'ceo, blast';
    
    
    /**
     * Кой може да променя активирани записи
     * @see change_Plugin
     */
    protected $canChangerec = 'blast, ceo';
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'email_DocumentIntf';
    
    
    /**
     * Плъгините и враперите, които ще се използват
     */
    public $loadList = 'blast_Wrapper, doc_DocumentPlg, plg_RowTools, bgerp_plg_blank, change_Plugin, plg_Search';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене 
     * @see plg_Search
     */
    public $searchFields = 'subject, body';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    protected $listFields = 'id, subject, srcLink, from, sendPerCall, startOn';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'blast_EmailSend';
    
    
    /**
     * Нов темплейт за показване
     */
    protected $singleLayoutFile = 'blast/tpl/SingleLayoutEmails.shtml';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "2.2|Циркулярни";
    
    
    /**
     * id на системата в крона
     */
    protected static $cronSytemId = 'SendEmails';
    
    
    /**
     * Описание на модела
     */
    protected function description()
    {
        $this->FLD('perSrcClassId', 'class(interface=bgerp_PersonalizationSourceIntf)', 'caption=Източник на данни->Клас, silent, input=hidden');
        $this->FLD('perSrcObjectId', 'int', 'caption=Списък, mandatory, silent');
        
        $this->FLD('from', 'key(mvc=email_Inboxes, select=email)', 'caption=От, mandatory, changable');
        $this->FLD('subject', 'varchar', 'caption=Относно, width=100%, mandatory, changable');
        $this->FLD('body', 'richtext(rows=15,bucket=Blast)', 'caption=Съобщение,mandatory, changable');
        $this->FLD('sendPerCall', 'int(min=1, max=100)', 'caption=Изпращания заедно, input=none, mandatory, oldFieldName=sendPerMinute, title=Брой изпращания заедно');
        $this->FLD('startOn', 'datetime', 'caption=Време на започване, input=none');
        $this->FLD('activatedBy', 'key(mvc=core_Users)', 'caption=Активирано от, input=none');
        
        //Данни на адресата - антетка
        $this->FLD('recipient', 'varchar', 'caption=Адресат->Фирма,class=contactData, changable');
        $this->FLD('attn', 'varchar', 'caption=Адресат->Лице,oldFieldName=attentionOf,class=contactData, changable');
        $this->FLD('email', 'varchar', 'caption=Адресат->Имейл,class=contactData, changable');
        $this->FLD('tel', 'varchar', 'caption=Адресат->Тел.,class=contactData, changable');
        $this->FLD('fax', 'varchar', 'caption=Адресат->Факс,class=contactData, changable');
        $this->FLD('country', 'varchar', 'caption=Адресат->Държава,class=contactData, changable');
        $this->FLD('pcode', 'varchar', 'caption=Адресат->П. код,class=contactData, changable');
        $this->FLD('place', 'varchar', 'caption=Адресат->Град/с,class=contactData, changable');
        $this->FLD('address', 'varchar', 'caption=Адресат->Адрес,class=contactData, changable');
        
        $this->FLD('encoding', 'enum(utf-8=Уникод|* (UTF-8),
									cp1251=Windows Cyrillic|* (CP1251),
                                    koi8-r=Rus Cyrillic|* (KOI8-R),
                                    cp2152=Western|* (CP1252),
                                    ascii=Латиница|* (ASCII))', 'caption=Знаци, changable');
        
        $this->FLD('attachments', 'set(files=Файловете,documents=Документите)', 'caption=Прикачи, changable');
        $this->FLD('lg', 'enum(auto=Автоматично, ' . EF_LANGUAGES . ')', 'caption=Език,changable');
        $this->FNC('srcLink', 'varchar', 'caption=Списък');
    }
    
    
    /**
     * Създава имейл с посочените данни
     * 
     * @param integer $perSrcClassId
     * @param integer $perSrcObjectId
     * @param string $text
     * @param string $subject
     * @param array $otherParams
     * 
     * @return integer
     */
    public static function createEmail($perSrcClassId, $perSrcObjectId, $text, $subject, $otherParams = array())
    {
        // Задаваме стойност
        $rec = new stdClass();
        $rec->perSrcClassId = core_Classes::getId($perSrcClassId);
        $rec->perSrcObjectId = $perSrcObjectId;
        $rec->body = $text;
        $rec->subject = $subject;
        $rec->state = 'draft';
        
        // Задаваме стойности за останалите полета
        foreach ((array)$otherParams as $fieldName => $value) {
            if ($rec->$fieldName) continue;
            $rec->$fieldName = $value;
        }
        
        // Ако не е зададен имейл на изпращача, да се използва дефолтният му 
        if (!$rec->from) {
            $rec->from = email_Outgoings::getDefaultInboxId();
        }
        
        // Записваме
        $id = self::save($rec);
        
        return $id;
    }
    
    
    /**
     * Активира имейла, като добавя и списъка с имейлите
     * 
     * @param integer|object $id
     * @param integer $sendPerCall
     */
    public static function activateEmail($id, $sendPerCall=5)
    {
        // Записа
        $rec = self::getRec($id);
        
        // Обновяваме списъка с имейлите
        $updateCnt = self::updateEmailList($id);
        
        // Активираме имейла
        $rec->state = 'active';
        $rec->activatedBy = core_Users::getCurrent();
        $rec->sendPerCall = $sendPerCall;
        self::save($rec);
        
        return $updateCnt;
    }
    
    
    /**
     * Обновява списъка с имейлите
     * 
     * @param integer|object $id
     * 
     * @return integer
     */
    protected static function updateEmailList($id)
    {
        // Записа
        $rec = self::getRec($id);
        
        // Инстанция на класа за персонализация
        $srcClsInst = cls::get($rec->perSrcClassId);
        
        // Масива с данните за персонализация
        $personalizationArr = $srcClsInst->getPresonalizationArr($rec->perSrcObjectId);
        
        // Масив с типовете на полетата
        $descArr = $srcClsInst->getPersonalizationDescr($rec->perSrcObjectId);
        
        // Масив с всички имейл полета
        $emailFieldsArr = self::getEmailFields($descArr);
        
        // Обновяваме листа и връщаме броя на обновленията
        $updateCnt = blast_EmailSend::updateList($rec->id, $personalizationArr, $emailFieldsArr);
        
        return $updateCnt;
    }
    
    
    
    /**
     * Връща записа
     * 
     * @param integer|object $id
     * 
     * @return object
     */
    protected static function getRec($id)
    {
        // Ако е обект, приемаме, че е подаден самия запис
        if (is_object($id)) {
            $rec = $id;
        } else {
            // Ако е id, фечваме записа
            $rec = self::fetch($id);
        }
        
        return $rec;
    }
    
    
	/**
     * Проверява дали има имейли за изпращане, персонализира ги и ги изпраща
     * Вика се от `cron`
     */
    protected function sendEmails()
    {
        // Всички активни или чакащи имейли, на които им е дошло времето за стартиране
        $query = blast_Emails::getQuery();
        $now = dt::verbal2mysql();
        $query->where("#startOn <= '{$now}'");
        $query->where("#state = 'active'");
        $query->orWhere("#state = 'pending'");
        
        //Проверяваме дали имаме запис, който не е затворен и му е дошло времето за активиране
        while ($rec = $query->fetch()) {
            
            // Променяме състоянието от чакащо в активно
            if ($rec->state == 'pending') {
                $rec->state = 'active';
                $this->save($rec);
            }
            
            // Вземаме данните за имейлите, до които ще пращаме
            $dataArr = blast_EmailSend::getDataArrForEmailId($rec->id, $rec->sendPerCall);
            
            // Ако няма данни, затваряме 
            if (!$dataArr) {
                $rec->state = 'closed';
                $this->save($rec);
                continue;
            }
            
            // Инстанция на обекта
            $srcClassInst = cls::get($rec->perSrcClassId);
            
            // Масив с полетата и описаниите за съответния обект
            $descArr = $srcClassInst->getPersonalizationDescr($rec->perSrcObjectId);
            
            // Маркираме имейлите, като изпратени
            blast_EmailSend::markAsSent($dataArr);
            
            // Вземаме всички полета, които могат да бъдат имейли
            $emailPlaceArr = self::getEmailFields($descArr);
            
            // Ако няма полета за имейли, няма смисъл да се праща
            if (!$emailPlaceArr) continue;
            
            // Обхождаме всички получени данни
            foreach ((array)$dataArr as $detId => $detArr) {
                
                $toEmail = '';
                
                // Обединяваме всички възможни имейли
                foreach ($emailPlaceArr as $place => $type) {
                    $emailsStr = $emailsStr ? ', ' . $detArr[$place] : $detArr[$place];
                }
                
                // Вземаме имейлите
                $emailsArr = type_Emails::toArray($emailsStr);
                
                // Първия валиден имейл, който не е в блокорани, да е получателя
                foreach ((array)$emailsArr as $email) {
                    if (blast_BlockedEmails::isBlocked($email)) continue;
                    $toEmail = $email;
                    break;
                }
                
                // Ако няма имейл, нямя до кого да се праща
                if (!$toEmail) continue;
                
                // Клонираме записа
                $cRec = clone $rec;
                
                // Ако е системяния потребител, го спираме
                $isSystemUser = core_Users::isSystemUser();
                if ($isSystemUser) {
                    core_Users::cancelSystemUser();
                }
                
                // Имейла да се рендира и да се праща с правата на активатора
                core_Users::sudo($cRec->activatedBy);
                
                // Задаваме екшъна за изпращането
                log_Documents::pushAction(
                    array(
                        'containerId' => $cRec->containerId,
                        'threadId' => $cRec->threadId,
                        'action' => log_Documents::ACTION_SEND,
                        'data' => (object)array(
                    		'sendedBy' => core_Users::getCurrent(),
                            'from' => $cRec->from,
                            'to' => $toEmail,
                            'detId' => $detId,
                        )
                    )
                );
                
                // Вземаме персонализирания имейл за съответните данни
                $body = $this->getEmailBody($cRec, $detArr, TRUE);
                
                // Деескейпваме шаблоните в текстовата част
                $body->text = core_ET::unEscape($body->text);
                
                // Опитваме се да изпратим имейла
                try {
                    //Извикваме функцията за изпращане на имейли
                    $status = email_Sent::sendOne(
                        $cRec->from,
                        $toEmail,
                        $body->subject,
                        $body,
                        array(
                           'encoding' => $cRec->encoding,
                           'no_thread_hnd' => TRUE
                        )
                    );
                } catch (Exception $e) {
                    $status = FALSE;
                }
                
                // Флушваме екшъна
                log_Documents::flushActions();
                
                // Връщаме стария потребител
                core_Users::exitSudo();
                
                // Ако е бил стартиран системия потребител, пак го стартираме
                if ($isSystemUser) {
                    
                    //Стартираме системния потребител
                    core_Users::forceSystemUser();
                }
                
                // Ако имейлът е изпратен успешно, добавяме времето на изпращане
                if ($status) {
                    
                    // Задаваме времето на изпращане и имейла изпращане
                    blast_EmailSend::setTimeAndEmail(array($detId => $toEmail));
                    
                } else {
                    // Ако възникне грешка при изпращане, записваме имейла, като върнат
                    log_Documents::returned($body->__mid);
                }
            }
        }
    }
    
    
	/**
     * Подготвяме данните в rec'а
     * 
     * @param object $rec - Обект с данните
     * @param array $detArr
     */
    protected function prepareRec(&$rec, $detArr)
    {
        // Заместваме данните
        $this->replaceAllData($rec, $detArr);
    }
    
    
    /**
     * Замества плейсхолдърите с тяхната стойност
     * 
     * @param object $rec
     * @param array $detArr
     */
    protected function replaceAllData(&$rec, $detArr)
    {
        //Масив с всички полета, които ще се заместят
        $fieldsArr = array();
        $fieldsArr['subject'] = 'subject';
        $fieldsArr['recipient'] = 'recipient';
        $fieldsArr['attn'] = 'attn';
        $fieldsArr['email'] = 'email';
        $fieldsArr['tel'] = 'tel';
        $fieldsArr['fax'] = 'fax';
        $fieldsArr['country'] = 'country';
        $fieldsArr['pcode'] = 'pcode';
        $fieldsArr['place'] = 'place';
        $fieldsArr['address'] = 'address';
        $fieldsArr['body'] = 'body';
        
        //Обхождаме всички данни от антетката
        foreach ($fieldsArr as $header) {
            
            //Ако нямаме въведена стойност, прескачаме
            if (!$rec->$header) continue;
            
            //Заместваме данните в антетката
            $rec->$header = $this->replacePlaces($rec->$header, $detArr);
        }
    }
    
    
    /**
     * Заместваме всички шаблони, с техните стойности
     * 
     * @param string $resStr - стринга, който ще се замества
     * @param array $detArr - масив със стойностите за плейсхолдерите
     * 
     * @return string
     */
    protected function replacePlaces($resStr, $detArr)
    {
        // Заместваме плейсхолдерите
        $resStr = new ET($resStr);
        $resStr->placeArray($detArr);
        
        return core_ET::unEscape($resStr->getContent());
    }
    
    
    /**
     * Връща допълнителните плейсхолдерите, за този тип документ
     * 
     * @param object $rec
     * 
     * @return array
     */
    protected static function getEmailOtherPlaces_($rec)
    {
        $me = get_called_class();
        
        $resArr = array();
        
        $mid = doc_DocumentPlg::getMidPlace();
        $urlBg = htmlentities(toUrl(array($me, 'Unsubscribe', $rec->id, 'm' => $mid, 'l' => 'bg'), 'absolute'), ENT_COMPAT | ENT_HTML401, 'UTF-8');
        $urlEn = htmlentities(toUrl(array($me, 'Unsubscribe', $rec->id, 'm' => $mid, 'l' => 'en'), 'absolute'), ENT_COMPAT | ENT_HTML401, 'UTF-8');

        // Създаваме линковете
        $resArr['otpisvane'] = "[link={$urlBg}]тук[/link]";
        $resArr['unsubscribe'] = "[link={$urlEn}]here[/link]";
        $resArr['mid'] = $mid;
        
        return $resArr;
    }
    
    
	/**
     * Връща тялото на съобщението
     * 
     * @param object $rec - Данни за имейла
     * @param int $detId - id на детайла на данните
     * @param boolen $sending - Дали ще изпращаме имейла
     * 
     * @return object $body - Обект с тялото на съобщението
     * 		   string $body->html - HTMl частта
     * 		   string $body->text - Текстовата част
     *         array  $body->attachments - Прикачените файлове
     */
    protected function getEmailBody($rec, $detArr, $sending=FALSE)
    {
        $body = new stdClass();
        
        //Вземаме HTML частта
        $body->html = $this->getEmailHtml($rec, $detArr, $sending);
        
        //Вземаме текстовата част
        $body->text = $this->getEmailText($rec, $detArr);
        
        // Конвертираме към въведения енкодинг
        if ($rec->encoding == 'ascii') {
            $body->html = str::utf2ascii($body->html);
            $body->text = str::utf2ascii($body->text);
        } elseif (!empty($rec->encoding) && $rec->encoding != 'utf-8') {
            $body->html = iconv('UTF-8', $rec->encoding . '//IGNORE', $body->html);
            $body->text = iconv('UTF-8', $rec->encoding . '//IGNORE', $body->text);
        }
        
        $docsArr = array();
        $attFhArr = array();
        
        if ($sending) {
            
            //Дали да прикачим файловете
            if ($rec->attachments) {
                $attachArr = type_Set::toArray($rec->attachments);
            }
            
            //Ако сме избрали да се добавят документите, като прикачени
            if ($attachArr['documents']) {
                
                $nRec = clone $rec;
                
                $this->prepareRec($nRec, $detArr);
                
                //Вземаме манупулаторите на документите
                $docsArr = $this->getDocuments($nRec);
                
                $docsFhArr = array();
                
                foreach ((array)$docsArr as $attachDoc) {
                    // Използваме интерфейсен метод doc_DocumentIntf::convertTo за да генерираме
                    // файл със съдържанието на документа в желания формат
                    $fhArr = $attachDoc['doc']->convertTo($attachDoc['ext'], $attachDoc['fileName']);
                
                    $docsFhArr += $fhArr;
                }
                
            }
            
            //Ако сме избрали да се добавят файловете, като прикачени
            if ($attachArr['files']) {
                
                //Вземаме манупулаторите на файловете
                $attFhArr = $this->getAttachments($rec);
                
                // Манипулаторите да са и в стойноситите им
                $attFhArr = array_keys($attFhArr);
                $attFhArr = array_combine($attFhArr, $attFhArr);
            }
            
            //Манипулаторите на файловете в масив
            $body->attachmentsFh = (array)$attFhArr;
            $body->documentsFh = (array)$docsFhArr;
            
            //id' тата на прикачените файлове с техните
            $body->attachments = keylist::fromArray(fileman_Files::getIdFromFh($attFhArr));
            $body->documents = keylist::fromArray(fileman_Files::getIdFromFh($docsFhArr));
        }
        
        // Други необходими данни за изпращането на имейла
        $body->containerId = $rec->containerId;
        $body->__mid = $rec->__mid;
        $body->subject = $rec->subject;
        
        return $body;
    }
    
    
	/**
     * Взема HTML частта на имейл-а
     * 
     * @param object $rec     - Данни за имейла
     * @param array $detArr - Масив с данните
     * @param boolean $sending - Дали се изпраща в момента
     * 
     * @return core_ET $res
     */
    protected function getEmailHtml($rec, $detArr, $sending)
    {
        // Опциите за генериране на тялото на имейла
        $options = new stdClass();
        
        // Добавяме обработения rec към опциите
        $options->rec = $rec;
        $options->__detArr = $detArr;
        
        // Вземаме тялото на имейла
        $res = self::getDocumentBody($rec->id, 'xhtml', $options);
        
        // За да вземем mid'а който се предава на $options
        $rec->__mid = $options->rec->__mid;
        
        // За да вземем subject'а със заменените данни
        $rec->subject = $options->rec->subject;

        //Ако изпращаме имейла
        if ($sending) {
            //Добавяме CSS, като inline стилове            
            $css = file_get_contents(sbf('css/common.css', "", TRUE)) .
                "\n" . file_get_contents(sbf('css/Application.css', "", TRUE)) . "\n" . file_get_contents(sbf('css/email.css', "", TRUE));
             
            $res = '<div id="begin">' . $res->getContent() . '<div id="end">'; 
             
            // Вземаме пакета
            $conf = core_Packs::getConfig('csstoinline');
            
            // Класа
            $CssToInline = $conf->CSSTOINLINE_CONVERTER_CLASS;
            
            // Инстанция на класа
            $inst = cls::get($CssToInline);
            
            // Стартираме процеса
            $res =  $inst->convert($res, $css);  
            
            $res = str::cut($res, '<div id="begin">', '<div id="end">');    
        }
        
        //Изчистваме HTMl коментарите
        $res = email_Outgoings::clearHtmlComments($res);
        
        return $res;
    }
    
    
	/**
     * Взема текстовата част на имейл-а
     * 
     * @param object $rec - Данни за имейла
     * @param array $detArr - Масив с данните
     * 
     * @return core_ET $res 
     */
    protected function getEmailText($rec, $detArr)
    {
        // Опциите за генериране на тялото на имейла
        $options = new stdClass();
        
        // Добавяме обработения rec към опциите
        $options->rec = $rec;
        $options->__detArr = $detArr;
        
        // Вземаме тялото на имейла
        $res = self::getDocumentBody($rec->id, 'plain', $options);
        
        // За да вземем mid'а който се предава на $options
        $rec->__mid = $options->rec->__mid;
        
        // За да вземем subject'а със заменените данни
        $rec->subject = $options->rec->subject;
        
        return $res;
    }
    
    
    /**
     * Намира предполагаемия език на текста
     * 
     * @param text $body - Текста, в който ще се търси
     * @param NULL|string $lang - Език
     * 
     * @return string $lg - Двубуквеното означение на предполагаемия език
     */
    protected static function getLanguage($body, $lang=NULL)
    {
        // Масив с всички допустими езици за системата
        $langArr = arr::make(EF_LANGUAGES, TRUE);
        
        // Ако подадения език е в допустимите, да се използва
        if ($lang && $langArr[$lang]) {
            $lg = $lang;
        } else {
            // Масив с всички предполагаеми езици
            $lg = i18n_Language::detect($body);
            
            // Ако езика не е допустимите за системата, да е английски
            if (!$langArr[$lg]) {
                $lg = 'en';
            }
        }
        
        return $lg;
    }
    
    
    /**
     * Вземаме всички прикачени документи
     * 
     * @param object $rec
     * 
     * @return array $documents - Масив с прикачените документи
     */
    function getDocuments($rec)
    {
        $docsArr = $this->getPossibleTypeConvertings($rec);
        $docs = array();
        
        // Обхождаме всички документи
        foreach ($docsArr as $fileName => $checked) {
            
            // Намираме името и разширението на файла
            if (($dotPos = mb_strrpos($fileName, '.')) !== FALSE) {
                $ext = mb_substr($fileName, $dotPos + 1);
            
                $docHandle = mb_substr($fileName, 0, $dotPos);
            } else {
                $docHandle = $fileName;
            }
            
            $doc = doc_Containers::getDocumentByHandle($docHandle);
            expect($doc);
            
            // Масив с манипулаторите на конвертиранети файлове
            $docs[] = compact('doc', 'ext', 'fileName');
        }
        
        return $docs;
    }
    
    
    /**
     * Връща масив с полетата, които са инстанции на type_Email или type_Emails
     * 
     * @param array $descArr
     * 
     * @return array
     */
    protected static function getEmailFields($descArr)
    {
        $fieldsArr = array();
        
        // Обхождаме всички подадени полета и проверяваме дали не са инстанции на type_Email или type_Emails
        foreach ((array)$descArr as $name => $type) {
            if (($type instanceof type_Email) || ($type instanceof type_Emails)) {
                $fieldsArr[$name] = $type;
            }
        }
        
        return $fieldsArr;
    }
    

	/**
     * Екшън за активиране, съгласно правилата на фреймуърка
     */
    function act_Activation()
    {
        // Права за работа с екшън-а
        $this->requireRightFor('activate');
        
        $id = Request::get('id', 'int');
        
        $retUrl = getRetUrl();
        
        // URL' то където ще се редиректва при отказ
        $retUrl = ($retUrl) ? ($retUrl) : (array($this, 'single', $id));

        // Вземаме формата към този модел
        $form = $this->getForm();
        
        // Въвеждаме id-то (и евентуално други silent параметри, ако има)
        $form->input(NULL, 'silent');
        
        // Очакваме да има такъв запис
        expect($rec = $this->fetch($form->rec->id));
        
        // Очакваме потребителя да има права за активиране
        $this->requireRightFor('activate', $rec);
        
        // Въвеждаме съдържанието на полетата
        $form->input('sendPerCall, startOn');
        
        // Инстанция на избрания клас
        $srcClsInst = cls::get($rec->perSrcClassId);
        
        // Ако формата е изпратена без грешки
        if($form->isSubmitted()) {
            
            // Ако има задедена дата
            if ($form->rec->startOn) {
                
                // Ако в записа няма зададена дата
                if (!$rec->startOn) {
                    
                    // Вземаме текущото време
                    $date = dt::now();
                } else {
                    
                    // Вземаме времото от записа
                    $date = $rec->startOn;
                }
                
                // Вземаме разликата в секундите
                $secB = dt::secsBetween($form->rec->startOn, $date);
                
                // Ако е предишна дата
                if ($secB < 0) {
                    
                    // Сетваме грешка
                    $form->setError('startOn', 'Не може да въведе минала дата');
                }
            }
        }
        
        // Ако формата е изпратена без грешки, то активираме, ... и редиректваме
        if($form->isSubmitted()) {
            
            $form->rec->activatedBy = core_Users::getCurrent();
            
            // Ако е въведена коректна дата, тогава използва нея
            // Ако не е въведено нищо, тогава използва сегашната дата
            // Ако е въведена грешна дата показва съобщение за грешка
            if (!$form->rec->startOn) {
                $form->rec->startOn = dt::verbal2mysql();
            }
            
            // Вземаме секундите между сегашното време и времето на стартиране
            $sec = dt::secsBetween($form->rec->startOn, dt::now());
            
            // Ако са по - малко от 60 секунди
            if ($sec < 60) {
                
                // Активираме
                $form->rec->state = 'active';
            } else {
                
                // Сменя статуса на чакащ
                $form->rec->state = 'pending';
            }
            
            // Упдейтва състоянието и данните за имейл-а
            blast_Emails::save($form->rec, 'state,startOn,sendPerCall,activatedBy,modifiedBy,modifiedOn');
            
            // Обновяваме списъка с имейлите
            $updateCnt = self::updateEmailList($form->rec->id);
            
            // В зависимост от броя на обновления променяме състоянието
            if ($updateCnt) {
                if ($updateCnt == 1) {
                    $msg = 'Добавен е|* ' . $updateCnt . ' |запис';
                } else {
                    $msg = 'Добавени са|* ' . $updateCnt . ' |записа';
                }
            } else {
                $msg = 'Не са добавени нови записи';
            }
            
            // Добавяме ново съобщени
            status_Messages::newStatus($msg);
            
            // След успешен запис редиректваме
            $link = array('blast_Emails', 'single', $rec->id);
            
            // Редиректваме
            return new Redirect($link, "Успешно активирахте бласт имейл-а");
        } else {
            
            // Стойности по подразбиране
            $perMin = $rec->sendPerCall ? $rec->sendPerCall : 5;
            $form->setDefault('sendPerCall', $perMin);
            $form->setDefault('startOn', $rec->startOn);
        }
        
        // Задаваме да се показват само полетата, които ни интересуват
        $form->showFields = 'sendPerCall, startOn';
        
        // Добавяме бутоните на формата
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close16.png');
        
        // Добавяме титлата на формата
        $form->title = "Стартиране на масово разпращане";
        $subject = $this->getVerbal($rec, 'subject');
        $date = dt::mysql2verbal($rec->createdOn);
        
        // Добавяме във формата информация, за да знаем за кое писмо става дума
        $form->info = new ET ('[#1#]', tr("|*<b>|Писмо|*<i style='color:blue'>: {$subject} / {$date}</i></b>"));
        
        // Вземаме един запис за персонализиране
        $personalizationArr = $srcClsInst->getPresonalizationArr($rec->perSrcObjectId, 1);
        
        // Вземаме елемента
        $detArr = array_pop($personalizationArr);
        
        // Тялото на съобщението
        $body = $this->getEmailBody($rec, $detArr);
        
        // Деескейпваме плейсхолдерите в текстовата част
        $body->text = core_ET::unEscape($body->text);
        
        // Получаваме изгледа на формата
        $tpl = $form->renderHtml();

        // Добавяме превю на първия бласт имейл, който ще изпратим
        $preview = new ET("<div class='preview-holder'><div style='margin-top:20px; margin-bottom:-10px; padding:5px;'><b>" . tr("Примерен имейл") . "</b></div><div class='scrolling-holder'>[#BLAST_HTML#]<div class='clearfix21'></div><pre class=\"document\">[#BLAST_TEXT#]</pre></div></div>");
        
        // Добавяме към шаблона
        $preview->append($body->html, 'BLAST_HTML');
        $preview->append(core_Type::escape($body->text), 'BLAST_TEXT');

        // Добавяме изгледа към главния шаблон
        $tpl->append($preview);

        return self::renderWrapping($tpl);
    }
    
    
    /**
     * Обновява списъка с имейлите
     */
    function act_Update()
    {
        // Права за работа с екшън-а
        $this->requireRightFor('update');
        
        $id = Request::get('id', 'int');
        
        // Очакваме да има такъв запис
        expect($rec = $this->fetch($id));
        
        // URL' то където ще се редиректва при отказ
        $retUrl = getRetUrl();
        $retUrl = ($retUrl) ? ($retUrl) : (array($this, 'single', $id));

        // Очакваме потребителя да има права за обновяване на съответния запис
        $this->requireRightFor('update', $rec);
        
        // Обновяваме списъка с имейлите
        $updateCnt = blast_Emails::updateEmailList($rec);
        
        // В зависимост от броя на обновления променяме състоянието
        if ($updateCnt) {
            if ($updateCnt == 1) {
                $updateMsg = 'Добавен е|* ' . $updateCnt . ' |запис';
            } else {
                $updateMsg = 'Добавени са|* ' . $updateCnt . ' |записа';
            }
            
            $nRec = new stdClass();
            
            // Ако състоянието е затворено, активираме имейла
            if ($rec->state == 'closed') {
                $nRec->id = $rec->id;
                $nRec->state = 'active';
                $this->save($nRec);
            }
        } else {
            $updateMsg = 'Няма нови записи за добавяне';
        }
        
        return new Redirect($retUrl, $updateMsg);
    }
    
    
    /**
     * Екшън за спиране
     */
    function act_Stop()
    {
        $this->requireRightFor('stop');
        
        //Очакваме да има такъв запис
        expect($id = Request::get('id', 'int'));
        
        expect($rec = $this->fetch($id));
        
        // Очакваме да има права за записа
        $this->requireRightFor('stop', $rec);
        
        //Очакваме потребителя да има права за спиране
        $this->haveRightFor('stop', $rec);
        
        $link = array('blast_Emails', 'single', $rec->id);
        
        //Променяме статуса на спрян
        $recUpd = new stdClass();
        $recUpd->id = $rec->id;
        $recUpd->state = 'stopped';
        
        blast_Emails::save($recUpd);
        
        // Добавяме съобщение в статуса
        status_Messages::newStatus(tr("Успешно спряхте бласт имейл-а"));
        
        // Редиректваме
        return redirect($link);
    }
    
    
	/**
     * Добавяне или премахване на имейл в блокираните
     */
    function act_Unsubscribe()
    {
    	$conf = core_Packs::getConfig('blast');
    	
        // GET променливите от линка
        $mid = Request::get("m");
        $lang = Request::get("l");
        $id = Request::get('id', 'int');
        $uns = Request::get("uns");
        
        $rec = $this->fetch($id);
        expect($rec);
        
        $cid = $rec->containerId; 
        
        expect($cid && $mid);
        
        // Сменяме езика за да може да  се преведат съобщенията
        core_Lg::push($lang);

        // Шаблон
        $tpl = new ET("<div class='unsubscribe'> [#text#] </div>");
        
        //Проверяваме дали има такъв имейл
        if (!($hRec = log_Documents::fetchHistoryFor($cid, $mid))) {
            
            //Съобщение за грешка, ако няма такъв имейл
            $tpl->append("<p>" . tr($conf->BGERP_BLAST_NO_MAIL) . "</p>", 'text');
            
            // Връщаме предишния език
            core_Lg::pop();
            
            return $tpl;
        }
        
        // Имейла на потребителя
        $email = $hRec->data->to;
        
        // Ако имейл-а е в листата на блокираните имейли или сме натиснали бутона за премахване от листата
        if (($uns == 'del') || ((!$uns) && (blast_BlockedEmails::isBlocked($email)))) {
            
            // Какво действие ще правим след натискане на бутона
            $act = 'add';
            
            // Какъв да е текста на бутона
            $click = 'Добави';
            
            // Добавяме имейл-а в листата на блокираните
            if ($uns) {
                
                blast_BlockedEmails::add($email);
            }
            
            $tpl->append("<p>" . tr($conf->BGERP_BLAST_SUCCESS_REMOVED) . "</p>", 'text');
        } elseif ($uns == 'add') {
            $act = 'del';
            $click = 'Премахване';
            
            // Премахваме имейл-а от листата на блокираните имейли
            blast_BlockedEmails::remove($email);
            $tpl->append("<p>" . tr($conf->BGERP_BLAST_SUCCESS_ADD) . "</p>", 'text');
        } else {
            $act = 'del';
            $click = 'Премахване';
            
            // Текста, който ще се показва при първото ни натискане на линка
            $tpl->append("<p>" . tr($conf->BGERP_BLAST_UNSUBSCRIBE) . "</p>", 'text');
        }
        
        $currUrl = getCurrentUrl();
        $currUrl['uns'] = $act;
        
        // Генерираме бутон за отписване или вписване
        $link = ht::createBtn($click, $currUrl);
        
        $tpl->append($link, 'text');
        
        // Връщаме предишния език
        core_Lg::pop();
        
        return $tpl;
    }
    
    
	/**
     * Изпълнява се след подготвяне на формата за редактиране
	 * 
	 * @param blast_Emails $mvc
	 * @param object $res
	 * @param object $data
	 */
    static function on_AfterPrepareEditForm(&$mvc, &$res, &$data)
    {
        $form = $data->form;
        
        // Ако не е подаден клас да е blast_List
        $listClassId = blast_Lists::getClassId();
        $data->form->setDefault('perSrcClassId', $listClassId);
        
        // Инстанция на източника за персонализация
        $perClsInst = cls::get($data->form->rec->perSrcClassId);
        
        // id на обекта на персонализация
        $perSrcObjId = $data->form->rec->perSrcObjectId;
        
        $perOptArr = array();
        
        // Ако е подаден такъв обект за персонализация
        if ($perSrcObjId) {
            
            // Очакваме да може да персонализира
            expect($perClsInst->canUsePersonalization($perSrcObjId));
            
            // Заглавието за персонализация
            $perTitle = $perClsInst->getPersonalizationTitle($perSrcObjId, FALSE);
            
            // Да може да се избере само подадения обект
            $perOptArr[$perSrcObjId] = $perTitle;
            $form->setOptions('perSrcObjectId', $perOptArr);
        }
        
        // Ако създаваме от blast_List и не е подаден обект
        // За съвместимост със старите системи
        if ($listClassId == $data->form->rec->perSrcClassId) {
            if (!$data->form->rec->perSrcObjectId) {
                
                // Взеамем всички възбможни опции за персонализация
                $perOptArr = $perClsInst->getPersonalizationOptions();
                
                // Обхождаме всички опции
                foreach ((array)$perOptArr as $id => $name) {
                    
                    // Проверяваме дали може да се персонализира
                    // Тряба да се проверява в getPersonalizationOptions()
//                    if (!$perClsInst->canUsePersonalization($id)) continue;
                    
                    // Описание на полетата
                    $descArr = $perClsInst->getPersonalizationDescr($id);
                    
                    // Ако няма полета за имейл
                    if (!self::getEmailFields($descArr)) {
                        
                        // Премахваме от опциите
                        unset($perOptArr[$id]);
                    }
                }
                
                // Очакваме да има поне една останала опция
                expect($perOptArr, 'Няма източник за персонализация');
                
                // Задаваме опциите
                $form->setOptions('perSrcObjectId', $perOptArr);
            }
        }
        
        // Само имейлите достъпни до потребителя да се показват
        $emailOption = email_Inboxes::getFromEmailOptions($form->rec->folderId);
        $form->setOptions('from', $emailOption);
        
        // Ако създаваме нов, тогава попълва данните за адресата по - подразбиране
        $rec = $data->form->rec;
        
        if ((!$rec->id) && (!Request::get('clone'))) {
            
            // По подразбиране да е избран текущия имейл на потребителя
            $form->setDefault('from', email_Outgoings::getDefaultInboxId($rec->folderId));
            
            $rec->recipient = '[#company#]';
            $rec->attn = '[#person#]';
            $rec->email = '[#email#]';
            $rec->tel = '[#tel#]';
            $rec->fax = '[#fax#]';
            $rec->country = '[#country#]';
            $rec->pcode = '[#pCode#]';
            $rec->place = '[#place#]';
            $rec->address = '[#address#]';
        }
    }
    
    
    /**
	 * Изпълнява се след въвеждането на даните от формата
     * 
     * @param blast_Emails $mvc
     * @param core_Form $form
     */
    function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = $form->rec;
        
        // Ако сме субмитнали формата
        if ($form->isSubmitted()) {
            
            // Ако ще се прикачат документи или файлове
            // Проверяваме разширенията им
            if ($rec->attachments) {
                
                $attachArr = type_Set::toArray($rec->attachments);
                
                if ($attachArr['documents']) {
                    // Прикачените документи
                    $docsArr = $mvc->getDocuments($rec);
                    $docsSizesArr = $mvc->getDocumentsSizes($docsArr);
                }
                if ($attachArr['files']) {
                    // Прикачените файлове
                    $attachmentsArr = $mvc->getAttachments($rec);
                    $filesSizesArr = $mvc->getFilesSizes($attachmentsArr);
                }
                   
                // Проверяваме дали размерът им е над допсутимият
                $allAttachmentsArr = array_merge((array)$docsSizesArr, (array)$filesSizesArr);
                if (!$mvc->checkMaxAttachedSize($allAttachmentsArr)) {
                    
                    // Вербалният размер на файловете и документите
                    $docAndFilesSizeVerbal = $mvc->getVerbalSizesFromArray($allAttachmentsArr);
                    
                    if ($attachArr['documents'] && $attachArr['files']) {
                        $str = "файлове и документи";
                    } else if ($attachArr['documents']) {
                        $str = "документи";
                    } else {
                        $str = "файлове";
                    }
                    
                    $form->setWarning('attachments', "Размерът на прикачените {$str} е|*: " . $docAndFilesSizeVerbal);
                }
            }
        }
        
        // Ако сме субмитнали формата
        // Проверява за плейсхолдери, които липсват в източника
        if ($form->isSubmitted()) {
            
            $classInst = cls::get($rec->perSrcClassId);
            
            // Масив с всички записи
            $recArr = (array)$form->rec;
            
            // Вземаме Относно и Съобщение
            $bodyAndSubject = $recArr['body'] . ' ' . $recArr['subject'];
            
            // Масив с данни от плейсхолдера
            $nRecArr['recipient'] = $recArr['recipient'];
            $nRecArr['attn'] = $recArr['attn'];
            $nRecArr['email'] = $recArr['email'];
            $nRecArr['tel'] = $recArr['tel'];
            $nRecArr['fax'] = $recArr['fax'];
            $nRecArr['country'] = $recArr['country'];
            $nRecArr['pcode'] = $recArr['pcode'];
            $nRecArr['place'] = $recArr['place'];
            $nRecArr['address'] = $recArr['address'];
            
            // Обикаляме всички останали стойности в масива
            foreach ($nRecArr as $field) {
                
                // Всички данни ги записваме в една променлива
                $allRecsWithPlaceHolders .= ' ' . $field;    
            }

            // Създаваме шаблон
            $tpl = new ET($allRecsWithPlaceHolders);
            
            // Вземаме всички шаблони, които се използват
            $allPlaceHolder = $tpl->getPlaceHolders();
            
            // Шаблон на Относно и Съобщение
            $bodyAndSubTpl = new ET($bodyAndSubject);
            
            // Вземаме всички шаблони, които се използват
            $bodyAndSubPlaceHolder = $bodyAndSubTpl->getPlaceHolders();
            
            // Другите плейсхолдери
            $otherDetArr = self::getEmailOtherPlaces($rec);
            
            // Полетата и описаниите им, които ще се използва за персонализация
            $onlyAllFieldsArr = $classInst->getPersonalizationDescr($rec->perSrcObjectId);
            
            // Обединяване плейсхолдерите
            $onlyAllFieldsArr = (array)$onlyAllFieldsArr + (array)$otherDetArr;
            
            // Създаваме масив с ключ и стойност имената на полетата, които ще се заместват
            foreach ((array)$onlyAllFieldsArr as $field => $dummy) {
                // Тримваме полето
                $field = trim($field);
                
                // Името в долен регистър
                $field = strtolower($field);
                
                // Добавяме в масива
                $fieldsArr[$field] = $field;
            }
            
            // Премахваме дублиращите се плейсхолдери
            $allPlaceHolder = array_unique($allPlaceHolder);
            
            // Търсим всички полета, които сме въвели, но ги няма в полетата за заместване
            foreach ($allPlaceHolder as $placeHolder) {
                
                $placeHolderL = strtolower($placeHolder);
                
                // Ако плейсхолдера го няма във листа
                if (!$fieldsArr[$placeHolderL]) {
                    
                    // Добавяме към съобщението за предупреждение
                    $warning .= ($warning) ? ", {$placeHolder}" : $placeHolder;
                    
                    // Стринг на плейсхолдера
                    $placeHolderStr = "[#" . $placeHolder . "#]";
                    
                    // Добавяме го в масива
                    $warningPlaceHolderArr[$placeHolderStr] = $placeHolderStr;
                }
            }
            
            // Премахваме дублиращите се плейсхолдери
            $bodyAndSubPlaceHolder = array_unique($bodyAndSubPlaceHolder);
            
            // Търсим всички полета, които сме въвели, но ги няма в полетата за заместване
            foreach ($bodyAndSubPlaceHolder as $placeHolder) {
                
                $placeHolderL = strtolower($placeHolder);
                
                // Ако плейсхолдера го няма във листа
                if (!$fieldsArr[$placeHolderL]) {
                    
                    // Добавяме към съобщението за грешка
                    $error .= ($error) ? ", {$placeHolder}" : $placeHolder;
                }
            }

            // Показваме грешка, ако има шаблони, които сме въвели в повече в Относно и Съощение
            if ($error) {
                $form->setError('*', "|Шаблоните, които сте въвели ги няма в източника|*: {$error}");    
            }
            
            // Показваме предупреждение за останалите шаблони
            if ($warning) {
                
                // Сетваме грешката
                $form->setWarning('*', "|Шаблоните, които сте въвели ги няма в източника|*: {$warning}"); 
                
                // При игнориране на грешката
                if (!$form->gotErrors()) {
                    
                    // Обхождаме масива с стойност
                    foreach ($nRecArr as $field => $val) {
                        
                        // Премахваме всички плейсхолдери, които не се използват
                        $val = str_ireplace((array)$warningPlaceHolderArr, '', $val);    
                        
                        // Добавяме към записа
                        $form->rec->{$field} = $val;
                    }
                }
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, необходимо за това действие
     * 
     * @param blast_Emails $mvc
     * @param string $roles
     * @param string $action
     * @param object $rec
     */
    static function on_AfterGetRequiredRoles($mvc, &$roles, $action, $rec)
    {
        // Трябва да има права за сингъла на документа, за да може да активира, спира и/или обновява
        if ((($action == 'activate') || ($action == 'stop') || ($action == 'update')) && $rec) {
            if (!$mvc->haveRightFor('single', $rec)) {
                $roles = 'no_one';
            }
        }
    }
    
    
	/**
	 * 
	 * 
	 * @param blast_Emails $mvc
	 * @param object $row
	 * @param object $rec
	 */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        //При рендиране на листовия изглед показваме дали ще се прикачат файловете и/или документите
        $attachArr = type_Set::toArray($rec->attachments);
        if ($attachArr['files']) $row->Files = tr('Файловете');
        if ($attachArr['documents']) $row->Documents = tr('Документите');
        
        // Манипулатора на документа
        $row->handle = $mvc->getHandle($rec->id);
        
        // Линка към обекта, който се използва за персонализация
        if ($rec->perSrcClassId && $rec->perSrcObjectId) {
            $inst = cls::get($rec->perSrcClassId);
            if ($inst->canUsePersonalization($rec->perSrcObjectId)) {
                $row->srcLink = $inst->getPersonalizationSrcLink($rec->perSrcObjectId);
            }
        }
    }
    
    
    /**
     * Добавя съответните бутони в лентата с инструменти, в зависимост от състоянието
     * 
     * @param blast_Emails $mvc
     * @param object $data
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;
        $state = $data->rec->state;
        
        if (($state == 'draft') || ($state == 'stopped')) {
            
            // Добавяме бутона Активирай, ако състоянието е чернова или спряно
            
            if ($mvc->haveRightFor('activate', $rec->rec)) {
                $data->toolbar->addBtn('Активиране', array($mvc, 'Activation', $rec->id), 'ef_icon = img/16/lightning.png');    
            }
        } else {
            
            // Добавяме бутона Спри, ако състоянието е активно или изчакване
            if (($state == 'pending') || ($state == 'active')) {
                if ($mvc->haveRightFor('stop', $rec->rec)) {
                    $data->toolbar->addBtn('Спиране', array($mvc, 'Stop', $rec->id), 'ef_icon = img/16/gray-close.png');
                }
            }
            
            // Добавяме бутон за обновяване в, ако състоянието е активно, изчакване или затворено
            if (($state == 'pending') || ($state == 'active') || ($state == 'closed')) {
                if ($mvc->haveRightFor('update', $rec->rec)) {
                    $data->toolbar->addBtn('Обновяване', array($mvc, 'Update', $rec->id), 'ef_icon = img/16/update-icon.png, row=1');
                }
            }
        }
    }
    
    
    /**
     * Променяме шаблона в зависимост от мода
     * 
     * @param blast_Emails $mvc
     * @param core_ET $tpl
     * @param object $data
     */
    function on_BeforeRenderSingleLayout($mvc, &$tpl, $data)
    {
        //Рендираме шаблона
        if (Mode::is('text', 'xhtml')) {
            //Ако сме в xhtml (изпращане) режим, рендираме шаблона за изпращане
            $mvc->singleLayoutFile = 'blast/tpl/SingleLayoutBlast.shtml';
        } elseif (Mode::is('text', 'plain')) {
            //Ако сме в текстов режим, рендираме txt
            $mvc->singleLayoutFile = 'blast/tpl/SingleLayoutBlast.txt';
        } else {
            $mvc->singleLayoutFile = 'blast/tpl/SingleLayoutEmails.shtml'; 
        }
    }
    
    
    /**
     * След рендиране на синъл обвивката
     * 
     * @param blast_Emails $mvc
     * @param core_ET $tpl
     * @param object $data
     */
    function on_AfterRenderSingleLayout($mvc, &$tpl, $data)
    {
        // Полета До и Към
        $attn = $data->row->recipient . $data->row->attn;
        $attn = trim($attn);
        
        // Ако нямаме въведени данни До: и Към:, тогава не показваме имейл-а, и го записваме в полето До:
        if (!$attn) {
            $data->row->recipientEmail = $data->row->email;
            unset($data->row->email);
        }
        
        // Полета Град и Адрес
        $addrStr = $data->row->place . $data->row->address;
        $addrStr = trim($addrStr);
        
        // Ако липсва адреса и града
        if (!$addrStr) {
            
            // Не се показва и пощенския код
            unset($data->row->pcode);
            
            // Ако имаме До: и Държава, и нямаме адресни данни, тогава добавяме държавата след фирмата
            if ($data->row->recipient) {
                $data->row->firmCountry = $data->row->country;
            }
            
            // Не се показва и държавата
            unset($data->row->country);
            
            $telFaxStr = $data->row->tel . $data->row->fax;
            $telFaxStr = trim($telFaxStr);
            
            // Имейла е само в дясната част, преместваме в ляво
            if (!$telFaxStr) {
                $data->row->emailLeft = $data->row->email;
                unset($data->row->email);
            }
        }        
        
        // Рендираме шаблона
        if (!Mode::is('text', 'xhtml') && !Mode::is('text', 'plain')) {
            
            // Записите
            $rec = $data->rec;
            
            // Ако състоянието е активирано или чернов
            if ($rec->state == 'active' || $rec->state == 'waitnig') {
                
                // Вземаме времето на следващото изпращане
                // Ако има такова време
                if ($nextStartTime = core_Cron::getNextStartTime(self::$cronSytemId)) {
                    
                    // Ако времето е преди въведената дата от потребителя
                    if ($nextStartTime < $rec->startOn) {
                        
                        // Използваме дата на потребителя
                        $nextStartTime = $rec->startOn;
                    }
                } else {
                    
                    // Вземаме времето въведено от потребителя
                    $nextStartTime = $rec->startOn;
                }
                
                // Ако сме успели да определим времето
                if ($nextStartTime) {
                    
                    // Показваме вербалното време
                    $data->row->NextStartTime = dt::mysql2verbal($nextStartTime, 'smartTime');
                }
            }
        }
    }
    
    
 	/**
 	 * След порготвяне на формата за филтриране
 	 * 
 	 * @param blast_Emails $mvc
 	 * @param object $data
 	 */
    static function on_AfterPrepareListFilter($mvc, &$data)
    {
        // Да се показва полето за търсене
        $data->listFilter->showFields = 'search';
        
        $data->listFilter->view = 'horizontal';
        
        //Добавяме бутон "Филтрирай"
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Сортиране на записите по състояние и по времето им на започване
        $data->query->orderBy('state', 'ASC');
        $data->query->orderBy('startOn', 'DESC');
    }
    
    
    /**
     * Преди да подготвим данните за имейла, подготвяме rec
     * 
     * @param blast_Emails $mvc
     * @param core_ET $res
     * @param integer $id
     * @param string $mode
     * @param object $options
     */
    function on_BeforeGetDocumentBody($mvc, &$res, $id, $mode = 'html', $options = NULL)
    {
        // Записите за имейла
        $emailRec = $mvc->fetch($id);
        
        // Очакваме да има такъв запис
        expect($emailRec);
        
        // Намираме преполагаемия език на съобщението
        core_Lg::push(self::getLanguage($emailRec->body, $emailRec->lg));
        
        $detDataArr = array();
        
        // Опитваме се да извлечен масива с данните
        if ($options->__detArr) {
            
            // Ако е подаден масива с данните
            $detDataArr = $options->__detArr;
        } elseif ($options->detId) {
            
            // Ако е подадено id, вместо масива
            $detDataArr = blast_EmailSend::getDataArr($options->detId);
        }
        
        // Обединяваме детайлите
        $otherDetArr = self::getEmailOtherPlaces($emailRec);
        $detDataArr = (array)$detDataArr + (array)$otherDetArr;
        
        // Подготвяме данните за съответния имейл
        $mvc->prepareRec($emailRec, $detDataArr);
        
        // Обединяваме рековете и ги добавяме в опциите
        // За да може да запазим ->mid' от река
        $options->rec = (object)((array)$emailRec + (array)$options->rec);
    }
    
    
    /**
     * 
     */
    function on_AfterGetDocumentBody($mvc, &$res, $id, $mode = 'html', $options = NULL)
    {
        // Връщаме езика по подразбиране
        core_Lg::pop();
    }
    
    
    /**
     * 
     * 
     * @param blast_Emails $mvc
     * @param array $res
     * @param integer $id
     * @param integer $userId
     * @param object $data
     */
    public static function on_BeforeGetLinkedDocuments($mvc, &$res, $id, $userId=NULL, $data=NULL)
    {
        // id на детайла
        $detId = $data->detId;
        
        if (!$detId) return ;
        
        // Масив с данните
        $detArr = blast_EmailSend::getDataArr($detId);
        
        if (is_object($id)) {
            $rec = $id;
        } else {
            $rec = $mvc->fetch($id);
        }
        
        // Подготвяме записите
        $mvc->prepareRec($rec, $detArr);
        
        core_Users::sudo($userId);
        
        // Вземаме прикачените документи за този детайл с правата на активиралия потребител
        $attachedDocs = (array)doc_RichTextPlg::getAttachedDocs($rec->body);
        
        core_Users::exitSudo();
        
        // Ако има прикачени документи
        if (count($attachedDocs)) {
            $attachedDocs = array_keys($attachedDocs);
            $attachedDocs = array_combine($attachedDocs, $attachedDocs);  

            $res = array_merge($attachedDocs, (array)$res);
        }
    }
    
    
    /**
     * Интерфейсен метод
     * Проверка дали нов документ може да бъде добавен в посочената папка, като начало на нишка.
     * 
     * @see email_DocumentIntf
     *
     * @param int $folderId - id на папката
     * 
     * @return boolean
     */
    public static function canAddToFolder($folderId)
    {
        // Името на класа
        $coverClassName = strtolower(doc_Folders::fetchCoverClassName($folderId));
        
        // TODO
        // Може да се добавя само в проекти и в групи
//        if (($coverClassName != 'doc_unsortedfolders') && ($coverClassName != 'crm_groups')) return FALSE;
        if (($coverClassName != 'doc_unsortedfolders')) return FALSE;
        
        return TRUE;
    }
    
    
	/**
     * Интерфейсен метод на 
     * 
     * @see email_DocumentIntf
     * 
     * @return object
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $row = new stdClass();
        
        $subject = $this->getVerbal($rec, 'subject');
        
        //Ако заглавието е празно, тогава изписва съответния текст
        if(!trim($subject)) {
            $subject = '[' . tr('Липсва заглавие') . ']';
        }
        
        //Заглавие
        $row->title = $subject;
        
        //Създателя
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        //Състояние
        $row->state = $rec->state;
        
        //id на създателя
        $row->authorId = $rec->createdBy;
        
        $row->recTitle = $rec->subject;
        
        return $row;
    }
    
    
	/**
     * Функция, която се изпълнява от крона и стартира процеса на изпращане на blast
     */
    function cron_SendEmails()
    {
        $this->sendEmails();
    }
    
    
    /**
     * Изпълнява се след създаването на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        $conf = core_Packs::getConfig('blast');
        
        // За да получим минути
        $period = round($conf->BLAST_EMAILS_CRON_PERIOD/60);
        
        //Данни за работата на cron
        $rec = new stdClass();
        $rec->systemId = self::$cronSytemId;
        $rec->description = 'Изпращане на много имейли';
        $rec->controller = $mvc->className;
        $rec->action = 'SendEmails';
        $rec->period = $period;
        $rec->offset = 0;
        $rec->delay = 0;
        $rec->timeLimit = $conf->BLAST_EMAILS_CRON_TIME_LIMIT;
        $res .= core_Cron::addOnce($rec);
        
        //Създаваме, кофа, където ще държим всички прикачени файлове на blast имейлите
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('Blast', 'Прикачени файлове в масовите имейли', NULL, '104857600', 'user', 'user');
    }
}
