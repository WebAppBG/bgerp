<?php



/**
 * Регистър на продуктите
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class cat_Products extends core_Master {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'acc_RegisterIntf,cat_ProductAccRegIntf';
    
    
    /**
     * Заглавие
     */
    var $title = "Продукти в каталога";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, plg_SaveAndNew, plg_PrevAndNext, acc_plg_Registry, plg_Rejected, plg_State,
                     cat_Wrapper, plg_Sorting, plg_Printing, Groups=cat_Groups, doc_FolderPlg, plg_Select, 
                     groups_Extendable';

    
    /**
     * Име на полето с групите, в които се намира продукт. Използва се от groups_Extendable
     * 
     * @var string
     */
    var $groupsField = 'groups';

    
    /**
     * Детайла, на модела
     */
    var $details = '';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Продукт";
    
    
    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/package-icon.png';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'name,code,categoryId,groups,tools=Пулт';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'name';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'admin,user';
    
    
    /**
     * Кой може да променя?
     */
    var $canEdit = 'admin,cat';
    
    
    /**
     * Кой може да добавя?
     */
    var $canAdd = 'admin,cat,broker';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'admin,cat,broker';
    
    
    /**
     * Кой може да го разгледа?
     */
    var $canList = 'admin,cat,broker';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin,cat';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'admin,cat';
    
    
    /**
     * Клас за елемента на обграждащия <div>
     */
    var $cssClass = 'folder-cover product-holder';
    
    
    /**
     * 
     */
    var $canSingle = 'admin, cat';
    

    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование, mandatory,remember=info,width=100%');
		$this->FLD('code', 'varchar(64)', 'caption=Код, mandatory,remember=info,width=15em');
        $this->FLD('eanCode', 'gs1_TypeEan13', 'input,caption=EAN,width=15em');
		$this->FLD('info', 'richtext', 'caption=Детайли');
        $this->FLD('measureId', 'key(mvc=cat_UoM, select=name)', 'caption=Мярка,mandatory,notSorting');
        $this->FLD('categoryId', 'key(mvc=cat_Categories,select=name)', 'caption=Категория,placeholder=Категория,remember=info');
        $this->FLD('groups', 'keylist(mvc=cat_Groups, select=name)', 'caption=Групи');
        
        $this->setDbUnique('code');
    }
    
    
    /**
     * Изпълнява се след подготовка на Едит Формата
     */
    static function on_AfterPrepareEditForm($mvc, $data)
    {
        if(!$data->form->rec->id && ($code = Mode::get('catLastProductCode'))) {
            
            //Разделяме текста от последното число
            preg_match("/(?'other'.+[^0-9])?(?'digit'[0-9]+)$/", $code, $match);
            
            //Ако сме отркили число
            if ($match['digit']) {
                
                //Съединяваме тескта с инкрементиранета с единица стойност на последното число
                $newCode = $match['other'] . ++$match['digit'];
                
                //Проверяваме дали има такъв запис в системата
                if (!$mvc->fetch("#code = '$newCode'")) {
                    $data->form->rec->code = $newCode;
                }
            }
        }
    }
    
    
    /**
     * Изпълнява се след въвеждане на данните от Request
     */
    static function on_AfterInputEditForm($mvc, $form)
    {
        //Проверяваме за недопустими символи
        if ($form->isSubmitted()){
            if (preg_match('/[^0-9a-zа-я]/iu', $form->rec->code)) {
                $form->setError('code', 'Полето може да съдържа само букви и цифри.');
            }
        }
                
        if (!$form->gotErrors()) {
            if(!$form->rec->id && ($code = Request::get('code', 'varchar'))) {
                Mode::setPermanent('catLastProductCode', $code);
            }    
        }
    }
    
    
    /**
     * Създаваме кофа
     *
     * @param core_MVC $mvc
     * @param stdClass $res
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('productsImages', 'Илюстрация на продукта', 'jpg,jpeg,png,bmp,gif,image/*', '3MB', 'user', 'every_one');
    }
    
    
    /**
     * Добавяне в таблицата на линк към детайли на продукта. Обр. на данните
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    static function on_AfterRecToVerbal ($mvc, $row, $rec)
    {
        // fancybox ефект за картинките
        $Fancybox = cls::get('fancybox_Fancybox');
        
        $tArr = array(200, 150);
        $mArr = array(600, 450);
        
        $images_fields = array('image1',
            'image2',
            'image3',
            'image4',
            'image5');
        
        foreach ($images_fields as $image) {
            if ($rec->{$image} == '') {
                $row->{$image} = NULL;
            } else {
                $row->{$image} = $Fancybox->getImage($rec->{$image}, $tArr, $mArr);
            }
        }
        
        // ENDOF fancybox ефект за картинките
    }
    
    
    /**
     * Оцветяване през ред
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    static function on_AfterPrepareListRows($mvc, $data)
    {
        $rowCounter = 0;
        
        if (count($data->rows)) {
            foreach ($data->rows as $i=>&$row) {
                $rec = $data->recs[$i];
                $rowCounter++;
                $row->code = ht::createLink($row->code, array($mvc, 'single', $rec->id));
                $row->name = ht::createLink($row->name, array($mvc, 'single', $rec->id));
                $row->name = "{$row->name}<div><small>" . $mvc->getVerbal($rec, 'info') . "</small></div>";
            }
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
        $data->listFilter->FNC('order', 'enum(alphabetic=Азбучно,last=Последно добавени)',
            'caption=Подредба,input,silent,remember');
        $data->listFilter->setField('categoryId',
            'placeholder=Всички категории,caption=Категория,input,silent,remember');
        $data->listFilter->getField('categoryId')->type->params['allowEmpty'] = TRUE;
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        $data->listFilter->showFields = 'order,categoryId';
        $data->listFilter->input('order,categoryId', 'silent');
        
        /**
         * @todo Кандидат за плъгин - перманентни полета на форма
         *
         * Плъгина може да се прикачи към формата, на on_AfterInput(). Трябва обаче да се
         * измисли еднозначно съответствие между име на поле на конкретна форма и името на
         * съответната стойност в сесията. Полетата на формите са именувани, но формите не са.
         */
        
        if (!$data->listFilter->rec->categoryId && !is_null(Request::get('categoryId'))) {
            $data->listFilter->rec->categoryId = Mode::get('cat_Products::listFilter::categoryId');
        } else {
            Mode::setPermanent('cat_Products::listFilter::categoryId', $data->listFilter->rec->categoryId);
        }
        
        if (!$data->listFilter->rec->order) {
            $data->listFilter->rec->order = Mode::get('cat_Products::listFilter::order');
        } else {
            Mode::setPermanent('cat_Products::listFilter::order', $data->listFilter->rec->order);
        }
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
        // Подредба
        if($data->listFilter->rec->order == 'alphabetic' || !$data->listFilter->rec->order) {
            $data->query->orderBy('#name');
        } elseif($data->listFilter->rec->order == 'last') {
            $data->query->orderBy('#createdOn=DESC');
        }
        
        if ($data->listFilter->rec->categoryId) {
            $data->query->where("#categoryId = {$data->listFilter->rec->categoryId}");
        }
    }
    
    
    /**
     * Изпълнява се преди запис на ред в таблицата
     */
    static function on_BeforeSave($mvc, &$id, $rec)
    {
        if ($rec->id) {
            if (!$rec->_old) {
                $rec->_old = new stdClass();
            }
            $rec->_old->categoryId = $mvc->fetchField($rec->id, 'categoryId');
            $rec->_old->groups = $mvc->fetchField($rec->id, 'groups');
        }
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
        if ($rec->_old->categoryId != $rec->categoryId) {
            if ($rec->_old->categoryId) {
                cat_Categories::updateProductCnt($rec->_old->categoryId);
            }
            cat_Categories::updateProductCnt($rec->categoryId);
        }
        
        $oldGroups = type_Keylist::toArray($rec->_old->groups);
        $groups = type_Keylist::toArray($rec->groups);
        $notifyGroups = array_diff(
            array_merge($oldGroups, $groups),
            array_intersect($oldGroups, $groups)
        );
        
        foreach ($notifyGroups as $groupId) {
            cat_Groups::updateProductCnt($groupId);
        }
    }
    
    
    /**
     * Запомняме категориите и групите на продуктите, които ще бъдат изтрити,
     * за да нотифицираме мастър моделите - cat_Categories и cat_Groups
     */
    static function on_BeforeDelete($mvc, &$res, &$query, $cond)
    {
        $_query = clone($query);
        $query->categoryIds = array();
        $query->groupIds = array();
        
        while ($rec = $_query->fetch($cond)) {
            if ($rec->categoryId) {
                $query->categoryIds[] = $rec->categoryId;
            }
            $query->groupIds = array_merge(
                $query->groupIds,
                type_Keylist::toArray($rec->groups)
            );
        }
        
        $query->categoryIds = array_unique($query->categoryIds);
        $query->groupIds = array_unique($query->groupIds);
    }
    
    
    /**
     * Обновява мастър моделите cat_Categories и cat_Groups след изтриване на продукти
     */
    static function on_AfterDelete($mvc, &$res, $query)
    {
        foreach ($query->categoryIds as $id) {
            cat_Categories::updateProductCnt($id);
        }
        
        foreach ($query->groupIds as $id) {
            cat_Groups::updateProductCnt($id);
        }
    }
    
    
    /**
     * Продуктите, заведени в дадено множество от групи.
     *
     * @param mixed $groups keylist(mvc=cat_Groups)
     * @param string $fields кои полета на продукта са необходими; NULL = всички
     */
    static function fetchByGroups($groups, $fields = NULL)
    {
        $result = array();
        
        if (count($groups = type_Keylist::toArray($groups)) > 0) {
            $query = self::getQuery();
            
            foreach ($groups as $group) {
                $query->orWhere("#groups LIKE '%|{$group}|%'");
            }
            
            if (isset($fields)) {
                $fields = arr::make($fields, TRUE);
                
                if (!isset($fields['id'])) {
                    $fields['id'] = 'id';
                }
                $query->show($fields);
            }
            
            while ($rec = $query->fetch()) {
                $result[$rec->id] = $rec;
            }
        }
        
        return $result;
    }
    
    
    /**
     * Перо в номенклатурите, съответстващо на този продукт
     *
     * Част от интерфейса: acc_RegisterIntf
     */
    static function getItemRec($objectId)
    {
        $result = NULL;
        
        if ($rec = self::fetch($objectId)) {
            $result = (object)array(
                'num' => $rec->code,
                'title' => $rec->name,
                'uomId' => $rec->measureId,
                'features' => 'foobar' // @todo!
            );
        }
        
        return $result;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::getLinkToObj
     * @param int $objectId
     */
    static function getLinkToObj($objectId)
    {
        if ($rec = self::fetch($objectId)) {
            $result = ht::createLink(static::getVerbal($rec, 'name'), array(__CLASS__, 'Single', $objectId));
        } else {
            $result = '<i>неизвестно</i>';
        }
        
        return $result;
    }
    
    
    /**
     * @see acc_RegisterIntf::itemInUse()
     * @param int $objectId
     */
    static function itemInUse($objectId)
    {
    }
    
    
    /**
     * Имплементация на @link cat_ProductAccRegIntf::getProductPrice() за каталожни продукти
     *
     * @param int $productId
     * @param string $date Ако е NULL връща масив с историята на цените на продукта: [дата] => цена
     * @param int $discountId key(mvc=catpr_Discounts) пакет отстъпки. Ако е NULL - цена без отстъпка.
     */
    static function getProductPrice($productId, $date = NULL, $discountId = NULL)
    {
        // Извличаме себестойността към дата или историята от себестойности
        $costs = catpr_Costs::getProductCosts($productId, $date);
        
        if (empty($costs)) {
            return NULL;
        }
        
        $result = array();
        
        if (isset($discountId)) {
            
            foreach ($costs as &$costRec) {
                $discount = catpr_Discounts::getDiscount(
                    $discountId,
                    $costRec->priceGroupId
                );
                
                $costRec->price = (double)$costRec->publicPrice * (1 - $discount);
            }
        }
        
        foreach ($costs as $costRec) {
            $result[$costRec->valior] = isset($costRec->price) ? $costRec->price : (double)$costRec->publicPrice;
        }
        
        if (isset($date)) {
            // Ако е фиксирана дата правилата гарантират точно определена (една) цена
            expect(count($result) == 1, $result, $costs);
            $result = reset($result);
        }
        
        return $result;
    }

    
    /**
     * Метод връщаш информация за продукта и неговите опаковки
     * @param int $productId - Ид на продукта
     * @param int $packagingId - Ид на опаковката, по дефолт NULL
     * @return stdClass $res - Обект с информация за продукта
     * и опаковките му ако $packagingId не е зададено, иначе връща
     * информацията за подадената опаковка
     */
    public static function getProductInfo($productId, $packagingId = NULL)
    {
    	// Ако няма такъв продукт връщаме NULL
    	if(!$productRec = static::fetch($productId)) {
    		return NULL;
    	}
    	
    	$res = new stdClass();
    	$res->productRec = $productRec;
    	$Packagings = cls::get('cat_products_Packagings');
    	
    	if(!$packagingId) {
    		
    		$res->packagings = array();
    		
    	    // Ако не е зададена опаковка намираме всички опаковки
    		$packagings = $Packagings->fetchDetails($productId);
    		
    		// Пре-индексираме масива с опаковки - ключ става id на опаковката 
    		foreach ((array)$packagings as $pack) {
    		    $res->packagings[$pack->packagingId] = $pack;
    		}
    	} else {
    		
    		// Ако е зададена опаковка, извличаме само нейния запис
    		$res->packagingRec = $Packagings->fetchPackaging($productId, $packagingId);
    		
    		if(!$res->packagingRec) {
    			
    			// Ако я няма зададената опаковка за този продукт
    			return NULL;
    		}
    	}
    	
    	// Връщаме информацията за продукта
    	return $res;
    }
    
    
    /**
     * Връща ид на продукта и неговата опаковка по зададен Код/Баркод
     * @param mixed $code - Код/Баркод на търсения продукт
     * @return stdClass $res - Информация за намерения продукт
     * и неговата опаковка
     */
    public static function getByCode($code)
    {
    	$code = trim($code);
    	expect($code, 'Не е зададен код');
    	$res = new stdClass();
    	
    	// Проверяваме имали опаковка с този код: вътрешен или баркод
    	$Packagings = cls::get('cat_products_Packagings');
    	$catPack = $Packagings->fetchByCode($code);
    	if($catPack) {
    		
    		// Ако има запис намираме ид-та на продукта и опаковката
    		$res->productId = $catPack->productId;
    		$res->packagingId = $catPack->packagingId;
    	} else {
    		
    		// Проверяваме имали продукт с такъв код
    		$query = static::getQuery();
    		$query->where(array("#code = '[#1#]'", $code));
    		if($rec = $query->fetch()) {
    			
    			$res->productId = $rec->id;
    			$res->packagingId = NULL;
    		} else {
    			
    			// Ако няма продукт
    			return FALSE;
    		}
    	}
    	
    	return $res;
    }
}
