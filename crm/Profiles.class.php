<?php


/**
 * Мениджър на потребителски профили
 *
 * @category  bgerp
 * @package   crm
 * @author    Stefan Stefanov <stefan.bg@gmail.com> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.12
 */
class crm_Profiles extends core_Master
{
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = array('crm_ProfileIntf');
    
    
    /**
     * 
     */
	var $details = 'Personalization=crm_Personalization';

	
    /**
     * Заглавие на мениджъра
     */
    var $title = "Профили";


    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Профил";
    

    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/user-profile.png';

    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'userId';

    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    var $loadList = 'plg_Created,crm_Wrapper,plg_RowTools, plg_Printing, plg_Search, plg_Rejected';

    
    /**
     * Кой  може да пише?
     */
    var $canWrite = 'admin';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'powerUser';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'powerUser';
    
    
    /**
     * Кой има право да листва всички профили?
     */
    var $canList = 'powerUser';

    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'crm/tpl/SingleProfileLayout.shtml';
    
    
    /**
     *Кой има достъп до единичния изглед
     */
    var $canSingle = 'powerUser';
    
    
    /**
     * Полета за списъчния изглед
     */
    var $listFields = 'userId,personId,lastLoginTime=Последно логване';
    
    
    /**
     * Кой може да види IP-то от последното логване
     */
    var $canViewip = 'powerUser';
    
    
    /**
     * Поле за търсене
     */
    var $searchFields = 'userId, personId';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('userId', 'key(mvc=core_Users, select=nick)', 'caption=Потребител,mandatory,notNull');
        $this->FLD('personId', 'key(mvc=crm_Persons, select=name, group=users)', 'input=hidden,silent,caption=Визитка,mandatory,notNull');
        $this->EXT('lastLoginTime',  'core_Users', 'externalKey=userId');
        $this->EXT('state',  'core_Users', 'externalKey=userId');
        $this->EXT('exState',  'core_Users', 'externalKey=userId');
        $this->EXT('lastUsedOn',  'core_Users', 'externalKey=userId');

        $this->setDbUnique('userId');
        $this->setDbUnique('personId');
    }
    
    
    /**
     * След подготовка на тулбара за единичния изглед
     */
    function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        // Премахваме edit бутона
        $data->toolbar->removeBtn('btnEdit');
        
        // Премахваме бутона за изтриване
        $data->toolbar->removeBtn('btnDelete');
    }
    
    
    /**
     * Подготовка за рендиране на единичния изглед
     */
    public static function on_AfterPrepareSingle(crm_Profiles $mvc, $data)
    {
        // Ако има personId
        if ($data->rec->personId) {

            // Създаваме обекта
            $data->Person = new stdClass();
            
            // Вземаме записите
            $data->Person->rec = crm_Persons::fetch($data->rec->personId);
            
            // Подготвяме сингъла
            crm_Persons::prepareSingle($data->Person);
            
            // Ако има права за сингъл
            if (crm_Persons::haveRightFor('single', $data->Person->rec)) {
                
                if ($data->Person->rec->id) {
                    // Добавяме бутон към сингъла на лицето
                    $data->toolbar->addBtn(tr('Визитка'), array('crm_Persons', 'single', $data->Person->rec->id), 'id=btnPerson', 'ef_icon = img/16/vcard.png');    
                }
            }
        }
        
        // Ако има userId
        if ($data->rec->userId) {
            
            // Създаваме обекта
            $data->User = new stdClass();
            
            // Вземаме записите
            $data->User->rec = core_Users::fetch($data->rec->userId);
            
            // Вземаме вербалните стойности на записите
            $data->User->row = core_Users::recToVerbal($data->User->rec);
            
            // Ако е текущия потребител
            if (core_Users::getCurrent() == $data->User->rec->id) {
                
                // URL за промяна на профила
                $changePassUrl =  array('crm_Profiles', 'changePassword', 'ret_url'=>TRUE);
                
                // Линк за промяна на URL
                $changePasswordLink = ht::createLink('(' . tr('cмяна') . ')', $changePassUrl, FALSE, 'title=' . tr('Смяна на парола'));
                
                // Променяме паролата
                $data->User->row->password = str_repeat('*', 7) . " " . $changePasswordLink;
                
                // Ако има роля admin
                if (haveRole('admin')) {
                    
                    // Иконата за редактиране
                    $img = "<img src=" . sbf('img/16/edit.png') . " width='16' height='16'>";
                    
                    // URL за промяна
                    $url = array('core_Users', 'edit', $data->rec->userId, 'ret_url' => TRUE);
                    
                    // Създаме линка
                    $data->User->row->editLink = ht::createLink($img, $url, FALSE, 'title=' . tr('Редактиране на потребителски данни'));  
                }
            } else {

                // Премахваме информацията, която не трябва да се вижда от другите
                unset($data->User->row->password);
            }
            
            if($data->User->rec->state != 'active') {
                $data->User->row->state = ht::createElement('span', array('class' => 'state-' . $data->User->rec->state, 'style' => 'padding:2px;'), $data->User->row->state);
            } else {
                unset($data->User->row->state);
            }
            
            // Ако има права за виждане на IP-то на последно логване
            if ($mvc->haveRightFor('viewip', $data->rec)) {
                
                // Ако има права за виждане на записите от лога
                if (core_LoginLog::haveRightFor('viewlog')) {
                    
                    // Създаваме обекта
                    $data->LoginLog = new stdClass();
                    
                    // Вземаме записите
                    $data->LoginLog->recsArr = core_LoginLog::getLastAttempts($data->rec->userId, 5);
                    
                    // Вземаме вербалните стойности
                    foreach ((array)$data->LoginLog->recsArr as $key => $logRec) {
                        
                        $data->LoginLog->rowsArr[$key] = core_LoginLog::recToVerbal($logRec);
                        
                        // Ако има зададен клас
                        if ($data->LoginLog->rowsArr[$key]->ROW_ATTR['class']) {
                            $data->LoginLog->rowsArr[$key]->logClass = $data->LoginLog->rowsArr[$key]->ROW_ATTR['class'];
                        } else {
                            $data->LoginLog->rowsArr[$key]->logClass = 'loginLog-' . $logRec->status;
                        }
                    }
                    
                    // Ако има роля admin
                    if (core_LoginLog::haveRightFor('list')) {
                        
                        // id на потребитяля за търсене
                        $userTeams = type_User::getUserFromTeams($data->rec->userId);
                        reset($userTeams);
                        $userId = key($userTeams);
                        
                        $attr['class'] = 'linkWithIcon';
        		        $attr['style'] = 'background-image:url(' . sbf('/img/16/page_go.png') . ');';
        		        $attr['title'] = tr('Логин лог за потребителя');
                        
                        // URL за промяна
                        $loginLogUrl = array('core_LoginLog', 'list', 'userId' => $userId, 'ret_url' => TRUE);
                        
                        $data->LoginLog->row = new stdClass();
                        
                        // Създаме линка
                        $data->LoginLog->row->loginLogLink = ht::createLink(tr("Още..."), $loginLogUrl, FALSE, $attr);  
                    }
                }
            } else {
                unset($data->User->row->lastLoginIp);
            }
        }
    }
    
    
    /**
     * След рендиране на единичния изглед
     * 
     * Използва crm_Persons::renderSingle() за да рендира асоциирата визитка така, както тя
     * би се показала във визитника.
     * 
     * По този начин използваме визитника (crm_Persons) за подготовка и рендиране на визитка, но
     * ефективно заобикаляме неговите ограничения за достъп. Целта е да осигурим достъп до 
     * всички профилни визитки
     * 
     * @param crm_Profiles $mvc
     * @param core_ET $tpl
     * @param stdClass $data
     */
    public static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        // Вземам шаблона за лицето
        $pTpl = new ET(tr('|*' . getFileContent('crm/tpl/SingleProfilePersonLayout.shtml')));
        
        // Заместваме данните
        $pTpl->placeObject($data->Person->row);
        
        // Заместваме в шаблона
        $tpl->prepend($pTpl, 'personInfo');
        
        // Вземаме шаблона за потребителя
        $uTpl = new ET(tr('|*' . getFileContent('crm/tpl/SingleProfileUserLayout.shtml')));
        
        // Заместваме данните
        $uTpl->placeObject($data->User->row);
        
        // Заместваме в шаблона
        $tpl->prepend($uTpl, 'userInfo');
        
        if ($data->LoginLog && $data->LoginLog->rowsArr) {
            // Вземаме шаблона за потребителя
            $lTpl = new ET(tr('|*' . getFileContent('crm/tpl/SingleProfileLoginLogLayout.shtml')));
            
            $logBlockTpl = $lTpl->getBlock('log');
            
            foreach ((array)$data->LoginLog->rowsArr as $rows) {
                $logBlockTpl->placeObject($rows);
                $logBlockTpl->append2Master();
            }
            
            // Заместваме данните
            $lTpl->append($data->LoginLog->row->loginLogLink, 'loginLogLink');
            
            // Заместваме в шаблона
            $tpl->prepend($lTpl, 'loginLog');
        }
    }
    
    
    /**
     * Екшън за смяна на парола
     * 
     * return core_ET
     */
    public function act_ChangePassword()
    {
        requireRole('powerUser');

        $form = $this->prepareChangePassword();
        
        // Въвежда и проверява формата за грешки
        $form->input();

        if ($form->isSubmitted()) {
           $this->validateChangePasswordForm($form);
           if(!$form->gotErrors()){
			
        		// Записваме данните
         		if (core_Users::setPassword($form->rec->passNewHash))  {
	                // Правим запис в лога
	                static::log('change_password');
	                
//             		if (EF_USSERS_EMAIL_AS_NICK) {
//             		    $userId = core_Users::fetchField(array("#email = '[#1#]'", $form->rec->email));
//                    } else {
//                        $userId = core_Users::fetchField(array("#nick = '[#1#]'", $form->rec->nick));
//                    }
//	                
//	                core_LoginLog::add('pass_change', $userId);
	                
	                // Редиректваме към предварително установения адрес
	                return new Redirect(getRetUrl(), "Паролата е сменена успешно");
            	}
			}
        }
        
        // Кои полета да се показват
        $form->showFields = (($form->fields['nick']) ? "nick" : "email") . ",passEx,passNew,passRe";
		
        // Получаваме изгледа на формата
        $tpl = $form->renderHtml();
        
        // Опаковаме изгледа
        $tpl = static::renderWrapping($tpl);
        
        return $tpl;
    }
    
    
    /**
     * Подготовка на формата за смяна на паролата
     */
    public function prepareChangePassword()
    {
    	 $form = cls::get('core_Form');
        
        //Ако е активирано да се използват имейлите, като никове тогава полето имейл го правим от тип имейл, в противен случай от тип ник
        if (EF_USSERS_EMAIL_AS_NICK) {
            //Ако използваме имейлите вместо никове, скриваме полето ник
            $form->FLD('email', 'email(link=no)', 'caption=Имейл,mandatory,width=100%');
            $nickField = 'email';
        } else {
            //Ако не използвам никовете, тогава полето трябва да е задължително
            $form->FLD('nick', 'nick(64)', 'caption=Ник,mandatory,width=100%');
            $nickField = 'nick';
        }
        
        $form->setDefault($nickField, core_Users::getCurrent($nickField));
        $form->setReadOnly($nickField);

        // Стара парола, когато се изисква при задаване на нова парола
        $passExHint = 'Въведете досегашната си парола';
        $form->FNC('passEx', 'password(allowEmpty,autocomplete=off)', "caption=Стара парола,input,hint={$passExHint},width=15em");
        $form->FNC('passExHash', 'varchar', 'caption=Хеш на старата парола,input=hidden');
        
        // Нова парола и нейния производен ключ
        $minLenHint = 'Паролата трябва да е минимум|* ' . EF_USERS_PASS_MIN_LEN . ' |символа';
        $form->FNC('passNew', 'password(allowEmpty,autocomplete=off)', "caption=Нова парола,input,hint={$minLenHint},width=15em");
        $form->FNC('passNewHash', 'varchar', 'caption=Хеш на новата парола,input=hidden');
        
        // Повторение на новата парола
        $passReHint = 'Въведете отново паролата за потвърждение, че сте я написали правилно';
        $form->FNC('passRe', 'password(allowEmpty,autocomplete=off)', "caption=Нова парола (пак),input,hint={$passReHint},width=15em");
    
        // Подготвяме лентата с инструменти на формата
        $form->toolbar->addSbBtn('Смяна', 'change_password', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close16.png');
        
        // Потготвяме заглавието на формата
        $form->title = 'Смяна на паролата';
        $form->rec->passExHash    = '';
        $form->rec->passNewHash   = '';
        
        core_Users::setUserFormJS($form);
        
        return $form;
    }
    
    
	/**
     * Проверка за валидност на формата за смяна на паролата
     * @param core_Form
     */
    public function validateChangePasswordForm(core_Form &$form)
    {
    	core_Users::calcUserForm($form);
		
		$rec = $form->rec;
		
		if (core_Users::fetchField (core_Users::getCurrent (), 'ps5Enc' ) != $rec->passExHash) {
			$form->setError ('passEx', 'Грешна стара парола' );
		}
		
		if ($rec->isLenOK == - 1) {
			$form->setError ('passNew', 'Паролата трябва да е минимум |* ' . EF_USERS_PASS_MIN_LEN . ' |символа' );
		} elseif ($rec->passNew != $rec->passRe) {
			$form->setError ('passNew,passRe', 'Двете пароли не съвпадат' );
		} elseif (!$rec->passNewHash) {
			$form->setError ('passNew,passRe', 'Моля, въведете (и повторете) новата парола' );
		}
    }
    
    
    /**
     *
     */
    public static function on_AfterInputEditForm(crm_Profiles $mvc, core_Form $form)
    {
        $form->rec->_syncUser = TRUE;
    }
    

    /**
     *
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $usersQuery = core_Users::getQuery();

        $opt = array();

        $query = self::getQuery();

        $used = array();

        while($rec = $query->fetch()) {
            if($rec->id != $data->form->rec->id) {
                $used[$rec->userId] = TRUE;
            }
        }
 
        while($uRec = $usersQuery->fetch("#state = 'active'")) {
            if(!$used[$uRec->id]) {
                $opt[$uRec->id] = $uRec->nick;
            }
        }
        
        $data->form->setOptions('userId', $opt);
        
        $addUserUrl = array(
            'core_Users', 
            'add', 
            'personId'=>Request::get('personId', 'int'), 
            'ret_url'=>getRetUrl()
        );

        $data->form->setField('personId', 'input');
        
        $data->form->toolbar->addBtn('Нов потребител', $addUserUrl, 'ef_icon = img/16/star_2.png');
    }
    
    
    /**
     * Промяна на данните на асоциирания потребител след запис на визитка
     * 
     * @param core_Mvc $mvc
     * @param stdClass $rec
     * @param core_Master $master
     */
    public static function on_AfterMasterSave(crm_Profiles $mvc, $personRec, core_Master $master)
    {
        if (get_class($master) != 'crm_Persons') {
            return;
            expect(get_class($master) == 'crm_Person'); // дали не е по-добре така?
        }
        
        // След промяна на профилна визитка, променяме името и имейла на асоциирания потребител
        static::syncUser($personRec);
    }
    
    
    /**
     * 
     */
    public static function on_AfterSave(crm_Profiles $mvc, $id, $profile)
    {
        if ($profile->_syncUser) {
            // Флага _sync се вдига само на crm_Profiles::on_AfterInputEditForm(). 
            $person = crm_Persons::fetch($profile->personId);
            $mvc::syncUser($person);
        }
    }


    /**
     * Визитката, асоциирана с потребителски акаунт
     * 
     * @param int $userId
     * @return stdClass
     */
    public static function getProfile($userId = NULL)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent('id');
        }
        
        if (!$profile = static::fetch("#userId = {$userId}")) {
            return NULL;
        }
        
        return crm_Persons::fetch($profile->personId);
    }
    
    
    /**
     * Синхронизиране на данните (имена, имейл) на профилната визитка с тези на потребител.
     * 
     * Ако лице с ключ $personId не съществува - създава се нов потребител.
     * 
     * 
     * @param int $personId key(mvc=crm_Persons), може и да е NULL
     * @param stdClass $user
     * @return int|boolean personId или FALSE при неуспешен запис
     */
    public static function syncPerson($personId, $user)
    {
        if ($user->_skipPersonUpdate) {
            // След запис на визитка се обновяват данните (имена, имейл) на асоциирания с нея 
            // потребител. Ако сме стигнали до тук по този път, не обновяваме отново данните
            // на визитката след промяна на потребителя, защото това води до безкраен цикъл!
            return;
        }
        
        if (!empty($personId)) {
            $person = crm_Persons::fetch($personId);
        }
        
        if (empty($person)) {
            $person = (object)array(
                'groupList' => '',
                'access'    => 'private',
                'email'     => ''
            );
            $profilesGroup = crm_Groups::fetch("#sysId = 'users'");
            $person->groupList = keylist::addKey($person->groupList, $profilesGroup->id);
            $mustSave = TRUE;
        }
        
        
        if(!empty($user->names) && ($person->name != $user->names)) {
            $person->name = $user->names;
            $mustSave = TRUE;
        }
        
        // Само ако записа на потребителя има 
        if(!empty($user->email) && (strpos($person->email, $user->email) === FALSE)) {
            $person->email     = type_Emails::prepend($person->email, $user->email);
            $mustSave = TRUE;
        }
        
        // Само ако досега визитката не е имала inCharge, променения потребител и става отговорник
        if(!$person->inCharge) {
        	
        	// Ако създадения потребител е contractor и няма powerUser
        	if(core_Users::haveRole('contractor', $user->id) && !core_Users::haveRole('powerUser', $user->id)){
        		
        		// За отговорник стават първия админ/ceo
        		$person->inCharge  = doc_FolderPlg::getDefaultInCharge();
        		
        		// Визитката се споделя до лицето
        		$person->shared = keylist::addKey('', $user->id);
        	} else {
        		
        		// Ако е powerUse Лицето става отговорник на папката си
        		$person->inCharge  = $user->id;
        	}
            
            $mustSave = TRUE;
        }

        $person->_skipUserUpdate = TRUE; // Флаг за предотвратяване на безкраен цикъл
        
        if($mustSave) {
            crm_Persons::save($person);
            
            return $person->id;
        }
    }
    
    
    /**
     * Обновяване на данните на асоциирания потребител след промяна във визитка
     * 
     * @param stdClass $personRec
     */
    public static function syncUser($personRec)
    {
        if ($personRec->_skipUserUpdate) {
            return;
        }
        
        $profile = static::fetch("#personId = {$personRec->id}");
        
        if (!$profile) {
            return;
        }
        
        // Обновяване на записа на потребителя след промяна на асоциираната му визитка
        if (!$userRec = core_Users::fetch($profile->userId)) {
            return;
        }
        
        if (!empty($personRec->email)) {
            // Вземаме първия (валиден!) от списъка с лични имейли на лицето
            $emails = type_Emails::toArray($personRec->email);
            if (!empty($emails)) {
                $userRec->email = reset($emails);
            }
        }
        
        if (!empty($personRec->name)) {
            $userRec->names = $personRec->name;
        }
        
        if (!empty($personRec->photo)) {
            $userRec->avatar = $personRec->photo; 
        }
        
        // Флаг за предотвратяване на безкраен цикъл след промяна на визитка
        $userRec->_skipPersonUpdate = TRUE;
        
        core_Users::save($userRec);
    }
    
    
    /**
     * URL към профилната визитка на потребител
     * 
     * @param string|int $user ако е числова стойност се приема за ид на потребител; иначе - ник
     * @return array URL към визитка; FALSE ако няма такъв потребител или той няма профилна визитка
     */
    public static function getUrl($userId)
    {
        // Извличаме профила (връзката м/у потребител и визитка)
        $personId = static::fetchField("#userId = {$userId}", 'id');

        if (!$personId) {
            
            // Няма профил или не е асоцииран с визитка
            return FALSE;
        }
        
        return array(get_called_class(), 'single', $personId);
    }
    
    
    /**
     * Метод за удобство при генерирането на линкове към потребителски профили
     * 
     * @param string|int $user @see crm_Profiles::getUrl()
     * @param string $title    @see core_Html::createLink()
     * @param string $warning  @see core_Html::createLink()
     * @param array $attr      @see core_Html::createLink()
     */
    public static function createLink($userId = NULL, $title = NULL, $warning = FALSE, $attr = array())
    {   
        if(!$userId) {
            $userId = core_Users::getCurrent();
        }

        if(!$title) {
            $userRec = core_Users::fetch($userId);
            $title = self::getUserTitle($userRec->nick);
        }

        $link = $title;
        
        $url  = static::getUrl($userId);

        if ($url) { 
            $attr['class'] .= ' profile';
            foreach (array('ceo', 'manager', 'officer', 'executive', 'contractor') as $role) {
                if (core_Users::haveRole($role, $userId)) {
                    $attr['class'] .= " {$role}"; break;
                } 
            }

            $before = time() - dt::mysql2timestamp($userRec->lastActivityTime);
            
            if($before < 5*60) {
                $attr['class'] .= ' active';
            } elseif($before > 60*60) {
                $attr['class'] .= ' inactive';
            }

            if($userRec->state != 'active') {
                $attr['class'] .= ' state-' . $userRec->state;
            }

            $attr['title'] = $userRec->names;

            $link = ht::createLink($title, $url, $warning, $attr);
        }
        
        return $link;
    }
    

    /**
     * Обработва ника на потребителя, така, че да изглежда добре 
     */
    static function getUserTitle($nick)
    {
        list($l, $r) = explode('@', $nick);
        $title = str_replace(' ', '&nbsp;', mb_convert_case(str_replace(array('.', '_'), array(' ', ' '), $l), MB_CASE_TITLE, "UTF-8"));
        if($r) {
            $title .= '@' . $r;
        }

        return $title;
    }


    /**
     * След подготовката на редовете на списъчния изглед
     * 
     * Прави ника и името линкове към профилната визитка (в контекста на crm_Profiles)
     * 
     * @param crm_Profiles $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListRows(crm_Profiles $mvc, $data)
    {
        $rows = &$data->rows;
        $recs = &$data->recs;
        
        if(count($rows)) {
            foreach ($rows as $i=>&$row) {
                $rec = &$recs[$i];
        
                if ($url = $mvc::getUrl($rec->userId)) {
                    
                    // Ако имаме права за сингъла, тогава създаваме линка
                    if (crm_Persons::haveRightFor('single', $rec->personId)) {
                        $personLink = array('crm_Persons', 'single', $rec->personId);
                    } else {
                        $personLink = NULL;
                    }
                    
                    $row->personId = ht::createLink($row->personId, $personLink, NULL, array('ef_icon' => 'img/16/vcard.png'));
                    $row->userId   = static::createLink($rec->userId, NULL, FALSE, array('ef_icon' => $mvc->singleIcon));
                }
            }
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
    	$data->listFilter->view = 'horizontal';
    	
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    	 
    	$data->listFilter->showFields = 'search';
        
        $data->listFilter->input('search', 'silent');
        
        $data->query->orderBy("lastLoginTime", "DESC");
    }
    
    
    /**
     * 
     * 
     * @param crm_Profiles $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param object $rec
     * @param id $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Ако редактираме или добавяме
        if ($action == 'edit' || $action == 'add') {
            
            // Ако текущия потребител не е userId
            if ($rec->userId != core_Users::getCurrent()) {
                
                // Изискваме роля admin
                $requiredRoles = 'admin';
            }
        }
        
        // Текущия потребител може да си види IP-то, admin и ceo могат на всичките
        if ($action == 'viewip') {
            if ($rec && ($rec->userId != $userId)) {
                if (!haveRole('ceo, admin')) {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
}
