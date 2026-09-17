<?php
if (!defined('TRENDYPOST')) die('Direct access not permitted.');
Auth::logout();
header('Location: ' . url());
exit;
?>