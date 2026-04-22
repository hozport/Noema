<?php

namespace App\Console\Commands;

use App\Mail\InvitedTestUserMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Ручное приглашение тестового пользователя
 *
 * Создаёт запись в таблице users так же, как при обычной регистрации (хэш пароля, folder_token из модели),
 * затем отправляет письмо с логином и сгенерированным паролем.
 */
class InviteTestUserCommand extends Command
{
    /**
     * Имя и сигнатура консольной команды
     *
     * @var string
     */
    protected $signature = 'noema:invite-test-user {email? : E-mail нового пользователя}';

    /**
     * Описание команды для списка artisan
     *
     * @var string
     */
    protected $description = 'Создать учётную запись по e-mail и отправить временный пароль на почту';

    /**
     * Выполняет валидацию e-mail, создание пользователя и отправку письма
     */
    public function handle(): int
    {
        $raw = $this->argument('email') ?? $this->ask('E-mail нового пользователя');
        $email = is_string($raw) ? Str::lower(trim($raw)) : '';

        $validator = Validator::make(
            ['email' => $email],
            [
                'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')],
            ]
        );
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $plainPassword = Str::password(16, letters: true, numbers: true, symbols: false);
        $local = trim((string) Str::before($email, '@'));
        $name = $local !== '' ? Str::limit($local, 250) : 'Участник Noema';

        if ($this->input->isInteractive() && ! $this->confirm("Создать пользователя {$email} и отправить письмо?", true)) {
            $this->warn('Отменено.');

            return self::SUCCESS;
        }

        try {
            DB::transaction(function () use ($email, $name, $plainPassword): void {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => $plainPassword,
                ]);
                $user->forceFill(['email_verified_at' => now()])->save();
                Mail::to($user->email)->send(new InvitedTestUserMail($user, $plainPassword));
            });
        } catch (\Throwable $e) {
            report($e);
            $this->error('Не удалось создать пользователя или отправить письмо: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Пользователь создан, письмо с доступом отправлено на '.$email.'.');

        return self::SUCCESS;
    }
}
