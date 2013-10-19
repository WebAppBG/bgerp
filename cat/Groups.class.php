<?php



/**
 * Мениджър на групи с продукти.
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_Groups extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Групи на артикулите";
    
    
    /**
     * @todo Чака за документация...
     */
    var $pageMenu = "Каталог";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, cat_Wrapper, 
    				 doc_FolderPlg, plg_Search';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,name';
    
    
    /**
     * Полета по които се прави пълнотекстово търсене от плъгина plg_Search
     */
    var $searchFields = 'sysId, name, productCnt, info, meta';
    
    
    /**
     * Дали да се превежда, транслитерира singleField полето
     * 
     * translate - Превежда
     * transliterate - Транслитерира
     */
    var $langSingleField = 'translate';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'name';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Група->продукти";
    
    
    /**
     * Икона за единичен изглед
     */
    var $singleIcon = 'img/16/category-icon.png';

    
    /**
     * Кой може да чете
     */
    var $canRead = 'powerUser';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    var $canEditsysdata = 'cat,ceo';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'cat,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'cat,ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'powerUser';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'powerUser';
	
    
    /**
     * Кой може да качва файлове
     */
    var $canWrite = 'ceo,cat';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'powerUser';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'cat,ceo';


    /**
     * Клас за елемента на обграждащия <div>
     */
    var $cssClass = 'folder-cover';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'cat/tpl/SingleGroup.shtml';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Наименование, mandatory');
        $this->FLD('sysId', 'varchar(32)', 'caption=System Id,oldFieldName=systemId,input=none,column=none');
        $this->FLD('info', 'richtext(bucket=Notes)', 'caption=Бележки');
        $this->FLD('productCnt', 'int', 'input=none');
        
        // Свойства присъщи на продуктите в групата
        $this->FLD('meta', 'set(canSell=Продаваеми,
        						canBuy=Купуваеми,
        						canStore=Складируеми,
        						canConvert=Вложими,
        						fixedAsset=ДМА,
        						canManifacture=Производими)', 'caption=Свойства->Списък,columns=2');
        
        $this->setDbUnique("sysId");
    }
    
/**
     * Подредба и филтър на on_BeforePrepareListRecs()
     * Манипулации след подготвянето на основния пакет данни
     * предназначен за рендиране на списъчния изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
    	$data->query->orderBy('#name');
    	
        if($data->listFilter->rec->product) {  
        	$groupList = cat_Products::fetchField($data->listFilter->rec->product, 'groups');
           		$data->query->where("'{$groupList}' LIKE CONCAT('%|', #id, '|%')");
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
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('product', 'key(mvc=cat_Products, select=name, allowEmpty=TRUE)', 'caption=Продукт');
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'search,product';
        
        $rec = $data->listFilter->input('product,search', 'silent');
 
    }
    
     /**
     * Изпълнява се след подготовка на Едит Формата
     */
    static function on_AfterPrepareEditForm($mvc, $data)
    {
    	if(!haveRole('ceo')){
    		
    		// Кой може да променя мета пропъртитата на групите
    		$data->form->setField('meta', 'input=none');
    	}
    }
    
    
	/**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->productCnt = intval($rec->productCnt);
    	if($fields['-list']){
    		$row->name .= " ({$row->productCnt})";
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
        // Ако групата е системна или в нея има нещо записано - не позволяваме да я изтриваме
        if($action == 'delete' && ($rec->sysId || $rec->productCnt)) {
            $requiredRoles = 'no_one';
        }
    }


    /**
     * Връща keylist от id-та на групи, съответстващи на даден стрингов
     * списък от sysId-та, разделени със запетайки
     */
    static function getKeylistBySysIds($list, $strict = FALSE)
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
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
    	$file = "cat/csv/Groups.csv";
    	$fields = array( 
	    	0 => "name", 
	    	1 => "info", 
	    	2 => "sysId", 
	    	3 => "meta");
    	
    	$cntObj = csv_Lib::importOnce($mvc, $file, $fields);
    	$res .= $cntObj->html;
    	
    	return $res;
    }
    
    
    /**
     * Преди запис в модела
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
    	if($rec->id){
    		// Старите мета данни
    		$rec->oldMeta = $mvc->fetchField($rec->id, 'meta');
    	}
    }
    
    
	/**
     * След запис в модела
     */
    static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
        if($rec->oldMeta != $rec->meta) {
        	
            // Ако има промяна на групите, Инвалидира се кеша
            core_Cache::remove('cat_Products', "productsMeta");
        }
    }
    
    
    /**
     * Връща групите които отговарят на посочени мета данни
     * @param mixed $meta - списък от мета данни
     * @return array $res - масив с опции
     */
    public static function getByMeta($meta)
    {
    	$metaArr = arr::make($meta);
    	$query = static::getQuery();
    	if(count($metaArr)){
	    	foreach ($metaArr as $m){
	    		$query->like('meta', $m);
	    	}
    	}
    	
    	$res = array();
    	while($rec = $query->fetch()){
    		$res[$rec->id] = static::getTitleById($rec->id);
    	}
    	
    	return $res;
    }
}