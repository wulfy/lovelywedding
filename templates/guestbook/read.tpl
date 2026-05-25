{* Smarty *}
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Livre d'or</title>
<!--[if gte IE 7]>
<link href="css/ie.css" rel="stylesheet" type="text/css" />
<![endif]-->
<link href="css/styles.css" rel="stylesheet" type="text/css" />
<link href="css/livre_dor_read.css" rel="stylesheet" type="text/css" />
<link href="css/jquery.booklet.1.4.0.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="css/lightbox/lightbox.css" type="text/css" media="screen" />
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
                    <div id='loadingLivre'>
                        Chargement en cours<br>
                        <img src='/img/loading.gif'>
                    </div>
                    <div id="mybook">
                        <div class='page'>
                            <div class='message intro'>
                                <h3>Le message le plus original gagnera une surprise durant la reception !</h3>
                                <img src='img/separateur.png' width='100%' />
                            </div>
                            <div class='message writeUs'>
                                <a href='livre_dor'><img class='plume' src='img/plume.png' /> Laissez nous aussi un message</a>
                            </div>
                        </div>
                        <div class='page'>
                            <div class='message'>
                                <img src='img/logo-mariage.png' width='100%'/>
                                <br/>
                                <h1> Livre d'or </h1>
                            </div>
                        </div>
                        {assign var="nbpages" value=(($pages|count)*2)}
                        {foreach from=$pages item=page}
                            <div class='page'>
                                <div class='message'>
                                    {if isset($page.image) && strlen($page.image)>1}
                                        <a href='{$page.image|escape:'htmlall'}' rel="lightbox"> <img src='{$page.image|escape:'htmlall'}' width='100%'/> </a>
                                    {/if}
                                </div>
                                {if isset($page.editable) && $page.editable}
                                    <div class='editLink'>
                                        <a href='livre_dor?id={$page.id|escape:'url'}'>Editer votre message</a>
                                    </div>
                                {/if}
                            </div>
                            {assign var='page2' value=''}
                            {if strlen($page.message)>700}
                                {assign var='pageTab' value=($page.message|substr:670:30)}
                                {assign var='pageTab' value=" "|explode:$pageTab}
                                {assign var='lengthMax' value=(670+strlen($pageTab[0]))}
                                {assign var='page1' value=($page.message|substr:0:$lengthMax)}
                                {assign var='page2' value=($page.message|substr:$lengthMax:strlen($page.message))}
                            {else}
                                {assign var='page1' value=$page.message}
                            {/if}
                            <div class='page'>
                                <h3>{$page.nom|upper|truncate:20}</h3>
                                <span class='date'>{$page.date|date_format:"%d-%m-%Y"}</span>
                                <div class='message'>
                                    <span class='firstLetter'>{$page1|substr:0:1}</span>{$page1|substr:1|nl2br}
                                </div>
                            </div>
                            {if strlen($page2)>2}
                                <div class='page'>
                                    <div class='message'>
                                        {$page2|substr:0:800|nl2br}
                                    </div>
                                </div>
                                <div class='page'>
                                    <div class='message'>
                                        {$page2|substr:800:500|nl2br} ...
                                    </div>
                                </div>
                                {assign var="nbpages" value=($nbpages+2)}
                            {/if}
                        {/foreach}
                        <div class='page'>
                            <div class='message'>
                                <img src='img/just.png' width='100%'/>
                            </div>
                        </div>
                        {assign var="nbpages" value=($nbpages+2)}
                    </div>
                </div>
                <div class='marquepage'>
                    <form action='javascript:gotopage()'><input type='text' id='pagenumber' size='1' value='{$nbpages}' /></form>
                    <div class='marque_text'><a href='#' id='last' >Aller à la page</a></div>
                </div>
            </div>
        </div>
    </div>
</div>
{literal}
<script src="js/jquery.js" type="text/javascript"></script>
<script src="js/scripts.js" type="text/javascript"></script>
<script src="js/lightbox.js"></script>
<script src="js/pageScroller.js" type="text/javascript"></script>
<script src="js/jquery.booklet.1.4.0.min.js" type="text/javascript"></script>
<script src="js/jquery.easing.1.3.js" type="text/javascript"></script>
<script src="js/jquery-ui-1.8.21.custom.min.js" type="text/javascript"></script>
<script type="text/javascript">
$('#loadingLivre').show();
var nbpages = {/literal}{$nbpages}{literal};

function gotopage() {
    var page = parseInt($("#pagenumber").val());
    if(isNaN(page)) page='start';
    else {
        if(page>nbpages || page<0) page='end';
        else if(page==0) page=1;
        else if(page%2==0) page = page-1;
    }
    $('#mybook').booklet("gotopage", page);
}
$(window).ready(function() {
    $('#loadingLivre').hide();
    $(function() {
        $('#mybook').booklet({ closed:false, width:710, height:550 });
    });
    $('#mybook').fadeIn(1000);
    $('#last').click(function(e){ e.preventDefault(); gotopage(); });
    $("#pagenumber").val(nbpages);
});
</script>
<!-- GA -->
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
