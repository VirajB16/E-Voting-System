<?php
require_once 'includes/session.php';
destroyUserSession();
header('Location: index.php');
exit();
?>
