<?php
class bank_DocumentWrapper extends bank_Wrapper
{
    function on_AfterRenderWrapping($mvc, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));
		
		$tabs->TAB('bank_IncomeDocument', 'Приходни банкови документи');
		$tabs->TAB('bank_CostDocument', 'Разходни банкови документи');
        $tabs->TAB('bank_InternalMoneyTransfer', 'Вътрешно парични трансфери');
        $tabs->TAB('bank_ExchangeDocument', 'Превалутиране');
        
        $tpl = $tabs->renderHtml($tpl, $mvc->className);
        $mvc->currentTab = 'Документи';
    }
}