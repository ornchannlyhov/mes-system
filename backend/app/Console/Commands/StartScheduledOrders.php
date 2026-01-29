<?php

namespace App\Console\Commands;

use App\Models\ManufacturingOrder;
use App\Services\ManufacturingOrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class StartScheduledOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:start-scheduled-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start scheduled manufacturing orders whose start date has arrived';

    /**
     * Execute the console command.
     */
    public function handle(ManufacturingOrderService $service)
    {
        $now = now();
        $orders = ManufacturingOrder::where('status', 'scheduled')
            ->where('scheduled_start', '<=', $now)
            ->get();

        if ($orders->isEmpty()) {
            $this->info('No scheduled orders to start.');
            return;
        }

        $this->info("Found {$orders->count()} orders to start.");

        foreach ($orders as $order) {
            try {
                // Ensure we have a valid Model instance
                $mo = $order instanceof ManufacturingOrder ? $order : ManufacturingOrder::find($order->id);

                if (!$mo) {
                    continue;
                }

                $service->start($mo);
                $this->info("Started MO: {$mo->name}");
                Log::info("Auto-started MO: {$mo->name}");
            } catch (\Exception $e) {
                // Use safe access to properties in case it's strictly stdClass without name
                $name = $order->name ?? 'Unknown';
                $this->error("Failed to start MO: {$name}. Error: {$e->getMessage()}");
                Log::error("Failed to auto-start MO: {$name}. Error: {$e->getMessage()}");
            }
        }

        $this->info('Process completed.');
    }
}
