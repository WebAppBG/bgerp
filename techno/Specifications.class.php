<?php



/**
 * "Спецификация" - нестандартен продукт или услуга
 *
 *
 * @category  bgerp
 * @package   techno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class techno_Specifications extends core_Manager {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'price_PolicyIntf, acc_RegisterIntf, cat_ProductAccRegIntf';
    
    
    /**
     * Заглавие
     */
    public $title = "Спецификации";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'techno_Wrapper, plg_Printing, plg_Search, plg_Rejected';

    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Спецификация";
    
    
    /**
     * Кой може да оттегля
     */
    public $canReject = 'no_one';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/specification.png';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'title, folderId, docClassId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, title, folderId, docClassId, common, createdOn, createdBy';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Кой може да го прочете?
     */
    public $canRead = 'ceo,techno';
    
    
    /**
     * Кой може да го прочете?
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Кой може да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,techno';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,techno';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('title', 'varchar', 'caption=Заглавие, input=none');
		$this->FLD('docClassId', 'class(interface=techno_ProductsIntf,select=title)', 'caption=Тип,input=none,silent');
		$this->FLD('docId', 'int', 'caption=Документ,input=none');
		$this->FLD('folderId', 'key(mvc=doc_Folders)', 'caption=Папка,input=none');
		$this->FLD('common', 'enum(no=Частен,yes=Общ)', 'caption=Достъп,input=none,value=no,autoFilter');
    	$this->FLD('sharedUsers', 'userList', 'caption=Споделяне->Потребители,input=none');
    	$this->FLD('createdOn', 'datetime(format=smartTime)', 'caption=Създаване->На, notNull, input=none');
        $this->FLD('createdBy', 'key(mvc=core_Users)', 'caption=Създаване->От, notNull, input=none');
    	$this->FLD('state', 
            'enum(active=Активирано, rejected=Отказано)', 
            'caption=Статус, input=none'
        );
    	
    	$this->setDbUnique('title');
    }
    
    
    /**
     * Малко манипулации след подготвянето на формата за филтриране
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
    	 $data->listFilter->view = 'horizontal';
    	 $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png'); 
    	 $data->listFilter->setOptions('common', array('' => '', 'no' => 'Общ', 'yes' => 'Частен'));
    	 $data->listFilter->setDefault('common', '');
    	 $data->listFilter->showFields = 'search,common';
    	 $data->listFilter->input();
    	 
    	 if($data->listFilter->rec->common){
    	 	$data->query->where("#common = '{$data->listFilter->rec->common}'");
    	 }
    	 
    	 $data->query->orderBy('id', 'DESC');
    }
    
    
	/**
     * Заглавие на политиката
     * 
     * @param mixed $customerClass
     * @param int $customerId
     * @return string
     */
    public function getPolicyTitle($customerClass, $customerId)
    {
        return $this->singleTitle;
    }
    
    
    /**
     * Връща продуктите, които могат да се продават на посочения клиент
     * Това са всички спецификации от неговата папка, както и
     * всички общи спецификации (създадени в папка "Проект")
     */
    function getProducts($customerClass, $customerId, $date = NULL, $containerId = NULL)
    {
    	$Class = cls::get($customerClass);
    	$folderId = $Class->forceCoverAndFolder($customerId, FALSE);
    	
    	if($containerId){
    		$origin = doc_Containers::getDocument($containerId);
    		$originClassId = $origin->getClassId();
    	}
    	
    	$products = array();
    	$query = $this->getQuery();
    	$query->where("#folderId = {$folderId}");
    	$query->orWhere("#common = 'yes'");
    	$query->where("#state = 'active'");
    	while($rec = $query->fetch()){
    		try{
    			$DocClass = cls::get($rec->docClassId);
    			if($rec->docClassId != $originClassId && $rec->docId != $origin->that){
    				if($DocClass->fetchField($rec->docId, 'state') != 'active') continue;
    			}    			
    			$products[$rec->id] = $this->recToVerbal($rec, 'title')->title;
    		} catch(Exception $e){
    			continue;
    		}
    	}
    	
    	return $products;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if($fields['-list']){
	    	$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
	    	try{
	    		$DocClass = cls::get($rec->docClassId);
	    	} catch(Exception $e){
	    		return;
	    	}
	    	
	    	$docThreadId = $DocClass->fetchField($rec->docId, 'threadId');
	    	
	    	if(doc_Threads::haveRightFor('single', $docThreadId)){
	    		$icon = $DocClass->getIcon($rec->id);
		    	$attr['class'] = 'linkWithIcon';
	            $attr['style'] = 'background-image:url(' . sbf($icon) . ');';
	            $row->title = str::limitLen(strip_tags($row->title), 70);
	            $row->title = ht::createLink($row->title, array($DocClass, 'single', $rec->docId), NULL, $attr);  
	    	}
	    	
	    	$row->ROW_ATTR['class'] = "state-{$rec->state}";
    	}
    }
    
    
    /**
     * Връща класа на драйвера на спецификацията
     * @param int $id - ид на спецификация
     * @return core_ObjectReference - използвания драйвър
     */
    public static function getDriver($id)
    {
    	expect($rec = static::fetchRec($id));
    	
    	return new core_ObjectReference($rec->docClassId, $rec->docId);
    }
    
    
    /**
     * Връща ДДС-то на продукта
     * @param int $id - ид на спецификацията
     * @param date $date - дата
     */
    public static function getVat($id, $date = NULL)
    {
    	$TechnoClass = static::getDriver($id);
    	
    	return $TechnoClass->getVat();
    }
    
    
    /**
     * Връща цената за посочения продукт към посочения
     * клиент на посочената дата
     * Цената се изчислява по формулата формулата:
     * ([начални такси] * (1 + [максимална надценка]) + [количество] * 
     *  [единична себестойност] *(1 + [минимална надценка])) / [количество]
     * 
     * @return object
     * $rec->price  - цена
     * $rec->discount - отстъпка
     */
    public function getPriceInfo($customerClass, $customerId, $id, $productManId, $packagingId = NULL, $quantity = 1, $datetime = NULL)
    {
    	$TechnoClass = static::getDriver($id);
    	$priceInfo = $TechnoClass->getPriceInfo($packagingId, $quantity, $datetime);
    	
    	if($priceInfo->price){
    		$price = new stdClass();
    		if($priceInfo->discount){
    			$price->discount = $priceInfo->discount;
    		}
    		
    		$minCharge = cond_Parameters::getParameter($customerClass, $customerId, 'minSurplusCharge');
    		$maxCharge = cond_Parameters::getParameter($customerClass, $customerId, 'maxSurplusCharge');
    		$price->price = ($priceInfo->tax * (1 + $maxCharge) 
    					+ $quantity * $priceInfo->price * (1 + $minCharge)) / $quantity;
    		
    		return $price;
    	}
    	
    	// Ако продукта няма цена, връщаме цената от последно
    	// продадената спецификация на този клиент (ако има)
    	$LastPricePolicy = cls::get('sales_SalesLastPricePolicy');
    	
    	return $LastPricePolicy->getPriceInfo($customerClass, $customerId, $id, $productManId, $packagingId, $quantity, $datetime);
	}
    
    
	/**
     * Връща цената по себестойност на продукта
     * @TODO себестойността да идва от заданието
     * @return double
     */
    public function getSelfValue($productId, $packagingId = NULL, $quantity = 1, $date = NULL)
    {
    	$TechnoClass = static::getDriver($productId);
    	$priceInfo = $TechnoClass->getPriceInfo($packagingId, $quantity, $date);
    	
    	if($priceInfo->price){
    		$price = ($priceInfo->tax  + $quantity * $priceInfo->price) / $quantity;
    		
    		// Цената по себестойност е тази с 0-ви максимални и минимални надценки
    		return $price;
    	}
    	
    	return NULL;
    }
    
    
    /**
     * Предефинираме метода getTitleById да връща вербалното
     * представяне на продукта
     * @param int $id - id на спецификацията
     * @param boolean $full 
     * 	      		FALSE - връща само името на спецификацията
     * 		        TRUE - връща целия шаблон на спецификацията
     * @return core_ET - шаблон с представянето на спецификацията
     */
     public static function getTitleById($id, $escaped = TRUE, $full = FALSE)
     {
	    $TechnoClass = static::getDriver($id);
     	
     	if(!$full) {
    		return $TechnoClass->getTitleById($escaped);
    	}
    	
    	$data = $TechnoClass->prepareData();
    	
	    return $TechnoClass->renderShortView($data);
     }
    
    
    /**
     * Метод връщаш информация за продукта и неговите опаковки
     * @param int $id - ид на продукта
     * @param int $packagingId - ид на опаковката, по дефолт NULL
     * @return stdClass $res - обект с информация за продукта
     * и опаковките му ако $packagingId не е зададено, иначе връща
     * информацията за подадената опаковка
     */
    public static function getProductInfo($id, $packagingId = NULL)
    {
    	$TechnoClass = static::getDriver($id);
    	
    	return $TechnoClass->getProductInfo($packagingId);
    }
    
    
    /**
     * Връща опаковките в които се предлага даден продукт
     */
	public static function getPacks($productId)
    {
    	$TechnoClass = static::getDriver($productId);
    	
    	return $TechnoClass->getPacks();
    }
    
    
    /**
     * Форсира спецификация
     * @param core_Mvc $mvc - mvc на модела
     * @param stdClass $rec - запис от sales_Sales или purchase_Purchases
     * @return int - ид на създадения или обновения запис
     */
    public static function forceRec(core_Mvc $mvc, $rec)
    {
    	$coverClass = doc_Folders::fetchCoverClassName($rec->folderId);
    	$classId = $mvc::getClassId();
    	$arr = array(
    		'id'         => static::fetchField("#docClassId = {$classId} AND #docId = {$rec->id}", 'id'),
    		'title'      => $rec->title,
    		'docClassId' => $classId,
    		'docId'      => $rec->id,
    		'folderId'   => $rec->folderId,
    		'state'      => ($rec->state != 'rejected') ? 'active' : 'rejected',
    		'createdOn'  => dt::now(),
    		'createdBy'  => core_Users::getCurrent(),
    		'common'     => !cls::haveInterface('doc_ContragentDataIntf', $coverClass) ? "yes" : "no",
    	);
    	
    	return static::save((object)$arr);
    }
    
    
    /**
     * Ф-я извличаща спецификация по даден документ
     * @param int $docClassId - ид на класа на документа
     * @param int $docId - ид на документа
     * @return stdRec - записа на спецификацията ако го има
     */
    public static function fetchByDoc($docClassId, $docId)
    {
    	return static::fetch("#docClassId = {$docClassId} AND #docId = {$docId}");
    }
    
    
   /**
	* Преобразуване на запис на регистър към запис за перо в номенклатура (@see acc_Items)
	*
	* @param int $objectId ид на обект от регистъра, имплементиращ този интерфейс
	* @return stdClass запис за модела acc_Items:
	*
	* o num
	* o title
	* o uomId (ако има)
	* o features - списък от признаци за групиране
	*/
    function getItemRec($objectId)
    {
        $info = $this->getProductInfo($objectId);
        $itemRec = (object)array(
            'num' => 'SPC' . $objectId,
            'title' => $info->productRec->title,
            'uomId' => $info->productRec->measureId,
        );
        
        return $itemRec;
    }
    
    
	/**
     * Връща масив от продукти отговарящи на зададени мета данни:
     * canSell, canBuy, canManifacture, canConvert, fixedAsset, canStore
     * @param mixed $properties - комбинация на горе посочените мета 
     * 							  данни или като масив или като стринг
     * @return array $products - продукти отговарящи на условието, ако не са
     * 							 зададени мета данни връща всички продукти
     */
    public static function getByProperty($properties)
    {
    	$products = array();
    	$properties = arr::make($properties);
    	expect(count($properties));
    	
    	$query = static::getQuery();
    	$query->where("#state = 'active'");
    	while($rec = $query->fetch()){
    		$flag = FALSE;
    		$DocClass = cls::get($rec->docClassId);
    		$meta = $DocClass->getProductInfo($rec->docId)->meta;
    		foreach ($properties as $prop){
    			if(empty($meta[$prop])) $flag = TRUE;
    		}
    		
    		if(!$flag){
    			$products[$rec->id] = $DocClass->getTitleById($rec->docId);
    		}
    	}
    	
    	return $products;
    }
    
    
   /**
	* Хипервръзка към този обект
	*
	* @param int $objectId ид на обект от регистъра, имплементиращ този интерфейс
	* @return mixed string или ET (@see ht::createLink())
	*/
    function getLinkToObj($objectId)
    {
    	return static::getHyperlink($objectId);
    }
    
    
    /**
     * Линк към драйвера на спецификацията
     */
    public static function getHyperlink($id, $icon = FALSE)
    {
    	expect($Driver = static::getDriver($id));
    	
        return $Driver->getHyperlink();
    }
    
    
	/**
     * Връща стойноства на даден параметър на продукта, ако я има
     * @param int $id - ид на продукт
     * @param string $sysId - sysId на параметър
     */
    public function getParam($id, $sysId)
    {
    	$TechnoClass = static::getDriver($id);
    	
    	return $TechnoClass->getParam($sysId);
    }
    
    
	/**
     * Връща теглото на еденица от продукта, ако е в опаковка връща нейното тегло
     * 
     * @param int $productId - ид на продукт
     * @param int $packagingId - ид на опаковка
     * @return double - теглото на еденица от продукта
     */
    public function getWeight($productId, $packagingId = NULL)
    {
    	$TechnoClass = static::getDriver($productId);
    	
    	return $TechnoClass->getWeight($productId, $packagingId);
    }
    
    
	/**
     * Връща обема на еденица от продукта, ако е в опаковка връща нейния обем
     * 
     * @param int $productId - ид на продукт
     * @param int $packagingId - ид на опаковка
     * @return double - теглото на еденица от продукта
     */
    public function getVolume($productId, $packagingId = NULL)
    {
    	$TechnoClass = static::getDriver($productId);
    	
    	return $TechnoClass->getVolume($productId, $packagingId);
    }
    
    
    /**
	 * Нотифицира регистъра, че обекта е станал (или престанал да бъде) перо
	 *
	 * @param int $objectId ид на обект от регистъра, имплементиращ този интерфейс
	 * @param boolean $inUse true - обекта е перо; false - обекта не е перо
	 */
    function itemInUse($objectId, $inUse)
    {
        /* TODO */
    }
    

    /**
     * Имплементиране на интерфейсен метод за съвместимост със стари записи
     */
    function getDocumentRow($id)
    {
    }
    
    
    /**
     * Имплементиране на интерфейсен метод за съвместимост със стари записи
     */
    function getIcon($id)
    {
    }
    
    
    /**
     * Имплементиране на интерфейсен метод за съвместимост със стари записи
     */
    static function getHandle($id)
    {
    }
}