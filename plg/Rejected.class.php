<?php



/**
 * Клас 'plg_Rejected' - Поддръжка на състоянието rejected
 *
 *
 * @category  ef
 * @package   plg
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class plg_Rejected extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
     */
    function on_AfterDescription(&$mvc)
    {
        // Добавяне на необходимите полета
        if(!isset($mvc->fields['state'])) {
            $mvc->FLD('state',
                'enum(draft=Чернова,active=Активирано,closed=Затворено,rejected=Оттеглено)',
                'caption=Състояние,column=none,input=none,notNull');
        }
        
        if(!isset($mvc->fields['state']->type->options['rejected'])) {
            $mvc->fields['state']->type->options['rejected'] = 'Оттеглено';
        }

        $mvc->FLD('exState', clone($mvc->fields['state']->type), "caption=Пред. състояние,column=none,input=none,notNull");
        
        if(!isset($mvc->fields['lastUsedOn'])) {
            $mvc->FLD('lastUsedOn', 'datetime(format=smartTime)', 'caption=Последна употреба,input=none,column=none');
        }
    }
    
    
    /**
     * Добавя бутон за оттегляне
     */
    function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        if (isset($data->rec->id) && !$mvc->haveRightFor('delete', $data->rec) && $mvc->haveRightFor('reject', $data->rec) && ($data->rec->state != 'rejected')) {
            $data->toolbar->addBtn('Оттегляне', array(
                    $mvc,
                    'reject',
                    $data->rec->id,
                    'ret_url' => TRUE
                ),
                'id=btnDelete,class=btn-reject,warning=Наистина ли желаете да оттеглите документа?,order=32');
        }
        
        if (isset($data->rec->id) && $mvc->haveRightFor('reject') && ($data->rec->state == 'rejected')) {
            $data->toolbar->removeBtn("*");
            $data->toolbar->addBtn('Възстановяване', array(
                    $mvc,
                    'restore',
                    $data->rec->id,
                    'ret_url' => TRUE
                ),
                'id=btnRestore,warning=Наистина ли желаете да възстановите документа?,order=32', 'ef_icon = img/16/restore.png');
        }
    }
    
    
    /**
     * Добавя бутон за показване на оттеглените записи
     */
    function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        if(Request::get('Rejected')) {
            $data->toolbar->removeBtn('*');
            $data->toolbar->addBtn('Всички', array($mvc), 'id=listBtn', 'ef_icon = img/16/application_view_list.png');
        } else {
            $rejCnt = $data->rejQuery->count();

            if($rejCnt) {
                $data->toolbar->addBtn("Кош|* ({$rejCnt})", array($mvc, 'list', 'Rejected' => 1), 'id=binBtn,class=btn-bin,order=50');
            }
        }
        if(Request::get('Rejected')) {
            $data->title = new ET('[#1#]', tr($data->title ? $data->title : $mvc->title));
            $data->title->append("&nbsp;<font class='state-rejected stateIndicator'>&nbsp;" . tr('оттеглени') . "&nbsp;</font>");
        } 
    }
    
    
    /**
     * Оттегляне на обект
     * 
     * Реализация по подразбиране на метода $mvc->reject($id)
     * 
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param int|stdClass $id
     */
    public static function on_AfterReject(core_Mvc $mvc, &$res, $id)
    {
        $res = FALSE;
        $rec = $mvc->fetchRec($id);
        
        if (!isset($rec->id) || $rec->state == 'rejected') {
            return;
        }
        
        $rec->exState = $rec->state;
        $rec->state = 'rejected';
        $res = $mvc->save($rec);

        $mvc->log('reject', $rec->id);
    }
    
    
    /**
     * Възстановяване на оттеглен обект
     * 
     * Реализация по подразбиране на метода $mvc->restore($id)
     * 
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param int|stdClass $id
     */
    public static function on_AfterRestore(core_Mvc $mvc, &$res, $id)
    {
        $res = FALSE;
        $rec = $mvc->fetchRec($id);
                        
        if (!isset($rec->id) || $rec->state != 'rejected') {
            return;
        }
        
        $rec->state = $rec->exState;
        $res = $mvc->save($rec);

        $mvc->log('restore', $rec->id);
    }


	/**
     * Смяна статута на 'rejected'
     *
     * @return core_Redirect
     */
    function on_BeforeAction(core_Manager $mvc, &$res, $action)
    {
        if ($action == 'reject') {
            expect(Request::isConfirmed());
            $id = Request::get('id', 'int');
            $rec = $mvc->fetch($id);
            $mvc->requireRightFor('reject', $rec);
            $mvc->reject($rec);
            $res = new Redirect(getRetUrl() ? getRetUrl() : array($mvc, 'single', $id));
                        
            return FALSE;
        }
        
        if ($action == 'restore') {
            expect(Request::isConfirmed());
            $id = Request::get('id', 'int');
            $rec = $mvc->fetch($id);
            $mvc->requireRightFor('restore', $rec);
            $mvc->restore($rec);
            $res = new Redirect(getRetUrl() ? getRetUrl() : array($mvc, 'single', $id));
            
            return FALSE;
        }
    }
    
    
    /**
     * Преди подготовка на данните за табличния изглед правим филтриране
     * на записите, които са (или не са) оттеглени
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        if($data->query) {
            if(Request::get('Rejected')) {
                $data->query->where("#state = 'rejected'");
            } else {
                $data->rejQuery = clone($data->query);
                $data->query->where("#state != 'rejected' || #state IS NULL");
                $data->rejQuery->where("#state = 'rejected'");
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * Забранява изтриването на вече използвани сметки
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass|NULL $rec
     * @param int|NULL $userId
     */
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if ($rec->id) {
            if($action == 'delete' && $rec->lastUsedOn) {
                $requiredRoles = 'no_one';
            }
            
            // Системните записи не могат да се оттеглят или изтриват
            if($rec->createdBy == -1 && $action == 'reject') {
                $requiredRoles = 'no_one';
            }
        }
    }
}