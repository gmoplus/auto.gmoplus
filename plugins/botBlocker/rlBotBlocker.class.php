<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: RLBOTBLOCKER.CLASS.PHP
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

class rlBotBLocker extends Flynax\Abstracts\AbstractPlugin implements Flynax\Interfaces\PluginInterface
{
    /**
     * @var array
     */
    public $botsList = [];

    /**
     * Plugin installation
     *
     * @since 1.1.0
     */
    public function install()
    {
        $bots = [
            'allowed' => 'google,yandex,facebookexternalhit',
            'denied'  => 'Sogou,msnbot,daum.net,grapeshot,baidu,qwant,BLEXBot,DotBot,AhrefsBot,SemrushBot,mj12bot,trendictionbot,worldping-api,magpie-crawler,CCBot,Bytespider,Amazonbot,ClaudeBot,gptbot,meta-externalagent,Barkrowler,DataForSeoBot',
        ];

        $this->updateBotsList($bots);
    }

    /**
     * @hook init
     */
    public function hookInit()
    {
        global $config, $rlDb, $reefless;

        if (!$reefless->isBot()) {
            return;
        }

        $blockRequest = false;
        switch ($config['botB_restriction_mode']) {
            case 'deny':
                $bots = $this->getDeniedBotsList();
                $bots = $bots ? explode(',', $bots) : [];

                if ($bots && (bool) preg_match('/' . implode('|', $bots) . '/i', $_SERVER['HTTP_USER_AGENT'])) {
                    $blockRequest = true;
                }
                break;
            case 'allow':
                $bots = $this->getAllowedBotsList();
                $bots = $bots ? explode(',', $bots) : [];
                $blockRequest = true;

                if ($bots && (bool) preg_match('/' . implode('|', $bots) . '/i', $_SERVER['HTTP_USER_AGENT'])) {
                    $blockRequest = false;
                }
                break;
        }

        if ($blockRequest) {
            $rlDb->connectionClose();

            header('HTTP/1.1 403 Forbidden');
            exit;
        }
    }

    /**
     * @hook apPhpConfigBeforeUpdate
     */
    public function hookApPhpConfigBeforeUpdate()
    {
        global $update;

        if (!$update) {
            return;
        }

        $bots = [];
        foreach ((array) $update as &$configData) {
            if ($configData['where']['Key'] === 'botB_bots_list') {
                self::prepareBotsList($configData['fields']['Default']);
                $bots['denied'] = $configData['fields']['Default'];
            } elseif ($configData['where']['Key'] === 'botB_allowed_bots_list') {
                self::prepareBotsList($configData['fields']['Default']);
                $bots['allowed'] = $configData['fields']['Default'];
            }
        }

        // Exit if now admin update other configs
        if (!isset($bots['denied']) && !isset($bots['allowed'])) {
            return;
        }

        if (!isset($bots['denied'])) {
            $bots['denied'] = $this->getDeniedBotsList();
        }

        if (!isset($bots['allowed'])) {
            $bots['allowed'] = $this->getAllowedBotsList();
        }

        $this->updateBotsList($bots);
    }

    /**
     * @hook apPhpConfigBottom
     */
    public function hookApPhpConfigBottom()
    {
        if (!empty($_POST)) {
            return false;
        }

        foreach ($GLOBALS['rlSmarty']->_tpl_vars['configs'] as &$data) {
            foreach ($data as &$configData) {
                if ($configData['Key'] === 'botB_bots_list') {
                    $configData['Default'] = $this->getDeniedBotsList();
                } elseif ($configData['Key'] === 'botB_allowed_bots_list') {
                    $configData['Default'] = $this->getAllowedBotsList();
                }
            }
        }
    }

    /**
     * @hook apTplContentBottom
     */
    public function hookApTplContentBottom()
    {
        global $cInfo;

        if ('settings' !== $cInfo['Controller']) {
            return false;
        }

        echo <<< HTML
            <script>
                let \$botB_restrictionType = $('[name="post_config[botB_restriction_mode][value]"]');

                $(function(){
                    botBlockerModuleHandler();

                    \$botB_restrictionType.change(function(){
                        botBlockerModuleHandler();
                    });
                });

                const botBlockerModuleHandler = function() {
                    let restrictionType  = \$botB_restrictionType.filter(':checked').val(),
                        \$deniedBotsList = $('[name="post_config[botB_bots_list][value]"]'),
                        \$allowedBotsList = $('[name="post_config[botB_allowed_bots_list][value]"]');

                    let \$deniedBotsListTr = \$deniedBotsList.closest('tr'),
                        \$allowedBotsListTr = \$allowedBotsList.closest('tr');

                    if (restrictionType === 'allow') {
                        \$deniedBotsList.attr('disabled', true).addClass('disabled');
                        \$allowedBotsList.removeAttr('disabled').removeClass('disabled');

                        \$deniedBotsListTr.addClass('hide');
                        \$allowedBotsListTr.removeClass('hide');
                    } else if (restrictionType === 'deny') {
                        \$deniedBotsList.removeAttr('disabled').removeClass('disabled');
                        \$allowedBotsList.attr('disabled', true).addClass('disabled');

                        \$deniedBotsListTr.removeClass('hide');
                        \$allowedBotsListTr.addClass('hide');
                    }
                }
            </script>
HTML;
    }

    /**
     * System use 'Code' column of this hook for cache
     * @hook boot
     */
    public function hookBoot()
    {
        return true;
    }

    /**
     * Prepare plugin configs values
     *
     * @since 1.1.0
     * @hook apMixConfigItem
     *
     * @param array $option - Data of option
     */
    public function hookApMixConfigItem(&$option)
    {
        if ($option['Key'] === 'botB_restriction_mode') {
            $option['Values']  = ['allow', 'deny'];
            $option['Display'] = [$GLOBALS['lang']['botB_allow'], $GLOBALS['lang']['botB_deny']];
        }
    }

    /**
     * Get list of bots which must be blocked from config
     *
     * @since 1.1.0
     *
     * @param string|null $botsType Necessary type of bots (allowed|denied)
     * @return string
     */
    public function getBotsList(?string $botsType = null)
    {
        global $rlDb, $config;

        if (empty($this->botsList)) {
            $this->botsList = json_decode($rlDb->getOne(
                'Code',
                "`Name` = 'boot' AND `Plugin` = 'botBlocker'",
                'hooks'
            ), true);
        }

        if (empty($this->botsList)) {
            $this->botsList = [
                'denied'  => $config['botB_bots_list'],
                'allowed' => $config['botB_allowed_bots_list']
            ];
        }

        return $botsType ? $this->botsList[$botsType] : $this->botsList;
    }

    /**
     * Get list of bots which must be allowed
     *
     * @since 1.1.0
     *
     * @return string
     */
    public function getAllowedBotsList()
    {
        return $this->getBotsList('allowed');
    }

    /**
     * Get list of bots which must be denied
     *
     * @since 1.1.0
     *
     * @return string
     */
    public function getDeniedBotsList()
    {
        return $this->getBotsList('denied');
    }

    /**
     * Update list of bots
     *
     * @since 1.1.0
     *
     * @param array $bots List of bots, must contain 'denied' and 'allowed' keys
     * @return bool
     */
    public function updateBotsList(array $bots): bool
    {
        global $rlDb;

        if (!is_array($bots) || !isset($bots['denied']) || !isset($bots['allowed'])) {
            return false;
        }

        $rlDb->updateOne([
            'fields' => ['Code' => json_encode($bots)],
            'where'  => ['Name' => 'boot', 'Plugin' => 'botBlocker'],
        ], 'hooks', ['Code']);

        $rlDb->updateOne([
            'fields' => ['Default' => is_array($bots['denied']) ? implode(',', $bots['denied']) : rtrim($bots['denied'], ',')],
            'where'  => ['Key'     => 'botB_bots_list', 'Plugin' => 'botBlocker'],
        ], 'config');

        $rlDb->updateOne([
            'fields' => ['Default' => is_array($bots['allowed']) ? implode(',', $bots['allowed']) : rtrim($bots['allowed'], ',')],
            'where'  => ['Key'     => 'botB_allowed_bots_list', 'Plugin' => 'botBlocker'],
        ], 'config');

        return true;
    }

    /**
     * Prepare bots list (remove spaces and duplicates)
     *
     * @since 1.1.0
     *
     * @param string $list Bots list
     *
     * @return void
     */
    public static function prepareBotsList(string &$list): void
    {
        $list = explode(',', $list);
        $list = array_map('trim', $list);
        $list = array_filter($list);
        $list = implode(',', $list);
    }

    /**
     * Update to 1.0.2 version
     */
    public function update102()
    {
        global $rlDb, $config;

        $botsList = $rlDb->getOne('Code', "`Name` = 'boot' AND `Plugin` = 'botBlocker'", 'hooks') ?: $config['botB_bots_list'];
        $botsList = $botsList ? explode(',', $botsList) : [];

        $newBots = [
            'Bytespider',
            'Amazonbot',
            'ClaudeBot',
            'gptbot',
            'facebookexternalhit',
            'meta-externalagent',
            'Barkrowler',
            'DataForSeoBot',
        ];

        $botsList = array_merge($botsList, $newBots);

        $rlDb->updateOne([
            'fields' => ['Code' => implode(',', $botsList)],
            'where'  => ['Name' => 'boot', 'Plugin' => 'botBlocker'],
        ], 'hooks');
    }

    /**
     * Update to 1.1.0 version
     */
    public function update110()
    {
        global $rlDb, $config, $languages;

        $botsList = $rlDb->getOne('Code', "`Name` = 'boot' AND `Plugin` = 'botBlocker'", 'hooks') ?: $config['botB_bots_list'];
        $botsList = $botsList ? explode(',', $botsList) : [];
        $botsList = array_unique($botsList);

        $index = array_search('facebookexternalhit', $botsList);

        if ($index) {
            unset($botsList[$index]);
        }

        $bots = [
            'allowed' => 'google,yandex,facebookexternalhit',
            'denied'  => implode(',', $botsList),
        ];

        $this->updateBotsList($bots);

        $rlDb->query(
            "DELETE FROM `{db_prefix}config`
             WHERE `Key` = 'botB_module' AND `Plugin` = 'botBlocker'"
        );

        $rlDb->query(
            "DELETE FROM `{db_prefix}lang_keys`
             WHERE `Key` = 'config+name+botB_module' AND `Plugin` = 'botBlocker'"
        );

        if (array_key_exists('ru', $languages)) {
            $russianTranslation = json_decode(file_get_contents(RL_UPLOAD . 'botBlocker/i18n/ru.json'), true);

            $rlDb->updateOne([
                'fields' => ['Value' => $russianTranslation['title_botBlocker']],
                'where'  => ['Key'   => 'title_botBlocker', 'Code' => 'ru'],
            ], 'lang_keys');

            $rlDb->updateOne([
                'fields' => ['Value' => $russianTranslation['description_botBlocker']],
                'where'  => ['Key'   => 'description_botBlocker', 'Code' => 'ru'],
            ], 'lang_keys');
        }
    }
}
