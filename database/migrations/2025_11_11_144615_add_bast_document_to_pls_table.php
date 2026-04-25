<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pls', function (Blueprint $table) {
            $table->string('bast_document')->nullable()->after('serah_terima_pekerjaan');
        });
    }

    public function down(): void
    {
        Schema::table('pls', function (Blueprint $table) {
            $table->dropColumn('bast_document');
        });
    }
};
