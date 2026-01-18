<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: POLLS.INC.PHP
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

class rlPolls extends Flynax\Abstracts\AbstractPlugin implements Flynax\Interfaces\PluginInterface
{
    /**
     * Variable in PHP content which will be replaced by content with polls info (encoded in JSON)
     *
     * @since 3.0.1
     */
    public const POLLS_TEMPLATE = '{$polls_replace}';

    /**
     * @var string - Content code for polls blocks
     */
    public $content = 'global $rlSmarty;
        $polls_array = json_decode(\'' . self::POLLS_TEMPLATE . '\', true);
        $count_max = count($polls_array);
        $index = rand(0, $count_max-1);
        shuffle($polls_array);
        $poll = $polls_array[$index];
        $polls = explode(\',\', $_COOKIE[\'polls\']);
        $poll_items = $poll[\'items\'];
        if (in_array($poll[\'ID\'], $polls)) {
            $poll[\'voted\'] = true;
        }
        foreach ($poll_items as $key => $pValue) {
            $poll_items[$key][\'percent\'] = $poll_items[$key][\'Votes\'] && $poll[\'total\']
                ? floor(((int)$poll_items[$key][\'Votes\'] * 100) / $poll[\'total\'])
                : 0;
        }
        $poll[\'items\'] = $poll_items;
        $rlSmarty->assign(\'poll\', $poll);
        $rlSmarty->display(RL_PLUGINS . \'polls/polls.block.tpl\');';

    /**
     * @var string - Content code for empty polls blocks
     */
    public $empty_content = 'global $rlSmarty;
        $poll = \'\';
        $rlSmarty->assign(\'poll\', $poll);
        $rlSmarty->display(RL_PLUGINS . \'polls/polls.block.tpl\');';

    /**
     * Get poll data by poll id
     *
     * @param int $poll_id - poll id
     *
     * @return array - Poll data
     */
    public function get($poll_id = false): array
    {
        global $rlDb;

        // Get poll
        $where = $poll_id == 'all' ? array('Random' => 1, 'Status' => 'active') : array('ID' => $poll_id, 'Status' => 'active');

        $poll = (array) $rlDb->fetch(array('ID', 'ID` AS `Key', 'Random'), $where, null, null, 'polls');
        foreach ($poll as $key => $val) {
            $where_sum = $poll_id != 'all' ? "`Poll_ID` = '{$poll_id}'" : "`Poll_ID` = '{$poll[$key]['ID']}'";

            if (!empty($poll[$key])) {
                // Get poll's items
                $poll_items  = $rlDb->fetch(array('ID` AS `Key', 'Votes', 'Color'), array('Poll_ID' => $poll[$key]['ID']), "ORDER BY `ID`", null, 'polls_items');
                $total_votes = $rlDb->getRow("SELECT SUM(`Votes`) AS `sum` FROM `" . RL_DBPREFIX . "polls_items` WHERE {$where_sum} LIMIT 1");

                foreach ($poll_items as $key2 => $pValue) {
                    $poll_items[$key2]["percent"] = $poll_items[$key2]["Votes"] && $poll[$key]["total"]
                        ? floor(((int)$poll_items[$key2]["Votes"] * 100) / $poll[$key]["total"])
                        : 0;
                }

                $poll[$key]['total'] = $total_votes['sum'];
                $poll[$key]['items'] = $poll_items;
            }
        }

        return $poll;
    }

    /**
     * Do vote
     *
     * @param int    $poll_id - Poll id
     * @param string $vote_id - Vote id
     *
     * @return array - Poll data
     */
    private function doVote($poll_id = false, $vote_id = false)
    {
        global $rlDb, $reefless, $rlLang;

        $polls = explode(',', $_COOKIE['polls']);
        $random = $rlDb->getOne('Random', "`ID` = {$poll_id}", 'polls');

        if (!in_array($poll_id, $polls)) {
            $rlDb->query("UPDATE `" . RL_DBPREFIX . "polls_items` SET `Votes` = `Votes` + 1 WHERE `Poll_ID` = '{$poll_id}' AND `ID` = '{$vote_id}'");

            /* save vote in cookie */
            $polls[] = $poll_id;
            $value = implode(',', $polls);
            $expire_time = time() + 2592000;
            $reefless->createCookie('polls', $value, $expire_time);
        }

        $content = $this->preparePollsContent($this->get($random ? 'all' : $poll_id));
        $GLOBALS['rlValid']->sql($content);

        /* edit poll block */
        $postfix = $random ? '' : '_' . $poll_id;
        $rlDb->query("UPDATE `" . RL_DBPREFIX . "blocks` SET `Content` = '{$content}' WHERE `Key` = 'polls{$postfix}' LIMIT 1");

        $array_polls = $this->get($poll_id);

        $poll = $array_polls[0];
        $poll_items = $poll['items'];

        $poll['voted'] = true;
        foreach ($poll_items as &$pollItem) {
            $pollItem['percent'] = $pollItem['Votes'] && $poll['total']
                ? floor(((int) $pollItem['Votes'] * 100) / $poll['total'])
                : 0;
            $pollItem['name'] = $rlLang->getPhrase("polls_items+name+{$pollItem['Key']}", null, null, true);
        }
        $poll['items'] = $poll_items;

        unset($array_polls, $poll_items);

        return $poll;
    }

    /**
     * Preparing content of polls in block
     *
     * @since 3.0.1
     *
     * @param array|null $polls
     *
     * @return string
     */
    public function preparePollsContent(?array $polls = []): string
    {
        return $polls
            ? str_replace(self::POLLS_TEMPLATE, json_encode($polls), $this->content)
            : $this->empty_content;
    }

    /**
     * @hook apTplHeader
     * @since 3.0.1
     */
    public function hookApTplHeader()
    {
        if ($_GET['controller'] == 'polls' && ($_GET['action'] == 'add' || $_GET['action'] == 'edit')) {
            echo '<script type="text/javascript" src="' . RL_LIBS_URL . 'jquery/colorpicker/js/colorpicker.js"></script>
            <link rel="stylesheet" type="text/css" href="' . RL_PLUGINS_URL . 'polls/static/polls.css" />';
        }
    }

    /**
     * @hook tplFooter
     * @since 3.0.1
     */
    public function hookTplFooter()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'polls' . RL_DS . 'footer.tpl');
    }

    /**
     * @hook ajaxRequest
     * @since 3.0.1
     */
    public function hookAjaxRequest(&$out, $request_mode)
    {
        if ($request_mode !== 'poll_vote') {
            return;
        }

        $pollID = (int) $_REQUEST['item'];
        $voteID = (int) $_REQUEST['vote'];
        $out    = ['status' => 'ERROR'];

        if (!$pollID || !$voteID) {
            return;
        }

        $out = ['status' => 'OK', 'results' => $this->doVote($pollID, $voteID)];
    }

    /**
     * @hook apAjaxRequest
     * @since 3.0.1
     */
    public function hookApAjaxRequest(&$out = null, $item = null): void
    {
        if ($item !== 'deletePoll') {
            return;
        }

        $out = ['status' => 'ERROR'];

        if (!$pollID = (int) $_REQUEST['id']) {
            return;
        }

        global $rlDb;

        $random = $rlDb->getOne('Random', "`ID` = {$pollID}", 'polls');

        $rlDb->delete(['ID' => $pollID], 'polls');
        $rlDb->delete(['Key' => "polls+name+{$pollID}", 'Plugin' => 'polls'], 'lang_keys', null, null);

        $items = $rlDb->fetch(['ID'], ['Poll_ID' => $pollID], null, null, 'polls_items');
        foreach ($items as $item) {
            $rlDb->delete(['Key' => "polls_items+name+{$item['ID']}", 'Plugin' => 'polls'], 'lang_keys', null, null);
        }
        $rlDb->delete(['Poll_ID' => $pollID], 'polls_items', null, null);

        if ($random) {
            $rlDb->rlAllowHTML = true;
            $rlDb->updateOne([
                'fields' => ['Content' => $this->preparePollsContent($this->get('all'))],
                'where'  => ['Key' => 'polls']
            ], 'blocks');
        } else {
            $rlDb->delete(['Key' => "polls_{$pollID}", 'Plugin' => 'polls'], 'blocks');
            $rlDb->delete(['Key' => "blocks+name+polls_{$pollID}", 'Plugin' => 'polls'], 'lang_keys', null, null);
        }

        $out = ['status' => 'OK'];
    }

    /**
     * @deprecated 3.0.1
     */
    public function hookStaticDataRegister() {}

    /**
     * Plugin installation process
     * @since 3.0.1
     */
    public function install()
    {
        $GLOBALS['rlDb']->createTable('polls',
            "`ID` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             `Date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
             `Side` VARCHAR(15) NOT NULL DEFAULT '',
             `Tpl` ENUM('1','0') NOT NULL DEFAULT '1',
             `Random` ENUM('1','0') NOT NULL DEFAULT '0',
             `Status` ENUM('active','approval') NOT NULL DEFAULT 'active',
             KEY `Status` (`Status`)"
        );

        $GLOBALS['rlDb']->createTable('polls_items',
            "`ID` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
             `Poll_ID` int(11) NOT NULL DEFAULT '0',
             `Votes` INT(9) NOT NULL DEFAULT '0',
             `Color` VARCHAR(10) NOT NULL DEFAULT '',
             KEY `Poll_ID` (`Poll_ID`)"
        );
    }

    /**
     * Plugin uninstalling process
     * @since 3.0.1
     */
    public function uninstall()
    {
        $GLOBALS['rlDb']->dropTables(['polls', 'polls_items']);
    }

    /**
     * Update to 3.0.0 version
     */
    public function update300()
    {
        $GLOBALS['rlDb']->query("DELETE FROM `{db_prefix}hooks` WHERE `Name` = 'tplHeader' AND `Plugin` = 'polls' LIMIT 1");
    }

    /**
     * Update to 3.0.1 version
     */
    public function update301()
    {
        global $rlDb;

        $rlDb->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Plugin` = 'polls' AND `Key` IN ('polls_color','view_results')");
        $rlDb->query("DELETE FROM `{db_prefix}hooks` WHERE `Plugin` = 'polls' AND `Name` IN ('specialBlock','staticDataRegister')");

        unlink(RL_PLUGINS . 'polls/static/style.css');

        if (in_array('ru', array_keys($GLOBALS['languages']))) {
            $russianTranslation = json_decode(file_get_contents(RL_PLUGINS . 'polls/i18n/ru.json'), true);

            foreach ($russianTranslation as $phraseKey => $phraseValue) {
                if (!$rlDb->getOne('ID', "`Key` = '{$phraseKey}' AND `Code` = 'ru'", 'lang_keys')) {
                    $insertPhrase = $rlDb->fetch(
                        ['Module', 'Key', 'Plugin'],
                        ['Code' => $GLOBALS['config']['lang'], 'Key' => $phraseKey],
                        null, 1, 'lang_keys', 'row'
                    );

                    $insertPhrase['Code']  = 'ru';
                    $insertPhrase['Value'] = $phraseValue;

                    $rlDb->insertOne($insertPhrase, 'lang_keys');
                } else {
                    $rlDb->updateOne([
                        'fields' => ['Value' => $phraseValue],
                        'where'  => ['Key'   => $phraseKey, 'Code' => 'ru'],
                    ], 'lang_keys');
                }
            }
        }

        // Update polls content (type of cache changed from serialized string to json)
        $rlDb->rlAllowHTML = true;
        foreach ($rlDb->fetch(['ID', 'Random'], null, null, null, 'polls') as $poll) {
            $id = (int) $poll['Random'] === 1 ? 'all' : $poll['ID'];

            $rlDb->updateOne([
                'fields' => ['Content' => $this->preparePollsContent($this->get($id))],
                'where'  => ['Key' => ($id === 'all' ? 'polls' : "polls_{$id}")],
            ], 'blocks');
        }
    }

    /*** DEPRECATED ***/

    /**
     * Vote
     *
     * @deprecated 3.0.1
     *
     * @param string $out - ajax response by reference
     */
    public function vote($out)
    {}

    /**
     * Vote
     *
     * @deprecated 3.0.1
     *
     * @param int    $poll_id - poll id
     * @param string $vote_id - vote id
     */
    public function ajaxVote($poll_id = false, $vote_id = false)
    {}

    /**
     * Delete poll
     *
     * @deprecated 3.0.1
     *
     * @param int $poll_id - Poll id
     */
    public function ajaxDeletePoll($poll_id = false)
    {}
}
