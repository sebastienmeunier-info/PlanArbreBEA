(() => {
  'use strict';
  const config = window.PlanArbreConfig;
  const form = document.querySelector('#proposal-form');
  const locationOutput = document.querySelector('#selected-location');
  const message = document.querySelector('#form-message');
  const map = L.map('map', { scrollWheelZoom: false }).setView(config.center, config.zoom);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
  let marker;
  let territory;

  const setMessage = (text, kind = '') => { message.textContent = text; message.className = `form-message ${kind}`; };
  const setPosition = (latitude, longitude, address = '') => {
    const latLng = [latitude, longitude];
    if (territory && window.turf && !territory.features.some((feature) => turf.booleanPointInPolygon(turf.point([longitude, latitude]), feature))) {
      setMessage('Ce point est situé hors du territoire autorisé.', 'error');
      return;
    }
    marker ? marker.setLatLng(latLng) : (marker = L.marker(latLng).addTo(map));
    map.setView(latLng, Math.max(map.getZoom(), 16));
    document.querySelector('#latitude').value = latitude;
    document.querySelector('#longitude').value = longitude;
    document.querySelector('#selected-address').value = address;
    locationOutput.value = `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
    setMessage('');
  };

  fetch(config.territoryUrl).then((response) => response.ok ? response.json() : null).then((geojson) => {
    if (!geojson || !geojson.features?.length) return;
    territory = geojson;
    const layer = L.geoJSON(geojson, { style: { color: '#1f6b3b', weight: 2, fillOpacity: .08 } }).addTo(map);
    map.fitBounds(layer.getBounds(), { padding: [16, 16], maxZoom: config.zoom });
  }).catch(() => setMessage('La limite du territoire n’est pas disponible pour le moment.', 'error'));

  map.on('click', (event) => setPosition(event.latlng.lat, event.latlng.lng));
  document.querySelector('#locate-me').addEventListener('click', () => {
    if (!navigator.geolocation) return setMessage('La géolocalisation n’est pas disponible sur cet appareil.', 'error');
    navigator.geolocation.getCurrentPosition((position) => setPosition(position.coords.latitude, position.coords.longitude), () => setMessage('Votre position n’a pas pu être obtenue.', 'error'), { enableHighAccuracy: true, timeout: 10000 });
  });
  document.querySelector('#address-search').addEventListener('submit', async (event) => {
    event.preventDefault(); const query = document.querySelector('#address').value.trim(); if (query.length < 3) return;
    const results = document.querySelector('#address-results'); results.textContent = 'Recherche en cours…';
    try { const response = await fetch(`https://nominatim.openstreetmap.org/search?format=jsonv2&limit=5&q=${encodeURIComponent(query)}`, { headers: { Accept: 'application/json' } }); const places = await response.json(); results.replaceChildren(...places.map((place) => { const button = document.createElement('button'); button.type = 'button'; button.textContent = place.display_name; button.onclick = () => { setPosition(Number(place.lat), Number(place.lon), place.display_name); results.textContent = ''; }; return button; })); } catch { results.textContent = 'La recherche d’adresse est indisponible.'; }
  });
  document.querySelector('#photos').addEventListener('change', (event) => {
    const files = [...event.target.files]; const previews = document.querySelector('#photo-previews'); previews.textContent = '';
    if (files.length > config.maxPhotos) { event.target.value = ''; setMessage(`Vous pouvez sélectionner ${config.maxPhotos} photos maximum.`, 'error'); return; }
    for (const file of files) { if (file.size > 1048576) { event.target.value = ''; previews.textContent = ''; setMessage('Chaque photo est limitée à 1 Mo.', 'error'); return; } const image = document.createElement('img'); image.src = URL.createObjectURL(file); image.alt = `Aperçu de ${file.name}`; previews.append(image); }
  });
  form.addEventListener('submit', async (event) => {
    event.preventDefault(); if (!form.latitude.value || !form.longitude.value) return setMessage('Choisissez l’emplacement de l’arbre sur la carte.', 'error');
    const submit = form.querySelector('[type="submit"]'); submit.disabled = true; setMessage('Envoi de la proposition…');
    try { const response = await fetch(config.proposalUrl, { method: 'POST', body: new FormData(form) }); const data = await response.json(); if (!response.ok) throw new Error(data.message || 'Envoi impossible.'); form.reset(); document.querySelector('#photo-previews').textContent = ''; marker?.remove(); marker = undefined; locationOutput.value = 'Choisissez un point sur la carte.'; setMessage(data.message, 'success'); } catch (error) { setMessage(error.message, 'error'); } finally { submit.disabled = false; }
  });
})();
