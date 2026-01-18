<tr class="fixed auction">
    <td class="name">
        {$lang.shc_digital_product}
    </td>
    <td class="field" id="sf_field_shc_available">
        <label><input type="radio" value="1" name="fshc[digital]" {if $smarty.post.fshc.digital == '1'}checked="checked"{/if} /> {$lang.yes}</label>
        <label><input type="radio" value="0" name="fshc[digital]" {if $smarty.post.fshc.digital == '0' || !$smarty.post.fshc.digital}checked="checked"{/if} /> {$lang.no}</label>
    </td>
</tr>
<tr class="digital-product auction fixed{if $smarty.post.fshc.digital != '1'} hide{/if}">
    <td class="name">
        {$lang.file}
    </td>
    <td class="field">
        {assign var="field_value" value=''}

        {if $smarty.post.fshc.digital_product}
            {assign var="field_value" value=$smarty.post.fshc.digital_product}
        {elseif $smarty.post.fshc.sys_exist_digital_product}
            {assign var="field_value" value=$smarty.post.fshc.sys_exist_digital_product}
        {/if}

        {if $field_value}
            <div id="digital_product_file" style="padding: 0 0 5px 0;">
                <input type="hidden" name="fshc[sys_exist_digital_product]" value="{$field_value}" />

                <a href="{$smarty.const.RL_FILES_URL}{$field_value}">{$lang.download}</a>
                |
                <a id="delete_digital_product" href="javascript:void(0)">{$lang.delete}</a>

                <script>
                {literal}
                    
                    $(document).ready(function(){
                        $('#delete_digital_product').click(function(){
                            {/literal}
                            rlConfirm('{$lang.delete_confirm}', 'shoppingCart.deleteFile', Array('{$listing_info.ID}'));
                            {literal}
                        });
                    });
                    
                {/literal}
                </script>
                <div style="font-style:italic;" class="dark_13" title="{$field_value}">
                    <b>{$field_value}</b>
                </div>
            </div>
        {/if}

        {getTmpFile field='digital_product' parent="fshc"}

        {assign var='field_type' value=$field.Default}
        <input type="hidden" name="fshc[digital_product]" value="" />
        <input class="file" type="file" name="digital_product" />
        <span class="green_11"> <em>{$l_file_types.zip} (.{$l_file_types.zip.ext|replace:',':', .'})</em></span>
    </td>
</tr>   
