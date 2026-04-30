<?php

// Detects Bhubaneswar ward information from local boundary data.
class BhubaneswarWardDetector
{
    private $wards = [];
    private $geoJsonData = null;

    // Initializes this object with the data it needs.
    public function __construct()
    {
        $this->loadGeoJSON();
        $this->setupWards();
    }


    //LOAD GEOJSON

    private function loadGeoJSON()
    {
        $path = __DIR__ . '/../../data/Wards.geojson';
        if (file_exists($path)) {
            $this->geoJsonData = json_decode(file_get_contents($path), true);
        }
    }


    //PREPARE WARDS

    private function setupWards()
    {
        if (!$this->geoJsonData || !isset($this->geoJsonData['features'])) {
            return;
        }

        foreach ($this->geoJsonData['features'] as $feature) {
            $props = $feature['properties'];

            if (!isset($props['wardno'])) {
                continue;
            }

            $key = strtolower(trim($props['wardno'])); // w9, w23

            $this->wards[$key] = [
                'ward_no'  => strtoupper($props['wardno']), // W9
                'zone'     => $props['municipalzone'] ?? 'Unknown',
                'geometry' => $feature['geometry']
            ];
        }
    }


    //       DETECT WARD

    public function detectWard($lat, $lng)
    {
        foreach ($this->wards as $key => $ward) {
            if ($this->pointInPolygon($lat, $lng, $ward['geometry'])) {
                // ✅ ALWAYS return uppercase ward
                return $ward['ward_no']; // W9, W23
            }
        }
        return null;
    }

    // Checks whether coordinates are inside Bhubaneswar boundaries.
    public function isWithinBhubaneswar($lat, $lng)
    {
        return $this->detectWard($lat, $lng) !== null;
    }

    // Returns details for a single ward.
    public function getWardDetails($ward)
    {
        $key = strtolower(trim($ward));
        return $this->wards[$key] ?? null;
    }

    // Returns all configured ward records.
    public function getAllWards()
    {
        return $this->wards;
    }


    //       POINT IN POLYGON

    private function pointInPolygon($lat, $lng, $geometry)
    {
        if ($geometry['type'] === 'Polygon') {
            return $this->rayCast($lat, $lng, $geometry['coordinates'][0]);
        }

        if ($geometry['type'] === 'MultiPolygon') {
            foreach ($geometry['coordinates'] as $polygon) {
                if ($this->rayCast($lat, $lng, $polygon[0])) {
                    return true;
                }
            }
        }

        return false;
    }

    // Uses ray casting to test if a point is inside a ring.
    private function rayCast($lat, $lng, $coords)
    {
        $inside = false;
        $x = $lng;
        $y = $lat;

        for ($i = 0, $j = count($coords) - 1; $i < count($coords); $j = $i++) {
            $xi = $coords[$i][0];
            $yi = $coords[$i][1];
            $xj = $coords[$j][0];
            $yj = $coords[$j][1];

            $intersect =
                (($yi > $y) !== ($yj > $y)) &&
                ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi);

            if ($intersect) {
                $inside = !$inside;
            }
        }

        return $inside;
    }
}
