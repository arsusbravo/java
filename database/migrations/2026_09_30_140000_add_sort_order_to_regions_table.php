<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Initial order, west to east; regions not listed follow in creation order. */
    private const ORDER = ['dki-jakarta', 'banten', 'west-java', 'central-java', 'di-yogyakarta', 'east-java'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('slug')->index();
        });

        $position = 0;
        foreach (self::ORDER as $slug) {
            if (DB::table('regions')->where('slug', $slug)->update(['sort_order' => $position + 1])) {
                $position++;
            }
        }

        DB::table('regions')->whereNotIn('slug', self::ORDER)->orderBy('id')->pluck('id')
            ->each(fn ($id) => DB::table('regions')->where('id', $id)->update(['sort_order' => ++$position]));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->dropIndex(['sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};
