<?php


/**
 * Клас 'doc_plg_TplManagerDetail' - помощен плъгин прикачащ се от doc_plg_TplManager към
 * всички детайли на модела. 
 * Той скрива определени полета от списъчния изглед на детайла, които са определени от шаблона
 *
 * За да скрива ненужните полета от формата на детайла, плъгина трябва да е прикачен от кода
 *
 * @category  bgerp
 * @package   doc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_plg_TplManagerDetail extends core_Plugin
{
    
    
	/**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal(core_Mvc $mvc, &$row, &$rec)
    {
    	// Ако няма шаблон, за шаблон се приема първия такъв за модела
    	if($rec->id){
    		$template = $mvc->Master->getTemplate($rec->{$mvc->masterKey});
    		$rec->tplLang = doc_TplManager::fetchField($template, 'lang');
    	}
    }
    
    
    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    static function on_AfterPrepareListFields(core_Mvc $mvc, &$data)
    {
    	$masterRec = $data->masterData->rec;
    	$toggleFields = doc_TplManager::fetchField($masterRec->template, 'toggleFields');
    		
    	if(count($toggleFields) && $toggleFields[$mvc->className] !== NULL){
    			
    		// Полетата които трябва да се показват
    		$fields = arr::make($toggleFields[$mvc->className]);
    			
    		// Всички полета, които могат да се скриват/показват
    		$toggleFields = arr::make($mvc->toggleFields);
    		$intersect = array_intersect_key($data->listFields, $toggleFields);
    			
    		foreach ($intersect as $k => $v){
    				
    			// За всяко от опционалните полета: ако не е избран да се показва, се маха
    			if(!in_array($k, $fields)){
    				unset($data->listFields[$k]);
    			}
    		}
    	}
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    function on_BeforeRenderListToolbar(core_Mvc $mvc, &$res, $data)
    {
    	// Маха се пушнатия език, за да може да се рендира тулбара нормално
    	core_Lg::pop();
    }
    
    
	/**
     * Извиква се преди рендирането на 'опаковката'
     */
    function on_AfterRenderListToolbar(core_Mvc $mvc, &$res, $data)
    {
    	// След рендиране на тулбара отново се пушва езика на шаблона
    	$lang = doc_TplManager::fetchField($data->masterData->rec->template, 'lang');
    	core_Lg::push($lang);
    }
    
    
    /**
     * След подготовка на записите
     */
    public static function on_AfterPrepareListRows($mvc, &$data)
    {
    	// Ако има скриптов клас за шаблона, подаваме му данните 
    	if($Script = doc_TplManager::getTplScriptClass($data->masterData->rec->template)){
    		$Script->modifyDetailData($mvc, $data);
    	}
    }
}