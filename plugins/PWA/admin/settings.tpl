<!-- PWA/Settings tpl -->

<script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}jquery/colorpicker/js/colorpicker.js"></script>
{assign var='sPost' value=$smarty.post}
{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}

<form method="post"
      enctype="multipart/form-data"
      onsubmit="$(this).find('[type=submit]').val('{$lang.loading}').attr('disabled', true);"
>
    <input type="hidden" name="submit" value="1" />

    <table class="form">
        <tbody>
        <tr>
            <td class="name"><span class="red">*</span>{$lang.pwa_name}</td>
            <td class="field">
                {if $allLangs|@count > 1}
                    <ul class="tabs">
                        {foreach from=$allLangs item='language' name='langF'}
                            <li lang="{$language.Code}"
                                {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}
                            </li>
                        {/foreach}
                    </ul>
                {/if}

                {foreach from=$allLangs item='language' name='langF'}
                    {if $allLangs|@count > 1}
                        <div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">
                    {/if}
                    <input type="text" name="name[{$language.Code}]" value="{$sPost.name[$language.Code]}" maxlength="350"/>
                    {if $allLangs|@count > 1}
                        <span class="field_description_noicon">{$lang.pwa_name} (<b>{$language.name}</b>)</span></div>
                    {/if}
                {/foreach}
            </td>
        </tr>
        <tr>
            <td class="name"><span class="red">*</span>{$lang.pwa_short_name}</td>
            <td class="field">
                {if $allLangs|@count > 1}
                    <ul class="tabs">
                        {foreach from=$allLangs item='language' name='langF'}
                            <li lang="{$language.Code}"
                                {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                        {/foreach}
                    </ul>
                {/if}

                {foreach from=$allLangs item='language' name='langF'}
                    {if $allLangs|@count > 1}
                        <div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">
                    {/if}
                    <input type="text" name="short_name[{$language.Code}]" value="{$sPost.short_name[$language.Code]}" maxlength="350"/>
                    {if $allLangs|@count > 1}
                        <span class="field_description_noicon">{$lang.pwa_short_name} (<b>{$language.name}</b>)</span></div>
                    {/if}
                {/foreach}
            </td>
        </tr>
        <tr>
            <td class="name"><span class="red">*</span>{$lang.pwa_description}</td>
            <td class="field">
                {if $allLangs|@count > 1}
                    <ul class="tabs">
                        {foreach from=$allLangs item='language' name='langF'}
                            <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>
                                {$language.name}
                            </li>
                        {/foreach}
                    </ul>
                {/if}

                {foreach from=$allLangs item='language' name='langF'}
                    {if $allLangs|@count > 1}<div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">{/if}
                    <textarea cols="" rows="" name="description[{$language.Code}]">{$sPost.description[$language.Code]}</textarea>
                    {if $allLangs|@count > 1}</div>{/if}
                {/foreach}
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.pwa_bg_color}</td>
            <td class="field">
                <div style="padding: 0 0 5px 0;">
                    <input type="hidden" name="color" value="{$sPost.color}"/>
                    <div id="colorSelector" class="colorSelector">
                        <div style="background-color: #{if $sPost.color}{$sPost.color}{else}d8cfc4{/if}"></div>
                    </div>
                </div>

                <script>
                var bgColor = '{if $sPost.color}{$sPost.color}{else}d8cfc4{/if}';
                {literal}

                $(function() {
                    $('#colorSelector').ColorPicker({
                        color: '#' + bgColor,
                        onShow: function(colpkr) {
                            $('input[name=color]').val(bgColor);
                            $(colpkr).fadeIn(500);
                            return false;
                        },
                        onHide: function(colpkr) {
                            $(colpkr).fadeOut(500);
                            return false;
                        },
                        onChange: function(hsb, hex) {
                            $('#colorSelector div').css('backgroundColor', '#' + hex);
                            $('input[name=color]').val(hex);
                        }
                    });

                });
                {/literal}</script>
            </td>
        </tr>
        <tr>
            <td class="name"><span class="red">*</span>{$lang.pwa_icon}</td>
            <td class="field">
                <input type="file" name="pwa-icon">
                <span class="field_description_noicon">{$lang.pwa_icon_desc}</span>

                {if $icon_exist}
                    <div class="pwa-app-img-container">
                        <img alt="" class="pwa-icon" src="{$icon_exist}" />
                    </div>
                {/if}
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.pwa_maskable_icons}</td>
            <td class="field">
                {if $sPost.maskable_icons == '1'}
                    {assign var='maskable_icons_yes' value='checked="checked"'}
                {elseif $sPost.maskable_icons == '0'}
                    {assign var='maskable_icons_no' value='checked="checked"'}
                {else}
                    {assign var='maskable_icons_no' value='checked="checked"'}
                {/if}

                <label>
                    <input {$maskable_icons_yes} type="radio" name="maskable_icons" value="1" /> {$lang.yes}
                </label>
                <label>
                    <input {$maskable_icons_no} type="radio" name="maskable_icons" value="0" /> {$lang.no}
                </label>

                <span class="field_description_noicon">
                    {assign var='replace' value='<a target="_blank" href="https://web.dev/maskable-icon/">$1</a>'}
                    {$lang.pwa_maskable_icons_desc|regex_replace:'/\[(.*)\]/':$replace}
                </span>
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.pwa_launch_screen}</td>
            <td class="field">
                <input type="file" name="portrait-launch-screen">
                <span class="field_description_noicon">{$lang.pwa_launch_desc}</span>

                {if $screen_exist}
                    <div class="pwa-app-img-container">
                        <img alt="" class="pwa-launch-screen" src="{$screen_exist}" />
                    </div>
                {/if}
            </td>
        </tr>
        <tr>
            <td class="no_divider"></td>
            <td class="field">
                <input type="submit" name="action" value="{$lang.save}">
            </td>
        </tr>
        </tbody>
    </table>
</form>

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}

{if $icon_exist || $screen_exist}
    <style>{literal}
        .pwa-app-img-container {
            margin: 10px 0 10px;
        }
        .pwa-app-img-container img {
            cursor: pointer;
            max-width: 60px;
            border: 2px #d7dcc9 solid;
        }
    {/literal}</style>

    <script>{literal}
    $(function () {
        let $launchScreen = $('.pwa-launch-screen'), $icon = $('.pwa-icon');

        $launchScreen.flModal({
            caption: '{/literal}{$lang.pwa_launch_screen}{literal}',
            content: '<div style="height: 800px"><img alt="" style="width: 100%; height: 100%; object-fit: contain;" src="'
                + $launchScreen.attr('src')
                + '" /></div>',
        });
        $icon.flModal({
            caption: '{/literal}{$lang.pwa_icon}{literal}',
            content: '<div style="height: 500px"><img alt="" style="width: 100%; height: 100%; object-fit: contain;" src="'
                + $icon.attr('src')
                + '" /></div>'
        });
    });
    {/literal}</script>
{/if}

<!-- PWA/Settings tpl end -->
