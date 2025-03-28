<?php

namespace Rikudou\CronBundle\Command;

use Cron\CronExpression;
use DateTimeZone;
use InvalidArgumentException;
use Rikudou\CronBundle\Cron\CronJobList;
use Rikudou\CronBundle\Cron\DisableableCronJobInterface;
use Rikudou\CronBundle\Cron\NamedCronJobInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'cron:list')]
final class ListCronJobsCommand extends Command
{
    public function __construct(private CronJobList $cronJobList)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Lists all cron jobs')
            ->addOption(
                'timezone',
                't',
                InputOption::VALUE_REQUIRED,
                'Timezone in which the next run date is displayed',
                'UTC',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $timezone = new DateTimeZone($input->getOption('timezone'));
        $cronJobs = $this->cronJobList->getCronJobs();
        $cronCommands = $this->cronJobList->getCommands();

        if (!count($cronJobs) && !count($cronCommands)) {
            $output->writeln('There are no cron jobs.');
            return 0;
        }

        $table = new Table($output);
        $table->setHeaders([
            'Name',
            'Enabled',
            'Type',
            'Cron expression',
            'Is due',
            'Next run'
        ]);

        foreach ($cronJobs as $cronJob) {
            if ($cronJob instanceof NamedCronJobInterface) {
                $name = $cronJob->getName();
            } else {
                $name = get_class($cronJob);
            }
            if ($cronJob instanceof DisableableCronJobInterface) {
                $isEnabled = $cronJob->isEnabled();
            } else {
                $isEnabled = true;
            }
            $isEnabled = $isEnabled ? 'Yes' : 'No';
            try {
                $cronExpression = new CronExpression($cronJob->getCronExpression());

                $table->addRow([
                    $name,
                    $isEnabled,
                    'Class',
                    $cronJob->getCronExpression(),
                    $cronExpression->isDue() ? 'Yes' : 'No',
                    $cronExpression
                        ->getNextRunDate()
                        ->setTimezone($timezone)
                        ->format('c')
                ]);
            } catch (InvalidArgumentException $e) {
                $table->addRow([
                    "<error>{$name}</error>",
                    "<error>{$isEnabled}</error>",
                    "<error>Class</error>",
                    "<error>{$cronJob->getCronExpression()}",
                    "<error>Cron expression is invalid</error>",
                    "<error>Cron expression is invalid</error>",
                ]);
            }
        }

        foreach ($cronCommands as $name => $cronCommand) {
            $isEnabled = $cronCommand['enabled'] ? 'Yes' : 'No';

            try {
                $cronExpression = new CronExpression($cronCommand['expression']);

                $table->addRow([
                    $name,
                    $isEnabled,
                    'Command',
                    $cronCommand['expression'],
                    $cronExpression->isDue(),
                    $cronExpression
                        ->getNextRunDate()
                        ->setTimezone($timezone)
                        ->format('c')
                ]);
            } catch (InvalidArgumentException $e) {
                $table->addRow([
                    "<error>{$name}</error>",
                    "<error>{$isEnabled}</error>",
                    "<error>Class</error>",
                    "<error>{$cronCommand['expression']}",
                    "<error>Cron expression is invalid</error>",
                    "<error>Cron expression is invalid</error>",
                ]);
            }
        }

        $table->render();

        return 0;
    }
}
