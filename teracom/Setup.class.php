<?php


/**
 * class teracom_Setup
 *
 * Инсталиране/Деинсталиране на драйвери за устройствата на Тераком ООД - Русе 
 *
 * @category  vendors
 * @package   teracom
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class teracom_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * От кои други пакети зависи
     */
    var $depends = '';
    
      
    /**
     * Описание на модула
     */
    var $info = "Драйвери за контролери на Тераком ООД";
    
            
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $html = parent::install();
                                 
        // Добавяме наличните драйвери
        $drivers = array(
            'teracom_TCW122BCM',
            'teracom_TCW121',
        );
        
        foreach ($drivers as $drvClass) {
            $html .= core_Classes::add($drvClass);
        }
         
        return $html;
    }
    
}
