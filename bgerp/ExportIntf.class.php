<?php



/**
 * Интерфейс за експортиране на документи
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за експортиране на документи
 */
class bgerp_ExportIntf
{
    
    /**
     * Клас имплементиращ мениджъра
     */
    public $class;
    
    
    /**
     * Инпортиране на csv-файл в даден мениджър
     *
     * @param mixed $data - данни
     * @return mixed - експортираните данни
     */
    function export($data)
    {
        $this->class->export($data);
    }
    
    
    /**
     * Подготвя формата за експорт
     *
     * @param core_Form $form
     */
    function prepareExportForm(core_Form &$form)
    {
        $this->class->prepareExportForm($form);
    }
    
    
    /**
     * Проверява импорт формата
     *
     * @param core_Form $form
     */
    function checkExportForm(core_Form &$form)
    {
        $this->class->checkExportForm($form);
    }
    
    
    /**
     * Дали драйвъра може да се прикрепи към даден мениджър
     * Мениджърите към които може да се прикачва се дефинират в $applyOnlyTo
     *
     * @param core_Mvc $mvc - мениджър за който се проверява
     * @return boolean TRUE/FALSE - можели да се прикепи или не
     */
    function isApplicable(core_Mvc $mvc)
    {
        return $this->class->isApplicable($mvc);
    }
}