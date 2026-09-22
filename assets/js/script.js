function startGame(subject) {
  alert("سيتم فتح قسم " + subject + " قريبًا");
}
let currentLang = localStorage.getItem("siteLanguage") || "ar";
let isSwitchingLanguage = false;

/* =========================
   VOICE ENGINE
========================= */






function focusBeforeLanguageButton() {
  const langTabStart = document.getElementById("langTabStart");
  if (langTabStart) {
    setTimeout(() => {
      langTabStart.focus();
    }, 50);
  }
}

/* =========================
   LANGUAGE SWITCHER
========================= */
function initLanguageSwitcher() {
  const langText = document.getElementById("langText");
  const heroImg = document.getElementById("heroImg");

  const logoTitle = document.getElementById("logoTitle");
  const logoSub = document.getElementById("logoSub");

  const navHome = document.getElementById("navHome");
  const navPrograms = document.getElementById("navPrograms");
  const navAbout = document.getElementById("navAbout");
  const navFun = document.getElementById("navFun");
  const navContact = document.getElementById("navContact");
  const loginBtn = document.getElementById("loginBtn");

  const heroTitle = document.getElementById("heroTitle");
  const heroDesc = document.getElementById("heroDesc");
  const startBtn = document.getElementById("startBtn");
  const moreBtn = document.getElementById("moreBtn");

  const subjectsTitle = document.getElementById("subjectsTitle");
  const card1Title = document.getElementById("card1Title");
  const card1Desc = document.getElementById("card1Desc");
  const card1Btn = document.getElementById("card1Btn");

  const card2Title = document.getElementById("card2Title");
  const card2Desc = document.getElementById("card2Desc");
  const card2Btn = document.getElementById("card2Btn");

  const card3Title = document.getElementById("card3Title");
  const card3Desc = document.getElementById("card3Desc");
  const card3Btn = document.getElementById("card3Btn");

  const card4Title = document.getElementById("card4Title");
  const card4Desc = document.getElementById("card4Desc");
  const card4Btn = document.getElementById("card4Btn");

  const aboutTitle = document.getElementById("aboutTitle");
  const aboutDesc = document.getElementById("aboutDesc");
  const feature1Title = document.getElementById("feature1Title");
  const feature1Desc = document.getElementById("feature1Desc");
  const feature2Title = document.getElementById("feature2Title");
  const feature2Desc = document.getElementById("feature2Desc");
  const feature3Title = document.getElementById("feature3Title");
  const feature3Desc = document.getElementById("feature3Desc");
  const feature4Title = document.getElementById("feature4Title");
  const feature4Desc = document.getElementById("feature4Desc");
  const feature5Title = document.getElementById("feature5Title");
  const feature5Desc = document.getElementById("feature5Desc");
  const feature6Title = document.getElementById("feature6Title");
  const feature6Desc = document.getElementById("feature6Desc");

  const funBadge = document.getElementById("funBadge");
  const funTitle = document.getElementById("funTitle");
  const funDesc = document.getElementById("funDesc");
  const fun1Title = document.getElementById("fun1Title");
  const fun1Desc = document.getElementById("fun1Desc");
  const fun2Title = document.getElementById("fun2Title");
  const fun2Desc = document.getElementById("fun2Desc");
  const fun3Title = document.getElementById("fun3Title");
  const fun3Desc = document.getElementById("fun3Desc");

  const footerBrandTitle = document.getElementById("footerBrandTitle");
  const footerBrandDesc = document.getElementById("footerBrandDesc");
  const footerQuickTitle = document.getElementById("footerQuickTitle");
  const footerLinkHome = document.getElementById("footerLinkHome");
  const footerLinkPrograms = document.getElementById("footerLinkPrograms");
  const footerLinkAbout = document.getElementById("footerLinkAbout");
  const footerLinkFun = document.getElementById("footerLinkFun");
  const footerLinkContact = document.getElementById("footerLinkContact");
  const footerProgramsTitle = document.getElementById("footerProgramsTitle");
  const footerProgram1 = document.getElementById("footerProgram1");
  const footerProgram2 = document.getElementById("footerProgram2");
  const footerProgram3 = document.getElementById("footerProgram3");
  const footerProgram4 = document.getElementById("footerProgram4");
  const footerContactTitle = document.getElementById("footerContactTitle");
  const footerBottom = document.getElementById("footerBottom");

  function setArabic() {
    currentLang = "ar";

    document.documentElement.lang = "ar";
    document.documentElement.dir = "rtl";
    document.body.classList.remove("ltr");
    localStorage.setItem("siteLanguage", "ar");

    if (langText) langText.textContent = "EN";
    if (langToggle) {
      langToggle.setAttribute("aria-label", "تغيير اللغة إلى الإنجليزية");
      langToggle.setAttribute("title", "تغيير اللغة إلى الإنجليزية");
    }

    if (logoTitle) logoTitle.textContent = "شمعة أمل";
    if (logoSub) logoSub.textContent = "منصة تعليمية للأطفال";

    if (navHome) navHome.textContent = "الرئيسية";
    if (navPrograms) navPrograms.textContent = "البرامج التعليمية";
    if (navAbout) navAbout.textContent = "عن المنصة";
    if (navFun) navFun.textContent = "التعليم الممتع";
    if (navContact) navContact.textContent = "تواصل معنا";
    if (loginBtn) loginBtn.textContent = "تسجيل الدخول";

    if (heroTitle) heroTitle.innerHTML = 'منصة <span>تعليمية ممتعة</span>';
    if (heroDesc) heroDesc.textContent = "هي تجربة تعليمية مبتكرة للأطفال من عمر 4 إلى 10 سنوات، تقدم محتوى تفاعليًا ممتعًا يعتمد على اللعب والمرح.";
    if (startBtn) startBtn.textContent = "ابدأ الآن";
    if (moreBtn) moreBtn.textContent = "تعرّف أكثر";

    if (subjectsTitle) subjectsTitle.textContent = "اختر المادة وابدأ التعلم";

    if (card1Title) card1Title.textContent = "اللغة الإنجليزية";
    if (card1Desc) card1Desc.textContent = "تعلم أساسيات اللغة الإنجليزية بطريقة ممتعة.";
    if (card1Btn) card1Btn.textContent = "ابدأ اللعب";

    if (card2Title) card2Title.textContent = "الرياضيات";
    if (card2Desc) card2Desc.textContent = "طور مهاراتك الحسابية بطريقة ممتعة.";
    if (card2Btn) card2Btn.textContent = "ابدأ اللعب";

    if (card3Title) card3Title.textContent = "التربية الإسلامية";
    if (card3Desc) card3Desc.textContent = "تعلم الوضوء والصلاة وأركان الإسلام بطريقة سهلة ومبسطة.";
    if (card3Btn) card3Btn.textContent = "ابدأ اللعب";

    if (card4Title) card4Title.textContent = "اللغة العربية";
    if (card4Desc) card4Desc.textContent = "تعلم القراءة والكتابة بأسلوب بسيط وممتع.";
    if (card4Btn) card4Btn.textContent = "ابدأ اللعب";

    if (aboutTitle) aboutTitle.innerHTML = 'لماذا يعد <span>شمعة أمل</span> الخيار الأمثل لتعليم طفلك؟';
    if (aboutDesc) aboutDesc.textContent = "نقدم تجربة تعليمية آمنة وممتعة، تمزج بين الإبداع والتكنولوجيا لتنمية مهارات طفلك بطريقة تفاعلية ومشوقة.";
    if (feature1Title) feature1Title.textContent = "مناهج عربية متكاملة";
    if (feature1Desc) feature1Desc.textContent = "محتوى تعليمي عالي الجودة وفق أحدث المعايير";
    if (feature2Title) feature2Title.textContent = "متابعة أداء الطفل";
    if (feature2Desc) feature2Desc.textContent = "لوحة تحكم ذكية لمتابعة تقدم الطفل بسهولة";
    if (feature3Title) feature3Title.textContent = "تعليم تفاعلي ممتع";
    if (feature3Desc) feature3Desc.textContent = "أنشطة وألعاب تجعل التعلم تجربة ممتعة";
    if (feature4Title) feature4Title.textContent = "تنمية المهارات";
    if (feature4Desc) feature4Desc.textContent = "تعزيز التفكير والإبداع لدى الطفل";
    if (feature5Title) feature5Title.textContent = "تعلم في أي وقت";
    if (feature5Desc) feature5Desc.textContent = "تعلم متاح 24/7 حسب وقت الطفل";
    if (feature6Title) feature6Title.textContent = "بيئة آمنة";
    if (feature6Desc) feature6Desc.textContent = "محتوى آمن وخالي من الإعلانات";

    if (funBadge) funBadge.textContent = "تعلم بمرح!";
    if (funTitle) funTitle.innerHTML = 'كيف نصنع <span>تجربة تعليمية</span><br>لا تُنسى؟';
    if (funDesc) funDesc.textContent = "في شمعة أمل نؤمن بأن التعلم يجب أن يكون ممتعًا ومحفزًا ومليئًا بالإبداع، إليك كيف نصمم تجربة استثنائية لطفلك:";
    if (fun1Title) fun1Title.textContent = "دروس تعليمية واختبارات ممتعة";
    if (fun1Desc) fun1Desc.textContent = "دروس تعليمية واختبارات تفاعلية شيقة تساعد في تعزيز مهارات القراءة والفهم مع تحفيز الطفل للتقدم.";
    if (fun2Title) fun2Title.textContent = "قصص وأنشطة تفاعلية";
    if (fun2Desc) fun2Desc.textContent = "محتوى مليء بالقصص والأنشطة والأناشيد، يقدم بأسلوب جذاب يعزز فهم الطفل ويشجعه على المشاركة.";
    if (fun3Title) fun3Title.textContent = "الوضوء و الصلاة واركان الاسلام";
    if (fun3Desc) fun3Desc.textContent = "تعلم اركان الاسلام و الوضوء و الصلاة بطريقة ممتعة ومفيدة";

    if (footerBrandTitle) footerBrandTitle.innerHTML = 'منصة <span>شمعة أمل</span>';
    if (footerBrandDesc) footerBrandDesc.textContent = "منصة تعليمية تفاعلية مبتكرة تقدم تجربة تعلم ممتعة للأطفال، مصممة لتنمية مهاراتهم بطريقة إبداعية.";
    if (footerQuickTitle) footerQuickTitle.textContent = "روابط سريعة";
    if (footerLinkHome) footerLinkHome.textContent = "الرئيسية";
    if (footerLinkPrograms) footerLinkPrograms.textContent = "البرامج التعليمية"; 
    if (footerLinkAbout) footerLinkAbout.textContent = "عن المنصة";
    if (footerLinkFun) footerLinkFun.textContent = "التعليم الممتع";
    if (footerLinkContact) footerLinkContact.textContent = "تواصل معنا";
    if (footerProgramsTitle) footerProgramsTitle.textContent = "البرامج التعليمية";
    if (footerProgram1) footerProgram1.textContent = "اللغة العربية";
    if (footerProgram2) footerProgram2.textContent = "العلوم";
    if (footerProgram3) footerProgram3.textContent = "الرياضيات";
    if (footerProgram4) footerProgram4.textContent = "اللغة الإنجليزية";
    if (footerContactTitle) footerContactTitle.textContent = "تواصل معنا";
    if (footerBottom) footerBottom.textContent = "© 2026 شمعة أمل - جميع الحقوق محفوظة";

    if (heroImg) {
      heroImg.style.transform = "scaleX(1)";
    }
  }

  function setEnglish() {
    currentLang = "en";

    document.documentElement.lang = "en";
    document.documentElement.dir = "ltr";
    document.body.classList.add("ltr");
    localStorage.setItem("siteLanguage", "en");

    if (langText) langText.textContent = "AR";
    if (langToggle) {
      langToggle.setAttribute("aria-label", "Change language to Arabic");
      langToggle.setAttribute("title", "Change language to Arabic");
    }

    if (logoTitle) logoTitle.textContent = "Sham'at Amal";
    if (logoSub) logoSub.textContent = "Educational platform for children";

    if (navHome) navHome.textContent = "Home";
    if (navPrograms) navPrograms.textContent = "Programs";
    if (navAbout) navAbout.textContent = "About";
    if (navFun) navFun.textContent = "Fun Learning";
    if (navContact) navContact.textContent = "Contact Us";
    if (loginBtn) loginBtn.textContent = "Login";

    if (heroTitle) heroTitle.innerHTML = 'A <span>Fun Educational</span> Platform';
    if (heroDesc) heroDesc.textContent = "An innovative learning experience for children aged 4 to 10, offering fun interactive content based on play and enjoyment.";
    if (startBtn) startBtn.textContent = "Start Now";
    if (moreBtn) moreBtn.textContent = "Learn More";

    if (subjectsTitle) subjectsTitle.textContent = "Choose a subject and start learning";

    if (card1Title) card1Title.textContent = "English Language";
    if (card1Desc) card1Desc.textContent = "Learn the basics of English in a fun way.";
    if (card1Btn) card1Btn.textContent = "Start Playing";

    if (card2Title) card2Title.textContent = "Mathematics";
    if (card2Desc) card2Desc.textContent = "Develop your math skills in a fun way.";
    if (card2Btn) card2Btn.textContent = "Start Playing";

    if (card3Title) card3Title.textContent = "Islamic Education";
    if (card3Desc) card3Desc.textContent = "Learn ablution, prayer, and the pillars of Islam in a simple way.";
    if (card3Btn) card3Btn.textContent = "Start Playing";

    if (card4Title) card4Title.textContent = "Arabic Language";
    if (card4Desc) card4Desc.textContent = "Learn reading and writing in a simple and enjoyable way.";
    if (card4Btn) card4Btn.textContent = "Start Playing";

    if (aboutTitle) aboutTitle.innerHTML = 'Why is <span>Sham\'at Amal</span> the best choice for your child?';
    if (aboutDesc) aboutDesc.textContent = "We provide a safe and enjoyable educational experience that combines creativity and technology to develop your child’s skills in an engaging way.";
    if (feature1Title) feature1Title.textContent = "Integrated Arabic Curriculum";
    if (feature1Desc) feature1Desc.textContent = "High-quality educational content based on the latest standards";
    if (feature2Title) feature2Title.textContent = "Track Child Progress";
    if (feature2Desc) feature2Desc.textContent = "A smart dashboard to monitor the child's progress easily";
    if (feature3Title) feature3Title.textContent = "Interactive Learning";
    if (feature3Desc) feature3Desc.textContent = "Activities and games that make learning enjoyable";
    if (feature4Title) feature4Title.textContent = "Skill Development";
    if (feature4Desc) feature4Desc.textContent = "Enhancing the child's thinking and creativity";
    if (feature5Title) feature5Title.textContent = "Learn Anytime";
    if (feature5Desc) feature5Desc.textContent = "Learning is available 24/7 based on the child's time";
    if (feature6Title) feature6Title.textContent = "Safe Environment";
    if (feature6Desc) feature6Desc.textContent = "Safe content free of ads";

    if (funBadge) funBadge.textContent = "Learn with Fun!";
    if (funTitle) funTitle.innerHTML = 'How do we create an <span>educational experience</span><br>to remember?';
    if (funDesc) funDesc.textContent = "At Sham'at Amal, we believe learning should be enjoyable, motivating, and full of creativity. Here is how we design an exceptional experience for your child:";
    if (fun1Title) fun1Title.textContent = "Fun Lessons and Tests";
    if (fun1Desc) fun1Desc.textContent = "Interactive lessons and fun quizzes that help strengthen reading and comprehension skills while motivating the child to progress.";
    if (fun2Title) fun2Title.textContent = "Stories and Interactive Activities";
    if (fun2Desc) fun2Desc.textContent = "Content full of stories, activities, and songs presented in an engaging style that encourages participation.";
    if (fun3Title) fun3Title.textContent = "Ablution, prayer, and the pillars of Islam";
    if (fun3Desc) fun3Desc.textContent = "Learn the pillars of Islam, ablution, and prayer in a fun and useful way.";

    if (footerBrandTitle) footerBrandTitle.innerHTML = 'Platform <span>Sham\'at Amal</span>';
    if (footerBrandDesc) footerBrandDesc.textContent = "An innovative interactive educational platform that offers a fun learning experience for children, designed to develop their skills creatively.";
    if (footerQuickTitle) footerQuickTitle.textContent = "Quick Links";
    if (footerLinkHome) footerLinkHome.textContent = "Home";
    if (footerLinkPrograms) footerLinkPrograms.textContent = "Programs";
    if (footerLinkAbout) footerLinkAbout.textContent = "About";
    if (footerLinkFun) footerLinkFun.textContent = "Fun Learning";
    if (footerLinkContact) footerLinkContact.textContent = "Contact Us";
    if (footerProgramsTitle) footerProgramsTitle.textContent = "Educational Programs";
    if (footerProgram1) footerProgram1.textContent = "Arabic Language";
    if (footerProgram2) footerProgram2.textContent = "Science";
    if (footerProgram3) footerProgram3.textContent = "Mathematics";
    if (footerProgram4) footerProgram4.textContent = "English Language";
    if (footerContactTitle) footerContactTitle.textContent = "Contact Us";
    if (footerBottom) footerBottom.textContent = "© 2026 Sham'at Amal - All rights reserved";

    if (heroImg) {
      heroImg.style.transform = "scaleX(-1)";
    }
  }

  currentLang = localStorage.getItem("siteLanguage") || "ar";

  if (currentLang === "en") {
    setEnglish();
  } else {
    setArabic();
  }

  if (langToggle) {
    langToggle.addEventListener("click", function () {
      isSwitchingLanguage = true;

      // تحديد اللغة الجديدة
      const newLang = currentLang === "ar" ? "en" : "ar";
      
      // على الصفحة الرئيسية (index.html)، تغيير DOM بدون إعادة تحميل
      if (document.getElementById("heroTitle")) {
        // نحن على الصفحة الرئيسية
        if (newLang === "en") {
          setEnglish();
        } else {
          setArabic();
        }
        
        speakPageWelcome();
        focusBeforeLanguageButton();
      } else {
        // نحن على صفحة أخرى، استخدم النظام الموحد الذي يعيد تحميل الصفحة
        if (typeof window.syncLanguage === 'function') {
          window.syncLanguage(newLang);
        } else {
          // fallback إذا لم يكن syncLanguage محمّل
          localStorage.setItem('siteLanguage', newLang);
          const separator = window.location.href.includes('?') ? '&' : '?';
          const newUrl = window.location.href.split('?')[0] + '?lang=' + newLang;
          window.location.href = newUrl;
        }
      }

      setTimeout(() => {
        isSwitchingLanguage = false;
      }, 1000);
    });

    langToggle.addEventListener("keydown", function (e) {
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        langToggle.click();
      }
    });
  }
}



