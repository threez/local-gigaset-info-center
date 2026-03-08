<?php echo '<?xml version="1.0" encoding="utf-8"?>' ?>

<!DOCTYPE html PUBLIC "-//OMA//DTD XHTML Mobile 1.2//EN" "http://www.openmobilealliance.org/tech/DTD/xhtml-mobile12.dtd">
<html>

<?php
/**
 * Replacement weather service for `info.gigaset.net`
 *
 * @copyright Copyright (c) 2024 Tilman Vogel <tilman.vogel@web.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

require('weather.php');

// Get configuration from environment variables
$lat = getenv('LATITUDE');
$lon = getenv('LONGITUDE');
$city = getenv('CITY');
$api_key = getenv('OPENWEATHERMAP_API_KEY');
$base_url = getenv('BASE_URL') ?: 'http://info.gigaset.net/info';
$icon_base_url = getenv('ICON_BASE_URL') ?: 'http://gigaset.net/img';
$proxy_base_url = getenv('PROXY_BASE_URL') ?: 'http://gigaset.net';
?>

<head>
  <title><?php echo $city; ?></title>
  <meta name="expires" content="3600" />
</head>

<body>
  <p style="text-align:center"><b><?php echo $city; ?> (1.3)</b></p>
  <br/>
  <?php
  try {
    $result = retrieve_weather($lat, $lon, $api_key);
    $weatherData = aggregate_daily_weather($result);

    // Display the weather data
    foreach ($weatherData as $date => $data) {
      $minTemp = $data['min_temp'];
      $maxTemp = $data['max_temp'];
      $totalRain = $data['total_rain'];
      $icon = $data['icon'];

      // Extract short weekday (e.g. "Mo" from "Mo, 08.03.2026")
      $dow = explode(',', $date)[0];

      $tempStr = str_replace('.', ',', sprintf('%.0f/%.0f°C', $minTemp, $maxTemp));
      if ($totalRain > 0.5) {
        $tempStr .= ' ' . str_replace('.', ',', sprintf('%.0f mm', $totalRain));
      }

      echo "<p style='text-align:left'>$dow</p>";
      $icon_url = urlencode("$icon_base_url/$icon.png");
      echo "<p style='text-align:center'>"
         . "<object data='$proxy_base_url/proxy/image.do?data=$icon_url'"
         . " type='image/fnt' width='16' height='16'></object>"
         . "</p>";
      echo "<p style='text-align:right'>$tempStr</p>";
      echo "<br/>";
    }
  } catch (Exception $e) {
    echo "<p style='text-align:center'><b>Error:</b> " . $e->getMessage() . "</p>";
  }
  ?>
</body>
</html>
