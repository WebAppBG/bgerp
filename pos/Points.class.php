<?php



/**
 * Мениджър за "Точки на продажба" 
 *
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class pos_Points extends core_Master {
    
    
    /**
     * Заглавие
     */
    var $title = "Точки на продажба";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, plg_Rejected, doc_FolderPlg,
                     pos_Wrapper, plg_Sorting, plg_Printing, plg_Current';

    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Точка на продажба";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт, title, caseId';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
   /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'title';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'ceo, pos';
    
    
    /**
     * Кой може да променя?
     */
    var $canWrite = 'ceo, pos';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo, pos';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,pos';
    
    
    /**
     * Икона за единичен изглед
     */
    var $singleIcon = 'img/16/cash-register-icon.png';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'admin, pos';
    

    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'pos/tpl/SinglePointLayout.shtml';
	
	
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('title', 'varchar(255)', 'caption=Наименование, mandatory');
    	$this->FLD('caseId', 'key(mvc=cash_Cases, select=name)', 'caption=Каса, mandatory');
        $this->FLD('storeId', 'key(mvc=store_Stores, select=name)', 'caption=Склад, mandatory');
        $this->FLD('policyId', 'key(mvc=price_Lists, select=title)', 'caption=Политика, silent, mandotory,width=15em');
    }
    
	
    /**
     * Създава дефолт контрагент за обекта, ако той вече няма създаден
     */
    static function on_AfterSave($mvc, &$id, $rec)
    {
    	if(!static::defaultContragent($id)) {
	    	$defaultContragent = new stdClass();
	    	$defaultContragent->name = "POS:" . $rec->id . "-Анонимен Клиент";
	    	crm_Persons::save($defaultContragent);
    	}
    }
    
    
    /**
     * Подготовка на формата за добавяне
     */
    static function on_AfterPrepareEditForm($mvc, $res, $data)
    { 
    	$conf = core_Packs::getConfig('price');
    	$data->form->setDefault('policyId', $conf->PRICE_LIST_CATALOG);
    }
    
    
    /**
     * Намира кой е дефолт контрагента на Точката на продажба
     * @param int $id - ид на точкта
     * @return mixed $id/FALSE - ид на контрагента или FALSE ако няма
     */
    public static function defaultContragent($id = NULL)
    {
    	($id) ? $pos = $id : $pos = pos_Points::getCurrent();
    	$query = crm_Persons::getQuery();
    	$query->where("#name LIKE '%POS:{$pos}%'");
    	if($rec = $query->fetch()) {
    		
    		return $rec->id;
    	}
    	
    	return FALSE;
    }
    
    
	/**
     * След подготовка на тулбара на единичен изглед.
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$urlArr = toUrl(array('pos_Receipts', 'single'));
    	$data->toolbar->addFnBtn("Отвори", "window.open('{$urlArr}')");
    }
    
    
    /**
     * Обработка по вербалното представяне на данните
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	if($rec->id == $mvc->getCurrent('id', NULL, FALSE)) {
    		$urlArr = toUrl(array('pos_Receipts', 'single'));
    		$row->currentPlg->append(ht::createFnBtn(tr("Отвори"), "window.open('{$urlArr}')", NULL, array('style' => 'margin-left:40px;margin-right:3px;display:inline')));
    	}
    }
    
    
	/**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'select' && isset($rec)){
    		$cashier = cash_Cases::fetchField($rec->caseId, 'cashier');
    		if($cashier == $userId){
    			$res = 'ceo, pos';
    		}
    	}
    }
    
    
	/**
	 * Преди подготовка на резултатите
	 */
	function on_BeforePrepareListRecs($mvc, $res, $data)
	{
		if(!haveRole('ceo')){
			
			// Показват се само точките на която каса
			// е касиер потребителя
			$cu = core_Users::getCurrent();
			$data->query->EXT('cashier', 'cash_Cases', 'externalKey=caseId');
			$data->query->where("#cashier = {$cu}");
		}
	}
}