<?php



/**
 * Клас 'blast_Lists' - Списъци за масово разпращане
 *
 *
 * @category  bgerp
 * @package   blast
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Списъци с контакти
 */
class blast_Lists extends core_Master
{
	
    /**
     * Име на папката по подразбиране при създаване на нови документи от този тип.
     * Ако стойноста е 'FALSE', нови документи от този тип се създават в основната папка на потребителя
     */
    var $defaultFolder = 'Списъци за разпращане';
	
    
    /**
     * Полета, които ще се клонират
     */
    var $cloneFields = 'title, keyField, fields';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'blast_Wrapper,plg_RowTools,doc_DocumentPlg, plg_Search';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    var $onlyFirstInThread = TRUE;
    
    
    /**
     * Заглавие
     */
    var $title = "Списъци за изпращане на циркулярни имейли, писма, SMS-и, факсове и др.";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = 'Списък с контакти';
    
    
    /**
     * Кой може да чете?
     */
    var $canRead = 'blast,ceo,admin';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'blast,ceo,admin';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'blast,ceo,admin';

	    
    /**
     * Кой може да го възстанови?
     */
    var $canRestore = 'blast,ceo,admin';
        
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'blast,ceo,admin';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'blast,ceo,admin';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'title';
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'blast_ListDetails';
    
    
    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/application_view_list.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = 'Bls';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'blast/tpl/SingleLayoutLists.shtml';
    
    /**
     * Поле за търсене
     */
    var $searchFields = 'title, keyField, contactsCnt, folderId, threadId, containerId ';
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "2.1|Циркулярни";
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Информация за папката
        $this->FLD('title' , 'varchar', 'caption=Заглавие,width=100%,mandatory');
        $this->FLD('keyField', 'enum(email=Имейл,mobile=Мобилен,fax=Факс,names=Лице,company=Фирма,uniqId=№)', 'caption=Ключ,width=100%,mandatory,hint=Kлючовото поле за списъка');
        $this->FLD('fields', 'text', 'caption=Полета,width=100%,mandatory,hint=Напишете името на всяко поле на отделен ред,column=none');
        $this->FNC('allFields', 'text', 'column=none,input=none');
        
        $this->FLD('contactsCnt', 'int', 'caption=Записи,input=none');
        
        $this->setDbUnique('title');
    }

    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param int $folderId - id на папката
     * @return boolean
     */
    public static function canAddToFolder($folderId)
    {
        // Името на класа
        $coverClassName = strtolower(doc_Folders::fetchCoverClassName($folderId));

        // Ако не е папка проект или контрагент, не може да се добави
        if (($coverClassName != 'doc_unsortedfolders') && 
            ($coverClassName != 'crm_persons') &&
            ($coverClassName != 'crm_groups') &&
            ($coverClassName != 'crm_companies')) return FALSE;
    }
    
    
    /**
     * Прибавя ключовото поле към другите за да получи всичко
     */
    static function on_CalcAllFields($mvc, $rec)
    {
        $rec->allFields = $rec->keyField . '=' . $mvc->fields['keyField']->type->options[$rec->keyField] . "\n" . $mvc->clearFields($rec->fields);
    }
    
    
    /**
     * Изчиства празния ред.
     * Премахва едноредовите коментари.
     */
    function clearFields($rec)
    {
        $delimiter = '[#newLine#]';
        
        //Заместваме празните редове
        $fields = str_ireplace(array("\n", "\r\n", "\n\r"), $delimiter, $rec);
        $fieldsArr = explode($delimiter, $fields);
        
        //Премахва редове, които започват с #
        foreach ($fieldsArr as $value) {
            
            //Премахваме празните интервали
            $value = trim($value);
            
            //Проверяваме дали е коментар
            if ((strpos($value, '#') !== 0) && (strlen($value))) {
                
                //Разделяме стринга на части
                $valueArr = explode("=", $value, 2);
                
                //Вземаме името на полето
                $fieldName = $valueArr[0];
                
                //Превръщаме името на полето в малки букви
                $fieldName = strtolower($fieldName);
                
                //Премахваме празните интервали в края и в началото в името на полето
                $fieldName = trim($fieldName);
                
                //Заместваме всички стойности различни от латински букви и цифри в долна черта
                $fieldName = preg_replace("/[^a-z0-9]/", "_", $fieldName);
                
                //Премахваме празните интервали в края и в началото в заглавието на полето
                $caption = trim($valueArr[1]);
                
                //Ескейпваме заглавието
//                $caption = htmlspecialchars($caption, ENT_COMPAT | ENT_HTML401, 'UTF-8');
//                $caption = core_Type::escape($caption);
                
                //Ескейпваме непозволените символи в заглавието
//                $caption = str_replace(array('=', '\'', '$', '|'), array('&#61;', '&#39;', '&#36;', '&#124;'), $caption);
                
                //Изчистваме заглавието на полето и го съединяваме със заглавието
                $newValue = $fieldName . '=' . $caption;
                
                //Създаваме нова променлива, в която ще се съхраняват всички полета
                ($newFields) ? ($newFields .= "\n" . $newValue) : $newFields = $newValue;
            }
        }
        
        return $newFields;
    }
    
    
    /**
     * Поддържа точна информацията за записите в детайла
     */
    static function on_AfterUpdateDetail($mvc, $id, $Detail)
    {
        $rec = $mvc->fetch($id);
        $dQuery = $Detail->getQuery();
        $dQuery->where("#listId = $id");
        $rec->contactsCnt = $dQuery->count();
        
        // Определяме състоянието на база на количеството записи (контакти)
        if($rec->state == 'draft' && $rec->contactsCnt > 0) {
            $rec->state = 'closed';
        } elseif ($rec->state == 'closed' && $rec->contactsCnt == 0) {
            $rec->state = 'draft';
        }
        
        $mvc->save($rec);
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, необходимо за това действие
     */
    static function on_AfterGetRequiredRoles($mvc, &$roles, $action, $rec)
    {
        if(($action == 'edit' || $action == 'delete') && $rec->state != 'draft' && isset($rec->state)) {
            $roles = 'no_one';
        }
    }
    
    
    /**
     * Добавя помощен шаблон за попълване на полетата
     */
    static function on_AfterPrepareEditForm($mvc, $data)
    {
        if (!$data->form->rec->fields) {
            $template = new ET (getFileContent("blast/tpl/ListsEditFormTemplates.txt"));
            $data->form->rec->fields = $template->getContent();
        }
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentIntf
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
          
        $row = new stdClass();
        
        //Заглавие
        $row->title = $this->getVerbal($rec, 'title');
        
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
     * Връща CSV представяне на данните в списъка
     */
    static function importCsvFromLists($listId)
    {
        $rec = self::fetch($listId);
        $fieldsArr = blast_ListDetails::getFncFieldsArr($rec->allFields);
        
        $csv = '';

        self::addCsvRow($csv, $fieldsArr);

 
        $dQuery = blast_ListDetails::getQuery();
        $dQuery->where("#listId = {$rec->id}");
        
        $listDetails = cls::get('blast_ListDetails');
        $listDetails->addFNC($rec->allFields);

        while($r = $dQuery->fetch()) {  
            $data = unserialize($r->data);
            
            $row = array();
            foreach($fieldsArr as $key => $caption) {
                $row[$key] = $data[$key . '_'];
            }
            
            self::addCsvRow($csv, $row);
         
        }

        return $csv;
    }
    
    /**
     * Добавя един ред в CSV структура
     */
    static function addCsvRow(&$csv, $row)
    { 
        $div = '';

        foreach($row as $value) {

            // escape
            if (preg_match('/\\r|\\n|,|"/', $value)) {
                $value = '"' . str_replace('"', '""', $value) . '"';
            }
                    
            $csv .= $div . $value;

            $div = ',';
        }
                
                 
        $csv .= "\n";

        return $csv;
    }
}
