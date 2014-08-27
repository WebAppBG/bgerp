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
     * Сол за генериране на ключ за криптиране
     */
    const KEY_SALTH = 'IMAGE THUMBNAILS';


    /**
     * @var int Широчина на контейнера за скалираното изображение
     */
    protected $boxWidth;


    /**
     * @var int Височина на контейнера за скалираното скалираното изображение
     */
    protected $boxHeight;
 

    /**
     * @var string Изходното изображение
     */
    protected $source;


    /**
     * @var string Тип на данните за оригиналното изображение: url, path, fileman, string, gdRes
     */
    protected $sourceType;


    /**
     * @var resource Съдържание на картинката, като GD ресурс
     */
    protected $gdRes;
    
    
    /**
     * @var int Широчина на изходното изображение
     */
    protected $width;


    /**
     * @var int Височина на изходното изображение
     */
    protected $height;


    /**
     * @var string Съдържание на картинката, като стринг
     */
    protected $imgAsString;


    /**
     * var @string Вербално име на умалената картинка
     */
    protected $verbalName;
    
    
    /**
     * @var string Графичен формат на резултатното изображение: png, jpg или gif
     */
    protected $format; 
    
    
    /**
     * @var int макс. секунди изчакване при вземане от URL
     */
    protected $timeout; 
    
    
    /**
     * @var string 
     * 
     * small-no-change  изображениео трябва да се вмести в boxWidth x boxHeight. Ако е по-малко да не се увеличава
     * small-fit        изображениео трябва да се вмести в boxWidth x boxHeight, като поне едната му страна трябва да прилепне
     * large-no-change  изображениео трябва да покрие boxWidth x boxHeight. Ако и двете му страни са по-големи, да не се променя
     * large-fit        изображениео трябва да покрие boxWidth x boxHeight. Поне една от страните му да прилепне на box
     */
    protected $mode; 
    
    
    /**
     * @var int колко секунди да е жив кешът за това изображение
     */
    protected $expirationTime; 

    
    /**
     * @var boolean дали генерираното URL да е абсолютно
     */
    protected $isAbsolute;

    
    /**
     * var int (0 - 100) колко процента да е качеството на jpeg изображенията
     */
    protected $quality;

    
    /**
     * var string на коя страна е възможно да се завърти изображението ('left' или 'right')
     */
    protected $possibleRotation;
    
    
    /**
     * Широчина на скалираното изображение
     */
    protected $scaledWidth;


    /**
     * Височина на скалираното изображение
     */
    protected $scaledHeight;
    
    
    /**
     * @var string Пътят във файловата система, където да бъде записано изображението
     */
    protected $thumbPath;
    

    /**
     * Какви параметри има този клас
     */
    static $argumentList = 'source, boxWidth, boxHeight, sourceType, verbalName, format, timeout, mode, expirationTime, isAbsolute, quality, possibleRotation, thumbPath';


    /**
     * Конструктор, който създава обект от изображение
     */
    function __construct(   $source, 
                            $boxWidth = NULL, 
                            $boxHeight = NULL, 
                            $sourceType = 'fileman', 
                            $verbalName = NULL,  
                            $format = NULL, 
                            $timeout = 3, 
                            $mode = 'small-no-change', 
                            $expirationTime = NULL,
                            $isAbsolute = NULL,
                            $quality = 95,
                            $possibleRotation = NULL,
                            $thumbPath = NULL)
    {
        // Дефинираните променливи
        $def = get_defined_vars();  

        // Първия елемент дали е масив? Ако да - очакваме там да са аргументите
        $isArraySource = is_array($source);

        foreach(arr::make(self::$argumentList) as $i => $argName) {
            if($isArraySource && isset($source[$i])) {
                $this->{$argName} = $source[$i];
            } elseif($isArraySource && isset($source[$argName])) {
                $this->{$argName} = $source[$argName];
            } else {  
                $this->{$argName} = $def[$argName];
            }
        } 
        
        expect($this->boxWidth > 0 && $this->boxHeight > 0, $this); 

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
                    try {
                        $this->imgAsString = fileman_Files::getContent($this->source);
                    } catch( core_exception_Expect $e) {
                        // Нищо не правим, ако има грешка
                    }
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
                    $param = $this->source;
                    break;
                case 'fileman':
                    try {
                        $param = fileman_Files::fetchByFh($this->source, 'md5');
                    } catch (core_exception_Expect $e) {
                        $param = str::getRand();
                    }
                    break;
                case 'path':
                    $param = md5_file($this->source);
                    break;
                case 'gdRes':
                    $param = md5_file($this->getAsString($this->source));
            }

            $this->hash = md5($param .  '|' . $this->sourceType  . '|' . $this->boxWidth . '|' .
                $this->boxHeight . '|' . $this->mode . '|' . $this->quality . '|' . $this->possibleRotation . EF_SALT);
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
     * Задаваме височината и широчината
     */
    function setWidthAndHeight()
    {
        // Ако не са зададени
        if(!$this->width || !$this->height) {
            
            $handler = $this->getHash();
            
            // Опитваме се да вземем размерите от кеша, ако не - от изображението
            if($sArr = core_Cache::get('imgSizes', $handler)) {  
                $this->width  = $sArr[0];
                $this->height = $sArr[1];
            } elseif($gdRes = $this->getGdRes()) {
                $this->width  = imagesx($gdRes);
                $this->height = imagesy($gdRes);

                core_Cache::set('imgSizes', $handler, array($this->width, $this->height), 100000);
             }
        }
    }
    
    
    /**
     * Връща размера на изображението
     */
    function getSize()
    {
        $this->setWidthAndHeight();

        if(!$this->scaledWidth || !$this->scaledHeight || !$this->ratio) {
            list($this->scaledWidth, $this->scaledHeight, $this->ratio, $this->rotation) = self::scaleSize($this->width, $this->height, $this->boxWidth, $this->boxHeight, $this->mode, (boolean)$this->possibleRotation);
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
                break;
                case 'fileman':
                    $this->format = fileman_Files::getExt(fileman_Files::fetchByFh($this->source, 'name'));
                break;
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
            $this->thumbName .= '-' . $this->boxWidth;
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
     * Форсира свалянето на скалираното изображение
     */
    function forceDownload()
    {
        // Записваме картинакта
        $this->saveThumb();
        
        // Пътя до картинката
        $path = $this->getThumbPath();
        
        // Размер на файла
        $fileLen = filesize($path);
        
        // Име на файла
        $fileName = $this->verbalName ? $this->verbalName . '.' . $this->getThumbFormat() : basename($path);
        
        // Задаваме хедърите
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . $fileName);
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Content-Length: ' . $fileLen);
        header("Connection: close");
        header('Pragma: public'); // Нужен е когато се използва SSL връзка в браузъри на IE <= 8 версия
        
        // Предизвикваме сваляне на файла
        readfile($path);
        
        shutdown();
    }
    
    
    /**
     * Връща URL до умалената картинка
     */
    protected function getThumbUrl()
    {
        if(!$this->thumbUrl) {
            $this->thumbUrl = sbf(IMG_THUMB_DIR . "/" . $this->getThumbName(), '', $this->isAbsolute);
        }

        return $this->thumbUrl;
    }


    /**
     * Връща ключа за криптиране на отложение връзки
     */
    protected static function getCryptKey()
    {
        $key = sha1(EF_SALTH . self::KEY_SALTH);
    }


    /**
     * Връща УРЛ към  картинката, което е с отложено изпълнение
     * Картинката, ако липсва ще се генерира, когато URL-то се покаже
     */
    protected function getDeferredUrl()
    {
        foreach(arr::make(self::$argumentList) as $i => $argName) {
            $state[$argName] = $this->{$argName};
        }
        
        $id = core_Crypt::encodeVar($state, img_Thumb::getCryptKey()) . '.' . $this->getThumbFormat();
        
        if ($this->isAbsolute) {
            $type = 'absolute';
        } else {
            $type = 'relative';
        }
        
        return toUrl(array('img_M', 'R', 't' => $id), $type);
    }


    /**
     * Връща урл към умаленото изображение
     * 
     * @param string $mode Режим за генериране на URL: 'auto', 'deferred', 'forced'
     *
     * $return string 
     */
    public function getUrl($mode = 'auto')
    {
        $path = $this->getThumbPath();
        
        if(!file_exists($path) || (filemtime($path) + $this->expirationTime < time())) {
            if(($this->sourceType != 'gdRes') && ($mode == 'deferred' || ($mode == 'auto' && !Mode::is('text', 'xhtml')))) {
                
                $url = $this->getDeferredUrl();

                return $url;
            } else {
                $this->saveThumb();  
            }
        }
        
        if(!file_exists($path)) {
            $url = sbf('img/1x1.gif', '');
        } else {
            $url  = $this->getThumbUrl();
        }

        return $url;
    }


    /**
     * създава и записва thumb изображението
     */
    protected function saveThumb()
    {
        if($gdRes = $this->getGdRes()) {
            
            $path = $this->getThumbPath();

            $this->getSize();
            
            // Склаираме, само ако имаме пропорция, различна от 1 или ротираме
            if($this->ratio != 1 || $this->rotation) {
                
                if($this->rotation) {
                    $newGdRes = self::scaleGdImg($gdRes, $this->scaledHeight, $this->scaledWidth, $this->format);
                    
                    $white = imagecolorallocatealpha($newGdRes, 255, 255, 255, 127);
                    
                    $angle = $this->possibleRotation == 'left' ? : 90 : 270;
                    
                    $newGdRes = imagerotate($newGdRes, $angle, $white);
                } else {
                    $newGdRes = self::scaleGdImg($gdRes, $this->scaledWidth, $this->scaledHeight, $this->format);
                }
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
     * Връща умаленото изображение, като html <img> таг 
     * 
     * @param attr array Допълнителни атрибути за html тага
     * 
     * @return string
     */
    public function createImg($attr = array())
    {
        setIfNot($attr['src'], $this->getUrl());
        
        $this->getSize();  
        setIfNot($attr['width'], $this->scaledWidth);
        setIfNot($attr['height'], $this->scaledHeight);
        
        setIfNot($attr['alt'], $this->verbalName);
        
        unset($attr['isAbsolute']);

        $img = ht::createElement('img', $attr);

        return $img;
    }


    /**
     * Мащабира входни размери на правоъгълник, така, че да се запази пропорцията и 
     * всеки един от новите размери е по-малък или равен на зададените максимални
     *
     * @param int   $width      Широчина на изходното изображение
     * @param int   $height     Височина на изходното изображение
     * @param int   $boxWidth   Максимална широчина
     * @param int   $boxHeight  Максимална широчина
     * @param bool  $mode       Увеличаване или намаляване?
     * @param bool  $allowRotate Трябва ли да се ротира
     *
     * @return array            ($newWidth, $newHeight, $ratio)
     */
    public static function scaleSize($width, $height, $boxWidth, $boxHeight, $mode = 'small-no-change', $allowRotate = FALSE)
    {
        if($width == 0 || $height == 0) {

            return array($boxWidth, $boxHeight, 1);
        }

        $wRatio = $boxWidth / $width;
        $hRatio = $boxHeight / $height;
        
        switch($mode) {
            case 'small-fit':
                $ratio  = min($wRatio, $hRatio);
                break;
            case 'small-no-change':
                $ratio  = min($wRatio, $hRatio, 1);
                break;
            case 'large-fit':
                $ratio  = max($wRatio, $hRatio);
                break;
            case 'large-no-change':
                $ratio  = max($wRatio, $hRatio, 1);
                break;
            default:
                expect(FALSE);
        }

        if($allowRotate) {
            list($rW, $rH, $rR) = self::scaleSize($height, $width, $boxWidth, $boxHeight, $mode);
            expect($ratio);
            $rK = ($rR <1) ? 1 / $rR : $rR;
            $nK = ($ratio <1) ? 1 / $ratio : $ratio;

            if($rK < $nK) {
                $ratio = $rR;
                $tempWidth = $width;
                $width = $height;
                $height = $tempWidth;
                $rotate = TRUE;
            }
        }
        $newHeight = ceil($ratio * $height);
        $newWidth = ceil($ratio * $width);

        return array($newWidth, $newHeight, $ratio, $rotate, $rK, $nK);
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
    public static function scaleGdImg($im, $dstWidth, $dstHeight, $format=NULL)
    {
        $width  = imagesx($im);
        $height = imagesy($im);
        
        $newImg = imagecreatetruecolor($dstWidth, $dstHeight);
        
        if (!$format || $format == 'gif') {
            $transparentIndex = imagecolortransparent($im);
            if (!$format && $transparentIndex > 0) {
                $isGif = TRUE;
            } elseif ($format) {
                $isGif = TRUE;
            }
        }
        
        if ($isGif == TRUE) {
            // is GIF
            imagepalettecopy($im, $newImg);
            imagefill($newImg, 0, 0, $transparentIndex);
            imagecolortransparent($newImg, $transparentIndex);
            imagetruecolortopalette($newImg, true, 256);
        } else {
            // is PNG
            imagealphablending($newImg, FALSE);
            imagesavealpha($newImg, TRUE);
    
            $transparent = imagecolorallocatealpha($newImg, 255, 255, 255, 127);
            imagefilledrectangle($newImg, 0, 0, $dstWidth, $dstWidth, $transparent);
        }
        
        imagecopyresampled($newImg, $im, 0, 0, 0, 0, $dstWidth, $dstHeight, $width, $height);
 
        return $newImg;
    }
}