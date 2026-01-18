<!-- update interface -->

<div class="x-hidden" id="statistic">
    <div class="x-window-header">{$lang.shc_updating_caption}</div>
    <div class="x-window-body" style="padding: 10px 15px;">
        <table class="updating">
        <tr>
            <td class="name">
                {$lang.total_listings}:
            </td>
            <td class="value">
                <label id="total">{$updateListings.total}</label>
            </td>
        </tr>
        </table>
        
        <div id="dom_area">
            <table class="updating">
            <tr>
                <td class="name">
                    {$lang.total_listings}:
                </td>
                <td class="value">
                    <label id="updating">1-{if $updateListings.per_run > $updateListings.total}{$updateListings.total}{else}{$updateListings.per_run}{/if}</label>
                </td>
            </tr>
            </table>
        </div>
        
        <table class="sTable">
        <tr>
            <td>
                <div class="progress">
                    <div id="processing"></div>
                </div>
            </td>
            <td class="counter">
                <div id="loading_percent">0%</div>
            </td>
        </tr>
        </table>
    </div>
</div>

<!-- update interface end -->
