<?php

/**
 * 홈에 나오는 상품. 데이터베이스 없이 이 파일과 public/images 만으로 화면이 그려진다.
 * 사진을 바꿀 때는 public/images/products 에 파일을 넣고 image 경로를 맞춘다.
 * is_recommended 가 true 이면 추천 상품에 나온다.
 * sections 에 적은 구간에 함께 나온다.
 */
return [
    'banner' => [
        'image' => '/images/banner.jpg?v=2',
        'eyebrow' => '이번 주 기획전',
        'title' => ['장바구니에 담기 전에', '먼저 만나는 특가'],
        'page' => '1 / 5',
    ],

    'sections' => [
        ['key' => 'discover', 'title' => '오늘의 발견', 'tone' => 'plain'],
        ['key' => 'arrival', 'title' => '오늘도착 인기 상품', 'tone' => 'plain'],
        ['key' => 'gold', 'title' => '골드딜', 'tone' => 'gold'],
    ],

    'products' => [
        ['name' => '무선 노이즈캔슬링 헤드폰', 'description' => '출퇴근길에 쓰기 좋은 무선 헤드폰', 'image' => '/images/products/headphones.jpg', 'price' => 32900, 'original_price' => 59000, 'rating' => 4.8, 'review_count' => 1204, 'badge' => '오늘도착', 'is_recommended' => true, 'sections' => ['discover']],
        ['name' => '가벼운 데일리 러닝화', 'description' => '매일 신기 좋은 가벼운 운동화', 'image' => '/images/products/shoes.jpg', 'price' => 45900, 'original_price' => 79000, 'rating' => 4.7, 'review_count' => 864, 'badge' => '오늘도착', 'is_recommended' => true, 'sections' => ['discover']],
        ['name' => '미니멀 가죽 손목시계', 'description' => '심플한 가죽 스트랩 시계', 'image' => '/images/products/watch.jpg', 'price' => 28900, 'original_price' => 42000, 'rating' => 4.6, 'review_count' => 532, 'badge' => '특가', 'is_recommended' => false, 'sections' => ['discover', 'gold']],
        ['name' => '여행용 크로스백', 'description' => '가볍게 메는 데일리 가방', 'image' => '/images/products/bag.jpg', 'price' => 21900, 'original_price' => 35000, 'rating' => 4.5, 'review_count' => 311, 'badge' => '오늘도착', 'is_recommended' => false, 'sections' => ['discover', 'gold']],
        ['name' => '수분 진정 스킨케어 세트', 'description' => '건조한 피부에 쓰는 기본 세트', 'image' => '/images/products/skincare.jpg', 'price' => 18900, 'original_price' => 32000, 'rating' => 4.9, 'review_count' => 2401, 'badge' => '특가', 'is_recommended' => true, 'sections' => ['discover', 'gold']],
        ['name' => '휴대용 블루투스 스피커', 'description' => '방 안과 밖에서 쓰는 작은 스피커', 'image' => '/images/products/speaker.jpg', 'price' => 25900, 'original_price' => 45000, 'rating' => 4.4, 'review_count' => 198, 'badge' => '오늘도착', 'is_recommended' => true, 'sections' => ['discover', 'gold']],
        ['name' => '홈카페 드립 커피 원두', 'description' => '집에서 내리는 중간 로스팅 원두', 'image' => '/images/products/coffee.jpg', 'price' => 12900, 'original_price' => 18000, 'rating' => 4.8, 'review_count' => 3502, 'badge' => '신선', 'is_recommended' => true, 'sections' => ['arrival']],
        ['name' => '데스크 화분 몬스테라', 'description' => '책상 위에 두는 작은 화분', 'image' => '/images/products/plant.jpg', 'price' => 16900, 'original_price' => 24000, 'rating' => 4.7, 'review_count' => 640, 'badge' => '오늘도착', 'is_recommended' => false, 'sections' => ['arrival']],
        ['name' => '캠핑 보온 텀블러', 'description' => '온도가 오래 가는 텀블러', 'image' => '/images/products/tumbler.jpg', 'price' => 14900, 'original_price' => 22000, 'rating' => 4.6, 'review_count' => 887, 'badge' => '골드딜', 'is_recommended' => false, 'sections' => ['arrival']],
        ['name' => '오버핏 코튼 셔츠', 'description' => '여유 있는 핏의 기본 셔츠', 'image' => '/images/products/shirt.jpg', 'price' => 19900, 'original_price' => 39000, 'rating' => 4.5, 'review_count' => 421, 'badge' => '오늘도착', 'is_recommended' => false, 'sections' => ['arrival']],
        ['name' => '무선 게이밍 마우스', 'description' => '클릭이 가벼운 무선 마우스', 'image' => '/images/products/mouse.jpg', 'price' => 23900, 'original_price' => 36000, 'rating' => 4.7, 'review_count' => 1560, 'badge' => '특가', 'is_recommended' => true, 'sections' => ['arrival']],
        ['name' => '아침 식빵 세트', 'description' => '아침에 바로 먹는 식빵', 'image' => '/images/products/bread.jpg', 'price' => 6900, 'original_price' => 8900, 'rating' => 4.9, 'review_count' => 980, 'badge' => '신선', 'is_recommended' => false, 'sections' => ['arrival']],
    ],
];
