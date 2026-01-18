<!-- keyword search block -->

<form class="kws-block" method="post" action="{$rlBase}{if $config.mod_rewrite}{$pages.search}.html{else}?page={$pages.search}{/if}">
	<input type="hidden" name="form" value="keyword_search" />

    <input type="text" maxlength="255" name="f[keyword_search]" {if $smarty.post.f.keyword_search}value="{$smarty.post.f.keyword_search}"{/if} />
	<div class="controls">
        <input type="submit" name="search" value="{$lang.go}" />
        <span id="refine_keyword_opt" class="link">{$lang.advanced_options}</span>
	</div>

	<div class="options hide">
		<ul>
			{assign var='tmp' value=3}
			{section name='keyword_opts' loop=$tmp max=3}
				<li><label><input {if $fVal.keyword_search_type || $keyword_mode}{if $smarty.section.keyword_opts.iteration == $fVal.keyword_search_type || $keyword_mode == $smarty.section.keyword_opts.iteration}checked="checked"{/if}{else}{if $smarty.section.keyword_opts.iteration == $config.keyword_search_type}checked="checked"{/if}{/if} value="{$smarty.section.keyword_opts.iteration}" type="radio" name="f[keyword_search_type]" /> {assign var='ph' value='keyword_search_opt'|cat:$smarty.section.keyword_opts.iteration}{$lang.$ph}</label></li>
			{/section}
		</ul>
	</div>


</form>

<!-- keyword search block -->
