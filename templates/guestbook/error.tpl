{* Smarty *}
<div id='fieldsInError' data-fields="{$jsonFields}"></div>
<div class='error'>
    <div class='error_image'>
        <img src='img/attention-mariage.jpg'/>
    </div>
    <ul>
        {foreach from=$errors item=error}
            <li>{$error}</li>
        {/foreach}
    </ul>
</div>
