<?php



/**
 * Ценови групи
 *
 *
 * @category  bgerp
 * @package   price
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Ценови групи
 */
class price_GroupOfProducts extends core_Detail
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Ценови групи';
    
    
    /**
     * Заглавие
     */
    var $singleTitle = 'Ценова група';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, price_Wrapper, plg_LastUsedKeys';
                    
 
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'groupId, productId, validFrom, createdBy, createdOn';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'validFrom';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'user';
    
    
    /**
     * Кой може да го промени?
     */
    var $canEdit = 'user';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'user';
    
        
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'user';
    
    
    /**
     * @todo Чака за документация...
     */
    var $currentTab = 'Групи';
    
    
    /**
     * Поле - ключ към мастера
     */
    var $masterKey = 'productId';
   

    /**
     * Променлива за кеширане на актуалната информация, кой продукт в коя група е;
     */
    static $products = array();


    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products,select=name,allowEmpty)', 'caption=Продукт,silent,mandatory,hint=Само продаваеми продукти');
        $this->FLD('groupId', 'key(mvc=price_Groups,select=title,allowEmpty)', 'caption=Група,silent');
        $this->FLD('validFrom', 'datetime(timeSuggestions=00:00|04:00|08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00|21:00)', 'caption=В сила oт');
    }


    /**
     * Връща групата на продукта към посочената дата
     */
    static function getGroup($productId, $datetime)
    {
        $query = self::getQuery();
        $query->orderBy('#validFrom', 'DESC');
        $query->where("#validFrom <= '{$datetime}'");
        $query->where("#productId = {$productId}");
        $query->limit(1);

        if($rec = $query->fetch()) {
			return $rec->groupId;
        }
    }


    /**
     * Връща масив групите на всички всички продукти към определената дата
     * $productId => $groupId
     */
    static function getAllProducts($datetime = NULL)
    {
        price_ListToCustomers::canonizeTime($datetime);
		
        $datetime = price_History::canonizeTime($datetime);
		
        $query = self::getQuery();

        $query->where("#validFrom <= '{$datetime}'");

        $query->orderBy("#validFrom", "DESC");
        
        $res = array();
        
        while($rec = $query->fetch()) {
            if(!$used[$rec->productId]) {
                if($rec->groupId) {
                    $res[$rec->productId] = cat_Products::getTitleById($rec->productId);
                }
                $used[$rec->productId] = TRUE;
            }
        }

        asort($res);
		
        return $res;
    }
    
    
    /**
     * Извиква се след подготовка на заявката за детайла
     */
    static function on_AfterPrepareDetailQuery(core_Detail $mvc, $data)
    {
        // Историята на ценовите групи на продукта - в обратно хронологичен ред.
        $data->query->orderBy("validFrom,id", 'DESC');
    }


    /**
     * Извиква се след обработка на ролите
     */
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec)
    {
        if($rec->validFrom && ($action == 'edit' || $action == 'delete')) {
            if($rec->validFrom <= dt::verbal2mysql()) {
                $requiredRoles = 'no_one';
            }
        }
    }
    

    /**
     * Подготвя формата за въвеждане на групи на продукти
     */
    public static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $rec = $data->form->rec;

        if(!$rec->id) {
            $rec->validFrom = Mode::get('PRICE_VALID_FROM');
        }
        
        if($rec->groupId) {
	        $groupName = price_Groups::getTitleById($rec->groupId);
	        $data->form->title = '|Добавяне на артикул към група|* "' . $groupName . '"';
        }
        
        // За опции се слагат само продаваемите продукти
        $products = cat_Products::makeArray4Select(NULL, "#meta LIKE '%canSell%'");
        $data->form->setOptions('productId', $products);

        if($data->masterMvc instanceof cat_Products) {
            $data->form->title = "Добавяне в ценова група";
            $data->form->setField('productId', 'input');
            $data->form->setReadOnly('productId');

            if(!$rec->groupId) {
                $rec->groupId = self::getGroup($rec->productId, dt::verbal2mysql());
            }
        }
    }
    

    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        if($form->isSubmitted()) {
            
            $rec = $form->rec;

            $now = dt::verbal2mysql();
            
            if(!$rec->validFrom) {
                $rec->validFrom = $now;
            }

            if($rec->validFrom < $now) {
                $form->setError('validFrom', 'Групата не може да се сменя с минала дата');
            }
            
            if(!$form->gotErrors() ) {
                Mode::setPermanent('PRICE_VALID_FROM', ($rec->validFrom > $now) ? $rec->validFrom : '');
            }
        }
    }
    

    /**
     * Връща съответния мастер
     */
    function getMasterMvc_($rec)
    {
        if($rec->_masterMvc) {
            return $rec->_masterMvc;
        }

        if($rec->groupId && !$rec->productId) {
            return cls::get('price_Groups');
        }

        if($rec->productId) {
            return cls::get('cat_Products');
        }

        return parent::getMasterMvc_($rec);
    }
    

    /**
     *
     */
    function getMasterKey($rec)
    {
        if($rec->_masterKey) { 
            return $rec->_masterKey;
        }

        if($rec->groupId && !$rec->productId) {
            return 'groupId';
        }

        if($rec->productId) {
            return 'productId';
        }
        
        return parent::getMasterKey_($rec);
    }




    /**
     * След подготовка на записите във вербален вид
     */
    public static function on_AfterPrepareListRows(core_Detail $mvc, $data)
    {   
        if (!$data->rows) {
            return;
        }
        
        $now  = dt::now(TRUE); // Текущото време (MySQL формат) с точност до секунда
        $currentGroupId = NULL;// ID на настоящата ценова група на продукта
        
        /**
         * @TODO следващата логика вероятно ще трябва и другаде. Да се рефакторира!
         */
        
        // Цветово кодиране на историята на ценовите групи: добавя CSS клас на TR елементите
        // както следва:
        //
        //  * 'future' за бъдещите ценови групи (невлезли все още в сила)
        //  * 'active' за текущата ценова група
        //  * 'past' за предишните ценови групи (които вече не са в сила)
        foreach ($data->rows as $id => &$row) {
            
            $rec = $data->recs[$id];
            
            if ($rec->validFrom > $now) {
                $row->ROW_ATTR['class'] = 'state-draft';
            } else {
                $row->ROW_ATTR['class'] = 'state-closed';

                if (!isset($currentGroupId) || $rec->validFrom > $data->recs[$currentGroupId]->validFrom) {
                    $currentGroupId = $id;
                }
            }
            
            $row->groupId = price_Groups::getHyperLink($rec->groupId, TRUE);
        }
        
        if (isset($currentGroupId)) {
            $data->rows[$currentGroupId]->ROW_ATTR['class'] = 'state-active';
        }
    }


    /**
     * Извиква се след рендиране на детайла
     */
    public static function on_AfterRenderDetail($mvc, &$tpl, $data)
    {
        $wrapTpl = new ET(getFileContent('cat/tpl/ProductDetail.shtml'));
        $wrapTpl->append($mvc->singleTitle, 'TITLE');
        $wrapTpl->append($tpl, 'CONTENT');
        $wrapTpl->replace(get_class($mvc), 'DetailName');
    
        $tpl = $wrapTpl;

        if ($data->addUrl) {
            $addBtn = ht::createLink("<img src=" . sbf('img/16/add.png') . " valign=bottom style='margin-left:5px;'>", $data->addUrl, NULL, 'title=Задаване на ценова група');
            $tpl->append($addBtn, 'TITLE');
        }
    }


    /**
     * Подготовка на данните за детайла
     */
    public static function preparePriceGroup($data)
    { 
        $data->TabCaption = 'Ценова група';
        $data->Order = 5;

        static::prepareDetail($data);

        $data->toolbar->removeBtn('*');

        $data->addUrl = array('price_GroupOfProducts', 'add', 'productId' => $data->masterId, 'ret_url' => TRUE);
    }
    
    
    /**
     * Рендиране изгледа на детайла
     */
    public function renderPriceGroup($data)
    {
        // Премахваме продукта - в случая той е фиксиран и вече е показан 
        unset($data->listFields[$this->masterKey]);
        
        return static::renderDetail($data);
    }

    
    /**
     * Премахва кеша за интервалите от време
     */
    public static function on_AfterSave($mvc, &$id, &$rec, $fields = NULL)
    {
        price_History::removeTimeline();
    }
	
    
    /**
     *
     */
    function prepareProductInGroup($data)
    {   
        $data->masterKey = 'groupId';
         
        // Очакваме да masterKey да е зададен
        expect($data->masterKey);
        expect($data->masterMvc instanceof core_Master);
        
         
        // Подготвяме полетата за показване
        $data->listFields = arr::make('productId=Продукт,validFrom=В сила от,createdBy=Създадено->От,createdOn=Създадено->На');
        
        // Подготвяме навигацията по страници
        $this->prepareListPager($data);
        
        // Подготвяме лентата с инструменти
        $this->prepareListToolbar($data);

        $query = self::getQuery();
         
        $query->orderBy('#validFrom', 'DESC');
        
        $data->recs = array();
        
        $now = dt::verbal2mysql();

        $used = $futureUsed = array();

        while($rec = $query->fetch()) {
             
            if($rec->validFrom > $now) {
                $var = 'futureUsed';
            } else {
                $var = 'used';
            }


            if(${$var}[$rec->productId]) continue;
            if($data->masterId == $rec->groupId) {
                $rec->_masterMvc = cls::get('price_Groups');
                $rec->_masterKey = 'groupId';
                $data->recs[$rec->id] = $rec;
            }
            ${$var}[$rec->productId] = TRUE;
        }
 
        if(count($data->recs)) {
            foreach($data->recs as $rec) {
                $data->rows[$rec->id] = self::recToVerbal($rec);  
                $data->rows[$rec->id]->productId = cat_Products::getHyperLink($rec->productId, TRUE);
                if($rec->validFrom > $now) {
                    $data->rows[$rec->id]->ROW_ATTR['class'] = 'state-draft';
                }
            }
        }
    }


    /**
     *
     */
    function renderProductInGroup($data)
    {
        return self::renderDetail_($data);
    }
}
