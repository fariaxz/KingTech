<?php
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit();
}

// Dados do usuário
$usuario_nome = $_SESSION["usuario_nome"];
$usuario_email = $_SESSION["usuario_email"];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="2.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <title>KingTech - Bem-vindo, <?= htmlspecialchars($usuario_nome) ?>!</title>
    
    <style>
        /* ====================
           Variáveis e Reset
           ==================== */
        :root {
            --primary-gold: #cea348;
            --primary-dark: #0d1b2a;
            --secondary-dark: #1b365d;
            --light-bg: #f8f9fa;
            --card-bg: #ffffff;
            --text-dark: #1a1a1a;
            --text-gray: #636363;
            --shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
            --shadow-hover: 0 12px 35px rgba(0, 0, 0, 0.18);
            --border-radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            scroll-behavior: smooth;
            list-style: none;
            text-decoration: none;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            line-height: 1.6;
            color: var(--text-dark);
        }

        /* Scrollbar Personalizada */
        body::-webkit-scrollbar {
            width: 15px;
        }

        body::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        body::-webkit-scrollbar-thumb {
            background: linear-gradient(45deg, var(--primary-gold), #e0b46d);
            border-radius: 4px;
        }

        body::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(45deg, #e0b46d, var(--primary-gold));
        }

        /* ====================
           Barra de Boas-vindas
           ==================== */
        .welcome-bar {
            background: linear-gradient(45deg, var(--primary-gold), #e0b46d);
            color: var(--primary-dark);
            text-align: center;
            padding: 0.8rem;
            font-weight: 600;
            font-size: 0.95rem;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 2px 10px rgba(206, 163, 72, 0.3);
        }

        .welcome-bar i {
            color: var(--primary-dark);
            font-size: 1.1rem;
        }

        /* ====================
         Navegação (Navbar)
         ==================== */
        nav {
            background: linear-gradient(to left, #0d1b2a, #1b365d);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            flex-wrap: wrap;
        }

        nav ul {
            display: flex;
            align-items: center;
            margin-right: 20px;
            gap: 1rem;
        }

        nav ul li {
            display: inline-block;
            line-height: 80px;
            margin: 0 5px;
        }

        nav ul li a {
            color: #fffcf6;
            font-size: 17px;
            padding: 7px 17px;
            border-radius: 3px;
            text-transform: uppercase;
            font-weight: 600;
            text-decoration: none;
        }

        .anav:hover {
            background: #cea348;
            transition: .3s;
        }

        .active {
            background: #cea348;
            transition: .3s;
        }

        .logo {
            width: 80px;
            height: 80px;
        }

        /* ====================
           Menu de Usuário
           ==================== */
        .user-menu {
            position: relative;
            display: inline-block;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.5rem 1rem;
            background: rgba(206, 163, 72, 0.1);
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            border: 2px solid transparent;
        }

        .user-profile:hover {
            background: rgba(206, 163, 72, 0.2);
            border-color: var(--primary-gold);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary-gold);
            color: var(--primary-dark);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.2rem;
        }

        .user-name {
            color: #fffcf6;
            font-weight: 600;
            font-size: 1rem;
        }

        .dropdown-arrow {
            color: var(--primary-gold);
            font-size: 0.8rem;
            transition: var(--transition);
        }

        .user-menu:hover .dropdown-arrow {
            transform: rotate(180deg);
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: var(--card-bg);
            min-width: 200px;
            box-shadow: var(--shadow);
            border-radius: var(--border-radius);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: var(--transition);
            z-index: 1000;
            border: 1px solid rgba(206, 163, 72, 0.2);
        }

        .user-menu:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.8rem 1.2rem;
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            border-bottom: 1px solid rgba(206, 163, 72, 0.1);
        }

        .dropdown-item:last-child {
            border-bottom: none;
        }

        .dropdown-item:hover {
            background: rgba(206, 163, 72, 0.1);
            color: var(--primary-gold);
        }

        .dropdown-item i {
            color: var(--primary-gold);
            width: 20px;
        }

        /* ====================
           Dashboard do Usuário
           ==================== */
        .user-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            text-align: center;
            transition: var(--transition);
            border: 1px solid rgba(206, 163, 72, 0.1);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(45deg, var(--primary-gold), #e0b46d);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(45deg, var(--primary-gold), #e0b46d);
            color: var(--primary-dark);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-gray);
            font-weight: 500;
            font-size: 1rem;
        }

        .divespaco {
            padding: 2rem 0;
        }

        /* ====================
           Carrossel (mantido igual ao index.html)
           ==================== */
        .carrossel-container {
            max-width: 100%;
            margin: 3rem auto;
            padding: 0 2rem;
            position: relative;
        }

        .carrossel-interno {
            position: relative;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            background: var(--card-bg);
            width: 100%;
            aspect-ratio: 2540 / 423;
            max-height: 500px;
        }

        .carrossel-item {
            display: none;
            width: 100%;
            height: 100%;
            position: relative;
            opacity: 0;
            transition: opacity 0.6s ease-in-out;
        }

        .carrossel-item.ativo {
            display: block;
            opacity: 1;
        }

        .carrossel-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            border-radius: var(--border-radius);
            display: block;
        }

        .carrossel-anterior,
        .carrossel-proximo {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(13, 27, 42, 0.8);
            color: white;
            border: none;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            font-size: 24px;
            cursor: pointer;
            transition: var(--transition);
            backdrop-filter: blur(10px);
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            user-select: none;
        }

        .carrossel-anterior {
            left: 20px;
        }

        .carrossel-proximo {
            right: 20px;
        }

        .carrossel-anterior:hover,
        .carrossel-proximo:hover {
            background: var(--primary-gold);
            color: var(--primary-dark);
            transform: translateY(-50%) scale(1.1);
            box-shadow: 0 6px 20px rgba(206, 163, 72, 0.4);
        }

        .carrossel-anterior:active,
        .carrossel-proximo:active {
            transform: translateY(-50%) scale(0.95);
        }

        .carrossel-dots {
            text-align: center;
            margin-top: 2rem;
        }

        .dot {
            display: inline-block;
            width: 16px;
            height: 16px;
            margin: 0 8px;
            background: #ddd;
            border-radius: 50%;
            cursor: pointer;
            transition: var(--transition);
        }

        .dot.ativo {
            background: var(--primary-gold);
            transform: scale(1.2);
        }

        .dot:hover {
            background: var(--secondary-dark);
            transform: scale(1.1);
        }

        /* ====================
           Cards de Serviços (mantido igual ao index.html)
           ==================== */
        .services-section {
            padding: 4rem 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            font-size: 3rem;
            font-weight: 800;
            color: var(--primary-dark);
            margin-bottom: 1rem;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(45deg, var(--primary-gold), #e0b46d);
            border-radius: 2px;
        }

        .section-subtitle {
            text-align: center;
            font-size: 1.2rem;
            color: var(--text-gray);
            margin-bottom: 3rem;
            font-weight: 400;
        }

        .car1 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin: 0 auto;
            max-width: 1200px;
        }

        .clickable-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 2rem;
            text-align: center;
            box-shadow: var(--shadow);
            transition: var(--transition);
            cursor: pointer;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(206, 163, 72, 0.1);
        }

        .clickable-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(206, 163, 72, 0.1), transparent);
            transition: var(--transition);
        }

        .clickable-card:hover::before {
            left: 100%;
        }

        .clickable-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-hover);
            border-color: var(--primary-gold);
        }

        .card-image {
            width: 120px;
            height: 120px;
            margin: 0 auto 1.5rem;
            border-radius: 20px;
            object-fit: cover;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(206, 163, 72, 0.2);
        }

        .clickable-card:hover .card-image {
            transform: scale(1.1) rotate(5deg);
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 1rem;
            transition: var(--transition);
        }

        .clickable-card:hover .card-title {
            color: var(--primary-gold);
        }

        .card-description {
            font-size: 1rem;
            color: var(--text-gray);
            line-height: 1.6;
            font-weight: 400;
        }

        /* ====================
           Seção de Contato (mantido igual ao index.html)
           ==================== */
        .contact-container {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--secondary-dark) 100%);
            padding: 4rem 2rem;
            position: relative;
            overflow: hidden;
        }

        .contact-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(206,163,72,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .contact-title {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary-gold);
            margin-bottom: 3rem;
            position: relative;
            z-index: 1;
        }

        .dconta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .map-container {
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            background: var(--card-bg);
            padding: 1rem;
        }

        .map-container iframe {
            width: 100%;
            height: 400px;
            border-radius: var(--border-radius);
            border: none;
        }

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 2rem;
            color: var(--primary-gold);
        }

        .contact-item,
        .contact-item1 {
            background: rgba(255, 255, 255, 0.1);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(206, 163, 72, 0.2);
            transition: var(--transition);
        }

        .contact-item:hover,
        .contact-item1:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateX(10px);
        }

        .h1conta {
            font-size: 1.3rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .h1conta::before {
            content: '';
            width: 4px;
            height: 30px;
            background: var(--primary-gold);
            border-radius: 2px;
        }

        /* ====================
           Footer (mantido igual ao index.html)
           ==================== */
        footer {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--secondary-dark) 100%);
            position: relative;
            overflow: hidden;
        }

        footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-gold), #e0b46d, var(--primary-gold));
        }

        .footer-distributed {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 3rem;
            padding: 3rem 2rem;
            color: white;
        }

        .footer-distributed h3 {
            color: var(--primary-gold);
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }

        .footer-links {
            color: #ffffff;
            margin: 1rem 0;
            font-weight: 500;
        }

        .footer-links a {
            color: inherit;
            margin: 0 0.5rem;
            transition: var(--transition);
            position: relative;
        }

        .footer-links a:hover {
            color: var(--primary-gold);
        }

        .footer-company-name {
            color: var(--primary-gold);
            font-size: 14px;
            font-weight: 400;
        }

        .footer-center i {
            background: var(--primary-gold);
            color: var(--primary-dark);
            font-size: 20px;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            transition: var(--transition);
        }

        .footer-center i:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(206, 163, 72, 0.4);
        }

        .footer-center p {
            color: var(--primary-gold);
            font-weight: 500;
            display: flex;
            align-items: center;
            margin: 1rem 0;
        }

        .footer-company-about {
            color: #ffffff;
            line-height: 1.8;
            font-weight: 400;
        }

        .footer-company-about span {
            display: block;
            color: var(--primary-gold);
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .email {
            color: white;
        }

        .email:hover {
            color: #cea348;
        }

        /* ====================
           Responsividade
           ==================== */
        @media (max-width: 768px) {
            .welcome-bar {
                font-size: 0.85rem;
                flex-direction: column;
                gap: 0.5rem;
            }

            nav {
                flex-direction: column;
                padding: 1rem;
                gap: 1rem;
            }

            .logo {
                width: 60px;
                height: 60px;
            }

            nav ul {
                flex-wrap: wrap;
                justify-content: center;
                gap: 1rem;
            }

            .user-dashboard {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1.5rem;
                padding: 0 1rem;
            }

            .stat-card {
                padding: 1.5rem;
            }

            .stat-number {
                font-size: 2rem;
            }

            .section-title {
                font-size: 2.5rem;
            }

            .car1 {
                grid-template-columns: 1fr;
                gap: 1.5rem;
                padding: 0 1rem;
            }

            .clickable-card {
                padding: 1.5rem;
            }

            .dconta {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .contact-title {
                font-size: 2rem;
            }

            .map-container iframe {
                height: 300px;
            }

            .carrossel-anterior,
            .carrossel-proximo {
                width: 50px;
                height: 50px;
                font-size: 20px;
            }

            .carrossel-anterior {
                left: 10px;
            }

            .carrossel-proximo {
                right: 10px;
            }

            .user-profile {
                flex-direction: column;
                gap: 0.5rem;
            }

            .dropdown-menu {
                right: -50px;
            }
        }

        @media (max-width: 480px) {
            .section-title {
                font-size: 2rem;
            }

            .contact-title {
                font-size: 1.5rem;
            }

            .car1 {
                padding: 0 0.5rem;
            }

            .clickable-card {
                padding: 1rem;
            }

            .h1conta {
                font-size: 1.1rem;
            }

            .user-dashboard {
                grid-template-columns: 1fr;
                padding: 0 0.5rem;
            }
        }

        /* ====================
           Animações e Efeitos
           ==================== */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes countUp {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .clickable-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .contact-item,
        .contact-item1 {
            animation: slideIn 0.6s ease-out;
        }

        .stat-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .stat-number {
            animation: countUp 0.8s ease-out;
        }

        /* Efeito de hover no container de contato */
        .contact-container {
            position: relative;
        }

        .contact-container::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 50% 50%, rgba(206, 163, 72, 0.1) 0%, transparent 70%);
            pointer-events: none;
        }
    </style>
</head>
<body>

    <!-- Navegação -->
    <nav>
        <img src="1.png" alt="KingTech Logo" class="logo">
        <ul>
            <li><a class="active" href="#">Home</a></li>
            <li><a class="anav" href="servicos.php">Serviços</a></li>
            <li>
                <div class="user-menu">
                    <div class="user-profile">
                        <div class="user-avatar">
                            <?= strtoupper(substr($usuario_nome, 0, 1)) ?>
                        </div>
                        <div class="user-name">
                            <?= htmlspecialchars(explode(' ', $usuario_nome)[0]) ?>
                        </div>
                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </div>
                    <div class="dropdown-menu">
                        <a href="pedidos.php" class="dropdown-item">
                            <i class="fas fa-history"></i>
                            Pedidos
                        </a>
                        <a href="index.html" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i>
                            Sair
                        </a>
                    </div>
                </div>
            </li>
        </ul>
    </nav>

    <!-- Carrossel Principal -->
    <div class="carrossel-container">
        <div class="carrossel-interno">
            <div class="carrossel-item ativo">
                <img src="banner (1).png" alt="Banner 1 - Serviços de Tecnologia">
            </div>
            <div class="carrossel-item">
                <img src="banner (2).png" alt="Banner 2 - Reparos Especializados">
            </div>
            <div class="carrossel-item">
                <img src="banner (3).png" alt="Banner 3 - Qualidade Garantida">
            </div>

            <button class="carrossel-anterior" aria-label="Imagem anterior">‹</button>
            <button class="carrossel-proximo" aria-label="Próxima imagem">›</button>
        </div>
        
        <div class="carrossel-dots">
            <span class="dot ativo" data-slide="0"></span>
            <span class="dot" data-slide="1"></span>
            <span class="dot" data-slide="2"></span>
        </div>
    </div>

    <!-- Seção de Serviços -->
    <section class="services-section">
        <h2 class="section-title">Nossos Serviços</h2>
        <p class="section-subtitle">Especialistas em soluções tecnológicas com qualidade e agilidade</p>
        
        <div class="car1">
            <a class="clickable-card" href="servicos.php?tipo=celulares">
                <img src="card (1).png" alt="Orçamento para Celulares" class="card-image">
                <h3 class="card-title">Orçamento para Celulares!</h3>
                <p class="card-description">Realize aqui o orçamento para o reparo ou manutenção do seu celular com nossa equipe especializada.</p>
            </a>
            
            <a class="clickable-card" href="servicos.php?tipo=computadores">
                <img src="card (2).png" alt="Orçamento para Computadores" class="card-image">
                <h3 class="card-title">Orçamento para Computadores!</h3>
                <p class="card-description">Realize aqui o orçamento para o reparo ou manutenção do seu PC com garantia de qualidade.</p>
            </a>
            
            <a class="clickable-card" href="servicos.php?tipo=eletrodomesticos">
                <img src="card (3).png" alt="Orçamento para Eletrodomésticos" class="card-image">
                <h3 class="card-title">Orçamento para Eletrodomésticos!</h3>
                <p class="card-description">Realize aqui o orçamento para o reparo ou manutenção do seu eletrodoméstico com preço justo.</p>
            </a>
        </div>
    </section>

    <!-- Seção de Contato -->
    <div class="contact-container">
        <h2 class="contact-title">Entre em contato ou venha conhecer nossa loja!</h2>
        
        <div class="dconta">
            <div class="map-container">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d917.9506439438097!2d-45.55241623038526!3d-23.031019964488223!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x94ccf910e88f4643%3A0x73ee8435979af65e!2sR.%20do%20Caf%C3%A9%2C%20179%20-%20Centro%2C%20Taubat%C3%A9%20-%20SP%2C%2012010-330!5e0!3m2!1spt-BR!2sbr!4v1747394417437!5m2!1spt-BR!2sbr" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade"
                    title="Localização da KingTech">
                </iframe>
            </div>
            
            <div class="contact-info">
                <div class="contact-item">
                    <h3 class="h1conta">📍 Endereço</h3>
                    <p>R. do Café, 179 - Centro, Taubaté - SP, 12010-330</p>
                </div>
                
                <div class="contact-item1">
                    <h3 class="h1conta">📞 Telefone</h3>
                    <p>(12) 3421-9844</p>
                </div>
                
                <div class="contact-item1">
                    <h3 class="h1conta">💬 WhatsApp</h3>
                    <p>(12) 99254-9069</p>
                </div>
                
                <div class="contact-item1">
                    <h3 class="h1conta">🕒 Horário de Funcionamento</h3>
                    <p>Segunda a Sexta — 09:00h às 18:00h<br>
                    Sábado 09:00h às 12:00h</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-distributed">
            <div class="footer-left">
                <h3>KingTech</h3>
                <p class="footer-links">
                    <a href="index_logado.php">Home</a> |
                    <a href="cliente.php">Serviços</a>
                </p>
                <p class="footer-company-name">Copyright © 2025 <strong>KingTech</strong> - Todos os direitos reservados</p>
            </div>
            
            <div class="footer-center">
                <p><i class="fa fa-map-marker"></i> Taubaté, São Paulo</p>
                <p><i class="fa fa-phone"></i> +55 (12) 99254-9069</p>
                <p><i class="fa fa-envelope"></i> <a href="mailto:kingtech@gmail.com" class="email">kingtech@gmail.com</a></p>
            </div>
            
            <div class="footer-right">
                <p class="footer-company-about">
                    <span>Sobre</span>
                    Na <strong>KingTech</strong>, somos especialistas em consertos e soluções tecnológicas. Atuamos com responsabilidade, agilidade e transparência para garantir o melhor atendimento em manutenção de celulares, computadores, notebooks e outros dispositivos eletrônicos. Nosso compromisso é devolver seu equipamento funcionando como novo, com qualidade e preço justo. Confie em quem entende: confie na KingTech!
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Espera o DOM ser completamente carregado
        document.addEventListener('DOMContentLoaded', function() {
            // Carrossel
            let currentSlide = 0;
            const slides = document.querySelectorAll('.carrossel-item');
            const dots = document.querySelectorAll('.dot');
            const totalSlides = slides.length;
            const nextBtn = document.querySelector('.carrossel-proximo');
            const prevBtn = document.querySelector('.carrossel-anterior');
    
            // Exibir o slide baseado no índice
            function showSlide(n) {
                // Remover a classe ativa do slide e do dot
                slides[currentSlide].classList.remove('ativo');
                dots[currentSlide].classList.remove('ativo');
                
                // Calcular o novo índice do slide
                currentSlide = (n + totalSlides) % totalSlides;
                
                // Adicionar a classe ativa no novo slide e dot
                slides[currentSlide].classList.add('ativo');
                dots[currentSlide].classList.add('ativo');
            }
    
            // Função para próximo slide
            function nextSlide() {
                showSlide(currentSlide + 1);
            }
    
            // Função para slide anterior
            function prevSlide() {
                showSlide(currentSlide - 1);
            }
    
            // Event listeners para os botões de próximo e anterior
            if (nextBtn) {
                nextBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    nextSlide();
                });
            }
    
            if (prevBtn) {
                prevBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    prevSlide();
                });
            }
    
            // Navegação com os dots
            dots.forEach((dot, index) => {
                dot.addEventListener('click', function(e) {
                    e.preventDefault();
                    showSlide(index);
                });
            });
    
            // Avanço automático do carrossel (opcional)
            let autoSlide = setInterval(nextSlide, 5000);
    
            // Pausar o avanço automático ao passar o mouse
            const carouselContainer = document.querySelector('.carrossel-container');
            if (carouselContainer) {
                carouselContainer.addEventListener('mouseenter', () => {
                    clearInterval(autoSlide);
                });
    
                carouselContainer.addEventListener('mouseleave', () => {
                    autoSlide = setInterval(nextSlide, 5000);
                });
            }
    
            // Navegação com as teclas do teclado (esquerda/direita)
            document.addEventListener('keydown', function(e) {
                if (e.key === 'ArrowLeft') {
                    prevSlide();
                } else if (e.key === 'ArrowRight') {
                    nextSlide();
                }
            });
        });

        // Animações ao carregar a página
        window.addEventListener('load', function() {
            const statNumbers = document.querySelectorAll('.stat-number');
            
            // Animar números das estatísticas
            statNumbers.forEach((stat, index) => {
                const finalValue = parseFloat(stat.textContent);
                let currentValue = 0;
                const increment = finalValue / 50;
                
                setTimeout(() => {
                    const timer = setInterval(() => {
                        currentValue += increment;
                        if (currentValue >= finalValue) {
                            stat.textContent = finalValue === 5.0 ? '5.0' : finalValue.toString();
                            clearInterval(timer);
                        } else {
                            stat.textContent = Math.floor(currentValue);
                        }
                    }, 30);
                }, index * 200);
            });
        });
    </script>
</body>
</html>