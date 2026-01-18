<!-- OpenAI API option -->

<tr>
    <td class="name">{$lang.openai_field_option_name}</td>
    <td class="field">
        {if $sPost.openai == '1'}
            {assign var='openai_yes' value='checked="checked"'}
        {elseif $sPost.openai == '0'}
            {assign var='openai_no' value='checked="checked"'}
        {else}
            {assign var='openai_no' value='checked="checked"'}
        {/if}
        <label><input {$openai_yes} type="radio" name="openai" value="1" /> {$lang.yes}</label>
        <label><input {$openai_no} type="radio" name="openai" value="0" /> {$lang.no}</label>
    </td>
</tr>

<!-- OpenAI API option end -->
