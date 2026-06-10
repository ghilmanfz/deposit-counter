<?php
ob_start();
session_start();
if (!empty($_SESSION['admin'])) {
    require '../../config.php';
    require_once __DIR__.'/../csrf.php';

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
        ob_clean();
        http_response_code(403);
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                if (typeof Swal !== "undefined") {
                    Swal.fire({
                        title: "Akses Ditolak!",
                        text: "Anda tidak memiliki izin untuk menghapus data.",
                        icon: "error",
                        confirmButtonColor: "#e74a3b"
                    }).then(function() {
                        window.location = "../../index.php";
                    });
                } else {
                    alert("Akses Ditolak! Anda tidak memiliki izin.");
                    window.location = "../../index.php";
                }
            });
        </script>';
        exit;
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
            echo '<script>alert("Data kategori tidak valid");history.go(-1);</script>';
            exit;
        }

        $sql = 'DELETE FROM kategori WHERE id_kategori=?';
        $row = $config->prepare($sql);
        $row->execute([$id]);
        echo '<script>window.location="../../index.php?page=kategori&&remove=hapus-data"</script>';
    }

    if (get_get_param('barang') !== '') {
        $id = get_get_param('id');
        if ($id === '' || !preg_match('/^[A-Za-z0-9-]+$/', $id)) {
            echo '<script>alert("Data barang tidak valid");history.go(-1);</script>';
            exit;
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
            echo '<script>alert("Data penjualan tidak valid");history.go(-1);</script>';
            exit;
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
            echo '<script>alert("Data user tidak valid");history.go(-1);</script>';
            exit;
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
            echo '<script>alert("Data customer tidak valid");history.go(-1);</script>';
            exit;
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
