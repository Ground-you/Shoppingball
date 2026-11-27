<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_skus', function (Blueprint $table) {
            $table->decimal('regular_price', 12, 2)->nullable()->comment('옵션 정상가');
            $table->decimal('sale_price', 12, 2)->nullable()->comment('옵션 판매가');
        });

        DB::statement(<<<'SQL'
            UPDATE product_skus AS sku
            SET regular_price = product.regular_price + sku.additional_price,
                sale_price = product.sale_price + sku.additional_price
            FROM products AS product
            WHERE product.id = sku.product_id
              AND sku.deleted_at IS NULL
        SQL);

        DB::statement(<<<'SQL'
            UPDATE products AS product
            SET regular_price = priced.regular_price,
                sale_price = priced.sale_price
            FROM (
                SELECT DISTINCT ON (product_id) product_id, regular_price, sale_price
                FROM product_skus
                WHERE deleted_at IS NULL AND sale_price IS NOT NULL
                ORDER BY product_id, sale_price
            ) AS priced
            WHERE product.id = priced.product_id
        SQL);
    }

    public function down(): void
    {
        Schema::table('product_skus', function (Blueprint $table) {
            $table->dropColumn(['regular_price', 'sale_price']);
        });
    }
};
