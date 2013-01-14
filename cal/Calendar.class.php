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
    var $loadList = 'plg_Created, plg_RowTools, cal_Wrapper, plg_Sorting, plg_State, bgerp_plg_GroupByDate, cal_View, plg_Printing';
    

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
    
    
    
    /**
     * Кой може да пише
     */
    var $canWrite = 'no_one';
    
    
    /**
     * Кой може да чете
     */
    var $canRead = 'user';
    
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

        // Приоритет 1=Нисък, 2=Нормале, 3=Висок, 4=Критичен, 0=Никакъв (приключена задача)
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
        
        if($from = $data->listFilter->rec->from) {
        	
            $data->query->where("#time >= date('$from')");
        
        }
        
        if($data->listFilter->rec->selectedUsers) {
            if($data->listFilter->rec->selectedUsers != 'all_users') {
                $data->query->likeKeylist('users', $data->listFilter->rec->selectedUsers);
                $data->query->orWhere('#users IS NULL');
            }
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
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('from', 'date', 'caption=От,input,silent, width = 150px');
        $data->listFilter->FNC('selectedUsers', 'users', 'caption=Потребител,input,silent', array('attr' => array('onchange' => 'this.form.submit();')));
        $data->listFilter->setdefault('from', date('Y-m-d'));
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'from, selectedUsers';
        
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
        $attr['style'] = 'background-image:url(' . sbf("img/16/{$lowerType}.png") . ');';
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
        $Calendar->prepareListRecs($state); //bp($state->recs);
        $Calendar->prepareListRows($state);
        
        // Подготвяме лентата с инструменти
        $Calendar->prepareListToolbar($state);

        if (is_array($state->recs)) {
            foreach($state->recs as $id => $rec) {
                if($rec->type == 'holiday' || $rec->type == 'non-working' || $rec->type == 'workday') {
                    $time = dt::mysql2timestamp($rec->time);
                    $i = (int) date('j', $time);
                    if(!isset($data[$i])) {
                        $data[$i] = new stdClass();
                    }
                    $data[$i]->type = $rec->type;
                } elseif($rec->type == 'workday') {
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
        $Calendar->prepareListRecs($state); 
        $Calendar->prepareListRows($state);

        $tpl->replace($Calendar->renderListTable($state), 'AGENDA');

        return $tpl;
    }

    
    /**
     * Функция извеждаща броя на работните, неработните и празничните дни в един месец
     */
    function calculateDays($month, $year)
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

        $monthEvent = array();
      
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
    	
    	// Очакваме дата от филтъра
    	$from = Request::get('from');
    	
    	// Разбиваме я на ден, месец и година
        $day = dt::mysql2Verbal($from, 'd');
        $month = dt::mysql2Verbal($from, 'm');
        $year = dt::mysql2Verbal($from, 'Y');
      
        // Избрана дата
        $currentDate = dt::mysql2Verbal($from, 'd F Y, l');
        $currentKey = date("Y-m-d 00:00:00", mktime(0, 0, 0, $month, $day, $year));
    	
    	// Текущото време на потребителя
    	$nowTime = strstr(dt::now(), " ");
    	
        $hours = array( "allDay" => "Цял ден");
        
        // Генерираме масив с часовете
        for($i = 0; $i < 24; $i++){
        	$hours[$i] = str_pad($i, 2, "0", STR_PAD_LEFT). ":00";
        }
        
        // Масив с информация за деня
        $dates[dt::mysql2verbal($from, 'Y-m-d')] = "tasktitle";
           	       
        // От началото на деня
       	$fromDate = dt::verbal2mysql($from);
       	
       	// До края на същия ден
       	$toDate = str_replace("00:00:00", "23:59:59",dt::verbal2mysql ($from));
       	
       	// Правим заявка към базата
       	$state = new stdClass();
        $state->query = self::getQuery();
        
        // Кой е текущия потребите?
        // Показваме неговия календар
        $cu = core_Users::getCurrent();
        $state->query->where("#users IS NULL OR #users = ''");
        $state->query->orLikeKeylist('users', "|$cu|");
        
        // Извличане на събитията за целия ден
    	while ($rec =  $state->query->fetch("#time >= '{$fromDate}' AND #time <= '{$toDate}'")){

    		// Проверка за конкретния запис
    	    self::requireRightFor('day', $rec);
    	    
    	    // Деня, за който взимаме събитията
    		$dayKey = $dates[dt::mysql2verbal($rec->time, 'Y-m-d')];
     		
    		// Начален час на събитието 
    		$hourKey = dt::mysql2verbal($rec->time, 'G');
		  
    		$type[$rec->type] = $from;
    		// Ако събитието е отбелязано да е активно през целия ден
    		if($rec->allDay == "yes"){
    			$hourKey = "allDay";
    		}
    		
            // Помощен масив за определяне на най-ранното и най-късното събитие
    		$minMax[] = $hourKey;
    		
    		// Линк към събитието
    		$url = getRetUrl($rec->url);
    		// Ид-то на събитието
    		$id = substr(strrchr($rec->url, "/"),1);
    		
    	    // Картинката за задачите
    		$img = "<img class='calImg' src=". sbf('img/16/task.png') .">&nbsp;";
    		
    		// Взимаме всеки път различен цвят за титлите на задачите
    		$color = array_pop(self::$colors);
    		
    		if(dt::mysql2verbal($rec->time, 'i') != "00"){
    			switch ($rec->state){
    				case "active":
    					$dayData[$hourKey][$dayKey] .= $img.ht::createLink(dt::mysql2verbal($rec->time, 'H:i'). "&nbsp;" . str::limitLen($rec->title, 10), $url, NULL, array('class'=>'calWeek', 'style' => 'color:'. $color, 'title' => $rec->title))."</br>";
    					break;
    				case "draft":
    					$dayData[$hourKey][$dayKey] .= $img.ht::createLink(dt::mysql2verbal($rec->time, 'H:i'). "&nbsp;" . str::limitLen($rec->title, 10), $url, NULL, array('class'=>'draftColor', 'style' => 'color:'. $color, 'title' => $rec->title));
    					break;
    				case "closed":
    					$dayData[$hourKey][$dayKey] .= $img.ht::createLink(dt::mysql2verbal($rec->time, 'H:i'). "&nbsp;" . str::limitLen($rec->title, 17), $url, NULL, array('class'=>'closedColor', 'style' => 'color:'. $color, 'title' => $rec->title));
    					break;
    			}
    		} elseif($hourKey != "allDay"){
    			switch ($rec->state){
    				case "active":
    					$dayData[$hourKey][$dayKey] .= $img.ht::createLink(str::limitLen($rec->title, 17), $url, NULL, array('class'=>'calWeek' , 'style' => 'color:'. $color, 'title' => $rec->title));
    					break;
    				case "draft":
    					$dayData[$hourKey][$dayKey] .= $img.ht::createLink(str::limitLen($rec->title, 17), $url, NULL, array('class'=>'draftColor' , 'style' => 'color:'. $color, 'title' => $rec->title));
    					break;
    				case "closed":
    					$dayData[$hourKey][$dayKey] .= $img.ht::createLink(str::limitLen($rec->title, 17), $url, NULL, array('class'=>'closedColor', 'style' => 'color:'. $color, 'title' => $rec->title));
    					break;
    			}
    			
    		 } elseif($hourKey == "allDay" && $rec->type == "task"){
    			switch ($rec->state){
    				case "active":
    					$dayData[$hourKey][$dayKey] .= $img.ht::createLink(str::limitLen($rec->title, 17), $url, NULL, array('class'=>'calWeek' , 'style' => 'color:'. $color, 'title' => $rec->title));
    					break;
    				case "draft":
    					$dayData[$hourKey][$dayKey] .= $img.ht::createLink(str::limitLen($rec->title, 17), $url, NULL, array('class'=>'draftColor' , 'style' => 'color:'. $color, 'title' => $rec->title));
    					break;
    				case "closed":
    					$dayData[$hourKey][$dayKey] .= $img.ht::createLink(str::limitLen($rec->title, 17), $url, NULL, array('class'=>'closedColor', 'style' => 'color:'. $color, 'title' => $rec->title));
    					break;
    				
    					
    			}
    		   } else {
    				$dayData[$hourKey][$dayKey] .= ht::createLink("<p class='calWeek'>" . str::limitLen($rec->title, 40) . "</p>", $url, NULL, array('title' => $rec->title));
     		     }
    		
    	}
    	
    	if(count($minMax) > 1){	
    		if($minMax[0] == 'allDay'){
    			unset($minMax[0]);
    		}
    
    		if(count($minMax) > 1){
	    		// Определяме началото и края на деня спрямо началните стойнности
		        if(min($minMax) < self::$tr && min($minMax) !== 'allDay'){
		        	self::$tr = min($minMax);
		        	if(max($minMax) > self::$tk ){
		        		self::$tk = max($minMax);
		        	}
		        	
		        } elseif(min($minMax) > self::$tk){
		        	self::$tr = 8;
		        	self::$tk = min($minMax);
		        } elseif(max($minMax) > self::$tk ){
		        	self::$tk = max($minMax);
		        }
    	    } else{
    	    	if(min($minMax) == min($minMax) && min($minMax) < self::$tr && min($minMax) !== 'allDay'){
    	    		self::$tr = min($minMax);
    	    	}elseif(min($minMax) == min($minMax) && min($minMax) > self::$tk){
    	    		self::$tk = min($minMax);
    	    	}else{
    	    		self::$tr = 8;
    				self::$tk = 18;
    	    	}
    	    }
    	}elseif(count($minMax) == 1){
    		for($i = 0; $i <=0; $i++){
    			if($minMax[$i] < self::$tr && $minMax[$i] !== 'allDay'){
    				self::$tr = $minMax[$i];
    			}elseif($minMax[$i] > self::$tk && $minMax[$i] !== 'allDay'){
    				self::$tk = $minMax[$i];
    			} else {
    				self::$tr = 8;
    				self::$tk = 18;
    			}
    		}
    		
    	} else {
    		self::$tr = 8;
    		self::$tk = 18;
    	}

    	// Рендираме деня
    	$tpl = new ET(getFileContent('cal/tpl/SingleLayoutDays.shtml'));
    	
    	$Calendar = cls::get('cal_Calendar');
    	$Calendar->prepareListRecs($state);
    	$Calendar->prepareListFilter($state);
    	
        // Рендираме филтара "календар"
        $tpl->replace($Calendar->renderListFilter($state), 'from');
    
        // Заместваме титлата на страницата
    	$tpl->replace($currentDate, 'title');

    	$titleColor = static::color($currentKey);
    	$tpl->replace($titleColor, 'colTitle');
    
    	foreach($hours as $h => $t){
    		if($h === 'allDay' || ($h >= self::$tr && $h <= self::$tk)){
    		$hourArr = $dayData[$h];
    		$hourArr['time'] = $t;
    		$hourArr['timeJs'] = $h;
    		$hourArr['dateJs'] = $from;
 
    		// Взимаме блока от шаблона
    		$cTpl = $tpl->getBlock("COMMENT_LI");
    		
    		// Ако времето е равно на текущото време на потребителя
    		// ограждаме визуално клетката
    		if($h == $nowTime && ($h % 2 == 0 && $h != 0)){
    			$cTpl->replace('mc-todayN', 'now');
    			
    			$cTpl->replace('#D1D7D1', 'colTr');
    			
    		}elseif($h == $nowTime && ($h % 2 != 0 && $h != 0)){
    			$cTpl->replace('mc-todayD', 'now');
    		}elseif($h % 2 == 0 && $h != 0){
    			$cTpl->replace('calDayN', 'now');
    			
    			$cTpl->replace('#D1D7D1', 'colTr');
    		}else {
    			
    			$cTpl->replace('calDay', 'now');
    			
    		}
    		
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
    	
    	// Рендираме страницата
    	return  $this->renderWrapping($tpl);
 
    }

    
    /**
     * Показва събитията за цяла произволна седмица
     */
    function act_Week()
    {
    	self::requireRightFor('week');
    	
    	// Очакваме дата от филтъра
        $from = Request::get('from');
        $currentDate = dt::mysql2Verbal($from, 'l d.m.Y');
       
        // Разбиваме получената дата на ден, месец, година
        $day = dt::mysql2Verbal($from, 'd');
        $month = dt::mysql2Verbal($from, 'm');
        $year = dt::mysql2Verbal($from, 'Y');
        
        // Текущото време на потребителя
        $nowTime = strstr(dt::now(), " ");
        
        
        $hours = array( "allDay" => "Цял ден");
        
        // Генерираме масив с часовете
        for($i = 0; $i < 24; $i++){
        	$hours[$i] = str_pad($i, 2, "0", STR_PAD_LEFT). ":00";
        }
        
        // Генерираме масив с дните и масив за обратна връзка
        for($i = 0; $i < 7; $i++){
        	$days[$i] = dt::mysql2Verbal(date("Y-m-d", mktime(0, 0, 0, $month, $day + $i - 3, $year)),'l'). "<br>".
        				dt::mysql2Verbal(date("Y-m-d", mktime(0, 0, 0, $month, $day + $i - 3, $year)),'d.m.Y');
        	$dates[date("Y-m-d", mktime(0, 0, 0, $month, $day + $i - 3, $year))] = "d" . $i;
        	
        	// Помощен масив за javaScripta
        	$dateJs["date".$i."Js"] = date("d.m.Y", mktime(0, 0, 0, $month, $day + $i - 3, $year));
        	$dayWeek[$i] = date("N", mktime(0, 0, 0, $month, $day + $i - 3, $year));
        	
        	// Цветовете на деня според типа им
        	$colorTitle["c".$i] = static::color(date("Y-m-d 00:00:00", mktime(0, 0, 0, $month, $day + $i - 3, $year)));
         }
      
        // От коя до коя дата ще извличаме събитията 
        $fromDate = date("Y-m-d 00:00:00", mktime(0, 0, 0, $month, $day - 3, $year));
        $toDate = date("Y-m-d 23:59:59", mktime(0, 0, 0, $month, $day + 3, $year));
        
        // Номера на седмицата
        $weekNbFrom = date('W', mktime(0, 0, 0, $month, $day - 3, $year));
        $weekNbTo = date('W', mktime(0, 0, 0, $month, $day + 3, $year));
        
        if($weekNbFrom == $weekNbTo){
        	//bp($weekNbFrom, $weekNbTo, $fromDate, $toDate);
        	$weekNb = $weekNbFrom;
        } else {
        	
        	$weekNb = $weekNbFrom . "/" . $weekNbTo;
        }
              
        // Извличане на събитията за цялата седмица
        $state = new stdClass();
        $state->query = self::getQuery();
        
        // Кой ни е текущия потребител? 
        // Показване на календара и събитията според потребителя
        $cu = core_Users::getCurrent();
        $state->query->where("#users IS NULL OR #users = ''");
        $state->query->orLikeKeylist('users', "|$cu|");
        
        // Сортираме по времето на събитията
        $state->query->orderBy('time', 'ASC'); 

        // Извличаме записите
        while ($rec =  $state->query->fetch("#time >= '{$fromDate}' AND #time <= '{$toDate}'")){
        	
        	// Проверка за конкретния запис
    	    self::requireRightFor('week', $rec);
        	
        	// Какъв ден е
        	$dayKey = $dates[dt::mysql2verbal($rec->time, 'Y-m-d')];
    		
    		// Начален час на събитието
    		$hourKey = dt::mysql2verbal($rec->time, 'G');
    		
    		// Събитието за целия ден ли ще отнася?
    		if($rec->allDay == "yes"){
    			$hourKey = "allDay";
    		}
    		
    		// Помощен масив за определяне на най-ранното и най-късното събитие
    		if($hourKey !== "allDay"){
    			$minMax[] = $hourKey;
    		}
    		
    		// Линк към събитието
    		$url = getRetUrl($rec->url);
    		// Ид-то на събитието
    		$id = substr(strrchr($rec->url, "/"),1);
    		
    		// Картинката която ще стои пред титлета на задачите
    		$img = "<img class='calImg' src=". sbf('img/16/task.png') .">&nbsp;";

    		// Взимаме всеки път различен цвят за титлите на задачите
    		$color = array_pop(self::$colors);

            if(dt::mysql2verbal($rec->time, 'i') != "00"){
    			switch ($rec->state){
    				case "active":
    					$weekData[$hourKey][$dayKey] .= $img.ht::createLink(dt::mysql2verbal($rec->time, 'H:i'). "&nbsp;" . str::limitLen($rec->title, 10), $url, NULL, array('class'=>'calWeek', 'style' => 'color:'. $color, 'title' => $rec->title));
    					break;
    				case "draft":
    					$weekData[$hourKey][$dayKey] .= $img.ht::createLink(dt::mysql2verbal($rec->time, 'H:i'). "&nbsp;" . str::limitLen($rec->title, 10), $url, NULL, array('class'=>'draftColor', 'style' => 'color:'. $color, 'title' => $rec->title));
    					break;
    				case "closed":
    					$weekData[$hourKey][$dayKey] .= $img.ht::createLink(dt::mysql2verbal($rec->time, 'H:i'). "&nbsp;" . str::limitLen($rec->title, 10), $url, NULL, array('class'=>'closedColor', 'style' => 'color:'. $color, 'title' => $rec->title));
    					break;
    			}
    		 } elseif($hourKey != "allDay"){
    			switch ($rec->state){
    				case "active":
    					$weekData[$hourKey][$dayKey] .= $img.ht::createLink(str::limitLen($rec->title, 15), $url, NULL, array('class'=>'calWeek' , 'style' => 'color:'. $color, 'title' => $rec->title));
    					break;
    				case "draft":
    					$weekData[$hourKey][$dayKey] .= $img.ht::createLink(str::limitLen($rec->title, 15), $url, NULL, array('class'=>'draftColor' , 'style' => 'color:'. $color, 'title' => $rec->title));
    					break;
    				case "closed":
    					$weekData[$hourKey][$dayKey] .= $img.ht::createLink(str::limitLen($rec->title, 13), $url, NULL, array('class'=>'closedColor', 'style' => 'color:'. $color, 'title' => $rec->title));
    					break;
    			}
    			
    		 } elseif($hourKey == "allDay" && $rec->type == "task"){
    			switch ($rec->state){
    				case "active":
    					$weekData[$hourKey][$dayKey] .= $img.ht::createLink(str::limitLen($rec->title, 15), $url, NULL, array('class'=>'calWeek' , 'style' => 'color:'. $color, 'title' => $rec->title));
    					break;
    				case "draft":
    					$weekData[$hourKey][$dayKey] .= $img.ht::createLink(str::limitLen($rec->title, 15), $url, NULL, array('class'=>'draftColor' , 'style' => 'color:'. $color, 'title' => $rec->title));
    					break;
    				case "closed":
    					$weekData[$hourKey][$dayKey] .= $img.ht::createLink(str::limitLen($rec->title, 15), $url, NULL, array('class'=>'closedColor', 'style' => 'color:'. $color, 'title' => $rec->title));
    					break;

    			}
    		 } else {
    				$weekData[$hourKey][$dayKey] .= ht::createLink("<p class='calWeek'>" . str::limitLen($rec->title, 40) . "</p>", $url, NULL, array('title' => $rec->title));
     		 }
    		
    		  		
        }//bp($holidayKey, $nonWorking, $workday);
      
    	if(count($minMax) > 1){
    		
    	    if($minMax[0] == 'allDay'){
    			unset($minMax[0]);
    		}
	    	
    		// Определяме началото и края на деня спрямо началните стойнности
	        if(min($minMax) < self::$tr && min($minMax) !== 'allDay'){ 
	        	self::$tr = min($minMax);
	            if(max($minMax) > self::$tk ){
	        		self::$tk = max($minMax);
	        	}
	        } elseif(min($minMax) >self:: $tk){
	        	self::$tr = 8;
	        	self::$tk = min($minMax);
	        } elseif(max($minMax) > self::$tk){
	        	self::$tk = max($minMax);
	        }
    	}
     
          
    	// Рендиране на седмицата	
        $tpl = new ET(getFileContent('cal/tpl/SingleLayoutWeek.shtml'));
    	
        // Рендираме филтъра за избор на дата
    	$Calendar = cls::get('cal_Calendar');
    	$Calendar->prepareListFilter($state);
        
        $tpl->replace($Calendar->renderListFilter($state), 'from');
    	
        // Заглавие на страницата
    	$tpl->replace('Събития за седмица » ' . $weekNb, 'title');
    	
    	// Рендираме масивите с дните и javaScript масива
    	$tpl->placeArray($days);
    	$tpl->placeArray($dateJs);
    	$tpl->placeArray($colorTitle);
        
    	
   		foreach($hours as $h => $t){
   			
   			// Ограничаваме часовета в таблицата до цел ден и най-малкия и най-големия час
   			if($h === 'allDay' || ($h >= self::$tr && $h <= self::$tk)){
    		$hourArr = $weekData[$h];
    		$hourArr['time'] = $t;
    		$hourArr['timeJs'] = $h;

    		// Взимаме блока от шаблона
    		$cTpl = $tpl->getBlock("COMMENT_LI");
   			
    		// Ако времето е равно на текущото време на потребителя
    		// Ограждаме кутийката
    		if($h == $nowTime && ($h % 2 == 0 && $h != 0)){
    			$cTpl->replace('mc-todayN', 'now');
    			$cTpl->replace('calWeekN', 'col');
    			$cTpl->replace('#D1D7D1', 'colTr');
    			
    		}elseif($h % 2 == 0 && $h != 0){
    			$cTpl->replace('calWeekN', 'now');
    			$cTpl->replace('calWeekN', 'col');
    			$cTpl->replace('#D1D7D1', 'colTr');
    		}elseif($h == $nowTime && ($h % 2 != 0 && $h != 0)){
    			$cTpl->replace('mc-todayD', 'now');
    			$cTpl->replace('calWeek', 'col');
    		}else {
    			$cTpl->replace('calWeek', 'now');
    			$cTpl->replace('calWeek', 'col');
    		}
    		
    		
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
   		
   		// Рендираме страницата
        return $this->renderWrapping($tpl);
    }


    /**
     * Показва събитията за целия месец
     */
    function act_Month()
    {
    	
    	self::requireRightFor('month');
    	
    	// Очакваме дата от формата
    	$from = Request::get('from');

    	// Разбиваме я на ден, месец и година
        $day = dt::mysql2Verbal($from, 'd');
        $month = dt::mysql2Verbal($from, 'm');
        $year = dt::mysql2Verbal($from, 'Y');
      
        // Избрана дата
        //$currentDay = date('d.m.Y', mktime(0, 0, 0, $month, $day, $year));
        $currentWeek = date('W', mktime(0, 0, 0, $month, $day, $year));
        $currentKey = "d".date('N', mktime(0, 0, 0, $month, $day, $year));
     
        // Таймстамп на първия ден на месеца
        $firstDayTms = mktime(0, 0, 0, $month, 1, $year);
        
        // Броя на дните в месеца
        $lastDay = date('t', $firstDayTms);
        
        // От началото на месеца
        $fromDate = date("Y-m-d 00:00:00", $firstDayTms);

        // До края на месеца
        $toDate = date('Y-m-t 23:59:59', $firstDayTms);
        
        // Генерираме масив масива на месеца => номер на седмицата[ден от седмицата][ден]
        for($i = 1; $i <= $lastDay; $i++) {
            $t = mktime(0, 0, 0, $month, $i, $year);
            $monthArr[date('W', $t)]["d".date('N', $t)] = $i;
            
           // Цветовете на деня според типа им
        	$colorTitle["m".$i][date('W', $t)]["d".date('N', $t)] = static::color(date("Y-m-d 00:00:00", mktime(0, 0, 0, $month,  $i, $year)));
        }

        // Извличане на събитията за целия месец
        $state = new stdClass();
        $state->query = self::getQuery();
         
        // Кой ни е текущия потребител? 
        // Показване на календара и събитията според потребителя
        $cu = core_Users::getCurrent();
        $state->query->where("#users IS NULL OR #users = ''");
        $state->query->orLikeKeylist('users', "|$cu|");
        
        $state->query->orderBy('time', 'ASC');     
        while ($rec =  $state->query->fetch("#time >= '{$fromDate}' AND #time <= '{$toDate}'")){
        	// Проверка за конкретния запис
    	    self::requireRightFor('month', $rec);
        	
    	    
        	// Времето на събитието от базата
            $recTime = $rec->time;
            
            // Разбиваме това време на: ден, месец и година
            $recDay = dt::mysql2Verbal($recTime, 'j');
	        $recMonth = dt::mysql2Verbal($recTime, 'm');
	        $recYear = dt::mysql2Verbal($recTime, 'Y');
	        
	        // Таймстамп на всеки запис
	        $recT = mktime(0, 0, 0, $recMonth, $recDay, $recYear);
	        
	        // В коя седмица е този ден
	        $weekKey = date('W', $recT);
	        
	        // Кой ден от седмицата е
	        $dayKey = "d".date('N', $recT);
	        
	        // Начален час на събитието
	        $hourKey = dt::mysql2verbal($rec->time, 'G');
	        
            // Събитието за целия ден ли ще отнася?
    		if($rec->allDay == "yes"){
    			$hourKey = "allDay";
    		}
    		
	        // Линк към събитието
    		$url = getRetUrl($rec->url);
    		
    		// Ид-то на събитието
    		$id = substr(strrchr($rec->url, "/"),1);
	       
            // Картинката която ще стои пред титлета на задачите
    		//sbf('img/16/task-normal.png')
    		//$img = "<img class='calImg' src=". sbf('img/16/task.png') .">&nbsp;";
    		
    		$type[$weekKey][$dayKey] .= "<br>". $rec->type;
    		
    		// Взимаме всеки път различен цвят за титлите на задачите
    		$color = array_pop(self::$colors);
    		
            if(dt::mysql2verbal($rec->time, 'i') != "00"){
    			switch ($rec->state){
    				case "active":
    					$monthArr[$weekKey][$dayKey] .= "<br>".$img.ht::createLink(dt::mysql2verbal($rec->time, 'H:i'). "&nbsp;" . str::limitLen($rec->title, 10), $url, NULL, array('class'=>'mc-calendar-calWeek', 'style' => 'color:'. $color, 'title' => $rec->title));
    					break;
    				case "draft":
    					$monthArr[$weekKey][$dayKey] .= "<br>".$img.ht::createLink(dt::mysql2verbal($rec->time, 'H:i'). "&nbsp;" . str::limitLen($rec->title, 10), $url, NULL, array('class'=>'mc-calendar-draftColor', 'style' => 'color:'. $color, 'title' => $rec->title));
    					break;
    				case "closed":
    					$monthArr[$weekKey][$dayKey] .= "<br>".$img.ht::createLink(dt::mysql2verbal($rec->time, 'H:i'). "&nbsp;" . str::limitLen($rec->title, 10), $url, NULL, array('class'=>'mc-calendar-closedColor', 'style' => 'color:'. $color, 'title' => $rec->title));
    					break;
    			}
    		 } elseif($hourKey != "allDay"){
    			switch ($rec->state){
    				case "active":
    					$monthArr[$weekKey][$dayKey] .= "<br>".$img.ht::createLink(str::limitLen($rec->title, 17), $url, NULL, array('class'=>'mc-calendar-calWeek' , 'style' => 'color:'. $color, 'title' => $rec->title));
    					break;
    				case "draft":
    					$monthArr[$weekKey][$dayKey] .= $img.ht::createLink(str::limitLen($rec->title, 17), $url, NULL, array('class'=>'mc-calendar-draftColor' , 'style' => 'color:'. $color, 'title' => $rec->title));
    					break;
    				case "closed":
    					$monthArr[$weekKey][$dayKey] .= "<br>".$img.ht::createLink(str::limitLen($rec->title, 17), $url, NULL, array('class'=>'mc-calendar-closedColor', 'style' => 'color:'. $color, 'title' => $rec->title));
    					break;
    			}
    			
    		 } elseif($hourKey == "allDay" && $rec->type == "task"){
    			switch ($rec->state){
    				case "active":
    					$monthArr[$weekKey][$dayKey] .= "<br>".$img.ht::createLink(str::limitLen($rec->title, 17), $url, NULL, array('class'=>'mc-calendar-calWeek' , 'style' => 'color:'. $color, 'title' => $rec->title));
    					break;
    				case "draft":
    					$monthArr[$weekKey][$dayKey] .= "<br>".$img.ht::createLink(str::limitLen($rec->title, 17), $url, NULL, array('class'=>'mc-calendar-draftColor' , 'style' => 'color:'. $color, 'title' => $rec->title));
    					break;
    				case "closed":
    					$monthArr[$weekKey][$dayKey] .= "<br>".$img.ht::createLink(str::limitLen($rec->title, 17), $url, NULL, array('class'=>'mc-calendar-closedColor', 'style' => 'color:'. $color, 'title' => $rec->title));
    					break;
   
    			}
    		 } else {
    				$monthArr[$weekKey][$dayKey] .= ht::createLink("<p class='mc-calendar'>" . str::limitLen($rec->title, 40) . "</p>", $url, NULL, array('title' => $rec->title));
     		 }

        }//bp($type);

        // Изчисляваме предходния и следващия месец
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
        
        $link = '/cal_Calendar/month/?';

        $nextLink = Url::addParams($link, array('from' => $day . '-' . $nm . '-' . $ny));
        $prevtLink = Url::addParams($link, array('from' => $day . '-' . $pm . '-' . $py));

        
        // Зареждаме шаблона
        $tpl = new ET(getFileContent('cal/tpl/SingleLayoutMonth.shtml'));
        
        // Рендираме филтъра
        $Calendar = cls::get('cal_Calendar'); 
    	$Calendar->prepareListFilter($state);
        $tpl->replace($Calendar->renderListFilter($state), 'from');
        
        // Добавяне на първия хедър
        $tpl->replace($prevtLink, 'prevtLink');
        $tpl->replace($prevMonth, 'prevMonth');
        $tpl->replace($currentMonth, 'currentMonth');
        $tpl->replace($nextLink, 'nextLink');
        $tpl->replace($nextMonth, 'nextMonth');
        $tpl->placeArray($colorTitle);

        // Дните от седмицата
        static $weekDays = array('Понеделник', 'Вторник', 'Сряда', 'Четвъртък', 'Петък', 'Събота', 'Неделя');
        $tpl->placeArray($weekDays);
     
    	
        foreach($monthArr as $weekNum => $weekArr) {
        	
        	$cTpl = $tpl->getBlock("COMMENT_LI");
        	
            // Проверка за текущия ден 
        	if($weekNum == $currentWeek){
	        	switch ($currentKey){
	    				case "d1":
	    					$cTpl->replace('mc-today', 'mon');
	    					$cTpl->replace('mc-day', 'tue');
				        	$cTpl->replace('mc-day', 'wed');
				        	$cTpl->replace('mc-day', 'thu');
				        	$cTpl->replace('mc-day', 'fri');
				        	$cTpl->replace('mc-saturday', 'sat');
				        	$cTpl->replace('mc-sunday', 'sun');
	    					break;
	    				case "d2":
							$cTpl->replace('mc-today', 'tue');
							$cTpl->replace('mc-day', 'mon');
				        	$cTpl->replace('mc-day', 'wed');
				        	$cTpl->replace('mc-day', 'thu');
				        	$cTpl->replace('mc-day', 'fri');
				        	$cTpl->replace('mc-saturday', 'sat');
				        	$cTpl->replace('mc-sunday', 'sun');    				
							break;
	    				case "d3":
	    					$cTpl->replace('mc-today', 'wed');
	    					$cTpl->replace('mc-day', 'mon');
				        	$cTpl->replace('mc-day', 'tue');
				        	$cTpl->replace('mc-day', 'thu');
				        	$cTpl->replace('mc-day', 'fri');
				        	$cTpl->replace('mc-saturday', 'sat');
				        	$cTpl->replace('mc-sunday', 'sun');
	    					break;
	    				case "d4":
	    					$cTpl->replace('mc-today', 'thu');
	    					$cTpl->replace('mc-day', 'mon');
				        	$cTpl->replace('mc-day', 'tue');
				        	$cTpl->replace('mc-day', 'wed');
				        	$cTpl->replace('mc-day', 'fri');
				        	$cTpl->replace('mc-saturday', 'sat');
				        	$cTpl->replace('mc-sunday', 'sun');
	    					break;
	    				case "d5":
	    					$cTpl->replace('mc-today', 'fri');
	    					$cTpl->replace('mc-day', 'mon');
				        	$cTpl->replace('mc-day', 'tue');
				        	$cTpl->replace('mc-day', 'wed');
				        	$cTpl->replace('mc-day', 'thu');
				        	$cTpl->replace('mc-saturday', 'sat');
				        	$cTpl->replace('mc-sunday', 'sun');
	    					break;
	    				case "d6":
	    					$cTpl->replace('mc-today', 'sat');
	    					$cTpl->replace('mc-day', 'mon');
				        	$cTpl->replace('mc-day', 'tue');
				        	$cTpl->replace('mc-day', 'wed');
				        	$cTpl->replace('mc-day', 'thu');
				        	$cTpl->replace('mc-day', 'fri');
				        	$cTpl->replace('mc-sunday', 'sun');
	    					break;
	    				case "d7":
	    					$cTpl->replace('mc-today', 'sun');
	    					$cTpl->replace('mc-day', 'mon');
				        	$cTpl->replace('mc-day', 'tue');
				        	$cTpl->replace('mc-day', 'wed');
				        	$cTpl->replace('mc-day', 'thu');
				        	$cTpl->replace('mc-day', 'fri');
				        	$cTpl->replace('mc-saturday', 'sat');
				        	break;

	    		}
        	} else {
        		$cTpl->replace('mc-day', 'mon');
				$cTpl->replace('mc-day', 'tue');
				$cTpl->replace('mc-day', 'wed');
				$cTpl->replace('mc-day', 'thu');
				$cTpl->replace('mc-day', 'fri');
				$cTpl->replace('mc-saturday', 'sat');
				$cTpl->replace('mc-sunday', 'sun');
        	}
        
          
        	$cTpl->replace($weekNum, 'weekNum');
        	$cTpl->placeArray($weekArr);
        	
            $cTpl->append2master();
         }

    	
        // Заглавието на страницата
    	$tpl->replace('Събития за месец » '. $currentMonth, 'title');

    	// Рендираме страницата
        return $this->renderWrapping($tpl);

    }

    
    /**
     *
     */
    function act_Year()
    {
    	self::requireRightFor('year');
    	
        $res = '1';

        return $this->renderWrapping($res);
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
     * По зададена mysql-ска дата връща цвят според типа й:
     * черен - работни дни
     * червен - официални празници
     * тъмно зелено - събота
     * свето зелено - неделя
     */
    static function color($date){
    	
    	// Разбиваме подадената дата
    	$day = dt::mysql2Verbal($date, 'd');
        $month = dt::mysql2Verbal($date, 'm');
        $year = dt::mysql2Verbal($date, 'Y');
        
        // Взимаме кой ден от седмицата е 1=пон ... 7=нед
        $weekName = date('N', mktime(0, 0, 0, $month, $day, $year));
    	
        // Ако е събота или неделя, пресвояваме цвят
    	if($weekName == "6"){
    		$color = '#006030';
    	}elseif($weekName == "7"){
    		$color = 'green';
    	}
    	
    	// проверяваме дали има записи за този ден
        $query = static::getQuery();
        $query->where("#time = '{$date}'");
        
    	while($rec = $query->fetch()){
    		
    		// Ако деня е празник, подаваме цвета и не ни трябват повече проверки
    		if($rec->type == 'holiday'){
	        	$color = 'red';// bp($color);
	        	break;
	        }
	        // Ако деня е работе, подаваме цвета и не ни трябват повече проверки
	        elseif($rec->type == 'workday'){
	        	$color = 'black';
	        	break;
	        }
	        // Ако деня е събота или неработен ден по близко до събота
	        elseif(($weekName == "6" || ($rec->type == 'non-working' && $weekName >= "4"))  && $rec->type !== 'workday'){
	        	$color = '#006030';
	        	
	        }
	        // Ако деня е неделя или неработен ден по близко до неделя
	        elseif(($weekName == "7" || ($rec->type == 'non-working' && $weekName < "4") ) && $rec->type !== 'workday'){
	        	$color = 'green';
	        	
	        }
    	}
    	
        return $color;
    }

}
