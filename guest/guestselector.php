<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Guest Selection Modal</title>
  <style>
    body {
      font-family: Arial, sans-serif;
    }

    .container {
      padding: 20px;
    }

    .booking-info {
      display: flex;
      gap: 20px;
      align-items: center;
    }

    .booking-section {
      background-color: #e4f5d1;
      padding: 10px 15px;
      border-radius: 4px;
      cursor: pointer;
    }

    /* Modal styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 999;
      left: 0;
      top: 0;
      width: 100vw;
      height: 100vh;
      background-color: rgba(0, 0, 0, 0.6);
      justify-content: center;
      align-items: center;
    }

    .modal-content {
      background: white;
      padding: 20px;
      width: 300px;
      border-radius: 6px;
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      font-weight: bold;
      margin-bottom: 15px;
    }

    .guest-selector {
      margin-bottom: 15px;
    }

    .counter {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .counter button {
      padding: 5px 10px;
      font-size: 16px;
    }

    .done-btn {
      background-color: #009879;
      color: white;
      border: none;
      padding: 10px 20px;
      float: right;
      border-radius: 4px;
      cursor: pointer;
    }
  </style>
</head>
<body>

<div class="container">
  <div class="booking-info">
    <div class="booking-section" id="guestBtn">
      <strong>Guests</strong><br>
      <span id="guestSummary">0 adults, 0 children</span>
    </div>
    <div class="booking-section">
      <strong>Check-in</strong><br>
      Sat, Jun 14, 2025
    </div>
    <div class="booking-section">
      <strong>Check-out</strong><br>
      Fri, Jun 20, 2025
    </div>
  </div>
</div>

<!-- Modal -->
<div id="guestModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <span>Guests</span>
      <span class="close" style="cursor:pointer;">&times;</span>
    </div>
    <div class="guest-selector">
      <label>Adults (Ages 13 or above)</label>
      <div class="counter">
        <button onclick="updateGuest('adult', -1)">−</button>
        <span id="adultCount">0</span>
        <button onclick="updateGuest('adult', 1)">+</button>
      </div>
    </div>
    <div class="guest-selector">
      <label>Children (Ages 0-12)</label>
      <div class="counter">
        <button onclick="updateGuest('child', -1)">−</button>
        <span id="childCount">0</span>
        <button onclick="updateGuest('child', 1)">+</button>
      </div>
    </div>
    <button class="done-btn" onclick="saveGuests()">DONE</button>
  </div>
</div>

<script>
  let adultCount = 0;
  let childCount = 0;

  document.getElementById("guestBtn").onclick = function () {
    document.getElementById("guestModal").style.display = "flex";
  };

  document.querySelector(".close").onclick = function () {
    document.getElementById("guestModal").style.display = "none";
  };

  function updateGuest(type, change) {
    if (type === "adult") {
      adultCount = Math.max(0, adultCount + change);
      document.getElementById("adultCount").textContent = adultCount;
    } else {
      childCount = Math.max(0, childCount + change);
      document.getElementById("childCount").textContent = childCount;
    }
  }

  function saveGuests() {
    document.getElementById("guestSummary").textContent =
      `${adultCount} adults, ${childCount} children`;
    document.getElementById("guestModal").style.display = "none";
  }
</script>

</body>
</html>
