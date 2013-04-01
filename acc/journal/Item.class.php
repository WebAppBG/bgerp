<?php

class acc_journal_Item
{
    /**
     *
     * @var int key(mvc=acc_Items)
     */
    public $id;


    /**
     *
     * @var int key(mvc=core_Classes)
     */
    protected $classId;


    /**
     * @var int key(mvc=$classId)
     */
    protected $objectId;

    /**
     *
     * @var stdClass
     */
    private $itemRec = NULL;


    /**
     * Конструктор
     * 
     * @param int|array $classId
     * @param int $objectId
     */
    public function __construct($classId, $objectId = NULL)
    {
        if (isset($classId) && is_array($classId)) {
            expect(count($classId) == 2);
            list($classId, $objectId) = $classId;
            $classId = core_Classes::getId($classId);
        }

        if (!isset($objectId)) {
            expect(is_null($classId) || is_numeric($classId), (array)$classId, $objectId);

            $this->id = $classId;

            if ($this->id) {
                expect($this->itemRec = acc_Items::fetch($this->id), func_get_args(), $classId, $objectId);
                $this->classId  = $this->itemRec->classId;
                $this->objectId = $this->itemRec->objectId;
            }
        } else {
            expect(is_numeric($objectId));

            $this->classId  = $classId;
            $this->objectId = $objectId;
        }
    }
    

    /**
     * Дали перото поддържа зададения интерфейс?
     * 
     * @param int|string $iface име или id на интерфейс (@see core_Interfaces)
     * @return boolean
     */
    public function implementsInterface($iface)
    {
        if (empty($iface)) {
            return empty($this->classId);
        }
        
        if (empty($this->classId)) {
            return FALSE;
        }
        
        if (is_numeric($iface)) {
            expect($iface = core_Interfaces::fetchField($iface, 'name'));
        }
        
        return cls::haveInterface($iface, $this->classId);
    }
    
    
    public function force($listId)
    {
        expect($itemId = acc_Items::force($this->classId, $this->objectId, $listId), "Не може да се форсира перо [{$this->classId}, {$this->objectId}]");
        
        if (isset($this->id)) {
            expect($this->id == $itemId);
        }
        
        $this->id = $itemId;
        
        return $this->id;
    }
    
    
    public function className()
    {
        if (empty($this->classId)) {
            return 'Неизвестен клас';
        }
        
        return core_Cls::getClassName($this->classId);
    }
}
