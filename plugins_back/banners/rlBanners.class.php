<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: ADD_BANNER.INC.PHP
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
 *  Flynax Classifieds Software 2024 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

use Flynax\Utils\Valid;
use Flynax\Utils\Util;

class rlBanners extends Flynax\Abstracts\AbstractPlugin implements Flynax\Interfaces\PluginInterface
{
    /**
     * Banner boxes
     **/
    public $bannerBoxes = [];

    /**
     * Banners list
     **/
    public $bannersList = [];

    /**
     * @var calculate items
     **/
    public $calc;

    /**
     * Collect banner IDs to update their time of display
     *
     * @since 2.7.0
     *
     * @var array
     */
    protected $shownBannerIDs = [];

    /**
     * Get steps for add banner process
     **/
    public static function getSteps()
    {
        global $lang;

        $steps = [
            'plan' => [
                'name' => $lang['select_plan'],
                'caption' => true,
                'path' => 'select-a-plan',
            ],
            'form' => [
                'name' => $lang['fill_out_form'],
                'caption' => true,
                'path' => 'fill-out-a-form',
            ],
            'media' => [
                'name' => $lang['banners_addMedia'],
                'caption' => true,
                'path' => 'add-media',
            ],
            'checkout' => [
                'name' => $lang['checkout'],
                'caption' => true,
                'path' => 'checkout',
            ],
            'done' => [
                'name' => $lang['reg_done'],
                'path' => 'done',
            ],
        ];

        return $steps;
    }

    /**
     * Unique key by name
     **/
    public function uniqKeyByName($name = false, $table = false, $prefix = false)
    {
        global $rlDb;

        // load the utf8 lib
        if (false === function_exists('utf8_is_ascii')) {
            loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');
        }

        if (!utf8_is_ascii($name)) {
            $name = utf8_to_ascii($name);
        }
        $name = strtolower($GLOBALS['rlValid']->str2key($name));

        // set prefix
        if ($prefix !== false) {
            $name = $prefix . $name;
        }

        // check on exists key
        $exists = $rlDb->getRow("
            SELECT COUNT(`Key`) AS `count` FROM `{db_prefix}{$table}`
            WHERE `Key` REGEXP '^{$name}(_[0-9]+)*$'
        ");

        if ($exists['count'] > 0) {
            return "{$name}_" . intval($exists['count'] + 1);
        }

        return $name;
    }

    /**
     * Get all plans by banners
     *
     * @param string $field
     * @param string $value
     * @param string $action
     *
     * @return array
     */
    public function getBannerPlans($field = null, $value = null, $action = 'all')
    {
        global $rlDb, $rlLang, $account_info;

        $where = !defined('REALM') ? "AND `Admin` = '0' AND (FIND_IN_SET('{$account_info['Type']}', `Allow_for`) > 0 OR `Allow_for` = '')" : '';

        if ($field && $value) {
            $where .= " AND `{$field}` = '{$value}'";
        }
        $where .= " ORDER BY `Position`";

        $plans = $rlDb->fetch('*', ['Status' => 'active'], $where, null, 'banner_plans', $action);
        $plans = $rlLang->replaceLangKeys($plans, 'banner_plans', ['name', 'des']);

        return $plans;
    }

    /**
     *
     **/
    public function makeBoxContent($boxKey = false, $limit = 5, $info = false)
    {
        $content = '
            global $reefless, $rlSmarty;

            $reefless -> loadClass("Banners", null, "banners");
            $banners = $GLOBALS["rlBanners"] -> getBanners("' . $boxKey . '", "' . $limit . '");
            $rlSmarty -> assign("banners", $banners);
            $rlSmarty -> assign("info", array(
                    "limit"  => ' . intval($info['limit']) . ',
                    "width"  => ' . intval($info['width']) . ',
                    "height" => ' . intval($info['height']) . ',
                    "slider" => ' . intval($info['slider']) . ',
                    "folder" => "banners/"
                )
            );
            unset($banners);

            $rlSmarty -> display(RL_PLUGINS ."banners". RL_DS ."banners_box.tpl");
        ';

        return preg_replace("'(\r|\n|\t)'", "", $content);
    }

    /**
     *
     */
    public function makeFakeCategoryBox($boxKey = false, $categoryKey = false, $info = false)
    {
        $hook = '
            $boxInfo = array(
                "width" => ' . $info['width'] . ',
                "height" => ' . $info['height'] . ',
                "folder" => "banners/",
            );
            $GLOBALS["rlBanners"] -> boxBetweenCategories("' . $categoryKey . '", "' . $boxKey . '", $boxInfo);
        ';

        return preg_replace("'(\r|\n|\t)'", "", $hook);
    }

    /**
     * Get my banners
     *
     * @param int $accountID - account ID
     **/
    public function getMyBanners(
        $accountID = false,
        $order = 'Last_show',
        $order_type = 'asc',
        $start = 0,
        $limit = false
    ) {
        global $rlDb;

        // define start position
        $start = $start > 1 ? ($start - 1) * $limit : 0;

        $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `T1`.*, `T2`.`Value` AS `name`, `T3`.`Key`, `T3`.`Plan_type`, COUNT(`T4`.`ID`) AS `clicks` ";
        $sql .= "FROM `{db_prefix}banners` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON CONCAT('banners+name+', `T1`.`ID`) = `T2`.`Key` AND `T2`.`Code` = '" . RL_LANG_CODE . "' AND `T2`.`Plugin` = 'banners' ";
        $sql .= "LEFT JOIN `{db_prefix}banner_plans` AS `T3` ON `T1`.`Plan_ID` = `T3`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}banners_click` AS `T4` ON `T1`.`ID` = `T4`.`Banner_ID` ";
        $sql .= "WHERE `T1`.`Account_ID` = '{$accountID}' AND `T1`.`Status` <> 'trash' GROUP BY `T1`.`ID` ";
        if ($order) {
            if ($order == 'Clicks') {
                $sql .= "ORDER BY `clicks` " . strtoupper($order_type) . " ";
            } else {
                $sql .= "ORDER BY `T1`.`{$order}` " . strtoupper($order_type) . " ";
            }
        } else {
            $sql .= "ORDER BY `T1`.`Date_release` DESC ";
        }

        $sql .= "LIMIT {$start},{$limit}";
        $banners = $rlDb->getAll($sql);

        if (empty($banners)) {
            return false;
        }

        $calc = $rlDb->getRow("SELECT FOUND_ROWS() AS `calc`");
        $this->calc = $calc['calc'];

        return $banners;
    }

    /**
     * Prepare banners list
     */
    public function prepareBannersList()
    {
        global $block_keys, $blocks, $rlHook, $config, $rlDb;

        $isExistsHeaderBox = false;
        $isExistsGridBox = false;

        foreach ($block_keys as $key => $value) {
            if (false === strpos($key, 'bb_')) {
                continue;
            }

            if (false === $limit = $this->getBoxLimit($blocks[$key])) {
                continue;
            }

            if (false === $this->updateGlobalBanners($key, $limit)) {
                if ($config['banners_hide_empty_boxes']
                    && (!isset($_GET['listing_id']) || !empty($_SESSION['geo_location']))
                ) {
                    unset($block_keys[$key], $blocks[$key]);
                }
            }

            if (isset($blocks[$key])) {
                $this->bannerBoxes[] = $key;
            }
        }

        $reflection = new ReflectionProperty('rlHook', 'hooks');
        $reflection->setAccessible(true);
        $hooks = $reflection->getValue($rlHook);

        // search banners in hooks [boxes between categories]
        $tbc_name = 'tplBetweenCategories';
        $boxes_betweenCategories = $hooks[$tbc_name];

        if (!empty($boxes_betweenCategories)) {
            foreach ($boxes_betweenCategories as $index => $box) {
                if (false !== $key = $this->getBoxKeyFromHook($box)) {
                    if (false === $this->updateGlobalBanners($key, 1) && $config['banners_hide_empty_boxes']) {
                        unset($hooks[$tbc_name]);
                        $reflection->setValue($rlHook, $hooks);
                    }
                }
            }

            if ($config['banners_hide_empty_boxes']) {
                if (empty($hooks[$tbc_name])) {
                    unset($hooks[$tbc_name]);
                    $reflection->setValue($rlHook, $hooks);
                }
            }
        }

        if ($config['banners_hide_empty_boxes']) {
            if ($blocks['header_banner']) {
                $isExistsHeaderBox = true;
            }
            if ($blocks['integrated_banner']) {
                $isExistsGridBox = true;
            }
            $GLOBALS['rlCommon']->defineBlocksExist($blocks);
        }

        if (!empty($this->bannerBoxes)) {
            $GLOBALS['rlSmarty']->assign('isBannersExistOnPage', true);
        }

        register_shutdown_function(function () {

            if (empty($this->shownBannerIDs)) {
                return;
            }

            $GLOBALS['rlDb']->query("
                UPDATE `{db_prefix}banners` SET `Shows` = `Shows` + 1, `Last_show` = UNIX_TIMESTAMP()
                WHERE `ID` IN ('" . implode("','", $this->shownBannerIDs) . "')
            ");
        });

        if ($config['banners_hide_empty_boxes']) {

            if ($isExistsHeaderBox) {
                if (!$blocks['header_banner']) {
                    $GLOBALS['config']['header_banner_space'] = false;
                }
            }
            if ($isExistsGridBox) {
                if (!$blocks['integrated_banner']) {
                    $GLOBALS['config']['banner_in_grid_position_option'] = false;
                }
            }
        }
    }

    /**
     * Get box limit
     *
     * @param array $box
     *
     * @return bool|int
     */
    public function getBoxLimit($box)
    {
        global $config;

        if (false !== $info = $this->getBoxInfo($box['Content'])) {
            $limit = (int) $info['limit'];

            if ('integrated_banner' === $box['Side']) {
                $boxesCount = (int) floor($config['listings_per_page'] / $config['banner_in_grid_position']);
                $limit = max(1, $limit * ($boxesCount - 1));
            }

            return $limit;
        }

        return false;
    }

    /**
     * Get Box Info
     *
     * @param string &$content  - content of boxes
     * @param bool    $between  - box between categories trigger
     * @param bool    $withSize - include size info
     */
    public function getBoxInfo(&$content, $between = false, $withSize = false)
    {
        if ($between === true) {
            $regex = '/boxBetweenCategories\("[a-z0-9_]+", "([a-z0-9_]+)", \$boxInfo\);/i';
        } else {
            $regex = '/getBanners\("([a-z0-9_]+)", "([0-9]+)"\);/i';
        }

        preg_match($regex, $content, $matches);
        if (!empty($matches)) {
            $info = [];
            if (isset($matches[1])) {
                $info['key'] = $matches[1];
            }

            if (isset($matches[2])) {
                $info['limit'] = $matches[2];
            }

            //
            if (!empty($info)) {
                if (!$between && $withSize) {
                    preg_match('/"width"\s*=>\s*(\d+),\s*"height"\s*=>\s*(\d+)/i', $content, $size);
                    $info['width'] = intval($size[1]);
                    $info['height'] = intval($size[2]);
                }

                return $info;
            }

            return false;
        }

        return false;
    }

    /**
     * Update global banners
     *
     * @param string $key
     * @param int    $limit
     *
     * @return bool
     */
    public function updateGlobalBanners($key, $limit = 1)
    {
        $banners = $this->bannersInBox($key, $limit);

        if (!empty($banners)) {
            $this->bannersList[$key] = $banners;
            unset($banners);

            return true;
        }

        return false;
    }

    /**
     * Banners in box by key
     *
     * @param string $boxKey
     * @param int    $limit
     * @param bool   $externalGeoData
     *
     * @return array
     */
    public function bannersInBox($boxKey, $limit = 1, $externalGeoData = false)
    {
        global $geo_filter_data, $rlDb;

        if (defined('IS_BOT') && IS_BOT) {
            return [];
        } elseif ($limit <= 0 || !$boxKey) {
            return [];
        }

        $geo_location = $externalGeoData ? $externalGeoData : $geo_filter_data['location'];
        $countryCode = $_SESSION['GEOLocationData']->Country_code;
        $geo_filter = 1;

        if ($this->mfActive()) {
            if (!empty($geo_location)) {
                $geo_filter = '';
                foreach ($geo_location as $index => $location) {
                    $geo_filter .= "FIND_IN_SET('{$location['Key']}', `T2`.`Regions`) OR ";
                }
                $geo_filter = '(' . rtrim($geo_filter, 'OR ') . ')';
            } else {
                $geo_filter = 0;
            }
        }

        $sql = "SELECT `T1`.`ID`, `T1`.`Type`, `T1`.`Image`, `T1`.`Link`, `T1`.`Html`, `T1`.`Responsive`, `T1`.`Follow` ";
        $sql .= "FROM `{db_prefix}banners` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}banner_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` AND `T2`.`Status` = 'active' ";
        $sql .= "WHERE `T1`.`Box` = '{$boxKey}' AND ";

        $sql .= "IF(`T2`.`Geo` = '2', (FIND_IN_SET('{$countryCode}', `T2`.`Country`) = 0), ";
        $sql .= "IF(`T2`.`Geo` = '3', {$geo_filter}, (FIND_IN_SET('{$countryCode}', `T2`.`Country`) > 0 OR `T2`.`Geo` = '1'))) AND ";

        $sql .= "IF(`T2`.`Plan_Type` = 'views', IF(`T1`.`Shows` < `T1`.`Date_to` OR `T1`.`Date_to` = '0', 1, 0), IF(`T1`.`Date_to` > UNIX_TIMESTAMP() OR `T1`.`Date_to` = '0', 1, 0)) = 1 ";
        $sql .= "AND `T1`.`Status` = 'active' AND `T2`.`Status` = 'active' AND `T1`.`Image` <> '' ";
        $sql .= "GROUP BY `T1`.`ID` ORDER BY `T1`.`Last_show` LIMIT {$limit}";
        $banners = $rlDb->getAll($sql);

        if (!empty($banners)) {
            foreach ($banners as $key => $row) {
                if ($row['Type'] == 'html') {
                    $html = str_replace('<br />', "\n", $row['Html']);
                    $banners[$key]['Html'] = $html;
                } else {
                    if ($row['Type'] == 'image') {
                        if (!is_numeric(strpos($row['Link'], RL_URL_HOME))) {
                            $banners[$key]['externalLink'] = true;
                        }
                    }
                }

                $banners[$key]['name'] = $GLOBALS['lang']['banners+name+' . $row['ID']];
            }
        }

        return $banners;
    }

    /**
     * mfActive
     */
    public function mfActive()
    {
        return isset($GLOBALS['plugins']['multiField']);
    }

    /**
     * Get box key from hook
     *
     * @param array $hook
     *
     * @return string|bool
     */
    public function getBoxKeyFromHook($hook)
    {
        if (false !== $info = $this->getBoxInfo($hook['code'], true)) {
            return $info['key'];
        }

        return false;
    }

    /**
     * Get box key
     *
     * @param string $content - content of hook
     */
    public function getBoxKey($content = false)
    {
        if (false !== $info = $this->getBoxInfo($content)) {
            return $info['key'];
        }

        return false;
    }

    /**
     * Fake box between categories
     *
     * @hook tplBetweenCategories
     *
     * @param bool $categoryKey
     * @param bool $boxKey
     * @param bool $boxInfo
     */
    public function boxBetweenCategories($categoryKey, $boxKey, $boxInfo)
    {
        global $rlSmarty;

        if ($rlSmarty->_tpl_vars['cat']['Key'] == $categoryKey) {
            $banners = $this->getBanners($boxKey);

            $rlSmarty->assign('banners', $banners);
            $rlSmarty->assign('info', $boxInfo);
            $rlSmarty->assign('boxBetweenCategories', true);

            $rlSmarty->display(__DIR__ . '/banners_box.tpl');
        }
    }

    /**
     * Get banners
     *
     * @param string $boxKey
     * @param int    $limit
     *
     * @return array|false
     */
    public function getBanners($boxKey, $limit = 1)
    {
        if (!empty($this->bannersList[$boxKey])) {
            $banners = array_splice($this->bannersList[$boxKey], 0, $limit);
            $ids = array_column($banners, 'ID');
            $this->shownBannerIDs = array_merge($this->shownBannerIDs, $ids);

            // hotfix for issue with xAjax
            if (isset($_POST['xjxfun']) && 'integrated_banner' === $GLOBALS['blocks'][$boxKey]['Side']) {
                $js = <<<JS
                setTimeout(function () {
                    bannersSlideShow();
                    callScriptInHtmlBanners();
                }, 300);

JS;
                $GLOBALS['_response']->script($js);
            }

            return $banners;
        }
        return false;
    }

    /**
     * Get banners by listing location (MF)
     *
     * @param array &$data - listing data
     */
    public function getBannersByListingLocation(&$data)
    {
        global $block_keys, $blocks, $config, $geo_filter_data;

        if (!$data || isset($geo_filter_data['location'])) {
            return false;
        }

        $ldl_field = 'b_country';
        $egf_location = [];
        if (!empty($data[$ldl_field])) {
            array_push($egf_location, ['Key' => $data[$ldl_field]]);
        }

        if (!empty($data[$ldl_field . '_level1'])) {
            array_push($egf_location, ['Key' => $data[$ldl_field . '_level1']]);
        }

        if (!empty($data[$ldl_field . '_level2'])) {
            array_push($egf_location, ['Key' => $data[$ldl_field . '_level2']]);
        }

        // search banners in boxes
        foreach ($block_keys as $key => $value) {
            if ((bool) preg_match('/^bb_/', $key)) {
                $limit = $this->getBoxLimit($blocks[$key]);
                $banners = $this->bannersInBox($key, $limit, $egf_location);

                // update banners
                if (!empty($banners)) {
                    $this->bannersList[$key] = $banners;
                    unset($banners);
                } else {
                    // remove box
                    if ($config['banners_hide_empty_boxes'] && !array_key_exists($key, $this->bannersList)) {
                        unset($block_keys[$key], $blocks[$key]);
                    }
                }
            }
        }

        // redefine boxes if necessary
        if ($config['banners_hide_empty_boxes']) {
            $GLOBALS['rlCommon']->defineBlocksExist($blocks);
        }
    }

    /**
     * Save uniq banner clicks
     *
     * @param int $id
     */
    public function ajaxBannerClick($id)
    {
        global $rlDb;

        if (!$id = (int) $id) {
            return;
        }

        $sessionHash = $this->sessionHash();
        $hasClicked = (int) $rlDb->getOne('ID', "`Banner_ID` = {$id} AND `Hash` = '{$sessionHash}'", 'banners_click');

        if ($hasClicked) {
            return;
        }

        $click = [
            'Banner_ID' => $id,
            'Hash' => $sessionHash,
            'Country' => $_SESSION['GEOLocationData']->Country_code,
        ];
        $rlDb->insert($click, 'banners_click');
    }

    /**
     * Get current session hash
     **/
    public function sessionHash()
    {
        return md5(Util::getClientIP() . $_SERVER['HTTP_USER_AGENT']);
    }

    /**
     * Create a new banner
     *
     * @param bool $planInfo
     * @param bool $data
     *
     * @return bool|int
     */
    public function create($planInfo, $data)
    {
        global $rlDb;

        if (!$planInfo || !$data) {
            return false;
        }

        $now = time();
        $insert = [
            'Plan_ID' => (int) $planInfo['ID'],
            'Box' => $data['banner_box'],
            'Type' => $data['banner_type'],
            'Account_ID' => (int) $data['account_id'],
            'Date_release' => $now,
            'Link' => $data['banner_type'] == 'image' ? $data['link'] : '',
            'Follow' => ($data['banner_type'] == 'image' && !empty($data['link'])) ? intval($data['nofollow']) : 0,
            'Status' => (defined('REALM') && REALM == 'admin') ? $data['status'] : 'incomplete',
        ];

        if ($data['banner_type'] == 'html') {
            $this->doCleanHtmlBanner($data['html']);

            $insert['Html'] = $data['html'];
            $insert['Responsive'] = (int) $data['responsive'];
            $insert['Image'] = 'html';
        }

        if ($planInfo['Price'] == 0 || (defined('REALM') && REALM == 'admin')) {
            $insert['Date_to'] = $planInfo['Plan_Type'] == 'period'
                ? ($planInfo['Period'] == 0 ? 0 : $now + ($planInfo['Period'] * 86400))
                : $planInfo['Period'];

            $insert['Date_from'] = $now;
            $insert['Pay_date'] = $now;
        }

        $sql = "INSERT INTO `{db_prefix}banners` ( `" . implode('`, `', array_keys($insert)) . "` ) VALUES ";
        $sql .= "( '" . implode("', '", array_values($insert)) . "' )";

        if ($rlDb->query($sql)) {
            $bannerId = $rlDb->insertID();
            $allLangs = $GLOBALS['languages'];
            $langKeysInsert = [];

            foreach ($allLangs as $key => $value) {
                $langKeysInsert[] = [
                    'Code' => $allLangs[$key]['Code'],
                    'Module' => 'common',
                    'Status' => 'active',
                    'Key' => 'banners+name+' . $bannerId,
                    'Value' => (count($allLangs) > 1
                        ? (!empty($data['name'][$allLangs[$key]['Code']])
                            ? $data['name'][$allLangs[$key]['Code']]
                            : $data['name'][$GLOBALS['config']['lang']]
                        ) : $data['name']
                    ),
                    'Plugin' => 'banners',
                ];
            }
            $rlDb->insert($langKeysInsert, 'lang_keys');

            return $bannerId;
        }

        return false;
    }

    /**
     * Update exists banner (my banners)
     *
     * @since 2.8.0 - Method renamed from `update()` to `updateBanner()`
     *
     * @param int   $bannerId - banner id
     * @param array $data     - banner data
     **/
    public function updateBanner($bannerId = false, $data = null, $banner_info = false)
    {
        global $account_info, $lang, $config, $reefless;

        if (!$bannerId || !$data) {
            return false;
        }

        $data['status'] = $config['banners_auto_approval'] ? 'active' : 'pending';
        $data['link'] = $data['banner_type'] != 'image' ? '' : $data['link'];

        $sql = "UPDATE `{db_prefix}banners` SET ";
        $sql .= "`Link` = '{$data['link']}', `Type` = '{$data['banner_type']}', ";
        $sql .= "`Status` = IF(`Status` NOT IN('incomplete', 'expired'), '{$data['status']}', `Status`) ";

        if ($data['banner_type'] == 'html') {
            $this->doCleanHtmlBanner($data['html']);

            $sql .= ",`Html` = '{$data['html']}' ";
            $sql .= ",`Responsive` = '" . intval($data['responsive']) . "', `Image` = 'html' ";
        }
        $sql .= "WHERE `ID` = " . $bannerId;

        if ($GLOBALS['rlDb']->query($sql)) {
            $this->bannerNameHandler($bannerId, $data['name']);
        }

        // remove old files if exists
        if ($data['banner_type'] != 'image') {
            $possible_old_banner_file = RL_FILES . 'banners' . RL_DS . $banner_info['Image'];
            if ($banner_info && $banner_info['Image'] != 'html' && file_exists($possible_old_banner_file)) {
                @unlink($possible_old_banner_file);
            }
        }

        // send notify to admin
        if (!$config['banners_auto_approval']) {
            $reefless->loadClass('Mail');

            $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('banners_admin_banner_edited');
            $m_find = ['{username}', '{link}', '{date}', '{status}'];
            $m_replace = [
                $account_info['Username'],
                '<a href="' . RL_URL_HOME . ADMIN . '/index.php?controller=banners&amp;filter=' . $bannerId . '">' . $lang['banners+name+' . $bannerId] . '</a>',
                date(str_replace(['b', '%'], ['M', ''], RL_DATE_FORMAT)),
                $lang['pending'],
            ];
            $mail_tpl['body'] = str_replace($m_find, $m_replace, $mail_tpl['body']);
            $GLOBALS['rlMail']->send($mail_tpl, $config['notifications_email']);
        }
    }

    /**
     * Assign to Smarty value of max file upload size
     *
     * @since 2.7.0
     */
    public static function assignMaxFileUploadSize()
    {
        $GLOBALS['rlSmarty']->assign('max_file_size', Util::getMaxFileUploadSize() / 1048576);
    }

    /**
     * Banner name's Handler
     *
     * @param int   $bannerId - banner id
     * @param mixed $names    - banner name's (string/array)
     **/
    public function bannerNameHandler($bannerId = false, $names = false)
    {
        global $rlDb, $config;

        $allLangs = $GLOBALS['languages'];

        // write/update name's phrases
        $langKeysInsert = $langKeysUpdate = [];
        foreach ($allLangs as $key => $value) {
            $exists = $rlDb->getOne('Key', "`Key` = 'banners+name+{$bannerId}' AND `Code` = '{$allLangs[$key]['Code']}' AND `Plugin` = 'banners'", 'lang_keys');
            if (empty($exists)) {
                array_push($langKeysInsert, [
                    'Code' => $allLangs[$key]['Code'],
                    'Module' => 'common',
                    'Status' => 'active',
                    'Key' => 'banners+name+' . $bannerId,
                    'Value' => count($allLangs) > 1 ? (!empty($names[$allLangs[$key]['Code']]) ? $names[$allLangs[$key]['Code']] : $names[$config['lang']]) : $names,
                    'Plugin' => 'banners',
                ]);
            } else {
                array_push($langKeysUpdate, [
                    'fields' => [
                        'Status' => 'active',
                        'Value' => count($allLangs) > 1 ? (!empty($names[$allLangs[$key]['Code']]) ? $names[$allLangs[$key]['Code']] : $names[$config['lang']]) : $names,
                    ],
                    'where' => [
                        'Key' => 'banners+name+' . $bannerId,
                        'Code' => $allLangs[$key]['Code'],
                    ],
                ]);
            }
        }

        if (!empty($langKeysInsert)) {
            $rlDb->insert($langKeysInsert, 'lang_keys');
        }

        if (!empty($langKeysUpdate)) {
            $rlDb->update($langKeysUpdate, 'lang_keys');
        }
    }

    /**
     * Edit exists banner (add form)
     *
     * @param int   $bannerId - banner id
     * @param array $planInfo - banner plan info
     * @param array $data     - banner data
     **/
    public function edit($bannerId = false, $planInfo = false, $data = false)
    {
        global $rlDb;

        if (!$bannerId || !$planInfo || !$data) {
            return false;
        }

        $bannerInfo = [];
        if (defined('REALM') && REALM == 'admin') {
            $sql = "SELECT `Account_ID`, `Date_release`, `Date_from`, `Date_to`, `Pay_date` FROM `{db_prefix}banners` WHERE `ID` = '{$bannerId}'";
            $bannerInfo = $rlDb->getRow($sql);
        }

        $sql = "UPDATE `{db_prefix}banners` SET ";
        if (defined('REALM') && REALM == 'admin') {
            $sql .= "`Account_ID` = '" . (int) $data['account_id'] . "', ";
        }

        $sql .= "`Plan_ID` = '" . (int) $planInfo['ID'] . "', ";
        $sql .= "`Pay_date` = " . ($bannerInfo['Date_release'] ? $bannerInfo['Date_release'] : 'UNIX_TIMESTAMP()') . ", ";
        $sql .= "`Date_release` = " . ($bannerInfo['Date_release'] ? $bannerInfo['Date_release'] : 'UNIX_TIMESTAMP()') . ", ";
        $sql .= "`Date_from` = " . ($bannerInfo['Date_from'] ? $bannerInfo['Date_from'] : 'UNIX_TIMESTAMP()') . ", ";

        $date_to = $planInfo['Plan_Type'] == 'period' ? ($planInfo['Period'] == 0 ? 0 : time() + ($planInfo['Period'] * 86400)) : $planInfo['Period'];
        $sql .= "`Date_to` = '" . ($bannerInfo['Date_to'] ? $bannerInfo['Date_to'] : $date_to) . "', ";
        $sql .= "`Box` = '" . $data['banner_box'] . "', ";
        $sql .= "`Type` = '" . $data['banner_type'] . "', ";
        $sql .= "`Link` = '" . ($data['banner_type'] == 'image' ? $data['link'] : '') . "', ";
        $sql .= "`Follow` = '" . (($data['banner_type'] == 'image' && !empty($data['link'])) ? intval($data['nofollow']) : 0) . "', ";
        $sql .= "`Status` = '" . ((defined('REALM') && REALM == 'admin') ? $data['status'] : 'incomplete') . "' ";

        if ($data['banner_type'] == 'html') {
            $this->doCleanHtmlBanner($data['html']);

            $sql .= ",`Html` = '{$data['html']}'";
            $sql .= ",`Responsive` = '" . intval($data['responsive']) . "', `Image` = 'html' ";
        }
        $sql .= "WHERE `ID` = '{$bannerId}'";

        if ($GLOBALS['rlDb']->query($sql)) {
            $this->bannerNameHandler($bannerId, $data['name']);
        }
    }

    /**
     * Upgrade banner
     *
     * @since 2.7.0 - Remove all params except $bannerId
     *
     * @param int $bannerId
     */
    public function upgradeBanner($bannerId)
    {
        global $rlDb, $lang, $config;

        $sql = "SELECT `T1`.`ID`, `T1`.`Plan_ID`, `T1`.`Account_ID`, `T1`.`Date_to`, `T1`.`Status` ";
        $sql .= ",`T2`.`Plan_Type`, `T2`.`Period`, `T2`.`Price` ";
        $sql .= "FROM `{db_prefix}banners` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}banner_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T1`.`ID` = {$bannerId}";
        $bannerInfo = $rlDb->getRow($sql);

        $now = time();
        $dateTo = ($bannerInfo['Period'] != 0
            ? ($bannerInfo['Plan_Type'] == 'period'
                ? $now + ($bannerInfo['Period'] * 86400)
                : $bannerInfo['Date_to'] + $bannerInfo['Period'])
            : 0
        );

        $update = [
            'fields' => [
                'Last_step' => '',
                'Pay_date' => $now,
                'Date_to' => $dateTo,
                'Status' => $config['banners_auto_approval'] ? 'active' : 'pending',
            ],
            'where' => [
                'ID' => $bannerId,
            ],
        ];

        /**
         * @since 2.7.0 - $bannerInfo, $update added
         */
        $GLOBALS['rlHook']->load('bannersUpgradeBanner', $bannerInfo, $update);

        $rlDb->updateOne($update, 'banners');

        if (!isset($_GET['item']) || $_GET['item'] != 'activateBWTDetails') {
            $GLOBALS['reefless']->loadClass('Notice');
            $GLOBALS['rlNotice']->saveNotice($lang['banners_noticeBannerUpgraded']);
        }
    }

    /**
     * Prepare deleting banner Plan or Box
     *
     * @param string|int $id - If int ID of plan, box Key if string
     *
     * @return array
     */
    public function ajaxPrepareDeleting($id)
    {
        global  $rlDb, $rlSmarty, $lang;

        $response = [
            'status' => 'ERROR',
            'message' => $lang['error'],
        ];

        if (!$id) {
            return $response;
        }

        $planMode = is_numeric($id);
        $field = $planMode ? '`T2`.`ID`' : '`T1`.`Box`';
        $sField = $planMode ? '`T2`.`Key`,' : '';

        $deleteDetails = [];
        $deleteTotalItems = 0;

        // check banners
        $sql = "SELECT {$sField} COUNT(`T1`.`ID`) AS `count` FROM `{db_prefix}banners` AS `T1` ";

        if ($planMode) {
            $sql .= "LEFT JOIN `{db_prefix}banner_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
        }

        $sql .= "WHERE {$field} = '{$id}' AND `T1`.`Status` <> 'trash'";
        $banners = $rlDb->getRow($sql);

        $deleteDetails[] = [
            'name' => $lang['banners_banner'],
            'items' => (int) $banners['count'],
            'link' => RL_URL_HOME . ADMIN . '/index.php?controller=banners&' . ($planMode ? "plan={$id}" : "box={$id}"),
        ];
        $deleteTotalItems += $banners['count'];

        $rlSmarty->assign('deleteDetails', $deleteDetails);
        $rlSmarty->assign('planInfo', [
            'id' => $id,
            'key' => $banners['Key'],
            'name' => $planMode ? $lang['banner_plans+name+' . $banners['Key']] : $lang['blocks+name+' . $id],
            'planMode' => $planMode,
        ]);

        if ($deleteTotalItems) {
            $rlSmarty->assign('lang', $lang);
            $tpl = RL_PLUGINS . 'banners/delete_preparing_banner_plan.tpl';
            $response['html'] = $rlSmarty->fetch($tpl);
        } else {
            $response['func'] = $planMode ? 'deletePlan' : 'deleteBox';
        }

        $response['status'] = 'OK';
        unset($response['message']);

        return $response;
    }

    /**
     * Delete banner plan
     *
     * @param int $id
     *
     * @return array
     */
    public function ajaxDeletePlan($id)
    {
        global $rlDb, $lang;

        $response = [
            'status' => 'ERROR',
            'message' => $lang['error'],
        ];

        if (!$id = (int) $id) {
            return $response;
        }

        $rlDb->query("DELETE FROM `{db_prefix}banner_plans` WHERE `ID` = {$id} LIMIT 1");
        $banners = $rlDb->getAll("SELECT `ID`, `Date_release` FROM `{db_prefix}banners` WHERE `Plan_ID` = {$id}");

        foreach ($banners as $banner) {
            $this->deleteBanner($banner['ID'], $banner['Date_release']);
        }

        $this->ubdateAbilities();

        $response = [
            'status' => 'OK',
            'message' => $lang['item_deleted'],
        ];

        return $response;
    }

    /**
     * Delete banner
     *
     * @param int $id   - banner ID
     * @param int $date - date release of the banner
     */
    public function deleteBanner($id, $date)
    {
        global $rlDb, $reefless;

        if (!$id = (int) $id) {
            return;
        }

        $rlDb->query("DELETE FROM `{db_prefix}banners` WHERE `ID` = {$id} LIMIT 1");
        $rlDb->query("DELETE FROM `{db_prefix}banners_click` WHERE `Banner_ID` = {$id}");
        $rlDb->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = 'banners+name+{$id}' AND `Plugin` = 'banners'");

        $reefless->deleteDirectory(RL_FILES . 'banners/' . date('m-Y', $date) . "/b{$id}/");
    }

    /**
     * ubdateAbilities
     */
    public function ubdateAbilities()
    {
        global $rlConfig, $rlDb, $reefless;

        $sql = "SELECT `Allow_for` FROM `{db_prefix}banner_plans` ";
        $sql .= "WHERE `Status` = 'active' AND `Allow_for` <> '' AND `Admin` = '0'";
        $limited_plans = $rlDb->getAll($sql);

        $abilities = [];
        if (!empty($limited_plans)) {
            foreach ($limited_plans as $plan) {
                $abilities = array_merge($abilities, explode(',', $plan['Allow_for']));
            }
            $abilities = array_unique($abilities);
        }
        $rlConfig->setConfig('banners_allow_add_banner_types', implode(',', $abilities));
    }

    /**
     * Delete banner thought Ajax.
     * Method used in Frontend and Backend
     *
     * @param int $id
     *
     * @return array
     */
    public function ajaxDeleteBanner($id)
    {
        global $rlDb, $reefless, $lang, $config, $account_info;

        $response = [
            'status' => 'ERROR',
            'message' => $lang['error'],
        ];

        if (!$id = (int) $id) {
            return $response;
        }

        $banner = $rlDb->getRow("
            SELECT `Account_ID`, `Date_release` FROM `{db_prefix}banners`
            WHERE `ID` = {$id} LIMIT 1
        ");

        $accountId = (int) $account_info['ID'];

        if (!defined('REALM') && $banner['Account_ID'] != $accountId) {
            return $response;
        }

        $this->deleteBanner($id, $banner['Date_release']);

        if (defined('REALM')) {
            return [
                'status' => 'OK',
                'message' => $lang['banners_noticeBannerDeleted'],
            ];
        }

        $bannersCount = $response['count'] = (int) $rlDb->getRow("
            SELECT COUNT(`ID`) AS `count` FROM `{db_prefix}banners`
            WHERE `Account_ID` = '{$accountId}' AND `Status` <> 'trash'
        ", 'count');

        if (0 === $bannersCount) {
            $href = $reefless->getPageUrl('add_banner');
            $replace = preg_replace('/(\[(.+)\])/', '<a href="' . $href . '">$2</a>', $lang['banners_noBannersHere']);
            $response['html'] = '<div class="info">' . $replace . '</div>';
        }

        $bannersPerPage = (int) $config['listings_per_page'];
        $currentPage = (int) $_REQUEST['page'];
        $pageIndex = max(0, $currentPage - 1);

        if ($currentPage > 1 && ($bannersCount <= $bannersPerPage * $pageIndex)) {
            $url = $reefless->getPageUrl('my_banners');
            $response['redirect'] = $url;
        }

        $response['status'] = 'OK';
        $response['message'] = $lang['banners_noticeBannerDeleted'];

        return $response;
    }

    /**
     * Delete banner box by key
     *
     * @param string $key
     *
     * @return array
     */
    public function ajaxDeleteBannerBox($key)
    {
        global $rlDb, $lang;

        $GLOBALS['rlValid']->sql($key);

        $rlDb->query("
            DELETE FROM `{db_prefix}blocks`
            WHERE `Key` = '{$key}' AND `Plugin` = 'banners' LIMIT 1
        ");

        $rlDb->query("
            DELETE FROM `{db_prefix}hooks`
            WHERE `Name` = 'tplBetweenCategories' AND `Plugin` LIKE 'banners\_{$key}'
        ");

        $plans = $rlDb->getAll("
            SELECT `ID`, `Boxes` FROM `{db_prefix}banner_plans`
            WHERE FIND_IN_SET('{$key}', `Boxes`) > 0;
        ");

        if (!empty($plans)) {
            $setBox = '';
            $ids = [];

            foreach ($plans as $entry) {
                $boxes = explode(',', $entry['Boxes']);
                $index = array_search($key, $boxes);
                unset($boxes[$index]);

                $setBox .= "WHEN {$entry['ID']} THEN '" . implode(',', $boxes) . "' ";
                $ids[] = $entry['ID'];
            }

            $GLOBALS['rlDb']->query("
                UPDATE `{db_prefix}banner_plans`
                    SET `Boxes` = CASE `ID`
                        {$setBox}
                    END
                WHERE `ID` IN ('" . implode("','", $ids) . "')
            ");

            $updateData = array(
                'fields' => array(
                    'Status' => 'approval',
                ),
                'where' => array(
                    'Boxes' => ''
                )
            );
            $rlDb->updateOne($updateData, 'banner_plans');
        }

        $banners = $rlDb->getAll("SELECT `ID`, `Date_release` FROM `{db_prefix}banners` WHERE `Box` = '{$key}'");

        foreach ($banners as $banner) {
            $this->deleteBanner($banner['ID'], $banner['Date_release']);
        }

        $response = [
            'status' => 'OK',
            'message' => $lang['item_deleted'],
        ];

        return $response;
    }

    /**
     * Mass actions for Banner manager
     *
     * @param string $ids
     * @param string $action
     *
     * @return array
     */
    public function ajaxBannersMassActions($ids, $action = 'activate')
    {
        global $rlDb, $lang;

        $response = [
            'status' => 'ERROR',
            'message' => $lang['error'],
        ];

        if (!$ids || !$action) {
            return $response;
        }

        $ids = explode('|', $ids);

        switch ($action) {
            case 'activate':
            case 'approve':
                $status = ($action === 'activate') ? 'active' : 'approval';
                $rlDb->query($sql = "
                    UPDATE `{db_prefix}banners` SET `Status` = '{$status}'
                    WHERE `ID` IN ('" . implode("','", $ids) . "')
                ");
                break;

            case 'delete':
                foreach ($ids as $key => $bannerId) {
                    $date = (int) $rlDb->getOne('Date_release', "`ID` = {$bannerId}", 'banners');
                    $this->deleteBanner($bannerId, $date);
                }
                break;
        }

        $response = [
            'status' => 'OK',
            'message' => $lang['banners_noticeBannerMassAction_' . $action],
        ];

        return $response;
    }

    /**
     * is_animated_gif
     **/
    public function isAnimatedGif($filename = false)
    {
        $raw = file_get_contents($filename);

        $offset = 0;
        $frames = 0;
        while ($frames < 2) {
            $where1 = strpos($raw, "\x00\x21\xF9\x04", $offset);
            if ($where1 === false) {
                break;
            } else {
                $offset = $where1 + 1;
                $where2 = strpos($raw, "\x00\x2C", $offset);
                if ($where2 === false) {
                    break;
                } else {
                    if ($where1 + 8 == $where2) {
                        $frames++;
                    }
                    $offset = $where2 + 1;
                }
            }
        }

        return $frames > 1;
    }

    /**
     * makeBannerFolder
     **/
    public function makeBannerFolder($bannerId = false, $options = false)
    {
        global $rlDb, $reefless;
        $dir = false;
        $curPhoto = $rlDb->getOne('Image', "`ID` = '{$bannerId}'", 'banners');

        if ($curPhoto) {
            $expDir = explode('/', $curPhoto);
            if (count($expDir) > 1) {
                array_pop($expDir);
                $dir = RL_FILES . $options['banners_dir'] . RL_DS . implode(RL_DS, $expDir) . RL_DS;
                $dirName = implode('/', $expDir) . '/';
            }
        }

        if (!$dir) {
            $dir = RL_FILES . $options['banners_dir'] . RL_DS . date('m-Y') . RL_DS . 'b' . $bannerId . RL_DS;
            $dirName = date('m-Y') . '/b' . $bannerId . '/';
        }

        $url = $options['upload_url'] . $dirName;
        $reefless->rlMkdir($dir);

        return [
            'dir' => $dir,
            'url' => $url,
            'dirName' => $dirName,
        ];
    }

    /**
     * Get countries list
     */
    public function getCountriesList()
    {
        $countries = '[
            {"Country_code":"AF","Country_name":"Afghanistan"},{"Country_code":"AX","Country_name":"Aland Islands"},{"Country_code":"AL","Country_name":"Albania"},
            {"Country_code":"DZ","Country_name":"Algeria"},{"Country_code":"AS","Country_name":"American Samoa"},{"Country_code":"AD","Country_name":"Andorra"},
            {"Country_code":"AO","Country_name":"Angola"},{"Country_code":"AI","Country_name":"Anguilla"},{"Country_code":"AQ","Country_name":"Antarctica"},
            {"Country_code":"AG","Country_name":"Antigua and Barbuda"},{"Country_code":"AR","Country_name":"Argentina"},{"Country_code":"AM","Country_name":"Armenia"},
            {"Country_code":"AW","Country_name":"Aruba"},{"Country_code":"AU","Country_name":"Australia"},{"Country_code":"AT","Country_name":"Austria"},
            {"Country_code":"AZ","Country_name":"Azerbaijan"},{"Country_code":"BS","Country_name":"Bahamas"},{"Country_code":"BH","Country_name":"Bahrain"},
            {"Country_code":"BD","Country_name":"Bangladesh"},{"Country_code":"BB","Country_name":"Barbados"},{"Country_code":"BY","Country_name":"Belarus"},
            {"Country_code":"BE","Country_name":"Belgium"},{"Country_code":"BZ","Country_name":"Belize"},{"Country_code":"BJ","Country_name":"Benin"},
            {"Country_code":"BM","Country_name":"Bermuda"},{"Country_code":"BT","Country_name":"Bhutan"},{"Country_code":"BO","Country_name":"Bolivia"},
            {"Country_code":"BA","Country_name":"Bosnia and Herzegovina"},{"Country_code":"BW","Country_name":"Botswana"},
            {"Country_code":"BV","Country_name":"Bouvet Island"},{"Country_code":"BR","Country_name":"Brazil"},{"Country_code":"IO","Country_name":"British Indian Ocean Territory"},
            {"Country_code":"BN","Country_name":"Brunei Darussalam"},{"Country_code":"BG","Country_name":"Bulgaria"},{"Country_code":"BF","Country_name":"Burkina Faso"},
            {"Country_code":"BI","Country_name":"Burundi"},{"Country_code":"KH","Country_name":"Cambodia"},{"Country_code":"CM","Country_name":"Cameroon"},
            {"Country_code":"CA","Country_name":"Canada"},{"Country_code":"CV","Country_name":"Cape Verde"},{"Country_code":"KY","Country_name":"Cayman Islands"},
            {"Country_code":"CF","Country_name":"Central African Republic"},{"Country_code":"TD","Country_name":"Chad"},{"Country_code":"CL","Country_name":"Chile"},
            {"Country_code":"CN","Country_name":"China"},{"Country_code":"CX","Country_name":"Christmas Island"},{"Country_code":"CC","Country_name":"Cocos (Keeling) Islands"},
            {"Country_code":"CO","Country_name":"Colombia"},{"Country_code":"KM","Country_name":"Comoros"},{"Country_code":"CG","Country_name":"Congo"},
            {"Country_code":"CD","Country_name":"Congo, The Democratic Republic of the"},{"Country_code":"CK","Country_name":"Cook Islands"},
            {"Country_code":"CR","Country_name":"Costa Rica"},{"Country_code":"CI","Country_name":"Cote D\'Ivoire"},{"Country_code":"HR","Country_name":"Croatia"},
            {"Country_code":"CU","Country_name":"Cuba"},{"Country_code":"CY","Country_name":"Cyprus"},{"Country_code":"CZ","Country_name":"Czech Republic"},
            {"Country_code":"DK","Country_name":"Denmark"},{"Country_code":"DJ","Country_name":"Djibouti"},{"Country_code":"DM","Country_name":"Dominica"},
            {"Country_code":"DO","Country_name":"Dominican Republic"},{"Country_code":"TL","Country_name":"East Timor"},{"Country_code":"EC","Country_name":"Ecuador"},
            {"Country_code":"EG","Country_name":"Egypt"},{"Country_code":"SV","Country_name":"El Salvador"},{"Country_code":"GQ","Country_name":"Equatorial Guinea"},
            {"Country_code":"ER","Country_name":"Eritrea"},{"Country_code":"EE","Country_name":"Estonia"},{"Country_code":"ET","Country_name":"Ethiopia"},
            {"Country_code":"FK","Country_name":"Falkland Islands (Malvinas)"},{"Country_code":"FO","Country_name":"Faroe Islands"},{"Country_code":"FJ","Country_name":"Fiji"},
            {"Country_code":"FI","Country_name":"Finland"},{"Country_code":"FR","Country_name":"France"},{"Country_code":"GF","Country_name":"French Guiana"},
            {"Country_code":"PF","Country_name":"French Polynesia"},{"Country_code":"TF","Country_name":"French Southern Territories"},{"Country_code":"GA","Country_name":"Gabon"},
            {"Country_code":"GM","Country_name":"Gambia"},{"Country_code":"GE","Country_name":"Georgia"},{"Country_code":"DE","Country_name":"Germany"},
            {"Country_code":"GH","Country_name":"Ghana"},{"Country_code":"GI","Country_name":"Gibraltar"},{"Country_code":"GR","Country_name":"Greece"},
            {"Country_code":"GL","Country_name":"Greenland"},{"Country_code":"GD","Country_name":"Grenada"},{"Country_code":"GP","Country_name":"Guadeloupe"},
            {"Country_code":"GU","Country_name":"Guam"},{"Country_code":"GT","Country_name":"Guatemala"},{"Country_code":"GG","Country_name":"Guernsey"},
            {"Country_code":"GN","Country_name":"Guinea"},{"Country_code":"GW","Country_name":"Guinea-Bissau"},{"Country_code":"GY","Country_name":"Guyana"},
            {"Country_code":"HT","Country_name":"Haiti"},{"Country_code":"HM","Country_name":"Heard Island and McDonald Islands"},
            {"Country_code":"VA","Country_name":"Holy See (Vatican City State)"},{"Country_code":"HN","Country_name":"Honduras"},{"Country_code":"HK","Country_name":"Hong Kong"},
            {"Country_code":"HU","Country_name":"Hungary"},{"Country_code":"IS","Country_name":"Iceland"},{"Country_code":"IN","Country_name":"India"},
            {"Country_code":"ID","Country_name":"Indonesia"},{"Country_code":"IR","Country_name":"Iran, Islamic Republic of"},{"Country_code":"IQ","Country_name":"Iraq"},
            {"Country_code":"IE","Country_name":"Ireland"},{"Country_code":"IM","Country_name":"Isle of Man"},{"Country_code":"IL","Country_name":"Israel"},
            {"Country_code":"IT","Country_name":"Italy"},{"Country_code":"JM","Country_name":"Jamaica"},{"Country_code":"JP","Country_name":"Japan"},
            {"Country_code":"JE","Country_name":"Jersey"},{"Country_code":"JO","Country_name":"Jordan"},{"Country_code":"KZ","Country_name":"Kazakhstan"},
            {"Country_code":"KE","Country_name":"Kenya"},{"Country_code":"KI","Country_name":"Kiribati"},{"Country_code":"KP","Country_name":"Korea, Democratic People\'s Republic of"},
            {"Country_code":"KR","Country_name":"Korea, Republic of"},{"Country_code":"KW","Country_name":"Kuwait"},{"Country_code":"KG","Country_name":"Kyrgyzstan"},
            {"Country_code":"LA","Country_name":"Lao People\'s Democratic Republic"},{"Country_code":"LV","Country_name":"Latvia"},{"Country_code":"LB","Country_name":"Lebanon"},
            {"Country_code":"LS","Country_name":"Lesotho"},{"Country_code":"LR","Country_name":"Liberia"},{"Country_code":"LY","Country_name":"Libyan Arab Jamahiriya"},
            {"Country_code":"LI","Country_name":"Liechtenstein"},{"Country_code":"LT","Country_name":"Lithuania"},{"Country_code":"LU","Country_name":"Luxembourg"},
            {"Country_code":"MO","Country_name":"Macau"},{"Country_code":"MK","Country_name":"Macedonia"},{"Country_code":"MG","Country_name":"Madagascar"},
            {"Country_code":"MW","Country_name":"Malawi"},{"Country_code":"MY","Country_name":"Malaysia"},{"Country_code":"MV","Country_name":"Maldives"},
            {"Country_code":"ML","Country_name":"Mali"},{"Country_code":"MT","Country_name":"Malta"},{"Country_code":"MH","Country_name":"Marshall Islands"},
            {"Country_code":"MQ","Country_name":"Martinique"},{"Country_code":"MR","Country_name":"Mauritania"},{"Country_code":"MU","Country_name":"Mauritius"},
            {"Country_code":"YT","Country_name":"Mayotte"},{"Country_code":"MX","Country_name":"Mexico"},{"Country_code":"FM","Country_name":"Micronesia, Federated States of"},
            {"Country_code":"MD","Country_name":"Moldova, Republic of"},{"Country_code":"MC","Country_name":"Monaco"},{"Country_code":"MN","Country_name":"Mongolia"},
            {"Country_code":"ME","Country_name":"Montenegro"},{"Country_code":"MS","Country_name":"Montserrat"},{"Country_code":"MA","Country_name":"Morocco"},
            {"Country_code":"MZ","Country_name":"Mozambique"},{"Country_code":"MM","Country_name":"Myanmar"},{"Country_code":"NA","Country_name":"Namibia"},
            {"Country_code":"NR","Country_name":"Nauru"},{"Country_code":"NP","Country_name":"Nepal"},{"Country_code":"NL","Country_name":"Netherlands"},
            {"Country_code":"AN","Country_name":"Netherlands Antilles"},{"Country_code":"NC","Country_name":"New Caledonia"},{"Country_code":"NZ","Country_name":"New Zealand"},
            {"Country_code":"NI","Country_name":"Nicaragua"},{"Country_code":"NE","Country_name":"Niger"},{"Country_code":"NG","Country_name":"Nigeria"},
            {"Country_code":"NU","Country_name":"Niue"},{"Country_code":"NF","Country_name":"Norfolk Island"},{"Country_code":"MP","Country_name":"Northern Mariana Islands"},
            {"Country_code":"NO","Country_name":"Norway"},{"Country_code":"OM","Country_name":"Oman"},{"Country_code":"PK","Country_name":"Pakistan"},
            {"Country_code":"PW","Country_name":"Palau"},{"Country_code":"PS","Country_name":"Palestinian Territory"},{"Country_code":"PA","Country_name":"Panama"},
            {"Country_code":"PG","Country_name":"Papua New Guinea"},{"Country_code":"PY","Country_name":"Paraguay"},{"Country_code":"PE","Country_name":"Peru"},
            {"Country_code":"PH","Country_name":"Philippines"},{"Country_code":"PN","Country_name":"Pitcairn"},{"Country_code":"PL","Country_name":"Poland"},
            {"Country_code":"PT","Country_name":"Portugal"},{"Country_code":"PR","Country_name":"Puerto Rico"},{"Country_code":"QA","Country_name":"Qatar"},
            {"Country_code":"RE","Country_name":"Reunion"},{"Country_code":"RO","Country_name":"Romania"},{"Country_code":"RU","Country_name":"Russian Federation"},
            {"Country_code":"RW","Country_name":"Rwanda"},{"Country_code":"SH","Country_name":"Saint Helena"},{"Country_code":"KN","Country_name":"Saint Kitts and Nevis"},
            {"Country_code":"LC","Country_name":"Saint Lucia"},{"Country_code":"PM","Country_name":"Saint Pierre and Miquelon"},
            {"Country_code":"VC","Country_name":"Saint Vincent and the Grenadines"},{"Country_code":"WS","Country_name":"Samoa"},{"Country_code":"SM","Country_name":"San Marino"},
            {"Country_code":"ST","Country_name":"Sao Tome and Principe"},{"Country_code":"SA","Country_name":"Saudi Arabia"},{"Country_code":"SN","Country_name":"Senegal"},
            {"Country_code":"RS","Country_name":"Serbia"},{"Country_code":"SC","Country_name":"Seychelles"},{"Country_code":"SL","Country_name":"Sierra Leone"},
            {"Country_code":"SG","Country_name":"Singapore"},{"Country_code":"SK","Country_name":"Slovakia"},{"Country_code":"SI","Country_name":"Slovenia"},
            {"Country_code":"SB","Country_name":"Solomon Islands"},{"Country_code":"SO","Country_name":"Somalia"},{"Country_code":"ZA","Country_name":"South Africa"},
            {"Country_code":"GS","Country_name":"South Georgia and the South Sandwich Islands"},{"Country_code":"ES","Country_name":"Spain"},
            {"Country_code":"LK","Country_name":"Sri Lanka"},{"Country_code":"SD","Country_name":"Sudan"},{"Country_code":"SR","Country_name":"Suriname"},
            {"Country_code":"SJ","Country_name":"Svalbard and Jan Mayen"},{"Country_code":"SZ","Country_name":"Swaziland"},{"Country_code":"SE","Country_name":"Sweden"},
            {"Country_code":"CH","Country_name":"Switzerland"},{"Country_code":"SY","Country_name":"Syrian Arab Republic"},
            {"Country_code":"TW","Country_name":"Taiwan (Province of China)"},{"Country_code":"TJ","Country_name":"Tajikistan"},{"Country_code":"TZ","Country_name":"Tanzania, United Republic of"},
            {"Country_code":"TH","Country_name":"Thailand"},{"Country_code":"TG","Country_name":"Togo"},{"Country_code":"TK","Country_name":"Tokelau"},{"Country_code":"TO","Country_name":"Tonga"},
            {"Country_code":"TT","Country_name":"Trinidad and Tobago"},{"Country_code":"TN","Country_name":"Tunisia"},{"Country_code":"TR","Country_name":"Turkey"},
            {"Country_code":"TM","Country_name":"Turkmenistan"},{"Country_code":"TC","Country_name":"Turks and Caicos Islands"},{"Country_code":"TV","Country_name":"Tuvalu"},
            {"Country_code":"UG","Country_name":"Uganda"},{"Country_code":"UA","Country_name":"Ukraine"},{"Country_code":"AE","Country_name":"United Arab Emirates"},
            {"Country_code":"GB","Country_name":"United Kingdom"},{"Country_code":"US","Country_name":"United States"},{"Country_code":"UM","Country_name":"United States Minor Outlying Islands"},
            {"Country_code":"UY","Country_name":"Uruguay"},{"Country_code":"UZ","Country_name":"Uzbekistan"},{"Country_code":"VU","Country_name":"Vanuatu"},
            {"Country_code":"VE","Country_name":"Venezuela"},{"Country_code":"VN","Country_name":"Vietnam"},{"Country_code":"VG","Country_name":"Virgin Islands, British"},
            {"Country_code":"VI","Country_name":"Virgin Islands, U.S."},{"Country_code":"WF","Country_name":"Wallis and Futuna"},{"Country_code":"EH","Country_name":"Western Sahara"},
            {"Country_code":"YE","Country_name":"Yemen"},{"Country_code":"ZM","Country_name":"Zambia"},{"Country_code":"ZW","Country_name":"Zimbabwe"}
        ]';
        $countries = preg_replace('/(\n|\t|\r)?/', '', $countries);

        return json_decode($countries);
    }

    /**
     * enum/set database fields manager
     *
     * @param string $table - table
     * @param string $field - field
     * @param string $value - new value
     **/
    public function enumAdd($table = false, $field = false, $value = false)
    {
        global $rlDb, $reefless;

        $sql = "SHOW COLUMNS FROM `{db_prefix}{$table}` LIKE '{$field}'";
        $enum_row = $rlDb->getRow($sql);

        preg_match('/([a-z]*)\((.*)\)/', $enum_row[$field], $matches);
        if (isset($matches[2])) {
            $enum_values = explode(',', $matches[2]);

            if (false === array_search("'{$value}'", $enum_values)) {
                $reefless->loadClass('Actions');
                $GLOBALS['rlActions']->enumAdd($table, $field, $value);
            }
        }
    }

    /**
     * Get children locations of parent location
     *
     * @since 2.7.0 - Parameter $parent set as required
     *
     * @param int|string $parent - Parent ID or Key of location
     *
     * @return array
     */
    public function ajaxMfGetChildren($parent)
    {
        global $rlSmarty, $lang;

        $response = [
            'status' => 'ERROR',
            'message' => $lang['error'],
        ];

        if (!$parent) {
            return $response;
        }

        Valid::escape($parent);
        $locations = $this->mfGetLocations($parent);

        if (empty($locations)) {
            return $response;
        }

        $rlSmarty->assign('mf_locations', $locations);

        $tpl = __DIR__ . '/admin/mf_locations.tpl';

        $response = [
            'status' => 'OK',
            'html' => $rlSmarty->fetch($tpl),
        ];

        return $response;
    }

    /**
     * Get children locations by parent location
     *
     * @param  int|string $parent - Parent ID or Key of location
     *
     * @return array
     */
    public function mfGetLocations($parent = null)
    {
        global $rlDb, $reefless, $rlMultiField, $config;

        if (!$parent) {
            $parent = $rlDb->getOne('Key', "`Geo_filter` = '1'", 'multi_formats');
        }
        $reefless->loadClass('MultiField', null, 'multiField');

        if (!$parent) {
            return [];
        }

        $levels = 0;

        if (isset($config['mf_geo_data_format'])) {
            $geo_format_data = json_decode($config['mf_geo_data_format'], true);
            $levels = $geo_format_data['Levels'];
        } else {
            $levels = $rlDb->getOne('Levels', "`Geo_filter` = '1'", 'multi_formats');
        }

        $GLOBALS['rlSmarty']->assign_by_ref('mf_levels', $levels);

        /**
         * @todo - Remove "else" case when compatible will be >= 4.7.1
         */
        if (method_exists($rlMultiField, 'getData')) {
            $locations = $rlMultiField->getData($parent, true, 'alphabetic');
        } else {
            $locations = $rlMultiField->getMDF($parent, 'alphabetic', true);
        }

        if (empty($locations)) {
            return [];
        }

        return $locations;
    }

    /**
     * Collect parent locations and assign to Smarty
     *
     * @param array $locations
     */
    public function mfParentPoints($locations)
    {
        global $rlDb, $rlSmarty;


        if (!is_array($locations) || empty($locations[0])) {
            return;
        }

        if (false === $rlDb->columnExists('Parent_IDs', 'data_formats')) {
            $this->assignLegacyParentPoints($locations);
            return;
        }

        $sql = "SELECT `Parent_IDs` FROM `{db_prefix}data_formats` ";
        $sql .= "WHERE `Key` IN ('" . implode("','", $locations) . "') AND `Status` = 'active'";
        $locations = $rlDb->getAll($sql);

        if (empty($locations)) {
            return;
        }

        $points = [];
        foreach ($locations as $location) {
            if ('' == $location['Parent_IDs']) {
                continue;
            }

            $ids = $rlDb->getAll("
                SELECT `Key` FROM `{db_prefix}data_formats`
                WHERE `ID` IN({$location['Parent_IDs']})
            ", 'Key');
            $points += $ids;
        }
        $points = array_keys($points);

        $rlSmarty->assign('mfParentPoints', $points);
    }

    /**
     * @since 2.7.0
     *
     * @param $locations
     */
    private function assignLegacyParentPoints($locations)
    {
        global $rlDb, $rlSmarty;

        $sql = "SELECT `ID`, `Parent_ID`, `Key` FROM `{db_prefix}data_formats` ";
        $sql .= "WHERE `Key` IN ('" . implode("','", $locations) . "') AND `Status` = 'active'";
        $parents = $rlDb->getAll($sql);

        $points = [];
        if (!empty($parents)) {
            $parentLocation = $rlDb->getOne('Key', "`Geo_filter` = '1'", 'multi_formats');

            foreach ($parents as $key => $parent) {
                if (!$parent['Parent_ID'] || in_array($parent['Key'], $points)) {
                    continue;
                }

                if ($parentLocation != $parent['Key']) {
                    $parents = $this->mfParents($parent['Parent_ID'], $parentLocation);

                    foreach ($parents as $point) {
                        if (!in_array($point, $points)) {
                            $points[] = $point;
                        }
                    }
                }
            }
            unset($parents);
        }

        $rlSmarty->assign('mfParentPoints', $points);
    }

    /**
     * @deprecated 2.7.0
     *
     * @param int    $locationId
     * @param string $parentLocation
     * @param array  $keys
     *
     * @return array
     */
    public function mfParents($locationId, $parentLocation, $keys = [])
    {
        if (!$locationId = (int) $locationId) {
            return [];
        }
        $keys = $keys ?: [];

        $location = $GLOBALS['rlDb']->getRow("
            SELECT `Parent_ID`, `Key` FROM `{db_prefix}data_formats`
            WHERE `ID` = {$locationId} LIMIT 1
        ");

        if (empty($location)) {
            return [];
        }

        if ($parentLocation != $location['Key']) {
            $keys[] = $location['Key'];

            if (!empty($location['Parent_ID'])) {
                return $this->mfParents($location['Parent_ID'], $parentLocation, $keys);
            }
        }

        return array_reverse($keys);
    }

    /**
     * Properly escape HTML from banner
     *
     * @since 2.7.0
     *
     * @param string $html
     */
    private function doCleanHtmlBanner(&$html)
    {
        Valid::revertQuotes($html);
        $html = str_replace(['\r\n', 'cookie'], [PHP_EOL, ''], $html);
        Valid::escape($html, true);
    }

    /**
     * checkAbilities
     */
    public function checkAbilities()
    {
        global $config, $deny_pages, $account_info;

        if (defined('IS_LOGIN') && !empty($config['banners_allow_add_banner_types'])) {
            if (!in_array($account_info['Type'], explode(',', $config['banners_allow_add_banner_types']))) {
                $deny_pages[] = 'add_banner';
                $deny_pages[] = 'my_banners';
            }
        }
    }

    /**
     * Set status expired for banners
     **/
    public function cron()
    {
        global $rlDb, $reefless, $rlMail, $rlAccount, $rlLang;

        $sql = "SELECT `T1`.`ID`, `T1`.`Account_ID` FROM `{db_prefix}banners` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}banner_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T1`.`Cron` = '0' AND `T1`.`Date_to` <> '0' AND `T1`.`Status` = 'active' AND ";
        $sql .= "IF(`T2`.`Plan_Type` = 'views', IF(`T1`.`Shows` >= `T1`.`Date_to`, 1, 0), IF(`T1`.`Date_to` <= UNIX_TIMESTAMP(), 1, 0)) = 1";
        $data = $rlDb->getAll($sql);

        if (empty($data)) {
            $rlDb->query("UPDATE `{db_prefix}banners` SET `Cron` = '0' WHERE `Status` <> 'incomplete'");
        } else {
            $reefless->loadClass('Mail');
            $reefless->loadClass('Account');

            $banner_expired_email = $rlMail->getEmailTemplate('banners_cron_banner_expired');
            $ids = [];

            foreach ($data as $key => $banner) {
                $ids[] = $banner['ID'];

                $link = sprintf(
                    '<a href="%s">%s</a>',
                    $reefless->getPageUrl('banners_renew') . '?id=' . $banner['ID'],
                    $rlLang->getPhrase('banners+name+' . $banner['ID'])
                );
                $account = $rlAccount->getProfile((int) $banner['Account_ID']);

                $copy_banner_expired_email = $banner_expired_email;
                $copy_banner_expired_email['body'] = strtr($copy_banner_expired_email['body'], [
                    '{name}'   => $account['Full_name'],
                    '{banner}' => $link,
                ]);

                $rlMail->send($copy_banner_expired_email, $account['Mail']);
            }

            if (!empty($ids)) {
                $sql = "UPDATE `{db_prefix}banners` SET `Status` = 'expired', `Pay_date` = '', `Cron` = '1' ";
                $sql .= "WHERE `ID` IN ('" . implode("','", $ids) . "')";
                $rlDb->query($sql);
            }
        }
    }

    /**
     * Install process
     *
     * @since 2.8.0
     */
    public function install()
    {
        global $reefless, $rlDb;

        $rlDb->createTable(
            'banner_plans',
            "`ID` int(11) NOT NULL AUTO_INCREMENT,
                `Key` varchar(255) NOT NULL,
                `Position` int(5) NOT NULL DEFAULT '0',
                `Admin` enum('0','1') NOT NULL DEFAULT '0',
                `Allow_for` mediumtext NOT NULL,
                `Geo` enum('0','1','2','3') NOT NULL DEFAULT '0',
                `Country` mediumtext NOT NULL,
                `Regions` mediumtext NOT NULL,
                `Boxes` mediumtext NOT NULL,
                `Types` mediumtext NOT NULL,
                `Color` varchar(7) NOT NULL,
                `Price` varchar(10) NOT NULL DEFAULT '0',
                `Plan_Type` enum('period','views') NOT NULL DEFAULT 'period',
                `Period` int(11) NOT NULL DEFAULT '0',
                `Status` enum('active','approval','trash') NOT NULL DEFAULT 'active',
                PRIMARY KEY (`ID`),
                KEY `Key` (`Key`)"
        );

        $rlDb->createTable(
            'banners',
            "`ID` int(11) NOT NULL AUTO_INCREMENT,
                `Plan_ID` int(3) DEFAULT '0',
                `Account_ID` int(11) DEFAULT '0',
                `Box` varchar(255) NOT NULL,
                `Date_release` int(10) NOT NULL DEFAULT '0',
                `Date_from` int(10) NOT NULL DEFAULT '0',
                `Date_to` int(10) NOT NULL DEFAULT '0',
                `Pay_date` int(10) NOT NULL,
                `Shows` int(11) NOT NULL DEFAULT '0',
                `Last_show` int(10) NOT NULL DEFAULT '0',
                `Link` varchar(255) NOT NULL,
                `Type` enum('image','html') NOT NULL DEFAULT 'image',
                `Follow` enum('0','1') NOT NULL DEFAULT '0',
                `Html` mediumtext NOT NULL,
                `Responsive` enum('0','1') NOT NULL DEFAULT '0',
                `Image` varchar(100) NOT NULL,
                `Last_step` varchar(15) NOT NULL,
                `Status` enum('active','approval','pending','incomplete','expired') NOT NULL DEFAULT 'approval',
                `Cron` enum('0','1') NOT NULL DEFAULT '0',
                `Cron_notified` enum('0','1') NOT NULL DEFAULT '0',
                PRIMARY KEY (`ID`),
                KEY `Status` (`Status`),
                KEY `Account_ID` (`Account_ID`),
                KEY `Plan_ID` (`Plan_ID`)"
        );

        $rlDb->createTable(
            'banners_click',
            "`ID` int(11) NOT NULL AUTO_INCREMENT,
                `Banner_ID` int(11) NOT NULL,
                `Hash` varchar(32) NOT NULL,
                `Country` varchar(3) NOT NULL,
                PRIMARY KEY (`ID`),
                KEY `Hash` (`Hash`)"
        );
        $rlDb->addColumnToTable(
            'Banners',
            "MEDIUMTEXT NOT NULL",
            'blocks'
        );
        $rlDb->query("UPDATE `" . RL_DBPREFIX . "lang_keys` SET `Module` = 'common' WHERE `Key` IN ('left','right','top','bottom','middle_right','middle_left','long_top')");
        $rlDb->query("INSERT INTO `" . RL_DBPREFIX . "config` (`Key`,`Group_ID`,`Default`,`Plugin`) VALUES
        ('banners_configs_group_id', 0, '0', 'banners'),
        ('banners_allow_add_banner_types', 0, '', 'banners')");

        // create folder for banners
        $reefless->rlMkdir(RL_FILES . 'banners');
    }

    /**
     * Update to 2.1.0 version
     */
    public function update210()
    {
        global $rlDb;

        $rlDb->addColumnToTable(
            'Plan_Type',
            "ENUM( 'period', 'views' ) NOT NULL DEFAULT 'period' AFTER `Price`",
            'banner_plans'
        );

        $rlDb->addColumnsToTable(
            array(
                'Cron' => "ENUM( '0', '1' ) NOT NULL DEFAULT '0'",
                'Html' => "TINYTEXT NOT NULL",
                'Cron_notified' => "ENUM( '0', '1' ) NOT NULL DEFAULT '0'",
            ),
            'banners'
        );
    }

    /**
     * Update to 2.1.2 version
     */
    public function update212()
    {
        $GLOBALS['rlDb']->query("UPDATE `" . RL_DBPREFIX . "banners` SET `Date_release` = `Date_from` WHERE `Date_release` = '0'");
        $GLOBALS['rlDb']->query("UPDATE `" . RL_DBPREFIX . "banners` SET `Pay_date` = `Date_from` WHERE `Pay_date` = '0'");
    }

    /**
     * Update to 2.2.2 version
     */
    public function update222()
    {
        $this->enumAdd('banners', 'Type', 'html');
    }

    /**
     * Update to 2.3.0 version
     */
    public function update230()
    {
        global $rlDb;

        $this->enumAdd('banner_plans', 'Geo', '2');

        // add indexes
        $rlDb->query("ALTER TABLE `" . RL_DBPREFIX . "banners` ADD INDEX ( `Account_ID` )");
        $rlDb->query("ALTER TABLE `" . RL_DBPREFIX . "banners` ADD INDEX ( `Plan_ID` )");
        $rlDb->query("ALTER TABLE `" . RL_DBPREFIX . "banners_click` ADD INDEX ( `Hash` )");

        // remove deprecated hook
        $rlDb->query("DELETE FROM `" . RL_DBPREFIX . "hooks` WHERE `Name` = 'phpGetGEOData' AND `Plugin` = 'banners'");
    }

    /**
     * Update to 2.3.2 version
     */
    public function update232()
    {
        global $rlDb;

        $rlDb->addColumnToTable(
            'Follow',
            "ENUM('0','1') NOT NULL DEFAULT '0' AFTER `Type`",
            'banners'
        );
    }

    /**
     * Update to 2.4.0  version
     */
    public function update240()
    {
        global $rlDb;

        $GLOBALS['rlDb']->query("INSERT INTO `" . RL_DBPREFIX . "config` (`Key`,`Group_ID`,`Default`,`Plugin`) VALUES ('banners_configs_group_id',0,'0','banners')");
        $rlDb->addColumnToTable(
            'Regions',
            "MEDIUMTEXT NOT NULL AFTER `Country`",
            'banner_plans'
        );

        $GLOBALS['rlDb']->query("ALTER TABLE `" . RL_DBPREFIX . "banner_plans` CHANGE `Geo` `Geo` ENUM('0','1','2','3') NOT NULL DEFAULT '0'");
    }

    /**
     * Update to 2.5.0  version
     */
    public function update250()
    {
        $GLOBALS['rlDb']->query("INSERT INTO `" . RL_DBPREFIX . "config` (`Key`,`Group_ID`,`Default`,`Plugin`) VALUES ('banners_allow_add_banner_types',0,'','banners')");
    }

    /**
     * Update to 2.6.0 version
     */
    public function update260()
    {
        global $rlDb;

        $rlDb->addColumnToTable(
            'Responsive',
            "ENUM('0','1') NOT NULL DEFAULT '0' AFTER `Html`",
            'banners'
        );
    }

    /**
     * Update to 2.6.1 version
     */
    public function update261()
    {
        $GLOBALS['rlDb']->query("
                UPDATE `{db_prefix}lang_keys`
                SET `Value` = REPLACE(`Value`, '{username}', '{name}')
                WHERE `Key` = 'email_templates+body+banners_banner_activated'
                OR `Key` = 'email_templates+body+banners_banner_deactivated'
                OR `Key` = 'email_templates+body+banners_cron_banner_expired'
                OR `Key` = 'email_templates+body+banners_payment_accepted'
            ");
    }

    /**
     * Update to 2.7.0 version
     */
    public function update270()
    {
        global $rlDb;

        $pluginDir = RL_PLUGINS . 'banners';
        unlink($pluginDir . '/add_banner_responsive_42.tpl');
        unlink($pluginDir . '/banner_plan_responsive_42.tpl');
        unlink($pluginDir . '/banner_responsive_42.tpl');
        unlink($pluginDir . '/header.tpl');
        unlink($pluginDir . '/my_banners_responsive_42.tpl');

        $rlDb->query("
            DELETE FROM `{db_prefix}hooks`
            WHERE `Plugin` = 'banners' AND `Name` = 'tplHeader'
        ");

        $rlDb->query("
            DELETE FROM `{db_prefix}email_templates`
            WHERE `Plugin` = 'banners' AND `Key` IN(
                'banners_payment_accepted',
                'banners_admin_banner_paid'
            )
        ");

        $rlDb->query("
            DELETE FROM `{db_prefix}lang_keys`
            WHERE `Plugin` = 'banners' AND `Key` IN(
                'banners_bannerType_flash',
                'banners_errorSelectFlashFile',
                'banners_errorFormatFlashFile',
                'banners_remove_images_notice',
                'banners_flash_file'
            )
        ");
    }

    /**
     * Update to 2.8.0 version
     */
    public function update280()
    {
        $pluginDir = RL_PLUGINS . 'banners';
        unlink($pluginDir . '/static/jquery.fileupload.js');
        unlink($pluginDir . '/static/jquery.fileupload-ui.js');
        unlink($pluginDir . '/static/jquery.iframe-transport.js');
        unlink($pluginDir . '/static/tmpl.min.js');
    }
    /**
     * Uninstall the plugin
     **/
    public function uninstall()
    {
        global $rlDb, $reefless;

        $rlDb->dropColumnFromTable('Banners', 'blocks');
        $rlDb->dropTables(['banners_click', 'banner_plans', 'banners']);

        $updateData = array(
            'fields' => array(
                'Item_ID' => 0,
                'Plan_ID' => 0,
            ),
            'where' => array(
                'Service' => 'banners'
            )
        );
        $rlDb->updateOne($updateData, 'transactions');

        // remove fake boxes
        $rlDb->query("DELETE FROM `{db_prefix}hooks` WHERE `Name` = 'tplBetweenCategories' AND `Plugin` LIKE 'banners_%'");

        // delete all banners
        $reefless->deleteDirectory(RL_FILES . 'banners' . RL_DS);
    }

    /**
     * @hook  apExtBlocksUpdate
     * @since 2.8.0
     */
    public function hookApExtBlocksUpdate()
    {
        global $rlDb, $field, $value, $id;

        if ($_GET['controller'] == 'banners' && $field == 'Status') {
            $key = $rlDb->getOne('Key', "`ID` = '{$id}'", 'blocks');
            $sql = "UPDATE `" . RL_DBPREFIX . "hooks` SET `Status` = '{$value}' WHERE `Name` = 'tplBetweenCategories' AND `Plugin` = 'banners_{$key}' LIMIT 1";
            $rlDb->query($sql);
        }
    }

    /**
     * @hook  apExtBlocksSql
     * @since 2.8.0
     */
    public function hookApExtBlocksSql()
    {
        global $sql;

        $operand = '<>';

        if ($_GET['controller'] == 'banners') {
            // since Flynax v4.4
            if (!is_numeric(strpos($sql, 'T1`.*'))) {
                $sql = str_replace('DISTINCT', 'DISTINCT `T1`.`Banners`,', $sql);
            }
            $operand = '=';
        }
        $sql = str_replace('LIMIT', "AND `T1`.`Plugin` {$operand} 'banners' LIMIT", $sql);
    }

    /**
     * @hook  apExtBlocksData
     * @since 2.8.0
     */
    public function hookApExtBlocksData()
    {
        global $data;

        if ($_GET['controller'] == 'banners') {
            foreach ($data as $key => $box) {
                $boxInfo = unserialize($data[$key]['Banners']);
                $data[$key]['banners_size'] = "{$boxInfo['width']} x {$boxInfo['height']}";
                $data[$key]['banners_limit'] = $boxInfo['limit'];
            }
        }
    }

    /**
     * @hook  apExtTransactionsSql
     * @since 2.8.0
     */
    public function hookApExtTransactionsSql()
    {
        global $sql;

        $replace[0] = ", `BT1`.`Key` AS `Banner_plan` FROM ";
        $replace[1] = "LEFT JOIN `" . RL_DBPREFIX . "banner_plans` AS `BT1` ON `T1`.`Plan_ID` = `BT1`.`ID` WHERE ";
        $sql = str_replace(array('FROM', 'WHERE'), $replace, $sql);
    }

    /**
     * @hook  apExtTransactionsData
     * @since 2.8.0
     */
    public function hookApExtTransactionsData()
    {
        global $data, $lang;

        foreach ($data as $key => $transaction) {
            if ($data[$key]['Service'] == 'banners') {
                $planName = $data[$key]['Banner_plan']
                    ? $lang["banner_plans+name+{$data[$key]['Banner_plan']}"] : $lang['banners_transactionsPlanRemoved'];
                $item = $data[$key]['Item_ID'] ? "{$lang['banners_planType']} (#{$data[$key]['Item_ID']})" : $lang['item_not_available'];
                $data[$key]['Item'] = "{$item}|{$planName}";
            }
        }
    }

    /**
     * @hook  phpPaymentHistoryLoop
     * @since 2.8.0
     */
    public function hookPhpPaymentHistoryLoop(&$transaction = [])
    {
        global $rlDb, $lang;

        if ($transaction['Service'] == 'banners') {
            $sql  = "SELECT `T1`.`ID`, `T2`.`Key` FROM `" . RL_DBPREFIX . "banners` AS `T1` ";
            $sql .= "LEFT JOIN `" . RL_DBPREFIX . "banner_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
            $sql .= "WHERE `T1`.`ID` = '{$transaction['Item_ID']}' LIMIT 1";
            $bannerInfo = $rlDb->getRow($sql);

            $planName = $lang["banner_plans+name+{$bannerInfo['Key']}"];
            $transaction['plan_info'] = $planName ? "{$planName} ({$lang['banners_planType']})" : false;
            $transaction['item_info'] = $lang["banners+name+{$bannerInfo['ID']}"];
        }
    }

    /**
     * @hook  pageinfoArea
     * @since 2.8.0
     */
    public function hookPageinfoArea()
    {
        $this->checkAbilities();
    }

    /**
     * @hook  specialBlock
     * @since 2.8.0
     */
    public function hookSpecialBlock()
    {
        $this->prepareBannersList();
    }

    /**
     * @hook  cronAdditional
     * @since 2.8.0
     */
    public function hookCronAdditional()
    {
        $this->cron();
    }

    /**
     * @hook  listingDetailsTop
     * @since 2.8.0
     */
    public function hookListingDetailsTop()
    {
        global $listing_data;

        $this->getBannersByListingLocation($listing_data);
    }

    /**
     * @hook  staticDataRegister
     * @since 2.7.0
     */
    public function hookStaticDataRegister()
    {
        global $rlStatic;

        $banner_pages = ['add_banner', 'edit_banner', 'renew'];
        $template_path = RL_ROOT . 'templates/' . $GLOBALS['config']['template'] . '/';
        $template_core_path = str_replace($GLOBALS['config']['template'], 'template_core', $template_path);

        if (in_array($GLOBALS['page_info']['Controller'], $banner_pages)) {
            // file added in Flynax 4.6.2
            if (is_file($template_path . 'components/plans-chart/plans-chart.css') || is_file($template_core_path . 'components/plans-chart/plans-chart.css')) {
                $rlStatic->addFooterCSS(RL_TPL_BASE . 'components/plans-chart/plans-chart' . (RL_LANG_DIR == 'rtl' ? '-rtl' : '') . '.css', $banner_pages);
            }
        }
    }

    /**
     * @hook ajaxRequest
     *
     * @since 2.7.0
     *
     * @param array  $out
     * @param string $mode
     * @param string $item
     */
    public function hookAjaxRequest(&$out, $mode, $item)
    {
        switch ($mode) {
            case 'bannersBannerClick':
                $this->ajaxBannerClick($item);
                break;

            case 'bannersDeleteBanner':
                $out = $this->ajaxDeleteBanner($item);
                break;
        }
    }

    /**
     * @hook apAjaxRequest
     *
     * @since 2.7.0
     *
     * @param array  $out
     * @param string $item
     */
    public function hookApAjaxRequest(&$out, $item)
    {
        if (!isset($GLOBALS['rlSmarty'])
            && ('bannersPrepareDeleting' === $item || 'bannersGetChildrenLocations' === $item)
        ) {
            require_once RL_LIBS . 'smarty/Smarty.class.php';
            $GLOBALS['reefless']->loadClass('Smarty');
        }

        switch ($item) {
            case 'bannersDeleteBanner':
                $bannerId = (int) $_REQUEST['id'];
                $out = $this->ajaxDeleteBanner($bannerId);
                break;

            case 'bannersMassActions':
                $ids = $_REQUEST['ids'];
                $action = $_REQUEST['action'];
                $out = $this->ajaxBannersMassActions($ids, $action);
                break;

            case 'bannersPrepareDeleting':
                $id = $_REQUEST['id'];
                $out = $this->ajaxPrepareDeleting($id);
                break;

            case 'bannersDeleteBannerBox':
                $key = $_REQUEST['key'];
                $out = $this->ajaxDeleteBannerBox($key);
                break;

            case 'bannersDeletePlan':
                $id = $_REQUEST['id'];
                $out = $this->ajaxDeletePlan($id);
                break;

            case 'bannersGetChildrenLocations':
                $parent = $_REQUEST['parent'];
                $out = $this->ajaxMfGetChildren($parent);
                break;
        }
    }

    /**
     * @hook tplFooter
     *
     * @since 2.7.0
     */
    public function hookTplFooter()
    {
        $GLOBALS['rlSmarty']->display(__DIR__ . '/footer.tpl');
    }

    /**
     * @hook phpUrlBottom
     *
     * @since 2.9.0
     */
    public function hookPhpUrlBottom(&$url, $mode, $data, $lang)
    {
        if ($data
            && $data['key'] === 'banners_edit_banner'
            && $_REQUEST['id']
            && !$GLOBALS['bannersOwnEditRequest']
        ) {
            $GLOBALS['bannersOwnEditRequest'] = true;
            $url = $GLOBALS['reefless']->getPageUrl('banners_edit_banner', null, $lang, "id={$_REQUEST['id']}");
            $GLOBALS['bannersOwnEditRequest'] = false;
        }
    }

    /**
     * Update to 2.9.0 version
     */
    public function update290()
    {
        global $rlDb;

        if (in_array('ru', array_keys($GLOBALS['languages']))) {
            $russianTranslation = json_decode(file_get_contents(RL_PLUGINS . 'banners/i18n/ru.json'), true);
            foreach ($russianTranslation as $phraseKey => $phrase) {
                if ($rlDb->getOne('ID', "`Key` = '{$phraseKey}' AND `Code` = 'ru'", 'lang_keys')) {
                    $rlDb->updateOne([
                        'fields' => ['Value' => $phrase],
                        'where'  => ['Key'   => $phraseKey, 'Code' => 'ru'],
                    ], 'lang_keys');
                } else {
                    $rlDb->insertOne([
                        'Code'   => 'ru',
                        'Module' => 'common',
                        'Key'    => $phraseKey,
                        'Value'  => $phrase,
                        'Plugin' => 'banners',
                    ], 'lang_keys');
                }
            }
        }
    }

    /**
     * Get minimal max file size for upload
     *
     * @deprecated 2.8.0
     *
     * @since 2.7.0
     *
     * @todo Remove as soon as compatible will be >= 4.6.1
     *
     * @return int
     */
    private static function getMaxFileUploadSize()
    {
    }
}
