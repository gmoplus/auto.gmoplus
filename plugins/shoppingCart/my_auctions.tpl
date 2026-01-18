<!-- my bids/offers | shopping cart -->

<div class="content-padding">
    {if !empty($auction_info)}
        {if $auction_mod == 'live'}
            {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/view/auction_details_live.tpl' auction_info=$auction_info}
        {else}
            {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/view/auction_details.tpl' auction_info=$auction_info}
        {/if}
    {elseif !isset($smarty.get.item)}
        <!-- tabs -->
        <ul class="tabs">
            {foreach from=$tabs item='tab' name='tabF'}{strip}
                <li {if ($smarty.foreach.tabF.first && !$auction_mod) || $auction_mod == $tab.key}class="active"{/if} id="tab_{$tab.key}">
                    <a href="#{$tab.key}" data-target="{$tab.key}">{$tab.name}</a>
                </li>
            {/strip}{/foreach}
        </ul>
        <!-- tabs end -->

        <div id="auctions">
            {if $auctions}
                {if $auction_mod == 'winnerbids' || !$auction_mod}
                    {assign var='auction_tab_file' value='my_auctions_won.tpl'}
                {else}
                    {assign var='auction_tab_file' value='my_auctions.tpl'}
                {/if}
                {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/view/'|cat:$auction_tab_file}
            {/if}
        </div>
    {/if}
</div>

<script>
var auctionHash = flynax.getHash();
var auctionMode = auctionHash ? auctionHash : 'winnerbids';
rlConfig['shc_orders_per_page'] = {if $config.shc_orders_per_page}{$config.shc_orders_per_page}{else}10{/if};
lang['shc_no_auctions'] = '{$lang.shc_no_auctions}';
{literal}

$(function(){
    $('ul.tabs li').click(function(){
        if ($(this).hasClass('more') || $(this).hasClass('overflowed')) {
            return;
        }

        auctionMode = $(this).attr('id').split('_')[1];
        loadAuctions(auctionMode);
    });

    $('#auctions').on('click', '[name=load_more_auctions]', function(){
        $(this)
            .prop('disabled', true)
            .addClass('disabled')
            .val(lang['loading']);

        var dataPage = $(this).data('page');
        var page = dataPage ? parseInt(dataPage) : 1;
        page++;
        loadAuctions(auctionMode, page);
        $(this).attr('data-page', page);
    });

    $('#tab_' + auctionMode).click();
});

var loadAuctions = function(mode, page) {
    var $container = $('#auctions');
    var $wrapper = $('#content_' + mode);
    var tabCreated = $wrapper.length;

    $container.find('> div').addClass('d-none');

    if (tabCreated) {
        $wrapper.removeClass('d-none');
    } else {
        $wrapper = $('<div>').attr('id', 'content_' + mode);
        $wrapper.html('<div class="preloader">'+ lang['loading']+'</div>');
    }

    $container.append($wrapper);

    if (!tabCreated || page) {
        var data = {
            mode: 'shoppingCartBidsOffers',
            item: mode,
            page: page ? page : 1
        };
        flUtil.ajax(data, function(response) {
            $wrapper.find('.preloader').remove();

            if (response.status == 'OK') {
                if (page) {
                    $wrapper.find('.list-table').append(response.content);

                    if (!response.count|| response.count < rlConfig['shc_orders_per_page']) {
                        $wrapper.find('.shc-load-more-button-cont').remove();
                    } else {
                        $loadMoreButton = $wrapper.find('[name=load_more_auctions]');
                        $loadMoreButton
                            .prop('disabled', false)
                            .removeClass('disabled')
                            .val($loadMoreButton.data('phrase'));
                    }
                } else {
                    if (response.count) {
                        $wrapper.html(response.content);
                    } else {
                        $wrapper.html('<div class="text-notice">' + lang['shc_no_auctions'] + '</div>');
                    }
                }
            } else {
                printMessage('error', response.message);
            }
        });
    }
}

{/literal}
</script>

<!-- my bids/offers end | shopping cart -->
