<?php
/**
 * clap Project ${PROJECT_URL}
 *
 * @link      ${GITHUB_URL} Source code
 */

namespace Sta\Commons\Geo;

class GeoPointUtil
{
    public const EARTH_RADIUS_KM = 6371;

    /**
     * Miles
     */
    public const UNIT_MI = 'mi';
    /**
     * Kilometers
     */
    public const UNIT_KM = 'km';
    /**
     * Meters
     */
    public const UNIT_M = 'm';
    /**
     * Centimeters
     */
    public const UNIT_CM = 'cm';
    /**
     * Millimeters
     */
    public const UNIT_MM = 'mm';

    /**
     * This method will return all the 4 points that determines the area based on the two points (southwest and
     * northeast).
     *
     * @param GeoPoint $swPoint
     * @param GeoPoint $nePoint
     * @param bool $addLastPointToEnsureTheAreaIsClosed
     *
     * @return array
     */
    public function getAreaFromSouthwestToNortheastPoints(
        GeoPoint $swPoint, GeoPoint $nePoint, bool $addLastPointToEnsureTheAreaIsClosed = false
    ): array {
        $area = [
            new GeoPoint($swPoint->getLat(), $swPoint->getLon()),
            new GeoPoint($nePoint->getLat(), $swPoint->getLon()),
            new GeoPoint($nePoint->getLat(), $nePoint->getLon()),
            new GeoPoint($swPoint->getLat(), $nePoint->getLon()),
        ];

        if ($addLastPointToEnsureTheAreaIsClosed) {
            $area[] = clone $area[0];
        }

        return $area;
    }

    /**
     * Calculates if the $point is inside the $area.
     * Based on https://stackoverflow.com/a/18190354/2397394
     *
     * @param GeoPoint $point
     * @param GeoPoint[] $area
     *
     * @return bool
     */
    public function isPointInThisArea(GeoPoint $point, array $area): bool
    {
        $edgesPassedThrough = 0;
        $areaCount          = count($area);
        $pLon               = $point->getLon();
        $pLat               = $point->getLat();
        $p1                 = $area[0];

        for ($i = 1; $i <= $areaCount; $i++) {
            $p2    = $area[$i % $areaCount];
            $p2Lat = $p2->getLat();
            $p2Lon = $p2->getLon();
            $p1Lat = $p1->getLat();
            $p1Lon = $p1->getLon();

            if ($pLon > min($p1Lon, $p2Lon)
                && $pLon <= max($p1Lon, $p2Lon)
                && $pLat <= max($p1Lat, $p2Lat)
                && $p1Lon != $p2Lon
            ) {
                $xinters = ($pLon - $p1Lon) * ($p2Lat - $p1Lat) / ($p2Lon - $p1Lon) + $p1Lat;

                if ($p1Lat == $p2Lat || $pLat <= $xinters) {
                    $edgesPassedThrough++;
                }
            }
            $p1 = $p2;
        }

        // if the number of edges we passed through is even, then it's not in the poly.
        return $edgesPassedThrough % 2 != 0;
    }

    /**
     * @param string $unit
     *  Use one of the consts {@link \Sta\Commons\Geo\GeoPointUtil}::UNIT_*
     *
     * @return float
     *  Returns 0 if the $unit is invalid.
     */
    public function getEarthRadius(string $unit = self::UNIT_KM): float
    {
        switch ($unit) {
            case self::UNIT_MI:
                return round(self::EARTH_RADIUS_KM * 0.62137119224);
            case self::UNIT_KM:
                return self::EARTH_RADIUS_KM;
            case self::UNIT_M :
                return self::EARTH_RADIUS_KM * 1000;
            case self::UNIT_CM:
                return self::EARTH_RADIUS_KM * 100000;
            case self::UNIT_MM:
                return self::EARTH_RADIUS_KM * 1000000;
        }

        return 0;
    }

    /**
     * Calculate the distance between two points.
     *
     * @param \Sta\Commons\Geo\GeoPoint $p1
     * @param \Sta\Commons\Geo\GeoPoint $p2
     * @param string $unit
     *
     * @return float
     */
    public function distanceBetweenTwoPoints(GeoPoint $p1, GeoPoint $p2, string $unit = self::UNIT_KM): float
    {
        $lat1 = $p1->getLat();
        $lon1 = $p1->getLon();
        $lat2 = $p2->getLat();
        $lon2 = $p2->getLon();

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $lat1 = deg2rad($lat1);
        $lat2 = deg2rad($lat2);

        $a = sin($dLat / 2) * sin($dLat / 2) + sin($dLon / 2) * sin($dLon / 2) * cos($lat1) * cos($lat2);
        $b = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $this->getEarthRadius($unit) * $b;

        return $distance;
    }

    /**
     * Reduz a quantidade pontos que determinam um area até que a quantidade de pontos restates seja menor ou igual a
     * $maxPoints.
     * Este algoritimo não tem a inteção de modificar a área, mas sim de diminuir o detalhamento dos pontos que
     * determinam uma area. No final, a área seá basicamente a mesma, porem com menas detalhes.
     *
     * Remove pontos que estejam muito próximos do ponto anterior. A distancia mínima permitida entre cada os pontos
     * é contralado por $allowedDistanceInKm.
     * Alem disto vc tbm pode controlar a quantidade máxima de pontos que vc quer que exista no final com a varaiavel
     * $maxPoints, sendo assim, se a quantidade de pontos restantes for maior que o permitido vamos aumentar
     * gradativamente a distancia minima permitida até que a quantidade de pontos restantes respeite o valor $maxPoints.
     *
     * @param \Sta\Commons\Geo\GeoPoint[] $boundaries
     *      A área que se deseja dminiuir a quantidade de pontons.
     *
     * @param int $maxPoints
     *      A quantidade máxima de pontos que podem ser retornados.
     *
     * @param array $distancesCache
     *      Array de cache de uso interno para evitar calcular a distancia entre dois pontos mais de uma vez.
     *
     * @param float $allowedDistanceInKm
     *     Distancia mínima permitida entre os pontos.
     *     Esta distancia será incrementada gradualmente até o mínimo necessário para que a $maxPoints seja respeitado.
     *
     * @param float $increaseDistanceFactor
     *      Fator para incrementar a distancia mínima entre os pontos.
     *
     * @return \Sta\Commons\Geo\GeoPoint[] A areá com menos detalhamento.
     * A areá com menos detalhamento.
     */
    public function reduceNumbersOfPoints(
        array $boundaries, int $maxPoints, array &$distancesCache = [], float $allowedDistanceInKm = 1,
        float $increaseDistanceFactor = 0.3
    ): array {
        if (!$boundaries) {
            return [];
        }

        if (count($boundaries) <= $maxPoints) {
            return $boundaries;
        }

        // Uses array_values so we can trust that key correspond to the position of the value in the array (eg, 0 will
        // be the first value, 1 the second and so on). Its necessary so we can guarantee that the result points will be
        // in the same order they appears in the original array of boundaries.
        $reducedBoundaries = array_values($boundaries);

        do {
            /** @var \Sta\Commons\Geo\GeoPoint $lastPoint */
            $lastPoint                  = null;
            $temporaryReducedBoundaries = [];
            foreach ($reducedBoundaries as $key => $boundary) {
                if (!$lastPoint) {
                    $lastPoint                        = $boundary;
                    $temporaryReducedBoundaries[$key] = $lastPoint;
                    continue;
                }

                $distanceCacheKey1 = "$lastPoint $boundary";
                $distanceCacheKey2 = "$boundary $lastPoint";
                if (!isset($distancesCache[$distanceCacheKey1])) {
                    $distancesCache[$distanceCacheKey1] = $this->distanceBetweenTwoPoints(
                        $lastPoint,
                        $boundary,
                        self::UNIT_KM
                    );
                    $distancesCache[$distanceCacheKey2] = $distancesCache[$distanceCacheKey1];
                }
                $distanceInKm = $distancesCache[$distanceCacheKey1];

                if ($distanceInKm >= $allowedDistanceInKm) {
                    $lastPoint                        = $boundary;
                    $temporaryReducedBoundaries[$key] = $lastPoint;
                }
            }
            $howMuchThereIsNow = count($temporaryReducedBoundaries);
            $reducedBoundaries = $temporaryReducedBoundaries;

            $continue = $howMuchThereIsNow > $maxPoints;
            if ($continue) {
                $allowedDistanceInKm += ($allowedDistanceInKm * $increaseDistanceFactor);
            }
        } while ($continue);

        // Sort by its keys, so the points will be in the same order they appears in the original array of points.
        ksort($reducedBoundaries);

        return $reducedBoundaries;
    }
}
