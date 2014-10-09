<?php



/**
 * Клас 'avatar_Setup' -
 *
 *
 * @category  vendors
 * @package   avatar
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class avatar_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = '';
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'fileman=0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Аватари или gravatar-и за потребителите";
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
    	
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина за аватари
        $html .= $Plugins->forcePlugin('Аватари', 'avatar_Plugin', 'core_Users', 'private');
        
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('Avatars', 'Аватари на потребители', 'jpg,jpeg,png,gif,image/*', '3MB', 'user', 'every_one');
        
        $Users = cls::get('core_Users');
        $AvatarPlg = cls::get('avatar_Plugin');
        
        $AvatarPlg->on_AfterDescription($Users);
        
        $html .= $Users->setupMVC();
        
        $Register = cls::get('avatar_Gravatar');
        $html .= $Register->setupMVC();
        
        $html .= "<li>Потребителите имат вече аватари";
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
    	$html = parent::deinstall();
    	
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме клавиатурата към password полета
        $Plugins->deinstallPlugin('avatar_Plugin');
        $html .= "<li>Махнати са аватарите на потребителите";
        
        return $html;
    }
}