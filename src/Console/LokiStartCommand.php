<?php

namespace Yiendos\MySitesIde\Monitoring\Loki\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiendos\MySitesIde\Monitoring\Loki\Ide;
use Yiendos\MySitesIde\Monitoring\Loki\Traits\InteractsWithLoki;

class LokiStartCommand extends Command
{
    use InteractsWithLoki;

    /**
     * The ability to configure the console command
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('monitoring:loki-start')
            ->setDescription('Start the Loki container - or recreate it if its compose config changed')
        ;
    }

    /**
     * `up -d --build` is a no-op for an up-to-date container, recreates one
     * whose compose config changed (e.g. a new LOKI_RETENTION), and builds the
     * image after an update to the Dockerfile. Logs are kept in
     * storage/plugins/loki/, so recreating loses nothing.
     *
     * Loki only stores logs - the alloy plugin is what collects them.
     *
     * @param OutputInterface $output
     * @param InputInterface $input
     * @param SymfonyStyle $io
     * @return integer
     */
    public function __invoke(OutputInterface $output, InputInterface $input, SymfonyStyle $io): int
    {
        Ide::storage();

        if ($this->compose($output, 'up -d --build loki') !== 0) {
            $io->error('Loki did not start - see above.');
            return Command::FAILURE;
        }

        $io->success('Loki started - loki:3100 inside the IDE');

        if (!Ide::installed('alloy')) {
            $io->text('Nothing sends Loki logs yet - install the yiendos/my-sites-ide-monitoring-alloy plugin to collect the IDE\'s container logs.');
        }

        return Command::SUCCESS;
    }
}
