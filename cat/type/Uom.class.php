<?php



/**
 * Клас  'cat_type_Uom' 
 * Тип за мерни еденици. Позволява да се въведе стойност с
 * нейната мярка по подобие на типа 'type_Time'. Примерно "5 килограма" и подобни.
 * Разпознава се коя мярка отговаря на посочения стринг и стойността се записва в
 * базата данни с основната си мярка
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class cat_type_Uom extends type_Varchar {
    
    /**
     * MySQL тип на полето в базата данни
     */
    public $dbFieldType = 'double';
    
    
    /**
     * Дължина на полето в mySql таблица
     */
    public $dbFieldLen = '11';
    
    
    /**
     * Стойност по подразбиране
     */
    public $defaultValue = 0;
    

    /**
     * Атрибути на елемента "<TD>" когато в него се записва стойност от този тип
     */
    public $cellAttr = 'align="right"';
    
    
    /**
     * @type_Double
     */
    protected $double;
    
    
    /**
     * ид на основната мярка на полето
     */
    protected $baseMeasureId;
    
    
	/**
     * Инициализиране на обекта
     */
    function init($params = array())
    {
        parent::init($params);
        
        // Инстанциране на type_Double
        $this->double = cls::get('type_Double', $params);
       
        // Запомняне на ид-то отговарящо на основната мярка
        $this->baseMeasureId = cat_UoM::fetchBySysId($this->params['unit'])->id;
    	expect($this->baseMeasureId);
    }
    
    
    /**
     * Конвертира от вербална стойност
     */
    function fromVerbal_($value)
    {
    	// Ако няма стойност
    	if(!is_array($value)) return NULL;
    	
    	// Тримване на въведената числова стойност
    	$left = trim($value['lP']);
    	
    	// Обръщане в невербален вид
    	$left = $this->double->fromVerbal($left);
    	
    	// Ако има проблем при обръщането сетва се грешка
    	if($left === FALSE){
	        $this->error = "Не е въведено валидно число";
	        	
	        return FALSE;
	    }
	    
	    // Конвертиране в основна мярка на числото от избраната мярка
	    $left = cat_UoM::convertToBaseUnit($left, $value['rP']);
	   
	    // Връщане на сумата в основна мярка
	    return $left;
    }
    
    
    /**
     * Рендиране на полето
     */
    function renderInput_($name, $value = '', &$attr = array())
	{
		// Ако има запис, конвертира се в удобен вид
		if(isset($value)){
			if(empty($this->error)){
				$convObject = cat_UoM::smartConvert($value, $this->params['unit'], FALSE, TRUE);
			} else {
				$convObject = new stdClass();
				$convObject->value = $value['lP'];
				$convObject->measure = $value['rP'];
			}
		}
		
		// Рендиране на частта за въвеждане на числото
		setIfNot($attr['size'], '7em');
		$inputLeft = $this->double->renderInput($name . '[lP]', $convObject->value, $attr);
		unset($attr['size']);
		
		// Извличане на всички производни мярки
		$options = cat_UoM::getSameTypeMeasures($this->baseMeasureId, TRUE);
        unset($options['']);
        
		$inputRight = " &nbsp;" . ht::createSmartSelect($options, $name . '[rP]', $convObject->measure);
		
		// Добавяне на дясната част към лявата на полето
        $inputLeft->append($inputRight);
        
        // Връщане на готовото поле
        return $inputLeft;
	}
	
	
	/**
     * Форматира числото в удобна за четене форма
     */
    function toVerbal_($value)
    {
    	if(!isset($value) || !is_numeric($value)) return NULL;
        $value = abs($value);
       	
        return cat_UoM::smartConvert($value, $this->params['unit']);
    }
}