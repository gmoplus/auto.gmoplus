<!-- AutoPoster message builder -->
<tr class="">
    <td class="divider_line" colspan="3">
        <div class="inner">{$lang.ap_autoposter_settings}</div>
    </td>
</tr>
<tr>
    <td class="name">{$lang.ap_message_in_posts}</td>
    <td class="field">
        {if $allLangs|@count > 1}
            <ul class="tabs">
                {foreach from=$allLangs item='language' name='langF'}
                    <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                {/foreach}
            </ul>
        {/if}

        {foreach from=$allLangs item='language' name='langF'}
            {if $allLangs|@count > 1}<div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">{/if}
            <input type="text" name="facebook_message[{$language.Code}]" value="{$sPost.facebook_message[$language.Code]|replace:'"':'&quot;'}" class="w350" maxlength="255" />
            <span class="field_description">{$lang.listing_meta_title_des}{if $allLangs|@count > 1} (<b>{$language.name}</b>){/if}</span>
            <div>
                <select>
                    <option value="0">{$lang.select}</option>
                    {foreach from=$fields item="field"}
                        <option value="{$field.Key}">{$field.name}</option>
                    {/foreach}
                </select>
                <input type="button" class="add_variable_button" value="{$lang.add}"/>
            </div>
            {if $allLangs|@count > 1}</div>{/if}
        {/foreach}
    </td>
</tr>
<!-- AutoPoster message builder end -->
