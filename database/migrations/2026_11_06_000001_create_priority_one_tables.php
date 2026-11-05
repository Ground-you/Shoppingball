<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('home_section_product');
        Schema::dropIfExists('home_sections');
        Schema::dropIfExists('products');

        Schema::create('statuses', function (Blueprint $table) {
            $table->id()->comment('상태 ID');
            $table->string('status_group', 50)->comment('상태 그룹(ORDER, PAYMENT 등)');
            $table->string('status_code', 50)->comment('상태 코드');
            $table->string('status_name', 100)->comment('상태명');
            $table->integer('display_order')->default(0)->comment('표시 순서');
            $table->timestamps();
            $table->unique(['status_group', 'status_code']);
        });

        Schema::create('admin_accounts', function (Blueprint $table) {
            $table->id()->comment('관리자 ID');
            $table->string('login_id', 50)->unique()->comment('로그인 ID');
            $table->string('password_hash')->comment('비밀번호 해시');
            $table->string('admin_name', 50)->comment('관리자명');
            $table->string('email', 100)->unique()->comment('이메일');
            $table->foreignId('status_id')->constrained('statuses')->comment('상태 ID');
            $table->timestamp('last_login_at')->nullable()->comment('마지막 로그인');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('carriers', function (Blueprint $table) {
            $table->id()->comment('택배사 ID');
            $table->string('carrier_code', 30)->unique()->comment('택배사 코드');
            $table->string('carrier_name', 50)->comment('택배사명');
            $table->string('tracking_url_template', 500)->nullable()->comment('배송조회 URL 템플릿');
            $table->boolean('is_active')->default(true)->comment('사용 여부');
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id()->comment('카테고리 ID');
            $table->foreignId('parent_category_id')->nullable()->constrained('categories')->nullOnDelete()->comment('상위 카테고리 ID');
            $table->string('category_name', 100)->comment('카테고리명');
            $table->integer('display_order')->default(0)->comment('표시 순서');
            $table->boolean('is_active')->default(true)->comment('사용 여부');
            $table->timestamps();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id()->comment('브랜드 ID');
            $table->string('brand_name', 100)->comment('브랜드명');
            $table->string('logo_url', 500)->nullable()->comment('로고 URL');
            $table->foreignId('status_id')->constrained('statuses')->comment('상태 ID');
            $table->timestamps();
        });

        Schema::create('buyers', function (Blueprint $table) {
            $table->id()->comment('구매자 ID');
            $table->string('login_id', 50)->unique()->comment('로그인 ID');
            $table->string('password_hash')->comment('비밀번호 해시');
            $table->string('buyer_name', 50)->comment('이름');
            $table->string('nickname', 50)->nullable()->comment('닉네임');
            $table->string('email', 100)->unique()->comment('이메일');
            $table->string('phone', 30)->nullable()->comment('휴대폰');
            $table->boolean('marketing_consent')->default(false)->comment('마케팅 수신 동의');
            $table->timestamp('email_verified_at')->nullable()->comment('이메일 인증일시');
            $table->foreignId('status_id')->constrained('statuses')->comment('상태 ID');
            $table->timestamp('last_login_at')->nullable()->comment('마지막 로그인');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sellers', function (Blueprint $table) {
            $table->id()->comment('판매자 ID');
            $table->string('seller_name', 100)->comment('판매자(사업체)명');
            $table->string('manager_name', 50)->nullable()->comment('담당자명');
            $table->string('contact_phone', 30)->nullable()->comment('연락처');
            $table->string('business_registration_number', 20)->unique()->comment('사업자등록번호');
            $table->string('business_address')->nullable()->comment('사업장 주소');
            $table->string('business_license_file_url', 500)->nullable()->comment('사업자등록증 파일');
            $table->string('representative_name', 50)->nullable()->comment('대표자명');
            $table->string('settlement_bank_name', 50)->nullable()->comment('정산 은행');
            $table->string('settlement_account_number_enc')->nullable()->comment('정산 계좌번호(암호화)');
            $table->string('settlement_account_holder', 50)->nullable()->comment('예금주');
            $table->foreignId('status_id')->constrained('statuses')->comment('상태 ID');
            $table->foreignId('reviewed_by_admin_id')->nullable()->constrained('admin_accounts')->nullOnDelete()->comment('가입 승인 관리자 ID');
            $table->timestamp('reviewed_at')->nullable()->comment('심사일시');
            $table->string('reject_reason')->nullable()->comment('반려 사유');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('seller_members', function (Blueprint $table) {
            $table->id()->comment('판매자 멤버 ID');
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete()->comment('판매자 ID');
            $table->string('login_email', 100)->unique()->comment('로그인 이메일');
            $table->string('password_hash')->comment('비밀번호 해시');
            $table->string('member_name', 50)->comment('멤버 이름');
            $table->string('member_role', 20)->default('OWNER')->comment('OWNER / STAFF');
            $table->foreignId('status_id')->constrained('statuses')->comment('상태 ID');
            $table->timestamp('last_login_at')->nullable()->comment('마지막 로그인');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stores', function (Blueprint $table) {
            $table->id()->comment('스토어 ID');
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete()->comment('판매자 ID');
            $table->string('store_name', 100)->comment('스토어명');
            $table->string('logo_url', 500)->nullable()->comment('로고 URL');
            $table->string('banner_image_url', 500)->nullable()->comment('배너 이미지 URL');
            $table->text('introduction')->nullable()->comment('스토어 소개');
            $table->foreignId('operation_status_id')->constrained('statuses')->comment('운영 상태 ID');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('policies', function (Blueprint $table) {
            $table->id()->comment('정책 ID');
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete()->comment('스토어 ID (NULL=플랫폼 기본 정책)');
            $table->integer('version_no')->comment('정책 버전 번호');
            $table->decimal('default_shipping_fee', 12, 2)->default(0)->comment('기본 배송비');
            $table->decimal('free_shipping_threshold', 12, 2)->nullable()->comment('무료배송 기준금액');
            $table->decimal('return_shipping_fee', 12, 2)->default(0)->comment('반품 배송비');
            $table->decimal('exchange_shipping_fee', 12, 2)->default(0)->comment('교환 배송비');
            $table->integer('return_period_days')->default(7)->comment('반품 가능 기간(일)');
            $table->integer('exchange_period_days')->default(7)->comment('교환 가능 기간(일)');
            $table->text('cancel_policy_description')->nullable()->comment('취소 정책 설명');
            $table->text('shipping_policy_notice')->nullable()->comment('배송 정책 안내');
            $table->text('exchange_return_policy_notice')->nullable()->comment('교환/반품 정책 안내');
            $table->timestamp('effective_from')->comment('정책 적용 시작일시');
            $table->foreignId('registered_by_seller_member_id')->nullable()->constrained('seller_members')->nullOnDelete()->comment('등록한 판매자 멤버 ID');
            $table->foreignId('registered_by_admin_id')->nullable()->constrained('admin_accounts')->nullOnDelete()->comment('등록한 관리자 ID');
            $table->timestamp('created_at')->useCurrent()->comment('정책 등록일시');
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id()->comment('상품 ID');
            $table->foreignId('store_id')->constrained()->comment('스토어 ID');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete()->comment('카테고리 ID');
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete()->comment('브랜드 ID');
            $table->string('product_name', 200)->comment('상품명');
            $table->text('description')->nullable()->comment('상품 설명');
            $table->decimal('regular_price', 12, 2)->comment('정상가');
            $table->decimal('sale_price', 12, 2)->comment('판매가(할인가)');
            $table->foreignId('sale_status_id')->constrained('statuses')->comment('판매 상태 ID');
            $table->foreignId('approval_status_id')->constrained('statuses')->comment('승인 상태 ID');
            $table->string('rejected_reason')->nullable()->comment('반려 사유');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_skus', function (Blueprint $table) {
            $table->id()->comment('SKU ID');
            $table->foreignId('product_id')->constrained()->cascadeOnDelete()->comment('상품 ID');
            $table->string('sku_code', 50)->unique()->comment('SKU 코드');
            $table->string('option_name', 200)->default('기본')->comment('옵션명');
            $table->decimal('additional_price', 12, 2)->default(0)->comment('추가 금액');
            $table->integer('stock_quantity')->default(0)->comment('재고 수량');
            $table->foreignId('status_id')->constrained('statuses')->comment('상태 ID');
            $table->integer('sort_order')->default(0)->comment('정렬 순서');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id()->comment('이미지 ID');
            $table->foreignId('product_id')->constrained()->cascadeOnDelete()->comment('상품 ID');
            $table->string('image_url', 500)->comment('이미지 URL');
            $table->boolean('is_main')->default(false)->comment('대표 이미지 여부');
            $table->integer('sort_order')->default(0)->comment('정렬 순서');
            $table->timestamp('created_at')->useCurrent()->comment('생성일시');
        });

        Schema::create('buyer_addresses', function (Blueprint $table) {
            $table->id()->comment('배송지 ID');
            $table->foreignId('buyer_id')->constrained()->cascadeOnDelete()->comment('구매자 ID');
            $table->string('address_name', 50)->nullable()->comment('배송지명');
            $table->string('recipient_name', 50)->comment('수령인');
            $table->string('recipient_phone', 30)->comment('수령인 연락처');
            $table->string('postal_code', 10)->comment('우편번호');
            $table->string('address_line1')->comment('주소');
            $table->string('address_line2')->nullable()->comment('상세주소');
            $table->boolean('is_default')->default(false)->comment('기본 배송지 여부');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id()->comment('장바구니 ID');
            $table->foreignId('buyer_id')->constrained()->cascadeOnDelete()->comment('구매자 ID');
            $table->foreignId('product_sku_id')->constrained()->comment('SKU ID');
            $table->integer('quantity')->default(1)->comment('수량');
            $table->timestamps();
            $table->unique(['buyer_id', 'product_sku_id']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id()->comment('주문 ID');
            $table->string('order_number', 30)->unique()->comment('주문번호');
            $table->foreignId('buyer_id')->constrained()->comment('구매자 ID');
            $table->foreignId('order_status_id')->constrained('statuses')->comment('주문 상태 ID');
            $table->string('recipient_name', 50)->comment('수령인');
            $table->string('recipient_phone', 30)->comment('수령인 연락처');
            $table->string('shipping_postal_code', 10)->comment('배송 우편번호');
            $table->string('shipping_address_line1')->comment('배송 주소');
            $table->string('shipping_address_line2')->nullable()->comment('배송 상세주소');
            $table->string('delivery_request')->nullable()->comment('배송 요청사항');
            $table->decimal('total_product_amount', 12, 2)->comment('상품 합계금액');
            $table->decimal('shipping_fee', 12, 2)->default(0)->comment('배송비');
            $table->decimal('final_payment_amount', 12, 2)->comment('최종 결제금액');
            $table->timestamp('ordered_at')->comment('주문(구매) 일시');
            $table->timestamp('canceled_at')->nullable()->comment('취소일시');
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id()->comment('주문상품 ID');
            $table->foreignId('order_id')->constrained()->cascadeOnDelete()->comment('주문 ID');
            $table->foreignId('product_sku_id')->constrained()->comment('SKU ID');
            $table->foreignId('store_id')->constrained()->comment('스토어 ID');
            $table->foreignId('applied_policy_id')->constrained('policies')->comment('구매 시점 적용 정책 ID');
            $table->string('product_name', 200)->comment('주문 당시 상품명');
            $table->string('option_name', 200)->comment('주문 당시 옵션명');
            $table->decimal('unit_price', 12, 2)->comment('단가');
            $table->integer('quantity')->comment('수량');
            $table->decimal('item_amount', 12, 2)->comment('금액');
            $table->foreignId('item_status_id')->constrained('statuses')->comment('주문상품 상태 ID');
            $table->timestamp('purchase_confirmed_at')->nullable()->comment('구매확정일시');
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id()->comment('결제 ID');
            $table->foreignId('order_id')->constrained()->comment('주문 ID');
            $table->string('payment_method', 30)->comment('결제 수단');
            $table->decimal('payment_amount', 12, 2)->comment('결제 금액');
            $table->foreignId('payment_status_id')->constrained('statuses')->comment('결제 상태 ID');
            $table->string('transaction_id', 100)->nullable()->comment('PG 거래 ID');
            $table->timestamp('paid_at')->nullable()->comment('결제일시');
            $table->timestamp('failed_at')->nullable()->comment('실패일시');
            $table->string('failure_message')->nullable()->comment('실패 메시지');
            $table->timestamp('canceled_at')->nullable()->comment('취소일시');
            $table->timestamps();
        });

        Schema::create('order_claims', function (Blueprint $table) {
            $table->id()->comment('클레임 ID');
            $table->string('claim_number', 30)->unique()->comment('클레임 번호');
            $table->foreignId('order_id')->constrained()->comment('주문 ID');
            $table->string('claim_type', 20)->comment('CANCEL / RETURN / EXCHANGE');
            $table->foreignId('claim_status_id')->constrained('statuses')->comment('클레임 상태 ID');
            $table->string('reason_code', 30)->comment('사유 코드');
            $table->string('reason_detail', 500)->nullable()->comment('사유 상세');
            $table->string('pickup_postal_code', 10)->nullable()->comment('수거지 우편번호');
            $table->string('pickup_address_line1')->nullable()->comment('수거지 주소');
            $table->string('pickup_address_line2')->nullable()->comment('수거지 상세주소');
            $table->decimal('shipping_fee_amount', 12, 2)->default(0)->comment('클레임 배송비');
            $table->string('seller_response', 500)->nullable()->comment('판매자 응답');
            $table->timestamp('responded_at')->nullable()->comment('응답일시');
            $table->string('reject_reason', 500)->nullable()->comment('거절 사유');
            $table->foreignId('handled_by_admin_id')->nullable()->constrained('admin_accounts')->nullOnDelete()->comment('개입 관리자 ID');
            $table->timestamp('requested_at')->comment('요청일시');
            $table->timestamp('completed_at')->nullable()->comment('완료일시');
            $table->timestamps();
        });

        Schema::create('order_claim_items', function (Blueprint $table) {
            $table->id()->comment('클레임상품 ID');
            $table->foreignId('order_claim_id')->constrained()->cascadeOnDelete()->comment('클레임 ID');
            $table->foreignId('order_item_id')->constrained()->comment('주문상품 ID');
            $table->integer('quantity')->comment('수량');
            $table->foreignId('exchange_product_sku_id')->nullable()->constrained('product_skus')->nullOnDelete()->comment('교환 SKU ID');
            $table->timestamp('created_at')->useCurrent()->comment('생성일시');
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id()->comment('배송 ID');
            $table->foreignId('order_id')->constrained()->comment('주문 ID');
            $table->foreignId('store_id')->constrained()->comment('스토어 ID');
            $table->foreignId('order_claim_id')->nullable()->constrained()->nullOnDelete()->comment('클레임 ID');
            $table->foreignId('carrier_id')->nullable()->constrained()->nullOnDelete()->comment('택배사 ID');
            $table->string('shipment_type', 20)->comment('OUTBOUND / RETURN / EXCHANGE');
            $table->string('tracking_number', 50)->nullable()->comment('운송장 번호');
            $table->foreignId('shipment_status_id')->constrained('statuses')->comment('배송 상태 ID');
            $table->timestamp('dispatched_at')->nullable()->comment('발송일시');
            $table->timestamp('delivered_at')->nullable()->comment('배송완료일시');
            $table->timestamps();
        });

        Schema::create('shipment_items', function (Blueprint $table) {
            $table->id()->comment('배송상품 ID');
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete()->comment('배송 ID');
            $table->foreignId('order_item_id')->constrained()->comment('주문상품 ID');
            $table->integer('quantity')->comment('수량');
            $table->timestamp('created_at')->useCurrent()->comment('생성일시');
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id()->comment('리뷰 ID');
            $table->foreignId('order_item_id')->unique()->constrained()->comment('주문상품 ID');
            $table->foreignId('buyer_id')->constrained()->comment('구매자 ID');
            $table->foreignId('product_id')->constrained()->comment('상품 ID');
            $table->unsignedTinyInteger('rating')->comment('평점(1~5)');
            $table->text('content')->nullable()->comment('리뷰 내용');
            $table->text('seller_reply')->nullable()->comment('판매자 답글');
            $table->timestamp('replied_at')->nullable()->comment('답글일시');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id()->comment('환불 ID');
            $table->foreignId('payment_id')->constrained()->comment('결제 ID');
            $table->foreignId('order_claim_id')->nullable()->constrained()->nullOnDelete()->comment('클레임 ID');
            $table->decimal('refund_amount', 12, 2)->comment('환불 금액');
            $table->foreignId('refund_status_id')->constrained('statuses')->comment('환불 상태 ID');
            $table->string('reason')->nullable()->comment('사유');
            $table->timestamp('refunded_at')->nullable()->comment('환불일시');
            $table->string('failure_message')->nullable()->comment('실패 메시지');
            $table->string('pg_refund_transaction_id', 100)->nullable()->comment('PG 환불 거래 ID');
            $table->timestamps();
        });

        Schema::create('settlements', function (Blueprint $table) {
            $table->id()->comment('정산 ID');
            $table->foreignId('seller_id')->constrained()->comment('판매자 ID');
            $table->timestamp('period_start_at')->comment('정산 기간 시작');
            $table->timestamp('period_end_at')->comment('정산 기간 종료');
            $table->decimal('total_sales_amount', 14, 2)->default(0)->comment('총 판매금액');
            $table->decimal('shipping_fee_amount', 14, 2)->default(0)->comment('배송비 합계');
            $table->decimal('commission_amount', 14, 2)->default(0)->comment('수수료 합계');
            $table->decimal('refund_amount', 14, 2)->default(0)->comment('환불 합계');
            $table->decimal('settlement_amount', 14, 2)->default(0)->comment('정산 금액');
            $table->foreignId('settlement_status_id')->constrained('statuses')->comment('정산 상태 ID');
            $table->foreignId('approved_by_admin_id')->nullable()->constrained('admin_accounts')->nullOnDelete()->comment('승인 관리자 ID');
            $table->timestamp('approved_at')->nullable()->comment('승인일시');
            $table->timestamp('completed_at')->nullable()->comment('지급 완료일시');
            $table->timestamps();
        });

        Schema::create('settlement_items', function (Blueprint $table) {
            $table->id()->comment('정산상세 ID');
            $table->foreignId('settlement_id')->constrained()->cascadeOnDelete()->comment('정산 ID');
            $table->foreignId('order_item_id')->constrained()->comment('주문상품 ID');
            $table->decimal('sales_amount', 14, 2)->default(0)->comment('판매금액');
            $table->decimal('commission_rate', 5, 2)->default(0)->comment('수수료율(%)');
            $table->decimal('commission_amount', 14, 2)->default(0)->comment('수수료');
            $table->decimal('refund_amount', 14, 2)->default(0)->comment('환불금액');
            $table->decimal('settlement_amount', 14, 2)->default(0)->comment('정산금액');
            $table->timestamp('created_at')->useCurrent()->comment('생성일시');
        });

        Schema::create('terms', function (Blueprint $table) {
            $table->id()->comment('약관 ID');
            $table->string('term_type', 50)->comment('약관 유형');
            $table->string('version', 20)->comment('버전');
            $table->string('title', 200)->comment('제목');
            $table->text('content')->comment('내용');
            $table->boolean('is_required')->default(true)->comment('필수 여부');
            $table->timestamp('announced_at')->nullable()->comment('공지 시작일시');
            $table->timestamp('effective_at')->comment('효력 발생일시');
            $table->timestamps();
            $table->unique(['term_type', 'version']);
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id()->comment('FAQ ID');
            $table->string('question', 300)->comment('질문');
            $table->text('answer')->comment('답변');
            $table->integer('display_order')->default(0)->comment('표시 순서');
            $table->boolean('is_public')->default(true)->comment('공개 여부');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('notices', function (Blueprint $table) {
            $table->id()->comment('공지 ID');
            $table->string('title', 200)->comment('제목');
            $table->text('content')->comment('내용');
            $table->boolean('is_public')->default(true)->comment('공개 여부');
            $table->timestamp('published_at')->nullable()->comment('게시일시');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('shop_settings', function (Blueprint $table) {
            $table->id()->comment('설정 ID');
            $table->string('shop_name', 100)->comment('쇼핑몰명');
            $table->string('logo_url', 500)->nullable()->comment('로고 URL');
            $table->string('business_registration_number', 20)->nullable()->comment('사업자등록번호');
            $table->string('representative_name', 50)->nullable()->comment('대표자명');
            $table->string('business_address')->nullable()->comment('사업장 주소');
            $table->string('customer_service_phone', 30)->nullable()->comment('고객센터 전화');
            $table->string('customer_service_email', 100)->nullable()->comment('고객센터 이메일');
            $table->string('customer_service_hours', 100)->nullable()->comment('고객센터 운영시간');
            $table->decimal('default_commission_rate', 5, 2)->default(0)->comment('기본 수수료율(%)');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_settings');
        Schema::dropIfExists('notices');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('terms');
        Schema::dropIfExists('settlement_items');
        Schema::dropIfExists('settlements');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('shipment_items');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('order_claim_items');
        Schema::dropIfExists('order_claims');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('buyer_addresses');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_skus');
        Schema::dropIfExists('products');
        Schema::dropIfExists('policies');
        Schema::dropIfExists('stores');
        Schema::dropIfExists('seller_members');
        Schema::dropIfExists('sellers');
        Schema::dropIfExists('buyers');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('carriers');
        Schema::dropIfExists('admin_accounts');
        Schema::dropIfExists('statuses');
    }
};
