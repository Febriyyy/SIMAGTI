<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        Schema::create('academic_periods', function (Blueprint $table) {
            $table->id();
            // contoh: Ganjil 2026/2027
            $table->string('name')->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_periods');
    }
};
