<?php

namespace Tests\Feature;

use Tests\TestCase;

class VendorDesignTokenComplianceTest extends TestCase
{
    /**
     * Test 1: Verify app.css defines all required single light theme semantic tokens.
     */
    public function test_app_css_defines_semantic_light_tokens(): void
    {
        $cssPath = resource_path('css/app.css');
        $this->assertFileExists($cssPath);
        $css = file_get_contents($cssPath);

        // Required semantic tokens from Phase 1 specification
        $this->assertStringContainsString('--bg: #F6F3EC;', $css, 'Canvas --bg token missing or incorrect');
        $this->assertStringContainsString('--surface: #FFFFFF;', $css, 'Surface --surface token missing or incorrect');
        $this->assertStringContainsString('--border: #E7E5E0;', $css, 'Border --border token missing or incorrect');
        $this->assertStringContainsString('--text: #1B1B19;', $css, 'Text --text token missing or incorrect');
        $this->assertStringContainsString('--text-muted: #5F5C55;', $css, 'Text muted --text-muted token missing or incorrect');
        $this->assertStringContainsString('--primary: #1B1B19;', $css, 'Primary --primary token missing or incorrect');
        $this->assertStringContainsString('--accent: #C6F432;', $css, 'Accent --accent token missing or incorrect');
        $this->assertStringContainsString('--accent-soft: #EEF9C4;', $css, 'Accent soft --accent-soft token missing or incorrect');
        $this->assertStringContainsString('--danger: #B42318;', $css, 'Danger --danger token missing or incorrect');
        $this->assertStringContainsString('--danger-bg: #FEE4E2;', $css, 'Danger soft bg missing or incorrect');
        $this->assertStringContainsString('--warning: #B54708;', $css, 'Warning --warning token missing or incorrect');
        $this->assertStringContainsString('--warning-bg: #FFFAEB;', $css, 'Warning soft bg missing or incorrect');
        $this->assertStringContainsString('--success: #067647;', $css, 'Success --success token missing or incorrect');
        $this->assertStringContainsString('--success-bg: #ECFDF3;', $css, 'Success soft bg missing or incorrect');
    }

    /**
     * Test 2: Verify WCAG 2.1 contrast ratios mathematically meet AA standards.
     */
    public function test_wcag_aa_contrast_ratios_mathematically_verified(): void
    {
        // 1. Text muted on Canvas (#F6F3EC) must be >= 4.5:1
        $contrastMutedOnCanvas = $this->calculateContrastRatio('#5F5C55', '#F6F3EC');
        $this->assertGreaterThanOrEqual(4.5, $contrastMutedOnCanvas, "text-muted on canvas contrast ratio {$contrastMutedOnCanvas}:1 is below 4.5:1");

        // 2. Text muted on Surface (#FFFFFF) must be >= 4.5:1
        $contrastMutedOnSurface = $this->calculateContrastRatio('#5F5C55', '#FFFFFF');
        $this->assertGreaterThanOrEqual(4.5, $contrastMutedOnSurface, "text-muted on surface contrast ratio {$contrastMutedOnSurface}:1 is below 4.5:1");

        // 3. Primary text on Canvas & Surface must be AAA (>= 7.0:1)
        $contrastTextOnCanvas = $this->calculateContrastRatio('#1B1B19', '#F6F3EC');
        $this->assertGreaterThanOrEqual(7.0, $contrastTextOnCanvas);

        $contrastTextOnSurface = $this->calculateContrastRatio('#1B1B19', '#FFFFFF');
        $this->assertGreaterThanOrEqual(7.0, $contrastTextOnSurface);

        // 4. White on Primary button (#1B1B19) must be AAA (>= 7.0:1)
        $contrastWhiteOnPrimary = $this->calculateContrastRatio('#FFFFFF', '#1B1B19');
        $this->assertGreaterThanOrEqual(7.0, $contrastWhiteOnPrimary);

        // 5. Dark text on Accent Lime (#C6F432) must be AAA (>= 7.0:1)
        $contrastDarkOnLime = $this->calculateContrastRatio('#1B1B19', '#C6F432');
        $this->assertGreaterThanOrEqual(7.0, $contrastDarkOnLime);

        // 6. Danger text on Danger Soft (#FEE4E2) must be >= 4.5:1
        $contrastDanger = $this->calculateContrastRatio('#B42318', '#FEE4E2');
        $this->assertGreaterThanOrEqual(4.5, $contrastDanger);

        // 7. Warning text on Warning Soft (#FFFAEB) must be >= 4.5:1
        $contrastWarning = $this->calculateContrastRatio('#B54708', '#FFFAEB');
        $this->assertGreaterThanOrEqual(4.5, $contrastWarning);

        // 8. Success text on Success Soft (#ECFDF3) must be >= 4.5:1
        $contrastSuccess = $this->calculateContrastRatio('#067647', '#ECFDF3');
        $this->assertGreaterThanOrEqual(4.5, $contrastSuccess);
    }

    /**
     * Test 3: Lint scan ensuring zero hardcoded hex or dark: classes in vendor UI directories.
     */
    public function test_zero_raw_hex_and_zero_dark_classes_in_vendor_ui(): void
    {
        $directories = [
            resource_path('views/filament/vendor'),
            app_path('Filament/Vendor'),
        ];

        $hexMatches = [];
        $darkMatches = [];
        $textWhiteMatches = [];

        foreach ($directories as $dir) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
            foreach ($files as $file) {
                if ($file->isDir()) {
                    continue;
                }

                $path = $file->getPathname();
                $content = file_get_contents($path);

                // Check for raw hex colors #[0-9a-fA-F]{3,8}
                if (preg_match_all('/#[0-9a-fA-F]{3,8}\b/', $content, $matches)) {
                    foreach ($matches[0] as $match) {
                        $hexMatches[] = "{$path}: {$match}";
                    }
                }

                // Check for dark: classes
                if (preg_match_all('/\bdark:[a-zA-Z0-9_\-\/]+/', $content, $matches)) {
                    foreach ($matches[0] as $match) {
                        $darkMatches[] = "{$path}: {$match}";
                    }
                }

                // Check for text-white
                if (preg_match_all('/\btext-white\b/', $content, $matches)) {
                    foreach ($matches[0] as $match) {
                        $textWhiteMatches[] = "{$path}: {$match}";
                    }
                }
            }
        }

        $this->assertEmpty($hexMatches, 'Found hardcoded hex in vendor UI: '.implode(', ', $hexMatches));
        $this->assertEmpty($darkMatches, 'Found dark: classes in vendor UI: '.implode(', ', $darkMatches));
        $this->assertEmpty($textWhiteMatches, 'Found hardcoded text-white in vendor UI: '.implode(', ', $textWhiteMatches));
    }

    /**
     * Helper to compute WCAG 2.1 relative luminance and contrast ratio.
     */
    private function calculateContrastRatio(string $hex1, string $hex2): float
    {
        $lum1 = $this->relativeLuminance($hex1);
        $lum2 = $this->relativeLuminance($hex2);

        $lighter = max($lum1, $lum2);
        $darker = min($lum1, $lum2);

        return round(($lighter + 0.05) / ($darker + 0.05), 2);
    }

    private function relativeLuminance(string $hex): float
    {
        $hex = ltrim($hex, '#');
        $rgb = [
            hexdec(substr($hex, 0, 2)) / 255.0,
            hexdec(substr($hex, 2, 2)) / 255.0,
            hexdec(substr($hex, 4, 2)) / 255.0,
        ];

        foreach ($rgb as $i => $val) {
            $rgb[$i] = ($val <= 0.04045)
                ? $val / 12.92
                : pow(($val + 0.055) / 1.055, 2.4);
        }

        return 0.2126 * $rgb[0] + 0.7152 * $rgb[1] + 0.0722 * $rgb[2];
    }
}
