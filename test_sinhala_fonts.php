<?php
/**
 * Sinhala Font Test and Diagnostic Tool
 * This script helps you test which Sinhala fonts are available in TCPDF
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sinhala Font Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #692f69;
            border-bottom: 2px solid #692f69;
            padding-bottom: 10px;
        }
        .font-item {
            margin: 15px 0;
            padding: 15px;
            background: #f9f9f9;
            border-left: 4px solid #ddd;
            border-radius: 4px;
        }
        .font-item.available {
            border-left-color: #4CAF50;
            background: #f1f8f4;
        }
        .font-item.unavailable {
            border-left-color: #f44336;
            background: #fef5f5;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        .badge.available {
            background: #4CAF50;
            color: white;
        }
        .badge.unavailable {
            background: #f44336;
            color: white;
        }
        .badge.selected {
            background: #2196F3;
            color: white;
        }
        .sample-text {
            font-size: 16px;
            margin-top: 10px;
            padding: 10px;
            background: white;
            border-radius: 4px;
        }
        code {
            background: #e8e8e8;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .recommendation {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔤 Sinhala Font Availability Check</h1>
        
        <div class="info-box">
            <strong>ℹ️ About This Tool:</strong><br>
            This page checks which Sinhala-compatible fonts are installed in your TCPDF system.
            The consent form will automatically use the best available font.
        </div>

        <?php
        $fontDir = __DIR__ . '/vendor/tecnickcom/tcpdf/fonts/';
        
        $fonts = [
            'helakuru' => [
                'name' => 'Helakuru',
                'description' => 'Custom Helakuru font - Premium Sinhala appearance',
                'category' => 'Custom'
            ],
            'fmabhaya' => [
                'name' => 'FM-Abhaya',
                'description' => 'FM-Abhaya font - High-quality Sinhala rendering',
                'category' => 'Custom'
            ],
            'notosanssinhala' => [
                'name' => 'Noto Sans Sinhala',
                'description' => 'Google Noto Sans - Modern, clean Sinhala font',
                'category' => 'Custom'
            ],
            'freesans' => [
                'name' => 'FreeSans',
                'description' => 'Excellent Unicode coverage including Sinhala characters',
                'category' => 'Built-in'
            ],
            'dejavusans' => [
                'name' => 'DejaVu Sans',
                'description' => 'Comprehensive Unicode support with Sinhala',
                'category' => 'Built-in'
            ],
            'abhayalibre' => [
                'name' => 'Abhaya Libre',
                'description' => 'Designed specifically for Sinhala text',
                'category' => 'Built-in'
            ]
        ];

        // Determine which font will be selected
        $selectedFont = 'freesans';
        foreach (['helakuru', 'fmabhaya', 'fm-abhaya', 'notosanssinhala', 'freesans', 'dejavusans', 'abhayalibre'] as $fontName) {
            if (file_exists($fontDir . $fontName . '.php')) {
                $selectedFont = $fontName;
                break;
            }
        }

        echo "<h2>📊 Font Status</h2>";

        foreach ($fonts as $fontKey => $fontInfo) {
            $isAvailable = file_exists($fontDir . $fontKey . '.php');
            $isSelected = ($fontKey === $selectedFont);
            
            $availabilityClass = $isAvailable ? 'available' : 'unavailable';
            $availabilityBadge = $isAvailable ? 'INSTALLED' : 'NOT INSTALLED';
            $badgeClass = $isAvailable ? 'available' : 'unavailable';
            
            echo '<div class="font-item ' . $availabilityClass . '">';
            echo '<strong>' . htmlspecialchars($fontInfo['name']) . '</strong>';
            echo '<span class="badge ' . $badgeClass . '">' . $availabilityBadge . '</span>';
            
            if ($isSelected) {
                echo '<span class="badge selected">🌟 CURRENTLY USED</span>';
            }
            
            echo '<br>';
            echo '<small style="color: #666;">Category: ' . $fontInfo['category'] . '</small><br>';
            echo '<small>' . htmlspecialchars($fontInfo['description']) . '</small>';
            
            if ($isAvailable) {
                $fileSize = filesize($fontDir . $fontKey . '.php');
                echo '<br><small style="color: #4CAF50;">✓ Font file size: ' . number_format($fileSize / 1024, 1) . ' KB</small>';
            }
            
            echo '</div>';
        }
        ?>

        <div class="recommendation">
            <strong>📌 Current Configuration:</strong><br>
            Your consent forms are currently using: <code style="font-size: 14px; color: #2196F3;"><?php echo strtoupper($selectedFont); ?></code>
            <br><br>
            <?php if ($selectedFont === 'freesans' || $selectedFont === 'dejavusans'): ?>
                ✅ <strong>Good!</strong> This font has excellent Unicode support and should display Sinhala text correctly.
            <?php elseif ($selectedFont === 'abhayalibre'): ?>
                ✅ <strong>Good!</strong> Abhaya Libre is specifically designed for Sinhala text.
            <?php else: ?>
                ✅ <strong>Excellent!</strong> You're using a premium Sinhala font for the best appearance.
            <?php endif; ?>
        </div>

        <div class="sample-text">
            <strong>Sample Sinhala Text (how it displays in your browser):</strong><br>
            <div style="font-size: 18px; margin-top: 10px; line-height: 1.8;">
                ඉහත සඳහන් උපාංගය අලුත්වැඩියා කිරීම සඳහා MCYoma electronic වෙත ඉදිරිපත් කර ඇත.
            </div>
        </div>

        <h2>🛠️ Recommendations</h2>
        <ul>
            <li><strong>Current Setup:</strong> Your system will automatically use the best available font.</li>
            <li><strong>Font Size:</strong> Increased to 12pt for better Sinhala text readability.</li>
            <li><strong>Unicode Support:</strong> Font subsetting enabled for complete character coverage.</li>
            <li><strong>To Install Custom Fonts:</strong> Place TTF files in the project root and run <code>php add_helakuru_font.php</code></li>
        </ul>

        <hr style="margin: 30px 0;">
        
        <p style="text-align: center; color: #666;">
            <small>
                💡 <strong>Tip:</strong> Generate a consent form to see how the actual PDF renders Sinhala text.<br>
                The PDF rendering may differ slightly from browser display.
            </small>
        </p>
    </div>
</body>
</html>
