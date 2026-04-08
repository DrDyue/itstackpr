# CSS Architecture - itstackpr

## Stratēģija

CSS fails ir centralizējusies **modulāra komponenta sistēmā**. Mērķis: noticies un vienkāršs CSS pārmaiņas.

## Kurtūra

```
resources/css/
  ├── app.css              (main, imports viss)
  └── ARHĪVE/
      ├── app-BACKUP.css   (original backup)
```

## CSS Klases Kategorijas

### 1. **Base Styles** (~100 linijas)
- Reset (*, html, body)
- Typography
- Form elements

### 2. **Legacy Auth Pages** (~500 linijas)
- `.auth-*` klases (login/register lapas)
- `.auth-wrapper`, `.auth-container`, `.auth-card`
- `.form-*` klases

### 3. **Modern Components** (~3500 linijas)

#### Device Management
- `.device-*` - device listing, cards, status
- `.device-type-*` - device types modal
- Device photos, transfers

#### Dashboard
- `.dash-*` - dashboard layouts, panels, grids
- `.dash-kpi-*` - KPI cards
- `.dash-table-*` - table styling
- `.dash-activity-*` - activity feed

#### Users & Repairs
- `.user-*` - user management tables/forms
- `.repair-*` - repair workflow
- `.request-*` - request management

#### UI Components
- `.btn-*` - buttons (primary, danger, approve)
- `.status-*` - status pills/badges
- `.metric-*` - metric cards
- `.table-action-*` - dropdown menus

### 4. **Dark Theme** (~2000 linijas)
- `:root[data-theme='dark']` selektori
- Color overrides per component

### 5. **Utilities** (~500 linijas)
- `.surface-*` - card-like surfaces
- `.filter-*` - filter UI
- `.quick-*` - quick filters
- `.searchable-*` - custom select
- `.notification-*` - toasts/notifications
- `.empty-state`, `.page-hero`

## Klases Ieskrūž (NEVAR)

🚫 **Neizbīstamās klases** (neizbloķē):
- `.app-pagination-*` - vecā pagination (nav aktuāla)
- `.app-confirm-*` - vecā dialog sistēma
- `.auth-demo-*` - demo klases

✅ **Draudzīgi noņemamas tikai ar validāciju**:
- Pārbaudīt visās template failos
- CSS regression testsēt
- Git commit ar skaidru pamatojumu

## Optimizācijas Problembētes

| Problēma | Risinājums | Status |
|-----------|-----------|--------|
| CSS fails liels (6800+ linijas) | Modularizācija fails | ⏳ |
| Vecā demo klases (auth-demo-*) | Noņemšana | ⏳ |
| Duplicate @apply direktīves | Konsolidācija | ⏳ |
| Dark theme CSS (2000 linijas) | Opcionalitāte/lazyload | ⏳ |
| Global const duplikācijas | CSS variables | ⏳ |

## Globālie CSS Mainīgie (Izdoties)

```css
/* Primāro krāsu konstantes */
:root {
    --primary: #0ea5e9;
    --primary-dark: #0284c7;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --slate-bg: #0f172a;
    --slate-text: #e2e8f0;
}
```

## Nākamie Soļi

1. ✅ Audīts - klases kategorijas
2. ⏳ Komponentizācija - moduli .css faili
3. ⏳ CSS mainīgu pieimplementēšana
4. ⏳ Vecā demo klases noņemšana
5. ⏳ Dark theme optimizācija

## Izmantošanas Vadlīnijas

Jauniem komponentiem:
- Izmantot jau noteiktas `.dash-*`, `.device-*`, `.user-*` klases
- Pirms jaunas klases, pārbaudīt esošus prefixus
- Dark theme: Pievienot `:root[data-theme='dark']` selektoru

Optimizācijai:
- Noņemot klases: VIENMĒR pārbaudīt grep ar `"class.*ClassName"`
- Validācija: Palaist visas templates bez vizuālām izmaiņām
- Dokumentācija: Atjaunināt šo README
