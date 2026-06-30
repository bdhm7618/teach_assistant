<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateSwaggerDocs extends Command
{
    protected $signature = 'swagger:generate';
    protected $description = 'Generate Swagger/OpenAPI documentation (warnings suppressed)';

    public function handle(): int
    {
        // Temporarily restore the default PHP error handler so swagger-php's
        // trigger_error(E_USER_WARNING) calls don't throw ErrorException.
        $previous = set_error_handler(function ($level, $message) {
            if ($level === E_USER_WARNING || $level === E_USER_NOTICE) {
                $this->warn($message);
                return true;
            }
            return false;
        });

        try {
            $this->call('l5-swagger:generate');
            $this->info('Swagger docs generated successfully.');
            return self::SUCCESS;
        } finally {
            set_error_handler($previous);
        }
    }
}
