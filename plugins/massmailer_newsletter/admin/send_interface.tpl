<!-- send interface -->

<div class="x-hidden" id="statistic">
    <div class="x-window-header">{$lang.massmailer_newsletter_processing}</div>
    <div class="x-window-body" style="padding: 10px 15px;">
        <table class="massmailer">
        <tr>
            <td class="name">
                {$lang.massmailer_newsletter_total}:
            </td>
            <td class="value">
                <b id="total">{$lang.massmailer_newsletter_processing}</b>
            </td>
        </tr>
        <tr>
            <td class="name">
                {$lang.massmailer_newsletter_sents}:
            </td>
            <td class="value">
                <b id="sent">0</b>
            </td>
        </tr>
        </table>
        
        <div id="sending_area">
            <table class="massmailer">
            <tr>
                <td class="name">
                    {$lang.massmailer_newsletter_sending}:
                </td>
                <td class="value">
                    <div id="sending" 
                        style="width: 450px; height: 200px; font-size: 11px;overflow-y: auto; display: table;">
                        <span style="display: table-cell; vertical-align: middle;">
                            {$lang.massmailer_newsletter_processing}
                        </span>
                    </div>
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

<!-- send interface end -->
