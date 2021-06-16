<?php

namespace Recca0120\AsyncTesting\Tests\Console;

use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Recca0120\AsyncTesting\Console\AsyncRequestCommand;
use Recca0120\AsyncTesting\Tests\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class AsyncCallCommandTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    /**
     * @var User
     */
    private $user = [
        'email' => 'recca0120@gmail.com',
        'password' => 'password',
    ];

    public function setUp(): void
    {
        parent::setUp();
    }

    public function test_it_should_execute_call_method(): void
    {
        $response = $this->givenResponse([
            'uri' => '/auth/login',
            '--method' => 'post',
            '--parameters' => '[]',
            '--server' => json_encode([
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Accept' => 'application/json',
            ]),
            '--content' => json_encode(['email' => $this->user['email'], 'password' => $this->user['password']]),
            '--followRedirects' => false,
        ]);

        self::assertJsonStringEqualsJsonString(json_encode($this->user), (string) $response->getBody());
    }

    /**
     * @dataProvider hasBodyProvider
     */
    public function test_it_should_execute_other_methods_with_post_body($method): void
    {
        $response = $this->givenResponse([
            'uri' => '/auth/login',
            '--method' => $method,
            '--data' => json_encode(['email' => $this->user['email'], 'password' => $this->user['password']]),
        ]);

        self::assertJsonStringEqualsJsonString(json_encode($this->user), (string) $response->getBody());
    }

    public function hasBodyProvider(): array
    {
        return [
            ['postJson'],
            ['post'],
            ['put'],
            ['putJson'],
            ['patch'],
            ['patchJson'],
            ['options'],
            ['optionsJson'],
            ['delete'],
            ['deleteJson'],
        ];
    }

    /**
     * @param array $arguments
     * @return Response
     */
    private function givenResponse(array $arguments = []): Response
    {
        $application = new Application();
        $application->add(new AsyncRequestCommand($this->app));
        $command = $application->find('async:request');
        $commandTester = new CommandTester($command);
        $commandTester->execute($arguments);
        $output = $commandTester->getDisplay();

        return Message::parseResponse($output);
    }
}
