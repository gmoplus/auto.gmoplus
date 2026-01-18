<!-- App manager custom settings tpl -->
<table class="hide">
    <tr id="app_account_type_settings">
        <td colspan="2">
        <table class="form">
        {foreach from=$appAccountTypes item='aType' name='aTypeName'}
            <tr {if $smarty.foreach.aTypeName.iteration%2 != 0} class="highlight"{/if}>
                <td class="name" style="width: 210px;">
                    {$aType.name}
                </td>
                <td class="field">
                    <div class="inner_margin" style="padding-top: 6px;">
                        <label>
                            <input type="radio"
                                   name="app_account_type[{$aType.Key}]"
                                   value="1"
                                   {if $aType.Key|in_array:$appSelectedAccountTypes}
                                   checked="checked"
                                   {/if} />
                            {$lang.yes}
                        </label>

                        <label>
                            <input type="radio"
                                   name="app_account_type[{$aType.Key}]"
                                   value="0"
                                   {if !$aType.Key|in_array:$appSelectedAccountTypes}
                                   checked="checked"
                                   {/if} />
                            {$lang.no}
                        </label>
                    </div>
                </td>
            </tr>
        {/foreach}
        </table>
    </td>
</tr>
</table>


<table class="hide">
    <tr id="app_banners">
        <td colspan="2">
         <table class="form">
            <tr  class="highlight">
                <td class="name" style="width: 210px;">
                    {phrase key='config+name+app_banners_android'}
                </td>
                <td class="field">
                    <div class="inner_margin" style="padding-top: 6px;">
                        <label>
                            <input type="radio"
                                   name="post_config[app_banners_android][value]"
                                   value="1"
                                   {if $config.app_banners_android}
                                   checked="checked"
                                   {/if} />
                            {$lang.yes}
                        </label>

                        <label>
                            <input type="radio"
                                   name="post_config[app_banners_android][value]"
                                   value="0"
                                   {if !$config.app_banners_android}
                                   checked="checked"
                                   {/if} />
                            {$lang.no}
                        </label>
                        
                        <div {if !$config.app_banners_android}class='hide'{/if}>
                            <input type="text" value="{$config.app_banners_android_key}" name="post_config[app_banners_android_key][value]">
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="name" style="width: 210px;">
                    {phrase key='config+name+app_banners_ios'}
                </td>
                <td class="field">
                    <div class="inner_margin" style="padding-top: 6px;">
                        <label>
                            <input type="radio"
                                   name="post_config[app_banners_ios][value]"
                                   value="1"
                                   {if $config.app_banners_ios}
                                   checked="checked"
                                   {/if} />
                            {$lang.yes}
                        </label>

                        <label>
                            <input type="radio"
                                   name="post_config[app_banners_ios][value]"
                                   value="0"
                                   {if !$config.app_banners_ios}
                                   checked="checked"
                                   {/if} />
                            {$lang.no}
                        </label>
                        
                        <div {if !$config.app_banners_ios}class='hide'{/if}>
                            <input type="text" value="{$config.app_banners_ios_key}" name="post_config[app_banners_ios_key][value]">
                        </div>
                    </div>
                </td>
            </tr>
            <tr class="highlight">
                <td class="name" style="width: 210px;">
                    {phrase key='config+name+app_banners_in_grid'}
                </td>
                <td class="field">
                    <div class="inner_margin" style="padding-top: 6px;">
                        <label>
                            <input type="radio"
                                   name="post_config[app_banners_in_grid][value]"
                                   value="1"
                                   {if $config.app_banners_in_grid}
                                   checked="checked"
                                   {/if} />
                            {$lang.yes}
                        </label>

                        <label>
                            <input type="radio"
                                   name="post_config[app_banners_in_grid][value]"
                                   value="0"
                                   {if !$config.app_banners_in_grid}
                                   checked="checked"
                                   {/if} />
                            {$lang.no}
                        </label>
                        <div {if !$config.app_banners_in_grid}class='hide'{/if}>
                            <select name="post_config[app_banners_in_grid_interation][value]" >
                                {section name="column_numbers" start=3 loop=11 step=1}
                                    {assign var="column_number" value=$smarty.section.column_numbers.index}
                                    <option value="{$column_number}" {if $config.app_banners_in_grid_interation == $column_number}selected="selected"{/if}>{$column_number}</option>
                                {/section}
                            </select>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="name" style="width: 210px;">
                    {phrase key='config+name+app_banners_pages'}
                </td>
                <td class="field">
                   <fieldset class="light">
                        {assign var='pages_phrase' value='admin_controllers+name+pages'}
                        <legend id="legend_pages" class="up">{$lang.$pages_phrase}</legend>
                        <div id="pages">
                            <div id="pages_cont" >
                                {assign var='appPostPages' value=','|explode:$config.app_banners_pages}
                                <table class="sTable" style="margin-bottom: 15px;">
                                <tr>
                                    <td valign="top">
                                    {foreach from=$appPages item='page' key='key' name='pagesF'}
                                    <div style="padding: 2px 8px;">
                                        <label class="cLabel" for="page_{$key}" ><input class="checkbox " {if $key|in_array:$appPostPages}checked="checked"{/if} id="page_{$key}" type="checkbox" name="app_banners_pages[]" value="{$key}" /> {$page}</label>
                                    </div>
                                    {assign var='perCol' value=$smarty.foreach.pagesF.total/3|ceil}

                                    {if $smarty.foreach.pagesF.iteration % $perCol == 0}
                                        </td>
                                        <td valign="top">
                                    {/if}
                                    {/foreach}
                                    </td>
                                </tr>
                                </table>
                            </div>

                            <div class="grey_area" style="margin: 0 0 5px;">
                                <span id="pages_nav" {if $sPost.show_on_all}class="hide"{/if}>
                                    <span onclick="$('#pages_cont input').prop('checked', true);" class="green_10">{$lang.check_all}</span>
                                    <span class="divider"> | </span>
                                    <span onclick="$('#pages_cont input').prop('checked', false);" class="green_10">{$lang.uncheck_all}</span>
                                </span>
                            </div>
                        </div>
                    </fieldset>

                    <script type="text/javascript">
                    {literal}

                    $(document).ready(function(){
                        $('#legend_pages').click(function(){
                            fieldset_action('pages');
                        });

                        $('input#show_on_all').click(function(){
                            $('#pages_cont').slideToggle();
                            $('#pages_nav').fadeToggle();
                        });
                    });

                    {/literal}
                    </script>
                </td>
            </tr>
         </table>
        </td>
    </tr>
</table>

<script>
{literal}

$(function(){
    var $containerItems = $('#app_account_type_settings');
    var $container = $('input[name="post_config[app_account_types][value]"]').closest('tr');
    $container.after($containerItems);
    $container.hide();

    var $containerBItems = $('#app_banners');
    var $containerB = $('input[name="post_config[app_banners_provider][value]"]').closest('tr');
    $containerB.after($containerBItems);
    
    $containerBItems.find('input[type=radio]').change(function(){
        var is_checked = parseInt($(this).filter(':checked').val());
        var $containerTmp = $(this).closest('div');
        $containerTmp.find('div')[is_checked ? 'removeClass' : 'addClass']('hide');
    })
});
{/literal}
</script>

<!-- App manager custom settings tpl end -->
