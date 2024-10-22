<?php

namespace Carbon\AutoMigrate\Command;

use Neos\ContentRepository\Migration\Command\NodeCommandController as OriginalNodeCommandController;
use Neos\ContentRepository\Migration\Domain\Factory\MigrationFactory;
use Neos\ContentRepository\Migration\Domain\Repository\MigrationStatusRepository;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;

#[Flow\Scope('singleton')]
class NodeCommandController extends CommandController
{
    #[Flow\Inject]
    protected MigrationFactory $migrationFactory;

    #[Flow\Inject]
    protected MigrationStatusRepository $migrationStatusRepository;

    #[Flow\Inject]
    protected OriginalNodeCommandController $nodeCommandController;

    #[Flow\InjectConfiguration('node')]
    protected $nodeMigration;

    /**
     * Run definded node migrations in setting Carbon.AutoMigrate.node
     *
     * @param bool $confirmation Confirm application of the migrations, only needed if one of the given migrations contains any warnings.
     * @param bool $dryRun If true, no changes will be made
     * @return void
     */
    public function autoMigrateCommand(bool $confirmation = false, bool $dryRun = false): void
    {
        $availableMigrations = $this->migrationFactory->getAvailableMigrationsForCurrentConfigurationType();
        if (count($availableMigrations) === 0) {
            $this->outputLine('No migrations available.');
            $this->quit();
        }
        $this->outputLine();
        $nodeMigrations = $this->nodeMigration ?? null;
        if (!isset($nodeMigrations) || !is_array($nodeMigrations) || !count($nodeMigrations)) {
            $this->outputLine('No automatic node migrations defined');
            $this->quit();
        }

        ksort($nodeMigrations);
        $notFound = [];
        $alreadyExecuted = [];
        $toExecute = [];

        foreach ($nodeMigrations as $version => $enabled) {
            if (!$enabled) {
                continue;
            }
            try {
                $migrationConfiguration =
                    $this->migrationFactory->getMigrationForVersion($version)->getUpConfiguration();
                $data = $availableMigrations[$version];
            } catch (\Throwable $th) {
                $notFound[] = $version;
                continue;
            }

            $package = $data['package']->getPackageKey();
            $date = $data['formattedVersionNumber'];
            $comment = $migrationConfiguration->getComments();
            $tableContent = [$version, $date, $package, wordwrap($comment, 60)];

            if (count($this->migrationStatusRepository->findByVersion($version))) {
                $alreadyExecuted[$version] = $tableContent;
                continue;
            }

            $toExecute[$version] = $tableContent;
        }

        if (count($notFound)) {
            $this->outputFormatted('<error>Following node migrations where not found:</error>');
            foreach ($notFound as $version) {
                $this->outputLine(' - %s', [$version]);
            }
            $this->outputLine();
        }
        if (count($alreadyExecuted)) {
            $this->outputFormatted('Following node migrations where already executed:');
            $tableRows = [];
            foreach ($alreadyExecuted as $version => $tableContent) {
                $tableRows[] = $tableContent;
            }
            $this->output->outputTable($tableRows, ['Version', 'Date', 'Package', 'Description']);
            $this->outputLine();
        }
        if (count($toExecute)) {
            $this->outputFormatted('<success>Run migrations…</success>');
            $this->outputLine();
            $tableRows = [];
            foreach ($toExecute as $version => $tableContent) {
                $tableRows[] = $tableContent;
                if (!$dryRun) {
                    $this->nodeCommandController->migrateCommand($version, $confirmation);
                    $this->outputLine();
                    $this->outputLine();
                }
            }
            $this->outputFormatted('<success>Applied following migrations:</success>');
            $this->output->outputTable($tableRows, ['Version', 'Date', 'Package', 'Description']);
            $this->outputLine();
        } else {
            $this->outputFormatted('<info>No migrations to apply</info>');
            $this->outputLine();
        }

        if ($dryRun) {
            $this->outputLine();
            $this->outputFormatted('<info>Dry run, no changes where made</info>');
            $this->outputLine();
        }
    }
}
