<?php



/**
 * Клас 'cms_Page' - Шаблон за публична страница
 *
 * Файлът може да се подмени с друг
 *
 *
 * @category  ef
 * @package   page
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class cms_Page extends page_Html {
    
    
    /**
     * Конструктор за страницата по подразбиране
     * Тази страница използва internal layout, header и footer за да 
     * покаже една обща обвивка за съдържанието за вътрешни потребители
     */
    function cms_Page()
    {
        // Конструктора на родителския клас
        $this->page_Html();
    	
        // Параметри от конфигурацията
        $conf = core_Packs::getConfig('core');
        $this->prepend(tr($conf->EF_APP_TITLE), 'PAGE_TITLE');

        // Ако е логнат потребител
        if (haveRole('user')) {
            
            // Абонираме за промяна на броя на нотификациите
            $this->appendOnce(new ET('[#bgerp_Notifications::subscribeCounter#]'));
        
            // Броя на отворените нотификации
            $openNotifications = bgerp_Notifications::getOpenCnt();
            
            // Ако имаме нотификации, добавяме ги към титлата и контейнера до логото
            if($openNotifications > 0) {
                
                // Добавяме броя в заглавието
                $this->append("({$openNotifications}) ", 'PAGE_TITLE');
            }
        }
        
        // Кодировка - UTF-8
        $this->replace("UTF-8", 'ENCODING');
        
        $this->push('css/common.css','CSS');
        $this->push('css/Application.css','CSS');
        $this->push('css/default-theme.css', 'CSS');
        $this->push('cms/css/Wide.css', 'CSS');
        jquery_Jquery::enable($this);
        $this->push('js/efCommon.js', 'JS');
        $this->push('js/overthrow-detect.js', 'JS');
        
        // Евентуално се кешират страници за не PowerUsers
        if(($expires = Mode::get('BrowserCacheExpires')) && !haveRole('powerUser')) {
            $this->push('Cache-Control: public', 'HTTP_HEADER');
            $this->push('Expires: ' . gmdate("D, d M Y H:i:s", time() + $expires) . ' GMT', 'HTTP_HEADER');
            $this->push('-Pragma', 'HTTP_HEADER');
        } else {
            $this->push('Cache-Control: private, max-age=0', 'HTTP_HEADER');
            //$this->push('Pragma: no-cache', 'HTTP_HEADER');
            $this->push('Expires: -1', 'HTTP_HEADER');
        }
        
        $this->appendOnce("\n<link  rel=\"shortcut icon\" href=" . sbf("img/favicon.ico", '"', TRUE) . " type=\"image/x-icon\">", "HEAD");
        
        $pageTpl = getFileContent('cms/tpl/Page.shtml');
        if(isDebug() && Request::get('Debug') && haveRole('debug')) {
            $pageTpl .= '[#Debug::getLog#]';
        }

        $this->replace(new ET($pageTpl), 'PAGE_CONTENT');
        
        // Скрипт за генериране на min-height, според устройството
        $this->append("runOnLoad(setMinHeightExt);", "JQRUN");
                
        // Добавка за разпознаване на браузъра
        $Browser = cls::get('core_Browser');
        $this->append($Browser->renderBrowserDetectingCode(), 'BROWSER_DETECT');

        // Добавяме основното меню
        $this->replace(cms_Content::getMenu(), 'CMS_MENU');
        
        // Добавяме лейаута
        $this->replace(cms_Content::getLayout(), 'CMS_LAYOUT');

    }

    
    /**
     * Прихваща изпращането към изхода, за да постави нотификации, ако има
     */
    static function on_Output(&$invoker)
    {
        // Генерираме хедъра и Линка към хедъра
        $invoker->appendOnce(cms_Feeds::generateHeaders(), 'HEAD');
        //$invoker->replace(cms_Feeds::generateFeedLink(), 'FEED');
        
        if (!Mode::get('lastNotificationTime')) {
            Mode::setPermanent('lastNotificationTime', time());    
        }

        // Добавяне на включвания външен код
        cms_Includes::insert($invoker);
    }


    /**
     * Връща картинката за главата на публичната страница
     */
    static function getHeaderImg() 
    {
        if(!Mode::is('screenMode', 'wide')) {
      	 	$screen = '-narrow';
        } else {
            $screen = '';
        }
        
        $lg = '-' . cms_Content::getLang();

        $path = "cms/img/header{$screen}{$lg}.jpg";
 
        if(!getFullPath($path)) {
            $path = "cms/img/header{$screen}.jpg";
            if(!getFullPath($path)) {
                $path = "cms/img/header.jpg";
                if(!getFullPath($path)) {
                    if(Mode::is('screenMode', 'wide')) {
      	 	            $path = "cms/img/bgERP.jpg";
                    } else {
      	 	            $path = "cms/img/bgERP-small.jpg";
                    }
                }
            }
        }

        $conf = core_Packs::getConfig('core');
        
        $img = ht::createElement('img', array('src' => sbf($path, ''), 'alt' => tr($conf->EF_APP_TITLE), 'id' => 'headerImg'));
        
        return $img;
    }

    
}
