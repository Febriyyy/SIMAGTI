<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        // Bukti pengerjaan ada di task_evidences, komentar mentor di task_comments.
        Schema::create('weekly_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('week_number');
            // senin | selasa | rabu | kamis | jumat
            $table->string('day', 10);
            $table->string('title');
            $table->text('description')->nullable();
            // todo | in_progress | completed
            $table->string('status', 20)->default('todo');
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['internship_id', 'week_number']);
            $table->index(['internship_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_tasks');
    }
};
