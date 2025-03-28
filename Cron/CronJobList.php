<?php

namespace Rikudou\CronBundle\Cron;

use Cron\CronExpression;
use Psr\Clock\ClockInterface;
use Symfony\Component\Clock\NativeClock;

final class CronJobList
{
    /**
     * @var CronJobInterface[]
     */
    private array $cronJobs = [];

    /**
     * @var array<string,array<string, string>>
     */
    private array $commands = [];

    public function __construct(
        private ?ClockInterface $clock,
    ) {
        $this->clock ??= new NativeClock();
    }

    public function addCronJob(CronJobInterface $cronJob): void
    {
        $key = get_class($cronJob);
        if ($cronJob instanceof NamedCronJobInterface) {
            $key = $cronJob->getName();
        }

        $this->cronJobs[$key] = $cronJob;
    }

    public function setCommands(array $commands): void
    {
        $this->commands = $commands;
    }

    /**
     * @return CronJobInterface[]
     */
    public function getCronJobs(): array
    {
        return $this->cronJobs;
    }

    /**
     * @return array<string,array<string, string>>
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    public function findByName(string $name): ?CronJobInterface
    {
        return $this->cronJobs[$name] ?? null;
    }

    /**
     * @return CronJobInterface[]
     */
    public function getDueJobs(): array
    {
        return array_filter($this->cronJobs, function (CronJobInterface $cronJob) {
            if ($cronJob instanceof DisableableCronJobInterface && !$cronJob->isEnabled()) {
                return false;
            }
            $cronExpression = new CronExpression($cronJob->getCronExpression());
            $now = $this->clock->now();

            return $cronExpression->isDue($now->format('c'), $now->getTimezone()->getName());
        });
    }

    /**
     * @return array<array{command: string, expression: string, enabled: bool, args: array<string, mixed>, opts: array<string, mixed>}>
     */
    public function getDueCommands(): array
    {
        return array_filter($this->commands, function (array $commandData) {
            if (!$commandData['enabled']) {
                return false;
            }
            $cronExpression = new CronExpression($commandData['expression']);
            $now = $this->clock->now();

            return $cronExpression->isDue($now->format('c'), $now->getTimezone()->getName());
        });
    }
}
