<?php



/**
 * Мениджър за "Средства за плащане" 
 *
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class pos_Payments extends core_Manager {
    
    
    /**
     * Заглавие
     */
    var $title = "Средства за плащане";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, plg_State2, pos_Wrapper';

    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, title, change, state';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'ceo, pos';
    
    
    /**
     * Кой може да променя?
     */
    var $canWrite = 'ceo, pos';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'ceo, pos';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,pos';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,pos';
    

    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('title', 'varchar(255)', 'caption=Наименование');
    	$this->FLD('change', 'enum(yes=Да,no=Не)', 'caption=Ресто?,value=no');
    	
    	$this->setDbUnique('title');
    }
    
    
    /**
     * Записи за инициализиране на таблицата
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
    	$file = "pos/csv/PaymentMethods.csv";
    	$fields = array( 
	    	0 => "id", 
	    	1 => "title", 
	    	2 => "state", 
	    	3 => "change",);
    	
    	$cntObj = csv_Lib::importOnce($mvc, $file, $fields);
    	$res .= $cntObj->html;
    	
    	return $res;
    }
    
    
    /**
     * Връща масив от обекти, които са ид-та и заглавията на методите
     * @return array $payments
     */
    public static function fetchSelected()
    {
    	$payments = array();
    	$query = static::getQuery();
	    $query->where("#state = 'active'");
	    while($rec = $query->fetch()) {
	    	$payment = new stdClass();
	    	$payment->id = $rec->id;
	    	$payment->title = $rec->title;
	    	$payments[] = $payment;
	    }
	    
    	return $payments;
    }
    
    
    /**
     *  Метод отговарящ дали даден платежен връща ресто
     *  @param int $id - ид на метода
     *  @return boolean $res - дали връща или не връща ресто
     */
    public static function returnsChange($id)
    {
    	expect($rec = static::fetch($id), 'Няма такъв платежен метод');
    	($rec->change == 'yes') ? $res = TRUE : $res = FALSE;
    	
    	return $res;
    }
}