<?php


/**
 * Тип за параметър 'Избор'
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Избор
 */
class cond_type_Enum extends cond_type_Proto
{
	
	
	/**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
		$fieldset->FLD('options', 'text', 'caption=Конкретизиране->Опции,before=default,mandatory');
	}
	
	
	/**
	 * Връща инстанция на типа
	 *
	 * @param int $paramId - ид на параметър
	 * @return core_Type - готовия тип
	 */
	public function getType($rec)
	{
		$Type = cls::get('type_Enum');
        $Type->options = static::text2options($rec->options);
		
		return $Type;
	}
}