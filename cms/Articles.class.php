<?php


/**
 * Публични статии
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_Articles extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Публични статии";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_Modified, plg_Search, plg_State2, plg_RowTools, plg_Printing, cms_Wrapper, plg_Sorting, cms_VerbalIdPlg, plg_AutoFilter, change_Plugin';
    
    
    /**
     * 
     */
    var $vidFieldName = 'vid';
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'cms_SourceIntf';
    
    
    /**
     * Полетата, които могат да се променят с change_Plugin
     */
    var $changableFields = 'level, title, body, menuId, vid';

    
    /**
     * Кой може да променя записа
     */
    var $canChangerec = 'cms,admin,ceo';
    
    
    /**
     * 
     */
    var $canEdit = 'no_one';
    
    
    /**
     * 
     */
    var $canDelete = 'no_one';


    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'cms/tpl/SingleLayoutArticles.shtml';


    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'level,title,state,modifiedOn,modifiedBy';
    
    
    /**
     * В коя колонка да са инструментите за реда
     */
    var $rowToolsField = 'level';
    
    
    /**
     * По кои полета да се прави пълнотекстово търсене
     */
    var $searchFields = 'title,body';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'cms,admin,ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,admin,cms';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,admin,cms';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'cms,admin,ceo';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('level', 'order', 'caption=Номер,mandatory');
        $this->FLD('menuId', 'key(mvc=cms_Content,select=menu)', 'caption=Меню,mandatory,silent,autoFilter');
        $this->FLD('title', 'varchar', 'caption=Заглавие,mandatory,width=100%');
        $this->FLD('body', 'richtext(bucket=Notes)', 'caption=Текст,column=none');

        $this->setDbUnique('menuId,level');
    }


    /**
     * Изпълнява се след подготовката на формата за филтриране
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {
        $form = $data->listFilter;
        
        // В хоризонтален вид
        $form->view = 'horizontal';
        
        // Добавяме бутон
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $form->showFields = 'search, menuId';
        
        $form->input('search, menuId', 'silent');

        $form->setOptions('menuId', $opt = self::getMenuOpt());

        if(!$opt[$form->rec->menuId]) {
            $form->rec->menuId = key($opt);
        }

        $data->query->where(array("#menuId = '[#1#]'", $form->rec->menuId));
    }


    /**
     * Връща възможните опции за менюто, в което може да се намира дадената статия, 
     * в зависимост от езика
     */
    static function getMenuOpt($lang = NULL)
    {
        if(!$lang) {
            $lang = cms_Content::getLang();
        }
        
        $cQuery = cms_Content::getQuery();
 
        $cQuery->where("#lang = '{$lang}'");
         
        $cQuery->orderBy('#menu');
        
        $options = array();
        
        while($cRec = $cQuery->fetch(array("#source = [#1#]" , self::getClassId()))) {
            $options[$cRec->id] = $cRec->menu;
        }

        return $options;
    }


    /**
     *  Задава подредбата
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        $data->query->orderBy('#menuId,#level');
    }


    /**
     * Подготвя някои полета на формата
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        if($menuId = $data->form->rec->menuId) {
            $lang = cms_Content::fetchField($menuId, 'lang');
        }
        
        $data->form->setOptions('menuId', self::getMenuOpt($lang));
    }


    /**
     * Изпълнява се след преобразуването към вербални стойности на полетата на записа
     */
    function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    { 
        if(trim($rec->body) && $fields['-list']) {
            $row->title = ht::createLink($row->title, toUrl(self::getUrl($rec)), NULL, 'ef_icon=img/16/monitor.png');
        }
        
        // Ако се намираме в режим "печат", не показваме инструментите на реда
        if(Mode::is('printing')) return;
        
        // Ако листваме
        if(!arr::haveSection($fields, '-list')) return;
        
        // Определяме в кое поле ще показваме инструментите
        $field = $mvc->rowToolsField ? $mvc->rowToolsField : 'id';
        
        // Ако полето е обект
        if (is_object($row->$field)) {
            
            // Добавяме линк, който води до промяна на записа
            $row->$field->append($mvc->getChangeLink($rec->id), 'TOOLS');
        }
    }


    /**
     * Екшън за разглеждане на статия
     */
    function act_Article()
    {   
        Mode::set('wrapper', 'cms_Page');
        
        $conf = core_Packs::getConfig('cms');
		$ThemeClass = cls::get($conf->CMS_THEME);
        
		if(Mode::is('screenMode', 'narrow')) {
            Mode::set('cmsLayout', $ThemeClass->getNarrowArticleLayout());
        } else {
            Mode::set('cmsLayout', $ThemeClass->getArticleLayout());
        }
		
        $id = Request::get('id', 'int'); 
         
        if(!$id || !is_numeric($id)) { 
            $menuId =  Mode::get('cMenuId');

            if(!$menuId) {
                $menuId = Request::get('menuId', 'int');
            }
            if(!$menuId) {
                return new Redirect(array('Index'));
            }
        } else {
            // Ако има, намира записа на страницата
            $rec = self::fetch($id);
        }
       
        if($rec) { 

            $menuId = $rec->menuId;

            $lArr = explode('.', self::getVerbal($rec, 'level'));
            
            $content = new ET('[#1#]', $desc = self::getVerbal($rec, 'body'));
           
            $ptitle = self::getVerbal($rec, 'title') . " » ";
 
            $content->prepend($ptitle, 'PAGE_TITLE');
            
        	// Подготвяме информаията за ографа на статията
            $ogp = $this->prepareOgraph($rec);
        } 
        
        Mode::set('SOC_TITLE', $ogp->siteInfo['Title']);
        Mode::set('SOC_SUMMARY', $ogp->siteInfo['Description']);

        if(!$content) $content = new ET(); 

        // Подготвя навигацията
        $query = self::getQuery();
        
        if($menuId) {
            $query->where("#menuId = {$menuId}");
        }

        $query->orderBy("#level");

        $navData = new stdClass();
        
        $cnt = 0;

        while($rec1 = $query->fetch()) {
            
            // Ако статуса е затворе, да не се показва
            if ($rec1->state == 'closed') continue;
            
            $cnt++;
            
            $lArr1 = explode('.', self::getVerbal($rec1, 'level'));

            if($lArr) {
                if($lArr1[2] && (($lArr[0] != $lArr1[0]) || ($lArr[1] != $lArr1[1]))) continue;
            }

            $title = self::getVerbal($rec1, 'title');
            

            if(!$rec && $rec1->body) {

                // Това е първата срещната статия

                $id = $rec1->id;

                $rec = self::fetch($id);

                $menuId = $rec->menuId;

                $lArr = explode('.', self::getVerbal($rec, 'level'));

                $content = new ET('[#1#]', $desc = self::getVerbal($rec, 'body'));

                $ptitle   = self::getVerbal($rec, 'title') . " » ";

                $content->prepend($ptitle, 'PAGE_TITLE');
                
            }

            $l = new stdClass();

            $l->selected = ($rec->id == $rec1->id);

            if($lArr1[2]) {
                $l->level = 3;
            } elseif($lArr1[1]) {
                $l->level = 2;
            } elseif($lArr1[0]) {
                $l->level = 1;
            }

            if(trim($rec1->body)) {
                $l->url = self::getUrl($rec1);
            } 
            
            $l->title = $title;
            
            // Вземаме линка за промяна на записа
            $l->editLink = $this->getChangeLink($rec1->id);

            $navData->links[] = $l;

        }
        
        if(self::haveRightFor('add')) {
            $navData->addLink = ht::createLink( tr('+ добави страница'), array('cms_Articles', 'Add', 'menuId' => $menuId, 'ret_url' => array('cms_Articles', 'Article', 'menuId' => $menuId)));
        }
		
        Mode::set('cMenuId', $menuId);

        if($menuId) {
            cms_Content::setLang(cms_Content::fetchField($menuId, 'lang'));
        }
 
        if($cnt + Mode::is('screenMode', 'wide') > 1) {
            $content->append($this->renderNavigation($navData), 'NAVIGATION');
        }
        
        $richText = cls::get('type_RichText');
        $desc = ht::escapeAttr(str::truncate(ht::extractText($desc), 200, FALSE));

        $content->replace($desc, 'META_DESCRIPTION');

        if($ogp){
        	
        	  // Генерираме ограф мета таговете
        	  $ogpHtml = ograph_Factory::generateOgraph($ogp);
        	  $content->append($ogpHtml);
        }
        
        if($rec) {
            if(core_Packs::fetch("#name = 'vislog'")) {
                vislog_History::add($rec->title);
            }
 
            // Добавя канонично URL
            $url = toUrl(self::getUrl($rec, TRUE), 'absolute');
            $content->append("\n<link rel=\"canonical\" href=\"{$url}\"/>", 'HEAD');
        }
        
        // Страницата да се кешира в браузъра за 1 час
        Mode::set('BrowserCacheExpires', $conf->CMS_BROWSER_CACHE_EXPIRES);
        

        return $content; 
    }


    /**
     * $data->items = $array( $rec{$level, $title, $url, $isSelected, $icon, $editLink} )
     * $data->new = {$caption, $url}
     * 
     */
    function renderNavigation_($data)
    {
        $navTpl = new ET();

        foreach($data->links as $l) {
            $selected = ($l->selected) ? $sel = 'sel_page' : '';
            $navTpl->append("<div class='nav_item level{$l->level} $selected'>");
            if($l->url) {
                $navTpl->append(ht::createLink($l->title, $l->url));
            } else {
                $navTpl->append($l->title);
            }

            if($l->editLink) {
                // Добавяме интервал
                $navTpl->append('&nbsp;');
                
                // Добавяме линка
                $navTpl->append($l->editLink);

            }
            $navTpl->append("</div>");
        }

        if($data->addLink) {
            $navTpl->append( "<div style='padding:2px; border:solid 1px #ccc; background-color:#eee; margin-top:10px;font-size:0.7em'>");
            $navTpl->append($data->addLink);
            $navTpl->append( "</div>");
        }

        return $navTpl;
    }
    
    
    /**
     * Създава линк, който води до промяната на записа
     * 
     * @param object $mvc
     * @param string $res
     * @param integer $id
     * @param string $title - Ако е подаден, връща линк с иконата и титлата. Ако липсва, връща само линк с иконата.
     * 
     * @return core_Et - Линк за редирект
     */
    static function getChangeLink($id, $title=FALSE)
    {
        // Ако нямаме права да редактираме, да не се показва линка
        if (!static::haveRightFor('changerec', $id)) return ;
        
        // URL' то за промяна
        $changeUrl = array('cms_Articles', 'changefields', $id, 'ret_url' => TRUE);
        
        // Иконата за промяна
        $editSbf = sbf("img/16/edit.png");
        
        // Ако е подаде заглавието
        if ($title) {
            
            // Създаваме линк с загллавието
            $attr['class'] = 'linkWithIcon';
            $attr['style'] = 'background-image:url(' . $editSbf . ');';
            
            $link = ht::createLink($title, $changeUrl, NULL, $attr); 
        } else {
            
            // Ако не е подадено заглавиет, създаваме линк с иконата
            $link = ht::createLink('<img src=' . $editSbf . ' width="12" height="12">', $changeUrl);
        }
        
        return $link;
    }
    
    
    /**
     * Подготвя Информацията за генериране на Ографа
     * @param stdClass $rec 
     * @return stdClass $ogp
     */
    function prepareOgraph($rec)
    {
    	$ogp = new stdClass();
    	$conf = core_Packs::getConfig('cms');
    	
    	// Добавяме изображението за ографа ако то е дефинирано от потребителя
        if($conf->CMS_OGRAPH_IMAGE != '') {
        	
	        $file = fileman_Files::fetchByFh($conf->CMS_OGRAPH_IMAGE);
	        $type = fileman_Files::getExt($file->name);
	        
	        $attr = array('isAbsolute' => TRUE, 'qt' => '');
        	$size = array(200, 'max'=>TRUE);
	        $imageURL = thumbnail_Thumbnail::getLink($file->fileHnd, $size, $attr);
	    	$ogp->imageInfo = array('url'=> $imageURL,
	    						    'type'=> "image/{$type}",
	    						 	);
        }
        				 
    	$richText = cls::get('type_RichText');
    	$desc = ht::extractText($richText->toHtml($rec->body));
    		
    	// Ако преглеждаме единична статия зареждаме и нейния Ograph
	    $ogp->siteInfo = array('Locale' =>'bg_BG',
	    				  'SiteName' =>'bgerp.com',
	    	              'Title' => self::getVerbal($rec, 'title'),
	    	              'Description' => $desc,
	    	              'Type' =>'article',
	    				  'Url' => toUrl(self::getUrl($rec, TRUE), 'absolute'),
	    				  'Determiner' =>'the',);
	        
	    // Създаваме Open Graph Article  обект
	    $ogp->recInfo = array('published' => $rec->createdOn);
	    
    	return $ogp;
    }


    /**
     *
     */
    static function on_AfterGetRequiredRoles($mvc, &$roles, $action, $rec = NULL, $userId = NULL)
    {
        if($rec->state == 'active' && $action == 'delete') {
            $roles = 'no_one';
        }
    }



    /**********************************************************************************************************
     *
     * Интерфейс cms_SourceIntf
     *
     **********************************************************************************************************/


    /**
     * Връща URL към публичната част (витрината), отговаряща на посоченото меню
     */
    function getUrlByMenuId($menuId)
    {
        $query = self::getQuery();
        $query->where("#menuId = {$menuId}");
        $query->orderBy("#level");

        $rec = $query->fetch("#menuId = {$menuId} AND #body != ''");

        if($rec) {

            return self::getUrl($rec); 
        } else {

            return NULL ;
        }
    }


    /**
     * Връща URL към посочената статия
     */
    static function getUrl($rec, $canonical = FALSE)
    {
        expect($rec->menuId, $rec);

        $lg = cms_Content::fetchField($rec->menuId, 'lang');
        
        $lg{0} = strtoupper($lg{0});

        $res = array($lg, $rec->vid ? $rec->vid : $rec->id, 'PU' => (haveRole('powerUser') && !$canonical) ? 1 : NULL);

        return $res;
    }


    /**
     * Връща URL към вътрешната част (работилницата), отговарящо на посочената точка в менюто
     */
    function getWorkshopUrl($menuId)
    {
        $url = array('cms_Articles', 'list', 'menuId' => $menuId);
 
        return $url;
    }

    
    /**
     * След подготвяне на сингъла, добавяме и лога с промените
     */
    function on_AfterPrepareSingle($mvc, $res, $data)
    {
        // Инстанция на класа
        $inst = cls::get('core_TableView');
        
        // Вземаме таблицата с попълнени данни
        $data->row->CHANGE_LOG = $inst->get(change_Log::prepareLogRow($mvc->className, $data->rec->id), 'createdOn=Дата, createdBy=От, Version=Версия');
    }
}
