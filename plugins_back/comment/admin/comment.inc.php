<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: RLCOMMENT.CLASS.PHP
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

/* ext js action */
if ($_GET['q'] == 'ext') {
    /* system config */
    require_once('../../../includes/config.inc.php');
    require_once(RL_ADMIN_CONTROL . 'ext_header.inc.php');
    require_once(RL_LIBS . 'system.lib.php');

    /* date update */
    if ($_GET['action'] == 'update') {
        $type = $rlValid->xSql($_GET['type']);
        $field = $rlValid->xSql($_GET['field']);
        $value = $rlValid->xSql(nl2br($_GET['value']));
        $id = $rlValid->xSql($_GET['id']);
        $key = $rlValid->xSql($_GET['key']);

        $updateData = array(
            'fields' => array(
                $field => $value
            ),
            'where' => array(
                'ID' => $id
            )
        );

        $current_status = $rlDb->getOne('Status', "`ID` = '{$id}'", 'comments');

        $rlDb->updateOne($updateData, 'comments');

        if ($field == 'Status' && $value != $current_status) {
            $listing_id = (int) $rlDb->getOne('Listing_ID', "`ID` = '{$id}'", 'comments');
            $sign = $value == 'active' ? '+' : '-';

            $reefless->loadClass('Comment', null, 'comment');
            $rating = $rlComment->getListingRating($listing_id);

            $rlDb->query("
                UPDATE `{db_prefix}listings`
                SET `comments_count` = `comments_count` {$sign} 1, `comments_rating` = {$rating}
                WHERE `ID` = '{$listing_id}' LIMIT 1
            ");
        }

        if ($field == 'Status' && $value != $current_status) {
            $reefless->loadClass('Comment', null, 'comment');
            $rlComment->updateBox();
        }

        exit;
    }

    /* data read */
    $limit = $rlValid->xSql($_GET['limit']);
    $start = $rlValid->xSql($_GET['start']);
    $search = $_GET['search'];
    $listing_type = $_GET['listing_type'];
    $date_from = $_GET['date_from'];
    $date_to = $_GET['date_to'];
    $status = $_GET['search_status'];

    $where = '1 ';

    if ($search) {
        if ($status) {
            $where .= "AND `T1`.`Status` = '{$status}' ";
        }
        if ($listing_type) {
            $where .= "AND `T3`.`Type` = '{$listing_type}' ";
        }
        if (!empty($date_from)) {
            $where .= "AND UNIX_TIMESTAMP(DATE(`T1`.`Date`)) >= UNIX_TIMESTAMP('{$date_from}') ";
        }
        if (!empty($date_to)) {
            $where .= "AND UNIX_TIMESTAMP(DATE(`T1`.`Date`)) <= UNIX_TIMESTAMP('{$date_to}') ";
        }
    }

    $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.`ID`, `T1`.`Title`, `T1`.`Author`, `T1`.`Date`, `T1`.`Status`, `T1`.`Rating` ";
    $sql .= ", `T3`.`Type` AS `Listing_type`";
    $sql .= "FROM `" . RL_DBPREFIX . "comments` AS `T1` ";
    $sql .= "LEFT JOIN `" . RL_DBPREFIX . "listings` AS `T2` ON `T1`.`Listing_ID` = `T2`.`ID` ";
    $sql .= "LEFT JOIN `" . RL_DBPREFIX . "categories` AS `T3` ON `T2`.`Category_ID` = `T3`.`ID` ";
    $sql .= "WHERE {$where} ";
    $sql .= "ORDER BY `ID` DESC ";
    $sql .= "LIMIT {$start}, {$limit}";
    $data = $rlDb->getAll($sql);

    foreach ($data as $key => $value) {
        $data[$key]['Status'] = $lang[$value['Status']];
        $data[$key]['Listing_type'] = $rlListingTypes->types[$value['Listing_type']]['name'];
    }

    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
} else {
    /* assign statuses */
    $statuses = array('active', 'approval', 'pending');
    $rlSmarty->assign_by_ref('statuses', $statuses);

    $reefless->loadClass('Comment', null, 'comment');

    /* additional bread crumb step */
    if ($_GET['action']) {
        switch ($_GET['action']) {
            case 'edit':
                $bcAStep = $lang['edit'];
                break;
        }
    }

    if ($_GET['action'] == 'edit') {
        $id = (int) $_GET['id'];
        $comment_info = $rlDb->fetch(array('Listing_ID', 'Title', 'Description', 'Author', 'Date', 'Status'), array('ID' => $id), "AND `Status` <> 'trash'", 1, 'comments', 'row');

        if ($_GET['action'] == 'edit' && !$_POST['fromPost']) {
            $_POST['title'] = $comment_info['Title'];
            $_POST['description'] = $comment_info['Description'];
            $_POST['author'] = $comment_info['Author'];
            $_POST['status'] = $comment_info['Status'];
        }

        if ($_GET['action'] == 'edit' && isset($_POST['submit'])) {
            $title = $_POST['title'];
            if (empty($title)) {
                $errors[] = str_replace('{field}', "<b>{$lang['title']}</b>", $lang['notice_field_empty']);
            }

            $description = $_POST['description'];
            if (empty($description)) {
                $errors[] = str_replace('{field}', "<b>{$lang['description']}</b>", $lang['notice_field_empty']);
            }

            if (!empty($errors)) {
                $rlSmarty->assign_by_ref('errors', $errors);
            } else {
                // decrease/increase comment count
                if ($_POST['status'] != $comment_info['Status']) {
                    $sign = $_POST['status'] == 'active' ? '+' : '-';
                    $rating = $rlComment->getListingRating((int) $comment_info['Listing_ID']);

                    $rlDb->query("
                        UPDATE `{db_prefix}listings`
                        SET `comments_count` = `comments_count` {$sign} 1, `comments_rating` = {$rating}
                        WHERE `ID` = '{$comment_info['Listing_ID']}' LIMIT 1
                    ");
                }

                $update_data = array(
                    'fields' => array(
                        'Title' => $_POST['title'],
                        'Description' => $_POST['description'],
                        'Status' => $_POST['status']
                    ),
                    'where' => array('ID' => $id)
                );
                $action = $rlActions->updateOne($update_data, 'comments');

                if ($_POST['status'] != $comment_info['Status']
                    || $_POST['title'] != $comment_info['Title']
                    || $_POST['Description'] != $comment_info['Description']
                ) {
                    $rlComment->updateBox();
                }

                if ($action) {
                    $aUrl = array("controller" => $controller);

                    $reefless->loadClass('Notice');
                    $rlNotice->saveNotice($lang['item_edited']);
                    $reefless->redirect($aUrl);
                }
            }
        }
    }
}
