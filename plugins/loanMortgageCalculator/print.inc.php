<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: LOAN_CALC.JS
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

$rlSmarty->register_function('rlHook', array('rlHook', 'load'));

/* send headers */
header("Content-Type: text/html; charset=utf-8");
header("Cache-Control: store, no-cache, max-age=3600, must-revalidate");

$listing_id = (int) $_GET['id'];

$loan_amount = $rlValid -> str2money($_GET['amount']);
$loan_term = $_GET['term'];
$loan_term_mode = $_GET['term_mode'];
$loan_rate = (int) $_GET['rate'];
$price_mode = $_GET['mode'];
$month = $_GET['date_month'];
$year = $_GET['date_year'];

if ( !$listing_id ) {
	$errors[] = $lang['loanMortgage_listing_unavailable'];
}

$rlSmarty->assign('upload_max_size', \Flynax\Utils\Util::getMaxFileUploadSize());

if ( !$errors ) {
	
	/* get listing info */
	$sql = "SELECT `T1`.*, `T2`.`Path`, `T2`.`Type` AS `Listing_type`, `T2`.`Key` AS `Cat_key`, `T2`.`Type` AS `Cat_type`, ";
	$sql .= "`T3`.`Image`, `T3`.`Image_unlim`, `T3`.`Video`, `T3`.`Video_unlim`, CONCAT('categories+name+', `T2`.`Key`) AS `Category_pName`, ";
	$sql .= "IF ( UNIX_TIMESTAMP(DATE_ADD(`T1`.`Pay_date`, INTERVAL `T3`.`Listing_period` DAY)) <= UNIX_TIMESTAMP(NOW()) AND `T3`.`Listing_period` > 0, 1, 0) AS `Listing_expired` ";
	$sql .= "FROM `" . RL_DBPREFIX . "listings` AS `T1` ";
	$sql .= "LEFT JOIN `" . RL_DBPREFIX . "categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
	$sql .= "LEFT JOIN `" . RL_DBPREFIX . "listing_plans` AS `T3` ON `T1`.`Plan_ID` = `T3`.`ID` ";
	$sql .= "LEFT JOIN `" . RL_DBPREFIX . "accounts` AS `T5` ON `T1`.`Account_ID` = `T5`.`ID` ";
	$sql .= "WHERE `T1`.`ID` = '{$listing_id}' AND `T5`.`Status` = 'active' ";
	
	$rlHook -> load('listingDetailsSql', $sql);
	
	$sql .= "LIMIT 1";
	
	$listing_data = $rlDb -> getRow($sql);
	
	if ( $listing_data['Listing_expired'] ) {
		$errors[] = $lang['error_listing_expired'];
	}
	
	if ( !$listing_data ) {
		$errors[] = $lang['loanMortgage_listing_unavailable'];
	}
	
	if ( !$errors ) {
		$reefless -> loadClass('Listings');
		$reefless -> loadClass('Common');
		$reefless -> loadClass('Account');
		
		/* define listing type */
		$listing_type = $rlListingTypes -> types[$listing_data['Listing_type']];
		$rlSmarty -> assign_by_ref('listing_type', $listing_type);
		
		/* get main picture */
		if ( $listing_type['Photo'] && $listing_data['Main_photo'] ) {
			$main_photo = $rlDb -> getOne('Photo', "`Listing_ID` = {$listing_id} AND `Thumbnail` = '{$listing_data['Main_photo']}'", 'listing_photos');
			$rlSmarty -> assign_by_ref('main_photo', $main_photo);
		}
		
        /* collect load terms */
        $currency_exp = explode('|', $listing_data[$config['price_tag_field']]);
        $currency_exp[1] = $lang['data_formats+name+' . $currency_exp[1]];

        // Currency converter mode
        if ($price_mode == 'converted') {
            if (method_exists($rlCurrencyConverter, 'getRates')) {
                $rates = $rlCurrencyConverter->getRates();
            } else {
                if ($code = $rlDb->getOne('Code', "`Name` = 'specialBlock' AND `Plugin` = 'currencyConverter'", 'hooks')) {
                    eval($code);
                    $rates = $rlCurrencyConverter->rates;
                }
            }

            if ($rates) {
                $currency_exp[1] = $rates[$rlSmarty->_tpl_vars['curConv_code']]['Code'];
            }
        }

        $rlSmarty -> assign_by_ref('currency_exp', $currency_exp);

        $set_amount = strlen($currency_exp[1]) == 3
            ? $loan_amount . ' '. $currency_exp[1]
            : $currency_exp[1] .' ' . $loan_amount;

        $loan_terms = array(
            array(
                'name' => $lang['loanMortgage_loan_amount'],
                'value' => $set_amount
            ),
            array(
                'name' => $lang['loanMortgage_loan_term'],
                'value' => $loan_term.' '.$lang['loanMortgage_'. $loan_term_mode .'s']
            ),
            array(
                'name' => $lang['loanMortgage_interest_rate'],
                'value' => $loan_rate .'%'
            ),
            array(
                'name' => $lang['loanMortgage_first_pmt_date'],
                'value' => $month .' '. $year
            )
        );
        $rlSmarty -> assign_by_ref('loan_terms', $loan_terms);

		/* get listing title */
		$listing_title = $rlListings -> getListingTitle($listing_data['Category_ID'], $listing_data, $listing_type['Key']);
		$rlSmarty -> assign_by_ref('listing_title', $listing_title);
		
		$short_form_fields = $rlListings -> getFormFields($listing_data['Category_ID'], 'short_forms', $listing_type['Key']);
		foreach ($short_form_fields as $key => $field) {
			$listing_short[$key]['name'] = $field['name'];
			if (in_array($field['Condition'], array('isUrl', 'isEmail'))) {
				$listing_short[$key]['value'] = $listing_data[$key];
			} elseif ($field['Key'] == $config['price_tag_field']) {
                $listing_short[$key]['value'] = $set_amount;
            } else {
				$listing_short[$key]['value'] = $rlCommon->adaptValue($field, $listing_data[$key], 'listing', $listing_id);
			}
		}
		unset($short_form_fields);
		$rlSmarty -> assign_by_ref('listing_short', $listing_short);
		
		/* get seller info */
		$sql = "SELECT * FROM `". RL_DBPREFIX ."accounts` ";
		$sql .= "WHERE `ID` = {$listing_data['Account_ID']} ";
		$seller_data = $rlDb -> getRow($sql);
		
		$account_type_id = $rlDb -> getOne('ID', "`Key` = '{$seller_data['Type']}'", 'account_types');
		$seller_fields = $rlAccount -> getFormFields($account_type_id);
		
		foreach ($seller_fields as $key => $field) {
			$seller_short[$key]['name'] = $lang[$field['pName']];
			if (in_array($field['Condition'], array('isUrl', 'isEmail'))) {
				$seller_short[$key]['value'] = $seller_data[$key];
			} else {
				$seller_short[$key]['value'] = $rlCommon->adaptValue($field, $seller_data[$key], 'account', $seller_data['ID']);
			}
		}
		unset($seller_fields);
		$rlSmarty -> assign_by_ref('seller_short', $seller_short);

		$seller_info = $rlAccount -> getProfile((int) $listing_data['Account_ID']);
		$rlSmarty -> assign_by_ref('seller_info', $seller_info);
	}
}
