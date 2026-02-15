<?php
/**
 * Helper script to add Helakuru Sinhala font to TCPDF
 * 
 * Instructions:
 * 1. Download the Helakuru font TTF file (helakuru.ttf or FM-Abhaya.ttf)
 * 2. Place the font file in the same directory as this script
 * 3. Run this script from the command line: php add_helakuru_font.php
 * 
 * Note: You can download Helakuru fonts from:
 * - https://www.helakuru.lk/
 * - Look for FM-Abhaya.ttf which is commonly used for Sinhala
 */

// Check if running from command line
if (php_sapi_name() != 'cli') {
    die('This script must be run from the command line.');
}

echo "Helakuru Font Installer for TCPDF\n";
echo "==================================\n\n";

// Define possible font file names
$possibleFontFiles = [
    'helakuru.ttf',
    'FM-Abhaya.ttf',
    'Helakuru.ttf',
    'helakuru-regular.ttf',
    'Helakuru-Regular.ttf'
];

// Find the font file
$fontFile = null;
$scriptDir = __DIR__;

foreach ($possibleFontFiles as $filename) {
    $path = $scriptDir . '/' . $filename;
    if (file_exists($path)) {
        $fontFile = $path;
        echo "✓ Found font file: $filename\n";
        break;
    }
}

if (!$fontFile) {
    echo "✗ Error: Font file not found!\n\n";
    echo "Please download the Helakuru font and place one of these files in:\n";
    echo "  $scriptDir/\n\n";
    echo "Supported filenames:\n";
    foreach ($possibleFontFiles as $filename) {
        echo "  - $filename\n";
    }
    echo "\nYou can download Sinhala fonts from:\n";
    echo "  - https://www.helakuru.lk/\n";
    echo "  - https://www.google.com/get/noto/ (Noto Sans Sinhala)\n";
    exit(1);
}

// Path to TCPDF addfont tool
$tcpdfToolPath = __DIR__ . '/vendor/tecnickcom/tcpdf/tools/tcpdf_addfont.php';

if (!file_exists($tcpdfToolPath)) {
    echo "✗ Error: TCPDF font tool not found at: $tcpdfToolPath\n";
    exit(1);
}

// Build the command
$command = sprintf(
    'php "%s" -b -t TrueTypeUnicode -i "%s"',
    $tcpdfToolPath,
    $fontFile
);

echo "\nRunning font conversion...\n";
echo "Command: $command\n\n";

// Execute the command
$output = [];
$returnCode = 0;
exec($command . ' 2>&1', $output, $returnCode);

// Display output
foreach ($output as $line) {
    echo $line . "\n";
}

if ($returnCode === 0) {
    echo "\n✓ Font successfully added to TCPDF!\n";
    echo "\nYou can now use the font in your PDF by calling:\n";
    echo "  \$pdf->SetFont('helakuru', '', 11);\n";
    echo "\nor\n";
    echo "  \$pdf->SetFont('fmabhaya', '', 11);\n";
    echo "\n(depending on the font file name)\n";
} else {
    echo "\n✗ Font conversion failed with error code: $returnCode\n";
    echo "Please check the error messages above.\n";
    exit(1);
}
