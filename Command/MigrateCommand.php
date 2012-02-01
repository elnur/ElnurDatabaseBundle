<?php
namespace Elnur\DatabaseBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;

class MigrateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('elnur:database:migrate');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $migrationService = $container->get('elnur.database.migration_service');
        $migrationService->migrate();
    }
}
