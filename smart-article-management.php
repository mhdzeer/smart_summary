<?php
/**
 * Plugin Name: Smart Article Management (Standalone)
 * Description: (V 1.3.1) - إدارة المقالات، كشف النواقص بدون API عبر المتصفح (إصلاح CORS)، والنشر التلقائي.
 * Version: 1.3.1
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
        .sam-badge-yt { background: #e3f2fd; color: #0d47a1; }
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
    if (isset($_POST['save_sam_settings'])) {
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
                <h1 style="color:#fff; margin:0;">🚀 مدقق ومحرر المقالات الذكي (V 1.3.0)</h1>
                <p style="margin:5px 0 0 0;">إدارة، ترتيب، ومزامنة المقالات مع يوتيوب (بدون API).</p>
            </div>

            <div class="sam-card">
                <h3>⚙️ إعدادات النظام والقوالب:</h3>
                <form method="post">
                    <div style="display:flex; gap:15px; flex-wrap:wrap;">
                        <div style="flex:1; min-width:250px;">
                            <label><b>ID القناة (YouTube Channel ID):</b></label>
                            <input type="text" name="sam_yt_channel_id" value="<?php echo esc_attr($yt_channel); ?>" class="sam-input" placeholder="مثال: UCxxxx...">
                            <div class="sam-variable-hint">🔹 <a href="https://www.youtube.com/account_advanced" target="_blank">اضغط هنا للحصول على ID القناة</a></div>
                        </div>
                        <div style="flex:1; min-width:250px;">
                            <label><b>مفتاح API (Google Cloud):</b></label>
                            <input type="password" name="sam_yt_api_key" value="<?php echo esc_attr($api_key); ?>" class="sam-input" placeholder="AIzaSy...">
                            <div class="sam-variable-hint">🔹 <a href="https://console.cloud.google.com/apis/library/youtube.googleapis.com" target="_blank">1. تفعيل المكتبة</a> | <a href="https://console.cloud.google.com/apis/credentials" target="_blank">2. أنشئ مفتاح API</a></div>
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
                <p style="font-size:12px; color:#666;">اختر التصنيف أولاً، ثم اضغط "فحص" أو استخدم الزر الذكي في يوتيوب.</p>
                <select id="sam_cat_select" class="sam-input" style="max-width:300px;">
                    <option value="">-- اختر التصنيف --</option>
                    <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat->term_id; ?>"><?php echo esc_html($cat->name); ?> (ID: <?php echo $cat->term_id; ?> | <?php echo $cat->count; ?>)</option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="sam_btn_fetch" class="sam-btn sam-btn-primary">فحص المقالات</button>
                <button type="button" id="sam_btn_yt_sync" class="sam-btn sam-btn-warning">🔍 ابحث عبر API (اختياري)</button>
                <span id="sam_loading">⏳ جاري العمل...</span>
            </div>

            <div class="sam-card" style="border-right: 5px solid #f57c00;">
                <h3>⚡ المزامنة بدون API (حل مشكلة Failed to fetch):</h3>
                <p>استخدم هذا الزر من داخل صفحة فيديوهات القناة في يوتيوب لجلب النواقص فوراً.</p>
                
                <?php 
                $saved_token = get_option('sam_access_token');
                if (!$saved_token) {
                    $saved_token = wp_generate_password(24, false);
                    update_option('sam_access_token', $saved_token);
                }
                
                $bookmarklet_code = "javascript:(function(){
                    var videos = [];
                    document.querySelectorAll('ytd-rich-grid-media, ytd-grid-video-renderer, ytd-video-renderer').forEach(function(el){
                        var titleEl = el.querySelector('#video-title');
                        var linkEl = el.querySelector('a#video-title, a#video-title-link, a.ytd-video-renderer, #thumbnail a');
                        if(titleEl && linkEl) {
                            videos.push({title: titleEl.innerText.trim(), url: linkEl.href});
                        }
                    });
                    if(videos.length === 0) return alert('لم يتم العثور على فيديوهات! تأكد أنك في صفحة فيديوهات القناة.');
                    var catId = prompt('أدخل رقم ID التصنيف المستهدف:', '');
                    if(!catId) return;
                    var formData = new FormData();
                    formData.append('action', 'sam_process_browser_data');
                    formData.append('cat_id', catId);
                    formData.append('sam_token', '" . $saved_token . "');
                    formData.append('videos', JSON.stringify(videos));
                    fetch('" . admin_url('admin-ajax.php') . "', {method: 'POST', body: formData, mode: 'cors'})
                    .then(r => r.json())
                    .then(d => {
                        if(d.success) alert('✅ تم إرسال ' + videos.length + ' فيديو. عد الآن للموقع واضغط \"فحص المقالات\".');
                        else alert('❌ فشل الإرسال: ' + (d.data || 'خطأ غير معروف'));
                    }).catch(e => alert('❌ خطأ في الاتصال: ' + e));
                })();";
                ?>
                <div style="background: #fdf6ec; padding: 15px; border-radius: 4px; border: 1px dashed #f57c00;">
                    <p><b>⚠️ هام جداً (تحديث أمان):</b> يرجى <b>حذف الزر القديم</b> من متصفحك وسحب هذا الزر الجديد بدلاً منه:</p>
                    <a href="<?php echo $bookmarklet_code; ?>" style="display:inline-block; padding:8px 15px; background:#f57c00; color:#fff; text-decoration:none; border-radius:4px; font-weight:bold; cursor:move;">مزامنة يوتيوب ذكية (نسخة مطورة) 🚀</a>
                    <p style="margin-top:10px; font-size:12px; color:#d32f2f;">💡 هذا الزر يحتوي الآن على "مفتاح أمان" خاص بموقعك لحل مشكلة الاتصال التي واجهتها.</p>
                </div>
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

            $('#sam_btn_fetch').click(function(){ fetchPosts(false); });
            $('#sam_btn_yt_sync').click(function(){ fetchPosts(true); });

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
                            $('#sam_btn_create_missing').show();
                        }
                    } else { alert(res.data || 'خطأ في جلب البيانات'); }
                });
            }

            $(document).on('click', '.sam-btn-update', function(){
                var row = $(this).closest('tr');
                var post_id = $(this).data('id');
                var new_title = row.find('.sam-new-title').val();
                var btn = $(this);
                btn.attr('disabled', true).text('⏳...');
                $.post(ajaxurl, { action: 'sam_update_title', post_id: post_id, title: new_title }, function(res){
                    if(res.success) { btn.text('✅ تم').css('background', '#4caf50'); }
                    else { alert('فشل التعديل'); btn.attr('disabled', false).text('تعديل'); }
                });
            });

            $(document).on('click', '.sam-btn-create-one', function(){
                var btn = $(this);
                var video = btn.data('video');
                var cat_id = $('#sam_cat_select').val();
                btn.attr('disabled', true).text('⏳ جاري النشر...');
                $.post(ajaxurl, { action: 'sam_create_post', video_url: video.url, title: video.title, cat_id: cat_id }, function(res){
                    if(res.success) { btn.text('✅ تم النشر').css('background', '#2e7d32'); }
                    else { alert('فشل النشر'); btn.attr('disabled', false).text('نشر الآن'); }
                });
            });

            $('#sam_btn_create_missing').click(function(){
                if(!confirm('سيقوم النظام بإنشاء مسودات لجميع الحلقات المفقودة. هل تريد الاستمرار؟')) return;
                $('.sam-btn-create-one').each(function(){
                    if(!$(this).is(':disabled')) $(this).click();
                });
            });

            $('#sam_btn_rename_all').click(function(){
                if(!confirm('تعديل جميع الأسماء المقترحة؟')) return;
                $('.sam-btn-update').each(function(){ if(!$(this).is(':disabled')) $(this).click(); });
            });
        });
        </script>
        <?php
}

function sam_extract_number($title)
{
    $title = mb_convert_encoding($title, 'UTF-8');
    if (preg_match('/(\d+)/', $title, $matches))
        return intval($matches[1]);
    $arabic_numbers = [
        'الثالث عشر' => 13,
        'الثاني عشر' => 12,
        'الحادي عشر' => 11,
        'العاشرة' => 10,
        'العاشر' => 10,
        'عشرة' => 10,
        'التاسعة' => 9,
        'التاسع' => 9,
        'تسعة' => 9,
        'الثامنة' => 8,
        'الثامن' => 8,
        'ثمانية' => 8,
        'السابعة' => 7,
        'السابع' => 7,
        'سبعة' => 7,
        'السادسة' => 6,
        'السادس' => 6,
        'ستة' => 6,
        'الخامسة' => 5,
        'الخامس' => 5,
        'خمسة' => 5,
        'الرابعة' => 4,
        'الرابع' => 4,
        'أربعة' => 4,
        'الثالثة' => 3,
        'الثالث' => 3,
        'ثلاثة' => 3,
        'الثانية' => 2,
        'الثاني' => 2,
        'اثنين' => 2,
        'الأولى' => 1,
        'الأول' => 1,
        'واحد' => 1
    ];
    foreach ($arabic_numbers as $word => $num) {
        if (mb_stripos($title, $word) !== false)
            return $num;
    }
    return 0;
}

add_action('wp_ajax_sam_fetch_posts', 'sam_fetch_posts_callback');
function sam_fetch_posts_callback()
{
    $cat_id = intval($_POST['cat_id']);
    $sync_yt = intval($_POST['sync_yt']);
    $cat_name = get_cat_name($cat_id);
    $posts = get_posts(['category' => $cat_id, 'posts_per_page' => -1, 'post_status' => ['publish', 'draft', 'future', 'pending'], 'orderby' => 'title', 'order' => 'ASC']);

    $final_list = [];
    $recorded_nums = [];
    $template = get_option('sam_title_template', '{cat} - الحلقة {n}');

    foreach ($posts as $p) {
        $num = sam_extract_number($p->post_title);
        if ($num > 0) $recorded_nums[$num] = true;
        $suggested_title = str_replace(['{cat}', '{n}'], [$cat_name, $num], $template);
        if ($num == 0) $suggested_title = $p->post_title;
        
        $final_list[] = [
            'id' => $p->ID,
            'title' => $p->post_title,
            'num' => $num,
            'suggested' => $suggested_title,
            'status_html' => '<span class="sam-badge sam-badge-ok">موجود (' . (get_post_status($p->ID) == 'publish' ? 'منشور' : 'مسودة') . ') ✅</span>',
            'action_html' => '<button type="button" class="sam-btn sam-btn-primary sam-btn-update" data-id="' . $p->ID . '">تعديل</button>',
            'row_style' => ''
        ];
    }

    $yt_missing_count = 0;
    $yt_error = '';
    if ($sync_yt) {
        $api_key = get_option('sam_yt_api_key');
        $channel_id = get_option('sam_yt_channel_id');
        if ($api_key && $channel_id) {
            $transient_name = 'sam_yt_cache_' . $cat_id;
            $data = get_transient($transient_name);

            if ($data === false) {
                $response = wp_remote_get("https://www.googleapis.com/youtube/v3/search?key={$api_key}&channelId={$channel_id}&part=snippet,id&order=date&maxResults=50&type=video&q=" . urlencode($cat_name), [
                    'timeout' => 20, // زيادة وقت الانتظار لتجنب الـ Timeout
                ]);
                if (!is_wp_error($response)) {
                    $data = json_decode(wp_remote_retrieve_body($response), true);
                    if (isset($data['items'])) {
                        set_transient($transient_name, $data, HOUR_IN_SECONDS); // تخزين النتائج لمدة ساعة لتقليل استهلاك الكوتا
                    }
                } else {
                    $yt_error = "خطأ في الاتصال بيوتيوب: " . $response->get_error_message();
                }
            }

            if ($data) {
                if (isset($data['items'])) {
                    foreach ($data['items'] as $vid) {
                        $v_num = sam_extract_number($vid['snippet']['title']);
                        if ($v_num > 0 && !isset($recorded_nums[$v_num])) {
                            $yt_missing_count++;
                            $v_suggested = str_replace(['{cat}', '{n}'], [$cat_name, $v_num], $template);
                            $v_url = "https://www.youtube.com/watch?v=" . $vid['id']['videoId'];
                            $video_data = ['title' => $v_suggested, 'url' => $v_url, 'num' => $v_num];

                            $final_list[] = [
                                'id' => 0,
                                'title' => '🌐 نتيجة يوتيوب: ' . $vid['snippet']['title'],
                                'num' => $v_num,
                                'suggested' => $v_suggested,
                                'status_html' => '<span class="sam-badge sam-badge-yt">متاح في يوتيوب 📺</span>',
                                'action_html' => '<button type="button" class="sam-btn sam-btn-warning sam-btn-create-one" data-video=\'' . esc_attr(json_encode($video_data)) . '\'>نشر الآن</button>',
                                'row_style' => 'background:#e3f2fd;'
                            ];
                            $recorded_nums[$v_num] = 'yt';
                        }
                    }
                } elseif (isset($data['error'])) {
                    $yt_error = "خطأ يوتيوب: " . $data['error']['message'];
                }
            }
        } else {
            $yt_error = "يرجى ضبط مفتاح API و ID القناة أولاً.";
        }
    }

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
                        'status_html' => '<span class="sam-badge sam-badge-missing">مفقود تماماً ⚠️</span>',
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
        $html .= '<td><input type="text" class="sam-input sam-new-title" value="' . esc_attr($item['suggested']) . '" ' . ($item['id'] == 0 ? 'readonly' : '') . '></td>';
        $html .= '<td>' . $item['status_html'] . '</td>';
        $html .= '<td>' . $item['action_html'] . '</td>';
        $html .= '</tr>';
    }

    $summary = "إجمالي المقالات الموجودة: " . count($posts);
    if ($sync_yt) {
        if ($yt_error) $summary .= " | <span style='color:red;'>⚠️ " . $yt_error . "</span>";
        else $summary .= " | تم العثور على " . $yt_missing_count . " حلقة في يوتيوب جاهزة للنشر.";
    }

    wp_send_json_success(['html' => $html, 'summary' => $summary, 'has_yt_missing' => ($yt_missing_count > 0)]);
}

// استقبال البيانات من المتصفح (Bookmarklet)
add_action('wp_ajax_sam_process_browser_data', 'sam_process_browser_data_callback');
add_action('wp_ajax_nopriv_sam_process_browser_data', 'sam_process_browser_data_callback'); // للسماح بالاستقبال من نطاق خارجي (يوتيوب)
function sam_process_browser_data_callback()
{
    // السماح بالطلبات القادمة من يوتيوب (CORS) لحل مشكلة Failed to fetch
    header("Access-Control-Allow-Origin: https://www.youtube.com");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");
    
    // التعامل مع طلب Preflight (OPTIONS)
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        status_header(200);
        exit;
    }

    $token = isset($_POST['sam_token']) ? sanitize_text_field($_POST['sam_token']) : '';
    $saved_token = get_option('sam_access_token');

    // التحقق من رمز الأمان عوضاً عن التحقق من تسجيل الدخول (لأن يوتيوب في نطاق مختلف)
    if (!$saved_token || $token !== $saved_token) {
        wp_send_json_error('رمز الأمان غير صحيح أو منتهي الصلاحية. يرجى إعادة تعيين الزر الذكي.');
    }

    $cat_id = intval($_POST['cat_id']);
    $videos_json = isset($_POST['videos']) ? stripslashes($_POST['videos']) : '';
    $videos = json_decode($videos_json, true);

    if (!$cat_id || empty($videos)) wp_send_json_error('بيانات غير مكتملة أو لم يتم العثور على فيديوهات');

    $cat_name = get_cat_name($cat_id);
    $filtered_items = [];
    
    foreach ($videos as $vid) {
        if (mb_stripos($vid['title'], $cat_name) !== false) {
            $v_id = '';
            if (preg_match('/v=([^&]+)/', $vid['url'], $m)) {
                $v_id = $m[1];
            } elseif (preg_match('/shorts\/([^?]+)/', $vid['url'], $m)) {
                $v_id = $m[1];
            }

            if (!$v_id) continue;

            $filtered_items[] = [
                'id' => ['videoId' => $v_id],
                'snippet' => ['title' => $vid['title']]
            ];
        }
    }

    if (empty($filtered_items)) wp_send_json_error('لم يتم العثور على فيديوهات في الصفحة تحتوي على كلمة: ' . $cat_name);

    set_transient('sam_yt_cache_' . $cat_id, ['items' => $filtered_items], HOUR_IN_SECONDS);
    wp_send_json_success();
}

add_action('wp_ajax_sam_create_post', 'sam_create_post_callback');
function sam_create_post_callback()
{
    $video_url = esc_url_raw($_POST['video_url']);
    $title = sanitize_text_field($_POST['title']);
    $cat_id = intval($_POST['cat_id']);
    if ($video_url && $title) {
        // بناء محتوى المقال كما طلب المستخدم (العنوان + الرابط)
        $content = "<!-- " . esc_html($title) . " -->\n";
        $content .= "<h3>" . esc_html($title) . "</h3>\n";
        $content .= "<p>شاهد الحلقة مباشرة من هنا: <a href='" . esc_url($video_url) . "' target='_blank'>" . esc_url($video_url) . "</a></p>\n";
        $content .= "\n" . esc_url($video_url) . "\n"; // تضمين الرابط ليقوم وردبريس بتحويله لفيديو تلقائياً
        $content .= "\n\n(تمت إضافة هذا المقال تلقائياً عبر نظام إدارة المقالات الذكي)";

        $new_post = [
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'draft',
            'post_category' => [$cat_id]
        ];
        if (wp_insert_post($new_post))
            wp_send_json_success();
    }
    wp_send_json_error();
}

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