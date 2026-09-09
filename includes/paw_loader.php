<?php
/**
 * ASENA Signature Multi-Animal Liquid Paw Loader
 * Server-rendered instant splash with animated liquid fill
 */

$animalModels = [
    [
        'id' => 'cat',
        'name' => 'گربه ملوس',
        'emoji' => '🐱',
        'viewBox' => '0 0 100 100',
        'path' => '
            <path d="M 50,50 C 33,50 23,61 25,75 C 27,86 37,90 50,87 C 63,90 73,86 75,75 C 77,61 67,50 50,50 Z" />
            <ellipse cx="25" cy="40" rx="8" ry="12" transform="rotate(-22 25 40)" />
            <ellipse cx="42" cy="27" rx="8.5" ry="13" transform="rotate(-7 42 27)" />
            <ellipse cx="58" cy="27" rx="8.5" ry="13" transform="rotate(7 58 27)" />
            <ellipse cx="75" cy="40" rx="8" ry="12" transform="rotate(22 75 40)" />
        '
    ],
    [
        'id' => 'dog',
        'name' => 'سگ باوفا',
        'emoji' => '🐶',
        'viewBox' => '0 0 100 100',
        'path' => '
            <path d="M 50,52 C 35,52 20,63 24,79 C 28,91 40,93 50,87 C 60,93 72,91 76,79 C 80,63 65,52 50,52 Z" />
            <ellipse cx="24" cy="42" rx="9" ry="14" transform="rotate(-25 24 42)" />
            <ellipse cx="42" cy="27" rx="9.5" ry="14.5" transform="rotate(-8 42 27)" />
            <ellipse cx="58" cy="27" rx="9.5" ry="14.5" transform="rotate(8 58 27)" />
            <ellipse cx="76" cy="42" rx="9" ry="14" transform="rotate(25 76 42)" />
            <path d="M 21,27 Q 23,21 25,27" stroke-width="2" stroke-linecap="round" />
            <path d="M 40,11 Q 42,5 44,11" stroke-width="2" stroke-linecap="round" />
            <path d="M 56,11 Q 58,5 60,11" stroke-width="2" stroke-linecap="round" />
            <path d="M 75,27 Q 77,21 79,27" stroke-width="2" stroke-linecap="round" />
        '
    ],
    [
        'id' => 'chick',
        'name' => 'پرندگان و طوطی‌سانان',
        'emoji' => '🐥',
        'viewBox' => '0 0 100 100',
        'path' => '
            <path d="M 47,56 C 47,42 46,26 47,15 C 48,11 52,11 53,15 C 54,26 53,42 53,56 C 60,50 70,42 79,33 C 83,29 86,34 83,37 C 74,46 64,54 56,61 C 56,68 55,77 53,86 C 51,91 49,91 47,86 C 45,77 44,68 44,61 C 36,54 26,46 17,37 C 14,34 17,29 21,33 C 30,42 40,50 47,56 Z" />
            <circle cx="50" cy="58" r="6.5" />
            <circle cx="50" cy="14" r="5" />
            <circle cx="81" cy="35" r="4.5" />
            <circle cx="19" cy="35" r="4.5" />
            <circle cx="50" cy="88" r="4" />
            <path d="M 50,11 L 50,5" stroke-width="2.5" stroke-linecap="round" />
            <path d="M 82,34 L 89,28" stroke-width="2.5" stroke-linecap="round" />
            <path d="M 18,34 L 11,28" stroke-width="2.5" stroke-linecap="round" />
        '
    ],
    [
        'id' => 'cow',
        'name' => 'دام و حیوانات بزرگ',
        'emoji' => '🐮',
        'viewBox' => '0 0 100 100',
        'path' => '
            <path d="M 46,14 C 41,13 32,20 23,34 C 14,48 13,66 18,78 C 22,87 34,89 43,84 C 46,82 46,76 46,68 C 45,50 45,32 46,14 Z" />
            <path d="M 54,14 C 59,13 68,20 77,34 C 86,48 87,66 82,78 C 78,87 66,89 57,84 C 54,82 54,76 54,68 C 55,50 55,32 54,14 Z" />
            <ellipse cx="27" cy="92" rx="6" ry="4" transform="rotate(-15 27 92)" />
            <ellipse cx="73" cy="92" rx="6" ry="4" transform="rotate(15 73 92)" />
        '
    ]
];

$chosenAnimal = $animalModels[array_rand($animalModels)];
$gradId = 'serverPawGrad_' . bin2hex(random_bytes(4));
$loaderTitle = 'سامانه مراکز و بیمارستان‌های دامپزشکی آسنا';
?>
<!-- Top Turbo Progress Bar -->
<div id="asena-top-bar"></div>

<!-- Signature Liquid Paw Loader Splash -->
<div id="asena-paw-loader" role="dialog" aria-label="در حال بارگذاری آسنا">
    <div class="paw-loader-card">
        <div class="paw-svg-container" style="box-shadow: 0 10px 25px -5px rgba(2, 132, 199, 0.25);">
            <svg viewBox="<?= $chosenAnimal['viewBox'] ?>" width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="<?= $gradId ?>" x1="0%" y1="100%" x2="0%" y2="0%">
                        <stop offset="0%" stop-color="#0284c7" />
                        <stop offset="100%" stop-color="#38bdf8" />
                    </linearGradient>
                </defs>
                <g class="paw-bg-path">
                    <?= $chosenAnimal['path'] ?>
                </g>
                <g class="paw-fill-path" fill="url(#<?= $gradId ?>)" stroke="#0284c7" stroke-width="0.8">
                    <?= $chosenAnimal['path'] ?>
                </g>
            </svg>
        </div>
        <div style="text-align: center; display: flex; flex-direction: column; align-items: center;">
            <div class="paw-loader-title">
                <span style="font-size: 19px;"><?= $chosenAnimal['emoji'] ?></span>
                <span><?= $loaderTitle ?></span>
            </div>
            <div class="paw-loader-sub">ردپای <?= $chosenAnimal['name'] ?> • بارگذاری هوشمند سامانه...</div>
            <div class="paw-progress-pill">
                <div class="paw-progress-bar" style="background: linear-gradient(to left, #0284c7, #38bdf8);"></div>
            </div>
        </div>
    </div>
</div>
