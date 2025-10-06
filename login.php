<?php
session_start();

// ================= CONFIGURAÇÃO DO BANCO =================
$host = "localhost";
$user = "root";   // seu usuário do MySQL
$pass = "";       // sua senha do MySQL
$db   = "sistema_login";

// Criar conexão
$conn = new mysqli($host, $user, $pass);

// Verificar conexão
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

// Criar banco de dados se não existir
$conn->query("CREATE DATABASE IF NOT EXISTS $db");
$conn->select_db($db);

// Criar tabela de usuários com campo tipo_usuario
$sql = "CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo_usuario ENUM('cliente', 'administrador') DEFAULT 'cliente',
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql);

// Adicionar coluna tipo_usuario se ela não existir (para tabelas existentes)
$check_column = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'tipo_usuario'");
if ($check_column->num_rows == 0) {
    $conn->query("ALTER TABLE usuarios ADD COLUMN tipo_usuario ENUM('cliente', 'administrador') DEFAULT 'cliente' AFTER senha");
}

// ================= FUNÇÕES PHP =================
$mensagem = "";

// Verificar se foi redirecionado do logout
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $mensagem = "✅ Logout realizado com sucesso! Até logo!";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["acao"]) && $_POST["acao"] === "cadastrar") {
        // Dados do formulário de cadastro
        $nome = trim($_POST["nome"]);
        $email = strtolower(trim($_POST["email"]));
        $senha = $_POST["senha"];
        $confirma = $_POST["confirma"];
        $tipo_usuario = $_POST["tipo_usuario"];

        if ($senha !== $confirma) {
            $mensagem = "❌ As senhas não coincidem.";
        } else {
            // Verifica se email já existe
            $check = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
            $check->bind_param("s", $email);
            $check->execute();
            $result = $check->get_result();

            if ($result->num_rows > 0) {
                $mensagem = "⚠️ Este email já está cadastrado.";
            } else {
                // Criptografa senha e cadastra
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, tipo_usuario) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $nome, $email, $hash, $tipo_usuario);
                if ($stmt->execute()) {
                    $mensagem = "✅ Conta criada com sucesso! Agora você pode fazer login.";
                } else {
                    $mensagem = "❌ Erro ao cadastrar.";
                }
            }
        }
    }

    if (isset($_POST["acao"]) && $_POST["acao"] === "login") {
        // Dados do formulário de login
        $email = strtolower(trim($_POST["email"]));
        $senha = $_POST["senha"];
        $tipo_usuario = $_POST["tipo_usuario"];

        $stmt = $conn->prepare("SELECT id, nome, senha, tipo_usuario FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $id = $row['id'];
            $nome = $row['nome'];
            $senhaHash = $row['senha'];
            $tipoUsuarioBD = $row['tipo_usuario'];

            if (password_verify($senha, $senhaHash)) {
                // Verificar se o tipo de usuário selecionado corresponde ao cadastrado
                if ($tipo_usuario === $tipoUsuarioBD) {
                    // Login bem-sucedido - criar sessão e redirecionar
                    $_SESSION["usuario_id"] = $id;
                    $_SESSION["usuario_nome"] = $nome;
                    $_SESSION["usuario_email"] = $email;
                    $_SESSION["tipo_usuario"] = $tipoUsuarioBD;
                    $_SESSION["login_time"] = time();
                    
                    // Redirecionar baseado no tipo de usuário
                    if ($tipoUsuarioBD === 'administrador') {
                        header("Location: admin.php");
                    } else {
                        header("Location: index_logado.php");
                    }
                    exit();
                } else {
                    $mensagem = "❌ Tipo de usuário incorreto. Esta conta é cadastrada como " . ucfirst($tipoUsuarioBD) . ".";
                }
            } else {
                $mensagem = "❌ Senha incorreta.";
            }
        } else {
            $mensagem = "⚠️ Email não encontrado.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Acesse sua conta</title>
    <style>
        /* O MESMO CSS QUE VOCÊ JÁ TEM */
        * {margin:0;padding:0;box-sizing:border-box;}
        :root {--dourado:#cea348;--azul-escuro:#0d1b2a;--azul-secundario:#1b365d;}
        body {font-family:'Segoe UI',sans-serif;background:linear-gradient(135deg,var(--azul-escuro),var(--azul-secundario));min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
        .container {background:rgba(255,255,255,0.05);backdrop-filter:blur(10px);border-radius:20px;padding:40px;width:100%;max-width:420px;box-shadow:0 20px 40px rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.1);}
        h2 {color:var(--dourado);text-align:center;margin-bottom:30px;}
        label {display:block;color:#fff;margin-bottom:5px;}
        
        /* Container para input com ícone */
        .input-container {
            position: relative;
            margin-bottom: 15px;
        }
        
        input, select {width:100%;padding:12px;border-radius:10px;border:1px solid #ccc;margin-bottom:0;background:#fff;}
        
        /* Estilos para inputs com ícone de visualizar */
        .input-with-icon {
            padding-right: 45px;
        }
        
        /* Ícone de visualizar senha */
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            font-size: 18px;
            user-select: none;
        }
        
        .toggle-password:hover {
            color: var(--dourado);
        }
        
        /* Estilos para o seletor de tipo de usuário */
        .user-type-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .user-type-option {
            flex: 1;
            position: relative;
        }
        
        .user-type-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .user-type-label {
            display: block;
            padding: 12px;
            text-align: center;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 10px;
            color: #fff;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.05);
        }
        
        .user-type-label:hover {
            border-color: var(--dourado);
            background: rgba(206,163,72,0.1);
        }
        
        .user-type-option input[type="radio"]:checked + .user-type-label {
            border-color: var(--dourado);
            background: rgba(206,163,72,0.2);
            color: var(--dourado);
            font-weight: bold;
        }
        
        .user-type-icon {
            display: block;
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        /* Verificador de força da senha */
        .password-strength {
            margin-top: 5px;
            margin-bottom: 10px;
        }
        
        .strength-bar {
            height: 4px;
            border-radius: 2px;
            background: #333;
            overflow: hidden;
            margin-bottom: 5px;
        }
        
        .strength-fill {
            height: 100%;
            transition: all 0.3s ease;
            width: 0%;
        }
        
        .strength-text {
            font-size: 12px;
            color: #fff;
            text-align: center;
        }
        
        /* Cores da força da senha */
        .weak { background: #ff4444; }
        .medium { background: #ffaa00; }
        .strong { background: #44ff44; }
        
        /* Indicador de senhas coincidentes */
        .password-match {
            font-size: 12px;
            margin-top: 5px;
            margin-bottom: 10px;
        }
        
        .match-success { color: #44ff44; }
        .match-error { color: #ff4444; }
        
        .btn-primary {width:100%;padding:12px;background:linear-gradient(45deg,var(--dourado),#e6c068);border:none;border-radius:12px;font-weight:bold;cursor:pointer;}
        .toggle-form{text-align:center;margin-top:20px;}
        .toggle-link{color:var(--dourado);text-decoration:none;}
        .mensagem {text-align:center;margin-bottom:15px;color:white;font-weight:bold;}
        
        /* Botão de voltar */
        .btn-back {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            padding: 10px 15px;
            border-radius: 10px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .btn-back:hover {
            background: rgba(255,255,255,0.2);
            color: var(--dourado);
            transform: translateY(-2px);
        }
        
        .btn-back::before {
            content: "←";
            font-size: 16px;
        }
    </style>
</head>
<body>
    <!-- Botão de voltar -->
    <a href="index.html" class="btn-back">Voltar</a>
    
    <div class="container">
        <?php if($mensagem): ?>
            <p class="mensagem"><?= $mensagem ?></p>
        <?php endif; ?>

        <!-- Formulário de Login -->
        <div id="loginForm" class="form-section">
            <h2>Entrar</h2>
            <form method="POST">
                <input type="hidden" name="acao" value="login">
                
                <label>Tipo de Usuário</label>
                <div class="user-type-selector">
                    <div class="user-type-option">
                        <input type="radio" id="login_cliente" name="tipo_usuario" value="cliente" checked>
                        <label for="login_cliente" class="user-type-label">
                            <span class="user-type-icon">👤</span>
                            Cliente
                        </label>
                    </div>
                    <div class="user-type-option">
                        <input type="radio" id="login_admin" name="tipo_usuario" value="administrador">
                        <label for="login_admin" class="user-type-label">
                            <span class="user-type-icon">⚙️</span>
                            Administrador
                        </label>
                    </div>
                </div>
                
                <label>Email</label>
                <div class="input-container">
                    <input type="email" name="email" required>
                </div>
                <label>Senha</label>
                <div class="input-container">
                    <input type="password" name="senha" id="loginSenha" class="input-with-icon" required>
                    <span class="toggle-password" onclick="togglePasswordVisibility('loginSenha', this)">👁️</span>
                </div>
                <button type="submit" class="btn-primary">Entrar</button>
            </form>
            <div class="toggle-form">
                <p style="color:#fff;">Não tem conta? 
                <a href="#" class="toggle-link" onclick="toggleForm('register')">Criar conta</a></p>
            </div>
        </div>

        <!-- Formulário de Cadastro -->
        <div id="registerForm" class="form-section" style="display:none;">
            <h2>Criar Conta</h2>
            <form method="POST">
                <input type="hidden" name="acao" value="cadastrar">
                
                <label>Tipo de Usuário</label>
                <div class="user-type-selector">
                    <div class="user-type-option">
                        <input type="radio" id="register_cliente" name="tipo_usuario" value="cliente" checked>
                        <label for="register_cliente" class="user-type-label">
                            <span class="user-type-icon">👤</span>
                            Cliente
                        </label>
                    </div>
                </div>
                
                <label>Nome Completo</label>
                <div class="input-container">
                    <input type="text" name="nome" required>
                </div>
                <label>Email</label>
                <div class="input-container">
                    <input type="email" name="email" required>
                </div>
                <label>Senha</label>
                <div class="input-container">
                    <input type="password" name="senha" id="registerSenha" class="input-with-icon" required oninput="checkPasswordStrength(this.value)">
                    <span class="toggle-password" onclick="togglePasswordVisibility('registerSenha', this)">👁️</span>
                </div>
                
                <!-- Indicador de força da senha -->
                <div class="password-strength" id="passwordStrength" style="display:none;">
                    <div class="strength-bar">
                        <div class="strength-fill" id="strengthFill"></div>
                    </div>
                    <div class="strength-text" id="strengthText"></div>
                </div>
                
                <label>Confirmar Senha</label>
                <div class="input-container">
                    <input type="password" name="confirma" id="confirmSenha" class="input-with-icon" required oninput="checkPasswordMatch()">
                    <span class="toggle-password" onclick="togglePasswordVisibility('confirmSenha', this)">👁️</span>
                </div>
                
                <!-- Indicador de senhas coincidentes -->
                <div class="password-match" id="passwordMatch"></div>
                
                <button type="submit" class="btn-primary">Criar Conta</button>
            </form>
            <div class="toggle-form">
                <p style="color:#fff;">Já tem conta? 
                <a href="#" class="toggle-link" onclick="toggleForm('login')">Fazer login</a></p>
            </div>
        </div>
    </div>

    <script>
        // Alternar entre login e cadastro
        function toggleForm(formType) {
            document.getElementById("loginForm").style.display = (formType === "login") ? "block" : "none";
            document.getElementById("registerForm").style.display = (formType === "register") ? "block" : "none";
        }

        // Alternar visibilidade da senha
        function togglePasswordVisibility(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
                icon.textContent = "🚫";
            } else {
                input.type = "password";
                icon.textContent = "👁️";
            }
        }

        // Verificar força da senha
        function checkPasswordStrength(password) {
            const strengthContainer = document.getElementById("passwordStrength");
            const strengthFill = document.getElementById("strengthFill");
            const strengthText = document.getElementById("strengthText");

            if (password.length === 0) {
                strengthContainer.style.display = "none";
                return;
            }

            strengthContainer.style.display = "block";

            let score = 0;
            let feedback = [];

            // Critérios de força
            if (password.length >= 8) score += 1;
            else feedback.push("pelo menos 8 caracteres");

            if (/[a-z]/.test(password)) score += 1;
            else feedback.push("letras minúsculas");

            if (/[A-Z]/.test(password)) score += 1;
            else feedback.push("letras maiúsculas");

            if (/[0-9]/.test(password)) score += 1;
            else feedback.push("números");

            if (/[^A-Za-z0-9]/.test(password)) score += 1;
            else feedback.push("símbolos especiais");

            // Definir força da senha
            let strength, className, width;
            if (score <= 2) {
                strength = "Fraca";
                className = "weak";
                width = "33%";
            } else if (score <= 4) {
                strength = "Média";
                className = "medium";
                width = "66%";
            } else {
                strength = "Forte";
                className = "strong";
                width = "100%";
            }

            strengthFill.className = `strength-fill ${className}`;
            strengthFill.style.width = width;
            
            if (feedback.length > 0 && score < 5) {
                strengthText.textContent = `${strength} - Adicione: ${feedback.join(", ")}`;
            } else {
                strengthText.textContent = `Senha ${strength}`;
            }

            // Verificar se as senhas coincidem quando a força muda
            checkPasswordMatch();
        }

        // Verificar se as senhas coincidem
        function checkPasswordMatch() {
            const password = document.getElementById("registerSenha").value;
            const confirmPassword = document.getElementById("confirmSenha").value;
            const matchIndicator = document.getElementById("passwordMatch");

            if (confirmPassword.length === 0) {
                matchIndicator.textContent = "";
                return;
            }

            if (password === confirmPassword) {
                matchIndicator.textContent = "✅ Senhas coincidem";
                matchIndicator.className = "password-match match-success";
            } else {
                matchIndicator.textContent = "❌ Senhas não coincidem";
                matchIndicator.className = "password-match match-error";
            }
        }
    </script>
</body>
</html>