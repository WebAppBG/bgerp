<?php
/**
 * Клас 'sales_Sales'
 *
 * Мениджър на документи за продажба на продукти от каталога
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_Sales extends core_Master
{
    /**
     * Заглавие
     * 
     * @var string
     */
    public $title = 'Продажби';


    /**
     * Абревиатура
     */
    var $abbr = 'Sal';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf,
                          acc_RegisterIntf=sales_RegisterImpl,
                          acc_TransactionSourceIntf=sales_TransactionSourceImpl,
                          bgerp_DealIntf, bgerp_DealAggregatorIntf';
    
    
    /**
     * Плъгини за зареждане
     * 
     * var string|array
     */
    public $loadList = 'plg_RowTools, sales_Wrapper, plg_Sorting, plg_Printing, acc_plg_Contable,
                    doc_DocumentPlg, plg_ExportCsv,
					doc_EmailCreatePlg, doc_ActivatePlg, bgerp_plg_Blank,
                    doc_plg_BusinessDoc2, acc_plg_Registry, store_plg_Shippable, acc_plg_DocumentSummary';
    
    
    /**
     * Активен таб на менюто
     * 
     * @var string
     */
    public $menuPage = 'Търговия:Продажби';
    
    /**
     * Кой има право да чете?
     * 
     * @var string|array
     */
    public $canRead = 'ceo,sales';
    
    
    /**
     * Кой има право да променя?
     * 
     * @var string|array
     */
    public $canEdit = 'ceo,sales';
    
    
    /**
     * Кой има право да добавя?
     * 
     * @var string|array
     */
    public $canAdd = 'ceo,sales';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,sales';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,sales';
    
    
    /**
     * Кой може да го види?
     * 
     * @var string|array
     */
    public $canView = 'ceo,sales';
    
    
    /**
     * Кой може да го изтрие?
     * 
     * @var string|array
     */
    public $canDelete = 'ceo,sales';
    

    /**
     * Документа продажба може да бъде само начало на нишка
     * 
     * Допълнително, папката в която могат да се създават нишки-продажби трябва да бъде с корица
     * контрагент. Това се гарантира с метода @see canAddToFolder()
     */
    var $onlyFirstInThread = TRUE;
    
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, valior, contragentClassId, contragentId, currencyId, amountDeal, amountDelivered, amountPaid, 
                             dealerId, initiatorId,
                             createdOn, createdBy';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     * 
     * @var string
     */
    public $rowToolsField;


    /**
     * Детайла, на модела
     *
     * @var string|array
     */
    public $details = 'sales_SalesDetails' ;
    

    /**
     * Заглавие в единствено число
     *
     * @var string
     */
    public $singleTitle = 'Продажба';
    
    
    /**
     * 
     */
   var $singleLayoutFile = 'sales/tpl/SingleLayoutSale.shtml';
   
    /**
     * Групиране на документите
     */ 
   var $newBtnGroup = "3.1|Търговия";
   
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        
        $this->FLD('valior', 'date', 'caption=Дата, mandatory,oldFieldName=date');
        $this->FLD('makeInvoice', 'enum(yes=Да,no=Не,monthend=Периодично)', 
            'caption=Фактуриране,maxRadio=3,columns=3');
        $this->FLD('chargeVat', 'enum(yes=с ДДС,no=без ДДС)', 'caption=ДДС');
        
        /*
         * Стойности
         */
        $this->FLD('amountDeal', 'double(decimals=2)', 'caption=Стойности->Поръчано,input=none,summary=amount'); // Сумата на договорената стока
        $this->FLD('amountDelivered', 'double(decimals=2)', 'caption=Стойности->Доставено,input=none,summary=amount'); // Сумата на доставената стока
        $this->FLD('amountPaid', 'double(decimals=2)', 'caption=Стойности->Платено,input=none,summary=amount'); // Сумата която е платена
        $this->FLD('amountInvoiced', 'double(decimals=2)', 'caption=Стойности->Фактурирано,input=none,summary=amount'); // Сумата която е платена
        
        /*
         * Контрагент
         */ 
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
        $this->FLD('contragentId', 'int', 'input=hidden');
        
        /*
         * Доставка
         */
        $this->FLD('deliveryTermId', 'key(mvc=salecond_DeliveryTerms,select=codeName)', 
            'caption=Доставка->Условие');
        $this->FLD('deliveryLocationId', 'key(mvc=crm_Locations, select=title)', 
            'caption=Доставка->Обект до,silent'); // обект, където да бъде доставено (allowEmpty)
        $this->FLD('deliveryTime', 'datetime', 
            'caption=Доставка->Срок до'); // до кога трябва да бъде доставено
        $this->FLD('shipmentStoreId', 'key(mvc=store_Stores,select=name,allowEmpty)', 
            'caption=Доставка->От склад'); // наш склад, от където се експедира стоката
        $this->FLD('isInstantShipment', 'enum(no=Последващ,yes=Този)', 
            'input, maxRadio=2, columns=2, caption=Доставка->Документ');
        
        /*
         * Плащане
         */
        $this->FLD('paymentMethodId', 'key(mvc=salecond_PaymentMethods,select=name)',
            'caption=Плащане->Начин,mandatory');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)',
            'caption=Плащане->Валута');
        $this->FLD('currencyRate', 'double', 'caption=Плащане->Курс');
        $this->FLD('bankAccountId', 'key(mvc=bank_OwnAccounts,select=title,allowEmpty)',
            'caption=Плащане->Банкова с-ка');
        $this->FLD('caseId', 'key(mvc=cash_Cases,select=name,allowEmpty)',
            'caption=Плащане->Каса');
        $this->FLD('isInstantPayment', 'enum(no=Последващ,yes=Този)', 'input,maxRadio=2, columns=2, caption=Плащане->Документ');
        
        /*
         * Наш персонал
         */
        $this->FLD('initiatorId', 'user(roles=user,allowEmpty)',
            'caption=Наш персонал->Инициатор');
        $this->FLD('dealerId', 'user(allowEmpty)',
            'caption=Наш персонал->Търговец');

        /*
         * Допълнително
         */
        $this->FLD('pricesAtDate', 'date', 'caption=Допълнително->Цени към');
        $this->FLD('note', 'richtext(bucket=Notes)', 'caption=Допълнително->Бележки', array('attr'=>array('rows'=>3)));
    	
    	$this->FLD('state', 
            'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)', 
            'caption=Статус, input=none'
        );
    	
    	$this->fields['dealerId']->type->params['roles'] = $this->getRequiredRoles('add');
    }
    
    
    /**
     * След промяна в детайлите на обект от този клас
     * 
     * @TODO Тук да се запомнят само мастър ид-тата, а същинското обновление на мастъра да се
     *       направи на on_Shutdown
     * 
     * @param core_Manager $mvc
     * @param int $id ид на мастър записа, чиито детайли са били променени
     * @param core_Manager $detailMvc мениджър на детайлите, които са били променени
     */
    public static function on_AfterUpdateDetail(core_Manager $mvc, $id, core_Manager $detailMvc)
    {
        $rec = $mvc->fetchRec($id);
        
        /* @var $query core_Query */
        $query = $detailMvc->getQuery();
        $query->where("#{$detailMvc->masterKey} = '{$id}'");
        
        $rec->amountDeal = 0;
        
        while ($detailRec = $query->fetch()) {
            $VAT = 1;
            
            if ($rec->chargeVat == 'yes') {
                $ProductManager = cls::get($detailRec->classId);
                
                $VAT += $ProductManager->getVat($detailRec->productId, $rec->valior);
            }
            
            $rec->amountDeal += $detailRec->amount * $VAT;
        }
        
        $mvc->save($rec);
    }
    
    public static function on_BeforeSave($mvc, $res, $rec)
    {
    }
    
    
    /**
     * Определяне на документа-източник (пораждащия документ)
     * 
     * @param core_Mvc $mvc
     * @param stdClass $origin
     * @param stdClass $rec
     */
    public static function getOrigin_($rec)
    {
        $rec = static::fetchRec($rec);
        
        if (!empty($rec->originId)) {
            $origin = doc_Containers::getDocument($rec->originId);
        } else {
            $origin = FALSE;
        }
        
        return $origin;
    }


    /**
     * След създаване на запис в модела
     *
     * @param store_Stores $mvc
     * @param store_model_ShipmentOrder $rec
     */
    public static function on_AfterCreate($mvc, $rec)
    {
        if (!$origin = static::getOrigin($rec)) {
            return;
        }
    
        // Ако новосъздадения документ има origin, който поддържа bgerp_DealIntf,
        // използваме го за автоматично попълване на детайлите на продажбата
    
        if ($origin->haveInterface('bgerp_DealIntf')) {
            /* @var $dealInfo bgerp_iface_DealResponse */
            $dealInfo = $origin->getDealInfo();
            
            $agreed = $dealInfo->agreed;
            
            /* @var $product bgerp_iface_DealProduct */
            foreach ($agreed->products as $product) {
                $product = (object)$product;

                if ($product->quantity <= 0) {
                    continue;
                }
        
                $saleProduct = new sales_model_SaleProduct(NULL);
        
                $saleProduct->saleId      = $rec->id;
                $saleProduct->classId     = cls::get($product->classId)->getClassId();
                $saleProduct->productId   = $product->productId;
                $saleProduct->packagingId = $product->packagingId;
                $saleProduct->quantity    = $product->quantity;
                $saleProduct->price       = $product->price;
                $saleProduct->uomId       = $product->uomId;
        
                $saleProduct->quantityInPack = $saleProduct->getQuantityInPack();
                
                $saleProduct->save();
            }
        }
    }
    

    /**
     * Извиква се преди изпълняването на екшън
     * 
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param string $action
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        switch ($action) {
            /*
             * Активират се само (непразни) продажби, които не генерират счетоводни транзакции
             */
            case 'activate':
                if (empty($rec->id)) {
                    // не се допуска активиране на незаписани продажби
                    $requiredRoles = 'no_one';
                } elseif (sales_SalesDetails::count("#saleId = {$rec->id}") == 0) {
                    // Не се допуска активирането на продажба без детайли
                    $requiredRoles = 'no_one';
                } elseif ($mvc->haveRightFor('conto', $rec)) {
                    // не се допуска активиране на продажба, която генерира счет. транзакция.
                    $requiredRoles = 'no_one';
                }
                break;
        }
    }

    
    function on_AfterRenderSingle($mvc, $tpl, $data)
    {
        // Данните на "Моята фирма"
        $ownCompanyData = crm_Companies::fetchOwnCompany();

        $address = trim($ownCompanyData->place . ' ' . $ownCompanyData->pCode);
        if ($address && !empty($ownCompanyData->address)) {
            $address .= '<br/>' . $ownCompanyData->address;
        }  
        
        $tpl->placeArray(
            array(
                'MyCompany'      => $ownCompanyData->company,
                'MyCountry'      => $ownCompanyData->country,
                'MyAddress'      => $address,
                'MyCompanyVatNo' => $ownCompanyData->vatNo,
            ), 'supplier'
        );
        
        // Данните на клиента
        $contragent = new core_ObjectReference($data->rec->contragentClassId, $data->rec->contragentId);
        $cdata      = static::normalizeContragentData($contragent->getContragentData());
        
        $tpl->placeObject($cdata, 'contragent');
        
        // Описателното (вербалното) състояние на документа
        $tpl->replace($data->row->state, 'stateText');
        
        if ($data->rec->currencyRate != 1) {
            $tpl->replace('(<span class="quiet">' . tr('курс') . "</span> {$data->row->currencyRate})", 'currencyRateText');
        }
    }
    
    
    public static function normalizeContragentData($contragentData)
    {
        /*
        * Разглеждаме четири случая според данните в $contragentData
        *
        *  1. Има данни за фирма и данни за лице
        *  2. Има само данни за фирма
        *  3. Има само данни за лице
        *  4. Нито едно от горните не е вярно
        */
        
        if (empty($contragentData->company) && empty($contragentData->person)) {
            // Случай 4: нито фирма, нито лице
            return FALSE;
        }
        
        // Тук ще попълним резултата
        $rec = new stdClass();
        
        $rec->contragentCountryId = $contragentData->countryId;
        $rec->contragentCountry   = $contragentData->country;
        
        if (!empty($contragentData->company)) {
            // Случай 1 или 2: има данни за фирма
            $rec->contragentName    = $contragentData->company;
            $rec->contragentAddress = trim(
                sprintf("%s %s\n%s",
                    $contragentData->place,
                    $contragentData->pCode,
                    $contragentData->address
                )
            );
            $rec->contragentVatNo = $contragentData->vatNo;
        
            if (!empty($contragentData->person)) {
                // Случай 1: данни за фирма + данни за лице
        
                // TODO за сега не правим нищо допълнително
            }
        } elseif (!empty($contragentData->person)) {
            // Случай 3: само данни за физическо лице
            $rec->contragentName    = $contragentData->person;
            $rec->contragentAddress = $contragentData->pAddress;
        }

        return $rec;
    }
    
    /**
     * Преди извличане на записите от БД
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param sales_Sales $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        // Задаване на стойности на полетата на формата по подразбиране
        self::setDefaultsFromOrigin($mvc, $data->form);
        self::setDefaults($mvc, $data->form);
        
        // Ако създаваме нов запис и то базиран на предхождащ документ ...
        if (empty($data->form->rec->id) && !empty($data->form->rec->originId)) {
            // ... и стойностите по подразбиране са достатъчни за валидиране
            // на формата, не показваме форма изобщо, а направо създаваме записа с изчислените
            // ст-сти по подразбиране. За потребителя си остава възможността да промени каквото
            // е нужно в последствие.
            
            if ($mvc->validate($data->form)) {
                if (self::save($data->form->rec)) {
                    redirect(array($mvc, 'single', $data->form->rec->id));
                }
            }
        }
    }
    
    
    /**
     * Зареждане на стойности по подразбиране от документа-основание 
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    protected static function setDefaultsFromOrigin(core_Mvc $mvc, core_Form $form)
    {
        if (!($origin = $mvc->getOrigin($form->rec)) || !$origin->haveInterface('bgerp_DealIntf')) {
            // Не може да се използва `bgerp_DealIntf`
            return false;
        }
        
        /* @var $dealInfo bgerp_iface_DealResponse */
        $dealInfo = $origin->getDealInfo();
        $aspect   = $dealInfo->agreed; // @FIXME: не трябваше ли да е ->quoted ?
        
        $form->setDefault('deliveryTermId', $aspect->delivery->term);
        $form->setDefault('deliveryLocationId', $aspect->delivery->location);
        $form->setDefault('paymentMethodId', $aspect->payment->method);
        $form->setDefault('bankAccountId', $aspect->payment->bankAccountId);
        $form->setDefault('currencyId', $aspect->currency);
    }
    
    
    /**
     * Зареждане на стойности по подразбиране 
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    protected static function setDefaults(core_Mvc $mvc, core_Form $form)
    {
        $form->setDefault('valior', dt::now());
        
        $form->setDefault('bankAccountId',bank_OwnAccounts::getCurrent('id', FALSE));
        $form->setDefault('caseId', cash_Cases::getCurrent('id', FALSE));
        $form->setDefault('shipmentStoreId', store_Stores::getCurrent('id', FALSE));
        
        if (empty($form->rec->dealerId)) {
            $form->setDefault('dealerId', $mvc::getDefaultDealer($form->rec));
        }
        
        if (empty($form->rec->folderId)) {
            expect($form->rec->folderId = core_Request::get('folderId', 'key(mvc=doc_Folders)'));
        }
        
        $form->setDefault('contragentClassId', doc_Folders::fetchCoverClassId($form->rec->folderId));
        $form->setDefault('contragentId', doc_Folders::fetchCoverId($form->rec->folderId));
        
        /*
         * Условия за доставка по подразбиране
         */
        if (empty($form->rec->deliveryTermId)) {
            $form->rec->deliveryTermId = $mvc::getDefaultDeliveryTermId($form->rec);
        }
        
        /*
         * Начин на плащане по подразбиране
         */
        if (empty($form->rec->paymentMethodId)) {
            $form->rec->paymentMethodId = $mvc::getDefaultPaymentMethodId($form->rec);
        }
        
        /*
         * Валута на продажбата по подразбиране
         */
        if (empty($form->rec->currencyId)) {
            $form->setDefault('currencyId', $mvc::getDefaultCurrencyCode($form->rec));
        }
        
        /*
         * Банкова сметка по подразбиране
         */
        if (empty($form->rec->bankAccountId)) {
            $form->setDefault('bankAccountId', $mvc::getDefaultBankAccountId($form->rec));
        }
        
        if (empty($form->rec->makeInvoice)) {
            $form->setDefault('makeInvoice', $mvc::getDefaultMakeInvoice($form->rec));
        }
        
        // Поле за избор на локация - само локациите на контрагента по продажбата
        $form->getField('deliveryLocationId')->type->options = 
            array(''=>'') +
            crm_Locations::getContragentOptions($form->rec->contragentClassId, $form->rec->contragentId);
        
        /*
         * Начисляване на ДДС по подразбиране
         */
        $contragentRef = new core_ObjectReference($form->rec->contragentClassId, $form->rec->contragentId);
        $form->setDefault('chargeVat', $contragentRef->shouldChargeVat() ?
                'yes' : 'no'
        );
        
        /*
         * Моментни експедиция и плащане по подразбиране
         */
        if (empty($form->rec->id)) {
            $isInstantShipment = !empty($form->rec->shipmentStoreId);
            $isInstantShipment = $isInstantShipment && 
                ($form->rec->shipmentStoreId == store_Stores::getCurrent('id', FALSE));
            $isInstantShipment = $isInstantShipment && 
                store_Stores::fetchField($form->rec->shipmentStoreId, 'chiefId');
            
            $isInstantPayment = !empty($form->rec->caseId);
            $isInstantPayment = $isInstantPayment && 
                ($form->rec->caseId == store_Stores::getCurrent('id', FALSE));
            $isInstantPayment = $isInstantPayment && 
                store_Stores::fetchField($form->rec->shipmentStoreId, 'chiefId');
            
            $form->setDefault('isInstantShipment', 
                $isInstantShipment ? 'yes' : 'no');
            $form->setDefault('isInstantPayment', 
                $isInstantPayment ? 'yes' : 'no');
        }
    }
    

    /**
     * Условия за доставка по подразбиране
     * 
     * @param stdClass $rec
     * @return int key(mvc=salecond_DeliveryTerms)
     */
    public static function getDefaultDeliveryTermId($rec)
    {
        $deliveryTermId = NULL;
        
        // 1. Условията на последната продажба на същия клиент
        if ($recentRec = self::getRecentSale($rec)) {
            $deliveryTermId = $recentRec->deliveryTermId;
        }
        
        // 2. Условията определени от локацията на клиента (държава, населено място)
        // @see salecond_DeliveryTermsByPlace
        if (empty($deliveryTermId)) {
            $contragent = new core_ObjectReference($rec->contragentClassId, $rec->contragentId);
            $deliveryTermId = salecond_DeliveryTerms::getDefault($contragent->getContragentData());
        }
        
        return $deliveryTermId;
    }
    

    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
        $title = tr("Продажба| №" . $rec->id);
        
         
        return $title;
    }


    /**
     * Условия за доставка по подразбиране
     * 
     * @param stdClass $rec
     * @return int key(mvc=salecond_DeliveryTerms)
     */
    public static function getDefaultPaymentMethodId($rec)
    {
        $paymentMethodId = NULL;    
        
        // 1. Според последната продажба на същия клиент от тек. потребител
        if ($recentRec = self::getRecentSale($rec, 'user')) {
            $paymentMethodId = $recentRec->paymentMethodId;
        }

        // 2. Ако има фиксирана каса - плащането (по подразбиране) е в брой (кеш, COD)
        if (!$paymentMethodId && $rec->caseId) {
            $paymentMethodId = salecond_PaymentMethods::fetchField("#name = 'COD'", 'id');
        }
        
        // 3. Според последната продажба към този клиент
        if (!$paymentMethodId && $recentRec = self::getRecentSale($rec, 'any')) {
            $paymentMethodId = $recentRec->paymentMethodId;
        }
        
        // 4. Според данните на клиента
        if (!$paymentMethodId) {
            $contragent = new core_ObjectReference($rec->contragentClassId, $rec->contragentId);
            $paymentMethodId = salecond_PaymentMethods::getDefault($contragent->getContragentData()); 
        }
        
        return $paymentMethodId;
    }


    /**
     * Определяне на валутата по подразбиране при нова продажба.
     *
     * @param stdClass $rec
     * @param string 3-буквен ISO код на валута (ISO 4217)
     */
    public static function getDefaultCurrencyCode($rec)
    {
        if ($recentRec = self::getRecentSale($rec)) {
            $currencyBaseCode = $recentRec->currencyId;
        } else {
            $contragent = new core_ObjectReference($rec->contragentClassId, $rec->contragentId);
            $currencyBaseCode = acc_Periods::getBaseCurrencyCode($rec->valior);
        }
         
        return $currencyBaseCode;
    }


    /**
     * Определяне на банковата с/ка по подразбиране при нова продажба.
     *
     * @param stdClass $rec
     * @param string 3-буквен ISO код на валута (ISO 4217)
     */
    public static function getDefaultBankAccountId($rec)
    {
        $bankAccountId = NULL;
        
        if ($recentRec = self::getRecentSale($rec)) {
            $bankAccountId = $recentRec->bankAccountId;
        }
        
        if ($bankAccountId && !empty($rec->currencyId)) {
            // Ако валутата на продажбата не съвпада с валутата на банк. с/ка - игнорираме
            // сметката.
            $baCurrencyId = bank_Accounts::fetchField($bankAccountId, 'currencyId');
            
            if ($baCurrencyId) {
                $baCurrencyId = currency_Currencies::getCodeById($baCurrencyId);
            }
            if ($baCurrencyId && $baCurrencyId != $rec->currencyId) {
                $bankAccountId = NULL;
            }
        }
        
        if (!$bankAccountId) {
            $contragent = new core_ObjectReference($rec->contragentClassId, $rec->contragentId);
            $bankAccountId = bank_OwnAccounts::getDefault($contragent->getContragentData()); 
        }
         
        return $bankAccountId;
    }
    
    
    /**
     * Определяне ст-ст по подразбиране на полето makeInvoice
     * 
     * @param stdClass $rec
     * @return string ('yes' | 'no' | 'monthend') 
     *  
     */
    public static function getDefaultMakeInvoice($rec)
    {
        $makeInvoice = NULL;
        
        if ($recentRec = self::getRecentSale($rec)) {
            $makeInvoice = $recentRec->makeInvoice;
        } else {
            $makeInvoice = 'yes';
        }
         
        return $makeInvoice;
    }
    
    
    /**
     * Помощен метод за определяне на търговец по подразбиране.
     * 
     * Правило за определяне: първия, който има права за създаване на продажби от списъка:
     * 
     *  1/ Отговорника на папката на контрагента
     *  2/ Текущият потребител
     *  
     *  Ако никой от тях няма права за създаване - резултатът е NULL
     *
     * @param stdClass $rec запис на модела sales_Sales
     * @return int|NULL user(roles=sales)
     */
    public static function getDefaultDealer($rec)
    {
        expect($rec->folderId);

        // Отговорника на папката на контрагента ...
        $inChargeUserId = doc_Folders::fetchField($rec->folderId, 'inCharge');
        if (self::haveRightFor('add', NULL, $inChargeUserId)) {
            // ... има право да създава продажби - той става дилър по подразбиране.
            return $inChargeUserId;
        }
        
        // Текущия потребител ...
        $currentUserId = core_Users::getCurrent('id');
        if (self::haveRightFor('add', NULL, $currentUserId)) {
            // ... има право да създава продажби
            return $currentUserId;
        }
        
        return NULL;
    }
    
    
    /**
     * Най-новата контирана продажба към същия клиент, създадена от текущия потребител, тима му или всеки
     * 
     * @param stdClass $rec запис на модела sales_Sales
     * @param string $scope 'user' | 'team' | 'any'
     * @return stdClass
     */
    protected static function getRecentSale($rec, $scope = NULL)
    {
        if (!isset($scope)) {
            foreach (array('user', 'team', 'any') as $scope) {
                expect(!is_null($scope));
                if ($recentRec = self::getRecentSale($rec, $scope)) {
                    return $recentRec;
                }
            }
            
            return NULL;
        }
        
        /* @var $query core_Query */
        $query = static::getQuery();
        $query->where("#state = 'active'");
        $query->where("#contragentClassId = '{$rec->contragentClassId}'");
        $query->where("#contragentId = '{$rec->contragentId}'");
        $query->orderBy("createdOn", 'DESC');
        $query->limit(1);
        
        switch ($scope) {
            case 'user':
                $query->where('#createdBy = ' . core_Users::getCurrent('id'));
                break;
            case 'team':
                $teamMates = core_Users::getTeammates(core_Users::getCurrent('id'));
                $teamMates = keylist::toArray($teamMates);
                if (!empty($teamMates)) {
                    $query->where('#createdBy IN (' . implode(', ', $teamMates) . ')');
                }
                break;
        }
        
        $recentRec = $query->fetch();
        
        return $recentRec ? $recentRec : NULL;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        if (!$form->isSubmitted()) {
            return;
        }
        
        /*
         * Ако не е въведен валутен курс, използва се курса към датата на документа 
         */
        if (empty($form->rec->currencyRate)) {
            $form->rec->currencyRate = 
                currency_CurrencyRates::getRate($form->rec->valior, $form->rec->currencyId, NULL);
        }

        if ($form->rec->isInstantShipment == 'yes') {
            $invalid = empty($form->rec->shipmentStoreId);
            $invalid = $invalid ||
                store_Stores::fetchField($form->rec->shipmentStoreId, 'chiefId') != core_Users::getCurrent();
            if ($invalid) {
                $form->setError('isInstantShipment', 'Само отговорика на склада може да експедира на момента от него');
            }
        }

        if ($form->rec->isInstantPayment == 'yes') {
            $invalid = empty($form->rec->caseId);
            $invalid = $invalid ||
                cash_Cases::fetchField($form->rec->caseId, 'cashier') != core_Users::getCurrent();
            if ($invalid) {
                $form->setError('isInstantPayment', 'Само отговорика на касата може да приема плащане на момента');
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
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
		foreach (array('Deal', 'Paid', 'Delivered', 'Invoiced', 'ToPay') as $amnt) {
            if ($rec->{"amount{$amnt}"} != 0) {
                $row->{"amount{$amnt}"} = (($row->currencyId) ? ("<span class='cCode'>{$row->currencyId}</span> ") : "") .  $row->{"amount{$amnt}"};
            } else {
                $row->{"amount{$amnt}"} = '<span class="quiet">0.00</span>';
            }
        }
        
        $amountType = $mvc->getField('amountDeal')->type;
        
        $row->amountToPay = '<span class="cCode">' . $row->currencyId . '</span> ' 
            . $amountType->toVerbal($rec->amountDeal - $rec->amountPaid);

        if ($rec->chargeVat == 'no') {
            $row->chargeVat = '';
        }
        
        if ($rec->isInstantPayment == 'yes') {
            $row->caseId .= ' (на момента)';
        }
        if ($rec->isInstantShipment == 'yes') {
            $row->shipmentStoreId .= ' (на момента)';
        }
    }
    
    
    public static function on_AfterPrepareListRecs(core_Mvc $mvc, $data)
    {
        if (!count($data->recs)) {
            return;
        }
        
        // Основната валута към момента
        $now            = dt::now();
        $baseCurrencyId = acc_Periods::getBaseCurrencyCode($now);
        
        // Всички общи суми на продажба - в базова валута към съотв. дата
        foreach ($data->recs as &$rec) {
            $rate = currency_CurrencyRates::getRate($now, $rec->currencyId, $baseCurrencyId);
            
            $rec->amountDeal *= $rate; 
            $rec->amountDelivered *= $rate; 
            $rec->amountPaid *= $rate; 
            $rec->currencyId = NULL; // За да не се показва валутата като префикс в списъка
        }
    }
    
    
    public static function on_AfterPrepareListRows(core_Mvc $mvc, $data)
    {
        // Премахваме някои от полетата в listFields. Те са оставени там за да ги намерим в 
        // тук в $rec/$row, а не за да ги показваме
        $data->listFields = array_diff_key(
            $data->listFields, 
            arr::make('currencyId,initiatorId,contragentId', TRUE)
        );
        
        $data->listFields['dealerId'] = 'Търговец';
        
        if (count($data->rows)) {
            foreach ($data->rows as $i=>&$row) {
                $rec = $data->recs[$i];
                
                // "Изчисляване" на името на клиента
                $contragentData = NULL;
                
                if ($rec->contragentClassId && $rec->contragentId) {
    
                    $contragent = new core_ObjectReference(
                        $rec->contragentClassId, 
                        $rec->contragentId 
                    );
                    
                    $row->contragentClassId = $contragent->getHyperlink();
                }
    
                // Търговец (чрез инициатор)
                if (!empty($rec->initiatorId)) {
                    $row->dealerId .= '<small style="display: block;"><span class="quiet">чрез</span> ' . $row->initiatorId;
                }
            }
        }
            
    }

    
    /**
     * Филтър на продажбите
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareListFilter(core_Mvc $mvc, $data)
    {
        $data->listFilter->FNC('type', 'enum(all=Всички,paid=Платени,unpaid=Неплатени,delivered=Доставени,undelivered=Недоставени)', 'caption=Тип,width=10em,silent,allowEmpty');
		$data->listFilter->showFields .= ',type';
		$data->listFilter->input();
		
		if($filter = $data->listFilter->rec) {
			if($filter->type) {
				switch($filter->type){
					case "all":
						break;
					case 'paid':
						$data->query->orWhere("#amountPaid = #amountDeal");
						break;
					case 'delivered':
						$data->query->orWhere("#amountDelivered = #amountDeal");
						break;
					case 'undelivered':
						$data->query->orWhere("#amountDelivered != #amountDeal");
						break;
					case 'unpaid':
						$data->query->orWhere("#amountPaid != #amountDelivered");
						$data->query->orWhere("#amountPaid IS NULL");
						$data->query->Where("#state = 'active'");
						break;
				}
			}
		}
    }
    
    
    public static function on_AfterPrepareListTitle($mvc, $data)
    {
        // Използваме заглавието на списъка за заглавие на филтър-формата
        $data->listFilter->title = $data->title;
        $data->title = NULL;
    }
    
    
    /**
     * Може ли документ-продажба да се добави в посочената папка?
     * 
     * Документи-продажба могат да се добавят само в папки с корица контрагент.
     *
     * @param $folderId int ид на папката
     * @return boolean
     */
    public static function canAddToFolder($folderId)
    {
        $coverClass = doc_Folders::fetchCoverClassName($folderId);
    
        return cls::haveInterface('doc_ContragentDataIntf', $coverClass);
    }
    
    
    /**
     * 
     * @param int $id key(mvc=sales_Sales)
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow($id)
    {
        expect($rec = $this->fetch($id));
        
        $row = (object)array(
            'title'    => "Продажба №{$rec->id} / " . $this->getVerbal($rec, 'valior'),
            'authorId' => $rec->createdBy,
            'author'   => $this->getVerbal($rec, 'createdBy'),
            'state'    => $rec->state,
            'recTitle' => $this->getRecTitle($rec),
        );
        
        return $row;
    }
    
    
    /*
     * РЕАЛИЗАЦИЯ НА store_ShipmentIntf
     */
    
    
    /**
     * Данни за експедиция, записани в документа продажба
     * 
     * @param int $id key(mvc=sales_Sales)
     * @return object
     */
    public function getShipmentInfo($id)
    {
        $rec = $this->fetch($id);
        
        return (object)array(
             'contragentClassId' => $rec->contragentClassId,
             'contragentId' => $rec->contragentId,
             'termId' => $rec->deliveryTermId,
             'locationId' => $rec->deliveryLocationId,
             'deliveryTime' => $rec->deliveryTime,
             'storeId' => $rec->shipmentStoreId,
        );
    }
    
    
    /**
     * Детайли (продукти), записани в документа продажба
     * 
     * @param int $id key(mvc=sales_Sales)
     * @return array
     */
    public function getShipmentProducts($id)
    {
        $products = array();
        $saleRec  = $this->fetchRec($id);
        $query    = sales_SalesDetails::getQuery();
        
        $query->where("#saleId = {$saleRec->id}");
        
        while ($rec = $query->fetch()) {
            if ($saleRec->chargeVat == 'yes') {
                // Начисляваме ДДС
                $ProductManager = cls::get($rec->classId);
                $rec->price *= 1 + $ProductManager->getVat($rec->productId, $saleRec->valior);
            } 
            $products[] = (object)array(
                'policyId'  => $rec->policyId,
                'productId'  => $rec->productId,
                'uomId'  => $rec->uomId,
                'packagingId'  => $rec->packagingId,
                'quantity'  => $rec->quantity,
                'quantityDelivered'  => $rec->quantityDelivered,
                'quantityInPack'  => $rec->quantityInPack,
                'price'  => $rec->price,
                'discount'  => $rec->discount,
            );
        }
        
        return $products;
    }
    
    
    public static function roundPrice($price)
    {
        $precision = 2 + 
            ($price <= 10) +
            ($price <= 1) +
            ($price <= 0.1);
        
        $price = round($price, $precision);
        
        return $price;
    }
    
    
    /**
     * Трасира веригата от документи, породени от дадена продажба. Извлича от тях експедираните 
     * количества и платените суми.
     * 
     * @param core_Mvc $mvc
     * @param core_ObjectReference $saleRef
     * @param core_ObjectReference $descendantRef кой породен документ е инициатор на трасирането
     */
    public static function on_DescendantChanged($mvc, $saleRef, $descendantRef = NULL)
    {
        $saleRec            = new sales_model_Sale($saleRef->rec());
        $aggregatedDealInfo = $saleRec->getAggregatedDealInfo();

        $saleRec->updateAggregateDealInfo($aggregatedDealInfo);
    }
    
    
	/**
     * Връща масив от използваните нестандартни артикули в продажбата
     * @param int $id - ид на продажба
     * @return param $res - масив с използваните документи
     * 					['class'] - Инстанция на документа
     * 					['id'] - Ид на документа
     */
    public function getUsedDocs_($id)
    {
    	$res = array();
    	$dQuery = $this->sales_SalesDetails->getQuery();
    	$dQuery->EXT('state', 'sales_Sales', 'externalKey=saleId');
    	$dQuery->where("#saleId = '{$id}'");
    	$dQuery->groupBy('productId,policyId');
    	while($dRec = $dQuery->fetch()){
    		$productMan = cls::get($dRec->classId);
    		if(cls::haveInterface('doc_DocumentIntf', $productMan)){
    			$res[] = (object)array('class' => $productMan, 'id' => $dRec->productId);
    		}
    	}
    	return $res;
    }
    

    /**
     * Имплементация на @link bgerp_DealIntf::getDealInfo()
     * 
     * @param int|object $id
     * @return bgerp_iface_DealResponse
     * @see bgerp_DealIntf::getDealInfo()
     */
    public function getDealInfo($id)
    {
        $rec = new sales_model_Sale(self::fetchRec($id));
        
        // Извличаме продуктите на продажбата
        $detailRecs = $rec->getDetails('sales_SalesDetails', 'sales_model_SaleProduct');
                
        $result = new bgerp_iface_DealResponse();
        
        $result->dealType = bgerp_iface_DealResponse::TYPE_SALE;
        
        $result->agreed->amount                 = $rec->amountDeal;
        $result->agreed->currency               = $rec->currencyId;
        $result->agreed->delivery->location     = $rec->deliveryLocationId;
        $result->agreed->delivery->term         = $rec->deliveryTermId;
        $result->agreed->delivery->time         = $rec->deliveryTime;
        $result->agreed->payment->method        = $rec->paymentMethodId;
        $result->agreed->payment->bankAccountId = $rec->bankAccountId;
        $result->agreed->payment->caseId        = $rec->caseId;
        
        
        if ($rec->isInstantPayment == 'yes') {
            $result->paid->amount   = $rec->amountDeal;
            $result->paid->currency = $rec->currencyId;
            $result->paid->payment->method        = $rec->paymentMethodId;
            $result->paid->payment->bankAccountId = $rec->bankAccountId;
            $result->paid->payment->caseId        = $rec->caseId;
        }

        if ($rec->isInstantShipment == 'yes') {
            $result->shipped->amount   = $rec->amountDeal;
            $result->shipped->currency = $rec->currencyId;
            $result->shipped->delivery->location     = $rec->deliveryLocationId;
            $result->shipped->delivery->term         = $rec->deliveryTermId;
            $result->shipped->delivery->time         = $rec->deliveryTime;
        }
        
        /* @var $dRec sales_model_SaleProduct */
        foreach ($detailRecs as $dRec) {
            $p = new bgerp_iface_DealProduct();
            
            $p->classId     = $dRec->classId;
            $p->productId   = $dRec->productId;
            $p->packagingId = $dRec->packagingId;
            $p->discount    = $dRec->discount;
            $p->isOptional  = FALSE;
            $p->quantity    = $dRec->quantity;
            $p->price       = $dRec->price;
            $p->uomId       = $dRec->uomId;
            
            $result->agreed->products[] = $p;
            
            if ($rec->isInstantShipment == 'yes') {
                $result->shipped->products[] = clone $p;
            }
        }
        
        return $result;
    }
    
    
    /**
     * Имплементация на @link bgerp_DealAggregatorIntf::getAggregateDealInfo()
     * 
     * @param int|object $id
     * @return bgerp_iface_DealResponse
     * @see bgerp_DealAggregatorIntf::getAggregateDealInfo()
     */
    public function getAggregateDealInfo($id)
    {
        $rec = new sales_model_Sale(self::fetchRec($id));
        
        // Извличаме продуктите на продажбата
        $detailRecs = $rec->getDetails('sales_SalesDetails', 'sales_model_SaleProduct');
        
        $result = new bgerp_iface_DealResponse();
        
        $result->dealType = bgerp_iface_DealResponse::TYPE_SALE;
        
        $result->agreed->amount                 = $rec->amountDeal;
        $result->agreed->currency               = $rec->currencyId;
        $result->agreed->delivery->location     = $rec->deliveryLocationId;
        $result->agreed->delivery->term         = $rec->deliveryTermId;
        $result->agreed->delivery->time         = $rec->deliveryTime;
        $result->agreed->payment->method        = $rec->paymentMethodId;
        $result->agreed->payment->bankAccountId = $rec->bankAccountId;
        $result->agreed->payment->caseId        = $rec->caseId;
        
        $result->paid->amount   = $rec->amountPaid;
        $result->paid->currency = $rec->currencyId;
        $result->paid->payment->method        = $rec->paymentMethodId;
        $result->paid->payment->bankAccountId = $rec->bankAccountId;
        $result->paid->payment->caseId        = $rec->caseId;

        $result->shipped->amount   = $rec->amountDelivered;
        $result->shipped->currency = $rec->currencyId;
        $result->shipped->delivery->location     = $rec->deliveryLocationId;
        $result->shipped->delivery->term         = $rec->deliveryTermId;
        $result->shipped->delivery->time         = $rec->deliveryTime;
        
        /* @var $dRec sales_model_SaleProduct */
        foreach ($detailRecs as $dRec) {
            /*
             * Договорени продукти
             */
            $aProd = new bgerp_iface_DealProduct();
            
            $aProd->classId     = $dRec->classId;
            $aProd->productId   = $dRec->productId;
            $aProd->packagingId = $dRec->packagingId;
            $aProd->discount    = $dRec->discount;
            $aProd->isOptional  = FALSE;
            $aProd->quantity    = $dRec->quantity;
            $aProd->price       = $dRec->price;
            $aProd->uomId       = $dRec->uomId;
            
            $result->agreed->products[] = $aProd;
            
            /*
             * Експедирани продукти
             */
            $sProd = clone $aProd;
            $sProd->quantity = $dRec->quantityDelivered;
            
            $result->shipped->products[] = $sProd;
            
            /*
             * Фактурирани продукти
             */
            $iProd = clone $aProd;
            $iProd->quantity = $dRec->quantityInvoiced;
            
            $result->invoiced->products[] = $iProd;
        }
        
        return $result;
    }
    
    
    /**
     * При нова продажба, се ънсетва threadId-то, ако има
     */
    static function on_AfterPrepareDocumentLocation($mvc, $form)
    {   
    	if($form->rec->threadId && !$form->rec->id){
		     unset($form->rec->threadId);
		}
    }
    
    
	/**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	if($rec->state != 'draft'){
    		acc_OpenDeals::saveRec($rec, $mvc);
    	}
    }
    
    
	/**
     * В кои корици може да се вкарва документа
     * @return array - интефейси, които трябва да имат кориците
     */
    public static function getAllowedFolders()
    {
    	return array('doc_ContragentDataIntf');
    }
}