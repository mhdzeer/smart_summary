<?php
/**
 * Plugin Name: Smart Arabic GPT Video Summarizer
 * Description: (V 4.6) - نسخة التنسيق المضمون والتنظيف الشامل.
 * Version: 4.6
 * Author: Abu Taher
 */

if (!defined('ABSPATH')) exit;

add_action('add_meta_boxes', 'svs_add_v4_metabox');
function svs_add_v4_metabox() {
    foreach (['post', 'page'] as $s) add_meta_box('svs_mb', '🤖 ملخص الفيديو الذكي (V 4.6)', 'svs_v4_html', $s, 'normal', 'high');
}

function svs_v4_html($post) { ?>
    <div id="svs_container" style="direction:rtl; font-family:sans-serif; padding:15px; background:#fff; border:1px solid #ddd; border-top:4px solid #1a73e8; border-radius:8px;">
        
        <div id="svs_conn_status" style="margin-bottom:12px; padding:6px 12px; background:#f5f5f5; border-radius:20px; font-size:12px; display:inline-block; color:#666;">
            ⏳ جاري فحص اتصال الإضافة...
        </div>
        
        <div style="margin-bottom:12px;">
            <label style="display:block; margin-bottom:5px; font-weight:600;">رابط الفيديو المكتشف:</label>
            <input type="text" id="svs_url" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px;" placeholder="يتم الكشف تلقائياً...">
        </div>
        
        <button type="button" id="svs_start_btn" style="width:100%; padding:15px; background:#1a73e8; color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:bold; font-size:16px;">
            🚀 ابدأ التلخيص التلقائي (في الخلفية)
        </button>

        <div id="svs_status_log" style="margin-top:12px; font-size:13px; color:#1a73e8; background:#e8f4fd; padding:12px; border-radius:6px; min-height:40px;">
            💡 تأكد من تثبيت إضافة المتصفح للعمل.
        </div>

        <div style="margin-top:20px;" id="svs_result_box">
            <label style="font-weight:bold; display:block; margin-bottom:5px;">نتيجة Gemini:</label>
            <textarea id="svs_raw_result" style="width:100%; height:120px; padding:10px; border-radius:4px; font-size:13px;"></textarea>
            <button type="button" id="svs_btn_format_final" style="width:100%; padding:14px; background:#2e7d32; color:#fff; border:none; border-radius:8px; margin-top:10px; cursor:pointer; font-weight:bold; font-size:15px;">
                ✨ تنسيق وإدراج المقال في المحرر فوراً
            </button>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($){
        // 1. كشف الرابط
        setInterval(function(){
            var content = "";
            try { if (window.wp?.data?.select('core/editor')) content = wp.data.select('core/editor').getEditedPostContent(); } catch(e) {}
            if (!content && window.tinyMCE?.activeEditor) content = tinyMCE.activeEditor.getContent();
            var m = content.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
            if (m) { var u = "https://www.youtube.com/watch?v="+m[1]; if($('#svs_url').val()!==u) $('#svs_url').val(u); }
        }, 3000);

        // 2. فحص الاتصال
        setInterval(function(){ window.dispatchEvent(new CustomEvent('SVS_CHECK_CONNECTION')); }, 2000);
        window.addEventListener('SVS_CONNECTION_OK', function(){
            $('#svs_conn_status').html('✅ إضافة المتصفح: <b>متصلة وجاهزة</b>').css({'background':'#e8f5e9','color':'#2e7d32'});
        });

        // 3. بدء العملية
        $('#svs_start_btn').click(function(){
            var url = $('#svs_url').val(); if(!url) return alert('ضع رابط يوتيوب!');
            $(this).attr('disabled', true).text('⏳ الروبوت يعمل الآن...');
            window.dispatchEvent(new CustomEvent('SVS_START_AUTO', { detail: { url: url } }));
        });

        // 4. استلام التحديثات
        window.addEventListener('SVS_LOG', function(e){
            $('#svs_status_log').html(e.detail.msg);
            if(e.detail.done) $('#svs_start_btn').attr('disabled', false).text('🚀 إعادة التلخيص');
        });

        // 5. التنسيق والنشر النهائي (الإصلاح الجذري)
        $('#svs_btn_format_final').click(function(){
            var text = $('#svs_raw_result').val().trim();
            if(!text) return alert('المحتوى فارغ!');

            // تنظيف النص من الفضلات قبل التنسيق
            text = text.split(/رابط الفيديو:|alkarbabadi|مشاهدة|views/i)[0].trim();
            
            // تحويل Markdown إلى HTML
            let html = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            html = html.replace(/^### (.*?)$/gm, '<h3>$1</h3>');
            html = html.replace(/\n\n/g, '</p><p>');
            html = '<hr> <div class="svs-article-summary"><p>' + html + '</p></div>';

            let inserted = false;
            // محاولة الإدراج في Gutenberg
            try {
                const editor = (window.wp && wp.data) ? wp.data.dispatch('core/block-editor') : null;
                const select = (window.wp && wp.data) ? wp.data.select('core/block-editor') : null;
                if (editor && editor.insertBlocks) {
                    editor.insertBlocks(wp.blocks.createBlock('core/freeform', { content: html }), select.getBlockCount());
                    inserted = true;
                }
            } catch(e) {}

            // محاولة Classic Editor
            if (!inserted && window.tinyMCE?.activeEditor) {
                try {
                    window.tinyMCE.activeEditor.setContent(window.tinyMCE.activeEditor.getContent() + html);
                    inserted = true;
                } catch(e) {}
            }

            if (inserted) {
                alert('✅ تم التنسيق والإدراج بنجاح!');
            } else {
                alert('⚠️ تعذر الإدراج آلياً. تم نسخ المقال للشاملة، قم بلصقه يدوياً في المحرر.');
                navigator.clipboard.writeText(html);
            }
        });
    });
    </script>
    <?php
}
