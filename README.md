# GéoVilles FR 🗺️

Application web cartographique permettant de visualiser les communes françaises selon leur nom.  
Projet réalisé dans le cadre du TP Webmapping — **ESTIAM Paris / GeoData Paris**.

## Stack technique

| Couche | Techno |
|--------|--------|
| Backend | PHP + [Flight](https://flightphp.com/) |
| Frontend | Vue.js 3 + Leaflet.js |
| Base de données | MySQL/MariaDB — base `geobase`, table `communes` |
| Fond de carte | OpenStreetMap France (`osmfr`) |

## Lancement (WAMP)

1. Placer le dossier `tpwebmapping/` dans `C:/wamp64/www/`
2. Activer `mod_rewrite` : icône WAMP → Apache → Apache modules → `rewrite_module`
3. Activer `AllowOverride All` dans `httpd.conf` (bloc `<Directory "c:/wamp64/www/">`)
4. Redémarrer WAMP
5. Ouvrir `http://localhost/tpwebmapping/`

## Structure des fichiers

```
tpwebmapping/
├── .htaccess               # Réécriture URL pour Flight
├── index.php               # Routes (landing, carte, API)
├── config/
│   └── db.php              # Connexion PDO MySQL
├── lib/flight/             # Micro-framework Flight
├── views/
│   ├── landing.php         # Page d'accueil
│   └── home.php            # Interface cartographique
└── assets/
    ├── css/style.css
    └── js/app.js           # Vue 3 app + Leaflet + barycentre
```

## Navigation

```
/                → Page d'accueil (landing)
/map             → Carte interactive
/api/communes    → API GeoJSON (points)
/api/suggestions → API autocomplete
```

## API

### `GET /api/communes`

| Paramètre | Valeurs possibles | Défaut |
|-----------|-------------------|--------|
| `type` | `start`, `end`, `contain` | `contain` |
| `search` | chaîne de caractères | `` |

Retourne un **GeoJSON FeatureCollection** de points (centroïdes des polygones).  
Chaque feature contient `nom` et `dept` (code INSEE département).

Exemple : `/api/communes?type=start&search=plou`

### `GET /api/suggestions`

| Paramètre | Description |
|-----------|-------------|
| `q` | Chaîne de recherche (min 1 caractère) |
| `type` | Même que ci-dessus |

Retourne un tableau JSON de `{ nom, dept }` — max 15 résultats.  
Utilisé pour l'autocomplétion en temps réel.

## Fonctionnalités

### Recherche par nom
Trois modes de filtrage SQL via `LIKE` :
- **Commence par** → `saint%`
- **Contient** → `%ville%`
- **Se termine par** → `%heim`

### Autocomplétion (fonctionnalité de base)
Dès la première lettre saisie, un dropdown liste les 15 premières communes avec leur département coloré. Clic = recherche instantanée.

### Couleurs par département
Chaque point sur la carte est coloré selon son `departement_insee`. La légende dynamique (bas gauche) liste uniquement les départements présents dans les résultats.

### Boutons préparamétrés
| Bouton | Filtre | Région typique |
|--------|--------|----------------|
| `Plou—` | commence par *plou* | Bretagne |
| `—ac` | se termine par *ac* | Occitanie/Sud-Ouest |
| `—ville—` | contient *ville* | Partout |
| `—heim` | se termine par *heim* | Alsace |
| `K—` | commence par *k* | Bretagne/Alsace |

### 🎯 Fonctionnalité différenciante — Barycentre géographique

Après chaque recherche, l'application calcule automatiquement le **barycentre** (centre de masse géographique) de l'ensemble des communes trouvées.

**Fonctionnement :**
1. On récupère les coordonnées lat/lon de chaque commune (centroïde du polygone, calculé côté SQL via `ST_Centroid`)
2. Le barycentre est la **moyenne arithmétique** des latitudes et des longitudes :
   - `baryLat = Σ(lat_i) / n`
   - `baryLon = Σ(lon_i) / n`
3. Un marqueur **⊕** noir est affiché sur la carte à cette position
4. Un clic sur le marqueur affiche les coordonnées exactes

**Panneau de statistiques (bas droite) :**  
En parallèle, un panneau affiche les 4 communes aux extrémités cardinales :
- ↑ **Nord** : commune avec la latitude maximale
- ↓ **Sud** : commune avec la latitude minimale
- → **Est** : commune avec la longitude maximale
- ← **Ouest** : commune avec la longitude minimale

Exemple avec *"plou"* : le barycentre tombe au centre de la Bretagne, confirmant la forte concentration bretonne de ces communes. Avec *"heim"*, il se positionne en Alsace.

Cette fonctionnalité permet de **visualiser instantanément la répartition géographique** d'un pattern de nom sur le territoire français.

## Auteur

Elyes — ESTIAM Paris E5 WMD — Alternance GeoData Paris
