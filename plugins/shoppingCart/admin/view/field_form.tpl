<!-- shopping cart option -->

<tr id="shc_google_autocomplete_field" {if !$sPost.map}class="hide"{/if}>
    <td class="name">{$lang.shc_google_autocomplete_field}</td>
    <td class="field">
        <select name="shc_google_autocomplete">
            <option value="">{$lang.select}</option>
            {foreach from=$shc_google_autocomplete item='item'}
                <option {if $sPost.google_autocomplete == $item.key}selected="selected"{/if} value="{$item.key}">{$item.name}</option>
            {/foreach}
        </select>

        <span class="field_description">{$lang.shc_google_autocomplete_field_notice}</span>
    </td>
</tr>

<script class="fl-js-dynamic">
{literal}
    $(document).ready(function() {
        $('input[name="map"]').click(function() {
            if ($(this).is(":checked")) {
                $('#shc_google_autocomplete_field').removeClass('hide');
            } else {
                $('#shc_google_autocomplete_field').addClass('hide');
            }
        });    
    });
{/literal}
</script>

<!-- shopping cart option end -->