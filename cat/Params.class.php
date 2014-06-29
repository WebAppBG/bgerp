<?php



/**
 * Мениджира динамичните параметри на продуктите
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Продуктови параметри
 */
class cat_Params extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = "Параметри";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, cat_Wrapper';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,typeExt,type,options,lastUsedOn';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'powerUser';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'cat,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'cat,ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'cat,ceo';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'cat,ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'cat,ceo';
    
    
    /**
     * Масив за съответствие на типовете на параметрите с тези в системата
     */
    public static $typeMap = array('double'  => 'type_Double',
    							   'weight'  => 'cat_type_Weight',
        						   'size'    => 'cat_type_Size',
    							   'density' => 'cat_type_Density',
        						   'volume'  => 'cat_type_Volume',
        						   'date'    => 'type_Date',
        						   'varchar' => 'type_Varchar',
        						   'percent' => 'type_Percent',
        						   'enum'    => 'type_Enum',
        						   'int'     => 'type_Int',
    );
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Име, mandatory');
        $this->FLD('type', 'enum(size=Размер,weight=Тегло,volume=Обем,double=Число,int=Цяло число,varchar=Текст,date=Дата,percent=Процент,enum=Изброим,density=Плътност)', 'caption=Тип');
        $this->FLD('options', 'varchar(128)', 'caption=Стойности');
        $this->FLD('suffix', 'varchar(64)', 'caption=Суфикс');
        $this->FLD('sysId', 'varchar(32)', 'input=none');
        $this->FLD('lastUsedOn', 'datetime', 'caption=Последно използване,input=hidden');
        $this->FNC('typeExt', 'varchar', 'caption=Име');
        $this->FLD('isFeature', 'enum(no=Не,yes=Да)', 'caption=Счетоводен признак за групиране->Използване,notNull,default=no,maxRadio=2,value=no,hint=Да служили параметъра като признак за групиране');
        
        $this->setDbUnique('name, suffix');
        $this->setDbUnique("sysId");
    }
    
    
    /**
     * Изчисляване на typeExt
     */
    static function on_CalcTypeExt($mvc, $rec)
    {
        $rec->typeExt = $rec->name;
        
        if (!empty($rec->suffix)) {
            $rec->typeExt .= ' [' . $rec->suffix . ']';
        }
    }
    

	/**
     * След изпращане на формата
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        if ($form->isSubmitted()) {
        	$rec = &$form->rec;
        	if($rec->options){
        		$vArr = arr::make($rec->options);
        		$Type = cls::get(static::$typeMap[$rec->type]);
        		foreach($vArr as $option){
        			if($rec->type != 'enum' && !$Type->fromVerbal($option)){
        				$form->setError('options', "Някоя от зададените стойности не е от типа {$rec->type}");
        			}
        		}
        	} else {
        		if($rec->type == 'enum'){
        			$form->setError('options', "За изброим тип задължително трябва да се се зададат стойностти");
        		}
        	}
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
    static function makeArray4Select($fields = NULL, $where = "", $index = 'id', $tpl = NULL)
    {
    	$query = static::getQuery();
    	if(strlen($where)){
    		$query->where = $where;
    	}
    	
    	$options = array();
    	while($rec = $query->fetch()){
    		$row = static::recToVerbal($rec, 'name,suffix');
    		$title = $row->name;
    		if($rec->suffix){
    			$title .= " ({$row->suffix})";
    		}
    		$options[$rec->{$index}] = $title;
    	}
    	
    	return $options;
    }
    
    
    /**
     * Помощна функция връщаща инстанция на класа от системата
     * отговарящ на типа на параметъра с опции зададените стойности
     * ако е enum или същите като предложения. Използва се и от
     * cond_ConditionsToCustomers
     * @param int $paramId - ид на параметър
     * @param string $className - в кой мениджър се намрират параметрите
     * @return core_Type $Type - типа от системата
     */
    public static function getParamTypeClass($id, $className)
    {
    	expect($Class = cls::get($className));
    	expect($rec = $Class::fetch($id));
    	
        if($rec->options) {
            $optType = ($rec->type == 'enum') ? 'options' : 'suggestions';
            
            $options = explode(',', $rec->options);
            foreach($options as $i => &$opt){
                $opt = type_Varchar::escape($opt);
            }
            $options = array('' => '') + array_combine($options, $options);
            $os = array($optType => $options);
        }
		
	    expect($Type = cls::get(static::$typeMap[$rec->type], $os));
    	
	    return $Type;
    }
    
    
	/**
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
    	$file = "cat/csv/Params.csv";
    	$fields = array( 
	    	0 => "name", 
	    	1 => "type", 
	    	2 => "suffix", 
	    	3 => "sysId",
    		4 => "options");
    	
    	$cntObj = csv_Lib::importOnce($mvc, $file, $fields);
    	$res .= $cntObj->html;
    
    	return $res;
    }
}