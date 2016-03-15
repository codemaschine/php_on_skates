
// http://stackoverflow.com/questions/1120335/how-to-make-css-visible-only-for-opera
// Sets browser infos as html.className
var params = [];
$.each($.browser, function(k, v) {
  var pat = /^[a-z].*/i;
  if(pat.test(k)) { params.push(k); }
});
params = params.join(' ');
$('html').addClass(params);
// ------------------------------------





function createCookie(name,value,days) {
				if (days) {
					var date = new Date();
					date.setTime(date.getTime()+(days*24*60*60*1000));
					var expires = "; expires="+date.toGMTString();
				}
				else var expires = "";
				document.cookie = name+"="+value+expires+"; path=/";
			}
			
function readCookie(name) {
	
				var nameEQ = name + "=";
				var ca = document.cookie.split(';');
				for(var i=0;i < ca.length;i++) {
					var c = ca[i];
					while (c.charAt(0)==' ') c = c.substring(1,c.length);
					if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
				}
				return null;
			}

function get_url_param( name )
{
	name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
	var regexS = "[\\?&]"+name+"=([^&#]*)";
	var regex = new RegExp( regexS );
	var results = regex.exec( window.location.href );

	if ( results == null )
		return "";
	else
		return results[1];
}

/* AJAX INdicator - noch noch ausgereift */
/*
$(document).ready(function() {
  	ajaxIndicatorForms();
});
function ajaxIndicatorForms(){
	  $('input[type="submit"],.ym-button').click(function(){
		origbg = $(this).css("background-image");
		origbgpos = $(this).css("background-position");
		$(this).attr("origbg",origbg).attr("origbgpos",origbgpos).addClass("ajaxindicator").css("background-image","url(img/ajax-loader-indicator.gif)").css("background-repeat","no-repeat").css("background-position","2px 5px");
		window.setTimeout("removeAjaxIndicator()",1000);
	});
}

function removeAjaxIndicator(orig){
	$('.ajaxindicator').css('background-image',$('.ajaxindicator').attr("origbg")).css('background-position',$('.ajaxindicator').attr("origbgpos")).removeClass("ajaxindicator");
}

*/
function delete_message_elems(elem_selector, elem_message_class_selector, success_message, callback) {
	if (!success_message)
		success_message = '<em>Das Anliegen wurde gelöscht</em>';
	$(elem_selector + ' ' + elem_message_class_selector).fadeTo(1000, 0.3, function(){ $(elem_selector + ' ' + elem_message_class_selector).html(success_message).parents(elem_selector).delay(3000).slideUp(2000, function(){ $(elem_selector).remove(); if (callback) callback(); });});
}

function show_hidden_elements(elems_container, placeholder) {
	$(elems_container).slideDown(1000);
	$(placeholder).slideUp(500);
}



/*
 * Viewport - jQuery selectors for finding elements in viewport
 *
 * Copyright (c) 2008-2009 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *  http://www.appelsiini.net/projects/viewport
 *
 */
    $.belowthefold = function(element, settings) {
        var fold = $(window).height() + $(window).scrollTop();
        return fold <= $(element).offset().top - settings.threshold;
    };

    $.abovethetop = function(element, settings) {
        var top = $(window).scrollTop();
        return top >= $(element).offset().top + $(element).height() - settings.threshold;
    };

    $.rightofscreen = function(element, settings) {
        var fold = $(window).width() + $(window).scrollLeft();
        return fold <= $(element).offset().left - settings.threshold;
    };

    $.leftofscreen = function(element, settings) {
        var left = $(window).scrollLeft();
        return left >= $(element).offset().left + $(element).width() - settings.threshold;
    };

    $.inviewport = function(element, settings) {
        return !$.rightofscreen(element, settings) && !$.leftofscreen(element, settings) && !$.belowthefold(element, settings) && !$.abovethetop(element, settings);
    };

    $.extend($.expr[':'], {
        "below-the-fold": function(a, i, m) {
            return $.belowthefold(a, {threshold : 0});
        },
        "above-the-top": function(a, i, m) {
            return $.abovethetop(a, {threshold : 0});
        },
        "left-of-screen": function(a, i, m) {
            return $.leftofscreen(a, {threshold : 0});
        },
        "right-of-screen": function(a, i, m) {
            return $.rightofscreen(a, {threshold : 0});
        },
        "in-viewport": function(a, i, m) {
            return $.inviewport(a, {threshold : 0});
        }
    });
	

function scrollTo(target, options, complete) {
	
	if (options == null)
		options = new Object;
	
	if (options.offset === undefined)
		options.offset = 0;
	
	if (options.delay) {
		var delay = options.delay;
		options.delay = null;
		window.setTimeout(function(){ scrollTo(target,options,complete); }, delay);
	}
	else {
		if(!$.inviewport( $(target), {threshold:0} )){
			 $('html, body').animate({
				 scrollLeft: $(target).offset().left,
				 scrollTop: $(target).offset().top + options.offset   // iPad erkennt leider nur Letztes, scrollLeft geht verloren. 
			 }, 500, null, complete);
		}
	}
}

function scrollReached(elem) {
	return $(elem).size() && document.documentElement.clientHeight + $(document).scrollTop() >= $(elem).offset().top
}


   /* Den Content anzeigen */
  function show_content(layer){
	  
	  hide_content(); 
	   
	  if(layer=="") layer ="content div";
	  $('#teaser_text').hide();
	  $('#interaction_forms').slideUp(500);
	  $('#' + layer).slideDown(500, function() { $('#teaser_text').hide(); });
	  scrollTo(".main-first #content"); 
	  window.setTimeout('$(\'.main-first\').css(\'backgroundSize\',\'cover\');',250);
	  

  }


   /* Den Content verstecken */
  function hide_content(layer){
	  
	 window.setTimeout('$(\'.main-first\').css(\'backgroundSize\',\'\');',250);
	  $('#interaction_forms').slideDown(500).animate({opacity: 1}, 800);
      $(".dynload").slideUp(500, function() { $('#teaser_text').show(); });
	  scrollTo(".logobox"); 
  }


/*  BACKGROUND  
      setbg(36);
      // setquote("right","right");
      
      for (var i=1;i<=36;i++)
        { 
        $("#testbilder").append("<a href='javascript:setbg("+i+")' style='white-space: nowrap;float:left;'><span style='text-shadow:0px 0px 3px #000000,0px 0px 3px #000000;margin:20px -45px 0 45px;position:absolute'>" + i + "</span><img src='https://dl.dropbox.com/u/2216407/amentest" + i + ".jpg'></a> ");
        }

  */
  
  
function initContent(selector){
    if (typeof(selector) === 'undefined')
    	selector = '';
    else
    	selector += ' ';
    
	$(selector + 'textarea.limited_to').each(function(){
		var maxSize, classList = $(this).attr('class').split(/\s+/);
		for(var i = 0; i < classList.length; i++) {
			console.log(parseInt(classList[i]));
			if (!isNaN(parseInt(classList[i]))) {
				maxSize = parseInt(classList[i]);
				break;
			}
		}
		if (!maxSize)
			maxSize = 1500;
		console.log("maxSize: "+ maxSize);
		$(this).textareaCount({
			'maxCharacterSize': maxSize,
			'originalStyle': 'originalTextareaInfo',
			'warningStyle' : 'warningTextareaInfo',
			'warningNumber': 40,
			'displayFormat' : '#input/#max'
		});
	});
	
	
	init_readmore_at();
	
	$(selector + 'div.readmore').expander({
        slicePoint: 500,
        expandText: ' [mehr anzeigen]',
        userCollapseText: ' '
    });
}

$(document).ready(function() {
    //set_bg();
	/* window.setInterval("set_bg()",60000); */
	
    initContent();
});  

function init_readmore_at(selector) {
	if (typeof(selector) === 'undefined')
    	selector = '';
    else
    	selector += ' ';
    
	$(selector + '.readmore_at').each(function(){
		var maxSize, classList = $(this).attr('class').split(/\s+/);
		for(var i = 0; i < classList.length; i++) {
			if (!isNaN(parseInt(classList[i]))) {
				maxSize = parseInt(classList[i]);
				break;
			}
		}
		if (!maxSize)
			maxSize = 80;
		
		$(this).expander({
	        slicePoint: maxSize,
	        expandText: '<span class="moreorless moreorless_more">mehr</span>',
	        userCollapseText: '<div class="moreorless moreorless_less">weniger</div>'
	    });
	});
}


$(document).keyup(function(e) {
	 if (e.keyCode == 27) {  // esc
		  $('.close_on_esc').remove();
		  $('.remove_on_esc').remove();
		  $('.hide_on_esc').hide();
     }
});








function counter_marked(direction){
	var bg = $(".icon_star").css("background-color");
	
	akt_count=$(".counter_marked").html();
	if(direction>0){
		new_count=parseFloat(akt_count)+1;
		$(".counter_marked").html(new_count);
		ani_color = "#91B632";
		$(".icon_star").css("transition","all 0.3s").css({ backgroundColor: ani_color,height: "22px",top: "6px"}); 
		window.setTimeout(function(){$(".icon_star").css("transition","all 1s,background-color 2.5s").css({ backgroundColor: "",height: "28px",top: "0px"});},200);
	} else if(direction<0 && parseFloat(akt_count)!=0){
		new_count=parseFloat(akt_count)-1;
		$(".counter_marked").html(new_count);
		ani_color = "#CACACA";
	}else{
		$(".counter_marked").html("0");
		new_count = 0;
		ani_color = "#FD8325";
	}
	if(new_count==1) 
		$(".icon_star span.singular").show();
	else
		$(".icon_star span.singular").hide();
	
}


function activate_microprayer(){
	
		$(".prayers_list .single_prayer:not(.microprayerprepared)").each(function(){
		
			mopper = $(this);
			origheight = mopper.css("height");
			mopper.attr("origheight",origheight);
			
			if(mopper.find(".flag.new").length==0 && mopper.find(".badge.new_feedbacks_count").length==0) // Neue offen lassen 
				mopper.addClass("microprayer").css({ height:'50px', overflow: 'hidden' }).animate({ marginTop: '20px' }, 300);
			
			if($(".mobile_view").css("display")=="none" && navigator.userAgent.match(/iPad/i) != null && navigator.userAgent.match(/Android/i) != null)	
				mopper.addClass("microprayerprepared").find(".single_prayer_header").hover(function(){
					
					if($(this).parent().parent().hasClass("microprayer")){
						$(this).find(".prayer_times,.status_icons").fadeOut(100);
						$(this).find(".microprayer_hover_info").fadeIn(200);
					}
				},function(){
					
					if($(this).parent().parent().hasClass("microprayer")){
						$(this).find(".microprayer_hover_info").fadeOut(100);
						$(this).find(".prayer_times,.status_icons").fadeIn(200);
					}
				});
			mopper.addClass("microprayerprepared").find(".single_prayer_header").click(function(){
					$(this).find(".microprayer_hover_info").fadeOut(100);
					$(this).find(".prayer_times,.status_icons").fadeIn(300);
					
					$(".prayers_list .single_prayer:not(.microprayer)").addClass("microprayer").css({ overflow:"hidden" }).animate({ height:'50px', marginTop: '20px' },{ duration: 300, queue: false });
					
					o = $(this).parent().parent().attr("origheight");
					oid = $(this).parent().parent().prev().attr("id");
					
					$(this).parent().parent().animate({ height: o , marginTop: '10px' },{ duration: 300, queue: false }).removeClass("microprayer");
					$(this).parent().parent().find(".single_prayer_container").css("box-shadow", "0 1px 9px #ffffff").animate({ boxShadow: '0 1px 9px #CCCCCC'},{ duration: 1000, queue: false });
					window.setTimeout('auto_height("#' + oid + '");',350);
					
				});
			
		});

	}

	function deactivate_microprayer(){
		$(".microprayerprepared").removeClass("microprayer microprayerprepared");
		$(".prayers_list .single_prayer").css({ height:"auto",overflow:"visible"});
		$(".single_prayer_header").off("hover click");
		$(".prayers_list .single_prayer").animate({ marginTop: '50px' }, 300);
	}
	
	function auto_height(scroller){
		$(".prayers_list .single_prayer:not(.microprayer)").css({ height:"auto",overflow:"visible"});
		scrollTo(scroller);
	}

	// Neuflags mit Prayerid markieren, damit javascript das Badge runterzählen kann
	function mark_flags(){
		$(".single_prayer").each(function() {
			id = $(this).attr("id");
			$(this).find(".flag.new").attr("prayerid",id);
		});
	}


	function toggle_activity(){
		
		if($("#toggle_activity input").is(":checked")){
			createCookie("activity_show_only_updates",1,999);
			$("div.activity.single_feedback_news:not(.activity_update)").slideUp();
			$("#toggle_activity #other_activities span").html($("div.activity.single_feedback_news:not(.activity_update)").length);
			$("#toggle_activity #other_activities").show("fast");
		}else{
			createCookie("activity_show_only_updates","");
			$("div.activity.single_feedback_news:not(.activity_update)").slideDown();
			$("#toggle_activity #other_activities").hide("fast");
		}
		
	}


function scrollToNextToDo(part){
		
		var direct = 0 ;
		doscroll = 0;
		if(part=="prayers")
			single = ".single_prayer:not(.done) .prayer_message_interactions";
		else if(part=="updates")
			single = ".single_feedback.update:not(.done) .prayer_feedback_interactions";
		else if(part=="feedbacks")
			single = ".single_feedback:not(.done) .prayer_feedback_interactions";
		else {
			single = part;
			direct = 1;
		}
		target = $(single+":visible");
		oset = target.offset();
		if(oset){
			 osettop = oset.top;
			 doscroll = 1;
			
		}else if(!direct){
			
			oset = $(".hole10" + part).offset();
			osettop = oset.top + 50;
			target = $(part);
			doscroll = 1;
		
		}
		if(oset && doscroll){
			var fensterhoehe = window.innerHeight - target.height();
			var scrollmeto = (osettop - fensterhoehe + 250); 
		
		// $.scrollTo(oset.top - fensterhoehe);
			$('html, body').animate({
					 scrollTop: scrollmeto  
				 }, 500);
		}
	}
	
	
// Bei gelesenen Elementen die Badges ausfaden
	
	
	

$(document).ready(function() {
	
	
	
	$('input[type=checkbox].switch').each(function(){
		var $label = $(this).next('label');
		
		if ($label.size()) {
			$label.prepend('<span>&nbsp</span>');
		}
		else {
			$label = $(this).prev('label');
			if ($label.size()) {
				$label.append('<span>&nbsp</span>');
			}
		}
		if ($label.size()) {
			$(this).css({visibility: 'hidden'});
			if($(this).attr('checked') == 'checked') {
	  			$label.find('span').removeClass("off").addClass('on');
	  		}
			else
				$label.find('span').removeClass("on").addClass('off');
		}
	});
	$('input[type=checkbox].switch').change(function(){
		var $label = $(this).next('label');
		if (!$label.size())
			$label = $(this).prev('label');
		if ($label.size()) {
			if($(this).attr('checked') == 'checked') {
	  			$label.find('span').removeClass("off").addClass('on');
	  		}
			else
				$label.find('span').removeClass("on").addClass('off');
		}
	});
	
	
	if($(".single_prayer_container").length){
		
		setInterval( function()
							{
				
								// Wenn im ViewPort, dann ausfaden
								$(".flag.new").not(".faded").each( function(i)
								{	
									if ( this && $.inviewport( this, {threshold:0} ) )
									{
										$(this).addClass("faded").animate({opacity:0.2},4000);
										
										// Prayer-Badge aktualisieren
										if($(this).parent().hasClass("single_feedback")){
											prayer = $(this).attr("prayerid");
											count = $("#"+prayer+" .badge.new_feedbacks_count").html()-1;
											if(count==0)
												$("#"+prayer+" .badge.new_feedbacks_count").html(count).animate({opacity:0.2},2000);
											else	
												$("#"+prayer+" .badge.new_feedbacks_count").html(count);
										}
										
										// Tab-Badge aktualisieren
										count_pray = $(".tabs span .badge.prayersupdates").html();
										count_feed = $(".tabs span .badge.feedbacks").html();
			
										if($(this).parent().hasClass("update") || $(this).parent().hasClass("single_prayer")){
											count_pray = count_pray-1;
											$(".tabs span .badge.prayersupdates").html(count_pray);
											if(count_pray == 0){
												$(".tabs span .badge.prayersupdates").hide();
												$(".tabs span .badge").removeClass("half");
											}
										}else{
											count_feed = count_feed-1;
											$(".tabs span .badge.feedbacks").html(count_feed);
											if(count_feed == 0){
												$(".tabs span .badge.feedbacks").hide();
												$(".tabs span .badge").removeClass("half");
											}
										}
									}
								});
								
							},3000);
								
	}
});