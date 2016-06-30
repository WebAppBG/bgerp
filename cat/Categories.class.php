<?php



/**
 * Мениджър на категории с продукти.
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_Categories extends core_Master
{
    
    
	/**
	 * Поддържани интерфейси
	 */
	public $interfaces = 'cat_ProductFolderCoverIntf';
	
	
	/**
	 * Детайли
	 */
	public $details = 'updates=price_Updates';
	
	
    /**
     * Заглавие
     */
    public $title = "Категории на артикулите";
    
    
    /**
     * Страница от менюто
     */
    public $pageMenu = "Каталог";
    
    
    /**
     * Кои документи да се добавят като бързи бутони в папката на корицата
     */
    public $defaultDefaultDocuments  = 'cat_Products';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, cat_Wrapper, plg_State, doc_FolderPlg, plg_Rejected, plg_Modified';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name,meta=Свойства,useAsProto=Прототипи,count=Артикули';
    
    
    /**
     * Полета по които се прави пълнотекстово търсене от плъгина plg_Search
     */
    public $searchFields = 'sysId, name, productCnt, info';
    
    
    /**
     * Да се създаде папка при създаване на нов запис
     */
    public $autoCreateFolder = 'instant';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Категория";
    
    
    /**
     * Икона за единичен изглед
     */
    public $singleIcon = 'img/16/category-icon.png';
    
    
    /**
     * Кой може да чете
     */
    public $canRead = 'cat,ceo,sales,purchase';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'cat,ceo';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'cat,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'cat,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'cat,ceo,sales,purchase';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'cat,ceo,sales,purchase';
    
    
    /**
     * Кой може да качва файлове
     */
    public $canWrite = 'cat,ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой има право да го оттегля?
     */
    public $canReject = 'cat,ceo';
    
    
    /**
     * Клас за елемента на обграждащия <div>
     */
    public $cssClass = 'folder-cover';
    
    
    /**
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'cat/tpl/SingleCategories.shtml';
    
    
    /**
     * Дефолт достъп до новите корици
     */
    public $defaultAccess = 'team';
    
    
    /**
     * Извиква се след подготовката на формата
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$suggestions = cat_UoM::getUomOptions();
    	$data->form->setSuggestions('measures', $suggestions);
    }
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(64,ci)', 'caption=Наименование, mandatory,translate');
        $this->FLD('sysId', 'varchar(32)', 'caption=System Id,oldFieldName=systemId,input=none,column=none');
        $this->FLD('info', 'richtext(bucket=Notes,rows=4)', 'caption=Бележки');
        $this->FLD('useAsProto', 'enum(no=Не,yes=Да)', 'caption=Използване на артикулите като прототипи->Използване');
        $this->FLD('measures', 'keylist(mvc=cat_UoM,select=name,allowEmpty)', 'caption=Настройки - допустими за артикулите в категорията (всички или само избраните)->Мерки,columns=2,hint=Ако не е избрана нито една - допустими са всички');
        $this->FLD('prefix', 'varchar(64)', 'caption=Настройки - препоръчителни за артикулите в категорията->Начало код');
        $this->FLD('markers', 'keylist(mvc=cat_Groups,select=name,allowEmpty)', 'caption=Настройки - препоръчителни за артикулите в категорията->Групи,columns=2');
        $this->FLD('params', 'keylist(mvc=cat_Params,select=name,makeLinks)', 'caption=Настройки - препоръчителни за артикулите в категорията->Параметри');
        
        // Свойства присъщи на продуктите в групата
        $this->FLD('meta', 'set(canSell=Продаваеми,
                                canBuy=Купуваеми,
                                canStore=Складируеми,
                                canConvert=Вложими,
                                fixedAsset=Дълготрайни активи,
        			canManifacture=Производими)', 'caption=Настройки - препоръчителни за артикулите в категорията->Свойства,columns=2');
        
        $this->setDbUnique("sysId");
        $this->setDbUnique("name");
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
        // Ако групата е системна или в нея има нещо записано - не позволяваме да я изтриваме
        if($action == 'delete' && ($rec->sysId || $rec->productCnt)) {
            $requiredRoles = 'no_one';
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if(empty($rec->useAsProto)){
    		$rec->useAsProto = 'no';
    		$row->useAsProto = $mvc->getFieldType('useAsProto')->toVerbal($rec->useAsProto);
    	}
    	
    	if($fields['-list']){
    		$row->name .= " {$row->folder}";
    		
    		$count = cat_Products::count("#folderId = '{$rec->folderId}'");
    		
    		$row->count = cls::get('type_Int')->toVerbal($count);
    		$row->count = "<span style='float:right'>{$row->count}</span>";
    	}
    }
    
    
    /**
     * Връща keylist от id-та на групи, съответстващи на даден стрингов
     * списък от sysId-та, разделени със запетайки
     */
    public static function getKeylistBySysIds($list, $strict = FALSE)
    {
        $sysArr = arr::make($list);
        
        foreach($sysArr as $sysId) {
            $id = static::fetchField("#sysId = '{$sysId}'", 'id');
            
            if($strict) {
                expect($id, $sysId, $list);
            }
            
            if($id) {
                $keylist .= '|' . $id;
            }
        }
        
        if($keylist) {
            $keylist .= '|';
        }
        
        return $keylist;
    }
    
    
    /**
     * Изпълнява се преди импортирването на данните
     */
    public static function on_BeforeImportRec($mvc, &$rec)
    {
    	if($rec->csv_measures){
    		$measures = arr::make($rec->csv_measures, TRUE);
    		$rec->measures = '';
    		foreach ($measures  as $m){
    			$rec->measures = keylist::addKey($rec->measures, cat_UoM::fetchBySinonim($m)->id);
    		}
    	}
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public static function on_AfterSetupMvc($mvc, &$res)
    {
        $res .= core_Classes::add($mvc);
        
        $file = "cat/csv/Categories.csv";
        $fields = array(
            0 => "name",
            1 => "info",
            2 => "sysId",
            3 => "meta",
        	4 => "csv_measures",
        );
        
        $cntObj = csv_Lib::importOnce($mvc, $file, $fields);
        $res .= $cntObj->html;
        
        return $res;
    }
    
    
    /**
     * Връща мета дефолт мета данните на папката
     *
     * @param int $id - ид на спецификация папка
     * @return array $meta - масив с дефолт мета данни
     */
    public function getDefaultMeta($id)
    {
    	$rec = $this->fetchRec($id);
    	
    	return arr::make($rec->meta, TRUE);
    }
    
    
    /**
     * Връща дефолтния код на артикула добавен в папката на корицата
     */
    public function getDefaultProductCode($id)
    {
    	$rec = $this->fetchRec($id);
    	
    	// Ако има представка
    	if($rec->prefix){
    		
    		// Опитваме се да намерим първия код започващ с представката
    		$code = str::addIncrementSuffix("", $rec->prefix);
    		while(cat_Products::getByCode($code)){
    			$code = str::addIncrementSuffix($code, $rec->prefix);
    			if(!cat_Products::getByCode($code)){
    				break;
    			}
    		}
    	}
    	
    	// Връщаме намерения код
    	return $code;
    }
    
    
    /**
     * Връща мета дефолт параметрите със техните дефолт стойностти, които да се добавят във формата на
     * универсален артикул, създаден в папката на корицата
     *
     * @param int $id - ид на корицата
     * @return array $params - масив с дефолтни параметри И техните стойности
     * 				<ид_параметър> => <дефолтна_стойност>
     */
    public function getDefaultProductParams($id)
    {
    	$rec = $this->fetchRec($id);
    	$params = keylist::toArray($rec->params);
    	foreach($params as $paramId => &$value){
    		$value = NULL;
    	}
    	
    	return $params;
    }
    
    
    /**
     * Връща възможните за избор прототипни артикули с дадения драйвер
     * 
     * @param int|NULL $driverId - Ид на продуктов драйвер
     * @param string|NULL $meta  - Мета свойства на артикулите
     * @param int|NULL $limit - Ограничаване на резултатите
     * @return array $opt - прототипните артикули
     */
    public static function getProtoOptions($driverId = NULL, $meta = NULL, $limit = NULL)
    {
    	$opt = $cArr = array();
    	
    	// В кои категории може да има прототипни артикули
    	$cQuery = self::getQuery();
    	$cQuery->show('folderId');
    	while($cRec = $cQuery->fetch("#useAsProto = 'yes'")) {
    		$cArr[] = $cRec->folderId;
    	}
    	
    	// Ако има такива, извличаме активните артикули със същия драйвер
    	if(count($cArr)) {
    		$catList = implode(',', $cArr);
    		$Products = cls::get('cat_Products');
    		
    		$query = cat_Products::getQuery();
    		if($driverId){
    			$query->where("#{$Products->driverClassField} = {$driverId}");
    		}
    		
    		$query->where("#state = 'active' AND #folderId IN ({$catList})");
    		if($limit){
    			$query->limit($limit);
    		}
    		
    		if(isset($meta)){
    			$query->where("#{$meta} = 'yes'");
    		}
    		
    		while($pRec = $query->fetch()) {
    			$opt[$pRec->id] = cat_Products::getTitleById($pRec->id, FALSE);
    		}
    	}
    	
    	// Връщаме готовите опции
    	return $opt;
    }
    
    
    /**
     * След подготовка на филтъра за филтриране в корицата
     * 
     * @param core_mvc $mvc
     * @param core_Form $threadFilter
     * @param core_Query $threadQuery
     */
    protected static function on_AfterPrepareThreadFilter($mvc, core_Form &$threadFilter, core_Query &$threadQuery)
    {
    	// Добавяме поле за избор на групи
    	$threadFilter->FLD('group', 'key(mvc=cat_Groups,select=name)', 'caption=Група');
    	$threadFilter->showFields .= ",group";
    	$threadFilter->input('group');
    	
    	if(isset($threadFilter->rec)){
    		
    		// Ако търсим по група
    		if($group = $threadFilter->rec->group){
    			$catClass = cat_Products::getClassId();
    			
    			// Подготвяме заявката да се филтрират само нишки с начало Артикул
    			$threadQuery->EXT('docId', 'doc_Containers', 'externalName=docId,externalKey=firstContainerId');
    			$threadQuery->EXT('docClass', 'doc_Containers', 'externalName=docClass,externalKey=firstContainerId');
    			$threadQuery->where("#docClass = {$catClass}");
    			
    			// Разпъваме групите
    			$descendants = cat_groups::getDescendantArray($group);
    			$keylist = keylist::fromArray($descendants);
    			
    			// Намираме ид-та на артикулите от тези групи
    			$catQuery = cat_Products::getQuery();
    			$catQuery->likeKeylist("groups", $keylist);
    			$catQuery->show('id');
    			$productIds = array_map(create_function('$o', 'return $o->id;'), $catQuery->fetchAll());
    			
    			// Искаме от нишките да останат само тези за въпросните артикули
    			$threadQuery->in('docId', $productIds);
    		}
    	}
    }
}