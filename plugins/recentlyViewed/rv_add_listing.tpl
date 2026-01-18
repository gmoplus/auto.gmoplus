{assign var='page_key' value='lt_'|cat:$listing_data.Listing_type}

{php}
    global $listing_data, $rlSmarty;

    $rlSmarty->assign(
        'l_title',
        $rlSmarty->str2path(
            $GLOBALS['rlListings']->getListingTitle($listing_data['Category_ID'], $listing_data, $GLOBALS['listing_type']['Key'])
        )
    );
{/php}

<script class="fl-js-dynamic">
lang['rv_listings'] = "{$lang.rv_listings}";
var rv_total_count    = '{$rv_total_count}';
var template_name     = '{$tpl_settings.name}';
var template_version  = '{$tpl_settings.version}';
var rv_history_link   = "{pageUrl key='rv_listings'}";
var storage_item_name = '{$smarty.const.RL_URL_HOME|parse_url:$smarty.const.PHP_URL_HOST|replace:".":"_"|cat:"_"}';
storage_item_name     += '{if $smarty.const.RL_DIR}{$smarty.const.RL_DIR|replace:"/":""}{/if}';

$(function (){literal} {
    xdLocalStorageInit(function () {
        if (isLocalStorageAvailable()) {{/literal}
            var listing_id  = '{$listing_data.ID}';
            var photo       = '{$listing_data.Main_photo}';
            var page_key    = '{$pages.$page_key}';
            var path        = '{$listing_data.Path}/{$l_title}';
            var title       = "{$pageInfo.name|escape:'javascript'|escape:'html'}";
            var listing_url = '{listingUrl listing=$listing_data}';

            rvAddListing([listing_id, photo, page_key, path, title, listing_url], {if $rvShowBox}true{else}false{/if});
        {literal}} else {
            console.log("Error. Your browser doesn't support web storage");
        }
    });
});{/literal}
</script>
