<!-- remote advert tpl -->

<script>
var rlUrlHome = '{$smarty.const.RL_URL_HOME}';
</script>
<script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}jquery/jquery.categoryDropdown.js"></script>

<div class="row">
    <div class="col-md-8 col-lg-7">
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' name=$lang.jl_box_settings id='jbox_settings'}
        {if $listing_types|@count > 1}
        <div class="submit-cell clearfix">
            <div class="name">
                {$lang.listing_type}
            </div>
            <div class="field single-field">
                <select name="listing_type">
                    <option value="0">{$lang.all}</option>
                    {foreach from=$listing_types key="key" item="listing_type" name="ltLoop"}
                        <option value="{$listing_type.Key}">{$listing_type.name}</option>
                    {/foreach}
                </select>
            </div>
        </div>
        {/if}

        <div id="categories_cont" class="submit-cell hide">
            <div class="name">
                {$lang.category}
            </div>
            <div class="field">
                <select name="category_id"></select>
            </div>
        </div>
        <div class="submit-cell">
            <div class="name">
                {$lang.jl_box_view}
            </div>
            <div class="field inline-fields">
                <span class="custom-input">
                    <label><input type="radio" value="list" name="box_view" checked="checked" /> {$lang.jl_list_view}</label>
                </span>
                <span class="custom-input">
                    <label><input type="radio" value="grid" name="box_view" /> {$lang.jl_grid_view}</label>
                </span>
            </div>
        </div>
        <div class="submit-cell">
            <div class="name">
                {$lang.jl_limit}
            </div>
            <div class="field">
                <input type="text" class="wauto" name="limit" size="3" maxlength="3" value="12" />
            </div>
        </div>
        <div class="submit-cell">
            <div class="name">
                {$lang.jl_per_page}
            </div>
            <div class="field" >
                <input type="text" class="wauto" name="per_page" size="3" maxlength="3" value="4" />
            </div>
        </div>
        <div class="submit-cell hide" id="per_row_section">
            <div class="name">
                {$lang.jl_per_row}
            </div>
            <div class="field">
                <select name="per_row">
                    {section start=1 loop=7 name='perRow'}
                    {assign var='index' value=$smarty.section.perRow.iteration}
                    <option {if $index == 4}selected="selected"{/if} value="{$index}">{$index}</option>
                    {/section}
                </select>
            </div>
        </div>
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' name=$lang.jl_box_styling id='jbox_styling'}
        <div id="jParams">
            <div class="submit-cell img-size-option">
                <div class="name">
                    {phrase key='config+name+pg_upload_thumbnail_width' db_check=true}
                </div>
                <div class="field">
                    <input type="text" class="wauto" size="4" abbr="img|jListingImg|width" name="conf_img_width" maxlength="5" value="180" /> px
                </div>
            </div>

            <div class="submit-cell img-size-option">
                <div class="name">
                    {phrase key='config+name+pg_upload_thumbnail_height' db_check=true}
                </div>
                <div class="field">
                    <input type="text" class="wauto" size="4" abbr="img|jListingImg|height" name="conf_img_height" maxlength="5" value="120" /> px
                </div>
            </div>

            <div class="submit-cell">
                <div class="name">
                    {$lang.jl_advert_radius}
                </div>
                <div class="field">
                    <input type="text" class="wauto" size="3" abbr="div|jListingItem|borderRadius" name="conf_border_radius" maxlength="3" value="3" /> px
                </div>
            </div>

            <div class="submit-cell">
                <div class="name">
                    {$lang.jl_advert_bg}
                </div>
                <div class="field">
                    <input type="hidden" name="conf_advert_bg" abbr="div|jListingItem|background" />
                    <div id="conf_advert_bg_picker" class="colorSelector"><div style="background-color: #fafafa;"></div></div>
                </div>
            </div>

            <div class="submit-cell">
                <div class="name">
                    {$lang.jl_border_color}
                </div>
                <div class="field">
                    <input type="hidden" name="conf_advert_border_color" abbr="div|jListingItem|borderColor" />
                    <div id="conf_advert_border_color_picker" class="colorSelector"><div style="background-color: #f1f1f1"></div></div>
                </div>
            </div>

            <div class="submit-cell">
                <div class="name">
                    {$lang.jl_image_bg}
                </div>
                <div class="field">
                    <input type="hidden" name="conf_image_bg" abbr="img|jListingImg|backgroundColor" />
                    <div id="conf_image_bg_picker" class="colorSelector"><div style="background-color: #efefef"></div></div>
                </div>
            </div>

            <div class="submit-cell">
                <div class="name">
                    {$lang.jl_field_first_color}
                </div>
                <div class="field" id="field_names_switch">
                    <input type="hidden" name="conf_field_first_color" abbr="a|jListingTitleLink|color" />
                    <div id="conf_field_first_color_picker" class="colorSelector"><div style="background-color: #3a5f9c"></div></div>
                </div>
            </div>

            <div class="submit-cell">
                <div class="name">
                    {$lang.jl_field_color}
                </div>
                <div class="field">
                    <input type="hidden" name="conf_field_color" abbr="span|jListingField_value|color" />
                    <div id="conf_field_color_picker" class="colorSelector"><div style="background-color: #666666"></div></div>
                </div>
            </div>

            {if $config.price_tag_field}
                <div class="submit-cell">
                    <div class="name">
                        {$lang.jl_price_field_color}
                    </div>
                    <div class="field">
                        <input type="hidden" name="conf_price_field_color" abbr="div|jListingPrice|color" />
                        <div id="conf_price_field_color_picker" class="colorSelector">
                            <div style="background-color: #3d3d3d"></div>
                        </div>
                    </div>
                </div>
            {/if}

            <div class="submit-cell">
                <div class="name">
                    {phrase key='config+name+sf_display_fields' db_check=true}
                </div>
                <div class="field inline-fields" id="field_names_switch">
                    <span class="custom-input">
                        <label><input type="radio" value="1" name="fn_switch" /> {$lang.enabled}</label>
                    </span>
                    <span class="custom-input">
                        <label><input type="radio" value="0" name="fn_switch" checked="checked" /> {$lang.disabled}</label>
                    </span>
                </div>
            </div>
        </div>
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
    </div>
    <div class="col-md-4 col-lg-5">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' name=$lang.jl_box_code id='jbox_code'}
            <textarea rows="15" id="jCodeOut">{$out|replace:"[aurl]":$custom_id}</textarea>
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
    </div>
</div>
<!-- remote advert tpl end -->

{if $account_info.ID}
    {assign var="custom_id" value="?custom_id="|cat:$box_id|cat:"&account_id="|cat:$account_info.ID}
{else}
    {assign var="custom_id" value="?custom_id="|cat:$box_id}
{/if}

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' name=$lang.jl_box_preview id='jbox_preview'}
    <div>{$out|replace:"[aurl]":$custom_id}</div>
{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}

<script>
lang['no_categories_available'] = "{$lang.no_categories_available}";
lang['select'] = "{$lang.select}";
lang['select_category'] = "{$lang.select_category}";

var bg_color, url, acurl, aurl, adurl, iout, boxID;
bg_color = 'd8cfc4';
url      = '{$smarty.const.RL_PLUGINS_URL}js_blocks/blocks.inc.php?custom_id={$box_id}{if $smarty.const.REALM != "admin" && $account_info}&account_id={$account_info.ID}{/if}';
acurl    = '?custom_id={$box_id}{if $smarty.const.REALM != "admin" && $account_info}&account_id={$account_info.ID}{/if}';
aurl     = '';
adurl    = new Array();
iout     = '{$out|replace:"</script>":"<\/script>"}';
boxID    = '{$box_id}';

{literal}
$(function(){
    /**
     * @todo - remove once categoryDropdown() is reworked (scope issue in general_nova template)
     */
    $('.horizontal-search').remove(); // Remove header search form in nova templates
    $('.page-header .top-search form').remove(); // Remove header search form in craigslist template
});
{/literal}
</script>
