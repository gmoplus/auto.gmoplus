<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: RLMICRODATA.CLASS.PHP
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

class rlMicrodata
{

    /**
     * @var microdata info
     **/
    public $microdata = [];

    /**
     * @var microdata error info
     **/
    public $microdataErrors = [];

    /**
     * @var Currency
     **/
    public $currency = [
        'dollar' => 'USD',
        'pound' => 'GBP',
        'euro' => 'EUR',
        'currency_rub' => 'RUB',
    ];

    /**
     * Plugin installer
     **/
    public function install()
    {
        $GLOBALS['rlDb']->addColumnToTable('Microdata', "ENUM( '1', '0' ) DEFAULT '1' NOT NULL", 'listing_types');
    }

    /**
     * Plugin un-installer
     **/
    public function uninstall()
    {
        $GLOBALS['rlDb']->dropColumnFromTable('Microdata', 'listing_types');
    }

    /**
     * @hook tplHeader
     **/
    public function hookTplHeader()
    {
        global $page_info;

        $this->microdataLogo();
        $this->microdataBreadCrumb();

        switch ($page_info['Controller']) {
            case 'faqs':
                $this->microdataFAQs();
                break;

            case 'news':
                $this->microdataNews();
                break;

            case 'listing_details':
                $this->microdataListingDetails();
                break;

            case 'listing_type':
                $this->microdataListingType();
                break;
        }

        $GLOBALS['rlSmarty']->assign_by_ref('microdata', json_encode($this->microdata));
        $GLOBALS['rlSmarty']->assign_by_ref('microdataErrors', $this->microdataErrors);

        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'microdata' . RL_DS . 'box.tpl');

    }

    /**
     * @hook apTplListingTypesForm
     */
    public function hookApTplListingTypesForm()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'microdata' . RL_DS . 'enable_box.tpl');
    }

    /**
     * @hook apPhpListingTypesPost
     */
    public function hookApPhpListingTypesPost()
    {
        $_POST['Microdata'] = $GLOBALS['type_info']['Microdata'];
    }

    /**
     * @hook apPhpListingTypesBeforeAdd
     */
    public function hookApPhpListingTypesBeforeAdd()
    {
        $GLOBALS['data']['Microdata'] = $_POST['Microdata'];
    }

    /**
     * @hook apPhpListingTypesBeforeEdit
     */
    public function hookApPhpListingTypesBeforeEdit()
    {
        $GLOBALS['update_date']['fields']['Microdata'] = $_POST['Microdata'];
    }

    /**
     * @hook listingDetailsSql
     *
     * @param string $sql - sql code
     */
    public function hookListingDetailsSql(&$sql)
    {
        $sql = str_replace('`T2`.`Type` AS `Listing_type`,', '`T2`.`Type` AS `Listing_type`, `T3`.`Listing_period`,', $sql);
    }

    /**
     * Adapt microdata for logo
     **/
    private function microdataLogo()
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'url' => RL_URL_HOME,
            'logo' => RL_TPL_BASE . 'img' . RL_DS . 'logo.png',
        ];
        $this->microdata[] = $data;
    }

    /**
     * Adapt microdata for BreadCrumbs
     **/
    private function microdataBreadCrumb()
    {
        global $bread_crumbs;

        if ($GLOBALS['page_info']['Key'] == 'home') {
            return;
        }

        $items = [];
        // Remove last item
        $tmp = $bread_crumbs;
        unset($tmp[count($tmp) - 1]);

        foreach ($tmp as $key => $value) {
            if (!$value['name']) {
                continue;
            }

            $items[$key] = [
                '@type' => 'ListItem',
                'position' => $key + 1,
                'name' => $value['name'],
            ];

            // Category
            if ($value['ID']) {
                $items[$key]['item'] = $GLOBALS['reefless']->getCategoryUrl($value['ID']);
            } else {
                $items[$key]['item'] = $GLOBALS['reefless']->getPageUrl(
                    array_search($value['path'], $GLOBALS['pages']),
                    false,
                    false,
                    $value['vars'] ?: false
                );
            }
        }

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];

        $this->microdata[] = $data;
    }

    /**
     * Adapt microdata for news
     **/
    private function microdataNews()
    {
        if ($GLOBALS['article_id']) {
            $article = $GLOBALS['article'];
            $data = [
                '@context' => 'https://schema.org',
                '@type' => 'NewsArticle',
                // 'image' => [""],
                'headline' => mb_strlen($article['title']) < 110 ? $article['title'] : mb_substr($article['title'], 0, 110), // allow 110 symbol
                'datePublished' => date('c', strtotime($article['Date'])), // convert to ISO 8601.
                'author' => [
                    '@type' => 'Person',
                    'name' => $GLOBALS['lang']['pages+title+home'],
                    'url' => RL_URL_HOME,
                ],
            ];

            $this->microdata[] = $data;
        }
    }

    /**
     * Adapt microdata for faqs
     **/
    private function microdataFAQs()
    {
        if ($GLOBALS['faqs_id']) {
            $article = $GLOBALS['faqs'];
            $data = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => [
                    '@type' => 'Question',
                    'name' => $article['title'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => strip_tags($article['content']),
                    ],
                ],
            ];
        } else {
            $all_faqs = $GLOBALS['all_faqs'];
            $faqs = [];
            foreach ($all_faqs as $item) {
                $faqs[] = [
                    '@type' => 'Question',
                    'name' => $item['title'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => strip_tags($item['content']),
                    ],
                ];
            }
            $data = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $faqs,
            ];
        }
        $this->microdata[] = $data;
    }

    /**
     * Adapt microdata for listings on listing type page
     **/
    private function microdataListingType()
    {
        if ($GLOBALS['listings']) {
            $listings = [];
            foreach ($GLOBALS['listings'] as $key => $item) {
                $listings[] = [
                    '@type' => 'ListItem',
                    'position' => $key + 1,
                    'url' => $item['url'],
                ];
            }
            $data = [
                '@context' => 'https://schema.org',
                '@type' => 'ItemList',
                'itemListElement' => $listings,
            ];
            $this->microdata[] = $data;
        }
    }

    /**
     * Adapt microdata for listing
     **/
    private function microdataListingDetails()
    {
        global $listing_data, $config;
        if ($listing_data['Status'] == 'active' && $GLOBALS['listing_type']['Microdata']) {
            if ($GLOBALS['plugins']['events'] && $GLOBALS['listing_type']['Key'] == $config['event_type_key']) {
                $this->getEventsDetails();
            } elseif (is_numeric(strpos($GLOBALS['listing_type']['Key'], 'job'))) {
                $this->getJobDetails();
            } else {
                $this->getProductDetails();
            }
        }
    }

    /**
     * Adapt microdata for Events
     **/
    private function getEventsDetails()
    {
        global $listing_data, $config;

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => strip_tags($GLOBALS['listing_title']),
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'organizer' => [
                '@type' => 'Organization',
                'name' => $GLOBALS['seller_info']['Full_name'],
                'url' => $GLOBALS['seller_info']['Personal_address'],
            ],
        ];

        // Set date for event
        if ($listing_data['event_type'] == '1') {
            $data['startDate'] = $listing_data['event_date'];
            $data['endDate'] = $listing_data['event_date'];
        } else if ($listing_data['event_type'] == '2') {
            $data['startDate'] = $listing_data['event_date'];
            $data['endDate'] = $listing_data['event_date_multi'];
        }

        // Build photos
        if ($GLOBALS['media']) {
            $photos = [];
            foreach ($GLOBALS['media'] as $photo) {
                if ($photo['Type'] == 'picture') {
                    $photos[] = $photo['Photo'];
                }
            }
            if ($photos) {
                $data['image'] = $photos;
            }
        }

        // Set Description
        if ($GLOBALS['listing']) {
            foreach ($GLOBALS['listing'] as $group) {
                foreach ($group['Fields'] as $item) {
                    if ($item['Type'] == 'textarea' && $item['value']) {
                        $data['description'] = strip_tags($item['value']);
                        break 2;
                    }
                }
            }
        }

        // Set price
        $price = 0;
        $currency = $currency_key = '';
        if ($config['price_tag_field']) {
            $price_data = explode('|', $listing_data[$config['price_tag_field']]);
            $price = $price_data[0] ?: $price;
            $currency_key = $price_data[1];

            if ($this->currency[$currency_key]) {
                $currency = $this->currency[$currency_key];
            } else {
                $currency = strtoupper(str_replace('currency_', '', $currency_key));
            }
        }

        // Set offer
        $data['offers'] = [
            '@type' => 'Offer',
            'url' => $GLOBALS['reefless']->getListingUrl($listing_data),
            'priceCurrency' => $currency,
            'price' => $price,
            'validFrom' => $listing_data['event_date'],
            'availability' => 'https://schema.org/InStock',
        ];

        // Set location
        if ($GLOBALS['location']['search']) {
            $data['location'] = [
                '@type' => 'Place',
                'name' => $GLOBALS['location']['search'],
                'address' => [
                    '@type' => 'PostalAddress',
                    '@name' => $GLOBALS['location']['search'],
                ],
            ];
        } else {
            $this->microdataErrors[] = 'Events - there is no location, therefore it is not used';
        }

        if (!$this->microdataErrors) {
            $this->microdata[] = $data;
        }
    }

    /**
     * Adapt microdata for Job
     **/
    private function getJobDetails()
    {
        global $listing_data, $config;

        $job_type = [
            'operating_schedule_full_time' => 'FULL_TIME',
            'operating_schedule_part_time' => 'PART_TIME',
            'operating_schedule_contractor' => 'CONTRACTOR',
            'operating_schedule_intern' => 'INTERN',
            'operating_schedule_temprorary' => 'TEMPORARY',
            'operating_schedule_volunteer' => 'VOLUNTEER',
            'operating_schedule_per_diem' => 'PER_DIEM',
            'other' => 'OTHER',
        ];

        $days = $listing_data['Listing_period'] > 0 ? $listing_data['Listing_period'] : 356;
        $expired_date = date('Y-m-d', strtotime($listing_data['Pay_date'] . ' +' . $days . ' day'));

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'JobPosting',
            'title' => strip_tags($GLOBALS['listing_title']),
            'employmentType' => $job_type[$listing_data['operating_schedule']] ?: $job_type['other'],
            'datePosted' => $listing_data['Date'],
            'validThrough' => $expired_date,
        ];

        $description = 'n/a';
        // Set Description
        if ($GLOBALS['listing']) {
            foreach ($GLOBALS['listing'] as $group) {
                foreach ($group['Fields'] as $item) {
                    if ($item['Type'] == 'textarea' && $item['value']) {
                        $description = strip_tags($item['value']);
                        break 2;
                    }
                }
            }
        }
        $data['description'] = $description;

        // Set price
        $price = 0;
        $currency = $currency_key = '';
        if ($listing_data['salary']) {
            $price_data = explode('|', $listing_data['salary']);
            $price = $price_data[0] ?: $price;
            $currency_key = $price_data[1];

            if ($this->currency[$currency_key]) {
                $currency = $this->currency[$currency_key];
            } else {
                $currency = strtoupper(str_replace('currency_', '', $currency_key));
            }

            $per = [
                'pay_period_week' => 'WEEK',
                'pay_period_month' => 'MONTH',
                'pay_period_hour' => 'HOUR',
                'pay_period_day' => 'DAY',
                'pay_period_annum' => 'YEAR',
            ];

            $data['baseSalary'] = [
                '@type' => 'MonetaryAmount',
                'name' => $GLOBALS['location']['search'],
                'currency' => $currency,
                'value' => [
                    '@type' => 'QuantitativeValue',
                    'value' => $price,
                    'unitText' => $per[$listing_data['pay_period']],
                ],
            ];
        }

        // Set location
        if ($GLOBALS['location']['search']) {
            $data['jobLocation'] = [
                '@type' => 'Place',
                'name' => $GLOBALS['location']['search'],
                'address' => [
                    '@type' => 'PostalAddress',
                    '@name' => $GLOBALS['location']['search'],
                ],
            ];
        } else {
            $this->microdataErrors[] = 'Jobs - there is no location, therefore it is not used';
        }

        $GLOBALS['seller_info'];

        $data['hiringOrganization'] = [
            '@type' => 'Organization',
            'name' => $GLOBALS['seller_info']['company_name'] ?: $GLOBALS['seller_info']['Full_name'],

        ];
        foreach ($GLOBALS['seller_info']['Fields'] as $field) {
            if ($field['Type'] == 'text' && $field['Condition'] == 'isUrl') {
                $data['hiringOrganization']['sameAs'] = $field['value'];
            }
        }

        if (!$this->microdataErrors) {
            $this->microdata[] = $data;
        }
    }

    /**
     * Adapt microdata for Product
     **/
    private function getProductDetails()
    {
        global $listing_data, $config;

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'sku' => $listing_data['ID'],
            'mpn' => $listing_data['ID'],
            'name' => strip_tags($GLOBALS['listing_title']),
        ];

        // Build category names
        if ($GLOBALS['cat_bread_crumbs']) {
            $category_name = '';
            foreach ($GLOBALS['cat_bread_crumbs'] as $cat) {
                $category_name .= $category_name ? ' ' . $cat['name'] : $cat['name'];
            }

            if ($category_name) {
                $data['brand'] = [
                    '@type' => 'Brand',
                    'name' => $category_name,
                ];
            }
        }

        // Build photos
        if ($GLOBALS['media']) {
            $photos = [];
            foreach ($GLOBALS['media'] as $photo) {
                if ($photo['Type'] == 'picture') {
                    $photos[] = $photo['Photo'];
                }
            }
            if ($photos) {
                $data['image'] = $photos;
            }
        }

        // Set Description
        if ($GLOBALS['listing']) {
            foreach ($GLOBALS['listing'] as $group) {
                foreach ($group['Fields'] as $item) {
                    if ($item['Type'] == 'textarea' && $item['value']) {
                        $data['description'] = strip_tags($item['value']);
                        break 2;
                    }
                }
            }
        }

        // Set price
        $price = 0;
        $currency = $currency_key = '';
        if ($config['price_tag_field']) {
            $price_data = explode('|', $listing_data[$config['price_tag_field']]);
            $price = $price_data[0] ?: $price;
            $currency_key = $price_data[1];

            if ($this->currency[$currency_key]) {
                $currency = $this->currency[$currency_key];
            } else {
                $currency = strtoupper(str_replace('currency_', '', $currency_key));
            }
        }

        $days = $listing_data['Listing_period'] > 0 ? $listing_data['Listing_period'] : 356;
        $expired_date = date('Y-m-d', strtotime($listing_data['Pay_date'] . ' +' . $days . ' day'));

        // Set offer
        $data['offers'] = [
            '@type' => 'Offer',
            'url' => $GLOBALS['reefless']->getListingUrl($listing_data),
            'priceCurrency' => $currency,
            'price' => $price,
            'priceValidUntil' => $expired_date,
            'availability' => 'https://schema.org/InStock',
        ];

        // Set Rating and comments
        if ($GLOBALS['plugins']['comment'] && $listing_data['comments_count']) {
            $comments = $this->getComments($listing_data['ID']);
            if ($comments) {
                $data['aggregateRating'] = [
                    '@type' => 'AggregateRating',
                    'ratingValue' => $comments['avg'],
                    'reviewCount' => $comments['count'],
                ];

                $review = [];
                foreach ($comments['comments'] as $comment) {
                    $review[] = [
                        '@type' => 'Review',
                        'author' => [
                            '@type' => 'Person',
                            'name' => $comment['Author'],
                        ],
                        'datePublished' => $comment['Date'],
                        'description' => strip_tags($comment['Description']),
                        'name' => strip_tags($comment['Title']),
                        'reviewRating' => [
                            '@type' => 'Rating',
                            'ratingValue' => $comment['Rating'],
                            'worstRating' => '1',
                        ],
                    ];
                }
                $data['review'] = $review;
            }
        }
        $this->microdata[] = $data;
    }

    // Get comments
    private function getComments($listing_id)
    {
        if (!$comments = $GLOBALS['rlSmarty']->_tpl_vars['comments']) {
            $comments = $GLOBALS['rlDb']->fetch(
                '*',
                array('Listing_ID' => $listing_id, 'Status' => 'active'),
                " ORDER BY `Date` DESC ",
                null,
                'comments'
            );
        }

        $avg = [];
        if ($comments) {
            $sum = 0;
            foreach ($comments as $key => $comment) {
                if ($comment['Rating']) {
                    $comments[$key]['Description'] = preg_replace('/(https?\:\/\/[^\s]+)/', '<a href="$1">$1</a>', $comment['Description']);

                    $sum += $comment['Rating'];
                } else {
                    unset($comments[$key]);
                }
            }

            if ($comments) {
                $avg['count'] = count($comments);
                $avg['sum'] = $sum;
                if ($avg['sum'] > 0 && $avg['count']) {
                    $avg['avg'] = number_format($avg['sum'] / $avg['count'], 1);
                }

                $avg['comments'] = $comments;
            }
        }

        return $avg;
    }
}
