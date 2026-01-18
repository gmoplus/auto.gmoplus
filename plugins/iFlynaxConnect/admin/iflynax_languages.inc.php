<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : IFLYNAX_LANGUAGES.INC.PHP
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

if ( isset($_GET['q']) )
{
	// load system config
	require_once('../../../includes/config.inc.php');
	require_once(RL_ADMIN_CONTROL .'ext_header.inc.php');

	if ( $_GET['action'] == 'update' )
	{
		$reefless -> loadClass( 'Actions' );

		$lang_code = $rlValid -> xSql($_GET['lang_code']);
		$value = $rlValid -> xSql(nl2br($_GET['value']));
		$field = $rlValid -> xSql($_GET['field']);
		$type = $rlValid -> xSql($_GET['type']);
		$key = $rlValid -> xSql($_GET['key']);
		$id = (int)$_GET['id'];

		// update
		$updateData = array(
			'fields' => array(
				$field => $value
			),
			'where' => array(
				'ID' => $id
			)
		);
	}
	else
	{
		$limit = (int)$_GET['limit'];
		$start = (int)$_GET['start'];
	}
}

if ($_GET['q'] == 'add_phrase') {
    $phrase_key = $rlValid->xSql($_REQUEST['key']);
    $phrase_code = $rlValid->xSql($_REQUEST['code']);

    $sql = "
        SELECT CONCAT(`Key`, '_', `Code`) AS `key_code` 
        FROM `" . RL_DBPREFIX . "iflynax_phrases` WHERE `Key` = '{$phrase_key}'
    ";
    $exists_phrases = $rlDb->getAll($sql, array(false, 'key_code'));

    if ($phrase_code == 'all') {
        $iflynax_languages = $rlDb->getAll("SELECT `Code` FROM `" . RL_DBPREFIX . "iflynax_languages`");
    } else {
        $iflynax_languages[] = array('Code' => $phrase_code);
    }

    $new_phrases = array();
    foreach ($iflynax_languages as $language) {
        $check_key = sprintf('%s_%s', $phrase_key, $language['Code']);

        if (in_array($check_key, $exists_phrases)) {
            continue;
        }

        $new_phrases[] = array(
            'Key' => $phrase_key,
            'Code' => $language['Code'],
            'Value' => $_REQUEST['value']
        );
    }

    $success = false;
    if (!empty($new_phrases)) {
        $reefless->loadClass('Actions');

        $success = $rlActions->insert($new_phrases, 'iflynax_phrases');
    }

    echo json_encode(['success' => $success]);
    exit;
}
elseif ( $_GET['q'] == 'ext_list')
{
	if ( $_GET['action'] == 'update' ) {
		$rlActions -> updateOne($updateData, 'iflynax_languages');
		exit;
	}

	$sql  = "SELECT SQL_CALC_FOUND_ROWS DISTINCT COUNT(`T2`.`ID`) AS `Number`, `T1`.* FROM `". RL_DBPREFIX ."iflynax_languages` AS `T1` ";
	$sql .= "LEFT JOIN `". RL_DBPREFIX ."iflynax_phrases` AS `T2` ON `T1`.`Code` = `T2`.`Code` ";
	$sql .= "GROUP BY `T2`.`Code` ORDER BY `ID` ";
	$sql .= "LIMIT {$start}, {$limit}";

	$data = $rlDb -> getAll($sql);
	$count = $rlDb -> getRow("SELECT FOUND_ROWS() AS `count`");

	foreach ( $data as $key => $value )
	{
		$is_current = $config['iflynax_lang'] == $value['Code'] ? 'true' : 'false';
		$data[$key]['Status'] = $GLOBALS['lang'][$value['Status']];
		$data[$key]['Data'] = $value['ID'] .'|'. $is_current;
		$data[$key]['Direction'] = $GLOBALS['lang'][$value['Direction'] .'_direction_title'];
		$data[$key]['name'] = $rlDb -> getOne('Value', "`Key` = '{$value['Key']}' AND `Code` = '{$value['Code']}'", 'iflynax_phrases');

		if ( $value['Code'] == $config['iflynax_lang'] ) {
			$data[$key]['name'] .= ' <b>('.$lang['default'].')</b>';
		}
		else {
			$data[$key]['name'] .= ' | <a class="green_11_bg" href="javascript:void(0)" onclick="xajax_setDefault( \'langs_container\', \''. $value['Code'] .'\' );"><b>'. $lang['set_default'] .'</b></a>';
		}
	}

	$output['total'] = $count['count'];
	$output['data'] = $data;

	echo json_encode($output);
}
elseif ($_GET['q'] == 'ext')
{
	// date update
	if ( $_GET['action'] == 'update' )
	{
		$updateData['fields'][$field] = trim($value, PHP_EOL);
		$rlActions -> updateOne($updateData, 'iflynax_phrases');
		exit;
	}

	// data read
	$sort = $rlValid -> xSql($_GET['sort']);
	$sort = $sort ? $sort : 'Value';
	$sortDir = $rlValid -> xSql($_GET['dir']);
	$sortDir = $sortDir ? $sortDir : 'ASC';

	$lang_id = intval($_GET['lang_id']);
	$lang_code = $rlValid -> xSql($_GET['lang_code']);
	$phrase = urldecode($rlValid -> xSql($_GET['phrase']));
	$phrase = str_replace(' ', '%', $phrase);

	if ($lang_id)
		$lang_code = $rlDb->getOne('Code', "`ID` = {$lang_id}", 'iflynax_languages');

	if (isset($_GET['action']) && $_GET['action'] == 'search') {
		$criteria = $_GET['criteria'];
		$where = '1';

		if ($lang_code != 'all')
			$where = "`Code` = '{$lang_code}'";

		$search_by = $criteria == 'in_value' ? 'Value' : 'Key';
		$sql = "SELECT SQL_CALC_FOUND_ROWS `ID`, `Code`, CONCAT('<span style=\"color: #596C27;\"><b>',`Code`,'</b></span> | ', `Key`) AS `Key`, `Value` ";
		$sql .= "FROM `" . RL_DBPREFIX . "iflynax_phrases` ";
		$sql .= "WHERE {$where} AND `{$search_by}` LIKE '%{$phrase}%' ORDER BY `{$sort}` {$sortDir} LIMIT {$start}, {$limit}";

		$lang_data = $rlDb -> getAll($sql);
		$count_rows = $rlDb -> getRow("SELECT FOUND_ROWS() AS `calc`");
		$lang_count['count'] = $count_rows['calc'];
	}
	else 
	{
		$rlDb -> setTable('iflynax_phrases');
		$lang_data = $rlDb -> fetch(array('ID', 'Key', 'Value'), array('Code' => $lang_code), "ORDER BY `{$sort}` {$sortDir}", array($start, $limit));
		$rlDb -> resetTable();

		$lang_count = $rlDb -> getRow("SELECT COUNT(`ID`) AS `count` FROM `". RL_DBPREFIX ."iflynax_phrases` WHERE `Code` = '{$lang_code}'");
	}

	$output['total'] = $lang_count['count'];
	$output['data'] = $lang_data;

	echo json_encode($output);
}
elseif ( $_GET['q'] == 'compare')
{
	$lang_1 = $_SESSION['lang_1'];
	$lang_2 = $_SESSION['lang_2'];

	if ( $_GET['action'] == 'update' )
	{
		$rlActions -> updateOne( $updateData, 'iflynax_phrases');

		if ( $_GET['compare_mode'] == 'phrases' )
		{
			set_time_limit(0);

			$rlDb -> setTable('iflynax_phrases');
			$phrases_1_tmp = $rlDb -> fetch('*', array('Code' => $lang_1), "ORDER BY `Key`");
			foreach ($phrases_1_tmp as $pK => $pV) {
				$phrases_1[$phrases_1_tmp[$pK]['Key']] = $phrases_1_tmp[$pK];
			}
			unset($phrases_1_tmp);

			$phrases_2_tmp = $rlDb -> fetch('*', array('Code' => $lang_2), "ORDER BY `Key`");
			foreach ($phrases_2_tmp as $pK => $pV) {
				$phrases_2[$phrases_2_tmp[$pK]['Key']] = $phrases_2_tmp[$pK];
			}
			unset($phrases_2_tmp);

			$compare_1 = array_diff_key($phrases_1, $phrases_2);
			foreach ($compare_1 as $cK => $cV) {
				$adapted_compare_1[] = $compare_1[$cK];
			}
			unset($compare_1);

			$_SESSION['compare_1'] = $_SESSION['source_1'] = $adapted_compare_1;

			$compare_2 = array_diff_key($phrases_2, $phrases_1);
			foreach ($compare_2 as $cK => $cV) {
				$adapted_compare_2[] = $compare_2[$cK];
			}
			unset($compare_2);

			$_SESSION['compare_2'] = $_SESSION['source_2'] = $adapted_compare_2;
		}
		else
		{
			$phrases_1_tmp = $rlDb -> fetch('*', array('Code' => $lang_1), "ORDER BY `Key`");
			foreach ($phrases_1_tmp as $pK => $pV) {
				$phrases_1[$phrases_1_tmp[$pK]['Key']] = $phrases_1_tmp[$pK]['Value'];
				$phrases_1_orig[$phrases_1_tmp[$pK]['Key']] = $phrases_1_tmp[$pK];
			}
			unset($phrases_1_tmp);

			$phrases_2_tmp = $rlDb -> fetch('*', array('Code' => $lang_2), "ORDER BY `Key`");

			foreach ($phrases_2_tmp as $pK => $pV) {
				$phrases_2[$phrases_2_tmp[$pK]['Key']] = $phrases_2_tmp[$pK]['Value'];
				$phrases_2_orig[$phrases_2_tmp[$pK]['Key']] = $phrases_2_tmp[$pK];
			}
			unset($phrases_2_tmp);

			$compare_1 = array_intersect_assoc($phrases_1, $phrases_2);
			foreach ($compare_1 as $cK => $cV) {
				$adapted_compare_1[] = $phrases_1_orig[$cK];
			}
			unset($compare_1);

			$_SESSION['compare_1'] = $adapted_compare_1;

			$compare_2 = array_intersect_assoc($phrases_2, $phrases_1);
			foreach ($compare_2 as $cK => $cV) {
				$adapted_compare_2[] = $phrases_2_orig[$cK];
			}
			unset($compare_2);

			$_SESSION['compare_2'] = $adapted_compare_2;
		}
	}

	$grid = (int)$_GET['grid'];
	$data = $_SESSION['compare_'. $grid];

	$output['total'] = (string)count($data);
	$output['data'] = array_slice($data, $start, $limit);

	echo json_encode($output);
}
elseif ( $_GET['action'] == 'export' )
{
	$reefless -> loadClass('IFlynaxLang', null, 'iFlynaxConnect');
	$rlIFlynaxLang -> exportLanguage(intval($_GET['lang']));
}
else
{
    if (isset($_GET['dev']) && isset($_GET['sync'])) {
        $reefless->loadClass('Install', null, 'iFlynaxConnect');
        $rlInstall->synchronizeAppLanguages();
        exit('Done');
    }

	if ( !function_exists('array_diff_key') ) {
		function array_diff_key() {
			$arrs = func_get_args();
			$result = array_shift($arrs);
			foreach ($arrs as $array) {
				foreach ($result as $key => $v) {
					if (array_key_exists($key, $array)) {
						unset($result[$key]);
					}
				}
			}
			return $result;
		}
	}

	// clear cache
	if ( !$_REQUEST['compare'] && !$_POST['xjxfun'] )
	{
		unset($_SESSION['compare_mode']);

		unset($_SESSION['compare_1']);
		unset($_SESSION['compare_2']);

		unset($_SESSION['source_1']);
		unset($_SESSION['source_2']);

		unset($_SESSION['lang_1']);
		unset($_SESSION['lang_2']);
	}

	// get all system languages
	$rlDb -> setTable('iflynax_languages');
	$allLangs = $rlDb -> fetch(array('ID', 'Key', 'Code'));

	foreach ($allLangs as &$lang_item) {
		$lang_item['name'] = $rlDb -> getOne('Value', "`Key` = '{$lang_item['Key']}'", 'iflynax_phrases');
	}

	$rlSmarty -> assign_by_ref('allLangs', $allLangs);
	$rlSmarty -> assign('langCount', count($allLangs));

	// get lang for edit
	if ( $_GET['action'] == 'edit' )
	{
		$bcAStep[] = array(
			'name' => $lang['edit']
		);

		$edit_id = (int)$_GET['lang'];

		// get current language info
		$language = $rlDb -> fetch('*', array('ID' => $edit_id), null, 1, 'iflynax_languages', 'row');

		if ( $_GET['action'] == 'edit' && !$_POST['fromPost'] )
		{
			$_POST['code'] = $language['Code'];
			$_POST['direction'] = $language['Direction'];
			$_POST['date_format'] = $language['Date_format'];
			$_POST['status'] = $language['Status'];

			// get names
			$l_name = $rlDb -> fetch(array('Code', 'Value'), array('Key' => $language['Key']), null, 1, 'iflynax_phrases', 'row');
			$_POST['name'] = $l_name['Value'];
		}
	}

	if ( $_POST['submit'] )
	{
		// check data
		if ( empty( $_POST['name'] ) ) {
			$errors[] = str_replace( '{field}', "<b>\"{$lang['name']}\"</b>", $lang['notice_field_empty']);
		}

		if ( empty( $_POST['date_format'] ) ) {
			$errors[] = str_replace( '{field}', "<b>\"{$lang['date_format']}\"</b>", $lang['notice_field_empty']);
		}

		if ( !empty($errors) ) {
			$rlSmarty -> assign_by_ref( 'errors', $errors );
		}
		else
		{
			$result = false;

			// update general information
			$updateLang = array(
				'fields' => array(
					'Date_format' => $_POST['date_format'],
					'Status' => $_POST['status'],
					'Direction' => $_POST['direction']
				),
				'where' => array(
					'Code' => $_POST['code'],
				)
			);

			$result = $rlActions -> updateOne ($updateLang, 'iflynax_languages');

			if ( $rlDb -> getOne('ID', "`Key` = '{$language['Key']}'", 'iflynax_phrases') )
			{
				// update phrase
				$updatePhrase = array(
					'fields' => array(
						'Value' => $_POST['name']
					),
					'where' => array(
						'Key' => $language['Key']
					)
				);

				$result = $rlActions -> updateOne ($updatePhrase, 'iflynax_phrases');
			}
			else
			{
				// insert phrase
				$insertPhrase = array(
					'Key' => $language['Key'],
					'Value' => $_POST['name'],
					'Code' => $language['Code']
				);

				$result = $rlActions -> insertOne ($insertPhrase, 'iflynax_phrases');
			}

			if ( $result )
			{
				$message = $lang['language_edited'];
				$aUrl = array("controller" => $controller);

				$reefless -> loadClass('Notice');
				$rlNotice -> saveNotice($message);
				$reefless -> redirect($aUrl);
			}
			else 
			{
				trigger_error("iFlynax: Can't edit language (MYSQL problems)", E_WARNING);
				$rlDebug -> logger("iFlynax: Can't edit language (MYSQL problems)");
			}
		}
	}

	if ( $_POST['import'] )
	{
		$dump_sours = $_FILES['dump']['tmp_name'];
		$dump_file = $_FILES['dump']['name'];

		preg_match( "/([\w]+)\(([\w]{2})\)(\.xml)$/", $dump_file, $matches );
		$new_lang_code = strtolower($matches[2]);

		if ( !empty($new_lang_code) && strtolower($matches[3]) == '.xml' )
		{
			if ( is_readable($dump_sours) )
			{
				$rlDb -> query("SET NAMES `utf8`");

				// check exist language
				$exist_lang_key = $rlDb -> getOne('Key', "LOWER(`Code`) = '{$new_lang_code}'", 'iflynax_languages');

				// read language file
				$doc = new DOMDocument();
				$doc -> load($dump_sours);
				$phrases = $doc -> getElementsByTagName('phrase');

				if ( $phrases )
				{
					// create new language entry if we haven't it
					if ( !$exist_lang_key )
					{
						loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');
						$lang_info = $doc -> getElementsByTagName('phrases') -> item(0);

						$new_lang_key = $matches[1];
						if ( !utf8_is_ascii($new_lang_key) ) {
							$new_lang_key = utf8_to_ascii($new_lang_key);
						}
						$new_lang_key = $rlValid -> str2key($new_lang_key);

						$insert_lang = array(
				    		'Code' => $new_lang_code,
				    		'Key' => $new_lang_key,
				    		'Direction' => $lang_info -> getAttribute('direction'),
				    		'Date_format' => $lang_info -> getAttribute('date_format')
				    	);
				    	$GLOBALS['rlActions'] -> insertOne($insert_lang, 'iflynax_languages');
					}

					// add missing phrases
				    foreach($phrases as $phrase)
				    {
				    	$phrase_key = $phrase -> getAttribute('key');

				    	if (!$rlDb->getOne('ID', "`Code` = '{$new_lang_code}' AND `Key` = '{$phrase_key}'", 'iflynax_phrases')) {
					    	$insert[] = array(
					    		'Code' => $new_lang_code,
					    		'Key' => $phrase_key,
					    		'Value' => $phrase->textContent
					    	);
				    	}
				    }
				    $GLOBALS['rlActions'] -> insert($insert, 'iflynax_phrases');

					$rlNotice -> saveNotice($lang['new_language_imported']);
					$aUrl = array("controller" => $controller);
					$reefless -> redirect($aUrl);
				}
				else {
					$errors[] = $lang['iflynax_import_lang_file_empty'];
				}
			}
			else
			{
				$errors[] = $lang['can_not_read_file'];
				trigger_error("iFlynax: Can not to read uploaded file | Language Import", E_WARNING);
				$rlDebug -> logger("iFlynax: Can not to read uploaded file | Language Import");
			}
		}
		else {
			$errors[] = $lang['iflynax_import_lang_file_name_error'];
		}

		if ( !empty($errors) ) {
			$rlSmarty -> assign_by_ref('errors', $errors);
		}
	}
	elseif ( isset($_POST['compare']) )
	{
		// additional bread crumb step
		$bcAStep = $lang['languages_compare'];
		$lang_1 = $_POST['lang_1'];
		$lang_2 = $_POST['lang_2'];

		foreach ($allLangs as $lK => $lV) {
			$langs_info[$allLangs[$lK]['Code']] = $allLangs[$lK];
		}

		// checking errors
		if ( empty($lang_1) || empty($lang_2) ) {
			$errors[] = $lang['compare_empty_langs'];
		}

		if ( $lang_1 == $lang_2 && !$errors ) {
			$errors[] = $lang['compare_languages_same'];
		}

		if ( (!array_key_exists( $lang_1, $langs_info ) || !array_key_exists( $lang_2, $langs_info )) && !$errors ) {
			$errors[] = $lang['system_error'];
			$rlDebug -> logger("iFlynax: Can not compare the languages, gets undefine language code");
		}

		if ( !empty($errors) ) {
			$rlSmarty -> assign_by_ref('errors', $errors);
		}
		else
		{
			set_time_limit(0);

			$rlDb -> setTable('iflynax_phrases');

			$_SESSION['compare_mode'] = $_POST['compare_mode'];
			if ( $_POST['compare_mode'] == 'phrases' )
			{
				$phrases_1_tmp = $rlDb -> fetch('*', array('Code' => $lang_1), "ORDER BY `Key`");
				foreach ($phrases_1_tmp as $pK => $pV) {
					$phrases_1[$phrases_1_tmp[$pK]['Key']] = $phrases_1_tmp[$pK];
				}
				unset($phrases_1_tmp);

				$phrases_2_tmp = $rlDb -> fetch( '*', array('Code' => $lang_2), "ORDER BY `Key`" );
				foreach ($phrases_2_tmp as $pK => $pV) {
					$phrases_2[$phrases_2_tmp[$pK]['Key']] = $phrases_2_tmp[$pK];
				}
				unset($phrases_2_tmp);

				$compare_1 = array_diff_key($phrases_1, $phrases_2);
				foreach ($compare_1 as $cK => $cV) {
					$adapted_compare_1[] = $compare_1[$cK];
				}
				unset($compare_1);

				$compare_2 = array_diff_key($phrases_2, $phrases_1);
				foreach ($compare_2 as $cK => $cV) {
					$adapted_compare_2[] = $compare_2[$cK];
				}
				unset($compare_2);

				if ( empty($adapted_compare_1) && empty($adapted_compare_2) )
				{
					$reefless -> loadClass( 'Notice' );
					$rlNotice -> saveNotice( $lang['compare_no_diff_found'] );

					$aUrl = array( "controller" => $controller );
					$reefless -> redirect( $aUrl );
				}
				else
				{
					$_SESSION['compare_1'] = $_SESSION['source_1'] = $adapted_compare_1;
					$_SESSION['lang_1'] = $lang_1;

					$_SESSION['compare_2'] = $_SESSION['source_2'] = $adapted_compare_2;
					$_SESSION['lang_2'] = $lang_2;
		
					$compare_lang1 = array('diff' => count($adapted_compare_1), 'Code' => $lang_1);
					$compare_lang2 = array('diff' => count($adapted_compare_2), 'Code' => $lang_2);

					$rlSmarty -> assign_by_ref('compare_lang1', $compare_lang1);
					$rlSmarty -> assign_by_ref('compare_lang2', $compare_lang2);
					$rlSmarty -> assign_by_ref('langs_info', $langs_info);
				}
			}
			else
			{
				$phrases_1_tmp = $rlDb -> fetch('*', array('Code' => $lang_1), "ORDER BY `Key`");
				foreach ($phrases_1_tmp as $pK => $pV) {
					$phrases_1[$phrases_1_tmp[$pK]['Key']] = $phrases_1_tmp[$pK]['Value'];
					$phrases_1_orig[$phrases_1_tmp[$pK]['Key']] = $phrases_1_tmp[$pK];
				}
				unset($phrases_1_tmp);

				$phrases_2_tmp = $rlDb -> fetch( '*', array('Code' => $lang_2), "ORDER BY `Key`" );

				foreach ($phrases_2_tmp as $pK => $pV) {
					$phrases_2[$phrases_2_tmp[$pK]['Key']] = $phrases_2_tmp[$pK]['Value'];
					$phrases_2_orig[$phrases_2_tmp[$pK]['Key']] = $phrases_2_tmp[$pK];
				}
				unset($phrases_2_tmp);

				$compare_1 = array_intersect_assoc($phrases_1, $phrases_2);
				foreach ($compare_1 as $cK => $cV) {
					$adapted_compare_1[] = $phrases_1_orig[$cK];
				}
				unset($compare_1);

				$compare_2 = array_intersect_assoc($phrases_2, $phrases_1);
				foreach ($compare_2 as $cK => $cV) {
					$adapted_compare_2[] = $phrases_2_orig[$cK];
				}
				unset($compare_2);

				if ( empty($adapted_compare_1) && empty($adapted_compare_2) )
				{
					$reefless -> loadClass( 'Notice' );
					$rlNotice -> saveNotice( $lang['compare_no_diff_found'] );
	
					$aUrl = array( "controller" => $controller );
					$reefless -> redirect( $aUrl );
				}
				else
				{
					$_SESSION['compare_1'] = $adapted_compare_1;
					$_SESSION['lang_1'] = $lang_1;

					$_SESSION['compare_2'] = $adapted_compare_2;
					$_SESSION['lang_2'] = $lang_2;

					$compare_lang1 = array( 'diff' => count($adapted_compare_1), 'Code' => $lang_1 );
					$compare_lang2 = array( 'diff' => count($adapted_compare_2), 'Code' => $lang_2 );

					$rlSmarty -> assign_by_ref('compare_lang1', $compare_lang1);
					$rlSmarty -> assign_by_ref('compare_lang2', $compare_lang2);
					$rlSmarty -> assign_by_ref('langs_info', $langs_info);
				}
			}
		}
	}

	// load admin class
	$reefless -> loadClass('IFlynaxLang', null, 'iFlynaxConnect');

	// register ajax methods
	$rlXajax -> registerFunction(array('setDefault', $rlIFlynaxLang, 'ajaxSetDefault'));
	$rlXajax -> registerFunction(array('deleteLang', $rlIFlynaxLang, 'ajaxDeleteLang'));
	$rlXajax -> registerFunction(array('addLanguage', $rlIFlynaxLang, 'ajax_addLanguage'));
	$rlXajax -> registerFunction(array('copyPhrases', $rlIFlynaxLang, 'ajaxCopyPhrases'));
}
