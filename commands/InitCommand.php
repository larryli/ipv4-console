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
        $output->writeln("<info>initialize ip database:</info>");
        foreach ($config->getQueries() as $name => $query) {
            if (empty($query->getProviders())) {
                $this->download($output, $query, $name, $force);
            } else {
                $this->division($output);
                $this->generate($output, $query, $name, $force);
            }
        }
    }

    /**
     * @param OutputInterface $output
     * @param Query $query
     * @param string $name,
     * @param bool $force
     * @return void
     * @throws \Exception
     */
    protected function download(OutputInterface $output, Query $query, $name, $force)
    {

        if (!$force && $query->exists()) {
            $output->writeln("<comment>use exist {$name} file or api.</comment>", OutputInterface::VERBOSITY_VERBOSE);
        } else {
            $output->writeln("<info>download {$name} file:</info>");
            $query->init(function ($url) use ($output) {
                return file_get_contents($url, false, $this->createStreamContext($output));
            });
            $output->writeln('<info> completed!</info>');
        }
    }

    /**
     * @param OutputInterface $output
     *
     * @return resource
     */
    protected function createStreamContext(OutputInterface $output)
    {
        $ctx = stream_context_create([], [
            'notification' => function ($code, $severity, $message, $message_code, $bytesTransferred, $bytesMax) use ($output) {
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
            }
        ]);
        return $ctx;
    }

    /**
     * @param OutputInterface $output
     */
    protected function division(OutputInterface $output)
    {
        DatabaseQuery::initDivision(function ($code, $n) use ($output) {
            switch ($code) {
                case 0:
                    $output->writeln("<info>generate divisions table:</info>");
                    $this->progress = new ProgressBar($output, $n);
                    $this->progress->start();
                    break;
                case 1:
                    $this->progress->setProgress($n);
                    break;
                case 2:
                    $this->progress->finish();
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
     * @return void
     * @throws \Exception
     */
    protected function generate(OutputInterface $output, Query $query, $name, $force)
    {
        $use = implode(', ', $query->getProviders());
        if (!$force && $query->exists()) {
            $output->writeln("<comment>use exist {$name} table.</comment>", OutputInterface::VERBOSITY_VERBOSE);
        } else {
            $output->writeln("<info>generate {$name} table with {$use}:</info>");
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
            $output->writeln('<info> completed!</info>');
        }
    }
}
