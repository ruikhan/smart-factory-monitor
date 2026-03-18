<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->onDelete('cascade');
            $table->foreignId('machine_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('type');
            $table->string('title');
            $table->text('message');
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info');
            $table->boolean('is_read')->default(false);
            $table->boolean('is_resolved')->default(false);
            $table->datetime('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['factory_id', 'is_read', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
