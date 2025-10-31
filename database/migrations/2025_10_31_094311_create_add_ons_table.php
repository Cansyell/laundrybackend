<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('add_ons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('add_ons');
    }
};


