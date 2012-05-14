<?php

/**
 * Клас 'cat_products_Files' 
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
class cat_products_Files extends core_Detail
{
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'productId';
    
    
    /**
     * Заглавие
     */
    var $title = 'Файлове';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, file,description';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'cat_Wrapper, plg_RowTools';
    
    
    /**
     * Активния таб в случай, че wrapper-а е таб контрол.
     */
    var $tabName = 'cat_Products';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'input=hidden,silent');
        $this->FLD('file', 'fileman_FileType(bucket=productsFiles)', 'caption=Файл, notSorting');
        $this->FLD('description', 'varchar', 'caption=Описание,input');
    }
    
    
    /**
     * Създаваме кофа
     *
     * @param core_MVC $mvc
     * @param stdClass $res
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('productsFiles', 'Файлове към продукта', '', '100MB', 'user', 'every_one');
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, $data)
    {
        $data->toolbar->removeBtn('*');
        
        $data->toolbar->addBtn('Нов Файл',
            array(
                $mvc,
                'add',
                'productId'=>$data->masterId,
                'ret_url'=>TRUE
            ),
            array(
                'class' => 'btn-add'
            )
        );
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    static function on_AfterInputEditForm($mvc, $form)
    {
        $productRec = cat_Products::fetch($form->rec->productId);
        $productName = cat_Products::getVerbal($productRec, 'name');
        
        $form->title = "Файл към|* {$productName}";
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_AfterRenderDetail($mvc, &$tpl, $data)
    {
        $tpl = new ET("<br><div style='display:inline-block;margin-top:10px;'>
                       <div style='background-color:#ddd;border-top:solid 1px #999; border-left:solid 1px #999; border-right:solid 1px #999; padding:5px; font-size:1.2em;'><b>Файлове</b></div>
                       <div>[#1#]</div>
                       </div>", $tpl);
    }
}