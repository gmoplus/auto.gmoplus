<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLLOANCALC.CLASS.PHP
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

/**
 * Loan mortgage calculator class, the main purpose is collecting hook related methods
 * 
 * @since 3.1.0
 */
class rlLoanCalc extends Flynax\Abstracts\AbstractPlugin implements Flynax\Interfaces\PluginInterface
{
    /**
     * Box/Tab view handler
     * 
     * @hook listingDetailsBottom
     */
    public function hookListingDetailsBottom()
    {
        global $tabs, $lang, $config, $listing_type, $blocks, $rlCommon, $listing_data;

        // Disable for rent
        if (isset($listing_data['sale_rent']) && $listing_data['sale_rent'] == '2') {
            $listing_type['Loan_calc'] = false;
        }

        // Remove box
        if (!$listing_type['Loan_calc'] || $config['loanMortgage_mode'] == 'tab' || !$listing_data) {
            unset($blocks['loan_mortgage']);
            $rlCommon->defineBlocksExist($blocks);
        }

        // Add tab
        if ($listing_type['Loan_calc'] && $config['loanMortgage_mode'] == 'tab') {
            $tabs['loanMortgage'] = array(
                'key' => 'loanMortgage',
                'name' => $lang['loanMortgage_tab_caption']
            );
        }
    }

    /**
     * Box/Tab view handler
     * 
     * @hook listingDetailsBottomTpl
     */
    public function hookListingDetailsBottomTpl()
    {
        global $rlSmarty, $config, $listing_type;

        if ($config['loanMortgage_mode'] == 'tab' && $listing_type['Loan_calc']) {
            $rlSmarty->display(RL_PLUGINS . 'loanMortgageCalculator/tab.tpl');
        }
    }

    /**
     * Prepare price data
     * 
     * @hook listingDetailsTop
     */
    public function hookListingDetailsTop()
    {
        global $rlSmarty, $listing_data, $config, $lang, $listing_type;

        if ($listing_data[$config['price_tag_field']] && $listing_type['Loan_calc']) {
            $price = explode('|', $listing_data[$config['price_tag_field']]);
            $price[1] = $lang['data_formats+name+' . $price[1]];

            $rlSmarty->assign_by_ref('lm_amount', $price);
        }
    }

    /**
     * Display plugin option in listing type form
     * 
     * @hook apTplListingTypesForm
     */
    public function hookApTplListingTypesForm()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'loanMortgageCalculator/row.tpl');
    }

    /**
     * Assign data to POST
     * 
     * @hook apPhpListingTypesPost
     */
    public function hookApPhpListingTypesPost()
    {
        $_POST['loan_calc'] = $GLOBALS['type_info']['Loan_calc'];
    }

    /**
     * Assign to data array
     * 
     * @hook apPhpListingTypesBeforeAdd
     */
    public function hookApPhpListingTypesBeforeAdd()
    {
        $GLOBALS['data']['Loan_calc'] = (int) $_POST['loan_calc'];
    }

    /**
     * Assign to data array
     * 
     * @hook apPhpListingTypesBeforeEdit
     */
    public function hookApPhpListingTypesBeforeEdit()
    {
        $GLOBALS['update_date']['fields']['Loan_calc'] = (int) $_POST['loan_calc'];
    }

    /**
     * Plugin box status handler
     * 
     * @hook apPhpConfigAfterUpdate
     */
    public function hookApPhpConfigAfterUpdate()
    {
        if ($GLOBALS['config']['loanMortgage_mode'] != $GLOBALS['dConfig']['loanMortgage_mode']) {
            $update = array(
                'fields' => array(
                    'Status' => $mode == 'tab' ? 'trash' : 'active'
                ),
                'where' => array(
                    'Key' => 'loan_mortgage'
                ),
            );
            $GLOBALS['rlDb']->update($update, 'blocks');
        }
    }

    /**
     * Exclude print page from the sitemap
     * 
     * @hook sitemapExcludedPages
     */
    public function hookSitemapExcludedPages(&$param1)
    {
        $param1 = array_merge($param1, array('loanMortgage_print'));
    }

    /**
     * Remove the box if it assigned to the wrong page
     * 
     * @hook boot
     */
    public function hookBoot()
    {
        global $blocks, $page_info;

        if (($page_info['Key'] != 'view_details' && $blocks['loan_mortgage'])
            || $GLOBALS['sError']
            || $page_info['Listing_details_inactive']
        ) {
            unset($blocks['loan_mortgage']);
            $GLOBALS['rlCommon']->defineBlocksExist($blocks);
        }
    }

    /**
     * @hook apMixConfigItem
     * @since 3.1.2
     *
     * @param array $value
     * @param array $systemSelects - Required configs with "select" type
     */
    public function hookApMixConfigItem(&$value, &$systemSelects)
    {
        if (!in_array($value['Key'], ['loanMortgage_mode', 'loanMortgage_loan_term_mode'])) {
            return;
        }

        $systemSelects[] = $value['Key'];
    }

    /**
     * Plugin installer
     */
    public function install()
    {
        global $rlDb;
    
        $rlDb->addColumnToTable(
            'Loan_calc',
            "ENUM('0', '1') NOT NULL DEFAULT '1' AFTER `Status`",
            'listing_types'
        );
        
        $update = array(
            'fields' => array(
                'Status'     => 'trash',
                'Page_ID'    => '25',
                'Sticky'     => '0',
                'Cat_sticky' => '1',
                'Position'   => 1,
                'Plugin'     => ''
            ),
            'where' => array(
                'Key' => 'loan_mortgage'
            ),
        );
        $rlDb->update($update, 'blocks');
    }

    /**
     * Plugin uninstaller
     */
    public function uninstall()
    {
        global $rlDb;

        $rlDb->dropColumnFromTable('Loan_calc', 'listing_types');
        $rlDb->delete(array('Key' => 'loan_mortgage'), 'blocks');
    }

    /**
     * Update process of the plugin (copy from core)
     *
     * @todo Remove this method when compatibility will be >= 4.6.2
     * 
     * @param string $version
     */
    public function update($version)
    {
        $version_method = 'update' . (int) str_replace('.', '', $version);
        if (method_exists($this, $version_method)) {
            $this->$version_method();
        }
    }

    /**
     * Update to 2.1.0 version
     */
    public function update210()
    {
        $GLOBALS['rlDb']->addColumnToTable(
            'Loan_calc',
            "ENUM('0', '1') NOT NULL DEFAULT '1' AFTER `Status`",
            'listing_types'
        );
    }

    /**
     * Update to 3.1.0 version
     */
    public function update310()
    {
        global $rlDb;

        // Update box
        $box = array(
            'fields' => array(
                'Content' => "{include file=\$smarty.const.RL_PLUGINS|cat:'loanMortgageCalculator/box.tpl'}",
                'Type'    => 'smarty',
            ),
            'where' => array('Key' => 'loan_mortgage'),
        );
        $rlDb->update($box, 'blocks');

        // Remove useless config
        $rlDb->query("
            DELETE FROM `{db_prefix}config`
            WHERE `Plugin` = 'loanMortgageCalculator' 
            AND `Key` IN ('loanMortgageModule', 'loanMortgage_price_field')
        ");

        // Remove legacy hooks
        $rlDb->query("
            DELETE FROM `{db_prefix}hooks`
            WHERE `Plugin` = 'loanMortgageCalculator' 
            AND `Name` IN ('tplHeader', 'phpCompressionJsCssExceptions')
        ");

        // Remove phrases
        $phrases = array(
            'config+name+loanMortgageModule',
            'config+name+loanMortgage_price_field',
        );

        $rlDb->query("
            DELETE FROM `{db_prefix}lang_keys` 
            WHERE `Plugin` = 'loanMortgageCalculator' 
            AND `Key` IN ('" . implode("','", $phrases) . "')
        ");

        // Remove legacy files
        $files_to_be_removed = array(
            'tab_responsive_42.tpl',
            'box_responsive_42.tpl',
            'static/style_responsive_42.css',
            'static/style.css',
        );
        foreach ($files_to_be_removed as $file) {
            unlink(RL_PLUGINS . 'loanMortgageCalculator/' . $file);
        }
    }

    /**
     * Update to 3.1.2 version
     */
    public function update312()
    {
        global $rlDb;

        $rlDb->query("UPDATE `{db_prefix}config` SET `Default` = LOWER(`Default`) WHERE `Key` = 'loanMortgage_loan_term_mode' LIMIT 1");

        // Translate phrases
        if (array_key_exists('ru', $GLOBALS['languages'])) {
            $russianTranslation = json_decode(file_get_contents(RL_UPLOAD . 'loanMortgageCalculator/i18n/ru.json'), true);

            foreach ($russianTranslation as $phraseKey => $phraseValue) {
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
