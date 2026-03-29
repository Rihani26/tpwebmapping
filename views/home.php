<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GéoVilles FR — Carte</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div id="app">
    <header>
        <a href="./" class="back-btn" title="Accueil">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 12H5M12 5l-7 7 7 7"/>
            </svg>
        </a>

        <h1>Géo<span>Villes</span> FR</h1>
        <div class="divider"></div>

        <div class="controls">
            <div class="type-tabs">
                <button :class="{ active: type === 'start' }"   @click="setType('start')">Commence par</button>
                <button :class="{ active: type === 'contain' }" @click="setType('contain')">Contient</button>
                <button :class="{ active: type === 'end' }"     @click="setType('end')">Se termine par</button>
            </div>

            <div class="search-wrap">
                <input
                    v-model="search"
                    @input="onInput"
                    @keydown.enter="searchData"
                    @focus="onInput"
                    placeholder="ex: plou, saint, heim…"
                />
                <div class="autocomplete" v-show="showSuggest && suggestions.length > 0">
                    <div
                        class="autocomplete-item"
                        v-for="item in suggestions"
                        :key="item.nom + item.dept"
                        @mousedown.prevent="pickSuggestion(item)"
                    >
                        <span class="ac-nom">{{ item.nom }}</span>
                        <span class="ac-dept" :style="{ borderColor: deptColor(item.dept), color: deptColor(item.dept) }">
                            {{ item.dept }}
                        </span>
                    </div>
                </div>
            </div>

            <button class="btn-search" @click="searchData">Rechercher</button>

            <div class="presets">
                <button class="preset-btn" @click="preset('start','plou')">Plou—</button>
                <button class="preset-btn" @click="preset('end','ac')">—ac</button>
                <button class="preset-btn" @click="preset('contain','ville')">—ville—</button>
                <button class="preset-btn" @click="preset('end','heim')">—heim</button>
                <button class="preset-btn" @click="preset('start','k')">K—</button>
            </div>

            <div class="stats" v-if="count >= 0">
                <strong>{{ count }}</strong> résultat{{ count > 1 ? 's' : '' }}
            </div>
        </div>
    </header>

    <div id="map"></div>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>
