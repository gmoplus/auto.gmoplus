<?php
/**copyright**/

namespace Flynax\Plugins\CarSpecs\Interfaces;

/**
 * Interface ModuleInterface
 *
 * @since 2.1.0
 * @package Flynax\Plugins\CarSpecs\Interfaces
 */
interface ModuleInterface
{
    /**
     * Get car information by unique identification: Car Plate or Vin number
     *
     * @param string $carIdentifier
     * @return mixed
     */
    public function getCarInfo($carIdentifier);
}
