{* Smarty *}
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Formulaire Livre d'or</title>
<!--[if gte IE 7]>
<link href="css/ie.css" rel="stylesheet" type="text/css" />
<![endif]-->
<link href="css/styles.css" rel="stylesheet" type="text/css" />
<link href="css/livre_dor_write.css" rel="stylesheet" type="text/css" />
<style id="bsa_css" type="text/css">
.fancybox-iframe { overflow:hidden; }
</style>
</head>
<body>
<div class="all">
    <div id="menu"></div>
    <div id='contenu'>
        <div id="header">
            <div class='header_text'>Mariage Audrey et Ludovic
                <span> <br> 3 MARS 2013 </span>
            </div>
            <div class="news"> <a href="news.html"><img src="img/news_sepia.png"></a></div>
        </div>
        <div id="menu_items">
            <ul>
                <li><a href="index.html"><img src='img/alliances_sepia.png' /><div class='selector'> </div><h3>Accueil</h3></a></li>
                <li><a href="maries.html"><img src='img/figurine_avion_sepia.png' /><div class='selector'> </div><h3>Les mariés</h3></a></li>
                <li><a href="infos.html"><img src='img/infos_sepia.png' /><div class='selector'> </div><h3>Infos</h3></a></li>
                <li><a href="livre_dor_read"><img src='img/livre_dor.png' /><div class='selector'> </div><h3>Livre d'Or</h3></a></li>
            </ul>
        </div>
    </div>

    <div class='sections'>
        <div class='fond'></div>
        <div id='section1' class='section'>
            <img id='background_img' class='bg' src='img/fond_livres.jpg' />
            <div class="contenu">
                <div class='form_livre'>
                    <form id='livreform' method="POST" action="javascript:sendLivreDor()" enctype="multipart/form-data">
                        <input type="hidden" name="_csrf" value="{$csrf_token}">
                        <div class='form_infos'>
                            <div class='input'>
                                <h1>NOM</h1>
                                <input id='nom' type="text" name="nom" class='nom' value="{if isset($last_post_values.nom)}{$last_post_values.nom}{/if}"/>
                            </div>
                            <div class='prenom' aria-hidden="true" style="position:absolute;left:-9999px;">
                                <h1>prenom</h1>
                                <input id='prenom' type="text" name="prenom" class='prenom' autocomplete="off" tabindex="-1"/>
                                {if isset($last_post_values.id)}<input id="id" type="hidden" name="id" value="{$last_post_values.id}"/>{/if}
                            </div>
                            <div class='input'>
                                <h1>MAIL</h1>
                                <input id='email' type="email" name="email" class='mail' value="{if isset($last_post_values.email)}{$last_post_values.email}{/if}"/>
                            </div>
                            <div class='input'>
                                <h1>VILLE</h1>
                                <input type="text" name="ville" class='ville' value="{if isset($last_post_values.ville)}{$last_post_values.ville}{/if}"/>
                            </div>
                            <div class='input'>
                                <h1>Image associée</h1>
                                <span class='description'>L'image qui sera affichée sur la page de gauche de votre message</span><br/>
                                <input id="inputImage" type="text" name="image" value="{if isset($last_post_values.image)}{$last_post_values.image}{/if}">
                                <input id='testImage' type="button" value="tester" name="tester"/>
                                <a id="help" href='aide.html'><img src='img/help.png'/></a><br/>
                            </div>
                            <img id="previewImage" class='form_button' src="{if isset($last_post_values.image)}{$last_post_values.image}{else}img/no_image.png{/if}"/>
                        </div>
                        <div class='form_message'>
                            <h1>MESSAGE</h1>
                            <textarea id='message' name='message' class='message' cols='28' rows='17'>{if isset($last_post_values.message)}{$last_post_values.message}{/if}</textarea><br>
                            <span id='error'> Taille maximum atteinte !</span>
                            <input type="submit" value="envoyer" name="send" class='submit' />
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
{literal}
<script src="js/jquery.js" type="text/javascript"></script>
<script src="js/scripts.js" type="text/javascript"></script>
<script src="js/forms_scripts.js" type="text/javascript"></script>
<link rel="stylesheet" href="libs/fancyBox/source/jquery.fancybox.css?v=2.1.1" type="text/css" media="screen" />
<script type="text/javascript" src="libs/fancyBox/source/jquery.fancybox.pack.js?v=2.1.1"></script>
<script type="text/javascript">
var MAXLENGTH = 1900;
var $obj = $("textarea#message");
$obj.on('keyup', function(e){
    if($obj.val().length > MAXLENGTH+1) {
        $obj.val($obj.val().substring(0, MAXLENGTH));
        $.fancybox(
            "<center> <h1>Attention, le texte est trop long.</h1> <br>Il a &eacute;t&eacute; tronqu&eacute; &agrave; la taille maximum autoris&eacute;e</center>",
            { padding:15, closeBtn:true, autoSize:false, scrolling:'no',
              beforeLoad: function(){ this.width=350; this.height=200; }
            });
    }
});
$('#help').fancybox({
    'width':'800px','height':'500px','autoSize':false,'autoScale':true,
    'transitionIn':'none','transitionOut':'none','scrolling':'no','type':'iframe'
});
$obj.on('keydown', function(e){
    if ($obj.val().length >= MAXLENGTH) {
        $("#error").show().delay(2000).fadeOut(500);
        e.preventDefault();
    }
});

$("#inputImage").focusout(function() {
    $("#previewImage").attr({src:$(this).val()});
});

$('#testImage').click(function(){
    $.fancybox(
        "<center> Voici le rendu de l'image sur la page du livre d'or</center>"+
        "<div class='page' style='background-color:#E3D7CB;height:100%;width:100%;position:absolute;'>"+
        "<div class='message' style='padding-left:20px;'> <img style='width:300px;max-height:400px' src='"+$("input[name='image']").val()+"'/></div></div>",
        { padding:15, closeBtn:true, autoSize:false, scrolling:'no',
          beforeLoad: function(){ this.width=350; this.height=500; }
        });
});

function colorizedErrorFields(fields){
    for (var i in fields) { $("#"+fields[i]).css("border",'1px solid red'); }
}

function sendLivreDor(){
    $.post("livre_dor_write", $("#livreform").serialize(), function(data) {
        $.fancybox(data, { padding:15, closeBtn:true, autoSize:false,
            beforeLoad: function(){ this.width=550; this.height=380; }});
    }).done(function() {
        setTimeout(function(){
            var s = ''+$('#fieldsInError').data('fields');
            colorizedErrorFields(s.split(','));
        }, 1000);
    }).fail(function() {
        alert("Erreur lors de l'envoi, veuillez reessayer ou nous contacter: contact@lovelywedding.fr");
    });
}
</script>
{/literal}
<!-- GA -->
{literal}
<script type="text/javascript">
var _gaq = _gaq || [];
_gaq.push(['_setAccount', 'UA-36817805-1']);
_gaq.push(['_trackPageview']);
(function() {
    var ga = document.createElement('script'); ga.type='text/javascript'; ga.async=true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
})();
</script>
{/literal}
</body>
</html>
