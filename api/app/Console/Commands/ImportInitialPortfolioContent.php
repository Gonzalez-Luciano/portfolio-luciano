<?php

namespace App\Console\Commands;

use App\Domain\Content\InitialPortfolioContent;
use App\Domain\Content\InitialPortfolioImporter;
use Illuminate\Console\Command;

/**
 * Operator surface for the guarded one-time initial-content import (spec §29).
 *
 * There are no flags, options, modes, or environment branches: the command
 * either fills the pristine Phase 4 structural singletons and creates the
 * approved draft collections, or it fails without changing anything. It never
 * runs migrations, the database seeders, cache publication, a Filament
 * publication action, or startup logic.
 */
final class ImportInitialPortfolioContent extends Command
{
    protected $signature = 'portfolio:import-initial-content';

    protected $description = 'Perform the guarded one-time import of the approved initial portfolio content as draft, hidden, unpublished records.';

    public function handle(InitialPortfolioImporter $importer): int
    {
        try {
            $importer(InitialPortfolioContent::data());
        } catch (\Throwable) {
            $this->error('The initial content import was rejected and nothing was changed. It runs only against a pristine content database: the two structural singletons must be untouched and the rest of the managed content graph empty. Resolve the reported condition and run the command again.');

            return self::FAILURE;
        }

        $this->info('The approved initial portfolio content was imported as draft, hidden, unpublished records. Review and publish it through the normal Filament publication flow.');

        return self::SUCCESS;
    }
}
