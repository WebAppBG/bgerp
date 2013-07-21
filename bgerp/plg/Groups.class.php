<?php



/**
 * Клас 'bgerp_plg_Groups' - Поддръжка на групи и групиране
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class bgerp_plg_Groups extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
     */
    function on_AfterDescription(&$mvc)
    {
        $mvc->doWithSelected = arr::make($mvc->doWithSelected) + array('group' => 'Групиране'); 
    }
    

    /**
     * Смяна статута на 'rejected'
     *
     * @return core_Redirect
     */
    function on_BeforeAction(core_Manager $mvc, &$res, $action)
    {
        if ($action == 'group') {
            
            // Създаване на формата
            $form = cls::get('core_Form');
            $form->FNC('id', 'int', 'input=hidden,silent');
            $form->FNC('Selected', 'text', 'input=hidden,silent');
            $form->FNC('ret_url', 'varchar', 'input=hidden,silent');
            $form->input(NULL, 'silent');
            $rec = $form->rec;

            expect($rec->id || $rec->Selected, $rec);
            
            $selArr = arr::make($rec->Selected);
            if($id) {
                $selArr[] = $id;
            }
            
            setIfNot($groupField, $mvc->groupField, 'groupList');

            $groupFieldType = $mvc->fields[$groupField]->type;
            
            $allGroups = $groupFieldType->getSuggestions();
            
            $canDelGroups  =  $canAddGroups = array();
            
            // Премахване на лишите или недостъпните id-та
            foreach($selArr as $i => $ind) {
                $obj = (object) array('id' => $ind);
                if(!is_numeric($ind) || !$mvc->haveRightFor('group', $obj)) {
                    unset($selArr[$i]);
                }


                $groups = $mvc->fetchField($ind, $groupField);
                $gArr = keylist::toArray($groups);
                foreach($gArr as $g) {
                    if($allGroups[$g]) {
                        $canDelGroups[$g]++;
                    }
                }

                foreach($allGroups as $g => $caption) {
                    if(!$gArr[$g]) {
                        $canAddGroups[$g]++;
                    }
                }
            }
            
            expect(count($selArr));

            if(count($selArr) == 1) {
                $id = $selArr[0];
                $groups = $mvc->fetchField($id, $groupField);
                $form->title = 'Промяна в групите на |*<i style="color:#ffffaa">' .  $mvc->getTitleById($selArr[0]) . '</i>';
                $form->FNC('groups', $mvc->fields[$groupField]->type, 'caption=Групи,input');
                $form->setDefault('groups', $groups);
            } else {
                $form->title = 'Групиране на |*' . count($selArr) . '| ' . mb_strtolower($mvc->title);
                if(count($canAddGroups)) {
                    $addType = cls::get('type_Set');
                    foreach($canAddGroups as $g => $cnt) {
                        $addType->suggestions[$g] = $allGroups[$g] . " ({$cnt})";
                    }
                    $form->FNC('addGroups', $addType, 'caption=Добавяне към->Групи,input');
                }
                if(count($canDelGroups)) {
                    $delType = cls::get('type_Set');
                    foreach($canDelGroups as $g => $cnt) {
                        $delType->suggestions[$g] = $allGroups[$g] . " ({$cnt})";
                    }
                    $form->FNC('delGroups', $delType, 'caption=Премахване от->Групи,input');
                }
            }
            
            $form->toolbar->addSbBtn('Запис');
            $retUrl = getRetUrl();
            if(!count($retUrl)) {
                if(count($selArr) == 1) {
                    $retUrl = array($mvc, 'single', $selArr[0]);
                } else {
                    $retUrl = array($mvc, 'list');
                }
            }

            $form->toolbar->addBtn('Отказ', $retUrl);

            $form->input();
            
            if($form->isSubmitted()) {
                
                $rec = $form->rec;
                 
                $changed = 0;
                if(count($selArr) == 1) {
                    $obj = new stdClass();
                    $obj->id = $id;
                    $obj->{$groupField} = $rec->groups;
                    if($groups != $rec->groups) {
                        $mvc->save($obj, $groupField); 
                        $changed = 1;
                    }
                } else {
                    foreach($selArr as $id) {
                        $exGroups = $groups = $mvc->fetchField($id, $groupField);
                        $groups = keylist::merge($groups, arr::make($rec->addGroups, TRUE));
                        $groups = keylist::diff($groups,  arr::make($rec->delGroups, TRUE));
                        $obj = new stdClass();
                        $obj->id = $id;
                        $obj->{$groupField} = $groups;
                        if($groups != $exGroups) {
                            $mvc->save($obj, $groupField); 
                            $changed++;
                        }
                    }
                }
                
                if(!$changed) {
                    $msg = tr("Не бяха променени групите на нито една фирма");
                } elseif($changed == 1) {
                    $msg = tr("Бяха променени групите на една фирма");
                } else {
                    $msg = tr("Бяха променени групите на|* {$changed} |фирми");
                }

                $res = new Redirect($retUrl, $msg);
            } else {
                $res = $mvc->renderWrapping($form->renderHtml());
            }

            return FALSE;
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
            if(!$requiredRoles && ($action == 'group') && $mvc->haveRightFor('single', $rec, $userId)) {
                $requiredRoles = 'user';
            }
        }
    }
}