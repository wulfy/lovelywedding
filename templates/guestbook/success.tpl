{* Smarty *}
<div class='remerciements'>
    <div class='toda'>
        <img src='img/toda.jpg'/>
    </div>
    {if $update}
        <div class='merci_message'>
            Votre message a bien été mis à jour !
            <br>
        </div>
    {else}
        <div class='merci_message'>
            Merci pour votre message !
            <br>
            Il sera affiché après validation dans les prochains jours.
        </div>
    {/if}
</div>

<div id="little_menu_items">
    <ul>
        <li><div class='item'><a href="index.html"><img src='img/alliances.png' /><h3>Accueil</h3></a></div></li>
        <li><div class='item'><a href="maries.html"><img src='img/figurine_avion.png' /><h3>Les mariés</h3></a></div></li>
        <li><div class='item'><a href="infos.html"><img src='img/infos.png' /><h3>Infos</h3></a></div></li>
        <li><div class='item'><a href="livre_dor_read"><img src='img/livre_dor.png' /><h3>Livre d'Or</h3></a></div></li>
    </ul>
</div>
