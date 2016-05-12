<?php



/**
 * Мениджира динамичните параметри на продуктите
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Продуктови параметри
 */
class cat_Params extends embed_Manager
{
    
    
	/**
	 * Свойство, което указва интерфейса на вътрешните обекти
	 */
	public $driverInterface = 'cond_ParamTypeIntf';
	
	
    /**
     * Заглавие
     */
    public $title = "Параметри";
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = "Параметър";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools, cat_Wrapper';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт,typeExt,driverClass=Тип,lastUsedOn';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'typeExt';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'powerUser';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'cat,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'cat,ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'cat,ceo';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'cat,ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'cat,ceo';
    
    
    /**
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'cat/tpl/SingleLayoutParams.shtml';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'cat,ceo';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Име, mandatory');
        $this->FLD('type', 'enum(size=Размер,weight=Тегло,volume=Обем,double=Число,int=Цяло число,varchar=Символи,text=Текст,date=Дата,percent=Процент,enum=Изброим,density=Плътност,time=Време)', 'caption=Тип,input=none');
        $this->FLD('suffix', 'varchar(64)', 'caption=Суфикс');
        $this->FLD('sysId', 'varchar(32)', 'input=none');
        $this->FLD('lastUsedOn', 'datetime', 'caption=Последно използване,input=hidden');
        $this->FNC('typeExt', 'varchar', 'caption=Име');
        $this->FLD('default', 'varchar(64)', 'caption=Конкретизиране->Дефолт');
        $this->FLD('isFeature', 'enum(no=Не,yes=Да)', 'caption=Счетоводен признак за групиране->Използване,notNull,value=no,maxRadio=2,value=no,hint=Използване като признак за групиране в счетоводните справки?');
        $this->FLD('showInPublicDocuments', 'enum(no=Не,yes=Да)', 'caption=Показване във външни документи->Показване,notNull,value=yes,maxRadio=2');
        
        $this->setDbUnique('name, suffix');
        $this->setDbUnique("sysId");
    }
    
    
    /**
     * Изчисляване на typeExt
     */
    protected static function on_CalcTypeExt($mvc, $rec)
    {
        $rec->typeExt = $rec->name;
        
        if (!empty($rec->suffix)) {
            $rec->typeExt .= ' [' . $rec->suffix . ']';
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$data->form->setDefault('showInPublicDocuments', 'yes');
    	$data->form->setField('driverClass', 'caption=Тип');
    	
    	if($data->form->rec->sysId){
    		$data->form->setReadOnly('name');
    		$data->form->setReadOnly('type');
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if($action == 'delete' && $rec->id) {
           if($rec->sysId || $rec->lastUsedOn) {
                $requiredRoles = 'no_one';
           }
        }
    }
    
   
    /**
     * Връща ид-то на параметъра по зададен sysId
     * @param string $sysId
     * @return int $id - ид на параметъра
     */
    public static function fetchIdBySysId($sysId)
    {
    	return static::fetchField(array("#sysId = '[#1#]'", $sysId), 'id');
    }
    
    
    /**
     * Подготвя опциите за селектиране на параметър като към името се
     * добавя неговия suffix 
     */
    public static function makeArray4Select($fields = NULL, $where = "", $index = 'id', $tpl = NULL)
    {
    	$query = static::getQuery();
    	if(strlen($where)){
    		$query->where = $where;
    	}
    	
    	$options = array();
    	while($rec = $query->fetch()){
    		$title = $rec->name;
    		if($rec->suffix){
    			$title .= " ({$rec->suffix})";
    		}
    		$options[$rec->{$index}] = $title;
    	}
    	
    	return $options;
    }
    
    
    /**
     * Връща типа на параметъра
     * 
     * @param mixed $id - ид или запис на параметър
     * @return FALSE|cond_type_Proto - инстанцирания тип или FALSE ако не може да се определи
     */
    public static function getTypeInstance($id)
    {
    	$rec = static::fetchRec($id);
    	if($Driver = static::getDriver($rec)){
    		return $Type = $Driver->getType($rec);
    	}
    	
    	return FALSE;
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    function loadSetupData()
    {
    	$file = "cat/csv/Params.csv";
    	$fields = array(
    			0 => "name",
    			1 => "driverClass",
    			2 => "suffix",
    			3 => "sysId",
    			4 => "options",
    			5 => "default",
    			6 => "showInPublicDocuments",
    	);
    	 
    	$cntObj = csv_Lib::importOnce($this, $file, $fields);
    	$res .= $cntObj->html;
    	
    	return $res;
    }
    
    
    /**
     * Връща дефолт стойността за параметъра
     * 
     * @param $paramId - ид на параметър
     * @return FALSE|string
     */
    public static function getDefault($paramId)
    {
    	// Ако няма гледаме имали дефолт за параметъра
    	$default = self::fetchField($paramId, 'default');
    	
    	if(!empty($default)) return $default;
    	
    	return FALSE;
    }
    
    
    /**
     * Изпълнява се преди импортирването на данните
     */
    public static function on_BeforeImportRec($mvc, &$rec)
    {
    	core_Classes::add($rec->driverClass);
    	$rec->driverClass = cls::get($rec->driverClass)->getClassId();
    }
}