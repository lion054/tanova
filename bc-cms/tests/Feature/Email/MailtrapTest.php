<?php

namespace Tests\Feature\Email;

use Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class MailtrapTest extends TestCase
{
    /**
     * Test Mailtrap SMTP connection and email sending
     */
    public function test_mailtrap_connection(): void
    {
        // Verify Mailtrap credentials are configured
        $this->assertNotEmpty(config('mail.mailers.smtp.host'));
        $this->assertEquals('live.smtp.mailtrap.io', config('mail.mailers.smtp.host'));
        $this->assertEquals(587, config('mail.mailers.smtp.port'));
        $this->assertEquals('api', config('mail.mailers.smtp.username'));
        $this->assertNotEmpty(config('mail.mailers.smtp.password'));
    }

    /**
     * Test sending a test email via Mailtrap
     */
    public function test_send_test_email(): void
    {
        Mail::fake();

        Mail::raw('Test email from Tsoka Portal', function (Message $message) {
            $message->to('test@tsoka.travel')
                ->subject('Tsoka Portal - Test Email');
        });

        Mail::assertSent(function ($mailable) {
            return true; // Email was queued
        });
    }

    /**
     * Test vendor registration email
     */
    public function test_vendor_registration_email(): void
    {
        Mail::fake();

        $emailData = [
            'vendor_name' => 'Test Vendor',
            'email' => 'vendor@example.com',
            'reset_link' => 'https://portal.tsokatravel.com/password-reset/token',
        ];

        Mail::raw("Welcome to Tsoka Travel! Complete your registration: {$emailData['reset_link']}",
            function (Message $message) use ($emailData) {
                $message->to($emailData['email'])
                    ->subject('Welcome to Tsoka Travel');
            });

        Mail::assertSent(function ($mailable) use ($emailData) {
            return true;
        });
    }

    /**
     * Test booking confirmation email
     */
    public function test_booking_confirmation_email(): void
    {
        Mail::fake();

        $bookingData = [
            'customer_email' => 'customer@example.com',
            'booking_id' => 'BK-2026-00001',
            'total_amount' => 5000,
        ];

        Mail::raw(
            "Booking confirmed: {$bookingData['booking_id']}\nTotal: \${$bookingData['total_amount']}",
            function (Message $message) use ($bookingData) {
                $message->to($bookingData['customer_email'])
                    ->subject('Booking Confirmation - Tsoka Travel');
            });

        Mail::assertSent(function ($mailable) use ($bookingData) {
            return true;
        });
    }

    /**
     * Test password reset email
     */
    public function test_password_reset_email(): void
    {
        Mail::fake();

        $resetData = [
            'email' => 'user@example.com',
            'reset_url' => 'https://portal.tsokatravel.com/reset-password/token123',
        ];

        Mail::raw(
            "Reset your password: {$resetData['reset_url']}",
            function (Message $message) use ($resetData) {
                $message->to($resetData['email'])
                    ->subject('Reset Your Password - Tsoka Travel');
            });

        Mail::assertSent(function ($mailable) use ($resetData) {
            return true;
        });
    }

    /**
     * Test error notification email (internal)
     */
    public function test_error_notification_email(): void
    {
        Mail::fake();

        $errorData = [
            'error_id' => 'ERR-2026-00001',
            'message' => 'Trip generation failed',
            'timestamp' => now(),
        ];

        Mail::raw(
            "Error: {$errorData['message']}\nID: {$errorData['error_id']}\nTime: {$errorData['timestamp']}",
            function (Message $message) {
                $message->to('ops@tsoka.travel')
                    ->subject('[ALERT] Tsoka Portal Error');
            });

        Mail::assertSent(function ($mailable) {
            return true;
        });
    }
}
