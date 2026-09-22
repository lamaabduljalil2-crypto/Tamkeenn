function normalizeArabic(text) {
  return text
    .trim()
    .toLowerCase()
    .replace(/[أإآا]/g, "ا")
    .replace(/ة/g, "ه")
    .replace(/ى/g, "ي")
    .replace(/ئ/g, "ي")
    .replace(/ؤ/g, "و")
    .replace(/[ًٌٍَُِّْـ]/g, "")
    .replace(/\s+/g, " ");
}

function levenshtein(a, b) {
  const matrix = Array.from({ length: b.length + 1 }, () => []);
  for (let i = 0; i <= b.length; i++) matrix[i][0] = i;
  for (let j = 0; j <= a.length; j++) matrix[0][j] = j;

  for (let i = 1; i <= b.length; i++) {
    for (let j = 1; j <= a.length; j++) {
      if (b.charAt(i - 1) === a.charAt(j - 1)) {
        matrix[i][j] = matrix[i - 1][j - 1];
      } else {
        matrix[i][j] = Math.min(
          matrix[i - 1][j - 1] + 1,
          matrix[i][j - 1] + 1,
          matrix[i - 1][j] + 1
        );
      }
    }
  }

  return matrix[b.length][a.length];
}

function similarityPercent(input, target) {
  const a = normalizeArabic(input);
  const b = normalizeArabic(target);

  if (!a || !b) return 0;

  const distance = levenshtein(a, b);
  const maxLen = Math.max(a.length, b.length);
  return Math.round((1 - distance / maxLen) * 100);
}

function bestMatchPercent(spokenText, validAnswers) {
  let best = 0;
  for (const answer of validAnswers) {
    const percent = similarityPercent(spokenText, answer);
    if (percent > best) best = percent;
  }
  return best;
}

function speakText(text) {
  if (!("speechSynthesis" in window)) return;

  window.speechSynthesis.cancel();

  const utterance = new SpeechSynthesisUtterance(text);
  utterance.lang = "ar-SA";
  utterance.rate = 0.9;
  utterance.pitch = 1;

  const voices = window.speechSynthesis.getVoices();
  const arabicVoice = voices.find(v => v.lang && v.lang.toLowerCase().includes("ar"));
  if (arabicVoice) utterance.voice = arabicVoice;

  window.speechSynthesis.speak(utterance);
}

function startSpeechCheck(validAnswers, resultEl) {
  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

  if (!SpeechRecognition) {
    resultEl.textContent = "المتصفح لا يدعم الميكروفون";
    resultEl.className = "speech-result error";
    return;
  }

  const recognition = new SpeechRecognition();
  recognition.lang = "ar-SA";
  recognition.interimResults = false;
  recognition.maxAlternatives = 1;

  resultEl.textContent = "جاري الاستماع...";
  resultEl.className = "speech-result listening";

  recognition.start();

  recognition.onresult = function(event) {
    const spokenText = event.results[0][0].transcript;
    const percent = bestMatchPercent(spokenText, validAnswers);

    if (percent >= 70) {
      resultEl.textContent = `ممتاز! النتيجة ${percent}%`;
      resultEl.className = "speech-result success";
      speakText("ممتاز");
    } else {
      resultEl.textContent = `حاول مرة أخرى. النتيجة ${percent}%`;
      resultEl.className = "speech-result error";
      speakText("حاول مرة أخرى");
    }
  };

  recognition.onerror = function() {
    resultEl.textContent = "حدث خطأ أثناء التسجيل";
    resultEl.className = "speech-result error";
  };
}

window.speechSynthesis?.addEventListener?.("voiceschanged", () => {});