<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->integer('external_id')->nullable()->after('id')->comment('cat_id с сайта essensworld.ru');
            $table->index('external_id');
        });

        Schema::table('subcategories', function (Blueprint $table) {
            $table->integer('external_id')->nullable()->after('id')->comment('cat_id с сайта essensworld.ru');
            $table->index('external_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['external_id']);
            $table->dropColumn('external_id');
        });

        Schema::table('subcategories', function (Blueprint $table) {
            $table->dropIndex(['external_id']);
            $table->dropColumn('external_id');
        });
    }
};
