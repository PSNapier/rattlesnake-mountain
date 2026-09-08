<?php

use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * Production mail goes out through the Symfony Mailgun bridge, which moved from
 * Symfony 7 to 8 in [033]. Nothing else in the suite touches that bridge: the
 * rest of the tests run on the array transport and would stay green even if the
 * Mailgun transport had stopped resolving. These tests cover that gap.
 *
 * Credentials are dummies and no request leaves the machine. Building a
 * transport does not open a connection, and the send below runs on the array
 * transport, so what is under test is the wiring rather than delivery.
 */
beforeEach(function () {
    config([
        'services.mailgun' => [
            'domain' => 'example.test',
            'secret' => 'test-only-not-a-credential',
            'endpoint' => 'api.mailgun.net',
            'scheme' => 'https',
        ],
        'mail.mailers.mailgun' => ['transport' => 'mailgun'],
    ]);

    Mail::purge('mailgun');
});

it('resolves the mailgun mailer to the symfony mailgun bridge', function () {
    $transport = Mail::mailer('mailgun')->getSymfonyTransport();

    expect($transport)
        ->toBeInstanceOf(TransportInterface::class)
        ->and($transport::class)
        ->toStartWith('Symfony\Component\Mailer\Bridge\Mailgun\Transport\\');
});

it('carries the configured domain and endpoint into the transport', function () {
    // The transport stringifies to its DSN, which is the cheapest honest proof
    // that config/services.php actually reaches Symfony rather than the bridge
    // falling back to its own defaults.
    $transport = Mail::mailer('mailgun')->getSymfonyTransport();

    expect((string) $transport)
        ->toContain('domain=example.test')
        ->toContain('api.mailgun.net');
});

it('sends through the symfony mailer path', function () {
    Mail::mailer('array')->raw('Mailgun bridge probe', function ($message) {
        $message->to('probe@example.test')->subject('Bridge probe');
    });

    $sent = Mail::mailer('array')->getSymfonyTransport()->messages();

    expect($sent)->toHaveCount(1)
        ->and($sent[0]->getOriginalMessage()->getSubject())->toBe('Bridge probe');
});
