<div class="submit-cell auction fixed">
    <div class="name">
        {$lang.shc_digital_product}
    </div>
    <div class="field inline-fields" id="sf_field_shc_bid_available">
        <span class="custom-input"><label><input type="radio" value="1" name="fshc[digital]" {if $smarty.post.fshc.digital == '1'}checked="checked"{/if} />{$lang.yes}</label></span>
        <span class="custom-input"><label><input type="radio" value="0" name="fshc[digital]" {if $smarty.post.fshc.digital == '0' || $smarty.post.fshc.digital == ''}checked="checked"{/if} />{$lang.no}</label></span>
    </div>
</div>
<div class="submit-cell clearfix digital-product auction fixed{if $smarty.post.fshc.digital != '1'} hide{/if}">
    <div class="name">
        {$lang.file}
    </div>
    <div class="field" id="sf_field_digital_product">
        {assign var="field_value" value=''}

        {if $smarty.post.fshc.digital_product}
            {assign var="field_value" value=$smarty.post.fshc.digital_product}
        {elseif $smarty.post.fshc.sys_exist_digital_product}
            {assign var="field_value" value=$smarty.post.fshc.sys_exist_digital_product}
        {/if}

        {if $field_value && !'digital_product'|files}
            <div id="digital_product_file" 
                class="image-field-preview file-data"
                data-field="digital_product"
                data-value="{$field_value}"
                data-type="listing">
                <div class="relative fleft">
                    <input type="hidden" name="fshc[sys_exist_digital_product]" value="{$field_value}" />

                    <div class="fleft" style="margin-bottom: 5px;">
                        <div>
                            <table class="sTable">
                                <tr>
                                    <td>{$lang.currently_uploaded_file}</td>
                                    <td class="ralign">
                                        <a href="{$smarty.const.RL_FILES_URL}{$field_value}">{$lang.download}</a>
                                        |
                                        <a href="javascript://" id="delete_digital_product" data-item="{$manageListing->listingID}" class="delete-file-product">
                                            {$lang.remove}
                                            <img id="delete_digital_product" class="delete icon" 
                                            src="{$rlTplBase}img/blank.gif" alt="" title="{$lang.delete}" />
                                        </a>
                                    </td></tr>
                            </table>
                        </div>
                        <span style="font-style:italic;" class="dark_13" title="{$field_value}">
                            <b>{$field_value}</b>
                        </span>
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        {else}
            {getTmpFile field='digital_product' parent="fshc"}
        {/if}

        <div class="file-input">
            <input type="hidden" name="fshc[digital_product]" value="" />
            <input class="file" type="file" name="digital_product" />
            <input type="text" class="file-name" name="" />
            <span>{$lang.choose}</span>
        </div>
        <em>{$l_file_types.zip} (.{$l_file_types.zip.ext|replace:',':', .'})</em>
    </div>
</div>
