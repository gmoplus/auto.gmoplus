<!-- Search box in the SellerReviews plugin tpl -->

<div id="search" class="hide">
    {include file='blocks/m_block_start.tpl' block_caption=$lang.search}

    <table class="form srr_comment_filters">
        <tr>
            <td class="name w130">{$lang.account}</td>
            <td class="field">
                <input style="width: 223px;" type="text" id="Account" />
            </td>
        </tr>
        <tr>
            <td class="name w130">{$lang.srr_author}</td>
            <td class="field">
                <input style="width: 223px;" type="text" id="Author" />
            </td>
        </tr>
        <tr>
            <td class="name w130">{$lang.date}</td>
            <td class="field">
                <input style="width: 66px;" type="text" value="" id="Date_from" autocomplete="off" />
                <img class="divider" alt="" src="{$rlTplBase}img/blank.gif" />
                <input style="width: 66px;" type="text" value="" id="Date_to" autocomplete="off" />
            </td>
        </tr>
        <tr>
            <td class="name w130">{$lang.status}</td>
            <td class="field">
                <select id="Status" style="width: 234px;">
                    <option value="" selected="selected">- {$lang.all} -</option>
                    {foreach from=$srr_statuses item='status'}
                        <option value="{$status}">{$lang.$status}</option>
                    {/foreach}
                </select>
            </td>
        </tr>
        <tr>
            <td></td>
            <td class="field">
                <input id="search_button" type="submit" value="{$lang.search}" />
                <input type="button" value="{$lang.reset}" id="reset_filter_button" />
                <a class="cancel" href="javascript:" onclick="show('search')">{$lang.cancel}</a>
            </td>
        </tr>
    </table>

    {include file='blocks/m_block_end.tpl'}
</div>

<script>{literal}
    $(function () {
        let filterFields          = ['Account', 'Author', 'Status', 'Date_from', 'Date_to'],
            datepickerProperties = {
                showOn         : 'both',
                buttonImage    : `${rlUrlHome}img/blank.gif`,
                buttonText     : '{/literal}{$lang.dp_choose_date}{literal}',
                buttonImageOnly: true,
                dateFormat     : 'yy-mm-dd',
                changeMonth    : true,
                changeYear     : true,
                yearRange      : '-100:+30',
            };

        /**
         * @todo - Remove it when compatibility will be >= 4.8.0
         */
        if (typeof rlLang === 'undefined') {
            var rlLang = '{/literal}{$smarty.const.RL_LANG_CODE}{literal}';
        }

        $('#Date_from,#Date_to').datepicker(datepickerProperties).datepicker($.datepicker.regional[rlLang]);

        $('#Account').rlAutoComplete();
        $('#Author').rlAutoComplete();

        $('#search_button').click(function() {
            let filters = [], saveCookies = [];

            for (let i = 0; i < filterFields.length; i++) {
                let filterValue = $('#' + filterFields[i]).val();

                if (filterValue) {
                    filters.push([filterFields[i], filterValue]);
                    saveCookies.push(filterFields[i] + '||' + filterValue);
                }
            }

            createCookie('srr_comment_filters', saveCookies, 1);
            filters.push(['srr_search', 1]);

            srrCommentsGrid.filters = filters;
            srrCommentsGrid.reload();
        });

        $('#reset_filter_button').click(function() {
            eraseCookie('srr_comment_filters');
            srrCommentsGrid.reset();

            $("#search select option[value='']").attr('selected', true);
            $("#search input[type=text]").val('');
        });

    });
{/literal}</script>

<!-- Search box in the SellerReviews plugin tpl end -->
