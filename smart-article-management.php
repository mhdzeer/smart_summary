<?php
/**
 * Plugin Name: Smart Article Management (Standalone)
 * Description: (V 1.2.0) - نظام إدارة المقالات، كشف النواقص، الربط مع يوتيوب، والنشر التلقائي.
 * Version: 1.2.0
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
        .sam-badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .sam-badge-missing { background: #ffebee; color: #c62828; }
        .sam-badge-ok { background: #e8f5e9; color: #2e7d32; }
        .sam-badge-yt { background: #fff3e0; color: #e65100; }
        .sam-btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-family: "Cairo"; margin-left: 5px; }
        .sam-btn-primary { background: #1a73e8; color: #fff; }
        .sam-btn-success { background: #2e7d32; color: #fff; }
        .sam-btn-warning { background: #f57c00; color: #fff; }
        .sam-input { padding: 8px; border: 1px solid #ccc; border-radius: 4px; width: 100%; box-sizing: border-box; }
        .sam-variable-hint { font-size: 11px; color: #666; margin-top: 4px; }
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
    if(isset($_POST['save_sam_settings'])) {
        update_option('sam_yt_channel_id', sanitize_text_field($_POST['sam_yt_channel_id']));
        update_option('sam_yt_api_key', sanitize_text_field($_POST['sam_yt_api_key']));
        update_option('sam_title_template', sanitize_text_field($_POST['sam_title_template']));
        echo '<div class="updated"><p>✅ تم حفظ الإعدادات!</p></div>';
    }

    $categories = get_categories();
    $yt_channel = get_option('sam_yt_channel_id', '');
    $api_key = get_option('sam_yt_api_key', '');
    $template = get_option('sam_title_template', '{cat} - الحلقة {n}');
    ?>
    <div class="wrap sam-wrap">
        <div class="sam-header">
            <h1 style="color:#fff; margin:0;">🚀 مدقق ومحرر المقالات الذكي (V 1.2)</h1>
            <p style="margin:5px 0 0 0;">إدارة، ترتيب، ومزامنة المقالات مع يوتيوب تلقائياً.</p>
        </div>

        <!-- إعدادات المزامنة -->
        <div class="sam-card">
            <h3>⚙️ إعدادات النظام والقوالب:</h3>
            <form method="post">
                <div style="display:flex; gap:15px; flex-wrap:wrap;">
                    <div style="flex:1; min-width:250px;">
                        <label><b>ID القناة (YouTube Channel ID):</b></label>
                        <input type="text" name="sam_yt_channel_id" value="<?php echo esc_attr($yt_channel); ?>" class="sam-input" placeholder="مثال: UCxxxx...">
                    </div>
                    <div style="flex:1; min-width:250px;">
                        <label><b>مفتاح API (Google Cloud):</b></label>
                        <input type="password" name="sam_yt_api_key" value="<?php echo esc_attr($api_key); ?>" class="sam-input" placeholder="اختياري للبحث المتقدم">
                    </div>
                    <div style="flex:1; min-width:250px;">
                        <label><b>قالب الأسماء الموحد:</b></label>
                        <input type="text" name="sam_title_template" value="<?php echo esc_attr($template); ?>" class="sam-input">
                        <div class="sam-variable-hint">{cat} = اسم التصنيف | {n} = رقم الحلقة</div>
                    </div>
                </div>
                <input type="submit" name="save_sam_settings" class="sam-btn sam-btn-primary" style="margin-top:15px;" value="💾 حفظ الإعدادات">
            </form>
        </div>

        <div class="sam-card">
            <h3>🔍 اختر التصنيف للفحص والمزامنة:</h3>
            <select id="sam_cat_select" class="sam-input" style="max-width:300px;">
                <option value="">-- اختر التصنيف --</option>
                <?php foreach($categories as $cat): ?>
                    <option value="<?php echo $cat->term_id; ?>"><?php echo esc_html($cat->name); ?> (<?php echo $cat->count; ?>)</option>
                <?php endforeach; ?>
            </select>
            <button type="button" id="sam_btn_fetch" class="sam-btn sam-btn-primary">فحص المقالات</button>
            <button type="button" id="sam_btn_yt_sync" class="sam-btn sam-btn-warning">🔍 ابحث في يوتيوب عن النواقص</button>
            <span id="sam_loading">⏳ جاري العمل...</span>
        </div>

        <div id="sam_result_area" style="display:none;">
            <div class="sam-card">
                <h3>📊 نتائج الفحص والمزامنة:</h3>
                <div id="sam_summary" style="margin-bottom:15px; font-weight:bold; font-size:16px;"></div>
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
                    <button type="button" id="sam_btn_rename_all" class="sam-btn sam-btn-success">✅ اعتماد جميع تعديلات الأسماء</button>
                    <button type="button" id="sam_btn_create_missing" class="sam-btn sam-btn-primary" style="display:none;">🏗️ إنشاء جميع المقالات المفقودة فوراً</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($){
        var yt_missing_videos = [];

        $('#sam_btn_fetch').click(function(){
            fetchPosts(false);
        });

        $('#sam_btn_yt_sync').click(function(){
            fetchPosts(true);
        });

        function fetchPosts(syncYt) {
            var cat_id = $('#sam_cat_select').val();
            if(!cat_id) return alert('الرجاء اختيار تصنيف أولاً');

            $('#sam_loading').show();
            $('#sam_result_area').hide();
            $('#sam_btn_create_missing').hide();

            $.post(ajaxurl, {
                action: 'sam_fetch_posts',
                cat_id: cat_id,
                sync_yt: syncYt ? 1 : 0
            }, function(res){
                $('#sam_loading').hide();
                if(res.success) {
                    $('#sam_post_list').html(res.data.html);
                    $('#sam_summary').html(res.data.summary);
                    $('#sam_result_area').show();
                    if(res.data.has_yt_missing) {
                        yt_missing_videos = res.data.yt_missing_videos;
                        $('#sam_btn_create_missing').show();
                    }
                } else {
                    alert(res.data || 'خطأ في جلب البيانات');
                }
            });
        }

        $(document).on('click', '.sam-btn-update', function(){
            var row = $(this).closest('tr');
            var post_id = $(this).data('id');
            var new_title = row.find('.sam-new-title').val();
            var btn = $(this);

            btn.attr('disabled', true).text('⏳...');
            $.post(ajaxurl, {
                action: 'sam_update_title',
                post_id: post_id,
                title: new_title
            }, function(res){
                if(res.success) {
                    btn.text('✅ تم').css('background', '#4caf50');
                } else {
                    alert('فشل التعديل');
                    btn.attr('disabled', false).text('تعديل');
                }
            });
        });

        $(document).on('click', '.sam-btn-create-one', function(){
            var btn = $(this);
            var video = btn.data('video');
            var cat_id = $('#sam_cat_select').val();
            
            btn.attr('disabled', true).text('⏳ جاري النشر...');
            $.post(ajaxurl, {
                action: 'sam_create_post',
                video_url: video.url,
                title: video.title,
                cat_id: cat_id
            }, function(res){
                if(res.success) {
                    btn.text('✅ تم النشر').css('background', '#2e7d32');
                } else {
                    alert('فشل النشر');
                    btn.attr('disabled', false).text('نشر الآن');
                }
            });
        });

        $('#sam_btn_create_missing').click(function(){
            if(!confirm('سيقوم النظام بإنشاء مسودات لجميع الحلقات المفقودة من يوتيوب. هل تريد الاستمرار؟')) return;
            $('.sam-btn-create-one').each(function(){
                if(!$(this).is(':disabled')) $(this).click();
            });
        });

        $('#sam_btn_rename_all').click(function(){
            if(!confirm('هل أنت متأكد من رغبتك في تعديل جميع الأسماء المقترحة؟')) return;
            $('.sam-btn-update').each(function(){
                if(!$(this).is(':disabled')) $(this).click();
            });
        });
    });
    </script>
    <?php
}

// دالة ذكية لاستخراج الرقم تدعم العربية
function sam_extract_number($title) {
    $title = mb_convert_encoding($title, 'UTF-8');
    if (preg_match('/(\d+)/', $title, $matches)) return intval($matches[1]);

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
        if (mb_stripos($title, $word) !== false) return $num;
    }
    return 0;
}

// AJAX: جلب المقالات ومزامنتها
add_action('wp_ajax_sam_fetch_posts', 'sam_fetch_posts_callback');
function sam_fetch_posts_callback() {
    $cat_id = intval($_POST['cat_id']);
    $sync_yt = intval($_POST['sync_yt']);
    $cat_name = get_cat_name($cat_id);
    
    $posts = get_posts(['category' => $cat_id, 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);

    $html = '';
    $post_items = [];
    $existing_numbers = [];
    $template = get_option('sam_title_template', '{cat} - الحلقة {n}');

    foreach($posts as $p) {
        $num = sam_extract_number($p->post_title);
        if($num > 0) $existing_numbers[$num] = true;
        
        $suggested_title = str_replace(['{cat}', '{n}'], [$cat_name, $num], $template);
        if($num == 0) $suggested_title = $p->post_title;

        $post_items[] = [ 'id' => $p->ID, 'title' => $p->post_title, 'num' => $num, 'suggested' => $suggested_title ];
    }

    usort($post_items, function($a, $b) { return $a['num'] - $b['num']; });

    foreach($post_items as $item) {
        $html .= '<tr>';
        $html .= '<td>' . ($item['num'] > 0 ? $item['num'] : '-') . '</td>';
        $html .= '<td>' . esc_html($item['title']) . '</td>';
        $html .= '<td><input type="text" class="sam-input sam-new-title" value="' . esc_attr($item['suggested']) . '"></td>';
        $html .= '<td><span class="sam-badge sam-badge-ok">موجود ✅</span></td>';
        $html .= '<td><button type="button" class="sam-btn sam-btn-primary sam-btn-update" data-id="' . $item['id'] . '">تعديل</button></td>';
        $html .= '</tr>';
    }

    $yt_missing_videos = [];
    if($sync_yt) {
        $api_key = get_option('sam_yt_api_key');
        $channel_id = get_option('sam_yt_channel_id');
        
        if(!$api_key || !$channel_id) {
            wp_send_json_error('يرجى ضبط API Key و ID القناة في الإعدادات أولاً!');
        }

        // جلب آخر فيديوهات من يوتيوب
        $response = wp_remote_get("https://www.googleapis.com/youtube/v3/search?key={$api_key}&channelId={$channel_id}&part=snippet,id&order=date&maxResults=50&type=video&q=" . urlencode($cat_name));
        
        if(!is_wp_error($response)) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            if(isset($data['items'])) {
                foreach($data['items'] as $vid) {
                    $v_title = $vid['snippet']['title'];
                    $v_num = sam_extract_number($v_title);
                    if($v_num > 0 && !isset($existing_numbers[$v_num])) {
                        $v_url = "https://www.youtube.com/watch?v=" . $vid['id']['videoId'];
                        $v_suggested = str_replace(['{cat}', '{n}'], [$cat_name, $v_num], $template);
                        
                        $yt_missing_videos[] = [ 'title' => $v_suggested, 'url' => $v_url, 'num' => $v_num ];
                        
                        $html .= '<tr style="background:#fffaf0;">';
                        $html .= '<td>' . $v_num . '</td>';
                        $html .= '<td style="color:#e65100;">🌐 يوتيوب: ' . esc_html($v_title) . '</td>';
                        $html .= '<td>' . $v_suggested . '</td>';
                        $html .= '<td><span class="sam-badge sam-badge-yt">موجود في يوتيوب فقط</span></td>';
                        $html .= '<td><button type="button" class="sam-btn sam-btn-warning sam-btn-create-one" data-video=\'' . json_encode($yt_missing_videos[count($yt_missing_videos)-1]) . '\'>نشر الآن</button></td>';
                        $html .= '</tr>';
                    }
                }
            }
        }
    }

    $summary = "إجمالي المقالات: " . count($posts);
    if($sync_yt) $summary .= " | وجدنا " . count($yt_missing_videos) . " فيديو في يوتيوب غير منشورين هنا.";

    wp_send_json_success([
        'html' => $html, 
        'summary' => $summary, 
        'has_yt_missing' => !empty($yt_missing_videos),
        'yt_missing_videos' => $yt_missing_videos
    ]);
}

// AJAX: إنشاء مقال جديد
add_action('wp_ajax_sam_create_post', 'sam_create_post_callback');
function sam_create_post_callback() {
    $video_url = esc_url_raw($_POST['video_url']);
    $title = sanitize_text_field($_POST['title']);
    $cat_id = intval($_POST['cat_id']);

    if($video_url && $title) {
        $content = "رابط الفيديو: " . $video_url . "\n\n(سيتم تلخيص هذا الفيديو تلقائياً بواسطة الإضافة الأخرى)";
        $new_post = [
            'post_title'    => $title,
            'post_content'  => $content,
            'post_status'   => 'draft',
            'post_category' => [$cat_id]
        ];
        $id = wp_insert_post($new_post);
        if($id) wp_send_json_success();
    }
    wp_send_json_error();
}

// AJAX: تحديث عنوان المقال
add_action('wp_ajax_sam_update_title', 'sam_update_title_callback');
function sam_update_title_callback() {
    $post_id = intval($_POST['post_id']);
    $new_title = sanitize_text_field($_POST['title']);
    if($post_id && $new_title) {
        wp_update_post(['ID' => $post_id, 'post_title' => $new_title]);
        wp_send_json_success();
    }
    wp_send_json_error();
}
