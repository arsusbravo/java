<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The old ReviewSeeder filled the site with made-up reviews (all with
     *
     * @example.com addresses). They were imported on the live site too.
     */
    public function up(): void
    {
        DB::table('reviews')->where('user_email', 'like', '%@example.com')->delete();
    }

    public function down(): void
    {
        //
    }
};
