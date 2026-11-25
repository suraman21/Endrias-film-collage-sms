<?php
/**
 * Endrias Film College — public site content (auto-generated, byte-exact).
 *
 * Every human-readable string is copied verbatim from the React source
 * (src/data/collegeData.ts + src/components/Layout.tsx + src/pages/HomePage.tsx).
 * Bilingual entries are ['en' => ..., 'am' => ...] mirroring t('en','am');
 * pick the column for the active language.
 */

// ── Primary navigation (Layout.tsx NAV_ITEMS) ─────────────────────────────
$EFC_NAV = [

    ['page' => '', 'en' => 'Home', 'am' => 'መነሻ'],
    ['page' => 'programs', 'en' => 'Programs', 'am' => 'ፕሮግራሞች'],
    ['page' => 'about', 'en' => 'About & Accreditation', 'am' => 'ስለ ኮሌጁ'],
    ['page' => 'faculty', 'en' => 'Faculty', 'am' => 'መምህራን'],
    ['page' => 'showcase', 'en' => 'Endi Award', 'am' => 'እንዲ አዋርድ'],
    ['page' => 'careers', 'en' => 'Careers', 'am' => 'የስራ ትስስር'],
    ['page' => 'admissions', 'en' => 'Admissions', 'am' => 'ምዝገባ'],
];

// ── Contact + social (collegeData.ts CONTACT_INFO) ─────────────────────────
$EFC_CONTACT = [
    'phonesPrimary' => ['+251 900 003 919', '+251 942 563 535'],
    'phonesAdmin'   => ['+251 940 579 907', '+251 902 542 621', '+251 118 112 518'],
    'emailPrimary'  => 'endriasfilmacademyplc@gmail.com',
    'emailAcademic' => 'endriasfilmcollegeplc@gmail.com',
    'telegram'      => 'https://t.me/endifilm',
    'facebook'      => 'https://www.facebook.com/endifilm/',
    'tiktok'        => 'https://www.tiktok.com/@endrias.film.coll',
    'instagram'     => 'https://www.instagram.com/reel/DaN9S0PCKAj/',
    'youtube'       => 'https://www.youtube.com/@endriastube2743',
    'sms'           => 'https://sms.endriasfilmcollege.com',
    'addressMainEn' => '4 Kilo Roundabout, Next to Wekma (Main Campus), Arada Sub-City, Woreda 9, Addis Ababa',
    'addressMainAm' => '4 ኪሎ አደባባይ ከወክማ ጎን (ዋናው ካምፓስ)፣ አራዳ ክፍለ ከተማ ወረዳ 9፣ አዲስ አበባ',
    'telegramCareers' => 'https://t.me/efcfilm',
    'telegramDean'    => 'https://t.me/al0254',
    'addressTrinityEn' => '4 Kilo Roundabout, Holy Trinity University Building, 4th Floor Terrace, Addis Ababa',
    'addressTrinityAm' => '4 ኪሎ አደባባይ ቅድስት ሥላሴ ዩኒቨርሲቲ ህንፃ 4ኛ ፎቅ (ቴራስ ላይ)፣ አዲስ አበባ',
    'social' => [
        ['label' => 'Telegram',  'url' => 'https://t.me/endifilm'],
        ['label' => 'TikTok',    'url' => 'https://www.tiktok.com/@endrias.film.coll'],
        ['label' => 'Facebook',  'url' => 'https://www.facebook.com/endifilm/'],
        ['label' => 'Instagram', 'url' => 'https://www.instagram.com/reel/DaN9S0PCKAj/'],
        ['label' => 'YouTube',   'url' => 'https://www.youtube.com/@endriastube2743'],
    ],
];


// Counts used in Home copy.
$EFC_FACULTY_COUNT = 6;   // count(FACULTY_MEMBERS)
$EFC_MENTOR_COUNT  = 5;   // count(MASTERCLASSES)

// ── Program tiers (Home teaser + Programs page) ────────────────────────────
$EFC_PROGRAM_TIERS = [

    [
        'id' => 'level-4-diploma',
        'code' => 'TVET LVL I–IV',
        'featured' => true,
        'levelBadgeEn' => 'Accredited TVET Diploma',
        'levelBadgeAm' => 'ዕውቅና ያለው ደረጃ 1–4 ዲፕሎማ',
        'titleEn' => 'Level IV Regular Filmmaking Diploma',
        'titleAm' => 'የደረጃ 4 (ዲፕሎማ) መደበኛ የፊልም ጥበብ ፕሮግራም',
        'durationEn' => 'Up to 2 Years (+10 Months Bridge Option)',
        'durationAm' => 'እስከ 2 ዓመት (ለ1 ዓመት ምሩቃን +10 ወር ማሳደጊያ)',
        'scheduleEn' => 'Mon–Fri or Weekend · 2 Hrs/Day (4 Shifts)',
        'scheduleAm' => 'ከሰኞ–አርብ ወይም ቅዳሜና እሑድ · በቀን 2 ሰዓት',
        'summaryEn' => 'Comprehensive government-accredited conservatory training from story conception to theatrical screen delivery, taught by MA/MFA university lecturers and active Ethiopian directors.',
        'summaryAm' => 'ከምናብ እስከ ሲኒማ ስክሪን ሁሉንም የፊልም ጥበብ ዘርፎች በንድፈ-ሀሳብና በተግባር የሚያስጨብጥ በመንግስት ዕውቅና የተሰጠው የዲፕሎማ መርሃግብር።',
        'pathwayEn' => 'TVET Level IV Diploma → National COC Exam → Direct BA Degree Entry',
        'pathwayAm' => 'ደረጃ 4 ዲፕሎማ → የCOC ምዘና → ቀጥታ ወደ ዲግሪ ፕሮግራም',
        'category' => 'diploma',
        'highlightsEn' => ['Direct progression to Bachelor’s Degree (BA) upon passing national COC assessment', '10-month fast-track Diploma upgrade available for 1-Year Filmmaking Certificate holders', 'Eligible to compete for the 3-film institutional Guma Award quota & Endi Award'],
        'highlightsAm' => ['በCOC ምዘና ቀጥታ ወደ የመጀመሪያ ዲግሪ (BA) ፕሮግራም ማሳደግ ያስችላል', 'የ1 ዓመት ፊልም ሜኪንግ ሰርተፍኬት ላላቸው በ10 ወር ብቻ ወደ ዲፕሎማ ማሳደጊያ ዕድል', 'በጉማ አዋርድ የ3 ፊልም ኮታ እና በእንዲ አዋርድ የመወዳደር ሙሉ ዕድል'],
        'modulesEn' => ['Directing & Directing Practicum', 'Script Writing', 'Cinematography & Advanced Cinematography', 'Acting for Camera', 'Advanced Video Editing & Color', 'Sound Recording & Sound Design', 'Cinema Lighting', 'Producing', 'Production Design & Miniature Art', 'Special Effects (VFX) & SFX Makeup', 'Documentary Production', 'Film Research, Criticism & Analysis', 'Graphics Design, AI for Filmmaking & Portfolio Branding'],
        'modulesAm' => ['ዝግጅት እና ተግባራዊ የፊልም ዝግጅት', 'ፊልም ጽሑፍ', 'ሲኒማቶግራፊ እና አድቫንስ ሲኒማቶግራፊ', 'ትወና', 'አድቫንስድ ቪዲዮ ኤዲቲንግ', 'ድምፅ እና ሳውንድ ዲዛይን', 'መብራት (Lighting)', 'ፕሮዲውሲንግ', 'ፕሮዳክሽን ዲዛይን እና ሚኔቸር ጥበብ', 'ስፔሻል ኢፌክት እና ሜካፕ', 'ዶክሜንተሪ ፕሮዳክሽን', 'የፊልም ሪሰርች፣ ሂስና ትንታኔ', 'ግራፊክስ፣ AI ለፊልም ስራ እና ፖርትፎሊዮ ብራንዲንግ'],
    ],
    [
        'id' => 'one-year-certificate',
        'code' => 'CERT-12M',
        'levelBadgeEn' => 'General Conservatory Track',
        'levelBadgeAm' => 'አጠቃላይ የፊልም አሰራር ሰርተፍኬት',
        'titleEn' => '1-Year General Filmmaking Certificate',
        'titleAm' => 'የአንድ ዓመት አጠቃላይ የፊልም አሰራር ፕሮግራም',
        'durationEn' => '12 Months',
        'durationAm' => '1 ዓመት (12 ወራት)',
        'scheduleEn' => 'Mon–Fri or Weekend · 4 Flexible Shifts',
        'scheduleAm' => 'ከሰኞ–አርብ ወይም ቅዳሜና እሑድ · 4 ፈረቃዎች',
        'summaryEn' => 'Intensive multi-disciplinary practical program covering narrative filmmaking, camera, lighting, editing, acting, graphics, and modern digital content strategy.',
        'summaryAm' => 'አጠቃላይ የፊልም አሰራር ጥበብን ከዘመኑ ዲጂታል ሚዲያና ግራፊክስ ክህሎቶች ጋር አጣምሮ የሚሰጥ የ1 ዓመት የተግባር ስልጠና።',
        'pathwayEn' => '1-Year Certificate → Studio Employment or +10-Month Level IV Diploma Bridge',
        'pathwayAm' => 'የ1 ዓመት ሰርተፍኬት → ወደ ስራ ዓለም ወይም በ10 ወር ወደ ደረጃ 4 ዲፕሎማ',
        'category' => 'certificate',
        'highlightsEn' => ['Complete a broadcast-grade graduation short film, trailer & theatrical poster', 'Qualifies for +10-month bridging into the accredited Level IV Diploma', 'Full access to zero-commission graduate job placement'],
        'highlightsAm' => ['በሲኒማ ስክሪን የሚታይ የመመረቂያ አጭር ፊልም፣ ትሬለርና ፖስተር ይሰራል', 'በ10 ወር ተጨማሪ ትምህርት ወደ ደረጃ 4 ዲፕሎማ ማሳደግ ይቻላል', 'ከኮሚሽን ነፃ የስራ ትስስር አገልግሎት ተጠቃሚ ያደርጋል'],
        'modulesEn' => ['Directing', 'Script Writing', 'Cinematography', 'Video Editing', 'Photography', 'Acting', 'Sound', 'Lighting', 'Graphics Design & Photo Editing', 'Media Management, Content Creation & Portfolio Branding'],
        'modulesAm' => ['ዝግጅት (Directing)', 'ፊልም ጽሑፍ (Script Writing)', 'ሲኒማቶግራፊ', 'ቪዲዮ ኤዲቲንግ', 'ፎቶግራፊ', 'ትወና', 'ድምፅ', 'መብራት', 'ግራፊክስ ዲዛይን እና ፎቶ ኤዲቲንግ', 'ሚዲያ ማኔጅመንት፣ ኮንቴንት ፈጠራና ፖርትፎሊዮ ብራንዲንግ'],
    ],
    [
        'id' => 'nine-month-audiovisual',
        'code' => 'AV-09M',
        'levelBadgeEn' => 'Technical Production Track',
        'levelBadgeAm' => 'የቴክኒክና ዲጂታል ፕሮዳክሽን ዘርፍ',
        'titleEn' => '9-Month Audio-Visual Production Certificate',
        'titleAm' => 'የ9 ወር የኦዲዮ-ቪዥዋል ሰርተፍኬት ፕሮግራም',
        'durationEn' => '9 Months',
        'durationAm' => '9 ወራት',
        'scheduleEn' => 'Mon–Fri or Weekend · 2 Hrs/Day',
        'scheduleAm' => 'ከሰኞ–አርብ ወይም ቅዳሜና እሑድ · በቀን 2 ሰዓት',
        'summaryEn' => 'Designed specifically for camera operators, editors, gaffers, sound recordists, and studio technicians—covering every digital production craft excluding acting, directing, and scriptwriting.',
        'summaryAm' => 'ከትወና፣ ዝግጅትና ጽሑፍ ውጪ ሁሉንም የዲጂታልና የቴክኒክ ፕሮዳክሽን ኮርሶች በጥልቀት የሚያስተምር የ9 ወር ፕሮግራም።',
        'pathwayEn' => '9-Month Certificate → Broadcast, Agency & Commercial Crew Placement',
        'pathwayAm' => 'የ9 ወር ሰርተፍኬት → ቀጥታ የቴክኒክ ባለሙያ የስራ ትስስር',
        'category' => 'certificate',
        'highlightsEn' => ['Hands-on mastery of Blackmagic 6K Pro, Canon 5D Mark IV & Aputure LED rigs', 'High-demand technical track for TV broadcasters, commercial houses & studios', 'Includes Graphics Design, Photo Editing & Digital Media Management'],
        'highlightsAm' => ['በBlackmagic 6K Pro፣ Canon 5D Mark IV እና Aputure መብራቶች የተደገፈ የተግባር ልምምድ', 'ለቴሌቪዥን ጣቢያዎችና ፕሮዳክሽን ተቋማት ከፍተኛ ተፈላጊነት ያለው ሙያ', 'ግራፊክስ ዲዛይንና ዲጂታል ሚዲያ ማኔጅመንትን ያካትታል'],
        'modulesEn' => ['Cinematography', 'Video Editing (Premiere Pro & DaVinci Resolve)', 'Photography', 'Production & Post Sound', 'Cinema & Studio Lighting', 'Graphics Design & Photo Editing', 'Media Management & Content Creation'],
        'modulesAm' => ['ሲኒማቶግራፊ', 'ቪዲዮ ኤዲቲንግ', 'ፎቶግራፊ', 'ድምፅ ቀረፃና ቅንብር', 'መብራት', 'ግራፊክስ ዲዛይን እና ፎቶ ኤዲቲንግ', 'ሚዲያ ማኔጅመንትና ኮንቴንት ክሬሽን'],
    ],
    [
        'id' => 'three-month-dual',
        'code' => 'DUAL-03M',
        'levelBadgeEn' => 'Dual-Specialization',
        'levelBadgeAm' => 'የ2 ምርጥ ኮርሶች ጥምር ስልጠና',
        'titleEn' => '3-Month Dual-Skill Intensive Program',
        'titleAm' => 'የ3 ወር የሁለት ኮርሶች ምርጫ ፕሮግራም',
        'durationEn' => '3 Months',
        'durationAm' => '3 ወራት',
        'scheduleEn' => '4 Daily Shifts + Weekend Options',
        'scheduleAm' => 'በ4 ፈረቃዎች እና ቅዳሜና እሑድ',
        'summaryEn' => 'Pair any two complementary filmmaking or digital courses—such as Cinematography + Video Editing, or Acting + Scriptwriting—for rapid, studio-ready upskilling.',
        'summaryAm' => 'ሰልጣኞች የመረጧቸውን ማናቸውንም ሁለት (2) ኮርሶች አጣምረው በጥራትና በተግባር የሚማሩበት የሶስት ወር ፕሮግራም።',
        'pathwayEn' => '3-Month Certificate → Freelance / Studio Production or Credit Upgrade',
        'pathwayAm' => 'የ3 ወር ሰርተፍኬት → ፈጣን የስራ ክህሎት እና ወደ መደበኛ ትምህርት የማደግ ዕድል',
        'category' => 'short',
        'highlightsEn' => ['Custom-pair any 2 disciplines from the conservatory catalog', 'Ideal for working journalists, YouTubers, photographers & actors', 'Practical project portfolio piece completed prior to certification'],
        'highlightsAm' => ['ከኮሌጁ ኮርሶች መካከል የፈለጉትን 2 ኮርሶች መምረጥ ይችላሉ', 'በስራ ላይ ላሉ ባለሙያዎችና ተማሪዎች እጅግ ተመራጭ', 'በተግባር የተደገፈ የፖርትፎሊዮ ስራ ያካትታል'],
        'modulesEn' => ['Any 2 Student-Selected Courses (e.g., Cinematography + Editing, Acting + Directing, Photography + Graphics)'],
        'modulesAm' => ['ሰልጣኙ የመረጣቸው ማናቸውም 2 ኮርሶች (ለምሳሌ፦ ሲኒማቶግራፊ + ኤዲቲንግ ወይም ትወና + ፊልም ጽሑፍ)'],
    ],
    [
        'id' => 'two-month-digital-summer',
        'code' => 'DIGI-02M',
        'levelBadgeEn' => 'Digital Creators & Youth Camp',
        'levelBadgeAm' => 'ዲጂታል ሚዲያ እና የክረምት ልዩ ስልጠና',
        'titleEn' => '2-Month Digital Media & Kiremt Youth Tracks',
        'titleAm' => 'የ2 ወር ዲጂታል ሚዲያ እና የክረምት ህፃናት/ታዳጊዎች ፕሮግራም',
        'durationEn' => '2 Months',
        'durationAm' => '2 ወራት',
        'scheduleEn' => 'Year-Round Creator Cohorts + Annual July–Aug Kiremt Camp',
        'scheduleAm' => 'ዓመቱን ሙሉ እና በየዓመቱ ልዩ የክረምት መርሃግብር',
        'summaryEn' => 'Two focused 2-month streams: (1) Digital Media Management, Mobile Production & Content Creation for adults/brands, and (2) the acclaimed Kiremt Children & Youth Talent Program in Acting, Modeling & Dance.',
        'summaryAm' => 'በሁለት ዘርፍ የሚሰጥ፦ (1) የዲጂታል ሚዲያ ማኔጅመንትና ኮንቴንት ፈጠራ እንዲሁም (2) በየክረምቱ ለህፃናትና ታዳጊዎች የሚሰጥ የትወና፣ ሞዴሊንግና ዳንስ ልዩ ስልጠና።',
        'pathwayEn' => '2-Month Certificate → Agency Content Roles & TV/Commercial Casting',
        'pathwayAm' => 'የ2 ወር ሰርተፍኬት → የዲጂታል ሚዲያ ስራዎች እና የቴሌቪዥን ካስቲንግ ዕድል',
        'category' => 'short',
        'highlightsEn' => ['Youth program produced TV stars including child actor Dagim Tezera ("Besintu", EBS TV)', 'Covers mobile shooting, PC editing, thumbnail design & AI creator workflows', 'Special internship & casting opportunities upon completion'],
        'highlightsAm' => ['እንደ ህፃን ዳግም ተዘራ (በስንቱ ድራማ) ያሉ ተዋንያንን ያፈራ የታዳጊዎች መርሃግብር', 'በስልክ ቀረፃ፣ በኮምፒውተር ኤዲቲንግ፣ ታምኔይል ዲዛይንና AI አጠቃቀም', 'ለሰልጣኞች ልዩ የካስቲንግ እና የስራ ዕድል ያመቻቻል'],
        'modulesEn' => ['Digital & Social Media Management', 'Content Creation & Mobile Cinematography', 'Youth Acting, Camera Confidence, Modeling & Choreography (Kiremt Track)'],
        'modulesAm' => ['ዲጂታል እና ሶሻል ሚዲያ ማኔጅመንት', 'ኮንቴንት ፈጠራ እና የሞባይል ፊልም ስራ', 'የህፃናትና ታዳጊዎች ትወና፣ ሞዴሊንግ እና ዳንስ (የክረምት መርሃግብር)'],
    ],
    [
        'id' => 'one-month-vip',
        'code' => 'VIP-01M',
        'levelBadgeEn' => '1-on-1 Personal & Corporate VIP',
        'levelBadgeAm' => 'የግል (VIP / VVIP) እና የድርጅቶች ስልጠና',
        'titleEn' => '1-Month Single Course & VIP / VVIP Personal Class',
        'titleAm' => 'የ1 ወር ነጠላ ኮርስ እና የግል (VIP / VVIP) ልዩ ስልጠና',
        'durationEn' => '1 Month to 6 Weeks',
        'durationAm' => 'ከ1 ወር እስከ 1 ወር ከ2 ሳምንት',
        'scheduleEn' => 'Custom 1-on-1 Schedule or On-Site Corporate Training',
        'scheduleAm' => 'በሰልጣኙ ምርጫ በግል ወይም በድርጅቶች ቢሮ በመገኘት',
        'summaryEn' => 'Accelerated one-on-one mentorship in any single course, exclusive personal instruction in Visual Effects (VFX) and Motion Graphics Design, or customized team training at your organization’s premises.',
        'summaryAm' => 'የመረጡትን 1 ኮርስ በአጭር ጊዜ በግል (VIP/VVIP)፣ ቪዥዋል ኢፌክትና ሞሽን ግራፊክስ በልዩ የግል ክፍል፣ ወይም ለድርጅትዎ ሰራተኞች ባሉበት ቦታ የሚሰጥ ስልጠና።',
        'pathwayEn' => 'Specialized Certificate → Immediate Specialist Deployment',
        'pathwayAm' => 'ልዩ ሰርተፍኬት → ፈጣን ሙያዊ ብቃት',
        'category' => 'vip',
        'highlightsEn' => ['Visual Effects (VFX) & Motion Graphics Design taught exclusively 1-on-1', 'All conservatory courses available in VIP & VVIP personalized format', 'Custom corporate media training for NGOs, banks, agencies & broadcasters'],
        'highlightsAm' => ['ቪዥዋል ኢፌክት (VFX) እና ሞሽን ግራፊክስ በግል (Personal Class) ብቻ የሚሰጡ', 'ሁሉንም የኮሌጁን ኮርሶች በVIP እና VVIP መማር ይቻላል', 'ለመንግስታዊና የግል ተቋማት የኮሚኒኬሽን ቡድኖች ልዩ ስልጠና'],
        'modulesEn' => ['Visual Effects (VFX — VIP Exclusive)', 'Motion Graphics Design (After Effects — VIP Exclusive)', 'Any Single Chosen Conservatory Course (1-on-1 Custom Pace)'],
        'modulesAm' => ['ቪዥዋል ኢፌክት (Visual Effects — በግል ብቻ)', 'ሞሽን ግራፊክስ ዲዛይን (Motion Graphics — በግል ብቻ)', 'ማንኛውም የተመረጠ አንድ ኮርስ በተመቸ ሰዓት'],
    ],
];

// ── Daily study shifts (Home Section 4) ────────────────────────────────────
$EFC_DAILY_SHIFTS = [

    ['id' => 'shift-1', 'labelEn' => 'Morning Shift', 'labelAm' => 'የጠዋት ፈረቃ', 'ethiopianTime' => '3:00 – 5:00 (ጠዋት)', 'intlTime' => '9:00 AM – 11:00 AM', 'daysEn' => 'Mon–Fri', 'daysAm' => 'ሰኞ – አርብ'],
    ['id' => 'shift-2', 'labelEn' => 'Early Afternoon Shift', 'labelAm' => 'የከሰዓት 1ኛ ፈረቃ', 'ethiopianTime' => '8:00 – 10:00 (ከሰዓት)', 'intlTime' => '2:00 PM – 4:00 PM', 'daysEn' => 'Mon–Fri', 'daysAm' => 'ሰኞ – አርብ'],
    ['id' => 'shift-3', 'labelEn' => 'Late Afternoon Shift', 'labelAm' => 'የከሰዓት 2ኛ ፈረቃ', 'ethiopianTime' => '10:00 – 12:00 (አመሻሽ)', 'intlTime' => '4:00 PM – 6:00 PM', 'daysEn' => 'Mon–Fri', 'daysAm' => 'ሰኞ – አርብ'],
    ['id' => 'shift-4', 'labelEn' => 'Evening Working-Pro Shift', 'labelAm' => 'የማታ ፈረቃ (ለሰራተኞች ምቹ)', 'ethiopianTime' => '11:30 – 1:30 (ምሽት)', 'intlTime' => '5:30 PM – 7:30 PM', 'daysEn' => 'Mon–Fri', 'daysAm' => 'ሰኞ – አርብ'],
    ['id' => 'shift-weekend', 'labelEn' => 'Weekend Conservatory Track', 'labelAm' => 'የቅዳሜ እና እሑድ ፈረቃ', 'ethiopianTime' => 'ቅዳሜ እና እሑድ', 'intlTime' => 'Saturday & Sunday Sessions', 'daysEn' => 'Sat – Sun', 'daysAm' => 'ቅዳሜ – እሑድ'],
];

// ── Home page copy (HomePage.tsx) ──────────────────────────────────────────
$EFC_HOME = [

    'hero_pill' => ['en' => 'Addis Ababa’s Sole Dedicated Accredited TVET Film College', 'am' => 'በአዲስ አበባ ብቸኛው በፊልም ሙያ ሙሉ ዕውቅና ያለው ኮሌጅ'],
    'hero_head1' => ['en' => 'From Imagination ', 'am' => 'ከምናብ ወደ '],
    'hero_head2' => ['en' => 'to the Cinema Screen.', 'am' => 'ታላቁ የሲኒማ ስክሪን።'],
    'hero_lead' => ['en' => 'Located at 4 Kilo Roundabout in Addis Ababa, Endrias Film College PLC delivers government-accredited TVET Level I–IV Diplomas and 1-month to 1-year studio certificates—combining Blackmagic 6K Pro production sets, AAU/Gondar university-caliber faculty, and zero-commission career placement.', 'am' => 'በአዲስ አበባ 4 ኪሎ አደባባይ የሚገኘው እንድርያስ የፊልም ኮሌጅ፤ ከ1 ወር እስከ 2 ዓመት ከሰርተፍኬት እስከ ደረጃ 4 (ዲፕሎማ) በዘመናዊ የሲኒማ ግብዓቶችና በከፍተኛ ዩኒቨርሲቲ መምህራን በተግባር ያሰለጥናል።'],
    'hero_cta1' => ['en' => 'Explore 6 Program Tiers', 'am' => '6ቱን የትምህርት ፕሮግራሞች ይመልከቱ'],
    'hero_cta2' => ['en' => 'Apply for 2019 E.C. Intake', 'am' => 'ለ2019 ዓ.ም ምዝገባ ያመልክቱ'],
    'crest_seal' => ['en' => 'Official Conservatory Seal', 'am' => 'የኮሌጁ ኦፊሻል ዓርማ'],
    'crest_acc' => ['en' => 'Accreditation:', 'am' => 'ዕውቅና፦'],
    'crest_deg' => ['en' => 'Degree Pathway:', 'am' => 'ወደ ዲግሪ መሸጋገሪያ፦'],
    'crest_guma' => ['en' => 'Guma Award:', 'am' => 'ጉማ አዋርድ ኮታ፦'],
    'crest_campus' => ['en' => 'Campus:', 'am' => 'ዋና ካምፓስ፦'],
    's1_eye' => ['en' => '2019 E.C. Academic Prospectus', 'am' => 'የ2019 ዓ.ም የትምህርት ፕሮግራሞች'],
    's1_h2' => ['en' => 'Six Flexible Pathways from First Frame to Degree', 'am' => 'ከሰርተፍኬት እስከ ደረጃ 4 ዲፕሎማ 6 አማራጭ መርሃግብሮች'],
    's1_p' => ['en' => 'Attend 2 hours a day across 4 convenient weekday shifts or weekend cohorts—with AI for Filmmaking, Digital Media Management, and Portfolio Branding integrated.', 'am' => 'ከሰኞ እስከ አርብ በ4 አማራጭ ፈረቃዎች ወይም ቅዳሜና እሑድ በቀን ለ2 ሰዓት ይማሩ።'],
    's1_btn' => ['en' => 'Compare All 21 Modules', 'am' => 'ሁሉንም ኮርሶች በዝርዝር ይመልከቱ'],
    'card_view' => ['en' => 'View Syllabus', 'am' => 'ዝርዝር ኮርሶች'],
    'card_enroll' => ['en' => 'Enroll', 'am' => 'ይመዝገቡ'],
    's2_eye' => ['en' => 'Hands-On Conservatory Method', 'am' => 'በተግባር የተደገፈ የማስተማር ስነ-ዘዴ'],
    's2_h2' => ['en' => 'Train on Real Cinema Cameras, Build Full Sets & Master Modern AI Tools', 'am' => 'በዘመናዊ የሲኒማ ካሜራዎች፣ መብራቶችና የAI ቴክኖሎጂ ይሰልጥኑ'],
    's2_p' => ['en' => 'At Endrias Film College, classrooms transform into working film sets—from building complete nightclub interiors in Directing Practicum to color-grading graduation shorts for theatrical projection on Addis Ababa’s largest cinema screens.', 'am' => 'በእንድርያስ ፊልም ኮሌጅ መማሪያ ክፍሎች ወደ ሙሉ የቀረፃ ስቱዲዮ ይቀየራሉ፤ ተማሪዎች ከፕሮዳክሽን ዲዛይን እስከ ሲኒማ ስክሪን ዕይታ ድረስ በተግባር ሰርተው ይመረቃሉ።'],
    's2_img' => ['en' => 'Premiere Pro · DaVinci Resolve · After Effects · AI Workflows', 'am' => 'በዘመናዊ የኤዲቲንግ፣ ከለር ግሬዲንግ እና AI ሶፍትዌሮች የተደገፈ'],
    's3_eye' => ['en' => 'Red-Carpet Premiere Culture', 'am' => 'የእንዲ አዋርድ እና የጉማ አዋርድ ዕድሎች'],
    's3_h2' => ['en' => 'Home of the “Endi Award” & Official 3-Film Guma Award Quota', 'am' => 'የ«እንዲ አዋርድ» መድረክ እና የ«ጉማ አዋርድ» የ3 ፊልም ቀጥታ ኮታ'],
    's3_p' => ['en' => 'Every Endrias graduation is a full-scale theatrical premiere—held at landmark venues including Addis Cinema Complex, Alem Cinema, and Hager Fikir Theatre—honoring student excellence across 12 craft & ethics categories alongside Ethiopian cinema pioneers.', 'am' => 'በአዲስ ሲኒማ ኮምፕሌክስ፣ በአለም ሲኒማ እና በሀገር ፍቅር ቴአትር የሚካሄዱት የኮሌጁ ምርቃቶች፤ የተማሪዎች ፊልሞች በትልቅ ስክሪን የሚታዩበትና በ«እንዲ አዋርድ» የሚሸለሙበት ደማቅ የሲኒማ በዓል ናቸው።'],
    's3_btn' => ['en' => 'Explore Endi Award & Alumni', 'am' => 'እንዲ አዋርድ እና የተማሪ ስራዎች'],
    's3_img_p' => ['en' => 'Over 250 graduates celebrated at the February 1, 2026 (Tir 24, 2018 E.C.) ceremony at Addis Cinema Complex in the presence of State Minister Asst. Prof. Nebiyu Baye, honoring director Henok Ayele.', 'am' => 'ጥር 24/2018 ዓ.ም በአዲስ ሲኒማ ኮምፕሌክስ ከ250 በላይ ተማሪዎች የተመረቁ ሲሆን፣ አንጋፋው አዘጋጅና መምህር ሔኖክ አየለ የክብር አዋርድ ተበርክቶላቸዋል።'],
    's4_eye' => ['en' => 'Flexible 2-Hour Daily Study Shifts', 'am' => 'በቀን የ2 ሰዓት ምቹ የመማሪያ ፈረቃዎች'],
    's4_h2' => ['en' => 'Study Around Your Work or University Schedule', 'am' => 'ከስራዎ ወይም ከመደበኛ ትምህርትዎ ጋር ሳይጋጭ ይማሩ'],
    's4_btn' => ['en' => 'Reserve Your Shift', 'am' => 'ፈረቃዎን ይምረጡና ይመዝገቡ'],
    's4_visit' => ['en' => 'Visit Endrias Film College First—Then Decide!', 'am' => 'በጥራት ለመማር ሲያስቡ እንድርያስ ፊልም ኮሌጅን ይጎብኙ፤ ከዚያ ይወስኑ!'],
    's4_tour' => ['en' => 'Tour our 4 Kilo Main Campus (next to Wekma & Holy Trinity University 4th Floor), inspect our cinema cameras and editing suites, and speak directly with our academic advisors.', 'am' => '4 ኪሎ አደባባይ ከወክማ ጎን (እና ቅድስት ሥላሴ ዩኒቨርሲቲ 4ኛ ፎቅ) በአካል በመገኘት የማስተማሪያ ግብዓቶቻችንን ይጎብኙ።'],
    's4_pre' => ['en' => 'Start Online Pre-Registration', 'am' => 'የቅድመ-ምዝገባ ቅጽ ይሙሉ'],
    'm1' => ['en' => 'Training Ethiopian Filmmakers (Since 2010 E.C.)', 'am' => 'በፊልም ጥበብ የማስተማር ልምድ (ከ2010 ዓ.ም ጀምሮ)'],
    'm2' => ['en' => 'Annual Diploma & Certificate Graduates', 'am' => 'በየዓመቱ የሚመረቁ የዲፕሎማና ሰርተፍኬት ሰልጣኞች'],
    'm3' => ['en' => '1-Month VIP up to 2-Year Level IV Diploma', 'am' => 'ከ1 ወር VIP እስከ 2 ዓመት ደረጃ 4 ዲፕሎማ'],
    'm4' => ['en' => 'Zero-Commission Graduate Job Placement', 'am' => 'ከኮሚሽን ነፃ የምሩቃን የስራ ትስስር አገልግሎት'],
    's2c1' => ['en' => 'Blackmagic 6K Pro & 5D Mk IV', 'am' => 'Blackmagic 6K Pro እና 5D Mark IV'],
    's2c1d' => ['en' => 'RAW cinema acquisition alongside dedicated mobile filmmaking training.', 'am' => 'በዘመናዊ የሲኒማ ካሜራዎችና በስልክ ፊልም ስራ የተሟላ ልምምድ።'],
    's2c2' => ['en' => 'AI for Filmmaking & Branding', 'am' => 'AI ለፊልም ስራ እና ፖርትፎሊዮ ብራንዲንግ'],
    's2c2d' => ['en' => '2018–2019 E.C. modules in AI pre-vis, content strategy & showreels.', 'am' => 'ለዘመኑ የሚመጥኑ የAI፣ ዲጂታል ሚዲያና የባለሙያ ብራንዲንግ ኮርሶች።'],
    's2c3' => ['en' => 'AAU & Gondar MA/MFA Faculty', 'am' => 'የአዲስ አበባና ጎንደር ዩኒቨስቲ መምህራን'],
    's2c3d' => ['en' => 'Taught by Master’s-qualified scholars and award-winning directors.', 'am' => 'ሁለተኛ ዲግሪና ከዚያ በላይ ባላቸው ምሁራንና አንጋፋ ባለሙያዎች።'],
    's2c4' => ['en' => 'Zero-Fee Job Placement', 'am' => 'ከኮሚሽን ነፃ የስራ ትስስር'],
    's2c4d' => ['en' => 'Direct crew referrals to TV channels, studios & agencies at 0% commission.', 'am' => 'ለሁሉም ምሩቃን ያለምንም ክፍያና ኮሚሽን ቀጥታ የስራ ዕድል ትስስር።'],
    's3r1t' => ['en' => '3-Film Direct Quota at Ethiopia’s Guma Award', 'am' => 'በጉማ አዋርድ የ3 አጫጭር ፊልሞች ልዩ ኮታ'],
    's3r1x' => ['en' => 'Endrias provides free cinema cameras, lighting gear, and faculty mentorship for 3 student films selected annually to compete directly in the Guma Award.', 'am' => 'ኮሌጁ ነፃ የቀረፃ መሳሪያዎችና የመምህራን ድጋፍ በማድረግ በየዓመቱ 3 የተማሪ ፊልሞችን በቀጥታ ለጉማ አዋርድ ውድድር ያቀርባል።'],
    's3r2t' => ['en' => 'Global & Emmy-Winning Mentorship', 'am' => 'ዓለም አቀፍ ወርክሾፖች እና የኤሚ አዋርድ አሸናፊ መምህራን'],
    's3r2x' => ['en' => 'Learn from Int’l Emmy winner Habtamu Sefiw, veteran director Henok Ayele, Munich’s Prof. Andreas Sippel (Otto Falckenberg School), and French visual artist Sébastien Cailleux.', 'am' => 'ከዓለም አቀፍ ኤሚ አዋርድ አሸናፊው ሐብታሙ ሰፊው፣ ከሔኖክ አየለ፣ ከጀርመኑ አንድሬስ ሲፔል እና ከፈረንሳዊው��ባስቲያ�ን ካይሊውክስ ልምድ ይቅሰሙ።'],
    's3r3t' => ['en' => 'Proven Screen Alumni — From “Hagere” to EBS TV’s “Besintu”', 'am' => 'በቴሌቪዥንና በሲኒማ ያሸበረቁ ምሩቃን'],
    's3r3x' => ['en' => 'Alumni include 2018 E.C. double-winner Yodit Solomon ("Hagere") and child star Dagim Tezera ("Nati" on EBS TV sitcom Besintu).', 'am' => 'በ2018 ዓ.ም የ«ሀገሬ» ፊልም አሸናፊዋ ዮዲት ሰሎሞን እና የኢቢኤስ «በስንቱ» ድራማ ተዋናይ ህፃን ዳግም ተዘራ የኮሌጁ ፍሬዎች ናቸው።'],
    ['hero_kicker' => 'ከምናብ እስከ ገቢር — በፊልም ጥበብ ዓለምን እንስራ !'],
    ['fac_btn' => ['en' => 'Meet 6 Faculty & 5 Guest Mentors', 'am' => 'መምህራንን እና ተጋባዥ ባለሙያዎችን ይተዋወቁ']],
    ['m_stats' => [
        ['stat' => '8+ Yrs',  'key' => 'm1'],
        ['stat' => '250+',    'key' => 'm2'],
        ['stat' => '6 Tiers', 'key' => 'm3'],
        ['stat' => '0% Fee',  'key' => 'm4'],
    ]],
    ['s2_cards' => [
        ['icon' => 'camera',         'key' => 's2c1', 'descKey' => 's2c1d'],
        ['icon' => 'sparkles',       'key' => 's2c2', 'descKey' => 's2c2d'],
        ['icon' => 'graduation-cap', 'key' => 's2c3', 'descKey' => 's2c3d'],
        ['icon' => 'briefcase',      'key' => 's2c4', 'descKey' => 's2c4d'],
    ]],
    ['s3_rows' => [
        ['icon' => 'award', 'tKey' => 's3r1t', 'xKey' => 's3r1x'],
        ['icon' => 'users', 'tKey' => 's3r2t', 'xKey' => 's3r2x'],
        ['icon' => 'tv',    'tKey' => 's3r3t', 'xKey' => 's3r3x'],
    ]],
];

// ── Shared chrome copy (Layout.tsx) ────────────────────────────────────────
$EFC_UI = [

    'apply_now' => ['en' => 'Apply Now', 'am' => 'አሁኑኑ ይመዝገቡ'],
    'student_portal' => ['en' => 'Student Portal', 'am' => 'የተማሪ ጎዳኝ'],
    'ann_tagline' => ['en' => 'AAETQAA Accredited TVET Film Conservatory · 4 Kilo, Addis Ababa', 'am' => 'በአዲስ አበባ ብቸኛው በፊልም ጥበብ ሙሉ ዕውቅና ያለው ኮሌጅ · 4 ኪሎ'],
    'foot_accredit' => ['en' => 'AAETQAA Master Registry Row #68 & Permit #32', 'am' => 'በትምህርትና ስልጠና ጥራት ቁጥጥር ባለስልጣን ዕውቅና የተሰጠው'],
    'foot_motto' => ['en' => '“From Imagination to Screen — Let’s create the world with film art!” Addis Ababa’s dedicated accredited institution for TVET Level I–IV Filmmaking Diplomas and practical studio certificates.', 'am' => '«ከምናብ እስከ ገቢር — በፊልም ጥበብ ዓለምን እንስራ!» በአዲስ አበባ ከተማ አስተዳደር ከደረጃ 1–4 (ዲፕሎማ) እና በአጫጭር ፕሮግራሞች ሙሉ ዕውቅና ያለው የፊልም ኮሌጅ።'],
    'foot_head_cons' => ['en' => 'Conservatory', 'am' => 'የኮሌጁ ገፆች'],
    'foot_head_tiers' => ['en' => 'Academic Tiers', 'am' => 'የትምህርት መርሃግብሮች'],
    'foot_head_campus' => ['en' => '4 Kilo Campus & Official Hub', 'am' => 'አድራሻ እና ኦፊሻል ገፆች'],
    'foot_addr1' => ['en' => '4 Kilo Roundabout, Next to Wekma', 'am' => '4 ኪሎ አደባባይ ከወክማ ጎን (ዋናው ካምፓስ)'],
    'foot_addr2' => ['en' => '& Holy Trinity University Bldg, 4th Floor · Arada Sub-City, Woreda 9', 'am' => 'እና ቅድስት ሥላሴ ዩኒቨርሲቲ ህንፃ 4ኛ ፎቅ · አራዳ ወረዳ 9'],
    'foot_rights' => ['en' => 'All rights reserved.', 'am' => 'መብቱ በህግ የተጠበቀ ነው።'],
    'foot_sister' => ['en' => 'Sister Entity: Endrias Film Company · Zero-Commission Career Pledge', 'am' => 'እህት ኩባንያ፦ እንድርያስ ፊልም ካምፓኒ · ነፃ የስራ ትስስር አገልግሎት'],
    'tl1' => ['en' => 'Level IV Regular Diploma (2 Yrs)', 'am' => 'የደረጃ 4 ዲፕሎማ (እስከ 2 ዓመት)'],
    'tl2' => ['en' => '+10-Month Certificate-to-Diploma Bridge', 'am' => 'የ10 ወር ዲፕሎማ ማሳደጊያ'],
    'tl3' => ['en' => '1-Year General Filmmaking', 'am' => 'የ1 ዓመት አጠቃላይ ፊልም አሰራር'],
    'tl4' => ['en' => '9-Month Audio-Visual Track', 'am' => 'የ9 ወር ኦዲዮ-ቪዥዋል ፕሮግራም'],
    'tl5' => ['en' => '3-Month Dual & 2-Month Digital/Summer', 'am' => 'የ3 ወር እና የ2 ወር ዲጂታል/ክረምት'],
    'tl6' => ['en' => '1-Month VIP (VFX & Motion Graphics)', 'am' => 'የ1 ወር VIP (VFX እና ሞሽን ግራፊክስ)'],
    ['brand_name_am' => 'እንድርያስ የፊልም ኮሌጅ ኃ.የ.የግ.ማ'],
    ['brand_plc' => 'Endrias Film College PLC'],
    ['est_line' => 'EST. 2010 E.C. (2017/18) · TVET ACCREDITED'],
    ['ann_pill' => '2019 E.C. INTAKE'],
    ['spec_strip' => ['BLACKMAGIC 6K PRO & 5D MK IV', 'APUTURE / AMARAN LED STUDIO', 'EDUCOC REGISTRY #68 / #32']],
    ['crest_plain' => ['TVET Levels I–IV', 'Direct via COC', '3 Direct Film Slots', '4 Kilo, Next to Wekma']],
    ['s2_img_label' => 'PRACTICAL STUDIO TRAINING'],
    ['s3_img_top' => 'ADDIS CINEMA COMPLEX · ALEM CINEMA · HAGER FIKIR'],
    ['s3_img_award' => 'ENDI AWARD'],
    ['s4_campushdr' => '4 KILO ROUNDABOUT · ARADA SUB-CITY, WOREDA 9'],
    ['phone1' => '0900 003 919'],
    ['phone2' => '0942 563 535'],
    ['tel_handle' => '@endifilm'],
];


// ── Course modules (collegeData.ts COURSE_MODULES) ────────────────────────
$EFC_COURSE_MODULES = [

    ['id' => 'directing', 'nameEn' => 'Film Directing & Practicum', 'nameAm' => 'የፊልም ዝግጅት እና ተግባራዊ ልምምድ', 'discipline' => 'directing', 'levelTag' => 'Core', 'toolsOrFocus' => 'Mise-en-scène · Blocking · Actor Direction · Set Leadership', 'descEn' => 'Translating screenplays into visual grammar, staging actors, transforming studio spaces into full narrative sets, and leading cast & crew.', 'descAm' => 'ስክሪፕትን ወደ ምስል ቋንቋ መቀየር፣ ተዋንያንን መምራትና ሙሉ የቀረፃ ሴት በመገንባት በተግባር ፊልም ማዘጋጀት።'],
    ['id' => 'scriptwriting', 'nameEn' => 'Screenwriting (Script Writing)', 'nameAm' => 'የፊልም ጽሑፍ (Script Writing)', 'discipline' => 'directing', 'levelTag' => 'Core', 'toolsOrFocus' => '3-Act & Non-Linear Structure · Character Arc · Amharic & International Format', 'descEn' => 'Story architecture, dialogue craft, dramatic tension, and professional screenplay formatting studied alongside real Ethiopian feature scripts.', 'descAm' => 'የታሪክ አወቃቀር፣ የገፀ-ባህሪ ስዕል፣ ዲያሎግ እና ደረጃውን የጠበቀ የፊልም ስክሪፕት አፃፃፍ ጥበብ።'],
    ['id' => 'cinematography', 'nameEn' => 'Cinematography & Advanced Camera', 'nameAm' => 'ሲኒማቶግራፊ እና አድቫንስ ካሜራ', 'discipline' => 'camera', 'levelTag' => 'Core', 'toolsOrFocus' => 'Blackmagic 6K Pro · Canon 5D Mark IV · Framing & Lens Language', 'descEn' => 'Camera movement, exposure science, RAW cinema workflows, lens psychology, and visual storytelling on cinema and DSLR systems.', 'descAm' => 'በBlackmagic 6K Pro እና 5D Mark IV ካሜራዎች የምስል ቅንብር፣ የሌንስ አጠቃቀምና የእይታ ትረካ።'],
    ['id' => 'lighting', 'nameEn' => 'Cinema & Studio Lighting', 'nameAm' => 'የሲኒማ መብራት ስርጭት (Lighting)', 'discipline' => 'camera', 'levelTag' => 'Core', 'toolsOrFocus' => 'Aputure / Amaran LED · Chiaroscuro · Color Temperature · Grip', 'descEn' => 'Three-point and motivated natural/dramatic lighting design, gaffer workflows, diffusion, and mood sculpting for narrative and commercial sets.', 'descAm' => 'በአፐርቸር እና አምራን የሲኒማ መብራቶች የብርሃን ስርጭት፣ ስሜት መፍጠርና የስቱዲዮ ብርሃን ዲዛይን።'],
    ['id' => 'acting', 'nameEn' => 'Screen Acting & Voice Projection', 'nameAm' => 'የፊልም ትወና እና የድምፅ አጠቃቀም', 'discipline' => 'directing', 'levelTag' => 'Core', 'toolsOrFocus' => 'Subtext · Camera Continuity · Monologue · Voiceover', 'descEn' => 'Naturalistic performance for cinema lenses, emotional recall, audition readiness, monologue delivery, and voiceover technique.', 'descAm' => 'ለካሜራ የሚመጥን ተፈጥሯዊ ትወና፣ የገፀ-ባህሪ ውህደት፣ ሞኖሎግ እና የቮይስ-ኦቨር ትወና።'],
    ['id' => 'editing', 'nameEn' => 'Video Editing & Color Grading', 'nameAm' => 'ቪዲዮ ኤዲቲንግ እና ከለር ግሬዲንግ', 'discipline' => 'post', 'levelTag' => 'Core', 'toolsOrFocus' => 'Adobe Premiere Pro · DaVinci Resolve · Pacing & Rhythm', 'descEn' => 'Narrative assembly, J/L cuts, multi-cam syncing, trailer cutting, and professional color correction & grading workflows.', 'descAm' => 'በPremiere Pro እና DaVinci Resolve የፊልም ቅንብር፣ ሪትም መጠበቅ፣ ትሬለር ስራና የቀለም እርማት።'],
    ['id' => 'sound', 'nameEn' => 'Production Sound & Sound Design', 'nameAm' => 'ድምፅ ቀረፃ እና ሳውንድ ዲዛይን', 'discipline' => 'craft', 'levelTag' => 'Core', 'toolsOrFocus' => 'Boom & Lav Recording · ADR · Foley · Dialogue Mixing', 'descEn' => 'Clean location audio capture, acoustic control, ADR dubbing, foley layering, and theatrical stereo/surround soundscape design.', 'descAm' => 'በቀረፃ ወቅት ጥራት ያለው ድምፅ መቅዳት፣ ፎሊ፣ ADR እና የድህረ-ምርት የድምፅ ዲዛይን።'],
    ['id' => 'photography', 'nameEn' => 'Professional Photography', 'nameAm' => 'ፕሮፌሽናል ፎቶግራፊ', 'discipline' => 'camera', 'levelTag' => 'Core', 'toolsOrFocus' => 'Documentary · Portrait · Street · Print Exhibition', 'descEn' => 'Composition, light metering, documentary & editorial street photography, culminating in framed A4 print critiques.', 'descAm' => 'የካሜራ ቅንብር፣ የብርሃን ልኬት፣ የዶክመንተሪና የስቱዲዮ ውጪ የፎቶግራፍ ጥበብ።'],
    ['id' => 'producing', 'nameEn' => 'Film Producing & Production Management', 'nameAm' => 'ፕሮዲውሲንግ እና ፕሮዳክሽን ማኔጅመንት', 'discipline' => 'directing', 'levelTag' => 'Advanced Diploma', 'toolsOrFocus' => 'Budgeting · Scheduling · Call Sheets · Festival Distribution', 'descEn' => 'Script breakdown, crew scheduling, financing, legal clearance, and festival/cinema release management.', 'descAm' => 'የፊልም በጀት ዝግጅት፣ የቀረፃ መርሃግብር፣ የቡድን አመራር እና የገበያና ፌስቲቫል ስርጭት።'],
    ['id' => 'production-design', 'nameEn' => 'Production Design & Miniature Art', 'nameAm' => 'ፕሮዳክሽን ዲዛይን እና ሚኔቸር ጥበብ', 'discipline' => 'craft', 'levelTag' => 'Advanced Diploma', 'toolsOrFocus' => 'Set Construction · Props · Wardrobe · Scale Miniatures', 'descEn' => 'World-building through set dressing, color palettes, period props, spatial transformation, and physical miniature model craft.', 'descAm' => 'የቀረፃ ቦታ ግንባታ፣ የቁሳቁስና አልባሳት ምርጫ እንዲሁም የሚኔቸር ሞዴል ጥበብ።'],
    ['id' => 'sfx-makeup', 'nameEn' => 'Special Effects Makeup & Continuity', 'nameAm' => 'ስፔሻል ኢፌክት ሜካፕ እና ኮንቲኒቲ', 'discipline' => 'craft', 'levelTag' => 'Advanced Diploma', 'toolsOrFocus' => 'Character Aging · Prosthetics · Wound FX · Script Continuity', 'descEn' => 'Character transformation, prosthetic & injury makeup effects, and shot-to-shot visual continuity supervision on set.', 'descAm' => 'የገፀ-ባህሪ ገፅ-ቅብ (ሜካፕ)፣ ልዩ ኢፌክት ሜካፕ እና የቀረፃ ቀጣይነት (Continuity) ክትትል።'],
    ['id' => 'documentary-criticism', 'nameEn' => 'Documentary, Film Research & Criticism', 'nameAm' => 'ዶክሜንተሪ፣ የፊልም ሪሰርች እና ሂስና ትንታኔ', 'discipline' => 'directing', 'levelTag' => 'Advanced Diploma', 'toolsOrFocus' => 'Non-Fiction Ethics · Archival Research · Formalist Film Analysis', 'descEn' => 'Observational and investigative documentary filmmaking alongside formalist film criticism and Ethiopian cinema historiography.', 'descAm' => 'የዶክመንተሪ ፊልም አሰራር፣ የፊልም ጥናትና ምርምር እንዲሁም ቅርጻዊ ሂስና ትንታኔ።'],
    ['id' => 'vfx-motion', 'nameEn' => 'Visual Effects (VFX) & Motion Graphics', 'nameAm' => 'ቪዥዋል ኢፌክት እና ሞሽን ግራፊክስ', 'discipline' => 'post', 'levelTag' => 'VIP Exclusive', 'toolsOrFocus' => 'Adobe After Effects · Compositing · Chroma Key · Title Design', 'descEn' => 'Green-screen keying, rotoscoping, particle compositing, broadcast title sequences, and dynamic motion design taught 1-on-1.', 'descAm' => 'በAfter Effects የቪዥዋል ኢፌክት ቅንብር፣ ክሮማ ኪይ እና የሞሽን ግራፊክስ ዲዛይን።'],
    ['id' => 'ai-filmmaking', 'nameEn' => 'Artificial Intelligence for Filmmaking', 'nameAm' => 'አርቴፊሻል ኢንተለጀንስ (AI) ለፊልም ስራ', 'discipline' => 'digital', 'levelTag' => '2018–2019 New', 'toolsOrFocus' => 'Pre-Vis Storyboarding · AI Concept Art · Audio/Post Assist', 'descEn' => 'Integrating ethical generative AI tools into pre-visualization, pitch decks, concept storyboarding, and accelerated post-production.', 'descAm' => 'ዘመናዊ የAI ቴክኖሎጂዎችን ከቅድመ-ዝግጅት እስከ ድህረ-ምርት የፊልም ስራ ጋር በብቃት ማሰናሰን።'],
    ['id' => 'digital-media-branding', 'nameEn' => 'Digital Media, Graphics & Portfolio Branding', 'nameAm' => 'ዲጂታል ሚዲያ፣ ግራፊክስ እና ፖርትፎሊዮ ብራንዲንግ', 'discipline' => 'digital', 'levelTag' => '2018–2019 New', 'toolsOrFocus' => 'Adobe Photoshop · Theatrical Posters · Showreels · Social Strategy', 'descEn' => 'Poster and thumbnail design, multi-platform content creation, personal showreel curation, and professional branding for film careers.', 'descAm' => 'የፊልም ፖስተርና ግራፊክስ ዲዛይን፣ የዲጂታል ይዘት ፈጠራ እና የባለሙያ ፖርትፎሊዮ ግንባታ።'],
];

// ── Programs page copy + filter tabs (ProgramsPage.tsx) ───────────────────
$EFC_PROGRAMS = [

    'hero_eyebrow' => ['en' => 'ACADEMIC PROGRAMS & SYLLABUS', 'am' => 'የትምህርት ፕሮግራሞች እና ኮርሶች'],
    'hero_h1' => ['en' => 'From 1-Month VIP Classes to Accredited Level IV Diplomas', 'am' => 'ከ1 ወር የግል ስልጠና እስከ ደረጃ 4 (ዲፕሎማ) የትምህርት ፕሮግራሞች'],
    'hero_lead' => ['en' => 'Every regular track at Endrias Film College now integrates Social & Digital Media Management, Graphics Design, AI for Filmmaking, and Professional Portfolio Branding.', 'am' => 'በሁሉም መደበኛ ፕሮግራሞቻችን ላይ የሶሻል ሚዲያ ማኔጅመንት፣ ግራፊክስ ዲዛይን፣ AI ለፊልም ስራ እና ፕሮፌሽናል ፖርትፎሊዮ ብራንዲንግ ተካትተዋል።'],
    's1_h2' => ['en' => '6 Official Program Tiers', 'am' => '6ቱ የትምህርት ደረጃዎችና መርሃግብሮች'],
    'tier_courses' => ['en' => 'Included Courses & Modules', 'am' => 'በፕሮግራሙ የተካተቱ ኮርሶች'],
    'tier_tuition' => ['en' => 'Tuition window: 27th–3rd E.C.', 'am' => 'ወርሃዊ ክፍያ፦ ከ27–3 ባሉት ቀናት'],
    'tier_apply' => ['en' => 'Apply for This Track', 'am' => 'በዚህ ፕሮግራም ይመዝገቡ'],
    's2_eyebrow' => ['en' => 'Interactive Study Planner', 'am' => 'የፕሮግራም እና የፈረቃ መምረጫ አጋዥ'],
    's2_h2' => ['en' => 'Not Sure Which Track Fits Your Goal?', 'am' => 'የትኛው የትምህርት መርሃግብር እንደሚስማማዎት ይምረጡ'],
    's2_goal_label' => ['en' => 'Select Your Primary Creative Goal', 'am' => 'ዋና ዓላማዎን ይምረጡ'],
    's2_shift_label' => ['en' => 'Choose Your Preferred Daily Shift', 'am' => 'የሚመችዎትን የመማሪያ ፈረቃ ይምረጡ'],
    'rec_title' => ['en' => 'Recommended Study Plan', 'am' => 'ለእርስዎ የተመረጠ መርሃግብር'],
    'rec_duration' => ['en' => 'Duration:', 'am' => 'ቆይታ፦'],
    'rec_eth' => ['en' => 'Ethiopian Time:', 'am' => 'በኢትዮጵያ ሰዓት፦'],
    'rec_std' => ['en' => 'Standard Time:', 'am' => 'ዓለም አቀፍ ሰዓት፦'],
    'rec_cta' => ['en' => 'Continue to Pre-Registration Slip', 'am' => 'በዚህ ምርጫ ቅድመ-ምዝገባ ይቀጥሉ'],
    's3_eyebrow' => ['en' => 'Complete Conservatory Syllabus', 'am' => 'ዝርዝር የኮርሶች ካታሎግ'],
    's3_h2' => ['en' => 'Explore All Core, Diploma & VIP Modules', 'am' => 'ሁሉንም የፊልምና ዲጂታል ኮርሶች ይመርምሩ'],
    's3_search' => ['en' => 'Search courses, tools (e.g. DaVinci, AI)...', 'am' => 'ኮርስ ወይም ሶፍትዌር ይፈልጉ...'],
    'coc_eyebrow' => ['en' => 'National Competency (COC) & +10-Month Bridging', 'am' => 'የCOC ምዘና ዝግጅት እና የ10 ወር ዲፕሎማ ማሳደጊያ'],
    'coc_h3' => ['en' => 'Already Hold a 1-Year Filmmaking Certificate? Upgrade to a Level IV Diploma in 10 Months.', 'am' => 'ከእኛ ወይም ከሌላ ተቋም የ1 ዓመት ፊልም ሰርተፍኬት አለዎት? በ10 ወር ብቻ ወደ ደረጃ 4 ዲፕሎማ ያሳድጉ!'],
    'coc_p' => ['en' => 'Endrias Film College also provides free theoretical and practical COC exam preparation sessions for its alumni—students pay only the official 500 ETB government assessment fee with zero college markup.', 'am' => 'ኮሌጁ ተማሪዎቹ ለመንግስት የብቃት ምዘና (COC) ሲቀርቡ ነፃ የተግባርና የንድፈ-ሀሳብ ዝግጅት ያደርግላቸዋል፤ ተመዛኞች የሚከፍሉት የመንግስት የምዘና ክፍያ (500 ብር) ብቻ ነው።'],
    'coc_cta' => ['en' => 'Inquire About Diploma Bridge', 'am' => 'ስለ ዲፕሎማ ማሳደጊያ ይጠይቁ'],
    ['tier_tabs' => [['id' => 'all', 'en' => 'All (6)', 'am' => 'ሁሉም (6)'], ['id' => 'diploma', 'en' => 'TVET Diploma', 'am' => 'ደረጃ 4 ዲፕሎማ'], ['id' => 'certificate', 'en' => '9–12 Mo Certificates', 'am' => 'የ9–12 ወር ሰርተፍኬት'], ['id' => 'short', 'en' => '2–3 Mo Short Tracks', 'am' => 'የ2–3 ወር አጫጭር'], ['id' => 'vip', 'en' => '1-Mo VIP & VFX', 'am' => 'የ1 ወር VIP / VFX']]],
    ['discipline_tabs' => [['id' => 'all', 'en' => 'All Disciplines', 'am' => 'ሁሉም ዘርፎች'], ['id' => 'directing', 'en' => 'Directing, Acting & Story', 'am' => 'ዝግጅት፣ ትወና እና ጽሑፍ'], ['id' => 'camera', 'en' => 'Camera, Lighting & Photo', 'am' => 'ካሜራ፣ መብራትና ፎቶ'], ['id' => 'post', 'en' => 'Editing, Color & VFX', 'am' => 'ኤዲቲንግ እና VFX'], ['id' => 'craft', 'en' => 'Sound, Set Design & Makeup', 'am' => 'ድምፅ፣ ሴት ዲዛይንና ሜካፕ'], ['id' => 'digital', 'en' => 'AI, Graphics & Branding', 'am' => 'AI፣ ግራፊክስና ብራንዲንግ']]],
    ['goal_options' => [['id' => 'degree', 'en' => 'Full Diploma + BA Degree Bridge (COC)', 'am' => 'ሙሉ ዲፕሎማ እና ወደ ዲግሪ መሸጋገሪያ (COC)'], ['id' => 'allround', 'en' => '1-Yr All-Round Director / Actor / Filmmaker', 'am' => 'በ1 ዓመት ሁለገብ የፊልም ባለሙያ መሆን'], ['id' => 'technical', 'en' => 'Camera, Lighting, Sound & Editing Tech Crew', 'am' => 'የካሜራ፣ መብራትና ኤዲቲንግ ቴክኒክ ባለሙያ'], ['id' => 'upskill', 'en' => 'Learn Any 2 Specific Courses in 3 Months', 'am' => 'በ3 ወር የመረጥኳቸውን 2 ኮርሶች መማር'], ['id' => 'creator', 'en' => 'Digital Content Creator or Youth Summer Camp', 'am' => 'ዲጂታል ኮንቴንት ፈጠራ ወይም የክረምት ታዳጊዎች'], ['id' => 'vfx', 'en' => '1-on-1 VIP Class (VFX / Motion Graphics)', 'am' => 'የግል VIP ስልጠና (VFX / ሞሽን ግራፊክስ)']]],
    ['goal_map' => ['degree' => 'level-4-diploma', 'allround' => 'one-year-certificate', 'technical' => 'nine-month-audiovisual', 'upskill' => 'three-month-dual', 'creator' => 'two-month-digital-summer', 'vfx' => 'one-month-vip']],
];

// ── Faculty members (collegeData.ts FACULTY_MEMBERS) ──────────────────────
$EFC_FACULTY = [

    ['id' => 'alazar-tilahun', 'nameEn' => 'Alazar Tilahun', 'nameAm' => 'አልዓዛር ጥላሁን', 'roleEn' => 'College Dean & Institutional Lead', 'roleAm' => 'የኮሌጁ ዲን እና የአካዳሚክ አመራር', 'specialtyEn' => 'Academic Governance, Curriculum & Industry Placement', 'specialtyAm' => 'የአካዳሚክ አመራር፣ ስርዓተ-ትምህርት እና የስራ ትስስር', 'badgeEn' => 'Executive Leadership', 'badgeAm' => 'የኮሌጁ ዲን', 'credentialsEn' => ['Spearheaded Endrias Film College’s transition from Academy to AAETQAA Level I–IV accredited TVET College', 'Oversees annual 250+ student graduations, Endi Award jury coordination & the Guma Award 3-film institutional quota', 'Leads the college’s zero-commission graduate employment pipeline (@al0254)'], 'credentialsAm' => ['ተቋሙ ከአካዳሚ ወደ ደረጃ 1–4 ዕውቅና ያለው የፊልም ኮሌጅ እንዲያድግ የመሩ', 'የዓመታዊ ምርቃት፣ የእንዲ አዋርድ እና የጉማ አዋርድ የ3 ፊልም ኮታ አስተባባሪ', 'ከኮሚሽን ነፃ የምሩቃን የስራ ትስስር መርሃግብር መሪ (@al0254)']],
    ['id' => 'henok-ayele', 'nameEn' => 'Henok Ayele', 'nameAm' => 'ሔኖክ አየለ', 'roleEn' => 'Senior Film Directing Lecturer', 'roleAm' => 'የፊልም ዝግጅት (Directing) መምህር', 'specialtyEn' => 'Narrative Directing, Screenwriting & Formalist Film Criticism', 'specialtyAm' => 'የፊልም ዝግጅት፣ ድርሰት እና ቅርጻዊ የፊልም ሂስ', 'badgeEn' => '2026 Honorary Laureate', 'badgeAm' => 'የክብር ተሸላሚ መምህር', 'credentialsEn' => ['Renowned Ethiopian feature film writer, director, producer & cinema scholar', 'Authored the Oct 2023 foundational paper "The Movement of Film Schools in Ethiopia: Today and Tomorrow"', 'Recipient of the 2026 Endrias Film College Honorary Award at Addis Cinema Complex; led the 2025 formalist critique of "Aster" at Cinema Empire'], 'credentialsAm' => ['አንጋፋ ኢትዮጵያዊ የፊልም ደራሲ፣ አዘጋጅ፣ ፕሮዲውሰር እና የፊልም ተመራማሪ', '«በኢትዮጵያ የፊልም ት/ቤቶች እንቅስቃሴ ዛሬና ነገ» ጥናታዊ ጽሑፍ አቅራቢ', 'በ2018 ዓ.ም በአዲስ ሲኒማ ኮምፕሌክስ የክብር ተሸላሚ እና የ«አስቴር» ፊልም ቅርጻዊ ሂስ አቅራቢ']],
    ['id' => 'habtamu-sefiw', 'nameEn' => 'Habtamu Sefiw Mekonnen', 'nameAm' => 'ሐብታሙ ሰፊው መኮንን', 'roleEn' => 'Filmmaking Lecturer & Mentor', 'roleAm' => 'የፊልም ጥበብ መምህር እና አማካሪ', 'specialtyEn' => 'Short & Feature Production, Directing & International Festival Strategy', 'specialtyAm' => 'የፊልም ፕሮዳክሽን፣ ዝግጅት እና ዓለም አቀፍ የፊልም ውድድሮች', 'badgeEn' => 'Int’l Emmy Winner', 'badgeAm' => 'የኤሚ አዋርድ አሸናፊ', 'credentialsEn' => ['Winner of the 2023 International Emmy Awards — JCS Young Creatives Award in New York ("Stop War")', 'BA in Theatre Arts & MFA in Film Production (Addis Ababa University)', 'Alumnus of MultiChoice Talent Factory East Africa (Nairobi) & New York Film Academy workshops'], 'credentialsAm' => ['የ2023 ዓለም አቀፍ ኤሚ አዋርድ (International Emmy Award) አሸናፊ', 'ከአዲስ አበባ ዩኒቨርሲቲ በቴአትር BA እና በፊልም ፕሮዳክሽን ሁለተኛ ዲግሪ (MFA)', 'የMultiChoice Talent Factory (ኬንያ) እና New York Film Academy ሰልጣኝ']],
    ['id' => 'zewdu-lingereh', 'nameEn' => 'Zewdu Lingereh', 'nameAm' => 'ዘውዱ ልንገርህ', 'roleEn' => 'Film Directing & Criticism Lecturer', 'roleAm' => 'የፊልም ዝግጅት እና የሂስ መምህር', 'specialtyEn' => 'Directing Practicum, Set Staging & Cinema Analysis', 'specialtyAm' => 'ተግባራዊ የፊልም ዝግጅት እና የፊልም ሂስና ትንታኔ', 'badgeEn' => 'Core Diploma Faculty', 'badgeAm' => 'የዲፕሎማ መምህር', 'credentialsEn' => ['Co-leads Diploma Film Directing studio practicums, mentoring students through full production-design set builds', 'Moderated the Addis Ababa Culture, Arts & Tourism Bureau cinema criticism symposium at Cinema Empire (March 2025)'], 'credentialsAm' => ['የዲፕሎማ ተግባራዊ የፊልም ዝግጅት ኮርስን ከሴት ግንባታ እስከ ቀረፃ በመምራት የሚያስተምሩ', 'በሲኒማ አምፒር የተካሄደውን የ«አስቴር» ፊልም ሂሳዊ ምልከታ መድረክ አወያይ']],
    ['id' => 'elias-mada', 'nameEn' => 'Elias Mada', 'nameAm' => 'ኤልያስ ማዳ', 'roleEn' => 'Film Directing Instructor', 'roleAm' => 'የፊልም ዝግጅት መምህር', 'specialtyEn' => 'Narrative Directing & Graduation Film Supervision', 'specialtyAm' => 'የፊልም ዝግጅት እና የመመረቂያ ፊልሞች ክትትል', 'badgeEn' => 'Directing Faculty', 'badgeAm' => 'የዝግጅት መምህር', 'credentialsEn' => ['Guided day and evening cohorts through script-to-screen directing assessments and thesis critiques'], 'credentialsAm' => ['የቀንና የማታ ሰልጣኞችን የዝግጅት ኮርስና የመመረቂያ ፊልም ስራዎች በማማከር ያገለገሉ']],
    ['id' => 'dagmawi-abebe', 'nameEn' => 'Dagmawi Abebe', 'nameAm' => 'ዳግማዊ አበበ', 'roleEn' => 'Guest Cinematography & Lighting Instructor', 'roleAm' => 'ተጋባዥ የሲኒማቶግራፊ እና መብራት መምህር', 'specialtyEn' => 'Studio Lighting Design, Gaffer Craft & Camera Blocking', 'specialtyAm' => 'የስቱዲዮ መብራት ዲዛይን እና ሲኒማቶግራፊ', 'badgeEn' => 'Guest Practitioner', 'badgeAm' => 'ተጋባዥ ባለሙያ', 'credentialsEn' => ['Collaborates on Diploma Directing studio practicums, training students in atmospheric club/interior lighting setups'], 'credentialsAm' => ['በዲፕሎማ የተግባር ቀረፃዎች ላይ የመብራትና የካሜራ ቅንብር ልምምዶችን የሚመሩ']],
];

// ── Masterclasses (collegeData.ts MASTERCLASSES) ──────────────────────────
$EFC_MASTERCLASSES = [

    ['id' => 'andreas-sippel', 'speakerEn' => 'Prof. Andreas Sippel', 'speakerAm' => 'አንድሬስ ሲፔል (Andreas Sippel)', 'originEn' => 'Munich, Germany', 'originAm' => 'ሙኒክ፣ ጀርመን', 'topicEn' => 'Voiceover Acting, Stage Projection & Speech Pedagogy', 'topicAm' => 'የቮይስ-ኦቨር ትወና፣ የመድረክ ትወና እና የንግግር ችሎታ', 'dateEn' => 'Dec 12, 2025 (Tahsas 3, 2018 E.C.)', 'dateAm' => 'ታህሳስ 3 / 2018 ዓ.ም', 'affiliationEn' => 'Otto Falckenberg School of the Performing Arts (Teaching since 1986)', 'affiliationAm' => 'ኦቶ ፋልከንበርግ የክዋኔ ትምህርት ቤት (ለ40 ዓመታት ያስተማሩ)', 'summaryEn' => 'Free open-campus masterclass sharing four decades of German conservatory methodology in vocal breath control, microphone acting, and dramatic speech.', 'summaryAm' => 'በጀርመን ኦቶ ፋልከንበርግ ት/ቤት ለ40 ዓመታት ያካበቱትን የድምፅ አወጣጥና የትወና ልምድ በኮሌጁ ካምፓስ በነፃ ያካፈሉበት ስልጠና።', 'category' => 'international'],
    ['id' => 'sebastien-cailleux', 'speakerEn' => 'Sébastien Cailleux', 'speakerAm' => 'ሴባስቲያን ካይሊውክስ (Sébastien Cailleux)', 'originEn' => 'Paris, France', 'originAm' => 'ፈረንሳይ', 'topicEn' => 'Framing, Composition & Visual Storytelling', 'topicAm' => 'Framing, Composition እና የዕይታ ጥበብ ትረካ', 'dateEn' => 'Aug 9, 2025 (Nehase 2017 E.C.)', 'dateAm' => 'ነሐሴ 2017 ዓ.ም', 'affiliationEn' => 'CFPJ Paris · Founder of École d’art au village (33+ Yrs, 50+ Countries)', 'affiliationAm' => 'ከ33 ዓመት በላይ ልምድና ከ50 በላይ ሀገራት የሰሩ የዕይታ ጥበብ ባለሙያ', 'summaryEn' => 'Explored light, frame geometry, and visual narrative across photography and cinema with current trainees and alumni at the 4 Kilo campus.', 'summaryAm' => 'ከሲኒማቶግራፊ፣ ፎቶግራፊና የዕይታ ጥበብ አንፃር የፍሬም አወቃቀርና የብርሃን ትረካ ላይ የተሰጠ ነፃ ወርክሾፕ።', 'category' => 'international'],
    ['id' => 'abel-mekasha', 'speakerEn' => 'Abel Mekasha', 'speakerAm' => 'አቤል መካሻ', 'originEn' => 'Ethiopia / International', 'originAm' => 'ኢትዮጵያ / ዓለም አቀፍ', 'topicEn' => 'Harmonizing Artificial Intelligence with Filmmaking', 'topicAm' => 'ኤአይን (AI) ከፊልም ስራ ጋር ማሰናሰን', 'dateEn' => 'July 11, 2026 (Hamle 4, 2018 E.C.)', 'dateAm' => 'ሐምሌ 4 / 2018 ዓ.ም', 'affiliationEn' => 'Writer, Director & Producer of Sci-Fi Feature "Superno"', 'affiliationAm' => 'የ«ሱፐርኖ» (Superno) ዓለም አቀፍ ፊልም ደራሲ፣ ዳይሬክተርና ፕሮዲውሰር', 'summaryEn' => 'Practical demonstration of emerging AI workflows for independent African cinema pre-visualization, world-building, and post-production.', 'summaryAm' => 'ዘመናዊ የAI መሳሪያዎችን ለፊልም ቅድመ-ዝግጅትና ፕሮዳክሽን እንዴት መጠቀም እንደሚቻል የተሰጠ ተግባራዊ ውይይት።', 'category' => 'ai'],
    ['id' => 'daniel-girma', 'speakerEn' => 'Daniel Girma', 'speakerAm' => 'ዳንኤል ግርማ', 'originEn' => 'Addis Ababa, Ethiopia', 'originAm' => 'አዲስ አበባ፣ ኢትዮጵያ', 'topicEn' => 'Visual Storytelling in Features, Commercials & Music Videos', 'topicAm' => 'Visual Storytelling በፊልም፣ ማስታወቂያና ሙዚቃ ክሊፖች', 'dateEn' => 'Aug 2026 (Hamle/Nehase 2018 E.C.)', 'dateAm' => 'ሐምሌ / ነሐሴ 2018 ዓ.ም', 'affiliationEn' => 'Acclaimed Ethiopian Cinematographer', 'affiliationAm' => 'ታዋቂ ኢትዮጵያዊ ሲኒማቶግራፈር', 'summaryEn' => 'In-depth cinematography dialogue analyzing lighting continuity, commercial visual polish, and director-DP collaboration.', 'summaryAm' => 'በበርካታ ፊልሞች፣ ማስታወቂያዎችና የሙዚቃ ክሊፖች ላይ ያካበቱትን የሲኒማቶግራፊ ተሞክሮ ያካፈሉበት መድረክ።', 'category' => 'industry'],
    ['id' => 'johnbosco-paul', 'speakerEn' => 'Johnbosco Paul', 'speakerAm' => 'ጆንቦስኮ ፖውል (Johnbosco Paul)', 'originEn' => 'Nigeria (Nollywood)', 'originAm' => 'ናይጄሪያ (ኖሊውድ)', 'topicEn' => 'No-Budget Filmmaking & Nollywood Production Models', 'topicAm' => 'ያለበጀት ፊልም ስራ (No-Budget Filmmaking) እና የኖሊውድ ተሞክሮ', 'dateEn' => 'Dec 15, 2023 (Tahsas 5, 2016 E.C.)', 'dateAm' => 'ታህሳስ 5 / 2016 ዓ.ም', 'affiliationEn' => 'Nigerian Filmmaker & Producer', 'affiliationAm' => 'ናይጄሪያዊ የፊልም ሰሪ', 'summaryEn' => 'Cross-African exchange focusing on resourceful story selection and producing compelling cinema within tight local equipment budgets.', 'summaryAm' => 'በታሪክ አመራረጥ ላይ በማተኮር አቅም በፈቀደ ግብዓት ጥራት ያለው ፊልም መስራትና የኖሊውድ ኢንዱስትሪ ልምድ።', 'category' => 'international'],
];

// ── Faculty page copy + workshop tabs (FacultyPage.tsx) ───────────────────
$EFC_FACULTY_UI = [

    'hero_eyebrow' => ['en' => 'FACULTY, LEADERSHIP & MASTERCLASSES', 'am' => 'መምህራን፣ አመራር እና ወርክሾፖች'],
    'hero_h1' => ['en' => 'Learn from University Scholars, Emmy Winners & Active Directors', 'am' => 'በዩኒቨርሲቲ ምሁራን፣ በኤሚ አዋርድ አሸናፊና በአንጋፋ ዳይሬክተሮች ይማሩ'],
    'hero_lead' => ['en' => 'Regular Diploma faculty at Endrias Film College hold Master’s degrees (MA/MFA) and above—with lecturing experience at Addis Ababa University and the University of Gondar—paired with celebrated Ethiopian filmmakers and international visiting professors.', 'am' => 'የኮሌጁ መደበኛ መምህራን በአዲስ አበባ ዩኒቨርሲቲና በጎንደር ዩኒቨርሲቲ ያስተማሩና እያስተማሩ ያሉ የሁለተኛ ዲግሪ (MA/MFA) ባለቤቶች እንዲሁም በተግባር ያስመሰከሩ የፊልም ባለሙያዎች ናቸው።'],
    's1_eyebrow' => ['en' => 'Academic Leadership & Instructors', 'am' => 'የኮሌጁ አመራር እና መምህራን'],
    's1_h2' => ['en' => 'Mentors Dedicated to Ethiopian Cinema', 'am' => 'የኢትዮጵያን ሲኒማ የሚቀርፁ መምህራን'],
    's1_std' => ['en' => 'MA / MFA Minimum Diploma Faculty Standard', 'am' => 'የሁለተኛ ዲግሪ (MA/MFA) የመምህራን ደረጃ'],
    's2_eyebrow' => ['en' => 'Open-Campus Masterclass Series', 'am' => 'ዓለም አቀፍ እና የሀገር ውስጥ ወርክሾፖች'],
    's2_h2' => ['en' => 'Global Film Pedagogues & Industry Pioneers on Our 4 Kilo Stage', 'am' => 'ከጀርመን፣ ፈረንሳይ፣ ናይጄሪያና ኢትዮጵያ የተጋበዙ የጥበብ ባለሙያዎች'],
    's2_lead' => ['en' => 'Current trainees and alumni receive free admission to regular workshops connecting Addis Ababa classrooms to Munich, Paris, Nairobi, and Nollywood.', 'am' => 'የአሁንም ሆነ የቀድሞ ተማሪዎቻችን ያለምንም ክፍያ የሚሳተፉባቸው ተከታታይ ሙያዊ ወርክሾፖች።'],
    'mc_free' => ['en' => 'Free Admission for Endrias Students & Alumni', 'am' => 'ለኮሌጁ ተማሪዎች በነፃ የሚዘጋጅ'],
    'cta' => ['en' => 'Join Our Next Cohort & Masterclasses', 'am' => 'ቤተሰባችንን ይቀላቀሉ — አሁኑኑ ይመዝገቡ'],
    ['mc_tabs' => [['id' => 'all', 'en' => 'All Workshops', 'am' => 'ሁሉም'], ['id' => 'international', 'en' => 'International Guests', 'am' => 'ዓለም አቀፍ እንግዶች'], ['id' => 'ai', 'en' => 'AI & Innovation', 'am' => 'AI እና ቴክኖሎጂ'], ['id' => 'industry', 'en' => 'Ethiopian DP Spotlight', 'am' => 'የሀገር ውስጥ ባለሙያዎች']]],
];

// ── Timeline milestones (collegeData.ts TIMELINE_MILESTONES) ──────────────
$EFC_TIMELINE = [

    ['year' => '2017 / 2018 (2010 E.C.)', 'titleEn' => 'Founding of Filmmaking Training Operations', 'titleAm' => 'የፊልም ስልጠና መጀመር (እንድርያስ የፊልም አካዳሚ)', 'descEn' => 'Launched structured practical filmmaking training in Addis Ababa out of the Endrias Film Production / Endrias Film Company ecosystem to close Ethiopia’s cinema skill gap.', 'descAm' => 'በኢትዮጵያ የፊልም ኢንዱስትሪ ውስጥ ያለውን የሰለጠነ የሰው ኃይል ክፍተት ለመሙላት በእንድርያስ ፊልም ፕሮዳክሽን ጥላ ስር ስልጠና መስጠት ጀመረ።'],
    ['year' => 'Feb 2019 – July 2021', 'titleEn' => '4 Kilo Campus Growth & Digital Hub Launch', 'titleAm' => 'በ4 ኪሎ ካምፓስ መስፋፋት እና የዲጂታል ቻናል መከፈት', 'descEn' => 'Registered in East Africa media directories (Feb 2019) at Holy Trinity University Building (3rd Floor), aired 2013 E.C. TV commercials, and launched the official @endifilm hub in July 2021.', 'descAm' => 'በ4 ኪሎ ቅድስት ሥላሴ ዩኒቨርሲቲ ህንፃ 3ኛ ፎቅ በመሆን ተማሪዎችን ማፍራት ቀጠለ፤ ሐምሌ 2013 ዓ.ም ኦፊሻል የቴሌግራም ቻናሉን ከፈተ።'],
    ['year' => 'Feb – Oct 2023 (2015–2016 E.C.)', 'titleEn' => 'AAETQAA TVET Level I–IV College Accreditation', 'titleAm' => 'ከደረጃ 1–4 (ዲፕሎማ) የኮሌጅ ዕውቅና ማግኘት', 'descEn' => 'Accredited by the Addis Ababa Education & Training Quality Assurance Authority (Row #68 & Row #32 — sole dedicated film college cleared in 2016 E.C.), convening AAU & University of Gondar scholars to inaugurate Diploma classes.', 'descAm' => 'በየካቲት 2015 ዓ.ም ሙሉ የኮሌጅነትና የደረጃ 1–4 ዲፕሎማ ዕውቅና አገኘ፤ ከአዲስ አበባና ጎንደር ዩኒቨርሲቲ ምሁራን ጋር በመምከር መደበኛ ዲፕሎማ አስጀመረ።'],
    ['year' => 'Nov 2024 & Dec 2024 (2017 E.C.)', 'titleEn' => 'Alem Cinema 256-Graduate Ceremony & Guma Award Quota', 'titleAm' => 'የ256 ተማሪዎች ምርቃት በአለም ሲኒማ እና የጉማ አዋርድ ኮታ', 'descEn' => 'Graduated 256 trainees at Alem Cinema, honored pioneer Ermias Woldeamlak at the Endi Award, and secured a 3-film direct institutional competition quota with Ethiopia’s prestigious Guma Award.', 'descAm' => 'በአለም ሲኒማ 256 ሰልጣኞችን አስመረቀ፤ ከታላቁ «ጉማ አዋርድ» ጋር በየዓመቱ 3 አጫጭር ፊልሞችን በቀጥታ የማወዳደር ስምምነት ፈፀመ።'],
    ['year' => '2025 – Nov 2026 (2018–2019 E.C.)', 'titleEn' => 'AI Curriculum, European Workshops & Addis Cinema Complex Graduation', 'titleAm' => 'የAI ስርዓተ-ትምህርት፣ ዓለም አቀፍ ወርክሾፖች እና የ2018 ዓ.ም ታላቅ ምርቃት', 'descEn' => 'Introduced AI for Filmmaking & Portfolio Branding, hosted German & French conservatory workshops, graduated 250+ students at Addis Cinema Complex (Feb 2026), and unveiled the renewed 2026 crest.', 'descAm' => 'AI ለፊልም ስራና ፖርትፎሊዮ ብራንዲንግ ኮርሶችን አካተተ፣ ከ250 በላይ ተማሪዎችን በአዲስ ሲኒማ ኮምፕሌክስ አስመረቀ፣ አዲሱን ዓርማውንም በ2026 ይፋ አደረገ።'],
];

// ── About page copy + dossier + collaborations (AboutPage.tsx) ────────────
$EFC_ABOUT = [

    'hero_eyebrow' => ['en' => 'HERITAGE, ACCREDITATION & STUDIOS', 'am' => 'ታሪክ፣ ህጋዊ ዕውቅና እና ስቱዲዮዎች'],
    'hero_h1' => ['en' => 'Addis Ababa’s Dedicated Accredited Home for Cinema Education', 'am' => 'በአዲስ አበባ ብቸኛው ራሱን የቻለ ዕውቅና ያለው የፊልም ኮሌጅ'],
    'hero_lead' => ['en' => 'Registered as Endrias Film College PLC (እንድርያስ የፊልም ኮሌጅ ኃ.የ.የግ.ማ), our institution bridges rigorous university-caliber film scholarship with practical studio production at 4 Kilo, Addis Ababa.', 'am' => 'እንድርያስ የፊልም ኮሌጅ ኃ.የ.የግ.ማ፤ በ4 ኪሎ አደባባይ በመገኘት የከፍተኛ ትምህርት ንድፈ-ሀሳብን ከዘመናዊ የሲኒማ ፕሮዳክሽን ተግባር ጋር አዋህዶ የሚያስተምር ተቋም ነው።'],
    's1_eyebrow' => ['en' => 'Official Regulatory Verification', 'am' => 'የመንግስት የዕውቅና ማረጋገጫ መረጃ'],
    's1_h2' => ['en' => 'Verified on the Addis Ababa Quality Assurance Registry', 'am' => 'በአዲስ አበባ ትምህርትና ስልጠና ጥራት ቁጥጥር ባለስልጣን የተረጋገጠ'],
    's1_lead' => ['en' => 'Out of 121 accredited public and private colleges across Addis Ababa on the Authority’s master registry (educoc.gov.et), Endrias Film College is the sole standalone, single-purpose accredited film college—and the only film institution among the 38 colleges cleared to register new regular trainees in 2016 E.C.', 'am' => 'በአዲስ አበባ ትምህርትና ስልጠና ጥራት ቁጥጥር ባለስልጣን መዝገብ ከሚገኙ 121 የመንግስትና የግል ኮሌጆች መካከል፣ ሙሉ በሙሉ በፊልም ጥበብ ብቻ ራሱን ችሎ ዕውቅና ያለው ብቸኛው ኮሌጅ እንድርያስ የፊልም ኮሌጅ ነው።'],
    'dossier_title' => ['en' => 'AAETQAA / EDUCOC Official Accreditation Dossier', 'am' => 'የአዲስ አበባ ትምህርትና ስልጠና ጥራት ቁጥጥር ባለስልጣን መዝገብ'],
    's2_eyebrow' => ['en' => 'Symbolism of Our Crest', 'am' => 'የኮሌጁ ዓርማ ትርጓሜ'],
    's2_h2' => ['en' => 'Where Academic Rigor Meets Ethiopian Cinema Craft', 'am' => 'የአካዳሚክ ልህቀትና የፊልም ጥበብ ውህደት'],
    's2_lead' => ['en' => 'Unveiled in its renewed form in June 2026, the Endrias Film College seal unites three foundational symbols: the Prussian-navy academic mortarboard (accredited higher vocational scholarship), the four-aperture cinema reel encircled by a dynamic amber arc forming a stylized “e”, and traditional Ethiopian Ge’ez four-dot ornaments (፠) honoring indigenous storytelling.', 'am' => 'ሰኔ 2018 ዓ.ም (June 2026) በአዲስ መልክ የቀረበው የኮሌጁ ዓርማ፤ የከፍተኛ ትምህርት ምልክት የሆነውን የምርቃት ቆብ፣ ባለ 4 ቀዳዳ የፊልም ሪል እና ሀገርኛ የግዕዝ ነቁጥ (፠) ጌጦችን በደማቅ ሰማያዊና ወርቃማ ብርቱካናማ ቀለማት አዋህዶ ይዟል።'],
    's3_eyebrow' => ['en' => 'Institutional Journey', 'am' => 'የኮሌጁ የዕድገት ጉዞ'],
    's3_h2' => ['en' => 'Eight+ Years Shaping Ethiopian Screen Talent', 'am' => 'ከ2010 እስከ 2019 ዓ.ም የዘለቀ የጥበብ ጉዞ'],
    's4_eyebrow' => ['en' => 'Ecosystem & Diplomatic Collaborations', 'am' => 'ተቋማዊና ባህላዊ አጋርነቶች'],
    's4_h2' => ['en' => 'Connected to Ethiopia’s Leading Cultural & Film Institutions', 'am' => 'ከሀገር ውስጥና ዓለም አቀፍ የባህል ተቋማት ጋር ያለን ትስስር'],
    'cta' => ['en' => 'Schedule a 4 Kilo Campus Visit', 'am' => '4 ኪሎ ካምፓሳችንን ይጎብኙ'],
    ['dossier' => [['labelEn' => 'Registered Legal Entity', 'labelAm' => 'ህጋዊ የድርጅት ስም', 'valEn' => 'Endrias Film College PLC', 'valAm' => 'እንድርያስ የፊልም ኮሌጅ ኃ.የ.የግ.ማ'], ['labelEn' => 'Master Registry & Permit Row', 'labelAm' => 'የዕውቅና መዝገብ ተራ ቁጥር', 'valEn' => 'Row #68 (Master) · Row #32 (Active Permit)', 'valAm' => 'ተ.ቁ 68 (ዋና መዝገብ) · ተ.ቁ 32 (የምዝገባ ፈቃድ)'], ['labelEn' => 'Accredited Stream & Levels', 'labelAm' => 'የሰለጠነበት ዘርፍ እና ደረጃ', 'valEn' => 'Video / Film (ቪድዮ ፊልም) · TVET Levels I–IV', 'valAm' => 'ቪድዮ ፊልም · ከደረጃ I እስከ IV (ዲፕሎማ)'], ['labelEn' => 'Jurisdiction & Woreda', 'labelAm' => 'ክፍለ ከተማ እና ወረዳ', 'valEn' => 'Arada Sub-City, Woreda 09 (4 Kilo)', 'valAm' => 'አራዳ ክፍለ ከተማ፣ ወረዳ 09 (4 ኪሎ)'], ['labelEn' => 'Authorized Regular TVET Intake', 'labelAm' => 'የተፈቀደ መደበኛ ሰልጣኝ ቁጥር', 'valEn' => '70 Day + 70 Evening (+ Short-Term Cohorts)', 'valAm' => 'በቀን 70 እና በማታ 70 (+ የአጫጭር ሰርተፍኬት ባቾች)'], ['labelEn' => 'Sister Production House', 'labelAm' => 'እህት የፕሮዳክሽን ተቋም', 'valEn' => 'Endrias Film Company / Production', 'valAm' => 'እንድርያስ ፊልም ካምፓኒ / ፕሮዳክሽን']]],
    ['collabs' => [['icon' => 'award', 'titleEn' => 'Guma Award (ጉማ አዋርድ)', 'titleAm' => 'ጉማ አዋርድ (Guma Award)', 'descEn' => 'Official 3-film annual direct competition quota backed by free college equipment and faculty mentorship.', 'descAm' => 'በየዓመቱ 3 የተማሪ ፊልሞች በቀጥታ የሚወዳደሩበት ልዩ ተቋማዊ ስምምነት።'], ['icon' => 'globe-2', 'titleEn' => 'Alliance Éthio-Française & Goethe-Institut', 'titleAm' => 'አሊያንስ ኢትዮ-ፍራንሲስ እና ጎተ-ኢንስቲትዩት', 'descEn' => 'Co-hosted Obscura Cinema student screenings (with Nada Hubs & White Canvas) and Goethe documentary/grant forums.', 'descAm' => 'የተማሪዎች አጫጭር ፊልሞች ዕይታ፣ ውይይትና የዶክመንተሪ ስልጠና መድረኮች።'], ['icon' => 'building-2', 'titleEn' => 'Addis Ababa Culture & Cinema Bureaus', 'titleAm' => 'የአዲስ አበባ ባህልና ኪነ-ጥበብ ቢሮ እና ሲኒማ አስተዳደር', 'descEn' => 'Partnered on Cinema Empire heritage screenings of "Hirut Abatwa Man New?" and formalist critiques of 35mm classic "Aster".', 'descAm' => 'የ«ኂሩት አባቷ ማነው?» ዕይታ እና የ«አስቴር» ፊልም ቅርጻዊ ሂስ መድረክ ትብብር።'], ['icon' => 'camera', 'titleEn' => 'Endrias Film Company (Sister Studio)', 'titleAm' => 'እንድርያስ ፊልም ካምፓኒ (እህት ኩባንያ)', 'descEn' => 'Commercial production partner producing documentaries, TVCs, music videos, corporate films, and 3D animation.', 'descAm' => 'ዶክመንተሪ፣ የቴሌቪዥን ማስታወቂያ፣ ሙዚቃ ክሊፕና ፊልሞችን የሚያመርት እህት ተቋም።'], ['icon' => 'sparkles', 'titleEn' => 'Ethiopian Academy of Sciences & INSA', 'titleAm' => 'የኢትዮጵያ ሳይንስ አካዳሚ እና INSA', 'descEn' => 'Active student participation in national TV-drama dialogues and INSA Cyber Security Awareness short-film contests.', 'descAm' => 'በሀገር አቀፍ የፊልም ውይይቶችና የሳይበር ደህንነት አጭር ፊልም ውድድሮች ተሳትፎ።'], ['icon' => 'check-circle-2', 'titleEn' => 'Babul Kheyr Humanitarian Outreach', 'titleAm' => 'የበጎ አድራጎት ማህበራዊ ኃላፊነት (ባቡል ከይር)', 'descEn' => 'Graduating classes organize community service and fundraising initiatives alongside zero-fee job placement.', 'descAm' => 'ተመራቂ ተማሪዎች ከምርቃታቸው ጎን ለጎን የሚያከናውኑት የበጎ አድራጎት ተግባር።']]],
    ['seal_card' => ['EST. 2010 E.C.', 'Endrias Film College PLC', 'Arada Sub-City · Woreda 09']],
    ['color_chips' => ['#054677 CONSERVATORY NAVY', '#FF9000 CINEMA AMBER', '፠ GE’EZ ORNAMENTATION']],
    ['seal_caption' => 'OFFICIAL SEAL (2026)'],
    ['mark_caption' => 'CENTRAL LOGOMARK'],
    ['dossier_badge' => 'VERIFIED RECORD'],
];

// ── Endi Award categories (collegeData.ts ENDI_AWARD_CATEGORIES) ──────────
$EFC_ENDI_CATEGORIES = [

    ['en' => 'Best Short Film', 'am' => 'ምርጥ አጭር ፊልም', 'weight' => '100% Jury Assessment'],
    ['en' => 'Best Director', 'am' => 'ምርጥ አዘጋጅ (ዳይሬክተር)', 'weight' => '100% Jury Assessment'],
    ['en' => 'Best Screenplay / Story', 'am' => 'ምርጥ የፊልም ታሪክ / ጽሑፍ', 'weight' => '100% Jury Assessment'],
    ['en' => 'Best Actor', 'am' => 'ምርጥ ወንድ ተዋናይ', 'weight' => '100% Jury Assessment'],
    ['en' => 'Best Actress', 'am' => 'ምርጥ ሴት ተዋናይት', 'weight' => '100% Jury Assessment'],
    ['en' => 'Best Cinematography & Lighting', 'am' => 'ምርጥ ሲኒማቶግራፊ እና መብራት', 'weight' => '100% Jury Assessment'],
    ['en' => 'Best Film Editor', 'am' => 'ምርጥ ፊልም ኤዲተር', 'weight' => '100% Jury Assessment'],
    ['en' => 'Best Sound Design', 'am' => 'ምርጥ የድምፅ ጥበብ', 'weight' => '100% Jury Assessment'],
    ['en' => 'Best Ethical Filmmaker', 'am' => 'ምርጥ የፊልም ሰሪ ሙያ ስነ-ምግባር ባለቤት', 'weight' => 'Faculty & Set Discipline Vote'],
    ['en' => 'Best Theatrical Poster (65×94 cm)', 'am' => 'ምርጥ የፊልም ፖስተር ዲዛይን', 'weight' => '60% Jury + 40% Public Social Vote'],
    ['en' => 'Best 1-Minute Trailer (Cash Prize)', 'am' => 'ምርጥ የ1 ደቂቃ ትሬለር (የገንዘብ ሽልማት)', 'weight' => 'Official TikTok Audience Choice'],
    ['en' => 'Honorary Lifetime Contribution Award', 'am' => 'የህይወት ዘመን የክብር አዋርድ', 'weight' => 'Conferred on Ethiopian Cinema Pioneers'],
];

// ── Graduation records (collegeData.ts GRADUATION_RECORDS) ────────────────
$EFC_GRADUATIONS = [

    ['yearEc' => '2018 E.C.', 'dateEn' => 'February 1, 2026 (Tir 24, 2018 E.C.)', 'dateAm' => 'ጥር 24 / 2018 ዓ.ም', 'venueEn' => 'Addis Cinema Complex (Senga Tera)', 'venueAm' => 'አዲስ ሲኒማ ኮምፕሌክስ (ሰንጋ ተራ)', 'graduatesCount' => '250+ Graduates', 'highlightsEn' => ['Graced by State Minister of Culture & Sports Asst. Prof. Nebiyu Baye & EALS Director-General Sertse Fresebehat', 'Yodit Solomon won both Top Academic Graduate and Endi Award for Best Short Film ("Hagere" / "ሀገሬ")', 'Introduced cash prize for Best 1-Minute TikTok Trailer and 40% public vote for Best Theatrical Poster'], 'highlightsAm' => ['የባህልና ስፖርት ሚኒስትር ዴኤታ ረ/ፕ ነብዩ ባዬ እና ረ/ፕ ሰርፀ ፍሬስብሐት በክብር እንግድነት ተገኝተዋል', 'ተመራቂ ዮዲት ሰሎሞን በከፍተኛ ውጤት እና በ«ሀገሬ» አጭር ፊልም ምርጥ ፊልም አሸናፊ ሆናለች', 'የምርጥ ትሬለር የገንዘብ ሽልማት እና የ40% የህዝብ ድምፅ የፖስተር ውድድር ተካሂዷል'], 'honoreeEn' => 'Henok Ayele (Honorary Lifetime Award)', 'honoreeAm' => 'ሔኖክ አየለ (የክብር አዋርድ ተሸላሚ)'],
    ['yearEc' => '2017 E.C.', 'dateEn' => 'November 24, 2024 (Hidar 15, 2017 E.C.)', 'dateAm' => 'ህዳር 15 / 2017 ዓ.ም', 'venueEn' => 'Alem Cinema (Bole)', 'venueAm' => 'አለም ሲኒማ (ቦሌ)', 'graduatesCount' => '256 Graduates', 'highlightsEn' => ['Graduated 256 Diploma and Certificate filmmakers; hosted by EBS TV personality Yonas', 'Presented Endi Award across 10 specializations; honored 35mm pioneer Ermias Woldeamlak ("The Father", "Aster")', 'Preceded by graduate humanitarian outreach & injera fundraising drive at Babul Kheyr charity center'], 'highlightsAm' => ['256 የዲፕሎማና የሰርተፍኬት ሰልጣኞች ተመርቀዋል፤ በ10 ዘርፎች «እንዲ አዋርድ» ተበርክቷል', 'የ35 ሚ.ሜ ፊልም ፈር-ቀዳጅ ጋሽ ኤርሚያስ ወልደአምላክ ልዩ የክብር ሽልማት ተበርክቶላቸዋል', 'ከምርቃቱ አስቀድሞ በባቡል ከይር የበጎ አድራጎት ማዕከል የበጎ ፈቃድ ድጋፍ ተከናውኗል'], 'honoreeEn' => 'Gash Ermias Woldeamlak (Special Lifetime Award)', 'honoreeAm' => 'ጋሽ ኤርሚያስ ወልደአምላክ (ልዩ የክብር ተሸላሚ)'],
    ['yearEc' => '2015 E.C.', 'dateEn' => 'August 7, 2023 (Nehase 1, 2015 E.C.)', 'dateAm' => 'ነሐሴ 1 / 2015 ዓ.ም', 'venueEn' => 'Hager Fikir Theatre (Piassa)', 'venueAm' => 'ሀገር ፍቅር ቴአትር (ፒያሳ)', 'graduatesCount' => 'Multi-Shift Cohort', 'highlightsEn' => ['Hosted at Ethiopia’s historic Hager Fikir Theatre immediately following TVET College accreditation', 'Inaugurated the signature "Best Ethical Filmmaker" award category alongside 8+ craft prizes'], 'highlightsAm' => ['በታሪካዊው ሀገር ፍቅር ቴአትር በድምቀት ተካሂዷል', '«ምርጥ የፊልም ሰሪ ሙያ ስነ-ምግባር ባለቤት» የተሰኘው ልዩ ዘርፍ ለመጀመሪያ ጊዜ ተካቷል']],
    ['yearEc' => '2014 E.C.', 'dateEn' => 'September 4, 2022 (Nehase 29, 2014 E.C.)', 'dateAm' => 'ነሐሴ 29 / 2014 ዓ.ም', 'venueEn' => 'Addis Ababa Cinema Exhibition Hall', 'venueAm' => 'አዲስ አበባ ሲኒማ አዳራሽ', 'graduatesCount' => '2012–2014 E.C. Combined Cohorts', 'highlightsEn' => ['Landmark post-COVID graduation uniting cohorts trained since 2012 E.C.', 'Top student short films ("Miknyat", "Deg Lerasu", "Nekut") subsequently showcased at Alliance Éthio-Française with actress Kidist Siyum Beza'], 'highlightsAm' => ['በኮቪድ ምክንያት ተራዝሞ የነበረው ከ2012 ዓ.ም ጀምሮ ያሉ ባቾች የጋራ ምርቃት', '«ምክንያት»፣ «ደግ ለራሱ» እና «ነቁጥ» አጫጭር ፊልሞች በአሊያንስ ኢትዮ-ፍራንሲስ ለዕይታ በቅተዋል']],
];

// ── Showcase page copy (ShowcasePage.tsx) ─────────────────────────────────
$EFC_SHOWCASE = [

    'hero_eyebrow' => ['en' => 'ENDI AWARD · GUMA QUOTA · ALUMNI CINEMA', 'am' => 'እንዲ አዋርድ · ጉማ አዋርድ · የተማሪ ስራዎች'],
    'hero_h1' => ['en' => 'Celebrating Student Cinema on Ethiopia’s Grandest Screens', 'am' => 'የተማሪዎች የጥበብ ፍሬ በታላላቅ የሲኒማ አዳራሾች የሚከበርበት መድረክ'],
    'hero_lead' => ['en' => 'Every graduating team produces an original broadcast-quality short film, a theatrical trailer, and a framed 65×94 cm poster—competing for the prestigious Endi Award ("እንዲ አዋርድ") and our 3-film direct quota at the national Guma Award.', 'am' => 'ተመራቂ ተማሪዎች ደረጃውን የጠበቀ አጭር ፊልም፣ ትሬለርና ባለ 65×94 ሴ.ሜ ፖስተር በማዘጋጀት በ12 የ«እንዲ አዋርድ» ዘርፎች እና በ«ጉማ አዋርድ» የ3 ፊልም ኮታ ይወዳደራሉ።'],
    's1_eyebrow' => ['en' => 'The Official “Endi Award” (እንዲ አዋርድ)', 'am' => 'የኮሌጁ ልዩ ሽልማት — «እንዲ አዋርድ»'],
    's1_h2' => ['en' => '12 Specializations Honoring Craft, Ethics & Audience Impact', 'am' => 'በ12 የሙያና የስነ-ምግባር ዘርፎች የሚሰጥ ዕውቅና'],
    's1_badge' => ['en' => 'Substandard films repeat before clearance — 100% Quality Standard', 'am' => 'ደረጃውን ያልጠበቀ ፊልም አይመረቅም — 100% የጥራት ቁጥጥር'],
    'guma_h3' => ['en' => 'Direct 3-Short-Film Institutional Quota in Ethiopia’s Guma Award', 'am' => 'በታላቁ «ጉማ አዋርድ» በየዓመቱ 3 አጫጭር ፊልሞችን በቀጥታ የማወዳደር ኮታ'],
    'guma_p' => ['en' => 'Under a direct institutional partnership, Endrias Film College selects three standout student short films annually to enter the Guma Award—providing participating teams with free Blackmagic/cinema cameras, lighting kits, and dedicated faculty script-to-edit supervision.', 'am' => 'ኮሌጁ ከጉማ አዋርድ ጋር ባለው ልዩ ስምምነት መሰረት፤ ተማሪዎቹ የሚሰሯቸውን 3 ምርጥ አጫጭር ፊልሞች ነፃ የቀረፃ ካሜራ፣ መብራትና የመምህራን ድጋፍ በማቅረብ በቀጥታ ለውድድር ያቀርባል።'],
    'guma_cta' => ['en' => 'See Eligible Diploma & Certificate Tracks', 'am' => 'ተሳታፊ የሚያደርጉ ፕሮግራሞችን ይመልከቱ'],
    's2_eyebrow' => ['en' => 'Landmark Cinema Graduations', 'am' => 'ታሪካዊ የምርቃት እና የፊልም ዕይታ መድረኮች'],
    's2_h2' => ['en' => 'Addis Cinema Complex · Alem Cinema · Hager Fikir Theatre', 'am' => 'በአዲስ ሲኒማ ኮምፕሌክስ፣ አለም ሲኒማ እና ሀገር ፍቅር ቴአትር የተካሄዱ ምርቃቶች'],
    's3_eyebrow' => ['en' => 'Alumni & Student Film Highlights', 'am' => 'ድንቅ ምሩቃን እና የተማሪዎች አጫጭር ፊልሞች'],
    's3_h2' => ['en' => 'Stories Born at Endrias Film College', 'am' => 'ከኮሌጃችን የፈለቁ ስኬታማ ስራዎችና ተዋንያን'],
    'al1_h3' => ['en' => 'Yodit Solomon — “Hagere” (ሀገሬ)', 'am' => 'ዮዲት ሰሎሞን — «ሀገሬ» አጭር ፊልም'],
    'al1_p' => ['en' => 'At the February 2026 (2018 E.C.) graduation at Addis Cinema Complex, Yodit Solomon achieved a rare double triumph: graduating at the top of her academic class while winning the Endi Award for Best Short Film for "Hagere".', 'am' => 'በ2018 ዓ.ም በአዲስ ሲኒማ ኮምፕሌክስ በተካሄደው ምርቃት በከፍተኛ አካዳሚክ ውጤት አንደኛ በመውጣትና በ«ሀገሬ» አጭር ፊልም የእንዲ አዋርድ ምርጥ ፊልም አሸናፊ በመሆን ድርብ ድል አስመዝግባለች።'],
    'al2_h3' => ['en' => 'Dagim Tezera — EBS TV’s “Besintu”', 'am' => 'ህፃን ዳግም ተዘራ — «በስንቱ» (EBS TV)'],
    'al2_p' => ['en' => 'Trained in Endrias Film College’s Kiremt Children & Youth Acting Program, young actor Dagim Tezera rose to national household recognition playing "Nati" on EBS TV’s hit family sitcom Besintu.', 'am' => 'በኮሌጁ የክረምት የህፃናትና ታዳጊዎች የትወና ስልጠና ፕሮግራም ሰልጥኖ በኢቢኤስ ቴሌቪዥን ተወዳጅ በሆነው «በስንቱ» ድራማ ላይ «ናቲ» ሆኖ በመተወን ከፍተኛ አድናቆት አትርፏል።'],
    'al3_h3' => ['en' => 'Alliance Éthio-Française Showcase', 'am' => 'በአሊያንስ ኢትዮ-ፍራንሲስ የታዩ ፊልሞች'],
    'al3_p' => ['en' => 'Graduate short films "Miknyat" (ምክንያት), "Deg Lerasu" (ደግ ለራሱ), and "Nekut" (ነቁጥ) headlined the Obscura Cinema showcase at Alliance Éthio-Française alongside international actress Kidist Siyum Beza.', 'am' => 'በኮሌጁ ተመራቂዎች የተሰሩት «ምክንያት»፣ «ደግ ለራሱ» እና «ነቁጥ» የተሰኙ አጫጭር ፊልሞች በፒያሳ አሊያንስ ኢትዮ-ፍራንሲስ ለዕይታና ለውይይት ቀርበዋል።'],
    ['alumni_icons' => ['film', 'tv', 'sparkles']],
];

// ── Sample job listings (collegeData.ts SAMPLE_JOB_LISTINGS) ──────────────
$EFC_JOB_LISTINGS = [

    ['id' => 'job-1', 'titleEn' => 'TV Broadcast Videographer & Editor', 'titleAm' => 'ለቲቪ የሚላክ ፕሮዳክሽን ቀረፃ እና ኤዲቲንግ', 'category' => 'broadcast', 'districtEn' => 'Lamberet Area, Addis Ababa', 'districtAm' => 'ላምበረት አካባቢ፣ አዲስ አበባ', 'openings' => 3, 'experienceEn' => '0–1 Yr (Strong Graduate Portfolio)', 'experienceAm' => 'ጀማሪ ጥሩ ብቃት ያለው/ያላት', 'employmentTypeEn' => 'Permanent Full-Time', 'employmentTypeAm' => 'ቋሚ', 'postedEc' => '2018/2019 E.C. Track', 'requirementsEn' => ['Proficient in broadcast camera framing and Adobe Premiere Pro / DaVinci Resolve assembly', 'Reliable turnaround for scheduled television segments'], 'requirementsAm' => ['በካሜራ ቀረፃና በቪዲዮ ኤዲቲንግ ጥሩ የተግባር ብቃት ያለው/ያላት', 'ለቴሌቪዥን ስርጭት የሚመጥን ጥራትና ፍጥነት']],
    ['id' => 'job-2', 'titleEn' => 'Multi-Role Crew: Director, Sound Mixer, Editor & Continuity', 'titleAm' => 'አዘጋጅ፣ ድምፅ ቀጂ፣ ኤዲተር፣ ሜካፕና ኮንቲኒቲ እና ፕሮዳክሽን ማናጀር', 'category' => 'production', 'districtEn' => 'Addis Ababa Studio Network', 'districtAm' => 'አዲስ አበባ', 'openings' => 12, 'experienceEn' => '1-Year Certificate & Above (Entry-Ready)', 'experienceAm' => 'ከ1 ዓመት ሰልጣኝ ጀምሮ (ጥሩ ጀማሪ)', 'employmentTypeEn' => 'Full-Time Production Crew', 'employmentTypeAm' => 'ቋሚ / ፕሮጀክት', 'postedEc' => 'Active Alumni Pipeline', 'requirementsEn' => ['2 openings each across Directing, Makeup & Continuity, Videography, Editing, Production Management & Production Sound Mixing', 'Zero placement fee or commission charged by Endrias Film College'], 'requirementsAm' => ['በ6 የሙያ ዘርፎች በእያንዳንዳቸው 2 ባለሙያዎች (ጠቅላላ 12 ክፍት ቦታዎች)', 'ኮሌጁ ምንም አይነት ኮሚሽን አይቀበልም']],
    ['id' => 'job-3', 'titleEn' => 'Cinematographer & Video Editor', 'titleAm' => 'ሲኒማቶግራፈር እና ቪዲዮ ኤዲተር', 'category' => 'camera', 'districtEn' => 'Bole & Gerji Mebrat Hail, Addis Ababa', 'districtAm' => 'ቦሌ እና ገርጂ መብራት ኃይል አካባቢ', 'openings' => 10, 'experienceEn' => '0 Years (Competent Graduate)', 'experienceAm' => '0 ዓመት (በሙያው ብቁ የሆነ/ነች)', 'employmentTypeEn' => 'Permanent Full-Time', 'employmentTypeAm' => 'ቋሚ', 'postedEc' => 'Verified Placement Partner', 'requirementsEn' => ['Completed Filmmaking, Audio-Visual, or Cinematography specialization', 'Strong lighting and camera movement fundamentals'], 'requirementsAm' => ['በፊልም ሜኪንግ ወይም በሲኒማቶግራፊና ኤዲቲንግ ተምሮ የጨረሰ/ች', 'የካሜራና መብራት መሰረታዊ ብቃት']],
    ['id' => 'job-4', 'titleEn' => 'Digital Content Creator, Mobile Shooter & Thumbnail Designer', 'titleAm' => 'በስልክ ቀርፆ በኮምፒውተር ኤዲት እና Thumbnail መስራት የሚችል', 'category' => 'editing', 'districtEn' => '22 Mazoria & Megenagna, Addis Ababa', 'districtAm' => '22 እና መገናኛ አካባቢ', 'openings' => 5, 'experienceEn' => 'Entry-Level Creator Portfolio', 'experienceAm' => 'ጥሩ የተግባር ችሎታ', 'employmentTypeEn' => 'Full-Time & Part-Time (2 Days/Wk)', 'employmentTypeAm' => 'ቋሚ እና በሳምንት 2 ቀን', 'postedEc' => '2018/2019 E.C. Digital Track', 'requirementsEn' => ['Shoot crisp vertical/horizontal video, edit on PC, and design engaging Photoshop thumbnails', 'Basic After Effects motion titles a plus'], 'requirementsAm' => ['በስልክ ወይም ካሜራ ቀርፆ በኮምፒውተር ኤዲት ማድረግና Thumbnail መስራት', 'መጠነኛ የአፍተር ኢፌክት እውቀት ተጨማሪ እሴት ይሆናል']],
    ['id' => 'job-5', 'titleEn' => 'Studio Audience Host & TV Program Presenter', 'titleAm' => 'የኦዲየንስ ሆስት እና የቴሌቪዥን ፕሮግራም አቅራቢ', 'category' => 'broadcast', 'districtEn' => 'Hayat / Summit, Addis Ababa', 'districtAm' => 'ሀያት ሰሚት አካባቢ፣ አዲስ አበባ', 'openings' => 5, 'experienceEn' => '0 Years (Diploma/Certificate in Film, Theatre or Journalism)', 'experienceAm' => '0 ዓመት (በፊልም፣ ትያትር ወይም ጋዜጠኝነት የተማረ/ች)', 'employmentTypeEn' => 'Permanent Full-Time', 'employmentTypeAm' => 'ቋሚ', 'postedEc' => 'Verified Broadcast Partner', 'requirementsEn' => ['Strong on-camera presence, vocal clarity, and audience engagement'], 'requirementsAm' => ['ጥሩ የካሜራ ፊት ብቃትና የተግባቦት ክህሎት']],
];

// ── Careers page copy + category tabs (CareersPage.tsx) ──────────────────
$EFC_CAREERS = [

    'hero_eyebrow' => ['en' => 'ZERO-COMMISSION CAREER PLACEMENT', 'am' => 'ከኮሚሽን ነፃ የስራ ትስስር ማዕከል'],
    'hero_h1' => ['en' => 'Direct Studio & Broadcast Placement—With Zero Commission', 'am' => 'ለምሩቃን እና ለቀጣሪ ተቋማት 100% ከኮሚሽን ነፃ የስራ ትስስር'],
    'hero_lead' => ['en' => 'As a core institutional social responsibility, Endrias Film College connects its Diploma and Certificate graduates directly with TV channels, production studios, agencies, and corporate media teams across Addis Ababa without charging a single birr in commission.', 'am' => 'ኮሌጁ ለሚያመቻቻቸው ለሁሉም የስራ ትስስሮች ምንም አይነት ኮሚሽን የማይቀበል መሆኑን እያሳወቀ፣ ይህንንም እንደ አንድ ዋነኛ ማህበራዊ ኃላፊነቱ ይወስደዋል።'],
    's1_eyebrow' => ['en' => 'Verified Alumni Placement Tracks', 'am' => 'ተደጋጋሚ የስራ ዕድል መስኮች'],
    's1_h2' => ['en' => 'Active Industry Placement Categories in Addis Ababa', 'am' => 'በአዲስ አበባ ለምሩቃን የሚመቻቹ የስራ መደቦች'],
    'positions' => ['en' => 'Positions', 'am' => 'ክፍት ቦታዎች'],
    'zero_fee' => ['en' => '0% Commission Guaranteed', 'am' => '0% ኮሚሽን (ነፃ አገልግሎት)'],
    'apply_tel' => ['en' => 'Apply via @efcfilm', 'am' => 'በ @efcfilm ያመልክቱ'],
    's2_h2' => ['en' => 'Alumni Talent Desk & Employer Crew Request Portal', 'am' => 'የምሩቃን የስራ መመዝገቢያ እና የቀጣሪ ድርጅቶች መጠየቂያ ቅጽ'],
    's2_lead' => ['en' => 'Prepare a structured placement dispatch for immediate review by our Career Office (@efcfilm / @al0254).', 'am' => 'መረጃዎን በመሙላት ለኮሌጁ የስራ ትስስር ክፍል (@efcfilm / @al0254) በቀጥታ ይላኩ።'],
    'tab_alumni' => ['en' => 'I Am an Endrias Graduate', 'am' => 'የኮሌጁ ምሩቅ ነኝ (ስራ ፈላጊ)'],
    'tab_employer' => ['en' => 'Hire Endrias Crew (0% Fee)', 'am' => 'ባለሙያ እፈልጋለሁ (ቀጣሪ ድርጅት)'],
    'a_name' => ['en' => 'Full Name *', 'am' => 'ሙሉ ስም *'],
    'a_name_ph' => ['en' => 'e.g. Abenezer Zenebe', 'am' => 'ለምሳሌ፦ አቤኔዘር ዘነበ'],
    'a_phone' => ['en' => 'Phone / Telegram Number *', 'am' => 'ስልክ ቁጥር *'],
    'a_track' => ['en' => 'Completed Program Track', 'am' => 'ያጠናቀቁት የትምህርት ፕሮግራም'],
    'a_role' => ['en' => 'Primary Specialization', 'am' => 'የሚፈልጉት የስራ መስክ'],
    'a_portfolio' => ['en' => 'Showreel Link or Key Skills Summary', 'am' => 'የሰሩት ፊልም / ፖርትፎሊዮ ሊንክ ወይም አጭር መግለጫ'],
    'a_portfolio_ph' => ['en' => 'Share Premiere/DaVinci proficiency, graduation short film role, or drive link...', 'am' => 'ስለ ችሎታዎ ወይም የመመረቂያ ፊልምዎ አጭር ማብራሪያ ይፃፉ...'],
    'a_submit' => ['en' => 'Generate Placement Dispatch', 'am' => 'የስራ ትስስር ማመልከቻ አዘጋጅ'],
    'e_name' => ['en' => 'Production House / Company Name *', 'am' => 'የድርጅቱ / ፕሮዳክሽኑ ስም *'],
    'e_name_ph' => ['en' => 'Studio, TV Channel, Agency or NGO', 'am' => 'የድርጅት ስም'],
    'e_phone' => ['en' => 'Hiring Manager Phone *', 'am' => 'የስልክ ቁጥር *'],
    'e_role' => ['en' => 'Crew Role(s) Needed *', 'am' => 'የሚፈለገው የባለሙያ አይነት *'],
    'e_count' => ['en' => 'Headcount', 'am' => 'ብዛት'],
    'e_district' => ['en' => 'Workplace District in Addis Ababa', 'am' => 'የስራ ቦታ አድራሻ'],
    'e_submit' => ['en' => 'Prepare Zero-Commission Crew Request', 'am' => 'ከኮሚሽን ነፃ የባለሙያ ጥያቄ አዘጋጅ'],
    'r_header' => ['en' => 'DISPATCH READY FOR @efcfilm / @al0254', 'am' => 'መልዕክቱ ተዘጋጅቷል'],
    'copied' => ['en' => 'Copied!', 'am' => 'ተቀድቷል!'],
    'copy' => ['en' => 'Copy Text', 'am' => 'ጽሑፉን ቅዳ'],
    'r_tel' => ['en' => 'Open @efcfilm Placement Desk', 'am' => 'ወደ @efcfilm ላክ'],
    'r_mail' => ['en' => 'Send via Official Email', 'am' => 'በኢሜይል ላክ'],
    ['cat_tabs' => [['id' => 'all', 'en' => 'All Categories', 'am' => 'ሁሉም'], ['id' => 'broadcast', 'en' => 'TV & Broadcast', 'am' => 'ቴሌቪዥንና ሆስት'], ['id' => 'production', 'en' => 'Full Film Crew', 'am' => 'የፊልም ፕሮዳክሽን'], ['id' => 'camera', 'en' => 'Cinematography', 'am' => 'ሲኒማቶግራፊ'], ['id' => 'editing', 'en' => 'Editing & Digital', 'am' => 'ኤዲቲንግና ዲጂታል']]],
    ['track_options' => ['Level IV Regular Diploma', '1-Year General Filmmaking Certificate', '9-Month Audio-Visual Certificate', '3-Month / 2-Month Specialized Track']],
    ['pledge_am' => 'ኮሌጁ ለሚያመቻቻቸው ለሁሉም የስራ ትስስሮች ምንም አይነት ኮሚሽን የማይቀበል መሆኑን እያሳወቅን ይልቁንም እንደ አንድ ማህበራዊ ኃላፊነቱ ይወስደዋል።'],
    ['depts' => ['@efcfilm · @al0254']],
];

// ── Admissions page copy (AdmissionsPage.tsx) ─────────────────────────────
$EFC_ADMISSIONS = [

    'hero_eyebrow' => ['en' => '2019 E.C. ADMISSIONS & 4 KILO CAMPUS', 'am' => 'የ2019 ዓ.ም ምዝገባ እና አድራሻ'],
    'hero_h1' => ['en' => 'Begin Your Cinema Journey at 4 Kilo Roundabout', 'am' => 'የ2019 ዓ.ም የፊልም ጥበብ ምዝገባዎን ዛሬውኑ ይጀምሩ'],
    'hero_lead' => ['en' => 'Complete your online pre-registration slip below or visit our 4 Kilo Main Campus (next to Wekma & Holy Trinity University 4th Floor) to tour our studios and finalize your shift.', 'am' => 'የቅድመ-ምዝገባ ቅጹን ከታች ይሙሉ ወይም 4 ኪሎ አደባባይ ከወክማ ጎን (እና ቅድስት ሥላሴ ዩኒቨርሲቲ 4ኛ ፎቅ) በሚገኘው ካምፓሳችን በአካል በመገኘት ይመዝገቡ።'],
    'form_step' => ['en' => 'Step 1 · Student Pre-Registration', 'am' => 'ደረጃ 1 · የቅድመ-ምዝገባ ቅጽ'],
    'form_h2' => ['en' => 'Generate Your Admission & Shift Slip', 'am' => 'የመመዝገቢያና የፈረቃ ምርጫ ቅጽ'],
    'f_name' => ['en' => 'Applicant Full Name *', 'am' => 'ሙሉ ስም *'],
    'f_name_ph' => ['en' => 'Enter your full name', 'am' => 'ሙሉ ስምዎን ያስገቡ'],
    'f_phone' => ['en' => 'Phone Number *', 'am' => 'ስልክ ቁጥር *'],
    'f_email' => ['en' => 'Email Address (Optional)', 'am' => 'ኢሜይል (ካለዎት)'],
    'f_edu' => ['en' => 'Prior Education / Certificate', 'am' => 'የትምህርት ዝግጅት'],
    'f_program' => ['en' => 'Select Academic Program Tier *', 'am' => 'የትምህርት ፕሮግራም ይምረጡ *'],
    'f_shift' => ['en' => 'Preferred 2-Hour Daily Shift *', 'am' => 'የሚመርጡት የመማሪያ ፈረቃ *'],
    'f_notes' => ['en' => 'Chosen Courses (for 3-Month / 1-Month VIP) or Questions', 'am' => 'የመረጧቸው ኮርሶች (ለ3 ወር / ለ1 ወር VIP) ወይም ተጨማሪ ጥያቄ'],
    'f_notes_ph' => ['en' => 'e.g. Interested in Cinematography + Video Editing, or VFX 1-on-1 VIP class...', 'am' => 'ለምሳሌ፦ ሲኒማቶግራፊ እና ኤዲቲንግ፣ ወይም የግል VFX ስልጠና...'],
    'f_submit' => ['en' => 'Generate Official Pre-Registration Slip', 'am' => 'የቅድመ-ምዝገባ ካርድ አዘጋጅ'],
    'slip_copied' => ['en' => 'Copied', 'am' => 'ተቀድቷል'],
    'slip_copy' => ['en' => 'Copy', 'am' => 'ቅዳ'],
    'slip_print' => ['en' => 'Print', 'am' => 'ፕሪንት'],
    'slip_tel' => ['en' => 'Send Slip to Registrar (@al0254)', 'am' => 'ለሬጅስትራር (@al0254) በቴሌግራም ላክ'],
    'slip_mail' => ['en' => 'Submit via Email', 'am' => 'በኢሜይል ላክ'],
    'dir_eyebrow' => ['en' => 'Campus & Registrar Directory', 'am' => 'የካምፓስ አድራሻ እና ስልኮች'],
    'dir_h3' => ['en' => 'Visit Us at 4 Kilo Roundabout', 'am' => '4 ኪሎ አደባባይ በአካል ያግኙን'],
    'dir_trinity' => ['en' => 'Holy Trinity Building Studio Hall', 'am' => 'ቅድስት ሥላሴ ዩኒቨርሲቲ ህንፃ'],
    'dir_hl' => ['en' => 'Direct Admissions Hotlines', 'am' => 'ቀጥታ የምዝገባ ስልክ መስመሮች'],
    'dir_email' => ['en' => 'Official Emails & Telegram', 'am' => 'ኦፊሻል ኢሜይል እና ቴሌግራም'],
    'guide_h3' => ['en' => 'Registrar & Graduation Guidelines', 'am' => 'የምዝገባ፣ ክፍያ እና ምርቃት ደንቦች'],
    'g1_label' => ['en' => 'Monthly Tuition Window:', 'am' => 'ወርሃዊ ክፍያ፦'],
    'g1' => ['en' => 'Paid between the 27th and 3rd of every Ethiopian calendar month.', 'am' => 'በየወሩ ከ27 እስከ 3 ባሉት ቀናት ውስጥ ይፈፀማል።'],
    'g2_label' => ['en' => 'Graduation Film Package:', 'am' => 'የመመረቂያ ፕሮጀክት፦'],
    'g2' => ['en' => 'Master short film + ≤1–2 min trailer + 65×94 cm framed banner poster approved prior to print.', 'am' => 'አጭር ፊልም፣ ትሬለር እና 65×94 ሴ.ሜ የተወጠረ ፖስተር ማስረከብ ይጠበቃል።'],
    'g3_label' => ['en' => 'COC Competency Support:', 'am' => 'የCOC ምዘና ድጋፍ፦'],
    'g3' => ['en' => 'Free exam prep tutorials for Endrias graduates; candidates only pay the 500 ETB government COC fee.', 'am' => 'ነፃ የCOC መሰናዶ ትምህርት ይሰጣል፤ ተመዛኞች የመንግስት 500 ብር ብቻ ይከፍላሉ።'],
    'dir_main' => ['en' => 'Main Campus (ዋናው ካምፓስ)', 'am' => 'ዋናው ካምፓስ'],
    ['edu_options' => ['Grade 10 / 12 Complete', '1-Year Filmmaking Certificate (+10-Mo Diploma Bridge)', 'University / College Student or Graduate', 'Working Media Professional / Content Creator', 'Youth / Kiremt Talent Trainee']],
    ['slip_campus' => 'Campus: 4 Kilo Roundabout, Next to Wekma (Main Campus), Addis Ababa'],
];
