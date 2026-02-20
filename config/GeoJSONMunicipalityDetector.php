<?php

class GeoJSONWardDetector
{
    private $wards = [];

    public function __construct()
    {
        $this->loadWardGeoJSON();
    }


//       Load Bhubaneswar Ward Boundaries
  
    private function loadWardGeoJSON()
    {
        $path = __DIR__ . '/../data/wards.geojson';

        if (!file_exists($path)) {
            return;
        }

        $geojson = json_decode(file_get_contents($path), true);

        if (!isset($geojson['features'])) {
            return;
        }

        foreach ($geojson['features'] as $feature) {
            $properties = $feature['properties'] ?? [];

            // Adjust key names if needed (depends on GeoJSON)
            $wardId = $properties['ward_no']
                   ?? $properties['WARD_NO']
                   ?? $properties['Ward_No']
                   ?? null;

            if ($wardId !== null) {
                $this->wards[$wardId] = [
                    'name'     => 'Ward ' . $wardId,
                    'geometry' => $feature['geometry']
                ];
            }
        }
    }

//       Detect Ward for a Point

    public function detectWard($latitude, $longitude)
    {
        foreach ($this->wards as $wardNo => $ward) {
            if ($this->pointInPolygon($latitude, $longitude, $ward['geometry'])) {
                return $wardNo;
            }
        }
        return null;
    }

    public function isWithinBhubaneswar($latitude, $longitude)
    {
        return $this->detectWard($latitude, $longitude) !== null;
    }

//       Geometry Logic

    private function pointInPolygon($lat, $lng, $geometry)
    {
        if ($geometry['type'] === 'Polygon') {
            return $this->pointInPolygonCoordinates($lat, $lng, $geometry['coordinates'][0]);
        }

        if ($geometry['type'] === 'MultiPolygon') {
            foreach ($geometry['coordinates'] as $polygon) {
                if ($this->pointInPolygonCoordinates($lat, $lng, $polygon[0])) {
                    return true;
                }
            }
        }
        return false;
    }

    private function pointInPolygonCoordinates($lat, $lng, $coordinates)
    {
        $x = $lng;
        $y = $lat;
        $inside = false;

        for ($i = 0, $j = count($coordinates) - 1; $i < count($coordinates); $j = $i++) {
            $xi = $coordinates[$i][0];
            $yi = $coordinates[$i][1];
            $xj = $coordinates[$j][0];
            $yj = $coordinates[$j][1];

            if ((($yi > $y) !== ($yj > $y)) &&
                ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi)) {
                $inside = !$inside;
            }
        }
        return $inside;
    }
}
