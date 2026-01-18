<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: COMMENTSCONTROLLER.PHP
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
 *  Flynax Classifieds Software 2025 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

namespace Flynax\Api\Http\Controllers\V1;

use Illuminate\Http\Request;

class CommentsController extends BaseController
{
    public function __construct()
    {
        rl('reefless')->loadClass('Comment', null, 'comment');
    }

    /**
     * Get comments
     *
     */
    public function getComments($listingID, $page = 0)
    {
        $comments = [];

        $comments['comments'] = rl('Comment')->getComments($listingID, $page);
        $comments['total'] = rl('Comment')->calc;
        
        return $comments;
    }

    /**
     * Get comments
     *
     */
    public function getCommentsNext()
    {
        if ((new AccountController)->issetAccount($_GET['account_id'], $_GET['account_password'])) {
            $listingID = $_GET['listing_id'];
            $page = $_GET['page'];
            $response = $this->getComments($listingID, $page);
        }
        
        return $response;
    }

    /**
     * Post add comment
     *
     */
    public function addComment()
    {
            $account_id = $_POST['account_id'];
            $listingID = $_POST['listing_id'];
            $author = $_POST['author'];
            $title = $_POST['title'];
            $message = $_POST['message'];
            $rating = round(($GLOBALS['config']['comments_stars_number'] * (int) $_POST['rating']) / 5);
            $status = $GLOBALS['config']['comment_auto_approval'] ? 'active' : 'pending';
            $insert_comment = array(
                'User_ID' => $account_id,
                'Listing_ID' => $listingID,
                'Author' => $author,
                'Title' => $title,
                'Description' => $message,
                'Rating' => $rating,
                'Status' => $status,
                'Date' => 'NOW()',
            );

            if (rl('Db')->insertOne($insert_comment, 'comments')) {
                $response['status'] = 'ok';
                // /* increase count */
                if ($GLOBALS['config']['comment_auto_approval']) {
                    rl('Db')->query("UPDATE `{db_prefix}listings` SET `comments_count` = `comments_count` + 1 WHERE `ID` = {$listingID} LIMIT 1");
                    $response['msg_key'] = 'notice_comment_added';
                }
                else {
                    $response['msg_key'] = 'notice_comment_added_approval';
                }

                if ($GLOBALS['config']['comments_send_email_after_added_comment']) {

                    $mail_tpl = rl('Mail')->getEmailTemplate('comment_email');

                    $listing_info = rl('Listings')->getListing($listingID);
                    $listing_info['listing_title'] = rl('Listings')->getListingTitle(
                        $listing_info['Category_ID'],
                        $listing_info,
                        $listing_info['Listing_type'],
                        null,
                        $listing_info['Parent_IDs']
                    );
                    $listing_info['url'] = rl('reefless')->getListingUrl($listing_info);
                    $link = $listing_info['url'] . '#comments';
                    $link = '<a href="' . $link . '">' . $listing_info['listing_title'] . '</a>';

                    $account_info = rl('Account')->getProfile((int) $listing_info['Account_ID']);                    
                    $message = nl2br($message);

                    $mail_tpl['body'] = str_replace(
                        array('{name}', '{author}', '{title}', '{message}', '{listing_title}'),
                        array($account_info['Full_name'], $author, $title, $message, $link),
                        $mail_tpl['body']
                    );
                    rl('Mail')->send($mail_tpl, $account_info['Mail']);
                }
            }
        return $response;
    }
}
