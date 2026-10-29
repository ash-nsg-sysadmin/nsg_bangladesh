<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$pageTitle = __t('Leadership & Board', 'নির্বাহী নেতৃত্ব');

// Hero Section Settings
$boardTitle                   = page_section('board', 'board_hero_title', 'Executive Leadership & Board');
$boardTitleColor              = page_section('board', 'board_hero_title_color', '#111111');
$boardTitleSize               = page_section('board', 'board_hero_title_size', '3rem');

$boardIntro                   = page_section('board', 'board_intro', 'Guided by experienced leaders dedicated to ethical growth, strategic operational strength, and talent development across Bangladesh and global markets.');
$boardIntroColor              = page_section('board', 'board_intro_color', '#555555');
$boardIntroSize               = page_section('board', 'board_intro_size', '1.15rem');

// Section Banner Styling Controls
$boardHeaderShowBorder        = page_section('board', 'board_header_show_border', '1');
$boardHeaderBorderStyle       = page_section('board', 'board_header_border_style', 'solid');
$boardHeaderBorderWidth       = page_section('board', 'board_header_border_width', '2');
$boardHeaderBorderColor       = page_section('board', 'board_header_border_color', '#111111');
$boardHeaderBgColor           = page_section('board', 'board_header_bg_color', '#ffffff');
$boardHeaderTextColor         = page_section('board', 'board_header_text_color', '#111111');

// Fetch Members Grouped by Board Category (ordered by group_order) and Hierarchy Level
$boardSections = [];

try {
    $qBoard = db()->query("SELECT * FROM board_members WHERE status='published' ORDER BY group_order ASC, board_group ASC, hierarchy_level ASC, sort_order ASC, id ASC");
    $allMembers = $qBoard->fetchAll();

    foreach ($allMembers as $m) {
        $grp   = !empty($m['board_group']) ? $m['board_group'] : 'US Leadership Board';
        $level = max(1, (int)($m['hierarchy_level'] ?? 1));

        if (!isset($boardSections[$grp])) {
            $boardSections[$grp] = [];
        }
        if (!isset($boardSections[$grp][$level])) {
            $boardSections[$grp][$level] = [];
        }

        $boardSections[$grp][$level][] = $m;
    }
} catch (Throwable $t) {
}

require __DIR__ . '/app/header.php';
?>

<!-- 1. Hero Header Banner -->
<section class="nsg-section nsg-bg-white text-center" style="padding-top: 4.5rem; padding-bottom: 3rem; border-bottom: 1px solid #f0f0f0;">
    <div class="nsg-container">
        <span class="nsg-eyebrow"><?= __t('EXECUTIVE GOVERNANCE', 'পরিচালনা পর্ষদ') ?></span>
        <h1 class="nsg-heading-serif" style="font-size: <?= e($boardTitleSize) ?>; color: <?= e($boardTitleColor) ?>; margin-top: 0.5rem; margin-bottom: 1rem;">
            <?= e(get_localized(['title' => $boardTitle], 'title')) ?>
        </h1>
        <p style="margin: 0 auto; font-size: <?= e($boardIntroSize) ?>; color: <?= e($boardIntroColor) ?>; max-width: 760px; line-height: 1.7;">
            <?= e(get_localized(['intro' => $boardIntro], 'intro')) ?>
        </p>
    </div>
</section>

<!-- 2. Dynamic Leadership Hierarchy Rendering -->
<section class="nsg-section nsg-bg-light" style="padding: 4rem 0;">
    <div class="nsg-container">

        <?php foreach ($boardSections as $groupTitle => $levels): ?>
            
            <div class="nsg-board-block" style="margin-bottom: 5rem;">
                
                <!-- BOARD CATEGORY TITLE BOX WITH FULL STYLING CONTROLS -->
                <?php
                $bannerBorderStyle = ($boardHeaderShowBorder === '1') 
                    ? 'border: ' . e($boardHeaderBorderWidth) . 'px ' . e($boardHeaderBorderStyle) . ' ' . e($boardHeaderBorderColor) . ';' 
                    : 'border: none;';
                ?>
                <div style="background: <?= e($boardHeaderBgColor) ?>; <?= $bannerBorderStyle ?> padding: 1.25rem 2rem; text-align: center; margin-bottom: 3.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.04);">
                    <h2 class="nsg-heading-serif" style="font-size: 2.2rem; margin: 0; color: <?= e($boardHeaderTextColor) ?>; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
                        <?= e($groupTitle) ?>
                    </h2>
                </div>

                <!-- RENDER EACH HIERARCHY LEVEL (Level 1, Level 2, Level 3...) -->
                <?php foreach ($levels as $levelNum => $membersInLevel): ?>
                    
                    <?php if (count($membersInLevel) === 1): ?>
                        <!-- SINGLE MEMBER LEVEL (CENTERED DYNAMIC CARD) -->
                        <?php $topLeader = $membersInLevel[0]; ?>
                        <?php
                        $photoSrc    = !empty($topLeader['photo_path']) ? site_url($topLeader['photo_path']) : 'assets/leader-placeholder.jpg';
                        $showBorder  = (int)($topLeader['show_border'] ?? 1) === 1;
                        $bStyle      = !empty($topLeader['border_style']) ? $topLeader['border_style'] : 'solid';
                        $bWidth      = (int)($topLeader['border_width'] ?? 2);
                        $borderColor = !empty($topLeader['card_border_color']) ? $topLeader['card_border_color'] : '#b71c1c';
                        $bgColor     = !empty($topLeader['card_bg_color']) ? $topLeader['card_bg_color'] : '#ffffff';
                        $textColor   = !empty($topLeader['text_color']) ? $topLeader['text_color'] : '#111111';
                        $radius      = (int)($topLeader['card_radius'] ?? 8);
                        $shadow      = !empty($topLeader['card_shadow']) && $topLeader['card_shadow'] !== 'none' ? $topLeader['card_shadow'] : 'none';
                        $photoShape  = !empty($topLeader['photo_shape']) ? $topLeader['photo_shape'] : '50%';
                        $fontFam     = !empty($topLeader['font_family']) ? $topLeader['font_family'] : 'Playfair Display, serif';
                        $nameSize    = !empty($topLeader['name_size']) ? $topLeader['name_size'] : '1.75rem';
                        $titleSize   = !empty($topLeader['title_size']) ? $topLeader['title_size'] : '0.8rem';

                        $cardBorderStyle = $showBorder ? "border: {$bWidth}px {$bStyle} {$borderColor};" : "border: none;";
                        ?>
                        <div style="display: flex; justify-content: center; margin-bottom: 3rem;">
                            <div style="max-width: 500px; width: 100%; background: <?= e($bgColor) ?>; <?= $cardBorderStyle ?> border-radius: <?= $radius ?>px; padding: 2.5rem 2rem; text-align: center; box-shadow: <?= e($shadow) ?>;">
                                
                                <div style="width: 140px; height: 140px; border-radius: <?= e($photoShape) ?>; overflow: hidden; border: 3px solid <?= e($borderColor) ?>; margin: 0 auto 1.25rem auto; background: #f0f0f0;">
                                    <img src="<?= e($photoSrc) ?>" alt="<?= e($topLeader['full_name']) ?>" style="width: 100%; height: 100%; object-fit: cover; display: block;">
                                </div>

                                <span class="nsg-eyebrow" style="font-size: <?= e($titleSize) ?>; color: <?= e($borderColor) ?>; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 0.3rem;">
                                    <?= e($topLeader['title']) ?>
                                </span>
                                <h3 class="nsg-heading-serif-sm" style="font-size: <?= e($nameSize) ?>; font-family: <?= e($fontFam) ?>; margin: 0 0 0.75rem; color: <?= e($textColor) ?>;">
                                    <?= e($topLeader['full_name']) ?>
                                </h3>

                                <?php if (!empty($topLeader['message'])): ?>
                                    <blockquote style="margin: 0 0 1rem 0; font-style: italic; color: #444; font-size: 0.95rem; font-family: Playfair Display, serif;">
                                        "<?= e($topLeader['message']) ?>"
                                    </blockquote>
                                <?php endif; ?>

                                <p style="font-size: 0.92rem; line-height: 1.6; color: #555; margin: 0;">
                                    <?= nl2br(e($topLeader['biography'])) ?>
                                </p>
                            </div>
                        </div>

                    <?php else: ?>
                        <!-- MULTI-MEMBER LEVEL ROW (GRID LAYOUT) -->
                        <div class="nsg-grid-3" style="gap: 2rem; margin-bottom: 3rem;">
                            <?php foreach ($membersInLevel as $member): ?>
                                <?php
                                $mPhotoSrc    = !empty($member['photo_path']) ? site_url($member['photo_path']) : 'assets/leader-placeholder.jpg';
                                $mShowBorder  = (int)($member['show_border'] ?? 1) === 1;
                                $mbStyle      = !empty($member['border_style']) ? $member['border_style'] : 'solid';
                                $mbWidth      = (int)($member['border_width'] ?? 2);
                                $mBorderColor = !empty($member['card_border_color']) ? $member['card_border_color'] : '#b71c1c';
                                $mBgColor     = !empty($member['card_bg_color']) ? $member['card_bg_color'] : '#ffffff';
                                $mTextColor   = !empty($member['text_color']) ? $member['text_color'] : '#111111';
                                $mRadius      = (int)($member['card_radius'] ?? 8);
                                $mShadow      = !empty($member['card_shadow']) && $member['card_shadow'] !== 'none' ? $member['card_shadow'] : 'none';
                                $mPhotoShape  = !empty($member['photo_shape']) ? $member['photo_shape'] : '50%';
                                $mFontFam     = !empty($member['font_family']) ? $member['font_family'] : 'Playfair Display, serif';
                                $mNameSize    = !empty($member['name_size']) ? $member['name_size'] : '1.4rem';
                                $mTitleSize   = !empty($member['title_size']) ? $member['title_size'] : '0.75rem';

                                $mCardBorderStyle = $mShowBorder ? "border: {$mbWidth}px {$mbStyle} {$mBorderColor};" : "border: none;";
                                ?>
                                <div style="padding: 2.25rem 2rem; text-align: center; background: <?= e($mBgColor) ?>; <?= $mCardBorderStyle ?> border-radius: <?= $mRadius ?>px; box-shadow: <?= e($mShadow) ?>; display: flex; flex-direction: column; align-items: center;">

                                    <div style="width: 120px; height: 120px; border-radius: <?= e($mPhotoShape) ?>; overflow: hidden; border: 3px solid <?= e($mBorderColor) ?>; margin-bottom: 1.25rem; background: #f0f0f0;">
                                        <img src="<?= e($mPhotoSrc) ?>" alt="<?= e($member['full_name']) ?>" style="width: 100%; height: 100%; object-fit: cover; display: block;">
                                    </div>

                                    <span class="nsg-eyebrow" style="font-size: <?= e($mTitleSize) ?>; color: <?= e($mBorderColor) ?>; font-weight: 700; margin-bottom: 0.25rem; text-transform: uppercase; letter-spacing: 1px;">
                                        <?= e($member['title']) ?>
                                    </span>
                                    <h3 class="nsg-heading-serif-sm" style="font-size: <?= e($mNameSize) ?>; font-family: <?= e($mFontFam) ?>; margin: 0 0 0.75rem; color: <?= e($mTextColor) ?>;">
                                        <?= e($member['full_name']) ?>
                                    </h3>

                                    <?php if (!empty($member['message'])): ?>
                                        <blockquote style="margin: 0 0 0.75rem 0; font-style: italic; color: #444; font-size: 0.9rem; font-family: Playfair Display, serif;">
                                            "<?= e($member['message']) ?>"
                                        </blockquote>
                                    <?php endif; ?>

                                    <p style="font-size: 0.9rem; line-height: 1.6; color: #555; margin: 0; flex: 1;">
                                        <?= nl2br(e($member['biography'])) ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <?php endforeach; ?>

            </div>

        <?php endforeach; ?>

    </div>
</section>

<!-- 3. Join Vision CTA Banner -->
<section class="nsg-cta-banner" style="background: #000; color: #fff; padding: 4rem 0;">
    <div class="nsg-container text-center">
        <h2 class="nsg-heading-serif" style="color: #fff; font-size: 2.25rem;"><?= __t('Be part of our expanding journey', 'আমাদের পথচলার অংশ হন') ?></h2>
        <div style="margin-top: 1.5rem;">
            <a href="<?= e(site_url('careers.php')) ?>" class="nsg-btn-red"><?= __t('EXPLORE CAREERS AT NSG', 'ক্যারিয়ারের সুযোগ দেখুন') ?></a>
        </div>
    </div>
</section>

<?php require __DIR__ . '/app/footer.php'; ?>