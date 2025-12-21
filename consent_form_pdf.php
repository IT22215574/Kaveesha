<?php
// consent_form_pdf.php
// Generates a consent form PDF dynamically from HTML with placeholders for fields

require_once __DIR__ . '/vendor/autoload.php'; // TCPDF autoload

// Example: Replace these with real data from your app or form
$customerName = isset($_GET['name']) ? $_GET['name'] : '________________';
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$address = isset($_GET['address']) ? $_GET['address'] : '________________';

// HTML template for the consent form (customize as needed)
$html = <<<EOD
<h2 style="text-align:center; color:#692f69;">Consent Form</h2>
<p>Date: <strong>{$date}</strong></p>
<p>Name: <strong>{$customerName}</strong></p>
<p>Address: <strong>{$address}</strong></p>
<br>
<p>I, <strong>{$customerName}</strong>, hereby give my consent to Yoma Electronics for the processing of my personal data for the purposes described in the company policy. I understand that my data will be handled confidentially and in accordance with applicable laws.</p>
<br>
<p>Signature: ____________________________</p>
<p>Date: _______________________________</p>
EOD;

// Create new TCPDF instance
$pdf = new \TCPDF();
$pdf->SetCreator('Yoma Electronics');
$pdf->SetAuthor('Yoma Electronics');
$pdf->SetTitle('Consent Form');
$pdf->SetMargins(20, 20, 20);
$pdf->AddPage();
$pdf->writeHTML($html, true, false, true, false, '');

// Output PDF for download
$pdf->Output('consent_form.pdf', 'D'); // 'D' for download, 'I' for inline
exit;
