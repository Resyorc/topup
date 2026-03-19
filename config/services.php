<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'digiflazz' => [
        'username' => env('DIGIFLAZZ_USERNAME'),
        'api_key' => env('DIGIFLAZZ_API_KEY'),
        'webhook_secret' => env('DIGIFLAZZ_WEBHOOK_SECRET'),
        'base_url' => env('DIGIFLAZZ_BASE_URL', 'https://api.digiflazz.com/v1'),
    ],

    'tripay' => [
        'api_key' => env('TRIPAY_API_KEY'),
        'private_key' => env('TRIPAY_PRIVATE_KEY'),
        'merchant_code' => env('TRIPAY_MERCHANT_CODE'),
        'mode' => env('TRIPAY_MODE', 'sandbox'),
    ],

    'fonnte' => [
        'token' => env('FONNTE_TOKEN'),
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
    ],

    'loyalty' => [
        // Persentase reward dari harga produk (1 = 1%, artinya Rp10.000 → 100 Coin)
        'rate_percent' => env('LOYALTY_RATE_PERCENT', 1),
        // Minimum amount transaksi agar dapat reward
        'min_amount' => env('LOYALTY_MIN_AMOUNT', 5000),
    ],

    'user_id_check' => [
        'endpoint' => 'https://order-sg.codashop.com/initPayment.action',
        'timeout' => 5,
        'cache_seconds' => 60,
        // Slug harus sesuai dengan slug di tabel games.
        // nickname_field: 'username' (default) | 'roles' | 'result'
        // api: 'codashop' (default) | 'dancingidol'
        'games' => [

            // ── Standard (no zone) ──────────────────────────────────────────
            'eight-ball-pool' => ['voucher_id' => 205678,  'price' => 140000.0,   'voucher_type' => 'EIGHT_BALL_POOL',       'need_zone' => false],
            'auto-chess' => ['voucher_id' => 203896,  'price' => 250000.0,   'voucher_type' => 'AUTO_CHESS',            'need_zone' => false],
            'captain-tsubasa' => ['voucher_id' => 352113,  'price' => 1099000.0,  'voucher_type' => 'CAPTAIN_TSUBASA',       'need_zone' => false],
            'dragon-city' => ['voucher_id' => 254278,  'price' => 479000.0,   'voucher_type' => 'DRAGON_CITY',           'need_zone' => false],
            'dragon-raja' => ['voucher_id' => 75648,   'price' => 1000000.0,  'voucher_type' => 'ZULONG_DRAGON_RAJA',    'need_zone' => false],
            'football-master' => ['voucher_id' => 185403,  'price' => 1000000.0,  'voucher_type' => 'FOOTBALL_MASTER',       'need_zone' => false],
            'growtopia' => ['voucher_id' => 398701,  'price' => 800000.0,   'voucher_type' => 'GROWTOPIA',             'need_zone' => false],
            'hago' => ['voucher_id' => 16153,   'price' => 544500.0,   'voucher_type' => 'HAGO',                  'need_zone' => false],
            'laplace-m' => ['voucher_id' => 25528,   'price' => 739000.0,   'voucher_type' => 'ZLONGAME',              'need_zone' => false],
            'marvel-duel' => ['voucher_id' => 155959,  'price' => 739000.0,   'voucher_type' => 'MARVEL_DUEL',           'need_zone' => false],
            'onmyoji-arena' => ['voucher_id' => 46466,   'price' => 706000.0,   'voucher_type' => 'ONMYOJI_ARENA',         'need_zone' => false],
            'point-blank' => ['voucher_id' => 54790,   'price' => 550000.0,   'voucher_type' => 'POINT_BLANK',           'need_zone' => false],
            'super-mecha-champions' => ['voucher_id' => 37815,   'price' => 706000.0,   'voucher_type' => 'SUPER_MECHA_CHAMPIONS', 'need_zone' => false],
            'super-sus' => ['voucher_id' => 266162,  'price' => 681000.0,   'voucher_type' => 'SUPER_SUS',             'need_zone' => false],
            'valorant' => ['voucher_id' => 973634, 'price' => 56000.0, 'voucher_type' => 'VALORANT', 'voucher_type_id' => 109, 'gvt_id' => 139, 'need_zone' => false],
            'war-planet-online' => ['voucher_id' => 424705,  'price' => 535000.0,   'voucher_type' => 'WAR_PLANET_ONLINE',     'need_zone' => false],
            'wild-rift' => ['voucher_id' => 372111,  'price' => 360000.0,   'voucher_type' => 'WILD_RIFT',             'need_zone' => false],
            'zepeto' => ['voucher_id' => 937273,  'price' => 1082050.0,  'voucher_type' => 'NAVER_Z_CORPORATION',   'need_zone' => false],

            'honkai-impact' => ['voucher_id' => 48250,   'price' => 16500.0,    'voucher_type' => 'HONKAI_IMPACT',         'need_zone' => false],

            // ── nickname_field: roles ────────────────────────────────────────
            'arena-of-valor' => ['voucher_id' => 7946,    'price' => 10000.0,    'voucher_type' => 'AOV',          'need_zone' => false, 'nickname_field' => 'roles'],
            'call-of-duty-mobile' => ['voucher_id' => 46129,   'price' => 10000.0,    'voucher_type' => 'CALL_OF_DUTY', 'need_zone' => false, 'nickname_field' => 'roles'],
            'free-fire' => ['api' => 'gopay', 'gopay_game' => 'FREEFIRE', 'need_zone' => false],
            'speed-drifters' => ['voucher_id' => 12861,   'price' => 1000000.0,  'voucher_type' => 'SPEEDDRIFTERS', 'need_zone' => false, 'nickname_field' => 'roles'],

            // ── Requires zone ────────────────────────────────────────────────
            'asphalt-9' => ['voucher_id' => 114548,  'price' => 479700.0,   'voucher_type' => 'GAMELOFT_A9',        'need_zone' => true],
            'azur-lane' => ['voucher_id' => 99716,   'price' => 590000.0,   'voucher_type' => 'AZUR_LANE',          'need_zone' => true],
            'badlanders' => ['voucher_id' => 124099,  'price' => 705000.0,   'voucher_type' => 'BAD_LANDERS',        'need_zone' => true],
            'basketrio' => ['voucher_id' => 147203,  'price' => 832500.0,   'voucher_type' => 'BASKETRIO',          'need_zone' => true],
            'crisis-action' => ['voucher_id' => 3745,    'price' => 300000.0,   'voucher_type' => 'HEROGAMES',          'need_zone' => true],
            'eos-red' => ['voucher_id' => 182235,  'price' => 852139.0,   'voucher_type' => 'EOS_RED',            'need_zone' => true],
            'genshin-impact' => ['voucher_id' => 116054,  'price' => 16500.0,    'voucher_type' => 'GENSHIN_IMPACT',     'need_zone' => true],
            'hsr' => [
                'voucher_id' => 855316, 'price' => 16000.0, 'voucher_type' => 'HONKAI_STAR_RAIL', 'need_zone' => true,
                'server_zones' => ['6' => 'prod_official_usa', '7' => 'prod_official_eur', '8' => 'prod_official_asia', '9' => 'prod_official_cht'],
            ],
            'identity-v' => ['voucher_id' => 59703,   'price' => 725000.0,   'voucher_type' => 'IDENTITY_V',         'need_zone' => true],
            'lifeafter' => ['voucher_id' => 45798,   'price' => 1098977.0,  'voucher_type' => 'NETEASE_LIFEAFTER',  'need_zone' => true],
            'mobile-legends' => ['voucher_id' => 27684,   'price' => 527250.0,   'voucher_type' => 'MOBILE_LEGENDS',     'need_zone' => true],
            'mobile-legends-adventure' => ['voucher_id' => 36359,   'price' => 739000.0,   'voucher_type' => 'ADVENTURE',          'need_zone' => true, 'voucher_type_id' => 47],
            'magic-chess-go-go' => ['voucher_id' => 997117,  'price' => 1579,   'voucher_type' => '106-MAGIC_CHESS',      'need_zone' => true],
            'mu-origin-2' => ['voucher_id' => 16273,   'price' => 550000.0,   'voucher_type' => 'OURPALM',            'need_zone' => true],
            'one-punch-man' => ['voucher_id' => 77832,   'price' => 5500000.0,  'voucher_type' => 'ONE_PUNCH_MAN',      'need_zone' => true],
            'ragnarok-m' => ['voucher_id' => 414041,  'price' => 1519050.0,  'voucher_type' => 'GRAVITY_RAGNAROK_M', 'need_zone' => true],
            'ragnarok-x' => ['voucher_id' => 195837,  'price' => 1000000.0,  'voucher_type' => 'RAGNAROK_X',         'need_zone' => true],
            'watcher-of-realms' => ['voucher_id' => 963012,  'price' => 819000.0,   'voucher_type' => 'WATCHER_OF_REALMS',  'need_zone' => true],

            // ── Fixed zone (hardcoded, tidak perlu input server) ─────────────
            'barbarq' => ['voucher_id' => 5171,   'price' => 660000.0,  'voucher_type' => 'ELECSOUL',   'need_zone' => false, 'nickname_field' => 'result', 'fixed_zone' => '1'],
            'sausage-man' => ['voucher_id' => 256634, 'price' => 1599000.0, 'voucher_type' => 'SAUSAGE_MAN', 'need_zone' => false, 'fixed_zone' => 'global-release'],

            // ── DancingIdol API ──────────────────────────────────────────────
            'au2-mobile' => ['api' => 'dancingidol', 'need_zone' => false],

            // ── Dynamic SKU Token (JWT bisa expire, perbarui jika perlu) ────
            'aether-gazer' => [
                'voucher_id' => 62,      'price' => 765900.0, 'voucher_type' => '547-AETHER_GAZER', 'need_zone' => false,
                'lvt_id' => 11840,
                'dynamic_sku_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkeW5hbWljU2t1SW5mbyI6IntcInNrdUlkXCI6XCJjb20ueW9zdGFyLmFldGhlcmdhemVyLnNoaWZ0aW5nZmxvd2VyNVwiLFwiZXZlbnRQYWNrYWdlXCI6XCIwXCIsXCJkZW5vbUltYWdlVXJsXCI6XCJodHRwczovL2NkbjEuY29kYXNob3AuY29tL2ltYWdlcy81NDdfM2QyMTBiNzUtNTJkYi00YjUxLTgzMGYtZDYxMTFiNjFkNDQ5X0FFVEhFUiBHQVpFUl9pbWFnZS9Db2RhX0FHX1NLVWltYWdlcy8yOTkwLnBuZ1wiLFwiZGVub21OYW1lXCI6XCIyOTkwIFNoaWZ0aW5nIEZsb3dlcnNcIixcImRlbm9tQ2F0ZWdvcnlOYW1lXCI6XCJTaGlmdGluZyBGbG93ZXJzXCIsXCJ0YWdzXCI6W10sXCJjb3VudHJ5Mk5hbWVcIjpcIklEXCIsXCJsdnRJZFwiOjExODQwfSJ9.qyp6OeAstvp7_0tRS4vWuvcko6D4quDUGTTRMWrbrHM',
                'price_point_dynamic_sku_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkeW5hbWljU2t1SW5mbyI6IntcInBjSWRcIjoyMjAsXCJwcmljZVwiOjc2NTkwMC4wLFwiY3VycmVuY3lcIjpcIklEUlwiLFwiYXBpUHJpY2VcIjo3NjU5MDAuMCxcImFwaVByaWNlQ3VycmVuY3lcIjpcIklEUlwiLFwicHJpY2VCZWZvcmVUYXhcIjo2OTAwMDAuMCxcInRheEFtb3VudFwiOjc1OTAwLjAsXCJza3VJZFwiOlwiY29tLnlvc3Rhci5hZXRoZXJnYXplci5zaGlmdGluZ2Zsb3dlcjVcIixcImx2dElkXCI6MTE4NDB9In0.8WW45qBicqad7rTGEFzMOEWUmkxEwIm76-nd0yVBTYc',
            ],
            'farlight-84' => [
                'voucher_id' => 229,     'price' => 559900.0, 'voucher_type' => 'FARLIGHT84', 'need_zone' => false,
                'lvt_id' => 4138,
                'dynamic_sku_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkeW5hbWljU2t1SW5mbyI6IntcInNrdUlkXCI6XCJjb20ubWlyYWNsZWdhbWVzLnNvbGFybGFuZC5sb3Rjb2luXzQwMDBcIixcImV2ZW50UGFja2FnZVwiOlwiMFwiLFwiZGVub21JbWFnZVVybFwiOlwiaHR0cHM6Ly9jZG4xLmNvZGFzaG9wLmNvbS9pbWFnZXMvNzQzXzkwMTk2YjZlLTlkODYtNGM4Ni1hZmJiLTY4NTg0M2QyNzM5Y19GYXJsaWdodCA4NF9pbWFnZS80NzAwX0ZhcmxpZ2h0X0RpYW1vbmRzLnBuZ1wiLFwiZGVub21OYW1lXCI6XCI0NzAwIERpYW1vbmRzXCIsXCJkZW5vbUNhdGVnb3J5TmFtZVwiOlwiVW5jYXRlZ29yaXplZFwiLFwidGFnc1wiOltdLFwiY291bnRyeTJOYW1lXCI6XCJJRFwiLFwibHZ0SWRcIjo0MTM4fSJ9.i442v0Nxnnq09y0r5V9N-xJ6w4x5bd9jhqExylX_u7s',
                'price_point_dynamic_sku_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkeW5hbWljU2t1SW5mbyI6IntcInBjSWRcIjoyMjAsXCJwcmljZVwiOjU1OTkwMC4wLFwiY3VycmVuY3lcIjpcIklEUlwiLFwiYXBpUHJpY2VcIjo1NTk5MDAuMCxcImFwaVByaWNlQ3VycmVuY3lcIjpcIklEUlwiLFwicHJpY2VCZWZvcmVUYXhcIjo1MDQ0MTQuMCxcInRheEFtb3VudFwiOjU1NDg2LjAsXCJza3VJZFwiOlwiY29tLm1pcmFjbGVnYW1lcy5zb2xhcmxhbmQubG90Y29pbl80MDAwXCIsXCJsdnRJZFwiOjQxMzh9In0.RXIVBNGby3DdMmpjoqAjDWfyLserZaZ0Ajshqp2ioLM',
            ],
            'love-and-deepspace' => [
                'voucher_id' => 125,     'price' => 799000.0, 'voucher_type' => 'INFOLD_GAMES-LOVE_AND_DEEPSPACE', 'need_zone' => false,
                'lvt_id' => 11684,
                'dynamic_sku_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkeW5hbWljU2t1SW5mbyI6IntcInNrdUlkXCI6XCI2XzEwMDVcIixcImV2ZW50UGFja2FnZVwiOlwiMFwiLFwiZGVub21JbWFnZVVybFwiOlwiaHR0cHM6Ly9jZG4xLmNvZGFzaG9wLmNvbS9pbWFnZXMvOTE2XzQ0Y2MyNmU3LWU3NDctNDk4NS04MzQ1LWZmODFjMGUwM2QxN19MT1ZFIEFORCBERUVQU1BBQ0VfaW1hZ2UvMzI4MCBDcnlzdGFscy5wbmdcIixcImRlbm9tTmFtZVwiOlwiMzI4MCBDcnlzdGFsczc3MjAgRGlhbW9uZHNcIixcImRlbm9tQ2F0ZWdvcnlOYW1lXCI6XCJDcnlzdGFsXCIsXCJ0YWdzXCI6W10sXCJjb3VudHJ5Mk5hbWVcIjpcIklEXCIsXCJsdnRJZFwiOjExNjg0LFwiZGVmYXVsdFByaWNlXCI6Nzk5MDAwLjAsXCJkZWZhdWx0Q3VycmVuY3lcIjpcIklEUlwiLFwiYWRkaXRpb25hbEluZm9cIjp7XCJEeW5hbWljU2t1UHJvbW9EZXRhaWxcIjpcIm51bGxcIixcIkxveWFsdHlDdXJyZW5jeURldGFpbFwiOlwie1xcXCJwcmljaW5nU2NoZW1lXFxcIjpcXFwicGFpZF9jdXJyZW5jeVxcXCIsXFxcImxveWFsdHlFYXJuZWRBbW91bnRcXFwiOjAuMCxcXFwibG95YWx0eUJ1cm5lZEFtb3VudFxcXCI6MC4wfVwifX0ifQ.9qYXDANw-mvEGUDZwJWYHow4xe1aMy27ATQ3HwRMpqc',
                'price_point_dynamic_sku_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkeW5hbWljU2t1SW5mbyI6IntcInBjSWRcIjoyMjAsXCJwcmljZVwiOjc5OTAwMC4wLFwiY3VycmVuY3lcIjpcIklEUlwiLFwiYXBpUHJpY2VcIjo3OTkwMDAuMCxcImFwaVByaWNlQ3VycmVuY3lcIjpcIklEUlwiLFwiZGlzY291bnRQcmljZVwiOjc5OTAwMC4wLFwicHJpY2VCZWZvcmVUYXhcIjo3MTk4MjAuMCxcInRheEFtb3VudFwiOjc5MTgwLjAsXCJza3VJZFwiOlwiNl8xMDA1XCIsXCJsdnRJZFwiOjExNjg0fSJ9.egPFavIM4u6tfG5wjYyCwXWY8IQZkLF4UbnqdOzHpGc',
            ],
            'pixel-gun-3d' => [
                'voucher_id' => 410,     'price' => 788767.0, 'voucher_type' => 'PIXEL_GUN_3D', 'need_zone' => false,
                'lvt_id' => 11461,
                'dynamic_sku_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkeW5hbWljU2t1SW5mbyI6IntcInNrdUlkXCI6XCIyMDE1OjIyMDBcIixcImV2ZW50UGFja2FnZVwiOlwiMFwiLFwiZGVub21JbWFnZVVybFwiOlwiXCIsXCJkZW5vbU5hbWVcIjpcIjIyMDAgR2Vtc1wiLFwiZGVub21DYXRlZ29yeU5hbWVcIjpcIkdlbXNcIixcInRhZ3NcIjpbXSxcImNvdW50cnkyTmFtZVwiOlwiSURcIixcImx2dElkXCI6MTE0NjEsXCJkZWZhdWx0UHJpY2VcIjo3ODg3NjYuMCxcImRlZmF1bHRDdXJyZW5jeVwiOlwiSURSXCIsXCJhZGRpdGlvbmFsSW5mb1wiOntcIkR5bmFtaWNTa3VQcm9tb0RldGFpbFwiOlwibnVsbFwiLFwiTG95YWx0eUN1cnJlbmN5RGV0YWlsXCI6XCJ7XFxcInByaWNpbmdTY2hlbWVcXFwiOlxcXCJwYWlkX2N1cnJlbmN5XFxcIixcXFwibG95YWx0eUVhcm5lZEFtb3VudFxcXCI6MC4wLFxcXCJsb3lhbHR5QnVybmVkQW1vdW50XFxcIjowLjB9XCJ9fSJ9.Ejfuo0gIJHP2drx6Q6Ax0L8U8la7iyyoeGRwz5kYCdM',
                'price_point_dynamic_sku_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJkeW5hbWljU2t1SW5mbyI6IntcInBjSWRcIjo5MDYsXCJwcmljZVwiOjc4ODc2Ny4wLFwiY3VycmVuY3lcIjpcIklEUlwiLFwiYXBpUHJpY2VcIjo3ODg3NjYuMCxcImFwaVByaWNlQ3VycmVuY3lcIjpcIklEUlwiLFwiZGlzY291bnRQcmljZVwiOjc4ODc2Ny4wLFwicHJpY2VCZWZvcmVUYXhcIjo3MTA2MDEuMCxcInRheEFtb3VudFwiOjc4MTY2LjAsXCJza3VJZFwiOlwiMjAxNToyMjAwXCIsXCJsdnRJZFwiOjExNDYxfSJ9.evXYDJkpDK2BCTSF3TxajPyQske4BpEkzAD6CSW-Vig',
            ],
        ],
    ],

    'product_grouping' => [
        'fallback_label' => env('PRODUCT_GROUPING_FALLBACK_LABEL', 'Produk Lainnya'),
        'default_rules' => [
            'Diamond' => ['diamond'],
            'Event Top Up' => ['event'],
        ],
        'rules_by_slug' => [
            'genshin' => [
                'Blessing' => ['blessing', 'welkin'],
                'Genesis Crystal' => ['genesis', 'crystal'],
            ],
            'hsr' => [
                'Express Supply Pass' => ['express', 'supply', 'pass'],
                'Oneiric Shard' => ['oneiric', 'shard'],
            ],
            'mobile-legends' => [
                'WDP' => ['wdp', 'weekly', 'weekly diamond pass'],
                'Diamond' => ['diamond'],
            ],
        ],
    ],

];
