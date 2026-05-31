<?php

namespace BonusBall\Laravel\Jobs;

use BonusBall\Client\Dto\Input\CreateTransactionInput;
use BonusBall\Client\Dto\TransactionDto;
use BonusBall\Client\Enums\TransactionType;
use BonusBall\Laravel\BonusBallManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AddBonuses implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public readonly string $clientUuid,
        public readonly CreateTransactionInput $input,
        public readonly ?string $onSuccessCallback = null,
    ) {
        $this->onQueue(config('bonusball.queue'));
    }

    public function handle(BonusBallManager $manager): void
    {
        $input = new CreateTransactionInput(
            type: TransactionType::Add,
            amount: $this->input->amount,
            reasonUuid: $this->input->reasonUuid,
            notes: $this->input->notes,
        );

        $transaction = $manager->transactions()->create($this->clientUuid, $input);

        if ($this->onSuccessCallback !== null) {
            ($this->onSuccessCallback)($transaction);
        }
    }

    /**
     * @return string[]
     */
    public function tags(): array
    {
        return ['bonusball', 'add'];
    }
}
