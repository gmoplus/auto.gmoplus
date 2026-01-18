<!-- migrate interface -->

<div class="x-hidden" id="statistic">
    <div class="x-window-header">{$lang.shc_migration_caption}</div>
    <div class="x-window-body" style="padding: 10px 15px;">
        <table class="updating">
        <tr>
            <td class="name">
                {$lang.total}:
            </td>
            <td class="value">
                <label id="total">{$shcMigrateFields.total}</label>
            </td>
        </tr>
        </table>
        
        <div id="dom_area">
            <table class="updating">
            <tr>
                <td class="name">
                    {$lang.total}:
                </td>
                <td class="value">
                    <label id="updating">1-{if $shcMigrateFields.per_run > $shcMigrateFields.total}{$shcMigrateFields.total}{else}{$shcMigrateFields.per_run}{/if}</label>
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

<!-- migrate interface end -->
