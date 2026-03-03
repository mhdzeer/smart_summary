<?php
/**
 * Plugin Name: Smart Article Management (Standalone)
 * Description: (V 2.0.0) - إدارة المقالات وكشف النواقص عبر إضافة كروم الذكية. بدون API، بدون قيود.
 * Version: 2.0.0
 * Author: Abu Taher
 */

if (!defined('ABSPATH'))
    exit;

// ===========================
// الأنماط والتصميم
// ===========================
add_action('admin_head', 'sam_styles');
function sam_styles()
{
    echo '<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <style>
        .sam-wrap { direction: rtl; font-family: "Cairo", sans-serif; padding: 20px; background: #f9f9f9; border-radius: 8px; margin-top: 20px; }
        .sam-header { background: linear-gradient(135deg, #1a73e8, #0d47a1); color: #fff; padding: 20px; border-radius: 8px 8px 0 0; margin: -20px -20px 20px -20px; }
        .sam-card { background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .sam-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .sam-table th, .sam-table td { padding: 12px; border-bottom: 1px solid #eee; text-align: right; }
        .sam-table th { background: #f4f4f4; font-weight: bold; }
        .sam-badge { padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .sam-badge-missing { background: #ffebee; color: #c62828; }
        .sam-badge-ok { background: #e8f5e9; color: #2e7d32; }
        .sam-badge-yt { background: #e3f2fd; color: #0d47a1; }
        .sam-btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-family: "Cairo"; margin-left: 5px; transition: opacity 0.2s; }
        .sam-btn:hover { opacity: 0.88; }
        .sam-btn-primary { background: #1a73e8; color: #fff; }
        .sam-btn-success { background: #2e7d32; color: #fff; }
        .sam-btn-danger { background: #c62828; color: #fff; }
        .sam-input { padding: 8px 10px; border: 1px solid #ccc; border-radius: 6px; width: 100%; box-sizing: border-box; font-family: "Cairo"; }
        .sam-input:focus { border-color: #1a73e8; outline: none; }
        .sam-variable-hint { font-size: 11px; color: #888; margin-top: 4px; }
        #sam_loading { display:none; margin-right: 10px; color: #1a73e8; font-weight: bold; }
        .token-box { background: #f3f4f6; padding: 10px 14px; border-radius: 6px; font-family: monospace; font-size: 14px; letter-spacing: 1px; color: #333; border: 1px dashed #999; display: inline-block; margin-top: 8px; }
        .ext-steps { padding-right: 20px; line-height: 2; font-size: 13px; }
    </style>';
}

// ===========================
// قائمة الإدارة
// ===========================
add_action('admin_menu', 'sam_admin_menu');
function sam_admin_menu()
{
    add_menu_page('إدارة المقالات الذكية', 'إدارة المقالات', 'manage_options', 'smart-article-mgmt', 'sam_admin_page', 'dashicons-media-spreadsheet');
}

// ===========================
// صفحة الإدارة الرئيسية
// ===========================
function sam_admin_page()
{
    // حفظ الإعدادات
    if (isset($_POST['save_sam_settings'])) {
        update_option('sam_title_template', sanitize_text_field($_POST['sam_title_template']));
        echo '<div class="updated"><p>✅ تم حفظ الإعدادات!</p></div>';
    }

    // توليد رمز الأمان إذا لم يكن موجوداً
    $access_token = get_option('sam_access_token');
    if (!$access_token) {
        $access_token = wp_generate_password(32, false);
        update_option('sam_access_token', $access_token);
    }

    // تجديد الرمز إذا طلب المستخدم
    if (isset($_POST['regenerate_token'])) {
        $access_token = wp_generate_password(32, false);
        update_option('sam_access_token', $access_token);
        echo '<div class="updated"><p>🔄 تم تجديد رمز الأمان بنجاح!</p></div>';
    }

    $categories = get_categories(['hide_empty' => false]);
    $template = get_option('sam_title_template', '{cat} - الحلقة {n}');
    ?>
        <div class="wrap sam-wrap">
            <div class="sam-header">
                <h1 style="color:#fff; margin:0;">🚀 مدقق ومحرر المقالات الذكي (V 2.0.0)</h1>
                <p style="margin:5px 0 0 0; opacity:0.9;">إدارة ومزامنة المقالات مع يوتيوب عبر إضافة كروم الذكية.</p>
            </div>

            <!-- إعدادات -->
            <div class="sam-card">
                <h3>⚙️ إعدادات القوالب:</h3>
                <form method="post">
                    <div style="max-width:400px;">
                        <label><b>قالب أسماء المقالات:</b></label>
                        <input type="text" name="sam_title_template" value="<?php echo esc_attr($template); ?>" class="sam-input">
                        <div class="sam-variable-hint">{cat} = اسم التصنيف | {n} = رقم الحلقة</div>
                    </div>
                    <input type="submit" name="save_sam_settings" class="sam-btn sam-btn-primary" style="margin-top:12px;" value="💾 حفظ">
                </form>
            </div>

            <!-- ربط إضافة الكروم -->
            <div class="sam-card" style="border-right: 5px solid #1a73e8;">
                <h3>🔌 ربط إضافة كروم المزامنة:</h3>
                <p>استخدم المعلومات التالية لضبط إضافة كروم على المتصفح:</p>
                <table style="margin-top:10px; font-size:13px; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 6px 12px 6px 0; font-weight:bold; color:#555;">🌐 رابط الموقع:</td>
                        <td><code class="token-box"><?php echo esc_html(get_site_url()); ?></code></td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 12px 6px 0; font-weight:bold; color:#555;">🔑 رمز الأمان:</td>
                        <td>
                            <code class="token-box"><?php echo esc_html($access_token); ?></code>
                            <form method="post" style="display:inline; margin-right:10px;">
                                <input type="submit" name="regenerate_token" class="sam-btn sam-btn-danger" style="padding:5px 12px; font-size:12px;" value="🔄 تجديد الرمز">
                            </form>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 12px 6px 0; font-weight:bold; color:#555;">📂 التصنيفات المتاحة:</td>
                        <td>
                            <?php foreach ($categories as $cat): ?>
                                <span style="background:#e3f2fd; color:#0d47a1; padding:3px 8px; border-radius:4px; margin-left:5px; font-size:12px;">
                                    <?php echo esc_html($cat->name); ?> (ID: <?php echo $cat->term_id; ?>)
                                </span>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                </table>
                <div style="background:#fafafa; border:1px dashed #bbb; border-radius:6px; padding:12px; margin-top:14px; font-size:12px;">
                    <b>📋 خطوات تثبيت إضافة الكروم:</b>
                    <ol class="ext-steps">
                        <li>افتح المتصفح واذهب إلى: <code>chrome://extensions</code></li>
                        <li>فعّل "وضع المطور" (Developer Mode) من الزاوية العليا</li>
                        <li>اضغط "Load unpacked" واختر مجلد <code>yt-sync-extension</code></li>
                        <li>افتح الإضافة وأدخل الرابط والرمز ورقم التصنيف</li>
                        <li>اذهب ليوتيوب، اضغط على الإضافة، ثم "استيراد الفيديوهات"</li>
                        <li>عد لهذه الصفحة، اختر التصنيف، واضغط "فحص المقالات"</li>
                    </ol>
                </div>
            </div>

            <!-- فحص المقالات -->
            <div class="sam-card">
                <h3>🔍 فحص المقالات وعرض النواقص:</h3>
                <p style="font-size:12px; color:#666; margin-bottom:10px;">اختر التصنيف ثم اضغط "فحص" لعرض المقالات الموجودة والحلقات المفقودة من يوتيوب.</p>
                <select id="sam_cat_select" class="sam-input" style="max-width:320px; display:inline-block;">
                    <option value="">-- اختر التصنيف --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat->term_id; ?>"><?php echo esc_html($cat->name); ?> (<?php echo $cat->count; ?> مقال)</option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="sam_btn_fetch" class="sam-btn sam-btn-primary">🔍 فحص المقالات</button>
                <span id="sam_loading">⏳ جاري الفحص...</span>
            </div>

            <!-- نتائج الفحص -->
            <div id="sam_result_area" style="display:none;">
                <div class="sam-card">
                    <h3>📊 نتائج الفحص:</h3>
                    <div id="sam_summary" style="margin-bottom:15px; font-weight:bold; font-size:15px;"></div>
                    <table class="sam-table">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>العنوان الحالي</th>
                                <th>العنوان المقترح</th>
                                <th>الحالة</th>
                                <th>الإجراء</th>
                            </tr>
                        </thead>
                        <tbody id="sam_post_list"></tbody>
                    </table>
                    <div style="margin-top:16px;">
                        <button type="button" id="sam_btn_rename_all" class="sam-btn sam-btn-success">✅ تعديل جميع الأسماء</button>
                        <button type="button" id="sam_btn_create_missing" class="sam-btn sam-btn-primary" style="display:none;">🏗️ نشر جميع المقالات المفقودة</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($){
            $('#sam_btn_fetch').click(function(){
                var cat_id = $('#sam_cat_select').val();
                if(!cat_id) return alert('الرجاء اختيار تصنيف أولاً');
                $('#sam_loading').show();
                $('#sam_result_area').hide();
                $('#sam_btn_create_missing').hide();
                $.post(ajaxurl, { action: 'sam_fetch_posts', cat_id: cat_id }, function(res){
                    $('#sam_loading').hide();
                    if(res.success) {
                        $('#sam_post_list').html(res.data.html);
                        $('#sam_summary').html(res.data.summary);
                        $('#sam_result_area').show();
                        if(res.data.has_yt_missing) $('#sam_btn_create_missing').show();
                    } else {
                        alert(res.data || 'خطأ في جلب البيانات');
                    }
                });
            });

            $(document).on('click', '.sam-btn-update', function(){
                var row = $(this).closest('tr');
                var post_id = $(this).data('id');
                var new_title = row.find('.sam-new-title').val();
                var btn = $(this);
                btn.attr('disabled', true).text('⏳...');
                $.post(ajaxurl, { action: 'sam_update_title', post_id: post_id, title: new_title }, function(res){
                    if(res.success) { btn.text('✅ تم').css('background','#4caf50'); }
                    else { alert('فشل التعديل'); btn.attr('disabled', false).text('تعديل'); }
                });
            });

            $(document).on('click', '.sam-btn-create-one', function(){
                var btn = $(this);
                var video = btn.data('video');
                var cat_id = $('#sam_cat_select').val();
                btn.attr('disabled', true).text('⏳...');
                $.post(ajaxurl, { action: 'sam_create_post', video_url: video.url, title: video.title, cat_id: cat_id }, function(res){
                    if(res.success) { btn.text('✅ تم النشر').css('background','#2e7d32'); }
                    else { alert('فشل النشر'); btn.attr('disabled', false).text('نشر'); }
                });
            });

            $('#sam_btn_create_missing').click(function(){
                if(!confirm('سيتم نشر جميع الحلقات المفقودة كمسودات. هل تريد الاستمرار؟')) return;
                $('.sam-btn-create-one').each(function(){ if(!$(this).is(':disabled')) $(this).click(); });
            });

            $('#sam_btn_rename_all').click(function(){
                if(!confirm('تعديل جميع الأسماء المقترحة؟')) return;
                $('.sam-btn-update').each(function(){ if(!$(this).is(':disabled')) $(this).click(); });
            });
        });
        </script>
    <?php
}

// ===========================
// جلب المقالات وكشف النواقص
// ===========================
add_action('wp_ajax_sam_fetch_posts', 'sam_fetch_posts_callback');
function sam_fetch_posts_callback()
{
    $cat_id = intval($_POST['cat_id']);
    $cat_name = get_cat_name($cat_id);
    $template = get_option('sam_title_template', '{cat} - الحلقة {n}');

    $posts = get_posts([
        'category' => $cat_id,
        'posts_per_page' => -1,
        'post_status' => ['publish', 'draft', 'future', 'pending'],
        'orderby' => 'title',
        'order' => 'ASC'
    ]);

    $final_list = [];
    $recorded_nums = [];

    // إضافة المقالات الموجودة
    foreach ($posts as $p) {
        $num = sam_extract_number($p->post_title);
        if ($num > 0) $recorded_nums[$num] = true;
        $suggested = ($num > 0) ? str_replace(['{cat}', '{n}'], [$cat_name, $num], $template) : $p->post_title;
        $status_label = (get_post_status($p->ID) === 'publish') ? 'منشور' : 'مسودة';

        $final_list[] = [
            'id' => $p->ID,
            'title' => $p->post_title,
            'num' => $num,
            'suggested' => $suggested,
            'status_html' => '<span class="sam-badge sam-badge-ok">موجود (' . $status_label . ') ✅</span>',
            'action_html' => '<button type="button" class="sam-btn sam-btn-primary sam-btn-update" data-id="' . $p->ID . '" style="padding:6px 12px; font-size:12px;">تعديل</button>',
            'row_style' => ''
        ];
    }

    // إضافة الفيديوهات القادمة من إضافة الكروم (إن وجدت في الكاش)
    $yt_missing_count = 0;
    $cached = get_transient('sam_yt_cache_' . $cat_id);
    if ($cached && isset($cached['items'])) {
        foreach ($cached['items'] as $vid) {
            $v_num = sam_extract_number($vid['snippet']['title']);
            if ($v_num > 0 && !isset($recorded_nums[$v_num])) {
                $yt_missing_count++;
                $v_suggested = str_replace(['{cat}', '{n}'], [$cat_name, $v_num], $template);
                $v_url = 'https://www.youtube.com/watch?v=' . $vid['id']['videoId'];
                $video_data = ['title' => $v_suggested, 'url' => $v_url, 'num' => $v_num];

                $final_list[] = [
                    'id' => 0,
                    'title' => '📺 يوتيوب: ' . $vid['snippet']['title'],
                    'num' => $v_num,
                    'suggested' => $v_suggested,
                    'status_html' => '<span class="sam-badge sam-badge-yt">متاح في يوتيوب 📺</span>',
                    'action_html' => '<button type="button" class="sam-btn sam-btn-primary sam-btn-create-one" data-video=\'' . esc_attr(json_encode($video_data)) . '\' style="padding:6px 12px; font-size:12px;">نشر</button>',
                    'row_style' => 'background:#e3f2fd;'
                ];
                $recorded_nums[$v_num] = 'yt';
            }
        }
    }

    // كشف الحلقات المفقودة ضمن النطاق
    if (!empty($recorded_nums)) {
        $only_nums = array_filter(array_keys($recorded_nums), 'is_numeric');
        if (!empty($only_nums)) {
            $min = min($only_nums);
            $max = max($only_nums);
            for ($i = $min; $i <= $max; $i++) {
                if (!isset($recorded_nums[$i])) {
                    $final_list[] = [
                        'id' => 0,
                        'title' => '❌ حلقة مفقودة (رقم ' . $i . ')',
                        'num' => $i,
                        'suggested' => '-',
                        'status_html' => '<span class="sam-badge sam-badge-missing">مفقود ⚠️</span>',
                        'action_html' => '-',
                        'row_style' => 'background:#fff5f5;'
                    ];
                }
            }
        }
    }

    usort($final_list, function ($a, $b) { return $a['num'] - $b['num']; });

    $html = '';
    foreach ($final_list as $item) {
        $html .= '<tr style="' . $item['row_style'] . '">';
        $html .= '<td>' . ($item['num'] > 0 ? $item['num'] : '-') . '</td>';
        $html .= '<td>' . esc_html($item['title']) . '</td>';
        $html .= '<td><input type="text" class="sam-input sam-new-title" value="' . esc_attr($item['suggested']) . '" ' . ($item['id'] == 0 ? 'readonly' : '') . ' style="font-size:12px;"></td>';
        $html .= '<td>' . $item['status_html'] . '</td>';
        $html .= '<td>' . $item['action_html'] . '</td>';
        $html .= '</tr>';
    }

    $summary = 'إجمالي المقالات: ' . count($posts);
    if ($yt_missing_count > 0) $summary .= ' | <span style="color:#0d47a1;">فيديوهات جاهزة للنشر من يوتيوب: ' . $yt_missing_count . '</span>';

    wp_send_json_success(['html' => $html, 'summary' => $summary, 'has_yt_missing' => ($yt_missing_count > 0)]);
}

// ===========================
// استقبال البيانات من إضافة الكروم
// ===========================
add_action('wp_ajax_sam_process_browser_data', 'sam_process_browser_data_callback');
add_action('wp_ajax_nopriv_sam_process_browser_data', 'sam_process_browser_data_callback');
function sam_process_browser_data_callback()
{
    $token = isset($_POST['sam_token']) ? sanitize_text_field($_POST['sam_token']) : '';
    $saved_token = get_option('sam_access_token');

    if (!$saved_token || $token !== $saved_token) {
        wp_send_json_error('رمز الأمان غير صحيح. تأكد من نسخه بشكل صحيح من صفحة الإضافة.');
    }

    $cat_id = intval($_POST['cat_id']);
    $videos = json_decode(stripslashes($_POST['videos']), true);

    if (!$cat_id || empty($videos)) wp_send_json_error('بيانات غير مكتملة');

    $cat_name = get_cat_name($cat_id);
    $filtered = [];
    foreach ($videos as $vid) {
        $v_id = '';
        if (preg_match('/v=([^&]+)/', $vid['url'], $m)) $v_id = $m[1];
        elseif (preg_match('/shorts\/([^?]+)/', $vid['url'], $m)) $v_id = $m[1];
        if ($v_id && mb_stripos($vid['title'], $cat_name) !== false) {
            $filtered[] = ['id' => ['videoId' => $v_id], 'snippet' => ['title' => $vid['title']]];
        }
    }

    if (empty($filtered)) wp_send_json_error('لم يتم العثور على فيديوهات تطابق التصنيف: ' . $cat_name);

    set_transient('sam_yt_cache_' . $cat_id, ['items' => $filtered], HOUR_IN_SECONDS);
    wp_send_json_success(['imported' => count($filtered)]);
}

// ===========================
// نشر مقال جديد
// ===========================
add_action('wp_ajax_sam_create_post', 'sam_create_post_callback');
function sam_create_post_callback()
{
    $video_url = esc_url_raw($_POST['video_url']);
    $title = sanitize_text_field($_POST['title']);
    $cat_id = intval($_POST['cat_id']);

    if ($video_url && $title) {
        $content  = '<h3>' . esc_html($title) . '</h3>' . "\n";
        $content .= '<p>شاهد الحلقة: <a href="' . esc_url($video_url) . '" target="_blank">' . esc_url($video_url) . '</a></p>' . "\n\n";
        $content .= esc_url($video_url) . "\n\n";
        $content .= '(تمت الإضافة تلقائياً عبر نظام إدارة المقالات الذكي)';

        $new_post = [
            'post_title'    => $title,
            'post_content'  => $content,
            'post_status'   => 'draft',
            'post_category' => [$cat_id]
        ];
        if (wp_insert_post($new_post)) wp_send_json_success();
    }
    wp_send_json_error();
}

// ===========================
// تعديل عنوان مقال
// ===========================
add_action('wp_ajax_sam_update_title', 'sam_update_title_callback');
function sam_update_title_callback()
{
    $post_id = intval($_POST['post_id']);
    $new_title = sanitize_text_field($_POST['title']);
    if ($post_id && $new_title) {
        wp_update_post(['ID' => $post_id, 'post_title' => $new_title]);
        wp_send_json_success();
    }
    wp_send_json_error();
}

// ===========================
// استخراج رقم الحلقة من العنوان
// ===========================
function sam_extract_number($title)
{
    $title = mb_convert_encoding($title, 'UTF-8');
    if (preg_match('/(\d+)/', $title, $m)) return intval($m[1]);

    $arabic = [
        'الثالث عشر' => 13, 'الثاني عشر' => 12, 'الحادي عشر' => 11,
        'العاشرة' => 10, 'العاشر' => 10, 'التاسعة' => 9, 'التاسع' => 9,
        'الثامنة' => 8, 'الثامن' => 8, 'السابعة' => 7, 'السابع' => 7,
        'السادسة' => 6, 'السادس' => 6, 'الخامسة' => 5, 'الخامس' => 5,
        'الرابعة' => 4, 'الرابع' => 4, 'الثالثة' => 3, 'الثالث' => 3,
        'الثانية' => 2, 'الثاني' => 2, 'الأولى' => 1, 'الأول' => 1,
    ];
    foreach ($arabic as $word => $num) {
        if (mb_stripos($title, $word) !== false) return $num;
    }
    return 0;
}