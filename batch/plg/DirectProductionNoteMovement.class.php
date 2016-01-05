<?php



/**
 * Клас 'batch_plg_DirectProductionNoteMovement' - За генериране на партидни движения на протокола за бързо производство
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class batch_plg_DirectProductionNoteMovement extends core_Plugin
{
	
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$data->form->setField('batch', 'input');
	}
	
	
	/**
	 * Извиква се след въвеждането на данните от Request във формата ($form->rec)
	 *
	 * @param core_Mvc $mvc
	 * @param core_Form $form
	 */
	public static function on_AfterInputEditForm($mvc, &$form)
	{
		$rec = &$form->rec;
		
		if(isset($rec->productId)){
			$BatchClass = batch_Defs::getBatchDef($rec->productId);
			if(is_object($BatchClass)){
				$form->setFieldType('batch', $BatchClass->getBatchClassType());
				$form->setDefault('batch', $BatchClass->getAutoValue($mvc->Master, $rec->{$mvc->masterKey}));
			}
		}
		
		if($form->isSubmitted()){
			if(is_object($BatchClass)){
				$measureId = cat_Products::fetchField($rec->productId, 'measureId');
				if(!$BatchClass->isValid($rec->batch, $measureId, $rec->quantity, $msg)){
					$form->setError('batch', $msg);
				}
			}
		}
	}
}