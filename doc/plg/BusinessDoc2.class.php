<?php
/**
 * Клас 'doc_plg_BusinessDoc2'
 *
 * Плъгин за избор на папка в която да се въздава документ.
 * Класа трябва да има метод getAllowedFolders който връща масив от интерфейси
 * на които трябва да отговарят папките които могат да са корици на документи.
 * След това се рендира форма за избор на запис от всеки клас отговарящ на
 * интерфейса. Трябва да се определи точно една папка, не е позволено да се 
 * изберат повече от една. След като папката се уточни се отива в екшъна за
 * добавяне на нов запис в мениджъра на документа
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_plg_BusinessDoc2 extends core_Plugin
{
    
    
    /**
     * След инициализирането на модела
     * 
     * @param core_Mvc $mvc
     * @param core_Mvc $data
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        // Проверка за приложимост на плъгина към зададения $mvc
        static::checkApplicability($mvc);
    }
    
    
    /**
     * Преди всеки екшън на мениджъра-домакин
     *
     * @param core_Manager $mvc
     * @param core_Et $tpl
     * @param core_Mvc $data
     */
    public static function on_BeforeAction(core_Mvc $mvc, &$tpl, $action)
    {
        if ($action != 'add') {
            // Плъгина действа само при добавяне на документ
            return;
        }
        
        if (!$mvc->haveRightFor($action)) {
            // Няма права за този екшън - не правим нищо - оставяме реакцията на мениджъра.
            return;
        }
        
    	if (Request::get('folderId', 'key(mvc=doc_Folders)') ||
            Request::get('threadId', 'key(mvc=doc_Threads)') ||
            Request::get('cloneId', 'key(mvc=doc_Containers)') ||
            Request::get('originId', 'key(mvc=doc_Containers)') ) {
            // Има основание - не правим нищо
            return;
        }
        
        // Генериране на форма за основание
        $form = static::prepareReasonForm($mvc);
        
        // Ако няма форма - не правим нищо
        if(!$form) return;
        
        // Формата се инпутва
        $form->input();
        if ($form->isSubmitted()) {
            if ($p = static::getReasonParams($form)) {
                $tpl = new Redirect(
                
                	// Редирект към създаването на документа в ясната папка
                    toUrl(array($mvc, $action) + $p + array('retUrl' => static::getRetUrl($mvc)))
                );
                return FALSE;
            }
        }
        
        // Ако няма поне едно поле key във формата
        if(!count($form->selectFields("#key"))){ 
        	$msg = tr('Не може да се добави документ в папка, защото възможните списъци за избор са празни');
        	return Redirect(core_Message::getErrorUrl($msg, 'page_Error'));
        }
        
        $form->title = 'Избор на папка';
        $form->toolbar->addSbBtn('Напред', 'default', array('class' => 'btn-next'), 'ef_icon = img/16/move.png');
        $form->toolbar->addBtn('Отказ', static::getRetUrl($mvc), 'ef_icon = img/16/close16.png');
        
        $form = $form->renderHtml();
        $tpl = $mvc->renderWrapping($form);
        
        // ВАЖНО: спираме изпълнението на евентуални други плъгини
        return FALSE;
    }
    
    
	/**
     * Помощен метод за определяне на URL при успешен запис или отказ
     * 
     * @param core_Mvc $mvc
     * @return string
     */
    protected static function getRetUrl(core_Mvc $mvc)
    {
        if (!$retUrl = getRetUrl()) {
            $retUrl = toUrl(array($mvc, 'list'));
        }
        
        return $retUrl;
    }
    
    
    /**
     * Подготвя формата за избор на папка
     * @param core_Mvc $mvc
     * @return core_Form $form
     */
    private static function prepareReasonForm(core_Mvc $mvc)
    {
    	// Между какви корици трябва да се избира
    	$interfaces = $mvc::getAllowedFolders();
    	
    	// Ако няма корици се прескача плъгина
    	if(!count($interfaces)) return NULL;
    	
    	// Ако има '*' се показват всички класове които могат да са корици
    	if(in_array('*', $interfaces)){
    		$interfaces = array('doc_FolderIntf');
    	}
    	
    	// Намират се всички класове отговарящи на тези интерфейси
    	$coversArr = array();
    	foreach ($interfaces as $int){
    		$coversArr +=  core_Classes::getOptionsByInterface($int);
    	}
    	
    	// Подготовка на формата за избор на папка
    	$form = cls::get('core_Form');
    	static::getFormFields($mvc, $form, $coversArr);
    	
    	return $form;
    }
    
    
    /**
     * Подготвя полетата на формата
     */
	private static function getFormFields(core_Mvc $mvc, &$form, $coversArr)
    {
    	core_Debug::$isLogging = FALSE;
    	foreach ($coversArr as $coverId){
    		
    		// Подадената корица, трябва да е съществуващ 
    		// клас и да може да бъде корица на папка
    		if(cls::haveInterface('doc_FolderIntf', $coverId)){
    			
    			// Създаване на поле за избор от дадения клас
    			$Class = cls::get($coverId);
    			
    			$options = $mvc->getCoverOptions($Class);
	    		$optionList = implode(", ", array_keys($options));
	    		list($pName, $coverName) = explode('_', $coverId);
	    		$coverName = $pName . strtolower(rtrim($coverName, 's')) . "Id";
	    		if ($optionList) {
	    			$form->FNC($coverName, "key(mvc={$coverId},allowEmpty)", "input,caption=Изберете точно една папка->{$Class->singleTitle},width=100%,key");
	    		} else {
	    			$form->FNC($coverName, "varchar", "input,caption=Изберете точно една папка->{$Class->singleTitle},width=100%");
	    			$form->setReadOnly($coverName);
	    			
	    			continue;
	    		}
	    		
	    		// Показват се само обектите до които има достъп потребителя
	    		$query = $Class::getQuery();
	    		$query->where("#id IN ({$optionList})");
	    		$query->show('inCharge,access,shared');
	    		while($rec = $query->fetch()){
	    			if(doc_Folders::haveRightToObject($rec)){
	    				$options[$rec->id] = $options[$rec->id];
	    			}
	    		}
	    		
	    		$form->setOptions($coverName, $options);
    		}
    	}
    	
	    core_Debug::$isLogging = TRUE;
    }
    
    
    /**
     * Връща ид-то на избраната папка,
     * проверява дали е избрана само една папка
     */
    private static function getReasonParams(core_Form $form)
    {
    	$selectedField = $value = NULL;
    	$errFields = array();
    	
    	// Обхождат се всички попълнени полета
    	$fields = $form->selectFields('');
    	foreach ($fields as $name => $fld){
    		$fldValue = $form->rec->{$name};
    		if($fldValue){
	    		if(!$value){
	    			$value = $fldValue;
	    			$selectedField = $fld->type->params['mvc'];
	    		} else {
	    			$errFields[] = $name;
	    		}
    		}
    	}

    	// Ако няма избран нито един обект, се показва грешка
		if(!$selectedField){
    		$form->setError(',', 'Не е избрана папка');
    		return;
    	}

    	// Ако има избран повече от един обект, се показва грешка
    	if(count($errFields)){
    		array_unshift($errFields, $selectedField);
    		$form->setError(implode(',', $errFields), 'Трябва да посочите точно еднa папка');
    		return;
    	}
    	
    	// При избран точно един обект се форсира неговата папка и се връща
    	return array('folderId' => $selectedField::forceCoverAndFolder($value));
    }
    
    
	/**
     * Проверява дали този плъгин е приложим към зададен мениджър
     */
    protected static function checkApplicability(core_Mvc $mvc)
    {
        // Прикачане е допустимо само към наследник на core_Manager ...
        if (!$mvc instanceof core_Manager) {
            return FALSE;
        }
        
        // ... към който е прикачен doc_DocumentPlg
        $plugins = arr::make($mvc->loadList);

        if (isset($plugins['doc_DocumentPlg'])) {
            return FALSE;
        } 
        
        return TRUE;
    }
}
