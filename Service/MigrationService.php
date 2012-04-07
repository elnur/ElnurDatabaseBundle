<?php
/*
 * Copyright (c) 2011-2012 Elnur Abdurrakhimov
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
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
        $this->initSchemaTable();

        $dir = $this->rootDir . '/../db/migrations';

        $this->db->beginTransaction();

        foreach ($this->findMigrations($dir) as $migration) {
            if ($migration <= $this->getCurrentVersion()) {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $migration . '.sql';

            $this->db->exec(file_get_contents($path));
            $this->db->exec("UPDATE schema SET version = '{$migration}'");
        }

        $this->db->commit();
    }

    /**
     * @param  string $dir
     * @return array
     */
    private function findMigrations($dir)
    {
        $migrations = array_filter(scandir($dir), function($filename) {
            if (!preg_match('|^\d{12}\.sql$|', $filename)) {
                return false;
            }

            return true;
        });

        array_walk($migrations, function($value, $key) use(&$migrations) {
            $migrations[$key] = substr($value, 0, 12);
        });

        return $migrations;
    }

    private function initSchemaTable()
    {
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS schema(version char(12) PRIMARY KEY)'
        );
    }

    /**
     * @return string
     */
    private function getCurrentVersion()
    {
        $result = $this->db->query('SELECT version FROM schema');
        $version = $result->fetchColumn();

        if (!$version) {
            $version = '000000000000';
            $this->db->exec("INSERT INTO schema VALUES ('{$version}')");
            return $version;
        }

        return $version;
    }
}
