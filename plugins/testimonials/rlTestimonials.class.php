<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLTESTIMONIALS.CLASS.PHP
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

use Flynax\Utils\Util;
use Flynax\Utils\Valid;

class rlTestimonials  extends Flynax\Abstracts\AbstractPlugin implements Flynax\Interfaces\PluginInterface
{
    /**
     * Box cache code
     *
     * @since 2.0.0
     */
    private $content = "
        global \$rlSmarty, \$blocks;

        \$code = <<< FL
{data_replace}
FL;

        \$testimonials = json_decode(\$code, true);

        \$out_testimonials = [];
        if (\$testimonials) {
            if (in_array(\$blocks['testimonials']['Side'], ['top', 'bottom', 'middle'])) {
                shuffle(\$testimonials);
                \$out_testimonials = array_slice(\$testimonials, 0, 3);
                \$rlSmarty->assign('testimonials_long', ['true']);
            }
            else {
                \$out_testimonials = \$testimonials[array_rand(\$testimonials)];
            }
        }

        \$rlSmarty->assign('testimonial_box', \$out_testimonials);
        \$rlSmarty->display(RL_PLUGINS . 'testimonials/box.tpl');
    ";

    /**
     * @since 2.0.0
     */
    public function install()
    {
        global $rlDb, $languages;

        $rlDb->createTable(
            'testimonials',
            "`ID` int(5) NOT NULL AUTO_INCREMENT,
            `Author` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
            `Account_ID` int(7) NOT NULL,
            `Testimonial` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
            `Date` datetime NOT NULL,
            `Email` varchar(100) NOT NULL,
            `IP` varchar(30) NOT NULL,
            `Status` ENUM('active', 'approval', 'pending') NOT NULL DEFAULT 'pending',
            INDEX (`Status`),
            INDEX(`Account_ID`),
            PRIMARY KEY  (`ID`)"
        );

        $rlDb->query(
            "UPDATE `{db_prefix}blocks` SET `Sticky` = 0,
            `Page_ID` = '2,8'
            WHERE `Key` = 'testimonials' LIMIT 1"
        );

        $this->updateBox();
    }

    /**
     * @since 2.0.0
     */
    public function uninstall()
    {
        global $rlDb;

        $rlDb->dropTable('testimonials');
    }

    /**
     * Updates sidebar box cache
     *
     * @since 2.0.0
     */
    public function updateBox()
    {
        global $rlDb;

        $testimonials = $rlDb->fetch(
            array('Author', 'Testimonial', 'Date', 'Account_ID'),
            array('Status' => 'active'),
            "ORDER BY `ID` DESC  LIMIT 5",
            null,
            'testimonials'
        );

        foreach ($testimonials as &$testimonial) {
            $testimonial['Testimonial'] = preg_replace('/[\n\r]/', '<br />', $testimonial['Testimonial']);
            Valid::revertQuotes($testimonial['Testimonial']);
        }

        $update = array(
            'fields' => array(
                'Content' => str_replace('{data_replace}', json_encode($testimonials), $this->content)
            ),
            'where' => array(
                'Key' => 'testimonials'
            )
        );

        $allow_html = $rlDb->rlAllowHTML;
        $rlDb->rlAllowHTML = true;
        $rlDb->updateOne($update, 'blocks');
        $rlDb->rlAllowHTML = $allow_html;
    }

	/**
	 * @deprecated 2.0.0
	 */
	public function getOne() {}

    //HOOKS

    /**
     * @hook apPhpHome
     *
     *  @since 2.0.0
     */
    public function hookApPhpHome()
    {
        $GLOBALS['rlTestimonials']->apStatistics();
    }

    /**
     * @hook tplHeader
     */
    public function hookTplHeader()
    {
        if ($GLOBALS['blocks']['testimonials'] || $GLOBALS['page_info']['Key'] == 'testimonials') {
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'testimonials/header.tpl');
        }
    }

    /**
     * @since 2.0.1
     * @hook tplHeaderUserNav
     */
    public function hookTplHeaderUserNav()
    {
        /**
         * @todo - Move this code to tplBodyTop hook once it is available
         */
        if ($GLOBALS['blocks']['testimonials'] || $GLOBALS['page_info']['Key'] == 'testimonials') {
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'testimonials/static/quote.svg');
        }
    }

    /**
     * @since 2.1.0
     *  @hook apPhpBlocksTop
     */
    public function hookApPhpBlocksTop()
    {
        $GLOBALS['l_block_excluded'][] = 'testimonials';
    }

    /**
     * Get all active testimonials
     */
    public function get()
    {
        global $rlSmarty, $rlDb, $rlValid, $config;

        $testimonials_page = (int) $_GET['pg'];
        $limit = (int) $GLOBALS['config']['testimonials_per_page'];
        $start = $testimonials_page > 1 ? ($testimonials_page - 1) * $limit : 0;

        $testimonials = $rlDb->fetch(
            array('Author', 'Testimonial', 'Date', 'Account_ID'),
            array('Status' => 'active'),
            "ORDER BY `ID` DESC  LIMIT {$start}, {$limit}",
            null,
            'testimonials'
        );

        foreach ($testimonials as $key => $testimonial) {
            if ($testimonial['Account_ID']) {
                $testimonials[$key]['ProfileLink'] = Flynax\Utils\Profile::getPersonalAddress($testimonial['Account_ID']);
            }
        }

        $rlSmarty->assign('testimonials_page', $testimonials_page);

        if ($testimonials) {
            $rlSmarty->assign_by_ref('testimonials', $testimonials);
            $rlSmarty->assign('countTestimonials', $this->getTotalCountTestimonials());
        }
    }

    /**
     * Get total count testimonials
     *
     * @since 2.0.0
     *
     * @return int - Count
     **/
    public function getTotalCountTestimonials()
    {
        global $rlDb ;

        $total = $rlDb->getRow("
            SELECT COUNT(`ID`) AS `Count`
            FROM `{db_prefix}testimonials` WHERE `Status` = 'active'
        ");

        return $total['Count'];
    }

    /**
     * Add testimonial
     *
     * @package AJAX
     *
     * @param  string $name        - Author name
     * @param  string $email       - Author email
     * @param  string $testimonial - Testimonial text
     * @param  string $code        - Security code
     * @return array               - Ajax response
     **/
    public function ajaxAdd($name = false, $email = false, $testimonial = false, $code = false)
    {
        global $rlValid, $rlSmarty, $lang, $account_info, $config, $rlDb, $rlLang, $page_info;

        $errors = [];
        $error_fields = [];

        if (empty($name)) {
            $errors[] = str_replace('{field}', '<span class="field_error">' . $lang['your_name'] . '</span>', $lang['notice_field_empty']);
            $error_fields[] = '#t-name';
        }

        if (!empty($email) && !$rlValid->isEmail($email)) {
            $errors[] = $lang['notice_bad_email'];
            $error_fields[] = '#t-email';
        }

        if (empty($testimonial) || strlen($testimonial) < 20) {
            $errors[] = $rlLang->getSystem('testimonial_not_valid_content');
            $error_fields[] = '#t-testimonial';
        }

        if ($code != $_SESSION['ses_security_code'] || empty($_SESSION['ses_security_code']) || !$code) {
            $errors[] = $lang['security_code_incorrect'];
            $error_fields[] = '#security_code';
        }

        if ($errors) {
            $error_content = '<ul>';
            foreach ($errors as $error) {
                $error_content .= '<li>' . $error . '</li>';
            }
            $error_content .= '</ul>';

            return [
                'status' => 'ERROR',
                'errorContent' => $error_content,
                'errorFields' => implode(',', $error_fields)
            ];
        } else {
            if (!$page_info) {
                $page_info = $rlDb->fetch('*', ['Key' => 'testimonials'], null, 1, 'pages', 'row');
            }

            $testimonial = strip_tags($testimonial, '<a>');
            $testimonial = preg_replace('/<a\s+(title="[^"]+"\s+)?href=["\']([^"\']+)["\'][^\>]*>[^<]+<\/a>/mi', '$2', $testimonial);

            $insert = array(
                'Author' => $name,
                'Account_ID' => $account_info['ID'],
                'Testimonial' => $testimonial,
                'Date' => 'NOW()',
                'Email' => $email,
                'IP' => Util::getClientIP(),
                'Status' => $config['testimonials_moderate'] ? 'pending' : 'active'
            );

            $rlDb->insertOne($insert, 'testimonials');

            $phrase_key = $config['testimonials_moderate']
            ? 'testimonials_accepted_to_moderation'
            : 'testimonials_posted';

            $this->get();

            if (!$config['testimonials_moderate']) {
                $this->updateBox();
            }

            $rlSmarty->assign('lang', $lang);
            $html = $rlSmarty->fetch(RL_PLUGINS . 'testimonials/dom.tpl', null, null, false);

            return [
                'status' => 'OK',
                'data' => $html,
                'msg' => $rlLang->getSystem($phrase_key)
            ];
        }
    }

    /**
     * @hook apAjaxRequest
     *
     * @since 2.0.0
     *
     * @param array  $out
     * @param string $item
     */
    public function hookAjaxRequest(&$out, $item)
    {
        global $rlSmarty, $lang;

        if (!$item || $item != 'addTM') {
            return false;
        }

        $out = $this->ajaxAdd(
            $_REQUEST['nameTM'],
            $_REQUEST['emailTM'],
            $_REQUEST['testimonial'],
            $_REQUEST['captchaTM']
        );
    }

    /**
     * Control ajax queries
     *
     * @since 2.0.0
     *
     * @hook apAjaxRequest
     *
     * @param array  $out  - response data
     */
    public function hookApAjaxRequest(&$out = null)
    {
        switch ($_REQUEST['mode']) {
            case 'deleteTestimonial':
                $response = $this->ajaxDelete($_REQUEST['tmID']);

                $out = $response;
                break;
        }
    }

    /**
     * delete testimonial
     *
     * @package ajax
     *
     * @param int $id - testimonial ID
     *
     **/
    public function ajaxDelete($id = false)
    {
        if (!$id) {
            return ['status' => 'ERROR'];
        }

        $id = (int) $id;

        if ($GLOBALS['rlDb']->delete(['ID' => $id], 'testimonials')) {
            $this->updateBox();

            return ['status' => 'OK'];
        } else {
            return ['status' => 'ERROR'];
        }
    }

    /**
     * build admin panel statistics section
     **/
    public function apStatistics()
    {
        global $plugin_statistics, $lang, $rlDb;

        $total = $rlDb->getRow("SELECT COUNT(`ID`) AS `Count` FROM `{db_prefix}testimonials`");
        $total = $total['Count'];

        $pending = $rlDb->getRow("SELECT COUNT(`ID`) AS `Count` FROM `{db_prefix}testimonials` WHERE `Status` = 'pending'");
        $pending = $pending['Count'];

        $link = RL_URL_HOME . ADMIN . '/index.php?controller=testimonials';

        $plugin_statistics[] = array(
            'name' => $lang['testimonials_testimonials'],
            'items' => array(
                array(
                    'name' => $lang['total'],
                    'link' => $link,
                    'count' => $total
                ),
                array(
                    'name' => $lang['pending'] . ' / ' . $lang['new'],
                    'link' => $link . '&amp;status=pending',
                    'count' => $pending
                )
            )
        );
    }

    /**
     * Update to 2.0.0 version
     */
    public function update200()
    {
        global $languages;

        $GLOBALS['rlDb']->query("
            UPDATE `{db_prefix}blocks` SET `Sticky` = 0, `Page_ID` = '8,2'
            WHERE `Key` = 'testimonials' LIMIT 1
        ");

        unlink(RL_PLUGINS . 'testimonials/page_responsive_42.tpl');
        unlink(RL_PLUGINS . 'testimonials/static/style.css');
        unlink(RL_PLUGINS . 'testimonials/static/gallery.png');

        $GLOBALS['rlDb']->query("ALTER TABLE `{db_prefix}testimonials` ADD INDEX ( `Account_ID` )");

        if (in_array('ru', array_keys($languages))) {
            $russianTranslation = json_decode(file_get_contents(RL_UPLOAD . 'testimonials/i18n/ru.json'), true);
            foreach ($russianTranslation as $phraseKey => $phraseValue) {
                $GLOBALS['rlDb']->updateOne(array(
                    'fields' => array('Value' => $phraseValue),
                    'where'  => array('Key'   => $phraseKey, 'Code' => 'ru'),
                ), 'lang_keys');
            }
        }

        // Remove old phrases
        $phrases = array(
            'testimonials_manager',
            'testimonials_add',
            'testimonials_ext_delete_notice',
            'testimonials_read_more_title',
        );

        $GLOBALS['rlDb']->query("
            DELETE FROM `{db_prefix}lang_keys`
            WHERE `Plugin` = 'testimonials' AND `Key` IN ('" . implode("','", $phrases) . "')
        ");

        // Remove useless hooks
        $GLOBALS['rlDb']->query("DELETE FROM `{db_prefix}hooks` WHERE `Plugin` = 'testimonials' AND `Name` = 'specialBlock'");

        $this->updateBox();
    }

    /**
     * Update to 2.1.0 version
     */
    public function update210()
    {
        $this->updateBox();
    }
}
