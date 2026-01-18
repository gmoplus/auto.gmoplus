<?php
/**copyright**/

namespace Flynax\Plugins\CarSpecs;

/**
 * Class Cache
 *
 * @since   2.1.0
 * @package Flynax\Plugins\CarSpecs
 */
class Cache
{
    const WORKING_TABLE = 'car_specs_cache';
    const WORKING_TABLE_WITH_PREFIX = RL_DBPREFIX . self::WORKING_TABLE;

    /**
     * @var \rlDb
     */
    private $rlDb;

    /**
     * @var \rlActions
     */
    private $rlActions;

    /**
     * Cache constructor.
     */
    public function __construct()
    {
        $this->rlDb = $GLOBALS['rlDb'];
        $this->rlActions = $GLOBALS['rlActions'];
    }


    public static function get($key, $module = '')
    {
        $self = new self();

        if ($module) {
            return $self->rlDb->getOne('Content', "`Uniq` = '{$key}'", self::WORKING_TABLE);
        }

        $sql = sprintf("SELECT 'ID', 'Content' FROM `%s` WHERE `Key` = '%s' ", self::WORKING_TABLE_WITH_PREFIX, $key);
        return $self->rlDb->getAll($sql);
    }

    public static function set($key, $content, $module)
    {
        if (!$key || !$content || !$module) {
            return '';
        }
        $self = new self();

        $rowID = $self->rlDb->getOne('ID', "`Uniq` = '{$key}'", self::WORKING_TABLE);
        if ($rowID) {
            $update = array(
                'fields' => array(
                    'Content' => $content,
                    'Date' => 'NOW()',
                ),
                'where' => array(
                    'ID' => $rowID,
                ),
            );

            return $self->rlDb->update($update, self::WORKING_TABLE);
        }

        $data = array(
            'Uniq' => $key,
            'Content' => $content,
            'Module' => $module,
            'Date' => 'NOW()',
        );
        return $self->rlDb->insertOne($data, self::WORKING_TABLE);
    }
}
