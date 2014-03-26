<?php

/**
 * Клас 'cat_products_Params'
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */

class cat_products_Params extends cat_products_Detail
{
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'productId';
    
    
    /**
     * Заглавие
     */
    var $title = 'Параметри';
    
    
    /**
     * Единично заглавие
     */
    var $singleTitle = 'Параметър';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'paramId, paramValue, tools=Пулт';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'cat_Wrapper, plg_RowTools, plg_LastUsedKeys, plg_SaveAndNew';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    var $lastUsedKeys = 'paramId';
    
    
    /**
     * Поле за пулт-а
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Активния таб в случай, че wrapper-а е таб контрол.
     */
    var $tabName = 'cat_Products';
    
    
    /**
     * Кой може да качва файлове
     */
    var $canAdd = 'ceo,cat';
    
    
    /**
     * Кой може да качва файлове
     */
    var $canDelete = 'ceo,cat';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'input=hidden');
        $this->FLD('paramId', 'key(mvc=cat_Params,select=name,maxSuggestions=10000)', 'input,caption=Параметър,mandatory,silent');
        $this->FLD('paramValue', 'varchar(255)', 'input,caption=Стойност,mandatory');
        
        $this->setDbUnique('productId,paramId');
    }
    
     
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    static function on_AfterPrepareListRows($mvc, $data)
    {
        $recs = &$data->recs;
        if ($recs) {
            $rows = &$data->rows;
            foreach ($recs as $i=>$rec) {
                
                $row = $rows[$i];
                $paramRec = cat_Params::fetch($rec->paramId);
                if($paramRec->type != 'enum'){
                	$Type = cls::get(cat_Params::$typeMap[$paramRec->type]);
            		$row->paramValue = $Type->toVerbal($rec->paramValue);
                }
            	if($paramRec->type != 'percent'){
            		$row->paramValue .=  ' ' . cat_Params::getVerbal($paramRec, 'suffix');
            	}
            }
        }
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = &$data->form;
        
    	if(!$form->rec->id){
    		$form->addAttr('paramId', array('onchange' => "addCmdRefresh(this.form); document.forms['{$form->formAttr['id']}'].elements['paramValue'].value ='';this.form.submit();"));
	    	expect($productId = $form->rec->productId);
			$options = self::getRemainingOptions($productId, $form->rec->id);
			expect(count($options));
	        
	        if(!$data->form->rec->id){
	        	$options = array('' => '') + $options;
	        }
	        $form->setOptions('paramId', $options);
    	} else {
    		$form->setReadOnly('paramId');
    	}
    	
        if($form->rec->paramId){
        	$form->fields['paramValue']->type = cat_Params::getParamTypeClass($form->rec->paramId, 'cat_Params');
        } else {
        	$form->setField('paramValue', 'input=hidden');
        }
    }

    
    /**
     * Връща не-използваните параметри за конкретния продукт, като опции
     *
     * @param $productId int ид на продукта
     * @param $id int ид от текущия модел, което не трябва да бъде изключено
     */
    static function getRemainingOptions($productId, $id = NULL)
    {
        $options = cat_Params::makeArray4Select();
        
        if(count($options)) {
            $query = self::getQuery();
            
            if($id) {
                $query->where("#id != {$id}");
            }

            while($rec = $query->fetch("#productId = $productId")) {
               unset($options[$rec->paramId]);
            }
        } else {
            $options = array();
        }

        return $options;
    }
    
    
    /**
     * Връща стойноста на даден параметър за даден продукт по негово sysId
     * @param int $productId - ид на продукт
     * @param int $sysId - sysId на параметъра
     * @return varchar $value - стойността на параметъра
     */
    public static function fetchParamValue($productId, $sysId)
    {
     	if($paramId = cat_Params::fetchIdBySysId($sysId)){
     		return static::fetchField("#productId = {$productId} AND #paramId = {$paramId}", 'paramValue');
     	}
     	
     	return NULL;
    }
    
    
    /**
     * Рендиране на общия изглед за 'List'
     */
    function renderDetail_($data)
    {
        $tpl = getTplFromFile('cat/tpl/products/Params.shtml');
        $tpl->append($data->changeBtn, 'TITLE');
        
        foreach((array)$data->rows as $row) {
            $block = $tpl->getBlock('param');
            $block->placeObject($row);
            $block->append2Master();
        }
            
        return $tpl;
    }
    

    /**
     * Подготвя данните за екстеншъна с параметрите на продукта
     */
    public function prepareParams($data)
    {
        $this->prepareDetail($data);
        
        if($this->haveRightFor('add', (object)array('productId' => $data->masterId)) && count(self::getRemainingOptions($data->masterId))) {
            $data->addUrl = array($this, 'add', 'productId' => $data->masterId, 'ret_url' => TRUE);
        }
    }
    
	/**
     * След проверка на ролите
     */
    public static function on_AfterGetRequiredRoles(core_Mvc $mvc, &$requiredRoles, $action, $rec)
    {
        if($requiredRoles == 'no_one') return;
    	
        if ($action == 'add' && isset($rec->productId)) {
        	if (!count($mvc::getRemainingOptions($rec->productId))) {
                $requiredRoles = 'no_one';
            } 
        }
    }
    
    
    /**
     * Рендира екстеншъна с параметри на продукт
     */
    public function renderParams($data)
    {
        if($data->addUrl) {
            $data->changeBtn = ht::createLink("<img src=" . sbf('img/16/add.png') . " valign=bottom style='margin-left:5px;'>", $data->addUrl);
        }

        return  $this->renderDetail($data);
    }
}
