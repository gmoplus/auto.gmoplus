<!-- the checkbox with terms of condition in the registration page -->

{assign var='sProfile' value=$smarty.post.profile}

<script class="fl-js-dynamic">{literal}
$(document).ready(function(){
    var $accountType = $('select[name="profile[type]"]');

    if ($('.aff_term_of_use').length == 0) {
        {/literal}var aff_checkbox_content = '<div class="aff_term_of_use hide" style="padding-top:10px">';
        aff_checkbox_content     += '<label><input type="checkbox" name="profile[affiliate_accept]" value="yes"';
        aff_checkbox_content     += ' {if $sProfile.affiliate_accept == "yes"}checked="checked"{/if}>';
        aff_checkbox_content     += ' {$lang.aff_affiliate_accept}';
        aff_checkbox_content     += ' {$aff_terms_of_use_link}</label></div>';{literal}
        $(aff_checkbox_content).insertAfter($accountType);
        flynaxTpl.customInput();
        var $checkboxContainer = $('.aff_term_of_use');

        $accountType.change(function(){
            $checkboxContainer[$(this).val() == '{/literal}{$affiliate_ID}{literal}' ? 'show' : 'hide']();
        });

        // show checkbox if user was reverted to same step
        {/literal}{if $sProfile.type == $affiliate_ID}
            $checkboxContainer.show();
        {/if}{literal}

        // update the profile submit handler
        var submit_allowed = false;
        $('form[name=account_reg_form]').unbind('submit').submit(function(){
            if ($accountType.val() == '{/literal}{$affiliate_ID}{literal}') {
                submit_allowed = false;
                if ($('input[name="profile[affiliate_accept]"]').is(':checked')) {
                    submit_allowed = true;
                } else {
                    printMessage(
                        'error',
                        '{/literal}{$lang.aff_affiliate_not_accepted}{literal}',
                        'profile[affiliate_accept]'
                    );
                }
            } else {
                submit_allowed = true;
            }

            return submit_allowed ? true : false;
        });
    }
});
{/literal}</script>

<!-- the checkbox with terms of condition in the registration page end -->
