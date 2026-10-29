<?php

declare(strict_types=1);

require dirname(__DIR__) . '/_init.php';

require_admin('pages.read');

$pageSlug = 'board';
$message  = '';
$error    = '';

function handle_leader_photo_upload(string $fileInputKey): ?string
{
    if (!isset($_FILES[$fileInputKey]) || $_FILES[$fileInputKey]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $tmpName = $_FILES[$fileInputKey]['tmp_name'];
    $name    = basename($_FILES[$fileInputKey]['name']);
    $ext     = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed, true)) {
        throw new Exception('Invalid image format. Allowed: JPG, PNG, GIF, WEBP');
    }

    $uploadDir = dirname(__DIR__, 2) . '/uploads/images/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = uniqid('leader_', true) . '.' . $ext;
    $targetPath = $uploadDir . $filename;

    if (move_uploaded_file($tmpName, $targetPath)) {
        return 'uploads/images/' . $filename;
    }

    throw new Exception('Failed to upload leader photo.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = $_POST['form_action'] ?? 'save_hero';

    try {
        if ($action === 'save_hero') {
            db()->beginTransaction();

            $qPage = db()->prepare('
                INSERT INTO pages (title, slug, seo_title, seo_description, status, created_at, updated_at, updated_by) 
                VALUES (?, ?, ?, ?, ?, NOW(), NOW(), ?) 
                ON DUPLICATE KEY UPDATE seo_title = VALUES(seo_title), seo_description = VALUES(seo_description), status = VALUES(status), updated_at = NOW(), updated_by = VALUES(updated_by)
            ');
            $qPage->execute(['Leadership & Board Page', $pageSlug, trim($_POST['seo_title'] ?? ''), trim($_POST['seo_description'] ?? ''), $_POST['status'] ?? 'published', $admin['id']]);

            $qSection = db()->prepare('
                INSERT INTO page_sections (page_slug, section_key, content_type, content_value) 
                VALUES (?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE content_value = VALUES(content_value), updated_at = NOW()
            ');

            $boardFields = [
                'board_hero_title'           => $_POST['sec_board_hero_title'] ?? 'Executive Leadership & Board',
                'board_hero_title_color'     => $_POST['sec_board_hero_title_color'] ?? '#111111',
                'board_hero_title_size'      => $_POST['sec_board_hero_title_size'] ?? '3rem',
                'board_intro'                => $_POST['sec_board_intro'] ?? '',
                'board_intro_color'          => $_POST['sec_board_intro_color'] ?? '#555555',
                'board_intro_size'           => $_POST['sec_board_intro_size'] ?? '1.15rem',
                'board_header_show_border'   => $_POST['sec_board_header_show_border'] ?? '1',
                'board_header_border_style'  => $_POST['sec_board_header_border_style'] ?? 'solid',
                'board_header_border_width'  => $_POST['sec_board_header_border_width'] ?? '2',
                'board_header_border_color'  => $_POST['sec_board_header_border_color'] ?? '#111111',
                'board_header_bg_color'      => $_POST['sec_board_header_bg_color'] ?? '#ffffff',
                'board_header_text_color'    => $_POST['sec_board_header_text_color'] ?? '#111111',
            ];

            foreach ($boardFields as $k => $v) {
                $type = str_contains($k, 'color') ? 'color' : 'text';
                $qSection->execute([$pageSlug, $k, $type, trim((string)$v)]);
            }

            db()->commit();
            $message = 'Page hero and section banner styles saved successfully!';
        }

        elseif ($action === 'save_leader') {
            $leaderId        = (int)($_POST['leader_id'] ?? 0);
            $fullName        = trim($_POST['full_name'] ?? '');
            $title           = trim($_POST['title'] ?? '');
            $boardGroup      = trim($_POST['board_group'] ?? 'US Leadership Board');
            $groupOrder      = max(1, (int)($_POST['group_order'] ?? 1));
            $hierarchyLevel  = max(1, (int)($_POST['hierarchy_level'] ?? 1));
            
            // Custom Styling Parameters
            $showBorder      = isset($_POST['show_border']) ? 1 : 0;
            $borderStyle     = trim($_POST['border_style'] ?? 'solid');
            $borderWidth     = max(0, (int)($_POST['border_width'] ?? 2));
            $cardBorderColor = trim($_POST['card_border_color'] ?? '#b71c1c');
            $cardBgColor     = trim($_POST['card_bg_color'] ?? '#ffffff');
            $textColor       = trim($_POST['text_color'] ?? '#111111');
            $cardRadius      = (int)($_POST['card_radius'] ?? 8);
            $cardShadow      = trim($_POST['card_shadow'] ?? '0 4px 16px rgba(0,0,0,0.04)');
            $photoShape      = trim($_POST['photo_shape'] ?? '50%');
            $fontFamily      = trim($_POST['font_family'] ?? 'Playfair Display, serif');
            $nameSize        = trim($_POST['name_size'] ?? '1.4rem');
            $titleSize       = trim($_POST['title_size'] ?? '0.8rem');

            $messageTxt      = trim($_POST['message'] ?? '');
            $biography       = trim($_POST['biography'] ?? '');
            $sortOrder       = (int)($_POST['sort_order'] ?? 1);
            $status          = $_POST['leader_status'] ?? 'published';

            if (empty($fullName) || empty($title)) {
                throw new Exception('Full Name and Designation Title are required.');
            }

            $photoPath = handle_leader_photo_upload('photo_file');
            if ($photoPath === null && isset($_POST['existing_photo'])) {
                $photoPath = $_POST['existing_photo'];
            }

            if ($leaderId > 0) {
                $stmt = db()->prepare("
                    UPDATE board_members 
                    SET full_name = ?, title = ?, board_group = ?, group_order = ?, hierarchy_level = ?, show_border = ?, border_style = ?, border_width = ?, card_border_color = ?, card_bg_color = ?, text_color = ?, card_radius = ?, card_shadow = ?, photo_shape = ?, font_family = ?, name_size = ?, title_size = ?, photo_path = ?, message = ?, biography = ?, sort_order = ?, status = ?
                    WHERE id = ?
                ");
                $stmt->execute([$fullName, $title, $boardGroup, $groupOrder, $hierarchyLevel, $showBorder, $borderStyle, $borderWidth, $cardBorderColor, $cardBgColor, $textColor, $cardRadius, $cardShadow, $photoShape, $fontFamily, $nameSize, $titleSize, $photoPath, $messageTxt, $biography, $sortOrder, $status, $leaderId]);
                $message = 'Leadership profile updated successfully!';
            } else {
                $stmt = db()->prepare("
                    INSERT INTO board_members (full_name, title, board_group, group_order, hierarchy_level, show_border, border_style, border_width, card_border_color, card_bg_color, text_color, card_radius, card_shadow, photo_shape, font_family, name_size, title_size, photo_path, message, biography, sort_order, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$fullName, $title, $boardGroup, $groupOrder, $hierarchyLevel, $showBorder, $borderStyle, $borderWidth, $cardBorderColor, $cardBgColor, $textColor, $cardRadius, $cardShadow, $photoShape, $fontFamily, $nameSize, $titleSize, $photoPath, $messageTxt, $biography, $sortOrder, $status]);
                $message = 'New leader profile added successfully!';
            }
        }

        elseif ($action === 'delete_leader') {
            $leaderId = (int)($_POST['leader_id'] ?? 0);
            if ($leaderId > 0) {
                $stmt = db()->prepare("DELETE FROM board_members WHERE id = ?");
                $stmt->execute([$leaderId]);
                $message = 'Leader profile deleted successfully!';
            }
        }
    } catch (Throwable $t) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        $error = 'Error: ' . $t->getMessage();
    }
}

// Fetch Data
$pageRecord = null;
try {
    $q = db()->prepare('SELECT * FROM pages WHERE slug = ? LIMIT 1');
    $q->execute([$pageSlug]);
    $pageRecord = $q->fetch();
} catch (Throwable $t) {}

$savedValues = [];
try {
    $q = db()->prepare('SELECT section_key, content_value FROM page_sections WHERE page_slug = ?');
    $q->execute([$pageSlug]);
    $rows = $q->fetchAll();
    foreach ($rows as $r) {
        $savedValues[$r['section_key']] = $r['content_value'];
    }
} catch (Throwable $t) {}

$allLeaders = [];
try {
    $qL = db()->query("SELECT * FROM board_members ORDER BY group_order ASC, board_group ASC, hierarchy_level ASC, sort_order ASC, id ASC");
    $allLeaders = $qL->fetchAll();
} catch (Throwable $t) {}

$editLeader = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    foreach ($allLeaders as $l) {
        if ((int)$l['id'] === $editId) {
            $editLeader = $l;
            break;
        }
    }
}

$pageTitle = 'Manage Leadership & Board';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: system-ui, -apple-system, sans-serif; }
        body { background: #f4f6f8; color: #333; line-height: 1.5; }
        .admin-layout { display: flex; min-height: 100vh; width: 100%; }
        .admin-sidebar { width: 260px; min-width: 260px; background: #123B5D; color: #fff; padding: 1.5rem 1rem; display: flex; flex-direction: column; flex-shrink: 0; }
        .admin-sidebar h2 { font-size: 1.2rem; color: #fff; margin-bottom: 1.5rem; border-bottom: 1px solid rgba(255, 255, 255, 0.15); padding-bottom: 0.75rem; }
        .admin-sidebar-nav { display: flex; flex-direction: column; gap: 0.4rem; flex: 1; }
        .admin-sidebar-nav a { color: #cfd8dc; text-decoration: none; padding: 0.75rem 1rem; border-radius: 6px; font-size: 0.9rem; display: block; }
        .admin-sidebar-nav a:hover, .admin-sidebar-nav a.active { background: rgba(255, 255, 255, 0.15); color: #fff; font-weight: 600; }
        .admin-main { flex: 1; padding: 2rem; background: #f4f6f8; overflow-y: auto; max-width: 100%; }
        .admin-main h1 { font-size: 1.75rem; color: #111; margin-bottom: 0.25rem; }
        .card-panel { background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 1.5rem; border: 1px solid #eef2f5; }
        .card-panel h2 { font-size: 1.25rem; color: #123B5D; border-bottom: 2px solid #f0f0f0; padding-bottom: 0.5rem; margin-bottom: 1.25rem; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.4rem; color: #333; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 0.65rem; border: 1px solid #ccc; border-radius: 4px; font-size: 0.95rem; }
        .color-picker-wrap { display: flex; align-items: center; gap: 0.5rem; }
        .color-picker-wrap input[type="color"] { width: 42px; height: 38px; cursor: pointer; padding: 2px; border: 1px solid #ccc; border-radius: 4px; }
        .leader-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .leader-table th, .leader-table td { padding: 0.85rem 1rem; text-align: left; border-bottom: 1px solid #eee; font-size: 0.9rem; }
        .leader-table th { background: #fafafa; font-weight: 600; color: #555; }
        .avatar-preview { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid #b71c1c; vertical-align: middle; }
        .btn-submit { padding: 0.85rem 2rem; background: #123B5D; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 0.95rem; }
        .btn-red { background: #b71c1c; }
        .action-link { color: #123B5D; font-weight:600; text-decoration:none; margin-right: 0.75rem; }
        .action-btn-del { background:none; border:none; color:#c62828; cursor:pointer; font-weight:600; }
    </style>
</head>
<body>

    <div class="admin-layout">
        <?= render_admin_sidebar('board') ?>

        <main class="admin-main">
            <h1>Dynamic Leadership & Board Management</h1>
            <p style="color:#666; margin-bottom: 1.5rem;">Create dynamic groups, re-order board sections (US vs BD), set unlimited hierarchy levels, and edit individual cards.</p>

            <?php if ($message): ?><p style="color: #2e7d32; background: #e8f5e9; padding: 0.75rem; border-radius: 4px; margin-bottom: 1.5rem;"><?= e($message) ?></p><?php endif; ?>
            <?php if ($error): ?><p style="color: #c62828; background: #ffebee; padding: 0.75rem; border-radius: 4px; margin-bottom: 1.5rem;"><?= e($error) ?></p><?php endif; ?>

            <!-- SECTION 1: ADD / EDIT LEADER FORM -->
            <div class="card-panel" style="border-top: 4px solid #b71c1c;" id="member-form">
                <h2><?= $editLeader ? '✏️ Edit Member: ' . e($editLeader['full_name']) : '➕ Add New Leadership Member' ?></h2>
                <form method="post" action="board.php#member-form" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="form_action" value="save_leader">
                    <input type="hidden" name="leader_id" value="<?= (int)($editLeader['id'] ?? 0) ?>">
                    <input type="hidden" name="existing_photo" value="<?= e($editLeader['photo_path'] ?? '') ?>">

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="full_name" value="<?= e($editLeader['full_name'] ?? '') ?>" placeholder="e.g. Mr. Noor" required>
                        </div>

                        <div class="form-group">
                            <label>Designation Title *</label>
                            <input type="text" name="title" value="<?= e($editLeader['title'] ?? '') ?>" placeholder="e.g. President & Founder" required>
                        </div>

                        <div class="form-group">
                            <label>Board Section / Group Name *</label>
                            <input type="text" name="board_group" value="<?= e($editLeader['board_group'] ?? 'US Leadership Board') ?>" placeholder="e.g. US Leadership Board or BD Leadership Board">
                        </div>

                        <div class="form-group">
                            <label>Board Group Sort Order (1 = Top Section, 2 = Below...)</label>
                            <input type="number" name="group_order" value="<?= (int)($editLeader['group_order'] ?? 1) ?>" min="1" placeholder="1 for US, 2 for BD">
                        </div>

                        <div class="form-group">
                            <label>Hierarchy Level (1, 2, 3, 4...)</label>
                            <input type="number" name="hierarchy_level" value="<?= (int)($editLeader['hierarchy_level'] ?? 1) ?>" min="1" required>
                        </div>

                        <div class="form-group">
                            <label>Sort Order in Level</label>
                            <input type="number" name="sort_order" value="<?= (int)($editLeader['sort_order'] ?? 1) ?>" min="1">
                        </div>

                        <div class="form-group">
                            <label>Status</label>
                            <select name="leader_status">
                                <option value="published" <?= ($editLeader['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Published</option>
                                <option value="draft" <?= ($editLeader['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                            </select>
                        </div>
                    </div>

                    <!-- CARD & FONT DESIGN CONTROLS -->
                    <h3 style="font-size:1rem; color:#123B5D; margin: 1rem 0 0.75rem 0;">🎨 Card Borders, Colors & Typography</h3>
                    <div class="form-grid" style="background: #fafafa; padding: 1.25rem; border-radius: 6px; margin-bottom: 1.25rem; border: 1px solid #eee;">
                        
                        <div class="form-group">
                            <label>Enable Outer Border?</label>
                            <label style="font-weight: normal; margin-top:0.4rem; cursor:pointer; display:block;">
                                <input type="checkbox" name="show_border" value="1" <?= (int)($editLeader['show_border'] ?? 1) === 1 ? 'checked' : '' ?>> Show Card Border
                            </label>
                        </div>

                        <div class="form-group">
                            <label>Border Style</label>
                            <select name="border_style">
                                <option value="solid" <?= ($editLeader['border_style'] ?? 'solid') === 'solid' ? 'selected' : '' ?>>Solid Line</option>
                                <option value="dashed" <?= ($editLeader['border_style'] ?? '') === 'dashed' ? 'selected' : '' ?>>Dashed Line</option>
                                <option value="dotted" <?= ($editLeader['border_style'] ?? '') === 'dotted' ? 'selected' : '' ?>>Dotted Line</option>
                                <option value="double" <?= ($editLeader['border_style'] ?? '') === 'double' ? 'selected' : '' ?>>Double Line</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Border Width (px)</label>
                            <input type="number" name="border_width" value="<?= (int)($editLeader['border_width'] ?? 2) ?>" min="0" max="10">
                        </div>

                        <div class="form-group">
                            <label>Border / Accent Color</label>
                            <div class="color-picker-wrap">
                                <input type="color" value="<?= e($editLeader['card_border_color'] ?? '#b71c1c') ?>" onchange="document.getElementById('card_border_color').value = this.value">
                                <input type="text" name="card_border_color" id="card_border_color" value="<?= e($editLeader['card_border_color'] ?? '#b71c1c') ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Card Background</label>
                            <div class="color-picker-wrap">
                                <input type="color" value="<?= e($editLeader['card_bg_color'] ?? '#ffffff') ?>" onchange="document.getElementById('card_bg_color').value = this.value">
                                <input type="text" name="card_bg_color" id="card_bg_color" value="<?= e($editLeader['card_bg_color'] ?? '#ffffff') ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Text Color</label>
                            <div class="color-picker-wrap">
                                <input type="color" value="<?= e($editLeader['text_color'] ?? '#111111') ?>" onchange="document.getElementById('text_color').value = this.value">
                                <input type="text" name="text_color" id="text_color" value="<?= e($editLeader['text_color'] ?? '#111111') ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Font Family</label>
                            <select name="font_family">
                                <option value="Playfair Display, serif" <?= ($editLeader['font_family'] ?? 'Playfair Display, serif') === 'Playfair Display, serif' ? 'selected' : '' ?>>Playfair Display (Serif)</option>
                                <option value="Inter, sans-serif" <?= ($editLeader['font_family'] ?? '') === 'Inter, sans-serif' ? 'selected' : '' ?>>Inter (Clean Sans)</option>
                                <option value="Arial, sans-serif" <?= ($editLeader['font_family'] ?? '') === 'Arial, sans-serif' ? 'selected' : '' ?>>Arial</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Name Font Size</label>
                            <input type="text" name="name_size" value="<?= e($editLeader['name_size'] ?? '1.4rem') ?>" placeholder="e.g. 1.4rem or 22px">
                        </div>

                        <div class="form-group">
                            <label>Title Font Size</label>
                            <input type="text" name="title_size" value="<?= e($editLeader['title_size'] ?? '0.8rem') ?>" placeholder="e.g. 0.8rem or 14px">
                        </div>

                        <div class="form-group">
                            <label>Card Corner Style</label>
                            <select name="card_radius">
                                <option value="0" <?= (int)($editLeader['card_radius'] ?? 8) === 0 ? 'selected' : '' ?>>Sharp Square (0px)</option>
                                <option value="8" <?= (int)($editLeader['card_radius'] ?? 8) === 8 ? 'selected' : '' ?>>Slightly Rounded (8px)</option>
                                <option value="16" <?= (int)($editLeader['card_radius'] ?? 8) === 16 ? 'selected' : '' ?>>Soft Rounded (16px)</option>
                                <option value="24" <?= (int)($editLeader['card_radius'] ?? 8) === 24 ? 'selected' : '' ?>>Smooth Pill (24px)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Photo Shape</label>
                            <select name="photo_shape">
                                <option value="50%" <?= ($editLeader['photo_shape'] ?? '50%') === '50%' ? 'selected' : '' ?>>Circle / Oval (100%)</option>
                                <option value="12px" <?= ($editLeader['photo_shape'] ?? '') === '12px' ? 'selected' : '' ?>>Rounded Box (12px)</option>
                                <option value="0px" <?= ($editLeader['photo_shape'] ?? '') === '0px' ? 'selected' : '' ?>>Sharp Square (0px)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Card Drop Shadow Style</label>
                            <select name="card_shadow">
                                <option value="none" <?= ($editLeader['card_shadow'] ?? '') === 'none' ? 'selected' : '' ?>>None (Flat)</option>
                                <option value="0 4px 16px rgba(0,0,0,0.04)" <?= ($editLeader['card_shadow'] ?? '0 4px 16px rgba(0,0,0,0.04)') === '0 4px 16px rgba(0,0,0,0.04)' ? 'selected' : '' ?>>Soft Glow</option>
                                <option value="0 8px 24px rgba(0,0,0,0.08)" <?= ($editLeader['card_shadow'] ?? '') === '0 8px 24px rgba(0,0,0,0.08)' ? 'selected' : '' ?>>Medium Floating</option>
                                <option value="0 12px 32px rgba(0,0,0,0.12)" <?= ($editLeader['card_shadow'] ?? '') === '0 12px 32px rgba(0,0,0,0.12)' ? 'selected' : '' ?>>Heavy Deep</option>
                            </select>
                        </div>

                    </div>

                    <div class="form-group">
                        <label>Leader Photo (Upload Headshot Image)</label>
                        <input type="file" name="photo_file" accept="image/*">
                        <?php if (!empty($editLeader['photo_path'])): ?>
                            <p style="margin-top:0.4rem;"><img src="<?= e(site_url($editLeader['photo_path'])) ?>" class="avatar-preview"> Current Photo</p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Quote / Statement</label>
                        <input type="text" name="message" value="<?= e($editLeader['message'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>Full Biography</label>
                        <textarea name="biography" rows="3"><?= e($editLeader['biography'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn-submit btn-red"><?= $editLeader ? 'Update Member' : 'Save Member' ?></button>
                    <?php if ($editLeader): ?>
                        <a href="board.php" style="margin-left: 1rem; color: #666; text-decoration: none; font-weight: 600;">Cancel Edit</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- SECTION 2: GLOBAL TITLE BANNER CONTROLS -->
            <div class="card-panel">
                <h2>🎨 Section Title Banner Controls</h2>
                <form method="post" action="board.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="form_action" value="save_hero">

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Show Border on Section Banner Box?</label>
                            <select name="sec_board_header_show_border">
                                <option value="1" <?= ($savedValues['board_header_show_border'] ?? '1') === '1' ? 'selected' : '' ?>>Yes - Show Border</option>
                                <option value="0" <?= ($savedValues['board_header_show_border'] ?? '') === '0' ? 'selected' : '' ?>>No - Clean View</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Banner Border Style</label>
                            <select name="sec_board_header_border_style">
                                <option value="solid" <?= ($savedValues['board_header_border_style'] ?? 'solid') === 'solid' ? 'selected' : '' ?>>Solid</option>
                                <option value="dashed" <?= ($savedValues['board_header_border_style'] ?? '') === 'dashed' ? 'selected' : '' ?>>Dashed</option>
                                <option value="dotted" <?= ($savedValues['board_header_border_style'] ?? '') === 'dotted' ? 'selected' : '' ?>>Dotted</option>
                                <option value="double" <?= ($savedValues['board_header_border_style'] ?? '') === 'double' ? 'selected' : '' ?>>Double Line</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Banner Border Width (px)</label>
                            <input type="number" name="sec_board_header_border_width" value="<?= e($savedValues['board_header_border_width'] ?? '2') ?>" min="0" max="10">
                        </div>

                        <div class="form-group">
                            <label>Banner Border Color</label>
                            <div class="color-picker-wrap">
                                <input type="color" value="<?= e($savedValues['board_header_border_color'] ?? '#111111') ?>" onchange="document.getElementById('sec_board_header_border_color').value = this.value">
                                <input type="text" name="sec_board_header_border_color" id="sec_board_header_border_color" value="<?= e($savedValues['board_header_border_color'] ?? '#111111') ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Banner Background Color</label>
                            <div class="color-picker-wrap">
                                <input type="color" value="<?= e($savedValues['board_header_bg_color'] ?? '#ffffff') ?>" onchange="document.getElementById('sec_board_header_bg_color').value = this.value">
                                <input type="text" name="sec_board_header_bg_color" id="sec_board_header_bg_color" value="<?= e($savedValues['board_header_bg_color'] ?? '#ffffff') ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Banner Text Color</label>
                            <div class="color-picker-wrap">
                                <input type="color" value="<?= e($savedValues['board_header_text_color'] ?? '#111111') ?>" onchange="document.getElementById('sec_board_header_text_color').value = this.value">
                                <input type="text" name="sec_board_header_text_color" id="sec_board_header_text_color" value="<?= e($savedValues['board_header_text_color'] ?? '#111111') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Leadership Page Hero Title</label>
                        <input type="text" name="sec_board_hero_title" value="<?= e($savedValues['board_hero_title'] ?? 'Executive Leadership & Board') ?>">
                    </div>

                    <div class="form-group">
                        <label>Introductory Copy</label>
                        <textarea name="sec_board_intro" rows="2"><?= e($savedValues['board_intro'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn-submit">Save Banner Styling Settings</button>
                </form>
            </div>

            <!-- SECTION 3: MEMBERS TABLE WITH EDIT ACTIONS -->
            <div class="card-panel">
                <h2>👥 Board Members List (<?= count($allLeaders) ?> Records)</h2>
                <table class="leader-table">
                    <thead>
                        <tr>
                            <th>Board Group (Order)</th>
                            <th>Level</th>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Designation</th>
                            <th>Border Style</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allLeaders as $l): ?>
                            <tr>
                                <td><strong><?= e($l['board_group']) ?></strong> <span style="font-size:0.8rem; color:#888;">(Order #<?= e((string)($l['group_order'] ?? 1)) ?>)</span></td>
                                <td><span style="background:#e3f2fd; color:#1565c0; padding:0.2rem 0.5rem; border-radius:4px; font-weight:700;">Level <?= e((string)$l['hierarchy_level']) ?></span></td>
                                <td>
                                    <?php if (!empty($l['photo_path'])): ?>
                                        <img src="<?= e(site_url($l['photo_path'])) ?>" class="avatar-preview">
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= e($l['full_name']) ?></strong></td>
                                <td><?= e($l['title']) ?></td>
                                <td><?= (int)($l['show_border'] ?? 1) === 1 ? e((string)$l['border_width']) . 'px ' . e($l['border_style'] ?? 'solid') : 'None' ?></td>
                                <td>
                                    <a href="board.php?edit=<?= e((string)$l['id']) ?>#member-form" class="action-link">✏️ Edit</a>
                                    
                                    <form method="post" action="board.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this leader?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="form_action" value="delete_leader">
                                        <input type="hidden" name="leader_id" value="<?= e((string)$l['id']) ?>">
                                        <button type="submit" class="action-btn-del">🗑️ Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

</body>
</html>