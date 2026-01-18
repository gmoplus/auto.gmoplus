<!-- remote adverts tpl -->

<script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}jquery/colorpicker/js/colorpicker.js"></script>
<link href="{$smarty.const.RL_LIBS_URL}jquery/colorpicker/css/colorpicker.css" type="text/css" rel="stylesheet" />
<script type="text/javascript" src="{$smarty.const.RL_PLUGINS_URL}js_blocks/static/lib.js"></script>
<script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}jquery/jquery.categoryDropdown.js"></script>

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}

<div style="max-width: 1000px;">

{assign var="custom_id" value="?custom_id="|cat:$box_id}

<div style="display: flex;">
    <div style="flex: 0 0 50%;max-width: 50%;margin-right: 20px;">
        <fieldset class="light" style="margin-bottom: 0;">
            <legend id="legend_box_styling" onclick="fieldset_action('box_styling');">{$lang.jl_box_settings}</legend>

            <div id="box_styling">
                <table class="form">
                <tr>
                    <td class="name">
                        {$lang.set_owner}
                    </td>
                    <td class="field">
                        <input type="text" maxlength="255" id="Account" />
                    </td>
                </tr>

                {if $listing_types|@count > 1}
                    <tr>
                        <td class="name">
                            {$lang.listing_type}
                        </td>
                        <td class="field">
                            <select name="listing_type" >
                                <option value="0">{$lang.all}</option>
                                {foreach from=$listing_types key="key" item="listing_type" name="ltLoop"}
                                    <option value="{$listing_type.Key}">{$listing_type.name}</option>
                                {/foreach}
                            </select>
                        </td>
                    </tr>
                {/if}

                <tr id="categories_cont" class="hide">
                    <td class="name">
                        {$lang.category}
                    </td>
                    <td class="field">
                        <select name="category_id"></select>
                    </td>
                </tr>
                <tr>
                    <td class="name">
                        {$lang.jl_box_view}
                    </td>
                    <td class="field">
                        <div style="padding-top: 5px;">
                            <label>
                                <input type="radio" value="list" name="box_view" checked="checked" />
                                {$lang.jl_list_view}
                            </label>
                            <label>
                                <input type="radio" value="grid" name="box_view" />
                                {$lang.jl_grid_view}
                            </label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="name">
                        {$lang.jl_limit}
                    </td>
                    <td class="field">
                        <input type="text" class="w60" name="limit" maxlength="5" value="12">
                    </td>
                </tr>
                <tr>
                    <td class="name">
                        {$lang.jl_per_page}
                    </td>
                    <td class="field">
                        <input type="text" class="w60" name="per_page" maxlength="5" value="4">
                    </td>
                </tr>
                <tr id="per_row_section" class="hide">
                    <td class="name">
                        {$lang.jl_per_row}
                    </td>
                    <td class="field">
                        <select name="per_row" class="w60">
                            {section start=1 loop=7 name='perRow'}
                            {assign var='index' value=$smarty.section.perRow.iteration}
                            <option {if $index == 4}selected="selected"{/if} value="{$index}">{$index}</option>
                            {/section}
                        </select>
                    </td>
                </tr>
                </table>

                <table class="form" id="jParams">
                <tr>
                    <td class="divider" colspan="2">
                        <div class="inner">{$lang.jl_box_styling}</div>
                    </td>
                </tr>
                <tr class="img-size-option">
                    <td class="name">
                        {phrase key='config+name+pg_upload_thumbnail_width' db_check=true}
                    </td>
                    <td class="field">
                        <input type="text" class="w60" abbr="img|jListingImg|width" name="conf_img_width" maxlength="5" value="180" /> px
                    </td>
                </tr>
                <tr class="img-size-option">
                    <td class="name">
                        {phrase key='config+name+pg_upload_thumbnail_height' db_check=true}
                    </td>
                    <td class="field">
                        <input type="text" class="w60" abbr="img|jListingImg|height" name="conf_img_height" maxlength="5" value="120" /> px
                    </td>
                </tr>
                <tr>
                    <td class="name">
                        {$lang.jl_advert_radius}
                    </td>
                    <td class="field">
                        <input type="text" class="w60" abbr="div|jListingItem|borderRadius" name="conf_border_radius" maxlength="3" value="3" /> px
                    </td>
                </tr>
                <tr>
                    <td class="name">
                        {$lang.jl_advert_bg}
                    </td>
                    <td class="field">
                        <input type="hidden" name="conf_advert_bg" abbr="div|jListingItem|background" />
                        <div id="conf_advert_bg_picker" class="colorSelector"><div style="background-color: #fafafa"></div></div>
                    </td>
                </tr>
                <tr>
                    <td class="name">
                        {$lang.jl_border_color}
                    </td>
                    <td class="field">
                        <input type="hidden" name="conf_advert_border_color" abbr="div|jListingItem|borderColor" />
                        <div id="conf_advert_border_color_picker" class="colorSelector"><div style="background-color: #f1f1f1"></div></div>
                    </td>
                </tr>
                <tr>
                    <td class="name">
                        {$lang.jl_image_bg}
                    </td>
                    <td class="field">
                        <input type="hidden" name="conf_image_bg" abbr="img|jListingImg|backgroundColor" />
                        <div id="conf_image_bg_picker" class="colorSelector"><div style="background-color: #efefef"></div></div>
                    </td>
                </tr>
                <tr>
                    <td class="name">
                        {$lang.jl_field_first_color}
                    </td>
                    <td class="field">
                        <input type="hidden" name="conf_field_first_color" abbr="a|jListingTitleLink|color" />
                        <div id="conf_field_first_color_picker" class="colorSelector"><div style="background-color: #3a5f9c"></div></div>
                    </td>
                </tr>
                <tr>
                    <td class="name">
                        {$lang.jl_field_color}
                    </td>
                    <td class="field">
                        <input type="hidden" name="conf_field_color" abbr="span|jListingField_value|color" />
                        <div id="conf_field_color_picker" class="colorSelector"><div style="background-color: #666666"></div></div>
                    </td>
                </tr>

                {if $config.price_tag_field}
                    <tr>
                        <td class="name">
                            {$lang.jl_price_field_color}
                        </td>
                        <td class="field">
                            <input type="hidden"
                                   name="conf_price_field_color"
                                   abbr="div|jListingPrice|color"
                            />
                            <div id="conf_price_field_color_picker" class="colorSelector">
                                <div style="background-color: #3d3d3d"></div>
                            </div>
                        </td>
                    </tr>
                {/if}

                <tr>
                    <td class="name">
                        {phrase key='config+name+sf_display_fields' db_check=true}
                    </td>
                    <td class="field" id="field_names_switch">
                        <div style="padding-top: 5px;">
                            <label>
                                <input type="radio" value="1" name="fn_switch" />
                                {$lang.enabled}
                            </label>
                            <label>
                                <input type="radio" value="0" name="fn_switch" checked="checked" />
                                {$lang.disabled}
                            </label>
                        </div>
                    </td>
                </tr>
                </table>
            </div>
        </fieldset>
    </div>
    <div style="flex: 1;">
        <fieldset style="height: 100%;box-sizing: border-box;margin-bottom: 0;">
        <legend id="legend_box_code" onclick="fieldset_action('box_code');">{$lang.jl_box_code}</legend>
            <div id="box_code">
                <textarea cols="5" rows="5" style="width:100%;height: 300px;box-sizing: border-box;" id="jCodeOut">{$out|replace:"[aurl]":$custom_id}</textarea>
            </div>
        </fieldset>
    </div>
</div>

<fieldset style="margin-top: 30px;">
    <legend id="legend_box_preview" onclick="fieldset_action('box_preview');">{$lang.jl_box_preview}</legend>
    <div id="box_preview">{$out|replace:"[aurl]":$custom_id}</div>
</fieldset>

</div>

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}

<script>
lang['no_categories_available'] = "{$lang.no_categories_available}";
lang['select'] = "{$lang.select}";
lang['select_category'] = "{$lang.select_category}";

var url, acurl, aurl, adurl, iout, boxID;

url      = '{$smarty.const.RL_PLUGINS_URL}js_blocks/blocks.inc.php?custom_id={$box_id}';
acurl    = '?custom_id={$box_id}';
aurl     = '';
adurl    = new Array();
iout     = '{$out|replace:"</script>":"<\/script>"}';
boxID    = '{$box_id}';

{literal}
$(function(){
    $('#Account').rlAutoComplete({
        add_id: true
    });
});
{/literal}
</script>

<!-- remote adverts tpl end -->
