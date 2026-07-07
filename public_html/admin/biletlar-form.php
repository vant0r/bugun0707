<?php
require_once __DIR__ . '/../includes/panel_layout.php';
vpy_require_admin('/login.php');
vpy_redirect('/admin/savollar-form.php' . ((int)vpy_get('bilet') ? '?bilet_id=' . (int)vpy_get('bilet') : ''));
