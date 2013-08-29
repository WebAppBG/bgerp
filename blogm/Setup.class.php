<?php
/**
 * Константи за брой дни след които статията се заключва за коментиране
 */
defIfNot('BLOGM_MAX_COMMENT_DAYS', 50*24*60*60);


/**
 *  Константа за тема по-подразбиране на блога
 */
defIfNot('BLOGM_DEFAULT_THEME', '');


/**
 *  Константа за продължителноста на живота на бисквитките създадени от блога
 */
defIfNot('BLOGM_COOKIE_LIFETIME', '2592000');


/**
 *  Броя на статии, които да се показват
 */
defIfNot('BLOGM_ARTICLES_PER_PAGE', '5');


/**
 *  Код за споделяне на статия
 */
defIfNot('BLOGM_ARTICLE_SHARE', '');


/**
  * class blogm_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с Блога
 *
 *
 * @category  bgerp
 * @package   blogm
 * @author    Ивелин Димов <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class blogm_Setup extends core_ProtoSetup
{


	/**
	 * Версия на пакета
	 */
	var $version = '0.1';


	/**
	 * Мениджър - входна точка в пакета
	 */
	var $startCtr = 'blogm_Articles';


	/**
	 * Екшън - входна точка в пакета
	 */
	var $startAct = 'list';


	/**
	 * Описание на модула
	 */
	var $info = "Поддръжка на блог: категории, статии, коментари ...";

	/**
	 * Описание на конфигурационните константи
	 */
	
	
	/**
	 * Описание на конфигурационните константи
	 */
	var $configDescription = array(
			'BLOGM_DEFAULT_THEME' => array ('class(interface=blogm_ThemeIntf,select=title,allowEmpty)', 'caption=Тема по подразбиране в блога->Тема'),
			
			'BLOGM_MAX_COMMENT_DAYS' => array ('time(suggestions=15 дни|30 дни|45 дни|50 дни)', 'caption=Време, след което статията се заключва за коментиране->Дни'),

            'BLOGM_ARTICLE_SHARE' => array ('html', 'caption=Код за споделяне на статията->HTML код'),

            'BLOGM_ARTICLES_PER_PAGE' => array('int', 'caption=Броят на статии, които да се показват на страница->Бр. статии '),
	
	);
	
	
	/**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
				'blogm_Articles',
				'blogm_Categories',
				'blogm_Comments',
				'blogm_Links',
		);
	
		
		
    /**
     * Роли за достъп до модула
     */
    var $roles = 'blog';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.5, 'Сайт', 'Блог', 'blogm_Articles', 'list', "cms, blog, admin, ceo"),
        );
        
        
   /**
	* Инсталиране на пакета
	*/
	function install()
	{
		$html = parent::install();

        // Ако няма категории, създаваме някакви 
        if(!blogm_Categories::fetch('1=1')) {
            $cat = array('Новини' => 'Новостите за нашата фирма',
                         'Интересно' => 'Интересни неща за бизнеса и около него',
                         'Политика' => 'Позиция по въпроси касаещи България, Европа и света',
                         'Наука' => 'Достиженията на науката в нашия бизнес',
                         'Изкуство' => 'Творческото начало в бизнеса',
                         'Статистика' => 'Отчети и доклади, за любителите на цифрите',
                );
            foreach($cat as $title => $d) {
                $rec = (object) array('title' => $title, 'description' => $d);
                blogm_Categories::save($rec);
            }

        }
        
        $Bucket = cls::get('fileman_Buckets');
        $html  .= $Bucket->createBucket(blogm_Articles::FILE_BUCKET, 'Файлове към блог-статиите', '', '10MB', 'user', 'every_one');

		// Добавяме класа връщащ темата в core_Classes
        core_Classes::add('blogm_DefaultTheme');
        
		return $html;
	}


	/**
	 * Де-инсталиране на пакета
	 */
	function deinstall()
	{
		// Изтриване на пакета от менюто
		$res .= bgerp_Menu::remove($this);

		return $res;
	}
}