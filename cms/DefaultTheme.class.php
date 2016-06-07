<?php
/**
 * Стандартна тема за външната част
 * 
 * @title     Стандартна CMS тема
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_DefaultTheme extends core_ProtoInner {
    

    /**
     * Поддържан интерфейс
     */
    public $interfaces = 'cms_ThemeIntf';
    

    /**
     * Дали темата носи собствени заглавни картинки
     */
    public $haveOwnHeaderImages = FALSE;

    
    /**
     * Допълване на формата за домейна със специфични полета за кожата
     */
    public function addEmbeddedFields(core_FieldSet &$form)
    {
        $form->FLD('wImg1', 'fileman_FileType(bucket=gallery_Pictures)', "caption=Заглавни картинки за десктоп (1000x288px)->Изображение 1");
        $form->FLD('wImg2', 'fileman_FileType(bucket=gallery_Pictures)', "caption=Заглавни картинки за десктоп (1000x288px)->Изображение 2");
        $form->FLD('wImg3', 'fileman_FileType(bucket=gallery_Pictures)', "caption=Заглавни картинки за десктоп (1000x288px)->Изображение 3");
        $form->FLD('wImg4', 'fileman_FileType(bucket=gallery_Pictures)', "caption=Заглавни картинки за десктоп (1000x288px)->Изображение 4");
        $form->FLD('wImg5', 'fileman_FileType(bucket=gallery_Pictures)', "caption=Заглавни картинки за десктоп (1000x288px)->Изображение 5");
        $form->FLD('fadeDelay', 'int', "caption=Превключване на картинките->Задържане,suggestions=3000|5000|7000");
        $form->FLD('fadeTransition', 'int', "caption=Превключване на картинките->Транзиция,suggestions=500|1000|1500");
        $form->FLD('nImg', 'fileman_FileType(bucket=gallery_Pictures)', "caption=Заглавна картинка за мобилен (360x104px)->Изображение 1");
        $form->FLD('title', 'varchar(14)', "caption=Заглавие на сайта->Кратък текст");
        $form->FLD('titleColor', 'color_Type', "caption=Заглавие на сайта->Цвят");

        // Икона за сайта
        $form->FLD('icon', 'fileman_FileType(bucket=gallery_Pictures)', "caption=Икона за сайта->Favicon");

        // Фон на хедъра
        $form->FLD('headerColor', 'color_Type', "caption=Цветове за темата->Цвят на хедъра");

        // Фон на менюто 
        $form->FLD('baseColor', 'color_Type', "caption=Цветове за темата->Фирмен цвят");
    }


    static function on_BeforeSave($mvc, $innerState, $innerForm)
    {
        if($innerForm->icon) {
            $dest = EF_INDEX_PATH . '/favicon.ico';
            file_put_contents($dest, fileman_Files::getContent($innerForm->icon));
        }
    }

    
    public function prepareWrapper($tpl)
    {
        // Добавяме заглавната картика
        $tpl->replace($this->getHeaderImg(), 'HEADER_IMG');
        
        // Добавяме заглавния текст
        $title = $this->innerForm->title;
        if(!$this->haveOwnHeaderImages && !$title) {
            $conf = core_Packs::getConfig('core');
            $title = $conf->EF_APP_TITLE;
        } elseif($title) {
            $style = '';
            if ($this->innerForm->titleColor) {
                $style =  " style='color:{$this->innerForm->titleColor};'";
            }
            $title = "<span{$style}>" . $title . "</span>";
        }

        if($title) {
            $tpl->replace($title, 'CORE_APP_NAME');
        }
        
        if($this->innerForm->headerColor) {
            $css .= "\n    #all #cmsTop {background-color:{$this->innerForm->headerColor} !important;}";
        }

        if ($this->innerForm->baseColor) {
            $baseColor = ltrim($this->innerForm->baseColor, "#");
        } else {
            $baseColor = "334";
        }

        $bordercolor = phpcolor_Adapter::changeColor($baseColor,  'mix', 10, '666');

        if(phpcolor_Adapter::checkColor($baseColor, 'dark')) {
            $mixColor = "#aaa";
            $css .= "\n    .foorterAdd, #cmsMenu a {color:#fff !important; text-shadow: 0px 0px 2px #000}";
            $css .= "\n    .vertical .formTitle, .vertical .formGroup, .vertical form[method=post] input[type=submit], form[method=post] input:first-child[type=submit] {color:#fff !important;}";
        } else {
            $mixColor = "#666";
            // стилове за тъмен цвят
            $css .= "\n    .foorterAdd, #cmsMenu a {color:#000 !important; text-shadow: none}";
            $css .= "\n    .vertical .formTitle, .vertical .formGroup, .vertical form[method=post] input[type=submit], form[method=post] input:first-child[type=submit] {color:#000 !important;}";
        }

        $colorObj =  new color_Object($baseColor);
        list($r, $g, $b) = array($colorObj->r, $colorObj->g, $colorObj->b);
        
        $colorObj =  new color_Object($mixColor);
        list($r1, $g1, $b1) = array($colorObj->r, $colorObj->g, $colorObj->b);

        if($r + $g + $b) {
            $colorMultiplier = sqrt(($r1*$r1 + $g1*$g1 + $b1*$b1)/($r*$r + $g*$g + $b*$b));

            if($colorMultiplier > 0.9) {
                if($colorMultiplier <= 1){
                    $colorMultiplier -= 0.2;
                } else if($colorMultiplier <= 1.1) {
                    $colorMultiplier += 0.2;
                }
            }

            $colorObj->r = $r * $colorMultiplier;
            $colorObj->g = $g * $colorMultiplier;
            $colorObj->b = $b * $colorMultiplier;

            $activeColor = $colorObj->getHex("");
        } else {
            $activeColor = "333";
        }

        // изчисления за фон и рамка на линковете
        if(phpcolor_Adapter::checkColor($activeColor, 'dark')) {
            $fontColor = phpcolor_Adapter::changeColor($activeColor, 'darken', 25);
            $bgcolorActive = phpcolor_Adapter::changeColor($activeColor, 'lighten', 30);
        } else {
            $fontColor = $baseColor;
            $bgcolorActive = phpcolor_Adapter::changeColor($activeColor, 'lighten', 20);
        }

        $colorObj =  new color_Object($bgcolorActive);
        list($tempR, $tempG, $tempB) = array($colorObj->r, $colorObj->g, $colorObj->b);

        $tempBalance = ($tempR + $tempB + $tempG)/3;

        if ($tempBalance < 200 && phpcolor_Adapter::changeColor($bgcolorActive, 'lighten', 20) != "#ffffff") {
           $bgcolorActive = phpcolor_Adapter::changeColor($bgcolorActive, 'lighten', 20);
        }

        $css .= "\n    #cmsMenu a.selected, #cmsMenu a:focus, #cmsMenu a:hover {background-color:#{$activeColor};}";

        // стилове за меню и футър
        $css .= "\n    #cmsMenu {background-color:#{$baseColor};}";
        $css .= "\n    #cmsBottom {background-color:#{$baseColor}; border-top:1px solid #{$bordercolor} !important;}";
        $css .= "\n    #cmsMenu {border-top:1px solid #{$bordercolor} !important; border-bottom:1px solid #{$bordercolor} !important;}";

        // цветове на формите в зависимост от основния цвят
        $css .= "\n    .vertical form[method=post] input[type=submit], form[method=post] input:first-child[type=submit] {background-color:#{$baseColor} !important; border: 1px solid #{$bordercolor} !important}";
        $css .= "\n    .vertical .formTitle, .vertical .formGroup {background-color:#{$baseColor} !important; border-color:#{$bordercolor};}";

        $linkBorder =  phpcolor_Adapter::changeColor($bgcolorActive, 'mix', 5, $bordercolor);

        // Цвятове за линковете и h2 заглавията
        $css .= "\n    #cmsNavigation .nav_item a { color: #{$fontColor};}";
        $css .= "\n    #cmsNavigation .sel_page a, #cmsNavigation a:hover {background-color: #{$bgcolorActive} !important; border: 1px solid #{$linkBorder} !important; color: #{$fontColor} !important;}";
        $css .= "\n    a:hover, .eshop-group-button:hover .eshop-group-button-title a {color: #{$fontColor}; border:none !important}";
        $css .= "\n    h2 {background-color:#{$bgcolorActive} !important; padding: 5px 10px;border:none !important}";

        if($css) {
            $tpl->append($css, 'STYLES');
        }
        
        // Добавяме дефолт темата за цветове
        $tpl->push('css/default-theme.css', 'CSS');

    }
    

    /**
     * Връща img-таг за заглавната картинка
     */
    function getHeaderImg()
    {
        if(!Mode::is('screenMode', 'narrow')) {
            for($i = 1; $i <=5; $i++) {
                $imgName = 'wImg' . $i;
                if($this->innerForm->{$imgName}) {
                    $imgs[$i] = $this->innerForm->{$imgName};
                }
            }

            if(count($imgs) > 1) {
                $conf = core_Packs::getConfig('core');
                $baner = "<div class=\"fadein\">"; 
                foreach($imgs as $iHash) {
                    $img = new thumb_Img(array($iHash, 1000, 288, 'fileman', 'isAbsolute' => TRUE, 'mode' => 'large-no-change'));
                    $imageURL = $img->getUrl('forced');
                    $hImage = ht::createElement('img', array('src' => $imageURL, 'width' => 1000, 'height' => 288, 'alt' => $conf->EF_APP_TITLE, 'class' => 'headerImg', 'style' => $style));
                    $baner .= "\n{$hImage}";
                    $style = 'display:none;';
                }
                $baner .= "</div>";
                $baner = new ET($baner);
                $fadeTransition = $this->innerForm->fadeTransition ? $this->innerForm->fadeTransition : 1500;
                $fadeDelay = $this->innerForm->fadeDelay ? $this->innerForm->fadeDelay : 5000;
                $baner->append(".fadein { position:relative; display:block; max-height:100%; max-width:100%} .fadein img {position:relative; left:0; top:0;}", "STYLES");
                jquery_Jquery::run($baner, "fadeImages({$fadeTransition}, {$fadeDelay});", TRUE);
             	
                $this->haveOwnHeaderImages = TRUE;

                return $baner;
            }

        } else {
            if ($this->innerForm->nImg) {
                $imgs[1] = $this->innerForm->nImg;
            }
            
        }
        
        $imgsCnt = count($imgs);
        
        if($imgsCnt) {
            
            // Ключа да започава от 1 до броя
            $imgs = array_combine(range(1, $imgsCnt), array_values($imgs));
            
            $img = $imgs[rand(1, count($imgs))];
            
            if ($img) {
                if(!Mode::is('screenMode', 'narrow')) {
                    $img = new thumb_Img(array($img, 1000, 288, 'fileman', 'isAbsolute' => TRUE, 'mode' => 'large-no-change'));
                } else {
                    $img = new thumb_Img(array($img, 360, 104, 'fileman', 'isAbsolute' => TRUE, 'mode' => 'large-no-change'));
                }
                $imageURL = $img->getUrl('forced');
                $this->haveOwnHeaderImages = TRUE;
            }
        }
         
        // Да покаже дефолт картинките, ако няма зададени
        if(!$imageURL) {
            $imageURL = sbf($this->getDefaultHeaderImagePath(), '');
        }

        $conf = core_Packs::getConfig('core');
        $hImage = ht::createElement('img', array('src' => $imageURL, 'alt' => $conf->EF_APP_TITLE, 'class' => 'headerImg'));
        
        return $hImage;
    }

    /**
     * Връща пътя до картинката за главата на публичната страница
     */
    private function getDefaultHeaderImagePath()
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
        
        // Дали си носим картинките по друг начин?
        if (defined('EF_PRIVATE_PATH') && file_exists(EF_PRIVATE_PATH . "/" . $path)) {
            $this->haveOwnHeaderImages = TRUE;
        }

    	return $path;
    }
}