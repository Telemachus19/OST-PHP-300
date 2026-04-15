<?php
session_start();
require_once __DIR__ . '/user_store.php';

if (is_authenticated()) {
	header('Location: listUsers.php');
	exit;
}

header('Location: login.php');
exit;
