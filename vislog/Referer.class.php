<?php



/**
 * Клас 'vislog_Referer'  
 *
 * Клас-мениджър, който логва от къде идват посетителите
 *
 *
 * @category  bgerp
 * @package   vislog
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class vislog_Referer extends core_Manager {
    
    
    /**
     * Заглавие
     */
    var $title = "Рефериране";
    
    
    /**
     * Старо име на модела
     */
    var $oldClassName = 'vislog_Refferer';
    

    /**
     * Кой  може да пише?
     */
    var $canWrite = "no_one";
    
    /**
     * Кой може да чете?
     */
    var $canRead = 'cms, ceo, admin';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo, admin, cms';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo, admin, cms';


    /**
     * Плъгини за зареждане
     */
    var $loadList = "plg_RowTools,plg_Created,vislog_Wrapper";
    

    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD("referer", 'varchar(255)', 'caption=Referer,oldFieldName=refferer');
        $this->FLD("query", 'varchar(255)', 'caption=Query');
        $this->FLD('searchLogResourceId', 'key(mvc=vislog_HistoryResources,title=query)', 'caption=Ресурс');
        $this->FLD('ip', 'ip(15,showNames)', 'caption=Ip');
    }
    
    
    /**
     * Добавя запис за страницата от която идва посетителя
     */
    function add($resource)
    {
        $rec = new stdClass();

        $rec->referer = $_SERVER['HTTP_REFERER'];
        
        if($rec->referer) {
            
            $parts = @parse_url($rec->referer);
            
            $localHost = $_SERVER['SERVER_NAME'];
            
            if(stripos($parts['host'], $localHost) === FALSE) {
                
                parse_str($parts['query'], $query);
                
                $search_engines = array(
                    'bing' => 'q',
                    'google' => 'q',
                    'yahoo' => 'p'
                );
                
                preg_match('/(' . implode('|', array_keys($search_engines)) . ')\./', $parts['host'], $matches);
                
                $rec->query = isset($matches[1]) && isset($query[$search_engines[$matches[1]]]) ? $query[$search_engines[$matches[1]]] : '';
                
                $rec->searchLogResourceId = $resource;
                
                // Поставяме IP ако липсва
                if(!$rec->ip) {
                    $rec->ip = $_SERVER['REMOTE_ADDR'];
                }
                
                $this->save($rec);
            }
        }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->query->orderBy('#createdOn', 'DESC');
    }


    /**
     * Вербализиране на row
     * Поставя хипервръзка на ip-то
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->ip =  type_Ip::decorateIp($rec->ip, $rec->createdOn, TRUE, TRUE);
    }


    /**
     * Показва съкратена информация за реферера, ако има такъв
     */
    static function getReferer($ip, $time)
    {
        $rec = self::fetch(array("#ip = '[#1#]' AND #createdOn = '[#2#]'", $ip, $time));

        if($rec) {
            $parse = @parse_url($rec->referer);
            
            $res = str_replace('www.', '', strtolower($parse['host']));

            if($rec->query) {
                $res .= ": " . self::getVerbal($rec, 'query');
            }
            
            return $res;
        }

    }


} 