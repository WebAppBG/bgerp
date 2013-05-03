<?php



/**
 * Банкови сметки
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_Accounts extends core_Master {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'acc_RegisterIntf';
    
    
    /**
     * Заглавие
     */
    var $title = 'Всички сметки';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, bank_Wrapper, plg_Rejected';
    
  
    /**
     * Кои полета да се показват в листовия изглед
     */
    var $listFields = 'id, iban, contragent=Контрагент, currencyId, type';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Банкова с-ка";
    
    
    /**
     * Икона на единичния обект
     */
    var $singleIcon = 'img/16/bank.png';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsSingleField = 'iban';

    /**
     * Кой има право да чете?
     */
    var $canRead = 'bank, ceo';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'bank, ceo';
    
    
    /**
	 * Файл за единичен изглед
	 */
	var $singleLayoutFile = 'bank/tpl/SingleAccountLayout.shtml';
	
	
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('contragentCls', 'class', 'caption=Контрагент->Клас,mandatory,input=hidden,silent');
        $this->FLD('contragentId', 'int', 'caption=Контрагент->Обект,mandatory,input=hidden,silent');
        $this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута,mandatory,width=6em');
        $this->FLD('iban', 'iban_Type', 'caption=IBAN / №,mandatory');     // Макс. IBAN дължина е 34 символа (http://www.nordea.dk/Erhverv/Betalinger%2bog%2bkort/Betalinger/IBAN/40532.html)
        $this->FLD('bic', 'varchar(16)', 'caption=BIC');
        $this->FLD('bank', 'varchar(64)', 'caption=Банка,width=100%');
        $this->FLD('comment', 'richtext(rows=6)', 'caption=Бележки,width=100%');
        
        // Задаваме индексите и уникалните полета за модела
        $this->setDbIndex('contragentCls,contragentId');
        $this->setDbUnique('iban');
    }
    
     
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $rec = $data->form->rec;
        $Contragents = cls::get($rec->contragentCls);
        expect($Contragents instanceof core_Master);
        $contragentRec   = $Contragents->fetch($rec->contragentId);
        $contragentTitle = $Contragents->getTitleById($contragentRec->id);
        
        if($rec->id) {
            $data->form->title = 'Редактиране на банкова с-ка на |*' . $contragentTitle;
        } else {
            
        	// По подразбиране, валутата е тази, която е в обръщение в страната на контрагента
            if ($contragentRec->country) {
                $countryRec = drdata_Countries::fetch($contragentRec->country);
                $cCode = $countryRec->currencyCode;
                $data->form->setDefault('currencyId',   currency_Currencies::fetchField("#code = '{$cCode}'", 'id'));  
            }
                    
            $data->form->title = 'Нова банкова с-ка на |*' . $contragentTitle;
        }
        
        if($iban = Request::get('iban')) {
        	$data->form->setDefault('iban', $iban);
        }
    }
    
    
    /**
     * След зареждане на форма от заявката. (@see core_Form::input())
     */
    static function on_AfterInputEditForm($mvc, &$form)
    {
        // ако формата е събмитната, и банката и бика не са попълнени,  
        // то ги извличаме от IBAN-a , ако са попълнени изкарваме преудреждение 
        // ако те се разминават с тези в системата
    	if($form->isSubmitted()){
    		$bank = drdata_Banks::getBankName($form->rec->iban);
	        if(!$form->rec->bank){
	        	$form->rec->bank = $bank;
	        } else {
	        	if($bank && $form->rec->bank != $bank){
	        		$form->setWarning('bank', "Въвели сте за банка '{$form->rec->bank}' а IBAN-a отговаря на банка '{$bank}'. Сигурни ли сте че искате да продължите");
	        	}
	        }
	        
	        $bic = drdata_Banks::getBankBic($form->rec->iban);
    		if(!$form->rec->bic){
	        	$form->rec->bic = $bic;
	        } else {
	        	if($bank && $form->rec->bic != $bic){
	        		$form->setWarning('bic', "Въвели сте за bic '{$form->rec->bic}' а правилния bic е '{$bic}'. Сигурни ли сте че искате да продължите");
	        	}
	        }
		}
    }


    /**
     * Връща иконата за сметката
     */
    function getIcon($id)
    {
        $rec = $this->fetch($id);

        $ourCompanyRec = crm_Companies::fetchOurCompany();

        if($rec->contragentId == $ourCompanyRec->id && $rec->contragentCls == $ourCompanyRec->classId) {
            $ownBA = cls::get('bank_OwnAccounts');
            $icon =  $ownBA->singleIcon;
        } else {
            $icon =  $this->singleIcon;
        }

        return $icon;
    }

    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
    	$cMvc = cls::get($rec->contragentCls);
        $field = $cMvc->rowToolsSingleField;
        $cRec = $cMvc->fetch($rec->contragentId);
        $cRow = $cMvc->recToVerbal($cRec, "-list,{$field}");
        $row->contragent = $cRow->{$field};
    }
    
    
    /**
     * Подготвя данните необходими за рендиране на банковите сметки за даден контрагент
     */
    function prepareContragentBankAccounts($data)
    {
        expect($data->contragentCls = core_Classes::fetchIdByName($data->masterMvc));
        expect($data->masterId);
        $query = $this->getQuery();
        $query->where("#contragentCls = {$data->contragentCls} AND #contragentId = {$data->masterId}");
        
        while($rec = $query->fetch()) {
            $data->recs[$rec->id] = $rec;
            $row = $data->rows[$rec->id] = $this->recToVerbal($rec);
        }

        $data->TabCaption = 'Банка';
    }
    
    
    /**
     * Рендира данните на банковите сметки за даден контрагент
     */
    function renderContragentBankAccounts($data)
    {
        $tpl = new ET(getFileContent('crm/tpl/ContragentDetail.shtml'));
        
        $tpl->append(tr('Банкови сметки'), 'title');
        
        if(count($data->rows)) {

            foreach($data->rows as $id => $row) {

                $rec = $data->recs[$id];

                $cCodeRec = currency_Currencies::fetch($rec->currencyId);
                $cCode = currency_Currencies::getVerbal($cCodeRec, 'code');
                
                $row->title = "<span style='border:solid 1px #ccc;background-color:#eee; padding:2px;
                font-size:0.7em;vertical-align:middle;'>{$cCode}</span>&nbsp;";

                $row->title .= $row->iban;
                
                $row->title .= ", {$row->type}";
                
                if($rec->bank) {
                    $row->title .= ", {$row->bank}";
                }

                $tpl->append("<div style='padding:3px;white-space:normal;font-size:0.9em;'>", 'content');
                
                $tpl->append("{$row->title}", 'content');
                
                if(!Mode::is('printing')) {
                    if($this->haveRightFor('edit', $id)) {
                        
                    	// Добавяне на линк за редактиране
                        $tpl->append("<span style='margin-left:5px;'>", 'content');
                        $url = array($this, 'edit', $id, 'ret_url' => TRUE);
                        $img = "<img src=" . sbf('img/16/edit-icon.png') . " width='16' height='16'>";
                        $tpl->append(ht::createLink($img, $url, FALSE, 'title=' . tr('Редактиране на банкова сметка')), 'content');
                        $tpl->append('</span>', 'content');
                    }
                    
                    if($this->haveRightFor('delete', $id)) {
                        
                    	// Добавяне на линк за изтриване
                        $tpl->append("<span style='margin-left:5px;'>", 'content');
                        $url = array($this, 'delete', $id, 'ret_url' => TRUE);
                        $img = "<img src=" . sbf('img/16/delete-icon.png') . " width='16'  height='16'>";
                        $tpl->append(ht::createLink($img, $url, 'Наистина ли желаете да изтриете сметката?', 'title=' . tr('Изтриване на банкова сметка')), 'content');
                        $tpl->append('</span>', 'content');
                    }
                }
                
                $tpl->append("</div>", 'content');
            }
        } else {
            $tpl->append(tr("Все още няма банкови сметки"), 'content');
        }
        
        if(!Mode::is('printing')) {
            $url = array($this, 'add', 'contragentCls' => $data->contragentCls, 'contragentId' => $data->masterId, 'ret_url' => TRUE);
            $img = "<img src=" . sbf('img/16/add.png') . " width='16' valign=absmiddle  height='16'>";
            $tpl->append(ht::createLink($img, $url, FALSE, 'title=' . tr('Добавяне на нова банкова сметка')), 'title');
        }
        
        return $tpl;
    }

    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	// Банкови сметки немогат да се добавят от мениджъра bank_Accounts
    	$data->toolbar->removeBtn('btnAdd');
    }
 	
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
        $title = $rec->iban;
        
        if($escaped) {
            $title = type_Varchar::escape($title);
        }
        
        return $title;
    }
    
    
    /**
     * Връща банковите сметки на даден контрагент
     * @param int $contragendId - Id на контрагента
     * @param int $contragentClassId - ClassId  на контрагента
     * @return array() $suggestions - Масив от сметките на клиента
     */
    static function getContragentIbans($contragentId, $contragentClassId)
    {
    	$suggestions[''] = '';
    	$query = static::getQuery();
    	$query->where("#contragentId = {$contragentId}");
    	$query->where("#contragentCls = {$contragentClassId}");
    	
    	while($rec = $query->fetch()) {
    		$iban = static::getVerbal($rec, 'iban');
	    	$suggestions[$iban] = $iban;
	    }
	    
	    return $suggestions;
    }
}