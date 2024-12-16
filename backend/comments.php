<?php

require 'db.php'; 

function crearComentario($task_id, $comment)
{
  global $pdo;
  try {
    $sql = "INSERT INTO comments (task_id, comment) VALUES (:task_id, :comment)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      'task_id' => $task_id,
      'comment' => $comment
    ]);
    return $pdo->lastInsertId();
  } catch (Exception $e) {
    logError("Error creando comentario: " . $e->getMessage());
    return 0;
  }
}

function editarComentario($id, $comment)
{
    global $pdo;
    try {
        $sql = "UPDATE comments SET comment = :comment WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'comment' => $comment,
            'id' => $id
        ]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        logError("Error editando comentario: " . $e->getMessage());
        return false;
    }
}

function obtenerComentariosPorTask($task_id)
{
  global $pdo;
  try {
    $sql = "SELECT * FROM comments WHERE task_id = :task_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['task_id' => $task_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (Exception $e) {
    logError("Error al obtener comentarios: " . $e->getMessage());
    return [];
  }
}

$method = $_SERVER['REQUEST_METHOD'];
header('Content-Type: application/json');

function getJsonInput()
{
  return json_decode(file_get_contents("php://input"), true);
}

session_start();
if (isset($_SESSION['user_id'])) {
  $user_id = $_SESSION['user_id'];

  switch ($method) {
    case 'GET':
      if (isset($_GET['task_id'])) {
        $task_id = $_GET['task_id'];
        $comentarios = obtenerComentariosPorTask($task_id);
        echo json_encode($comentarios);
      } else {
        http_response_code(400);
        echo json_encode(["error" => "task_id es necesario"]);
      }
      break;

    case 'POST':
      $input = getJsonInput();
      if (isset($input['task_id'], $input['comment'])) {
        $task_id = $input['task_id'];
        $comment = $input['comment'];
        $id = crearComentario($task_id, $comment);
        if ($id > 0) {
          http_response_code(201);
          echo json_encode(["newId" => $id]);
        } else {
          http_response_code(500);
          echo json_encode(["error" => "Error general creando el comentario"]);
        }
      } else {
        http_response_code(400);
        echo json_encode(["error" => "Datos incorrectos"]);
      }
      break;

      case 'PUT':
        $input = getJsonInput();
        if (isset($input['comment']) && isset($_GET['id'])) {
            $editResult = editarComentario($_GET['id'], $input['comment']);
            if ($editResult) {
                http_response_code(200);
                echo json_encode(["message" => "Comentario actualizado"]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Error actualizando el comentario"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Datos insuficientes"]);
        }
        break;

    case 'DELETE':
      if (isset($_GET['commentId'])) {
        $commentId = $_GET['commentId']; 
        logDebug ("id -> " . $commentId);
        $sql = "DELETE FROM comments WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $commentId, PDO::PARAM_INT);

        if ($stmt->execute()) {
          if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(['message' => "Comentario eliminado"]);
          } else {
            http_response_code(404);
            echo json_encode(['message' => 'Comentario no encontrado']);
          }
        } else {
          http_response_code(500);
          echo json_encode(['message' => 'Error al eliminar el comentario']);
        }
      } else {
        http_response_code(400);
        echo json_encode(["error" => "El id del comentario es necesario"]);
      }
      break;

    default:
      http_response_code(405);
      echo json_encode(["error" => "Método no permitido"]);
      break;
  }
} else {
  http_response_code(401);
  echo json_encode(["error" => "Sesión no activa"]);
}
