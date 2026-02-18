<?php
header('Content-Type: text/plain; charset=utf-8');
echo password_hash('admin123', PASSWORD_DEFAULT);
