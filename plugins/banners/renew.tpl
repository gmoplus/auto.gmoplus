<!-- renew tpl -->

<div class="highlight clear">
    <!-- checkout -->
    <div class="area_checkout step_area">
        {if isset($smarty.get.canceled)}
            <script class="fl-js-dynamic">
                printMessage('error', '{$lang.bannersNoticePaymentCanceled}', 0, 1);
            </script>
        {/if}

        {include file='blocks/fieldset_header.tpl' id='plans' name=$lang.select_plan tall=false}
        {include file=$smarty.const.RL_PLUGINS|cat:'banners/banner_plans.tpl' plans=$planInfo}
        {include file='blocks/fieldset_footer.tpl'}

        <!-- select a payment gateway -->
        <form method="post" action="{pageUrl key=$pageInfo.Key vars="id=`$bannerInfo.ID`"}">
            <input type="hidden" name="form" value="checkout" />
            {assign var='showFormButtons' value=true}

            {if $planInfo[$smarty.post.plan].Price > 0}
                {gateways}

                {if $txn_info && $txn_info.Txn_ID != ''}
                    {assign var='showFormButtons' value=false}
                {/if}
            {/if}

            {if $showFormButtons}
                <table class="form">
                    <tr>
                        <td class="name">
                            <input type="submit" name="submit" value="{$lang.upgrade}" id="checkout_submit" />
                        </td>
                    </tr>
                </table>
            {/if}
        </form>
        <!-- select a payment gateway end -->
    </div>
    <!-- checkout end -->
</div>

<!-- renew tpl end -->
