<?php
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'OrcamentosManutencao';

$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) die("Conexão falhou: " . $conn->connect_error);
$conn->query("CREATE DATABASE IF NOT EXISTS $db");
$conn->select_db($db);

$sql = "
CREATE TABLE IF NOT EXISTS Computadores (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nome_cliente VARCHAR(100) NOT NULL,
    whatsapp VARCHAR(15) NOT NULL,
    tipo_servico ENUM('Limpeza', 'Troca de peças', 'Diagnóstico'),
    servico_solicitado TEXT,
    problema_reportado TEXT,
    valor_servico DECIMAL(10,2) DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS Celulares (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nome_cliente VARCHAR(100) NOT NULL,
    whatsapp VARCHAR(15) NOT NULL,
    modelo_celular VARCHAR(100),
    tipo_servico ENUM('Limpeza', 'Troca de peças', 'Diagnóstico'),
    servico_solicitado TEXT,
    problema_reportado TEXT,
    valor_servico DECIMAL(10,2) DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS Eletrodomesticos (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nome_cliente VARCHAR(100) NOT NULL,
    whatsapp VARCHAR(15) NOT NULL,
    nome_eletrodomestico VARCHAR(100),
    marca VARCHAR(100),
    tipo_servico ENUM('Limpeza', 'Troca de peças', 'Diagnóstico'),
    servico_solicitado TEXT,
    problema_reportado TEXT,
    valor_servico DECIMAL(10,2) DEFAULT NULL
);";

// Adicionar coluna valor_servico e status se não existir
$conn->query("ALTER TABLE Computadores ADD COLUMN IF NOT EXISTS valor_servico DECIMAL(10,2) DEFAULT NULL");
$conn->query("ALTER TABLE Celulares ADD COLUMN IF NOT EXISTS valor_servico DECIMAL(10,2) DEFAULT NULL");
$conn->query("ALTER TABLE Eletrodomesticos ADD COLUMN IF NOT EXISTS valor_servico DECIMAL(10,2) DEFAULT NULL");
$conn->query("ALTER TABLE Computadores ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'ativo'");
$conn->query("ALTER TABLE Celulares ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'ativo'");
$conn->query("ALTER TABLE Eletrodomesticos ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'ativo'");

$conn->multi_query($sql);
while ($conn->more_results() && $conn->next_result()) { }

$tipo = $_POST['tipo'] ?? $_GET['tipo'] ?? 'computadores';
$acao = $_GET['acao'] ?? '';
$id   = $_GET['id'] ?? null;

function getTableName($tipo) {
    return match($tipo) {
        'computadores' => 'Computadores',
        'celulares' => 'Celulares',
        'eletrodomesticos' => 'Eletrodomesticos',
        default => 'Computadores',
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar'])) {
    $nome = $_POST['nome_cliente'];
    $whatsapp = $_POST['whatsapp'];
    $problema = $_POST['problema_reportado'];
    $servico_solicitado = $_POST['servico_solicitado'];
    $tipo_servico = $_POST['tipo_servico'];
    $valor = $_POST['valor_servico'] !== '' ? $_POST['valor_servico'] : null;
    $valor_param = $valor !== null ? (float)$valor : null;

    if ($tipo === 'computadores') {
        if ($acao === 'editar') {
            $stmt = $conn->prepare("UPDATE Computadores SET nome_cliente=?, whatsapp=?, tipo_servico=?, servico_solicitado=?, problema_reportado=?, valor_servico=? WHERE id_cliente=?");
            $stmt->bind_param("sssssdi", $nome, $whatsapp, $tipo_servico, $servico_solicitado, $problema, $valor_param, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO Computadores (nome_cliente, whatsapp, tipo_servico, servico_solicitado, problema_reportado, valor_servico) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssd", $nome, $whatsapp, $tipo_servico, $servico_solicitado, $problema, $valor_param);
        }
    }
    elseif ($tipo === 'celulares') {
        $modelo = $_POST['modelo_celular'];
        if ($acao === 'editar') {
            $stmt = $conn->prepare("UPDATE Celulares SET nome_cliente=?, whatsapp=?, modelo_celular=?, tipo_servico=?, servico_solicitado=?, problema_reportado=?, valor_servico=? WHERE id_cliente=?");
            $stmt->bind_param("ssssssdi", $nome, $whatsapp, $modelo, $tipo_servico, $servico_solicitado, $problema, $valor_param, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO Celulares (nome_cliente, whatsapp, modelo_celular, tipo_servico, servico_solicitado, problema_reportado, valor_servico) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssd", $nome, $whatsapp, $modelo, $tipo_servico, $servico_solicitado, $problema, $valor_param);
        }
    }
    elseif ($tipo === 'eletrodomesticos') {
        $nome_el = $_POST['nome_eletrodomestico'];
        $marca = $_POST['marca'];
        if ($acao === 'editar') {
            $stmt = $conn->prepare("UPDATE Eletrodomesticos SET nome_cliente=?, whatsapp=?, nome_eletrodomestico=?, marca=?, tipo_servico=?, servico_solicitado=?, problema_reportado=?, valor_servico=? WHERE id_cliente=?");
            $stmt->bind_param("sssssssdi", $nome, $whatsapp, $nome_el, $marca, $tipo_servico, $servico_solicitado, $problema, $valor_param, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO Eletrodomesticos (nome_cliente, whatsapp, nome_eletrodomestico, marca, tipo_servico, servico_solicitado, problema_reportado, valor_servico) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssd", $nome, $whatsapp, $nome_el, $marca, $tipo_servico, $servico_solicitado, $problema, $valor_param);
        }
    }

    $stmt->execute();
    echo "<script>location.href='?tipo=$tipo';</script>";
    exit;
}

$tabela = getTableName($tipo);
// CORREÇÃO: Adicionar filtro WHERE status != 'cancelado'
$result = $conn->query("SELECT * FROM $tabela WHERE status != 'cancelado' OR status IS NULL");
$dados_edicao = null;
if ($acao === 'editar' && $id) {
    $res = $conn->query("SELECT * FROM $tabela WHERE id_cliente=$id");
    $dados_edicao = $res->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="shortcut icon" href="2.png" type="image/x-icon">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <title>KingTech - Painel Administrativo</title>
  
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
    }

    body {
        font-family: 'Montserrat', sans-serif;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        line-height: 1.6;
        color: var(--text-dark);
        min-height: 100vh;
    }

    body::-webkit-scrollbar {
        width: 12px;
    }

    body::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    body::-webkit-scrollbar-thumb {
        background: linear-gradient(45deg, var(--primary-gold), #e0b46d);
        border-radius: 6px;
    }

    nav {
        background: linear-gradient(135deg, var(--primary-dark) 0%, var(--secondary-dark) 100%);
        padding: 1rem 2rem;
        box-shadow: var(--shadow);
        position: relative;
        overflow: hidden;
    }

    nav::before {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-gold), #e0b46d, var(--primary-gold));
    }

    .nav-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        max-width: 1400px;
        margin: 0 auto;
    }

    .logo {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        box-shadow: 0 4px 15px rgba(206, 163, 72, 0.3);
        transition: var(--transition);
    }

    .logo:hover {
        transform: scale(1.05);
        box-shadow: 0 6px 20px rgba(206, 163, 72, 0.5);
    }

    .nav-title {
        color: var(--primary-gold);
        font-size: 2rem;
        font-weight: 800;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }

    .nav-links {
        display: flex;
        gap: 1rem;
        align-items: center;
    }

    .btn-sair {
        background: linear-gradient(45deg, #e74c3c, #c0392b);
        color: white;
        padding: 0.8rem 1.5rem;
        border-radius: var(--border-radius);
        text-decoration: none;
        font-weight: 600;
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
    }

    .btn-sair:hover {
        background: linear-gradient(45deg, #c0392b, #a93226);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(231, 76, 60, 0.5);
    }

    .main-container {
        max-width: 1400px;
        margin: 2rem auto;
        padding: 0 2rem;
    }

    .filters-section {
        background: var(--card-bg);
        padding: 2rem;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        margin-bottom: 2rem;
        border: 1px solid rgba(206, 163, 72, 0.1);
    }

    .filter-tabs {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }

    .tab-button {
        padding: 0.8rem 1.5rem;
        border: 2px solid var(--primary-gold);
        background: transparent;
        color: var(--primary-gold);
        border-radius: var(--border-radius);
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .tab-button:hover,
    .tab-button.active {
        background: var(--primary-gold);
        color: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(206, 163, 72, 0.3);
    }

    .table-container {
        background: var(--card-bg);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        overflow: hidden;
        margin-bottom: 2rem;
        border: 1px solid rgba(206, 163, 72, 0.1);
    }

    .table-header {
        background: linear-gradient(45deg, var(--primary-gold), #e0b46d);
        color: var(--primary-dark);
        padding: 1rem 2rem;
        font-size: 1.2rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        background: var(--card-bg);
    }

    th {
        background: linear-gradient(135deg, var(--primary-dark), var(--secondary-dark));
        color: var(--primary-gold);
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 3px solid var(--primary-gold);
    }

    td {
        padding: 1rem;
        border-bottom: 1px solid rgba(206, 163, 72, 0.1);
        vertical-align: top;
    }

    tr:hover {
        background: rgba(206, 163, 72, 0.05);
    }

    tr:nth-child(even) {
        background: rgba(248, 249, 250, 0.5);
    }

    tr:nth-child(even):hover {
        background: rgba(206, 163, 72, 0.08);
    }

    .edit-btn {
        background: linear-gradient(45deg, var(--primary-gold), #e0b46d);
        color: var(--primary-dark);
        padding: 0.5rem 1rem;
        border-radius: var(--border-radius);
        text-decoration: none;
        font-weight: 600;
        font-size: 0.85rem;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }

    .edit-btn:hover {
        background: linear-gradient(45deg, #e0b46d, var(--primary-gold));
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(206, 163, 72, 0.4);
    }

    .form-container {
        background: var(--card-bg);
        padding: 2.5rem;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        border: 1px solid rgba(206, 163, 72, 0.1);
        position: relative;
        overflow: hidden;
    }

    .form-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-gold), #e0b46d, var(--primary-gold));
    }

    .form-title {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--primary-dark);
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .form-title::before {
        content: '';
        width: 4px;
        height: 40px;
        background: linear-gradient(45deg, var(--primary-gold), #e0b46d);
        border-radius: 2px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    label {
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    input, select, textarea {
        padding: 1rem;
        border: 2px solid #e1e8ed;
        border-radius: var(--border-radius);
        font-size: 1rem;
        transition: var(--transition);
        background: #fafbfc;
        font-family: inherit;
    }

    input:focus, select:focus, textarea:focus {
        outline: none;
        border-color: var(--primary-gold);
        background: white;
        box-shadow: 0 0 0 3px rgba(206, 163, 72, 0.1);
        transform: translateY(-1px);
    }

    textarea {
        min-height: 100px;
        resize: vertical;
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 2px solid rgba(206, 163, 72, 0.1);
    }

    .btn-primary {
        background: linear-gradient(45deg, var(--primary-gold), #e0b46d);
        color: var(--primary-dark);
        padding: 1rem 2rem;
        border: none;
        border-radius: var(--border-radius);
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 4px 15px rgba(206, 163, 72, 0.3);
    }

    .btn-primary:hover {
        background: linear-gradient(45deg, #e0b46d, var(--primary-gold));
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(206, 163, 72, 0.5);
    }

    .btn-secondary {
        background: transparent;
        color: var(--text-gray);
        padding: 1rem 2rem;
        border: 2px solid #e1e8ed;
        border-radius: var(--border-radius);
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-secondary:hover {
        border-color: var(--primary-gold);
        color: var(--primary-gold);
        background: rgba(206, 163, 72, 0.05);
    }

    .campos-especificos {
        display: none;
        animation: fadeInUp 0.3s ease-out;
    }

    .campos-especificos.show {
        display: block;
    }

    @media (max-width: 768px) {
        .nav-container {
            flex-direction: column;
            gap: 1rem;
        }

        .nav-title {
            font-size: 1.5rem;
        }

        .main-container {
            padding: 0 1rem;
        }

        .form-grid {
            grid-template-columns: 1fr;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            min-width: 800px;
        }

        .form-actions {
            flex-direction: column;
        }

        .filter-tabs {
            justify-content: center;
        }

        .tab-button {
            flex: 1;
            justify-content: center;
        }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .no-data {
        text-align: center;
        padding: 3rem;
        color: var(--text-gray);
        font-style: italic;
    }

    .value-cell {
        font-weight: 600;
        color: var(--primary-gold);
    }

    .status-badge {
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .status-badge.limpeza {
        background: rgba(52, 152, 219, 0.1);
        color: #3498db;
    }

    .status-badge.troca {
        background: rgba(230, 126, 34, 0.1);
        color: #e67e22;
    }

    .status-badge.diagnostico {
        background: rgba(155, 89, 182, 0.1);
        color: #9b59b6;
    }
  </style>

  <script>
    function atualizarCampos() {
      const tipo = document.getElementById('tipo').value;
      
      document.querySelectorAll('.campos-especificos').forEach(campo => {
        campo.classList.remove('show');
      });
      
      const campoAtivo = document.getElementById(`campos-${tipo}`);
      if (campoAtivo) {
        setTimeout(() => {
          campoAtivo.classList.add('show');
        }, 100);
      }
    }

    function formatarWhatsApp(input) {
      let value = input.value.replace(/\D/g, '');
      if (value.length > 0) value = '(' + value.substring(0, 2) + ') ' + value.substring(2);
      if (value.length > 10) value = value.substring(0, 10) + '-' + value.substring(10, 14);
      input.value = value;
    }

    window.addEventListener('DOMContentLoaded', function() {
      atualizarCampos();
      
      const rows = document.querySelectorAll('tr');
      rows.forEach((row, index) => {
        row.style.animation = `fadeInUp 0.6s ease-out ${index * 0.1}s both`;
      });
    });
  </script>
</head>
<body>
  <nav>
    <div class="nav-container">
      <img src="1.png" alt="KingTech Logo" class="logo">
      <h1 class="nav-title">Painel Administrativo</h1>
      <div class="nav-links">
        <a href="login.php?logout=success" class="btn-sair">
          <i class="fas fa-sign-out-alt"></i>
          Sair
        </a>
      </div>
    </div>
  </nav>

  <div class="main-container">
    <div class="filters-section">
      <div class="filter-tabs">
        <a href="?tipo=computadores" class="tab-button <?= $tipo === 'computadores' ? 'active' : '' ?>">
          <i class="fas fa-desktop"></i>
          Computadores
        </a>
        <a href="?tipo=celulares" class="tab-button <?= $tipo === 'celulares' ? 'active' : '' ?>">
          <i class="fas fa-mobile-alt"></i>
          Celulares
        </a>
        <a href="?tipo=eletrodomesticos" class="tab-button <?= $tipo === 'eletrodomesticos' ? 'active' : '' ?>">
          <i class="fas fa-tv"></i>
          Eletrodomésticos
        </a>
      </div>
    </div>

    <div class="table-container">
      <div class="table-header">
        <i class="fas fa-list"></i>
        Orçamentos - <?= ucfirst($tipo) ?>
      </div>
      
      <?php if ($result->num_rows > 0): ?>
      <table>
        <thead>
          <tr>
            <th><i class="fas fa-hashtag"></i> ID</th>
            <th><i class="fas fa-user"></i> Cliente</th>
            <th><i class="fas fa-phone"></i> WhatsApp</th>
            <?php if ($tipo === 'celulares'): ?>
              <th><i class="fas fa-mobile-alt"></i> Modelo</th>
            <?php elseif ($tipo === 'eletrodomesticos'): ?>
              <th><i class="fas fa-tv"></i> Eletrodoméstico</th>
              <th><i class="fas fa-tag"></i> Marca</th>
            <?php endif; ?>
            <th><i class="fas fa-wrench"></i> Tipo de Serviço</th>
            <th><i class="fas fa-clipboard-list"></i> Solicitado</th>
            <th><i class="fas fa-exclamation-triangle"></i> Problema</th>
            <th><i class="fas fa-dollar-sign"></i> Valor</th>
            <th><i class="fas fa-cogs"></i> Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><strong><?= $row['id_cliente'] ?></strong></td>
            <td><?= htmlspecialchars($row['nome_cliente']) ?></td>
            <td><?= htmlspecialchars($row['whatsapp']) ?></td>
            <?php if ($tipo === 'celulares'): ?>
              <td><?= htmlspecialchars($row['modelo_celular']) ?></td>
            <?php elseif ($tipo === 'eletrodomesticos'): ?>
              <td><?= htmlspecialchars($row['nome_eletrodomestico']) ?></td>
              <td><?= htmlspecialchars($row['marca']) ?></td>
            <?php endif; ?>
            <td>
              <span class="status-badge <?= strtolower(str_replace(['ç', 'ã'], ['c', 'a'], $row['tipo_servico'])) ?>">
                <?= htmlspecialchars($row['tipo_servico']) ?>
              </span>
            </td>
            <td><?= htmlspecialchars(substr($row['servico_solicitado'], 0, 50)) . (strlen($row['servico_solicitado']) > 50 ? '...' : '') ?></td>
            <td><?= htmlspecialchars(substr($row['problema_reportado'], 0, 50)) . (strlen($row['problema_reportado']) > 50 ? '...' : '') ?></td>
            <td class="value-cell">
              <?= is_null($row['valor_servico']) ? '—' : 'R$ ' . number_format($row['valor_servico'], 2, ',', '.') ?>
            </td>
            <td>
              <a href="?tipo=<?= $tipo ?>&acao=editar&id=<?= $row['id_cliente'] ?>" class="edit-btn">
                <i class="fas fa-edit"></i> Editar
              </a>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
      <?php else: ?>
      <div class="no-data">Nenhum registro encontrado</div>
      <?php endif; ?>
    </div>

    <div class="form-container">
      <h2 class="form-title"><?= $acao === 'editar' ? 'Editar' : 'Novo' ?> Orçamento</h2>
      <form method="POST">
        <div class="form-grid">
          <div class="form-group">
            <label>Tipo de Dispositivo</label>
            <select name="tipo" id="tipo" onchange="atualizarCampos()">
              <option value="computadores" <?= $tipo === 'computadores' ? 'selected' : '' ?>>Computador</option>
              <option value="celulares" <?= $tipo === 'celulares' ? 'selected' : '' ?>>Celular</option>
              <option value="eletrodomesticos" <?= $tipo === 'eletrodomesticos' ? 'selected' : '' ?>>Eletrodoméstico</option>
            </select>
          </div>

          <div class="form-group">
            <label>Nome do Cliente</label>
            <input name="nome_cliente" value="<?= $dados_edicao['nome_cliente'] ?? '' ?>" required>
          </div>

          <div class="form-group">
            <label>WhatsApp</label>
            <input type="tel" name="whatsapp" oninput="formatarWhatsApp(this)"
                   placeholder="(99) 99999-9999" value="<?= $dados_edicao['whatsapp'] ?? '' ?>">
          </div>
        </div>

        <div id="campos-celulares" class="campos-especificos">
          <div class="form-grid">
            <div class="form-group">
              <label>Modelo do Celular</label>
              <input name="modelo_celular" value="<?= $dados_edicao['modelo_celular'] ?? '' ?>">
            </div>
          </div>
        </div>

        <div id="campos-eletrodomesticos" class="campos-especificos">
          <div class="form-grid">
            <div class="form-group">
              <label>Nome do Eletrodoméstico</label>
              <input name="nome_eletrodomestico" value="<?= $dados_edicao['nome_eletrodomestico'] ?? '' ?>">
            </div>
            <div class="form-group">
              <label>Marca</label>
              <input name="marca" value="<?= $dados_edicao['marca'] ?? '' ?>">
            </div>
          </div>
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label>Tipo de Serviço</label>
            <select name="tipo_servico" required>
              <option value="Limpeza" <?= ($dados_edicao['tipo_servico'] ?? '') === 'Limpeza' ? 'selected' : '' ?>>Limpeza</option>
              <option value="Troca de peças" <?= ($dados_edicao['tipo_servico'] ?? '') === 'Troca de peças' ? 'selected' : '' ?>>Troca de peças</option>
              <option value="Diagnóstico" <?= ($dados_edicao['tipo_servico'] ?? '') === 'Diagnóstico' ? 'selected' : '' ?>>Diagnóstico</option>
            </select>
          </div>

          <div class="form-group">
            <label>Valor do Serviço (opcional)</label>
            <input type="number" step="0.01" name="valor_servico" value="<?= $dados_edicao['valor_servico'] ?? '' ?>" placeholder="0.00">
          </div>
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label>Serviço Solicitado</label>
            <textarea name="servico_solicitado" required><?= $dados_edicao['servico_solicitado'] ?? '' ?></textarea>
          </div>

          <div class="form-group">
            <label>Problema Apresentado</label>
            <textarea name="problema_reportado" required><?= $dados_edicao['problema_reportado'] ?? '' ?></textarea>
          </div>
        </div>

        <div class="form-actions">
          <?php if ($acao === 'editar'): ?>
          <a href="?tipo=<?= $tipo ?>" class="btn-secondary">
            <i class="fas fa-times"></i>
            Cancelar
          </a>
          <?php endif; ?>
          <button type="submit" name="salvar" class="btn-primary">
            <i class="fas fa-save"></i>
            <?= $acao === 'editar' ? 'Atualizar' : 'Salvar' ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>