<?php

/**
 * Помощен модел за лесна работа с баланс, в който участват само определени пера и сметки
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_ActiveShortBalance {
	
	
	/**
	 * Променлива в която ще се помни баланса
	 */
	private $balance = array();
	
	
	/**
	 * Конструктор на обекта
	 * 
	 * @param string $itemsAll - списък от ид-та на пера, които може да са на всяка позиция
	 * @param string $item1 - списък от ид-та на пера, поне едно от които може да е на първа позиция
	 * @param string $item2 - списък от ид-та на пера, поне едно от които може да е на втора позиция
	 * @param string $item3 - списък от ид-та на пера, поне едно от които може да е на трета позиция
	 */
	function __construct($itemsAll = NULL, $item1 = NULL, $item2 = NULL , $item3 = NULL)
	{
		// Тряба да има поне едно перо
		if($itemsAll || $item1 || $item2 || $item3){
			
			// Подготвяме заявката към базата данни
			$jQuery = acc_JournalDetails::getQuery();
			acc_JournalDetails::filterQuery($jQuery, NULL, dt::now(), NULL, $itemsAll, $item1, $item2, $item3);
			
			// Изчисляваме мини баланса
			$this->calcBalance($jQuery->fetchAll());
		}
	}
	
	
	/**
	 * Изчислява мини баланса
	 */
	private function calcBalance($recs)
	{
		if(count($recs)){
			
			// За всеки запис
			foreach ($recs as $rec){
				
				// За дебита и кредита
				foreach (array('debit', 'credit') as $type){
					$accId = $rec->{"{$type}AccId"};
					$item1 = $rec->{"{$type}Item1"};
					$item2 = $rec->{"{$type}Item2"};
					$item3 = $rec->{"{$type}Item3"};
					
					// За всяка уникална комбинация от сметка и пера, сумираме количествата и сумите
					$sign = ($type == 'debit') ? 1 : -1;
					$index = $accId . "|" . $item1 . "|" . $item2 . "|" . $item3;
					$b = &$this->balance[$index];
					
					$b['accountSysId'] = acc_Accounts::fetchField($accId, 'systemId');
					$b['ent1Id'] = $item1;
					$b['ent2Id'] = $item2;
					$b['ent3Id'] = $item3;
					$b['blQuantity'] += $rec->{"{$type}Quantity"} * $sign;
					$b['blAmount'] += $rec->amount * $sign;
				}
			}
		}
	}
	
	
	/**
	 * Връща крайното салдо на няколко сметки
	 * 
	 * @param mixxed $accs - масив от систем ид-та на сметка
	 * @return stdClass $res - масив с 'amount' - крайното салдо
	 */
	public function getAmount($accs)
	{
		$arr = arr::make($accs);
		expect(count($arr));
		
		$res = 0;
		foreach ($arr as $accSysId){
			foreach ($this->balance as $index => $b){
				if($b['accountSysId'] == $accSysId){
					$res += $b['blAmount'];
				}
			}
		}
		
		return $res;
	}
	
	
	/**
	 * Връща краткия баланс с посочените сметки
	 */
	public function getShortBalance($accs)
	{
		$arr = arr::make($accs);
		if(!count($arr)) return $this->balance;
		
		$newArr = array();
		foreach ($arr as $accSysId){
				
			foreach ($this->balance as $index => $b){
				if($b['accountSysId'] == $accSysId){
					$newArr[$index] = $b;
				}
			}
		}
		
		return $newArr;
	}
}