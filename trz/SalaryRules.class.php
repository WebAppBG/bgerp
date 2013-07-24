<?php



/**
 * Мениджър на заплати
 *
 *
 * @category  bgerp
 * @package   trz
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Заплати
 */
class trz_SalaryRules extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = ' Правила';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Created, plg_Rejected,  plg_SaveAndNew, 
                    trz_Wrapper, trz_SalaryWrapper';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,trz';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,trz';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,trz';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,trz';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,trz';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,trz';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,trz';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт, personId, departmentId, positionId, conditionExpr, amountExpr';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('personId',    'key(mvc=crm_Persons,select=name,group=employees, allowEmpty=true)', 'caption=Лице,width=100%');
    	$this->FLD('departmentId',    'key(mvc=hr_Departments, select=name, allowEmpty=true)', 'caption=Отдел,width=100%');
    	$this->FLD('positionId',    'key(mvc=hr_Positions, select=name, allowEmpty=true)', 'caption=Длъжност,width=100%');
    	$this->FLD('conditionExpr',    'text', 'caption=Условие,mandatory,width=100%');
    	$this->FLD('amountExpr',    'text', 'caption=Сума,mandatory,width=100%');
    	
    }
    
    static public function act_Test()
    {
    	bp(self::calculateConditionExpr());
    }

    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    static function on_AfterInputEditForm($mvc, &$form)
    {
        
        if ($form->isSubmitted()) {
                        
            // Ако не е цяло число
            if ($form->rec->personId && $form->rec->departmentId && $form->rec->positionId) {
                
            	$departmentId = hr_EmployeeContracts::fetchField("#personId = '{$form->rec->personId}'", 'departmentId');
    	    	$positionId = hr_EmployeeContracts::fetchField("#personId = '{$form->rec->personId}'", 'positionId');
    	    	
    	    	if($form->rec->departmentId != $departmentId || $form->rec->positionId != $positionId){
	                // Сетваме грешката
	                $form->setError('departmentId, positionId', 'Лицето не е в този отдел или не е на тази длъжност');
    	    	}
            }
            
            if(!$form->gotErrors()){
	            // Ако са въведени повече от допустимите полета полета: Лице, Отдел, Длъжност
	            if ($form->rec->personId && ($form->rec->departmentId || $form->rec->positionId)) {
	                
	                // Сетваме предупреждение
	                $form->setWarning('personId, departmentId, positionId', 'Въведени са повече от допустимите полета: Лице, Отдел или Длъжност');
	            }
            }
        }
    }
    
    
    /**
     * 
     */
    static public function calculateConditionExpr()
    {
    	// Заявка по договорите
        $query = hr_EmployeeContracts::getQuery();
    	     	 
    	while($rec = $query->fetch()){
    	
    		$contracts[] = $rec;
    	}
    	
    	// Заявка по правилата
    	$querySelf = self::getQuery();
    	    	 
    	while($recSelf = $querySelf->fetch()){
    	
    		$rules[] = $recSelf;
    	}
    	
    	// тримерен масив [договор][лице][правило]
    	$result = array();
    	
    	foreach($contracts as $contract){
    		$person = $contract->personId;
    		$department = $contract->departmentId;
    		$position = $contract->positionId;
    		
    		foreach($rules as $rule){
    			$personRule = $rule->personId;
    			$departmentRule = $rule->departmentId;
    			$positionRule = $rule->positionId;
    			
    			if(($person == $personRule || $personRule == NULL) &&  
    			    ($department == $departmentRule || $departmentRule == NULL) && 
    			    ($position == $positionRule || $positionRule == NULL)){
    				$result[$contract->id][$person][$rule->id] = "Правилото се изпълнява";
    			} else {
    				$result[$contract->id][$person][$rule->id] = "Правилото не се изпълнява";
    			}
    		}
    	}
    	
    	return $result;
    }

}