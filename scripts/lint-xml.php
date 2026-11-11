<?php

declare(strict_types = 1);

if ($argc < 2) {
    fwrite(STDERR, "Usage: php scripts/lint-xml.php <file.xml> [file.xml ...]\n");

    exit(1);
}

libxml_use_internal_errors(true);

$exitCode = 0;

foreach (array_slice($argv, 1) as $file) {
    libxml_clear_errors();

    $document = new DOMDocument();

    if ($document->load($file, LIBXML_NONET)) {
        continue;
    }

    $exitCode = 1;

    fwrite(STDERR, sprintf("Invalid XML: %s\n", $file));

    foreach (libxml_get_errors() as $error) {
        fwrite(STDERR, sprintf("%s:%d: %s\n", $file, $error->line, trim($error->message)));
    }
}

exit($exitCode);
