<?php



/**
 * Календар - всички събития
 *
 *
 * @category  bgerp
 * @package   cal
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cal_Calendar extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Календар на събития и празници";
    
    
    /**
     * Класове за автоматично зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, cal_Wrapper, plg_Sorting, plg_State, bgerp_plg_GroupByDate, cal_View, plg_Printing, plg_Search';
    
    
    /**
     * полета от БД по които ще се търси
     */
    var $searchFields = 'title';

    /**
     * Името на полито, по което плъгина GroupByDate ще групира редовете
     */
    var $groupByDateField = 'time';
    

    /**
     * Полетата, които ще видим в таблицата
     */
    var $listFields = 'time,event=Събитие';
    
    // var $listFields = 'date,event=Събитие,type,url';
    
    
    /**
     *  @todo Чака за документация...
     */
    // var $searchFields = '';
    
    static  $specialDates = array();
    
    /**
     * Кой може да пише
     */
    var $canWrite = 'no_one';
    
    
    /**
     * Кой може да чете
     */
    var $canRead = 'user';
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'user';
    
    // Масив с цветове за събитията
    static $colors = array( "#610b7d", 
				    	"#1b7d23",
				    	"#4a4e7d",
				    	"#7d6e23", 
				    	"#33757d",
				    	"#211b7d", 
				    	"#72147d",
				    	"Violet",
				    	"Green",
				    	"DeepPink ",
				    	"MediumVioletRed",
				    	"#0d777d",
				    	"Indigo",
				    	"#7d1c24",
				    	"DarkSlateBlue",
				    	"#7b237d", 
				    	"DarkMagenta ",
    	                "#610b7d", 
				    	"#1b7d23",
				    	"#4a4e7d",
				    	"#7d6e23", 
				    	"#33757d",
				    	"#211b7d", 
				    	"#72147d",
				    	"Violet",
				    	"Green",
				    	"DeepPink ",
				    	"MediumVioletRed",
				    	"#0d777d",
				    	"Indigo",
				    	"#7d1c24",
				    	"DarkSlateBlue",
				    	"#7b237d", 
				    	"DarkMagenta ");
    
    
    //Начална стойнности за начало на деня
    static	$tr = 8;
    	
    //Начална стойност за края на деня
    static	$tk = 18;
    
    // Дните от седмицата
    static $weekDays = array('Понеделник', 'Вторник', 'Сряда', 'Четвъртък', 'Петък', 'Събота', 'Неделя');
    
    // Масив с часове в деня
    static $hours = array( "allDay" => "Цял ден");
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Уникален ключ за събитието
        $this->FLD('key', 'varchar(32)', 'caption=Ключ');

        // Дата на събититието
        $this->FLD('time', new type_Datetime(array('cellAttr' => 'class="portal-date"', 'format' => 'smartTime')), 'caption=Време');
        
        // Продължителност на събитието
        $this->FLD('duration', 'time', 'caption=Продължителност');

        // Тип на събититето. Той определя и иконата на събититето
        $this->FLD('type', 'varchar(32)', 'caption=Тип');
        
        // За кои потребители се отнася събитието. Празно => за всички
        $this->FLD('users', 'keylist(mvc=core_Users,title=nick)', 'caption=Потребители');

        // Заглавие на събитието
        $this->FLD('title', 'varchar', 'caption=Заглавие');

        // Приоритет 1=Нисък, 2=Нормален, 3=Висок, 4=Критичен, 0=Никакъв (приключена задача)
        $this->FLD('priority', 'int', 'caption=Приоритет,notNull,value=1');

        // Локално URL към обект, даващ повече информация за събитието
        $this->FLD('url',  'varchar', 'caption=Url,column=none');
        
        // Дали събитието се отнася за целия ден
        $this->FLD('allDay', 'enum(yes=Да,no=Не)', 'caption=Цял ден?');
        
        // Индекси
         $this->setDbUnique('key');
    }


    /**
     * Обновява събитията в календара
     *
     * @param $events   array   Масив със събития
     * @param $fromDate date    Начало на периода за който се отнасят събитията
     * @param $fromDate date    Край на периода за който се отнасят събитията
     * @param $prefix   string  Префикс на ключовете за събитията от този източник
     * 
     * @return $status array Статус на операцията, който съдържа:
     *      о ['updated'] броя на обновените събития
     * 
     */
    static function updateEvents($events, $fromDate, $toDate, $prefix)
    {
        $query    = self::getQuery();
        $fromTime = $fromDate . ' 00:00:00';
        $toTime   = $toDate   . ' 23:59:59';

        $query->where("#time >= '{$fromTime}' AND #time <= '{$toTime}' AND #key LIKE '{$prefix}%'");
        
        // Извличаме съществуващите събития за този префикс
        $exEvents = array();
        while($rec = $query->fetch()) {
            $exEvents[$rec->key] = $rec;
        }
 
        // Инициализираме резултатния масив
        $res = array(
            'new' => 0,
            'updated' => 0,
            'deleted' => 0
            );

        // Обновяваме информацията за новопостъпилите събития
        if(count($events)) {
            foreach($events as $e) {
                if(!trim($e->users)) {
                    unset($e->users);
                }
                if(($e->id = $exEvents[$e->key]->id) ||
                   ($e->id = self::fetchField("#key = '{$e->key}'", 'id')) ) {
                    unset($exEvents[$e->key]);
                    $res['updated']++;
                } else {
                    $res['new']++;
                }

                self::save($e);
            }
        }

        // Изтриваме старите записи, които не са обновени
        foreach($exEvents as $e) {
            self::delete("#key = '{$e->key}'");
            $res['deleted']++;
        }
        
        return $res;
    }
        
    
    /**
     * Прилага филтъра, така че да се показват записите след посочената дата
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
    	
        $data->query->orderBy("#time=ASC,#priority=DESC");
        
        if($data->action == 'list' || $data->action == 'day' || $data->action == 'week'){
	        if($from = $data->listFilter->rec->from) {
	        	
	            $data->query->where("#time >= date('$from')");
	          	        
	       }
        }
        
      if(!$data->listFilter->rec->selectedUsers) {
      	
		  $data->listFilter->rec->selectedUsers = 
		  type_Keylist::fromArray(arr::make(core_Users::getCurrent('id'), TRUE));
	  }
       // bp($data->listFilter->rec);
        if($data->listFilter->rec->selectedUsers) {
          
	        $data->query->likeKeylist('users', $data->listFilter->rec->selectedUsers);
	        $data->query->orWhere('#users IS NULL OR #users = ""');
          
        } 
    }
    
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
    	$cu = core_Users::getCurrent();

        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('from', 'date', 'caption=От,input,silent, width = 150px');
        $data->listFilter->FNC('selectedUsers', 'users', 'caption=Потребител,input,silent', array('attr' => array('onchange' => 'this.form.submit();')));
        $data->listFilter->setdefault('from', date('Y-m-d'));
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        if($data->action === "list"){
        	$data->listFilter->showFields = 'from, search, selectedUsers';
        } else{
        	$data->listFilter->showFields = 'from, selectedUsers';
        }
        
        $data->listFilter->input('selectedUsers, from', 'silent');
    }

    
    function on_AfterRenderWrapping($mvc, &$tpl)
    {
    	jquery_Jquery::enable($tpl);
    	
    	$tpl->push('cal/tpl/style.css', 'CSS');
    	$tpl->push('cal/js/mouseEvent.js', 'JS');
    	
    }
    
 	function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec, $userId)
    {
    	//bp(&$roles, $action, $rec, $userId);
    	if($action == 'day' || $action == 'week' || $action == 'month' || $action == 'year'){
	    	 $requiredRoles = 'user';
         }
    }
    
    /**
     * Конвертира един запис в разбираем за човека вид
     * Входният параметър $rec е оригиналният запис от модела
     * резултата е вербалният еквивалент, получен до тук
     */
    static function recToVerbal(&$rec)
    {
    	
    	$row = parent::recToVerbal_($rec);

    	$lowerType = strtolower($rec->type);
        $url = getRetUrl($rec->url);
        $attr['class'] = 'linkWithIcon';
        if($rec->type == 'leave'){
        	$attr['style'] = 'background-image:url(' . sbf("img/16/beach.png") . ');';
        } elseif($rec->type == 'sickday') {
        	$attr['style'] = 'background-image:url(' . sbf("img/16/sick.png") . ');';
        }else{
        	$attr['style'] = 'background-image:url(' . sbf("img/16/{$lowerType}.png") . ');';
   		}
        if($rec->priority <= 0) {
            $attr['style'] .= 'color:#aaa;text-decoration:line-through;';
        }
        $row->event = ht::createLink($row->title, $url, NULL, $attr);
     
        $today     = date('Y-m-d');
        $tommorow  = date('Y-m-d', time() + 24 * 60 * 60);
        $dayAT = date('Y-m-d', time() + 48 * 60 * 60);
        $yesterday = date('Y-m-d', time() - 24 * 60 * 60);
      
        list($rec->date,) = explode(' ', $rec->time);

        $row->date = dt::mysql2verbal($rec->time, 'd.m.Y');        

        if($rec->date == $today) {
            $row->ROW_ATTR['style'] .= 'background-color:#ffc;';
        } elseif($rec->date == $tommorow) {
            $row->ROW_ATTR['style'] .= 'background-color:#efc;';
        } elseif($rec->date == $dayAT) {
            $row->ROW_ATTR['style'] .= 'background-color:#dfc;';
        } elseif($rec->date == $yesterday) {
            $row->ROW_ATTR['style'] .= 'background-color:#eee;';
        } elseif($rec->date > $today) {
            $row->ROW_ATTR['style'] .= 'background-color:#cfc;';
        } elseif($rec->date < $yesterday) {
            $row->ROW_ATTR['style'] .= 'background-color:#ddd;';
        }

        
        return $row;
    }


    /**
     * Рендира календар за посочения месец
     *
     * @param int  $year Година
     * @param int  $month Месец
     * @param array $data  Масив с данни за дните в месеца
     *     о  $data[...]->isHoliday - дали е празник
     *     о  $data[...]->url - URL, където трябва да сочи посочения ден
     *     о  $data[...]->html - съдържание на клетката, осен датата
     * @param string $header - заглавие на календара
     *
     * @return string
     */
    static function renderCalendar($year, $month, $data = array(), $header = NULL)
    {   
        // Таймстамп на първия ден на месеца
        $firstDayTms = mktime(0, 0, 0, $month, 1, $year);

        // Броя на дните в месеца (= на последната дата в месеца);
        $lastDay = date('t', $firstDayTms);
        
        // Днес
        $today = date('j-n-Y');

        for($i = 1; $i <= $lastDay; $i++) {
            $t = mktime(0, 0, 0, $month, $i, $year);
            $monthArr[date('W', $t)][date('N', $t)] = $i;
        }

        $html = "<table class='mc-calendar'>";        

        $html .= "<tr><td colspan='8' style='padding:0px;'>{$header}</td><tr>";

        // Добавяне на втория хедър
        $html .= "<tr><td>" . tr('Сд') . "</td>";
        foreach(dt::$weekDays as $wdName) {
            $wdName = tr($wdName);
            $html .= "<td class='mc-wd-name'>{$wdName}</td>";
        }
        $html .= '<tr>';

        foreach($monthArr as $weekNum => $weekArr) {
            $html .= "<tr>";
            $html .= "<td class='mc-week-nb'>$weekNum</td>";
            for($wd = 1; $wd <= 7; $wd++) {
                if($d = $weekArr[$wd]) { 
                    if($data[$d]->type == 'holiday') {  
                        $class = 'mc-holiday';
                    } elseif(($wd == 6 || ($data[$d]->type == 'non-working' && $wd >= 4) ) && ($data[$d]->type != 'workday')) {
                        $class = 'mc-saturday';
                    } elseif(($wd == 7 || ($data[$d]->type == 'non-working' && $wd < 4) ) && ($data[$d]->type != 'workday')) {
                        $class = 'mc-sunday';
                    } else {
                        $class = '';
                    }
                    if($today == "{$d}-{$month}-{$year}") {
                        $class .= ' mc-today';
                    }
                    
                    // URL към което сочи деня
                    $url = $data[$d]->url;
                 
                    // Съдържание на клетката, освен датата
                    $content = $data[$d]->html;

                    $html .= "<td class='{$class} mc-day' onclick='document.location=\"{$url}\"'>{$content}$d</td>";

                } else {
                    $html .= "<td class='mc-empty'>&nbsp;</td>";
                }
            }
            $html .= "</tr>";
        }

        $html .= "</table>";
        
        return $html;
    }




    /**
     * Рендира блока за портала на текущия потребител
     */
    static function renderPortal()
    {
        $month = Request::get('cal_month', 'int');
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        $year  = Request::get('cal_year', 'int');

        if(!$month || $month < 1 || $month > 12 || !$year || $year < 1970 || $year > 2038) {
            $year = date('Y');
            $month = date('n');
        }

        // Добавяне на първия хедър
        $currentMonth = tr(dt::$months[$month-1]) . " " . $year;

        $pm = $month-1;
        if($pm == 0) {
            $pm = 12;
            $py = $year-1;
        } else {
            $py = $year;
        }
        $prevMonth = tr(dt::$months[$pm-1]) . " " .$py;

        $nm = $month+1;
        if($nm == 13) {
            $nm = 1;
            $ny = $year+1;
        } else {
            $ny = $year;
        }
        $nextMonth = tr(dt::$months[$nm-1]) . " " .$ny;
        
        $link = $_SERVER['REQUEST_URI'];
        $nextLink = Url::addParams($link, array('cal_month' => $nm, 'cal_year' => $ny));
        $prevtLink = Url::addParams($link, array('cal_month' => $pm, 'cal_year' => $py));

        $header = "<table class='mc-header' width='100%' cellpadding='0'>
                <tr>
                    <td align='left'><a href='{$prevtLink}'>{$prevMonth}</a></td>
                    <td align='center'><b>{$currentMonth}</b></td>
                    <td align='right'><a href='{$nextLink}'>{$nextMonth}</a></td>
                </tr>
            </table>";
        
        
        // Съдържание на клетките на календара 
	       
        //От началото на месеца
        $from = "{$year}-{$month}-01 00:00:00";
        
        // До последния ден за месеца
        $lastDay = date('d', mktime(12, 59, 59, $month + 1, 0, $year));
        $to = "{$year}-{$month}-{$lastDay} 23:59:59";
       
        // Подготвяме заглавието на таблицата
        //$state->title = tr("Календар");

        $state = new stdClass();
        $state->query = self::getQuery();
        
    
        // Само събитията за текущия потребител или за всички потребители
        $cu = core_Users::getCurrent();
        $state->query->where("#users IS NULL OR #users = ''");
        $state->query->orLikeKeylist('users', "|$cu|");

        $state->query->where("#time >= '{$from}' AND #time <= '{$to}'");

        $Calendar = cls::get('cal_Calendar');
        $Calendar->prepareListFields($state);
        $Calendar->prepareListFilter($state);
        $Calendar->prepareListRecs($state); 
        $Calendar->prepareListRows($state);
        
        // Подготвяме лентата с инструменти
        $Calendar->prepareListToolbar($state);

        if (is_array($state->recs)) {
            foreach($state->recs as $id => $rec) {
            	//bp($id, $rec);
                if($rec->type == 'holiday' || $rec->type == 'non-working' || $rec->type == 'workday') {
                    $time = dt::mysql2timestamp($rec->time);
                    $i = (int) date('j', $time);
                    if(!isset($data[$i])) {
                        $data[$i] = new stdClass();
                      
                    }
                    $data[$i]->type = $rec->type;
                   
                } elseif($rec->type == 'workday') {
                } elseif($rec->type == 'task'){
                	$time = dt::mysql2timestamp($rec->time);
                    $i = (int) date('j', $time);
                	if(!isset($data[$i])) {
                        $data[$i] = new stdClass();
                      
                    }
                    $data[$i]->html = "<img style='height10px;width:10px;' src=". sbf('img/16/star_2.png') .">&nbsp;";
                }
                
            }
        }
        for($i = 1; $i <= 31; $i++) {
            if(!isset($data[$i])) {
                $data[$i] = new stdClass();
            }
            $data[$i]->url = toUrl(array('cal_Calendar', 'list', 'from' => "{$i}-{$month}-{$year}"));;
        }

        $tpl = new ET("[#MONTH_CALENDAR#] <br> [#AGENDA#]");

        $tpl->replace(static::renderCalendar($year, $month, $data, $header), 'MONTH_CALENDAR');


        // Съдържание на списъка със събития

        // От вчера 
        $previousDayTms = mktime(0, 0, 0, date('m'), date('j')-1, date('Y'));
        $from = dt::timestamp2mysql($previousDayTms);

        // До вдругиден
        $afterTwoDays = mktime(0, 0, -1, date('m'), date('j')+3, date('Y'));
        $to = dt::timestamp2mysql($afterTwoDays);
       
        $state = new stdClass();
        $state->query = self::getQuery();

        // Само събитията за текущия потребител или за всички потребители
        $cu = core_Users::getCurrent();
        $state->query->where("#users IS NULL OR #users = ''");
        $state->query->orLikeKeylist('users', "|$cu|");

        $state->query->where("#time >= '{$from}' AND #time <= '{$to}'");

        $Calendar->prepareListFields($state);
        $Calendar->prepareListFilter($state); 
        $Calendar->prepareListRecs($state); 
        $Calendar->prepareListRows($state);

        $tpl->replace($Calendar->renderListTable($state), 'AGENDA');

        return $tpl;
        //return static::renderWrapping($tpl);
    }

    
    /**
     * Функция извеждаща броя на работните, неработните и празничните дни в един месец
     */
    static public function calculateDays($month, $year)
    {
    
    	// Ако е въведен несъществуващ месец или година, взима текущите данни
        if(!$month || $month < 1 || $month > 12 || !$year || $year < 1970 || $year > 2038) {
            $year = date('Y');
            $month = date('n');
            
        }
        
        // Таймстамп на първия ден в месеца
        $timestamp = strtotime("$year-$month-01");
        
        // Броя на дните в месеца (= на последната дата в месеца);
        $lastDay = date('t', $timestamp);
    
        for($i = 1; $i <= $lastDay; $i++) {
            $t = mktime(0, 0, 0, $month, $i, $year);
            $monthArr[date('W', $t)][date('N', $t)] = $i;
            
        }
        
        // Начална дата
        $from = "{$year}-{$month}-01 00:00:00";
        
        // Крайна дата
        $to = "{$year}-{$month}-{$lastDay} 00:00:00";
      
    	$query = self::getQuery();

    	$holiday = $nonWorking = $workday = 0;
    	
        while($rec = $query->fetch("#time >= '{$from}' AND #time <= '{$to}'")) {
            
	        if($rec->type == "holiday"){
	        	$holiday++;
	        } elseif ($rec->type == "non-working"){
	        	$nonWorking++;
	        } elseif($rec->type == "workday"){
	        	$workday++;
	        		
	        }
	    }
	  
        $satSun = 0;
               
        foreach ($monthArr as $dayWeek){
			foreach($dayWeek as $k=>$day){
		    	if($k == 6 || $k == 7){
		        	$satSun++;
		        }
		     }
        }
      
        $allHolidays = $satSun - $workday + $nonWorking + $holiday;
        $allWoking = $lastDay - $allHolidays;
           
        $statusArr = array();
        $statusArr['working'] = $allWoking;
        $statusArr['nonWorking'] = $allHolidays;
        $statusArr['holiday'] = $holiday;
        
        return $statusArr;
           
    }

    function act_Test()
    {
    	$m = 9;
    	$y = 2012;
    
    	$days = self::calculateDays($m, $y);
    	
    	expect ($days['working'] == 18 && $days['nonWorking'] == 12 && $days['holiday'] == 2, 'Greshka');
    }
    
    
    /**
     * Функция показваща събитията за даден ден
     */
    function act_Day()
    {
    	self::requireRightFor('day');
    	
    	    	
    	$data = new stdClass();
    	$data->query = $this->getQuery();
    	$data->action = 'day';
    	$this->prepareListFilter($data);
       	
    	$layout = 'cal/tpl/SingleLayoutDays.shtml';
    	$tpl = self::renderLayoutDay($layout, $data);
    	$tpl->append($this->renderListFilter($data), 'ListFilter');
    	
    	// Рендираме страницата
    	return  $this->renderWrapping($tpl);
 
    }

    
    /**
     * Показва събитията за цяла произволна седмица
     */
    function act_Week()
    {
    	self::requireRightFor('week');
    	
    	$data = new stdClass();
    	$data->query = $this->getQuery();
    	$data->action = 'week';
    	$this->prepareListFilter($data);

        $layout = 'cal/tpl/SingleLayoutWeek.shtml';
        $tpl = self::renderLayoutWeek($layout, $data);
        $tpl->append($this->renderListFilter($data), 'ListFilter');

   		
   		// Рендираме страницата
        return $this->renderWrapping($tpl);
    }


    /**
     * Показва събитията за целия месец
     */
    function act_Month()
    {
    	
    	self::requireRightFor('month');
    	
    	$data = new stdClass();
    	$data->query = $this->getQuery();
    	$data->action = 'month';
    	$this->prepareListFilter($data);
             
        $layout = 'cal/tpl/SingleLayoutMonth.shtml';
        $tpl = self::renderLayoutMonth($layout, $data);
        $tpl->append($this->renderListFilter($data), 'ListFilter');

    	// Рендираме страницата
        return $this->renderWrapping($tpl);

    }

    
    /**
     * Общ поглед върху всички събития през годината
     */
    function act_Year()
    {
    	self::requireRightFor('year');
 
    	$data = new stdClass();
    	$data->query = $this->getQuery();
    	$data->action = 'year';
    	$this->prepareListFilter($data);
             
        $layout = 'cal/tpl/SingleLayoutYear.shtml';
        $tpl = self::renderLayoutYear($layout, $data);
        $tpl->append($this->renderListFilter($data), 'ListFilter');
        
        return $this->renderWrapping($tpl);
    }
    
    /**
     * Генерираме масив с часовете на деня
     */
    static public function generateHours()
    {
    
        for($i = 0; $i < 24; $i++){
        	self::$hours[$i] = str_pad($i, 2, "0", STR_PAD_LEFT). ":00";
        }
        
        return self::$hours;
    }
    
    /**
     * Генерираме масив с дните и масив за обратна връзка
     */
	static public function generateWeek($data)
    {
    	$fromFilter = $data->listFilter->rec->from;
    	$fromFilter = explode("-", $fromFilter);
  
        for($i = 0; $i < 7; $i++){
        	$days[$i] = dt::mysql2Verbal(date("Y-m-d", mktime(0, 0, 0, $fromFilter[1], $fromFilter[2] + $i - 3, $fromFilter[0])),'l'). "<br>".
        				dt::mysql2Verbal(date("Y-m-d", mktime(0, 0, 0, $fromFilter[1], $fromFilter[2] + $i - 3, $fromFilter[0])),'d.m.Y');
        	$dates[date("Y-m-d", mktime(0, 0, 0, $fromFilter[1], $fromFilter[2] + $i - 3, $fromFilter[0]))] = "d" . $i;
        	
        	// Помощен масив за javaScripta
        	$dateJs["date".$i."Js"] = date("d.m.Y", mktime(0, 0, 0, $fromFilter[1], $fromFilter[2] + $i - 3, $fromFilter[0]));
        	$dayWeek[$i] = date("N", mktime(0, 0, 0, $fromFilter[1], $fromFilter[2] + $i - 3, $fromFilter[0]));
           	 
        	// Помощен масив за css
        	$tdCssClass["c".$i] = 'calWeekTime';
            $tdCssClass["c".$i] .= ' ' . static::getColorOfDay(date("Y-m-d 00:00:00", mktime(0, 0, 0, $fromFilter[1],  $fromFilter[2] + $i - 3, $fromFilter[0])));
        	
        }
       
        return (object) array('days'=>$days, 'dates'=> $dates, 'dateJs'=>$dateJs, 'dayWeek'=> $dayWeek, 'tdCssClass'=>$tdCssClass);
      
    }
    
    /**
     * Генерираме масив масива на месеца => номер на седмицата[ден от седмицата][ден]
     */
	static public function generateMonth($data)
    {
    	$fromFilter = $data->listFilter->rec->from;
    	$fromFilter = explode("-", $fromFilter);
    	
    	// Таймстамп на първия ден на месеца
        $firstDayTms = mktime(0, 0, 0, $fromFilter[1], 1, $fromFilter[0]);
        
        // Броя на дните в месеца
        $lastDay = date('t', $firstDayTms);
        
        // Днешната дата без часа
        $today = dt::now($full = FALSE);
        $today = explode("-", $today);

        for($i = 1; $i <= $lastDay; $i++) {
            $t = mktime(0, 0, 0, $fromFilter[1], $i, $fromFilter[0]);
            
            $isToday = ($i == $today[2] && $fromFilter[1] == $today[1] && $fromFilter[0] == $today[0]);
            
            $monthArr[date('W', $t)]["d".date('N', $t)] = $i;
            
            // Поможен масив за javaScript-а
            $dateJs[date('W', $t)]["date".date('N', $t)."Js"] = date("d.m.Y", $t);
            
            // Помощен масив за css
            $tdCssClass[date('W', $t)]["now".date('N', $t)] = $isToday ? 'mc-today' : 'mc-day';
            $tdCssClass[date('W', $t)]["now".date('N', $t)] .= ' ' . static::getColorOfDay(date("Y-m-d 00:00:00", mktime(0, 0, 0, $fromFilter[1], $i, $fromFilter[0])));

        }
       
        return (object) array('monthArr'=>$monthArr, 'dateJs'=> $dateJs, 'tdCssClass'=>$tdCssClass);
      
    }
    
    /**
     * Генерираме масива за годината
     */
    static public function generateYear()
    {
    	$fromFilter = $from = Request::get('from');
    	$fromFilter = explode(".", $fromFilter);
    	
	    for($m = 1; $m <= 12; $m++){
	    	
			// Таймстамп на първия ден на месеца
			$firstDayTms = mktime(0, 0, 0, $m, 1, $fromFilter[2]);
			
		    // Броя на дните в месеца
	    	$lastDay = date('t', $firstDayTms);
	
	    	// Днешната дата без час
	    	$today = dt::now($full = FALSE);
        	$today = explode("-", $today);
	
			
			for($i = 1; $i <= $lastDay; $i++) {
				$t = mktime(0, 0, 0, $m, $i, $fromFilter[2]);
				
				$isToday = ($i == $today[2] && $m == $today[1] && $fromFilter[2] == $today[0]);
								
				$yearArr[$m][date('W', $t)]["d".date('N', $t)] = $i;
				
				// Помощен масив за javaScript-а
				$dateJs[$m][date('W', $t)]["date".date('N', $t)."Js"] = date("d.m.Y", $t);
				
				// Помощен масив за css
				$tdCssClass[$m][date('W', $t)]["now".date('N', $t)] = $isToday ? 'mc-today' : 'mc-day';
				$tdCssClass[$m][date('W', $t)]["now".date('N', $t)] .= ' ' . static::getColorOfDay(date("Y-m-d 00:00:00", $t));
			}
		}
		
		return (object)array('yearArr'=>$yearArr, 'dateJs'=> $dateJs, 'tdCssClass'=>$tdCssClass);
    }
    
    function endTask($hour, $duration)
    {
    
	 	$taskEnd = ((strstr($hour, ":", TRUE) * 3600) + (substr(strstr($hour, ":"),1) * 60) + $duration) / 3600;
	    		
	    $taskEndH = floor($taskEnd);
	    $taskEndM =  ($taskEnd - $taskEndH) * 60;
	    
		if(substr($taskEndM,1) === FALSE){
			$taskEndM = $taskEndM . '0';
		}
	
		// Краен час: минути на събитието 
	    $taskEndHour = $taskEndH . ":" . $taskEndM;
    	
	    return $taskEndHour;
    	
    }
    
    /**
     * Намира какъв е типа на деня (празник, работен, не работен)
     * @param datetime $date - mySQL формат на дата (гггг-мм-дд чч:мм:сс)
     * Прави локален кеш на празниците. Връща масив с всички специални дни.
     */
    static public function getDateType($date)
    {
    	$year = dt::mysql2Verbal($date, 'Y');
    	
    	if(empty(self::$specialDates[$year])) {
	    	// От началото на месеца
			$fromDate = "{$year}-01-01 00:00:00";
		
			// До края на месеца
			$toDate = "{$year}-12-31 00:00:00";	 

			// проверяваме дали има записи за този ден
		   	$query = static::getQuery();
			$query->where("#time >= '{$fromDate}' AND #time <= '{$toDate}'");
		  	$query->where("#type = 'holiday' OR #type = 'workday' OR #type = 'non-working'");
		       
			while($rec = $query->fetch()) {
				//list($dates, $t) = explode(' ', $rec->time);
		    	self::$specialDates[$year][$rec->time] = $rec->type;
		    	
		 	}
		 	
		 	return self::$specialDates[$year][$date];
    	}
    	

    	if (self::$specialDates[$year][$date]) return self::$specialDates[$year][$date];
    	
    	
    }
    
    /**
     * По зададена mysql-ска дата връща цвят според типа й:
     * черен - работни дни
     * червен - официални празници
     * тъмно зелено - събота
     * свето зелено - неделя
     */
    static public function getColorOfDay($date)
    {
      	// Разбиваме подадената дата
    	$day = dt::mysql2Verbal($date, 'd');
        $month = dt::mysql2Verbal($date, 'm');
        $year = dt::mysql2Verbal($date, 'Y');
        
        // Взимаме кой ден от седмицата е 1=пон ... 7=нед
        $weekDayNo = date('N', mktime(0, 0, 0, $month, $day, $year));
    	
        $dateType = self::getDateType($date);
       
        // Ако е събота или неделя, пресвояваме цвят
    	if($weekDayNo == "6" && $dateType !== 'workday'){
    		$class = 'saturday'; // '#006030';
    	}elseif($weekDayNo == "7" && $dateType !== 'workday'){
    		$class = 'sunday'; // 'green';
    	}

    	if ($dateType == 'holiday'){
    		$class = 'holiday';
    	}elseif($dateType == 'workday' && ($weekDayNo == "6" || $weekDayNo == "7")){
    		$class = 'workday';
    	}elseif($dateType == 'non-working' && $weekDayNo >= "4"){
    		$class = 'saturday non-working';
    	}elseif($dateType == 'non-working' && $weekDayNo < "4"){
    		$class = 'sunday non-working';
    	}
   	
        return $class;
    }
    
    
    /**
     * Изчисляваме работните дни между две дати
     * @param datetime $leaveFrom
     * @param datetime $leaveTo
     * 
     * Връща масив с броя на почивните, работните дни в периода
     */
    static public function calcLeaveDays($leaveFrom, $leaveTo)
    {
    	
    	$nonWorking = $workDays = $allDays = 0;
    	
    	$curDate = "{$leaveFrom} 00:00:00";
    	
    	while($curDate < dt::addDays(1, $leaveTo)){
    		
    		$dateType = static::getDateType($curDate);
    		
    		if(!$dateType) {
    			if(dt::isHoliday($curDate)) {
    				$dateType = 'non-working';
    			} else {
    				$dateType = 'workday';
    			}
    		}
    		
    		if($dateType == 'workday') {
    			$workDays++;
    		} else {
    			$nonWorking++;
    		}
    		
    		$curDate = dt::addDays(1, $curDate); 
    		
    		$allDays++;
    	}

    	return (object) array('nonWorking'=>$nonWorking, 'workDays'=>$workDays, 'allDays'=>$allDays);
 
    }

    /**
     * Взима кой е селектирания потребител от филтъра
     */
    static public function getSelectedUsers($data)
    {        
        $selectUser = $data->listFilter->rec->selectedUsers;
       
    
    	if($selectUser == NULL){
    		$selectUser = '|' . core_Users::getCurrent() . '|';
    	}
    	
    	return $selectUser;
    }
  
    /**
     * Намира началната и крайната дата за деня.
     * Взима данни от филтъра
     */
    static function getFromToDay($data)
    {
     	
        // От началото на деня
        $from['fromDate'] = $data->listFilter->rec->from. " 00:00:00";
       
        // До края на същия ден
        $from['toDate'] = $data->listFilter->rec->from. " 23:59:59";

        
        return $from;
    }
    
    /**
     * Намира началната и крайната дата за седмицата.
     * Взима данни от филтъра
     * Избрания ден от филтъра се приема за текущ и 
     * седмицата се определя спрямо него 
     */
    static public function getFromToWeek($data)
    {
    	$fromFilter = $data->listFilter->rec->from;
    	$fromFilter = explode("-", $fromFilter);

    	// От началото на седмицата
        $from['fromDate'] = date("Y-m-d 00:00:00", mktime(0, 0, 0, $fromFilter[1], $fromFilter[2] - 3, $fromFilter[0]));
       
        // До края на седмицата
        $from['toDate'] = date("Y-m-d 23:59:59", mktime(0, 0, 0, $fromFilter[1], $fromFilter[2] + 3, $fromFilter[0]));
        
        return $from;
    }
    
    
    /**
     * Намира началната и крайната дата на месеца
     * Взима данни от филтъра
     */
	static public function getFromToMonth($data)
    {
    	$fromFilter = $data->listFilter->rec->from;
    	$fromFilter = explode("-", $fromFilter);
    	
        // Таймстамп на първия ден на месеца
        $firstDayTms = mktime(0, 0, 0, $fromFilter[1], 1, $fromFilter[0]);
        
        // Броя на дните в месеца
        $lastDay = date('t', $firstDayTms);
        
    	// От началото на седмицата
        $from['fromDate'] = date("Y-m-d 00:00:00", $firstDayTms);
       
        // До края на седмицата
        $from['toDate'] = date("Y-m-t 23:59:59", $firstDayTms);
        
        return $from;
    }
    
    
    /**
     * Намира началната и крайната дата за годината
     * Поличава данни от URL-то
     */
    static public function getFromToYear()
    {
    	$fromFilter = Request::get('from');
    	$fromFilter = explode(".", $fromFilter);

    	// Таймстамп на първия ден на месеца
		$lastDayTms = mktime(0, 0, 0, 12, 31, $fromFilter[2]);
		
		// От началото на месеца
		$from['fromDate'] = date("Y-m-d 00:00:00", mktime(0, 0, 0, 1, 1, $fromFilter[2]));
		
		// До края на месеца
		$from['toDate'] = date('Y-m-t 23:59:59', $lastDayTms);
       
    	return $from;
    }
    
    /**
     * Взима данните от филтъра
     * Датата и селектирания потребител
     */
    static public function getFromFilter($data){
    	    	
    	$state['from'] = $data->listFilter->rec->from;
    	$state['selectedUsers'] = self::getSelectedUsers($data);
    	
    	return $state;
    }
    
    /**
     * Намира каква е иконата според състоянието на задачата
     */
    static public function getIconByType($type, $key)
    {
    	 // Картинката за задачите
     
		if($type == 'task'){
			$idTask = str_replace("TSK-", " ", $key);
			$idTask = str_replace("-Start", " ", $idTask);
			$idTask = str_replace("-End", " ", $idTask);
			$getTask = cls::get('cal_Tasks');
			$imgTask = $getTask->getIcon(trim($idTask));
			$img = "<img class='calImg' src=". sbf($imgTask) .">&nbsp;";
		
		}elseif($type == 'end-date'){
			$img = "<img class='calImg'  src=". sbf('img/16/end-date.png') .">&nbsp;";
		
		}elseif($type == 'leave'){
			$img = "<img class='calImg'  src=". sbf('img/16/beach.png') .">&nbsp;";
		} elseif($type == 'sickday'){
			$img = "<img class='calImg'  src=". sbf('img/16/sick.png') .">&nbsp;";
		}
			
		return $img;
    }
   
    /**
     * Генерира заявката към базата данни
     */
    static function prepareState($fromDate, $toDate, $selectedUsers)
    {
    	
    	// Извличане на събитията за целия месец
		$state = new stdClass();
		$state->query = self::getQuery();
      
		// Кой ни е текущия потребител? 
		// Показване на календара и събитията според потребителя
		$state->query->where("#time >= '{$fromDate}' AND #time <= '{$toDate}'");
		$state->query->LikeKeylist('users', $selectedUsers);
        $state->query->orWhere('#users IS NULL OR #users = ""');
        
        $state->query->orderBy('time', 'ASC');  
		
		while($rec = $state->query->fetch()){
			$recState[] = $rec;
		}
 		
		return $recState;
    }
    
    /**
     * Генерира заявката към базата данни за екшън Година
     */
	static function prepareStateYear($fromDate, $toDate, $selectedUsers, $type)
    {
    	
    	// Извличане на събитията за целия месец
		$state = new stdClass();
		$state->query = self::getQuery();
      
		// Кой ни е текущия потребител? 
		// Показване на календара и събитията според потребителя
		$state->query->where("#time >= '{$fromDate}' AND #time <= '{$toDate}' AND #type = '{$type}'");
		$state->query->LikeKeylist('users', $selectedUsers);
        $state->query->orWhere('#users IS NULL OR #users = ""');
        
        $state->query->orderBy('time', 'ASC');  
		
		while($rec = $state->query->fetch()){
			$recState[] = $rec;
		}
 		
		return $recState;
    }
    
    /**
     * Подготвя записите от базата данни за екшън Ден
     */
    static public function prepareRecDay($data)
    {
        $date = self::getFromFilter($data);
    	$selectedUsers = self::getSelectedUsers($data);
    	$from = self::getFromToDay($data);

     	// Масив с информация за деня
        $dates[dt::mysql2verbal($date['from'], 'Y-m-d')] = "tasktitle";

     	// От началото на деня
        $fromDate = $from['fromDate'];
       
        // До края на същия ден
        $toDate = $from['toDate'];
        
        $stateDay = self::prepareState($fromDate, $toDate, $selectedUsers);
        
        if(is_array($stateDay)){
	        foreach($stateDay as $rec){
			    // Деня, за който взимаме събитията
			    $dayKey = $dates[dt::mysql2verbal($rec->time, 'Y-m-d')];
			     
			    // Начален час на събитието
			    $hourKey = dt::mysql2verbal($rec->time, 'G');
			
			    // Ако събитието е отбелязано да е активно през целия ден
			    if($rec->allDay == "yes")  $hourKey = "allDay";
			    
			    if($hourKey <= self::$tr && $hourKey != "allDay") self::$tr = $hourKey;
			    
			    if($hourKey >= self::$tk && $hourKey != "allDay") self::$tk = $hourKey;
			    
			    // Линк към събитието
	     		$url = getRetUrl($rec->url);
	               
	     		// Ид-то на събитието
	    		$id = substr(strrchr($rec->url, "/"),1);
	    		
	     	    // Картинката за задачите
	     		$img = self::getIconByType($rec->type, $rec->key);
				
	     		if($hourKey == "allDay" ){
	     			if($rec->type == 'leave' || $rec->type == 'sickday' || $rec->type == 'task') {
	     				$dayData[$hourKey][$dayKey] .= "<div class='task'>".$img.ht::createLink("<p class='state-{$rec->state}'>" . str::limitLen($rec->title, 35) . "</p>", $url, NULL, array('title' => $rec->title))."</div>";
	     			} else {
	     				$dayData[$hourKey][$dayKey] .= ht::createLink("<p class='calWeek'>" . $rec->title . "</p>", $url, NULL, array('title' => $rec->title));
	     			}
	     		}
	    		
	     		if($hourKey != "allDay" && dt::mysql2verbal($rec->time, 'i') == "00")$dayData[$hourKey][$dayKey] .= "<div class='task'>".$img.ht::createLink("<p class='state-{$rec->state}'>" . str::limitLen($rec->title, 35) . "</p>", $url, NULL, array('title' => $rec->title))."</div>";
	    		
	    		if(dt::mysql2verbal($rec->time, 'i') != "00") $dayData[$hourKey][$dayKey] .= "<div class='task'>".$img.ht::createLink("<p class='state-{$rec->state}'>" . dt::mysql2verbal($rec->time, 'H:i'). "&nbsp;" . str::limitLen($rec->title, 35) . "</p>", $url, NULL, array('title' => $rec->title))."</div>";
	
	     	}
        }
     	
     	return $dayData;
    }
    
    /**
     * Подготвя записите от базата данни за екшън Седмица
     */
    static public function prepareRecWeek($data)
    {
    	$date = self::getFromFilter($data);
    	$selectedUsers = self::getSelectedUsers($data);
    	$from = self::getFromToWeek($data);
    	$weekArr = self::generateWeek($data);
        	
     	// От началото на деня
        $fromDate = $from['fromDate'];
       
        // До края на същия ден
        $toDate = $from['toDate'];
        
        $stateWeek = self::prepareState($fromDate, $toDate, $selectedUsers);
        
        if(is_array($stateWeek)){
	        foreach($stateWeek as $rec){
	        	
	        	// Деня, за който взимаме събитията
			    $dayKey = $weekArr->dates[dt::mysql2verbal($rec->time, 'Y-m-d')];
			     
			    // Начален час на събитието
			    $hourKey = dt::mysql2verbal($rec->time, 'G');
			
			    // Ако събитието е отбелязано да е активно през целия ден
			    if($rec->allDay == "yes")  $hourKey = "allDay";
			    
			    if($hourKey <= self::$tr && $hourKey != "allDay") self::$tr = $hourKey;
			    
			    if($hourKey >= self::$tk && $hourKey != "allDay") self::$tk = $hourKey;
			    
			    // Линк към събитието
	     		$url = getRetUrl($rec->url);
	               
	     		// Ид-то на събитието
	    		$id = substr(strrchr($rec->url, "/"),1);
	    		
	     	    // Картинката за задачите
	            $img = self::getIconByType($rec->type, $rec->key);
	            
	            if($hourKey == "allDay"){
	            	if($rec->type == 'leave' || $rec->type == 'sickday' || $rec->type == 'task'){
	            		$weekData[$hourKey][$dayKey] .= "<div class='task'>".$img.ht::createLink("<p class='state-{$rec->state}'>" . str::limitLen($rec->title, 20) . "</p>", $url, NULL, array('title' => $rec->title))."</div>";
	            	} else {
	            		$weekData[$hourKey][$dayKey] .= ht::createLink("<p class='calWeek'>" . $rec->title . "</p>", $url, NULL, array('title' => $rec->title));
	            	}
	            } 
	            
	            if($hourKey != "allDay" && dt::mysql2verbal($rec->time, 'i') == "00") $weekData[$hourKey][$dayKey] .= "<div class='task'>".$img.ht::createLink("<p class='state-{$rec->state}'>" . str::limitLen($rec->title, 20) . "</p>", $url, NULL, array('title' => $rec->title)) .'</div>';
	    		
	    		if(dt::mysql2verbal($rec->time, 'i') != "00") $weekData[$hourKey][$dayKey] .= "<div class='task'>" . $img.ht::createLink("<p class='state-{$rec->state}'>" . dt::mysql2verbal($rec->time, 'H:i'). "&nbsp;" . str::limitLen($rec->title, 15) . "</p>", $url, NULL, array('title' => $rec->title)). '</div>';
	        }
        }
        
        return $weekData;
    }
    
    
    /**
     * Подготвя записите от базата данни за екшън Месец
     */
    static public function prepareRecMonth($data)
    {
    	$date = self::getFromFilter($data);
    	$selectedUsers = self::getSelectedUsers($data);
    	$from = self::getFromToMonth($data);
    	$monthDate = self::generateMonth($data);
        	
     	// От началото на деня
        $fromDate = $from['fromDate'];
       
        // До края на същия ден
        $toDate = $from['toDate'];
        
        $stateMonth = self::prepareState($fromDate, $toDate, $selectedUsers);
      
        if(is_array($stateMonth)){
	        foreach($stateMonth as $rec){
	       
			     
			    // Начален час на събитието
			    $hourKey = dt::mysql2verbal($rec->time, 'G');
			    
			    // Разбиваме това време на: ден, месец и година
	            $recDay = dt::mysql2Verbal($rec->time, 'j');
				$recMonth = dt::mysql2Verbal($rec->time, 'm');
				$recYear = dt::mysql2Verbal($rec->time, 'Y');
				
				// Таймстамп на всеки запис
				$recT = mktime(0, 0, 0, $recMonth, $recDay, $recYear);
				
				// В коя седмица е този ден
				$weekKey = date('W', $recT);
				
			 	// Деня, за който взимаме събитията
			    $dayKey = "d".date('N', $recT);
			    
			    // Ако събитието е отбелязано да е активно през целия ден
			    if($rec->allDay == "yes")  $hourKey = "allDay";
			    
			    if($hourKey <= self::$tr && $hourKey != "allDay") self::$tr = $hourKey;
			    
			    if($hourKey >= self::$tk && $hourKey != "allDay") self::$tk = $hourKey;
			    
			    // Линк към събитието
	     		$url = getRetUrl($rec->url);
	               
	     		// Ид-то на събитието
	    		$id = substr(strrchr($rec->url, "/"),1);
	    		
	     	    // Картинката за задачите
	            $img = self::getIconByType($rec->type, $rec->key);
	            
	        	if($hourKey == "allDay" ){
	     			if($rec->type == 'leave' || $rec->type == 'sickday' || $rec->type == 'task') {
	     				$monthDate->monthArr[$weekKey][$dayKey] .= "<div class='task'>".$img.ht::createLink("<p class='state-{$rec->state}'>" . str::limitLen($rec->title, 17) . "</p>", $url, NULL, array('title' => $rec->title))."</div>";
	     			} else {
	     				$monthDate->monthArr[$weekKey][$dayKey] .= ht::createLink("<p class='calWeek'>" . $rec->title . "</p>", $url, NULL, array('title' => $rec->title));
	     			}
	     		}
	     		
	            if($hourKey != "allDay" && dt::mysql2verbal($rec->time, 'i') == "00") $monthDate->monthArr[$weekKey][$dayKey] .="<div class='task'>" .$img.ht::createLink("<p class='state-{$rec->state}'>" . str::limitLen($rec->title, 20) . "</p>", $url, NULL, array('title' => $rec->title)). '</div>';
	    		
	    		if(dt::mysql2verbal($rec->time, 'i') != "00") $monthDate->monthArr[$weekKey][$dayKey] .="<div class='task'>" . $img.ht::createLink("<p class='state-{$rec->state}'>" . dt::mysql2verbal($rec->time, 'H:i'). "&nbsp;" . str::limitLen($rec->title, 12) . "</p>", $url, NULL, array('title' => $rec->title)).'</div>';
	        }
        }
       
        return $monthDate;
    }
    
    /**
     * Подготвя записите от базата данни за екшън Година
     */
    static public function prepareRecYear($data)
    {
    	$from = self::getFromToYear();
    	$yearDate = self::generateYear();
    	$date = self::getFromFilter($data);
    	$selectedUsers = self::getSelectedUsers($data);

     	// От началото на деня
        $fromDate = $from['fromDate'];
       
        // До края на същия ден
        $toDate = $from['toDate'];
        
        // TODO всеки ден от отпуската
        $stateYearLeave = self::prepareStateYear($fromDate, $toDate, $selectedUsers, $type = 'leave');
    
        if(is_array($stateYearLeave)){
	        foreach($stateYearLeave as $rec){
	        	
				// Разбиваме това време на: ден, месец и година
				$recDay = dt::mysql2Verbal($rec->time, 'j');
				$recMonth = dt::mysql2Verbal($rec->time, 'n');
				$recYear = dt::mysql2Verbal($rec->time, 'Y');
				
				// Таймстамп на всеки запис
				$recT = mktime(0, 0, 0, $recMonth, $recDay, $recYear);
						
				// В коя седмица е този ден
				$weekKey = date('W', $recT);
				
				// Кой ден от седмицата е
				$dayKey = "d".date('N', $recT);
				
				// Добавяме звезда там където имаме събитие
				$yearDate->yearArr[$recMonth][$weekKey][$dayKey] = "<img class='starImg' src=". sbf('img/16/star_3.png') .">" . $recDay;
	        }
        }
        
        $stateYear = self::prepareStateYear($fromDate, $toDate, $selectedUsers, $type = 'task');
    
        if(is_array($stateYear)){
	        foreach($stateYear as $rec){
	        	
				// Разбиваме това време на: ден, месец и година
				$recDay = dt::mysql2Verbal($rec->time, 'j');
				$recMonth = dt::mysql2Verbal($rec->time, 'n');
				$recYear = dt::mysql2Verbal($rec->time, 'Y');
				
				// Таймстамп на всеки запис
				$recT = mktime(0, 0, 0, $recMonth, $recDay, $recYear);
						
				// В коя седмица е този ден
				$weekKey = date('W', $recT);
				
				// Кой ден от седмицата е
				$dayKey = "d".date('N', $recT);
				
				// Добавяме звезда там където имаме събитие
				$yearDate->yearArr[$recMonth][$weekKey][$dayKey] = "<img class='starImg' src=". sbf('img/16/star_2.png') .">" . $recDay;
	        }
        }

        return $yearDate;
    }
    
    /**
     * Създава линкове за предишен и следващ месец
     */
    static function prepareMonhtHeader($data)
    {
    	
    	$date = $data->listFilter->rec->from;
    	$date = explode("-", $date);
	  
        // Разбиваме я на ден, месец и година
        $day = $date[2];
        $month = $date[1];
        $year = $date[0];
    	
        if(!$month || $month < 1 || $month > 12 || !$year || $year < 1970 || $year > 2038) {
            $year = date('Y');
            $month = date('n');
        }

        // Добавяне на първия хедър
        $currentMonth = tr(dt::$months[$month-1]) . " " . $year;

        $pm = $month-1;
        if($pm == 0) {
            $pm = 12;
            $py = $year-1;
        } else {
            $py = $year;
        }
        $prevMonth = tr(dt::$months[$pm-1]) . " " .$py;

        $nm = $month+1;
        if($nm == 13) {
            $nm = 1;
            $ny = $year+1;
        } else {
            $ny = $year;
        }
        $nextMonth = tr(dt::$months[$nm-1]) . " " .$ny;
        
        $link = $_SERVER['REQUEST_URI'];
      
        $headerLink['nextLink'] = Url::addParams($link, array('from' => $day . '.' . $nm . '.' . $ny));
        $headerLink['prevtLink'] = Url::addParams($link, array('from' => $day . '.' . $pm . '.' . $py));
        $headerLink['currentMonth'] = $currentMonth;
        $headerLink['nextMonth'] = $nextMonth;
        $headerLink['prevMonth'] = $prevMonth;
    	
        return $headerLink;
    }
    
    /**
     * Изчислява номера(/номерата, 
     * ако избраната седмица в екшън Седмица обхваща дни
     * от две седмици) на седмицата
     */
    static public function prepareWeekNumber($data)
    {
    	$fromFilter = $data->listFilter->rec->from;
    	$fromFilter = explode("-", $fromFilter);
    	 
        // Номера на седмицата
        $weekNbFrom = date('W', mktime(0, 0, 0, $fromFilter[1], $fromFilter[2] - 3, $fromFilter[0]));
        $weekNbTo = date('W', mktime(0, 0, 0, $fromFilter[1], $fromFilter[2] + 3, $fromFilter[0]));
        
	    if($weekNbFrom == $weekNbTo){
	        	
	    	$weekNb = $weekNbFrom;
	    } else {
	        	
	        $weekNb = $weekNbFrom . "/" . $weekNbTo;
	    }
	    
	    return $weekNb;
    }
    
    
    /**
     * Замествания по шаблона на екшън Ден
     */
    static public function renderLayoutDay($layout, $data)
    {
    	$dayData = self::prepareRecDay($data);
    	$isToday = self::isToday($data);
    	$dayHours = self::generateHours();
    
    	// Текущото време на потребителя
     	$nowTime = strstr(dt::now(), " ");
    	
    	// Рендираме деня
    	$tpl = new ET(tr('|*' . getFileContent($layout)));
        
    	$url = toUrl(array('cal_Tasks', 'add'));
    	
    	$jsFnc = "
    	function createTask(dt)
    	{
    		document.location = '{$url}?timeStart[d]=' + dt;
		}";
    	    	
    	$jsDblFnc = "
    	function createDblTask(dt)
    	{
    		document.location = '{$url}?timeStart[d]=' + dt;
		}";

    	
    	$tpl->appendOnce($jsFnc, 'SCRIPTS');
    	$tpl->appendOnce($jsDblFnc, 'SCRIPTS');

    	foreach(self::$hours as $h => $t){
    		if($h === 'allDay' || ($h >= self::$tr && $h <= self::$tk)){
    			
	    		$hourArr = $dayData[$h];
	    		$hourArr['time'] = $t;
	    		$hourArr['timeJs'] = $h;
	    		$hourArr['dateJs'] = $data->listFilter->rec->from;
	 
	    		
	    		// Определяме класа на клетката, за да стане на зебра
	    		if($h % 2 == 0 && $h !== 'allDay' && ($h != $nowTime || $h != $isToday)){
	    			$classTd = 'calDayN';
	    			$classTr = 'calDayC';	    
			    }elseif($h % 2 == 0 && $h !== 'allDay' && $isToday == FALSE && $h != $nowTime){
			    	$classTd = 'calDayN';
			    	$classTr = 'calDayC';
			    }elseif($h == $nowTime && $isToday && $h % 2 == 0){
			     	$classTd = 'mc-todayN';
			     	$classTr = 'calDayC';
			    }elseif($h == $nowTime && $isToday && $h % 2 != 0 && $h != 0){
				    $classTd = 'mc-todayD';
				    $classTr = 'calDayD';
			    }else{
			    	$classTd = 'calDay';
			    	$classTr = 'calDayD';
			    }
	    		
			    // Взимаме блока от шаблона
	    		$cTpl = $tpl->getBlock("COMMENT_LI");
	    		$cTpl->replace($classTr, 'colTr');
	    		$cTpl->replace($classTd, 'now');
	    		// За да сработи javaSkript–а за всяка картинак "+", която ще показваме
			    // задаваме уникално ид
			    for($j = 0; $j < 26; $j++){
					    
					    
					// Линкове на картинката
					$aHrefs["href".$j] = "<img class='calWeekAdd' id=$h$j src=".sbf('img/16/add1-16.png').">";
					
					// javaScript функциите
					$overs["over".$j] = "onmouseover='ViewImage($h$j)'";
					$outs["out".$j] = "onmouseout='NoneImage($h$j)'";
			     } 
			         
			      
			    // Заместваме всички масиви
			
			    $cTpl->placeArray($aHrefs);
			    $cTpl->placeArray($overs);
			    $cTpl->placeArray($outs);
			  
	    		
	   
	    		$cTpl->placeArray($hourArr);
	    		
	    		//Връщаме към мастера
	    		$cTpl->append2master();
    		}
   		}

   		
  
   		$currentDate = self::getFromFilter($data);
   	    $currentDateDay = dt::mysql2Verbal($currentDate['from'], 'd F Y, l');
    
        // Заместваме титлата на страницата
    	$tpl->replace($currentDateDay, 'title');

    	$titleColor = static::getColorOfDay($currentDate['from']. " 00:00:00");
    	$tpl->replace($titleColor, 'colTitle');
    	
    	return $tpl;
    }
    
    
    /**
     * Замествания по шаблона на екшън Седмица
     */
    static public function renderLayoutWeek($layout, $data)
    {
    	$weekData = self::prepareRecWeek($data);
    	
    	$weekArr = self::generateWeek($data);
   
    	$isToday = self::isToday($data);
    	
    	$weekHours = self::generateHours();
  
    	// Текущото време на потребителя
     	$nowTime = strstr(dt::now(), " ");
    	
    	// Рендиране на седмицата	
        $tpl = new ET(tr('|*' . getFileContent($layout)));
        
        $urlWeek = toUrl(array('cal_Tasks', 'add'));
    	
    	$jsFnc = "
    	function createWeekTask(dt)
    	{
    		document.location = '{$urlWeek}?timeStart[d]=' + dt;
		}";
    	
    	$jsDblFnc = "
    	function createDblWeekTask(dt)
    	{
    		document.location = '{$urlWeek}?timeStart[d]=' + dt;
		}";
    	
    	$tpl->appendOnce($jsFnc, 'SCRIPTS');
    	$tpl->appendOnce($jsDblFnc, 'SCRIPTS');
 
    
    	
   		foreach(self::$hours as $h => $t){
   		
   			// Ограничаваме часовета в таблицата до цел ден и най-малкия и най-големия час
   			if($h === 'allDay' || ($h >= self::$tr && $h <= self::$tk)){
    		$hourArr = $weekData[$h];
    		$hourArr['time'] = $t;
    		$hourArr['timeJs'] = $h;

    		// Взимаме блока от шаблона
    		$cTpl = $tpl->getBlock("COMMENT_LI");
   			
   			// Определяме класа на клетката, за да стане на зебра
    		if($h % 2 == 0 && $h !== 'allDay' && ($h != $nowTime || $h != $isToday)){
    			$classTd = 'calWeekN';
    			$classTr = 'calDayC';
			    $classToday = 'calWeekN';		    
		    }elseif($h == $nowTime && $isToday && $h % 2 == 0){
		    	$classTd = 'calWeekN';
		     	$classToday = 'mc-todayN';
		     	$classTr = 'calDayC';
		     	
		    }elseif($h == $nowTime && $isToday && $h % 2 != 0 && $h != 0){
			    $classToday = 'mc-todayD';
			    $classTd = 'calWeek';
			    $classTr = 'calDayD';
			    
		    }else{
		    	$classTd = 'calWeek';
		    	$classTr = 'calDayD';
		    	$classToday = 'calWeek';
		    }
    		    		
    		$cTpl->replace($classTr, 'colTr');
    		$cTpl->replace($classToday, 'now');
    		$cTpl->replace($classTd, 'col');
    		
    	
    		$cTpl->placeArray($hourArr);
    		
		     // За да сработи javaSkript–а за всяка картинак "+", която ще показваме
		     // задаваме уникално ид
		    for($j = 0; $j < 26; $j++){
		
				// Линкове на картинката
				$aHrefs["href".$j] = "<img class='calWeekAdd' id=$h$j src=".sbf('img/16/add1-16.png').">";
				
				// javaScript функциите
				$overs["over".$j] = "onmouseover='ViewImage($h$j)'";
				$outs["out".$j] = "onmouseout='NoneImage($h$j)'";
		     }

             // Заместваме всички масиви в шаблона
		     $cTpl->placeArray($aHrefs);
		     $cTpl->placeArray($overs);
		     $cTpl->placeArray($outs);
		     $cTpl->placeArray($hourArr);
     
    		   			
            // Връщаме се към мастера
    		$cTpl->append2master();
   			}
   		}

    	
   		$weekNb = self::prepareWeekNumber($data);
    	
        // Заглавие на страницата
    	$tpl->replace(tr('Събития за седмица') . ' » ' . $weekNb, 'title');
    	
    	// Рендираме масивите с дните и javaScript масива
    	$tpl->placeArray($weekArr->days);
    	$tpl->placeArray($weekArr->dateJs);
    	$tpl->placeArray($weekArr->tdCssClass);
    	
    	return $tpl;
    }
    
    
    /**
     * Замествания по шаблона на екшън Месец
     */
    static public function renderLayoutMonth($layout, $data)
    {
    	$monthData = self::prepareRecMonth($data);
    	$monthArr = self::generateMonth($data);
    	
    	// Зареждаме шаблона
        $tpl = new ET(tr('|*' . getFileContent($layout)));
        
        $urlMonth = toUrl(array('cal_Calendar', 'week'));
    	
    	$jsFnc = "
    	function createMonthLink(dt)
    	{
    		document.location = '{$urlMonth}?from=' + dt;
		}";
    	
    	$tpl->appendOnce($jsFnc, 'SCRIPTS');

    	
        foreach($monthData->monthArr as $weekNum => $weekArr) {
        	
        	$cTpl = $tpl->getBlock("COMMENT_LI");
        	
        	$cTpl->placeArray($monthArr->colorTitle[$weekNum]);
        	$cTpl->placeArray($monthArr->tdCssClass[$weekNum]);
        	$cTpl->placeArray($monthArr->dateJs[$weekNum]);

        	$cTpl->replace($weekNum, 'weekNum');
        	$cTpl->placeArray($weekArr);
        	
            $cTpl->append2master();
         }
        
        $tpl->placeArray(static::$weekDays);
        
        $link = static::prepareMonhtHeader($data);

        // Добавяне на първия хедър
        $tpl->replace($link['prevtLink'], 'prevtLink');
        $tpl->replace($link['prevMonth'], 'prevMonth');
        $tpl->replace($link['currentMonth'], 'currentMonth');
        $tpl->replace($link['nextLink'], 'nextLink');
        $tpl->replace($link['nextMonth'], 'nextMonth');
        
        // Заглавието на страницата
    	$tpl->replace(tr('Събития за месец') . ' » '. $link['currentMonth'], 'title');
    	
    	return $tpl;
    }
    
    
    /**
     * Замествания по шаблона на екшън Година
     */
    static public function renderLayoutYear($layout, $data)
    {
    	
    	$yearData = self::prepareRecYear($data);
    	$yearArr = self::generateYear($data);
    	
    	$fromFilter = $from = Request::get('from');
    	$fromFilter = explode(".", $fromFilter);
    	
	    // Зареждаме шаблона
        $tpl = new ET(tr('|*' . getFileContent($layout)));
        
        $urlYear = toUrl(array('cal_Calendar', 'week'));
    	
    	$jsFnc = "
    	function createLink(dt)
    	{
    		document.location = '{$urlYear}?from=' + dt;
		}";
    	
    	$tpl->appendOnce($jsFnc, 'SCRIPTS');
       
    	foreach($yearData->yearArr as $monthNum => $monthArr) {
    	
    		foreach($monthArr as $weekNum => $weekArr){
    			
				$tpl->replace(dt::getMonth($monthNum, 'F'), 'month'.$monthNum);
				$block = "COMMENT_LI{$monthNum}";
				
				$lTpl = $tpl->getBlock("COMMENT_LI{$monthNum}");
							      
				$lTpl->replace($weekNum, 'weekNum');
				$lTpl->placeArray($weekArr);
				$lTpl->placeArray($yearArr->dateJs[$monthNum][$weekNum]);
				$lTpl->placeArray($yearArr->tdCssClass[$monthNum][$weekNum]);
				$lTpl->append2master();
						         
    		}
         }

        // Заглавието на страницата
    	$tpl->replace(tr('Събития за година') . ' » '. $fromFilter[2], 'title');

    	// Имената на дните от седмицата
        $tpl->placeArray(dt::$weekDays);
        
        return $tpl;
    }
    
    /**
     * Проверява дали избраната дата от филтъра е днешния ден
     */
    static public function isToday($data)
    {
    	$from = self::getFromFilter($data);
    
       	$fromA = $from['from'];
        $fromA = explode("-", $fromA);
        
        $today = dt::now($full = FALSE);
        $today = explode("-", $today);
        
    	$isToday = ($fromA[2]== $today[2] && $fromA[1] == $today[1] && $fromA[0] == $today[0]);
    	
    	return $isToday;
    }

}