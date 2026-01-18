<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: auto.gmoplus.com
 *  FILE: BLOCKSCONTROLLER.PHP
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

namespace Flynax\Plugin\WordPressBridge\Controllers;

use Flynax\Plugin\WordPressBridge\Response;
use Flynax\Plugin\WordPressBridge\WordPressAPI\API;

/**
 * Class BlocksController
 *
 * @since 2.0.0
 *
 * @package Flynax\Plugin\WordPressBridge\Controllers
 */
class BlocksController
{
    /**
     * @var \rlActions
     */
    private $rlActions;

    /**
     * BlocksController constructor.
     */
    public function __construct()
    {
        $this->rlActions = wbMake('rlActions');
    }

    /**
     * Update cache of the all blocks
     */
    public function updateBlocksCache()
    {
        self::updateCache();
        Response::success('Cache has ben update successfully');
    }

    /**
     * Update cache of the block with 'wpbridge_last_post' key
     *
     * @return bool
     */
    public static function updateCache()
    {
        $blockKey = 'wpbridge_last_post';
        $self = new self();
        $wordPressApi = new API();
        $posts = $wordPressApi->getRecentPosts((int) $GLOBALS['config']['wp_post_count']);
        $posts = json_encode($posts);
        $posts = str_replace("\\\\'", "\\'", $posts);
        $posts = str_replace('\\\\"', '\\"', $posts);
        $newBlockData = <<< PHP
        \$GLOBALS['reefless']->loadClass('WordpressBridge', null, 'wordpressBridge');
        \$GLOBALS['rlWordpressBridge']->cachedPosts = '{$posts}';
        \$GLOBALS['rlWordpressBridge']->blockWPBridgeLastPost();
PHP;
        $update = array(
            'fields' => array(
                'Content' => $newBlockData,
            ),
            'where' => array(
                'Key' => $blockKey,
                'Plugin' => 'wordpressBridge',
            ),
        );

        $self->rlActions->rlAllowHTML = true;
        return $self->rlActions->updateOne($update, 'blocks');
    }
}
