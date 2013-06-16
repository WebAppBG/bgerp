<?php



/**
 * class dma_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с DMA
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hr_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'hr_EmployeeContracts';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Човешки ресурси";

    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'hr_WorkingCycles',
            'hr_WorkingCycleDetails',
            'hr_Shifts',
            'hr_Departments',
            'hr_Positions',
            'hr_ContractTypes',
            'hr_EmployeeContracts',
	        'hr_NKID',
	        'hr_NKPD'
        );
        
        // Роля ръководител на организация 
        // Достъпни са му всички папки и документите в тях
        $role = 'hr';
        $html .= core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('humanResources', 'Прикачени файлове в човешки ресурси', NULL, '1GB', 'user', 'hr');
        
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(2.31, 'Персонал', 'HR', 'hr_EmployeeContracts', 'default', "admin, ceo, {$role}");
        
        return $html;
    }
    
    
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