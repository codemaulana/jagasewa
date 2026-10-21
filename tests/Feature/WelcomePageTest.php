<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_page_renders_hero_text_and_cta_buttons(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        // Core Hero Headlines and Value Proposition
        $response->assertSee('Barang yang kamu butuhkan.');
        $response->assertSee('Waktu yang kamu tentukan.');
        $response->assertSee('Temukan dan booking barang rental dari berbagai bisnis, tanpa perlu membuat akun.');

        // CTA Buttons
        $response->assertSee('Jelajahi Rental');
        $response->assertSee('Gabung sebagai');
        $response->assertSee('Pemilik Rental');

        // Asset and Local Animation Script references
        $response->assertSee('js/gsap.min.js');
        $response->assertSee('js/ScrollTrigger.min.js');

        // Categories & Sections
        $response->assertSee('Jelajahi Berdasarkan Kategori');
        $response->assertSee('Kostum Cosplay Anime');
        $response->assertSee('Cara Kerja');
        $response->assertSee('Keunggulan');
    }

    public function test_csp_header_whitelists_animation_and_font_cdns(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertNotNull($csp);
        $this->assertStringContainsString('https://cdnjs.cloudflare.com', $csp);
        $this->assertStringContainsString('https://fonts.googleapis.com', $csp);
        $this->assertStringContainsString('https://fonts.gstatic.com', $csp);
    }
}
