<?php
require_once __DIR__ . '/config.php';
require_admin();
require_once __DIR__ . '/vendor/autoload.php'; // Composer autoload (mPDF)

// Expect listing_id parameter
$listingId = isset($_GET['listing_id']) ? (int)$_GET['listing_id'] : 0;
if ($listingId <= 0) {
	http_response_code(400);
	echo 'Invalid listing id';
	exit;
}

// Fetch listing and user info
try {
	$stmt = db()->prepare('SELECT l.id, l.title, l.created_at, u.id AS user_id, u.username, u.mobile_number FROM listings l LEFT JOIN users u ON l.user_id = u.id WHERE l.id = ? LIMIT 1');
	$stmt->execute([$listingId]);
	$row = $stmt->fetch();
} catch (Throwable $e) {
	http_response_code(500);
	echo 'Database error';
	exit;
}

if (!$row) {
	http_response_code(404);
	echo 'Listing not found';
	exit;
}

$customerName = $row['username'] ?: '________________';
$date = $row['created_at'] ? substr($row['created_at'], 0, 10) : date('Y-m-d');
$listingTitle = $row['title'] ?: '';

// Ensure proper UTF-8 encoding
mb_internal_encoding('UTF-8');

// Build HTML template with proper Unicode handling
$safeName = htmlspecialchars($customerName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeDate = htmlspecialchars($date, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeTitle = htmlspecialchars($listingTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// Calculate deadline date (3 months from received date)
$dateObj = new DateTime($date);
$dateObj->add(new DateInterval('P3M'));
$deadlineDate = $dateObj->format('Y/m/d');
$safeDeadlineDate = htmlspecialchars($deadlineDate, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// Logo paths
$logoPath1 = __DIR__ . '/logo/logo1.png';
$logoPath2 = __DIR__ . '/logo/logo2.png';

// CSS for proper text rendering with MC Yomas branding
$css = '<style>
body { 
	font-family: "Noto Sans Sinhala", "Abhaya Libre", sans-serif; 
	line-height: 1.6;
	color: #333;
}
.header-section {
	text-align: center;
	margin-bottom: 20px;
	border-top: 6px solid #692f69;
	padding-top: 15px;
}
.logo-container {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 10px;
}
.section-header {
	background-color: #692f69;
	color: white;
	padding: 8px 15px;
	margin: 15px 0 10px 0;
	border-radius: 8px;
	font-weight: bold;
	font-size: 16px;
}
.field-row {
	margin: 8px 0;
	padding: 4px 0;
}
.field-label {
	font-weight: bold;
	display: inline-block;
}
.field-value {
	border-bottom: 1px dotted #666;
	display: inline-block;
	min-width: 300px;
	padding: 0 5px;
}
.two-column {
	display: table;
	width: 100%;
	margin: 8px 0;
}
.column {
	display: table-cell;
	width: 50%;
	padding-right: 10px;
}
.consent-text {
	text-align: justify;
	line-height: 1.8;
	margin: 10px 0;
	font-size: 19px;
}
.signature-section {
	margin-top: 30px;
}
hr.divider {
	border: 0;
	border-top: 2px solid #692f69;
	margin: 20px 0;
}
</style>';

$html = '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
' . $css . '
</head>
<body>';

// Header with logos
$html .= '<div class="header-section">';
$html .= '<table width="100%" style="margin-bottom: 15px;"><tr>';
$html .= '<td width="30%" align="left">';
if (file_exists($logoPath1)) {
	$html .= '<img src="' . $logoPath1 . '" height="60" />';
}
$html .= '</td>';
$html .= '<td width="40%" align="center" style="font-size: 20px; font-weight: bold; color: #692f69;">MC YOMAS ELECTRONIC</td>';
$html .= '<td width="30%" align="right">';
if (file_exists($logoPath2)) {
	$html .= '<img src="' . $logoPath2 . '" height="60" />';
}
$html .= '</td>';
$html .= '</tr></table>';
$html .= '</div>';

// Customer Details Section
$html .= '<div class="section-header">Customer Details</div>';
$html .= '<div class="field-row"><span class="field-label">Name - </span><span class="field-value">' . $safeName . '</span></div>';

$html .= '<div class="two-column">';
$html .= '<div class="column"><span class="field-label">Mobile number - </span><span class="field-value" style="min-width: 150px;">' . htmlspecialchars($row['mobile_number'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</span></div>';
$html .= '<div class="column"><span class="field-label">Whatsapp no - </span><span class="field-value" style="min-width: 150px;"></span></div>';
$html .= '</div>';

$html .= '<div class="field-row"><span class="field-label">Location - </span><span class="field-value"></span></div>';

$html .= '<hr class="divider" />';

// Item Details Section
$html .= '<div class="section-header">Item Details</div>';
$html .= '<div class="two-column">';
$html .= '<div class="column"><span class="field-label">Item - </span><span class="field-value" style="min-width: 150px;">' . $safeTitle . '</span></div>';
$html .= '<div class="column"><span class="field-label">Serial No - </span><span class="field-value" style="min-width: 150px;"></span></div>';
$html .= '</div>';

$html .= '<div class="field-row"><span class="field-label">Nature of fault - </span><span class="field-value"></span></div>';
$html .= '<div class="field-row"><span class="field-label">Cause of fault - </span><span class="field-value"></span></div>';
$html .= '<div class="field-row"><span class="field-label">Describe - </span><span class="field-value"></span></div>';
$html .= '<div style="height: 20px;"></div>';

$html .= '<div class="two-column">';
$html .= '<div class="column"><span class="field-label">Received date - </span><span class="field-value" style="min-width: 120px;">' . $safeDate .  '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; / </span></div>';
$html .= '<div class="column"><span class="field-label">Date of repair - </span><span class="field-value" style="min-width: 120px;">20......../............/...........  / </span></div>';
$html .= '</div>';

$html .= '<hr class="divider" />';

// Consensus Statement Section
$html .= '<div class="section-header">Cosensus statement</div>';
$html .= '<div class="consent-text">';
$html .= 'ඉහත සඳහන් උපාංගය අලුත්වැඩියා කිරීම සඳහා MCYoma electronic වෙත ඉදිරිපත් කර ඇත. ';
$html .= 'MCYoma electronic විසින් ලැබුණු දින <strong>' . $safeDate . '</strong> සිට මාස 3ක් ඇතුලත (<strong>' . $safeDeadlineDate . '</strong>) එය නැවත ලබා ගැනීම ඔබේ වගකීමක් වන අතර, මෙම කාල සීමාව තුළ ';
$html .= 'එය ලබා නොගත්හොත්, එය අතහැර දැමූ ලෙස සලකනු ලබන අතර එය MCYoma electronic ';
$html .= 'වෙතින් ලබා ගත නොහැකි බවට ඔබ එකඟ විය යුතුයි. ඔබ ලබා දෙන අයිතමය පරීක්ෂා කර ';
$html .= 'දෝෂ හඳුනා ගැනීමට දින 4 - 7 අතර කාලයක් ගත වන බවත්, එම කාලය තුළ කිසිදු කරදරකාරී ';
$html .= 'ඇමතුමක් ලබා නොදෙන බවත් ඔබ එකඟ විය යුතුයි. ඔබ සපයන නිෂ්පාදනයේ දෝෂ පරීක්ෂා ';
$html .= 'කර වාර්තා කිරීමට කාලය සහ වෑයම අවශ්‍ය වන බැවින්, නිෂ්පාදනය භාරදීමේදී රු .............. ';
$html .= 'ක සේවා ගාස්තුවක් ගෙවිය යුතුය. භාණ්ඩ භාරදීමේදී ඔබට රු .............. ක අත්තිකාරම් මුදලක් ';
$html .= 'ගෙවීමට ද අවශ්‍ය වේ. මෙම මුදල අලුත්වැඩියා ගාස්තුවෙන් අඩු කරනු ලබන අතර අලුත්වැඩියා ';
$html .= 'කළ නොහැකි අයිතම සඳහා ආපසු ගෙවනු ලබන බව කරුණාවෙන් සලකන්න. පරීක්ෂා කිරීම ';
$html .= 'සහ දෝෂ වාර්තා කිරීම අවසන් වූ පසු අපි ඔබට ඒ බව දන්වන්නෙමු.';
$html .= '</div>';

$html .= '<div style="margin-top: 15px; font-weight: bold; text-align: center; color: #692f69;">';
$html .= 'ඉහත වගකීම් සහ නියමයන් මා ගෙන පිළිගන්නා බව මෙයින් සහතික කරමි.';
$html .= '</div>';

$html .= '<div class="signature-section">';
$html .= '<div class="field-row"><span class="field-label">Signature: </span><span class="field-value" style="min-width: 200px;"></span></div>';
$html .= '<div class="field-row"><span class="field-label">Date: ' . $safeDate . '</span><span class="field-value" style="min-width: 200px;"></span></div>';
$html .= '</div>';

$html .= '</body></html>';

// Generate PDF using mPDF (better complex script support than TCPDF)
try {
	$mpdf = new \Mpdf\Mpdf([
		'mode' => 'utf-8',
		'format' => 'A4',
		'margin_left' => 20,
		'margin_right' => 20,
		'margin_top' => 20,
		'margin_bottom' => 20,
		'margin_header' => 0,
		'margin_footer' => 0,
		'default_font_size' => 12,
		'default_font' => 'dejavusans',
		'autoScriptToLang' => true,
		'autoLangToFont' => true,
		'useSubstitutions' => true,
		'tempDir' => __DIR__ . '/tmp'
	]);
	
	$mpdf->SetTitle('Consent Form - Listing ' . (int)$listingId);
	$mpdf->SetAuthor('MC YOMA electronic');
	$mpdf->SetCreator('MC YOMA electronic');
	
	// Write HTML content
	$mpdf->WriteHTML($html);
	
	// Output PDF for download
	$filename = 'consent_form_listing_' . (int)$listingId . '.pdf';
	$mpdf->Output($filename, 'D');
	
} catch (Exception $e) {
	http_response_code(500);
	echo 'PDF generation error: ' . htmlspecialchars($e->getMessage());
}

exit;
