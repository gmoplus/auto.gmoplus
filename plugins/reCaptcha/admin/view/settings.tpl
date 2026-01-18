<!-- Settings handler of the reCaptcha plugin tpl -->

<script>{literal}
    let $reCaptchaTypeField = $('[name="post_config[reCaptcha_type][value]"]');

    $(function() {
        reCaptchaTypeHandler($reCaptchaTypeField.find('option:selected').val());

        $reCaptchaTypeField.change(function() {
            reCaptchaTypeHandler($reCaptchaTypeField.find('option:selected').val());
        });
    });

    /**
     * Show/hide necessary fields of selected reCaptcha type
     */
    const reCaptchaTypeHandler = function(type) {
        let $position = $('[name="post_config[reCaptcha_position][value]"]');

        $('[name="post_config[reCaptcha2_theme][value]"]').closest('tr')[
            type === 'v3' ? 'addClass' : 'removeClass'
        ]('hide');

        $('[name="post_config[reCaptcha2_compact][value]"]').closest('tr')[
            type === 'v2_checkbox' ? 'removeClass' : 'addClass'
        ]('hide');

        $('[name="post_config[reCaptcha3_score][value]"]').closest('tr')[
            type === 'v3' ? 'removeClass' : 'addClass'
        ]('hide');

        $position.closest('tr')[type !== 'v2_checkbox' ? 'removeClass' : 'addClass']('hide');

        if (type === 'v3') {
            $position.find('[value="inline"]').prop('disabled', true).addClass('hide');

            if ($position.val() === 'inline') {
                $position.val('bottomleft');
            }
        } else {
            $position.find('[value="inline"]').prop('disabled', false).removeClass('hide');
        }
    }
{/literal}</script>

<!-- Settings handler of the reCaptcha plugin tpl end -->
