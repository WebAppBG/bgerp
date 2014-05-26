<?php



/**
 * Клас 'newsbar_Plugin'
 *
 * Прихваща събитията на plg_ProtoWrapper и добавя, ако е има помощна информация в newsbar_Nesw, като бар лента
 *
 *
 * @category  bgerp
 * @package   newsbar
 * @author    Gabriela Petrova <gpetrova@experta.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class newsbar_Plugin extends core_Plugin
{
    static function on_Output(&$invoker)
    {
       // взимаме всички нови новини
       $str = newsbar_News::getTopNews();
      
       if($str->news !== NULL && $str->color !== NULL && $str->transparency !== NULL) { 
           $convertText = cls::get('type_Richtext');
           $barNews = $convertText->toVerbal($str->news);
           
           $html = new ET("<div class='newsbar' style='opacity:[#transparency#];background-color:[#color#];'>
            <marquee scrollamount='4'><b>$barNews</b></marquee>
            </div><div class='clearfix21'></div>");
           
           $html->replace($str->color, 'color');
           $html->replace($str->transparency, 'transparency');
     
           $invoker->appendOnce($html, 'PAGE_HEADER');
       }
    }

}