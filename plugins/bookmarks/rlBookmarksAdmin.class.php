<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLBOOKMARKSADMIN.CLASS.PHP
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
 * Bookmarks and share Admin Panel Class
 * 
 * @since 4.0.0
 */
class rlBookmarksAdmin
{
    /**
     * Get AddThis services list
     * @return array
     */
    public function getServices()
    {
        $services = array(
            "facebook" => array(
                "name" => "Facebook",
                "icon" => "facebook",
                "color" => "1877F2",
            ),
            "twitter" => array(
                "name" => "Twitter",
                "icon" => "twitter",
                "color" => "1D9BF0",
            ),
            "pinterest" => array(
                "name" => "Pinterest",
                "icon" => "pinterest",
                "color" => "BD081C",
            ),
            "email" => array(
                "name" => "Email",
                "icon" => "email",
                "color" => "0166FF",
            ),
            "tumblr" => array(
                "name" => "Tumblr",
                "icon" => "tumblr",
                "color" => "35465C",
            ),
            "reddit" => array(
                "name" => "Reddit",
                "icon" => "reddit",
                "color" => "ff4500",
            ),
            "linkedin" => array(
                "name" => "LinkedIn",
                "icon" => "linkedin",
                "color" => "007BB5",
            ),
            "whatsapp" => array(
                "name" => "WhatsApp",
                "icon" => "whatsapp",
                "color" => "12AF0A",
            ),
            "amazon_wish_list" => array(
                "name" => "Amazon Wish List",
                "icon" => "amazon",
                "color" => "F90",
            ),
            "aol_mail" => array(
                "name" => "AOL Mail",
                "icon" => "aol",
                "color" => "2A2A2A",
            ),
            "balatarin" => array(
                "name" => "Balatarin",
                "icon" => "balatarin",
                "color" => "079948",
            ),
            "bibsonomy" => array(
                "name" => "BibSonomy",
                "icon" => "bibsonomy",
                "color" => "2A2A2A",
            ),
            "bitty_browser" => array(
                "name" => "Bitty Browser",
                "icon" => "bitty",
                "color" => "999",
            ),
            "blogger" => array(
                "name" => "Blogger",
                "icon" => "blogger",
                "color" => "FDA352",
            ),
            "blogmarks" => array(
                "name" => "BlogMarks",
                "icon" => "blogmarks",
                "color" => "535353",
            ),
            "bookmarks_fr" => array(
                "name" => "Bookmarks.fr",
                "icon" => "bookmarks_fr",
                "color" => "96C044",
            ),
            "box_net" => array(
                "name" => "Box.net",
                "icon" => "box",
                "color" => "1A74B0",
            ),
            "buffer" => array(
                "name" => "Buffer",
                "icon" => "buffer",
                "color" => "2A2A2A",
            ),
            "copy_link" => array(
                "name" => "Copy Link",
                "icon" => "link",
                "color" => "0166FF",
            ),
            "diary_ru" => array(
                "name" => "Diary.Ru",
                "icon" => "diary_ru",
                "color" => "912D31",
            ),
            "diaspora" => array(
                "name" => "Diaspora",
                "icon" => "diaspora",
                "color" => "2E3436",
            ),
            "digg" => array(
                "name" => "Digg",
                "icon" => "digg",
                "color" => "2A2A2A",
            ),
            "diigo" => array(
                "name" => "Diigo",
                "icon" => "diigo",
                "color" => "4A8BCA",
            ),
            "douban" => array(
                "name" => "Douban",
                "icon" => "douban",
                "color" => "071",
            ),
            "draugiem" => array(
                "name" => "Draugiem",
                "icon" => "draugiem",
                "color" => "F60",
            ),
            "evernote" => array(
                "name" => "Evernote",
                "icon" => "evernote",
                "color" => "00A82D",
            ),
            "fark" => array(
                "name" => "Fark",
                "icon" => "fark",
                "color" => "555",
            ),
            "flipboard" => array(
                "name" => "Flipboard",
                "icon" => "flipboard",
                "color" => "C00",
            ),
            "folkd" => array(
                "name" => "Folkd",
                "icon" => "folkd",
                "color" => "0F70B2",
            ),
            "google_gmail" => array(
                "name" => "Gmail",
                "icon" => "gmail",
                "color" => "DD5347",
            ),
            "google_classroom" => array(
                "name" => "Google Classroom",
                "icon" => "google_classroom",
                "color" => "FFC112",
            ),
            "google_translate" => array(
                "name" => "Google Translate",
                "icon" => "google_translate",
                "color" => "528ff5",
            ),
            "hacker_news" => array(
                "name" => "Hacker News",
                "icon" => "y18",
                "color" => "F60",
            ),
            "hatena" => array(
                "name" => "Hatena",
                "icon" => "hatena",
                "color" => "00A6DB",
            ),
            "houzz" => array(
                "name" => "Houzz",
                "icon" => "houzz",
                "color" => "7AC143",
            ),
            "instapaper" => array(
                "name" => "Instapaper",
                "icon" => "instapaper",
                "color" => "2A2A2A",
            ),
            "kakao" => array(
                "name" => "Kakao",
                "icon" => "kakao",
                "color" => "FCB700",
            ),
            "known" => array(
                "name" => "Known",
                "icon" => "known",
                "color" => "2A2A2A",
            ),
            "line" => array(
                "name" => "Line",
                "icon" => "line",
                "color" => "00C300",
            ),
            "livejournal" => array(
                "name" => "LiveJournal",
                "icon" => "livejournal",
                "color" => "113140",
            ),
            "mail_ru" => array(
                "name" => "Mail.Ru",
                "icon" => "mail_ru",
                "color" => "356FAC",
            ),
            "mastodon" => array(
                "name" => "Mastodon",
                "icon" => "mastodon",
                "color" => "2b90d9",
            ),
            "mendeley" => array(
                "name" => "Mendeley",
                "icon" => "mendeley",
                "color" => "A70805",
            ),
            "meneame" => array(
                "name" => "Meneame",
                "icon" => "meneame",
                "color" => "FF7D12",
            ),
            "facebook_messenger" => array(
                "name" => "Messenger",
                "icon" => "facebook_messenger",
                "color" => "0084FF",
            ),
            "mewe" => array(
                "name" => "MeWe",
                "icon" => "mewe",
                "color" => "007DA1",
            ),
            "mixi" => array(
                "name" => "Mixi",
                "icon" => "mixi",
                "color" => "D1AD5A",
            ),
            "myspace" => array(
                "name" => "MySpace",
                "icon" => "myspace",
                "color" => "2A2A2A",
            ),
            "odnoklassniki" => array(
                "name" => "Odnoklassniki",
                "icon" => "odnoklassniki",
                "color" => "F2720C",
            ),
            "outlook_com" => array(
                "name" => "Outlook.com",
                "icon" => "outlook_com",
                "color" => "0072C6",
            ),
            "papaly" => array(
                "name" => "Papaly",
                "icon" => "papaly",
                "color" => "3AC0F6",
            ),
            "pinboard" => array(
                "name" => "Pinboard",
                "icon" => "pinboard",
                "color" => "1341DE",
            ),
            "plurk" => array(
                "name" => "Plurk",
                "icon" => "plurk",
                "color" => "CF682F",
            ),
            "pocket" => array(
                "name" => "Pocket",
                "icon" => "pocket",
                "color" => "EE4056",
            ),
            "print" => array(
                "name" => "Print",
                "icon" => "print",
                "color" => "0166FF",
            ),
            "printfriendly" => array(
                "name" => "PrintFriendly",
                "icon" => "printfriendly",
                "color" => "6D9F00",
            ),
            "pusha" => array(
                "name" => "Pusha",
                "icon" => "pusha",
                "color" => "0072B8",
            ),
            "kindle_it" => array(
                "name" => "Push to Kindle",
                "icon" => "kindle",
                "color" => "2A2A2A",
            ),
            "qzone" => array(
                "name" => "Qzone",
                "icon" => "qzone",
                "color" => "2B82D9",
            ),
            "rediff" => array(
                "name" => "Rediff MyPage",
                "icon" => "rediff",
                "color" => "D20000",
            ),
            "refind" => array(
                "name" => "Refind",
                "icon" => "refind",
                "color" => "1492ef",
            ),
            "sina_weibo" => array(
                "name" => "Sina Weibo",
                "icon" => "sina_weibo",
                "color" => "E6162D",
            ),
            "sitejot" => array(
                "name" => "SiteJot",
                "icon" => "sitejot",
                "color" => "FFC808",
            ),
            "skype" => array(
                "name" => "Skype",
                "icon" => "skype",
                "color" => "00AFF0",
            ),
            "slashdot" => array(
                "name" => "Slashdot",
                "icon" => "slashdot",
                "color" => "004242",
            ),
            "sms" => array(
                "name" => "SMS",
                "icon" => "sms",
                "color" => "6CBE45",
            ),
            "snapchat" => array(
                "name" => "Snapchat",
                "icon" => "snapchat",
                "color" => "2A2A2A",
            ),
            "stocktwits" => array(
                "name" => "StockTwits",
                "icon" => "stocktwits",
                "color" => "40576F",
            ),
            "svejo" => array(
                "name" => "Svejo",
                "icon" => "svejo",
                "color" => "5BD428",
            ),
            "symbaloo_bookmarks" => array(
                "name" => "Symbaloo Bookmarks",
                "icon" => "symbaloo",
                "color" => "6DA8F7",
            ),
            "microsoft_teams" => array(
                "name" => "Teams",
                "icon" => "microsoft_teams",
                "color" => "5059C9",
            ),
            "telegram" => array(
                "name" => "Telegram",
                "icon" => "telegram",
                "color" => "2CA5E0",
            ),
            "threema" => array(
                "name" => "Threema",
                "icon" => "threema",
                "color" => "2A2A2A",
            ),
            "trello" => array(
                "name" => "Trello",
                "icon" => "trello",
                "color" => "0079BF",
            ),
            "twiddla" => array(
                "name" => "Twiddla",
                "icon" => "twiddla",
                "color" => "2A2A2A",
            ),
            "typepad_post" => array(
                "name" => "TypePad Post",
                "icon" => "typepad",
                "color" => "D2DE61",
            ),
            "viber" => array(
                "name" => "Viber",
                "icon" => "viber",
                "color" => "7C529E",
            ),
            "vk" => array(
                "name" => "VK",
                "icon" => "vk",
                "color" => "587EA3",
            ),
            "wechat" => array(
                "name" => "WeChat",
                "icon" => "wechat",
                "color" => "7BB32E",
            ),
            "wordpress" => array(
                "name" => "WordPress",
                "icon" => "wordpress",
                "color" => "464646",
            ),
            "wykop" => array(
                "name" => "Wykop",
                "icon" => "wykop",
                "color" => "367DA9",
            ),
            "x" => array(
                "name" => "X",
                "icon" => "x",
                "color" => "2A2A2A",
            ),
            "xing" => array(
                "name" => "XING",
                "icon" => "xing",
                "color" => "165B66",
            ),
            "yahoo_mail" => array(
                "name" => "Yahoo Mail",
                "icon" => "yahoo",
                "color" => "400090",
            ),
            "yummly" => array(
                "name" => "Yummly",
                "icon" => "yummly",
                "color" => "E16120",
            ),
        );

        // Add 'addtoany' service
        $addtoany = ['dd' => [
            'name' => 'AddToAny',
            'icon' => 'dd',
            'color' => '00f',
        ]];

        return $addtoany + $services;
    }

    /**
     * @deprecated 5.0.0
     */
    public function checkServices()
    {}

    /**
     * Generate box SMARTY content
     *
     * @since 5.0.0 - $key parameter added
     * @since 5.0.0 - The 5th parameter $share_type changed to $counter
     * @since 5.0.0 - The 6th parameter $share_style removed
     * @since 5.0.0 - The second parameter $service_type removed
     *
     * @param  string $type         - box type: "floating_bar" or "inline"
     * @param  string $services     - services to use separated by comma
     * @param  string $button_size  - button size: "small", "middle" or "large"
     * @param  string $counter      - show counters
     * @param  string $theme        - floating bar theme: "light", "dark" or "transparent"
     * @param  string $align        - inline box items align: "left", "center" or "right"
     * @param  string $key          - Bookmark key
     * @return string               - SMARTY content
     */
    public function generateContent($type, $services, $button_size, $counter, $theme, $align = 'left', $key = '')
    {
        return '{include 
            file=$smarty.const.RL_PLUGINS|cat:$smarty.const.RL_DS|cat:"bookmarks"|cat:$smarty.const.RL_DS|cat:"'. $type . '.tpl"
            services="' . $services . '"
            button_size="' . $button_size . '"
            counter="' . $counter . '"
            theme="' . $theme . '"
            align="' . $align . '"
            key="' . $key . '"
        }';
    }

    /**
     * Delete bookmarks box by entry ID
     *
     * @package ajax
     * @param  string $id - box entry ID
     * @return array      - query response
     */
    public function delete($id)
    {
        // Check admin session expire
        if ($GLOBALS['reefless']->checkSessionExpire() === false) {
            return $this->getFailResponse();
        }

        global $rlDb;

        $id = (int) $id;

        if (!$id) {
            $msg = 'Unable to delete selected sharing box, no ID parameter specified.';

            $GLOBALS['rlDebug']->logger('Bookmarks plugins: ' . $msg);

            return array(
                'status'  => 'ERROR',
                'message' => $msg,
            );
        }

        $key = $rlDb->getOne('Key', "`ID` = {$id}", 'bookmarks');

        // Remove bookmark entry
        $sql = "DELETE FROM `" . RL_DBPREFIX . "bookmarks` WHERE `ID` = {$id} LIMIT 1";
        $rlDb->query($sql);

        // Remove box entry
        $sql = "DELETE FROM `" . RL_DBPREFIX . "blocks` WHERE `Key` = '{$key}' LIMIT 1";
        $rlDb->query($sql);

        // Remove box related phrases
        $sql = "DELETE FROM `" . RL_DBPREFIX . "lang_keys` WHERE `Key` = 'blocks+name+{$key}'";
        $rlDb->query($sql);

        return array(
            'status'  => 'OK',
            'message' => $GLOBALS['lang']['block_deleted'],
        );
    }

    /**
     * Fetch data for grid
     *
     * @package ajax
     * @return array - response data with bookmarks details
     */
    public function fetch()
    {
        global $rlDb, $lang;

        $limit = (int) $_GET['limit'] ?: 20;
        $start = (int) $_GET['start'] ?: 0;

        $sql = "
            SELECT SQL_CALC_FOUND_ROWS `T2`.`Key`, `T2`.`Status`, `T2`.`Tpl`,
            `T2`.`Header`, `T2`.`Page_ID`, `T2`.`Sticky`, `T1`.`Type`, `T1`.`Align`,
            `T1`.`ID`
            FROM `" . RL_DBPREFIX . "bookmarks` AS `T1`
            LEFT JOIN `" . RL_DBPREFIX . "blocks` AS `T2` ON `T1`.`Key` = `T2`.`Key`
            ORDER BY `T2`.`ID` ASC
            LIMIT {$start}, {$limit}
        ";
        $data = $rlDb->getAll($sql);

        foreach ($data as &$item) {
            if ($item['Type'] == 'inline') {
                if ($item['Header'] || in_array($item['Key'], ['bookmark_details', 'bookmark_done_step'])) {
                    $item['Name'] = $GLOBALS['rlLang']->getPhrase('blocks+name+' . $item['Key'], RL_LANG_CODE, false, true);
                } else {
                    $item['Name'] = $lang['bsh_inline'] . ' ' . $item['ID'];
                }
            } else {
                $item['Name'] = $lang['bsh_floating_bar'] . ' ' . $item['ID'];
            }
            $item['Status'] = $lang[$item['Status']];
            $item['Type_name'] = $lang['bsh_' . $item['Type']];
            $item['Align'] = $lang['bookmark_' . $item['Align']];
            $item['Tpl'] = $item['Tpl'] ? $lang['yes'] : $lang['no'];
            $item['Header'] = $item['Header'] ? $lang['yes'] : $lang['no'];

            $page_names = array();
            if (!$item['Sticky'] && $item['Page_ID']) {
                $sql = "
                    SELECT `Key` FROM `" . RL_DBPREFIX . "pages`
                    WHERE FIND_IN_SET(`ID`, '{$item['Page_ID']}') > 0
                ";
                foreach ($rlDb->getAll($sql) as $page) {
                    $page_names[] = $lang['pages+name+' . $page['Key']];
                }
            }
            $item['Pages'] = $item['Sticky']
            ? $lang['sticky']
            : implode(', ', $page_names);
        }

        return array(
            'total' => $rlDb->getRow("SELECT FOUND_ROWS() AS `count`", 'count'),
            'data' => $data,
        );
    }

    /**
     * Update entry through the grid
     *
     * @package ajax
     * @param  string $id    - entry id
     * @param  string $field - field to edit
     * @param  string $value - new value
     * @return array         - query response
     */
    public function update($id, $field, $value)
    {
        global $rlActions, $reefless, $rlDb, $rlValid;

        $field = $rlValid->xSql($field);
        $value = $rlValid->xSql($value);
        $id    = (int) $id;
        
        if (!$id) {
            $msg = 'Unable to update selected sharing box, no ID parameter specified.';

            $GLOBALS['rlDebug']->logger('Bookmarks plugins: ' . $msg);

            return array(
                'status'  => 'ERROR',
                'message' => $msg,
            );
        }

        // Update block content
        $bookmark = $rlDb->fetch('*', array('ID' => $id), null, 1, 'bookmarks', 'row');

        switch ($field){
            case 'Align':
                // Update related box
                $updateBlock = array(
                    'fields' => array(
                        'Content' => $this->generateContent(
                            $bookmark['Type'],
                            $bookmark['Services'],
                            $bookmark['View_mode'],
                            $bookmark['Counter'],
                            $bookmark['Theme'],
                            $value,
                            $bookmark['Key']
                        )
                    ),
                    'where' => array(
                        'Key' => $bookmark['Key']
                    )
                );
                $rlActions->updateOne($updateBlock, 'blocks');

                // Update bookmark entry
                $updateData = array(
                    'fields' => array(
                        $field => $value
                    ),
                    'where' => array(
                        'ID' => $id
                    )
                );
                $rlActions->updateOne($updateData, 'bookmarks');
                break;

            case 'Status':
            case 'Tpl':
                $updateBlock = array(
                    'fields' => array(
                        $field => $value
                    ),
                    'where' => array(
                        'Key' => $bookmark['Key']
                    )
                );
                $rlActions->updateOne($updateBlock, 'blocks');
                break;
        }

        return array('status' => 'OK');
    }

    /**
     * Get expired session response data
     * 
     * @return array - response data
     */
    public function getFailResponse()
    {
        $redirect = RL_URL_HOME . ADMIN . '/index.php';
        $redirect .= empty($_SERVER['QUERY_STRING']) 
                ? '?session_expired'
                :'?' . $_SERVER['QUERY_STRING'] . '&session_expired';

        return array(
            'status'   => 'ERROR',
            'redirect' => $redirect
        );
    }

    /**
     * Handle admin panel ajax queries
     * 
     * @hook apAjaxRequest
     */
    public function hookApAjaxRequest(&$out, $item)
    {
        // Support for version less then 4.6.0
        if (!func_get_arg(1)) {
            global $out, $item;
        }

        if (!in_array($item, array('bookmarks_delete', 'bookmarks_fetch'))) {
            return;
        }

        switch ($item) {
            case 'bookmarks_delete':
                $out = $this->delete($_POST['id']);
                break;
            
            case 'bookmarks_fetch':
                if ($_GET['action'] == 'update') {
                    $out = $this->update($_GET['id'], $_GET['field'], $_GET['value']);
                } else {
                    $out = $this->fetch();
                }
                break;
        }
    }

    /**
     * Hide floating bar boxes from the grid of blocks
     *
     * @hook apExtBlocksSql
     */
    public function hookApExtBlocksSql()
    {
        global $sql;

        $sql = preg_replace(
            '/(LIMIT\s[0-9]+)/',
            "AND (`T1`.`Plugin` != 'bookmarks' OR (`T1`.`Plugin` = 'bookmarks' AND `T1`.`Key` IN ('bookmark_twitter_timeline','bookmark_fb_box'))) $1",
            $sql
        );
    }

    /**
     * Simulate inline box name in grid
     *
     * @hook apExtBlocksData
     */
    public function hookApExtBlocksData()
    {
        global $data, $lang, $rlDb;

        foreach ($data as $index => $block) {
            if (strpos($block['Key'], 'bookmark_inline_') === 0 
                && $block['Header'] == $lang['no']
            ) {
                $data[$index]['name'] = $lang['bsh_inline'] . ' ' . str_replace('bookmark_inline_', '', $block['Key']);
            }
        }
    }

    /**
     * Change controller for bookmarks blocks
     *
     * @hook apTplBlocksGrid
     */
    public function hookApTplBlocksGrid()
    {
        $out = <<< JAVASCRIPT
            var instance = blocksGrid.getInstance();
            var index = instance.columns.length-1;
            var renderer = instance.columns[index].renderer;
            instance.columns[index].renderer = function(data, ext, row){
                if (row.data.Key.indexOf('bookmark_inline_') === 0) {
                    var original_controller = controller;
                    controller = 'bookmarks';
                }
                data = renderer.call(this, data);
                if (row.data.Key.indexOf('bookmark_inline_') === 0) {
                    controller = original_controller;
                }

                return data;
            }
JAVASCRIPT;

        echo $out;
    }

    /**
     * Remove "integrated_banner" and "header_banner" box positions from the 
     * grid cell for plugin rows
     *
     * @hook apTplBlocksBottom
     */
    public function hookApTplBlocksBottom()
    {
        $out = <<< JAVASCRIPT
        $(function(){
            var removed = false;

            blocksGrid.grid.addListener('beforeedit', function(editEvent){
                if (editEvent.field == 'Header') {
                    if (editEvent.record.data.Key.indexOf('bookmark_inline_') === 0) {
                        editEvent.cancel = true;
                        blocksGrid.store.rejectChanges();
                    }
                } else if (editEvent.field == 'Side') {
                    var column = editEvent.grid.colModel.columns[2];

                    if (editEvent.record.data.Key.indexOf('bookmark_inline_') === 0) {
                        var items = column.editor.getStore().data.items;
                        var items_ids = [];
                        for (var i = 0; i < items.length; i++) {
                            if (['integrated_banner', 'header_banner'].indexOf(items[i].data.field1) >= 0) {
                                items_ids.push(i);
                            }
                        }

                        if (items_ids.length) {
                            for (var i in items_ids.reverse()) {
                                column.editor.getStore().removeAt(items_ids[i])
                            }

                            removed = true;
                        }
                    } else {
                        if (removed) {
                            column.editor = new Ext.form.ComboBox({
                                store: block_sides,
                                displayField: 'value',
                                valueField: 'key',
                                typeAhead: true,
                                mode: 'local',
                                triggerAction: 'all',
                                selectOnFocus: true
                            });
                            removed = false;
                        }
                    }
                }
            });
        });
JAVASCRIPT;

        echo "<script>{$out}</script>";
    }
}
