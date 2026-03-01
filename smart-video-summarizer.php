<?php
/**
 * Plugin Name: Smart Arabic GPT Video Summarizer
 * Description: (V 4.4) - نسخة التأكيد التفاعلية. تواصل مباشر وحي مع الإضافة.
 * Version: 4.4
 * Author: Abu Taher
 */

if (!defined('ABSPATH')) exit;

add_action('add_meta_boxes', 'svs_add_v4_metabox');
function svs_add_v4_metabox() {
    foreach (['post', 'page'] as $s) add_meta_box('svs_mb', '🤖 ملخص الفيديو الذكي (V 4.4)', 'svs_v4_html', $s, 'normal', 'high');
}

function svs_v4_html($post) { ?>
    <div id="svs_container" style="direction:rtl; font-family:'Segoe UI', Tahoma, sans-serif; padding:15px; background:#fff; border:1px solid #ddd; border-top:4px solid #1a73e8; border-radius:8px;">
        
        <div id="svs_conn_status" style="margin-bottom:12px; padding:6px 12px; background:#f5f5f5; border-radius:20px; font-size:12px; display:inline-block; color:#666;">
            ❌ إضافة المتصفح: <b>غير متصلة</b>
        </div>
        
        <div style="margin-bottom:12px;">
            <label style="display:block; margin-bottom:5px; font-weight:600;">رابط الفيديو:</label>
            <input type="text" id="svs_url" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:6px;" placeholder="يتم الكشف تلقائياً...">
        </div>
        
        <button type="button" id="svs_start_btn" style="width:100%; padding:15px; background:#1a73e8; color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:bold; font-size:16px; transition:0.3s;">
            🚀 ابدأ التلخيص التلقائي الآن
        </button>

        <div id="svs_status_log" style="margin-top:12px; font-size:13px; color:#1a73e8; background:#e8f4fd; padding:12px; border-radius:6px; min-height:40px; line-height:1.6;">
            💡 يرجى تثبيت الإضافة وعمل Refresh لهذه الصفحة.
        </div>

        <div style="margin-top:20px; display:none;" id="svs_result_box">
            <label style="font-weight:bold; display:block; margin-bottom:5px;">نتيجة Gemini (خام):</label>
            <textarea id="svs_raw_result" style="width:100%; height:120px; padding:10px; border-radius:4px;"></textarea>
            <button type="button" id="svs_btn_format_final" style="width:100%; padding:12px; background:#2e7d32; color:#fff; border:none; border-radius:6px; margin-top:10px; cursor:pointer; font-weight:bold;">✨ نشر التنسيق النهائي في المقال</button>
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

        // 2. فحص اتصال الإضافة (Heartbeat)
        setInterval(function(){
            window.dispatchEvent(new CustomEvent('SVS_CHECK_CONNECTION'));
        }, 2000);

        window.addEventListener('SVS_CONNECTION_OK', function(){
            $('#svs_conn_status').html('✅ إضافة المتصفح: <b>متصلة وجاهزة</b>').css({'background':'#e8f5e9','color':'#2e7d32'});
        });

        // 3. بدء التلخيص
        $('#svs_start_btn').click(function(){
            var url = $('#svs_url').val();
            if(!url) { alert('ضع رابط فيديو أولاً!'); return; }
            $(this).attr('disabled', true).css('opacity','0.6').text('⏳ جاري الطلب من الإضافة...');
            $('#svs_status_log').html('⏳ يتم الآن إرسال الرابط للإضافة... يرجى عدم إغلاق المتصفح.');
            
            // إرسال الإشارة
            window.dispatchEvent(new CustomEvent('SVS_START_AUTO', { detail: { url: url } }));
        });

        // 4. استقبال التحديثات الحية من الإضافة
        window.addEventListener('SVS_LOG', function(e){
            $('#svs_status_log').html(e.detail.msg);
            if(e.detail.done) {
                $('#svs_start_btn').attr('disabled', false).css('opacity','1').text('🚀 إعادة التلخيص');
                $('#svs_result_box').fadeIn();
            }
        });

        // 5. التنسيق والنشر
        $('#svs_btn_format_final').click(function(){
            var raw = $('#svs_raw_result').val().trim(); if(!raw) return;
            let html = '<hr><h3>ملخص الفيديو</h3>' + raw.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>').replace(/### (.*?)\n/g, '<h3>$1</h3>').replace(/\n\n/g, '</p><p>');
            if(window.wp?.data?.dispatch('core/block-editor')) {
                wp.data.dispatch('core/block-editor').insertBlocks(wp.blocks.createBlock('core/freeform', {content: html}));
            } else if(window.tinyMCE?.activeEditor) {
                tinyMCE.activeEditor.setContent(tinyMCE.activeEditor.getContent() + html);
            }
            alert('✅ تم النشر بنجاح!');
        });
    });
    </script>
    <?php
}
