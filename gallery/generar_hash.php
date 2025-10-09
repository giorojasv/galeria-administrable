<?php
// Contraseñas de prueba
$password_admin = 'adminpass';
$password_editor = '123456';

// Generar HASHES
$hash_admin = password_hash($password_admin, PASSWORD_DEFAULT);
$hash_editor = password_hash($password_editor, PASSWORD_DEFAULT);

echo "<h1>Hashes Generados:</h1>";
echo "<h2>Para el usuario 'admin' (Contraseña: adminpass):</h2>";
echo "<p><strong>HASH:</strong> <code>" . $hash_admin . "</code></p>";
echo "<hr>";
echo "<h2>Para el usuario 'editor' (Contraseña: 123456):</h2>";
echo "<p><strong>HASH:</strong> <code>" . $hash_editor . "</code></p>";
echo "<hr>";
echo "<p><strong>Instrucción:</strong> Copia los códigos HASH completos (incluyendo $2y$) e insértalos en tu tabla 'galeria_usuarios'.</p>";
?>