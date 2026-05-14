<?php
require_once __DIR__ . '/../auth.php';

auth_require_role(1);

$html = file_get_contents(__DIR__ . '/index.html');

if ($html !== false && strpos($html, '../login.php') === false) {
	$loginButton = <<<HTML
						<!-- Nav Item - Login Button -->
						<li class="nav-item d-flex align-items-center mr-2">
							<a href="../login.php" class="btn btn-primary btn-sm shadow-sm">
								<i class="fas fa-sign-in-alt fa-sm text-white-50 mr-1"></i> Login
							</a>
						</li>
HTML;

	$needle = "                    <!-- Topbar Navbar -->\n                    <ul class=\"navbar-nav ml-auto\">";
	$replacement = "                    <!-- Topbar Navbar -->\n                    <ul class=\"navbar-nav ml-auto\">\n" . $loginButton;
	$html = str_replace($needle, $replacement, $html);
}

echo $html;
