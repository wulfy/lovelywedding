var Lesfonds = new Array("fond_accueil_1.jpg", "fond_accueil_2.jpg", "fond_accueil_3.jpg", "fond_accueil_4.jpg", "fond_accueil_5.jpg","fond_accueil_6.jpg","fond_accueil_7.jpg");
var background_image_container = $('#background_img');
var alternate_tempo = 10000;
function change_background(img){

$('#background_img').attr("src", img); 
}

function change_background_random()
{
	var randomnumber = Math.floor((Math.random()*Lesfonds.length)); 
	var radom_img = Lesfonds[randomnumber];
	change_background("img/"+radom_img);
}

function alternate_fade(){
	
    // affiche l'image
    $('#background_img').fadeIn('normal',function(){
        // attend 3 secondes
        setTimeout(function(){
            // cache l'image
            $('#background_img').fadeOut('normal',function(){
                // on passe à l'image suivante (avec boucle modulaire)
				$(this).hide();
				change_background_random();
                setTimeout(function(){
						alternate_fade();
				},100);
            });
        },alternate_tempo);
    });
}

function alternate_simple(){
change_background_random();
	setTimeout(function(){
		alternate_simple();
	},alternate_tempo);
}


$(window).load(function() {
change_background_random();
alternate_fade();
//alternate_simple();

});


