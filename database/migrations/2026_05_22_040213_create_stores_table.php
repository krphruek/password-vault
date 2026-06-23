<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->string('ID', 20)->primary();
            $table->string('Name');
            $table->string('BU')->nullable();
            $table->string('RM')->nullable();
            $table->string('AM')->nullable();           // ชื่อจริง ไม่มี FK
            $table->string('ช่องทางการขาย')->nullable();
            $table->string('BM')->nullable();
            $table->string('Mobile')->nullable();
            $table->text('address')->nullable();
            $table->string('Mail')->nullable();
            $table->uuid('am_user_id')->nullable();     // FK → users.id
            $table->timestamps();
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->foreign('am_user_id')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};