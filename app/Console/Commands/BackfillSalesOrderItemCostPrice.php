<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SalesOrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class BackfillSalesOrderItemCostPrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sales-order:backfill-cost-price';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill cost_price untuk SalesOrderItem yang NULL dari product cost_price saat ini';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai backfill cost_price untuk SalesOrderItem...');
        
        $items = SalesOrderItem::whereNull('cost_price')
            ->whereNotNull('product_id')
            ->with('product')
            ->get();
        
        $total = $items->count();
        $this->info("Ditemukan {$total} item yang perlu di-backfill.");
        
        if ($total === 0) {
            $this->info('Tidak ada item yang perlu di-backfill.');
            return 0;
        }
        
        $bar = $this->output->createProgressBar($total);
        $bar->start();
        
        $updated = 0;
        $failed = 0;
        
        foreach ($items as $item) {
            try {
                if ($item->product && $item->product->cost_price) {
                    $item->update(['cost_price' => $item->product->cost_price]);
                    $updated++;
                } else {
                    // Jika product tidak ada atau cost_price null, set ke 0
                    $item->update(['cost_price' => 0]);
                    $updated++;
                }
            } catch (\Exception $e) {
                $failed++;
                $this->error("\nError pada item ID {$item->id}: " . $e->getMessage());
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        
        $this->info("Backfill selesai!");
        $this->info("Berhasil: {$updated} item");
        if ($failed > 0) {
            $this->warn("Gagal: {$failed} item");
        }
        
        return 0;
    }
}
