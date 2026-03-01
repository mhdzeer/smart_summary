<?php
/**
 * Plugin Name: Smart Arabic GPT Video Summarizer
 * Description: (V 6.0) - نظام القوالب الذكية والنشر الفاخر.
 * Version: 6.0
 * Author: Abu Taher
 */

if (!defined('ABSPATH')) exit;

// إضافة الخطوط العربية الفاخرة
add_action('admin_head', 'svs_premium_styles');
function svs_premium_styles() {
    echo '<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&family=Amiri:wght@400;700&display=swap" rel="stylesheet">
    <style>
        .svs-print-mode { 
            font-family: "Amiri", serif; 
            line-height: 1.9; 
            font-size: 20px; 
            color: #1a1a1a; 
            background: #fff; 
            padding: 40px; 
            border: 2px solid #333; 
            border-radius: 4px;
            box-shadow: none;
            max-width: 900px;
            margin: 30px auto;
            direction: rtl;
            position: relative;
        }
        .svs-print-mode h1, .svs-print-mode h2 { font-family: "Cairo", sans-serif; color: #000; text-align: center; border-bottom: 3px double #333; padding-bottom: 15px; margin-bottom: 30px; }
        .svs-print-mode h3 { font-family: "Cairo", sans-serif; color: #1a73e8; margin-top: 35px; border-right: 5px solid #1a73e8; padding-right: 15px; font-size: 24px; }
        .svs-print-mode p { margin-bottom: 20px; text-align: justify; }
        .svs-print-mode .list-item { margin-bottom: 15px; display: block; padding-right: 10px; }
        .svs-print-mode strong { color: #000; font-weight: bold; }
        
        @media print {
            body * { visibility: hidden; }
            .svs-print-mode, .svs-print-mode * { visibility: visible; }
            .svs-print-mode { position: absolute; left: 0; top: 0; width: 100%; border: none; }
        }
    </style>';
}

// إضافة صفحة الإعدادات في لوحة التحكم
add_action('admin_menu', 'svs_settings_menu');
function svs_settings_menu() {
    add_menu_page('قوالب التلخيص', 'قوالب التلخيص', 'manage_options', 'svs-settings', 'svs_settings_page', 'dashicons-media-document');
}

// صفحة الإعدادات
function svs_settings_page() {
    if (isset($_POST['save_svs_templates'])) {
        $templates = [];
        if (isset($_POST['template_names'])) {
            foreach ($_POST['template_names'] as $i => $name) {
                if (!empty($name)) {
                    $templates[] = [
                        'name' => sanitize_text_field($name),
                        'prompt' => wp_kses_post($_POST['template_prompts'][$i])
                    ];
                }
            }
        }
        update_option('svs_prompts_templates', $templates);
        echo '<div class="updated"><p>✅ تم حفظ القوالب بنجاح!</p></div>';
    }

    $default_prompt = "أنت كاتب مقالات ديني واجتماعي محترف. قم بكتابة مقال تلخيصي عميق وبليغ باللغة العربية الفصحى لهذا الفيديو:\n[URL]\n\nتعليمات صارمة (ممنوع كتابة هذه التعليمات في ردك):\n1. ابدأ مباشرة بالمقال بمقدمة روحانية وبليغة جداً.\n2. استخدم عناوين h3 جذابة للنقاط الرئيسية.\n3. اجعل التفاصيل على شكل قائمة مرقمة (1. 2. 3. 4.) لتسهيل القراءة والطباعة.\n4. ممنوع ذكر اسم الشيخ أو القناة أو كلمة 'إليك' أو 'رابط'.\n5. اجعل النص غنياً بالمعاني ليكون مناسباً للنشر كـ مقال صحفي فاخر.\nتنبيه: لا تكرر سؤالي ولا تكتب الهيكل المطلوب، ابدأ بالمقال فوراً بـ 'بسم الله' أو بمقدمة بليغة.";

    $templates = get_option('svs_prompts_templates', [
        ['name' => 'تلخيص ديني فاخر', 'prompt' => $default_prompt]
    ]);
    ?>
    <div class="wrap" style="direction:rtl; font-family:'Cairo', sans-serif;">
        <h1>⚙️ إدارة قوالب التلخيص (V 6.0)</h1>
        <form method="post">
            <div id="templates_list">
                <?php foreach ($templates as $idx => $t): ?>
                    <div style="background:#fff; padding:20px; border:1px solid #ccc; margin-bottom:15px; border-radius:8px; position:relative;">
                        <label><b>اسم القالب:</b></label><br>
                        <input type="text" name="template_names[]" value="<?php echo esc_attr($t['name']); ?>" style="width:100%; margin:10px 0;"><br>
                        <label><b>نص التعليمات (استخدم [URL] لمكان الرابط):</b></label><br>
                        <textarea name="template_prompts[]" style="width:100%; height:150px;"><?php echo esc_textarea($t['prompt']); ?></textarea>
                        <button type="button" onclick="this.parentElement.remove()" style="color:red; cursor:pointer; background:none; border:none; position:absolute; left:10px; top:10px;">❌ حذف</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" onclick="addTemplate()" class="button" style="margin-bottom:20px;">➕ إضافة قالب جديد</button><br>
            <input type="submit" name="save_svs_templates" class="button button-primary" value="💾 حفظ كافة القوالب">
        </form>
    </div>
    <script>
    function addTemplate() {
        var html = '<div style="background:#fff; padding:20px; border:1px solid #ccc; margin-bottom:15px; border-radius:8px; position:relative;">' +
                   '<label><b>اسم القالب الجديد:</b></label><br>' +
                   '<input type="text" name="template_names[]" style="width:100%; margin:10px 0;"><br>' +
                   '<label><b>نص التعليمات:</b></label><br>' +
                   '<textarea name="template_prompts[]" style="width:100%; height:150px;"></textarea>' +
                   '<button type="button" onclick="this.parentElement.remove()" style="color:red; cursor:pointer; background:none; border:none; position:absolute; left:10px; top:10px;">❌ حذف</button></div>';
        jQuery('#templates_list').append(html);
    }
    </script>
    <?php
}

add_action('add_meta_boxes', 'svs_add_v4_metabox');
function svs_add_v4_metabox() {
    foreach (['post', 'page'] as $s) add_meta_box('svs_mb', '💠 مقال النشر والطباعة الاحترافي (V 6.0)', 'svs_v4_html', $s, 'normal', 'high');
}

function svs_v4_html($post) { 
    $templates = get_option('svs_prompts_templates', []);
    ?>
    <div id="svs_container" style="direction:rtl; font-family:sans-serif; padding:15px; background:#f4f4f4; border-radius:10px; border:1px solid #ccc;">
        
        <div id="svs_conn_status" style="margin-bottom:12px; padding:6px 12px; background:#fff; border:1px solid #ddd; border-radius:20px; font-size:12px; display:inline-block;">
            ⏳ جاري فحص الاتصال...
        </div>

        <div style="margin-bottom:12px;">
            <label style="font-weight:bold; display:block; margin-bottom:5px;">رابط الفيديو المكتشف:</label>
            <input type="text" id="svs_url" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;" placeholder="سيظهر الرابط هنا تلقائياً...">
        </div>

        <div style="margin-bottom:15px;">
            <label style="font-weight:bold; display:block; margin-bottom:5px;">اختر قالب التلخيص:</label>
            <select id="svs_template_select" style="width:100%; padding:10px; border-radius:4px; border:1px solid #ccc;">
                <?php if ($templates): ?>
                    <?php foreach ($templates as $t): ?>
                        <option value="<?php echo esc_attr($t['prompt']); ?>"><?php echo esc_html($t['name']); ?></option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="">⚠️ لا توجد قوالب! أضف قوالب من صفحة الإعدادات.</option>
                <?php endif; ?>
            </select>
            <p style="font-size:11px; margin-top:5px;"><a href="<?php echo admin_url('admin.php?page=svs-settings'); ?>" target="_blank">⚙️ إدارة القوالب</a></p>
        </div>
        
        <button type="button" id="svs_start_btn" style="width:100%; padding:15px; background:#1a73e8; color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:bold; font-size:16px;">
            🚀 ابدأ التلخيص آلياً
        </button>

        <div id="svs_status_log" style="margin-top:10px; font-size:13px; color:#1a73e8;">💡 اختر القالب واضغط ابدأ...</div>

        <div style="margin-top:20px;" id="svs_result_box">
            <label style="font-weight:bold; display:block; margin-bottom:8px;">مراجعة النص وتحريره:</label>
            <textarea id="svs_raw_result" style="width:100%; height:250px; padding:15px; border:1px solid #bbb; border-radius:4px; font-family: 'Amiri', serif; font-size:18px; line-height:1.6; background:#fff;"></textarea>
            <button type="button" id="svs_btn_format_premium" style="width:100%; padding:18px; background:#2e7d32; color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:bold; font-size:18px; margin-top:15px;">
                ✨ تنسيق ونشر المقال الفاخر
            </button>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($){
        // كشف الرابط التلقائي
        setInterval(function(){
            var c = "";
            try { if (window.wp?.data?.select("core/editor")) c = wp.data.select("core/editor").getEditedPostContent(); } catch(e) {}
            if(!c && window.tinyMCE?.activeEditor) c = tinyMCE.activeEditor.getContent();
            var m = c.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/|shorts\/)([a-zA-Z0-9_-]{11})/);
            if (m) { var url = "https://www.youtube.com/watch?v=" + m[1]; if($('#svs_url').val() != url) $('#svs_url').val(url); }
        }, 3000);

        setInterval(function(){ window.dispatchEvent(new CustomEvent('SVS_CHECK_CONNECTION')); }, 2000);
        window.addEventListener('SVS_CONNECTION_OK', function(){ 
            $('#svs_conn_status').html('✅ إضافة المتصفح: متصلة وجاهزة').css({'color':'#2e7d32','background':'#e8f5e9'}); 
        });

        $('#svs_start_btn').click(function(){
            var url = $('#svs_url').val().trim();
            var promptTemplate = $('#svs_template_select').val();
            if(!url) return alert("❌ لم يتم العثور على رابط فيديو!");
            if(!promptTemplate) return alert("❌ يرجى اختيار قالب تلخيص!");
            
            // تبديل [URL] بالرابط الحقيقي
            var finalPrompt = promptTemplate.replace('[URL]', url);

            $(this).attr('disabled', true).text('⏳ الروبوت يكتب الآن...');
            window.dispatchEvent(new CustomEvent('SVS_START_AUTO', { detail: { prompt: finalPrompt } }));
        });

        window.addEventListener('SVS_LOG', function(e){ $('#svs_status_log').html(e.detail.msg); });

        $('#svs_btn_format_premium').click(function(){
            var text = $('#svs_raw_result').val().trim();
            if(!text) return;

            // تنظيف
            text = text.split(/الهيكل المطلوب|ابدأ بمقدمة|التعليمات الصارمة|رابط الفيديو|alkarbabadi/i)[0].trim();

            function advancedMarkdownToHtml(md) {
                let html = md.trim();
                html = html.replace(/^### (.*?)$/gm, '<h3>$1</h3>');
                html = html.replace(/^## (.*?)$/gm, '<h2>$1</h2>');
                html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                html = html.replace(/^(\d+[\.\)]\s+.*?)$/gm, '\n<p class="list-item"><strong>$1</strong></p>\n');
                html = html.replace(/^[\*\-]\s+(.*?)$/gm, '\n<p class="list-item">• $1</p>\n');
                let lines = html.split('\n');
                html = lines.map(line => {
                    line = line.trim();
                    if (!line) return "";
                    if (line.startsWith('<h') || line.startsWith('<p')) return line;
                    return '<p>' + line + '</p>';
                }).join('');
                return html;
            }

            let finalOutput = '<div class="svs-print-mode">' + advancedMarkdownToHtml(text) + '</div>';
            try {
                const editor = wp.data.dispatch('core/block-editor') || wp.data.dispatch('core/editor');
                if (editor && editor.insertBlocks) {
                    editor.insertBlocks(wp.blocks.createBlock('core/freeform', { content: finalOutput }));
                }
            } catch(e) {}
            if (window.tinyMCE?.activeEditor) {
                window.tinyMCE.activeEditor.setContent(window.tinyMCE.activeEditor.getContent() + finalOutput);
            }
            alert('✅ تم التنسيق والنشر بالهيئة الجديدة!');
        });
    });
    </script>
    <?php
}
