<?php



/**
 * Клас 'cond_DeliveryTerms' - Условия на доставка
 *
 * Набор от стандартните условия на доставка (FOB, DAP, ...)
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cond_Countries extends core_Manager
{
	
	
	/**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,cond';
	
	
	/**
	 * Кой може да пише
	 */
	public $canWrite = 'ceo,cond';
	
	
	/**
	 * Плъгини за зареждане
	 */
	public $loadList = 'plg_RowTools2,cond_Wrapper,plg_Created';
	
	
	/**
	 * Полета, които ще се показват в листов изглед
	 */
	public $listFields = 'country, conditionId, value, createdOn,createdBy';
	
	
	/**
	 * Кой има право да променя системните данни?
	 */
	public $canEditsysdata = 'no_one';
	
	
	/**
	 * Заглавие
	 */
	public $title = 'Търговски условия по държави';
	
	
	/**
	 * Заглавие на единичния обект
	 */
	public $singleTitle = 'търговско условие за държава';
	

    /**
     * Кой може да променя състоянието на валутата
     */
    public $canChangestate = 'cond,ceo';


	/**
	 * Описание на модела (таблицата)
	 */
	function description()
	{
		$this->FLD('country', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Държава,removeAndRefreshForm=conditionId,silent,placeholder=Всички държави');
		$this->FLD('conditionId', 'key(mvc=cond_Parameters,select=name,allowEmpty)', 'input,caption=Условие,mandatory,silent,removeAndRefreshForm=value');
		$this->FLD('value', 'varchar(255)', 'caption=Стойност, mandatory');
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $data
	 */
	protected static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$form = &$data->form;
		$rec = $form->rec;
		
		if($form->cmd != 'refresh' && empty($rec->id)){
			$myCompany = crm_Companies::fetchOurCompany();
			$form->setDefault('country', $myCompany->country);
		}
		
		if($rec->conditionId){
			if($Type = cond_Parameters::getTypeInstance($rec->conditionId, 'drdata_Countries', $rec->country, $rec->value)){
				$form->setField('value', 'input');
				$form->setFieldType('value', $Type);
			}else {
				$form->setError('conditionId', 'Има проблем при зареждането на типа');
			}
		}
	}
	
	
	/**
	 * Проверка след изпращането на формата
	 */
	protected static function on_AfterInputEditForm($mvc, $form)
	{
		$rec = &$form->rec;
		if ($form->isSubmitted()){
			if(empty($rec->country)){
				$rec->country = NULL;
			}
		}
	}
	
	
	/**
	 * Проверява дали посочения запис не влиза в конфликт с някой уникален
	 * @param: $rec stdClass записа, който ще се проверява
	 * @param: $fields array|string полетата, които не уникални.
	 * @return: bool
	 */
	public function isUnique($rec, &$fields = array(), &$exRec = NULL)
	{
		$where = "#id != '{$rec->id}' AND #conditionId = {$rec->conditionId}";
		$where .= (!empty($rec->country)) ? " AND #country = {$rec->country}" : " AND (#country IS NULL OR #country = 0 OR #country = '')";
	
		$res = $this->fetch($where);
		if($res){
			$exRec = $res;
			$fields = array('country', 'conditionId');
			return FALSE;
		}
		
		return TRUE;
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид
	 */
	protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		$paramRec = cond_Parameters::fetch($rec->conditionId);
		$row->conditionId = cond_Parameters::getVerbal($paramRec, 'typeExt');
		
		if($ParamType = cond_Parameters::getTypeInstance($paramRec, 'drdata_Countries', $rec->country, $rec->value)){
			$row->value = $ParamType->toVerbal(trim($rec->value));
		}
		
		$row->ROW_ATTR['class'] .= " state-active";
		
		if(cond_Parameters::haveRightFor('single', $rec->conditionId)){
			$row->conditionId = ht::createLink($row->conditionId, array('cond_Parameters', 'single', $rec->conditionId));
		}
		
		if(empty($rec->country)){
			$row->country = "<span class='quiet'>" . tr('Всички държави') . "</span>";
		}
	}
	
	
	/**
	 * Извиква се след SetUp-а на таблицата за модела
	 */
	function loadSetupData()
	{
		if(!cond_Parameters::count()){
			cls::get('cond_Parameters')->loadSetupData();
		}
		
		$file = "cond/csv/Countries.csv";
		$fields = array(
				0 => "csv_country",
				1 => "paramSysId",
				2 => "value",
		);
	
		$cntObj = csv_Lib::importOnce($this, $file, $fields);
		$res = $cntObj->html;
		 
		return $res;
	}
	
	
	/**
	 * Изпълнява се преди импортирването на данните
	 */
	protected static function on_BeforeImportRec($mvc, &$rec)
	{
		if(isset($rec->paramSysId)){
			expect($rec->conditionId = cond_Parameters::fetchIdBySysId($rec->paramSysId));
		}
		
		$rec->country = (!empty($rec->csv_country)) ? drdata_Countries::getIdByName($rec->csv_country) : NULL;
	}
}