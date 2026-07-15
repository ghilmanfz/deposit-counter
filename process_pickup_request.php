<?php
  require_once('includes/load.php');
  require_permission('pickup','process');

  if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    $session->msg('d','Aksi request pengambilan hanya dapat dilakukan melalui formulir yang sah.');
    redirect('pickup_requests.php', false);
  }

  $csrf_valid = warehouse_csrf_is_valid(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '');
  if(!$csrf_valid){
    $session->msg('d','Sesi aksi tidak valid atau sudah kedaluwarsa. Silakan coba kembali.');
    redirect('pickup_requests.php', false);
  }

  $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  $action = isset($_POST['action']) ? $_POST['action'] : '';

  if($id <= 0){
    $session->msg('d','Request pengambilan tidak valid.');
    redirect('pickup_requests.php', false);
  }

  if($action === 'approve'){
    if(approve_pickup_request($id)){
      $session->msg('s','Request berhasil disetujui. Stok belum dipotong sampai admin menjalankan proses pengambilan atau pengiriman.');
    } else {
      $session->msg('d','Request gagal disetujui. Pastikan status masih menunggu dan seluruh bundle masih tersedia.');
    }
    redirect('pickup_requests.php', false);
  }

  if($action === 'process'){
    if(!function_exists('process_pickup_request_stock')){
      $session->msg('d','Layanan proses pengambilan belum tersedia. Tidak ada stok yang diubah.');
      redirect('pickup_requests.php', false);
    }

    $request = find_pickup_request_details($id);
    $fulfillment_method = $request ? normalize_pickup_fulfillment_method(isset($request['fulfillment_method']) ? $request['fulfillment_method'] : 'self_pickup') : null;
    $is_delivery = $fulfillment_method === 'delivery';
    $transport_data = array();
    if($is_delivery){
      $transport_data['driver_name'] = isset($_POST['driver_name']) ? trim((string)$_POST['driver_name']) : '';
      $transport_data['vehicle_no'] = isset($_POST['vehicle_no']) ? trim((string)$_POST['vehicle_no']) : '';
      if($transport_data['driver_name'] === '' || $transport_data['vehicle_no'] === ''){
        $session->msg('d','Nama supir dan pelat kendaraan wajib diisi sebelum pengiriman diproses. Stok belum berubah.');
        redirect('pickup_requests.php', false);
      }
    }

    if(process_pickup_request_stock($id, $transport_data)){
      $session->msg('s',($is_delivery ? 'Pengiriman' : 'Pengambilan').' berhasil diproses. Stok seluruh bundle telah dimutasi satu kali dan Surat Jalan siap dicetak.');
    } else {
      $session->msg('d',($is_delivery ? 'Pengiriman' : 'Pengambilan').' gagal diproses. Tidak ada pemotongan sebagian; periksa status request, transportasi, dan ketersediaan bundle.');
    }
    redirect('pickup_requests.php', false);
  }

  $session->msg('d','Aksi request pengambilan tidak dikenal.');
  redirect('pickup_requests.php', false);
?>
