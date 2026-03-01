<?php
/**
 * Plugin Name: Smart Arabic GPT Video Summarizer
 * Description: (V 4.9) - نسخة النشر الفاخر والطباعة الاحترافية.
 * Version: 4.9
 * Author: Abu Taher
 */

if (!defined('ABSPATH')) exit;

// إضافة الخطوط العربية الفاخرة
add_action('admin_head', 'svs_premium_styles');
function svs_premium_styles() {
    echo '<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&family=Amiri&display=swap" rel="stylesheet">
    <style>
        .svs-print-mode { 
            font-family: "Cairo", sans-serif; 
            line-height: 1.8; 
            font-size: 18px; 
            color: #2c3e50; 
            background: #fff; 
            padding: 30px; 
            border: 1px solid #eaeaea; 
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            max-width: 850px;
            margin: 20px auto;
            direction: rtl;
        }
        .svs-print-mode h3 { color: #1a73e8; border-bottom: 2px solid #e3f2fd; padding-bottom: 8px; margin-top: 25px; font-weight: 700; }
        .svs-print-mode ul, .svs-print-mode ol { padding-right: 25px; margin: 20px 0; }
        .svs-print-mode li { margin-bottom: 12px; }
        .svs-print-mode strong { color: #d32f2f; }
    </style>';
}

add_action('add_meta_boxes', 'svs_add_v4_metabox');
function svs_add_v4_metabox() {
    foreach (['post', 'page'] as $s) add_meta_box('svs_mb', '💠 مقال تلخيصي احترافي (V 4.9)', 'svs_v4_html', $s, 'normal', 'high');
}

function svs_v4_html($post) { ?>
    <div id="svs_container" style="direction:rtl; font-family:sans-serif; padding:15px; background:#f9f9f9; border-radius:10px; border:1px solid #ddd;">
        
        <div id="svs_conn_status" style="margin-bottom:12px; padding:6px 12px; background:#eee; border-radius:20px; font-size:12px; display:inline-block;">
            ⏳ جاري فحص اتصال الإضافة...
        </div>

        <div style="margin-bottom:12px;">
            <label style="display:block; margin-bottom:5px; font-weight:bold;">رابط الفيديو المكتشف:</label>
            <input type="text" id="svs_url" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;" placeholder="سيظهر الرابط هنا تلقائياً...">
        </div>
        
        <button type="button" id="svs_start_btn" style="width:100%; padding:15px; background:#1a73e8; color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:bold; font-size:16px;">
            🚀 ابدأ التلخيص وطباعة المقال آلياً
        </button>

        <div id="svs_status_log" style="margin-top:10px; font-size:13px; color:#1a73e8; background:#e8f4fd; padding:10px; border-radius:5px;">💡 بانتظار بدء العملية...</div>

        <div style="margin-top:20px;" id="svs_result_box">
            <textarea id="svs_raw_result" style="width:100%; height:100px; display:none;"></textarea>
            <button type="button" id="svs_btn_format_premium" style="width:100%; padding:15px; background:#2e7d32; color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:bold; font-size:16px; box-shadow:0 4px 10px rgba(0,0,0,0.1);">
                ✨ تنسيق ونشر "مقال فاخر" للطباعة
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
            if(!c) c = $('body').text(); // البحث في الصفحة كحل أخير

            var m = c.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/|shorts\/)([a-zA-Z0-9_-]{11})/);
            if (m) {
                var url = "https://www.youtube.com/watch?v=" + m[1];
                if($('#svs_url').val() != url) $('#svs_url').val(url);
            }
        }, 3000);

        setInterval(function(){ window.dispatchEvent(new CustomEvent('SVS_CHECK_CONNECTION')); }, 2000);
        window.addEventListener('SVS_CONNECTION_OK', function(){ 
            $('#svs_conn_status').html('✅ إضافة المتصفح: متصلة وجاهزة').css({'color':'#2e7d32','background':'#e8f5e9'}); 
        });

        $('#svs_start_btn').click(function(){
            var url = $('#svs_url').val().trim();
            if(!url) {
                alert("❌ لم يتم العثور علىابط فيديو يوتيوب. تأكد من وجود الرابط بداخل المقال.");
                return;
            }
            $(this).attr('disabled', true).text('⏳ الروبوت يكتب الآن...');
            $('#svs_status_log').html('⏳ تم إرسال الطلب لـ Gemini... يرجى الانتظار.');
            window.dispatchEvent(new CustomEvent('SVS_START_AUTO', { detail: { url: url } }));
        });

        window.addEventListener('SVS_LOG', function(e){ 
            $('#svs_status_log').html(e.detail.msg);
            if(e.detail.done) {
                $('#svs_start_btn').attr('disabled', false).text('🚀 ابدأ التلخيص مجدداً');
            }
        });

        $('#svs_btn_format_premium').click(function(){
            var text = $('#svs_raw_result').val().trim();
            if(!text) return;

            // تنظيف
            text = text.replace(/رابط الفيديو:.*$/gis, '').replace(/alkarbabadi\.net.*$/gis, '').trim();

            let html = text;
            html = html.replace(/^### (.*?)$/gm, '<h3>$1</h3>');
            html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            html = html.replace(/^\d+\.\s+(.*?)$/gm, '<li>$1</li>');
            html = html.replace(/(<li>.*?<\/li>)+/gs, function(match){ return '<ol>' + match + '</ol>'; });
            html = html.replace(/^\*\s+(.*?)$/gm, '<li>$1</li>');
            html = html.replace(/(<li>.*?<\/li>)+/gs, function(match){ return '<ul>' + match + '</ul>'; });
            html = html.replace(/\n\n/g, '</p><p>');

            let finalOutput = '<div class="svs-print-mode">' + html + '</div>';

            let success = false;
            try {
                const editor = wp.data.dispatch('core/block-editor') || wp.data.dispatch('core/editor');
                const select = wp.data.select('core/block-editor') || wp.data.select('core/editor');
                if (editor && editor.insertBlocks) {
                    editor.insertBlocks(wp.blocks.createBlock('core/freeform', { content: finalOutput }), select.getBlockCount());
                    success = true;
                }
            } catch(e) {}

            if (!success && window.tinyMCE?.activeEditor) {
                window.tinyMCE.activeEditor.setContent(window.tinyMCE.activeEditor.getContent() + finalOutput);
                success = true;
            }

            if(success) alert('✅ تم النشر بتنسيق فاخر!');
        });
    });
    </script>
    <?php
}
