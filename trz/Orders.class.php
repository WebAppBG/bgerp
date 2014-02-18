<?php



/**
 * Мениджър на заповеди за отпуски
 *
 *
 * @category  bgerp
 * @package   trz
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Заповеди за отпуски
 */
class trz_Orders extends core_Master
{
    
	
	/**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf';
    
    /**
     * Заглавие
     */
    var $title = 'Заповеди';
    
     /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Заповед за отпуск";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, trz_Wrapper, trz_LeavesWrapper, 
    				 doc_DocumentPlg, acc_plg_DocumentSummary, doc_ActivatePlg,
    				 plg_Printing, doc_plg_BusinessDoc,bgerp_plg_Blank';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,personId, leaveFrom, leaveTo, note, useDaysFromYear, isPaid, amount';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    //var $searchFields = 'description';

    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Поле в което да се показва иконата за единичен изглед
     */
    var $rowToolsSingleField = 'personId';
    
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo, trz';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,trz';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,trz';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo, trz';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo, trz';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo, trz';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo, trz';
  
    /**
     * За плъгина acc_plg_DocumentSummary
     */
    var $filterFieldDateFrom = 'leaveFrom';
    var $filterFieldDateTo = 'leaveTo';
    
    var $canOrders = 'ceo, trz';
    /**
     * Икона за единичния изглед
     */
    //var $singleIcon = 'img/16/money.png';
    
    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'trz/tpl/SingleLayoutOrders.shtml';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Ord";
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "5.3|Човешки ресурси"; 

    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('personId', 'key(mvc=crm_Persons,select=name,group=employees,allowEmpty=TRUE)', 'caption=Служител');
    	$this->FLD('leaveFrom', 'date', 'caption=Считано->От, mandatory');
    	$this->FLD('leaveTo', 'date', 'caption=Считано->До, mandatory');
    	$this->FLD('leaveDays', 'int', 'caption=Считано->Дни, input=none');
    	$this->FLD('note', 'richtext(rows=5, bucket=Notes)', 'caption=Информация->Бележки');
    	$this->FLD('useDaysFromYear', 'int(nowYest, nowYear-1)', 'caption=Информация->Ползване от,unit=година');
    	$this->FLD('isPaid', 'enum(paid=платен, unpaid=неплатен)', 'caption=Вид,maxRadio=2,columns=2,notNull,value=paid');
    	$this->FLD('amount', 'double', 'caption=Начисления');
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    static function on_BeforeSave($mvc, &$id, $rec)
    {
        if($rec->leaveFrom &&  $rec->leaveTo){
        	$state = hr_EmployeeContracts::getQuery();
	        $state->where("#personId='{$rec->personId}'");
	        
	        if($employeeContractDetails = $state->fetch()){
	           
	        	$employeeContract = $employeeContractDetails->id;
	        	$department = $employeeContractDetails->departmentId;
	        	
	        	$schedule = hr_EmployeeContracts::getWorkingSchedule($employeeContract);
	        	if($schedule){
	        		$days = hr_WorkingCycles::calcLeaveDaysBySchedule($schedule, $department, $rec->leaveFrom, $rec->leaveTo);
	        	} else {
	        		$days = cal_Calendar::calcLeaveDays($rec->leaveFrom, $rec->leaveTo);
	        	}
	        }else{
        	
	    		$days = cal_Calendar::calcLeaveDays($rec->leaveFrom, $rec->leaveTo);
	        }
	    	$rec->leaveDays = $days->workDays;
        }

    }

    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
    	// Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields .= ',personId, isPaid';
        
        $data->listFilter->input('personId, isPaid', 'silent');
        
    	if($filterRec = $data->listFilter->rec){
        	if($filterRec->personId){
        		$data->query->where(array("#personId = '[#1#]'", $filterRec->personId));
        	}
    		if($filterRec->isPaid){
        		$data->query->where(array("#isPaid = '[#1#]'", $filterRec->isPaid));
        	}
    	}
    }

    
    /**
     * Подготовка на формата за добавяне/редактиране
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
    	//bp($data->form->fields[personId]);
    	$nowYear = dt::mysql2Verbal(dt::now(),'Y');
    	for($i = 0; $i < 5; $i++){
    		$years[] = $nowYear - $i;
    	}
    	$data->form->setSuggestions('useDaysFromYear', $years);
    	$data->form->setDefault('useDaysFromYear', $years[0]);

    	if($data->form->rec->originId){
			// Ако напомнянето е по  документ задача намираме кой е той
    		$doc = doc_Containers::getDocument($data->form->rec->originId);
    		$class = $doc->className;
    		$dId = $doc->that;
    		$rec = $class::fetch($dId);
    		
    		// Извличаме каквато информация можем от оригиналния документ
    		
    		$data->form->setDefault('personId', $rec->personId);
    		$data->form->setDefault('leaveFrom', $rec->leaveFrom);
    		$data->form->setDefault('leaveTo', $rec->leaveTo);
    		$data->form->setDefault('leaveDays', $rec->leaveDays);
    		$data->form->setDefault('note', $rec->note);
    		$data->form->setDefault('useDaysFromYear', $rec->useDaysFromYear);
    		$data->form->setDefault('isPaid', $rec->paid);
    

		}
		
        $rec = $data->form->rec;
        if($rec->folderId){
	        $data->form->setDefault('personId', doc_Folders::fetchCoverId($rec->folderId));
	        $data->form->setReadonly('personId');
        }
    }
      
    
    /**
     * Проверява и допълва въведените данни от 'edit' формата
     */
    function on_AfterInputEditForm($mvc, $form)
    {
    	$rec = $form->rec;

    }
    
    
	/**
     * След подготовка на тулбара на единичен изглед.
     * 
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        if(doc_Threads::haveRightFor('add', $data->сrec->threadId) == FALSE){
	    	$data->toolbar->removeBtn('Коментар');
	    }
        
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка 
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        $coverClass = doc_Folders::fetchCoverClassName($folderId);
        
        if ('crm_Persons' != $coverClass) {
        	return FALSE;
        }
        
        $personId = doc_Folders::fetchCoverId($folderId);
        
        $personRec = crm_Persons::fetch($personId);
        $emplGroupId = crm_Groups::getIdFromSysId('employees');
        
        return keylist::isIn($emplGroupId, $personRec->groupList);
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentIntf
     *
     * @param int $id
     * @return stdClass $row
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $row = new stdClass();
        
        //Заглавие
        $row->title = "Заповед за отпуск  №{$rec->id}";
        
        //Създателя
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        //Състояние
        $row->state = $rec->state;
        
        //id на създателя
        $row->authorId = $rec->createdBy;
        
        $row->recTitle = $rec->title;
        
        return $row;
    }
    

    /**
     * В кои корици може да се вкарва документа
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getAllowedFolders()
    {
    	return array('crm_PersonAccRegIntf');
    }
    
    
	/**
     * Преди да се подготвят опциите на кориците, ако
     */
    function getCoverOptions($coverClass)
    {
    	
    	if($coverClass instanceof crm_Persons){
    		
    		// Искаме да филтрираме само групата "Служители"
    		$sysId = crm_Groups::getIdFromSysId('employees');
    	
    		return $coverClass::makeArray4Select(NULL, "#state != 'rejected' AND #groupList LIKE '%|{$sysId}|%'");
    	}
    }
}
