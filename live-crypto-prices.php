<?php
/**
 * Plugin Name: Live Crypto Prices
 * Description: Display live cryptocurrency prices with real-time updates.
 * Version: 1.0
 * Author: Muhammad Haris
 */

if (!defined('ABSPATH')) {
    exit;
}

function fetch_crypto_prices_with_chart() {
    $api_url = 'https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&ids=bitcoin,ethereum,xrp,solana,cronos&sparkline=true';
    $response = wp_remote_get($api_url);

    if (is_wp_error($response)) {
        return 'Error fetching crypto prices!';
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (empty($data)) {
        return 'No data available!';
    }

    // Include Chart.js (only once)
    $output = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';

    // Table Start
    $output .= '<table border="1" width="100%" style="background:#111; color:white; padding:20px; border-radius:10px; max-width:800px; margin:auto; text-align:left;">
                <tr>
                    <th>Coin</th>
                    <th>Price (USD)</th>
                    <th>24h Change</th>
                    <th>Market Cap</th>
                    <th>7-Day Trend</th>
                </tr>';

    foreach ($data as $coin) {
        $price = number_format($coin['current_price'], 2);
        $change = number_format($coin['price_change_percentage_24h'], 2);
        $market_cap = number_format($coin['market_cap']);
        $change_color = ($change >= 0) ? 'green' : 'red';

        // Convert Sparkline Data to JavaScript Array
        $sparkline_data = json_encode($coin['sparkline_in_7d']['price']);

        // Generate Table Row
        $output .= "<tr>
                        <td>{$coin['name']} ({$coin['symbol']})</td>
                        <td>\${$price}</td>
                        <td style='color: {$change_color};'>{$change}%</td>
                        <td>\${$market_cap}</td>
                        <td><canvas id='chart-{$coin['id']}' width='100' height='30'></canvas></td>
                    </tr>";

        // Add Chart.js Script for Each Coin
        $output .= "<script>
            document.addEventListener('DOMContentLoaded', function () {
                let ctx = document.getElementById('chart-{$coin['id']}').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: Array.from({length: {$sparkline_data}.length}, (_, i) => i),
                        datasets: [{
                            data: {$sparkline_data}.map(price => price.toFixed(2)), 
                            borderColor: '{$change_color}',
                            backgroundColor: 'rgba(0,0,0,0)',
                            borderWidth: 2,
                            pointRadius: 0,
                            tension: 0.2 
                        }]
                    },
                    options: {
                        responsive: false,
                        elements: { line: { tension: 0.2 } },
                        plugins: { legend: { display: false } },
                        scales: { x: { display: false }, y: { display: false } }
                    }
                });
            });
        </script>";
    }

    $output .= '</table>';
    return $output;
}

add_shortcode('crypto_prices', 'fetch_crypto_prices_with_chart');
