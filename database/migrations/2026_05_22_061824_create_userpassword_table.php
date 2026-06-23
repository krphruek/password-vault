<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('userpassword', function (Blueprint $table) {
            $table->id();

            $table->string('store_id', 20);
            $table->foreign('store_id')
                  ->references('ID')
                  ->on('stores')
                  ->cascadeOnDelete();

            $table->enum('system', [
                'SF+', 'UFun', 'ITOS',
                'Office Mate', 'Workspace',
                'Paynext', 'TrueMoney',
            ]);

            $table->string('username');
            $table->string('password');
            $table->text('note')->nullable();

            $table->timestamps();

            $table->unique(['store_id', 'system']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('userpassword');
    }
};