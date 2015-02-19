<?php



/**
 * Плъгин за Регистрите, който им добавя възможност обекти от регистрите да влизат като пера
 * 
 * Ако е заден класов параметър 'autoList' след създаване, обекта се вкарва в тази номенклатура
 * След оттегляне, ако обекта е бил перо, то се затваря. Затворените но неизползвани пера се изтриват по разписание
 * След възстановяване ако обекта е бил перо, отваряме му перото
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_plg_Registry extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        $mvc->declareInterface('acc_RegisterIntf');
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    public static function on_AfterCreate($mvc, $rec)
    {
    	if (!empty($mvc->autoList)) {
        	
            // Автоматично добавяне към номенклатурата $autoList, след създаване на обекта
            expect($autoListId = acc_Lists::fetchField(array("#systemId = '[#1#]'", $mvc->autoList), 'id'));
            $lists = keylist::addKey('', $autoListId);
            acc_Lists::updateItem($mvc, $rec->id, $lists);
            
            if(haveRole('ceo,acc')){
            	$list = acc_Lists::fetchField("#systemId = '{$mvc->autoList}'", 'name');
            	core_Statuses::newStatus(tr("|Обекта е добавен в номенклатура|*: {$list}"));
            }
        }
    }
    
    
    /**
     * След запис
     */
    protected static function on_AfterSave($mvc, &$id, &$rec, $fieldList = NULL)
    {
    	$added = FALSE;
    	
    	// Ако е зададено да се добави в номенклатура при активиране
    	if(!empty($mvc->addToListOnActivation)){
    		if($rec->state == 'active'){
    		
    			// И вече не е перо, добавяме го в тази номенклатура
    			if(!acc_Items::fetchItem($mvc, $rec->id)){
    				
    				// Проверяваме дали записа може да се добави в номенклатура при активиране
    				if($mvc->canAddToListOnActivation($rec)){
    					
    					// Ако валутата е активна, добавя се като перо
    					$rec->lists = keylist::addKey($rec->lists, acc_Lists::fetchField(array("#systemId = '[#1#]'", $mvc->addToListOnActivation), 'id'));
    					acc_Lists::updateItem($mvc, $rec->id, $rec->lists);
    					
    					if(haveRole('ceo,acc')){
    						$list = acc_Lists::fetchField("#systemId = '{$mvc->addToListOnActivation}'", 'name');
    						core_Statuses::newStatus(tr("|Обекта е добавен в номенклатура|*: {$list}"));
    					}
    					
    					$added = TRUE;
    				}
    			}
    		} 
    	}
    	
    	// Ако обекта не е бил добавен като ново перо
    	if(!$added){
    		
    		// Ако е активно състоянието и обекта е перо
    		if($rec->state == 'active'){
    			
    			// Активираме перото, ако не е било активирано
    			if($itemRec = acc_Items::fetchItem($mvc, $rec->id)){
    				if($itemRec->state != 'active'){
    					acc_Lists::updateItem($mvc, $rec->id, $itemRec->lists);
    						
    					if(haveRole('ceo,acc')){
    						core_Statuses::newStatus(tr("|Активирано е перо|*: {$itemRec->title}"));
    					}
    				}
    			}
    		}
    	}
    	
    	// Ако обекта е затворен или оттеглен, затваряме перото му
    	if($rec->state == 'rejected' || $rec->state == 'closed'){
    		if($itemRec = acc_Items::fetchItem($mvc, $rec->id)){
    			acc_Lists::removeItem($mvc, $rec->id);
    			
    			if(haveRole('ceo,acc')){
    				core_Statuses::newStatus(tr("|Затворено е перо|*: {$itemRec->title}"));
    			}
    		}
    	}
    }
    
    
    /**
     * Метод по подразбиране дали обекта може да се добави в номенклатура при активиране
     */
    public static function on_AfterCanAddToListOnActivation($mvc, &$res, $rec)
    {
    	if(!$res){
    		$res = TRUE;
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
        if($res != 'no_one' && $action == 'delete' && isset($rec)){
            if(acc_Items::fetchItem($mvc->getClassId(), $rec->id)){
                
                // Не може да се изтрива ако обекта вече е перо
                $res = 'no_one';
            }
        }
    }
}
