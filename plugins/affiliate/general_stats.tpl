<!-- my affiliates page tpl -->

<div class="content-padding">
    {if $isLogin}
        <div class="affiliate-stats">
            {if $config.aff_html_in_link}
                <div class="submit-cell">{$lang.aff_your_referral_link}:</div>
                <div class="aff-referral-link two-inline">
                    <div><input type="button" value="{$lang.select|replace:'-':''|trim}" /></div>
                    <div>
                        <input type="text" value='<a title="{$lang.aff_by_product}" href="{$smarty.const.RL_URL_HOME}?aff={$account_info.ID}">{$lang.aff_by_product}</a>' />
                    </div>
                </div>
            {else}
                <div class="submit-cell">
                    {$lang.aff_your_referral_link}:
                    <a class="aff-referral-link" href="javascript://">{$smarty.const.RL_URL_HOME}?aff={$account_info.ID}</a>
                </div>
            {/if}

            <div class="row current-stats">
                <h3 class="col-md-12">{$lang.aff_current_statistics}</h3>

                <div class="col-sm-5">
                    <div class="table-cell small">
                        <div class="name">{$lang.aff_cur_count_visitors}</div>
                        <div class="value"><span>{$stats.Current.Visitors}</span></div>
                    </div>
                    <div class="table-cell small">
                        <div class="name">{$lang.aff_cur_count_unique_visitors}</div>
                        <div class="value"><span>{$stats.Current.Unique_visitors}</span></div>
                    </div>
                    <div class="table-cell small">
                        <div class="name">{$lang.aff_cur_count_registered}</div>
                        <div class="value"><span>{$stats.Current.Registered}</span></div>
                    </div>
                    <div class="table-cell small">
                        <div class="name">{$lang.aff_cur_count_transactions}</div>
                        <div class="value"><span>{$stats.Current.Transactions}</span></div>
                    </div>
                    <div class="table-cell small">
                        <div class="name">{$lang.aff_cur_pending_earnings}</div>
                        <div class="value"><span>{$stats.Current.Pending_earnings}</span></div>
                    </div>
                    <div class="table-cell small">
                        <div class="name">{$lang.aff_cur_available_earnings}</div>
                        <div class="value"><span>{$stats.Current.Available_earnings}</span></div>
                    </div>
                </div>
                <div class="col-sm-7">
                    {if $stats.Current.Unique_visitors || $stats.Current.Transactions}
                        <canvas id="current_stats" width="150" height="150">
                            Your browser doesn't support HTML5.
                        </canvas>
                    {/if}
                </div>
            </div>

            <div class="row total-stats">
                <h3 class="col-md-12">{$lang.aff_general_statistics}</h3>

                <div class="col-sm-5">
                    <div class="table-cell small">
                        <div class="name">{$lang.aff_gen_count_visitors}</div>
                        <div class="value"><span>{$stats.Total.Visitors}</span></div>
                    </div>
                    <div class="table-cell small">
                        <div class="name">{$lang.aff_gen_count_unique_visitors}</div>
                        <div class="value"><span>{$stats.Total.Unique_visitors}</span></div>
                    </div>
                    <div class="table-cell small">
                        <div class="name">{$lang.aff_gen_count_registered}</div>
                        <div class="value"><span>{$stats.Total.Registered}</span></div>
                    </div>
                    <div class="table-cell small">
                        <div class="name">{$lang.aff_gen_count_transactions}</div>
                        <div class="value"><span>{$stats.Total.Transactions}</span></div>
                    </div>
                    <div class="table-cell small">
                        <div class="name">{$lang.aff_gen_earnings}</div>
                        <div class="value"><span>{$stats.Total.Earnings}</span></div>
                    </div>
                </div>
                <div class="col-sm-7">
                    {if $stats.Total.Unique_visitors || $stats.Total.Transactions}
                        <canvas id="total_stats" width="150" height="150">
                            Your browser doesn't support HTML5.
                        </canvas>
                    {/if}
                </div>
            </div>
        </div>

        {assign var='replace' value='<a href="'|cat:$my_profile_url|cat:'">$1</a>'}

        <script class="fl-js-dynamic">
        lang.aff_copy                  = '{$lang.aff_copy}';
        lang.aff_referral_link_coppied = '{$lang.aff_referral_link_coppied}';
        lang.aff_billing_details_empty = '{$lang.aff_billing_details_empty|regex_replace:"/\[(.*)\]/":$replace}';
        var current_stats_data         = '';
        var total_stats                = '';
        var aff_html_in_link           = {if $config.aff_html_in_link}true{else}false{/if};

        // current statistics
        {if $stats.Current.Unique_visitors || $stats.Current.Transactions || $stats.Current.Registered}
            current_stats_data = [{$stats.Current.Unique_visitors}, {$stats.Current.Transactions}, {$stats.Current.Registered}];
            var current_label = ['{$lang.aff_cur_count_visitors}', '{$lang.aff_cur_count_transactions}', '{$lang.aff_cur_count_registered}'];
        {/if}

        // total statistics
        {if $stats.Total.Unique_visitors || $stats.Total.Transactions || $stats.Total.Registered}
            total_stats = [{$stats.Total.Unique_visitors}, {$stats.Total.Transactions}, {$stats.Total.Registered}];
            var total_label = ['{$lang.aff_gen_count_visitors}', '{$lang.aff_gen_count_transactions}', '{$lang.aff_gen_count_registered}'];
        {/if}

        {literal}
        $(function() {
            if (aff_html_in_link) {
                $('div.aff-referral-link [type=button]').click(function() {
                    copyTextToClipboard($('div.aff-referral-link [type=text]').val());
                });
            } else {
                $('a.aff-referral-link').click(function() {
                    copyTextToClipboard($(this).text());
                });
            }

            function copyTextToClipboard(text) {
                if (!text) {
                    return false;
                }

                var $textarea   = document.createElement('textarea');
                $textarea.value = text;
                $textarea.type  = 'hidden';
                document.body.appendChild($textarea);
                $textarea.select();
                document.execCommand('copy');
                printMessage('notice', lang.aff_referral_link_coppied);
                document.body.removeChild($textarea);
            }

            // draw chart for current stats
            if (current_stats_data) {
                affiliateJS.buildChart('current_stats', current_stats_data, current_label);
            }

            // draw chart for total stats
            if (total_stats) {
                setTimeout(function(){
                    affiliateJS.buildChart('total_stats', total_stats, total_label);
                }, 500);
            }

            // show warning if Billing Details is missing
            {/literal}{if !$account_info.Aff_billing_details}
                printMessage('warning', lang.aff_billing_details_empty);
            {/if}{literal}
        });
        {/literal}
        </script>
    {else}
        {$lang.notice_should_login}
    {/if}
</div>

<!-- my affiliates page tpl end -->
