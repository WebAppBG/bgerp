<?php
/**
 * Клас 'sales_ClosedDeals'
 * Клас с който се приключва една продажба
 * 
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_ClosedDeals extends acc_ClosedDeals
{
    /**
     * Заглавие
     */
    public $title = 'Приключване на продажба';


    /**
     * Абревиатура
     */
    public $abbr = 'Cds';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'sales_Wrapper, acc_plg_Contable, plg_RowTools, plg_Sorting,
                    doc_DocumentPlg, doc_plg_HidePrices, acc_plg_Registry, plg_Search';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,sales';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,salesMaster';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,sales';
    
  
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,sales';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,sales';
    
	
	/**
	 * Кой може да контира документите?
	 */
	public $canConto = 'ceo,sales';
	
	
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Приключване на продажба';
   
    
    /**
     * Групиране на документите
     */ 
    public $newBtnGroup = "3.9|Търговия";
   
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'costAmount, incomeAmount';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'notes,docId,classId';
    
    
    /**
     * Имплементиране на интерфейсен метод
     * @see acc_ClosedDeals::getDocumentRow()
     */
    public function getDocumentRow($id)
    {
    	$row = parent::getDocumentRow($id);
    	$title = "Приключване на продажба #{$row->saleId}";
    	$row->title = $title;
    	$row->recTitle = $title;
    	
    	return $row;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->DOC_NAME = tr("ПРОДАЖБА");
    	if($rec->amount == 0){
    		$costAmount = $incomeAmount = 0;
    	} elseif($rec->amount > 0){
    		$incomeAmount = $rec->amount;
    		$costAmount = 0;
    		$row->type = tr('Приход');
    	} elseif($rec->amount < 0){
    		$costAmount = $rec->amount;
    		$incomeAmount = 0;
    		$row->type = tr('Разход');
    	}
    	
    	$row->costAmount = $mvc->fields['amount']->type->toVerbal($costAmount);
    	$row->incomeAmount = $mvc->fields['amount']->type->toVerbal($incomeAmount);
    }
    
    
    /**
     * Малко манипулации след подготвянето на формата за филтриране
     */
    static function on_AfterPrepareListFilter($mvc, &$data)
    {
    	$data->listFilter->view = 'horizontal';
    	$data->listFilter->showFields = 'search';
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list', 'show' => Request::get('show')), 'id=filter', 'ef_icon = img/16/funnel.png');
    	
        $data->listFilter->input(NULL, 'silent');
    }
    
    
    /**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото на имейл по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
        $handle = static::getHandle($id);
        $tpl = new ET(tr("Моля запознайте се с нашия документ") . ': #[#handle#]');
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
    }
    
    

    /**
     * Дали разликата на доставеното - платеното е в допустимите граници
     */
    public static function isSaleDiffAllowed($saleRec)
    {
    	$diff = round($saleRec->amountDelivered - $saleRec->amountPaid, 2);
    	$conf = core_Packs::getConfig('sales');
    	$res = ($diff >= -1 * $conf->SALE_CLOSE_TOLERANCE && $diff <= $conf->SALE_CLOSE_TOLERANCE);
    	
    	return $res;
    }
    
    
	/**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'add' || $action == 'conto') && isset($rec)){
    		
    		// Ако има ориджин
    		if($origin = $mvc->getOrigin($rec)){
    			$originRec = $origin->fetch();
    			
    			if($res == 'no_one') return;
    			
    			// Ако разликата между доставеното/платеното е по голяма, се изисква
    			// потребителя да има по-големи права за да създаде документа
    			if(!self::isSaleDiffAllowed($originRec)){
    				$res = 'ceo,salesMaster';
    			} else {
    				$res = 'ceo,sales';
    			}
    		}
    	}
    }
    
    
 	/**
     * Връща записа за начисляване на извънредния приход/разход
     * ------------------------------------------------------
     * Надплатеното: Dt:  411. Вземания от клиенти (Клиенти, Сделки, Валути)
     * 				 Ct: 7911. Надплатени по продажби
     * 
     * Недоплатеното: Dt: 6911. Отписани вземания по продажби
     * 				  Ct:  411. Вземания от клиенти (Клиенти, Сделки, Валути)
     */
    protected function getCloseEntry($amount, &$totalAmount, $docRec, $firstDoc)
    {
    	$entry = array();
    	
    	if($amount == 0) return $entry;
    	if($amount > 0){
    		
    		// Ако платеното е по-вече от доставеното (кредитно салдо)
    		$entry1 = array(
    				'amount' => -1 * currency_Currencies::round($amount),
    				'debit'  => array('7911',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),
    				'credit' => array('411',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that),
    						array('currency_Currencies', currency_Currencies::getIdByCode($docRec->currencyId)),
    						'quantity' => currency_Currencies::round(-1 * $amount / $docRec->currencyRate)),
    		);
    		
    		$entry2 = array('amount' => currency_Currencies::round($amount),
    						 'debit'  => array('7911',
    								array($docRec->contragentClassId, $docRec->contragentId),
    								array($firstDoc->className, $firstDoc->that)),
    						 'credit'  => array('700', array($docRec->contragentClassId, $docRec->contragentId),
    								array($firstDoc->className, $firstDoc->that)),
    						);
    		
    		static::$incomeAmount -= $amount;
    		
    	} elseif($amount < 0){
    		
    		// Ако платеното е по-малко от доставеното (дебитно салдо)
    		$entry1 = array(
    				'amount' => currency_Currencies::round($amount),
    				'credit'  => array('6911',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),
    				'debit' => array('411',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that),
    						array('currency_Currencies', currency_Currencies::getIdByCode($docRec->currencyId)),
    						'quantity' => currency_Currencies::round($amount / $docRec->currencyRate)),
    		);
    		
    		$entry2 = array('amount' => -1 * currency_Currencies::round($amount),
    				'debit'  => array('700', array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),
    				'credit'  => array('6911',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),);
    		
    		static::$incomeAmount += -1 * currency_Currencies::round($amount);
    		
    	}
    	
    	// Връщане на записа
    	return array($entry1, $entry2);
    }
    
    protected function transferIncomeToYear($dealInfo, $docRec, &$total, $firstDoc)
    {
    	$arr1 = array('700', array($docRec->contragentClassId, $docRec->contragentId), array($firstDoc->className, $firstDoc->that));
    	$arr2 = array('123', $this->year->id);
    	$total += abs(static::$incomeAmount);
    	
    	if(static::$incomeAmount > 0){
    		$debitArr = $arr2;
    		$creditArr = $arr1;
    	} else {
    		$debitArr = $arr1;
    		$creditArr = $arr2;
    	}
    	
    	$entry = array('amount' => abs(static::$incomeAmount), 'debit' => $debitArr, 'credit' => $creditArr);
    	
    	return $entry;
    }
    
    
    protected function transferIncome($dealInfo, $docRec, &$total, $firstDoc)
    {
    	$entries = array();
    	$balanceArr = $this->shortBalance->getShortBalance('701,706,703');
    	
    	$blAmountGoods = $this->shortBalance->getAmount('701,706,703');
    	$total += abs($blAmountGoods);
    	
    	if(!count($balanceArr)) return $entries;
    	
    	foreach ($balanceArr as $rec){
    		$arr1 = array('700', array($docRec->contragentClassId, $docRec->contragentId),
    					array($firstDoc->className, $firstDoc->that));
    		$arr2 = array($rec['accountSysId'], $rec['ent1Id'], $rec['ent2Id'], $rec['ent3Id'], 'quantity' => $rec['blQuantity']);
    		
    		static::$incomeAmount += $blAmountGoods;
    		
    		if($blAmountGoods > 0){
    			$debitArr = $arr1;
    			$creditArr = $arr2;
    		} else {
    			$debitArr = $arr2;
    			$creditArr = $arr1;
    		}
    		
    		$entries[] = array('amount' => abs($rec['blAmount']), 'debit' => $debitArr, 'credit' => $creditArr);
    	}
    	
    	return $entries;
    }
    
    
	/**
     * Прехвърля не неначисленото ДДС
     * За Продажба:
     * 		Dt: 4530. ДДС за начисляване
     * 		
     * 		Ct: 701. Приходи от продажби на Стоки и Продукти     (Клиенти, Сделки, Стоки и Продукти)
     * 			703. Приходи от продажби на услуги			     (Клиенти, Сделки, Услуги)
     * 			706. Приходи от продажба на суровини/материали   (Клиенти, Сделки, Суровини и Материали)
     * 
     */
    protected function transferVatNotCharged($dealInfo, $docRec, &$total, $firstDoc)
    {
    	$entries = array();
    	
    	$jRecs = acc_Journal::getEntries(array($firstDoc->className, $firstDoc->that));
    	
    	$blAmount = acc_Balances::getBlAmounts($jRecs, '4530')->amount;
    	$total += abs($blAmount);
    	
    	if($blAmount == 0) return $entries;
    	
    	if($blAmount < 0){
    		$entries = array('amount' => abs($blAmount),
	    					 'credit'  => array('4535'),
	            			 'debit' => array('4530', array($firstDoc->className, $firstDoc->that)));
    	} elseif($blAmount > 0){
    		$entries = array('amount' => $blAmount,
    						 'credit'  => array('4530', array($firstDoc->className, $firstDoc->that)),
    						 'debit' => array('411',
    										array($docRec->contragentClassId, $docRec->contragentId),
    										array($firstDoc->className, $firstDoc->that),
    										array('currency_Currencies', currency_Currencies::getIdByCode($dealInfo->get('currency'))),
    						 			'quantity' => $blAmount));
    		
    		static::$diffAmount  -= $blAmount;
    	}
    	
    	return $entries;
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     */
    public static function canAddToThread($threadId)
    {
    	// Можели да се добави към нишката
    	$res = parent::canAddToThread($threadId);
    	if(!$res) return FALSE;
    	
    	$firstDoc = doc_Threads::getFirstDocument($threadId);
    	
    	// Може само към нишка, породена от продажба
    	if(!($firstDoc->instance instanceof sales_Sales)) return FALSE;
    	
    	return TRUE;
    }
    
    
    /**
     * Ако има направени авансови плащания към сделката се приключва и аванса
     * Направените аванси са сумирани по валута, така за всяко авансово плащане в различна валута
     * има запис за неговото приключване
     *
     *
     * Приключване на аванс на продажба:
     * ------------------------------------------------------
     * Dt:  412. Задължения към клиенти (по аванси)
     * Ct:  411. Вземания от клиенти (Клиенти, Валути)
     */
    protected function trasnferDownpayments(bgerp_iface_DealAggregator $dealInfo, $docRec, &$total, $firstDoc)
    {
    	$entryArr = array();
    	
    	$docRec = $firstDoc->rec();
    	
    	$jRecs = acc_Journal::getEntries(array($firstDoc->className, $firstDoc->that));
    	
    	// Колко е направеното авансовото плащане
    	$downpaymentAmount = -1 * acc_Balances::getBlAmounts($jRecs, '412')->amount;
    	if($downpaymentAmount == 0) return $entryArr;
    	
    	// Валутата на плащането е тази на сделката
    	$currencyId = currency_Currencies::getIdByCode($dealInfo->get('currency'));
    	$amount = currency_Currencies::round($downpaymentAmount / $dealInfo->get('rate'), 2);
    	 
    	$entry = array();
    	$entry['amount'] = currency_Currencies::round($downpaymentAmount);
    	$entry['debit'] = array('412',
    			array($docRec->contragentClassId, $docRec->contragentId),
    			array($firstDoc->className, $firstDoc->that),
    			array('currency_Currencies', $currencyId),
    			'quantity' => $amount);
    	 
    	$entry['credit'] = array('411',
    			array($docRec->contragentClassId, $docRec->contragentId),
    			array($firstDoc->className, $firstDoc->that),
    			array('currency_Currencies', $currencyId),
    			'quantity' => $amount);
    	 
    	$total += $entry['amount'];
    	 
    	return $entry;
    }
}