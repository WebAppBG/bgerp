<?php



/**
 * Мениджър на детайли на Мемориален ордер
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_ArticleDetails extends core_Detail
{
    
    
    /**
     * Заглавие
     */
    var $title = "Мемориален ордер";

     
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = 'Счетоводна статия';


    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'articleId';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, acc_Wrapper, plg_RowNumbering, plg_StyleNumbers, plg_AlignDecimals, doc_plg_HidePrices,
        Accounts=acc_Accounts, Lists=acc_Lists, Items=acc_Items, plg_SaveAndNew';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт, debitAccId, debitQuantity=Дебит->К-во, debitPrice=Дебит->Цена, creditAccId, creditQuantity=Кредит->К-во, creditPrice=Кредит->Цена, amount=Сума';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Активен таб
     */
    var $currentTab = 'Мемориални ордери';

        
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,acc';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,accMaster';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,acc';
    

    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo,acc';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,accMaster';

	
    /**
     * Полета свързани с цени
     */
    var $priceFields = 'debitQuantity, debitPrice, creditQuantity, creditPrice, amount';
    
    
    /**
     * Работен кеш
     */
    protected static $cache = array();
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('articleId', 'key(mvc=acc_Articles)', 'column=none,input=hidden,silent');
        
        $this->FLD('debitAccId', 'acc_type_Account(remember)',
            'silent,caption=Дебит->Сметка и пера,mandatory,input','tdClass=articleCell');
        $this->FLD('debitEnt1', 'acc_type_Item(select=title,allowEmpty)', 'caption=Дебит->перо 1,remember');
        $this->FLD('debitEnt2', 'acc_type_Item(select=title,allowEmpty)', 'caption=Дебит->перо 2,remember');
        $this->FLD('debitEnt3', 'acc_type_Item(select=title,allowEmpty)', 'caption=Дебит->перо 3,remember');
        $this->FLD('debitQuantity', 'double', 'caption=Дебит->Количество');
        $this->FLD('debitPrice', 'double(minDecimals=2)', 'caption=Дебит->Цена');
        
        $this->FLD('creditAccId', 'acc_type_Account(remember)',
            'silent,caption=Кредит->Сметка и пера,mandatory,input','tdClass=articleCell');
        $this->FLD('creditEnt1', 'acc_type_Item(select=title,allowEmpty)', 'caption=Кредит->перо 1,remember');
        $this->FLD('creditEnt2', 'acc_type_Item(select=title,allowEmpty)', 'caption=Кредит->перо 2,remember');
        $this->FLD('creditEnt3', 'acc_type_Item(select=title,allowEmpty)', 'caption=Кредит->перо 3,remember');
        $this->FLD('creditQuantity', 'double', 'caption=Кредит->Количество');
        $this->FLD('creditPrice', 'double(minDecimals=2)', 'caption=Кредит->Цена');
       
        $this->FLD('amount', 'double(decimals=2)', 'caption=Оборот->Сума,remember=info');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    static function on_AfterPrepareListRows($mvc, &$res)
    {
        $rows = &$res->rows;
        $recs = &$res->recs;
        
        if (count($recs)) {
            foreach ($recs as $id=>$rec) {
                $row = &$rows[$id];
               
                foreach (array('debit', 'credit') as $type) {
                    $ents = "";
                    
                    foreach (range(1, 3) as $i) {
                        $ent = "{$type}Ent{$i}";
                        if ($rec->{$ent}) {
                            $ents .= "<tr><td> <span style='margin-left:10px; font-size: 11px; color: #747474;'>{$i}.</span> <span>{$row->{$ent}}</span></td</tr>";
                        }
                    }
                    
                    if (!empty($ents)) {
                        $row->{"{$type}AccId"} .=
                        "<table class='acc-article-entries'>" .
                        $ents .
                        "</table>";
                    }
                }
            }
        }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if (!$mvc->Master->haveRightFor('edit', $data->masterData->rec)) {
            
            $data->toolbar->removeBtn('btnAdd');
            
            return;
        }
        
        $query = acc_ArticleDetails::getQuery();
        $query->where("#articleId = {$data->masterId}");
        $query->orderBy("id", "DESC");
        $lastRec = $query->fetch();
        
        expect($data->masterId);
        $form = cls::get('core_Form');
        
        $form->method = 'GET';
        $form->action = array (
            $mvc, 'add',
        );
        $form->view = 'horizontal';
        $form->FLD('debitAccId', 'acc_type_Account(allowEmpty)',
            'silent,caption=Дебит,mandatory');
        $form->FLD('creditAccId', 'acc_type_Account(allowEmpty)',
            'silent,caption=Кредит,mandatory');
        
        $form->setDefault('debitAccId', $lastRec->debitAccId);
        $form->setDefault('creditAccId', $lastRec->creditAccId);
        $form->FLD('articleId', 'int', 'input=hidden');
        $form->setHidden('articleId', $data->masterId);
        
        $form->FLD('ret_url', 'varchar(1024)', 'input=hidden');
        $form->setHidden('ret_url', toUrl(getCurrentUrl(), 'local'));
        
        $form->title = 'Нов запис в журнала';
        
        $form->toolbar->addSbBtn('Нов', '', '', "id=btnAdd", 'ef_icon = img/16/star_2.png');
        
        $data->accSelectToolbar = $form;
    }
    
    
    /**
     * Извиква се след рендиране на Toolbar-а
     */
    static function on_AfterRenderListToolbar($mvc, &$tpl, $data)
    {
        if ($data->accSelectToolbar) {
            $form = $data->accSelectToolbar->renderHtml();
            
            if($form) {
                $tpl = $form;
            }
        }
    }
    
    
    /**
     * След подготовка на формата за добавяне/редакция
     */
    static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        if ((!$rec->debitAccId) || (!$rec->creditAccId)) {
            
            Redirect(array('acc_Articles', 'single', $rec->articleId), FALSE, "Не са избрани сметки за дебит и кредит.");
        }
        
        $quantityOnly = $dimensional = FALSE;
        $form->setReadOnly('debitAccId');
        $form->setReadOnly('creditAccId');
        
        $form->setField('debitAccId', 'caption=Дебит->Сметка');
        $form->setField('creditAccId', 'caption=Кредит->Сметка');
        
        $debitAcc = acc_Accounts::getAccountInfo($rec->debitAccId);
        $creditAcc = acc_Accounts::getAccountInfo($rec->creditAccId);
        
        $dimensional = $debitAcc->isDimensional || $creditAcc->isDimensional;
        
        $quantityOnly = ($debitAcc->rec->type == 'passive' && $debitAcc->rec->strategy) ||
        ($creditAcc->rec->type == 'active' && $creditAcc->rec->strategy);
        
        $masterRec = $mvc->Master->fetch($form->rec->articleId);
        
        foreach (array('debit' => 'Дебит', 'credit' => 'Кредит') as $type => $caption) {
            
            $acc = ${"{$type}Acc"};
            
            // Скриваме всички полета за пера, и после показваме само тези, за които съответната
            // (дебит или кредит) сметка има аналитичност.
            $form->setField("{$type}Ent1", 'input=none');
            $form->setField("{$type}Ent2", 'input=none');
            $form->setField("{$type}Ent3", 'input=none');
            
            foreach ($acc->groups as $i=>$list) {
                if (!$list->rec->itemsCnt) {
                    redirect(array('acc_Items', 'list', 'listId'=>$list->rec->id), FALSE, tr("Липсва избор за |* \"" . acc_Lists::getVerbal($list->rec, 'name') . "\""));
                }
                
                $form->getField("{$type}Ent{$i}")->type->params['lists'] = $list->rec->num;
                $form->setField("{$type}Ent{$i}", "mandatory,input,caption={$caption}->" . $list->rec->name);
                
                // Ако може да се избират приключени пера, сетваме параметър в типа на перата
                if($masterRec->useCloseItems == 'yes'){
                	$form->getField("{$type}Ent{$i}")->type->params['showAll'] = TRUE;
                }
            }
            
            if (!$acc->isDimensional) {
                $form->setField("{$type}Quantity", 'input=none');
                $form->setField("{$type}Price", 'input=none');
            }
            
            if ($quantityOnly) {
                $form->setField("{$type}Price", 'input=none');
                $form->setField("{$type}Quantity", 'mandatory');
            }
        }
        
        if ($quantityOnly) {
            $form->setField('amount', 'input=none');
        }
        
        if (!$dimensional && !$quantityOnly) {
            $form->setField('amount', 'mandatory');
        }
    }
    
    
    /**
     * След изпращане на формата
     */
    static function on_AfterInputEditForm($mvc, $form)
    {
        if (!$form->isSubmitted()){
            return;
        }
        
        $rec = $form->rec;
        
        $accs = array(
            'debit' => acc_Accounts::getAccountInfo($rec->debitAccId),
            'credit' => acc_Accounts::getAccountInfo($rec->creditAccId),
        );
        
        $quantityOnly = ($accs['debit']->rec->type == 'passive' && $accs['debit']->rec->strategy) ||
        ($accs['credit']->rec->type == 'active' && $accs['credit']->rec->strategy);
        
        if ($quantityOnly) {
            
            /**
             * @TODO да се провери, че debitQuantity == creditQuantity в случай, че размерните
             * аналитичности на дебит и кредит сметките са едни и същи.
             */
        } else {
            foreach ($accs as $type=>$acc) {
                if ($acc->isDimensional) {
                    
                    /**
                     * @TODO За размерни сметки: проверка дали са въведени поне два от трите оборота.
                     * Изчисление на (евентуално) липсващия оборот.
                     */
                    $nEmpty = (int)empty($rec->{"{$type}Quantity"}) +
                    (int)empty($rec->{"{$type}Price"}) +
                    (int)empty($rec->amount);
                    
                    if ($nEmpty > 1) {
                        $form->setError("{$type}Quantity, {$type}Price, amount", 'Поне два от оборотите трябва да бъдат попълнени');
                    } else {
                        
                        /**
                         * Изчисление на {$type}Amount:
                         *
                         * За размерни сметки: {$type}Amount = {$type}Quantity & {$type}Price
                         * За безразмерни сметки: {$type}Amount = amount
                         *
                         */
                        switch (true) {
                            case empty($rec->{"{$type}Quantity"}) :
                            $rec->{"{$type}Quantity"} = $rec->amount / $rec->{"{$type}Price"};
                            break;
                            case empty($rec->{"{$type}Price"}) :
                            $rec->{"{$type}Price"} = $rec->amount / $rec->{"{$type}Quantity"};
                            break;
                            case empty($rec->amount) :
                            $rec->amount = $rec->{"{$type}Price"} * $rec->{"{$type}Quantity"};
                            break;
                        }
                        
                        $rec->{"{$type}Amount"} = $rec->amount;
                    }
                    
                    if ($rec->amount != $rec->{"{$type}Price"} * $rec->{"{$type}Quantity"}) {
                        $form->setError("{$type}Quantity, {$type}Price, amount", 'Невъзможни стойности на оборотите');
                    }
                } else {
                    $rec->{"{$type}Amount"} = $rec->amount;
                }
            }
            
            /**
             * Проверка дали debitAmount == debitAmount
             */
            if ($rec->debitAmount != $rec->creditAmount) {
                $form->setError('debitQuantity, debitPrice, creditQuantity, creditPrice, amount', 'Дебит и кредит страните са различни');
            }
        }
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    static function on_AfterSave($mvc, &$id, $rec, $fields = NULL)
    {
        $mvc->Master->detailsChanged($rec->{$mvc->masterKey}, $mvc, $rec);
    }
    
    
    /**
     * Преди изтриване на запис
     */
    static function on_BeforeDelete($mvc, &$res, &$query, $cond)
    {
        $_query = clone($query);
        $query->notifyMasterIds = array();
        
        while ($rec = $_query->fetch($cond)) {
            $query->notifyMasterIds[$rec->{$mvc->masterKey}] = TRUE;
        }
    }
    
    
    /**
     * След изтриване на запис
     */
    static function on_AfterDelete($mvc, &$res, $query, $cond)
    {
        foreach ($query->notifyMasterIds as $masterId => $_) {
            $mvc->Master->detailsChanged($masterId, $mvc);
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'edit' || $action == 'delete') && isset($rec)){
    		$articleState = acc_Articles::fetchField($rec->articleId, 'state');
    		if($articleState != 'draft'){
    			$res = 'no_one';
    		}
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	foreach (array('debitEnt1', 'debitEnt2', 'debitEnt3', 'creditEnt1', 'creditEnt2', 'creditEnt3') as $fld){
    		if(isset($rec->$fld)){
    			
    			// Кешираме името на перото
    			if(!isset(static::$cache['items'][$rec->$fld])){
    				static::$cache['items'][$rec->$fld] = acc_Items::getVerbal($rec->$fld, 'titleLink');
    			}
    			
    			$row->$fld = static::$cache['items'][$rec->$fld];
    		}
    	}
    	
    	// В кой баланс е влязал записа
    	$valior = $mvc->Master->fetchField($rec->articleId, 'valior');
    	$balanceValior = acc_Balances::fetch("#fromDate <= '{$valior}' AND '{$valior}' <= #toDate");
    	
    	// Кешираме линковете към сметките
    	foreach (array('debitAccId', 'creditAccId') as $accId){
    		if(!isset(static::$cache['accs'][$rec->$accId])){
    			static::$cache['accs'][$rec->$accId] = acc_Balances::getAccountLink($rec->$accId, $balanceValior);
    		}
    	}
    	
    	
    	
    	// Линкове към сметките в баланса
    	$row->debitAccId = static::$cache['accs'][$rec->debitAccId];
    	$row->creditAccId = static::$cache['accs'][$rec->creditAccId];
    }
}
