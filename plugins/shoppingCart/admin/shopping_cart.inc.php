<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: SHOPPING_CART.INC.PHP
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

use \Flynax\Utils\Valid;
use \Flynax\Utils\Util;
use \ShoppingCart\Admin\Auction;
use \ShoppingCart\Admin\Configs;
use \ShoppingCart\Admin\Shipping;
use \ShoppingCart\Admin\ShippingFields;
use \ShoppingCart\Admin\Shopping;
use \ShoppingCart\Escrow;
use \ShoppingCart\Shipping as UserShipping;


if (isset($_GET['q'])) {
    require_once '../../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require_once RL_LIBS . 'system.lib.php';

    $reefless->loadClass('ShoppingCart', false, 'shoppingCart');

    $start = (int) $_GET['start'];
    $limit = (int) $_GET['limit'];
    $sort = Valid::escape($_GET['sort']);
    $sortDir = Valid::escape($_GET['dir']);

    if ($_GET['action'] == 'update') {
        $field = Valid::escape($_GET['field']);
        $value = Valid::escape(nl2br($_GET['value']));
        $id = (int) $_GET['id'];

        $updateData = array(
            'fields' => array(
                $field => $value,
            ),
            'where' => array(
                'ID' => $id,
            ),
        );
    }

    $reefless->loadClass('Mail');

    switch ($_GET['q']) {
        case 'ext':
            $shopping = new Shopping();

            // update order
            if ($_GET['action'] == 'update') {
                if ($field == 'Escrow_status') {
                    if (!is_object($rlSmarty)) {
                        require_once RL_LIBS . 'smarty' . RL_DS . 'Smarty.class.php';
                        $reefless->loadClass('Smarty');
                    }

                    $reefless->loadClass('Listings');
                    $reefless->loadClass('Account');
                    $reefless->loadClass('Payment');

                    $rlPayment->setOption('service', 'shopping');

                    $sql = "SELECT * FROM `{db_prefix}shc_orders` WHERE `ID` = {$id}";
                    $itemInfo = $rlDb->getRow($sql);

                    $lang = array_merge($lang, $rlShoppingCart->getPhrases(['shopping_cart']));
                    $escrow = new Escrow();

                    $escrow->makeAction('confirm', $id, $itemInfo['Buyer_ID']);
                }

                if ($field == 'Shipping_status') {
                    $shipping = new UserShipping();
                    $shipping->changeStatus($id, $value);
                } else {
                    $rlDb->updateOne($updateData, 'shc_orders');
                }
                exit;
            }

            if (!isset($lang['shc_processing'])) {
                $lang = array_merge($lang, $rlShoppingCart->getPhrases(['shopping_cart']));
            }

            $output = $shopping->getOrders($start, $limit);
            break;

        case 'ext_auction':
            $reefless->loadClass('Listings');
            $auction = new Auction();

            if (!isset($lang['shc_progress'])) {
                $lang = array_merge($lang, $rlShoppingCart->getPhrases('shopping_cart'));
            }

            // update order
            if ($_GET['action'] == 'update') {
                $rlDb->updateOne($updateData, 'shc_orders');
                exit;
            }

            $output = $auction->getOrders($start, $limit);
            break;

        case 'ext_bids':
            $id = (int) $_GET['item_id'];
            $auction = new Auction();

            // update order
            if ($_GET['action'] == 'update') {
                $rlDb->updateOne($updateData, 'shc_bids');
                exit;
            }

            $output = $auction->getBids($id, $start, $limit);
            break;

        case 'ext_shipping_fields':
            $shipping_fields = new ShippingFields();

            // update order
            if ($_GET['action'] == 'update') {
                $rlDb->updateOne($updateData, 'shc_shipping_fields');
                exit;
            }

            $output = $shipping_fields->getFields($start, $limit);
            break;

        case 'ext_shipping_methods':
            $shipping = new Shipping();

            // update order
            if ($_GET['action'] == 'update') {
                $rlDb->updateOne($updateData, 'shc_shipping_methods');

                $shipping->controlConfigs();
                exit;
            }

            $output = $shipping->getMethods($start, $limit, $sort, $sortDir);
            break;
    }

    echo json_encode($output);
} else {
    $reefless->loadClass('Listings');
    $reefless->loadClass('ListingTypes');
    $reefless->loadClass('Notice');
    $reefless->loadClass('Payment');
    $reefless->loadClass('Account');
    $reefless->loadClass('ShoppingCart', false, 'shoppingCart');

    $pluginPath = RL_PLUGINS . RL_DS . 'shoppingCart' . RL_DS . 'admin' . RL_DS . 'view' . RL_DS;
    $rlSmarty->assign_by_ref('pluginPath', $pluginPath);

    $lang = array_merge($lang, $rlShoppingCart->getPhrases(['add_listing', 'shopping_cart', 'my_shopping_cart']));

    $module = $_GET['module'];

    if ($config['shc_update_listings'] > 0 && $module != 'update_listings') {
        $url = RL_URL_HOME . ADMIN . '/index.php?controller=shopping_cart&module=update_listings';

        $alert = preg_replace(
            '/(\[(\pL.*)\])(.*)?(\[(\pL.*)\])/u',
            "<a href=\"{$url}\">$2</a>$3<a href=\"javascript://\" class=\"cancel-update-listings\">$5</a>",
            $lang['shc_update_listings_notice']
        );
        $alert = str_replace('{count}', $config['shc_count_exists_listings'], $alert);
        $rlSmarty->assign_by_ref('alerts', $alert);
    }

    // The action needed only for update plugin to version 3.0.0
    $migrateData = new \ShoppingCart\Admin\MigrateData();
    $migrateData->init($module);

    switch ($module) {
        case 'configs':
            $bcAStep[0]['name'] = $lang['settings'];

            $configs = new Configs();

            if ($_POST['form'] == 'submit') {
                $configs->saveSettings();
            }
            $configs->prepareData();

            if (!isset($lang['config+name+shc_module'])) {
                $lang = array_merge($lang, $rlShoppingCart->getPhrases('settings'));
            }

            // prepare config names
            $shcLang = [];
            foreach ($lang as $lKey => $lVal) {
                $pos = strrpos($lKey, '+') + 1;
                if (substr_count($lKey, 'config+name+shc_') > 0) {
                    $shcLang[substr($lKey, $pos)] = $lVal;
                }
                if (substr_count($lKey, 'config+des+shc_') > 0) {
                    $shcLang[substr($lKey, $pos) . '_des'] = $lVal;
                }
            }

            $shipping = new \ShoppingCart\Shipping();
            $shipping->getShippingFields(true);

            $rlSmarty->assign_by_ref('shcLang', $shcLang);
            $rlSmarty->assign('multi_format_keys', $config['mf_format_keys'] ? explode('|', $config['mf_format_keys']) : []);
            $rlSmarty->assign('mf_form_prefix', 'f');
            break;

        case 'auction':
            $bcAStep[0] = array(
                'name' => $lang['shc_auction'],
                'Controller' => 'shopping_cart',
                'Vars' => '&module=auction',
            );

            $auction = new Auction();

            if (!isset($lang['shc_time_left'])) {
                $lang = array_merge($lang, $rlShoppingCart->getPhrases(['my_purchases', 'listing_details'], true));
            }

            if ($_GET['action'] == 'view') {
                $bcAStep[1] = array('name' => $lang['shc_auction_details']);

                $auction->apAuctionDetails();
            } else {
                $auction->apAuctionList();
            }
            break;

        case 'shipping_methods':
            $shipping = new Shipping();

            $bcAStep[0] = array(
                'name' => $lang['shc_shipping_methods'],
                'Controller' => 'shopping_cart',
                'Vars' => '&module=shipping_methods',
            );

            // get all languages
            $allLangs = $rlLang->getLanguagesList('all');
            $rlSmarty->assign_by_ref('allLangs', $allLangs);

            if ($_GET['action'] == 'edit') {
                $i_key = Valid::escape($_GET['item']);

                // get current field information
                $methodInfo = $rlDb->fetch(
                    '*',
                    array('Key' => $i_key),
                    "AND `Status` <> 'trash'",
                    null,
                    'shc_shipping_methods',
                    'row'
                );

                if ($_GET['action']) {
                    $bcAStep[1]['name'] = $lang['shipping_methods+name+' . $methodInfo['Key']];
                }

                if (empty($methodInfo)) {
                    $errors[] = $lang['notice_shipping_method_not_found'];
                } else {
                    if (!$_POST['fromPost']) {
                        $shipping->simulatePost($methodInfo);
                    }
                }

                if ($_POST['submit']) {
                    $result = $shipping->update($methodInfo, $_POST['f']);

                    if ($result) {
                        $message = $lang['shc_shipping_method_updated'];
                        $aUrl = ["controller" => $controller, 'module' => 'shipping_methods'];

                        $rlNotice->saveNotice($message);
                        $reefless->redirect($aUrl);
                    }
                }
            }

            break;

        case 'shipping_fields':
            $bcAStep[0] = array(
                'name' => $lang['shc_shipping_fields'],
                'Controller' => 'shopping_cart',
                'Vars' => '&module=shipping_fields',
            );

            // get all languages
            $allLangs = $rlLang->getLanguagesList('all');
            $rlSmarty->assign_by_ref('allLangs', $allLangs);
            $rlSmarty->assign('agreement_pages', Util::getMinorPages());

            // get all data formats
            $bind_data_formats = $rlDb->fetch(
                array('Key', 'ID'),
                array('Parent_ID' => '0', 'Status' => 'active'),
                "AND `Key` <> 'currency' ORDER BY `Position`",
                null,
                'data_formats'
            );
            $bind_data_formats = $rlLang->replaceLangKeys(
                $bind_data_formats,
                'data_formats',
                'name',
                RL_LANG_CODE,
                'admin'
            );

            $rlSmarty->assign_by_ref('data_formats', $bind_data_formats);

            $reefless->loadClass('Fields', 'admin');
            $rlFields->table = 'shc_shipping_fields';
            $rlFields->source_table = 'shc_orders';

            $shipping_fields = new ShippingFields();

            if ($_GET['action']) {
                $bcAStep[1]['name'] = $_GET['action'] == 'add' ? $lang['add_field'] : $lang['edit_field'];
            }

            if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
                $rlSmarty->assign('agreement_pages', Util::getMinorPages());

                if ($_GET['action'] == 'edit') {
                    $e_key = Valid::escape($_GET['field']);

                    // get current field information
                    $field_info = $rlDb->fetch(
                        '*',
                        array('Key' => $e_key),
                        "AND `Status` <> 'trash'",
                        null,
                        $rlFields->table,
                        'row'
                    );
                    $rlSmarty->assign_by_ref('field_info', $field_info);

                    if (empty($field_info)) {
                        $errors[] = $lang['notice_field_not_found'];
                    } else {
                        if (!$_POST['fromPost']) {
                            $rlFields->simulatePost($e_key, $field_info);
                            $_POST['google_autocomplete'] = $field_info['Google_autocomplete'];
                        }
                    }
                }

                if ($_POST['submit']) {
                    $errors = array();

                    $shipping_fields->validate();

                    if (!$errors) {
                        if ($_GET['action'] == 'add') {
                            $action = $rlFields->createField($f_type, $f_key, $allLangs);

                            if ($action) {
                                $field_id = $rlFields->addID;
                                $shipping_fields->assignAutocomplete($field_id, $_POST['shc_google_autocomplete']);
                                $aUrl = array("controller" => $controller, 'module' => 'shipping_fields', "action" => "add");
                                $message = $lang['field_added'];
                            }
                        } elseif ($_GET['action'] == 'edit') {
                            $action = $rlFields->editField($f_type, $e_key, $allLangs);

                            if ($action) {
                                $shipping_fields->assignAutocomplete($e_key, $_POST['shc_google_autocomplete'], true);
                                $aUrl = array("controller" => $controller, 'module' => 'shipping_fields');
                                $message = $lang['field_edited'];
                            }
                        }

                        if ($action) {
                            $rlNotice->saveNotice($message);
                            $reefless->redirect($aUrl);
                        }
                    }
                }
            }
            break;

        case 'shipping_form':
            $bcAStep[0] = array(
                'name' => $lang['shc_shipping_form'],
                'Controller' => 'shopping_cart',
                'Vars' => '&module=shipping_form',
            );

            $reefless->loadClass('Builder', 'admin');
            $rlXajax->registerFunction(array('buildForm', $rlBuilder, 'ajaxBuildForm'));

            // simulate category
            $rlSmarty->assign('category_info', array('ID' => 1));

            $rlBuilder->rlBuildTable = 'shc_shipping_form';
            $rlBuilder->rlBuildField = 'Field_ID';

            // get form fields
            $relations = $rlBuilder->getFormRelations(1, 'shc_shipping_fields');
            $rlSmarty->assign_by_ref('relations', $relations);

            foreach ($relations as $rKey => $rValue) {
                $no_groups[] = $relations[$rKey]['Key'];

                $f_fields = $relations[$rKey]['Fields'];

                if ($relations[$rKey]['Group_ID']) {
                    foreach ($f_fields as $fKey => $fValue) {
                        $no_fields[] = $f_fields[$fKey]['Key'];
                    }
                } else {
                    $no_fields[] = $relations[$rKey]['Fields']['Key'];
                }
            }

            $fields = $rlDb->fetch(array('ID', 'Key', 'Type', 'Status'), null, false, null, 'shc_shipping_fields');
            $fields = $rlLang->replaceLangKeys($fields, 'shc_shipping_fields', array('name'), RL_LANG_CODE, 'admin');

            // hide already using fields
            if (!empty($no_fields)) {
                foreach ($fields as $fKey => $fVal) {
                    if (false !== array_search($fields[$fKey]['Key'], $no_fields)) {
                        $fields[$fKey]['hidden'] = true;
                    }
                }
            }

            $rlSmarty->assign_by_ref('fields', $fields);
            break;

        case 'update_listings':
            $reefless->loadClass('Account');
            $reefless->loadClass('ListingTypes');

            $bcAStep[0] = array(
                'name' => $lang['shc_update_listings'],
                'Controller' => 'shopping_cart',
                'Vars' => '&module=update_listings',
            );

            if (!isset($lang['shc_duration'])) {
                $lang = array_merge($lang, $rlShoppingCart->getPhrases(['add_listing'], true));
            }

            if ($_GET['action'] == 'apply') {
                $updateListings = new \ShoppingCart\Admin\UpdateListings();
                $info = [
                    'total' => $updateListings->getTotal($_SESSION['updateListings']['data']),
                    'per_run' => 100,
                ];

                $_SESSION['updateListings']['info'] = $info;
                $rlSmarty->assign_by_ref('updateListings', $info);
            } else {
                if ($_POST) {
                    if (!$_POST['types']) {
                        $errors[] = str_replace('{field}', "<b>\"" . $lang['listing_type'] . "\"</b>", $lang['notice_select_empty']);
                    }

                    if (!$rlDb->columnExists($config['price_tag_field'], 'listings')) {
                        $errors[] = $lang['shc_price_field_not_exists'];
                    }

                    $settings = [
                        'types' => $_POST['types'],
                        'atypes' => $_POST['atypes'],
                        'data' => $_POST,
                    ];

                    if (!$errors) {
                        $_SESSION['updateListings'] = $settings;
                        $aUrl = array('controller' => $controller, 'module' => 'update_listings', 'action' => 'apply');
                        $reefless->redirect($aUrl);
                    }
                }
            }

            if (!isset($_POST['types'])) {
                $_POST['types'] = [];
            }

            if (!isset($_POST['atypes'])) {
                $_POST['atypes'] = [];
            }

            $account_types = $rlAccount->getAccountTypes();
            $rlSmarty->assign_by_ref('account_types', $account_types);
            $rlSmarty->assign_by_ref('listing_types', $rlListingTypes->types);
            break;

        default:
            if ($_GET['action'] == 'view') {
                $bcAStep[0]['name'] = $lang['view_details'];

                if (!isset($lang['shc_order_details'])) {
                    $lang = array_merge($lang, $rlShoppingCart->getPhrases(['my_purchases', 'listing_details', 'my_items_sold'], true));
                }

                $shopping = new Shopping();
                $shopping->apOrderDetails();
            }
            break;
    }
}
