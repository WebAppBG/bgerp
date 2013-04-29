<?php 


/**
 * Ръчен постинг в документната система
 *
 *
 * @category  bgerp
 * @package   email
 * @author    Stefan Stefanov <stefan.bg@gmail.com> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_Outgoings extends core_Master
{
    
    /**
     * Име на папката по подразбиране при създаване на нови документи от този тип.
     * Ако стойноста е 'FALSE', нови документи от този тип се създават в основната папка на потребителя
     */
    var $defaultFolder = FALSE;
    

    /**
     * Полета, които ще се клонират
     */
    var $cloneFields = 'subject, body, recipient, attn, email, emailCc, tel, fax, country, pcode, place, address';
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'doc_Postings';
    
    
    /**
     * Заглавие
     */
    var $title = "Изходящи имейли";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Изходящ имейл";
    
    
    /**
     * Кой има право да го чете?
     */
    var $canSingle = 'ceo, email';
    
    
    /**
     * Кой има право да го променя?
     */
    var $canEdit = 'user';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'user';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'user';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'user';
    
    
    /**
     * Кой може да изпраща имейли?
     */
    var $canSend = 'user';
    
    
    /**
     * Кой има право да изтрива?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Кой има права за
     */
    var $canEmail = 'user';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'email_Wrapper, doc_DocumentPlg, plg_RowTools, 
        plg_Printing, email_plg_Document, doc_ActivatePlg, 
        bgerp_plg_Blank,  plg_Search, recently_Plugin';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'email/tpl/SingleLayoutOutgoings.shtml';
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    var $singleIcon = 'img/16/email_edit.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = 'Eml';
    
    
    /**
     * Полето "Относно" да е хипервръзка към единичния изглед
     */
    var $rowToolsSingleField = 'subject';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,subject,recipient,attn,email,createdOn,createdBy';
    
    /**
     * Поле за търсене
     */
    var $searchFields = 'subject, recipient, attn, email, body, folderId, threadId, containerId';
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "1.2|Общи";


    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('subject', 'varchar', 'caption=Относно,mandatory,width=100%');
        $this->FLD('body', 'richtext(rows=15,bucket=Postings, appendQuote)', 'caption=Съобщение,mandatory');
        
        //Данни за адресанта
        $this->FLD('email', 'emails', 'caption=Адресант->Имейл, width=100%');
        $this->FLD('emailCc', 'emails', 'caption=Адресант->Копие,  width=100%');
        $this->FLD('recipient', 'varchar', 'caption=Адресант->Фирма,class=contactData');
        $this->FLD('attn', 'varchar', 'caption=Адресант->Лице,oldFieldName=attentionOf,class=contactData');
        $this->FLD('tel', 'varchar', 'caption=Адресант->Тел.,oldFieldName=phone,class=contactData');
        $this->FLD('fax', 'varchar', 'caption=Адресант->Факс,class=contactData');
        $this->FLD('country', 'varchar', 'caption=Адресант->Държава,class=contactData');
        $this->FLD('pcode', 'varchar', 'caption=Адресант->П. код,class=pCode');
        $this->FLD('place', 'varchar', 'caption=Адресант->Град/с,class=contactData');
        $this->FLD('address', 'varchar', 'caption=Адресант->Адрес,class=contactData');
    }


    /**
     * Филтрира само собсвеноръчно създадените изходящи имейли
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        if(!haveRole('ceo')) {
            $cu = core_Users::getCurrent();
            $data->query->where("#createdBy = {$cu}");
        }

        $data->query->orderBy('#createdOn', 'DESC');
        
     }
    
    
    /**
     * @todo Чака за документация...
     */
    function act_Send()
    {
        $this->requireRightFor('send');
        
        $data = new stdClass();
        
        // Създаване и подготвяне на формата
        $this->prepareSendForm($data);
        
        // Подготвяме адреса за връщане, ако потребителя не е логнат.
        // Ресурса, който ще се зареди след логване обикновено е страницата, 
        // от която се извиква екшън-а act_Manage
        $retUrl = getRetUrl();
        
        // Очакваме до този момент във формата да няма грешки
        expect(!$data->form->gotErrors(), 'Има грешки в silent полетата на формата', $data->form->errors);
        
        // Зареждаме формата
        $data->form->input();
        
        // Проверка за коректност на входните данни
        $this->invoke('AfterInputSendForm', array($data->form));
        
        // Дали имаме права за това действие към този запис?
        $this->requireRightFor('send', $data->rec, NULL, $retUrl);

        $lg = email_Outgoings::getLanguage($data->rec->originId, $data->rec->threadId, $data->rec->folderId);

        // Ако формата е успешно изпратена - изпращане, лог, редирект
        if ($data->form->isSubmitted()) {

            static::_send($data->rec, $data->form->rec, $lg);
            
            // Подготвяме адреса, към който трябва да редиректнем,  
            // при успешно записване на данните от формата
            $data->form->rec->id = $data->rec->id;
            $this->prepareRetUrl($data);
            
            // $msg е съобщение за статуса на изпращането
            return new Redirect($data->retUrl);
        } else {
            // Подготвяме адреса, към който трябва да редиректнем,  
            // при успешно записване на данните от формата
            $this->prepareRetUrl($data);
        }
        
        // Получаваме изгледа на формата
        $tpl = $data->form->renderHtml();
        
        // Добавяме превю на имейла, който ще изпратим
        $preview = new ET("<div style='width:896px'><div style='margin-top:20px; margin-bottom:-10px; padding:5px;'><b>" . tr("Изходящ имейл") . "</b></div>[#EMAIL_HTML#]<pre class=\"document\" style=\"width:95%; white-space: pre-wrap;\">[#EMAIL_TEXT#]</pre></div>");
       
        $preview->append($this->getEmailHtml($data->rec, $lg) , 'EMAIL_HTML');
        $preview->append(core_Type::escape($this->getEmailText($data->rec, $lg)) , 'EMAIL_TEXT');
        
        $tpl->append($preview);

        return static::renderWrapping($tpl);
    }
    
    
    protected static function _send($rec, $options, $lg)
    {
        //Вземаме всички избрани файлове
        $rec->attachmentsFh = type_Set::toArray($options->attachmentsSet);
        
        //Ако имамем прикачени файлове
        if (count($rec->attachmentsFh)) {
        
            //Вземаме id'тата на файловете вместо манупулатора име
            $attachments = fileman_Files::getIdFromFh($rec->attachmentsFh);
        
            //Записваме прикачените файлове
            $rec->attachments = type_KeyList::fromArray($attachments);
        }
        
        // Генерираме списък с документи, избрани за прикачане
        $docsArr = static::getAttachedDocuments($options);
        
        // Имейлите от адресант
        $rEmails = $rec->email;
        
        // Имейлите от получател
        $oEmails = $options->emailsTo;
        
        $groupEmailsArr['cc'][0] = $options->emailsCc;
        
        // Ако не сме променили имейлите
        if (trim($rEmails) == trim($oEmails)) {
            
            // Всики имейли са в една група
            $groupEmailsArr['to'][0] = $oEmails;
        } else {
            
            // Масив с имейлите от адресанта
            $rEmailsArr = type_Emails::toArray($rEmails);
            
            // Масив с имейлите от получателя
            $oEmailsArr = type_Emails::toArray($oEmails);
            
            // Събираме в група всички имейли, които се ги има и в двата масива
            $intersectArr = array_intersect($oEmailsArr, $rEmailsArr);
            
            // Вземаме имейлите, които ги няма в адресанта, но ги има в получатели
            $diffArr = array_diff($oEmailsArr, $rEmailsArr);

            // Добавяме имейлите, които са в адресант и в получател
            // Те ще се изпращат заедно с CC
            if ($intersectArr) {
                $groupEmailsArr['to'][0] = type_Emails::fromArray($intersectArr);    
            }
            
            // Обхождаме всички имейли, които ги няма в адресант, но ги има в получател
            foreach ($diffArr as $diff) {
                
                // Добавяме ги в масива, те ще се изпращат самостоятелно
                $groupEmailsArr['to'][] = $diff;
            }
        }

        // CSS' а за имейли
        $emailCss = getFileContent('css/email.css');
        
        // списъци с изпратени и проблеми получатели
        $success  = $failure = array();
        
        // Обхождаме масива с всички групи имейли
        foreach ($groupEmailsArr['to'] as $key => $emailTo) {
            
            // Вземаме имейлите от cc
            $emailsCc = $groupEmailsArr['cc'][$key];
            
            // Проверяваме дали същия имейл е изпращан преди
            $isSendedBefore = log_Documents::isSended($rec->containerId, $emailTo, $emailsCc);

            // Ако е изпращан преди
            if ($isSendedBefore) {
                
                // В събджекта добавяме текста
                $rec->_resending = 'Повторно изпращане';
            } else {
                
                // Ако не е изпращане преди
                $rec->_resending = NULL;
            }
            
            // Данни за съответния екшън
            $action = array(
                    'containerId' => $rec->containerId,
                    'action'      => log_Documents::ACTION_SEND,
                    'data'        => (object)array(
                        'from' => $options->boxFrom,
                        'to'   => $emailTo,
                    )
                );
                
            // Ако има CC
            if ($emailsCc) {
                
                // Добавяме към екшъна
                $action['data']->cc = $emailsCc;
            }
            
            // Добавяме изпращача
            $action['data']->sendedBy = core_Users::getCurrent();
            
            // Пушваме екшъна
            log_Documents::pushAction($action);
        
            // Подготовка на текста на писмото (HTML & plain text)
            $rec->__mid = NULL;
            $rec->html = static::getEmailHtml($rec, $lg, $emailCss);
            $rec->text = static::getEmailText($rec, $lg);
        
            // Генериране на прикачените документи
            $rec->documentsFh = array();
            foreach ($docsArr as $attachDoc) {
                // Използваме интерфейсен метод doc_DocumentIntf::convertTo за да генерираме
                // файл със съдържанието на документа в желания формат
                $fhArr = $attachDoc['doc']->convertTo($attachDoc['ext'], $attachDoc['fileName']);
            
                $rec->documentsFh += $fhArr;
            }
    
            // .. ако имаме прикачени документи ...
            if (count($rec->documentsFh)) {
                
                //Вземаме id'тата на файловете вместо манипулаторите
                $documents = fileman_Files::getIdFromFh($rec->documentsFh);
            
                //Записваме прикачените файлове
                $rec->documents = type_KeyList::fromArray($documents);
            }
    
            // ... и накрая - изпращане.
            $status = email_Sent::sendOne(
                $options->boxFrom,
                $emailTo,
                $rec->subject,
                $rec,
                array(
                   'encoding' => $options->encoding
                ),
                $emailsCc
            );
            
            // Стринга с имейлите, до които е изпратено
            $allEmailsToStr = ($emailsCc) ? "{$emailTo}, $emailsCc": $emailTo;
            
            // Ако е изпратен успешно
            if ($status) {
                
                // Правим запис в лога
                static::log('Send to ' . $allEmailsToStr, $rec->id);
                
                // Добавяме в масива
                $success[] = $allEmailsToStr;
            } else {
                
                // Правим запис в лога за неуспех
                static::log('Unable to send to ' . $allEmailsToStr, $rec->id);
                $failure[] = $allEmailsToStr;
            }
            
            // Записваме историята
            log_Documents::flushActions();
        }
        
        // Ако има успешно изпращане
        if ($success) {
            $msg = 'Успешно изпратено до: ' . implode(', ', $success);
            $statusType = 'notice';
            
            // Добавяме статус
            core_Statuses::add($msg, $statusType);
        } 
        
        // Ако има провалено изпращане
        if ($failure) {
            $msg = 'Грешка при изпращане до: ' . implode(', ', $failure);
            $statusType = 'warning';   
            
            // Добавяме статус
            core_Statuses::add($msg, $statusType);
        }
    }
    
    
    static function getAttachedDocuments($rec)
    {
        $docs     = array();
        $docNames = type_Set::toArray($rec->documentsSet);
        
        //Обхождаме избрани документи
        foreach ($docNames as $fileName) {
        
            //Намираме името и разширението на файла
            if (($dotPos = mb_strrpos($fileName, '.')) !== FALSE) {
                $ext       = mb_substr($fileName, $dotPos + 1);
                $docHandle = mb_substr($fileName, 0, $dotPos);
            } else {
                $docHandle = $fileName;
            }
        
            // $docHandle -> $doc
            $doc = doc_Containers::getDocumentByHandle($docHandle);
            expect($doc);
            
            $docs[] = compact('doc', 'ext', 'fileName');
        }
        
        return $docs;
    } 
    
    
    /**
     * Подготовка на формата за изпращане
     * Самата форма се взема от email_Send
     */
    function prepareSendForm_($data)
    {
        $data->form = email_Sent::getForm();
        $data->form->setAction(array($mvc, 'send'));
        $data->form->title = 'Изпращане на имейл';
        
        $id = Request::get('id', 'int');
        
        $data->form->FNC(
            'emailsTo',
            'emails',
            'input,caption=До,mandatory,width=750px,formOrder=2',
            array(
                'attr' => array(
                    'data-role' => 'list'
                ),
            )
        );
        
        $data->form->FNC(
            'emailsCc',
            'emails',
            'input,caption=Копие,width=750px,formOrder=3',
            array(
                'attr' => array(
                    'data-role' => 'list'
                ),
            )
        );
        
        // Добавяме поле за URL за връщане, за да работи бутона "Отказ"
        $data->form->FNC('ret_url', 'varchar', 'input=hidden,silent');
        
        // Подготвяме лентата с инструменти на формата
        $data->form->toolbar->addSbBtn('Изпрати', 'send', 'id=save,class=btn-send');
        
        // Ако има права за ипзващне на факс
        if (email_FaxSent::haveRightFor('send')) {
            
            //Броя на класовете, които имплементират интерфейса email_SentFaxIntf
            $clsCount = core_Classes::getInterfaceCount('email_SentFaxIntf');
    
            //Ако има поне един клас, който да имплементира интерфейса
            if ($clsCount) {
                $data->form->toolbar->addBtn('Факс', array('email_FaxSent', 'send', $id, 'ret_url' => getRetUrl()), 'class=btn-fax');      
            }
        }
        
        $data->form->toolbar->addBtn('Отказ', getRetUrl(), array('class' => 'btn-cancel'));
        
        $data->form->input(NULL, 'silent');
        
        return $data;
    }
    
    
    /**
     * Извиква се след подготовката на формата за изпращане
     */
    static function on_AfterPrepareSendForm($mvc, $data)
    {
        expect($data->rec = $mvc->fetch($data->form->rec->id));
     
        // Трябва да имаме достъп до нишката, за да можем да изпращаме писма от нея
        doc_Threads::requireRightFor('single', $data->rec->threadId);
        
        $data->form->fields['boxFrom']->type->params['folderId'] = $data->rec->folderId;
        
        $data->form->setDefault('containerId', $data->rec->containerId);
        $data->form->setDefault('threadId', $data->rec->threadId);
        $data->form->setDefault('boxFrom', $boxFromId);
        
        // Масив, който ще съдърща прикачените файлове
        $filesArr = array();  
        
        expect(is_array($mvc->getAttachments($data->rec)), $mvc->getAttachments($data->rec), $data->rec);

        // Добавяне на предложения за прикачени файлове
        $filesArr += $mvc->getAttachments($data->rec);
        
        // Добавяне на предложения на свързаните документи
        $docHandlesArr = $mvc->GetPossibleTypeConvertings($data->form->rec->id);
        
        if(count($docHandlesArr) > 0) {
            $data->form->FNC('documentsSet', 'set', 'input,caption=Документи,columns=4,formOrder=6'); 
            
            //Вземаме всички документи
            foreach ($docHandlesArr as $name => $checked) {
                
                // Масив, с информация за документа
                $documentInfoArr = doc_RichTextPlg::getFileInfo($name);
                
                // Вземаме прикачените файлове от линковете към други документи в имейла
                $filesArr += (array)$documentInfoArr['className']::getAttachments($documentInfoArr['id']);
                
                //Проверяваме дали документа да се избира по подразбиране
                if ($checked == 'on') {
                    //Стойността да е избрана по подразбиране
                    $setDef[$name] = $name;
                }
                
                //Всички стойности, които да се покажат
                $suggestion[$name] = $name;
            }
            
            // Задаваме на формата да се покажат полетата
            $data->form->setSuggestions('documentsSet', $suggestion);
            
            // Задаваме, кои полета да са избрани по подразбиране
            $data->form->setDefault('documentsSet', $setDef); 
        }
        
        // Ако има прикачени файлове
        if(count($filesArr) > 0) {
            
            // Задаваме на формата да се покажат полетата
            $data->form->FNC('attachmentsSet', 'set', 'input,caption=Файлове,columns=4,formOrder=7');
            $data->form->setSuggestions('attachmentsSet', $filesArr);   
        }
        
        // Ако има originId
        if ($data->rec->originId) {
            
            // Контрагент данните от контейнера
            $contrData = doc_Containers::getContragentData($data->rec->originId);
        } else {
            
            // Контрагент данните от нишката
            $contrData = doc_Threads::getContragentData($data->rec->threadId);    
        }
        
        // Масив с всички имейли в До
        $emailsToArr = type_Emails::toArray($data->rec->email);
        
        // Масив с всички имейли в Cc
        $emailsCcArr = type_Emails::toArray($data->rec->emailCc);
        
        // Всички групови имейли
        $groupEmailsArr = type_Emails::toArray($contrData->groupEmails);
        
        // Премахваме нашите имейли
        $groupEmailsArr = email_Inboxes::removeOurEmails($groupEmailsArr);

        // Премахваме имейлите, които ги има записани в полето Имейл
        $groupEmailsArr = array_diff((array)$groupEmailsArr, (array)$emailsToArr);
        
        // Премахваме имейлите, които ги има записани в полето Копие
        $groupEmailsArr = array_diff((array)$groupEmailsArr, (array)$emailsCcArr);
        
        // Ако има имейл
        if (count($groupEmailsArr)) {
            
            // Ключовете да са равни на стойностите
            $groupEmailsArr = array_combine($groupEmailsArr, $groupEmailsArr);    
        }
        
        // Добавяне на предложения за имейл адреси, до които да бъде изпратено писмото
        if (count($groupEmailsArr)) {
            $data->form->setSuggestions('emailsTo', array('' => '') + $groupEmailsArr);
            $data->form->setSuggestions('emailsCc', array('' => '') + $groupEmailsArr);
        }

        // По подразбиране кои да са избрани
        $data->form->setDefault('emailsTo', $data->rec->email);
        $data->form->setDefault('emailsCc', $data->rec->emailCc);
    }
    
    
    /**
     * Проверка на входните параметри от формата за изпращане
     */
    static function on_AfterInputSendForm($mvc, $form)
    {
        if($form->isSubmitted()) {
            $rec = $form->rec;
            
            if($form->rec->encoding != 'utf8' && $form->rec->encoding != 'lat') {
                $html = (string) $rec->html;
                $converted = iconv('UTF-8', $rec->encoding, $html);
                $deconverted = iconv($rec->encoding, 'UTF-8', $converted);
                
                if($deconverted  != $html) {
                    $form->setWarning('encoding', 'Писмото съдържа символи, които не могат да се конвертират към|* ' .
                        $form->fields['encoding']->type->toVerbal($rec->encoding));
                }
            }
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $mvc->flagSendIt = ($form->cmd == 'sending');
            
            if ($mvc->flagSendIt) {
                $form->rec->state = 'active';
                
                $mvc->invoke('Activation', array($form->rec));
                
                //Ако изпращаме имейла и полето за имейл е празно, показва съобщение за грешка
                if (!trim($form->rec->email)) {
                    $form->setError('email', "За да изпратите имейла, трябва да попълните полето|* <b>|Адресант->Имейл|*</b>.");    
                }
            }
        }
    }
    

    /**
     * @todo Чака за документация...
     */
    static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
        if ($mvc->flagSendIt) {
            
            $options = array();
            
            // Масив с всички документи
            $docHandlesArr = $mvc->GetPossibleTypeConvertings($rec->id);
    
            // Обхождаме документите
            foreach ($docHandlesArr as $name => $checked) {
                
                // Проверяваме дали документа да се избира по подразбиране
                if ($checked == 'on') {
                    
                    // Добавяме в масива
                    $docsArr[$name] = $name;
                }
            }
            
            // Ако има прикачени файлове по подаразбиране
            if (count($docsArr)) {
                
                // Инстанция на класа
                $typeSet = cls::get('type_Set');
                
                // Файловете, които ще се прикачат
                $docsSet = $typeSet->fromVerbal($docsArr);
                
                // Добавяме прикачените документи в опциите
                $options['documentsSet'] = $docsSet;
            }
            
            $lg = email_Outgoings::getLanguage($rec->originId, $rec->threadId, $rec->folderId);
            
            $fromEmailOptions = email_Inboxes::getFromEmailOptions($rec->folderId);
            
            $boxFromId = key($fromEmailOptions);
            
            $options['boxFrom'] = $boxFromId;
            $options['encoding'] = 'utf-8';
            $options['emailsTo'] = $rec->email;
            $options['emailsCc'] = $rec->emailCc;

            static::_send($rec, (object)$options, $lg);
        }
        
        // Ако активираме имейла
        if ($rec->__activation) {
            
            // Вземаме целия запис
            $nRec = $mvc->fetch($rec->id);
            
            // Записваме обръщението в модела
            email_Salutations::create($nRec);
        }
        
        // Ако препащме имейла
        if ($rec->forward && $rec->originId) {
            
            // Записваме в лога, че имейла, който е създаден е препратен
            log_Documents::forward($rec);
        }
    }
    
    
    /**
     * Връща plain-текста на писмото
     */
    static function getEmailText($oRec, $lg)
    {
        core_Lg::push($lg);
        
        $textTpl = static::getDocumentBody($oRec->id, 'plain', (object)array('rec' => $oRec));
        $text    = html_entity_decode($textTpl->getContent(), ENT_COMPAT | ENT_HTML401, 'UTF-8');
        
        core_Lg::pop();
        
        return $text;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function getEmailHtml($rec, $lg, $css = '')
    {
        core_Lg::push($lg);

        // Използваме интерфейсния метод doc_DocumentIntf::getDocumentBody() за да рендираме
        // тялото на документа (изходящия имейл)
        $res = static::getDocumentBody($rec->id, 'xhtml', (object)array('rec' => $rec));
        
        // Правим инлайн css, само ако са зададени стилове $css
        // Причината е, че Emogrifier не работи правилно, като конвертира html entities към 
        // символи (страничен ефект).
        //
        // @TODO Да се сигнализират създателите му
        //
        if($css) {
            //Създаваме HTML частта на документа и превръщаме всички стилове в inline
            //Вземаме всичките css стилове
            $css = getFileContent('css/wideCommon.css') .
                "\n" . getFileContent('css/wideApplication.css') . "\n" . $css ;
                
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
            
        //Изчистваме HTML коментарите
        $res = self::clearHtmlComments($res);
        
        core_Lg::pop();
        
        return $res;
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $rec = $data->form->rec;
        $form = $data->form;
        
        // Ако се препраща
        $forward = Request::get('forward');
        
        // Добавяме бутона изпрати
        $form->toolbar->addSbBtn('Изпрати', 'sending', array('class' => 'btn-send', 'order'=>'10'));
                
        //Зареждаме нужните променливи от $data->form->rec
        $originId = $rec->originId;
        
        // Ако не редактираме и не клонираме
        if (!($rec->id) && !(Request::get('clone'))) {
    
            // Ако писмото не се препраща
            if (!$forward) {
                $threadId = $rec->threadId;    
            }
            
            // Ако не е задедено folderId в URL' то
            if (!($folderId = Request::get('folderId', 'int'))) {
                $folderId = $rec->folderId;
                $emptyReqFolder = TRUE;    
            }
            
            
            $emailTo = Request::get('emailto');
            
            $emailTo = str_replace(email_ToLinkPlg::AT_ESCAPE, '@', $emailTo);
            $emailTo = str_replace('mailto:', '', $emailTo);
    
            // Определяме треда от originId, ако не се препраща
            if($originId && !$threadId && !$forward) {
                $threadId = doc_Containers::fetchField($originId, 'threadId');
            }
            
            //Определяме папката от треда
            if($threadId && !$folderId) {
                $folderId = doc_Threads::fetchField($threadId, 'folderId');
            }
    
            // Ако сме дошли на формата чрез натискане на имейл
            if ($emailTo) {
                
                // Проверяваме дали е валидем имейл адрес
                if (type_Email::isValidEmail($emailTo)) {
                    if (!$forward) {
                        // Опитваме се да вземаме папката
                        if (!$folderId = static::getAccessedEmailFolder($emailTo)) {
                            
                            if ($personId = crm_Profiles::getProfile()->id) {
                                
                                // Ако нищо не сработи вземаме папката на текущия потребител
                                $folderId = crm_Persons::forceCoverAndFolder($personId);    
                            } else {
                                
                                // Трябва да има потребителски профил
                                expect(FALSE, 'Няма потребителски профил');
                            }
                        }    
                    }       
                } else {
                    
                    //Ако не е валидемимейал, добавяме статус съобщения, че не е валиден имейл
                    core_Statuses::add("Невалиден имейл: {$emailTo}", 'warning');   
                }
            }
    
            // Ако писмото е отговор на друго, тогава по подразбиране попълваме полето относно
            if ($originId) {
                //Добавяме в полето Относно отговор на съобщението
                $oDoc = doc_Containers::getDocument($originId);
                $oRow = $oDoc->getDocumentRow();
                
                // Заглавието на темата
                $title = html_entity_decode($oRow->title, ENT_COMPAT | ENT_HTML401, 'UTF-8');
                
                $oContragentData = $oDoc->getContragentData();    
                
                // Ако се препраща
                if ($forward) {
                    
                    // Полето относно
                    $rec->subject = 'FW: ' . $title;    
                } else {
                    
                    $rec->subject = 'RE: ' . $title;
                }
            }
            
            if ($forward) {
                
                // Определяме езика от папката
                $lg = email_Outgoings::getLanguage(FALSE, FALSE, $folderId);   
            } else {
                
                // Определяме езика на който трябва да е имейла
                $lg = email_Outgoings::getLanguage($originId, $threadId, $folderId); 
            }
            
            //Сетваме езика, който сме определили за превод на съобщението
            core_Lg::push($lg);
    
            //Ако сме в треда, вземаме данните на получателя и не препращаме имейла
            if ($threadId && !$forward) {
                
                //Данните на получателя от треда
                $contragentData = doc_Threads::getContragentData($threadId);
            }
    
            //Ако създаваме нов тред, определяме данните на контрагента от ковъра на папката
            if ((!$threadId || $forward) && $folderId) {
                
                // Ако препращаме имейла, трябва да сме взели папката от URL' то
                if (!($forward && $emptyReqFolder)) {
                    
                    // Вземаме данните на контрагента
                    $contragentData = doc_Folders::getContragentData($folderId);    
                }
            }    
    
            //Ако сме открили някакви данни за получателя
            if ($contragentData) {
                
                // Премахваме данните за нашата фирма
                crm_Companies::removeOwnCompanyData($contragentData);
                
                //Заместваме данните в полетата с техните стойности. Първо се заместват данните за потребителя
                $rec->recipient = $contragentData->company;
                $rec->attn      = $contragentData->person;
                $rec->country   = $contragentData->country;
                $rec->pcode     = $contragentData->pCode;
                $rec->place     = $contragentData->place;
                
                //Телефонен номер. Ако има се взема от компанията, aко няма, от мобилния. В краен случай от персоналния (домашен).
                $rec->tel = ($contragentData->tel) ? ($rec->tel = $contragentData->tel) : ($rec->tel = $contragentData->pMobile);
                
                if (!$rec->tel) $rec->tel = $contragentData->pTel;
                
                //Факс. Прави опит да вземе факса на компанията. Ако няма тогава взема персоналния.
                $rec->fax = $contragentData->fax ? $contragentData->fax : $contragentData->pFax;
                
                //Адрес. Прави опит да вземе адреса на компанията. Ако няма тогава взема персоналния.
                $rec->address = $contragentData->address ? $contragentData->address : $contragentData->pAddress;
                
                //Имейл. Прави опит да вземе имейл-а на компанията. Ако няма тогава взема персоналния.
                $rec->email = $contragentData->email ? $contragentData->email : $contragentData->pEmail;
            }
            
            // Ако отговаряме на конкретен е-имейл, винаги имейл адреса го вземаме от него
            if($oContragentData->email && !$forward) {
                
                // Ако има replyTo използваме него
                if ($oContragentData->replyToEmail) {
                    
                    // Вземаме стринга само с имейлите и го добавяме в имейл полето
                    $rec->email = email_Mime::getAllEmailsFromStr($oContragentData->replyToEmail);
                    $replyTo = TRUE;    
                } else {
                    
                    // Ако няма, имейлите да се вземат от контрагента
                    $rec->email = $oContragentData->email;    
                }
            }
            
            //Данни необходими за създаване на хедър-а на съобщението
            $contragentDataHeader['name'] = $contragentData->person;
            if($s = $contragentDataHeader['salutation'] = $contragentData->salutation) {
                if($s != 'Г-н') {
                    $hello = "Уважаема";
                } else {
                    $hello = "Уважаеми";
                }
            }
            
            if($contragentData->person) {
                setIfNot($hello, 'Здравейте');
            } else {
                setIfNot($hello, 'Уважаеми колеги');
            }
    
            $contragentDataHeader['hello'] = $hello;
     
            //Създаваме тялото на постинга
            $rec->body = $mvc->createDefaultBody($contragentDataHeader, $rec, $forward);
            
            //След превода връщаме стария език
            core_Lg::pop();
            
            //Добавяме новите стойности на $rec
            if($threadId && !$forward) {
                $rec->threadId = $threadId;
            }
            
            // Записваме папката ако не препращаме имейла
            if($folderId && !$forward) {
                $rec->folderId = $folderId;
            }
            
            // Ако препращаме имейла и папката не взета от URL' то
            if ($forward && !$emptyReqFolder) {
                
                // Да се записва в папката от където препращаме
                $rec->folderId = $folderId;
                unset($rec->threadId);
            } 
               
            // Ако има originId
            if ($originId) {
                
                // Използваме контрагент данните от origin' а
                $contrData = $oContragentData;
            } else {
                
                // Използваме контрагент данните от ковъра
                $contrData = $contragentData;
            }
        } else {
            
            // Флаг
            $editing = TRUE;
            
            // Ако клонираме или редактираме, вземаме контрагент данните от нишката
            if ($rec->threadId) {
                
                // Използваме контрагент данните от ковъра
                $contrData = doc_Threads::getContragentData($rec->threadId);    
            } elseif ($rec->folderId) {
                
                // Ако няма нишка вземам контрагент данните на папката
                $contrData = doc_Folders::getContragentData($rec->folderId);    
            }
        }
        
        // Създаваме масива
        $allEmailsArr = array();
        
        if ($contrData->groupEmails) {
            
            // Разделяме стринга в масив
            $allEmailsArr = explode(', ', $contrData->groupEmails);    
        }
        
        // Ако отговаряме на конкретен имейл
        if ($emailTo) {
            
            // Попълваме полето Адресант->Имейл със съответния имейл
            $rec->email = $emailTo;     
        }
        
        // Всички имейли от река
        $recEmails = type_Emails::toArray($rec->email);
        
        // От река премахваме нашите имейли
        $recEmails = email_Inboxes::removeOurEmails($recEmails);
        
        // Ако се редактира или клонира
        if ($editing) {
            
            // Имейлите от река за премахване
            $emailForRemove = $recEmails;
        } else {
            
            // Ако няма replyTo
            if (!$replyTo) {
                
                // Само един имейл в полето имейли
                $rec->email = $recEmails[0];   
                
                // Имейлите за премахване
                $emailForRemove = array($recEmails[0]);
            } else {
                
                // replyTo в имейлите за премахване
                $emailForRemove = $recEmails;
            }
        }

        // Премахваме имейлите, които не ни трябват
        $allEmailsArr = array_diff($allEmailsArr, $emailForRemove);

        // Премахваме нашите имейл акаити
        $allEmailsArr = email_Inboxes::removeOurEmails($allEmailsArr);
        
        // Ако има групови имейли
        if (count($allEmailsArr)) {
            
            // Ключовете да са равни на стойностите
            $allEmailsArr = array_combine($allEmailsArr, $allEmailsArr);
            
            // Имейлите по подразбиране
            $data->form->setSuggestions('email', array('' => '') + $allEmailsArr);
            $data->form->setSuggestions('emailCc', array('' => '') + $allEmailsArr);
            
            // Добавяме атрибута
            $data->form->addAttr('email', array('data-role' => 'list'));
            $data->form->addAttr('emailCc', array('data-role' => 'list'));
        }
        
        // Ако препращаме писмото
        if ($forward) {
            
            // Добавяме функционално поле
            $data->form->FNC('forward', 'varchar', 'input=hidden');
            
            // Задаваме стойност
            $data->form->setDefault('forward', $forward);  
        }
    }
    
    
    /**
     * Създава тялото на постинга
     */
    function createDefaultBody($HeaderData, $rec, $forward=FALSE)
    {
        //Хедър на съобщението
        $header = $this->getHeader($HeaderData, $rec);
        
        //Текста между заглавието и подписа
        $body = $this->getBody($rec->originId, $forward);
        
        //Футър на съобщението
        $footer = $this->getFooter();
        
        //Текста по подразбиране в "Съобщение"
        $defaultBody = $header . "\n\n" . $body . "\n\n" . $footer;
        
        return $defaultBody;
    }
    
    
    /**
     * Създава хедър към постинга
     */
    function getHeader($data, $rec)
    {  
        // Вземаме обръщението
        $salutation = email_Salutations::get($rec->folderId, $rec->threadId);
        
        // Ако сме открили обръщение използваме него
        if ($salutation) return $salutation;
        
        $tpl = new ET(getFileContent("email/tpl/OutgoingHeader.shtml"));
        
        // Вземаме привета от потребителя
        $header = crm_Personalization::getHeader();
        
        // Ако е зададен привет
        if ($header) {
            
            // Използваме него
            $data['hello'] = $header;
        }
        
        //Заместваме шаблоните
        $tpl->replace(tr($data['hello']), 'hello');
        $tpl->replace(tr($data['salutation']), 'salutation');
        $tpl->replace($data['name'], 'name');

        return $tpl->getContent();
    }
    
    
    /**
     * Създава текста по подразбиране
     */
    function getBody($originId, $forward=FALSE)
    {
        if (!$originId) return ;
        
        //Вземаме класа, за който се създава съответния имейл
        $document = doc_Containers::getDocument($originId);
        
        //Името на класа
        $className = $document->className;
        
        //Ако класа имплементира интерфейса "doc_ContragentDataIntf", тогава извикваме метода, който ни връща тялото на имейл-а
        if (cls::haveInterface('doc_ContragentDataIntf', $className)) {
            $body = $className::getDefaultEmailBody($document->that, $forward);
        }
        
        return $body;
    }
    
    
    /**
     * Създава футър към постинга в зависимост от типа на съобщението
     */
    function getFooter()
    {
        // Вземаме подписа от потребителя
        $signature = crm_Personalization::getSignature();

        // Ако има подпис, превеждаме го и го връщаме
        if ($signature) {
            
            return tr($signature);
        }
        
        // Вземаме езика
        $lg = core_Lg::getCurrent();
        
        // Профила на текущият потребител
        $crmPersonRec = crm_Profiles::getProfile();
        
        // Ако текущия потребител няма фирма
        if (!($companyId = $crmPersonRec->buzCompanyId)) {
            
            // Вземаме фирмата по подразбиране
            $conf = core_Packs::getConfig('crm');
            $companyId = $conf->BGERP_OWN_COMPANY_ID;        
        }
        
        // Вземаме данните за нашата фирма
        $myCompany = crm_Companies::fetch($companyId);
        
        // Името на компанията
        $companyName = $myCompany->name;

        // Името на потребителя
        $userName = $crmPersonRec->name;
        
        // Телефон
        $tel = $crmPersonRec->buzTel;
        $tel = ($tel) ? ($tel) : $myCompany->tel;
        
        // Факс
        $fax = $crmPersonRec->buzFax;
        $fax = ($fax) ? ($fax) : $myCompany->fax;
        
        // Имейл
        $email = $crmPersonRec->buzEmail;
        $email = ($email) ? ($email) : $myCompany->email;
        
        // Длъжност
        $buzPosition = $crmPersonRec->buzPosition;
        
        // Адреса
        $buzAddress = $crmPersonRec->buzAddress;
        
        // Ако няма въведен адрес на бизнеса на потребителя
        if (!$buzAddress) {
            
            // Определяме адреса от фирмата
            $pCode = $myCompany->pCode;
            $city = $myCompany->place;
            $address = $myCompany->address;
            $country = crm_Companies::getVerbal($myCompany, 'country');
        } else {
            $address = $buzAddress;
        }
        
        // Страницата
        $webSite = $myCompany->website;
        
        // Държавата
        $country = crm_Companies::getVerbal($myCompany, 'country');
        
        //Ако езика е на български и държавата е България, да не се показва държавата
        if ((strtolower($lg) == 'bg') && (strtolower($country) == 'bulgaria')) {
            
            unset($country);
        }
        
        // Зареждаме шаблона
        $tpl = new ET(tr('|*' . getFileContent("email/tpl/OutgoingFooter.shtml")));

        //Заместваме шаблоните
        $tpl->replace(tr($userName), 'name');
        $tpl->replace(tr($companyName), 'company');
        $tpl->replace($tel, 'tel');
        $tpl->replace($fax, 'fax');
        $tpl->replace($email, 'email');
        $tpl->replace($webSite, 'website');
        $tpl->replace(tr($country), 'country');
        $tpl->replace($pCode, 'pCode');
        $tpl->replace(tr($city), 'city');
        $tpl->replace(tr($address), 'street');
        $tpl->replace(tr($buzPosition), 'position');
         
        return $tpl->getContent();
    }
    
    
    /**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото наимей по подразбиране
     */
    static function getDefaultEmailBody($id, $forward)
    {
        // Ако препращаме
        if ($forward) {
            
            // Манипулатора на документа
            $handle = static::getHandle($id);

            // Текстова част
            $text = tr("Моля запознайте се с препратения имейл|* #{$handle}.");    
        }
        
        return $text;
    }
    
    
    /**
     * Подготвя иконата за единичния изглед
     */
    static function on_AfterPrepareSingle($mvc, $data)
    {
        if($data->rec->recipient || $data->rec->attn || $data->rec->email) {
            $data->row->headerType = tr('Писмо');
        } elseif($data->rec->originId) {
            $data->row->headerType = tr('Отговор');
        } else {
            $threadRec = doc_Threads::fetch($data->rec->threadId);
            
            if($threadRec->firstContainerId == $data->rec->containerId) {
                $data->row->headerType = tr('Съобщение');
            } else {
                $data->row->headerType = tr('Съобщение');
            }
        }

        $data->lg = $lg = email_Outgoings::getLanguage($data->rec->originId, $data->rec->threadId, $data->rec->folderId);
    }
    
    
    /**
     * След рендиране на singleLayout заместваме плейсхолдера
     * с шаблонa за тялото на съобщение в документната система
     */
    function renderSingleLayout_(&$data)
    {
        if (Mode::is('printing')) {
            core_Lg::push($data->lg);
        }

        //Полета До и Към
        $attn = $data->row->recipient . $data->row->attn;
        $attn = trim($attn);
        
        //Ако нямаме въведени данни До: и Към:, тогава не показваме имейл-а, и го записваме в полето До:
        if (!$attn) {
            $data->row->recipientEmail = $data->row->email;
            $data->row->emailCcLeft = $data->row->emailCc;
            unset($data->row->email);
            unset($data->row->emailCc);
        }
        
        //Полета Град и Адрес
        $addr = $data->row->place . $data->row->address;
        $addr = trim($addr);
        
        //Ако липсва адреса и града
        if (!$addr) {
            //Не се показва и пощенския код
            unset($data->row->pcode);
            
            //Ако имаме До: и Държава, и нямаме адресни данни, тогава добавяме държавата след фирмата
            if ($data->row->recipient) {
                $data->row->firmCountry = $data->row->country;
            }
            
            //Не се показва и държавата
            unset($data->row->country);
            
            $telFax = $data->row->tel . $data->row->fax;
            $telFax = trim($telFax);
            
            //Имейла е само в дясната част, преместваме в ляво
            if (!$telFax) {
                $data->row->emailLeft = $data->row->email;
                setIfNot($data->row->emailCcLeft, $data->row->emailCc);
                unset($data->row->email);
                unset($data->row->emailCc);
            }
        }        
        
        // Определяме лейаута според режима на рендиране
        
        switch (true) 
        {
            case Mode::is('text', 'plain'):
                $tpl = 'email/tpl/SingleLayoutOutgoings.txt';
                break;
                
            case Mode::is('printing'):
                $tpl = 'email/tpl/SingleLayoutSendOutgoings.shtml';
                break;
                
            default:
                $tpl = 'email/tpl/SingleLayoutOutgoings.shtml';
                
        }
        
        $tpl = new ET(tr('|*' . getFileContent($tpl)));
        
        if (Mode::is('printing')) {
            core_Lg::pop($data->lg);
        }

        return $tpl;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Manager $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->handle = $mvc->getHandle($rec->id);
    }


    
    
    /******************************************************************************************
     *
     * ИМПЛЕМЕНТАЦИЯ НА email_DocumentIntf
     * 
     ******************************************************************************************/
    
    
    /**
     * Какъв да е събджекта на писмото по подразбиране
     *
     * @param int $id ид на документ
     * @param string $emailTo
     * @param string $boxFrom
     * @return string
     *
     * @TODO това ще е полето subject на doc_Posting, когато то бъде добавено.
     */
    public function getDefaultSubject($id, $emailTo = NULL, $boxFrom = NULL)
    {
        return static::fetchField($id, 'subject');
    }
    
    
    /**
     * До кой е-имейл или списък с етрябва да се изпрати писмото
     *
     * @param int $id ид на документ
     */
    public function getDefaultEmailTo($id)
    {
        return static::fetchField($id, 'email');
    }
    
    
    /**
     * Писмото (ако има такова), в отговор на което е направен този постинг
     *
     * @param int $id ид на документ
     * @return int key(email_Incomings) NULL ако документа не е изпратен като отговор
     */
    public function getInReplayTo($id)
    {
        
        /**
         * @TODO
         */
        return NULL;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $subject = $this->getVerbal($rec, 'subject');
        
        $row = new stdClass();
        $row->title = $subject;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->authorId = $rec->createdBy;
        $row->state = $rec->state;
        $row->recTitle = $rec->subject;
        
        return $row;
    }
    
    
    /**
     * Изпълнява се след създаването на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        //инсталиране на кофата
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('Postings', 'Прикачени файлове в постингите', NULL, '300 MB', 'user', 'user');
    }
    
    
    /**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща данните за адресанта
     */
    static function getContragentData($id)
    {
        $posting = email_Outgoings::fetch($id);
        
        $contrData = new stdClass();
        $contrData->company = $posting->recipient;
        $contrData->person = $posting->attn;
        $contrData->tel = $posting->tel;
        $contrData->fax = $posting->fax;
        $contrData->country = $posting->country;
        $contrData->pCode = $posting->pcode;
        $contrData->place = $posting->place;
        $contrData->address = $posting->address;
        $contrData->email = $posting->email;
        
        // Ако има originId
        if ($posting->originId) {
            
            // Вземаме контрагент данните на оригиналния документ (когато клонираме изходящ имейл)
            $originContr = doc_Containers::getContragentData($posting->originId);
            
            // Ако има групови имейли
            if ($originContr->groupEmails) {
                
                // Добавяме ги
                $contrData->groupEmails .= ($contrData->groupEmails) ? "$contrData->groupEmails, $originContr->groupEmails" : $originContr->groupEmails;
            }
        }
        
        return $contrData;
    }
            
    
    /**
     * Добавя бутон за Изпращане в единичен изглед
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        //Добавяме бутона, ако състоянието не е чернова или отхвърлена, и ако имаме права за изпращане
        if (($data->rec->state != 'draft') && ($data->rec->state != 'rejected')) {
            
            // Подготвяме ret_url' то
            $retUrl = array('email_Outgoings', 'single', $data->rec->id);
            
            // Разделяме имейла на факсове и имейли
            $faxAndEmailsArr = static::explodeEmailsAndFax($data->rec->email);
            
            // Броя на факсовете
            $faxCount = count($faxAndEmailsArr['fax']);
            
            // Ако има факс номер и имаме права за изпращане на факс
            if (($faxCount) && (email_FaxSent::haveRightFor('send'))) {
                
                // Бутона за изпращане да сочи към екшъна за изпращане на факсове
                $data->toolbar->addBtn('Изпращане', array('email_FaxSent', 'send', $data->rec->id, 'ret_url'=>$retUrl), 'class=btn-email-send');    
            } else {
                
                // Ако няма факс номер и имаме права за изпращане на имейл
                if (email_Outgoings::haveRightFor('email')) {
                    
                    // Добавяме бутон за изпращане на имейл
                    $data->toolbar->addBtn('Изпращане', array('email_Outgoings', 'send', $data->rec->id, 'ret_url'=>$retUrl), 'class=btn-email-send');    
                }
            }

            // Добавяме бутон за препращане на имейла
            $data->toolbar->addBtn('Препращане', array(
                    'email_Outgoings',
                    'forward',
                    $data->rec->containerId,
                    'ret_url' => TRUE,
                ), 'class=btn-forward, order=20, row=2'
            );
        }
    }
    
    
    /**
     * Разделя подадения стринг от имейли на масив с факсове и имейли
     * 
     * @param string $emails - Стринг от имейли (и факсове)
     * 
     * @return array $arr - Масив с имейли и факсове
     * @return arry $arr['fax'] - Масив с всчики факс номера
     * @return arry $arr['email'] - Масив с всчики имейли
     */
    static function explodeEmailsAndFax($emails)
    {
        // Превръщаме всички имейли на масив
        $emailsArr = type_Emails::toArray($emails);
        
        // Обхождаме масива
        foreach ($emailsArr as $email) {
            
            // Вземаме домейн частта на всеки имейл
            $domain = mb_strtolower(type_Email::domain($email));
            
            // Ако домейн частта показва, че е факс
            if ($domain == 'fax.man') {
                
                // Добавяме в масива с факосе
                $arr['fax'][$email] = $email;
            } else {
                
                // Добавяме в масива с имейли
                $arr['email'][$email] = $email;
            }
        }
        
        return $arr;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function getExternalEmails($threadId)
    {
        /* @var $query core_Query */
        $query = static::getQuery();
        $query->where("#threadId = {$threadId}");
        $query->show('email');
        
        $result = array();
        
        while ($rec = $query->fetch()) {
            if($eml = trim($rec->email)) {
                $result[$eml] = $eml;
            }
        }
        
        return $result;
    }
    
    
    /**
     * Намира предполагаемия езика на който трябва да отговорим
     * 
     * 1. Ако е отговор, гледаме езика на origin'а
     * 2. В нишката - Първо от обръщенията (ако корицата е папка на контрагент), после от езика на първия документ
     * 3. В папката - Първо от обръщенията (ако корицата е папка на контрагент), после от държавата на визитката
     * 4. Текущия език
     * 5. Ако не е bg, следователно е английски
     *
     * @param int $originId - id' то на контейнера
     * @param int $threadId - id' то на нишката
     * @param int $folderId  -id' то на папката
     *
     * @return string $lg - Двубуквеното означение на предполагаемия език на имейла
     */
    static function getLanguage($originId, $threadId, $folderId)
    {
        // Търсим езика в контейнера
        $lg = doc_Containers::getLanguage($originId);

        // Ако не сме открили езика
        if (!$lg) {
            
            // Търсим езика в нишката
            $lg = doc_Threads::getLanguage($threadId);
        }
        
        // Ако не сме открили езика
        if (!$lg) {
            
            // Търсим езика в папката
            $lg = doc_Folders::getLanguage($folderId);
        }

        // Ако не сме открили езика
        if (!$lg) {
            
            // Вземаме езика на текущия интерфейс
            $lg = core_Lg::getCurrent();
        }

        // Ако езика не е bg, използваме en
        if ($lg != 'bg') {
            $lg = 'en';
        }

        return $lg;
    }
    
    
    /**
     * Изчиства всики HTML коментари
     */
    static function clearHtmlComments($html)
    {
        //Шаблон за намиране на html коментари
        //Коментарите са:
        //<!-- Hello -->
        //<!-- Hello -- -- Hello-->
        //<!---->
        //<!------ Hello -->
        //<!>
        $pattern = '/(\<!\>)|(\<![-]{2}[^\>]*[-]{2}\>)/i';
        
        //Премахваме всички коментари
        $html = preg_replace($pattern, '', $html);
        
        return $html;
    }
    

    /**
     * Екшън за препращане на имейли
     */
    function act_Forward()
    {
        // id'то на контейнера
        $cid = Request::get('id', 'int');
        
        // Записите на контейнер
        $cRec = doc_Containers::fetch($cid);

        // Инстанция на класа
        $class = cls::get($cRec->docClass);
        
        // id на записа
        $id = $cRec->docId;
        
        // Вземаме записа
        $rec = $class::fetch($id);
        
        // Оттеглените имейли, да не може да се препращат
        expect($rec->state != 'rejected', 'Не може да се препраща оттеглен имейл.');
        
        // Проверяваме за права
        $class::requireRightFor('single', $rec);
        
        $data = new stdClass();
        
        // Вземаме формата
        $data->form = static::getForm();
        
        $form = &$data->form;
        
        // Обхождаме всички да не се показват
        foreach($form->fields as &$field) {
            $field->input = 'none';
        } 

        // Добавяме функционални полета
        $form->FNC('personId', 'key(mvc=crm_Persons, select=name, allowEmpty)', 'input,silent,caption=Папка->Лице,width=100%');          
        $form->FNC('companyId', 'key(mvc=crm_Companies, select=name, allowEmpty)', 'input,silent,caption=Папка->Фирма,width=100%');          
                    
        $form->FNC('userEmail', 'email', 'input=input,silent,caption=Имейл->Адрес,width=100%,recently');

        $form->input();

        // Проверка за грешки
        if($form->isSubmitted()) {
            // Намира броя на избраните
            $count = (int)isset($form->rec->personId) + (int)isset($form->rec->companyId) + (int)isset($form->rec->userEmail);
            
            if($count != 1) {
                $form->setError('#', 'Трябва да изберете само една от трите възможности');
            } 
        }
        
        // Ако формата е субмитната
        if ($form->isSubmitted()) {
                        
            // Ако сме избрали потребител
            if (isset($form->rec->personId)) {
                
                // Инстанция на класа
                $Persons = cls::get('crm_Persons');
                
                // Папката
                $folderId = $Persons->forceCoverAndFolder($form->rec->personId);
            }
            
            // Ако сме избрали фирмата
            if (isset($form->rec->companyId)) {
                
                // Инстанция на класа
                $Companies = cls::get('crm_Companies');
                
                // Папката
                $folderId = $Companies->forceCoverAndFolder($form->rec->companyId);
            }
            
            // Ако сме въвели имейл
            if (isset($form->rec->userEmail)) {
                
                // Вземаме папката на имейла
                $folderId = static::getForwardEmailFolder($form->rec->userEmail);
            }

            // Ако не сме открили папка или нямаме права в нея
            if (!$folderId || !doc_Folders::haveRightFor('single', $folderId)) {

                // Изтриваме папката
                unset($folderId);
            }
            
            // Препращаме към формата за създаване на имейл
            redirect(toUrl(array(
            					 'email_Outgoings',
            					 'add',
            					 'originId'=>$rec->containerId,
            					 'folderId' => $folderId,
    					 		 'emailto' => $form->rec->userEmail,
            					 'forward'=>'forward',
            					 'ret_url'=>TRUE,
                                )));
        }

        // Заявка за извличане на потребителите
        $personsQuery = crm_Persons::getQuery();
        // Да извлече само достъпните
        crm_Persons::applyAccessQuery($personsQuery);

        // Обхождаме всички откити резултати
        while ($personsRec = $personsQuery->fetch()) {
            
            // Добавяме в масива
            $personsArr[$personsRec->id] = crm_Persons::getVerbal($personsRec, 'name');
        }

        // Ако има открити стойности
        if (count($personsArr)) {
            
            // Добавяме ги в комбобокса
            $form->setOptions('personId', $personsArr);    
        } else {
            
            // Добавяме празен стринг, за да не се покажат всичките записи
            $form->setOptions('personId', array('' => '')); 
        }
        
        // Заявка за извличане на фирмите
        $companyQuery = crm_Companies::getQuery();
        // Да извлече само достъпните
        crm_Companies::applyAccessQuery($companyQuery);
    
        // Обхождаме всички откити резултати
        while ($companiesRec = $companyQuery->fetch()) {
            
            // Добавяме в масива
            $companiesArr[$companiesRec->id] = crm_Companies::getVerbal($companiesRec, 'name');
        }

        // Ако има открити стойности
        if (count($companiesArr)) {
            
            // Добавяме ги в комбобокса
            $form->setOptions('companyId', $companiesArr);    
        } else {
            
            // Добавяме празен стринг, за да не се покажат всичките записи
            $form->setOptions('companyId', array('' => '')); 
        }
        
        // URL' то където ще се редиректва
        $retUrl = getRetUrl();
        
        // Ако няма ret_url, създаваме го
        $retUrl = ($retUrl) ? $retUrl : toUrl(array($class,'single', $id));
        
        // Подготвяме лентата с инструменти на формата
        $form->toolbar->addSbBtn('Избор', 'default', array('class' => 'btn-save'));
        $form->toolbar->addBtn('Отказ', $retUrl, array('class' => 'btn-cancel'));
        
        // Потготвяме заглавието на формата
        $form->title = 'Препращане на имейл';
        
        // Получаваме изгледа на формата
        $tpl = $form->renderHtml();
        
        // Опаковаме изгледа
        $tpl = static::renderWrapping($tpl, $data);

        return $tpl;
    }
    
    /**
     * Функция, която прихваща след активирането на документа
     */
    public static function on_Activation($mvc, &$rec)
    {
        $rec->__activation = TRUE;
    }
    
    
    /**
     * Връща имейла, до който имаме достъп
     * 
     * Начин за определяна не папката:
     * 1. Ако е на фирма
     * 2. Ако е бизнес имейл на лице свързано с фирма
     * 3. Ако е на лице
     * 4. Къде би се рутирал имейла (само папка на контрагент)
     * 5. Ако има корпоративен акаунт:
     * 5.1 Кутия на потребителя
     * 5.2 Кутия на която е inCharge от съответния корпоративен акаунт
     * 6. Последната кутия на която сме inCharge
     * 
     * @param email $email - Имейл
     * 
     * @return doc_Folders $folderId - id на папка
     */
    static function getAccessedEmailFolder($email) 
    {
        // Имейла в долния регистър
        $email = mb_strtolower($email);
        
        // Папката на фирмата
        $folderId = crm_Companies::getFolderFromEmail($email);
        
        // Ако има папка връщаме
        if ($folderId) return $folderId;
        
        // Папката от бизнес имейла на фирмата
        $folderId = crm_Persons::getFolderFromBuzEmail($email);
        
        // Ако има папка връщаме
        if ($folderId) return $folderId;
        
        // Личната папка
        $folderId = crm_Persons::getFolderFromEmail($email);

        // Ако има папка връщаме
        if ($folderId) return $folderId;
        
        // Вземаме предполагаемата папка
        $folderId = email_Router::getEmailFolder($email);
        
        // Ако може да се определи папка
        if ($folderId && doc_Folders::haveRightFor('single', $folderId)) {

            // Вземаем името на cover
            $coverClassName = strtolower(doc_Folders::fetchCoverClassName($folderId));    
            
            // Ако корицата е на контрагент
            if (($coverClassName == 'crm_persons') || ($coverClassName == 'crm_companies')) {
                
                return $folderId;
            }
        }
        
        // Вземаме корпоративната сметка
        $corpAccRec = email_Accounts::getCorporateAcc();
        
        $currUserId = core_Users::getCurrent();
        
        // Ако имаме корпоративен акаунт
        if ($corpAccId = $corpAccRec->id) {
            
            // Корпоративния имейла на потребиеля
            $currUserCorpEmail = mb_strtolower(email_Inboxes::getUserEmail());
            
            // Вземаме папката
            $folderId = email_Inboxes::fetchField(array("LOWER(#email) = '[#1#]' AND #state = 'active' AND #accountId = '{$corpAccId}'", $currUserCorpEmail), 'folderId');

            // Ако има папка и имаме права в нея
            if ($folderId && email_Inboxes::haveRightFor('single', $folderId)) {

                return $folderId;
            }
            
            // Ако нямаме корпоративен имейл
            // Вземаме последния имейл на който сме inCharge
            $queryCorp = email_Inboxes::getQuery();
            $queryCorp->where("#inCharge = '{$currUserId}' AND #accountId = '{$corpAccId}' AND #state = 'active'");
            $queryCorp->orderBy('createdOn', 'DESC');
            $queryCorp->limit(1);
            $emailCorpAcc = $queryCorp->fetch();
            $folderId = $emailCorpAcc->folderId;
            
            // Ако има папка и имаме права
            if ($folderId && email_Inboxes::haveRightFor('single', $folderId)) {

                return $folderId;
            }
        }
        
        // Ако няма корпоративна сметка
        // Вземаме последния имейл на който имаме права за inCharge
        $queryEmail = email_Inboxes::getQuery();
        $queryEmail->where("#inCharge = '{$currUserId}' AND #state = 'active'");
        $queryEmail->orderBy('createdOn', 'DESC');
        $queryEmail->limit(1);
        $emailAcc = $queryEmail->fetch();
        $folderId = $emailAcc->folderId;

        // Ако има папка и имаме права
        if ($folderId && email_Inboxes::haveRightFor('single', $folderId)) {

            return $folderId;
        }

        // Ако не може да се определи по никакъв начин
        return FALSE;
    }
    
    
    /**
     * Връща папката от имейла при препращане
     * 
     * @param email $email - Имейла, към който ще препращаме
     * 
     * Начин за определяна не папката:
     * 1. Ако е на фирма
     * 2. Ако е бизнес имейл на лице свързано с фирма
     * 3. Ако е на лице
     * 
     * @return doc_Folders $folderId - id на папка
     */
    static function getForwardEmailFolder($email)
    {
        // Имейла в долния регистър
        $email = mb_strtolower($email);

        // Папката на фирмата
        $folderId = crm_Companies::getFolderFromEmail($email);
        
        // Ако има папка връщаме
        if ($folderId) return $folderId;
        
        // Папката от бизнес имейла на фирмата
        $folderId = crm_Persons::getFolderFromBuzEmail($email);
        
        // Ако има папка връщаме
        if ($folderId) return $folderId;
        
        // Личната папка
        $folderId = crm_Persons::getFolderFromEmail($email);

        // Ако има папка връщаме
        if ($folderId) return $folderId;
        
        // Ако не може да се определи по никакъв начин
        return FALSE;
    }
}
