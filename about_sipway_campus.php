<?php
session_start();
require_once 'db.php';
if (!isset($conn) || $conn === null) {
    die("Database connection failed. Please check db.php file.");
}
$isLoggedIn = isset($_SESSION['student_id']);
// ---------- Defaults (guest state) ----------
$studentId       = null;
$studentName     = '';
$studentLanguage = 'en';
$firstName       = '';
$fullNameSafe    = '';
$photoUrl        = null;
if ($isLoggedIn) {
    $studentId   = $_SESSION['student_id'];
    $studentName = $_SESSION['student_name'] ?? '';
    $stmt = $conn->prepare("SELECT full_name, language, profile_photo FROM students WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $studentName     = $row['full_name'];
        $studentLanguage = $row['language'] ?: 'en';
        $studentPhoto    = $row['profile_photo'] ?? null;
        if ($studentPhoto && file_exists(__DIR__ . '/' . $studentPhoto)) {
            $photoUrl = htmlspecialchars($studentPhoto);
        }
    } else {
        session_destroy();
        session_start();
        $isLoggedIn = false;
        $studentId  = null;
    }
    $stmt->close();
}
if ($isLoggedIn) {
    $firstName    = htmlspecialchars(explode(' ', trim($studentName))[0]);
    $fullNameSafe = htmlspecialchars($studentName);
}
$studentNameJs = json_encode($studentName);

// ===== REGISTER GUIDE VIDEO (for How to Register modal) =====
$registerVideo = null;

$regRes = $conn->query("
    SELECT id, title, source_type, video_path, video_url
    FROM register_guide_video
    WHERE status = 'active'
    ORDER BY id DESC
    LIMIT 1
");

if ($regRes && $regRow = $regRes->fetch_assoc()) {
    $registerVideo = [
        'title'       => $regRow['title'] ?: 'How to Register',
        'source_type' => $regRow['source_type'],
        'player_type' => 'file',
        'player_src'  => $regRow['video_path'] ?? '',
    ];

    if ($regRow['source_type'] === 'link' && !empty($regRow['video_url'])) {
        $url = trim($regRow['video_url']);

        // YouTube: watch, embed, shorts and youtu.be links
        if (preg_match(
            '~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{6,})~i',
            $url,
            $m
        )) {
            $registerVideo['player_type'] = 'youtube';
            $registerVideo['player_src']  = 'https://www.youtube.com/embed/' . $m[1];
        }
        // Vimeo
        elseif (preg_match('~vimeo\.com/(?:video/)?(\d+)~i', $url, $m)) {
            $registerVideo['player_type'] = 'vimeo';
            $registerVideo['player_src']  = 'https://player.vimeo.com/video/' . $m[1];
        }
        // Direct video URL
        else {
            $registerVideo['player_type'] = 'direct';
            $registerVideo['player_src']  = $url;
        }
    }
}

$registerVideoJs = json_encode(
    $registerVideo,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
);

$conn->close();
// ===== Language Flag (Image-based) =====
$langMeta = [
    'en' => ['code' => 'gb', 'label' => 'English'],
    'de' => ['code' => 'de', 'label' => 'German'],
    'zh' => ['code' => 'cn', 'label' => 'Chinese'],
    'ja' => ['code' => 'jp', 'label' => 'Japanese'],
    'fr' => ['code' => 'fr', 'label' => 'French'],
    'hi' => ['code' => 'in', 'label' => 'Hindi'],
    'ru' => ['code' => 'ru', 'label' => 'Russian'],
    'ar' => ['code' => 'sa', 'label' => 'Arabic'],
    'ta' => ['code' => 'in', 'label' => 'Tamil'],
    'si' => ['code' => 'lk', 'label' => 'Sinhala'],
    'it' => ['code' => 'it', 'label' => 'Italian'],
];
$currentLang = strtolower($studentLanguage);
if (!isset($langMeta[$currentLang])) {
    $currentLang = 'en';
}
$langFlag  = 'https://flagcdn.com/w40/' . $langMeta[$currentLang]['code'] . '.png';
$langLabel = $langMeta[$currentLang]['label'];

// ===== About page translations (complete for all languages) =====
$t = [
    'en' => [
        'page_title'   => 'About Sipway Campus',
        'hero_title'   => 'About Sipway Campus',
        'hero_p'       => 'Empowering students across Sri Lanka with live, expert-led English learning sessions — anytime, anywhere.',
        'story_title'  => 'Our Story',
        'story_p1'     => 'Sipway Campus was founded with a simple mission: make high-quality English,German,Chinese, Japanese,French,Hindi,Russian,Arabic,Tamil,Italian education accessible to every student, regardless of location. We connect learners with experienced lecturers through interactive live sessions, flexible packages, and modern learning tools.',
        'story_p2'     => 'Whether you are preparing for exams, improving spokenEnglish,German,Chinese, Japanese,French,Hindi,Russian,Arabic,Tamil,Italianor building professional communication skills, Sipway Campus is designed to support your journey every step of the way.',
        'numbers_title'=> 'By the Numbers',
        'stat1'        => 'Active Students',
        'stat2'        => 'Expert Lecturers',
        'stat3'        => 'Languages Supported',
        'stat4'        => 'Sessions Completed',
        'values_title' => 'Our Values',
        'v1_title'     => 'Quality First',
        'v1_p'         => 'Every lecturer is carefully selected and every session is designed for real progress.',
        'v2_title'     => 'Accessibility',
        'v2_p'         => 'Learn from anywhere in Sri Lanka with flexible schedules and multiple language options.',
        'v3_title'     => 'Student Support',
        'v3_p'         => 'From registration to booking, our team and platform are built to help you succeed.',
        'v4_title'     => 'Continuous Growth',
        'v4_p'         => 'We keep improving the platform with AI practice tools, progress tracking, and more.',
        'cta_p'        => 'Ready to start your English learning journey with Sipway Campus?',
        'cta_btn_login'=> 'View Packages',
        'cta_btn_guest'=> 'Register Now',
        'your_lang'    => 'Your Language',
    ],
    'de' => [
        'page_title'   => 'Über Sipway Campus',
        'hero_title'   => 'Über Sipway Campus',
        'hero_p'       => 'Wir stärken Schüler in ganz Sri Lanka mit Live-Englischkursen von Experten — jederzeit und überall.',
        'story_title'  => 'Unsere Geschichte',
        'story_p1'     => 'Sipway Campus wurde mit einer einfachen Mission gegründet: hochwertige Englischbildung für jeden Schüler zugänglich zu machen, unabhängig vom Standort. Wir verbinden Lernende mit erfahrenen Dozenten durch interaktive Live-Sitzungen, flexible Pakete und moderne Lernwerkzeuge.',
        'story_p2'     => 'Ob Sie sich auf Prüfungen vorbereiten, Ihr gesprochenes Englisch verbessern oder berufliche Kommunikationsfähigkeiten aufbauen — Sipway Campus unterstützt Sie auf jedem Schritt Ihres Weges.',
        'numbers_title'=> 'In Zahlen',
        'stat1'        => 'Aktive Schüler',
        'stat2'        => 'Experten-Dozenten',
        'stat3'        => 'Unterstützte Sprachen',
        'stat4'        => 'Abgeschlossene Sitzungen',
        'values_title' => 'Unsere Werte',
        'v1_title'     => 'Qualität zuerst',
        'v1_p'         => 'Jeder Dozent wird sorgfältig ausgewählt und jede Sitzung ist auf echten Fortschritt ausgelegt.',
        'v2_title'     => 'Zugänglichkeit',
        'v2_p'         => 'Lernen Sie von überall in Sri Lanka mit flexiblen Zeitplänen und mehreren Sprachoptionen.',
        'v3_title'     => 'Schülerunterstützung',
        'v3_p'         => 'Von der Anmeldung bis zur Buchung – unser Team und die Plattform helfen Ihnen zum Erfolg.',
        'v4_title'     => 'Kontinuierliches Wachstum',
        'v4_p'         => 'Wir verbessern die Plattform ständig mit KI-Übungstools, Fortschrittsverfolgung und mehr.',
        'cta_p'        => 'Bereit, Ihre Englisch-Lernreise mit Sipway Campus zu starten?',
        'cta_btn_login'=> 'Pakete ansehen',
        'cta_btn_guest'=> 'Jetzt registrieren',
        'your_lang'    => 'Ihre Sprache',
    ],
    'zh' => [
        'page_title'   => '关于 Sipway Campus',
        'hero_title'   => '关于 Sipway Campus',
        'hero_p'       => '通过专家主持的实时英语学习课程，赋能斯里兰卡各地的学生——随时随地。',
        'story_title'  => '我们的故事',
        'story_p1'     => 'Sipway Campus 的创立使命很简单：让每位学生都能获得高质量的英语教育，无论身处何地。我们通过互动直播课程、灵活套餐和现代化学习工具，将学习者与经验丰富的讲师连接起来。',
        'story_p2'     => '无论您是准备考试、提高口语，还是培养专业沟通能力，Sipway Campus 都将全程支持您的学习之旅。',
        'numbers_title'=> '数据一览',
        'stat1'        => '活跃学生',
        'stat2'        => '专家讲师',
        'stat3'        => '支持语言',
        'stat4'        => '已完成课程',
        'values_title' => '我们的价值观',
        'v1_title'     => '质量第一',
        'v1_p'         => '每位讲师都经过精心挑选，每节课都为真正的进步而设计。',
        'v2_title'     => '无障碍学习',
        'v2_p'         => '在斯里兰卡任何地方学习，灵活的时间安排和多种语言选项。',
        'v3_title'     => '学生支持',
        'v3_p'         => '从注册到预约，我们的团队和平台都为帮助您成功而建。',
        'v4_title'     => '持续成长',
        'v4_p'         => '我们不断改进平台，加入 AI 练习工具、进度跟踪等功能。',
        'cta_p'        => '准备好与 Sipway Campus 一起开启英语学习之旅了吗？',
        'cta_btn_login'=> '查看套餐',
        'cta_btn_guest'=> '立即注册',
        'your_lang'    => '您的语言',
    ],
    'ja' => [
        'page_title'   => 'Sipway Campusについて',
        'hero_title'   => 'Sipway Campusについて',
        'hero_p'       => 'スリランカ全土の学生に、専門家によるライブ英語学習セッションを提供し、いつでもどこでも学べるようにします。',
        'story_title'  => '私たちの物語',
        'story_p1'     => 'Sipway Campusは、場所を問わずすべての学生が高品質な英語教育を受けられるようにするというシンプルな使命で設立されました。インタラクティブなライブセッション、柔軟なパッケージ、現代的な学習ツールを通じて、学習者と経験豊富な講師をつなぎます。',
        'story_p2'     => '試験対策、スピーキング力向上、またはプロフェッショナルなコミュニケーションスキルの構築など、Sipway Campusはあなたの学習の旅を一歩一歩サポートします。',
        'numbers_title'=> '数字で見る',
        'stat1'        => 'アクティブ学生',
        'stat2'        => '専門講師',
        'stat3'        => '対応言語',
        'stat4'        => '完了セッション',
        'values_title' => '私たちの価値観',
        'v1_title'     => '品質第一',
        'v1_p'         => 'すべての講師は慎重に選ばれ、すべてのセッションは本物の進歩のために設計されています。',
        'v2_title'     => 'アクセスしやすさ',
        'v2_p'         => 'スリランカのどこからでも、柔軟なスケジュールと多言語オプションで学べます。',
        'v3_title'     => '学生サポート',
        'v3_p'         => '登録から予約まで、チームとプラットフォームはあなたの成功をサポートします。',
        'v4_title'     => '継続的な成長',
        'v4_p'         => 'AI練習ツールや進捗追跡などでプラットフォームを常に改善しています。',
        'cta_p'        => 'Sipway Campusで英語学習の旅を始める準備はできましたか？',
        'cta_btn_login'=> 'パッケージを見る',
        'cta_btn_guest'=> '今すぐ登録',
        'your_lang'    => 'あなたの言語',
    ],
    'fr' => [
        'page_title'   => 'À propos de Sipway Campus',
        'hero_title'   => 'À propos de Sipway Campus',
        'hero_p'       => 'Autonomiser les étudiants à travers le Sri Lanka avec des sessions d\'apprentissage de l\'anglais en direct animées par des experts — à tout moment, n\'importe où.',
        'story_title'  => 'Notre histoire',
        'story_p1'     => 'Sipway Campus a été fondé avec une mission simple : rendre une éducation anglaise de haute qualité accessible à chaque étudiant, quel que soit son lieu. Nous connectons les apprenants à des conférenciers expérimentés grâce à des sessions live interactives, des forfaits flexibles et des outils d\'apprentissage modernes.',
        'story_p2'     => 'Que vous prépariez des examens, amélioriez votre anglais parlé ou développiez des compétences de communication professionnelle, Sipway Campus est conçu pour soutenir votre parcours à chaque étape.',
        'numbers_title'=> 'En chiffres',
        'stat1'        => 'Étudiants actifs',
        'stat2'        => 'Conférenciers experts',
        'stat3'        => 'Langues prises en charge',
        'stat4'        => 'Sessions terminées',
        'values_title' => 'Nos valeurs',
        'v1_title'     => 'Qualité d\'abord',
        'v1_p'         => 'Chaque conférencier est soigneusement sélectionné et chaque session est conçue pour un vrai progrès.',
        'v2_title'     => 'Accessibilité',
        'v2_p'         => 'Apprenez de n\'importe où au Sri Lanka avec des horaires flexibles et plusieurs options linguistiques.',
        'v3_title'     => 'Soutien aux étudiants',
        'v3_p'         => 'De l\'inscription à la réservation, notre équipe et notre plateforme sont conçues pour vous aider à réussir.',
        'v4_title'     => 'Croissance continue',
        'v4_p'         => 'Nous améliorons constamment la plateforme avec des outils d\'entraînement IA, le suivi des progrès et plus encore.',
        'cta_p'        => 'Prêt à commencer votre parcours d\'apprentissage de l\'anglais avec Sipway Campus ?',
        'cta_btn_login'=> 'Voir les forfaits',
        'cta_btn_guest'=> 'S\'inscrire maintenant',
        'your_lang'    => 'Votre langue',
    ],
    'hi' => [
        'page_title'   => 'सिपवे कैंपस के बारे में',
        'hero_title'   => 'सिपवे कैंपस के बारे में',
        'hero_p'       => 'श्रीलंका भर के छात्रों को विशेषज्ञों द्वारा संचालित लाइव अंग्रेजी सीखने के सत्रों के साथ सशक्त बनाना — कभी भी, कहीं भी।',
        'story_title'  => 'हमारी कहानी',
        'story_p1'     => 'सिपवे कैंपस की स्थापना एक सरल मिशन के साथ हुई: स्थान की परवाह किए बिना हर छात्र के लिए उच्च गुणवत्ता वाली अंग्रेजी शिक्षा को सुलभ बनाना। हम इंटरैक्टिव लाइव सत्रों, लचीले पैकेजों और आधुनिक सीखने के उपकरणों के माध्यम से शिक्षार्थियों को अनुभवी लेक्चरर से जोड़ते हैं।',
        'story_p2'     => 'चाहे आप परीक्षा की तैयारी कर रहे हों, बोलचाल की अंग्रेजी सुधार रहे हों, या पेशेवर संचार कौशल बना रहे हों, सिपवे कैंपस आपकी यात्रा के हर कदम पर समर्थन के लिए डिज़ाइन किया गया है।',
        'numbers_title'=> 'आंकड़ों में',
        'stat1'        => 'सक्रिय छात्र',
        'stat2'        => 'विशेषज्ञ लेक्चरर',
        'stat3'        => 'समर्थित भाषाएँ',
        'stat4'        => 'पूर्ण सत्र',
        'values_title' => 'हमारे मूल्य',
        'v1_title'     => 'गुणवत्ता पहले',
        'v1_p'         => 'हर लेक्चरर सावधानी से चुना जाता है और हर सत्र वास्तविक प्रगति के लिए डिज़ाइन किया गया है।',
        'v2_title'     => 'सुलभता',
        'v2_p'         => 'लचीले शेड्यूल और कई भाषा विकल्पों के साथ श्रीलंका में कहीं से भी सीखें।',
        'v3_title'     => 'छात्र सहायता',
        'v3_p'         => 'पंजीकरण से बुकिंग तक, हमारी टीम और प्लेटफ़ॉर्म आपकी सफलता में मदद के लिए बने हैं।',
        'v4_title'     => 'निरंतर विकास',
        'v4_p'         => 'हम AI अभ्यास उपकरण, प्रगति ट्रैकिंग और अधिक के साथ प्लेटफ़ॉर्म को लगातार सुधारते रहते हैं।',
        'cta_p'        => 'सिपवे कैंपस के साथ अपनी अंग्रेजी सीखने की यात्रा शुरू करने के लिए तैयार हैं?',
        'cta_btn_login'=> 'पैकेज देखें',
        'cta_btn_guest'=> 'अभी पंजीकरण करें',
        'your_lang'    => 'आपकी भाषा',
    ],
    'ru' => [
        'page_title'   => 'О Sipway Campus',
        'hero_title'   => 'О Sipway Campus',
        'hero_p'       => 'Мы даём студентам по всему Шри-Ланке возможность учиться английскому на живых занятиях с экспертами — в любое время и в любом месте.',
        'story_title'  => 'Наша история',
        'story_p1'     => 'Sipway Campus был основан с простой миссией: сделать качественное английское образование доступным для каждого студента, независимо от местоположения. Мы соединяем учеников с опытными преподавателями через интерактивные живые занятия, гибкие пакеты и современные инструменты обучения.',
        'story_p2'     => 'Готовитесь ли вы к экзаменам, улучшаете разговорный английский или развиваете профессиональные навыки общения — Sipway Campus поддерживает вас на каждом шагу.',
        'numbers_title'=> 'В цифрах',
        'stat1'        => 'Активных студентов',
        'stat2'        => 'Эксперт-преподавателей',
        'stat3'        => 'Поддерживаемых языков',
        'stat4'        => 'Завершённых занятий',
        'values_title' => 'Наши ценности',
        'v1_title'     => 'Качество прежде всего',
        'v1_p'         => 'Каждый преподаватель тщательно отбирается, а каждое занятие рассчитано на реальный прогресс.',
        'v2_title'     => 'Доступность',
        'v2_p'         => 'Учитесь из любой точки Шри-Ланки с гибким расписанием и поддержкой нескольких языков.',
        'v3_title'     => 'Поддержка студентов',
        'v3_p'         => 'От регистрации до записи — наша команда и платформа созданы, чтобы помочь вам добиться успеха.',
        'v4_title'     => 'Постоянный рост',
        'v4_p'         => 'Мы постоянно улучшаем платформу с помощью ИИ-инструментов практики, отслеживания прогресса и многого другого.',
        'cta_p'        => 'Готовы начать путь изучения английского с Sipway Campus?',
        'cta_btn_login'=> 'Смотреть пакеты',
        'cta_btn_guest'=> 'Зарегистрироваться',
        'your_lang'    => 'Ваш язык',
    ],
    'ar' => [
        'page_title'   => 'عن حرم سيبواي',
        'hero_title'   => 'عن حرم سيبواي',
        'hero_p'       => 'تمكين الطلاب في جميع أنحاء سريلانكا من خلال جلسات تعلم اللغة الإنجليزية الحية بقيادة خبراء — في أي وقت ومن أي مكان.',
        'story_title'  => 'قصتنا',
        'story_p1'     => 'تأسس حرم سيبواي بمهمة بسيطة: جعل تعليم اللغة الإنجليزية عالي الجودة في متناول كل طالب، بغض النظر عن الموقع. نربط المتعلمين بمحاضرين ذوي خبرة من خلال جلسات حية تفاعلية وباقات مرنة وأدوات تعليمية حديثة.',
        'story_p2'     => 'سواء كنت تستعد للامتحانات أو تحسن الإنجليزية المنطوقة أو تبني مهارات التواصل المهني، فإن حرم سيبواي مصمم لدعم رحلتك في كل خطوة.',
        'numbers_title'=> 'بالأرقام',
        'stat1'        => 'طلاب نشطون',
        'stat2'        => 'محاضرون خبراء',
        'stat3'        => 'لغات مدعومة',
        'stat4'        => 'جلسات مكتملة',
        'values_title' => 'قيمنا',
        'v1_title'     => 'الجودة أولاً',
        'v1_p'         => 'يتم اختيار كل محاضر بعناية وتصميم كل جلسة لتحقيق تقدم حقيقي.',
        'v2_title'     => 'سهولة الوصول',
        'v2_p'         => 'تعلم من أي مكان في سريلانكا بجداول مرنة وخيارات لغات متعددة.',
        'v3_title'     => 'دعم الطلاب',
        'v3_p'         => 'من التسجيل إلى الحجز، فريقنا ومنصتنا مبنيان لمساعدتك على النجاح.',
        'v4_title'     => 'نمو مستمر',
        'v4_p'         => 'نواصل تحسين المنصة بأدوات تدريب الذكاء الاصطناعي وتتبع التقدم والمزيد.',
        'cta_p'        => 'هل أنت مستعد لبدء رحلة تعلم الإنجليزية مع حرم سيبواي؟',
        'cta_btn_login'=> 'عرض الباقات',
        'cta_btn_guest'=> 'سجل الآن',
        'your_lang'    => 'لغتك',
    ],
    'ta' => [
        'page_title'   => 'சிப்வே கேம்பஸ் பற்றி',
        'hero_title'   => 'சிப்வே கேம்பஸ் பற்றி',
        'hero_p'       => 'இலங்கை முழுவதும் உள்ள மாணவர்களை நிபுணர்கள் நடத்தும் நேரலை ஆங்கில கற்றல் அமர்வுகளுடன் மேம்படுத்துகிறோம் — எப்போதும், எங்கும்.',
        'story_title'  => 'எங்கள் கதை',
        'story_p1'     => 'சிப்வே கேம்பஸ் ஒரு எளிய நோக்கத்துடன் நிறுவப்பட்டது: இருப்பிடத்தைப் பொருட்படுத்தாமல் ஒவ்வொரு மாணவருக்கும் உயர்தர ஆங்கிலக் கல்வியை அணுகக்கூடியதாக மாற்றுவது. ஊடாடும் நேரலை அமர்வுகள், நெகிழ்வான தொகுப்புகள் மற்றும் நவீன கற்றல் கருவிகள் மூலம் கற்றலாளர்களை அனுபவமுள்ள விரிவுரையாளர்களுடன் இணைக்கிறோம்.',
        'story_p2'     => 'நீங்கள் தேர்வுகளுக்குத் தயாராகிறீர்களா, பேசும் ஆங்கிலத்தை மேம்படுத்துகிறீர்களா அல்லது தொழில்முறை தொடர்பு திறன்களை வளர்க்கிறீர்களா — சிப்வே கேம்பஸ் உங்கள் பயணத்தின் ஒவ்வொரு அடியிலும் ஆதரிக்க வடிவமைக்கப்பட்டுள்ளது.',
        'numbers_title'=> 'எண்களில்',
        'stat1'        => 'செயலில் உள்ள மாணவர்கள்',
        'stat2'        => 'நிபுணர் விரிவுரையாளர்கள்',
        'stat3'        => 'ஆதரிக்கப்படும் மொழிகள்',
        'stat4'        => 'முடிக்கப்பட்ட அமர்வுகள்',
        'values_title' => 'எங்கள் மதிப்புகள்',
        'v1_title'     => 'தரம் முதலில்',
        'v1_p'         => 'ஒவ்வொரு விரிவுரையாளரும் கவனமாகத் தேர்ந்தெடுக்கப்படுகிறார், ஒவ்வொரு அமர்வும் உண்மையான முன்னேற்றத்திற்காக வடிவமைக்கப்பட்டுள்ளது.',
        'v2_title'     => 'அணுகல்',
        'v2_p'         => 'நெகிழ்வான அட்டவணைகள் மற்றும் பல மொழி விருப்பங்களுடன் இலங்கையில் எங்கிருந்தும் கற்றுக்கொள்ளுங்கள்.',
        'v3_title'     => 'மாணவர் ஆதரவு',
        'v3_p'         => 'பதிவு முதல் முன்பதிவு வரை, எங்கள் குழுவும் தளமும் உங்கள் வெற்றியை உதவ கட்டமைக்கப்பட்டுள்ளன.',
        'v4_title'     => 'தொடர்ச்சியான வளர்ச்சி',
        'v4_p'         => 'AI பயிற்சி கருவிகள், முன்னேற்ற கண்காணிப்பு மற்றும் மேலும் பலவற்றுடன் தளத்தை தொடர்ந்து மேம்படுத்துகிறோம்.',
        'cta_p'        => 'சிப்வே கேம்பஸுடன் உங்கள் ஆங்கில கற்றல் பயணத்தைத் தொடங்க தயாரா?',
        'cta_btn_login'=> 'தொகுப்புகளைப் பார்க்க',
        'cta_btn_guest'=> 'இப்போது பதிவு செய்யுங்கள்',
        'your_lang'    => 'உங்கள் மொழி',
    ],
    'si' => [
        'page_title'   => 'සිප්වේ කැම්පස් ගැන',
        'hero_title'   => 'සිප්වේ කැම්පස් ගැන',
        'hero_p'       => 'ශ්‍රී ලංකාව පුරා සිසුන්ට විශේෂඥයින් මගින් පවත්වන සජීවී ඉංග්‍රීසි ඉගෙනුම් සැසි මගින් බලගන්වමු — ඕනෑම වේලාවක, ඕනෑම තැනක.',
        'story_title'  => 'අපේ කතාව',
        'story_p1'     => 'සිප්වේ කැම්පස් සරල මෙහෙවරකින් ආරම්භ විය: ස්ථානය කුමක් වුවත් සෑම සිසුවෙකුටම උසස් තත්ත්වයේ ඉංග්‍රීසි අධ්‍යාපනය ලබා ගත හැකි කිරීම. අපි අන්තර්ක්‍රියාකාරී සජීවී සැසි, නම්‍යශීලී පැකේජ සහ නවීන ඉගෙනුම් මෙවලම් හරහා ඉගෙනුම් ලබන්නන් අත්දැකීම් සහිත ආචාර්යවරුන් සමඟ සම්බන්ධ කරමු.',
        'story_p2'     => 'ඔබ විභාග සඳහා සූදානම් වෙනවාද, කතා කරන ඉංග්‍රීසි වැඩිදියුණු කරනවාද හෝ වෘත්තීය සන්නිවේදන කුසලතා ගොඩනගනවාද — සිප්වේ කැම්පස් ඔබේ ගමනේ සෑම පියවරකදීම සහාය වීමට නිර්මාණය කර ඇත.',
        'numbers_title'=> 'අංක වලින්',
        'stat1'        => 'ක්‍රියාකාරී සිසුන්',
        'stat2'        => 'විශේෂඥ ආචාර්යවරු',
        'stat3'        => 'සහාය දක්වන භාෂා',
        'stat4'        => 'සම්පූර්ණ කළ සැසි',
        'values_title' => 'අපේ අගයන්',
        'v1_title'     => 'ගුණාත්මකභාවය මුල්',
        'v1_p'         => 'සෑම ආචාර්යවරයෙකුම ප්‍රවේශමෙන් තෝරා ගනු ලබන අතර සෑම සැසියක්ම සැබෑ ප්‍රගතිය සඳහා නිර්මාණය කර ඇත.',
        'v2_title'     => 'ප්‍රවේශ්‍යතාව',
        'v2_p'         => 'නම්‍යශීලී කාලසටහන් සහ බහු භාෂා විකල්ප සමඟ ශ්‍රී ලංකාවේ ඕනෑම තැනකින් ඉගෙන ගන්න.',
        'v3_title'     => 'ශිෂ්‍ය සහාය',
        'v3_p'         => 'ලියාපදිංචියේ සිට වෙන්කරවා ගැනීම දක්වා, අපේ කණ්ඩායම සහ වේදිකාව ඔබේ සාර්ථකත්වයට උදව් කිරීමට ගොඩනගා ඇත.',
        'v4_title'     => 'අඛණ්ඩ වර්ධනය',
        'v4_p'         => 'අපි AI පුහුණු මෙවලම්, ප්‍රගති නිරීක්ෂණය සහ තවත් දේ සමඟ වේදිකාව නිරන්තරයෙන් වැඩිදියුණු කරමු.',
        'cta_p'        => 'සිප්වේ කැම්පස් සමඟ ඔබේ ඉංග්‍රීසි ඉගෙනුම් ගමන ආරම්භ කිරීමට සූදානම්ද?',
        'cta_btn_login'=> 'පැකේජ බලන්න',
        'cta_btn_guest'=> 'දැන් ලියාපදිංචි වන්න',
        'your_lang'    => 'ඔබේ භාෂාව',
    ],
    'it' => [
        'page_title'   => 'Informazioni su Sipway Campus',
        'hero_title'   => 'Informazioni su Sipway Campus',
        'hero_p'       => 'Potenziamo gli studenti in tutto lo Sri Lanka con sessioni di apprendimento dell\'inglese dal vivo guidate da esperti — in qualsiasi momento e ovunque.',
        'story_title'  => 'La nostra storia',
        'story_p1'     => 'Sipway Campus è stato fondato con una missione semplice: rendere l\'istruzione inglese di alta qualità accessibile a ogni studente, indipendentemente dalla posizione. Colleghiamo gli studenti a docenti esperti attraverso sessioni live interattive, pacchetti flessibili e strumenti di apprendimento moderni.',
        'story_p2'     => 'Che tu stia preparando esami, migliorando l\'inglese parlato o costruendo competenze di comunicazione professionale, Sipway Campus è progettato per supportare il tuo percorso in ogni passo.',
        'numbers_title'=> 'In numeri',
        'stat1'        => 'Studenti attivi',
        'stat2'        => 'Docenti esperti',
        'stat3'        => 'Lingue supportate',
        'stat4'        => 'Sessioni completate',
        'values_title' => 'I nostri valori',
        'v1_title'     => 'Qualità prima di tutto',
        'v1_p'         => 'Ogni docente è selezionato con cura e ogni sessione è progettata per un vero progresso.',
        'v2_title'     => 'Accessibilità',
        'v2_p'         => 'Impara da qualsiasi luogo in Sri Lanka con orari flessibili e multiple opzioni linguistiche.',
        'v3_title'     => 'Supporto agli studenti',
        'v3_p'         => 'Dalla registrazione alla prenotazione, il nostro team e la piattaforma sono costruiti per aiutarti a riuscire.',
        'v4_title'     => 'Crescita continua',
        'v4_p'         => 'Continuiamo a migliorare la piattaforma con strumenti di pratica AI, monitoraggio dei progressi e altro ancora.',
        'cta_p'        => 'Pronto a iniziare il tuo percorso di apprendimento dell\'inglese con Sipway Campus?',
        'cta_btn_login'=> 'Vedi i pacchetti',
        'cta_btn_guest'=> 'Registrati ora',
        'your_lang'    => 'La tua lingua',
    ],
];

// Fallback to English if translation missing
$txt = $t[$currentLang] ?? $t['en'];
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($currentLang); ?>" data-lang="<?php echo htmlspecialchars($currentLang); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($txt['page_title']); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@400;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root {
  --sidebar-bg: #1e1b4b;
  --sidebar-bg-2: #2e2a5e;
  --sidebar-accent: linear-gradient(135deg, #a855f7, #ec4899);
  --sidebar-text: #ffffff;
  --sidebar-text-active: #ffffff;
  --topbar-bg: #0b0a1f;
  --bg: #f4f0ff;
  --bg-soft: #faf8ff;
  --card: #ffffff;
  --text: #1e1b4b;
  --muted: #6b7280;
  --muted-2: #9ca3af;
  --line: #e9e5f5;
  --line-soft: #f3f0fa;
  --purple: #7c3aed;
  --purple-soft: #f3e8ff;
  --pink: #ec4899;
  --coral: #e11d48;
  --success: #10b981;
  --success-soft: #d1fae5;
  --amber: #f59e0b;
  --amber-soft: #fef3c7;
  --blue: #3b82f6;
  --blue-soft: #dbeafe;
  --radius-lg: 20px;
  --radius-md: 14px;
  --radius-sm: 10px;
  --shadow-card: 0 8px 30px -8px rgba(124, 58, 237, 0.08);
  --shadow-hover: 0 16px 40px -12px rgba(124, 58, 237, 0.14);
  --ease: cubic-bezier(.4,0,.2,1);
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: 'Inter', 'Noto Sans Sinhala', -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
  background: var(--bg);
  color: var(--text);
  -webkit-font-smoothing: antialiased;
  min-height: 100vh;
}
a { color: inherit; text-decoration: none; }
@keyframes fadeUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
@keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
@keyframes scaleIn { from { opacity:0; transform:scale(0.94); } to { opacity:1; transform:scale(1); } }
@keyframes spin { to { transform: rotate(360deg); } }
.animate-up { animation: fadeUp .5s var(--ease) both; }
.delay-1 { animation-delay: .08s; }
.delay-2 { animation-delay: .16s; }
/* ========== TOPBAR ========== */
.topbar {
  height: 64px;
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 0 22px;
  background: var(--topbar-bg);
  position: sticky;
  top: 0;
  z-index: 50;
}
.burger {
  background: none;
  border: none;
  cursor: pointer;
  padding: 8px;
  display: flex;
  border-radius: 10px;
  color: #e0e7ff;
  flex-shrink: 0;
  transition: background .2s;
}
.burger:hover { background: rgba(255,255,255,0.08); }
.burger svg { width: 22px; height: 22px; }
.logo {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 800;
  color: #fff;
  font-size: 15.5px;
  letter-spacing: -0.3px;
  flex-shrink: 0;
  white-space: nowrap;
}
.logo-mark {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  background: linear-gradient(135deg, #ef4444, #dc2626);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 13px;
  font-weight: 800;
  box-shadow: 0 4px 12px -3px rgba(239,68,68,0.5);
}
.lang-nav-badge {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 5px 14px 5px 8px;
  border-radius: 999px;
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.1);
  margin-left: 6px;
  flex-shrink: 0;
  cursor: default;
  transition: background .2s;
}
.lang-nav-badge:hover { background: rgba(255,255,255,0.1); }
.lang-flag-big img {
  width: 26px;
  height: 18px;
  object-fit: cover;
  border-radius: 3px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.2);
  display: block;
}
.lang-nav-text { display: flex; flex-direction: column; line-height: 1.15; }
.lang-nav-label { font-size: 12.5px; font-weight: 800; color: #fff; }
.lang-nav-sub { font-size: 9px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.4px; }
.top-links {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 22px;
}
.top-links a {
  font-size: 13px;
  font-weight: 600;
  color: #94a3b8;
  transition: color .2s;
}
.top-links a:hover { color: #fff; }
.top-links a.current { color: #c4b5fd; }
.notif-btn {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: none;
  background: rgba(255,255,255,0.06);
  color: #e0e7ff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  position: relative;
  transition: background .2s;
}
.notif-btn:hover { background: rgba(255,255,255,0.12); }
.notif-btn svg { width: 18px; height: 18px; }
.notif-dot {
  position: absolute;
  top: 6px;
  right: 6px;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #ef4444;
  border: 2px solid var(--topbar-bg);
}
.user-menu {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  padding: 5px 10px;
  border-radius: 999px;
  position: relative;
  flex-shrink: 0;
  transition: background .2s;
}
.user-menu:hover { background: rgba(255,255,255,0.08); }
.avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, #a855f7, #ec4899);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  overflow: hidden;
  font-weight: 700;
  font-size: 13px;
}
.avatar img { width: 100%; height: 100%; object-fit: cover; }
.avatar svg { width: 16px; height: 16px; }
.user-menu .chev { width: 13px; height: 13px; color: #94a3b8; }
.user-name { font-size: 13px; font-weight: 700; color: #fff; }
.dropdown {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-hover);
  min-width: 190px;
  padding: 6px;
  display: none;
  z-index: 60;
  animation: scaleIn .2s var(--ease);
}
.dropdown.show { display: block; }
.dropdown a {
  display: block;
  padding: 11px 13px;
  font-size: 13.5px;
  font-weight: 600;
  border-radius: 9px;
  color: var(--text);
  transition: background .15s;
}
.dropdown a:hover { background: var(--purple-soft); }
.dropdown a.danger { color: #b91c1c; }
.topbar-login-btn {
  padding: 10px 20px;
  border: none;
  border-radius: 999px;
  flex-shrink: 0;
  background: linear-gradient(135deg, #a855f7, #ec4899);
  color: #fff;
  font-weight: 800;
  font-size: 13px;
  letter-spacing: 0.3px;
  cursor: pointer;
  box-shadow: 0 8px 18px -5px rgba(168,85,247,0.5);
  transition: filter .2s, transform .15s;
}
.topbar-login-btn:hover { filter: brightness(1.06); transform: translateY(-1px); }
/* ========== LAYOUT ========== */
.shell { display: flex; min-height: calc(100vh - 64px); }
.sidebar {
  width: 260px;
  flex-shrink: 0;
  background: linear-gradient(180deg, #0f0c29 0%, #1a1440 50%, #1e1b4b 100%);
  padding: 22px 14px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  position: sticky;
  top: 64px;
  align-self: flex-start;
  height: calc(100vh - 64px);
  overflow-y: auto;
  transition: transform .3s var(--ease);
}
.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-radius: 12px;
  font-weight: 600;
  font-size: 14px;
  color: var(--sidebar-text);
  cursor: pointer;
  transition: all .2s var(--ease);
}
.nav-item svg { width: 19px; height: 19px; flex-shrink: 0; opacity: 0.85; color: #fff; }
.nav-item:hover { background: rgba(255,255,255,0.06); color: #fff; }
.nav-item.active {
  background: linear-gradient(135deg, #a855f7, #ec4899);
  color: #fff;
  box-shadow: 0 8px 24px -6px rgba(168,85,247,0.5);
}
.nav-item.active svg { opacity: 1; }
.nav-item .badge-new {
  margin-left: auto;
  font-size: 10px;
  font-weight: 800;
  padding: 3px 8px;
  border-radius: 999px;
  background: linear-gradient(135deg, #a855f7, #6366f1);
  color: #fff;
  letter-spacing: 0.3px;
}
.side-divider {
  height: 1px;
  background: rgba(255,255,255,0.08);
  margin: 14px 8px;
}
.side-illustration {
  margin-top: auto;
  padding: 16px 8px 8px;
  text-align: center;
}
.side-help-card {
  margin-top: 12px;
  padding: 16px;
  border-radius: 16px;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.08);
  text-align: center;
}
.side-help-card h4 {
  font-size: 13.5px;
  font-weight: 700;
  color: #fff;
  margin-bottom: 4px;
}
.side-help-card p {
  font-size: 11.5px;
  color: #94a3b8;
  margin-bottom: 12px;
}
.side-help-card a {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: 999px;
  background: rgba(255,255,255,0.08);
  border: 1px solid rgba(255,255,255,0.12);
  color: #e0e7ff;
  font-size: 12px;
  font-weight: 700;
  transition: background .2s;
}
.side-help-card a:hover { background: rgba(255,255,255,0.14); }
.backdrop {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(15,12,41,0.5);
  backdrop-filter: blur(3px);
  z-index: 45;
}
.backdrop.show { display: block; animation: fadeIn .25s; }
.main {
  flex: 1;
  padding: 28px clamp(16px, 3vw, 36px) 50px;
  min-width: 0;
  background: linear-gradient(160deg, #f4f0ff 0%, #faf8ff 40%, #f0eaff 100%);
  position: relative;
  overflow: hidden;
}
.main::before {
  content: '';
  position: absolute;
  top: -80px;
  right: -60px;
  width: 320px;
  height: 320px;
  background: radial-gradient(circle, rgba(168,85,247,0.12) 0%, transparent 70%);
  pointer-events: none;
}
/* ===== About page content ===== */
.hero {
  background: linear-gradient(135deg, #0f0c29 0%, #7c3aed 50%, #ec4899 100%);
  color: #fff;
  padding: 48px 30px;
  text-align: center;
  border-radius: var(--radius-lg);
  margin-bottom: 26px;
  position: relative;
  z-index: 1;
  box-shadow: 0 16px 40px -12px rgba(124,58,237,0.35);
}
.hero h1 {
  font-size: 1.9rem;
  font-weight: 800;
  margin: 0 0 12px 0;
  letter-spacing: -0.4px;
}
.hero p {
  font-size: 1rem;
  color: rgba(255,255,255,0.85);
  max-width: 600px;
  margin: 0 auto;
  line-height: 1.6;
}
.panel {
  background: var(--card);
  border: 1px solid var(--line-soft);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-card);
  padding: 26px clamp(20px,4vw,36px);
  margin-bottom: 22px;
  position: relative;
  z-index: 1;
  transition: box-shadow .3s;
}
.panel:hover { box-shadow: var(--shadow-hover); }
.panel h2 {
  font-size: 17px;
  font-weight: 800;
  margin: 0 0 14px 0;
  color: var(--text);
  display: flex;
  align-items: center;
  gap: 10px;
}
.panel h2::before {
  content: '';
  width: 6px;
  height: 18px;
  border-radius: 3px;
  background: linear-gradient(180deg, #a855f7, #ec4899);
  display: inline-block;
}
.panel p {
  font-size: 14px;
  color: var(--text);
  line-height: 1.7;
  margin: 0 0 12px 0;
}
.panel p:last-child { margin-bottom: 0; }
.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px,1fr));
  gap: 16px;
  margin-top: 8px;
}
.stat-card {
  background: var(--purple-soft);
  border-radius: var(--radius-md);
  padding: 20px;
  text-align: center;
}
.stat-card .num {
  font-size: 1.7rem;
  font-weight: 800;
  background: linear-gradient(135deg, #a855f7, #ec4899);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.stat-card .label {
  font-size: 11.5px;
  font-weight: 700;
  color: var(--muted);
  margin-top: 4px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}
.values {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px,1fr));
  gap: 14px;
  margin-top: 8px;
}
.value-card {
  border: 1px solid var(--line-soft);
  border-radius: var(--radius-md);
  padding: 18px;
  background: var(--bg-soft);
  transition: border-color .2s, box-shadow .2s;
}
.value-card:hover {
  border-color: #c4b5fd;
  box-shadow: 0 6px 20px -8px rgba(124,58,237,0.15);
}
.value-card h3 {
  font-size: 14px;
  font-weight: 800;
  color: var(--text);
  margin: 0 0 6px 0;
}
.value-card p {
  font-size: 12.5px;
  color: var(--muted);
  margin: 0;
  line-height: 1.6;
}
.cta-card {
  background: linear-gradient(135deg, #f3e8ff, #fce7f3);
  border: 1px solid #e9d5ff;
  border-radius: var(--radius-lg);
  padding: 28px;
  text-align: center;
  position: relative;
  z-index: 1;
}
.cta-card p {
  color: #5b21b6;
  font-size: 14px;
  margin: 0 0 16px 0;
  font-weight: 600;
}
.btn-primary {
  display: inline-block;
  padding: 12px 28px;
  border: none;
  border-radius: var(--radius-sm);
  background: linear-gradient(135deg, #a855f7, #ec4899);
  color: #fff;
  font-weight: 800;
  font-size: 13.5px;
  letter-spacing: 0.4px;
  cursor: pointer;
  text-decoration: none;
  box-shadow: 0 10px 22px -6px rgba(168,85,247,0.45);
  transition: filter .2s, transform .15s;
}
.btn-primary:hover { filter: brightness(1.06); transform: translateY(-1px); }

/* ========== AUTH MODAL ========== */
.auth-modal-overlay {
  display: none; position: fixed; inset: 0;
  background: rgba(15,12,41,0.65); backdrop-filter: blur(8px);
  z-index: 400; align-items: center; justify-content: center;
  padding: 20px; overflow-y: auto;
}
.auth-modal-overlay.show { display: flex; animation: fadeIn .25s; }
.auth-modal-close {
  position: absolute; top: 14px; right: 14px; width: 34px; height: 34px;
  border-radius: 11px; border: none; background: var(--purple-soft); color: var(--purple);
  cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 2;
  transition: all .25s;
}
.auth-modal-close:hover { background: #fce7f3; color: #be185d; transform: rotate(90deg); }
.gp-card {
  background: var(--card); border-radius: var(--radius-lg);
  padding: clamp(28px, 4vw, 40px) clamp(22px, 4vw, 34px);
  box-shadow: 0 40px 90px -20px rgba(0,0,0,0.4); border: 1px solid var(--line-soft);
  width: 440px; max-width: 100%; position: relative; margin: auto;
  animation: scaleIn .3s var(--ease);
}
.gp-hidden { display: none !important; }
.gp-head { text-align: center; margin-bottom: 24px; }
.gp-head .gp-eyebrow {
  font-size: 12.5px; font-weight: 700; color: #a855f7;
  text-transform: uppercase; letter-spacing: 1.2px; margin: 0 0 8px 0;
}
.gp-head h1 {
  font-size: clamp(20px, 3vw, 25px); color: var(--text); margin: 0 0 6px 0;
  font-weight: 800; letter-spacing: -0.4px;
}
.gp-head p { color: var(--muted); font-size: 13px; line-height: 1.6; margin: 0; }
.gp-field { margin-bottom: 15px; position: relative; }
.gp-field label { display: block; font-size: 12.5px; color: var(--text); margin-bottom: 7px; font-weight: 600; }
.gp-input-shell { position: relative; display: flex; align-items: center; }
.gp-input-icon {
  position: absolute; left: 14px; width: 18px; height: 18px; color: var(--muted-2);
  pointer-events: none; display: flex; flex-shrink: 0;
}
.gp-field input, .gp-field select {
  width: 100%; padding: 12.5px 14px 12.5px 40px; border-radius: var(--radius-sm);
  border: 1.5px solid var(--line); font-size: 14px; font-family: inherit; outline: none;
  background: var(--bg-soft); color: var(--text);
  transition: border-color .15s, box-shadow .15s, background .15s;
}
.gp-field select { padding-left: 14px; cursor: pointer; }
.gp-field input::placeholder { color: var(--muted-2); }
.gp-field input:focus, .gp-field select:focus {
  border-color: #a855f7; background: var(--card);
  box-shadow: 0 0 0 4px rgba(168,85,247,0.14);
}
.gp-field input.gp-invalid { border-color: #ef4444; background: #fef2f2; }
.gp-field input.gp-valid { border-color: var(--success); }
.gp-toggle-pass {
  position: absolute; right: 12px; background: none; border: none; cursor: pointer;
  color: var(--muted-2); padding: 6px; display: flex; align-items: center; border-radius: 6px;
}
.gp-toggle-pass:hover { color: var(--purple); background: var(--purple-soft); }
.gp-toggle-pass svg { width: 18px; height: 18px; }
.gp-error {
  font-size: 12px; color: #ef4444; margin-top: 6px; display: none;
  align-items: center; gap: 5px; font-weight: 500;
}
.gp-error.show { display: flex; }
.gp-strength-meter { display: flex; gap: 4px; margin-top: 8px; height: 4px; }
.gp-strength-meter span { flex: 1; border-radius: 2px; background: var(--line); transition: background .2s; }
.gp-strength-label { font-size: 11px; color: var(--muted-2); margin-top: 5px; font-weight: 600; }
.gp-row-inline {
  display: flex; justify-content: space-between; align-items: center;
  margin: 2px 0 18px 0; flex-wrap: wrap; gap: 8px;
}
.gp-checkbox-label {
  font-size: 13px; color: var(--muted); display: flex; align-items: center;
  gap: 7px; font-weight: 500; cursor: pointer; user-select: none;
}
.gp-checkbox-label input { width: 16px; height: 16px; accent-color: #a855f7; cursor: pointer; }
.gp-row-inline a { font-size: 13px; color: var(--purple); font-weight: 700; }
.gp-row-inline a:hover { color: #7c3aed; }
.gp-btn-primary {
  width: 100%; padding: 14px; border: none; border-radius: var(--radius-sm);
  background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%); color: #fff;
  font-weight: 800; font-size: 14px; letter-spacing: 0.4px; cursor: pointer;
  transition: transform .12s, box-shadow .2s, filter .15s;
  box-shadow: 0 10px 24px -6px rgba(168,85,247,0.4);
  display: flex; align-items: center; justify-content: center; gap: 8px;
}
.gp-btn-primary:hover { filter: brightness(1.04); }
.gp-btn-primary:disabled { opacity: .7; cursor: not-allowed; }
.gp-btn-primary .gp-spinner {
  width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.4);
  border-top-color: #fff; border-radius: 50%; animation: spin .7s linear infinite; display: none;
}
.gp-btn-primary.loading .gp-spinner { display: inline-block; }
.gp-btn-primary.loading .gp-btn-text { opacity: 0.85; }
.gp-switch-row { text-align: center; margin-top: 22px; font-size: 13.5px; color: var(--muted); }
.gp-switch-row a { color: #a855f7; font-weight: 800; cursor: pointer; }
.gp-switch-row a:hover { text-decoration: underline; }
.gp-lang-select { position: relative; }
.gp-lang-trigger {
  width: 100%; padding: 12.5px 14px 12.5px 40px; border-radius: var(--radius-sm);
  border: 1.5px solid var(--line); font-size: 14px; font-family: inherit; outline: none;
  background: var(--bg-soft); color: var(--text); cursor: pointer;
  display: flex; align-items: center; justify-content: space-between; gap: 8px;
  transition: border-color .15s, box-shadow .15s; user-select: none;
}
.gp-lang-trigger.open {
  border-color: #a855f7; background: var(--card);
  box-shadow: 0 0 0 4px rgba(168,85,247,0.14);
}
.gp-lang-trigger .gp-lang-current {
  display: flex; align-items: center; gap: 9px; overflow: hidden; white-space: nowrap;
}
.gp-lang-flag {
  width: 22px; height: 16px; flex-shrink: 0; border-radius: 3px;
  box-shadow: 0 0 0 1px rgba(0,0,0,0.12); overflow: hidden;
}
.gp-lang-caret { width: 16px; height: 16px; color: var(--muted-2); flex-shrink: 0; transition: transform .18s; }
.gp-lang-trigger.open .gp-lang-caret { transform: rotate(180deg); }
.gp-lang-options {
  position: absolute; top: calc(100% + 6px); left: 0; right: 0;
  background: var(--card); border: 1.5px solid var(--line); border-radius: var(--radius-sm);
  box-shadow: var(--shadow-hover); z-index: 20; max-height: 220px; overflow-y: auto;
  padding: 6px; opacity: 0; transform: translateY(-6px); pointer-events: none;
  transition: opacity .15s, transform .15s;
}
.gp-lang-options.open { opacity: 1; transform: translateY(0); pointer-events: auto; }
.gp-lang-option {
  display: flex; align-items: center; justify-content: space-between; gap: 10px;
  padding: 10px 12px; border-radius: 8px; cursor: pointer; font-size: 14px;
  font-weight: 500; color: var(--text);
}
.gp-lang-option:hover { background: var(--purple-soft); }
.gp-lang-option-left { display: flex; align-items: center; gap: 10px; }
.gp-lang-tick { width: 16px; height: 16px; color: #a855f7; flex-shrink: 0; opacity: 0; transition: opacity .12s; }
.gp-lang-option.selected { background: var(--purple-soft); font-weight: 700; color: #6b21a8; }
.gp-lang-option.selected .gp-lang-tick { opacity: 1; }
.gp-lang-option-loading { padding: 12px; font-size: 13px; color: var(--muted-2); text-align: center; }
.gp-toast {
  position: fixed; top: 20px; left: 50%; transform: translateX(-50%) translateY(-16px);
  background: #0f0c29; color: #fff; padding: 13px 22px; border-radius: 10px;
  font-size: 13.5px; font-weight: 600; opacity: 0; pointer-events: none;
  transition: opacity .25s, transform .25s; z-index: 600;
  box-shadow: 0 12px 30px rgba(0,0,0,0.3); display: flex; align-items: center; gap: 10px; max-width: 90vw;
}
.gp-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
.gp-toast.gp-error-toast { background: #ef4444; }

/* ========== HOW TO REGISTER VIDEO MODAL ========== */
.howto-modal-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(15,12,41,0.75);
  backdrop-filter: blur(8px);
  z-index: 450;
  align-items: center;
  justify-content: center;
  padding: 20px;
}
.howto-modal-overlay.show { display: flex; animation: fadeIn .25s; }

.howto-modal-box {
  background: var(--card);
  border-radius: 20px;
  width: 100%;
  max-width: 720px;
  max-height: 90vh;
  overflow: hidden;
  box-shadow: 0 40px 90px -20px rgba(0,0,0,0.5);
  animation: scaleIn .3s cubic-bezier(.34,1.56,.64,1);
  display: flex;
  flex-direction: column;
}

.howto-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  background: linear-gradient(135deg, #0f0c29 0%, #a855f7 100%);
  color: #fff;
  flex-shrink: 0;
}

.howto-modal-header h3 {
  font-size: 16px;
  font-weight: 800;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
}

.howto-modal-header h3 span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.howto-modal-close {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  border: none;
  background: rgba(255,255,255,0.18);
  color: #fff;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all .25s;
  flex-shrink: 0;
}

.howto-modal-close:hover {
  background: rgba(255,255,255,0.3);
  transform: rotate(90deg);
}

.howto-modal-body {
  padding: 0;
  background: #000;
  overflow: auto;
}

.howto-modal-body video {
  width: 100%;
  max-height: 70vh;
  display: block;
  background: #000;
}

.howto-modal-body iframe {
  width: 100%;
  aspect-ratio: 16 / 9;
  min-height: 320px;
  border: none;
  background: #000;
  display: block;
}

.howto-modal-footer {
  padding: 14px 20px;
  text-align: center;
  background: var(--bg-soft);
  border-top: 1px solid var(--line-soft);
  flex-shrink: 0;
}

.howto-modal-footer p {
  font-size: 13px;
  color: var(--muted);
  margin: 0 0 10px 0;
  font-weight: 600;
}

.howto-modal-footer .btn-primary {
  padding: 10px 22px;
  font-size: 13px;
  border: none;
  border-radius: var(--radius-sm);
  background: linear-gradient(135deg, #a855f7, #ec4899);
  color: #fff;
  font-weight: 800;
  cursor: pointer;
  transition: filter .2s, transform .15s;
}
.howto-modal-footer .btn-primary:hover { filter: brightness(1.06); transform: translateY(-1px); }

@media (max-width: 560px) {
  .howto-modal-overlay { padding: 10px; }
  .howto-modal-box { border-radius: 16px; max-height: 94vh; }
  .howto-modal-header { padding: 13px 14px; }
  .howto-modal-header h3 { font-size: 14px; }
  .howto-modal-body iframe { min-height: 220px; }
  .howto-modal-footer { padding: 12px 14px; }
}

/* ========== RESPONSIVE ========== */
@media (max-width: 1100px) {
  .stats { grid-template-columns: repeat(auto-fit, minmax(140px,1fr)); }
}
@media (max-width: 900px) {
  .top-links { display: none; }
}
@media (max-width: 820px) {
  .sidebar {
    position: fixed;
    left: 0;
    top: 64px;
    transform: translateX(-100%);
    width: 280px;
    height: calc(100vh - 64px);
    z-index: 46;
    box-shadow: 0 0 40px rgba(0,0,0,0.3);
  }
  .sidebar.open { transform: translateX(0); }
  .lang-nav-badge { padding: 4px 10px 4px 6px; }
  .lang-flag-big img { width: 22px; height: 15px; }
  .lang-nav-label { font-size: 11.5px; }
}
@media (max-width: 560px) {
  .topbar { padding: 0 10px; gap: 8px; }
  .logo span:not(.logo-mark) { display: none; }
  .lang-nav-text { display: none; }
  .user-name { display: none; }
  .main { padding: 18px 12px 36px; }
  .hero { padding: 34px 18px; }
  .hero h1 { font-size: 1.5rem; }
  .panel { padding: 18px; }
}
.logo {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
}

.logo-image {
    width: 100px;
    height: 100px;
    object-fit: contain;
    border-radius: 10px;
}
</style>
</head>
<body>
<!-- ==================== TOPBAR ==================== -->
<header class="topbar">
  <button class="burger" id="burgerBtn" aria-label="Toggle menu">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
  </button>
<a href="index.php" class="logo">
    <img src="images/logo.png" alt="Lingora Logo" class="logo-image">
    <span></span>
</a>
  <?php if ($isLoggedIn): ?>
  <div class="lang-nav-badge" title="<?php echo htmlspecialchars($txt['your_lang']); ?>">
    <span class="lang-flag-big">
      <img src="<?php echo htmlspecialchars($langFlag); ?>" alt="<?php echo htmlspecialchars($langLabel); ?>" width="26" height="18">
    </span>
    <div class="lang-nav-text">
      <span class="lang-nav-label"><?php echo htmlspecialchars($langLabel); ?></span>
      <span class="lang-nav-sub"><?php echo htmlspecialchars($txt['your_lang']); ?></span>
    </div>
  </div>
  <?php endif; ?>
  <nav class="top-links">
    <a href="about_sipway_campus.php" class="current">About Sipway Campus</a>
    <a href="terms_of_use.php">Terms of Use</a>
    <a href="privacy_policy.php">Privacy Policy</a>
  </nav>
  <?php if ($isLoggedIn): ?>

  <div class="user-menu" id="userMenu">
    <span class="avatar">
      <?php if ($photoUrl): ?>
        <img src="<?php echo $photoUrl; ?>" alt="<?php echo $fullNameSafe; ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
        <svg style="display:none;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
      <?php else: ?>
        <?php echo strtoupper(substr($firstName, 0, 1)); ?>
      <?php endif; ?>
    </span>
    <span class="user-name">Hi, <?php echo $firstName; ?></span>
    <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
    <div class="dropdown" id="userDropdown">
      <a href="edit_profile.php">✏️ Edit Profile</a>
      <a href="student_logout.php" class="danger">Log out</a>
    </div>
  </div>
  <?php else: ?>
  <button class="topbar-login-btn" onclick="openAuthModal(false)">Login / Register</button>
  <?php endif; ?>
</header>
<div class="backdrop" id="backdrop"></div>
<div class="shell">
  <!-- ==================== SIDEBAR ==================== -->
  <aside class="sidebar" id="sidebar">
    <a href="index.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      Dashboard
    </a>
    <a href="packages.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Packages
    </a>
    <a href="session_progress.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
      My Progress
    </a>
    <a href="practice-ai-video.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
      Practice with AI Video
      <span class="badge-new">New</span>
    </a>
 <a href="student_chat.php" class="nav-item">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
  </svg>
  Chat with Admin
</a>
    <div class="side-divider"></div>
    <a href="faq-support.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      FAQs & Support
    </a>
    <a href="lecturer-details.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Lecturer Details
    </a>
    <a href="javascript:void(0)" class="nav-item" id="howToRegisterBtn">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
        <polyline points="14 2 14 8 20 8"/>
        <line x1="16" y1="13" x2="8" y2="13"/>
        <line x1="16" y1="17" x2="8" y2="17"/>
        <polyline points="10 9 9 9 8 9"/>
      </svg>
      How to Register
    </a>
    <div class="side-illustration">
      <svg viewBox="0 0 200 180" fill="none" xmlns="http://www.w3.org/2000/svg" style="max-width:170px;margin:0 auto;display:block;">
        <ellipse cx="100" cy="160" rx="70" ry="12" fill="rgba(168,85,247,0.15)"/>
        <rect x="40" y="100" width="50" height="45" rx="4" fill="#7c3aed" opacity="0.7"/>
        <rect x="50" y="90" width="50" height="45" rx="4" fill="#a855f7" opacity="0.8"/>
        <rect x="60" y="80" width="50" height="45" rx="4" fill="#c084fc"/>
        <circle cx="140" cy="70" r="35" fill="url(#g1)" opacity="0.9"/>
        <defs>
          <linearGradient id="g1" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#6366f1"/>
            <stop offset="100%" stop-color="#a855f7"/>
          </linearGradient>
        </defs>
      </svg>
    </div>
  </aside>

  <!-- ==================== MAIN ==================== -->
  <main class="main">
    <div class="hero animate-up">
      <h1><?php echo htmlspecialchars($txt['hero_title']); ?></h1>
      <p><?php echo htmlspecialchars($txt['hero_p']); ?></p>
    </div>

    <div class="panel animate-up delay-1">
      <h2><?php echo htmlspecialchars($txt['story_title']); ?></h2>
      <p><?php echo htmlspecialchars($txt['story_p1']); ?></p>
      <p><?php echo htmlspecialchars($txt['story_p2']); ?></p>
    </div>

    <div class="panel animate-up delay-1">
      <h2><?php echo htmlspecialchars($txt['numbers_title']); ?></h2>
      <div class="stats">
        <div class="stat-card">
          <div class="num">500+</div>
          <div class="label"><?php echo htmlspecialchars($txt['stat1']); ?></div>
        </div>
        <div class="stat-card">
          <div class="num">50+</div>
          <div class="label"><?php echo htmlspecialchars($txt['stat2']); ?></div>
        </div>
        <div class="stat-card">
          <div class="num">10+</div>
          <div class="label"><?php echo htmlspecialchars($txt['stat3']); ?></div>
        </div>
        <div class="stat-card">
          <div class="num">1000+</div>
          <div class="label"><?php echo htmlspecialchars($txt['stat4']); ?></div>
        </div>
      </div>
    </div>

    <div class="panel animate-up delay-2">
      <h2><?php echo htmlspecialchars($txt['values_title']); ?></h2>
      <div class="values">
        <div class="value-card">
          <h3>🎓 <?php echo htmlspecialchars($txt['v1_title']); ?></h3>
          <p><?php echo htmlspecialchars($txt['v1_p']); ?></p>
        </div>
        <div class="value-card">
          <h3>🌍 <?php echo htmlspecialchars($txt['v2_title']); ?></h3>
          <p><?php echo htmlspecialchars($txt['v2_p']); ?></p>
        </div>
        <div class="value-card">
          <h3>🤝 <?php echo htmlspecialchars($txt['v3_title']); ?></h3>
          <p><?php echo htmlspecialchars($txt['v3_p']); ?></p>
        </div>
        <div class="value-card">
          <h3>🚀 <?php echo htmlspecialchars($txt['v4_title']); ?></h3>
          <p><?php echo htmlspecialchars($txt['v4_p']); ?></p>
        </div>
      </div>
    </div>

    <div class="cta-card animate-up delay-2">
      <p><?php echo htmlspecialchars($txt['cta_p']); ?></p>
      <?php if ($isLoggedIn): ?>
        <a href="packages.php" class="btn-primary"><?php echo htmlspecialchars($txt['cta_btn_login']); ?></a>
      <?php else: ?>
        <button class="btn-primary" onclick="openAuthModal(true)"><?php echo htmlspecialchars($txt['cta_btn_guest']); ?></button>
      <?php endif; ?>
    </div>
  </main>
</div>

<!-- ==================== AUTH MODAL ==================== -->
<div class="auth-modal-overlay" id="authModalOverlay">
  <div class="gp-card" id="gpLoginCard">
    <button class="auth-modal-close" onclick="closeAuthModal()" aria-label="Close">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
    </button>
    <div class="gp-head">
      <p class="gp-eyebrow">Sipway Campus</p>
      <h1>Welcome Back</h1>
      <p>Login to continue your learning journey</p>
    </div>
    <form id="gpLoginForm" novalidate>
      <div class="gp-field">
        <label for="gpLoginEmail">Email</label>
        <div class="gp-input-shell">
          <span class="gp-input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M4 6l8 6 8-6"/></svg></span>
          <input type="email" id="gpLoginEmail" placeholder="you@example.com" autocomplete="email">
        </div>
        <div class="gp-error" id="gpLoginEmailErr">⚠ Valid email එකක් දෙන්න</div>
      </div>
      <div class="gp-field">
        <label for="gpLoginPass">Password</label>
        <div class="gp-input-shell">
          <span class="gp-input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
          <input type="password" id="gpLoginPass" placeholder="••••••••" autocomplete="current-password">
          <button type="button" class="gp-toggle-pass" data-target="gpLoginPass" aria-label="Show password">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
        <div class="gp-error" id="gpLoginPassErr">⚠ Password එක ඇතුළත් කරන්න</div>
      </div>
      <div class="gp-row-inline">
        <label class="gp-checkbox-label"><input type="checkbox"> Remember me</label>
        
      </div>
      <button type="submit" class="gp-btn-primary" id="gpLoginBtn">
        <span class="gp-spinner"></span>
        <span class="gp-btn-text">Login</span>
      </button>
    </form>
    <div class="gp-switch-row">Don't have an account? <a id="gpGoRegister">Register</a></div>
  </div>

  <div class="gp-card gp-hidden" id="gpRegisterCard">
    <button class="auth-modal-close" onclick="closeAuthModal()" aria-label="Close">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
    </button>
    <div class="gp-head">
      <p class="gp-eyebrow">Sipway Campus</p>
      <h1>Create Account</h1>
      <p>Join and start learning with expert teachers</p>
    </div>
    <form id="gpRegisterForm" novalidate enctype="multipart/form-data">
      <div class="gp-field">
        <label for="gpRegName">Full Name</label>
        <div class="gp-input-shell">
          <span class="gp-input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
          <input type="text" id="gpRegName" placeholder="Your full name">
        </div>
        <div class="gp-error" id="gpRegNameErr">⚠ Name එක ඇතුළත් කරන්න</div>
      </div>
      <div class="gp-field">
        <label for="gpRegEmail">Email</label>
        <div class="gp-input-shell">
          <span class="gp-input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M4 6l8 6 8-6"/></svg></span>
          <input type="email" id="gpRegEmail" placeholder="you@example.com">
        </div>
        <div class="gp-error" id="gpRegEmailErr">⚠ Valid email එකක් දෙන්න</div>
      </div>
      <div class="gp-field">
        <label for="gpRegMobile">Mobile</label>
        <div class="gp-input-shell">
          <span class="gp-input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>
          <input type="tel" id="gpRegMobile" placeholder="07XXXXXXXX" maxlength="10">
        </div>
        <div class="gp-error" id="gpRegMobileErr">⚠ 10 digit mobile number එකක් දෙන්න (0XXXXXXXXX)</div>
      </div>
      <div class="gp-field">
        <label>Language</label>
        <div class="gp-lang-select" id="gpLangSelect">
          <div class="gp-lang-trigger" id="gpLangTrigger" tabindex="0" role="combobox" aria-expanded="false">
            <span class="gp-input-icon" style="left:14px;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg></span>
            <span class="gp-lang-current" id="gpLangCurrent">Loading...</span>
            <svg class="gp-lang-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
          </div>
          <ul class="gp-lang-options" id="gpLangOptions" role="listbox"></ul>
          <input type="hidden" id="gpRegLanguage" value="en">
        </div>
      </div>
      <div class="gp-field">
        <label for="gpRegGender">Gender</label>
        <select id="gpRegGender">
          <option value="">Select</option>
          <option value="Male">Male</option>
          <option value="Female">Female</option>
          <option value="Other">Other</option>
        </select>
      </div>
      <div class="gp-field">
        <label for="gpRegAddress">Address (optional)</label>
        <div class="gp-input-shell">
          <span class="gp-input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></span>
          <input type="text" id="gpRegAddress" placeholder="Your address">
        </div>
      </div>
      <div class="gp-field">
        <label for="gpRegPhoto">Profile Photo (optional)</label>
        <input type="file" id="gpRegPhoto" accept="image/*" style="padding-left:14px;">
      </div>
      <div class="gp-field">
        <label for="gpRegPass">Password</label>
        <div class="gp-input-shell">
          <span class="gp-input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
          <input type="password" id="gpRegPass" placeholder="Min 6 characters">
          <button type="button" class="gp-toggle-pass" data-target="gpRegPass" aria-label="Show password">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
        <div class="gp-strength-meter" id="gpStrengthMeter"><span></span><span></span><span></span><span></span></div>
        <div class="gp-strength-label" id="gpStrengthLabel">Minimum 6 characters</div>
        <div class="gp-error" id="gpRegPassErr">⚠ Password අවම වශයෙන් අක්ෂර 6ක් විය යුතුයි</div>
      </div>
      <div class="gp-field">
        <label for="gpRegPass2">Confirm Password</label>
        <div class="gp-input-shell">
          <span class="gp-input-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
          <input type="password" id="gpRegPass2" placeholder="Repeat password">
          <button type="button" class="gp-toggle-pass" data-target="gpRegPass2" aria-label="Show password">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
        <div class="gp-error" id="gpRegPass2Err">⚠ Passwords ගැලපෙන්නේ නැහැ</div>
      </div>
      <button type="submit" class="gp-btn-primary" id="gpRegisterBtn">
        <span class="gp-spinner"></span>
        <span class="gp-btn-text">Create Account</span>
      </button>
    </form>
    <div class="gp-switch-row">Already have an account? <a id="gpGoLogin">Login</a></div>
  </div>
</div>

<div class="gp-toast" id="gpToast"></div>

<!-- ==================== HOW TO REGISTER VIDEO MODAL ==================== -->
<div class="howto-modal-overlay" id="howtoModalOverlay" aria-hidden="true">
  <div class="howto-modal-box" role="dialog" aria-modal="true" aria-labelledby="howtoModalTitle">
    <div class="howto-modal-header">
      <h3>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polygon points="5 3 19 12 5 21 5 3"/>
        </svg>
        <span id="howtoModalTitle">How to Register – Video Guide</span>
      </h3>

      <button class="howto-modal-close" id="howtoModalCloseBtn" aria-label="Close">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M18 6L6 18M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <div class="howto-modal-body" id="howtoModalBody">
      <div style="padding:40px;text-align:center;color:#94a3b8;background:#0f0c29;">
        Video එක load වෙමින්...
      </div>
    </div>

    <div class="howto-modal-footer">
      <p>Video එක බලලා Register කරන්න. ගැටලුවක් තියෙනවා නම් FAQs &amp; Support බලන්න.</p>
      <button class="btn-primary" onclick="closeHowtoModal(); openAuthModal(true);">
        Register Now
      </button>
    </div>
  </div>
</div>

<script>
  const IS_LOGGED_IN = <?php echo json_encode($isLoggedIn); ?>;

  // ========== Auth Modal ==========
  const authOverlay = document.getElementById('authModalOverlay');
  const loginCard = document.getElementById('gpLoginCard');
  const registerCard = document.getElementById('gpRegisterCard');
  const gpToast = document.getElementById('gpToast');

  function openAuthModal(isRegister) {
    authOverlay.classList.add('show');
    if (isRegister) {
      loginCard.classList.add('gp-hidden');
      registerCard.classList.remove('gp-hidden');
    } else {
      registerCard.classList.add('gp-hidden');
      loginCard.classList.remove('gp-hidden');
    }
  }
  function closeAuthModal() { authOverlay.classList.remove('show'); }
  function requireAuth() {
    if (!IS_LOGGED_IN) { openAuthModal(false); return false; }
    return true;
  }
  window.openAuthModal = openAuthModal;
  window.closeAuthModal = closeAuthModal;
  window.requireAuth = requireAuth;
  function goToLogin() { openAuthModal(false); }
  window.goToLogin = goToLogin;

  authOverlay.addEventListener('click', (e) => { if (e.target === authOverlay) closeAuthModal(); });

  // ========== Login / Register form logic ==========
  (function(){
    document.getElementById('gpGoRegister').addEventListener('click', () => openAuthModal(true));
    document.getElementById('gpGoLogin').addEventListener('click', () => openAuthModal(false));

    function showToast(msg, isError){
      gpToast.textContent = (isError ? '⚠ ' : '✓ ') + msg;
      gpToast.classList.toggle('gp-error-toast', !!isError);
      gpToast.classList.add('show');
      clearTimeout(showToast._t);
      showToast._t = setTimeout(()=> gpToast.classList.remove('show'), 2600);
    }
    function isValidEmail(v){ return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()); }
    function isValidMobile(v){ return /^0\d{9}$/.test(v.trim()); }
    function setFieldState(input, errEl, valid){
      input.classList.toggle('gp-invalid', !valid);
      input.classList.toggle('gp-valid', valid);
      errEl.classList.toggle('show', !valid);
    }

    document.querySelectorAll('.gp-toggle-pass').forEach(btn => {
      btn.addEventListener('click', () => {
        const target = document.getElementById(btn.dataset.target);
        const isPass = target.type === 'password';
        target.type = isPass ? 'text' : 'password';
        btn.setAttribute('aria-label', isPass ? 'Hide password' : 'Show password');
      });
    });

    // Language dropdown
    const langSelect = document.getElementById('gpLangSelect');
    const langTrigger = document.getElementById('gpLangTrigger');
    const langOptions = document.getElementById('gpLangOptions');
    const langCurrent = document.getElementById('gpLangCurrent');
    const regLanguage = document.getElementById('gpRegLanguage');

    function normalizeCode(code) { return (code || '').toString().trim().toLowerCase(); }

    const FLAG_SVGS = {
      GB: '<rect width="60" height="40" fill="#012169"/><path d="M0,0 L60,40 M60,0 L0,40" stroke="#fff" stroke-width="10"/><path d="M0,0 L60,40 M60,0 L0,40" stroke="#C8102E" stroke-width="6"/><path d="M30,0 V40 M0,20 H60" stroke="#fff" stroke-width="16"/><path d="M30,0 V40 M0,20 H60" stroke="#C8102E" stroke-width="10"/>',
      DE: '<rect width="60" height="13.34" y="0" fill="#000"/><rect width="60" height="13.33" y="13.33" fill="#DD0000"/><rect width="60" height="13.33" y="26.67" fill="#FFCE00"/>',
      FR: '<rect width="20" height="40" x="0" fill="#002395"/><rect width="20" height="40" x="20" fill="#FFF"/><rect width="20" height="40" x="40" fill="#ED2939"/>',
      CN: '<rect width="60" height="40" fill="#DE2910"/><polygon points="10,6 11.8,11.5 17.5,11.5 12.8,14.8 14.5,20.2 10,17 5.5,20.2 7.2,14.8 2.5,11.5 8.2,11.5" fill="#FFDE00"/>',
      JP: '<rect width="60" height="40" fill="#FFF"/><circle cx="30" cy="20" r="12" fill="#BC002D"/>',
      IN: '<rect width="60" height="13.34" y="0" fill="#FF9933"/><rect width="60" height="13.33" y="13.33" fill="#FFF"/><rect width="60" height="13.33" y="26.67" fill="#138808"/><circle cx="30" cy="20" r="4.5" fill="none" stroke="#000080" stroke-width="1"/><circle cx="30" cy="20" r="1" fill="#000080"/>',
      RU: '<rect width="60" height="13.34" y="0" fill="#FFF"/><rect width="60" height="13.33" y="13.33" fill="#0039A6"/><rect width="60" height="13.33" y="26.67" fill="#D52B1E"/>',
      SA: '<rect width="60" height="40" fill="#006C35"/>',
      LK: '<rect width="60" height="40" fill="#FFB714"/><rect x="0" y="0" width="10" height="40" fill="#8D153A"/><rect x="10" y="0" width="8" height="40" fill="#00534E"/><rect x="20" y="4" width="36" height="32" fill="#8D153A"/>',
      IT: '<rect width="20" height="40" x="0" fill="#009246"/><rect width="20" height="40" x="20" fill="#FFF"/><rect width="20" height="40" x="40" fill="#CE2B37"/>',
      DEFAULT: '<rect width="60" height="40" fill="#e6e2da"/><circle cx="30" cy="20" r="12" fill="none" stroke="#8a93a3" stroke-width="2"/>'
    };
    const LANG_TO_FLAG = {
      en:'GB', zh:'CN', ja:'JP', fr:'FR', hi:'IN', ru:'RU', ar:'SA', ta:'IN', si:'LK', de:'DE', it:'IT'
    };

    function resolveFlagCode(lang) {
      let code = (lang.flag || '').toString().trim().toUpperCase();
      if (code && FLAG_SVGS[code]) return code;
      const langCode = normalizeCode(lang.code);
      if (LANG_TO_FLAG[langCode] && FLAG_SVGS[LANG_TO_FLAG[langCode]]) return LANG_TO_FLAG[langCode];
      return 'DEFAULT';
    }
    function flagImgHtml(code) {
      const key = (code || 'DEFAULT').toUpperCase().trim();
      const inner = FLAG_SVGS[key] || FLAG_SVGS.DEFAULT;
      return '<svg class="gp-lang-flag" viewBox="0 0 60 40" xmlns="http://www.w3.org/2000/svg" width="22" height="16">' + inner + '</svg>';
    }
    function openLangDropdown(){ langOptions.classList.add('open'); langTrigger.classList.add('open'); langTrigger.setAttribute('aria-expanded','true'); }
    function closeLangDropdown(){ langOptions.classList.remove('open'); langTrigger.classList.remove('open'); langTrigger.setAttribute('aria-expanded','false'); }
    langTrigger.addEventListener('click', (e) => { e.stopPropagation(); langOptions.classList.contains('open') ? closeLangDropdown() : openLangDropdown(); });
    document.addEventListener('click', (e) => { if (!langSelect.contains(e.target)) closeLangDropdown(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeLangDropdown(); });

    function selectLangOption(li){
      langOptions.querySelectorAll('.gp-lang-option').forEach(o => { o.classList.remove('selected'); o.setAttribute('aria-selected','false'); });
      li.classList.add('selected'); li.setAttribute('aria-selected','true');
      langCurrent.innerHTML = flagImgHtml(li.dataset.flag) + '<span>' + li.dataset.label + '</span>';
      regLanguage.value = normalizeCode(li.dataset.value);
    }
    function buildLangOption(lang, selectDefault){
      const flagCode = resolveFlagCode(lang);
      const codeNorm = normalizeCode(lang.code);
      const li = document.createElement('li');
      li.className = 'gp-lang-option' + (selectDefault ? ' selected' : '');
      li.setAttribute('role','option');
      li.setAttribute('aria-selected', selectDefault ? 'true' : 'false');
      li.dataset.value = codeNorm;
      li.dataset.flag = flagCode;
      li.dataset.label = lang.label;
      li.innerHTML = '<span class="gp-lang-option-left">' + flagImgHtml(flagCode) + '<span>' + lang.label + '</span></span><svg class="gp-lang-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>';
      li.addEventListener('click', () => { selectLangOption(li); closeLangDropdown(); });
      return li;
    }
    function loadLanguages(){
      fetch('get_languages.php')
        .then(res => res.json())
        .then(data => {
          langOptions.innerHTML = '';
          if (!data.success || !data.languages || data.languages.length === 0) {
            langOptions.innerHTML = '<li class="gp-lang-option-loading">No languages found.</li>';
            langCurrent.innerHTML = flagImgHtml('GB') + '<span>English</span>';
            regLanguage.value = 'en';
            return;
          }
          const defaultLang = data.languages.find(l => normalizeCode(l.code) === 'en') || data.languages[0];
          data.languages.forEach(lang => {
            const isDefault = normalizeCode(lang.code) === normalizeCode(defaultLang.code);
            langOptions.appendChild(buildLangOption(lang, isDefault));
          });
          langCurrent.innerHTML = flagImgHtml(resolveFlagCode(defaultLang)) + '<span>' + defaultLang.label + '</span>';
          regLanguage.value = normalizeCode(defaultLang.code);
        })
        .catch(() => {
          langOptions.innerHTML = '<li class="gp-lang-option-loading">Could not load languages.</li>';
          langCurrent.innerHTML = flagImgHtml('GB') + '<span>English</span>';
          regLanguage.value = 'en';
        });
    }
    loadLanguages();

    // Login
    const loginEmail = document.getElementById('gpLoginEmail');
    const loginPass = document.getElementById('gpLoginPass');
    loginEmail.addEventListener('input', () => { if (loginEmail.value.length) setFieldState(loginEmail, document.getElementById('gpLoginEmailErr'), isValidEmail(loginEmail.value)); });
    loginPass.addEventListener('input', () => { if (loginPass.value.length) setFieldState(loginPass, document.getElementById('gpLoginPassErr'), loginPass.value.length > 0); });

    const loginForm = document.getElementById('gpLoginForm');
    const loginBtn = document.getElementById('gpLoginBtn');
    loginForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      let ok = true;
      const emailOk = isValidEmail(loginEmail.value);
      setFieldState(loginEmail, document.getElementById('gpLoginEmailErr'), emailOk); if (!emailOk) ok = false;
      const passOk = loginPass.value.length > 0;
      setFieldState(loginPass, document.getElementById('gpLoginPassErr'), passOk); if (!passOk) ok = false;
      if (!ok) return;
      loginBtn.classList.add('loading'); loginBtn.disabled = true;
      try {
        const res = await fetch('login.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email: loginEmail.value.trim(), password: loginPass.value })
        });
        const result = await res.json();
        if (result.success) {
          showToast(result.message || 'Login successful! Welcome back.');
          setTimeout(() => { window.location.href = 'about_sipway_campus.php'; }, 600);
        } else {
          showToast(result.message || 'Invalid email or password.', true);
        }
      } catch (err) {
        showToast('Could not connect to the server. Please try again.', true);
      } finally {
        loginBtn.classList.remove('loading'); loginBtn.disabled = false;
      }
    });

    // Register
    const regName = document.getElementById('gpRegName');
    const regEmail = document.getElementById('gpRegEmail');
    const regMobile = document.getElementById('gpRegMobile');
    const regPass = document.getElementById('gpRegPass');
    const regPass2 = document.getElementById('gpRegPass2');
    regMobile.addEventListener('input', () => {
      regMobile.value = regMobile.value.replace(/\D/g, '').slice(0,10);
      if (regMobile.value.length) setFieldState(regMobile, document.getElementById('gpRegMobileErr'), isValidMobile(regMobile.value));
    });
    regName.addEventListener('input', () => { if (regName.value.length) setFieldState(regName, document.getElementById('gpRegNameErr'), regName.value.trim().length > 0); });
    regEmail.addEventListener('input', () => { if (regEmail.value.length) setFieldState(regEmail, document.getElementById('gpRegEmailErr'), isValidEmail(regEmail.value)); });

    const strengthBars = document.querySelectorAll('#gpStrengthMeter span');
    const strengthLabel = document.getElementById('gpStrengthLabel');
    const strengthColors = ['#ef4444', '#f59e0b', '#eab308', '#10b981'];
    const strengthText = ['Weak', 'Fair', 'Good', 'Strong'];
    function scorePassword(v){
      let score = 0;
      if (v.length >= 6) score++;
      if (v.length >= 10) score++;
      if (/[A-Z]/.test(v) && /[0-9]/.test(v)) score++;
      if (/[^A-Za-z0-9]/.test(v)) score++;
      return Math.min(score, 4);
    }
    regPass.addEventListener('input', () => {
      const score = regPass.value.length ? Math.max(1, scorePassword(regPass.value)) : 0;
      strengthBars.forEach((bar, i) => { bar.style.background = i < score ? strengthColors[score-1] : 'var(--line)'; });
      strengthLabel.textContent = regPass.value.length === 0 ? 'Minimum 6 characters' : strengthText[Math.max(0, score-1)];
      if (regPass.value.length) setFieldState(regPass, document.getElementById('gpRegPassErr'), regPass.value.length >= 6);
      if (regPass2.value.length) setFieldState(regPass2, document.getElementById('gpRegPass2Err'), regPass2.value === regPass.value);
    });
    regPass2.addEventListener('input', () => {
      if (regPass2.value.length) setFieldState(regPass2, document.getElementById('gpRegPass2Err'), regPass2.value === regPass.value && regPass2.value.length > 0);
    });

    const registerForm = document.getElementById('gpRegisterForm');
    const registerBtn = document.getElementById('gpRegisterBtn');
    registerForm.addEventListener('submit', async function(e){
      e.preventDefault();
      let ok = true;
      const nameOk = regName.value.trim().length > 0;
      setFieldState(regName, document.getElementById('gpRegNameErr'), nameOk); if (!nameOk) ok = false;
      const emailOk = isValidEmail(regEmail.value);
      setFieldState(regEmail, document.getElementById('gpRegEmailErr'), emailOk); if (!emailOk) ok = false;
      const mobileOk = isValidMobile(regMobile.value);
      setFieldState(regMobile, document.getElementById('gpRegMobileErr'), mobileOk); if (!mobileOk) ok = false;
      const passOk = regPass.value.length >= 6;
      setFieldState(regPass, document.getElementById('gpRegPassErr'), passOk); if (!passOk) ok = false;
      const pass2Ok = regPass2.value === regPass.value && regPass2.value.length > 0;
      setFieldState(regPass2, document.getElementById('gpRegPass2Err'), pass2Ok); if (!pass2Ok) ok = false;
      if (!ok) { showToast('Please fix the highlighted fields.', true); return; }

      registerBtn.classList.add('loading'); registerBtn.disabled = true;
      const fd = new FormData();
      fd.append('fullName', regName.value.trim());
      fd.append('email', regEmail.value.trim());
      fd.append('mobile', regMobile.value.trim());
      fd.append('language', normalizeCode(regLanguage.value));
      fd.append('gender', document.getElementById('gpRegGender').value);
      fd.append('address', document.getElementById('gpRegAddress').value.trim());
      fd.append('password', regPass.value);
      const photoFile = document.getElementById('gpRegPhoto').files[0];
      if (photoFile) fd.append('profilePhoto', photoFile);
      try {
        const res = await fetch('register.php', { method: 'POST', body: fd });
        const result = await res.json();
        if (result.success) {
          showToast(result.message || 'Account created! You can now log in.');
          registerForm.reset();
          [regName, regEmail, regMobile, regPass, regPass2].forEach(i => i.classList.remove('gp-valid','gp-invalid'));
          strengthBars.forEach(bar => bar.style.background = 'var(--line)');
          strengthLabel.textContent = 'Minimum 6 characters';
          loadLanguages();
          setTimeout(() => openAuthModal(false), 900);
        } else if (result.errors) {
          const map = { fullName: [regName,'gpRegNameErr'], email:[regEmail,'gpRegEmailErr'], mobile:[regMobile,'gpRegMobileErr'], password:[regPass,'gpRegPassErr'] };
          Object.keys(result.errors).forEach(key => {
            if (map[key]) {
              const [input, errId] = map[key];
              document.getElementById(errId).textContent = '⚠ ' + result.errors[key];
              setFieldState(input, document.getElementById(errId), false);
            }
          });
          showToast('Please fix the highlighted fields.', true);
        } else {
          showToast(result.message || 'Could not create account.', true);
        }
      } catch (err) {
        showToast('Could not connect to the server. Please try again.', true);
      } finally {
        registerBtn.classList.remove('loading'); registerBtn.disabled = false;
      }
    });
  })();

  // Sidebar
  const sidebar = document.getElementById('sidebar');
  const burgerBtn = document.getElementById('burgerBtn');
  const backdrop = document.getElementById('backdrop');
  function openSidebar(){ sidebar.classList.add('open'); backdrop.classList.add('show'); }
  function closeSidebar(){ sidebar.classList.remove('open'); backdrop.classList.remove('show'); }
  burgerBtn.addEventListener('click', () => {
    sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
  });
  backdrop.addEventListener('click', closeSidebar);

  // User dropdown (logged-in only)
  const userMenu = document.getElementById('userMenu');
  if (userMenu) {
    const userDropdown = document.getElementById('userDropdown');
    userMenu.addEventListener('click', (e) => {
      userDropdown.classList.toggle('show');
      e.stopPropagation();
    });
    document.addEventListener('click', () => userDropdown.classList.remove('show'));
  }

  // Gate links that require login → open modal on this page
  document.querySelectorAll('[data-requires-auth="1"]').forEach(link => {
    link.addEventListener('click', function(e){
      if (!IS_LOGGED_IN) {
        e.preventDefault();
        openAuthModal(false);
      }
    });
  });

  // ==================== HOW TO REGISTER VIDEO MODAL ====================
  const REGISTER_VIDEO = <?php echo $registerVideoJs ?: 'null'; ?>;

  const howtoOverlay = document.getElementById('howtoModalOverlay');
  const howtoBody    = document.getElementById('howtoModalBody');
  const howtoTitle   = document.getElementById('howtoModalTitle');
  const howToRegisterBtn = document.getElementById('howToRegisterBtn');
  const howtoCloseBtn = document.getElementById('howtoModalCloseBtn');

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function openHowtoModal() {
    if (!REGISTER_VIDEO || !REGISTER_VIDEO.player_src) {
      alert('Register guide video තවම upload කරලා නැහැ. Admin ට කියන්න.');
      return;
    }
    howtoTitle.textContent = REGISTER_VIDEO.title || 'How to Register – Video Guide';
    const type = REGISTER_VIDEO.player_type;
    const src  = REGISTER_VIDEO.player_src;

    if (type === 'youtube' || type === 'vimeo') {
      const autoplaySrc = src + (src.includes('?') ? '&' : '?') + 'autoplay=1';
      howtoBody.innerHTML =
        `<iframe src="${escapeHtml(autoplaySrc)}"
                 allow="autoplay; fullscreen; picture-in-picture"
                 allowfullscreen
                 title="${escapeHtml(REGISTER_VIDEO.title || 'How to Register Video')}"></iframe>`;
    } else {
      const safeSrc = escapeHtml(src);
      howtoBody.innerHTML =
        `<video id="howtoVideoPlayer" controls playsinline autoplay preload="metadata"
                style="width:100%;max-height:70vh;display:block;background:#000;">
           <source src="${safeSrc}">
           Your browser does not support the video tag.
         </video>`;
      const player = document.getElementById('howtoVideoPlayer');
      if (player) player.play().catch(() => {});
    }
    howtoOverlay.classList.add('show');
    howtoOverlay.setAttribute('aria-hidden', 'false');
  }

  function closeHowtoModal() {
    howtoOverlay.classList.remove('show');
    howtoOverlay.setAttribute('aria-hidden', 'true');
    const v = document.getElementById('howtoVideoPlayer');
    if (v) { v.pause(); v.removeAttribute('src'); v.load(); }
    howtoBody.innerHTML = '';
  }

  window.openHowtoModal = openHowtoModal;
  window.closeHowtoModal = closeHowtoModal;

  if (howToRegisterBtn) {
    howToRegisterBtn.addEventListener('click', function(e) {
      e.preventDefault();
      openHowtoModal();
    });
  }
  if (howtoCloseBtn) howtoCloseBtn.addEventListener('click', closeHowtoModal);
  if (howtoOverlay) {
    howtoOverlay.addEventListener('click', function(e) {
      if (e.target === howtoOverlay) closeHowtoModal();
    });
  }
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && howtoOverlay && howtoOverlay.classList.contains('show')) {
      closeHowtoModal();
    }
  });
</script>
</body>
</html>