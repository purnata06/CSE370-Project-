<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Period Tracker & Mental Health Support Portal</title>
  <style>
    
    body, h1, h2, h3, p 
    {
      margin: 0;
      padding: 0;
    }
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f9f6ff; 
      color: #333;
      line-height: 1.6;
    }

    header {
      text-align: center;
      background: linear-gradient(135deg, #a18cd1, #fbc2eb); 
      color: white;
      padding: 40px 20px;
      border-bottom-left-radius: 40px;
      border-bottom-right-radius: 40px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    header h1 {
      font-size: 2.5rem;
      margin-bottom: 10px;
    }
    header p {
      font-size: 1.1rem;
    }

    .hero {
      text-align: center;
      padding: 40px 20px;
    }
    .hero img {
      width: 180px;
      margin-bottom: 20px;
    }
    .hero h2 {
      font-size: 2rem;
      margin-bottom: 10px;
      color: #5a3ea0;
    }
    .hero p {
      max-width: 650px;
      margin: 0 auto 20px auto;
      font-size: 1rem;
      color: #555;
    }
    .btn {
      display: inline-block;
      background: #7f5af0;
      color: white;
      padding: 12px 24px;
      border-radius: 30px;
      text-decoration: none;
      font-weight: bold;
      transition: background 0.3s, transform 0.2s;
    }
    .btn:hover {
      background: #5a3ea0;
      transform: translateY(-2px);
    }

    .features {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      padding: 40px 20px;
    }
    .feature-card {
      background: white;
      padding: 25px;
      border-radius: 20px;
      text-align: center;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      transition: transform 0.3s, box-shadow 0.3s;
    }
    .feature-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }
    .feature-card img {
      width: 60px;
      margin-bottom: 15px;
    }
    .feature-card h3 {
      margin-bottom: 10px;
      color: #7f5af0;
    }
    .feature-card p {
      font-size: 0.95rem;
      color: #555;
    }

    footer {
      text-align: center;
      padding: 20px;
      background: #f1ebff;
      margin-top: 40px;
      font-size: 0.9rem;
      color: #5a3ea0;
    }
  </style>
</head>
<body>
  <header>
    <h1> Period Tracker & Mental Health Support Portal</h1>
    <p>Your personal space to track cycles, moods and mental health</p>
  </header>

  <section class="hero">
    <img src="https://cdn-icons-png.flaticon.com/512/2910/2910762.png" alt="Wellness Icon">
    <h2>Welcome to Your Wellness Hub</h2>
    <p>Stay in tune with your body and mind. Get cycle predictions, log symptoms, share reports with your doctor, and find support in a caring community.</p>
    <a href="login.php" class="btn">Get Started →</a>
  </section>

  <section class="features">
    <div class="feature-card">
      <img src="https://cdn-icons-png.flaticon.com/512/747/747310.png" alt="Cycle">
      <h3>Cycle Prediction</h3>
      <p>Accurate period and fertility tracking with a customizable calendar.</p>
    </div>
    <div class="feature-card">
      <img src="https://cdn-icons-png.flaticon.com/512/2883/2883841.png" alt="Community">
      <h3>Cycle Buddies</h3>
      <p>Connect with others anonymously and share your journey.</p>
    </div>
    <div class="feature-card">
      <img src="https://cdn-icons-png.flaticon.com/512/3063/3063825.png" alt="Mental Health">
      <h3>Mental Health Support</h3>
      <p>Log moods, access wellness tips, and track emotional balance.</p>
    </div>
    <div class="feature-card">
      <img src="https://cdn-icons-png.flaticon.com/512/1256/1256650.png" alt="Doctor Report">
      <h3>Doctor Reports</h3>
      <p>Download and share your health history with your doctor anytime.</p>
    </div>
  </section>

  <footer>
    &copy; <?php echo date("Y"); ?> Period Tracker & Mental Health Support. All rights reserved.
  </footer>
</body>
</html>
