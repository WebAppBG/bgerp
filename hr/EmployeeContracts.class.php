<?php 


/**
 * Смени
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hr_EmployeeContracts extends core_Master
{
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'acc_RegisterIntf,hr_ContractAccRegIntf';
    
    
    /**
     * Заглавие
     */
    var $title = "Трудови Договори";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Трудов договор";
    
    
    /**
     * @todo Чака за документация...
     */
    var $pageMenu = "Персонал";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, hr_Wrapper, plg_Printing,
                     acc_plg_Registry, doc_DocumentPlg, plg_Search';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    var $onlyFirstInThread = TRUE;
    
    
    /**
     * Клас за елемента на обграждащия <div>
     */
    var $cssClass = 'document';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,hr';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'admin,hr';
    
    
    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/report_user.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Td";
    
    /**
     * Поле за търсене
     */
    var $searchFields = 'typeId, managerId, personId, specialty, 
                         departmentId, positionId, startFrom, 
                         endOn, folderId, threadId, containerId';
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "5.1|Човешки ресурси";
    
    
    var $listFields = 'id,typeId,personId=Имена,departmentId,positionId,startFrom,endOn';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'typeId';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('typeId', 'key(mvc=hr_ContractTypes,select=name)', "caption=Тип");
        
        $this->FLD('managerId', 'key(mvc=crm_Persons,select=name)', 'caption=Управител, mandatory');
        
        // Служител
        $this->FLD('personId', 'key(mvc=crm_Persons,select=name,group=employees)', 'caption=Служител->Имена, mandatory,width=100%');
        $this->FLD('education', 'varchar', 'caption=Служител->Образование,width=100%');
        $this->FLD('specialty', 'varchar', 'caption=Служител->Специалност,width=100%');
        $this->FLD('diplomId', 'varchar', 'caption=Служител->Диплома №,width=100%');
        $this->FLD('diplomIssuer', 'varchar', 'caption=Служител->Издадена от,width=100%');
        $this->FLD('lengthOfService', 'int', 'caption=Служител->Трудов стаж,unit=г.');
        
        // Работа
        $this->FLD('departmentId', 'key(mvc=hr_Departments,select=name)', 'caption=Работа->Отдел, mandatory');
        //$this->FLD('shiftId', 'key(mvc=hr_Shifts,select=name)', 'caption=Работа->Смяна, mandatory');
        $this->FLD('positionId', 'key(mvc=hr_Positions,select=name)', 'caption=Работа->Длъжност, mandatory,oldField=possitionId');
        
        // УСЛОВИЯ
        $this->FLD('startFrom', 'date(format=d.m.Y)', "caption=Условия->Начало,mandatory");
        $this->FLD('endOn', 'date(format=d.m.Y)', "caption=Условия->Край");
        $this->FLD('term', 'int', "caption=Условия->Срок,unit=месеца");
        $this->FLD('annualLeave', 'int', "caption=Условия->Годишен отпуск,unit=дни");
        $this->FLD('notice', 'int', "caption=Условия->Предизвестие,unit=дни");
        $this->FLD('probation', 'int', "caption=Условия->Изпитателен срок,unit=месеца");
        $this->FLD('descriptions', 'richtext(bucket=humanResources)', 'caption=Условия->Допълнителни');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_AfterPrepareEditForm($mvc, $data)
    {
    	$conf = core_Packs::getConfig('crm');
    	
        $pQuery = crm_Persons::getQuery();
        
        cls::load('crm_Companies');
        
        while($pRec = $pQuery->fetch("#buzCompanyId = " . $conf->BGERP_OWN_COMPANY_ID)) {
            $options[$pRec->id] = crm_Persons::getVerbal($pRec, 'name');
        }
        
        $data->form->setOptions('managerId', $options);
        
    	$rec = $data->form->rec;
        
        $coverClass = doc_Folders::fetchCoverClassName($rec->folderId);
        
        if ('crm_Persons' == $coverClass) {
        	$data->form->setDefault('personId', doc_Folders::fetchCoverId($rec->folderId));
	        $data->form->setReadonly('personId');
        }
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->personId = ht::createLink($row->personId, array('crm_Persons', 'Single', $rec->personId));
        
        $row->positionId = ht::createLink($row->positionId, array('hr_Positions', 'Single', $rec->positionId));
        
        $row->departmentId = ht::createLink($row->departmentId, array('hr_Departments', 'Single', $rec->departmentId));
        
        //$row->shiftId = ht::createLink($row->shiftId, array('hr_Shifts', 'Single', $rec->shiftId));
    }
    
    
    /**
     * Подготвя иконата за единичния изглед
     */
    static function on_AfterPrepareSingle($mvc, &$res, &$data)
    {
    	$conf = core_Packs::getConfig('crm');
    	
        $row = $data->row;
        
        $rec = $data->rec;
        
        $row->script = hr_ContractTypes::fetchField($rec->typeId, 'script');
        
        $row->num = $data->rec->id;
        
        $row->employeeRec         = crm_Persons::fetch($rec->personId);
        $row->employeeRec->idCard = crm_ext_IdCards::fetch("#personId = {$rec->personId}");

        if(!$row->employeeRec->egn) {  
            unset($row->employeeRec->egn);
        }


        
        $row->employerRec = crm_Companies::fetch($conf->BGERP_OWN_COMPANY_ID);
        
        $row->managerRec = crm_Persons::fetch($rec->managerId);
        $row->managerRec->idCard = crm_ext_IdCards::fetch("#personId = {$rec->managerId}");
        $row->employersRec = crm_ext_CourtReg::fetch("#companyId = {$row->employerRec->id}");

        if(!$row->managerRec->egn) {
            unset($row->managerRec->egn);
        }

        $row->positionRec = hr_Positions::fetch($rec->positionId);
        

        $res = $data;
    }
  
    
    /**
     * Render single
     *
     * @param core_Mvc $mvc
     * @param core_Et $tpl
     * @param stdClass $data
     */
    static function on_BeforeRenderSingle($mvc, &$res, $data)
    {
        $row = $data->row;
        
        $lsTpl = cls::get('legalscript_Engine', array('script' => $row->script));
        
        unset($row->script);

        $contract = $lsTpl->render($row);

        $res = new ET("[#toolbar#]
        <div class='document'>[#contract#]</div> <div style='clear:both;'></div>
        
        ");
        
        $res->replace($contract, 'contract');
        
        $res->replace($mvc->renderSingleToolbar($data), 'toolbar');
        
        return FALSE;
    }
    
    
    /**
     * Връща заглавието и мярката на перото за продукта
     *
     * Част от интерфейса: intf_Register
     */
    function getItemRec($objectId)
    {
        $result = NULL;
        
        if ($rec = self::fetch($objectId)) {
            $result = (object)array(
                'title' => $this->getVerbal($rec, 'personId') . " [" . $this->getVerbal($rec, 'startFrom') . ']',
                'num' => $rec->id,
                'features' => 'foobar' // @todo!
            );
        }
        
        return $result;
    }
    
    static function act_Test()
    {
    	$id = 2;
    	bp(self::getWorkingSchedule($id));
    }
    
    static public function getWorkingSchedule($id)
    {
    	$departmentId = self::fetchField($id, 'departmentId');
    	
    	$schedule = hr_Departments::fetchField($departmentId, 'schedule');
    	
    	return $schedule;
    }

    
    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     * @param int $objectId
     */
    static function itemInUse($objectId)
    {
        // @todo!
    }
    
    /****************************************************************************************
     *                                                                                      *
     *  ИМПЛЕМЕНТАЦИЯ НА @link doc_DocumentIntf                                             *
     *                                                                                      *
     ****************************************************************************************/
    
    
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
     * Интерфейсен метод на doc_DocumentInterface
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $row = new stdClass();
        $row->title = tr('Трудов договор на|* ') . $this->getVerbal($rec, 'personId');
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
        $row->recTitle = $row->title;
        
        return $row;
    }
 
}