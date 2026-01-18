<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLUNDERCONSTRUCTION.CLASS.PHP
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

class rlUnderConstruction
{
    /**
     * Plugin installer
     * @since 3.1.0
     */
    public function install()
    {
        $sql = "
            UPDATE `" . RL_DBPREFIX . "config` 
            SET `Default` = DATE(DATE_ADD(NOW(), INTERVAL 1 MONTH)) 
            WHERE `Key` = 'under_constructions_date' LIMIT 1
        ";
        $GLOBALS['rlDb']->query($sql);
    }

    /**
     * @hook boot
     *
     * @since 3.1.0
     */
    public function hookBoot()
    {
        global $config, $reefless, $rlSmarty, $rlDb;

        $ips = explode(';', $config['under_constructions_ip']);
        $ip = $reefless->getClientIpAddress();

        $file = $config['under_constructions_file'];
        $date = strtotime($config['under_constructions_date']);

        $rlSmarty->assign('date', $date);

        if (!in_array($ip, $ips) && time() <= $date) {
            // Massmailer support
            $mm_version = $GLOBALS['plugins']['massmailer_newsletter'];
            $legacy_version = version_compare($mm_version, '3.0.0') < 0 ? true : false;

            $rlSmarty->assign('legacy_version', $legacy_version);

            if ($mm_version && $legacy_version) {
                $reefless->loadClass('MassmailerNewsletter', null, 'massmailer_newsletter');
                $GLOBALS['rlXajax']->registerFunction(
                    array('subscribe', $GLOBALS['rlMassmailerNewsletter'], 'ajaxSubscribe')
                );
            }

            // Show under construction interface
            if (!empty($file) && file_exists(RL_ROOT . $file)) {
                echo file_get_contents(RL_ROOT . $file);
            } else {
                $rlSmarty->display(RL_PLUGINS . 'underConstructions' . RL_DS .'content.tpl');
            }
            exit;
        }
    }
}
