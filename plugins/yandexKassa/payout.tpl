{assign var='yandexKassa_saved_card' value='|'|explode:$smarty.post.shc.yandexKassa_saved_card}

<div class="submit-cell clearfix">
    <div class="name">{$lang.yandexKassa_saved_card}</div>
    <div class="field single-field">
        <div id="yandexKassa-saved-card">
            <span>{$yandexKassa_saved_card[1]}</span>
            <a class="yk-remove-card{if !$yandexKassa_saved_card[1]} hide{/if}" title="{$lang.delete}" href="javascript://;">
                <svg viewBox="0 0 18 18" class="icon grid-icon-fill">
                    <use xlink:href="#close_icon"></use>
                </svg>
            </a>
            <a class="yk-add-card{if $yandexKassa_saved_card[1]} hide{/if}"  href="javascript://;">
                {$lang.yandexKassa_add_card}
            </a>
        </div>
    </div>
    <div id="yk-payout-container">
        <div id="yk-payout-form" class="hide">
            <div></div>
        </div>
    </div>
</div>

<script>
    {literal}
    $(document).ready(function() {
        $('a.yk-remove-card').click(function() {
            removeToken();
        });

        $('a.yk-add-card').click(function() {
            let el = '#yk-payout-form';
            $(el).removeClass('hide');
            $(el).append('<div id="payout-form" />');
            $(el).append('<div id="yk-loading">' + lang['loading'] + '</div>');

            flUtil.loadScript([
                rlConfig['tpl_base'] + 'components/popup/_popup.js',
            ], function(){
                $('.yk-add-card').popup({
                    click: false,
                    scroll: true,
                    closeOnOutsideClick: false,
                    content: $(el),
                    caption: '{/literal}{$lang.yandexKassa_add_card}{literal}',
                    onShow: function(popup) {
                        flUtil.loadScript([
                            'https://static.yoomoney.ru/payouts-data-widget-front/widget.js',
                        ], function() {
                            //Инициализация виджета. Все параметры обязательные.
                            const payoutsData = new window.PayoutsData({
                                successCallback(data) {
                                    //Обработка ответа с токеном карты
                                    saveToken(data);
                                    restorePayoutContainer(popup);
                                },
                                errorCallback(error) {
                                    //Обработка ошибок инициализации
                                    printMessage('error', error);
                                }
                            });
                            $(el).find('div#yk-loading').remove();

                            //Отображение платежной формы в контейнере
                            payoutsData.render('payout-form')
                                //После отображения платежной формы метод render возвращает Promise (можно не использовать).
                                .then(() => {
                                    //Код, который нужно выполнить после отображения платежной формы.
                                });
                        });
                    },
                    navigation: {
                        okButton: {
                            text: lang['save'],
                            onClick: function(popup){
                                restorePayoutContainer(popup);
                            }
                        },
                        cancelButton: {
                            text: lang['cancel'],
                            class: 'cancel',
                            onClick: function(popup){
                                let el = '#yk-payout-container > #yk-payout-form';
                                $(el).addClass('hide');
                                $(el).html('').append('<div />');

                                popup.close();
                            }
                        }
                    },
                    onClose: function(popup){
                        restorePayoutContainer(popup);
                    }
                });
            });
        });
    });

    let saveToken = function(tokenInfo) {
        if (!tokenInfo.synonim) {
            return;
        }

        let data = {
            mode: 'yandexKassaSaveToken',
            bankName: tokenInfo.bankName,
            panmask: tokenInfo.panmask,
            synonim: tokenInfo.synonim,
            type: tokenInfo.type,
        };
        flUtil.ajax(data, function(response) {
            if (response.status == 'OK') {
                let ykEl = $('#yandexKassa-saved-card');
                ykEl.find('span').html(tokenInfo.panmask);
                ykEl.find('.yk-remove-card').removeClass('hide');
                ykEl.find('.yk-add-card').addClass('hide');
                printMessage('notice', response.message);
            } else {
                printMessage('error', response.message);
            }
        });
    }

    let removeToken = function() {
        let data = {
            mode: 'yandexKassaRemoveToken'
        };
        flUtil.ajax(data, function(response) {
            if (response.status == 'OK') {
                let ykEl = $('#yandexKassa-saved-card');
                ykEl.find('span').html('');
                ykEl.find('.yk-remove-card').addClass('hide');
                ykEl.find('.yk-add-card').removeClass('hide');
                printMessage('notice', response.message);
            } else {
                printMessage('error', response.message);
            }
        });
    }

    let restorePayoutContainer = function(popup) {
        let el = '#yk-payout-container > #yk-payout-form';
        if ($(el).length > 0) {
            $(el).addClass('hide');
            $(el).html('').append('<div />');
        } else {
            $('#yk-payout-container').append('<div id="yk-payout-form"><div></div></div>');
            $(el).addClass('hide');
        }
        popup.remove();
    }
    {/literal}
</script>
