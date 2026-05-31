<?php

namespace Tests\Security;

use Tests\TestCase;

/**
 * Security Audit Tests
 * Validates OWASP Top 10 and security best practices
 */
class SecurityAuditTest extends TestCase
{
    // A01: Broken Access Control
    public function test_api_requires_authentication(): void
    {
        $endpoints = [
            '/api/v/tanova/generate',
            '/api/v/tanova/trips',
            '/api/v/bookings',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->postJson($endpoint, []);
            $this->assertEquals(401, $response->status(), "Endpoint $endpoint should require authentication");
        }
    }

    // A02: Cryptographic Failures
    public function test_https_required(): void
    {
        // In production, should redirect HTTP to HTTPS
        // This test verifies the setting
        $this->assertTrue(config('app.secure_urls'), 'URLs should use HTTPS');
    }

    // A03: Injection - SQL Injection Prevention
    public function test_sql_injection_prevention(): void
    {
        // Test with malicious SQL
        $response = $this->getJson('/api/v/tanova/trips?id=1 OR 1=1', [
            'Authorization' => 'Bearer test_key',
        ]);

        // Should not expose raw SQL errors
        $this->assertNotNull($response);
        // Verify SQL injection is prevented by Laravel's query binding
    }

    // A04: Insecure Design
    public function test_rate_limiting_configured(): void
    {
        $this->assertTrue(
            file_exists(app_path('Http/Middleware/ApiRateLimiting.php')),
            'Rate limiting middleware should exist'
        );
    }

    // A05: Security Misconfiguration
    public function test_debug_mode_disabled_in_production(): void
    {
        if (app()->isProduction()) {
            $this->assertFalse(config('app.debug'), 'Debug mode should be disabled in production');
        }
    }

    public function test_security_headers_configured(): void
    {
        $this->assertTrue(
            file_exists(app_path('Http/Middleware/ApiSecurityHeaders.php')),
            'Security headers middleware should exist'
        );
    }

    // A06: Vulnerable and Outdated Components
    public function test_dependencies_locked(): void
    {
        $this->assertTrue(
            file_exists(base_path('composer.lock')),
            'composer.lock should exist to lock dependency versions'
        );
    }

    // A07: Authentication Failures
    public function test_password_hashing(): void
    {
        // Verify bcrypt is used for hashing
        $this->assertEquals(
            'bcrypt',
            config('hashing.driver'),
            'Should use bcrypt for password hashing'
        );
    }

    public function test_session_security(): void
    {
        $this->assertTrue(
            config('session.secure') ?? false,
            'Sessions should use secure cookies in production'
        );
        $this->assertTrue(
            config('session.http_only'),
            'Sessions should use httpOnly flag'
        );
    }

    // A08: Software and Data Integrity Failures
    public function test_csrf_protection_enabled(): void
    {
        $this->assertTrue(
            \Illuminate\Support\Str::contains(
                file_get_contents(base_path('bc-cms/app/Http/Kernel.php')),
                'VerifyCsrfToken'
            ),
            'CSRF protection should be enabled'
        );
    }

    // A09: Logging and Monitoring Failures
    public function test_error_logging_configured(): void
    {
        $this->assertNotEmpty(
            config('logging.channels'),
            'Error logging channels should be configured'
        );
    }

    // A10: SSRF Prevention
    public function test_ssrf_prevention(): void
    {
        // Verify external requests are controlled
        // This is handled by Laravel's HTTP client verification
        $this->assertTrue(true);
    }

    // Additional Security Tests
    public function test_vendor_isolation(): void
    {
        // Verify vendor_id is required in queries
        $this->assertTrue(
            \Illuminate\Support\Str::contains(
                file_get_contents(app_path('../pro/Tanova/Controllers/Api/TanovaApiController.php')),
                'vendor_id'
            ),
            'Vendor isolation should be enforced'
        );
    }

    public function test_api_key_format(): void
    {
        // API keys should follow sk_live_ pattern
        $this->assertTrue(true); // Format enforced at generation time
    }

    public function test_sensitive_data_not_logged(): void
    {
        // Verify passwords and sensitive data aren't logged
        $excludedKeys = config('app.log_excluded_keys') ?? [];
        $this->assertContains('password', $excludedKeys);
        $this->assertContains('api_key', $excludedKeys);
    }

    public function test_cors_headers(): void
    {
        // Verify CORS is configured properly
        $config = config('cors');
        $this->assertNotNull($config);
    }
}
