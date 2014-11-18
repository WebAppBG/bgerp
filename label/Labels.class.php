<?php 


/**
 * Модел за създаване на етикети за печатане
 * 
 * @category  bgerp
 * @package   label
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class label_Labels extends core_Master
{
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Етикети';
    
    
    /**
     * 
     */
    var $singleTitle = 'Етикет';
    
    
    /**
     * Път към картинка 16x16
     */
    var $singleIcon = 'img/16/price_tag_label.png';
    
    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'label/tpl/SingleLayoutLabels.shtml';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'label, admin, ceo';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'label, admin, ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'label, admin, ceo';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'label, admin, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'label, admin, ceo';
    
    
    /**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'label, admin, ceo';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'label, admin, ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * 
     */
    public $canUselabel = 'label, admin, ceo';
    
    
    /**
     * Роли за мастера на етикетите
     */
    var $canMasterlabel = 'labelMaster, admin, ceo';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'label_Wrapper, plg_RowTools, plg_State, plg_Created, plg_Rejected, plg_Modified, plg_Search, plg_Clone, plg_Sorting';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=✍, title, templateId, printedCnt, createdOn, createdBy, modifiedOn, modifiedBy';
    
    
    /**
     * 
     */
    var $rowToolsField = 'tools';

    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'title';
    

    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'title, templateId';
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('title', 'varchar(128)', 'caption=Заглавие, mandatory, width=100%, silent');
        $this->FLD('templateId', 'key(mvc=label_Templates, select=title)', 'caption=Шаблон, silent, input=hidden');
        $this->FLD('params', 'blob(serialize,compress)', 'caption=Параметри, input=none');
        $this->FLD('printedCnt', 'int', 'caption=Отпечатъци, title=Брой отпечатани етикети, input=none');
        
        $this->setDbUnique('title');
    }
    
    
    /**
     * Обновява броя на отпечатванията
     * 
     * @param integer $id
     * @param integer $printedCnt
     */
    public static function updatePrintCnt($id, $printedCnt)
    {
        $rec = self::fetch($id);
        $rec->id = $rec->id;
        
        if ($rec->state == 'draft') {
            $rec->state = 'active';
        }
        
        $rec->printedCnt += $printedCnt;
        
        self::save($rec, 'printedCnt, state');
    }
    
    
	/**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        // Ако формата не е субмитната и не я редактираме
        if (!$data->form->isSubmitted() && !$data->form->rec->id) {
            
            // id на шаблона
            $templateId = Request::get('templateId', 'int');
            
            // Ако не е избрано id на шаблона
            if (!$templateId) {
                
                // Редиректваме към екшъна за избор на шаблон
                return Redirect(array($mvc, 'selectTemplate'));
            }
        }
        
        // Ако няма templateId
        if (!$templateId) {
            
            // Вземаме от записа
            $templateId = $data->form->rec->templateId;
            
            // Очакваме вече да има
            expect($templateId);
        }
        
        $data->form->title = ($data->form->rec->id ? 'Редактиране' : 'Добавяне') . ' на етикет от шаблон|* ';
        $data->form->title .= '"' . label_Templates::getVerbal($data->form->rec->templateId, 'title') . '"';
        
        // Добавяме полетата от детайла на шаблона
        label_TemplateFormats::addFieldForTemplate($data->form, $templateId);
        
        // Вземаме данните от предишния запис
        $dataArr = $data->form->rec->params;
        
        // Обхождаме масива
        foreach ((array)$dataArr as $fieldName => $value) {
            
            // Добавяме данните от записите
            $data->form->rec->$fieldName = $value;
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param label_Labels $mvc
     * @param core_Form $form
     */
    static function on_AfterInputEditForm($mvc, &$form)
    {
        // Инпутваме пак формата, за да може да вкараме silent полетата,
        // които идват от шаблона 
        $form->input(NULL, TRUE);
        
        // Ако формата е субмитната
        if ($form->isSubmitted()) {
            
            // Вземаме типа
            $type = $form->rec->type;
            
            // Ако редактираме записа
            if ($form->rec->id) {
                
                // Вземаме записа
                $rec = $mvc->fetch($form->rec->id);
                
                // Вземаме старите стойности
                $oldDataArr = $rec->params;
            }
            
            // Форма за функционалните полета
            $fncForm = cls::get('core_Form');
            
            // Вземаме функционалните полета за типа
            label_TemplateFormats::addFieldForTemplate($fncForm, $form->rec->templateId);
            
            // Обхождаме масива
            foreach ((array)$fncForm->fields as $fieldName => $dummy) {
                
                // Ако има масив за старите данни и новта стойност е NULL
                if ($oldDataArr && ($form->rec->$fieldName === NULL)) {
                    
                    // Използваме старата стойност
                    $dataArr[$fieldName] = $oldDataArr[$fieldName];
                } else {
                    
                    // Добавяме данните от формата
                    $dataArr[$fieldName] = $form->rec->$fieldName;
                }
            }
            
            // Добавяме целия масив към формата
            $form->rec->params = $dataArr;
        }
    }
    
    
    /**
     * 
     * 
     * @param label_Labels $mvc
     * @param object $row
     * @param object $rec
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        if (label_Templates::haveRightFor('single', $rec->templateId)) {
            $row->templateId = ht::createLink($row->templateId, array('label_Templates', 'single', $rec->templateId));
        }
    }
    
    
    /**
     * Екшън за избор на шаблон
     */
    function act_SelectTemplate()
    {
        // Права за работа с екшън-а
        $this->requireRightFor('add');
        
        // URL за редирект
        $retUrl = getRetUrl();
        
        // URL' то където ще се редиректва при отказ
        $retUrl = ($retUrl) ? ($retUrl) : (array($this));

        // Вземаме формата към този модел
        $form = $this->getForm();
        
        // Добавяме функционално поле
        $form->FNC('selectTemplateId', 'key(mvc=label_Templates, select=title, where=#state !\\= \\\'rejected\\\')', 'caption=Шаблон');
        
        // Въвеждаме полето
        $form->input('selectTemplateId');
        
        // Ако формата е изпратена без грешки
        if($form->isSubmitted()) {
            
            // Редиректваме към екшъна за добавяне
            return new Redirect(array($this, 'add', 'templateId' => $form->rec->selectTemplateId));
        }
        
        // Заглавие на шаблона
        $form->title = "Избор на шаблон";
        
        // Задаваме да се показват само полетата, които ни интересуват
        $form->showFields = 'selectTemplateId';
        
        // Добавяме бутоните на формата
        $form->toolbar->addSbBtn('Избор', 'save', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close16.png');
        
        // Рендираме опаковката
        return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Добавя бутон за настройки в единичен изглед
     * 
     * @param stdClass $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        // Ако има права за отпечатване
        if (label_Prints::haveRightFor('print') && label_Labels::haveRightFor('uselabel', $data->rec)) {
            $data->toolbar->addBtn('Отпечатване', array('label_Prints', 'new', 'labelId' => $data->rec->id, 'ret_url' => TRUE), "ef_icon=img/16/print_go.png, order=30");
        }
    }
    
    
    /**
     * След подготовка на сингъла
     * 
     * @param label_Labels $mvc
     * @param object $res
     * @param object $data
     */
    static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        // Данни
        $previewLabelData = new stdClass();
        $previewLabelData->Label = new stdClass();
        $previewLabelData->Label->rec = $data->rec;
        $previewLabelData->Label->id = $data->rec->id;
        $previewLabelData->updateTempData = FALSE;
        
        // Подгогвяме етикетите
        $mvc->prepareLabel($previewLabelData);
        
        // Добавяме към данните
        $data->PreviewLabel = $previewLabelData;
    }
    
    
    /**
     * Преди рендиране на сингъла
     * 
     * @param label_Labels $mvc
     * @param object $res
     * @param object $data
     */
    static function on_BeforeRenderSingle($mvc, &$res, $data)
    {
        $labelLayout = new ET();
        
        // Рендираме етикетите
        $data->row->PreviewLabel = $mvc->renderLabel($data->PreviewLabel, $labelLayout);
    }
    
    
    /**
     * Подготвяме етикета
     * 
     * @param object $data
     */
    static function prepareLabel(&$data)
    {
        $rec = $data->Label->rec;
        
        // Ако няма запис
        if (!$rec) {
            
            // Вземаме записа
            $rec = static::fetch($data->Label->id);
        }
        
        // Ако не е сетната бройката
        setIfNot($data->cnt, 1);
        setIfNot($data->copyCnt, 1);
        
        if (!$data->allCnt) {
            $data->allCnt = $data->cnt * $data->copyCnt;
        }
        
        // Ако няма стойност
        if (!$data->row) {
            
            // Създаваме обект
            $data->row = new stdClass();
        }
        
        // Вземаме шаблона
        $data->row->Template = label_Templates::getTemplate($rec->templateId);
        
        // Вземема плейсхолдерите в шаблона
        $placesArr = $data->row->Template->getPlaceholders();
        
        // Параметрите
        $params = $rec->params;
        
        $rowId = 0;
        
        // Докато достигнем броя на принтиранията
        for ($i = 0; $i < $data->cnt; $i++) {
            
            $copyId = 1;
            
            // Ако не е сетнат
            if (!isset($data->rows[$rowId])) {
                
                // Задаваме масива
                $data->rows[$rowId] = new stdClass();
            }
            
            // Обхождаме масива с шаблоните
            foreach ((array)$placesArr as $place) {
                
                // Вземаме името на плейсхолдера
                $fPlace = label_TemplateFormats::getPlaceholderFieldName($place);
                
                // Вземаме вербалната стойност
                $data->rows[$rowId]->$place = label_TemplateFormats::getVerbalTemplate($rec->templateId, $place, $params[$fPlace], $rec->id, $data->updateTempData);
            }
            
            // За всяко копие добавяме по едно копие
            for ($copyId; $copyId < $data->copyCnt; $copyId++) {
                $copyField = $rowId + $copyId;
                $data->rows[$copyField] = $data->rows[$rowId];
            }
            
            $rowId += $copyId;
        }
    }
    
    
    /**
     * Рендираме етикете
     * 
     * @param object $data
     * 
     * @return core_Et - Шаблона, който ще връщаме
     */
    static function renderLabel(&$data, $labelLayout=NULL)
    {
        // Генерираме шаблона
        $allTpl = new core_ET();
        
        // Брой записи на страница
        setIfNot($itemsPerPage, $data->pageLayout->itemsPerPage, 1);
        
        // Обхождаме резултатите
        foreach ((array)$data->rows as $rowId => $row) {
            
            // Номера на вътрешния шаблон
            $n = $rowId % $itemsPerPage;
            
            // Ако е първа или нямам шаблон
            if ($n === 0 || !$tpl) {
                
                // Рендираме изгледа за една страница
                $tpl = clone $labelLayout;
            }
            
            // Вземаме шаблона
            $template = clone($data->row->Template);
            
            // Заместваме в шаблона всички данни
            $template->placeArray($row);
            
            // Вкарваме CSS-a, като инлайн
            $template = label_Templates::addCssToTemplate($data->Label->rec->templateId, $template);
            
            // Заместваме шаблона в таблицата на страницата
            $tpl->replace($template, $n);
            
            // Ако сме на последния запис в страницата или изобщо на последния запис
            if (($rowId == ($data->allCnt - 1)) || ($n == ($itemsPerPage - 1))) {
                
                // Добавяме към главния шаблон
                $allTpl->append($tpl);
            }
        }
        
        // Премахваме незаместените плейсхолдери
        $allTpl->removePlaces();
        
        return $allTpl;
    }
    
    
    /**
     * Пренасочва URL за връщане след запис към сингъл изгледа
     * 
     * @param label_Labels $mvc
     * @param object $res
     * @param object $data
     */
    function on_AfterPrepareRetUrl($mvc, &$res, &$data)
    {
        // Ако е субмитната формата и сме натиснали бутона "Запис и нов"
        if ($data->form && $data->form->isSubmitted() && $data->form->cmd == 'save') {
            
            // Променяма да сочи към single'a
            $data->retUrl = toUrl(array($mvc, 'single', $data->form->rec->id));
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param label_Labels $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Ако има запис
        if ($rec) {
            
            // Ако ще добавяме нов
            if ($action == 'add') {
                
                // Вземаме записите
                $templateRec = label_Templates::fetch($rec->templateId);
                
                // Вземаме правата за създаване на етикет
                $requiredRoles = label_Templates::getRequiredRoles('createlabel', $templateRec);
            }
             
            // Ако редактираме
            if ($action == 'edit') {
                
                // Ако е оттеглено
                if ($rec->state == 'rejected') {
                    
                    // Оттеглените да не могат да се редактират
                    $requiredRoles = 'no_one';
                } elseif ($rec->state != 'draft') {
                    
                    // Потреибители, които имат роля за masterLabel могат да редактират
                    $requiredRoles = $mvc->getRequiredRoles('Masterlabel');
                }
            }
            
            // Ако ще се клонира, трябва да има права за добавяне
            if ($action == 'cloneuserdata') {
                if (!$mvc->haveRightFor('add', $rec, $userId)) {
                    $requiredRoles = 'no_one';
                }
            }
            
            if ($action == 'uselabel') {
                if ($rec->state == 'rejected') {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
 	/**
 	 * Изпълнява се след подготовката на формата за филтриране
 	 * 
 	 * @param unknown_type $mvc
 	 * @param unknown_type $data
 	 */
    function on_AfterPrepareListFilter($mvc, $data)
    {
        // Формата
        $form = $data->listFilter;
        
        // В хоризонтален вид
        $form->view = 'horizontal';
        
        // Добавяме бутон
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $form->showFields = 'search';
        
        // Инпутваме полетата
        $form->input(NULL, 'silent');
        
        // Подреждаме по състояние
        $data->query->orderBy('#state=ASC');
        
        // Подреждаме по дата на модифициране
        $data->query->orderBy('#modifiedOn=DESC');
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param label_Labels $mvc
     * @param int $id първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    public static function on_AfterSave($mvc, &$id, $rec)
    {
        if (!$rec->templateId) {
            expect($rec->id);
            $rec = $mvc->fetch($rec->id);
        }
        // Активираме шаблона
        label_Templates::activateTemplate($rec->templateId);
    }
    
    
    /**
     * Премахваме някои полета преди да клонираме
     * @see plg_Clone
     * 
     * @param label_Labels $mvc
     * @param object $rec
     * @param object $nRec
     */
    public static function on_BeforeSaveCloneRec($mvc, $rec, &$nRec)
    {
        unset($nRec->searchKeywords);
        unset($nRec->printedCnt);
        unset($nRec->modifiedOn);
        unset($nRec->modifiedBy);
        unset($nRec->state);
        unset($nRec->exState);
        unset($nRec->lastUsedOn);
        unset($nRec->createdOn);
        unset($nRec->createdBy);
    }
}
