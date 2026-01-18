<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : SERVERINTERFACE.PHP
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

namespace Flynax\Plugins\RemoteStorage\Interfaces;

/**
 * Interface ServerInterface
 * @package Flynax\Plugins\RemoteStorage\Interfaces
 */
interface ServerInterface
{
    /**
     * @param array $serverData
     *
     * @return object
     */
    public function initialize(array $serverData): object;

    /**
     * @param string $key
     * @param string $path
     *
     * @return string
     */
    public function sendFile(string $key, string $path): string;

    /**
     * @param string $key
     *
     * @return void
     */
    public function removeFile(string $key): void;

    /**
     * @param string $key
     * @param string $path
     *
     * @return bool
     */
    public function getFile(string $key, string $path): bool;

    /**
     * @return array
     */
    public static function getCredentials(): array;

    /**
     * @return string
     */
    public static function getType(): string;

    /**
     * @param array $serverData
     *
     * @return void
     */
    public static function adaptServerData(array &$serverData): void;

    /**
     * @param array $serverData
     *
     * @return void
     */
    public static function getFileURLPattern(array &$serverData): string;
}
