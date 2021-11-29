<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Browscap;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateDataCommand extends Command
{
    use LockableTrait;

    protected static $defaultName = 'ops:update';
    private Browscap $browscap;
    private Connection $connection;

    public function __construct(Browscap $browscap, Connection $connection)
    {
        parent::__construct();
        $this->browscap = $browscap;
        $this->connection = $connection;
    }

    protected function configure()
    {
        $this->setDescription('Update Aduser data')->setHelp(
            'This command allows you to update data used by Aduser'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->lock()) {
            $io->warning('The command is already running in another process.');
            return self::FAILURE;
        }

        $io->comment('Updating Browscap...');

        if ($this->browscap->update()) {
            $io->success('Browscap successfully updated!');
        } else {
            $io->error('Browscap updated with errors');
        }

        $this->release();
        return self::SUCCESS;
    }
}
