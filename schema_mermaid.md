# 쇼핑몰 ERD (Mermaid)

```mermaid
erDiagram
    admin_accounts {
        bigint id PK
        varchar login_id UK
        varchar password_hash
        varchar admin_name
        varchar email UK
        varchar status
        tinyint two_factor_enabled
        int failed_login_count
        datetime locked_at
        datetime password_changed_at
        datetime last_login_at
        datetime created_at
        datetime updated_at
        datetime deleted_at
    }

    permissions {
        bigint id PK
        varchar permission_code UK
        varchar permission_name
        varchar target_scope
        varchar description
        datetime created_at
        datetime updated_at
    }

    carriers {
        bigint id PK
        varchar carrier_code UK
        varchar carrier_name
        varchar tracking_url_template
        tinyint is_active
        datetime created_at
        datetime updated_at
    }

    terms {
        bigint id PK
        varchar term_type "composite UK: uk_terms_type_version"
        varchar version "composite UK: uk_terms_type_version"
        varchar title
        text content
        tinyint is_required
        datetime effective_at
        tinyint is_public
        datetime created_at
        datetime updated_at
        datetime deleted_at
    }

    categories {
        bigint id PK
        bigint parent_category_id FK "composite UK: uk_categories_parent_name"
        varchar category_name "composite UK: uk_categories_parent_name"
        int display_order
        tinyint is_active
        datetime created_at
        datetime updated_at
        datetime deleted_at
    }

    buyers {
        bigint id PK
        varchar login_id UK
        varchar password_hash
        varchar buyer_name
        varchar nickname
        varchar email UK
        varchar phone
        varchar profile_image_url
        datetime birth_date
        tinyint marketing_consent
        datetime marketing_consented_at
        datetime email_verified_at
        datetime phone_verified_at
        varchar status
        datetime suspended_at
        varchar suspension_reason
        datetime dormant_at
        datetime withdraw_requested_at
        datetime withdrawn_at
        datetime last_login_at
        datetime created_at
        datetime updated_at
        datetime deleted_at
    }

    sellers {
        bigint id PK
        varchar login_email UK
        varchar password_hash
        varchar seller_name
        varchar manager_name
        varchar contact_phone
        datetime email_verified_at
        varchar business_registration_number UK
        varchar business_address
        varchar business_license_file_url
        varchar representative_name
        varchar representative_phone
        varchar settlement_bank_name
        varchar settlement_account_number_enc
        varchar settlement_account_holder
        varchar customer_service_phone
        varchar customer_service_hours
        varchar status
        int failed_login_count
        datetime locked_at
        datetime suspended_at
        varchar suspension_reason
        datetime last_login_at
        datetime withdraw_requested_at
        datetime withdrawn_at
        datetime created_at
        datetime updated_at
        datetime deleted_at
    }

    shop_settings {
        bigint id PK
        varchar shop_name
        varchar logo_url
        varchar business_registration_number
        varchar representative_name
        varchar business_address
        varchar customer_service_phone
        varchar customer_service_email
        varchar customer_service_hours
        decimal default_shipping_fee
        decimal free_shipping_threshold
        decimal return_shipping_fee
        decimal exchange_shipping_fee
        int return_period_days
        int exchange_period_days
        int point_expiry_days
        text cancel_policy_description
        decimal default_commission_rate
        datetime created_at
        datetime updated_at
    }

    verification_requests {
        bigint id PK
        varchar account_type
        varchar purpose
        varchar target_value
        varchar code_hash
        datetime expires_at
        datetime verified_at
        int attempt_count
        datetime created_at
    }

    buyer_social_accounts {
        bigint id PK
        bigint buyer_id FK
        varchar provider "composite UK: uk_buyer_social_provider_user"
        varchar provider_user_id "composite UK: uk_buyer_social_provider_user"
        datetime created_at
    }

    seller_applications {
        bigint id PK
        bigint seller_id FK
        varchar application_status
        datetime applied_at
        bigint reviewed_by_admin_id FK
        datetime reviewed_at
        varchar reject_reason
        datetime canceled_at
        datetime created_at
        datetime updated_at
    }

    seller_staffs {
        bigint id PK
        bigint seller_id FK
        varchar login_email UK
        varchar password_hash
        varchar staff_name
        varchar status
        int failed_login_count
        datetime last_login_at
        datetime created_at
        datetime updated_at
        datetime deleted_at
    }

    seller_term_agreements {
        bigint id PK
        bigint seller_id FK "composite UK: uk_seller_term_agreements_pair"
        bigint term_id FK "composite UK: uk_seller_term_agreements_pair"
        datetime agreed_at
        datetime created_at
    }

    seller_staff_permissions {
        bigint id PK
        bigint seller_staff_id FK "composite UK: uk_seller_staff_permissions_pair"
        bigint permission_id FK "composite UK: uk_seller_staff_permissions_pair"
        datetime created_at
    }

    admin_account_permissions {
        bigint id PK
        bigint admin_account_id FK "composite UK: uk_admin_account_permissions_pair"
        bigint permission_id FK "composite UK: uk_admin_account_permissions_pair"
        datetime created_at
    }

    login_sessions {
        bigint id PK
        bigint buyer_id FK
        bigint seller_id FK
        bigint seller_staff_id FK
        bigint admin_account_id FK
        varchar session_token_hash UK
        varchar device_name
        varchar ip_address
        varchar user_agent
        tinyint is_persistent
        datetime last_active_at
        datetime expires_at
        datetime logged_out_at
        datetime created_at
    }

    login_histories {
        bigint id PK
        bigint buyer_id FK
        bigint seller_id FK
        bigint seller_staff_id FK
        bigint admin_account_id FK
        varchar login_identifier
        varchar login_result
        varchar fail_reason
        varchar ip_address
        varchar device_name
        varchar user_agent
        datetime created_at
    }

    stores {
        bigint id PK
        bigint seller_id FK, UK
        varchar store_name UK
        varchar logo_url
        varchar main_image_url
        varchar banner_image_url
        text introduction
        text shipping_policy_notice
        text exchange_return_policy_notice
        varchar operation_status
        datetime created_at
        datetime updated_at
        datetime deleted_at
    }

    store_notices {
        bigint id PK
        bigint store_id FK
        varchar title
        text content
        tinyint is_public
        datetime created_at
        datetime updated_at
        datetime deleted_at
    }

    products {
        bigint id PK
        bigint store_id FK
        bigint category_id FK
        varchar product_name
        text description
        varchar brand_name
        decimal regular_price
        decimal sale_price
        varchar sale_status
        varchar approval_status
        varchar rejected_reason
        tinyint is_hidden
        int display_order
        datetime sale_started_at
        datetime sale_stopped_at
        datetime created_at
        datetime updated_at
        datetime deleted_at
    }

    product_images {
        bigint id PK
        bigint product_id FK
        varchar image_url
        tinyint is_main
        int sort_order
        datetime created_at
        datetime updated_at
    }

    product_option_groups {
        bigint id PK
        bigint product_id FK "composite UK: uk_product_option_groups_name"
        varchar group_name "composite UK: uk_product_option_groups_name"
        int sort_order
        datetime created_at
        datetime updated_at
    }

    product_options {
        bigint id PK
        bigint product_id FK "composite UK: uk_product_options_name"
        bigint option_group_id FK
        varchar option_name "composite UK: uk_product_options_name"
        tinyint is_default
        decimal additional_price
        int stock_quantity
        int low_stock_threshold
        varchar image_url
        varchar status
        int sort_order
        datetime created_at
        datetime updated_at
        datetime deleted_at
    }

    product_discounts {
        bigint id PK
        bigint product_id FK
        varchar discount_type
        decimal discount_value
        datetime started_at
        datetime ended_at
        varchar status
        datetime canceled_at
        datetime created_at
        datetime updated_at
    }

    product_price_histories {
        bigint id PK
        bigint product_id FK
        decimal previous_regular_price
        decimal new_regular_price
        decimal previous_sale_price
        decimal new_sale_price
        bigint changed_by_seller_id FK
        bigint changed_by_admin_id FK
        datetime created_at
    }

    seller_commission_rates {
        bigint id PK
        bigint seller_id FK
        decimal commission_rate
        datetime effective_from
        datetime effective_to
        datetime created_at
        datetime updated_at
    }

    category_commission_rates {
        bigint id PK
        bigint category_id FK
        decimal commission_rate
        datetime effective_from
        datetime effective_to
        datetime created_at
        datetime updated_at
    }

    buyer_addresses {
        bigint id PK
        bigint buyer_id FK
        varchar address_name
        varchar recipient_name
        varchar recipient_phone
        varchar postal_code
        varchar address_line1
        varchar address_line2
        varchar entrance_access_method
        varchar delivery_request
        tinyint is_default
        datetime created_at
        datetime updated_at
        datetime deleted_at
    }

    recent_search_keywords {
        bigint id PK
        bigint buyer_id FK
        varchar keyword
        datetime searched_at
    }

    recent_viewed_products {
        bigint id PK
        bigint buyer_id FK "composite UK: uk_recent_viewed_products_pair"
        bigint product_id FK "composite UK: uk_recent_viewed_products_pair"
        datetime viewed_at
    }

    wishlists {
        bigint id PK
        bigint buyer_id FK "composite UK: uk_wishlists_pair"
        bigint product_id FK "composite UK: uk_wishlists_pair"
        datetime created_at
    }

    restock_notification_requests {
        bigint id PK
        bigint buyer_id FK
        bigint product_id FK
        bigint product_option_id FK
        varchar request_status
        datetime notified_at
        datetime created_at
        datetime updated_at
    }

    cart_items {
        bigint id PK
        bigint buyer_id FK "composite UK: uk_cart_items_pair"
        bigint product_option_id FK "composite UK: uk_cart_items_pair"
        int quantity
        decimal unit_price_at_added
        datetime created_at
        datetime updated_at
    }

    coupons {
        bigint id PK
        bigint store_id FK
        varchar coupon_name
        varchar coupon_code UK
        varchar discount_type
        decimal discount_value
        decimal max_discount_amount
        decimal min_order_amount
        int total_quantity
        int issued_quantity
        datetime valid_from
        datetime valid_until
        varchar status
        datetime created_at
        datetime updated_at
    }

    buyer_coupons {
        bigint id PK
        bigint buyer_id FK "composite UK: uk_buyer_coupons_pair"
        bigint coupon_id FK "composite UK: uk_buyer_coupons_pair"
        varchar status
        datetime issued_at
        datetime used_at
        datetime created_at
        datetime updated_at
    }

    orders {
        bigint id PK
        varchar order_number UK
        bigint buyer_id FK
        bigint buyer_coupon_id FK
        varchar order_status
        varchar recipient_name
        varchar recipient_phone
        varchar shipping_postal_code
        varchar shipping_address_line1
        varchar shipping_address_line2
        varchar entrance_access_method
        varchar delivery_request
        decimal total_product_amount
        decimal shipping_fee
        decimal coupon_discount_amount
        decimal point_used_amount
        decimal final_payment_amount
        datetime ordered_at
        datetime canceled_at
        datetime created_at
        datetime updated_at
    }

    order_items {
        bigint id PK
        bigint order_id FK
        bigint product_option_id FK
        bigint store_id FK
        varchar product_name
        varchar option_name
        decimal unit_price
        int quantity
        decimal item_amount
        varchar item_status
        datetime purchase_confirmed_at
        datetime created_at
        datetime updated_at
    }

    stock_histories {
        bigint id PK
        bigint product_option_id FK
        bigint order_item_id FK
        varchar change_type
        int quantity_change
        int quantity_after
        varchar reason
        bigint changed_by_seller_id FK
        bigint changed_by_admin_id FK
        datetime created_at
    }

    order_status_histories {
        bigint id PK
        bigint order_id FK
        bigint order_item_id FK
        varchar previous_status
        varchar new_status
        varchar changed_by_role
        bigint admin_account_id FK
        varchar reason
        datetime created_at
    }

    order_memos {
        bigint id PK
        bigint order_id FK
        bigint admin_account_id FK
        text memo
        datetime created_at
        datetime updated_at
        datetime deleted_at
    }

    payments {
        bigint id PK
        bigint order_id FK
        varchar payment_method
        varchar easy_pay_provider
        varchar card_company
        decimal payment_amount
        varchar payment_status
        varchar transaction_id UK
        varchar bank_name
        varchar virtual_account_number
        varchar depositor_name
        datetime deposit_due_at
        datetime paid_at
        datetime failed_at
        varchar failure_code
        varchar failure_message
        varchar receipt_url
        datetime canceled_at
        datetime created_at
        datetime updated_at
    }

    order_claims {
        bigint id PK
        varchar claim_number UK
        bigint order_id FK
        varchar claim_type
        varchar requested_by_role
        varchar claim_status
        varchar reason_code
        varchar reason_detail
        varchar pickup_recipient_name
        varchar pickup_phone
        varchar pickup_postal_code
        varchar pickup_address_line1
        varchar pickup_address_line2
        decimal shipping_fee_amount
        decimal additional_amount
        varchar seller_response
        datetime responded_at
        varchar reject_reason
        datetime due_at
        bigint handled_by_admin_id FK
        varchar result_note
        datetime requested_at
        datetime completed_at
        datetime created_at
        datetime updated_at
    }

    order_claim_items {
        bigint id PK
        bigint order_claim_id FK "composite UK: uk_order_claim_items_pair"
        bigint order_item_id FK "composite UK: uk_order_claim_items_pair"
        int quantity
        bigint exchange_product_option_id FK
        datetime created_at
    }

    shipments {
        bigint id PK
        bigint order_id FK
        bigint store_id FK
        bigint order_claim_id FK
        bigint carrier_id FK "composite UK: uk_shipments_carrier_tracking"
        varchar shipment_type
        varchar tracking_number "composite UK: uk_shipments_carrier_tracking"
        varchar shipment_status
        datetime dispatched_at
        datetime delivered_at
        datetime expected_delivery_at
        varchar delay_reason
        varchar failure_reason
        datetime created_at
        datetime updated_at
    }

    shipment_items {
        bigint id PK
        bigint shipment_id FK "composite UK: uk_shipment_items_pair"
        bigint order_item_id FK "composite UK: uk_shipment_items_pair"
        int quantity
        datetime created_at
    }

    disputes {
        bigint id PK
        bigint order_id FK
        bigint order_claim_id FK
        varchar dispute_status
        varchar dispute_reason
        text buyer_opinion
        text seller_opinion
        bigint assigned_admin_id FK
        varchar decision_type
        decimal decision_amount
        varchar decision_note
        datetime decided_at
        datetime created_at
        datetime updated_at
    }

    refunds {
        bigint id PK
        bigint payment_id FK
        bigint order_claim_id FK
        bigint dispute_id FK
        varchar refund_type
        decimal refund_amount
        varchar refund_status
        varchar reason
        varchar approver_role
        bigint approved_by_admin_id FK
        datetime approved_at
        datetime refunded_at
        varchar failure_message
        varchar pg_refund_transaction_id
        datetime created_at
        datetime updated_at
    }

    evidence_files {
        bigint id PK
        bigint order_claim_id FK
        bigint dispute_id FK
        varchar uploader_role
        varchar file_url
        varchar file_name
        datetime created_at
    }

    reviews {
        bigint id PK
        bigint order_item_id FK, UK
        bigint buyer_id FK
        bigint product_id FK
        tinyint rating
        text content
        varchar review_status
        datetime created_at
        datetime updated_at
        datetime deleted_at
    }

    review_images {
        bigint id PK
        bigint review_id FK
        varchar image_url
        int sort_order
        datetime created_at
    }

    review_helpful_marks {
        bigint id PK
        bigint review_id FK "composite UK: uk_review_helpful_marks_pair"
        bigint buyer_id FK "composite UK: uk_review_helpful_marks_pair"
        datetime created_at
    }

    review_replies {
        bigint id PK
        bigint review_id FK, UK
        bigint seller_id FK
        text content
        datetime created_at
        datetime updated_at
        datetime deleted_at
    }

    reports {
        bigint id PK
        varchar report_type
        bigint reporter_buyer_id FK
        bigint reporter_seller_id FK
        bigint target_product_id FK
        bigint target_review_id FK
        bigint target_seller_id FK
        bigint target_buyer_id FK
        varchar reason_code
        varchar reason_detail
        varchar report_status
        varchar action_taken
        varchar result_note
        bigint processed_by_admin_id FK
        datetime processed_at
        datetime created_at
        datetime updated_at
    }

    product_inquiries {
        bigint id PK
        bigint product_id FK
        bigint buyer_id FK
        text content
        tinyint is_secret
        varchar inquiry_status
        datetime created_at
        datetime updated_at
        datetime deleted_at
    }

    product_inquiry_answers {
        bigint id PK
        bigint product_inquiry_id FK
        bigint seller_id FK
        text content
        datetime created_at
        datetime updated_at
        datetime deleted_at
    }

    customer_inquiries {
        bigint id PK
        bigint buyer_id FK
        bigint order_id FK
        varchar inquiry_type
        varchar title
        text content
        varchar inquiry_status
        bigint assigned_admin_id FK
        datetime closed_at
        datetime created_at
        datetime updated_at
        datetime deleted_at
    }

    customer_inquiry_answers {
        bigint id PK
        bigint customer_inquiry_id FK
        bigint admin_account_id FK
        text content
        datetime created_at
        datetime updated_at
    }

    point_transactions {
        bigint id PK
        bigint buyer_id FK
        bigint order_id FK
        bigint review_id FK
        varchar transaction_type
        int point_value
        int balance_after
        varchar reason_type
        datetime expires_at
        datetime created_at
    }

    settlements {
        bigint id PK
        bigint seller_id FK "composite UK: uk_settlements_seller_period"
        datetime period_start_at "composite UK: uk_settlements_seller_period"
        datetime period_end_at "composite UK: uk_settlements_seller_period"
        decimal total_sales_amount
        decimal shipping_fee_amount
        decimal commission_amount
        decimal refund_amount
        decimal cancel_amount
        decimal settlement_amount
        varchar settlement_status
        varchar hold_reason
        varchar failure_reason
        bigint approved_by_admin_id FK
        datetime approved_at
        datetime completed_at
        varchar statement_file_url
        datetime created_at
        datetime updated_at
    }

    settlement_items {
        bigint id PK
        bigint settlement_id FK "composite UK: uk_settlement_items_pair"
        bigint order_item_id FK "composite UK: uk_settlement_items_pair"
        decimal sales_amount
        decimal shipping_fee_amount
        decimal commission_rate
        decimal commission_amount
        decimal refund_amount
        decimal cancel_amount
        decimal settlement_amount
        datetime created_at
    }

    notifications {
        bigint id PK
        bigint buyer_id FK
        bigint seller_id FK
        bigint product_option_id FK
        bigint sent_by_admin_id FK
        varchar notification_type
        varchar title
        text content
        tinyint is_read
        datetime read_at
        datetime sent_at
        datetime created_at
    }

    banners {
        bigint id PK
        varchar banner_type
        varchar title
        varchar image_url
        varchar link_url
        varchar display_position
        int display_order
        datetime display_started_at
        datetime display_ended_at
        tinyint is_active
        datetime created_at
        datetime updated_at
        datetime deleted_at
    }

    events {
        bigint id PK
        varchar title
        varchar description
        text page_content
        datetime started_at
        datetime ended_at
        varchar event_status
        tinyint is_public
        datetime created_at
        datetime updated_at
        datetime deleted_at
    }

    exhibitions {
        bigint id PK
        varchar title
        text description
        datetime started_at
        datetime ended_at
        tinyint is_public
        datetime created_at
        datetime updated_at
        datetime deleted_at
    }

    notices {
        bigint id PK
        varchar title
        text content
        tinyint is_public
        datetime published_at
        datetime created_at
        datetime updated_at
        datetime deleted_at
    }

    faqs {
        bigint id PK
        varchar question
        text answer
        int display_order
        tinyint is_public
        datetime created_at
        datetime updated_at
        datetime deleted_at
    }

    admin_activity_logs {
        bigint id PK
        bigint admin_account_id FK
        varchar action_type
        varchar target_type
        bigint target_id
        varchar action_reason
        text detail
        varchar ip_address
        datetime created_at
    }

    buyer_memos {
        bigint id PK
        bigint buyer_id FK
        bigint admin_account_id FK
        text memo
        datetime created_at
        datetime updated_at
        datetime deleted_at
    }

    categories |o--o{ categories : "parent_category_id"
    buyers ||--o{ buyer_social_accounts : "buyer_id"
    sellers ||--o{ seller_applications : "seller_id"
    admin_accounts |o--o{ seller_applications : "reviewed_by_admin_id"
    sellers ||--o{ seller_staffs : "seller_id"
    sellers ||--o{ seller_term_agreements : "seller_id"
    terms ||--o{ seller_term_agreements : "term_id"
    seller_staffs ||--o{ seller_staff_permissions : "seller_staff_id"
    permissions ||--o{ seller_staff_permissions : "permission_id"
    admin_accounts ||--o{ admin_account_permissions : "admin_account_id"
    permissions ||--o{ admin_account_permissions : "permission_id"
    buyers |o--o{ login_sessions : "buyer_id"
    sellers |o--o{ login_sessions : "seller_id"
    seller_staffs |o--o{ login_sessions : "seller_staff_id"
    admin_accounts |o--o{ login_sessions : "admin_account_id"
    buyers |o--o{ login_histories : "buyer_id"
    sellers |o--o{ login_histories : "seller_id"
    seller_staffs |o--o{ login_histories : "seller_staff_id"
    admin_accounts |o--o{ login_histories : "admin_account_id"
    sellers ||--o| stores : "seller_id"
    stores ||--o{ store_notices : "store_id"
    stores ||--o{ products : "store_id"
    categories |o--o{ products : "category_id"
    products ||--o{ product_images : "product_id"
    products ||--o{ product_option_groups : "product_id"
    products ||--o{ product_options : "product_id"
    product_option_groups |o--o{ product_options : "option_group_id"
    products ||--o{ product_discounts : "product_id"
    products ||--o{ product_price_histories : "product_id"
    sellers |o--o{ product_price_histories : "changed_by_seller_id"
    admin_accounts |o--o{ product_price_histories : "changed_by_admin_id"
    sellers ||--o{ seller_commission_rates : "seller_id"
    categories ||--o{ category_commission_rates : "category_id"
    buyers ||--o{ buyer_addresses : "buyer_id"
    buyers ||--o{ recent_search_keywords : "buyer_id"
    buyers ||--o{ recent_viewed_products : "buyer_id"
    products ||--o{ recent_viewed_products : "product_id"
    buyers ||--o{ wishlists : "buyer_id"
    products ||--o{ wishlists : "product_id"
    buyers ||--o{ restock_notification_requests : "buyer_id"
    products ||--o{ restock_notification_requests : "product_id"
    product_options |o--o{ restock_notification_requests : "product_option_id"
    buyers ||--o{ cart_items : "buyer_id"
    product_options ||--o{ cart_items : "product_option_id"
    stores |o--o{ coupons : "store_id"
    buyers ||--o{ buyer_coupons : "buyer_id"
    coupons ||--o{ buyer_coupons : "coupon_id"
    buyers ||--o{ orders : "buyer_id"
    buyer_coupons |o--o{ orders : "buyer_coupon_id"
    orders ||--o{ order_items : "order_id"
    product_options ||--o{ order_items : "product_option_id"
    stores ||--o{ order_items : "store_id"
    product_options ||--o{ stock_histories : "product_option_id"
    order_items |o--o{ stock_histories : "order_item_id"
    sellers |o--o{ stock_histories : "changed_by_seller_id"
    admin_accounts |o--o{ stock_histories : "changed_by_admin_id"
    orders ||--o{ order_status_histories : "order_id"
    order_items |o--o{ order_status_histories : "order_item_id"
    admin_accounts |o--o{ order_status_histories : "admin_account_id"
    orders ||--o{ order_memos : "order_id"
    admin_accounts ||--o{ order_memos : "admin_account_id"
    orders ||--o{ payments : "order_id"
    orders ||--o{ order_claims : "order_id"
    admin_accounts |o--o{ order_claims : "handled_by_admin_id"
    order_claims ||--o{ order_claim_items : "order_claim_id"
    order_items ||--o{ order_claim_items : "order_item_id"
    product_options |o--o{ order_claim_items : "exchange_product_option_id"
    orders ||--o{ shipments : "order_id"
    stores ||--o{ shipments : "store_id"
    order_claims |o--o{ shipments : "order_claim_id"
    carriers |o--o{ shipments : "carrier_id"
    shipments ||--o{ shipment_items : "shipment_id"
    order_items ||--o{ shipment_items : "order_item_id"
    orders ||--o{ disputes : "order_id"
    order_claims |o--o{ disputes : "order_claim_id"
    admin_accounts |o--o{ disputes : "assigned_admin_id"
    payments ||--o{ refunds : "payment_id"
    order_claims |o--o{ refunds : "order_claim_id"
    disputes |o--o{ refunds : "dispute_id"
    admin_accounts |o--o{ refunds : "approved_by_admin_id"
    order_claims |o--o{ evidence_files : "order_claim_id"
    disputes |o--o{ evidence_files : "dispute_id"
    order_items ||--o| reviews : "order_item_id"
    buyers ||--o{ reviews : "buyer_id"
    products ||--o{ reviews : "product_id"
    reviews ||--o{ review_images : "review_id"
    reviews ||--o{ review_helpful_marks : "review_id"
    buyers ||--o{ review_helpful_marks : "buyer_id"
    reviews ||--o| review_replies : "review_id"
    sellers ||--o{ review_replies : "seller_id"
    buyers |o--o{ reports : "reporter_buyer_id"
    sellers |o--o{ reports : "reporter_seller_id"
    products |o--o{ reports : "target_product_id"
    reviews |o--o{ reports : "target_review_id"
    sellers |o--o{ reports : "target_seller_id"
    buyers |o--o{ reports : "target_buyer_id"
    admin_accounts |o--o{ reports : "processed_by_admin_id"
    products ||--o{ product_inquiries : "product_id"
    buyers ||--o{ product_inquiries : "buyer_id"
    product_inquiries ||--o{ product_inquiry_answers : "product_inquiry_id"
    sellers ||--o{ product_inquiry_answers : "seller_id"
    buyers ||--o{ customer_inquiries : "buyer_id"
    orders |o--o{ customer_inquiries : "order_id"
    admin_accounts |o--o{ customer_inquiries : "assigned_admin_id"
    customer_inquiries ||--o{ customer_inquiry_answers : "customer_inquiry_id"
    admin_accounts ||--o{ customer_inquiry_answers : "admin_account_id"
    buyers ||--o{ point_transactions : "buyer_id"
    orders |o--o{ point_transactions : "order_id"
    reviews |o--o{ point_transactions : "review_id"
    sellers ||--o{ settlements : "seller_id"
    admin_accounts |o--o{ settlements : "approved_by_admin_id"
    settlements ||--o{ settlement_items : "settlement_id"
    order_items ||--o{ settlement_items : "order_item_id"
    buyers |o--o{ notifications : "buyer_id"
    sellers |o--o{ notifications : "seller_id"
    product_options |o--o{ notifications : "product_option_id"
    admin_accounts |o--o{ notifications : "sent_by_admin_id"
    admin_accounts ||--o{ admin_activity_logs : "admin_account_id"
    buyers ||--o{ buyer_memos : "buyer_id"
    admin_accounts ||--o{ buyer_memos : "admin_account_id"
```
