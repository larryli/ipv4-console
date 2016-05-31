<?php
/**
 * InitCommand.php
 *
 * Author: Larry Li <larryli@qq.com>
 */

namespace larryli\ipv4\console\commands;

use larryli\ipv4\console\Config;
use larryli\ipv4\DatabaseQuery;
use larryli\ipv4\Query;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class InitCommand
 * @package larryli\ipv4\console\commands
 */
class InitCommand extends Command
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
        $this->setName('init')
            ->setDescription('initialize ip database')
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force to initialize(download qqwry.dat & 17monipdb.dat if not exist & generate new database)'
            )
            ->addOption(
                'no-progress',
                '',
                InputOption::VALUE_NONE,
                'Do not show progress bar.'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = Config::getInstance();
        $force = $input->getOption('force');
        $noProgress = $input->getOption('no-progress');
        $output->writeln("<info>initialize ip database:</info>");
        foreach ($config->getQueries() as $name => $query) {
            if (empty($query->getProviders())) {
                $this->download($output, $query, $name, $force, $noProgress);
            } else {
                $this->division($output, $noProgress);
                $this->generate($output, $query, $name, $force, $noProgress);
            }
        }
    }

    /**
     * @param OutputInterface $output
     * @param Query $query
     * @param string $name ,
     * @param bool $force
     * @param bool $noProgress
     * @return void
     * @throws \Exception
     */
    protected function download(OutputInterface $output, Query $query, $name, $force, $noProgress)
    {

        if (!$force && $query->exists()) {
            $output->writeln("<comment>use exist {$name} file or api.</comment>", OutputInterface::VERBOSITY_VERBOSE);
        } else {
            $output->writeln("<info>download {$name} file:</info>");
            $query->init(function ($url) use ($output, $noProgress) {
                return file_get_contents($url, false, $this->createStreamContext($output, $noProgress));
            });
            $output->writeln('<info> completed!</info>');
        }
    }

    /**
     * @param OutputInterface $output
     * @param bool $noProgress
     * @return resource
     */
    protected function createStreamContext(OutputInterface $output, $noProgress)
    {
        $params = [];
        if (!$noProgress) {
            $params['notification'] = function ($code, $severity, $message, $message_code, $bytesTransferred, $bytesMax) use ($output) {
                switch ($code) {
                    case STREAM_NOTIFY_FILE_SIZE_IS:
                        $this->progress = new ProgressBar($output, $bytesMax);
                        $this->progress->start();
                        break;
                    case STREAM_NOTIFY_PROGRESS:
                        $this->progress->setProgress($bytesTransferred);
                        if ($bytesTransferred == $bytesMax) {
                            $this->progress->finish();
                        }
                        break;
                    case STREAM_NOTIFY_COMPLETED:
                        $this->progress->finish();
                        break;
                }
            };
        }
        return stream_context_create([], $params);
    }

    /**
     * @param OutputInterface $output
     * @param bool $noProgress
     */
    protected function division(OutputInterface $output, $noProgress)
    {
        DatabaseQuery::initDivision(function ($code, $n) use ($output, $noProgress) {
            switch ($code) {
                case 0:
                    $output->writeln("<info>generate divisions table:</info>");
                    if (!$noProgress) {
                        $this->progress = new ProgressBar($output, $n);
                        $this->progress->start();
                    }
                    break;
                case 1:
                    if (!$noProgress) {
                        $this->progress->setProgress($n);
                    }
                    break;
                case 2:
                    if (!$noProgress) {
                        $this->progress->finish();
                    }
                    $output->writeln('<info> completed!</info>');
                    break;
            }
        }, true);
    }

    /**
     * @param OutputInterface $output
     * @param Query $query
     * @param string $name
     * @param bool $force
     * @param bool $noProgress
     * @return void
     * @throws \Exception
     */
    protected function generate(OutputInterface $output, Query $query, $name, $force, $noProgress)
    {
        $use = implode(', ', $query->getProviders());
        if (!$force && $query->exists()) {
            $output->writeln("<comment>use exist {$name} table.</comment>", OutputInterface::VERBOSITY_VERBOSE);
        } else {
            $output->writeln("<info>generate {$name} table with {$use}:</info>");
            if (!$noProgress) {
                $query->init(function ($code, $n) use ($output) {
                    switch ($code) {
                        case 0:
                            $this->progress = new ProgressBar($output, $n);
                            $this->progress->start();
                            break;
                        case 1:
                            $this->progress->setProgress($n);
                            break;
                        case 2:
                            $this->progress->finish();
                            break;
                    }
                });
            } else {
                $query->init();
            }
            $output->writeln('<info> completed!</info>');
        }
    }
}
