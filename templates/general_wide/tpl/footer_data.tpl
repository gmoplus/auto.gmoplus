<!-- footer data tpl -->

{include file='../img/social.svg'}

<div class="footer-data">
	<div class="icons mb-3 d-flex">
		{include file='menus/footer_social_icons.tpl' marginClass='mr-3'}
	</div>
	
	<div>
		&copy; {$smarty.now|date_format:'%Y'}, {$lang.powered_by}
		<a title="{$lang.powered_by} {$lang.copy_rights}" href="{$lang.flynax_url}">{$lang.copy_rights}</a>
	</div>
</div>

<!-- footer data tpl end -->
