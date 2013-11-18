<?php


/**
 * Име на под-директория  в sbg/EF_APP_NAME, където се намират умалените изображения
 */
defIfNot('IMG_THUMB_DIR', '_tb_');


/**
 * Пълен път до директорията, където се съхраняват умалените картинки
 */
defIfNot('IMG_THUMB_PATH',  EF_INDEX_PATH . '/' . EF_SBF . '/' . EF_APP_NAME . '/' . IMG_THUMB_DIR);



/**
 * Клас 'img_Thumb' - За работа с умалени изображения
 *
 *
 * @category  vendors
 * @package   img
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 */
class img_Thumb
{
    /**
     * Максимална широчина на скалираното изображение
     */
    var $maxWidth;


    /**
     * Максимална височина на скалираното изображение
     */
    var $maxHeight;
 

    /**
     * Изходното изображение
     */
    var $source;


    /**
     * Тип на данните за оригиналното изображение: url, path, fileman, string, gdRes
     */
    var $sourceType;


    /**
     * Графичен формат на умаленото изображение: png, jpg или gif
     */
    var $format;


    /**
     * Съдържание на картинката, като GD ресурс
     */
    var $gdRes;
    
    
    /**
     * Широчина на изходното изображение
     */
    var $width;


    /**
     * Височина на изходното изображение
     */
    var $height;


    /**
     * Съдържание на картинката, като стринг
     */
    var $imgAsString;


    /**
     * Вербално име на умалената картинка
     */
    var $verbalName;
    
    
    /**
     * Широчина на скалираното изображение
     */
    var $scaledWidth;


    /**
     * Височина на скалираното изображение
     */
    var $scaledHeight;
    

    /**
     * Какви параметри има този клас
     */
    static $argumentList = 'source, maxWidth, maxHeight, sourceType, verbalName, format, timeout, allowEnlarge, expirationTime, isAbsolute, quality';


    /**
     * Конструктор, който създава обект от изображение
     */
    function __construct(   $source, 
                            $maxWidth = NULL, 
                            $maxHeight = NULL, 
                            $sourceType = 'fileman', 
                            $verbalName = NULL,  
                            $format = NULL, 
                            $timeout = 3, 
                            $allowEnlarge = FALSE, 
                            $expirationTime = NULL,
                            $isAbsolute = NULL,
                            $quality = NULL)
    {
        
        if(is_array($source)) {
            foreach($source as $name => $value) {
                $this->{$name} = $value;
            }
        } else {
            $args = func_get_args();
            foreach(arr::make(self::$argumentList) as $i => $argName) {
                $this->{$argName} = $args[$i];
            }
        }

        expect($this->maxWidth > 0 && $this->maxHeight > 0, $this); 

        // Времена за кеширане на умалени изображения
        if(!$this->expirationTime) {
            switch($this->sourceType) {
                case 'url':
                    $this->expirationTime = 2 * 2 * 24 * 60 * 60;
                    break;
                case 'string':
                case 'path':
                    $this->expirationTime = 20 * 24 * 60 * 60;
                    break;
                case 'fileman':
                case 'gdRes':
                    $this->expirationTime = 2000 * 24 * 60 * 60;
                    break;
                default:
                    expect(FALSE, 'Непознат тип за източник на графичен файл', $this->sourceType);
            }
        }
        
        setIfNot($this->quality, 90);
        setIfNot($this->timeout, 3);
        setIfNot($this->sourceType, 'fileman');

    }


    /**
     * Връща имиджа като стринг
     */
    function getAsString()
    {
        if(!$this->imgAsString) { 
            switch($this->sourceType) {
                case 'url':  
                    $ctx = stream_context_create(array('http' => array('timeout' => $this->timeout)));
                    $this->imgAsString =  @file_get_contents($this->source, 0, $ctx);
                    break;
                case 'string':
                    $this->imgAsString = $this->source;
                    break;
                case 'fileman':
                    $this->imgAsString = fileman_Files::getContent($this->source);
                    break;
                case 'path':
                    $this->imgAsString = @file_get_contents($this->source);
                    break;
                case 'gdRes':
                    ob_start();
                    switch($this->getThumbFormat()) {
                        case 'jpg':
                            @imagejpeg($this->source);
                            break;
                        case 'gif':
                            @imagegif($this->source);
                            break;
                        default:
                            @imagepng($this->source);
                    }
                    $this->imgAsString =  ob_get_contents();
                    ob_end_clean();
                    break;
                default:
                    expect(FALSE, 'Непознат тип за източник на графичен файл', $this->sourceType);
            }
        }

        return $this->imgAsString;
    }
    

    /**
     * Прави хеш, с голяма вероятност уникален, спрямо картинката и параметрите на мащабирането
     */
    function getHash()
    {   
        if(!$this->hash) {
            switch($this->sourceType) {
                case 'url':
                case 'string':
                case 'fileman':
                    $param = $this->source;
                    break;
                case 'path':
                    $param = md5_file($this->source);
                case 'gdRes':
                    $param = $this->getAsString($this->source);
            }

            $this->hash = md5($param .  '|' . $this->sourceType  . '|' . $this->maxWidth . '|' .
                $this->maxHeight . '|' . $this->allowEnlarge . '|' . $this->quality . '|' .  EF_SALT);
        }

        return $this->hash;
    }
    

    /**
     * Връща GD ресурс, създаден от картинката
     */
    function getGdRes()
    {
        if(!$this->gdRes) {
            if($this->sourceType == 'gdRes') {
                $this->gdRes = $this->source;
            } else {
                if($asString = $this->getAsString()) {
                    $this->gdRes = imagecreatefromstring($asString);
                }
            }
        }

        return $this->gdRes;
    }


    /**
     * Връща размера на изображението
     */
    function getSize()
    {
        if(!$this->width || !$this->height) {
            $gdRes = $this->getGdRes();
            $this->width  = imagesx($gdRes);
            $this->height = imagesy($gdRes);
        }

        return array($this->width, $this->height);
    }


    /**
     * Връща името на умаленото изображение
     */
    function getThumbFormat()
    {        
        if(!$this->format) {
            switch($this->sourceType) {
                case 'url':
                case 'path':
                    $this->format = fileman_Files::getExt($this->source);
                case 'fileman':
                    $this->format = fileman_Files::getExt(fileman_Files::fetchByFh($this->source, 'name'));
            }

            if($this->format == 'jpeg') {
                $this->format = 'jpg';
            }

            if(!in_array($this->format, array('png', 'jpg', 'gif'))) {
                $this->format = 'png';
            }
        }

        return $this->format;
    }


    /**
     * Връща името на умалената картинка
     */
    function getThumbName()
    {
        if(!$this->thumbName) {
            if($this->verbalName) {
                $this->thumbName = fileman_Files::normalizeFileName($this->verbalName) . '-'; 
            }
            $this->thumbName .= substr($this->getHash(), 0, 8);
            $this->thumbName .= '-' . $this->maxWidth;
            $this->thumbName .= '.' . $this->getThumbFormat();
        }

        return $this->thumbName;
    }


    /**
     * Връща пътя до умалената картинка
     */
    function getThumbPath()
    {
        if(!$this->thumbPath) {
            $this->thumbPath = IMG_THUMB_PATH . '/' . $this->getThumbName();
        }

        return $this->thumbPath;
    }
    
    
    /**
     * Връща URL до умалената картинка
     */
    function getThumbUrl()
    {
        if(!$this->thumbUrl) {
            $this->thumbUrl = sbf(IMG_THUMB_DIR . "/" . $this->getThumbName(), '', $this->isAbsolute);
        }

        return $this->thumbUrl;
    }


    /**
     * Връща урл към умаленото изображение
     */
    function forceUrl($postpond = TRUE)
    {
        $path = $this->getThumbPath();
        
        if(!file_exists($path) || (filemtime($path) + $this->expirationTime < time())) {
            if($postpond) {
                foreach(arr::make(self::$argumentList) as $i => $argName) {
                    $state[$argName] = $this->{$argName};
                }
                $id = core_Crypt::encodeVar($state);
                return toUrl(array('img_M', 'R', 't' => $id));
            } else {
                $this->saveThumb();  
            }
        }
        
        $url  = $this->getThumbUrl();

        return $url;
    }


    function saveThumb()
    {
        if($gdRes = $this->getGdRes()) {
            
            $path = $this->getThumbPath();

            list($width, $height) = $this->getSize();

            list($this->scaledWidth, $this->scaledHeight, $ratio) = self::scaleSize($width, $height, $this->maxWidth, $this->maxHeight, $this->allowEnlarge);
                 
            // Склаираме, само ако имаме пропорция, различна от 1
            if($ratio != 1) {
                $newGdRes = self::scaleGdImg($gdRes, $this->scaledWidth, $this->scaledHeight);  
            } elseif($this->sourceType == 'gdRes') {
                $newGdRes = $gdRes;
            }

            if($newGdRes) { 
                switch($this->getThumbFormat()) {
                    case 'jpg':
                        imagejpeg($newGdRes, $path, $this->quality);
                        break;
                    case 'gif':
                        imagegif($newGdRes, $path);
                        break;
                    default:
                        imagepng($newGdRes, $path);
                }
                imagedestroy($newGdRes);
            } else {
                if($asString = $this->getAsString()) {  
                    file_put_contents($path, $asString);
                }
            }
        }
    }


    /**
     * Връща умаленото изображение, като стринг
     */
    function createImg($attr = array())
    {
        $attr['src']    = $this->forceUrl();
        $attr['width']  = $this->scaledWidth;
        $attr['height'] = $this->scaledHeight;
        $attr['alt'] = $this->verbalName;

        $img = ht::createElement('img', $attr);

        return $img;
    }


    /**
     * Мащабира входни размери на правоъгълник, така, че да се запази пропорцията и 
     * всеки един от новите размери е по-малък или равен на зададените максимални
     *
     * @param int   $width      Широчина на изходното изображение
     * @param int   $height     Височина на изходното изображение
     * @param int   $maxWidth   Максимална широчина
     * @param int   $maxHeight  Максимална широчина
     * @param bool  $notEnlarge Трябва ли да се увеличава входния правоъгълник?
     *
     * @return array            ($newWidth, $newHeight, $ratio)
     */
    public static function scaleSize($width, $height, $maxWidth, $maxHeight, $allowEnlarge = FALSE)
    {
        $wRatio = $maxWidth / $width;
        $hRatio = $maxHeight / $height;

        if($allowEnlarge) {
            $ratio  = min($wRatio, $hRatio);
        } else {
            $ratio  = min($wRatio, $hRatio, 1);
        }

        $newHeight = ceil($ratio * $height);
        $newWidth = ceil($ratio * $width);

        return array($newWidth, $newHeight, $ratio);
    }


    /**
     * Скалира изображение, към нова широчина и височина
     * 
     * @param   GD resource     $im         Начално изображение
     * @param   int             $dstWidth   Нова широчина
     * @param   int             $dstWidth   Нова височина
     *
     * @return  GD resource                 Резултатно изображение
     */
    static function scaleGdImg($im, $dstWidth, $dstHeight)
    {
        $width  = imagesx($im);
        $height = imagesy($im);

        $newImg = imagecreatetruecolor($dstWidth, $dstHeight);

        imagealphablending($newImg, FALSE);
        imagesavealpha($newImg, TRUE);

        $transparent = imagecolorallocatealpha($newImg, 255, 255, 255, 127);
        imagefilledrectangle($newImg, 0, 0, $dstWidth, $dstWidth, $transparent);
        imagecopyresampled($newImg, $im, 0, 0, 0, 0, $dstWidth, $dstHeight, $width, $height);
 
        return $newImg;
    }

}