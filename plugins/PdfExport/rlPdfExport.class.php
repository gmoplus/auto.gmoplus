<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: RLPDFEXPORT.CLASS.PHP
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

/**
 * PDF Export class
 * @since 2.2.0
 */
class rlPdfExport extends Flynax\Abstracts\AbstractPlugin implements Flynax\Interfaces\PluginInterface
{
    /**
     * Path of folder with plugin
     * @since 2.3.0
     */
    public const PLUGIN_DIR = RL_PLUGINS . 'PdfExport/';

    /**
     * Generate and display PDF content with listing details
     *
     * @since 2.3.0
     *
     * @param $listingID
     * @return bool
     */
    public function display($listingID)
    {
        global $rlListingTypes, $rlListings, $rlAccount, $config, $plugins, $lang, $rlCurrencyConverter,
               $rlMembershipPlan, $domain_info;

        $listingID = (int) $listingID;

        if (!$listingID) {
            return false;
        }

        $GLOBALS['rlCommon']->pageMetaTags();

        if (is_object($GLOBALS['rlGeoFilter'])) {
            $GLOBALS['rlGeoFilter']->adaptLocString($lang['pages+title+home']);
        }

        $listingData  = $rlListings->getListing($listingID, true);

        if ($plugins['currencyConverter'] && $config['price_tag_field'] && $listingData[$config['price_tag_field']]) {
            $GLOBALS['reefless']->loadClass('CurrencyConverter', null, 'currencyConverter');

            $currencyRate = $_COOKIE['curConv_code'] ?: $_SESSION['curConv_code'];
            $price        = $listingData[$config['price_tag_field']];
            $currency     = explode('|', $price)[1] ?: '';

            if ($currency
                && $currencyRate
                && $rlCurrencyConverter->rates[$currency]['Rate']
                && $rlCurrencyConverter->rates[$currencyRate]['Rate']
            ) {
                $price        = $price / $rlCurrencyConverter->rates[$currency]['Rate'];
                $price        = round($price * $rlCurrencyConverter->rates[$currencyRate]['Rate'], 2);
                $currencyData = $rlCurrencyConverter->rates[$currencyRate];
                $currencyCode = $currencyData['Code'];
                $price        = $GLOBALS['rlValid']->str2money($price);

                if ($config['system_currency_position'] === 'before') {
                    $price = $currencyCode . ' ' . $price;
                } else {
                    $price = $price . ' ' . $currencyCode;
                }

                $listingData['converted_price'] = $price;
            }
        }

        $listingTitle = $listingData['listing_title'];
        $listingUrl   = $listingData['listing_link'];
        $listingType  = $rlListingTypes->types[$listingData['Listing_type']];
        $listing      = array_values($rlListings->getListingDetails($listingData['Category_ID'], $listingData, $listingType));

        if (isset($listingData['converted_price'])) {
            foreach ($listing as $groupKey => $group) {
                foreach ($group['Fields'] as $fieldKey => $field) {
                    if ($fieldKey == $config['price_tag_field']) {
                        $listing[$groupKey]['Fields'][$fieldKey]['value'] = $listingData['converted_price'];
                        break;
                    }
                }
            }
        }

        $seller        = $rlAccount->getProfile((int) $listingData['Account_ID']);
        $accountFields = $seller['Fields'];

        if ($config['pdf_account_form'] === 'browse') {
            $accountFields = $rlAccount->getShortDetails($seller, $seller['Account_type_ID']);
            foreach ($accountFields as &$accountField) {
                $accountField['name'] = $lang[$accountField['pName']];
            }
        }

        $siteLogo = RL_ROOT . "templates/{$config['template']}/img/logo.png";

        if ($config['pdf_export_logo']) {
            $customLogo = self::PLUGIN_DIR . 'static/' . $config['pdf_export_logo'];
            $siteLogo   = is_file($customLogo) ? $customLogo : $siteLogo;
        }

        if ($listingData['Main_photo']) {
            $photo = RL_FILES . $listingData['Main_photo'];
        } else {
            $photo = self::PLUGIN_DIR . 'static/no-photo.jpg';
        }

        // QR Code integration
        if ($plugins['qrCode']) {
            $qrCodeImage = RL_FILES . "qrcode/user_{$listingData['Account_ID']}/listing_{$listingID}.png";

            if (is_readable($qrCodeImage)) {
                $qrCodeHtml = "<img style=\"border: 1px black solid;\" src=\"{$qrCodeImage}\" />";
            }
        }

        // Include the main TCPDF library
        require self::PLUGIN_DIR . 'vendor/tecnickcom/tcpdf/tcpdf.php';
        require self::PLUGIN_DIR . 'MYPDF.class.php';

        // Create new PDF document
        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $fontname = 'freeserif';

        // Load custom font for Russian language
        if (RL_LANG_CODE == 'ru') {
            $fontname = TCPDF_FONTS::addTTFfont(self::PLUGIN_DIR . 'static/Roboto-Regular.ttf', 'TrueTypeUnicode', '', 96);
        }

        // Convert IDN domain
        $seo_base = $domain_info['scheme'] . '://' . idn_to_utf8($domain_info['host']) . '/';

        // Set document information
        $pdf->setCustomLogo($siteLogo);
        $pdf->SetCreator($lang['pages+title+home'] . 'PDF Export Plugin');
        $pdf->SetAuthor($seller['Full_name']);
        $pdf->SetTitle($listingTitle);
        $pdf->SetSubject('PDF Listing Export by ' . $lang['pages+title+home']);
        $pdf->SetKeywords($lang['pages+title+home'] . ', PDF, export, PDF Export');
        $pdf->SetHeaderData(PDF_HEADER_LOGO, 55, $lang['pages+title+home'], $seo_base);
        $pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
        $pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(PDF_MARGIN_LEFT, 30, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->SetFont($fontname, '', 12);
        $pdf->setHeaderFont([$fontname, '', PDF_FONT_SIZE_MAIN]);
        $pdf->AddPage();
        $pdf->SetTextColor(39, 39, 39);

        if (RL_LANG_DIR == 'rtl') {
            $pdf->setRTL(true);
        }

        $html = <<<EOD
        <style>
            table {
                width: 100%;
            }
            table td.field-name {
                width: 200px;
                color: #676766;
                vertical-align: top;
            }
        </style>

        <table>
        <tr>
            <td colspan="2" style="height: 50px;"><a style="color: #444444; font-size: 26px; text-decoration: none;" href="{$listingUrl}">{$listingTitle}</a></td>
        </tr>
        <tr>
EOD;

        if ($photo) {
            $html .= <<<EOD
            <td><img src="{$photo}" alt="{$listingTitle}" /></td>
EOD;
        }

        $isVisibleAccountSection = true;
        $isVisibleSellerDetails  = true;
        $isSendingMessageAllowed = true;

        if ($config['membership_module']
            && !$rlMembershipPlan->is_contact_allowed
            && !$rlMembershipPlan->is_send_message_allowed
        ) {
            $isVisibleAccountSection = false;
        }

        if ($config['membership_module'] && !$rlMembershipPlan->is_contact_allowed) {
            $isVisibleSellerDetails = false;
        }

        if ($config['membership_module'] && !$rlMembershipPlan->is_send_message_allowed) {
            $isSendingMessageAllowed = false;
        }

        if ($isVisibleAccountSection) {
            $html .= <<<EOD
            <td>
            <table style="width: 100%;">
EOD;

            if ($isVisibleSellerDetails) {
                $html .= <<<EOD
                <tr>
                    <td colspan="2" style="background-color: #e5e5e5;font-size: 20px;height: 28px;">{$lang['seller_info']}</td>
                </tr>
                <tr><td colspan="2"></td></tr>
                <tr>
                    <td style="width: 100px; color: #676766; height: 20px;">{$lang['name']}:</td>
                    <td>{$seller['Full_name']}</td>
                </tr>
EOD;
            }

            if ($seller['Display_email'] && $isSendingMessageAllowed) {
                $html .= <<<EOD
                    <tr>
                        <td style="color: #676766; height: 20px;">{$lang['mail']}:</td>
                        <td>{$seller['Mail']}</td>
                    </tr>
EOD;
            }

            if ($isVisibleSellerDetails) {
                foreach ($accountFields as $additionalValue) {
                    if ($additionalValue['Details_page'] === '0' || $additionalValue['value'] == '') {
                        continue;
                    }

                    if (substr_count($additionalValue['value'], 'http')) {
                        $additionalValue['value'] = str_replace(RL_URL_HOME, '', $additionalValue['value']);
                    }

                    $html .= <<<EOD
                        <tr>
                            <td style="color: #676766; height: 20px;">{$additionalValue['name']}:</td>
                                <td>{$additionalValue['value']}</td>
                        </tr>
EOD;
                }
            }

            $html .= <<<EOD
                </table></td>
EOD;
        }

        $html .= <<<EOD
                </tr>
                <tr><td colspan="2"></td></tr>
            </table>
            <table>
                <tr>
                    <td style="background-color: #e5e5e5; font-size: 20px;height: 28px;">{$lang['listing_details']}</td>
                </tr>
                <tr><td colspan="2"></td></tr>
            </table>
EOD;

        // Print listing details
        foreach ($listing as $value) {
            if (!count(array_filter((array) $value['Fields']))) {
                continue;
            }

            $html .= '<table>';

            if ($value['Group_ID']) {
                $html .= <<<EOD
                    <tr><td colspan="2"></td></tr>
                    <tr>
                        <td colspan="3" style="height: 28px;"><span style="font-size: 18px;font-weight: bold;">{$value['name']}</span></td>
                    </tr>
EOD;
            }

            foreach ($value['Fields'] as $field) {
                if ($field['Details_page'] === '0') {
                    continue;
                }

                if ($field['Type'] === 'textarea') {
                    $html .= <<<EOD
                        <tr>
                            <td class="field-name">{$field['name']}:</td><td></td></tr></table>
                            <table><tr><td>{$field['value']}</td></tr></table><table>
EOD;
                } else if ($field['Type'] === 'image' && is_file($imagePath = RL_FILES . $field['source'][0])) {
                    $html .= <<<EOD
                        <tr>
                            <td class="field-name">{$field['name']}:</td>
                            <td><img alt="" src="{$imagePath}"></td>
                        </tr>
EOD;
                } else {
                    $html .= <<<EOD
                        <tr><td class="field-name">{$field['name']}:</td><td style="height: 24px;">{$field['value']}</td></tr>
EOD;
                }
            }

            $html .= '</table>';
        }

        if ($qrCodeHtml) {
            $side = RL_LANG_DIR == 'rtl' ? 'left' : 'right';
            $html .= <<<EOD
                <table><tr><td style="text-align: {$side}">{$qrCodeHtml}</td></tr></table>
EOD;
        }

        /**
         * @since 2.5.0
         */
        $GLOBALS['rlHook']->load('phpPdfExportHtml', $listingData, $seller, $html);

        // Replace ₽ sign to ISO code
        if (RL_LANG_CODE != 'ru' && false !== strpos($html, '₽')) {
            $html = str_replace('₽', 'RUB', $html);
        }

        // Output the HTML content
        $pdf->writeHTML($html, true, false, true, false, 'left');

        // Close and output PDF document
        $pdf->Output("pdfExport_listing{$listingID}.pdf", 'I');
    }

    /**
     * @hook listingDetailsAfterStats
     */
    public function hookListingDetailsAfterStats()
    {
        if ($GLOBALS['page_info']['Controller'] !== 'listing_details') {
            return;
        }

        $GLOBALS['rlSmarty']->display(self::PLUGIN_DIR . 'PdfExport_icon.tpl');
    }

    /**
     * @hook sitemapExcludedPages
     */
    public function hookSitemapExcludedPages(&$excludedPages)
    {
        $excludedPages = array_merge($excludedPages, ['PdfExport']);
    }

    /**
     * @hook  apPhpConfigBeforeUpdate
     * @since 2.3.0
     */
    public function hookApPhpConfigBeforeUpdate()
    {
        global $update;

        if (!$update) {
            return;
        }

        foreach ($GLOBALS['update'] as $option) {
            if ('pdf_export_logo' === $option['where']['Key'] && $fileName = $option['fields']['Default']) {
                if ($fileName && !is_file(self::PLUGIN_DIR . 'static/' . $fileName)) {
                    $GLOBALS['reefless']->loadClass('Notice');
                    $GLOBALS['rlNotice']->saveNotice($GLOBALS['lang']['pdf_export_image_error'], 'errors');
                    $update = [];
                }
                break;
            }
        }
    }

    /**
     * @hook  apMixConfigItem
     *
     * @since 2.4.0
     *
     * @param  array $value
     * @param  array $systemSelects - Required configs with "select" type
     */
    public function hookApMixConfigItem(&$value, &$systemSelects = null)
    {
        if ($value['Key'] !== 'pdf_account_form') {
            return;
        }

        $systemSelects[] = 'pdf_account_form';
    }

    /**
     * Update to 2.2.0 version
     */
    public function update220()
    {
        $GLOBALS['reefless']->deleteDirectory(self::PLUGIN_DIR . 'tcpdf/');
    }

    /**
     * Update to 2.3.0 version
     */
    public function update230(): void
    {
        @rename(self::PLUGIN_DIR . 'no-photo.jpg', self::PLUGIN_DIR . 'static/no-photo.jpg');
        @rename(self::PLUGIN_DIR . 'pdf.png', self::PLUGIN_DIR . 'static/icon.png');
    }

    /**
     * Update to 2.5.0 version
     */
    public function update250(): void
    {
        global $rlDb;

        if (array_key_exists('ru', $GLOBALS['languages'])) {
            $russianTranslation = json_decode(file_get_contents(RL_UPLOAD . 'PdfExport/i18n/ru.json'), true);

            foreach ($russianTranslation as $phraseKey => $phraseValue) {
                if (!in_array($phraseKey, ['title_PdfExport', 'description_PdfExport'])) {
                    continue;
                }

                if (!$rlDb->getOne('ID', "`Key` = '{$phraseKey}' AND `Code` = 'ru'", 'lang_keys')) {
                    $insertPhrase = $rlDb->fetch(
                        ['Module', 'Key', 'Plugin'],
                        ['Code' => $GLOBALS['config']['lang'], 'Key' => $phraseKey],
                        null, 1, 'lang_keys', 'row'
                    );

                    $insertPhrase['Code']  = 'ru';
                    $insertPhrase['Value'] = $phraseValue;

                    $rlDb->insertOne($insertPhrase, 'lang_keys');
                } else {
                    $rlDb->updateOne([
                        'fields' => ['Value' => $phraseValue],
                        'where' => ['Key'   => $phraseKey, 'Code' => 'ru'],
                    ], 'lang_keys');
                }
            }
        }
    }
}
