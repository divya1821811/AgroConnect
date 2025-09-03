<?php
// ---- PHP Backend Logic ---- //
$apiKey = "YOUR_API_KEY"; // 🔑 Replace with your actual API key
$commodity = isset($_GET['commodity']) ? $_GET['commodity'] : "";
$state     = isset($_GET['state']) ? $_GET['state'] : "";

$apiUrl = "https://api.data.gov.in/resource/9ef84268-d588-465a-a308-a864a43d0070
?api-key={$apiKey}&format=json&limit=20";

// Add filters if selected
if ($commodity != "") {
    $apiUrl .= "&filters[commodity]=" . urlencode($commodity);
}
if ($state != "") {
    $apiUrl .= "&filters[state]=" . urlencode($state);
}

// Fetch Data
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

// Decode JSON
$data = json_decode($response, true);
$records = isset($data['records']) ? $data['records'] : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Agriculture Market Prices</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f4f6f9;
      margin: 0;
      padding: 20px;
    }
    h1 {
      text-align: center;
      color: #2c3e50;
    }
    form {
      text-align: center;
      margin-bottom: 20px;
    }
    input, select, button {
      padding: 8px 12px;
      margin: 5px;
      border: 1px solid #ccc;
      border-radius: 6px;
    }
    button {
      background: #2ecc71;
      color: white;
      cursor: pointer;
    }
    button:hover {
      background: #27ae60;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
      background: white;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    th, td {
      padding: 10px;
      border: 1px solid #ddd;
      text-align: center;
    }
    th {
      background: #3498db;
      color: white;
    }
    tr:nth-child(even) {
      background: #f9f9f9;
    }
  </style>
</head>
<body>
  <h1>🌾 Agriculture Market Prices (Agmarknet API)</h1>

  <!-- Search Form -->
  <form method="GET" action="">
    <input type="text" name="commodity" placeholder="Enter Commodity (e.g. Tomato)" value="<?php echo htmlspecialchars($commodity); ?>">
    <input type="text" name="state" placeholder="Enter State (e.g. Andhra Pradesh)" value="<?php echo htmlspecialchars($state); ?>">
    <button type="submit">Search</button>
  </form>

  <!-- Results Table -->
  <table>
    <tr>
      <th>State</th>
      <th>District</th>
      <th>Market</th>
      <th>Commodity</th>
      <th>Variety</th>
      <th>Min Price (₹)</th>
      <th>Max Price (₹)</th>
      <th>Modal Price (₹)</th>
    </tr>
    <?php if (!empty($records)): ?>
      <?php foreach ($records as $record): ?>
        <tr>
          <td><?php echo $record['state']; ?></td>
          <td><?php echo $record['district']; ?></td>
          <td><?php echo $record['market']; ?></td>
          <td><?php echo $record['commodity']; ?></td>
          <td><?php echo $record['variety']; ?></td>
          <td><?php echo $record['min_price']; ?></td>
          <td><?php echo $record['max_price']; ?></td>
          <td><?php echo $record['modal_price']; ?></td>
        </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr>
        <td colspan="8">❌ No data found. Try another commodity or state.</td>
      </tr>
    <?php endif; ?>
  </table>
</body>
</html>
