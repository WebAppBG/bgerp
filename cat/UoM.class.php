<?php



/**
 * Клас 'cat_UoM' - измервателни единици
 *
 * Unit of Measures
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_UoM extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_State, plg_RowTools, cat_Wrapper, plg_State2, plg_AlignDecimals, plg_Sorting';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Заглавие
     */
    var $title = 'Измерителни единици';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name', 'varchar(36)', 'caption=Мярка, export');
        $this->FLD('shortName', 'varchar(12)', 'caption=Съкращение, export');
        $this->FLD('baseUnitId', 'key(mvc=cat_UoM, select=name,allowEmpty)', 'caption=Базова мярка, export');
        $this->FLD('baseUnitRatio', 'double', 'caption=Коефициент, export');
        
        $this->setDbUnique('name');
        $this->setDbUnique('shortName');
    }
    
    
    /**
     * @param double amount
     * @param int $unitId
     */
    function convertToBaseUnit($amount, $unitId)
    {
        $rec = $this->fetch($unitId);
        
        if ($rec->baseUnitId == null) {
            $ratio = 1;
        } else {
            $ratio = $rec->baseUnitRatio;
        }
        
        $result = $amount * $ratio;
        
        return $result;
    }
    
    
    /**
     * @param double amount
     * @param int $unitId
     */
    function convertFromBaseUnit($amount, $unitId)
    {
        $rec = $this->fetch($unitId);
        
        if ($rec->baseUnitId == null) {
            $ratio = 1;
        } else {
            $ratio = $rec->baseUnitRatio;
        }
        
        $result = $amount / $ratio;
        
        return $result;
    }
    
    
    /**
     * Функция връщащи масив от всички мерки които са сродни
     * на посочената мярка (примерно за грам това са : килограм, тон и др)
     * @param int $measureId - id на мярка
     * @return array $options - всички мярки от същата категория
     * като подадената
     */
    static function getSameTypeMeasures($measureId)
    {
    	expect($rec = static::fetch($measureId), "Няма такава мярка");	
    	
    	$query = static::getQuery();
    	($rec->baseUnitId) ? $baseId = $rec->baseUnitId : $baseId = $rec->id;
    	$query->where("#baseUnitId = {$baseId}");
    	$query->orWhere("#id = {$baseId}");
    	
    	$options = array();
    	while($op = $query->fetch()){
    		$options[$op->id] = $op->name;	
    	}
    	
    	return $options;
    }
    
    
    /**
     * Функция която конвертира стойност от една мярка в друга
     * сродна мярка
     * @param double $value - Стойноста за конвертиране
     * @param int $from - Id на мярката от която ще обръщаме
     * @param int $to - Id на мярката към която конвертираме
     * @return double - Конвертираната стойност
     */
    public static function convertValue($value, $from, $to){
    	expect($fromRec = static::fetch($from), 'Проблем при изчислението на първата валута');
    	expect($toRec = static::fetch($to), 'Проблем при изчислението на втората валута');
    	
    	($fromRec->baseUnitId) ? $baseFromId = $fromRec->baseUnitId : $baseFromId = $fromRec->id;
    	($toRec->baseUnitId) ? $baseToId = $toRec->baseUnitId : $baseToId = $toRec->id;
    	
    	// Очакваме двете мерки да имат една обща основна мярка
    	expect($baseFromId == $baseToId, "Неможе да се конвертира от едната мярка в другата");
    	$rate = $fromRec->baseUnitRatio / $toRec->baseUnitRatio;
    	
    	// Форматираме резултата да се показва правилно числото
    	$rate = number_format($rate, 9, '.', '');
    	
    	return $value * $rate;
    }
    
    
    /**
     * Връща краткото име на мярката
     * @param int $id - ид на мярка
     * @return string - краткото име на мярката
     */
    public static function getShortName($id)
    {
    	expect($rec = static::fetch($id));
    	return static::recToVerbal($rec, 'shortName')->shortName;
    }
    
    
    /**
     * Функция проверяваща дали по зададен стринг има в системата
     * такава мерна еденица, ако да връщаме ид-то и
     * @param string $string - стринга представляващ мярката
     * @return mixed FALSE/int - ид-то на мярката или FALSE
     */
    public static function ifExists($string)
    {
    	$string = plg_Search::normalizeText($string);
    	$query = static::getQuery();
    	while($rec = $query->fetch()){
    		$uomNameNorm = plg_Search::normalizeText($rec->name);
    		$uomShortNameNorm = plg_Search::normalizeText($rec->shortName);
    		if($string == $uomNameNorm || $string == $uomShortNameNorm){
    			return $rec->id;
    		}
    	}
    	
    	return FALSE;
    }
    
    
    /**
     * Изпълнява се преди запис
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
    	// Ако се импортира от csv файл, заместваме основната
    	// единица с ид-то и от системата
    	if(isset($rec->csv_baseUnitId) && strlen($rec->csv_baseUnitId) != 0){
    		$rec->baseUnitId = static::fetchField("#name = '{$rec->csv_baseUnitId}'", 'id');
    	}
    }
    
    
	/**
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
    	$file = "cat/csv/UoM.csv";
    	$fields = array( 
	    	0 => "name", 
	    	1 => "shortName", 
	    	2 => "csv_baseUnitId", 
	    	3 => "baseUnitRatio",
	    	4 => "state");
    	
    	$cntObj = csv_Lib::importOnce($mvc, $file, $fields);
    	$res .= $cntObj->html;
    	
    	return $res;
    }
}