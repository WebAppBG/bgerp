<?php 


/**
 * История от събития, свързани с документите
 *
 * Събитията са изпращане по имейл, получаване, връщане, печат, разглеждане
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Stefan Stefanov <stefan.bg@gmail.com> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class log_Documents extends core_Manager
{
    
    
    /**
     * Заглавие на таблицата
     */
    var $title = "Лог на документи";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'log_Wrapper,  plg_Created';
    
    
    /**
     * @todo Чака за документация...
     */
    var $listFields = 'createdOn, createdBy, action=Какво, containerId=Кое, dataBlob';
    
    
    /**
     * Масов-кеш за историите на контейнерите по нишки
     *
     * @var array
     */
    protected static $histories = array();
    
    
    /**
     * Домейн на записите в кеша
     *
     * @see core_Cache
     */
    const CACHE_TYPE = 'thread_history';
    
    
    /**
     * Екшъна за изпращане
     */
    const ACTION_SEND    = 'send';
    
    
    /**
     * Екшъна за връщане
     */
    const ACTION_RETURN  = '_returned';
    
    
    /**
     * Екшъна за получаване
     */
    const ACTION_RECEIVE = '_received';
    
    
    /**
     * Екшъна за отваряне
     */
    const ACTION_OPEN    = 'open';
    
    
    /**
     * Екшъна за печатане
     */
    const ACTION_PRINT   = 'print';
    
    
    /**
     * Екшъна за показване
     */
    const ACTION_DISPLAY = 'display';
    
    
    /**
     * Екшъна за факс
     */
    const ACTION_FAX     = 'fax';
    
    
    /**
     * Екшъна за PDF
     */
    const ACTION_PDF     = 'pdf';
    
    
    /**
     * Екшъна за сваляне
     */
    const ACTION_DOWNLOAD = 'download';
    
    
    /**
     * Екшъна за промяна
     */
    const ACTION_CHANGE = 'changed';
    
    
    /**
     * Екшъна за препращане
     */
    const ACTION_FORWARD = 'forward';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        // enum полетата на екшъните
        $actionsEnum = array(
            self::ACTION_SEND    . '=имейл',
            self::ACTION_RETURN  . '=връщане',
            self::ACTION_RECEIVE . '=получаване',
            self::ACTION_OPEN    . '=показване',
            self::ACTION_PRINT   . '=отпечатване',
            self::ACTION_DISPLAY . '=разглеждане',
            self::ACTION_FAX     . '=факс',
            self::ACTION_PDF     . '=PDF',
            self::ACTION_DOWNLOAD . '=сваляне',
            self::ACTION_CHANGE . '=промяна',
            self::ACTION_FORWARD . '=препращане',
        );
        
        // Тип на събитието
        $this->FLD("action", 'enum(' . implode(',', $actionsEnum) . ')', "caption=Действие");
        
        // Нишка на документа, за който се отнася събитието
        $this->FLD('threadId', 'key(mvc=doc_Threads)', 'caption=Нишка');
        
        // Документ, за който се отнася събитието
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'caption=Контейнер');
        
        // MID на документа
        $this->FLD('mid', 'varchar', 'input=none,caption=Ключ,column=none');
        
        $this->FLD('parentId', 'key(mvc=log_Documents, select=action)', 'input=none,caption=Основание');
        
//         $this->FLD('baseParentId', 'key(mvc=log_Documents, select=action)', 'input=none,caption=Основание');
        
        // Допълнителни обстоятелства, в зависимост от събитието (в PHP serialize() формат) и компресирани
        $this->FLD("dataBlob", "blob(serialize, compress)", 'caption=Обстоятелства,column=none');
        
        // Други функционални полета
        $this->FNC('data', 'text', 'input=none,column=none');
        $this->FNC('receivedOn', 'datetime(format=smartTime)', 'input=none');
        $this->FNC('returnedOn', 'datetime(format=smartTime)', 'input=none');
        $this->FNC('openAction', 'html', 'input=none');
        $this->FNC('time', 'datetime(format=smartTime)', 'input=none, caption=Време');
        $this->FNC('from', 'user', 'input=none');
        $this->FNC('ip', 'ip', 'input=none');
        $this->FNC('toEmail', 'emails', 'input=none');
        $this->FNC('cc', 'emails', 'input=none');
        
        $this->setDbIndex('containerId');
        
        $this->setDbUnique('mid');
    } 
    
    
    /**
     * Изчислява data полето
     */
    function on_CalcData($mvc, $rec)
    {
        // Вземаме dataBlob
        $rec->data = $rec->dataBlob;
        
        // Ако е празно
        if (empty($rec->data)) {
            
            // Нов празен обект
            $rec->data = new StdClass();
        }
    }
    

    /**
     * Изчислява receivedOn
     */
    function on_CalcReceivedOn($mvc, $rec)
    {
        // Ако екшъна е изпращане и има receivedOn в data
		if ($rec->action == static::ACTION_SEND && !empty($rec->data->receivedOn)) {
		    
		    // Използваме него
			$rec->receivedOn = $rec->data->receivedOn;
		}
    }
    
    
    /**
     * Изчислява returnedOn
     */
    function on_CalcReturnedOn($mvc, $rec)
    {
        // Ако екшъна е изпращане и има returnedOn в data
		if ($rec->action == static::ACTION_SEND && !empty($rec->data->returnedOn)) {
		    
		    // Използваме него
			$rec->returnedOn = $rec->data->returnedOn;
		}
    }
    
    
    /**
     * След изчислянване на вербалната стойност
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        // Ако има from
        if ($rec->from) {
            
            // Линк към визитката
            $row->from = crm_Profiles::createLink($rec->from);
        }
    }
    
    
    /**
     * Подготвяне на данните за рендиране на детайла за препращания
     * 
     * @param object $data
     */
    function prepareForward($data)
    {
        // Вземаме cid от URL' то
        $cid = Request::get('Cid', 'int');
        
        // Ако не листваме данните за съответния контейнер
        if ($data->masterData->rec->containerId != $cid) return ;
        
        // Името на таба
        $data->TabCaption = 'Препращания';
        
        // Екшъна
        $action = static::ACTION_FORWARD;
        
        // Вземаме записите
        $recs = static::getRecs($cid, $action);
        
        // Ако няма записи не се изпълнява
        if (empty($recs)) {
            
            // Бутона да не е линк
            $data->disabled = TRUE;
            
            return ;
        }
        
        // Масив с данните във вербален вид
        $rows = array();
        
        // Обхождаме записите
        foreach ($recs as $rec) {
            
            // Ако няма запис за препращане на съответния запис прескачаме            
            if (!count($rec->data->$action)) continue;
            
            // Обхождаме всички препратени записи
            foreach ($rec->data->{$action} as $forwardRec) {
                
                // Записите
                $row = (object)array(
                    'time' => $forwardRec['on'],
                    'from' => $forwardRec['from'],
                );

                // Записите във вербален вид
                $row = static::recToVerbal($row, array_keys(get_object_vars($row)));
                
                // Вземаме документите
                $doc = doc_Containers::getDocument($forwardRec['containerId']);

                // Ако имаме права за сингъл на документ
                if ($doc->instance->haveRightFor('single', $doc->that)) {
                
                    // Вербални данни на докуемент
                    $docRow = $doc->getDocumentRow();
                    
                    // Създаваме линк към документа
                    $row->document = ht::createLink($docRow->title, array($doc, 'single', $doc->that));    
                }
                
                // Добавяме в главния масив
                $rows[$forwardRec['on']] = $row;    
            }
        }

        // Сортираме по дата
        krsort($rows);
        
        // Заместваме данните за рендиране
        $data->rows = $rows; 
    }
    
    
    /**
     * Рендиране на данните за шаблона на детайла за препращания
     * 
     * @param object $data
     */
    function renderForward($data)
    {
        // Ако няма записи
        if (!$data->rows) return ;
        
        // Вземаме шаблона за детайлите с попълнена титла
        $tpl = static::getLogDetailTpl();
        
        // Инстанция на класа
        $inst = cls::get('core_TableView');

        // Вземаме таблицата с попълнени данни
        $forwardTpl = $inst->get($data->rows, 'time=Дата, from=Потребител, document=Документ');
        
        // Заместваме в главния шаблон за детайлите
        $tpl->append($forwardTpl, 'content');
        
        return $tpl;
    }
    
    
    /**
     * Подготвяне на данните за рендиране на детайла за принтирания
     * 
     * @param object $data
     */
    function preparePrint($data)
    {
        // Вземаме cid от URL' то
        $cid = Request::get('Cid', 'int');
        
        // Ако не листваме данните за съответния контейнер
        if ($data->masterData->rec->containerId != $cid) return ;
        
        // Името на таба
        $data->TabCaption = 'Отпечатвания';
        
        // Екшъните
        $actionArr = array(static::ACTION_PRINT, static::ACTION_PDF);
        
        // Вземаме записите
        $recs = static::getRecs($cid, $actionArr);
        
        // Ако няма записи не се изпълнява
        if (empty($recs)) {
            
            // Бутона да не е линк
            $data->disabled = TRUE;
            
            return ;
        }
       
        // Обхождаме записите
        foreach ($recs as $rec) {
            
            // Записите
            $row = (object)array(
                'time' => $rec->createdOn,
                'from' => $rec->createdBy,
                'action' => $rec->action,
            );

            // Записите във вербален вид
            $row = static::recToVerbal($row, array_keys(get_object_vars($row)));
            
            // Екшъна за отваряне
            $openAction = static::ACTION_OPEN;
            
            // Добавяме класа на състоянието
            $row->ROW_ATTR['class'] .= ' ' . ($rec->data->{$openAction} ? 'state-closed' : 'state-active');
            
            // Екшъна за отваряне
            $row->openAction = static::renderOpenActions($rec);
            
            // Добавяме в главния масив
            $rows[$rec->createdOn] = $row;    
        }

        // Сортираме по дата
        krsort($rows);
        
        // Заместваме данните за рендиране
        $data->rows = $rows; 
    }
    
    
    /**
     * Рендиране на данните за шаблона на детайла за принтирания
     * 
     * @param object $data
     */
    function renderPrint($data)
    {
        // Ако няма записи
        if (!$data->rows) return ;
        
        // Вземаме шаблона за детайлите с попълнена титла
        $tpl = static::getLogDetailTpl();
        
        // Инстанция на класа
        $inst = cls::get('core_TableView');
        
        // Вземаме таблицата с попълнени данни
        $printTpl = $inst->get($data->rows, 'time=Дата, from=Потребител, action=Действие, openAction=Видяно');
        
        // Заместваме в главния шаблон за детайлите
        $tpl->append($printTpl, 'content');
        
        return $tpl;
    }
    
    
    /**
     * Подготвяне на данните за рендиране на детайла за отваряния
     * 
     * @param object $data
     */
    function prepareOpen($data)
    {
        // Вземаме cid от URL' то
        $cid = Request::get('Cid', 'int');
        
        // Ако не листваме данните за съответния контейнер
        if ($data->masterData->rec->containerId != $cid) return ;
        
        // Името на таба
        $data->TabCaption = 'Виждания';
        
        // Екшъна
        $action = static::ACTION_OPEN;
        
        // Вземаме записите
        $recs = static::getRecs($cid);

        // Ако няма записи не се изпълнява
        if (empty($recs)) {
            
            // Бутона да не е линк
            $data->disabled = TRUE;
            
            return ;
        }
        
        // Масив с данния във вербален вид
        $rows = array();
        
        // Бутона да не е линк
        $data->disabled = TRUE;
        
        // Обхождаме всички записи
        foreach ($recs as $i=>$rec) {
            
            // Ако не виждан
            if (count($rec->data->{$action}) == 0) {
                
                continue;
            } else {
                
                // Бутона да не е линк
                $data->disabled = FALSE;
            }
            
            // Обхождаме всички записи
            foreach ($rec->data->{$action} as $o) {
                
                // Данните, които ще се визуализрат
                $row = (object)array(
                    'time' => $o['on'],
                    'ip' => $o['ip'],
                    'openAction' => static::formatViewReason($rec),
                );
                
                // Данните във вербален вид
                $row = static::recToVerbal($row, array_keys(get_object_vars($row)));
                
                // Добавяме в масива
                $rows[$o['on']] = $row;
            }
        }

        // Сортираме масива
        krsort($rows);
        
        // Дабавяме в $data
        $data->rows = $rows; 
    }
    
    
	/**
     * Рендиране на данните за шаблона на детайла за отваряния
     * 
     * @param object $data
     */
    function renderOpen($data)
    {
        // Ако няма записи
        if (!$data->rows) return ;
        
        // Вземаме шаблона за детайлите с попълнена титла
        $tpl = static::getLogDetailTpl();
        
        // Инстанция на класа
        $inst = cls::get('core_TableView');
        
        // Вземаме таблицата с попълнени данни
        $openTpl = $inst->get($data->rows, 'time=Дата, ip=IP, openAction=Основание');
        
        // Заместваме в главния шаблон за детайлите
        $tpl->append($openTpl, 'content');
        
        return $tpl;
    }
    
    
    /**
     * Подготвяне на данните за рендиране на детайла за изпращания
     * 
     * @param object $data
     */
    function prepareSend($data)
    {
        // Вземаме cid от URL' то
        $cid = Request::get('Cid', 'int');
        
        // Ако не листваме данните за съответния контейнер
        if ($data->masterData->rec->containerId != $cid) return ;
        
        // Името на таба
        $data->TabCaption = 'Изпращания';
        
        // Екшъните
        $actionArr = array(static::ACTION_SEND, static::ACTION_FAX);
        
        // Вземаме записите
        $recs = static::getRecs($cid, $actionArr);

        // Ако няма записи не се изпълнява
        if (empty($recs)) {
            
            // Бутона да не е линк
            $data->disabled = TRUE;
            
            return ;
        }
        
        // Вземаме всички записи
        foreach ($recs as $i=>$rec) {

            // Изчистваме нотификациите
            $linkArr = static::getLinkToSingle($rec->containerId, static::ACTION_SEND);
            bgerp_Notifications::clear($linkArr, $rec->createdBy);
            
            // Данните, които ще се визуализрат
            $row = (object)array(
                'time' => $rec->createdOn,
                'from' => $rec->createdBy,
            	'toEmail' => $rec->data->to,
                'cc' => $rec->data->cc,
                'returnedOn' => $rec->returnedOn,
            );
            
            // Записите във вербален вид
            $row = static::recToVerbal($row, array_keys(get_object_vars($row)));
            
            // Рендираме екшъна за виждане
            $row->receivedOn = static::renderOpenActions($rec, $rec->receivedOn);

            // Полето за върнато и полуяено
            $row->returnedAndReceived = $row->receivedOn;
            
            // Ако има връщане
            if ($row->returnedOn) {
                
                // Добавяме го
                $row->returnedAndReceived .= "<br />" . tr("Върнато") . ": {$row->returnedOn}";
            }
            
            // Имейлите До
            $row->emails = $row->toEmail;
            
            // Ако има копие
            if ($row->cc) {
                
                // Добавяме към имейлите
                $row->emails .= "<br />" . tr("Kп") . ": {$row->cc}";
            }
            
            // Стейта на класа
            $stateClass = 'state-active';
            switch (true) {

                // Ако е получен
                case !empty($row->receivedOn):
                    $stateClass = 'state-closed';
                    break;
                    
                // Ако е върнато
                case !empty($row->returnedOn):
                    $stateClass = 'state-stopped';
                    break;
                
            }
            
            // Доабвяме класа към атрибутите на полето
            $row->ROW_ATTR['class'] .= ' ' . $stateClass;
            
            // Добавяме в масива
            $rows[$rec->createdOn] = $row;
        }

        // Сортираме по дата
        krsort($rows);
        
        // Заместваме данните за рендиране
        $data->rows = $rows;
    }
    
    
    /**
     * Рендиране на данните за шаблона на детайла за изпращания
     * 
     * @param object $data
     */
    function renderSend($data)
    {
        // Ако няма записи
        if (!$data->rows) return ;
        
        // Вземаме шаблона за детайлите с попълнена титла
        $tpl = static::getLogDetailTpl();
        
        // Инстанция на класа
        $inst = cls::get('core_TableView');
        
        // Вземаме таблицата с попълнени данни
        $sendTpl = $inst->get($data->rows, 'time=Дата, from=Потребител, emails=До, returnedAndReceived=Получено');
        
        // Заместваме в главния шаблон за детайлите
        $tpl->append($sendTpl, 'content');
        
        return $tpl;
    }
    
    
    /**
     * Подготвяне на данните за рендиране на детайла за сваляния
     * 
     * @param object $data
     */
    function prepareDownload($data)
    {
        // Вземаме cid от URL' то
        $cid = Request::get('Cid', 'int');
        
        // Ако не листваме данните за съответния контейнер
        if ($data->masterData->rec->containerId != $cid) return ;
        
        // Името на таба
        $data->TabCaption = 'Сваляния';
        
        // Екшъна
        $action = static::ACTION_DOWNLOAD;
        
        // Вземаме записите
        $recs = static::getRecs($cid, $action);

        // Ако няма записи не се изпълнява
        if (empty($recs)) {
            
            // Бутона да не е линк
            $data->disabled = TRUE;
            
            return ;
        }
       
        $rows = array();

        // Обхождаме записите
        foreach ($recs as $rec) {

            // Ако няма зададени действия прескачаме
            if (count($rec->data->{$action}) == 0) continue;
            
            // Обхождаме всички сваляния
            foreach ($rec->data->{$action} as $fh => $downData) {
                foreach ($downData as $downData2) {
                    // СЪздаваме обект със запсиите
                    $nRec = (object)array(
                        'time' => $downData2['seenOnTime'],
                        'from' => $downData2['seenFrom'],
                        'ip' => $downData2['ip'],
                    );
                    
                    // Вземаме вербалните стойности
                    $row = static::recToVerbal($nRec, array_keys(get_object_vars($nRec)));
                    
                    // Превръщаме манипулатора, в линк за сваляне
                    $row->fileHnd = fileman_Download::getDownloadLink($fh);
                    
                    // Ако потребител от системата е свалил файла, показваме името му, в противен случай IP' то
                    $row->ip = $row->from ? $row->from : $row->ip;
                    
                    // Записваме в масив данните, с ключ датата
                    $rows[$nRec->time] = $row;    
                }
            }
        }

        // Подреждаме масива
        krsort($rows);

        // Променяме всички вербални данни, да показват откритите от нас
        $data->rows = $rows;
    }
	
	
	/**
     * Рендиране на данните за шаблона на детайла за сваляния
     * 
     * @param object $data
     */
    function renderDownload($data)
    {
        // Ако няма записи
        if (!$data->rows) return ;
        
        // Вземаме шаблона за детайлите с попълнена титла
        $tpl = static::getLogDetailTpl();
        
        // Инстанция на класа
        $inst = cls::get('core_TableView');
        
        // Вземаме таблицата с попълнени данни
        $sendTpl = $inst->get($data->rows, 'time=Дата, ip=Свалено от, fileHnd=Файл');
        
        // Заместваме в главния шаблон за детайлите
        $tpl->append($sendTpl, 'content');
        
        return $tpl;
    }
    
    
    /**
     * Подготвяне на данните за рендиране на детайла за промени
     * 
     * @param object $data
     */
    function prepareChanged($data)
    {
        // Вземаме cid от URL' то
        $cid = Request::get('Cid', 'int');
        
        // Ако не листваме данните за съответния контейнер
        if ($data->masterData->rec->containerId != $cid) return ;
        
        // Името на таба
        $data->TabCaption = 'Промени';
        
        // Екшъна
        $action = static::ACTION_CHANGE;
        
        // Вземаме записите
        $recs = static::getRecs($cid, $action);

        // Ако няма записи не се изпълнява
        if (empty($recs)) {
            
            // Бутона да не е линк
            $data->disabled = TRUE;
            
            return ;
        }
       
        $rows = array();
        
        // Обхождаме записите
        foreach ($recs as $rec) {

            // Ако няма зададени действия прескачаме
            if (count($rec->data->{$action}) == 0) continue;

            // Обхождаме всички сваляния
            foreach ($rec->data->{$action} as $changeData) {
               
                // Ако няма docId или docClass прескачаме
                if (!$changeData['docId'] || !$changeData['docClass']) continue;
                
                // Вземаме запите
                $rows = change_Log::prepareLogRow($changeData['docClass'], $changeData['docId']);

                break;
            }
        }

        // Променяме всички вербални данни, да показват откритите от нас
        $data->rows = $rows;
    }
    
    
    /**
     * Рендиране на данните за шаблона на детайла за промени
     * 
     * @param object $data
     */
    function renderChanged($data)
    {
        // Ако няма записи
        if (!$data->rows) return ;
        
        // Вземаме шаблона за детайлите с попълнена титла
        $tpl = static::getLogDetailTpl();
        
        // Инстанция на класа
        $inst = cls::get('core_TableView');
        
        // Вземаме таблицата с попълнени данни
        $sendTpl = $inst->get($data->rows, 'createdOn=Дата, createdBy=От, field=Поле, oldValue=Стара стойност');
        
        // Заместваме в главния шаблон за детайлите
        $tpl->append($sendTpl, 'content');
        
        return $tpl;
    }  
    
    
    /**
     * Връща шаблона на детайла
     * 
     * @param string $title - Заглавието на детайла
     */
    static function getLogDetailTpl()
    {
        // Шаблона
        $tpl = getTplFromFile('log/tpl/LogDetail.shtml');
        
        return $tpl;
    }
    
    
    /**
     * Връща записа за съответния контейнер със съответния екшъна
     * 
     * @param integer $cid - containerId
     * @param mixed $action - Масив или стринг с екшъна
     * 
     * @return array $recsArr - Масив с намерените записи
     */
    function getRecs($cid, $action = NULL)
    {
        // Очакваме да има $cid
        expect($cid);
        
        // Вземаме всики със записис от съответния контейнер
        $query = static::getQuery();
        $query->where("#containerId = '{$cid}'");
        
        // Ако има подаден action
        if ($action) {
            
            // Ако е масив
            if (is_array($action)) {
                
                // Добавяме екшъните с или
                $query->orWhereArr('action', $action);
            } else {
                
                // Ако не е масив, а стринг добавяме екшъна в клаузата
                $query->where("#action = '{$action}'");
            }
        }
        
        // Намираме всички записи, които отговарят на критериите ни
        while ($rec = $query->fetch()) {
            
            // Добавяме в масива
            $recsArr[] =  $rec;
        }
        
        return $recsArr;
    }

    
    /**
     * 
     */
    public static function saveAction($actionData)
    {
        $rec = (object)array_merge((array)static::getAction(), (array)$actionData);
        
        if (empty($rec->parentId)) {
            if (($parentAction = static::getAction(-1)) && !empty($parentAction->id) ) {
                $rec->parentId = $parentAction->id;
            }
        }
        
        expect($rec->containerId && $rec->action);
        
        if (empty($rec->threadId)) {
            expect($rec->threadId = doc_Containers::fetchField($rec->containerId, 'threadId'));
        }

        if (!$rec->mid && !in_array($rec->action, array(self::ACTION_DISPLAY, self::ACTION_RECEIVE, self::ACTION_RETURN, self::ACTION_DOWNLOAD, self::ACTION_CHANGE, self::ACTION_FORWARD))) {
            $rec->mid = static::generateMid();
        }
        
        // Ако има изпращач
        if ($rec->data->sendedBy) {
            
            // Използваме него за createdBy
            $rec->createdBy = $rec->data->sendedBy;
        }
        
        /*
         * Забележка: plg_Created ще попълни полетата createdBy (ако не са зададени) и
         *             createdOn (кога е станало това)
         */
        
        if (static::save($rec)) {
			// Milen: Това какво прави? Супер неясно глобално предаване на параметри!!!
			if(static::getAction()) {
				static::getAction()->id = $rec->id;
			}
            
            return $rec->mid;
        }
        
        return FALSE;
    }
    
    
    /**
     * Вкарва екшъна
     */
    public static function pushAction($actionData)
    {
        Mode::push('action', (object)$actionData);
    }
    
    
    /**
     * Изкарва екшъна
     */
    public static function popAction()
    {
        if ($action = static::getAction()) {
            Mode::pop('action');
        }
        
        return $action;
    }

    
    /**
     * Връща екшъна
     */
    public static function getAction($offset = 0)
    {
        return Mode::get('action', $offset);
    }

    
    /**
     * Проверява дали има екшън
     */
    public static function hasAction()
    {
        return Mode::get('action');
    }
    
    
    /**
     * Достъпност на документ от не-идентифицирани посетители
     * 
     * @param int $cid key(mvc=doc_Containers)
     * @param string $mid
     * @return object|boolean запис на модела или FALSE
     * 
     */
    public static function fetchHistoryFor($cid, $mid)
    {
        return static::fetch(array("#mid = '[#1#]' AND #containerId = [#2#]", $mid, $cid));
    }
    
    
    /**
     * Извлича записа по подаден $mid
     */
    protected static function fetchByMid($mid)
    {
        return static::fetch(array("#mid = '[#1#]'", $mid));
    }

    
    /**
     * Извлича записа по подаден cid
     */
    protected static function fetchByCid($cid)
    {
        return static::fetch(array("#containerId = [#1#]", $cid));
    }

    
    /**
     * Отбелязва имейла за върнат
     */
    public static function returned($mid, $date = NULL)
    {
        if (!($sendRec = static::getActionRecForMid($mid, static::ACTION_SEND))) {
            // Няма изпращане с такъв MID
            return FALSE;
        }
    
        if (!empty($sendRec->data->returnedOn)) {
            // Връщането на писмото вече е било отразено в историята; не правим нищо
            return TRUE;
        }

        if (!isset($date)) {
            $date = dt::now();
        }
        
        expect(is_object($sendRec->data), $sendRec);
    
        $sendRec->data->returnedOn = $date;
    
        static::save($sendRec);
    
        $retRec = (object)array(
            'action' => static::ACTION_RETURN,
            'containerId' => $sendRec->containerId,
            'threadId'    => $sendRec->threadId,
            'parentId'    => $sendRec->id
        );
    
        static::save($retRec);

        $msg = tr("Върнато писмо|*: ") . doc_Containers::getDocTitle($sendRec->containerId);
    
        // Нотификация за връщането на писмото до изпращача му
        $linkArr = static::getLinkToSingle($sendRec->containerId, static::ACTION_SEND);
        bgerp_Notifications::add(
            $msg, // съобщение
            $linkArr, // URL
            $sendRec->createdBy, // получател на нотификацията
            'alert' // Важност (приоритет)
        );
    
        core_Logs::add(get_called_class(), $sendRec->id, $msg);
        
        return TRUE;
    }

    
    /**
     * Отбелязва имейла за получен
     */
    public static function received($mid, $date = NULL, $IP = NULL)
    {
        if (!($sendRec = static::getActionRecForMid($mid, static::ACTION_SEND))) {
            // Няма изпращане с такъв MID
            return FALSE;
        }
    
        if (!empty($sendRec->data->receivedOn)) {
            // Връщането на писмото вече е било отразено в историята; не правим нищо
            return TRUE;
        }
    
        if (!isset($date)) {
            $date = dt::now();
        }

        expect(is_object($sendRec->data), $sendRec);
        
        $sendRec->data->receivedOn = $date;
        $sendRec->data->seenFromIp = $IP;
    
        static::save($sendRec);
    
        $rcvRec = (object)array(
            'action' => static::ACTION_RECEIVE,
            'containerId' => $sendRec->containerId,
            'threadId'    => $sendRec->threadId,
            'parentId'    => $sendRec->id
        );
    
        static::save($rcvRec);
        
        $msg = tr("Потвърдено получаване|*: ") . doc_Containers::getDocTitle($sendRec->containerId);
        
        // Нотификация за получаване на писмото до адресата.
        /*
         * За сега отпада: @link https://github.com/bgerp/bgerp/issues/353#issuecomment-8531333
         *  
        $linkArr = static::getLinkToSingle($sendRec->containerId, static::ACTION_SEND);
        bgerp_Notifications::add(
            $msg, // съобщение
            $linkArr, // URL
            $sendRec->createdBy, // получател на нотификацията
            'alert' // Важност (приоритет)
        );
        */
        
        
        core_Logs::add(get_called_class(), $sendRec->id, $msg);
    
        return TRUE;
    }
    
    
    /**
     * Преди показването на документ по MID
     * Отбелязва документа за видян
     * 
     * @param int $cid key(mvc=doc_Containers)
     * @param string $mid
     * @return stdClass
     */
    public static function opened($cid, $mid)
    {
        expect($parent = static::fetchByMid($mid));
        
        if ($parent->containerId != $cid) {
            // Заявен е документ, който не е собственик на зададения MID. В този случай заявения 
            // документ трябва да е свързан с (цитиран от) документа собственик на MID.
            $requestedDoc = doc_Containers::getDocument($cid);
            $midDoc       = doc_Containers::getDocument($parent->containerId);
            
            $linkedDocs = $midDoc->getLinkedDocuments();
            
            // свързан ли е?
            expect(isset($linkedDocs[$requestedDoc->getHandle()]));
            
            // До тук се стига само ако заявения е свързан.
            
            $action = static::fetch(array("#containerId = [#1#] AND #parentId = {$parent->id}", $cid));
            
            if (!$action || $action->action != self::ACTION_OPEN) {
                // Ако нямаме отбелязано виждане на заявения документ - създаваме нов запис
                $action = (object)array(
                    'action'      => self::ACTION_OPEN,
                    'containerId' => $cid,
                    'parentId'    => $parent->id,
                    'data'        => new stdClass(),
                );
            }
        } else {
            $action = $parent;
            $parent = NULL;
            
            if ($action->parentId) {
                // Ако текущото виждане има родител - подсигуряваме, че и родителя е маркиран 
                // като видян
                $parent = static::fetch($action->parentId);
            }
        }
        
        expect($action);
        
        return static::markAsOpened($action, $parent);
    }
    
    
    /**
     * Помощен метод - маркира запис като видян и го добавя в стека с действията.
     * 
     * Ако има зададен запис за родителско действие ($parent) и той се маркира като видян.
     * Стека с действията се пълни в паметта; записа му в БД става в края на заявката
     * @see log_Documents::on_Shutdown()
     * 
     * @param stdClass $action запис на този модел
     */
    protected static function markAsOpened($action, $parent)
    {
        if ($parent) {
            // Ако е зададено действие-родител - маркираме го като видяно.
            static::markAsOpened($parent, NULL);
        }
        
        $openAction = self::ACTION_OPEN;
        
        $ip = core_Users::getRealIpAddr();
        
        if (!isset($action->data->{$openAction}[$ip])) {
            $action->data->{$openAction}[$ip] = array(
                'on' => dt::now(true),
                'ip' => $ip
            );
        }
        
        static::pushAction($action);
        
        $msg = tr("Видян документ|*: ") . doc_Containers::getDocTitle($action->containerId);
        
        core_Logs::add('doc_Containers', $action->containerId, $msg);
        
        return $action;
    }
    
    
    /**
     * Отбелязва, когато препращаме имейл
     * 
     * @param object $eRec - Записа
     */
    public static function forward($eRec)
    {
        // От кой документ се създава записа
        $originId = $eRec->originId;
        
        // Ако няма originId
        if (!$originId) return ;
        
        // Екшъна за проманя
        $forwardAction = static::ACTION_FORWARD;
        
        // Вземаме записите за контейнера на документа, от който се създава имейла
        $cRec = doc_Containers::fetch($originId);
        
        // id на контейнера, който ще запишем в модела
        $containerId = $originId;
        
        // id на нишката, който ще запишем в модела
        $threadId = $cRec->threadId;
        
        // Вземаме записа, ако има такъв
        $rec = static::fetch("#containerId = '{$containerId}' AND #action = '{$forwardAction}'");
        
        // Ако няма запис
        if (!$rec) {
            
            // Създаваме обект с данни
            $rec = (object)array(
                'action' => $forwardAction,
                'containerId' => $containerId,
                'threadId' => $threadId,
                'data' => new stdClass(),
            );    
        }
        
        // Добавяме данните
        $rec->data->{$forwardAction}[] = array(
            'on' => dt::now(true),
            'from' => core_Users::getCurrent(),
            'containerId'  => $eRec->containerId
        );
        
        // Пушваме съответното действие
        static::pushAction($rec);

        // Съобщение в лога
        $msg = tr("Препратен имейл|*: ") . doc_Containers::getDocTitle($containerId);
        
        // Добавяме запис в лога
        core_Logs::add('doc_Containers', $rec->containerId, $msg);
        
        return $rec;
    }
    
    
    /**
     * Отбелязва като променен някой документ
     * 
     * @param object $logRecArr - Записа
     */
    public static function changed($logRecArr)
    {
        // Екшъна за проманя
        $changeAction = static::ACTION_CHANGE;
        
        // Обхождаме масива с логовете
        foreach ((array)$logRecArr as $logRec) {
            
            // Ако има docId и docClass
            if ($logRec->docId && $logRec->docClass) {
                
                // Инстанция на класа
                $docClass = cls::get($logRec->docClass);
                
                // Записите за съответния клас
                $dRec = $docClass->fetch($logRec->docId);
                
                // id на контейнера
                $containerId = $dRec->containerId;
                
                // id на треда
                $threadId = $dRec->threadId;
                
                // Ако няма запис
                if (!$rec) {
                    
                    // Вземаме записа, ако има такъв
                    $rec = static::fetch("#containerId = '{$containerId}' AND #action = '{$changeAction}'");
                    
                    // Ако няма запис
                    if (!$rec) {
                        
                        // Създаваме обект с данни
                        $rec = (object)array(
                            'action' => $changeAction,
                            'containerId' => $containerId,
                            'threadId' => $threadId,
                            'data' => new stdClass(),
                        );    
                    }
                }
                
                // Добавяме данните
                $rec->data->{$changeAction}[$logRec->id] = array(
                    'docId' => $logRec->docId,
                    'docClass' => $logRec->docClass
                );
            }
        }
        
        // Пушваме съответното действие
        static::pushAction($rec);
        
        // Съобщение в лога
        $msg = tr("Редактиран документ|*: ") . doc_Containers::getDocTitle($containerId);
        
        // Добавяме запис в лога
        core_Logs::add('doc_Containers', $rec->containerId, $msg);
        
        return $rec;
    }
    
    
    /**
     * Маркира файла, че е свален
     * 
     * @param string $mid
     * @param fileHnd $fh - Манипулатор на файла, който се сваля
     * 
     * @return object $rec
     */
    public static function downloaded($mid, $fh)
    {
        $downloadAction = static::ACTION_DOWNLOAD;
        
        // Очакваме да има запис, в който е цитиран файла
        expect($sendRec = static::getActionRecForMid($mid, FALSE));
        expect(is_object($sendRec->data));
        
        // Вземаме записа, ако има такъв
        $rec = static::fetch("#containerId = '{$sendRec->containerId}' AND #action = '{$downloadAction}'");
        
        // IP' то на потребителя
        $ip = core_Users::getRealIpAddr();
        
        // id' то на текущия потребител
        $currUser = core_Users::getCurrent('id');
        
        // 
        $actionToken = ($currUser) ? $currUser : $ip;

        // Ако съответния потребител е свалял файла
        if (!empty($rec->data->{$downloadAction}[$fh][$actionToken])) {
            
            return TRUE;    
        }
        
        // Датата и часа
        $date = dt::now(true);
        
        // Ако няма запис
        if (!$rec) {
            
            // Създаваме обект с данни
            $rec = (object)array(
                'action' => $downloadAction,
                'containerId' => $sendRec->containerId,
                'threadId'    => $sendRec->threadId,
                'data' => new stdClass(),
            );    
        }
        
        // Добавяме данните
        $rec->data->{$downloadAction}[$fh][$actionToken] = array(
            'ip' => $ip,
            'seenOnTime' => $date,
        );
        
        // Ако има логнат потребител
        if ($currUser) {
            
            // Добавяме id' то му
            $rec->data->{$downloadAction}[$fh][$actionToken]['seenFrom'] = $currUser; 
        }

        // Пушваме съответното действие
        static::pushAction($rec);
        
        // Добавяме запис в лога
        $msg = tr("Свален файл|*: ") . fileman_Download::getDownloadLink($fh);
        
        core_Logs::add('doc_Containers', $rec->containerId, $msg);

        return $rec;
    }
    
    
    /**
     * Случаен уникален идентификатор на документ
     *
     * @return string
     */
    protected static function generateMid()
    {
        do {
            $mid = str::getRand('Aaaaaaaa');
        } while (static::fetch("#mid = '{$mid}'", 'id'));
    
        return $mid;
    }
    
    
    /**
     * Изпълнява се преди всеки запис в модела
     * 
     * @param unknown_type $mvc
     * @param unknown_type $id
     * @param unknown_type $rec
     */
    static function on_BeforeSave($mvc, &$id, $rec)
    {
        if (empty($rec->data)) {
            $rec->dataBlob = NULL;
        } else {
            if (is_array($rec->data)) {
                $rec->data = (object)$rec->data;
            }
        
            $rec->dataBlob = $rec->data;
        }
    }
    
    
    /**
     * Изпълнява се след всеки запис в модела
     *
     * @param log_Documents $mvc
     * @param int $id key(mvc=log_Documents)
     * @param stdClass $rec запис на модела, който е бил записан в БД
     */
    static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
        if ((!$rec->threadId) && ($rec->containerId)) {
            $rec->threadId = doc_Containers::fetchField($rec->containerId, 'threadId');
        }
        expect($rec->threadId);
        
        // Изчистваме кешираната история на треда, понеже тя току-що е била променена.
        $mvc::removeHistoryFromCache($rec->threadId);
    }
    
    
    /**
     * Подготовка на историята на цяла нишка
     *
     * Данните с историята на треда се кешират, така че многократно извикване с един и същ
     * параметър няма негативен ефект върху производителността.
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return array ключ е contanerId, стойност - историята на този контейнер
     */
    protected static function prepareThreadHistory($threadId)
    {
        if (!isset(static::$histories[$threadId])) {
            $cacheKey = static::getHistoryCacheKey($threadId);
        
            if (($history = core_Cache::get(static::CACHE_TYPE, $cacheKey)) === FALSE) {
                // Историята на този тред я няма в кеша - подготвяме я и я записваме в кеша
                $history = static::buildThreadHistory($threadId);
                core_Cache::set(static::CACHE_TYPE, $cacheKey, $history, '2 дена');
            }       
            
            static::$histories[$threadId] = $history;
        }
        
        return static::$histories[$threadId];
    }
    
    
    /**
     * Изтрива от кеша записана преди история на нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     */
    static function removeHistoryFromCache($threadId)
    {
        $cacheKey = static::getHistoryCacheKey($threadId);
        
        core_Cache::remove(static::CACHE_TYPE, $cacheKey);
    }
    
    
    /**
     * Ключ, под който се записва историята на нишка в кеша
     *
     * @see core_Cache
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return string
     */
    protected static function getHistoryCacheKey($threadId)
    {
        return $threadId;
    }
    
    
    /**
     * Преизчислява историята на нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return array масив с ключ $containerId (на контейнерите от $threadId, за които има запис
     *                  в историята) и стойности - обекти (stdClass) със следната структура:
     *
     *  ->summary => array(
     *         [ACTION1] => брой,
     *         [ACTION2] => брой,
     *         ...
     *     )
     *         
     *  ->containerId - контейнера, чиято история се съдържа в обекта (за удобство)
     */
    protected static function buildThreadHistory($threadId)
    {
        static::log('Регенериране на историята на нишка', $threadId, 3);
        
        $query = static::getQuery();
        $query->where("#threadId = {$threadId}");
        $query->orderBy('#createdOn');
        
        $open = self::ACTION_OPEN;
        $download = self::ACTION_DOWNLOAD;
        $change = self::ACTION_CHANGE;
        $forward = self::ACTION_FORWARD;
        
        $data = array();   // Масив с историите на контейнерите в нишката
        while ($rec = $query->fetch()) {
            if (($rec->action != $open) && ($rec->action != $download) && ($rec->action != $change) && ($rec->action != $forward)) {
                $data[$rec->containerId]->summary[$rec->action] += 1;
            }
            $data[$rec->containerId]->summary[$open] += count($rec->data->{$open});
            $data[$rec->containerId]->summary[$download] += static::getCountOfDownloads($rec->data->{$download});
            $data[$rec->containerId]->summary[$change] += count($rec->data->{$change});
            $data[$rec->containerId]->summary[$forward] += count($rec->data->{$forward});
            $data[$rec->containerId]->containerId = $rec->containerId;
        }

        return $data;
    }
    
    
    /**
     * Връща броя на свалянията
     * 
     * @param array $data - Масив с данни, в които ще се търси
     * 
     * @return integer $downloadCount - Броя на свалянията на файловете
     */
    protected static function getCountOfDownloads($data)
    {
        // Ако е масив
        if (is_array($data)) {
            
            // Обхождаме масива
            foreach ($data as $downloadRec) {
                
                // Добавяме броя на свалянията към променливата
                $downloadCount += count($downloadRec);
            }  
        }

        return $downloadCount;
    }
    
    
    /**
     * Подготвя историята на един контейнер
     *
     * @param int $containerId key(mvc=doc_Containers)
     * @param int $threadId key(mvc=doc_Threads)
     */
    protected static function prepareContainerHistory($containerId, $threadId)
    {
        $threadHistory = static::prepareThreadHistory($threadId);
        
        return $threadHistory[$containerId];
    }

    
    /**
     * Рендира историята на действията
     */
    public static function renderSummary($data)
    {
        static $wordings = NULL;
        
        static $actionToTab = NULL;
        
        if (empty($data->summary)) {
            return '';
        }
        
        if (!isset($wordings)) {
            $wordings = array(
                static::ACTION_SEND    => array('изпращане', 'изпращания'),
                static::ACTION_RECEIVE => array('получаване', 'получавания'),
                static::ACTION_RETURN  => array('връщане', 'връщания'),
                static::ACTION_PRINT   => array('отпечатване', 'отпечатвания'),
                static::ACTION_OPEN   => array('виждане', 'виждания'),
                static::ACTION_DOWNLOAD => array('сваляне', 'сваляния'),
                static::ACTION_CHANGE => array('промяна', 'промени'),
                static::ACTION_FORWARD => array('препратен', 'препратени'),
            );
        }

        if (!isset($actionToTab)) {
            $actionToTab = array(
                static::ACTION_SEND    => static::ACTION_SEND,
                static::ACTION_FAX     => static::ACTION_SEND,
                static::ACTION_RECEIVE => static::ACTION_SEND,
                static::ACTION_RETURN  => static::ACTION_SEND,
                static::ACTION_PRINT   => static::ACTION_PRINT,
                static::ACTION_PDF     => static::ACTION_PRINT,
                static::ACTION_OPEN    => static::ACTION_OPEN,
                static::ACTION_DOWNLOAD    => static::ACTION_DOWNLOAD,
                static::ACTION_CHANGE    => static::ACTION_CHANGE,
                static::ACTION_FORWARD    => static::ACTION_FORWARD,
            );
        }
        
        $html = '';
        
        foreach ($data->summary as $action=>$count) {
            if ($count == 0) {
                continue;
            }
            $actionVerbal = $action;
            if (isset($wordings[$action])) {
                $actionVerbal = $wordings[$action][intval($count > 1)];
            }
            $linkArr = static::getLinkToSingle($data->containerId, $actionToTab[$action]);
	        $link = ht::createLink("<b>{$count}</b><span>{$actionVerbal}</span>", $linkArr);
            
            $html .= "<li class=\"action {$action}\">{$link}</li>";
        }
        
        $html = "<ul class=\"history summary\">{$html}</ul>";
        
        return $html;
    }
    
    
    /**
     * Връща линк към сингъла на документа
     * 
     * @param unknown_type $cid
     * @param unknown_type $action
     */
    static function getLinkToSingle($cid, $action)
    {
        $document = doc_Containers::getDocument($cid);
        $detailTab = ucfirst(strtolower($action));
        
        $link = array(
	                 $document, 
	                 'single', 
	                 $document->that,
	                 'Cid' => $cid, 
	                 'Tab' => $detailTab,
	                );

        return $link;
    }
    
    
    /**
     * Шаблон (ET) съдържащ обобщената историята на документа в този контейнер.
     *
     * @param int $container key(mvc=doc_Containers)
     * @param int $threadId key(mvc=doc_Thread) нишката,в която е контейнера
     * @return core_ET
     */
    public static function getSummary($containerId, $threadId)
    {
        $data = static::prepareContainerHistory($containerId, $threadId);
        
        return static::renderSummary($data);
    }

    
    /**
     * Връща форматирано виждането на документа
     * 
     * @param unknown_type $rec
     * @param unknown_type $deep
     */
    protected static function formatViewReason($rec, $deep = TRUE)
    {
        switch ($rec->action) {
            case static::ACTION_SEND:
                $row = (object)array('toEmail' => $rec->data->to);
                $row = static::recToVerbal($row, array_keys(get_object_vars($row)));
                return 'Имейл до ' . $row->toEmail . ' / ' . static::getVerbal($rec, 'createdOn');
            case static::ACTION_PRINT:
                return 'Отпечатване / ' . static::getVerbal($rec, 'createdOn');
            case static::ACTION_OPEN:
                if ($deep && !empty($rec->parentId)) {
                    $parentRec = static::fetch($rec->parentId);
                    $res = static::formatViewReason($parentRec, FALSE);
                } else {
                    $linkArr = static::getLinkToSingle($rec->containerId, static::ACTION_OPEN);
                    $doc = doc_Containers::getDocument($rec->containerId);
                    $docRow = $doc->getDocumentRow();
                    $res = 'Показване на ' . ht::createLink($docRow->title, $linkArr) . ' / ' . static::getVerbal($rec, 'createdOn');
                }
                return $res;
            default:
                return strtoupper($rec->action) . ' / ' . static::getVerbal($rec, 'createdOn');
        }
    }
    
    
    
    /**
     * Помощен метод - рендира историята на разглежданията на документ
     * 
     * @param stdClass $rec
     * @param string $date
     * @return string HTML 
     */
    private static function renderOpenActions($rec, $date = NULL, $brief = TRUE)
    {
        $openActionName = static::ACTION_OPEN;

        $html = '';
        
        if (!empty($rec->data->{$openActionName})) {
            $firstOpen = reset($rec->data->{$openActionName});
        }
        
        $_r = $rec->receivedOn;
        
        if (!empty($firstOpen) && (empty($date) || $firstOpen['on'] < $date)) {
            $rec->receivedOn = $firstOpen['on'];
        } else {
            $rec->receivedOn = $date;
        }
        
        $html .= static::getVerbal($rec, 'receivedOn');
        $linkArr = static::getLinkToSingle($rec->containerId, static::ACTION_OPEN);
        
        if (!empty($firstOpen)) {
            $html .= ' (' . $firstOpen['ip'] . ') ';
            $html .= ht::createLink(
                count($rec->data->{$openActionName}),
                $linkArr,
                FALSE,
                array(
                    'class' => 'badge',
                )
            );
        }
        
        $rec->receivedOn = $_r;
        
        return $html;
    }
    

    /**
     * Връща cid' а на документа от URL.
     * 
     * Проверява URL' то дали е от нашата система.
     * Проверява дали cid' а и mid'а си съвпадат.
     * Ако открие записа на документа проверява дали има родител.
     * Ако има родител връща cid'а на родителя. 
     * 
     * @param URL $url - URL от системата, в който ще се търси
     * 
     * @return integer $cid - Container id на документа
     */
    static function getDocumentCidFromURL($url)
    {
        // Проверяваме дали URL' то е от нашата система
        if (!static::isOurURL($url)) {
            
            return ;
        }
        
        // Вземаме cid'a и mid' а от URL' то
        $cidAndMidArr = static::getCidAndMidFromUrl($url);
        
        // Ако няма cid или мид
        if (!count($cidAndMidArr)) {
            
            return ;
        }
        
        // Вземам записа за съответния документ в лога
        $rec = log_Documents::fetchHistoryFor($cidAndMidArr['cid'], $cidAndMidArr['mid']);
        
        // Ако няма запис - mid' а не е правилен
        if (!$rec) {
            
            return ;
        }
        
        // Ако записа има parentId
        if ($rec->parentId) {
            
            // Задаваме cid'a да е containerId' то на родителския документ
            $cid = log_Documents::fetchField($rec->parentId, 'containerId');
        } else {
            
            $cid = $rec->containerId;
        }
        
        return $cid;
    }
    
    
    /**
     * Проверява подаденото URL далу е от системата.
     * 
     * @param URL $url - Линка, който ще се проверява
     * 
     * @return boolean - Ако открие съвпадение връща TRUE
     */
    static function isOurURL($url)
    {
        // Изчистваме URL' то от празни символи
        $url = trim($url);
        
        // Ако открием търсенто URL в позиция 0
        if (stripos($url, core_App::getBoot(TRUE)) === 0) {
            return TRUE;
        }
        
        return FALSE;
    }
    
    
    /**
     * Връща cid' а и mid' а от подаденото URL
     * 
     * @param URL $URL - Линка, в който ще се търси
     * 
     * @return array $res - Масив с ['cid'] и ['mid']
     */
    static function getCidAndMidFromUrl($url)
    {
        $bootUrl = core_App::getBoot(TRUE);
        
        // Ескейпваме името на директорията. Също така, допълнително ескейпваме и '/'
        $bootUrlEsc = preg_quote($bootUrl, '/');
        
        // Шаблон за намиране на mid'a и cid'а в URL
        // Шаблона работи само ако:
        // Класа е L
        // Екшъна е B или S
        // Веднага след тях следва ?m= за мида
        $pattern = "/(?'boot'{$bootUrlEsc}{1})\/(?'ctr'[L]{1})\/(?'act'[B|S]{1})\/(?'cid'[^\/]+)\/\?m\=(?'mid'[^$]+)/i";

        // Проверявама дали има съвпадение
        preg_match($pattern, $url, $matches);
        
        $res = array();
        
        // Ако намери cid и mid
        if (($matches['cid']) && ($matches['mid'])) {
            
            $res['cid'] = $matches['cid'];
            $res['mid'] = $matches['mid'];
        }

        return $res;
    }
    
    
    /**
     * Връща записа за съответния екшън и мид
     * 
     * @param string $mid - Mid' а на действието
     * @param string $action - Действието, което искаме да търсим
     * 
     * @return log_Documents - Обект с данни
     */
    static function getActionRecForMid($mid, $action=NULL)
    {
        // Ако не сме задали да не се проверява 
        if ($action === FALSE) {
            
            // Вземаме записа, ако има такъв
            $rec = static::fetch(array("#mid = '[#1#]'", $mid));
            
            // Ако екшъна е един от посочените, връщаме FALSE
            if (in_array($rec->action, array(self::ACTION_DISPLAY, self::ACTION_RECEIVE, self::ACTION_RETURN, self::ACTION_DOWNLOAD, self::ACTION_CHANGE, self::ACTION_FORWARD))) {
                
                return FALSE;
            }
        } else {
            
            // Акшъна по подразбиране да е send
            setIfNot($action, static::ACTION_SEND);
    
            // Вземаме записа, ако има такъв
            $rec = static::fetch(array("#mid = '[#1#]' AND #action = '{$action}'", $mid));
        }
        
        return $rec;
    }
    
    
    /**
     * При приключване на изпълнените на скрипта
     */
    public static function on_Shutdown($mvc)
    {
        // Записва в БД всички действия от стека
        static::flushActions();
    }
    
    
    /**
     * Записва в БД всички действия от стека
     */
    public static function flushActions()
    {
        $count = 0;
        
        while ($action = static::popAction()) {
            static::save($action);
            $count++;
        }

        if($count > 0) {
            core_Logs::add(get_called_class(), NULL, "Записани {$count} действия");
        }
    }
    
    
    /**
     * Проверява дали е изпратен имейл от този контейнер към имейлите
     * 
     * @param integer $containerId - id на контейнера
     * @param string $emailTo - Имейли в to
     * @param string $emailCc - Имейли в cc
     */
    static function isSended($containerId, $emailTo=FALSE, $emailCc=NULL)
    {
        // Ако не е подадено $containerId
        if (!$containerId) return FALSE;
        
        // Конфигурацията на пакета
        $conf = core_Packs::getConfig('log');
        
        // Датата след което ще се брои за повторно изпращане
        $resendingTime = time() - $conf->LOG_EMAIL_RESENDING_TIME;
        
        // В MYSQL вид
        $resendingTime = dt::timestamp2Mysql($resendingTime);
        
        // Екшъна за изпращане
        $sendAction = static::ACTION_SEND;
        
        // Извличаме всички изпратени имейли от този контейнер изпратени преди датата за повторно изпращане
        $query = static::getQuery();
        $query->where("#containerId = '{$containerId}'");
        $query->where("#action = '{$sendAction}'");
        $query->where("#createdOn < '{$resendingTime}'");
        
        // Обхождаме всички записи
        while ($rec = $query->fetch()) {
            
            // Ако имейлите до са зададени
            if ($emailTo !== FALSE) {
                
                // Ако има CC имейли
                if ($emailCc) {
                    
                    // Проверяваме to и cc дали съвпадат
                    if (($rec->data->cc == $emailCc) && ($rec->data->to == $emailTo)) return TRUE;
                } else {
                    
                    // Ако няма CC, проверяваме само to
                    if ($rec->data->to == $emailTo) return TRUE;    
                }
            } elseif ($rec) {
                
                // Ако няма to имйел, но има изпращане
                return TRUE;
            }    
        }

        return FALSE;
    }
}