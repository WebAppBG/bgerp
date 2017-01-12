<?php


/**
 * Базов драйвер за вид партида 'varchar'
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title Символи(128)
 */
class batch_definitions_Varchar extends batch_definitions_Proto
{
	
	
	/**
	 * Име на полето за партида в документа
	 *
	 * @param string
	 */
	public $fieldCaption = 'lot';
	
	
	/**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
		$fieldset->FLD('length', 'int', 'caption=Дължина,placeholder=255');
	}
	
	
	/**
	 * Проверява дали стойността е невалидна
	 *
	 * @return core_Type - инстанция на тип
	 */
	public function getBatchClassType()
	{
		$string = !isset($this->rec->length) ? 'varchar' : "varchar({$this->rec->length})";
		$Type = core_Type::getByName($string);
	
		return $Type;
	}
}