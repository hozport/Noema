<?php

namespace App\Mail\Transport;

use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Message;

/**
 * Отправка через встроенную PHP-функцию mail()
 *
 * Использует уже собранное MIME-сообщение Laravel/Symfony: тема и тело передаются в mail(),
 * остальные заголовки — четвёртым аргументом. Удобно для коротких тестов на ВПС при настроенном
 * sendmail_path или локальном MTA; для продакшена предпочтительнее SMTP или транзакционный API.
 */
class PhpMailTransport extends AbstractTransport
{
    /**
     * Отправляет одно письмо через mail()
     *
     * @param  SentMessage  $message  Отправляемое сообщение и конверт
     */
    protected function doSend(SentMessage $message): void
    {
        $envelope = $message->getEnvelope();
        $to = implode(', ', array_map(static fn ($a) => $a->getAddress(), $envelope->getRecipients()));

        $raw = $message->getMessage()->toString();
        $parts = preg_split("/\r\n\r\n|\n\n/", $raw, 2);
        $headerBlock = $parts[0] ?? '';
        $body = $parts[1] ?? '';

        $subject = $this->extractHeaderValue($headerBlock, 'Subject') ?? '';
        if ($subject === '') {
            $original = $message->getOriginalMessage();
            if ($original instanceof Message) {
                $subject = (string) $original->getSubject();
            }
        }
        if ($subject === '') {
            $subject = '(без темы)';
        }

        $headers = $this->removeHeaders($headerBlock, ['Subject', 'To']);
        $headers = trim(str_replace("\n", "\r\n", str_replace("\r\n", "\n", $headers)));

        $sender = $envelope->getSender()->getAddress();
        $params = $sender !== '' ? '-f'.$sender : '';

        if ($headers !== '') {
            $ok = $params !== ''
                ? mail($to, $subject, $body, $headers, $params)
                : mail($to, $subject, $body, $headers);
        } else {
            $ok = $params !== ''
                ? mail($to, $subject, $body, '', $params)
                : mail($to, $subject, $body);
        }

        if (! $ok) {
            throw new TransportException(
                'PHP mail() вернул false. '.$this->mailFailureHint()
            );
        }
    }

    /**
     * Подсказка по типичной причине сбоя mail() на сервере без MTA
     */
    private function mailFailureHint(): string
    {
        $path = trim((string) ini_get('sendmail_path'));
        if ($path === '') {
            return 'В php.ini пустой sendmail_path — задайте путь к sendmail/postfix или переключите MAIL_MAILER на smtp.';
        }

        if (! preg_match('/^\s*([\'"]?)([^\s\'"]+)\1(?:\s|$)/', $path, $m)) {
            return 'Не удалось разобрать sendmail_path ('.$path.'). Проверьте php.ini для CLI и для php-fpm.';
        }

        $bin = $m[2];
        if (! is_file($bin)) {
            return sprintf(
                'В sendmail_path указан несуществующий файл: %s. Установите MTA, например: sudo apt install -y postfix (или mailutils), либо смените MAIL_MAILER на smtp.',
                $bin
            );
        }

        if (! is_executable($bin)) {
            return sprintf('Файл из sendmail_path не исполняется: %s. Проверьте права chmod +x.', $bin);
        }

        return sprintf(
            'Бинарник %s есть; смотрите логи MTA (journalctl -xe, /var/log/mail.log). Часто помогает: sudo systemctl start postfix. Либо откажитесь от php_mail и настройте MAIL_MAILER=smtp.',
            $bin
        );
    }

    /**
     * Извлекает значение однострочного или «сложённого» заголовка
     *
     * @param  string  $headerBlock  Блок заголовков
     * @param  string  $name  Имя заголовка без двоеточия
     * @return string|null Декодированная строка или null
     */
    private function extractHeaderValue(string $headerBlock, string $name): ?string
    {
        $pattern = '/^'.preg_quote($name, '/').':(.*(?:\r\n[ \t].*)*)/mi';
        if (! preg_match($pattern, $headerBlock, $m)) {
            return null;
        }

        $value = trim(preg_replace("/\r\n[ \t]+/", ' ', $m[1]));

        return $value !== '' ? $value : null;
    }

    /**
     * Удаляет из блока заголовков указанные поля (включая продолжения folded-строк)
     *
     * @param  string  $headerBlock  Исходные заголовки
     * @param  list<string>  $names  Имена без двоеточия
     * @return string Оставшиеся строки заголовков
     */
    private function removeHeaders(string $headerBlock, array $names): string
    {
        $norm = str_replace("\r\n", "\n", $headerBlock);
        $lines = explode("\n", $norm);
        $skipNames = array_map('strtolower', $names);
        $out = [];
        $skipping = false;

        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }

            $isContinuation = ($line[0] === ' ' || $line[0] === "\t");
            if ($isContinuation) {
                if ($skipping) {
                    continue;
                }
                $out[] = $line;

                continue;
            }

            if (preg_match('/^([^\s:]+):/', $line, $m)) {
                $skipping = in_array(strtolower($m[1]), $skipNames, true);
                if ($skipping) {
                    continue;
                }
            } else {
                $skipping = false;
            }

            $out[] = $line;
        }

        return implode("\r\n", $out);
    }

    /**
     * Строковое имя транспорта для отладки
     */
    public function __toString(): string
    {
        return 'php-mail';
    }
}
