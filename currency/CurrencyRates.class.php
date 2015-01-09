<?php



/**
 * Валутни курсове
 *
 *
 * @category  bgerp
 * @package   currency
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class currency_CurrencyRates extends core_Detail
{
	/**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'currencyId';
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, Currencies=currency_Currencies, currency_Wrapper, plg_Sorting, plg_Chart';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "currencyId, date, rate, baseCurrencyId";
    
    
    /**
     * Заглавие
     */
    var $title = 'Исторически валутни курсове';
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Валутен курс";

    
    /**
     * Брой записи на страница
     */
    var $listItemsPerPage = 20;
    

    /**
     * Кой може да зарежда валутните курсове ръчно от ЕЦБ?
     */
    var $canRetrieve = 'currency,ceo';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'ceo,admin,cash,bank,currency';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,currency';
    
    
    /**
     * Работен кеш за вече изчислени валутни курсове
     *  
     * @var array
     */
    protected static $cache = array();
    
    
    /**
     * Код на междинна валута за косвено изчисляване изчисляване на курсове.
     * 
     * Когато курсът на една валута (X) към друга (Y) не е изрично записан в БД, той може да бъде 
     * изчислен чрез преминаване през трета валута, при условие че в БД има записани курсовете
     * както на X така и на Y към тази трета валута. В тази променлива е посочен кода на 
     * междинната валута
     * 
     * @todo Дали не е добре това да премине в конфигурацията?
     * @var string Трибуквен ISO код на валута
     */
    public static $crossCurrencyCode = 'EUR';
        
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута,chart=diff');
        $this->FLD('baseCurrencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Курс->База,width=6em');
        $this->FLD('date', 'date', 'caption=Курс->Дата,chart=ax');
        $this->FLD('rate', 'double', 'caption=Курс->Стойност,chart=ay');
        
        $this->setDbUnique('currencyId,baseCurrencyId,date');
    }
    
    
    /**
     * Зареждане на валути от xml файл от ECB
     *
     * @return string
     */
    function retrieveCurrenciesFromEcb()
    {
        $euroId = $this->Currencies->fetchField("#code='EUR'", 'id');
        
        $this->data = new stdClass();

        $this->data->rates = array();
        $XML = simplexml_load_file("http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml");
        $now = $XML->Cube->Cube['time']->__toString();
        
        $countCurrencies = 0;
        
        foreach($XML->Cube->Cube->Cube as $item){
            $rate = $item['rate']->__toString();
            $currency = $item['currency']->__toString();
            $currencyId = $this->Currencies->fetchField(array("#code='[#1#]'", $currency), 'id');
            
            if(!$currencyId) continue;
            
            $state = $this->Currencies->fetchField($currencyId, "state");
            
            if ($state == "closed") continue;
            
            // Проверка дали имаме такъв запис за текуща дата 
            if ($this->fetch("#currencyId={$currencyId} AND #baseCurrencyId={$euroId} AND #date='{$now}'")) {
                continue;
            }
            $rec = new stdClass();
            $rec->currencyId = $currencyId;
            $rec->baseCurrencyId = $euroId;
            $rec->date = $now;
            $rec->rate = $rate;
            
            $currenciesRec = new stdClass();
            $currenciesRec->id = $rec->currencyId;
            $currenciesRec->lastUpdate = $rec->date;
            $currenciesRec->lastRate = $rec->rate;
            
            $this->Currencies->save($currenciesRec, 'lastUpdate,lastRate');
            
            $this->save($rec);
            
            $countCurrencies++;
        }
        
        if($countCurrencies == '0') {
            $res = "Няма нови курсове за валути.";
        } else {
            $res = "Извлечени са курсове за {$countCurrencies} валути.";
        }
        
        return $res;
    }
    
    
    /**
     * Метод за Cron за зареждане на валутите
     */
    function cron_RetrieveCurrencies()
    {
        return $this->retrieveCurrenciesFromEcb();
    }
    
    
    /**
     * Action за тестване зареждането на валутите в debug mode.
     * В production mode този метод не се използва.
     */
    function act_RetrieveCurrencies()
    {   
        $this->requireRightFor('retrieve');

        return new Redirect (array('currency_CurrencyRates', 'default'), $this->retrieveCurrenciesFromEcb());
    }
    
    
    /**
     * Зареждане на Cron задачите за валутите след setup на класа
     *
     * @param core_MVC $mvc
     * @param string $res
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        $rec = new stdClass();
        $rec->systemId = "update_currencies_afternoon";
        $rec->description = "Зареждане на валутните курсове";
        $rec->controller = "currency_CurrencyRates";
        $rec->action = "RetrieveCurrencies";
        $rec->period = 24 * 60;
        $rec->offset = 17 * 60;
        $res .= core_Cron::addOnce($rec);
        
        unset($rec->id);
        $rec->systemId = "update_currencies_night";
        $rec->offset = 21 * 60;
        $res .= core_Cron::addOnce($rec);
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, $data)
    {
        if($mvc->haveRightFor('retrieve')) {
            $data->toolbar->addBtn('Зареди от ECB', array($mvc, 'RetrieveCurrencies'), NULL, 'title=Зареждане от Европейската Централна Банка');
        }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy("date", 'DESC');;
    }
   
    
    /**
     *  Обръща сума от една валута в друга към дата
     *  
     *  @param double $amount Сума която ще обърнем
     *  @param date $date NULL = текущата дата
     *  @param string $from Код на валутата от която ще обръщаме
     *                      NULL = базова валута към $date
     *  @param string $to Код на валутата към която ще обръщаме
     *                    NULL = базова валута към $date
     *  @return double $amount Конвертираната стойност на сумата
     */
    public static function convertAmount($amount, $date, $from, $to = NULL)
    {
        return $amount * static::getRate($date, $from, $to);
    }

    
    /**
     *  Обменният курс на една валута спрямо друга към дата
     *  
     *  Закръгля резултата до 4-тата цифра след дес. точка
     *
     *  @param double $amount Сума която ще обърнем
     *  @param date $date NULL = текущата дата
     *  @param string $from Код на валутата от която ще обръщаме
     *                      NULL = базова валута към $date
     *  @param string $to Код на валутата към която ще обръщаме
     *                    NULL = базова валута към $date
     *  @return double $amount Конвертираната стойност на сумата
     */
    public static function getRate($date, $from, $to)
    {
    	if ($from == $to) {
    	    // Ако подадените валути са еднакви, то обменния им курс е 1
    	    return 1;
    	}
    	
    	// Незададен (NULL) код на валута означава базова валута, зададен - обръщаме го към id
    	$fromId = is_null($from) ? acc_Periods::getBaseCurrencyId($date) : currency_Currencies::getIdByCode($from);
    	$toId   = is_null($to)   ? acc_Periods::getBaseCurrencyId($date) : currency_Currencies::getIdByCode($to);
    	
    	if (!isset($date)) {
    	    $date = dt::verbal2mysql();
    	}
    	
    	expect($fromId, "{$from}: Няма такава валута");
    	expect($toId,   "{$to}: Няма такава валута");
    	    	                            
        if ($fromId == $toId) {
    	    // Ако подадените валути са еднакви, то обменния им курс е 1
            return 1;
        }
        
        if (!is_null($rate = static::getDirectRate($date, $fromId, $toId))) {
            return round($rate, 4);
        }
        
        $crossCurrencyId = currency_Currencies::getIdByCode(static::$crossCurrencyCode);

        if ($crossCurrencyId != $fromId && $crossCurrencyId != $toId) {
            if (!is_null($rate = static::getCrossRate($date, $fromId, $toId, $crossCurrencyId))) {
                return round($rate, 4);
            }
        }
        
        $from = currency_Currencies::getCodeById($fromId);
        $to   = currency_Currencies::getCodeById($toId);

        expect(FALSE, "Не може да се определи валутен курс {$from}->{$to}");
    }
    

    /**
     * Връща директния курс на една валута към друга, без преизчисляване през трета валута
     *
     * @param string $date
     * @param int $fromId
     * @param int $toId
     */
    protected static function getDirectRate($date, $fromId, $toId)
    {
        $rate = static::getStoredRate($date, $fromId, $toId);
    
        if (is_null($rate)) {
            if (!is_null($rate = static::getStoredRate($date, $toId, $fromId))) {
                $rate = 1 / $rate;
            }
        }
    
        return $rate;
    }
    
    
    /**
     * Връща записан в БД обменен курс на една валута спрямо друга
     * 
     * getStoredRate(X, Y) = колко Y струва 1 X
     * 
     * @param string $date
     * @param int $fromId key(mvc=currency_Currencies)
     * @param int $toId key(mvc=currency_Currencies)
     * @return float 
     */
    protected static function getStoredRate($date, $fromId, $toId)
    {
        if (!isset(static::$cache[$date][$fromId][$toId])) {
            /* @var $query core_Query */
            $query = static::getQuery();
            
            $query->where("#date <= '{$date}'");
            $query->where("#baseCurrencyId = {$fromId}");
            $query->where("#currencyId = {$toId}");
            $query->orderBy('date', 'DESC');
            $query->limit(1);
            
            if ($rec = $query->fetch()) {
                static::$cache[$date][$rec->baseCurrencyId][$rec->currencyId] = $rec->rate;
            }
        }
    
        if (isset(static::$cache[$date][$fromId][$toId])) {
            return static::$cache[$date][$fromId][$toId];
        }
        
        return NULL;
    }
    
    
    /**
     * Изчисляване на курс чрез преминаване през междинна валута
     * 
     * @param string $date
     * @param int $fromId
     * @param int $toId
     * @param int $baseCurrencyId
     * @return float
     */
    protected static function getCrossRate($date, $fromId, $toId, $baseCurrencyId)
    {
        if (is_null($fromBaseRate = static::getDirectRate($date, $fromId, $baseCurrencyId))) {
            return NULL;
        }
        
        if (is_null($toBaseRate = static::getDirectRate($date, $toId, $baseCurrencyId))) {
            return NULL;
        }
        
        return static::$cache[$date][$fromId][$toId] = $fromBaseRate / $toBaseRate;
    }
    
    
    /**
     * Модификации по ролите
     */
    static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action== 'add' && !isset($rec->currencyId)) {
			
			// Предпазване от добавяне на нов постинг в act_List
			$res = 'no_one';
		}
    }
    
    
    /**
     * Функция изчисляваща дали има отклонение между подадена сума
     * и курса от системата между две валути.
     * Приемливото отклонение е дефинирано в , дефолт 5%
     * @param double $givenRate - подаден курс.
     * @param string $from - код от коя валута
     * @param string $то - код към коя валута
     * @return mixed FALSE - ако няма отколонение
     * 				 $msg  - 'предупреждението за съответствие'
     */
    public static function hasDeviation($givenRate, $date, $from, $to)
    {
    	expect($givenRate);
    	$conf = core_Packs::getConfig('currency');
    	$percent = $conf->EXCHANGE_DEVIATION * 100;
    	$knownRate = static::getRate($date, $from, $to);
    	
    	$difference = round(abs($givenRate - $knownRate) / min($givenRate, $knownRate) * 100);
    	if($difference > $percent) {
		    return "Въведения курс е много различен от очаквания '{$knownRate}'";
		}
		 
		return FALSE;
    }
}
