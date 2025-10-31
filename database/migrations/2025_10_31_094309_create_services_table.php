<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->string('name');
            $table->integer('min_order')->default(1);
            $table->string('type')->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->string('estimate')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('services');
    }
};


