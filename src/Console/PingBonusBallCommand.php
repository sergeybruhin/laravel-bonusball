<?php

namespace BonusBall\Laravel\Console;

use BonusBall\Client\Exceptions\AuthenticationException;
use BonusBall\Client\Exceptions\BonusBallException;
use BonusBall\Client\Exceptions\NotFoundException;
use BonusBall\Laravel\BonusBallManager;
use Illuminate\Console\Command;

class PingBonusBallCommand extends Command
{
    protected $signature = 'bonusball:ping';

    protected $description = 'Check connectivity and authentication with the BonusBall API';

    public function handle(BonusBallManager $manager): int
    {
        $baseUrl = config('bonusball.base_url');
        $token = config('bonusball.token');

        $this->line("Base URL : <info>{$baseUrl}</info>");
        $this->line('Token    : <info>'.($token ? str_repeat('*', 8).substr($token, -4) : '(not set)').'</info>');
        $this->newLine();

        if (empty($token)) {
            $this->error('BONUSBALL_TOKEN is not configured.');

            return self::FAILURE;
        }

        try {
            $manager->clients()->find('00000000-0000-0000-0000-000000000000');
        } catch (NotFoundException) {
            $this->info('SUCCESS — API is reachable and token is valid.');

            return self::SUCCESS;
        } catch (AuthenticationException) {
            $this->error('FAILURE — Authentication rejected. Check BONUSBALL_TOKEN.');

            return self::FAILURE;
        } catch (BonusBallException $e) {
            $this->error("FAILURE — {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info('SUCCESS — API is reachable and token is valid.');

        return self::SUCCESS;
    }
}
