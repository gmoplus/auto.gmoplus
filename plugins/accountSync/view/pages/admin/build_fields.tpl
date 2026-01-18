<div id="account-fields">
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
    <table class="sTable">
        <tr>
            {foreach from=$domains_info item='domain'}
                <td valign="top">
                    <fieldset class="light domain-info" style="margin: 0 10px 0 0;">
                        <legend id="domain2_section" data-host="{$domain.host}" data-url="{$domain.domain}" class="legend_form_section" class="up" onclick="fieldset_action('domain1_section');">{$domain.url}</legend>
                        <div data-fields-of="{$domain.domain}" class="domain1_section ui-sortable">
                            {if $domain.fields}
                                {foreach from=$domain.fields item="field"}
                                    {strip}
                                    <div id="field_{$field.key}" data-key="{$field.key}" data-missing="{if !$field.is_exist}1{else}0{/if}" class="{$field.key} field_obj{if !$field.is_exist} field_unsync{/if}">
                                        <div class="field_title" title="{$field.name}">
                                            <div class="title">{$field.name}</div>
                                            <span class="b_field_type">{$field.key}</span>
                                        </div>
                                    </div>
                                    {/strip}
                                {/foreach}
                            {else}
                                {$lang.not_available}
                            {/if}
                        </div>
                    </fieldset>
                </td>
            {/foreach}
        </tr>
    </table>
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
</div>

<script>
    var rlAccountSync = [];
    rlAccountSync['url'] = '{$smarty.const.RL_URL_HOME}';
    rlAccountSync['reload'] = true;
</script>
