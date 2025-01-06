<?php

namespace Botble\Base\Commands;

use Botble\Base\Supports\Core;
use Botble\Setting\Facades\Setting;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

#[AsCommand('cms:license:activate', 'Activate license')]
class ActivateLicenseCommand extends Command
{
    public function __construct(protected Core $core)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        // Bypassing input validation and directly using the options or default values
        $username = $this->option('buyer') ?: 'default_user'; // Default user if none is provided
        $purchasedCode = $this->option('purchase_code') ?: 'default_purchase_code'; // Default purchase code if none is provided

        try {
            return $this->performUpdate($purchasedCode, $username);
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    protected function performUpdate(string $purchasedCode, string $username): int
    {
        // Attempt to activate the license without validation
        $status = $this->core->activateLicense($purchasedCode, $username);

        if (!$status) {
            $this->components->error('This license is invalid.');

            return self::FAILURE;
        }

        Setting::forceSet('licensed_to', $username)->save();

        $this->components->info('This license has been activated successfully.');

        return self::SUCCESS;
    }

    protected function configure(): void
    {
        $this->addOption('buyer', null, InputOption::VALUE_REQUIRED, 'The buyer name');
        $this->addOption('purchase_code', null, InputOption::VALUE_REQUIRED, 'The purchase code');
    }
}
