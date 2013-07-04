<?php



/**
 * Клас 'cms_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'cms'
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_Wrapper extends plg_ProtoWrapper
{
    
    /**
     * Описание на табовете
     */
    function description()
    {
        $this->TAB('cms_Content', 'Съдържание', 'cms,ceo,admin');
        $this->TAB('cms_Articles', 'Статии', 'cms,ceo,admin');
        $this->TAB('cms_Objects', 'Обекти', 'cms,ceo,admin');
        $this->TAB('cms_Feeds', 'Хранилки', 'cms,ceo,admin');
        $this->TAB('cms_GalleryImages', 'Галерия', 'cms,ceo,admin');
    }
}