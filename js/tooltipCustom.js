function tooltipCustom(){
	if($('.tooltip-button').length){
		var checkVisibility = $('.tooltip-text');
		if(checkVisibility.hasClass('show-tooltip')){
			$('.tooltip-text').css("display","block");
		}
	
		//изчислява на позицията на стрелката
		setArrowPosition();
		
		//задава като max-width на тултипа разстоянието до активния таб
		setTooltipMaxWidth();
		
		if (isTouchDevice()){
			//ако сменим ориентацията на телефона, изчисляваме отново позицията на стрелката
			$(window).resize( function() {
				setArrowPosition();
				setTooltipMaxWidth();
			});
		}
		
		//при клик на бутона, да се скрива и показва инфото и да се изчисли позицията на стрелката
		 $('.tooltip-button').click(function(e) {
		     $('.tooltip-text').fadeToggle("slow");
		     setArrowPosition();
		     e.stopPropagation();
		 });
		 
		//при клик на `x` да се скрива тултипа
		 $('.close-tooltip').click(function() {
			 $('.tooltip-text').fadeOut("slow");
		 });
		 
	}
}

function setTooltipMaxWidth(){
	var tooltip = $('.tooltip-button');
	var mwidth = tooltip.offset().left;
	if(mwidth > 700){
		$('.tooltip-text').css('maxWidth', mwidth);
	}
}

function setArrowPosition(){
	
	var leftOffet = $('.tooltip-button').offset().left;
	var leftOffetBlock = $('.tooltip-text').offset().left;
	
	//заради разликата в големината на картинката в двата изгледа
	var offset = 2;
	if($('body').hasClass('narrow')) {
		offset=0;
	}
	
	leftOffet = parseInt(leftOffet) - parseInt(leftOffetBlock) - offset;
	$('.tooltip-arrow').css("left",leftOffet ) ;
	
}

function isTouchDevice(){
	return !!('ontouchstart' in window);
}

