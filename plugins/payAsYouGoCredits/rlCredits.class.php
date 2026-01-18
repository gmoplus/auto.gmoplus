<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: RLCREDITS.CLASS.PHP
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

class rlCredits
{
    /**
     * Get credit packages
     *
     * @return []
     */
    public function get()
    {
        $credits = $GLOBALS['rlDb']->getAll("SELECT * FROM `{db_prefix}credits_manager` WHERE `Status` = 'active' ORDER BY `Price` ASC");

        if ($credits) {
            foreach ($credits as &$package) {
                $package['name'] = $GLOBALS['lang']["credits_manager+name+credit_package_{$package['ID']}"];
                $package['Price_one'] = $package['Credits'] ? round(((float) $package['Price'] / (float) $package['Credits']), 2) : 0;
            }
        }

        return $credits;
    }

    /**
     * Get credits info
     *
     * @return []
     */
    public function getCreditsInfo()
    {
        $days = ceil((int) $GLOBALS['config']['payAsYouGoCredits_period'] * 30.5);
        $sql = "SELECT `ID`, `Total_credits`, `paygc_pay_date`, DATE_ADD(`paygc_pay_date`, INTERVAL {$days} DAY) AS `Expiration_date` ";
        $sql .= "FROM `{db_prefix}accounts` WHERE `ID` = '{$GLOBALS['account_info']['ID']}'";
        $creditsInfo = $GLOBALS['rlDb']->getRow($sql);

        if ($creditsInfo) {
            $creditsInfo['Total_credits'] = number_format($creditsInfo['Total_credits'], 2, '.', '');
        }

        return $creditsInfo;
    }

    /**
     * Add credits to account
     *
     * @param  int    $package_id
     * @param  int    $plan_id
     * @param  int    $account_id
     * @param  string $txn_id
     * @param  string $gateway
     * @param  double $total
     * @return bool
     */
    public function addCredits($package_id = 0, $plan_id = 0, $account_id = 0, $txn_id = '', $gateway = '', $total = 0)
    {
        global $rlDb;

        if (!$package_id || !$account_id) {
            return false;
        }

        $package_id = (int) $package_id;
        $account_id = (int) $account_id;

        $account_info = $rlDb->fetch
            (array('Username', 'First_name', 'Last_name', 'Mail', 'Total_credits'),
            array('ID' => $account_id),
            null,
            1,
            'accounts',
            'row'
        );
        $package_info = $rlDb->fetch(
            array('ID', 'Credits', 'Price'),
            array('ID' => $package_id),
            null,
            1,
            'credits_manager',
            'row'
        );

        if ($account_info && $package_info) {
            $update = array(
                'fields' => array(
                    'Total_credits'  => round($account_info['Total_credits'] + $package_info['Credits'], 2),
                    'paygc_pay_date' => 'NOW()',
                ),
                'where' => array(
                    'ID' => $account_id,
                ),
            );

            if ($rlDb->updateOne($update, 'accounts')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Update credits in Account
     */
    public function updateCreditsForAccount()
    {
        global $profile_data;

        if (!isset($_POST['Total_credits'])) {
            return false;
        }

        $balance = (float) $_POST['Total_credits'];
        $where = $_GET['action'] == 'add' ? "`Username` = '{$profile_data['username']}'" : "`ID` = '" . intval($_GET['account']) . "'";
        $sql = "UPDATE `{db_prefix}accounts` SET `Total_credits` = '{$balance}', `paygc_pay_date` = NOW() WHERE {$where} LIMIT 1";

        $GLOBALS['rlDb']->query($sql);
    }

    /*** DEPRECATED ***/

    /**
     * Delete credit item by ID
     *
     * @deprecated 2.1.2
     *
     * @param   int $id - credit id
     * @package AJAX
     */
    public function ajaxDeleteCreditItem($id = 0)
    {}
}
