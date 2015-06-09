<?php



/**
 * Клас 'plg_TreeObject' - плъгин за обекти със дървовидна структура
 *
 *
 * @category  bgerp
 * @package   plg
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class plg_TreeObject extends core_Plugin
{
	

	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Mvc $mvc)
	{
		setIfNot($mvc->parentFieldName, 'parentId');
		setIfNot($mvc->nameField, 'name');
		
		// Създаваме поле за име, ако няма такова
		if(!$mvc->getField($mvc->nameField, FALSE)){
			$mvc->FLD($mvc->nameField, "varchar(64)", 'caption=Наименование, mandatory');
		}
		
		// Поставяме поле за избор на баща, ако вече не съществува такова
		if(!$mvc->getField($mvc->parentFieldName, FALSE)){
			$mvc->FLD($mvc->parentFieldName, "key(mvc={$mvc->className},allowEmpty,select={$mvc->nameField})", 'caption=В състава на');
		}
		$mvc->setField($mvc->parentFieldName, 'silent');
		
		// Дали наследниците на обекта да са счетоводни пера
		if(!$mvc->getField('makeDescendantsFeatures', FALSE)){
			$mvc->FLD('makeDescendantsFeatures', "enum(yes=Да,no=Не)", 'caption=Наследниците да бъдат ли счетоводни признаци?->Избор,notNull,value=yes');
		}
		
		$mvc->setField($mvc->nameField, 'tdClass=leafName');
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна
	 */
	public static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$options = self::getParentOptions($mvc, $data->form->rec);
		if(count($options)){
			$data->form->setOptions($mvc->parentFieldName, $options);
		} else {
			$data->form->setReadOnly($mvc->parentFieldName);
		}
		
		$data->form->setDefault('makeDescendantsFeatures', 'yes');
	}
	
	
	/**
	 * Връща възможните опции за избор на бащи
	 * 
	 * @param stdClass $rec
	 * @return $options
	 */
	private static function getParentOptions($mvc, $rec)
	{
		$where = '';
		if($rec->id){
			$where = "#id != {$rec->id}";
		}
		
		if($mvc->getField('state', FALSE)){
			$where .= (($where != '') ? " AND " : "") . " #state != 'rejected'";
		}
		
		// При редакция оставяме само тези опции, в чиите бащи не участва текущия обект
		$options = $mvc->makeArray4Select($mvc->nameField, $where);
		if(count($options) && isset($rec->id)){
			foreach ($options as $id => $title){
				self::traverseTree($mvc, $id, $rec->id, $notAllowed);
				if(count($notAllowed) && in_array($id, $notAllowed)){
					unset($options[$id]);
				}
			}
		}
		
		return $options;
	}
	
	
	/**
	 * Търси в дърво, дали даден обект не е баща на някой от бащите на друг обект
	 * 
	 * @param int $objectId - ид на текущия обект
	 * @param int $needle - ид на обекта който търсим
	 * @param array $notAllowed - списък със забранените обекти
	 * @param array $path
	 * @return void
	 */
	private static function traverseTree($mvc, $objectId, $needle, &$notAllowed, $path = array())
	{
		// Добавяме текущия продукт
		$path[$objectId] = $objectId;
		
		// Ако стигнем до началния, прекратяваме рекурсията
		if($objectId == $needle){
			foreach($path as $p){
				
				// За всеки продукт в пътя до намерения ние го
				// добавяме в масива notAllowed, ако той, вече не е там
				$notAllowed[$p] = $p;
			}
			
			return;
		}
		
		// Намираме бащата на този обект и за него продължаваме рекурсивно
		if($parentId = $mvc->fetchField($objectId, $mvc->parentFieldName)){
			self::traverseTree($mvc, $parentId, $needle, $notAllowed, $path);
		}
	}
	
	
	/**
	 * Подготвя вербалното име на опциите по нов по азбучен ред и с подробното им име
	 * 
	 * @param core_Mvc $mvc
	 * @return void
	 */
	private static function modifySelectOptions($mvc, &$options)
	{
		if(count($options)){
			foreach ($options as $id => &$title){
				$title = $mvc->getVerbal($id, $mvc->nameField);
			}
		}
		
		// Сортираме опциите
		uasort($options, function($a, $b)
		{
			if($a == $b) return 0;
			
			return (strnatcasecmp($a, $b) < 0) ? -1 : 1;
		});
	}
	
	
	/**
	 * След подготовка на предложенията за избор в type_Keylist
	 */
	public static function on_AfterPrepareSuggestions($mvc, &$res, $keylist)
	{
		// Подменяме предложенията с подробните
		self::modifySelectOptions($mvc, $res);
	}
	
	
	/**
	 * Премахва от резултатите скритите от менютата за избор
	 */
	public static function on_AfterMakeArray4Select($mvc, &$res, $fields = NULL, &$where = "", $index = 'id'  )
	{
		// Подменяме предложенията с подробните
		self::modifySelectOptions($mvc, $res);
	}
	
	
	/**
	 * След извличане на записите от базата данни
	 */
	public static function on_AfterPrepareListRecs(core_Mvc $mvc, $data)
	{
		if(!count($data->recs)) return;
		
		// За всички записи
		foreach ($data->recs as &$rec){
			
			// Взимаме баща им
			$parentId = $rec->{$mvc->parentFieldName};
			
			// Проверяваме дали е сетнат в $data->recs, ако не е го извличаме, продължаваме докато
			// всички бащи присъстват в $data->recs. Правим това за да се подсигурим че при
			// вече филтрирани записи по някакъв признак, да не се показват само намерените 
			// редове, а и техните бащи
			while($parentId){
				if(!isset($data->recs[$parentId])){
					$parentRec = $mvc->fetch($parentId);
					$parentRec->show = TRUE;
					$rec->show = TRUE;
					$data->recs[$parentId] = $parentRec;
					$parentId = $parentRec->{$mvc->parentFieldName};
				} else {
					$parentId = NULL;
				}
			}
		}
		
		// Групираме записите по бащи
		$tree = array();
		foreach ($data->recs as $br){
			$tree[$br->parentId][] = $br;
		}
		
		// Подготвяме дървото започвайки от обектите без бащи (корените)
		$tree = self::createTree($tree, $tree[NULL]);
		
		// Обръщаме дървото в обикновен масив за показване
		$data->recs = self::flattenTree($tree);

		// Клас за таблицата
        $data->listTableClass = 'treeView';
	}
	
	
	/**
	 * Създава дърво от записите
	 * 
	 * @param array $list - масив
	 * @param int $parent - ид на бащата бащата (NULL ако няма)
	 * @return array $tree - записите в дървовидна структура
	 */
	private static function createTree(&$list, $parent, $round = -1)
	{
		$round++;
		$tree = array();
	    
	    foreach ($parent as $k => $l){
	    	if(is_null($l->parentId)){
	    		$round = 0;
	    	}
	        if(isset($list[$l->id])){
	            $l->children = self::createTree($list, $list[$l->id], $round);
	        }
	        $l->_level = $round;
	        $tree[] = $l;
	    } 
	    
	    return $tree;
	}
	
	
	/**
	 * Обръщане на дървовидния масив в нормален (децата стават редове след баща им)
	 * 
	 * @param array $array
	 * @return array - сортираните записи
	 */
	private static function flattenTree($array)
	{
		$return = array();
		
		foreach ($array as $key => $value) {
			$return[$value->id] = $value;
			if(count($value->children)){
				$return = $return + self::flattenTree($value->children);
			}
			$value->_childrenCount = count($value->children);
			unset($value->children);
		}
		
		return $return;
	}

	
	/**
	 * След преобразуване на записа в четим за хора вид.
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $row Това ще се покаже
	 * @param stdClass $rec Това е записа в машинно представяне
	 */
	public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		if(isset($fields['-list'])){
			$row->ROW_ATTR['data-parentid'] .= $rec->{$mvc->parentFieldName};
			$row->ROW_ATTR['data-id']       .= $rec->id;
			$row->ROW_ATTR['class']    .= ' treeLevel' . $rec->_level;
			
			// Ако може да се добавя поделемент, показваме бутон за добавяне
			if($mvc->haveRightFor('add')){
				$url = array($mvc, 'add', $mvc->parentFieldName => $rec->id, 'ret_url' => TRUE);
				$img = ht::createElement('img', array('src' => sbf('img/16/add.png', ''), 'style' => 'width: 13px; padding: 0px 2px;'));
				$parentTitle = $mvc->getVerbal($rec, $mvc->nameField);
				$row->_addBtn = ht::createLink($img, $url, FALSE, "title=Добави нов поделемент на '{$parentTitle}'");
			}
			
			// Ако записа е намерен при търсене добавяме му клас
			if($rec->show === TRUE){
				$row->ROW_ATTR['class'] .= " searchResult";
			}
		}
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид.
	 */
	public static function on_AfterPrepareListRows($mvc, &$data)
	{
		if(!count($data->recs)) return;
		
		// За всеки обект
		foreach($data->rows as $id => &$row){
			$rec = $data->recs[$id];
			
			// Ако обекта има деца, добавяме бутоните за скриване/показване
			if($rec->_childrenCount > 0){
				$plusIcon = sbf('img/16/toggle-expand.png', '');
				$minusIcon = sbf('img/16/toggle2.png', '');
				$plus = "<img class = 'toggleBtn plus' src='{$plusIcon}' width='13' height='13' title = 'Показване на наследниците'/>";
				$minus = "<img class = 'toggleBtn minus' src='{$minusIcon}' width='13' height='13' title = 'Скриване на наследниците'/>";
					
				$row->{$mvc->nameField} = " {$plus}{$minus}" . $row->{$mvc->nameField};
			}
		}
	}
	
	
	/**
	 * Извиква се след подготовката на колоните ($data->listFields)
	 */
	public static function on_AfterPrepareListFields($mvc, $data)
	{
		arr::placeInAssocArray($data->listFields, array('_addBtn' => ' '), NULL, $mvc->nameField);
	}
	
	
	/**
	 * След рендиране на лист таблицата
	 */
	public static function on_AfterRenderListTable($mvc, &$tpl, &$data)
	{
		jquery_Jquery::run($tpl, "treeViewAction();");
	}
	
	
	/**
	 * Връща масив от вида `< име на баща > => < име на наследник >`, ако няма баща е
	 * `< име на наследник >` => `< име на наследник >`, ако бащата на обекта
	 * има чекнато децата му да са свойства. За да е един обект свойство трябва или да има баща
	 * и децата му да са свойства или да няма баща
	 *
	 * @param string $ids - кейлист на обекти
	 * @return array - масив със свойства и стойностти
	 */
	public static function on_AfterGetFeaturesArray($mvc, &$res, $keylist)
	{
		// Ако няма подготвен масив със свойства
		if(!$res){
			$ids = keylist::toArray($keylist);
			
			$features = array();
			
			if(!count($ids)) return $features;
			
			foreach ($ids as $id){
				$rec = $mvc->fetch($id, "{$mvc->nameField},{$mvc->parentFieldName}");
					
				// Намираме името на обекта
				$nameVerbal = $mvc->getVerbal($rec, $mvc->nameField);
				$nameVerbal = strip_tags($nameVerbal);
				$keyVerbal = $nameVerbal;
					
				// Ако има баща и е указано децата му да са свойства
				if(!empty($rec->{$mvc->parentFieldName})){
					if($mvc->fetchField($rec->{$mvc->parentFieldName}, 'makeDescendantsFeatures') == 'yes'){
						$keyVerbal = $mvc->getVerbal($rec->{$mvc->parentFieldName}, $mvc->nameField);
						$keyVerbal = strip_tags($keyVerbal);
					} else {
							
						// Ако не трябва да са наследници пропускаме
						continue;
					}
				}
					
				// задаваме свойството
				$features[$keyVerbal] = $nameVerbal;
			}
			
			// Връщаме намерените свойства
			$res = $features;
		}
	}
	
	
	/**
	 * След подготовката на навигацията по сраници
	 */
	public static function on_AfterPrepareListPager($mvc, &$data)
	{
		// Предефинираме метода, за да не заработи страницирането на данните
		// В $data->recs ни трябват всички записи, за да можем да подготвим дървовидната структура
		unset($data->pager);
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'delete' && isset($rec)){
			if($mvc->fetch("#{$mvc->parentFieldName} = {$rec->id}")){
				$requiredRoles = 'no_one';
			}
		}
	}
	
	
	/**
	 * След като е готово вербалното представяне
	 */
	public static function on_AfterGetVerbal($mvc, &$num, $rec, $part)
	{
		if($part == $mvc->nameField){
			
		    if (!$rec->id) return ;
		    
			$parent = $mvc->fetchField($rec->id, $mvc->parentFieldName);
			$title = $num;
			
			while($parent && ($pRec = $mvc->fetch($parent, "{$mvc->parentFieldName},{$mvc->nameField}"))) {
				$pName = type_Varchar::escape($pRec->{$mvc->nameField});
				$title = $pName . ' » ' . $title;
				$parent = $pRec->{$mvc->parentFieldName};
			}
			
			$num = $title;
		}
	}
}