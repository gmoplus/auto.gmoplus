
/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : _PAGINATION.JS
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2023
 *	https://www.flynax.com
 *
 ******************************************************************************/

/**
 * Paging transit handler
 *
 * @param $pagination - Base UL container of the pagination
 */
let flPaginationHandler = function($pagination) {
    $pagination.find('li.transit input').on('focus', function() {
        $(this).select();
    }).keypress(function(event) {
        // Enter key pressed
        if (event.keyCode === 13) {
            let page     = Number($(this).val()),
                $transit = $pagination.find('li.transit'),
                info     = $transit.find('input[name=stats]').val().split('|');

            if (page > 0 && page !== Number(info[0]) && page <= Number(info[1])) {
                if (page === 1) {
                    location.href = $transit.find('input[name=first]').val();
                }
                else {
                    location.href = $transit.find('input[name=pattern]').val().replace('[pg]', page);
                }
            }
        }
    });
}
