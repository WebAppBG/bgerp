<?php 


/**
 * Медии за отпечатване
 * 
 * @category  bgerp
 * @package   label
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class label_Prints extends core_Master
{
    
    
    /**
     * Заглавие на модела
     */
    public $title = 'Серии за отпечатване';
    
    
    /**
     * 
     */
    public $singleTitle = 'Отпечатък';
    
    
    /**
     * Кой има право да чете?
     */
    public $canChangestate = 'label, admin, ceo';
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'label, admin, ceo';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'label, admin, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'label, admin, ceo';
    
    
    /**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'label, admin, ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой има право да принтира етикети
     */
    public $canPrint = 'label, admin, ceo';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'label_Wrapper, plg_Created, plg_Modified, plg_State, plg_RefreshRows, plg_Search, plg_Sorting';
    
    
    /**
     * Стойност по подразбиране на състоянието
     * @see plg_State
     */
    public $defaultState = 'active';
    
    
    /**
     * 
     * 
     * @see plg_RefreshRowss
     */
    public $refreshRowsTime = 5000;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'labelId=Данни->Етикет, mediaId=Данни->Медия, labelsCnt=Брой->Етикети, copiesCnt=Брой->Копия, printedCnt=Брой->Отпечатвания, tools=Отпечатване, createdOn, createdBy, modifiedOn, modifiedBy';
    

    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'labelId, mediaId';
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('labelId', 'key(mvc=label_Labels, select=title)', 'caption=Етикет, mandatory, width=100%, silent');
        $this->FLD('mediaId', 'key(mvc=label_Media, select=title)', 'caption=Медия, silent, mandatory, notNull');
        
        $this->FLD('printedCnt', 'int', 'caption=Брой отпечатвания, mandatory, notNull, input=none');
        
        $this->FLD('labelsCnt', 'int(min=1, max=200)', 'caption=Брой етикети, mandatory');
        $this->FLD('copiesCnt', 'int(min=1, max=50)', 'caption=Брой копия, value=1, mandatory');
        
        $this->FLD('state', 'enum(active=Активно, closed=Спрян)', 'caption=Състояние, input=none, notNull');
    }
    
    
    /**
     * Добавя нов запис за отпечатване
     */
    function act_New()
    {
        // Трябва да има запис и да има права за записа
        $this->requireRightFor('single');
        $labelId = Request::get('labelId', 'int');
        $lRec = label_Labels::fetch($labelId);
        expect($lRec);
        label_Labels::requireRightFor('uselabel', $lRec);
        
        $form = $this->getForm();
        $form->input(NULL, TRUE);
        
        $retUrl = getRetUrl();
        if (!$retUrl) {
            $retUrl = array('label_labels', 'single', $labelId);
        }
        
        // Трябва да има зададена медия за шаблона
        $mediaArr = label_Templates::getMediaForTemplate($lRec->templateId);
        if (!$mediaArr) {
            if (label_Templates::haveRightFor('single', $lRec->templateId)) {
                return new Redirect(array('label_Templates', 'single', $lRec->templateId, 'ret_url' => TRUE), 'Трябва да добавите медия за шаблона');
            } else {
                return new Redirect($retUrl, 'Няма добавена медия за шаблона');
            }
        }
        
        if (!isset($mediaArr[''])) {
            $mediaArr = array('' => '') + $mediaArr;
        }
        $form->setOptions('mediaId', $mediaArr);
        
        $form->input();
        
        // Показваме предипреждение, ако ше има празни пространства в една страница на медията
        if ($form->isSubmitted()) {
            $labelsCnt = label_Media::getCountInPage($form->rec->mediaId);
            
            $allPirntsCnt = $form->rec->labelsCnt * $form->rec->copiesCnt;
            
            if ($allPirntsCnt % $labelsCnt) {
                $form->setWarning('labelsCnt, copiesCnt', "Броя не е кратен на {$labelsCnt}. Ще има неизползвана част от медията.");
            }
        }
        
        // Ако няма грешки, записваме и редиректваме към листовия изглед
        if ($form->isSubmitted()) {
            $id = $this->save($form->rec);
            
            return new Redirect(array($this, 'list', 'saveId' => $id));
        }
        
        $form->setReadOnly('labelId');
        $form->setDefault('copiesCnt', 1);
        
        $form->title = 'Отпечатване';
        
        // Добавяме бутоните на формата
        $form->toolbar->addSbBtn('Печат', 'save', 'ef_icon = img/16/printer.png');
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close16.png');
        
        return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Рендира етикетите за отпечатване
     * 
     * @see core_Master::act_Single()
     */
    function act_Single()
    {
        // Трябва да има запис и да има права за записа
        $this->requireRightFor('single');
        $id = Request::get('id', 'int');
        $rec = self::fetch($id);
        $this->requireRightFor('single', $rec);
        
        // Ще се принтира
        Mode::set('wrapper', 'page_Print');
        Mode::set('printing');
        
        $data = new stdClass();
        
        $data->rec = $rec;
        
        $data->cnt = $rec->labelsCnt;
        $data->copyCnt = $rec->copiesCnt;
        $data->allCnt = $data->cnt * $data->copyCnt;
        
        $data->Media = new stdClass();
        $data->Media->rec = label_Media::fetch($rec->mediaId);
        
        $data->Label = new stdClass();
        $data->Label->rec = label_Labels::fetch($rec->labelId);
        
        // Подготвяме медията
        label_Media::prepareMediaPageLayout($data);
        
        // Подготвяме данните за етикета
        label_Labels::prepareLabel($data);
        
        // Рендираме медията
        $pageLayout = label_Media::renderMediaPageLayout($data);
        
        // Рендираме етикета
        $tpl = label_Labels::renderLabel($data, $pageLayout);
        
        // Маркираме медията, като използване
        label_Media::markMediaAsUsed($rec->mediaId);
        
        $printedLabels = $rec->labelsCnt * $rec->copiesCnt;
        
        // Обновяваме броя на отпечатваният в етикета
        label_Labels::updatePrintCnt($rec->labelId, $printedLabels);
        
        // Обновяваме броя на отпечатванията и за текущия отпечатък и го затваряме
        $rec->state = 'closed';
        $rec->printedCnt += $printedLabels;
        
        $this->save($rec);
        
        return $tpl;
    }
    
    
    /**
     * Подготовка на филтър формата
     * 
     * @param label_Prints $mvc
     * @param object $data
     */
    static function on_AfterPrepareListFilter($mvc, &$data)
    {
        // По подразбиране да се показват черновите записи най-отпред
        $data->query->orderBy("state", "ASC");
        $data->query->orderBy("modifiedOn", "DESC");
        
        $data->listFilter->FNC('author', 'users(rolesForAll=labelMaster|ceo|admin, rolesForTeams=label|ceo|admin)', 'caption=От', array('attr' => array('onchange' => "addCmdRefresh(this.form);this.form.submit()")));
        
        $data->listFilter->showFields = 'author, search';
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->input('author', TRUE);
        
        //Добавяме бутон "Филтрирай"
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        
        // Ако не е избран потребител по подразбиране
        if(!$data->listFilter->rec->author) {
            
            // Да е текущия
            $data->listFilter->rec->author = '|' . core_Users::getCurrent() . '|';
        }
        
        // Ако има филтър
        if($filter = $data->listFilter->rec) {
            
			// Ако се търси по всички
			if (strpos($filter->author, '|-1|') !== FALSE) {
			    
			    if (!haveRole('labelMaster, ceo, admin')) {
			        $data->query->where('1=2');
			    }
            } else {
                
                // Масив с потребителите
                $usersArr = type_Keylist::toArray($filter->author);
                
                $data->query->orWhereArr('createdBy', $usersArr);
                $data->query->orWhereArr('modifiedBy', $usersArr, TRUE);
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if ($mvc->haveRightFor('single', $rec)) {
            $warning = FALSE;
            
            // Ако съсотоянието е затворено показваме предупреждение
            if ($rec->state == 'closed') {
                $modifiedDate = dt::mysql2verbal($rec->modifiedOn, "d.m.Y");
                $warning = "Този етикет е бил отпечатван нa|* $modifiedDate. |Искате ли да го отпечатате още веднъж|*?";
            }
            
            $row->tools = ht::createBtn('Печат', array($mvc, 'single', $rec->id), $warning, '_blank', 'ef_icon=img/16/printer.png, title=Отпечатване');
        }
        
        if (label_Labels::haveRightFor('single', $rec->labelId)) {
            $row->labelId = ht::createLink($row->labelId, array('label_Labels', 'single', $rec->labelId));
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if ($action == 'single') {
            if (!$mvc->haveRightFor('print', $rec, $userId)) {
                $requiredRoles = 'no_one';
            }
        } 
        
        // За да може да се принтира трябва етикета да не е оттеглен
        if ($rec && ($action == 'print') && $requiredRoles != 'no_one') {
            if (!label_Labels::haveRightFor('uselabel', $rec->labelId)) {
                $requiredRoles = 'no_one';
            }
        }
    }
}
