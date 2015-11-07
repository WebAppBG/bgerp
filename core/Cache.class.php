<?php



/**
 * Колко голям да бъде максималния обект, който се съхранява
 * в кеша не-компресиран?
 */
defIfNot('EF_CACHE_MAX_UNCOMPRESS', 10000);


/**
 * Максимален размер за полето на типа
 */
defIfNot('EF_CACHE_TYPE_SIZE', 16);


/**
 * Максимален размер за полето на манипулатора
 */
defIfNot('EF_CACHE_HANDLER_SIZE', 32);


/**
 * 
 */
defIfNot('CORE_CACHE_PREFIX_SALT', md5(EF_SALT . '_CORE_CACHE'));


/**
 * Клас 'core_Cache' - Кеширане на обекти, променливи или масиви за определено време
 *
 *
 * @category  bgerp
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Cache extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Кеширани обекти';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Кеширан обект";
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'admin';
	
	
	/**
	 * 
	 */
	public $canAdd = 'no_one';
	
	
	/**
	 * 
	 */
	public $canEdit = 'no_one';
	
	
	/**
	 * 
	 */
	public $canDelete = 'no_one';
	

    /**
     * Кои полета ще извличаме, преди изтриване на заявката
     */
    public $fetchFieldsBeforeDelete = 'id,key';

    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('key', 'identifier(' . (EF_CACHE_TYPE_SIZE + EF_CACHE_HANDLER_SIZE + 3) . ')', 'caption=Ключ,notNull');
        $this->FLD('data', 'blob(16777215)', 'caption=Данни');
        $this->FLD('lifetime', 'int', 'caption=Живот,notNull');     // В секунди
        $this->load('plg_Created,plg_SystemWrapper,plg_RowTools');
        
        $this->setDbUnique('key');
    }
    
    
    /**
     * Връща съдържанието на кеша за посочения обект
     */
    static function get($type, $handler, $keepMinutes = NULL, $depends = array())
    {
        $Cache = cls::get('core_Cache');
        
        $key = $Cache->getKey($type, $handler);
        
        if($data = $Cache->getData($key, $keepMinutes)) {
            if($dHash = $Cache->getDependsHash($depends)) {
                
                // Ако хешовете на кешираните данни и изчисления хеш не съвпадат - 
                // изтриваме кеша и връщаме NULL
                if($data->dHash != $dHash) {
                    $Cache->deleteData($key);
                    
                    Debug::log("Cache::get $type, $handler - other models are changed, no success");
                    
                    return FALSE;
                }
            }
            
            // Увеличаваме времето на валидността на данните ????
            
            Debug::log("Cache::get $type, $handler - success");
            
            return $data->value;
        }
        
        Debug::log("Cache::get $type, $handler - no exists");
        
        return FALSE;
    }
    
    
    /**
     * Записва обект в кеша
     */
    static function set($type, $handler, $value, $keepMinutes = 1, $depends = array())
    {
        $Cache = cls::get('core_Cache');
        
        Debug::log("Cache::set $type, $handler");
        
        if (!$handler) {
            $handler = md5(json_encode($value));
        }
        
        $key = $Cache->getKey($type, $handler);
        
        $data = new stdClass();
        
        $data->value = $value;
        $data->dHash = $Cache->getDependsHash($depends);
        
        expect(is_numeric($keepMinutes));
        
        $Cache->setData($key, $data, $keepMinutes);
        
        return $handler;
    }
    
    
    /**
     * Изтрива всички обекти от указания тип
     */
    static function removeByType($type)
    {
    	$Cache = cls::get('core_Cache');
    	$handler = NULL;
    	$key = $Cache->getKey($type, $handler);
        $query = self::getQuery();
        while($rec = $query->fetch(array("#key LIKE '%[#1#]'", "{$key}"))) {
            $Cache->deleteData($rec->key);
        }
    }
    
    
    /**
     * Изтрива обектите от указания тип(ове) (и манипулатор)
     */
    static function remove($type, $handler = NULL)
    {
        $Cache = cls::get('core_Cache');
        
        if ($handler === NULL) {
            
            $type = arr::make($type);
            
            foreach ($type as $t) {
                $key = $Cache->getKey($t, $handler);
                $query = self::getQuery();
                while($rec = $query->fetch(array("#key LIKE '[#1#]'", "{$key}"))) {
                    $Cache->deleteData($rec->key);
                }
            }
        } else {
            $key = $Cache->getKey($type, $handler);
            $Cache->deleteData($key);
        }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        $data->toolbar->addBtn('Изтриване на изтеклите записи', array(
                $mvc,
                'DeleteExpiredData',
                'ret_url' => TRUE
            ));
        
        $data->toolbar->addBtn('Изтриване на всички записи', array(
                $mvc,
                'DeleteExpiredData',
                'all' => TRUE,
                'ret_url' => TRUE
            ));
        
        $data->toolbar->removeBtn('btnAdd');
        
        return $data;
    }
    
    
    /**
     * 'Ръчно' почистване на кеша
     */
    function act_DeleteExpiredData()
    {
        requireRole('admin');
        
        return new Redirect(array('core_Cache'), $this->cron_DeleteExpiredData(Request::get('all')));
    }


    /**
     * След изтриване на записи на модела
     *
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param core_Query $query
     */
    static function on_AfterDelete($mvc, &$res, $query)
    {
        foreach ($query->getDeletedRecs() as $rec) {
            $mvc->deleteData($rec->key);
        }
    }

    
    
    /**
     * Почистване на обектите с изтекъл срок
     */
    function cron_DeleteExpiredData($all = FALSE)
    {
        $query = $this->getQuery();
        
        if($all) {
            $query->where('1 = 1');
        } else {
            $query->where("#lifetime < " . time());
        }
        
        $deletedRecs = 0;
        
        while ($rec = $query->fetch()) {
            $deletedRecs += $this->deleteData($rec->key);
        }
        
        if($all) {
            $msg = "Лог: Всички <b style='color:blue;'>{$deletedRecs}</b> кеширани записа бяха изтрити";
        } else {
            $msg = "Лог: <b style='color:blue;'>{$deletedRecs}</b> записа с изтекъл срок бяха изтрити";
        }
        
        return $msg;
    }
    
    
    /**
     * Инсталация на MVC манипулатора
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        // Почистване на всичкия изтекъл Кеш
        $res .= $mvc->cron_DeleteExpiredData(TRUE);
    }
    
    
    /**
     * Подреждане - най-отгоре са последните записи
     */
    static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy('#createdOn', 'DESC');
    }
    
    
    /**
     * Подготвя ключовете
     */
    function getKey(&$type, &$handler)
    {
        $handler = str::convertToFixedKey($handler, EF_CACHE_HANDLER_SIZE, 12);
        $type = str::convertToFixedKey($type, EF_CACHE_TYPE_SIZE, 8);
        
        $prefix = md5(EF_DB_NAME . '|' . CORE_CACHE_PREFIX_SALT);
        $prefix = substr($prefix, 0, 6);
        
        $key = "{$prefix}|{$handler}|{$type}";
        
        return $key;
    }
    
    
    /**
     * Подготвя хеш, който съответства на моментите на последното обновяване
     * на посочените в аргумента модели
     */
    function getDependsHash($depends)
    {
        $depends = arr::make($depends);
        
        if(count($depends)) {
            foreach($depends as $id => $cls) {
                if(is_object($cls) || !strpos($cls, '::')) {
                    $obj[$id] = cls::get($cls);
                    $hash .= $obj[$id]->getDbTableUpdateTime();
                } else {
                    $hash .= call_user_method($cls);
                }
            }
            
            $hash = md5($hash);
        }
        
        return $hash;
    }
    
    
    /**
     * Връща съдържанието записано на дадения ключ
     */
    function getData($key, $keepMinutes = NULL)
    {   
        if (function_exists('apc_fetch')) {
            $res = apc_fetch($key);
        } elseif (function_exists('xcache_get')) {
            $res = xcache_get($key);
            if($res) {
                $res = unserialize($res);
            }
        }

        if($res) {

            return $res;
        }
 
        if($rec = $this->fetch(array("#key = '[#1#]' AND #lifetime >= " . time(), $key))) {

            if($keepMinutes) {
                $rec->lifetime = time() + $keepMinutes * 60;
                $this->save($rec,  'lifetime');
            }
            
            $this->idByKey[$key] = $rec->id;
            
            $data = $rec->data;
            
            if (ord($rec->data{0}) == 120 && ord($rec->data{1}) == 156) {
                $data = gzuncompress($data);
            }
            
            $data = unserialize($data);
            
            return $data;
        }
    }
    
    
    /**
     * Изтрива съдържанието на дадения ключ
     */
    function deleteData($key)
    {
        if (function_exists('apc_delete')) {
            apc_delete($key);
        } elseif (function_exists('xcache_unset')) {
            xcache_unset($key);
        }

        return $this->delete(array("#key LIKE '[#1#]'", $key));
    }
    
    
    /**
     * Задава съдържанието на посочения ключ
     */
    function setData($key, $data, $keepMinutes)
    {   
        $saved = FALSE;
        $keepSeconds = $keepMinutes * 60;

        if (function_exists('apc_store')) {
            apc_store($key, $data, $keepSeconds);
            $saved = TRUE;
        } elseif (function_exists('xcache_set')) {
            xcache_set($key, serialize($data), $keepSeconds);
            $saved = TRUE;
        }

        $rec = new stdClass();
        
        
        // Задаваме ключа
        $rec->key = $key;
        
        if(!$saved) {

            // Сериализираме обекта
            $rec->data = serialize($data);
            
            // Ако е необходимо, компресираме данните
            if (strlen($rec->data) > EF_CACHE_MAX_UNCOMPRESS) {
                $rec->data = gzcompress($rec->data);
            }
        }
        
        // Задаваме крайното време за живот на данните
        $rec->lifetime = time() + $keepSeconds;
        
        $this->save($rec, NULL, 'REPLACE');
    }
}
