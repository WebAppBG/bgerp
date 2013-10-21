<?php



/**
 * Клас 'editwatch_Plugin' -
 *
 *
 * @category  vendors
 * @package   editwatch
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class editwatch_Plugin extends core_Plugin {
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    function on_AfterPrepareEditForm(&$mvc, $data)
    {
        $Editors = cls::get('editwatch_Editors');
        
        if(isset($data->form->rec->id) && haveRole('user')) {
            $data->editedBy = $Editors->getAndSetCurrentEditors($mvc, $data->form->rec->id);
        }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а на формата за редактиране/добавяне
     */
    function on_AfterPrepareEditToolbar($mvc, &$tpl, $data)
    {
        if(!$recId = $data->form->rec->id) return TRUE;
        
        if(count($data->editedBy)) {
            $info = $this->renderStatus($data->editedBy, $mvc, $recId);
        }
        
        // Ако не е зададено, рефрешът се извършва на всеки 60 секунди
        $time = $mvc->refreshEditwatchTime ? $mvc->refreshEditwatchTime : 5000;
        
        $info = new ET("<div id='editStatus'>[#1#]</div>", $info);
        
        $url = toUrl(array($mvc, 'ajaxGetEditwatchStatus', $recId, 'ajax_mode' => 1));
        
        $info->appendOnce("\n runOnLoad(function(){setTimeout(function(){ajaxRefreshContent('" . $url . "', {$time},'editStatus');}, {$time});});", 'JQRUN');
        
        $data->form->info = new core_ET($data->form->info);
        $data->form->info->append($info);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function renderStatus($editedBy, $mvc, $recId)
    {
        $info = '<span></span>';
        
        if(count($editedBy)) {
            $Users = cls::get('core_Users');
            $info = "Този запис се редактира също и от: ";
            
            foreach($editedBy as $userId => $last) {
                $info .= $sign . "<b>" . $Users->fetchField($userId, 'nick') . "</b>";
                $sign = ', ';
            }
            $info = "<span class='warningMsg'>$info</span>";
        }
        
        return $info;
    }
    
    
    /**
     * Извиква се преди изпълняването на екшън
     */
    function on_BeforeAction($mvc, &$res, $act)
    {
        if($act != 'ajaxgeteditwatchstatus') return;
        
        if(!haveRole('user')) {
            $status = tr('Трябва да сте логнати, за да редактирате този запис.');
            $status = "<span class='errorMsg'>$status</span>";
        } else {
            
            $recId = Request::get('id', 'int');
            $Editors = cls::get('editwatch_Editors');
            $editedBy = array();
            
            if(isset($recId)) {
                $editedBy = $Editors->getAndSetCurrentEditors($mvc, $recId);
            }
            
            $status = $this->renderStatus($editedBy, $mvc, $recId);
        }
        
        $statusHash  = md5($status);
        
        $savedName    = "REFRESH_ROWS_" . md5(toUrl(getCurrentUrl()));
        $savedHash    = Mode::get($savedName);
        
        if(empty($savedHash)) $savedHash = md5($savedHash);
        
        if($statusHash != $savedHash) {
            
            Mode::setPermanent($savedName, $statusHash);
            
            $res = new stdClass();

            $res->content = $status;
            
            echo json_encode($res);
        }
        
        die;
    }
}