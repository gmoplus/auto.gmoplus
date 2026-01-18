<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: RLSELLERREVIEWS.CLASS.PHP
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
 *  Flynax Classifieds Software 2024 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

use Flynax\Abstracts\AbstractPlugin;
use Flynax\Interfaces\PluginInterface;
use Flynax\Plugins\SellerReviews\SellerComment;
use Flynax\Plugins\SellerReviews\SellerReviews;

require __DIR__ . '/vendor/autoload.php';

/**
 * General Seller Reviews/Rating class
 */
class rlSellerReviews extends AbstractPlugin implements PluginInterface
{
    /**
     * @var SellerReviews
     */
    protected $plugin;

    /**
     * @var
     */
    protected $rlDb;

    /**
     * @var
     */
    protected $rlSmarty;

    /**
     * @var
     */
    protected $rlAccount;

    /**
     * @var array
     */
    protected $pageInfo = [];

    /**
     * @var array
     */
    protected $lang = [];

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var array
     */
    protected $seller = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->plugin    = new SellerReviews();
        $this->rlDb      = &$GLOBALS['rlDb'];
        $this->rlSmarty  = &$GLOBALS['rlSmarty'];
        $this->rlAccount = &$GLOBALS['rlAccount'];
        $this->pageInfo  = &$GLOBALS['page_info'];
        $this->lang      = &$GLOBALS['lang'];
        $this->config     = &$GLOBALS['config'];

        define('SRR_VIEW_PATH', SellerReviews::VIEW_PATH);
        define('SRR_ROOT_URL', RL_PLUGINS_URL . 'sellerReviews/');
    }

    /**
     * @hook tplHeader
     * @return void
     * @throws Exception
     */
    public function hookTplHeader(): void
    {
        if ($this->isValidPage()) {
            $this->assignAccountInfo();
            $this->plugin::view($this->rlSmarty, 'header');
        }
    }

    /**
     * @hook tplFooter
     * @return void
     * @throws Exception
     */
    public function hookTplFooter(): void
    {
        if ($this->isValidPage()) {
            $this->assignAccountInfo();
            $this->plugin::view($this->rlSmarty, 'footer');
            $this->plugin::view($this->rlSmarty, 'new_comment_form');
        }
    }

    /**
     * @todo - Remove this hook when compatibility will be > 4.9.0
     * @hook listingDetailsSellerBox
     * @return void
     * @throws Exception
     */
    public function hookListingDetailsSellerBox(): void
    {
        if ($this->isValidPage() && version_compare($this->config['rl_version'], '4.9.0') <= 0) {
            $this->assignAccountInfo();
            $this->plugin::view($this->rlSmarty, 'account_rating');
        }
    }

    /**
     * @since 1.1.0
     * @hook tplSellerBoxAfterName
     * @return void
     * @throws Exception
     */
    public function hookTplSellerBoxAfterName(): void
    {
        if ($this->isValidPage() && version_compare($this->config['rl_version'], '4.9.0') > 0) {
            $this->assignAccountInfo();
            $this->plugin::view($this->rlSmarty, 'account_rating');
        }
    }

    /**
     * @hook ajaxRequest
     * @param $out
     * @param $mode
     * @return void
     */
    public function hookAjaxRequest(&$out, $mode): void
    {
        if (!$this->plugin->isValidAjax($mode)) {
            return;
        }

        $sellerComment = new SellerComment();

        switch ($mode) {
            case 'srrGetComments':
                $out = $sellerComment->loadCommentsInPage($_REQUEST);
                break;
            case 'srrAddNewComment':
                $out = $sellerComment->addComment($_REQUEST);
                break;
            case 'srrLoadAccountRating':
                $out = $sellerComment->getAccountRating($_REQUEST);
                break;
        }
    }

    /**
     * @param $rlStatic
     */
    public function hookStaticDataRegister($rlStatic): void
    {
        if ($this->isValidPage()) {
            $rlStatic = $rlStatic ?: $GLOBALS['rlStatic'];
            $rlStatic->addJS(SRR_ROOT_URL . 'static/lib.js');
        }
    }

    /**
     * @hook phpDeleteAccountDetails
     *
     * @param $id
     *
     * @return void
     */
    public function hookPhpDeleteAccountDetails($id): void
    {
        $this->plugin::removeSellerReviews($id, $this->rlDb);
    }

    /**
     * @hook deleteAccountSetItems
     *
     * @param $id
     *
     * @return void
     */
    public function hookDeleteAccountSetItems($id): void
    {
        $this->plugin::removeSellerReviews($id, $this->rlDb);
    }

    /**
     * @hook apMixConfigItem
     *
     * @param array|null $value
     * @param array|null $systemSelects - Required configs with "select" type
     */
    public function hookApMixConfigItem(array &$value = null, array &$systemSelects = null): void
    {
        if ($value['Key'] === 'srr_display_mode' && false === in_array('srr_display_mode', $systemSelects, true)) {
            $systemSelects[] = 'srr_display_mode';
        }
    }

    /**
     * @hook apAjaxRequest
     */
    public function hookApAjaxRequest(&$out = null, $item = null): void
    {
        if ($item === 'srrDeleteComment') {
            $sellerComment = new SellerComment();
            $out = ['status' => $sellerComment->removeComment((int)$_REQUEST['id']) ? 'OK' : 'ERROR'];
        }
    }

    /**
     * Detects system page available for showing rating/comments data
     *
     * @return bool
     */
    protected function isValidPage(): bool
    {
        // Block plugin functions for individual accounts
        if (defined('IS_ESCORT') && $this->pageInfo['Controller'] === 'my_messages' && $_GET['id']) {
            $contact = $this->rlAccount->getProfile((int) $_GET['id']);

            if ($contact['Escort_Type'] === $GLOBALS['rlEscort']::PERSONAL) {
                return false;
            }
        }

        return ($this->pageInfo['Controller'] === 'listing_type' && $_GET['listing_id'])
            || ($this->pageInfo['Controller'] === 'account_type'
                && ($_GET['id'] || $_GET['nvar_1'])
                && ($this->rlAccount->getProfile($_GET['id'] ? (int) $_GET['id'] : $_GET['nvar_1']))
            )
            || ($this->pageInfo['Controller'] === 'my_messages' && $_GET['id'])
            || $this->pageInfo['Controller'] === 'listing_details';
    }

    /**
     * @return void
     */
    protected function assignAccountInfo(): void
    {
        if (!$this->seller) {
            switch ($this->pageInfo['Controller']) {
                case 'account_type':
                    if ($GLOBALS['account']) {
                        $this->seller = $GLOBALS['account'];
                    }
                    break;
                case 'listing_details':
                    if ($GLOBALS['seller_info']) {
                        $this->seller = $GLOBALS['seller_info'];
                    }
                    break;
                case 'my_messages':
                    if ($GLOBALS['contact']) {
                        $this->seller = $GLOBALS['contact'];
                    }
                    break;
            }
        }

        $this->plugin::assignAccountInfo($this->seller, $this->rlSmarty);
    }

    /**
     * @return void
     */
    public function install(): void
    {
        $this->plugin->createSystemTable($this->rlDb);
        $this->plugin->createCountTriggers($this->rlDb);
        $this->plugin->addCommentsCountColumn($this->rlDb);
        $this->plugin->addAccountRatingColumn($this->rlDb);

        /**
         * @todo Remove it when compatibility will be >= 4.8.1
         */
        if (version_compare($this->config['rl_version'], '4.8.1') < 0) {
            $this->fixPhraseScopes();
        }
    }

    /**
     * @return void
     */
    public function uninstall(): void
    {
        $this->plugin->removeSystemTable($this->rlDb);
        $this->plugin->dropCommentsCountColumn($this->rlDb);
        $this->plugin->dropAccountRatingColumn($this->rlDb);
    }

    /**
     * Update to 1.1.0 version
     * @return void
     */
    public function update110(): void
    {
        $this->rlDb->addColumnToTable(
            'IP',
            "VARCHAR (39) NOT NULL DEFAULT '' AFTER `Author_Name`",
            SellerReviews::TABLE
        );

        $this->rlDb->query(
            "UPDATE `{db_prefix}config` SET `Position` = `Position` + 1
             WHERE `Key` IN ('srr_rating_group', 'srr_rating_module', 'srr_stars_number')
             AND `Plugin` = 'sellerReviews'"
        );
    }

    /**
     * @todo Remove it when compatibility will be >= 4.8.1
     * @return void
     */
    public function fixPhraseScopes(): void
    {
        $this->rlDb->query(
            "UPDATE `{db_prefix}lang_keys`
             SET `Module` = 'common'
             WHERE `Module` = '' AND `Plugin` = 'sellerReviews'"
        );
    }
}
