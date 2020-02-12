<?php

namespace Rikudou\CronBundle\Cron;

interface NamedCronJobInterface extends CronJobInterface
{
    /**
     * Returns the short name of the cron job (shown in listing and can be used for test execution)
     *
     * @return string
     */
    public function getName(): string;
}
