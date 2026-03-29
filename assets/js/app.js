// ── Couleurs par département ──────────────────────────────────────────────────
const DEPT_COLORS = {
    '01':'#e8a838','02':'#e84c8b','03':'#e8763a','04':'#38c4e8','05':'#3874e8',
    '06':'#38e8c4','07':'#e8503a','08':'#e8c438','09':'#c438e8','10':'#e8e038',
    '11':'#e83874','12':'#e87038','13':'#38a4e8','14':'#5038e8','15':'#e85038',
    '16':'#38e880','17':'#38e8a0','18':'#e8b038','19':'#e86838','2A':'#ff6b9d',
    '2B':'#c44dff','21':'#38e8d4','22':'#38e864','23':'#e84038','24':'#38c8e8',
    '25':'#3858e8','26':'#e8c038','27':'#6038e8','28':'#e8d038','29':'#38e84c',
    '30':'#e83868','31':'#e83858','32':'#e85838','33':'#38d8e8','34':'#e83878',
    '35':'#38e858','36':'#e8b838','37':'#e8c838','38':'#e8a038','39':'#3868e8',
    '40':'#38e898','41':'#e8c038','42':'#e85038','43':'#e8e038','44':'#38e8b4',
    '45':'#e8d038','46':'#e87838','47':'#38b8e8','48':'#e84868','49':'#38e8c8',
    '50':'#4838e8','51':'#e8d838','52':'#c8e838','53':'#38e8d0','54':'#a8e838',
    '55':'#88e838','56':'#38e870','57':'#68e838','58':'#3878e8','59':'#e83888',
    '60':'#e838a8','61':'#7838e8','62':'#e838c8','63':'#e86038','64':'#38a8e8',
    '65':'#e8d438','66':'#e8e838','67':'#48e838','68':'#28e838','69':'#e84838',
    '70':'#3888e8','71':'#e8c038','72':'#38d0e8','73':'#e87838','74':'#e89038',
    '75':'#e83838','76':'#8838e8','77':'#e85838','78':'#e86838','79':'#38e8b8',
    '80':'#e838d8','81':'#e86838','82':'#e87838','83':'#3898e8','84':'#3848e8',
    '85':'#38c0e8','86':'#38d8e8','87':'#e85038','88':'#38e838','89':'#3848e8',
    '90':'#5848e8','91':'#e84848','92':'#e83858','93':'#e83868','94':'#e83878',
    '95':'#e84888','971':'#a038e8','972':'#b038e8','973':'#c038e8',
    '974':'#d038e8','976':'#e038e8',
};

function getDeptColor(dept) {
    if (!dept) return '#94a3b8';
    return DEPT_COLORS[String(dept).toUpperCase()] || '#94a3b8';
}

// ── BASE URL : retire le segment /map de l'URL courante ───────────────────────
const BASE = window.location.pathname
    .replace(/\/map\/?$/, '')   // enlève /map en fin d'URL
    .replace(/\/$/, '');        // enlève le slash final

// ── Vue 3 ─────────────────────────────────────────────────────────────────────
const { createApp, ref, onMounted } = Vue;

createApp({
    setup() {
        const type        = ref('contain');
        const search      = ref('');
        const count       = ref(-1);
        const suggestions = ref([]);
        const showSuggest = ref(false);

        let map          = null;
        let markersLayer = null;
        let baryMarker   = null;
        let legendEl     = null;
        let statsEl      = null;
        let acTimer      = null;

        // ── Init carte ────────────────────────────────
        onMounted(() => {
            map = L.map('map', { zoomControl: false }).setView([46.8, 2.3], 6);
            L.control.zoom({ position: 'bottomright' }).addTo(map);

            L.tileLayer('https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> France',
                maxZoom: 19
            }).addTo(map);

            markersLayer = L.layerGroup().addTo(map);

            document.addEventListener('click', e => {
                if (!e.target.closest('.search-wrap')) showSuggest.value = false;
            });
        });

        function setType(t) {
            type.value = t;
            if (search.value.length > 0) fetchSuggestions();
        }

        function onInput() {
            clearTimeout(acTimer);
            if (search.value.length === 0) {
                suggestions.value = [];
                showSuggest.value = false;
                return;
            }
            acTimer = setTimeout(fetchSuggestions, 220);
        }

        async function fetchSuggestions() {
            if (search.value.length < 1) return;
            try {
                const url = `${BASE}/api/suggestions?q=${encodeURIComponent(search.value)}&type=${type.value}`;
                const res = await fetch(url);
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                suggestions.value = data;
                showSuggest.value = data.length > 0;
            } catch(e) {
                console.error('Suggestions error:', e);
            }
        }

        function pickSuggestion(item) {
            search.value = item.nom;
            showSuggest.value = false;
            searchData();
        }

        async function searchData() {
            showSuggest.value = false;
            if (markersLayer) markersLayer.clearLayers();
            if (baryMarker)   { map.removeLayer(baryMarker); baryMarker = null; }
            if (legendEl)     legendEl.style.display = 'none';
            if (statsEl)      statsEl.style.display  = 'none';

            try {
                const url = `${BASE}/api/communes?type=${type.value}&search=${encodeURIComponent(search.value)}`;
                const res = await fetch(url);
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const geojson = await res.json();
                const features = geojson.features || [];
                count.value = features.length;

                if (features.length === 0) {
                    showToast('Aucune commune trouvée !');
                    return;
                }

                const depts = new Set();
                const lats  = [];
                const lons  = [];

                features.forEach(f => {
                    const dept  = f.properties.dept;
                    const color = getDeptColor(dept);
                    const [lon, lat] = f.geometry.coordinates;

                    depts.add(String(dept).toUpperCase());
                    lats.push(lat);
                    lons.push(lon);

                    const marker = L.circleMarker([lat, lon], {
                        radius: 6,
                        fillColor: color,
                        fillOpacity: 0.88,
                        color: '#fff',
                        weight: 1.2
                    });

                    marker.bindPopup(`
                        <div class="popup-nom">${f.properties.nom}</div>
                        <div class="popup-dept">Département ${dept}</div>
                    `);

                    markersLayer.addLayer(marker);
                });

                const group = L.featureGroup(markersLayer.getLayers());
                if (group.getLayers().length > 0) map.fitBounds(group.getBounds().pad(0.12));

                // Barycentre
                const baryLat = lats.reduce((a,b) => a+b, 0) / lats.length;
                const baryLon = lons.reduce((a,b) => a+b, 0) / lons.length;

                const maxLat = Math.max(...lats), minLat = Math.min(...lats);
                const maxLon = Math.max(...lons), minLon = Math.min(...lons);
                const nord  = features[lats.indexOf(maxLat)].properties.nom;
                const sud   = features[lats.indexOf(minLat)].properties.nom;
                const est   = features[lons.indexOf(maxLon)].properties.nom;
                const ouest = features[lons.indexOf(minLon)].properties.nom;

                const baryIcon = L.divIcon({
                    className: '',
                    html: `<div class="bary-marker">⊕</div>`,
                    iconSize: [28, 28], iconAnchor: [14, 14]
                });

                baryMarker = L.marker([baryLat, baryLon], { icon: baryIcon });
                baryMarker.bindPopup(`
                    <div class="popup-nom">Barycentre</div>
                    <div class="popup-dept">
                        Centre géographique · ${features.length} communes<br>
                        ${baryLat.toFixed(4)}°N · ${baryLon.toFixed(4)}°E
                    </div>
                `);
                baryMarker.addTo(map);

                buildLegend([...depts].sort());
                buildStats({ nord, sud, est, ouest, baryLat, baryLon });

            } catch(e) {
                console.error('Search error:', e);
                showToast('Erreur serveur');
            }
        }

        function preset(t, s) { type.value = t; search.value = s; searchData(); }

        function buildLegend(depts) {
            if (!legendEl) {
                legendEl = document.createElement('div');
                legendEl.className = 'legend';
                document.getElementById('map').appendChild(legendEl);
            }
            legendEl.style.display = 'block';
            legendEl.innerHTML = `<div class="legend-title">Départements</div>` +
                depts.map(d => `<div class="legend-item"><span class="legend-dot" style="background:${getDeptColor(d)}"></span>${d}</div>`).join('');
        }

        function buildStats({ nord, sud, est, ouest, baryLat, baryLon }) {
            if (!statsEl) {
                statsEl = document.createElement('div');
                statsEl.className = 'stats-panel';
                document.getElementById('map').appendChild(statsEl);
            }
            statsEl.style.display = 'block';
            statsEl.innerHTML = `
                <div class="stats-title">⊕ Barycentre</div>
                <div class="stats-coord">${baryLat.toFixed(4)}°N · ${baryLon.toFixed(4)}°E</div>
                <div class="stats-divider"></div>
                <div class="stats-row"><span class="stats-dir">↑ Nord</span><span class="stats-val">${nord}</span></div>
                <div class="stats-row"><span class="stats-dir">↓ Sud</span><span class="stats-val">${sud}</span></div>
                <div class="stats-row"><span class="stats-dir">→ Est</span><span class="stats-val">${est}</span></div>
                <div class="stats-row"><span class="stats-dir">← Ouest</span><span class="stats-val">${ouest}</span></div>
            `;
        }

        function showToast(msg) {
            let t = document.getElementById('toast');
            if (!t) { t = document.createElement('div'); t.id = 'toast'; document.body.appendChild(t); }
            t.textContent = msg;
            t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 2800);
        }

        function deptColor(dept) { return getDeptColor(dept); }

        return { type, search, count, suggestions, showSuggest,
                 setType, onInput, pickSuggestion, searchData, preset, deptColor };
    }
}).mount('#app');
