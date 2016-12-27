<?php


/**
 * Базов драйвер за партиден клас 'срок на годност'
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title Срок на годност
 */
class batch_definitions_ExpirationDate extends batch_definitions_Proto
{
	
	
	/**
	 * Позволени формати
	 */
	private $formatSuggestions = 'm/d/y,m.d.y,d.m.Y,m/d/Y,d/m/Y,Ymd,Ydm,Y-m-d,dmY,ymd,ydm';
	
	
	/**
	 * Име на полето за партида в документа
	 *
	 * @param string
	 */
	public $fieldCaption = 'Ср. годност';
	
	
	/**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
		$fieldset->FLD('format', 'varchar(20)', 'caption=Формат,mandatory');
		$fieldset->FLD('time', 'time(suggestions=1 ден|2 дена|1 седмица|1 месец)', 'caption=Срок до,unit=след текущата дата');
		
		$fieldset->setOptions('format', array('' => '') + arr::make($this->formatSuggestions, TRUE));
	}
	
	
	/**
	 * Връща автоматичния партиден номер според класа
	 * 
	 * @param mixed $documentClass - класа за който ще връщаме партидата
	 * @param int $id              - ид на документа за който ще връщаме партидата
	 * @param int $storeId         - склад
	 * @param date|NULL $date      - дата
	 * @return mixed $value        - автоматичния партиден номер, ако може да се генерира
	 */
	function getAutoValue($documentClass, $id, $storeId, $date = NULL)
	{
		$date = dt::today();
		if(isset($this->rec->time)){
			$date = dt::addSecs($this->rec->time, $date);
			$date = dt::verbal2mysql($date, FALSE);
		}
		$date = dt::mysql2verbal($date, $this->rec->format);
		
		return $date;
	}
	
	
	/**
	 * Проверява дали стойността е невалидна
	 *
	 * @param string $value - стойноста, която ще проверяваме
	 * @param quantity $quantity - количеството
	 * @param string &$msg - текста на грешката ако има
	 * @return boolean - валиден ли е кода на партидата според дефиницията или не
	 */
	public function isValid($value, $quantity, &$msg)
	{
		// Ако артикула вече има партидаза този артикул с тази стойност, се приема че е валидна
		if(batch_Items::fetchField(array("#productId = {$this->rec->productId} AND #batch = '[#1#]'", $value))){
			return TRUE;
		}
		
		// Карта
		$map = array();
		$map['m'] = "(?'month'[0-9]{2})";
		$map['d'] = "(?'day'[0-9]{2})";
		$map['y'] = "(?'yearShort'[0-9]{2})";
		$map['Y'] = "(?'year'[0-9]{4})";
		
		// Генерираме регулярен израз спрямо картата
		$expr = $this->rec->format;
		$expr = preg_quote($expr, '/');
		$expr = strtr($expr, $map);
		
		// Проверяваме дали датата отговаря на формата
		if(!preg_match("/^{$expr}$/", $value, $matches)){
			$msg = "|Партидата трябва да е във формат за дата|* <b>{$this->rec->format}</b>";
			return FALSE;
		}
		
		// Ако годината е кратка, правим я дълга
		if(isset($matches['yearShort'])){
			$matches['year'] = "20{$matches['yearShort']}";
		}
		
		// Проверяваме дали датата е възможна
		if(!checkdate($matches['month'], $matches['day'], $matches['year'])){
			$msg = "|Партидата трябва да е във формат за дата|* <b>{$this->rec->format}</b>";
			return FALSE;
		}
		
		// Връщаме истина, ако не са се получили грешки
		return TRUE;
	}
	
	
	/**
	 * Кой може да избере драйвера
	 */
	public function toVerbal($value)
	{
		$mysqlValue = dt::verbal2mysql($value, FALSE);
		$today = dt::today();
		
		// Ако партидата е изтекла оцветяваме я в червено
		if($mysqlValue < $today){
			$valueHint = ht::createHint($value, 'Крайният срок на партидата е изтекъл', 'warning');
			$value = new core_ET("<span class='red'>[#value#]</span>");
			$value->replace($valueHint, 'value');
		} else {
			
			// Ако има срок на годност
			if(isset($this->rec->time)){
				$startDate = dt::addSecs(-1 * $this->rec->time, $mysqlValue);
				$startDate = dt::verbal2mysql($startDate, FALSE);
				$startTime = strtotime($startDate);
				$endTime = strtotime($mysqlValue);
				$currentTime = strtotime($today);
				
				// Намираме колко сме близо до изтичането на партидата
				$percent = ($currentTime - $startTime) / ($endTime - $startTime);
				$percent = round($percent, 2);
				
				// Оцветяваме я в оранжево ако сме наближили края и
				if($percent > 0){
					$confPercent = core_Packs::getConfigValue('batch', 'BATCH_EXPIRYDATE_PERCENT');
					$percentToCompare = 1 - $confPercent;
					
					if($percent >= $percentToCompare){
						$valueHint = ht::createHint($value, 'Партидата ще изтече скоро', 'warning');
						$value = new core_ET("<span style='color:orange'>[#value#]</span>");
						$value->replace($valueHint, 'value');
					}
				}
			}
		}
		
		return cls::get('type_Html')->toVerbal($value);
	}
	
	
	/**
	 * Връща масив с опции за лист филтъра на партидите
	 *
	 * @return array - масив с опции
	 * 		[ключ_на_филтъра] => [име_на_филтъра]
	 */
	public function getListFilterOptions()
	{
		return array('expiration' => 'Срок на годност');
	}
	
	
	/**
	 * Добавя филтър към заявката към  batch_Items възоснова на избраната опция (@see getListFilterOptions)
	 *
	 * @param core_Query $query - заявка към batch_Items
	 * @param varchar $value -стойност на филтъра
	 * @param string $featureCaption - Заглавие на колоната на филтъра
	 * @return void
	 */
	public function filterItemsQuery(core_Query &$query, $value, &$featureCaption)
	{
		expect($query->mvc instanceof batch_Items, 'Невалидна заявка');
		$options = $this->getListFilterOptions();
		expect(array_key_exists($value, $options), "Няма такава опция|* '{$value}'");
		
		// Ако е избран филтър за срок на годност
		if($value == 'expiration'){
			
			// Намиране на партидите със свойство 'срок на годност'
			$featQuery = batch_Features::getQuery();
			$featQuery->where("#classId = {$this->getClassId()}");
			$featQuery->orderBy('value', 'ASC');
			$itemsIds = arr::extractValuesFromArray($featQuery->fetchAll(), 'itemId');
			$query->in('id', $itemsIds);
			
			// Ако има ще бъдат подредени по стойноста на срока им
			if(is_array($itemsIds)){
				$count = 1;
				$case = "CASE #id WHEN ";
				foreach ($itemsIds as $id){
					$when = ($count == 1) ? '' : ' WHEN ';
					$case .= "{$when}{$id} THEN {$count}";
					$count++;
				}
				$case .= " END";
				$query->XPR('orderById', 'int', "({$case})");
				$query->orderBy('orderById');
			}
			
			$query->EXT('featureId', 'batch_Features', 'externalName=id,remoteKey=itemId');
		}
		
		$featureCaption = 'Срок на годност';
	}
	
	
	/**
	 * Добавя записа
	 *
	 * @param stdClass $rec
	 * @return void
	 */
	public function setRec($rec)
	{
		$this->fieldPlaceholder = $rec->format;
		$this->rec = $rec;
	}
}