//
// Everything here is quick and real dirty. As in thrown together quickly. Don't judge it too harshly
//

(function() {
    var map;
    var markers = [];
    var loadedData = {};
    var selector, typeSelector;

    function getFilteredApointments() {
        var selectedDateRange = selector.value;
        if (selectedDateRange) {
            selectedDateRange = selectedDateRange.split(',');
            selectedDateRange[0] = selectedDateRange[0] * 1;
            selectedDateRange[1] = selectedDateRange[1] * 1;
        } else {
            selectedDateRange[0] = 0;
            selectedDateRange[1] = 1746136785000; // 2025, sure hope this is done by then...
        }

        var selectedType = typeSelector.value;

        appointments = loadedData.appointments || [];
        return appointments.filter(function(appointment) {
            var isType = !selectedType,
                hasSlot = false;

            if (!isType) {
                isType = appointment.type.indexOf(selectedType) > -1;
            }

            if (isType) {
                for (var i = 0; i < appointment.slots.length; i++) {
                    if (appointment.slots[i] > selectedDateRange[0] && appointment.slots[i] < selectedDateRange[1]) {
                        hasSlot = true;
                        break;
                    }
                }
            }

            return isType && hasSlot;
        });
    }

    function updateData() {
        var option, types, selectedType, selectedDate;
        
        document.querySelector('.scraped-time').innerText = (new Date(loadedData.last_updated * 1000)).toString();

        selectedDate = selector.value;
        selector.querySelectorAll('[value]').forEach(function(element) {
            element.remove();
        });
        
        for (var i = 0; i < loadedData.dates.length; i++ ) {
            option = document.createElement('option');
            option.value = loadedData.dates[i].min + ',' + loadedData.dates[i].max;
            option.innerText = loadedData.dates[i].label;

            if (option.value == selectedDate) {
                option.selected = true;
            }
            selector.appendChild(option);
        }


        selectedType = typeSelector.value;
        types = {'any': ''};

        loadedData.appointments.forEach(function(appointment) {
            for (var i = 0; i < appointment.type.length; i++) {
                if (typeof types[appointment.type[i]] === 'undefined') {
                    types[appointment.type[i]] = appointment.type[i];
                }
            }
        });

        typeSelector.querySelectorAll('[value]').forEach(function(element) {
            element.remove();
        });
        
        for (var index in types) {
            option = document.createElement('option');
            option.value = types[index];
            option.innerText = index;

            if (option.value == selectedType) {
                option.selected = true;
            }
            typeSelector.appendChild(option);
        }


        updateSelection();
    }

    function updateSelection() {
        var tempElement;
        var appointments = getFilteredApointments();

        markers.forEach(function(item) {
            item.remove();
        });

        var locations = document.querySelector('#locations');
        locations.innerText = '';

        document.querySelector('h3').innerText = 'Locations (' + appointments.length + '): ';

        markers = [];
        appointments.forEach(function (item) {
            var marker = L.marker([item.location.lat, item.location.long]);
            marker.bindPopup(item.name);
            markers.push(marker);
            marker.addTo(map);

            tempElement = document.createElement('li');
            tempElement.innerText = item.name;
            locations.appendChild(tempElement);
        });
    }

    function refreshData() {
        var xhr = new XMLHttpRequest();
        // Round down to eight minutes for caching. 
        var cacheRounding = 48e4;
        xhr.open('GET', 'appointments.json?_=' + roundTime(Date.now()), true);
        xhr.responseType = 'json';
        xhr.onload = function() {
            var status = xhr.status;

            if (selector.querySelector('[value=""]')) {
                selector.querySelector('[value=""]').remove();
            }

            if (status === 200) {
                loadedData = xhr.response;
                updateData();
            }
        };

        xhr.send();

    }

    function roundTime (time) {
        return Math.floor(time / 48e4) * 48e4;
    }

    function init() {
        selector = document.querySelector('#date-selector');
        typeSelector = document.querySelector('#type-selector');

        // Hardcode to Nova Scotia
        map = L.map('mapid').setView([44.6488, -63.5752], 6);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            minZoom: 6,
            'attribution': 'Map data © <a href="http://openstreetmap.org">OpenStreetMap</a> contributors'
        }).addTo(map);
        
        refreshData();
        setInterval(refreshData, 120e3);

        selector.addEventListener('change', function () {
            typeSelector.querySelector(['[value=""]']).selected = true;
            updateSelection();

        });
        typeSelector.addEventListener('change', updateSelection);
    }

    init();
    
})();