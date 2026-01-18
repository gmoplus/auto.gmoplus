<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: RLAUTOPOSTER.CLASS.PHP
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

namespace Autoposter;

use Autoposter\Providers;

class ProviderController
{
    /**
     * @var \rlDb
     */
    private $rlDb;

    /**
     * @var \rlValid
     */
    private $rlValid;

    /**
     * @var string - Current active provider
     */
    private $provider;

    /**
     * @var array - List of all available providers
     */
    private $available_providers;

    /**
     * ProviderController constructor.
     *
     * @param string $provider_name - Set provider as active for this instance
     */
    public function __construct($provider_name)
    {
        $this->rlDb = AutoPosterContainer::getObject('rlDb');
        $this->rlValid = AutoPosterContainer::getObject('rlValid');
        $this->available_providers = array_keys(AutoPosterContainer::getConfig('configs')['modules']);
        return $this->setProvider($provider_name) ? true : false;
    }

    /**
     * Provider setter
     *
     * @param  string $name - Provider name: {facebook, twitter}
     * @return bool         -
     */
    public function setProvider($name)
    {
        $provider = strtolower($name);
        if (!in_array($provider, $this->available_providers)) {
            return false;
        }
        $class = '\\Autoposter\\Providers\\' . ucfirst($provider);

        return class_exists($class) ? $this->provider = new $class() : false;
    }

    /**
     * Provider getter
     *
     * @return object|false - Active provider
     */
    public function getProvider()
    {
        return $this->provider;
    }
}
