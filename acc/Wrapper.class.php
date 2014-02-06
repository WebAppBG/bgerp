<?php



/**
 * Клас 'acc_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'Acc'
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class acc_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
       
        
        $this->TAB('acc_Balances', 'Оборотни ведомости', 'ceo,acc');
        $this->TAB('acc_Articles', 'Мемориални Ордери', 'acc,ceo');
        $this->TAB('acc_Journal', 'Журнал', 'ceo,acc');
        $this->TAB('acc_Invoices', 'Фактури', 'ceo,acc');
        
        $this->title = 'Книги « Счетоводство';
        Mode::set('menuPage','Счетоводство:Книги');
    }
}