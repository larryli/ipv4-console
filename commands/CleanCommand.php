<?php
/**
 * CleanCommand.php
 *
 * Author: Larry Li <larryli@qq.com>
 */

namespace larryli\ipv4\console\commands;

use larryli\ipv4\console\Config;
use larryli\ipv4\DatabaseQuery;
use larryli\ipv4\FileQuery;
use larryli\ipv4\Query;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CleanCommand
 * @package larryli\ipv4\console\commands
 */
class CleanCommand extends Command
{
    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName('clean')
            ->setDescription('clean ip database')
            ->addArgument(
                'type',
                InputArgument::OPTIONAL,
                "file or database",
                'all');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = Config::getInstance();
        $cleanDivision = false;
        $type = $input->getArgument('type');
        $output->writeln("<info>clean {$type}:</info>");
        switch ($type) {
            case 'all':
                foreach ($config->getQueries() as $name => $query) {
                    if (DatabaseQuery::is_a($query)) {
                        $cleanDivision = true;
                    }
                    $this->clean($output, $query, $name);
                }
                break;
            case 'file':
                foreach ($config->getQueries() as $name => $query) {
                    if (FileQuery::is_a($query)) {
                        $this->clean($output, $query, $name);
                    }
                }
                break;
            case 'database':
                foreach ($config->getQueries() as $name => $query) {
                    if (DatabaseQuery::is_a($query)) {
                        $cleanDivision = true;
                        $this->clean($output, $query, $name);
                    }
                }
                break;
            default:
                $output->writeln("<error>Unknown type \"{$type}\".</error>");
                break;
        }
        if ($cleanDivision) {
            $this->cleanDivision($output);
        }
    }

    /**
     * @param OutputInterface $output
     * @param Query $query
     * @param string $name
     * @throws \Exception
     */
    private function clean(OutputInterface $output, Query $query, $name)
    {
        $output->write("<info>clean {$name}:</info>");
        $query->clean();
        $output->writeln('<info> completed!</info>');
    }

    /**
     * @param OutputInterface $output
     */
    private function cleanDivision(OutputInterface $output)
    {
        $output->write("<info>clean divisions:</info>");
        DatabaseQuery::cleanDivision();
        $output->writeln('<info> completed!</info>');
    }

}
