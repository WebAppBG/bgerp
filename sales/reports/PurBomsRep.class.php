<?php



/**
 * Мениджър на отчети от Задание за производство
 *
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Продажби » Договори, чакащи за задание
 */
class sales_reports_PurBomsRep extends frame2_driver_Proto
{                  
	
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'cat,ceo,sales,purchase';
    
    
    /**
     * Нормализираните имена на папките
     *
     * @var array
     */
    private static $folderNames = array();
    
    
    /**
     * Имената на контрагентите
     *
     * @var array
     */
    private static $contragentNames = array();
    
    
    /**
     * Дилърите
     *
     * @var array
     */
    private static $dealers = array();
    
    
    /**
     * Брой записи на страница
     *
     * @var int
     */
    private $listItemsPerPage = 50;
    
    
    /**
     * Връща заглавието на отчета
     *
     * @param stdClass $rec - запис
     * @return string|NULL  - заглавието или NULL, ако няма
     */
    public function getTitle($rec)
    {
        return 'Продажби » Договори, чакащи за задание';
    }
    
    
    /**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
	    $fieldset->FLD('dealers', 'keylist(mvc=core_Users,select=nick)', 'caption=Търговци,after=title,single=none');
	}
      

    /**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param frame2_driver_Proto $Driver $Driver
	 * @param embed_Manager $Embedder
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
	{
	    $form = &$data->form;
		
		// Всички активни потебители
		$uQuery = core_Users::getQuery();
		$uQuery->where("#state = 'active'");
		$uQuery->orderBy("#names", 'ASC');
		$uQuery->show('id');
		
		// Които са търговци
		$roles = core_Roles::getRolesAsKeylist('ceo,sales');
		$uQuery->likeKeylist('roles', $roles);
		$allDealers = arr::extractValuesFromArray($uQuery->fetchAll(), 'id');
		
		// Към тях се добавят и вече избраните търговци
		if(isset($form->rec->dealers)){
			$dealers = keylist::toArray($form->rec->dealers);
			$allDealers = array_merge($allDealers, $dealers);
		}
		
		// Вербализират се
		$suggestions = array();
		foreach ($allDealers as $dealerId){
			$suggestions[$dealerId] = core_Users::fetchField($dealerId, 'nick');
		}
		
		// Задават се като предложение
		$form->setSuggestions('dealers', $suggestions);
		
		// Ако текущия потребител е търговец добавя се като избран по дефолт
		if(haveRole('sales') && empty($form->rec->id)){
			$form->setDefault('dealers', keylist::addKey('', core_Users::getCurrent()));
		}
	}
    
	
	/**
	 * Подготвя данните на справката от нулата, които се записват в модела
	 *
	 * @param stdClass $rec        - запис на справката
	 * @return stdClass|NULL $data - подготвените данни
	 */
	public function prepareData($rec)
	{
	     
	}
	
	
	/**
	 * Рендиране на данните на справката
	 *
	 * @param stdClass $rec - запис на справката
	 * @return core_ET      - рендирания шаблон
	 */
	public function renderData($rec)
	{
	    $tpl = new core_ET("[#PAGER_TOP#][#TABLE#][#PAGER_BOTTOM#]");
	    
	    $data = new stdClass();
	    //$data = $rec->data; 
	    $data->listFields = $this->getListFields($rec);
	    $data->rows = array();
	    
	    // Подготовка на пейджъра
	    if(!Mode::isReadOnly()){
	        $data->Pager = cls::get('core_Pager',  array('itemsPerPage' => $this->listItemsPerPage));
	        $data->Pager->setPageVar('frame2_Reports', $rec->id);
	        $data->Pager->itemsCount = count($data->recs);
	    }
	    
	    // Вербализиране само на нужните записи
	    if(is_array($data->recs)){
	        foreach ($data->recs as $index => $dRec){
	            if(isset($data->Pager) && !$data->Pager->isOnPage()) continue;
	            $data->rows[$index] = $this->detailRecToVerbal($dRec);
	        }
	    }
	    
	    // Рендиране на пейджъра
	    if(isset($data->Pager)){
	        $tpl->append($data->Pager->getHtml(), 'PAGER_TOP');
	        $tpl->append($data->Pager->getHtml(), 'PAGER_BOTTOM');
	    }
	    
	    // Рендиране на лист таблицата
	    $fld = cls::get('core_FieldSet');
	    $fld->FLD('dealerId', 'varchar', 'smartCenter');

	    $table = cls::get('core_TableView', array('mvc' => $fld));
	    $tpl->append($table->get($data->rows, $data->listFields), 'TABLE');
	    $tpl->removeBlocks();
	    $tpl->removePlaces();
	    
	    // Връщане на шаблона
	    return $tpl;
	}
	
    
    /**
	 * Вербализиране на данните
	 * 
	 * @param stdClass $dRec - запис от детайла
	 * @return stdClass $row - вербалния запис
	 */
	private function detailRecToVerbal(&$dRec)
	{
		$isPlain = Mode::is('text', 'plain');
		$row = new stdClass();

		// Линк към дилъра
		if(!array_key_exists($dRec->dealerId, self::$dealers)){
			self::$dealers[$dRec->dealerId] = crm_Profiles::createLink($dRec->dealerId);
		}
		
		$row->dealerId = self::$dealers[$dRec->dealerId];
		if($isPlain){
			$row->dealerId = strip_tags(($row->dealerId instanceof core_ET) ? $row->dealerId->getContent() : $row->dealerId);
		}

		$row->deliveryTime = ($isPlain) ? frame_CsvLib::toCsvFormatData($dRec->deliveryTime) : dt::mysql2verbal($dRec->deliveryTime);
		
		return $row;
	}

    
    /**
     * Връща списъчните полета
     *
     * @param stdClass $rec  - запис
     * @return array $fields - полета
     */
    private function getListFields($rec)
    {
        // Кои полета ще се показват
        $fields = array('num'   => '№',
                        'pur' => 'Договор->№',
                        'purDate'     => 'Договор->Дата',
                        'dealerId'     => 'Търговец',
                        'article'    => 'Артикул',
                        'quantity' => 'Количество',
                        'deliveryTime' => 'Дата за доставка'
                        );

        return $fields;
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager $Embedder
     * @param core_ET $tpl
     * @param stdClass $data
     */
    public static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
    {
   
        $dealers = keylist::toArray($rec->dealers);
        foreach ($dealers as $userId => &$nick) {
            $nick = crm_Profiles::createLink($userId)->getContent();
        }
    
        $row->dealers = implode(', ', $dealers);
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager $Embedder
     * @param core_ET $tpl
     * @param stdClass $data
     */
    public static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
       /* $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
							    <!--ET_BEGIN place--><small><div><!--ET_BEGIN dealers-->|Търговци|*: [#dealers#]<!--ET_END dealers--></div><!--ET_BEGIN countries--><div>|Държави|*: [#countries#]</div><!--ET_END countries--></small></fieldset><!--ET_END BLOCK-->"));
    
        if(isset($data->rec->dealers)){
            $fieldTpl->append($data->row->dealers, 'dealers');
        }
    
        if(isset($data->rec->countries)){
            $fieldTpl->append($data->row->countries, 'countries');
        }
    
        $tpl->append($fieldTpl, 'DRIVER_FIELDS');*/
    }
    
    
    /**
     * Връща нормализирано име на корицата, за по-лесно сортиране
     *
     * @param int $folderId
     * @return string
     */
    private static function normalizeFolderName($folderId)
    {
        if(!array_key_exists($folderId, self::$folderNames)){
            self::$folderNames[$folderId] = strtolower(str::utf2ascii(doc_Folders::fetchField($folderId, 'title')));
        }
    
        return self::$folderNames[$folderId];
    }
    

    /**
     * Връща редовете на CSV файл-а
     *
     * @param stdClass $rec
     * @return array
     */
    public function getCsvExportRows($rec)
    {
        $dRecs = $rec->data->recs;
        $exportRows = array();
    
        Mode::push('text', 'plain');
        if(is_array($dRecs)){
            foreach ($dRecs as $key => $dRec){
                $exportRows[$key] = $this->detailRecToVerbal($dRec);
            }
        }
        Mode::pop('text');
    
        return $exportRows;
    }
    
    
    /**
     * Връща полетата за експортиране във csv
     *
     * @param stdClass $rec
     * @return array
     */
    public function getCsvExportFieldset($rec)
    {

        $fieldset->FLD('num', 'varchar','caption=№');
        $fieldset->FLD('pur', 'varchar','caption=Договор->№');
        $fieldset->FLD('purDate', 'varchar','caption=Договор->Дата');
        $fieldset->FLD('dealerId', 'varchar','caption=Търговец');
        $fieldset->FLD('article', 'varchar','caption=Артикул');
        $fieldset->FLD('quantity', 'varchar','caption=Количество');
        $fieldset->FLD('deliveryTime', 'varchar','caption=Дата за доставка');
    
        return $fieldset;
    }
    
    
    /**
     * Да се изпраща ли нова нотификация на споделените потребители, при опресняване на отчета
     *
     * @param stdClass $rec
     * @return boolean $res
     */
    public function canSendNotificationOnRefresh($rec)
    {
        // Намира се последните две версии
        $query = frame2_ReportVersions::getQuery();
        $query->where("#reportId = {$rec->id}");
        $query->orderBy('id', 'DESC');
        $query->limit(2);
    
        // Маха се последната
        $all = $query->fetchAll();
        unset($all[key($all)]);
    
        // Ако няма предпоследна, бие се нотификация
        if(!count($all)) return TRUE;
        $oldRec = $all[key($all)]->oldRec;
    
        $dataRecsNew = $rec->data->recs;
        $dataRecsOld = $oldRec->data->recs;
    
        $newContainerIds = $oldContainerIds = array();
        if(is_array($rec->data->recs)){
            $newContainerIds = arr::extractValuesFromArray($rec->data->recs, 'containerId');
        }
    
        if(is_array($oldRec->data->recs)){
            $oldContainerIds = arr::extractValuesFromArray($oldRec->data->recs, 'containerId');
        }
    
        // Ако има нови документи бие се нотификация
        $diff = array_diff_key($newContainerIds, $oldContainerIds);
        $res = (is_array($diff) && count($diff));
    
        return $res;
    }
    
}