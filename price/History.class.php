<?php



/**
 * История с кеширани цени
 *
 *
 * @category  bgerp
 * @package   price
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Ценоразписи
 */
class price_History extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Кеширани цени';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_Rejected, plg_RowTools, price_Wrapper';
                    
    
    /**
     * Детайла, на модела
     */
    var $details = 'price_ListRules';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, listId, validFrom, productId, packagingId, price';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'ceo,admin';
    
    
    /**
     * Кой може да го промени?
     */
    var $canWrite = 'ceo';
    
    
     /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo';
    

    /**
     * Масив с всички ремена, които имат отношение към историята на цените
     */
    static $timeline = array();


    /**
     * Масив с кеш на изчислените стойности
     */
    static $cache = array();


    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('listId', 'key(mvc=price_Lists,select=title)', 'caption=Ценоразпис');
        $this->FLD('validFrom', 'datetime', 'caption=В сила от');
        $this->FLD('productId', 'key(mvc=cat_Products,select=name,allowEmpty)', 'caption=Продукт,mandatory');
        $this->FLD('packagingId', 'key(mvc=cat_Packagings,select=name,allowEmpty)', 'caption=Опаковка');
        $this->FLD('price', 'double(decimals=5)', 'caption=Цена');
    }


    /**
     * Връща началото на най-близкия исторически интервал до посоченото време
     */
    static function canonizeTime($datetime)
    {   
        $timeline = &self::$timeline;
        
        // Ако тази стойност вече е извлечена, директно я връщаме
        if(self::$cache[$datetime]) {

            return self::$cache[$datetime];
        }

        // Ако времевата линия липсва, опитваме се да я извадим от кеша
        if(!count($timeline)) {
            self::$timeline = core_Cache::get('price_History', 'timeline');
        }
 
        // Ако времевата линия пак липсва, генерираме я и я записваме в кеша
        if(!is_array($timeline) || !count($timeline)) {
            
            $timeline = array();
            
            // Вземаме всички времена от правилата
            $query = price_ListRules::getQuery();
            $query->show('validFrom,validUntil');
            while($rec = $query->fetch()) {  
                $timeline[$rec->validFrom] = TRUE;
                if($rec->validUntil) {
                    $timeline[$rec->validUntil] = TRUE;
                }
            }

            // Вземаме всички времена от групите на продуктите
            $query = price_GroupOfProducts::getQuery();
            $query->show('validFrom');
            while($rec = $query->fetch()) {
                $timeline[$rec->validFrom] = TRUE;
            }

            // Вземаме всички времена от ценоразписите на клиентите
            $query = price_ListToCustomers::getQuery();
            $query->show('validFrom');
            while($rec = $query->fetch()) {
                $timeline[$rec->validFrom] = TRUE;
            }
  
            // Сортираме обратно масива, защото очакваме да търсим предимно съвременни цени
            krsort($timeline);
            $timeline = array_keys($timeline);
            core_Cache::set('price_History', 'timeline', $timeline, 300000);
        }
       
        // Връщаме първото срещнато време, което е по-малко от аргумента
        foreach($timeline as $t) {
            if($datetime >= $t) {
                self::$cache[$datetime] = $t;

                return $t;
            }
        }
    }


    /**
     * Инвалидира кеша с времевата линия
     */
    static function removeTimeline()
    {
        // Изтриваме кеша
        core_Cache::remove('price_History', 'timeline');

    }


    /**
     * Връща кешираната цена за продукта
     */
    static function getPrice($listId, $datetime, $productId, $packagingId = NULL)
    {
        $validFrom = self::canonizeTime($datetime);
        
        if(!$validFrom) return;
        
        $cond = "#listId = {$listId} AND #validFrom = '{$validFrom}' AND #productId = {$productId} AND #packagingId";

        if($packagingId) {
           $cond .= " = {$packagingId}";
        } else {
            $cond .= " IS NULL";
        }

        $price = self::fetchField($cond, 'price');

        return $price;
    }
    
    
    /**
     * Записва кеш за цената на продукта
     */
    static function setPrice($price, $listId, $datetime, $productId, $packagingId = NULL)
    {
        $validFrom = self::canonizeTime($datetime);
        
        if(!$validFrom) return;
        
        $rec = new stdClass();
        $rec->listId      = $listId;
        $rec->validFrom   = $validFrom;
        $rec->productId   = $productId;
        $rec->packagingId = $packagingId;
        $rec->price       = $price;
        self::save($rec);

        return $rec;
    }


 }