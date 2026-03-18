<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('units_produced');
            $table->integer('units_rejected')->default(0);
            $table->integer('target_units');
            $table->string('product_name')->nullable();
            $table->string('shift')->default('day');
            $table->date('production_date');
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['machine_id', 'production_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_records');
    }
};
