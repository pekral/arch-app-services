<?php

declare(strict_types=1);

$coverageFile = 'coverage.xml';

if (!file_exists($coverageFile)) {
    echo "❌ Coverage file not found: {$coverageFile}\n";
    exit(1);
}

$coverage = simplexml_load_file($coverageFile);

if (!$coverage || !isset($coverage->project->metrics)) {
    echo "❌ Invalid coverage file format\n";
    exit(1);
}

$metrics = $coverage->project->metrics;
$linesCovered = (float) $metrics['coveredstatements'];
$linesValid = (float) $metrics['statements'];

if ($linesValid === 0.0) {
    echo "❌ No lines to cover found\n";
    exit(1);
}

$coveragePercentage = ($linesCovered / $linesValid) * 100;

if ($coveragePercentage < 100) {
    echo '❌ Coverage is ' . round($coveragePercentage, 2) . "% but required 100%\n";
    exit(1);
}

echo '✅ Coverage is ' . round($coveragePercentage, 2) . "%\n";
