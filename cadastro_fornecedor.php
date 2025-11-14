<?php
// Inclui o arquivo que valida a sessão do usuário
include('valida_sessao.php');
// ... (código PHP existente)...
// ... (função redimensionarESalvarImagem)...
// ... (lógica de POST e GET)...
// ...
$marcas = $conn->query("SELECT * FROM fornecedores");

// Se foi solicitada a edição de uma marca (via GET), busca os dados dela para preencher o formulário
$marca = null;
if (isset($_GET['edit_id'])) {
// ... (código existente para buscar marca)...
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
        <!-- O 'action' vazio faz o post para a própria página -->
        <!-- 'enctype' é necessário para o upload de arquivos -->
        <form method="post" action="" enctype="multipart/form-data">
            <!-- ... (inputs do formulário: id, nome, email, telefone)... -->
    // Cria a nova imagem redimensionada
    $nova_imagem = imagecreatetruecolor($nova_largura, $nova_altura);
    imagecopyresampled($nova_imagem, $imagem_original, 0, 0, 0, 0, $nova_largura, $nova_altura, $largura_original, $altura_original);

    // Salva a nova imagem no destino
    if ($tipo_arquivo == "jpg" || $tipo_arquivo == "jpeg") { imagejpeg($nova_imagem, $caminho_completo, 90); } 
    elseif ($tipo_arquivo == "png") { imagepng($nova_imagem, $caminho_completo); } 
    elseif ($tipo_arquivo == "gif") { imagegif($nova_imagem, $caminho_completo); }

    // Libera a memória
    imagedestroy($imagem_original); imagedestroy($nova_imagem);
    return $caminho_completo;
}

// Verifica se o formulário foi enviado (método POST)
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
            // Se a função de upload retornar um erro
            $mensagem_erro = $resultado_upload;
        }
    }

    // Prepara a query SQL para inserção ou atualização
    if ($id) {
        // Se o ID existe, é uma atualização
        $sql = "UPDATE fornecedores SET nome=?, email=?, telefone=?";
        $params = [$nome, $email, $telefone];
        
        // Se uma nova imagem foi enviada, adiciona à query
        if($imagem) {
            $sql .= ", imagem=?";
            $params[] = $imagem;
        }
        
        $sql .= " WHERE id=?";
        $params[] = $id;
        
        $stmt = $conn->prepare($sql);
        // Define os tipos (string 's' e integer 'i') dinamicamente
        $types = str_repeat('s', count($params) - 1) . 'i'; 
        $stmt->bind_param($types, ...$params);
        
        $mensagem = "Marca atualizada com sucesso!";
    } else {
        // Se não há ID, é uma nova inserção
        $sql = "INSERT INTO fornecedores (nome, email, telefone, imagem) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $nome, $email, $telefone, $imagem);
        $mensagem = "Marca cadastrada com sucesso!";
    }

    // Executa a query e define a classe da mensagem (sucesso ou erro)
    if ($stmt->execute()) {
        $class = "success";
    } else {
        $mensagem = "Erro: " . $stmt->error;
        $class = "error";
    }
    $stmt->close();
}

// Verifica se foi solicitada a exclusão de uma marca (via GET)
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    // Antes de excluir, verifica se a marca tem artigos (produtos) vinculados
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM produtos WHERE fornecedor_id = ?");
    $check_stmt->bind_param("i", $delete_id);
    $check_stmt->execute();
    $check_produtos = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();
    
    if ($check_produtos['count'] > 0) {
        // Se tiver artigos, impede a exclusão
        $mensagem = "Não é possível excluir esta marca pois existem artigos esportivos cadastrados para ela.";
        $class = "error";
    } else {
        // Se não tiver artigos, prossegue com a exclusão
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

// Se foi solicitada a edição de uma marca (via GET), busca os dados dela para preencher o formulário
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
        <!-- O 'action' vazio faz o post para a própria página -->
        <!-- 'enctype' é necessário para o upload de arquivos -->
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
            
            <!-- Mostra a imagem atual se estiver editando -->
            <?php if (isset($marca['imagem']) && $marca['imagem']): ?>
                <img src="<?php echo $marca['imagem']; ?>" alt="Imagem atual da marca" class="update-image">
            <?php endif; ?>
            <br>
            <button type="submit"><?php echo $marca ? 'Atualizar' : 'Cadastrar'; ?></button>
        </form>
        
        <!-- Exibe mensagens de sucesso ou erro (SEÇÃO CORRIGIDA) -->
        <?php if (isset($mensagem)): ?>
            <p class="message <?php echo $class; ?>"><?php echo $mensagem; ?></p>
        <?php endif; ?>
        <?php if (isset($mensagem_erro)): ?>
            <p class="message error"><?php echo $mensagem_erro; ?></p>
        <?php endif; ?>

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
                <!-- Loop para exibir cada marca -->
                <?php while ($row = $marcas->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['nome']; ?></td>
                    <td><?php echo $row['email']; ?></td>
                    <td><?php echo $row['telefone']; ?></td>
                    <td>
                        <!-- Mostra a miniatura da imagem se ela existir -->
                        <?php if ($row['imagem']): ?>
                            <img src="<?php echo $row['imagem']; ?>" alt="Imagem da marca" class="thumbnail">
                        <?php else: ?>
                            Sem imagem
                        <?php endif; ?>
                    </td>
                    <td>
                        <!-- Links de Ação (Editar e Excluir) -->
                        <a href="?edit_id=<?php echo $row['id']; ?>">Editar</a>
                        <a href="?delete_id=<?php echo $row['id']; ?>" onclick="return confirm('Tem certeza que deseja excluir?')">Excluir</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <div class="actions">
          <!-- Botão "Voltar" atualizado para 'painel.php' -->
          <a href="painel.php" class="back-button">Voltar ao Painel</a>
        </div>
    </div>
</body>
</html>