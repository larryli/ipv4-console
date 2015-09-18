<?php
/**
 * ExportCommand.php
 *
 * Author: Larry Li <larryli@qq.com>
 */

namespace larryli\ipv4\console\commands;

use larryli\ipv4\console\Config;
use larryli\ipv4\export\ExportQuery;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ExportCommand
 * @package larryli\ipv4\console\commands
 */
class ExportCommand extends Command
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
        $this->setName('export')
            ->setDescription('export to other ip database file')
            ->addOption(
                'encoding',
                'e',
                InputOption::VALUE_OPTIONAL,
                'file encoding',
                ''
            )
            ->addOption(
                'ecdz',
                'z',
                InputOption::VALUE_OPTIONAL,
                'file encoding',
                false
            )
            ->addOption(
                'remove-ip-in-recode',
                'r',
                InputOption::VALUE_OPTIONAL,
                'file encoding',
                false
            )
            ->addOption(
                'type',
                't',
                InputOption::VALUE_OPTIONAL,
                'monipdb or qqwry',
                'monipdb'
            )
            ->addArgument(
                'query',
                InputArgument::REQUIRED,
                'exists query name'
            )
            ->addArgument(
                'filename',
                InputArgument::REQUIRED,
                'output file'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return null|int null or 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = Config::getInstance();
        $query = $input->getArgument('query');
        $provider = $config->getQuery($query);
        if (empty($provider)) {
            $output->writeln("<error>cannot found query \"{$query}\"</error>");
            $output->writeln("<info>you can execute \"</info>ipv4 edit<info>\" to configure the query</info>");
            return 1;
        }
        $type = $input->getOption('type');
        $filename = $config->getFilename($input->getArgument('filename'));
        $export = ExportQuery::create($type, $filename);
        if (empty($export)) {
            $output->writeln("<error>cannot found export query \"{$type}\"</error>");
            return 2;
        }
        $export->setProviders([$provider]);
        $encoding = $input->getOption('encoding');
        if (!empty($encoding)) {
            $export->setEncoding($encoding);
        }
        $ecdz = $input->getOption('ecdz');
        if ($ecdz && method_exists($export, 'setEcdz')) {
            $export->setEcdz($ecdz);
        }
        $remove_ip_in_recode = $input->getOption('remove-ip-in-recode');
        if ($remove_ip_in_recode && method_exists($export, 'setRemoveIpInRecode')) {
            $export->setRemoveIpInRecode($remove_ip_in_recode);
        }

        $output->writeln("<info>export \"{$query}\" to \"{$type}\" filename \"{$filename}\":</info>");
        $export->init(function ($code, $n) use ($output) {
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
        return 0;
    }
}
