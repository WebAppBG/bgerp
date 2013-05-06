<?php



/**
 * Плъгин за Регистрите, който им добавя възможност обекти от регистрите да влизат като пера
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_plg_Registry extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
     */
    function on_AfterDescription(core_Mvc $mvc)
    {
        $mvc->declareInterface('acc_RegisterIntf');
        
        // Подсигуряваме, че първичния ключ на регистъра-приемник ще се запомни преди изтриване
        $mvc->fetchFieldsBeforeDelete = arr::make($mvc->fetchFieldsBeforeDelete, TRUE);
        $mvc->fetchFieldsBeforeDelete['id'] = 'id';
        
     /*   if (static::supportExtenders($mvc)) {
            // Динамично прикачане на екстендера acc_Items към регистровия мениджър.
            $mvc->addExtender('lists', 
                array(
                    'className' => 'acc_Items',
                    'prefix'    => 'ObjectLists',
                    'title'     => 'Номенклатура',
                )                
            );
        } elseif ($mvc instanceof core_Master) {
            $mvc->attachDetails('ObjectLists=acc_Items');
        } */
    }


    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    function on_AfterPrepareEditForm($mvc, $data)
    {
        if (static::supportExtenders($mvc) || static::hasDetail($mvc, 'ObjectLists')) {
            return;
        }
        
        if ($suggestions = static::getSelectableLists($mvc)) {
            $data->form->FNC('lists', 'keylist(mvc=acc_Lists,select=name,maxColumns=1)', 'caption=Номенклатури->Избор,input,remember');
            $data->form->setSuggestions('lists', $suggestions);
    
            if ($data->form->rec->id) {
                $data->form->setDefault('lists',
                    keylist::fromArray(acc_Lists::getItemLists($mvc, $data->form->rec->id)));
            }
        }
    }
    

    /**
     * След промяна на обект от регистър
     *
     * Нотифицира номенклатурите за промяната.
     *
     * @param core_Manager $mvc
     * @param int $id
     * @param stdClass $rec
     */
    function on_AfterSave($mvc, &$id, &$rec, $fieldList = NULL)
    {
        if (static::supportExtenders($mvc)) {
            return;
        }
        
        if (!empty($mvc->autoList)) {
            // Автоматично добавяне към номенклатурата $autoList
            expect($autoListId = acc_Lists::fetchField(array("#systemId = '[#1#]'", $mvc->autoList), 'id'));
            $rec->lists = keylist::addKey($rec->lists, $autoListId);
        }
        $fieldListArr = arr::make($fieldList, TRUE);
    
        if(empty($fieldList) || $fieldListArr['lists']) {
            acc_Lists::updateItem($mvc, $rec->id, $rec->lists);
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function on_AfterDelete($mvc, &$res, $query)
    {
        foreach ($query->getDeletedRecs() as $rec) {
            acc_Lists::updateItem($mvc, $rec->id, NULL);
        }
    }
    
    
    /**
     * Допустимите номенклатури минус евентуално $autoList номенклатурата.
     *
     * @param core_Mvc $mvc
     * @return array
     */
    private static function getSelectableLists($mvc)
    {
        if ($suggestions = acc_Lists::getPossibleLists($mvc)) {
            if (!empty($mvc->autoList)) {
                $autoListId = acc_Lists::fetchField(array("#systemId = '[#1#]'", $mvc->autoList), 'id');
                
                if (isset($suggestions[$autoListId])) {
                    unset($suggestions[$autoListId]);
                }
            }
        }
        
        return $suggestions;
    }
    
    
    protected static function supportExtenders($mvc)
    {
        return isset($mvc->_plugins['groups_Extendable']);
    }
    
    
    protected static function hasDetail($mvc, $detailAlias, $detailName = NULL)
    {
        return $mvc instanceof core_Master && $mvc->hasDetail($detailAlias, $detailName);
    }
}
