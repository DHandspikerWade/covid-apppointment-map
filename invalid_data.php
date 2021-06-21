<?php 

$cache = json_decode(file_get_contents('location_cache.json'), true);
foreach ($cache as $key => $data) {
    if ($data['lat'] == 0 || $data['long'] == 0) {
        echo $key . PHP_EOL;
    }

    if (is_string($data['long'])) {
        $cache[$key]['long'] = (float) $data['long'];
    }

    if (is_string($data['lat'])) {
        $cache[$key]['lat'] = (float) $data['lat'];
    }
}

file_put_contents('location_cache.json', json_encode($cache, JSON_PRETTY_PRINT));