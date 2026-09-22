<?php
require_once __DIR__ . '/../../config/session_admin.php';

if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
    header("Location: ../auth/login.php");
    exit;
}

$isSupervisor = ($_SESSION['admin_role'] ?? '') === 'supervisor';
$currentPage  = basename($_SERVER['PHP_SELF']);

// ── صفحات الأدمن فقط (محظورة على المشرف) ────────────────────
$adminOnlyPages = [
    'supervisors.php', 'add-supervisor.php', 'edit-supervisor.php',
    'settings.php', 'reset_progress.php', 'do_reset.php',
    'reset_general.php', 'sign-videos.php', 'animals_setup.php',
];

// ── خريطة الصفحة → الصلاحية المطلوبة ────────────────────────
// null = لا يوجد فحص مركزي (يُفحص داخلياً)
// [section, action] = يجب أن يملك المشرف هذه الصلاحية للدخول
// بالنسبة لصفحات التعديل المرتبطة بملكية عنصر يُفحص داخلياً أيضاً
$pagePermMap = [
    'lessons.php'               => null,
    'add-lesson.php'            => ['arabic_letters',   'can_add'],
    'edit-arabic-example.php'   => null,   // يُتحقق داخلياً (ملكية أو can_edit)
    'subject-lessons.php'       => null,

    'add-english-letter.php'    => ['english_letters',  'can_add'],
    'edit-english-letter.php'   => null,   // يُتحقق داخلياً

    'add-arabic-number.php'     => ['arabic_numbers',   'can_add'],
    'edit-arabic-number.php'    => null,   // يُتحقق داخلياً

    'add-english-number.php'    => ['english_numbers',  'can_add'],
    'edit-english-number.php'   => null,   // يُتحقق داخلياً

    'stories.php'               => null,   // يُتحقق داخلياً (أي صلاحية)
    'story.php'                 => ['stories',          'can_view'],
    'add-story.php'             => ['stories',          'can_add'],
    'edit-story.php'            => null,   // يُتحقق داخلياً (ملكية أو can_edit)

    'games.php'                 => ['games',            'can_view'],

    // ── الثقافة العامة ──────────────────────────────────────
    'weather.php'               => ['gc_weather',       'can_view'],
    'seasons.php'               => ['gc_seasons',       'can_view'],
    'days.php'                  => ['gc_days',          'can_view'],
    'admin-pillars.php'         => ['gc_islam',         'can_view'],

    // الحيوانات — مقسّمة حسب النوع
    'manage-animals.php'        => null,   // يُتحقق داخلياً حسب category
    'add-animal.php'            => null,   // يُتحقق داخلياً حسب category
    'edit-animal.php'           => null,   // يُتحقق داخلياً حسب category
    'pets.php'                  => ['gc_animals_pet',  'can_view'],
    'wild-animals.php'          => ['gc_animals_wild', 'can_view'],

    // الفواكه والخضار — مقسّمة حسب النوع
    'manage-food.php'           => null,   // يُتحقق داخلياً حسب category
    'add-food.php'              => null,   // يُتحقق داخلياً حسب category
    'edit-food.php'             => null,   // يُتحقق داخلياً حسب category
    'fruits.php'                => ['gc_food_fruit',      'can_view'],
    'vegetables.php'            => ['gc_food_vegetable',  'can_view'],

    'manage-custom-section.php' => null,
    'add-general-section.php'   => null,

    'lesson-types.php'          => null,

    'children.php'              => ['children',         'can_view'],
    'add-child.php'             => ['children',         'can_add'],
    'edit-child.php'            => ['children',         'can_edit'],
    'delete-child.php'          => ['children',         'can_delete'],

    'reports.php'               => ['reports',          'can_view'],

    'admin-chat.php'            => ['chat',             'can_view'],
    'track.php'                 => ['chat',             'can_view'],
];

if ($isSupervisor) {

    if (in_array($currentPage, $adminOnlyPages)) {
        header('Location: supervisor-dashboard.php'); exit;
    }

    if ($currentPage === 'dashboard.php') {
        header('Location: supervisor-dashboard.php'); exit;
    }

    // ── تحميل صلاحيات المشرف ────────────────────────────────
    if (!isset($GLOBALS['_supPerms'])) {
        global $conn;
        if (!isset($conn)) {
            require_once __DIR__ . '/../../config/db.php';
            mysqli_set_charset($conn, 'utf8mb4');
        }
        $supId = intval($_SESSION['supervisor_id'] ?? 0);
        $GLOBALS['_supPerms'] = [];
        $spr = mysqli_query($conn, "SELECT * FROM supervisor_permissions WHERE supervisor_id=$supId");
        if ($spr) while ($sp = mysqli_fetch_assoc($spr)) $GLOBALS['_supPerms'][$sp['section']] = $sp;
    }

    // ── تحقق من الصلاحيات للصفحات المربوطة ──────────────────
    if (isset($pagePermMap[$currentPage]) && $pagePermMap[$currentPage] !== null) {
        [$section, $action] = $pagePermMap[$currentPage];
        if (!supCan($section, $action)) {
            header('Location: supervisor-dashboard.php'); exit;
        }
    }
}

/**
 * التسلسل الهرمي للصلاحيات:
 *
 *  general_culture  ← ماستر كبير يشمل كل gc_*
 *  ├── gc_animals   ← ماستر الحيوانات يشمل gc_animals_wild + gc_animals_pet
 *  │   ├── gc_animals_wild
 *  │   └── gc_animals_pet
 *  ├── gc_food      ← ماستر الفواكه/خضار يشمل gc_food_vegetable + gc_food_fruit
 *  │   ├── gc_food_vegetable
 *  │   └── gc_food_fruit
 *  ├── gc_weather
 *  ├── gc_seasons
 *  ├── gc_days
 *  ├── gc_islam
 *  └── gc_custom_{id}
 */
function supCan(string $section, string $action = 'can_view'): bool {
    if (($_SESSION['admin_role'] ?? '') !== 'supervisor') return true;

    $p = $GLOBALS['_supPerms'];

    // الماستر الكبير general_culture يمنح كل gc_*
    if (strpos($section, 'gc_') === 0 && !empty($p['general_culture'][$action])) return true;

    // ماستر gc_animals يمنح gc_animals_wild و gc_animals_pet
    if (in_array($section, ['gc_animals_wild', 'gc_animals_pet'])
        && !empty($p['gc_animals'][$action])) return true;

    // ماستر gc_food يمنح gc_food_vegetable و gc_food_fruit
    if (in_array($section, ['gc_food_vegetable', 'gc_food_fruit'])
        && !empty($p['gc_food'][$action])) return true;

    return !empty($p[$section][$action]);
}

/**
 * هل يملك المشرف أي صلاحية على قسم معين؟
 * يُستخدم للتحقق من إمكانية الدخول للصفحة (بدلاً من اشتراط can_view فقط)
 */
function supHasAny(string $section): bool {
    if (($_SESSION['admin_role'] ?? '') !== 'supervisor') return true;
    return supCan($section, 'can_view')
        || supCan($section, 'can_add')
        || supCan($section, 'can_edit')
        || supCan($section, 'can_delete');
}

/**
 * هل يملك المشرف الحالي عنصراً معيناً؟
 * المشرف يملك أي عنصر أضافه هو (created_by_supervisor = supervisor_id)
 * المالك يحصل تلقائياً على view + edit + delete لعنصره بغض النظر عن الصلاحيات العامة
 */
function supOwns(int $createdBySupervisor): bool {
    if (($_SESSION['admin_role'] ?? '') !== 'supervisor') return false;
    $supId = intval($_SESSION['supervisor_id'] ?? 0);
    return $supId > 0 && $createdBySupervisor === $supId;
}
