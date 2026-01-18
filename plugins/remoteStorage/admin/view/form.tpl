<!-- RemoteStorage add/edit server form tpl -->

{assign var='sPost' value=$smarty.post}
{include file='blocks/m_block_start.tpl'}

<form action="{$rlBaseC}action={if $smarty.get.action == 'edit'}edit&id={$smarty.get.id}{else}add{/if}"
      method="post"
      onsubmit="return submitHandler()"
>
    <input type="hidden" name="submit" value="1" />

    {if $smarty.get.action == 'edit'}
        <input type="hidden" name="fromPost" value="1" />
        <input type="hidden" name="id" value="{$smarty.get.id}" />
    {/if}

    <table class="form">
        <tr>
            <td class="name">
                <span class="red">*</span>{$lang.title}
            </td>
            <td>
                <input type="text" name="title" value="{$sPost.title}" maxlength="350" />
            </td>
        </tr>

        <tr>
            <td class="name">{$lang.rs_service_provider}</td>
            <td class="field">
                <select id="rs_type" name="type" {if $smarty.get.action == 'edit'}class="disabled" disabled{/if}>
                    {foreach from=$rsTypes item='type'}
                        <option value="{$type}" {if $sPost.type == $type}selected="selected"{/if}>
                            {assign var='type_phrase_key' value="rs_server_type_`$type`"}
                            {$lang.$type_phrase_key}
                        </option>
                    {/foreach}
                </select>

                {if $smarty.get.action !== 'edit'}
                    {foreach from=$rsTypes item='type' name='rsTypesNotices'}
                        {assign var='rsServerGuide' value=null}
                        {if $rsS3Guides.$type[$smarty.const.RL_LANG_CODE]}
                            {assign var='rsServerGuide' value=$rsS3Guides.$type[$smarty.const.RL_LANG_CODE]}
                        {elseif $rsS3Guides.$type.en}
                            {assign var='rsServerGuide' value=$rsS3Guides.$type.en}
                        {/if}

                        {if $rsServerGuide}
                            <span id="server_{$type}_notice"
                                  class="server_notice field_description{if ($sPost.type && $sPost.type != $type) || (!$sPost.type && !$smarty.foreach.rsTypesNotices.first)} hide{/if}"
                            >
                                {assign var='replace_var' value=`$smarty.ldelim`link`$smarty.rdelim`}
                                {$lang.rs_server_guide|replace:$replace_var:$rsServerGuide}
                            </span>
                        {/if}
                    {/foreach}
                {/if}

                {if $smarty.get.action == 'edit'}
                    <input type="hidden" name="type" value="{$sPost.type}" />
                {/if}
            </td>
        </tr>

        <tr>
            <td class="name">{$lang.rs_bucket}</td>
            <td class="field">
                {if $sPost.bucket}
                    <b>{$sPost.bucket}</b>
                {else}
                    {$lang.not_available}
                    <span class="field_description">{$lang.rs_new_bucket_notice}</span>
                {/if}
            </td>
        </tr>

        <tr>
            <td class="name">{$lang.rs_server_configuration}</td>
            <td class="field">
                {foreach from=$rsTypes item='type' name='fRsTypes'}
                    <fieldset id="{$type}"
                              class="light{if ($sPost.type && $sPost.type !== $type)
                                || (!$sPost.type && $smarty.foreach.fRsTypes.iteration != 1)} hide{/if}"
                    >
                        <legend id="legend_{$type}_tab_area" class="up" onclick="fieldset_action('{$type}_tab_area');">
                            {assign var='type_phrase_key' value="rs_server_type_`$type`"}
                            {$lang.$type_phrase_key}
                        </legend>
                        <div id="{$type}_tab_area" style="padding: 0 10px 10px 10px;">
                            <table class="form">
                                {foreach from=$rsTypesCredentials[$type] item='credential'}
                                    {* Hide unnecessary credentials *}
                                    {if in_array($credential, $rsS3HiddenCredentials[$type])}
                                        {continue}
                                    {/if}

                                    <tr>
                                        <td class="name">
                                            {assign var='credential_phrase_key' value="rs_`$type`_`$credential`"}

                                            {**
                                             * You can use custom phrase for credential title: rs_amazon_s3_REGION,
                                             * where "amazon_s3" it's a type of server.
                                             * Or system will use base phrase: rs_base_s3_REGION,
                                             * if your server have "_s3" in type
                                             *}
                                            {if !isset($lang.$credential_phrase_key) && $type|strpos:'_s3' > 0}
                                                {assign var='credential_phrase_key' value="rs_base_s3_`$credential`"}
                                            {/if}

                                            {$lang.$credential_phrase_key}
                                        </td>
                                        <td class="field">
                                            {assign var='credential_description_phrase_key' value="rs_`$type`_`$credential`_notice"}

                                            {if $credential === 'REGION' && $rsS3Regions[$type]}
                                                <select name="{$type}[{$credential}]"
                                                        {if $smarty.get.action == 'edit'}class="disabled" disabled{/if}
                                                        class="rs_region"
                                                >
                                                    {foreach from=$rsS3Regions[$type] item='region' key='regionKey'}
                                                        <option value="{$regionKey}"
                                                                {if $sPost.$type.$credential == $regionKey}selected="selected"{/if}
                                                        >
                                                            {$region}
                                                        </option>
                                                    {/foreach}
                                                </select>

                                                {if $lang.$credential_description_phrase_key}
                                                    <span class="field_description_noicon">
                                                        {$lang.$credential_description_phrase_key}
                                                    </span>
                                                {/if}
                                            {else}
                                                <input type="text"
                                                       name="{$type}[{$credential}]"
                                                       value="{$sPost.$type.$credential}"
                                                       {if $smarty.get.action == 'edit'}class="disabled" disabled{/if}
                                                />

                                                {if $lang.$credential_description_phrase_key}
                                                    <span class="field_description_noicon">
                                                        {$lang.$credential_description_phrase_key}
                                                    </span>
                                                {/if}
                                            {/if}

                                            {if $smarty.get.action == 'edit'}
                                                <input type="hidden" name="{$type}[{$credential}]" value="{$sPost.$type.$credential}" />
                                            {/if}
                                        </td>
                                    </tr>
                                {/foreach}
                            </table>
                        </div>
                    </fieldset>
                {/foreach}

                <script>{literal}
                $(function () {
                    let $type = $('select[name=type]');
                    $type.change(function () {
                        let value = $type.find(':checked').val();

                        $('fieldset,span.server_notice').hide();
                        $('fieldset#' + value + ',#server_' + value + '_notice').show();
                    });
                });
                {/literal}
                </script>
            </td>
        </tr>

        <tr>
            <td class="name">{$lang.status}</td>
            <td class="field">
                <select name="status" class="disabled" disabled>
                    {foreach from=$rsStatuses item='status'}
                        <option value="{$status}" {if $sPost.status == $status}selected="selected"{/if}>
                            {$lang.$status}
                        </option>
                    {/foreach}
                </select>

                {if $smarty.get.action == 'edit'}
                    <input type="hidden" name="status" value="{$sPost.status}" />
                {/if}
            </td>
        </tr>

        <tr>
            <td></td>
            <td class="field">
                <input type="submit" value="{if $smarty.get.action == 'edit'}{$lang.edit}{else}{$lang.add}{/if}" />
            </td>
        </tr>
    </table>
</form>

{include file='blocks/m_block_end.tpl'}

<style>{literal}
#rs_type, .rs_region {
    width: 211px;
}
{/literal}</style>

<!-- RemoteStorage add/edit server form tpl end -->
