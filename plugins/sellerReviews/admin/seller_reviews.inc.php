<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : SELLER_REVIEWS.INC.PHP
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

use Flynax\Plugins\SellerReviews\SellerComment;
use Flynax\Utils\Valid;

require __DIR__ . '/../vendor/autoload.php';

if ($_GET['q'] === 'ext') {
    require '../../../includes/config.inc.php';
    require RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require RL_LIBS . 'system.lib.php';

    $sellerComment = new SellerComment();

    if ($_GET['action'] === 'update' && (string) $_GET['field'] === 'Status') {
        $sellerComment->updateCommentStatus((int) $_GET['id'], (string) $_GET['value']);
    } else {
        $sellerComment->apGetExtJsComments();
    }
} else {
    if ($_GET['action'] === 'edit' && ($commentID = (int) $_GET['id'])) {
        $GLOBALS['bcAStep'] = $GLOBALS['lang']['edit'];

        $statuses = [SellerComment::ACTIVE_STATUS, SellerComment::APPROVAL_STATUS];

        $sellerComment = new SellerComment();
        $comment = $sellerComment->getCommentInfo($commentID);

        if (!isset($_POST['submit'])) {
            $_POST['title']       = $comment['Title'];
            $_POST['description'] = $comment['Description'];
            $_POST['account']     = $comment['Account']['Full_name'];
            $_POST['author']      = $comment['Author_ID'] ? $comment['Author']['Full_name'] : $comment['Author_Name'];
            $_POST['status']      = $comment['Status'];
        } else {
            $lang        = $GLOBALS['lang'];
            $errors      = [];
            $error_fields = [];

            if (empty($title = Valid::escape($_POST['title']))) {
                $errors[]      = str_replace('{field}', "<b>{$lang['title']}</b>", $lang['notice_field_empty']);
                $error_fields[] = 'title';
            }

            if (empty($description = Valid::escape($_POST['description']))) {
                $errors[]      = str_replace('{field}', "<b>{$lang['description']}</b>", $lang['notice_field_empty']);
                $error_fields[] = 'description';
            }

            if ($errors) {
                $GLOBALS['rlSmarty']->assign('errors', $errors);
            } else if ($sellerComment->updateComment($commentID, $_POST)) {
                $GLOBALS['reefless']->loadClass('Notice');
                $GLOBALS['rlNotice']->saveNotice($lang['item_edited']);
                $GLOBALS['reefless']->redirect(['controller' => $GLOBALS['controller']]);
            }
        }
    } else {
        $statuses = SellerComment::getCommentStatuses();
    }

    $GLOBALS['rlSmarty']->assign('srr_statuses', $statuses);
}
