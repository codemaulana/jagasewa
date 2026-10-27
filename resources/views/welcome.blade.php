<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JagaSewa — Platform Booking & Manajemen Rental Barang Aman</title>
    <meta name="description" content="Temukan dan booking barang rental (kamera, kostum cosplay, kebaya, busana adat) tanpa perlu membuat akun. Aman, cepat, dan terkonfirmasi langsung via WhatsApp.">
    <link rel="icon" type="image/png" href="{{ asset('jagasewa_logo3.png') }}">

    <!-- Google Fonts: Cormorant Garamond (Luxury Serif), Plus Jakarta Sans, & Inter Tight -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500;1,600&family=Inter+Tight:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- GSAP & ScrollTrigger (Local First with CDN Fallback) -->
    <script src="{{ asset('js/gsap.min.js') }}"></script>
    <script src="{{ asset('js/ScrollTrigger.min.js') }}"></script>
    <script>
        if (typeof gsap === 'undefined') {
            document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"><\/script>');
            document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"><\/script>');
        }
    </script>
</head>
<body class="min-h-screen flex flex-col justify-between selection:bg-accent-lime selection:text-white">

    
    <header id="site-header" class="w-full border-b border-border-subtle/70 bg-bg-base/90 backdrop-blur-md sticky top-0 z-50 transition-colors duration-500">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            
            <a href="{{ url('/') }}" class="flex items-center gap-2 group transition hover:opacity-90">
                <img src="{{ asset('jagasewa_logo1.png') }}" 
                     alt="JagaSewa" 
                     class="h-10 sm:h-11 w-auto object-contain">
            </a>

            <nav class="hidden md:flex items-center gap-8 lg:gap-10 text-sm font-medium text-text-main">
                <a href="#jelajahi-rental" id="nav-katalog" class="nav-link hover:text-text-main transition-colors py-2 relative group">
                    <span>Katalog</span>
                    <span class="nav-indicator absolute bottom-1 left-0 w-0 h-0.5 bg-primary transition-all duration-300 group-hover:w-full"></span>
                </a>

                <a href="#reveal-showcase" id="nav-mitra" class="nav-link hover:text-text-main transition-colors py-2 relative group">
                    <span>Panggung Mitra</span>
                    <span class="nav-indicator absolute bottom-1 left-0 w-0 h-0.5 bg-primary transition-all duration-300 group-hover:w-full"></span>
                </a>

                <a href="#cara-kerja" id="nav-cara-kerja" class="nav-link hover:text-text-main transition-colors py-2 relative group">
                    <span>Cara Kerja</span>
                    <span class="nav-indicator absolute bottom-1 left-0 w-0 h-0.5 bg-primary transition-all duration-300 group-hover:w-full"></span>
                </a>

                <a href="#keunggulan" id="nav-keunggulan" class="nav-link hover:text-text-main transition-colors py-2 relative group">
                    <span>Keunggulan</span>
                    <span class="nav-indicator absolute bottom-1 left-0 w-0 h-0.5 bg-primary transition-all duration-300 group-hover:w-full"></span>
                </a>
            </nav>

            <div class="flex items-center gap-3">
                <a href="{{ url('/vendor/login') }}" class="btn-fill-dark btn-magnetic text-xs sm:text-sm font-semibold h-11 px-5 sm:px-6 rounded-full inline-flex items-center gap-2 text-white bg-primary shadow-sm">
                    <svg class="w-4 h-4 text-white/90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                    <span>Masuk</span>
                </a>

                <!-- Mobile Hamburger Button -->
                <button type="button" 
                        onclick="document.getElementById('mobile-menu').classList.toggle('hidden')"
                        class="md:hidden p-2 rounded-xl text-neutral-700 hover:text-neutral-900 hover:bg-surface-subtle transition"
                        aria-label="Toggle Menu">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Drawer Menu (Sesuai Urutan Section) -->
        <div id="mobile-menu" class="hidden md:hidden border-t border-border-subtle bg-bg-base/95 backdrop-blur-md px-4 pt-3 pb-5 space-y-2">
            <a href="#jelajahi-rental" 
               onclick="document.getElementById('mobile-menu').classList.add('hidden')"
               class="block px-3 py-2 text-sm font-medium text-neutral-800 hover:text-text-main rounded-lg hover:bg-neutral-100">
               Katalog
            </a>
            <a href="#reveal-showcase" 
               onclick="document.getElementById('mobile-menu').classList.add('hidden')"
               class="block px-3 py-2 text-sm font-medium text-neutral-800 hover:text-text-main rounded-lg hover:bg-neutral-100">
               Panggung Mitra
            </a>
            <a href="#cara-kerja" 
               onclick="document.getElementById('mobile-menu').classList.add('hidden')"
               class="block px-3 py-2 text-sm font-medium text-neutral-800 hover:text-text-main rounded-lg hover:bg-neutral-100">
               Cara Kerja
            </a>
            <a href="#keunggulan" 
               onclick="document.getElementById('mobile-menu').classList.add('hidden')"
               class="block px-3 py-2 text-sm font-medium text-neutral-800 hover:text-text-main rounded-lg hover:bg-neutral-100">
               Keunggulan
            </a>
        </div>
    </header>

    <main id="main-content">

        
        <section id="hero-section" class="relative overflow-hidden pt-8 pb-14 sm:pt-12 sm:pb-20 lg:pt-16 lg:pb-24">
            
            <!-- Golden Ribbon Background Curve SVG -->
            <div id="hero-ribbon-wrapper" class="absolute inset-0 pointer-events-none overflow-hidden z-0">
                <svg id="hero-ribbon-svg" class="w-full h-full min-w-[1100px] opacity-85" viewBox="0 0 1600 850" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="goldRibbon" x1="0%" y1="20%" x2="100%" y2="80%">
                            <stop offset="0%" stop-color="rgb(220, 218, 210)" stop-opacity="0.85"/>
                            <stop offset="30%" stop-color="rgb(200, 195, 185)" stop-opacity="1"/>
                            <stop offset="60%" stop-color="rgb(215, 212, 202)" stop-opacity="0.9"/>
                            <stop offset="85%" stop-color="rgb(190, 185, 175)" stop-opacity="0.85"/>
                            <stop offset="100%" stop-color="rgb(210, 205, 195)" stop-opacity="0.8"/>
                        </linearGradient>
                        <filter id="softGlow" x="-20%" y="-20%" width="140%" height="140%">
                            <feGaussianBlur stdDeviation="2.5" result="blur" />
                            <feComposite in="SourceGraphic" in2="blur" operator="over" />
                        </filter>
                    </defs>
                    <path d="M 100,520 C 350,620 520,320 820,180 C 1050,70 1350,110 1550,340" 
                          stroke="url(#goldRibbon)" stroke-width="2.5" fill="none" filter="url(#softGlow)"/>
                    <path d="M 140,540 C 380,630 540,350 830,210 C 1030,110 1310,130 1510,330" 
                          stroke="url(#goldRibbon)" stroke-width="1" stroke-dasharray="8 6" fill="none" opacity="0.45"/>
                    <path d="M 320,710 C 580,800 850,780 1150,680 C 1380,600 1540,480 1580,340" 
                          stroke="url(#goldRibbon)" stroke-width="2" fill="none"/>
                    <path d="M 1200,680 C 1420,620 1560,500 1580,320 C 1600,160 1480,90 1320,130" 
                          stroke="url(#goldRibbon)" stroke-width="1.8" fill="none" opacity="0.45"/>
                </svg>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-8 items-center min-h-[540px]">
                    
                    <div class="lg:col-span-5 space-y-6 text-left hero-text-block">
                        <div class="space-y-2 overflow-hidden">
                            <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-[54px] font-extrabold text-text-main tracking-tight leading-[1.12]">
                                <span class="hero-bounce-line-1 block">Barang yang kamu butuhkan.</span>
                                <span class="hero-bounce-line-2 font-luxury italic font-normal text-text-main block text-4xl sm:text-5xl md:text-6xl lg:text-[62px] mt-1">Waktu yang kamu tentukan.</span>
                            </h1>
                        </div>
                        
                        <p class="hero-bounce-sub text-base sm:text-lg text-text-secondary max-w-lg leading-relaxed font-normal">
                            Temukan dan booking barang rental dari berbagai bisnis, tanpa perlu membuat akun.
                        </p>

                        <div class="hero-bounce-cta flex flex-col sm:flex-row items-stretch sm:items-center gap-3.5 pt-2 w-full max-w-md sm:max-w-none">
                            <a href="#jelajahi-rental" class="btn-fill-dark btn-magnetic w-full sm:w-auto min-h-[52px] sm:min-h-[56px] px-8 rounded-full text-sm sm:text-base font-bold inline-flex items-center justify-center gap-2.5 text-white bg-primary shadow-md group">
                                <span>Jelajahi Rental</span>
                                <span class="text-lg leading-none transition-transform duration-300 group-hover:translate-x-1.5">&rarr;</span>
                            </a>

                            <a href="{{ url('/vendor/register') }}" class="btn-fill-outline btn-magnetic w-full sm:w-auto min-h-[52px] sm:min-h-[56px] px-7 rounded-full text-xs sm:text-sm font-semibold inline-flex items-center justify-center text-center text-text-main border-2 border-border-subtle bg-transparent">
                                <span>Gabung sebagai<br class="hidden sm:inline"> Pemilik Rental</span>
                            </a>
                        </div>
                    </div>

                    <div class="lg:col-span-7 relative w-full flex items-center justify-center lg:justify-end">
                        <div id="hero-interactive-canvas" class="relative w-full max-w-[680px] h-[380px] sm:h-[460px] md:h-[520px] lg:h-[560px]">
                            
                            <div class="hero-shadow-floor absolute bottom-4 left-1/2 -translate-x-1/2 w-4/5 h-14 bg-black/15 blur-2xl rounded-full pointer-events-none"></div>

                            <div class="hero-cosplay js-zoom-out absolute bottom-6 sm:bottom-8 left-[12%] sm:left-[16%] h-[82%] sm:h-[84%] flex items-end justify-center z-10 cursor-pointer"
                                 title="Arahkan kursor untuk efek zoom out">
                                <img src="{{ asset('cosplay.png') }}" 
                                     alt="Rental Kostum Cosplay Anime Lengkap" 
                                     class="h-full w-auto object-contain object-bottom shadow-mannequin pointer-events-auto"
                                     loading="eager" 
                                     fetchpriority="high">
                            </div>

                            <div class="hero-kebaya absolute bottom-6 sm:bottom-8 left-[44%] sm:left-[46%] h-[90%] sm:h-[93%] flex items-end justify-center z-10">
                                <img src="{{ asset('kebaya.png') }}" 
                                     alt="Rental Kebaya Modern & Wisuda" 
                                     class="h-full w-auto object-contain object-bottom shadow-mannequin pointer-events-auto"
                                     loading="eager" 
                                     fetchpriority="high">
                            </div>

                            <div class="hero-adat js-zoom-out absolute bottom-6 sm:bottom-8 right-[-2%] sm:right-[1%] h-[86%] sm:h-[89%] flex items-end justify-center z-10 cursor-pointer"
                                 title="Arahkan kursor untuk efek zoom out">
                                <img src="{{ asset('adat.png') }}" 
                                     alt="Rental Busana Pengantin Adat Tradisional" 
                                     class="h-full w-auto object-contain object-bottom shadow-mannequin pointer-events-auto"
                                     loading="eager" 
                                     fetchpriority="high">
                            </div>

                            <div class="hero-camera absolute bottom-2 sm:bottom-3 left-[-2%] sm:left-[2%] h-[48%] sm:h-[54%] flex items-end justify-center z-20">
                                <img src="{{ asset('kamera.png') }}" 
                                     alt="Rental Kamera Mirrorless & Lensa Sony" 
                                     class="h-full w-auto object-contain object-bottom shadow-ground pointer-events-auto"
                                     loading="eager" 
                                     fetchpriority="high">
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </section>

        
        <section id="jelajahi-rental" class="py-16 sm:py-24 border-t border-border-subtle/70 relative overflow-hidden bg-transparent">
            
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                
                <div class="kategori-header flex flex-col md:flex-row md:items-end justify-between mb-12 sm:mb-16 gap-4">
                    <div class="space-y-2">
                        <span class="reveal-bounce-left inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-semibold bg-surface-subtle/70 text-text-secondary border border-border-subtle/40">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                            <span>Katalog Pilihan JagaSewa</span>
                        </span>
                        <h2 class="reveal-bounce-left text-2xl sm:text-3xl md:text-4xl font-extrabold text-text-main tracking-tight">
                            Jelajahi Berdasarkan Kategori
                        </h2>
                        <p class="reveal-bounce-left text-sm sm:text-base text-neutral-600 max-w-xl">
                            Semua inventaris terdaftar memiliki proteksi jadwal real-time untuk memastikan barang siap saat Anda butuhkan.
                        </p>
                    </div>

                    <a href="{{ url('/vendor/register') }}" class="reveal-bounce-left text-xs sm:text-sm font-semibold text-text-secondary hover:text-text-main inline-flex items-center gap-1.5 transition">
                        <span>Punya inventaris rental? Pasang di sini</span>
                        <span>&rarr;</span>
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-7">
                    
                    <div class="kategori-card card-editorial rounded-2xl p-5 flex flex-col justify-between h-full group">
                        <div>
                            <div class="pedestal-stage h-48 w-full rounded-xl mb-4 flex items-center justify-center p-3 relative overflow-hidden">
                                <span class="absolute top-3 left-3 px-2.5 py-1 rounded-full text-[10px] font-bold bg-primary text-white shadow-xs z-10">
                                    Paling Diminati
                                </span>
                                <span class="absolute top-3 right-3 text-[11px] font-bold text-text-secondary">
                                    Rp 75rb<span class="text-[9px] font-normal text-neutral-500">/hr</span>
                                </span>
                                <img src="{{ asset('cosplay.png') }}" 
                                     alt="Rental Kostum Cosplay" 
                                     class="h-full w-auto object-contain transition-transform duration-500 group-hover:scale-90 js-zoom-out">
                            </div>
                            
                            <div class="space-y-1.5">
                                <h3 class="text-base font-bold text-text-main group-hover:text-text-main transition-colors">
                                    Kostum Cosplay Anime
                                </h3>
                                <p class="text-xs text-neutral-500 leading-relaxed">
                                    Fullset kostum, wig tertata rapi, boots, dan aksesori senjata busa untuk event & photoshoot.
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-1.5 pt-3">
                                <span class="px-2 py-0.5 rounded-md bg-surface-subtle text-[10px] font-medium text-neutral-600">Wig Styled</span>
                                <span class="px-2 py-0.5 rounded-md bg-surface-subtle text-[10px] font-medium text-neutral-600">Laundry Steril</span>
                            </div>
                        </div>

                        <div class="mt-5 pt-3.5 border-t border-border-subtle/60 flex items-center justify-between">
                            <span class="text-[11px] font-medium text-text-main inline-flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Siap Booking
                            </span>
                            <a href="{{ url('/b/tokyo-cosplay/frieren-fullset-m') }}" class="text-xs font-semibold text-text-main group-hover:underline inline-flex items-center gap-1">
                                Pilih Jadwal &rarr;
                            </a>
                        </div>
                    </div>

                    <div class="kategori-card card-editorial rounded-2xl p-5 flex flex-col justify-between h-full group">
                        <div>
                            <div class="pedestal-stage h-48 w-full rounded-xl mb-4 flex items-center justify-center p-3 relative overflow-hidden">
                                <span class="absolute top-3 left-3 px-2.5 py-1 rounded-full text-[10px] font-bold bg-primary text-white shadow-xs z-10">
                                    Peralatan Profesional
                                </span>
                                <span class="absolute top-3 right-3 text-[11px] font-bold text-text-secondary">
                                    Rp 250rb<span class="text-[9px] font-normal text-neutral-500">/hr</span>
                                </span>
                                <img src="{{ asset('kamera.png') }}" 
                                     alt="Rental Kamera Mirrorless" 
                                     class="h-full w-auto object-contain transition-transform duration-500 group-hover:scale-105">
                            </div>
                            
                            <div class="space-y-1.5">
                                <h3 class="text-base font-bold text-text-main group-hover:text-text-main transition-colors">
                                    Kamera & Lensa Mirrorless
                                </h3>
                                <p class="text-xs text-neutral-500 leading-relaxed">
                                    Kamera Sony Alpha full frame, lensa fix bokeh, flash godox, stabilizer gimbal untuk wisuda & konten.
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-1.5 pt-3">
                                <span class="px-2 py-0.5 rounded-md bg-surface-subtle text-[10px] font-medium text-neutral-600">Sensor Bersih</span>
                                <span class="px-2 py-0.5 rounded-md bg-surface-subtle text-[10px] font-medium text-neutral-600">Memory Pro</span>
                            </div>
                        </div>

                        <div class="mt-5 pt-3.5 border-t border-border-subtle/60 flex items-center justify-between">
                            <span class="text-[11px] font-medium text-text-main inline-flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Siap Booking
                            </span>
                            <a href="{{ url('/b/tokyo-cosplay/sony-a7-iii-kit') }}" class="text-xs font-semibold text-text-main group-hover:underline inline-flex items-center gap-1">
                                Pilih Jadwal &rarr;
                            </a>
                        </div>
                    </div>

                    <div class="kategori-card card-editorial rounded-2xl p-5 flex flex-col justify-between h-full group">
                        <div>
                            <div class="pedestal-stage h-48 w-full rounded-xl mb-4 flex items-center justify-center p-3 relative overflow-hidden">
                                <span class="absolute top-3 left-3 px-2.5 py-1 rounded-full text-[10px] font-bold bg-primary text-white shadow-xs z-10">
                                    Brokat Modern Halus
                                </span>
                                <span class="absolute top-3 right-3 text-[11px] font-bold text-text-secondary">
                                    Rp 120rb<span class="text-[9px] font-normal text-neutral-500">/hr</span>
                                </span>
                                <img src="{{ asset('kebaya.png') }}" 
                                     alt="Rental Kebaya Wisuda" 
                                     class="h-full w-auto object-contain transition-transform duration-500 group-hover:scale-105">
                            </div>
                            
                            <div class="space-y-1.5">
                                <h3 class="text-base font-bold text-text-main group-hover:text-text-main transition-colors">
                                    Kebaya Modern & Wisuda
                                </h3>
                                <p class="text-xs text-neutral-500 leading-relaxed">
                                    Kebaya kutubaru beludru, payet mewah, kain jarik wiru batik, kemben satin untuk wisuda & lamaran.
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-1.5 pt-3">
                                <span class="px-2 py-0.5 rounded-md bg-surface-subtle text-[10px] font-medium text-neutral-600">Fitting Rapih</span>
                                <span class="px-2 py-0.5 rounded-md bg-surface-subtle text-[10px] font-medium text-neutral-600">Bros Susun Emas</span>
                            </div>
                        </div>

                        <div class="mt-5 pt-3.5 border-t border-border-subtle/60 flex items-center justify-between">
                            <span class="text-[11px] font-medium text-text-main inline-flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Siap Booking
                            </span>
                            <a href="{{ url('/b/tokyo-cosplay/kebaya-kutubaru-maroon') }}" class="text-xs font-semibold text-text-main group-hover:underline inline-flex items-center gap-1">
                                Pilih Jadwal &rarr;
                            </a>
                        </div>
                    </div>

                    <div class="kategori-card card-editorial rounded-2xl p-5 flex flex-col justify-between h-full group">
                        <div>
                            <div class="pedestal-stage h-48 w-full rounded-xl mb-4 flex items-center justify-center p-3 relative overflow-hidden">
                                <span class="absolute top-3 left-3 px-2.5 py-1 rounded-full text-[10px] font-bold bg-primary text-white shadow-xs z-10">
                                    Warisan Nusantara
                                </span>
                                <span class="absolute top-3 right-3 text-[11px] font-bold text-text-secondary">
                                    Rp 180rb<span class="text-[9px] font-normal text-neutral-500">/hr</span>
                                </span>
                                <img src="{{ asset('adat.png') }}" 
                                     alt="Rental Busana Adat" 
                                     class="h-full w-auto object-contain transition-transform duration-500 group-hover:scale-90 js-zoom-out">
                            </div>
                            
                            <div class="space-y-1.5">
                                <h3 class="text-base font-bold text-text-main group-hover:text-text-main transition-colors">
                                    Busana Adat Tradisional
                                </h3>
                                <p class="text-xs text-neutral-500 leading-relaxed">
                                    Baju bodo, beskap jawa lengkap blangkon, songket minang, mahkota adat untuk tari & peringatan nasional.
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-1.5 pt-3">
                                <span class="px-2 py-0.5 rounded-md bg-surface-subtle text-[10px] font-medium text-neutral-600">Aksesori Komplit</span>
                                <span class="px-2 py-0.5 rounded-md bg-surface-subtle text-[10px] font-medium text-neutral-600">Kain Asli Tenun</span>
                            </div>
                        </div>

                        <div class="mt-5 pt-3.5 border-t border-border-subtle/60 flex items-center justify-between">
                            <span class="text-[11px] font-medium text-text-main inline-flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Siap Booking
                            </span>
                            <a href="{{ url('/b/tokyo-cosplay/baju-adat-jawa-pria') }}" class="text-xs font-semibold text-text-main group-hover:underline inline-flex items-center gap-1">
                                Pilih Jadwal &rarr;
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        
        <section id="reveal-showcase" class="relative overflow-hidden w-full min-h-screen flex items-center justify-center bg-primary">

            <!-- THE INNER STAGE: Teater Gelap Mewah Obsidian rgb(17, 17, 17) yang Terbuka -->
            <div id="curtain-stage" class="relative w-full min-h-screen py-20 sm:py-28 px-4 sm:px-6 lg:px-8 flex flex-col justify-center items-center text-center bg-primary text-white z-10 overflow-hidden">
                
                <div class="curtain-glow-1 absolute -top-32 -right-32 w-96 h-96 bg-primary/35 rounded-full blur-3xl pointer-events-none"></div>
                <div class="curtain-glow-2 absolute -bottom-32 -left-32 w-96 h-96 bg-[rgb(200, 195, 185)]/25 rounded-full blur-3xl pointer-events-none"></div>

                <div class="curtain-content relative z-20 max-w-4xl mx-auto space-y-7 sm:space-y-8 w-full">
                    
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-semibold bg-primary/25 border border-border-hover/40 text-surface-subtle tracking-wider uppercase">
                        <span class="w-1.5 h-1.5 rounded-full bg-[rgb(200, 195, 185)] animate-pulse"></span>
                        <span>Panggung Eksklusif Mitra Rental</span>
                    </div>

                    <h2 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-extrabold tracking-tight leading-[1.12]">
                        Waktunya Toko Rental Anda <br class="hidden sm:inline">
                        <span class="font-luxury italic font-normal text-text-main block mt-1">Bebas dari Drama Bentrok Jadwal</span>
                    </h2>

                    <p class="text-sm sm:text-base md:text-lg text-neutral-300 max-w-2xl mx-auto leading-relaxed font-normal">
                        Tinggalkan rekap spreadsheet manual dan perdebatan jadwal di DM. Kelola pesanan sewa, verifikasi KTP penyewa, dan terima pembayaran langsung di WhatsApp toko Anda.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-5 max-w-3xl mx-auto pt-2 text-left w-full">
                        <div class="curtain-metric-card bg-white/5 backdrop-blur-md border border-white/10 rounded-2xl p-5 space-y-1.5 hover:border-border-hover/60 transition">
                            <div class="text-2xl sm:text-3xl font-extrabold text-text-main">0%</div>
                            <div class="text-sm font-bold text-white">Double Booking</div>
                            <p class="text-xs text-neutral-400">Pessimistic locking database mengunci slot secara atomik.</p>
                        </div>

                        <div class="curtain-metric-card bg-white/5 backdrop-blur-md border border-white/10 rounded-2xl p-5 space-y-1.5 hover:border-border-hover/60 transition">
                            <div class="text-2xl sm:text-3xl font-extrabold text-text-main">100%</div>
                            <div class="text-sm font-bold text-white">Kepatuhan UU PDP</div>
                            <p class="text-xs text-neutral-400">Watermark dinamis otomatis & pemusnahan berkas 7 hari.</p>
                        </div>

                        <div class="curtain-metric-card bg-white/5 backdrop-blur-md border border-white/10 rounded-2xl p-5 space-y-1.5 hover:border-border-hover/60 transition">
                            <div class="text-2xl sm:text-3xl font-extrabold text-text-main">24 Jam</div>
                            <div class="text-sm font-bold text-white">Auto-Release Slot</div>
                            <p class="text-xs text-neutral-400">Booking tak berbayar otomatis dibatalkan, kalender bebas lagi.</p>
                        </div>
                    </div>

                    <div class="pt-4 sm:pt-6 flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-3.5 sm:gap-4 max-w-md sm:max-w-none mx-auto w-full">
                        <a href="{{ url('/vendor/register') }}" 
                           class="btn-fill-light btn-magnetic w-full sm:w-auto min-h-[52px] sm:min-h-[56px] px-8 sm:px-10 rounded-full text-sm sm:text-base font-bold inline-flex items-center justify-center shadow-2xl">
                            <span>Buka Toko Rental Sekarang &rarr;</span>
                        </a>

                        <a href="{{ url('/vendor/login') }}" 
                           class="btn-fill-outline-light btn-magnetic w-full sm:w-auto min-h-[52px] sm:min-h-[56px] px-8 sm:px-10 rounded-full text-sm sm:text-base font-semibold inline-flex items-center justify-center border-2 border-white/40 hover:border-white">
                            <span>Masuk Dashboard Vendor</span>
                        </a>
                    </div>

                </div>
            </div>

            <div id="curtain-left" class="absolute inset-y-0 left-0 w-1/2 bg-[rgb(245, 244, 240)] border-r-2 border-border-subtle/60 z-30 flex items-center justify-end pr-6 sm:pr-12 pointer-events-none shadow-2xl">
                <div class="text-right space-y-2 opacity-85">
                    <span class="text-xs uppercase tracking-widest text-text-secondary font-semibold block">Platform Terpadu</span>
                    <div class="h-0.5 w-16 bg-primary ml-auto"></div>
                </div>
            </div>

            <div id="curtain-right" class="absolute inset-y-0 right-0 w-1/2 bg-[rgb(245, 244, 240)] border-l-2 border-border-subtle/60 z-30 flex items-center justify-start pl-6 sm:pl-12 pointer-events-none shadow-2xl">
                <div class="text-left space-y-2 opacity-85">
                    <span class="text-xs uppercase tracking-widest text-text-secondary font-semibold block">Solusi Rental</span>
                    <div class="h-0.5 w-16 bg-primary"></div>
                </div>
            </div>

        </section>

        
        <section id="cara-kerja" class="py-16 sm:py-24 border-t border-border-subtle/70 relative overflow-hidden bg-transparent">
            
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
                
                <div class="cara-kerja-header max-w-2xl mx-auto space-y-2">
                    <span class="reveal-bounce-left inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-semibold bg-surface-subtle/70 text-text-secondary border border-border-subtle/40">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                        <span>Alur Singkat Tanpa Repot</span>
                    </span>
                    <h2 class="reveal-bounce-left text-2xl sm:text-3xl md:text-4xl font-extrabold text-text-main tracking-tight">
                        Cara Mudah Sewa Barang di JagaSewa
                    </h2>
                    <p class="reveal-bounce-left text-sm sm:text-base text-neutral-600 mt-2">
                        Langsung booking dari tautan bio media sosial toko tanpa registrasi atau membuat akun baru.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-12 sm:mt-16 text-left relative">
                    
                    <div class="step-card card-editorial p-7 rounded-2xl relative space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="w-12 h-12 rounded-2xl bg-primary text-white flex items-center justify-center font-bold text-base shadow-sm">
                                01
                            </div>
                            <span class="text-xs font-bold uppercase tracking-wider text-text-main">Langkah Pertama</span>
                        </div>
                        <h3 class="text-lg font-bold text-text-main">Pilih Barang & Cek Kalender</h3>
                        <p class="text-xs sm:text-sm text-neutral-600 leading-relaxed">
                            Pilih tanggal pada <strong>Smart Buffer Calendar</strong>. Sistem otomatis mengunci jeda pengiriman barang dan waktu laundry/cuci, memastikan tanggal sewa Anda tidak bentrok.
                        </p>
                        <div class="pt-2 text-[11px] font-semibold text-emerald-700 bg-emerald-50/70 border border-emerald-200/60 rounded-lg p-2.5 flex items-center gap-2">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Anti double-booking dengan row locking DB.</span>
                        </div>
                    </div>

                    <div class="step-card card-editorial p-7 rounded-2xl relative space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="w-12 h-12 rounded-2xl bg-primary text-white flex items-center justify-center font-bold text-base shadow-sm">
                                02
                            </div>
                            <span class="text-xs font-bold uppercase tracking-wider text-text-main">Langkah Kedua</span>
                        </div>
                        <h3 class="text-lg font-bold text-text-main">Isi Data & Upload KTP Aman</h3>
                        <p class="text-xs sm:text-sm text-neutral-600 leading-relaxed">
                            Cukup masukkan nama dan nomor WhatsApp. Unggah foto KTP yang langsung dilindungi dengan <strong>watermark otomatis</strong> dan disimpan di storage privat terenkripsi.
                        </p>
                        <div class="pt-2 text-[11px] font-semibold text-blue-700 bg-blue-50/70 border border-blue-200/60 rounded-lg p-2.5 flex items-center gap-2">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                            </svg>
                            <span>Sesuai UU Perlindungan Data Pribadi (UU PDP).</span>
                        </div>
                    </div>

                    <div class="step-card card-editorial p-7 rounded-2xl relative space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="w-12 h-12 rounded-2xl bg-primary text-white flex items-center justify-center font-bold text-base shadow-sm">
                                03
                            </div>
                            <span class="text-xs font-bold uppercase tracking-wider text-text-main">Langkah Terakhir</span>
                        </div>
                        <h3 class="text-lg font-bold text-text-main">Konfirmasi via WhatsApp</h3>
                        <p class="text-xs sm:text-sm text-neutral-600 leading-relaxed">
                            Satu klik membuka percakapan ke admin toko di WhatsApp dengan nomor referensi dan total sewa yang terisi otomatis. Konfirmasi DP/lunas langsung ke pemilik rental.
                        </p>
                        <div class="pt-2 text-[11px] font-semibold text-emerald-800 bg-emerald-100/60 border border-emerald-300/60 rounded-lg p-2.5 flex items-center gap-2">
                            <svg class="w-4 h-4 flex-shrink-0 text-emerald-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.592 2.654-.696c.969.586 1.838.894 2.806.894 3.181 0 5.767-2.587 5.768-5.766.001-3.18-2.585-5.777-5.768-5.777zm0-1.172c3.824 0 6.94 3.116 6.94 6.94 0 3.824-3.116 6.94-6.94 6.94-1.129 0-2.227-.275-3.21-.803l-3.821 1.002 1.023-3.731c-.604-1.042-.932-2.213-.932-3.408 0-3.824 3.116-6.94 6.94-6.94z" />
                            </svg>
                            <span>Tanpa biaya perantara (direct vendor transaction).</span>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        
        <section id="keunggulan" class="py-20 sm:py-28 border-t border-border-subtle/70 relative bg-transparent">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

                <div class="keunggulan-header text-center max-w-2xl mx-auto mb-14 sm:mb-20 space-y-3">
                    <span class="reveal-bounce-left inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-semibold bg-surface-subtle/70 text-text-secondary border border-border-subtle/40">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                        <span>Infrastruktur Terpercaya</span>
                    </span>
                    <h2 class="reveal-bounce-left text-3xl sm:text-4xl md:text-5xl font-extrabold text-text-main tracking-tight">
                        Dirancang Khusus untuk Bisnis Rental Modern
                    </h2>
                    <p class="reveal-bounce-left text-sm sm:text-base text-neutral-600 max-w-lg mx-auto">
                        Scroll ke bawah untuk melihat 4 pilar arsitektur proteksi dan automasi yang menjaga kelancaran usaha rental Anda.
                    </p>
                </div>

                <div class="sticky-stack-wrapper relative space-y-6 sm:space-y-8 pb-16">
                    
                    <div class="sticky-card sticky top-24 sm:top-28 z-10 bg-white/95 backdrop-blur-md rounded-3xl p-6 sm:p-10 border border-border-subtle/50 shadow-xl transition-all duration-300">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 sm:gap-8 items-center">
                            <div class="md:col-span-8 space-y-4">
                                <div class="flex items-center gap-3">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-surface-subtle text-text-secondary">Pilar 01</span>
                                    <span class="text-xs font-semibold uppercase tracking-wider text-neutral-400">Kalender Cerdas</span>
                                </div>
                                <h3 class="text-2xl sm:text-3xl font-extrabold text-text-main">
                                    Smart Buffer Calendar Otomatis
                                </h3>
                                <p class="text-sm sm:text-base text-neutral-600 leading-relaxed">
                                    Secara otomatis mengunci jeda pengiriman (<em>buffer before</em>) dan waktu cuci/laundry/inspeksi (<em>buffer after</em>) saat pesanan masuk. Tidak akan ada lagi kostum atau kamera yang belum dicuci atau belum kembali saat penyewa berikutnya datang.
                                </p>
                                <div class="flex flex-wrap gap-2 pt-2">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-bg-base border border-border-subtle/40 text-xs font-medium text-neutral-700">
                                        <svg class="w-3.5 h-3.5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        Buffer Before & After
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-bg-base border border-border-subtle/40 text-xs font-medium text-neutral-700">
                                        <svg class="w-3.5 h-3.5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        Sinkronisasi Real-Time
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-bg-base border border-border-subtle/40 text-xs font-medium text-neutral-700">
                                        <svg class="w-3.5 h-3.5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        Kalender Visual Interaktif
                                    </span>
                                </div>
                            </div>
                            <div class="md:col-span-4 flex items-center justify-center">
                                <div class="w-24 h-24 sm:w-32 sm:h-32 rounded-3xl bg-gradient-to-br from-surface-subtle to-bg-base border border-border-subtle/60 flex items-center justify-center text-text-secondary shadow-inner">
                                    <svg class="w-12 h-12 sm:w-16 sm:h-16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.253 3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sticky-card sticky top-28 sm:top-36 z-20 bg-surface-subtle backdrop-blur-md rounded-3xl p-6 sm:p-10 border border-border-subtle/60 shadow-xl transition-all duration-300">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 sm:gap-8 items-center">
                            <div class="md:col-span-8 space-y-4">
                                <div class="flex items-center gap-3">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-surface-subtle text-text-secondary">Pilar 02</span>
                                    <span class="text-xs font-semibold uppercase tracking-wider text-neutral-400">Keamanan Data Pribadi</span>
                                </div>
                                <h3 class="text-2xl sm:text-3xl font-extrabold text-text-main">
                                    Kepatuhan Penuh UU PDP No. 27/2022
                                </h3>
                                <p class="text-sm sm:text-base text-neutral-600 leading-relaxed">
                                    Foto KTP & identitas penyewa otomatis dibubuhi watermark dinamis ("VERIFIKASI JAGASEWA - [VENDOR] - [TANGGAL]"), metadata EXIF dihapus tuntas, file disimpan di storage privat S3 Backblaze B2, dan dimusnahkan secara otomatis setelah sewa selesai.
                                </p>
                                <div class="flex flex-wrap gap-2 pt-2">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-surface-subtle border border-border-subtle/40 text-xs font-medium text-neutral-700">
                                        <svg class="w-3.5 h-3.5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        Watermark Dinamis Otomatis
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-surface-subtle border border-border-subtle/40 text-xs font-medium text-neutral-700">
                                        <svg class="w-3.5 h-3.5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        Auto-Delete Berkas 7 Hari
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-surface-subtle border border-border-subtle/40 text-xs font-medium text-neutral-700">
                                        <svg class="w-3.5 h-3.5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        Private S3 Storage (Signed URL)
                                    </span>
                                </div>
                            </div>
                            <div class="md:col-span-4 flex items-center justify-center">
                                <div class="w-24 h-24 sm:w-32 sm:h-32 rounded-3xl bg-gradient-to-br from-surface-subtle to-bg-base border border-border-subtle/60 flex items-center justify-center text-text-secondary shadow-inner">
                                    <svg class="w-12 h-12 sm:w-16 sm:h-16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sticky-card sticky top-32 sm:top-44 z-30 bg-white/95 backdrop-blur-md rounded-3xl p-6 sm:p-10 border border-border-subtle/60 shadow-xl transition-all duration-300">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 sm:gap-8 items-center">
                            <div class="md:col-span-8 space-y-4">
                                <div class="flex items-center gap-3">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-surface-subtle text-text-secondary">Pilar 03</span>
                                    <span class="text-xs font-semibold uppercase tracking-wider text-neutral-400">Integritas Transaksi</span>
                                </div>
                                <h3 class="text-2xl sm:text-3xl font-extrabold text-text-main">
                                    Pessimistic Locking Database (Zero Double-Booking)
                                </h3>
                                <p class="text-sm sm:text-base text-neutral-600 leading-relaxed">
                                    Setiap reservasi dieksekusi dalam transaksi ACID database dengan perintah <code class="px-1.5 py-0.5 rounded bg-neutral-100 text-xs text-neutral-800 font-mono">lockForUpdate()</code>. Dua pelanggan yang mengklik tombol sewa di milidetik yang sama tidak akan pernah bisa mendapatkan slot yang sama.
                                </p>
                                <div class="flex flex-wrap gap-2 pt-2">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-bg-base border border-border-subtle/40 text-xs font-medium text-neutral-700">
                                        <svg class="w-3.5 h-3.5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        SELECT ... FOR UPDATE
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-bg-base border border-border-subtle/40 text-xs font-medium text-neutral-700">
                                        <svg class="w-3.5 h-3.5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        Database ACID Transaction
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-bg-base border border-border-subtle/40 text-xs font-medium text-neutral-700">
                                        <svg class="w-3.5 h-3.5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        Race-Condition Immunity
                                    </span>
                                </div>
                            </div>
                            <div class="md:col-span-4 flex items-center justify-center">
                                <div class="w-24 h-24 sm:w-32 sm:h-32 rounded-3xl bg-gradient-to-br from-surface-subtle to-bg-base border border-border-subtle/60 flex items-center justify-center text-text-secondary shadow-inner">
                                    <svg class="w-12 h-12 sm:w-16 sm:h-16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sticky-card sticky top-36 sm:top-52 z-40 bg-primary text-white rounded-3xl p-6 sm:p-10 border border-[rgb(200, 195, 185)]/40 shadow-2xl transition-all duration-300">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 sm:gap-8 items-center">
                            <div class="md:col-span-8 space-y-4">
                                <div class="flex items-center gap-3">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-primary text-white">Pilar 04</span>
                                    <span class="text-xs font-semibold uppercase tracking-wider text-neutral-400">Proteksi Finansial</span>
                                </div>
                                <h3 class="text-2xl sm:text-3xl font-extrabold text-text-main">
                                    Kalkulator Denda Pasti & Auto-Release 24 Jam
                                </h3>
                                <p class="text-sm sm:text-base text-neutral-300 leading-relaxed">
                                    Aritmatika bilangan bulat murni (integer rupiah tanpa floating point) menghitung denda keterlambatan dan deposit secara transparan. Booking tanpa konfirmasi pembayaran otomatis dibatalkan dalam 24 jam sehingga kalender barang kembali terbuka.
                                </p>
                                <div class="flex flex-wrap gap-2 pt-2">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-white/10 border border-white/10 text-xs font-medium text-neutral-200">
                                        <svg class="w-3.5 h-3.5 text-accent-lime" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        Zero Float Precision Error
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-white/10 border border-white/10 text-xs font-medium text-neutral-200">
                                        <svg class="w-3.5 h-3.5 text-accent-lime" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        Auto-Cancel Pending Unpaid
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-white/10 border border-white/10 text-xs font-medium text-neutral-200">
                                        <svg class="w-3.5 h-3.5 text-accent-lime" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        WhatsApp Format Otomatis
                                    </span>
                                </div>
                            </div>
                            <div class="md:col-span-4 flex items-center justify-center">
                                <div class="w-24 h-24 sm:w-32 sm:h-32 rounded-3xl bg-white/5 border border-[rgb(200, 195, 185)]/50 flex items-center justify-center text-text-main shadow-inner">
                                    <svg class="w-12 h-12 sm:w-16 sm:h-16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6H2.25m0 0H3m-1.5 0h1.5m0 0a60.07 60.07 0 0115.797-2.101c.727-.198 1.453.342 1.453 1.096V6m-1.5 0H18m1.5 0H21m-1.5 0v.75" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </section>

    </main>

    
    <footer id="site-footer" class="border-t border-border-subtle/70 py-10 transition-colors duration-500 bg-primary text-neutral-400">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-6 text-xs">
            <div class="flex items-center gap-3">
                <img src="{{ asset('jagasewa_logo1.png') }}" alt="JagaSewa" class="h-8 w-auto object-contain brightness-110">
                <span class="border-l border-neutral-700 pl-3 font-normal text-neutral-400">Platform booking rental mobile-first</span>
            </div>
            <div class="flex items-center gap-6 text-neutral-300">
                <a href="#jelajahi-rental" class="hover:text-text-main transition">Katalog</a>
                <a href="#reveal-showcase" class="hover:text-text-main transition">Panggung Mitra</a>
                <a href="#cara-kerja" class="hover:text-text-main transition">Cara Kerja</a>
                <a href="#keunggulan" class="hover:text-text-main transition">Keunggulan</a>
                <a href="{{ url('/vendor/login') }}" class="hover:text-text-main transition">Portal Vendor</a>
            </div>
            <div>
                &copy; {{ date('Y') }} JagaSewa. Hak cipta dilindungi undang-undang.
            </div>
        </div>
    </footer>

    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof gsap === 'undefined') {
                console.warn('GSAP tidak tersedia atau diblokir; mode tampilan statis aktif.');
                return;
            }

            if (typeof ScrollTrigger !== 'undefined') {
                gsap.registerPlugin(ScrollTrigger);
            }

            /* ====================================================
               1. PERPINDAHAN BACKGROUND SECARA HALUS (CONTINUOUS SCRUB)
               (Hero -> Katalog -> Curtain Showcase -> Cara Kerja -> Keunggulan)
               ==================================================== */
            // Hero -> Katalog (rgb(250, 249, 246) -> rgb(245, 244, 240))
            gsap.to('body', {
                backgroundColor: 'rgb(245, 244, 240)',
                ease: 'none',
                scrollTrigger: {
                    trigger: '#jelajahi-rental',
                    start: 'top 85%',
                    end: 'top 20%',
                    scrub: 0.6
                }
            });

            // Katalog -> Curtain Showcase (rgb(245, 244, 240) -> rgb(17, 17, 17))
            gsap.to('body', {
                backgroundColor: 'rgb(17, 17, 17)',
                ease: 'none',
                scrollTrigger: {
                    trigger: '#reveal-showcase',
                    start: 'top 80%',
                    end: 'top 15%',
                    scrub: 0.6
                }
            });

            // Curtain Showcase -> Cara Kerja (rgb(17, 17, 17) -> rgb(245, 244, 240))
            gsap.to('body', {
                backgroundColor: 'rgb(245, 244, 240)',
                ease: 'none',
                scrollTrigger: {
                    trigger: '#cara-kerja',
                    start: 'top 85%',
                    end: 'top 20%',
                    scrub: 0.6
                }
            });

            // Cara Kerja -> Keunggulan (rgb(245, 244, 240) -> rgb(250, 249, 246))
            gsap.to('body', {
                backgroundColor: 'rgb(250, 249, 246)',
                ease: 'none',
                scrollTrigger: {
                    trigger: '#keunggulan',
                    start: 'top 85%',
                    end: 'top 20%',
                    scrub: 0.6
                }
            });

            /* ====================================================
               2. SCROLLSPY NAVBAR AKTIF SESUAI URUTAN SECTION
               ==================================================== */
            const navItems = [
                { id: '#jelajahi-rental', nav: 'nav-katalog' },
                { id: '#reveal-showcase', nav: 'nav-mitra' },
                { id: '#cara-kerja', nav: 'nav-cara-kerja' },
                { id: '#keunggulan', nav: 'nav-keunggulan' }
            ];

            navItems.forEach(function(item) {
                ScrollTrigger.create({
                    trigger: item.id,
                    start: 'top 50%',
                    end: 'bottom 50%',
                    onEnter: function() {
                        setNavbarActive(item.nav);
                    },
                    onEnterBack: function() {
                        setNavbarActive(item.nav);
                    }
                });
            });

            function setNavbarActive(navId) {
                document.querySelectorAll('.nav-link').forEach(function(link) {
                    link.classList.remove('active-nav');
                });
                const current = document.getElementById(navId);
                if (current) {
                    current.classList.add('active-nav');
                }
            }

            /* ====================================================
               3. CURTAIN REVEAL SHOWCASE (Tirai Teater Membuka)
               (Posisi: Tepat setelah Kategori Rental)
               ==================================================== */
            const curtainTl = gsap.timeline({
                scrollTrigger: {
                    trigger: '#reveal-showcase',
                    start: 'top top',
                    end: '+=100%',
                    pin: true,
                    scrub: 1.2,
                    anticipatePin: 1
                }
            });

            curtainTl
                .to('#curtain-left', {
                    xPercent: -105,
                    ease: 'power2.inOut'
                }, 0)
                .to('#curtain-right', {
                    xPercent: 105,
                    ease: 'power2.inOut'
                }, 0)
                .from('.curtain-content', {
                    scale: 0.88,
                    opacity: 0.15,
                    y: 45,
                    ease: 'power2.out'
                }, 0.1)
                .from('.curtain-metric-card', {
                    y: 35,
                    opacity: 0,
                    stagger: 0.1,
                    ease: 'power2.out'
                }, 0.25);

            /* ====================================================
               4. ANIMASI ENTRANCE SAAT WEBSITE PERTAMA KALI DIBUKA
               (Text Bounce dari Kiri + Zoom Out Cosplay & Adat)
               ==================================================== */
            const masterTl = gsap.timeline({
                defaults: {
                    ease: 'power3.out'
                }
            });

            masterTl
                .from('#site-header', {
                    y: -30,
                    opacity: 0,
                    duration: 0.8
                })
                
                // TEXT MUNCUL DARI KIRI DENGAN BOUNCE
                .from('.hero-bounce-line-1', {
                    x: -80,
                    opacity: 0,
                    duration: 1.0,
                    ease: 'back.out(1.7)'
                }, 0.2)
                .from('.hero-bounce-line-2', {
                    x: -80,
                    opacity: 0,
                    duration: 1.0,
                    ease: 'back.out(1.7)'
                }, 0.35)
                .from('.hero-bounce-sub', {
                    x: -80,
                    opacity: 0,
                    duration: 0.9,
                    ease: 'back.out(1.5)'
                }, 0.5)
                .from('.hero-bounce-cta', {
                    x: -80,
                    opacity: 0,
                    duration: 0.9,
                    ease: 'back.out(1.5)'
                }, 0.65)
                
                .from('#hero-ribbon-svg', {
                    scale: 0.85,
                    opacity: 0,
                    duration: 1.2
                }, 0.2)

                // ANIMASI ZOOM OUT KHUSUS COSPLAY.PNG (Dari scale 1.28 mengecil ke 1.0)
                .fromTo('.hero-cosplay', {
                    scale: 1.28,
                    y: 55,
                    opacity: 0
                }, {
                    scale: 1,
                    y: 0,
                    opacity: 1,
                    duration: 1.3,
                    ease: 'power3.out'
                }, 0.4)

                // ANIMASI ZOOM OUT KHUSUS ADAT.PNG (Dari scale 1.28 mengecil ke 1.0)
                .fromTo('.hero-adat', {
                    scale: 1.28,
                    y: 55,
                    opacity: 0
                }, {
                    scale: 1,
                    y: 0,
                    opacity: 1,
                    duration: 1.3,
                    ease: 'power3.out'
                }, 0.5)

                .from('.hero-kebaya', {
                    y: 60,
                    opacity: 0,
                    duration: 1.2
                }, 0.45)
                .from('.hero-camera', {
                    scale: 0.75,
                    y: 50,
                    opacity: 0,
                    duration: 1.2,
                    ease: 'back.out(1.5)'
                }, 0.6);

            /* ====================================================
               5. INTERACTIVE 3D MOUSE PARALLAX PADA HERO SECTION
               ==================================================== */
            const heroSection = document.getElementById('hero-section');
            if (heroSection && !window.matchMedia('(pointer: coarse)').matches) {
                const camX = gsap.quickTo('.hero-camera', 'x', { duration: 0.5, ease: 'power2.out' });
                const camY = gsap.quickTo('.hero-camera', 'y', { duration: 0.5, ease: 'power2.out' });
                const camRot = gsap.quickTo('.hero-camera', 'rotation', { duration: 0.5, ease: 'power2.out' });

                const cosplayX = gsap.quickTo('.hero-cosplay', 'x', { duration: 0.8, ease: 'power2.out' });
                const cosplayY = gsap.quickTo('.hero-cosplay', 'y', { duration: 0.8, ease: 'power2.out' });

                const kebayaX = gsap.quickTo('.hero-kebaya', 'x', { duration: 0.9, ease: 'power2.out' });
                const kebayaY = gsap.quickTo('.hero-kebaya', 'y', { duration: 0.9, ease: 'power2.out' });

                const adatX = gsap.quickTo('.hero-adat', 'x', { duration: 0.85, ease: 'power2.out' });
                const adatY = gsap.quickTo('.hero-adat', 'y', { duration: 0.85, ease: 'power2.out' });

                const ribbonRot = gsap.quickTo('#hero-ribbon-svg', 'rotation', { duration: 1.2, ease: 'power2.out' });
                const ribbonX = gsap.quickTo('#hero-ribbon-svg', 'x', { duration: 1.2, ease: 'power2.out' });

                heroSection.addEventListener('mousemove', function(e) {
                    const rect = heroSection.getBoundingClientRect();
                    const nx = (e.clientX - rect.left) / rect.width - 0.5;
                    const ny = (e.clientY - rect.top) / rect.height - 0.5;

                    camX(nx * 42);
                    camY(ny * 32);
                    camRot(nx * 4);

                    cosplayX(nx * -28);
                    cosplayY(ny * -18);

                    kebayaX(nx * -16);
                    kebayaY(ny * -12);

                    adatX(nx * -32);
                    adatY(ny * -24);

                    ribbonRot(nx * 6);
                    ribbonX(nx * 25);
                });

                heroSection.addEventListener('mouseleave', function() {
                    camX(0);
                    camY(0);
                    camRot(0);
                    cosplayX(0);
                    cosplayY(0);
                    kebayaX(0);
                    kebayaY(0);
                    adatX(0);
                    adatY(0);
                    ribbonRot(0);
                    ribbonX(0);
                });
            }

            /* ====================================================
               6. HERO SCROLL PARALLAX SCRUBBING
               ==================================================== */
            gsap.to('.hero-camera', {
                y: -140,
                x: -30,
                rotation: -4,
                scrollTrigger: {
                    trigger: '#hero-section',
                    start: 'top top',
                    end: 'bottom top',
                    scrub: 1.2
                }
            });

            gsap.to('.hero-cosplay', {
                y: -75,
                scale: 0.90,
                scrollTrigger: {
                    trigger: '#hero-section',
                    start: 'top top',
                    end: 'bottom top',
                    scrub: 1
                }
            });

            gsap.to('.hero-adat', {
                y: -90,
                scale: 0.88,
                scrollTrigger: {
                    trigger: '#hero-section',
                    start: 'top top',
                    end: 'bottom top',
                    scrub: 1.1
                }
            });

            gsap.to('.hero-kebaya', {
                y: -50,
                scale: 0.95,
                scrollTrigger: {
                    trigger: '#hero-section',
                    start: 'top top',
                    end: 'bottom top',
                    scrub: 0.9
                }
            });

            gsap.to('#hero-ribbon-svg', {
                y: 110,
                rotation: 12,
                scale: 1.12,
                scrollTrigger: {
                    trigger: '#hero-section',
                    start: 'top top',
                    end: 'bottom top',
                    scrub: 1.4
                }
            });

            gsap.to('.hero-text-block', {
                y: -50,
                opacity: 0.85,
                scrollTrigger: {
                    trigger: '#hero-section',
                    start: 'top top',
                    end: 'bottom top',
                    scrub: 0.8
                }
            });

            /* ====================================================
               7. SCROLL REVEAL BOUNCE ANIMATION (TEXT DARI KIRI)
               ==================================================== */
            gsap.utils.toArray('.reveal-bounce-left').forEach(function(el) {
                gsap.from(el, {
                    scrollTrigger: {
                        trigger: el,
                        start: 'top 88%',
                        toggleActions: 'play none none none'
                    },
                    x: -70,
                    opacity: 0,
                    duration: 0.95,
                    ease: 'back.out(1.7)'
                });
            });

            // Kategori Cards Stagger
            gsap.from('.kategori-card', {
                scrollTrigger: {
                    trigger: '#jelajahi-rental',
                    start: 'top 75%'
                },
                y: 65,
                opacity: 0,
                stagger: 0.14,
                duration: 1,
                ease: 'power3.out'
            });

            // Cara Kerja Step Cards Stagger
            gsap.from('.step-card', {
                scrollTrigger: {
                    trigger: '#cara-kerja',
                    start: 'top 75%'
                },
                y: 75,
                opacity: 0,
                scale: 0.94,
                stagger: 0.16,
                duration: 1,
                ease: 'power3.out'
            });

            /* ====================================================
               8. STICKY STACKING CARDS DI SECTION KEUNGGULAN
               (Kartu berikutnya menumpuk, kartu sebelumnya scale down & dim)
               ==================================================== */
            const stickyCards = gsap.utils.toArray('.sticky-card');
            stickyCards.forEach(function(card, index) {
                if (index < stickyCards.length - 1) {
                    gsap.to(card, {
                        scale: 0.94 - index * 0.02,
                        opacity: 0.72,
                        filter: 'brightness(0.88)',
                        ease: 'none',
                        scrollTrigger: {
                            trigger: stickyCards[index + 1],
                            start: 'top 45%',
                            end: 'top 20%',
                            scrub: 0.8
                        }
                    });
                }
            });

            /* ====================================================
               9. INTERAKTIF HOVER ZOOM-OUT KHUSUS COSPLAY & ADAT
               ==================================================== */
            document.querySelectorAll('.js-zoom-out').forEach(function(el) {
                el.addEventListener('mouseenter', function() {
                    gsap.to(el, {
                        scale: 0.88,
                        y: 8,
                        duration: 0.45,
                        ease: 'power2.out',
                        overwrite: 'auto'
                    });
                });

                el.addEventListener('mouseleave', function() {
                    gsap.to(el, {
                        scale: 1,
                        y: 0,
                        duration: 0.65,
                        ease: 'elastic.out(1, 0.6)',
                        overwrite: 'auto'
                    });
                });
            });

            /* ====================================================
               10. MAGNETIC BUTTONS (Desktop Pointer)
               ==================================================== */
            if (!window.matchMedia('(pointer: coarse)').matches) {
                document.querySelectorAll('.btn-magnetic').forEach(function(btn) {
                    const bx = gsap.quickTo(btn, 'x', { duration: 0.35, ease: 'power2.out' });
                    const by = gsap.quickTo(btn, 'y', { duration: 0.35, ease: 'power2.out' });

                    btn.addEventListener('mousemove', function(e) {
                        const rect = btn.getBoundingClientRect();
                        const x = e.clientX - (rect.left + rect.width / 2);
                        const y = e.clientY - (rect.top + rect.height / 2);
                        bx(x * 0.28);
                        by(y * 0.28);
                    });

                    btn.addEventListener('mouseleave', function() {
                        bx(0);
                        by(0);
                    });
                });
            }
        });
    </script>

</body>
</html>
