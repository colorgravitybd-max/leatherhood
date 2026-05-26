<?php
declare(strict_types=1);

namespace LH\Services;

use LH\Core\Database;

/**
 * Lightweight SMTP mailer (PHP-only, no Composer required).
 * Speaks AUTH LOGIN, supports STARTTLS, sends multipart/alternative.
 */
final class Mailer
{
    public static function send(string $to, string $subject, string $htmlBody, ?string $textBody = null, ?int $orderId = null): bool
    {
        $cfg = $GLOBALS['LH_CFG']['mail'];
        $logId = Database::i()->insert('email_logs', [
            'to_email' => $to,
            'subject'  => $subject,
            'template' => 'transactional',
            'body_excerpt' => mb_substr(strip_tags($htmlBody), 0, 280),
            'status'   => 'queued',
            'order_id' => $orderId,
        ]);

        try {
            self::smtpSend($cfg, $to, $subject, $htmlBody, $textBody ?: strip_tags($htmlBody));
            Database::i()->update('email_logs', ['status' => 'sent'], 'id = :_id', [':_id' => $logId]);
            return true;
        } catch (\Throwable $e) {
            Database::i()->update('email_logs', [
                'status' => 'failed',
                'error'  => substr($e->getMessage(), 0, 1000),
            ], 'id = :_id', [':_id' => $logId]);
            return false;
        }
    }

    private static function smtpSend(array $cfg, string $to, string $subject, string $html, string $text): void
    {
        $tls = $cfg['encryption'] === 'tls';
        $sock = @stream_socket_client(
            ($cfg['encryption'] === 'ssl' ? 'ssl://' : '').$cfg['host'].':'.$cfg['port'],
            $errno, $errstr, 30);
        if (!$sock) throw new \RuntimeException("SMTP connect: $errstr");

        $read  = fn() => fgets($sock, 1024);
        $write = function (string $cmd) use ($sock) {
            fwrite($sock, $cmd."\r\n");
        };

        $expect = function (int $code) use (&$read) {
            $line = $read();
            if (!$line || (int)substr($line,0,3) !== $code)
                throw new \RuntimeException("SMTP expected $code: ".trim((string)$line));
        };

        $read(); // greet
        $write('EHLO leatherhoodbd.com'); while (($l = $read()) && $l[3] === '-') {}
        if ($tls) {
            $write('STARTTLS'); $expect(220);
            stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $write('EHLO leatherhoodbd.com'); while (($l = $read()) && $l[3] === '-') {}
        }
        if (!empty($cfg['user'])) {
            $write('AUTH LOGIN'); $expect(334);
            $write(base64_encode($cfg['user'])); $expect(334);
            $write(base64_encode($cfg['pass'])); $expect(235);
        }
        $write('MAIL FROM:<'.$cfg['from'].'>'); $expect(250);
        $write('RCPT TO:<'.$to.'>');             $expect(250);
        $write('DATA');                          $expect(354);

        $boundary = 'lh_'.bin2hex(random_bytes(8));
        $headers  = [];
        $headers[] = 'From: '.self::encodeHeader($cfg['from_name']).' <'.$cfg['from'].'>';
        $headers[] = 'To: <'.$to.'>';
        $headers[] = 'Subject: '.self::encodeHeader($subject);
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: multipart/alternative; boundary="'.$boundary.'"';
        $headers[] = 'Date: '.date('r');
        $body  = "--$boundary\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n".$text."\r\n";
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n".$html."\r\n";
        $body .= "--$boundary--\r\n";
        $write(implode("\r\n", $headers)."\r\n\r\n".$body."\r\n.");
        $expect(250);
        $write('QUIT');
        fclose($sock);
    }

    private static function encodeHeader(string $s): string
    {
        return preg_match('/[^\x20-\x7E]/', $s)
            ? '=?UTF-8?B?'.base64_encode($s).'?='
            : $s;
    }
}
