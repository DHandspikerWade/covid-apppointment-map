<?php
define('APPOINTMENT_FILE', __DIR__ . '/www/appointments.json');
define('SETTINGS_FILE', __DIR__ . '/settings.json');
define('GEOCODE_CACHE_FILE', __DIR__ . '/location_cache.json');
define('GEOCODE_URL_TEMPLATE', 'https://nominatim.geocoding.ai/search.php?q=%s&accept-language=en&format=jsonv2');
define('BOOKING_URL_TEMPLATE', 'https://sync-cf2-1.canimmunize.ca/fhir/v1/public/booking-page/%s');
define('APPOINTMENT_URL_TEMPLATE','https://sync-cf2-1.canimmunize.ca/fhir/v1/public/booking-page/%s/appointment-types');
define('TIME_URL_TEMPLATE', 'https://sync-cf2-1.canimmunize.ca/fhir/v1/public/availability/%s?appointmentTypeId=%s');

// Booking pages provide timezone, but not sure when it'd change as Nova Scotia is one zone.
// Define as var in case I need to track it later
$timezone = 'America/Halifax';

function geocodeAddress($address) {
	static $cache = null;

	if ($cache === null) {
		if (file_exists(GEOCODE_CACHE_FILE)) {
			$cache = json_decode(file_get_contents(GEOCODE_CACHE_FILE), true);
		} else {
			$cache = [];
		}
	}

	$address = explode(', ', trim($address));
	$address = array_slice($address, 0, 2);
	$address = implode(', ', $address);
	$address .= ', Nova Scotia';
	if (array_key_exists($address, $cache)) {
		return [
			'lat' => (float) $cache[$address]['lat'],
			'long' => (float) $cache[$address]['long'],
		];
	}

	echo "Geocoding: " . $address . PHP_EOL;

	$curl_handle = curl_init();
	curl_setopt($curl_handle, CURLOPT_TIMEOUT, 60);
	curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl_handle, CURLOPT_URL, sprintf(GEOCODE_URL_TEMPLATE, urlencode($address)));
	$output = curl_exec($curl_handle);
	curl_close($curl_handle);

	if ($output) {
		sleep(1); // API Terms of max 1 request per second. Sleep regardless of result '

		try {
			$data = json_decode($output, true);

			if ($data && is_array($data) && $data[0]) {
				$cache[$address] = [
					'lat' => (float) $data[0]['lat'],
					'long' => (float) $data[0]['lon'],
				];
				
				echo "Success \n";
				
			} else {
				echo 'Failed: ' . sprintf(GEOCODE_URL_TEMPLATE, urlencode($address)) . PHP_EOL;
				$cache[$address] = [
					'lat' => 0,
					'long' =>0,
				];
			}

			
			// It's gonna be slow to write everytime but it's a one time cost. This whole app is quick&dirty
			file_put_contents(GEOCODE_CACHE_FILE, json_encode($cache));
			return $cache[$address];
		} catch (\Exception $ex) {
			// Geocode failed
		}
	}

	return false;
}

$yesterday = new DateInterval('P1D');
$datetime = new DateTime();
$datetime->setTimezone(new DateTimeZone($timezone));

$curl_handle = curl_init();
curl_setopt($curl_handle, CURLOPT_TIMEOUT, 60);
curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
$raw_appointments = [];

foreach (json_decode(file_get_contents(SETTINGS_FILE), true)['bookingPages'] as $page_slug) {
	if (!$page_slug) {
		continue;
	}

	curl_setopt($curl_handle, CURLOPT_URL, sprintf(BOOKING_URL_TEMPLATE, $page_slug));
	$output = curl_exec($curl_handle);
	$page_id = null;
	
	if ($output) {
		try {
			$data = json_decode($output, true);
		
			if ($data && isset($data['id'])) {
				$page_id = $data['id'];
			}
		} catch (\Exception $ex) {
			error_log(sprintf('Booking page "%s" threw: %s', $page_slug, $ex->getMessage()));
		}
	}

	if ($page_id) {
		curl_setopt($curl_handle, CURLOPT_URL, sprintf(APPOINTMENT_URL_TEMPLATE, $page_id));
		$output = curl_exec($curl_handle);
		
		if ($output) {
			try {
				$data = json_decode($output, true);

				if ($data && isset($data['total']) && $data['total'] > 0) {
					$raw_appointments = array_merge($raw_appointments, array_values($data['results']));
				}
			} catch (\Exception $ex) {
				error_log(sprintf('Appointment page "%s" threw: %s', $page_id, $ex->getMessage()));
			}
		}
	}
}

$save_output = [
	'dates' => [],
	'appointments' => [],
];

foreach ($raw_appointments as $raw) {
	if ($raw['fullyBooked']) {
		// Fully booked cause I don't care about it
		continue;
	}

	/*
	 * Nova Scotia's English and French properties provide English strings. 
	 * Leave it to the goverment to ignore French...
	 */

	$appointment = [
		'name' => trim($raw['nameEn']),
		'location' => geocodeAddress($raw['gisLocationString']),
		'slots' => [],
		'type' => [],
	];

	$search_label = strtolower($appointment['name']);
	if (strpos($search_label, 'pfizer') !== false) {
		$appointment['type'][] = 'Pfizer';
	}
	if (strpos($search_label, 'astrazeneca') !== false) {
		$appointment['type'][] = 'Astrazeneca';
	}
	if (strpos($search_label, 'moderna') !== false) {
		$appointment['type'][] = 'Moderna';
	}

	if (count($appointment['type']) < 1) {
		$appointment['type'][] = 'Unknown';
	}


	curl_setopt($curl_handle, CURLOPT_URL, sprintf(TIME_URL_TEMPLATE, $raw['id'], $raw['appointmentTypeId']));
	$output = curl_exec($curl_handle);

	if ($output) {
		try {
			$data = json_decode($output, true);

			if ($data && is_array($data)) {
				foreach ($data as $item) {
					if (isset($item['availabilities'])) {
						foreach ($item['availabilities'] as $slot) {
							$datetime->setTimestamp(strtotime($slot['time']));
							$timestamp = $datetime->getTimestamp();

							if ($timestamp) {
								$appointment['slots'][] = $timestamp;
								$key = $datetime->format('m-d');

								if (!array_key_exists($key, $save_output['dates'])) {
									$save_output['dates'][$key] = [
										'label' => $datetime->format('l, F jS'),
										'min' => 0,
										'max' => 0,
									];

									$save_output['dates'][$key]['min'] = $datetime->setTime(0,0)->getTimestamp();
									$save_output['dates'][$key]['max'] = $datetime->add($yesterday)->setTime(0,0)->getTimestamp();
								}
							}

						}
					}
				}
			}
		} catch (\Exception $ex) {
			error_log($ex);
		}
	}

	if (count(array_filter($appointment['slots'])) && $appointment['location']) {
		$save_output['appointments'][] = $appointment;
	}
}

ksort($save_output['dates'], SORT_STRING );
$save_output['dates'] = array_values($save_output['dates']);
$save_output['last_updated'] = time();
file_put_contents(APPOINTMENT_FILE, json_encode($save_output));