<?php 




/**
 * Съхранява данни за автомобилите за проследяване
 *
 *
 * @category  bgerp
 * @package   tracking
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class tracking_Vehicles extends core_Manager
{
    
    /**
     * Заглавие
     */
    public $title = 'Превозни средства';
    

    /**
     * Плъгини за зареждане
     *
     * var string|array
     */
    public $loadList = 'plg_Created, plg_Rejected, plg_RowTools, plg_State2, tracking_Wrapper';
    
    /**
     * Права
     */
    public $canWrite = 'tracking,admin,ceo';
    
    public $canRead = 'tracking,admin,ceo';
    
    public $canList = 'tracking,admin,ceo';
    
//     public $canAdd = 'tracking, admin, ceo';
    
     public $canEdit = 'tracking, admin, ceo';
    
    public $canDelete = 'no_one';
    
    //public $canSingle = 'tracking,admin,ceo';
    
    /**
     * Полета за показване
     *
     * var string|array
     */
//    public $listFields = 'trackerId, make, model, number';
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('trackerId', 'varchar(12)', 'caption=Тракер Id');
        $this->FLD('make', 'varchar(12)', 'caption=марка');
        $this->FLD('model', 'varchar(12)', 'caption=модел');
        $this->FLD('number', 'varchar(10)', 'caption=рег. номер');
        $this->FLD('personId', 'key(mvc=crm_Persons, select=name)', 'caption=Водач');
    }
    
    
}
