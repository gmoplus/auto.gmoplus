<?php


/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : MASSMAILER_NEWSLETTER.INC.PHP
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

use Flynax\Utils\Valid;

/* ext js action */

if ($_GET['q'] == 'ext') {
    /* system config */
    require_once '../../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require_once RL_LIBS . 'system.lib.php';

    /* date update */
    if ($_GET['action'] == 'update') {
        $reefless->loadClass('Actions');

        $type  = Valid::escape($_GET['type']);
        $field = Valid::escape($_GET['field']);
        $value = Valid::escape($_GET['value']);
        $id    = (int) $_GET['id'];
        $key   = Valid::escape($_GET['key']);

        $updateData = array(
            'fields' => array(
                $field => $value,
            ),
            'where' => array(
                'ID' => $id,
            ),
        );

        $rlDb->updateOne($updateData, 'massmailer');
        exit;
    }

    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];

    $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM `{db_prefix}massmailer` ";
    $sql .= "WHERE `Status` <> 'trash' LIMIT {$start}, {$limit}";
    $data = $rlDb->getAll($sql);

    foreach ($data as $key => $value) {
        $data[$key]['Status'] = $GLOBALS['lang'][$data[$key]['Status']];
    }

    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
} elseif ($_GET['q'] == 'ext2') {
    /* system config */
    require_once '../../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require_once RL_LIBS . 'system.lib.php';

    /* date update */
    if ($_GET['action'] == 'update') {
        $reefless->loadClass('Actions');

        $type  = Valid::escape($_GET['type']);
        $field = Valid::escape($_GET['field']);
        $value = Valid::escape($_GET['value']);
        $id    = (int) $_GET['id'];
        $key   = Valid::escape($_GET['key']);

        $updateData = array(
            'fields' => array(
                $field => $value,
            ),
            'where' => array(
                'ID' => $id,
            ),
        );

        $rlDb->updateOne($updateData, 'subscribers');
        exit;
    }

    $limit  = (int) $_GET['limit'];
    $start  = (int) $_GET['start'];
    $name   = Valid::escape($_GET['name']);
    $mail   = Valid::escape($_GET['email']);
    $acType = Valid::escape($_GET['account_type']);
    $from   = Valid::escape($_GET['date_from']);
    $to     = Valid::escape($_GET['date_to']);

    $filter_fields = array('name', 'email', 'subscribed_from', 'account_type', 'date_from', 'date_to');

    if ($name) {
        $subscribers_filters = "LOWER(`Name`) LIKE '%{$name}%' AND ";
        $accounts_filters    = "(LOWER(`First_name`) LIKE '%{$name}";
        $accounts_filters    .= "%' OR LOWER(`Username`) LIKE '%{$name}%') AND ";
        $contacts_filters    = "LOWER(`Name`) LIKE '%{$name}%' AND ";
    }

    if ($mail) {
        $subscribers_filters .= "`Mail` LIKE '%{$mail}%' AND ";
        $accounts_filters    .= "`Mail` LIKE '%{$mail}%' AND ";
        $contacts_filters    .= "`Email` LIKE '%{$mail}%' AND ";
    }

    if ($acType && $_GET['subscribed_from'] == 2) {
        $accounts_filters .= "`Type` = '{$acType}' AND ";
    }

    if ($from) {
        $subscribers_filters .= "UNIX_TIMESTAMP(`Date`) > UNIX_TIMESTAMP('{$from}') AND ";
        $accounts_filters    .= "UNIX_TIMESTAMP(`Date`) > UNIX_TIMESTAMP('{$from}') AND ";
        $contacts_filters    .= "UNIX_TIMESTAMP(`Date`) > UNIX_TIMESTAMP('{$from}') AND ";
    }

    if (!$to) {
        $subscribers_filters .= substr($subscribers_filters, 0, -4);
        $accounts_filters    .= substr($accounts_filters, 0, -4);
        $contacts_filters    .= substr($contacts_filters, 0, -4);
    }

    if ($to) {
        $subscribers_filters .= "UNIX_TIMESTAMP(`Date`) < UNIX_TIMESTAMP('{$to}') ";
        $accounts_filters    .= "UNIX_TIMESTAMP(`Date`) < UNIX_TIMESTAMP('{$to}') ";
        $contacts_filters    .= "UNIX_TIMESTAMP(`Date`) < UNIX_TIMESTAMP('{$to}') ";
    }

    if (!empty($subscribers_filters)) {
        $subscribers_filters = 'WHERE ' . $subscribers_filters;
    }

    if (!empty($accounts_filters)) {
        $accounts_filters = 'AND ' . $accounts_filters;
    }

    if (!empty($contacts_filters)) {
        $contacts_filters = "AND " . $contacts_filters;
    }

    if ($_GET['subscribed_from'] == 1) {
        $sql = "SELECT SQL_CALC_FOUND_ROWS `ID`, `Name`, `Mail`, `Date`, '' AS `Type`, ";
        $sql .= "'Subscribers' as `subscribe_type`, `Status` FROM `{db_prefix}subscribers` ";
        $sql .= "{$subscribers_filters} LIMIT {$start}, {$limit}";
    } elseif ($_GET['subscribed_from'] == 2) {
        $sql = "SELECT SQL_CALC_FOUND_ROWS `ID`, IF(`First_name` OR `Last_name`, ";
        $sql .= "CONCAT(`First_name`,' ', `Last_name`), `Username`) AS `Name`, `Mail`, `Date`, `Type`, ";
        $sql .= "'Accounts' as `subscribe_type`, `Status` FROM `{db_prefix}accounts` ";
        $sql .= "WHERE `Status` = 'active' AND `Subscribe` = '1' {$accounts_filters} LIMIT {$start}, {$limit}";
    } elseif ($_GET['subscribed_from'] == 3) {
        $sql = "SELECT SQL_CALC_FOUND_ROWS `ID`, `Name`, `Email` AS `Mail`, `Date`, '' AS `Type`, ";
        $sql .= "'Contacts' as `subscribe_type`, `Status` FROM `{db_prefix}contacts` ";
        $sql .= "WHERE `Subscribe` = '1' {$contacts_filters} LIMIT {$start}, {$limit}";
    } else {
        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM ((SELECT `ID`, `Name`, `Mail`, `Date`, '' AS `Type`, ";
        $sql .= "'Subscribers' as `subscribe_type`, `Status` FROM `{db_prefix}subscribers` ";
        $sql .= "{$subscribers_filters}) UNION (";
        $sql .= "SELECT `ID` AS `sid`, IF(`First_name` OR `Last_name`, ";
        $sql .= "CONCAT(`First_name`,' ', `Last_name`), `Username`) AS `Name`, `Mail`, ";
        $sql .= "SUBSTRING_INDEX(`Date`, ' ', 1) AS `Date`, `Type`, 'Accounts' as `subscribe_type`, `Status`";
        $sql .= "FROM `{db_prefix}accounts` WHERE `Status` = 'active' AND `Subscribe` = '1' ";
        $sql .= "{$accounts_filters}) UNION (SELECT `ID` AS `sid`, `Name`, `Email` AS `Mail`, ";
        $sql .= "SUBSTRING_INDEX(`Date`, ' ', 1) AS `Date`, '' AS `Type`, 'Contacts' as `subscribe_type`, `Status` ";
        $sql .= "FROM `{db_prefix}contacts` WHERE `Subscribe` = '1' {$contacts_filters})) AS `T1` ";
        $sql .= "LIMIT {$start}, {$limit}";
    }

    $data = $rlDb->getAll($sql);
    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    $sql = "SELECT * FROM ((SELECT `ID`,`Mail`, 'subscriber' FROM `{db_prefix}subscribers`) ";
    $sql .= "UNION (SELECT `ID`,`Mail`, 'account' FROM `{db_prefix}accounts` ";
    $sql .= "WHERE `Status` <> 'trash' AND `Subscribe` = '1') UNION (";
    $sql .= "SELECT `ID`,`Email` AS `Mail`, 'contact' FROM `{db_prefix}contacts` ";
    $sql .= "WHERE `Subscribe` = '1' )) AS `T1`";
    $emails = $rlDb->getAll($sql);

    $reefless->loadClass('MassmailerNewsletter', null, 'massmailer_newsletter');
    $emails = $rlMassmailerNewsletter->arrayValueRecursive('Mail', $emails);
    $duplicate_emails = $rlMassmailerNewsletter->returndup($emails);

    foreach ($data as $key => $value) {
        if ($value['subscribe_type'] == 'Accounts') {
            $data[$key]['ID'] = $data[$key]['ID'] . '_accounts';
        } elseif ($value['subscribe_type'] == 'Contacts') {
            $data[$key]['ID'] = $data[$key]['ID'] . '_contacts';
        }

        $data[$key]['Status'] = $GLOBALS['lang'][$data[$key]['Status']];
        if ($data[$key]['subscribe_type'] == 'Subscribers') {
            $data[$key]['From'] = $lang['massmailer_newsletter_newsletter'];
            $data[$key]['dev_subscriber'] = 'subscribers';
        } elseif ($data[$key]['subscribe_type'] == 'Accounts') {
            $data[$key]['From'] = $lang['accounts'] . ' (' . $lang['account_types+name+' . $data[$key]['Type']] . ')';
            $data[$key]['dev_subscriber'] = 'accounts';
        } else {
            $data[$key]['From'] = $lang['contacts'];
            $data[$key]['dev_subscriber'] = 'contacts';
        }

        $data[$key]['Name'] = ucwords($data[$key]['Name']);

        if (in_array($data[$key]['Mail'], $duplicate_emails)) {
            $data[$key]['Mail'] = "<b style='color: red;'>" . $data[$key]['Mail'] . "</b> - <em>";
            $data[$key]['Mail'] .= $lang['massmailer_newsletter_duplicate_email'] . '</em>';
        }
    }

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
} else {
    if ($_GET['action']) {
        switch ($_GET['action']) {
            case 'add':
                $bcAStep = $lang['massmailer_newsletter_add_massmailer'];
                break;
            case 'edit':
                $bcAStep = $lang['massmailer_newsletter_edit_massmailer'];
                break;
            case 'send':
                $bcAStep = $lang['massmailer_newsletter_send'];
                break;
        }
    } elseif ($_GET['page'] == 'newsletter') {
        $bcAStep = $lang['massmailer_newsletter_newsletter'];
    }

    if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
        /* get all languages */
        $allLangs = $GLOBALS['languages'];
        $rlSmarty->assign_by_ref('allLangs', $allLangs);

        if ($_GET['action'] == 'add' && !$_POST['fromPost']) {
            $reefless->loadClass('Mail');
            $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('massmailer_massmail_example');
            $_POST['subject'] = $mail_tpl['subject'];
            $_POST['body'] = $mail_tpl['body'];
        }

        $reefless->loadClass('MassmailerNewsletter', null, 'massmailer_newsletter');
        $acTypes = $rlMassmailerNewsletter->getCountTypeByLang('all');
        $total = $acTypes['total'];
        unset($acTypes['total']);

        $rlSmarty->assign_by_ref('account_types', $acTypes);

        $other_type_count['subscribers'] = $rlDb->getRow("
            SELECT COUNT(`ID`) AS 'count' FROM `{db_prefix}subscribers` 
            WHERE `Status` = 'active'
        ");
        $other_type_count['contacts'] = $rlDb->getRow("
            SELECT COUNT(DISTINCT `Email`) AS 'count' FROM `{db_prefix}contacts` 
            WHERE `Subscribe` = '1'
        ");
        $other_type_count['total_accounts'] = $total;

        $rlSmarty->assign_by_ref('other_type_count', $other_type_count);

        unset($l_email_variables[3]);

        if ($_GET['action'] == 'edit' && !$_POST['fromPost']) {

            $id = (int) $_GET['massmailer'];

            $massmailer = $rlDb->fetch(
                '*',
                array('ID' => $id),
                " AND `Status` <> 'trash'",
                1,
                'massmailer',
                'row'
            );

            $_POST['subject']              = $massmailer['Subject'];
            $_POST['body']                 = $massmailer['Body'];
            $_POST['type']                 = explode(',', $massmailer['Recipients_accounts']);
            $_POST['massmailer_key']       = $massmailer['Key'];
            $_POST['status']               = $massmailer['Status'];
            $_POST['site_accounts']        = $massmailer['Recipients_accounts'];
            $_POST['newsletters_accounts'] = $massmailer['Recipients_newsletter'];
            $_POST['contact_us']           = $massmailer['Recipients_contact_us'];
            $_POST['langSelect']           = $massmailer['Lang'];
        }

        if (isset($_POST['fromPost'])) {
            if ($_GET['action'] == 'add') {
                $massmailer_key = $rlValid->str2key($_POST['massmailer_key']);
                if (empty($massmailer_key)) {
                    $errors[] = str_replace('{field}', "<b>" . $lang['key'] . "</b>", $lang['notice_field_empty']);
                    $error_fields[] = 'massmailer_key';
                }
            }

            if (empty($_POST['subject'])) {
                $errors[] = str_replace(
                    '{field}',
                    "<b>" . $lang['massmailer_newsletter_subject'] . "</b>",
                    $lang['notice_field_empty']
                );
                $error_fields[] = 'subject';
            }


            if (empty($_POST['body'])) {
                $errors[] = str_replace(
                    '{field}',
                    "<b>" . $lang['massmailer_newsletter_body'] . "</b>",
                    $lang['notice_field_empty']
                );
            }

            if (!empty($errors)) {
                $rlSmarty->assign_by_ref('errors', $errors);
            } else {
                if ($_GET['action'] == 'add') {
                    $data = array(
                        'Key'                   => $massmailer_key,
                        'Status'                => $_POST['status'],
                        'Date'                  => 'NOW()',
                        'Subject'               => trim($_POST['subject']),
                        'Body'                  => trim($_POST['body']),
                        'Recipients_newsletter' => $_POST['newsletters_accounts'] ? 1 : 0,
                        'Recipients_accounts'   => implode(',', $_POST['type']),
                        'lang' => $_POST['lang'],
                        'Recipients_contact_us' => $_POST['contact_us'] ? 1 : 0,
                    );

                    $action = $rlDb->insertOne($data, 'massmailer');

                    $message = $lang['massmailer_newsletter_added'];
                    $aUrl = array("controller" => $controller);

                    if ($action) {
                        $reefless->loadClass('Notice');
                        $rlNotice->saveNotice($message);
                        $reefless->redirect($aUrl);
                    }
                } elseif ($_GET['action'] == 'edit') {
                    if (empty($_POST['subject'])) {
                        $errors[] = str_replace(
                            '{field}',
                            "<b>" . $lang['massmailer_newsletter_subject'] . "</b>",
                            $lang['notice_field_empty']
                        );
                    }

                    if (empty($_POST['body'])) {
                        $errors[] = str_replace(
                            '{field}',
                            "<b>" . $lang['massmailer_newsletter_body'] . "</b>",
                            $lang['notice_field_empty']
                        );
                    }

                    if (!empty($errors)) {
                        $rlSmarty->assign_by_ref('errors', $errors);
                    } else {
                        $id = (int) $_GET['massmailer'];

                        $update_data = array(
                            'fields' => array(
                                'Status'                => $_POST['status'],
                                'Date'                  => 'NOW()',
                                'Subject'               => trim($_POST['subject']),
                                'Body'                  => trim($_POST['body']),
                                'Recipients_newsletter' => $_POST['newsletters_accounts'] ? 1 : 0,
                                'Recipients_accounts'   => implode(',', $_POST['type']),
                                'Recipients_contact_us' => $_POST['contact_us'] ? 1 : 0,
                                'lang' => $_POST['lang']
                            ),
                            'where' => array('ID' => $id),
                        );

                        $action = $rlDb->updateOne($update_data, 'massmailer');

                        $message = $lang['massmailer_newsletter_edited'];
                        $aUrl = array("controller" => $controller);
                        if ($action) {
                            $reefless->loadClass('Notice');
                            $rlNotice->saveNotice($message);
                            $reefless->redirect($aUrl);
                        }
                    }
                }
            }
        }
    } elseif ($_GET['action'] == 'send') {
        $id = (int) $_GET['massmailer'];

        $massmailer = $rlDb->fetch(
            '*',
            array('ID' => $id),
            " AND `Status` <> 'trash'",
            1,
            'massmailer',
            'row'
        );

        if ($massmailer['Recipients_newsletter']) {
            $massmailer['Recipients_newsletter_count'] = $rlDb->getRow("
                SELECT COUNT(`ID`) AS `Count` FROM `{db_prefix}subscribers` 
                WHERE `Status` = 'active'
            ");
            $massmailer['Recipients_newsletter_emails'] = $rlDb->getAll("
                SELECT `Mail` FROM `{db_prefix}subscribers` 
                WHERE `Status` = 'active'
            ");
        }

        if ($massmailer['Recipients_contact_us']) {
            $massmailer['Recipients_contact_us_count'] = $rlDb->getRow("
                SELECT COUNT(DISTINCT `Email`) AS `Count` FROM `{db_prefix}contacts` 
                WHERE `Status` <> 'trash' AND `Subscribe` = '1'
            ");
            $massmailer['Recipients_contact_us_emails'] = $rlDb->getAll("
                SELECT `Email` AS `Mail` FROM `{db_prefix}contacts` 
                WHERE `Status` <> 'trash' AND `Subscribe` = '1'
            ");
        }

        // get accounts
        if (!empty($massmailer['Recipients_accounts'])) {
            $acTypes = explode(",", $massmailer['Recipients_accounts']);
            foreach ($acTypes as $key => $val) {
                $account_type[]['Key'] = $val;
                $sqlCount = "SELECT COUNT(`ID`) AS `Count` FROM `{db_prefix}accounts` 
                WHERE `Status` = 'active' AND `Type` = '{$val}' AND `Subscribe` = '1'";

                $sqlAccounts = "SELECT `Mail` FROM `{db_prefix}accounts` 
                WHERE `Status` = 'active' AND `Type` = '{$val}' AND `Subscribe` = '1'";
                if ($massmailer['Lang'] !== 'all') {
                    $sqlCount .= "AND `Lang` = '{$massmailer['Lang']}'";
                    $sqlAccounts .= "AND `Lang` = '{$massmailer['Lang']}'";
                }

                $count = $rlDb->getRow($sqlCount);
                $massmailer['Recipients_accounts_count'][$val]  = $count['Count'];
                $massmailer['Recipients_accounts_emails'][$val] = $rlDb->getAll($sqlAccounts);
            }
            $acTypes = $rlLang->replaceLangKeys($account_type, 'account_types', array('name'), RL_LANG_CODE, 'admin');

            $massmailer['Recipients_accounts'] = $acTypes;
        }
        $rlSmarty->assign_by_ref('massmailer_form', $massmailer);
    }
    /* register ajax methods */
    $reefless->loadClass('MassmailerNewsletter', null, 'massmailer_newsletter');

    $rlXajax->registerFunction(
        array('deleteMassmailerNewsletter', $rlMassmailerNewsletter, 'ajaxDeleteMassmailerNewsletter')
    );
    $rlXajax->registerFunction(array('deleteNewsletter', $rlMassmailerNewsletter, 'ajaxDeleteNewsletter'));
    $rlXajax->registerFunction(array('massmailerSave', $rlMassmailerNewsletter, 'ajaxMassmailerSave'));

    if ($_GET['page'] == 'newsletter') {
        $acTypes = $rlDb->getAll("
            SELECT `ID`, `Key` FROM `{db_prefix}account_types` 
            WHERE `Status` = 'active'
        ");

        foreach ($acTypes as $key => $val) {
            $acTypes[$key]['name'] = $lang['account_types+name+' . $val['Key']];
        }
        $rlSmarty->assign_by_ref('account_types', $acTypes);

        $rlXajax->registerFunction(array('deleteSubscriber', $rlMassmailerNewsletter, 'ajaxDeleteSubscrider'));
    }
}
