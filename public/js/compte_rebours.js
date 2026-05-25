$(document).ready(function() {
$(".postit .figurine").click(function(){
  $(this).parent().children('.text').animate({
    height: "100px"
  }, 250 );
}); 
});


