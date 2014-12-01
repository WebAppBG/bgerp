<?php



/**
 * Плъгин за документите източници на счетоводни транзакции
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Stefan Stefanov <stefan.bg@gmail.com> и Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_plg_Contable extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
     *
     * @param core_Mvc $mvc
     */
    function on_AfterDescription(core_Mvc $mvc)
    {
        $mvc->declareInterface('acc_TransactionSourceIntf');
        
        $mvc->getFieldType('state')->options['revert'] = 'Сторниран';
        
        // Добавяне на кеш-поле за контируемостта на документа. Обновява се при (преди) всеки 
        // запис. Използва се при определяне на правата за контиране.
        if(empty($mvc->fields['isContable'])){
            $mvc->FLD('isContable', 'enum(yes,no,activate)', 'input=none,notNull,default=no');
        }
        
        setIfNot($mvc->canCorrection, 'ceo, accMaster');
        setIfNot($mvc->valiorFld, 'valior');
        
        // Зареждаме плъгина, който проверява можели да се оттегли/възстанови докумена
        $mvc->load('acc_plg_RejectContoDocuments');
    }
    
    
    /**
     * Преди изпълнението на контролерен екшън
     *
     * @param core_Manager $mvc
     * @param core_ET $res
     * @param string $action
     */
    public static function on_BeforeAction(core_Manager $mvc, &$res, $action)
    {
        if(strtolower($action) == strtolower('getTransaction')) {
            $id = Request::get('id', 'int');
            $rec = $mvc->fetch($id);
            $transactionSource = cls::getInterface('acc_TransactionSourceIntf', $mvc);
            $transaction       = $transactionSource->getTransaction($rec);
            
            Mode::set('wrapper', 'page_Empty');
            
            if(!static::hasContableTransaction($mvc, $rec, $transactionRes)){
                $res = ht::wrapMixedToHtml(ht::mixedToHtml(array($transactionRes, $transaction), 4));
            } else {
                $res = ht::wrapMixedToHtml(ht::mixedToHtml($transaction, 4));
            }
            
            return FALSE;
        }
    }
    
    
    /**
     * Преди запис на документ, изчислява стойността на полето `isContable`
     *
     * @param core_Manager $mvc
     * @param stdClass $rec
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
        if (!empty($rec->state) && $rec->state != 'draft') {
            return;
        }
        
        try {
            // Дали документа може да се активира
            $canActivate = $mvc->canActivate($rec);
            
            // Извличане на транзакцията
            $transaction = $mvc->getValidatedTransaction($rec);
            
            // Ако има валидна транзакция
            if($transaction !== FALSE){
                
                // Ако транзакцията е празна и документа може да се активира
                if($transaction->isEmpty() && $canActivate){
                    $rec->isContable = 'activate';
                } elseif(!$transaction->isEmpty() && $canActivate) {
                    $rec->isContable = 'yes';
                } else {
                    $rec->isContable = 'no';
                }
            } else {
                $rec->isContable = 'no';
            }
        } catch (acc_journal_Exception $ex) {
            $rec->isContable = 'no';
        }
    }
    
    
    /**
     * Добавя бутони за контиране или сторниране към единичния изглед на документа
     */
    function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        $rec = &$data->rec;
        
        if(haveRole('debug')) {
            $data->toolbar->addBtn('Транзакция', array($mvc, 'getTransaction', $rec->id), 'ef_icon=img/16/bug.png,title=Дебъг,row=2');
        }
        
        if ($mvc->haveRightFor('conto', $rec)) {
            
        	unset($error);
            // Проверка на счетоводния период, ако има грешка я показваме
            if(!self::checkPeriod($rec->{$mvc->valiorFld}, $error)){
                $error = ",error={$error}";
            }
            
            $caption = ($rec->isContable == 'activate') ? 'Активиране' : 'Контиране';
            
            // Урл-то за контиране
            $contoUrl = $mvc->getContoUrl($rec->id);
            $data->toolbar->addBtn($caption, $contoUrl, "id=btnConto,warning=Наистина ли желаете документа да бъде контиран?{$error}", 'ef_icon = img/16/tick-circle-frame.png,title=Контиране на документа');
        }
        
        if ($mvc->haveRightFor('revert', $rec)) {
            $rejectUrl = array(
                'acc_Journal',
                'revert',
                'docId' => $rec->id,
                'docType' => $mvc->className,
                'ret_url' => TRUE
            );
            $data->toolbar->addBtn('Сторно', $rejectUrl, 'id=revert,warning=Наистина ли желаете документа да бъде сторниран?', 'ef_icon = img/16/red-back.png,title=Сторниране на документа');
        } else {
        	
        	// Ако потребителя може да създава коригиращ документ, слагаме бутон
        	if ($mvc->haveRightFor('correction', $rec)) {
        		$correctionUrl = array(
        				'acc_Articles',
        				'RevertArticle',
        				'docType' => $mvc->getClassId(),
        				'docId' => $rec->id,
        				'ret_url' => TRUE
        		);
        		$data->toolbar->addBtn('Корекция', $correctionUrl, "id=btnCorrection-{$rec->id},class=btn-correction,warning=Наистина ли желаете да коригирате документа?,title=Създаване на обратен мемориален ордер,ef_icon=img/16/page_red.png,row=2");
        	}
        }
        
        // Ако има запис в журнала и потребителя има права за него, слагаме бутон
        $journalRec = acc_Journal::fetchByDoc($mvc->getClassId(), $rec->id);
        
        if(($rec->state == 'active' || $rec->state == 'closed') && acc_Journal::haveRightFor('read') && $journalRec) {
            $journalUrl = array('acc_Journal', 'single', $journalRec->id);
            $data->toolbar->addBtn('Журнал', $journalUrl, 'row=2,ef_icon=img/16/book.png,title=Преглед на транзакцията в журнала');
        }
    }
    
    
    /**
     * Ф-я проверяваща периода в който е датата и връща съобщение за грешка
     *
     * @param date $valior - дата
     * @param mixed $error - съобщение за грешка, NULL ако няма
     * @return boolean
     */
    public static function checkPeriod($valior, &$error)
    {
        $docPeriod = acc_Periods::fetchByDate($valior);
        
        if($docPeriod){
            if($docPeriod->state == 'closed'){
                $error = "Не може да се контира в затворения сч. период \'{$docPeriod->title}\'";
            } elseif($docPeriod->state == 'draft'){
                $error = "Не може да се контира в бъдещия сч. период \'{$docPeriod->title}\'";
            }
        } else {
            $error = "Не може да се контира в несъществуващ сч. период";
        }
        
        return ($error) ? FALSE : TRUE;
    }
    
    
    /**
     * Метод връщащ урл-то за контиране на документа.
     * Може да се използва в мениджъра за подмяна на контиращото урл
     */
    public static function on_AfterGetContoUrl(core_Manager $mvc, &$res, $id)
    {
        $res = acc_Journal::getContoUrl($mvc, $id);
    }
    
    
    /**
     * Реализация по подразбиране на acc_TransactionSourceIntf::getLink()
     *
     * @param core_Manager $mvc
     * @param mixed $res
     * @param mixed $id
     */
    static function on_AfterGetLink($mvc, &$res, $id)
    {
        if(!$res) {
            $title = sprintf('%s&nbsp;№%d',
                empty($mvc->singleTitle) ? $mvc->title : $mvc->singleTitle,
                $id
            );
            
            $res = ht::createLink($title, array($mvc, 'single', $id));
        }
    }
    
    
    /**
     * Извиква се след изчисляването на необходимите роли за това действие
     */
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if ($action == 'conto') {
            
            // Не може да се контира в състояние, което не е чернова
            if ($rec->id && $rec->state != 'draft') {
                $requiredRoles = 'no_one';
            }
            
            // Не може да се контира, ако документа не генерира валидна транзакция
            if (isset($rec) && $rec->isContable == 'no'){
                $requiredRoles = 'no_one';
            }
            
            // '@sys' може да контира документи
            if($userId == '-1'){
                $requiredRoles = 'every_one';
            }
            
            // Кой може да реконтира документа( изпълнява се след възстановяване на оттеглен документ)
        } elseif($action == 'reconto' && isset($rec)){
            
            // Който може да възстановява, той може и да реконтира
            $requiredRoles = $mvc->getRequiredRoles('restore', $rec);
            
            // Не може да се реконтират само активни и приключени документи
            if ($rec->id && ($rec->state == 'draft' || $rec->state == 'rejected')) {
                $requiredRoles = 'no_one';
            }
            
            // Не може да се контира, ако документа не генерира валидна транзакция
            if ($rec->isContable == 'no'){
                $requiredRoles = 'no_one';
            }
        } elseif ($action == 'revert') {
            if ($rec->id) {
                $periodRec = acc_Periods::fetchByDate($rec->{$mvc->valiorFld});
                
                if (($rec->state != 'active' && $rec->state != 'closed') || ($periodRec->state != 'closed')) {
                    $requiredRoles = 'no_one';
                }
            }
        } elseif ($action == 'reject') {
            if ($rec->id) {
                
                $periodRec = acc_Periods::fetchByDate($rec->{$mvc->valiorFld});
                
                if ($periodRec->state == 'closed') {
                    $requiredRoles = 'no_one';
                } else {
                    
                    // Ако потребителя не може да контира документа, не може и да го оттегля
                    if(!haveRole($mvc->getRequiredRoles('conto'))){
                        $requiredRoles = 'no_one';
                    }
                }
            }
        } elseif ($action == 'restore') {
        	
            // Ако потребителя не може да контира документа, не може и да го възстановява
            if(!haveRole($mvc->getRequiredRoles('conto'))){
                $requiredRoles = 'no_one';
            }
            
            if(isset($rec)){
            	
            	// Ако сч. период на записа е затворен, документа не може да се възстановява
            	$periodRec = acc_Periods::fetchByDate($rec->{$mvc->valiorFld});
            	if ($periodRec->state == 'closed') {
            		$requiredRoles = 'no_one';
            	}
            }
            
        } elseif ($action == 'correction') {
            
            // Кой може да създава коригиращ документ
            $requiredRoles = $mvc->canCorrection;
            
            // Трябва да има запис
            if (!$rec) {
                return;
            }
            
            // Черновите и оттеглените документи немогат да се коригират
            if ($rec->state == 'draft' || $rec->state == 'rejected') {
                $requiredRoles = 'no_one';
            }
            
            // Ако няма какво да се коригира в журнала, не може да се създаде корекция
            if(!acc_Journal::fetchByDoc($mvc->getClassId(), $rec->id)){
                $requiredRoles = 'no_one';
            }
            
            // Ако документа не генерира валидна и непразна транзакция - не може да му се прави корекция
            if (!$rec->isContable) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    /**
     * Помощен метод, енкапсулиращ условието за валидност на счетоводна транзакция
     *
     * @param core_Manager $mvc
     * @param stdClass $rec
     * @return boolean
     */
    protected static function hasContableTransaction(core_Manager $mvc, $rec, &$res = NULL)
    {
        try {
            $result = ($transaction = $mvc->getValidatedTransaction($rec)) !== FALSE;
        } catch (acc_journal_Exception $ex) {
            $res = $ex->getMessage();
            $result = FALSE;
        }
        
        return $result;
    }
    
    
    /**
     * Помощна ф-я за контиране на документ
     */
    private static function conto($mvc, $id)
    {
        $rec = $mvc->fetchRec($id);
        
        // Контирането е позволено само в съществуващ активен/чакащ/текущ период;
        $period = acc_Periods::fetchByDate($rec->valior);
        expect($period && ($period->state != 'closed' && $period->state != 'draft'), 'Не може да се контира в несъществуващ, бъдещ или затворен период');
        $cRes = acc_Journal::saveTransaction($mvc->getClassId(), $rec);
        $handle = $mvc->getHandle($rec->id);
        
        if(!empty($cRes)){
            $action = ($rec->isContable == 'activate') ? "активиран" : "контиран";
            $cRes = "е {$action} успешно";
        } else {
            $cRes = 'НЕ Е контиран';
        }
        
        // Слагане на статус за потребителя
        status_Messages::newStatus("#{$handle} " . tr($cRes));
    }
    
    
    /**
     * Контиране на счетоводен документ
     *
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param int|object $id първичен ключ или запис на $mvc
     */
    public static function on_AfterConto(core_Mvc $mvc, &$res, $id)
    {
        self::conto($mvc, $id);
    }
    
    
    /**
     * Ре-контиране на счетоводен документ
     *
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param int|object $id първичен ключ или запис на $mvc
     */
    public static function on_AfterReConto(core_Mvc $mvc, &$res, $id)
    {
        self::conto($mvc, $id); 
    }
    
    
    /**
     * Реакция в счетоводния журнал при оттегляне на счетоводен документ
     *
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param int|object $id първичен ключ или запис на $mvc
     */
    public static function on_AfterReject(core_Mvc $mvc, &$res, $id)
    {
        if (is_object($id)) {
            $id = $id->id;
        }
        
        $res = acc_Journal::rejectTransaction($mvc->getClassId(), $id);
    }
    
    
    /**
     * Реакция в счетоводния журнал при възстановяване на оттеглен счетоводен документ
     *
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param int|object $id първичен ключ или запис на $mvc
     */
    public static function on_AfterRestore(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        
        if($rec->state == 'active' || $rec->state == 'closed'){
            // Ре-контиране на документа след възстановяването му
            self::on_AfterReConto($mvc, $res, $id);
        }
    }
    
    
    /**
     * Обект-транзакция, съответстващ на счетоводен документ, ако е възможно да се генерира
     *
     * @param core_Mvc $mvc
     * @param acc_journal_Transaction $transation FALSE, ако не може да се генерира транзакция
     * @param stdClass $rec
     */
    public static function on_AfterGetValidatedTransaction(core_Mvc $mvc, &$transaction, $rec)
    {
        if (empty($rec)) {
            $transaction = FALSE;
            
            return;
        }
        
        $rec = $mvc->fetchRec($rec);
        
        $transactionSource = cls::getInterface('acc_TransactionSourceIntf', $mvc);
        $transaction       = $transactionSource->getTransaction($rec);
        
        expect(!empty($transaction), 'Класът ' . get_class($mvc) . ' не върна транзакция!');
        
        // Проверяваме валидността на транзакцията
        $transaction = new acc_journal_Transaction($transaction);
        
        $transaction->check();
    }
    
    
    /**
     * Метод по подразбиране на canActivate
     */
    public static function on_AfterCanActivate($mvc, &$res, $rec)
    {
        if(!$res){
            if (!empty($rec->id) && ($rec->state != 'draft' || !$mvc->haveRightFor('edit', $rec))) {
                $res = FALSE;
            } elseif(count($mvc->details)){
                $hasDetail = FALSE;
                
                if($rec->id){
                    // Ако класа има поне един запис в детаил, той може да се активира
                    foreach ($mvc->details as $name){
                        $Details = $mvc->{$name};
                        
                        if($Details->fetch("#{$Details->masterKey} = {$rec->id}")){
                            $hasDetail = TRUE;
                            break;
                        }
                    }
                }
                $res = $hasDetail;
            } else {
                $res = TRUE;
            }
        }
    }
    
    
    /**
     * Връща основанието за транзакцията, по подразбиране е основанието на журнала
     */
    public static function on_AfterGetContoReason($mvc, &$res, $id, $reasonCode = NULL)
    {
        if(empty($res)){
        	if($jRec = acc_Journal::fetchByDoc($mvc->getClassId(), $id)){
        		$Varchar = cls::get('type_Varchar');
        		$res = $Varchar->toVerbal($jRec->reason);
        	}
        }
    }
}