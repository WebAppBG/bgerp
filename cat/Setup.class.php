<?php


/**
 * Коя да е основната мярка на универсалните артикули
 */
defIfNot('CAT_DEFAULT_MEASURE_ID', '');


/**
 * Показване на компонентите при вложени рецепти, Макс. брой
 */
defIfNot('CAT_BOM_MAX_COMPONENTS_LEVEL', 3);


/**
 * Колко от последно вложените ресурси да се показват в мастъра на рецептите
 */
defIfNot('CAT_BOM_REMEMBERED_RESOURCES', 20);


/**
 * Дефолт свойства на нови артикули в папките на клиенти
 */
defIfNot('CAT_DEFAULT_META_IN_CONTRAGENT_FOLDER', 'canSell,canManifacture,canStore');


/**
 * Дефолт свойства на нови артикули в папките на доставчици
 */
defIfNot('CAT_DEFAULT_META_IN_SUPPLIER_FOLDER', 'canBuy,canConvert,canStore');


/**
 * class cat_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с продуктите
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версията на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'cat_Products';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Каталог на стандартните артикули";
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'cond=0.1';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'cat_UoM',
            'cat_Groups',
    		'cat_Categories',
            'cat_Products',
            'cat_products_Params',
            'cat_products_Packagings',
    		'cat_products_VatGroups',
            'cat_Params',
    		'cat_Boms',
    		'cat_BomDetails',
    		'cat_ProductTplCache',
    		'migrate::migrateGroups',
    		'migrate::migrateProformas',
    		'migrate::removeOldParams1',
    		'migrate::updateDocs',
    		'migrate::truncatCache',
            'migrate::fixProductsSearchKeywords',
    		'migrate::replacePackagings',
    		'migrate::updateProductsNew',
    		'migrate::deleteCache1',
    		'migrate::updateParams',
    		'migrate::addClassIdToParams',
    		'migrate::updateBomType'
        );


    /**
     * Роли за достъп до модула
     */
    var $roles = 'cat,sales,purchase,techno';
 
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(1.42, 'Артикули', 'Каталог', 'cat_Products', 'default', "powerUser"),
        );


    /**
     * Дефинирани класове, които имат интерфейси
     */
    var $defClasses = "cat_GeneralProductDriver, cat_reports_SalesArticle";


    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
    		'CAT_BOM_REMEMBERED_RESOURCES' => array("int", 'caption=Колко от последно изпозлваните ресурси да се показват в рецептите->Брой'),
    		'CAT_DEFAULT_META_IN_CONTRAGENT_FOLDER' => array("set(canSell=Продаваем,canBuy=Купуваем,canStore=Складируем,canConvert=Вложим,fixedAsset=Дълготраен актив,canManifacture=Производим)", 'caption=Свойства по подразбиране в папка->На клиент,columns=2'),
    		'CAT_DEFAULT_META_IN_SUPPLIER_FOLDER' => array("set(canSell=Продаваем,canBuy=Купуваем,canStore=Складируем,canConvert=Вложим,fixedAsset=Дълготраен актив,canManifacture=Производим)", 'caption=Свойства по подразбиране в папка->На доставчик,columns=2'),
    		'CAT_DEFAULT_MEASURE_ID' => array("key(mvc=cat_UoM,select=name,allowEmpty)", 'optionsFunc=cat_UoM::getUomOptions,caption=Основна мярка на универсалните артикули->Мярка'),
    		'CAT_BOM_MAX_COMPONENTS_LEVEL' => array("int(min=0)", 'caption=Вложени рецепти - нива с показване на компонентите->Макс. брой'),
    );

    
    /**
     * Настройки за Cron
     */
    var $cronSettings = array(
    		array(
    				'systemId' => "Close Old Private Products",
    				'description' => "Затваряне на частните артикули, по които няма движения",
    				'controller' => "cat_Products",
    				'action' => "closePrivateProducts",
    				'period' => 21600,
    				'offset' => 60,
    				'timeLimit' => 200
    		),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $html = parent::install();
        
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('productsImages', 'Илюстрация на продукта', 'jpg,jpeg,png,bmp,gif,image/*', '3MB', 'user', 'every_one');
        
        return $html;
    }
    

    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
    
    
    /**
     * Миграция на мета данните на групите
     */
    public function migrateGroups()
    {
    	$Set = cls::get('type_Set');
    	
    	$query = cat_Groups::getQuery();
    	while($rec = $query->fetch()){
    		$meta = type_Set::toArray($rec->meta);
    		if(isset($meta['materials'])){
    			$meta['canStore'] = 'canStore';
    			$meta['canConvert'] = 'canConvert';
    			unset($meta['materials']);
    		}
    		
    		$rec->meta = $Set->fromVerbal($meta);
    		cat_Groups::save($rec, 'meta');
    	}
    }
    
    
    /**
     * Изтрива стари параметри
     */
    public function removeOldParams1()
    {
    	foreach (array('vat', 'vatGroup') as $sysId){
    		if($vRec = cat_Params::fetch("#sysId = '{$sysId}'")){
    			cat_products_Params::delete("#paramId = '{$vRec->id}'");
    			cat_Params::delete($vRec->id);
    		}
    	}
    }
    
    
    /**
     * Временна миграция
     */
    public function migrateProformas()
    {
    	if(core_Packs::fetch("#name = 'sales'")){
    		$Detail = cls::get('sales_ProformaDetails');
    		
    		if($Detail::count()){
    			$query = $Detail->getQuery();
    			$productId = cat_Products::getClassId();
    			while($rec = $query->fetch()){
    				if($rec->classId != $productId){
    					$rec->classId = $productId;
    					$Detail->save_($rec);
    				}
    			}
    		}
    	}
    }
    
    
    /**
     * Ъпдейтване на старите задания и рецепти
     */
    public function updateDocs()
    {
    	$bomQuery = cat_Boms::getQuery();
    	$bomQuery->where("#productId IS NULL");
    	while($bRec = $bomQuery->fetch()){
    		$origin = doc_Containers::getDocument($bRec->originId);
    		$bRec->productId = $origin->that;
    		cat_Boms::save($bRec, 'productId');
    	}
    	
    	if(core_Packs::fetch("#name = 'planning'")){
    		$jQuery = planning_Jobs::getQuery();
    		$jQuery->where("#productId IS NULL");
    		while($jRec = $jQuery->fetch()){
    			$origin = doc_Containers::getDocument($jRec->originId);
    			$jRec->productId = $origin->that;
    			planning_Jobs::save($jRec, 'productId');
    		}
    	}
    }
    
    
    /**
     * Изтриваме кеша
     */
    public function truncatCache()
    {
    	cat_ProductTplCache::truncate();
    }
    
    
    /**
     * Оправя ключовите думи на артикулите
     */
    public static function fixProductsSearchKeywords()
    {
    	$query = cat_Products::getQuery();
    	
    	while($rec = $query->fetch()) {
    		if(cls::load($rec->innerClass, TRUE)){
    			try {
    				cat_Products::save($rec, 'searchKeywords');
    			} catch (core_exception_Expect $e) {
    				continue;
    			}
    		}
    	}
    }
    
    
    /**
     * Миграционна функция
     */
    function replaceBoms()
    {
    	$Bom = cls::get('cat_BomDetails');
    	$bomQuery = $Bom->getQuery();
    	
    	while ($bomRec = $bomQuery->fetch()){
    		if($bomRec->resourceId == 1147){
    			$r = cat_products_Packagings::fetch(15);
    	
    			$bomRec->packagingId = $r->packagingId;
    			$bomRec->quantityInPack = $r->quantity;
    	
    			$Bom->save($bomRec, NULL, 'REPLACE');
    		} elseif($bomRec->resourceId == 1151){
    			$r = cat_products_Packagings::fetch(7);
    	
    			$bomRec->packagingId = $r->packagingId;
    			$bomRec->quantityInPack = $r->quantity;
    	
    			$Bom->save($bomRec, NULL, 'REPLACE');
    		} elseif($bomRec->resourceId == 1145){
    			$r = cat_products_Packagings::fetch(11);
    	
    			$bomRec->packagingId = $r->packagingId;
    			$bomRec->quantityInPack = $r->quantity;
    	
    			$Bom->save($bomRec, NULL, 'REPLACE');
    		}
    	}
    	 
    	unset($bomRec);
    	$Dp = cls::get('planning_DirectProductNoteDetails');
    	$dQuery = $Dp->getQuery();
    	
    	while ($bomRec = $dQuery->fetch()){
    		
    		if($bomRec->productId == 1147){
    			$r = cat_products_Packagings::fetch(15);
    		
    			$bomRec->packagingId = $r->packagingId;
    			$bomRec->quantityInPack = $r->quantity;
    	
    			$Dp->save($bomRec, NULL, 'REPLACE');
    		} elseif($bomRec->productId == 1151){
    			$r = cat_products_Packagings::fetch(7);
    			$bomRec->packagingId = $r->packagingId;
    			$bomRec->quantityInPack = $r->quantity;
    	
    			$Dp->save($bomRec, NULL, 'REPLACE');
    		} elseif($bomRec->productId == 1145){
    			$r = cat_products_Packagings::fetch(11);
    			
    			$bomRec->packagingId = $r->packagingId;
    			$bomRec->quantityInPack = $r->quantity;
    			
    			$Dp->save($bomRec, NULL, 'REPLACE');
    		}
    	}
    }
    
    
    function replacePackagings()
    {
    	core_App::setTimeLimit(400);
    	 
    	if(!cls::load('cat_Packagings', TRUE)) return;
    	 
    	$Packs = cls::get('cat_Packagings');
    	$Packs->setupMvc();
    	 
    	$Pos = cls::get('pos_Reports');
    	$Pos->setupMvc();
    	 
    	$Cat = cls::get('cat_Categories');
    	$Cat->setupMvc();
    	 
    	$Uom = cls::get('cat_UoM');
    	$Uom->setupMvc();
    	 
    	$Products = cls::get('cat_Products');
    	$Products->setupMvc();
    	 
    	$Pl = cls::get('price_ListDocs');
    	$Pl->setupMvc();
    	 
    	acc_Balances::logInfo("Започване на миграцията на ОПАКОВКИТЕ");
    	 
    	$packs = array();
    	$pQuery = cat_Packagings::getQuery();
    	while($pRec = $pQuery->fetch()){
    		$name = mb_strtolower($pRec->name);
    		if($name == '(брой)' || $name == 'бройка'){
    			$name = 'брой';
    		} elseif($name == 'хил.бр.'){
    			$name = 'хиляди бройки';
    		}
    
    		if($name == 'хиляди бройки' || $name == 'брой'){
    			$pRec->showContents = 'no';
    		}
    
    		$nRec = (object)array('name' => $name, 'shortName' => $name, 'type' => 'packaging', 'round' => $pRec->round, 'showContents' => $pRec->showContents);
    		if(!$Uom->isUnique($nRec, $fields, $exRec)){
    			$nRec->id = $exRec->id;
    			$nRec->type = $exRec->type;
    			 
    			if($exRec->shortName){
    				$nRec->shortName = $exRec->shortName;
    			}
    			 
    			$exRecs[$nRec->id] = $exRec;
    		}
    
    		$Uom->save($nRec, NULL, 'IGNORE');
    		$packs[$pRec->id] = $nRec->id;
    	}
    	 
    	$brRec = cat_UoM::fetch("#name = 'брой'");
    	$brRec->showContents = 'no';
    	$Uom->save($brRec);
    	 
    	$hbrRec = cat_UoM::fetch("#name = 'хиляди бройки'");
    	$hbrRec->showContents = 'no';
    	$Uom->save($hbrRec);
    	 
    	$packQuery = cat_products_Packagings::getQuery();
    	 
    	while($pRec = $packQuery->fetch()){
    		$pRec->packagingId = $packs[$pRec->packagingId];
    		cls::get('cat_products_Packagings')->save_($pRec, NULL, 'REPLACE');
    	}
    	 
    	$lQuery = price_ListDocs::getQuery();
    	$lQuery->where('#packagings IS NOT NULL');
    	$lQuery->show('packagings');
    	while($lRec = $lQuery->fetch()){
    		$packagings = keylist::toArray($lRec->packagings);
    
    		$newPacks = array();
    		foreach ($packagings as $p){
    			$val = $packs[$p];
    			$newPacks[$val] = $val;
    		}
    
    		$keylist = keylist::fromArray($newPacks);
    		$lRec->packagings = $keylist;
    
    		try{
    			cls::get('price_ListDocs')->save_($lRec, 'packagings');
    		} catch(core_exception_Expect $e){
    		    
    		    reportException($e);
    		}
    	}
    	 
    	sales_Sales::logInfo(ht::arrayToHtml($packs));
    	
    	$details = array('sales_SalesDetails',
    			'purchase_PurchasesDetails',
    			'store_ShipmentOrderDetails',
    			'store_ReceiptDetails',
    			'sales_InvoiceDetails',
    			'sales_QuotationsDetails',
    			'purchase_InvoiceDetails',
    			'cat_BomDetails',
    			'pos_Favourites',
    			'sales_ProformaDetails',
    			'store_TransfersDetails',
    			'planning_ConsumptionNoteDetails',
    			'planning_ProductionNoteDetails',
    			'planning_DirectProductNoteDetails',
    			'store_ConsignmentProtocolDetailsReceived',
    			'store_ConsignmentProtocolDetailsSend',
    			'sales_ServicesDetails',
    			'purchase_ServicesDetails',
    	);
    	 
    	foreach ($details as $Det){
    		$Det = cls::get($Det);
    		$Det->setupMvc();
    
    		$query = $Det->getQuery();
    
    		$count = 0;
    		$recsToSave = array();
    		while($dRec = $query->fetch()){
    			if($dRec->packagingId){
    				if(isset($packs[$dRec->packagingId])){
    					$dRec->packagingId = $packs[$dRec->packagingId];
    
    					$recsToSave[] = $dRec;
    				}
    			} else {
    				if($Det->className == 'cat_BomDetails'){
    					if(!$dRec->resourceId) continue;
    						
    					if(empty($measureArr[$dRec->resourceId])){
    						$measureArr[$dRec->resourceId] = cat_Products::fetchField($dRec->resourceId, 'measureId');
    					}
    					$dRec->packagingId = $measureArr[$dRec->resourceId];
    					$recsToSave[] = $dRec;
    						
    				} else {
    					if(empty($measureArr[$dRec->productId]) && isset($dRec->productId)){
    						$measureArr[$dRec->productId] = cat_Products::fetchField($dRec->productId, 'measureId');
    					}
    					$dRec->packagingId = $measureArr[$dRec->productId];
    					$recsToSave[] = $dRec;
    				}
    			}
    			 
    			$count++;
    		}
    
    		if(count($recsToSave)){
    			sales_Sales::logInfo("$Det->className: {$count}");
    			$Det->saveArray_($recsToSave);
    		}
    	}
    	 
    	$recsToSave = array();
    	$repQuery = pos_Reports::getQuery();
    	while($repRec = $repQuery->fetch()){
    		$add = FALSE;
    		if($repRec->details['receiptDetails']){
    
    			foreach ($repRec->details['receiptDetails'] as $d){
    				if($d->action != 'sale') continue;
    				 
    				if(isset($packs[$d->pack])){
    					$d->pack = $packs[$d->pack];
    					$add = TRUE;
    				}
    			}
    
    			if($add){
    				$recsToSave[] = $repRec;
    			}
    		}
    	}
    
    	cls::get('pos_Reports')->setupMvc();
    	if(count($recsToSave)){
    		cls::get('pos_Reports')->saveArray_($recsToSave);
    	}
    
    	$recsToSave = $measureArr = array();
    
    	cls::get('pos_ReceiptDetails')->setupMvc();
    	$rQuery = pos_ReceiptDetails::getQuery();
    	 
    	$rQuery->where("#action LIKE '%sale%'");
    	while($rRec = $rQuery->fetch()){
    		if(isset($packs[$rRec->value])){
    			$rRec->value = $packs[$rRec->value];
    			$recsToSave[] = $rRec;
    		}
    	}
    
    	if(count($recsToSave)){
    		cls::get('pos_ReceiptDetails')->saveArray_($recsToSave);
    	}
    }
    
    
    /**
     * Миграция на артикулите
     */
    function updateProductsNew()
    {
    	if(!cat_Products::count()) return;
    	
    	core_App::setTimeLimit(700);
    	
    	$Products = cls::get('cat_Products');
    	$query = $Products->getQuery();
    	
		$query->orderBy('id', 'ASC');
    	while($rec = $query->fetch()){
    		try{
    			$Products->save_($rec);
    		} catch(core_exception_Expect $e){
    			
    		}
    	}
    }
    
    
    /**
     * Изчистване на кеша на артикулите
     */
    public function deleteCache1()
    {
    	cat_ProductTplCache::truncate();
    }
    
    
    /**
     * Ъпдейтва параметрите
     */
    function updateParams()
    {
    	$map = array('size'    => 'cond_type_Double',
    			'weight'  => 'cond_type_Double',
    			'volume'  => 'cond_type_Double',
    			'double'  => 'cond_type_Double',
    			'int'     => 'cond_type_Int',
    			'varchar' => 'cond_type_Varchar',
    			'text'    => 'cond_type_Text',
    			'date'    => 'cond_type_Date',
    			'percent' => 'cond_type_Percent',
    			'enum'    => 'cond_type_Enum',
    			'density' => 'cond_type_Double',
    			'time'    => 'cond_type_Time',
    	);
    	 
    	$query = cat_Params::getQuery();
    	$query->where("#driverClass IS NULL");
    	 
    	try{
    		while($rec = $query->fetch()){
    			if($rec->id == 21 && $rec->name == 'МПС №'){
    				$rec->options = 'ВТ 4250 ВВ,ВТ 6249 ВК,ВТ 0507 ВН,ВТ 2130 ВН,ВТ 7009 ВТ,ВТ 7119 АТ,ВТ 4969 ВТ,ВТ 3963 АХ';
    			}
    			 
    			if($rec->type == 'size' && empty($rec->suffix)){
    				$rec->suffix = 'cm';
    			}
    			 
    			$newClass = $map[$rec->type];
    			core_Classes::add($newClass);
    			 
    			$rec->driverClass = cls::get($newClass)->getClassId();
    			cls::get('cat_Params')->save_($rec);
    		}
    		
    		$pQuery = cat_products_Params::getQuery();
    		$pQuery->EXT('type', 'cat_Params', 'externalName=type,externalKey=paramId');
    		$pQuery->where("#type = 'size'");
    		while($pRec = $pQuery->fetch()){
    			$pRec->paramValue *= 100;
    			cls::get('cat_products_Params')->save_($pRec);
    		}
    	} catch(core_exception_Expect $e){
    		reportException($e);
    	}
    }
    
    
    /**
     * Миграция на параметрите
     */
    public static function addClassIdToParams()
    {
    	$Params = cls::get('cat_products_Params');
    	$Params->setupMvc();
    	$classId = cat_Products::getClassId();
    	
    	try{
    		$query = $Params->getQuery();
    		$query->where("#classId IS NULL");
    		while($rec = $query->fetch()){
    			$rec->classId = $classId;
    			$Params->save_($rec, 'classId');
    		}
    	} catch(core_exception_Expect $e){
    		reportException($e);
    	}
    }
    
    
    /**
     * Ъпдейт на типа на рецептите
     */
    public function updateBomType()
    {
    	$Boms = cls::get('cat_Boms');
    	$Boms->setupMvc();
    	
    	$query = $Boms->getQuery();
    	while($rec = $query->fetch()){
    		try{
    			$firstDocument = doc_Threads::getFirstDocument($rec->threadId);
    			$type = 'sales';
    			if($firstDocument && $firstDocument->isInstanceOf('planning_Jobs')){
    				$type = 'production';
    			}
    			$rec->type = $type;
    			$Boms->save_($rec, 'type');
    		} catch(core_exception_Expect $e){
    			reportException($e);
    		}
    	}
    }
}
