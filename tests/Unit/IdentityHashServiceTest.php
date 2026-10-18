<?php

namespace Tests\Unit;

use App\Services\IdentityHashService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class IdentityHashServiceTest extends TestCase
{
    public function test_normalizes_indonesian_phone_numbers_consistently(): void
    {
        $expected = '6281234567890';

        $variations = [
            '081234567890',
            '+6281234567890',
            '+62 812-3456-7890',
            '6281234567890',
            '81234567890',
            '0812.3456.7890',
            '(0812) 3456 7890',
            ' 0812-3456-7890 ',
        ];

        foreach ($variations as $input) {
            $this->assertEquals(
                $expected,
                IdentityHashService::normalizePhone($input),
                "Failed normalizing phone variation: {$input}"
            );
        }
    }

    public function test_normalizes_nik_numbers(): void
    {
        $expected = '3271123456789012';

        $variations = [
            '3271 1234 5678 9012',
            '3271-1234-5678-9012',
            '3271123456789012',
            ' 3271 1234 5678 9012 ',
        ];

        foreach ($variations as $input) {
            $this->assertEquals(
                $expected,
                IdentityHashService::normalizeNik($input),
                "Failed normalizing NIK variation: {$input}"
            );
        }
    }

    public function test_normalizes_instagram_handles(): void
    {
        $expected = 'budirental';

        $variations = [
            '@BudiRental',
            'budiRental',
            'BUDIRENTAL',
            '  @budirental  ',
            'https://instagram.com/BudiRental/',
            'https://www.instagram.com/BudiRental?igsh=abc123xyz',
            'http://instagram.com/@budirental/',
            'instagram.com/BudiRental',
        ];

        foreach ($variations as $input) {
            $this->assertEquals(
                $expected,
                IdentityHashService::normalizeInstagram($input),
                "Failed normalizing Instagram variation: {$input}"
            );
        }
    }

    public function test_generic_normalize_handles_supported_types_and_aliases(): void
    {
        $this->assertEquals('6281234567890', IdentityHashService::normalize('wa', '081234567890'));
        $this->assertEquals('6281234567890', IdentityHashService::normalize('phone', '081234567890'));
        $this->assertEquals('6281234567890', IdentityHashService::normalize('whatsapp', '+62 812 3456 7890'));

        $this->assertEquals('3271123456789012', IdentityHashService::normalize('nik', '3271-1234-5678-9012'));

        $this->assertEquals('budirental', IdentityHashService::normalize('instagram', '@BudiRental'));
        $this->assertEquals('budirental', IdentityHashService::normalize('ig', 'https://instagram.com/budirental'));
    }

    public function test_generic_normalize_throws_exception_on_unsupported_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported identity type: twitter');

        IdentityHashService::normalize('twitter', '@budirental');
    }

    public function test_hash_is_deterministic_and_sha256_format(): void
    {
        $secret = 'test-secret-key-12345';

        $hash1 = IdentityHashService::hashIdentity('wa', '081234567890', $secret);
        $hash2 = IdentityHashService::hashIdentity('wa', '+62 812-3456-7890', $secret);
        $hash3 = IdentityHashService::hashIdentity('wa', '6281234567890', $secret);

        // Deterministic: all variations produce identical hash
        $this->assertEquals($hash1, $hash2);
        $this->assertEquals($hash1, $hash3);

        // SHA-256 output is 64 hex characters
        $this->assertEquals(64, strlen($hash1));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash1);
    }

    public function test_different_inputs_produce_different_hashes(): void
    {
        $secret = 'test-secret-key-12345';

        $hashA = IdentityHashService::hashIdentity('wa', '081234567890', $secret);
        $hashB = IdentityHashService::hashIdentity('wa', '081234567891', $secret);

        $this->assertNotEquals($hashA, $hashB);
    }

    public function test_matches_correctly_verifies_raw_input_against_stored_hash(): void
    {
        $secret = 'test-secret-key-12345';
        $storedHash = IdentityHashService::hashIdentity('wa', '081234567890', $secret);

        $this->assertTrue(IdentityHashService::matches('wa', '+62 812-3456-7890', $storedHash, $secret));
        $this->assertTrue(IdentityHashService::matches('wa', '081234567890', $storedHash, $secret));
        $this->assertFalse(IdentityHashService::matches('wa', '089999999999', $storedHash, $secret));
    }

    public function test_instagram_hash_determinism(): void
    {
        $secret = 'test-secret-key-12345';

        $hash1 = IdentityHashService::hashIdentity('instagram', '@BudiRental', $secret);
        $hash2 = IdentityHashService::hashIdentity('instagram', 'https://instagram.com/budirental/', $secret);

        $this->assertEquals($hash1, $hash2);
    }

    public function test_nik_hash_determinism(): void
    {
        $secret = 'test-secret-key-12345';

        $hash1 = IdentityHashService::hashIdentity('nik', '3271 1234 5678 9012', $secret);
        $hash2 = IdentityHashService::hashIdentity('nik', '3271123456789012', $secret);

        $this->assertEquals($hash1, $hash2);
    }
}
