<!-- facebook registration process -->

<script>
var closest_selector = "{if $tpl_settings.type == 'responsive_42'}.submit-cell{else}tr{/if}";
var wide_template = {if preg_match('/_wide$/', $tpl_settings.name)}true{else}false{/if};
var customPasswordStrengthTrigger = false;
var facebook_info_name = '{$facebook_info.name}';
var facebook_info_email = '{$facebook_info.email}';
var facebook_def_account_type_id = '{$facebook_def_account_type_id}';

{if $smarty.const.RL_MOBILE_HOME|defined}
    wide_template = false;
{/if}

{literal}
    flynax.passwordStrength = function() {
        if (customPasswordStrengthTrigger === true)
            return false;

        // fill out with Facebook details
        $('input[name="profile[username]"]').val(facebook_info_name);
        $('input[name="profile[mail]"]').val(facebook_info_email);
        $('select[name="profile[type]"] > option[value='+ facebook_def_account_type_id +']').prop('selected', true);

        // assign some vars
        var passwordSelector = 'input[name="profile[password]"]';
        var passwordRepeatSelector = 'input[name="profile[password_repeat]"]';

        // mobile version. aka: simple red,blue,grin
        // also wide templates
        if ($(passwordSelector).closest('.area_profile').length) {
            if (wide_template) {
                $(passwordSelector).closest(closest_selector).remove();
                $(passwordRepeatSelector).closest(closest_selector).remove();
            }
            else if ($(passwordSelector).closest(closest_selector).last().length) {
                $(passwordSelector).closest(closest_selector).last().remove();
                $(passwordRepeatSelector).closest(closest_selector).last().remove();
            }
            else {
                var passwordField = $(passwordSelector).closest('div');
                var passwordTitle = $(passwordField).prev();
                var passwordRepeatField = $(passwordRepeatSelector);
                var passwordRepeatTitle = $(passwordRepeatField).prev();

                $(passwordTitle).remove(); passwordTitle = null;
                $(passwordField).remove(); passwordField = null;
                $(passwordRepeatField).remove(); passwordRepeatField = null;
                $(passwordRepeatTitle).remove(); passwordRepeatTitle = null;
            }
        }
        else {
            // remove password fields
            $(passwordSelector).closest(closest_selector).remove();
            $(passwordRepeatSelector).closest(closest_selector).remove();

            // remove captcha
            if ($('input[name="security_code"]').length) {
                $('input[name="security_code"]').closest(closest_selector).remove();
            }
        }

        // append hidden fields
        $('form[name="account_reg_form"] input[name$="step"]').after('\
            <input type="hidden" name="profile[password]" value="fake" /> \
            <input type="hidden" name="profile[password_repeat]" value="fake" />'
        );
        customPasswordStrengthTrigger = true;
    }
{/literal}
</script>
<!-- facebook registration process end -->
