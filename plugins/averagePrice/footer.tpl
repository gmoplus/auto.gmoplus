<!-- Average Price update data of listing -->

{if $apListingID}
    <script class="fl-js-dynamic">
    var apListingID = '{$apListingID}';

    {literal}
    flUtil.ajax({mode: 'apUpdateListingData', item: apListingID}, function(response){
        if (response && response.status === 'OK' && response.html) {
            flUtil.loadStyle(rlConfig['plugins_url'] + 'averagePrice/static/style.css');
            var $averagePriceBox = $('.side_block.averagePrice');

            if ($averagePriceBox.length) {
                $averagePriceBox.replaceWith(response.html);
            } else {
                $('.side_block.seller-short').after(response.html);
            }

            if (typeof $.convertPrice == 'function') {
                $('.ap-container .average-price, .ap-container .listing-price').convertPrice({
                    showCents: false,
                    shortView: true
                });
            }

            $('#apSearchButton').click(function() {
                $(this).prev('.ap-search-form').find('form').submit();
            });

            // Add hidden plugin fields to search form
            $('.ap-search-form form').append(
                $('<input>').attr({type: 'hidden', name: 'ap-search', value: 'true'}),
                apListingID ? $('<input>').attr({type: 'hidden', name: 'ap-listing-id', value: apListingID}) : null
            );

            flUtil.loadScript(rlConfig['tpl_base'] + 'components/cascading-category/_cascading-category.js', function(){
                $('#cascading-category-{/literal}{$listing_type.Key}{literal}-').cascadingCategory();
            });
        }
    });
    {/literal}</script>
{/if}

<!-- Average Price update data of listing end -->
