<?php
/**
 * clap Project ${PROJECT_URL}
 *
 * @link      ${GITHUB_URL} Source code
 */

namespace Sta\Commons\Geo;

class GeoPoint
{
    /**
     * @var float
     */
    protected $lat;
    /**
     * @var float
     */
    protected $lon;

    /**
     * GeoPoint constructor.
     *
     * @param float $lat
     * @param float $lon
     */
    public function __construct(float $lat, float $lon)
    {
        $this->lat = $lat;
        $this->lon = $lon;
    }

    public function __toString()
    {
        return "{$this->getLat()},{$this->getLon()}";
    }

    /**
     * @return float
     */
    public function getLat(): float
    {
        return $this->lat;
    }

    /**
     * @param float $lat
     *
     * @return $this
     */
    public function setLat(float $lat)
    {
        $this->lat = $lat;

        return $this;
    }

    /**
     * @return float
     */
    public function getLon(): float
    {
        return $this->lon;
    }

    /**
     * @param float $lon
     *
     * @return $this
     */
    public function setLon(float $lon)
    {
        $this->lon = $lon;

        return $this;
    }

}
