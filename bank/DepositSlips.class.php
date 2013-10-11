<?php 


/**
 * Документ за Вносни Бележки
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_DepositSlips extends core_Master
{
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'doc_DocumentIntf';
   
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Вносни бележки";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    var $loadList = 'plg_RowTools, bank_Wrapper, bank_TemplateWrapper, plg_Printing,
     	plg_Sorting, doc_DocumentPlg, acc_plg_DocumentSummary, doc_ActivatePlg,
     	plg_Search, doc_plg_MultiPrint, bgerp_plg_Blank, cond_plg_DefaultValues, doc_EmailCreatePlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "tools=Пулт, number=Номер, reason, valior, amount, currencyId, beneficiaryName, beneficiaryIban, state, createdOn, createdBy";
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'reason';
    
    
    /**
     * Кой може да създава
     */
    var $canAdd = 'bank, ceo';
    
    
    /**
     * Заглавие на единичен документ
     */
    var $singleTitle = 'Вносна бележка';
    

    /**
     * Икона на документа
     */
    var $singleIcon = 'img/16/vnb.png';

    
    /**
     * Абревиатура
     */
    var $abbr = "Vb";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'bank, ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'bank,ceo';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'bank,ceo';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'bank, ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'bank, ceo';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'bank/tpl/SingleDepositSlip.shtml';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'valior, reason, beneficiaryName, beneficiaryIban, execBank';

    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "4.91|Финанси";
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array(
    
    	'execBank' 		  => 'lastDocUser|lastDoc',
    	'execBankBranch'  => 'lastDocUser|lastDoc',
    	'execBankAdress'  => 'lastDocUser|lastDoc',
    	'beneficiaryName' => 'lastDocUser|lastDoc',
    	'beneficiaryIban' => 'lastDocUser|lastDoc',
    	'beneficiaryBank' => 'lastDocUser|lastDoc',
    	'depositor' 	  => 'lastDocUser|lastDoc',
    );
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('amount', 'double(decimals=2,max=2000000000,min=0)', 'caption=Сума,mandatory,width=6em,summary=amount');
    	$this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута,width=6em');
    	$this->FLD('reason', 'varchar(255)', 'caption=Основание,width=100%,mandatory');
    	$this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,width=6em,mandatory');
    	$this->FLD('execBank', 'varchar(255)', 'caption=До->Банка,width=16em,mandatory');
    	$this->FLD('execBankBranch', 'varchar(255)', 'caption=До->Клон,width=16em');
        $this->FLD('execBankAdress', 'varchar(255)', 'caption=До->Адрес,width=16em');
    	$this->FLD('beneficiaryName', 'varchar(255)', 'caption=Получател->Име,mandatory,width=16em');
    	$this->FLD('beneficiaryIban', 'iban_Type', 'caption=Получател->IBAN,mandatory,width=16em');
    	$this->FLD('beneficiaryBank', 'varchar(255)', 'caption=Получател->Банка,width=16em');
    	$this->FLD('depositor', 'varchar(255)', 'caption=Вносител->Име,mandatory');
    }
    
    
    /**
     *  Обработка на формата за редакция и добавяне
     */
    static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
    	$form = &$data->form;
    	$originId = $form->rec->originId;
    	
    	if($originId) {
    		
    		// Ако основанието е по банков документ намираме кой е той
    		$doc = doc_Containers::getDocument($originId);
    		$originRec = $doc->fetch();
    		
    		// Извличаме каквато информация можем от оригиналния документ
    		$form->setDefault('currencyId', $originRec->currencyId);
    		$form->setDefault('amount', $originRec->amount);
    		$form->setDefault('reason', $originRec->reason);
    		$form->setDefault('valior', $originRec->valior);

    		$myCompany = crm_Companies::fetchOwnCompany();
	    	$form->setDefault('beneficiaryName', $myCompany->company);
	    	$ownAccount = bank_OwnAccounts::getOwnAccountInfo($originRec->ownAccount);
	    	$form->setDefault('beneficiaryIban', $ownAccount->iban);
	    	$form->setDefault('beneficiaryBank', $ownAccount->bank);
	    		
	    	// Ако контрагента е лице, слагаме името му за получател
	    	if($originRec->contragentClassId != crm_Companies::getClassId()){
	    		$form->setDefault('depositor', $originRec->contragentName);
	    	}
	    	
	    	$options = bank_Accounts::getContragentIbans($originRec->contragentId, $originRec->contragentClassId);
	    	$form->setSuggestions('beneficiaryIban', $options);
    	}
	    
    	static::getContragentInfo($form);
    }
    
    
    /**
     *  Попълва формата с информацията за контрагента извлечена от папката
     */
    static function getContragentInfo(core_Form $form)
    {
    	if(isset($form->rec->beneficiaryName)) return;
    	$folderId = $form->rec->folderId;
    	$contragentId = doc_Folders::fetchCoverId($folderId);
    	$contragentClassId = doc_Folders::fetchField($folderId, 'coverClass');
    	
   		// Информацията за контрагента на папката
    	expect($contragentData = doc_Folders::getContragentData($folderId), "Проблем с данните за контрагент по подразбиране");
    	
    	if($contragentData) {
    		if($contragentData->company) {
    			
    			$form->setDefault('beneficiaryName', $contragentData->company);
    		} elseif ($contragentData->person) {
    			
    			// Ако папката е на лице, то вносителя по дефолт е лицето
    			$form->setDefault('beneficiaryName', $contragentData->person);
    		}
    		$form->setReadOnly('beneficiaryName');
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
        return FALSE;
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената нишка
     * 
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
	public static function canAddToThread($threadId)
    {
    	$firstDoc = doc_Threads::getFirstDocument($threadId);
    	
    	return $firstDoc->className == 'bank_IncomeDocument' || $firstDoc->className == 'bank_CostDocument';
    }
    
    
	/**
     *  Обработки по вербалното представяне на данните
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->number = static::getHandle($rec->id);
    	
    	if($fields['-single']) {
    		$spellNumber = cls::get('core_SpellNumber');
			$row->sayWords = $spellNumber->asCurrency($rec->amount, 'bg', FALSE);
	        
	    	// При принтирането на 'Чернова' скриваме системите полета и заглавието
    		if(!Mode::is('printing')){
	    		$row->header = $mvc->singleTitle . "&nbsp;&nbsp;<b>{$row->ident}</b>" . " ({$row->state})" ;
	    	}
    	}
    }
    
    
    /**
     * Обработка след изпращане на формата
     */
	static function on_AfterInputEditForm($mvc, &$form)
	{
		if($form->isSubmitted()){
		    if(!$form->rec->beneficiaryBank){
				 $form->rec->beneficiaryBank = bglocal_Banks::getBankName($form->rec->beneficiaryIban);
			}
		}
	}

	 
	/**
     * Вкарваме css файл за единичния изглед
     */
	static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
    	$tpl->push('bank/tpl/css/belejka.css', 'CSS');
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
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    static function getHandle($id)
    {
    	$rec = static::fetch($id);
    	$self = cls::get(get_called_class());
    	
    	return $self->abbr . $rec->id;
    }
    
    
	/**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	 $data->toolbar->removeBtn('btnAdd');
    }
    
    
	/**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото на имейл по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
        $handle = static::getHandle($id);
        $tpl = new ET(tr("Моля запознайте се с нашата вносна бележка") . ': #[#handle#]');
        $tpl->append($handle, 'handle');
        return $tpl->getContent();
    }
}