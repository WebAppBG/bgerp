<?php


/**
 * Превантивни действия
 *
 * @category  bgerp
 * @package   support
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class support_Preventions extends core_Master
{
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Полета, които ще се клонират
     */
    var $cloneFields = 'subject, body';
    
    
    /**
     * Заглавие
     */
    var $title = "Превантивни действия";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Превантивни действия";
    
    
    /**
     * Кой има право да го чете?
     */
    var $canRead = 'user';
    
    
    /**
     * Кой има право да го променя?
     */
    var $canEdit = 'user';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'user';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'user';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin, support';
    
    
    /**
     * Кой има право да изтрива?
     */
    var $canDelete = 'no_one';


    /**
     * 
     */
    var $canSingle = 'admin, support';
    

    /**
     *
     */
    var $canActivate = 'user';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'support_Wrapper, doc_SharablePlg, doc_DocumentPlg, plg_RowTools, 
        plg_Printing, doc_ActivatePlg, bgerp_plg_Blank';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'support/tpl/SingleLayoutPreventions.shtml';
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
//    var $singleIcon = 'img/16/xxx.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = 'PRV';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'subject, body';
    
    
    /**
     * Полето "Относно" да е хипервръзка към единичния изглед
     */
    var $rowToolsSingleField = 'subject';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, subject, sharedUsers=Споделяне, createdOn, createdBy';

    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('subject', 'varchar', 'caption=Относно,mandatory,width=100%');
        $this->FLD('body', 'richtext(rows=10,bucket=Support)', 'caption=Коментар,mandatory');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $subject = $this->getVerbal($rec, 'subject');
        
        $row = new stdClass();
        
        $row->title = $subject;
        
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        $row->authorId = $rec->createdBy;
        
        $row->state = $rec->state;
        
        $row->recTitle = $rec->subject;
        
        return $row;
    }
    

    /**
     * Реализация  на интерфейсния метод ::getThreadState()
     * Добавянето на коментар не променя състоянието на треда
     */
    static function getThreadState($id)
    {

        return NULL;
    }


    /**
     * Потребителите, с които е споделен този документ
     *
     * @return string keylist(mvc=core_Users)
     * @see doc_DocumentIntf::getShared()
     */
    static function getShared($id)
    {

        return static::fetchField($id, 'sharedUsers');
    }
    
    
    /**
     * Проверка дали нов документ може да бъде
     * добавен в посочената нишк-а
     *
     * @param $threadId int ид на нишката
     * @param $firstClass string класът на първия документ в нишката
     * 
     * @return boolean
     */
    public static function canAddToThread($threadId, $firstClass)
    {
        
        // Ако някой от документите в нишката, е support_Issue
        return doc_Containers::checkDocumentExistInThread($threadId, 'support_Issues');
    }
    
    
	/**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param int $folderId - id на папката
     * @param string $firstClass - класът на корицата на папката
     * 
     * @return boolean
     */
    public static function canAddToFolder($folderId, $folderClass)
    {
        // Да не може да се добавя в папка, като начало на нишка
        return FALSE;
    }
    
    
    /**
     * 
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        
        $data->row->subject = tr("ПД|*: {$data->row->subject}");
    }
}
