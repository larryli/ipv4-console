<?php
/**
 * DumpCommand.php
 *
 * Author: Larry Li <larryli@qq.com>
 */

namespace larryli\ipv4\console\commands;

use larryli\ipv4\console\Config;
use larryli\ipv4\FileQuery;
use larryli\ipv4\Query;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DumpCommand
 * @package larryli\ipv4\console\commands
 */
class DumpCommand extends Command
{
    /**
     * @var ProgressBar|null
     */
    private $progress = null;

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName('dump')
            ->setDescription('dump ip database')
            ->addOption(
                'no-progress',
                '',
                InputOption::VALUE_NONE,
                'Do not show progress bar.'
            )
            ->addArgument(
                'type',
                InputArgument::OPTIONAL,
                "division or division_id or count",
                'default');
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
        $noProgress = $input->getOption('no-progress');
        $type = $input->getArgument('type');
        $output->writeln("<info>dump {$type}:</info>");
        switch ($type) {
            case 'default':
                foreach ($config->getQueries() as $name => $query) {
                    $this->dumpDefault($output, $query, $name, $noProgress);
                }
                break;
            case 'division':
                foreach ($config->getQueries() as $name => $query) {
                    $this->dumpDivision($output, $query, $name, $noProgress);
                }
                break;
            case 'division_id':
                foreach ($config->getQueries() as $name => $query) {
                    if (FileQuery::is_a($query)) {
                        $this->dumpDivisionWithId($output, $query, $name, $noProgress);
                    }
                }
                break;
            case 'count':
                foreach ($config->getQueries() as $name => $query) {
                    $this->dumpCount($output, $query, $name, $noProgress);
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
     * @param bool $noProgress
     * @throws \Exception
     */
    private function dumpDefault(OutputInterface $output, Query $query, $name, $noProgress)
    {
        $filename = 'dump_' . $name . '.json';
        $result = $this->dump($output, $query, $filename, $noProgress);
        if (count($result) > 0) {
            $this->write($output, $filename, $result);
        }
    }

    /**
     * @param OutputInterface $output
     * @param Query $query
     * @param $filename
     * @param bool $noProgress
     * @return array
     */
    private function dump(OutputInterface $output, Query $query, $filename, $noProgress)
    {
        $result = [];
        if (count($query) > 0) {
            $output->writeln("<info>dump {$filename}:</info>");
            if (!$noProgress) {
                $this->progress = new ProgressBar($output, count($query));
                $this->progress->start();
            }
            $n = 0;
            $time = Query::time();
            foreach ($query as $ip => $division) {
                if (is_integer($division)) {
                    $division = $query->divisionById($division);
                }
                $result[long2ip($ip)] = $division;
                $n++;
                if (!$noProgress && $time < Query::time()) {
                    $this->progress->setProgress($n);
                    $time = Query::time();
                }
            }
            if (!$noProgress) {
                $this->progress->finish();
            }
            $output->writeln('<info> completed!</info>');
        }
        return $result;
    }

    /**
     * @param OutputInterface $output
     * @param string $filename
     * @param string[] $result
     */
    private function write(OutputInterface $output, $filename, $result)
    {
        $output->write("<info>write {$filename}:</info>");
        file_put_contents($filename, json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $output->writeln('<info> completed!</info>');
    }

    /**
     * @param OutputInterface $output
     * @param Query $query
     * @param string $name
     * @param bool $noProgress
     * @throws \Exception
     */
    private function dumpDivision(OutputInterface $output, Query $query, $name, $noProgress)
    {
        $filename = 'dump_' . $name . '_division.json';
        $result = $this->divisions($output, $query, $filename, 'dump_' . $name . '.json', $noProgress);
        if (count($result) > 0) {
            $result = array_unique(array_values($result));
            sort($result);
            $this->write($output, $filename, $result);
        }
    }

    /**
     * @param OutputInterface $output
     * @param Query $query
     * @param $filename
     * @param $json_filename
     * @param bool $noProgress
     * @return array|\string[]
     */
    private function divisions(OutputInterface $output, Query $query, $filename, $json_filename, $noProgress)
    {
        if (file_exists($json_filename)) {
            $result = $this->read($output, $json_filename);
        } else {
            $result = $this->dump($output, $query, $filename, $noProgress);
        }
        $result = array_unique(array_values($result));
        sort($result);
        return $result;
    }

    /**
     * @param OutputInterface $output
     * @param string $filename
     * @return string[] $result
     */
    private function read(OutputInterface $output, $filename)
    {
        $output->write("<info>read {$filename}:</info>");
        $result = json_decode(file_get_contents($filename), true);
        $output->writeln('<info> completed!</info>');
        return $result;
    }

    /**
     * @param OutputInterface $output
     * @param Query $query
     * @param string $name
     * @param bool $noProgress
     * @throws \Exception
     */
    private function dumpDivisionWithId(OutputInterface $output, Query $query, $name, $noProgress)
    {
        $filename = 'dump_' . $name . '_division_id.json';
        $json_filename = 'dump_' . $name . '_division.json';
        if (file_exists($json_filename)) {
            $result = $this->read($output, $json_filename);
        } else {
            $result = $this->divisions($output, $query, $filename, 'dump_' . $query . '.json', $noProgress);
        }
        if (count($result) > 0) {
            $result = $this->divisionsWithId($output, $query, $result, $noProgress);
            $this->write($output, $filename, $result);
        }
    }

    /**
     * @param OutputInterface $output
     * @param Query $query
     * @param string[] $divisions
     * @param bool $noProgress
     * @return array
     */
    private function divisionsWithId(OutputInterface $output, Query $query, $divisions, $noProgress)
    {
        $result = [];
        $output->writeln("<info>translate division to division_id:</info>");
        if (!$noProgress) {
            $this->progress = new ProgressBar($output, count($divisions));
            $this->progress->start();
        }
        $n = 0;
        $time = Query::time();
        foreach ($divisions as $division) {
            $result[$division] = $query->idByDivision($division);
            $n++;
            if (!$noProgress && $time < Query::time()) {
                $this->progress->setProgress($n);
                $time = Query::time();
            }
        }
        if (!$noProgress) {
            $this->progress->finish();
        }
        $output->writeln('<info> completed!</info>');
        return $result;
    }

    /**
     * @param OutputInterface $output
     * @param Query $query
     * @param $name
     * @param bool $noProgress
     */
    private function dumpCount(OutputInterface $output, Query $query, $name, $noProgress)
    {
        $filename = 'dump_' . $name . '_count.json';
        $result = [];
        if (count($query) > 0) {
            $output->writeln("<info>dump {$filename}:</info>");
            if (!$noProgress) {
                $this->progress = new ProgressBar($output, count($query));
                $this->progress->start();
            }
            $n = 0;
            $time = Query::time();
            $last = -1;
            foreach ($query as $ip => $division) {
                if (is_integer($division)) {
                    $id = $division;
                    $division = $query->divisionById($id);
                } else {
                    $id = $query->idByDivision($division);
                }
                if ($id === null) {
                    die(long2ip($ip));
                }
                $count = $ip - $last;
                $last = $ip;
                $result[$id]['id'] = $id;
                $result[$id]['division'] = empty($id) ? '' : $division;
                @$result[$id]['records'] += 1;    // 纪录数
                @$result[$id]['count'] += $count;   // IP 数
                if ($id > 100000) { // 中国
                    @$result[1]['records'] += 1;
                    @$result[1]['children_records'] += 1;
                    @$result[1]['count'] += $count;
                    @$result[1]['children_count'] += $count;
                    $province = intval($id / 10000) * 10000;
                    if ($province != $id) {
                        @$result[$province]['records'] += 1;
                        @$result[$province]['children_records'] += 1;
                        @$result[$province]['count'] += $count;
                        @$result[$province]['children_count'] += $count;
                    }
                }
                $n++;
                if (!$noProgress && $time < Query::time()) {
                    $this->progress->setProgress($n);
                    $time = Query::time();
                }
            }
            if (!$noProgress) {
                $this->progress->finish();
            }
            $output->writeln('<info> completed!</info>');
        }
        ksort($result);
        $result = array_map(function ($data) {
            $result = [
                'id' => $data['id'],
                'division' => $data['division'],
                'records' => $data['records'],
                'count' => $data['count'],
            ];
            if (isset($data['children_records'])) {
                $result['self']['records'] = $data['records'] - $data['children_records'];
                $result['children']['records'] = $data['children_records'];
            }
            if (isset($data['children_count'])) {
                $result['self']['count'] = $data['count'] - $data['children_count'];
                $result['children']['count'] = $data['children_count'];
            }
            return $result;
        }, array_values($result));
        if (count($result) > 0) {
            $this->write($output, $filename, $result);
        }
    }
}
