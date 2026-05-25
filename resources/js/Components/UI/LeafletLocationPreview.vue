<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    latitude: { type: [Number, String], default: null },
    longitude: { type: [Number, String], default: null },
    address: { type: String, default: '' },
    markerLabel: { type: String, default: 'Titik lokasi' },
});

const mapEl = ref(null);
let map = null;
let marker = null;

const hasPoint = computed(() => Number.isFinite(toNumber(props.latitude)) && Number.isFinite(toNumber(props.longitude)));
const coordinateLabel = computed(() => {
    if (! hasPoint.value) {
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

function updateMarker(centerMap = false) {
    if (! map || ! hasPoint.value) {
        return;
    }

    const latLng = L.latLng(toNumber(props.latitude), toNumber(props.longitude));

    if (! marker) {
        marker = L.marker(latLng, {
            icon: markerIcon(),
        }).addTo(map);
    } else {
        marker.setLatLng(latLng);
    }

    marker.bindPopup(props.address || props.markerLabel);

    if (centerMap) {
        map.setView(latLng, 16);
    }
}

function initMap() {
    if (! mapEl.value || ! hasPoint.value || map) {
        return;
    }

    const center = [toNumber(props.latitude), toNumber(props.longitude)];

    map = L.map(mapEl.value, {
        center,
        zoom: 16,
        scrollWheelZoom: true,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    updateMarker();

    window.setTimeout(() => {
        map?.invalidateSize();
        updateMarker(true);
    }, 150);
}

onMounted(() => {
    nextTick(initMap);
});

onBeforeUnmount(() => {
    map?.remove();
    map = null;
    marker = null;
});

watch(() => [props.latitude, props.longitude, props.address], () => {
    if (! map) {
        nextTick(initMap);

        return;
    }

    updateMarker(true);
});
</script>

<template>
    <div>
        <div v-if="hasPoint" ref="mapEl" class="h-[420px] w-full rounded-md border border-neutral-border" />
        <div v-else class="grid h-64 place-items-center rounded-md border border-neutral-border bg-neutral-light text-sm text-neutral-muted">
            Titik lokasi belum tersedia.
        </div>

        <div v-if="hasPoint" class="mt-3 grid gap-1 text-sm">
            <p v-if="address" class="font-medium text-neutral-text">{{ address }}</p>
            <p class="text-xs text-neutral-muted">{{ coordinateLabel }}</p>
        </div>
    </div>
</template>

<style scoped>
:deep(.leaflet-container) {
    z-index: 0;
}

:deep(.leaflet-pane) {
    z-index: 0;
}

:deep(.leaflet-top),
:deep(.leaflet-bottom) {
    z-index: 1;
}
</style>
