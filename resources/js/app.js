

import Alpine from 'alpinejs';
import './confirm-dialog';

window.Alpine = Alpine;
window.initMapPicker = (options) => import('./map-picker').then((module) => module.initMapPicker(options));
window.initInfrastrukturMap = (options) => import('./infrastruktur-map').then((module) => module.initInfrastrukturMap(options));

Alpine.start();
