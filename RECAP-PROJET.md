# Récapitulatif du projet — mesfenetresvolets.fr

Site e-commerce sur-mesure (volets roulants & fenêtres), 100 % autonome dans un seul fichier
`index.html` (HTML + JS vanilla, aucune dépendance, aucun build). Données réelles de marques
récupérées en ligne, photos téléchargées en local dans `assets/`.

Dernière mise à jour : **8 juillet 2026** — **audit sécurité & cohérence complet** + lot de correctifs
(voir §12) : **mode démonstration** (bandeau + paiement CB désactivé via `DEMO`), **polices auto-hébergées**
(RGPD, plus aucun appel Google), meta **SEO/Open Graph**, ventilation panier corrigée, badge panier en
quantités, « dès 275 €/m² », sens d'ouverture masqué si châssis fixe. Historique : 5 juillet — GitHub Pages,
passe responsive, retrait émojis, étude paiement (§11) ; 30 juin — PVC uniquement, page Aide à la mesure.

---

## 1. Lancer le site en local

Serveur configuré dans [.claude/launch.json](.claude/launch.json) :

| Config | Commande | Port | Note |
|---|---|---|---|
| **Static site (npx serve)** ✅ | `npx -y serve -l 3000 .` | 3000 | **À utiliser** |

→ Ouvrir **http://localhost:3000**

Le projet étant dans iCloud Drive, le serveur Python (`python3 -m http.server`) ne fonctionne pas :
le sandbox macOS bloque `os.getcwd()` (`PermissionError: Operation not permitted`). Cette config a
été retirée du `launch.json`. `npx serve` sert un chemin explicite et fonctionne.

### Mise en ligne (GitHub Pages)

Le site est **publié** et partageable :

- **URL publique** : **https://kevkobal.github.io/fenetresvolets/**
- **Dépôt** : [KevKobal/fenetresvolets](https://github.com/KevKobal/fenetresvolets) (public), remote `origin`, branche `main`.
- **Déploiement** : GitHub Pages sert la racine du dépôt ; se redéploie seul ~1 min après chaque `git push`.
- **Convention** : après **toute modif du site**, on fait `git add -A && git commit && git push` (auto-push, mémorisé).
- **Mettre à jour manuellement** :
  ```
  cd "…/FenetresVolets.fr" && git add -A && git commit -m "maj" && git push
  ```
- Les **PDF de travail** (ex. comparatif paiement) sont **exclus du dépôt** via `.gitignore` (`*.pdf`).

---

## 2. Marques intégrées

### Volets roulants solaires — **Bubendorff**
Fabricant français · moteur solaire SO · batterie lithium-ion 3,4 Ah (jusqu'à 6 semaines
d'autonomie) · testé 30 000 cycles · SAV 30 ans · mode CLIMAT+.
- **Mono ID4 Solaire** (lames alu standard) — dès 320 €/m²
- **Mono ID4 Solaire orientable** (+45 €/m²) — dès 365 €/m²
- Showcase d'accueil : + **Rolax solaire** (verrière) et **Store ZIP solaire**
- Dans le configurateur volet : l'option « Solaire Bubendorff » affiche un encart **Gamme Bubendorff**.

### Fenêtres PVC — **Kömmerling 76**
Profilé allemand 76 mm · double/triple vitrage · coloris anthracite, chêne doré, gris, brun chocolat…
- **76 MD** : 6 chambres, triple joint, Uw ≥ 0,77 — dès 275 €/m² (**seul profilé proposé**)
- ⚠️ **Le profilé « 76 AD » a été retiré** (sur demande — choix inversé : on garde le MD, pas l'AD) :
  supprimé de `KOMMERLING.profils`, de la vitrine d'accueil, du sélecteur de profilé du configurateur
  et de la section accueil `brandKommerling`. Défaut `S.fenetre.profil = '76md'`. Les grilles
  (sélecteur + accueil) s'adaptent au nombre de profilés (`repeat(n,1fr)`) → une seule carte.
- Dans le configurateur fenêtre : matériau « PVC Kömmerling » affiche un encart **Profilé Kömmerling 76**.
- **Nuancier menuiserie mis à jour** (programme couleurs Kömmerling, 30 juin) : 12 coloris — Blanc
  RAL 9016 · Blanc crème RAL 9001 · Gris quartz RAL 7039 · Gris anthracite RAL 7016 · Gris basalte
  RAL 7012 · Brun chocolat RAL 8017 · Vert sapin RAL 6009 · Bleu acier RAL 5011 · Rouge pourpre
  RAL 3004 · Chêne doré · Noyer · Winchester (décors bois). Maps `COL`/`couleurName` enrichies ; le
  tablier volet garde ses propres teintes (`sable`, `beige` conservées pour le volet uniquement).
- **Application du coloris — une face / deux faces** (comme sur koemmerling.com, 30 juin) : champ
  `S.fenetre.faces` (`'2'` par défaut = deux faces). Dans les **Finitions**, sous le nuancier, un
  sélecteur (`optCards 'fenetre','faces'`) s'affiche **uniquement si la teinte ≠ blanc** :
  **Deux faces** (même teinte int/ext) ou **Une face** (extérieur coloré, intérieur blanc RAL 9016).
  Le sous-titre « Couleur de menuiserie » et le libellé panier (`itemSub` via `facesName`) indiquent
  le choix ; récap d'attributs (`summaryRows`) ajoute une ligne **Application** si teinte ≠ blanc.
- **Grille tarifaire fenêtre (TTC) — interpolation sur la surface** (30 juin) : constante `FEN_PRIX`
  (3 tailles de référence par type, colonnes `blanc` / `f1` = 1 face ext / `f2` = 2 faces). `priceFor`
  interpole **linéairement sur la surface réelle** (`lerpFen`, extrapolation au-delà des bornes) →
  retombe **exactement** sur les prix fournis aux tailles de référence. Mapping ouverture → grille :
  `1v`, `fixe` → grille **1 vantail** ; `2v` → grille **2 vantaux** ; **`fixe` = 1 vantail ÷ 1,5**.
  ⚠️ **Type « Oscillo-battant » retiré comme choix séparé** (sur demande) : la fonction oscillo-battant
  est désormais **incluse d'office** dans le 1 vantail ET le 2 vantaux (mention dans `secOuverture` +
  descriptions des cartes). Le sélecteur ne propose plus que **1 vantail · 2 vantaux · Fixe**
  (grille `repeat(3,1fr)`). `ouvertureName` garde l'entrée `oscillo` de façon défensive (non proposée).
  Suppléments ajoutés après : **triple vitrage** `FEN_TRIPLE_M2` (70 €/m²), **seuil alu 20 mm**
  `FEN_SEUIL` (+100 €). Arrondi à l'euro (plus de `/5*5` pour la fenêtre). Anciennes formules
  `260/390 €/m²` + `×1,10/1,18` **supprimées**.
- **Option seuil bas 20 mm** : champ `S.fenetre.seuil` (`'non'` par défaut), toggle dans les Finitions
  (« Sans seuil » / « Seuil 20 mm · +100 € »). Reporté au panier (`itemSub`) et au récap (`summaryRows`).
- **Option grille de ventilation** : champ `S.fenetre.grille` (`'non'` par défaut), constante
  `FEN_GRILLE` (**+30 €**), toggle dans les Finitions sous le seuil (« Sans grille » / « Grille de
  ventilation · +30 € »). Reporté au panier (`itemSub`) et au récap (`summaryRows` → ligne « Ventilation »).
  Tailles de référence : **1 vantail** 600×600 (323/380/400) · 1200×1200 (562/663/697) · 1200×2200
  (852/998/1046) ; **2 vantaux** 1000×1000 (705/823/862) · 1400×1400 (924/1080/1132) · 1800×2200
  (1547/1800/1885). [blanc / 1 face / 2 faces].
- Section d'accueil dédiée + galerie photo « En images ».
- ⚠️ **PVC uniquement** : les matériaux **Aluminium** puis **Bois** ont été retirés du configurateur
  fenêtre (sur demande). Le sélecteur « Matériau » ne propose plus que **PVC Kömmerling** (carte unique).
  Les maps `matiereName`/prix ne listent plus `bois` ; `alu` reste de façon défensive (non proposé). Le
  « Décor bois Chêne doré » conservé est un **coloris aspect bois sur PVC**, pas une fenêtre en bois.

---

## 3. Boutique de pièces Bubendorff (écran `pieces`)

Accessible via la nav « Pièces & moteurs », la bande d'accueil « Deux façons de commander »,
et un bouton dans le configurateur. **16 références** photographiées, en 4 catégories :

- **Moteurs (5)** : radio R 10/33 Nm, filaire MI2 25 Nm, HY orientable, Rolax — 221 à 335 €
- **Kits de motorisation (4)** : Radio, Filaire, Hybrid, Solaire — 312 à 567 €
- **Commandes (3)** : horloge ID2, émetteur Tyxia 2330, iDiamant Netatmo — 88 à 246 €
- **Adaptations (4)** : adaptateur ID 2.0, support moteur, câble, prolongateur PV — 11 à 42 €

Ajout au panier avec **incrément de quantité automatique**, vignette photo dans le panier,
intégré aux totaux HT/TVA/livraison.

---

## 4. Effets & animations

- **Hero** : photo de façade réelle (maison + fenêtres) en plein cadre, dégradé sombre pour la
  lisibilité. (`assets/kommerling/76-facade.jpg`) Le **grand titre `mesfenêtresvolets.fr` a été retiré**
  du hero (28 juin) — il ne reste que le badge « Fabrication française · sur-mesure », le sous-titre
  et les 2 boutons. (La version « Sparkles » canvas testée précédemment a été abandonnée ; la fonction
  JS `sparklesMount()` subsiste mais est inoffensive : elle sort si le canvas est absent.)
  - **Texte « Scroll Velocity »** (d'après *21st.dev — Edil-ozi/scroll-velocity*) : le texte du hero est
    un **défilement horizontal en boucle** (2 bandes en sens opposés) des phrases **Qualité • Volets
    Roulants • Fenêtres • Fabriquées en France • Pièces Détachées** (séparateur `•` en accent). Conteneur
    `.sv-wrap` (largeur `100vw`, centré), 2 `.sv-row` (`overflow:hidden`) contenant chacune un `.sv-track`
    (`#sv-track1/2`, attribut `data-dir` = 1/-1). Animé **en JS (rAF)** par `scrollVelocityMount()` :
    vitesse de fond 55 px/s × `dir`, **+ vélocité de scroll** de la page (la position défile plus vite et
    peut s'inverser quand on scrolle ; léger `skewX` ±7° proportionnel). Largeur d'un jeu de phrases
    mesurée (`scrollWidth/6`) puis bouclée par modulo → couture invisible ; recalcul sur
    `document.fonts.ready` et au `resize`. `dt` borné (onglet masqué). Photo + dégradé en `z-index`
    dessous → texte lisible.
    - ⚠️ **L'ancien « Gooey Text Morphing » a été retiré** (et avant lui « Ink Reveal », qui provoquait un
      hero noir). La fonction `gooeyTextMount()` et le filtre `#gooey-threshold` ne sont **plus appelés/
      présents** dans le hero ; le repli reste la photo `<img>` toujours visible (jamais de hero noir).
  - **Logo image dans le header, centré et agrandi ×3** : le wordmark texte a été **remplacé par le
    logo** `assets/logos/logo-mfv.png` (`<img>` dans `.hdr-logo`, `position:absolute;left:50%;
    transform:translateX(-50%)`, `onclick=goHome()`), hauteur **150px** (96px ≤820px, 72px ≤560px).
    La barre du header (`.hdr-inner`) est passée à **172px** (116px ≤820px, 92px ≤560px) pour le contenir ;
    les panneaux collants `position:sticky` sont à **top:184px** (config/panier/paiement + bandeau pièces).
    Le logo a été **détouré** (fond transparent) et **recadré serré** (profils d'encre + autocrop, script
    PIL flood-fill depuis les bords) depuis la source fournie `assets/logos/Logo mesfentresvolets.fr.png`
    (4096², fond dégradé) → `logo-mfv.png` 1600×652 transparent (version **.FR**). (Avant : wordmark HTML
    `mes**fenêtres**volets.fr`, puis logo `.com` ; la source 4k et l'ancien `.hero-brand` subsistent, non utilisés.)
    - ⚠️ Mobile : pour éviter le chevauchement du logo centré, le bouton **Panier passe en icône seule**
      ≤560px (`.cart-label` masqué, padding réduit).
  - **Drapeau français mat « Fenêtre française »** : classe **`.fr-flag`** **centré en haut** du hero
    (`top:24px;left:50%;translateX(-50%)`, `width:clamp(96px,12vw,140px)`, 84px ≤560px). Drapeau `.flag`
    (ratio 3/2, coins arrondis, 3 bandes `<i>` `#0055A4`/`#F2F2F2`/`#EF4135`) **effet mat** : pas de
    reflet, bordure discrète + ombre portée douce. Légende `.cap` « Fenêtre française » dessous.
    (Avant : version premium brillante, puis blason `.fr-seal`/sceau circulaire — abandonnés.)
- **Fluid menu** (d'après *21st.dev — deepaksslibra/fluid-menu*) : la nav du header (liens desktop +
  hamburger mobile) est remplacée par un **bouton circulaire** (`.fluid-toggle`, icône hamburger ↔ ✕)
  qui déploie une **capsule blanche** (`.fluid-panel`) listant Accueil · Volets roulants · Fenêtres ·
  Pièces & moteurs · Aide à la mesure · **Demander à être rappelé** · Panier (icônes SVG dans pastilles
  accent, entrée animée en CSS `@keyframes`). État `S.navOpen` + `toggleNav()`. Même menu desktop ET
  mobile. À droite du header : bouton **« Demander à être rappelé »** (`.hdr-phone`, → `goCallback()`,
  masqué ≤820px → dispo via le menu) + bouton **Panier en contour néon** (voir ci-dessous).
  - ⚠️ Les **pastilles de sélection de thème ont été retirées** du header (`setTheme` existe encore
    mais n'est plus appelé → thème fixe « Bleu »/azure).
- **Aurora background pleine page** (d'après *21st.dev — aceternity/aurora-background*) : div fixe
  `.aurora-bg` (`position:fixed;inset:0;z-index:-1`) placé dans le `<body>` **avant `#root`**. Dégradé
  aurora bleu/indigo (`repeating-linear-gradient` couleurs Tailwind blue-500/indigo-300/violet-200…)
  superposé à un `--white-gradient`, `background-size:300%,200%`, **flouté** (`blur(14px)`, `opacity:.6`)
  et animé en boucle par `@keyframes auroraFlow` (60 s, défile la `background-position`). Masque vertical
  (`mask-image` linéaire) pour adoucir le bas ; `prefers-reduced-motion` coupe l'anim.
  - Pour que l'aurora **transparaisse globalement** : dans `THEMES`, `bg` → `rgba(255,255,255,.45)` et
    `section` → `rgba(246,248,250,.55)` (translucides) ; `surface` reste **blanc opaque** → cartes &
    header nets, l'aurora ne glow que dans les bandes/fonds de section. (Hero garde sa photo opaque.)
- **Neon button** (d'après *21st.dev — cybergaz/neon-button*) : classe **`.neon-btn`** appliquée à **tous
  les boutons d'action** du site (Panier, CTA hero, « Demander à être rappelé », Suivant/Précédent,
  Ajouter au panier, Acheter des pièces, Valider et payer, Payer, Être rappelé, Retour à l'accueil…).
  Fond transparent + **bordure lumineuse** `var(--neon)` (= `--accent`) + `text-shadow`/`box-shadow` glow
  (outer + inset, via `color-mix`) ; au survol le fond se remplit de l'accent, texte blanc, glow intensifié.
  Variante **`.is-light`** (néon clair `#cfe0ff`) pour les boutons posés sur fond sombre (CTA hero «
  Configurer une fenêtre » + bande CTA `var(--ink)` « Configurer mon projet »). Les contrôles utilitaires
  (steppers quantité ±, onglets volet/fenêtre, flèches du carrousel, menu fluide) **restent neutres**.
  - L'ancienne classe `.cart-btn` (contour discret) et `.aurora-btn` subsistent dans le CSS mais ne sont
    plus utilisées. Badge de comptage panier en pastille `var(--accent)` texte blanc.
- **Bandeau de marques défilant** (style *21st.dev logos3*) : marquee infini « Nos marques
  partenaires » — **Kömmerling 76 · Bubendorff · Siegenia · Verissima** (logos officiels en images,
  niveaux de gris → couleur au survol, pause au survol). ⚠️ **Caloriver/Calorigroup retiré, remplacé
  par Verissima** (sur demande) : logo image `assets/logos/VERRISSIMA-GROUPE.jpg.webp` (fourni). Le
  tableau `logos` gère `{img}` ou `{text}` (le repli wordmark `.marquee-word` subsiste, non utilisé).
- **Effet de survol du menu** (style *21st.dev — minhxthanh/menu-hover-effects*) : soulignement
  accent qui glisse depuis la gauche au survol, ressort vers la droite à la sortie.
- **Carrousel circulaire** (style *21st.dev — Northstrix/circular-testimonials*) : section
  « Notre savoir-faire », empilement 3D rotatif des **3 photos d'usine** (active centrée ;
  latérales `rotateY ±15°`, `scale 0.85`, opacité 0.7, perspective 1000px), autoplay 5 s + flèches.

> ⚠️ **Note Caloriver** : aucun logo Caloriver dédié exploitable (seul un GIF basse résolution à
> fond opaque existe). Logo **Calorigroup** (maison-mère de Caloriver) utilisé à la place,
> propre et transparent. À remplacer si un logo Caloriver détouré est fourni.

---

## 5. Page d'accueil — produits affichés

Grille réduite à **3 produits** (sur demande, retrait du « Volet électrique radio », de la
« Fenêtre aluminium » puis de la « Fenêtre PVC 76 MD ») :
1. Volet solaire Mono ID4 (Bubendorff)
2. Volet solaire orientable (Bubendorff)
3. Fenêtre PVC 76 MD (Kömmerling)

> Le volet électrique radio reste disponible dans le **configurateur** (motorisation radio) ; il a
> seulement été retiré de la vitrine d'accueil.
>
> ⚠️ **Plus aucune fenêtre en aluminium ni en bois** : seuls le **PVC Kömmerling** est proposé au
> configurateur fenêtre (voir §2 — le Bois a lui aussi été retiré). Le footer liste « Fenêtre PVC ».
> (Les mappings `matiereName`/prix gardent l'entrée `alu` de façon défensive pour d'éventuelles
> données existantes, mais elle n'est plus proposée.)

---

## 6. Inventaire des photos (`assets/`)

| Dossier | Contenu | Source |
|---|---|---|
| `assets/` | 4 produits Bubendorff (mono, orientable, rolax, zip) + 2 profilés Kömmerling + façade/salon | bubendorff.com, fenetre24.com |
| `assets/pieces/` | 16 pièces Bubendorff (moteurs, kits, commandes, adaptations) | moteur-volet-roulant.fr |
| `assets/kommerling/` | 13 photos Kömmerling 76 (profilés, coupes, intérieur/extérieur, coloris, ferrures, façade) | fenetre24.com |
| `assets/Fabrication Fr/` | 3 photos d'usine : Usine, Usine 1, Usine 2 (carrousel savoir-faire) | fournies par l'utilisateur |
| `assets/usine/` | 3 photos d'usine Bubendorff (anciennes, non utilisées actuellement) | bubendorff.com |
| `assets/fonts/` | 4 polices variables woff2 auto-hébergées : Schibsted Grotesk + Hanken Grotesk (latin & latin-ext) | Google Fonts (téléchargées le 8 juil.) |
| `assets/logos/` | logos : bubendorff.svg, kommerling.png, siegenia.svg, caloriver.svg (= Calorigroup), VERRISSIMA-GROUPE.jpg.webp, logo-mfv.png (+ source 4k) | sites officiels |
| `Volet roulant Bubendorff.jpg` (racine) | détourage produit volet anthracite (non utilisé — inadapté au hero) | fournie par l'utilisateur |

---

## 7. Sources des données

- **Bubendorff** : [bubendorff.com](https://www.bubendorff.com/) (produits, photos usine, logo)
- **Pièces Bubendorff** : [moteur-volet-roulant.fr](https://moteur-volet-roulant.fr/volet-roulant/9-bubendorff) (réfs, prix, photos)
- **Kömmerling 76** : [fenetre24.com](https://www.fenetre24.com/fenetres/pvc/kommerling.php) (specs, photos, coloris)
- **Logos** : koemmerling.com, siegenia.com, calorigroup.fr

> ⚠️ **clic-volet.fr** (site demandé initialement) renvoie **403 sur tout** (pare-feu anti-bot) —
> impossible à scraper. Données équivalentes récupérées via le fabricant et des revendeurs accessibles.

---

## 8. Design / charte (refonte 28 juin)

Le site est passé d'une **base crème/chaude** à une **base blanche froide**, pilotée par `THEMES` :
- Pages & surfaces `#FFFFFF` · sections `#F6F8FA` · bordures `#E6E9EE` · texte `#16181B`.
- **Accent par défaut : bleu net `#2563EB`** (thème « azure » → label « Bleu »). Les 3 accents restent
  commutables : Bleu `#2563EB` · Terracotta `#C2613D` · Sauge `#4F7C5A`.
- Tout passe par les variables CSS (`--bg`, `--surface`, `--section`, `--line`, `--ink`, `--accent`…),
  donc un changement de palette = ~3 lignes dans `THEMES`.

**Ergonomie ajoutée :**
- **Barre d'action collante** en bas du configurateur (`position:fixed`) : produit + dimensions à
  gauche, total TTC + bouton « Ajouter au panier » à droite, toujours visibles. `padding-bottom:120px`
  sur le `<main>` du config pour libérer l'espace.
- Fil d'Ariane du configurateur enrichi (`Accueil / Volets roulants (ou Fenêtres) / Configurateur`).
- **Configurateur en 4 étapes** avec stepper numéroté + barre de progression. État `S.step`,
  fonctions `setStep/nextStep/prevStep` (reset à 0 dans `pickProduct`/`setProduct`). Étapes :
  Dimensions → Pose/Ouverture → Motorisation/Matériau (gamme/profilé inclus si solaire/PVC) →
  Finitions. Boutons « ← Précédent » / « Suivant → » (dernière étape : « Ajouter au panier »).
  Sections découpées en variables `secDims/secPose/secMotor/secFinVolet/secOuverture/secMateriau/secFinFenetre`.
- **Mini-panier déroulant** au survol du bouton Panier (`miniCart()`, classes `.cart-wrap`/`.cart-pop`,
  pont `::before` anti-perte de survol). Masqué ≤820px (pas de survol tactile → clic = page panier).

**Responsive mobile (✅ fait) :**
- Bloc de media queries dans le `<style>` unique. Astuce : les grilles étant en styles *inline*,
  elles sont ciblées par **sélecteurs d'attribut** `[style*="grid-template-columns:…"]` + `!important`
  (évite d'éditer ~18 conteneurs). Breakpoints : `≤1024px` (grilles 4→2 col), `≤820px` (tout en
  1 col, panneau config dé-stické), `≤560px` (grilles 4→1 col).
- **Menu hamburger** : état `S.navOpen`, `toggleNav()`, fermé par `go()`/`pickProduct()`. Header
  desktop (nav, pastilles thème `.hdr-dots`, téléphone `.hdr-phone`) masqué ≤820px au profit du
  burger `.hdr-burger` + panneau `.hdr-mobile`. `html,body{overflow-x:hidden}` en filet de sécurité.

---

## 9. Architecture du code (`index.html`)

- **CONSTANTS** : `THEMES` (3 thèmes), `COL`, `BUBENDORFF`, `KOMMERLING`, `PIECES`
- **STATE** : objet `S` (écran, produit, configs volet/fenetre, panier, paiement, `client`,
  `cb` rappel)
- **Écrans** : `home`, `config`, `pieces`, `cart`, `payment`, `confirm`, `callback`, `aide`, `livraison` (routés dans `_render`)
- **Livraison & frais de port / délais** (`totals()`) : frais calculés selon le contenu du panier —
  **pièces seules 9 €** (`FRAIS_PIECES`), **1 fenêtre ou 1 volet 49 €** (`FRAIS_1`), **2 menuiseries ou
  plus 109 €** (`FRAIS_PLUS`) ; **livraison offerte ≥ 3000 €** (`LIVRAISON_SEUIL`). Une menuiserie prime
  toujours sur les pièces. **Délais** : fenêtres/volets **6 sem.** (`DELAI_MENUISERIE`), pièces **3 sem.**
  (`DELAI_PIECES`). `totals()` renvoie `livraison, flash, flashActive, canFlash, weeks, menuiserieQty…`.
- **Livraison Flash** (`S.flash`, `toggleFlash()`) : option à l'étape **paiement** (carte toggle, visible
  seulement si `canFlash`, i.e. délai de base > 3 sem.) → délai ramené à **3 sem.** (`DELAI_FLASH`),
  supplément **+20% du sous-total** (`FLASH_PCT`). Reflété dans le récap panier + paiement + confirmation
  (`order.weeks/flash`, `eta` = aujourd'hui + `weeks×7 j`). `S.flash` remis à `false` après commande.
- **Livraison & délais** : écran `livraison` (`renderLivraison`, nav `goLivraison()`) — sous-menu Aide :
  table des frais, cartes des délais, encart Livraison Flash, bandeau « offerte dès 3000 € ». Liens :
  item de menu « Livraison & délais », lien « Livraison & délais » du footer (fil d'Ariane Accueil/Aide).
- **Aide à la mesure** : écran `aide` (`renderAide`, nav `goAide()`) — guide de prise de cotes inspiré de
  **fenetre24.com** (fenêtre rénovation/tunnel/applique, volet roulant, types de pose, « bon à savoir » +
  CTA). **Sans photos ni vidéos** pour l'instant (placeholders à ajouter). Liens vers la page : item de
  menu « Aide à la mesure », « Guide de mesure » du footer, lien « Aide à la mesure » du configurateur.
- **Bulle d'aide configurateur** (« Besoin d'aide ? ») : à **chaque étape** du configurateur, un CTA
  `helpCta` (« Décrire ma difficulté ») entre le contenu de l'étape et les boutons. Ouvre une **modale**
  `helpModal` (overlay `position:fixed;z-index:80`) : contexte auto (étape / produit / dimensions),
  champ e-mail + message, validation (message obligatoire), confirmation « Demande envoyée ». État
  `S.help{open,sent,error,email,msg}` ; `openHelp/closeHelp/setHelp(sans render→focus)/sendHelp`.
  `sendHelp()` compose un **`mailto:` vers `CONTACT_EMAIL`** (sujet + contexte + message + e-mail client)
  et affiche la confirmation. ⚠️ **`CONTACT_EMAIL` est un placeholder** (`contact@mesfenetresvolets.fr`)
  à remplacer par l'adresse de contact réelle quand elle sera fournie. `go()` referme la bulle.
- **Paiement** : avant la CB, bloc **« Vos coordonnées »** (Prénom/Nom/Adresse/CP/Ville/Téléphone/E-mail
  → `S.client`, setters `setCli` sans re-render pour ne pas perdre le focus). Bouton « Payer » toujours
  cliquable ; `onPay()` valide via `payValid()` (= `cliValid()` + CB) et, si invalide, `S.checkoutError`
  affiche bandeau + bordures rouges. Les coordonnées sont copiées dans `S.lastOrder.client`.
- **Rappel** : écran `callback` (`renderCallback`) — formulaire Nom (facultatif) + Téléphone → `S.cb`,
  `sendCallback()` valide le n° puis `S.cbSent=true` (page de confirmation).
- **Carrousel** : `savoir` (état) + `savoirApply/Nav/Autoplay/Mount` (réinit à chaque rendu)
- **CSS** : un seul `<style>` (marquee, nav-hover, carrousel circulaire, animations)

---

## 10. Pistes / à valider

- [x] ~~**Responsive mobile**~~ — fait + **passe complète mobile/desktop** (voir §11).
- [x] ~~Configurateur **en étapes numérotées** avec progression~~ — fait (voir §8)
- [x] ~~**Mini-panier déroulant** au survol~~ — fait (voir §8)
- [x] ~~Page **Aide à la mesure**~~ — fait (écran `aide`, inspiré fenetre24.com, voir §9)
- [x] ~~**Mise en ligne** partageable~~ — fait (GitHub Pages, voir §1)
- [ ] **Paiement sécurisé réel** — en cours de décision (voir §11 et audit §12). Prérequis : choix hébergeur
      (statique vs serveur qui exécute du code), choix prestataire (Stripe recommandé), remplacer le **formulaire
      CB factice** par une page/widget hébergé (Stripe Checkout/Elements). En attendant : **mode démo actif**
      (`DEMO=true`, bandeau + CB désactivée, voir §12).
- [x] ~~**Pages légales** CGV / Mentions / RGPD~~ — **rédigées en version préparatoire** (8 juil., voir §12) ;
      reste à renseigner les champs `[À compléter]` (SIRET, raison sociale, médiateur…) et faire relire.
- [ ] **Domaine `mesfenetresvolets.fr` à enregistrer** (~10 €/an) — priorité audit : tant qu'il n'est pas déposé,
      n'importe qui peut l'acheter et recevoir les e-mails `contact@`.
- [ ] **Formulaires « rappel » / « aide » à brancher** (rien n'est transmis actuellement) — Netlify Forms ou backend.
- [ ] **Photos / schémas / vidéos** de la page Aide à la mesure (volontairement absents pour l'instant)
- [ ] Logo **Caloriver** dédié (détourer le GIF, ou fournir un SVG/PNG transparent)
- [ ] Derniers placeholders « photo — » restants (galerie inspiration)
- [ ] Variantes électriques Bubendorff (radio/filaire/CLIMAT+) au catalogue, si souhaité

---

## 11. Journal — session du 5 juillet 2026

### Mise en ligne
- Dépôt Git initialisé et publié sur **GitHub Pages** (voir §1 pour l'URL et la procédure de mise à jour).
- Convention **auto-push** après chaque modif du site.

### Passe responsive (mobile + desktop)
Bug de fond identifié : plusieurs grilles à **colonne latérale de largeur fixe** n'étaient pas couvertes par
les media queries → le contenu partait hors écran sur mobile (masqué par `overflow-x:hidden`, donc invisible
à l'audit automatique). Corrigé dans le bloc `@media` :
- **Panier** (`1fr 350px`) et **Paiement** (`1fr 330px`) → récap repasse en **pleine largeur** sous le contenu.
- **Footer** : `1.4fr 1fr 1fr 1fr` → **2 col ≤820px**, puis **1 col ≤560px** (sinon l'e-mail
  `contact@mesfenetresvolets.fr` était **coupé**) ; ajout `overflow-wrap:anywhere` sur l'e-mail.
- **Galerie photos** Kömmerling (`repeat(6,1fr)`) → 3 col (≤1024px) / 2 col (≤560px).
- **Barre d'action** collante du configurateur : padding réduit (`12px 32px` → `11px 14px`) ≤560px.
- Vitrines produit (`1.05fr .95fr`) et galerie inspiration (`2fr 1fr 1fr`) repassent en 1 col.
- Vérifié : **aucun débordement horizontal** sur les 8 écrans à 375px ; layout desktop intact à 1280px.

### Footer
- **Numéro de téléphone retiré** (rubrique Contact = e-mail seul).
- Liens **Produits** rendus actifs : Volets roulants → `pickProduct('volet')`, Fenêtre PVC →
  `pickProduct('fenetre')`, Pièces détachées → `goPieces()` (avec `cursor:pointer`).
- Légende carrousel usine : « Usine en **Meurthe-et-Moselle (Grand-Est)** ».

### Retrait des émojis décoratifs
Tous les **émojis couleur** supprimés (⚙️ 🧰 📱 🔩 📷 🔒 ⚡ 📦 🎁 🪟) : icônes catégories pièces
(`PIECES[*].icon=''`), cartes d'accueil « deux façons de commander », bouton configurateur, notes
panier/paiement/confirmation, page Livraison. **Conservés** (ce ne sont pas des émojis) : flèches → ←,
coches ✓, croix ✕, et les **glyphes typographiques monochromes** du design (⬡ ⌂ ✦ ▣ ▤ ▥ ; le 🔒 du bandeau
« Paiement sécurisé » remplacé par **◆** ; badges délais Livraison par **▦ ▥ ▸**). Reste un `⚠️` dans un
**commentaire de code** (non affiché).

### Étude paiement sécurisé (décision en cours)
- Le formulaire CB actuel est **factice** ; un vrai paiement impose un prestataire agréé (PCI-DSS) + 3-D Secure (DSP2).
- Contrainte : prix **sur-mesure calculés côté navigateur** → à **revalider** (lien/facture, ou côté serveur).
- **2 routes** : **A** = devis → **lien/facture** (sans serveur, reste sur Pages) ✅ pour démarrer ;
  **B** = **Checkout intégré** (paiement auto instantané) → nécessite un backend / hébergeur qui exécute du code.
- **Si le futur hébergeur exécute du code** (PHP le plus probable sur mutualisé FR) → Route B possible :
  recalcul du prix côté serveur, clé secrète cachée, webhook de confirmation.
- Prestataires comparés : **Stripe** (recommandé), PayPal, Mollie, PayPlug (FR), Stancer (FR), SumUp,
  Lyra/Systempay (banque), Lemonway (marketplace, surdimensionné).
- Livrable : **`Paiement-securise-comparatif.pdf`** (racine, exclu du dépôt) — comparatif complet + reco.
- **À faire avant d'encaisser** : pages légales **CGV / Mentions légales / RGPD** (liens footer vides), et
  remplacer le formulaire CB par Stripe Checkout/Elements.

---

## 12. Journal — session du 8 juillet 2026 (audit + correctifs)

### Audit sécurité & cohérence (contrôle complet du site)
Constats principaux : **faux paiement sur site public** (textes mensongers « informations chiffrées »,
« e-mail envoyé », « bon de commande transmis »), **formulaires qui ne transmettent rien** (rappel, aide),
**Google Fonts** = transfert d'IP à Google (RGPD), **pages légales absentes**, self-XSS mineur sur
`payName`, ventilation HT/TVA du panier fausse (livraison comptée 2×), badge panier ≠ quantités,
« 260 €/m² » obsolète, sens d'ouverture proposé sur châssis fixe, SEO nul (pas de meta/OG).
Propositions hébergement (Netlify/Cloudflare Pages ou mutualisé FR o2switch/OVH) et paiement
(Route A liens Stripe → Route B Checkout + fonction serverless) détaillées en conversation + PDF §11.

### Correctifs appliqués (lot 1)
- **Mode démonstration** : constante **`DEMO = true`** (en tête des CONSTANTS, à passer à `false` au
  lancement réel). Effets : **bandeau ambre** site-wide au-dessus du header (« Site de démonstration —
  les commandes et paiements ne sont pas encore actifs. »), **champs CB `disabled`** (+ carte à
  `opacity:.55`), encart honnête ambre (#FEF3C7) à la place de « Vos informations sont chiffrées… »,
  bouton **« Paiement bientôt disponible »** désactivé (l'écran `confirm` devient inatteignable en démo).
  Les coordonnées client restent saisissables ; `autocomplete="off"` ajouté aux champs CB.
- **Polices auto-hébergées (RGPD)** : les 3 `<link>` Google Fonts remplacés par 4 `@font-face` locaux →
  `assets/fonts/*.woff2` (Schibsted Grotesk 400–800, Hanken Grotesk 400–700, variables, latin +
  latin-ext, ~120 Ko). **Zéro requête externe** vérifiée au runtime (performance API).
- **SEO** : `<meta name="description">` + Open Graph (title/description/type/url/image/locale).
- **Panier — ventilation corrigée** : « Sous-total TTC » (= articles), Livraison, Flash éventuel, Délai,
  **« dont TVA 20% »** (au lieu de « Sous-total HT » = total/1,2 livraison incluse + ligne Livraison en
  double). Sous-total + Livraison + Flash = Total ✓ (harmonisé avec la page Paiement).
- **Badge panier = somme des quantités** (`cartQty`) dans le header, le menu fluide et « X article(s) »
  de la page panier (avant : nombre de lignes ; incohérent avec le mini-panier).
- **`payName` échappé** (`&quot;`) à la réinjection — self-XSS clos, homogène avec les autres champs.
- **« dès 275 €/m² »** sur la carte Matériau PVC du configurateur (au lieu de « 260 €/m² », reliquat).
- **Sens d'ouverture masqué si châssis fixe** (grille 1 col dans les Finitions fenêtre).
- RECAP corrigé : §5 (PVC uniquement, plus de « Bois »), §6 (3 photos usine, ligne `assets/fonts/`).

### Vérifié au runtime (npx serve + navigateur)
Zéro erreur console · zéro 404 · zéro requête externe · polices locales chargées (`document.fonts.check` ✓)
· bandeau démo affiché · 4 champs CB désactivés, 7 champs client actifs · bouton payer désactivé ·
panier : 1 566 € + 109 € = 1 675 € ✓, badge = 3 = « 3 article(s) » ✓ · « dès 275 €/m² » ✓ · sens masqué ✓.

### Reste à faire (issu de l'audit, non traité dans ce lot)
- Enregistrer le **domaine** + boîte `contact@` (remplacer `CONTACT_EMAIL`).
- **Pages légales** CGV (avec exclusion rétractation sur-mesure, art. L221-28 3°) / Mentions / RGPD.
- Brancher les **formulaires** (Netlify Forms recommandé) — rappel & aide.
- Année dans la date de livraison estimée (`eta`) ; clarifier « Garantie 2 ans » vs garanties fabricant ;
  encadrer l'usage des logos « marques partenaires » (Siegenia, Verrissima) avant commercialisation.

### Pages légales (8 juillet 2026, suite de session)
- **`CONTACT_EMAIL` officialisé** : `contact@mesfenetresvolets.fr` (confirmé par Kevin — plus un placeholder).
- **3 nouveaux écrans** routés dans `_render` : `mentions` (`renderMentions`, 5 sections), `cgv`
  (`renderCGV`, 12 articles), `confidentialite` (`renderConfidentialite`, 7 sections). Nav :
  `goMentions()/goCGV()/goConfidentialite()`.
- **Helpers** : `legalShell` (fil d'Ariane + en-tête + bandeau « Document préparatoire » + boutons croisés),
  `legalSec` (carte de section), `phFill(t)` (balise ambre `[À compléter : …]` pour les infos manquantes :
  raison sociale, SIRET, capital, TVA, directeur de publication, médiateur, prestataire de paiement).
  Constante `LEGAL_MAJ` (date de dernière mise à jour affichée).
- **Contenu clé** : hébergeur GitHub Inc. (à maj si changement) · marques tierces citées à titre informatif ·
  CGV avec frais/délais **dynamiques** (constantes `FRAIS_*`, `DELAI_*`, `FLASH_PCT` → toujours en phase avec
  le site) · **rétractation** : exclue pour le sur-mesure (L221-28 3°), 14 jours pour les pièces détachées ·
  encadré garantie légale de conformité · médiation L612-1 + plateforme ODR · RGPD : bases légales, durées
  (3 ans prospects / 10 ans comptable), droits + CNIL, **aucun cookie**, note de transparence « démo :
  rien n'est transmis ni stocké ».
- **Liens d'accès** : footer bas de page (Mentions légales · CGV · Confidentialité, cliquables), note RGPD
  sous le formulaire de rappel, note RGPD sous « Vos coordonnées » (paiement), « vous acceptez nos CGV »
  sous le bouton payer. Les CGV affichent aussi l'encart « site en démonstration ».
- Vérifié au runtime : 0 erreur console, 3 pages rendues, liens footer actifs, frais/délais dynamiques OK.
- ⚠️ **Avant l'ouverture des ventes** : renseigner tous les `[À compléter]`, désigner un médiateur de la
  consommation, faire relire par un professionnel du droit.

### Configurateur fenêtre — étape 3 simplifiée (8 juillet 2026, suite)
- **Sélecteur « Matériau » supprimé** (sur demande) : carte unique « PVC Kömmerling · dès 275 €/m² »
  redondante avec l'encart Profilé juste dessous. `secMateriau` ne rend plus que le bloc
  **« Profilé Kömmerling 76 »** (toujours conditionné à `matiere==='pvc'`, valeur par défaut jamais
  modifiée puisqu'il n'y a plus d'UI pour en changer).
- **Étape renommée « Matériau » → « Profilé »** dans le stepper fenêtre.
- Le récap latéral conserve sa ligne « Matériau : PVC » (info utile au client).
- Vérifié : 0 erreur console, prix inchangé (739 € pour 1000×1150 par défaut), stepper à jour.
