<?php

namespace Tests\Unit;

use App\Mail\Transport\PhpMailTransport;
use Illuminate\Mail\MailManager;
use Tests\TestCase;

class PhpMailTransportRegistrationTest extends TestCase
{
    public function test_php_mail_transport_is_registered(): void
    {
        /** @var MailManager $manager */
        $manager = $this->app->make('mail.manager');
        $transport = $manager->createSymfonyTransport([
            'transport' => 'php_mail',
        ]);

        $this->assertInstanceOf(PhpMailTransport::class, $transport);
    }
}
