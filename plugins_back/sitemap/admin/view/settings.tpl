<!-- Settings handler of the Sitemap plugin tpl -->

<script>{literal}
    let $sitemapRobotsField = $('[name="post_config[sm_robots_tag][value]"]');

    $(function() {
        sitemapRobotsFieldHandler($sitemapRobotsField.filter(':checked').val());

        $sitemapRobotsField.change(function() {
            sitemapRobotsFieldHandler($sitemapRobotsField.filter(':checked').val());
        });
    });

    /**
     * Enable/disable option "Tag value for category page without ads"
     */
    const sitemapRobotsFieldHandler = function(value) {
        let $noIndexTag = $('[name="post_config[sm_robots_noindex][value]"]');

        $noIndexTag[value === '0' ? 'addClass' : 'removeClass']('disabled');
        $noIndexTag.prop('disabled', value === '0' ? true : false);
    }
{/literal}</script>

<!-- Settings handler of the Sitemap plugin tpl end -->
