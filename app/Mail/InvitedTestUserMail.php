<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Приглашение тестового пользователя с логином и паролем
 *
 * Отправляется после ручного создания учётной записи через консольную команду;
 * пароль передаётся только в теле письма и не хранится в открытом виде в БД.
 */
class InvitedTestUserMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * Абсолютный URL главной страницы (форма входа на лендинге).
     */
    public string $homeUrl;

    /**
     * @param  User  $user  Созданный пользователь
     * @param  string  $plainPassword  Временный пароль (одноразово в письме)
     */
    public function __construct(
        public User $user,
        #[\SensitiveParameter]
        public string $plainPassword,
    ) {
        $this->homeUrl = route('site.home', [], true);
    }

    /**
     * Тема и адресаты письма
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Noema — доступ к системе проектирования миров',
        );
    }

    /**
     * HTML- и текстовая версии письма
     */
    public function content(): Content
    {
        return new Content(
            html: 'emails.invited-test-user',
            text: 'emails.invited-test-user-plain',
        );
    }
}
