<?php


/**
 * Клас 'planning_TaskSerials' - Серийни номера по задачи за производство
 *
 * 
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_TaskSerials extends core_Manager
{
	
	
	/**
	 * Заглавие
	 */
	public $title = 'Серийни номера по задачи за производство';
	
	
	/**
	 * Кой може да го разглежда?
	 */
	public $canList = 'debug';
	
	
	/**
	 * Кой има право да променя?
	 */
	public $canWrite = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created';
	
	
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId,taskId,serial=С. номер,labelNo,domain,createdOn,createdBy';

    
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('serial', 'bigint', 'caption=Брояч,mandatory');
		$this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул,mandatory');
		$this->FLD('taskId', 'key(mvc=planning_Tasks,select=title)', 'caption=Задача,mandatory');
		$this->FLD('labelNo', 'int', 'caption=Номер на етикета,mandatory');
		$this->FLD('domain', 'enum(auto,labels)', 'caption=Домейн,mandatory,notNull,value=auto');
		
		$this->setDbUnique('serial');
		$this->setDbUnique('taskId,labelNo,domain');
	}
	
	
	/**
	 * Връща следващия сериен номер
	 * 
	 * @return string $serial
	 */
	public static function getNextSerial()
	{
		// Намираме последния въведен код
		$query = static::getQuery();
		$query->XPR('maxSerial', 'int', 'MAX(#serial)');
		$startCounter = $query->fetch()->maxSerial;
		if(!$startCounter){
			$startCounter = core_packs::getConfigValue('planning', 'PLANNING_TASK_SERIAL_COUNTER');
		};
		$serial = $startCounter;
		
		// Инкрементираме кода, докато достигнем свободен код
		$serial++;
		while(self::fetch("#serial = '{$serial}'")){
			$serial++;
		}
		
		return $serial;
	}
	
	
	/**
	 * Връща следващия сериен номер, автоинкрементиран
	 *
	 * @param int $taskId - ид на задача за прозиводство
	 * @return string $serial - сериен номер
	 */
	public static function forceAutoNumber($taskId, $productId)
	{
		$query = self::getQuery();
		$query->where("#domain = 'auto'");
		$query->XPR('maxLabelNo', 'int', 'MAX(#labelNo)');
		$labelNo = $query->fetch()->maxLabelNo;
		$labelNo++;
		
		$rec = (object)array('taskId'    => $taskId, 
							 'labelNo'   => $labelNo,
							 'domain'    => 'auto',
							 'productId' => $productId,
							 'serial'    => self::getNextSerial());
		
		self::save($rec);
		
		return $rec->serial;
	}
	
	
	/**
	 * Форсираме сериен номер
	 * 
	 * @param int $id - ид 
	 * @param number $labelNo - номер на етикета
	 * @return int - намерения сериен номер
	 */
	public static function force($taskId, $labelNo = 0, $productId)
	{
		if($rec = static::fetch(array("#taskId = [#1#] AND #labelNo = '[#2#]' AND #domain = 'labels'", $taskId, $labelNo))){
			
			return $rec->serial;
		}
		
		$rec = (object)array('taskId'    => $taskId, 
						     'labelNo'   => $labelNo, 
							 'domain'    => 'labels',
							 'productId' => $productId,
							 'serial'    => static::getNextSerial());
		
		static::save($rec);
		
		return $rec->serial;
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид.
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $row Това ще се покаже
	 * @param stdClass $rec Това е записа в машинно представяне
	 */
	public static function on_AfterRecToVerbal($mvc, &$row, $rec)
	{
		$row->taskId = planning_Tasks::getHyperlink($rec->taskId, TRUE);
		$row->productId = cat_Products::getHyperlink($rec->productId, TRUE);
		$row->ROW_ATTR['class'] = 'state-active';
	}
	


	/**
	 * Проверява дали даден сериен номер е допустим
	 * Допустими са само серийни номера генерирани от системата (автоматично или чрез разпечатване
	 * на етикети от задачата). Трябва серийния номер да отговаря на Артикула.
	 * Ако номера е за произведен артикул, той трябва да е генериран от същата задача
	 * Ако влагаме то номера трябва да е генериран от задача към същото задание
	 * 
	 * 
	 * @param bigint $serial       - сериен номер
	 * @param int $productId       - ид на артикул, на който добавяме номера
	 * @param int $taskId          - задача към която се опитваме да добавим номер в прогреса
	 * @param product|input $type  - дали е за производим артикул или е за вложим/отпадък
	 * @return FALSE|string $error - FALSE ако номера е допустим, или текст с какъв е проблема
	 */
	public static function isSerialinValid($serial, $productId, $taskId, $type)
	{
		// Трябва да има сериен номер
		expect($serial);
		$error = '';
		
		// Проверяваме имали въобще такъв сериен номер в системата
		$serialRec = self::fetch(array("#serial = '[#1#]'", $serial));
		
		// Ако няма връщаме грешката
		if(!$serialRec){
			$error = 'Несъществуващ сериен номер';
		} else {
			
			// Ако има сериен номер, проверяваме дали е за същия артикул
			if($serialRec->productId != $productId){
				
				// Ако не е връщаме грешката
				$error = "Въведения сериен номер е за друг артикул";
				$error .= "|* <b>" . cat_Products::getHyperlink($serialRec->productId, TRUE) . "</b>";
			} else {
				// Ако серийния номер е за същия артикул
				
				// И произвеждаме
				if($type == 'product'){
					
					// То серийния номер на производимия артикул трябва да е по същата задача
					// Ако е по друга сетваме подходяща грешка
					if($serialRec->taskId != $taskId){
						$error = "Въведения сериен номер е по друга задача";
						$error .= "|* " . planning_Tasks::getLink($serialRec->taskId, 0);
					}
				} else {
					// Ако влагаме
					
					// намираме заданията по които са породени задачата от номера и текущата задача
					$productTaskOriginId = planning_Tasks::fetchField($serialRec->taskId, 'originId');
					$taskOriginId = planning_Tasks::fetchField($taskId, 'originId');
					
					// Двете задачи трябва да са към едно и съще задание
					// Не можем да влагаме заготовка която е произведена със задача по друго задание
					if($taskOriginId != $productTaskOriginId){
						$error = "Въведения сериен номер е по друга задача";
						$error .= "|* " . planning_Tasks::getLink($serialRec->taskId, 0);
					}
				}
			}
		}
		
		// Ако не е намерена грешка ще върнем FALSE
		if($error == ''){
			$error = FALSE;
		}
		
		// Връщаме резултата
		return $error;
	}
}