<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLCOOKIESPOLICY.CLASS.PHP
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
 * Class rlCookiesPolicy
 *
 * @since 1.1.0
 */
class rlCookiesPolicy
{
    /**
     * @hook tplHeader
     */
    public function hookTplHeader()
    {
        if ($this->isPopupNecessary()) {
            if ($GLOBALS['config']['cookiesPolicy_view'] == 'popup') {
                $GLOBALS['rlStatic']->addFooterCSS(RL_PLUGINS_URL . 'cookiesPolicy/static/style.css');
            }

            if ($GLOBALS['config']['cp_block_all_cookies'] && !$_COOKIE['cookies_policy']) {
                $this->removeSession();
            }
        }
    }

    /**
     * @hook tplFooter
     */
    public function hookTplFooter()
    {
        if ($this->isPopupNecessary()) {
            $GLOBALS['rlSmarty']->display(RL_ROOT . 'plugins/cookiesPolicy/tab.tpl');

            if ($GLOBALS['config']['cp_block_all_cookies'] && !$_COOKIE['cookies_policy']) {
                echo "<script>
                    $(function() {
                        // Remove all exist cookies
                        document.cookie.split('; ').forEach(function(name) {
                            eraseCookie(name.split('=')[0]);
                        });
    
                        // Re-declare function to prevent creation of new cookies via system function
                        var createCookie = function(){};
                    });
                    </script>";
            }
        }
    }

    /**
     * @hook ajaxRequest
     */
    public function hookAjaxRequest()
    {
        $this->removeSession();
    }

    /**
     * @hook phpPreCreateCookie
     */
    public function hookPhpPreCreateCookie(&$cookie_name)
    {
        if (!defined('REALM') && !$_COOKIE['cookies_policy'] && $GLOBALS['config']['cp_block_all_cookies']) {
            $cookie_name = '';
        }
    }

    /**
     * @hook smartyFetchHook
     */
    public function hookSmartyFetchHook(&$compiled_content)
    {
        global $config, $domain_info;

        if (($config['cp_block_all_cookies'] && $_COOKIE['cookies_policy'])
            || !$config['cp_block_all_cookies']
            || defined('REALM')
            || !$domain_info
        ) {
            return;
        }

        // Prevent checking of content which doesn't have any scripts
        if (false === strpos($compiled_content, '<script ') && false === strpos($compiled_content, '<link ')) {
            return;
        }

        // Remove js
        preg_match_all('/(<script.*src=[\'\"]?([^\s\'\"]+).*?\/?>(<\/script>)?)/', $compiled_content, $matches, PREG_SET_ORDER);

        if ($matches[0]) {
            foreach ($matches as $match) {
                if ($match[2] && false === strpos($match[2], $domain_info['host'])) {
                    $compiled_content = str_replace($match[0], '', $compiled_content);
                }
            }
        }

        // Remove css
        preg_match_all('/(<link.*href=[\'\"]?([^\s\'\"]+).*?\/?>)/', $compiled_content, $matches, PREG_SET_ORDER);

        if ($matches[0]) {
            foreach ($matches as $match) {
                if ($match[2] && false === strpos($match[2], $domain_info['host'])) {
                    $compiled_content = str_replace($match[0], '', $compiled_content);
                }
            }
        }
    }

    /**
     * Hide settings depending on view mode
     *
     * @since 1.3.0
     *
     * @hook apTplContentBottom
     */
    public function hookApTplContentBottom()
    {
        global $config, $cInfo;

        if ($cInfo['Key'] == 'config') {
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'cookiesPolicy' . RL_DS . 'admin' . RL_DS . 'js.tpl');
        }
    }

    /**
     * Remove default option from the view setting
     *
     * @since 1.3.0
     *
     * @hook apMixConfigItem
     */
    public function hookApMixConfigItem(&$option, &$systemSelects)
    {
        if (in_array($option['Key'], ['cookiesPolicy_view', 'cookiesPolicy_position'])) {
            $systemSelects[] = $option['Key'];
        }
    }

    /**
     * Remove session from server when user decline cookies
     *
     * @return bool
     */
    protected function removeSession()
    {
        if (!$_COOKIE['cookies_policy']
            && $GLOBALS['config']['cp_block_all_cookies']
            && ini_get('session.use_cookies')
        ) {
            $list = session_get_cookie_params();
            $name = session_name();
            $time = time() - 42000;

            setcookie($name, '', $time, $list['path'], $list['domain'], $list['secure'], $list['httponly']);
            session_destroy();
        }
    }

    /**
     * Checking of necessary to show the pop-up
     *
     * @since 1.2.0
     *
     * @return bool
     */
    protected function isPopupNecessary()
    {
        global $config;

        return false === IS_BOT
            && false === strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome-Lighthouse')
            && false === strpos($_SERVER['HTTP_USER_AGENT'], 'Speed Insights')
            && (false !== strpos($config['cookiesPolicy_country'], $_SESSION['GEOLocationData']->Country_code)
                || $config['cp_block_all_cookies']
        );
    }
}
