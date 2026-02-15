<?php
/**
 * Corrected Sinhala Text for Consent Form
 * 
 * The original text had malformed Unicode - vowel signs (ෙ, ේ, ො, etc.) 
 * were appearing BEFORE consonants instead of after them.
 * 
 * In proper Sinhala Unicode:
 * - Base consonant comes FIRST
 * - Combining marks/vowel signs come AFTER
 * 
 * Example:
 * ❌ Wrong: ෙව + ත = displays as boxes
 * ✅ Correct: ව + ෙ + ත = වෙත (displays properly)
 */

// CORRECTED SINHALA TEXT - Copy this into consent_form_pdf.php line 69

$correctedText = 'ඉහත සඳහන් උපාංගය අලුත්වැඩියා කිරීම සඳහා MCYoma electronic වෙත ඉදිරිපත් කර ඇත.
MCYoma electronic විසින් ලැබුණු දින <strong>Date:</strong> ' . $safeDate . ' සිට මාස 3ක් ඇතුලත (20......../............/...........) එය නැවත ලබා ගැනීම ඔබේ වගකීමක් වන අතර, මෙම කාල සීමාව තුළ
එය ලබා නොගතහොත්, එය අතහැර දැමූ ලෙස සලකනු ලබන අතර එය MCYoma electronic
වෙතින් ලබා ගත නොහැකි බවට ඔබ එකඟ විය යුතුයි. ඔබ ලබා දෙන අයිතමය පරීක්ෂා කර
දෝෂ හඳුනා ගැනීමට දින 4 - 7 අතර කාලයක් ගත වන බවත්, එම කාලය තුළ කිසිදු කරදරකාරී
ඇමතුමක් ලබා නොදෙන බවත් ඔබ එකඟ විය යුතුයි. ඔබ සපයන නිෂ්පාදනේ දෝෂ පරීක්ෂා
කර වාර්තා කිරීමට කාලය සහ වෑයම අවශ්‍ය වන බැවින්, නිෂ්පාදනය භාරදීමේදී රු ..…….......…..
ක සේවා ගාස්තුවක් ගෙවිය යුතුය. භාණ්ඩ භාරදීමේදී ඔබට රු ....……....….. ක අත්තිකාරම් මුදලක්
ගෙවීමට ද අවශ්‍ය වේ. මෙම මුදල අලුත්වැඩියා ගාස්තුවෙන් අඩු කරනු ලබන අතර අලුත්වැඩියා
කළ නොහැකි අයිතම සඳහා ආපසු ගෙවනු ලබන බව කරුණාවෙන් සලකන්න. පරීක්ෂා කිරීම
සහ දෝෂ වාර්තා කිරීම අවසන් වූ පසු අපි ඔබට ඒ බව දන්වන්නෙමු.';

$correctedFinalStatement = 'ඉහත වගකීම් සහ නියමයන් මා හරහා ගෙන පිළිගන්නා බව මෙයින් සහතික කරමි.';

echo "<!DOCTYPE html>
<html lang='si'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Corrected Sinhala Text</title>
    <style>
        body {
            font-family: 'Noto Sans Sinhala', 'Iskoola Pota', sans-serif;
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
            line-height: 1.8;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #692f69;
            border-bottom: 3px solid #692f69;
            padding-bottom: 10px;
        }
        .error-box {
            background: #ffebee;
            border-left: 4px solid #f44336;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .success-box {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .sinhala-text {
            font-size: 18px;
            line-height: 2;
            padding: 20px;
            background: #fafafa;
            border-radius: 4px;
            margin: 15px 0;
        }
        .comparison {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        .comparison > div {
            padding: 15px;
            border-radius: 4px;
        }
        .wrong {
            background: #ffebee;
        }
        .correct {
            background: #e8f5e9;
        }
        code {
            background: #263238;
            color: #aed581;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        pre {
            background: #263238;
            color: #aed581;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            line-height: 1.5;
        }
        .step {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .step-number {
            display: inline-block;
            background: #ff9800;
            color: white;
            width: 30px;
            height: 30px;
            line-height: 30px;
            text-align: center;
            border-radius: 50%;
            margin-right: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔤 Corrected Sinhala Text for Consent Form</h1>
        
        <div class='error-box'>
            <strong>⚠️ Problem Found:</strong><br>
            The original Sinhala text had <strong>malformed Unicode</strong>. The vowel signs (combining marks) 
            were appearing BEFORE the base consonants instead of AFTER them, causing the text to display as boxes/squares.
        </div>
        
        <div class='info-box'>
            <strong>📝 How Sinhala Unicode Works:</strong><br>
            In proper Sinhala Unicode text:<br>
            1. Base consonant comes FIRST<br>
            2. Combining marks/vowel signs come AFTER<br>
            3. The rendering engine positions them visually
        </div>
        
        <h2>Example of the Problem:</h2>
        <div class='comparison'>
            <div class='wrong'>
                <strong>❌ Wrong Order:</strong><br>
                <code>ෙ</code> + <code>ව</code> + <code>ත</code><br>
                (vowel BEFORE consonant)<br>
                Result: Displays as boxes ▯▯▯
            </div>
            <div class='correct'>
                <strong>✅ Correct Order:</strong><br>
                <code>ව</code> + <code>ෙ</code> + <code>ත</code><br>
                (consonant BEFORE vowel)<br>
                Result: <span style='font-size: 24px;'>වෙත</span>
            </div>
        </div>
        
        <h2>📋 Corrected Sinhala Text:</h2>
        <div class='sinhala-text'>
            {$correctedText}
        </div>
        
        <h2>📋 Corrected Final Statement:</h2>
        <div class='sinhala-text'>
            <strong>{$correctedFinalStatement}</strong>
        </div>
        
        <h2>🛠️ How to Fix:</h2>
        
        <div class='step'>
            <span class='step-number'>1</span>
            <strong>Open consent_form_pdf.php</strong> in your editor
        </div>
        
        <div class='step'>
            <span class='step-number'>2</span>
            <strong>Find line 69</strong> (the line starting with <code>\$html .= \"&lt;br&gt;&lt;p&gt;ඉහත...\"</code>)
        </div>
        
        <div class='step'>
            <span class='step-number'>3</span>
            <strong>Replace the Sinhala text</strong> with the corrected version from the boxes above
        </div>
        
        <div class='step'>
            <span class='step-number'>4</span>
            <strong>Save the file</strong> and regenerate the PDF
        </div>
        
        <div class='success-box'>
            <strong>✅ Alternative Solution:</strong><br>
            I can automatically apply the fix for you. The corrected text is ready to be inserted into your PHP file.
            Just confirm and I'll update the file with properly formatted Sinhala Unicode text.
        </div>
        
        <h2>🔍 Common Sinhala Unicode Issues:</h2>
        <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
            <tr style='background: #f5f5f5;'>
                <th style='padding: 10px; text-align: left; border: 1px solid #ddd;'>Wrong</th>
                <th style='padding: 10px; text-align: left; border: 1px solid #ddd;'>Correct</th>
                <th style='padding: 10px; text-align: left; border: 1px solid #ddd;'>Word</th>
            </tr>
            <tr>
                <td style='padding: 10px; border: 1px solid #ddd;'><code>ෙව + ත</code></td>
                <td style='padding: 10px; border: 1px solid #ddd;'><code>ව + ෙ + ත</code></td>
                <td style='padding: 10px; border: 1px solid #ddd; font-size: 20px;'>වෙත</td>
            </tr>
            <tr>
                <td style='padding: 10px; border: 1px solid #ddd;'><code>ෙම + ම</code></td>
                <td style='padding: 10px; border: 1px solid #ddd;'><code>ම + ෙ + ම</code></td>
                <td style='padding: 10px; border: 1px solid #ddd; font-size: 20px;'>මෙම</td>
            </tr>
            <tr>
                <td style='padding: 10px; border: 1px solid #ddd;'><code>ෙද + ෂ</code></td>
                <td style='padding: 10px; border: 1px solid #ddd;'><code>ද + ෝ + ෂ</code></td>
                <td style='padding: 10px; border: 1px solid #ddd; font-size: 20px;'>දෝෂ</td>
            </tr>
        </table>
        
        <div class='info-box'>
            <strong>💡 Tip:</strong> Use a proper Sinhala keyboard input method (like Helakuru or Google Sinhala Input) 
            to avoid these Unicode ordering issues in the future.
        </div>
    </div>
</body>
</html>";
?>
