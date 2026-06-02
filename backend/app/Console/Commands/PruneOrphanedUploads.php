<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneOrphanedUploads extends Command
{
    protected $signature = 'app:prune-uploads {--hours=2 : Delete files older than this many hours}';

    protected $description = 'Delete files in uploads/ that are not referenced by any product and are older than the given hours';

    public function handle(): void
    {
        $hours = (int) $this->option('hours');
        $cutoff = now()->subHours($hours)->timestamp;

        $referenced = Product::whereNotNull('image_url')
            ->where('image_url', 'like', 'uploads/%')
            ->pluck('image_url')
            ->flip();

        $files = Storage::disk('public')->files('uploads');
        $deleted = 0;

        foreach ($files as $file) {
            if ($referenced->has($file)) {
                continue;
            }

            $lastModified = Storage::disk('public')->lastModified($file);

            if ($lastModified < $cutoff) {
                Storage::disk('public')->delete($file);
                $deleted++;
            }
        }

        $this->info("Pruned {$deleted} orphaned file(s) from uploads/.");
    }
}
