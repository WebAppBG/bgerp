<?php

/**
 * Клас 'cat_products_Params' - продуктови параметри
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */

class cat_products_SharedInFolders extends core_Manager
{
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'productId';
    
    
    /**
     * Заглавие
     */
    public $title = 'Споделени папки';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Споделяне';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт,folderId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'cat_Wrapper, plg_RowTools, plg_SaveAndNew';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    public $lastUsedKeys = 'paramId';
    
    
    /**
     * Поле за пулт-а
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Активния таб в случай, че wrapper-а е таб контрол.
     */
    public $tabName = 'cat_Products';
    
    
    /**
     * Кой може да добавя
     */
    public $canAdd = 'ceo,cat';
    
    
    /**
     * Кой може да редактира
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой може да листва
     */
    public $canList = 'no_one';
    
    
    /**
     * Кой може да изтрива
     */
    public $canDelete = 'ceo,cat';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('productId', 'key(mvc=cat_Products)', 'caption=Артикул,mandatory,silent,input=hidden');
    	$this->FLD('folderId', 'key(mvc=doc_Folders,select=title,allowEmpty)', 'caption=Сподели в,mandatory');
    
    	$this->setDbUnique('productId,folderId');
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
    	
    	$query = self::getQuery();
    	$query->where("#productId = {$form->rec->productId}");
    	$masterRec = cat_Products::fetch($form->rec->productId);
    	
    	$ignore = array($masterRec->folderId => $masterRec->folderId);
    	while($dRec = $query->fetch()){
    		$ignore[$dRec->folderId] = $dRec->folderId;
    	}
    	
    	$folderOptions = doc_Folders::getOptionsByCoverInterface('crm_ContragentAccRegIntf', $ignore);
    	$form->setOptions('folderId', $folderOptions);
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    public static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
    	$title = cat_Products::getHyperlink($data->form->rec->productId, TRUE);
    	$data->form->title = "Показване на|* <b>{$title}</b> |в папка на контрагент|*";
    }
    
    
   /**
    * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
    */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'add' || $action == 'delete') && isset($rec)){
    		$productRec = cat_Products::fetch($rec->productId);
    		if($productRec->isPublic == 'yes' && $action != 'delete'){
    			$requiredRoles = 'no_one';
    		} elseif($productRec->state == 'rejected') {
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Подготовка на детайла
     */
    public function prepareShared($data)
    {
    	$masterRec = $data->masterData->rec;
    	if($masterRec->isPublic == 'yes' && !self::fetch("#productId = {$masterRec->id}")){
    		$data->hide = TRUE;
    		return;
    	}
    	
    	$data->TabCaption = 'Достъпност';
    	$data->Tab = 'top';
    	
    	$data->recs = $data->rows = array();
    	$data->recs[0] = (object)array('folderId' => $masterRec->folderId, 'productId' => $masterRec->id); 
    	$query = self::getQuery();
    	$query->where("#productId = {$masterRec->id}");
    	while($rec = $query->fetch()){
    		$data->recs[$rec->id] = $rec;
    	}
    	
    	foreach ($data->recs as $id => $rec){
    		$row = static::recToVerbal($rec);
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    		$data->rows[$id] = $row;
    	}
    	
    	unset($data->rows[0]->tools);
    	
    	if($this->haveRightFor('add', (object)array('productId' => $masterRec->id))){
    		$data->addUrl = array($this, 'add', 'productId' => $masterRec->id, 'ret_url' => TRUE);
    	}
    }
    
    
    /**
     * Рендиране на детайла
     * 
     * @param stdClass $data
     * @return core_ET $tpl
     */
    public function renderShared($data)
    {
    	if($data->hide == TRUE) return;
    	
    	$tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
    	$tpl->append(tr('Папки, в които артикулът е достъпен'), 'title');
    	
    	if(isset($data->addUrl)){
    		$ht = ht::createLink('', $data->addUrl, FALSE, 'ef_icon=img/16/add.png,title=Добавяне папки на контрагенти');
    		$tpl->append($ht, 'title');
    	}
    	
    	if($data->masterData->rec->isPublic == 'yes'){
			$tpl->append("<div><b>" . tr('Артикулът е стандартен и е достъпен във всички папки.') . "</b></div>", 'content');
			$tpl->append("<div><i><small>" . tr('Като частен е бил споделен в папките на:') . "</small></i></div>", 'content');
    	}
    	
    	if(is_array($data->rows)){
    		foreach ($data->rows as $row){
    			$dTpl = new core_ET("<div>[#folderId#] <span class='custom-rowtools'>[#tools#]</span></div>");
    			$dTpl->placeObject($row);
    			$dTpl->removeBlocks();
    		
    			$tpl->append($dTpl, 'content');
    		}
    	}
    	
    	return $tpl;
    }
    
    
    /**
     * Кои са споделените артикули към дадена папка
     * 
     * @param int $folderId - ид на папка
     * @return array $res - масив със споделените артикули
     */
    public static function getSharedProducts($folderId)
    {
    	$res = array();
    	
    	expect($folderId);
    	$query = self::getQuery();
    	$query->where("#folderId = {$folderId}");
    	while($rec = $query->fetch()){
    		$res[$rec->productId] = $rec->productId;
    	}
    	
    	return $res;
    }
}