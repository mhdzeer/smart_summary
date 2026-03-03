// content.js - يعمل داخل صفحة يوتيوب لجمع الفيديوهات

// استماع للرسائل القادمة من popup.js
chrome.runtime.onMessage.addListener(function (request, sender, sendResponse) {
    if (request.action === 'getVideos') {
        var videos = [];
        var selectors = [
            'ytd-rich-grid-media',
            'ytd-grid-video-renderer',
            'ytd-video-renderer'
        ];

        document.querySelectorAll(selectors.join(', ')).forEach(function (el) {
            var titleEl = el.querySelector('#video-title');
            var linkEl = el.querySelector('a#video-title, a#video-title-link, a.ytd-video-renderer, #thumbnail a');
            if (titleEl && linkEl && titleEl.innerText.trim()) {
                var url = linkEl.href;
                // تنظيف الرابط من المعاملات الزائدة
                if (url.includes('watch?v=')) {
                    url = url.split('&')[0];
                }
                videos.push({
                    title: titleEl.innerText.trim(),
                    url: url
                });
            }
        });

        sendResponse({ videos: videos, count: videos.length });
    }
    return true; // للسماح بالرد غير المتزامن
});
