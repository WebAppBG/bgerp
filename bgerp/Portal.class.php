<?php



/**
 * Портален изглед на състоянието на системата
 *
 * Има възможност за костюмиране за всеки потребител
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_Portal extends core_Manager
{
    
    
    /**
     * Неща за зареждане в началото
     */
    var $loadList = 'plg_Created, plg_RowTools, bgerp_Wrapper';
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = 'Елементи на портала';
    
    // Права
    
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('column', 'enum(1,2,3,4)', 'caption=Колона, mandatory');
        $this->FLD('blockSource', 'class(interface=bgerp_BlockSource)', 'caption=Контролер, mandatory');
        $this->FLD('params', 'text', 'caption=Настройки,input=none');
        $this->FLD('userId', 'key(mvc=core_Users)', 'caption=Потребител');
        $this->FLD('mobile', 'enum(no=Не,yes=Да)', 'caption=Мобилен');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function act_Show()
    {
        requireRole('user');

        Mode::set('pageMenuKey', '_none_');

        if(Mode::is('screenMode', 'narrow')) {
            $tpl = new ET("
                <div>[#NOTIFICATIONS#]</div>
                <div>[#STATUSES#]</div>
                <div style='margin-top:25px;'>[#RIGHT_COLUMN#]</div>
                <div style='margin-top:25px;'>[#LEFT_COLUMN#]</div>
            ");
        } else {
            $tpl = new ET("
            <table width=100% class='top-table' cellspacing=10 >
            <tr>
                <td width=32%>[#LEFT_COLUMN#]</td>
                <td width=36%>[#NOTIFICATIONS#]</td>
                <td width=32%>[#RIGHT_COLUMN#]</td>
            </tr>
            </table>
            ");
        }
        
        // Добавяме "Наскоро" - документи и папки с които е работено наскоро
        $tpl->append(bgerp_Recently::render(), 'LEFT_COLUMN');
        
        $tpl->replace(bgerp_Notifications::render(), 'NOTIFICATIONS');
		
        
		// Задачи
        if(Mode::is('listTasks', 'by')) {
           $taskTitle   = tr('Задачи от');
           $switchTitle = tr('Задачи към') . ' ' . crm_Profiles::getUserTitle(core_Users::getCurrent('nick'));
        } else {
            $taskTitle = tr('Задачи към');
            $switchTitle = tr('Задачи от') . ' ' . crm_Profiles::getUserTitle(core_Users::getCurrent('nick'));
        }

        $taskTitle = str_replace(' ', '&nbsp;', $taskTitle);

        $tasksTpl = new ET('<div class="clearfix21 portal" style="background-color:#fffff0;margin-bottom:25px;">
            <div class="legend" style="background-color:#ffd;">' . $taskTitle . '&nbsp;' . crm_Profiles::createLink() . '&nbsp;[#SWITCH_BTN#]&nbsp;[#ADD_BTN#]</div>
            [#TASKS#]
            </div>');
        
        // Бутон за добавяне на задачи
        $img = sbf('img/16/add.png');
		$addUrl = array('cal_Tasks', 'add');
		$addBtn = ht::createLink(' ', $addUrl, NULL, array('style' => "background-image:url({$img})", 'class' => 'linkWithIcon addTask', 'title' => 'Добавяне на нова Задача'));
        $tasksTpl->append($addBtn, 'ADD_BTN');
        
        // Бутон за смяна от <-> към
        $img = sbf('img/16/arrow-switch-270.png');
		$addUrl = array('cal_Tasks', 'SwitchByTo');
		$addBtn = ht::createLink(' ', $addUrl, NULL, array('style' => "background-image:url({$img})", 'class' => 'linkWithIcon addTask', 'title' => $switchTitle, 'id' => 'switchTasks'));
        $tasksTpl->append($addBtn, 'SWITCH_BTN');
       
        $tasksTpl->append(cal_Tasks::renderPortal(), 'TASKS');

        $tpl->append($tasksTpl, 'RIGHT_COLUMN');

        
        $calendarHeader = new ET('<div class="clearfix21 portal" style="background-color:#f8fff8;">
            <div class="legend" style="background-color:#efe;">' . tr('Календар') . '</div>
            [#CALENDAR_DETAILS#]
            </div>');
        
        $calendarHeader->append(cal_Calendar::renderPortal(), 'CALENDAR_DETAILS');

        $tpl->append($calendarHeader, 'RIGHT_COLUMN');
        
        jquery_Jquery::enable($tpl);
        $tpl->push('js/PortalSearch.js', 'JS');
        
        return $tpl;
    }
    
    
    /**
     * Подготвя форма за търсене в портала
     * @param core_Mvc $mvc - викащия клас
     * @param core_Form $form - филтър форма
     */
    public static function prepareSearchForm(core_Mvc $mvc, core_Form &$form)
    {
    	$form->layout = new ET(getFileContent("bgerp/tpl/PortalSearch.shtml"));
    	$form->layout->replace($mvc->searchInputField, 'FLD_NAME');
    	if($search = Request::get($mvc->searchInputField)){
    		$form->layout->replace($search, 'VALUE');
    	}
    	$findIcon = sbf('img/16/find.png');
    	$form->layout->replace($mvc->className, 'LIST');
    	$form->layout->replace($findIcon, 'ICON');
    	static::prepareSearchDataList($mvc, $form);
        $form->toolbar->addSbBtn('', NULL, "ef_icon=img/16/find.png,class=SearchBtnPortal");
        $form->setField('id', 'input=none');
        
        // Зареждаме всички стойности от GET заявката в формата, като
        // пропускаме тези които не са параметри в нея
        foreach(getCurrentUrl() as $key => $value){
        	if($key != 'App' && $key != 'Ctr' && $key != 'Act' && $key != 'Cmd'){
        	 	if(!$form->fields[$key]){
        	 		$form->FNC($key, 'varchar', 'input=hidden');
        	 		$form->setDefault($key, $value);
        	 	}
        	 }
        }
    }
    
    
    /**
     * Подготовка на дата листа с предложения за формата за търсене
     */
    public static function prepareSearchDataList($mvc, &$form)
    {
    	$Recently = cls::get('recently_Values');
    	$name = $form->mvc->dbTableName . "." . $mvc->searchInputField;
    	$suggestions = $Recently->getSuggestions($name);
    	
    	$html = "<datalist id='{$mvc->className}'>\n";
    	if(count($suggestions)){
	    	foreach($suggestions as $string){
	    		$html .= "<option value='{$string}'>\n";
	    	}
    	}
    	$html .= "</datalist>\n";
    	$form->layout->append(new ET($html), 'DATA_LIST');
    }
}