<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: RLHIGHLIGHT.CLASS.PHP
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

class rlHighlight
{
    /**
     * @since 1.4.0
     */
    const HIGLIGHT_HTML_CLASS = 'highlight';
    /**
     * @var string - plan type
     */
    private $plan_type = 'highlight';
    
    /**
     * @var string - default ordering field
     */
    private $orderBy = 'HighlightDate';

    /**
     * Highlight listing.
     *
     * @param  int  $listing_id - ID of the Listing
     * @param  int  $plan_id    - ID of the Highlight Plan
     * @param  int  $accountID
     * @return bool $result     - Status of the listing higlighting
     */
    public function highlight($listing_id, $plan_id, $accountID = 0)
    {
        global $rlDb, $rlMonetize;

        $accountID = $accountID ?: $GLOBALS['account_info']['ID'];

        if (!$listing_id || !$plan_id || !$accountID) {
            return false;
        }

        if (!$GLOBALS['rlMonetize']) {
            $GLOBALS['reefless']->loadClass('Monetize', null, 'monetize');
        }

        $plan_info = $rlMonetize->getPlanInfo($plan_id, 'highlight');
        $plan_using = $rlMonetize->getPlanUsing($plan_id, $accountID);

        if (!$plan_info) {
            return false;
        }

        $result = false;
        
        // check should I just increase days
        if ($highlight_info = $this->alreadyHighlighted($listing_id)) {
            $when_was_highlighted = new DateTime($highlight_info['HighlightDate']);
        } else {
            $when_was_highlighted = new DateTime();
        }

        $new_highlightDate = $when_was_highlighted->add(new DateInterval(sprintf('P%dD', $plan_info['Days'])));
        
        $updateData = array(
            'fields' => array(
                'HighlightDate' => $new_highlightDate->format('Y-m-d H:i:s'),
                'Highlight_Plan' => $plan_info['Plan_ID'],
            ),
            'where' => array(
                'ID' => $listing_id,
            ),
        );

        if ($rlDb->updateOne($updateData, 'listings')) {
            if ($plan_using) {
                if (!$plan_using['Is_unlim'] && $plan_info['Highlights'] > 0) {
                    if ($plan_using['Highlights_available'] == 1) {
                        $rlMonetize->removePlanUsingRow($plan_using['ID']);
                    } else {
                        $updateData = array(
                            'fields' => array(
                                'Highlights_available' => $plan_using['Highlights_available'] - 1,
                            ),
                            'where' => array(
                                'ID' => $plan_using['ID'],
                            ),
                        );
                        $rlDb->updateOne($updateData, 'monetize_using');
                    }
                }
            } else {
                $this->addPlanUsing($plan_info, $accountID, true);
            }
            $result = true;
        }

        return $result;
    }


    /**
     * Add plan using depending on the package. It can be Listing plan or Highlight plan.
     *
     * @since  1.3.0   Added - $accountID
     *
     * @param  array  $plan           - Plan info.
     * @param  string $type           - Plan type: {highlight/listing}
     * @param  int     $accountID     - Account ID on which you want to assign credits
     * @param  bool    $subtract
     *
     * @return bool                   - Is successfully added
     */
    public function addPlanUsing($plan = array(), $accountID = 0, $subtract = false)
    {
        global $lang, $rlDb;

        if (!is_array($plan)) {
            return false;
        }

        $plan_id = $plan['ID'];

        $highlights = $plan['Highlights'] ? $plan['Highlights'] : 0;
        $days_highlight = $plan['Days'] ?: 0;
        $is_unlim = $plan['Highlights'] > 0 ? 0 : 1;

        $data = array(
            'Account_ID' => $accountID ?: $GLOBALS['account_info']['ID'],
            'Plan_ID' => $plan['ID'],
            'Plan_type' => 'highlight',
            'Date' => 'NOW()',
            'Highlights_available' => $highlights > 0 && $subtract ? $highlights - 1 : $highlights,
            'Days_highlight' => $days_highlight,
            'Is_unlim' => $is_unlim,
        );

        return $rlDb->insertOne($data, 'monetize_using');
    }

    /**
     * Getting highlight information by Listing plan / package.
     *
     * @param int $plan_id - Listing plan package ID
     * @return array $plan - Highlight information depending on plan
     */
    public function getHighlightByPlan($plan_id)
    {
        $sql = "SELECT `Highlight`, `Days_highlight`  FROM `{db_prefix}listing_plans` WHERE `ID` = {$plan_id} ";
        $plan = $GLOBALS['rlDb']->getRow($sql);

        return $plan;
    }

    /**
     * Prepare highlight tab data
     */
    public function prepareTab()
    {
        global $lang, $account_info, $rlMonetize;

        if (!$account_info['ID']) {
            return false;
        }

        $GLOBALS['tabs']['highlight_credits'] = array(
            'key' => 'highlight',
            'name' => $lang['m_highlight_credits'],
        );

        $info['highlights'] = $rlMonetize->getCredits($account_info['ID'], false, 'highlight');

        $accountID = (int) $account_info['ID'];
        $sql = "SELECT `Date` FROM `{db_prefix}monetize_using` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}monetize_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T2`.`Type` = 'highlight' AND `T1`.`Account_ID` = {$accountID} ";
        $sql .= "ORDER BY `T1`.`Date` DESC LIMIT 1";
        $row = $GLOBALS['rlDb']->getRow($sql);

        if ($row) {
            $info['last_purchased'] = $row['Date'];
        }

        // get last highlighted listings
        $GLOBALS['modify_highlight_where'] = true;
        $listings = $GLOBALS['rlListings']->getMyListings(
            'monetizeAll',
            $this->orderBy,
            'asc',
            0,
            $GLOBALS['config']['listings_per_page']
        );
        foreach ($listings as $key => $listing) {
            $plan_info = $rlMonetize->getPlanInfo($listing['Highlight_Plan']);
            $now = new DateTime();
            $highlight_end = new DateTime($listing['HighlightDate']);
            $time_left = $now->diff($highlight_end);
            $listings[$key]['expiring_status'] = $lang['m_unhighlighted'];
            $left_phrase_array = array();
            
            if($time_left->d > 0 && !$time_left->invert) {
                $left_phrase_array[] = str_replace('{days}', $time_left->d, $lang['m_highlight_days']);
            }
            if($time_left->h > 0 && !$time_left->invert) {
                $left_phrase_array[] = str_replace('{hours}', $time_left->h, $lang['m_highlight_hours']);
            }
            
            if($left_phrase_array) {
                $listings[$key]['expiring_status'] = implode(',', $left_phrase_array) . $lang['m_highlight_left'];
            }
        }
        $info['highlightListings'] = $listings;
        unset($GLOBALS['modify_highlight_where']);

        $link = $rlMonetize->getNotEmptyListingType();
        $info['link'] = $GLOBALS['reefless']->getPageUrl($link);

        $GLOBALS['rlSmarty']->assign_by_ref('hInfo', $info);
    }

    /**
     * Modify <article> tags.
     *
     * @param  string $html - HTML container where all <articles> is located (<section id="listings"> in our case).
     * @return string $html - Modified tags.
     */
    function modifyArticles($html)
    {
        preg_match_all("/<article[^\>]*>[\s\S]*?<\/article>/", $html, $output_array);
        $articles = $output_array[0];

        foreach ($articles as $article) {
            if ($this->hasElement($article)) {
                $modified_article = $this->addClass('highlight', $article);
                $html = str_replace($article, $modified_article, $html);
            }
        }

        return $html;
    }

    /**
     * Checking if child exist in the element.
     *
     * @param  string $html - Element, where child nodes will be search.
     * @return bool         - Did element found? True or false
     */
    function hasElement($html)
    {
        $re = '/<i .*class="highlight .*"[^\>]*>[\s\S]*?<\/i>/m';
        preg_match_all($re, $html, $is_exist);
        $is_exist = array_filter($is_exist);
        if (!empty($is_exist)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Add class to the element (article in our case).
     *
     * @param  string $class       - Class which you wan't to add
     * @param  string $element     - Element to which class will be added
     * @return string $new_element - Changed element
     */
    function addClass($class, $element)
    {
        $old_classes = $this->getClasses($element);
        $new_classes = $old_classes . " " . $class;
        $new_element = str_replace($old_classes, $new_classes, $element);

        return $new_element;
    }

    /**
     * Get class attribute of the "<article>" HTML element.
     *
     * @param  string $element - HTML element.
     * @return string $class   - Value of the class attribute
     */
    function getClasses($element)
    {
        $re = '/<article\s*class="([^"]*)"/m';
        preg_match($re, $element, $matches);
        $class = '';
        if ($matches[1]) {
            $class = $matches[1];
        }
        
        return $class;
    }
    
    /**
     * Method is checking, does listings was highlighted before
     *
     * @param  int        $listing_id   - Listing ID
     * @return array|bool $listing_info - Highlight data of the listing in success case, or false.
     */
    public function alreadyHighlighted($listing_id)
    {
        $sql = "SELECT `HighlightDate`, `Highlight_Plan` FROM `{db_prefix}listings` ";
        $sql .= "WHERE `ID` = {$listing_id}";
        $listing_info = $GLOBALS['rlDb']->getRow($sql);
        
        if ($listing_info['Highlight_Plan']) {
            return $listing_info;
        }
    
        return false;
    }

    /**
     * @deprecated 2.0.0
     */
    public function addPlan($data)
    {}

    /**
     * @deprecated 2.0.0
     * 
     * Return all highlight plans by limit or all
     *
     * @param  int    $start  - Start from
     * @param  int    $limit  - Limit plans
     * @param  string $status - Status of the plans
     * @return array  $data   - An array of the highlight plans
     */
    public function getPlans($start = 0, $limit = 0, $status = '')
    {}

    /**
     * @deprecated 2.0.0
     * 
     * Delete highlight plan.
     *
     * @since 1.3.0 Added - $withPlanUsing
     *
     * @param  int  $plan_id       - Highlight Plan ID
     * @param  bool $withPlanUsing - Delete plan with all related plan using
     *
     * @return array $out     - Answer for Ajax
     */
    public function deletePlan($plan_id, $withPlanUsing = false)
    {}

    /**
     * @deprecated 2.0.0
     * 
     * Getting highlight plan info
     *
     * @since 1.3.0 Added - $by
     *
     * @param  int        $plan_id - ID of the plan
     * @param  string     $by      - Getting plan of monetize of flynax
     * @return array|bool          - Return plan info if plan exist | false if plan doesn't exist
     */
    public function getPlanInfo($plan_id, $by = 'plugin')
    {}

    /**
     * @deprecated 2.0.0
     * 
     * Return available credits by specified account.
     *
     * @param  int  $account_id - ID of needed account.
     * @param  int  $planID     - Highlight plan ID
     *
     * @return int  $row        - Highlight credits.
     */
    public function getCredits($account_id, $planID = 0)
    {}

    /**
     * @deprecated 2.0.0
     * 
     * Get plan using.
     *
     * @since 1.3.0 Added -  parameter $accountID
     *
     * @param  bool $plan_id   - ID of the plan
     * @param  int  $accountID
     * @return mixed|bool $plan_using - Plan using row | False if it doesn't exist
     */
    public function getPlanUsing($plan_id = false, $accountID = 0)
    {}

    /**
     * @deprecated 2.0.0
     * 
     * This method run after payment successfully passed
     *
     * @param int   $item_id    - Listing ID
     * @param int   $plan_id    - Bump up ID
     * @param int   $account_id - ID of the account who bought bump ups
     * @param array $params     - Additional parameters of the payment system
     */
    public function upgradeHighlight($item_id = 0, $plan_id = 0, $account_id = 0, $params = array())
    {}

    /**
     * @deprecated 2.0.0
     * 
     * Rebuild all plans depending bought unlimited plan.
     *
     * @param  array  $plans    - Highlight plans
     * @param  int    $unlim_id - Bought unlimited plan ID
     * @param  string $type     - Type of the plan
     * @return array  $plans    - Modified plans
     */
    public function rebuildPlans($plans, $unlim_id, $type = 'highlight')
    {}
}
