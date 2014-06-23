<?php 


/**
 * Смени
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hr_Shifts extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Графици";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "График";
    
    
    /**
     * @todo Чака за документация...
     */
    var $pageMenu = "Персонал";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, hr_Wrapper,  plg_Printing,
                       plg_SaveAndNew, WorkingCycles=hr_WorkingCycles';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,hr';
    
    
    var $details = 'hr_ShiftDetails';
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'ceo,hr';
    

    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'hr/tpl/SingleLayoutShift.shtml';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование, mandatory');
        $this->FLD('cycle', 'key(mvc=hr_WorkingCycles,select=name)', "caption=Раб. цикъл");
        $this->FLD('startingOn', 'datetime', "caption=Започване на");
        $this->FLD('employersCnt', 'datetime', "caption=Служители,input=none");
        
        $this->setDbUnique('name');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_BeforePrepareEditForm($mvc, $data)
    {
        if(!$mvc->WorkingCycles->fetch('1=1')) {
            core_Message::redirect("Моля въведете поне един работен режим", 'page_Error', NULL, array('hr_WorkingCycles'));
        }
    }

    
    static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
    	static $shiftMap = array(
    		0 => 'п',
    		1 => 'I',
    		2 => 'II',
    		3 => 'н',
    		4 => 'д',
 		);
 		
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
        
        $nextLink = $prevtLink = getCurrentUrl();
        
        $nextLink['cal_month'] = $nm;
        $nextLink['cal_year'] = $ny;
        $nextLink = toUrl($nextLink);
        
        $prevtLink['cal_month'] = $pm;
        $prevtLink['cal_year'] = $py;
        $prevtLink = toUrl($prevtLink);

        $header = "<table class='mc-header' width='100%' cellpadding='0'>
                <tr>
                    <td align='left'><a href='{$prevtLink}'>{$prevMonth}</a></td>
                    <td align='center'><b>{$currentMonth}</b></td>
                    <td align='right'><a href='{$nextLink}'>{$nextMonth}</a></td>
                </tr>
            </table>";

		//$year = dt::mysql2verbal($data->rec->startingOn, "Y");
		//$month = Request::get('cal_month', 'int');
		
		 // Таймстамп на първия ден на месеца
        $firstDayTms = mktime(0, 0, 0, $month, 1, $year);

        // Броя на дните в месеца (= на последната дата в месеца);
        $lastDay = date('t', $firstDayTms);
        
        for($i = 1; $i <= $lastDay; $i++){
        	$daysTs = mktime(0, 0, 0, $month, $i, $year);
        	$date = date("Y-m-d H:i", $daysTs);
        	$d[$i] = new stdClass(); 
    		
    		$d[$i]->html = "<span style='float: left;'>" . $shiftMap[static::getShiftDay($data->rec, $date)] . "</span>";
    		$d[$i]->type = (string)static::getShiftDay($data->rec, $date);
    		
	        if($d[$i]->type == '0'){
	        	$res->row->shift0 = ' rest'; 
	        } elseif($d[$i]->type == '1'){
	        	$res->row->shift1 = ' first';
	        } elseif($d[$i]->type == '2'){
	        	$res->row->shift2 = ' second';
	        } elseif($d[$i]->type == '3'){
	        	$res->row->shift3 = ' third';
	        } elseif($d[$i]->type == '4'){
	        	$res->row->shift4 = ' diurnal';
	        }
        }
        
    	
        $res->row->month = dt::getMonth($month, $format = 'F', $lg = 'bg');
    	$res->row->calendar = cal_Calendar::renderCalendar($year, $month, $d, $header);
   }

    function on_AfterRenderWrapping($mvc, &$tpl)
    {
    	$tpl->push('hr/tpl/style.css', 'CSS');
    }
    
    function act_Test()
    {
    	$id = 3;
    	$rec = self::fetch("#id='{$id}'");
    	$recDetail = hr_ShiftDetails::fetch("#shiftId='{$id}'");
    	
    	$date = '2013-05-03 00:00:00';

    	//bp(static::getShiftDay($rec, $date));
    	bp(static::putNewShiftDetail($rec, $recDetail));
    }
    
    
    /**
     * По зададена смяна и ден от календара
     * връща режима на смяната
     * 
     * @param stdClass $recShift
     * @param mySQL date $date
     */
    static public function getShiftDay($recShift, $date)
    {
    	// По кой цикъл работи смяната
    	// Кога започва графика на смяната
    	$cycle = $recShift->cycle;
    	$startOn = $recShift->startingOn;
    	
    	// Продължителността на цикъла в дни
    	$cycleDuration = hr_WorkingCycles::fetchField($cycle, 'cycleDuration');
    
    	// В кой ден от цикъла сме
    	$dayIs = (dt::daysBetween($date, $startOn) + 1) % $cycleDuration;
    	
    	// Извличане на данните за циклите
        $stateDetails = hr_WorkingCycleDetails::getQuery();

		// Подробности за конкретния цикъл
		$stateDetails->where("#cycleId='{$cycle}' AND #day='{$dayIs}'");
        
		// Взимаме записа на точно този избран ден от цикъла
		// Кога за почва режима и с каква продължителност е
		$cycleDetails = $stateDetails->fetch();
		$dayStart = $cycleDetails->start;
		$dayDuration = $cycleDetails->duration;
		
		return hr_WorkingCycleDetails::getWorkingShiftType($dayStart, $dayDuration);
    }
    
    
    /**
     * 
     */
    static public function putNewShiftDetail($recShift, $recDetail)
    {
    	if($recDetail->startingOn > $recShift->startingOn)
    	{
    		bp($recDetail->startingOn > $recShift->startingOn, $recDetail->startingOn , $recShift->startingOn);
    	}
    	
    }
}