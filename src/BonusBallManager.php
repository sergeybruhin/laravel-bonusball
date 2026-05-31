<?php

namespace BonusBall\Laravel;

use BonusBall\Client\BonusBallClient;
use BonusBall\Client\Resources\ClientResource;
use BonusBall\Client\Resources\ReasonResource;
use BonusBall\Client\Resources\TransactionResource;

class BonusBallManager
{
    private ?BonusBallClient $client = null;

    public function clients(): ClientResource
    {
        return $this->getClient()->clients();
    }

    public function transactions(): TransactionResource
    {
        return $this->getClient()->transactions();
    }

    public function reasons(): ReasonResource
    {
        return $this->getClient()->reasons();
    }

    public function setClient(BonusBallClient $client): void
    {
        $this->client = $client;
    }

    private function getClient(): BonusBallClient
    {
        if ($this->client === null) {
            $this->client = BonusBallClient::create(
                baseUrl: config('bonusball.base_url'),
                token: config('bonusball.token'),
                timeout: config('bonusball.timeout'),
            );
        }

        return $this->client;
    }
}
