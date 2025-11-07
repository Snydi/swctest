<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('header');
            $table->string('description');
            $table->enum('status', ['planned', 'in_progress', 'done'])->default('planned');
            $table->date('completed_at')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('project_id')->constrained('projects');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
