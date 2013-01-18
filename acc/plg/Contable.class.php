<?php



/**
 * Плъгин за документите източници на счетоводни транзакции
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
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
        
        $mvc->fields['state']->type->options['revert'] = 'Сторниран';
    }
    
    
    /**
     * Добавя бутони за контиране или сторниране към единичния изглед на документа
     */
    function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        if ($mvc->haveRightFor('conto', $data->rec)) {
            $contoUrl = array(
                'acc_Journal',
                'conto',
                'docId' => $data->rec->id,
                'docType' => $mvc->className,
                'ret_url' => TRUE
            );
            $data->toolbar->addBtn('Контиране', $contoUrl, 'id=conto,class=btn-conto,warning=Наистина ли желаете документа да бъде контиран?');
        }
        
        if ($mvc->haveRightFor('revert', $data->rec)) {
            $rejectUrl = array(
                'acc_Journal',
                'revert',
                'docId' => $data->rec->id,
                'docType' => $mvc->className,
                'ret_url' => TRUE
            );
            $data->toolbar->addBtn('Сторниране', $rejectUrl, 'id=revert,class=btn-revert,warning=Наистина ли желаете документа да бъде сторниран?');
        }
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
            
            $res = Ht::createLink($title, array($mvc, 'single', $id));
        }
    }
    
    
    /**
     * Извиква се след изчисляването на необходимите роли за това действие
     */
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if ($action == 'conto') {
            if ($rec->id && $rec->state != 'draft') {
                $requiredRoles = 'no_one';
            }
        } elseif ($action == 'revert') {
            if ($rec->id) {
                $periodRec = acc_Periods::fetchByDate($rec->valior);
                
                if ($rec->state != 'active' || ($periodRec->state != 'closed')) {
                    $requiredRoles = 'no_one';
                }
            }
        } elseif ($action == 'reject') {
            if ($rec->id) {
                $periodRec = acc_Periods::fetchByDate($rec->valior);
                
                if ($periodRec->state == 'closed') {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
}