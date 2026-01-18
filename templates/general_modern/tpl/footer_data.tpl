<!-- footer data tpl -->

{include file='../img/social.svg'}

<div class="footer-data">
    <div class="icons justify-content-center justify-content-md-end d-flex mt-2 mb-2 mt-lg-0 mt-mb-0">
        {include file='menus/footer_social_icons.tpl' marginClass='ml-3 mr-3'}
    </div>

    {if !$noCP}    
    <div>
        &copy; {$smarty.now|date_format:'%Y'}, {$lang.powered_by}
        <a title="{$lang.powered_by} {$lang.copy_rights}" href="{$lang.flynax_url}">{$lang.copy_rights}</a>
    </div>
    {/if}
</div>

<!-- footer data tpl end -->
