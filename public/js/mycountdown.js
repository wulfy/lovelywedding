/*

requires Jquery 

*/

function MyCountDown() {

	this.currentDate = null;
	this.targetDate = null;
	this.currentTimestamp = 0
	this.currentTimerId = 0;
	this.targerDisplayer = null;

	this.setTargetDate = function(targetDateString) {
		this.targetDate = moment(targetDateString);
		this.currentDate = moment();
		this.currentTimestamp = this.targetDate.unix()-this.currentDate.unix();
	}
	
	this.setTargetDisplayer = function(displayerId) {
		this.targerDisplayer = $('#'+displayerId);
	}
	
	this.startCountDown = function(targetDateString) {
  	
		if(this.currentTimerId !=0)
		{
			clearInterval(this.currentTimerId);
		}
		var obj = this;
		this.currentTimerId = setInterval(function(){obj.countDown()}, 1000);
	}
	
	this.stopCountDown = function(targetDateString) {  	
		clearInterval(this.currentTimerId);
	}
	
	this.countDown = function(){
		/*this.targetDate.subtract('seconds', 1);*/
		
		this.currentTimestamp = this.currentTimestamp - 1;
		if(this.targerDisplayer != null)
		{
			/*var momentOnlyDays = this.targetDate;
			momentOnlyDays.startOf('day');*/
			
			var dayoff = this.currentTimestamp%86400;
			var days = parseInt(this.currentTimestamp/86400);
			
			var houroff = dayoff%3600;
			var hours = parseInt(dayoff/3600);
			
			var minutesoff = houroff%60;
			var minutes = parseInt(houroff/60);
			var seconds = minutesoff;
			/*
			var addMinutes = 0;
			var hour = 0;
			if(this.targetDate.hours()<this.currentDate.hours())
				hour = this.targetDate.hours() - this.currentDate.hours();
			else
				hour = 24 - (this.currentDate.hours()-this.targetDate.hours());
			
			var minutes = 0;
			if(this.targetDate.minutes()<this.currentDate.minutes())
				minutes = this.targetDate.diff(moment(),"minutes");
			else
				minutes = this.targetDate.diff(moment(),"minutes");
			var seconds = this.targetDate.diff(moment(),"seconds");
			var days	=	this.targetDate.diff(moment(),"days");*/
			
			var output = days+"  Jours <br>&nbsp;&nbsp;"+hours+" H&nbsp;&nbsp;"+minutes+" mn&nbsp;&nbsp;"+seconds+" S";
			
			this.targerDisplayer.html(output);
		}
	}

}