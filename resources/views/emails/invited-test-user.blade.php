<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Noema</title>
</head>
<body style="margin:0;padding:0;background-color:#0f1115;font-family:Georgia,'Times New Roman',serif;-webkit-font-smoothing:antialiased;">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color:#0f1115;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:520px;background-color:#161b22;border:1px solid #2d333b;border-radius:0;">
                    <tr>
                        <td style="padding:28px 28px 20px 28px;border-bottom:1px solid #2d333b;text-align:center;">
                            <p style="margin:0;font-size:13px;letter-spacing:0.2em;text-transform:uppercase;color:#8b949e;font-family:system-ui,-apple-system,sans-serif;">Noema</p>
                            <h1 style="margin:12px 0 0 0;font-size:22px;font-weight:600;color:#e6edf3;line-height:1.35;">Система проектирования Миров</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 28px 8px 28px;">
                            <p style="margin:0 0 16px 0;font-size:16px;line-height:1.65;color:#c9d1d9;font-family:system-ui,-apple-system,sans-serif;">
                                Благодарим за регистрацию в Системе проектирования Миров Noema. Ваши учётные данные:
                            </p>
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color:#0d1117;border:1px solid #30363d;border-radius:0;margin:20px 0;">
                                <tr>
                                    <td style="padding:16px 18px;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:14px;color:#e6edf3;">
                                        <p style="margin:0 0 10px 0;color:#8b949e;font-size:12px;text-transform:uppercase;letter-spacing:0.06em;font-family:system-ui,-apple-system,sans-serif;">Логин</p>
                                        <p style="margin:0;word-break:break-all;">{{ $user->email }}</p>
                                        <p style="margin:18px 0 10px 0;color:#8b949e;font-size:12px;text-transform:uppercase;letter-spacing:0.06em;font-family:system-ui,-apple-system,sans-serif;">Пароль</p>
                                        <p style="margin:0;word-break:break-all;">{{ $plainPassword }}</p>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 24px 0;font-size:13px;line-height:1.55;color:#8b949e;font-family:system-ui,-apple-system,sans-serif;">
                                Сохраните письмо в надёжном месте. После первого входа вы можете сменить пароль в настройках аккаунта.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 28px 32px 28px;text-align:center;">
                            <table role="presentation" cellpadding="0" cellspacing="0" align="center" style="margin:0 auto;">
                                <tr>
                                    <td style="background-color:#c4a574;border-radius:0;">
                                        <a href="{{ $homeUrl }}" style="display:inline-block;padding:14px 36px;font-size:15px;font-weight:600;color:#1a1a1a;text-decoration:none;font-family:system-ui,-apple-system,sans-serif;letter-spacing:0.02em;">Войти</a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:20px 0 0 0;font-size:12px;line-height:1.5;color:#6e7681;font-family:system-ui,-apple-system,sans-serif;">
                                Если кнопка не открывается, перейдите по ссылке:<br>
                                <a href="{{ $homeUrl }}" style="color:#8b949e;word-break:break-all;">{{ $homeUrl }}</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
