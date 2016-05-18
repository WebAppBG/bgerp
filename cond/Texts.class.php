<?php


/**
 * Модул Пасаж
 *
 * @category  bgerp
 * @package   cond
 * @author    Kristiyan Serafimov <kristian.plamenov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cond_Texts extends core_Manager
{


    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'passage_Texts';


    /**
     * Заглавие
     */
    public $title = "Фрагменти";


    /**
     * Плъгини за зареждане
     */
    public $loadList = "plg_Created, plg_Sorting, plg_RowTools2, plg_Printing, cond_Wrapper, plg_Search, cond_DialogWrapper";


    /**
     * Избор на полетата, по които може да се осъществи търсенето
     */
    public $searchFields = "title, body";


    /**
     * Време за опресняване информацията при лист на събитията
     */
    var $refreshRowsTime = 5000;


    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,admin, powerUser';


    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,admin,powerUser';


    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,admin,powerUser';


    /**
     * Кой може да го разглежда?
     */
    var $canList = 'powerUser';


    /**
     * Кой може да разглежда сингъла на документите?
     */
    var $canSingle = 'ceo,admin';


    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,admin';


    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,admin,trans';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'body, created=Автор';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('title', 'varchar(256)', 'caption=Заглавие, oldFieldName = name');
        $this->FLD('body', 'richtext(rows=10,bucket=Comments, passage=Общи)', 'caption=Описание, mandatory');
        $this->FLD('access', 'enum(private=Персонален,public=Публичен)', 'caption=Достъп, mandatory');
        $this->FLD('lang', 'enum(bg,en)', 'caption=Език на пасажа');
        $this->FLD('group', 'keylist(mvc=cond_Groups,select=title)', 'caption=Група');
    }

    /**
     * Екшъна за показване на диалоговия прозорец за добавяне на пасаж
     */
    function act_Dialog()
    {
        Request::setProtected('groupName');

        Mode::set('wrapper', 'page_Dialog');

        // Вземаме callBack'а
        $callback = Request::get('callback', 'identifier');

        // Сетваме нужните променливи
        Mode::set('dialogOpened', TRUE);
        Mode::set('callback', $callback);
      // Mode::set('bucketId', $bucketId);


        // Вземаме шаблона
        $tpl = $this->act_List();

        // Връщаме шаблона
        return $tpl;
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $res
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     * @internal param string $requiredRoles
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
        if ($action == 'add') {
            if (Mode::get('dialogOpened')) {
                $res = 'no_one';
            }
        }

        if ($action == 'edit') {
            if (Mode::get('dialogOpened')) {
                $res = 'no_one';
            }
        }

    }
    /**
     * Извиква се преди рендирането на 'опаковката' на мениджъра
     *
     * @param core_Mvc $mvc
     * @param string $res
     * @param core_Et $tpl
     * @param object $data
     *
     * @return boolean
     */
    function on_BeforeRenderWrapping($mvc, &$res, &$tpl, $data=NULL)
    {
        // Ако е отворен в диалоговия прозорец
        if (Mode::get('dialogOpened')) {

            // Рендираме опаковката от друго място
            $res = $mvc->renderDialog($tpl);

            // Да не се извикат останалите и да не се рендира "опаковката"
            return FALSE;
        }
    }


    /**
     * Връща шаблона за диалоговия прозорец
     *
     * @param Core_Et $tpl
     *
     * @return core_ET $tpl
     */
    function renderDialog_($tpl)
    {
        return $tpl;
    }


    /**
     * Промяна да дължината на заглавието
     *
     * @param $mvc
     * @param $id
     * @param $rec
     * @param null $fields
     */
    static function on_BeforeSave($mvc, &$id, &$rec, $fields = NULL)
    {
        if(empty($rec->title)){
            list($title,) = explode("/n", $rec->body);
            $rec->title =   str::limitLen($title, 100);
        }
    }


    /**
     *
     * Поставянето на полета за търсене
     *
     * @param $mvc
     * @param $data
     *
     */
    static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $form = $data->listFilter;
        $form->FLD('author' , 'users(roles=powerUser, rolesForTeams=manager|ceo|admin, rolesForAll=ceo|admin)', 'caption=Автор, autoFilter');
        $form->FLD('langWithAllSelect', 'enum(,bg,en)', 'caption=Език на пасажа, placeholder=Всичко');

        Request::setProtected('groupName');
        $group = Request::get('groupName');

        if (isset($group)) {
            $groupId = cond_Groups::fetchField(array("#title = '[#1#]'", $group), 'id');

            $default = type_Keylist::fromArray(array($groupId => $groupId));

            $form->setDefault('group', $default);
        }

        $form->showFields = 'search,author,langWithAllSelect, group';
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $form->view = 'vertical';
        $form->class = 'simpleForm';

        $form->input();

            $rec = $form->rec;
            if($rec->author){
                $data->query->where("'{$rec->author}' LIKE CONCAT ('%|', #createdBy , '|%')");
            }
            if($rec->langWithAllSelect){

                $data->query->where(array("#lang = '[#1#]'", $rec->langWithAllSelect));
            }
            if($rec->group){
                $data->query->likeKeylist('group', $rec->group);
//                bp($data->query->where);
//                $data->query->where(array("#gropu = '[#1#]'", $rec->langWithAllSelect));
            }
        $data->query->orderBy('#createdOn', 'DESC');
    }


    /**
     * Променяне на вида на прозореца при отварянето му като диалог
     *
     * @param $mvc
     * @param $row
     * @param $rec
     * @param null $fields
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = NULL)
    {
        if (Mode::get('dialogOpened')) {
            $callback = Mode::get('callback');
            $str = json_encode($rec->body);

            $attr = array('onclick' => "if(window.opener.{$callback}($str) != true) self.close(); else self.focus();", "class" => "file-log-link");
//            $attr = array('onclick' => "console.log('test');", "class" => "file-log-link");
            $title = ht::createLink($rec->title, '#', FALSE, $attr);

            $string = str_replace(array("\r", "\n"), array('', ' '), $rec->body);

            Mode::push('text', 'plain');

            $string =  $mvc->fields['body']->type->toVerbal($string);

            Mode::pop('text');
            $rec->title = str::limitLen($title, 100);

            $string = substr_replace($string, "[hide]", 0, 0);
            $string = substr_replace($string, "[/hide]", strlen($string), 0);
            $string =  $mvc->fields['body']->type->toVerbal($string);
            $createdOn = $mvc->getVerbal($rec, 'createdOn');
            $createdBy = $mvc->getVerbal($rec, 'createdBy');

            $row->body = "<span class='passageHolder'>" . $title . $string . "</span>";
            $row->created = $createdOn . '<br>' . $createdBy;
        }
    }


    /**
    * Преди рендиране на таблицата
    */
    protected static function on_BeforeRenderListTable($mvc, &$res, $data)
    {
        $data->listTableMvc->FLD('created', 'varchar', 'tdClass=createdInfo');
    }
}