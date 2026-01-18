<!-- Average Price -->

{assign var='sReplace' value=`$smarty.ldelim`title`$smarty.rdelim`}
{assign var='apHeaderBox' value=$apData.headerBox}
<p>{$lang.ap_title|replace:$sReplace:$apHeaderBox}</p>

<div class="ap-container relative {if $config.ap_hide_footer}without-footer{/if}">
    <div class="ap-absolute">
        <svg viewBox="0 0 128 23" class="ap-graph">
            <use xlink:href="#ap-graph"></use>
        </svg>

        {if $apData.graphPercent >= 47 && $apData.graphPercent <= 50}
            {assign var='apGraphPadding' value=47}
        {elseif $apData.graphPercent > 50 && $apData.graphPercent <= 53}
            {assign var='apGraphPadding' value=53}
        {else}
            {assign var='apGraphPadding' value=$apData.graphPercent}
        {/if}

        <div class="ap-listing" style="padding-left: {$apGraphPadding}%">
            <span class="ap-listing-line"></span>
            <div class="ap-listing-cont {if $apData.graphPercent > 50}higher{/if}">
                <div class="ap-caption">
                    {if $apData.graphPercent > 50}
                        {$lang.ap_bad_price}
                    {else}
                        {$lang.ap_great_price}
                    {/if}
                </div>
                <div><b class="listing-price">{$apData.listingPrice}</b></div>
            </div>
        </div>
    </div>

    <div class="ap-price align-center">
        <div>{$lang.ap_price}</div>
        <div><b class="average-price">{$apData.averagePrice}</b></div>
    </div>
</div>

{if !$config.ap_hide_footer}
    <div class="ap-search-form hide">
        {include file='blocks/search_block.tpl'}
    </div>

    {* Replace count of found listings *}
    {assign var='sReplace' value=`$smarty.ldelim`count`$smarty.rdelim`}
    {assign var='apCountListings' value=$lang.ap_link_text2|replace:$sReplace:$apData.countListings}

    {* Add properly link to search results page *}
    {assign var='sReplace' value=`$smarty.ldelim`link`$smarty.rdelim`}
    {assign var='apSearchLink' value='<a id="apSearchButton" href="javascript://">'|cat:$apCountListings|cat:'</a>'}

    {$lang.ap_link_text1|replace:$sReplace:$apSearchLink}

    <script class="fl-js-dynamic">
    var apListingID = '{$listing_data.ID}';

    {literal}
    $(function() {
        $('#apSearchButton').click(function() {
            $(this).prev('.ap-search-form').find('form').submit();
        });

        // Add hidden plugin fields to search form
        $('.ap-search-form form').append(
            $('<input>').attr({type: 'hidden', name: 'ap-search', value: 'true'}),
            apListingID ? $('<input>').attr({type: 'hidden', name: 'ap-listing-id', value: apListingID}) : null
        );
    });
    {/literal}</script>
{/if}

<svg width="100%" class="hide" xmlns="http://www.w3.org/2000/svg">
    {if $apData.graphPercent > 50}
        {assign var='apGraphFigureColor' value='#EC5145'}
        {assign var='apGraphBackgroundColor' value='#D8D8D8'}
    {else}
        {assign var='apGraphFigureColor' value='#D8D8D8'}
        {assign var='apGraphBackgroundColor' value='#048900'}
    {/if}

    <g id="ap-graph">
        <path d="M64 0c29.04 0 41.8 23 64 23H0C22.194 23 34.96 0 64 0z" fill="{$apGraphFigureColor}"/>
        <path d="M0 23C22.194 23 34.96 0 64 0v23H0z" fill="{$apGraphBackgroundColor}"/>
    </g>
</svg>

<script class="fl-js-dynamic">{literal}
$(function() {
    if (typeof $.convertPrice == 'function') {
        $('.ap-container .average-price, .ap-container .listing-price').convertPrice({
            showCents: false,
            shortView: true
        });
    }
});
{/literal}</script>

<!-- Average Price end -->
