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

// ====================
// Conexão com o banco
// ====================
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
    tipo_servico ENUM('Limpeza', 'Troca de Peças', 'Diagnóstico'),
    servico_solicitado TEXT,
    problema_reportado TEXT,
    email VARCHAR(100)
);

CREATE TABLE IF NOT EXISTS Celulares (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nome_cliente VARCHAR(100) NOT NULL,
    whatsapp VARCHAR(15) NOT NULL,
    modelo_celular VARCHAR(100),
    tipo_servico ENUM('Limpeza', 'Troca de Peças', 'Diagnóstico'),
    servico_solicitado TEXT,
    problema_reportado TEXT,
    email VARCHAR(100)
);

CREATE TABLE IF NOT EXISTS Eletrodomesticos (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nome_cliente VARCHAR(100) NOT NULL,
    whatsapp VARCHAR(15) NOT NULL,
    nome_eletrodomestico VARCHAR(100),
    marca VARCHAR(100),
    tipo_servico ENUM('Limpeza', 'Troca de Peças', 'Diagnóstico'),
    servico_solicitado TEXT,
    problema_reportado TEXT,
    email VARCHAR(100)
);";
$conn->multi_query($sql);
while ($conn->more_results() && $conn->next_result()) { }

// Adicionar coluna email se não existir
$tables = ['Computadores', 'Celulares', 'Eletrodomesticos'];
foreach ($tables as $table) {
    $check = $conn->query("SHOW COLUMNS FROM $table LIKE 'email'");
    if ($check->num_rows == 0) {
        $conn->query("ALTER TABLE $table ADD COLUMN email VARCHAR(100)");
    }
}

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

$mensagem_sucesso = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar'])) {
    $nome = $_POST['nome_cliente'];
    $whatsapp = $_POST['whatsapp'];
    $problema = $_POST['problema_reportado'];
    $servico_solicitado = $_POST['servico_solicitado'];
    $tipo_servico = $_POST['tipo_servico'];

    if ($tipo === 'computadores') {
      if ($acao === 'editar') {
          $stmt = $conn->prepare("UPDATE Computadores SET nome_cliente=?, whatsapp=?, tipo_servico=?, servico_solicitado=?, problema_reportado=?, email=? WHERE id_cliente=?");
          $stmt->bind_param("ssssssi", $nome, $whatsapp, $tipo_servico, $servico_solicitado, $problema, $usuario_email, $id);
          $mensagem_sucesso = 'Orçamento atualizado com sucesso!';
      } else {
          $stmt = $conn->prepare("INSERT INTO Computadores (nome_cliente, whatsapp, tipo_servico, servico_solicitado, problema_reportado, email) VALUES (?, ?, ?, ?, ?, ?)");
          $stmt->bind_param("ssssss", $nome, $whatsapp, $tipo_servico, $servico_solicitado, $problema, $usuario_email);
          $mensagem_sucesso = 'Orçamento cadastrado com sucesso!';
      }
  }
  elseif ($tipo === 'celulares') {
    $modelo = $_POST['modelo_celular'];
    if ($acao === 'editar') {
        $stmt = $conn->prepare("UPDATE Celulares SET nome_cliente=?, whatsapp=?, modelo_celular=?, tipo_servico=?, servico_solicitado=?, problema_reportado=?, email=? WHERE id_cliente=?");
        $stmt->bind_param("sssssssi", $nome, $whatsapp, $modelo, $tipo_servico, $servico_solicitado, $problema, $usuario_email, $id);
        $mensagem_sucesso = 'Orçamento atualizado com sucesso!';
    } else {
        $stmt = $conn->prepare("INSERT INTO Celulares (nome_cliente, whatsapp, modelo_celular, tipo_servico, servico_solicitado, problema_reportado, email) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $nome, $whatsapp, $modelo, $tipo_servico, $servico_solicitado, $problema, $usuario_email);
        $mensagem_sucesso = 'Orçamento cadastrado com sucesso!';
    }
}
elseif ($tipo === 'eletrodomesticos') {
  $nome_el = $_POST['nome_eletrodomestico'];
  $marca = $_POST['marca'];
  if ($acao === 'editar') {
      $stmt = $conn->prepare("UPDATE Eletrodomesticos SET nome_cliente=?, whatsapp=?, nome_eletrodomestico=?, marca=?, tipo_servico=?, servico_solicitado=?, problema_reportado=?, email=? WHERE id_cliente=?");
      $stmt->bind_param("ssssssssi", $nome, $whatsapp, $nome_el, $marca, $tipo_servico, $servico_solicitado, $problema, $usuario_email, $id);
      $mensagem_sucesso = 'Orçamento atualizado com sucesso!';
  } else {
      $stmt = $conn->prepare("INSERT INTO Eletrodomesticos (nome_cliente, whatsapp, nome_eletrodomestico, marca, tipo_servico, servico_solicitado, problema_reportado, email) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("ssssssss", $nome, $whatsapp, $nome_el, $marca, $tipo_servico, $servico_solicitado, $problema, $usuario_email);
      $mensagem_sucesso = 'Orçamento cadastrado com sucesso!';
  }
}

    $stmt->execute();
    $_SESSION['mensagem_sucesso'] = $mensagem_sucesso;
    echo "<script>location.href='?tipo=$tipo&sucesso=1';</script>";
    exit;
}

// Verificar se há mensagem de sucesso
$mostrar_notificacao = false;
if (isset($_GET['sucesso']) && isset($_SESSION['mensagem_sucesso'])) {
    $mensagem_sucesso = $_SESSION['mensagem_sucesso'];
    $mostrar_notificacao = true;
    unset($_SESSION['mensagem_sucesso']);
}

$tabela = getTableName($tipo);
$result = $conn->query("SELECT * FROM $tabela");
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
  <title>KingTech - Gerenciar Orçamentos</title>

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(to left, #0d1b2a, #1b365d);
      color: #333;
      line-height: 1.6;
      min-height: 100vh;
    }

/* ====================
 Estilos Globais
 ==================== */

 body {
  font-family: 'Montserrat', sans-serif;
}
      /* Scrollbar Personalizada */
      body::-webkit-scrollbar {
            width: 15px;
        }

        body::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        body::-webkit-scrollbar-thumb {
            background: linear-gradient(45deg, #cea348, #e0b46d);
            border-radius: 4px;
        }

        body::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(45deg, #e0b46d, #cea348);
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
  float: right;
  margin-right: 20px;
  display: flex;
  align-items: center;
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

.navimg {
  margin-bottom: -25px;
  width: 60px;
  height: 60px;
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
  transition: all 0.3s ease;
  border: 2px solid transparent;
}

.user-profile:hover {
  background: rgba(206, 163, 72, 0.2);
  border-color: #cea348;
}

.user-avatar {
  width: 40px;
  height: 40px;
  background: #cea348;
  color: #0d1b2a;
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
  color: #cea348;
  font-size: 0.8rem;
  transition: all 0.3s ease;
}

.user-menu:hover .dropdown-arrow {
  transform: rotate(180deg);
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  background: #ffffff;
  min-width: 200px;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  border-radius: 12px;
  opacity: 0;
  visibility: hidden;
  transform: translateY(-10px);
  transition: all 0.3s ease;
  z-index: 1000;
  border: 1px solid rgba(206, 163, 72, 0.2);
  margin-top: 10px;
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
  color: #1a1a1a;
  text-decoration: none;
  font-weight: 500;
  transition: all 0.3s ease;
  border-bottom: 1px solid rgba(206, 163, 72, 0.1);
}

.dropdown-item:last-child {
  border-bottom: none;
}

.dropdown-item:hover {
  background: rgba(206, 163, 72, 0.1);
  color: #cea348;
}

.dropdown-item i {
  color: #cea348;
  width: 20px;
}

/* ====================
 Responsividade da Navbar
 ==================== */

@media (max-width: 768px) {
  nav {
      flex-direction: column;
      align-items: center;
      padding: 10px;
  }

  .logo {
      width: 60px;
      height: 60px;
      margin-bottom: 10px;
  }

  nav ul {
      flex-direction: column;
      text-align: center;
      width: 100%;
  }

  nav ul li {
      display: block;
      line-height: normal;
      margin: 5px 0;
  }

  nav ul li a {
      display: block;
      padding: 10px;
  }

  .user-menu {
      width: 100%;
  }

  .user-profile {
      justify-content: center;
  }

  .dropdown-menu {
      right: auto;
      left: 50%;
      transform: translateX(-50%) translateY(-10px);
  }

  .user-menu:hover .dropdown-menu {
      transform: translateX(-50%) translateY(0);
  }
}

    /* Container principal */
    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 30px 20px;
    }

    /* Títulos */
    h1, h2 {
      color: #cea348;
      text-align: center;
      margin-bottom: 30px;
      text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }

    h1 {
      font-size: 2.5em;
      margin-bottom: 20px;
    }

    h2 {
      font-size: 2em;
      margin-top: 40px;
    }

    /* Cards de navegação */
    .nav-cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 40px;
    }

    .nav-card {
      background: linear-gradient(145deg, #fff, #f8f9fa);
      padding: 25px;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      text-align: center;
      transition: all 0.3s ease;
      border: 1px solid rgba(206, 163, 72, 0.2);
      cursor: pointer;
      text-decoration: none;
      color: inherit;
    }

    .nav-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 40px rgba(206, 163, 72, 0.3);
      background: linear-gradient(145deg, #cea348, #e8c368);
      color: #fff;
    }

    .nav-card.ativo {
      background: linear-gradient(145deg, #cea348, #e8c368);
      color: #fff;
      transform: translateY(-5px);
    }

    .nav-card i {
      font-size: 2.5em;
      margin-bottom: 15px;
      color: #cea348;
    }

    .nav-card:hover i,
    .nav-card.ativo i {
      color: #fff;
    }

    .nav-card h3 {
      font-size: 1.3em;
      margin-bottom: 10px;
    }

    /* Formulário */
    .form-container {
      background: linear-gradient(145deg, #fff, #f8f9fa);
      padding: 40px;
      border-radius: 20px;
      box-shadow: 0 15px 35px rgba(0,0,0,0.1);
      margin-bottom: 40px;
      border: 1px solid rgba(206, 163, 72, 0.2);
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr;
      gap: 20px;
      margin-bottom: 25px;
    }

    .form-row.two-columns {
      grid-template-columns: 1fr 1fr;
    }

    label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #555;
      font-size: 0.95em;
    }

    input[type="text"],
    input[type="number"],
    input,
    textarea,
    select {
      width: 100%;
      padding: 15px;
      border: 2px solid #e0e0e0;
      border-radius: 10px;
      font-size: 16px;
      transition: all 0.3s ease;
      background: #fff;
      box-sizing: border-box;
    }

    input:focus,
    textarea:focus,
    select:focus {
      outline: none;
      border-color: #cea348;
      box-shadow: 0 0 0 3px rgba(206, 163, 72, 0.1);
      transform: translateY(-2px);
    }

    textarea {
      resize: vertical;
      min-height: 120px;
    }

    select {
      appearance: none;
      background-image: url('data:image/svg+xml;charset=UTF-8,<svg fill="%23cea348" viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg>');
      background-repeat: no-repeat;
      background-position: right 15px top 50%;
      background-size: 20px;
      cursor: pointer;
    }

    /* Campos específicos */
    .campos-especificos {
      background: rgba(206, 163, 72, 0.05);
      padding: 25px;
      border-radius: 15px;
      border: 2px dashed rgba(206, 163, 72, 0.3);
      margin-top: 20px;
    }

    .campos-especificos h4 {
      color: #cea348;
      margin-bottom: 20px;
      font-size: 1.2em;
      text-align: center;
    }

    /* Botões */
    .button-group {
      display: flex;
      justify-content: flex-end;
      gap: 15px;
      margin-top: 30px;
    }

    button {
      background: linear-gradient(45deg, #cea348, #e8c368);
      color: #1b365d;
      border: none;
      padding: 15px 30px;
      border-radius: 25px;
      cursor: pointer;
      font-size: 16px;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 5px 15px rgba(206, 163, 72, 0.3);
    }

    button:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(206, 163, 72, 0.4);
    }

    /* Notificação de Sucesso */
    .toast-notification {
      position: fixed;
      top: 20px;
      right: 20px;
      background: linear-gradient(135deg, #10b981, #059669);
      color: white;
      padding: 20px 25px;
      border-radius: 15px;
      box-shadow: 0 10px 40px rgba(16, 185, 129, 0.4);
      display: flex;
      align-items: center;
      gap: 15px;
      z-index: 9999;
      animation: slideInRight 0.5s ease-out forwards;
      min-width: 320px;
      border: 2px solid rgba(255, 255, 255, 0.2);
    }
    .toast-notification.hiding {
      animation: slideOutRight 0.5s ease-in forwards;
    }

    @keyframes slideInRight {
      from {
        transform: translateX(400px);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }

    @keyframes slideOutRight {
      from {
        transform: translateX(0);
        opacity: 1;
      }
      to {
        transform: translateX(400px);
        opacity: 0;
      }
    }

    .toast-notification i {
      font-size: 28px;
      background: rgba(255, 255, 255, 0.2);
      width: 50px;
      height: 50px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      flex-shrink: 0;
    }

    .toast-content {
      flex: 1;
    }

    .toast-content h4 {
      margin: 0;
      font-size: 1.1em;
      font-weight: 700;
      margin-bottom: 5px;
    }

    .toast-content p {
      margin: 0;
      font-size: 0.9em;
      opacity: 0.95;
    }

    /* Tabela */
    .table-container {
      background: #fff;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      margin-top: 30px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th {
      background: linear-gradient(45deg, #cea348, #e8c368);
      color: #1b365d;
      padding: 20px 15px;
      font-weight: 600;
      text-align: left;
      font-size: 0.95em;
    }

    td {
      padding: 15px;
      border-bottom: 1px solid #eee;
      vertical-align: top;
    }

    tr:nth-child(even) {
      background-color: #f8f9fa;
    }

    tr:hover {
      background-color: rgba(206, 163, 72, 0.1);
      transform: scale(1.01);
      transition: all 0.2s ease;
    }

    .action-buttons {
      display: flex;
      gap: 8px;
    }

    .btn-sm {
      padding: 8px 12px;
      font-size: 0.85em;
      border-radius: 15px;
      text-decoration: none;
      color: #fff;
    }

    .btn-edit {
      background: linear-gradient(45deg, #17a2b8, #20c997);
    }

    .btn-delete {
      background: linear-gradient(45deg, #dc3545, #e74c3c);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .container {
        padding: 20px 15px;
      }

      h1 {
        font-size: 2em;
      }

      h2 {
        font-size: 1.5em;
      }

      .form-container {
        padding: 25px 20px;
      }

      .form-row.two-columns {
        grid-template-columns: 1fr;
      }

      .button-group {
        flex-direction: column;
      }

      .table-container {
        overflow-x: auto;
      }

      table {
        min-width: 600px;
      }

      .action-buttons {
        flex-direction: column;
      }
      
      /* Footer Moderno */
    footer {
      background: linear-gradient(135deg, var(--primary-dark, #0d1b2a) 0%, var(--secondary-dark, #1b365d) 100%);
      position: relative;
      overflow: hidden;
      margin-top: 60px;
    }

    footer::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #cea348, #e0b46d, #cea348);
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
      color: #cea348;
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
      transition: all 0.3s ease;
      position: relative;
    }

    .footer-links a:hover {
      color: #cea348;
    }

    .footer-company-name {
      color: #cea348;
      font-size: 14px;
      font-weight: 400;
    }

    .footer-center i {
      background: #cea348;
      color: #0d1b2a;
      font-size: 20px;
      width: 45px;
      height: 45px;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-right: 1rem;
      transition: all 0.3s ease;
    }

    .footer-center i:hover {
      transform: scale(1.1);
      box-shadow: 0 4px 15px rgba(206, 163, 72, 0.4);
    }

    .footer-center p {
      color: #cea348;
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
      color: #cea348;
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: 1rem;
    }

    .email {
      color: white;
      text-decoration: none;
    }

    .email:hover {
      color: #cea348;
    }

    .toast-notification {
      min-width: 280px;
    }

    }

    @media (max-width: 480px) {
      .nav-cards {
        grid-template-columns: 1fr;
      }

      .form-container {
        padding: 20px 15px;
      }

      input,
      textarea,
      select {
        padding: 12px;
        font-size: 14px;
      }

      button {
        padding: 12px 20px;
        font-size: 14px;
      }

      .toast-notification {
        left: 10px;
        right: 10px;
        min-width: auto;
      }
    }

    
  </style>

  <script>
    function atualizarCampos() {
      const tipo = document.getElementById('tipo').value;
      document.getElementById('campos-computadores').style.display = tipo === 'computadores' ? 'block' : 'none';
      document.getElementById('campos-celulares').style.display = tipo === 'celulares' ? 'block' : 'none';
      document.getElementById('campos-eletrodomesticos').style.display = tipo === 'eletrodomesticos' ? 'block' : 'none';
      
      // Atualizar cards de navegação
      document.querySelectorAll('.nav-card').forEach(card => {
        card.classList.remove('ativo');
      });
      if (document.getElementById('card-' + tipo)) {
        document.getElementById('card-' + tipo).classList.add('ativo');
      }
    }

    function limitarDigitos(input, max) {
      let digits = input.value.replace(/\D/g, '');
      if (digits.length > max) {
        digits = digits.substring(0, max);
      }
      input.value = digits;
    }

    function formatarWhatsApp(input) {
      let value = input.value.replace(/\D/g, '');
      if (value.length === 11) {
        value = '(' + value.substring(0, 2) + ') ' + value.substring(2, 7) + '-' + value.substring(7);
      } else if (value.length === 10) {
        value = '(' + value.substring(0, 2) + ') ' + value.substring(2, 6) + '-' + value.substring(6);
      }
      input.value = value;
    }

    function removerFormatacao(input) {
      input.value = input.value.replace(/\D/g, '');
    }

    window.addEventListener('DOMContentLoaded', atualizarCampos);
  </script>
</head>
<body>
<!-- Notificação de Sucesso -->
<?php if ($mostrar_notificacao): ?>
<div class="toast-notification" id="toastNotification">
  <i class="fas fa-check-circle"></i>
  <div class="toast-content">
    <h4>Sucesso!</h4>
    <p><?= htmlspecialchars($mensagem_sucesso) ?></p>
  </div>
</div>
<?php endif; ?>

<!-- Navbar com menu de usuário -->
<nav>
    <img src="1.png" alt="KingTech Logo" class="logo">
    <ul>
        <li><a class="anav" href="index_logado.php">Home</a></li>
        <li><a class="active" href="#">Serviços</a></li>
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
  <h1><i class="fas fa-calculator"></i> Sistema de Orçamentos</h1>
  
  <!-- Cards de Navegação -->
  <div class="nav-cards">
    <a href="?tipo=computadores" class="nav-card <?= $tipo === 'computadores' ? 'ativo' : '' ?>" id="card-computadores">
      <i class="fas fa-desktop"></i>
      <h3>Computadores</h3>
      <p>Gerenciar orçamentos para PCs</p>
    </a>
    <a href="?tipo=celulares" class="nav-card <?= $tipo === 'celulares' ? 'ativo' : '' ?>" id="card-celulares">
      <i class="fas fa-mobile-alt"></i>
      <h3>Celulares</h3>
      <p>Gerenciar orçamentos para celulares</p>
    </a>
    <a href="?tipo=eletrodomesticos" class="nav-card <?= $tipo === 'eletrodomesticos' ? 'ativo' : '' ?>" id="card-eletrodomesticos">
      <i class="fas fa-blender"></i>
      <h3>Eletrodomésticos</h3>
      <p>Gerenciar orçamentos para eletrodomésticos</p>
    </a>
  </div>

  <!-- Formulário -->
  <div class="form-container">
    <h2><?= $acao === 'editar' ? '<i class="fas fa-edit"></i> Editar' : '<i class="fas fa-plus-circle"></i> Novo' ?> Orçamento</h2>
    <form method="POST">
      <div class="form-row">
        <div>
          <label>Tipo de Dispositivo</label>
          <select name="tipo" id="tipo" onchange="atualizarCampos()">
            <option value="computadores" <?= $tipo === 'computadores' ? 'selected' : '' ?>>Computador</option>
            <option value="celulares" <?= $tipo === 'celulares' ? 'selected' : '' ?>>Celular</option>
            <option value="eletrodomesticos" <?= $tipo === 'eletrodomesticos' ? 'selected' : '' ?>>Eletrodoméstico</option>
          </select>
        </div>
      </div>

      <div class="form-row two-columns">
        <div>
          <label>Nome do Cliente</label>
          <input 
           name="nome_cliente" 
           value="<?= $dados_edicao['nome_cliente'] ?? '' ?>"
           maxlength="100"
           oninput="validarNome(this)"
           pattern="[A-Za-zÀ-ÿ\s]+"
           title="Digite apenas letras e espaços"
           required>
        </div>
        <div>
          <label>Whatsapp</label>
          <input
            name="whatsapp"
            id="whatsapp"
            value="<?= $dados_edicao['whatsapp'] ?? '' ?>"
            maxlength="15"
            minlength="15"
            oninput="limitarDigitos(this, 11)"
            onblur="formatarWhatsApp(this)"
            onfocus="removerFormatacao(this)"
            autocomplete="off"
            placeholder="(11) 98765-4321"
            required
          >
        </div>
      </div>

      <div class="form-row">
        <div>
          <label>Tipo de Serviço</label>
          <select name="tipo_servico" required>
            <option value="Limpeza" <?= (!empty($dados_edicao['tipo_servico']) && $dados_edicao['tipo_servico'] === 'Limpeza') ? 'selected' : '' ?>>Limpeza</option>
            <option value="Troca de Peças" <?= (!empty($dados_edicao['tipo_servico']) && $dados_edicao['tipo_servico'] === 'Troca de Peças') ? 'selected' : '' ?>>Troca de Peças</option>
            <option value="Diagnóstico" <?= (!empty($dados_edicao['tipo_servico']) && $dados_edicao['tipo_servico'] === 'Diagnóstico') ? 'selected' : '' ?>>Diagnóstico</option>
          </select>
        </div>
      </div>

      <div class="form-row two-columns">
        <div>
          <label>Problema Reportado</label>
          <textarea name="problema_reportado" required><?= $dados_edicao['problema_reportado'] ?? '' ?></textarea>
        </div>
        <div>
          <label>Serviço Solicitado</label>
          <textarea name="servico_solicitado" required><?= $dados_edicao['servico_solicitado'] ?? '' ?></textarea>
        </div>
      </div>

      <div id="campos-celulares" class="campos-especificos" style="display: <?= $tipo === 'celulares' ? 'block' : 'none' ?>;">
        <h4><i class="fas fa-mobile-alt"></i> Informações do Celular</h4>
        <div class="form-row">
          <div>
            <label>Modelo do Celular</label>
            <input name="modelo_celular" value="<?= $dados_edicao['modelo_celular'] ?? '' ?>" required>
          </div>
        </div>
      </div>

      <div id="campos-eletrodomesticos" class="campos-especificos" style="display: <?= $tipo === 'eletrodomesticos' ? 'block' : 'none' ?>;">
        <h4><i class="fas fa-blender"></i> Informações do Eletrodoméstico</h4>
        <div class="form-row two-columns">
          <div>
            <label>Nome do Eletrodoméstico</label>
            <input name="nome_eletrodomestico" value="<?= $dados_edicao['nome_eletrodomestico'] ?? '' ?>" required>
          </div>
          <div>
            <label>Marca</label>
            <input name="marca" value="<?= $dados_edicao['marca'] ?? '' ?>" required>
          </div>
        </div>
      </div>
      
      <div id="campos-computadores" class="campos-especificos" style="display: none;"></div>
      
      <div class="button-group">
        <button type="submit" name="salvar"><?= $acao === 'editar' ? 'Salvar' : 'Cadastrar' ?></button>
      </div>
    </form>
  </div>
  
  <script>
    function atualizarCampos() {
      const tipo = document.getElementById('tipo').value;
      
      // Campos de computadores
      const camposComputadores = document.getElementById('campos-computadores');
      if (camposComputadores) {
        camposComputadores.style.display = tipo === 'computadores' ? 'block' : 'none';
      }
      
      // Campos de celulares
      const camposCelulares = document.getElementById('campos-celulares');
      const modeloCelular = document.querySelector('input[name="modelo_celular"]');
      if (tipo === 'celulares') {
        camposCelulares.style.display = 'block';
        if (modeloCelular) modeloCelular.required = true;
      } else {
        camposCelulares.style.display = 'none';
        if (modeloCelular) modeloCelular.required = false;
      }
      
      // Campos de eletrodomésticos
      const camposEletro = document.getElementById('campos-eletrodomesticos');
      const nomeEletro = document.querySelector('input[name="nome_eletrodomestico"]');
      const marca = document.querySelector('input[name="marca"]');
      if (tipo === 'eletrodomesticos') {
        camposEletro.style.display = 'block';
        if (nomeEletro) nomeEletro.required = true;
        if (marca) marca.required = true;
      } else {
        camposEletro.style.display = 'none';
        if (nomeEletro) nomeEletro.required = false;
        if (marca) marca.required = false;
      }
      
      // Atualizar cards de navegação
      document.querySelectorAll('.nav-card').forEach(card => {
        card.classList.remove('ativo');
      });
      if (document.getElementById('card-' + tipo)) {
        document.getElementById('card-' + tipo).classList.add('ativo');
      }
    }

    // Auto-fechar após 4 segundos
    window.addEventListener('DOMContentLoaded', () => {
  atualizarCampos();
  
  const toast = document.getElementById('toastNotification');
  if (toast) {
    setTimeout(() => {
      toast.classList.add('hiding');
      setTimeout(() => {
        toast.remove();
      }, 500);
    }, 3500);
  }
});

  </script>
</div>
</body>
</html>