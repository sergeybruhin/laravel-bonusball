# laravel-bonusball

Laravel integration for the [BonusBall API](https://bonusball.ru) — a loyalty bonus-points platform. Wraps [bonusball-client](https://github.com/sergeybruhin/bonusball-client) with a service provider, facade, queueable jobs, and an Artisan connectivity command.

## Requirements

- PHP 8.4+
- Laravel 13+
- [sergeybruhin/bonusball-client](https://github.com/sergeybruhin/bonusball-client) ^0.0.1

## Installation

```bash
composer require sergeybruhin/laravel-bonusball
```

The package is auto-discovered by Laravel. No manual provider registration needed.

Publish the config file:

```bash
php artisan vendor:publish --tag=bonusball-config
```

## Configuration

Add to your `.env`:

```env
BONUSBALL_BASE_URL=https://bonusball.ru
BONUSBALL_TOKEN=your-project-token
BONUSBALL_TIMEOUT=10
BONUSBALL_QUEUE=default
```

`config/bonusball.php`:

```php
return [
    'base_url' => env('BONUSBALL_BASE_URL', 'http://bonusball-api'),
    'token'    => env('BONUSBALL_TOKEN'),
    'timeout'  => (int) env('BONUSBALL_TIMEOUT', 10),
    'queue'    => env('BONUSBALL_QUEUE', 'default'),
];
```

Project tokens are issued by a platform admin via `POST /api/v1/admin/projects/{uuid}/tokens`.

---

## Usage

### Facade

```php
use BonusBall\Laravel\Facades\BonusBall;

// Find a client and check their balance
$member = BonusBall::clients()->find($uuid);
echo $member->balance;

// Add bonus points
$tx = BonusBall::transactions()->create($uuid, new CreateTransactionInput(
    type: TransactionType::Add,
    amount: 300,
    reasonUuid: 'reason-uuid',
    notes: 'Order #5678',
));

// List active transaction reasons
$reasons = BonusBall::reasons()->list();
```

### Dependency injection

```php
use BonusBall\Laravel\BonusBallManager;

class OrderService
{
    public function __construct(private readonly BonusBallManager $bonusBall) {}

    public function rewardCustomer(string $clientUuid, int $points, string $reasonUuid): void
    {
        $this->bonusBall->transactions()->create(
            $clientUuid,
            new CreateTransactionInput(TransactionType::Add, $points, $reasonUuid),
        );
    }
}
```

### Available methods

All methods delegate to the underlying `bonusball-client` resources. See the [bonusball-client README](https://github.com/sergeybruhin/bonusball-client) for full method signatures, DTO shapes, and exception details.

| Method | Returns | Description |
|---|---|---|
| `BonusBall::clients()` | `ClientResource` | Create and look up clients |
| `BonusBall::transactions()` | `TransactionResource` | Record and list transactions |
| `BonusBall::reasons()` | `ReasonResource` | List active transaction reasons |

---

## Queue Jobs

Use the provided jobs to dispatch bonus operations asynchronously. Both jobs implement `ShouldQueue` with `tries = 3` and `backoff = 10` seconds.

### `AddBonuses`

Asynchronously add bonus points to a client.

```php
use BonusBall\Client\Dto\Input\CreateTransactionInput;
use BonusBall\Client\Enums\TransactionType;
use BonusBall\Laravel\Jobs\AddBonuses;

AddBonuses::dispatch(
    clientUuid: $member->uuid,
    input: new CreateTransactionInput(
        type: TransactionType::Add,
        amount: 200,
        reasonUuid: $reason->uuid,
        notes: 'Order #1234',
    ),
);
```

With an optional success callback (must be a serializable callable string):

```php
AddBonuses::dispatch(
    clientUuid: $member->uuid,
    input: new CreateTransactionInput(TransactionType::Add, 200, $reason->uuid),
    onSuccessCallback: function (TransactionDto $tx) {
        Cache::put("balance:{$tx->clientUuid}", $tx->balanceAfter, now()->addHour());
    },
);
```

### `ReduceBonuses`

Asynchronously deduct bonus points from a client.

```php
use BonusBall\Laravel\Jobs\ReduceBonuses;

ReduceBonuses::dispatch(
    clientUuid: $member->uuid,
    input: new CreateTransactionInput(
        type: TransactionType::Reduce,
        amount: 100,
        reasonUuid: $reason->uuid,
    ),
);
```

Both jobs are tagged `['bonusball', 'add']` and `['bonusball', 'reduce']` respectively, making them filterable in [Laravel Horizon](https://laravel.com/docs/horizon).

**Queue configuration:** jobs are dispatched to the queue specified by `BONUSBALL_QUEUE` (default: `default`).

---

## Artisan Command

Check connectivity and authentication with the BonusBall API:

```bash
php artisan bonusball:ping
```

Example output on success:

```
Base URL : https://bonusball.ru
Token    : ********abcd

SUCCESS — API is reachable and token is valid.
```

Example output on failure:

```
Base URL : https://bonusball.ru
Token    : ********abcd

FAILURE — Authentication rejected. Check BONUSBALL_TOKEN.
```

Useful as a container readiness check or deploy verification step.

---

## Testing

### Swapping the HTTP client

Use `BonusBallManager::setClient()` to inject a mocked HTTP client in your feature tests. This bypasses the real API entirely.

```php
use BonusBall\Client\BonusBallClient;
use BonusBall\Laravel\BonusBallManager;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

$mock = new MockHandler([
    new Response(201, [], json_encode([
        'data' => [
            'uuid'           => '019602a0-0000-7000-8000-000000000001',
            'type'           => 'add',
            'amount'         => 100,
            'balance_before' => 0,
            'balance_after'  => 100,
            'notes'          => null,
            'reason'         => null,
            'client_uuid'    => '019602a0-0000-7000-8000-000000000002',
            'project_uuid'   => '019602a0-0000-7000-8000-000000000003',
            'created_at'     => '2024-01-01T00:00:00+00:00',
        ],
    ])),
]);

$http = new Client(['handler' => HandlerStack::create($mock)]);

app(BonusBallManager::class)->setClient(new BonusBallClient($http));

// Now any call to BonusBall::transactions()->create(...) uses the mock
```

### Faking jobs in tests

```php
use BonusBall\Laravel\Jobs\AddBonuses;
use Illuminate\Support\Facades\Queue;

Queue::fake();

// ... trigger the code that dispatches AddBonuses

Queue::assertPushed(AddBonuses::class, function (AddBonuses $job) use ($expectedUuid) {
    return $job->clientUuid === $expectedUuid;
});
```

### Running the package tests

```bash
composer install
./vendor/bin/phpunit
```

---

## License

MIT
