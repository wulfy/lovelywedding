/**
MANAGE PAGE SCROLLING WITH ANCHOR

@author:ludovic
**/
var is_chrome = navigator.userAgent.toLowerCase().indexOf('chrome') > -1;
var is_safari = navigator.userAgent.toLowerCase().indexOf('safari') > -1;

function scrollToId(id,callback)
{
	if(is_chrome || is_safari)
	{
		$('body').animate({scrollTop: $("div#"+id).offset().top},3500,null,callback);
	}
	else
	{
		$('html').animate({scrollTop: $("div#"+id).offset().top},3500,null,callback);
	}
}



