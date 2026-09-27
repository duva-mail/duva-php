<?php

declare(strict_types=1);

// Fetches the public fixtures of `duva-mail/duva-conformance` (generated and tested in the
// `duva` repository: see `docs/bibliotheques-clientes.md` section 6). Never committed here (see
// `.gitignore`): always the freshest version, never a copy that could silently drift.
//
//   php scripts/fetch-conformance.php

$base = 'https://raw.githubusercontent.com/duva-mail/duva-conformance/main';
$files = ['webhooks.json', 'requests.json', 'retries.json'];
$outDir = __DIR__ . '/../conformance';

if (!is_dir($outDir)) {
    mkdir($outDir);
}

foreach ($files as $name) {
    $content = file_get_contents("{$base}/{$name}");
    if ($content === false) {
        fwrite(STDERR, "fetching {$name} failed\n");
        exit(1);
    }
    file_put_contents("{$outDir}/{$name}", $content);
    echo "conformance/{$name} fetched\n";
}
