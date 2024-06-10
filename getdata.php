<?php
$host = "localhost";
$username = "amamiya";
$dbname = "geojson";
$password = "amamiyakun02";

try {
    // Membuat koneksi PDO ke database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query untuk mengambil data dari tabel lokasi
    $sql = "SELECT id, nama_lokasi, jenis, ST_AsText(geom) AS geom FROM lokasi";
    $stmt = $pdo->query($sql);

    $features = [];

    // Mengambil data dari tabel dan memformatnya menjadi GeoJSON
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $geom = $row['geom'];
        $geomType = substr($geom, 0, strpos($geom, '('));
        $coordinates = substr($geom, strpos($geom, '(') + 1, -1);

        if ($geomType === 'POLYGON') {
            $polygon = [];
            $rings = explode('),(', $coordinates);
            foreach ($rings as $ring) {
                $points = explode(',', str_replace(['(', ')'], '', $ring));
                $polygon[] = array_map(function($point) {
                    return array_map('floatval', explode(' ', trim($point)));
                }, $points);
            }
            $coordinates = $polygon;
        } else if ($geomType === 'POINT') {
            $coordinates = array_map('floatval', explode(' ', $coordinates));
        }

        $features[] = [
            "type" => "Feature",
            "properties" => [
                "id" => $row['id'],
                "nama_lokasi" => $row['nama_lokasi'],
                "jenis" => $row['jenis']
            ],
            "geometry" => [
                "type" => "Polygon",
                "coordinates" => $coordinates
            ]
        ];
    }

    $geojson = [
        "type" => "FeatureCollection",
        "features" => $features
    ];

    // Mengembalikan data dalam format JSON
    header('Content-Type: application/json');
    echo json_encode($geojson, JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

?>