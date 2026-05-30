

/**
MANAGE ANIMATION EFFECT

@author:ludovic
**/

function isScrolledIntoView(elem)
{
    var docViewTop = $(window).scrollTop();
    var docViewBottom = docViewTop + $(window).height();

	if($(elem).offset() != null)
	{
    var elemTop = $(elem).offset().top;
    var elemBottom = elemTop + $(elem).height();
	}

    return ((elemBottom <= docViewBottom) && (elemTop >= docViewTop));
}


function my_slider(sliderId,options) {
 this.current_options = options;
 this.delay = 3000;
 this.timer;
 this.animator;
 this.animation = 'fade';
 this.speed = 'normal';
 this.slidecontainer;
 this.slidecontainerId = sliderId;
 this.slides;
 this.currentSlide;
 
	 this.init = function(){
		this.slidecontainer = $('#'+this.slidecontainerId);
		if(typeof(this.current_options) == 'array')
		{
			if(typeof(this.current_options["delay"]) !== 'undefined')
				this.delay = this.current_options["delay"];
			if(typeof(this.current_options["animation"]) !== 'undefined')
				this.animation = this.current_options["animation"];	
			if(typeof(this.current_options["speed"]) !== 'undefined')
				this.speed = this.current_options["speed"];
			/*if(typeof(this.current_options["sliderId"]) !== 'undefined')
				this.slidecontainer = $('#'+this.current_options["sliderId"]);*/	
		}

		this.slides = $('img',this.slidecontainer);
		this.slides.hide();
		this.centerAll();
		this.slides.css('position','absolute');
		this.slides.first().show();
		
	}
	 
	 this.centerAll = function(){
		this.slides.each(function(){
			var width = $(this).parent().width() - $(this).width();
			margin	=	width/2;
			$(this).css("margin-left",margin+"px");
		});
	 }
	 
	 this.startpause = function() {
			
			if(this.pause)
			{
				this.start() ;
			}
			else
			{			
				this.stop();
			}
	}
	
	this.start = function() {
		var currentObj = this;
		this.timer = setInterval(function(){currentObj.animate()}, (currentObj.delay));
		this.currentSlide = this.slides.first();
		this.currentSlide.show();
	}
	
	this.stop = function() {				
				if(this.timer)
					clearInterval(this.timer);
	}
	
	this.animate = function(){
	
		//animate if visible
		if(isScrolledIntoView(this.slidecontainer))
		{
			this.animout(this.currentSlide);
			// Si l'image active courante n'est pas la dernière image de la liste
			if(!this.currentSlide.is(this.slides.last()))
			{		
			   //this.currentSlide = $(this.currentSlide.parentNode).find(".active");
				this.currentSlide = this.currentSlide.next();		
			}
			// L'image est la dernière de la liste
			else
			{
				this.currentSlide = this.slides.first();
				// On fait la même chose mais en prenant la première image de la liste via le sélecteur "first-child"
			}
			this.animin(this.currentSlide);
		}
	}
	
	this.animin = function(slide){
		slide.fadeIn();
	}
	
	this.animout = function(slide){
		slide.fadeOut();
	}
	
	this.init();
}






