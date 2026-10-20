<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('categories') && !Schema::hasColumn('categories', 'category')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('category', 100)->nullable()->after('name');
            });

            DB::statement('UPDATE `categories` SET `category` = `name` WHERE `category` IS NULL');
        }

        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'img')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('img')->nullable()->after('image_path');
            });

            DB::statement('UPDATE `products` SET `img` = `image_path` WHERE `img` IS NULL AND `image_path` IS NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('categories') && Schema::hasColumn('categories', 'category')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'img')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('img');
            });
        }
    }
};
