<?php

function normalizeRegionName(string $name): string
{
    return match ($name) {
        'Dar-es-salaam' => 'Dar es Salaam',
        default => $name,
    };
}

$source = $argv[1] ?? dirname(__DIR__).'/storage/app/tanzania-all-flat.json';
$target = dirname(__DIR__).'/config/tanzania_locations.php';

if (! is_file($source)) {
    fwrite(STDERR, "Source file not found: {$source}\n");
    exit(1);
}

$json = json_decode(file_get_contents($source), true, 512, JSON_THROW_ON_ERROR);
$locations = [];

foreach ($json['data'] as $item) {
    if (($item['level'] ?? 0) === 1) {
        $name = normalizeRegionName($item['name']['en'] ?? $item['name']['local']);
        $locations[$name] = [];
    }
}

foreach ($json['data'] as $item) {
    if (($item['level'] ?? 0) === 2) {
        $region = normalizeRegionName($item['parent']['name']['en'] ?? $item['parent']['name']['local']);
        $district = $item['name']['en'] ?? $item['name']['local'];

        if (! isset($locations[$region])) {
            $locations[$region] = [];
        }

        $locations[$region][] = $district;
    }
}

ksort($locations);

foreach ($locations as &$districts) {
    sort($districts);
}

unset($districts);

$export = var_export($locations, true);
file_put_contents($target, "<?php\n\nreturn {$export};\n");

echo 'Wrote '.count($locations).' regions and '.array_sum(array_map('count', $locations))." districts\n";
