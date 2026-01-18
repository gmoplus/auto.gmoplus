<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: RLQRCODE.CLASS.PHP
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

use Flynax\Abstracts\AbstractPlugin;
use Flynax\Interfaces\PluginInterface;

require __DIR__ . '/phpqrcode/qrlib.php';

/**
 * QR code class
 */
class rlQrCode extends AbstractPlugin implements PluginInterface
{
    /**
     * @var string
     */
    private $_sData = '';

    /**
     * @param $id
     *
     * @since 1.1.1 - $user_id parameter removed
     * @return false|void
     */
    public function generateQR_Code($id = false)
    {
        global $reefless, $rlDb, $config;

        $reefless->loadClass('Valid');
        $reefless->loadClass('Listings');
        $reefless->loadClass('ListingTypes');
        $reefless->loadClass('Account');

        if (!$id) {
            return false;
        }

        if (!is_dir(RL_FILES . 'qrcode')) {
            $reefless->rlMkdir(RL_FILES . 'qrcode');
        }

        $listing = $GLOBALS['rlListings']->getListing($id, true);

        if (!$listing) {
            return false;
        }

        $seller = $GLOBALS['rlAccount']->getProfile((int) $listing['Account_ID']);

        $data = [
            'ID' => $listing['ID'],
            'Account_ID' => $listing['Account_ID'],
            'Title' => $listing['listing_title'],
            'Link' => $listing['listing_link'],
            'Phone' => $this->getListingPhone($listing),
            'Account_phone' => $this->getPhone($seller),
            'Full_name' => trim($seller['Full_name']),
            'Filepath' => ''
        ];

        if ($seller['First_name'] || $seller['Last_name']) {
            $data['Name'] = trim($seller['First_name'] . ' ' . $seller['Last_name']);
        }

        if ($seller['Display_email']) {
            $data['Mail'] = trim($seller['Mail']);
        }

        $this->finish($data);
    }

    /**
     * @param $seller
     *
     * @return false|string
     */
    private function getPhone($seller)
    {
        global $config;
        if ($option = $config['qrCode_phone_field_account']) {
            if (isset($seller['Fields'][$option]) && $seller['Fields'][$option]) {
                return '+' . preg_replace('/[\D]/', '', $seller['Fields'][$option]['value']);
            }
        }
        return false;
    }

    /**
     * Get listing phone
     *
     * @since 1.1.1
     *
     * @param  array  $listing - Listing data
     * @return string          - Phone number
     */
    private function getListingPhone(array $listing): string
    {
        global $config, $reefless, $rlDb;

        if ($option = $config['qrCode_phone_field_name']) {
            if ($phone_raw = $listing[$option]) {
                $phone_data = $reefless->parsePhone($phone_raw);
                $phone = implode('', $phone_data);
                return '+' . preg_replace('/[\D]/', '', $phone);
            }
        }

        return '';
    }

    /**
     * Remove folder by user ID
     */
    public function remove_QR_ByUserID($user_id)
    {
        if (is_dir($qrCodeFolder = RL_FILES . 'qrcode/user_' . $user_id)) {
            $GLOBALS['reefless']->deleteDirectory($qrCodeFolder);
        }
    }

    /**
     * Remove folder by listing ID
     */
    public function remove_QR_ByListing($user_id, $listing_id)
    {
        $qrCodeDir = RL_FILES . 'qrcode' . RL_DS . 'user_' . $user_id;
        $qrCodeFile = $qrCodeDir . RL_DS . 'listing_' . $listing_id . '.png';
        if (file_exists($qrCodeFile)) {
            unlink($qrCodeFile);

            // Delete qrcode folder
            if ($qrCodeDir && scandir($qrCodeDir) && count(scandir($qrCodeDir)) === 2) {
                $GLOBALS['reefless']->deleteDirectory($qrCodeDir);
            }
        }
    }

    /**
     * Generate the QR code.
     *
     * @return void
     */
    public function finish($data)
    {
        $this->_sData = "BEGIN:VCARD\r\n";
        $this->_sData .= "VERSION:2.1\r\n";

        if (!empty($data['Title'])) {
            $this->note($data['Title']);
        }
        if (!empty($data['Mail'])) {
            $this->email($data['Mail']);
        }
        if (!empty($data['Phone'])) {
            $this->mobilePhone($data['Phone']);
        }
        if (!empty($data['Account_phone'])) {
            $this->workPhone($data['Account_phone']);
        }
        if (!empty($data['Name'])) {
            $this->name($data['Name']);
        }
        if (!empty($data['Full_name'])) {
            $this->nickName($data['Full_name']);
        }
        $this->url($data['Link']);
        $user_dir = RL_FILES . 'qrcode/user_' . $data['Account_ID'];
        if (!is_dir($user_dir)) {
            $GLOBALS['reefless']->rlMkdir($user_dir);
        }
        $this->_sData .= 'END:VCARD';
        $Filepath = $user_dir . RL_DS . 'listing_' . $data['ID'] . '.png';
        QRcode::png($this->_sData, $Filepath, QR_ECLEVEL_L, 3, 2);
    }

    /**
     * The name of the person.
     *
     * @param string $sName
     *
     * @return void this
     */
    public function name($sName): void
    {
        $this->_sData .= "N;CHARSET=UTF-8:" . $sName . "\r\n";
    }

    /**
     * The full name of the person.
     *
     * @param string $sFullName
     *
     * @return void
     */
    public function fullName($sFullName): void
    {
        $sFullName = preg_replace('/\s+/', ' ', $sFullName);
        $this->_sData .= 'FN;CHARSET=UTF-8:' . $sFullName . "\r\n";
    }

    /**
     * Delivery address.
     *
     * @param string $sAddress
     *
     * @return void this
     */
    public function address($sAddress): void
    {
        $this->_sData .= 'ADR:' . $sAddress . "\r\n";
    }

    /**
     * Nickname.
     *
     * @param string $sNickname
     *
     * @return void
     */
    public function nickName($sNickname): void
    {
        $this->_sData .= 'NICKNAME;CHARSET=UTF-8:' . $sNickname . "\r\n";
    }

    /**
     * Email address.
     *
     * @param string $sMail
     *
     * @return void this
     */
    public function email($sMail): void
    {
        $this->_sData .= 'EMAIL:' . $sMail . "\r\n";
    }

    /**
     * Work Phone.
     *
     * @param string $sVal
     *
     * @return void this
     */
    public function workPhone($sVal): void
    {
        $this->_sData .= 'TEL;WORK:' . $sVal . "\r\n";
    }

    /**
     * Home Phone.
     *
     * @param string $sVal
     *
     * @return void
     */
    public function homePhone($sVal): void
    {
        $phone = $GLOBALS['reefless']->parsePhone($sVal);
        $this->_sData .= "TEL;HOME:+" . implode('', $phone) . "\r\n";
    }

    /**
     * Mobile Phone.
     *
     * @param string $sVal
     *
     * @return void
     */
    public function mobilePhone($sVal): void
    {
        $this->_sData .= 'TEL;CELL:' . $sVal . "\r\n";
    }

    /**
     * URL address.
     *
     * @param string $sUrl
     *
     * @return void
     */
    public function url($sUrl): void
    {
        $sUrl = (substr($sUrl, 0, 4) != 'http') ? 'http://' . $sUrl : $sUrl;
        $this->_sData .= 'URL:' . $sUrl . "\r\n";
    }

    /**
     * SMS code.
     *
     * @param string $sPhone
     * @param string $sText
     *
     * @return void this
     */
    public function sms($sPhone, $sText): void
    {
        $this->_sData .= 'SMSTO:' . $sPhone . ':' . $sText . "\r\n";
    }

    /**
     * Birthday.
     *
     * @param string $sBirthday Date in the format YYYY-MM-DD or ISO 8601
     *
     * @return void this
     */
    public function birthday($sBirthday): void
    {
        $this->_sData .= 'BDAY:' . $sBirthday . "\r\n";
    }

    /**
     * Anniversary.
     *
     * @param string $sBirthDate Date in the format YYYY-MM-DD or ISO 8601
     *
     * @return void
     */
    public function anniversary($sBirthDate): void
    {
        $this->_sData .= 'ANNIVERSARY:' . $sBirthDate . "\r\n";
    }

    /**
     * Gender.
     *
     * @param string $sSex F = Female. M = Male
     *
     * @return void this
     */
    public function gender($sSex): void
    {
        $this->_sData .= 'GENDER:' . $sSex . "\r\n";
    }

    /**
     * A list of "tags" that can be used to describe the object represented by this vCard.
     *
     * @param $sCategories
     *
     * @return void
     */
    public function categories($sCategories): void
    {
        $this->_sData .= 'CATEGORIES:' . $sCategories . "\r\n";
    }

    /**
     * The instant messenger (Instant Messaging and Presence Protocol).
     *
     * @param string $sVal
     *
     * @return void this
     */
    public function impp($sVal): void
    {
        $this->_sData .= 'IMPP:' . $sVal . "\r\n";
    }

    /**
     * Photo (avatar).
     *
     * @param string $sImgUrl URL of the image.
     *
     * @return void this
     * @throws InvalidArgumentException If the image format is invalid.
     */
    public function photo($sImgUrl): void
    {
        $bIsImgExt = strtolower(substr(strrchr($sImgUrl, '.'), 1)); // Get the file extension.

        if ($bIsImgExt === 'jpeg' || $bIsImgExt === 'jpg' || $bIsImgExt === 'png' || $bIsImgExt === 'gif') {
            $sExt = strtoupper($bIsImgExt);
        } else {
            throw new InvalidArgumentException('Invalid format Image!');
        }

        $this->_sData .= 'PHOTO;VALUE=URL;TYPE=' . $sExt . ':' . $sImgUrl . "\r\n";
    }

    /**
     * The role, occupation, or business category of the vCard object within an organization.
     *
     * @param string $sRole e.g.: Executive
     *
     * @return void
     */
    public function role($sRole): void
    {
        $this->_sData .= 'ROLE:' . $sRole . "\r\n";
    }

    /**
     * The supplemental information or a comment that is associated with the vCard.
     *
     * @param string $sText
     *
     * @return void
     */
    public function note($sText): void
    {
        $this->_sData .= 'NOTE;CHARSET=UTF-8:' . $sText . "\r\n";
    }

    /**
     * Bookmark.
     *
     * @param string $sTitle
     * @param string $sUrl
     *
     * @return void
     */
    public function bookmark($sTitle, $sUrl): void
    {
        $this->_sData .= 'MEBKM:TITLE:' . $sTitle . ';URL:' . $sUrl . "\r\n";
    }

    /**
     * Geolocation.
     *
     * @param string  $sLat    Latitude
     * @param string  $sLon    Longitude
     * @param integer $iHeight Height
     *
     * @return void
     */
    public function geo($sLat, $sLon, $iHeight): void
    {
        $this->_sData .= 'GEO:' . $sLat . ',' . $sLon . ',' . $iHeight . "\r\n";
    }

    /**
     * The language that the person speaks.
     *
     * @param string $sLang e.g.: en-US
     *
     * @return void
     */
    public function lang($sLang): void
    {
        $this->_sData .= 'LANG:' . $sLang . "\r\n";
    }

    /**
     * Wifi.
     *
     * @param string $sType
     * @param string $sSsid
     * @param string $sPwd
     *
     * @return void
     */
    public function wifi($sType, $sSsid, $sPwd): void
    {
        $this->_sData .= 'WIFI:T:' . $sType . ';S' . $sSsid . ';' . $sPwd . "\r\n";
    }

    /**
     * Plugin installation process
     *
     * @since 1.1.1
     *
     * @return void
     */
    public function install(): void
    {
        global $reefless;
        if (!is_dir($qrCodeFolder = RL_FILES . 'qrcode/')) {
            $reefless->rlMkdir($qrCodeFolder);
        }
    }

    /**
     * Plugin uninstallation process
     *
     * @return void
     */
    public function uninstall(): void
    {
        if (is_dir($qrCodeFolder = RL_FILES . 'qrcode/')) {
            $GLOBALS['reefless']->deleteDirectory($qrCodeFolder);
        }
    }

    /**
     * @hook listingDetailsAfterStats
     *
     * @since 1.1.1
     *
     * @return void
     */
    public function hookListingDetailsAfterStats(): void
    {
        global $reefless, $page_info, $listing_data, $listing_id, $rlSmarty;

        if ($page_info['Controller'] !== 'listing_details' || !$listing_data || !$listing_id) {
            return;
        }

        $userFolder = RL_FILES . "qrcode/user_{$listing_data['Account_ID']}/";

        if (!is_dir($userFolder)) {
            $reefless->rlMkdir($userFolder);
        }

        if (!file_exists($userFolder . "listing_{$listing_id}.png")) {
            $this->generateQR_Code((int) $listing_id);
        }

        $rlSmarty->display(RL_PLUGINS . 'qrCode/qrCode.tpl');
    }

    /**
     * @hook apPhpListingsAfterAdd
     *
     * @since 1.1.1
     *
     * @return void
     */
    public function hookApPhpListingsAfterAdd(): void
    {
        $this->generateQR_Code((int) $GLOBALS['listing_id']);
    }

    /**
     * @hook apPhpListingsAfterEdit
     *
     * @since 1.1.1
     *
     * @return void
     */
    public function hookApPhpListingsAfterEdit(): void
    {
        $this->generateQR_Code((int) $GLOBALS['listing_id']);
    }

    /**
     * @hook apPhpAccountsAfterEdit
     *
     * @since 1.1.1
     *
     * @return void
     */
    public function hookApPhpAccountsAfterEdit(): void
    {
        $this->remove_QR_ByUserID((int) $_GET['account']);
    }

    /**
     * @hook afterListingCreate
     *
     * @since 1.1.1
     *
     * @param $instance
     *
     * @return void
     */
    public function hookAfterListingCreate($instance): void
    {
        if (!$instance || !$instance->listingID) {
            return;
        }

        $this->generateQR_Code((int) $instance->listingID);
    }

    /**
     * @hook afterListingUpdate
     *
     * @since 1.1.1
     *
     * @param $instance
     *
     * @return void
     */
    public function hookAfterListingUpdate($instance): void
    {
        if (!$instance || !$instance->listingID) {
            return;
        }

        $this->generateQR_Code((int) $instance->listingID);
    }

    /**
     * @hook afterListingEdit
     *
     * @since 1.1.1
     *
     * @return void
     */
    public function hookAfterListingEdit(): void
    {
        $this->generateQR_Code((int) $GLOBALS['listing_id']);
    }

    /**
     * @hook profileController
     *
     * @since 1.1.1
     *
     * @return void
     */
    public function hookProfileController(): void
    {
        $this->remove_QR_ByUserID((int) $GLOBALS['profile_info']['ID']);
    }

    /**
     * @hook  phpListingsAjaxDeleteListing
     *
     * @since 1.1.1
     *
     * @param $listing
     *
     * @return void
     */
    public function hookPhpListingsAjaxDeleteListing($listing): void
    {
        if (!$listing || !$listing['ID']) {
            return;
        }

        if ($accountID = $GLOBALS['rlDb']->getOne('Account_ID', "`ID` = '{$listing['ID']}'", 'listings')) {
            $this->remove_QR_ByListing($accountID, $listing['ID']);
        }
    }

    /**
     * @hook  phpDeleteListingData
     *
     * @since 1.1.1
     *
     * @param $listingID
     *
     * @return void
     */
    public function hookPhpDeleteListingData($listingID): void
    {
        if (!$listingID) {
            return;
        }

        if ($accountID = $GLOBALS['rlDb']->getOne('Account_ID', "`ID` = '{$listingID}'", 'listings')) {
            $this->remove_QR_ByListing($accountID, $listingID);
        }
    }

    /**
     * @hook apMixConfigItem
     *
     * @since 1.1.0
     *
     * @param array|null $config
     */
    public function hookApMixConfigItem(?array &$config): void
    {
        global $rlDb;

        if (in_array($config['Key'], ['qrCode_phone_field_name', 'qrCode_phone_field_account'])) {
            $mode = $config['Key'] == 'qrCode_phone_field_account' ? 'account' : 'listing';
            $config['Values'] = array();

            $rlDb->setTable($mode . '_fields');
            $fields = $rlDb->fetch(['Key'], ['Status' => 'active'], "AND `Type` IN ('text','number', 'phone')");

            foreach ($fields as $item) {
                $config['Values'][] = array(
                    'ID'   => $item['Key'],
                    'name' => $GLOBALS['lang'][$mode . '_fields+name+' . $item['Key']],
                );
            }
        }
    }

    /**
     * Update to 1.1.0 version
     */
    public function update110(): void
    {
        global $rlDb;

        if (in_array('ru', array_keys($GLOBALS['languages']))) {
            $russianTranslation = json_decode(file_get_contents(RL_PLUGINS . 'qrCode/i18n/ru.json'), true);
            foreach ($russianTranslation as $phraseKey => $phrase) {
                if ($rlDb->getOne('ID', "`Key` = '{$phraseKey}' AND `Code` = 'ru'", 'lang_keys')) {
                    $rlDb->updateOne([
                        'fields' => ['Value' => $phrase],
                        'where' => ['Key' => $phraseKey, 'Code' => 'ru'],
                    ], 'lang_keys');
                } else {
                    $rlDb->insertOne([
                        'Code' => 'ru',
                        'Module' => 'common',
                        'Key' => $phraseKey,
                        'Value' => $russianTranslation[$phraseKey],
                        'Plugin' => 'qrCode',
                    ], 'lang_keys');
                }
            }
        }
    }

    /**
     * Update to 1.1.1 version
     */
    public function update111(): void
    {
        $GLOBALS['rlDb']->query("
            DELETE FROM `{db_prefix}hooks` WHERE `Plugin` = 'qrCode' AND `Name` = 'apPhpListingsAjaxDeleteListing'
        ");
    }
}
