<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            $table->string('business_postal_code', 10)->nullable()->comment('사업장 우편번호');
            $table->string('business_address_line1')->nullable()->comment('사업장 주소');
            $table->string('business_address_line2')->nullable()->comment('사업장 상세주소');
        });
    }

    public function down(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            $table->dropColumn([
                'business_postal_code',
                'business_address_line1',
                'business_address_line2',
            ]);
        });
    }
};
