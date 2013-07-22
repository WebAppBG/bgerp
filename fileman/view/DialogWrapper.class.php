<?php

/**
 * Клас 'fileman_view_DialogWrapper' -
 *
 *
 * @category  vendors
 * @package   fileman
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class fileman_view_DialogWrapper extends page_Html {
    
    
    /**
     * Изпраща към изхода съдържанието, като преди това го опакова
     */
    function output($content = '', $place = 'CONTENT')
    {
        $this->replace("UTF-8", 'ENCODING');
        
        $this->append("<link rel=\"stylesheet\" type=\"text/css\" href=" . sbf("fileman/css/default.css") . "/>\n", "HEAD");
        
        $this->append("<link rel=\"stylesheet\" type=\"text/css\" href=" . sbf("css/common.css") . "/>\n", "HEAD");
        
        $this->append("<script type=\"text/javascript\" src=" . sbf("js/efCommon.js") . "></script>\n", "HEAD");
        
        parent::output($content, 'PAGE_CONTENT');
    }
}