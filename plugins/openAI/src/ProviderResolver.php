<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: PROVIDERRESOLVER.PHP
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

namespace Flynax\Plugins\OpenAI;

class ProviderResolver
{
    private $providers = [
        'openai' => 'OpenAI',
        'yandex' => 'YandexGPT',
        'deepseek' => 'DeepSeek'
    ];

    /**
     * Get provider object by available provider name
     *
     * @param $providerName - Provider Name
     * @return              - Object|bool
     */
    public function getProvider($providerName)
    {
        if (!$class_name = $this->providers[$providerName]) {
            return false;
        }

        $class = '\\Flynax\\Plugins\\OpenAI\\Providers\\' . $class_name;

        if (class_exists($class)) {
            $providerObject = new $class();

            if ($providerObject->isConfigured()) {
                return $providerObject;
            } else {
                return false;
            }
        }

        return false;
    }
}
