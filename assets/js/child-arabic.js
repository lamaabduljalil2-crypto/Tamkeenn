const lettersData = [
  { letter: "أ", name: "ألف", animal: "أسد", img: 1 },
  { letter: "ب", name: "باء", animal: "بقرة", img: 2 },
  { letter: "ت", name: "تاء", animal: "تفاحة", img: 3 },
  { letter: "ث", name: "ثاء", animal: "ثعبان", img: 4 },
  { letter: "ج", name: "جيم", animal: "جمل", img: 5 },
  { letter: "ح", name: "حاء", animal: "حلوى", img: 6 },
  { letter: "خ", name: "خاء", animal: "خروف", img: 7 },
  { letter: "د", name: "دال", animal: "دجاجة", img: 8 },
  { letter: "ذ", name: "ذال", animal: "ذئب", img: 9 },
  { letter: "ر", name: "راء", animal: "رمّان", img: 10 },
  { letter: "ز", name: "زاي", animal: "زرافة", img: 11 },
  { letter: "س", name: "سين", animal: "ساعة", img: 12 },
  { letter: "ش", name: "شين", animal: "شمس", img: 13 },
  { letter: "ص", name: "صاد", animal: "صندوق", img: 14 },
  { letter: "ض", name: "ضاد", animal: "ضفدع", img: 15 },
  { letter: "ط", name: "طاء", animal: "طفل", img: 16 },
  { letter: "ظ", name: "ظاء", animal: "ظرف", img: 17 },
  { letter: "ع", name: "عين", animal: "عصفور", img: 18 },
  { letter: "غ", name: "غين", animal: "غيوم", img: 19 },
  { letter: "ف", name: "فاء", animal: "فراشة", img: 20 },
  { letter: "ق", name: "قاف", animal: "قلم", img: 21 },
  { letter: "ك", name: "كاف", animal: "كتاب", img: 22 },
  { letter: "ل", name: "لام", animal: "ليمون", img: 23 },
  { letter: "م", name: "ميم", animal: "مروحة", img: 24 },
  { letter: "ن", name: "نون", animal: "نحلة", img: 25 },
  { letter: "هـ", name: "هاء", animal: "هدية", img: 26 },
  { letter: "و", name: "واو", animal: "ورود", img: 27 },
  { letter: "ي", name: "ياء", animal: "يد", img: 28 }
];

let currentLetter = lettersData[0];
let selectedColor = "red";

const openBtn = document.getElementById("openLettersBtn");
const section = document.getElementById("lettersSection");
const grid = document.getElementById("lettersGrid");

const selectedLetter = document.getElementById("selectedLetter");
const letterTitle = document.getElementById("letterTitle");
const animalTitle = document.getElementById("animalTitle");
const animalImage = document.getElementById("animalImage");

const quizQuestion = document.getElementById("quizQuestion");
const quizOptions = document.getElementById("quizOptions");
const quizResult = document.getElementById("quizResult");

const paintLetter = document.getElementById("paintLetter");

/* فتح القسم */
openBtn.onclick = () => {
  section.classList.remove("hidden");
};

/* نطق */
function speak(text) {
  const u = new SpeechSynthesisUtterance(text);
  u.lang = "ar-SA";
  speechSynthesis.speak(u);
}

/* عرض الحرف */
function setLetter(item) {
  currentLetter = item;

  selectedLetter.textContent = item.letter;
  letterTitle.textContent = item.letter + " - " + item.name;
  animalTitle.textContent = item.animal;

  // 🔥 أهم سطر: ربط الصورة بالرقم
  animalImage.src = "../images/letters/" + item.img + ".png";

  renderQuiz(item);
}

/* إنشاء الأزرار */
function renderLetters() {
  grid.innerHTML = "";

  lettersData.forEach(item => {
    const btn = document.createElement("button");
    btn.textContent = item.letter;

    btn.onclick = () => {
      setLetter(item);
      speak(item.name + " " + item.animal);
    };

    grid.appendChild(btn);
  });

  setLetter(lettersData[0]);
}

/* الاختبار */
function renderQuiz(item) {
  quizQuestion.textContent = "اختر الحرف: " + item.name;

  quizOptions.innerHTML = "";
  quizResult.textContent = "";

  lettersData.forEach(opt => {
    const btn = document.createElement("button");
    btn.textContent = opt.letter;

    btn.onclick = () => {
      if (opt.letter === item.letter) {
        quizResult.textContent = "✔ صح";
      } else {
        quizResult.textContent = "✖ خطأ";
      }
    };

    quizOptions.appendChild(btn);
  });
}

/* النطق عند الصورة */
animalImage.onclick = () => {
  speak(currentLetter.name + " " + currentLetter.animal);
};

/* التلوين */
document.querySelectorAll(".color-btn").forEach(btn => {
  btn.onclick = () => {
    selectedColor = btn.dataset.color;
  };
});

paintLetter.onclick = () => {
  paintLetter.style.color = selectedColor;
};

renderLetters();