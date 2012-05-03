<?php


/**
 * Роля за основен екип
 */
defIfNot('BGERP_ROLE_HEADQUARTER', 'Headquarter');


/**
 * Клас 'email_UserInboxPlg' - Създава пощенска кутия след създаване на нов потребител
 *
 *
 * @category  bgerp
 * @package   email
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_UserInboxPlg extends core_Plugin
{
    
    
    /**
     * id на потребителя, който е inCharge в модела
     */
    var $inCharge = FALSE;
    
    
    /**
     * Извиква се след вкарване на запис в таблицата на модела users
     */
    static function on_AfterSave($mvc, &$id, $rec)
    {
        //Ако се добавя или редактира потребител
        //При вход в системата не се задейства
        if($rec->nick) {
            //Данни необходими за създаване на папка
            $eRec = new stdClass();
            $eRec->inCharge = $rec->id;
            $eRec->access = "private";
            
            $eRec->domain = BGERP_DEFAULT_EMAIL_DOMAIN;
            $eRec->type = 'internal';
            $eRec->applyRouting = 'yes';
            
            $nick = $rec->nick;
            
            if (EF_USSERS_EMAIL_AS_NICK) {
                $nick = type_Nick::parseEmailToNick($rec->nick);
            }
            
            //Добавяме полето имейл, необходима за създаване на корица
            $eRec->email = email_Inboxes::getUserEmail($nick);
            $eRec->name = $nick;
            
            email_Inboxes::forceCoverAndFolder($eRec);
        }
        
        if($rec->first && $rec->id) {
            core_Users::addRole($rec->id, 'ceo');
            core_Users::addRole($rec->id, BGERP_ROLE_HEADQUARTER);
        }
    }
    
    
    /**
     * Преди записване на данните
     */
    function on_BeforeSave($mvc, $id, &$rec)
    {
        //Ако добавяме нов потребител
        if (!$rec->id) {
            
            if(!core_Users::fetch('1=1')) {
                $rec->first = TRUE;
            }
            $this->checkFolderCharge($rec);
            
            //Проверяваме дали имаме папка със същото име и дали някой е собственик
            if ($this->inCharge) {
                
                core_Message::redirect("Моля въведете друг Ник. Папката е заета от друг потребител.", 'page_Error', NULL, array('core_Users', 'add'));
            }
        }
    }
    
    
    /**
     * След вкарване на записите в едит форматa
     */
    function on_AfterInputEditForm($mvc, &$form)
    {
        //Ако формата е субмитната
        if ($form->isSubmitted()) {
            
            $rolesArr = type_Keylist::toArray($form->rec->roles);
            
            if(core_Users::fetch('1=1')) {
                foreach($rolesArr as $roleId) {
                    $roleType = core_Roles::fetchField($roleId, 'type');
                    $rolesByTypeArr[$roleType] += 1;
                }
                
                if($rolesByTypeArr['rang'] != 1) {
                    $form->setError('roles', "Потребителя трябва да има точно една роля за ранг");
                }
                
                if($rolesByTypeArr['team'] < 1) {
                    $form->setError('roles1', "Потребителя трябва да има поне една роля за екип");
                }
            }
            
            //Ако редактираме данните във формата
            if ($form->rec->id) {
                $this->checkFolderCharge($form->rec);
                
                //Ако имаме inCharge
                if ($this->inCharge) {
                    
                    //Ако потребителя не е собственик на новата папка показваме грешка
                    if ($form->rec->id != $this->inCharge) {
                        $form->setError('nick', "Моля въведете друг '{$form->fields['nick']->caption}'. Папката е заета от друг потребител.");
                    }
                }
            }
        }
    }
    
    
    /**
     * Проверяваме дали имаме папка със същото име
     */
    function checkFolderCharge($rec)
    {
        if ($this->inCharge !== FALSE) return;
        
        $nick = $rec->nick;
        
        if (EF_USSERS_EMAIL_AS_NICK) {
            $nick = type_Nick::parseEmailToNick($rec->nick);
        }
        
        //Името на папката
        $folderTitle = email_Inboxes::getUserEmail($nick);
        
        //Вземаме id' то на потребителя, който е inCharge
        $this->inCharge = doc_Folders::fetchField("#title = '{$folderTitle}'", 'inCharge');
        
        return ;
    }
}