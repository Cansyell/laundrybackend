<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('payment_status', ['unpaid', 'paid', 'partial'])
                  ->default('unpaid')
                  ->after('total');

            // Status cucian
            $table->enum('laundry_status', ['pending', 'in_queue', 'in_process', 'ready', 'delivered'])
                  ->default('pending')
                  ->after('payment_status');

            // Tanggal transaksi
            $table->date('transaction_date')->nullable()->after('laundry_status');

            // Estimasi selesai
            $table->date('estimated_completion')->nullable()->after('transaction_date');

            // Customer ID (karena customer bukan user)
            $table->foreignId('customer_id')->nullable()
                  ->constrained('customers')
                  ->onDelete('set null')
                  ->after('user_id');

            // Catatan tambahan
            $table->text('notes')->nullable()->after('estimated_completion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'payment_status',
                'laundry_status', 
                'transaction_date',
                'estimated_completion',
                'customer_id',
                'notes'
            ]);
        });
    }
};
