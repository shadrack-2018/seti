<?php
declare(strict_types=1);

namespace App\Services;

class MailService
{
    protected string $fromName;
    protected string $fromEmail;

    public function __construct(string $fromEmail = null, string $fromName = null)
    {
        $this->fromEmail = $fromEmail ?? ($_ENV['MAIL_FROM_ADDRESS'] ?? 'no-reply@setiplatform.local');
        $this->fromName = $fromName ?? ($_ENV['MAIL_FROM_NAME'] ?? 'SETI Platform');
    }

    public function send(string $to, string $subject, string $body, bool $isHtml = true): bool
    {
        $headers = [];
        $headers[] = 'From: ' . $this->fromName . ' <' . $this->fromEmail . '>';
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: ' . ($isHtml ? 'text/html; charset=utf-8' : 'text/plain; charset=utf-8');

        $headersStr = implode("\r\n", $headers);

        // For production use a robust mailer (SMTP via PHPMailer or Symfony Mailer). This is a minimal placeholder.
        return @mail($to, $subject, $body, $headersStr);
    }
}
