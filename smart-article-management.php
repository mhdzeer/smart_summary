<?php
/**
 * Plugin Name: Smart Article Management (Standalone)
 * Description: (V 1.1.1) - نظام إدارة وترتيب المقالات الذكي، كشف الحلقات المفقودة وتعديل الأسماء.
 * Version: 1.1.1
 * Author: Abu Taher
 */

if (!defined('ABSPATH'))
    exit;

// إضافة التنسيقات والخطوط
add_action('admin_head', 'sam_styles');
function sam_styles()
{
    echo '<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <style>
        .sam-wrap { direction: rtl; font-family: "Cairo", sans-serif; padding: 20px; background: #f9f9f9; border-radius: 8px; margin-top: 20px; }
        .sam-header { background: #1a73e8; color: #fff; padding: 20px; border-radius: 8px 8px 0 0; margin: -20px -20px 20px -20px; }
        .sam-card { background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .sam-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .sam-table th, .sam-table td { padding: 12px; border-bottom: 1px solid #eee; text-align: right; }
        .sam-table th { background: #f4f4f4; }
        .sam-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .sam-badge-missing { background: #ffebee; color: #c62828; }
        .sam-badge-ok { background: #e8f5e9; color: #2e7d32; }
        .sam-btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-family: "Cairo"; }
        .sam-btn-primary { background: #1a73e8; color: #fff; }
        .sam-btn-success { background: #2e7d32; color: #fff; }
        .sam-input { padding: 8px; border: 1px solid #ccc; border-radius: 4px; width: 100%; box-sizing: border-box; }
        #sam_loading { display:none; margin-right: 10px; }
    </style>';
}

// إضافة قائمة الإدارة
add_action('admin_menu', 'sam_admin_menu');
function sam_admin_menu()
{
    add_menu_page('إدارة المقالات الذكية', 'إدارة المقالات', 'manage_options', 'smart-article-mgmt', 'sam_admin_page', 'dashicons-media-spreadsheet');
}

// صفحة الإدارة الرئيسية
function sam_admin_page()
{
    $categories = get_categories();
    ?>
    <div class="wrap sam-wrap">
        <div class="sam-header">
            <h1 style="color:#fff; margin:0;">🚀 مدقق ومحرر المقالات الذكي</h1>
            <p style="margin:5px 0 0 0;">قم بتنظيم مقالاتك، اكتشف النواقص، ووحد الأسماء بضغطة زر.</p>
        </div>

        <div class="sam-card">
            <h3>🔍 اختر التصنيف للفحص:</h3>
            <select id="sam_cat_select" class="sam-input" style="max-width:300px;">
                <option value="">-- اختر التصنيف --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat->term_id; ?>">
                        <?php echo esc_html($cat->name); ?> (
                        <?php echo $cat->count; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="button" id="sam_btn_fetch" class="sam-btn sam-btn-primary">فحص المقالات</button>
            <span id="sam_loading">⏳ جاري التحميل...</span>
        </div>

        <div id="sam_result_area" style="display:none;">
            <div class="sam-card">
                <h3>📊 نتائج الفحص:</h3>
                <div id="sam_summary" style="margin-bottom:15px; font-weight:bold;"></div>
                <table class="sam-table">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>العنوان الحالي</th>
                            <th>العنوان المقترح (تعديل ذكي)</th>
                            <th>الحالة</th>
                            <th>الإجراء</th>
                        </tr>
                    </thead>
                    <tbody id="sam_post_list">
                    </tbody>
                </table>
                <div style="margin-top:20px;">
                    <button type="button" id="sam_btn_rename_all" class="sam-btn sam-btn-success">✅ اعتماد جميع التعديلات
                        المقترحة</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        jQuery(document).ready(function ($) {
            $('#sam_btn_fetch').click(function () {
                var cat_id = $('#sam_cat_select').val();
                if (!cat_id) return alert('الرجاء اختيار تصنيف أولاً');

                $('#sam_loading').show();
                $('#sam_result_area').hide();

                $.post(ajaxurl, {
                    action: 'sam_fetch_posts',
                    cat_id: cat_id
                }, function (res) {
                    $('#sam_loading').hide();
                    if (res.success) {
                        $('#sam_post_list').html(res.data.html);
                        $('#sam_summary').html(res.data.summary);
                        $('#sam_result_area').show();
                    } else {
                        alert('خطأ في جلب البيانات');
                    }
                });
            });

            $(document).on('click', '.sam-btn-update', function () {
                var row = $(this).closest('tr');
                var post_id = $(this).data('id');
                var new_title = row.find('.sam-new-title').val();
                var btn = $(this);

                btn.attr('disabled', true).text('⏳...');
                $.post(ajaxurl, {
                    action: 'sam_update_title',
                    post_id: post_id,
                    title: new_title
                }, function (res) {
                    if (res.success) {
                        btn.text('✅ تم').css('background', '#4caf50');
                    } else {
                        alert('فشل التعديل');
                        btn.attr('disabled', false).text('تعديل');
                    }
                });
            });

            $('#sam_btn_rename_all').click(function () {
                if (!confirm('هل أنت متأكد من رغبتك في تعديل جميع الأسماء المقترحة؟')) return;
                $('.sam-btn-update').each(function () {
                    if (!$(this).is(':disabled')) $(this).click();
                });
            });
        });
    </script>
    <?php
}

// AJAX: جلب المقالات وتحليلها
add_action('wp_ajax_sam_fetch_posts', 'sam_fetch_posts_callback');

function sam_extract_number($title) {
    // 1. تنظيف النص وتبسيطه
    $title = mb_convert_encoding($title, 'UTF-8');
    
    // 2. البحث عن الأرقام العادية (1, 2, 3...)
    if (preg_match('/(\d+)/', $title, $matches)) {
        return intval($matches[1]);
    }

    // 3. خريطة الأرقام المكتوبة بالعربية (ترتيب تنازلي للطول لتجنب الأخطاء)
    $arabic_numbers = [
        'الثالث عشر' => 13, 'الثاني عشر' => 12, 'الحادي عشر' => 11,
        'العاشرة' => 10, 'العاشر' => 10, 'عشرة' => 10,
        'التاسعة' => 9, 'التاسع' => 9, 'تسعة' => 9,
        'الثامنة' => 8, 'الثامن' => 8, 'ثمانية' => 8,
        'السابعة' => 7, 'السابع' => 7, 'سبعة' => 7,
        'السادسة' => 6, 'السادس' => 6, 'ستة' => 6,
        'الخامسة' => 5, 'الخامس' => 5, 'خمسة' => 5,
        'الرابعة' => 4, 'الرابع' => 4, 'أربعة' => 4,
        'الثالثة' => 3, 'الثالث' => 3, 'ثلاثة' => 3,
        'الثانية' => 2, 'الثاني' => 2, 'اثنين' => 2,
        'الأولى' => 1, 'الأول' => 1, 'واحد' => 1
    ];

    foreach ($arabic_numbers as $word => $num) {
        if (mb_stripos($title, $word) !== false) {
            return $num;
        }
    }

    return 0;
}

function sam_fetch_posts_callback() {
    $cat_id = intval($_POST['cat_id']);
    $posts = get_posts([
        'category' => $cat_id,
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ]);

    $html = '';
    $post_items = [];
    $existing_numbers = [];

    foreach($posts as $p) {
        $num = sam_extract_number($p->post_title);
        if($num > 0) $existing_numbers[$num] = true;
        
        $cat_name = get_cat_name($cat_id);
        $suggested_title = $num > 0 ? $cat_name . " - الحلقة " . $num : $p->post_title;

        $post_items[] = [
            'id' => $p->ID,
            'title' => $p->post_title,
            'num' => $num,
            'suggested' => $suggested_title
        ];
    }

    // ترتيب العرض حسب الرقم
    usort($post_items, function($a, $b) {
        return $a['num'] - $b['num'];
    });

    foreach($post_items as $item) {
        $html .= '<tr>';
        $html .= '<td>' . ($item['num'] > 0 ? $item['num'] : '-') . '</td>';
        $html .= '<td>' . esc_html($item['title']) . '</td>';
        $html .= '<td><input type="text" class="sam-input sam-new-title" value="' . esc_attr($item['suggested']) . '"></td>';
        $html .= '<td><span class="sam-badge sam-badge-ok">موجود</span></td>';
        $html .= '<td><button type="button" class="sam-btn sam-btn-primary sam-btn-update" data-id="' . $item['id'] . '">تعديل</button></td>';
        $html .= '</tr>';
    }

    // كشف الفجوات (Gaps)
    $summary = "إجمالي المقالات: " . count($posts);
    if (!empty($existing_numbers)) {
        $found_nums = array_keys($existing_numbers);
        sort($found_nums);
        $min = min($found_nums);
        $max = max($found_nums);
        $missing = [];
        for ($i = $min; $i <= $max; $i++) {
            if (!isset($existing_numbers[$i])) $missing[] = $i;
        }
        
        if (!empty($missing)) {
            $summary .= " | <span style='color:red;'>⚠️ حلقات مفقودة: " . implode(', ', $missing) . "</span>";
            foreach ($missing as $m) {
                $html .= '<tr style="background:#fff5f5;">';
                $html .= '<td>' . $m . '</td>';
                $html .= '<td style="color:#c62828;">❌ حلقة مفقودة (رقم ' . $m . ')</td>';
                $html .= '<td>-</td>';
                $html .= '<td><span class="sam-badge sam-badge-missing">مفقود</span></td>';
                $html .= '<td>-</td>';
                $html .= '</tr>';
            }
        } else {
            $summary .= " | ✅ لا توجد حلقات مفقودة في السلسلة.";
        }
    }

    wp_send_json_success(['html' => $html, 'summary' => $summary]);
}

// AJAX: تحديث عنوان المقال
add_action('wp_ajax_sam_update_title', 'sam_update_title_callback');
function sam_update_title_callback()
{
    $post_id = intval($_POST['post_id']);
    $new_title = sanitize_text_field($_POST['title']);

    if ($post_id && $new_title) {
        wp_update_post([
            'ID' => $post_id,
            'post_title' => $new_title
        ]);
        wp_send_json_success();
    }
    wp_send_json_error();
}
