<?php
/**
 * InitCommand.php
 *
 * Author: Larry Li <larryli@qq.com>
 */

namespace larryli\ipv4\console\commands;

use larryli\ipv4\console\Config;
use larryli\ipv4\DatabaseQuery;
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
        $force = $input->getOption('force');
        $output->writeln("<info>initialize ip database:</info>");
        foreach (Config::getInstance()->providers as $name => $provider) {
            if (empty($provider)) {
                $this->download($output, $name, $force);
            } else {
                $this->division($output);
                $this->generate($output, $name, $force, $provider);
            }
        }
    }

    /**
     * @param OutputInterface $output
     * @param string $name
     * @param bool $force
     * @return void
     * @throws \Exception
     */
    protected function download(OutputInterface $output, $name, $force)
    {
        $query = Config::getInstance()->getQuery($name);
        $name = $query->name();
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
     * @param string $name
     * @param bool $force
     * @param array $providers
     * @return void
     * @throws \Exception
     */
    protected function generate(OutputInterface $output, $name, $force, $providers)
    {
        $query = Config::getInstance()->getQuery($name);
        if (is_array($providers)) {
            $provider = Config::getInstance()->getQuery($providers[0]);
            $use = $provider->name();
            $provider_extra = Config::getInstance()->getQuery(@$providers[1]);
            if (!empty($provider_extra)) {
                $use .= ' and ' . $provider_extra->name();
            }
        } else {
            throw new \Exception("Error generate options {$providers}");
        }
        $name = $query->name();
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
            }, $provider, $provider_extra);
            $output->writeln('<info> completed!</info>');
        }
    }
}
