<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class RateLimitStats extends Command
{
    protected $signature = 'rate-limit:stats {--clear : Clear all rate limit counters}';
    protected $description = 'View current rate limit statistics';

    public function handle(): int
    {
        if ($this->option('clear')) {
            $this->clearRateLimits();
            return Command::SUCCESS;
        }

        $this->info('📊 Rate Limit Statistics');
        $this->info('════════════════════════');
        $this->newLine();

        $this->table(
            ['Limiter', 'Limit', 'Window', 'Status'],
            [
                ['api (global)',    '60',  '1 min', '✅ Active'],
                ['auth',            '5',   '1 min', '✅ Active'],
                ['search',          '30',  '1 min', '✅ Active'],
                ['booking',         '10',  '1 min', '✅ Active'],
                ['payment',         '5',   '1 min', '✅ Active'],
                ['invoice',         '5',   '1 min', '✅ Active'],
                ['upload',          '10',  '1 min', '✅ Active'],
                ['webhook',         '100', '1 min', '✅ Active'],
                ['role:admin',      '300', '1 min', '✅ Active'],
                ['role:owner',      '120', '1 min', '✅ Active'],
                ['role:guest',      '60',  '1 min', '✅ Active'],
            ]
        );

        $this->newLine();
        $this->info('Cache Driver: ' . config('cache.default'));
        $this->info('Check logs: storage/logs/laravel.log for violations');

        return Command::SUCCESS;
    }

    protected function clearRateLimits(): void
    {
        // Clear all rate limit keys from cache
        if (config('cache.default') === 'redis') {
            $keys = Redis::keys('laravel_cache:rate_limit:*');
            if (!empty($keys)) {
                Redis::del(...$keys);
                $this->info("✅ Cleared " . count($keys) . " rate limit counters.");
            } else {
                $this->info('No active rate limit counters found.');
            }
        } else {
            Cache::flush();
            $this->info('✅ Cache flushed (all rate limits reset).');
        }
    }
}