<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : MANIFEST.PHP
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

namespace Flynax\Plugins\PWA\Files;

use Flynax\Plugins\PWA\Config;
use function GuzzleHttp\Psr7\mimetype_from_filename;

class Manifest
{
    const MANIFEST_NAME = 'manifest.json';

    /**
     * todo: Add ability to create manifest outside of plugins folder
     */
    public function generate()
    {
        $names      = Config::i()->getConfigs()['multiple']['name'];
        $countNames = count($names);

        if ($countNames > 1) {
            foreach ($names as $configName) {
                $filePath = PWA_ROOT . 'files/' . $configName['Code'] . '-' . self::MANIFEST_NAME;
                $data     = $this->prepareData($configName['Code']);

                $fp = fopen($filePath, 'w');
                fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
                fclose($fp);
            }
        } else {
            $filePath = PWA_ROOT . 'files/' . self::MANIFEST_NAME;
            $data     = $this->prepareData();

            $fp = fopen($filePath, 'w');
            fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
            fclose($fp);
        }
    }

    /**
     * Collect data for manifests
     *
     * @param string $lang
     * @return array
     */
    private function prepareData($lang = '')
    {
        global $config;

        $startUrl = RL_URL_HOME;

        if ($config['mod_rewrite']) {
            $startUrl .= ($lang && $lang != $config['lang'] ? $lang . '/' : '') . '?utm_source=web_app_manifest';
        } else {
            $startUrl .= $lang && $lang != $config['lang'] ? 'index.php?language=' . $lang . '&' : 'index.php?';
            $startUrl .= 'utm_source=web_app_manifest';
        }

        $data = [
            'name'             => $this->getName($lang),
            'short_name'       => $this->getShortName($lang),
            'start_url'        => $startUrl,
            'background_color' => '#' . Config::i()->getConfig('pwa_color'),
            'theme_color'      => '#' . Config::i()->getConfig('pwa_color'),
            'description'      => $this->getDescription($lang),
            'display'          => 'standalone',
            'orientation'      => 'portrait',
            'icons'            => $this->getIcons(),
        ];

        return $data;
    }

    /**
     * Get name for manifest
     *
     * @param  $lang
     * @return string
     */
    private function getName($lang = '')
    {
        return Config::i()->getConfig('name', $lang ?: RL_LANG_CODE);
    }

    /**
     * Get short name for manifest
     *
     * @param  $lang
     * @return string
     */
    private function getShortName($lang = '')
    {
        return Config::i()->getConfig('short_name', $lang ?: RL_LANG_CODE);
    }

    /**
     * Get description for manifest
     *
     * @param  $lang
     * @return string
     */
    private function getDescription($lang = '')
    {
        return Config::i()->getConfig('description', $lang ?: RL_LANG_CODE);
    }

    /**
     * Get array with icons for manifest
     *
     * @return array
     */
    private function getIcons()
    {
        $icons         = Icon::getIconsFromDb();
        $manifestIcons = [];
        unset($icons['original']);

        foreach ($icons as $key => $icon) {
            if ('launch_portrait' === $icon['Type']) {
                continue;
            }

            $info     = getimagesize(PWA_FILES_PATH . $icon['Image']);
            $iconData = [
                'src'   => $icon['Image'],
                'sizes' => $key,
                'type'  => $info['mime'],
            ];

            if (Config::i()->getConfig('pwa_maskable_icons')) {
                $iconData['purpose'] = 'any maskable';
            }

            $manifestIcons[] = $iconData;
        }

        return $manifestIcons;
    }
}
