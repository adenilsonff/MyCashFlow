<?php if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(404); exit; } ?>
<footer>
    <p>MyCashFlow © 2026 - Sistema de Finanças Pessoais</p>
</footer>
</body>
</html>