<?php



/**
 * Документ за Приходни касови ордери
 *
 *
 * @category  bgerp
 * @package   cash
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cash_Pko extends cash_Document
{
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=cash_transaction_Pko, bgerp_DealIntf, email_DocumentIntf, doc_ContragentDataIntf';
   
    
    /**
     * Заглавие на мениджъра
     */
    public $title = "Приходни касови ордери";
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Приходен касов ордер';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/money_add.png';
    
    
    /**
     * Абревиатура
     */
    public $abbr = "Pko";
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    public $singleLayoutFile = 'cash/tpl/Pko.shtml';

    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.1|Финанси";
    
    
    /**
     * Кое поле отговаря на броилия парите
     */
    protected $personDocumentField = "depositor";
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	// Зареждаме полетата от бащата
    	parent::getFields($this);
    	$this->FLD('depositor', 'varchar(255)', 'caption=Контрагент->Броил,mandatory');
    }

    
    /**
     * Връща платежните операции
     */
    protected static function getOperations($operations)
    {
    	$options = array();
    	
    	// Оставяме само тези операции, в които се дебитира основната сметка на документа
    	foreach ($operations as $sysId => $op){
    		if($op['debit'] == static::$baseAccountSysId){
    			$options[$sysId] = $op['title'];
    		}
    	}
    	
    	return $options;
    }
}
