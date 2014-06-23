<?php



/**
 * Имитация на драйвер за IP сензор
 *
 *
 * @category  bgerp
 * @package   sens
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Имитация на драйвер за IP сензор
 */
class sens2_MockupDrv  
{
    
    /**
     * Заглавие на драйвера
     */
    var $title = 'Mockup';
    
    
    /**
     * Интерфeйси, поддържани от всички наследници
     */
    var $interfaces = 'sens2_DriverIntf';

    
    function getInputPorts()
    {
        return array('Temp1' => 'ºC', 'Memory' => 'B');
    }


    function getOutputPorts()
    {
        return array('D1' => '');
    }


    function prepareConfigForm($form)
    {
        $form->FLD('ip', 'ip', 'caption=Ip');
    }

    function checkConfigForm($form)
    {
        if($form->rec->ip{0} == '2') {
            $form->setError('ip', 'Ip-то не трябва да започва с 2');
        }
    }

    function readInputs($inputs, $config, &$persistentState)
    {
        if($inputs['Temp1']) {
            $inputs['Temp1'] = time();
        }

        if($inputs['Memory']) {
            $inputs['Memory'] = memory_get_usage(TRUE);
        }

        sleep(10);

        return $inputs;
    }
}