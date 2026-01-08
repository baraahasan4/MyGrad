<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>360° Room View</title>
  <style>
    html, body {
      height: 100%;
      margin: 0;
    }
    #viewer {
      width: 100%;
      height: 100vh;
    }
  </style>

  <!-- CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/photo-sphere-viewer@4.8.1/dist/photo-sphere-viewer.css" />
</head>
<body>

  <div id="viewer"></div>

  <!-- JS -->
  <script src="https://cdn.jsdelivr.net/npm/three@0.130.1/build/three.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/uevent@2/browser.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/photo-sphere-viewer@4.8.1/dist/photo-sphere-viewer.js"></script>

  <script>
    const { Viewer } = PhotoSphereViewer;

    const viewer = new Viewer({
      container: document.getElementById('viewer'),
      panorama: '{{ asset("images/rooms/stock-photo-full-spherical-seamless-hdr-panorama-degrees-view-in-interior-of-modern-flat-apartment-bedroom-2297506855.jpg") }}',
      navbar: ['zoom', 'fullscreen'],
    });

    // بعد التحميل، قم بتغيير زاوية الرؤية
    viewer.once('ready', () => {
      viewer.setOption('longitude', 3.14); // أو أي زاوية مناسبة
      // أو استخدم setPosition لتحديد latitude و longitude
      // viewer.setPosition({ longitude: Math.PI, latitude: 0 });
    });
  </script>

</body>
</html>
