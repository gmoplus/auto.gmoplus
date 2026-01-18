{include file='../img/social.svg'}

<div class="footer-copyrights col-12 col-md-8">
    &copy; {$smarty.now|date_format:'%Y'}, {$lang.powered_by}
    <a title="{$lang.powered_by} {$lang.copy_rights}" href="{$lang.flynax_url}">{$lang.copy_rights}</a>
</div>

<div class="icons mt-3 mt-md-0 d-flex justify-content-center col-12 col-md-4">
    {include file='menus/footer_social_icons.tpl' marginClass='ml-3 mr-3'}
</div>
