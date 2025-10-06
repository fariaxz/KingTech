<?php
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit();
}

// Dados do usuário
$usuario_id = $_SESSION["usuario_id"];
$usuario_nome = $_SESSION["usuario_nome"];
$usuario_email = $_SESSION["usuario_email"];

// Conectar ao banco de dados
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'OrcamentosManutencao';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Verificar e adicionar colunas necessárias se não existirem
$conn->query("ALTER TABLE Computadores ADD COLUMN IF NOT EXISTS valor_servico DECIMAL(10,2) DEFAULT NULL");
$conn->query("ALTER TABLE Celulares ADD COLUMN IF NOT EXISTS valor_servico DECIMAL(10,2) DEFAULT NULL");
$conn->query("ALTER TABLE Eletrodomesticos ADD COLUMN IF NOT EXISTS valor_servico DECIMAL(10,2) DEFAULT NULL");
$conn->query("ALTER TABLE Computadores ADD COLUMN IF NOT EXISTS email VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE Celulares ADD COLUMN IF NOT EXISTS email VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE Eletrodomesticos ADD COLUMN IF NOT EXISTS email VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE Computadores ADD COLUMN IF NOT EXISTS data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
$conn->query("ALTER TABLE Celulares ADD COLUMN IF NOT EXISTS data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
$conn->query("ALTER TABLE Eletrodomesticos ADD COLUMN IF NOT EXISTS data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
$conn->query("ALTER TABLE Computadores ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'ativo'");
$conn->query("ALTER TABLE Celulares ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'ativo'");
$conn->query("ALTER TABLE Eletrodomesticos ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'ativo'");

// Processar cancelamento de pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_pedido'])) {
    $pedido_id = intval($_POST['pedido_id']);
    $pedido_tipo = $_POST['pedido_tipo'];
    
    $tabela = '';
    if ($pedido_tipo == 'Computador') $tabela = 'Computadores';
    elseif ($pedido_tipo == 'Celular') $tabela = 'Celulares';
    elseif ($pedido_tipo == 'Eletrodoméstico') $tabela = 'Eletrodomesticos';
    
    if ($tabela) {
        $sql = "UPDATE $tabela SET status = 'cancelado' WHERE id_cliente = ? AND (email = ? OR (email IS NULL AND id_cliente = ?))";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isi", $pedido_id, $usuario_email, $pedido_id);
        $stmt->execute();
        header("Location: pedidos.php?msg=cancelado");
        exit();
    }
}

// Buscar pedidos do cliente usando o EMAIL do usuário
$pedidos = [];

// Buscar em Computadores
$sql = "SELECT 'Computador' as tipo, id_cliente, nome_cliente, email, whatsapp, tipo_servico, 
        servico_solicitado, problema_reportado, valor_servico, data_cadastro, status
        FROM Computadores 
        WHERE (email = ? OR (email IS NULL AND id_cliente = ?)) AND status != 'cancelado'
        ORDER BY data_cadastro DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $usuario_email, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $pedidos[] = $row;
}

// Buscar em Celulares
$sql = "SELECT 'Celular' as tipo, id_cliente, nome_cliente, email, whatsapp, modelo_celular,
        tipo_servico, servico_solicitado, problema_reportado, valor_servico, data_cadastro, status
        FROM Celulares 
        WHERE (email = ? OR (email IS NULL AND id_cliente = ?)) AND status != 'cancelado'
        ORDER BY data_cadastro DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $usuario_email, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $pedidos[] = $row;
}

// Buscar em Eletrodomésticos
$sql = "SELECT 'Eletrodoméstico' as tipo, id_cliente, nome_cliente, email, whatsapp, 
        nome_eletrodomestico, marca, tipo_servico, servico_solicitado, 
        problema_reportado, valor_servico, data_cadastro, status
        FROM Eletrodomesticos 
        WHERE (email = ? OR (email IS NULL AND id_cliente = ?)) AND status != 'cancelado'
        ORDER BY data_cadastro DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $usuario_email, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $pedidos[] = $row;
}

// Ordenar todos os pedidos por data (mais recentes primeiro)
usort($pedidos, function($a, $b) {
    return strtotime($b['data_cadastro']) - strtotime($a['data_cadastro']);
});

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="2.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <title>KingTech - Meus Pedidos</title>
    
    <style>
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
            min-height: 100vh;
        }

        /* Scrollbar personalizada do index_logado.php */
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

        nav {
            background: linear-gradient(to left, #0d1b2a, #1b365d);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            flex-wrap: wrap;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
            transition: var(--transition);
        }

        nav ul li a:hover {
            background: #cea348;
        }

        .logo {
            width: 80px;
            height: 80px;
        }

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

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            animation: fadeInDown 0.6s ease-out;
        }

        .page-title {
            font-size: 3rem;
            font-weight: 800;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
            position: relative;
            display: inline-block;
        }

        .page-title::after {
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

        .page-subtitle {
            font-size: 1.2rem;
            color: var(--text-gray);
            font-weight: 400;
            margin-top: 1.5rem;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideIn 0.5s ease-out;
        }

        .alert-success {
            background: rgba(46, 204, 113, 0.1);
            border-left: 4px solid #2ecc71;
            color: #27ae60;
        }

        .alert i {
            font-size: 1.5rem;
        }

        .user-info-box {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 1rem;
            border-left: 4px solid var(--primary-gold);
        }

        .user-info-box i {
            color: var(--primary-gold);
            font-size: 1.5rem;
        }

        .user-info-text {
            color: var(--text-dark);
            font-weight: 500;
        }

        .filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            justify-content: center;
            animation: fadeInUp 0.6s ease-out;
        }

        .filter-btn {
            padding: 0.8rem 1.5rem;
            background: var(--card-bg);
            border: 2px solid var(--primary-gold);
            border-radius: 8px;
            color: var(--primary-dark);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Montserrat', sans-serif;
            font-size: 1rem;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: var(--primary-gold);
            color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .pedidos-list {
            display: grid;
            gap: 1.5rem;
            animation: fadeInUp 0.6s ease-out 0.2s both;
        }

        .pedido-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-left: 5px solid var(--primary-gold);
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 2rem;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .pedido-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-gold), transparent);
        }

        .pedido-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .pedido-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(45deg, var(--primary-gold), #e0b46d);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--primary-dark);
            flex-shrink: 0;
        }

        .pedido-info {
            flex: 1;
        }

        .pedido-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .pedido-id {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .pedido-tipo {
            display: inline-block;
            padding: 0.3rem 1rem;
            background: rgba(206, 163, 72, 0.1);
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--primary-gold);
        }

        .pedido-descricao {
            font-size: 1.1rem;
            color: var(--text-dark);
            margin-bottom: 0.8rem;
            font-weight: 500;
        }

        .pedido-details {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
            color: var(--text-gray);
            font-size: 0.95rem;
        }

        .pedido-detail {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .pedido-detail i {
            color: var(--primary-gold);
        }

        .pedido-actions {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            align-items: flex-end;
        }

        .pedido-valor {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--primary-gold);
        }

        .valor-pendente {
            color: var(--text-gray);
            font-size: 1.2rem;
            font-style: italic;
        }

        .btn-cancelar {
            padding: 0.6rem 1.2rem;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Montserrat', sans-serif;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-cancelar:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }

        .status-badge {
            padding: 0.5rem 1.2rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-limpeza {
            background: rgba(52, 152, 219, 0.1);
            color: #3498db;
            border: 2px solid #3498db;
        }

        .status-troca {
            background: rgba(230, 126, 34, 0.1);
            color: #e67e22;
            border: 2px solid #e67e22;
        }

        .status-trocadepecas {
            background: rgba(230, 126, 34, 0.1);
            color: #e67e22;
            border: 2px solid #e67e22;
        }

        .status-diagnostico {
            background: rgba(155, 89, 182, 0.1);
            color: #9b59b6;
            border: 2px solid #9b59b6;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .empty-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(45deg, var(--primary-gold), #e0b46d);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            font-size: 3rem;
            color: var(--primary-dark);
        }

        .empty-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 1rem;
        }

        .empty-text {
            font-size: 1.1rem;
            color: var(--text-gray);
            margin-bottom: 2rem;
        }

        .btn-primary {
            display: inline-block;
            padding: 1rem 2rem;
            background: linear-gradient(45deg, var(--primary-gold), #e0b46d);
            color: var(--primary-dark);
            font-weight: 700;
            border-radius: 8px;
            text-decoration: none;
            transition: var(--transition);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        /* Modal de confirmação */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease-out;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: var(--border-radius);
            max-width: 500px;
            width: 90%;
            box-shadow: var(--shadow-hover);
            animation: slideUp 0.3s ease-out;
            text-align: center;
        }

        .modal-icon {
            width: 80px;
            height: 80px;
            background: rgba(231, 76, 60, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.5rem;
            color: #e74c3c;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 1rem;
        }

        .modal-text {
            color: var(--text-gray);
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .btn-modal {
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Montserrat', sans-serif;
            font-size: 1rem;
        }

        .btn-confirmar {
            background: #e74c3c;
            color: white;
        }

        .btn-confirmar:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        .btn-voltar {
            background: var(--light-bg);
            color: var(--text-dark);
        }

        .btn-voltar:hover {
            background: #dee2e6;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

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

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .page-title {
                font-size: 2rem;
            }

            .pedido-card {
                grid-template-columns: 1fr;
                gap: 1.5rem;
                text-align: center;
            }

            .pedido-icon {
                margin: 0 auto;
            }

            .pedido-header {
                flex-direction: column;
                align-items: center;
            }

            .pedido-details {
                justify-content: center;
            }

            .pedido-actions {
                align-items: center;
                width: 100%;
            }

            .btn-cancelar {
                width: 100%;
                justify-content: center;
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

            .user-info-box {
                flex-direction: column;
                text-align: center;
            }

            .modal-content {
                width: 95%;
                padding: 1.5rem;
            }

            .modal-actions {
                flex-direction: column;
            }

            .btn-modal {
                width: 100%;
            }
        }
    </style>
</head>
<body>

    <nav>
        <img src="1.png" alt="KingTech Logo" class="logo">
        <ul>
            <li><a href="index_logado.php">Home</a></li>
            <li><a href="servicos.php">Serviços</a></li>
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

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Meus Pedidos</h1>
            <p class="page-subtitle">Acompanhe o status de todos os seus serviços</p>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'cancelado'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <div>Pedido cancelado com sucesso!</div>
            </div>
        <?php endif; ?>

        <div class="user-info-box">
            <i class="fas fa-envelope"></i>
            <div class="user-info-text">
                Exibindo pedidos cadastrados com o email: <strong><?= htmlspecialchars($usuario_email) ?></strong>
            </div>
        </div>

        <div class="filters">
            <button class="filter-btn active" data-filter="todos">
                <i class="fas fa-list"></i> Todos (<?= count($pedidos) ?>)
            </button>
            <button class="filter-btn" data-filter="Computador">
                <i class="fas fa-laptop"></i> Computadores
            </button>
            <button class="filter-btn" data-filter="Celular">
                <i class="fas fa-mobile-alt"></i> Celulares
            </button>
            <button class="filter-btn" data-filter="Eletrodoméstico">
                <i class="fas fa-blender"></i> Eletrodomésticos
            </button>
        </div>

        <div class="pedidos-list">
            <?php if (empty($pedidos)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <h2 class="empty-title">Nenhum pedido encontrado</h2>
                    <p class="empty-text">Você ainda não realizou nenhum serviço conosco com este email.</p>
                    <a href="servicos.php" class="btn-primary">
                        <i class="fas fa-plus"></i> Solicitar Serviço
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($pedidos as $pedido): ?>
                    <div class="pedido-card" data-tipo="<?= $pedido['tipo'] ?>">
                        <div class="pedido-icon">
                            <?php
                            $icone = 'fa-tools';
                            if ($pedido['tipo'] == 'Celular') $icone = 'fa-mobile-alt';
                            if ($pedido['tipo'] == 'Computador') $icone = 'fa-laptop';
                            if ($pedido['tipo'] == 'Eletrodoméstico') $icone = 'fa-blender';
                            ?>
                            <i class="fas <?= $icone ?>"></i>
                        </div>

                        <div class="pedido-info">
                            <div class="pedido-header">
                                <span class="pedido-id">
                                    Orçamento #<?= $pedido['id_cliente'] ?> - <?= htmlspecialchars($pedido['tipo']) ?>
                                    <?php if ($pedido['tipo'] == 'Celular' && !empty($pedido['modelo_celular'])): ?>
                                        (<?= htmlspecialchars($pedido['modelo_celular']) ?>)
                                    <?php elseif ($pedido['tipo'] == 'Eletrodoméstico' && !empty($pedido['nome_eletrodomestico'])): ?>
                                        (<?= htmlspecialchars($pedido['nome_eletrodomestico']) ?>)
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="pedido-descricao"><?= htmlspecialchars($pedido['servico_solicitado']) ?></div>
                            <div class="pedido-details">
                                <div class="pedido-detail">
                                    <i class="fas fa-wrench"></i>
                                    <span><?= htmlspecialchars($pedido['tipo_servico']) ?></span>
                                </div>
                                <div class="pedido-detail">
                                    <i class="fas fa-phone"></i>
                                    <span><?= htmlspecialchars($pedido['whatsapp']) ?></span>
                                </div>
                                <?php if (!empty($pedido['data_cadastro'])): ?>
                                <div class="pedido-detail">
                                    <i class="fas fa-calendar"></i>
                                    <span><?= date('d/m/Y H:i', strtotime($pedido['data_cadastro'])) ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($pedido['problema_reportado'])): ?>
                                <div style="margin-top: 0.8rem; font-size: 0.9rem; color: var(--text-gray);">
                                    <strong>Problema:</strong> <?= htmlspecialchars($pedido['problema_reportado']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="pedido-actions">
                            <div class="pedido-valor <?= is_null($pedido['valor_servico']) ? 'valor-pendente' : '' ?>">
                                <?php if (is_null($pedido['valor_servico'])): ?>
                                    <i class="fas fa-clock"></i> Aguardando orçamento
                                <?php else: ?>
                                    R$ <?= number_format($pedido['valor_servico'], 2, ',', '.') ?>
                                <?php endif; ?>
                            </div>
                            <span class="status-badge status-<?= strtolower(str_replace(['ç', 'ã', ' ', 'ó'], ['c', 'a', '', 'o'], $pedido['tipo_servico'])) ?>">
                                <?= htmlspecialchars($pedido['tipo_servico']) ?>
                            </span>
                            <button class="btn-cancelar" onclick="abrirModalCancelar(<?= $pedido['id_cliente'] ?>, '<?= $pedido['tipo'] ?>')">
                                <i class="fas fa-times-circle"></i> Cancelar Pedido
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal de Confirmação de Cancelamento -->
    <div id="modalCancelar" class="modal">
        <div class="modal-content">
            <div class="modal-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2 class="modal-title">Confirmar Cancelamento</h2>
            <p class="modal-text">
                Tem certeza que deseja cancelar este pedido? Esta ação não pode ser desfeita.
            </p>
            <form id="formCancelar" method="POST">
                <input type="hidden" name="cancelar_pedido" value="1">
                <input type="hidden" name="pedido_id" id="pedido_id">
                <input type="hidden" name="pedido_tipo" id="pedido_tipo">
                <div class="modal-actions">
                    <button type="button" class="btn-modal btn-voltar" onclick="fecharModal()">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </button>
                    <button type="submit" class="btn-modal btn-confirmar">
                        <i class="fas fa-check"></i> Sim, Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Filtros de pedidos
        const filterBtns = document.querySelectorAll('.filter-btn');
        const pedidoCards = document.querySelectorAll('.pedido-card');

        filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                filterBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                const filter = this.getAttribute('data-filter');

                pedidoCards.forEach(card => {
                    if (filter === 'todos') {
                        card.style.display = 'grid';
                    } else {
                        const tipo = card.getAttribute('data-tipo');
                        card.style.display = tipo === filter ? 'grid' : 'none';
                    }
                });
            });
        });

        // Modal de cancelamento
        function abrirModalCancelar(pedidoId, pedidoTipo) {
            document.getElementById('pedido_id').value = pedidoId;
            document.getElementById('pedido_tipo').value = pedidoTipo;
            document.getElementById('modalCancelar').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function fecharModal() {
            document.getElementById('modalCancelar').classList.remove('show');
            document.body.style.overflow = 'auto';
        }

        // Fechar modal ao clicar fora
        document.getElementById('modalCancelar').addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModal();
            }
        });

        // Fechar modal com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                fecharModal();
            }
        });

        // Auto-fechar alerta de sucesso
        const alert = document.querySelector('.alert');
        if (alert) {
            setTimeout(() => {
                alert.style.animation = 'fadeOut 0.5s ease-out';
                setTimeout(() => {
                    alert.remove();
                }, 500);
            }, 5000);
        }

        // Adicionar animação de fadeOut
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeOut {
                from { opacity: 1; transform: translateX(0); }
                to { opacity: 0; transform: translateX(30px); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>