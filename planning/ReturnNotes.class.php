<?php



/**
 * Клас 'planning_ReturnNotes' - Документ за Протокол за връщане
 *
 * 
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_ReturnNotes extends deals_ManifactureMaster
{
	
	
	/**
	 * Заглавие
	 */
	public $title = 'Протоколи за връщане от производство';
	
	
	/**
	 * Абревиатура
	 */
	public $abbr = 'Mrn';
	
	
	/**
	 * Поддържани интерфейси
	 */
	public $interfaces = 'acc_TransactionSourceIntf=planning_transaction_ReturnNote';
	
	
	/**
	 * Плъгини за зареждане
	 */
	public $loadList = 'plg_RowTools2, store_plg_StoreFilter, planning_Wrapper, acc_plg_DocumentSummary, acc_plg_Contable,
                    doc_DocumentPlg, plg_Printing, plg_Clone, plg_Search';
	
	
	/**
	 * Полета от които се генерират ключови думи за търсене (@see plg_Search)
	 */
	public $searchFields = 'storeId,note';
	
	
	/**
	 * Кой има право да чете?
	 */
	public $canConto = 'ceo,planning,store';
	
	
	/**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,planning,store';
	
	
	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,planning,store';
	
	
	/**
	 * Кой има право да променя?
	 */
	public $canEdit = 'ceo,planning,store';
	
	
	/**
	 * Кой има право да добавя?
	 */
	public $canAdd = 'ceo,planning,store';
	
	
	/**
	 * Заглавие в единствено число
	 */
	public $singleTitle = 'Протокол за връщане от производство';
	
	
	/**
	 * Файл за единичния изглед
	 */
	public $singleLayoutFile = 'planning/tpl/SingleLayoutReturnNote.shtml';
	
	 
	/**
	 * Групиране на документите
	 */
	public $newBtnGroup = "3.51|Производство";
	
	
	/**
	 * Детайл
	 */
	public $details = 'planning_ReturnNoteDetails';
	
	
	/**
	 * Кой е главния детайл
	 * 
	 * @var string - име на клас
	 */
	public $mainDetail = 'planning_ReturnNoteDetails';
	
	
	/**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     * 
     * @see plg_Clone
     */
	public $cloneDetails = 'planning_ReturnNoteDetails';
	
	
	/**
	 * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
	 */
	public $rowToolsSingleField = 'title';
	
	
	/**
	 * Икона на единичния изглед
	 */
	public $singleIcon = 'img/16/produce_out.png';
	
	
	/**
	 * Кой може да го прави документа чакащ/чернова?
	 */
	public $canPending = 'ceo,planning,store';
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		parent::setDocumentFields($this);
		$this->FLD('departmentId', 'key(mvc=hr_Departments,select=name,allowEmpty)', 'caption=Департамент,before=note');
		$this->FLD('useResourceAccounts', 'enum(yes=Да,no=Не)', 'caption=Детайлно връщане->Избор,notNull,default=yes,maxRadio=2,before=note');
	}
	
	
	/**
	 * Подготвя данните (в обекта $data) необходими за единичния изглед
	 */
	public function prepareEditForm_($data)
	{
		parent::prepareEditForm_($data);
		
		$form = &$data->form;
		$rec = &$form->rec;
		
		// Ако ориджина е протокол за влагане
		if(isset($rec->originId) && empty($rec->id)){
			$origin = doc_Containers::getDocument($rec->originId);
			if($origin->isInstanceOf('planning_ConsumptionNotes')){
				$detailId = planning_ConsumptionNoteDetails::getClassId();
		
				// Всеки артикул от протокола се показва във формата
				$rec->details = array();
				$dQuery = planning_ConsumptionNoteDetails::getQuery();
				$dQuery->where("#noteId = {$origin->that}");
		
				// Ако ориджина има артикули
				while($dRec = $dQuery->fetch()){
					$caption = cat_Products::getTitleById($dRec->productId);
					$caption .= " / " . cat_UoM::getShortName($dRec->packagingId);
					$caption= str_replace(',', ' ', $caption);
					$Def = batch_Defs::getBatchDef($dRec->productId);
						
					$subCaption = 'К-во';
					
					// Ако е инсталиран пакета за партиди, ще се показват и те
					if(core_Packs::isInstalled('batch') && is_object($Def)){
						$subCaption = 'Без партида';
						$bQuery = batch_BatchesInDocuments::getQuery();
						$bQuery->where("#detailClassId = {$detailId} AND #detailRecId = {$dRec->id} AND #productId = {$dRec->productId}");
						$bQuery->show('batch');
						while($bRec = $bQuery->fetch()){
							$verbal = strip_tags($Def->toVerbal($bRec->batch));
							$b = str_replace(',', '', $bRec->batch);
							$b = str_replace('.', '', $b);
							
							$max = ($Def instanceof batch_definitions_Serial) ? 'max=1' : '';
							$key = "quantity|{$b}|{$dRec->id}";
							$form->FLD($key, "double(Min=0,{$max})","input,caption={$caption}->|*{$verbal}");
							$clone = clone $dRec;
							$clone->batch = $bRec->batch;
							$rec->details[$key] = $clone;
						}
					}

					// Показване на полетата без партиди
					$form->FLD("quantity||{$dRec->id}", "double(Min=0)","input,caption={$caption}->{$subCaption}");
					$rec->details["quantity||{$dRec->id}"] = $dRec;
				}
			}
		}
		
		return $data;
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна
	 */
	protected static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$form = &$data->form;
		$rec = &$form->rec;
		$form->setDefault('useResourceAccounts', planning_Setup::get('CONSUMPTION_USE_AS_RESOURCE'));
		
		$folderCover = doc_Folders::getCover($rec->folderId);
		if($folderCover->isInstanceOf('hr_Departments')){
			$form->setReadOnly('departmentId', $folderCover->that);
		}
	}
	
	
	/**
	 * Извиква се след успешен запис в модела
	 *
	 * @param core_Mvc $mvc
	 * @param int $id първичния ключ на направения запис
	 * @param stdClass $rec всички полета, които току-що са били записани
	 */
	public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $saveFileds = NULL)
	{
		// Ако дефолтни детайли
		if(count($rec->details)){
			$saveArray = array();
			$Detail = cls::get('planning_ReturnNoteDetails');
			
			// Ъпдейтват им се к-та
			foreach ($rec->details as $field => $det){
				if(empty($rec->{$field})) continue;
				unset($det->id, $det->createdOn, $det->createdBy);
				if(!empty($det->batch)){
					$det->isEdited = TRUE;
				}
				
				$det->noteId = $rec->id;
				$det->quantity = $rec->{$field} * $det->quantityInPack;
				$Detail->save($det);
			}
		}
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид.
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $row Това ще се покаже
	 * @param stdClass $rec Това е записа в машинно представяне
	 */
	public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		$row->useResourceAccounts = ($rec->useResourceAccounts == 'yes') ? 'Артикулите ще бъдат изписани от незавършеното производство един по един' : 'Артикулите ще бъдат изписани от незавършеното производството сумарно';
		$row->useResourceAccounts = tr($row->useResourceAccounts);
		
		if(isset($rec->departmentId)){
			$row->departmentId = hr_Departments::getHyperlink($rec->departmentId, TRUE);
		}
	}
}