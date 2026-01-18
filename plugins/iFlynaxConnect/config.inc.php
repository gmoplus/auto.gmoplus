<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : CONFIG.INC.PHP
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

// iphone router paths
define('RL_IPHONE_CONTROLLERS', RL_PLUGINS . 'iFlynaxConnect' . RL_DS . 'controllers' . RL_DS);
define('RL_IPHONE_GATEWAYS', RL_PLUGINS . 'iFlynaxConnect' . RL_DS . 'gateways' . RL_DS);

/**
 * iOS App trigger
 *
 * @since 3.0.0
 */
define('IOS_APP', true);

/**
 * @since 3.0.0 - deprecated
 */
define('APP_USE_GZIP', false);
define('APP_SHORT_FORM_FIELDS_LIMIT', 5);
define('APP_FEATUREDS_FIELDS_LIMIT', 3);

/**
 * @since 3.1.0 - deprecated
 */
define('RL_IPHONE_CLASSES', '');
