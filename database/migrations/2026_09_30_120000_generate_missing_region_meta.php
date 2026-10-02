<?php

use App\Models\Region;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Fill empty meta fields on existing regions; Region generates them on save.
     */
    public function up(): void
    {
        Region::query()
            ->where(fn ($query) => $query
                ->whereNull('meta_title')->orWhere('meta_title', '')
                ->orWhereNull('meta_description')->orWhere('meta_description', ''))
            ->each(fn (Region $region) => $region->save());
    }

    /**
     * Generated values are indistinguishable from typed ones, so nothing is undone.
     */
    public function down(): void {}
};
