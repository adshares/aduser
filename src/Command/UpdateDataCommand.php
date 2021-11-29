<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Browscap;
use App\Service\PageInfo;
use DateTimeImmutable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateDataCommand extends Command
{
    use LockableTrait;

    protected static $defaultName = 'ops:update';
    private PageInfo $pageInfo;
    private Browscap $browscap;

    public function __construct(PageInfo $pageInfo, Browscap $browscap)
    {
        parent::__construct();
        $this->pageInfo = $pageInfo;
        $this->browscap = $browscap;
    }

    protected function configure()
    {
        $this
            ->setDescription('Update Aduser data')
            ->setHelp('This command allows you to update data used by Aduser')
            ->addOption('full', 'f', InputOption::VALUE_NONE, 'Perform full update');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->lock()) {
            $io->warning('The command is already running in another process.');
            return self::FAILURE;
        }

        $io->comment('Updating pages...');

        $changedAfter = null;
        if (!$input->getOption('full')) {
            $changedAfter = new DateTimeImmutable('-24 hours');
        }

        if ($this->pageInfo->update($changedAfter)) {
            $io->success('Pages successfully updated!');
        } else {
            $io->error('Pages updated with errors');
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
