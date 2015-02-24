<?php 


/**
 * Структура
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hr_Departments extends core_Master
{
    
     /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'acc_RegisterIntf,hr_DepartmentAccRegIntf,bgerp_plg_Blank, doc_FolderIntf';

    
    /**
     * Необходими пакети
     */
    public $depends = 'acc=0.1';
    
    
    /**
     * Детайли на този мастер
     */
    public $details = 'AccReports=acc_ReportDetails,Grafic=hr_WorkingCycles,Positions=hr_Positions';
    
    
    /**
     * По кои сметки ще се правят справки
     */
    public $balanceRefAccounts = '611,6112';
    
    
    /**
     * По кой итнерфейс ще се групират сметките
     */
    public $balanceRefGroupBy = 'crm_ContragentAccRegIntf';
    
    
    /**
     * Заглавие
     */
    public $title = "Организационна структура";
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = "Звено";
    
    
    /**
     * Страница от менюто
     */
    public $pageMenu = "Персонал";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, hr_Wrapper, doc_FolderPlg, plg_Printing, plg_State, plg_Rejected,
                     plg_Created, WorkingCycles=hr_WorkingCycles,acc_plg_Registry';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,hr';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,hr';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,hr';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'ceo,hr';
    
    
    /**
     * Кой може да оттегля
     */
    public $canReject = 'ceo,hr';
    
    
    /**
     * Кой може да го възстанови?
     */
    public $canRestore = 'ceo,hr';
    
    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'hr/tpl/SingleLayoutDepartment.shtml';
    
    
    /**
     * Единична икона
     */
    public $singleIcon = 'img/16/user_group.png';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Кои полета ще извличаме, преди изтриване на заявката
     */
    public $fetchFieldsBeforeDelete = 'id,name';
   
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, name, type, staff, locationId, employmentOccupied=Назначени, employmentTotal=От общо, schedule=График';

    
    /**
     * Дефолт достъп до новите корици
     */
    public $defaultAccess = 'public';
    
    
    // Подготвяме видовете графики 
    static $chartTypes = array(
        'List' => 'Tаблица',
        'StructureChart' => 'Графика',
    );
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование, mandatory,width=100%');
        $this->FLD('type', 'enum(section=Поделение,
                                 branch=Клон,
                                 office=Офис,
                                 affiliate=Филиал,
                                 division=Дивизия,
                                 direction=Дирекция,
                                 department=Oтдел,
                                 plant=Завод,
                                 workshop=Цех, 
                                 unit=Звено,
                                 brigade=Бригада,
                                 shift=Смяна,
                                 organization=Учреждение)', 'caption=Тип, mandatory,width=100%');
        $this->FLD('nkid', 'key(mvc=bglocal_NKID, select=title,allowEmpty=true)', 'caption=НКИД, hint=Номер по НКИД');
        $this->FLD('staff', 'key(mvc=hr_Departments, select=name,allowEmpty)', 'caption=В състава на,width=100%');
        $this->FLD('locationId', 'key(mvc=crm_Locations, select=title, allowEmpty)', "caption=Локация,width=100%");
        $this->FLD('activities', 'enum(yes=Да, no=Не)', "caption=Център на дейности,maxRadio=2,columns=2,notNull,value=no, input=none,");
        
        $this->FLD('employmentTotal', 'int', "caption=Служители->Щат, input=none");
        $this->FLD('employmentOccupied', 'int', "caption=Служители->Назначени, input=none");
        $this->FLD('schedule', 'key(mvc=hr_WorkingCycles, select=name, allowEmpty=true)', "caption=Работно време->График");
        $this->FLD('startingOn', 'datetime', "caption=Работно време->Начало");
        $this->FLD('orderStr', 'varchar', "caption=Подредба,input=none,column=none");
        // Състояние
        $this->FLD('state', 'enum(active=Вътрешно,closed=Нормално,rejected=Оттеглено)', 'caption=Състояние,value=closed,notNull,input=none');
        $this->FLD('systemId', 'varchar', 'input=none');
        
        $this->setDbUnique('systemId');
        $this->setDbUnique('name');
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
    	//bp($data->form->setDefault('locationId',crm_Locations::getOwnLocations()));
        
        // Да не може да се слага в звена, които са в неговия състав
        if($id = $data->form->rec->id) {
            $notAllowedCond = "#id NOT IN (" . implode(',', self::getInheritors($id, 'staff')) . ")";
        }
        
        $query = self::getQuery();
        $query->orderBy('#orderStr');
        
        while($r = $query->fetch($notAllowedCond)) {
            self::expandRec($r);
            $opt[$r->id] = $r->name;
        }
        
        $data->form->setOptions('staff', $opt);
    }
    
    
    /**
     * Връща наследниците на даден запис
     */
    public static function getInheritors($id, $field, &$arr = array())
    {
        $arr[$id] = $id;
        $query = self::getQuery();
        
        while($rec = $query->fetch("#{$field} = $id")) {
            
            self::getInheritors($rec->id, $field, $arr);
        }
        
        return $arr;
    }
    
    
    /**
     * Добавя данните за записа, които зависят от неговите предшественици и от неговите детайли
     */
    public static function expandRec($rec)
    {
        $parent = $rec->staff;
        
        while($parent && ($pRec = self::fetch($parent))) {
            $rec->name = $pRec->name . ' » ' . $rec->name;
            setIfNot($rec->nkid, $pRec->nkid);
            setIfNot($rec->locationId, $pRec->locationId);
            $parent = $pRec->staff;
        }
    }
    
    
    /**
     * Определя заглавието на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
        self::expandRec($rec);
        
        return parent::getRecTitle($rec, $escaped);
    }
    
    
    /**
     * Изпънява се преди превръщането във вербални стойности на записа
     */
    public function on_BeforeRecToVerbal($mvc, &$row, &$rec)
    {
        self::expandRec($rec);
    }
    
    
    /**
     * Проверка за зацикляне след субмитване на формата. Разпъване на всички наследени роли
     */
    public static function on_AfterInputEditForm($mvc, $form)
    {
        $rec = $form->rec;
        
        // Ако формата е субмитната и редактираме запис
        if ($form->isSubmitted() && ($rec->id)) {
            
            if($rec->staff || $rec->dependent) {
                
                $expandedDepartment = self::expandRec($form->rec->dependent);
                
                // Ако има грешки
                if ($expandedDepartment[$rec->id]) {
                    $form->setError('dependent', "|Не може отдела да е подчинен на себе си");
                } else {
                    $rec->dependent = keylist::fromArray($expandedDepartment);
                }
            }
        }
        
        $mvc->currentTab = "Структура->Таблица";
    }
    
    
    /**
     * Извиква се преди подготовката на масивите $data->recs и $data->rows
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy("#orderStr");
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    public static function on_BeforeSave($mvc, $id, $rec)
    {
        $rec->orderStr = '';
        
        if ($rec->staff) {
            $rec->orderStr = self::fetchField($rec->staff, 'orderStr');
        }
        $rec->orderStr .= str_pad(mb_substr($rec->name, 0, 10), 10, ' ', STR_PAD_RIGHT);
        
        if(!$rec->id) {
        	$rec->state = 'active';
        	$rec->activities == 'yes';
        }
        
        $rec->activities == 'yes';
    }
    
    
    /**
     * След промяна на обект от регистър
     */
    function on_AfterSave($mvc, &$id, &$rec, $fieldList = NULL)
    {
    	if($rec->activities == 'yes'){
    	    // Добавя се като перо 

    		$rec->lists = keylist::addKey($rec->lists, acc_Lists::fetchField(array("#systemId = '[#1#]'", 'departments'), 'id'));
    		acc_Lists::updateItem($mvc, $rec->id, $rec->lists);
        } else {
    		//acc_Lists::removeItem($mvc, $rec->id);
        }
    }
    
    
    /**
     * Игнорираме pager-а
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_BeforePrepareListPager($mvc, &$res, $data) {
        // Ако искаме да видим графиката на структурата
        // не ни е необходимо страницирване
        if(Request::get('Chart')  == 'Structure') {
            // Задаваме броя на елементите в страница
            $mvc->listItemsPerPage = 1000000;
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if($action == 'delete'){
            if ($rec->id) {
                
                $haveContracts = hr_EmployeeContracts::fetch("#departmentId = '{$rec->id}'");
                
                $haveSubDeparments = self::fetch("#staff = '{$rec->id}'");
                
                if($haveContracts || $haveSubDeparments){
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * Добавя след таблицата
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    public function on_AfterRenderListTable($mvc, &$tpl, $data)
    {
        $chartType = Request::get('Chart');
        
        if($chartType == 'Structure') {
            
            $tpl = static::getChart($data);
            
            $mvc->currentTab = "Структура->Графика";
        } else {
            $mvc->currentTab = "Структура->Таблица";
        }
    }
    
    
    /**
     * След подготовка на сингъла
     */
    public static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
    	$mvc->currentTab = "Структура->Таблица";
    }
    
    
    /*******************************************************************************************
     * 
     * ИМПЛЕМЕНТАЦИЯ на интерфейса @see crm_ContragentAccRegIntf
     * 
     ******************************************************************************************/
    
    
    /**
     * Връща заглавието и мярката на перото за продукта
     *
     * Част от интерфейса: intf_Register
     */
    function getItemRec($objectId)
    {
        $result = NULL;
        
        if ($rec = self::fetch($objectId)) {
            $result = (object)array(
                'title' => $rec->name . " dp",
                'num' => "Dep" . $rec->id,
                //'features' => 'foobar' // @todo!
            );
        }
        
        return $result;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     * @param int $objectId
     */
    static function itemInUse($objectId)
    {
        // @todo!
    }
    
    
    /**
     * КРАЙ НА интерфейса @see acc_RegisterIntf
     */
    
    /****************************************************************************************
     *                                                                                      *
     *  ИМПЛЕМЕНТАЦИЯ НА @link doc_DocumentIntf                                             *
     *                                                                                      *
     ****************************************************************************************/

    
    /**
     * Изчертаване на структурата с данни от базата
     */
    public static function getChart ($data)
    {
        $arrData = (array)$data->recs;
        
        foreach($arrData as $rec){ 
            // Ако имаме родител 
             if($rec->staff == NULL && $rec->systemId !== 'myOrganisation') {
                 $parent = '0';
                 // взимаме чистото име на наследника
                 $name = self::fetchField($rec->id, 'name');
             } else {
                 // в противен случай, го взимаме както е
                 if ($rec->systemId == 'myOrganisation'){
                     $name = $rec->name;
                     $oldId = $rec->id;
                     $rec->id = '0';
                     $parent = 'NULL';
                 } elseif ($rec->staff == $oldId) {
                 	$name = self::fetchField($rec->id, 'name');
                 	$parent = '0';
                 } else {
                     $name = self::fetchField($rec->id, 'name');
                     $parent = $rec->staff;
                 }
             }
         
             $res[] = array(
             'id' => $rec->id,
             'title' => $name,
             'parent_id' => $parent,
             );
        }
       
        $chart = orgchart_Adapter::render_($res);
        
        return $chart;
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
    	// Подготвяме пътя до файла с данните
    	$file = "hr/csv/Departments.csv";
    	
    	// Кои колонки ще вкарваме
    	$fields = array(
    			0 => "name",
    			1 => "activities",
    			2 => "systemId",
    			3 => "type",
    	);
    	
    	// Импортираме данните от CSV файла.
    	// Ако той не е променян - няма да се импортират повторно
    	$cntObj = csv_Lib::importOnce($this, $file, $fields, NULL, NULL);
    	
    	// Записваме в лога вербалното представяне на резултата от импортирането
    	$res .= $cntObj->html;
    	
    	return $res;
    }
}
