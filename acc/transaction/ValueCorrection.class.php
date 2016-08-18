<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа acc_ValueCorrections
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class acc_transaction_ValueCorrection extends acc_DocumentTransactionSource
{
	
	
	/**
	 * @param int $id
	 * @return stdClass
	 * @see acc_TransactionSourceIntf::getTransaction
	 */
	public function getTransaction($id)
	{
		// Извличане на мастър-записа
		expect($rec = $this->class->fetchRec($id));
	
		$result = (object)array(
				'reason' => $rec->notes,
				'valior' => $rec->valior,
				'totalAmount' => 0,
				'entries' => array()
		);
	
		$entries = $this->getEntries($rec, $result->totalAmount);
		if(count($entries)){
			$result->entries = $entries;
		}
		
		return $result;
	}
	
	
	/**
	 * Връща записите на транзакцията
	 */
	private function getEntries($rec, &$total)
	{
		// Кой е първия документ в треда ?
		$firstDoc = doc_Threads::getFirstDocument($rec->threadId);
		$firstDocOriginId = $firstDoc->fetchField('containerId');
		$correspondingDoc = doc_Containers::getDocument($rec->correspondingDealOriginId);
		
		// Ако кореспондиращата сделка е същата сделка
		if($firstDocOriginId == $rec->correspondingDealOriginId){
			$entries = $this->getSameDealEntries($rec, $total, $firstDoc, $correspondingDoc);
		
			// Ако кореспондиращата сделка е финансова сделка
		} elseif($correspondingDoc->isInstanceOf('findeals_Deals')){
			$entries = $this->getFindealsEntries($rec, $total, $firstDoc, $correspondingDoc);
		
			// Ако кореспондиращата сделка е покупка
		} elseif($correspondingDoc->isInstanceOf('purchase_Purchases')) {
			$entries = $this->getPurchaseEntries($rec, $total, $firstDoc, $correspondingDoc);
		}
		
		return $entries;
	}
	
	
	/**
	 * Записите ако кореспондиращата сделка е същата като сделката начало на нишката
	 */
	private function getSameDealEntries($rec, &$total, $firstDoc, $correspondingDoc)
	{
		$entries = array();
		
		$sign = ($rec->action == 'increase') ? 1 : -1;
		
		$contragentClassId = $correspondingDoc->fetchField('contragentClassId');
		$contragentId = $correspondingDoc->fetchField('contragentId');
		$currencyId = currency_Currencies::getIdByCode($correspondingDoc->fetchField('currencyId'));
		$vatType = $firstDoc->fetchField('chargeVat');
		
		// Ако е към продажба
		if($firstDoc->isInstanceOf('sales_Sales')){
			$debitArr = array('411', array($contragentClassId, $contragentId),
									  array($correspondingDoc->getInstance()->getClassId(), $correspondingDoc->that),
									  array('currency_Currencies', $currencyId),
								'quantity' => 0);
			
			$vatAmount = 0;
			foreach ($rec->productsData as $prod){
				$pInfo = cat_Products::getProductInfo($prod->productId);
				$creditAcc = (isset($pInfo->meta['canStore'])) ? '701' : '703';
				
				$entries[] = array('amount' => $sign * $prod->allocated,
								   'debit' => $debitArr,
								   'credit' => array($creditAcc, 
													array($contragentClassId, $contragentId),
													array($correspondingDoc->getInstance()->getClassId(), $correspondingDoc->that),
									  				array('cat_Products', $prod->productId),
											'quantity' => 0),
								   
				);
					
				$total += $sign * $prod->allocated;
				$vatAmount += $prod->allocated * cat_Products::getVat($prod->productId, $rec->valior);
			}
			
			if($vatType == 'yes' || $vatType == 'separate'){
				$entries[] = array('amount' => round($sign * $vatAmount, 2),
						'debit' => $debitArr,
						'credit' => array('4530'),
				);
					
				$total += round($sign * $vatAmount, 2);
			}
			
			// Ако е към покупка
		} elseif($firstDoc->isInstanceOf('purchase_Purchases')){
			
			$creditArr = array('401', array($contragentClassId, $contragentId),
									  array($correspondingDoc->getInstance()->getClassId(), $correspondingDoc->that),
									  array('currency_Currencies', $currencyId),
							   'quantity' => 0);
			$vatAmount = 0;
			
			foreach ($rec->productsData as $prod){
				foreach ($prod->inStores as $storeId => $storeQuantity){
					$storeQuantity = (is_array($storeQuantity)) ? $storeQuantity['quantity'] : $storeQuantity;
					$amount = round($prod->allocated * ($storeQuantity / $prod->quantity), 2);
					
					$entries[] = array('amount' => $sign * $amount,
										'debit' => array('321', 
															array('store_Stores', $storeId), 
															array('cat_Products', $prod->productId), 
															'quantity' => 0),
										'credit' => $creditArr,
					);
					
					$total += $sign * $amount;
				}
				
				$vatAmount += $prod->allocated * cat_Products::getVat($prod->productId, $rec->valior);
			}
			
			if($vatType == 'yes' || $vatType == 'separate'){
					
				$entries[] = array('amount' => round($sign * $vatAmount, 2),
						'debit' => array('4530'),
						'credit' => $creditArr,
				);
					
				$total += round($sign * $vatAmount, 2);
			}
		}
		
		return $entries;
	}
	
	
	/**
	 * Връща записите на транзакцията ако кореспондиращата сделка е финансова сделка
	 */
	private function getFindealsEntries($rec, &$total, $firstDoc, $correspondingDoc)
	{
		$entries = array();
		
		$sign = ($rec->action == 'increase') ? 1 : -1;
		
		$contragentClassId = $correspondingDoc->fetchField('contragentClassId');
		$contragentId = $correspondingDoc->fetchField('contragentId');
		$currencyId = currency_Currencies::getIdByCode($correspondingDoc->fetchField('currencyId'));
		$creditAccId = $correspondingDoc->fetchField('accountId');
		$creditSysId = acc_Accounts::fetchField($creditAccId, 'systemId');
		
		$vatType = $firstDoc->fetchField('chargeVat');
		
		$creditArr = array($creditSysId,
				array($contragentClassId, $contragentId),
				array($correspondingDoc->getInstance()->getClassId(), $correspondingDoc->that),
				array('currency_Currencies', $currencyId),
				'quantity' => 0);
		
		// Ако е към продажба
		if($firstDoc->isInstanceOf('sales_Sales')){
			$vatAmount = 0;
			foreach ($rec->productsData as $prod){
				$pInfo = cat_Products::getProductInfo($prod->productId);
				$debitAcc = (isset($pInfo->meta['canStore'])) ? '701' : '703';
				$debitContragentClassId = $firstDoc->fetchField('contragentClassId');
				$debitContragentId = $firstDoc->fetchField('contragentId');
				
				$entries[] = array('amount' => $sign * $prod->allocated,
								   'debit' => array($debitAcc, 
													array($debitContragentClassId, $debitContragentId),
													array($firstDoc->getInstance()->getClassId(), $firstDoc->that),
									  				array('cat_Products', $prod->productId),
											'quantity' => 0),
								   'credit' => $creditArr,
								   
				);
					
				$total += $sign * $prod->allocated;
			}
			
			// Ако е към покупка
		} elseif($firstDoc->isInstanceOf('purchase_Purchases')){
			foreach ($rec->productsData as $prod){
				foreach ($prod->inStores as $storeId => $storeQuantity){
					$storeQuantity = (is_array($storeQuantity)) ? $storeQuantity['quantity'] : $storeQuantity;
					$amount = round($prod->allocated * ($storeQuantity / $prod->quantity), 2);
						
					$entries[] = array('amount' => $sign * $amount,
							'debit' => array('321',
									array('store_Stores', $storeId),
									array('cat_Products', $prod->productId),
									'quantity' => 0),
							'credit' => $creditArr,
								
					);
						
					$total += $sign * $amount;
				}
			}
		}
		
		return $entries;
	}
	
	
	/**
	 * Връща записите ако кореспондиращата сделка е покупка само със услуги
	 */
	private function getPurchaseEntries($rec, &$total, $firstDoc, $correspondingDoc)
	{
		$entries = array();
		
		$sign = ($rec->action == 'increase') ? 1 : -1;
		
		$contragentClassId = $correspondingDoc->fetchField('contragentClassId');
		$contragentId = $correspondingDoc->fetchField('contragentId');
		$currencyId = currency_Currencies::getIdByCode($correspondingDoc->fetchField('currencyId'));
		
		// Ако е към продажба
		if($firstDoc->isInstanceOf('sales_Sales')){
			foreach ($rec->productsData as $prod){
				$pInfo = cat_Products::getProductInfo($prod->productId);
				$debitAcc = (isset($pInfo->meta['canStore'])) ? '701' : '703';
				$debitContragentClassId = $firstDoc->fetchField('contragentClassId');
				$debitContragentId = $firstDoc->fetchField('contragentId');
				
				$entries[] = array('amount' => $sign * $prod->allocated,
						'debit' => array($debitAcc,
									array($debitContragentClassId, $debitContragentId),
									array($firstDoc->getInstance()->getClassId(), $firstDoc->that),
									array('cat_Products', $prod->productId),
								'quantity' => 0),
						'credit' => array('61102'),
				
				);
				
				$total += $sign * $prod->allocated;
			}
			
			// Ако е към покупка
		} elseif($firstDoc->isInstanceOf('purchase_Purchases')){
			
			foreach ($rec->productsData as $prod){
				foreach ($prod->inStores as $storeId => $storeQuantity){
					$storeQuantity = (is_array($storeQuantity)) ? $storeQuantity['quantity'] : $storeQuantity;
					$amount = round($prod->allocated * ($storeQuantity / $prod->quantity), 2);
			
					$entries[] = array('amount' => $sign * $amount,
										'debit' => array('321',
												array('store_Stores', $storeId),
												array('cat_Products', $prod->productId),
												'quantity' => 0),
										'credit' => array('61102'),
			
					);
			
					$total += $sign * $amount;
				}
			}
		}
		
		return $entries;
	}
	
	
	
	/**
	 * 
	 * 
	 * 
	 * @param array $products - масив с информация за артикули
     * 			    o productId       - ид на артикул
     * 				o name            - име на артикула
     *  			o quantity        - к-во
     *   			o amount          - сума на артикула
     *     			o inStores        - к-та с които артикула присъства в складовете, ако е повече от 1
	 * @param int $productId                           - ид на артикул
	 * @param int $expenseItemId                       - ид на разходен обект
	 * @param double $amount                           - сума за разпределяне
	 * @param quantity|value|weight|volume $allocateBy - начин на разпределяне
	 * @param boolean $sign                            - дали сумите да са отрицателни
	 * @return array $entries
	 */
	public static function getCorrectionEntries($products, $productId, $expenseItemId, $value, $allocateBy, $reverse = FALSE)
	{
		$entries = array();
		$sign = ($reverse) ? -1 : 1;
		
		$errorMsg = acc_ValueCorrections::allocateAmount($products, $value, $allocateBy);
		if(!empty($errorMsg)) return $entries;
		$itemRec = acc_Items::fetch($expenseItemId);
		$isPurchase = ($itemRec->classId == purchase_Purchases::getClassId());
		
		foreach ($products as $p){
			$creditArr = array('60201', $expenseItemId, array('cat_Products', $productId), 'quantity' => $sign * $p->allocated);
		
			if($isPurchase){
				foreach ($p->inStores as $storeId => $arr){
					if(is_array($arr)){
						$q = $arr['amount'];
						$am = $p->amount;
					} else {
						$q = $arr;
						$am = $p->quantity;
					}
					
					$allocated = round($p->allocated * ($q / $am), 2);
					$creditArr['quantity'] = $sign * $allocated;
					
					$entries[] = array('debit' => array('321',
													array('store_Stores', $storeId),
													array('cat_Products', $p->productId),
													'quantity' => 0),
									   'credit' => $creditArr, 
							'reason' => 'Корекция на стойност');
				}
			} else {
				$canStore = cat_Products::fetchField($p->productId, 'canStore');
				$accountSysId = ($canStore == 'yes') ? '701' : '703';
				$dealRec = cls::get($itemRec->classId)->fetch($itemRec->objectId, 'contragentClassId, contragentId');
				$creditArr['quantity'] = $sign * $p->allocated;
				
				$entries[] = array('debit' => array($accountSysId,
								array($dealRec->contragentClassId, $dealRec->contragentId),
								$expenseItemId, array('cat_Products', $p->productId),
								'quantity' => 0),
						'credit' => $creditArr, 'reason' => 'Корекция на стойност');
			}
		}
		 
		return $entries;
	}
}