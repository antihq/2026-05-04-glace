<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (Schema::hasColumn('accounts', 'sort_order')) {
                if (Schema::hasIndex('accounts', 'accounts_team_id_sort_order_index')) {
                    $table->dropIndex(['team_id', 'sort_order']);
                }
                $table->dropColumn('sort_order');
            }

            if (Schema::hasColumn('accounts', 'deleted_at')) {
                $table->dropColumn('deleted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->integer('sort_order')->default(0);
            $table->softDeletes();
            $table->index(['team_id', 'sort_order']);
        });
    }
};
