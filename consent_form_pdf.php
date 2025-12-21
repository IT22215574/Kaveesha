<?php
require_once __DIR__ . '/config.php';
require_admin();
require_once __DIR__ . '/vendor/autoload.php'; // TCPDF

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
$date = $row['created_at'] ?: date('Y-m-d');
$listingTitle = $row['title'] ?: '';

// Build HTML template
$safeName = htmlspecialchars($customerName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeDate = htmlspecialchars($date, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeTitle = htmlspecialchars($listingTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$html = "<h2 style=\"text-align:center; color:#692f69;\">Consent Form</h2>";
$html .= "<p><strong>Job Number:</strong> " . (int)$listingId . "</p>";
$html .= "<p><strong>Customer Details</strong> " . "</p>";
$html .= "<p><strong>Name:</strong> " . $safeName . "</p>";
$html .= "<p><strong>Mobile Number:</strong> " . htmlspecialchars($row['mobile_number'] ?? '________________', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</p>";
$html .= "<p><strong>Address:_ _ _ _ _ _ _ _ _ _ _ _ _ _</strong> " . "</p>";

$html .= "<p><strong>Item Details</strong> " . "</p>";

$html .= "<p><strong>Listing Title:</strong> " . $safeTitle . "</p>";
$html .= "<p><strong>Serial Number:_ _ _ _ _ _ _ _ _ _ _ _ _ _</strong> " . "</p>";
$html .= "<p><strong>Nature of fault:_ _ _ _ _ _ _ _ _ _ _ _ _ _</strong> " . "</p>";
$html .= "<p><strong>Cause of fault:_ _ _ _ _ _ _ _ _ _ _ _ _ _</strong> " . "</p>";
$html .= "<p><strong>Describe:_ _ _ _ _ _ _ _ _ _ _ _ _ _</strong> " . "</p>";
$html .= "<p><strong>Date:</strong> " . $safeDate . "</p>";
$html .= "<p><strong>Date of repair:20_ _ _/_ _ _/_ _ _</strong> " . "</p>";

$html .= "<br><p>I, <strong>" . $safeName . "</strong>, hereby give my consent to MC YOMA electronic
 for the processing of my personal data for the purposes described in the company policy. I understand that 
 my data will be handled confidentially and in accordance with applicable laws.</p>";
$html .= "<br><p>Signature: ____________________________</p><p>Date: _______________________________</p>";

// Generate PDF
$pdf = new \TCPDF();
$pdf->SetCreator('MC YOMA electronic');
$pdf->SetAuthor('MC YOMA electronic');
$pdf->SetTitle('Consent Form - Listing ' . (int)$listingId);
$pdf->SetMargins(20, 20, 20);
$pdf->AddPage();
$pdf->writeHTML($html, true, false, true, false, '');

// Send download
$filename = 'consent_form_listing_' . (int)$listingId . '.pdf';
$pdf->Output($filename, 'D');
exit;
