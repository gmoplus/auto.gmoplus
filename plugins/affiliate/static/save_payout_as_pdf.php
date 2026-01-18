<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : SAVE_PAYOUT_AS_PDF.PHP
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

use Dompdf\Dompdf;
use Dompdf\Options;

require_once __DIR__ . "/../../../includes/config.inc.php";
require_once RL_INC . 'control.inc.php';
require RL_ROOT . 'vendor/autoload.php';

if ($payout_id = (int) $_GET['id']) {
    $languages = $rlLang->getLanguagesList();
    $rlLang->defineLanguage($_SESSION['account']['Lang'] ?: $config['lang']);
    $lang = $rlLang->getLangBySide();

    $reefless->loadClass('Affiliate', null, 'affiliate');
    $rlAffiliate->getPayoutDetails();

    $select = ['ID', 'Date_format'];
    $where  = ['Status' => 'active', 'Code' => RL_LANG_CODE];
    $user_lang = $rlDb->fetch($select, $where, null, null, 'languages', 'row');

    $date = new DateTimeImmutable($payout['Date'], new DateTimeZone($config['timezone']));
    $payment_date = date(str_replace(['%', 'b'], ['', 'M'], $user_lang['Date_format']), $date->getTimestamp());

    $logoExtension = is_file(RL_ROOT . 'templates/' . $config['template'] . '/img/logo.svg') ? 'svg' : 'png';

    $html = '<!doctype html><html lang="en"><head><meta charset="UTF-8">';
    $html .= "<title>{$lang['aff_payout_details']}</title>";
    $html .= "<meta name=\"author\" content=\"{$payout['Aff_Full_name']}\">";
    $html .= "<style>body {font-size: 12px; font-family: 'Dejavu Sans', Arial, Helvetica, sans-serif;}</style>";
    $html .= '</head><body>';
    $html .= '<table width="100%" cellpadding="10">
        <tr>
            <td width="150px" height="80px">
                <img src="' . RL_URL_HOME . '/templates/' . $config['template'] . '/img/logo.' . $logoExtension . '">
            </td>
            <td style="text-align: right;">
                <font size="14" color="#000000">' . $lang['pages+title+home'] . '</font>
                <br />
                ' . RL_URL_HOME . '
            </td>
        </tr>
    </table>
    <hr>';

    // add payout details
    $html .= '
    <table width="100%" cellpadding="10">
        <tr>
            <td align="right" height="80px">
                <font size="17">' . $lang['aff_payment_invoice'] . '</font>
            </td>
        </tr>
        <tr>
            <td bgcolor="#d9d9d9" style="border-bottom:1px solid #9b9b9b;">
                <font size="14" color="#000000">' . $lang['aff_payout_details'] . '</font>
            </td>
        </tr>
    </table>

    <table width="100%" cellpadding="4">
        <tr>
            <td width="150"><font size="12" color="#555555">' . $lang['account'] . ':</font></td>
            <td>' . $payout['Aff_Full_name'] . '</td>
        </tr>
        <tr>
            <td width="150"><font size="12" color="#555555">' . $lang['mail'] . ':</font></td>
            <td>' . $payout['Aff_email'] . '</td>
        </tr>
        <tr>
            <td width="150"><font size="12" color="#555555">' . $lang['aff_payout_date'] . ':</font></td>
            <td>' . $payment_date . '</td>
        </tr>
        <tr>
            <td width="150"><font size="12" color="#555555">' . $lang['aff_payout_count'] . ':</font></td>
            <td>' . $payout['Count_deals'] . '</td>
        </tr>
        <tr>
            <td width="150"><font size="12" color="#555555">' . $lang['aff_payout_amount'] . ':</font></td>
            <td>' . $payout['Amount'] . '</td>
        </tr>
        <tr>
            <td colspan="2" height="50px"></td>
        </tr>
    </table>

    <table width="100%" cellpadding="10">
        <tr>
            <td width="30%" bgcolor="#d9d9d9" style="border-bottom:1pt solid #9b9b9b;">
                <font size="14" color="#000000">' . $lang['aff_commissions_date'] . '</font>
            </td>
            <td width="40%" bgcolor="#d9d9d9" style="border-bottom:1pt solid #9b9b9b;">
                <font size="14" color="#000000">' . $lang['aff_commissions_commission'] . '</font>
            </td>
            <td width="30%" bgcolor="#d9d9d9" style="border-bottom:1pt solid #9b9b9b;">
                <font size="14" color="#000000">' . $lang['aff_details_item_admin_item'] . '</font>
            </td>
        </tr>';

    foreach ($payout['Deals'] as $deal) {
        $deal_index++;

        $date = new DateTimeImmutable($deal['Posted'], new DateTimeZone($config['timezone']));
        $commission_date = date(str_replace(['%', 'b'], ['', 'M'], $user_lang['Date_format']), $date->getTimestamp());

        $html .= '
            <tr>
                <td width="30%" ' . ($deal_index < count($payout['Deals']) ? 'style="border-bottom:1pt solid #9b9b9b"' : '') . '>
                    <font size="14" color="#000000">' . $commission_date . '</font>
                </td>
                <td width="40%" ' . ($deal_index < count($payout['Deals']) ? 'style="border-bottom:1pt solid #9b9b9b"' : '') . '>
                    <font size="14" color="#000000">' . $deal['Commission'] . ($deal['Plan'] ? ' (' . $deal['Plan'] . ')' : '') . '</font>
                </td>
                <td width="30%" ' . ($deal_index < count($payout['Deals']) ? 'style="border-bottom:1pt solid #9b9b9b"' : '') . '>
                    <font size="14" color="#000000">' . $deal['Item'] . '</font>
                </td>
            </tr>
        ';
    }

    $html .= '</table></body></html>';

    $options = new Options();
    $options->set('defaultFont', 'dejavu serif');
    $options->set('font', 'dejavu serif');
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);

    $dompdf = new Dompdf($options);

    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'letter');
    $dompdf->render();
    $dompdf->stream('payout_' . $payout['ID'], ['Attachment' => 0]);
}
