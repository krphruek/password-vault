<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('systems', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('color', 20)->default('#888888');
            $table->timestamps();
        });

        // seed ข้อมูลเริ่มต้น (ของเดิมที่เคยอยู่ใน CHECK constraint)
        DB::table('systems')->insert([
            ['name' => 'SF+',          'color' => '#0ea5e9', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'UFund',        'color' => '#8b5cf6', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ITOS',         'color' => '#f97316', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Office Mate',  'color' => '#10b981', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Workspace',    'color' => '#3b82f6', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Paynext',      'color' => '#ec4899', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'TrueMoney',    'color' => '#ef4444', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ลบ CHECK constraint เดิมของ userpassword.system ออก (validate ผ่าน systems table แทน)
        DB::statement('ALTER TABLE userpassword DROP CONSTRAINT IF EXISTS userpassword_system_check');
    }

    public function down(): void
    {
        Schema::dropIfExists('systems');
    }
};