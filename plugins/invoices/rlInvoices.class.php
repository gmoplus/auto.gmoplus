<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLINVOICES.CLASS.PHP
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

use Dompdf\Dompdf;
use Dompdf\Options;
use Flynax\Utils\Util;

class rlInvoices
{
    /**
     * Total found invoices
     *
     * @var int
     */
    public $calc;

    /**
     * Invoice details
     *
     * @var array
     */
    protected $item_info;

    /**
     * Payment status
     *
     * @var bool
     */
    protected $is_payment;

    /**
     * Complete payment of invoice
     *
     * @since 2.1.0 - Removed unnecessary parameteres ($txn_id, $gateway, $total)
     *
     * @param int $item_id
     * @param int $plan_id
     * @param int $account_id
     * @return bool
     */
    public function completeTransaction($item_id = 0, $plan_id = 0, $account_id = 0)
    {
        global $reefless, $rlDb;

        $item_id = (int) $item_id;

        $invoice_info = $rlDb->fetch('*', array('ID' => $item_id), null, 1, 'invoices', 'row');

        if (!empty($invoice_info)) {
            $invoice_update = array(
                'fields' => array(
                    'pStatus' => 'paid',
                    'Pay_date' => 'NOW()',
                    'IP' => Util::getClientIP(),
                ),
                'where' => array(
                    'ID' => $item_id,
                ),
            );

            if ($rlDb->updateOne($invoice_update, 'invoices')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate number of invoice
     *
     * @param string $txn_tpl
     */
    public function generate($txn_tpl = 'INV*******')
    {
        global $reefless;

        $txn_length = substr_count($txn_tpl, '*');

        if ($txn_length > 2) {
            $tmp_hash = $reefless->generateHash($txn_length, 'upper');
        } else {
            $tmp_hash = $reefless->generateHash(5, 'upper');
        }

        $mask = str_replace("*", "", $txn_tpl);
        $txn = $mask . $tmp_hash;

        if ($GLOBALS['rlDb']->getOne("Txn_ID", "`Txn_ID` = '{$txn}'", 'invoices')) {
            return $this->generate($txn_tpl);
        } else {
            return $txn;
        }
    }

    /**
     * Delete invoice
     *
     * @param int $id
     */
    public function ajaxDeleteItem($id = 0)
    {
        global $_response;

        $id = (int) $id;
        if (!$id) {
            return $_response;
        }

        $delete = "DELETE FROM `{db_prefix}invoices` WHERE `ID` = '{$id}' LIMIT 1";
        $GLOBALS['rlDb']->query($delete);

        // print message, update grid
        $_response->script("
            invoicesGrid.reload();
            printMessage('notice', '{$GLOBALS['lang']['item_deleted']}');
        ");

        return $_response;
    }

    /**
     * Get invoice details
     *
     * @param  string $invoice_id
     * @param  int $account_id
     * @return array
     */
    public function getInvoice($invoice_id = '', $account_id = 0)
    {
        $sql = "SELECT `T1`.*, `T2`.`Gateway`, `T2`.`Txn_ID` AS `Txn_gateway` ";
        $sql .= "FROM `{db_prefix}invoices` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}transactions` AS `T2` ON `T1`.`ID` = `T2`.`Item_ID` AND `T2`.`Service` = 'invoice' ";
        $sql .= "WHERE (`T1`.`Txn_ID` = '{$invoice_id}' || `T1`.`ID` = '{$invoice_id}') ";

        if ($account_id) {
            $sql .= "AND `T1`.`Account_ID` = '{$account_id}' ";
        }

        $invoice_info = $GLOBALS['rlDb']->getRow($sql);

        if ($invoice_info) {
            return $invoice_info;
        }

        return array();
    }

    /**
     * Get list invoices by account
     *
     * @param  int $account_id
     * @param  int $page
     * @return array
     */
    public function getInvoices($account_id = 0, $page = 0)
    {
        global $config, $rlDb;

        if (!$account_id) {
            return false;
        }

        $from = $page * $config['invoices_per_page'];
        $limit = $config['invoices_per_page'];

        $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `T1`.*, `T2`.`Gateway`  ";
        $sql .= "FROM `{db_prefix}invoices` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}transactions` AS `T2` ON `T1`.`ID` = `T2`.`Item_ID` ";
        $sql .= "WHERE `T1`.`Account_ID` = '{$account_id}' ";
        $sql .= "GROUP BY `T1`.`ID` ";
        $sql .= "ORDER BY `T1`.`Date` DESC ";
        $sql .= "LIMIT {$from},{$limit}";

        $invoices = $rlDb->getAll($sql);

        $calc = $rlDb->getRow("SELECT FOUND_ROWS() AS `calc`");
        $this->calc = $calc['calc'];

        if ($invoices) {
            return $invoices;
        }

        return array();
    }

    /**
     * Get invoices by status
     *
     * @param  int $account_id
     * @param  string $status
     * @param  int $limit
     * @return array
     */
    public function getInvoicesByStatus($account_id = 0, $status = '', $limit = 5)
    {
        if (!$status) {
            return array();
        }

        $sql = "SELECT `ID`,`Txn_ID`,`Date`,`Subject` FROM `{db_prefix}invoices` ";
        $sql .= "WHERE `Account_ID` = '{$account_id}' AND `pStatus` = '{$status}' ORDER BY `Date` DESC LIMIT {$limit}";
        $invoices = $GLOBALS['rlDb']->getAll($sql);

        return $invoices;
    }

    /**
     * @hook tplFooter
     *
     * @since 2.0.0
     */
    public function hookTplFooter()
    {
        global $rlSmarty;

        if (!$GLOBALS['rlAccount']->isLogin()) {
            return;
        }

        $invoices = (array) $_SESSION['unpaidInvoices'];

        $invoice_link = $GLOBALS['reefless']->getPageUrl('invoices', array('item' => $invoices[0]['Txn_ID']));
        $invoice_link = '<a href="' . $invoice_link . '">' . $GLOBALS['lang']['here'] . '</a>';

        $rlSmarty->assign('unpaid_invoices', count($invoices));
        $rlSmarty->assign('invoice_link', $invoice_link);
        $rlSmarty->display(RL_PLUGINS . 'invoices' . RL_DS . 'tplFooter.tpl');
    }

    /**
     * @hook apTplHeader
     *
     * @since 2.0.0
     */
    public function hookApTplHeader()
    {
        global $controller;

        if ($controller == 'invoices') {
            echo '<link href="' . RL_PLUGINS_URL . 'invoices/static/aStyle.css" type="text/css" rel="stylesheet" />';
        }
    }

    /**
     * @hook postPaymentComplete
     *
     * @since 2.1.0
     */
    public function hookPostPaymentComplete(&$params)
    {
        $this->is_payment = true;
        $this->initPDF();
        $this->buildPDF('payment', (array) $params, false, true);
    }

    /**
     * Initialize PDF library
     */
    public function initPDF()
    {
        if (!is_object($GLOBALS['rlSmarty'])) {
            require_once RL_LIBS . 'smarty' . RL_DS . 'Smarty.class.php';
            $GLOBALS['reefless']->loadClass('Smarty');
        }
    }

    /**
     * @hook apExtTransactionsData
     *
     * @since 2.0.0
     */
    public function hookApExtTransactionsData()
    {
        global $data, $pages;

        // get pages list if they're not exist
        if (!$pages) {
            $pages = Util::getPages(array('Key', 'Path'), array('Status' => 'active'), null, array('Key', 'Path'));
        }

        $invoice_in_pdf = $GLOBALS['reefless']->getPageUrl('invoice_in_pdf', array('type' => 'payment'), false, 'item=');

        foreach ($data as $key => $value) {
            if ($value['Service'] == 'invoice') {
                $sql = "SELECT `ID`, `Subject` FROM `{db_prefix}invoices` WHERE `ID` = '{$value['Item_ID']}' LIMIT 1";
                $item_info = $GLOBALS['rlDb']->getRow($sql);

                if ($item_info) {
                    $data[$key]['Item'] = $item_info['Subject'];
                }
            }
            $data[$key]['pdf'] = "
<center>
    <a href='{$invoice_in_pdf}{$value['ID']}' target='_blank'>
        <img ext:qtip='{$GLOBALS['lang']['invoice_view']}' src='" . RL_PLUGINS_URL . "invoices/static/pdf.png' height='15' />
    </a>
</center>
";
        }
    }

    /**
     * @hook apTplTransactionsGrid
     *
     * @since 2.0.0
     */
    public function hookApTplTransactionsGrid()
    {
        echo <<< FL
    var gridInstance = transactionsGrid.getInstance();
    var columns = [];
    var j = 0;
    for (var i = 0; i < gridInstance.columns.length; i++) {
        columns[j++] = gridInstance.columns[i];
        if (i == 8) {
            columns[j++] = {
                header: '{$GLOBALS['lang']['invoice_in']}',
                dataIndex: 'pdf',
                width: 50,
                fixed: true
            }
        }
    }
    gridInstance.columns = columns;
    gridInstance.fields.push({name: 'pdf', mapping: 'pdf'});
    transactionsGrid = new gridObj(gridInstance);
FL;
    }

    /**
     * Get transaction by ID
     *
     * @param int $item_id
     * @return array
     */
    public function getTransaction($item_id = 0)
    {
        if (!$item_id) {
            return false;
        }

        $item_info = $GLOBALS['rlDb']->fetch('*', array('ID' => $item_id), false, 1, 'transactions', 'row');
        if ($item_info) {
            $GLOBALS['reefless']->loadClass('Account');
            $item_info['buyer'] = $GLOBALS['rlAccount']->getProfile((int) $item_info['Account_ID']);
        }

        return $item_info;
    }

    /**
     * Build invoice in PDF format
     *
     * @since 2.1.1 - Added $isGateway parameter
     *
     * @param string $type
     * @param array $params
     * @param bool $is_ouput
     * @param bool $isGateway
     */
    public function buildPDF($type = '', $params = array(), $is_ouput = true, $isGateway = false)
    {
        global $rlSmarty, $account_info, $reefless, $config;

        $prefix = 'invoice-';
        switch ($type) {
            case 'payment':
                $item_info = $this->getTransaction((int) $params['txn_id']);
                if ($config['invoices_include_tax']) {
                    $item_info['tax_rate'] = round(((float) $item_info['Total'] * (float) $config['invoices_tax_value']) / 100, 2);
                    $item_info['price'] = (float) $item_info['Total'] - $item_info['tax_rate'];
                    $item_info['subotal'] = (float) $item_info['Total'] - $item_info['tax_rate'];
                } else {
                    $item_info['price'] = $item_info['subotal'] = (float) $item_info['Total'];
                }
                if (isset($params['txn_gateway']) && !empty($params['txn_gateway'])) {
                    $item_info['Txn_ID'] = $params['txn_gateway'];
                }
                $prefix = 'payment-';

                if ($isGateway) {
                    $item_info['Status'] = 'paid';
                }
                break;

            case 'service':
                $item_info = $this->getInvoice((int) $params['txn_id']);
                break;
        }

        if (!$_SESSION['sessAdmin'] && !$isGateway) {
            if (!defined('IS_LOGIN')) {
                $reefless->redirect(false, $reefless->getPageUrl('login'));
            }
            if ($account_info['ID'] != $item_info['Account_ID']) {
                $reefless->redirect(false, $reefless->getPageUrl('404'));
            }
        }

        $this->item_info = $item_info;

        if ($item_info) {
            $options = new Options();
            $options->set('defaultFont', 'dejavu serif');
            $options->set('font', 'dejavu serif');
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);

            $dompdf = new Dompdf($options);

            if ((defined('ANDROID_APP') || defined('IOS_APP')) && $type === 'payment') {
                $html = $this->prepareTpl($item_info);
            } else {
                $rlSmarty->assign('type', $type);
                $rlSmarty->assign('item_info', $item_info);
                $html = $rlSmarty->fetch(RL_PLUGINS . 'invoices' . RL_DS . 'payment_invoice.tpl', null, null, false);
            }

            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'letter');
            $dompdf->render();

            if ($is_ouput) {
                $dompdf->stream($prefix . sprintf('%08s', $item_info['ID']), ['Attachment' => 0]);
            } else {
                file_put_contents(RL_TMP . $prefix . sprintf('%08s', $item_info['ID'])  . '.pdf', $dompdf->output());
            }
        }
    }

    /**
     * @hook phpMailSend
     *
     * @since 2.0.0
     */
    public function hookPhpMailSend(&$subject, &$body, &$attach_file, &$from_mail, &$from_name)
    {
        global $page_info;

        if ($this->item_info && $this->is_payment) {
            $attach_file = RL_TMP . 'payment-' . sprintf("%08s", $this->item_info['ID']) . '.pdf';
        }
    }

    /**
     * @hook apTplFooter
     *
     * @since 2.0.0
     */
    public function hookApTplFooter()
    {
        global $cInfo;

        if ($cInfo['Controller'] == 'settings') {
            echo <<< FL
<script type="text/javascript">
    $(document).ready(function() {
        if ($('input[name="post_config[invoices_send_pdf][value]"]').length) {
            controlInvoicesPDFOptions($('input[name="post_config[invoices_send_pdf][value]"]:checked').val());
        }
        $('input[name="post_config[invoices_send_pdf][value]"]').change(function() {
            controlInvoicesPDFOptions($(this).val());
        });
    });

    var controlInvoicesPDFOptions = function(is_enable) {
        if (is_enable == 1) {
            $('input[name="post_config[invoices_billing][value]"]').parent().parent().parent('tr').removeClass('hide');
            $('input[name="post_config[invoices_include_tax][value]"]').parent().parent().parent('tr').removeClass('hide');
            $('input[name="post_config[invoices_tax_value][value]"]').parent().parent().parent('tr').removeClass('hide');
        } else {
            $('input[name="post_config[invoices_billing][value]"]').parent().parent().parent('tr').addClass('hide');
            $('input[name="post_config[invoices_include_tax][value]"]').parent().parent().parent('tr').addClass('hide');
            $('input[name="post_config[invoices_tax_value][value]"]').parent().parent().parent('tr').addClass('hide');
        }
    }
</script>
FL;
        }
    }

    /**
     * Prepare payment invoice in html
     *
     * @since 2.1.0
     *
     * @param array $item_info
     * @return string
     */
    public function prepareTpl($item_info = array())
    {
        global $lang, $config;

        $html = '';
        $file = RL_PLUGINS . 'invoices' . RL_DS . 'payment_invoice.source';

        if (file_exists($file)) {
            $tmp     = fopen($file, 'rb');
            $content = '';
            if ($tmp) {
                while (!feof($tmp)) {
                    $content .= fgets($tmp);
                }
                fclose($tmp);
            }

            $email_site_name = $lang['email_site_name'] ?: $lang['pages+title+home'];
            $email_site_name .= '<br /><small>' . RL_URL_HOME . '</small>';

            $invoice_buyer_info = '';
            foreach ($item_info['buyer']['Fields'] as $field) {
                $invoice_buyer_info .= '<div style="padding: 3px 0;">' . $field['name'] . ': ' . $field['value'] . '</div>';
            }

            $common_info = "
{$lang['invoice_number']}:&nbsp;{$item_info['ID']}<br />
{$lang['txn_id']}:&nbsp;{$item_info['Txn_ID']}<br />
{$lang['payment_gateway']}:&nbsp;{$item_info['Gateway']}<br />
{$lang['date']}:&nbsp;{$item_info['Date']}
";

            $logoExtension = is_file(RL_ROOT . 'templates/' . $config['template'] . '/img/logo.svg') ? 'svg' : 'png';

            $search = array(
                '{$lang.invoice_number}',
                '{$item_info.ID}',
                '{logo}',
                '{$lang.email_site_name}',
                '{$lang.invoice_buyer_info}',
                '{$invoice_buyer_info}',
                '{$lang.invoice_seller_info}',
                '{$config.invoices_billing}',
                '{$common_info}',
                '{$lang.item}',
                '{$lang.invoice_quantity}',
                '{$lang.status}',
                '{$lang.price}',
                '{$item_info.Item_name}',
                '{$item_info.Status}',
                '{$item_info.price}',
                '{$lang.invoice_subtotal}',
                '{$item_info.subotal}',
                '{$lang.total}',
                '{$item_info.Total}',

            );
            $replacement = array(
                $lang['invoice_number'],
                $item_info['ID'],
                RL_URL_HOME . '/templates/' . $config['template'] . '/img/logo.' . $logoExtension,
                $email_site_name,
                $lang['invoice_buyer_info'],
                $invoice_buyer_info,
                $lang['invoice_seller_info'],
                $config['invoices_billing'],
                $common_info,
                $lang['item'],
                $lang['invoice_quantity'],
                $lang['status'],
                $lang['price'],
                $item_info['Item_name'],
                $lang[$item_info['Status']],
                self::addCurrency($item_info['price']),
                $lang['invoice_subtotal'],
                self::addCurrency($item_info['subotal']),
                $lang['total'],
                self::addCurrency($item_info['Total']),
            );

            $html = str_replace($search, $replacement, $content);
        }

        return $html;
    }

    /**
     * Add currency to price
     *
     * @since 2.1.0
     *
     * @param  double $total
     * @return double
     */
    public static function addCurrency($total = 0)
    {
        global $config;

        $total = number_format(
            $total,
            2,
            $config['price_separator'],
            $config['price_delimiter']
        );

        if ($config['system_currency_position'] == 'before') {
            $total = $config['system_currency'] . ' ' . $total;
        } else {
            $total = $total . ' ' . $config['system_currency'];
        }

        return $total;
    }

    /**
     * @hook apPhpPagesValidate
     *
     * @since 2.1.1
     */
    public function hookApPhpPagesValidate()
    {
        global $errors;

        foreach ($errors as $key => $value) {
            if (strpos($value, 'invoice_in_pdf.tpl') > 0) {
                unset($errors[$key]);
            }
        }
    }

    /**
     * @hook specialBlock
     *
     * @since 2.1.1
     */
    public function hookSpecialBlock()
    {
        global $rlSmarty;

        if (!$GLOBALS['rlAccount']->isLogin()) {
            return;
        }

        if(!isset($_SESSION['unpaidInvoices'])) {
            $_SESSION['unpaidInvoices'] = $this->getInvoicesByStatus($GLOBALS['account_info']['ID'], 'unpaid');
        }

        // Remove invoice_in_pdf page from menus
        $main_menu = $rlSmarty->get_template_vars('main_menu');
        $footer_menu = $rlSmarty->get_template_vars('footer_menu');
        $account_menu = $rlSmarty->get_template_vars('account_menu');

        foreach ($main_menu as $mmKey => $mmItem) {
            if ($mmItem['Key'] == 'invoice_in_pdf') {
                unset($main_menu[$mmKey]);
            }
        }
        foreach ($footer_menu as $fmKey => $fmItem) {
            if ($fmItem['Key'] == 'invoice_in_pdf') {
                unset($footer_menu[$fmKey]);
            }
        }
        foreach ($account_menu as $amKey => $amItem) {
            if ($amItem['Key'] == 'invoice_in_pdf') {
                unset($account_menu[$amKey]);
            }
        }
        $rlSmarty->assign_by_ref('main_menu', $main_menu);
        $rlSmarty->assign_by_ref('footer_menu', $footer_menu);
        $rlSmarty->assign_by_ref('account_menu', $account_menu);
    }

    /**
     * @hook tplHeader
     *
     * @deprecated 2.1.1
     */
    public function hookTplHeader()
    {}

    /**
     * @deprecated 2.1.1
     *
     * The method checks if the PDFLib is ready
     *
     * @return bool
     */
    public function isExistsPDFLib()
    {}

    /**
     * Get PDF lang
     *
     * @deprecated 2.1.2
     *
     * @param string $current_lang
     * @return string
     */
    public function getPDFLang($current_lang = '')
    {}
}
