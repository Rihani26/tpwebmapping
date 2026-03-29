<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GéoVilles FR</title>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800;900&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; overflow: hidden; font-family: 'Barlow', sans-serif; background: #f5f0e8; color: #1a1a14; }

        .page { display: grid; grid-template-columns: 460px 1fr; height: 100vh; }

        /* LEFT */
        .left {
            display: flex;
            flex-direction: column;
            padding: 44px 48px;
            background: #1a1a14;
            position: relative;
            z-index: 2;
            overflow-y: auto;
        }

        /* Grain texture */
        .left::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
            pointer-events: none;
            z-index: 0;
        }

        .left > * { position: relative; z-index: 1; }

        .logo-wrap { display: flex; align-items: center; gap: 12px; margin-bottom: 52px; }

        .logo-icon {
            width: 38px; height: 38px;
            background: #c9a84c;
            border-radius: 4px;
            display: flex; align-items: center; justify-content: center;
        }
        .logo-icon svg { width: 20px; height: 20px; fill: #1a1a14; }

        .logo-text {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 0.82rem;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #fff;
            line-height: 1.2;
        }
        .logo-text span { display: block; font-weight: 400; font-size: 0.68rem; color: #7a7060; letter-spacing: 0.06em; }

        .hero { flex: 1; display: flex; flex-direction: column; justify-content: center; }

        .tag {
            display: inline-flex; align-items: center; gap: 8px;
            background: #2d6a4f22;
            border: 1px solid #2d6a4f;
            color: #4caf7d;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 5px 12px;
            border-radius: 3px;
            margin-bottom: 22px;
            width: fit-content;
        }

        .tag::before {
            content: '';
            width: 6px; height: 6px;
            border-radius: 50%;
            background: #4caf7d;
            animation: blink 2s infinite;
        }

        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.3} }

        h1 {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 3.2rem;
            font-weight: 900;
            line-height: 0.95;
            letter-spacing: -0.01em;
            text-transform: uppercase;
            color: #fff;
            margin-bottom: 22px;
        }

        h1 em { font-style: normal; color: #c9a84c; }

        .sub {
            font-size: 0.92rem;
            color: #7a7060;
            line-height: 1.7;
            margin-bottom: 38px;
            max-width: 340px;
        }

        .cta {
            display: inline-flex; align-items: center; gap: 10px;
            background: #2d6a4f;
            color: #fff;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 13px 28px;
            border-radius: 4px;
            text-decoration: none;
            width: fit-content;
            transition: background .2s, transform .15s;
            margin-bottom: 14px;
        }
        .cta:hover { background: #1e4d39; transform: translateY(-1px); }
        .cta svg { width: 16px; height: 16px; fill: none; stroke: #fff; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }

        .cta-hint { font-size: 0.72rem; color: #4a4030; letter-spacing: 0.04em; }

        .features {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 44px;
            padding-top: 32px;
            border-top: 1px solid #2a2a1e;
        }

        .feat { display: flex; align-items: flex-start; gap: 10px; }

        .feat-icon {
            width: 30px; height: 30px;
            border-radius: 3px;
            background: #2a2a1e;
            border: 1px solid #3a3a2a;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .feat-icon svg { width: 14px; height: 14px; fill: none; stroke: #c9a84c; stroke-width: 2; stroke-linecap: round; }

        .feat-text strong { display: block; font-family: 'Barlow Condensed', sans-serif; font-size: 0.82rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: #fff; margin-bottom: 2px; }
        .feat-text span { font-size: 0.7rem; color: #5a5040; line-height: 1.4; }

        .footer { margin-top: 32px; font-size: 0.68rem; color: #3a3020; letter-spacing: 0.06em; text-transform: uppercase; }

        /* RIGHT */
        .right { position: relative; overflow: hidden; }
        #preview-map { width: 100%; height: 100%; }
        .right::before {
            content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 50px;
            background: linear-gradient(to right, #1a1a14, transparent);
            z-index: 10; pointer-events: none;
        }

        .map-badge {
            position: absolute; top: 20px; right: 20px;
            background: rgba(253,250,244,0.95);
            backdrop-filter: blur(8px);
            border: 1px solid #d9d0bc;
            border-top: 2px solid #c9a84c;
            border-radius: 4px;
            padding: 12px 16px;
            z-index: 20;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }
        .map-badge strong {
            display: block;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 1.6rem;
            font-weight: 900;
            color: #2d6a4f;
            letter-spacing: -0.02em;
            line-height: 1;
            margin-bottom: 2px;
        }
        .map-badge span { font-size: 0.7rem; color: #7a7060; font-family: 'Barlow Condensed', sans-serif; text-transform: uppercase; letter-spacing: 0.06em; }
    </style>
</head>
<body>
<div class="page">
    <div class="left">
        <div class="logo-wrap">
            <div class="logo-icon">
                <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
            </div>
            <div class="logo-text">
                GeoData Paris
                <span> Webmapping </span>
            </div>
        </div>

        <div class="hero">
            <div class="tag">Cartographie interactive</div>
            <h1>Explorer les <em>communes</em><br>françaises</h1>
            <p class="sub">Visualisez 36 000+ communes sur une carte interactive. Filtrez par nom, découvrez des patterns géographiques et calculez le barycentre de chaque recherche.</p>

            <a href="map" class="cta">
                <svg viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                Ouvrir la carte
            </a>
            <span class="cta-hint">Données OpenStreetMap France · MySQL Spatial</span>

            <div class="features">
                <div class="feat">
                    <div class="feat-icon"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></div>
                    <div class="feat-text"><strong>Autocomplete</strong><span>Suggestions dès la 1ère lettre</span></div>
                </div>
                <div class="feat">
                    <div class="feat-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/></svg></div>
                    <div class="feat-text"><strong>Par département</strong><span>Couleur unique par dép.</span></div>
                </div>
                <div class="feat">
                    <div class="feat-icon"><svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg></div>
                    <div class="feat-text"><strong>3 modes filtre</strong><span>Début · contient · fin</span></div>
                </div>
                <div class="feat">
                    <div class="feat-icon"><svg viewBox="0 0 24 24"><path d="M12 2v20M2 12h20"/></svg></div>
                    <div class="feat-text"><strong>Barycentre</strong><span>Centre géo + extrémités</span></div>
                </div>
            </div>
        </div>

        <div class="footer">© 2026 GeoData Paris · </div>
    </div>

    <div class="right">
        <div id="preview-map"></div>
        <div class="map-badge">
            <strong>36 684</strong>
            <span>communes en France</span>
        </div>
    </div>
</div>

<script>
    const map = L.map('preview-map', {
        zoomControl: false, dragging: false, scrollWheelZoom: false,
        doubleClickZoom: false, keyboard: false
    }).setView([46.6, 2.3], 6);

    L.tileLayer('https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap France', maxZoom: 19
    }).addTo(map);
</script>
</body>
</html>
