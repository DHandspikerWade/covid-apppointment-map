<!DOCTYPE html>
<html>
<head>
	<title>Nova Scotia Covid Clinics</title>
	<meta charset="utf-8" />
	<!-- Blank icon. Not a good icon for this. Flag maybe? -->
	<link href="data:image/x-icon;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQEAYAAABPYyMiAAAABmJLR0T///////8JWPfcAAAACXBIWXMAAABIAAAASABGyWs+AAAAF0lEQVRIx2NgGAWjYBSMglEwCkbBSAcACBAAAeaR9cIAAAAASUVORK5CYII=" rel="icon" type="image/x-icon" />	
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="canonical" href="https://clinic-map.spikedhand.com/" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" integrity="sha512-xodZBNTC5n17Xt2atTPuE1HxjVMSvLVW9ocqUKLsCC5CXdbqCmblAshOMAS6/keqq/sMZMZ19scR4PsZChSR7A==" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js" integrity="sha512-XQoYMqMTK8LvdxXYG3nZ448hOEQiglfqkJs1NOQV44cWnUrBc8PkAOcXy20w0vlaXaVUearIOBhiXZ5V3ynxwA==" crossorigin=""></script>
	<style>
	body {
		background-color: #b8e0e8;
	}

	a {
		color: black;
		text-decoration: underline;
	}
	a:hover{
		color: gray;
	}

	.detail-wrapper {
		width: 100%;
		max-width: 600px;
		margin: 0 auto;
	}

	@media only screen and (min-width: 950px) {
		.detail-wrapper {
			max-width: 950px;
		}

		#mapid {
			margin-right: 10px;
		}

		.detail-wrapper div {
			vertical-align: top;
			display: inline-block;
			max-width: 320px;
		}

		h3 {
			margin-top: 0;
		}
	}

	@media only screen and (min-width: 1150px) {
		.detail-wrapper {
			max-width: 1150px;
		}
		.detail-wrapper div {
			max-width: 520px;
		}
	}
	</style>
</head>
<body>

<div>
	<h1>Unofficial NS Covid Vaccaine Clinic Map</h1>
	<p>This map uses data provided by the Nova Scotia's booking website and was created to help people find open appointments. The data in the map <i>should</i> update automatically about every 20 minutes. </p>
	<p>As appointments go quickly, there is a good chance that openings displayed on the map is out-of-date. The official <a href="https://novascotia.flow.canimmunize.ca/en/9874123-19-7418965">CANImmunize</a> page will always has the most up-to-date information. More infomation about getting vaccinated can be found on the <a href="https://novascotia.ca/coronavirus/book-your-vaccination-appointment/">Nova Scotia Goverment page</a>.</p>
	<p>Just a reminder: You need to book an appointment to get vaccinated. All appointments need to be booked in advance. Don't go to a vaccination clinic unless you have an appointment. (Don&apos;t harass the clinics. They're trying to help us.)</p>
	<p>
	Appointments can be booked via:
	<ul>
		<li>CANImmunize at <a href="https://novascotia.flow.canimmunize.ca/en/9874123-19-7418965">https://novascotia.flow.canimmunize.ca/en/9874123-19-7418965</a></li>
		<li>By toll-free phone: <a href="tel:18337977772">1-833-797-7772</a> (7 am to 10 pm, 7 days a week)</a>
	</ul>
	</p>
</div>

<div>
	Available dates:
	<select id="date-selector"><option value="">Loading</option></select>
</div>
<div>
	Type:
	<select id="type-selector"><option value="">Any</option></select>
</div>

<div>
	Data last scraped: <span class="scraped-time"></span>
	<br />
	<br />
</div>

<div class="detail-wrapper">
	<div id="mapid" style="width: 600px; max-width: 100%; height: 400px;"></div>
	<div>
		<h3>Locations:</h3>
		<ul id="locations"></ul>
	</div>
</div>



<script src="script.js?version=<?php echo filemtime(__DIR__ . '/script.js'); ?>"></script>
</body>
</html>
