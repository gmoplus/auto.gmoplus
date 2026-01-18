<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: RLCATEGORIESICONS.CLASS.PHP
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

class rlCategoriesIcons extends Flynax\Abstracts\AbstractPlugin implements Flynax\Interfaces\PluginInterface
{
    /**
     * @deprecated 3.0.0
     */
    private $isUseIconBox = false;

    /**
     * Flag the block was used
     *
     * @since 2.2.2
     * @var boolean
     */
    private $ltCatBlock = false;

    /**
    * Install plugin
    *
    * @since 3.0.0
    */
    public function install()
    {
        global $rlDb;

        $rlDb->addColumnToTable('Icon', 'varchar(128) NOT NULL', 'categories');
        $rlDb->addColumnToTable('Icon', 'varchar(128) NOT NULL', 'listing_types');

        $GLOBALS['rlCache']->updateCategories();
    }

    public function ajaxDeleteIcon($key = '', $object = 'category')
    {
        global $_response, $rlDb;

        $GLOBALS['rlValid']->sql($key);
        $_response->setCharacterEncoding('UTF-8');
        $table = $object == 'category' ? 'categories' : 'listing_types';
        $icon = $rlDb->getOne('Icon', "`Key` = '{$key}'", $table);

        $update_info = array(
            'fields' => array('Icon' => ''),
            'where' => array('Key' => $key)
        );

        $rlDb->updateOne($update_info, $table);

        if (!empty($icon)) {
            @unlink(RL_FILES . $icon);
            @unlink(RL_FILES . str_replace('icon', 'icon_original', $icon));
        }
        if ($object == 'category') {
            $GLOBALS['rlCache']->updateCategories();
        }

        $js = <<< JAVASCRIPT
        $('#gallery').slideUp('normal');
        $('#fileupload').html(null);
        printMessage('notice','{$GLOBALS['lang']['category_icon_icon_deleted']}');
        $('.category-icon-cont .svg-icon-row').removeClass('hide');
JAVASCRIPT;

        $_response->script($js);

        return $_response;
    }

    /**
     * Update icons after change icon sizes
     * @param int $width
     * @param int $height
     */
    public function updateIcons($width = 0, $height = 0)
    {
        global $reefless, $rlDb;

        if ($width > 0 && $height > 0) {
            $reefless->loadClass('Resize');
            $reefless->loadClass('Crop');

            // get categories
            $sql = "SELECT `ID`, `Icon` FROM `" . RL_DBPREFIX . "categories` ";
            $sql .= "WHERE `Icon` <> '' AND `Status` <> 'trash'";
            $categories = $rlDb->getAll($sql);
            if (!empty($categories)) {
                foreach ($categories as $category) {
                    $this->resizeIcon($category, $width, $height);
                }
            }
            // get listing types
            if (version_compare($GLOBALS['config']['rl_version'], '4.5.1') >= 0) {
                $sql = "SELECT `ID`, `Icon` FROM `" . RL_DBPREFIX . "listing_types` ";
                $sql .= "WHERE `Icon` <> '' AND `Status` <> 'trash'";
                $listing_types = $rlDb->getAll($sql);
                if (!empty($listing_types)) {
                    foreach ($listing_types as $type) {
                        $this->resizeIcon($type, $width, $height);
                    }
                }
            }
            unset($categories, $listing_types);
        }
    }

    /**
     * Resize icon
     * @param array $item
     * @param int $width
     * @param int $height
     */
    public function resizeIcon($item = array(), $width = 0, $height = 0)
    {
        global $rlCrop, $rlResize, $config;

        if (!empty($item['Icon'])) {
            $original = RL_FILES . str_replace("icon", "icon_original", $item['Icon']);
            $icon_name = $item['Icon'];
            $icon_file = RL_FILES . $icon_name;

            if ($config['icon_crop_module']) {
                $rlCrop->loadImage($original);
                $rlCrop->cropBySize($width, $height, ccCENTER);
                $rlCrop->saveImage($icon_file, $config['img_quality']);
                $rlCrop->flushImages();

                $rlResize->resize($icon_file, $icon_file, 'C', array($width, $height));
            } else {
                $rlResize->resize($original, $icon_file, 'C', array($width, $height), null, false);
            }

            if (is_readable($icon_file)) {
                chmod($icon_file, 0644);
            }
        }
    }

    /**
     * Check if uploaded file is image type
     * @param mixed $image
     */
    public function isImage($image = false)
    {
        if (!$image) {
            return false;
        }
        $allowed_types = array(
            'image/gif',
            'image/jpeg',
            'image/jpg',
            'image/png'
        );

        $img_details = getimagesize($image);
        if (in_array($img_details['mime'], $allowed_types)) {
            return true;
        }
        return false;
    }

    /**
     * @hook getCategoriesModifySelect
     * @since 2.2.0
     */
    public function hookGetCategoriesModifySelect()
    {
        $GLOBALS['select'][] = 'Icon';
    }

    /**
     * @hook tplPreCategory
     * @since 2.2.0
     */
    public function hookTplPreCategory()
    {
        global $rlSmarty, $config;

        if ($rlSmarty->_tpl_vars['cat']['Icon'] && $config['categories_icons_position'] == 'top') {
            $rlSmarty->display(RL_PLUGINS . 'categories_icons' . RL_DS . 'icon.tpl');
        }
    }

    /**
     * @hook tplPreCategoryName
     * @since 3.0.0
     */
    public function hookTplPreCategoryName()
    {
        global $rlSmarty, $config;

        if ($rlSmarty->_tpl_vars['cat']['Icon'] && $config['categories_icons_position'] == 'left') {
            $rlSmarty->display(RL_PLUGINS . 'categories_icons' . RL_DS . 'icon.tpl');
        }
    }

    /**
     * @hook tplPostCategory
     * @since 2.2.0
     */
    public function hookTplPostCategory()
    {
        global $rlSmarty, $config;

        if ($rlSmarty->_tpl_vars['cat']['Icon'] && in_array($config['categories_icons_position'], ['right', 'bottom'])) {
            $rlSmarty->display(RL_PLUGINS . 'categories_icons' . RL_DS . 'icon.tpl');
        }
    }

    /**
     * @hook tplPreSubCategory
     * @since 2.2.0
     */
    public function hookTplPreSubCategory()
    {
        $this->showSubcategoryIcon('left');
    }

    /**
     * @hook tplPostSubCategory
     * @since 3.0.0
     */
    public function hookTplPostSubCategory()
    {
        $this->showSubcategoryIcon('right');
    }

    /**
     * Show subcategory icon
     *
     * @since 3.0.0
     *
     * @param  string $position - 'left' or 'right' position
     */
    private function showSubcategoryIcon(string $position): void
    {
        global $rlSmarty, $config;

        $block = $rlSmarty->_tpl_vars['block'];
        $cat = $rlSmarty->_tpl_vars['sub_cat'];

        if ($cat['Icon']
            && $config['categories_icons_subcategory_icons']
            && $config['categories_icons_subcategory_position'] == $position
            && (
                (strpos($block['Key'], 'ltcategories_') === 0 && $config['categories_icons_type_page'])
                || strpos($block['Key'], 'ltcb_') === 0
            )
        ) {
            $rlSmarty->display(RL_PLUGINS . 'categories_icons' . RL_DS . 'subcat_icons.tpl');
        }
    }

    /**
     * @hook apTplCategoriesForm
     * @since 2.2.0
     */
    public function hookApTplCategoriesForm()
    {
        $this->getGroupIdToSmarty();
    }
    /**
     * Get id group  and asign to smarty
     *
     * @since 2.2.2
     *
     * @return  void
     *
     */
    public function getGroupIdToSmarty()
    {
        global $rlSmarty;

        if ($_SESSION['categories_icons_group_id']) {
            $groupID = $_SESSION['categories_icons_group_id'];
        } else {
            $groupID = $GLOBALS['rlDb']->getOne('ID', "`Key` = 'categories_icons'", 'config_groups');
            $_SESSION['categories_icons_group_id'] = $groupID;
        }

        $rlSmarty->assign_by_ref('ci_groupID', $groupID);

        $rlSmarty->display(RL_PLUGINS . 'categories_icons/admin/add_category_block.tpl');
    }
    /**
     * @hook apPhpCategoriesBeforeAdd
     * @since 2.2.0
     */
    public function hookApPhpCategoriesBeforeAdd()
    {
        if ($_POST['category_icon_svg']) {
            $this->setSVGIcon($_POST['category_icon_svg']);
        } else {
            $this->uploadIcon($_FILES['icon']);
        }
    }

    /**
     * @hook apPhpCategoriesBeforeEdit
     * @since 2.2.0
     */
    public function hookApPhpCategoriesBeforeEdit()
    {
        global $category_info;

        if ($_POST['category_icon_svg']) {
            if ($category_info['Icon']) {
                $this->removeIconFiles($category_info['Icon']);
            }

            $this->setSVGIcon($_POST['category_icon_svg']);
        } elseif ($category_info['Icon'] && false !== strpos($category_info['Icon'], '.svg') && !$_POST['category_icon_svg']) {
            $this->resetSVGIcon();
        } else {
            $this->uploadIcon($_FILES['icon'], $category_info);
        }
    }

    /**
     * @hook apPhpCategoriesBottom
     * @since 2.2.0
     */
    public function hookApPhpCategoriesBottom()
    {
        $GLOBALS['reefless']->loadClass('CategoriesIcons', null, 'categories_icons');
        $GLOBALS['rlXajax']->registerFunction(array('deleteIcon', $GLOBALS['rlCategoriesIcons'], 'ajaxDeleteIcon'));
    }

    /**
     * @hook apPhpCategoriesPost
     * @since 2.2.0
     */
    public function hookApPhpCategoriesPost()
    {
        global $category_info;

        $_POST['icon'] = $category_info['Icon'];
    }

    /**
     * @hook simulateCatBlocks
     * @since 2.2.2
     */
    public function hookSimulateCatBlocks($blocks, $categories, $cat_blocks)
    {
        if ($GLOBALS['rlAllInOne'] && $cat_blocks && count($cat_blocks) === 1) {
            $countCategoriesBlocks = 0;
            foreach (reset($cat_blocks) as $catBlock) {
                if ($blocks['ltcb_' . $catBlock]) {
                    $countCategoriesBlocks++;
                }
            }

            if ($countCategoriesBlocks !== count(reset($cat_blocks))) {
                $this->ltCatBlock = true;
            }
            $this->ltCatBlock = true;
        }
        $GLOBALS['rlSmarty']->assign_by_ref('ltCatBlock', $this->ltCatBlock);
    }

    /**
     * @deprecated 3.0.0
     */
    public function hookTplFooter()
    {}

    /**
     * @hook apPhpConfigAfterUpdate
     * @since 2.2.0
     */
    public function hookApPhpConfigAfterUpdate()
    {
        global $dConfig;

        if (!empty($dConfig['categories_icons_width']['value']) && !empty($dConfig['categories_icons_height']['value'])) {
            $this->updateIcons((int)$dConfig['categories_icons_width']['value'], (int)$dConfig['categories_icons_height']['value']);
        }
    }

    /**
     * @hook apPhpConfigAfterUpdate
     * @since 2.2.0
     */
    public function hookApTplListingTypesForm()
    {
        $this->getGroupIdToSmarty();
    }

    /**
     * @hook apPhpListingTypesPost
     * @since 2.2.0
     */
    public function hookApPhpListingTypesPost()
    {
        global $type_info;

        $_POST['icon'] = $type_info['Icon'];
    }

    /**
     * @hook apPhpListingTypesBeforeAdd
     * @since 2.2.0
     */
    public function hookApPhpListingTypesBeforeAdd()
    {
        if ($_POST['category_icon_svg']) {
            $this->setListingTypeSVGIcon();
        } else {
            $this->uploadIcon($_FILES['icon'], false, 'type');
        }
    }

    /**
     * @hook apPhpListingTypesBeforeEdit
     * @since 2.2.0
     */
    public function hookApPhpListingTypesBeforeEdit()
    {
        global $type_info;

        if ($_POST['category_icon_svg']) {
            if ($type_info['Icon']) {
                $this->removeIconFiles($type_info['Icon']);
            }

            $this->setListingTypeSVGIcon();
        } elseif ($type_info['Icon'] && false !== strpos($type_info['Icon'], '.svg') && !$_POST['category_icon_svg']) {
            $this->resetListingTypeSVGIcon();
        } else {
            $this->uploadIcon($_FILES['icon'], $type_info, 'type');
        }
    }

    /**
     * @hook apPhpListingTypesBottom
     * @since 2.2.0
     */
    public function hookApPhpListingTypesBottom()
    {
        $GLOBALS['reefless']->loadClass('CategoriesIcons', null, 'categories_icons');
        $GLOBALS['rlXajax']->registerFunction(array('deleteIcon', $GLOBALS['rlCategoriesIcons'], 'ajaxDeleteIcon'));
    }

    /**
     * @hook apTplHeader
     * @since 3.0.0
     */
    public function hookApTplHeader()
    {
        global $tpl_settings, $config;

        if (
            ($GLOBALS['cInfo']['Key'] == 'categories' && !$tpl_settings['category_menu'])
            || (
                $GLOBALS['cInfo']['Key'] == 'listing_types'
                && !$tpl_settings['category_menu_listing_type']
                && !$tpl_settings['listing_type_form_icon']
            )
        ) {
            echo '<link href="' . RL_TPL_BASE . 'css/icons-manager.css?rev=' . $config['static_files_revision'] . '" type="text/css" rel="stylesheet" />';
            echo <<< HTML
            <style>
            .svg-icon-row.hide,
            .svg-icon-row.hide + span {
                display: none !important;
            }
            </style>
HTML;
        }
    }

    /**
     * @hook tplHeader
     * @since 3.0.0
     */
    public function hookTplHeader()
    {
        global $config;

        $categories_block_exists = false;

        foreach ($GLOBALS['blocks'] as $block) {
            if (strpos($block['Key'], 'ltcb_') === 0 || strpos($block['Key'], 'ltcategories_') === 0) {
                $categories_block_exists = true;
                break;
            }
        }

        $icon_width = $config['categories_icons_width'];
        $icon_height = $config['categories_icons_height'];

        if ($GLOBALS['page_info']['Controller'] == 'listing_type') {
            $icon_width = $config['categories_icons_width_type_page'];
            $icon_height = $config['categories_icons_height_type_page'];
        }

        if ($categories_block_exists) {
            echo <<< HTML
            <style>
            .category-icon svg {
                width: {$icon_width}px;
                height: {$icon_height}px;
                vertical-align: top;
            }
            .category-icon svg > * {
                stroke-width: 1.2px;
            }
            @media screen and (max-width: 991px) {
                .category-icon svg {
                    width: calc({$config['categories_icons_width']}px * 0.75);
                    height: calc({$config['categories_icons_height']}px * 0.75);
                }
            }
            </style>
HTML;

            if (in_array($config['categories_icons_position'], ['top', 'bottom'])) {
                echo <<< HTML
                <style>
                .category-wrapper-hook {
                    flex-wrap: wrap;
                }
                </style>
HTML;
            }
        }
    }

    /**
     * @hook apMixConfigItem
     * @since 3.0.0
     *
     * @param array $value
     * @param array $systemSelects - Required configs with "select" type
     */
    public function hookApMixConfigItem(&$value, &$systemSelects)
    {
        if (in_array($value['Key'], ['categories_icons_position', 'categories_icons_subcategory_position'])) {
            $systemSelects[] = $value['Key'];
        }
    }

    /**
     * Set icon name
     *
     * @since 3.0.0
     * @param string $iconName - Icon name from the gallery
     */
    public function setSVGIcon($iconName)
    {
        global $update_data;
        $update_data['fields']['Icon'] = $iconName;
    }

    /**
     * Reset icon name
     *
     * @since 3.0.0
     */
    public function resetSVGIcon()
    {
        global $update_data;
        $update_data['fields']['Icon'] = '';
    }

    /**
     * Set listing type icon name
     *
     * @since 3.0.1
     */
    public function setListingTypeSvgIcon()
    {
        global $update_date;
        $update_date['fields']['Icon'] = $_POST['category_icon_svg'];
    }

    /**
     * Reset listing type svg icon name
     *
     * @since 3.0.1
     */
    public function resetListingTypeSVGIcon()
    {
        global $update_date;
        $update_date['fields']['Icon'] = '';
    }

    /**
     * Remove icon files
     *
     * @since 3.0.1
     *
     * @param string $file - Icon file name
     */
    private function removeIconFiles(string $file): void
    {
        $icon_dir = RL_FILES . $file;
        $original_icon_dir = RL_FILES . str_replace('icon', 'icon_original', $file);

        if (is_file($icon_dir)) {
            unlink($icon_dir);
        }

        if (is_file($original_icon_dir)) {
            unlink($original_icon_dir);
        }
    }

    /**
     * Upload icon to server
     * @param array $icon
     * @param array $item_info
     * @param string $type
     */
    public function uploadIcon($icon, $item_info = array(), $type = 'category')
    {
        global $data, $update_date, $update_data, $config, $rlCrop, $rlResize, $reefless;

        if (!empty($icon['tmp_name']) && $this->isImage($icon['tmp_name'])) {
            $reefless->loadClass('Resize');
            $reefless->loadClass('Crop');

            if ($item_info['Icon']) {
                $this->removeIconFiles($item_info['Icon']);
            }

            $file_ext = explode('.', $icon['name']);
            $file_ext = array_reverse($file_ext);
            $file_ext = '.' . $file_ext[0];

            $tmp_location = RL_UPLOAD . 'tmp_listing' . mt_rand() . time() . $file_ext;

            if (move_uploaded_file($icon['tmp_name'], $tmp_location)) {
                chmod($tmp_location, 0777);

                $icon_name = $type . '_icon_' . mt_rand() . time() . $file_ext;

                $icon_original = str_replace("icon", "icon_original", $icon_name);
                copy($tmp_location, RL_FILES . $icon_original);

                $icon_file = RL_FILES . $icon_name;

                if ($config['icon_crop_module']) {
                    $rlCrop->loadImage($tmp_location);
                    $rlCrop->cropBySize($config['categories_icons_width'], $config['categories_icons_height'], ccCENTER);
                    $rlCrop->saveImage($icon_file, $config['img_quality']);
                    $rlCrop->flushImages();

                    $rlResize->resize($icon_file, $icon_file, 'C', array($config['categories_icons_width'], $config['categories_icons_height']));
                } else {
                    $rlResize->resize($tmp_location, $icon_file, 'C', array($config['categories_icons_width'], $config['categories_icons_height']), null, false);
                }

                unlink($tmp_location);

                if (is_readable($icon_file)) {
                    chmod($icon_file, 0644);
                    if (isset($update_date['fields'])) {
                        $update_date['fields']['Icon'] = $icon_name;
                    } elseif ($update_data['fields']) {
                        $update_data['fields']['Icon'] = $icon_name;
                    } else {
                        $data['Icon'] = $icon_name;
                    }
                }
            }
        }
    }

    /**
     * @version 2.2.0
     */
    public function update220()
    {
        $GLOBALS['rlDb']->addColumnToTable('Icon', 'varchar(255) NOT NULL', 'listing_types');
    }

    /**
     * @version 2.2.2
     */
    public function update222()
    {
        $sql = "DELETE FROM `{db_prefix}hooks` WHERE `Name` = 'apTplListingTypesAction' AND `Plugin` = 'categories_icons'";
        $GLOBALS['rlDb']->query($sql);
        @unlink(RL_PLUGINS . 'categories_icons/icons_post_category.tpl');
        @unlink(RL_PLUGINS . 'categories_icons/icons_pre_category.tpl');
        $languages = $GLOBALS['languages'];
        if (in_array('ru', array_keys($languages))) {
            $russianTranslation = json_decode(file_get_contents(RL_UPLOAD . 'categories_icons/i18n/ru.json'), true);
            foreach (['config+option+categories_icons_position_bottom', 'config+option+categories_icons_position_top', 'config+option+categories_icons_position_left', 'config+option+categories_icons_position_right'] as $phraseKey) {
                if (!$GLOBALS['rlDb']->getOne('ID', "`Key` = '{$phraseKey}' AND `Code` = 'ru'", 'lang_keys')) {
                    $insert_phrases = array(
                        'Code'   => 'ru',
                        'Module' => 'admin',
                        'Key'    =>  $phraseKey,
                        'Value'  => $russianTranslation[$phraseKey],
                    );
                    $GLOBALS['rlDb']->insertOne($insert_phrases, 'lang_keys');
                } else {
                    $GLOBALS['rlDb']->updateOne(array(
                        'fields' => array('Value' => $russianTranslation[$phraseKey]),
                        'where'  => array('Key'   => $phraseKey, 'Code' => 'ru'),
                    ), 'lang_keys');
                }
            }
        }
    }

    /**
     * @version 3.0.0
     */
    public function update300()
    {
        global $rlDb;

        // Remove unnecessary phrases
        $phrases = array(
            'category_icon_image',
        );

        $rlDb->query("
            DELETE FROM `{db_prefix}lang_keys`
            WHERE `Plugin` = 'categories_icons' AND `Key` IN ('" . implode("','", $phrases) . "')"
        );

        unlink(RL_PLUGINS . 'categories_icons/rlInstall.class.php');

        $rlDb->query("UPDATE `{db_prefix}plugins` SET `Uninstall` = '' WHERE `Key` = 'categories_icons'");
        $rlDb->delete(['Name' => 'tplFooter', 'Plugin' => 'categories_icons'], 'hooks');

        foreach ($GLOBALS['rlPlugin']->configs as $position => $config_item) {
            $rlDb->updateOne([
                'fields' => ['Position' => $position],
                'where' => ['Plugin' => 'categories_icons', 'Key' => $config_item['Key']],
            ], 'config');
        }
    }

    /**
    * Uninstall plugin
    */
    public function uninstall()
    {
        global $rlDb;

        // Remove category icons
        $cats = $rlDb->fetch(array('Icon'), null, "WHERE `Icon` <> '' AND `Icon` NOT LIKE '%.svg'", null, 'categories');

        foreach($cats as $value) {
            unlink(RL_FILES . $value['Icon']);
            unlink(RL_FILES . str_replace("icon", "icon_original", $value['Icon']));
        }

        $rlDb->dropColumnFromTable('Icon', 'categories');
        $GLOBALS['rlCache']->updateCategories();

        // Remove listing type icons
        $listing_types = $rlDb->fetch('*', null, "WHERE `Icon` <> '' AND `Icon` NOT LIKE '%.svg'", null, 'listing_types');

        foreach($listing_types as $key => $value) {
            unlink(RL_FILES . $value['Icon']);
            unlink(RL_FILES . str_replace("icon", "icon_original", $value['Icon']));
        }

        $rlDb->dropColumnFromTable('Icon', 'listing_types');
    }
}
