<!-- footer data tpl -->

{include file='../img/social.svg'}

<div class="footer-data row mt-4">
    <div class="icons justify-content-start justify-content-md-end col-12 col-sm-4 order-sm-2 d-flex">
        {include file='menus/footer_social_icons.tpl'}
    </div>

    <div class="align-self-center col-12 mt-4 mt-sm-0 col-sm-8 font-size-xs">
        &copy; {$smarty.now|date_format:'%Y'}, {$lang.powered_by}
        <a title="{$lang.powered_by} {$lang.copy_rights}" href="{$lang.flynax_url}">{$lang.copy_rights}</a>
    </div>
</div>

<!-- footer data tpl end -->
