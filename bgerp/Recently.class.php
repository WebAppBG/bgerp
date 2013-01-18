<?php



/**
 * Последни документи и папки, посетени от даден потребител
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Последни документи и папки
 */
class bgerp_Recently extends core_Manager
{
    
    
    /**
     * Необходими мениджъри
     */
    var $loadList = 'bgerp_Wrapper, plg_RowTools, bgerp_plg_GroupByDate';


    /**
     * Името на полито, по което плъгина GroupByDate ще групира редовете
     */
    var $groupByDateField = 'last';
    
    
    /**
     * Заглавие
     */
    var $title = 'Последни';
    
    
    /**
     * Права за писане
     */
    var $canWrite = 'admin';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('type', 'enum(folder,document)', 'caption=Тип, mandatory');
        $this->FLD('objectId', 'int', 'caption=Id');
        $this->FLD('userId', 'key(mvc=core_Users)', 'caption=Потребител');
        $this->FLD('last', 'datetime(format=smartTime)', 'caption=Последно');
        $this->FLD('hidden', 'enum(no,yes)', 'caption=Скрито,notNull');

        $this->setDbUnique('type, objectId, userId');
    }
    
    
    /**
     * Добавя известие за настъпило събитие
     * @param varchar $msg
     * @param array $url
     * @param integer $userId
     * @param enum $priority
     */
    static function add($type, $objectId, $userId = NULL)
    {
        // Не добавяме от опресняващи ajax заявки
        if(Request::get('ajax_mode')) return;
        
        // Debug
        self::log("$type, $objectId " . $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING']);

        $rec = new stdClass();
        
        $rec->type      = $type;
        $rec->objectId  = $objectId;
        $rec->userId    = $userId ? $userId : core_Users::getCurrent();
        $rec->last      = dt::verbal2mysql();
        
        $rec->id = bgerp_Recently::fetchField("#type = '{$type}'  AND #objectId = $objectId AND #userId = {$rec->userId}");
        
        bgerp_Recently::save($rec);
    }

    
    /**
     * Скрива посочените записи
     * Обикновено след Reject
     */
    static function setHidden($type, $objectId, $hidden = 'yes', $userId=NULL) 
    {
        $query = self::getQuery();

        $query->where("#type = '{$type}'  AND #objectId = $objectId");
        
        if ($userId) {
            $query->where("#userId = '{$userId}'");
        }

        while($rec = $query->fetch()) {
            $rec->hidden = $hidden;
            self::save($rec);
        }
    }
   
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Manager $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        if($rec->type == 'folder') {
            try {
                $folderRec = doc_Folders::fetch($rec->objectId);
                $folderRow = doc_Folders::recToVerbal($folderRec);
                $row->title = $folderRow->title;
                $state = $folderRec->state;
            } catch (core_exception_Expect $ex) {
                $row->title = "Проблемна папка № {$rec->objectId}";
            }
        } elseif ($rec->type == 'document') {
            
            try {
                $docProxy = doc_Containers::getDocument($rec->objectId);
                $docRow = $docProxy->getDocumentRow();
                $docRec = $docProxy->fetch();
                
                $attr['class'] .= 'linkWithIcon';
                $attr['style'] = 'background-image:url(' . sbf($docProxy->getIcon()) . ');';
                $threadRec = doc_Threads::fetch($docRec->threadId);
                $state     = $threadRec->state;
                $row->title = ht::createLink(str::limitLen($docRow->title, 70),
                    array($docProxy->instance, 'single',
                        'id' => $docRec->id),
                    NULL, $attr);
            } catch (core_exception_Expect $ex) {
                $row->title = "Проблемен контейнер № {$rec->objectId}";
            }
        }

        if($state == 'opened') {
            $row->title = new ET("<div class='state-{$state}'>[#1#]</div>", $row->title);
        }
     }


    /**
     * @todo Чака за документация...
     */
    static function render($userId = NULL)
    {
        if(empty($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        $Recently = cls::get('bgerp_Recently');
        
        // Създаваме обекта $data
        $data = new stdClass();
        
        // Създаваме заявката
        $data->query = $Recently->getQuery();
        
        // Подготвяме полетата за показване
        $data->listFields = 'last,title';
        
        // Подготвяме формата за филтриране
        // $this->prepareListFilter($data);
        
        $data->query->where("#userId = {$userId} AND #hidden != 'yes'");
        $data->query->orderBy("last=DESC");
        
        // Подготвяме навигацията по страници
        $Recently->prepareListPager($data);
        
        // Подготвяме записите за таблицата
        $Recently->prepareListRecs($data);
        
        // Подготвяме редовете на таблицата
        $Recently->prepareListRows($data);
        
        // Подготвяме заглавието на таблицата
        $data->title = tr("Последно");
        
        // Подготвяме лентата с инструменти
        $Recently->prepareListToolbar($data);
        
        // Рендираме изгледа
        $tpl = $Recently->renderPortal($data);
        
        return $tpl;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function renderPortal($data)
    {
        $Recently = cls::get('bgerp_Recently');
        
        $tpl = new ET("
            <div class='clearfix21 portal' style='background-color:#f8f8ff'>
            <div style='background-color:#eef' class='legend'>[#PortalTitle#]</div>
            [#PortalPagerTop#]
            [#PortalTable#]
            [#PortalPagerBottom#]
            </div>
          ");
        
        // Попълваме титлата
        $tpl->append($data->title, 'PortalTitle');
        
        // Попълваме горния страньор
        $tpl->append($Recently->renderListPager($data), 'PortalPagerTop');
        
        // Попълваме долния страньор
        $tpl->append($Recently->renderListPager($data), 'PortalPagerBottom');
        
        // Попълваме таблицата с редовете
        $tpl->append($Recently->renderListTable($data), 'PortalTable');
        
        return $tpl;
    }
    
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->query->orderBy("last=DESC");
    }
    
    
    /**
     * Какво правим след сетъпа на модела?
     */
    static function on_AfterSetupMVC()
    {
    
    }


}
