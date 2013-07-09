<?php 


/**
 * Документ за Вътрешно Паричен Трансфер
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_InternalMoneyTransfer extends core_Master
{
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf';
   
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Вътрешни банкови трансфери";
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    var $onlyFirstInThread = TRUE;
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    var $loadList = 'plg_RowTools, bank_Wrapper, bank_DocumentWrapper, plg_Printing,
     	plg_Sorting, doc_DocumentPlg, doc_plg_MultiPrint, bgerp_plg_Blank, acc_plg_Contable, acc_plg_DocumentSummary, plg_Search';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "tools=Пулт, number=Номер, reason, valior, amount, currencyId, state, createdOn, createdBy";
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'reason';
    
    
    /**
     * Заглавие на единичен документ
     */
    var $singleTitle = 'Вътрешен банков трансфер';
    
    
    /**
     * Икона на единичния изглед
     */
    var $singleIcon = 'img/16/money.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Vpt";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'bank, ceo';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'bank, ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'bank, ceo';
    
    
    /**
     * Кой може да го контира?
     */
    var $canConto = 'acc, bank, ceo';
    
    
    /**
     * Кой може да сторнира
     */
    var $canRevert = 'bank, ceo';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'bank/tpl/SingleInternalMoneyTransfer.shtml';
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "4.5|Финанси";
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'valior, reason, creditBank, debitBank';
    
    
	/**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('operationSysId', 'customKey(mvc=acc_Operations,key=systemId, select=name)', 'caption=Операция,width=100%,mandatory,silent');
    	$this->FLD('amount', 'double(decimals=2)', 'caption=Сума,width=6em,mandatory,summary=amount');
    	$this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута,width=6em');
    	$this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,width=6em,mandatory');
    	$this->FLD('reason', 'varchar(255)', 'caption=Основание,width=20em,input,mandatory');
    	$this->FLD('creditAccId', 'acc_type_Account()','caption=Кредит,width=300px,input=none');
    	$this->FLD('creditBank', 'key(mvc=bank_OwnAccounts, select=bankAccountId)','caption=От->Б. Сметка,width=300px');
    	$this->FLD('debitAccId', 'acc_type_Account()','caption=Дебит,width=300px,input=none');
        $this->FLD('debitCase', 'key(mvc=cash_Cases, select=name)','caption=Към->Каса,width=300px,input=none');
    	$this->FLD('debitBank', 'key(mvc=bank_OwnAccounts, select=bankAccountId)','caption=Към->Б. Сметка,width=300px,input=none');
        $this->FLD('state', 
            'enum(draft=Чернова, active=Активиран, rejected=Сторнирана, closed=Контиран)', 
            'caption=Статус, input=none'
        );
    }
	
    
	/**
	 *  Подготовка на филтър формата
	 */
	static function on_AfterPrepareListFilter($mvc, $data)
	{
		// Добавяме към формата за търсене търсене по Каса
		bank_OwnAccounts::prepareBankFilter($data, array('creditBank', 'debitBank'));
	}
	
	
    /**
     *  Добавяме помощник за избиране на сч. операция
     */
    public static function on_BeforeAction($mvc, &$tpl, $action)
    {
    	if ($action != 'add') {
            
            return;
        }
        
        if (!$mvc->haveRightFor($action)) {
            
            return;
        }

       // Има ли вече зададено основание? 
       if (Request::get('operationSysId', 'varchar')) {
            
           // Има основание - не правим нищо
           return;
        }
        
        $form = static::prepareReasonForm();
        $form->input();
        $form = $form->renderHtml();
        $tpl = $mvc->renderWrapping($form);
        
        return FALSE;
    }
    
    
    /**
     * Подготвяме формата от която ще избираме посоката на движението
     */
    static function prepareReasonForm()
    {
    	$form = cls::get('core_Form');
    	$form->method = 'GET';
    	$form->FNC('operationSysId', 'customKey(mvc=acc_Operations, key=systemId, select=name)', 'input,caption=Операция');
    	$form->title = 'Нов Вътрешен банков трансфер';
        $form->toolbar->addSbBtn('Напред', '', array('class'=>'btn-next'), 'ef_icon = img/16/arrow_right.png');
        $form->toolbar->addBtn('Отказ', toUrl(array($this, 'list')), 'ef_icon = img/16/close16.png');
        
        $options = acc_Operations::getPossibleOperations(get_called_class());
        $form->setOptions('operationSysId', $options);
        
        return $form;
    }
    
    
    /**
     * Подготовка на формата за добавяне
     */
    static function on_AfterPrepareEditForm($mvc, $res, $data)
    { 
    	$form = &$data->form;
    	
    	// Очакваме и намираме коя е извършената операция
    	if(!$form->rec->id) {
    		expect($operationSysId = Request::get('operationSysId'));
    	} else {
    		$operationSysId = $form->rec->operationSysId;
    	}
    	
    	$operation = acc_Operations::getOperationInfo($operationSysId);
      
    	// Трябва документа да поддържа тази операция
    	$classId = core_Classes::fetchIdByName(get_called_class());
    	expect($operation->documentSrc == $classId, 'Този документ не поддържа избраната операция');
        $ownAccounts = bank_OwnAccounts::getOwnAccounts();
        
    	switch($operationSysId) {
        	case "bank2bank":
        		$form->setField("debitBank", "input");
        		$form->setOptions('debitBank', $ownAccounts);
        		break;
        	case "bank2case":
        		$form->setField("debitCase", "input");
        		break;
        }
       
        $form->setOptions('creditBank', $ownAccounts);
        $form->setReadOnly('operationSysId');
        $today = dt::verbal2mysql();
        $form->setDefault('valior', $today);
        $form->setDefault('currencyId', acc_Periods::getBaseCurrencyId($today));
      	$form->setDefault('creditBank', bank_OwnAccounts::getCurrent());
    }
    
     
    /**
     * Проверка след изпращането на формата
     */
    function on_AfterInputEditForm($mvc, $form)
    { 
    	if ($form->isSubmitted()){
    		
    		$rec = &$form->rec;
    		
		    $operation = acc_Operations::fetchbySysId($rec->operationSysId);
    		$rec->debitAccId = $operation->debitAccount;
    		$rec->creditAccId = $operation->creditAccount;
    		
    		// Проверяваме дали валутите на дебитната сметка съвпадат
    		// с тези на кредитната
    		$mvc->validateForm($form);
    		$form->rec->folderId = bank_OwnAccounts::forceCoverAndFolder($form->rec->creditBank);
    	}
    }
    
    
    /**
     * При Банка -> Каса
     *    Валутата на касата към която прехвърляме приема стойноста на
     *    валутата на сметката от която местим
     * При Банка -> Банка 
     *    Проверява дали банковата сметка към която прехвърляме да е 
     *    същата като тази на банката от която местим, ако не - сетва 
     *    грешка
     * 
     * @param core_Form $form 
     */
    function validateForm($form)
    {
    	$rec = &$form->rec;
    	$creditInfo = bank_OwnAccounts::getOwnAccountInfo($rec->creditBank);
    		
    	if($rec->operationSysId == 'bank2bank') {
    			
	    	// Двете банкови сметки трябва да са различни
	    	if($rec->creditBank == $rec->debitBank) {
	    			$form->setError("debitBank", 'Дестинацията е една и съща !!!');
	    			return;
	    	} 
	    			
	    	$debitInfo = bank_OwnAccounts::getOwnAccountInfo($rec->debitBank);
	    	if($creditInfo->currencyId != $debitInfo->currencyId) {
	    		$form->setError("debitBank, creditBank", 'Банковите сметки не са в една валута !!!');
	    		return;
	    	} 
	    	
	    	if($creditInfo->currencyId != $rec->currencyId) {
	    			$form->setError("debitBank, creditBank", 'Банковите сметки не са в посочената валута !!!');
	    			return;
	    	}
    	} elseif($rec->operationSysId == 'bank2case') {
    		 if($creditInfo->currencyId != $rec->currencyId) {
    		  	 $form->setError("debitEnt1,creditEnt1", 'Банковата сметка не е в посочената валута !!!');
    		  	 return;
    		}
    	} 
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->number = static::getHandle($rec->id);
    	if($fields['-single']) {
    		$row->currency = currency_Currencies::getCodeById($rec->currencyId);
    		
    		// Изчисляваме равностойността на сумата в основната валута
    		
	    	if($rec->rate != '1') {
	    		$double = cls::get('type_Double');
	    		$double->params['decimals'] = 2;
	    		$row->equals = currency_CurrencyRates::convertAmount($rec->amount, $rec->valior, $row->currency);
    			$row->baseCurrency = acc_Periods::getBaseCurrencyCode($rec->valior);
    		}
    		
    		// Показваме заглавието само ако не сме в режим принтиране
		    if(!Mode::is('printing')){
		    	$row->header = $mvc->singleTitle . "&nbsp;&nbsp;<b>{$row->ident}</b>" . " ({$row->state})" ;
		    }
    	}
    }
    
    
    /**
     * Поставя бутони за генериране на други банкови документи възоснова
     * на този, само ако документа е "чернова".
     */
	static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	if($data->rec->state == 'draft') {
	    	$rec = $data->rec;
	    	$data->toolbar->addBtn('Нареждане разписка', array('bank_CashWithdrawOrders', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE, ''));
	    }
    }
    
    
	/**
   	 *  Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
   	 *  Създава транзакция която се записва в Журнала, при контирането
   	 */
    public static function getTransaction($id)
    {
    	// Извличаме записа
        expect($rec = self::fetchRec($id));
        
        ($rec->debitCase) ? $debitArr = array('cash_Cases', $rec->debitCase) : $debitArr = array('bank_OwnAccounts', $rec->debitBank);
        $currencyCode = currency_Currencies::getCodeById($rec->currencyId);
        $amount = currency_CurrencyRates::convertAmount($rec->amount, $rec->valior, $currencyCode);
        $entry = array(
            'amount' => $amount,
            'debit' => array(
                $rec->creditAccId,
        		array('bank_OwnAccounts', $rec->creditBank),
        		array('currency_Currencies', $rec->currencyId),
                'quantity' => $rec->amount
            ),
            'credit' => array(
                $rec->debitAccId,
            	$debitArr,
            	array('currency_Currencies', $rec->currencyId),
                'quantity' => $rec->amount
            ),
        );
      	
      	// Подготвяме информацията която ще записваме в Журнала
        $result = (object)array(
            'reason' => $rec->reason,   // основанието за ордера
            'valior' => $rec->valior,   // датата на ордера
            'entries' => array($entry)
        );
        
        return $result;
    }
    
    
	/**
     * @param int $id
     * @return stdClass
     * @see acc_TransactionSourceIntf::getTransaction
     */
    public static function finalizeTransaction($id)
    {
        $rec = self::fetchRec($id);
        $rec->state = 'closed';
                
        return self::save($rec);
    }
    
    
    /**
     * @param int $id
     * @return stdClass
     * @see acc_TransactionSourceIntf::rejectTransaction
     */
    public static function rejectTransaction($id)
    {
        $rec = self::fetch($id, 'id,state,valior');
        
        if ($rec) {
            static::reject($id);
        }
    }
    
    
	/**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        // Може да създаваме документ-а само в дефолт папката му
        if ($folderId == static::getDefaultFolder(NULL, FALSE) || doc_Folders::fetchCoverClassName($folderId) == 'bank_OwnAccounts') {
        	
        	return TRUE;
        } 
        	
       return FALSE;
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = $rec->reason;
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
		$row->recTitle = $rec->reason;
		
        return $row;
    }
}
