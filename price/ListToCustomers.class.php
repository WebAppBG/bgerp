<?php



/**
 * Клиентски ценови политики
 *
 *
 * @category  bgerp
 * @package   price
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Клиентски ценови политики
 */
class price_ListToCustomers extends core_Detail
{
    
    /**
     * Заглавие
     */
    public $title = 'Ценови политики';
    
    
    /**
     * Заглавие
     */
    public $singleTitle = 'Ценова политика';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, price_Wrapper';
                    
    
    /**
     * Интерфейс за ценова политика
     */
    public $interfaces = 'price_PolicyIntf';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'listId, cClass, cId, validFrom, createdBy, createdOn';
    
    
    /**
     * Кой може да го прочете?
     */
    public $canRead = 'powerUser';
    
    
    /**
     * Кой може да го промени?
     */
    public $canEdit = 'powerUser';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'powerUser';
    
        
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'powerUser';
    

    /**
     * Поле - ключ към мастера
     */
    public $masterKey = 'cId';
    

    /**
     * Предлог в формата за добавяне/редактиране
     */
    public $formTitlePreposition = 'за';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('listId', 'key(mvc=price_Lists,select=title)', 'caption=Политика');
        $this->FLD('cClass', 'class(select=title,interface=crm_ContragentAccRegIntf)', 'caption=Клиент->Клас,input=hidden,silent');
        $this->FLD('cId', 'int', 'caption=Клиент->Обект');
        $this->FLD('validFrom', 'datetime', 'caption=В сила от');
        $this->EXT('state', 'price_Lists', 'externalName=state,externalKey=listId', 'caption=Сметка->№');
    }

    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if($form->isSubmitted()) {
            
            $rec = $form->rec;

            $now = dt::verbal2mysql();

            if(!$rec->validFrom) {
                $rec->validFrom = $now;
            }

            if($rec->validFrom < $now) {
                $form->setError('validFrom', 'Ценоразписа не може да се задава с минала дата');
            }

            if($rec->validFrom && !$form->gotErrors() && $rec->validFrom > $now) {
                Mode::setPermanent('PRICE_VALID_FROM', $rec->validFrom);
            }
        }
    }


    /**
     * Подготвя формата за въвеждане на ценови правила за клиент
     */
    protected static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $rec = $data->form->rec;

        if(!$rec->id) {
            $rec->validFrom = Mode::get('PRICE_VALID_FROM');
        }

        $rec->listId = self::getListForCustomer($rec->cClass, $rec->cId);
  		
        $data->form->setOptions('listId', price_Lists::getAccessibleOptions());
        
        if(price_Lists::haveRightFor('add')){
        	$data->form->toolbar->addBtn('Нови правила', array('price_Lists', 'add', 'cClass' => $rec->cClass , 'cId' => $rec->cId, 'ret_url' => TRUE), NULL, 'order=10.00015,ef_icon=img/16/page_white_star.png');
        }
    }


    /**
     * След подготовка на заявката към детайла
     */
    protected static function on_AfterPrepareDetailQuery($mvc, $data)
    {
        $cClassId = core_Classes::getId($data->masterMvc);
        
        $data->query->where("#cClass = {$cClassId}");

        $data->query->orderBy("#validFrom,#id", "DESC");
    }

    
    /**
     * Кой е мастър класа
     */
    public function getMasterMvc_($rec)
    {
        $masterMvc = cls::get($rec->cClass);
 
        return $masterMvc;      
    }
    
    
    /**
     * Кое поле е ключ към мастъра
     */
    public function getMasterKey_($rec)
    {
        return 'cId';      
    }
    
    
    /**
     * След като се намери мастъра
     */
    protected static function on_AfterGetMasters($mvc, &$masters, $rec)
    {
        if (empty($masters)) {
            $masters = array();
        }
        
        $masters['cId']    = cls::get($rec->cClass);
        $masters['listId'] = cls::get('price_Lists');
    }
    

    /**
     * След подготовка на лентата с инструменти за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, $data)
    {
        if (!empty($data->toolbar->buttons['btnAdd'])) {
            $data->toolbar->removeBtn('*');
            $masterClassId = core_Classes::getId($data->masterMvc);
            $masterRec = $data->masterMvc->fetch($data->masterId);
            
            if($data->masterMvc->haveRightFor('edit', $masterRec)){
            	$data->addUrl = array($mvc, 'add', 'cClass' => $masterClassId, 'cId' => $data->masterId, 'ret_url' => TRUE);
            }
        }
    }

	
    /**
     * След рендиране на детайла
     */
    protected static function on_AfterRenderDetail($mvc, &$tpl, $data)
    {
        $wrapTpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        $wrapTpl->append($mvc->title, 'title');
        $wrapTpl->append($tpl, 'content');
        $wrapTpl->replace(get_class($mvc), 'DetailName');
    
        $tpl = $wrapTpl;
        
        if ($data->addUrl  && !Mode::is('text', 'xhtml') && !Mode::is('printing')) {
            $tpl->append(ht::createLink("<img src=" . sbf('img/16/add.png') . " style='vertical-align: middle; margin-left:5px;'>", $data->addUrl, FALSE, 'title=Избор на Ценова политика'), 'title');
        }
    }


    /**
     * Връща актуалния към посочената дата набор от ценови правила за посочения клиент
     */
    private static function getValidRec($customerClassId, $customerId, $datetime = NULL)
    { 
        $now = dt::verbal2mysql();

        if(!$datetime) {
            $datetime = $now;
        }

        $query = self::getQuery();
        $query->where("#cClass = {$customerClassId} AND #cId = {$customerId}");
        $query->where("#validFrom <= '{$datetime}'");
        $query->where("#state != 'rejected'");
        
        $query->limit(1);
        $query->orderBy("#validFrom,#id", 'DESC');
        $lRec = $query->fetch();
 		
        return $lRec;
    }


    /**
     * Задава ценова политика за определен клиент
     */
    public static function setPolicyTocustomer($policyId, $cClass, $cId, $datetime = NULL)
    {
        if(!$datetime) {
            $datetime = dt::verbal2mysql();
        }

        $rec = new stdClass();
        $rec->cClass = $cClass;
        $rec->cId   = $cId;
        $rec->validFrom = $datetime;
        $rec->listId = $policyId;
 
        self::save($rec);
    }

    
    /**
     * Подготвя ценоразписите на даден клиент
     */
    public function preparePricelists($data)
    { 
        static::prepareDetail($data);
		
        $now = dt::verbal2mysql();

        $cClassId = core_Classes::getId($data->masterMvc);
        
        $validRec = self::getValidRec($cClassId, $data->masterId, $now);
       
        if(count($data->rows)) {
            foreach($data->rows as $id => &$row) {
                $rec = $data->recs[$id];
                if($rec->state == 'rejected'){
                	unset($data->rows[$id]);
                	continue;
                }
                
                if($rec->validFrom > $now) {
                    $state = 'draft';
                } elseif($validRec->id == $rec->id) {
                    $state = 'active';
                } else {
                    $state = 'closed';
                }
                $data->rows[$id]->ROW_ATTR['class'] = "state-{$state}";

                if(price_Lists::haveRightFor('single', $rec)) {
                    $row->listId = ht::createLink($row->listId, array('price_Lists', 'single', $rec->listId));
                }
            }
        }

        $data->TabCaption = 'Цени';
    }


    /**
     * След обработка на ролите
     */
    protected static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec)
    {
        if($rec->validFrom && ($action == 'edit' || $action == 'delete')) {
            if($rec->validFrom <= dt::verbal2mysql()) {
                $requiredRoles = 'no_one';
            }
        }
    }

    
    /**
     * Рендиране на ценоразписите на клиента
     */
    public function renderPricelists($data)
    {
        // Премахваме контрагента - в случая той е фиксиран и вече е показан 
        unset($data->listFields[$this->masterKey]);
        unset($data->listFields['cClass']);
        
        return static::renderDetail($data);
    }

    
    /**
     * Премахва кеша за интервалите от време
     */
    protected static function on_AfterSave($mvc, &$id, &$rec, $fields = NULL)
    {
        price_History::removeTimeline();
    }



    /****************************************************************************************************
     *                                                                                                  *
     *    И Н Т Е Р Ф Е Й С   `price_PolicyIntf`                                                        *
     *                                                                                                  *
     ***************************************************************************************************/
    

    /**
     * Връща валидните ценови правила за посочения клиент
     */
    public static function getListForCustomer($customerClass, $customerId, &$datetime = NULL)
    {
        static::canonizeTime($datetime);
    	
    	$validRec = self::getValidRec($customerClass, $customerId, $datetime);

        if($validRec) {
            $listId   = $validRec->listId;
        } else {
            $listId = price_ListRules::PRICE_LIST_CATALOG;
        }
        
        return $listId;
    }
    
    
    /**
     * Връща цената за посочения продукт към посочения клиент на посочената дата
     * 
     * @param mixed $customerClass - клас на контрагента
     * @param int $customerId - ид на контрагента
     * @param int $productId - ид на артикула
     * @param int $packagingId - ид на опаковка
     * @param double $quantity - количество
     * @param datetime $datetime - дата
     * @param double $rate  - валутен курс
     * @param enum(yes=Включено,no=Без,separate=Отделно,export=Експорт) $chargeVat - начин на начисляване на ддс
     * @return stdClass $rec->price  - цена
     * 				  $rec->discount - отстъпка
     */
    public function getPriceInfo($customerClass, $customerId, $productId, $packagingId = NULL, $quantity = NULL, $datetime = NULL, $rate = 1, $chargeVat = 'no')
    {
        $isProductPublic = cat_Products::fetchField($productId, 'isPublic');
        
        // Проверяваме имали последна цена по оферта
        $rec = sales_QuotationsDetails::getPriceInfo($customerClass, $customerId, $productId, $packagingId, $quantity);

        // Ако има връщаме нея
        if(empty($rec->price)){
        	
        	// Проверяваме дали артикула е частен или стандартен
        	if($isProductPublic == 'no'){
        		 
        		$rec = (object)array('price' => NULL);
        		 
        		// Ако драйвера може да върне цена, връщаме нея
        		if($Driver = cat_Products::getDriver($productId)){
        			$price = $Driver->getPrice($customerClass, $customerId, $productId, $packagingId, $quantity, $datetime, $rate, $chargeVat);
        			if(isset($price)){
        				$rec->price = $price;
        				return $rec;
        			}
        		}
        		 
        		// Ако не е зададено количество, взимаме това от последното активно задание, ако има такова
        		if(!isset($quantity)){
        			$quantityJob = cat_Products::getLastJob($productId)->quantity;
        			if(isset($quantityJob)){
        				$quantity = $quantityJob;
        			}
        		}
        		 
        		// Търсим първо активната търговска рецепта, ако няма търсим активната работна
        		$bomRec = cat_Products::getLastActiveBom($productId, 'sales');
        		if(empty($bomRec)){
        			$bomRec = cat_Products::getLastActiveBom($productId, 'production');
        		}
        		 
        		// Ако има рецепта връщаме по нея
        		if($bomRec){
        			$deltas = price_ListToCustomers::getMinAndMaxDelta($customerClass, $customerId);
        			$defPriceListId = price_ListToCustomers::getListForCustomer($customerClass, $customerId);
        			if($defPriceListId == price_ListRules::PRICE_LIST_CATALOG){
        				$defPriceListId = price_ListRules::PRICE_LIST_COST;
        			}
        	
        			$rec->price = cat_Boms::getBomPrice($bomRec, $quantity, $deltas->minDelta, $deltas->maxDelta, $datetime, $defPriceListId);
        		}
        	} else {
        		// За стандартните артикули търсим себестойността в ценовите политики
        		$rec = $this->getPriceByList($customerClass, $customerId, $productId, $packagingId, $quantity, $datetime, $rate, $chargeVat);
        	}
        }
       
        // Обръщаме цената във валута с ДДС ако е зададено и се закръгля спрямо ценоразписа
        if(!is_null($rec->price)){
        	$vat = cat_Products::getVat($productId);
        	$rec->price = deals_Helper::getDisplayPrice($rec->price, $vat, $rate, $chargeVat, $listRec->roundingPrecision);
        }
       
        // Връщаме цената
        return $rec;
    }
    
	
    /**
     * Връща минималната отстъпка и максималната надценка за даден контрагент
     * 
     * @param mixed $customerClass - ид на клас на контрагента
     * @param int $customerId      - ид на контрагента
     * @return object $res		   - масив с надценката и отстъпката
     * 				 o minDelta  - минималната отстъпка
     * 				 o maxDelta  - максималната надценка
     */
    private static function getMinAndMaxDelta($customerClass, $customerId)
    {
    	$res = (object)array('minDelta' => 0, 'maxDelta' => 0);
    	
    	$defPriceListId = price_ListToCustomers::getListForCustomer($customerClass, $customerId);
    	 
    	// Ако контрагента има зададен ценоразпис, който не е дефолтния
    	if($defPriceListId != price_ListRules::PRICE_LIST_CATALOG){
    		 
    		// Взимаме максималната и минималната надценка от него, ако ги има
    		$defPriceList = price_Lists::fetch($defPriceListId);
    		$res->minDelta = $defPriceList->minSurcharge;
    		$res->maxDelta = $defPriceList->maxSurcharge;
    	}
    	 
    	// Ако няма мин надценка, взимаме я от търговските условия
    	if(!$res->minDelta){
    		$res->minDelta = cond_Parameters::getParameter($customerClass, $customerId, 'minSurplusCharge');
    	}
    	 
    	// Ако няма макс надценка, взимаме я от търговските условия
    	if(!$res->maxDelta){
    		$res->maxDelta = cond_Parameters::getParameter($customerClass, $customerId, 'maxSurplusCharge');
    	}
    	
    	return $res;
    }
    
    
    /**
     * Опит за намиране на цената според политиката за клиента (ако има такава)
     */
    private function getPriceByList($customerClass, $customerId, $productId, $packagingId = NULL, $quantity = NULL, $datetime = NULL, $rate = 1, $chargeVat = 'no')
    {
    	$listId = self::getListForCustomer($customerClass, $customerId, $datetime);
    	$rec = new stdClass();
    	$rec->price = price_ListRules::getPrice($listId, $productId, $packagingId, $datetime);
    	
    	$listRec = price_Lists::fetch($listId);
    	 
    	// Ако е избрано да се връща отстъпката спрямо друга политика
    	if(!empty($listRec->discountCompared)){
    		 
    		// Намираме цената по тази политика и намираме колко % е отстъпката/надценката
    		$comparePrice = price_ListRules::getPrice($listRec->discountCompared, $productId, $packagingId, $datetime);
    		if($comparePrice){
    			$disc = ($rec->price - $comparePrice) / $comparePrice;
    			$discount = round(-1 * $disc, 4);
    			
    			// Ще показваме цената без отстъпка и отстъпката само ако отстъпката е положителна
    			// Целта е да не показваме надценката а само отстъпката
    			if($discount > 0){
    				
    				// Подменяме цената за да може като се приспадне отстъпката и, да се получи толкова колкото тя е била
    				$rec->discount = round(-1 * $disc, 4);
    				$rec->price  = $comparePrice;
    			}
    		}
    	}
    	
    	return $rec;
    }
    
    
    /**
     * Помощна функция, добавяща 23:59:59 ако е зададена дата без час
     */
	public static function canonizeTime(&$datetime)
	{
		if(!$datetime) {
	       $datetime = dt::verbal2mysql();
	    } else { 
	       if(strlen($datetime) == 10) {
	          list($d, $t) = explode(' ', dt::verbal2mysql());
	          if($datetime == $d) {
	             $datetime = dt::verbal2mysql();
	          } else {
	             $datetime .= ' 23:59:59';
	          }
	      }
	   }
	}
}
