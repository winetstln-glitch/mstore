import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Set CSRF Token header to avoid 419 (Page Expired) on POST/PUT/DELETE
const tokenMeta = document.querySelector('meta[name=\"csrf-token\"]');
if (tokenMeta && tokenMeta.content) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = tokenMeta.content;
}
