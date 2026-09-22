/* ====== ELEMENTS ====== */
const seaArea = document.getElementById("seaArea");
const targetLetterEl = document.getElementById("targetLetter");
const scoreEl = document.getElementById("score");
const heartsEl = document.getElementById("hearts");
const net = document.getElementById("net");

const endActions = document.getElementById("endActions");
const endText = document.getElementById("endText");
const retryBtn = document.getElementById("retryBtn");
const backToLetterBtn = document.getElementById("backToLetterBtn");
const gameToast = document.getElementById("gameToast");

const confettiCanvas = document.getElementById("confettiCanvas");
const confettiCtx = confettiCanvas ? confettiCanvas.getContext("2d") : null;

/* ====== VIDEO ====== */
const guideVideo = document.getElementById("guideVideo");
const playBtn = document.getElementById("playBtn");

/* ====== CONFIG ====== */
const CONFIG = window.QUIZ_CONFIG || {};
const TARGET_LETTER = CONFIG.targetLetter || "أ";
const TARGET_ID = CONFIG.targetId || 1;
const MAX_WINS = CONFIG.maxWins || 3;
const MAX_LIVES = CONFIG.maxLives || 3;
const REDIRECT_URL = CONFIG.redirectUrl || `arabic-letter.php?id=${TARGET_ID}`;

/* ====== DATA ====== */
const arabicLetters = [
  "أ", "ب", "ت", "ث", "ج", "ح", "خ", "د", "ذ", "ر", "ز", "س", "ش", "ص",
  "ض", "ط", "ظ", "ع", "غ", "ف", "ق", "ك", "ل", "م", "ن", "ه", "و", "ي"
];

const englishLetters = [
  "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M",
  "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z"
];

const fishImages = [
  "../../images/quiz/fish1.png",
  "../../images/quiz/fish2.png",
  "../../images/quiz/fish3.png"
];

/* ====== LANGUAGE DETECTION ====== */
function isEnglishLetter(letter) {
  return /^[A-Za-z]$/.test(String(letter).trim());
}

const ACTIVE_LETTERS = isEnglishLetter(TARGET_LETTER) ? englishLetters : arabicLetters;

/* ====== STATE ====== */
let fishes = [];
let score = 0;
let lives = MAX_LIVES;
let roundLocked = false;
let animationFrame = null;
let usedWrongLetters = [];
let toastTimeout = null;

let correctFishPositions = [];
let correctFishPointer = 0;

/* ====== HELPERS ====== */
function resizeCanvas() {
  if (!confettiCanvas) return;
  confettiCanvas.width = window.innerWidth;
  confettiCanvas.height = window.innerHeight;
}

resizeCanvas();
window.addEventListener("resize", resizeCanvas);

function randomBetween(min, max) {
  return Math.random() * (max - min) + min;
}

function shuffleArray(arr) {
  const a = [...arr];
  for (let i = a.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [a[i], a[j]] = [a[j], a[i]];
  }
  return a;
}

/* ====== UI ====== */
function updateStatus() {
  if (targetLetterEl) {
    targetLetterEl.textContent = TARGET_LETTER;
  }

  if (scoreEl) {
    scoreEl.textContent = score;
  }

  if (heartsEl) {
    heartsEl.textContent = "❤️".repeat(lives) + "🖤".repeat(MAX_LIVES - lives);
  }
}

function showToast(type = "success") {
  if (!gameToast) return;

  const icon = type === "success" ? "✓" : "✕";

  gameToast.innerHTML = `
    <div class="game-toast-icon">${icon}</div>
  `;

  gameToast.className = `game-toast ${type}`;

  requestAnimationFrame(() => {
    gameToast.classList.add("show");
  });

  clearTimeout(toastTimeout);
  toastTimeout = setTimeout(() => {
    gameToast.classList.remove("show");
  }, 700);
}

function showEndActions(success) {
  if (!endActions) return;

  if (endText) {
    endText.textContent = "";
    endText.style.display = "none";
    endText.className = success ? "end-text success-text" : "end-text fail-text";
  }

  endActions.classList.remove("hidden");
}

function hideEndActions() {
  if (endActions) {
    endActions.classList.add("hidden");
  }

  if (endText) {
    endText.textContent = "";
    endText.style.display = "none";
  }
}

/* ====== CONFETTI ====== */
function launchConfetti() {
  if (!confettiCanvas || !confettiCtx) return;

  const pieces = [];
  const colors = ["#ff6b6b", "#ffd93d", "#6bcB77", "#4d96ff", "#c77dff", "#ff8fab"];

  for (let i = 0; i < 120; i++) {
    pieces.push({
      x: randomBetween(0, confettiCanvas.width),
      y: randomBetween(-150, 0),
      size: randomBetween(6, 12),
      speedX: randomBetween(-2.2, 2.2),
      speedY: randomBetween(2.2, 5.5),
      rotation: randomBetween(0, Math.PI * 2),
      rotationSpeed: randomBetween(-0.18, 0.18),
      color: colors[i % colors.length]
    });
  }

  let frames = 0;

  function animateConfetti() {
    confettiCtx.clearRect(0, 0, confettiCanvas.width, confettiCanvas.height);

    pieces.forEach(piece => {
      piece.x += piece.speedX;
      piece.y += piece.speedY;
      piece.rotation += piece.rotationSpeed;

      confettiCtx.save();
      confettiCtx.translate(piece.x, piece.y);
      confettiCtx.rotate(piece.rotation);
      confettiCtx.fillStyle = piece.color;
      confettiCtx.fillRect(-piece.size / 2, -piece.size / 2, piece.size, piece.size * 0.6);
      confettiCtx.restore();
    });

    frames++;
    if (frames < 120) {
      requestAnimationFrame(animateConfetti);
    } else {
      confettiCtx.clearRect(0, 0, confettiCanvas.width, confettiCanvas.height);
    }
  }

  animateConfetti();
}

/* ====== GAME HELPERS ====== */
function clearFishes() {
  fishes.forEach(fish => {
    if (fish.el && fish.el.parentNode) {
      fish.el.parentNode.removeChild(fish.el);
    }
  });
  fishes = [];
}

function getWrongLettersForRound(count = 2) {
  const allWrongLetters = ACTIVE_LETTERS.filter(letter => letter !== TARGET_LETTER);
  let available = allWrongLetters.filter(letter => !usedWrongLetters.includes(letter));

  if (available.length < count) {
    usedWrongLetters = [];
    available = allWrongLetters.filter(letter => !usedWrongLetters.includes(letter));
  }

  const selected = shuffleArray(available).slice(0, count);
  usedWrongLetters.push(...selected);
  return selected;
}

function getNextCorrectFishIndex() {
  if (correctFishPointer >= correctFishPositions.length) {
    correctFishPositions = shuffleArray([0, 1, 2]);
    correctFishPointer = 0;
  }

  return correctFishPositions[correctFishPointer++];
}

function createRoundLetters() {
  const wrongLetters = shuffleArray(getWrongLettersForRound(2));
  const correctIndex = getNextCorrectFishIndex();

  const roundLetters = ["", "", ""];
  roundLetters[correctIndex] = TARGET_LETTER;

  let wrongIndex = 0;
  for (let i = 0; i < 3; i++) {
    if (roundLetters[i] === "") {
      roundLetters[i] = wrongLetters[wrongIndex++];
    }
  }

  return roundLetters;
}

function setFishDirection(fishObj) {
  const img = fishObj.el.querySelector("img");
  if (!img || fishObj.caught) return;

  img.classList.remove("face-left", "face-right");

  if (fishObj.speed > 0) {
    img.classList.add("face-right");
  } else {
    img.classList.add("face-left");
  }
}

function createFish(letter, imgPath, x, y, speed) {
  const fish = document.createElement("div");
  fish.className = "fish";

  fish.innerHTML = `
    <img src="${imgPath}" alt="سمكة ${letter}">
    <span class="fish-letter">${letter}</span>
  `;

  seaArea.appendChild(fish);

  const fishObj = {
    el: fish,
    letter,
    x,
    y,
    speed,
    width: window.innerWidth <= 900 ? 96 : 122,
    height: window.innerWidth <= 900 ? 66 : 82,
    caught: false
  };

  setFishDirection(fishObj);

  fish.addEventListener("click", () => checkAnswer(fishObj));
  fishes.push(fishObj);
}

function startRound() {
  if (!seaArea || score >= MAX_WINS || lives <= 0) return;

  roundLocked = false;
  hideEndActions();
  clearFishes();

  const roundLetters = createRoundLetters();
  const seaWidth = seaArea.clientWidth;

  const yPositions = window.innerWidth <= 600
    ? [60, 145, 225]
    : window.innerWidth <= 900
      ? [70, 160, 250]
      : [85, 180, 285];

  roundLetters.forEach((letter, index) => {
    const imgPath = fishImages[index % fishImages.length];
    const startX = randomBetween(25, Math.max(30, seaWidth - 150));
    const y = yPositions[index];
    const speed = index % 2 === 0
      ? randomBetween(0.9, 1.35)
      : -randomBetween(0.9, 1.35);

    createFish(letter, imgPath, startX, y, speed);
  });

  updateStatus();
}

function animateFish() {
  if (!seaArea) return;

  const seaWidth = seaArea.clientWidth;

  fishes.forEach(fish => {
    if (!fish.caught) {
      fish.x += fish.speed;

      if (fish.speed > 0 && fish.x > seaWidth) {
        fish.x = -fish.width;
      }

      if (fish.speed < 0 && fish.x < -fish.width) {
        fish.x = seaWidth;
      }

      setFishDirection(fish);
    }

    fish.el.style.left = `${fish.x}px`;
    fish.el.style.top = `${fish.y}px`;
  });

  animationFrame = requestAnimationFrame(animateFish);
}

function showNetAtFish(fish) {
  if (!net) return;

  const netWidth = 140;
  const fishWidth = fish.width || 122;

  net.style.left = `${fish.x + (fishWidth / 2) - (netWidth / 2)}px`;
  net.style.top = `${fish.y - 24}px`;
  net.classList.add("show");

  setTimeout(() => {
    net.classList.remove("show");
  }, 500);
}

function lockFishOnCatch(fish) {
  const img = fish.el.querySelector("img");

  fish.caught = true;

  if (img) {
    const currentTransform = window.getComputedStyle(img).transform;
    img.style.transform = currentTransform;
    img.classList.remove("face-left", "face-right");
  }

  fish.el.style.transform = "scale(1)";
}

/* ====== GAME LOGIC ====== */
function endGame(win) {
  roundLocked = true;
  clearFishes();

  if (win) {
    launchConfetti();
    showEndActions(true);
  } else {
    showEndActions(false);
  }
}

function checkAnswer(fish) {
  if (roundLocked || fish.caught) return;

  showNetAtFish(fish);
  lockFishOnCatch(fish);

  if (fish.letter === TARGET_LETTER) {
    roundLocked = true;
    score++;
    updateStatus();

    fish.el.classList.add("correct");
    showToast("success");

    if (score >= MAX_WINS) {
      setTimeout(() => endGame(true), 700);
    } else {
      setTimeout(() => {
        startRound();
      }, 950);
    }
  } else {
    lives--;
    updateStatus();

    fish.el.classList.add("wrong");
    showToast("error");

    if (lives <= 0) {
      setTimeout(() => endGame(false), 700);
    } else {
      setTimeout(() => {
        fish.el.classList.remove("wrong");
        fish.caught = false;

        const img = fish.el.querySelector("img");
        if (img) {
          img.style.transform = "";
        }

        setFishDirection(fish);
      }, 450);
    }
  }
}

function restartGame() {
  score = 0;
  lives = MAX_LIVES;
  usedWrongLetters = [];
  correctFishPositions = [];
  correctFishPointer = 0;
  roundLocked = false;
  hideEndActions();
  clearFishes();
  updateStatus();
  startRound();
}

/* ====== VIDEO EVENTS ====== */
if (guideVideo && playBtn) {

  function playGuideVideo() {
    guideVideo.play().then(() => {
      playBtn.style.display = "none";
    }).catch(() => {});
  }

  playBtn.addEventListener("click", (e) => {
    e.stopPropagation();
    playGuideVideo();
  });

  guideVideo.addEventListener("click", () => {
    playGuideVideo();
  });

  guideVideo.addEventListener("pause", () => {
    if (!guideVideo.ended) {
      playBtn.style.display = "flex";
    }
  });

  guideVideo.addEventListener("ended", () => {
    playBtn.style.display = "flex";
  });

  guideVideo.addEventListener("play", () => {
    playBtn.style.display = "none";
  });
}

/* ====== BUTTON EVENTS ====== */
if (retryBtn) {
  retryBtn.addEventListener("click", restartGame);
}

if (backToLetterBtn) {
  backToLetterBtn.addEventListener("click", () => {
    window.location.href = REDIRECT_URL;
  });
}

window.addEventListener("resize", () => {
  clearFishes();
  hideEndActions();

  if (score < MAX_WINS && lives > 0) {
    startRound();
  }
});

/* ====== INIT ====== */
updateStatus();
startRound();

if (animationFrame) {
  cancelAnimationFrame(animationFrame);
}
animateFish();