<?php



/**
 * Клас 'core_Cron' - Стартиране на процеси по часовник
 *
 * Поддържа механизъм за периодично (по часовник) извикване на методи
 * в системата, които са регистрирани в таблицата на този клас и името
 * им започва с приставката 'cron_'
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
class core_Cron extends core_Manager
{
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = 'Периодични процеси';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = 'Периодичeн процес';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "id,title=Описание,parameters=Параметри,last=Последно,state";
    
    
    /**
     * Списък с плъгини, които се прикачат при конструиране на мениджъра
     */
    var $loadList = "plg_Created,plg_Modified,plg_SystemWrapper,plg_RowTools,plg_RefreshRows,plg_State2";
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'admin';



    /**  
	 * Кой има право да променя системните данни?  
	 */  
	var $canEditsysdata = 'admin';  

	
    
    /**
     * Време за опресняване информацията при лист на събитията
     */
    var $refreshRowsTime = 5000;
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('systemId', 'varchar', 'caption=Системен ID,notNull');
        $this->FLD('description', 'varchar', 'caption=Описание');
        $this->FLD('controller', 'varchar(64)', 'caption=Контролер');
        $this->FLD('action', 'varchar(32)', 'caption=Функция');
        $this->FLD('period', 'minutes', 'caption=Период (мин)');
        $this->FLD('offset', 'minutes', 'caption=Отместване (мин)');
        $this->FLD('delay', 'int', 'caption=Закъснение (s)');
        $this->FLD('timeLimit', 'int', 'caption=Време-лимит (s)');
        $this->FLD('state', 'enum(free=Свободно,locked=Заключено,stopped=Спряно)', 'caption=Състояние,1input=none');
        $this->FLD('lastStart', 'datetime', 'caption=Последно->Стартиране,input=none');
        $this->FLD('lastDone', 'datetime', 'caption=Последно->Приключване,input=none');

        $this->setDbUnique('systemId,offset,delay');
    }
    

    /**
     * Преди извличането на записите за листовия изглед
     */
    function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy('#period'); 
    }
    

    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        $data->toolbar->addBtn('Логове на Cron', array(
                'core_Logs',
                'className' => $mvc->className
            ),
            'ef_icon = img/16/action_log.png');
    }
    
    
    /**
     * Този метод се задейства през интервал от 1 минута от OS
     */
    function act_Cron()
    {
        header('Cache-Control: no-cache, no-store');
        
        // Отключваме всички процеси, които са в състояние заключено, а от последното
        // им стартиране е изминало повече време от Време-лимита-а
        $query = $this->getQuery();
        $query->where("#state = 'locked'");
        $now = dt::verbal2mysql();
        $query->where("ADDTIME(#lastStart, SEC_TO_TIME(#timeLimit)) < '{$now}'");
        
        while ($rec = $query->fetch()) {
            $rec->state = 'free';
            $this->save($rec, 'state');
            $this->log("Warning: {$this->className} unlock process {$rec->systemId}", NULL, 7);
        }
        
        // Коя е текущата минута?
        $timeStamp = time();
        $currentMinute = round($timeStamp / 60);
        
        // Определяме всички процеси, които трябва да се стартират през тази минута
        // и ги стартираме наред
        $query = $this->getQuery();
        $query->where("MOD({$currentMinute}, #period) = #offset AND #state != 'stopped'");
        $i = 0;
        
        while ($rec = $query->fetch()) {
            $i++;
            fopen(toUrl(array(
                        'Act' => 'ProcessRun',
                        'id' => str::addHash($rec->id)
                    ), 'absolute'), 'r');
            echo "\n\r<li>" . toUrl(array(
                    'Act' => 'ProcessRun',
                    'id' => str::addHash($rec->id)
                ), 'absolute');
        }
        
        $host = gethostbyname($_SERVER['SERVER_NAME']);

        $Os = cls::get('core_Os');

        $apacheProc = $Os->countApacheProc();
        
        $this->logThenStop("{$this->className} is working: {$i} processes was run in $currentMinute, total {$apacheProc} Apaches on server");
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function act_ProcessRun()
    {
        // Форсираме системния потребител
        core_Users::forceSystemUser();
        
        // Затваряме връзката създадена от httpTimer, ако извикването не е форсирано
        if(!$forced = Request::get('forced')) {
            header("Connection: close");
            ob_start();
            session_write_close();
            header("Content-Length: 0");
            @ob_end_flush();
            flush();
        } else {
            header ('Content-type: text/html; charset=utf-8');
        }
        
        // Декриптираме входния параметър. Чрез предаването на id-to на процеса, който
        // трябва да се стартира в защитен вид, ние се предпазваме от евентуална външна намеса
        $id = str::checkHash(Request::get('id'));
        
        if (!$id || !is_numeric($id)) {
            $cryptId = Request::get('id');
            $this->logThenStop("Error: ProcessRun -> incorrect crypted id: {$cryptId}");
        }
        
        // Вземаме информация за процеса
        $rec = $this->fetch($id);
        
        if (!$rec) {
            $this->logThenStop("Error: ProcessRun -> missing record for  id = {$id}");
        }
        
        // Дали процесът не е заключен?
        if ($rec->state == 'locked' && !$forced) {
            $this->logThenStop("Error: Process \"{$rec->systemId}\" is locked!");
        }
        
        // Дали този процес не е стартиран след началото на текущата минута
        $nowMinute = date("Y-m-d H:i:00", time());
        if ($nowMinute <= $rec->lastStart && !$forced) {
            $this->logThenStop("Error: Process \"{$rec->systemId}\" have been started after $nowMinute!");
        }
        
        // Заключваме процеса и му записваме текущото време за време на последното стартиране
        $rec->state = 'locked';
        $rec->lastStart = dt::verbal2mysql();
        $rec->lastDone = NULL;
        $this->save($rec, 'state,lastStart,lastDone');
        
        // Изчакваме преди началото на процеса, ако е зададено 
        if ($rec->delay > 0) {
            sleep($rec->delay);
        }
        
        // Стартираме процеса
        $act = 'cron_' . $rec->action;
        
        $class = cls::getClassName($rec->controller);
        
        $handlerObject = & cls::get($class);
        
        if (is_a($handlerObject, $class)) {
            if (method_exists($handlerObject, $act)) {
                $msg = "ProcessRun found {$rec->controller}->{$act}";
                $this->log($msg, $rec->id, 7);
                
                // Ако е зададено максимално време за изпълнение, 
                // задаваме го към PHP , като добавяме 5 секунди
                if ($rec->timeLimit) {
                    set_time_limit($rec->timeLimit + 5);
                }
                
                $startingMicroTime = $this->getMicrotime();
                $content = $handlerObject->$act();
                
                // Ако извикания метод е генерирал резултат, то го добавяме
                // подходящо форматиран към лога
                if ($content) {
                    $content = "<p><i>$content</i></p>";
                }
                
                $workingTime = round($this->getMicrotime() - $startingMicroTime, 2);
                
                // Колко време да пазим лога?
                $logLifeTime = max(1, 3 * round($rec->period / (24 * 60)));
                
                $msg = "ProcessRun successfuly execute {$rec->controller}->{$act} for {$workingTime}sec. {$content}";
                $this->log($msg, $rec->id, $logLifeTime);
            } else {
                $this->unlockProcess($rec);
                $this->logThenStop("Error: ProcessRun -> missing method \"$act\" on class  {$rec->controller}", $rec->id);
                echo(core_Debug::getLog());
                shutdown();
            }
        } else {
            $this->unlockProcess($rec);
            $this->logThenStop("Error: ProcessRun -> missing class  {$rec->controller} in process ", $rec->id);
            echo(core_Debug::getLog());
            shutdown();
        }
        
        // Отключваме процеса и му записваме текущото време за време на последното приключване
        $this->unlockProcess($rec);
        echo(core_Debug::getLog());
        shutdown();
    }


    /**
     * Записва в лога и спира
     */
    function logThenStop($msg, $id = NULL)
    {
        $this->log($msg, $id, 7);
        echo(core_Debug::getLog());
        shutdown();
    }
    
    
    /**
     * Отключва заключен процес
     */
    function unlockProcess($rec)
    {
        $rec->state = 'free';
        $rec->lastDone = dt::verbal2mysql();
        $this->save($rec, 'state,lastDone');
    }
    
    
    /**
     * Изпълнява се след поготовка на формата за редактиране
     */
    static function on_AfterPrepareEditForm($mvc, $data)
    {
        $data->form->setOptions('state', array('free' => 'Свободно',
                'stopped' => 'Спряно'
            ));
    }
    
    
    /**
     * Изпълнява се при всяко преобразуване на запис към вербални стойности
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        // За по-голяма точност, показваме и секундите
        $row->lastStart = dt::mysql2verbal($rec->lastStart, "d.m.y  H:i:s");
        $row->lastDone = dt::mysql2verbal($rec->lastDone, "d.m.y  H:i:s");
        
        $row->description = $mvc->getVerbal($rec, 'description');
        $row->controller = $mvc->getVerbal($rec, 'controller');
        $row->action = $mvc->getVerbal($rec, 'action');
        $row->period = $mvc->getVerbal($rec, 'period');
        $row->offset = $mvc->getVerbal($rec, 'offset');
        $row->delay = $mvc->getVerbal($rec, 'delay');
        $row->timeLimit = $mvc->getVerbal($rec, 'timeLimit');
        $row->systemId = $mvc->getVerbal($rec, 'systemId');
        
        if($rec->lastStart) {
            $row->last = "<p>От: <b>{$row->lastStart}</b>";
        }
        
        if($rec->lastDone) {
            $row->last .= "<p>До: <b>{$row->lastDone}</b>";
        }
        
        $url = toUrl(array(
                'Act' => 'ProcessRun',
                'id' => str::addHash($rec->id),
                'forced' => 'yes'
            ), 'absolute');
        
        $row->systemId = ht::createLink("<b>{$row->systemId}</b>", $url, NULL, array('target' => 'cronjob'));
        
        $row->title = "<p>" . $row->systemId . "</p><p><i>{$row->description}</i></p>";
        
        $row->parameters = "<p style='color:green'><b>\${$row->controller}->{$row->action}</b><p>" .
        "Всеки <b>{$row->period}</b> + <b>{$row->offset}</b>";
        
        if($rec->delay) {
            $row->parameters .= ", Зак.: <b>{$row->delay}</b> s";
        }
        
        if($rec->timeLimit) {
            $row->parameters .= ", Лимит: <b>{$row->timeLimit}</b> s";
        }
        
        $now = dt::mysql2timestamp(dt::verbal2mysql());
        
        if($rec->state == 'locked' ||
            ($rec->state == 'free' && ($now - $mvc->refreshRowsTime / 1000-2) < dt::mysql2timestamp($rec->lastStart))) {
            
            $row->ROW_ATTR['style'] .= 'background-color:#ffa;';
        } elseif ($rec->state == 'free') {
            
            $row->ROW_ATTR['style'] .= 'background-color:#cfc;';
        } elseif ($rec->state == 'stopped') {
            
            $row->ROW_ATTR['style'] .= 'background-color:#aaa;';
        }
    }
    
    
    /**
     * Връща timestamp в микро секунди, като рационално число
     */
    static function getMicrotime()
    {
        list($usec, $sec) = explode(" ", microtime());
        
        return ((float) $usec + (float) $sec);
    }
    
    
    /**
     * Добавя запис, като гледа да няма запис със същото systemId
     * 
     * return boolean
     */
    static function addOnce($rec)
    {
        
        if(is_array($rec)) {
            $rec = (object) $rec;
        }
        
        expect($rec->systemId);
        expect($rec->description);
        expect($rec->controller);
        expect($rec->action);
        
        // Периода трябва да е по-голям от една минута
        expect($rec->period >= 1);
        
        // Офсета трябва да е по-голям от нула и да е по-малък от периода
        $rec->offset = max(0, $rec->offset);
        expect($rec->period > $rec->offset);
 
        // Търсим дали има съществуващ запис със същото id
        $exRec = self::fetch(array("#systemId = '[#1#]'", $rec->systemId ));

        
        // Записваме, че записът е създаден от системния потребител
        setIfNot($rec->createdBy, -1);

        // Ако няма зададено преди това състояние - то е празно
        setIfNot($rec->state, 'free');

        // По подразбиране 50 секунди времелимит за извършване на операцията
        setIfNot($rec->timeLimit, 50);

        // Описанието с малки букви
        $description = mb_strtolower(mb_substr($rec->description, 0, 1)) . mb_substr($rec->description, 1);
        
        // Ако има стар запис 
        if ($exRec) {
            
            $rec->id = $exRec->id;

            if( $rec->systemId != $exRec->systemId ||
                $rec->description != $exRec->description ||
                $rec->controller != $exRec->controller ||
                $rec->action != $exRec->action ||
                $rec->period != $exRec->period ||
                floor($rec->offset) != floor($exRec->offset) ||
                floor($rec->delay) != floor($exRec->delay) ||
                $rec->timeLimit != $exRec->timeLimit) {

                $mustSave = TRUE;
                $msg = "<li class=\"debug-update\">Обновено {$description} по разписание</li>";
            } else {
                $mustSave = FALSE;
                $msg = "<li class=\"debug-info\">Съществуващо {$description} по разписание</li>";
            }

        } else {
            $mustSave = TRUE;
            $msg = "<li class=\"debug-new\">Добавено {$description} по разписание</li>";
        }
 

        if($mustSave) {
            if(!self::save($rec)) {
                $msg = "<li class=\"debug-error\">Грешка при нагласяне на {$description} по разписание</li>";
            }
        }

        return $msg;
    }
    
    
    /**
     * Рутинен метод, премахва задачите, свързани с класове от посочения пакет
     */
    static function deinstallPack($pack)
    {
        $query = self::getQuery();
        $preffix = $pack . "_";
        $query->delete(array("#controller LIKE '[#1#]%'", $preffix));
    }
    

    /**
     * Премахва методите, за които не съществуват входни точки
     */
    static function cleanRecords()
    {
        $query = self::getQuery();

        while($rec = $query->fetch()) {
            if(cls::load($rec->controller, TRUE)) {
                $ctr = cls::get($rec->controller);
                if(method_exists($ctr, 'cron_' . $rec->action)) {
                    continue;
                }
            }
            
            $class = cls::getClassName($rec->controller);

            self::delete($rec->id);

            $res .= ($res ? ', ' : '') . "{$class}::{$rec->action}";
        }

        if($res) {

            return "<li style='color:green;'>Премахнати бяха липсващите входни точки за Cron: {$res}</li>";
        }
    }
    
    
    /**
     * Връща времето на следващото стартиране на крона
     * 
     * @param string $systemId
     * 
     * @return date $nextStartTime
     */
    static function getNextStartTime($systemId)
    {
        // Вземаме записитеи за тази ситема
        $rec = core_Cron::fetch("#systemId = '{$systemId}'");
        
        // Ако е спрян или няма период
        if ($rec->state == 'stopped' || !$rec->period) {
            
            // Връщаме FALSE
            return FALSE;
        }
        
        // Текущото време
        $now = dt::now();
        
        // Ако няма време на последно стартиране
        if (!($startTime = $rec->lastStart)) {
            
            // Използваме текущото време
            $startTime = $now;
        }
        
        // Добавяме секундите
        $nextStartTime = dt::addSecs($rec->period * 60, $startTime);
        
        // Ако е преди текущото време, връщаме NULL
        if ($nextStartTime < $now) return NULL;
        
        return $nextStartTime;
    }
}
