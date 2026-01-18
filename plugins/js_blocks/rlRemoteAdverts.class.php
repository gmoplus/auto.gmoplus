<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLREMOTEADVERTS.CLASS.PHP
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2024
 *	https://www.flynax.com
 *
 ******************************************************************************/

use Flynax\Utils\Valid;

class rlRemoteAdverts extends Flynax\Abstracts\AbstractPlugin implements Flynax\Interfaces\PluginInterface
{
    /**
     * Get listings
     *
     * @deprecated 3.0.0
     */
    public function getListings($limit = false, $type = false, $where = false, $order = false, $orderType = 'DESC')
    {}

    /**
     * Add get params condition to sql WHERE clause
     *
     * @since 3.0.0
     *
     * @hook listingsModifyWhere
     */
    public function hookListingsModifyWhere()
    {
        global $rlDb, $sql;

        if (!isset($_GET['custom_id'])) {
            return;
        }

        $disabledFields = []; // Put here fields which you do not want to be used in $where
        $structureTmp   = $rlDb->getAll('SHOW FIELDS FROM `{db_prefix}listings`');

        foreach ($structureTmp as $field) {
            if (!in_array($field['Field'], $disabledFields)) {
                $structure[] = strtolower($field['Field']);
            }
        }

        foreach ($_GET as $key => $value) {
            $key = Valid::escape(strtolower($key));
            $value = Valid::escape($value);

            if (in_array($key, $structure) && $value) {
                $sql .= "AND `T1`.`{$key}` = '{$value}' ";
            }
        }

        // Remove type condition
        if (!$_GET['listing_type']) {
            $sql = str_replace("AND `T3`.`Type` = ''", '', $sql);
        }
    }

    /**
     * Load categories
     *
     * @deprecated 3.0.0
     */
    public function ajaxLoadCategories($listingType, $value = 0, $level = 0)
    {}

    /**
     * Install process
     * @since 3.0.0
     */
    public function install()
    {
        global $rlDb;

        $rlDb->addColumnToTable('Remote_adverts', "ENUM('0','1') NOT NULL DEFAULT '0'", 'account_types');
        $rlDb->addColumnToTable('Remote_adverts', "ENUM('0','1') NOT NULL DEFAULT '1'", 'listing_plans');
        $rlDb->addColumnToTable('Remote_adverts', "ENUM('0','1') NOT NULL DEFAULT '1'", 'membership_plans');
        $rlDb->query(
            "UPDATE `{db_prefix}account_types` SET `Remote_adverts` = '1'
            WHERE `Key` LIKE 'dealer' OR `Key` LIKE 'agency'"
        );

        $aids = $rlDb->getRow(
            "SELECT GROUP_CONCAT(`ID`) as `ids` FROM `{db_prefix}account_types`
            WHERE `Key` NOT LIKE 'dealer' AND `Key` NOT LIKE 'agency'"
        );

        $rlDb->query(
            "UPDATE `{db_prefix}pages` SET `Deny` = '{$aids['ids']}'
            WHERE `Key` = 'remote_adverts'"
        );
    }

    /**
     * Uninstall process
     * @since 3.0.0
     */
    public function uninstall()
    {
        global $rlDb;

        $rlDb->dropColumnFromTable('Remote_adverts', 'listing_plans');
        $rlDb->dropColumnFromTable('Remote_adverts', 'membership_plans');
        $rlDb->dropColumnFromTable('Remote_adverts', 'account_types');
    }

    /**
     * Update process of the plugin (copy from core)
     * @todo - Remove this method when compatibility will be >= 4.6.2
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
     * Update to 2.0.1 version
     */
    public function update201()
    {
        $GLOBALS['rlDb']->query("DELETE FROM `{db_prefix}config` WHERE `Plugin` = 'js_blocks'");
    }

    /**
     * Update to 3.0.0 version
     */
    public function update300()
    {
        global $rlDb;

        $rlDb->query(
            "DELETE FROM `{db_prefix}hooks`
            WHERE `Name` = 'tplHeader' AND `Plugin` = 'js_blocks' LIMIT 1"
        );

        $rlDb->query(
            "DELETE FROM `{db_prefix}lang_keys`
            WHERE `Key` IN ('jl_field_names_color','jl_show','jl_box_code_pre') AND `Plugin` = 'js_blocks'"
        );

        $switch_to_common = array(
            'jl_box_code',
            'jl_border_color',
        );

        $rlDb->query(
            "UPDATE `{db_prefix}lang_keys`
            SET `Module` = 'common'
            WHERE `Key` IN ('" . implode("','", $switch_to_common) . "') AND `Plugin` = 'js_blocks'"
        );

        unlink(RL_PLUGINS . 'js_blocks/remote_adverts_responsive_42.tpl');

        $rlDb->addColumnToTable('Remote_adverts', "ENUM('0','1') NOT NULL DEFAULT '1'", 'membership_plans');
    }

    /**
     * Update to 3.0.2 version
     */
    public function update302()
    {
        // Remove unnecessary phrases
        $phrases = array(
            'jl_add_box',
            'jl_box_list',
            'jl_box_name',
            'jl_listing_types',
            'jl_img_width',
            'jl_img_height',
            'jl_field_names',
            'jl_owner',
        );

        $GLOBALS['rlDb']->query("
            DELETE FROM `{db_prefix}lang_keys`
            WHERE `Plugin` = 'js_blocks' AND `Key` IN ('" . implode("','", $phrases) . "')"
        );

        if (in_array('ru', array_keys($GLOBALS['languages']))) {
            $russianTranslation = json_decode(file_get_contents(RL_PLUGINS . 'js_blocks/i18n/ru.json'), true);
            foreach ($russianTranslation as $phraseKey => $phraseValue) {
                if (!$GLOBALS['rlDb']->getOne('ID', "`Key` = '{$phraseKey}' AND `Code` = 'ru'", 'lang_keys')) {
                    $newPhrase = $GLOBALS['rlDb']->fetch(
                        ['Module', 'Key', 'Plugin', 'JS', 'Target_key'],
                        ['Code' => $GLOBALS['config']['lang'], 'Key' => $phraseKey, 'Plugin' => 'js_blocks'],
                        null, 1, 'lang_keys', 'row'
                    );
                    $newPhrase['Code']  = 'ru';
                    $newPhrase['Value'] = $phraseValue;

                    $GLOBALS['rlDb']->insertOne($newPhrase, 'lang_keys');
                } else {
                    $GLOBALS['rlDb']->updateOne([
                        'fields' => ['Value' => $phraseValue],
                        'where'  => ['Key'   => $phraseKey, 'Code' => 'ru'],
                    ], 'lang_keys');
                }
            }
        }
    }

    /**
     * @hook  apTplListingPlansForm
     * @since 3.0.0
     */
    public function hookApTplListingPlansForm()
    {
        $this->addOptionToPlans();
    }

    /**
     * @hook  apTplMembershipPlansForm
     * @since 3.0.0
     */
    public function hookApTplMembershipPlansForm()
    {
        $this->addOptionToPlans();
    }

    /**
     * Add plugin option to listing/membership plans
     *
     * @since 3.0.0
     */
    public function addOptionToPlans()
    {
        global $lang;

        echo "<table class=\"form\"><tr><td class=\"name\">{$lang['jl_remote_adverts']}</td><td class=\"field\">";

        if ($_POST['remote_adverts'] == '1') {
            $yes = 'checked="checked"';
        } elseif ($_POST['remote_adverts'] == '0') {
            $no = 'checked="checked"';
        } else {
            $yes = 'checked="checked"';
        }

        echo "<input {$yes} type=\"radio\" id=\"ra_yes\" name=\"remote_adverts\" value=\"1\" />&nbsp;";
        echo "<label for=\"ra_yes\">{$lang['yes']}</label>";
        echo "<input {$no} type=\"radio\" id=\"ra_no\" name=\"remote_adverts\" value=\"0\" />&nbsp;";
        echo "<label for=\"ra_no\">{$lang['no']}</label>";
        echo '</td></tr></table>';
    }

    /**
     * Remove old cached files (which has not been updated over 2 days)
     * 
     * @since 3.0.0
     */
    public function removeOldCacheFiles()
    {
        foreach (scandir(RL_CACHE) as $file) {
            if (0 === strpos($file, 'js_blocks_') && filemtime(RL_CACHE . $file) + 172800 < time()) {
                unlink(RL_CACHE . $file);
            }
        }
    }

    /**
     * @hook  apPhpListingPlansPost
     * @since 3.0.0
     */
    public function hookApPhpListingPlansPost()
    {
        $_POST['remote_adverts'] = $GLOBALS['plan_info']['Remote_adverts'];
    }

    /**
     * @hook  apPhpMembershipPlansPost
     * @since 3.0.0
     */
    public function hookApPhpMembershipPlansPost()
    {
        $_POST['remote_adverts'] = $GLOBALS['plan_info']['Remote_adverts'];
    }

    /**
     * @hook  apPhpListingPlansBeforeAdd
     * @since 3.0.0
     */
    public function hookApPhpListingPlansBeforeAdd()
    {
        $GLOBALS['data']['Remote_adverts'] = $_POST['remote_adverts'];
    }

    /**
     * @hook  apPhpMembershipPlansBeforeAdd
     * @since 3.0.0
     */
    public function hookApPhpMembershipPlansBeforeAdd(&$data)
    {
        $data['Remote_adverts'] = $_POST['remote_adverts'];
    }

    /**
     * @hook  apPhpListingPlansBeforeEdit
     * @since 3.0.0
     */
    public function hookApPhpListingPlansBeforeEdit()
    {
        $GLOBALS['update_date']['fields']['Remote_adverts'] = $_POST['remote_adverts'];
    }

    /**
     * @hook  apPhpMembershipPlansBeforeEdit
     * @since 3.0.0
     */
    public function hookApPhpMembershipPlansBeforeEdit(&$updatePlan)
    {
        $updatePlan['fields']['Remote_adverts'] = $_POST['remote_adverts'];
    }

    /**
     * @hook  apTplAccountTypesForm
     * @since 3.0.0
     */
    public function hookApTplAccountTypesForm()
    {
        global $lang;

        echo "<tr><td class=\"name\">{$lang['jl_remote_adverts']}</td><td class=\"field\">";

        if ($_POST['remote_adverts'] == '1') {
            $yes = 'checked="checked"';
        } elseif ($_POST['remote_adverts'] == '0') {
            $no = 'checked="checked"';
        } else {
            $no = 'checked="checked"';
        }

        echo "<input {$yes} type=\"radio\" id=\"ra_yes\" name=\"remote_adverts\" value=\"1\" />&nbsp;";
        echo "<label for=\"ra_yes\">{$lang['yes']}</label>";
        echo "<input {$no} type=\"radio\" id=\"ra_no\" name=\"remote_adverts\" value=\"0\" />&nbsp;";
        echo "<label for=\"ra_no\">{$lang['no']}</label>";
        echo '</td></tr>';
    }

    /**
     * @hook  apPhpAccountTypesPost
     * @since 3.0.0
     */
    public function hookApPhpAccountTypesPost()
    {
        $_POST['remote_adverts'] = $GLOBALS['item_info']['Remote_adverts'];
    }

    /**
     * @hook  apPhpAccountTypesBeforeAdd
     * @since 3.0.0
     */
    public function hookApPhpAccountTypesBeforeAdd()
    {
        $GLOBALS['data']['Remote_adverts'] = $_POST['remote_adverts'];
    }

    /**
     * @hook  apPhpAccountTypesBeforeEdit
     * @since 3.0.0
     */
    public function hookApPhpAccountTypesBeforeEdit()
    {
        $GLOBALS['update_date']['fields']['Remote_adverts'] = $_POST['remote_adverts'];
    }

    /**
     * @hook  staticDataRegister
     * @since 3.0.0
     */
    public function hookStaticDataRegister()
    {
        global $rlStatic;

        $rlStatic->addFooterCSS(RL_PLUGINS_URL . 'js_blocks/static/responsive.css', 'remote_adverts');
        $rlStatic->addFooterCSS(RL_LIBS_URL . 'jquery/colorpicker/css/colorpicker.css', 'remote_adverts');
        $rlStatic->addJS(RL_PLUGINS_URL . 'js_blocks/static/lib.js', 'remote_adverts');
        $rlStatic->addJS(RL_LIBS_URL . 'jquery/colorpicker/js/colorpicker.js', 'remote_adverts');
    }

    /**
     * @hook  apAjaxRequest
     * @since 3.0.0
     *
     * @param string $out
     * @param string $item
     */
    public function hookApAjaxRequest(&$out, $item)
    {
        if ($item !== 'raSaveBoxIDInSession' || !$id = (string) $_REQUEST['id']) {
            return;
        }

        $_SESSION['raBoxes'][] = $id;
        $out                   = ['status' => 'OK'];
    }

    /**
     * @hook ajaxRequest
     * @since 3.0.0
     *
     * @param array  $out
     * @param string $mode
     */
    public function hookAjaxRequest(&$out, $mode)
    {
        if ($mode !== 'raSaveBoxIDInSession' || !$id = (string) $_REQUEST['id']) {
            return;
        }

        $_SESSION['raBoxes'][] = $id;
        $out                   = ['status' => 'OK'];
    }

    /**
     * @hook init
     * @since 3.0.0
     */
    public function hookInit()
    {
        if (isset($_REQUEST['item']) && $_REQUEST['item'] == 'getCategoriesByType') {
            $_REQUEST['mode'] = $_REQUEST['item'];
        }
    }
}
