<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DiskCheck extends Command
{
    protected $signature = 'disk:check {disk=tenant_logos}';
    protected $description = 'Check filesystem disk connectivity';

    public function handle(): int
    {
        $disk = $this->argument('disk');

        try {
            $storage = Storage::disk($disk);

            $this->info("Disk: {$disk}");

            $ok = $storage->put('healthchecks/ping.txt', 'ok ' . now());
            $this->info('PUT: ' . ($ok ? 'OK' : 'FAILED'));

            $content = $storage->get('healthchecks/ping.txt');
            $this->info('GET: ' . substr($content, 0, 60));

            $storage->get('healthchecks/ping.txt'); // GET is enough
            $this->info('EXISTS: ' . ($exists ? 'YES' : 'NO'));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $prev = $e->getPrevious();
            $this->error('ERROR: ' . $e->getMessage());
            if ($prev) {
                $this->error('PREV: ' . $prev->getMessage());
            }
            return self::FAILURE;
        }
    }
}