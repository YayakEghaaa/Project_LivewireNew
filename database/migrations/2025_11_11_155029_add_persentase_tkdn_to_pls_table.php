<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pls', function (Blueprint $table) {
            $table->decimal('persentase_tkdn', 5, 2)->nullable()->after('pdn_tkdn_impor');
        });
    }

    public function down(): void
    {
        Schema::table('pls', function (Blueprint $table) {
            $table->dropColumn('persentase_tkdn');
        });
    }
};
