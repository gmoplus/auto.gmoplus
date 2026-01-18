<!-- Billing details tpl -->

{if $isLogin 
    && $account_info.Type == 'affiliate' 
    && ($config.aff_paypal || $config.aff_western_union || $config.aff_bank_wire)}
    {assign var="paypal_name" value='payment_gateways+name+paypal'}
    {assign var="affPost" value=$smarty.post.aff_billing_details}

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='aff_billing_area' name=$lang.aff_billing_area}

    <div class="submit-cell billing_type">
        <div class="name">{$lang.aff_billing_type}</div>
        <div class="field inline-fields">
            {if $config.aff_paypal}
                <span class="custom-input">
                    <label>
                        <input type="radio" 
                            value="paypal" 
                            name="aff_billing_details[type]" 
                            {if $affPost.type == 'paypal'}checked="checked"{/if}/>
                        {$lang.$paypal_name}
                    </label>
                </span>
            {/if}

            {if $config.aff_western_union}
                <span class="custom-input">
                    <label>
                        <input type="radio" 
                            value="western_union" 
                            name="aff_billing_details[type]" 
                            {if $affPost.type == 'western_union'}checked="checked"{/if}/>
                        {$lang.aff_western_union}
                    </label>
                </span>
            {/if}

            {if $config.aff_bank_wire}
                <span class="custom-input">
                    <label>
                        <input type="radio" 
                            value="bank_wire" 
                            name="aff_billing_details[type]" 
                            {if $affPost.type == 'bank_wire'}checked="checked"{/if}/>
                        {$lang.aff_bank_wire}
                    </label>
                </span>
            {/if}
        </div>
    </div>

    {if $config.aff_paypal}
        <div class="submit-cell paypal {if $affPost.type != 'paypal'}hide{/if}">
            <div class="name">{$lang.aff_paypal_email}</div>
            <div class="field single-field">
                <input type="text" 
                    value="{$affPost.paypal_email}" 
                    placeholder="{$lang.mail}" 
                    name="aff_billing_details[paypal_email]"/>
            </div>
        </div>
    {/if}

    {if $config.aff_western_union}
        <div class="submit-cell western_union {if $affPost.type != 'western_union'}hide{/if}">
            <div class="name">{$lang.aff_wu_country}</div>
            <div class="field single-field">
                <input type="text" 
                    value="{$affPost.wu_country}" 
                    placeholder="{$lang.aff_wu_country}" 
                    name="aff_billing_details[wu_country]"/>
            </div>
        </div>

        <div class="submit-cell western_union {if $affPost.type != 'western_union'}hide{/if}">
            <div class="name">{$lang.aff_wu_city}</div>
            <div class="field single-field">
                <input type="text" 
                    value="{$affPost.wu_city}" 
                    placeholder="{$lang.aff_wu_city}" 
                    name="aff_billing_details[wu_city]"/>
            </div>
        </div>

        <div class="submit-cell western_union {if $affPost.type != 'western_union'}hide{/if}">
            <div class="name">{$lang.aff_wu_fullname}</div>
            <div class="field single-field">
                <input type="text" 
                    value="{$affPost.wu_fullname}" 
                    placeholder="{$lang.aff_wu_fullname}" 
                    name="aff_billing_details[wu_fullname]"/>
            </div>
        </div>
    {/if}

    {if $config.aff_bank_wire}
        <div class="submit-cell bank_wire {if $affPost.type != 'bank_wire'}hide{/if}">
            <div class="name">{$lang.aff_bank_wire_details}</div>
            <div class="field single-field">
                <textarea rows="5" 
                    cols="" 
                    name="aff_billing_details[bank_wire_details]">{$affPost.bank_wire_details}</textarea>
            </div>
        </div>
    {/if}

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}

    <script class="fl-js-dynamic">{literal}
    $('#profile_submit').closest('.submit-cell').before($('#fs_aff_billing_area'));

    $(function(){
        $('[name="aff_billing_details[type]"]').change(function(){
            var type = $(this).attr('value');

            if (type) {
                $('#fs_aff_billing_area .submit-cell.' + type).fadeIn();
                $('#fs_aff_billing_area .submit-cell:not(.' + type + '):not(.billing_type)').hide();
            }
        });
    });
    {/literal}</script>
{/if}

<!-- Billing details tpl end -->
