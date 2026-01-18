<!-- edit_banner tpl -->

{assign var='sPost' value=$smarty.post}

<div class="area_form">
    <form id="banners-form"  method="post" action="{$rlBase}{if $config.mod_rewrite}{$pageInfo.Path}.html?id={$bannerData.ID}{else}?page={$pageInfo.Path}&id={$bannerData.ID}{/if}" enctype="multipart/form-data">
    <input type="hidden" name="submit_form" value="1" />
    <div class="content-padding">
        <!-- fields block -->
        {strip}

        <div class="submit-cell">
            <div class="name">
                {$lang.name} <span class="red">*</span>
            </div>

            <div class="field single-field" id="sf_field_name">
                {if $languages|@count > 1}
                    <ul class="tabs tabs-hash">
                        {foreach from=$languages item='language' name='langF'}
                            <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>
                                <a href="#name_{$language.Code}" data-target="name_{$language.Code}">
                                    {$language.name}
                                </a>
                            </li>
                        {/foreach}
                    </ul>
                    <div class="ml_tabs_content light-inputs">
                        {foreach from=$languages item='language' name='langF'}
                        {assign var='l_code' value=$language.Code}
                        <div lang="{$l_code}" 
                            {if !$smarty.foreach.langF.first}class="hide"{/if} 
                            id="area_name_{$l_code}">
                            <input type="text" name="name[{$l_code}]]" maxlength="255" value="{$sPost.name.$l_code}" />
                        </div>
                        {/foreach}
                    </div>
                {else}
                    <input type="text" name="name" maxlength="255" value="{$sPost.name}" />
                {/if}
            </div>
        </div>

        <div class="submit-cell">
            <div class="name">
                {$lang.banners_bannerBox}
            </div>

            <div class="field single-field" id="sf_field_banner_box">
                <input type="text" class="disabled" value="{$bannerData.Box.name}" disabled="disabled" />
            </div>
        </div>

        <div class="submit-cell">
            <div class="name">
                {$lang.banners_bannerType}
            </div>

            <div class="field single-field" id="sf_field_banner_type">
                {if $plan_info.types|@count > 1}
                    <select name="banner_type">
                        {foreach from=$plan_info.types item='type'}
                            {if $type.key == 'flash'}
                            <option disabled {if 'flash' == $sPost.banner_type}selected{/if}>{$lang.banners_flash_deprecated}</option>
                            {else}
                            <option value="{$type.key}" {if $type.key == $sPost.banner_type}selected{/if}>{$type.name}</option>
                            {/if}
                        {/foreach}
                    </select>
                {else}
                    <input type="hidden" name="banner_type" value="{$bannerData.Type.key}" />
                    <input type="text" class="disabled" value="{$bannerData.Type.name}" disabled="disabled" />
                {/if}
            </div>
        </div>

        {if $sPost.banner_type == 'html'}
        <div class="submit-cell">
            <div class="name">{$lang.banners_html_responsive}</div>
            <div class="field checkbox-field" id="sf_field_banner_html_responsive">
                <span class="custom-input">
                    <label><input type="radio" value="1" name="responsive" {if $sPost.responsive}checked="checked" {/if}/>{$lang.yes}</label>
                </span>
                <span class="custom-input">
                    <label><input type="radio" value="0" name="responsive" {if !$sPost.responsive}checked="checked" {/if}/>{$lang.no}</label>
                </span>
            </div>
        </div>
        {/if}

        <div class="submit-cell {if $sPost.banner_type && $sPost.banner_type != 'image'}hide{/if}" id="b_link">
            <div class="name">
                {$lang.banners_bannerLink}
            </div>

            <div class="field single-field" id="sf_field_banner_link">
                <input type="text" name="link" value="{$sPost.link}" />
            </div>
        </div>

        <div class="submit-cell {if $sPost.banner_type != 'html'}hide{/if}" id="btype_html">
            <div class="name">
                {$lang.banners_bannerType_html} <span class="red">*</span>
            </div>

            <div class="field single-field" id="sf_field_banner_html_text">
                <textarea id="banner_html" name="html" rows="3" cols="">{$sPost.html}</textarea>
            </div>
        </div>
        {/strip}
        <!-- fields block end -->
    </div>
    </form>

    <div class="submit-cell {if $sPost.banner_type != 'image'}hide{/if}" id="btype_image">
        <div class="name">
            {$lang.file} <span class="red">*</span>
        </div>
        <div class="field" id="sf_field_banner_media_file" style="padding-top: 10px;">
            {include file=$smarty.const.RL_PLUGINS|cat:'banners/upload/account_manager.tpl' boxInfo=$bannerData.Box}
        </div>
    </div>

    <div class="submit-cell form-buttons">
        <div class="name"></div>
        <div class="field"><input type="submit" id="banners_submit_button" value="{$lang.save}"/></div>
    </div>
</div>

<script class="fl-js-static">
{literal}
    $(document).ready(function() {
        $('select[name=banner_type]').change(function() {
            var new_type = $(this).val();

            $('#b_link').css('display', (new_type == 'image') ? 'block' : 'none');
            $('#btype_html, #btype_image').fadeOut('fast', function() {
                $('#btype_'+ new_type).show();
            });
        });
    });
{/literal}
</script>

<script class="fl-js-dynamic">
    {literal}
    $(document).ready(function() {
        $('input#banners_submit_button').click(function() {
            $('form#banners-form').submit();
        });
    });

    {/literal}
</script>

<!-- edit_banner tpl end -->
