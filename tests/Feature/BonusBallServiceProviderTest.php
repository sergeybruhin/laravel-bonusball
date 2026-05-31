<?php

declare(strict_types=1);

namespace BonusBall\Laravel\Tests\Feature;

use BonusBall\Client\BonusBallClient;
use BonusBall\Client\Dto\Input\CreateTransactionInput;
use BonusBall\Client\Enums\TransactionType;
use BonusBall\Client\Resources\ClientResource;
use BonusBall\Client\Resources\ReasonResource;
use BonusBall\Client\Resources\TransactionResource;
use BonusBall\Laravel\BonusBallManager;
use BonusBall\Laravel\BonusBallServiceProvider;
use BonusBall\Laravel\Facades\BonusBall;
use BonusBall\Laravel\Jobs\AddBonuses;
use BonusBall\Laravel\Jobs\ReduceBonuses;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Orchestra\Testbench\TestCase;

final class BonusBallServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [BonusBallServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['BonusBall' => BonusBall::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('bonusball.base_url', 'http://bonusball-api');
        $app['config']->set('bonusball.token', 'test-project-token');
        $app['config']->set('bonusball.timeout', 10);
        $app['config']->set('bonusball.queue', 'default');
    }

    public function test_manager_is_registered_as_singleton(): void
    {
        $a = $this->app->make(BonusBallManager::class);
        $b = $this->app->make(BonusBallManager::class);

        $this->assertSame($a, $b);
    }

    public function test_facade_resolves_manager(): void
    {
        $this->assertInstanceOf(BonusBallManager::class, BonusBall::getFacadeRoot());
    }

    public function test_manager_clients_returns_client_resource(): void
    {
        $this->assertInstanceOf(
            ClientResource::class,
            $this->app->make(BonusBallManager::class)->clients(),
        );
    }

    public function test_manager_transactions_returns_transaction_resource(): void
    {
        $this->assertInstanceOf(
            TransactionResource::class,
            $this->app->make(BonusBallManager::class)->transactions(),
        );
    }

    public function test_manager_reasons_returns_reason_resource(): void
    {
        $this->assertInstanceOf(
            ReasonResource::class,
            $this->app->make(BonusBallManager::class)->reasons(),
        );
    }

    public function test_config_is_merged(): void
    {
        $this->assertNotNull(config('bonusball'));
        $this->assertArrayHasKey('base_url', config('bonusball'));
        $this->assertArrayHasKey('token', config('bonusball'));
        $this->assertArrayHasKey('timeout', config('bonusball'));
        $this->assertArrayHasKey('queue', config('bonusball'));
    }

    public function test_add_bonuses_job_can_be_instantiated(): void
    {
        $input = new CreateTransactionInput(
            type: TransactionType::Add,
            amount: 100,
            reasonUuid: '019602a0-0000-7000-8000-000000000002',
        );

        $job = new AddBonuses('019602a0-0000-7000-8000-000000000001', $input);

        $this->assertInstanceOf(AddBonuses::class, $job);
        $this->assertSame($input, $job->input);
        $this->assertContains('add', $job->tags());
    }

    public function test_reduce_bonuses_job_can_be_instantiated(): void
    {
        $input = new CreateTransactionInput(
            type: TransactionType::Reduce,
            amount: 50,
            reasonUuid: '019602a0-0000-7000-8000-000000000002',
        );

        $job = new ReduceBonuses('019602a0-0000-7000-8000-000000000001', $input);

        $this->assertInstanceOf(ReduceBonuses::class, $job);
        $this->assertContains('reduce', $job->tags());
    }

    public function test_manager_uses_injected_client(): void
    {
        $clientPayload = [
            'uuid' => '019602a0-0000-7000-8000-000000000001',
            'name' => 'Ivan Petrov',
            'external_id' => null,
            'email' => 'ivan@example.com',
            'phone' => '+79001234567',
            'balance' => 500,
        ];

        $mock = new MockHandler([new Response(200, [], json_encode(['data' => $clientPayload]))]);
        $http = new Client(['handler' => HandlerStack::create($mock)]);
        $client = new BonusBallClient($http);

        $manager = $this->app->make(BonusBallManager::class);
        $manager->setClient($client);

        $result = $manager->clients()->find('019602a0-0000-7000-8000-000000000001');

        $this->assertSame('019602a0-0000-7000-8000-000000000001', $result->uuid);
        $this->assertSame('Ivan Petrov', $result->name);
        $this->assertSame(500, $result->balance);
    }
}
