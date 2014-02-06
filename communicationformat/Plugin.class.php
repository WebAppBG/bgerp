<?php


/**
 * Форматиране на ICQ, Skype, tel. и други
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class communicationformat_Plugin extends core_Plugin
{

	/**
     * Обработваме елементите линковете, които сочат към ISQ, Scype, tel
     */
    function on_AfterCatchRichElements($mvc, &$html)
    {
       
       $this->mvc = $mvc;
       
       $conf = core_Packs::getConfig('communicationformat'); 
       $format = explode(",", $conf->COMMUNICATION_FORMAT);
      
       try {
	       if (in_array('tel', $format)) { 
	       		 // Ако намери съвпадение на регулярния израз изпълнява функцията
				 // намира телефонните номера
				 $html = preg_replace_callback("/^\s*((Тел|Телефон|tel\/fax|тел\/факс|Tel|Telephone|Phone|Тел.|Тelefax)\.?\:? *)[^0-9\(\+]{0,6}([\d\(\+][\d\- \(\)\.\+\/]{7,27}[\d\)])/umi", array($this, 'catchCommunicationTelFormat'), $html);
	       }
	       
	       if (in_array('fax', $format)) {
	       		 $html = preg_replace_callback("/^\s*((Tel\/fax|Тел\/факс|Факс|Fax|Тelefax)\.?\:? *)[^0-9\(\+]{0,6}([\d\(\+][\d\- \(\)\.\+\/]{7,27}[\d\)])/umi", array($this, 'catchCommunicationFaxFormat'), $html);
	       	
	       }
	       
	       if (in_array('mob', $format)) {
	       		 $html = preg_replace_callback("/^\s*((M|Gsm|Mtel|Mobiltel|Vivacom|Vivatel|Globul|Mobile|Mob)\.?\:? *)[^0-9\(\+]{0,4}([\d\(\+][\d\- \,\(\)\.\+\/]{7,27}[\d\)])/umi", array($this, 'catchCommunicationMobFormat'), $html);
	       	
	       }
	       
	       if (in_array('email', $format)) {
	       		 // искаме да намерим изрази като Email|E-mail|Mail|@ , за да сложим пред тях икона
		         $html = preg_replace_callback("/^\s*(((Имейл|Емайл|Е-майл|Email|E-mail|Mail|@)\.?\:? *)([\-;:&=\+\$,\w]+@)?[A-Za-z0-9\.\-]+)/umi", array($this, 'catchCommunicationEmailFormat'), $html);
		         $html = preg_replace_callback("/^\s*(([\-;:&=\+\$,\w]+@)?[A-Za-z0-9\.\-]+)/umi", array($this, 'catchCommunicationEmailFormat'), $html);
		        // bp($html);
	       }
	       
	       if (in_array('icq', $format)) {
	       		 // валидация на ICQ номер
		       	 $html = preg_replace_callback("/^\s*((ICQ)\.?\:? *)(\-*[1-9][\-0-9]*[0-9]+)/umi", array($this, 'catchCommunicationICQFormat'), $html);
	       }
	       
	       if (in_array('social', $format)) { 
	       		 $html = preg_replace_callback("/^\s*((AIM|YIM|MSNIM|MSN|XMPP|Jabber|Skype)\.?\:?\ *)([a-zA-Z0-9_\-\@\.]{3,64})/umi", array($this, 'catchCommunicationFormat'), $html);
	       }
	       
	       if(in_array('web', $format)) {
	       	     
	       		$html = preg_replace_callback("/^\s*((((Web|WWW|Site|Сайт|Блог|Blog)\.?\:?\ *)((http|https|ftp):\/\/[A-Za-z0-9\.\-]+|(?:www\.)[A-Za-z0-9\.\-]+))((?:\/[\+~%\/\.\w\-_]*)?\??(?:[\-\+=&;%@\.\w_]*)#?(?:[\.\!\/\\\w]*))?)/umi", array($this, 'catchCommunicationWebFormat'), $html);
	       	
	       	    //$html = preg_replace_callback("/^\s*((((http|https|ftp):\/\/)[A-Za-z0-9\.\-]+|(?:www\.)[A-Za-z0-9\.\-]+)((?:\/[\+~%\/\.\w\-_]*)?\??(?:[\-\+=&;%@\.\w_]*)#?(?:[\.\!\/\\\w]*))?)/umi", array($this, 'catchCommunicationWebFormat'), $html);
	       }
       } catch (core_Exception_Expect $exp) {  }
       
    } 
       
    //link + email
    //((([A-Za-z]{3,9}:(?:\/\/)?)(?:[\-;:&=\+\$,\w]+@)?[A-Za-z0-9\.\-]+|(?:www\.|[\-;:&=\+\$,\w]+@)[A-Za-z0-9\.\-]+)((?:\/[\+~%\/\.\w\-_]*)?\??(?:[\-\+=&;%@\.\w_]*)#?(?:[\.\!\/\\\w]*))?)
    
    //drdata_address
    //(f|telefax|fax|faks)[^0-9\(\+]{0,6}([\d\(\+][\d\- \(\)\.\+\/]{7,27}[\d\)])
    
    //old
    //(((Тел|Телефон|Tel|Telephone|Phone|Mobile|Mob|Факс|Fax|Тел.)\.?\:? *)([ ]*[0-9\(\)\/\+\- ]+[ ]*))
    
    //new
    //(Тел|Телефон|Tel|Telephone|Phone|Mobile|Mob|Факс|Fax|Тел.)[\.?\:? *][^0-9\(\+]{0,6}([\d\(\+][\d\- \(\)\.\+\/]{7,27}[\d\)])
    
    
    /**
     * Обработваме всички елементи в richText-а,
     * които приличат на link
     * и започват на нов ред.
     * Добавяме пред тях икона за хипервръзка
     * 
     * @param array $match
     */
    function catchCommunicationWebFormat($match)
    {
    	// намираме мястото, което ще заместваме
        $place = $this->mvc->getPlace();
        
    	if (Mode::is('text', 'xhtml') || Mode::is('printing')) {
        	// Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
        	$isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
        
        	// Иконата на класа
        	$icon = sbf("img/16/world_link.png", '', $isAbsolute);
        
        } else {
        	$icon = sbf("img/16/world_link.png",''); 
        }
	         	    
        // добавяме иконата пред името на услугата
        $this->mvc->_htmlBoard[$place] =  "<img class='communicationImg' src='{$icon}' />{$match[2]}";
      
        $communicationFormat = str_replace($match[2], "[#{$place}#]", $match[0]);

        return $communicationFormat;
    }
    
    
    /**
     * Обработваме всички елементи в richText-а,
     * които приличат на телефонен номер 
     * и започват на нов ред.
     * Заместваме ги с линк към съответната услуга
     * 
     * @param array $match
     */
    function catchCommunicationTelFormat($match)
    {
    	// ако не може да мачнем телефон, просто не правим
    	// никакви обработки
        if(!trim($match[3])) {
            return $match[0];
        }
        //bp($match);
        // намираме мястото, което ще заместваме
        $place = $this->mvc->getPlace();
        
        // елемент съдържащ: телефонен номер или потребителско име/номер
        $matchElement = trim(mb_strtolower($match[2]));
                         		
	    $PhonesVerbal = cls::get('drdata_PhoneType');
	        		
	    // парсирваме всеки телефон
	    $parsTel = $PhonesVerbal->toArray($match[3]);
	        	
	    if(!count($parsTel)) {
            return  $match[0];
        }
	
		// му задаваме друга икона
		if (Mode::is('text', 'xhtml') || Mode::is('printing')) {
        	// Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
        	$isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
        
        	// Иконата на класа
        	$icon = sbf("img/16/telephone2.png", '', $isAbsolute);
        
        } else {
        	$icon = sbf("img/16/telephone2.png",''); 
        } 

	    // ако сме в тесен режим
	    if (Mode::is('screenMode', 'narrow')) {	
		    // ако мачнатия елемент прилича на телефон
		    // го обработваме като телефон
		    $this->mvc->_htmlBoard[$place] =  "<img class='communicationImg' src='{$icon}' />{$match[1]}<span class='communication'>" . $PhonesVerbal->toVerbal($match[3]) . "</span>";
		    
	        // линк е мачнатия елемент, не името на услугата
		    // посочваме мястото където ще за заменят линковете
	        $communicationFormat = str_replace($match[0], "[#{$place}#]", $match[0]);
	        //$communicationFormat = str_replace($match[1], "", $match[0]);
	
	        // добавяме иконата пред името на услугата
	       // $communicationFormat = str_replace($match[1], "<img class='communicationImg' src='{$icon}' />{$match[1]}", $communicationFormat);
        // ако сме в широк екран
        } else {
        	// няма да правим линк
        	$this->mvc->_htmlBoard[$place] = "<img class='communicationImg' src='{$icon}' />{$match[1]}";
        	// просто ще сложим икона отпред
        	$communicationFormat = str_replace($match[1], "[#{$place}#]", $match[0]);
        }
              
        return $communicationFormat;
    }

    
    /**
     * Обработваме всички елементи в richText-а,
     * които приличат на  факс
     * и започват на нов ред.
     * Заместваме ги с линк към съответната услуга.
     * Тук тя е изпращане на имейл
     * 
     * @param array $match
     */
    function catchCommunicationFaxFormat($match)
    {     
        // намираме мястото, което ще заместваме
        $place = $this->mvc->getPlace();
        
    	if (Mode::is('text', 'xhtml') || Mode::is('printing')) {
        	// Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
        	$isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
        
        	// Иконата на класа
        	$icon = sbf("img/16/fax2.png", '', $isAbsolute);
        
        } else {
        	$icon = sbf("img/16/fax2.png",''); 
        }
        				       	                
		// ако сме в тесен режим и имаме възможност за изпращане на факсове
		if (Mode::is('screenMode', 'narrow') && email_FaxSent::haveRightFor('send')) {
			   	
			$PhonesVerbal = cls::get('drdata_PhoneType');
			$Email = cls::get('type_Email');
			        		
			// ако мачнатия елемент прилича на телефон
			// го парсирваме
			if($tArr = $PhonesVerbal->toArray($match[3])){
				// за всеки един алемент
				foreach($tArr as $t){ 
					// номера започва с +
					$value = '+';
					// ако имаме намерен код на страната го добавяме
					if($t->countryCode) {
						$value .= '' . $t->countryCode;
					}
					// ако имаме намерен код на областта го добавяме
					if($t->areaCode) {
						$value .= '' . $t->areaCode;
					}
					// накрая слагаме и номера
					if($t->number) {
						$value .= '' . $t->number;
					}
					// ще показваме, оригинално въведения номер
					$toVerbal = $t->original;
				}
			}
			
			// слагаме му домейн
			$domain = '@fax.man';
			// за да може да го изпратим като имейл
			$email = $value.$domain;
					
			// правим линк за изпращане на имейл през системата
			$href = "<span class='communication'><img class='communicationImg' src='{$icon}' />{$match[1]}" . $Email->addHyperlink($email, $PhonesVerbal->toVerbal($match[3])). "</span>";
					        			
			// и го връщаме
			$this->mvc->_htmlBoard[$place] = str_replace($email, $toVerbal, $href);
								    	
			// линк е мачнатия елемент, не името на услугата
		    // посочваме мястото където ще за заменят линковете
	        $communicationFormat = str_replace($match[0], "[#{$place}#]", $match[0]);
	
	        // добавяме иконата пред името на услугата
	        //$communicationFormat = str_replace($match[1], "<img class='communicationImg' src='{$icon}' />{$match[1]}", $communicationFormat);
		// ако сме в широк екран	
		} else {
		    // няма да правим линк   	
			$this->mvc->_htmlBoard[$place] = "<img class='communicationImg' src='{$icon}' />{$match[1]}";
			// просто ще добавим иконка
			$communicationFormat = str_replace($match[1], "[#{$place}#]", $match[0]);
		}
      
        return $communicationFormat;
    }
    
    
    /**
     * Обработваме всички елементи в richText-а,
     * които приличат на мобилен номер 
     * и започват на нов ред.
     * Заместваме ги с линк към съответната услуга
     * 
     * @param array $match
     */
    function catchCommunicationMobFormat($match)
    {  
       
    	// ако не може да мачнем телефон, просто не правим
    	// никакви обработки
        if(!trim($match[3])) {
            return $match[0];
        }
        
        // иконка
		if (Mode::is('text', 'xhtml') || Mode::is('printing')) {
        	// Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
        	$isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
        
        	// Иконата на класа
        	$icon = sbf("img/16/mobile2.png", '', $isAbsolute);
        
        } else {
        	$icon = sbf("img/16/mobile2.png",''); 
        }
		
        // намираме мястото, което ще заместваме
        $place = $this->mvc->getPlace();
                               		
	    $PhonesVerbal = cls::get('drdata_PhoneType');
	        		
	    // парсирваме всеки телефон
	    $parsTel = $PhonesVerbal->toArray($match[3]);
	        	
	    if(!count($parsTel)) {
            return $match[0];
        }

	    // ако сме в тесен режим
	    if (Mode::is('screenMode', 'narrow')) {	
		   // ако мачнатия елемент прилича на телефон
		   // го обработваме като телефон 

		   $this->mvc->_htmlBoard[$place] =  "<span class='communication'><img class='communicationImg' src='{$icon}' />{$match[1]}" . $PhonesVerbal->toVerbal($match[3]) . "</span>";
		   
		   // линк е мачнатия елемент, не името на услугата
		   // посочваме мястото където ще за заменят линковете
	       $communicationFormat = str_replace($match[0], "[#{$place}#]", $match[0]);
	
	       // добавяме иконата пред името на услугата
	       //$communicationFormat = str_replace($match[1], "<img class='communicationImg' src='{$icon}' />{$match[1]}", $communicationFormat);
        
	    // ако сме в широк
        } else {
        	// няма да правим линк
       		$this->mvc->_htmlBoard[$place] = "<img class='communicationImg' src='{$icon}' />{$match[1]}";
        	// просто ще сложим иконка отпред
        	$communicationFormat = str_replace($match[1], "[#{$place}#]", $match[0]);
        }  
  
        return $communicationFormat;
    }
    
    
    /**
     * Обработваме всички елементи в richText-а,
     * които са от вида на "Skype: скайп_име" 
     * и започват на нов ред.
     * Заместваме ги с линк към съответната услуга
     * 
     * @param array $match
     */
    function catchCommunicationFormat($match)
    {  
        if(!trim($match[3])) {
            return $match[0];
        }

        // намираме мястото, което ще заместваме
        $place = $this->mvc->getPlace();
        
        // елемент съдържащ: телефонен номер или потребителско име/номер
        $matchElement = trim(mb_strtolower($match[2]));
        
        
        // Намираме иконата в sbf папката
        $nameIcon = str::utf2ascii($matchElement);
	  	    
    	if (Mode::is('text', 'xhtml') || Mode::is('printing')) {
        	// Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
        	$isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
        
        	// Иконата на класа
        	$icon = sbf("img/16/{$nameIcon}.png", '', $isAbsolute);
        
        } else {
        	$icon = sbf("img/16/{$nameIcon}.png",''); 
        }
        
        $Email = cls::get('type_Email');
        
        // Ако мачнатият елемент е валиден имейл за системата
	    if($Email->isValidEmail($match[3])){
	    	$email = $Email->toVerbal($match[3]);
	    }
	    
        // в зависимост от услугата, правим различни линкове
        switch ($matchElement) {
        	        	    
        	case 'msnim' :
        		
		        if (Mode::is('text', 'xhtml') || Mode::is('printing')) {
		        	// Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
		        	$isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
		        
		        	// Иконата на класа
		        	$icon = sbf("img/16/msn.png", '', $isAbsolute);
		        
		        } else {
		        	$icon = sbf("img/16/msn.png",''); 
		        }

        	case 'msn' :
        		$this->mvc->_htmlBoard[$place] = "<span class='communication'><img class='communicationImg' src='{$icon}' /><a class='url' href='msnim:chat?contact={$match[3]}' title='MSN'>{$match[1]}</a></span>{$email}";
        		break;

        	case 'xmpp' :
        	case 'jabber' :
        		$this->mvc->_htmlBoard[$place] = "<span class='communication'><img class='communicationImg' src='{$icon}' /><a class='url' href='xmpp:{$match[3]}' title='{$match[2]}'>{$match[1]}</a></span>{$email}";
        	    break;
        		 
	        case 'skype' : 
		        $skypeUser = trim($match[3]);
        	
        		$this->mvc->_htmlBoard[$place] = "<span class='communication'><img class='communicationImg' src='{$icon}' />{$match[1]}<a class='url' href='skype:{$skypeUser}?call' title='Skype'>{$match[3]}</a></span>";
		        break;
		        
	        case 'aim' : 
		        $this->mvc->_htmlBoard[$place] = "<span class='communication'><img class='communicationImg' src='{$icon}' /><a class='url' href='aim:goim?screenname={$match[3]}' title='AOL Instant Messenger (AIM)'>{$match[1]}</a></span>{$email}";
		        break;
		        
	        case 'yim' :
		        $this->mvc->_htmlBoard[$place] = "<span class='communication'><img class='communicationImg' src='{$icon}' /><a class='url' href='ymsgr:sendIM?{$match[3]}' title='Yahoo! Messenger'>{$match[1]}</a></span>{$email}";
		        break;
		 		        
        }        
    	//$Email = cls::get('type_Email');
	    
    	// Ако мачнатият елемент е валиден имейл за системата
	    //if($Email->isValidEmail($match[3])){
	    	 
	    	// оставяме връзката на имейла : изпращана на имейл от системата
	    //	$communicationFormat = str_replace($match[3], $Email->toVerbal($match[3]), $match[0]);
	    	
	    	// и правим линк името на услугата
	    	// посочваме мястото където ще за заменят линковете
        //	$communicationFormat = str_replace($match[0], "[#{$place}#]", $match[0]);
	   // } else {
	    	
	    	// линк е мачнатия елемент, не името на услугата
	    	// посочваме мястото където ще за заменят линковете
        	$communicationFormat = str_replace($match[0], "[#{$place}#]", $match[0]);
        	
        	// добавяме иконата пред името на услугата
        	//$communicationFormat = str_replace($match[1], "<img class='communicationImg' src='{$icon}' />{$match[1]}", $communicationFormat);
	    //}
    	
        return $communicationFormat;
    }
    
    
    /**
     * Обработваме всички елементи в richText-а,
     * които са от вида на "ICQ номер на icq потребител"
     * и започват на нов ред.
     * Заместваме ги с линк към съответната услуга
     * 
     * @param array $match
     */
    function catchCommunicationICQFormat($match)
    {  
        if(!trim($match[3])) {
            return $match[0];
        }
        //bp($match);
        // намираме мястото, което ще заместваме
        $place = $this->mvc->getPlace();
        
        // елемент съдържащ: телефонен номер или потребителско име/номер
        $matchElement = trim(mb_strtolower($match[2]));
        
        // Намираме иконата в sbf папката
        $nameIcon = str::utf2ascii($matchElement); 
	    
    	if (Mode::is('text', 'xhtml') || Mode::is('printing')) {
        	// Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
        	$isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
        
        	// Иконата на класа
        	$icon = sbf("img/16/{$matchElement}.png", '', $isAbsolute);
        
        } else {
        	$icon = sbf("img/16/{$matchElement}.png",''); 
        }

		$this->mvc->_htmlBoard[$place] = "<img class='communicationImg' src='{$icon}' />{$match[1]}<span class='communication'><a class='url' type='application/x-icq' 
		href='http://www.icq.com/people/cmd.php?uin={$match[3]}&action=message'>{$match[3]}</a></span>";
		
	    // линк е мачнатия елемент, не името на услугата
	    // посочваме мястото където ще за заменят линковете
        $communicationFormat = str_replace($match[0], "[#{$place}#]", $match[0]);
    	
        return $communicationFormat;
    }
    
    
    /**
     * Обработваме всички елементи в richText-а,
     * които съдършат дума за имейл
     * и започват на нов ред.
     * Добавяме икона пред реда
     * 
     * @param array $match
     */
    function catchCommunicationEmailFormat($match)
    {   
    	
    	//bp($match);
        if(!trim($match[2])) {
            return  $match[0];
        }
       
        // намираме мястото, което ще заместваме
        $place = $this->mvc->getPlace();
        
        if (Mode::is('text', 'xhtml') || Mode::is('printing')) {
        	// Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
        	$isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
        
        	// Иконата на класа
        	$icon = sbf("img/16/email.png", '', $isAbsolute);
        
        } else {
        	$icon = sbf("img/16/email.png",''); 
        }
	         	    
        // добавяме иконата пред името на услугата
        $this->mvc->_htmlBoard[$place] =  "<img class='communicationImg' src='{$icon}' />{$match[2]}";
      
        $communicationFormat = str_replace($match[2], "[#{$place}#]", $match[0]);

        return $communicationFormat;
    }
}