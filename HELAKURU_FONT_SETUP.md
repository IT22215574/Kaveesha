# Sinhala Font Configuration for Consent Forms

## Overview
The consent form PDF now has **enhanced Unicode support** for proper Sinhala text rendering. The system automatically uses the best available font with the following improvements:

✅ **Unicode font subsetting** - Ensures all Sinhala characters are included  
✅ **FreeSans default** - Excellent Unicode coverage including full Sinhala support  
✅ **Larger font size** (12pt) - Better readability for Sinhala text  
✅ **Proper UTF-8 encoding** - Correct handling of Unicode characters  
✅ **CSS text rendering** - Optimized layout and spacing

## Current Default Setup (Works Immediately)
The system now uses **FreeSans** by default, which provides excellent Unicode coverage including complete Sinhala character support. Your consent forms should display properly without any additional configuration.

### Test Your Fonts
You can check which fonts are available by visiting:
```
http://localhost/test_sinhala_fonts.php
```

This diagnostic page shows:
- Which fonts are installed
- Which font is currently being used
- Font availability status
- Sample Sinhala text rendering

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
   /Applications/XAMPP/xamppfiles/htdocs/
   ```

### Step 3: Run the Font Installation Script
Open Terminal and run:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/
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
1. **UTF-8 encoding** is enforced with `mb_internal_encoding('UTF-8')`
2. **Font subsetting** is enabled to embed all required Unicode characters
3. The system checks for custom fonts (Helakuru, FM-Abhaya, Noto Sans)
4. If no custom fonts found, uses **FreeSans** (excellent Unicode + Sinhala support)
5. Font size set to **12pt** for optimal Sinhala text readability
6. CSS styles added for proper text layout and spacing
7. All text is properly HTML-escaped while preserving UTF-8 encoding

### Technical Improvements
- **Unicode Subsetting**: `$pdf->setFontSubsetting(true)` ensures all Sinhala characters are embedded
- **Larger Font**: 12pt instead of 11pt for better readability
- **UTF-8 Internal Encoding**: Forces proper multibyte character handling
- **CSS Styling**: Adds line-height and justification for better text flow
- **Better Default**: FreeSans has 807KB character map vs smaller fonts

### Font Priority (Automatic Selection)
The system checks for fonts in this order and uses the first one found:

1. **Helakuru** (if installed) - Premium Sinhala appearance
2. **FM-Abhaya** (if installed) - High-quality Sinhala rendering  
3. **Noto Sans Sinhala** (if installed) - Modern, clean Google font
4. **FreeSans** (built-in, DEFAULT) - Excellent Unicode + Sinhala support
5. **DejaVu Sans** (built-in) - Comprehensive Unicode coverage
6. **Abhaya Libre** (built-in) - Sinhala-specific font

### What's New
- ✨ **Font subsetting enabled** - All required Unicode characters are embedded
- 📏 **Font size increased** to 12pt (from 11pt) for better readability
- 🎨 **CSS styling added** - Improved text layout and spacing
- 🔤 **UTF-8 encoding enforced** - Proper Unicode character handling
- 🔄 **Better font fallback** - Prioritizes FreeSans for best Unicode coverage

### Testing Different Fonts
To test with a specific font, you can manually edit `consent_form_pdf.php`:
```php
$pdf->SetFont('your-font-name', '', 11, '', true);
```

## Quick Start

### Just Generate the PDF ✅
No setup needed! Simply:
1. Go to admin panel
2. Generate a consent form
3. Sinhala text should display correctly using FreeSans

### Check Your Configuration
Visit: `http://localhost/test_sinhala_fonts.php`

This shows:
- Which font is currently being used
- Which fonts are available
- Font installation status
- Sample Sinhala text

## Notes
- ✅ **FreeSans** is pre-installed with excellent Unicode/Sinhala support
- ✅ **No setup required** - works immediately with proper Sinhala rendering
- ✅ **Automatic font selection** - uses the best available font
- ✅ **Font size optimized** at 12pt for better Sinhala text readability
- ✅ **Unicode subsetting enabled** - all characters properly embedded
- 💡 **Custom fonts optional** - only install if you want a specific style (Helakuru)
- 🔄 **No server restart needed** - changes take effect immediately
