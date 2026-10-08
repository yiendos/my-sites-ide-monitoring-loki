<?php

namespace Yiendos\MySitesIde\Monitoring\Loki\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiendos\MySitesIde\Monitoring\Loki\Traits\InteractsWithLoki;

class LokiStopCommand extends Command
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
            ->setName('monitoring:loki-stop')
            ->setDescription('Stop the Loki container, leaving the rest of the IDE running')
        ;
    }

    /**
     * Stopped rather than removed, so monitoring:loki-start brings back
     * the same container. Its data is kept in storage/plugins/loki/
     * either way.
     *
     * @param OutputInterface $output
     * @param InputInterface $input
     * @param SymfonyStyle $io
     * @return integer
     */
    public function __invoke(OutputInterface $output, InputInterface $input, SymfonyStyle $io): int
    {
        if (!$this->running()) {
            $io->writeln('Loki is not running - nothing to stop.');
            return Command::SUCCESS;
        }

        if ($this->compose($output, 'stop loki') !== 0) {
            $io->error('Loki did not stop - see above.');
            return Command::FAILURE;
        }

        $io->success('Loki stopped.');

        return Command::SUCCESS;
    }
}
