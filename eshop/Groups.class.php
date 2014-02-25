<?php



/**
 * Мениджър на групи с продукти.
 *
 *
 * @category  bgerp
 * @package   eshop
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class eshop_Groups extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Групи в онлайн магазина";
    
    
    /**
     * @todo Чака за документация...
     */
    var $pageMenu = "Каталог";
    

    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'cms_SourceIntf';


    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, eshop_Wrapper, plg_State2, cms_VerbalIdPlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,name,menuId,state';
    
    
    /**
     * Полета по които се прави пълнотекстово търсене от плъгина plg_Search
     */
    var $searchFields = 'name';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Група";
    
    
    /**
     * Икона за единичен изглед
     */
    var $singleIcon = 'img/16/category-icon.png';

    
    /**
     * Кой може да чете
     */
    var $canRead = 'eshop,ceo';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    var $canEditsysdata = 'eshop,ceo';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'eshop,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'eshop,ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'eshop,ceo';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'eshop,ceo';
	
    
    /**
     * Кой може да качва файлове
     */
    var $canWrite = 'eshop,ceo';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'eshop,ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';


    /**
     * Нов темплейт за показване
     */
   // var $singleLayoutFile = 'cat/tpl/SingleGroup.shtml';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Група, mandatory,width=100%');
        $this->FLD('info', 'richtext(bucket=Notes)', 'caption=Описание');
        $this->FLD('icon', 'fileman_FileType(bucket=eshopImages)', 'caption=Картинка->Малка');
        $this->FLD('image', 'fileman_FileType(bucket=eshopImages)', 'caption=Картинка->Голяма');
        $this->FLD('productCnt', 'int', 'input=none');
        $this->FLD('menuId', 'key(mvc=cms_Content,select=menu)', 'caption=Меню');
    }


    /**
     * Изпълнява се след подготовката на формата за единичен запис
     */
    function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $cQuery = cms_Content::getQuery();
        
        $classId = core_Classes::fetchIdByName($mvc->className);
        while($rec = $cQuery->fetch("#source = {$classId} AND state = 'active'")) {
            $opt[$rec->id] = cms_Content::getVerbal($rec, 'menu');
        }

        $data->form->setOptions('menuId', $opt);
    }


    /**
     * Изпълнява се след подготовката на вербалните стойности за всеки запис
     */
    function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    {
        if($fields['-list']) {
            $row->name = ht::createLink($row->name, self::getUrl($rec), NULL, 'ef_icon=img/16/monitor.png');
        }
    }


    /**
     * Показва списъка с всички групи
     */
    function act_ShowAll()
    {
        $data = new stdClass();
        $data->menuId = Request::get('cMenuId', 'int');
        
        if(!$data->menuId) {
            $lg = cms_Content::getLang();
            $clsId = core_Classes::getId($this);
            expect($lg, $clsId);
            $data->menuId = cms_Content::fetchField("#lang = '{$lg}' AND #source = $clsId");
        }

        cms_Content::setCurrent($data->menuId);

        $this->prepareNavigation($data);   
        $this->prepareAllGroups($data);  

        $layout = $this->getLayout();
        $layout->append(cms_Articles::renderNavigation($data), 'NAVIGATION');
        $layout->append($this->renderAllGroups($data), 'PAGE_CONTENT');
        
         // Добавя канонично URL
        $url = toUrl(array($this, 'ShowAll', 'cMenuId' => $data->menuId), 'absolute');
        $layout->append("\n<link rel=\"canonical\" href=\"{$url}\"/>", 'HEAD');
       
        // Страницата да се кешира в браузъра
        $conf = core_Packs::getConfig('eshop');
        Mode::set('BrowserCacheExpires', $conf->ESHOP_BROWSER_CACHE_EXPIRES);

        return $layout;
    }


    /**
     * Екшън за единичен изглед на групата във витрината
     */
    function act_Show()
    {
        $data = new stdClass();

        $data->groupId = Request::get('id', 'int');
        if(!$data->groupId) {

            return $this->act_ShowAll();
        }
        expect($groupRec = self::fetch($data->groupId));
        cms_Content::setCurrent($groupRec->menuId);
        
        $this->prepareGroup($data);
        $this->prepareNavigation($data);

        $layout = $this->getLayout();
        $layout->append(cms_Articles::renderNavigation($data), 'NAVIGATION');
        $layout->append($this->renderGroup($data), 'PAGE_CONTENT');
        
        // Добавя канонично URL
        $url = toUrl(self::getUrl($data->rec, TRUE), 'absolute');
        $layout->append("\n<link rel=\"canonical\" href=\"{$url}\"/>", 'HEAD');

        // Страницата да се кешира в браузъра
        $conf = core_Packs::getConfig('eshop');
        Mode::set('BrowserCacheExpires', $conf->ESHOP_BROWSER_CACHE_EXPIRES);

        return $layout;
    }


    /**
     * Подготвя данните за показването на страницата със всички групи
     */
    function prepareAllGroups($data)
    {
        $query = self::getQuery();
        $query->where("#state = 'active' AND #menuId = {$data->menuId}");  
        
        while($rec = $query->fetch()) {
            $rec->url = self::getUrl($rec);
            $data->recs[] = $rec;
        }

        $cRec = cms_Content::fetch($data->menuId);

        $data->title = type_Varchar::escape($cRec->url);
    }


    /**
     * Подготвя данните за показването на една група
     */
    function prepareGroup_($data)
    {    
        expect($rec = $data->rec = $this->fetch($data->groupId), $data);
        
        $rec->menuId = $rec->menuId;

        $row = $data->row = new stdClass();

        $row->name = $this->getVerbal($rec, 'name');

        if($rec->image) {
            $row->image = fancybox_Fancybox::getImage($rec->image, array(620, 620), array(1200, 1200), $row->name); 
        }

        $row->description = $this->getVerbal($rec, 'info');
        
        $data->products = new stdClass();
        $data->products->groupId = $data->groupId;
         
        eshop_Products::prepareGroupList($data->products);
    }


    /**
     * Добавя бутони за разглеждане във витрината на групите с продукти
     */
    function on_AfterPrepareListToolbar($mvc, $data)
    {   
        $cQuery = cms_Content::getQuery();
        
        $classId = core_Classes::fetchIdByName($mvc->className);
        while($rec = $cQuery->fetch("#source = {$classId} AND state = 'active'")) {
            $data->toolbar->addBtn( type_Varchar::escape($rec->menu), 
                array('eshop_Groups', 'ShowAll', 'cMenuId' =>  $rec->id, 'PU' => 1));
        }
    }

    /**
     *
     */
    function renderAllGroups_($data)
    {   
        $all = new ET("<h1>{$data->title}</h1>");
        
        if(is_array($data->recs)) {
            foreach($data->recs as $rec) {
                $tpl = new ET(getFileContent('eshop/tpl/GroupButton.shtml'));
                if($rec->icon) {
                    $img = new img_Thumb($rec->icon, 500, 180, 'fileman');
                    $tpl->replace(ht::createLink($img->createImg(), $rec->url), 'img');
                }
                $name = ht::createLink($this->getVerbal($rec, 'name'), $rec->url);
                $tpl->replace($name, 'name');
                $all->append($tpl);
            }
        }

        $all->prepend(tr('Всички продукти') . ' « ', 'PAGE_TITLE');

        return $all;
    }


    /**
     *
     */
    function renderGroup_($data)
    {
        $groupTpl = new ET(getFileContent("eshop/tpl/SingleGroupShow.shtml"));
        $groupTpl->setRemovableBlocks(array('PRODUCT'));
        $groupTpl->placeArray($data->row);
        $groupTpl->append(eshop_Products::renderGroupList($data->products), 'PRODUCTS');

        $groupTpl->prepend($data->row->name . ' « ', 'PAGE_TITLE');


        return $groupTpl;
    }


    /**
     * Връща лейаута за единичния изглед на групата
     */
    static function getLayout()
    {
        Mode::set('wrapper', 'cms_Page');
        

		if(Mode::is('screenMode', 'narrow')) {
            $layout = "eshop/tpl/ProductGroupsNarrow.shtml";
        } else {
            $layout =  "eshop/tpl/ProductGroups.shtml";
        }
        
        Mode::set('cmsLayout',  $layout);
        

        return new ET();
    }
    

    /**
     * Подготвя данните за навигацията
     */
    function prepareNavigation_($data)
    {
        $query = $this->getQuery(); 
        
        $query->where("#state = 'active'");
        
        $groupId   = $data->groupId;
        $productId = $data->productId;
        $menuId = $data->menuId;

        if($productId) {
            $pRec = eshop_Products::fetch("#id = {$productId} AND #state = 'active'");
            $groupId = $pRec->groupId;
        }
        
        if($groupId) {
            $menuId = self::fetch($groupId)->menuId;
        }

        $query->where("#menuId = {$menuId}");
 
        $l = new stdClass();
        $l->selected = ($groupId == NULL &&  $productId == NULL);
        $l->url = array('eshop_Groups', 'ShowAll', 'cMenuId' => $menuId, 'PU' => haveRole('powerUser') ? 1 : NULL);
        $l->title = tr('Всички продукти');;
        $l->level = 1;
        $data->links[] = $l;

        $editSbf = sbf("img/16/edit.png", '');
        $editImg = ht::createElement('img', array('src' => $editSbf, 'width' => 16, 'height' => 16));
 
        while($rec = $query->fetch()) {
            $l = new stdClass();
            $l->url = self::getUrl($rec);
            $l->title  = $this->getVerbal($rec, 'name');
            $l->level = 2;
            $l->selected = ($groupId == $rec->id);
            
            if($this->haveRightFor('edit', $rec)) {
                $l->editLink = ht::createLink($editImg, array('eshop_Groups', 'edit', $rec->id, 'ret_url' => TRUE));
            }
            
            $data->links[] = $l;
        }
    }


    /**
     * Връща каноничното URL на статията за външния изглед
     */
    static function getUrl($rec, $canonical = FALSE)
    {
        $mRec = cms_Content::fetch($rec->menuId);
        
        $lg = $mRec->lang;

        $lg{0} = strtoupper($lg{0});

        $url = array('A', 'g', $rec->vid ? $rec->vid : $rec->id, 'PU' => (haveRole('powerUser') && !$canonical) ? 1 : NULL);
        
        return $url;
    }


    // Интерфейс cms_SourceIntf
     

    /**
     * Връща URL към себе си  
     */
    function getUrlByMenuId($cMenuId)
    {   
        $url = array('eshop_Groups', 'ShowAll', 'cMenuId' => $cMenuId);
        
        return $url;
    }


    /**
     * Връща URL към съдържание в публичната част, което отговаря на посочения запис
     */
    function getUrlByRec($rec)
    {
        $url = self::getUrl($rec);

        return $url;
    }


    /**
     * Връща URL към вътрешната част (работилницата), отговарящо на посочената точка в менюто
     */
    function getWorkshopUrl($menuId)
    {
        $url = array('eshop_Groups', 'list');

        return $url;
    }

    
}
