<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Market Eye — Know the price before you go</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Work+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --indigo-950: #131f38;
            --indigo-800: #1e3159;
            --indigo-600: #33507f;
            --chalk-50: #fbf8f1;
            --chalk-100: #f4efe2;
            --ochre-500: #c99a2e;
            --ochre-600: #a87c1f;
            --rust-500: #b0472c;
            --rust-600: #8f3720;
            --green-600: #2f7a4f;
            --ink: #17223b;
            --muted: #5c6b84;
            --line: rgba(23,34,59,.12);
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            font-family: 'Work Sans', system-ui, sans-serif;
            color: var(--ink);
            background: var(--chalk-50);
        }
        a { color: inherit; }
        img { max-width: 100%; display: block; }
        .wrap { width: min(1160px, calc(100% - 32px)); margin: 0 auto; }
        .mono { font-family: 'IBM Plex Mono', ui-monospace, monospace; }
        .display { font-family: 'Space Grotesk', system-ui, sans-serif; }

        /* ---------- Ticker ---------- */
        .ticker-band {
            background: var(--indigo-950);
            color: var(--chalk-100);
            overflow: hidden;
            white-space: nowrap;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .ticker-track {
            display: inline-flex;
            align-items: center;
            padding: 9px 0;
            animation: ticker 38s linear infinite;
            will-change: transform;
        }
        .ticker-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: .8rem;
            padding: 0 20px;
            border-right: 1px solid rgba(255,255,255,.14);
        }
        .ticker-item .dot { width: 6px; height: 6px; border-radius: 50%; background: var(--ochre-500); flex: none; }
        .ticker-item .mkt { opacity: .62; }
        .ticker-item .amt { color: #fff; font-weight: 600; }
        @keyframes ticker { from { transform: translateX(0); } to { transform: translateX(-50%); } }
        @media (prefers-reduced-motion: reduce) { .ticker-track { animation: none; } }

        /* ---------- Nav ---------- */
        .nav {
            display: flex; align-items: center; justify-content: space-between;
            padding: 16px 0; gap: 16px; position: relative; z-index: 40;
        }
        .brand {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            font-size: 1.4rem; font-weight: 700; letter-spacing: -0.02em;
            color: var(--indigo-950); text-decoration: none;
        }
        .nav-toggle {
            display: none;
            width: 44px; height: 44px; border-radius: 10px;
            border: 1.5px solid var(--line); background: #fff;
            align-items: center; justify-content: center; cursor: pointer; padding: 0;
        }
        .nav-toggle span {
            display: block; width: 18px; height: 2px; background: var(--indigo-950);
            position: relative;
        }
        .nav-toggle span::before,
        .nav-toggle span::after {
            content: ''; position: absolute; left: 0; width: 18px; height: 2px;
            background: var(--indigo-950);
        }
        .nav-toggle span::before { top: -6px; }
        .nav-toggle span::after { top: 6px; }
        .nav-toggle[aria-expanded="true"] span { background: transparent; }
        .nav-toggle[aria-expanded="true"] span::before { top: 0; transform: rotate(45deg); }
        .nav-toggle[aria-expanded="true"] span::after { top: 0; transform: rotate(-45deg); }
        .nav-links { display: flex; gap: 22px; align-items: center; flex-wrap: wrap; }
        .nav-links a { text-decoration: none; font-weight: 500; font-size: .92rem; color: var(--muted); }
        .nav-links a:hover { color: var(--indigo-950); }
        .nav-backdrop {
            display: none; position: fixed; inset: 0; background: rgba(19,31,56,.35); z-index: 30;
        }
        .nav-backdrop.is-open { display: block; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            text-decoration: none; border: none; cursor: pointer;
            font-weight: 600; border-radius: 8px; padding: 12px 20px; font-size: .92rem;
        }
        .btn-primary { background: var(--indigo-950); color: #fff; }
        .btn-primary:hover { background: var(--indigo-800); }
        .btn-ochre { background: var(--ochre-500); color: #241a03; }
        .btn-ochre:hover { background: var(--ochre-600); }
        .btn-ghost { background: transparent; border: 1.5px solid var(--line); color: var(--indigo-950); }

        section { padding: 72px 0; }
        .section-eyebrow {
            font-family: 'IBM Plex Mono', monospace;
            font-size: .74rem; letter-spacing: .12em; text-transform: uppercase;
            color: var(--rust-500); font-weight: 600; margin-bottom: 10px;
        }
        .section-title {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            font-size: clamp(1.7rem, 3vw, 2.3rem);
            letter-spacing: -0.02em; margin: 0 0 12px; color: var(--indigo-950); font-weight: 700;
        }
        .section-sub { margin: 0 0 32px; color: var(--muted); max-width: 42rem; line-height: 1.6; font-size: 1.02rem; }

        /* ---------- Hero ---------- */
        .hero {
            padding: 44px 0 30px;
            display: grid; grid-template-columns: 1.05fr .95fr; gap: 40px; align-items: center;
        }
        .hero h1 {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            font-size: clamp(2.4rem, 5.4vw, 3.9rem);
            line-height: 1.04; letter-spacing: -0.03em; margin: 0 0 18px; color: var(--indigo-950); font-weight: 700;
        }
        .hero h1 .accent { color: var(--rust-500); }
        .hero p.lede {
            margin: 0 0 28px; font-size: 1.12rem; line-height: 1.6; color: var(--muted); max-width: 34rem;
        }
        .hero-actions { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 26px; }
        .hero-note { font-size: .85rem; color: var(--muted); display: flex; align-items: center; gap: 8px; }
        .hero-note .dot { width: 7px; height: 7px; border-radius: 50%; background: var(--green-600); }

        /* ---------- Ticket / tag motif (signature element) ---------- */
        .tag-stack { position: relative; min-height: 380px; }
        .price-tag {
            position: relative;
            background: var(--chalk-100);
            border: 1px solid var(--line);
            border-radius: 3px 3px 14px 14px;
            padding: 22px 20px 18px;
            box-shadow: 0 14px 30px rgba(19,31,56,.10);
        }
        .price-tag::before {
            content: '';
            position: absolute; top: -7px; left: 28px;
            width: 13px; height: 13px; border-radius: 50%;
            background: var(--chalk-50); border: 2px solid var(--indigo-950);
        }
        .price-tag::after {
            content: '';
            position: absolute; top: -22px; left: 34px;
            width: 1.5px; height: 18px; background: var(--indigo-950); opacity: .35;
            transform: rotate(8deg);
        }
        .price-tag .tag-label { font-size: .78rem; color: var(--muted); margin-bottom: 6px; }
        .price-tag .tag-value {
            font-family: 'IBM Plex Mono', monospace; font-weight: 600;
            font-size: 1.5rem; color: var(--indigo-950);
        }
        .tag-stack .price-tag:nth-child(1) { position: absolute; top: 0; left: 10%; width: 62%; transform: rotate(-3deg); z-index: 3; }
        .tag-stack .price-tag:nth-child(2) { position: absolute; top: 150px; left: 0; width: 56%; transform: rotate(2.5deg); z-index: 2; background: var(--indigo-950); border-color: var(--indigo-950); }
        .tag-stack .price-tag:nth-child(2) .tag-label { color: rgba(255,255,255,.6); }
        .tag-stack .price-tag:nth-child(2) .tag-value { color: #fff; }
        .tag-stack .price-tag:nth-child(2)::before { background: var(--indigo-950); }
        .tag-stack .price-tag:nth-child(3) { position: absolute; top: 210px; right: 4%; width: 54%; transform: rotate(-1.5deg); z-index: 1; background: var(--ochre-500); border-color: var(--ochre-600); }
        .tag-stack .price-tag:nth-child(3) .tag-label { color: rgba(36,26,3,.65); }
        .tag-stack .price-tag:nth-child(3) .tag-value { color: #241a03; }
        .tag-stack .price-tag:nth-child(3)::before { background: var(--ochre-500); border-color: #241a03; }

        /* ---------- Trust strip ---------- */
        .trust-strip {
            padding: 34px 0;
            border-top: 1px solid var(--line); border-bottom: 1px solid var(--line);
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;
        }
        .trust-item { display: flex; gap: 14px; align-items: flex-start; }
        .trust-item .mark {
            font-family: 'Space Grotesk', system-ui, sans-serif; font-weight: 700; font-size: 1rem;
            width: 34px; height: 34px; border-radius: 50%; flex: none;
            display: flex; align-items: center; justify-content: center;
            background: var(--indigo-950); color: #fff;
        }
        .trust-item h4 { margin: 0 0 4px; font-size: .98rem; color: var(--indigo-950); }
        .trust-item p { margin: 0; font-size: .88rem; color: var(--muted); line-height: 1.5; }

        /* ---------- How it works ---------- */
        .steps-line { position: relative; }
        .steps { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; position: relative; }
        .steps::before {
            content: ''; position: absolute; top: 17px; left: 6%; right: 6%; height: 1px;
            background: repeating-linear-gradient(90deg, var(--line) 0 8px, transparent 8px 14px);
            z-index: 0;
        }
        .step { position: relative; z-index: 1; }
        .step .num {
            display: inline-flex; align-items: center; justify-content: center;
            width: 34px; height: 34px; border-radius: 50%; background: var(--chalk-50);
            border: 1.5px solid var(--indigo-950); color: var(--indigo-950);
            font-family: 'IBM Plex Mono', monospace; font-weight: 600; font-size: .85rem;
            margin-bottom: 14px;
        }
        .step h3 { margin: 0 0 8px; font-size: 1.05rem; color: var(--indigo-950); font-family: 'Space Grotesk', system-ui, sans-serif; }
        .step p { margin: 0; color: var(--muted); font-size: .92rem; line-height: 1.5; }

        /* ---------- Categories ---------- */
        .category-row { display: flex; flex-wrap: wrap; gap: 12px; }
        .category-chip {
            display: flex; align-items: center; gap: 10px;
            background: #fff; border: 1px solid var(--line); border-radius: 999px;
            padding: 10px 18px 10px 14px; text-decoration: none;
        }
        .category-chip .swatch { width: 10px; height: 10px; border-radius: 50%; flex: none; }
        .category-chip span { font-size: .92rem; font-weight: 500; color: var(--ink); }
        .category-chip small { color: var(--muted); font-size: .8rem; }

        /* ---------- Live prices ---------- */
        .market-block { margin-bottom: 40px; }
        .market-head { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; margin-bottom: 18px; }
        .market-head h3 { margin: 0; font-size: 1.15rem; color: var(--indigo-950); font-family: 'Space Grotesk', system-ui, sans-serif; }
        .market-head span { color: var(--muted); font-size: .88rem; }
        .carousel {
            display: flex; gap: 22px; overflow-x: auto; scroll-snap-type: x mandatory;
            padding: 18px 6px 18px; -webkit-overflow-scrolling: touch;
        }
        .carousel::-webkit-scrollbar { height: 8px; }
        .carousel::-webkit-scrollbar-thumb { background: var(--line); border-radius: 999px; }
        .price-tile {
            flex: 0 0 196px; scroll-snap-align: start;
            background: var(--chalk-100);
            border: 1px solid var(--line);
            border-radius: 3px 3px 14px 14px;
            padding: 20px 16px 16px;
            position: relative;
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .price-tile::before {
            content: ''; position: absolute; top: -7px; left: 22px;
            width: 12px; height: 12px; border-radius: 50%;
            background: var(--chalk-50); border: 2px solid var(--indigo-950);
        }
        .carousel .price-tile:nth-child(3n+1) { transform: rotate(-1.6deg); }
        .carousel .price-tile:nth-child(3n+2) { transform: rotate(0.8deg); }
        .carousel .price-tile:nth-child(3n) { transform: rotate(-0.4deg); }
        .price-tile:hover { transform: translateY(-4px) rotate(0deg); box-shadow: 0 16px 32px rgba(19,31,56,.12); }
        .price-tile .name { font-weight: 600; color: var(--indigo-950); margin-bottom: 4px; font-size: .98rem; }
        .price-tile .unit { color: var(--muted); font-size: .8rem; margin-bottom: 14px; }
        .price-tile .amount { font-family: 'IBM Plex Mono', monospace; font-weight: 600; font-size: 1.4rem; color: var(--ink); }
        .stamp {
            display: inline-block; margin-top: 12px; font-size: .68rem; font-weight: 600;
            padding: 3px 9px; border-radius: 999px; text-transform: uppercase; letter-spacing: .05em;
            font-family: 'IBM Plex Mono', monospace;
        }
        .stamp.good { background: rgba(47,122,79,.13); color: var(--green-600); }
        .stamp.low { background: rgba(176,71,44,.13); color: var(--rust-600); }
        .empty-note {
            border: 1px dashed var(--line); border-radius: 14px; padding: 28px; color: var(--muted); font-size: .95rem;
        }

        /* ---------- Verification section ---------- */
        .verify-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 48px; align-items: center; }
        .verify-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 18px; }
        .verify-list li { display: flex; gap: 14px; }
        .verify-list .glyph {
            font-family: 'IBM Plex Mono', monospace; font-weight: 600; color: var(--rust-500);
            font-size: .85rem; flex: none; width: 22px; padding-top: 2px;
        }
        .verify-list h4 { margin: 0 0 4px; font-size: .98rem; color: var(--indigo-950); }
        .verify-list p { margin: 0; color: var(--muted); font-size: .9rem; line-height: 1.55; }
        .verify-visual {
            background: var(--indigo-950); border-radius: 20px; padding: 30px;
            display: flex; flex-direction: column; gap: 14px;
        }
        .verify-visual .price-tile { background: var(--chalk-100); }

        /* ---------- Voices ---------- */
        .voices { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .voice-card {
            background: var(--chalk-100); border: 1px solid var(--line); border-radius: 16px;
            padding: 24px; position: relative;
        }
        .voice-card p.quote { margin: 0 0 16px; font-size: .96rem; line-height: 1.6; color: var(--ink); }
        .voice-card .who { display: flex; align-items: center; gap: 10px; }
        .voice-card .avatar {
            width: 34px; height: 34px; border-radius: 50%; background: var(--rust-500); color: #fff;
            display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: .85rem;
            font-family: 'Space Grotesk', system-ui, sans-serif;
        }
        .voice-card .who strong { display: block; font-size: .88rem; color: var(--indigo-950); }
        .voice-card .who span { font-size: .78rem; color: var(--muted); }

        /* ---------- CTA band ---------- */
        .cta-band {
            background: var(--indigo-950); color: #fff; border-radius: 24px; padding: 46px 40px;
            display: flex; justify-content: space-between; gap: 28px; align-items: center; flex-wrap: wrap;
        }
        .cta-band h2 { font-family: 'Space Grotesk', system-ui, sans-serif; margin: 0 0 10px; font-size: 1.9rem; letter-spacing: -0.02em; }
        .cta-band p { margin: 0; opacity: .78; max-width: 32rem; line-height: 1.55; }

        /* ---------- Footer ---------- */
        footer { padding: 56px 0 40px; border-top: 1px solid var(--line); }
        .footer-grid { display: grid; grid-template-columns: 1.4fr repeat(3, 1fr); gap: 28px; margin-bottom: 32px; }
        .footer-brand p { color: var(--muted); font-size: .9rem; line-height: 1.55; max-width: 24rem; margin: 10px 0 0; }
        .footer-col h5 {
            font-family: 'IBM Plex Mono', monospace; font-size: .74rem; letter-spacing: .1em; text-transform: uppercase;
            color: var(--muted); margin: 0 0 14px;
        }
        .footer-col ul { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 10px; }
        .footer-col a { text-decoration: none; font-size: .9rem; color: var(--ink); }
        .footer-col a:hover { color: var(--rust-500); }
        .footer-bottom {
            display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap;
            font-size: .82rem; color: var(--muted); padding-top: 24px; border-top: 1px solid var(--line);
        }

        @media (max-width: 900px) {
            .hero { grid-template-columns: 1fr; }
            .tag-stack { min-height: 320px; }
            .trust-strip, .steps, .verify-grid, .voices, .footer-grid { grid-template-columns: 1fr 1fr; }
            .verify-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .nav-toggle { display: inline-flex; }
            .nav-links {
                display: none;
                position: absolute; top: calc(100% + 8px); left: 0; right: 0;
                flex-direction: column; align-items: stretch; gap: 4px;
                background: #fff; border: 1px solid var(--line); border-radius: 14px;
                padding: 10px; box-shadow: 0 18px 40px rgba(19,31,56,.14); z-index: 40;
            }
            .nav-links.is-open { display: flex; }
            .nav-links a {
                padding: 12px 14px; border-radius: 10px; min-height: 44px;
                display: flex; align-items: center;
            }
            .nav-links a:hover { background: var(--chalk-100); }
            .nav-links .btn { width: 100%; justify-content: center; margin-top: 4px; }
        }
        @media (max-width: 560px) {
            .trust-strip, .steps, .voices, .footer-grid { grid-template-columns: 1fr; }
            .steps::before { display: none; }
            .cta-band { padding: 32px 24px; }
            .tag-stack { min-height: 280px; }
            .tag-stack .price-tag:nth-child(1) { width: 72%; left: 8%; }
            .tag-stack .price-tag:nth-child(2) { width: 68%; }
            .tag-stack .price-tag:nth-child(3) { width: 66%; right: 2%; }
        }
    </style>
</head>
<body>

    @php
        $tickerItems = collect($carousels ?? [])->flatMap(function ($block) {
            return collect($block['prices'])->map(function ($price) use ($block) {
                return [
                    'market' => $block['market']->name,
                    'name' => $price['name'],
                    'price' => $price['price'],
                    'unit' => $price['unit'],
                ];
            });
        });
    @endphp

    @if ($tickerItems->isNotEmpty())
        <div class="ticker-band">
            <div class="ticker-track">
                @for ($i = 0; $i < 2; $i++)
                    @foreach ($tickerItems as $item)
                        <span class="ticker-item mono">
                            <span class="dot"></span>
                            <span class="mkt">{{ $item['market'] }} ·</span>
                            <span>{{ $item['name'] }}</span>
                            <span class="amt">₦{{ number_format($item['price'], 0) }}{{ $item['unit'] ? '/'.$item['unit'] : '' }}</span>
                        </span>
                    @endforeach
                @endfor
            </div>
        </div>
    @endif

    <div class="nav-backdrop" id="navBackdrop" hidden></div>
    <div class="wrap">
        <nav class="nav" aria-label="Primary">
            <a class="brand" href="{{ route('home') }}">Market Eye</a>
            <button type="button" class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="navLinks" aria-label="Open menu">
                <span></span>
            </button>
            <div class="nav-links" id="navLinks">
                <a href="#prices">Live prices</a>
                <a href="#how">How it works</a>
                <a href="#trust">Trust</a>
                <a href="{{ route('developers') }}">Developer API</a>
                <a class="btn btn-primary" href="{{ route('developer.login') }}">Developer login</a>
            </div>
        </nav>

        <header class="hero">
            <div>
                <h1>Know the price<br>before you <span class="accent">go.</span></h1>
                <p class="lede">
                    Live, crowd-verified prices from Nigerian markets, in the units traders actually
                    sell in — mudu, bag, paint, kg — so you can budget with confidence before you leave the house.
                </p>
                <div class="hero-actions">
                    <a class="btn btn-primary" href="#prices">See market prices</a>
                    <a class="btn btn-ghost" href="{{ route('developers') }}">Build with our API</a>
                </div>
                <div class="hero-note"><span class="dot"></span> Updated by shoppers and traders across {{ $marketCount }} markets</div>
            </div>
            <div class="tag-stack">
                <div class="price-tag">
                    <div class="tag-label">Markets tracked</div>
                    <div class="tag-value">{{ $marketCount }}</div>
                </div>
                <div class="price-tag">
                    <div class="tag-label">Every price</div>
                    <div class="tag-value">Checked by shoppers</div>
                </div>
                <div class="price-tag">
                    <div class="tag-label">To use it</div>
                    <div class="tag-value">Free, always</div>
                </div>
            </div>
        </header>

        <div class="trust-strip">
            <div class="trust-item">
                <div class="mark">✓</div>
                <div>
                    <h4>Crowd-verified</h4>
                    <p>No price goes live until other shoppers confirm what they're seeing at the same stalls.</p>
                </div>
            </div>
            <div class="trust-item">
                <div class="mark">₦</div>
                <div>
                    <h4>Real market units</h4>
                    <p>Mudu, paint, bag, derica — priced the way traders sell, not converted into kilograms you'll never see quoted.</p>
                </div>
            </div>
            <div class="trust-item">
                <div class="mark">↻</div>
                <div>
                    <h4>Always current</h4>
                    <p>Markets move daily. Submissions refresh the board so old prices don't linger and mislead you.</p>
                </div>
            </div>
        </div>

        <section id="how">
            <div class="section-eyebrow">The process</div>
            <h2 class="section-title">How Market Eye works</h2>
            <p class="section-sub">One job: help Nigerians buy smart with live market truth, gathered from the people already at the market.</p>
            <div class="steps">
                <div class="step"><div class="num">01</div><h3>Check prices</h3><p>Browse live averages by market before you travel, so you know what to expect.</p></div>
                <div class="step"><div class="num">02</div><h3>Budget calmly</h3><p>Compare goods in the units you actually buy, no mental conversion required.</p></div>
                <div class="step"><div class="num">03</div><h3>Submit what you see</h3><p>Spot a price while you shop? Add it, and it's reviewed before it counts.</p></div>
                <div class="step"><div class="num">04</div><h3>Earn airtime</h3><p>Earn ₦1 per verified submission. Claim as airtime once you hit ₦200.</p></div>
            </div>
        </section>

        <section>
            <div class="section-eyebrow">What's on the board</div>
            <h2 class="section-title">Goods people check most</h2>
            <p class="section-sub">A cross-section of what shoppers track across the markets we cover.</p>
            <div class="category-row">
                <a class="category-chip" href="#prices"><span class="swatch" style="background:var(--rust-500)"></span><span>Grains &amp; tubers</span> <small>garri, rice, yam</small></a>
                <a class="category-chip" href="#prices"><span class="swatch" style="background:var(--ochre-500)"></span><span>Produce</span> <small>tomatoes, pepper, onions</small></a>
                <a class="category-chip" href="#prices"><span class="swatch" style="background:var(--green-600)"></span><span>Proteins &amp; fish</span> <small>meat, fish, eggs</small></a>
                <a class="category-chip" href="#prices"><span class="swatch" style="background:var(--indigo-600)"></span><span>Provisions</span> <small>oil, sugar, seasoning</small></a>
                <a class="category-chip" href="#prices"><span class="swatch" style="background:var(--indigo-950)"></span><span>Building materials</span> <small>paint, cement, blocks</small></a>
            </div>
        </section>

        <section id="prices">
            <div class="section-eyebrow">Right now</div>
            <h2 class="section-title">Prices in the market today</h2>
            <p class="section-sub">Swipe each market's row. The tag color shows how confident we are in a price, so you know when it needs more eyes.</p>

            @forelse ($carousels as $block)
                <div class="market-block">
                    <div class="market-head">
                        <h3>{{ $block['market']->name }}</h3>
                        <span>{{ $block['market']->area }}{{ $block['market']->city ? ' · '.$block['market']->city : '' }}</span>
                    </div>
                    <div class="carousel" data-carousel>
                        @foreach ($block['prices'] as $price)
                            <article class="price-tile">
                                <div class="name">{{ $price['name'] }}</div>
                                <div class="unit">{{ $price['unit'] ?: 'market unit' }}</div>
                                <div class="amount">₦{{ number_format($price['price'], 0) }}</div>
                                <span class="stamp {{ $price['confidence'] }}">{{ $price['confidence'] === 'good' ? 'trusted' : 'needs eyes' }}</span>
                            </article>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="empty-note">Price rows will appear here once markets have verified snapshots. Seed the database and approve a few submissions to populate this section.</p>
            @endforelse
        </section>

        <section id="trust">
            <div class="verify-grid">
                <div>
                    <div class="section-eyebrow">How verification works</div>
                    <h2 class="section-title">Why you can trust a tag</h2>
                    <p class="section-sub" style="margin-bottom: 24px;">Every submission starts as a single report. It only becomes a trusted price once the crowd backs it up.</p>
                    <ul class="verify-list">
                        <li>
                            <span class="glyph">01</span>
                            <div><h4>A shopper submits a price</h4><p>Anyone at the market can log what they're paying, in the unit it's sold.</p></div>
                        </li>
                        <li>
                            <span class="glyph">02</span>
                            <div><h4>Nearby reports are compared</h4><p>New submissions are checked against recent ones from the same market and stall type.</p></div>
                        </li>
                        <li>
                            <span class="glyph">03</span>
                            <div><h4>The tag is marked</h4><p>Prices with enough agreement are stamped "trusted"; new or disputed ones show "needs eyes" until confirmed.</p></div>
                        </li>
                    </ul>
                </div>
                <div class="verify-visual">
                    <article class="price-tile">
                        <div class="name">Garri (yellow)</div>
                        <div class="unit">mudu</div>
                        <div class="amount">₦1,200</div>
                        <span class="stamp good">trusted</span>
                    </article>
                    <article class="price-tile">
                        <div class="name">Tomatoes</div>
                        <div class="unit">basket</div>
                        <div class="amount">₦18,500</div>
                        <span class="stamp low">needs eyes</span>
                    </article>
                </div>
            </div>
        </section>

        <section>
            <div class="section-eyebrow">From the markets</div>
            <h2 class="section-title">What shoppers and traders say</h2>
            <p class="section-sub">Illustrative feedback from the kind of people Market Eye is built for.</p>
            <div class="voices">
                <div class="voice-card">
                    <p class="quote">"I check the board before I leave the house now. Saves me from haggling blind at Mile 12."</p>
                    <div class="who">
                        <div class="avatar">A</div>
                        <div><strong>Amaka</strong><span>Shopper, Lagos</span></div>
                    </div>
                </div>
                <div class="voice-card">
                    <p class="quote">"My customers used to argue that my prices were too high. Now they see the same range on the app."</p>
                    <div class="who">
                        <div class="avatar">M</div>
                        <div><strong>Musa</strong><span>Grains trader, Kano</span></div>
                    </div>
                </div>
                <div class="voice-card">
                    <p class="quote">"Submitting prices while I shop earns me small airtime. It only takes a few seconds."</p>
                    <div class="who">
                        <div class="avatar">C</div>
                        <div><strong>Chidinma</strong><span>Shopper, Abuja</span></div>
                    </div>
                </div>
            </div>
        </section>

        <section>
            <div class="cta-band">
                <div>
                    <h2>Build on Nigeria's price standard</h2>
                    <p>Pull categories, markets, and unit-aware prices with an API key and daily rate limits. Documented for platforms building on Nigerian market data.</p>
                </div>
                <a class="btn btn-ochre" href="{{ route('developers') }}">Read the API docs</a>
            </div>
        </section>

        <footer>
            <div class="footer-grid">
                <div class="footer-brand">
                    <div class="brand">Market Eye</div>
                    <p>Live Nigerian market prices, gathered by the people who shop there. Built to make every trip to the market a little less of a guess.</p>
                </div>
                <div class="footer-col">
                    <h5>Product</h5>
                    <ul>
                        <li><a href="#prices">Live prices</a></li>
                        <li><a href="#how">How it works</a></li>
                        <li><a href="#trust">Trust &amp; verification</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h5>Developers</h5>
                    <ul>
                        <li><a href="{{ route('developers') }}">API documentation</a></li>
                        <li><a href="{{ route('developer.register') }}">Developer portal</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h5>Company</h5>
                    <ul>
                        <li><a href="#">About</a></li>
                        <li><a href="#">Contact</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <span>&copy; {{ date('Y') }} Market Eye. All rights reserved.</span>
                <span>Prices are crowd-submitted and reviewed for accuracy, not guaranteed.</span>
            </div>
        </footer>
    </div>

    <script>
        (function () {
            const toggle = document.getElementById('navToggle');
            const links = document.getElementById('navLinks');
            const backdrop = document.getElementById('navBackdrop');
            if (!toggle || !links) return;

            const setOpen = (open) => {
                links.classList.toggle('is-open', open);
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
                if (backdrop) {
                    backdrop.classList.toggle('is-open', open);
                    backdrop.hidden = !open;
                }
                document.body.style.overflow = open ? 'hidden' : '';
            };

            toggle.addEventListener('click', () => setOpen(!links.classList.contains('is-open')));
            backdrop?.addEventListener('click', () => setOpen(false));
            links.querySelectorAll('a').forEach((a) => a.addEventListener('click', () => setOpen(false)));
            window.addEventListener('resize', () => {
                if (window.innerWidth > 768) setOpen(false);
            });
        })();

        document.querySelectorAll('[data-carousel]').forEach((el) => {
            let dir = 1;
            setInterval(() => {
                if (el.scrollWidth <= el.clientWidth + 8) return;
                const max = el.scrollWidth - el.clientWidth;
                const next = el.scrollLeft + dir * 220;
                if (next >= max || next <= 0) dir *= -1;
                el.scrollTo({ left: el.scrollLeft + dir * 220, behavior: 'smooth' });
            }, 3200);
        });
    </script>
</body>
</html>