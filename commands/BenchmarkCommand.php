<?php
/**
 * BenchmarkCommand.php
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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class BenchmarkCommand
 * @package larryli\ipv4\console\commands
 */
class BenchmarkCommand extends Command
{
    /**
     *
     */
    protected function configure()
    {
        $this->setName('benchmark')
            ->setDescription('benchmark')
            ->addOption(
                'times',
                't',
                InputOption::VALUE_OPTIONAL,
                'number of times',
                100000
            )
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
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = Config::getInstance();
        $times = $input->getOption('times');
        if ($times < 1) {
            $output->writeln("<error>benchmark {$times} is too small</error>");
            return;
        }
        $type = $input->getArgument('type');
        $output->writeln("<info>benchmark {$type}:</info>\t<comment>{$times} times</comment>");
        switch ($type) {
            case 'all':
                foreach ($config->getQueries() as $name => $query) {
                    $this->benchmark($output, $query, $name, $times);
                }
                break;
            case 'file':
                foreach ($config->getQueries() as $name => $query) {
                    if (FileQuery::is_a($query)) {
                        $this->benchmark($output, $query, $name, $times);
                    }
                }
                break;
            case 'database':
                foreach ($config->getQueries() as $name => $query) {
                    if (DatabaseQuery::is_a($query)) {
                        $this->benchmark($output, $query, $name, $times);
                    }
                }
                break;
            default:
                $output->writeln("<error>Unknown type \"{$type}\".</error>");
                break;
        }
    }

    /**
     * @param OutputInterface $output
     * @param Query $query
     * @param string $name
     * @param integer $times
     * @throws \Exception
     */
    private function benchmark(OutputInterface $output, Query $query, $name, $times)
    {
        $step = intval(4000000000 / $times);
        if ($step < 1) {
            $step = 1;
        }
        if (count($query) > 0) {
            $output->write("\t<info>benchmark {$name}:</info> \t");
            $start = microtime(true);
            for ($ip = 0, $i = 0; $i < $times; $ip += $step, $i++) {
                $query->find($ip);
            }
            $time = microtime(true) - $start;
            $output->writeln("<comment>{$time} secs</comment>");
        }
    }

}
