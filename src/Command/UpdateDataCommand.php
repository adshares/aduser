<?php

namespace Adshares\Aduser\Command;

use Adshares\Aduser\DataProvider\DataProviderInterface;
use Adshares\Aduser\DataProvider\DataProviderManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateDataCommand extends Command
{
    protected static $defaultName = 'aduser:update';

    /** @var DataProviderManager|DataProviderInterface[] */
    private $providers;

    public function __construct(DataProviderManager $providers, string $name = null)
    {
        parent::__construct($name);
        $this->providers = $providers;
    }

    protected function configure()
    {
        $this->setDescription('Update Aduser external data')->setHelp(
            'This command allows you to update external data used by Aduser'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $success = true;
        foreach ($this->providers as $provider) {
            $output->writeln(sprintf('Updating %s provider', $provider->getName()));
            $success = $provider->updateData() && $success;
        }
        if ($success) {
            $output->writeln('External data successfully updated!');
        } else {
            $output->writeln('External data updated with errors');
        }
    }
}