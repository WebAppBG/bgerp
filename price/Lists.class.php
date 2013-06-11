<?php



/**
 * Ценоразписи за продукти от каталога
 *
 *
 * @category  bgerp
 * @package   price
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Ценоразписи от каталога
 */
class price_Lists extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Ценови политики';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_Rejected, plg_RowTools, price_Wrapper, plg_NoChange';
                    
    
    /**
     * Детайла, на модела
     */
    var $details = 'price_ListRules';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, title, parent, createdOn, createdBy';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'user';
    
    
    /**
     * Кой може да го промени?
     */
    var $canEdit = 'price,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'price,ceo';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'user';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'price,ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'price,ceo';
    
    
    /**
     * Поле за връзка към единичния изглед
     */
    var $rowToolsSingleField = 'title';

    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'price/tpl/SingleLayoutLists.shtml';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('title', 'varchar(128)', 'mandatory,caption=Наименование,hint=Наименование на ценовата политика,width=100%');
        $this->FLD('parent', 'key(mvc=price_Lists,select=title,allowEmpty)', 'caption=Наследява,noChange');
        $this->FLD('public', 'enum(no=Не,yes=Да)', 'caption=Публичен');
        $this->FLD('currency', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'notNull,caption=Валута,noChange');
        $this->FLD('vat', 'enum(yes=С начислен ДДС,no=Без ДДС)', 'mandatory,notNull,caption=ДДС,noChange'); 
        $this->FNC('customer', 'varchar', 'caption=Прикрепяне->Клиент,input=hidden');
        $this->FNC('validFrom', 'datetime', 'caption=Прикрепяне->В сила от,input=hidden');
        $this->FLD('cId', 'int', 'caption=Клиент->Id,input=hidden,silent');
        $this->FLD('cClass', 'class(select=title)', 'caption=Клиент->Клас,input=hidden,silent');
        $this->FLD('roundingPrecision', 'double', 'caption=Закръгляне->Точност');
        $this->FLD('roundingOffset', 'double', 'caption=Закръгляне->Отместване');

        $this->setDbUnique('title');
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
		
        if($rec->parent){
        	$form->setReadOnly('parent');
        }
        
        if($rec->cId && $rec->cClass) {
            $cMvc = cls::get($rec->cClass);
            expect($cRec = $cMvc->fetch($rec->cId));
            $cMvc->requireRightFor('single', $rec);
            $form->setField('public', 'input=none');
            $form->setField('customer', 'input');
            $form->setField('validFrom', 'input');
            $title = $cMvc->gettitleById($rec->cId);
            $rec->customer =  $title;
            $form->setReadonly('customer');
        }
        
        if(empty($rec->id)){
	        if($rec->cId && $rec->cClass){
	        	$rec->parent =  price_ListToCustomers::getListForCustomer($rec->cClass, $rec->cId);
	            $parentOptions = self::makeArray4select('title', "#id = '{$rec->parent}' OR #public = 'yes' OR (#cId = '{$rec->cId}' AND #cClass = '{$rec->cClass}')");
	            $form->setOptions('parent', $parentOptions);
	        } else {
	        	$conf = core_Packs::getConfig('price');
	            $rec->parent = $conf->PRICE_LIST_CATALOG;
	        }
        }
            

        if(!$rec->currency) {
            $rec->currency = acc_Periods::getBaseCurrencyCode();
        }
    }


    /**
     * Изпълнява се след създаване на нов набор от ценови правила
     */
    function on_AfterCreate($mvc, $rec)
    {
        if($rec->cId && $rec->cClass) {
            price_ListToCustomers::setPolicyToCustomer($rec->id,  $rec->cClass, $rec->cId);
        }
    }


 

    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if($rec->parent) {
            $row->parent = ht::createLink($row->parent, array('price_Lists', 'Single', $rec->parent));
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
        if($action == 'delete') {
            if($rec->id && (self::fetch("#parent = {$rec->id}") || price_ListToCustomers::fetch("#listId = {$rec->id}")) ) {
                $requiredRoles = 'no_one';
            }
        }
    }

    
    /**
     * След инсталирането на модела, създава двете базови групи с правила за ценообразуване
     * Себестойност - тук се задават цените на придобиване на стоките, продуктите и услугите
     * Каталог - това са цените които се публикуват
     */
    function on_AfterSetupMVC($mvc, $res)
    {
        $conf = core_Packs::getConfig('price');

        if(!$mvc->fetchField($conf->PRICE_LIST_COST, 'id')) {
            $rec = new stdClass();
            $rec->id = $conf->PRICE_LIST_COST;
            $rec->parent = NULL;
            $rec->title  = 'Себестойност';
            $rec->currency = acc_Periods::getBaseCurrencyCode();
            $rec->vat      = 'no';
            $rec->createdOn = dt::verbal2mysql();
            $rec->createdBy = -1;
            $mvc->save($rec, NULL, 'REPLACE');
        }
        
        if(!$mvc->fetchField($conf->PRICE_LIST_CATALOG, 'id')) {
            $rec = new stdClass();
            $rec->id = $conf->PRICE_LIST_CATALOG;
            $rec->parent = $conf->PRICE_LIST_COST;
            $rec->title  = 'Каталог';
            $rec->currency = acc_Periods::getBaseCurrencyCode();
            $rec->vat = 'yes';
            $rec->public = 'yes';
            $rec->createdOn = dt::verbal2mysql();
            $rec->createdBy = -1;
            $mvc->save($rec, NULL, 'REPLACE');
        }
    }
    
}