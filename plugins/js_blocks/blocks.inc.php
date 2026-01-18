<?php
/**copyright*/

use Flynax\Utils\Valid;

require __DIR__ . '/../../includes/config.inc.php';

$filename = RL_CACHE . 'js_blocks_' . md5(serialize($_GET));
$boxID    = (string) $_GET['custom_id'];
$fmtime   = 0;

if (file_exists($filename)) {
    $fmtime = filemtime($filename);
}

if ($fmtime && $fmtime + 600 > time()) {
    echo file_get_contents($filename);
    exit;
} else {
    require RL_INC . 'control.inc.php';

    // Load template settings
    $ts_path = RL_ROOT . 'templates' . RL_DS . $config['template'] . RL_DS . 'settings.tpl.php';
    if (is_readable($ts_path)) {
        require_once $ts_path;
    }

    $reefless->loadClass('RemoteAdverts', null, 'js_blocks');

    if ($config['membership_module']) {
        $reefless->loadClass('Account');
    }

    $get_lang = Valid::escape($_GET['lang']);

    if (!empty($get_lang) && $rlDb->getOne('Code', "`Code` = '" . $get_lang . "'", 'languages')) {
        define('RL_LANG_CODE', $get_lang);
    } else {
        define('RL_LANG_CODE', $config['lang']);
    }

    define('RL_DATE_FORMAT', $rlDb->getOne('Date_format', "`Code` = '" . RL_LANG_CODE . "'", 'languages'));

    $lang = $rlLang->getLangBySide('frontEnd', RL_LANG_CODE);
    $rlSmarty->assign_by_ref('lang', $lang);

    $bPath = RL_URL_HOME;
    if ($config['lang'] != RL_LANG_CODE && $config['mod_rewrite']) {
        $bPath .= RL_LANG_CODE . '/';
    }
    if (!$config['mod_rewrite']) {
        $bPath .= 'index.php';
    }
    define('SEO_BASE', $bPath);
    $rlSmarty->assign('rlBase', $bPath);

    $limit       = intval($_GET['limit']) > 0 ? intval($_GET['limit']) : 12;
    $orderField  = $_GET['order_by'] ? $_GET['order_by'] : 'Date';
    $orderType   = $_GET['order_type'] ? strtoupper($_GET['order_type']) : 'DESC';
    $type        = $_GET['listing_type'] ? Valid::escape($_GET['listing_type']) : false;
    $category_id = $_GET['category_id'] ? intval($_GET['category_id']) : false;

    unset($_GET['category_id'], $_GET['order_by'], $_GET['order_type']);

    $rlSmarty->register_function('str2path', array('rlSmarty', 'str2path'));

	$listings = $rlListings->getListings($category_id, $orderField, $orderType, 0, $limit, $type);
    $rlSmarty->assign('listings', $listings);
    $rlSmarty->assign('tmp_code', md5(mt_rand()));
    $content = $rlSmarty->fetch(RL_PLUGINS . 'js_blocks/blocks.tpl');

    if ($boxID && (($_SESSION['raBoxes'] && !in_array($boxID, $_SESSION['raBoxes'])) || !isset($_SESSION['raBoxes']))) {
        require __DIR__ . '/vendor/autoload.php';
        $content = (new Tholu\Packer\Packer($content, 'Normal', true, false, true))->pack();
        file_put_contents($filename, $content);
    }

    $rlDb->connectionClose();

    echo $content;
}

function isIntVal($data)
{
    if (is_int($data) === true) {
        return true;
    } elseif (is_string($data) === true && is_numeric($data) === true) {
        return (strpos($data, '.') === false);
    }

    return false;
}
