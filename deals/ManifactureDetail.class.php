<?php


/**
 * Клас 'deals_ManifactureDetail' - базов клас за детайли на производствени документи
 *
 * @category  bgerp
 * @package   mp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class deals_ManifactureDetail extends doc_Detail
{
	
	
	/**
	 * Какви продукти да могат да се избират в детайла
	 * 
	 * @var enum(canManifacture=Производими,canConvert=Вложими)
	 */
	protected $defaultMeta;
	
	
	/**
	 * Описание на модела (таблицата)
	 */
	public function setDetailFields($mvc)
	{
		$mvc->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Продукт,mandatory', 'tdClass=large-field leftCol wrap,silent,removeAndRefreshForm=quantity|measureId|packagingId|packQuantity');
		$mvc->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Мярка','tdClass=small-field,mandatory');
		$mvc->FNC('packQuantity', 'double(Min=0)', 'caption=К-во,input=input,mandatory');
		$mvc->FLD('quantityInPack', 'double(smartRound)', 'input=none,notNull,value=1');
		
		$mvc->FLD('quantity', 'double(Min=0)', 'caption=К-во,input=none');
		$mvc->FLD('measureId', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,input=hidden');
	}
	

	/**
	 * Подготовка на филтър формата
	 */
	protected static function on_AfterPrepareListFilter($mvc, &$data)
	{
		$data->query->orderBy('id', 'ASC');
	}
	
	
	/**
	 * Изчисляване на количеството на реда в брой опаковки
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $rec
	 */
	public static function on_CalcPackQuantity(core_Mvc $mvc, $rec)
	{
		if (empty($rec->quantity) || empty($rec->quantityInPack)) {
			return;
		}
		$rec->packQuantity = $rec->quantity / $rec->quantityInPack;
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$form = &$data->form;
		
		if(!$mvc->defaultMeta) return;
		$products = cat_Products::getByProperty($mvc->defaultMeta);
		 
		expect(count($products));
			
		if (empty($form->rec->id)) {
			$data->form->setOptions('productId', array('' => ' ') + $products);
		} else {
			$data->form->setOptions('productId', array($form->rec->productId => $products[$form->rec->productId]));
		}
	}
	
	
	/**
	 * Извиква се след въвеждането на данните от Request във формата ($form->rec)
	 */
	public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
	{
		$rec = &$form->rec;
		
		if($rec->productId){
			$form->setDefault('measureId', cat_Products::getProductInfo($rec->productId)->productRec->measureId);
			$shortName = cat_UoM::getShortName($rec->measureId);
			$form->setField('quantity', "unit={$shortName}");
			
			$packs = cat_Products::getPacks($rec->productId);
			$form->setOptions('packagingId', $packs);
		} else {
			$form->setReadOnly('packagingId');
		}
		
		if($form->isSubmitted()){
			$productInfo = cat_Products::getProductInfo($rec->productId);
			$rec->quantityInPack = ($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;
			
			if($rec->productId){
				if($rec->productId){
					$rec->measureId = $productInfo->productRec->measureId;
				}
			}
			
			$rec->quantity = $rec->packQuantity * $rec->quantityInPack;
		}
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if(($action == 'edit' || $action == 'delete' || $action == 'add') && isset($rec)){
			if($mvc->Master->fetchField($rec->{$mvc->masterKey}, 'state') != 'draft'){
				$requiredRoles = 'no_one';
			}
		}
	}
	
	
	/**
	 * След подготовка на лист тулбара
	 */
	public static function on_AfterPrepareListToolbar($mvc, &$data)
	{
		if (!empty($data->toolbar->buttons['btnAdd'])) {
				unset($data->toolbar->buttons['btnAdd']);
				$products = cat_Products::getByProperty($mvc->defaultMeta);
	
				if(!count($products)){
					$error = "error=Няма артикули, ";
				}
	
				$data->toolbar->addBtn('Артикул', array($mvc, 'add', $mvc->masterKey => $data->masterId, 'ret_url' => TRUE),
						"id=btnAdd,{$error} order=10,title=Добавяне на артикул", 'ef_icon = img/16/shopping.png');
		}
	}
	
	
	/**
	 * Преди подготвяне на едит формата
	 */
	public static function on_AfterRecToVerbal($mvc, &$row, $rec)
	{
		$row->productId = cat_Products::getShortHyperLink($rec->productId);
		
		// Показваме подробната информация за опаковката при нужда
		deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
	}
}
 	