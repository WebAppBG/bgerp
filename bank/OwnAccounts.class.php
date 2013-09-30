<?php



/**
 * Банкови сметки на фирмата
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_OwnAccounts extends core_Master {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'acc_RegisterIntf, bank_OwnAccRegIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, bank_Wrapper, acc_plg_Registry,
                     plg_Sorting, plg_Current, plg_LastUsedKeys, doc_FolderPlg, plg_Rejected';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    var $lastUsedKeys = 'bankAccountId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт, title, bankAccountId, type';
    
    
    /**
	 * Кое поле отговаря на кой работи с дадена сметка
	 */
	var $inChargeField = 'operators';
	
	
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'bank, ceo';
    
    
    /**
	* Кой може да селектира?
	*/
	var $canSelect = 'ceo,bank';
	
	
    /**
     * Кой може да пише?
     */
    var $canWrite = 'bankMaster, ceo';
    
    
    /**
	 * Кой може да селектира всички записи
	 */
	var $canSelectAll = 'ceo, bankMaster';
	
	
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'bank,ceo';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'bank,ceo';
    
    
    /**
     * Заглавие
     */
    var $title = 'Банкови сметки на фирмата';
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = 'Банкова сметка';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'title';
    
    
    /**
     * Всички записи на този мениджър автоматично стават пера в номенклатурата със системно име
     * $autoList.
     * 
     * @see acc_plg_Registry
     * @var string
     */
    var $autoList = 'bankAcc';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'bank/tpl/SingleLayoutOwnAccount.shtml';
    
    
    /**
     * Икона за единичен изглед
     */
    var $singleIcon = 'img/16/own-bank.png';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('bankAccountId', 'key(mvc=bank_Accounts,select=iban)', 'caption=Сметка,mandatory');
        $this->FLD('type', 'enum(current=Разплащателна,
            deposit=Депозитна,
            loan=Кредитна,
            personal=Персонална,
            capital=Набирателна)', 'caption=Тип,mandatory');
        $this->FLD('title', 'varchar(128)', 'caption=Наименование');
        $this->FLD('titulars', 'keylist(mvc=crm_Persons, select=name)', 'caption=Титуляри->Име,mandatory');
        $this->FLD('together',  'enum(together=Заедно,separate=Поотделно)', 'caption=Титуляри->Представляват');
        $this->FLD('operators', 'userList(roles=bank)', 'caption=Оператори,mandatory');
    }
    

    /**
     * Наша банкова сметка по подразбиране според клиента
     *
     * @see doc_ContragentDataIntf
     * @param stdClass $contragentInfo
     * @return int key(mvc=bank_OwnAccounts)
     */
    public static function getDefault($contragentInfo)
    {
        // @TODO
        return static::fetchField(1, 'id'); // За тест
    }
    
    
    /**
     * Обработка по формата
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
    	$optionAccounts = $mvc->getPossibleBankAccounts();
    	
    	$titulars = $mvc->getTitulars();
        
        $data->form->setOptions('bankAccountId', $optionAccounts);
        $data->form->setSuggestions('operators', $operators);
        $data->form->setSuggestions('titulars', $titulars);
    	
        // Номера на сметката неможе да се променя ако редактираме, за смяна на
        // сметката да се прави от bank_accounts
        if($data->form->rec->id) {
        	$data->form->setReadOnly('bankAccountId');
        }
    }
    
    
    /**
     * Връща всички Всички лица, които могат да бъдат титуляри на сметка
     * тези включени в група "Управители"
     */
    function getTitulars()
    {
    	$options = array();
    	$groupId = crm_Groups::fetchField("#name = 'Управители'", 'id');
    	$personQuery = crm_Persons::getQuery();
    	$personQuery->where("#groupList LIKE '%|{$groupId}|%'");
    	while($personRec = $personQuery->fetch()) {
    		$options[$personRec->id] = $personRec->name;
    	}   	
    	
    	if(count($options) == 0) {
    		return Redirect(array('crm_Persons', 'list'), NULL, 'Няма лица в група "Управители" за титуляри на "нашите сметки". Моля добавете !');
    	}
    	return $options;
    }
    
    
    /**
     * Подготовка на списъка от банкови сметки, между които можем да избираме
     * @return array $options - масив от потребители
     */
    function getPossibleBankAccounts()
    {
    	$bankAccounts = cls::get('bank_Accounts');
    	
    	// Извличаме само онези сметки, които са на нашата фирма и не са
        // записани в bank_OwnAccounts
        $ourCompany        = crm_Companies::fetchOurCompany();
        $queryBankAccounts = $bankAccounts->getQuery();
        $queryBankAccounts->where("#contragentId = {$ourCompany->id}");
        $queryBankAccounts->where("#contragentCls = {$ourCompany->classId}");
        $options = array();
        
        while($rec = $queryBankAccounts->fetch()) {
           if (!static::fetchField("#bankAccountId = " . $rec->id , 'id')) {
               $options[$rec->id] = $bankAccounts->getVerbal($rec, 'iban');
           }
        }
       
        return $options;
    }
    
    
    /**
     * Проверка дали може да се добавя банкова сметка в ownAccounts(Ако броя
     * на собствените сметки отговаря на броя на сметките на Моята компания в
     * bank_Accounts то неможем да добавяме нова сметка от този мениджър
     * @return boolean TRUE/FALSE - можем ли да добавяме нова сметка
     */
    function canAddOwnAccount()
    {
        $ourCompany = crm_Companies::fetchOurCompany();
    	
        $accountsQuery = bank_Accounts::getQuery();
    	$accountsQuery->where("#contragentId = {$ourCompany->id}");
        $accountsQuery->where("#contragentCls = {$ourCompany->classId}");
        $accountsNumber = $accountsQuery->count();
    	$ownAccountsQuery = $this->getQuery();
    	$ownAccountsNumber = $ownAccountsQuery->count();
    	
        if($ownAccountsNumber == $accountsNumber) {
    		return FALSE;
    	}
    	
    	return TRUE;
    }
    
    
    /**
     * Изчличане на цялата информация за сметката която е активна
     * @return bank_Accounts $acc - записа отговарящ на текущата ни сметка
     */
    static function getOwnAccountInfo($id = NULL)
    {
    	if($id) {
    		$ownAcc = static::fetch($id);
    	} else {
    		$ownAcc = static::fetch(static::getCurrent());
    	}
    	
    	$acc = bank_Accounts::fetch($ownAcc->bankAccountId);
    	if(!$acc->bank) {
    		$acc->bank = bglocal_Banks::getBankName($acc->iban);
    	}
    	if(!$acc->bic) {
    		$acc->bic = bglocal_Banks::getBankBic($acc->iban);
    	}
    	
    	return $acc;
    }
    

    /**
     * Изпълнява се след въвеждането на данните от формата
     */
    function on_AfterInputEditForm($mvc, $form)
    {
        $rec = $form->rec;
        
        if($form->isSubmitted()) {
            if(!$rec->title) {
                $rec->title = bank_Accounts::fetchField($rec->bankAccountId, 'iban');
            }
        }
    }
    
    
    /**
     * Обработка на ролите 
     */
    function on_AfterGetRequiredRoles($mvc, &$res, $action)
    {
     	if($action == 'add') {
     		if(!$mvc->canAddOwnAccount()) {
     			$res = 'no_one';
     		}
     	}
    }
    
    
    /*******************************************************************************************
     * 
     * ИМПЛЕМЕНТАЦИЯ на интерфейса @see crm_ContragentAccRegIntf
     * 
     ******************************************************************************************/
    
    
    /**
     * @see crm_ContragentAccRegIntf::getItemRec
     * @param int $objectId
     */
    static function getItemRec($objectId)
    {
        $result = NULL;
        if ($rec = static::fetch($objectId)) {
        	$account = bank_Accounts::fetch($rec->bankAccountId);
        	$cCode = currency_Currencies::getCodeById($account->currencyId);
            $result = (object)array(
                'num' => $rec->id,
				'title' => $cCode . " - " . $account->iban,
                'features' => 'foobar' // @todo!
            );
        }
        
        return $result;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     * @param int $objectId
     */
    static function itemInUse($objectId)
    {
        // @todo!
    }
    
    /**
     * КРАЙ НА интерфейса @see acc_RegisterIntf
     */
    
    
    /**
     * Връща Валутата и iban-a на всивки наши сметки разделени с "-"
     */
    static function getOwnAccounts()
    {
    	$Iban = cls::get('iban_Type');
    	$accounts = array();
    	$query = static::getQuery();
    	while($rec = $query->fetch()) {
    		$account = bank_Accounts::fetch($rec->bankAccountId);
    		$cCode = currency_Currencies::getCodeById($account->currencyId);
    		$verbal = $Iban->toVerbal($account->iban);
    		$accounts[$rec->id] = "{$cCode} - {$verbal}";
    	}
    	
    	return $accounts;
    }
    
    
	/**
     * Подготвя и осъществява търсене по банка, изпозлва се
     * в банковите документи
     * @param stdClass $data 
     * @param array $fields - масив от полета в полета в които ще се
     * търси по bankId
     */
    public static function prepareBankFilter(&$data, $fields = array())
    {
    	$data->listFilter->FNC('own', 'key(mvc=bank_OwnAccounts,select=bankAccountId,allowEmpty)', 'caption=Сметка,width=16em,silent');
		$data->listFilter->showFields .= ',own';
		$data->listFilter->setDefault('own', static::getCurrent('id', FALSE));
		$data->listFilter->input();
		if($filter = $data->listFilter->rec) {
			if($filter->own) {
				foreach($fields as $fld){
					$data->query->orWhere("#{$fld} = {$filter->own}");
				}
			}
		}
    }
    
    
	/**
	 * Преди подготовка на резултатите
	 */
	function on_BeforePrepareListRecs($mvc, $res, $data)
	{
		if(!haveRole('ceo,bankMaster')){
			
			// Показват се само записите за които отговаря потребителя
			$cu = core_Users::getCurrent();
			$data->query->where("#operators LIKE '%|{$cu}|%'");
		}
	}
}