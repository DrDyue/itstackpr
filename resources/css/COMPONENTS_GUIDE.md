# CSS Komponenžu Roku Grāmata

## 🎨 Globālo Mainīgo Izmantošana

Visi primārie krāvu, lielumi un attālumi ir centralizēti `globals.css`:

### Mainīgie piekļuvušo CSS failiem:

```css
/* Krāvas */
background: var(--color-primary);
color: var(--color-danger);
border: 1px solid var(--color-slate-200);

/* Spacing */
padding: var(--space-lg);
gap: var(--space-md);
margin: var(--space-xl);

/* Bordes */
border-radius: var(--border-radius-xl);

/* Shadows */
box-shadow: var(--shadow-md);
```

## 🔄 Kā Mainīt Globālus Stilus

### Piemērs: Mainīt primāro krāsu (no zils → sarkans)

**Pirms:**
```css
/* resources/css/globals.css */
--color-primary: #0ea5e9;  /* Zils */
```

**Pēc:**
```css
/* resources/css/globals.css */
--color-primary: #ef4444;  /* Sarkans */
```

**Efekts:** Automātiski mainās VISUR projektā, kur izmanto `.btn-primary`, `.status-primary`, utt.

### Piemērs: Mainīt spacing

```css
/* Palielinājums gap no 12px → 16px */
--space-md: 16px;  /* bija 12px */
```

---

## 🧱 Komponenžu Klases Pieejamums

### Button klases
```html
<button class="btn-base btn-primary">Primārā poga</button>
<button class="btn-base btn-danger">Bīstama poga</button>
<button class="btn-base btn-success">Veiksmīga poga</button>
```

### Card Komponenti
```html
<div class="card-base">
    Satura šeit - automātiski formatēts
</div>
```

### Status Pills
```html
<span class="status-base status-success">Aktīvs</span>
<span class="status-base status-warning">Gaidās</span>
<span class="status-base status-danger">Kļūda</span>
```

---

## 🌙 Dark Tema

Dark tēma ir definēta `:root[data-theme='dark']` selektoros.

Jaunajiem komponentiem pievienot:
```css
:root[data-theme='dark'] .my-component {
    background: var(--color-dark-surface);
    color: var(--color-dark-text);
}
```

---

## ✨ Labās Prakses

✅ **DARĪT:**
- Izmantot `var(--color-*)` vietā izrakstītu hex (#123456)
- Izmantot `var(--space-*)` vietā izrakstītu px (12px)
- Mainīt globals.css primāriem stiliem
- Pievienot dark theme atbalstu

❌ **NEDARĪT:**
- Hardcodēt krāvas komponentos
- Izveidot lokālus mainīgos, kas ir jau globals
- Nepievienot dark theme support

---

## 📊 CSS Failu Struktura

```
resources/css/
  ├── app.css              (MAIN - imports visus)
  ├── globals.css          (mainīgie + base komponenti)
  ├── app.css.backup       (originālais backup)
  └── README.md            (šis fails)
```

---

## 🔧 Kā Mainīt Konkrēta Komponenta Stilus

### Variants 1: Mainīt globals.css (ieteikts)
Mainīt vērtības `.btn-primary { color: ... }` vai jauni mainīgie.

### Variants 2: Override app.css failā
```css
/* app.css */
.my-custom-button {
    @apply btn-base btn-primary rounded-full;
}
```

### Variants 3: Tailwind @apply (ja nepieciešams)
```css
.special-button {
    @apply inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg;
}
```

---

## 📝 Dokumentēšana

Katram komponentam pieraksti:
- Ko dara
- Kur izmanto
- Dark tema atbalsts (jā/nē)
- Paraugu kodulapieza

**Piemērs:**
```
/* Card Component
 * - Balts fons ar borders
 * - Hover efekts (lift)
 * - Responsive spacing
 * - Dark theme: temna fona
 */
.card-base { ... }
```

---

## 🚀 Nākamie Soļi

- [ ] Atsevišķs `.css` fails katrai komponenžu grupai (device-, dash-, form-)
- [ ] CSS mainīgu dokumentācija par ALL krāvām/lielumiem
- [ ] Komponenžu bibliotēka (Storybook-style)
- [ ] Automatizēta testēšana (vizuāla regreska)
