<!-- escort gender field -->
{if $smarty.const.USE_GENDER_FIELD}
    {if $single_ltypes && $single_ltypes|@count > 1}
        <div class="submit-cell">
            <select class="ha-width-100" name="profile[escort_gender]" data-validate="required">
                {strip}
                    <option value="">{$lang.ha_select_gender}</option>
                    {foreach from=$single_ltypes item='single_type'}
                        <option value="{$single_type.Key}">{$single_type.name}</option>
                    {/foreach}
                {/strip}
            </select>
        </div>
    {else}
        {assign var="single_type" value=$single_ltypes|@current}
        <input type="hidden" value="{$single_type.ID}" name="profile[escort_gender]" />
    {/if}
{/if}
<!-- escort gender field end -->
