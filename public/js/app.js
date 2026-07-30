(() => {
  'use strict';
  const config = window.PlanArbreConfig;
  const form = document.querySelector('#proposal-form');
  const locationOutput = document.querySelector('#selected-location');
  const message = document.querySelector('#form-message');
  const toast = document.querySelector('#toast');
  const map = L.map('map', { scrollWheelZoom: false }).setView(config.center, config.zoom);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
  let marker;
  let territory;
  let municipalities;
  let toastTimer;
  let locationRequest = 0;

  const setMessage = (text, kind = '') => { message.textContent = text; message.className = `form-message ${kind}`; };
  const showToast = (text, kind = '') => { clearTimeout(toastTimer); toast.textContent = text; toast.className = `toast ${kind}`; toast.hidden = false; toastTimer = window.setTimeout(() => { toast.hidden = true; }, 5000); };
  const markerDetails = (status = 'a_valider') => {
    if (['refusee', 'rejetee'].includes(status)) return { className: 'proposal-marker--rejected', icon: '●' };
    if (status === 'validee') return { className: 'proposal-marker--validated', icon: '●' };
    if (['arbre_plante', 'realisee'].includes(status)) return { className: 'proposal-marker--planted', icon: '🌳' };
    return { className: '', icon: '●' };
  };
  const proposalIcon = (status) => { const details = markerDetails(status); return L.divIcon({ className: '', html: `<span class="proposal-marker ${details.className}" aria-hidden="true">${details.icon}</span>`, iconSize: [42, 42], iconAnchor: [21, 21] }); };
  const delegatedMunicipality = (longitude, latitude) => municipalities?.features?.find((feature) => window.turf && turf.booleanPointInPolygon(turf.point([longitude, latitude]), feature))?.properties?.nom || '';
  const shortAddress = (place) => {
    const address = place.address || {};
    const street = [address.house_number, address.road || address.pedestrian || address.footway].filter(Boolean).join(' ');
    return street || address.amenity || address.building || address.hamlet || (place.display_name || '').split(',')[0] || 'adresse non trouvée';
  };
  const describeLocation = async (latitude, longitude, knownAddress = '') => {
    const request = ++locationRequest;
    let address = knownAddress;
    const municipality = delegatedMunicipality(longitude, latitude);
    locationOutput.value = `${latitude.toFixed(6)}, ${longitude.toFixed(6)} — recherche de l’adresse… — ${municipality || 'commune déléguée non identifiée'}`;
    if (!address) {
      try { const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&lat=${latitude}&lon=${longitude}`, { headers: { Accept: 'application/json' } }); const place = await response.json(); address = shortAddress(place); } catch { address = ''; }
    }
    if (request !== locationRequest) return;
    document.querySelector('#selected-address').value = address;
    locationOutput.value = `${latitude.toFixed(6)}, ${longitude.toFixed(6)} — ${address || 'adresse non trouvée'} — ${municipality || 'commune déléguée non identifiée'}`;
  };
  const setPosition = (latitude, longitude, address = '') => {
    const latLng = [latitude, longitude];
    if (territory && window.turf && !territory.features.some((feature) => turf.booleanPointInPolygon(turf.point([longitude, latitude]), feature))) {
      setMessage('Ce point est situé hors du territoire autorisé.', 'error');
      return;
    }
    marker ? marker.setLatLng(latLng) : (marker = L.marker(latLng, { icon: proposalIcon('a_valider') }).addTo(map));
    map.setView(latLng, Math.max(map.getZoom(), 16));
    document.querySelector('#latitude').value = latitude;
    document.querySelector('#longitude').value = longitude;
    describeLocation(latitude, longitude, address);
    setMessage('');
  };

  fetch(config.territoryUrl).then((response) => response.ok ? response.json() : null).then((geojson) => {
    if (!geojson || !geojson.features?.length) return;
    territory = geojson;
    const layer = L.geoJSON(geojson, { style: { color: '#1f6b3b', weight: 2, fillOpacity: .08 } }).addTo(map);
    map.fitBounds(layer.getBounds(), { padding: [16, 16], maxZoom: config.zoom });
  }).catch(() => setMessage('La limite du territoire n’est pas disponible pour le moment.', 'error'));
  fetch(config.municipalitiesUrl).then((response) => response.ok ? response.json() : null).then((geojson) => { municipalities = geojson; });
  fetch(config.proposalsUrl).then((response) => response.ok ? response.json() : null).then((geojson) => {
    if (!geojson?.features?.length) return;
    L.geoJSON(geojson, { pointToLayer: (feature, latLng) => L.marker(latLng, { icon: proposalIcon(feature.properties?.status) }), onEachFeature: (feature, layer) => { const properties = feature.properties || {}; const objectives = (properties.objectives || []).map((objective) => config.objectives[objective]?.label || objective).join(', '); layer.bindPopup(`<strong>${properties.species || 'Proposition'}</strong><br>${objectives || 'Objectifs non renseignés'}`); } }).addTo(map);
  });

  map.on('click', (event) => setPosition(event.latlng.lat, event.latlng.lng));
  document.querySelector('#locate-me').addEventListener('click', () => {
    if (!navigator.geolocation) return setMessage('La géolocalisation n’est pas disponible sur cet appareil.', 'error');
    navigator.geolocation.getCurrentPosition((position) => setPosition(position.coords.latitude, position.coords.longitude), () => setMessage('Votre position n’a pas pu être obtenue.', 'error'), { enableHighAccuracy: true, timeout: 10000 });
  });
  document.querySelector('#address-search').addEventListener('submit', async (event) => {
    event.preventDefault(); const query = document.querySelector('#address').value.trim(); if (query.length < 3) return;
    const results = document.querySelector('#address-results'); results.textContent = 'Recherche en cours…';
    try { const response = await fetch(`https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=5&q=${encodeURIComponent(query)}`, { headers: { Accept: 'application/json' } }); const places = await response.json(); results.replaceChildren(...places.map((place) => { const button = document.createElement('button'); button.type = 'button'; button.textContent = place.display_name; button.onclick = () => { setPosition(Number(place.lat), Number(place.lon), shortAddress(place)); results.textContent = ''; }; return button; })); } catch { results.textContent = 'La recherche d’adresse est indisponible.'; }
  });
  document.querySelector('#photos').addEventListener('change', (event) => {
    const files = [...event.target.files]; const previews = document.querySelector('#photo-previews'); previews.textContent = '';
    if (files.length > config.maxPhotos) { event.target.value = ''; setMessage(`Vous pouvez sélectionner ${config.maxPhotos} photos maximum.`, 'error'); return; }
    for (const file of files) { if (file.size > 1048576) { event.target.value = ''; previews.textContent = ''; setMessage('Chaque photo est limitée à 1 Mo.', 'error'); return; } const image = document.createElement('img'); image.src = URL.createObjectURL(file); image.alt = `Aperçu de ${file.name}`; previews.append(image); }
  });
  form.querySelectorAll('input[name="objectives[]"]').forEach((input) => input.addEventListener('change', () => {
    const selected = form.querySelectorAll('input[name="objectives[]"]:checked');
    if (selected.length > config.maxObjectives) { input.checked = false; showToast(`Vous pouvez sélectionner ${config.maxObjectives} objectifs maximum.`, 'error'); }
  }));
  form.addEventListener('submit', async (event) => {
    event.preventDefault(); if (!form.latitude.value || !form.longitude.value) return setMessage('Choisissez l’emplacement de l’arbre sur la carte.', 'error');
    const submit = form.querySelector('[type="submit"]'); submit.disabled = true; setMessage('Envoi de la proposition…');
    try { const response = await fetch(config.proposalUrl, { method: 'POST', body: new FormData(form) }); const data = await response.json(); if (!response.ok) throw new Error(data.message || 'Envoi impossible.'); form.reset(); document.querySelector('#photo-previews').textContent = ''; marker?.remove(); marker = undefined; locationOutput.value = 'Choisissez un point sur la carte.'; setMessage(''); showToast(data.message); } catch (error) { setMessage(error.message, 'error'); } finally { submit.disabled = false; }
  });
})();
