<?php
/**
 * Клас 'purchase_Services'
 *
 * Мениджър на Приемателен протокол
 *
 *
 * @category  bgerp
 * @package   purchase
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class purchase_Services extends deals_ServiceMaster
{
    /**
     * Заглавие
     */
    public $title = 'Приемателни протоколи';


    /**
     * Абревиатура
     */
    public $abbr = 'Pps';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf, bgerp_DealIntf,acc_TransactionSourceIntf=purchase_transaction_Service';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, purchase_Wrapper, plg_Sorting, acc_plg_Contable, doc_DocumentPlg, plg_Printing,
                    acc_plg_DocumentSummary,
					doc_EmailCreatePlg, bgerp_plg_Blank, cond_plg_DefaultValues, doc_plg_TplManager, doc_plg_HidePrices,plg_Search';

    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, purchase';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, purchase';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo, purchase';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, purchase';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, purchase';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canConto = 'ceo, purchase';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт, valior,title=Документ, folderId, amountDelivered, createdOn, createdBy';


    /**
     * Детайла, на модела
     */
    public $details = 'purchase_ServicesDetails';
    

    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Приемателен протокол';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'purchase/tpl/SingleLayoutServices.shtml';

   
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.5|Логистика";
   
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'amountDelivered';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/shipment.png';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'valior, contragentClassId, contragentId, locationId, deliveryTime, folderId, id';
    
    
    /**
     * Главен детайл на модела
     */
    public $mainDetail = 'purchase_ServicesDetails';
    
    
    /**
     * Основна операция
     */
    protected static $defOperationSysId = 'buyServices';
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
    	'delivered' => 'lastDocUser|lastDoc',
    );
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        parent::setServiceFields($this);
        $this->FLD('activityCenterId', 'key(mvc=hr_Departments, select=name, allowEmpty)', 'caption=Център на дейност,mandatory,after=locationId');
    }
     
     
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if($rec->activityCenterId){
    		if(!Mode::is('text', 'xhtml') && !Mode::is('printing') && !Mode::is('pdf')){
    			$row->activityCenterId = hr_Departments::getHyperlink($rec->activityCenterId, TRUE);
    		}
    	}
    }
    
    
	/**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
        return tr("|Приемателен протокол|* №") . $rec->id;
    }
    
    
	/**
     * Зарежда шаблоните на продажбата в doc_TplManager
     */
    protected function setTemplates(&$res)
    {
    	$tplArr[] = array('name' => 'Приемателен протокол за услуги', 
    					  'content' => 'purchase/tpl/SingleLayoutServices.shtml', 'lang' => 'bg', 'narrowContent' => 'purchase/tpl/SingleLayoutServicesNarrow.shtml',
    					  'toggleFields' => array('masterFld' => NULL, 'purchase_ServicesDetails' => 'packagingId,packQuantity,weight,volume'));
    	$tplArr[] = array('name' => 'Приемателен протокол за услуги с цени', 
    					  'content' => 'purchase/tpl/SingleLayoutServicesPrices.shtml', 'lang' => 'bg', 'narrowContent' => 'purchase/tpl/SingleLayoutServicesPricesNarrow.shtml',
    					  'toggleFields' => array('masterFld' => NULL, 'purchase_ServicesDetails' => 'packagingId,packQuantity,packPrice,discount,amount'));
    	$tplArr[] = array('name' => 'Acceptance protocol',
		    			'content' => 'purchase/tpl/SingleLayoutServicesEN.shtml', 'lang' => 'en',  'narrowContent' => 'purchase/tpl/SingleLayoutServicesNarrowEN.shtml',
		    			'toggleFields' => array('masterFld' => NULL, 'purchase_ServicesDetails' => 'packagingId,packQuantity,weight,volume'));
    	$tplArr[] = array('name' => 'Acceptance protocol with prices',
		    			'content' => 'purchase/tpl/SingleLayoutServicesPricesEN.shtml', 'lang' => 'en',  'narrowContent' => 'purchase/tpl/SingleLayoutServicesPricesNarrowEN.shtml',
		    			'toggleFields' => array('masterFld' => NULL, 'purchase_ServicesDetails' => 'packagingId,packQuantity,packPrice,discount,amount'));
    	 
        $res .= doc_TplManager::addOnce($this, $tplArr);
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$dealInfo = static::getOrigin($data->form->rec)->getAggregateDealInfo();
    	$data->form->dealInfo = $dealInfo;
    	$data->form->setDefault('activityCenterId', $dealInfo->get('activityCenterId'));
    	
    	// Ако имаме само един център не показваме полето за избор на център
    	if(hr_Departments::count() == 1){
    		$data->form->setField('activityCenterId', 'input=none');
    	} else {
    		$defCenter = hr_Departments::fetchField("#systemId = 'emptyCenter'", 'id');
    		$data->form->setDefault('activityCenterId', $defCenter);
    	}
    	
    	$data->form->setDefault('received', core_Users::getCurrent('names'));
    }
    
    
    /**
     * След изпращане на формата
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
    	if ($form->isSubmitted()) {
    		$rec = &$form->rec;
    		$dealInfo = $form->dealInfo;
    		$operations = $dealInfo->get('allowedShipmentOperations');
    		$operation = $operations[$mvc::$defOperationSysId];
    		
    		$rec->accountId = $operation['credit'];
    		$rec->isReverse = (isset($operation['reverse'])) ? 'yes' : 'no';
    	}
    }
}