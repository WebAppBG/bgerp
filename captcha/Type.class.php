<?php


/**
 * Колко минути да е активна информацията в кеша?
 */
defIfNot('CAPTCHA_LIFETIME', 10);


/**
 * Колко символа да е дълга капчата?
 */
defIfNot('CAPTCHA_LENGTH', 4);


/**
 * Колко да са високи символите?
 */
defIfNot('CAPTCHA_HEIGHT', 28);


/**
 * Колко да е широко изображението?
 */
defIfNot('CAPTCHA_WIDTH', round(0.70 * CAPTCHA_HEIGHT * CAPTCHA_LENGTH));


/**
 * С какъв тип да се правят записите в кеша?
 */
defIfNot('CAPTCHA_CACHE_TYPE', 'Captcha');


/**
 * Клас 'captcha_Type' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    captcha
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class captcha_Type extends core_Type {
    
    
    /**
     *  Рендира полето за въвеждане на Captcha
     */
    function renderInput_($name, $value="", $attr = array())
    {
        $attr['size'] = CAPTCHA_LENGTH;
        $attr['autocomplete'] = 'off';
        $attr['style'] = 'vertical-align:top;';
        $attr['title'] = tr('Тук въведете предходните цифри');
        
        $code = rand(1000, 9999);
        
        $handler = core_Cache::set(CAPTCHA_CACHE_TYPE, // Тип
        rand(1, 99999999), // Манипулатор
        $code, // Код, който се изписва с картинка
        CAPTCHA_LIFETIME, // Колко време да е валидна кепчата
        'minutes');
        
        $tpl = ht::createTextInput($name . "[value]", "", $attr);
        
        $url = toUrl(array('captcha_Type', 'img', $handler));
        
        $tpl->prepend("<img  align='absmiddle'  src='{$url}' width='" .
        CAPTCHA_WIDTH . "' height='" . CAPTCHA_HEIGHT . "' alt='captcha' border='0'>");
        
        $tpl->append("<input type='hidden' name='{$name}[handler]' value='{$handler}'>");
        
        return $tpl;
    }
    
    
    /**
     *  Проверява дали стойността съответства на записа в кеша
     */
    function fromVerbal($value)
    {
        $handler = (int) $value['handler'];
        
        expect($handler, 'Липсва хендлър за captcha.');
        
        $code = core_Cache::get(CAPTCHA_CACHE_TYPE, $handler);
        
        if(!$code) {
            $this->error = "Времето за разпознаване е изтекло. Пробвайте с друг код.";
            
            return FALSE;
        }
        
        core_Cache::remove(CAPTCHA_CACHE_TYPE, $handler);
        
        $value = trim($value['value']);
        
        if( $code == $value ) {
            
            return $value;
        } else {
            $this->error = "Некоректно разпознаване на кода";
            
            return FALSE;
        }
    }
    
    
    /**
     *  Връща png картинка, съдържаща цифрите от капчата
     *  От Request-а взема манипулатора на запис в кеша, който съдържа цифрите
     */
    function act_Img()
    {
        $width = CAPTCHA_WIDTH;
        $height = CAPTCHA_HEIGHT;
        
        $font = dirname(__FILE__) . '/fonts/arial.ttf';
        
        $code = core_Cache::get(CAPTCHA_CACHE_TYPE, Request::get('id'));
        
        /* font size will be 75% of the image height */
        $font_size = $height * 0.75;
        $image = @imagecreate($width, $height) or halt('Cannot initialize new GD image stream');
        
        /* set the colours */
        $background_color = imagecolorallocate($image, 255, 255, 255);
        $text_color = imagecolorallocate($image, 20, 40, 100);
        $noise_color = imagecolorallocate($image, 100, 120, 180);
        
        /* generate random dots in background */
        for( $i=0; $i<($width*$height)/3; $i++ ) {
            imagefilledellipse($image, mt_rand(0,$width), mt_rand(0,$height), 1, 1, $noise_color);
        }
        
        /* generate random lines in background */
        for( $i=0; $i<($width*$height)/150; $i++ ) {
            imageline($image, mt_rand(0,$width), mt_rand(0,$height), mt_rand(0,$width), mt_rand(0,$height), $noise_color);
        }
        
        /* create textbox and add text */
        $textbox = imagettfbbox($font_size, 0, $font, $code) or halt('Error in imagettfbbox function');
        $x = ($width - $textbox[4])/2;
        $y = ($height - $textbox[5])/2;
        
        imagettftext($image, $font_size, 0, $x, $y, $text_color, $font , $code) or halt('Error in imagettftext function');
        
        /* output captcha image to browser */
        header('Content-Type: image/jpeg');
        
        imagejpeg($image);
        
        imagedestroy($image);
    }
}