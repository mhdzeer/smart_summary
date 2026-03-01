<?php
/**
 * Plugin Name: Smart Arabic GPT Video Summarizer
 * Description: (V 4.2) - نسخة الأتمتة الكاملة (مع التنسيق المباشر والسريع).
 * Version: 4.2
 * Author: Abu Taher
 * Text Domain: smart-video-summarizer
 */

if (!defined('ABSPATH')) exit;

add_action('add_meta_boxes', 'svs_add_v4_metabox');
function svs_add_v4_metabox() {
    foreach (['post', 'page'] as $s) add_meta_box('svs_mb', '⚡ تلخيص الفيديو آلياً (V 4.2)', 'svs_v4_html', $s, 'normal', 'high');
}

function svs_v4_html($post) { ?>
    <div id="svs_container" style="direction:rtl; font-family:'Segoe UI', Tahoma, sans-serif; padding:15px; background:#f0f7ff; border:2px solid #1a73e8; border-radius:10px; box-shadow:0 4px 15px rgba(0,0,0,0.05);">
        <h3 style="margin:0 0 10px; color:#0d47a1;">🤖 نظام الأتمتة الذكي (صامت)</h3>
        
        <div style="margin-bottom:12px;">
            <label style="font-weight:bold; display:block; margin-bottom:5px;">رابط الفيديو المكتشف:</label>
            <input type="text" id="svs_url" style="width:100%; padding:10px; border:1px solid #bbdefb; border-radius:4px; background:#fff;" placeholder="يتم الكشف تلقائياً...">
        </div>
        
        <button type="button" id="svs_start_btn" style="width:100%; padding:15px; background:#1a73e8; color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:bold; font-size:16px; box-shadow:0 4px 10px rgba(26,115,232,0.3); transition:0.2s;">
            🚀 ابدأ التلخيص (خلفية صامتة)
        </button>

        <div id="svs_status_log" style="margin-top:12px; font-size:13px; color:#0d47a1; background:#e3f2fd; padding:12px; border-radius:6px; border-right:5px solid #1a73e8; line-height:1.6;">
            💡 تأكد من تثبيت إضافة المتصفح، ثم اضغط الزر أعلاه.
        </div>

        <div id="svs_manual_box" style="margin-top:20px; border-top:1px solid #bbdefb; padding-top:15px;">
            <label style="font-weight:bold; display:block; margin-bottom:5px;">النتيجة الخام (Gemini):</label>
            <textarea id="svs_raw_result" style="width:100%; height:120px; padding:10px; border:1px solid #ccc; border-radius:4px; font-size:13px;" placeholder="ستظهر النتيجة هنا تلقائياً..."></textarea>
            
            <button type="button" id="svs_btn_format_direct" style="width:100%; padding:14px; background:#2e7d32; color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:bold; font-size:15px; margin-top:10px; box-shadow:0 4px 10px rgba(46,125,50,0.2);">
                ✨ تنسيق وإدراج في المقال فوراً
            </button>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($){
        // كشف تلقائي للرابط
        setInterval(function(){
            var content = "";
            try { if (window.wp?.data?.select('core/editor')) content = wp.data.select('core/editor').getEditedPostContent(); } catch(e) {}
            if (!content && window.tinyMCE?.activeEditor) content = tinyMCE.activeEditor.getContent();
            var m = content.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
            if (m) { var u = "https://www.youtube.com/watch?v="+m[1]; if($('#svs_url').val()!==u) $('#svs_url').val(u); }
        }, 3000);

        // إرسال الإشارة للإضافة
        $('#svs_start_btn').click(function(){
            var url = $('#svs_url').val();
            if(!url) { alert('لم يتم العثور على رابط يوتيوب!'); return; }
            $(this).attr('disabled', true).text('⏳ العملية قيد التنفيذ في الخلفية...');
            $('#svs_status_log').html('⏳ <b>الروبوت يعمل الآن في صمت...</b> يرجى الانتظار بضع ثوانٍ وعدم مغادرة الصفحة.');
            window.dispatchEvent(new CustomEvent('SVS_START_SUMMARIZATION', { detail: { url: url } }));
        });

        // "المصافحة" مع الإضافة
        window.addEventListener('SVS_STATUS_UPDATE', (e) => {
            $('#svs_status_log').html(e.detail.msg);
            if(e.detail.done) $('#svs_start_btn').attr('disabled', false).text('🚀 ابدأ التلخيص مجدداً');
        });

        // --- وظيفة التنسيق المباشر والسريع ---
        $('#svs_btn_format_direct').click(function(){
            var raw = $('#svs_raw_result').val().trim();
            if(!raw) { alert('المربع فارغ! انتظر النتيجة أو الصقها يدوياً.'); return; }
            
            // تنظيف وتنظيم النص (Formatting Engine)
            let html = raw;
            // تحويل العناوين (### إلى h3)
            html = html.replace(/^### (.*?)$/gm, '<h3>$1</h3>');
            // تحويل البولد (** إلى strong)
            html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            // تحويل السطور لفقرات
            html = html.replace(/\n\n/g, '</p><p>');
            // إضافة فاصل وتنسيق نهائي
            html = '<hr> <div class="svs-article-summary">' + html + '</div>';

            let success = false;
            // 1. محاولة الإدراج في Gutenberg
            try {
                const editor = wp.data.dispatch('core/block-editor') || wp.data.dispatch('core/editor');
                const select = wp.data.select('core/block-editor') || wp.data.select('core/editor');
                if (editor && editor.insertBlocks) {
                    editor.insertBlocks(wp.blocks.createBlock('core/freeform', { content: html }), select.getBlockCount());
                    success = true;
                }
            } catch(e) { console.log("Gutenberg insert failed:", e); }

            // 2. محاولة TinyMCE (Classic Editor)
            if (!success && window.tinyMCE?.activeEditor) {
                try {
                    window.tinyMCE.activeEditor.setContent(window.tinyMCE.activeEditor.getContent() + html);
                    success = true;
                } catch(e) { console.log("TinyMCE insert failed:", e); }
            }

            if (success) {
                alert('✅ تم التنسيق والإدراج في المقال بنجاح!');
                $('#svs_status_log').html('✅ <b>عملية ناجحة!</b> يمكنك مراجعة المقال في المحرر.');
            } else {
                alert('⚠️ لم نتمكن من الإدراج التلقائي في المحرر. تم النسخ للحافظة، يرجى اللصق يدوياً.');
                navigator.clipboard.writeText(html);
            }
        });
    });
    </script>
    <?php
}
