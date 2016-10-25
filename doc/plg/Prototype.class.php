<?php



/**
 * Плъгин за добавяне на възможността документи да стават шаблони
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_plg_Prototype extends core_Plugin
{
	
	
	
	public static $unsetFields = array('id', 
									   'threadId', 
			                           'folderId', 
									   'state', 
							           'containerId', 
									   'createdBy', 
							           'createdOn', 
			                           'originId',
			                           'modifiedBy', 
			                           'modifiedOn', 
			                           'searchKeywords', 
			                           'lastUsedOn',
									   'prototypeId', 
									   'version',
									   'subVersion', 
									   'changeModifiedOn', 
			                           'changeModifiedBy',
			                           'brState');
	
	
	/**
	 * Извиква се след описанието на модела
	 */
	public static function on_AfterDescription(&$mvc)
	{
		$mvc->declareInterface('doc_PrototypeSourceIntf');
		
		$after = ($mvc instanceof embed_Manager) ? $mvc->driverClassField : (($mvc instanceof core_Embedder) ? $mvc->driverClassField : 'id');
		$mvc->FLD('prototypeId', "int", "caption=Шаблон,forceField,input=none,silent,removeAndRefreshForm=chargeVat,after={$after}");
	}
	
	
	/**
	 * Подготвя формата за въвеждане на данни за вътрешния обект
	 *
	 * @param core_Form $form
	 */
	public static function on_AfterPrepareEmbeddedForm($mvc, core_Form &$form)
	{
		self::prepareForm($mvc, $form);
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна
	 */
	public static function on_AfterPrepareEditForm($mvc, &$data)
	{
		if($mvc instanceof core_Embedder) return;
		
		self::prepareForm($mvc, $data->form);
	}
	
	
	/**
	 * Подготовка на формата 
	 * 
	 * @param core_Mvc $mvc
	 * @param core_Form $form
	 */
	private static function prepareForm($mvc, &$form)
	{
		$fields = array();
		
		if($mvc instanceof embed_Manager){
			if(isset($form->rec->{$mvc->driverClassField})){
				$prototypes = doc_Prototypes::getPrototypes($mvc, $form->rec->{$mvc->driverClassField});
			}
		} elseif($mvc instanceof core_Embedder){
			if(isset($form->rec->{$mvc->innerClassField})){
				$prototypes = doc_Prototypes::getPrototypes($mvc, $form->rec->{$mvc->innerClassField});
			}
		} else{
			$prototypes = doc_Prototypes::getPrototypes($mvc);
		}
		
		// Ако има прототипи
		if(count($prototypes)){
			$form->setField('prototypeId', 'input');
			$form->setOptions('prototypeId', array('' => '') + $prototypes);
				
			// Определяне на кои полета ще се попълват от прототипа
			$fields = arr::make(array_keys($mvc->selectFields()), TRUE);
			if($mvc instanceof core_Embedder){
				$driverFields = cls::get($form->rec->{$mvc->innerClassField})->getDriverFields();
				if(count($driverFields)){
					$fields += $driverFields;
				}
			} elseif($mvc instanceof embed_Manager){
				if(isset($form->rec->{$mvc->driverClassField})){
					if($Driver = cls::get($form->rec->{$mvc->driverClassField})){
						$driverFields = arr::make(array_keys($mvc::getDriverFields($Driver)), TRUE);
						if(count($driverFields)){
							$fields += $driverFields;
						}
					}
				}
			}
			
			// Махат се определени полета от всичките
			$unsetFields = arr::make(self::$unsetFields, TRUE);
			$fieldsNotToClone = arr::make($mvc->fieldsNotToClone, TRUE);
			$unsetFields = $unsetFields + $fieldsNotToClone;
			$fields = array_diff_key($fields, $unsetFields);
			
			// Добавяне на рефреш на полето
			if(count($fields)){
				$refresh = implode('|', array_keys($fields));
				$form->setField('prototypeId', "removeAndRefreshForm={$refresh}");
			}
				
			// При редакция прототипа не може да се сменя
			if(isset($form->rec->id)){
				$form->setField('prototypeId', 'input=hidden');
			}
		}
		
		// Ако няма ид
		if(empty($form->rec->id)){
			
			// И има избран прототип
			if($proto = $form->rec->prototypeId) {
				if($protoRec = $mvc->fetch($proto)) {
					$isCoreEmbedder = $mvc instanceof core_Embedder;
					
					// Данните му се зареждат
					if(count($fields)){
						foreach ($fields as $field){
							$value = ($isCoreEmbedder === FALSE) ? $protoRec->{$field} : $protoRec->{$mvc->innerFormField}->{$field};
							$form->rec->{$field} = $value;
						}
					}
				}
			}
		}
	}
	
	
	/**
	 * След подготовка на тулбара за единичен изглед
	 */
	public static function on_AfterPrepareSingleToolbar($mvc, $data)
	{
		$rec = $data->rec;
		
		// Бутон за добавяне на шаблон
		if(doc_Prototypes::haveRightFor('add', (object)array('originId' => $rec->containerId))){
        	$data->toolbar->addBtn('Шаблон', array('doc_Prototypes', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE), 'ef_icon=img/16/disk.png, title=Маркиране на документа като шаблон');
        }
        
        // Бутон за редакция на шаблона, ако има такъв
        if($pRec = doc_Prototypes::fetch("#originId = {$rec->containerId}")){
        	if(doc_Prototypes::haveRightFor('edit', $pRec)){
        		$data->toolbar->addBtn('Шаблон', array('doc_Prototypes', 'edit', $pRec->id, 'ret_url' => TRUE), 'ef_icon=img/16/edit-icon.png, title=Редактиране на шаблона');
        	}
        }
	}
	
	
	/**
	 * Изпълнява се след създаване на нов запис
	 */
	public static function on_AfterCreate($mvc, $rec)
	{
		if(isset($rec->prototypeId) && ($rec->_isClone !== TRUE)){
			$oldRec = (object)array('id' => $rec->prototypeId);
			
			// След създаване на документ с избран прототип, клонират се детайлите му
			$Details = $mvc->getDetailsToClone($rec);
			plg_Clone::cloneDetails($Details, $rec->prototypeId, $rec->id);
		}
	}
}