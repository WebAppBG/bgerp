<?php



/**
 * Мениджира динамичните параметри на категориите
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
     * @todo Чака за документация...
     */
    var $pageMenu = "Каталог";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_Rejected, plg_RowTools, cat_Wrapper';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,typeExt,type,options';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'user';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'cat,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'cat,ceo';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'user';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'cat,ceo';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Име, mandatory');
        $this->FLD('type', 'enum(double=Число,int=Цяло число,varchar=Текст,date=Дата,percent=Процент,enum=Изброим)', 'caption=Тип');
        $this->FLD('options', 'varchar(128)', 'caption=Стойности');
        $this->FLD('suffix', 'varchar(64)', 'caption=Суфикс');
        $this->FLD('sysId', 'varchar(32)', 'input=none');
        $this->FNC('typeExt', 'varchar', 'caption=Име');
        
        $this->setDbUnique('name, suffix');
        $this->setDbUnique("sysId");
    }
    
    
    /**
     * @todo Чака за документация...
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
        		$Type = cls::get("type_{$rec->type}");
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
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if($action == 'delete' && $rec->id) {
            if($rec->sysId || cat_products_Params::fetch("#paramId = $rec->id")) {
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
    			$title .= " - {$row->suffix}";
    		}
    		$options[$rec->{$index}] = $title;
    	}
    	
    	return $options;
    }
    
    
    /**
     * Помощна функция връщаща инстанция на класа от системата
     * отговарящ на типа на параметъра с опции зададените стойности
     * ако е enum или същите като предложения. Използва се и от
     * salecond_ConditionsToCustomers
     * @param int $paramId - ид на параметър
     * @param string $className - в кой мениджър се намрират параметрите
     * @return core_Type $Type - типа от системата
     */
    public static function getParamTypeClass($id, $className)
    {
    	expect($Class = cls::get($className));
    	expect($rec = $Class::fetch($id));
    	$Varchar = cls::get('type_Varchar');
        $optType = ($rec->type == 'enum') ? 'options' : 'suggestions';
        $options = explode(',', $rec->options);
	    foreach($options as $i => &$opt){
	        $opt = $Varchar->toVerbal($opt);
	    }
        $options = array('' => '') + array_combine($options, $options);
	    expect($Type = cls::get("type_{$rec->type}", array($optType => $options)), "Няма тип \"type_{$rec->type}\" в системата");
    	
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
	    	3 => "sysId");
    	
    	$cntObj = csv_Lib::importOnce($mvc, $file, $fields);
    	$res .= $cntObj->html;
    	
    	return $res;
    }
}
