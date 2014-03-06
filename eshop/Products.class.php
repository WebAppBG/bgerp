<?php



/**
 * Мениджър на групи с продукти.
 *
 *
 * @category  bgerp
 * @package   eshop
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class eshop_Products extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Продукти в онлайн магазина";
    
    
    /**
     * @todo Чака за документация...
     */
    var $pageMenu = "Е-Магазин";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, eshop_Wrapper, plg_State2, cms_VerbalIdPlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,name,groupId,state';
    
    
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
    var $singleTitle = "Продукт";
    
    
    /**
     * Икона за единичен изглед
     */
    var $singleIcon = 'img/16/wooden-box.png';

    
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
    //var $singleLayoutFile = 'cat/tpl/SingleGroup.shtml';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('code', 'varchar(10)', 'caption=Код');
        $this->FLD('groupId', 'key(mvc=eshop_Groups,select=name)', 'caption=Група, mandatory, silent');
        $this->FLD('name', 'varchar(64)', 'caption=Продукт, mandatory,width=100%');
        $this->FLD('image', 'fileman_FileType(bucket=eshopImages)', 'caption=Илюстрация1');
        
        $this->FLD('image2', 'fileman_FileType(bucket=eshopImages)', 'caption=Илюстрация2,column=none');
        $this->FLD('image3', 'fileman_FileType(bucket=eshopImages)', 'caption=Илюстрация3,column=none');

        $this->FLD('info', 'richtext(bucket=Notes,rows=5)', 'caption=Описание->Кратко');
        $this->FLD('longInfo', 'richtext(bucket=Notes,rows=5)', 'caption=Описание->Разширено');

        // Запитване за нестандартен продукт
        $this->FLD('coDriver', 'class(interface=techno_ProductsIntf,allowEmpty)', 'caption=Запитване->Драйвер');
        $this->FLD('coParams', 'text(rows=5)', 'caption=Запитване->Параметри,width=100%');
        $this->FLD('coMoq', 'varchar', 'caption=Запитване->МКП,hint=Минимално количество за поръчка');

		$this->setDbUnique('code');
    }


    /**
     * $data->rec, $data->row
     */
    function prepareGroupList_($data)
    {
        $data->row = $this->recToVerbal($data->rec);
    }


    /**
     *
     */
    function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    {
        if($rec->code) {
            $row->code      = "<span>" . tr('Код') . ": <b>{$row->code}</b></span>";
        }
 
        if($rec->coMoq) {
            $title = tr('Минимално Количество за Поръчка');
            $row->coMoq = "<span title=\"{$title}\">" . tr('МКП') . ": <b>{$row->coMoq}</b></span>";
        }

        if($rec->coDriver) {
            if(sales_Inquiries::haveRightFor('new')){
            	
            	$title = tr('Изпратете запитване за производство');
            	Request::setProtected('drvId,coParams');
            	
            	$row->coInquiry = ht::createLink(tr('Запитване'), array('sales_Inquiries', 'new', 'drvId' => $rec->coDriver, 'coParams' => $rec->coParams, 'ret_url' => TRUE), NULL, "ef_icon=img/16/button-question-icon.png,title={$title}");
            }
        }

        if($fields['-list']) {
            $row->name = ht::createLink($row->name, self::getUrl($rec), NULL, 'ef_icon=img/16/monitor.png');
        }

    }


    /**
     *
     */
    static function prepareGroupList($data)
    {
        $pQuery = self::getQuery();
        $pQuery->orderBy("#code");
        while($pRec = $pQuery->fetch("#state = 'active' AND #groupId = {$data->groupId}")) {
            $data->recs[] = $pRec;
            $pRow = $data->rows[] = self::recToVerbal($pRec, 'name,info,image,code,coMoq');
            $img = new img_Thumb($pRec->image, 120, 120);
            $pRow->image = $img->createImg(array('class' => 'eshop-product-image'));
            if(self::haveRightFor('edit', $pRec)) {
                $pRec->editUrl = array('eshop_Products', 'edit', $pRec->id, 'ret_url' => TRUE);
            }
        }

        // URL за добавяне на продукт
        if(self::haveRightFor('add')) {
            $data->addUrl = array('eshop_Products', 'add', 'groupId' => $data->groupId, 'ret_url' => TRUE);
        }
    }


    /**
     *
     * @return $tpl
     */
    function renderGroupList_($data)
    {   
        $layout = new ET();

        if(is_array($data->rows)) {
            $editSbf = sbf("img/16/edit.png", '');
            $editImg = ht::createElement('img', array('src' => $editSbf, 'width' => 16, 'height' => 16));
            foreach($data->rows as $id => $row) {
                
                $rec = $data->recs[$id];

                $pTpl = new ET(getFileContent('eshop/tpl/ProductListGroup.shtml'));

                if($rec->editUrl) {
                    $row->editLink = ht::createLink($editImg, $rec->editUrl);
                }
                
                $url = self::getUrl($rec);

                $row->name = ht::createLink($row->name, $url);
                $row->image = ht::createLink($row->image, $url);

                $pTpl->placeObject($row);

                $layout->append($pTpl);
            }
        }

        if($data->addUrl) {
            $layout->append(ht::createBtn('Нов продукт', $data->addUrl,  NULL, NULL, array('style' => 'margin-top:15px;')));
        }

        return $layout;
    }


    /**
     *
     */
    function act_Show()
    {
        $data = new stdClass();
        $data->productId = Request::get('id', 'int');
        $data->rec = self::fetch($data->productId);
        $data->groups = new stdClass();
        $data->groups->groupId = $data->rec->groupId;
        $data->groups->rec = eshop_Groups::fetch($data->groups->groupId);
        
        cms_Content::setCurrent($data->groups->rec->menuId);
        

        $this->prepareProduct($data);

        eshop_Groups::prepareNavigation($data->groups);
        
        $tpl = eshop_Groups::getLayout();
        $tpl->append(cms_Articles::renderNavigation($data->groups), 'NAVIGATION');
        
        $tpl->prepend($data->row->name . ' « ', 'PAGE_TITLE');

        $tpl->append($this->renderProduct($data), 'PAGE_CONTENT');
        
        // Добавя канонично URL
        $url = toUrl(self::getUrl($data->rec, TRUE), 'absolute');
        $tpl->append("\n<link rel=\"canonical\" href=\"{$url}\"/>", 'HEAD');

        
        // Страницата да се кешира в браузъра
        $conf = core_Packs::getConfig('eshop');
        Mode::set('BrowserCacheExpires', $conf->ESHOP_BROWSER_CACHE_EXPIRES);

        return $tpl;
    }


    /**
     * Подготовка на данните за рендиране на единичния изглед на продукт
     */
    function prepareProduct($data)
    {
        $data->rec->info = trim($data->rec->info);
        $data->rec->longInfo = trim($data->rec->longInfo);

        $data->row = $this->recToVerbal($data->rec);
        if($data->rec->image) {
            $data->row->image = fancybox_Fancybox::getImage($data->rec->image, array(120, 120), array(600, 600), $row->name); 
        }
        

        if($data->rec->image2) {
            $data->row->image2 = fancybox_Fancybox::getImage($data->rec->image2, array(120, 120), array(600, 600), $row->name . ' 2'); 
        }

        if($data->rec->image3) {
            $data->row->image3 = fancybox_Fancybox::getImage($data->rec->image3, array(120, 120), array(600, 600), $row->name3 . ' 3'); 
        }

        if($data->rec->image4) {
            $data->row->image4 = fancybox_Fancybox::getImage($data->rec->image4, array(120, 120), array(600, 600), $row->name4 . ' 4'); 
        }

        if($data->rec->image5) {
            $data->row->image5 = fancybox_Fancybox::getImage($data->rec->image5, array(120, 120), array(600, 600), $row->name5 . ' 5'); 
        }

        if(self::haveRightFor('edit', $data->rec)) {
            $editSbf = sbf("img/16/edit.png", '');
            $editImg = ht::createElement('img', array('src' => $editSbf, 'width' => 16, 'height' => 16));
            $data->row->editLink = ht::createLink($editImg, array('eshop_Products', 'edit', $data->rec->id, 'ret_url' => TRUE));
        }

    }


    /**
     *
     */
    function renderProduct($data)
    {
        $tpl = new ET(getFileContent("eshop/tpl/ProductShow.shtml"));
        
        if($data->row->editLink) { 
            $data->row->name .= '&nbsp;' . $data->row->editLink;
        }
        
        $tpl->placeObject($data->row);
    

        return $tpl;
    }

    
    /**
     * Връща каноничното URL на продукта за външния изглед
     */
    static function getUrl($rec, $canonical = FALSE)
    {   
        $gRec = eshop_Groups::fetch($rec->groupId);

        $mRec = cms_Content::fetch($gRec->menuId);
        
        $lg = $mRec->lang;

        $lg{0} = strtoupper($lg{0});

        $url = array('A', 'p', $rec->vid ? $rec->vid : $rec->id, 'PU' => (haveRole('powerUser') && !$canonical) ? 1 : NULL);
        
        return $url;
    }


}