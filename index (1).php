<?php
require_once 'config.php';
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EasyDebt — Debt Management Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #0f1117;
            --bg-secondary: #161922;
            --bg-tertiary: #1c1f2a;
            --bg-card: #1e212b;
            --bg-hover: #252a36;
            --border: #2a2e3b;
            --border-light: #353a4a;
            --text-primary: #f0f2f5;
            --text-secondary: #8b92a8;
            --text-muted: #5c6275;
            --accent: #6366f1;
            --accent-hover: #818cf8;
            --accent-glow: rgba(99, 102, 241, 0.15);
            --success: #22c55e;
            --success-bg: rgba(34, 197, 94, 0.1);
            --warning: #f59e0b;
            --warning-bg: rgba(245, 158, 11, 0.1);
            --danger: #ef4444;
            --danger-bg: rgba(239, 68, 68, 0.1);
            --info: #3b82f6;
            --info-bg: rgba(59, 130, 246, 0.1);
            --gradient-start: #6366f1;
            --gradient-end: #8b5cf6;
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.3);
            --shadow: 0 4px 6px -1px rgba(0,0,0,0.4), 0 2px 4px -2px rgba(0,0,0,0.3);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.5), 0 4px 6px -4px rgba(0,0,0,0.3);
            --shadow-glow: 0 0 20px rgba(99, 102, 241, 0.15);
            --radius-sm: 8px;
            --radius: 12px;
            --radius-lg: 16px;
            --radius-xl: 24px;
            --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        [data-theme="light"] {
            --bg-primary: #f8fafc;
            --bg-secondary: #ffffff;
            --bg-tertiary: #f1f5f9;
            --bg-card: #ffffff;
            --bg-hover: #f1f5f9;
            --border: #e2e8f0;
            --border-light: #cbd5e1;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --accent: #4f46e5;
            --accent-hover: #6366f1;
            --accent-glow: rgba(79, 70, 229, 0.1);
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
            --shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.05);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.05);
            --shadow-glow: 0 0 20px rgba(79, 70, 229, 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            transition: background 0.3s ease, color 0.3s ease;
        }

        /* Animated Background Mesh */
        .bg-mesh {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
            opacity: 0.4;
        }
        .bg-mesh::before {
            content: '';
            position: absolute;
            top: -50%; left: -50%;
            width: 200%; height: 200%;
            background: 
                radial-gradient(ellipse 80% 50% at 20% 40%, rgba(99, 102, 241, 0.08), transparent),
                radial-gradient(ellipse 60% 40% at 80% 60%, rgba(139, 92, 246, 0.06), transparent),
                radial-gradient(ellipse 50% 50% at 50% 50%, rgba(59, 130, 246, 0.04), transparent);
            animation: meshFloat 20s ease-in-out infinite;
        }
        @keyframes meshFloat {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(2%, -2%) rotate(1deg); }
            66% { transform: translate(-1%, 1%) rotate(-1deg); }
        }

        /* Noise texture overlay */
        .noise-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none;
            z-index: 1;
            opacity: 0.02;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 24px;
            position: relative;
            z-index: 10;
        }

        /* ===== LOGIN PAGE ===== */
        .login-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 24px;
            position: relative;
            z-index: 10;
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 48px 40px;
            box-shadow: var(--shadow-lg), var(--shadow-glow);
            position: relative;
            overflow: hidden;
        }
        .login-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--gradient-start), var(--gradient-end));
        }
        .login-brand {
            text-align: center;
            margin-bottom: 32px;
        }
        .login-brand-icon {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            border-radius: var(--radius-lg);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(99, 102, 241, 0.3);
        }
        .login-brand-icon svg {
            width: 32px; height: 32px;
            color: white;
        }
        .login-brand h1 {
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -0.025em;
            margin-bottom: 6px;
        }
        .login-brand p {
            color: var(--text-secondary);
            font-size: 0.9375rem;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            font-size: 0.9375rem;
            font-family: inherit;
            transition: var(--transition);
            outline: none;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }
        .form-group input::placeholder {
            color: var(--text-muted);
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 0.9375rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            white-space: nowrap;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            width: 100%;
            padding: 14px 24px;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35);
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.45);
        }
        .btn-primary:active {
            transform: translateY(0);
        }
        .btn-success {
            background: var(--success);
            color: white;
        }
        .btn-success:hover { background: #16a34a; }
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        .btn-danger:hover { background: #dc2626; }
        .btn-secondary {
            background: var(--bg-hover);
            color: var(--text-secondary);
            border: 1px solid var(--border);
        }
        .btn-secondary:hover {
            background: var(--border);
            color: var(--text-primary);
        }
        .btn-sm {
            padding: 6px 14px;
            font-size: 0.8125rem;
        }
        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        .btn-group .btn { flex: 1; }

        .login-hint {
            text-align: center;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid var(--border);
            color: var(--text-muted);
            font-size: 0.8125rem;
        }
        .login-hint code {
            background: var(--bg-tertiary);
            padding: 2px 8px;
            border-radius: 4px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.75rem;
            color: var(--accent);
        }

        /* ===== NAVIGATION ===== */
        .navbar {
            background: rgba(22, 25, 34, 0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
            display: none;
        }
        [data-theme="light"] .navbar {
            background: rgba(255, 255, 255, 0.85);
        }
        .navbar-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 64px;
        }
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            font-size: 1.125rem;
            letter-spacing: -0.02em;
        }
        .nav-brand-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .nav-brand-icon svg {
            width: 18px; height: 18px;
            color: white;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .nav-btn {
            padding: 8px 16px;
            border: none;
            background: transparent;
            color: var(--text-secondary);
            cursor: pointer;
            border-radius: var(--radius-sm);
            font-weight: 500;
            font-size: 0.875rem;
            font-family: inherit;
            transition: var(--transition);
            position: relative;
        }
        .nav-btn:hover {
            color: var(--text-primary);
            background: var(--bg-hover);
        }
        .nav-btn.active {
            color: var(--accent);
            background: var(--accent-glow);
        }
        .nav-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .theme-toggle {
            width: 36px; height: 36px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            background: var(--bg-tertiary);
            color: var(--text-secondary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }
        .theme-toggle:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
        }

        /* ===== PAGES ===== */
        .page {
            display: none;
            animation: pageIn 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 32px 0 48px;
        }
        .page.active { display: block; }
        @keyframes pageIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .page-header {
            margin-bottom: 32px;
        }
        .page-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -0.025em;
            margin-bottom: 6px;
        }
        .page-header p {
            color: var(--text-secondary);
            font-size: 0.9375rem;
        }

        /* ===== CARDS ===== */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 28px;
            margin-bottom: 24px;
            transition: var(--transition);
        }
        .card:hover {
            border-color: var(--border-light);
        }
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }
        .card-header h2 {
            font-size: 1.125rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-header h2 .icon {
            width: 36px; height: 36px;
            background: var(--accent-glow);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        /* ===== STATS GRID ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 24px;
            position: relative;
            overflow: hidden;
            transition: var(--transition);
        }
        .stat-card:hover {
            border-color: var(--border-light);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--gradient-start), var(--gradient-end));
            opacity: 0;
            transition: var(--transition);
        }
        .stat-card:hover::before { opacity: 1; }
        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        .stat-label {
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .stat-icon {
            width: 40px; height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        .stat-icon.purple { background: var(--accent-glow); }
        .stat-icon.green { background: var(--success-bg); }
        .stat-icon.blue { background: var(--info-bg); }
        .stat-icon.orange { background: var(--warning-bg); }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 4px;
            font-family: 'JetBrains Mono', monospace;
        }
        .stat-change {
            font-size: 0.8125rem;
            color: var(--text-muted);
        }

        /* ===== ACTION GRID ===== */
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        }
        .action-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
            cursor: pointer;
            transition: var(--transition);
            text-align: left;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .action-card:hover {
            border-color: var(--accent);
            background: var(--bg-hover);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        .action-card .icon {
            width: 44px; height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            background: var(--accent-glow);
        }
        .action-card h3 {
            font-size: 0.9375rem;
            font-weight: 600;
        }
        .action-card p {
            font-size: 0.8125rem;
            color: var(--text-secondary);
            line-height: 1.5;
        }

        /* ===== TABLES ===== */
        .table-container {
            overflow-x: auto;
            border-radius: var(--radius);
            border: 1px solid var(--border);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }
        thead {
            background: var(--bg-tertiary);
        }
        th {
            padding: 14px 20px;
            text-align: left;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }
        td {
            padding: 14px 20px;
            border-bottom: 1px solid var(--border);
            color: var(--text-primary);
            vertical-align: middle;
        }
        tbody tr {
            transition: var(--transition);
        }
        tbody tr:hover {
            background: var(--bg-hover);
        }
        tbody tr:last-child td { border-bottom: none; }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
        }
        .badge-warning {
            background: var(--warning-bg);
            color: var(--warning);
        }
        .badge-success {
            background: var(--success-bg);
            color: var(--success);
        }
        .badge-info {
            background: var(--info-bg);
            color: var(--info);
        }
        .badge-danger {
            background: var(--danger-bg);
            color: var(--danger);
        }

        /* ===== ALERTS ===== */
        .alert {
            padding: 14px 18px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            display: none;
            align-items: center;
            gap: 10px;
            font-size: 0.875rem;
            font-weight: 500;
            border: 1px solid;
        }
        .alert.show { display: flex; animation: alertIn 0.3s ease; }
        @keyframes alertIn {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .alert-success {
            background: var(--success-bg);
            color: var(--success);
            border-color: rgba(34, 197, 94, 0.2);
        }
        .alert-error {
            background: var(--danger-bg);
            color: var(--danger);
            border-color: rgba(239, 68, 68, 0.2);
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 60px 24px;
            color: var(--text-secondary);
        }
        .empty-state .icon {
            width: 64px; height: 64px;
            background: var(--bg-tertiary);
            border-radius: var(--radius-lg);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 20px;
        }
        .empty-state h3 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 6px;
        }
        .empty-state p {
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        /* ===== TOTAL DISPLAY ===== */
        .total-display {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            padding: 28px;
            border-radius: var(--radius-lg);
            text-align: center;
            margin: 20px 0;
            position: relative;
            overflow: hidden;
        }
        .total-display::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.1), transparent);
        }
        .total-display h3 {
            font-size: 0.8125rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            opacity: 0.8;
            position: relative;
            margin-bottom: 8px;
        }
        .total-display .amount {
            font-size: 2.5rem;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
            position: relative;
            letter-spacing: -0.02em;
        }

        /* ===== LOADING ===== */
        .loading {
            text-align: center;
            padding: 48px;
            color: var(--text-muted);
            font-size: 0.875rem;
        }
        .loading::after {
            content: '';
            display: inline-block;
            width: 16px; height: 16px;
            border: 2px solid var(--border);
            border-top-color: var(--accent);
            border-radius: 50%;
            margin-left: 10px;
            animation: spin 0.8s linear infinite;
            vertical-align: middle;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* ===== GRID LAYOUTS ===== */
        .two-col-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }
        @media (max-width: 768px) {
            .two-col-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr; }
            .actions-grid { grid-template-columns: 1fr; }
            .navbar-content { padding: 0 16px; }
            .container { padding: 0 16px; }
            .login-card { padding: 32px 24px; }
            .nav-links { display: none; }
        }

        /* ===== SCROLLBAR ===== */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg-primary); }
        ::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover { background: var(--border-light); }

        /* ===== MOBILE NAV ===== */
        .mobile-nav-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 4px;
        }
        @media (max-width: 768px) {
            .mobile-nav-toggle { display: block; }
            .nav-links {
                position: absolute;
                top: 64px; left: 0; right: 0;
                background: var(--bg-card);
                border-bottom: 1px solid var(--border);
                padding: 12px;
                flex-direction: column;
                gap: 4px;
                display: none;
            }
            .nav-links.show { display: flex; }
            .nav-btn { width: 100%; text-align: left; }
        }
    </style>
</head>
<body>

    <div class="bg-mesh"></div>
    <div class="noise-overlay"></div>

    <!-- Navigation -->
    <nav class="navbar" id="navbar">
        <div class="navbar-content">
            <div class="nav-brand">
                <div class="nav-brand-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                </div>
                EasyDebt
            </div>
            <button class="mobile-nav-toggle" onclick="toggleMobileNav()">☰</button>
            <div class="nav-links" id="navLinks">
                <button class="nav-btn active" onclick="showPage('dashboard')">Dashboard</button>
                <button class="nav-btn" onclick="showPage('inventory')">Inventory</button>
                <button class="nav-btn" onclick="showPage('addDebt')">New Debt</button>
                <button class="nav-btn" onclick="showPage('debtList')">Debts</button>
                <button class="nav-btn" onclick="showPage('paidHistory')">History</button>
                <button class="nav-btn" onclick="logout()">Logout</button>
            </div>
            <div class="nav-actions">
                <!-- Stock notification bell -->
                <div style="position:relative" id="stockBellWrap">
                    <button class="theme-toggle" id="stockBell" onclick="toggleStockPanel()" title="Stock alerts" style="display:none;position:relative">
                        🔔
                        <span id="stockBadge" style="position:absolute;top:-5px;right:-5px;background:var(--danger);color:white;font-size:0.6rem;font-weight:700;min-width:16px;height:16px;border-radius:8px;display:flex;align-items:center;justify-content:center;padding:0 3px"></span>
                    </button>
                    <div id="stockPanel" style="display:none;position:absolute;top:calc(100% + 10px);right:0;width:300px;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);z-index:999;overflow:hidden">
                        <div style="padding:14px 18px;border-bottom:1px solid var(--border);font-weight:600;font-size:0.875rem;display:flex;align-items:center;gap:8px">
                            ⚠️ Low / Out of Stock
                        </div>
                        <div id="stockPanelList" style="max-height:320px;overflow-y:auto"></div>
                    </div>
                </div>
                <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme">
                    <span id="themeIcon">🌙</span>
                </button>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Login Page -->
        <div class="page active" id="loginPage">
            <div class="login-wrapper">
                <div class="login-card">
                    <div class="login-brand">
                        <div class="login-brand-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                        </div>
                        <h1>Welcome Back</h1>
                        <p>Sign in to manage your debt records</p>
                    </div>
                    <div class="alert alert-error" id="loginError">
                        <span>⚠️</span> Invalid username or password
                    </div>
                    <form id="loginForm" onsubmit="handleLogin(event)">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" required placeholder="Enter your username">
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" required placeholder="Enter your password">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" /></svg>
                            Sign In
                        </button>
                    </form>
                    <div class="login-hint">
                        Default credentials: <code>gladys</code> / <code>admin</code>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard -->
        <div class="page" id="dashboard">
            <div class="page-header">
                <h1>Dashboard</h1>
                <p>Overview of your debt management system</p>
            </div>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Total Customers</span>
                        <div class="stat-icon purple">👥</div>
                    </div>
                    <div class="stat-value" id="totalCustomers">0</div>
                    <div class="stat-change">Unique debtors</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Outstanding Balance</span>
                        <div class="stat-icon orange">💰</div>
                    </div>
                    <div class="stat-value" id="totalOutstanding">₱0.00</div>
                    <div class="stat-change">Total unpaid amount</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Inventory Items</span>
                        <div class="stat-icon blue">📦</div>
                    </div>
                    <div class="stat-value" id="totalItems">0</div>
                    <div class="stat-change">Products in stock</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-label">Paid Records</span>
                        <div class="stat-icon green">✅</div>
                    </div>
                    <div class="stat-value" id="totalPaid">0</div>
                    <div class="stat-change">Completed transactions</div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h2><span class="icon">⚡</span> Quick Actions</h2>
                </div>
                <div class="actions-grid">
                    <div class="action-card" onclick="showPage('addDebt')">
                        <div class="icon">➕</div>
                        <h3>Add New Debt</h3>
                        <p>Record a new debt transaction for a customer</p>
                    </div>
                    <div class="action-card" onclick="showPage('debtList')">
                        <div class="icon">📋</div>
                        <h3>View Debt List</h3>
                        <p>See all outstanding and unpaid debts</p>
                    </div>
                    <div class="action-card" onclick="showPage('paidHistory')">
                        <div class="icon">📜</div>
                        <h3>Paid History</h3>
                        <p>Review completed and paid transactions</p>
                    </div>
                    <div class="action-card" onclick="showPage('inventory')">
                        <div class="icon">🎒</div>
                        <h3>Inventory</h3>
                        <p>Manage items and set pricing</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inventory Page -->
        <div class="page" id="inventory">
            <div class="page-header">
                <h1>Inventory</h1>
                <p>Manage your products and pricing</p>
            </div>
            <div class="card">
                <div class="card-header">
                    <h2><span class="icon">🏷️</span> Add New Item</h2>
                </div>
                <div class="alert alert-success" id="itemSuccess">
                    <span>✅</span> Item added successfully
                </div>
                <div class="alert alert-error" id="itemError"></div>
                <form id="itemForm" onsubmit="handleAddItem(event)">
                    <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:16px">
                        <div class="form-group" style="margin-bottom:0">
                            <label for="itemName">Item Name</label>
                            <input type="text" id="itemName" required placeholder="e.g., Rice, Sugar">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label for="itemPrice">Price (₱)</label>
                            <div style="display:flex;gap:8px;align-items:center">
                                <input type="number" id="itemPrice" required min="0" step="0.01" placeholder="0.00" style="flex:1">
                                <div style="display:flex;border:1px solid var(--border);border-radius:var(--radius-sm);overflow:hidden;flex-shrink:0">
                                    <button type="button" id="unitPcs" onclick="setUnit('pcs')"
                                        style="padding:10px 14px;border:none;background:var(--accent);color:white;font-weight:600;font-size:0.875rem;font-family:inherit;cursor:pointer;transition:var(--transition)">pcs</button>
                                    <button type="button" id="unitKg" onclick="setUnit('kg')"
                                        style="padding:10px 14px;border:none;background:var(--bg-tertiary);color:var(--text-secondary);font-weight:600;font-size:0.875rem;font-family:inherit;cursor:pointer;transition:var(--transition)">kg</button>
                                </div>
                            </div>
                            <input type="hidden" id="priceUnit" value="pcs">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label for="itemStock">Initial Stock</label>
                            <input type="number" id="itemStock" min="0" step="0.001" placeholder="0" value="0">
                        </div>
                    </div>
                    <div class="btn-group">
                        <button type="submit" class="btn btn-success">Save Item</button>
                        <button type="button" class="btn btn-secondary" onclick="resetForm('itemForm');setUnit('pcs')">Cancel</button>
                    </div>
                </form>
            </div>
            <div class="card">
                <div class="card-header">
                    <h2><span class="icon">🎒</span> Items & Prices</h2>
                </div>
                <div id="inventoryList"><div class="loading">Loading</div></div>
            </div>
        </div>

        <!-- Add Debt Page -->
        <div class="page" id="addDebt">
            <div class="page-header">
                <h1>New Debt</h1>
                <p>Record a new debt transaction</p>
            </div>
            <div class="card">
                <div class="card-header">
                    <h2><span class="icon">➕</span> Debt Details</h2>
                </div>
                <div class="alert alert-success" id="debtSuccess">
                    <span>✅</span> Debt recorded successfully
                </div>
                <div class="alert alert-error" id="debtError"></div>
                <form id="debtForm" onsubmit="handleAddDebt(event)">
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px">
                        <div class="form-group" style="margin-bottom:0">
                            <label for="debtCustomer">Customer Name</label>
                            <input type="text" id="debtCustomer" required placeholder="Enter customer name">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label for="debtPhone">Phone Number</label>
                            <input type="tel" id="debtPhone" placeholder="e.g. 09123456789">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label for="debtItemSelect">Select Item</label>
                            <select id="debtItemSelect" required onchange="updateDebtTotal()">
                                <option value="">-- Choose an item --</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label for="debtQuantity" id="debtQuantityLabel">Quantity</label>
                            <input type="number" id="debtQuantity" required min="0.001" step="0.001" value="1" onchange="updateDebtTotal()" onkeyup="updateDebtTotal()">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label for="debtDueDate">Due Date</label>
                            <input type="date" id="debtDueDate" required>
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label for="debtInterest">Interest Rate (% / day overdue)</label>
                            <input type="number" id="debtInterest" min="0" step="0.01" placeholder="0 = no interest" value="0">
                        </div>
                        <div class="total-display" style="margin:0;align-self:end">
                            <h3>Total Amount</h3>
                            <div class="amount" id="debtTotalDisplay">₱0.00</div>
                        </div>
                        <div class="form-group" style="margin-bottom:0;grid-column:1/-1">
                            <label for="debtNotes">Notes (Optional)</label>
                            <textarea id="debtNotes" rows="2" placeholder="Additional details..." style="resize:none"></textarea>
                        </div>
                    </div>
                    <div class="btn-group">
                        <button type="submit" class="btn btn-success">Save Debt</button>
                        <button type="button" class="btn btn-secondary" onclick="resetForm('debtForm')">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Debt List Page -->
        <div class="page" id="debtList">
            <div class="page-header">
                <h1>Unpaid Debts</h1>
                <p>All outstanding debt records</p>
            </div>
            <div class="card">
                <div class="card-header">
                    <h2><span class="icon">📋</span> Debt Records</h2>
                    <div style="position:relative;width:260px">
                        <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);pointer-events:none">🔍</span>
                        <input type="text" id="debtSearch" placeholder="Search customer or item…"
                            oninput="filterDebts()"
                            style="width:100%;padding:8px 12px 8px 36px;background:var(--bg-tertiary);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text-primary);font-size:0.875rem;font-family:inherit;outline:none;transition:var(--transition)"
                            onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border)'">
                    </div>
                </div>
                <div id="debtListContainer"><div class="loading">Loading debts</div></div>
            </div>
        </div>

        <!-- Paid History Page -->
        <div class="page" id="paidHistory">
            <div class="page-header">
                <h1>Payment History</h1>
                <p>Completed and paid transactions</p>
            </div>
            <div class="card">
                <div class="card-header">
                    <h2><span class="icon">📜</span> Paid Records</h2>
                    <div style="position:relative;width:260px">
                        <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);pointer-events:none">🔍</span>
                        <input type="text" id="historySearch" placeholder="Search customer or item…"
                            oninput="filterHistory()"
                            style="width:100%;padding:8px 12px 8px 36px;background:var(--bg-tertiary);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text-primary);font-size:0.875rem;font-family:inherit;outline:none;transition:var(--transition)"
                            onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border)'">
                    </div>
                </div>
                <div id="paidListContainer"><div class="loading">Loading history</div></div>
            </div>
        </div>
    </div>

    <script>
        let pricesCache = [];
        let itemsCache = [];

        // Theme
        function initTheme() {
            const saved = localStorage.getItem('theme');
            if (saved) {
                document.documentElement.setAttribute('data-theme', saved);
                updateThemeIcon(saved);
            }
        }
        function toggleTheme() {
            const current = document.documentElement.getAttribute('data-theme');
            const next = current === 'light' ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);
            updateThemeIcon(next);
        }
        function updateThemeIcon(theme) {
            document.getElementById('themeIcon').textContent = theme === 'light' ? '☀️' : '🌙';
        }

        // Mobile nav
        function toggleMobileNav() {
            document.getElementById('navLinks').classList.toggle('show');
        }

        async function api(action, method = 'GET', data = null) {
            const options = { method, headers: { 'Content-Type': 'application/json' } };
            if (data) options.body = JSON.stringify(data);
            const response = await fetch(`api.php?action=${action}`, options);
            const result = await response.json();
            if (!response.ok) throw new Error(result.error || 'Request failed');
            return result;
        }

        function showPage(pageId) {
            document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
            document.getElementById(pageId).classList.add('active');
            document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('navLinks').classList.remove('show');

            const btnMap = { dashboard: 0, inventory: 1, addDebt: 2, debtList: 3, paidHistory: 4 };
            const idx = btnMap[pageId];
            if (idx !== undefined) document.querySelectorAll('.nav-btn')[idx]?.classList.add('active');

            if (pageId === 'dashboard') updateDashboard();
            if (pageId === 'inventory') { renderInventoryList(); }
            if (pageId === 'addDebt') { loadItemSelect('debtItemSelect'); updateDebtTotal(); }
            if (pageId === 'debtList') renderDebtList();
            if (pageId === 'paidHistory') renderPaidHistory();
        }

        async function handleLogin(e) {
            e.preventDefault();
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            try {
                await api('login', 'POST', { username, password });
                document.getElementById('loginError').classList.remove('show');
                document.getElementById('navbar').style.display = 'block';
                showPage('dashboard');
            } catch (err) {
                document.getElementById('loginError').classList.add('show');
            }
        }

        async function logout() {
            await api('logout', 'POST');

            // Show logout overlay
            const overlay = document.getElementById('logoutOverlay');
            overlay.style.display = 'flex';

            // After 1.5s switch message to "Redirecting…"
            setTimeout(() => {
                document.getElementById('logoutMsg').textContent = 'Redirecting to login page…';
                document.getElementById('logoutSub').style.opacity = '1';
            }, 1500);

            // After 2.8s hide overlay and go to login
            setTimeout(() => {
                overlay.style.opacity = '0';
                setTimeout(() => {
                    overlay.style.display = 'none';
                    overlay.style.opacity = '1';
                    document.getElementById('navbar').style.display = 'none';
                    document.getElementById('loginForm').reset();
                    showPage('loginPage');
                }, 400);
            }, 2800);
        }

        async function handleAddItem(e) {
            e.preventDefault();
            const name  = document.getElementById('itemName').value.trim();
            const price = parseFloat(document.getElementById('itemPrice').value);
            const unit  = document.getElementById('priceUnit').value;
            const stock = parseFloat(document.getElementById('itemStock').value) || 0;
            try {
                const result = await api('add_item', 'POST', { name, stock });
                await api('add_price', 'POST', { item_id: result.id, price, unit });
                showAlert('itemSuccess');
                resetForm('itemForm');
                setUnit('pcs');
                await renderInventoryList();
                await loadItemSelect('debtItemSelect');
                pricesCache = await api('get_prices');
            } catch (err) {
                showAlert('itemError', err.message);
            }
        }

        function setUnit(unit) {
            document.getElementById('priceUnit').value = unit;
            document.getElementById('unitPcs').style.background = unit === 'pcs' ? 'var(--accent)' : 'var(--bg-tertiary)';
            document.getElementById('unitPcs').style.color = unit === 'pcs' ? 'white' : 'var(--text-secondary)';
            document.getElementById('unitKg').style.background = unit === 'kg' ? 'var(--accent)' : 'var(--bg-tertiary)';
            document.getElementById('unitKg').style.color = unit === 'kg' ? 'white' : 'var(--text-secondary)';
        }

        async function handleAddDebt(e) {
            e.preventDefault();
            const data = {
                customer_name: document.getElementById('debtCustomer').value.trim(),
                phone: document.getElementById('debtPhone').value.trim(),
                item_id: parseInt(document.getElementById('debtItemSelect').value),
                quantity: parseFloat(document.getElementById('debtQuantity').value),
                due_date: document.getElementById('debtDueDate').value,
                notes: document.getElementById('debtNotes').value.trim(),
                interest_rate: parseFloat(document.getElementById('debtInterest').value) || 0
            };
            try {
                await api('add_debt', 'POST', data);
                showAlert('debtSuccess');
                resetForm('debtForm');
                updateDebtTotal();
                checkLowStock();
            } catch (err) {
                showAlert('debtError', err.message);
            }
        }

        async function markAsPaid(debtId) {
            if (!confirm('Mark this debt as fully paid?')) return;
            try {
                await api('mark_paid', 'POST', { id: debtId });
                renderDebtList();
                updateDashboard();
            } catch (err) {
                alert(err.message);
            }
        }

        async function applyPayment(debtId) {
            const input = document.getElementById(`payment-input-${debtId}`);
            const amount = parseFloat(input ? input.value : 0);
            if (!amount || amount <= 0) { input.focus(); return; }
            try {
                const res = await api('partial_payment', 'POST', { id: debtId, amount });
                if (res.fully_paid) {
                    updateDashboard();
                }
                renderDebtList();
                updateDashboard();
            } catch (err) {
                alert(err.message);
            }
        }

        function startEditDebt(id, currentRate, currentDueDate, currentNotes) {
            // Close any other open debt edits first
            const existing = document.getElementById('debt-edit-row');
            if (existing) existing.remove();

            // Find the row and insert an edit row after it
            const btn = document.querySelector(`button[onclick*="startEditDebt(${id},"]`);
            if (!btn) return;
            const row = btn.closest('tr');
            const colCount = row.cells.length;

            const editRow = document.createElement('tr');
            editRow.id = 'debt-edit-row';
            editRow.style.background = 'var(--bg-tertiary)';
            editRow.innerHTML = `
                <td colspan="${colCount}" style="padding:16px 20px">
                    <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
                        <div>
                            <label style="display:block;font-size:0.75rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px">Interest Rate (% / day)</label>
                            <input type="number" id="edit-debt-rate-${id}" value="${currentRate}" min="0" step="0.01"
                                style="padding:8px 12px;width:140px;background:var(--bg-card);border:1px solid var(--accent);border-radius:var(--radius-sm);color:var(--text-primary);font-size:0.875rem;font-family:'JetBrains Mono',monospace;outline:none">
                        </div>
                        <div>
                            <label style="display:block;font-size:0.75rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px">Due Date</label>
                            <input type="date" id="edit-debt-due-${id}" value="${currentDueDate}"
                                style="padding:8px 12px;background:var(--bg-card);border:1px solid var(--accent);border-radius:var(--radius-sm);color:var(--text-primary);font-size:0.875rem;font-family:inherit;outline:none">
                        </div>
                        <div style="flex:1;min-width:180px">
                            <label style="display:block;font-size:0.75rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px">Notes</label>
                            <input type="text" id="edit-debt-notes-${id}" value="${escapeHtml(currentNotes)}"
                                style="padding:8px 12px;width:100%;background:var(--bg-card);border:1px solid var(--accent);border-radius:var(--radius-sm);color:var(--text-primary);font-size:0.875rem;font-family:inherit;outline:none"
                                onkeydown="if(event.key==='Enter')saveEditDebt(${id});if(event.key==='Escape')document.getElementById('debt-edit-row').remove()">
                        </div>
                        <div style="display:flex;gap:8px">
                            <button class="btn btn-success btn-sm" onclick="saveEditDebt(${id})">Save</button>
                            <button class="btn btn-secondary btn-sm" onclick="document.getElementById('debt-edit-row').remove()">Cancel</button>
                        </div>
                    </div>
                </td>`;
            row.after(editRow);
            document.getElementById(`edit-debt-rate-${id}`).focus();
        }

        async function saveEditDebt(id) {
            const rate    = parseFloat(document.getElementById(`edit-debt-rate-${id}`)?.value) || 0;
            const dueDate = document.getElementById(`edit-debt-due-${id}`)?.value || '';
            const notes   = document.getElementById(`edit-debt-notes-${id}`)?.value.trim() || '';
            try {
                await api('update_debt', 'POST', { id, interest_rate: rate, due_date: dueDate, notes });
                await renderDebtList();
            } catch (err) {
                alert(err.message);
            }
        }

        async function updateDashboard() {
            try {
                const stats = await api('get_dashboard');
                document.getElementById('totalCustomers').textContent = stats.totalCustomers;
                document.getElementById('totalOutstanding').textContent = '₱' + parseFloat(stats.totalOutstanding).toFixed(2);
                document.getElementById('totalItems').textContent = stats.totalItems;
                document.getElementById('totalPaid').textContent = stats.totalPaid;
                checkLowStock();
            } catch (err) {
                console.error('Dashboard error:', err);
            }
        }

        async function checkLowStock() {
            try {
                const items = await api('get_items');
                const LOW_THRESHOLD = 5;
                const low = items.filter(i => parseFloat(i.stock || 0) <= LOW_THRESHOLD);
                const bell  = document.getElementById('stockBell');
                const badge = document.getElementById('stockBadge');
                const list  = document.getElementById('stockPanelList');
                if (!bell) return;

                if (!low.length) {
                    bell.style.display = 'none';
                    return;
                }

                bell.style.display = 'flex';
                badge.textContent = low.length;

                list.innerHTML = low.map(i => {
                    const stock = parseFloat(i.stock || 0);
                    const isOut = stock <= 0;
                    return `<div style="display:flex;align-items:center;justify-content:space-between;padding:10px 18px;border-bottom:1px solid var(--border);gap:12px">
                        <span style="font-size:0.875rem;font-weight:500">${escapeHtml(i.name)}</span>
                        <span class="badge ${isOut ? 'badge-danger' : 'badge-warning'}">
                            ${isOut ? 'Out of stock' : stock % 1 === 0 ? stock + ' left' : stock.toFixed(3) + ' left'}
                        </span>
                    </div>`;
                }).join('') +
                `<div style="padding:10px 18px">
                    <button class="btn btn-secondary btn-sm" style="width:100%" onclick="showPage('inventory');closeStockPanel()">
                        Go to Inventory →
                    </button>
                </div>`;
            } catch(e) {}
        }

        function toggleStockPanel() {
            const panel = document.getElementById('stockPanel');
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        }

        function closeStockPanel() {
            const panel = document.getElementById('stockPanel');
            if (panel) panel.style.display = 'none';
        }

        document.addEventListener('click', function(e) {
            const wrap = document.getElementById('stockBellWrap');
            if (wrap && !wrap.contains(e.target)) closeStockPanel();
        });

        async function updateDebtTotal() {
            const itemId = document.getElementById('debtItemSelect').value;
            const quantity = parseFloat(document.getElementById('debtQuantity').value) || 0;
            if (!pricesCache.length) {
                try { pricesCache = await api('get_prices'); } catch(e) {}
            }
            const record = pricesCache.find(p => p.item_id == itemId);
            const total = record ? record.price * quantity : 0;
            document.getElementById('debtTotalDisplay').textContent = '₱' + total.toFixed(2);

            // Update quantity label with unit
            const unit = record ? (record.unit || 'pcs') : 'pcs';
            const label = document.getElementById('debtQuantityLabel');
            if (label) label.textContent = unit === 'kg' ? 'Quantity (kg)' : 'Quantity (pcs)';
        }

        async function loadItemSelect(selectId) {
            const select = document.getElementById(selectId);
            try {
                const items = await api('get_items');
                itemsCache = items;
                select.innerHTML = '<option value="">-- Choose an item --</option>';
                items.forEach(item => {
                    const stock = parseFloat(item.stock || 0);
                    const isOut = stock <= 0;
                    const isLow = stock > 0 && stock <= 5;
                    const stockLabel = isOut ? ' — Out of stock' : isLow ? ` — Low: ${stock % 1 === 0 ? stock : stock.toFixed(3)} left` : ` (${stock % 1 === 0 ? stock : stock.toFixed(3)} left)`;
                    const opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = item.name + stockLabel;
                    if (isOut) opt.style.color = 'var(--danger)';
                    else if (isLow) opt.style.color = 'var(--warning)';
                    select.appendChild(opt);
                });
            } catch (err) {
                select.innerHTML = '<option value="">Error loading items</option>';
            }
        }

        async function renderInventoryList() {
            const container = document.getElementById('inventoryList');
            try {
                const [items, prices] = await Promise.all([api('get_items'), api('get_prices')]);
                pricesCache = prices;

                // Build a map of item_id -> price record for quick lookup
                const priceMap = {};
                prices.forEach(p => { priceMap[p.item_id] = p; });

                if (!items.length) {
                    container.innerHTML = '<div class="empty-state"><div class="icon">📦</div><h3>No items yet</h3><p>Add your first item to get started</p></div>';
                    return;
                }

                let html = `<div class="table-container"><table>
                    <thead><tr>
                        <th>Item Name</th>
                        <th>Price (₱)</th>
                        <th>Unit</th>
                        <th>Stock</th>
                        <th>Date Added</th>
                        <th>Actions</th>
                    </tr></thead><tbody>`;

                items.forEach(item => {
                    const p    = priceMap[item.id];
                    const unit = p ? (p.unit || 'pcs') : null;
                    const stock = parseFloat(item.stock || 0);
                    const priceDisplay = p
                        ? `<span style="font-family:'JetBrains Mono',monospace" id="price-val-${p.id}">₱${parseFloat(p.price).toFixed(2)}</span>`
                        : `<span style="color:var(--text-muted);font-size:0.8rem">No price</span>`;
                    const unitBadge = p
                        ? `<span class="badge ${unit === 'kg' ? 'badge-info' : 'badge-success'}">${unit}</span>`
                        : `<span style="color:var(--text-muted);font-size:0.8rem">—</span>`;
                    const stockDisplay = stock <= 0
                        ? `<span class="badge badge-danger">Out of stock</span>`
                        : stock <= 5
                        ? `<span class="badge badge-warning" title="Low stock">${stock % 1 === 0 ? stock : stock.toFixed(3)} ${unit || ''}</span>`
                        : `<span style="font-family:'JetBrains Mono',monospace;color:var(--success)">${stock % 1 === 0 ? stock : stock.toFixed(3)} ${unit || ''}</span>`;

                    html += `<tr>
                        <td id="item-name-${item.id}">${escapeHtml(item.name)}</td>
                        <td>${priceDisplay}</td>
                        <td>${unitBadge}</td>
                        <td>${stockDisplay}</td>
                        <td>${formatDate(item.created_at)}</td>
                        <td>
                            <div style="display:flex;gap:8px;flex-wrap:wrap">
                                <button class="btn btn-secondary btn-sm" onclick="startEditItem(${item.id}, '${escapeHtml(item.name).replace(/'/g, "\\'")}', ${p ? p.id : 'null'}, ${p ? p.item_id : 'null'}, ${p ? parseFloat(p.price) : 0}, '${unit || 'pcs'}', ${stock})">✏️ Edit</button>
                                <button class="btn btn-danger btn-sm" onclick="deleteItem(${item.id})">🗑️ Delete</button>
                            </div>
                        </td>
                    </tr>`;
                });

                html += '</tbody></table></div>';
                container.innerHTML = html;
            } catch (err) {
                container.innerHTML = '<div class="empty-state"><h3>Error loading inventory</h3></div>';
            }
        }

        // Keep these as aliases so other code that calls them still works
        async function renderItemsList()  { await renderInventoryList(); }
        async function renderPricesList() { await renderInventoryList(); }

        function startEditItem(itemId, currentName, priceId, priceItemId, currentPrice, currentUnit, currentStock) {
            // Close any open edit rows first, then open this one
            renderInventoryList().then(() => {
                const cell = document.getElementById(`item-name-${itemId}`);
                if (!cell) return;
                cell.innerHTML = `
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                    <input type="text" id="edit-item-input-${itemId}" value="${escapeHtml(currentName)}"
                        style="padding:6px 10px;background:var(--bg-tertiary);border:1px solid var(--accent);border-radius:var(--radius-sm);color:var(--text-primary);font-size:0.875rem;font-family:inherit;outline:none;min-width:120px;flex:1"
                        onkeydown="if(event.key==='Enter')saveEditItem(${itemId},${priceId},${priceItemId});if(event.key==='Escape')renderInventoryList()">
                    <input type="number" id="edit-item-price-${itemId}" value="${currentPrice}" min="0" step="0.01"
                        style="padding:6px 10px;background:var(--bg-tertiary);border:1px solid var(--accent);border-radius:var(--radius-sm);color:var(--text-primary);font-size:0.875rem;font-family:'JetBrains Mono',monospace;outline:none;width:90px"
                        placeholder="Price" onkeydown="if(event.key==='Enter')saveEditItem(${itemId},${priceId},${priceItemId});if(event.key==='Escape')renderInventoryList()">
                    <input type="number" id="edit-item-stock-${itemId}" value="${currentStock}" min="0" step="0.001"
                        style="padding:6px 10px;background:var(--bg-tertiary);border:1px solid var(--accent);border-radius:var(--radius-sm);color:var(--text-primary);font-size:0.875rem;font-family:'JetBrains Mono',monospace;outline:none;width:80px"
                        placeholder="Stock" onkeydown="if(event.key==='Enter')saveEditItem(${itemId},${priceId},${priceItemId});if(event.key==='Escape')renderInventoryList()">
                    <div style="display:flex;border:1px solid var(--border);border-radius:var(--radius-sm);overflow:hidden">
                        <button type="button" id="edit-unit-pcs-${itemId}" onclick="setInlineUnit(${itemId},'pcs')"
                            style="padding:5px 10px;border:none;font-weight:600;font-size:0.75rem;font-family:inherit;cursor:pointer;background:${currentUnit==='pcs'?'var(--accent)':'var(--bg-tertiary)'};color:${currentUnit==='pcs'?'white':'var(--text-secondary)'}">pcs</button>
                        <button type="button" id="edit-unit-kg-${itemId}" onclick="setInlineUnit(${itemId},'kg')"
                            style="padding:5px 10px;border:none;font-weight:600;font-size:0.75rem;font-family:inherit;cursor:pointer;background:${currentUnit==='kg'?'var(--accent)':'var(--bg-tertiary)'};color:${currentUnit==='kg'?'white':'var(--text-secondary)'}">kg</button>
                    </div>
                    <input type="hidden" id="edit-item-unit-${itemId}" value="${currentUnit}">
                    <button class="btn btn-success btn-sm" onclick="saveEditItem(${itemId},${priceId},${priceItemId})">Save</button>
                    <button class="btn btn-secondary btn-sm" onclick="renderInventoryList()">Cancel</button>
                </div>`;
                document.getElementById(`edit-item-input-${itemId}`).focus();
            });
        }

        function setInlineUnit(itemId, unit) {
            document.getElementById(`edit-item-unit-${itemId}`).value = unit;
            const pcsBtn = document.getElementById(`edit-unit-pcs-${itemId}`);
            const kgBtn  = document.getElementById(`edit-unit-kg-${itemId}`);
            pcsBtn.setAttribute('style', `padding:5px 10px;border:none;font-weight:600;font-size:0.75rem;font-family:inherit;cursor:pointer;background:${unit==='pcs'?'var(--accent)':'var(--bg-tertiary)'};color:${unit==='pcs'?'white':'var(--text-secondary)'}`);
            kgBtn.setAttribute('style',  `padding:5px 10px;border:none;font-weight:600;font-size:0.75rem;font-family:inherit;cursor:pointer;background:${unit==='kg'?'var(--accent)':'var(--bg-tertiary)'};color:${unit==='kg'?'white':'var(--text-secondary)'}`);
        }

        async function saveEditItem(itemId, priceId, priceItemId) {
            const nameInput  = document.getElementById(`edit-item-input-${itemId}`);
            const priceInput = document.getElementById(`edit-item-price-${itemId}`);
            const stockInput = document.getElementById(`edit-item-stock-${itemId}`);
            const unitInput  = document.getElementById(`edit-item-unit-${itemId}`);
            const name  = nameInput  ? nameInput.value.trim() : '';
            const price = priceInput ? parseFloat(priceInput.value) : NaN;
            const stock = stockInput ? parseFloat(stockInput.value) : 0;
            const unit  = unitInput  ? unitInput.value : 'pcs';
            if (!name) return;
            try {
                await api('update_item', 'POST', { id: itemId, name, stock: isNaN(stock) ? 0 : stock });
                if (!isNaN(price) && price >= 0 && priceItemId) {
                    await api('add_price', 'POST', { item_id: priceItemId, price, unit });
                }
                await renderInventoryList();
                await loadItemSelect('debtItemSelect');
                pricesCache = await api('get_prices');
                checkLowStock();
            } catch (err) {
                showAlert('itemError', err.message);
            }
        }

        async function deleteItem(id) {
            if (!confirm('Delete this item? This cannot be undone.')) return;
            try {
                await api('delete_item', 'POST', { id });
                await renderInventoryList();
                await loadItemSelect('debtItemSelect');
                pricesCache = await api('get_prices');
                checkLowStock();
            } catch (err) {
                showAlert('itemError', err.message);
            }
        }

        // Keep startEditPrice/saveEditPrice/setEditUnit/deletePrice working via the unified list
        function startEditPrice(id, itemId, currentPrice, currentUnit) {
            startEditItem(itemId, '', id, itemId, currentPrice, currentUnit);
        }
        async function saveEditPrice(id, itemId) {
            const input     = document.getElementById(`edit-price-input-${id}`);
            const unitInput = document.getElementById(`edit-unit-val-${id}`);
            const price = input ? parseFloat(input.value) : NaN;
            const unit  = unitInput ? unitInput.value : 'pcs';
            if (isNaN(price) || price < 0) { showAlert('itemError', 'Please enter a valid price.'); return; }
            try {
                await api('add_price', 'POST', { item_id: itemId, price, unit });
                pricesCache = await api('get_prices');
                await renderInventoryList();
            } catch (err) { showAlert('itemError', err.message); }
        }
        async function deletePrice(id) {
            if (!confirm('Remove this price?')) return;
            try {
                await api('delete_price', 'POST', { id });
                pricesCache = await api('get_prices');
                await renderInventoryList();
            } catch (err) { showAlert('itemError', err.message); }
        }

        async function renderDebtList() {
            const container = document.getElementById('debtListContainer');
            try {
                debtsCache = await api('get_debts');
                const q = (document.getElementById('debtSearch')?.value || '').toLowerCase().trim();
                renderDebtTable(q ? debtsCache.filter(d =>
                    d.customer_name.toLowerCase().includes(q) ||
                    d.item_name.toLowerCase().includes(q) ||
                    (d.phone || '').toLowerCase().includes(q)
                ) : debtsCache);
            } catch (err) {
                container.innerHTML = '<div class="empty-state"><h3>Error loading debts</h3></div>';
            }
        }

        function renderDebtTable(debts) {
            const container = document.getElementById('debtListContainer');
            if (!debts.length) {
                container.innerHTML = '<div class="empty-state"><div class="icon">📋</div><h3>No results</h3><p>No matching debt records found</p></div>';
                return;
            }
            let html = `<div class="table-container"><table><thead><tr>
                <th>Customer</th><th>Phone</th><th>Item</th><th>Qty</th>
                <th>Total</th><th>Paid</th><th>Balance</th><th>Interest</th>
                <th>Due Date</th><th>Status</th><th>Action</th>
            </tr></thead><tbody>`;
            debts.forEach(debt => {
                const isOverdue      = new Date(debt.due_date) < new Date();
                const dateStyle      = isOverdue ? 'style="color:var(--danger);font-weight:600"' : '';
                const overdueText    = isOverdue ? ' ⚠️' : '';
                const total          = parseFloat(debt.total_amount);
                const paid           = parseFloat(debt.amount_paid || 0);
                const interest       = parseFloat(debt.interest_accrued || 0);
                const balanceWithInt = parseFloat(debt.balance_with_interest || (total - paid));
                const daysOverdue    = parseInt(debt.days_overdue || 0);
                const rate           = parseFloat(debt.interest_rate || 0);
                const hasPartial     = paid > 0;
                const hasInterest    = interest > 0;

                const balanceCell = `<span style="font-family:'JetBrains Mono',monospace;color:${hasInterest ? 'var(--danger)' : hasPartial ? 'var(--warning)' : 'var(--text-primary)'};font-weight:${hasInterest || hasPartial ? '600' : '400'}">₱${balanceWithInt.toFixed(2)}</span>`;

                const paidCell = hasPartial
                    ? `<span style="font-family:'JetBrains Mono',monospace;color:var(--success)">₱${paid.toFixed(2)}</span>`
                    : `<span style="color:var(--text-muted)">—</span>`;

                const interestCell = hasInterest
                    ? `<div style="line-height:1.4">
                        <span style="font-family:'JetBrains Mono',monospace;color:var(--danger);font-weight:600">+₱${interest.toFixed(2)}</span>
                        <div style="font-size:0.7rem;color:var(--text-muted)">${rate}%/day × ${daysOverdue}d</div>
                       </div>`
                    : rate > 0
                        ? `<span style="font-size:0.75rem;color:var(--text-muted)">${rate}%/day</span>`
                        : `<span style="color:var(--text-muted)">—</span>`;

                const statusBadge = hasInterest
                    ? `<span class="badge badge-danger">Overdue</span>`
                    : hasPartial
                        ? `<span class="badge badge-warning">Partial</span>`
                        : `<span class="badge badge-danger">Unpaid</span>`;

                html += `<tr${hasInterest ? ' style="background:rgba(239,68,68,0.04)"' : ''}>
                    <td>${escapeHtml(debt.customer_name)}</td>
                    <td style="font-size:0.8125rem;color:var(--text-secondary)">${debt.phone ? escapeHtml(debt.phone) : '<span style="color:var(--text-muted)">—</span>'}</td>
                    <td>${escapeHtml(debt.item_name)}</td>
                    <td>${debt.quantity}</td>
                    <td style="font-family:'JetBrains Mono',monospace">₱${total.toFixed(2)}</td>
                    <td>${paidCell}</td>
                    <td>${balanceCell}</td>
                    <td>${interestCell}</td>
                    <td ${dateStyle}>${formatDate(debt.due_date)}${overdueText}</td>
                    <td>${statusBadge}</td>
                    <td>
                        <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
                            <input type="number" id="payment-input-${debt.id}" min="0.01" step="0.01"
                                placeholder="₱ amount"
                                style="padding:5px 8px;width:100px;background:var(--bg-tertiary);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text-primary);font-size:0.8125rem;font-family:'JetBrains Mono',monospace;outline:none"
                                onkeydown="if(event.key==='Enter')applyPayment(${debt.id})"
                                onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border)'">
                            <button class="btn btn-success btn-sm" onclick="applyPayment(${debt.id})">Pay</button>
                            <button class="btn btn-secondary btn-sm" onclick="markAsPaid(${debt.id})" title="Mark fully paid">✓ Full</button>
                            <button class="btn btn-secondary btn-sm" onclick="startEditDebt(${debt.id}, ${rate}, '${debt.due_date}', ${JSON.stringify(debt.notes || '')})">✏️</button>
                        </div>
                    </td>
                </tr>`;
            });
            html += '</tbody></table></div>';
            container.innerHTML = html;
        }

        let paidCache = [];
        let debtsCache = [];

        async function renderPaidHistory() {
            const container = document.getElementById('paidListContainer');
            try {
                paidCache = await api('get_paid');
                renderPaidTable(paidCache);
            } catch (err) {
                container.innerHTML = '<div class="empty-state"><h3>Error loading history</h3></div>';
            }
        }

        function renderPaidTable(data) {
            const container = document.getElementById('paidListContainer');
            if (!data.length) {
                container.innerHTML = '<div class="empty-state"><div class="icon">📜</div><h3>No results</h3><p>No matching records found</p></div>';
                return;
            }
            let html = '<div class="table-container"><table><thead><tr><th>Customer</th><th>Item</th><th>Qty</th><th>Amount</th><th>Paid Date</th><th>Status</th></tr></thead><tbody>';
            data.forEach(p => {
                html += `<tr>
                    <td>${escapeHtml(p.customer_name)}</td>
                    <td>${escapeHtml(p.item_name)}</td>
                    <td>${p.quantity}</td>
                    <td style="font-family:'JetBrains Mono',monospace">₱${parseFloat(p.total_amount).toFixed(2)}</td>
                    <td>${formatDate(p.paid_at)}</td>
                    <td><span class="badge badge-success">Paid</span></td>
                </tr>`;
            });
            html += '</tbody></table></div>';
            container.innerHTML = html;
        }

        function filterHistory() {
            const q = document.getElementById('historySearch').value.toLowerCase().trim();
            if (!q) { renderPaidTable(paidCache); return; }
            renderPaidTable(paidCache.filter(p =>
                p.customer_name.toLowerCase().includes(q) ||
                p.item_name.toLowerCase().includes(q)
            ));
        }

        function filterDebts() {
            const q = document.getElementById('debtSearch').value.toLowerCase().trim();
            if (!q) { renderDebtTable(debtsCache); return; }
            renderDebtTable(debtsCache.filter(d =>
                d.customer_name.toLowerCase().includes(q) ||
                d.item_name.toLowerCase().includes(q) ||
                (d.phone || '').toLowerCase().includes(q)
            ));
        }

        function showAlert(id, msg) {
            const el = document.getElementById(id);
            if (msg) el.innerHTML = '<span>⚠️</span> ' + escapeHtml(msg);
            el.classList.add('show');
            setTimeout(() => el.classList.remove('show'), 3000);
        }

        function resetForm(formId) {
            document.getElementById(formId).reset();
            if (formId === 'debtForm') updateDebtTotal();
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDate(dateStr) {
            if (!dateStr) return '-';
            return new Date(dateStr).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        }

        window.onload = async function() {
            initTheme();
            <?php if ($isLoggedIn): ?>
                document.getElementById('navbar').style.display = 'block';
                showPage('dashboard');
            <?php else: ?>
                try {
                    const result = await api('check_auth');
                    if (result.authenticated) {
                        document.getElementById('navbar').style.display = 'block';
                        showPage('dashboard');
                    }
                } catch(e) {}
            <?php endif; ?>
        };
    </script>
    <!-- Logout overlay -->
    <div id="logoutOverlay" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,17,23,0.92);backdrop-filter:blur(8px);align-items:center;justify-content:center;flex-direction:column;gap:16px;transition:opacity 0.4s ease">
        <div style="width:64px;height:64px;background:linear-gradient(135deg,var(--gradient-start),var(--gradient-end));border-radius:var(--radius-lg);display:flex;align-items:center;justify-content:center;font-size:1.75rem;box-shadow:0 8px 32px rgba(99,102,241,0.35);animation:popIn 0.4s cubic-bezier(0.34,1.56,0.64,1)">
            👋
        </div>
        <div style="text-align:center">
            <div style="font-size:1.25rem;font-weight:700;color:var(--text-primary);margin-bottom:8px" id="logoutMsg">Thank you, logged out successfully</div>
            <div id="logoutSub" style="font-size:0.9rem;color:var(--text-secondary);opacity:0;transition:opacity 0.4s ease">Redirecting to login page…</div>
        </div>
        <div style="width:200px;height:3px;background:var(--bg-tertiary);border-radius:2px;overflow:hidden;margin-top:8px">
            <div style="height:100%;background:linear-gradient(90deg,var(--gradient-start),var(--gradient-end));border-radius:2px;animation:logoutBar 2.8s linear forwards"></div>
        </div>
    </div>

    <style>
        @keyframes popIn {
            from { transform: scale(0.5); opacity: 0; }
            to   { transform: scale(1);   opacity: 1; }
        }
        @keyframes logoutBar {
            from { width: 0%; }
            to   { width: 100%; }
        }
    </style>

</body>
</html> 