<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : USPS.PHP
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

namespace ShoppingCart\Shipping;

use ShoppingCart\Shipping;
use ShoppingCart\XMLParser;
use \Flynax\Utils\Util;

/**
 * @since 3.1.0
 */
class Usps extends ShippingMethod
{
    /**
     * Initialize method information
     */
    public function init()
    {
        $shipping = new Shipping();
        $this->methodInfo = $shipping->getMethod('usps');

        if ($this->methodInfo['Settings']) {
            $this->methodInfo['Settings'] = unserialize($this->methodInfo['Settings']);
        }

        $host = $this->methodInfo['Test_mode']
        ? 'http://production.shippingapis.com/ShippingAPI.dll'
        : 'https://production.shippingapis.com/ShippingAPI.dll';

        $this->setAPIHost($host);
        $this->loadStaticData();
    }

    /**
     * Get quote
     *
     * @param array $request
     * @param array $item
     *
     * @return array
     */
    public function getQuote($request = array(), $item = array())
    {
        $quote = array();

        if (!$this->getMethod()) {
            return $quote;
        }

        $method = $this->getMethod();

        // define service
        if (!empty($request['domestic_services']) && $request['country_to'] == 'US') {
            $service = $request['domestic_services'];
        } elseif (!empty($request['international_services'])) {
            $service = $request['international_services'];
        } else {
            $service = 'ALL';
        }

        // set request options
        $size = $item['shipping']['usps']['size'];
        $container = strtoupper($item['shipping']['usps']['container']);
        $mail_type = strtoupper($item['shipping']['usps']['mail_type']);
        $machinable = $item['shipping']['usps']['machinable'];
        $country = $request['country_to'];

        $this->setOption('userid', $method['Settings']['userid']['value']);
        $this->setOption('machinable', $machinable);
        $this->setOption('origination_postcode', $request['postcode_from']);
        $this->setOption('destination_postcode', $request['postcode_to']);
        $this->setOption('service', strtoupper($service));
        $this->setOption('mail_type', $mail_type);
        $this->setOption('size', $size);
        $this->setOption('pounds', floor($request['weight']));
        $this->setOption('ounces', ($request['weight'] - floor($request['weight'])));
        $this->setOption('container', $container);
        $this->setOption('width', $request['width']);
        $this->setOption('length', $request['length']);
        $this->setOption('height', $request['height']);
        $this->setOption('total', $request['total']);
        $this->setOption('country', $country);

        $APIVersion = $request['country_to'] == 'US' ? 'RateV4' : 'IntlRateV2';

        $request = 'API=' . $APIVersion . '&XML=' . urlencode($this->prepareXMLStructure());

        $response = XMLParser::parse($this->post($request));
        if (isset($response['Error'])) {
            $quote['error'] = $response['Error']['Description'];
            return $quote;
        }

        if (isset($response['RateV4Response'])) {
            $response = $response['RateV4Response']['Package'];

            if (!isset($response['Error'])) {
                if (isset($response['Postage']['Rate']) || isset($response['Postage']['CommercialRate'])) {
                    $quote = array(
                        'service' => $response['Postage']['MailService'],
                        'title' => $response['Postage']['MailService'],
                        'total' => (float) $response['Postage']['Rate'] > 0
                        ? (float) $response['Postage']['Rate']
                        : (float) $response['Postage']['CommercialRate'],
                        'currency' => 'USD',
                        'delivered' => '',
                    );
                } else {
                    foreach ($response['Postage'] as $qKey => $qVal) {
                        $quote[] = array(
                            'service' => $qVal['MailService'],
                            'title' => $qVal['MailService'],
                            'total' => $qVal['Rate'] > 0 ? (float) $qVal['Rate'] : (float) $qVal['CommercialRate'],
                            'currency' => 'USD',
                            'delivered' => '',
                        );
                    }
                }
            } else {
                $quote['error'] = $response['Error']['Description'] . ' (Error Number: ' . $response['Error']['Number'] . ')';
            }
        } elseif (isset($response['IntlRateV2Response'])) {
            if (!isset($response['IntlRateV2Response']['Package']['Error'])) {
                $response = $response['IntlRateV2Response']['Package']['Service'];
                foreach ($response as $rKey => $Value) {
                    $quote[] = array(
                        'service' => substr($Value['SvcDescription'], 0, strpos($Value['SvcDescription'], '&lt;')),
                        'title' => $Value['SvcDescription'],
                        'total' => (float) $Value['Postage'],
                        'currency' => 'USD',
                        'delivered' => '',
                    );
                }
            } else {
                $quote['error'] = $response['IntlRateV2Response']['Package']['Error']['Description'];
            }
        }

        return $quote;
    }

    /**
     * Send request to server
     *
     * @param mixed $request
     */
    public function post($request)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->apiHost . '?' . $request);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        if (strpos($this->apiHost, 'https://') === false) {
            curl_setopt($curl, CURLOPT_PORT, 80);
        }

        $response = curl_exec($curl);

        curl_close($curl);

        // strip reg, trade and ** out 01-02-2011
        $response = str_replace('&amp;lt;sup&amp;gt;&amp;amp;reg;&amp;lt;/sup&amp;gt;', '', $response);
        $response = str_replace('&amp;lt;sup&amp;gt;&amp;amp;trade;&amp;lt;/sup&amp;gt;', '', $response);
        $response = str_replace('**', '', $response);
        $response = str_replace("\r\n", '', $response);
        $response = str_replace('\"', '"', $response);

        return $response;
    }

    /**
     * Prepare data of form fields
     */
    public function prepareFields()
    {
        self::loadStaticData();
    }

    /**
     * Prepare XML Structure for request
     */
    public function prepareXMLStructure()
    {
        if ($this->getOption('country') == 'US') {
            $xml = '<RateV4Request USERID="' . $this->getOption('userid') . '">' . PHP_EOL;
            $xml .= '      <Revision/>' . PHP_EOL;
            $xml .= '    <Package ID="1ST">' . PHP_EOL;
            $xml .= '        <Service>' . $this->getOption('service') . '</Service>' . PHP_EOL;
            if (substr_count($this->getOption('service'), 'FIRST CLASS') > 0) {
                $xml .= '    <FirstClassMailType>' . $this->getOption('mail_type') . '</FirstClassMailType>' . PHP_EOL;
            }
            $xml .= '        <ZipOrigination>' . substr($this->getOption('origination_postcode'), 0, 5) . '</ZipOrigination>' . PHP_EOL;
            $xml .= '        <ZipDestination>' . substr($this->getOption('destination_postcode'), 0, 5) . '</ZipDestination>' . PHP_EOL;
            $xml .= '        <Pounds>' . $this->getOption('pounds') . '</Pounds>' . PHP_EOL;
            $xml .= '        <Ounces>' . $this->getOption('ounces') . '</Ounces>' . PHP_EOL;

            // Prevent common size mismatch error from USPS (Size cannot be Regular if Container is Rectangular for some reason)
            if ($this->getOption('container') == 'RECTANGULAR' && $this->getOption('size') == 'REGULAR') {
                $this->setOption('container', 'VARIABLE');
            }

            $xml .= '        <Container>' . $this->getOption('container') . '</Container>' . PHP_EOL;
            $xml .= '        <Size>' . $this->getOption('size') . '</Size>' . PHP_EOL;
            $xml .= '        <Width>' . $this->getOption('width') . '</Width>' . PHP_EOL;
            $xml .= '        <Length>' . $this->getOption('length') . '</Length>' . PHP_EOL;
            $xml .= '        <Height>' . $this->getOption('height') . '</Height>' . PHP_EOL;

            // Calculate girth based on usps calculation
            $xml .= '        <Girth>' . (round(((float) $this->getOption('length') + (float) $this->getOption('width') * 2 + (float) $this->getOption('height') * 2), 1)) . '</Girth>' . PHP_EOL;

            $xml .= '        <Machinable>' . ($this->getOption('machinable') ? 'true' : 'false') . '</Machinable>' . PHP_EOL;
            $xml .= '    </Package>' . PHP_EOL;
            $xml .= '</RateV4Request>' . PHP_EOL;
        } else {
            $xml = '<IntlRateV2Request USERID="' . $this->getOption('userid') . '">' . PHP_EOL;
            $xml .= '    <Package ID="1">' . PHP_EOL;
            $xml .= '        <Pounds>' . $this->getOption('pounds') . '</Pounds>' . PHP_EOL;
            $xml .= '        <Ounces>' . $this->getOption('ounces') . '</Ounces>' . PHP_EOL;
            $xml .= '        <MailType>All</MailType>' . PHP_EOL;
            $xml .= '        <GXG>' . PHP_EOL;
            $xml .= '          <POBoxFlag>N</POBoxFlag>' . PHP_EOL;
            $xml .= '          <GiftFlag>N</GiftFlag>' . PHP_EOL;
            $xml .= '        </GXG>' . PHP_EOL;
            $xml .= '        <ValueOfContents>' . $this->getOption('total') . '</ValueOfContents>' . PHP_EOL;
            $xml .= '        <Country>' . $this->getOption('country') . '</Country>' . PHP_EOL;

            // Intl only supports RECT and NONRECT
            if ($this->getOption('container') == 'VARIABLE') {
                $this->setOption('container', 'NONRECTANGULAR');
            }

            $xml .= '        <Container>' . $this->getOption('container') . '</Container>' . PHP_EOL;
            $xml .= '        <Size>' . $this->getOption('size') . '</Size>' . PHP_EOL;
            $xml .= '        <Width>' . $this->getOption('width') . '</Width>' . PHP_EOL;
            $xml .= '        <Length>' . $this->getOption('length') . '</Length>' . PHP_EOL;
            $xml .= '        <Height>' . $this->getOption('height') . '</Height>' . PHP_EOL;
            $xml .= '        <Girth>' . (round(((float) $this->getOption('length') + (float) $this->getOption('width') * 2 + (float) $this->getOption('height') * 2), 1)) . '</Girth>' . PHP_EOL;
            $xml .= '        <CommercialFlag>N</CommercialFlag>' . PHP_EOL;
            $xml .= '    </Package>' . PHP_EOL;
            $xml .= '</IntlRateV2Request>' . PHP_EOL;
        }

        return $xml;
    }

    /**
     * Check if configured this method
     *
     * @return bool
     */
    public function isConfigured()
    {
        if ($this->methodInfo['Settings']['userid']['value']) {
            return true;
        }

        return false;
    }

    /**
     * Load static data
     */
    public static function loadStaticData()
    {
        global $rlSmarty, $uspsContainers, $uspsServicesDomestic,
        $uspsServicesInternational, $uspsMailTypesDomestic, $uspsMailTypesInternational;

        require_once RL_PLUGINS . 'shoppingCart/shipping/usps/static.inc.php';

        if ($rlSmarty) {
            $rlSmarty->assign_by_ref('shc_usps_containers', $uspsContainers);
            $rlSmarty->assign_by_ref('shc_usps_domestic_services', $uspsServicesDomestic);
            $rlSmarty->assign_by_ref('shc_usps_international_services', $uspsServicesInternational);
            $rlSmarty->assign_by_ref('shc_usps_mail_types_domestic', $uspsMailTypesDomestic);
            $rlSmarty->assign_by_ref('shc_usps_mail_types', $uspsMailTypesInternational);
        }
    }
}
