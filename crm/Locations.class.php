<?php



/**
 * Локации
 *
 *
 * @category  bgerp
 * @package   crm
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class crm_Locations extends core_Master {
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'cms_ObjectSourceIntf';
    
    
    
    /**
     * Заглавие
     */
    var $title = "Локации на контрагенти";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, crm_Wrapper, plg_Rejected, plg_RowNumbering, plg_Sorting';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "id, title, contragent=Контрагент, type";


    /**
     * Кой може да чете и записва локации?
     */
    var $canRead  = 'ceo';
    
    
    /**
     * 
     */
    var $canWrite = 'powerUser';
    
    
    /**
     * 
     */
    var $canSingle = 'powerUser';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'powerUser';
    
	
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Локация";
    
    
    /**
     * Икона на единичния обект
     */
    var $singleIcon = 'img/16/location_pin.png';
    
    
    /**
	 * Детайли към локацията
	 */
	var $details = 'routes=sales_Routes';
	
	
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsSingleField = 'title';

    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'crm/tpl/SingleLayoutLocation.shtml';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('contragentCls', 'class(interface=crm_ContragentAccRegIntf)', 'caption=Собственик->Клас,input=hidden,silent');
        $this->FLD('contragentId', 'int', 'caption=Собственик->Id,input=hidden,silent');
        $this->FLD('title', 'varchar', 'caption=Наименование,width=100%');
        $this->FLD('type', 'enum(correspondence=За кореспонденция,
            headquoter=Главна квартира,
            shipping=За получаване на пратки,
            office=Офис,shop=Магазин,
            storage=Склад,
            factory=Фабрика,
            other=Друг)', 'caption=Тип,mandatory,width=15.4em');
        $this->FLD('countryId', 'key(mvc=drdata_Countries, select=commonName, selectBg=commonNameBg, allowEmpty)', 'caption=Държава');
        $this->FLD('place', 'varchar(64)', 'caption=Град,oldFieldName=city');
        $this->FLD('pCode', 'varchar(16)', 'caption=П. код');
        $this->FLD('address', 'varchar(255)', 'caption=Адрес');
        $this->FLD('gln', 'gs1_TypeEan(gln)', 'caption=GLN код,width=15.4em');
        $this->FLD('gpsCoords', 'location_Type', 'caption=Координати');
        $this->FLD('image', 'fileman_FileType(bucket=location_Images)', 'caption=Снимка');
        $this->FLD('comment', 'richtext(bucket=Notes, rows=4)', 'caption=@Информация');

        $this->setDbUnique('gln');
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $rec = $data->form->rec;
        
        $Contragents = cls::get($rec->contragentCls);
        expect($Contragents instanceof core_Master);
        
        $contragentRec = $Contragents->fetch($rec->contragentId);
        
        $data->form->setDefault('countryId', $contragentRec->country);
        $data->form->setDefault('place', $contragentRec->place);
        $data->form->setDefault('pCode', $contragentRec->pCode);
        
        $contragentTitle = $Contragents->getTitleById($contragentRec->id);
        
        if($rec->id) {
            $data->form->title = 'Редактиране на локация на |*' . $contragentTitle;
        } else {
            $data->form->title = 'Нова локация на |*' . $contragentTitle;
        }
    }
    
    
     /**
     * Изпълнява се след въвеждането на данните от заявката във формата
     */
    static function on_AfterInputEditForm($mvc, $form)
    {
        $rec = $form->rec;
        if(!$rec->gpsCoords && $rec->image){
        	
        	if($gps = exif_Reader::getGps($rec->image)){
        		
        		// Ако има GPS коодинати в снимката ги извличаме
        		$rec->gpsCoords = $gps['lat'] . ", " . $gps['lon'];
        	}
        }
        
        if($form->isSubmitted()){
        	if(empty($rec->title)){
        		if(isset($rec->pCode) && isset($rec->place) && isset($rec->countryId)){
        			$countryName = drdata_Countries::fetchField($rec->countryId, 'commonNameBg');
        			$rec->title = "{$rec->pCode} {$rec->place}, {$countryName}";
        		} else {
        			$form->setError('title', 'Не е избрано име за локацията! Изберете име или посочете държава, град и код');
        			$form->setField('title', 'mandatory');
        		}
        	}
        }
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    {
        $cMvc = cls::get($rec->contragentCls);
        $field = $cMvc->rowToolsSingleField;
        $cRec = $cMvc->fetch($rec->contragentId);
        $cRow = $cMvc->recToVerbal($cRec, "-list,{$field}");
        $row->contragent = $cRow->{$field};
       
    	if($rec->image) {
			$Fancybox = cls::get('fancybox_Fancybox');
			$row->image = $Fancybox->getImage($rec->image, array(188, 188), array(580, 580));
		}
		
		if(!$rec->gpsCoords){
			unset($row->gpsCoords);
		}
		
        if($rec->state == 'rejected'){
        	if($fields['-single']){
        		$row->headerRejected = ' state-rejected';
        	} else {
        		$row->ROW_ATTR['class'] .= ' state-rejected';
        	}
        }
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    static function on_AfterSave($mvc, &$id, $rec, $fields = NULL)
    {
    	$mvc->routes->changeState($id);
    }
    
    
    /**
     * Подготвя локациите на контрагента
     */
    function prepareContragentLocations($data)
    {
        expect($data->masterId);
        expect($data->contragentCls = core_Classes::getId($data->masterMvc));
        
        $data->recs = static::getContragentLocations($data->contragentCls, $data->masterId);
        
        foreach ($data->recs as $rec) {
            $data->rows[$rec->id] = $this->recToVerbal($rec);
        }

        $data->TabCaption = 'Локации';
    }


    /**
     * Премахване на бутона за добавяне на нова локация от лист изгледа
     */
    function on_BeforeRenderListToolbar($mvc, &$tpl, &$data)
    {
        $data->toolbar->removeBtn('btnAdd');
    }
    
    
    /**
   	 * Обработка на ListToolbar-a
   	 */
   	static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = &$data->rec;
    	if(sales_Sales::haveRightFor('write') && $rec->state != 'rejected'){
    		$contragentCls = cls::get($rec->contragentCls);
    		$cRec = $contragentCls->fetch($rec->contragentId);
    		$url = array('sales_Sales', 'add','folderId' => $cRec->folderId, 'deliveryLocationId' => $rec->id);
    		$Sales = cls::get('sales_Sales');
    		$data->toolbar->addBtn($Sales->singleTitle, $url,  NULL, 'ef_icon=img/16/view.png');
    	}
    	
    	if($rec->address && $rec->place && $rec->countryId){
    		$address = "{$data->row->address},{$data->row->place},{$data->row->countryId}";
    	} elseif($rec->gpsCoords) {
    		$address = $rec->gpsCoords;
    	}
    	
    	if($address && $rec->state != 'rejected'){
    		$url = "https://maps.google.com/?daddr={$address}";
    		$data->toolbar->addBtn('Навигация', $url,  NULL, 'ef_icon=img/16/compass.png,target=_blank');
    	}
    }
    
    
    /**
     * Рендира данните
     */
    function renderContragentLocations($data)
    {
        $tpl = new ET(getFileContent('crm/tpl/ContragentDetail.shtml'));
        
        $tpl->append(tr('Локации'), 'title');
        
        if(count($data->rows)) {
            
            foreach($data->rows as $id => $row) {
                $tpl->append("<div>", 'content');
                
                $tpl->append("{$row->title}, {$row->type}", 'content');
                
                if(!Mode::is('printing')) {
                    if($this->haveRightFor('edit', $id)) {
                        // Добавяне на линк за редактиране
                        $tpl->append("<span style='margin-left:5px;'>", 'content');
                        $url = array($this, 'edit', $id, 'ret_url' => TRUE);
                        $img = "<img src=" . sbf('img/16/edit-icon.png') . " width='16' height='16'>";
                        $tpl->append(ht::createLink($img, $url, FALSE, 'title=' . tr('Редактиране на локация')), 'content');
                        $tpl->append('</span>', 'content');
                    }
                    
                    if($this->haveRightFor('delete', $id)) {
                        // Добавяне на линк за изтриване
                        $tpl->append("<span style='margin-left:5px;'>", 'content');
                        $url = array($this, 'delete', $id, 'ret_url' => TRUE);
                        $img = "<img src=" . sbf('img/16/delete.png') . " width='16' height='16'>";
                        $tpl->append(ht::createLink($img, $url, 'Наистина ли желаете да изтриете локацията?', 'title=' . tr('Изтриване на локация')), 'content');
                        $tpl->append('</span>', 'content');
                    }
                }
                
                $tpl->append("</div>", 'content');
            }
        } else {
            $tpl->append(tr("Все още няма локации"), 'content');
        }
        
        if(!Mode::is('printing')) {
            if ($data->masterMvc->haveRightFor('single', $data->masterId)) {
                $url = array($this, 'add', 'contragentCls' => $data->contragentCls, 'contragentId' => $data->masterId, 'ret_url' => TRUE);
                $img = "<img src=" . sbf('img/16/add.png') . " width='16' height='16'>";
                $tpl->append(ht::createLink($img, $url, FALSE, 'title=' . tr('Добавяне на нова локация')), 'title');
            }
        }
        
        return $tpl;
    }


    /**
     *
     */
    static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if($requiredRoles == 'no_one') return;
        
    	if($rec->contragentCls) {
            $contragent = cls::get($rec->contragentCls);
            $requiredRoles = $contragent->getRequiredRoles($action, $rec->contragentId, $userId);
        }
    }


    /**
     * Връща масив със собствените локации
     */
    static function getOwnLocations()
    {
        
        return static::getContragentOptions('crm_Companies', crm_Setup::BGERP_OWN_COMPANY_ID);
    }


    /**
     * Всички локации на зададен контрагент
     * 
     * @param mixed $contragentClassId име, ид или инстанция на клас-мениджър на контрагент
     * @param int $contragentId първичен ключ на контрагента (в мениджъра му)
     * @return array масив от записи crm_Locations
     */
    public static function getContragentLocations($contragentClassId, $contragentId)
    {
        expect($contragentClassId = core_Classes::getId($contragentClassId));
        
        /* @var $query core_Query */
        $query = static::getQuery();
        $query->where("#contragentCls = {$contragentClassId} AND #contragentId = {$contragentId}");
        
        $recs = array();
        
        while($rec = $query->fetch()) {
            $recs[$rec->id] = $rec;
        }

        return $recs;
    }
    

    /**
     * Наименованията на всички локации на зададен контрагент
     * 
     * @param mixed $contragentClassId име, ид или инстанция на клас-мениджър на контрагент
     * @param int $contragentId първичен ключ на контрагента (в мениджъра му)
     * @param boolean $intKeys - дали ключовите да са инт или стринг
     * @return array масив от наименования на локации, ключ - ид на локации
     */
    public static function getContragentOptions($contragentClassId, $contragentId, $intKeys = TRUE)
    {
        $locationRecs = static::getContragentLocations($contragentClassId, $contragentId);
        
        foreach ($locationRecs as &$rec) {
            $rec = $rec->title;
        }

        if(!$intKeys && count($locationRecs)){
        	$locationRecs = array_combine($locationRecs, $locationRecs);
        }
        
        return $locationRecs;
    }


    /**
     * За NULL-ява празните gln и gpsCoords
     */
    function on_BeforeSetupMvc($mvc, &$res) 
    {
        if($mvc->db->tableExists($mvc->dbTableName)) {
            $query = $mvc->getQuery();
            while($rec = $query->fetch()) {
                
                $saveFlag = FALSE;
                
                if($rec->gln === '') {
                    $rec->gln = NULL;
                    $saveFlag = TRUE;
                }
                 if($rec->gpsCoords === '') {
                    $rec->gpsCoords = NULL;
                    $saveFlag = TRUE;
                }

                if($saveFlag) {
                    $mvc->save($rec);
                }
            }
        }
    }
}