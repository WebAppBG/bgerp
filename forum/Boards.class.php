<?php

/**
 * Дъски
 *
 *
 * @category  bgerp
 * @package   forum
 * @author    Ивелин Димов <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class forum_Boards extends core_Master {
	
	
	/**
	 * Заглавие на страницата
	 */
	var $title = 'Форумни дъски';
   

    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'cms_SourceIntf';
	

	/**
	 * Зареждане на необходимите плъгини
	 */
	var $loadList = 'plg_RowTools, plg_Created, plg_Modified, forum_Wrapper, plg_Sorting'; 
	
	
	/**
	 * Поле за лентата с инструменти
	 */
	var $rowToolsField = 'tools';
	
	
	/**
	 * Полета за листов изглед 
	 */
	var $listFields = 'tools=Пулт, title, category, shortDesc, themesCnt, commentsCnt, boardType, shared, lastComment, lastCommentedTheme, lastCommentBy, createdOn, createdBy';
	
	
	/**
	 * Теми и коментари на дъската
	 */
	var $details = 'forum_Postings';
	
	
	/**
	 * Кой може да листва дъските
	 */
	var $canRead = 'forum, cms, admin,ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'forum,ceo,admin,cms';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'forum,ceo,admin,cms';
	
	
	/**
	 * Кой може да добявя,редактира или изтрива дъска
	 */
	var $canWrite = 'forum, cms, admin,ceo';
	
	
	/**
	 * Кой може да изтрива дъските
	 */
	var $canDelete = 'no_one';
	
	
	/**
	 * Файл за единичен изглед
	 */
	var $singleLayoutFile = 'forum/tpl/SingleBoard.shtml';
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('title', 'varchar(50)', 'caption=Име, mandatory, width=400px');
		$this->FLD('shortDesc', 'varchar(100)', 'caption=Oписание, mandatory, width=100%');
		$this->FLD('category', 'key(mvc=forum_Categories,select=title,groupBy=type)', 'caption=Категория, mandatory');
		$this->FLD('boardType', 'enum(normal=Нормална,confidential=Конфиденциална)', 'caption=Достъп->Тип, notNull, value=normal');
		$this->FLD('shared', 'userList', 'caption=Достъп->Споделяне');
		$this->FLD('themesCnt', 'int', 'caption=Темите, input=none, value=0');
		$this->FLD('commentsCnt', 'int', 'caption=Коментари, input=none, value=0');
		$this->FLD('lastComment', 'datetime(format=smartTime)', 'caption=Последно->кога, input=none');
		$this->FLD('lastCommentBy', 'int', 'caption=Последно->кой, input=none');
		$this->FLD('lastCommentedTheme', 'int', 'caption=Последно->къде, input=none');
		
		// Поставяме уникален индекс
		$this->setDbUnique('title');
	}
	
	
	/**
	 * Подрежане и филтриране на дъските по категории
	 */ 
	function on_BeforePrepareListRecs($mvc, $res, $data)
	{
		// Предпазване от листване на конфиденциални дъски
		$cu = core_Users::getCurrent();
		
		// Пропускаме конфиденциалните папки,несподелени с текущия потребител
       	if(!haveRole('forum') && $cu >0) {
            $data->query->where("NOT (#boardType = 'confidential'  AND !(#shared LIKE '%|{$cu}|%'))");
        }
		
		if($category = Request::get('cat')) {
			$data->query->where(array("#category = [#1#]", $category));
		}
		
		$data->query->orderBy('#category');
	}
	
	
	/**
	 * Обновяваме броя на темите и коментарите на дъската. Обновяваме кой, къде и
	 * кога е направил последния коментар
	 * @param int $id
	 */
	function updateBoard($id)
	{
		// Заявка за работа с темите от дъската
		$themesQuery = forum_Postings::getQuery();
		$themesQuery->where("#boardId = {$id} AND #themeId IS NULL");
		
		// Заявка за работа с коментарите от дъската
		$commentsQuery = forum_Postings::getQuery();
		$commentsQuery->where("#boardId = {$id} AND #themeId IS NOT NULL");
		
		// Извличане на последните тема и коментар
		$themesQuery->XPR('maxId', 'int', 'max(#id)');
		$commentsQuery->XPR('maxId', 'int', 'max(#id)');
		
		if($commentsQuery->count() > 0) {
			
			// Ако има коментари то намираме, последно добавения коментар
			$last = forum_Postings::fetch($commentsQuery->fetch()->maxId);
		} else {
			if($themesQuery->count() > 0) {
				
				// Ако няма коментари но има теми, намираме последно добавената тема
				$last = forum_Postings::fetch($themesQuery->fetch()->maxId);
			} else {
				
				// Ако няма нито коментари нито теми, то $last е NULL
				$last = NULL;
			  }
		  }
		
		// Дъската в която ще обновяваме информацията
		$rec = $this->fetch($id);
		
		// Броят на постингите, които са теми
		$rec->themesCnt = $themesQuery->count();
		
		// Броят на постингите, които са коментари
		$rec->commentsCnt = $commentsQuery->count();
		
		// Ако има коментар в дъската, ние обновяваме кой, кога и къде го е направил
		if($last) { 
			($last->themeId !== NULL) ? $id = $last->themeId : $id = $last->id;
			
			// Обновяваме кога къде и от кого е направн последния коментар, ако няма 
			// коментари обновяваме коя, кога и къде е последно създадената тема
			$rec->lastCommentedTheme = $id;
			$rec->lastComment = $last->createdOn;
		    $rec->lastCommentBy = $last->createdBy;
		   
		}
		
	    // Обновяваме дъската
	    $this->save($rec);
	}
	
	
	/**
	 * Екшън за преглеждане на всички дъски
	 */
	function act_Forum()
	{
		// Създаваме празен $data обект
		$data = new stdClass();

        // Създаваме заявка към модела
		$data->query = $this->getQuery();
		
		// Тема по подразбиране
		$conf = core_Packs::getConfig('forum');
        $data->forumTheme = $conf->FORUM_DEFAULT_THEME;
        $data->title = tr($conf->GREETING_MESSAGE);
        $data->action = 'forum';
        $data->display = 'public';
        $data->category = Request::get('cat');
        
        // Подготвяме необходимите данни за показване на дъските
        $this->prepareForum($data);
        
        // Рендираме Дъските в форума
        $layout = $this->renderForum($data);
       
        return $layout;
	}
	
	
	/**
	 *  Подготовка на списъка с дъски, разпределени по техните категории
	 */
	 function prepareForum($data)
	 {
		// Извличаме всички категории на дъските
		forum_Categories::prepareCategories($data);

		if(count($data->categories)) {
			
			// За всяка категория ние подготвяме списъка от дъски, които са част от нея
			foreach($data->categories as $category){
				$this->prepareBoards($category);
			}
		}
		
		if($this->haveRightFor('list')) {
			if($data->category) {
				$url = array($this, 'list', 'category' => $data->category);
			} else {
				$url = array($this, 'list');
			  }
			
			$data->listUrl = $url;
		}
		
		$data->searchUrl = array('forum_Postings', 'search');
		$data->navigation = $this->prepareNavigation($data->category, NULL, NULL, $data->display);
	 }
	
	
	/**
	 * Подготвяме, навигационните линкове за бърз достъп до избраната категория/дъска/тема
	 * в навигационното поле на форума
	 * @param int $categoryId - ид на категорията
	 * @param int $boardId - ид на дъската
	 * @param int $themeId - ид на темата
	 * @param string $display - дали е за външен изглед 
	 * @return array $arr - масив с линковете
	 */
	function prepareNavigation($categoryId, $boardId = NULL, $themeId = NULL, $display = NULL)
	{
		$arr = array();
		$varchar = cls::get("type_Varchar");
		
		$url = array('forum_Boards', 'list');
		if($display == 'public'){
				$url[1] = 'forum';
		}
		
		// Линк към началото на форума
		$arr[] = ht::createLink(tr('Форуми'), $url);
		
		if($categoryId) {
			$url = array('forum_Boards', 'list', 'cat' => $categoryId);
			if($display == 'public'){
				$url[1] = 'forum';
			}
			
			$catName = forum_Categories::fetchField($categoryId, "title");
			$arr[] = ht::createLink($varchar->toVerbal($catName), $url);
		}
		if($boardId){
			$url = array($this, 'single', $boardId);
			if($display == 'public'){
				$url[1] = 'browse';
			}
			
			$boardName = $this->fetchField($boardId, "title");
			$arr[] = ht::createLink($varchar->toVerbal($boardName), $url);
		}
		if($themeId){
			$url = array('forum_Postings', 'topic', $themeId);
			if($display == 'public'){
				$url[1] = 'theme';
			}
			$themeName = $this->forum_Postings->fetchField($themeId, "title");
			$arr[] = ht::createLink($varchar->toVerbal($themeName), $url);
		}
		
		return $arr;
	}
	
	
	/**
	 * Подготовка на формата за търсене
	 */
	function prepareSearchForm($data)
	{
		$form = cls::get('core_Form');
 		$data->searchForm = $form;
	}
	
	
	/**
	 * Добавяме всеки елемент на в последователност от линкове
	 */
	function renderNavigation($data)
	{
		if($data->navigation) {
			foreach($data->navigation as $nav) {
				$navigation .= $nav . "&nbsp;»&nbsp;"; 
			}
		}
		// Премахваме излишните символи от края на линка
		$navigation = trim($navigation, "&nbsp»&nbsp;");
		$navigation = "<span id='navigation-inner-link'>" . $navigation . "</span>";
		
		if($data->display) {
		   
		   // Добавяме външният изглед, само ако екшъна е за външен изглед
		   Mode::set('wrapper', 'cms_tpl_Page');
		  
		   // Засветяване на Форум  в менюто
		   $selfId = core_Classes::fetchIdByName($this);
		   Mode::set('cMenuId', cms_Content::fetchField("#source = {$selfId}", 'id'));
		}
		
        return $navigation;
	}
	
	
	/**
	 * Рендираме формата за търсене
	 */
	function renderSearchForm_(&$data)
    {
 		if($data->searchForm){
	    	$data->searchForm->layout = new ET(getFileContent($data->forumTheme . '/SearchForm.shtml'));
	 		$data->searchForm->layout->replace(toUrl(array('forum_Postings', 'search')), 'ACTION');
			$data->searchForm->layout->replace(sbf('img/16/find.png', ''), 'FIND_IMG');
			
			return $data->searchForm->renderHtml();
 		}
	}
	
	
	/**
	 * Подготвя дъските от подадената категория
	 */
	function prepareBoards(&$category)
	{
		$query = $this->getQuery();
		$query->where("#category = {$category->id}");
		
	 	// Предпазване от листване на конфиденциални дъски
		$cu = core_Users::getCurrent();

       	if(!haveRole('forum') && $cu >0) {
            $query->where("NOT (#boardType = 'confidential'  AND !(#shared LIKE '%|{$cu}|%'))");
        }
		
		$fields = $this->selectFields("");
		$fields['-public'] = TRUE;
		while($rec = $query->fetch()) {
			
		// Ако имаме права да виждаме дъските
		if($this->haveRightFor('read', $rec)){
				$category->boards->recs[$rec->id] = $rec;
	 			$category->boards->rows[$rec->id] = $this->recToVerbal($rec, $fields);
	 		}
		}
	}
	
	
	/**
	 *  Рендираме списъка с дъските групирани по категории
	 */
	function renderForum($data)
	{
		$tpl = new ET(getFileContent($data->forumTheme . '/Index.shtml'));
 		$tpl->replace('<h2>' . $data->title . '</h2>', 'GREETING');
		$boards = new ET(getFileContent($data->forumTheme . '/Boards.shtml'));
		
		if(count($data->categories)) {
        	
        	// Зареждаме шаблоните веднъж в паметта и после само ги клонирваме
        	$categoryTpl = $tpl->getBlock("category");
            $icon = ht::createElement('img', array('src' => sbf($data->forumTheme . "/img/32/Card-file-icon.png", "")));
        	
            foreach($data->categories as $category) {
                
                // За всяка категория ние поставяме името и преди  списъка с нейните дъски
                $catTpl = clone($categoryTpl);
                $catTpl->replace($category->title, 'cat');
                if($category->boards->rows) { 
                    
                    // За всички дъски от категорията ние ги поставяме под нея в шаблона
                    foreach($category->boards->rows as $row) {
                    	$rowTpl = clone($boards);
                        $rowTpl->placeObject($row);
                        $rowTpl->replace($icon, "ICON");
                        $rowTpl->removeBlocks();
                    	$catTpl->append($rowTpl, 'BOARDS');
                    }
                } else {
                       $catTpl->replace(new ET("<li class='no-boards'>" . tr("Няма Дъски") . "</li>"), 'BOARDS');
                }
                
				$catTpl->removeBlocks();
				$catTpl->append2master();
            }
        }
		
		if($data->listUrl) { 
			$tpl->append(ht::createBtn('Работилница', $data->listUrl, NULL, NULL, 'ef_icon=img/16/application_edit.png'), 'TOOLBAR');
		}
		
		if($data->searchUrl){
			$tpl->append(ht::createBtn('Търсене', $data->searchUrl, NULL, NULL, 'ef_icon=img/16/application_edit.png'), 'TOOLBAR');
		}
		
		$tpl->push($data->forumTheme . '/styles.css', 'CSS');
        $tpl->replace($this->renderNavigation($data), 'NAVIGATION');
        $tpl->replace($this->renderSearchForm($data), 'SEARCH_FORM');
        
		// Връщаме шаблона с всички дъски групирани по категории
		return $tpl;
	}
	
	
	/**
	 * Екшън за преглеждане на темите в една дъска
	 */
	function act_Browse() 
	{
		$id = Request::get('id', 'int');
		$data = new stdClass();
		$data->query = $this->getQuery();
		
		// Тема по подразбиране
		$conf = core_Packs::getConfig('forum');
        $data->forumTheme = $conf->FORUM_DEFAULT_THEME;
        $data->action = 'browse';
        $data->display = 'public';
        expect($data->rec = $this->fetch($id));
		
		// Изискваме потребителя да има права да вижда  дъската
		$this->requireRightFor('read', $data->rec);
		
		// Подготвяме информацията нужна за преглеждане на дъската
		$this->prepareBrowse($data);
		
		// Рендираме разглежданата дъска
		$layout = $this->renderBrowse($data);
		
		return $layout;
	}
	
	
	/**
	 * Подготовка на темите от дъската
	 */
	function prepareBrowse_($data)
	{
		$data->query->orderBy('createdOn', 'DESC');
		$fields = $this->selectFields("");
        $fields['-browse'] = TRUE;
        $data->row = $this->recToVerbal($data->rec, $fields);
        
        // Извличаме всички Постинги, които са начало на нова тема в дъската
        $this->forum_Postings->prepareBoardThemes($data);
        $this->prepareSearchForm($data);
		$data->navigation = $this->prepareNavigation($data->rec->category, $data->rec->id, NULL, $data->display);
    }
	
	
	/**
	 *  Рендиране на списъка от теми, в разглежданата дъска
	 */
	function renderBrowse_($data) 
	{
		$tpl = new ET(getFileContent($data->forumTheme . '/Browse.shtml'));
		
		$tpl->placeObject($data->row);
		
		// Рендираме всички теми от дъската
		$tpl = $this->forum_Postings->renderBoardThemes($data, $tpl);
		
		if($data->submitUrl) { 
			$tpl->append(ht::createBtn('Нова Тема', $data->submitUrl, NULL, NULL, 'id=btnAdd,class=btn-add'), 'TOOLBAR');
		}
		
		if($data->singleUrl) { 
			$tpl->append(ht::createBtn('Работилница', $data->singleUrl, NULL, NULL, 'ef_icon=img/16/application_edit.png'), 'TOOLBAR');
		}
		
		$tpl->push($data->forumTheme . '/styles.css', 'CSS');
        $tpl->replace($this->renderNavigation($data), 'NAVIGATION');
        $tpl->replace($this->renderSearchForm($data), 'SEARCH_FORM');
         
		return $tpl;
	}
	
	
	/**
     * Бутон за преглед на дъските във външен изглед
     */
	static function on_AfterPrepareListToolbar($mvc, &$data)
    {
		if($cat = Request::get('category')){
			$url = array($this, 'forum', 'cat' => $cat);
		} else {
			$url = array($this, 'forum');
		  }
		
    	$data->toolbar->addBtn('Преглед', $url);
    }
 	
    
    /**
     * Бутон за преглед на дъската във външен изглед
     */
    function on_AfterPrepareSingleToolbar($mvc, $data)
    {
		 if ($mvc->haveRightFor('read', $data->rec)) {
            $data->toolbar->addBtn('Преглед', array($this, 'Browse', $data->rec->id));
        }
    }
    
    
    /**
	 * Модификация на ролите, които могат да видят избраната тема
	 */
    static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{  
		if($action == 'read' && isset($rec->id)) {
			
			($mvc::haveRightToObject($rec, $userId)) ? $res = 'every_one' : $res = 'forum';
		}
	}
	
	
	/**
	 * Функция проверяваща дали потребителя има достъп до дъската
	 * @param stdClass $rec
	 * @param int $userId 
	 * @return boolean TRUE/FALSE
	 */
	static function haveRightToObject($rec, $userId = NULL)
    {
        if(!$userId) {
            $userId = core_Users::getCurrent();
        }
        
        // 'forum' има достъп до всяка дъска
        if(haveRole('forum')) return TRUE;
        
        // Ако дъската е 'нормална' всички имат достъп до нея
        if($rec->boardType == 'normal') return TRUE;
        
        // Ако дъската е споделена с текущия потребител, той има достъп
        if(strpos($rec->shared, '|' . $userId . '|') !== FALSE) return TRUE;
        
        // Ако никое от горните не е изпълнено - отказваме достъпа
        return FALSE;
    }
	
	
	/**
	 * Модификация на вербалните записи
	 */
	function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
   	{
   		if($fields['-list'] || $fields['-single']) {
   			
   			// Правим заглавието на линк за единичен изглед
   			$row->title = ht::createLink($row->title, array($mvc, 'Single', $rec->id));
   			
   			if(!$rec->lastCommentBy) {
   				$row->lastCommentedTheme = tr('няма');
   				$row->lastComment = tr('няма');
   				$row->lastCommentBy = tr('няма');
   			} else {
   				$row->lastCommentBy = crm_Profiles::createLink($rec->lastCommentBy);
   			}

   			if($rec->lastCommentedTheme) {
   				$themeRec = forum_Postings::fetch($rec->lastCommentedTheme);
   				if(strlen($themeRec->title) >= 10) {
   					
   					// Ако заглавието и е много дълго го съкръщаваме
   					$themeRec->title = mb_substr($themeRec->title, 0 , 10);
   					$themeRec->title .= "..."; 
   				}
   				
   				$row->lastCommentedTheme = ht::createLink($themeRec->title, array('forum_Postings', 'Topic', $themeRec->id));
   			}
   		}
   		
   		// Модификации по вербалното представяне на записите  в екшъна forum
   		if($fields['-public']) {
   			
   			$row->title = ht::createLink($row->title, array($mvc, 'Browse', $rec->id));
   			$categoryRec = forum_Categories::fetch($rec->category);
   			$row->category = forum_Categories::recToVerbal($categoryRec, 'id,title,-public');
   			$row->themesCnt .= "&nbsp;" . tr('Теми');
   			$row->commentsCnt .= "&nbsp;" . tr('Мнения');
   			
   			// Ако темата има последен коментар
   			if($rec->lastCommentBy) {
	          
	           // преобразуваме ид-то на последно коментираната тема в разбираем вид
	           $themeRec = forum_Postings::fetch($rec->lastCommentedTheme);
	           if(strlen($themeRec->title) >= 25) {
	           	
	           		// Ако заглавието и е много дълго го съкръщаваме
   					$themeRec->title = mb_substr($themeRec->title, 0, 20);
   					$themeRec->title .= "..."; 
   				}
   				
   				$row->lastCommentedTheme =  ht::createLink($themeRec->title, array('forum_Postings', 'Theme', $themeRec->id));
	           
	           // Намираме граватара и ника на потребителя коментирал последно
	           $lastUser = core_Users::fetch($rec->lastCommentBy);
	           $row->lastAvatar =  avatar_Plugin::getImg(0, $lastUser->email, 50);
	           $row->lastNick = $lastUser->nick;
	       } else {
	           $row->noComment = tr('дъската е празна');
	        }
   		}
    }
    
   
    /**
     * Връща URL към себе си (форума)
     */
    function getContentUrl($cMenuId)
    {
        return array('forum_Boards', 'forum');
    }
    
    
    /**
     * Ако сме в екшън за единичен изглед, подготвяме навигацията
     */
    static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
    	$data->navigation = $mvc->prepareNavigation($data->rec->category, $data->rec->id);
    }
    
    
    /**
     * Подготвяме вътрешната навигация в List екшъна
     */
    static function on_BeforePrepareListTitle($mvc, &$res, $data)
    {
    	$data->navigation = $mvc->prepareNavigation(Request::get('cat'));
    }
    
    
	/**
     * Подготвяме вътрешната навигация в List екшъна
     */
    static function on_AfterRenderListTitle($mvc, &$tpl, $data)
    {
    	$tpl->replace(new ET("[#NAVIGATION#]"));
    }
    
    
    /**
     * Рендираме навигацията след рендирането на обвивката
     */
    function on_AfterRenderWrapping($mvc, &$tpl, $content, $data = NULL) {
    	
    	$tpl->push('forum/tpl/styles.css', 'CSS');
    	if($data->navigation){ 
    		$tpl->replace($this->renderNavigation($data), 'NAVIGATION');
    	}
     }
     
     
     /**
      *  Обновяване на категория, след добавяне на нова тема
      */
     static function on_AfterCreate($mvc, $rec)
     {
     	forum_Categories::updateCategory($rec->category);
     }
}
