<?php
  /*
    Função que converte os parâmetros
    de requisições HTTP
    POST e PUT em um new (notícia)
    *
    */
  function f_parametro_to_new()
  {

    // Obtém o conteúdo da requisição
    $dados = file_get_contents("php://input");

    // Converte para Json e retornar
    $new = json_decode($dados, true);
    return $new;
  }

  /*
  * Função que retorna uma conexão
  * com o banco de dados.
  *
  */
  function f_obtem_conexao(){
    // Parâmetros
    $servidor = "localhost";
    $usuario = "root";
    $senha = "";
    $bancodedados = "new"; // tradução (notícia)
    
    // Cria uma conexão com o banco de dados
    $conexao = new mysqli( $servidor
      , $usuario
      , $senha
      , $bancodedados);

    // Verifica a conexão
    if ($conexao->connect_error) {
      die("Falha na conexão: " .
      $conexao->connect_error);
    }
    
    return $conexao;
  }

  /*
    * Função que retorna as news (notícias)
    *
    */
  function f_select_new()
  {
    // cria uma cláusula WHERE com os
    // parâmetros que foram
    // recebidos através da requisição
    // HTTP get
    $queryWhere = " WHERE ";
    $primeiroParametro = true;
    $parametrosGet = array_keys($_GET);

    foreach ($parametrosGet as $param) {
      
      if (!$primeiroParametro) {
        $queryWhere .= " AND ";
      }

      $primeiroParametro = false;
      $queryWhere .= $param . " = '" . $_GET[$param] . "'";
    }

    // Executa a query da variável $sql
    $sql = " SELECT news.id " .
      " , news.description " .
      " , news.published " .
      " , news.tittle " .
      " , articles.body " .
      " FROM news " . 
      " LEFT JOIN articles ON (news.id = articles.new_id) ";

    // utiliza o where criado com base
    // nos parâmetros do GET

    if ($queryWhere != " WHERE ") {
      $sql .= $queryWhere;
      
    }

    // Obtém a conexão com o DB
    $conexao = f_obtem_conexao();

    // Executa a query
    $resultado = $conexao->query($sql);

    // Verifica se a query retornou registros
    if ($resultado->num_rows > 0) {
      // Inicializa o array para
      // a formação dos objetos JSON

      $jsonNewArray = array();
      $contador = 0;

      while ($registro = $resultado->fetch_assoc()) {

        // Monta um objeto Json
        // através de um array associativo ,
        // ou seja, indexado através de strings
        $jsoNew = array();
        $jsoNew["id"] = $registro["id"];
        $jsoNew["description"] = $registro["description"];
        $jsoNew["published"] = $registro["published"];
        $jsoNew["tittle"] = $registro["tittle"];
        $jsoNew["body"] = $registro["body"];
        $jsonNewArray[$contador++] =  $jsoNew;
      }

      // Transforma o array com
      // os resultados da query
      // em um array Json e imprime-o
      // na página
      echo json_encode($jsonNewArray);
    } else {
      // Se a query não retornou
      // registros, devolve um
      // array Json vazio
      echo json_encode(array());
    }


    // Fecha a conexão com o MySQL
    $conexao->close();
  }
  /*
    * Insere um nova notícia ou new na tabela news
    *
    */
  function f_insert_new() {
    $new = f_parametro_to_new();

    // Busca nome que foi recebido
    // via post através do formulário
    // de cadastro
    $tittle = $new["tittle"];
    $description = $new["description"];
    $body = $new["body"];


    // Insere o notícia na tabela
    // news do banco de dados
    $sql = "INSERT INTO news (tittle, description, published ) VALUES ('".$tittle."','".$description."',now())";

    // Obtem a conexão
    $conexao = f_obtem_conexao();

    // Verifica se ocorreu tudo bem
    // Caso houve erro, fecha a conexão
    // e aborta o programa
    if (!($conexao->query($sql) === TRUE)) {
      $conexao->close();
      die("Erro: " . $sql . "<br>" . $conexao->error);
    }
    

    // Insere as demais informações
    // no Json
    $new["id"] = mysqli_insert_id($conexao);
    echo json_encode($new);
    
    // Insere o notícia na tabela
    // news do banco de dados
    $sql = "INSERT INTO articles (new_id, body) VALUES ('".$new["id"]."','".$body."')";
    
    if (!($conexao->query($sql) === TRUE)) {
      $conexao->close();
      die("Erro: " . $sql . "<br>" . $conexao->error);
    }    

    // Fecha a conexão com o
    // Banco de dados
    $conexao->close();
  }
  /*
    * Atualiza uma notícia (new) existente
    *
    */
  function f_update_new(){
    $new = f_parametro_to_new();

    $id = $new["id"];
    $tittle = $new["tittle"];
    $description = $new["description"];
    $body = $new["body"];

    $sql = " UPDATE news "
    ." SET tittle = '".$tittle."' "
    .", description = '".$description."'"
    ." WHERE id = ".$id; 

    // Obtém a cnexão com o banco
    // de dados  
    $conn = f_obtem_conexao();

    // Verifica se o comando foi
    // executado com sucesso
    if (!($conn->query($sql) === TRUE)) {
      $conn->close();
      die("Erro ao atualizar: " . $conn->error);
    }

    $sql = " UPDATE articles "
    ." SET body = '".$body."'"
    ." WHERE new_id = ".$id;

    // Verifica se o comando foi
    // executado com sucesso
    if (!($conn->query($sql) === TRUE)) {
      $conn->close();
      die("Erro ao atualizar: " . $conn->error);
    }    

    // retorna o Registro
    // atualizado
    echo json_encode($new);

    // Fecha a conexão
    $conn->close();    

  }
  /*
    * Exclui uma new (notícia) existente
    *
    */
  function f_delete_new(){
    // Obtém o id do registro
    // que foi recebido via get
    $id = $_GET["id"];

    $sql1 = "DELETE FROM articles WHERE new_id=".$id;

    $sql2 = "DELETE FROM news WHERE id=".$id;
    
    // Obtém a Conexão
    $conn = f_obtem_conexao();

    // Executa o comando delete
    // da variável $sql
    if (!($conn->query($sql1) === TRUE)) {
      die("Erro ao deletar: "
      . $conn->error);
    }

    if (!($conn->query($sql2) === TRUE)) {
      die("Erro ao deletar: "
      . $conn->error);
    }    
    
    $conn->close();
  }

  // A variável de servidor REQUEST_METHOD
  // contém o nome do método HTTP através
  // qual o arquivo solicitado foi
  // acessado
  $metodo = $_SERVER['REQUEST_METHOD'];

  // Verifica qual ação a ser tomada
  // de acordo com o método HTTP
  // que foi utilizado para acessar
  // este recurso
  switch ($metodo) {

    // Se foi GET
    // deve consultar
    case "GET":
      f_select_new();
      break;
    
    // Se foi POST
    // deve inserir
    case "POST":
      f_insert_new();
      break;
    
    // Se foi put
    // deve alterar
    case "PUT":
      f_update_new();
      break;
    
    // Se foi delete
    // deve excluir
    case "DELETE":
      f_delete_new();
      break;
}
