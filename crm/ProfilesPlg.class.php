<?php


/**
 * Плъгин, който заменя никовете на потребителите с линкове към техните профили
 * Действието му е във всички наследници на core_Manager и обхваща всички полета от тип key/list(mvc=core_Users), user, users
 *
 * @category  bgerp
 * @package   crm
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.12
 */
class crm_ProfilesPlg extends core_Plugin
{
    static function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    {   
        // В случаите, когато генерираме html за цел, различна от нормалното 
        // показване на обектите, не правим никаква обработка
        if(Mode::is('text', 'plain') || Mode::is('text', 'xhtml') || Mode::is('printing')) {

            return;
        }
         
        // Показваме никовете, като линкове, само при лист и сингъл изглед
        if($fields['-list'] || $fields['-single'] || !count($fields)) {
            $fieldsArr = $mvc->selectFields();
            foreach($fieldsArr as $name => $field) {
                $type = $field->type;

                // Ако е от type_Key
                if(cls::isSubclass($type, 'type_Key')) { 
                    if(cls::isSubclass($type->params['mvc'], 'core_Users')) {
                        if($type->params['select'] == 'nick' || !$type->params['select']) { 
                            if(($rec->{$name} > 0) && !strpos($row->{$name}, '<')) {
                            	if(crm_Profiles::haveRightFor('single', $rec->{$name})){
                            		$row->{$name} = crm_Profiles::createLink($rec->{$name});
                            	} else {
                            		$row->{$name} = crm_Profiles::getVerbal($rec->{$name}, 'userId');
                            	}
                            }
                        }
                    }
                }
                
                // Ако е от тип type_Keylist
                if(cls::isSubclass($type, 'type_Keylist')) {
                    if(cls::isSubclass($type->params['mvc'], 'core_Users')) {
                        if($type->params['select'] == 'nick' || !$type->params['select']) { 
                            if($rec->{$name} && !strpos($row->{$name}, '<')) {
                                $usersArr = keylist::toArray($rec->{$name});
                                $row->{$name} = '';
                                foreach($usersArr as $userId) {
                                    $row->{$name} .= ($row->{$name} ? ', ' : '') . crm_Profiles::createLink($userId);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}