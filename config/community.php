<?php

return [
    'roles' => [
        'guest' => 0,
        'member' => 1,
        'resident' => 2,
        'household_rep' => 3,
        'owner_verified' => 4,
        'tenant_verified' => 4,
        'admin' => 5,
    ],

    'board_permission_roles' => [
        'guest' => '비회원',
        'member' => '비인증회원',
        'verified' => '인증회원',
        'admin' => '관리자',
    ],

    'board_types' => [
        'normal',
        'notice',
        'poll',
        'market',
        'lost',
    ],
];
