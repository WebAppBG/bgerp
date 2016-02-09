<?php



/**
 * Планиране - опаковка
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
    	$this->TAB('planning_Jobs', 'Задания', 'ceo,planning');
    	$this->TAB('planning_ConsumptionNotes', 'Протоколи->Влагане', 'ceo,planning');
    	$this->TAB('planning_ReturnNotes', 'Протоколи->Връщане', 'ceo,planning');
    	$this->TAB('planning_DirectProductionNote', 'Протоколи->Производство', 'ceo,planning');
    	$this->TAB('planning_Tasks', 'Задачи', 'ceo,planning');
    	$this->TAB('planning_HumanResources', 'Ресурси->Човешки', 'ceo,planning');
    	$this->TAB('planning_AssetResources', 'Ресурси->Машини', 'ceo,planning');
    	
        $this->title = 'Планиране';
    }
}