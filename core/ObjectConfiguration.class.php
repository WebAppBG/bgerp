<?php


/**
 * Клас 'core_ObjectConfiguration' - Поддръжка на конфигурационни данни
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class core_ObjectConfiguration extends core_BaseClass
{
    
    /**
     * Описание на конфигурацията
     */
    var $_description = array();
    

    /**
     * Стойности на константите
     */
    var $_data = array();
    

    /**
     * Конструктор
     */
    public function init($params = array())
    {
        list($description, $data, $userId) = $params;
        
        if (is_string($description)) {
            $description = unserialize($description);
        }

        if(is_array($description)) {
            $this->_description = $description;
        }

        if (is_string($data) && strlen($data)) {
            $data = unserialize($data);
        }
        
        if(is_array($data)) {
            $this->_data = $data;
        }
        
        if (($userId < 1) || ($userId == core_Users::getCurrent())) {
            // Данните от конфигурацията на текущия потребител
            $configDataArr = (array)core_Users::getCurrent('configData');
        } else {
            // Данните от конфигурацията на съответния потребител
            $configDataArr = (array)core_Users::fetchField($userId, 'configData');
        }
        
        // Сетваме данните
        foreach ($configDataArr as $name => $value) {
            $this->_data[$name] = $value;
        }
    }


    /**
     * 'Магически' метод, който връща стойността на константата
     */
    function __get($name)
    { 
        $this->invoke('BeforeGetConfConst', array(&$value, $name));

        // Търси константата в данните въведени през уеб-интерфейса
        if(!isset($value) && !empty($this->_data[$name])) {

            $value = $this->_data[$name];
        }

        // Търси константата като глобално дефинирана
        if(!isset($value) && defined($name)) {

            $value = constant($name);
        }
        
        if($this->_description[$name]) {
            expect(isset($value), "Недефинирана константа $name", $this->_description, $this->_data);
        }

        return $value;
    }


    /**
     * Връща броя на описаните константи
     */
    function getConstCnt()
    {
        return count($this->_description);
    }


    /**
     * Връща броя на недефинираните константи
     */
    function haveErrors()
    {
        $cnt = 0;
        if(count($this->_description)) {
            foreach($this->_description as $name => $descr) {
                $params = arr::make($descr[1], TRUE);
                if(!$params['mandatory']) continue;
                if(isset($this->_data[$name]) && $this->_data[$name] !== '') continue;
                if(defined($name) && constant($name) !== '' && constant($name) !== NULL) continue;

                return TRUE;
            }
        }

        return FALSE;
    }
    
}