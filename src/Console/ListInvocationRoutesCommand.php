<?php

namespace AlazziAz\LaravelDaprInvoker\Console;


use AlazziAz\LaravelDaprInvoker\Support\InvocationRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ListInvocationRoutesCommand extends Command
{
    protected $signature = 'dapr-invoker:list {--json : Output JSON instead of a table}';

    protected $description = 'List registered Dapr invocation routes and handlers.';

    public function __construct(
        protected InvocationRegistry $registry
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $rows = [];
        $prefix = trim(config('dapr.invocation.prefix', 'dapr/invoke'), '/');
        $handlers = $this->registry->all();

        foreach ($handlers as $method => $handler) {
            $rows[] = [
                'method' => $method,
                'handler' => $this->formatHandler($handler),
                'route' => '/' . $prefix . '/' . $method,
            ];
        }

        if ($this->option('json')) {
            $this->line(json_encode($rows, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        if (empty($rows)) {
            $this->components->warn('No Dapr invocation routes registered.');

            return self::SUCCESS;
        }

        $this->table(['Method', 'Handler', 'Route'], $rows);

        return self::SUCCESS;
    }

    protected function formatHandler(mixed $handler): string
    {
        if (is_string($handler)) {
            return $handler;
        }

        if (is_array($handler) && isset($handler[0])) {
            $class = is_string($handler[0]) ? $handler[0] : (is_object($handler[0]) ? $handler[0]::class : gettype($handler[0]));
            $method = $handler[1] ?? '__invoke';

            return sprintf('%s@%s', $class, $method);
        }

        if ($handler instanceof \Closure) {
            return 'closure';
        }

        if (is_object($handler)) {
            return $handler::class;
        }

        return (string) Str::of(var_export($handler, true))->limit(40);
    }
}
