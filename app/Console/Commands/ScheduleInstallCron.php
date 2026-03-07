<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Outputs the exact cron line and one-liner for this app so you can
 * copy-paste or run once per server (no manual path editing).
 */
class ScheduleInstallCron extends Command
{
    protected $signature = 'schedule:install-cron
                            {--user= : System user for crontab (e.g. taskbook-horizon)}
                            {--php= : Path to PHP binary (default: PHP_BINARY or "php")}';

    protected $description = 'Print cron one-liner for this app (run on each server once; path is auto-detected).';

    public function handle(): int
    {
        $path = base_path();
        $php = $this->option('php') ?: (PHP_BINARY ?: 'php');

        $line = '* * * * * cd ' . $path . ' && ' . $php . ' artisan schedule:run >> /dev/null 2>&1';
        $line30 = '* * * * * sleep 30 && cd ' . $path . ' && ' . $php . ' artisan schedule:run >> /dev/null 2>&1';

        $this->line('');
        $this->line('Add this to the <comment>site user</comment> crontab (e.g. CloudPanel site user):');
        $this->line('');
        $this->line('  <info>' . $line . '</info>');
        $this->line('');
        $this->line('Optional second line (~every 30s):');
        $this->line('  <info>' . $line30 . '</info>');
        $this->line('');

        $user = $this->option('user');
        if ($user) {
            $oneLiner = "(crontab -u {$user} -l 2>/dev/null; echo '" . $line . "'; echo '" . $line30 . "') | crontab -u {$user} -";
            $this->line('As <comment>root</comment>, run this to add both lines for user <comment>' . $user . '</comment>:');
            $this->line('');
            $this->line('  <info>' . $oneLiner . '</info>');
            $this->line('');
        } else {
            $this->line('To add as root for a user, run: <comment>php artisan schedule:install-cron --user=USERNAME</comment>');
            $this->line('Example: php artisan schedule:install-cron --user=taskbook-horizon --php=/usr/bin/php8.4');
            $this->line('');
        }

        return self::SUCCESS;
    }
}
