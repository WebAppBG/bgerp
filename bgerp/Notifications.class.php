<?php



/**
 * Мениджър за известявания
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Известявания
 */
class bgerp_Notifications extends core_Manager
{
    
    
    /**
     * Необходими мениджъри
     */
    var $loadList = 'plg_Modified, bgerp_Wrapper, plg_RowTools, bgerp_plg_GroupByDate, plg_Search';
    

    /**
     * Името на полито, по което плъгина GroupByDate ще групира редовете
     */
    var $groupByDateField = 'modifiedOn';


    /**
     * Заглавие
     */
    var $title = 'Известия';
    
    
    /**
     * Права за писане
     */
    var $canWrite = 'admin';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin';
    
    
    /** 
	 *  Полета по които ще се търси
	 */
	var $searchFields = 'msg';
	
	
	/**
	 * Как се казва полето за пълнотекстово търсене
	 */
	var $searchInputField = 'noticeSearch';
	
	
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('msg', 'varchar(128)', 'caption=Съобщение, mandatory');
        $this->FLD('state', 'enum(active=Активно, closed=Затворено)', 'caption=Състояние');
        $this->FLD('userId', 'key(mvc=core_Users)', 'caption=Отговорник');
        $this->FLD('priority', 'enum(normal, warning, alert)', 'caption=Приоритет');
        $this->FLD('cnt', 'int', 'caption=Брой');
        $this->FLD('url', 'varchar', 'caption=URL->Ключ');
        $this->FLD('customUrl', 'varchar', 'caption=URL->Обект');
        $this->FLD('hidden', 'enum(no,yes)', 'caption=Скрито,notNull');

        $this->setDbUnique('url, userId');
    }
    
    
    /**
     * Добавя известие за настъпило събитие
     * @param varchar $msg
     * @param array $url
     * @param integer $userId
     * @param enum $priority
     */
    static function add($msg, $urlArr, $userId, $priority, $customUrl = NULL)
    {
        $rec = new stdClass();
        $rec->msg = $msg;
        
        $rec->url = toUrl($urlArr, 'local');
        $rec->userId = $userId;
        $rec->priority = $priority;
        
        // Потребителя не може да си прави нотификации сам на себе си
        // Ако искаме да тестваме нотификациите - дава си роля 'tester'
        if (!haveRole('tester') && $userId == core_Users::getCurrent()) return;
        
        // Ако има такова съобщение - само му вдигаме флага че е активно
        $query = bgerp_Notifications::getQuery();
        $r = $query->fetch("#userId = {$rec->userId} AND #url = '{$rec->url}'");
        
        // Ако съобщението е активно от преди това - увеличаваме брояча му
        if ($r->state == 'active') {
            $rec->cnt = $r->cnt + 1;
        } else {
            $rec->cnt = 1;
        }
        
        $rec->id = $r->id;
        $rec->state = 'active';
        if($customUrl) {
            $rec->customUrl = toUrl($customUrl, 'local');
        }
        
        bgerp_Notifications::save($rec);
    }
    
    
    /**
     * Отбелязва съобщение за прочетено
     */
    static function clear($urlArr, $userId = NULL)
    {   
        // Не изчистваме от опресняващи ajax заявки
        if(Request::get('ajax_mode')) return;

        if(empty($userId)) {
            $userId = core_Users::getCurrent();
        }
        if(empty($userId)) {
            return;
        }
        $url = toUrl($urlArr, 'local');
        $query = bgerp_Notifications::getQuery();
        
        if($userId == '*') {
            $query->where("#url = '{$url}' AND #state = 'active'");
        } else {
            $query->where("#userId = {$userId} AND #url = '{$url}' AND #state = 'active'");
        }
        $query->show('id, state, userId, url');
        
        while($rec = $query->fetch()) {
            $rec->state = 'closed';
            $rec->cnt = 0;
            bgerp_Notifications::save($rec, 'state');
        }
    }
    
    
    /**
     * Скрива посочените записи
     * Обикновено след Reject
     */
    static function setHidden($urlArr, $hidden = 'yes', $userId=NULL) 
    {
        $url = toUrl($urlArr, 'local');
        
        $query = self::getQuery();

        $query->where("#url = '{$url}'");

        if ($userId) {
            $query->where("#userId = '{$userId}'");
        }
        
        while($rec = $query->fetch()) {
            $rec->hidden = $hidden;
            self::save($rec);
        }
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
        $url = getRetUrl($rec->customUrl ? $rec->customUrl : $rec->url);
        
        if($rec->cnt > 1) {
            //  $row->msg .= " ({$rec->cnt})";
        }
        
        if($rec->state == 'active') {
            $attr['style'] = 'font-weight:bold;';
        } else {
            $attr['style'] = 'color:#666;';
        }
        
        // Превеждаме съобщението
        // Спираме преовада и въте, ако има за превеждане, тогава се превежда
        $row->msg = tr("|*{$row->msg}");
        
        $row->msg = ht::createLink($row->msg, $url, NULL, $attr);
    }
    
    
    /**
     * Рендира блок с нотификации за текущия или посочения потребител
     */
    static function render($userId = NULL)
    {
        if(empty($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        $Notifications = cls::get('bgerp_Notifications');
        
        // Създаваме обекта $data
        $data = new stdClass();
        
        // Създаваме заявката
        $data->query = $Notifications->getQuery();
        
        // Подготвяме полетата за показване
        $data->listFields = 'modifiedOn=Време,msg=Съобщение';
        
        $data->query->where("#userId = {$userId} AND #hidden != 'yes'");
        $data->query->orderBy("state,modifiedOn=DESC");

        if(Mode::is('screenMode', 'narrow')) {
            $data->query->where("#state = 'active'");
        }
        
        // Подготвяме филтрирането
        $Notifications->prepareListFilter($data);
        
        // Подготвяме навигацията по страници
        $Notifications->prepareListPager($data);
        
        // Подготвяме записите за таблицата
        $Notifications->prepareListRecs($data);
        
        // Подготвяме редовете на таблицата
        $Notifications->prepareListRows($data);
        
        // Подготвяме заглавието на таблицата
        $data->title = tr("Известия");
        
        // Подготвяме лентата с инструменти
        $Notifications->prepareListToolbar($data);
        
        // Рендираме изгледа
        $tpl = $Notifications->renderPortal($data);
        
        //Задаваме текущото време, за последно преглеждане на нотификациите
        Mode::setPermanent('lastNotificationTime', time());
        
        return $tpl;
    }


    /**
     * @todo Чака за документация...
     */
    static function getOpenCnt($userId = NULL)
    {
        if(empty($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        if($userId > 0) {
            $query = self::getQuery();
            $cnt = $query->count("#userId = $userId AND #state = 'active' AND #hidden = 'no'");
        } else {
            $cnt = 0;
        }
        
        return $cnt;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function renderPortal($data)
    {
        $Notifications = cls::get('bgerp_Notifications');
        
        $tpl = new ET("
            <div class='clearfix21 portal' style='background-color:#fff8f8'>
            <div style='background-color:#fee' class='legend'><div style='float:left'>[#PortalTitle#]</div>
            [#ListFilter#]<div class='clearfix21'></div></div>
            [#PortalPagerTop#]
            [#PortalTable#]
            [#PortalPagerBottom#]
            </div>
          ");
        
        // Попълваме титлата
        $tpl->append($data->title, 'PortalTitle');
        
        // Попълваме горния страньор
        $tpl->append($Notifications->renderListPager($data), 'PortalPagerTop');
        
    	if($data->listFilter){
    		$formTpl = $data->listFilter->renderHtml();
    		$formTpl->removeBlocks();
    		$formTpl->removePlaces();
        	$tpl->append($formTpl, 'ListFilter');
        }
        
        // Попълваме долния страньор
        $tpl->append($Notifications->renderListPager($data), 'PortalPagerBottom');
        
        // Попълваме таблицата с редовете
        $tpl->append($Notifications->renderListTable($data), 'PortalTable');
        
        return $tpl;
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
    	$data->listFilter->view = 'horizontal';
    	$data->listFilter->showFields = $mvc->searchInputField;
    	if(Request::get('Act') == 'Show'){
        	bgerp_Portal::prepareSearchForm($mvc, $data->listFilter);
    	} else {
    		$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
    	}
    }
    
    
    /**
     * Какво правим след сетъпа на модела?
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
    	$count = 0;
    	$query = static::getQuery();
    	$query->orderBy("#id", "DESC");
    	while($rec = $query->fetch()){
    		
    		// Обновяваме ключовите думи на нотификациите, ако нямат
    		$mvc->save($rec);
    		$count++;
    	}
    	
    	$res .= "Обновени ключови думи на  {$count} записа в Нотификациите";
    }
}
