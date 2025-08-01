<?php
$password = "***USER_PASSWORD_REMOVED***";
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Hash generado: <br><code>$hash</code>";
