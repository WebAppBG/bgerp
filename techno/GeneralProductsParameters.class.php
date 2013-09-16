<?php

/**
 * Клас 'techno_GeneralProductsParameters'
 *
 *
 * @category  bgerp
 * @package   techno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */

class techno_GeneralProductsParameters extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Параметри на универсални продукти';
    
    
    /**
     * Единично заглавие
     */
    var $singleTitle = 'Параметър на универсален продукт';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'techno_Wrapper,plg_RowTools';
    
    
    /**
     * Поле за показване лентата с инструменти
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Кой може да вижда списъчния изглед
     */
    var $canList = 'no_one';
    
    
    /**
     * Кой да е активния таб
     */
    var $currentTab = "Универсални продукти";
    
    
    /**
	 * Мастър ключ към универсалния продукт
	 */
	var $masterKey = 'generalProductId';
	
	
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('generalProductId', 'key(mvc=techno_GeneralProducts)', 'caption=Продукт,input=hidden,silent');
        $this->FLD('paramId', 'key(mvc=cat_Params,select=name,allowEmpty)', 'input,caption=Параметър,mandatory,silent');
        $this->FLD('value', 'varchar(255)', 'caption=Стойност, mandatory');
        
        $this->setDbUnique('generalProductId,paramId');
    }
    
    
    /**
     * Извиква се след подготовка на формата
     */
    static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
    	$form = &$data->form;
        
    	if(!$form->rec->id){
    		$form->addAttr('paramId', array('onchange' => "addCmdRefresh(this.form); document.forms['{$form->formAttr['id']}'].elements['value'].value ='';this.form.submit();"));
	    	expect($productId = $form->rec->generalProductId);
			$options = static::getRemainingOptions($productId);
			expect(count($options));
	        
	        if(!$data->form->rec->id){
	        	$options = array('' => '') + $options;
	        }
	        $form->setOptions('paramId', $options);
    	} else {
    		$form->setReadOnly('paramId');
    	}
    	
        if($form->rec->paramId){
        	$form->fields['value']->type = cat_Params::getParamTypeClass($form->rec->paramId, 'cat_Params');
        } else {
        	$form->setField('value', 'input=hidden');
        }
    }
    
    
    /**
     * Помощен метод за показване само на тези компоненти, които
     * не са добавени към спецификацията
     */
    public static function getRemainingOptions($generalProductId)
    {
    	$params = cat_Params::makeArray4Select();
    	$query = static::getQuery();
        $query->where("#generalProductId = {$generalProductId}");
    	
        if(count($params)) {
        	while($rec = $query->fetch()) {
               unset($params[$rec->paramId]);
            }
        }
        
        return $params;
    }
    
    
    /**
     * Подготвя данните за екстеншъна с параметрите на продукта
     */
    function prepareParams($data, $short = FALSE)
    {
        $productId = ($data->masterData->rec->id) ? $data->masterData->rec->id : $data->id;
    	$query = $this->getQuery();
        $query->where("#generalProductId = {$productId}");
        while($rec = $query->fetch()){
        	$data->params[$rec->id] = $this->recToverbal($rec);
        }
        
        if(!$short){
        	$remaining = static::getRemainingOptions($productId);
	        if(count($remaining) && $this->haveRightFor('add', (object)array('generalProductId' => $productId))){
	        	$data->addParamUrl = array($this, 'add', 'generalProductId' => $data->masterData->rec->id);
	        } 
        }
    }  

    
    /**
     * Подготвя данните за екстеншъна с параметрите на продукта
     */
    function renderParams($data, $short = FALSE)
    {
    	$blockName = ($short) ? "SHORT" : "LONG";
    	$tpl = getTplFromFile('techno/tpl/Parameters.shtml')->getBlock($blockName);
    	if($data->params){
    		foreach ($data->params as $row){
    			$block = clone $tpl->getBlock('PARAMS');
    			$block->placeObject($row);
    			$block->removeBlocks();
    			$block->append2master();
    		}
    	} elseif($short){
    		$tpl = new ET("");
    	}
    	
    	if($data->addParamUrl){
    		$img = sbf('img/16/add.png', '');
    		$tpl->replace(ht::createLink(' ', $data->addParamUrl, NULL, array('style' => "background-image:url({$img});", 'class' => 'spec-add-btn', 'title' => 'Добавяне на нов параметър')), 'ADD');
    	}
    	
    	return $tpl;
    }
    
    
    /**
     * Пренасочва URL за връщане след запис към сингъл изгледа
     */
    function on_AfterPrepareRetUrl($mvc, $res, $data)
    {
    	if($data->form->rec->generalProductId){
    		$data->retUrl = toUrl(array('techno_GeneralProducts', 'single', $data->form->rec->generalProductId));
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
       if ($action == 'add' || $action == 'edit' || $action == 'delete') {
       		if(empty($rec->generalProductId)){
       			$res = 'no_one';
       		} else {
       			$masterState = $mvc->Master->fetchField($rec->generalProductId, 'state');
       			if($masterState != 'draft'){
       				$res = 'no_one';
       			}
       		}
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$paramRec = cat_Params::fetch($rec->paramId);
        if($paramRec->type != 'enum'){
               $Type = cls::get("type_{$paramRec->type}");
               if($paramRec->type == 'double'){
               	   $Type->params['decimals'] = strlen(substr(strrchr($rec->value, "."), 1));
               }
               $row->value = $Type->toVerbal($rec->value);
        }
           
        if($paramRec->type != 'percent'){
            $row->value .=  ' ' . cat_Params::getVerbal($paramRec, 'suffix');
        }
    }
    
    
    /**
     * Връща краткото представяне на продукта
     * @param int $id - ид на универсален продукт
     */
    public function getShortLayout($id)
    {
    	$params = (object)array('id' => $id);
    	$this->prepareParams($params, TRUE);
    	return $this->renderParams($params, TRUE);
    }
}