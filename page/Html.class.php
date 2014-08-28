<?php 


/**
 * Клас 'page_Html' - Общ шаблон за всички HTML страници
 *
 *
 * @category  ef
 * @package   page
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class page_Html extends core_ET {
    
    
    /**
     * Конструктор, който генерира лейаута на шаблона
     */
    function page_Html() {
        
        $bodyClass = Mode::is('screenMode', 'narrow') ? "narrow" : "wide";
        $this->core_ET(
            "<!doctype html>" .
            
            (Mode::is('screenMode', 'narrow') ?
                "\n<html [#OG_PREFIX#] xmlns=\"http://www.w3.org/1999/xhtml\">" :
                "\n<html [#OG_PREFIX#]>") . 
                
            "\n<head>" .
            "\n<meta name=\"viewport\" content=\"width=device-width, initial-scale=1, maximum-scale=2\">" .
            "\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=[#ENCODING#]\">" .
            "<!--ET_BEGIN META_OGRAPH-->[#META_OGRAPH#]<!--ET_END META_OGRAPH-->".
            "<!--ET_BEGIN META_DESCRIPTION-->\n<meta name=\"description\" content=\"[#META_DESCRIPTION#]\"><!--ET_END META_DESCRIPTION-->" .
            "<!--ET_BEGIN META_KEYWORDS-->\n<meta name=\"keywords\" content=\"[#META_KEYWORDS#]\"><!--ET_END META_KEYWORDS-->" .
            "<!--ET_BEGIN PAGE_TITLE-->\n<title>[#NOT_CNT#][#PAGE_TITLE#]</title><!--ET_END PAGE_TITLE-->" .
            "<!--ET_BEGIN STYLE_IMPORT-->\n<style type=\"text/css\">[#STYLE_IMPORT#]\n</style><!--ET_END STYLE_IMPORT-->" .
            "<!--ET_BEGIN STYLES-->\n<style type=\"text/css\">[#STYLES#]\n</style><!--ET_END STYLES-->" .
            "<!--ET_BEGIN HEAD-->[#HEAD#]<!--ET_END HEAD-->" .
            "\n</head>" .
            "\n<body<!--ET_BEGIN ON_LOAD--> onload=\"[#ON_LOAD#]\"<!--ET_END ON_LOAD--> class=\"{$bodyClass} [#BODY_CLASS_NAME#]\">" .
        	"<!--ET_BEGIN START_SCRIPTS-->\n<script type=\"text/javascript\">[#START_SCRIPTS#]\n</script><!--ET_END START_SCRIPTS-->" .
            "<!--ET_BEGIN PAGE_CONTENT-->[#PAGE_CONTENT#]<!--ET_END PAGE_CONTENT-->" .
            "<!--ET_BEGIN JQRUN-->\n<script type=\"text/javascript\">[#JQRUN#]\n</script><!--ET_END JQRUN-->" .
            "<!--ET_BEGIN SCRIPTS-->\n<script type=\"text/javascript\">[#SCRIPTS#]\n</script><!--ET_END SCRIPTS-->" .
            "<!--ET_BEGIN BROWSER_DETECT-->[#BROWSER_DETECT#]<!--ET_END BROWSER_DETECT-->" .
            "[#page_Html::addJs#]" .
            "\n</body>" .
            "\n</html>");
    }
    
    
    /**
     * Прихваща събитието 'output' на ЕТ, за да добави стиловете и javascripts
     */
    static function on_Output(&$invoker)
    {
        // Дали линковете да са абсолютни
        $absolute = (boolean)(Mode::is('text', 'xhtml'));

        $headers = $invoker->getArray('HTTP_HEADER');

        if(is_array($headers)) {
            foreach($headers as $hdr) {
                if($hdr{0} == '-') {
                    header_remove(substr($hdr, 1));
                } else {
                    header($hdr, TRUE);
                }
            }
        }
        
        $css = $invoker->getArray('CSS');
        
        $inst = cls::get(get_called_class());
        
        $css = $inst->prepareCssFiles($css);
        
        if(is_array($css)) {
            foreach($css as $file) {
                if(!preg_match('#^[^/]*//#', $file)) {
                    $file = sbf($file, '', $absolute);
                }
                
                $invoker->appendOnce("\n@import url(\"{$file}\");", "STYLE_IMPORT", TRUE);
            }
        }
        
        $js = $invoker->getArray('JS');
        
        $js = $inst->prepareJsFiles($js);
        
        if(is_array($js)) {
            foreach($js as $file) {
                if(!preg_match('#^[^/]*//#', $file)) {
                    $file = sbf($file, '', $absolute);
                }
                $invoker->appendOnce("\n<script type=\"text/javascript\" src=\"{$file}\"></script>", "HEAD", TRUE);
            }
        }
    }
    
    
    /**
     * Добавя JS
     * 
     * @return core_ET
     */
    static function addJs()
    {
        $tpl = new ET();
        
        // Показване на статус съобщения
        static::subscribeStatus($tpl);
        
        // Записване на избрания текст с JS
        static::saveSelTextJs($tpl);
        
        // Вземане на времето на бездействие в съответния таб
        static::idleTimerJs($tpl);
        
        $tpl->append("runOnLoad(scrollLongListTable);", "JQRUN");
      
        return $tpl;
    }
    
    
    /**
     * Показва статус съобщението
     * 
     * @param core_ET $tpl
     */
    static function subscribeStatus(&$tpl)
    {
        // Ако не е сетнато времето на извикване
        if (!$hitTime = Mode::get('hitTime')) {
            
            // Използваме текущото
            $hitTime = dt::mysql2timestamp();
        }
        
        // Добавяме в JS timestamp на извикване на страницата
        $tpl->appendOnce("var hitTime = {$hitTime};", 'SCRIPTS');
        
        // Извикваме показването на статусите
        $tpl->append(core_Statuses::subscribe(), 'STATUSES');
    }
    
    
    /**
     * Маркиране на избрания текст с JS
     * 
     * @param core_ET $tpl
     */
    static function saveSelTextJs(&$tpl)
    {
        // Скрипт, за вземане на инстанция на efae
        $tpl->appendOnce("\n runOnLoad(function(){getEO().saveSelText();});", 'SCRIPTS');
    }
    
    
    /**
     * Функция за вземане на времето на бездействие в съответния таб
     * 
     * @param core_ET $tpl
     */
    static function idleTimerJs(&$tpl)
    {
        // 
        $tpl->appendOnce("\n runOnLoad(function(){getEO().runIdleTimer();});", 'SCRIPTS');
    }
    
    
    /**
     * Ако няма кой да прихване извикването на функцията
     * 
     * @param array $css
     */
    function prepareCssFiles_($css)
    {
        
        return $css;
    }
    
    
    /**
     * Ако няма кой да прихване извикването на функцията
     * 
     * @param array $css
     */
    function prepareJsFiles_($css)
    {
        
        return $css;
    }
}
