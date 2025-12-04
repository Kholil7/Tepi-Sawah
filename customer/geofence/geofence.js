const RESTO_LAT = -8.157573444888646;
const RESTO_LNG = 113.72278294958585;
const RADIUS_METERS = 100;

function calculateDistance(lat1, lon1, lat2, lon2) {
  const R = 6371e3;
  const φ1 = (lat1 * Math.PI) / 180;
  const φ2 = (lat2 * Math.PI) / 180;
  const Δφ = ((lat2 - lat1) * Math.PI) / 180;
  const Δλ = ((lon2 - lon1) * Math.PI) / 180;

  const a =
    Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
    Math.cos(φ1) * Math.cos(φ2) * Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

  return R * c;
}

function blockPage(message) {
  document.body.innerHTML = `
    <div style="display: flex; align-items: center; justify-content: center; min-height: 100vh; font-family: Arial, sans-serif; margin: 0; padding: 20px;">
      <div style="background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); padding: 40px; max-width: 500px; text-align: center;">
        <div style="background: #fee2e2; border-radius: 50%; width: 100px; height: 100px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
          <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="15" y1="9" x2="9" y2="15"></line>
            <line x1="9" y1="9" x2="15" y2="15"></line>
          </svg>
        </div>
        <h2 style="color: #1f2937; font-size: 28px; margin-bottom: 15px;">${message}</h2>
        <p style="color: #6b7280; margin-bottom: 25px; font-size: 16px;">Aplikasi ini hanya dapat diakses dalam radius ${RADIUS_METERS} meter dari lokasi restoran.</p>
        <button onclick="location.reload()" style="background: linear-gradient(135deg, #f97316 0%, #dc2626 100%); color: white; border: none; padding: 15px 30px; border-radius: 10px; font-size: 16px; font-weight: bold; cursor: pointer; transition: transform 0.2s;">
          Coba Lagi
        </button>
      </div>
    </div>
  `;
}

function showLoading() {
  document.body.innerHTML = `
    <div style="display: flex; align-items: center; justify-content: center; min-height: 100vh; background: linear-gradient(135deg, #fed7aa 0%, #fdba74 100%); font-family: Arial, sans-serif; margin: 0;">
      <div style="background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); padding: 40px; max-width: 500px; text-align: center;">
        <div style="width: 60px; height: 60px; border: 5px solid #fed7aa; border-top-color: #f97316; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
        <h2 style="color: #1f2937; font-size: 24px; margin-bottom: 10px;">Memeriksa Lokasi</h2>
        <p style="color: #6b7280; font-size: 16px;">Mohon izinkan akses lokasi untuk melanjutkan...</p>
      </div>
    </div>
    <style>
      @keyframes spin {
        to { transform: rotate(360deg); }
      }
    </style>
  `;
}

function checkGeofence(callback) {
  const originalContent = document.body.innerHTML;
  showLoading();

  if (!navigator.geolocation) {
    blockPage('Browser Tidak Mendukung Geolokasi');
    return;
  }

  navigator.geolocation.getCurrentPosition(
    function(position) {
      const userLat = position.coords.latitude;
      const userLng = position.coords.longitude;
      const distance = calculateDistance(userLat, userLng, RESTO_LAT, RESTO_LNG);
      
      console.log('Lokasi Anda: ' + userLat + ', ' + userLng);
      console.log('Lokasi Gedung: ' + RESTO_LAT + ', ' + RESTO_LNG);
      console.log('Jarak: ' + distance + ' meter');
      console.log('Radius Max: ' + RADIUS_METERS + ' meter');

      if (distance <= RADIUS_METERS) {
        document.body.innerHTML = originalContent;
        if (callback) {
          callback(true, distance);
        }
      } else {
        blockPage(`Anda Berada ${Math.round(distance)} Meter dari Restoran`);
      }
    },
    function(error) {
      let errorMessage = 'Terjadi Kesalahan';
      
      switch (error.code) {
        case error.PERMISSION_DENIED:
          errorMessage = 'Izin Lokasi Ditolak';
          break;
        case error.POSITION_UNAVAILABLE:
          errorMessage = 'Lokasi Tidak Tersedia';
          break;
        case error.TIMEOUT:
          errorMessage = 'Waktu Permintaan Habis';
          break;
      }
      
      blockPage(errorMessage);
    },
    {
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 0
    }
  );
}