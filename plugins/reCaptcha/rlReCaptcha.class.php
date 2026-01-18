<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLRECAPTCHA.CLASS.PHP
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

use Flynax\Abstracts\AbstractPlugin;
use Flynax\Interfaces\PluginInterface;
use Flynax\Utils\Util;
use ReCaptcha\ReCaptcha;

class rlReCaptcha extends AbstractPlugin implements PluginInterface
{
    /**
     * Path of plugin directory
     * @since 3.0.0
     * @string
     */
    public const PLUGIN_DIR = RL_PLUGINS . 'reCaptcha/';

    /**
     * Path of view directory for admin side
     * @since 3.0.0
     * @string
     */
    public const ADMIN_VIEW_DIR = self::PLUGIN_DIR . 'admin/view/';

    /**
     * Clear compile directory
     *
     * @since 2.3.2 - Removed $mode parameter
     */
    public function clearCompile(): void
    {
        foreach ($GLOBALS['reefless']->scanDir(RL_TMP . 'compile') as $file) {
            if (in_array($file, ['index.html', '.htaccess'])) {
                continue;
            }

            @unlink(RL_TMP . 'compile' . RL_DS . $file);
        }
    }

    /**
     * @hook init
     * @since 2.2.0
     */
    public function hookInit(): void
    {
        global $config;

        $check_post = $_POST['xjxargs'] ?: $_POST;

        $security_code = '';
        foreach ($check_post as $v) {
            if (is_string($v) && strpos($v, 'flgcaptcha') > 300) {
                $security_code = $v;
                break;
            }
        }

        $security_code = $security_code ?: $_REQUEST['security_code'];

        if (strlen($security_code) > 300) {
            require_once __DIR__ . '/vendor/autoload.php';
            $recaptcha = new ReCaptcha($config['reCaptcha_private_key']);

            $g_arr  = explode('flgcaptcha', $security_code);
            [$g_code, $captcha_id] = $g_arr;

            if ($config['reCaptcha_type'] === 'v3')  {
                $recaptcha->setScoreThreshold($config['reCaptcha3_score']);
            }

            $resp = $recaptcha->verify($g_code, Util::getClientIP());

            if ($resp->isSuccess()) {
                if ($captcha_id) {
                    $_SESSION['ses_security_code_' . $captcha_id] = $security_code;
                } else {
                    $_SESSION['ses_security_code'] = $security_code;
                }
                $_SESSION['gcaptcha_solved_need_reset'] = $captcha_id ?: true;
            }
        }

        if (!$_POST['xjxfun']) {
            unset($_SESSION['gcaptcha_solved_need_reset']);
        }
    }

    /**
     * @hook seoBase
     * @since 2.2.0
     */
    public function hookSeoBase(): void
    {
        global $lang;

        $lang['security_code_incorrect'] = $lang['recaptcha_error'];
    }

    /**
     * @hook smartyCompileFileBottom
     *
     * @param string $content - Compiled content
     * @since 2.2.0
     */
    public function hookSmartyCompileFileBottom(&$content): void
    {
        if (false !== strpos($content, 'captcha.tpl')) {
            $content = preg_replace("/('captcha\.tpl')/", 'RL_PLUGINS . \'reCaptcha/reCaptcha.tpl\'', $content);
        }
    }

    /**
     * @hook tplHeader
     * @since 3.0.0
     */
    public function hookTplHeader(): void
    {
        global $config;

        echo PHP_EOL . '<link rel="preconnect" href="https://www.google.com">';
        echo PHP_EOL . '<link rel="preconnect" href="https://www.gstatic.com" crossorigin>';

        // Forsly hide recaptcha container when it added in popups to prevent design issues
        if ($config['reCaptcha_type'] === 'v2_invisible'
            && in_array($config['reCaptcha_position'], ['bottomleft', 'bottomright'])
        ) {
            echo <<<HTML
                <style>
                    .popup .body .submit-cell .grecaptcha-badge {
                        visibility: hidden;
                    }
                </style>
HTML;
        }
    }

    /**
     * @hook tplFooter
     * @since 2.2.0
     */
    public function hookTplFooter(): void
    {
        $GLOBALS['rlSmarty']->display(self::PLUGIN_DIR . 'reCaptcha_init.tpl');
    }

    /**
     * @hook pageinfoArea
     * @since 2.2.0
     */
    public function hookPageinfoArea(): void
    {
        global $_response;

        if ($_POST['xjxfun']
            && $_SESSION['gcaptcha_solved_need_reset']
            && $_response
        ) {
            if ($_SESSION['gcaptcha_solved_need_reset'] === true) {
                $_response->script('ReCaptcha.resetAllWidgets();');
            } else {
                $_response->script("
                    let commentWidgetID = $('input[name=security_code_comment]')
                        .prev('div.gptwdg')
                        .attr('id')
                        .split('gcaptcha_widget')[1] - 1;

                    ReCaptcha.resetWidgetByID(commentWidgetID);
                ");
            }
        }
    }

    /**
     * @hook addListingPreFields
     * @since 2.3.0
     */
    public function hookAddListingPreFields(): void
    {
        if ($GLOBALS['config']['security_img_add_listing'] && $GLOBALS['config']['add_listing_single_step']) {
            $GLOBALS['rlSmarty']->display(self::PLUGIN_DIR . 'reCaptcha_init.tpl');

            echo<<<HTML
            <script>
                var checkRecaptchaObjectExist = setInterval(function() {
                    if (typeof grecaptcha === 'object' && typeof grecaptcha.render === 'function') {
                        onloadCallback();
                        clearInterval(checkRecaptchaObjectExist);
                   }
                }, 100);
             </script>
HTML;
        }
    }

    /**
     * Clear compile directory after activation/deactivation of the plugin
     *
     * @since 3.0.0
     *
     * @return void
     */
    public function statusChanged(): void
    {
        $this->clearCompile();
    }

    /**
     * @hook  apMixConfigItem
     * @since 3.0.0
     */
    public function hookApMixConfigItem(&$value, &$systemSelects = null): void
    {
        $requiredFields = ['reCaptcha_type', 'reCaptcha2_theme', 'reCaptcha_position'];

        if ($value && $value['Key'] && !$systemSelects[$value['Key']] && in_array($value['Key'], $requiredFields, true)) {
            $systemSelects[] = $value['Key'];
        }
    }

    /**
     * @hook  apTplFooter
     * @since 2.3.0
     */
    public function hookApTplFooter(): void
    {
        if ($GLOBALS['controller'] !== 'settings') {
            return;
        }

        $GLOBALS['rlSmarty']->display(self::ADMIN_VIEW_DIR . 'settings.tpl');
    }

    /**
     * @since 3.0.0
     *
     * @return void
     */
    public function install(): void
    {
       $this->clearCompile();
    }

    /**
     * @since 3.0.0
     *
     * @return void
     */
    public function uninstall(): void
    {
       $this->clearCompile();
    }

    /**
     * Update to 2.0.0 version
     *
     * @since 3.0.0
     */
    public function update200(): void
    {
        $sql = "DELETE `T1`, `T2` FROM `" . RL_DBPREFIX . "config` AS `T1` ";
        $sql .= "LEFT JOIN `" . RL_DBPREFIX . "lang_keys` AS `T2` ON `T2`.`Key` = CONCAT('config+name+', `T1`.`Key`) ";
        $sql .= "WHERE `T1`.`Key` = 'reCaptcha_theme'";
        $GLOBALS['rlDb']->query($sql);
    }

    /**
     * Update to 2.2.0 version
     *
     * @since 3.0.0
     */
    public function update220(): void
    {
        $GLOBALS['rlDb']->query(
            "DELETE FROM `" . RL_DBPREFIX . "hooks` WHERE `Name` = 'listingDetailsBottomTpl' AND `Plugin` = 'reCaptcha'"
        );
    }

    /**
     * Update to 2.3.0 version
     *
     * @since 3.0.0
     */
    public function update230(): void
    {
        $GLOBALS['rlDb']->query(
            "DELETE FROM `{db_prefix}hooks` WHERE `Name` = 'apPhpConfigAfterUpdate' AND `Plugin` = 'reCaptcha'"
        );
        unlink(self::PLUGIN_DIR . 'tplFooter.tpl');
    }

    /**
     * Update to 2.3.3 version
     *
     * @since 3.0.0
     */
    public function update233(): void
    {
        $GLOBALS['rlDb']->query("DELETE FROM `{db_prefix}config` WHERE `Key` = 'reCaptcha_divider' LIMIT 1");
        $GLOBALS['rlDb']->query(
            "DELETE FROM `{db_prefix}lang_keys`
             WHERE `Plugin` = 'reCaptcha' AND `Key` = 'config+name+reCaptcha_divider'"
        );

        if (in_array('ru', array_keys($GLOBALS['languages']))) {
            $ru_phrases = json_decode(file_get_contents(self::PLUGIN_DIR . 'i18n/ru.json'), true);
            foreach ($ru_phrases as $key => $phrase) {
                $GLOBALS['rlDb']->updateOne([
                    'fields'  => ['Value' => $phrase],
                    'where'  => ['Key'   => $key, 'Code' => 'ru'],
                ], 'lang_keys');
            }
        }
    }

    /**
     * Update to 3.0.0 version
     */
    public function update300(): void
    {
        unlink(self::PLUGIN_DIR . 'recaptchalib.php');

        $GLOBALS['rlDb']->query(
            "DELETE FROM `{db_prefix}hooks` WHERE `Name` = 'apPhpConfigAfterUpdate' AND `Plugin` = 'reCaptcha'"
        );

        $GLOBALS['rlDb']->query(
            "DELETE FROM `{db_prefix}config` WHERE `Key` = 'reCaptcha_module' AND `Plugin` = 'reCaptcha'"
        );

        $GLOBALS['rlDb']->query(
            "DELETE FROM `{db_prefix}lang_keys`
             WHERE `Key` = 'config+name+reCaptcha_module' AND `Plugin` = 'reCaptcha'"
        );
    }

    /*** DEPRECATED METHODS ***/

    /**
     * @hook apPhpConfigAfterUpdate
     *
     * @deprecated 3.0.0
     * @since      2.3.2 - Moved part of code from clearCompile() method which was related with admin side only
     */
    public function hookApPhpConfigAfterUpdate(): void
    {}
}
