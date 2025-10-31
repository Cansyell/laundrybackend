<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('service_id')->nullable()->constrained('services')->onDelete('set null');
            $table->decimal('total', 15, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->decimal('paid_amount', 15, 2)->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('transactions');
    }
};

