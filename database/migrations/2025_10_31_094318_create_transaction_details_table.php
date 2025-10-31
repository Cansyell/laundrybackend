<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('transaction_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->foreignId('service_id')->nullable()->constrained('services')->onDelete('set null');
            $table->foreignId('add_on_id')->nullable()->constrained('add_ons')->onDelete('set null');
            $table->integer('weight')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('line_total', 15, 2);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('transaction_details');
    }
};


