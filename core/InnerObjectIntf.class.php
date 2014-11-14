<?php

/**
 * Интерфейс за създаване драйвери за вграждане в други обекти
 *
 *
 * @category  bgerp
 * @package   core
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class core_InnerObjectIntf
{
	
	
	/**
	 * Инстанция на класа имплементиращ интерфейса
	 */
	public $class;
	
	
	/**
	 * Добавя полетата на вътрешния обект
	 * 
	 * @param core_Fieldset $fieldset
	 */
	public function addEmbeddedFields(core_Fieldset &$fieldset)
	{
		return $this->class->addEmbeddedFields($fieldset);
	}
	
	
	/**
	 * Подготвя формата за въвеждане на данни за вътрешния обект
	 * 
	 * @param core_Form $form
	 */
	public function prepareEmbeddedForm(core_Form &$form)
	{
		return $this->class->prepareEmbeddedForm($form);
	}
	
	
	/**
	 * Проверява въведените данни
	 * 
	 * @param core_Form $form
	 */
	public function checkEmbeddedForm(core_Form &$form)
	{
		return $this->class->checkEmbeddedForm($form);
	}
	
	
	/**
	 * Подготвя вътрешното състояние, на база въведените данни
	 * 
	 * @param core_Form $innerForm
	 */
	public function prepareInnerState()
	{
		return $this->class->prepareInnerState();
	}
	
	
	/**
	 * Подготвя данните необходими за показването на вградения обект
	 *
	 * @param core_Form $innerForm
	 * @param stdClass $innerState
	 */
	public function prepareEmbeddedData()
	{
		return $this->class->prepareEmbeddedData();
	}
	
	
	/**
	 * Рендира вградения обект
	 * 
	 * @param stdClass $data
	 */
	public function renderEmbeddedData($data)
	{
		return $this->class->renderEmbeddedData($data);
	}
	
	
	/**
	 * Можели вградения обект да се избере
	 */
	public function canSelectInnerObject($userId = NULL)
	{
		return $this->class->canSelectInnerObject($userId = NULL);
	}
	
	
	/**
	 * Променя ключовите думи
	 * 
	 * @param string $searchKeywords
	 */
	public function alterSearchKeywords(&$searchKeywords)
	{
		return $this->class->alterSearchKeywords($searchKeywords);
	}
}