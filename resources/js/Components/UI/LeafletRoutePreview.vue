<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    originLatitude: { type: [Number, String], default: null },
    originLongitude: { type: [Number, String], default: null },
    destinationLatitude: { type: [Number, String], default: null },
    destinationLongitude: { type: [Number, String], default: null },
    routeGeometry: { type: Array, default: () => [] },
    originLabel: { type: String, default: 'Toko' },
    destinationLabel: { type: String, default: 'Alamat pengiriman' },
});

const mapEl = ref(null);
let map = null;
let originMarker = null;
let destinationMarker = null;
let routeLine = null;

const hasRoutePoints = computed(() => originPoint.value !== null && destinationPoint.value !== null);
const originPoint = computed(() => pointFromValues(props.originLatitude, props.originLongitude));
const destinationPoint = computed(() => pointFromValues(props.destinationLatitude, props.destinationLongitude));

const routePoints = computed(() => props.routeGeometry
    .map((point) => {
        if (Array.isArray(point)) {
            return pointFromValues(point[0], point[1]);
        }

        return pointFromValues(point.latitude, point.longitude);
    })
    .filter(Boolean));

function toNumber(value) {
    if (value === null || value === undefined || value === '') {
        return NaN;
    }

    return Number(value);
}

function pointFromValues(latitude, longitude) {
    const lat = toNumber(latitude);
    const lng = toNumber(longitude);

    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        return null;
    }

    return L.latLng(lat, lng);
}

function markerIcon(color) {
    return L.divIcon({
        className: '',
        html: `<span style="display:block;width:18px;height:18px;border-radius:9999px;background:${color};border:3px solid white;box-shadow:0 2px 10px rgba(15,23,42,.35)"></span>`,
        iconSize: [18, 18],
        iconAnchor: [9, 9],
    });
}

function updateRoute(centerMap = false) {
    if (!map || !hasRoutePoints.value) {
        return;
    }

    if (!originMarker) {
        originMarker = L.marker(originPoint.value, {
            icon: markerIcon('#16a34a'),
        }).addTo(map);
    } else {
        originMarker.setLatLng(originPoint.value);
    }

    if (!destinationMarker) {
        destinationMarker = L.marker(destinationPoint.value, {
            icon: markerIcon('#2563eb'),
        }).addTo(map);
    } else {
        destinationMarker.setLatLng(destinationPoint.value);
    }

    originMarker.bindPopup(props.originLabel);
    destinationMarker.bindPopup(props.destinationLabel);

    const linePoints = routePoints.value.length >= 2
        ? routePoints.value
        : [originPoint.value, destinationPoint.value];
    const isRouteGeometry = routePoints.value.length >= 2;

    if (!routeLine) {
        routeLine = L.polyline(linePoints, {
            color: '#2563eb',
            weight: 5,
            opacity: 0.9,
            dashArray: isRouteGeometry ? null : '8 8',
            lineCap: 'round',
            lineJoin: 'round',
        }).addTo(map);
    } else {
        routeLine.setLatLngs(linePoints);
        routeLine.setStyle({ dashArray: isRouteGeometry ? null : '8 8' });
    }

    if (centerMap) {
        const bounds = L.latLngBounds([originPoint.value, destinationPoint.value]);
        linePoints.forEach((point) => bounds.extend(point));
        map.fitBounds(bounds, { padding: [28, 28], maxZoom: 15 });
    }
}

function initMap() {
    if (!mapEl.value || !hasRoutePoints.value || map) {
        return;
    }

    map = L.map(mapEl.value, {
        center: originPoint.value,
        zoom: 13,
        scrollWheelZoom: true,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    updateRoute(true);

    window.setTimeout(() => {
        map?.invalidateSize();
        updateRoute(true);
    }, 150);
}

onMounted(() => {
    nextTick(initMap);
});

onBeforeUnmount(() => {
    map?.remove();
    map = null;
    originMarker = null;
    destinationMarker = null;
    routeLine = null;
});

watch(() => [
    props.originLatitude,
    props.originLongitude,
    props.destinationLatitude,
    props.destinationLongitude,
    props.routeGeometry,
], () => {
    if (!map) {
        nextTick(initMap);

        return;
    }

    updateRoute(true);
}, { deep: true });
</script>

<template>
    <div>
        <div
            v-if="hasRoutePoints"
            ref="mapEl"
            class="h-72 w-full overflow-hidden rounded-md border border-neutral-border bg-neutral-light sm:h-80 xl:h-[320px]"
        />
        <div v-else class="grid h-72 w-full place-items-center rounded-md border border-neutral-border bg-neutral-light text-sm text-neutral-muted sm:h-80 xl:h-[320px]">
            Titik pengiriman belum tersedia.
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
