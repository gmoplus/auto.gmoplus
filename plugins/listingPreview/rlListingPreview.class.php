<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLLISTINGPREVIEW.CLASS.PHP
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

class rlListingPreview
{
    /**
     * enable preview step
     *
     * @since 2.3.0
     */
    public function hookAddListingTop()
    {
        $this->addStep();
    }

    private function addStep()
    {
        global $steps;

        $preview_step = array(
            'name'    => $GLOBALS['lang']['listingPreview_preview'],
            'path'    => 'preview',
            'caption' => true,
            'plugin'  => 'listingPreview',
            'class'   => 'rlListingPreview',
            'method'  => 'step',
            'tpl'     => 'step'
        );

        // Find checkout step index
        $index = array_search('checkout', array_keys($steps));

        if (!$index) {
            $index = count($steps);
        }

        // Add step
        $steps = array_merge(
            array_slice($steps, 0, $index, true), 
            array('preview' => $preview_step),
            array_slice($steps, $index , null, true)
        );
    }

    /**
     * Enable sidebar and rewrite page controller
     *
     * @since 2.3.0
     */
    public function hookTplHeaderUserNav()
    {
        global $rlSmarty;

        if (!is_object($this->manageListing)) {
            return;
        }

        if ($this->manageListing->step != 'preview') {
            return;
        }

        // Enable sidebar
        $rlSmarty->assign('side_bar_exists', true);

        // Rewrite page controller
        $page_info = $rlSmarty->_tpl_vars['pageInfo'];
        $page_info['Controller'] = 'listing_details';

        unset($rlSmarty->_tpl_vars['pageInfo']);
        $rlSmarty->assign('pageInfo', $page_info);

        $GLOBALS['page_info']['Controller'] = 'add_listing';
    }

    /**
     * Replace body class which indicates the current page key
     *
     * @since 2.3.0
     */
    public function hookSmartyFetchHook(&$compiled_content, &$resource_name)
    {
        if (is_object($this->manageListing)) {
            $compiled_content = preg_replace('/(add\-listing\-page)/', 'view-details-page', $compiled_content);
        }
    }

    /**
     * Replace the current page name with listing title
     */
    public function hookListingDetailsTopTpl()
    {
        if (!is_object($this->manageListing)) {
            return;
        }

        if ($this->manageListing->step != 'preview') {
            return;
        }

        // Remove account_id from the data to disable "Edit Listing" button
        $GLOBALS['rlSmarty']->_tpl_vars['listing_data']['Account_ID'] = false;

        // Get listing title
        $listing_title = $GLOBALS['rlListings']->getListingTitle(
            $this->manageListing->category['ID'],
            $this->manageListing->listingData,
            $this->manageListing->listingType['Key']
        );

        // Set listing title as page name
        $GLOBALS['page_info']['name'] = $listing_title;

        // Replace page controller in js to allow picGallery() working
        echo <<< HTML
        <script>rlPageInfo['controller'] = 'listing_details';</script>
HTML;
    }

    /**
     * Disable login indicator to show full contact form in sidebar
     *
     * @since 2.4.3
     *
     * @hook listingDetailsSellerBox
     */
    public function hookListingDetailsSellerBox()
    {
        if (!is_object($this->manageListing)) {
            return;
        }

        if ($this->manageListing->step != 'preview') {
            return;
        }

        $GLOBALS['rlSmarty']->assign('isLogin', false);
    }

    /**
     * Add hide buttons styles
     *
     * @since 2.4.5
     * @hook tplHeader
     */
    public function hookTplHeader()
    {
        if (!is_object($this->manageListing)) {
            return;
        }

        if ($this->manageListing->step != 'preview') {
            return;
        }

        echo <<< HTML
        <style>
        .contact-owner-navbar {
            display: none !important;
        }
        </style>
HTML;
    }

    /**
     * prepare data for listing preview stap
     */
    public function step($instance)
    {
        global $rlDb, $page_info, $lang, $rlSmarty, $rlHook, $rlXajax, $config, $rlLang,
               $reefless, $l_youtube_thumbnail, $rlListings, $rlAccount, $rlListingTypes,
               $blocks, $tpl_settings, $rlStatic;

        $this->manageListing = $instance;

        $page_info['name'] = $lang['listingPreview_preview'];

        // Membership plans support
        if (is_object($GLOBALS['rlMembershipPlan'])) {
            $rlSmarty->assign('allow_photos', true);
            $rlSmarty->assign('allow_contacts', true);
            $rlSmarty->assign('allow_send_message', true);
            $GLOBALS['rlMembershipPlan']->is_contact_allowed = true;
        }

        // Redirect to the next step if the listing preview confirmed
        if ($_POST['step'] == 'preview') {
            $instance->redirectToNextStep();
            exit;
        }

        // Disable h1
        if ((bool) preg_match('/_modern$/', $config['template'])
            || $tpl_settings['name'] == 'escort_sun_cocktails_wide'
        ) {
            $rlSmarty->assign('no_h1', true);
        }

        // Assign data
        $rlSmarty->assign_by_ref('listing_data', $instance->listingData);
        $rlSmarty->assign_by_ref('listing_type', $instance->listingType);

        $rlHook->load('listingDetailsTop');

        // Build listing structure
        $category_id = $instance->category['ID'];
        $listing = $rlListings->getListingDetails($category_id, $instance->listingData, $instance->listingType);
        $rlSmarty->assign('listing', $listing);

        // Get seller information
        $account_id = (int) $instance->listingData['Account_ID'];
        $seller_info = $rlAccount->getProfile($account_id);
        $rlSmarty->assign_by_ref('seller_info', $seller_info);

        $owner_short_details = $rlAccount->getShortDetails($seller_info, $seller_info['Account_type_ID']);
        $rlSmarty->assign_by_ref('owner_short_details', $owner_short_details);

        // Build location data corresponding to user account
        if ($config['address_on_map'] && $instance->listingData['account_address_on_map']) {
            $location = $rlAccount->mapLocation;
        }
        // Get location data from the listing
        else {
            $fields_list = $rlListings->fieldsList;

            $location = false;
            foreach ($fields_list as $key => $value) {
                if ($fields_list[$key]['Map'] && !empty($instance->listingData[$fields_list[$key]['Key']])) {
                    $mValue = str_replace("'", "\'", $value['value']);
                    $location['search'] .= $mValue . ', ';
                    $location['show'] .= $lang[$value['pName']] . ': <b>' . $mValue . '<\/b><br />';
                    unset($mValue);
                }
            }

            if (!empty($location)) {
                $location['search'] = substr($location['search'], 0, -2);
            }

            if ($instance->listingData['Loc_latitude'] && $instance->listingData['Loc_longitude']) {
                $location['direct'] = $instance->listingData['Loc_latitude'] . ',' . $instance->listingData['Loc_longitude'];
            }
        }
        $rlSmarty->assign_by_ref('location', $location);

        $plan_info = $instance->plans[$instance->planID];
        $photos_limit = $plan_info['Image_unlim'] ? true : $plan_info['Image'];
        $videos_limit = $plan_info['Video_unlim'] ? true : $plan_info['Video'];

        // Get listing media
        $media = Flynax\Utils\ListingMedia::get(
            $instance->listingID,
            $photos_limit,
            $videos_limit
        );

        if ($media && method_exists($rlStatic, 'addComponentCSS')) {
            $rlStatic->addComponentCSS('listingDetailsGalleryComponents', 'listing-details-gallery');
        }

        if ($config['show_call_owner_button']) {
            $rlStatic->addHeaderCss(RL_TPL_BASE . 'components/call-owner/call-owner-buttons.css');
        }

        /**
         * Remove photo index for "first video" case
         *
         * @todo Remove from 4.7.2 software version
         */
        if ($media && $media[0]['Type'] == 'video') {
            unset($media[0]['Photo']);
        }

        $rlSmarty->assign_by_ref('photos', $media);

        // Get amenities
        if ($config['map_amenities']) {
            $rlDb->setTable('map_amenities');
            $amenities = $rlDb->fetch(array('Key', 'Default'), array('Status' => 'active'), "ORDER BY `Position`");
            $amenities = $rlLang->replaceLangKeys($amenities, 'map_amenities', array('name'));
            $rlSmarty->assign_by_ref('amenities', $amenities);
        }

        // Populate tabs
        $tabs = array(
            'listing' => array(
                'key' => 'listing',
                'name' => $lang['listing']
            ),
            'tell_friend' => array(
                'key' => 'tell_friend',
                'name' => $lang['tell_friend']
            )
        );

        if ($page_info['Listing_details_inactive'] || !$config['tell_a_friend_tab']) {
            unset($tabs['tell_friend']);
        }

        $rlSmarty->assign_by_ref('tabs', $tabs);

        // Register ajax methods
        if (is_object($rlXajax)) {
            $rlXajax->registerFunction(array('tellFriend', $rlListings, 'ajaxTellFriend'));
        }

        $rlHook->load('listingDetailsBottom');

        $instance->listingData['Account_ID'] = false; // it will disable Edit Listing button
    }
}
