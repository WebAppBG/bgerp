<?php



/**
 * class dec_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с декларациите за съответствия
 *
 *
 * @category  bgerp
 * @package   dec
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class dec_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'dec_Declarations';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Декларации за съответствия";

    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
   var $managers = array(
            'dec_Declarations',
			'dec_Statements',
   			'dec_DeclarationDetails',
   			'dec_DeclarationTypes',
			'dec_Materials',
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'dec';

            
    /**
     * Инсталиране на пакета
     */
    /*function install()
    {  
    	$html = parent::install(); 
    	 
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('humanResources', 'Прикачени файлове в човешки ресурси', NULL, '1GB', 'user', 'hr');
        
        return $html;
    }*/
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }

}