<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLRSSFEED.CLASS.PHP
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2022
 *	https://www.flynax.com
 *
 ******************************************************************************/

class rlRssFeed
{
   /**
    * box content pattern
    */
    var $box_content;

   /**
    * Class constructor
    */
    public function __construct()
    {
        $this->box_content = <<< VS
global \$rlSmarty;

\$rss_feed = <<< FL
{data}
FL;
\$rlSmarty -> assign('rss_feed', unserialize(\$rss_feed));
\$rlSmarty -> display(RL_PLUGINS .'rssfeed'. RL_DS . 'block.tpl');
VS;
    }


    /**
    * Get RSS feed content
    *
    * @param string $url
    * @param int $number
    *
    * @return array - Rss data
    */
    public function get($url = '', $number = 3 )
    {
        if (!$url) {
            return false;
        }
        $GLOBALS['reefless']->loadClass('Rss');

        $pos = array_search('description', $GLOBALS['rlRss']->items);
        unset($GLOBALS['rlRss']->items[$pos]);

        $GLOBALS['rlRss']->items_number = $number;

        $GLOBALS['rlRss']->createParser($GLOBALS['reefless']->getPageContent($url));
        $rss_feed = $GLOBALS['rlRss']->getRssContent();
        unset($GLOBALS['rlRss']);

        return $rss_feed;
    }

   /**
    * Validate url
    *
    * @package xAjax
    *
    * @param string $url - Validate rss url and build sample content
    *
    * @todo build sample content
    *
    * @return xajaxResponse $_response
    */
    public function ajaxValidate($url = false)
    {
        global $_response, $lang;

        $rss_feed = $this->get($url, 3);

        if ($rss_feed) {
            $GLOBALS['rlSmarty']->assign_by_ref('rss_feed', $rss_feed);

            $tpl = RL_PLUGINS .'rssfeed'. RL_DS .'admin'. RL_DS .'sample.tpl';
            $_response->assign('feed_sample', 'innerHTML', $GLOBALS['rlSmarty']->fetch($tpl, null, null, false));

            $_response->script("$('#rssfeed_url input[type=text]').removeClass('error');");
        }
        else {
            $_response->script("
                printMessage('error', '{$lang['rssfeed_empty_feed']}');
                $('#rssfeed_url input[type=text]').addClass('error');
                $('#feed_sample').html('');
            ");
        }

        $_response->script("$('#rssfeed_url input[type=button]').val('{$lang['rssfeed_validate']}');");

        return $_response;
    }

   /**
    * Delete Rss
    *
    * @package xAjax
    *
    * @param int $id - Rss feed id
    *
    *  @return string
    */
    public function ajaxDeleteRss($id = false )
    {
        global $_response, $lang, $rlDb;

        $id = (int)$id;

        // check admin session expire
        if ($GLOBALS['reefless']->checkSessionExpire() === false)
        {
            $_response->redirect(RL_URL_HOME . ADMIN . '/index.php?action=session_expired');
            return $_response;
        }

        if (!$id)
        {
            return $_response;
        }

        // delete rss feed
        $rlDb->query("DELETE FROM `" . RL_DBPREFIX . "rss_feed` WHERE `ID` = '{$id}' LIMIT 1");
        $rlDb->query("DELETE FROM `" . RL_DBPREFIX . "blocks` WHERE `Key` = 'rssfeed_{$id}' LIMIT 1");
        $rlDb->query("DELETE FROM `" . RL_DBPREFIX . "lang_keys` WHERE `Key` = 'blocks+name+rssfeed_{$id}'");

        $_response->script("
            printMessage('notice', '{$lang['item_deleted']}');
            rssFeedGrid.reload();
        ");

        return $_response;
    }

    /**
     * @hook cronAdditional
     * @since 2.1.5
     */
    public function hookCronAdditional()
    {
        $this->updateFeeds();
    }

    /**
    * Update all feeds
    *
    * @return  void
    */
    public function updateFeeds()
    {
        global $rlDb;

        $rlDb->setTable('rss_feed');
        $feeds = $rlDb->fetch(array('ID', 'Url', 'Article_num'), array('Status' => 'active'), "AND NOW() >= DATE_ADD(`Last_update`, INTERVAL `Update_delay` HOUR) OR UNIX_TIMESTAMP(`Last_update`) IS NULL || UNIX_TIMESTAMP(`Last_update`) = 0");

        $rlDb->rlAllowHTML = true;

        foreach ($feeds as $item) {
            $content = $this->get($item['Url'], $item['Article_num']);
            if ($content) {
                $rss_feed_key = 'rssfeed_'. $item['ID'];

                /* update related block content */
                $new_content = str_replace(
                    array('{data}'),
                    array(serialize($content)),
                    $this->box_content
                );

                $update_block = array(
                    'fields' => array('Content' => $new_content, 'Type' => 'php'),
                    'where' => array('Key' => $rss_feed_key)
                );

                $rlDb->updateOne($update_block, 'blocks');

                /* update last date check of the feed */
                $update_feed = array(
                    'fields' => array('Last_update' => 'NOW()'),
                    'where' => array('ID' => $item['ID'])
                );

               $rlDb->updateOne($update_feed, 'rss_feed');
            }
        }

        $rlDb-> resetTable();
    }
}
