<?php
ob_start();
session_start();
if (!empty($_SESSION['admin'])) {
    require '../../config.php';
    require_once __DIR__.'/../csrf.php';

    if (!function_exists('render_action_error_modal')) {
        function render_action_error_modal(string $title, string $message, string $redirect): void
        {
            ob_clean();
            $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
            $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
            $safeRedirect = htmlspecialchars($redirect, ENT_QUOTES, 'UTF-8');
            echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
            echo '<title>'.$safeTitle.'</title><style>';
            echo 'body{margin:0;font-family:Arial,sans-serif;background:#f1f5f9;color:#0f172a}.app-modal-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.58);display:flex;align-items:center;justify-content:center;padding:20px}.app-modal{width:100%;max-width:460px;background:#fff;border-radius:14px;box-shadow:0 24px 60px rgba(15,23,42,.28);overflow:hidden}.app-modal-header{padding:20px 24px;border-bottom:1px solid #e2e8f0}.app-modal-header h2{font-size:20px;margin:0}.app-modal-body{padding:24px;line-height:1.6;color:#475569}.app-modal-footer{padding:16px 24px;border-top:1px solid #e2e8f0;text-align:right}.app-modal-button{display:inline-block;padding:10px 18px;border-radius:8px;background:#0f172a;color:#fff;text-decoration:none;font-weight:700}</style></head><body>';
            echo '<div class="app-modal-backdrop"><section class="app-modal" role="alertdialog" aria-modal="true" aria-labelledby="app-error-title"><header class="app-modal-header"><h2 id="app-error-title">'.$safeTitle.'</h2></header><div class="app-modal-body">'.$safeMessage.'</div><footer class="app-modal-footer"><a class="app-modal-button" href="'.$safeRedirect.'">Kembali</a></footer></section></div>';
            echo '</body></html>';
            exit;
        }
    }

    // ============================================================
    // VALIDASI ROLE: Hanya owner dan admin yang boleh hapus data
    // kasir (User Biasa) TIDAK boleh mengakses halaman ini
    // ============================================================
    $roleUser = $_SESSION['admin']['role'] ?? '';
    if (empty($roleUser)) {
        $roleUser = 'owner';
    }

    $allowedRoles = ['owner', 'admin'];
    if (!in_array($roleUser, $allowedRoles, true)) {
        // Proteksi: kasir yang coba akses URL hapus secara langsung ditolak
        http_response_code(403);
        render_action_error_modal('Akses Ditolak', 'Anda tidak memiliki izin untuk menghapus data.', '../../index.php');
    }

    $csrfToken = filter_input(
        INPUT_GET,
        'csrf_token',
        FILTER_UNSAFE_RAW,
        ['flags' => FILTER_FLAG_NO_ENCODE_QUOTES]
    );
    csrf_require_token($csrfToken ?? '');

    if (!function_exists('sanitize_scalar_input')) {
        function sanitize_scalar_input($value, bool $allowNewlines = false): string
        {
            $stringValue = trim((string) $value);
            $pattern = $allowNewlines ? '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u' : '/[\x00-\x1F\x7F]/u';
            $cleaned = preg_replace($pattern, '', $stringValue);

            return $cleaned === null ? '' : trim($cleaned);
        }
    }

    if (!function_exists('get_get_param')) {
        function get_get_param(string $key): string
        {
            $value = filter_input(
                INPUT_GET,
                $key,
                FILTER_UNSAFE_RAW,
                ['flags' => FILTER_FLAG_NO_ENCODE_QUOTES]
            );

            if ($value === null) {
                return '';
            }

            return sanitize_scalar_input($value);
        }
    }

    if (get_get_param('kategori') !== '') {
        $id = get_get_param('id');
        if ($id === '' || !ctype_digit($id)) {
            render_action_error_modal('Data Tidak Valid', 'Data kategori tidak valid.', '../../index.php?page=kategori');
        }

        $sql = 'DELETE FROM kategori WHERE id_kategori=?';
        $row = $config->prepare($sql);
        $row->execute([$id]);
        echo '<script>window.location="../../index.php?page=kategori&&remove=hapus-data"</script>';
    }

    if (get_get_param('barang') !== '') {
        $id = get_get_param('id');
        if ($id === '' || !preg_match('/^[A-Za-z0-9-]+$/', $id)) {
            render_action_error_modal('Data Tidak Valid', 'Data barang tidak valid.', '../../index.php?page=barang');
        }

        $sql = 'DELETE FROM barang WHERE id_barang=?';
        $row = $config->prepare($sql);
        $row->execute([$id]);
        echo '<script>window.location="../../index.php?page=barang&&remove=hapus-data"</script>';
    }

    if (get_get_param('jual') !== '') {
        // Check if AJAX request
        $isAjax = (get_get_param('ajax') === '1') || 
                  (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
        
        $barangId = get_get_param('brg');
        $penjualanId = get_get_param('id');
        if ($barangId === '' || !preg_match('/^[A-Za-z0-9-]+$/', $barangId) || $penjualanId === '' || !ctype_digit($penjualanId)) {
            if ($isAjax) {
                ob_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Data penjualan tidak valid']);
                exit;
            }
            render_action_error_modal('Data Tidak Valid', 'Data penjualan tidak valid.', '../../index.php?page=jual');
        }

        $sqlI = 'select*from barang where id_barang=?';
        $rowI = $config->prepare($sqlI);
        $rowI->execute([$barangId]);
        $rowI->fetch();

        $sql = 'DELETE FROM penjualan WHERE id_penjualan=?';
        $row = $config->prepare($sql);
        $row->execute([$penjualanId]);
        
        if ($isAjax) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Barang berhasil dihapus']);
            exit;
        }
        
        echo '<script>window.location="../../index.php?page=jual"</script>';
    }

    if (get_get_param('penjualan') !== '') {
        // Check if AJAX request
        $isAjax = (get_get_param('ajax') === '1') || 
                  (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
        
        $sql = 'DELETE FROM penjualan';
        $row = $config->prepare($sql);
        $row->execute();
        
        if ($isAjax) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Keranjang berhasil dikosongkan']);
            exit;
        }
        
        echo '<script>window.location="../../index.php?page=jual"</script>';
    }

    if (get_get_param('laporan') !== '') {
        $sql = 'DELETE FROM nota';
        $row = $config->prepare($sql);
        $row->execute();
        echo '<script>window.location="../../index.php?page=laporan&remove=hapus"</script>';
    }

    if (get_get_param('user') !== '') { // tetap untuk trigger hapus
        $id = get_get_param('id');      // ambil id member yang benar
        if ($id === '' || !ctype_digit($id)) {
            render_action_error_modal('Data Tidak Valid', 'Data user tidak valid.', '../../index.php?page=kelola_user/list_user');
        }

        // Hapus dari tabel login dulu
        $sqlLogin = 'DELETE FROM login WHERE id_member=?';
        $rowLogin = $config->prepare($sqlLogin);
        $rowLogin->execute([$id]);

        // Hapus dari tabel member
        $sqlMember = 'DELETE FROM member WHERE id_member=?';
        $rowMember = $config->prepare($sqlMember);
        $rowMember->execute([$id]);

        echo '<script>window.location="../../index.php?page=kelola_user/list_user&remove=hapus-data"</script>';
    }

    // Hapus Customer
    if (get_get_param('customer') !== '') {
        $id = get_get_param('customer');
        if ($id === '' || !ctype_digit($id)) {
            render_action_error_modal('Data Tidak Valid', 'Data customer tidak valid.', '../../index.php?page=customer');
        }

        // Hapus dari tabel customer
        $sql = 'DELETE FROM customer WHERE id_customer=?';
        $row = $config->prepare($sql);
        $row->execute([$id]);

        echo '<script>window.location="../../index.php?page=customer&remove=hapus-data"</script>';
    }
} else {
    echo '<script>window.location="../../index.php";</script>';
    exit;
}
