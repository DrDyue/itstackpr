import axios from 'axios';
import Alpine from 'alpinejs';

// Bootstrap fails sagatavo bibliotēkas, kuras pārējie moduļi sagaida uz `window`.
// Laravel Blade skati un Alpine komponentes izmanto šīs globālās atsauces bez papildu importiem.
window.axios = axios;
window.Alpine = Alpine;

// Visi Axios pieprasījumi pēc noklusējuma tiek marķēti kā AJAX,
// lai Laravel pusē var atšķirt pilnas lapas pieprasījumu no fragmenta/API pieprasījuma.
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.content;

if (csrfToken) {
    // CSRF tokenu ieliekam vienreiz bootstrap laikā, lai POST/PATCH/DELETE darbībām
    // nav jāatkārto šī loģika katrā modulī atsevišķi.
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
}
