

import Alpine from 'alpinejs';
import 'leaflet/dist/leaflet.css';
import './confirm-dialog';
import { initMapPicker } from './map-picker';

window.Alpine = Alpine;
window.initMapPicker = initMapPicker;

Alpine.start();
