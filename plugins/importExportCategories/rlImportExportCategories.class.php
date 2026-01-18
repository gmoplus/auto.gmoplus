<?php


/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLIMPORTEXPORTCATEGORIES.CLASS.PHP
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
use Flynax\Plugins\ImportExportCategories\Import;

/**
 * Class rlImportExportCategories
 */
class rlImportExportCategories extends AbstractPlugin implements PluginInterface
{
    /**
     * @hook apTplHeader
     * @since 2.3.0
     */
    public function hookApTplHeader()
    {
        if ($_GET['controller'] !== 'importExportCategories') {
            return;
        }

        $href = RL_PLUGINS_URL . 'importExportCategories/admin/static/style.css';
        printf('<link href="%s" type="text/css" rel="stylesheet" />', $href);
    }

    /**
     * @hook apAjaxRequest
     * @since 3.0.0
     *
     * @param array  $out
     * @param string $item
     */
    public function hookApAjaxRequest(&$out, $item)
    {
        if ($item !== 'importCategory') {
            return;
        }

        require __DIR__ . '/vendor/autoload.php';

        $out = (new Import)->fromStack((int) $_REQUEST['stack']);
    }

    /**
     * @hook  apPhpIndexBottom
     * @since 2.3.0
     */
    public function hookApPhpIndexBottom()
    {
        global $_response, $lang;

        if ($_GET['controller'] === 'importExportCategories'
            && $_REQUEST['xjxfun'] === 'ajaxGetCatLevel'
            && ($categoryID = reset($_REQUEST['xjxargs']))
        ) {
            $_response->script("
                var imExChildInterval = setInterval(function(){
                    var \$childList = $('li#tree_cat_{$categoryID} ul');

                    if (\$childList.length) {
                        var \$spanCheckAll = $('\<span\>')
                            .addClass('green_10')
                            .text(\"{$lang['check_all']}\")
                            .click(function(){
                                \$childList.find('input').prop('checked', true);
                                levelDynamic('check', $(this));
                            });

                        var \$spanDivider = $('\<span\>').addClass('divider').text(' | ');

                        var \$spanUnCheckAll = $('\<span\>')
                            .addClass('green_10')
                            .text(\"{$lang['uncheck_all']}\")
                            .click(function(){
                                 levelDynamic('uncheck', $(this));
                                \$childList.find('input').prop('checked', false)
                            });

                        var \$divGrey = $('\<div\>').addClass('grey_area margin_block').append(
                            \$spanCheckAll,
                            \$spanDivider,
                            \$spanUnCheckAll
                         )

                        \$childList.after(\$divGrey);

                        clearInterval(imExChildInterval);

                        uncheckChildCheckboxes();
                    }
                }, 200);
            ");
        }
    }

    /**
     * @hook phpPreGetCategoryData
     * @since 3.0.0
     */
    public function hookPhpPreGetCategoryData($id = 0, $path = '', &$select = []): void
    {
        global $config, $languages, $rlLang;

        if ($config['multilingual_paths']
            && $_SESSION['imex_plugin']
            && $_SESSION['imex_plugin']['category_id']
            && ($_SERVER['SCRIPT_FILENAME'] === RL_PLUGINS . 'importExportCategories/admin/importExportCategories.inc.php'
                || $_REQUEST['item'] === 'importCategory'
            )
        ) {
            if (!$languages) {
                $languages = $rlLang->getLanguagesList();
            }

            foreach ($languages as $languageKey => $languageData) {
                if ($languageData['Code'] === $config['lang']) {
                    continue;
                }

                $select[] = 'Path_' . $languageData['Code'];
            }
        }
    }

    /**
     * @version 2.1.0
     */
    public function update210(): void
    {
        $GLOBALS['rlDb']->query("
            DELETE FROM `{db_prefix}lang_keys` 
            WHERE `Key` IN (
              'importExportCategories_selector_tr_level',
              'importExportCategories_selector_tr_parent',
              'importExportCategories_selector_tr_name',
              'importExportCategories_selector_tr_path',
              'importExportCategories_selector_tr_type',
              'importExportCategories_selector_tr_lock',
              'importExportCategories_selector_tr_key',
              'importExportCategories_no_parent'
            )
        ");
    }

    /**
     * @version 3.0.0
     */
    public function update300(): void
    {
        global $rlDb;

        $GLOBALS['reefless']->deleteDirectory(RL_PLUGINS . 'importExportCategories/phpExcel/');
        @unlink(RL_PLUGINS . 'importExportCategories/admin/import.php');
        @unlink(RL_PLUGINS . 'importExportCategories/admin/static/example.png');

        $rlDb->query(
            "DELETE FROM `{db_prefix}lang_keys` 
             WHERE `Plugin` = 'importExportCategories' AND `Key` IN (
                 'importExportCategories_import_reupload',
                 'importExportCategories_rowLevel'
             )"
        );

        if (array_key_exists('ru', $GLOBALS['languages'])) {
            $russianTranslation = json_decode(file_get_contents(RL_PLUGINS . 'importExportCategories/i18n/ru.json'), true);
            foreach ($russianTranslation as $phraseKey => $phrase) {
                if ($rlDb->getOne('ID', "`Key` = '{$phraseKey}' AND `Code` = 'ru'", 'lang_keys')) {
                    $rlDb->updateOne([
                        'fields' => ['Value' => $phrase],
                        'where'  => ['Key'  => $phraseKey, 'Code' => 'ru'],
                    ], 'lang_keys');
                } else {
                    $rlDb->insertOne([
                        'Code'   => 'ru',
                        'Module' => 'common',
                        'Key'    => $phraseKey,
                        'Value'  => $phrase,
                        'Plugin' => 'importExportCategories',
                    ], 'lang_keys');
                }
            }
        }
    }
}
