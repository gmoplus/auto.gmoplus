<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLMANAGER.CLASS.PHP
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

/**
 * Class Manager.
 * Installation, Uninstallation and Updates functions of the Cookie Policy plugin.
 *
 * @since 1.2.1
 */
class rlManager extends Flynax\Abstracts\AbstractPlugin implements Flynax\Interfaces\PluginInterface
{
    /**
     * Update to 1.1.0 version
     */
    public function update110()
    {
        global $config, $rlConfig;

        // collect old data
        $countries = $config['cookiesPolicy_country'];
        $position  = $config['cookiesPolicy_position'];
        $icon      = $config['cookiesPolicy_hide_icon'];
        $url       = $config['cookiesPolicy_redirect_url'];

        $new_countries = 'AT,BE,BG,HR,CY,CZ,DK,EE,FI,FR,DE,GR,HU,IE,IT,LV,LT,LU,MT,NL,PL,PT,RO,SK,SI,ES,SE,GB,GF,GP,MQ,ME,YT,RE,MF,GI,AX,PM,GL,BL,SX,AW,CW,WF,PF,NC,TF,AI,BM,IO,VG,KY,FK,MS,PN,SH,GS,TC,AD,LI,MC,SM,VA,JE,GG,GI';

        // new data
        $countries = implode(',', array_unique(array_merge(explode(',', $countries), explode(',', $new_countries))));
        $position  = $position == 'Bottom Left' ? 'bottom_left' : 'bottom_right';

        // restore old values of configs after update
        $rlConfig->setConfig('cookiesPolicy_country', $countries);
        $rlConfig->setConfig('cookiesPolicy_position', $position);
        $rlConfig->setConfig('cookiesPolicy_hide_icon', $icon);
        $rlConfig->setConfig('cookiesPolicy_redirect_url', $url);

        $GLOBALS['rlDb']->query("
            DELETE FROM `{db_prefix}lang_keys`
            WHERE `Key` = 'cookies_policy_content_text' AND `Plugin` = 'cookiesPolicy'
        ");
    }

    /**
     * Update to 1.2.0 version
     */
    public function update120()
    {
        // Apply old value of config after update
        $GLOBALS['rlConfig']->setConfig('cp_block_all_cookies', $GLOBALS['config']['cp_block_all_cookies']);
    }

    /**
     * Update to 1.2.1 version
     */
    public function update121()
    {
        global $rlDb, $config, $allLangs;

        $popupText = $config['cp_popup_text'];

        $rlDb->query(
            "DELETE FROM `{db_prefix}config`
            WHERE `Key` = 'cp_popup_text' AND `Plugin` = 'cookiesPolicy'"
        );

        $rlDb->query(
            "DELETE FROM `{db_prefix}lang_keys`
            WHERE `Key` = 'config+name+cp_popup_text' AND `Plugin` = 'cookiesPolicy'"
        );

        foreach ($GLOBALS['languages'] as $langKey => $langData) {
            $currentPhrase = $rlDb->getOne(
                'ID',
                "`Key` = 'cookies_policy_content_text' AND `Code` = '{$langKey}'",
                'lang_keys'
            );

            if (!$currentPhrase) {
                $rlDb->insertOne(
                    [
                        'Code'   => $langKey,
                        'Module' => 'frontend',
                        'Key'    => 'cookies_policy_content_text',
                        'Value'  => $popupText,
                        'Plugin' => 'cookiesPolicy'
                    ],
                    'lang_keys'
                );
            }
        }
    }

    /**
     * Update to 1.3.0 version
     */
    public function update130()
    {
        global $rlDb;

        $rlDb->updateOne([
            'fields' => ['Position' => 0],
            'where' => ['Plugin' => 'cookiesPolicy', 'Key' => 'cookiesPolicy_view']
        ], 'config');
    }
}
