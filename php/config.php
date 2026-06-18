<?php
// ── Database connection ──────────────────────────────────────────
// Fill these in for your MySQL / MariaDB host, then create the database:
//   CREATE DATABASE salon CHARACTER SET utf8mb4;
// Tables are created automatically on first load (see ensureSchema in api.php).
const DB_HOST = 'localhost';
const DB_NAME = 'salon';
const DB_USER = 'root';
const DB_PASS = '';

function db() {
  static $pdo = null;
  if ($pdo === null) {
    $pdo = new PDO(
      'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
      DB_USER,
      DB_PASS,
      [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      ]
    );
  }
  return $pdo;
}
