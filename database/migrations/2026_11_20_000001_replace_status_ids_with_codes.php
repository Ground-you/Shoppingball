<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('statuses')) {
            $this->swap('admin_accounts', 'status_id', 'status', 'ACTIVE', <<<'SQL'
                UPDATE admin_accounts AS rec
                SET status = CASE status_row.status_code
                    WHEN 'LOCKED' THEN 'LOCKED'
                    ELSE 'ACTIVE'
                END
                FROM statuses AS status_row
                WHERE rec.status_id = status_row.id
            SQL);

            $this->swap('brands', 'status_id', 'status', 'ACTIVE', <<<'SQL'
                UPDATE brands AS rec
                SET status = CASE status_row.status_code
                    WHEN 'INACTIVE' THEN 'INACTIVE'
                    ELSE 'ACTIVE'
                END
                FROM statuses AS status_row
                WHERE rec.status_id = status_row.id
            SQL);

            $this->swap('buyers', 'status_id', 'status', 'ACTIVE', <<<'SQL'
                UPDATE buyers AS rec
                SET status = CASE status_row.status_code
                    WHEN 'DORMANT' THEN 'DORMANT'
                    WHEN 'SUSPENDED' THEN 'SUSPENDED'
                    WHEN 'WITHDRAWN' THEN 'WITHDRAWN'
                    ELSE 'ACTIVE'
                END
                FROM statuses AS status_row
                WHERE rec.status_id = status_row.id
            SQL);

            $this->swap('sellers', 'status_id', 'status', 'PENDING', <<<'SQL'
                UPDATE sellers AS rec
                SET status = CASE status_row.status_code
                    WHEN 'PENDING' THEN 'PENDING'
                    WHEN 'REJECTED' THEN 'REJECTED'
                    WHEN 'SUSPENDED' THEN 'SUSPENDED'
                    WHEN 'WITHDRAWN' THEN 'WITHDRAWN'
                    WHEN 'ACTIVE' THEN 'APPROVED'
                    ELSE 'APPROVED'
                END
                FROM statuses AS status_row
                WHERE rec.status_id = status_row.id
            SQL);

            $this->swap('seller_members', 'status_id', 'status', 'ACTIVE', <<<'SQL'
                UPDATE seller_members AS rec
                SET status = CASE status_row.status_code
                    WHEN 'LOCKED' THEN 'LOCKED'
                    WHEN 'WITHDRAWN' THEN 'LOCKED'
                    ELSE 'ACTIVE'
                END
                FROM statuses AS status_row
                WHERE rec.status_id = status_row.id
            SQL);

            $this->swap('stores', 'operation_status_id', 'operation_status', 'OPEN', <<<'SQL'
                UPDATE stores AS rec
                SET operation_status = CASE status_row.status_code
                    WHEN 'PAUSED' THEN 'CLOSED'
                    WHEN 'CLOSED' THEN 'CLOSED'
                    WHEN 'SUSPENDED' THEN 'SUSPENDED'
                    ELSE 'OPEN'
                END
                FROM statuses AS status_row
                WHERE rec.operation_status_id = status_row.id
            SQL);

            $this->swap('products', 'sale_status_id', 'sale_status', 'ON_SALE', <<<'SQL'
                UPDATE products AS rec
                SET sale_status = CASE status_row.status_code
                    WHEN 'SOLD_OUT' THEN 'SOLD_OUT'
                    WHEN 'STOPPED' THEN 'STOPPED'
                    WHEN 'DRAFT' THEN 'STOPPED'
                    ELSE 'ON_SALE'
                END
                FROM statuses AS status_row
                WHERE rec.sale_status_id = status_row.id
            SQL);

            $this->swap('products', 'approval_status_id', 'approval_status', 'PENDING', <<<'SQL'
                UPDATE products AS rec
                SET approval_status = CASE status_row.status_code
                    WHEN 'PENDING' THEN 'PENDING'
                    WHEN 'REJECTED' THEN 'REJECTED'
                    ELSE 'APPROVED'
                END
                FROM statuses AS status_row
                WHERE rec.approval_status_id = status_row.id
            SQL);

            $this->swap('product_skus', 'status_id', 'status', 'ON_SALE', <<<'SQL'
                UPDATE product_skus AS rec
                SET status = CASE status_row.status_code
                    WHEN 'SOLD_OUT' THEN 'SOLD_OUT'
                    WHEN 'STOPPED' THEN 'STOPPED'
                    WHEN 'ACTIVE' THEN 'ON_SALE'
                    ELSE 'ON_SALE'
                END
                FROM statuses AS status_row
                WHERE rec.status_id = status_row.id
            SQL);

            $this->swap('orders', 'order_status_id', 'order_status', 'PENDING_PAYMENT', <<<'SQL'
                UPDATE orders AS rec
                SET order_status = CASE status_row.status_code
                    WHEN 'PAID' THEN 'PAID'
                    WHEN 'APPROVED' THEN 'PREPARING'
                    WHEN 'PREPARING' THEN 'PREPARING'
                    WHEN 'READY_TO_SHIP' THEN 'PREPARING'
                    WHEN 'SHIPPED' THEN 'SHIPPING'
                    WHEN 'SHIPPING' THEN 'SHIPPING'
                    WHEN 'DELIVERED' THEN 'DELIVERED'
                    WHEN 'CONFIRMED' THEN 'CONFIRMED'
                    WHEN 'CANCELED' THEN 'CANCELED'
                    ELSE 'PENDING_PAYMENT'
                END
                FROM statuses AS status_row
                WHERE rec.order_status_id = status_row.id
            SQL);

            $this->swap('order_items', 'item_status_id', 'item_status', 'PREPARING', <<<'SQL'
                UPDATE order_items AS rec
                SET item_status = CASE status_row.status_code
                    WHEN 'PAID' THEN 'PREPARING'
                    WHEN 'APPROVED' THEN 'PREPARING'
                    WHEN 'PREPARING' THEN 'PREPARING'
                    WHEN 'READY_TO_SHIP' THEN 'PREPARING'
                    WHEN 'SHIPPED' THEN 'SHIPPING'
                    WHEN 'SHIPPING' THEN 'SHIPPING'
                    WHEN 'DELIVERED' THEN 'DELIVERED'
                    WHEN 'CONFIRMED' THEN 'CONFIRMED'
                    WHEN 'CANCELED' THEN 'CANCELED'
                    WHEN 'RETURNED' THEN 'RETURNED'
                    ELSE 'PREPARING'
                END
                FROM statuses AS status_row
                WHERE rec.item_status_id = status_row.id
            SQL);

            $this->swap('payments', 'payment_status_id', 'payment_status', 'PENDING', <<<'SQL'
                UPDATE payments AS rec
                SET payment_status = CASE status_row.status_code
                    WHEN 'PAID' THEN 'PAID'
                    WHEN 'FAILED' THEN 'FAILED'
                    WHEN 'CANCELED' THEN 'CANCELED'
                    ELSE 'PENDING'
                END
                FROM statuses AS status_row
                WHERE rec.payment_status_id = status_row.id
            SQL);

            $this->swap('order_claims', 'claim_status_id', 'claim_status', 'REQUESTED', <<<'SQL'
                UPDATE order_claims AS rec
                SET claim_status = CASE status_row.status_code
                    WHEN 'APPROVED' THEN 'APPROVED'
                    WHEN 'REJECTED' THEN 'REJECTED'
                    WHEN 'PICKING_UP' THEN 'PICKING_UP'
                    WHEN 'COMPLETED' THEN 'COMPLETED'
                    ELSE 'REQUESTED'
                END
                FROM statuses AS status_row
                WHERE rec.claim_status_id = status_row.id
            SQL);

            $this->swap('shipments', 'shipment_status_id', 'shipment_status', 'PREPARING', <<<'SQL'
                UPDATE shipments AS rec
                SET shipment_status = CASE status_row.status_code
                    WHEN 'READY' THEN 'PREPARING'
                    WHEN 'SHIPPED' THEN 'SHIPPING'
                    WHEN 'SHIPPING' THEN 'SHIPPING'
                    WHEN 'DELIVERED' THEN 'DELIVERED'
                    ELSE 'PREPARING'
                END
                FROM statuses AS status_row
                WHERE rec.shipment_status_id = status_row.id
            SQL);

            $this->swap('refunds', 'refund_status_id', 'refund_status', 'REQUESTED', <<<'SQL'
                UPDATE refunds AS rec
                SET refund_status = CASE status_row.status_code
                    WHEN 'PROCESSING' THEN 'PROCESSING'
                    WHEN 'COMPLETED' THEN 'COMPLETED'
                    WHEN 'FAILED' THEN 'FAILED'
                    ELSE 'REQUESTED'
                END
                FROM statuses AS status_row
                WHERE rec.refund_status_id = status_row.id
            SQL);

            $this->swap('settlements', 'settlement_status_id', 'settlement_status', 'PENDING', <<<'SQL'
                UPDATE settlements AS rec
                SET settlement_status = CASE status_row.status_code
                    WHEN 'APPROVED' THEN 'APPROVED'
                    WHEN 'PAID' THEN 'PAID'
                    ELSE 'PENDING'
                END
                FROM statuses AS status_row
                WHERE rec.settlement_status_id = status_row.id
            SQL);

            Schema::dropIfExists('statuses');
        }

        if (Schema::hasTable('faqs') && Schema::hasColumn('faqs', 'question')) {
            DB::statement('ALTER TABLE faqs ALTER COLUMN question TYPE TEXT');
        }
    }

    public function down(): void
    {
        // 문자열 상태를 다시 상태 ID로 되돌리지 않는다.
    }

    private function swap(string $table, string $from, string $to, string $default, string $sql): void
    {
        if (! Schema::hasColumn($table, $from) || Schema::hasColumn($table, $to)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($to) {
            $blueprint->string($to, 20)->nullable();
        });

        DB::statement($sql);
        DB::table($table)->whereNull($to)->update([$to => $default]);

        DB::statement("ALTER TABLE {$table} ALTER COLUMN {$to} SET DEFAULT '{$default}'");
        DB::statement("ALTER TABLE {$table} ALTER COLUMN {$to} SET NOT NULL");

        Schema::table($table, function (Blueprint $blueprint) use ($from) {
            $blueprint->dropConstrainedForeignId($from);
        });
    }
};
