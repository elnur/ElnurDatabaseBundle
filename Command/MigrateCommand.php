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
        $dir = $this->getContainer()->getParameter("kernel.root_dir") . '/../db/migrations';
        $migrations = array_filter(scandir($dir), function($filename) {
            if (!preg_match('|^\d{12}\.sql$|', $filename)) {
                return false;
            }

            return true;
        });

        array_walk($migrations, function($value, $key) use(&$migrations) {
            $migrations[$key] = substr($value, 0, 12);
        });

        $db = $this->getContainer()->get('database_connection');
        $db->exec('CREATE TABLE IF NOT EXISTS schema(version char(12) PRIMARY KEY)');

        $result = $db->query('SELECT version FROM schema');
        $version = $result->fetchColumn();

        if (!$version) {
            $version = '000000000000';
            $db->exec("INSERT INTO schema VALUES ('{$version}')");
        }

        $name = $db->getDatabase();
        $user = $db->getUsername();

        foreach ($migrations as $migration) {
            if ($migration <= $version) {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $migration . '.sql';
            exec(sprintf('psql %s %s -f %s > /dev/null 2>&1', $name, $user, $path));

            $db->exec("UPDATE schema SET version = '{$migration}'");
        }
    }
}
