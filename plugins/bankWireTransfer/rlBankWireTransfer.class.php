<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLBANKWIRETRANSFER.CLASS.PHP
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

class rlBankWireTransfer
{
    /**
     * @hook tplFooter
     * @since 2.0.0
     */
    public function hookTplFooter()
    {
        if ($this->isConfigured()
            && $GLOBALS['page_info']['Controller'] == 'payment_history'
        ) {
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'bankWireTransfer/footer.tpl');
        }
    }

    /**
     * @hook pageinfoArea
     * @since 2.0.0
     */
    public function hookPageinfoArea()
    {
        global $deny_pages, $config, $lang;

        $GLOBALS['reefless']->loadClass('Payment');

        if (!$this->isConfigured() || !defined('IS_LOGIN')) {
            $deny_pages[] = 'bwt_requests';
            return;
        }

        if (!empty($config['shc_account_types'])) {
            $allowed_account_types = explode(",", $config['shc_account_types']);

            if (!in_array($GLOBALS['account_info']['Type'], $allowed_account_types)) {
                $deny_pages[] = 'bwt_requests';
            }
        }
        if (isset($_GET['completed']) && $GLOBALS['rlPayment']->getGateway() == 'bankWireTransfer') {
            $phraseKey = '';
            switch ($GLOBALS['page_info']['Controller']) {
                case 'profile':
                    $phraseKey = 'profile_upgrade_success';
                    break;

                case 'upgrade_listing':
                    $phraseKey = 'notice_payment_listing_completed';
                    break;

                case 'my_packages':
                    $phraseKey = 'notice_package_payment_completed';
                    break;

                case 'my_credits':
                    $phraseKey = 'paygc_payment_completed';
                    break;

                case 'invoices':
                    $phraseKey = 'invoices_payment_completed';
                    break;
            }
            if (!empty($phraseKey)) {
                $lang[$phraseKey] = $lang['bwt_after_payment'];
            }
        }
    }

    /**
     * @hook phpPaymentHistoryBottom
     * @since 2.0.0
     */
    public function hookPhpPaymentHistoryBottom()
    {
        global $transactions, $rlSmarty, $rlBankWireTransferGateway, $config, $lang;

        if (!$this->isConfigured()) {
            return;
        }

        $this->initGateway();

        // get payments details
        $payment_details = $rlBankWireTransferGateway->getPaymentDetails();
        $rlSmarty->assign_by_ref('payment_details', $payment_details);

        if ($transactions) {
            foreach ($transactions as $key => $val) {
                if ($val['Gateway'] != 'bankWireTransfer') {
                    continue;
                }
                if ($config['shc_method'] == 'multi'
                    && in_array($val['Service'], ['shopping', 'auction'])
                    && $val['Dealer_ID']
                ) {
                    $transactions[$key]['payment_details'] = $rlBankWireTransferGateway->getPaymentDetails((int) $val['Dealer_ID'], true);
                }
                $transactions[$key]['Gateway_key'] = 'bankWireTransfer';
                $transactions[$key]['Gateway'] = $lang['payment_gateways+name+bankWireTransfer'];
                $transactions[$key]['Doc_name'] = $val['Doc'] ? explode('/', $val['Doc'])[2] : '';
                $transactions[$key]['Doc_name'] = preg_replace('/\d+/', $val['ID'], $transactions[$key]['Doc_name']);
            }
        }
    }

    /**
     * @hook shoppingCartAccountSettings
     * @since 2.0.0
     */
    public function hookShoppingCartAccountSettings()
    {
        global $config;

        if (!$config['shc_module']) {
            return;
        }

        if ($config['shc_method'] == 'single') {
            $gateways = explode(',', $config['shc_payment_gateways']);

            if (!in_array('bankWireTransfer', $gateways)) {
                return;
            }
        }

        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'bankWireTransfer/account_settings.tpl');
    }

    /**
     * @hook getPhrase
     * @since 2.0.0
     */
    public function hookGetPhrase(&$params, &$phrase)
    {
        global $addListing;

        if ($addListing->step == 'done') {
            if ($params['key'] == 'done_phrase_key') {
                $phrase = $GLOBALS['lang']['bwt_after_payment'];
            }
        }
    }

    /**
     * @hook apTplTransactionsGrid
     * @since 2.0.0
     */
    public function hookApTplTransactionsGrid()
    {
        global $lang;

        $unpaid_phrase = $lang['ext_unpaid'];

        echo <<< FL
    var gridInstance = transactionsGrid.getInstance();
    gridInstance.fields.push({name: 'Doc', mapping: 'Doc'});
    var columns = [];
    var j = 0;
    for (var i = 0; i < gridInstance.columns.length; i++) {
        columns[j++] = gridInstance.columns[i];
        if (gridInstance.columns[i]['dataIndex'] == 'ID' && i > 0) {
            gridInstance.columns[i]['width'] = '80';
            gridInstance.columns[i]['renderer'] = function(data, obj, row) {
                if (row.data.GatewayKey == 'bankWireTransfer') {
                    var output = "<img class='view' ext:qtip='"+lang['ext_view']+"' src='"+rlUrlHome+"img/blank.gif' onClick='loadBWTTransaction(\""+data+"\")' />";
                    if (row.data.pStatus == '{$unpaid_phrase}') {
                        output += "<img class='activate' ext:qtip='"+lang['ext_activate']+"' src='"+rlUrlHome+"img/blank.gif' onClick='rlConfirm( \""+lang['ext_bwt_notice_activate']+"\", \"activateBWTTransaction\", \""+data+"\", \"load\" )' />";
                    }
                    output += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onClick='rlConfirm( \""+lang['ext_notice_'+delete_mod]+"\", \"xajax_deleteTransaction\", \""+data+"\", \"load\" )' />";
                } else {
                    var output = "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onClick='rlConfirm( \""+lang['ext_notice_'+delete_mod]+"\", \"xajax_deleteTransaction\", \""+data+"\", \"load\" )' />";
                }
                obj.style += 'text-align: center;';
                return output;
            };
        }
        if (gridInstance.columns[i]['dataIndex'] == 'pStatus') {
            gridInstance.columns[i]['renderer'] = function(data, obj, row) {
                if (row.data.GatewayKey == 'bankWireTransfer' && row.data.pStatus == '{$unpaid_phrase}') {
                    obj.style += 'background: #d7d7d7;';
                } else {
                    if (data == lang['ext_paid']) {
                        obj.style += 'background: #d2e798;';
                    } else if (data == lang['ext_unpaid']) {
                        obj.style += 'background: #fbc4c4;';
                    }
                }
                return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+data+'</span>';
            }
        }
        if (i == 8) {
            columns[j++] = {
                header: lang['bwt_doc'],
                dataIndex: 'Doc',
                width: 100,
                fixed: true,
                renderer: function(data, obj, row) {
                    if (row.data.GatewayKey == 'bankWireTransfer' && row.data.Doc != '{$lang['not_available']}' && row.data.pStatus == '{$unpaid_phrase}') {
                        obj.style += 'background: #f0b690;';
                    }
                    obj.style += 'text-align: center;';
                    return '<span>'+data+'</span>';
                }
            }
        }
    }

    gridInstance.columns = columns;
    gridInstance.fields.push({name: 'GatewayKey', mapping: 'GatewayKey'});
    gridInstance.fields.push({name: 'Doc', mapping: 'Doc'});
    transactionsGrid = new gridObj(gridInstance);
FL;
    }

    /**
     * @hook apAjaxRequest
     * @since 2.0.0
     */
    public function hookApAjaxRequest(&$out, $item)
    {
        global $config, $lang, $rlSmarty, $reefless;

        if ($item == 'loadBWTDetails') {
            $out = $this->loadBWTTransaction((int) $_REQUEST['id']);

            // Ext popup doesn't support json response
            echo $out;exit;
        }
        if ($item == 'activateBWTDetails') {
            require_once RL_LIBS . 'smarty' . RL_DS . 'Smarty.class.php';
            $reefless->loadClass('Smarty');
            $reefless->loadClass('Listings');

            $rlSmarty->assign_by_ref('config', $config);
            $rlSmarty->assign_by_ref('lang', $lang);

            $result = $this->activateBWTTransaction((int) $_REQUEST['id']);
            $out = array(
                'status' => $result ? 'OK' : 'ERROR',
                'message' => $GLOBALS['lang']['bwt_activate_' . ($result ? 'success' : 'error')],
            );
        }
    }

    /**
     * @hook apExtTransactionItem
     * @since 2.0.0
     */
    public function hookApExtTransactionItem(&$param1, &$param2, &$param3)
    {
        $param1['GatewayKey'] = $param3['Gateway'];
    }

    /**
     * @hook registrationDone
     * @since 2.0.0
     */
    public function hookRegistrationDone()
    {
        global $lang;

        if (self::isConfigured() && $GLOBALS['config']['membership_module']) {
            $lang['registration_complete_caption'] .= '<p>' . $lang['bwt_after_payment'] . '</p>';
        }
    }

    /**
     * @hook apTplTransactionsBottom
     * @since 2.0.0
     */
    public function hookApTplTransactionsBottom()
    {
        global $lang;

        echo <<< FL
            <script>
                var loadBWTTransaction = function(id) {
                    popupTxnInfo= new Ext.Window({
                        title: '{$lang['bwt_request_details']}',
                        autoLoad: {
                            url: rlConfig['ajax_url'],
                            scripts: true ,
                            params: {item: 'loadBWTDetails', id: id}
                        },
                        layout: 'fit',
                        width: 500,
                        height: 'auto',
                        plain: true,
                        modal: true,
                        closable: true,
                        y: 150,
                    });

                    popupTxnInfo.show();
                    flynax.slideTo('body');
                }

                var activateBWTTransaction = function(id) {
                    $.getJSON(rlConfig['ajax_url'], {item: 'activateBWTDetails', id: id}, function(response) {
                        if (response.status == 'OK') {
                            printMessage('notice', response.message);
                            transactionsGrid.reload();
                        } else {
                            printMessage('error', response.message);
                        }
                    });
                }

                $(document).ready(function(){
                    transactionsGrid.grid.addListener('beforeedit', function(editEvent) {
                        if (editEvent.field == 'pStatus' 
                            && editEvent.record.json.GatewayKey == 'bankWireTransfer' 
                            && editEvent.record.json.Status == lang['ext_unpaid']
                        ) {
                            editEvent.cancel = true;
                            transactionsGrid.store.rejectChanges();
                            Ext.MessageBox.minWidth = 300;
                            Ext.MessageBox.alert(
                                lang['warning'],
                                lang['bwt_click_activate']
                            );
                        }
                    });
                });
            </script>
FL;
    }

    /**
     * @hook apPhpGatewayUpdateSettings
     * @since 2.0.0
     */
    public function hookApPhpGatewayUpdateSettings(&$update, $key)
    {
        if ($key !== 'bankWireTransfer_payment_details') {
            return;
        }

        foreach ($_POST as $optionKey => $optionValue) {
            if (false === strpos($optionKey, 'bwt_payment_details_content_')) {
                continue;
            }

            $GLOBALS['rlDb']->updateOne([
                'fields' => ['Value' => $optionValue],
                'where' => [
                    'Key'  => 'bwt_payment_details_content',
                    'Code' => str_replace('bwt_payment_details_content_', '', $optionKey),
                ]
            ], 'lang_keys');
            unset($_POST[$optionKey]);
        }
    }

    /**
     * @hook apPaymentGatewaysSimulatePost
     * @since 2.0.0
     */
    public function hookApPaymentGatewaysSimulatePost(&$gatewayInfo, &$gatewaySettings, $key)
    {
        if ($key !== 'bankWireTransfer') {
            return;
        }

        foreach ($gatewaySettings as &$gatewaySetting) {
            if ($gatewaySetting['Key'] === 'bankWireTransfer_payment_details') {
                $gatewaySetting['Default'] = [];
                $phrases = $GLOBALS['rlDb']->fetch(
                    ['Code', 'Value'],
                    ['Key' => 'bwt_payment_details_content'],
                    null, null, 'lang_keys'
                );
                foreach ($phrases as $phrase) {
                    $gatewaySetting['Default'][$phrase['Code']] = $phrase['Value'];
                }
                break;
            }
        }
    }

    /**
     * @hook apPaymentGatewaysValidate
     * @since 3.1.0
     */
    public function hookApPaymentGatewaysValidate($errors, $i_key): void
    {
        global $gateway_settings;

        if ($i_key !== 'bankWireTransfer' || !$errors) {
            return;
        }

        foreach ($gateway_settings as &$gatewaySetting) {
            if ($gatewaySetting['Key'] === 'bankWireTransfer_payment_details') {
                $gatewaySetting['Default'] = [];

                foreach ($_POST as $optionKey => $optionValue) {
                    if (false === strpos($optionKey, 'bwt_payment_details_content_')) {
                        continue;
                    }

                    $gatewaySetting['Default'][str_replace('bwt_payment_details_content_', '', $optionKey)] = $optionValue;
                }
                break;
            }
        }
    }

    /**
     * check if plugin configured
     *
     */
    public function isConfigured()
    {
        if ($GLOBALS['rlPayment']->gateways['bankWireTransfer']['Status'] == 'active') {
            return true;
        }

        return false;
    }

    /**
     * Load BWT transaction details in HTML
     *
     * @param  int $txn_id
     * @return string
     */
    public function loadBWTTransaction($txn_id = 0)
    {
        global $rlDb, $rlSmarty, $lang, $config, $reefless;

        if (!is_object($rlSmarty)) {
            require_once RL_LIBS . 'smarty' . RL_DS . 'Smarty.class.php';
            $reefless->loadClass('Smarty');
        }

        $this->initGateway();

        $txn_info = $rlDb->fetch('*', array('ID' => $txn_id), null, 1, 'transactions', 'row');

        if ($txn_info) {
            $payment_details = $GLOBALS['rlBankWireTransferGateway']->getPaymentDetails($txn_info['Dealer_ID']);
            $rlSmarty->assign_by_ref('payment_details', $payment_details);

            if ($txn_info['Dealer_ID']) {
                $reefless->loadClass('Account');
                $txn_info['dealer'] = $GLOBALS['rlAccount']->getProfile((int) $txn_info['Dealer_ID']);
                $lang['shc_dealer'] = $GLOBALS['rlLang']->getPhrase('shc_dealer', null, null, true);
            }
        }

        $rlSmarty->assign_by_ref('config', $config);
        $rlSmarty->assign_by_ref('lang', $lang);
        $rlSmarty->assign_by_ref('txn_info', $txn_info);

        $file = RL_PLUGINS . 'bankWireTransfer' . RL_DS . 'txn_details.tpl';
        $out = $rlSmarty->fetch($file, null, null, false);

        return $out;
    }

    /**
     * Activate BWT transaction
     *
     * @param  int $txn_id
     * @return bool
     */
    public function activateBWTTransaction($txn_id = 0)
    {
        global $reefless;

        $this->initGateway();
        $reefless->loadClass('Payment');

        return $GLOBALS['rlBankWireTransferGateway']->callBack($txn_id);
    }

    /**
     * @hook boot
     * @since 2.1.0
     */
    public function hookBoot()
    {
        global $account_info, $page_info;

        if ($page_info['Controller'] == 'registration'
            && isset($account_info['Type'])
            && !$account_info['Type_ID']
        ) {
            $account_info['Type_ID'] = $GLOBALS['rlDb']->getOne(
                'ID',
                "`Key` = '{$account_info['Type']}'",
                'account_types'
            );
        }
    }

    /**
     * @hook ajaxRequest
     *
     * @since 2.1.0
     */
    public function hookAjaxRequest(&$out, $request_mode, $request_item, $request_lang)
    {
        global $lang, $rlDb, $config, $reefless, $rlLang, $account_info;

        if ($request_mode == 'bwtCompleteTransaction') {
            $item_id = (int) $request_item;
            $dealer_id = (int) $account_info['ID'];

            if (!$item_id || !$dealer_id) {
                return;
            }

            $txn_info = $rlDb->fetch('*', array('ID' => $item_id, 'Dealer_ID' => $dealer_id), null, 1, 'transactions', 'row');
            $html = $html_status = $message = '';

            if ($txn_info) {
                $this->initGateway();

                $reefless->loadClass('Payment');
                $GLOBALS['rlBankWireTransferGateway']->callBack($txn_info['ID']);

                $url = $reefless->getPageUrl('bwt_requests');
                $url .= $config['mod_rewrite'] ? '?item=' . $item_id : '&item=' . $item_id;

                $tpl_base = RL_URL_HOME . 'templates/' . $config['template'] . '/';
                $html = '<a href="' . $url . '"><img src="' . $tpl_base . 'img/blank.gif" alt="' . $lang['view_details'];
                $html .= '" title="' . $lang['view_details'] . '" class="view_details" />' . $lang['view_details'] . '</a>';
                $html_status = '<span class="item_paid">' . $lang['shc_paid'] . '</span>';

                $message = $lang['bwt_request_activated_successfully'];
                $status = 'OK';
            } else {
                $message = $lang['bwt_request_activated_failed'];
                $status = 'ERROR';
            }

            $out = array(
                'status' => $status,
                'html' => $html,
                'html_status' => $html_status,
                'message_text' => $message,
            );
        }

        if ($request_mode == 'bwtUploadFile') {
            $itemID = (int) $request_item;

            $txnInfo = $rlDb->fetch('*', array('ID' => $itemID, 'Account_ID' => $account_info['ID']), null, 1, 'transactions', 'row');

            if (!$txnInfo) {
                $error = $lang['bwt_transaction_not_found'];
            }

            $fileSource = $_FILES['file'];

            if (empty($fileSource['tmp_name'])) {
                $error = $lang['bwt_file_not_selected'];
            }

            $file_ext = explode('.', $fileSource['name']);
            $file_ext = array_reverse($file_ext);
            $file_ext = $file_ext[0];

            $allowed_types = array(
                'application/pdf',
                'application/zip',
                'application/x-zip-compressed',
                'application/x-compressed',
                'multipart/x-zip',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/vnd.ms-office',
                'application/vnd.oasis.opendocument.text',
                'application/vnd.ms-excel'
            );

            $mime = mime_content_type($fileSource['tmp_name']);

            if (!in_array($mime, $allowed_types) && !empty($fileSource['tmp_name'])) {
                $error = str_replace(
                    array('{field}', '{ext}'),
                    array(
                        '<span class="field_error">"' . $lang['bwt_doc_file'] . '"</span>',
                        '<span class="field_error">"' . $file_ext . '"</span>',
                    ),
                    $lang['notice_bad_file_ext']
                );
            }

            if ($error) {
                $out = array(
                    'status' => 'ERROR',
                    'message' => $error,
                );

                return;
            }

            $reefless->loadClass('Actions');

            $file_name = 'txn_doc_' . time() . mt_rand();
            $file_name = $GLOBALS['rlActions']->upload('file', $file_name, false, false, '', false);
            $docName = preg_replace('/\d+/', $itemID, $file_name);

            if ($file_name) {
                if ($txnInfo['Doc'] && file_exists(RL_FILES . $txnInfo['Doc'])) {
                    unlink(RL_FILES . $txnInfo['Doc']);
                }

                $dir = RL_FILES . 'bwt-docs' . RL_DS . date('m-Y') . RL_DS;
                $dir_name = 'bwt-docs/' . date('m-Y') . '/';
                $GLOBALS['reefless']->rlMkdir($dir);
                rename(RL_FILES . $file_name, $dir . $file_name);
                $file_name = $dir_name . $file_name;

                $update = array(
                    'fields' => array('Doc' => $file_name),
                    'where' => array(
                        'ID' => $itemID
                    )
                );

                $rlDb->updateOne($update, 'transactions');
                $lang['bwt_view_doc'] = $rlLang->getPhrase('bwt_view_doc', $request_lang, null, true);

                $out = array(
                    'status' => 'OK',
                    'file' => '<a class="d-block download text-truncate d-inline-block" style="max-width: 120px;" href="' . RL_FILES_URL . $file_name . '" target="_blank" title="' . $lang['bwt_view_doc'] . '"><svg width="18" height="18" viewBox="0 0 24 24" class="icon grid-icon-fill align-middle"><use xlink:href="#download"></use></svg>&nbsp;' . $docName . '</a><a class="bwt-delete-file d-block mt-2" data-item="' . $itemID . '" href="javascript://"><svg width="18" height="18" viewBox="0 0 24 24" class="icon grid-icon-fill align-middle"><use xlink:href="#remove"></use></svg>&nbsp;' . $lang['delete'] . '</a>',
                    'message' => $rlLang->getPhrase('bwt_file_uploaded', $request_lang, null, true),
                );
            } else {
                $out = array(
                    'status' => 'ERROR',
                    'message' => $rlLang->getPhrase('bwt_file_upload_error', $request_lang, null, true),
                );
            }
        }

        if ($request_mode == 'bwtDeleteFile') {
            $itemID = (int) $request_item;

            $txnInfo = $rlDb->fetch('*', array('ID' => $itemID, 'Account_ID' => $account_info['ID']), null, 1, 'transactions', 'row');

            if (!$txnInfo) {
                $error = $lang['bwt_transaction_not_found'];
            }

            if ($txnInfo['Doc'] && file_exists(RL_FILES . $txnInfo['Doc'])) {
                unlink(RL_FILES . $txnInfo['Doc']);

                $update = array(
                    'fields' => array('Doc' => ''),
                    'where' => array(
                        'ID' => $itemID
                    )
                );

                $result = $rlDb->updateOne($update, 'transactions');
            }

            $phraseKey = $result ? 'bwt_file_delete_ok' : 'bwt_file_delete_error';
            $out = array(
                'status' => $result ? 'OK' : 'ERROR',
                'message' => $rlLang->getPhrase($phraseKey, $request_lang, null, true),
            );
        }
    }

    /**
     * Initialize gateway class
     *
     * @since 2.1.0
     */
    public function initGateway()
    {
        if (!is_object('rlGateway')) {
            require_once RL_CLASSES . 'rlGateway.class.php';
        }

        $GLOBALS['reefless']->loadClass('BankWireTransferGateway', null, 'bankWireTransfer');
    }

    /**
     * Add account fields for shopping cart & bidding plugin
     *
     * @since 3.0.0
     */
    public function addAccountFields()
    {
        global $rlDb, $plugins;

        if (!$plugins['shoppingCart']) {
            return;
        }

        $accountsTable = 'shc_account_settings';

        if (!$rlDb->tableExists('shc_account_settings')) {
            $accountsTable = 'accounts';

            if (version_compare($GLOBALS['config']['rl_version'], '4.8.2') >= 0) {
                return;
            }
        }

        $rlDb->addColumnsToTable(
            array(
                'bankWireTransfer_enable' => "ENUM('0','1') NOT NULL DEFAULT '0'",
                'bankWireTransfer_details' => "text NOT NULL default ''",
            ),
            $accountsTable
        );
    }

    /**
     * @hook apExtTransactionsData
     *
     * @since 3.0.0
     */
    public function hookApExtTransactionsData()
    {
        global $data, $lang;

        foreach ($data as $key => $value) {
            $file = RL_FILES_URL . $value['Doc'];

            if (empty($value['Doc']) || !file_exists(RL_FILES . $value['Doc'])) {
                $data[$key]['Doc'] = $lang['not_available'];
                continue;
            }
                $data[$key]['Doc'] = "
<a href='{$file}' target='_blank'>
    <svg ext:qtip='{$lang['bwt_view_doc']}' width=\"18\" height=\"18\" viewBox=\"0 0 24 24\"><use xlink:href=\"#download\"></use></svg>
</a>
";
        }
    }

    /**
     * @hook tplHeaderUserNav
     *
     * @since 3.0.0
     */
    public function hookTplHeaderUserNav()
    {
        global $page_info;

        if ($page_info['Controller'] == 'payment_history') {
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'bankWireTransfer/static/icons.svg');
        }
    }

    /**
     * @hook apTplHeaderNavBar
     *
     * @since 3.0.0
     */
    public function hookApTplHeaderNavBar()
    {
        global $cInfo;

        if ($cInfo['Controller'] == 'transactions') {
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'bankWireTransfer/static/icons.svg');
        }
    }

    /**
     * @hook apTplFooter
     *
     * @since 3.0.0
     */
    public function hookApTplFooter()
    {
        global $cInfo;

        if ($cInfo['Controller'] == 'payment_gateways' && $_GET['item'] == 'bankWireTransfer') {
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'bankWireTransfer/admin/payment_gateways.tpl');
        }
    }

    /**
     * @hook addListingBottom
     *
     * Prevent the double increasing count of listing in the categories
     *
     * @since 3.0.0
     */
    public function hookAddListingBottom(&$addListing): void
    {
        global $reefless, $rlCategories, $rlDb, $rlPayment;

        $id = (int) $addListing->listingID;

        if ($addListing->step === 'done' && (int) $_SESSION['bwtAddListing'] === $id) {
            /**
             * If customer to pay for membership plan via add listing page,
             * the listing must be deactivated until transaction will be paid
             */
            if ($addListing->planType === 'account'
                && $rlPayment->getOption('service') === 'membership'
                && $rlDb->getOne('Status', "`ID` = {$id}", 'listings') === 'active'
            ) {
                self::deactivateListing($id, $rlDb);
            }

            $reefless->loadClass('Categories');
            $categoryID = $addListing->listingData['Category_ID'];
            $crossed = $addListing->listingData['Crossed'];

            $rlCategories->listingsDecrease($categoryID, $addListing->listingType['Key']);
            $rlCategories->accountListingsDecrease($rlDb->getOne('Account_ID', "`ID` = {$id}", 'listings'));

            // crossed listings count control
            if ($crossed) {
                $crossed = explode(',', $crossed);
                foreach ($crossed as $crossedID) {
                    $rlCategories->listingsDecrease($crossedID);
                }
            }

            unset($_SESSION['bwtAddListing']);
        }
    }

    /**
     * @since 3.1.1
     *
     * @param int    $id
     * @param object $rlDb
     *
     * @return bool
     */
    public static function deactivateListing(int $id, object $rlDb): bool
    {
        return $rlDb->updateOne([
            'fields' => [
                'Status'        => 'pending',
                'Pay_date'      => '0000-00-00 00:00:00',
                'Featured_date' => '0000-00-00 00:00:00',
            ],
            'where' => ['ID' => $id],
        ], 'listings');
    }

    /**
     * @hook shcSaveAccountSettings
     *
     * @since 3.0.0
     */
    public function hookShcSaveAccountSettings(&$htmlFields)
    {
        $htmlFields[] = 'bankWireTransfer_details';
    }

    /**
     * @deprecated 2.1.0
     *
     * @hook apPhpIndexBottom
     * @since 2.0.0
     */
    public function hookApPhpIndexBottom()
    {}

    /**
     * @deprecated 2.1.0
     *
     * Comlete transaction (for shopping cart & bidding plugin)
     * @param int $item_id
     */
    public function ajaxCompleteTransaction($item_id = false)
    {}

    /**
     * @deprecated 3.0.0
     *
     * @hook profileController
     */
    public function hookProfileController()
    {}

    /**
     * @deprecated 3.0.0
     *
     * @hook apPhpPaymetGatewaysSettings
     */
    public function hookApPhpPaymetGatewaysSettings(&$param1)
    {}

    /**
     * @deprecated 3.0.0
     *
     * @hook phpGetPaymentGatewaysItem
     */
    public function hookPhpGetPaymentGatewaysItem(&$gateway, &$content)
    {}

    /**
     * @deprecated 3.0.0
     *
     * @hook specialBlock
     * @since 2.0.0
     */
    public function hookSpecialBlock()
    {}
}
