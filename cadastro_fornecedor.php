<?php
// Inclui o arquivo que valida a sessão do usuário
include('valida_sessao.php');
// Inclui o arquivo de conexão com o banco de dados
include('conexao.php');

// Função para redimensionar e salvar a imagem (sem alterações na lógica)
function redimensionarESalvarImagem($arquivo, $largura = 80, $altura = 80) {
    $diretorio_destino = "img/";
    if (!file_exists($diretorio_destino)) {
        mkdir($diretorio_destino, 0777, true);
    }
    $nome_arquivo = uniqid() . '_' . basename($arquivo["name"]);
    $caminho_completo = $diretorio_destino . $nome_arquivo;
    $tipo_arquivo = strtolower(pathinfo($caminho_completo, PATHINFO_EXTENSION));

    $check = getimagesize($arquivo["tmp_name"]);
    if($check === false) { return "O arquivo não é uma imagem válida."; }
    if ($arquivo["size"] > 5000000) { return "O arquivo é muito grande. O tamanho máximo permitido é 5MB."; }
    if($tipo_arquivo != "jpg" && $tipo_arquivo != "png" && $tipo_arquivo != "jpeg" && $tipo_arquivo != "gif" ) { return "Apenas arquivos JPG, JPEG, PNG e GIF são permitidos."; }

    if ($tipo_arquivo == "jpg" || $tipo_arquivo == "jpeg") { $imagem_original = imagecreatefromjpeg($arquivo["tmp_name"]); } 
    elseif ($tipo_arquivo == "png") { $imagem_original = imagecreatefrompng($arquivo["tmp_name"]); } 
    elseif ($tipo_arquivo == "gif") { $imagem_original = imagecreatefromgif($arquivo["tmp_name"]); }

    $largura_original = imagesx($imagem_original); $altura_original = imagesy($imagem_original);
    $ratio = min($largura / $largura_original, $altura / $altura_original);
    $nova_largura = $largura_original * $ratio; $nova_altura = $altura_original * $ratio;
    $nova_imagem = imagecreatetruecolor($nova_largura, $nova_altura);
    imagecopyresampled($nova_imagem, $imagem_original, 0, 0, 0, 0, $nova_largura, $nova_altura, $largura_original, $altura_original);

    if ($tipo_arquivo == "jpg" || $tipo_arquivo == "jpeg") { imagejpeg($nova_imagem, $caminho_completo, 90); } 
    elseif ($tipo_arquivo == "png") { imagepng($nova_imagem, $caminho_completo); } 
    elseif ($tipo_arquivo == "gif") { imagegif($nova_imagem, $caminho_completo); }

    imagedestroy($imagem_original); imagedestroy($nova_imagem);
    return $caminho_completo;
}

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];

    // Processa o upload da imagem
    $imagem = "";
    if(isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
        $resultado_upload = redimensionarESalvarImagem($_FILES['imagem']);
        if(strpos($resultado_upload, 'img/') === 0) {
            $imagem = $resultado_upload;
        } else {
            $mensagem_erro = $resultado_upload;
        }
    }

    // Prepara a query SQL para inserção ou atualização (tabela 'fornecedores' mantida)
    if ($id) {
        // Se o ID existe, é uma atualização
        $sql = "UPDATE fornecedores SET nome=?, email=?, telefone=?";
        $params = [$nome, $email, $telefone];
        
        if($imagem) {
            $sql .= ", imagem=?";
            $params[] = $imagem;
        }
        
        $sql .= " WHERE id=?";
        $params[] = $id;
        
        $stmt = $conn->prepare($sql);
        $types = str_repeat('s', count($params) - 1) . 'i'; // Tipos para bind_param
        $stmt->bind_param($types, ...$params);
        
        $mensagem = "Marca atualizada com sucesso!";
    } else {
        // Se não há ID, é uma nova inserção
        $sql = "INSERT INTO fornecedores (nome, email, telefone, imagem) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $nome, $email, $telefone, $imagem);
        $mensagem = "Marca cadastrada com sucesso!";
    }

    // Executa a query e verifica se houve erro
    if ($stmt->execute()) {
        $class = "success";
    } else {
        $mensagem = "Erro: " . $stmt->error;
        $class = "error";
    }
    $stmt->close();
}

// Verifica se foi solicitada a exclusão de uma marca
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    // Verifica se a marca tem artigos cadastrados
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM produtos WHERE fornecedor_id = ?");
    $check_stmt->bind_param("i", $delete_id);
    $check_stmt->execute();
    $check_produtos = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();
    
    if ($check_produtos['count'] > 0) {
        $mensagem = "Não é possível excluir esta marca pois existem artigos esportivos cadastrados para ela.";
        $class = "error";
    } else {
        $delete_stmt = $conn->prepare("DELETE FROM fornecedores WHERE id=?");
        $delete_stmt->bind_param("i", $delete_id);
        if ($delete_stmt->execute()) {
            $mensagem = "Marca excluída com sucesso!";
            $class = "success";
        } else {
            $mensagem = "Erro ao excluir marca: " . $delete_stmt->error;
            $class = "error";
        }
        $delete_stmt->close();
    }
}

// Busca todas as marcas para listar na tabela
$marcas = $conn->query("SELECT * FROM fornecedores");

// Se foi solicitada a edição de uma marca, busca os dados dela
$marca = null;
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $edit_stmt = $conn->prepare("SELECT * FROM fornecedores WHERE id=?");
    $edit_stmt->bind_param("i", $edit_id);
    $edit_stmt->execute();
    $marca = $edit_stmt->get_result()->fetch_assoc();
    $edit_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Marca</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container" style="width: 900px;">
        <h2>Cadastro de Marca</h2>
        <!-- Formulário para cadastro/edição de marca -->
        <form method="post" action="" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $marca['id'] ?? ''; ?>">
            
            <label for="nome">Nome:</label>
            <input type="text" name="nome" value="<?php echo $marca['nome'] ?? ''; ?>" required>
            
            <label for="email">Email:</label>
            <input type="email" name="email" value="<?php echo $marca['email'] ?? ''; ?>">
            
            <label for="telefone">Telefone:</label>
            <input type="text" name="telefone" value="<?php echo $marca['telefone'] ?? ''; ?>">
            
            <label for="imagem">Logo/Imagem da Marca:</label>
            <input type="file" name="imagem" accept="image/*">
            <?php if (isset($marca['imagem']) && $marca['imagem']): ?>
                <img src="<?php echo $marca['imagem']; ?>" alt="Imagem atual da marca" class="update-image">
            <?php endif; ?>
            <br>
            <button type="submit"><?php echo $marca ? 'Atualizar' : 'Cadastrar'; ?></button>
        </form>
        
        <!-- Exibe mensagens de sucesso ou erro -->
        <?php
        if (isset($mensagem)) echo "<p class='message " . $class . "'>$mensagem</p>";
        if (isset($mensagem_erro)) echo "<p class='message error'>$mensagem_erro</p>";
        ?>

        <h2>Listagem de Marcas</h2>
        <!-- Tabela para listar as marcas cadastradas -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Telefone</th>
                    <th>Imagem</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $marcas->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['nome']; ?></td>
                    <td><?php echo $row['email']; ?></td>
                    <td><?php echo $row['telefone']; ?></td>
                    <td>
                        <?php if ($row['imagem']): ?>
                            <img src="<?php echo $row['imagem']; ?>" alt="Imagem da marca" class="thumbnail">
                        <?php else: ?>
                            Sem imagem
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="?edit_id=<?php echo $row['id']; ?>">Editar</a>
                        <a href="?delete_id=<?php echo $row['id']; ?>" onclick="return confirm('Tem certeza que deseja excluir?')">Excluir</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <div class="actions">
          <a href="index.php" class="back-button">Voltar ao Painel</a>
        </div>
    </div>
</body>
</html>