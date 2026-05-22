<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Crosshair, MapPinned } from '@lucide/vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet-control-geocoder';
import 'leaflet-control-geocoder/dist/Control.Geocoder.css';

const props = defineProps({
    title: { type: String, default: 'Peta Lokasi' },
    latitude: { type: [Number, String], default: null },
    longitude: { type: [Number, String], default: null },
    radiusKm: { type: [Number, String], default: null },
    address: { type: String, default: '' },
    error: { type: String, default: '' },
    searchPlaceholder: { type: String, default: 'Cari alamat atau wilayah' },
    helper: { type: String, default: 'Klik peta atau gunakan pencarian untuk memilih titik.' },
    markerLabel: { type: String, default: 'Titik terpilih' },
    showRadius: { type: Boolean, default: false },
});

const emit = defineEmits([
    'update:latitude',
    'update:longitude',
    'update:address',
    'address-details',
]);

const mapEl = ref(null);
const selectedAddress = ref(props.address);
let map = null;
let marker = null;
let circle = null;
let geocoder = null;
let geocoderControl = null;

const fallbackCenter = [-6.2, 106.816666];
const hasPoint = computed(() => Number.isFinite(toNumber(props.latitude)) && Number.isFinite(toNumber(props.longitude)));
const coordinateLabel = computed(() => {
    if (!hasPoint.value) {
        return '';
    }

    return `${toNumber(props.latitude).toFixed(6)}, ${toNumber(props.longitude).toFixed(6)}`;
});

function toNumber(value) {
    if (value === null || value === undefined || value === '') {
        return NaN;
    }

    return Number(value);
}

function markerIcon() {
    return L.divIcon({
        className: '',
        html: '<span style="display:block;width:18px;height:18px;border-radius:9999px;background:#2563eb;border:3px solid white;box-shadow:0 2px 10px rgba(15,23,42,.35)"></span>',
        iconSize: [18, 18],
        iconAnchor: [9, 9],
    });
}

function updateLayers(centerMap = false) {
    if (!map || !hasPoint.value) {
        return;
    }

    const latLng = L.latLng(toNumber(props.latitude), toNumber(props.longitude));

    if (!marker) {
        marker = L.marker(latLng, { icon: markerIcon() }).addTo(map);
    } else {
        marker.setLatLng(latLng);
    }

    const radiusMeters = toNumber(props.radiusKm) * 1000;

    if (props.showRadius && Number.isFinite(radiusMeters) && radiusMeters > 0) {
        if (!circle) {
            circle = L.circle(latLng, {
                radius: radiusMeters,
                color: '#2563eb',
                weight: 2,
                fillColor: '#60a5fa',
                fillOpacity: 0.18,
            }).addTo(map);
        } else {
            circle.setLatLng(latLng);
            circle.setRadius(radiusMeters);
        }
    } else if (circle) {
        circle.remove();
        circle = null;
    }

    if (centerMap) {
        map.setView(latLng, Math.max(map.getZoom(), 14));
    }
}

function stripHtml(value) {
    const element = document.createElement('div');
    element.innerHTML = value || '';

    return element.textContent || element.innerText || '';
}

function detailsFromResult(result) {
    const properties = result?.properties || {};
    const address = properties.address || {};
    const label = result?.name || properties.display_name || stripHtml(result?.html) || coordinateLabel.value;

    return {
        formatted_address: label,
        city: address.city || address.town || address.village || address.county || address.state || '',
        district: address.suburb || address.city_district || address.district || address.neighbourhood || '',
        postal_code: address.postcode || '',
    };
}

function applyPoint(latLng, details = null, centerMap = true) {
    emit('update:latitude', Number(latLng.lat.toFixed(8)));
    emit('update:longitude', Number(latLng.lng.toFixed(8)));

    if (details) {
        selectedAddress.value = details.formatted_address;
        emit('update:address', details.formatted_address);
        emit('address-details', details);
    } else {
        selectedAddress.value = `${latLng.lat.toFixed(6)}, ${latLng.lng.toFixed(6)}`;
        emit('update:address', selectedAddress.value);
        emit('address-details', {
            formatted_address: selectedAddress.value,
            city: '',
            district: '',
            postal_code: '',
        });
    }

    nextTick(() => updateLayers(centerMap));
}

function reverseGeocode(latLng) {
    if (!geocoder) {
        applyPoint(latLng);
        return;
    }

    geocoder.reverse(latLng, map.getZoom(), (results) => {
        applyPoint(latLng, results?.[0] ? detailsFromResult(results[0]) : null);
    });
}

function initMap() {
    const center = hasPoint.value ? [toNumber(props.latitude), toNumber(props.longitude)] : fallbackCenter;
    map = L.map(mapEl.value, {
        center,
        zoom: hasPoint.value ? 14 : 12,
        scrollWheelZoom: true,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    geocoder = L.Control.Geocoder.nominatim();
    geocoderControl = L.Control.geocoder({
        defaultMarkGeocode: false,
        placeholder: props.searchPlaceholder,
        geocoder,
    })
        .on('markgeocode', (event) => {
            const result = event.geocode;
            const bounds = result.bbox;

            applyPoint(result.center, detailsFromResult(result), false);

            if (bounds) {
                map.fitBounds(bounds, { maxZoom: 16 });
            } else {
                map.setView(result.center, 16);
            }
        })
        .addTo(map);

    map.on('click', (event) => reverseGeocode(event.latlng));

    updateLayers(true);
    setTimeout(() => map?.invalidateSize(), 120);
}

onMounted(() => {
    selectedAddress.value = props.address;
    initMap();
});

onBeforeUnmount(() => {
    if (geocoderControl) {
        geocoderControl.remove();
    }

    if (map) {
        map.remove();
    }
});

watch(() => props.address, (value) => {
    selectedAddress.value = value;
});

watch(() => [props.latitude, props.longitude, props.radiusKm], () => updateLayers(), { deep: true });
</script>

<template>
    <section class="overflow-hidden rounded-md border border-neutral-border bg-white">
        <div class="flex flex-col gap-3 border-b border-neutral-border px-4 py-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="flex items-center gap-2 text-sm font-semibold text-neutral-text">
                    <MapPinned class="h-4 w-4 text-info" />
                    <span>{{ title }}</span>
                </div>
                <p class="mt-1 text-xs text-neutral-muted">{{ helper }}</p>
            </div>
            <slot name="actions" />
        </div>

        <div class="grid gap-3 p-4">
            <div ref="mapEl" class="h-80 min-h-80 w-full overflow-hidden rounded-md border border-neutral-border bg-neutral-light"></div>

            <div class="grid gap-2 rounded-md bg-neutral-light p-3 text-sm">
                <div class="flex items-start gap-2">
                    <Crosshair class="mt-0.5 h-4 w-4 shrink-0 text-info" />
                    <div>
                        <p class="font-semibold text-neutral-text">{{ selectedAddress || address || markerLabel }}</p>
                        <p v-if="coordinateLabel" class="mt-1 text-xs text-neutral-muted">{{ coordinateLabel }}</p>
                    </div>
                </div>
                <p v-if="error" class="text-sm text-danger">{{ error }}</p>
            </div>
        </div>
    </section>
</template>
