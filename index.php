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
$show_icons = getenv('SHOW_ICONS') !== 'false';

$version = '1.7';
$city_with_version = "$city ($version)";

// German weather labels for icon codes
$icon_labels = [
    '01d' => 'Sonnig',   '02d' => 'Wolkig',   '03d' => 'Bewölkt',
    '04d' => 'Bedeckt',  '09d' => 'Schauer',  '10d' => 'Regen',
    '11d' => 'Gewitter', '13d' => 'Schnee',   '50d' => 'Nebel',
];

/**
 * Maps daily rainfall total to German intensity label.
 *
 * @param float $mm Daily total rainfall in millimeters
 * @return string The German intensity label
 */
function rain_label(float $mm): string
{
  if ($mm < 3)  return 'Leichter Regen';
  if ($mm < 15) return 'Regen';
  return 'Starker Regen';
}
?>

<head>
  <title><?php echo $city_with_version; ?></title>
  <meta name="expires" content="3600" />
</head>

<body>
  <?php
  try {
    $result = retrieve_weather($lat, $lon, $api_key);
    $weatherData = array_slice(aggregate_daily_weather($result), 0, 3);

    // Display the weather data
    $isFirst = true;
    foreach ($weatherData as $date => $data) {
      $currentTemp = $data['current_temp'];
      $minTemp = $data['min_temp'];
      $maxTemp = $data['max_temp'];
      $totalRain = $data['total_rain'];
      $icon = $data['icon'];

      // Determine condition label
      $rain_icons = ['09d', '10d', '11d'];
      if (in_array($icon, $rain_icons)) {
        $condLabel = rain_label($totalRain);
      } elseif ($icon === '04d' && $totalRain > 2) {
        $condLabel = rain_label($totalRain);
      } else {
        $condLabel = $icon_labels[$icon] ?? $icon;
      }

      if ($isFirst) {
        // "Heute Bedeckt jetzt 12°C, max 15°C"
        $nowStr = str_replace('.', ',', sprintf('%.0f', $currentTemp));
        $maxStr = str_replace('.', ',', sprintf('%.0f', $maxTemp));
        echo "<p>Heute ";
        if ($show_icons) {
          $icon_url = urlencode("$icon_base_url/$icon.png");
          echo "<object data='$proxy_base_url/proxy/image.do?data=$icon_url'"
             . " type='image/fnt' width='16' height='16'></object>";
        } else {
          echo $condLabel;
        }
        echo " jetzt {$nowStr}°C, max {$maxStr}°C</p>";
        $isFirst = false;
      } else {
        // "Mo Sonnig 8-14°C"
        $dow = explode(',', $date)[0];
        $tempStr = str_replace('.', ',', sprintf('%.0f-%.0f°C', $minTemp, $maxTemp));
        echo "<p>$dow ";
        if ($show_icons) {
          $icon_url = urlencode("$icon_base_url/$icon.png");
          echo "<object data='$proxy_base_url/proxy/image.do?data=$icon_url'"
             . " type='image/fnt' width='16' height='16'></object>";
        } else {
          echo $condLabel;
        }
        echo " $tempStr</p>";
      }
    }
  } catch (Exception $e) {
    echo "<p style='text-align:center'><b>Error:</b> " . $e->getMessage() . "</p>";
  }
  ?>
</body>
</html>
