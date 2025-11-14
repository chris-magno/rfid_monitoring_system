<<<<<<< HEAD
<?php
$hashed = '$2y$10$2b4x1ixyO9ctY5bZ5E4qXOmjZp3Kxt5E7WCeCQcOzWlZp.m5gSthS';
$password = 'admin123';
echo password_hash('admin123', PASSWORD_BCRYPT);

if (password_verify($password, $hashed)) {
    echo "✅ Password matches!";
} else {
    echo "❌ Password invalid!";
}
=======
<?php
$hashed = '$2y$10$2b4x1ixyO9ctY5bZ5E4qXOmjZp3Kxt5E7WCeCQcOzWlZp.m5gSthS';
$password = 'admin123';
echo password_hash('admin123', PASSWORD_BCRYPT);

if (password_verify($password, $hashed)) {
    echo "✅ Password matches!";
} else {
    echo "❌ Password invalid!";
}
>>>>>>> 13c008637d0328504b4f32dc4f7283c97b07a45c
