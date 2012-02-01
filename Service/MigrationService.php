<?php
namespace Elnur\DatabaseBundle\Service;

use Doctrine\DBAL\Driver\Connection;

class MigrationService
{
    /**
     * @var \Doctrine\DBAL\Driver\Connection
     */
    private $db;

    /**
     * @var string
     */
    private $rootDir;

    public function __construct(Connection $db, $rootDir)
    {
        $this->db = $db;
        $this->rootDir = $rootDir;
    }

    public function migrate()
    {
        $dir = $this->rootDir . '/../db/migrations';
        $migrations = array_filter(scandir($dir), function($filename) {
            if (!preg_match('|^\d{12}\.sql$|', $filename)) {
                return false;
            }

            return true;
        });

        array_walk($migrations, function($value, $key) use(&$migrations) {
            $migrations[$key] = substr($value, 0, 12);
        });

        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS schema(version char(12) PRIMARY KEY)'
        );

        $result = $this->db->query('SELECT version FROM schema');
        $version = $result->fetchColumn();

        if (!$version) {
            $version = '000000000000';
            $this->db->exec("INSERT INTO schema VALUES ('{$version}')");
        }

        $this->db->beginTransaction();

        foreach ($migrations as $migration) {
            if ($migration <= $version) {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $migration . '.sql';

            $this->db->exec(file_get_contents($path));
            $this->db->exec("UPDATE schema SET version = '{$migration}'");
        }

        $this->db->commit();
    }
}
