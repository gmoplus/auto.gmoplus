<!-- openAI field handler script -->

<span class="d-none">{fetch file=$smarty.const.RL_PLUGINS|cat:'openAI/static/icon.svg'}</span>

<script class="fl-js-dynamic">
rlConfig['price_tag_field'] = '{$config.price_tag_field}';
var openai_fields = JSON.parse('{$openai_fields|@json_encode|escape:'quotes'}');
var openai_db_data = JSON.parse('{$openai_db_data|@json_encode|escape:'quotes'}');
var openai_listing_id = {if $openai_listing_id}'{$openai_listing_id}'{else}false{/if};
var openai_title_field_key = {if $config.openai_title_field}"{$config.openai_title_field}"{else}false{/if};
var openai_ignore_fields = {if $config.openai_ignore_fields}"{$config.openai_ignore_fields}"{else}false{/if};

rlConfig['openai_documentation'] = '{$config.openai_documentation}';

lang['or'] = "{$lang.or}";

{foreach from=$openai_phrases item='openai_phrase' key='openai_phrase_key'}
lang['{$openai_phrase_key}'] = '{$openai_phrase|escape:'quotes'|replace:$smarty.const.PHP_EOL:'<br />'}';
{/foreach}

{literal}

/**
 * Redefine flynax.htmlEditor function to disable hardLimit option of the wordcount plugin
 * @todo - Remove once the wordcount config.hardLimit option is disabled in form.js file
 *         or possibilities to edit field ckeditor configuration is available
 */
var flynaxHtmlEditor = flynax.htmlEditor;
flynax.htmlEditor = function(fields, additional_configs) {
    if (typeof additional_configs[0] == 'object') {
        if (additional_configs[0][0] == 'wordcount') {
            additional_configs[0][1].hardLimit = false;
        }
    }
    flynaxHtmlEditor(fields, additional_configs);
}

$(function(){
    if (!openai_fields.length) {
        console.log('OpenAI: no assigned textarea fields found');
        return;
    }

    if (!openai_listing_id) {
        console.log('OpenAI: no listing ID specified');
        return;
    }

    if (!openai_title_field_key) {
        console.log('OpenAI: no title field key specified');
        return;
    }

    // Activate current language tabs for title field
    $('#sf_field_' + openai_title_field_key + ' .tabs li[lang=' + rlLang + '] a').click();

    var avoid_fields = openai_ignore_fields.split(',');
    avoid_fields.push(openai_title_field_key);
    avoid_fields.push(rlConfig['price_tag_field']);

    var setText = function(text, $OAIfield, instance){
        if (instance) {
            instance.setData(text.replace(/(?:\r\n|\r|\n)/g, '<br>'));
        } else {
            $OAIfield.val(text);
        }
    }

    var openaiCall = function(data, isMultilingual, isHtml, $OAILink, $OAIfield, $OAIFieldCont, callBack){
        data.mode = 'openAI';

        $OAILink.html($OAILink.html().replace(lang['openai_generate_button_text'], lang['loading']));
        $OAILink.addClass('openai-generate_loading').removeClass('link');

        var instance = false;

        if (isHtml) {
            var field_index = 'textarea_' + data.field_key + (isMultilingual ? '_' + rlLang : '');
            instance = CKEDITOR.instances[field_index];
        }

        flUtil.ajax(data, function(response, status){
            var phrase_key = status == 'success' && response.status == 'OK'
            ? 'openai_generated_button_text'
            : 'openai_generate_button_text';

            $OAILink
                .html($OAILink.html().replace(lang['loading'], lang[phrase_key]))
                .addClass('link')
                .removeClass('openai-generate_loading');

            if (!callBack && status != 'success') {
                printMessage('warning', lang['system_error']);
                return;
            }

            if (response.status == 'OK') {
                if (typeof response.results == 'object') {
                    if (isMultilingual) {
                        var $mlField = $OAIFieldCont.find('.ml_tabs_content > *[lang=' + response.results.Lang + '] > textarea');
                        setText(response.results.Text, $mlField, instance);
                    } else {
                        setText(response.results.Text, $OAIfield, instance);
                    }
                    $OAIFieldCont.find('.tabs li[lang=' + response.results.Lang + '] a').click();
                } else {
                    setText(response.results, $OAIfield, instance);
                }

                $OAILink.addClass('openai-generate_done').removeClass('link');
            } else if (response.message) {
                printMessage('error', response.message);
            }

            if (typeof callBack == 'function') {
                callBack.call(this, response, status);
            }
        });
    }

    var collectFormData = function($form){
        var data = [];

        $form.find('.field.combo-field,.field.single-field').filter(':not(.phone)').each(function(){
            var $nameCont = $(this).prev();
            $nameCont.find('> *').remove();
            var field_name = $nameCont.text().trim();
            var not_filter = ':not([name^="f[' + avoid_fields.join(']"]):not([name^="f[') + ']"])';

            // Text fields
            var $textField = $(this).find('input[type=text][name^="f["]:first' + not_filter);

            if ($(this).find('.ml_tabs_content').length) {
                $textField = $(this).find('.ml_tabs_content > *[lang=' + rlLang + '] > input' + not_filter);
            }

            if ($textField.length && $textField.val()) {
                var $fieldCont = $textField.closest('.field');
                var field_value = $textField.val().trim();
                var field_text = field_name + ': ' + field_value;
                
                if ($fieldCont.hasClass('combo-field')) {
                    $combo = $textField.next();
                    if ($combo.prop('tagName').toLowerCase() == 'select') {
                        if ($combo.val() != '') {
                            field_text += ' ' + $combo.find('option:selected').text().trim();
                        }
                    } else {
                        field_text += ' ' + $combo.next().text().trim();
                    }
                }
                data.push(field_text);
            }

            // Select fields
            var $selectField = $(this).find('select[name^="f["]:first' + not_filter).filter(':not([name*="[df]"])');
            if ($selectField.length && $selectField.val() != '' && $selectField.val() != '0') {
                var field_text = field_name + ': ' + $selectField.find('option:selected').text().trim();
                data.push(field_text);
            }
        });

        return data;
    }

    for (var i in openai_fields) {
        var field_key = openai_fields[i];
        var $OAIFieldCont = $('#sf_field_' + field_key);
        var isMultilingual = $OAIFieldCont.find('.ml_tabs_content').length ? true : false;
        var isHtml = window.textarea_fields['textarea_' + field_key + (isMultilingual ? ('_' + rlLang) : '')].type == 'html';

        if (isMultilingual) {
            $OAIFieldCont.find('.tabs li[lang=' + rlLang + '] a').get(0).click();
            var $OAIfield = $OAIFieldCont.find('.ml_tabs_content > *[lang=' + rlLang + '] > textarea');
        } else {
            var $OAIfield = $OAIFieldCont.find('> textarea');
        }

        // Reset textarea styles
        $OAIfield.css({
            maxWidth: 'inherit',
            display: 'initial'
        });

        setTimeout((function(field_key, $OAIFieldCont, isMultilingual, isHtml, $OAIfield){
            var data_exists = openai_db_data[field_key];
            var link_phrase_key = data_exists && $OAIfield.val().trim().length > 100
            ? 'openai_generated_button_text'
            : 'openai_generate_button_text';

            var $counter = $OAIfield.next();
            var wrapper_class = isHtml ? 'mt-2' : '';
            $counter.wrap($('<div class="d-flex openai-wrapper ' + wrapper_class + '"></div>'));

            var $openAIWrapper = $OAIFieldCont.find('.openai-wrapper');
            var $content = $('<span class="ml-auto"><span class="link openai-generate d-flex align-items-center date"><svg width="16px" viewBox="0 0 24 24" class="mr-1 grid-icon-fill"><use xlink:href="#openai-logo"></use></svg>' + lang[link_phrase_key] + '</span></span>');

            $openAIWrapper.append($content);
            var $OAILink = $OAIFieldCont.find('.openai-generate');

            if (data_exists) {
                $OAILink.click(function(){
                    var data = {
                        listing_id: openai_listing_id,
                        field_key: field_key
                    };
                    openaiCall(data, isMultilingual, isHtml, $OAILink, $OAIfield, $OAIFieldCont);
                });
            } else {
                var popover_content = `${lang['openai_generated_popup_hint']}
                <div class="mt-3 no-gutters text-center">
                    <input class="w-100 openai-generate-automatically" type="button" value="${lang['openai_generated_automatically']}" />
                    <div class="mt-2">${lang['or']} <span class="link">${lang['openai_generated_manually']}</span></div>
                </div>`;

                $OAILink.popover({
                    content: popover_content,
                    width: 300,
                    onShow: function($body){
                        var $buttonAuto = $body.find('.openai-generate-automatically');
                        var $buttonManually = $body.find('span.link');
                        var popoverClass = this;

                        if ($OAILink.hasClass('openai-generate_done') || $OAILink.hasClass('openai-generate_loading')) {
                            popoverClass.destroy();
                            return;
                        }

                        // Automatically mode
                        $buttonAuto.click(function(){
                            var errors = [];
                            var errors_fields = '';

                            var $form = $('#listing_form');
                            var titleName = 'f[' + openai_title_field_key + ']';
                            titleName += $('#sf_field_' + openai_title_field_key + ' > .ml_tabs_content').length ? '[' + rlLang + ']' : '';
                            var $titleField = $form.find('input[name^="' + titleName + '"]');
                            var title_text = $titleField.val();

                            if (title_text.length < 10) {
                                errors_fields = $titleField.attr('name');
                                errors.push(lang['openai_error_short_title']);
                            }

                            if (errors.length) {
                                printMessage('error', errors, errors_fields);
                            } else {
                                var form_data = collectFormData($form);

                                if (!form_data.length) {
                                    errors.push(lang['openai_error_no_data']);
                                }

                                if (errors.length) {
                                    printMessage('error', errors);
                                } else {
                                    popoverClass.close();

                                    var payload = {
                                        title: title_text,
                                        data: form_data,
                                        listing_id: openai_listing_id,
                                        field_key: field_key
                                    };
                                    openaiCall(payload, isMultilingual, isHtml, $OAILink, $OAIfield);
                                }
                            }
                        });

                        $buttonManually.click(function(){
                            popoverClass.close();

                            var $form = $('#listing_form');
                            var titleName = 'f[' + openai_title_field_key + ']';
                            titleName += $('#sf_field_' + openai_title_field_key + ' > .ml_tabs_content').length ? '[' + rlLang + ']' : '';
                            var $titleField = $form.find('input[name^="' + titleName + '"]');
                            var title_text = $titleField.val();
                            var form_data = collectFormData($form);

                            var user_message_template = lang.openai_message_user_template
                                .replace('{title}', title_text)
                                .replace('{options}', form_data.join(', '));

                            var popup_form = `<div class="d-flex flex-column w-100">
                                <div class="submit-cell d-block">
                                    <div class="name">${lang.openai_system_message}</div>
                                    <input name="system_message" type="text" class="w-100" value="${lang.openai_message_system_template}" />
                                </div>
                                <div class="flex-fill">
                                    <div class="submit-cell flex-column d-flex h-100">
                                        <div class="name flex-basis-0">${lang.openai_user_message}</div>
                                        <textarea name="user_message" class="w-100 h-100 flex-fill">${user_message_template}</textarea>
                                    </div>
                                </div>
                                <div class="openai-errors-area red font-size-xs"></div>
                                <div class="mt-2 d-flex align-items-center">
                                    <input type="button" value="${lang.openai_generate}" />
                                    <span class="link close ml-3">${lang.cancel}</span>

                                    <div class="ml-auto">
                                        <a href="${rlConfig.openai_documentation}" rel="nofollow" target="_blank"><span class="font-size-xs">${lang.openai_documentation}</span></a>
                                    </div>
                                </div>
                                <div class="mt-3 d-flex align-items-center date">${lang.openai_manual_popup_hint}</div>
                            </div>`;

                            setTimeout(function(){
                                $('body').popup({
                                    width: 800,
                                    height: 500,
                                    click: false,
                                    caption: lang['openai_generate_button_text'],
                                    content: popup_form,
                                    onShow: function($body){
                                        var popupClass = this;
                                        var $userMessage = $body.find('[name=user_message]');
                                        var $systemMessage = $body.find('[name=system_message]');
                                        var $generateButton = $body.find('input[type=button]');
                                        var $errorArea = $('.openai-errors-area');

                                        $generateButton.click(function(){
                                            var user_message_value = $userMessage.val();
                                            var system_message_value = $systemMessage.val();
                                            var error = false;

                                            if (user_message_value.length < 10) {
                                                $userMessage.addClass('error');
                                                error = true;
                                            }

                                            if (system_message_value.length < 10) {
                                                $systemMessage.addClass('error');
                                                error = true;
                                            }

                                            if (!error) {
                                                $generateButton
                                                    .attr('disabled', 'disabled')
                                                    .val(lang['loading']);

                                                $errorArea.text('');

                                                var payload = {
                                                    user_message: user_message_value,
                                                    system_message: system_message_value,
                                                    listing_id: openai_listing_id,
                                                    field_key: field_key
                                                };

                                                openaiCall(payload, isMultilingual, isHtml, $OAILink, $OAIfield, null, function(response, status){
                                                    if (status == 'success') {
                                                        if (response.status == 'ERROR') {
                                                            $errorArea.text(response.message);
                                                        } else {
                                                            popupClass.close();
                                                        }
                                                    }

                                                    $generateButton
                                                        .removeAttr('disabled')
                                                        .val(lang['openai_generate']);
                                                });
                                            }
                                        });

                                        $systemMessage.on('click focus', function(){
                                            $(this).removeClass('error');
                                        });

                                        $userMessage.on('click focus', function(){
                                            $(this).removeClass('error');
                                        });

                                        $body.find('.close').click(function(){
                                            popupClass.close();
                                        });
                                    }
                                });
                            }, 10);
                        });
                    }
                });
            }
        })(field_key, $OAIFieldCont, isMultilingual, isHtml, $OAIfield), 10);
    }
});

{/literal}
</script>

<!-- openAI field handler script end -->
