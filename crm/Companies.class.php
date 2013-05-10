<?php



/**
 * Константи за инициализиране на таблицата с контактите
 */
defIfNot('BGERP_OWN_COMPANY_ID', '1');


/**
 * Име на собствената компания (тази за която ще работи bgERP)
 */
defIfNot('BGERP_OWN_COMPANY_NAME', 'Моята Фирма ООД');





/**
 * Фирми
 *
 * Мениджър на фирмите
 *
 *
 * @category  bgerp
 * @package   crm
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 * @todo:     Да се документира този клас
 */
class crm_Companies extends core_Master
{
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = array(
        // Интерфейс на всички счетоводни пера, които представляват контрагенти
        'crm_ContragentAccRegIntf',
        
        // Интерфейс за всякакви счетоводни пера
        'acc_RegisterIntf',
        
        // Интерфейс за корица на папка
        'doc_FolderIntf',
        
        //Интерфейс за данните на контрагента
        'doc_ContragentDataIntf'
    );
    
    
    /**
     * Заглавие
     */
    var $title = "Фирми";
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Фирма";
    
    
    /**
     * Икона на единичния обект
     */
    var $singleIcon = 'img/16/office-building.png';
    
    
    /**
     * Класове за автоматично зареждане
     */
    var $loadList = 'plg_Created, plg_Modified, plg_RowTools, plg_State, 
                     Groups=crm_Groups, crm_Wrapper, crm_AlphabetWrapper, plg_SaveAndNew, plg_PrevAndNext,
                     plg_Sorting, fileman_Files, recently_Plugin, plg_Search, plg_Rejected, plg_Printing,
                     acc_plg_Registry,doc_FolderPlg, plg_LastUsedKeys,plg_Select';
    
    
    /**
     * Полетата, които ще видим в таблицата
     */
    var $listFields = 'id=№,nameList=Име,phonesBox=Комуникации,addressBox=Адрес,name=';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'name';
    
    
    /**
     * Полета по които се прави пълнотекстово търсене от плъгина plg_Search
     */
    var $searchFields = 'name,pCode,place,country,email,tel,fax,website,vatId,info';
    
    
    /**
     * Кой  може да пише?
     */
    var $canWrite = 'user';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'user';
    
    
    /**
     * Детайли, на модела
     */
    var $details = 'CompanyExpandData=crm_Persons,ContragentLocations=crm_Locations,Pricelists=price_ListToCustomers,
                    ContragentBankAccounts=bank_Accounts,ObjectLists=acc_Items,CourtReg=crm_ext_CourtReg';
    
    
    /**
     * @todo Чака за документация...
     */
    var $features = 'place, country';
    
    
    /**
     * @var crm_Groups
     */
    var $Groups;
    
    
    /**
     * Поле на модела съдържащо списък с групите, в които е включена фирмата.
     * 
     * Използва се от плъгина @link groups_Extendable 
     * 
     * @see groups_Extendable
     * @var string
     */
    var $groupsField = 'groupList';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'crm/tpl/SingleCompanyLayout.shtml';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    var $lastUsedKeys = 'groupList';
    
    
    /**
     * Предефинирани подредби на листовия изглед
     */
    var $listOrderBy = array(
        'alphabetic'    => array('Азбучно', '#name=ASC'),
        'last'          => array('Последно добавени', '#createdOn=DESC', 'createdOn=Създаване->На,createdBy=Създаване->От'),
        'modified'      => array('Последно променени', '#modifiedOn=DESC', 'modifiedOn=Модифициране->На,modifiedBy=Модифициране->От'),
        'vatId'      => array('Данъчен №', '#vatId=DESC', 'vatId=Данъчен №'),
        'pCode'      => array('Пощенски код', '#pCode=DESC', 'pCode=П. код'),
        'website'       => array('Сайт/Блог', '#website', 'website=Сайт/Блог'),
        );
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Име на фирмата
        $this->FLD('name', 'varchar(255)', 'caption=Фирма,class=contactData,mandatory,remember=info');
        $this->FNC('nameList', 'varchar', 'sortingLike=name');
        
        // Адресни данни
        $this->FLD('country', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Държава,remember,class=contactData');
        $this->FLD('pCode', 'varchar(16)', 'caption=П. код,recently,class=pCode');
        $this->FLD('place', 'varchar(64)', 'caption=Град,class=contactData');
        $this->FLD('address', 'varchar(255)', 'caption=Адрес,class=contactData');
        
        // Комуникации
        $this->FLD('email', 'emails', 'caption=Имейли,class=contactData');
        $this->FLD('tel', 'drdata_PhoneType', 'caption=Телефони,class=contactData');
        $this->FLD('fax', 'drdata_PhoneType', 'caption=Факс,class=contactData');
        $this->FLD('website', 'url', 'caption=Web сайт,class=contactData');
        
        // Данъчен номер на фирмата
        $this->FLD('vatId', 'drdata_VatType', 'caption=Данъчен №,remember=info,class=contactData');
        
        // Допълнителна информация
        $this->FLD('info', 'richtext(bucket=crmFiles)', 'caption=Бележки,height=150px,class=contactData');
        $this->FLD('logo', 'fileman_FileType(bucket=pictures)', 'caption=Лого');
                
        // В кои групи е?
        $this->FLD('groupList', 'keylist(mvc=crm_Groups,select=name,where=#allow !\\= \\\'persons\\\')', 'caption=Групи->Групи,remember,silent');
        
        // Състояние
        $this->FLD('state', 'enum(active=Вътрешно,closed=Нормално,rejected=Оттеглено)', 'caption=Състояние,value=closed,notNull,input=none');
    }
    
    
    /**
     * Подредба и филтър на on_BeforePrepareListRecs()
     * Манипулации след подготвянето на основния пакет данни
     * предназначен за рендиране на списъчния изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        // Подредба
        setIfNot($data->listFilter->rec->order, 'alphabetic');
        $orderCond = $mvc->listOrderBy[$data->listFilter->rec->order][1];
        if($orderCond) {
            $data->query->orderBy($orderCond);
        }

        
        if($data->listFilter->rec->alpha) {
            if($data->listFilter->rec->alpha{0} == '0') {
                $cond = "LTRIM(REPLACE(REPLACE(REPLACE(LOWER(#name), '\"', ''), '\'', ''), '`', '')) NOT REGEXP '^[a-zA-ZА-Яа-я]'";
            } else {
                $alphaArr = explode('-', $data->listFilter->rec->alpha);
                $cond = array();
                $i = 1;
                
                foreach($alphaArr as $a) {
                    $cond[0] .= ($cond[0] ? ' OR ' : '') .
                    "( LTRIM(REPLACE(REPLACE(REPLACE(LOWER(#name), '\"', ''), '\'', ''), '`', '')) LIKE LOWER('[#{$i}#]%'))";
                    $cond[$i] = $a;
                    $i++;
                }
            }
            
            $data->query->where($cond);
        }

        // Филтриране по потребител/и
        if(!$data->listFilter->rec->users) {
            $data->listFilter->rec->users = '|' . core_Users::getCurrent() . '|';
        }

        if(($data->listFilter->rec->users != 'all_users') && (strpos($data->listFilter->rec->users, '|-1|') === FALSE)) {  
            $data->query->where("'{$data->listFilter->rec->users}' LIKE CONCAT('%|', #inCharge, '|%')");
            $data->query->orLikeKeylist('shared', $data->listFilter->rec->users);
        }
                    
        if($data->groupId = Request::get('groupId', 'key(mvc=crm_Groups,select=name)')) {
            $data->query->where("#groupList LIKE '%|{$data->groupId}|%'");
        }
    }
    
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('users', 'users', 'caption=Потребител,input,silent', array('attr' => array('onchange' => 'this.form.submit();')));
        
        // По подразбиране кое да е избрано
        if (haveRole($data->listFilter->fields['users']->type->params['rolesForAll'])) {
            
            // Ако има права за всички, да са избани всички
            $data->listFilter->setDefault('users', 'all_users');    
        } else {
            
            // Текущия потребител
            $currUserId = core_Users::getCurrent();
        
            // Ако няма права за всички да е избран текущия потребител
            $data->listFilter->setDefault('users', "|$currUserId|"); 
        }
        
        // Подготовка на полето за подредба
        foreach($mvc->listOrderBy as $key => $attr) {
            $options[$key] = $attr[0];
        }
        $orderType = cls::get('type_Enum');
        $orderType->options = $options;
        $data->listFilter->FNC('order', $orderType, 'caption=Подредба,input,silent', array('attr' => array('onchange' => 'this.form.submit();')));
        
        // Филтриране по група
        $data->listFilter->FNC('groupId', 'key(mvc=crm_Groups,select=name,allowEmpty)',
            'placeholder=Всички групи,caption=Група,input,silent', array('attr' => array('onchange' => 'this.form.submit();')));
        $data->listFilter->FNC('alpha', 'varchar', 'caption=Буква,input=hidden,silent');
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'search,users,order,groupId';
        
        $rec = $data->listFilter->input('alpha,users,search,order,groupId', 'silent');
        
        // Според заявката за сортиране, показваме различни полета
        $showColumns = $mvc->listOrderBy[$data->listFilter->rec->order][2];

        if($showColumns) {
            $showColumns = arr::make($showColumns, TRUE);
            foreach($showColumns as $field => $title) {
                $data->listFields[$field] = $title;
            }
        }
    }
    
    
    /**
     * Премахване на бутон и добавяне на нови два в таблицата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        if($data->toolbar->removeBtn('btnAdd')) {
            if($groupId = $data->listFilter->rec->groupId) {
                $data->toolbar->addBtn('Нова фирма', array($mvc, 'Add', "groupList[{$groupId}]" => 'on'), 'id=btnAdd,class=btn-add');
            } else {
                $data->toolbar->addBtn('Нова фирма', array($mvc, 'Add'), 'id=btnAdd,class=btn-add');
            }
        }
    }
    
    
    /**
     * Модифициране на edit формата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
    	$conf = core_Packs::getConfig('crm');
    	
        $form = $data->form;
        
        if(empty($form->rec->id)) {
            // Слагаме Default за поле 'country'
            $Countries = cls::get('drdata_Countries');
            $form->setDefault('country', $Countries->fetchField("#commonName = '" .
                    $conf->BGERP_OWN_COMPANY_COUNTRY . "'", 'id'));
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    static function on_AfterInputeditForm($mvc, $form)
    {
        $rec = $form->rec;
        
        if($form->isSubmitted()) {
            
            // Правим проверка за дублиране с друг запис
            if(!$rec->id) {
                $nameL = "#" . plg_Search::normalizeText(STR::utf2ascii($rec->name)) . "#";
                
                $buzType = arr::make(strtolower(STR::utf2ascii("АД,АДСИЦ,ЕАД,ЕООД,ЕТ,ООД,КД,КДА,СД,LTD,SRL")));
                
                foreach($buzType as $word) {
                    $nameL = str_replace(array("#{$word}", "{$word}#"), array('', ''), $nameL);
                }
                
                $nameL = trim(str_replace('#', '', $nameL));
                
                $query = $mvc->getQuery();
                
                while($similarRec = $query->fetch(array("#searchKeywords LIKE '% [#1#] %'", $nameL))) {
                    $similars[$similarRec->id] = $similarRec;
                    $similarName = TRUE;
                }
                
                $vatNumb = preg_replace("/[^0-9]/", "", $rec->vatId);
                
                if($vatNumb) {
                    $query = $mvc->getQuery();
                    
                    while($similarRec = $query->fetch(array("#vatId LIKE '%[#1#]%'", $vatNumb))) {
                        $similars[$similarRec->id] = $similarRec;
                    }
                    $similarVat = TRUE;
                }
                
                if(count($similars)) {
                    foreach($similars as $similarRec) {
                        $similarCompany .= "<li>";
                        $similarCompany .= ht::createLink($mvc->getVerbal($similarRec, 'name'), array($mvc, 'single', $similarRec->id), NULL, array('target' => '_blank'));
                        
                        if($similarRec->vatId) {
                            $similarCompany .= ", " . $mvc->getVerbal($similarRec, 'vatId');
                        }
                        
                        if(trim($similarRec->place)) {
                            $similarCompany .= ", " . $mvc->getVerbal($similarRec, 'place');
                        } else {
                            $similarCompany .= ", " . $mvc->getVerbal($similarRec, 'country');
                        }
                        $similarCompany .= "</li>";
                    }
                    
                    $fields = ($similarVat && $similarName) ? "name,vatId" : ($similarName ? "name" : "vatId");
                    
                    $sledniteFirmi = (count($similars) == 1) ? "следната фирма" : "следните фирми";
                    
                    $form->setWarning($fields, "Възможно е дублиране със {$sledniteFirmi}|*: <ul>{$similarCompany}</ul>");
                }
            }
            
            if($rec->place) {
                $rec->place = drdata_Address::canonizePlace($rec->place);
            }
            
            if($rec->regCompanyFileYear && $rec->regDecisionDate) {
                $dYears = abs($rec->regCompanyFileYear - (int) $rec->regDecisionDate);
                
                if($dYears > 1) {
                    $form->setWarning('regCompanyFileYear,regDecisionDate', "Годината на регистрацията на фирмата и фирменото дело се различават твърде много.");
                }
            }
        }
    }
    
    
    /**
     * Манипулации със заглавието
     *
     * @param core_Mvc $mvc
     * @param core_Et $tpl
     * @param stdClass $data
     */
    static function on_AfterPrepareListTitle($mvc, &$tpl, $data)
    {
        if($data->listFilter->rec->groupId) {
            $data->title = "Фирми в групата|* \"<b style='color:green'>" .
            $mvc->Groups->getTitleById($data->groupId) . "</b>\"";
        } elseif($data->listFilter->rec->search) {
            $data->title = "Фирми отговарящи на филтъра|* \"<b style='color:green'>" .
            type_Varchar::escape($data->listFilter->rec->search) .
            "</b>\"";
        } elseif($data->listFilter->rec->alpha) {
            if($data->listFilter->rec->alpha{0} == '0') {
                $data->title = "Фирми, които започват с не-буквени символи";
            } else {
                $data->title = "Фирми започващи с буквите|* \"<b style='color:green'>{$data->listFilter->rec->alpha}</b>\"";
            }
        } else {
            $data->title = NULL;
        }
    }
    
    
    
    
    /**
     * Промяна на данните от таблицата
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec, $fields)
    {
        $row->nameList = new ET('[#1#]', $row->name);
        
        // $row->nameTitle = mb_strtoupper($rec->name);
        // $row->nameLower = mb_strtolower($rec->name);
        
        if($fields['-single']) {
            // Fancy ефект за картинката
            $Fancybox = cls::get('fancybox_Fancybox');
            
            $tArr = array(200, 150);
            $mArr = array(600, 450);
            
            if($rec->logo) {
                $row->image = $Fancybox->getImage($rec->logo, $tArr, $mArr);
            } elseif(!Mode::is('screenMode', 'narrow')) {
                $row->image = "<img class=\"hgsImage\" src=" . sbf('img/noimage120.gif') . " alt='no image'>";
            }
        }
        
        $row->country = $mvc->getVerbal($rec, 'country');
        
        $pCode = $mvc->getVerbal($rec, 'pCode');
        $place = $mvc->getVerbal($rec, 'place');
        $address = $mvc->getVerbal($rec, 'address');
        
        $row->addressBox = $row->country;
        $row->addressBox .= ($pCode || $place) ? "<br>" : "";
        
        $row->addressBox .= $pCode ? "{$pCode} " : "";
        $row->addressBox .= $place;
        
        $row->addressBox .= $address ? "<br/>{$address}" : "";
        
        $tel = $mvc->getVerbal($rec, 'tel');
        $fax = $mvc->getVerbal($rec, 'fax');
        $eml = $mvc->getVerbal($rec, 'email');
        
        // phonesBox
        $row->phonesBox .= $tel ? "<div class='telephone'>{$tel}</div>" : "";
        $row->phonesBox .= $fax ? "<div class='fax'>{$fax}</div>" : "";
        $row->phonesBox .= $eml ? "<div class='email'>{$eml}</div>" : "";
        $row->phonesBox = "<div style='max-width:400px;'>{$row->phonesBox}</div>";

        $row->title =  $mvc->getTitleById($rec->id);
        
        $vatType = new drdata_VatType();
        
        $vat = $vatType->toVerbal($rec->vatId);
        
        $row->title .= ($vat ? "&nbsp;&nbsp;<div style='display:inline-block'>{$vat}</div>" : "");
        $row->nameList .= ($vat ? "<div style='font-size:0.8em;margin-top:5px;'>{$vat}</div>" : "");
        
        //bp($row);
        // END phonesBox
    }
    
    
    /**
     * След всеки запис (@see core_Mvc::save_())
     */
    static function on_AfterSave(crm_Companies $mvc, &$id, $rec, $saveFileds = NULL)
    {
        if($rec->groupList) {
            $mvc->updateGroupsCnt = TRUE;
        }
        $mvc->updatedRecs[$id] = $rec;
        
        /**
         * @TODO Това не трябва да е тук, но по някаква причина не сработва в on_Shutdown()
         */
        $mvc->updateRoutingRules($rec);
    }
    
    
    /**
     * Рутинни действия, които трябва да се изпълнят в момента преди терминиране на скрипта
     */
    static function on_Shutdown($mvc)
    {
        if($mvc->updateGroupsCnt) {
            $mvc->updateGroupsCnt();
        }
        
        if(count($mvc->updatedRecs)) {
            foreach($mvc->updatedRecs as $id => $rec) {
                $mvc->updateRoutingRules($rec);
            }
        }
    }
    
    
    /**
     * Прекъсва връзките на изтритите визитки с всички техни имейл адреси.
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param core_Query $query
     */
    static function on_AfterDelete($mvc, &$res, $query)
    {
        $mvc->updateGroupsCnt = TRUE;
        
        foreach ($query->getDeletedRecs() as $rec) {
            // изтриваме всички правила за рутиране, свързани с визитката
            email_Router::removeRules('company', $rec->id);
        }
    }
    
    
    /**
     * Обновява информацията за количеството на визитките в групите
     */
    function updateGroupsCnt()
    {
        $query = $this->getQuery();
        
        while($rec = $query->fetch()) {
            $keyArr = keylist::toArray($rec->groupList);
            
            foreach($keyArr as $groupId) {
                $groupsCnt[$groupId]++;
            }
        }
        
        // Вземаме id' тата на всички групи
        $groupsArr = crm_Groups::getGroupRecsId();
        
        // Обхождаме масива
        foreach ($groupsArr as $id) {
            
            // Записа, който ще обновим
            $groupsRec = new stdClass();
            
            // Броя на фирмите в съответната група
            $groupsRec->companiesCnt = (int)$groupsCnt[$id];
            
            // id' то на групата
            $groupsRec->id = $id;
            
            // Обновяваме броя на фирмите
            crm_Groups::save($groupsRec, 'companiesCnt');    
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function updateRoutingRules($rec)
    {
        if ($rec->state == 'rejected') {
            // Визитката е оттеглена - изтриваме всички правила за рутиране, свързани с нея
            email_Router::removeRules('company', $rec->id);
        } else {
            if ($rec->email) {
                static::createRoutingRules($rec->email, $rec->id);
            }
        }
    }
    
    /**
     * Създава `From` и `Doman` правила за рутиране след запис на визитка
     *
     * Използва се от @link crm_Companies::updateRoutingRules() като инструмент за добавяне на
     * правила
     *
     * @access protected
     * @param mixed $emails един или повече имейли, зададени като стринг или като масив 
     * @param int $objectId
     */
    public static function createRoutingRules($emails, $objectId)
    {
        // Приоритетът на всички правила, генериране след запис на визитка е нисък и намаляващ с времето
        $priority = email_Router::dateToPriority(dt::now(), 'low', 'desc');

        // Нормализираме параметъра $emails - да стане масив от валидни имейл адреси
        if (!is_array($emails)) {
            $emails = type_Emails::toArray($emails);
        }
        
        foreach ($emails as $email) {
            // Създаване на `From` правило
            email_Router::saveRule(
                (object)array(
                    'type' => email_Router::RuleFrom,
                    'key' => email_Router::getRoutingKey($email, NULL, email_Router::RuleFrom),
                    'priority' => $priority,
                    'objectType' => 'company',
                    'objectId' => $objectId
                )
            );
            
            // Създаване на `Domain` правило
            if ($key = email_Router::getRoutingKey($email, NULL, email_Router::RuleDomain)) {
                // $key се генерира само за непублични домейни (за публичните е FALSE), така че това 
                // е едновременно индиректна проверка дали домейнът е публичен.
                email_Router::saveRule(
                    (object)array(
                        'type' => email_Router::RuleDomain,
                        'key' => $key,
                        'priority' => $priority,
                        'objectType' => 'company',
                        'objectId' => $objectId
                    )
                );
            }
        }
    }


    /**
     * Връща информацията, която има за нашата фирма
     */
    static function fetchOurCompany()
    {
        $conf = core_Packs::getConfig('crm');
        $rec = self::fetch($conf->BGERP_OWN_COMPANY_ID);
        $rec->classId = core_Classes::fetchIdByName('crm_Companies');
        
        return $rec;
    }
    
    
    /**
     * Ако е празна таблицата с контактите я инициализираме с един нов запис
     * Записа е с id=1 и е с данните от файла bgerp.cfg.php
     *
     * @param unknown_type $mvc
     * @param unknown_type $res
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
    	$conf = core_Packs::getConfig('crm');
    	
        if(Request::get('Full')) {
            
            $query = $mvc->getQuery();
            
            while($rec = $query->fetch()) {
                if($rec->id == $conf->BGERP_OWN_COMPANY_ID) {
                    $rec->state = 'active';
                } elseif($rec->state == 'active') {
                    $rec->state = 'closed';
                }
                
                $mvc->save($rec, 'state');
            }
        }
    }
    

    /**
     * Изпълнява се след инсталацията
     */
    static function loadData()
    {
        $conf = core_Packs::getConfig('crm');
        
        if (!static::fetch($conf->BGERP_OWN_COMPANY_ID)){

            $rec = new stdClass();
            $rec->id = $conf->BGERP_OWN_COMPANY_ID;
            $rec->name = $conf->BGERP_OWN_COMPANY_NAME;
            
            //$rec->groupList = '|7|';
            $groupList = cls::get('crm_Groups');
            $group = 'Свързани лица';
            $rec->groupList = "|". $groupList->fetchField("#name = '{$group}'", 'id') . "|";
            
            // Страната не е стринг, а id
            $Countries = cls::get('drdata_Countries');
            $rec->country = $Countries->fetchField("#commonName = '" . $conf->BGERP_OWN_COMPANY_COUNTRY . "'", 'id');
            
            if(static::save($rec, NULL, 'REPLACE')) {
                
                $html = "<li style='color:green'>Фирмата " . $conf->BGERP_OWN_COMPANY_NAME . " е записана с #id=" .
                $conf->BGERP_OWN_COMPANY_ID . " в базата с константите</li>";
            }
        }
        
        return $html;
    }
    
    
	/**
     * Дали на фирмата се начислява ДДС:
     * Не начисляваме ако:
     * 		1 . Не е от ЕС
     * 		2.  Има ЕИК от ЕС, различен от BG
     * Ако няма държава начисляваме ДДС
     * @param int $id - id' то на записа
     * @return boolean TRUE/FALSE
     */
    static function getDefaultVat($id)
    {
        $rec = static::fetch($id);
        if(!$rec->country) return TRUE;
        
        return drdata_Vats::isValidVat($rec->vatId, $rec->country);
    }
    
    
    /**
     * Фирмата, от чието лице работи bgerp (BGERP_OWN_COMPANY_ID)
     * 
     * @return stdClass @see doc_ContragentDataIntf::getContragentData()
     */
    public static function fetchOwnCompany()
    {
        $conf = core_Packs::getConfig('crm');
        
        return static::getContragentData($conf->BGERP_OWN_COMPANY_ID);
    }
    
    
    /****************************************************************************************
     *                                                                                      *
     *  Методи на интерфейс "doc_FoldersIntf"                                               *
     *                                                                                      *
     ****************************************************************************************/
    
    
    /**
     * Връща заглавието на папката
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
        // Конфигурационните данните
    	$conf = core_Packs::getConfig('crm');
    	
    	// Заглавието
        $title = $rec->name;
        
        // Ако е зададена държава
        if ($rec->country) {
            
            // Името на дръжавата
            $commonName = mb_strtolower(drdata_Countries::fetchField($rec->country, 'commonName'));    
            $country = self::getVerbal($rec, 'country');
        }
        
        // Ако е зададен града и държавата не е същата
        if($rec->place && ($commonName == mb_strtolower($conf->BGERP_OWN_COMPANY_COUNTRY))) {
            
            // Добавяме града
            $title .= ' - ' . $rec->place;
        } elseif ($country) {
            
            // Или ако има държава
            $title .= ' - ' . $country;
        }
        
        // Ако е зададено да се ескейпва
        if($escaped) {
            
            // Ескейпваваме заглавието
            $title = type_Varchar::escape($title);
        }
        
        return $title;
    }
    
    /*******************************************************************************************
     * 
     * ИМПЛЕМЕНТАЦИЯ на интерфейса @see crm_ContragentAccRegIntf
     * 
     ******************************************************************************************/
    
    
    /**
     * @see crm_ContragentAccRegIntf::getItemRec
     * @param int $objectId
     */
    static function getItemRec($objectId)
    {
        $self = cls::get(__CLASS__);
        $result = NULL;
        
        if ($rec = $self->fetch($objectId)) {
            $result = (object)array(
                'num' => $rec->id,
                'title' => $rec->name,
                'features' => 'foobar' // @todo!
            );
        }
        
        return $result;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::getLinkToObj
     * @param int $objectId
     */
    static function getLinkToObj($objectId)
    {
        $self = cls::get(__CLASS__);
        
        if ($rec = $self->fetch($objectId)) {
            $result = ht::createLink(static::getVerbal($rec, 'name'), array($self, 'Single', $objectId));
        } else {
            $result = '<i>неизвестно</i>';
        }
        
        return $result;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     * @param int $objectId
     */
    static function itemInUse($objectId)
    {
        // @todo!
    }
    
    /**
     * КРАЙ НА интерфейса @see acc_RegisterIntf
     */
    
    
    /**
     * Връща данните на фирмата
     * @param integer $id    - id' то на записа
     * @param email   $email - Имейл
     *
     * return object
     */
    static function getContragentData($id)
    {
        //Вземаме данните от визитката
        $company = crm_Companies::fetch($id);
        
        //Заместваме и връщаме данните
        if ($company) {
            $contrData = new stdClass();
            $contrData->company = $company->name;
            $contrData->companyId = $company->id;
            $contrData->vatNo = $company->vatId;
            $contrData->tel = $company->tel;
            $contrData->fax = $company->fax;
            $contrData->country = crm_Companies::getVerbal($company, 'country');
            $contrData->countryId = $company->country;
            $contrData->pCode = $company->pCode;
            $contrData->place = $company->place;
            $contrData->address = $company->address;
            $contrData->email = $company->email;
            
            // Вземаме груповите имейли
            $contrData->groupEmails = crm_Persons::getGroupEmails($company->id);    
        }

        return $contrData;
    }
    
    
    /**
     * Създава папка на фирма по указаните данни
     */
    static function getCompanyFolder($company, $country, $pCode, $place, $address, $email, $tel, $fax, $website, $vatId)
    {
        $rec = new stdClass();
        $rec->name = $company;
        
        // Адресни данни
        $rec->country = $country;
        $rec->pCode = $pCode;
        $rec->place = $place;
        $rec->address = $address;
        
        // Комуникации
        $rec->email = $email;
        $rec->tel   = $tel;
        $rec->fax   = $fax;
        $rec->website = $website;
        
        // Данъчен номер на фирмата
        $rec->vatId = $vatId;
        
        $Companies = cls::get('crm_Companies');
        
        $folderId = $Companies->forceCoverAndFolder($rec);
         
        return $folderId;
    }
    
    
    /**
     * 
     */
    function on_AfterPrepareRetUrl($mvc, $res, $data)
    {
        // Ако е субмитната формата и не сме натиснали бутона "Запис и нов"
        if ($data->form && $data->form->isSubmitted() && $data->form->cmd != 'save_n_new') {
            
            // Променяма да сочи към single'a
            $data->retUrl = toUrl(array($mvc, 'single', $data->form->rec->id));
        }
    }

    
    /**
     * Функция, която задава правата за достъп до дадена фирма в търсенето
     * 
     * Вземаме всики папки на които сме inCharge или са споделени с нас или са публични или 
     * (са екипни и inCharge е някой от нашия екип) и състоянието е активно
     * 
     * @param crm_Persons $query - Заявката към системата
     * @param int $userId - Потребителя, за който ще се отнася
     */
    static function applyAccessQuery(&$query, $userId = NULL)
    {
        // Ако няма зададен потребител
        if (!$userId) {
            
            // Вземаме текущия
            $userId = core_Users::getCurrent();
        }
        
        $user = "|" . $userId . "|";
        
        // Вземаме членовете на екипа
        $teammates = core_Users::getTeammates($userId);
        
        // Проверка дали не е inCharge
        $query->where("'{$user}' LIKE CONCAT('%|', #inCharge, '|%')");
        
        // Проверка дали не е споделен към потребителя
        $query->orLikeKeylist('shared', $user);
        
        // Вземаме всички публични
        $query->orWhere("#access = 'public'");
        
        // Ако достъпа е отборен и собственика е екипа на потребителя
        $query->orWhere("#access = 'team' AND '{$teammates}' LIKE CONCAT('%|', #inCharge, '|%')");
        
        // Състоянието да е активно
        $query->where("#state = 'active'");
    }
    

    /**
     * Манипулация на списъка с екстендерите
     * 
     * @param core_Master $master
     * @param array $extenders @see groups_Manager::extendersArr
     * @param stdClass $rec запис на crm_Companies    
     */
    public static function on_AfterGetExtenders(core_Master $master, &$extenders, $rec)
    {
        // Премахваме от списъка екстендерите, които не могат да бъдат приложени към фирми
        $extenders = array_diff_key($extenders, arr::make('idCard, profile', TRUE));
    }
    
    
    /**
     * Връща папката на фирмата от имейла, ако имаме достъп до нея
     * 
     * @param email $email - Имейл, за който търсим
     * 
     * @return integet $fodlerId - id на папката
     */
    static function getFolderFromEmail($email)
    {
        // Имейла в долния регистър
        $email = mb_strtolower($email);
    
        // Вземаме компанията с този имейл
        $companyId = static::fetchField(array("LOWER(#email) LIKE '%[#1#]%'", $email));
        
        // Ако има такава компания
        if ($companyId) {
            
            // Вземаме папката на фирмата
            $folderId = static::forceCoverAndFolder($companyId);
            
            // Проверяваме дали имаме права за папката
            if (doc_Folders::haveRightFor('single', $folderId)) {

                return $folderId;
            }  
        }
        
        return FALSE;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass|NULL $rec
     * @param int|NULL $userId
     */
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Никой да не може да изтрива
        if ($action == 'delete') {
            $requiredRoles = 'no_one';
        }
    }
    
    
    /**
     * Премахва данните за нашата фирма - id на компанията, име на компанията, адрес, телефон, факс и имейли
     * 
     * @param std_Object &$contrData - Обект от който ще се премахва
     */
    static function removeOwnCompanyData(&$contrData)
    {
        // Записи за нашата компания
        $ownCompany = static::fetchOwnCompany();
        
        // Ако id' то е в данните на контрагента
        if ($ownCompany->companyId == $contrData->companyId) {
            
            // Премахваме id' тото 
            $contrData->companyId = NULL;
            
            // Премахваме името на компанията
            $contrData->company = NULL;
        }
        
        // Ако името на компанията съвпада
        if (mb_strtolower($ownCompany->company) == mb_strtolower($contrData->company)) {
            
            // Премахваме от списъка
            $contrData->company = NULL;
        }
        
        // Ако има открити телефони
        if ($ownCompany->tel && $contrData->tel) {
            
            // Масив с телефони на нашата компания
            $oTelArr = drdata_PhoneType::toArray($ownCompany->tel);
            
            // Масив с телефони на контрагента
            $cTelArr = drdata_PhoneType::toArray($contrData->tel);
            
            // Обхождаме масива с телефони на нашата фирма
            foreach ($oTelArr as $oTel) {
                
                // Обхождаме масива с телефони на контрагента
                foreach ($cTelArr as $key => $cTel) {
                    
                    // Ако телефона е същия
                    if (($cTel->countryCode == $oTel->countryCode) && ($cTel->areaCode == $oTel->areaCode)
                        && ($cTel->number == $oTel->number)) {
                            
                        // Премахваме от масива на контрагента
                        unset($cTelArr[$key]);
                    }
                }
            }

            // Обхождаме останалия масив
            foreach ($cTelArr as $cTel) {
                
                // Добавяме в стринга телефона
                $newCTel .= ($newCTel) ? ', ' . $cTel->original : $cTel->original;
            }
            
            // Заместваме новия стринг с данните на котрагента
            $contrData->tel = $newCTel;
        }
        
        // Ако има открити факсове
        if ($ownCompany->fax && $contrData->fax) {
            
            // Масив с факсове на нашата компания
            $oFaxArr = drdata_PhoneType::toArray($ownCompany->fax);
            
            // Масив с факсове на контрагента
            $cFaxArr = drdata_PhoneType::toArray($contrData->fax);

            // Обхождаме масива с факсове на нашата фирма
            foreach ($oFaxArr as $oFax) {
                
                // Обхождаме масива с факсове на контрагента
                foreach ($cFaxArr as $key => $cFax) {
                    
                    // Ако факса е същия
                    if (($cFax->countryCode == $oFax->countryCode) && ($cFax->areaCode == $oFax->areaCode)
                        && ($cTel->number == $oFax->number)) {
                            
                        // Премахваме от масива на контрагента
                        unset($cFaxArr[$key]);
                    }
                }
            }
            
            // Обхождаме останалия масив
            foreach ($cFaxArr as $cFax) {
                
                // Добавяме в стринга факса
                $newCFax .= ($newCFax) ? ', ' . $cFax->original : $cFax->original;
            }
            
            // Заместваме новия стринг с данните на котрагента
            $contrData->fax = $newCFax;
        }

        // Ако адреса е същия
        if (mb_strtolower($ownCompany->address) == mb_strtolower($contrData->address)) {
            
            // Премахваме от данните
            $contrData->address = NULL;
        }
        
        // Ако има имейли
        if ($ownCompany->email && $contrData->email) {
            
            // Масив с имейлите на нашата компания
            $oEmailArr = type_Emails::toArray($ownCompany->email);
            
            // Масив с имейлите на контрагента
            $cEmailArr = type_Emails::toArray($contrData->email);
            
            // Ако има имейли
            if (count($oEmailArr) && count($cEmailArr)) {
                
                // Ключа на масивите е същата със стойността
                $oEmailArr = array_combine($oEmailArr, $oEmailArr);
                $cEmailArr = array_combine($cEmailArr, $cEmailArr);
                
                // Обхождаме масива с имейли на нашата фирма
                foreach ($oEmailArr as $oEmail) {
                    
                    // Ако стойността я има в масива на контрагента, премахваме го
                    if ($cEmailArr[$oEmail]) unset($cEmailArr[$oEmail]);
                }
                
                // Останалите имейли ги записваме в имейли, като стринг
                $contrData->email = type_Emails::fromArray($cEmailArr);
            }
        }
        
        // Ако има групови имейли
        if ($ownCompany->email && $contrData->groupEmails) {
            
            // Ако не сме намерили масива преди
            if (!$oEmailArr) {
                
                // Всички имейли в масив
                $oEmailArr = type_Emails::toArray($ownCompany->email);
                
                // Ако има стойнност
                if (count($oEmailArr)) {
                    
                    // Ключовете да са равни със стойностите
                    $oEmailArr = array_combine($oEmailArr, $oEmailArr);    
                }
            }
            
            // Масив с груповите имейли
            $cGroupEmailArr = type_Emails::toArray($contrData->groupEmails);

            // Ако има стойности в масива
            if (count($cGroupEmailArr)) {
                
                // Ключовете да са равни със стойностите
                $cGroupEmailArr = array_combine($cGroupEmailArr, $cGroupEmailArr);
                
                // Обхождаме масива с имейлите на нашата фирма
                foreach ($oEmailArr as $oEmail) {
                    
                    // Ако имейла е в масива премахваме го от груповите
                    if ($cGroupEmailArr[$oEmail]) unset($cGroupEmailArr[$oEmail]);
                }
            }
            
            // Останалите имейли ги записва в груповите
            $contrData->groupEmails = type_Emails::fromArray($cGroupEmailArr);
        }

        // Ако сме премахнали имейлите и има имейли в групите
        if (!$contrData->email && count($cGroupEmailArr)) {
            
            // Добавяме първия в имейлите
            $contrData->email = key($cGroupEmailArr);
        }
    }
}
