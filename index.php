<?php
require 'lib/flight/Flight.php';

Flight::route('GET /', function(){
    require 'views/landing.php';
});

Flight::route('GET /map', function(){
    require 'views/home.php';
});

Flight::route('GET /api/communes', function(){
    require_once 'config/db.php';
    $db = getDB();

    $type   = isset($_GET['type'])   ? $_GET['type']   : 'contain';
    $search = isset($_GET['search']) ? $_GET['search'] : '';

    switch ($type) {
        case 'start': $like = $search . '%';       break;
        case 'end':   $like = '%' . $search;       break;
        default:      $like = '%' . $search . '%'; break;
    }

    // Utilise ? au lieu de :search pour éviter les problèmes PDO en sous-requête
    $sql = "
        SELECT nom, departement_insee, lon, lat
        FROM (
            SELECT
                nom,
                departement_insee,
                ST_X(ST_Centroid(ST_GeomFromText(ST_AsText(geometry)))) AS lon,
                ST_Y(ST_Centroid(ST_GeomFromText(ST_AsText(geometry)))) AS lat
            FROM communes
            WHERE nom LIKE ?
            AND ST_IsValid(geometry) = 1
            ORDER BY nom
        ) AS sub
        LIMIT 500
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([$like]);

    $features = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $lat = (float)$row['lat'];
        $lon = (float)$row['lon'];
        if ($lat == 0 && $lon == 0) continue;
        if ($lat < -90  || $lat > 90)  continue;
        if ($lon < -180 || $lon > 180) continue;
        $features[] = [
            'type'     => 'Feature',
            'geometry' => ['type' => 'Point', 'coordinates' => [$lon, $lat]],
            'properties' => [
                'nom'  => $row['nom'],
                'dept' => $row['departement_insee']
            ]
        ];
    }

    Flight::json(['type' => 'FeatureCollection', 'features' => $features]);
});

Flight::route('GET /api/suggestions', function(){
    require_once 'config/db.php';
    $db = getDB();

    $q    = isset($_GET['q'])    ? $_GET['q']    : '';
    $type = isset($_GET['type']) ? $_GET['type'] : 'contain';

    if (strlen(trim($q)) < 1) { Flight::json([]); return; }

    switch ($type) {
        case 'start': $like = $q . '%';       break;
        case 'end':   $like = '%' . $q;       break;
        default:      $like = '%' . $q . '%'; break;
    }

    $sql = "
        SELECT nom, departement_insee
        FROM communes
        WHERE nom LIKE ?
        ORDER BY nom
        LIMIT 15
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([$like]);

    $results = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $results[] = ['nom' => $row['nom'], 'dept' => $row['departement_insee']];
    }

    Flight::json($results);
});

Flight::start();