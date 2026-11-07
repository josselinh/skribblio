import $ from 'jquery';
import axios from 'axios';
import _ from 'lodash';
import 'bootstrap';

window.$ = window.jQuery = $;
window.axios = axios;
window._ = _;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
