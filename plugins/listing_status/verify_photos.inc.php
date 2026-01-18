<?php

$listing_id = (int)$_GET['id'];

/* get listing info */
$sql = "SELECT `T1`.*, `T2`.`Cross` AS `Plan_crossed`, `T2`.`Key` AS `Plan_key`, `T3`.`Type` AS `Listing_type` ";
$sql .= "FROM `{db_prefix}listings` AS `T1` ";
$sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
$sql .= "LEFT JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";
$sql .= "WHERE `T1`.`ID` = '{$listing_id}' LIMIT 1";

$listing = $rlDb->getRow($sql);

// replace verified description
$date = date('Y-m-d');
$web = rtrim(str_replace(['http://', 'https://'], '', RL_URL_HOME), '/');
$lang['ls_verified_desc'] = str_replace(['[website]', '[date]'], [$web, $date], $lang['ls_verified_desc']);

/* get listing form */
if ( isset($listing_id) )
{
    $l_type = $rlListingTypes->types[$listing['Listing_type']];
    
    if (in_array('verified', explode(',', $listing['Sub_status_data']))) {
        $reefless->loadClass('Notice');
        $rlNotice->saveNotice($lang['ls_request_approved']);

        $my_key_page = $config['one_my_listings_page'] ? 'my_all_ads' : $l_type['My_key'];
        \Flynax\Utils\Util::redirect($reefless->getPageUrl($my_key_page));
    }

    /* get current listing category information */
    $category = $rlCategories->getCategory($listing['Category_ID']);
    $rlSmarty->assign_by_ref('category', $category);

    $listing['url'] = $reefless->getListingUrl($listing);
    $listing['listing_title'] = $rlListings->getListingTitle($listing['Category_ID'], $listing, $listing['Listing_type']);

    $rlSmarty->assign_by_ref('listing', $listing);

    $verified_data = $rlDb->fetch('*', array('Listing_ID' => $listing_id), null, null, 'listing_status_verified', 'row');
    if (in_array('wait', explode(',', $listing['Sub_status_data']))) {
        $verified_data['pending'] = true;
        $page_info['name'] = $page_info['title'] = $lang['ls_verified_details'];
    }
    $rlSmarty->assign_by_ref('verified_data', $verified_data);

    /* add bread crumbs item */
    $my_page_key = $config['one_my_listings_page'] ? 'my_all_ads' : 'my_' . $l_type['Key'];
    $bc_last = array_pop($bread_crumbs);
    $bread_crumbs[] = array(
        'name'  => $lang['pages+name+' . $my_page_key],
        'title' => $lang['pages+title+' . $my_page_key],
        'path'  => $pages[$my_page_key],
    );
    $bread_crumbs[] = $bc_last;
    
    if ($_POST['submit']) {
        $reefless->loadClass('ListingStatus', null, 'listing_status');

        $description = $_POST['description'];
        $image = $_FILES['image'];

        $ext = array_reverse(explode('.', $image['name']));
        if (empty($image) || !$rlValid->isImage($ext[0]) || !$GLOBALS['rlListingStatus']->isImage($image['tmp_name'])) {
            $errors[] = str_replace('{field}', "<b>\"" . $lang['ls_image']. "\"</b>", $lang['notice_field_empty']);
            $error_fields[] = "image";
        }

        if (!$errors) {
            $image_name = 'verified_photo_' . $listing_id . '.' .$ext[0];

            $folder_name = RL_FILES . "verified_photos";
            $file = $folder_name . RL_DS . $image_name;
            $GLOBALS['reefless']->rlMkdir($folder_name);
            
            if (move_uploaded_file($image['tmp_name'], $file)) {
                $action = false;
                if ($item_id = $rlDb->getOne('ID', "`Listing_ID` = '{$listing_id}}'", 'listing_status_verified')) {
                    $update_data =  array(
                        'fields' => array(
                            'Listing_ID' => $listing_id,
                            'Description' => $description,
                            'Image' => $image_name,
                            'Date' => 'NOW()',
                        ),
                        'where' => array('Listing_ID' => $listing_id),
                    );
                    if ($rlDb->updateOne($update_data, 'listing_status_verified')) {
                        $action = true;
                    }
                }
                else {
                    $insert = array(
                        'Listing_ID' => $listing_id,
                        'Description' => $description,
                        'Image' => $image_name,
                        'Date' => 'NOW()',
                    );
                    if ($rlDb->insertOne($insert, 'listing_status_verified')) {
                        $item_id = $rlDb->insertID();
                        $action = true;
                    }
                }

                if ($action) {
                    $labels_array = array_flip(explode(',', $listing['Sub_status_data']));

                    if ($labels_array['verified']) {
                        unset($labels_array['verified']);
                    }
                   
                    $labels_array['wait'] = 1;
                    $labels_array = array_flip($labels_array);
                    
                    // change listing status
                    $updateStatus = array(
                        'fields' => array(
                            'Sub_status_data' => implode(',',$labels_array),
                        ),
                        'where' => array(
                            'ID' => (int)$listing_id
                        )
                    );

                    $rlDb->updateOne($updateStatus, 'listings');

                    if ($rlDb->getOne('ID', "`Listing_ID` = '{$listing_id}}' AND `Type`='verify'", 'listing_status_data')) {
                        $update = array(
                            'fields' => array(
                                'Key' => 'wait',
                            ), 
                            'where' => array(
                                'Type' => 'verify',
                                'Listing_ID' => $listing_id
                            ), 
                        );
                        $rlDb->updateOne($update, 'listing_status_data');
                    }
                    else {
                        $insertData = array(
                            'Listing_ID' => $listing_id,
                            'Type'       => 'verify', 
                            'Key'        => 'wait', 
                            'Date'       => 'NOW()', 
                        );
                        $rlDb->insertOne($insertData, 'listing_status_data');
                    }

                    // send notify to admin
                    $reefless->loadClass('Mail');

                    // send admin notification
                    $mailTpl = $rlMail->getEmailTemplate('verified_photos_wait_admin');

                    $link = RL_URL_HOME . ADMIN . '/index.php?controller=listing_status&amp;module=verify_photos&amp;action=view&amp;id=' . $item_id;

                    $mFind = array('{listing_id}', '{title}');
                    $mReplace = array(
                        $listing_id,
                        $listing['listing_title'],
                    );
                    $mailTpl['body'] = str_replace($mFind, $mReplace, $mailTpl['body']);
                    $mailTpl['body'] = preg_replace('/\[(.*)\]/', '<a href="' . $link . '">$1</a>', $mailTpl['body']);

                    $rlMail->send($mailTpl, $config['notifications_email']);

                    $reefless->loadClass('Notice');
                    $rlNotice->saveNotice($lang['ls_wait_request']);
                    
                    $url = $reefless->getPageUrl('verify_photos', null, null, 'id=' . $listing_id);
                    $reefless->redirect(null, $url);
                }
            }
        }
    }
}
