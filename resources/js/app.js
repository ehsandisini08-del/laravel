

import Alpine from 'alpinejs';
import './confirm-dialog';

window.Alpine = Alpine;
window.initMapPicker = (options) => import('./map-picker').then((module) => module.initMapPicker(options));

Alpine.start();
