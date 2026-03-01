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
        
        /* تحسينات للطباعة */
        @media print {
            body * { visibility: hidden; }
            .svs-print-mode, .svs-print-mode * { visibility: visible; }
            .svs-print-mode { position: absolute; left: 0; top: 0; width: 100%; border: none; }
        }
    </style>';
}

add_action('add_meta_boxes', 'svs_add_v4_metabox');
function svs_add_v4_metabox() {
    foreach (['post', 'page'] as $s) add_meta_box('svs_mb', '💠 مقال النشر والطباعة الاحترافي (V 4.9.5)', 'svs_v4_html', $s, 'normal', 'high');
}

function svs_v4_html($post) { ?>
    <div id="svs_container" style="direction:rtl; font-family:sans-serif; padding:15px; background:#f4f4f4; border-radius:10px; border:1px solid #ccc;">
        
        <div id="svs_conn_status" style="margin-bottom:12px; padding:6px 12px; background:#fff; border:1px solid #ddd; border-radius:20px; font-size:12px; display:inline-block;">
            ⏳ جاري فحص الاتصال...
        </div>

        <div style="margin-bottom:12px;">
            <input type="text" id="svs_url" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;" placeholder="سيظهر رابط الفيديو هنا...">
        </div>
        
        <button type="button" id="svs_start_btn" style="width:100%; padding:15px; background:#1a73e8; color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:bold; font-size:16px;">
            🚀 ابدأ التلخيص (أتمتة كاملة)
        </button>

        <div id="svs_status_log" style="margin-top:10px; font-size:13px; color:#1a73e8;">💡 بانتظار البدء...</div>

        <div style="margin-top:20px;" id="svs_result_box">
            <label style="font-weight:bold; display:block; margin-bottom:8px;">النص المستلم من Gemini (يمكنك التعديل هنا):</label>
            <textarea id="svs_raw_result" style="width:100%; height:300px; padding:15px; border:1px solid #bbb; border-radius:4px; font-family: 'Amiri', serif; font-size:18px; line-height:1.6; background:#fff;"></textarea>
            
            <button type="button" id="svs_btn_format_premium" style="width:100%; padding:18px; background:#2e7d32; color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:bold; font-size:18px; margin-top:15px; box-shadow:0 4px 15px rgba(0,0,0,0.2);">
                ✨ تحويل إلى "مقال فاخر" ونشره في المحرر
            </button>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($){
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
            if(!url) return alert("❌ لم يتم العثور على رابط فيديو!");
            $(this).attr('disabled', true).text('⏳ الروبوت يكتب الآن...');
            window.dispatchEvent(new CustomEvent('SVS_START_AUTO', { detail: { url: url } }));
        });

        window.addEventListener('SVS_LOG', function(e){ $('#svs_status_log').html(e.detail.msg); });

        $('#svs_btn_format_premium').click(function(){
            var text = $('#svs_raw_result').val().trim();
            if(!text) return;

            // 1. تنظيف التذييلات (تأكيد الحذف)
            text = text.split(/رابط الفيديو:|alkarbabadi|مشاهدة|views/i)[0].trim();

            function advancedMarkdownToHtml(md) {
                let html = md;
                
                // أولاً: تحويل العناوين الرئيسية (إذا كانت محاطة بـ **)
                html = html.replace(/^\*\*(.*?)\*\*$/gm, '<h2>$1</h2>');

                // ثانياً: العناوين الفرعية ###
                html = html.replace(/^### (.*?)$/gm, '<h3>$1</h3>');
                
                // ثالثاً: البولد العادي
                html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                
                // رابعاً: القوائم المرقمة (الحفاظ على الرقم كـ نص لضمان عدم حذفه)
                // نحول 1. نص إلى <span class="list-item">1. نص</span>
                html = html.replace(/^(\d+[\.\)]\s+.*?)$/gm, '<span class="list-item">$1</span>');
                
                // خامساً: القوائم المنقطة
                html = html.replace(/^[\*\-]\s+(.*?)$/gm, '<li>$1</li>');
                html = html.replace(/(?:<li>.*?<\/li>\n?)+/g, function(m) { return '<ul>' + m + '</ul>'; });

                // سادساً: الفقرات (تغليف أي نص غير مغلف بـ وسوم)
                let lines = html.split('\n');
                html = lines.map(line => {
                    line = line.trim();
                    if (!line) return "";
                    if (line.startsWith('<h') || line.startsWith('<ul') || line.startsWith('<li') || line.startsWith('<span')) return line;
                    return '<p>' + line + '</p>';
                }).join('\n');

                return html;
            }

            let finalOutput = '<div class="svs-print-mode">' + advancedMarkdownToHtml(text) + '</div>';

            let success = false;
            try {
                const editor = wp.data.dispatch('core/block-editor') || wp.data.dispatch('core/editor');
                if (editor && editor.insertBlocks) {
                    editor.insertBlocks(wp.blocks.createBlock('core/freeform', { content: finalOutput }));
                    success = true;
                }
            } catch(e) {}

            if (!success && window.tinyMCE?.activeEditor) {
                window.tinyMCE.activeEditor.setContent(window.tinyMCE.activeEditor.getContent() + finalOutput);
                success = true;
            }

            if(success) alert('✅ تم التنسيق والنشر بالهيئة الجديدة!');
        });
    });
    </script>
    <?php
}
