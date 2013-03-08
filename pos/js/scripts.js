$(document).ready(function () {
	
	if($('tr').is('#last-row')) {
		$("#last-row").css("background-color", "#FFFF99");
	
	} else { 
		$(".scrollWrapper").scrollTop($(".scrollWrapper")[0].scrollHeight);
	}
	
	$('input[name=ean]').focus();
	
	$('input[name=quantity]').live("keyup",function(e) {
			if(e.keyCode != 173){
				$('input[name=ean]').focus();
			}
		});
	
	$('#incBtn').live("click", function() {
		$('input[name=quantity]').val(parseInt($("input[name=quantity]").val()) + 1);
	});
	
	$('#decBtn').live("click", function() {
		$('input[name=quantity]').val(parseInt($("input[name=quantity]").val()) - 1);
	});
	
	$('#subBtn').live("click", function() {
		$('input[name=quantity]').val(parseInt($("input[name=quantity]").val()) - 1);
	});
	
	$('.actionBtn').live("click", function() {
		var value = $(this).attr("data-type");
		$("select[name=action]").val(value);
		$(".actionBtn").not(this).removeClass('selectedPayButton');
		if($("input[name=ean]").val() != '') {
			$("#receipt-details-form form").submit();
		} 
	});

    $(function(){
	        if (typeof(window.WebScan) == "undefined" ) {
	            $('.webscan').hide();
	        }
	});
	
	$("form input[type=button]").live("hover", function(){$(this).toggleClass('button_hover');});
	$("form input[type=submit]").live("hover", function(){$(this).toggleClass('submit_hover');});
	
	$("input[disabled=disabled]").addClass("disabledBtn");
	$("input.disabledBtn").attr('title', 'Неможе да приключите бележката, докато не е платена');

	$(".pos-product-category[data-id='']").addClass('active');
	$('.pos-product-category').click(function() {
		var value = $(this).attr("data-id");
		
		$(this).addClass('active');
		$(".active").not(this).removeClass('active');
		
		if(value) {
			$("div.pos-product[data-cat != "+value+"]").each(function() {
				$(this).hide();
			});
			$("div.pos-product[data-cat = "+value+"]").each(function() {
				$(this).show();
			});
		} else {
			$("div.pos-product").each(function() {
				$(this).show();
			});
		}
	});
	
	$('.pos-product').click(function vote() {
		var rId = $('input[name=receiptId]').val();
		var action = "sale|code";
		var quantity = $('input[name=quantity]').val();
		var ean = $(this).attr("data-code");
		var cmd ={'default':1};
		var data = {receiptId:rId, quantity:quantity, ean:ean, action:action, Cmd:cmd, ajax_mode:1};
		
		$.ajax({
   	     type: "POST",
   	     data: data,
   	     dataType: 'json',
   	     success: function(result)
   	     { 
   	    	$(".single-receipt-wrapper").replaceWith(result);
   	    	$("#last-row").css("background-color", "#FFFF99");
   	    	$("input[disabled=disabled]").addClass("disabledBtn");
   	    	$("input.disabledBtn").attr('title', 'Неможе да приключите бележката, докато не е платена');
   	     },
   	     error: function(result)
   	     {
   	       alert('проблем със записването');
   	     }
   	     });
	});
});