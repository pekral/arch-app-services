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

// PHPUnit HTML report is already generated in coverage-html folder
$htmlReportPath = 'coverage-html/index.html';

if ($coveragePercentage < 100) {
    echo "⚠️  Codebase is not 100% covered by tests: " . round($coveragePercentage, 2) . "%\n";
    
    if (file_exists($htmlReportPath)) {
        $htmlReportUrl = 'file://' . realpath($htmlReportPath);
        echo "📄 HTML report: {$htmlReportUrl}\n";
    }
    
    exit(1);
}

echo "✅ Codebase is 100% covered by tests: " . round($coveragePercentage, 2) . "%\n";