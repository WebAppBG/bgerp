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
class bgerp_plg_CommunicationFormat extends core_Plugin
{
    
	/**
     * Обработваме елементите линковете, които сочат към ISQ, Scype, tel
     */
    function on_AfterCatchRichElements($mvc, &$html)
    {

       $this->mvc = $mvc;
       
       // Ако намери съвпадение на регулярния израз изпълнява функцията
       // намира телефонните номера
       $html = preg_replace_callback("/^\s*((Тел|Телефон|Tel|Telephone|Phone|Mobile|Mob|Факс|Fax|Тел.|Тelefax)\.?\:? *)[^0-9\(\+]{0,6}([\d\(\+][\d\- \(\)\.\+\/]{7,27}[\d\)])/umi", array($this, 'catchCommunicationTelFormat'), $html);
       
       // намира всичко което съдържа: букви, цифри, @, -, – и .
       $html = preg_replace_callback("/^\s*((AIM|YIM|MSNIM|MSN|XMPP|Jabber|Skype)\.?\:? *)([a-zA-Z0-9_\-\@\.]{3,64})/umi", array($this, 'catchCommunicationFormat'), $html);
       
       // валидация на ICQ номер
       $html = preg_replace_callback("/^\s*((ICQ)\.?\:? *)(-*[1-9][-0-9]*[0-9]+)/umi", array($this, 'catchCommunicationICQFormat'), $html);
       
       // искаме да намерим изрази като Email|E-mail|Mail|@ , за да сложим пред тях икона
       $html = preg_replace_callback("/^\s*((Имейл|Емайл|Е-майл|Email|E-mail|Mail|@)\.?\:? *)/umi", array($this, 'catchCommunicationEmailFormat'), $html);
    }
    
    //drdata_address
    //(f|telefax|fax|faks)[^0-9\(\+]{0,6}([\d\(\+][\d\- \(\)\.\+\/]{7,27}[\d\)])
    
    //old
    //(((Тел|Телефон|Tel|Telephone|Phone|Mobile|Mob|Факс|Fax|Тел.)\.?\:? *)([ ]*[0-9\(\)\/\+\- ]+[ ]*))
    
    //new
    //(Тел|Телефон|Tel|Telephone|Phone|Mobile|Mob|Факс|Fax|Тел.)[\.?\:? *][^0-9\(\+]{0,6}([\d\(\+][\d\- \(\)\.\+\/]{7,27}[\d\)])
    
    /**
     * Обработваме всички елементи в richText-а,
     * които приличат на телефонен номер или факс
     * и започват на нов ред.
     * Заместваме ги с линк към съответната услуга
     * 
     * @param array $match
     */
    function catchCommunicationTelFormat($match)
    {  
       
    	// ако не може да мачнем телефон, просто не правим
    	// никакви обработки
        if(!trim($match[3])) return;
        
        // намираме мястото, което ще заместваме
        $place = $this->mvc->getPlace();
        
        // елемент съдържащ: телефонен номер или потребителско име/номер
        $matchElement = trim(mb_strtolower($match[2]));
          
        // в зависимост от услугата, правим различни линкове
        switch ($matchElement) {
        	
        	case 'тел' :
        	case 'телефон' :
        	case 'tel' :
        	case 'telephone' :
        	case 'phone' :
        	case 'mobile' :
        	case 'mob' :
        	case 'тел.' :
        		
        		
	        	$PhonesVerbal = cls::get('drdata_PhoneType');
	        		
	        	// парсирваме всеки телефон
	        	$parsTel = $PhonesVerbal->toArray($match[3]);
	        	
	            if(!count($parsTel)) break;
	
	        	foreach($parsTel as $t){
		        	// ако той е мобилен
		        	if(strstr($t->area, 'Cellular')){
			        	// му задаваме една икона
			        	$icon = sbf("img/16/mobile2.png", '');
		        	// ако не е
		        	} else { 
			        	// му задаваме друга икона
			        	$icon = sbf("img/16/telephone2.png", ''); 
		        	}
	        	}
	        	
	        	// ако сме в тесен режим
	        	if (Mode::is('screenMode', 'narrow')) {	
	        		// ако мачнатия елемент прилича на телефон
	        		// го обработваме като телефон
	        		$this->mvc->_htmlBoard[$place] =  "<span class='communication'>" . $PhonesVerbal->toVerbal($match[3]) . "</span>";

        		} else {
        			$this->mvc->_htmlBoard[$place] = $match[0];
        		}
        		
        	    break;
        	    
        	    
        	    case 'fax' :
		        case 'факс' :
        			$icon = sbf("img/16/fax2.png",'');
        				       
	                if(!haveRole('officer')) break;
	                
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
		        			// слагаме му домейн
		        			$domain = '@fax.man';
		        			// за да може да го изпратим като имейл
		        			$email = $value.$domain;
		
		        			// правим линк за изпращане на имейл през системата
		        			$href = "<span class='communication'>" . $Email->addHyperlink($email, $PhonesVerbal->toVerbal($match[3])). "</span>";
		        			
		        			// и го връщаме
		        			$this->mvc->_htmlBoard[$place] = str_replace($email, $toVerbal, $href);
		        		}
		        } else {
		        	
		        	$this->mvc->_htmlBoard[$place] = $match[0];
		        }

		        break;
        }

        if (Mode::is('screenMode', 'narrow')) {
	      		    	
			// линк е мачнатия елемент, не името на услугата
		    // посочваме мястото където ще за заменят линковете
	        $communicationFormat = str_replace($match[3], "[#{$place}#]", $match[0]);
	
	        // добавяме иконата пред името на услугата
	        $communicationFormat = str_replace($match[1], "<img class='communicationImg' src='{$icon}' />{$match[1]}", $communicationFormat);
		  
        } else  {
        	$communicationFormat = str_replace($match[1], "<img class='communicationImg' src='{$icon}' />{$match[1]}", $match[0]);
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
        if(!trim($match[3])) return;

        // намираме мястото, което ще заместваме
        $place = $this->mvc->getPlace();
        
        // елемент съдържащ: телефонен номер или потребителско име/номер
        $matchElement = trim(mb_strtolower($match[2]));
        
        
        // Намираме иконата в sbf папката
        $nameIcon = str::utf2ascii($matchElement);
	    $icon = sbf("img/16/{$nameIcon}.png",'');
        
        // в зависимост от услугата, правим различни линкове
        switch ($matchElement) {
        	        	    
        	case 'msnim' :
        		$icon = sbf("img/16/msn.png",'');

        	case 'msn' :
        		$this->mvc->_htmlBoard[$place] = "<span class='communication'><a class='url' href='msnim:chat?contact={$match[3]}' title='MSN'>{$match[1]}</a></span>";
        		break;

        	case 'xmpp' :
        	case 'jabber' :
        		$this->mvc->_htmlBoard[$place] = "<span class='communication'><a class='url' href='xmpp:{$match[3]}' title='{$match[2]}'>{$match[1]}</a></span>";
        	    break;
        		 
	        case 'skype' : 
		        $skypeUser = trim($match[3]);
        	
        		$this->mvc->_htmlBoard[$place] = "<span class='communication'><a class='url' href='skype:{$skypeUser}?call' title='Skype'>{$match[3]}</a></span>";
		        break;
		        
	        case 'aim' : 
		        $this->mvc->_htmlBoard[$place] = "<span class='communication'><a class='url' href='aim:goim?screenname={$match[3]}' title='AOL Instant Messenger (AIM)'>{$match[1]}</a></span>";
		        break;
		        
	        case 'yim' :
		        $this->mvc->_htmlBoard[$place] = "<span class='communication'><a class='url' href='ymsgr:sendIM?{$match[3]}' title='Yahoo! Messenger'>{$match[1]}</a></span>";
		        break;
		 		        
        }        
    	$Email = cls::get('type_Email');
	    
    	// Ако мачнатият елемент е валиден имейл за системата
	    if($Email->isValidEmail($match[3])){
	    	 
	    	// оставяме връзката на имейла : изпращана на имейл от системата
	    	$communicationFormat = str_replace($match[3], $Email->toVerbal($match[3]), $match[0]);
	    	
	    	// и правим линк името на услугата
	    	// посочваме мястото където ще за заменят линковете
        	$communicationFormat = str_replace($match[1], "<img class='communicationImg' src='{$icon}' />[#{$place}#]", $communicationFormat);
	    } else {
	    	
	    	// линк е мачнатия елемент, не името на услугата
	    	// посочваме мястото където ще за заменят линковете
        	$communicationFormat = str_replace($match[3], "[#{$place}#]", $match[0]);
        	
        	// добавяме иконата пред името на услугата
        	$communicationFormat = str_replace($match[1], "<img class='communicationImg' src='{$icon}' />{$match[1]}", $communicationFormat);
	    }
    	
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
        if(!trim($match[3])) return;

        // намираме мястото, което ще заместваме
        $place = $this->mvc->getPlace();
        
        // елемент съдържащ: телефонен номер или потребителско име/номер
        $matchElement = trim(mb_strtolower($match[2]));
        
        // Намираме иконата в sbf папката
        $nameIcon = str::utf2ascii($matchElement);
	    $icon = sbf("img/16/{$nameIcon}.png",'');

		$this->mvc->_htmlBoard[$place] = "<span class='communication'><a class='url' type='application/x-icq' 
		href='http://www.icq.com/people/cmd.php?uin={$match[3]}&action=message'>{$match[3]}</a></span>";
		
	    // линк е мачнатия елемент, не името на услугата
	    // посочваме мястото където ще за заменят линковете
        $communicationFormat = str_replace($match[3], "[#{$place}#]", $match[0]);
        	
        // добавяме иконата пред името на услугата
        $communicationFormat = str_replace($match[1], "<img class='communicationImg' src='{$icon}' />{$match[1]}", $communicationFormat);
    	
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
        if(!trim($match[2]))  return;

        // намираме мястото, което ще заместваме
        $place = $this->mvc->getPlace();
        
        // елемент съдържащ: телефонен номер или потребителско име/номер
        $matchElement = trim(mb_strtolower($match[2]));
     
        // в зависимост от услугата, правим различни линкове
        switch ($matchElement) {   
        	        	
		        case 'email' :
		        case 'e-mail' :
		        case 'mail' :
                case 'имейл':
                case 'емайл':
                case 'е-майл':
		        case '@' :
		        	
		        	$icon = sbf("img/16/email.png",''); 
		        	
		        	break;
        }
         	    
        // добавяме иконата пред името на услугата
        $communicationFormat = str_replace($match[1], "<img class='communicationImg' src='{$icon}' />{$match[1]}", $match[0]);

    	
        return $communicationFormat;
    }
}