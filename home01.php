<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SIPORA POLIJE - Sistem Informasi Kampus</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary-blue: #0ea5e9;
      --dark-blue: #0284c7;
      --light-blue: #e0f2fe;
      --white: #ffffff;
      --text-primary: #1e293b;
      --text-secondary: #64748b;
      --text-light: #94a3b8;
      --border-color: #e2e8f0;
      --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
      --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
      --shadow-lg: 0 10px 25px rgba(0,0,0,0.1);
      --shadow-xl: 0 20px 40px rgba(0,0,0,0.15);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      background: linear-gradient(to bottom, #f0f9ff, #e0f2fe);
      color: var(--text-primary);
      overflow-x: hidden;
      padding-bottom: 70px;
      line-height: 1.6;
    }

    /* Modern Header with Search */
    .modern-header {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      height: 64px;
      background: var(--primary-blue);
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      z-index: 1000;
      display: flex;
      align-items: center;
      padding: 0 24px;
      transition: all 0.3s ease;
    }

    .header-left {
      display: flex;
      align-items: center;
      gap: 20px;
      flex-shrink: 0;
    }

    .menu-btn {
      background: none;
      border: none;
      color: var(--white);
      font-size: 22px;
      cursor: pointer;
      padding: 10px;
      border-radius: 12px;
      transition: all 0.3s ease;
    }

    .menu-btn:hover {
      background: rgba(255, 255, 255, 0.1);
      transform: translateY(-2px);
    }

    .logo {
      display: flex;
      align-items: center;
      gap: 12px;
      color: var(--white);
      text-decoration: none;
    }

    .logo-icon {
      width: 40px;
      height: 40px;
      background: var(--white);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--primary-blue);
      font-weight: 700;
      font-size: 20px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .logo-text {
      font-size: 20px;
      font-weight: 700;
      letter-spacing: -0.5px;
    }

    .header-center {
      flex: 1;
      max-width: 600px;
      margin: 0 24px;
    }

    .search-container {
      position: relative;
      width: 100%;
    }

    .search-input {
      width: 100%;
      padding: 10px 40px 10px 16px;
      border: none;
      border-radius: 24px;
      background: var(--white);
      color: var(--text-primary);
      font-size: 14px;
      transition: all 0.3s ease;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .search-input::placeholder {
      color: var(--text-secondary);
    }

    .search-input:focus {
      outline: none;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    }

    .search-icon-btn {
      position: absolute;
      right: 8px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: var(--text-secondary);
      font-size: 18px;
      cursor: pointer;
      padding: 6px;
      border-radius: 50%;
      transition: all 0.3s ease;
    }

    .search-icon-btn:hover {
      color: var(--primary-blue);
    }

    .header-right {
      display: flex;
      align-items: center;
      gap: 16px;
      flex-shrink: 0;
    }

    .notification-btn, .profile-btn {
      background: none;
      border: none;
      color: var(--white);
      font-size: 20px;
      cursor: pointer;
      padding: 10px;
      border-radius: 12px;
      transition: all 0.3s ease;
      position: relative;
    }

    .notification-btn:hover, .profile-btn:hover {
      background: rgba(255, 255, 255, 0.1);
      transform: translateY(-2px);
    }

    .notification-badge {
      position: absolute;
      top: 8px;
      right: 8px;
      width: 8px;
      height: 8px;
      background: #f472b6;
      border-radius: 50%;
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0% {
        box-shadow: 0 0 0 0 rgba(244, 114, 182, 0.7);
      }
      70% {
        box-shadow: 0 0 0 10px rgba(244, 114, 182, 0);
      }
      100% {
        box-shadow: 0 0 0 0 rgba(244, 114, 182, 0);
      }
    }

    /* Search Overlay */
    .search-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.8);
      backdrop-filter: blur(10px);
      z-index: 2000;
      display: none;
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    .search-overlay.active {
      display: flex;
      opacity: 1;
    }

    .search-modal {
      width: 90%;
      max-width: 600px;
      margin: auto;
      background: white;
      border-radius: 20px;
      padding: 32px;
      margin-top: 100px;
      box-shadow: var(--shadow-xl);
      animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
      from {
        transform: translateY(50px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .search-modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
    }

    .search-modal-title {
      font-size: 24px;
      font-weight: 700;
      color: var(--text-primary);
    }

    .search-close-btn {
      background: none;
      border: none;
      color: var(--text-secondary);
      font-size: 24px;
      cursor: pointer;
      padding: 8px;
      border-radius: 8px;
      transition: all 0.3s ease;
    }

    .search-close-btn:hover {
      background: var(--light-blue);
      color: var(--text-primary);
    }

    .search-modal-input {
      width: 100%;
      padding: 16px 20px;
      border: 2px solid var(--border-color);
      border-radius: 12px;
      font-size: 16px;
      transition: all 0.3s ease;
      margin-bottom: 24px;
    }

    .search-modal-input:focus {
      outline: none;
      border-color: var(--primary-blue);
      box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
    }

    .search-filters {
      display: flex;
      gap: 12px;
      margin-bottom: 24px;
      flex-wrap: wrap;
    }

    .filter-chip {
      padding: 8px 16px;
      border: 1px solid var(--border-color);
      border-radius: 20px;
      background: white;
      color: var(--text-secondary);
      font-size: 14px;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .filter-chip:hover {
      border-color: var(--primary-blue);
      color: var(--primary-blue);
    }

    .filter-chip.active {
      background: var(--primary-blue);
      border-color: transparent;
      color: white;
    }

    .search-results {
      max-height: 400px;
      overflow-y: auto;
    }

    .search-result-item {
      display: flex;
      align-items: center;
      padding: 16px;
      border-radius: 12px;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-bottom: 8px;
    }

    .search-result-item:hover {
      background: var(--light-blue);
    }

    .search-result-icon {
      width: 40px;
      height: 40px;
      background: var(--light-blue);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--dark-blue);
      font-size: 18px;
      margin-right: 16px;
    }

    .search-result-content {
      flex: 1;
    }

    .search-result-title {
      font-weight: 600;
      color: var(--text-primary);
      margin-bottom: 4px;
    }

    .search-result-subtitle {
      font-size: 14px;
      color: var(--text-secondary);
    }

    /* Notification Panel */
    .notification-panel {
      position: fixed;
      top: 64px;
      right: -400px;
      width: 400px;
      height: calc(100vh - 64px);
      background: white;
      box-shadow: var(--shadow-xl);
      z-index: 1500;
      transition: right 0.3s ease;
      overflow-y: auto;
    }

    .notification-panel.active {
      right: 0;
    }

    .notification-header {
      padding: 20px;
      border-bottom: 1px solid var(--border-color);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .notification-title {
      font-size: 18px;
      font-weight: 600;
      color: var(--text-primary);
    }

    .notification-clear {
      background: none;
      border: none;
      color: var(--primary-blue);
      font-size: 14px;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .notification-clear:hover {
      text-decoration: underline;
    }

    .notification-list {
      padding: 16px;
    }

    .notification-item {
      display: flex;
      padding: 16px;
      border-radius: 12px;
      margin-bottom: 12px;
      cursor: pointer;
      transition: all 0.3s ease;
      border: 1px solid transparent;
    }

    .notification-item:hover {
      background: var(--light-blue);
      border-color: #bae6fd;
    }

    .notification-item.unread {
      background: var(--light-blue);
      border-color: #bae6fd;
    }

    .notification-icon {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 12px;
      font-size: 18px;
    }

    .notification-icon.info {
      background: var(--light-blue);
      color: var(--dark-blue);
    }

    .notification-icon.success {
      background: #10b98120;
      color: #10b981;
    }

    .notification-icon.warning {
      background: #f59e0b20;
      color: #f59e0b;
    }

    .notification-content {
      flex: 1;
    }

    .notification-message {
      font-weight: 500;
      color: var(--text-primary);
      margin-bottom: 4px;
      font-size: 14px;
    }

    .notification-time {
      font-size: 12px;
      color: var(--text-secondary);
    }

    /* Modern Sidebar */
    .sidebar-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(5px);
      z-index: 999;
      display: none;
    }

    .sidebar-overlay.active {
      display: block;
    }

    .sidebar {
      position: fixed;
      top: 0;
      left: -320px;
      width: 320px;
      height: 100vh;
      background: white;
      box-shadow: 0 0 40px rgba(0, 0, 0, 0.15);
      z-index: 1001;
      transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      overflow-y: auto;
    }

    .sidebar.active {
      left: 0;
    }

    .sidebar-header {
      height: 64px;
      background: var(--primary-blue);
      display: flex;
      align-items: center;
      padding: 0 24px;
      color: white;
    }

    .sidebar-close {
      background: none;
      border: none;
      color: white;
      font-size: 24px;
      cursor: pointer;
      margin-right: 20px;
      transition: transform 0.3s ease;
    }

    .sidebar-close:hover {
      transform: rotate(90deg);
    }

    .sidebar-profile {
      padding: 24px;
      background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
      text-align: center;
      border-bottom: 1px solid var(--border-color);
    }

    .profile-avatar {
      width: 90px;
      height: 90px;
      background: var(--primary-blue);
      border-radius: 50%;
      margin: 0 auto 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 36px;
      font-weight: 700;
      box-shadow: 0 8px 20px rgba(14, 165, 233, 0.3);
    }

    .profile-name {
      font-weight: 600;
      font-size: 18px;
      margin-bottom: 4px;
      color: var(--text-primary);
    }

    .profile-role {
      color: var(--text-secondary);
      font-size: 14px;
    }

    .sidebar-menu {
      padding: 20px 0;
    }

    .menu-section {
      margin-bottom: 28px;
    }

    .menu-section-title {
      padding: 8px 24px;
      font-size: 12px;
      font-weight: 600;
      color: var(--text-light);
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .menu-item {
      display: flex;
      align-items: center;
      padding: 14px 24px;
      color: var(--text-primary);
      text-decoration: none;
      transition: all 0.3s ease;
      position: relative;
    }

    .menu-item:hover {
      background: linear-gradient(90deg, rgba(14, 165, 233, 0.08) 0%, transparent 100%);
    }

    .menu-item.active {
      background: linear-gradient(90deg, rgba(14, 165, 233, 0.12) 0%, transparent 100%);
      color: var(--primary-blue);
      font-weight: 600;
    }

    .menu-item.active::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      bottom: 0;
      width: 3px;
      background: var(--primary-blue);
    }

    .menu-icon {
      margin-right: 16px;
      font-size: 20px;
    }

    /* Main Content */
    .main-content {
      margin-top: 64px;
      padding: 24px;
      max-width: 1400px;
      margin-left: auto;
      margin-right: auto;
    }

    /* Filter Bar */
    .filter-bar {
      background: white;
      border-radius: 16px;
      padding: 20px;
      margin-bottom: 24px;
      box-shadow: var(--shadow-sm);
      display: flex;
      align-items: center;
      gap: 16px;
      flex-wrap: wrap;
    }

    .filter-group {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .filter-label {
      font-size: 14px;
      color: var(--text-secondary);
      font-weight: 500;
    }

    .filter-select {
      padding: 8px 12px;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      background: white;
      color: var(--text-primary);
      font-size: 14px;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .filter-select:focus {
      outline: none;
      border-color: var(--primary-blue);
      box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
    }

    .view-toggle {
      margin-left: auto;
      display: flex;
      gap: 8px;
    }

    .view-btn {
      padding: 8px;
      border: 1px solid var(--border-color);
      background: white;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
      color: var(--text-secondary);
    }

    .view-btn:hover {
      border-color: var(--primary-blue);
      color: var(--primary-blue);
    }

    .view-btn.active {
      background: var(--primary-blue);
      border-color: transparent;
      color: white;
    }

    /* Modern Banner */
    .banner-section {
      margin-bottom: 32px;
    }

    .banner-carousel {
      position: relative;
      border-radius: 20px;
      overflow: hidden;
      height: 280px;
      box-shadow: var(--shadow-xl);
    }

    .banner-slide {
      position: absolute;
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      padding: 40px;
      color: white;
      opacity: 0;
      transition: opacity 0.6s ease;
    }

    .banner-slide.active {
      opacity: 1;
    }

    .banner-slide:nth-child(1) {
      background: var(--primary-blue);
    }

    .banner-slide:nth-child(2) {
      background: var(--dark-blue);
    }

    .banner-slide:nth-child(3) {
      background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    }

    .banner-content {
      max-width: 600px;
    }

    .banner-content h2 {
      font-size: 32px;
      font-weight: 700;
      margin-bottom: 12px;
      line-height: 1.2;
    }

    .banner-content p {
      font-size: 18px;
      margin-bottom: 24px;
      opacity: 0.95;
      line-height: 1.5;
    }

    .banner-btn {
      background: white;
      color: var(--primary-blue);
      border: none;
      padding: 14px 32px;
      border-radius: 30px;
      font-weight: 600;
      font-size: 16px;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .banner-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .banner-dots {
      position: absolute;
      bottom: 20px;
      left: 50%;
      transform: translateX(-50%);
      display: flex;
      gap: 10px;
    }

    .banner-dot {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.4);
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .banner-dot.active {
      background: white;
      width: 30px;
      border-radius: 5px;
    }

    /* Stats Section */
    .stats-section {
      background: white;
      border-radius: 20px;
      padding: 28px;
      margin-bottom: 32px;
      box-shadow: var(--shadow-md);
    }

    .stats-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
    }

    .stats-title {
      font-size: 22px;
      font-weight: 700;
      color: var(--text-primary);
    }

    .stats-timer {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 14px;
      color: var(--text-secondary);
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 20px;
    }

    .stat-card {
      background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
      border-radius: 16px;
      padding: 20px;
      text-align: center;
      transition: all 0.3s ease;
      border: 1px solid #bae6fd;
      cursor: pointer;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--shadow-lg);
      background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
    }

    .stat-icon {
      font-size: 36px;
      margin-bottom: 12px;
    }

    .stat-label {
      font-size: 13px;
      color: var(--text-secondary);
      margin-bottom: 6px;
      font-weight: 500;
    }

    .stat-value {
      font-size: 24px;
      font-weight: 700;
      color: var(--dark-blue);
    }

    /* Cards Section */
    .cards-section {
      margin-bottom: 32px;
    }

    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
    }

    .section-title {
      font-size: 22px;
      font-weight: 700;
      color: var(--text-primary);
    }

    .section-actions {
      display: flex;
      gap: 12px;
      align-items: center;
    }

    .see-all-btn {
      color: var(--primary-blue);
      text-decoration: none;
      font-size: 15px;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .see-all-btn:hover {
      color: var(--dark-blue);
      transform: translateX(3px);
    }

    .add-btn {
      padding: 8px 16px;
      background: var(--primary-blue);
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .add-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
    }

    .cards-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
      gap: 16px;
    }

    .card {
      background: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: var(--shadow-sm);
      transition: all 0.3s ease;
      cursor: pointer;
      position: relative;
    }

    .card:hover {
      transform: translateY(-5px);
      box-shadow: var(--shadow-lg);
    }

    .card-thumbnail {
      width: 100%;
      height: 160px;
      background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
    }

    .card-thumbnail img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .card-badge {
      position: absolute;
      top: 8px;
      left: 8px;
      background: var(--primary-blue);
      color: white;
      padding: 4px 8px;
      border-radius: 16px;
      font-size: 11px;
      font-weight: 600;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      z-index: 10;
    }

    .card-info {
      padding: 12px;
    }

    .card-title {
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 4px;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      color: var(--text-primary);
      line-height: 1.3;
    }

    .card-meta {
      font-size: 11px;
      color: var(--text-secondary);
      display: flex;
      align-items: center;
      gap: 3px;
    }

    .card-type-icon {
      position: absolute;
      top: 8px;
      right: 8px;
      width: 24px;
      height: 24px;
      background: rgba(255, 255, 255, 0.9);
      border-radius: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--primary-blue);
      font-size: 12px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
      z-index: 10;
    }

    /* List Section */
    .list-section {
      background: white;
      border-radius: 20px;
      padding: 28px;
      margin-bottom: 32px;
      box-shadow: var(--shadow-md);
    }

    .list-item {
      display: flex;
      align-items: center;
      padding: 18px 0;
      border-bottom: 1px solid var(--border-color);
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .list-item:last-child {
      border-bottom: none;
    }

    .list-item:hover {
      background: linear-gradient(90deg, rgba(14, 165, 233, 0.05) 0%, transparent 100%);
      margin: 0 -28px;
      padding-left: 28px;
      padding-right: 28px;
    }

    .list-icon {
      width: 52px;
      height: 52px;
      background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--primary-blue);
      font-size: 24px;
      margin-right: 20px;
      transition: all 0.3s ease;
      border: 1px solid #bae6fd;
    }

    .list-item:hover .list-icon {
      background: var(--primary-blue);
      color: white;
      transform: scale(1.1);
      border-color: transparent;
    }

    .list-content {
      flex: 1;
    }

    .list-title {
      font-weight: 600;
      margin-bottom: 4px;
      color: var(--text-primary);
    }

    .list-subtitle {
      font-size: 14px;
      color: var(--text-secondary);
    }

    .list-arrow {
      color: var(--text-light);
      transition: all 0.3s ease;
    }

    .list-item:hover .list-arrow {
      color: var(--primary-blue);
      transform: translateX(5px);
    }

    /* Floating Action Button */
    .fab {
      position: fixed;
      bottom: 90px;
      right: 24px;
      width: 60px;
      height: 60px;
      background: var(--primary-blue);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 24px;
      box-shadow: 0 8px 25px rgba(14, 165, 233, 0.3);
      cursor: pointer;
      transition: all 0.3s ease;
      z-index: 999;
    }

    .fab:hover {
      transform: scale(1.1) rotate(90deg);
      box-shadow: 0 12px 35px rgba(14, 165, 233, 0.4);
    }

    /* Bottom Navigation */
    .bottom-nav {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      height: 60px;
      background: white;
      box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
      display: flex;
      justify-content: space-around;
      align-items: center;
      z-index: 1000;
    }

    .nav-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 4px;
      color: var(--text-secondary);
      text-decoration: none;
      font-size: 12px;
      transition: all 0.3s ease;
      padding: 8px;
      border-radius: 12px;
    }

    .nav-item.active {
      color: var(--primary-blue);
    }

    .nav-icon {
      font-size: 24px;
      transition: all 0.3s ease;
    }

    .nav-item:hover .nav-icon {
      transform: translateY(-3px);
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 2000;
      overflow: auto;
    }

    .modal-content {
      background-color: white;
      margin: 5% auto;
      padding: 20px;
      border-radius: 12px;
      width: 90%;
      max-width: 600px;
      box-shadow: var(--shadow-xl);
      animation: slideUp 0.3s ease;
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 1px solid var(--border-color);
    }

    .modal-title {
      font-size: 20px;
      font-weight: 600;
      color: var(--text-primary);
    }

    .close-btn {
      background: none;
      border: none;
      font-size: 24px;
      color: var(--text-secondary);
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .close-btn:hover {
      color: var(--text-primary);
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: var(--text-primary);
    }

    .form-control {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      font-size: 14px;
      transition: all 0.3s ease;
    }

    .form-control:focus {
      outline: none;
      border-color: var(--primary-blue);
      box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
    }

    .form-select {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      font-size: 14px;
      background-color: white;
      transition: all 0.3s ease;
    }

    .form-select:focus {
      outline: none;
      border-color: var(--primary-blue);
      box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
    }

    .btn-primary {
      background-color: var(--primary-blue);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .btn-primary:hover {
      background-color: var(--dark-blue);
    }

    .btn-secondary {
      background-color: transparent;
      color: var(--text-secondary);
      border: 1px solid var(--border-color);
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .btn-secondary:hover {
      background-color: var(--light-blue);
      border-color: var(--primary-blue);
      color: var(--primary-blue);
    }

    .modal-footer {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 20px;
    }

    /* Upload Area */
    .upload-area {
      border: 2px dashed var(--border-color);
      border-radius: 8px;
      padding: 20px;
      text-align: center;
      transition: all 0.3s ease;
      cursor: pointer;
    }

    .upload-area:hover {
      border-color: var(--primary-blue);
      background-color: var(--light-blue);
    }

    .upload-area.dragover {
      border-color: var(--primary-blue);
      background-color: var(--light-blue);
    }

    .upload-icon {
      font-size: 48px;
      color: var(--text-secondary);
      margin-bottom: 10px;
    }

    .upload-text {
      color: var(--text-secondary);
      margin-bottom: 10px;
    }

    .upload-button {
      background-color: var(--primary-blue);
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 6px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .upload-button:hover {
      background-color: var(--dark-blue);
    }

    .file-list {
      margin-top: 15px;
    }

    .file-item {
      display: flex;
      align-items: center;
      padding: 10px;
      border-radius: 6px;
      background-color: var(--light-blue);
      margin-bottom: 8px;
    }

    .file-icon {
      font-size: 24px;
      color: var(--primary-blue);
      margin-right: 10px;
    }

    .file-name {
      flex: 1;
      font-size: 14px;
      color: var(--text-primary);
    }

    .file-size {
      font-size: 12px;
      color: var(--text-secondary);
      margin-right: 10px;
    }

    .file-remove {
      background: none;
      border: none;
      color: var(--text-secondary);
      cursor: pointer;
      font-size: 18px;
      transition: all 0.3s ease;
    }

    .file-remove:hover {
      color: #ef4444;
    }

    /* Loading Spinner */
    .spinner {
      display: inline-block;
      width: 20px;
      height: 20px;
      border: 3px solid rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      border-top-color: white;
      animation: spin 1s ease-in-out infinite;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    /* Toast Notification */
    .toast-container {
      position: fixed;
      top: 80px;
      right: 20px;
      z-index: 3000;
    }

    .toast {
      background-color: white;
      border-radius: 8px;
      box-shadow: var(--shadow-lg);
      padding: 16px;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      min-width: 300px;
      animation: slideInRight 0.3s ease;
    }

    @keyframes slideInRight {
      from {
        transform: translateX(100%);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }

    .toast-icon {
      font-size: 20px;
      margin-right: 12px;
    }

    .toast.success .toast-icon {
      color: #10b981;
    }

    .toast.error .toast-icon {
      color: #ef4444;
    }

    .toast.info .toast-icon {
      color: var(--primary-blue);
    }

    .toast-message {
      flex: 1;
      font-size: 14px;
      color: var(--text-primary);
    }

    .toast-close {
      background: none;
      border: none;
      color: var(--text-secondary);
      cursor: pointer;
      font-size: 18px;
      margin-left: 10px;
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 40px 20px;
      color: var(--text-secondary);
    }

    .empty-state-icon {
      font-size: 64px;
      margin-bottom: 16px;
      opacity: 0.5;
    }

    .empty-state-title {
      font-size: 18px;
      font-weight: 600;
      margin-bottom: 8px;
      color: var(--text-primary);
    }

    .empty-state-text {
      font-size: 14px;
      margin-bottom: 20px;
    }

    .empty-state-action {
      background-color: var(--primary-blue);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .empty-state-action:hover {
      background-color: var(--dark-blue);
    }

    /* Loading State */
    .loading-state {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 40px 20px;
      color: var(--text-secondary);
    }

    .loading-spinner {
      width: 40px;
      height: 40px;
      border: 4px solid var(--light-blue);
      border-top: 4px solid var(--primary-blue);
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin-bottom: 16px;
    }

    .loading-text {
      font-size: 16px;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .header-center {
        display: none;
      }
      
      .main-content {
        padding: 16px;
      }
      
      .banner-content h2 {
        font-size: 24px;
      }
      
      .banner-content p {
        font-size: 16px;
      }
      
      .cards-grid {
        grid-template-columns: repeat(2, 1fr);
      }
      
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }

      .notification-panel {
        width: 100%;
        right: -100%;
      }

      .search-modal {
        width: 95%;
        padding: 24px;
      }

      .modal-content {
        width: 95%;
        margin: 10% auto;
      }
    }

    @media (min-width: 1200px) {
      .cards-grid {
        grid-template-columns: repeat(5, 1fr);
      }
      
      .stats-grid {
        grid-template-columns: repeat(6, 1fr);
      }
    }
  </style>
</head>
<body>
  <!-- Modern Header with Search -->
  <header class="modern-header">
    <div class="header-left">
      <button class="menu-btn" id="menuBtn">
        <i class="bi bi-list"></i>
      </button>
      <a href="#" class="logo">
        <div class="logo-icon">P</div>
        <span class="logo-text">SIPORA</span>
      </a>
    </div>
    <div class="header-center">
      <div class="search-container">
        <input type="text" class="search-input" placeholder="Cari mahasiswa, buku, jurnal..." id="searchInput">
        <button class="search-icon-btn" id="searchBtn">
          <i class="bi bi-search"></i>
        </button>
      </div>
    </div>
    <div class="header-right">
      <button class="notification-btn" id="notificationBtn">
        <i class="bi bi-bell"></i>
        <span class="notification-badge" id="notificationBadge" style="display: none;"></span>
      </button>
      <button class="profile-btn" id="profileBtn">
        <i class="bi bi-person-circle"></i>
      </button>
    </div>
  </header>

  <!-- Search Overlay -->
  <div class="search-overlay" id="searchOverlay">
    <div class="search-modal">
      <div class="search-modal-header">
        <h3 class="search-modal-title">Pencarian Lanjutan</h3>
        <button class="search-close-btn" id="searchCloseBtn">
          <i class="bi bi-x"></i>
        </button>
      </div>
      <input type="text" class="search-modal-input" placeholder="Ketik kata kunci pencarian..." id="searchModalInput">
      <div class="search-filters">
        <button class="filter-chip active" data-filter="all">Semua</button>
        <button class="filter-chip" data-filter="students">Mahasiswa</button>
        <button class="filter-chip" data-filter="books">Buku</button>
        <button class="filter-chip" data-filter="journals">Jurnal</button>
        <button class="filter-chip" data-filter="thesis">Skripsi</button>
      </div>
      <div class="search-results" id="searchResults">
        <div class="loading-state">
          <div class="loading-spinner"></div>
          <div class="loading-text">Menunggu pencarian...</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Notification Panel -->
  <div class="notification-panel" id="notificationPanel">
    <div class="notification-header">
      <h3 class="notification-title">Notifikasi</h3>
      <button class="notification-clear" id="clearNotificationsBtn">Hapus semua</button>
    </div>
    <div class="notification-list" id="notificationList">
      <div class="loading-state">
        <div class="loading-spinner"></div>
        <div class="loading-text">Memuat notifikasi...</div>
      </div>
    </div>
  </div>

  <!-- Sidebar Overlay -->
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- Modern Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <button class="sidebar-close" id="sidebarClose">
        <i class="bi bi-x"></i>
      </button>
      <span>Menu</span>
    </div>
    <div class="sidebar-profile">
      <div class="profile-avatar" id="profileAvatar">AD</div>
      <div class="profile-name" id="profileName">Admin</div>
      <div class="profile-role" id="profileRole">Administrator</div>
    </div>
    <nav class="sidebar-menu">
      <div class="menu-section">
        <div class="menu-section-title">Akademik</div>
        <a href="#" class="menu-item active" data-page="dashboard">
          <i class="bi bi-grid-1x2 menu-icon"></i>
          Beranda
        </a>
        <a href="#" class="menu-item" data-page="students">
          <i class="bi bi-people menu-icon"></i>
          Mahasiswa
        </a>
        <a href="#" class="menu-item" data-page="lecturers">
          <i class="bi bi-person-badge menu-icon"></i>
          Dosen
        </a>
        <a href="#" class="menu-item" data-page="departments">
          <i class="bi bi-building menu-icon"></i>
          Jurusan
        </a>
      </div>
      <div class="menu-section">
        <div class="menu-section-title">Perpustakaan</div>
        <a href="#" class="menu-item" data-page="books">
          <i class="bi bi-book menu-icon"></i>
          Buku
        </a>
        <a href="#" class="menu-item" data-page="journals">
          <i class="bi bi-journal-text menu-icon"></i>
          Jurnal
        </a>
        <a href="#" class="menu-item" data-page="thesis">
          <i class="bi bi-file-earmark-text menu-icon"></i>
          Skripsi
        </a>
      </div>
      <div class="menu-section">
        <div class="menu-section-title">Lainnya</div>
        <a href="#" class="menu-item" data-page="settings">
          <i class="bi bi-gear menu-icon"></i>
          Pengaturan
        </a>
        <a href="#" class="menu-item" id="logoutBtn">
          <i class="bi bi-box-arrow-right menu-icon"></i>
          Keluar
        </a>
      </div>
    </nav>
  </aside>

  <!-- Main Content -->
  <main class="main-content" id="mainContent">
    <div class="loading-state">
      <div class="loading-spinner"></div>
      <div class="loading-text">Memuat data...</div>
    </div>
  </main>

  <!-- Floating Action Button -->
  <div class="fab" id="fab">
    <i class="bi bi-plus"></i>
  </div>

  <!-- Bottom Navigation -->
  <nav class="bottom-nav">
    <a href="#" class="nav-item active" data-page="dashboard">
      <i class="bi bi-house-door nav-icon"></i>
      <span>Beranda</span>
    </a>
    <a href="#" class="nav-item" data-page="students">
      <i class="bi bi-people nav-icon"></i>
      <span>Mahasiswa</span>
    </a>
    <a href="#" class="nav-item" data-page="library">
      <i class="bi bi-book nav-icon"></i>
      <span>Perpustakaan</span>
    </a>
    <a href="#" class="nav-item" data-page="activities">
      <i class="bi bi-activity nav-icon"></i>
      <span>Aktivitas</span>
    </a>
    <a href="#" class="nav-item" data-page="profile">
      <i class="bi bi-person nav-icon"></i>
      <span>Profil</span>
    </a>
  </nav>

  <!-- Toast Container -->
  <div class="toast-container" id="toastContainer"></div>

  <!-- Modals -->
  <!-- Add Student Modal -->
  <div id="addStudentModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Tambah Mahasiswa Baru</h3>
        <button class="close-btn" onclick="closeModal('addStudentModal')">
          <i class="bi bi-x"></i>
        </button>
      </div>
      <form id="addStudentForm">
        <div class="form-group">
          <label class="form-label" for="studentName">Nama Lengkap</label>
          <input type="text" class="form-control" id="studentName" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="studentNim">NIM</label>
          <input type="text" class="form-control" id="studentNim" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="studentDepartment">Jurusan</label>
          <select class="form-select" id="studentDepartment" required>
            <option value="">Pilih Jurusan</option>
            <option value="Teknik Informatika">Teknik Informatika</option>
            <option value="Sistem Informasi">Sistem Informasi</option>
            <option value="Teknik Elektro">Teknik Elektro</option>
            <option value="Manajemen">Manajemen</option>
            <option value="Akuntansi">Akuntansi</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="studentEmail">Email</label>
          <input type="email" class="form-control" id="studentEmail" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="studentPhone">Nomor Telepon</label>
          <input type="tel" class="form-control" id="studentPhone">
        </div>
        <div class="form-group">
          <label class="form-label" for="studentPhoto">Foto</label>
          <div class="upload-area" id="studentPhotoUpload">
            <i class="bi bi-cloud-upload upload-icon"></i>
            <p class="upload-text">Seret dan lepas foto di sini atau klik untuk mengunggah</p>
            <button type="button" class="upload-button">Pilih File</button>
            <input type="file" id="studentPhotoInput" accept="image/*" style="display: none;">
          </div>
          <div class="file-list" id="studentPhotoList"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" onclick="closeModal('addStudentModal')">Batal</button>
          <button type="submit" class="btn-primary">
            <span id="addStudentBtnText">Simpan</span>
            <span id="addStudentBtnSpinner" class="spinner" style="display: none;"></span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Add Book Modal -->
  <div id="addBookModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Tambah Buku Baru</h3>
        <button class="close-btn" onclick="closeModal('addBookModal')">
          <i class="bi bi-x"></i>
        </button>
      </div>
      <form id="addBookForm">
        <div class="form-group">
          <label class="form-label" for="bookTitle">Judul Buku</label>
          <input type="text" class="form-control" id="bookTitle" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="bookAuthor">Penulis</label>
          <input type="text" class="form-control" id="bookAuthor" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="bookPublisher">Penerbit</label>
          <input type="text" class="form-control" id="bookPublisher">
        </div>
        <div class="form-group">
          <label class="form-label" for="bookYear">Tahun Terbit</label>
          <input type="number" class="form-control" id="bookYear" min="1900" max="2024">
        </div>
        <div class="form-group">
          <label class="form-label" for="bookISBN">ISBN</label>
          <input type="text" class="form-control" id="bookISBN">
        </div>
        <div class="form-group">
          <label class="form-label" for="bookCategory">Kategori</label>
          <select class="form-select" id="bookCategory" required>
            <option value="">Pilih Kategori</option>
            <option value="Teknik Informatika">Teknik Informatika</option>
            <option value="Sistem Informasi">Sistem Informasi</option>
            <option value="Teknik Elektro">Teknik Elektro</option>
            <option value="Manajemen">Manajemen</option>
            <option value="Akuntansi">Akuntansi</option>
            <option value="Umum">Umum</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="bookCover">Sampul Buku</label>
          <div class="upload-area" id="bookCoverUpload">
            <i class="bi bi-cloud-upload upload-icon"></i>
            <p class="upload-text">Seret dan lepas sampul buku di sini atau klik untuk mengunggah</p>
            <button type="button" class="upload-button">Pilih File</button>
            <input type="file" id="bookCoverInput" accept="image/*" style="display: none;">
          </div>
          <div class="file-list" id="bookCoverList"></div>
        </div>
        <div class="form-group">
          <label class="form-label" for="bookFile">File Buku (PDF)</label>
          <div class="upload-area" id="bookFileUpload">
            <i class="bi bi-cloud-upload upload-icon"></i>
            <p class="upload-text">Seret dan lepas file PDF di sini atau klik untuk mengunggah</p>
            <button type="button" class="upload-button">Pilih File</button>
            <input type="file" id="bookFileInput" accept=".pdf" style="display: none;">
          </div>
          <div class="file-list" id="bookFileList"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" onclick="closeModal('addBookModal')">Batal</button>
          <button type="submit" class="btn-primary">
            <span id="addBookBtnText">Simpan</span>
            <span id="addBookBtnSpinner" class="spinner" style="display: none;"></span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Add Journal Modal -->
  <div id="addJournalModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Tambah Jurnal Baru</h3>
        <button class="close-btn" onclick="closeModal('addJournalModal')">
          <i class="bi bi-x"></i>
        </button>
      </div>
      <form id="addJournalForm">
        <div class="form-group">
          <label class="form-label" for="journalTitle">Judul Jurnal</label>
          <input type="text" class="form-control" id="journalTitle" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="journalAuthor">Penulis</label>
          <input type="text" class="form-control" id="journalAuthor" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="journalVolume">Volume</label>
          <input type="text" class="form-control" id="journalVolume">
        </div>
        <div class="form-group">
          <label class="form-label" for="journalIssue">Nomor</label>
          <input type="text" class="form-control" id="journalIssue">
        </div>
        <div class="form-group">
          <label class="form-label" for="journalDate">Tanggal Terbit</label>
          <input type="date" class="form-control" id="journalDate">
        </div>
        <div class="form-group">
          <label class="form-label" for="journalCategory">Kategori</label>
          <select class="form-select" id="journalCategory" required>
            <option value="">Pilih Kategori</option>
            <option value="Teknik Informatika">Teknik Informatika</option>
            <option value="Sistem Informasi">Sistem Informasi</option>
            <option value="Teknik Elektro">Teknik Elektro</option>
            <option value="Manajemen">Manajemen</option>
            <option value="Akuntansi">Akuntansi</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="journalFile">File Jurnal (PDF)</label>
          <div class="upload-area" id="journalFileUpload">
            <i class="bi bi-cloud-upload upload-icon"></i>
            <p class="upload-text">Seret dan lepas file PDF di sini atau klik untuk mengunggah</p>
            <button type="button" class="upload-button">Pilih File</button>
            <input type="file" id="journalFileInput" accept=".pdf" style="display: none;">
          </div>
          <div class="file-list" id="journalFileList"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" onclick="closeModal('addJournalModal')">Batal</button>
          <button type="submit" class="btn-primary">
            <span id="addJournalBtnText">Simpan</span>
            <span id="addJournalBtnSpinner" class="spinner" style="display: none;"></span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Add Thesis Modal -->
  <div id="addThesisModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Tambah Skripsi Baru</h3>
        <button class="close-btn" onclick="closeModal('addThesisModal')">
          <i class="bi bi-x"></i>
        </button>
      </div>
      <form id="addThesisForm">
        <div class="form-group">
          <label class="form-label" for="thesisTitle">Judul Skripsi</label>
          <input type="text" class="form-control" id="thesisTitle" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="thesisAuthor">Penulis</label>
          <input type="text" class="form-control" id="thesisAuthor" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="thesisNim">NIM</label>
          <input type="text" class="form-control" id="thesisNim" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="thesisDepartment">Jurusan</label>
          <select class="form-select" id="thesisDepartment" required>
            <option value="">Pilih Jurusan</option>
            <option value="Teknik Informatika">Teknik Informatika</option>
            <option value="Sistem Informasi">Sistem Informasi</option>
            <option value="Teknik Elektro">Teknik Elektro</option>
            <option value="Manajemen">Manajemen</option>
            <option value="Akuntansi">Akuntansi</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="thesisYear">Tahun</label>
          <input type="number" class="form-control" id="thesisYear" min="2000" max="2024">
        </div>
        <div class="form-group">
          <label class="form-label" for="thesisAdvisor">Pembimbing</label>
          <input type="text" class="form-control" id="thesisAdvisor">
        </div>
        <div class="form-group">
          <label class="form-label" for="thesisAbstract">Abstrak</label>
          <textarea class="form-control" id="thesisAbstract" rows="4"></textarea>
        </div>
        <div class="form-group">
          <label class="form-label" for="thesisFile">File Skripsi (PDF)</label>
          <div class="upload-area" id="thesisFileUpload">
            <i class="bi bi-cloud-upload upload-icon"></i>
            <p class="upload-text">Seret dan lepas file PDF di sini atau klik untuk mengunggah</p>
            <button type="button" class="upload-button">Pilih File</button>
            <input type="file" id="thesisFileInput" accept=".pdf" style="display: none;">
          </div>
          <div class="file-list" id="thesisFileList"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" onclick="closeModal('addThesisModal')">Batal</button>
          <button type="submit" class="btn-primary">
            <span id="addThesisBtnText">Simpan</span>
            <span id="addThesisBtnSpinner" class="spinner" style="display: none;"></span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // API Configuration
    const API_BASE_URL = 'https://sipora-api.polije.ac.id/api/v1'; // Ganti dengan URL API Anda
    let authToken = localStorage.getItem('authToken') || null;

    // Current user data
    let currentUser = null;
    let currentPage = 'dashboard';

    // Initialize the app
    document.addEventListener('DOMContentLoaded', function() {
      // Check authentication
      if (!authToken) {
        window.location.href = 'login.html';
        return;
      }

      // Initialize app
      initializeApp();
    });

    // Initialize app
    async function initializeApp() {
      try {
        // Setup event listeners
        setupEventListeners();
        
        // Setup upload areas
        setupUploadAreas();
        
        // Setup forms
        setupForms();
        
        // Load user profile
        await loadUserProfile();
        
        // Load initial page
        await navigateToPage('dashboard');
        
        // Load notifications
        await loadNotifications();
        
        // Setup periodic updates
        setupPeriodicUpdates();
      } catch (error) {
        console.error('Error initializing app:', error);
        showToast('error', 'Gagal memuat aplikasi. Silakan refresh halaman.');
      }
    }

    // Setup event listeners
    function setupEventListeners() {
      // Sidebar
      document.getElementById('menuBtn').addEventListener('click', openSidebar);
      document.getElementById('sidebarClose').addEventListener('click', closeSidebar);
      document.getElementById('sidebarOverlay').addEventListener('click', closeSidebar);
      
      // Navigation
      document.querySelectorAll('.menu-item[data-page], .nav-item[data-page]').forEach(item => {
        item.addEventListener('click', function(e) {
          e.preventDefault();
          const page = this.getAttribute('data-page');
          navigateToPage(page);
        });
      });
      
      // Search
      document.getElementById('searchBtn').addEventListener('click', openSearch);
      document.getElementById('searchInput').addEventListener('focus', openSearch);
      document.getElementById('searchCloseBtn').addEventListener('click', closeSearch);
      document.getElementById('searchModalInput').addEventListener('input', debounce(performSearch, 500));
      
      // Search filters
      document.querySelectorAll('.filter-chip').forEach(chip => {
        chip.addEventListener('click', function() {
          document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
          this.classList.add('active');
          performSearch();
        });
      });
      
      // Notifications
      document.getElementById('notificationBtn').addEventListener('click', toggleNotifications);
      document.getElementById('clearNotificationsBtn').addEventListener('click', clearAllNotifications);
      
      // Profile
      document.getElementById('profileBtn').addEventListener('click', () => navigateToPage('profile'));
      
      // Logout
      document.getElementById('logoutBtn').addEventListener('click', logout);
      
      // FAB
      document.getElementById('fab').addEventListener('click', showAddOptions);
      
      // Close modals when clicking outside
      document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
          if (e.target === this) {
            this.style.display = 'none';
            document.body.style.overflow = 'auto';
          }
        });
      });
    }

    // Setup upload areas
    function setupUploadAreas() {
      // Student photo upload
      setupUploadArea('studentPhotoUpload', 'studentPhotoInput', 'studentPhotoList', ['image/jpeg', 'image/png', 'image/webp']);
      
      // Book cover upload
      setupUploadArea('bookCoverUpload', 'bookCoverInput', 'bookCoverList', ['image/jpeg', 'image/png', 'image/webp']);
      
      // Book file upload
      setupUploadArea('bookFileUpload', 'bookFileInput', 'bookFileList', ['application/pdf']);
      
      // Journal file upload
      setupUploadArea('journalFileUpload', 'journalFileInput', 'journalFileList', ['application/pdf']);
      
      // Thesis file upload
      setupUploadArea('thesisFileUpload', 'thesisFileInput', 'thesisFileList', ['application/pdf']);
    }

    // Setup individual upload area
    function setupUploadArea(uploadAreaId, inputId, fileListId, allowedTypes) {
      const uploadArea = document.getElementById(uploadAreaId);
      const fileInput = document.getElementById(inputId);
      const fileList = document.getElementById(fileListId);
      
      // Click to upload
      uploadArea.addEventListener('click', () => fileInput.click());
      
      // File input change
      fileInput.addEventListener('change', () => {
        handleFiles(fileInput.files, fileList, allowedTypes);
      });
      
      // Drag and drop
      uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
      });
      
      uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
      });
      
      uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        handleFiles(e.dataTransfer.files, fileList, allowedTypes);
      });
    }

    // Handle uploaded files
    function handleFiles(files, fileListElement, allowedTypes) {
      fileListElement.innerHTML = '';
      
      for (let i = 0; i < files.length; i++) {
        const file = files[i];
        
        // Check file type
        if (allowedTypes && !allowedTypes.includes(file.type)) {
          showToast('error', `File ${file.name} tidak valid. Hanya file ${allowedTypes.join(', ')} yang diperbolehkan.`);
          continue;
        }
        
        // Check file size (max 10MB)
        if (file.size > 10 * 1024 * 1024) {
          showToast('error', `File ${file.name} terlalu besar. Maksimal 10MB.`);
          continue;
        }
        
        // Create file item
        const fileItem = document.createElement('div');
        fileItem.className = 'file-item';
        fileItem.dataset.file = file.name;
        
        const fileIcon = document.createElement('i');
        fileIcon.className = 'bi bi-file-earmark file-icon';
        
        const fileName = document.createElement('div');
        fileName.className = 'file-name';
        fileName.textContent = file.name;
        
        const fileSize = document.createElement('div');
        fileSize.className = 'file-size';
        fileSize.textContent = formatFileSize(file.size);
        
        const fileRemove = document.createElement('button');
        fileRemove.className = 'file-remove';
        fileRemove.innerHTML = '<i class="bi bi-x"></i>';
        fileRemove.addEventListener('click', () => {
          fileItem.remove();
        });
        
        fileItem.appendChild(fileIcon);
        fileItem.appendChild(fileName);
        fileItem.appendChild(fileSize);
        fileItem.appendChild(fileRemove);
        
        fileListElement.appendChild(fileItem);
      }
    }

    // Format file size
    function formatFileSize(bytes) {
      if (bytes === 0) return '0 Bytes';
      
      const k = 1024;
      const sizes = ['Bytes', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Setup forms
    function setupForms() {
      // Add student form
      document.getElementById('addStudentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        addStudent();
      });
      
      // Add book form
      document.getElementById('addBookForm').addEventListener('submit', function(e) {
        e.preventDefault();
        addBook();
      });
      
      // Add journal form
      document.getElementById('addJournalForm').addEventListener('submit', function(e) {
        e.preventDefault();
        addJournal();
      });
      
      // Add thesis form
      document.getElementById('addThesisForm').addEventListener('submit', function(e) {
        e.preventDefault();
        addThesis();
      });
    }

    // Setup periodic updates
    function setupPeriodicUpdates() {
      // Update notifications every 30 seconds
      setInterval(async () => {
        await loadNotifications();
      }, 30000);
      
      // Update stats every 5 minutes
      setInterval(async () => {
        if (currentPage === 'dashboard') {
          await loadDashboard();
        }
      }, 300000);
    }

    // Load user profile
    async function loadUserProfile() {
      try {
        const response = await apiRequest('/profile');
        currentUser = response.data;
        
        // Update UI
        document.getElementById('profileName').textContent = currentUser.name;
        document.getElementById('profileRole').textContent = currentUser.role;
        document.getElementById('profileAvatar').textContent = currentUser.name.split(' ').map(n => n[0]).join('').toUpperCase();
      } catch (error) {
        console.error('Error loading user profile:', error);
        showToast('error', 'Gagal memuat profil pengguna');
      }
    }

    // Navigate to page
    async function navigateToPage(page) {
      // Update active menu items
      document.querySelectorAll('.menu-item, .nav-item').forEach(item => {
        item.classList.remove('active');
        if (item.getAttribute('data-page') === page) {
          item.classList.add('active');
        }
      });
      
      // Close sidebar on mobile
      if (window.innerWidth < 768) {
        closeSidebar();
      }
      
      // Update current page
      currentPage = page;
      
      // Show loading state
      document.getElementById('mainContent').innerHTML = `
        <div class="loading-state">
          <div class="loading-spinner"></div>
          <div class="loading-text">Memuat halaman...</div>
        </div>
      `;
      
      // Load page content
      try {
        switch (page) {
          case 'dashboard':
            await loadDashboard();
            break;
          case 'students':
            await loadStudents();
            break;
          case 'lecturers':
            await loadLecturers();
            break;
          case 'departments':
            await loadDepartments();
            break;
          case 'books':
            await loadBooks();
            break;
          case 'journals':
            await loadJournals();
            break;
          case 'thesis':
            await loadThesis();
            break;
          case 'library':
            await loadLibrary();
            break;
          case 'activities':
            await loadActivities();
            break;
          case 'profile':
            await loadProfile();
            break;
          case 'settings':
            await loadSettings();
            break;
          default:
            await loadDashboard();
        }
      } catch (error) {
        console.error(`Error loading page ${page}:`, error);
        document.getElementById('mainContent').innerHTML = `
          <div class="empty-state">
            <i class="bi bi-exclamation-triangle empty-state-icon"></i>
            <h3 class="empty-state-title">Terjadi Kesalahan</h3>
            <p class="empty-state-text">Gagal memuat halaman. Silakan coba lagi.</p>
            <button class="empty-state-action" onclick="navigateToPage('${page}')">Coba Lagi</button>
          </div>
        `;
        showToast('error', 'Gagal memuat halaman');
      }
    }

    // Load dashboard
    async function loadDashboard() {
      try {
        // Fetch stats data
        const statsResponse = await apiRequest('/stats');
        const statsData = statsResponse.data;
        
        // Fetch recent students
        const studentsResponse = await apiRequest('/students?limit=6&sort=created_at&order=desc');
        const studentsData = studentsResponse.data;
        
        // Fetch recent library items
        const libraryResponse = await apiRequest('/library?limit=6&sort=created_at&order=desc');
        const libraryData = libraryResponse.data;
        
        // Render dashboard
        renderDashboard(statsData, studentsData, libraryData);
      } catch (error) {
        console.error('Error loading dashboard:', error);
        throw error;
      }
    }

    // Render dashboard
    function renderDashboard(statsData, studentsData, libraryData) {
      const mainContent = document.getElementById('mainContent');
      
      mainContent.innerHTML = `
        <!-- Filter Bar -->
        <div class="filter-bar">
          <div class="filter-group">
            <span class="filter-label">Filter:</span>
            <select class="filter-select" id="dashboardFilter">
              <option value="">Semua Kategori</option>
              <option value="Teknik Informatika">Teknik Informatika</option>
              <option value="Sistem Informasi">Sistem Informasi</option>
              <option value="Teknik Elektro">Teknik Elektro</option>
              <option value="Manajemen">Manajemen</option>
            </select>
          </div>
          <div class="filter-group">
            <span class="filter-label">Urutkan:</span>
            <select class="filter-select" id="dashboardSort">
              <option value="newest">Terbaru</option>
              <option value="oldest">Terlama</option>
              <option value="name-asc">Nama A-Z</option>
              <option value="name-desc">Nama Z-A</option>
            </select>
          </div>
          <div class="view-toggle">
            <button class="view-btn active">
              <i class="bi bi-grid-3x3-gap"></i>
            </button>
            <button class="view-btn">
              <i class="bi bi-list-ul"></i>
            </button>
          </div>
        </div>

        <!-- Modern Banner -->
        <section class="banner-section">
          <div class="banner-carousel">
            <div class="banner-slide active">
              <div class="banner-content">
                <h2>Selamat Datang di SIPORA</h2>
                <p>Sistem Informasi Polije Terintegrasi yang modern dan efisien</p>
                <button class="banner-btn" onclick="navigateToPage('dashboard')">Jelajahi Sekarang</button>
              </div>
            </div>
            <div class="banner-slide">
              <div class="banner-content">
                <h2>Perpustakaan Digital</h2>
                <p>Akses ribuan koleksi buku dan jurnal akademik</p>
                <button class="banner-btn" onclick="navigateToPage('library')">Buka Perpustakaan</button>
              </div>
            </div>
            <div class="banner-slide">
              <div class="banner-content">
                <h2>Aktivitas Terbaru</h2>
                <p>Pantau perkembangan dan kegiatan kampus real-time</p>
                <button class="banner-btn" onclick="navigateToPage('activities')">Lihat Aktivitas</button>
              </div>
            </div>
            <div class="banner-dots">
              <span class="banner-dot active" data-slide="0"></span>
              <span class="banner-dot" data-slide="1"></span>
              <span class="banner-dot" data-slide="2"></span>
            </div>
          </div>
        </section>

        <!-- Stats Section -->
        <section class="stats-section">
          <div class="stats-header">
            <h3 class="stats-title">Statistik Hari Ini</h3>
            <div class="stats-timer">
              <i class="bi bi-clock"></i>
              <span>Update: ${new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}</span>
            </div>
          </div>
          <div class="stats-grid">
            <div class="stat-card" onclick="navigateToPage('students')">
              <div class="stat-icon">👥</div>
              <div class="stat-label">Mahasiswa</div>
              <div class="stat-value">${statsData.students || 0}</div>
            </div>
            <div class="stat-card" onclick="navigateToPage('lecturers')">
              <div class="stat-icon">👨‍🏫</div>
              <div class="stat-label">Dosen</div>
              <div class="stat-value">${statsData.lecturers || 0}</div>
            </div>
            <div class="stat-card" onclick="navigateToPage('books')">
              <div class="stat-icon">📚</div>
              <div class="stat-label">Buku</div>
              <div class="stat-value">${statsData.books || 0}</div>
            </div>
            <div class="stat-card" onclick="navigateToPage('journals')">
              <div class="stat-icon">📄</div>
              <div class="stat-label">Jurnal</div>
              <div class="stat-value">${statsData.journals || 0}</div>
            </div>
            <div class="stat-card" onclick="navigateToPage('thesis')">
              <div class="stat-icon">🎓</div>
              <div class="stat-label">Skripsi</div>
              <div class="stat-value">${statsData.thesis || 0}</div>
            </div>
            <div class="stat-card" onclick="navigateToPage('archives')">
              <div class="stat-icon">📁</div>
              <div class="stat-label">Arsip</div>
              <div class="stat-value">${statsData.archives || 0}</div>
            </div>
          </div>
        </section>

        <!-- Mahasiswa Section -->
        <section class="cards-section">
          <div class="section-header">
            <h2 class="section-title">Mahasiswa Baru</h2>
            <div class="section-actions">
              <a href="#" class="see-all-btn" onclick="navigateToPage('students')">Lihat Semua →</a>
              <button class="add-btn" onclick="openModal('addStudentModal')">
                <i class="bi bi-plus"></i>
                Tambah
              </button>
            </div>
          </div>
          <div class="cards-grid" id="studentsGrid">
            ${studentsData.length > 0 ? studentsData.map(student => `
              <div class="card" onclick="viewStudentDetails(${student.id})">
                <div class="card-thumbnail">
                  <img src="${student.photo || `https://picsum.photos/seed/student${student.id}/160/160.jpg`}" alt="${student.name}">
                  <span class="card-badge">Baru</span>
                </div>
                <div class="card-info">
                  <div class="card-title">${student.name}</div>
                  <div class="card-meta">
                    <i class="bi bi-mortarboard"></i>
                    <span>${student.department} • ${student.nim}</span>
                  </div>
                </div>
              </div>
            `).join('') : `
              <div class="empty-state" style="grid-column: 1 / -1;">
                <i class="bi bi-people empty-state-icon"></i>
                <h3 class="empty-state-title">Belum Ada Mahasiswa</h3>
                <p class="empty-state-text">Tambahkan mahasiswa baru untuk memulai</p>
                <button class="empty-state-action" onclick="openModal('addStudentModal')">Tambah Mahasiswa</button>
              </div>
            `}
          </div>
        </section>

        <!-- Perpustakaan Section -->
        <section class="cards-section">
          <div class="section-header">
            <h2 class="section-title">Koleksi Perpustakaan</h2>
            <div class="section-actions">
              <a href="#" class="see-all-btn" onclick="navigateToPage('library')">Lihat Semua →</a>
              <button class="add-btn" onclick="showLibraryAddOptions()">
                <i class="bi bi-plus"></i>
                Tambah
              </button>
            </div>
          </div>
          <div class="cards-grid" id="libraryGrid">
            ${libraryData.length > 0 ? libraryData.map(item => {
              let icon, badge;
              
              if (item.type === 'book') {
                icon = '<i class="bi bi-book"></i>';
                badge = 'Buku';
              } else if (item.type === 'journal') {
                icon = '<i class="bi bi-journal-text"></i>';
                badge = 'Jurnal';
              } else if (item.type === 'thesis') {
                icon = '<i class="bi bi-file-earmark-text"></i>';
                badge = 'Skripsi';
              } else {
                icon = '<i class="bi bi-folder"></i>';
                badge = 'Arsip';
              }
              
              return `
                <div class="card" onclick="viewLibraryItemDetails(${item.id})">
                  <div class="card-thumbnail">
                    <img src="${item.cover || `https://picsum.photos/seed/${item.type}${item.id}/160/160.jpg`}" alt="${item.title}">
                    <span class="card-badge">${badge}</span>
                    <div class="card-type-icon">${icon}</div>
                  </div>
                  <div class="card-info">
                    <div class="card-title">${item.title}</div>
                    <div class="card-meta">
                      ${item.type === 'book' ? 
                        `<i class="bi bi-person"></i><span>${item.author}</span>` : 
                        item.type === 'journal' ? 
                          `<i class="bi bi-calendar3"></i><span>Vol. ${item.volume} No. ${item.issue} • ${formatDate(item.date)}</span>` : 
                          item.type === 'thesis' ? 
                            `<i class="bi bi-person"></i><span>${item.author}</span>` : 
                            `<i class="bi bi-building"></i><span>${item.source}</span>`
                      }
                    </div>
                  </div>
                </div>
              `;
            }).join('') : `
              <div class="empty-state" style="grid-column: 1 / -1;">
                <i class="bi bi-book empty-state-icon"></i>
                <h3 class="empty-state-title">Belum Ada Koleksi</h3>
                <p class="empty-state-text">Tambahkan buku, jurnal, atau skripsi untuk memulai</p>
                <button class="empty-state-action" onclick="showLibraryAddOptions()">Tambah Koleksi</button>
              </div>
            `}
          </div>
        </section>

        <!-- Quick Actions List -->
        <section class="list-section">
          <div class="section-header">
            <h2 class="section-title">Aksi Cepat</h2>
          </div>
          <div class="list-item" onclick="openModal('addStudentModal')">
            <div class="list-icon">
              <i class="bi bi-plus-circle"></i>
            </div>
            <div class="list-content">
              <div class="list-title">Tambah Mahasiswa Baru</div>
              <div class="list-subtitle">Daftarkan mahasiswa baru ke sistem</div>
            </div>
            <i class="bi bi-chevron-right list-arrow"></i>
          </div>
          <div class="list-item" onclick="showLibraryAddOptions()">
            <div class="list-icon">
              <i class="bi bi-upload"></i>
            </div>
            <div class="list-content">
              <div class="list-title">Unggah Dokumen</div>
              <div class="list-subtitle">Tambah buku, jurnal, atau skripsi</div>
            </div>
            <i class="bi bi-chevron-right list-arrow"></i>
          </div>
          <div class="list-item" onclick="navigateToPage('stats')">
            <div class="list-icon">
              <i class="bi bi-graph-up"></i>
            </div>
            <div class="list-content">
              <div class="list-title">Lihat Statistik</div>
              <div class="list-subtitle">Analisis data kampus</div>
            </div>
            <i class="bi bi-chevron-right list-arrow"></i>
          </div>
          <div class="list-item" onclick="navigateToPage('announcements')">
            <div class="list-icon">
              <i class="bi bi-envelope"></i>
            </div>
            <div class="list-content">
              <div class="list-title">Kirim Pengumuman</div>
              <div class="list-subtitle">Buat pengumuman untuk semua</div>
            </div>
            <i class="bi bi-chevron-right list-arrow"></i>
          </div>
        </section>
      `;
      
      // Re-initialize banner carousel
      setupBannerCarousel();
      
      // Setup filter and sort event listeners
      document.getElementById('dashboardFilter').addEventListener('change', applyFilters);
      document.getElementById('dashboardSort').addEventListener('change', applyFilters);
    }

    // Load students page
    async function loadStudents() {
      try {
        // Fetch students data
        const response = await apiRequest('/students');
        const studentsData = response.data;
        
        // Render students page
        renderStudentsPage(studentsData);
      } catch (error) {
        console.error('Error loading students:', error);
        throw error;
      }
    }

    // Render students page
    function renderStudentsPage(studentsData) {
      const mainContent = document.getElementById('mainContent');
      
      mainContent.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h1 class="h3 mb-0">Data Mahasiswa</h1>
          <button class="add-btn" onclick="openModal('addStudentModal')">
            <i class="bi bi-plus"></i>
            Tambah Mahasiswa
          </button>
        </div>
        
        <!-- Filter Bar -->
        <div class="filter-bar">
          <div class="filter-group">
            <span class="filter-label">Filter:</span>
            <select class="filter-select" id="studentsFilter">
              <option value="">Semua Jurusan</option>
              <option value="Teknik Informatika">Teknik Informatika</option>
              <option value="Sistem Informasi">Sistem Informasi</option>
              <option value="Teknik Elektro">Teknik Elektro</option>
              <option value="Manajemen">Manajemen</option>
              <option value="Akuntansi">Akuntansi</option>
            </select>
          </div>
          <div class="filter-group">
            <span class="filter-label">Urutkan:</span>
            <select class="filter-select" id="studentsSort">
              <option value="name-asc">Nama A-Z</option>
              <option value="name-desc">Nama Z-A</option>
              <option value="nim-asc">NIM A-Z</option>
              <option value="nim-desc">NIM Z-A</option>
              <option value="newest">Terbaru</option>
              <option value="oldest">Terlama</option>
            </select>
          </div>
          <div class="filter-group ms-auto">
            <div class="input-group">
              <input type="text" class="form-control" placeholder="Cari mahasiswa..." id="studentsSearch">
              <button class="btn btn-primary" type="button" id="studentsSearchBtn">
                <i class="bi bi-search"></i>
              </button>
            </div>
          </div>
        </div>
        
        ${studentsData.length > 0 ? `
          <!-- Students Table -->
          <div class="card shadow-sm">
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Foto</th>
                      <th>NIM</th>
                      <th>Nama</th>
                      <th>Jurusan</th>
                      <th>Email</th>
                      <th>No. Telepon</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody id="studentsTableBody">
                    ${studentsData.map(student => `
                      <tr>
                        <td>
                          <img src="${student.photo || `https://picsum.photos/seed/student${student.id}/40/40.jpg`}" alt="${student.name}" class="rounded-circle" width="40" height="40">
                        </td>
                        <td>${student.nim}</td>
                        <td>${student.name}</td>
                        <td>${student.department}</td>
                        <td>${student.email}</td>
                        <td>${student.phone || '-'}</td>
                        <td>
                          <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewStudentDetails(${student.id})">
                              <i class="bi bi-eye"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="editStudent(${student.id})">
                              <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteStudent(${student.id})">
                              <i class="bi bi-trash"></i>
                            </button>
                          </div>
                        </td>
                      </tr>
                    `).join('')}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        ` : `
          <div class="empty-state">
            <i class="bi bi-people empty-state-icon"></i>
            <h3 class="empty-state-title">Belum Ada Mahasiswa</h3>
            <p class="empty-state-text">Tambahkan mahasiswa baru untuk memulai</p>
            <button class="empty-state-action" onclick="openModal('addStudentModal')">Tambah Mahasiswa</button>
          </div>
        `}
      `;
      
      // Setup event listeners
      document.getElementById('studentsFilter').addEventListener('change', filterStudents);
      document.getElementById('studentsSort').addEventListener('change', sortStudents);
      document.getElementById('studentsSearchBtn').addEventListener('click', searchStudents);
      document.getElementById('studentsSearch').addEventListener('keyup', function(e) {
        if (e.key === 'Enter') searchStudents();
      });
    }

    // Load library page
    async function loadLibrary() {
      try {
        // Fetch library data
        const response = await apiRequest('/library');
        const libraryData = response.data;
        
        // Render library page
        renderLibraryPage(libraryData);
      } catch (error) {
        console.error('Error loading library:', error);
        throw error;
      }
    }

    // Render library page
    function renderLibraryPage(libraryData) {
      const mainContent = document.getElementById('mainContent');
      
      mainContent.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h1 class="h3 mb-0">Koleksi Perpustakaan</h1>
          <button class="add-btn" onclick="showLibraryAddOptions()">
            <i class="bi bi-plus"></i>
            Tambah Koleksi
          </button>
        </div>
        
        <!-- Filter Bar -->
        <div class="filter-bar">
          <div class="filter-group">
            <span class="filter-label">Filter:</span>
            <select class="filter-select" id="libraryTypeFilter">
              <option value="">Semua Tipe</option>
              <option value="book">Buku</option>
              <option value="journal">Jurnal</option>
              <option value="thesis">Skripsi</option>
              <option value="archive">Arsip</option>
            </select>
          </div>
          <div class="filter-group">
            <span class="filter-label">Kategori:</span>
            <select class="filter-select" id="libraryCategoryFilter">
              <option value="">Semua Kategori</option>
              <option value="Teknik Informatika">Teknik Informatika</option>
              <option value="Sistem Informasi">Sistem Informasi</option>
              <option value="Teknik Elektro">Teknik Elektro</option>
              <option value="Manajemen">Manajemen</option>
              <option value="Akuntansi">Akuntansi</option>
            </select>
          </div>
          <div class="filter-group">
            <span class="filter-label">Urutkan:</span>
            <select class="filter-select" id="librarySort">
              <option value="title-asc">Judul A-Z</option>
              <option value="title-desc">Judul Z-A</option>
              <option value="author-asc">Penulis A-Z</option>
              <option value="author-desc">Penulis Z-A</option>
              <option value="newest">Terbaru</option>
              <option value="oldest">Terlama</option>
            </select>
          </div>
          <div class="filter-group ms-auto">
            <div class="input-group">
              <input type="text" class="form-control" placeholder="Cari koleksi..." id="librarySearch">
              <button class="btn btn-primary" type="button" id="librarySearchBtn">
                <i class="bi bi-search"></i>
              </button>
            </div>
          </div>
        </div>
        
        ${libraryData.length > 0 ? `
          <!-- Library Grid -->
          <div class="cards-grid" id="libraryGrid">
            ${libraryData.map(item => {
              let icon, badge;
              
              if (item.type === 'book') {
                icon = '<i class="bi bi-book"></i>';
                badge = 'Buku';
              } else if (item.type === 'journal') {
                icon = '<i class="bi bi-journal-text"></i>';
                badge = 'Jurnal';
              } else if (item.type === 'thesis') {
                icon = '<i class="bi bi-file-earmark-text"></i>';
                badge = 'Skripsi';
              } else {
                icon = '<i class="bi bi-folder"></i>';
                badge = 'Arsip';
              }
              
              return `
                <div class="card" onclick="viewLibraryItemDetails(${item.id})">
                  <div class="card-thumbnail">
                    <img src="${item.cover || `https://picsum.photos/seed/${item.type}${item.id}/160/160.jpg`}" alt="${item.title}">
                    <span class="card-badge">${badge}</span>
                    <div class="card-type-icon">${icon}</div>
                  </div>
                  <div class="card-info">
                    <div class="card-title">${item.title}</div>
                    <div class="card-meta">
                      ${item.type === 'book' ? 
                        `<i class="bi bi-person"></i><span>${item.author}</span>` : 
                        item.type === 'journal' ? 
                          `<i class="bi bi-calendar3"></i><span>Vol. ${item.volume} No. ${item.issue} • ${formatDate(item.date)}</span>` : 
                          item.type === 'thesis' ? 
                            `<i class="bi bi-person"></i><span>${item.author}</span>` : 
                            `<i class="bi bi-building"></i><span>${item.source}</span>`
                      }
                    </div>
                  </div>
                </div>
              `;
            }).join('')}
          </div>
        ` : `
          <div class="empty-state">
            <i class="bi bi-book empty-state-icon"></i>
            <h3 class="empty-state-title">Belum Ada Koleksi</h3>
            <p class="empty-state-text">Tambahkan buku, jurnal, atau skripsi untuk memulai</p>
            <button class="empty-state-action" onclick="showLibraryAddOptions()">Tambah Koleksi</button>
          </div>
        `}
      `;
      
      // Setup event listeners
      document.getElementById('libraryTypeFilter').addEventListener('change', filterLibrary);
      document.getElementById('libraryCategoryFilter').addEventListener('change', filterLibrary);
      document.getElementById('librarySort').addEventListener('change', sortLibrary);
      document.getElementById('librarySearchBtn').addEventListener('click', searchLibrary);
      document.getElementById('librarySearch').addEventListener('keyup', function(e) {
        if (e.key === 'Enter') searchLibrary();
      });
    }

    // Load activities page
    async function loadActivities() {
      try {
        // Fetch activities data
        const response = await apiRequest('/activities');
        const activitiesData = response.data;
        
        // Render activities page
        renderActivitiesPage(activitiesData);
      } catch (error) {
        console.error('Error loading activities:', error);
        throw error;
      }
    }

    // Render activities page
    function renderActivitiesPage(activitiesData) {
      const mainContent = document.getElementById('mainContent');
      
      mainContent.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h1 class="h3 mb-0">Aktivitas Terbaru</h1>
          <button class="add-btn" onclick="navigateToPage('add-activity')">
            <i class="bi bi-plus"></i>
            Tambah Aktivitas
          </button>
        </div>
        
        ${activitiesData.length > 0 ? `
          <!-- Activities Timeline -->
          <div class="card shadow-sm">
            <div class="card-body">
              <div class="timeline">
                ${activitiesData.map(activity => `
                  <div class="timeline-item">
                    <div class="timeline-marker ${getActivityClass(activity.type)}"></div>
                    <div class="timeline-content">
                      <div class="d-flex justify-content-between align-items-start">
                        <div>
                          <h6 class="mb-1">${activity.title}</h6>
                          <p class="mb-1">${activity.description}</p>
                          <small class="text-muted">
                            <i class="bi bi-person"></i> ${activity.user} • 
                            <i class="bi bi-clock"></i> ${formatDateTime(activity.timestamp)}
                          </small>
                        </div>
                        ${activity.link ? `
                          <a href="${activity.link}" class="btn btn-sm btn-outline-primary">Lihat Detail</a>
                        ` : ''}
                      </div>
                    </div>
                  </div>
                `).join('')}
              </div>
            </div>
          </div>
        ` : `
          <div class="empty-state">
            <i class="bi bi-activity empty-state-icon"></i>
            <h3 class="empty-state-title">Belum Ada Aktivitas</h3>
            <p class="empty-state-text">Aktivitas akan muncul di sini saat ada perubahan data</p>
          </div>
        `}
      `;
    }

    // Load profile page
    async function loadProfile() {
      try {
        // Fetch user profile data
        const response = await apiRequest('/profile');
        const profileData = response.data;
        
        // Render profile page
        renderProfilePage(profileData);
      } catch (error) {
        console.error('Error loading profile:', error);
        throw error;
      }
    }

    // Render profile page
    function renderProfilePage(profileData) {
      const mainContent = document.getElementById('mainContent');
      
      mainContent.innerHTML = `
        <div class="row">
          <div class="col-md-4">
            <div class="card shadow-sm mb-4">
              <div class="card-body text-center">
                <img src="${profileData.avatar || `https://picsum.photos/seed/avatar${profileData.id}/200/200.jpg`}" alt="${profileData.name}" class="rounded-circle mb-3" width="150" height="150">
                <h4>${profileData.name}</h4>
                <p class="text-muted">${profileData.role}</p>
                <div class="d-flex justify-content-center mb-3">
                  <button class="btn btn-primary me-2" onclick="openEditProfileModal()">
                    <i class="bi bi-pencil"></i> Edit Profil
                  </button>
                  <button class="btn btn-outline-danger" onclick="openChangePasswordModal()">
                    <i class="bi bi-key"></i> Ganti Password
                  </button>
                </div>
                <hr>
                <div class="text-start">
                  <p><strong>Email:</strong> ${profileData.email}</p>
                  <p><strong>Telepon:</strong> ${profileData.phone || '-'}</p>
                  <p><strong>Alamat:</strong> ${profileData.address || '-'}</p>
                  <p><strong>Bergabung:</strong> ${formatDate(profileData.joinDate)}</p>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-8">
            <div class="card shadow-sm mb-4">
              <div class="card-header bg-white">
                <ul class="nav nav-tabs card-header-tabs">
                  <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#activity-tab">Aktivitas</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#settings-tab">Pengaturan</a>
                  </li>
                </ul>
              </div>
              <div class="card-body">
                <div class="tab-content">
                  <div class="tab-pane fade show active" id="activity-tab">
                    <h5 class="mb-3">Aktivitas Terbaru</h5>
                    ${profileData.recentActivities && profileData.recentActivities.length > 0 ? `
                      <div class="timeline">
                        ${profileData.recentActivities.map(activity => `
                          <div class="timeline-item">
                            <div class="timeline-marker ${getActivityClass(activity.type)}"></div>
                            <div class="timeline-content">
                              <h6 class="mb-1">${activity.title}</h6>
                              <p class="mb-1">${activity.description}</p>
                              <small class="text-muted">
                                <i class="bi bi-clock"></i> ${formatDateTime(activity.timestamp)}
                              </small>
                            </div>
                          </div>
                        `).join('')}
                      </div>
                    ` : `
                      <div class="empty-state">
                        <i class="bi bi-activity empty-state-icon"></i>
                        <p class="empty-state-text">Belum ada aktivitas</p>
                      </div>
                    `}
                  </div>
                  <div class="tab-pane fade" id="settings-tab">
                    <h5 class="mb-3">Pengaturan</h5>
                    <form id="settingsForm">
                      <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="emailNotifications" ${profileData.settings && profileData.settings.emailNotifications ? 'checked' : ''}>
                        <label class="form-check-label" for="emailNotifications">
                          Notifikasi Email
                        </label>
                      </div>
                      <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="pushNotifications" ${profileData.settings && profileData.settings.pushNotifications ? 'checked' : ''}>
                        <label class="form-check-label" for="pushNotifications">
                          Notifikasi Push
                        </label>
                      </div>
                      <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="darkMode" ${profileData.settings && profileData.settings.darkMode ? 'checked' : ''}>
                        <label class="form-check-label" for="darkMode">
                          Mode Gelap
                        </label>
                      </div>
                      <div class="form-group mb-3">
                        <label for="language" class="form-label">Bahasa</label>
                        <select class="form-select" id="language">
                          <option value="id" ${!profileData.settings || profileData.settings.language === 'id' ? 'selected' : ''}>Indonesia</option>
                          <option value="en" ${profileData.settings && profileData.settings.language === 'en' ? 'selected' : ''}>English</option>
                        </select>
                      </div>
                      <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      `;
      
      // Setup settings form
      document.getElementById('settingsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveSettings();
      });
    }

    // Load other pages (placeholder functions)
    async function loadLecturers() {
      // Implementation similar to loadStudents
      document.getElementById('mainContent').innerHTML = `
        <div class="empty-state">
          <i class="bi bi-person-badge empty-state-icon"></i>
          <h3 class="empty-state-title">Halaman Dosen</h3>
          <p class="empty-state-text">Halaman ini sedang dalam pengembangan</p>
        </div>
      `;
    }

    async function loadDepartments() {
      // Implementation for departments page
      document.getElementById('mainContent').innerHTML = `
        <div class="empty-state">
          <i class="bi bi-building empty-state-icon"></i>
          <h3 class="empty-state-title">Halaman Jurusan</h3>
          <p class="empty-state-text">Halaman ini sedang dalam pengembangan</p>
        </div>
      `;
    }

    async function loadBooks() {
      // Implementation similar to loadLibrary but filtered for books only
      try {
        const response = await apiRequest('/books');
        const booksData = response.data;
        
        // Render books page
        document.getElementById('mainContent').innerHTML = `
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Daftar Buku</h1>
            <button class="add-btn" onclick="openModal('addBookModal')">
              <i class="bi bi-plus"></i>
              Tambah Buku
            </button>
          </div>
          
          ${booksData.length > 0 ? `
            <div class="cards-grid">
              ${booksData.map(book => `
                <div class="card" onclick="viewBookDetails(${book.id})">
                  <div class="card-thumbnail">
                    <img src="${book.cover || `https://picsum.photos/seed/book${book.id}/160/160.jpg`}" alt="${book.title}">
                    <span class="card-badge">Buku</span>
                  </div>
                  <div class="card-info">
                    <div class="card-title">${book.title}</div>
                    <div class="card-meta">
                      <i class="bi bi-person"></i>
                      <span>${book.author}</span>
                    </div>
                  </div>
                </div>
              `).join('')}
            </div>
          ` : `
            <div class="empty-state">
              <i class="bi bi-book empty-state-icon"></i>
              <h3 class="empty-state-title">Belum Ada Buku</h3>
              <p class="empty-state-text">Tambahkan buku untuk memulai</p>
              <button class="empty-state-action" onclick="openModal('addBookModal')">Tambah Buku</button>
            </div>
          `}
        `;
      } catch (error) {
        console.error('Error loading books:', error);
        throw error;
      }
    }

    async function loadJournals() {
      // Implementation similar to loadLibrary but filtered for journals only
      try {
        const response = await apiRequest('/journals');
        const journalsData = response.data;
        
        // Render journals page
        document.getElementById('mainContent').innerHTML = `
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Daftar Jurnal</h1>
            <button class="add-btn" onclick="openModal('addJournalModal')">
              <i class="bi bi-plus"></i>
              Tambah Jurnal
            </button>
          </div>
          
          ${journalsData.length > 0 ? `
            <div class="cards-grid">
              ${journalsData.map(journal => `
                <div class="card" onclick="viewJournalDetails(${journal.id})">
                  <div class="card-thumbnail">
                    <img src="${journal.cover || `https://picsum.photos/seed/journal${journal.id}/160/160.jpg`}" alt="${journal.title}">
                    <span class="card-badge">Jurnal</span>
                  </div>
                  <div class="card-info">
                    <div class="card-title">${journal.title}</div>
                    <div class="card-meta">
                      <i class="bi bi-calendar3"></i>
                      <span>Vol. ${journal.volume} No. ${journal.issue} • ${formatDate(journal.date)}</span>
                    </div>
                  </div>
                </div>
              `).join('')}
            </div>
          ` : `
            <div class="empty-state">
              <i class="bi bi-journal-text empty-state-icon"></i>
              <h3 class="empty-state-title">Belum Ada Jurnal</h3>
              <p class="empty-state-text">Tambahkan jurnal untuk memulai</p>
              <button class="empty-state-action" onclick="openModal('addJournalModal')">Tambah Jurnal</button>
            </div>
          `}
        `;
      } catch (error) {
        console.error('Error loading journals:', error);
        throw error;
      }
    }

    async function loadThesis() {
      // Implementation similar to loadLibrary but filtered for thesis only
      try {
        const response = await apiRequest('/thesis');
        const thesisData = response.data;
        
        // Render thesis page
        document.getElementById('mainContent').innerHTML = `
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Daftar Skripsi</h1>
            <button class="add-btn" onclick="openModal('addThesisModal')">
              <i class="bi bi-plus"></i>
              Tambah Skripsi
            </button>
          </div>
          
          ${thesisData.length > 0 ? `
            <div class="cards-grid">
              ${thesisData.map(thesis => `
                <div class="card" onclick="viewThesisDetails(${thesis.id})">
                  <div class="card-thumbnail">
                    <img src="${thesis.cover || `https://picsum.photos/seed/thesis${thesis.id}/160/160.jpg`}" alt="${thesis.title}">
                    <span class="card-badge">Skripsi</span>
                  </div>
                  <div class="card-info">
                    <div class="card-title">${thesis.title}</div>
                    <div class="card-meta">
                      <i class="bi bi-person"></i>
                      <span>${thesis.author}</span>
                    </div>
                  </div>
                </div>
              `).join('')}
            </div>
          ` : `
            <div class="empty-state">
              <i class="bi bi-file-earmark-text empty-state-icon"></i>
              <h3 class="empty-state-title">Belum Ada Skripsi</h3>
              <p class="empty-state-text">Tambahkan skripsi untuk memulai</p>
              <button class="empty-state-action" onclick="openModal('addThesisModal')">Tambah Skripsi</button>
            </div>
          `}
        `;
      } catch (error) {
        console.error('Error loading thesis:', error);
        throw error;
      }
    }

    async function loadSettings() {
      // Implementation for settings page
      document.getElementById('mainContent').innerHTML = `
        <div class="empty-state">
          <i class="bi bi-gear empty-state-icon"></i>
          <h3 class="empty-state-title">Halaman Pengaturan</h3>
          <p class="empty-state-text">Halaman ini sedang dalam pengembangan</p>
        </div>
      `;
    }

    // API request function
    async function apiRequest(endpoint, options = {}) {
      const url = `${API_BASE_URL}${endpoint}`;
      
      // Default headers
      const headers = {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${authToken}`
      };
      
      // Merge options
      options = {
        headers,
        ...options
      };
      
      // Handle FormData (for file uploads)
      if (options.body instanceof FormData) {
        delete options.headers['Content-Type'];
      }
      
      try {
        const response = await fetch(url, options);
        
        // Handle unauthorized
        if (response.status === 401) {
          localStorage.removeItem('authToken');
          window.location.href = 'login.html';
          return;
        }
        
        // Handle other errors
        if (!response.ok) {
          const errorData = await response.json();
          throw new Error(errorData.message || 'Terjadi kesalahan');
        }
        
        return await response.json();
      } catch (error) {
        console.error('API request error:', error);
        throw error;
      }
    }

    // Add student
    async function addStudent() {
      const form = document.getElementById('addStudentForm');
      const formData = new FormData(form);
      
      // Show loading state
      document.getElementById('addStudentBtnText').style.display = 'none';
      document.getElementById('addStudentBtnSpinner').style.display = 'inline-block';
      
      try {
        // Add student
        await apiRequest('/students', {
          method: 'POST',
          body: formData
        });
        
        // Close modal
        closeModal('addStudentModal');
        
        // Reset form
        form.reset();
        document.getElementById('studentPhotoList').innerHTML = '';
        
        // Reload current page
        await navigateToPage(currentPage);
        
        showToast('success', 'Mahasiswa berhasil ditambahkan');
      } catch (error) {
        console.error('Error adding student:', error);
        showToast('error', error.message || 'Gagal menambahkan mahasiswa');
      } finally {
        // Hide loading state
        document.getElementById('addStudentBtnText').style.display = 'inline';
        document.getElementById('addStudentBtnSpinner').style.display = 'none';
      }
    }

    // Add book
    async function addBook() {
      const form = document.getElementById('addBookForm');
      const formData = new FormData(form);
      
      // Show loading state
      document.getElementById('addBookBtnText').style.display = 'none';
      document.getElementById('addBookBtnSpinner').style.display = 'inline-block';
      
      try {
        // Add book
        await apiRequest('/books', {
          method: 'POST',
          body: formData
        });
        
        // Close modal
        closeModal('addBookModal');
        
        // Reset form
        form.reset();
        document.getElementById('bookCoverList').innerHTML = '';
        document.getElementById('bookFileList').innerHTML = '';
        
        // Reload current page
        await navigateToPage(currentPage);
        
        showToast('success', 'Buku berhasil ditambahkan');
      } catch (error) {
        console.error('Error adding book:', error);
        showToast('error', error.message || 'Gagal menambahkan buku');
      } finally {
        // Hide loading state
        document.getElementById('addBookBtnText').style.display = 'inline';
        document.getElementById('addBookBtnSpinner').style.display = 'none';
      }
    }

    // Add journal
    async function addJournal() {
      const form = document.getElementById('addJournalForm');
      const formData = new FormData(form);
      
      // Show loading state
      document.getElementById('addJournalBtnText').style.display = 'none';
      document.getElementById('addJournalBtnSpinner').style.display = 'inline-block';
      
      try {
        // Add journal
        await apiRequest('/journals', {
          method: 'POST',
          body: formData
        });
        
        // Close modal
        closeModal('addJournalModal');
        
        // Reset form
        form.reset();
        document.getElementById('journalFileList').innerHTML = '';
        
        // Reload current page
        await navigateToPage(currentPage);
        
        showToast('success', 'Jurnal berhasil ditambahkan');
      } catch (error) {
        console.error('Error adding journal:', error);
        showToast('error', error.message || 'Gagal menambahkan jurnal');
      } finally {
        // Hide loading state
        document.getElementById('addJournalBtnText').style.display = 'inline';
        document.getElementById('addJournalBtnSpinner').style.display = 'none';
      }
    }

    // Add thesis
    async function addThesis() {
      const form = document.getElementById('addThesisForm');
      const formData = new FormData(form);
      
      // Show loading state
      document.getElementById('addThesisBtnText').style.display = 'none';
      document.getElementById('addThesisBtnSpinner').style.display = 'inline-block';
      
      try {
        // Add thesis
        await apiRequest('/thesis', {
          method: 'POST',
          body: formData
        });
        
        // Close modal
        closeModal('addThesisModal');
        
        // Reset form
        form.reset();
        document.getElementById('thesisFileList').innerHTML = '';
        
        // Reload current page
        await navigateToPage(currentPage);
        
        showToast('success', 'Skripsi berhasil ditambahkan');
      } catch (error) {
        console.error('Error adding thesis:', error);
        showToast('error', error.message || 'Gagal menambahkan skripsi');
      } finally {
        // Hide loading state
        document.getElementById('addThesisBtnText').style.display = 'inline';
        document.getElementById('addThesisBtnSpinner').style.display = 'none';
      }
    }

    // Delete student
    async function deleteStudent(id) {
      if (!confirm('Apakah Anda yakin ingin menghapus data mahasiswa ini?')) return;
      
      try {
        // Delete student
        await apiRequest(`/students/${id}`, { method: 'DELETE' });
        
        // Reload students
        await loadStudents();
        
        showToast('success', 'Data mahasiswa berhasil dihapus');
      } catch (error) {
        console.error('Error deleting student:', error);
        showToast('error', error.message || 'Gagal menghapus data mahasiswa');
      }
    }

    // Load notifications
    async function loadNotifications() {
      try {
        const response = await apiRequest('/notifications');
        const notifications = response.data;
        
        // Update notification badge
        const unreadCount = notifications.filter(n => !n.read).length;
        const badge = document.getElementById('notificationBadge');
        
        if (unreadCount > 0) {
          badge.style.display = 'block';
          badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
        } else {
          badge.style.display = 'none';
        }
        
        // Render notifications
        renderNotifications(notifications);
      } catch (error) {
        console.error('Error loading notifications:', error);
        document.getElementById('notificationList').innerHTML = `
          <div class="empty-state">
            <i class="bi bi-bell empty-state-icon"></i>
            <p class="empty-state-text">Gagal memuat notifikasi</p>
          </div>
        `;
      }
    }

    // Render notifications
    function renderNotifications(notifications) {
      const notificationList = document.getElementById('notificationList');
      
      if (notifications.length === 0) {
        notificationList.innerHTML = `
          <div class="empty-state">
            <i class="bi bi-bell empty-state-icon"></i>
            <p class="empty-state-text">Tidak ada notifikasi</p>
          </div>
        `;
        return;
      }
      
      notificationList.innerHTML = notifications.map(notification => {
        let iconClass;
        
        switch (notification.type) {
          case 'info':
            iconClass = 'info';
            break;
          case 'success':
            iconClass = 'success';
            break;
          case 'warning':
            iconClass = 'warning';
            break;
          default:
            iconClass = 'info';
        }
        
        return `
          <div class="notification-item ${notification.read ? '' : 'unread'}" onclick="viewNotification(${notification.id})">
            <div class="notification-icon ${iconClass}">
              <i class="bi bi-${notification.type === 'info' ? 'info-circle' : notification.type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
            </div>
            <div class="notification-content">
              <div class="notification-message">${notification.message}</div>
              <div class="notification-time">${formatRelativeTime(notification.timestamp)}</div>
            </div>
          </div>
        `;
      }).join('');
    }

    // View notification
    async function viewNotification(id) {
      try {
        // Mark notification as read
        await apiRequest(`/notifications/${id}/read`, { method: 'POST' });
        
        // Reload notifications
        await loadNotifications();
      } catch (error) {
        console.error('Error viewing notification:', error);
      }
    }

    // Clear all notifications
    async function clearAllNotifications() {
      if (!confirm('Apakah Anda yakin ingin menghapus semua notifikasi?')) return;
      
      try {
        // Clear all notifications
        await apiRequest('/notifications/clear', { method: 'POST' });
        
        // Reload notifications
        await loadNotifications();
        
        showToast('success', 'Semua notifikasi telah dihapus');
      } catch (error) {
        console.error('Error clearing notifications:', error);
        showToast('error', error.message || 'Gagal menghapus notifikasi');
      }
    }

    // Perform search
    async function performSearch() {
      const query = document.getElementById('searchModalInput').value.trim();
      const activeFilter = document.querySelector('.filter-chip.active').getAttribute('data-filter');
      
      if (!query) {
        document.getElementById('searchResults').innerHTML = `
          <div class="empty-state">
            <i class="bi bi-search empty-state-icon"></i>
            <p class="empty-state-text">Ketik kata kunci untuk mencari</p>
          </div>
        `;
        return;
      }
      
      try {
        // Show loading state
        document.getElementById('searchResults').innerHTML = `
          <div class="loading-state">
            <div class="loading-spinner"></div>
            <div class="loading-text">Mencari...</div>
          </div>
        `;
        
        // Fetch search results
        const response = await apiRequest(`/search?q=${encodeURIComponent(query)}&filter=${activeFilter}`);
        const searchResults = response.data;
        
        // Render search results
        renderSearchResults(searchResults);
      } catch (error) {
        console.error('Error searching:', error);
        document.getElementById('searchResults').innerHTML = `
          <div class="empty-state">
            <i class="bi bi-exclamation-triangle empty-state-icon"></i>
            <p class="empty-state-text">Gagal melakukan pencarian</p>
          </div>
        `;
      }
    }

    // Render search results
    function renderSearchResults(results) {
      const searchResultsElement = document.getElementById('searchResults');
      
      if (results.length === 0) {
        searchResultsElement.innerHTML = `
          <div class="empty-state">
            <i class="bi bi-search empty-state-icon"></i>
            <p class="empty-state-text">Tidak ada hasil untuk pencarian ini</p>
          </div>
        `;
        return;
      }
      
      searchResultsElement.innerHTML = results.map(result => {
        let icon, subtitle;
        
        switch (result.type) {
          case 'student':
            icon = '<i class="bi bi-person"></i>';
            subtitle = `${result.department} • ${result.nim}`;
            break;
          case 'book':
            icon = '<i class="bi bi-book"></i>';
            subtitle = result.author;
            break;
          case 'journal':
            icon = '<i class="bi bi-journal-text"></i>';
            subtitle = `Vol. ${result.volume} No. ${result.issue} • ${formatDate(result.date)}`;
            break;
          case 'thesis':
            icon = '<i class="bi bi-file-earmark-text"></i>';
            subtitle = `${result.author} • ${result.department}`;
            break;
          default:
            icon = '<i class="bi bi-file-earmark"></i>';
            subtitle = result.category || '';
        }
        
        return `
          <div class="search-result-item" onclick="viewSearchResult('${result.type}', ${result.id})">
            <div class="search-result-icon">
              ${icon}
            </div>
            <div class="search-result-content">
              <div class="search-result-title">${result.title}</div>
              <div class="search-result-subtitle">${subtitle}</div>
            </div>
          </div>
        `;
      }).join('');
    }

    // View search result
    function viewSearchResult(type, id) {
      closeSearch();
      
      switch (type) {
        case 'student':
          viewStudentDetails(id);
          break;
        case 'book':
          viewBookDetails(id);
          break;
        case 'journal':
          viewJournalDetails(id);
          break;
        case 'thesis':
          viewThesisDetails(id);
          break;
        default:
          showToast('info', 'Detail tidak tersedia');
      }
    }

    // View student details (placeholder)
    function viewStudentDetails(id) {
      showToast('info', `Melihat detail mahasiswa dengan ID: ${id}`);
    }

    // Edit student (placeholder)
    function editStudent(id) {
      showToast('info', `Mengedit mahasiswa dengan ID: ${id}`);
    }

    // View book details (placeholder)
    function viewBookDetails(id) {
      showToast('info', `Melihat detail buku dengan ID: ${id}`);
    }

    // View journal details (placeholder)
    function viewJournalDetails(id) {
      showToast('info', `Melihat detail jurnal dengan ID: ${id}`);
    }

    // View thesis details (placeholder)
    function viewThesisDetails(id) {
      showToast('info', `Melihat detail skripsi dengan ID: ${id}`);
    }

    // View library item details (placeholder)
    function viewLibraryItemDetails(id) {
      showToast('info', `Melihat detail item dengan ID: ${id}`);
    }

    // Save settings (placeholder)
    async function saveSettings() {
      try {
        const formData = new FormData(document.getElementById('settingsForm'));
        const data = {};
        
        formData.forEach((value, key) => {
          if (value === 'on') {
            data[key] = true;
          } else {
            data[key] = value;
          }
        });
        
        await apiRequest('/settings', {
          method: 'POST',
          body: JSON.stringify(data)
        });
        
        showToast('success', 'Pengaturan berhasil disimpan');
      } catch (error) {
        console.error('Error saving settings:', error);
        showToast('error', error.message || 'Gagal menyimpan pengaturan');
      }
    }

    // Filter functions (placeholders)
    function filterStudents() {
      const filter = document.getElementById('studentsFilter').value;
      console.log('Filter students by:', filter);
      // Implement actual filtering logic
    }

    function sortStudents() {
      const sort = document.getElementById('studentsSort').value;
      console.log('Sort students by:', sort);
      // Implement actual sorting logic
    }

    function searchStudents() {
      const query = document.getElementById('studentsSearch').value.trim();
      console.log('Search students:', query);
      // Implement actual search logic
    }

    function filterLibrary() {
      const typeFilter = document.getElementById('libraryTypeFilter').value;
      const categoryFilter = document.getElementById('libraryCategoryFilter').value;
      console.log('Filter library by:', typeFilter, categoryFilter);
      // Implement actual filtering logic
    }

    function sortLibrary() {
      const sort = document.getElementById('librarySort').value;
      console.log('Sort library by:', sort);
      // Implement actual sorting logic
    }

    function searchLibrary() {
      const query = document.getElementById('librarySearch').value.trim();
      console.log('Search library:', query);
      // Implement actual search logic
    }

    function applyFilters() {
      console.log('Apply filters');
      // Implement actual filter application logic
    }

    // UI Helper Functions
    function openSidebar() {
      document.getElementById('sidebar').classList.add('active');
      document.getElementById('sidebarOverlay').classList.add('active');
    }

    function closeSidebar() {
      document.getElementById('sidebar').classList.remove('active');
      document.getElementById('sidebarOverlay').classList.remove('active');
    }

    function openSearch() {
      document.getElementById('searchOverlay').style.display = 'flex';
      setTimeout(() => {
        document.getElementById('searchOverlay').classList.add('active');
        document.getElementById('searchModalInput').focus();
      }, 10);
    }

    function closeSearch() {
      document.getElementById('searchOverlay').classList.remove('active');
      setTimeout(() => {
        document.getElementById('searchOverlay').style.display = 'none';
      }, 300);
    }

    function toggleNotifications() {
      document.getElementById('notificationPanel').classList.toggle('active');
    }

    function showAddOptions() {
      // Create a simple dropdown menu
      const fab = document.getElementById('fab');
      const existingMenu = document.getElementById('fabMenu');
      
      if (existingMenu) {
        existingMenu.remove();
        return;
      }
      
      const menu = document.createElement('div');
      menu.id = 'fabMenu';
      menu.className = 'position-absolute bottom-0 end-0 p-3';
      menu.style.marginBottom = '70px';
      menu.innerHTML = `
        <div class="d-flex flex-column gap-2">
          <button class="btn btn-primary rounded-circle shadow" onclick="openModal('addStudentModal')" title="Tambah Mahasiswa">
            <i class="bi bi-person-plus"></i>
          </button>
          <button class="btn btn-primary rounded-circle shadow" onclick="openModal('addBookModal')" title="Tambah Buku">
            <i class="bi bi-book-plus"></i>
          </button>
          <button class="btn btn-primary rounded-circle shadow" onclick="openModal('addJournalModal')" title="Tambah Jurnal">
            <i class="bi bi-journal-plus"></i>
          </button>
          <button class="btn btn-primary rounded-circle shadow" onclick="openModal('addThesisModal')" title="Tambah Skripsi">
            <i class="bi bi-file-earmark-plus"></i>
          </button>
        </div>
      `;
      
      fab.parentNode.appendChild(menu);
    }

    function showLibraryAddOptions() {
      // Create a simple dropdown menu
      const menu = document.createElement('div');
      menu.className = 'dropdown-menu show';
      menu.innerHTML = `
        <a class="dropdown-item" href="#" onclick="openModal('addBookModal')">
          <i class="bi bi-book me-2"></i> Buku
        </a>
        <a class="dropdown-item" href="#" onclick="openModal('addJournalModal')">
          <i class="bi bi-journal-text me-2"></i> Jurnal
        </a>
        <a class="dropdown-item" href="#" onclick="openModal('addThesisModal')">
          <i class="bi bi-file-earmark-text me-2"></i> Skripsi
        </a>
        <a class="dropdown-item" href="#" onclick="openModal('addArchiveModal')">
          <i class="bi bi-folder me-2"></i> Arsip
        </a>
      `;
      
      // Position the menu
      const rect = event.target.getBoundingClientRect();
      menu.style.position = 'absolute';
      menu.style.top = `${rect.bottom + window.scrollY}px`;
      menu.style.left = `${rect.left + window.scrollX}px`;
      menu.style.zIndex = '1000';
      
      document.body.appendChild(menu);
      
      // Close menu when clicking outside
      setTimeout(() => {
        document.addEventListener('click', function closeMenu(e) {
          if (!menu.contains(e.target)) {
            menu.remove();
            document.removeEventListener('click', closeMenu);
          }
        });
      }, 100);
    }

    function openModal(modalId) {
      document.getElementById(modalId).style.display = 'block';
      document.body.style.overflow = 'hidden';
    }

    function closeModal(modalId) {
      document.getElementById(modalId).style.display = 'none';
      document.body.style.overflow = 'auto';
    }

    function logout() {
      if (confirm('Apakah Anda yakin ingin keluar?')) {
        // Clear session
        localStorage.removeItem('authToken');
        
        // Redirect to login page
        window.location.href = 'login.html';
      }
    }

    function showToast(type, message) {
      const toastContainer = document.getElementById('toastContainer');
      
      const toast = document.createElement('div');
      toast.className = `toast ${type}`;
      
      let icon;
      switch (type) {
        case 'success':
          icon = '<i class="bi bi-check-circle-fill toast-icon"></i>';
          break;
        case 'error':
          icon = '<i class="bi bi-x-circle-fill toast-icon"></i>';
          break;
        case 'info':
          icon = '<i class="bi bi-info-circle-fill toast-icon"></i>';
          break;
        default:
          icon = '<i class="bi bi-info-circle-fill toast-icon"></i>';
      }
      
      toast.innerHTML = `
        ${icon}
        <div class="toast-message">${message}</div>
        <button class="toast-close">
          <i class="bi bi-x"></i>
        </button>
      `;
      
      toastContainer.appendChild(toast);
      
      // Setup close button
      toast.querySelector('.toast-close').addEventListener('click', () => {
        toast.remove();
      });
      
      // Auto remove after 5 seconds
      setTimeout(() => {
        toast.remove();
      }, 5000);
    }

    // Utility Functions
    function setupBannerCarousel() {
      const slides = document.querySelectorAll('.banner-slide');
      const dots = document.querySelectorAll('.banner-dot');
      let currentSlide = 0;
      
      function showSlide(index) {
        slides.forEach(slide => slide.classList.remove('active'));
        dots.forEach(dot => dot.classList.remove('active'));
        
        slides[index].classList.add('active');
        dots[index].classList.add('active');
      }
      
      dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
          currentSlide = index;
          showSlide(currentSlide);
        });
      });
      
      // Auto-rotate banner
      setInterval(() => {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
      }, 5000);
    }

    function getActivityClass(type) {
      switch (type) {
        case 'student_added': return 'bg-success';
        case 'book_added': return 'bg-info';
        case 'journal_added': return 'bg-warning';
        case 'thesis_added': return 'bg-danger';
        default: return 'bg-secondary';
      }
    }

    function formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
      });
    }

    function formatDateTime(dateString) {
      const date = new Date(dateString);
      return date.toLocaleString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    }

    function formatRelativeTime(dateString) {
      const date = new Date(dateString);
      const now = new Date();
      const diffMs = now - date;
      const diffMins = Math.floor(diffMs / 60000);
      const diffHours = Math.floor(diffMs / 3600000);
      const diffDays = Math.floor(diffMs / 86400000);
      
      if (diffMins < 1) return 'Baru saja';
      if (diffMins < 60) return `${diffMins} menit yang lalu`;
      if (diffHours < 24) return `${diffHours} jam yang lalu`;
      if (diffDays < 7) return `${diffDays} hari yang lalu`;
      
      return formatDate(dateString);
    }

    function debounce(func, wait) {
      let timeout;
      return function executedFunction(...args) {
        const later = () => {
          clearTimeout(timeout);
          func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
      };
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
      // Close notification panel when clicking outside
      if (!document.getElementById('notificationPanel').contains(e.target) && 
          !document.getElementById('notificationBtn').contains(e.target)) {
        document.getElementById('notificationPanel').classList.remove('active');
      }
      
      // Close FAB menu when clicking outside
      const fabMenu = document.getElementById('fabMenu');
      if (fabMenu && !fabMenu.contains(e.target) && !document.getElementById('fab').contains(e.target)) {
        fabMenu.remove();
      }
    });
  </script>
</body>
</html>