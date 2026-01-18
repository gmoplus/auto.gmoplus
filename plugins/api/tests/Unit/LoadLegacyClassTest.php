<?php

namespace Tests\Unit;

use Tests\TestCase;

define('TEST_LEGACY_LOAD', true);

class LoadLegacyClassTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @dataProvider validWordsProvider
     * @return void
     */
    public function testHelperFunction(...$args)
    {
        $expected_filename = array_pop($args);
        $filename = rl(...$args);

        $this->assertEquals($expected_filename, $filename);
    }

    public static function validWordsProvider()
    {
        return [
            ['Listings', RL_CLASSES . 'rlListings.class.php'],
            ['Listings', null, null, true, RL_CLASSES . 'rlListings.class.php'],

            ['Plugin', true, RL_CLASSES . 'admin/rlPlugin.class.php'],
            ['admin/Plugin', RL_CLASSES . 'admin/rlPlugin.class.php'],

            ['IPGeo', null, 'ipgeo', RL_PLUGINS . 'ipgeo/rlIPGeo.class.php'],
            ['plugins/ipgeo/IPGeo', RL_PLUGINS . 'ipgeo/rlIPGeo.class.php'],
            ['plugin/ipgeo/IPGeo', RL_PLUGINS . 'ipgeo/rlIPGeo.class.php'],
        ];
    }
}
