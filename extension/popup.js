// Smart YT - UTF-8 Supported Logic
document.getElementById('start').addEventListener('click', async () => {
  const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
  const statusMsg = document.getElementById('msg');

  if (!tab.url.includes("wp-admin")) {
    statusMsg.innerText = "❌ استخدم الإضافة داخل الووردبريس.";
    return;
  }

  statusMsg.innerText = "⏳ جاري استخراج رابط الفيديو...";

  chrome.scripting.executeScript({
    target: { tabId: tab.id },
    func: () => {
      let rawContent = "";
      try { if (window.wp?.data?.select('core/editor')) rawContent += wp.data.select('core/editor').getEditedPostContent(); } catch (e) { }
      try { if (window.tinyMCE?.activeEditor) rawContent += window.tinyMCE.activeEditor.getContent(); } catch (e) { }
      try { document.querySelectorAll('textarea, input, body').forEach(el => rawContent += " " + (el.value || el.innerText)); } catch (e) { }

      const ytRegex = /(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/;
      const m = rawContent.match(ytRegex);
      return m ? "https://www.youtube.com/watch?v=" + m[1] : null;
    }
  }, (results) => {
    const url = results[0]?.result;

    if (!url) {
      statusMsg.innerText = "❌ لم يتم العثور على رابط فيديو.";
      return;
    }

    statusMsg.innerText = "✅ جاري التوجيه لـ Gemini...";

    // استخدام Unicode للنص لضمان التشفير السليم
    const prompt = "قم بكتابة مقال تلخيصي احترافي وعميق باللغة العربية الفصحى لهذا الفيديو، جاهز للنشر الفوري.\n\n" +
      "رابط الفيديو: " + url + "\n\n" +
      "القواعد:\n" +
      "1. ابدأ مباشرة بمقدمة بليغة.\n" +
      "2. ممنوع ذكر اسم الشيخ أو القناة.\n" +
      "3. استخدم h3 للنصوص و ul للنقاط.\n" +
      "4. ممنوع مخاطبتي بـ 'إليك'.";

    chrome.storage.local.set({ "svs_prompt": prompt, "svs_source_tab": tab.id }, () => {
      chrome.tabs.create({ url: "https://gemini.google.com/app" });
    });
  });
});
