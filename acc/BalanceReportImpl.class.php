<?php



/**
 * Имплементация на 'frame_ReportSourceIntf' за направата на справка на баланса
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_BalanceReportImpl extends frame_BaseDriver
{
    
	
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'ceo, acc';
    
    
    /**
     * Заглавие
     */
    public $title = 'Справка за оборотните ведомости';
    
    
    /**
     * Кои интерфейси имплементира
     */
    public $interfaces = 'frame_ReportSourceIntf';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 50;
    
    
    /**
     * Работен кеш
     */
    protected $cache = array();
    
    
    /**
     * Добавя полетата на вътрешния обект
     *
     * @param core_Fieldset $fieldset
     */
    public function addEmbeddedFields(core_Form &$form)
    {
    	$form->FLD('accountId', 'acc_type_Account(allowEmpty)', 'caption=Сметка,mandatory,silent', array('attr' => array('onchange' => "addCmdRefresh(this.form);this.form.submit()")));
    	$form->FLD('from', 'datetime', 'caption=От,mandatory');
    	$form->FLD('to', 'datetime', 'caption=До,mandatory');
    	$form->FLD("action", 'varchar', "caption=Действие,width=330px,silent,input=hidden", array('attr' => array('onchange' => "addCmdRefresh(this.form);this.form.submit()")));
    	$form->setOptions('action', array('' => '', 'filter' => 'Филтриране по пера', 'group' => 'Групиране по пера'));
    }
    
    
    /**
     * Подготвя формата за въвеждане на данни за вътрешния обект
     *
     * @param core_Form $form
     * @param string $documentType - (@see deals_DocumentTypes)
     */
    public function prepareEmbeddedForm(core_Form &$form, $documentType)
    {
    	// Ако е избрана сметка
    	if($form->rec->accountId){
    		$form->setField('action', 'input');
    		
    		if($form->rec->id){
    			
    			if(frame_Reports::fetchField($form->rec->id, 'filter')->accountId != $form->rec->accountId){
    				unset($form->rec->grouping1, $form->rec->grouping2, $form->rec->grouping3, $form->rec->feat1, $form->rec->feat2, $form->rec->feat3);
    				Request::push(array('grouping1' => NULL, 'grouping2' => NULL, 'grouping3' => NULL, 'feat1' => NULL, 'feat2' => NULL, 'feat3' => NULL));
    			}
    		}
    		
    		// Ако е избрано действие филтриране или групиране
    		if($form->rec->action){
    			$accInfo = acc_Accounts::getAccountInfo($form->rec->accountId);
    			 
    			// Показваме номенкалтурите на сметката като предложения за селектиране
    			$options = array();
    			 
    			if(count($accInfo->groups)){
    				foreach ($accInfo->groups as $i => $gr){
    					$options["ent{$i}Id"] .= $gr->rec->name;
    				}
    			}
    			
    			$Items = cls::get('acc_Items');
    			
    			// За всяка позиция показваме поле за избор на перо и свойство
    			foreach (range(1, 3) as $i){
    				if(isset($accInfo->groups[$i])){
    					$form->FLD("grouping{$i}", "key(mvc=acc_Items, allowEmpty)", "caption={$accInfo->groups[$i]->rec->name}->Перо");
    						
    					$items = $Items->makeArray4Select('title', "#lists LIKE '%|{$accInfo->groups[$i]->rec->id}|%'", 'id');
    					$form->setOptions("grouping{$i}", $items);
    						
    					if(count($items)){
    						$form->setOptions("grouping{$i}", $items);
    					} else {
    						$form->setReadOnly("grouping{$i}");
    					}
    						
    					$features = acc_Features::getFeatureOptions(array_keys($items));
    					$features = array('' => '') + $features + array('*' => '[По пера]');
    					$form->FLD("feat{$i}", 'varchar', "caption={$accInfo->groups[$i]->rec->name}->Свойство,width=330px,input");
    					$form->setOptions("feat{$i}", $features);
    				}
    			}
    		}
    	}
    }
    
    
    /**
     * Проверява въведените данни
     *
     * @param core_Form $form
     */
    public function checkEmbeddedForm(core_Form &$form)
    {
    	if($form->isSubmitted()){
    		if($form->rec->to < $form->rec->from){
    		     $form->setError('to, from', 'Началната дата трябва да е по малка от крайната');
    		}
    		
    		foreach (range(1, 3) as $i){
    			if($form->rec->{"grouping{$i}"} && $form->rec->{"feat{$i}"}){
    				$form->setError("grouping{$i},feat{$i}", "Не може да са избрани едновременно перо и свойтво за една позиция");
    			}
    		}
    	}
    }
    
    
    /**
     * Подготвя вътрешното състояние, на база въведените данни
     *
     * @param core_Form $innerForm
     */
    public function prepareInnerState()
    {
    	$data = new stdClass();
        $data->rec = $this->innerForm;
       
        $this->prepareListFields($data);
        
        $accSysId = acc_Accounts::fetchField($data->rec->accountId, 'systemId');
        $Balance = new acc_ActiveShortBalance(array('from' => $data->rec->from, 'to' => $data->rec->to));
        $data->recs = $Balance->getBalance($accSysId);
        if(count($data->recs)){
        	foreach ($data->recs as $rec){
        		foreach (range(1, 3) as $i){
        			if(!empty($rec->{"ent{$i}Id"})){
        				$this->cache[$rec->{"ent{$i}Id"}] = $rec->{"ent{$i}Id"};
        			}
        		}
        	}
        	
        	if(count($this->cache)){
	        	$iQuery = acc_Items::getQuery();
	            $iQuery->show("num");
	            $iQuery->in('id', $this->cache);
	            
	            while($iRec = $iQuery->fetch()){
	                $this->cache[$iRec->id] = $iRec->num;
	            }
        	}
        }
        
        
        $this->filterRecsByItems($data);
        
        return $data;
    }
    
    
    /**
     * След подготовката на показването на информацията
     */
    public function on_AfterPrepareEmbeddedData($mvc, &$res)
    {
    	// Подготвяме страницирането
    	$data = $res;
    	$Pager = cls::get('core_Pager', array('itemsPerPage' => $this->listItemsPerPage));
        $Pager->itemsCount = count($data->recs);
        $Pager->calc();
        $data->pager = $Pager;
        
        $start = $data->pager->rangeStart;
        $end = $data->pager->rangeEnd - 1;
        
        $data->summary = new stdClass();
        
        if(count($data->recs)){
            $count = 0;
            
            foreach ($data->recs as $id => $rec){
                
                // Показваме само тези редове, които са в диапазона на страницата
                if($count >= $start && $count <= $end){
                    $rec->id = $count + 1;
                    $row = $this->getVerbalDetail($rec);
                    $data->rows[$id] = $row;
                }
                
                // Сумираме всички суми и к-ва
                foreach (array('baseQuantity', 'baseAmount', 'debitAmount', 'debitQuantity', 'creditAmount', 'creditQuantity', 'blAmount', 'blQuantity') as $fld){
                    if(!is_null($rec->$fld)){
                        $data->summary->$fld += $rec->$fld;
                    }
                }
                
                $count++;
            }
        }
        
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        
        foreach ((array)$data->summary as $name => $num){
            $data->summary->$name  = $Double->toVerbal($num);
            if($num < 0){
            	$data->summary->$name  = "<span class='red'>{$data->summary->$name}</span>";
            }
        }
        
        $this->recToVerbal($data);
        
        $res = $data;
    }
    
    
    /**
     * Рендира вградения обект
     *
     * @param stdClass $data
     */
    public function renderEmbeddedData($data)
    {
    	if(empty($data)) return;
    	
    	$tpl = getTplFromFile('acc/tpl/ReportDetailedBalance.shtml');
    	
    	$this->prependStaticForm($tpl, 'FORM');
    	
    	$tpl->placeObject($data->row);
    	
    	$tableMvc = new core_Mvc;
    	$tableMvc->FLD('ent1Id', 'varchar', 'tdClass=itemClass');
    	$tableMvc->FLD('ent2Id', 'varchar', 'tdClass=itemClass');
    	$tableMvc->FLD('ent3Id', 'varchar', 'tdClass=itemClass');
    	$tableMvc->FLD('baseQuantity', 'int', 'tdClass=accCell');
    	$tableMvc->FLD('baseAmount', 'int', 'tdClass=accCell');
    	$tableMvc->FLD('debitQuantity', 'int', 'tdClass=accCell');
    	$tableMvc->FLD('debitAmount', 'int', 'tdClass=accCell');
    	$tableMvc->FLD('creditQuantity', 'int', 'tdClass=accCell');
    	$tableMvc->FLD('creditAmount', 'int', 'tdClass=accCell');
    	$tableMvc->FLD('blQuantity', 'int', 'tdClass=accCell');
    	$tableMvc->FLD('blAmount', 'int', 'tdClass=accCell');
    	
    	$table = cls::get('core_TableView', array('mvc' => $tableMvc));
    	
    	$tpl->append($table->get($data->rows, $data->listFields), 'DETAILS');
    	
    	$data->summary->colspan = count($data->listFields);
    	
    	if(!$data->bShowQuantities || $data->rec->action === 'group'){
    	     $data->summary->colspan -= 4;
    	     $beforeRow = new core_ET("<tr style = 'background-color: #eee'><td colspan=[#colspan#]><b>" . tr('ОБЩО') . "</b></td><td style='text-align:right'><b>[#baseAmount#]</b></td><td style='text-align:right'><b>[#debitAmount#]</b></td><td style='text-align:right'><b>[#creditAmount#]</b></td><td style='text-align:right'><b>[#blAmount#]</b></td></tr>");
    	} else{
    	    $data->summary->colspan -= 8;
    	    $beforeRow = new core_ET("<tr  style = 'background-color: #eee'><td colspan=[#colspan#]><b>" . tr('ОБЩО') . "</b></td><td style='text-align:right'><b>[#baseQuantity#]</b></td><td style='text-align:right'><b>[#baseAmount#]</b></td><td style='text-align:right'><b>[#debitQuantity#]</b></td><td style='text-align:right'><b>[#debitAmount#]</b></td><td style='text-align:right'><b>[#creditQuantity#]</b></td><td style='text-align:right'><b>[#creditAmount#]</b></td><td style='text-align:right'><b>[#blQuantity#]</b></td><td style='text-align:right'><b>[#blAmount#]</b></td></tr>");
    	}
    	
    	$beforeRow->placeObject($data->summary);
    	$tpl->append($beforeRow, 'ROW_BEFORE');
    	
    	if($data->pager){
    	     $tpl->append($data->pager->getHtml(), 'PAGER_BOTTOM');
    	     $tpl->append($data->pager->getHtml(), 'PAGER_TOP');
    	}
    	
    	return $tpl;
    }
    
    
    /**
     * Подготвя хедърите на заглавията на таблицата
     */
    private function prepareListFields(&$data)
    {
         $data->accInfo = acc_Accounts::getAccountInfo($data->rec->accountId);
    
         $bShowQuantities = ($data->accInfo->isDimensional === TRUE) ? TRUE : FALSE;
        
         
    	 $data->bShowQuantities = $bShowQuantities;
         
         $data->listFields = array();
    		
         foreach ($data->accInfo->groups as $i => $list) {
         	$data->listFields["ent{$i}Id"] = "|*" . acc_Lists::getVerbal($list->rec, 'name');
         }
    
    	 if($data->bShowQuantities) {
            $data->listFields += array(
                'baseQuantity' => 'Начално салдо->ДК->К-во',
                'baseAmount' => 'Начално салдо->ДК->Сума',
                'debitQuantity' => 'Обороти->Дебит->К-во',
                'debitAmount' => 'Обороти->Дебит->Сума',
                'creditQuantity' => 'Обороти->Кредит->К-во',
                'creditAmount' => 'Обороти->Кредит->Сума',
                'blQuantity' => 'Крайно салдо->ДК->К-во',
                'blAmount' => 'Крайно салдо->ДК->Сума', );
        } else {
            $data->listFields += array(
                'baseAmount' => 'Салдо->Начално',
                'debitAmount' => 'Обороти->Дебит',
                'creditAmount' => 'Обороти->Кредит',
                'blAmount' => 'Салдо->Крайно',
            );
        }
        
    }
    
    
   /**
    * Вербалното представяне на записа
    */
   private function recToVerbal($data)
   {
   		$data->row = new stdClass();
    	
        foreach (range(1, 3) as $i){
       		if(!empty($data->rec->{"ent{$i}Id"})){
       			$data->row->{"ent{$i}Id"} = "<b>" . acc_Lists::getVerbal($data->accInfo->groups[$i]->rec, 'name') . "</b>: ";
       			$data->row->{"ent{$i}Id"} .= acc_Items::fetchField($data->rec->{"ent{$i}Id"}, 'titleLink');
       		}
        }
       
        if(!empty($data->rec->action)){
        	$data->row->action = ($data->rec->action == 'filter') ? tr('Филтриране по') : tr('Групиране по');
        	$data->row->groupBy = '';
        	
        	$Varchar = cls::get('type_Varchar');
        	foreach (range(1, 3) as $i){
        		if(!empty($data->rec->{"grouping{$i}"})){
        			$data->row->groupBy .= acc_Items::getVerbal($data->rec->{"grouping{$i}"}, 'title') . ", ";
        		} elseif(!empty($data->rec->{"feat{$i}"})){
        			
        			$data->rec->{"feat{$i}"} = ($data->rec->{"feat{$i}"} == '*') ? "[По пера]" : $data->rec->{"feat{$i}"};
        			$data->row->groupBy .= $Varchar->toVerbal($data->rec->{"feat{$i}"}) . ", ";
        		}
        	}
        	
        	$data->row->groupBy = trim($data->row->groupBy, ', ');
        	
        	if($data->row->groupBy === ''){
        		unset($data->row->action);
        	}
        }
   }
     
     
     /**
      * Оставяме в записите само тези, които трябва да показваме
      */
     private function filterRecsByItems(&$data)
     {
     	$Balance = cls::get('acc_BalanceDetails');
     	
     	 if(!empty($data->rec->action)){
         	$cmd = ($data->rec->action == 'filter') ? 'default' : 'group';
         	$Balance->doGrouping($data, (array)$data->rec, $cmd, $data->recs);
         }
         
         
         $Balance->canonizeSortRecs($data, $this->cache);
      }
       
       
       /**
        * Вербалното представяне на ред от таблицата
        */
       private function getVerbalDetail($rec)
       {
           $Varchar = cls::get('type_Varchar');
           $Double = cls::get('type_Double');
           $Double->params['decimals'] = 2;
           $Int = cls::get('type_Int');
       
           $row = new stdClass();
           $row->id = $Int->toVerbal($rec->id);
       
           foreach (array('baseAmount', 'debitAmount', 'creditAmount', 'blAmount', 'baseQuantity', 'debitQuantity', 'creditQuantity', 'blQuantity') as $fld){
               $row->$fld = $Double->toVerbal($rec->$fld);
               $row->$fld = (($rec->$fld) < 0) ? "<span style='color:red'>{$row->$fld}</span>" : $row->$fld;
           }
       
           foreach (range(1, 3) as $i) {
           		if(isset($rec->{"grouping{$i}"})){
           			$row->{"ent{$i}Id"} = $rec->{"grouping{$i}"};
           
           			if($row->{"ent{$i}Id"} == 'others'){
           				$row->{"ent{$i}Id"} = "<i>" . tr('Други') . "</i>";
           			}
           		} else {
           			if(!empty($rec->{"ent{$i}Id"})){
           				$row->{"ent{$i}Id"} .= acc_Items::getVerbal($rec->{"ent{$i}Id"}, 'titleLink');
           			}
           		}
           }
       
           $row->ROW_ATTR['class'] = ($rec->id % 2 == 0) ? 'zebra0' : 'zebra1';
       
           return $row;
      }

      
	  /**
	   * Добавяме полета за търсене
	   * 
	   * @see frame_BaseDriver::alterSearchKeywords()
	   */
      public function alterSearchKeywords(&$searchKeywords)
      {
      	  if(!empty($this->innerForm)){
	      		$accVerbal = acc_Accounts::getVerbal($this->innerForm->accountId, 'title');
	      		$num = acc_Accounts::getVerbal($this->innerForm->accountId, 'num');
	      			
	      		$str = $accVerbal . " " . $num;
	      		$searchKeywords .= " " . plg_Search::normalizeText($str);
      	  }
      }
      
      
      /**
       * Скрива полетата, които потребител с ниски права не може да вижда
       *
       * @param stdClass $data
       */
      public function hidePriceFields()
      {
      		$innerState = &$this->innerState;
      		
      		unset($innerState->recs);
      }
      
      
      /**
       * Коя е най-ранната дата на която може да се активира документа
       */
      public function getEarlyActivation()
      {
      	  return $this->innerForm->to;
      }
}