<!-- testimonials page content -->

<div class="testimonials-area">
{if $testimonials}
    {include file=$smarty.const.RL_PLUGINS|cat:'testimonials'|cat:$smarty.const.RL_DS|cat:'dom.tpl'}
{/if}
</div>

{if !$testimonials}
    <div class="text-notice">{$lang.testimonials_no_testimonials}</div>
{/if}

<div class="testimonials-form mx-auto mt-{if $testimonials}3{else}5{/if} content-padding">
    <a id="add-testimonial"></a>

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' name=$lang.testimonials_add_testimonial}

    <form class="content-padding" method="POST" action="" name="testimonial-form">
        <div class="submit-cell">
            <div class="field">
                <input placeholder="{$lang.your_name} *" type="text" size="32" class="w-100" maxlength="32" id="t-name" {if $account_info}value="{$account_info.Full_name}"{/if} />
            </div>
        </div>
        <div class="submit-cell">
            <div class="field">
                <input placeholder="{$lang.your_email}" type="text" size="50" class="w-100" maxlength="100" id="t-email" />
            </div>
        </div>
        <div class="submit-cell">
            <div class="field">
                <textarea placeholder="{$lang.testimonials_testimonial} *" id="t-testimonial" cols="" rows="6"></textarea>
            </div>
        </div>
        <div class="submit-cell">
            <div class="field">{include file='captcha.tpl' no_caption=true}</div>
        </div>
        <div class="submit-cell buttons">
            <div class="field"><input class="w-100" type="submit" name="finish" value="{$lang.add}" data-default-phrase="{$lang.add}" /></div>
        </div>
    </form>

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
</div>

<script class="fl-js-dynamic">
rlConfig['testimonials_moderate'] = {if $config.testimonials_moderate}true{else}false{/if};

{literal}

var setTriangleColor = function(){
    var color = $('.testimonials-area div.hlight').css('background-color');

    $('.testimonials-area .testimonial-triangle').css(
        'border-' + (rlLangDir == 'rtl' ? 'top' : 'right') + '-color',
        color
    );
}

$(function(){
    var $fieldset = $('#controller_area .fieldset');
    var $form = $('form[name="testimonial-form"]');
    var $button = $form.find('input[type=submit]');
    var $container = $('.testimonials-area');

    setTriangleColor();

    $('a.post_ad').click(function(e){
        flynax.slideTo($fieldset);
        return false;
    });
    
    if (flynax.getHash() == 'add-testimonial') {
        flynax.slideTo($fieldset);
    }

    $form.submit(function(){
        $button
            .val(lang['loading'])
            .addClass('disabled')
            .attr('disabled', true);

        flUtil.ajax({
                mode: 'addTM',
                nameTM: $(this).find('#t-name').val(),
                emailTM: $(this).find('#t-email').val(),
                testimonial : $(this).find('#t-testimonial').val(),
                captchaTM: $(this).find('#security_code').val()
            },
            function(response, status) {
                if (response && status == 'success') {
                    if (response.status === 'ERROR') {
                        printMessage('error', response.errorContent, response.errorFields); 
                    } else {
                        $form.find('#t-name,#t-email,#t-testimonial').val('');
                        $('#security_img').trigger('click');

                        // Reset captcha/reCaptcha widget
                        if (typeof ReCaptcha === 'object' && typeof ReCaptcha.resetWidgetByIndex === 'function') {
                            ReCaptcha.resetWidgetByIndex($form.find('.gptwdg').attr('data-recaptcha-index'));
                        } else {
                            $form.find('#security_code').val('');
                        }

                        if (!rlConfig['testimonials_moderate']) {
                            $('.text-notice').remove();

                            $container.html('');
                            flynax.slideTo('body');

                            $container.append(response.data)
                            setTriangleColor();
                        }

                        printMessage('notice', response.msg);
                    }
                } else {
                    printMessage('error', lang.system_error);
                }

                $button
                    .val($button.data('default-phrase'))
                    .removeClass('disabled')
                    .attr('disabled', false);
            }
        );

        return false;
    });
});

{/literal}
</script>

<!-- testimonials page content -->
