(() => {
  'use strict';

  window.PlantonsMapLayerControl = {
    add(map, initialLayer = null) {
      if (!window.L || map._plantonsBaseMapControl) return;
      map._plantonsBaseMapControl = true;
      const osm = initialLayer || Object.values(map._layers).find((layer) => layer instanceof L.TileLayer);
      const aerial = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19,
        attribution: 'Tiles © Esri',
      });
      let selected = osm;
      const control = L.control({ position: 'topright' });

      const openChooser = () => {
        const content = document.createElement('form');
        const group = `plantons-basemap-${map._leaflet_id}`;
        const zoom = Math.min(Math.max(Math.round(map.getZoom()), 0), 19);
        const point = map.project(map.getCenter(), zoom);
        const tileX = Math.floor(point.x / 256);
        const tileY = Math.floor(point.y / 256);
        content.className = 'basemap-popup';
        content.innerHTML = '<strong>Fond de carte</strong>';
        [
          ['osm', 'OpenStreetMap', osm, `https://tile.openstreetmap.org/${zoom}/${tileX}/${tileY}.png`],
          ['aerial', 'Photo aérienne', aerial, `https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/${zoom}/${tileY}/${tileX}`],
        ].forEach(([value, label, layer, previewUrl]) => {
          const labelElement = document.createElement('label');
          const input = document.createElement('input');
          const preview = document.createElement('span');
          const title = document.createElement('span');
          labelElement.className = 'basemap-tile';
          preview.className = 'basemap-tile-preview';
          preview.style.backgroundImage = `url("${previewUrl}")`;
          title.className = 'basemap-tile-title'; title.textContent = label;
          input.type = 'radio'; input.name = group; input.value = value; input.checked = selected === layer;
          input.addEventListener('change', () => {
            if (!input.checked) return;
            if (selected && map.hasLayer(selected)) map.removeLayer(selected);
            selected = layer;
            map.addLayer(selected);
            map.closePopup();
          });
          preview.append(title);
          labelElement.append(input, preview);
          content.append(labelElement);
        });
        L.popup({ closeButton: true, autoClose: true, className: 'basemap-leaflet-popup' }).setLatLng(map.getCenter()).setContent(content).openOn(map);
      };

      control.onAdd = () => {
        const button = L.DomUtil.create('button', 'leaflet-bar basemap-control-button');
        button.type = 'button'; button.title = 'Choisir parmi 2 fonds de carte'; button.setAttribute('aria-label', 'Choisir parmi 2 fonds de carte');
        button.innerHTML = '<svg class="basemap-control-layers" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7.5 12 3l9 4.5L12 12 3 7.5Z"/><path d="m5.5 12 6.5 3.3 6.5-3.3"/><path d="m5.5 16.2 6.5 3.3 6.5-3.3"/></svg><span class="basemap-control-count" aria-hidden="true">2</span>';
        L.DomEvent.disableClickPropagation(button); L.DomEvent.on(button, 'click', openChooser);
        return button;
      };
      control.addTo(map);
    },
  };
})();
