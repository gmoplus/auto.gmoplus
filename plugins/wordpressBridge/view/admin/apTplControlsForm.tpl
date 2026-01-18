<tr class="body">
    <td class="list_td">{$lang.wpb_refresh_cache_phrase}</td>
    <td class="list_td" align="center" style="width: 200px;">
        <input id="wpb-update-cache" type="button" value="{$lang.wpb_refresh}" style="margin: 0;width: 100px;">
    </td>
</tr>

<script>{literal}
    $(document).ready(function() {
        $('#wpb-update-cache').click(function() {
            var $updateCacheButton = $(this);
            var updateCacheButtonText = $updateCacheButton.val();

            $updateCacheButton.val(lang['loading']);

            WordPressBridgeCacheClass().refreshCache(function(response) {
                $updateCacheButton.val(updateCacheButtonText);
                printMessage('notice', response.message);
            });
        });
    });
{/literal}</script>
