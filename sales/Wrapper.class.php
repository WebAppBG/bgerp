<?php



/**
 * Покупки - опаковка
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_Wrapper extends core_Plugin
{
    
    
    /**
     * Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs');
        
        $tabs->TAB('sales_Deals', 'Сделки');
        $tabs->TAB('sales_Invoices', 'Фактури');
        
        $tpl = $tabs->renderHtml($tpl, $invoker->currentTab ? : $invoker->className);
        
        $tpl->prepend(tr($invoker->title) . " « ", 'PAGE_TITLE');
    }
}