<?php



/**
 * Драйвер за IP контролер Teracom TCW122B-CM
 *
 * @see http://www.teracom.cc/index.php/products/monitoring-and-control/ethernet-controllers/tcw122b-cm.html
 *
 * @category  bgerp
 * @package   sens
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class teracom_TCW122BCM
{
    
    /**
     * Заглавие на драйвера
     */
    var $title = 'TCW122B-CM';
    
    
    /**
     * Интерфeйси, поддържани от всички наследници
     */
    var $interfaces = 'sens2_DriverIntf';

    
    /**
     * Описание на входовете на драйвера
     */
    var $inputs = array(
        'T1'   => array('caption'=>'Температура 1', 'uom'=>'ºC', 'xmlPath'=>'/Temperature1[1]'),
        'T2'   => array('caption'=>'Температура 2', 'uom'=>'ºC', 'xmlPath'=>'/Temperature2[1]'),
        'Hr1'  => array('caption'=>'Влажност 1', 'uom'=>'%', 'xmlPath'=>'/Humidity1[1]'),
        'Hr2'  => array('caption'=>'Влажност 2', 'uom'=>'%', 'xmlPath'=>'/Humidity2[1]'),
    	'InD1' => array('caption'=>'Цифров вход 1', 'uom' => '', 'xmlPath'=>'/DigitalInput1[1]'),
        'InD2' => array('caption'=>'Цифров вход 2', 'uom' => '', 'xmlPath'=>'/DigitalInput2[1]'),
        'InA1' => array('caption'=>'Аналогов вход 1', 'uom' => 'V', 'xmlPath'=>'/AnalogInput1[1]'),
        'InA2' => array('caption'=>'Аналогов вход 2', 'uom' => 'V', 'xmlPath'=>'/AnalogInput2[1]')
    );
    
    
    /**
     * Описание на изходите на драйвера
     */
    var $outputs = array(
        'OutD1' => array('caption'=>'Цифров изход 1', 'uom' => '', 'xmlPath'=>'/Relay1[1]', 'cmd'=>'?r1'),
        'OutD2' => array('caption'=>'Цифров изход 2', 'uom' => '', 'xmlPath'=>'/Relay2[1]', 'cmd'=>'?r2')
    );


    /**
     *  Информация за входните портове на устройството
     *
     * @see  sens2_DriverIntf
     *
     * @return  array
     */
    function getInputPorts()
    {
        foreach($this->inputs as $name => $params) {
            $res[$name] = (object) array('caption' => $params['caption'], 'uom' => $params['uom']);
        }

        return $res; ;
    }

    
    /**
     * Информация за изходните портове на устройството
     *
     * @see  sens2_DriverIntf
     *
     * @return  array
     */
    function getOutputPorts()
    {
        foreach($this->outputs as $name => $params) {
            $res[$name] = array('caption' => $params['caption'], 'uom' => $params['uom']);
        }

        return $res; ;
    }

    
    /**
     * Подготвя форма с настройки на контролера, като добавя полета с $form->FLD(....)
     *
     * @see  sens2_DriverIntf
     *
     * @param core_Form
     */
    function prepareConfigForm($form)
    {
        $form->FNC('ip', 'ip', 'caption=IP,hint=Въведете IP адреса на устройството, input, mandatory');
        $form->FNC('port', 'int(5)', 'caption=Port,hint=Порт, input, mandatory,value=80');
        $form->FNC('user', 'varchar(10)', 'caption=User,hint=Потребител, input, mandatory, value=admin, notNull');
        $form->FNC('password', 'password(allowEmpty)', 'caption=Password,hint=Парола, input, value=admin, notNull,autocomplete=off');
    }
    

    /**
     * Проверява след  субмитване формата с настройки на контролера
     *
     * @see  sens2_DriverIntf
     *
     * @param   core_Form
     */
    function checkConfigForm($form)
    {
    }


    /**
     * Прочита стойностите от сензорните входове
     *
     * @see  sens2_DriverIntf
     *
     * @param   array   $inputs
     * @param   array   $config
     * @param   array   $persistentState
     * 
     * @return  mixed
     */
    function readInputs($inputs, $config, &$persistentState)
    {   
        // Подготвяме URL-то
        $url = new ET("http://[#user#]:[#password#]@[#ip#]:[#port#]/status.xml");
        $url->placeArray($config);
        $url = $url->getContent();
        
        echo "<li> $url";

        // Извличаме XML-a
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); 
        $xml = curl_exec($ch);
        curl_close($ch);
     
        // Ако не сме получили xml - връщаме грешка
        if (empty($xml) || !$xml) {
            
            return "Грешка при четене от {$config->ip}";
        }
        
        echo "<br><pre>$xml</pre>";

        // Парсираме XML-а
        $result = array();
        $this->XMLToArrayFlat(simplexml_load_string($xml), $result);
        
        // Извличаме състоянията на входовете от парсирания XML
        foreach ($this->inputs as $name => $details) {
            if($inputs[$name]) {
                $res[$name] = $result[$details['xmlPath']];
            }
        }
        
        // Извличаме състоянията на изходите от парсирания XML
        foreach ($this->outputs as $name => $details) {
            if($inputs[$name]) {
                $res[$name] = $result[$details['xmlPath']];
            }
        }
        
        // Цифровизираме стойностите
        foreach($res as $name => $value) {
            if($value) {
                switch (strtoupper($value)) {
                    case 'ON':
                        $res[$name] = 1;
                        break;
                    case 'OFF':
                        $res[$name] = 0;
                        break;
                    default:
                        $res[$name] = (float) $value;
                }
            }
        }

        echo "<br><pre>";
        print_r($res);
        echo "</pre>";

        return $res;
    }

    
    
    /**
     * Сетва изходите на драйвера по зададен масив
     *
     * @return bool
     */
    function setOuts($outs)
    {
        $baseUrl = "http://{$this->settings->user}:{$this->settings->password}@{$this->settings->ip}:{$this->settings->port}/status.xml";
        
        foreach ($this->outs as $out => $attr) {
            $res[] = $baseUrl . $attr['cmd'] . "=" . $outs[$out];
        }
        
        
        // Превключваме релетата
        foreach ($res as $cmd) {
            $ch = curl_init("$cmd");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_exec($ch);
            curl_close($ch);
        }
    }
    
    
    /**
     * Преобразува SimpleXMLElement в масив
     */
    function XMLToArrayFlat($xml, &$return, $path = '', $root = FALSE)
    {
        $children = array();
        
        if ($xml instanceof SimpleXMLElement) {
            $children = $xml->children();
            
            if ($root){ // we're at root
                $path .= '/' . $xml->getName();
            }
        }
        
        if (count($children) == 0){
            $return[$path] = (string) $xml;
            
            return;
        }
        
        $seen = array();
        
        foreach ($children as $child => $value) {
            $childname = ($child instanceof SimpleXMLElement) ? $child->getName() : $child;
            
            if (!isset($seen[$childname])){
                $seen[$childname] = 0;
            }
            $seen[$childname]++;
            $this->XMLToArrayFlat($value, $return, $path . '/' . $child . '[' . $seen[$childname] . ']');
        }
    }
}
