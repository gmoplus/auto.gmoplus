<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : RLCCBILL.CLASS.PHP
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2022
 *	https://www.flynax.com
 *
 ******************************************************************************/

class rlCcbill
{
    /**
     * Available plugins which using in the plugin
     *
     * @var []
     */
    protected $plugins;

    /**
     * List object items by group
     *
     * @var []
     */
    protected $object_items;

    /**
     * @hook apTplPaymentGatewaysBottom
     */
    public function hookApTplPaymentGatewaysBottom()
    {
        if ($_GET['item'] == 'ccbill') {
            $url = RL_PLUGINS_URL . 'ccbill/static/ccbill-speed-configuration-guide.pdf';
            echo <<< FL
<script type="text/javascript">
    $(document).ready(function(){
        $('#nav_bar').prepend('<a href="{$url}" class="button_bar" target="_blank"><span class="left"></span><span class="center_list">{$GLOBALS['lang']['ccbill_speed_configuration_guide']}</span><span class="right"></span></a>');
    });
</script>
FL;
        }
    }

    /**
     * Check if plugin installed
     *
     * @param  string $key
     * @return bool
     */
    public function isPluginInstalled($key = '')
    {
        if (!$key) {
            return false;
        }

        if (isset($this->plugins[$key])) {
            if ($this->plugins[$key]) {
                return true;
            } else {
                return false;
            }
        }
        $sql = "SELECT * FROM `" . RL_DBPREFIX . "plugins` WHERE `Key` = '{$key}' AND `Install` = '1' AND `Status` = 'active' LIMIT 1";
        $plugin = $GLOBALS['rlDb']->getRow($sql);
        $this->plugins[$key] = $plugin['ID'] ? true : false;
        return $this->plugins[$key];
    }

    /**
     * Get ccbill package item settings
     * 
     * @return []
     */
    public static function getSettingsItem()
    {
        global $rlPayment;

        $service = $rlPayment->getOption('service');
        switch ($service) {
            case 'featured':
            case 'package':
            case 'listing':
            case 'membership':
            case 'banners':
                $plan_id = $rlPayment->getOption('plan_id');
                break;

            case 'credits':
                $plan_id = $rlPayment->getOption('item_id');
                break;
        };
        $sql = "SELECT * FROM `" . RL_DBPREFIX . "ccbill_settings` WHERE `Item_ID` = '{$plan_id}' AND `service` = '{$service}' LIMIT 1";
        return $GLOBALS['rlDb']->getRow($sql);
    }

    /**
     * Save ccbill settings by object
     *
     * @param array $data
     */
    public function saveSettings(array $data)
    {
        global $rlActions;

        if (!$data) {
            return;
        }

        foreach ($data as $key => $value) {
            foreach ($value as $iKey => $iVal) {
                if (isset($this->object_items[$key]['items'][$iKey])) {
                    $service = $this->object_items[$key]['service']
                    ? $this->object_items[$key]['service']
                    : $this->object_items[$key]['items'][$iKey]['service'];

                    if ($GLOBALS['rlDb']->getOne('ID', "`Service` = '{$service}' AND `Item_ID` = '" . (int) $iKey . "'", 'ccbill_settings')) {
                        $update[] = array(
                            'fields' => array('Form' => $iVal['form'], 'Allowed_types' => $iVal['allowed_types']),
                            'where' => array('Service' => $service, 'Item_ID' => $iKey),
                        );
                    } else {
                        $insert[] = array(
                            'Service' => $service,
                            'Item_ID' => $iKey,
                            'Form' => $iVal['form'],
                            'Allowed_types' => $iVal['allowed_types'],
                        );
                    }
                }
            }
        }
        if ($update) {
            $rlActions->update($update, 'ccbill_settings');
        }
        if ($insert) {
            $rlActions->insert($insert, 'ccbill_settings');
        }
    }

    /**
     * Get ccbill settings
     *
     * @param array $object_items
     */
    public function getSettings(&$object_items)
    {
        $settings_tmp = $GLOBALS['rlDb']->getAll("SELECT * FROM `" . RL_DBPREFIX . "ccbill_settings`");

        foreach ($settings_tmp as $value) {
            $settings[$value['Service'] . '_' . $value['Item_ID']] = $value;
        }

        foreach ($object_items as $gKey => $group) {
            foreach ($group['items'] as $iKey => $item) {
                $service = $object_items[$gKey]['service'] ? $object_items[$gKey]['service'] : $item['Type'];
                if (isset($settings[$service . '_' . $item['ID']])) {
                    $object_items[$gKey]['items'][$iKey]['form'] = $settings[$service . '_' . $item['ID']]['Form'];
                    $object_items[$gKey]['items'][$iKey]['allowed_types'] = $settings[$service . '_' . $item['ID']]['Allowed_types'];
                }
            }
        }
    }

    /**
     * Set object items
     *
     * @param array $items
     */
    public function setObjectItems(array $items)
    {
        foreach ($items as $gKey => $gVal) {
            $tmp_items = array();
            if ($gVal['items']) {
                foreach ($gVal['items'] as $iKey => $iVal) {
                    if (!$gVal['service']) {
                        $iVal['service'] = $iVal['Type'];
                    }
                    $tmp_items[$iVal['ID']] = $iVal;
                }
                $items[$gKey]['items'] = $tmp_items;
            }
        }
        $this->object_items = $items;
    }

    /**
     * Get object items
     *
     * @return []
     */
    public function getObjectItems()
    {
        return $this->object_items;
    }
}
