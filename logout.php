<?php
/**
 * Logout - Uses New Auth System
 */

require_once __DIR__ . '/app/Controllers/AuthController.php';

AuthController::logout();
