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
        content.className = 'basemap-popup';
        content.innerHTML = '<strong>Fond de carte</strong>';
        [
          ['osm', 'OpenStreetMap', osm],
          ['aerial', 'Photo aérienne', aerial],
        ].forEach(([value, label, layer]) => {
          const labelElement = document.createElement('label');
          const input = document.createElement('input');
          input.type = 'radio'; input.name = group; input.value = value; input.checked = selected === layer;
          input.addEventListener('change', () => {
            if (!input.checked) return;
            if (selected && map.hasLayer(selected)) map.removeLayer(selected);
            selected = layer;
            map.addLayer(selected);
            map.closePopup();
          });
          labelElement.append(input, document.createTextNode(' ' + label));
          content.append(labelElement);
        });
        L.popup({ closeButton: true, autoClose: true, className: 'basemap-leaflet-popup' }).setLatLng(map.getCenter()).setContent(content).openOn(map);
      };

      control.onAdd = () => {
        const button = L.DomUtil.create('button', 'leaflet-bar basemap-control-button');
        button.type = 'button'; button.title = 'Choisir le fond de carte'; button.setAttribute('aria-label', 'Choisir le fond de carte'); button.textContent = '🗺️';
        L.DomEvent.disableClickPropagation(button); L.DomEvent.on(button, 'click', openChooser);
        return button;
      };
      control.addTo(map);
    },
  };
})();
