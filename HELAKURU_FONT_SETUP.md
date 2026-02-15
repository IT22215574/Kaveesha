# Adding Helakuru Sinhala Font to Consent Form

## Overview
The consent form PDF now properly supports Sinhala text using the **Abhaya Libre** font, which is already built into TCPDF. The Sinhala text should display correctly without any additional setup.

For an even better appearance, you can optionally install the Helakuru font by following the steps below.

## Default Font (No Setup Required)
The system now uses **Abhaya Libre** by default, which provides excellent Sinhala Unicode support. Your consent forms should display Sinhala text correctly right away.

## Optional: Install Helakuru Font for Better Appearance

If you prefer the Helakuru font style, you can install it as follows:

## Installation Steps

### Step 1: Download the Helakuru Font
You can get the Helakuru font from one of these sources:

1. **Helakuru Official Website**: https://www.helakuru.lk/
   - Look for the font download section
   - Download the TTF file (usually `helakuru.ttf` or `FM-Abhaya.ttf`)

2. **Alternative Sinhala Fonts**:
   - Noto Sans Sinhala: https://www.google.com/get/noto/
   - FM-Abhaya from FontMirror

### Step 2: Place the Font File
1. Download the font file (e.g., `helakuru.ttf` or `FM-Abhaya.ttf`)
2. Place it in the main project directory:
   ```
   /Applications/XAMPP/xamppfiles/htdocs/Kaveesha/
   ```

### Step 3: Run the Font Installation Script
Open Terminal and run:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/Kaveesha/
php add_helakuru_font.php
```

The script will:
- Detect the font file
- Convert it to TCPDF format
- Install it in the TCPDF fonts directory
- Confirm successful installation

### Step 4: Test the Consent Form
1. Log in to the admin panel
2. Generate a consent form for any listing
3. The PDF should now use Helakuru font for Sinhala text

## Troubleshooting

### Font Not Found Error
If you see "Font file not found", make sure you:
- Downloaded the correct TTF file
- Placed it in the project root directory
- Named it one of: `helakuru.ttf`, `FM-Abhaya.ttf`, `Helakuru.ttf`

### Permission Issues
If you get permission errors, run:
```bash
chmod +x add_helakuru_font.php
```

### Font Conversion Failed
Make sure PHP CLI is installed and accessible:
```bash
php -v
```

### Still Using Default Font?
The system falls back to FreeSans if Helakuru isn't found. Check:
1. Run the installation script successfully
2. Verify the font files were created in:
   ```
   vendor/tecnickcom/tcpdf/fonts/
   ```
3. Look for files like `helakuru.php`, `helakuru.z`, etc.

## Supported Font File Names
The system will automatically detect these filenames:
- `helakuru.ttf`
- `FM-Abhaya.ttf`
- `Helakuru.ttf`
- `helakuru-regular.ttf`
- `Helakuru-Regular.ttf`

## Technical Details

### How It Works
1. The consent form checks for Helakuru font files at runtime
2. If found, it uses Helakuru for the best Sinhala rendering
3. If not found, it uses **Abhaya Libre** (built-in, excellent Sinhala support)
4. This ensures PDFs always generate with proper Sinhala text display

### Font Priority
1. `helakuru` (if installed - best appearance)
2. `fmabhaya` (if installed)
3. `abhayalibre` (default - built-in, excellent Sinhala support)
4. `notosanssinhala` (if installed)

### Testing Different Fonts
To test with a specific font, you can manually edit `consent_form_pdf.php`:
```php
$pdf->SetFont('your-font-name', '', 11, '', true);
```

## Notes
- **Abhaya Libre** is already installed and provides excellent Sinhala support
- The consent form will now display Sinhala text properly without any setup
- Helakuru font is optional and only needed if you prefer its specific style
- Font installation (if done) only needs to be done once
- All future consent forms will use the best available font
- The original consent form content remains unchanged
- Font changes don't require server restart
