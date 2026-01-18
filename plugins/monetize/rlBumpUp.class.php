<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: RLBUMPUP.CLASS.PHP
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

class rlBumpUp
{
    /**
     * Add bump up 'plan using' information
     *
     * @since  1.3.0   Added - $from, $accountID, $addBumpUps
     *
     * @param  array   $plan       - Information about plan. It can be listing or bumpup plan info
     * @param  int     $accountID  - Account to which you want to assign credits
     * @param  bool    $subtract
     *
     * @return bool    $result - True if row was added, false if not.
     */
    public function addPlanUsing($plan = array(), $accountID = 0, $subtract = false)
    {
        if (!is_array($plan) || !$accountID) {
            return false;
        }

        $bumpups = $plan['Bump_ups'] ? $plan['Bump_ups'] : 0;
        $is_unlim = $plan['Bump_ups'] > 0 ? 0 : 1;

        $data = array(
            'Account_ID' => $accountID ?: $GLOBALS['account_info']['ID'],
            'Plan_ID' => $plan['ID'],
            'Plan_type' => 'bumpup',
            'Date' => 'NOW()',
            'Bumpups_available' => $bumpups > 0 && $subtract ? $bumpups - 1 : $bumpups,
            'Is_unlim' => $is_unlim,
        );

        $result = $GLOBALS['rlDb']->insertOne($data, 'monetize_using');

        return $result;
    }

    /**
     * Bump up listing ID
     *
     * @param  int   $listing_id   - Listing ID which user want to update
     * @param  int   $plan_id      - ID of the bump up plan
     * @param  int   $accountID
     * @return bool  $out          - Status of the bump up processing
     */
    public function bumpUp($listing_id = 0, $plan_id = 0, $accountID = 0)
    {
        global $rlDb, $rlMonetize;

        $accountID = $accountID ?: $GLOBALS['account_info']['ID'];

        if (!$listing_id || !$plan_id || !$accountID) {
            return false;
        }

        if (!$GLOBALS['rlMonetize']) {
            $GLOBALS['reefless']->loadClass('Monetize', null, 'monetize');
        }

        $plan_info = $rlMonetize->getPlanInfo($plan_id, 'bump_up');
        $plan_using = $rlMonetize->getPlanUsing($plan_id, $accountID);

        if (!$plan_info) {
            return false;
        }

        if ($plan_using) {
            if (!$plan_using['Is_unlim'] && $plan_info['Bump_ups'] > 0) {
                if ($plan_using['Bumpups_available'] == 1) {
                    $rlMonetize->removePlanUsingRow($plan_using['ID']);
                } else {
                    $update = array(
                        'fields' => ['Bumpups_available' => $plan_using['Bumpups_available'] - 1],
                        'where'  => ['ID' => $plan_using['ID']],
                    );

                    $rlDb->updateOne($update, 'monetize_using');
                }
            }
        } else {
            $this->addPlanUsing($plan_info, $accountID, true);
        }

        $sql = "UPDATE `{db_prefix}listings` SET `Date` = NOW(), `Bumped` = '1' ";
        $sql .= "WHERE `ID` = {$listing_id};";
        $result = $rlDb->query($sql);

        return $result;
    }

    /**
     * Prepare BumpUp tab in the 'My profile' page
     */
    public function prepareTab()
    {
        global $account_info, $rlMonetize;

        if (!$account_info['ID']) {
            return false;
        }

        $GLOBALS['tabs']['bump_up_credits'] = array(
            'key' => 'bump_up',
            'name' => $GLOBALS['lang']['bumpups_credits'],
        );
        $info['bump_ups'] = $rlMonetize->getCredits($account_info['ID'], false, 'bump_up');

        $accountID = (int) $account_info['ID'];
        $sql = "SELECT `Date` FROM `{db_prefix}monetize_using` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}monetize_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T2`.`Type` = 'bumpup' AND `T1`.`Account_ID` = {$accountID} ";
        $sql .= "ORDER BY `T1`.`Date` DESC LIMIT 1";
        $row = $GLOBALS['rlDb']->getRow($sql);
        if ($row) {
            $info['last_purchased'] = $row['Date'];
        }

        $GLOBALS['modify_where'] = true;

        $info['bumpedUpListings'] = $GLOBALS['rlListings']->getMyListings(
            'monetizeAll',
            'Date',
            'desc',
            0,
            $GLOBALS['config']['listings_per_page']
        );

        $link = $rlMonetize->getNotEmptyListingType();
        $info['link'] = $GLOBALS['reefless']->getPageUrl($link);

        $GLOBALS['rlSmarty']->assign_by_ref('buInfo', $info);
        unset($GLOBALS['modify_where']);
    }

    /**
     * @deprecated 2.0.0
     * 
     * This method run after payment successfully passed
     *
     * @param int   $item_id    - Listing ID
     * @param int   $plan_id    - Bump up ID
     * @param int   $account_id - ID of the account who bought bump ups
     * @param array $params     -  Additional parameters of the payment system
     */
    public function upgradeBumpUp($item_id = 0, $plan_id = 0, $account_id = 0, $params = array())
    {}

    /**
     * @deprecated 2.0.0
     *
     * Adding new BumpUp plan from Admin Panel
     *
     * @param $data - Data for adding to the bump up table
     * @return string|bool - Key of the added Bump up plan, if it was added | False if something goes wrong
     */
    public function addPlan($data)
    {}

    /**
     * @deprecated 2.0.0
     *
     * Return bump up plans by limit or all
     *
     * @param  int $start - Start from
     * @param  int $limit - Limit plans
     * @return array $data  - An array of the bump up plans
     */
    public function getPlans($start = false, $limit = false, $status = false)
    {}

    /**
     * @deprecated 2.0.0
     *
     * Getting bump up plan info
     *
     * @param  $plan_id - ID of the plan
     * @return data|bool   - Return plan info if plan exist | false if plan doesn't exist
     */
    public function getPlanInfo($plan_id)
    {}

    /**
     * @deprecated 2.0.0
     *
     * Delete bump up plan and all associated phrases of the plan
     *
     * @param  int $plan_id - ID of the plan
     * @return array $out     - Response for ajax
     */
    public function deletePlan($plan_id)
    {}

    /**
     * @deprecated 2.0.0
     * 
     * Getting plan usin row by plan_id and account
     *
     * @param int $plan_id   - Monetize plan ID
     * @param int $accountID
     *
     * @return bool|mixed  - $plan_using
     */
    public function getPlanUsing($plan_id = 0, $accountID = 0)
    {}

    /**
     * @deprecated 2.0.0
     * 
     * Return available credits by specified account.
     *
     * @since 1.3.0 Added $planID
     *
     * @param  int $account_id - ID of needed account
     * @param  int $planID     - Monetize plan ID
     * @return int
     */
    public function getCredits($account_id, $planID = 0)
    {}
}
