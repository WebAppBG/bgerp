<?php



/**
 * Регистър за вербални id-та
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_VerbalId extends core_Manager
{

     
    /**
     * Заглавие
     */
    var $title = "Регистър за вербални id-та";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, cms_Wrapper, plg_Sorting';
     
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'cms,admin,ceo';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'cms,admin,ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,admin,cms';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,admin,cms';
    

    /**
     * Полета за листовия изглед
     */
    var $listFields = '✍,vid,mvc,recId';


    /**
     * Поле за инструментите на реда
     */
    var $rowToolsField = '✍';
    
    
    /**
     * По кои полета ще се търси
     */
    var $searchFields = 'menu';


    /**
     * Описание на модела (таблицата)
     */
    function description()
    {   
        $this->FLD('vid',   'varchar(128)', 'caption=Вербално ID,mandatory');
        $this->FLD('mvc', 'class(interface=cms_SourceIntf, allowEmpty, select=title)', 'caption=Източник,mandatory');
        $this->FLD('recId', 'int', 'caption=Запис,mandatory');
 
        $this->setDbUnique('vid');
    }

    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
       // expect($mvc = cls::get($rec->source));

      //  $title = $mvc->getTitleById($rec->recId);

       // $row->source = $title;
    }


    /**
     * Записва връзката между вербално и реално ID в модела
     */
    static function saveVid($vid, $mvc, $id)
    {
        $rec = new stdClass();
        $rec->id    = self::fetchField(array("#vid = '[#1#]'", $vid), 'id');
        $rec->mvc   = core_Classes::getId($mvc);
        $rec->recId = $id;
        $rec->vid   = $vid;

        self::save($rec);
    }


    /**
     * Извлича id от $vid
     */
    static function fetchId($vid, $mvc)
    { 
        $mvcId = core_Classes::getId($mvc);

        $rec = self::fetch(array("#vid = '[#1#]'", $vid));

        if($rec && $rec->mvc == $mvcId) {
            $id = $rec->recId;
        } else {
            $id = FALSE;
        }

        return $id;
    }

 }