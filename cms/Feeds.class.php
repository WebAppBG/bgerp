<?php

/**
 * Хранилка
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Ивелин Димов <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_Feeds extends core_Manager {

	
	/**
	 * Заглавие на страницата
	 */
	var $title = 'Хранилки';
	
	
	/**
	 * Зареждане на необходимите плъгини
	 */
	var $loadList = 'plg_RowTools, plg_Created, plg_Modified, cms_Wrapper';
	

    /**
     * Да не се кодират id-тата
     */
    var $protectId = FALSE;
	
	/**
	 * Поле за лентата с инструменти
	 */
	var $rowToolsField = 'tools';
	
	
	/**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'feed_Generator';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,admin,cms';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,admin,cms';
    
    
	/**
	 * Полета за листов изглед 
	 */
	var $listFields = 'tools=Пулт, title, description, type, url, source, logo, lg, maxItems, createdOn, createdBy, modifiedOn, modifiedBy';
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('title', 'varchar(50)', 'caption=Наименование, mandatory');
		$this->FLD('description', 'text', 'caption=Oписание, mandatory');
		$this->FLD('logo', 'fileman_FileType(bucket=feedImages)', 'caption=Лого');
		$this->FLD('source', 'class(interface=cms_FeedsSourceIntf,allowEmpty)', 'caption=Източник, mandatory,silent');
		$this->FLD('type', 'enum(rss=RSS,rss2=RSS 2.0,atom=ATOM)', 'caption=Тип, notNull, mandatory');
		$this->FLD('lg', 'enum(bg=Български,en=Английски)', 'caption=Език, notNull, value=bg');
		$this->FLD('maxItems', 'int', 'caption=Максимално, mandatory, notNull');
		$this->FLD('data', 'blob(serialize,compress)', 'caption=Информация за продукта,input=none');
		
		// Определяме уникален индекс
		$this->setDbUnique('title, type');
	}
	
	
	/**
	 *  Създаваме нова кофа за логото
	 */
	static function on_AfterSetupMvc($mvc, &$res)
    {
        // Кофа за логото
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('feedImages', 'Лого на хранилката', 'jpg,jpeg,png,bmp,gif,image/*', '3MB', 'user', 'every_one');
    }
    
    
    /**
     *  Генерира хранилка 
     */
	function act_Get()
	{
		// Извличаме записа на хранилката
		expect($id = Request::get('id', 'int'));
		expect($rec = $this->fetch($id));
		
		// Инстанцираме източника
		expect($source = cls::get($rec->source));
		
		// Генерираме масив от елементи за хранилката
		$items = $source->getItems($rec->maxItems, $rec->lg, $rec->data);
		
		// Вкарваме компонента FeedWriter
		$path = "cms/feedWriter/FeedTypes.php";
        require_once getFullPath($path);
		
        // Взависимост от посоченият вид, инстанцираме определения клас хранилка
        switch ($rec->type) {
        	case 'rss' : 
        		 // Инстанцираме нова хранилка от тип RSS 1
        		 $feed = new RSS1FeedWriter();
				 $feed->setChannelAbout(toUrl(array($this, 'get', $rec->id), 'absolute'));
				 break;

        	case 'rss2' : 
        		 $pubDate = $this->getPubDate($items);
        		 
        		 // Инстанцираме нова хранилка от тип RSS 2.0
        		 $feed = new RSS2FeedWriter();
  				 $feed->setChannelElement('language', $rec->lg);
  				 $feed->setChannelElement('pubDate', date(DATE_RSS, time()));
  				 if($rec->logo){
  				 	$feed->setImage($rec->title, toUrl(array($this, 'get', $rec->id), 'absolute'), fileman_Download::getDownloadUrl($rec->logo));
  				 }
  				 break;

        	case 'atom' : 
        		// Инстанцираме нова хранилка от тип ATOM
        		$feed = new ATOMFeedWriter();
        		$feed->setChannelElement('updated', date(DATE_ATOM, time()));
				$feed->setChannelElement('author', array('name'=>'bgerp'));
        		break;
        }
        
        // Заглавие, Адрес и Описание на хранилката
		$feed->setTitle($rec->title);
		$feed->setLink(toUrl(array('blogm_Articles'), 'absolute'));
        $feed->setDescription($rec->description);
        
        // Попълваме хранилката от източника
		foreach($items as $item) {
			
        	$newFeed = $feed->createNewItem();
		    $newFeed->setTitle($item->title);
		    $newFeed->setlink($item->link);
		    if($rec->type == 'rss2'){
		    	$newFeed->setGuid($item->link);
		    }
		    $newFeed->setDate($item->date);
		    $newFeed->setDescription($item->description);
		    
		    // Добавяме новия елемент на хранилката
		    $feed->addItem($newFeed);
        }
        
        // Генерираме хранилката
		$feed->generateFeed();
		
        shutdown();
	}
	
	
	/**
	 * Връща датата на последния елемент във фийда
	 */
	private function getPubDate($items)
	{
		if($items){
			foreach ($items as $i => $item){
				$dates[] = $item->date;
			}
			
			rsort($dates);
		
			return reset($dates);
		}
	}
	
	
	/**
	 *  Екшън за показване на всички Хранилки за външен достъп
	 */
	function act_Feeds()
	{
        cms_Content::setCurrent();

		$data = new stdClass();
		$data->action = 'feeds';
		$data->query = $this->getQuery();
		
		// Подготвяме хранилките
		$this->prepareFeeds($data);
		
		// Рендираме екшъна
		$layout = $this->renderFeeds($data);
		
		// Поставяме обвивката за външен достъп
		Mode::set('wrapper', 'cms_Page');
		
		return $layout;
	}
	
	
	/**
	 * Подготвяме хранилката
	 */
	function prepareFeeds($data)
	{
		$fields = $this->selectFields("");
		$fields['-feeds'] = TRUE;
		$tableName = static::instance()->dbTableName;
		
		// Проверка дали съществува таблица на модела
		if(static::instance()->db->tableExists($tableName)) {
			
			// Попълваме вътрешните и вербалните записи
			while($rec = $data->query->fetch(array("#lg = '[#1#]'", cms_Content::getLang()))) {
				$data->recs[$rec->id] = $rec;
				$data->rows[$rec->id] = $this->recToVerbal($rec, $fields);
			}
		} else {
			$msg = new stdClass();
			$msg->title = tr('Има проблем при генерирането на емисиите');
			$data->rows[] = $msg;
		} 
	}
	
	
	/**
	 * Рендираме списъка от хранилки за външен изглед
	 * @return core_ET
	 */
	function renderFeeds($data)
	{
		$layout = getTplFromFile('cms/tpl/Feeds.shtml');
		
		// Поставяме иконка и заглавие
		$layout->append(tr('Нашите емисии'), 'HEADER');
 
		if(count($data->rows) > 0) {
			foreach($data->rows as $row) {
				$feedTpl = $layout->getBlock('ROW');
				$feedTpl->placeObject($row);
				$feedTpl->removeBlocks();
				$feedTpl->append2master();
			}
		}
		
		return $layout;
	}
	
	
	/**
	 * Модификация по вербалните записи
	 */
	static function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
	{
		// Подготвяме адреса на хранилката
		$rssLink = array($this, 'get', $rec->id);
        $typeUrl = cls::get('type_Url');
		$row->url = $typeUrl->toVerbal(toUrl($rssLink, 'absolute'));
		
		if($fields['-feeds']) {
			// Преобразуваме логото на фийда да е  img
			$imgUrl = sbf('cms/img/' . $rec->type . '.png', '\'');
			
			$row->title = ht::createLink($row->title, $rssLink, NULL, 
                array('class' => 'linkWithIcon', 'style' => "padding-left:45px !important; background-image:url({$imgUrl})"));
			
		}
	}
	
	
	/**
	 * Генерира хедърите за обвивката
	 * @return core_ET
	 */
	static function generateHeaders()
	{
		// Шаблона който ще се връща
		$tpl = new ET('');
		
		// Заявка за работа с модела 
        $feedQuery = static::getQuery();
        $curLg = cms_Content::getLang();
        
        $tableName = static::instance()->dbTableName;
        if(static::instance()->db->tableExists($tableName)) {
	        while($feed = $feedQuery->fetch()) {
	       		
	       		// Адрес на хранилката
	       		$url = toUrl(array('cms_Feeds', 'get', $feed->id), 'absolute');
	       		
	       		// Взависимост от типа на хранилката определяме типа на хедъра
	       		if($feed->type != 'atom') {
	       			$type = 'application/rss+xml';
	       		} else {
	       			$type = 'application/atom+xml';
	       		}
	       		
	       		if($feed->lg == $curLg){
	       			
	       			// Натрупваме генерираният хедър в шаблона, ако хранилката е от същия език, като на външната част
	       			$tpl->append("\n<link rel='alternate' type='{$type}' title='{$feed->title}' href='{$url}' />");
	       		}
	       	}
        }
		
       	return $tpl;
	}
	
	
	/**
	 * Генерира икона с линк за екшъна с хранилките
	 * @return core_ET
	 */
	static function generateFeedLink()
	{
		// Шаблон в който ще се добави линка
		$tpl = new ET('');
		
		$query = static::getQuery();
		$feeds = $query->fetchAll();
		if(!count($feeds)) return NULL;
		
		// Подготвяме иконка с линк към публичния лист на хранилката
		$url = array('cms_Feeds', 'feeds');
		
        $src = sbf("cms/img/rss_icon_glass_gray24.PNG", "");

        $img = ht::createElement('img', array('src' => $src));

		$link = ht::createLink($img, $url, NULL, array('class' => 'soc-following noSelect'));
		
		// Добавяме линка към шаблона
		$tpl->append($link);
		
		// Връщаме шаблона
		return $tpl;
	}
	
	
	/**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    static function on_AfterPrepareEditForm($mvc, $data)
    {
    	$form = &$data->form;
    	$form->addAttr('source', array('onchange' => "addCmdRefresh(this.form);this.form.submit();"));
    	
	    if($form->rec->source){
	    	$Source = cls::get($form->rec->source);
	    	if($Source->feedFilterField){
	    		$sourceField = $Source->fields[$Source->feedFilterField];
	    		$form->FNC($Source->feedFilterField, $sourceField->type, "input,fromSource,caption={$sourceField->caption},after=type");
	    			
		    	if($form->rec->data){
		    		$form->setDefault($Source->feedFilterField, $form->rec->data);
		    	}
	    	}
	    }
    }
    
    
    /**
     * След инпут на формата
     */
	public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
    		$fld = $form->selectFields('#fromSource');
    		if(!count($fld)) return;
    		$fld = reset($fld);
    		
    		$form->rec->data = $form->rec->{$fld->name};
    	}
    }
}