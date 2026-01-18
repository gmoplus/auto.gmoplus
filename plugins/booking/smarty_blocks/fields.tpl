<!-- booking fields -->

{if $step == 'fields' && $edit_action && $smarty.session.bookingData.fields}
    {assign var="booking_fields" value=$smarty.session.bookingData.fields}
{/if}

<div class="{if ($step && $step != 'fields') || !$step}hide{/if}" id="step_2">
    {include file='blocks/fieldset_header.tpl' id='booking_mes' name=$lang.booking_client_details}

    <form action="#" method="post">
        <input type="hidden" name="booking_order" value="">

        {foreach from=$fields item='field'}
            {assign var='fKey' value=$field.Key}
            {assign var='fVal' value=$smarty.post.f}

            <div class="submit-cell">
                <div class="name">
                    {if $field.Required}<span class="red">*</span>{/if} {$field.name}
                    {if !empty($field.description)}
                        <img class="booking_qtip" alt="" title="{$field.description}" id="fd_{$field.Key}" src="{$rlTplBase}img/blank.gif" />
                    {/if}
                </div>

                <div class="field {if $field.Type == 'bool'}inline-fields{/if}">
                    {if $field.Type == 'text'}
                        <input class="wauto {if $field.Required}required{/if} {if $field.Condition == 'isEmail'}isEmail{elseif $field.Condition == 'isUrl'}isUrl{elseif $field.Condition == 'isDomain'}isDomain{/if}" type="text" name="{$field.Key}" maxlength="{if $field.Values > 0}{$field.Values}{else}150{/if}" value ="{if $booking_fields[$field.Key]}{$booking_fields[$field.Key]}{elseif $field.Key == 'first_name' && $account_info.First_name}{$account_info.First_name}{elseif $field.Key == 'last_name' && $account_info.Last_name}{$account_info.Last_name}{elseif $field.Key == 'email' && $account_info.Mail}{$account_info.Mail}{elseif $field.default}{$field.default}{/if}" />
                    {elseif $field.Type == 'textarea'}
                        <textarea rows="5" class="text {if $field.Required}required{/if}" name="{$field.Key}" id="textarea_{$field.Key}">{if $booking_fields[$field.Key]}{$booking_fields[$field.Key]}{elseif $fVal.$fKey}{$fVal.$fKey}{elseif $field.Default}{$field.Default}{/if}</textarea>
                        <script class="fl-js-dynamic">{literal}
                            $(document).ready(function() {
                                $('#textarea_{/literal}{$field.Key}{literal}').textareaCount({
                                    'maxCharacterSize': {/literal}{$field.Values}{literal},
                                    'warningNumber'   : 20
                                });
                            });
                        {/literal}</script>
                    {elseif $field.Type == 'number'}
                        <input class="numeric wauto {if $field.Required}required{/if}" type="text" name="{$field.Key}" size="{if $field.Values}{$field.Values}{else}18{/if}" maxlength="{if $field.Values}{$field.Values}{else}18{/if}" value="{if $booking_fields[$field.Key]}{$booking_fields[$field.Key]}{elseif $fVal.$fKey}{$fVal.$fKey}{/if}"/>
                    {elseif $field.Type == 'bool'}
                        <span class="custom-input">
                            <label>
                                <input class="bool" type="radio" value="1" name="{$field.Key}" {if $booking_fields[$field.Key] == '1'}{$booking_fields[$field.Key]}{elseif $fVal.$fKey == '1'}checked="checked"{elseif $field.Default}checked="checked"{/if} />
                                {$lang.yes}
                            </label>
                        </span>
                        <span class="custom-input">
                            <label>
                                <input class="bool" type="radio" value="0" name="{$field.Key}" {if $booking_fields[$field.Key] == '0'}{$booking_fields[$field.Key]}{elseif $fVal.$fKey == '0'}checked="checked"{elseif !$field.Default}checked="checked"{/if} />
                                {$lang.no}
                            </label>
                        </span>
                    {/if}
                </div>
            </div>
        {/foreach}

        <div class="submit-cell">
            <div class="name"></div>
            <div class="field">
                {assign var='b_terms' value=`$smarty.ldelim`terms`$smarty.rdelim`}
                {assign var="b_terms_page_key" value='pages+title+booking_terms_and_conditions'}
                {assign var="b_terms_page_name" value=$lang.$b_terms_page_key}

                {pageUrl key="booking_terms_and_conditions" assign="booking_terms_and_conditions_url"}

                {assign var="b_link" value='<a target="_blank" href="'|cat:$booking_terms_and_conditions_url|cat:'">'|cat:$b_terms_page_name|cat:'</a>'}
                {assign var="b_accept_notice" value=$lang.booking_terms_and_conditions_notice|replace:$b_terms:$b_link}

                {$b_accept_notice}
            </div>
        </div>

        <div class="submit-cell buttons">
            <div class="name"></div>
            <div class="field">
                <input type="submit" class="button" id="checkValid" value="{$lang.next_step}" />
            </div>
        </div>
    </form>

    {include file='blocks/fieldset_footer.tpl'}
</div>

<!-- booking fields end -->
