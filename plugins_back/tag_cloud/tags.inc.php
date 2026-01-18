<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: RLTAGCLOUD.CLASS.PHP
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2024 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

unset($blocks['tag_cloud']);

$path = $rlValid->xSql($config['mod_rewrite'] ? $_GET['nvar_1'] : $_GET['tag']);
$tag_info = $rlDb->fetch("*", array("Path" => $path, "Status" => "active"), null, null, 'tag_cloud', 'row');

if ($path && !$tag_info) {
    $sError = true;
} else {
    $reefless->loadClass('TagCloud', null, 'tag_cloud');

    if ($tag_info) {
        $rlTagCloud->ownRequest = true;
        foreach ($languages as $language) {
            $rlTagCloud->tagPages[] = $GLOBALS['reefless']->getPageUrl('tags', null, $language['Code']);
        }
        $rlTagCloud->ownRequest = false;
        $rlTagCloud->tagInfo = $tag_info;

        $tag_info = $rlLang->replaceLangKeys($tag_info, 'tag_cloud', array('title', 'des' ,'h1' ,'meta_description', 'meta_keywords'));

        $default_info = $rlLang->replaceLangKeys(array("Key" => "tags_defaults"), 'tag_cloud', array('title', 'h1','des', 'meta_description', 'meta_keywords'));

        if ($tag_info['meta_description']) {
            $page_info['meta_description'] = $tag_info['meta_description'];
        } else {
            $page_info['meta_description'] = str_replace('{tag}', $tag_info['Tag'], $default_info['meta_description']);
        }

        if ($tag_info['meta_keywords']) {
            $page_info['meta_keywords'] = $tag_info['meta_keywords'];
        } else {
            $page_info['meta_keywords'] = str_replace('{tag}', $tag_info['Tag'], $default_info['meta_keywords']);
        }

        if (!$tag_info['des']) {
            $tag_info['des'] = str_replace('{tag}', $tag_info['Tag'], $default_info['des']);
        }

        $rlSmarty->assign("tag_info", $tag_info);

        $reefless->loadClass('Search');

        $data['keyword_search'] = $tag_info['Tag'];
        $data['keyword_search_type'] = 2;

        $query = trim($data['keyword_search']);
        $query = preg_replace('/(\\s)\\1+/', ' ', $query);
        $query = str_replace('%', '', $query);

        $rlSmarty->assign('keyword_search', true);

        if (!empty($query)) {
            $pInfo['current'] = (int)$_GET['pg'];
            $rlSmarty->assign('keyword_mode', $data['keyword_search_type']);

            if ($pInfo['current'] > 1) {
                $_SESSION['tags_pageNum'] = $pInfo['current'];
            } else {
                unset($_SESSION['tags_pageNum']);
            }

            if (!$_POST) {
                $_POST['f'] = $_SESSION['tags_data'];
            }

            $rlSearch->fields['keyword_search'] = array(
                'Key' => 'keyword_search',
                'Type' => 'text'
            );

            $sorting = array(
                'type' => array(
                    'name' => $lang['listing_type'],
                    'field' => 'Listing_type',
                    'Key' => 'Listing_type',
                    'Type' => 'select'
                ),
                'category' => array(
                    'name' => $lang['category'],
                    'field' => 'Category_ID',
                    'Key' => 'Category_ID',
                    'Type' => 'select'
                ),
                'post_date' => array(
                    'name' => $lang['join_date'],
                    'field' => 'Date',
                    'Key' => 'Date'
                )
            );
            $rlSmarty->assign_by_ref('sorting', $sorting);

            /* define sort field */
            $sort_by = $_SESSION['tags_sort_by'] = empty($_REQUEST['sort_by']) ? $_SESSION['tags_sort_by'] : $_REQUEST['sort_by'];

            if (!empty($sorting[$sort_by])) {
                $data['sort_by'] = $sort_by;
                $rlSmarty->assign_by_ref('sort_by', $sort_by);
            }

            /* define sort type */
            $sort_type = $_SESSION['tags_sort_type'] = empty($_REQUEST['sort_type']) ? $_SESSION['tags_sort_type'] : $_REQUEST['sort_type'];
            if ($sort_type) {
                $data['sort_type'] = $sort_type = in_array($sort_type, array('asc', 'desc')) ? $sort_type : false;
                $rlSmarty->assign_by_ref('sort_type', $sort_type);
            }

            $rlSearch->fields = array_merge($rlSearch->fields, $sorting);

            $rlHook->load('keywordSearchData');

            /* get listings */
            $listings = $rlSearch->search($data, $tag_info['Type'], $pInfo['current'], $config['listings_per_page']);
            $rlSmarty->assign_by_ref('listings', $listings);

            $pInfo['calc'] = $rlSearch->calc;
            $rlSmarty->assign_by_ref('pInfo', $pInfo);
            if ($tag_info['h1']) {
                $page_info['name'] = str_replace(['{tag}','{number}'],
                [$tag_info['Tag'],$pInfo['calc']],
                $tag_info['h1']);         
            }else if ($default_info['h1']) {
                $page_info['name'] = str_replace(['{tag}','{number}'],
                [$tag_info['Tag'],$pInfo['calc']],
                $default_info['h1']);
            } else {
                $page_info['name'] = str_replace(
                    ['{number}', '{type}'],
                    [$pInfo['calc'], $lang['listings']],
                    $lang['listings_found']
                );
            }

             if ($tag_info['title']) {
                $page_info['title'] = str_replace('{tag}', $tag_info['Tag'], $tag_info['title']);
            } elseif ($default_info['title']) {
                $page_info['title'] = str_replace(
                    ['{tag}','{number}'],
                    [$tag_info['Tag'], $pInfo['calc']], 
                    $default_info['title']);
            } else {
                $page_info['name'] = str_replace(
                    ['{number}', '{type}'],
                    [$pInfo['calc'], $lang['listings']],
                    $lang['listings_found']
                );
            }

            $bread_crumbs[] = array(
                'name' => $tag_info['Tag']
            );
        }
    } else {
        $tag_cloud = $rlTagCloud->getTagCloud();
        $rlSmarty->assign('tag_cloud', $tag_cloud);
    }
}
