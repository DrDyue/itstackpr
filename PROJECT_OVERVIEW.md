# ITStack — Īss projekta pārskats

## Mērķis
Šī ir IT inventāra uzskaites un pieprasījumu sistēma (Laravel + Vite + Tailwind).

## Ātrā palaišana
1. `composer install`
2. `npm install`
3. `cp .env.example .env`
4. `php artisan key:generate`
5. `php artisan migrate --seed`
6. `npm run build`
7. `php artisan serve`

## Struktūra (konsolidēta)
- `app/` — biznesa loģika (kontrolleri, modeļi, atbalsta klases)
- `resources/views/` — Blade lapas
- `resources/js/app.js` — klienta puses uzvedība
- `resources/css/app.css` + `resources/css/globals.css` — stili
- `routes/web.php` — maršruti

## Piezīmes uzturēšanai
- Front-end iniciācija tiek centralizēta `resources/js/app.js`, lai mazinātu dublēšanos.
- Pēc pieprasījuma helperi (DOM-ready, localStorage, query params, filtri) ir konsolidēti vienā failā `resources/js/app.js`, lai būtu mazāk failu un vienkāršāka pārskatāmība.
- CSS tiek kompilēts ar Vite/Tailwind, tāpēc jāizvairās no nevalidiem `@apply` tokeniem.
- Produkcijas domēns: `https://itstack.gt.tc`.

## AI/drošas izmaiņas (ieteikums nākotnei)
1. Veic mazus, izolētus commitus (1 tēma = 1 commits).
2. Pirms commit vienmēr palaid `npm run build`.
3. Ja izmaiņas ir JS, palaid arī `node --check resources/js/app.js`.
4. Ja tiek mainītas tabulas/filtri, pārbaudi `window.submitAsyncTableForm` un `window.clearAllFilters` plūsmu.
5. Saglabā vienu dokumentu ar uzturēšanas norādēm (šo failu), neizkaisot instrukcijas vairākos MD failos.
